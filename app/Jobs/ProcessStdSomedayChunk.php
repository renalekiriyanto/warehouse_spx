<?php

namespace App\Jobs;

use App\Models\Driver;
use App\Models\ImportBatch;
use App\Models\StdSomeday;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class ProcessStdSomedayChunk implements ShouldQueue
{
    use Queueable, Batchable;

    public int $tries   = 3;
    public int $timeout = 180;

    public function __construct(
        public readonly int   $importBatchId,  // renamed: hindari konflik dengan Batchable::$batchId
        public readonly array $rows,
    ) {}

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $importBatch = ImportBatch::find($this->importBatchId);
        if (! $importBatch) {
            return;
        }

        if ($importBatch->status === 'queued') {
            $importBatch->update([
                'status'     => 'processing',
                'started_at' => now(),
            ]);
        }

        // Preload drivers map: id_driver string → PK id
        $driversMap = [];
        Driver::all(['id', 'id_driver'])->each(function ($driver) use (&$driversMap) {
            if ($driver->id_driver) {
                $driversMap[strtolower(trim($driver->id_driver))] = $driver->id;
            }
            $driversMap[(string) $driver->id] = $driver->id;
        });

        // Preload existing StdSomeday untuk chunk ini (dedup by awb+date)
        $awbs = collect($this->rows)->pluck('awb')->filter()->unique()->toArray();
        $existingMap = [];
        if (! empty($awbs)) {
            StdSomeday::whereIn('awb', $awbs)
                ->get()
                ->each(function ($record) use (&$existingMap) {
                    $dateKey = $record->date_time->toDateString();
                    $existingMap[$record->awb . '_' . $dateKey] = $record->id;
                });
        }

        $now     = now();
        $inserts = [];
        $updates = [];
        $errors  = [];

        foreach ($this->rows as $index => $row) {
            if (empty($row['awb']) || empty($row['date_time'])) {
                $errors[] = [
                    'row'       => $index + 1,
                    'attribute' => empty($row['awb']) ? 'awb' : 'date_time',
                    'errors'    => ['Field wajib tidak boleh kosong.'],
                ];
                continue;
            }

            $idDriver = $row['id_driver'] ?? null;
            $driverId = null;
            if ($idDriver) {
                $driverId = $driversMap[strtolower(trim($idDriver))] ?? null;
            }

            $dateOnly = substr($row['date_time'], 0, 10);
            $key      = $row['awb'] . '_' . $dateOnly;

            if (isset($existingMap[$key])) {
                $updates[$existingMap[$key]] = [
                    'date_time'  => $row['date_time'],
                    'id_driver'  => $driverId,
                    'status'     => $row['status'] ?? 'LMHub_Received',
                    'updated_at' => $now,
                ];
            } else {
                $inserts[$key] = [
                    'date_time'  => $row['date_time'],
                    'awb'        => $row['awb'],
                    'id_driver'  => $driverId,
                    'status'     => $row['status'] ?? 'LMHub_Received',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::transaction(function () use ($inserts, $updates) {
            if (! empty($inserts)) {
                foreach (array_chunk(array_values($inserts), 500) as $chunk) {
                    DB::table('std_somedays')->insert($chunk);
                }
            }

            foreach ($updates as $id => $data) {
                DB::table('std_somedays')->where('id', $id)->update($data);
            }
        });

        $failedCount = count($errors);
        $importBatch->increment('processed_rows', count($this->rows) - $failedCount);
        $importBatch->increment('failed_rows', $failedCount);

        if (! empty($errors)) {
            $importBatch->refresh();
            $importBatch->appendErrors($errors);
            $importBatch->save();
        }
    }

    public function failed(\Throwable $exception): void
    {
        $importBatch = ImportBatch::find($this->importBatchId);
        if (! $importBatch) {
            return;
        }

        $importBatch->increment('failed_rows', count($this->rows));
        $importBatch->appendErrors([[
            'chunk' => true,
            'errors' => [$exception->getMessage()],
        ]]);
        $importBatch->save();
    }
}
