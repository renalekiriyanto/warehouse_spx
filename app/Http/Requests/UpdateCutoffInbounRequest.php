<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCutoffInbounRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $id = $this->route('cutoff_inboun') ? $this->route('cutoff_inboun')->id : null;
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'required', 'string', 'max:255', 'unique:cutoff_inbouns,slug,' . $id],
            'is_active' => ['boolean'],
            'time_start' => ['sometimes', 'required', 'date_format:H:i:s'],
            'time_end' => ['sometimes', 'required', 'date_format:H:i:s'],
        ];
    }
}
