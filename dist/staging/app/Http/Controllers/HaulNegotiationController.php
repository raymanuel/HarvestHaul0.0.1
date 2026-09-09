<?php

namespace App\Http\Controllers;

use App\Models\HaulIntent;
use App\Models\HaulIntentMessage;
use App\Services\HaulingRateCalculator;
use App\Traits\GeometryHelper;
use App\Traits\Notifiable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Farmer <-> Logistics in-app haul negotiation.
 *
 * Reuses the crop-negotiation chat-room experience (message bubbles,
 * system-offer notices, proposed-terms panel) but on a haul intent:
 *  - logistics proposes a hauling rate (₱/kg)
 *  - farmer counters
 *  - either party agrees -> intent is booked (farmer) or marked agreed (logistics)
 */
class HaulNegotiationController extends Controller
{
    use Notifiable, GeometryHelper;

    private function authorizeParty(HaulIntent $intent): void
    {
        $user = Auth::user();

        if ($user->role === 'farmer') {
            if ($intent->haulRequest->user_id !== $user->id) {
                abort(403);
            }
            return;
        }

        if ($user->role === 'logistics_partner') {
            if ($intent->logistics_profile_id !== $user->logisticsProfile?->id) {
                abort(403);
            }
            return;
        }

        abort(403);
    }

    private function isFarmerSide(): bool
    {
        return Auth::user()->role === 'farmer';
    }

    public function room(HaulIntent $haulIntent)
    {
        $this->authorizeParty($haulIntent);

        $haulIntent->load([
            'haulRequest.harvest.crop',
            'haulRequest.harvest.cropVariety',
            'haulRequest.farmer',
            'logisticsProfile.user',
            'messages.sender',
        ]);

        $rateReference = null;
        $h = $haulIntent->haulRequest?->harvest;
        $pickupLat = (float) ($h->latitude ?? $h->farmer?->farmerProfile?->latitude);
        $pickupLng = (float) ($h->longitude ?? $h->farmer?->farmerProfile?->longitude);
        $destLat = (float) ($h?->destination?->latitude ?? $h?->destination_latitude);
        $destLng = (float) ($h?->destination?->longitude ?? $h?->destination_longitude);
        if ($pickupLat && $pickupLng && $destLat && $destLng && (float) ($h->quantity_kg ?? 0) > 0) {
            $km = $this->haversine($pickupLat, $pickupLng, $destLat, $destLng);
            $rateReference = app(HaulingRateCalculator::class)
                ->suggest($km, (float) $h->quantity_kg)['rate_per_kg'];
        }

        return view('haul-negotiations.room', compact('haulIntent', 'rateReference'));
    }

    public function logisticsInbox()
    {
        $user = Auth::user();
        if ($user->role !== 'logistics_partner') {
            abort(403);
        }

        $intents = HaulIntent::where('logistics_profile_id', $user->logisticsProfile?->id)
            ->with(['haulRequest.harvest.crop', 'haulRequest.farmer'])
            ->latest()
            ->get();

        return view('logistics.haul-negotiations', compact('intents'));
    }

    public function sendMessage(Request $request, HaulIntent $haulIntent)
    {
        $this->authorizeParty($haulIntent);

        if (in_array($haulIntent->status, ['accepted', 'declined'])) {
            return response()->json(['error' => 'This intent is closed. No further messages.'], 422);
        }

        $validated = $request->validate([
            'message_text' => ['required', 'string', 'max:2000'],
        ]);

        $msg = HaulIntentMessage::create([
            'haul_intent_id' => $haulIntent->id,
            'sender_id'      => Auth::id(),
            'message_text'   => $validated['message_text'],
        ]);

        $haulIntent->touch();
        $msg->load('sender');

        return response()->json(['message' => $msg]);
    }

    public function getMessages(Request $request, HaulIntent $haulIntent)
    {
        $this->authorizeParty($haulIntent);

        $sinceId = (int) $request->query('since_id', 0);

        $messages = $sinceId > 0
            ? $haulIntent->messages()->where('id', '>', $sinceId)->with('sender')->orderBy('id')->get()
            : $haulIntent->messages()->with('sender')->orderBy('id')->take(100)->get();

        return response()->json([
            'messages'              => $messages,
            'offer_rate'            => $haulIntent->offer_rate_php_per_kg,
            'counter_rate'          => $haulIntent->counter_rate_php_per_kg,
            'hauling_rate'          => $haulIntent->hauling_rate_php_per_kg,
            'status'                => $haulIntent->status,
        ]);
    }

