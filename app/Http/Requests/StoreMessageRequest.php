<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'body' => 'required|string|max:4000',
            'context_type' => 'nullable|string|in:'.implode(',', \App\Models\Message::CONTEXTS),
            'context_id'   => 'nullable|integer|required_with:context_type',
        ];
    }
}
