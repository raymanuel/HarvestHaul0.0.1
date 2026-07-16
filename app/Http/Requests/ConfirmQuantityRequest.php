<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmQuantityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'actual_quantity_kg' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
        ];
    }

    public function messages(): array
    {
        return [
            'actual_quantity_kg.required' => 'Please enter the actual loaded quantity.',
            'actual_quantity_kg.min'      => 'Quantity must be at least 0.01 kg.',
        ];
    }
}
