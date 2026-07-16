<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStopStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status'                       => ['required', 'in:arrived,loaded,delivered'],
            'loaded_quantity_kg'           => ['required_if:status,loaded', 'nullable', 'numeric', 'min:0.01', 'max:999999.99'],
            'loaded_volume_cubic_meters'   => ['required_if:status,loaded', 'nullable', 'numeric', 'min:0.01', 'max:999999.99'],
            'load_photo'                   => ['required_if:status,loaded', 'nullable', 'image', 'max:10240'],
            'crop_confirmed'               => ['required_if:status,loaded', 'nullable', 'boolean'],
            'delivery_receipt'             => ['required_if:status,delivered', 'nullable', 'image', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required'                => 'Please select a status.',
            'status.in'                      => 'Invalid status value.',
            'loaded_quantity_kg.required_if' => 'Loaded quantity is required when marking as loaded.',
            'loaded_quantity_kg.min'         => 'Loaded quantity must be at least 0.01 kg.',
            'crop_confirmed.required_if'     => 'You must confirm the crop matches the listing.',
            'delivery_receipt.required_if'   => 'A delivery receipt photo is required to mark as delivered.',
        ];
    }
}
