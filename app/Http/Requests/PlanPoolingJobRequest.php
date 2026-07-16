<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class PlanPoolingJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = Auth::user();
        return $user && $user->role === 'logistics_partner' && $user->logisticsProfile;
    }

    public function rules(): array
    {
        return [
            'truck_id'      => ['required', 'integer', 'exists:trucks,id'],
            'harvest_ids'   => ['required', 'array', 'min:1'],
            'harvest_ids.*' => ['integer', 'exists:harvests,id'],
            'start_lat'     => ['required', 'numeric'],
            'start_lng'     => ['required', 'numeric'],
            'end_lat'       => ['required', 'numeric'],
            'end_lng'       => ['required', 'numeric'],
            'radius_km'     => ['required', 'numeric', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'truck_id.required'    => 'Please select a truck.',
            'harvest_ids.required' => 'Please select at least one harvest.',
            'harvest_ids.min'      => 'Please select at least one harvest.',
            'radius_km.min'        => 'Search radius must be at least 1 km.',
        ];
    }
}
