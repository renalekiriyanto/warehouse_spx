<?php

namespace App\Jobs;

use App\Models\Agency;
use App\Models\Driver;
use App\Models\ImportBatch;
use App\Models\VehicleType;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;

class ProcessDriverChunk implements ShouldQueue
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

        // Preload vehicle types map: slug → id
        $vehicleTypeMap = VehicleType::all(['id', 'slug'])
            ->mapWithKeys(fn ($vt) => [strtolower($vt->slug) => $vt->id])
            ->toArray();

        $errors = [];

        foreach ($this->rows as $index => $row) {
            // Support both indexed CSV rows (0,1,2...) and associative
            $idDriver     = $row[0] ?? $row['id_driver']     ?? null;
            $name         = $row[1] ?? $row['name']          ?? null;
            $vtSlug       = $row[2] ?? $row['vehicle_type']  ?? null;
            $contractType = $row[3] ?? $row['contract_type'] ?? null;
            $agencyName   = $row[4] ?? $row['agency']        ?? null;
            $status       = strtolower($row[5] ?? $row['status'] ?? 'active');

            if (! $idDriver || ! $name) {
                $errors[] = [
                    'row'       => $index + 1,
                    'attribute' => ! $idDriver ? 'id_driver' : 'name',
                    'errors'    => ['Field wajib tidak boleh kosong.'],
                ];
                continue;
            }

            $vehicleTypeId = $vehicleTypeMap[strtolower(trim($vtSlug ?? ''))] ?? null;

            $agencyId = null;
            if ($agencyName) {
                $agency   = Agency::firstOrCreate(
                    ['name' => $agencyName],
                    ['slug' => Str::slug($agencyName)]
                );
                $agencyId = $agency->id;
            }

            Driver::updateOrCreate(
                ['id_driver' => $idDriver],
                [
                    'name'            => $name,
                    'id_vehicle_type' => $vehicleTypeId,
                    'id_agency'       => $agencyId,
                    'contract_type'   => $contractType,
                    'status'          => $status,
                ]
            );
        }

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
