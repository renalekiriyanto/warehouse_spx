<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InboundDailyAnalysisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date.required' => 'Parameter tanggal (date) wajib diisi.',
            'date.date' => 'Format tanggal tidak valid. Gunakan YYYY-MM-DD.',
        ];
    }
}
