<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LogisticsCounterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'negotiated_price' => ['required', 'numeric', 'min:1', 'max:999999'],
        ];
    }

    public function messages(): array
    {
        return [
            'negotiated_price.required' => 'Please enter the bid price.',
            'negotiated_price.min'      => 'Bid price must be at least ₱1.00.',
        ];
    }
}
