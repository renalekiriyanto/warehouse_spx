<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreEstimasiArrivalRequest extends FormRequest
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
            'type_slot_id' => ['required', 'integer', 'exists:type_slots,id'],
            'time_start' => ['required', 'date_format:H:i:s'],
            'time_end' => ['required', 'date_format:H:i:s'],
            'is_active' => ['boolean'],
        ];
    }
}
