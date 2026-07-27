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
        $hasCustomCrop = $this->input('crop_id') === 'other';
        $hasCustomVariety = $this->input('crop_variety_id') === 'other';

        return [
            'crop_id'               => $hasCustomCrop
                ? ['required', 'string']
                : ['required_without:custom_crop_name', 'exists:crops,id'],
            'custom_crop_name'      => $hasCustomCrop
                ? ['required', 'string', 'max:255']
                : ['nullable', 'string', 'max:255'],
            'crop_variety_id'       => $hasCustomVariety
                ? ['required', 'string']
                : ['required_without:custom_variety_name', 'exists:crop_varieties,id'],
            'custom_variety_name'   => $hasCustomVariety
                ? ['required', 'string', 'max:255']
                : ['nullable', 'string', 'max:255'],
            'quantity_kg'           => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'suggested_price_per_kg'=> ['nullable', 'numeric', 'min:0.01', 'max:99999.99'],
            'notes'                 => ['nullable', 'string', 'max:1000'],
            'harvest_date'          => ['nullable', 'date', 'after_or_equal:today', 'before_or_equal:tomorrow'],
        ];
    }

    public function messages(): array
    {
        return [
            'crop_id.required'              => 'Please select a crop.',
            'crop_id.required_without'      => 'Please enter a crop name.',
            'crop_variety_id.required'      => 'Please select a crop variety.',
            'crop_variety_id.required_without' => 'Please enter a variety name.',
            'quantity_kg.max'               => 'Quantity cannot exceed 999,999.99 kg.',
            'quantity_kg.min'               => 'Quantity must be at least 0.01 kg.',
            'suggested_price_per_kg.max'    => 'Suggested price cannot exceed 99,999.99.',
            'suggested_price_per_kg.min'    => 'Suggested price must be at least ₱0.01.',
        ];
    }
}
