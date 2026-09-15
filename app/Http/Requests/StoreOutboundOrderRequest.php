<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOutboundOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_card_id'       => ['required', 'integer', 'exists:customer_cards,id'],
            'notes'                  => ['nullable', 'string', 'max:191'],
            'lines'                  => ['required', 'array', 'min:1'],
            'lines.*.crop_type'      => ['required', 'string', 'max:191'],
            'lines.*.quantity_kg'    => ['required', 'numeric', 'min:0.01'],
            'lines.*.rate_per_kg'    => ['required', 'numeric', 'min:0.01'],
        ];
    }
}