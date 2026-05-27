<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateInboundRequest extends FormRequest
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
            'actual_arrival' => ['sometimes', 'nullable', 'date_format:H:i:s'],
            'bulky' => ['sometimes', 'required', 'integer', 'min:0'],
            'total_order' => ['sometimes', 'required', 'integer', 'min:0'],
        ];
    }
}
