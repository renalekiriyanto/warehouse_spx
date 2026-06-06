<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreStdSomedayRequest extends FormRequest
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
            'date_time' => ['required', 'date'],
            'awb' => ['required', 'string', 'max:255'],
            'id_driver' => ['nullable', 'integer', 'exists:drivers,id'],
            'status' => ['required', 'in:LMHub_Received,LMHub_Assigned,LMHub_Assigning,Return_LMHub_Packed,Return_LMHub_Received,Delivering,OnHold,Delivered'],
        ];
    }
}
