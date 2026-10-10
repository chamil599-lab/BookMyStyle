<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class PublicSalonsIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'department_id.integer' => 'Department must be a valid numeric identifier.',
            'department_id.exists' => 'The selected department does not exist.',
        ];
    }

    public function attributes(): array
    {
        return [
            'department_id' => 'department',
        ];
    }
}
