<?php

namespace App\Services;

use App\Jobs\ProcessDriverChunk;
use App\Jobs\ProcessInboundChunk;
use App\Jobs\ProcessProjectionChunk;
use App\Jobs\ProcessStdSomedayChunk;
use App\Models\ImportBatch;
use Illuminate\Bus\Batch;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ImportDispatcherService
{
    /**
     * Ukuran chunk baris per job.
     */
    private const CHUNK_SIZE = 1000;

    /**
     * Format CSV yang didukung untuk std_someday.
     */
    private const FORMAT_LEGACY = 'legacy';
    private const FORMAT_SHOPEE = 'shopee';
    private const TIMEZONE = 'Asia/Jakarta';

    /**
     * Entry point: simpan file, baca baris, buat ImportBatch, dispatch jobs.
     *
     * @param  UploadedFile  $file
     * @param  string        $type   inbound | projection | std_someday | driver
     * @return ImportBatch
     */
    public function dispatch(UploadedFile $file, string $type): ImportBatch
    {
        // 1. Simpan file ke storage/app/imports/<uuid>.<ext>
        $uuid       = Str::uuid()->toString();
        $ext        = strtolower($file->getClientOriginalExtension());
        $storedPath = "imports/{$uuid}.{$ext}";
        Storage::put($storedPath, file_get_contents($file->getRealPath()));

        // 2. Buat record ImportBatch dengan status 'uploading'
        $importBatch = ImportBatch::create([
            'uuid'              => $uuid,
            'type'              => $type,
            'original_filename' => $file->getClientOriginalName(),
            'stored_path'       => $storedPath,
            'status'            => 'uploading',
        ]);

        // 3. Ambil timestamp server saat upload — digunakan sebagai date_time
        //    untuk seluruh record dalam batch Shopee Direct CSV.
        //    Semua row dalam satu file akan memiliki date_time yang sama.
        $uploadTimestamp = now()->format('Y-m-d H:i:s');

        // 4. Baca semua baris dari file
        $allRows = match ($ext) {
            'csv'         => $this->readCsv($file->getRealPath(), $type, $uploadTimestamp),
            'xlsx', 'xls' => $this->readExcel(Storage::path($storedPath), $type, $uploadTimestamp),
            default       => [],
        };

        $totalRows = count($allRows);
        $importBatch->update([
            'total_rows' => $totalRows,
            'status'     => 'queued',
        ]);

        if ($totalRows === 0) {
            $importBatch->update([
                'status'      => 'completed',
                'finished_at' => now(),
            ]);
            return $importBatch;
        }

        // 5. Buat jobs untuk setiap chunk
        $chunks = array_chunk($allRows, self::CHUNK_SIZE);
        $jobs   = [];

        foreach ($chunks as $chunk) {
            $jobs[] = $this->makeJob($type, $importBatch->id, $chunk);
        }

        // 6. Dispatch sebagai Laravel Bus Batch
        $batch = Bus::batch($jobs)
            ->name("import:{$type}:{$uuid}")
            ->allowFailures()
            ->finally(function (Batch $batch) use ($importBatch) {
                $importBatch->refresh();

                $status = $batch->hasFailures()
                    ? ($importBatch->processed_rows > 0 ? 'completed' : 'failed')
                    : 'completed';

                $importBatch->update([
                    'status'      => $status,
                    'finished_at' => now(),
                ]);

                // Bersihkan file sementara
                Storage::delete($importBatch->stored_path);
            })
            ->dispatch();

        $importBatch->update(['job_batch_id' => $batch->id]);

        return $importBatch;
    }

    // ── File readers ────────────────────────────────────────────────────────

    private function readCsv(string $path, string $type, ?string $uploadTimestamp = null): array
    {
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (empty($lines)) {
            return [];
        }

        // ✅ Strip UTF-8 BOM jika ada (penyebab header tidak terbaca)
        $lines[0] = preg_replace('/^\xEF\xBB\xBF/', '', $lines[0]);

        $rawHeader = str_getcsv(array_shift($lines));
        $format    = $type === 'std_someday'
            ? $this->detectStdSomedayFormat($rawHeader)
            : self::FORMAT_LEGACY;

        $header = array_map(fn($h) => $this->toSnakeCase(trim($h)), $rawHeader);

        $rows = [];
        foreach ($lines as $line) {
            $values = str_getcsv($line);
            $row    = array_combine($header, array_pad($values, count($header), null));
            $rows[] = $this->normalizeRow($row, $type, $format, $uploadTimestamp);
        }

        return $rows;
    }

    private function readExcel(string $path, string $type, ?string $uploadTimestamp = null): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet       = $spreadsheet->getActiveSheet();
        // formatData=false → kembalikan raw value (serial number float untuk tanggal)
        // agar ExcelDate::excelToDateTimeObject() bisa mengkonversi dengan benar.
        // formatData=true akan menghasilkan string locale (misal "2026年6月2日") yang tidak bisa diparse.
        $sheetData   = $sheet->toArray(null, true, false, false);

        if (empty($sheetData)) {
            return [];
        }

        // Baris pertama sebagai header
        $rawHeader = array_shift($sheetData);
        $format    = $type === 'std_someday'
            ? $this->detectStdSomedayFormat($rawHeader)
            : self::FORMAT_LEGACY;

        $header = array_map(fn($h) => $this->toSnakeCase((string) ($h ?? '')), $rawHeader);

        $rows = [];
        foreach ($sheetData as $rowValues) {
            if ($this->isEmptyRow($rowValues)) {
                continue;
            }
            $row    = array_combine($header, array_pad($rowValues, count($header), null));
            $rows[] = $this->normalizeRow($row, $type, $format, $uploadTimestamp);
        }

        return $rows;
    }

    // ── Format detection ────────────────────────────────────────────────────

    /**
     * Deteksi format CSV std_someday berdasarkan header.
     *
     * Shopee Direct CSV  → mengandung kolom "Order ID" atau "order_id"
     * Legacy STD CSV     → mengandung kolom "AWB" atau "awb"
     *
     * Jika tidak ada penanda yang dikenali, fallback ke legacy.
     */
    private function detectStdSomedayFormat(array $rawHeader): string
    {
        $normalized = array_map(fn($h) => strtolower(trim((string) $h)), $rawHeader);

        foreach ($normalized as $col) {
            if (in_array($col, ['order id', 'order_id'], true)) {
                return self::FORMAT_SHOPEE;
            }
        }

        foreach ($normalized as $col) {
            if ($col === 'awb') {
                return self::FORMAT_LEGACY;
            }
        }

        // Fallback: tidak ada penanda yang dikenali → anggap legacy
        return self::FORMAT_LEGACY;
    }

    // ── Row normalizers ─────────────────────────────────────────────────────

    /**
     * Normalisasi tipe-spesifik untuk setiap row.
     */
    private function normalizeRow(
        array $row,
        string $type,
        string $format = self::FORMAT_LEGACY,
        ?string $uploadTimestamp = null
    ): array {
        return match ($type) {
            'inbound'     => $this->normalizeInboundRow($row),
            'projection'  => $this->normalizeProjectionRow($row),
            'std_someday' => $format === self::FORMAT_SHOPEE
                ? $this->normalizeShopeeRow($row, $uploadTimestamp)
                : $this->normalizeStdSomedayRow($row),
            'driver'      => $row,   // Driver CSV sudah indexed, tetap dikembalikan apa adanya
            default       => $row,
        };
    }

    private function normalizeInboundRow(array $row): array
    {
        foreach (['date_inbound', 'inbound_date'] as $field) {
            if (isset($row[$field]) && is_numeric($row[$field])) {
                try {
                    $row[$field] = ExcelDate::excelToDateTimeObject($row[$field])->format('Y-m-d');
                } catch (\Exception) {
                }
            }
        }

        if (! isset($row['date_inbound']) && isset($row['inbound_date'])) {
            $row['date_inbound'] = $row['inbound_date'];
        }

        if (isset($row['actual_arrival']) && is_numeric($row['actual_arrival'])) {
            try {
                $row['actual_arrival'] = ExcelDate::excelToDateTimeObject($row['actual_arrival'])->format('H:i:s');
            } catch (\Exception) {
            }
        }

        if (isset($row['actual_arrival']) && $row['actual_arrival'] === '') {
            $row['actual_arrival'] = null;
        }

        return $row;
    }

    private function normalizeProjectionRow(array $row): array
    {
        foreach (['inbound_date', 'date_inbound'] as $field) {
            if (isset($row[$field]) && is_numeric($row[$field])) {
                try {
                    $row[$field] = ExcelDate::excelToDateTimeObject($row[$field])->format('Y-m-d');
                } catch (\Exception) {
                }
            }
        }

        return $row;
    }

    /**
     * MODE 1 — Legacy STD CSV (tidak diubah sama sekali).
     *
     * Format: Date | Time | AWB | ID Driver | Driver Name | Status
     */
    private function normalizeStdSomedayRow(array $row): array
    {
        // Konversi date
        $datePart = null;
        if (isset($row['date'])) {
            if (is_numeric($row['date'])) {
                try {
                    $datePart = ExcelDate::excelToDateTimeObject($row['date'])->format('Y-m-d');
                } catch (\Exception) {
                }
            } else {
                $ts = strtotime($row['date']);
                $datePart = $ts !== false ? date('Y-m-d', $ts) : $row['date'];
            }
        }

        // Konversi time
        $timePart = null;
        if (isset($row['time'])) {
            if (is_numeric($row['time'])) {
                try {
                    $timePart = ExcelDate::excelToDateTimeObject($row['time'])->format('H:i:s');
                } catch (\Exception) {
                }
            } else {
                $timeStr = trim((string) $row['time']);
                if (preg_match('/^\s*(\d+):/', $timeStr, $m) && (int) $m[1] > 12) {
                    $timeStr = preg_replace('/\s*(AM|PM)/i', '', $timeStr);
                }
                $ts = strtotime($timeStr);
                $timePart = $ts !== false ? date('H:i:s', $ts) : $row['time'];
            }
        }

        if ($datePart && $timePart) {
            $row['date_time'] = $datePart . ' ' . $timePart;
        } elseif ($datePart) {
            $row['date_time'] = $datePart . ' 00:00:00';
        } elseif (isset($row['date_time']) && is_numeric($row['date_time'])) {
            try {
                $row['date_time'] = ExcelDate::excelToDateTimeObject($row['date_time'])->format('Y-m-d H:i:s');
            } catch (\Exception) {
            }
        }

        if (isset($row['id_driver']) && $row['id_driver'] === '') {
            $row['id_driver'] = null;
        }

        if (isset($row['status'])) {
            $row['status'] = trim((string) $row['status']);
        }

        return $row;
    }

    /**
     * MODE 2 — Shopee Direct CSV.
     *
     * Mapping kolom Shopee → kolom StdSomeday:
     *   order_id  → awb
     *   driver_id → id_driver
     *   status    → status
     *   date_time → $uploadTimestamp (timestamp server saat upload, BUKAN dari Shopee)
     *
     * Kolom Shopee lain (received_time, delivering_time, delivered_time,
     * on_hold_time, dll) sengaja diabaikan karena STD/Someday adalah
     * snapshot kondisi courier saat upload, bukan histori status paket.
     */
    private function normalizeShopeeRow(array $row, ?string $uploadTimestamp): array
{
    // ✅ DEBUG LOG — hapus setelah confirmed working
    \Illuminate\Support\Facades\Log::info('SHOPEE ROW MAPPING', [
        'raw_keys'   => array_keys($row),
        'order_id'   => $row['order_id'] ?? 'KEY_NOT_FOUND',
        'driver_id'  => $row['driver_id'] ?? 'KEY_NOT_FOUND',
        'status'     => $row['status'] ?? 'KEY_NOT_FOUND',
        'timestamp'  => $uploadTimestamp,
    ]);

    $awb = isset($row['order_id']) ? trim((string) $row['order_id']) : null;
    if ($awb === '') {
        $awb = null;
    }

        $idDriver = isset($row['driver_id']) ? trim((string) $row['driver_id']) : null;
        if ($idDriver === '') {
            $idDriver = null;
        }

        $status = isset($row['status']) ? trim((string) $row['status']) : 'LMHub_Received';
        if ($status === '') {
            $status = 'LMHub_Received';
        }

        return [
            'awb'       => $awb,
            'id_driver' => $idDriver,
            'status'    => $status,
            // Seluruh row dalam satu batch menggunakan timestamp yang sama
            // yaitu waktu server saat file diupload.
            'date_time' => now(self::TIMEZONE)->format('Y-m-d H:i:s')
        ];
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    private function makeJob(string $type, int $importBatchId, array $chunk): ShouldQueue
    {
        return match ($type) {
            'inbound'     => new ProcessInboundChunk($importBatchId, $chunk),
            'projection'  => new ProcessProjectionChunk($importBatchId, $chunk),
            'std_someday' => new ProcessStdSomedayChunk($importBatchId, $chunk),
            'driver'      => new ProcessDriverChunk($importBatchId, $chunk),
            default       => throw new \InvalidArgumentException("Unknown import type: {$type}"),
        };
    }

    private function isEmptyRow(array $row): bool
    {
        return collect($row)->every(fn($v) => $v === null || trim((string) $v) === '');
    }

    private function toSnakeCase(string $str): string
    {
        // Ubah header Excel (e.g. "Inbound Date", "inbound_date", "InboundDate") ke snake_case
        $str = preg_replace('/\s+/', '_', strtolower(trim($str)));
        $str = preg_replace('/[^a-z0-9_]/', '', $str);
        return $str;
    }
}
