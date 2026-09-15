<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Harvest;
use App\Models\HarvestStatus;
use App\Models\NegotiationStatus;
use App\Models\Crop;
use App\Models\CropVariety;
use App\Models\PoolingJobStatus;
use App\Services\CropResolverService;
use App\Traits\Notifiable;

class HarvestController extends Controller
{
    use Notifiable;

    private function isVerifiedFarmer(): bool
    {
        return (bool) Auth::user()->farmerProfile?->is_verified;
    }

    public function index(Request $request)
    {
        $tab = in_array($request->query('tab'), ['active', 'history'], true)
            ? $request->query('tab')
            : 'active';

        $query = Auth::user()
            ->harvests()
            ->with(['crop', 'cropVariety', 'haulRequest', 'destination']);

        if ($tab === 'history') {
            $query->whereIn('status', [HarvestStatus::COMPLETED, HarvestStatus::CANCELLED]);
        } else {
            $query->whereNotIn('status', [HarvestStatus::COMPLETED, HarvestStatus::CANCELLED]);
        }

        $harvests = $query->latest()->paginate(20)->withQueryString();

        return view('harvests.index', compact('harvests', 'tab'));
    }

    public function show(Harvest $harvest)
    {
        if ($harvest->user_id !== Auth::id()) {
            abort(403);
        }

        $harvest->load([
            'crop',
            'cropVariety',
            'destination',
            'negotiations' => function ($q) {
                $q->with(['buyer', 'messages'])->latest();
            },
            'poolingJobs' => function ($q) {
                $q->with(['truck', 'driver', 'harvests'])->latest();
            },
        ]);

        $activeNegotiation = $harvest->negotiations
            ->where('status', '!=', NegotiationStatus::CANCELLED)
            ->sortByDesc('last_activity_at')
            ->first();

        $activePoolingJob = $harvest->poolingJobs
            ->whereIn('status', [
                PoolingJobStatus::PENDING,
                PoolingJobStatus::CONFIRMED,
                PoolingJobStatus::IN_PROGRESS,
                PoolingJobStatus::AWAITING_CONFIRMATION,
            ])
            ->sortByDesc('created_at')
            ->first();

        return view('farmers.crop-hub', compact('harvest', 'activeNegotiation', 'activePoolingJob'));
    }

