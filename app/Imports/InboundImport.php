<?php

namespace App\Imports;

use App\Models\Inbound;
use App\Models\TypeSlot;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class InboundImport implements ToModel, WithHeadingRow, WithValidation
{
    /**
     * @param  array<string, mixed>  $row
     */
    public function model(array $row): ?Inbound
    {
        // Cari TypeSlot berdasarkan nama yang contain dengan value type_slot dari file
        $typeSlotName = $row['type_slot'] ?? null;
        $typeSlot = null;

        if ($typeSlotName) {
            $typeSlot = TypeSlot::where('name', 'like', '%' . $typeSlotName . '%')->first();
        }

        // Jika type_slot tidak ditemukan, skip baris ini (return null)
        if (! $typeSlot) {
            return null;
        }

        return new Inbound([
            'id_type_slot' => $typeSlot->id,
            'date_inbound' => $row['date_inbound'] ?? $row['inbound_date'] ?? now()->toDateString(),
            'actual_arrival' => $row['actual_arrival'] ?? null,
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
            'type_slot' => ['required', 'string'],
            'date_inbound' => ['nullable', 'date'],
            'inbound_date' => ['nullable', 'date'],
            'actual_arrival' => ['nullable', 'date_format:H:i:s'],
            'total_order' => ['required', 'integer', 'min:0'],
        ];
    }
}
