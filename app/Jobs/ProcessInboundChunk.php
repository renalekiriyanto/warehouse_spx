<?php

namespace App\Jobs;

use App\Models\ImportBatch;
use App\Models\TypeSlot;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class ProcessInboundChunk implements ShouldQueue
{
    use Queueable, Batchable;

    public int $tries   = 3;
    public int $timeout = 120;

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

        // Preload semua TypeSlot ke memory map
        $typeSlotMap = TypeSlot::all()->mapWithKeys(
            fn ($slot) => [strtolower(trim($slot->name)) => $slot->id]
        )->toArray();

        $inserts     = [];
        $failedCount = 0;
        $errors      = [];
        $now         = now();

        foreach ($this->rows as $index => $row) {
            $typeSlotName = strtolower(trim($row['type_slot'] ?? ''));

            // Partial match di memory (replika behaviour LIKE)
            $typeSlotId = null;
            foreach ($typeSlotMap as $name => $id) {
                if (str_contains($name, $typeSlotName) || str_contains($typeSlotName, $name)) {
                    $typeSlotId = $id;
                    break;
                }
            }

            if (! $typeSlotId) {
                $failedCount++;
                $errors[] = [
                    'row'       => $index + 1,
                    'attribute' => 'type_slot',
                    'errors'    => ["type_slot '{$row['type_slot']}' tidak ditemukan."],
                ];
                continue;
            }

            $inserts[] = [
                'id_type_slot'   => $typeSlotId,
                'date_inbound'   => $row['date_inbound']   ?? now()->toDateString(),
                'actual_arrival' => $row['actual_arrival'] ?? null,
                'total_order'    => (int) ($row['total_order'] ?? 0),
                'created_at'     => $now,
                'updated_at'     => $now,
            ];
        }

        DB::transaction(function () use ($inserts) {
            if (! empty($inserts)) {
                DB::table('inbounds')->insert($inserts);
            }
        });

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
