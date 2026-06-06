<?php

namespace App\Http\Controllers;

use App\Models\ImportBatch;
use Illuminate\Http\JsonResponse;

class ImportBatchController extends Controller
{
    /**
     * Ambil status & progress dari sebuah import batch.
     *
     * GET /api/import-batches/{uuid}
     *
     * Response example:
     * {
     *   "success": true,
     *   "data": {
     *     "uuid": "...",
     *     "type": "inbound",
     *     "status": "processing",
     *     "status_label": "Sedang diproses...",
     *     "progress": 42,
     *     "total_rows": 10000,
     *     "processed_rows": 4200,
     *     "failed_rows": 3,
     *     "errors": [...],
     *     "started_at": "...",
     *     "finished_at": null
     *   }
     * }
     */
    public function show(string $uuid): JsonResponse
    {
        $batch = ImportBatch::where('uuid', $uuid)->firstOrFail();

        return $this->successResponse('Berhasil mengambil status import.', $this->format($batch));
    }

    /**
     * Daftar semua import batch (terbaru dahulu, max 50).
     *
     * GET /api/import-batches
     */
    public function index(): JsonResponse
    {
        $batches = ImportBatch::latest()->limit(50)->get()->map(fn ($b) => $this->format($b));

        return $this->successResponse('Berhasil mengambil daftar import batch.', $batches);
    }

    // ── Private helpers ────────────────────────────────────────────────────

    private function format(ImportBatch $batch): array
    {
        return [
            'uuid'              => $batch->uuid,
            'type'              => $batch->type,
            'original_filename' => $batch->original_filename,
            'status'            => $batch->status,
            'status_label'      => $batch->status_label,
            'progress'          => $batch->progress,
            'total_rows'        => $batch->total_rows,
            'processed_rows'    => $batch->processed_rows,
            'failed_rows'       => $batch->failed_rows,
            'errors'            => $batch->errors ?? [],
            'started_at'        => $batch->started_at?->toIso8601String(),
            'finished_at'       => $batch->finished_at?->toIso8601String(),
            'created_at'        => $batch->created_at->toIso8601String(),
        ];
    }
}
