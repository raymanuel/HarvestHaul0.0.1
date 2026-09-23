<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Coop\LocationMonitoringController;
use App\Models\Cooperative;
use App\Models\Crop;
use App\Models\CropVariety;
use App\Models\HaulRequest;
use App\Models\Notification;
use App\Models\PackagingType;
use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class HaulRequestController extends Controller
{
    /**
     * The farmer's approved cooperative membership, or null when the farmer
     * is not an approved member of any cooperative. Farmers sell their
     * harvests through their cooperative, so pickup requests require one.
     */
    private function cooperativeMembership(): ?Cooperative
    {
        $profile = Auth::user()?->farmerProfile;

        if (! $profile || $profile->affiliation_type !== 'cooperative' || ! $profile->cooperative_id || $profile->membership_status !== 'approved') {
            return null;
        }

        return $profile->cooperative;
    }

    public function index()
    {
        $user = Auth::user();
        $cooperative = $this->cooperativeMembership();

        $requests = HaulRequest::where('farmer_id', $user->id)
            ->with(['crop', 'cropVariety', 'packagingType', 'haulJob'])
            ->orderByDesc('created_at')
            ->get();

        return view('farmer.haul-requests.index', compact('requests', 'cooperative'));
    }

    public function create()
    {
        $cooperative = $this->cooperativeMembership();

        $crops = Crop::active()->orderBy('name')->get();
        $varieties = CropVariety::active()->get();
        $packagings = PackagingType::active()->get();

        return view('farmer.haul-requests.create', compact('cooperative', 'crops', 'varieties', 'packagings'));
    }

    public function store(Request $request)
    {
        $cooperative = $this->cooperativeMembership();

        if (! $cooperative) {
            return redirect()->route('farmer.haul-requests.index')
                ->with('error', 'Only approved cooperative members can request a pickup.');
        }

        $data = $request->validate([
            'crop_id'           => 'required|exists:crops,id',
            'crop_variety_id'   => 'nullable|exists:crop_varieties,id',
            'packaging_type_id' => 'nullable|exists:packaging_types,id',
            'estimated_sacks'   => 'nullable|integer|min:1',
            'estimated_weight_kg' => 'required|numeric|min:0.01|max:100000',
            'harvest_date'      => 'nullable|date|before_or_equal:today',
            'preferred_pickup_date' => 'required|date|after_or_equal:today',
            'pickup_window_start' => 'required|date_format:H:i',
            'pickup_window_end'   => 'required|date_format:H:i',
            'pickup_location'   => 'required|string|max:500',
            'pickup_location_lat' => 'required|numeric|between:-90,90',
            'pickup_location_lng' => 'required|numeric|between:-180,180',
            'notes'             => 'nullable|string|max:1000',
        ]);

        if ($data['pickup_window_end'] <= $data['pickup_window_start']) {
            throw ValidationException::withMessages([
                'pickup_window_end' => 'The pickup window end time must be later than the start time.',
            ]);
        }

        $user = Auth::user();
        $crop = Crop::findOrFail($data['crop_id']);

        $haulRequest = HaulRequest::create(array_merge($data, [
            'farmer_id'      => $user->id,
            'cooperative_id' => $cooperative->id,
            'status'         => HaulRequest::STATUS_PENDING,
        ]));

        $this->notifyCoopAdmins($cooperative->id, [
            'title'   => 'New pickup request',
            'message' => "{$user->name} filed a pickup request for ~{$data['estimated_weight_kg']} kg of {$crop->name} on {$data['preferred_pickup_date']}. Review it, then schedule the pickup trip.",
            'link'    => route('coop.haul-requests.index'),
        ]);

        return redirect()->route('farmer.haul-requests.index')->with([
            'success' => 'Pickup request sent to your cooperative.',
            'next_steps' => [
                'title'   => 'Pickup request submitted',
                'message' => 'Your cooperative has been notified about your pickup request for '.$data['estimated_weight_kg'].' kg of '.$crop->name.'.',
                'steps'   => [
                    'Your cooperative will review your request.',
                    'Once scheduled, the pickup date, time window, and assigned truck show here.',
                    'You will get an in-app update when the cooperative responds.',
                ],
                'cta' => ['label' => 'View your pickup requests', 'url' => route('farmer.haul-requests.index')],
            ],
        ]);
    }

    public function track(HaulRequest $haulRequest)
    {
        $this->authorizeFarmer($haulRequest);
        $haulRequest->load('haulJob.cooperative', 'haulJob.stops');

        $haulJob = $haulRequest->haulJob;
        $stop = $haulJob?->stops->firstWhere('haul_request_id', $haulRequest->id);

        return view('farmer.haul-requests.track', compact('haulRequest', 'haulJob', 'stop'));
    }

    public function trackLocation(HaulRequest $haulRequest)
    {
        $this->authorizeFarmer($haulRequest);

        $haulJob = $haulRequest->haulJob;
        if (! $haulJob) {
            return response()->json(['has_position' => false]);
        }

        return LocationMonitoringController::positionJson($haulJob);
    }

    private function authorizeFarmer(HaulRequest $haulRequest): void
    {
        if ($haulRequest->farmer_id !== Auth::id()) {
            abort(403, 'This is not your pickup request.');
        }
    }

    public function cancel(HaulRequest $haulRequest)
    {
        $this->authorizeFarmer($haulRequest);

        if ($haulRequest->status !== HaulRequest::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'haul_request' => 'Only requests still awaiting scheduling can be cancelled.',
            ]);
        }

        $haulRequest->update(['status' => HaulRequest::STATUS_CANCELLED]);

        $this->notifyCoopAdmins($haulRequest->cooperative_id, [
            'title'   => 'Pickup request cancelled',
            'message' => Auth::user()->name." cancelled their pickup request for {$haulRequest->estimated_weight_kg} kg. No action needed.",
            'link'    => route('coop.haul-requests.index'),
        ]);

        return back()->with('success', 'Pickup request cancelled.');
    }

    private function notifyCoopAdmins(int $cooperativeId, array $payload): void
    {
        $admins = \App\Models\User::where('role', UserRole::COOP_ADMIN->value)
            ->where('cooperative_id', $cooperativeId)
            ->get();

        foreach ($admins as $admin) {
            Notification::create([
                'user_id'  => $admin->id,
                'title'    => $payload['title'],
                'message'  => $payload['message'],
                'link'     => $payload['link'],
                'category' => 'haul',
            ]);
        }
    }
}