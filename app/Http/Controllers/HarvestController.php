<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Harvest;
use App\Models\HarvestStatus;
use App\Models\NegotiationStatus;
use App\Models\Crop;
use App\Models\CropVariety;
use App\Services\CropResolverService;
use App\Traits\Notifiable;

class HarvestController extends Controller
{
    use Notifiable;

    private function authorizeFarmer(): void
    {
        if (Auth::user()->role !== 'farmer') {
            abort(403, 'Unauthorized action.');
        }
    }

    private function isVerifiedFarmer(): bool
    {
        return (bool) Auth::user()->farmerProfile?->is_verified;
    }

    public function index()
    {
        $this->authorizeFarmer();

        $harvests = Auth::user()
            ->harvests()
            ->with(['crop', 'cropVariety'])
            ->latest()
            ->paginate(20);

        return view('harvests.index', compact('harvests'));
    }

    public function create()
    {
        $this->authorizeFarmer();

        if (!$this->isVerifiedFarmer()) {
            return redirect()
                ->route('harvests.index')
                ->with('error', 'Your account is pending verification. You cannot post harvests until approved by an administrator.');
        }

        $crops = Crop::with(['varieties' => function ($query) {
                $query->where('status', 'active')->orderBy('name');
            }])
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $destinations = \App\Models\Destination::active()->orderBy('type')->orderBy('name')->get();

        $farmerProfile = Auth::user()->farmerProfile;
        $isIndependent = $farmerProfile?->affiliation_type === 'independent';

        $hasCommercialLogistics = true;

        if ($isIndependent) {
            $hasCommercialLogistics = \App\Models\LogisticsProfile::where('logistics_type', 'company')
                ->where('is_verified', true)
                ->exists();
        }

        return view('harvests.create', compact('crops', 'destinations', 'isIndependent', 'hasCommercialLogistics'));
    }

    public function store(\App\Http\Requests\StoreHarvestRequest $request)
    {
        if ($request->destination_id === 'custom') {
            $request->merge(['destination_id' => null]);
        }

        $validated = $request->validated();

        $destLat = (float) $validated['destination_latitude'];
        $destLng = (float) $validated['destination_longitude'];
        if ($destLat < 4 || $destLat > 21 || $destLng < 116 || $destLng > 127) {
            return back()->withInput()->with('error', 'Destination must be within the Philippines (4°N–21°N, 116°E–127°E).');
        }

        $resolver = app(CropResolverService::class);

        if (!empty($validated['custom_crop_name'])) {
            $categoryId = Crop::find($validated['crop_id'])?->crop_category_id ?? 1;
            $crop = $resolver->resolveCrop($validated['custom_crop_name'], $categoryId);
            $validated['crop_id'] = $crop->id;
        } else {
            $crop = Crop::findOrFail($validated['crop_id']);
        }

        if (!empty($validated['custom_variety_name'])) {
            $cropVariety = $resolver->resolveVariety($crop, $validated['custom_variety_name']);
            $validated['crop_variety_id'] = $cropVariety->id;
        } else {
            $cropVariety = CropVariety::findOrFail($validated['crop_variety_id']);
        }

        if ($cropVariety->crop_id !== $crop->id) {
            return back()->withInput()->with('error', 'Selected variety does not belong to the selected crop.');
        }
        $farmerProfile = Auth::user()->farmerProfile;

        $harvest = Auth::user()->harvests()->create([
            'crop_id'               => $crop->id,
            'crop_variety_id'       => $cropVariety->id,
            'crop_category_id'      => $crop->crop_category_id,
            'crop_type'             => $crop->name,
            'variety'               => $cropVariety->name,
            'quantity_kg'           => $validated['quantity_kg'],
            'remaining_quantity_kg' => $validated['quantity_kg'],
            'suggested_price_per_kg'=> $validated['suggested_price_per_kg'] ?? null,
            'unit'                  => 'kg',
            'notes'                 => $validated['notes'] ?? null,
            'harvest_date'          => $validated['harvest_date'] ?? null,
            'quality_grade'         => $validated['quality_grade'] ?? null,
            'packaging_type'        => $validated['packaging_type'] ?? null,
            'latitude'              => $farmerProfile?->latitude,
            'longitude'             => $farmerProfile?->longitude,
            'destination_id'        => $validated['destination_id'] ?? null,
            'destination_address'   => $validated['destination_address'],
            'destination_latitude'  => $validated['destination_latitude'],
            'destination_longitude' => $validated['destination_longitude'],
            'status'                => HarvestStatus::ACTIVE,
            'visibility'            => $farmerProfile?->affiliation_type === 'independent' ? 'buyers_only' : 'both',
        ]);

        if ($request->hasFile('crop_photos')) {
            $paths = [];
            foreach ($request->file('crop_photos') as $photo) {
                $paths[] = $photo->store('crop-photos/' . $harvest->id, 'public');
            }
            $harvest->update(['crop_photos' => $paths]);
        }

        self::logAudit(Auth::id(), 'created_harvest', 'harvest', $harvest->id,
            "Farmer " . Auth::user()->name . " created harvest post for {$harvest->crop_type} ({$harvest->quantity_kg} kg).");

        return redirect()
            ->route('harvests.index')
            ->with('success', 'Harvest post published. You are now visible on the logistics map.');
    }

