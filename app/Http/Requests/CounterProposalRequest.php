<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CounterProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'counter_price' => ['required', 'numeric', 'min:1', 'max:999999'],
        ];
    }

    public function messages(): array
    {
        return [
            'counter_price.required' => 'Please enter your counter-proposal price.',
            'counter_price.min'      => 'Counter-proposal must be at least ₱1.00.',
        ];
    }
}
