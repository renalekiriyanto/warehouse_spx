<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreExpediteRequest extends FormRequest
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
            'awb' => 'required|string',
            'id_driver' => 'required|exists:drivers,id',
            'status' => 'nullable|string',
            'current_station' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'awb.required' => 'AWB wajib diisi.',
            'awb.string' => 'AWB harus berupa string.',
            'id_driver.required' => 'ID driver wajib diisi.',
            'id_driver.exists' => 'ID driver tidak valid.',
            'status.string' => 'Status harus berupa string.',
            'current_station.string' => 'Current station harus berupa string.',
        ];
    }
}
