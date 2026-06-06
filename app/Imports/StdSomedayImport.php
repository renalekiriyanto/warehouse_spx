<?php

namespace App\Imports;

use App\Models\Driver;
use App\Models\StdSomeday;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class StdSomedayImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    /**
     * Tentukan kriteria baris kosong yang harus dilewati
     */
    public function isEmptyWhen(array $row): bool
    {
        return empty(trim($row['awb'] ?? '')) && empty(trim($row['date'] ?? ''));
    }

    /**
     * Kolom Excel yang diharapkan (WithHeadingRow auto-snake_case):
     * | Date | Time | AWB | ID Driver | Driver name | Status |
     *   date   time   awb   id_driver   driver_name   status
     *
     * @param  Collection  $rows
     */
    public function collection(Collection $rows)
    {
        // Nonaktifkan query log untuk menghemat RAM & CPU selama bulk insert
        \DB::connection()->disableQueryLog();

        // 1. Normalisasi semua baris data secara in-memory
        $normalizedRows = [];
        foreach ($rows as $index => $row) {
            $normalizedRows[$index] = $this->prepareRowForValidation($row->toArray(), $index);
        }

        // 2. Jalankan validasi pada seluruh baris sekaligus (jauh lebih cepat dibanding row-by-row Maatwebsite)
        $validator = \Validator::make($normalizedRows, [
            '*.date' => ['required'],
            '*.time' => ['required'],
            '*.date_time' => ['required', 'date'],
            '*.awb' => ['required', 'string', 'max:255'],
            '*.id_driver' => ['nullable'],
            '*.driver_name' => ['nullable', 'string'],
            '*.status' => ['required', 'string', 'in:LMHub_Received,LMHub_Assigned,LMHub_Assigning,Return_LMHub_Packed,Return_LMHub_Received,Delivering,OnHold,Delivered'],
        ]);

        if ($validator->fails()) {
            $failures = [];
            foreach ($validator->errors()->toArray() as $key => $messages) {
                // $key format: "row_index.attribute", e.g., "0.awb"
                $parts = explode('.', $key);
                $index = (int)$parts[0];
                $attribute = $parts[1] ?? '';

                // Baris Excel mulai dari 2 (1-indexed + header row)
                $rowNum = $index + 2; 

                $failures[] = new \Maatwebsite\Excel\Validators\Failure($rowNum, $attribute, $messages, $normalizedRows[$index]);
            }
            throw new \Maatwebsite\Excel\Validators\ValidationException($validator, $failures);
        }

        // 3. Preload drivers ke memory map
        $driversMap = [];
        Driver::all(['id', 'id_driver'])->each(function ($driver) use (&$driversMap) {
            if ($driver->id_driver) {
                $driversMap[strtolower(trim($driver->id_driver))] = $driver->id;
            }
            $driversMap[(string)$driver->id] = $driver->id;
        });

        // 4. Preload existing StdSomeday records untuk AWB yang ada di file
        $awbs = collect($normalizedRows)->pluck('awb')->filter()->unique()->toArray();
        $existingMap = [];
        if (!empty($awbs)) {
            StdSomeday::whereIn('awb', $awbs)->get()->each(function ($record) use (&$existingMap) {
                $dateKey = $record->date_time->toDateString();
                $existingMap[$record->awb . '_' . $dateKey] = $record;
            });
        }

        // 5. Proses baris data dan pilah antara insert dan update
        $now = now();
        $inserts = [];
        $updates = [];

        foreach ($normalizedRows as $row) {
            if (!isset($row['awb']) || !isset($row['date_time'])) {
                continue;
            }

            $idDriver = $row['id_driver'] ?? null;
            $driverId = null;

            if ($idDriver) {
                $lookupKey = strtolower(trim($idDriver));
                $driverId = $driversMap[$lookupKey] ?? null;
            }

            $dateOnly = substr($row['date_time'], 0, 10);
            $key = $row['awb'] . '_' . $dateOnly;

            $existing = $existingMap[$key] ?? null;

            if ($existing) {
                $updates[$existing->id] = [
                    'date_time' => $row['date_time'],
                    'id_driver' => $driverId,
                    'status' => $row['status'] ?? 'LMHub_Received',
                    'updated_at' => $now,
                ];
            } else {
                $inserts[$key] = [
                    'date_time' => $row['date_time'],
                    'awb' => $row['awb'],
                    'id_driver' => $driverId,
                    'status' => $row['status'] ?? 'LMHub_Received',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        // 6. Jalankan write operations dalam satu transaksi database
        \DB::transaction(function () use ($inserts, $updates) {
            // Bulk Insert dalam chunks 500 records
            if (!empty($inserts)) {
                foreach (array_chunk(array_values($inserts), 500) as $chunk) {
                    \DB::table('std_somedays')->insert($chunk);
                }
            }

            // Jalankan update via query builder
            if (!empty($updates)) {
                foreach ($updates as $id => $data) {
                    \DB::table('std_somedays')->where('id', $id)->update($data);
                }
            }
        });
    }

    /**
     * Normalisasi data sebelum validasi:
     * - Gabungkan kolom `date` + `time` menjadi `date_time`
     * - Handle Excel serial number untuk date & time
     * - Normalize empty string & whitespace
     */
    public function prepareRowForValidation(array $data, int $index): array
    {
        // ── 1. Konversi date (Excel serial → Y-m-d, atau parse string M/D/YYYY) ──
        $datePart = null;
        if (isset($data['date'])) {
            if (is_numeric($data['date'])) {
                try {
                    $datePart = ExcelDate::excelToDateTimeObject($data['date'])->format('Y-m-d');
                } catch (\Exception $e) {
                    // biarkan validasi menangani
                }
            } else {
                $ts = strtotime($data['date']);
                if ($ts !== false) {
                    $datePart = date('Y-m-d', $ts);
                } else {
                    $datePart = $data['date'];
                }
            }
        }

        // ── 2. Konversi time (Excel serial → H:i:s, atau parse string "7:00:00 PM") ──
        $timePart = null;
        if (isset($data['time'])) {
            if (is_numeric($data['time'])) {
                try {
                    $timePart = ExcelDate::excelToDateTimeObject($data['time'])->format('H:i:s');
                } catch (\Exception $e) {
                    // biarkan validasi menangani
                }
            } else {
                $timeStr = trim($data['time']);
                
                // Jika jam > 12 tapi ada suffix AM/PM (cth: "14:00:00 AM"), hapus AM/PM nya
                if (preg_match('/^\s*(\d+):/', $timeStr, $matches)) {
                    $hour = (int)$matches[1];
                    if ($hour > 12) {
                        $timeStr = preg_replace('/\s*(AM|PM)/i', '', $timeStr);
                    }
                }

                $ts = strtotime($timeStr);
                if ($ts !== false) {
                    $timePart = date('H:i:s', $ts);
                } else {
                    $timePart = $data['time'];
                }
            }
        }

        // ── 3. Gabungkan date + time → date_time ──
        if ($datePart && $timePart) {
            $data['date_time'] = $datePart . ' ' . $timePart;
        } elseif ($datePart) {
            $data['date_time'] = $datePart . ' 00:00:00';
        } elseif (isset($data['date_time']) && is_numeric($data['date_time'])) {
            // Fallback: jika ada kolom date_time langsung (serial number)
            try {
                $data['date_time'] = ExcelDate::excelToDateTimeObject($data['date_time'])->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                // biarkan validasi menangani
            }
        }

        // ── 4. Normalize id_driver (empty string → null) ──
        if (isset($data['id_driver']) && $data['id_driver'] === '') {
            $data['id_driver'] = null;
        }

        // ── 5. Normalize status (trim whitespace) ──
        if (isset($data['status'])) {
            $data['status'] = trim($data['status']);
        }

        return $data;
    }
}
