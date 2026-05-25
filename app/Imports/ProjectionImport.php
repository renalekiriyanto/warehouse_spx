<?php

namespace App\Imports;

use App\Models\Projection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class ProjectionImport implements ToModel, WithHeadingRow, WithValidation
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        $projection = Projection::firstOrNew([
            'date_inbound' => $row['inbound_date'],
        ]);
        
        $projection->projected_inbound = $row['projected_lm_inbound'];
        
        return $projection;
    }

    /**
     * @param array $data
     * @param int $index
     * @return array
     */
    public function prepareForValidation($data, $index)
    {
        // Convert Excel serial date to standard Y-m-d format if it's numeric
        if (isset($data['inbound_date']) && is_numeric($data['inbound_date'])) {
            try {
                $data['inbound_date'] = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($data['inbound_date'])->format('Y-m-d');
            } catch (\Exception $e) {
                // Ignore and let Laravel validation fail
            }
        }
        
        return $data;
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'projected_lm_inbound' => ['required', 'integer', 'min:0'],
            'inbound_date'         => ['required', 'date'],
        ];
    }
}