    public function create()
    {
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
        $coop = $farmerProfile?->isCooperativeMember()
            ? $farmerProfile->cooperative
            : null;

        $hasCommercialLogistics = true;

        if ($isIndependent) {
            $hasCommercialLogistics = \App\Models\LogisticsProfile::where('logistics_type', 'company')
                ->where('is_verified', true)
                ->exists();
        }

        return view('harvests.create', compact('crops', 'destinations', 'isIndependent', 'hasCommercialLogistics', 'farmerProfile', 'coop'));
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

        $hasPopupLocation = !empty($validated['popup_latitude']) && !empty($validated['popup_longitude']);
        $hasProfileLocation = !is_null($farmerProfile->latitude) && !is_null($farmerProfile->longitude);

        if ($hasPopupLocation) {
            $latitude = (float) $validated['popup_latitude'];
            $longitude = (float) $validated['popup_longitude'];

            if (!empty($validated['popup_save_permanently'])) {
                $farmerProfile->update([
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'farm_location' => $validated['popup_address'] ?? $farmerProfile->farm_location,
                ]);
            }
        } elseif ($hasProfileLocation) {
            $latitude = (float) $farmerProfile->latitude;
            $longitude = (float) $farmerProfile->longitude;
        } else {
            return back()->withInput()->with('error', 'Please provide a pickup location for this harvest.');
        }

        if ($farmerProfile?->isCooperativeMember() && $farmerProfile->cooperative?->latitude && $farmerProfile->cooperative?->longitude) {
            $coop = $farmerProfile->cooperative;
            $validated['destination_id']        = null;
            $validated['destination_address']   = $coop->office_address ?: ($coop->company_name . ' Drop-off Point');
            $validated['destination_latitude']  = $coop->latitude;
            $validated['destination_longitude'] = $coop->longitude;
        }

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
            'estimated_volume_cubic_m' => $validated['estimated_volume_cubic_m'] ?? null,
            'pickup_window_start'   => $validated['pickup_window_start'] ?? null,
            'pickup_window_end'     => $validated['pickup_window_end'] ?? null,
            'latitude'              => $latitude,
            'longitude'             => $longitude,
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
                $filename = uniqid('crop_', true) . '.' . $photo->getClientOriginalExtension();
                $path = $photo->storeAs('crop-photos/' . $harvest->id, $filename, 'public');

                // Strip EXIF metadata (GPS coordinates, camera info) for privacy
                if (function_exists('imagecreatefromjpeg') && function_exists('imagecreatefrompng')) {
                    $fullPath = storage_path('app/public/' . $path);
                    $imageInfo = @getimagesize($fullPath);
                    if ($imageInfo) {
                        $image = match($imageInfo[2]) {
                            IMAGETYPE_JPEG => imagecreatefromjpeg($fullPath),
                            IMAGETYPE_PNG  => imagecreatefrompng($fullPath),
                            default        => null,
                        };
                        if ($image) {
                            match($imageInfo[2]) {
                                IMAGETYPE_JPEG => imagejpeg($image, $fullPath, 85),
                                IMAGETYPE_PNG  => imagepng($image, $fullPath, 6),
                            };
                            imagedestroy($image);
                        }
                    }
                }
                $paths[] = $path;
            }
            $harvest->update(['crop_photos' => $paths]);
        }

        self::logAudit(Auth::id(), 'created_harvest', 'harvest', $harvest->id,
            "Farmer " . Auth::user()->name . " created harvest post for {$harvest->crop_type} ({$harvest->quantity_kg} kg).");

        $isCoop = $farmerProfile?->affiliation_type === 'cooperative';

        $coopLogisticsUserId = $isCoop ? ($farmerProfile->cooperative?->user_id) : null;

        self::notifyHarvestPosted(Auth::id(), $harvest->id, $harvest->crop_type, $coopLogisticsUserId);

        return redirect()
            ->route('harvests.index')
            ->with('success', $isCoop
                ? 'Your harvest was posted and is now on the crop board.'
                : 'Your harvest was posted and is now on the crop board and logistics map.')
            ->with('next_steps', $isCoop
                ? [
                    'title'   => 'Harvest published',
                    'message' => 'Your harvest was posted. Go to the crop board to see your post.',
                    'steps'   => [
                        'Buyers can now discover your harvest on the crop board.',
                    ],
                    'cta' => ['label' => 'View My Harvests', 'url' => route('harvests.index')],
                ]
                : [
                    'title'   => 'Harvest published',
                    'message' => 'Your harvest was posted. Go to the crop board to see your post.',
                    'steps'   => [
                        'Buyers can now discover your harvest on the crop board.',
                        'Logistics partners can see it on the map for route planning.',
                        'You can edit or mark it sold from your harvest list anytime.',
                    ],
                    'cta' => ['label' => 'View My Harvests', 'url' => route('harvests.index')],
                ]);
    }

    public function edit(Harvest $harvest)
    {
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
            'estimated_volume_cubic_m' => $validated['estimated_volume_cubic_m'] ?? null,
            'pickup_window_start'   => $validated['pickup_window_start'] ?? null,
            'pickup_window_end'     => $validated['pickup_window_end'] ?? null,
        ];

        if ($harvest->status === HarvestStatus::ACTIVE) {
            $updateData['remaining_quantity_kg'] = $validated['quantity_kg'];
        }

        $harvest->update($updateData);

        self::logAudit(Auth::id(), 'updated_harvest', 'harvest', $harvest->id,
            "Farmer " . Auth::user()->name . " updated harvest post for {$harvest->crop_type} ({$harvest->quantity_kg} kg).");

        return redirect()
            ->route('harvests.index')
            ->with('success', 'Harvest post updated successfully.')
            ->with('next_steps', [
                'title'   => 'Harvest updated',
                'message' => 'Your harvest post was updated. Buyers will see the latest details.',
                'steps'   => [
                    'Review the updated listing to confirm the new details look correct.',
                    'Keep quantity and price current so buyer offers match your stock.',
                    'Respond to negotiations to keep the deal moving.',
                ],
                'cta' => ['label' => 'View My Harvests', 'url' => route('harvests.index')],
            ]);
    }

    public function destroy(Harvest $harvest)
    {
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

        return back()->with('success', 'Harvest post removed.')
            ->with('next_steps', [
                'title'   => 'Post removed',
                'message' => 'Your harvest post has been deleted.',
                'steps'   => [
                    'This post will no longer appear on the crop board.',
                ],
                'cta' => ['label' => 'View My Harvests', 'url' => route('harvests.index')],
            ]);
    }

    public function markAsSold(Harvest $harvest)
    {
        if ($harvest->user_id !== Auth::id()) {
            abort(403);
        }

        if (!in_array($harvest->status->value, ['active', 'partially_sold'])) {
            return back()->with('error', 'This harvest cannot be marked as sold in its current status.');
        }

        if ($harvest->visibility !== 'buyers_only') {
            return back()->with('error', 'This harvest is already visible to logistics.');
        }

        $activePoolingJob = \App\Models\PoolingJob::whereIn('status', ['pending', 'confirmed', 'in_progress', 'awaiting_confirmation'])
            ->whereHas('harvests', fn($q) => $q->where('harvest_id', $harvest->id))
            ->exists();

        if ($activePoolingJob) {
            return back()->with('error', 'This harvest is assigned to an active pooling job and cannot be marked as sold.');
        }

        $agreedNegotiation = \App\Models\Negotiation::where('harvest_id', $harvest->id)
            ->where('status', NegotiationStatus::AGREED)
            ->exists();

        if ($agreedNegotiation) {
            return back()->with('error', 'This harvest has an agreed deal. Resolve the pending deal before marking it as sold externally.');
        }

        $completedNegotiation = \App\Models\Negotiation::where('harvest_id', $harvest->id)
            ->where('status', NegotiationStatus::COMPLETED)
            ->exists();

        if ($completedNegotiation) {
            return back()->with('error', 'This harvest has completed deals. Remaining stock cannot be marked as sold externally.');
        }

        // Auto-cancel any open inquiries so buyers are not left hanging on a sold listing.
        $openNegotiations = \App\Models\Negotiation::where('harvest_id', $harvest->id)
            ->where('status', NegotiationStatus::OPEN)
            ->get();

        foreach ($openNegotiations as $negotiation) {
            $negotiation->update(['status' => NegotiationStatus::CANCELLED]);
            self::notifyListingSoldElsewhere(
                $negotiation->buyer_id,
                $harvest->crop->name
            );
        }

        $harvest->update([
            'status' => HarvestStatus::SOLD,
            'remaining_quantity_kg' => 0,
            'visibility' => 'logistics_only',
        ]);

        self::logAudit(Auth::id(), 'marked_harvest_as_sold', 'harvest', $harvest->id,
            "Farmer " . Auth::user()->name . " marked harvest {$harvest->crop_type} as sold externally.");

        return back()->with('success', 'Harvest marked as sold. It is now visible to logistics partners.');
    }
}
