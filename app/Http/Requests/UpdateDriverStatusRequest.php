<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDriverStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'driver';
    }

    public function rules(): array
    {
        return [
            'end_odometer_reading' => ['required_with:end_odometer_reading', 'numeric', 'min:0.01', 'max:9999999.99'],
        ];
    }
}
