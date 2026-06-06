<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ImportBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'type',
        'original_filename',
        'stored_path',
        'status',
        'total_rows',
        'processed_rows',
        'failed_rows',
        'errors',
        'job_batch_id',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'errors'       => 'array',
        'started_at'   => 'datetime',
        'finished_at'  => 'datetime',
    ];

    // ── Computed helpers ───────────────────────────────────────────

    /**
     * Progress 0–100 (percent).
     */
    public function getProgressAttribute(): int
    {
        if ($this->total_rows === 0) {
            return $this->status === 'completed' ? 100 : 0;
        }

        return (int) min(100, round(($this->processed_rows / $this->total_rows) * 100));
    }

    /**
     * Human-readable status label (Bahasa Indonesia).
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'uploading'  => 'Mengunggah file...',
            'queued'     => 'Menunggu antrian...',
            'processing' => 'Sedang diproses...',
            'completed'  => 'Selesai',
            'failed'     => 'Gagal',
            default      => $this->status,
        };
    }

    /**
     * Tambah error ke array (auto-truncate ke 200 entri agar kolom tidak membengkak).
     */
    public function appendErrors(array $newErrors): void
    {
        $existing = $this->errors ?? [];
        $merged   = array_merge($existing, $newErrors);

        // Batasi 200 entri untuk mencegah kolom JSON membesar tak terbatas
        if (count($merged) > 200) {
            $merged = array_slice($merged, -200);
        }

        $this->errors = $merged;
    }
}