    public function proposeRate(Request $request, HaulIntent $haulIntent)
    {
        $this->authorizeParty($haulIntent);

        if ($this->isFarmerSide()) {
            abort(403);
        }

        if (in_array($haulIntent->status, ['accepted', 'declined'])) {
            return back()->with('error', 'This intent is closed.');
        }

        $validated = $request->validate([
            'offer_rate_php_per_kg' => ['required', 'numeric', 'min:0.5', 'max:1000'],
        ]);

        $rate = $validated['offer_rate_php_per_kg'];

        $haulIntent->update([
            'offer_rate_php_per_kg' => $rate,
            'counter_rate_php_per_kg' => null,
            'status'                => 'pending',
        ]);

        $msg = HaulIntentMessage::create([
            'haul_intent_id' => $haulIntent->id,
            'sender_id'      => Auth::id(),
            'message_text'   => '[System Offer] Proposes hauling rate: ₱' . number_format((float) $rate, 2) . '/kg.',
        ]);

        self::notifyHaulRateOffer(
            $haulIntent->haulRequest->user_id,
            $haulIntent->logisticsProfile->company_name,
            (float) $rate,
        );

        $msg->load('sender');
        return response()->json([
            'message'      => $msg,
            'offer_rate'   => $rate,
            'counter_rate' => null,
            'status'       => $haulIntent->status,
        ]);
    }

    public function counterRate(Request $request, HaulIntent $haulIntent)
    {
        $this->authorizeParty($haulIntent);

        if (!$this->isFarmerSide()) {
            abort(403);
        }

        if (in_array($haulIntent->status, ['accepted', 'declined'])) {
            return back()->with('error', 'This intent is closed.');
        }

        $validated = $request->validate([
            'counter_rate_php_per_kg' => ['required', 'numeric', 'min:0.5', 'max:1000'],
        ]);

        $rate = $validated['counter_rate_php_per_kg'];

        $haulIntent->update([
            'counter_rate_php_per_kg' => $rate,
            'status'                  => 'pending',
        ]);

        $msg = HaulIntentMessage::create([
            'haul_intent_id' => $haulIntent->id,
            'sender_id'      => Auth::id(),
            'message_text'   => '[System Offer] Farmer counters with rate: ₱' . number_format((float) $rate, 2) . '/kg.',
        ]);

        self::notifyHaulCounterRate(
            $haulIntent->logisticsProfile->user_id,
            (float) $rate,
        );

        $msg->load('sender');
        return response()->json([
            'message'      => $msg,
            'offer_rate'   => $haulIntent->offer_rate_php_per_kg,
            'counter_rate' => $rate,
            'status'       => $haulIntent->status,
        ]);
    }

    public function agree(Request $request, HaulIntent $haulIntent)
    {
        $this->authorizeParty($haulIntent);

        if (in_array($haulIntent->status, ['accepted', 'declined'])) {
            return back()->with('error', 'This intent is closed.');
        }

        $rate = $haulIntent->counter_rate_php_per_kg ?? $haulIntent->offer_rate_php_per_kg;

        if (!$rate) {
            return back()->with('error', 'No rate has been proposed yet. Propose or counter a rate first.');
        }

        $isFarmer = $this->isFarmerSide();
        $logisticsName = $haulIntent->logisticsProfile?->company_name ?? 'Logistics partner';
        $farmerName = $haulIntent->haulRequest->farmer?->name ?? 'Farmer';

        $haulIntent->update([
            'hauling_rate_php_per_kg' => $rate,
            'status'                  => $isFarmer ? 'accepted' : 'agreed',
        ]);

        $msg = HaulIntentMessage::create([
            'haul_intent_id' => $haulIntent->id,
            'sender_id'      => Auth::id(),
            'message_text'   => $isFarmer
                ? "[System Message] {$farmerName} agreed to haul at ₱" . number_format((float) $rate, 2) . "/kg. Booking confirmed."
                : "[System Message] {$logisticsName} agreed to the farmer's rate of ₱" . number_format((float) $rate, 2) . "/kg.",
        ]);

        if ($isFarmer) {
            $haulRequest = $haulIntent->haulRequest;
            $haulRequest->update(['status' => 'booked']);
            $haulRequest->harvest->update(['status' => \App\Models\HarvestStatus::BOOKED]);

            HaulIntent::where('haul_request_id', $haulRequest->id)
                ->where('id', '!=', $haulIntent->id)
                ->where('status', 'pending')
                ->update(['status' => 'declined']);

            self::notifyHaulBookedViaAgreement(
                $haulIntent->logisticsProfile->user_id,
                (float) $rate,
            );
        } else {
            self::notifyHaulDealAgreed(
                $haulIntent->haulRequest->user_id,
                $logisticsName,
                (float) $rate,
            );
        }

        $msg->load('sender');
        return response()->json([
            'message'      => $msg,
            'hauling_rate' => $rate,
            'status'       => $haulIntent->status,
        ]);
    }
}
