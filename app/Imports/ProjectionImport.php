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
        return new Projection([
            'projected_inbound' => $row['projected_inbound'],
            'date_inbound'      => $row['date_inbound'],
        ]);
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'projected_inbound' => ['required', 'integer', 'min:0'],
            'date_inbound'      => ['required', 'date'],
        ];
    }
}
