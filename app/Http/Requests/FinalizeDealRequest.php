<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FinalizeDealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'destination_address'   => ['required', 'string', 'max:500'],
            'destination_latitude'  => ['required', 'numeric', 'between:-90,90'],
            'destination_longitude' => ['required', 'numeric', 'between:-180,180'],
        ];
    }

    public function messages(): array
    {
        return [
            'destination_address.required'   => 'Please provide the drop-off address.',
            'destination_latitude.required'  => 'Please provide the drop-off latitude.',
            'destination_latitude.between'   => 'Latitude must be between -90 and 90.',
            'destination_longitude.required' => 'Please provide the drop-off longitude.',
            'destination_longitude.between'  => 'Longitude must be between -180 and 180.',
        ];
    }
}
