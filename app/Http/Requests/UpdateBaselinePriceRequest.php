<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBaselinePriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'baseline_price_per_kg' => ['required', 'numeric', 'min:0.01', 'max:99999.99'],
        ];
    }

    public function messages(): array
    {
        return [
            'baseline_price_per_kg.required' => 'Please enter the new baseline price.',
            'baseline_price_per_kg.min'      => 'Price must be at least ₱0.01.',
        ];
    }
}
