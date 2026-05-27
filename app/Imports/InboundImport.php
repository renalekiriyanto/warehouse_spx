<?php

namespace App\Imports;

use App\Models\Inbound;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class InboundImport implements ToModel, WithHeadingRow, WithValidation
{
    /**
     * @param  array<string, mixed>  $row
     */
    public function model(array $row): Inbound
    {
        return new Inbound([
            'date_inbound' => $row['date_inbound'] ?? $row['inbound_date'] ?? now()->toDateString(),
            'actual_arrival' => $row['actual_arrival'] ?? null,
            'bulky' => $row['bulky'],
            'total_order' => $row['total_order'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function prepareForValidation($data, $index): array
    {
        if (isset($data['actual_arrival']) && is_numeric($data['actual_arrival'])) {
            try {
                $data['actual_arrival'] = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($data['actual_arrival'])
                    ->format('H:i:s');
            } catch (\Exception $e) {
                // Biarkan validasi Laravel menangani format tidak valid.
            }
        }

        if (isset($data['actual_arrival']) && $data['actual_arrival'] === '') {
            $data['actual_arrival'] = null;
        }

        foreach (['date_inbound', 'inbound_date'] as $dateField) {
            if (isset($data[$dateField]) && is_numeric($data[$dateField])) {
                try {
                    $data[$dateField] = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($data[$dateField])
                        ->format('Y-m-d');
                } catch (\Exception $e) {
                    // Biarkan validasi Laravel menangani format tidak valid.
                }
            }
        }

        if (! isset($data['date_inbound']) && isset($data['inbound_date'])) {
            $data['date_inbound'] = $data['inbound_date'];
        }

        return $data;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'date_inbound' => ['nullable', 'date'],
            'inbound_date' => ['nullable', 'date'],
            'actual_arrival' => ['nullable', 'date_format:H:i:s'],
            'bulky' => ['required', 'integer', 'min:0'],
            'total_order' => ['required', 'integer', 'min:0'],
        ];
    }
}
