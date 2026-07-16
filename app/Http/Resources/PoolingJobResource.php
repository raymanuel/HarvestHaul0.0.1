<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PoolingJobResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'status'           => $this->status->value,
            'status_label'     => $this->status->label(),
            'total_kg'         => $this->total_kg,
            'farm_count'       => $this->farm_count,
            'price_reference'  => $this->price_reference,
            'negotiated_price' => $this->negotiated_price,
            'created_at'       => $this->created_at?->toIso8601String(),
            'confirmed_at'     => $this->confirmed_at?->toIso8601String(),
            'completed_at'     => $this->completed_at?->toIso8601String(),
            'truck'            => [
                'id'            => $this->truck?->id,
                'plate_number'  => $this->truck?->plate_number,
                'truck_name'    => $this->truck?->truck_name,
            ],
            'harvests_count'   => $this->harvests?->count() ?? 0,
        ];
    }
}