    public function edit(Harvest $harvest)
    {
        $this->authorizeFarmer();
        $this->authorize('update', $harvest);

        if (!$this->isVerifiedFarmer()) {
            return redirect()
                ->route('harvests.index')
                ->with('error', 'Your account is pending verification. You cannot edit harvests until approved by an administrator.');
        }

        $crops = Crop::with(['varieties' => function ($query) {
                $query->where('status', 'active')->orderBy('name');
            }])
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('harvests.edit', compact('harvest', 'crops'));
    }

    public function update(\App\Http\Requests\UpdateHarvestRequest $request, Harvest $harvest)
    {
        $this->authorize('update', $harvest);

        if ($harvest->status->isLocked()) {
            return back()->with('error', 'This post can no longer be modified.');
        }

        if ($harvest->poolingJobs()->where('pooling_jobs.status', 'in', ['pending', 'confirmed', 'in_progress'])->exists()) {
            return back()->with('error', 'Cannot edit while a logistics proposal is active.');
        }

        $validated = $request->validated();

        $resolver = app(CropResolverService::class);

        if (!empty($validated['custom_crop_name'])) {
            $categoryId = Crop::find($validated['crop_id'])?->crop_category_id ?? 1;
            $crop = $resolver->resolveCrop($validated['custom_crop_name'], $categoryId);
            $validated['crop_id'] = $crop->id;
        } else {
            $crop = Crop::findOrFail($validated['crop_id']);
        }

        if (!empty($validated['custom_variety_name'])) {
            $cropVariety = $resolver->resolveVariety($crop, $validated['custom_variety_name']);
            $validated['crop_variety_id'] = $cropVariety->id;
        } else {
            $cropVariety = CropVariety::findOrFail($validated['crop_variety_id']);
        }

        if ($cropVariety->crop_id !== $crop->id) {
            return back()->withInput()->with('error', 'Selected variety does not belong to the selected crop.');
        }

        $updateData = [
            'crop_id'               => $crop->id,
            'crop_variety_id'       => $cropVariety->id,
            'crop_category_id'      => $crop->crop_category_id,
            'crop_type'             => $crop->name,
            'variety'               => $cropVariety->name,
            'quantity_kg'           => $validated['quantity_kg'],
            'suggested_price_per_kg'=> $validated['suggested_price_per_kg'] ?? null,
            'notes'                 => $validated['notes'] ?? null,
            'harvest_date'          => $validated['harvest_date'] ?? null,
            'quality_grade'         => $validated['quality_grade'] ?? null,
            'packaging_type'        => $validated['packaging_type'] ?? null,
        ];

        if ($harvest->status === HarvestStatus::ACTIVE) {
            $updateData['remaining_quantity_kg'] = $validated['quantity_kg'];
        }

        $harvest->update($updateData);

        self::logAudit(Auth::id(), 'updated_harvest', 'harvest', $harvest->id,
            "Farmer " . Auth::user()->name . " updated harvest post for {$harvest->crop_type} ({$harvest->quantity_kg} kg).");

        return redirect()
            ->route('harvests.index')
            ->with('success', 'Harvest post updated successfully.');
    }

    public function destroy(Harvest $harvest)
    {
        $this->authorizeFarmer();
        $this->authorize('delete', $harvest);

        if ($harvest->driver_id !== null) {
            return back()->with('error', 'Cannot remove a post that already has a driver assigned.');
        }

        if ($harvest->poolingJobs()->where('pooling_jobs.status', 'in', ['pending', 'confirmed', 'in_progress'])->exists()) {
            return back()->with('error', 'Cannot remove a post that is part of an active pooling route. Wait for the route to complete or be cancelled.');
        }

        if ($harvest->negotiations()->whereIn('status', [NegotiationStatus::OPEN, NegotiationStatus::AGREED])->exists()) {
            return back()->with('error', 'Cannot remove a post with active negotiations. Cancel the negotiation first.');
        }

        $harvest->delete();

        self::logAudit(Auth::id(), 'deleted_harvest', 'harvest', $harvest->id,
            "Farmer " . Auth::user()->name . " deleted harvest post for {$harvest->crop_type} ({$harvest->quantity_kg} kg).");

        return back()->with('success', 'Harvest post removed.');
    }
}
