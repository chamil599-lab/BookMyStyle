<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreImageCycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'alt' => ['nullable', 'string', 'max:255'],
            'src_url' => ['nullable', 'url'],
            'order' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'in:0,1'],
            'file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,gif,webp', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.max' => 'Title may not be greater than 255 characters.',
            'alt.max' => 'Alt text may not be greater than 255 characters.',
            'src_url.url' => 'Source URL must be a valid URL.',
            'order.integer' => 'Order must be a numeric value.',
            'order.min' => 'Order must be zero or greater.',
            'status.in' => 'Status must be either 0 or 1.',
            'file.file' => 'Uploaded image is invalid.',
            'file.mimes' => 'Image must be one of: jpg, jpeg, png, gif, webp.',
            'file.max' => 'Image size must not exceed 10 MB.',
        ];
    }

    public function attributes(): array
    {
        return [
            'src_url' => 'source URL',
            'file' => 'image file',
        ];
    }
}
