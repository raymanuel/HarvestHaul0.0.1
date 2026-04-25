<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Harvest;
use App\Models\Crop;
use App\Models\CropVariety;

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
                ->with('error', 'Your account is pending verification. You cannot post harvest listings until approved by an administrator.');
        }

        $crops = Crop::with(['varieties' => function ($query) {
                $query->where('status', 'active')->orderBy('name');
            }])
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('harvests.create', compact('crops'));
    }

    // -------------------------------------------------------
    // store — save new harvest listing
    // -------------------------------------------------------
    public function store(Request $request)
    {
        $this->authorizeFarmer();

        if (!$this->isVerifiedFarmer()) {
            return redirect()
                ->route('harvests.index')
                ->with('error', 'Your account is pending verification. You cannot post harvest listings until approved by an administrator.');
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
            'crop_id.required'             => 'Please select a crop.',
            'crop_id.exists'               => 'Selected crop is invalid.',
            'crop_variety_id.required'     => 'Please select a crop variety.',
            'crop_variety_id.exists'       => 'Selected variety is invalid.',
            'quantity_kg.max'              => 'Quantity cannot exceed 999,999.99 kg.',
            'quantity_kg.min'              => 'Quantity must be at least 0.01 kg.',
            'quantity_kg.numeric'          => 'Quantity must be a valid number.',
            'harvest_date.before_or_equal' => 'Harvest date cannot be in the future.',
        ]);

        $crop          = Crop::findOrFail($validated['crop_id']);
        $cropVariety   = CropVariety::findOrFail($validated['crop_variety_id']);
        $farmerProfile = Auth::user()->farmerProfile;

        Auth::user()->harvests()->create([
            'crop_id'          => $validated['crop_id'],
            'crop_variety_id'  => $validated['crop_variety_id'],
            'crop_category_id' => $crop->crop_category_id,
            'crop_type'        => $crop->name,
            'variety'          => $cropVariety->name,
            'quantity_kg'      => $validated['quantity_kg'],
            'unit'             => 'kg',
            'notes'            => $validated['notes'] ?? null,
            'harvest_date'     => $validated['harvest_date'] ?? null,
            'quality_grade'    => $validated['quality_grade'] ?? null,
            'packaging_type'   => $validated['packaging_type'] ?? null,
            'latitude'         => $farmerProfile?->latitude,
            'longitude'        => $farmerProfile?->longitude,
            'status'           => 'active',
        ]);

        return redirect()
            ->route('harvests.index')
            ->with('success', 'Harvest listing posted. You are now visible on the logistics map.');
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
                ->with('error', 'Your account is pending verification. You cannot edit harvest listings until approved by an administrator.');
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
                ->with('error', 'Your account is pending verification. You cannot edit harvest listings until approved by an administrator.');
        }

        $harvest = Auth::user()->harvests()->findOrFail($id);

        if (in_array($harvest->status, ['completed', 'cancelled'])) {
            return back()->with('error', 'This listing can no longer be modified.');
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

        $harvest->update([
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
        ]);

        return redirect()
            ->route('harvests.index')
            ->with('success', 'Harvest listing updated successfully.');
    }

    // -------------------------------------------------------
    // destroy — remove farmer's own harvest listing
    // -------------------------------------------------------
    public function destroy($id)
    {
        $this->authorizeFarmer();

        $harvest = Auth::user()->harvests()->findOrFail($id);

        if ($harvest->driver_id !== null) {
            return back()->with('error', 'Cannot remove a listing that already has a driver assigned.');
        }

        $harvest->delete();

        return back()->with('success', 'Harvest listing removed.');
    }
}
