<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'salon_id' => ['required', 'integer', 'exists:salons,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc,dns'],
            'phone' => ['required', 'string', 'max:20'],
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'time' => ['required', 'date_format:H:i'],
        ];
    }

    public function messages(): array
    {
        return [
            'salon_id.required' => 'Salon is required.',
            'salon_id.integer' => 'Salon must be a valid numeric identifier.',
            'salon_id.exists' => 'The selected salon does not exist.',
            'name.required' => 'Name is required.',
            'name.max' => 'Name may not be greater than 255 characters.',
            'email.required' => 'Email is required.',
            'email.email' => 'Email must be a valid email address.',
            'phone.required' => 'Phone number is required.',
            'phone.max' => 'Phone number may not be greater than 20 characters.',
            'service_id.required' => 'Service is required.',
            'service_id.integer' => 'Service must be a valid numeric identifier.',
            'service_id.exists' => 'The selected service does not exist.',
            'date.required' => 'Booking date is required.',
            'date.date_format' => 'Booking date must be in YYYY-MM-DD format.',
            'date.after_or_equal' => 'Booking date cannot be in the past.',
            'time.required' => 'Booking time is required.',
            'time.date_format' => 'Booking time must be in HH:MM format.',
        ];
    }

    public function attributes(): array
    {
        return [
            'salon_id' => 'salon',
            'service_id' => 'service',
            'date' => 'booking date',
            'time' => 'booking time',
        ];
    }
}
