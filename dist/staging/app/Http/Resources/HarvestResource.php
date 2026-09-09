<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HarvestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'crop_type'            => $this->crop_type,
            'variety'              => $this->variety,
            'quantity_kg'          => $this->quantity_kg,
            'remaining_quantity_kg' => $this->remaining_quantity_kg,
            'status'               => $this->status->value,
            'status_label'         => $this->status->label(),
            'status_color'         => $this->status->color(),
            'visibility'           => $this->visibility,
            'latitude'             => $this->latitude,
            'longitude'            => $this->longitude,
            'destination_address'  => $this->destination_address,
            'destination_latitude' => $this->destination_latitude,
            'destination_longitude' => $this->destination_longitude,
            'harvest_date'         => $this->harvest_date?->toDateString(),
            'quality_grade'        => $this->quality_grade,
            'created_at'           => $this->created_at?->toIso8601String(),
            'farmer'               => [
                'id'   => $this->farmer?->id,
                'name' => $this->farmer?->name,
            ],
            'crop' => [
                'id'   => $this->crop?->id,
                'name' => $this->crop?->name,
            ],
        ];
    }
}
