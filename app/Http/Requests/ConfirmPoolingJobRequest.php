<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ConfirmPoolingJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = Auth::user();
        return $user && $user->role === 'logistics_partner' && $user->logisticsProfile;
    }

    public function rules(): array
    {
        return [
            'truck_id'        => ['required', 'integer', 'exists:trucks,id'],
            'harvest_ids'     => ['required', 'array', 'min:1'],
            'harvest_ids.*'   => ['integer', 'exists:harvests,id'],
            'total_kg'        => ['required', 'numeric', 'min:0.01'],
            'start_lat'       => ['required', 'numeric', 'between:-90,90'],
            'start_lng'       => ['required', 'numeric', 'between:-180,180'],
            'end_lat'         => ['required', 'numeric', 'between:-90,90'],
            'end_lng'         => ['required', 'numeric', 'between:-180,180'],
            'radius_km'       => ['required', 'numeric', 'min:1'],
            'notes'           => ['nullable', 'string', 'max:500'],
            'route_geometry'  => ['required', 'array'],
        ];
    }
}
