<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateHarvestRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'farmer') {
            return false;
        }

        $farmerProfile = $user->farmerProfile;

        return $farmerProfile && $farmerProfile->is_verified;
    }

    public function rules(): array
    {
        return [
            'crop_id'         => ['required', 'exists:crops,id'],
            'crop_variety_id' => ['required', 'exists:crop_varieties,id'],
            'quantity_kg'     => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'notes'           => ['nullable', 'string', 'max:1000'],
            'harvest_date'    => ['nullable', 'date', 'before_or_equal:today'],
            'quality_grade'   => ['nullable', 'string', 'max:100'],
            'packaging_type'  => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'crop_id.required'         => 'Please select a crop.',
            'crop_variety_id.required' => 'Please select a crop variety.',
            'quantity_kg.max'          => 'Quantity cannot exceed 999,999.99 kg.',
            'quantity_kg.min'          => 'Quantity must be at least 0.01 kg.',
        ];
    }
}
