<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreHarvestRequest extends FormRequest
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
            'crop_id'               => ['required', 'exists:crops,id'],
            'crop_variety_id'       => ['required', 'exists:crop_varieties,id'],
            'quantity_kg'           => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'notes'                 => ['nullable', 'string', 'max:1000'],
            'harvest_date'          => ['nullable', 'date', 'before_or_equal:today'],
            'quality_grade'         => ['nullable', 'string', 'max:100'],
            'packaging_type'        => ['nullable', 'string', 'max:100'],
            'destination_id'        => ['nullable', 'exists:destinations,id'],
            'destination_address'   => ['required', 'string', 'max:500'],
            'destination_latitude'  => ['required', 'numeric', 'between:-90,90'],
            'destination_longitude' => ['required', 'numeric', 'between:-180,180'],
            'crop_photos'           => ['nullable', 'array', 'max:5'],
            'crop_photos.*'         => ['nullable', 'image', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'crop_id.required'              => 'Please select a crop.',
            'crop_variety_id.required'      => 'Please select a crop variety.',
            'quantity_kg.max'               => 'Quantity cannot exceed 999,999.99 kg.',
            'quantity_kg.min'               => 'Quantity must be at least 0.01 kg.',
            'quantity_kg.numeric'           => 'Quantity must be a valid number.',
            'harvest_date.before_or_equal'  => 'Harvest date cannot be in the future.',
            'destination_address.required'  => 'Please select or pin a delivery destination.',
            'destination_latitude.required' => 'Please select or pin a delivery destination.',
        ];
    }
}
