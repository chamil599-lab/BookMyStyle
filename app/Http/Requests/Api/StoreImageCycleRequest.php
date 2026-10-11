<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreImageCycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return \App\Support\ManagementAccess::admin($this->user()?->fresh());
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'alt' => ['nullable', 'string', 'max:255'],
            'src_url' => ['nullable', 'url:http,https', 'max:2048'],
            'order' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'in:0,1'],
            'file' => ['nullable', 'image', 'dimensions:max_width=6000,max_height=6000', 'mimes:jpg,jpeg,png,gif,webp', 'max:10240'],
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
