<?php

namespace App\Jobs;

use App\Models\ImportBatch;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class ProcessProjectionChunk implements ShouldQueue
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

        $now    = now();
        $errors = [];

        DB::transaction(function () use ($now, &$errors) {
            foreach ($this->rows as $index => $row) {
                $dateInbound      = $row['inbound_date'] ?? $row['date_inbound'] ?? null;
                $projectedInbound = isset($row['projected_lm_inbound']) ? (int) $row['projected_lm_inbound'] : null;

                if (! $dateInbound || $projectedInbound === null) {
                    $errors[] = [
                        'row'       => $index + 1,
                        'attribute' => ! $dateInbound ? 'inbound_date' : 'projected_lm_inbound',
                        'errors'    => ['Field wajib tidak boleh kosong.'],
                    ];
                    continue;
                }

                DB::table('projections')->upsert(
                    [
                        'date_inbound'      => $dateInbound,
                        'projected_inbound' => $projectedInbound,
                        'created_at'        => $now,
                        'updated_at'        => $now,
                    ],
                    ['date_inbound'],
                    ['projected_inbound', 'updated_at']
                );
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
