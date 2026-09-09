<?php

namespace App\Http\Controllers;

use App\Models\HaulRequest;
use App\Models\HaulIntent;
use App\Models\HaulIntentMessage;
use App\Models\Harvest;
use App\Models\HarvestStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HaulRequestController extends Controller
{
    public function farmerHaulRequests()
    {
        $user = Auth::user();

        $haulRequests = HaulRequest::where('user_id', $user->id)
            ->with([
                'harvest.crop',
                'harvest.cropVariety',
                'harvest.destination',
                'intents.logisticsProfile.user',
            ])
            ->latest()
            ->get();

        return view('farmers.haul-requests', compact('haulRequests'));
    }

    public function create(Request $request, Harvest $harvest)
    {
        // Cooperative members are hauled by their own cooperative via Route Offers
        // (PoolingJob) — the haul request flow is for independent farmers only.
        if (Auth::user()->farmerProfile?->affiliation_type === 'cooperative') {
            return back()->with('error', 'Cooperative members are hauled through their cooperative\'s route offers. No haul request needed.');
        }

        if ($harvest->user_id !== Auth::id()) {
            abort(403);
        }

        if (!in_array($harvest->status->value, ['sold', 'partially_sold'])) {
            return back()->with('error', 'Only sold or partially-sold harvests can request haul.');
        }

        $openRequest = HaulRequest::where('harvest_id', $harvest->id)
            ->whereIn('status', ['open', 'booked'])
            ->exists();

        if ($openRequest) {
            return back()->with('error', 'A haul request for this harvest is already open. Wait for it to be resolved first.');
        }

        $validated = $request->validate([
            'pickup_date' => ['nullable', 'date', 'after_or_equal:today'],
            'notes'       => ['nullable', 'string', 'max:500'],
        ]);

        $agreedNegotiation = $harvest->agreedNegotiation;

        HaulRequest::create([
            'harvest_id'     => $harvest->id,
            'user_id'        => Auth::id(),
            'buyer_id'       => $agreedNegotiation?->buyer_id,
            'negotiation_id' => $agreedNegotiation?->id,
            'pickup_date'    => $validated['pickup_date'] ?? null,
            'notes'          => $validated['notes'] ?? null,
        ]);

        return back()->with('success', 'Haul request posted. Logistics partners can now express interest.');
    }

    public function expressIntent(Request $request, HaulRequest $haulRequest)
    {
        if (Auth::user()->role !== 'logistics_partner') {
            abort(403);
        }

        $logisticsProfile = Auth::user()->logisticsProfile;

        // Verify affiliation compatibility — cooperative farmers need cooperative logistics
        $haulRequest->load('harvest.farmer');
        $farmerAffiliation = $haulRequest->harvest->farmer->affiliation_type ?? 'independent';
        $logisticsAffiliation = $logisticsProfile->logistics_type ?? 'independent';
        if ($farmerAffiliation === 'cooperative' && $logisticsAffiliation !== 'cooperative') {
            return back()->with('error', 'Cooperative farmers require cooperative logistics partners.');
        }

        $alreadyExpressed = HaulIntent::where('haul_request_id', $haulRequest->id)
            ->where('logistics_profile_id', $logisticsProfile->id)
            ->whereIn('status', ['pending', 'accepted'])
            ->exists();

        if ($alreadyExpressed) {
            return back()->with('error', 'You already expressed intent for this haul request.');
        }

        $validated = $request->validate([
            'notes'                 => ['nullable', 'string', 'max:500'],
            'suggested_date'        => ['nullable', 'date', 'after_or_equal:today'],
            'offer_rate_php_per_kg' => ['nullable', 'numeric', 'min:0.5', 'max:1000'],
        ]);

        $intent = HaulIntent::create([
            'haul_request_id'     => $haulRequest->id,
            'logistics_profile_id' => $logisticsProfile->id,
            'notes'               => $validated['notes'] ?? null,
            'suggested_date'      => $validated['suggested_date'] ?? null,
            'offer_rate_php_per_kg' => $validated['offer_rate_php_per_kg'] ?? null,
        ]);

        HaulIntentMessage::create([
            'haul_intent_id' => $intent->id,
            'sender_id'      => Auth::id(),
            'message_text'   => 'Hello! We would like to haul this harvest.'
                . (($validated['offer_rate_php_per_kg'] ?? null)
                    ? ' Proposed rate: ₱' . number_format((float) ($validated['offer_rate_php_per_kg'] ?? 0), 2) . '/kg.'
                    : ''),
        ]);

        self::notifyHaulIntentExpressed(
            $haulRequest->user_id,
            $logisticsProfile->company_name,
            $validated['offer_rate_php_per_kg'] ?? null,
        );

        return back()->with('success', 'Intent expressed. Farmer will review your offer.');
    }

    public function acceptIntent(HaulIntent $haulIntent)
    {
        $haulRequest = $haulIntent->haulRequest;

        if ($haulRequest->user_id !== Auth::id()) {
            abort(403);
        }

        if ($haulRequest->status !== 'open') {
            return back()->with('error', 'This haul request is no longer open.');
        }

        if (!in_array($haulIntent->status, ['pending', 'agreed'])) {
            return back()->with('error', 'This intent is no longer pending.');
        }

        $haulIntent->update([
            'status'                  => 'accepted',
            'hauling_rate_php_per_kg' => $haulIntent->hauling_rate_php_per_kg ?? $haulIntent->currentRate(),
        ]);

        HaulIntent::where('haul_request_id', $haulRequest->id)
            ->where('id', '!=', $haulIntent->id)
            ->where('status', 'pending')
            ->update(['status' => 'declined']);

        $haulRequest->update(['status' => 'booked']);

        $harvest = $haulRequest->harvest;
        $harvest->update(['status' => HarvestStatus::BOOKED]);

        self::notifyHaulBookingConfirmed(
            $haulIntent->logisticsProfile->user_id,
            $harvest->id,
            $haulIntent->hauling_rate_php_per_kg,
        );

        return back()->with('success', 'Logistics partner accepted for haul.');
    }

    public function declineIntent(HaulIntent $haulIntent)
    {
        $haulRequest = $haulIntent->haulRequest;

        if ($haulRequest->user_id !== Auth::id()) {
            abort(403);
        }

        if ($haulIntent->status !== 'pending') {
            return back()->with('error', 'This intent is no longer pending.');
        }

        $haulIntent->update(['status' => 'declined']);

        self::notifyHaulIntentDeclined(
            $haulIntent->logisticsProfile->user_id,
            $haulRequest->harvest_id,
        );

        return back()->with('success', 'Intent declined.');
    }
}
