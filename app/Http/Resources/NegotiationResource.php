<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NegotiationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $counterpart = $this->buyer_id === $user->id ? $this->farmer : $this->buyer;

        return [
            'id'               => $this->id,
            'crop'             => $this->harvest?->crop?->name ?? $this->harvest?->crop_type ?? 'Unknown',
            'variety'          => $this->harvest?->cropVariety?->name ?? $this->harvest?->variety ?? '',
            'lot'              => $this->harvest_id,
            'counterpart_name' => $counterpart?->name ?? '—',
            'counterpart_role' => $counterpart?->role ?? '',
            'status'           => $this->status->value,
            'status_label'     => $this->status->label(),
            'price'            => $this->negotiated_price,
            'volume'           => $this->negotiated_volume,
            'last_activity'    => $this->last_activity_at?->diffForHumans(),
            'url'              => route('negotiations.room', $this->id),
            'is_buyer'         => $this->buyer_id === $user->id,
            'unread_count'     => (int) ($this->unread_count ?? 0),
        ];
    }
}
