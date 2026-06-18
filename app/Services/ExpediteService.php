<?php

namespace App\Services;

use App\Models\Expedite;
use App\Traits\ApiResponse;
use Carbon\Carbon;

class ExpediteService
{
    use ApiResponse;
    private const TIMEZONE = 'Asia/Jakarta';
    public function fetchExpediteData($filters = [])
    {
        // Logic to fetch expedite data from the database
        $query = Expedite::with('driver');

        if (isset($filters['start_date'])) {
            $query->where('date_time', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('date_time', '<=', $filters['end_date']);
        }

        $data = $query->get();
        return $data;
    }

    public function storeExpediteData($data)
    {
        // Logic to store expedite data in the database
        $expedite = Expedite::create($data);
        return $this->successResponse('Data expedite berhasil disimpan', $expedite, 200);
    }

    public function deleteExpediteData($id)
    {
        // Logic to delete expedite data from the database
        $expedite = Expedite::find($id);
        if (!$expedite) {
            return $this->errorResponse('Data expedite tidak ditemukan', 404);
        }
        $expedite->delete();
        return $this->successResponse('Data expedite berhasil dihapus', null, 200);
    }

    public function uploadExpediteData($request)
    {
        // 1. Validasi file
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120', // Maksimal 5MB
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');

        // 2. Baca baris pertama (header).
        $header = fgetcsv($handle, 1000, ',');

        $dataToInsert = [];

        // 3. Loop while untuk membaca data dan langsung susun array insert (lebih hemat memory)
        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            // Skip baris kosong
            if ($row === [null] || empty(array_filter($row))) {
                continue;
            }

            // Logic pembulatan waktu sesuai requirement:
            // 22:30 -> 22:00, 22:45 -> 23:00
            // time to indonesian
            // 1. Biarkan tetap sebagai objek Carbon (jangan langsung diformat ke string)
            $roundedTime = now(self::TIMEZONE)->copy();

            // 2. Lakukan logika pembulatan
            if ($roundedTime->minute > 30) {
                $roundedTime->addHour();
            }

            // 3. Reset menit dan detik menjadi 0 agar waktu benar-benar bulat (opsional tapi disarankan)
            $roundedTime->startOfHour();

            // 4. Baru format menjadi string di akhir setelah semua proses selesai
            $roundedTime = $roundedTime->format('Y-m-d H:i:s');

            $dataToInsert[] = [
                'date_time'       => $roundedTime,
                'awb'             => $row[0] ?? null,
                'id_driver'       => $row[8] ?? null,
                'status'          => $row[17] ?? null,
                'current_station' => $row[29] ?? null,
                'created_at'      => now(),
                'updated_at'      => now(),
            ];
        }

        fclose($handle); // Tutup file pointer untuk mencegah memory leak

        // 4. Simpan ke database
        if (!empty($dataToInsert)) {
            // Menggunakan storeExpediteData (sesuai comment di kode asli)
            foreach ($dataToInsert as $item) {
                $this->storeExpediteData($item);
            }

            /*
             * CATATAN PENTING:
             * Jika file CSV memiliki ribuan baris, loop di atas akan mengeksekusi
             * query INSERT satu per satu yang bisa membuat proses sangat lambat.
             * Sangat disarankan menggunakan Bulk Insert seperti di bawah ini:
             *
             * Expedite::insert($dataToInsert);
             */
        }

        return $this->successResponse('Data expedite berhasil diupload', [
            'total_data' => count($dataToInsert)
        ], 200);
    }
}
