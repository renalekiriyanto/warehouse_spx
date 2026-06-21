<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class DamageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d'],
            'arrival' => ['required', 'date_format:Y-m-d H:i:s'],
            'departed' => ['required', 'date_format:Y-m-d H:i:s'],
            'awb' => ['required', 'string', 'size:17'],
            'lt_number' => ['required', 'string', 'size:13'],
            'to_number' => ['required', 'string', 'size:15'],
            'driver_lh' => ['required', 'string'],
            'vehicle_number' => ['required', 'string', 'max:8'],
            'sku_name' => ['required', 'string']
        ];
    }
}
