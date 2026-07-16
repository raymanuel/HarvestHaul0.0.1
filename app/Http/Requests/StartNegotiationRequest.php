<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartNegotiationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'harvest_id' => ['required', 'exists:harvests,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'harvest_id.required' => 'Please select a product to negotiate.',
            'harvest_id.exists'   => 'The selected product does not exist.',
        ];
    }
}
