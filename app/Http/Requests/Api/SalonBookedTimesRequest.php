<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class SalonBookedTimesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d'],
        ];
    }

    public function messages(): array
    {
        return [
            'date.required' => 'Date is required.',
            'date.date_format' => 'Date must be in YYYY-MM-DD format.',
        ];
    }

    public function attributes(): array
    {
        return [
            'date' => 'booking date',
        ];
    }
}
