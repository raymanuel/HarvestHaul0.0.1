<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProposeTermsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'negotiated_price'  => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'negotiated_volume' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
        ];
    }

    public function messages(): array
    {
        return [
            'negotiated_price.required'  => 'Please enter a proposed price per kg.',
            'negotiated_price.min'       => 'Price must be at least ₱0.01.',
            'negotiated_volume.required' => 'Please enter the volume in kg.',
            'negotiated_volume.min'      => 'Volume must be at least 0.01 kg.',
        ];
    }
}
