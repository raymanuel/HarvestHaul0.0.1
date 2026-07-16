<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFuelLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fuel_liters'      => ['required', 'numeric', 'min:0.01', 'max:9999.99'],
            'cost'             => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'odometer_reading' => ['required', 'numeric', 'min:0.01', 'max:9999999.99'],
        ];
    }

    public function messages(): array
    {
        return [
            'fuel_liters.required'      => 'Please enter the fuel liters purchased.',
            'fuel_liters.min'           => 'Fuel liters must be at least 0.01.',
            'cost.required'             => 'Please enter the fuel cost.',
            'odometer_reading.required' => 'Please enter the current odometer reading.',
        ];
    }
}
