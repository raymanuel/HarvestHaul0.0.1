<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_receipt' => ['required', 'image', 'max:10240', 'mimes:jpg,jpeg,png,pdf'],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_receipt.required' => 'Please select a receipt file.',
            'payment_receipt.image'    => 'The receipt must be an image.',
            'payment_receipt.max'      => 'Receipt must not exceed 10MB.',
            'payment_receipt.mimes'    => 'Please upload a valid receipt (JPG, PNG, or PDF).',
        ];
    }
}
