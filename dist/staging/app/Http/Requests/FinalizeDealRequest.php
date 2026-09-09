<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FinalizeDealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()->role, ['buyer', 'logistics_partner'], true);
    }

    public function rules(): array
    {
        $isCoopLogistics = $this->user()->role === 'logistics_partner'
            && $this->user()->logisticsProfile
            && $this->user()->logisticsProfile->isCooperative();

        return [
            'destination_address'   => ['required', 'string', 'max:500'],
            'destination_latitude'  => ['required', 'numeric', 'between:-90,90'],
            'destination_longitude' => ['required', 'numeric', 'between:-180,180'],
            'hauling_rate_per_kg'   => [$isCoopLogistics ? 'required' : 'nullable', 'numeric', 'min:0', 'max:5000'],
            'popup_save_permanently'=> ['nullable', 'boolean'],
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
            'hauling_rate_per_kg.required'   => 'Please enter the hauling rate (₱/kg) agreed in the chat.',
        ];
    }
}
