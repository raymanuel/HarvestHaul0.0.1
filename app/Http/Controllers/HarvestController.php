<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Harvest;
use App\Models\HarvestStatus;
use App\Models\Crop;
use App\Models\CropVariety;
use Illuminate\Validation\Rule;

/**
 * Class HarvestController
 * 
 * Manages the CRUD lifecycle of crop harvest posts by farmer users.
 * 
 * System Flow:
 * 1. Post Harvest: Farmers register their upcoming crop volumes, matching crops/varieties,
 *    and delivery destinations.
 * 2. Geo-location Binding: The post automatically inherits the farmer's profile coordinates
 *    as the pickup location, while matching a pinned address as the destination.
 * 3. Commercial Check: Independent farmers are warned if no active/verified commercial logistics 
 *    coordinators are currently on the platform to transport their produce.
 */
class HarvestController extends Controller
{
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

    // -------------------------------------------------------
    // index — list farmer's own harvests
    // -------------------------------------------------------
    public function index()
    {
        $this->authorizeFarmer();

        $harvests = Auth::user()
            ->harvests()
            ->with(['crop', 'cropVariety'])
            ->latest()
            ->get();

        return view('harvests.index', compact('harvests'));
    }


    // -------------------------------------------------------
    // create — show the post harvest form
    // -------------------------------------------------------
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

        // --- Priority 5: Independent Farmer Warning Logic ---
        $farmerProfile = Auth::user()->farmerProfile;
        $isIndependent = $farmerProfile?->affiliation_type === 'independent';

        $hasCommercialLogistics = true; // Default to true so cooperative members bypass this logic

        if ($isIndependent) {
            // Count if there is at least one verified commercial fleet on the platform
            $hasCommercialLogistics = \App\Models\LogisticsProfile::where('logistics_type', 'company')
                ->where('is_verified', true)
                ->exists();
        }

        return view('harvests.create', compact('crops', 'destinations', 'isIndependent', 'hasCommercialLogistics'));
    }

    // -------------------------------------------------------
    // store — save new harvest post
    // -------------------------------------------------------
    public function store(Request $request)
    {
        $this->authorizeFarmer();

        if (!$this->isVerifiedFarmer()) {
            return redirect()
                ->route('harvests.index')
                ->with('error', 'Your account is pending verification. You cannot post harvests until approved by an administrator.');
        }

        if ($request->destination_id === 'custom') {
            $request->merge(['destination_id' => null]);
        }

        $validated = $request->validate([
            'crop_id'               => ['required', 'exists:crops,id'],
            'crop_variety_id'       => ['required', 'exists:crop_varieties,id'],
            'quantity_kg'           => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'notes'                 => ['nullable', 'string', 'max:1000'],
            'harvest_date'          => ['nullable', 'date', 'before_or_equal:today'],
            'quality_grade'         => ['nullable', 'string', 'max:100'],
            'packaging_type'        => ['nullable', 'string', 'max:100'],
            // destination
            'destination_id'        => ['nullable', 'exists:destinations,id'],
            'destination_address'   => ['required', 'string', 'max:500'],
            'destination_latitude'  => ['required', 'numeric', 'between:-90,90'],
            'destination_longitude' => ['required', 'numeric', 'between:-180,180'],
            'crop_photos'           => ['nullable', 'array', 'max:5'],
            'crop_photos.*'         => ['nullable', 'image', 'max:5120'], // 5MB each
        ], [
            'crop_id.required'              => 'Please select a crop.',
            'crop_variety_id.required'      => 'Please select a crop variety.',
            'quantity_kg.max'               => 'Quantity cannot exceed 999,999.99 kg.',
            'quantity_kg.min'               => 'Quantity must be at least 0.01 kg.',
            'quantity_kg.numeric'           => 'Quantity must be a valid number.',
            'harvest_date.before_or_equal'  => 'Harvest date cannot be in the future.',
            'destination_address.required'  => 'Please select or pin a delivery destination.',
            'destination_latitude.required' => 'Please select or pin a delivery destination.',
        ]);

        // Validate destination within Philippines bounds
        $destLat = (float) $validated['destination_latitude'];
        $destLng = (float) $validated['destination_longitude'];
        if ($destLat < 4 || $destLat > 21 || $destLng < 116 || $destLng > 127) {
            return back()->withInput()->with('error', 'Destination must be within the Philippines (4°N–21°N, 116°E–127°E).');
        }

        $crop          = Crop::findOrFail($validated['crop_id']);
        $cropVariety   = CropVariety::findOrFail($validated['crop_variety_id']);

        // Validate crop_variety belongs to selected crop
        if ($cropVariety->crop_id !== $crop->id) {
            return back()->withInput()->with('error', 'Selected variety does not belong to the selected crop.');
        }
        $farmerProfile = Auth::user()->farmerProfile;

        $harvest = Auth::user()->harvests()->create([
            'crop_id'               => $validated['crop_id'],
            'crop_variety_id'       => $validated['crop_variety_id'],
            'crop_category_id'      => $crop->crop_category_id,
            'crop_type'             => $crop->name,
            'variety'               => $cropVariety->name,
            'quantity_kg'           => $validated['quantity_kg'],
            'remaining_quantity_kg' => $validated['quantity_kg'],
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
            'status'                => 'active',
            'visibility'            => $farmerProfile?->affiliation_type === 'independent' ? 'buyers_only' : 'both',
        ]);

        // Handle crop photos
        if ($request->hasFile('crop_photos')) {
            $paths = [];
            foreach ($request->file('crop_photos') as $photo) {
                $paths[] = $photo->store('crop-photos/' . $harvest->id, 'public');
            }
            $harvest->update(['crop_photos' => $paths]);
        }

        \App\Models\AuditLog::create([
            'admin_id'    => Auth::id(),
            'action'      => 'created_harvest',
            'target_type' => 'harvest',
            'target_id'   => $harvest->id,
            'notes'       => "Farmer " . Auth::user()->name . " created harvest post for {$harvest->crop_type} ({$harvest->quantity_kg} kg).",
        ]);

        return redirect()
            ->route('harvests.index')
            ->with('success', 'Harvest post published. You are now visible on the logistics map.');
    }

    // -------------------------------------------------------
    // edit — show edit form for an existing harvest
    // -------------------------------------------------------
    public function edit($id)
    {
        $this->authorizeFarmer();

        if (!$this->isVerifiedFarmer()) {
            return redirect()
                ->route('harvests.index')
                ->with('error', 'Your account is pending verification. You cannot edit harvests until approved by an administrator.');
        }

        $harvest = Auth::user()->harvests()->findOrFail($id);

        $crops = Crop::with(['varieties' => function ($query) {
                $query->where('status', 'active')->orderBy('name');
            }])
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('harvests.edit', compact('harvest', 'crops'));
    }

    // -------------------------------------------------------
    // update — persist edits to an existing harvest
    // -------------------------------------------------------
    public function update(Request $request, $id)
    {
        $this->authorizeFarmer();

        if (!$this->isVerifiedFarmer()) {
            return redirect()
                ->route('harvests.index')
                ->with('error', 'Your account is pending verification. You cannot edit harvests until approved by an administrator.');
        }

        $harvest = Auth::user()->harvests()->findOrFail($id);

        if (in_array($harvest->status, HarvestStatus::LOCKED)) {
            return back()->with('error', 'This post can no longer be modified.');
        }

        // Block edit if harvest has active pooling proposals
        if ($harvest->poolingJobs()->where('pooling_jobs.status', 'in', ['pending', 'confirmed', 'in_progress'])->exists()) {
            return back()->with('error', 'Cannot edit while a logistics proposal is active.');
        }

        $validated = $request->validate([
            'crop_id'         => ['required', 'exists:crops,id'],
            'crop_variety_id' => ['required', 'exists:crop_varieties,id'],
            'quantity_kg'     => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'notes'           => ['nullable', 'string', 'max:1000'],
            'harvest_date'    => ['nullable', 'date', 'before_or_equal:today'],
            'quality_grade'   => ['nullable', 'string', 'max:100'],
            'packaging_type'  => ['nullable', 'string', 'max:100'],
        ], [
            'crop_id.required'         => 'Please select a crop.',
            'crop_variety_id.required' => 'Please select a crop variety.',
            'quantity_kg.max'          => 'Quantity cannot exceed 999,999.99 kg.',
            'quantity_kg.min'          => 'Quantity must be at least 0.01 kg.',
        ]);

        $crop        = Crop::findOrFail($validated['crop_id']);
        $cropVariety = CropVariety::findOrFail($validated['crop_variety_id']);

        if ($cropVariety->crop_id !== $crop->id) {
            return back()->withInput()->with('error', 'Selected variety does not belong to the selected crop.');
        }

        $updateData = [
            'crop_id'          => $validated['crop_id'],
            'crop_variety_id'  => $validated['crop_variety_id'],
            'crop_category_id' => $crop->crop_category_id,
            'crop_type'        => $crop->name,
            'variety'          => $cropVariety->name,
            'quantity_kg'      => $validated['quantity_kg'],
            'notes'            => $validated['notes'] ?? null,
            'harvest_date'     => $validated['harvest_date'] ?? null,
            'quality_grade'    => $validated['quality_grade'] ?? null,
            'packaging_type'   => $validated['packaging_type'] ?? null,
        ];

        // If harvest is still active (no deals), sync remaining qty with new quantity
        if ($harvest->status === HarvestStatus::ACTIVE) {
            $updateData['remaining_quantity_kg'] = $validated['quantity_kg'];
        }

        $harvest->update($updateData);

        \App\Models\AuditLog::create([
            'admin_id'    => Auth::id(),
            'action'      => 'updated_harvest',
            'target_type' => 'harvest',
            'target_id'   => $harvest->id,
            'notes'       => "Farmer " . Auth::user()->name . " updated harvest post for {$harvest->crop_type} ({$harvest->quantity_kg} kg).",
        ]);

        return redirect()
            ->route('harvests.index')
            ->with('success', 'Harvest post updated successfully.');
    }

    // -------------------------------------------------------
    // destroy — remove farmer's own harvest post
    // -------------------------------------------------------
    public function destroy($id)
    {
        $this->authorizeFarmer();

        $harvest = Auth::user()->harvests()->findOrFail($id);

        if ($harvest->driver_id !== null) {
            return back()->with('error', 'Cannot remove a post that already has a driver assigned.');
        }

        // Block deletion if harvest is attached to any active pooling job
        if ($harvest->poolingJobs()->where('pooling_jobs.status', 'in', ['pending', 'confirmed', 'in_progress'])->exists()) {
            return back()->with('error', 'Cannot remove a post that is part of an active pooling route. Wait for the route to complete or be cancelled.');
        }

        $harvest->delete();

        \App\Models\AuditLog::create([
            'admin_id'    => Auth::id(),
            'action'      => 'deleted_harvest',
            'target_type' => 'harvest',
            'target_id'   => $harvest->id,
            'notes'       => "Farmer " . Auth::user()->name . " deleted harvest post for {$harvest->crop_type} ({$harvest->quantity_kg} kg).",
        ]);

        return back()->with('success', 'Harvest post removed.');
    }
}
