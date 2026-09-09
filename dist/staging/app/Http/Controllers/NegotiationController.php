<?php

namespace App\Http\Controllers;

use App\Actions\FinalizeDealAction;
use App\Exceptions\NegotiationException;
use App\Http\Requests\StartNegotiationRequest;
use App\Http\Requests\SendMessageRequest;
use App\Http\Requests\ProposeTermsRequest;
use App\Http\Requests\FinalizeDealRequest;
use App\Models\Harvest;
use App\Models\HarvestStatus;
use App\Models\HaulIntent;
use App\Models\Negotiation;
use App\Models\NegotiationStatus;
use App\Models\NegotiationMessage;
use App\Models\User;

use App\Services\NegotiationService;
use App\Traits\Notifiable;
use App\Traits\GeometryHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NegotiationController extends Controller
{
    use Notifiable, GeometryHelper;

    public function __construct(
        protected NegotiationService $negotiationService,
    ) {}

    public function start(StartNegotiationRequest $request)
    {
        $validated = $request->validated();
        $buyer = Auth::user();

        if (!$buyer->relationLoaded('logisticsProfile')) {
            $buyer->load('logisticsProfile');
        }

        try {
            $negotiation = $this->negotiationService->startNegotiation(
                Harvest::findOrFail($validated['harvest_id']),
                $buyer,
                $validated['offered_price'] ?? 0,
                $validated['message'] ?? null,
            );
        } catch (NegotiationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('negotiations.room', $negotiation->id);
    }

    public function room(Negotiation $negotiation)
    {
        $user = Auth::user();

        if ($user->role === 'farmer' && $negotiation->farmer_id === $user->id) {
            return redirect()->route('farmer.deal-room', $negotiation->id);
        }

        $this->authorize('view', $negotiation);

        $column = $negotiation->buyer_id === $user->id ? 'buyer_last_read_at' : 'farmer_last_read_at';
        $negotiation->update([$column => now()]);

        $negotiation->load(['buyer.logisticsProfile', 'farmer', 'harvest.crop', 'harvest.cropVariety', 'messages.sender']);

$haulDistanceKm = null;
        $rateReference  = null;
        $h = $negotiation->harvest;
        $pickupLat = (float) ($h->latitude ?? $h->farmer?->farmerProfile?->latitude);
        $pickupLng = (float) ($h->longitude ?? $h->farmer?->farmerProfile?->longitude);
        $destLat = (float) ($negotiation->destination_latitude ?? $h->destination?->latitude ?? $h->destination_latitude ?? $negotiation->buyer?->logisticsProfile?->latitude);
        $destLng = (float) ($negotiation->destination_longitude ?? $h->destination?->longitude ?? $h->destination_longitude ?? $negotiation->buyer?->logisticsProfile?->longitude);
        if ($pickupLat && $pickupLng && $destLat && $destLng) {
            $haulDistanceKm = round($this->haversine($pickupLat, $pickupLng, $destLat, $destLng), 2);
            $totalKg = (float) ($negotiation->negotiated_volume ?? $h->quantity_kg ?? 0);
            if ($totalKg > 0) {
                $rateReference = app(\App\Services\HaulingRateCalculator::class)
                    ->suggest($haulDistanceKm, $totalKg)['rate_per_kg'];
            }
        }

        return view('negotiations.room', compact('negotiation', 'haulDistanceKm', 'rateReference'));
    }

    public function sendMessage(SendMessageRequest $request, Negotiation $negotiation)
    {
        $user = Auth::user();
        $this->authorize('view', $negotiation);

        if (in_array($negotiation->status, [NegotiationStatus::COMPLETED, NegotiationStatus::CANCELLED])) {
            return back()->with('error', 'This negotiation is closed. No further messages.');
        }

        $validated = $request->validated();

        $msg = NegotiationMessage::create([
            'negotiation_id' => $negotiation->id,
            'sender_id'      => $user->id,
            'message_text'   => $validated['message_text'],
        ]);

        $negotiation->update(['last_activity_at' => now()]);

        $msg->load('sender');
        return response()->json(['message' => $msg]);
    }

    public function proposeTerms(ProposeTermsRequest $request, Negotiation $negotiation)
    {
        $user = Auth::user();
        $this->authorize('update', $negotiation);

        $validated = $request->validated();

        $haulRate = isset($validated['hauling_rate_per_kg']) && $validated['hauling_rate_per_kg'] !== '' && $validated['hauling_rate_per_kg'] !== null
            ? (float) $validated['hauling_rate_per_kg']
            : null;

        try {
            $result = $this->negotiationService->proposeTerms(
                $negotiation,
                $user,
                (float) $validated['negotiated_price'],
                (float) $validated['negotiated_volume'],
                $haulRate,
            );
        } catch (NegotiationException $e) {
            return $request->ajax()
                ? response()->json(['error' => $e->getMessage()], 422)
                : back()->with('error', $e->getMessage());
        }

        if ($request->ajax()) {
            $result['message']->load('sender');
            return response()->json($result);
        }

        return back()->with('success', 'Terms proposed successfully.');
    }

    public function agreeTerms(Request $request, Negotiation $negotiation)
    {
        $user = Auth::user();
        $this->authorize('update', $negotiation);

        try {
            $this->negotiationService->agreeTerms($negotiation, $user);
        } catch (NegotiationException $e) {
            return back()->with('error', $e->getMessage());
        }

        if ($request->ajax()) {
            $sysMsg = NegotiationMessage::where('negotiation_id', $negotiation->id)
                ->latest()->first();
            $sysMsg->load('sender');
            return response()->json(['message' => $sysMsg, 'status' => 'AGREED']);
        }

        return back()->with('success', 'You agreed to the proposed terms.');
    }

    public function finalizeDeal(FinalizeDealRequest $request, Negotiation $negotiation)
    {
        $buyer = Auth::user();

        if ($negotiation->buyer_id !== $buyer->id) {
            abort(403, 'Only the buyer can finalize this deal with drop-off details.');
        }

        if ($buyer->status !== 'active') {
            return back()->with('error', 'Your account is not active. Cannot finalize deal.');
        }

        try {
            $result = app(FinalizeDealAction::class)->execute($negotiation, $buyer, $request->validated());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $freshHarvest = $negotiation->fresh()->harvest;
        $fullySold = $freshHarvest->status === HarvestStatus::SOLD;
        $msg = $fullySold
            ? 'B2B deal closed! Harvest fully sold. Now visible to logistics partners.'
            : 'B2B deal closed! Remaining quantity still available on the crop board.';

        $steps = $fullySold
            ? ['The chat room is now locked to read-only.', 'Your purchase is now visible to logistics partners for route planning.', 'Once a route is planned, track pickup and delivery under your Deliveries page.']
            : ['The chat room is now locked to read-only.', 'The remaining harvest quantity is still available on the crop board.', 'Your purchased quantity will be routed to you once a logistics partner plans a route.'];

        $cropLabel = $freshHarvest->crop_type;
        $isCoopBuyer = $buyer->role === 'logistics_partner'
            && $buyer->logisticsProfile
            && $buyer->logisticsProfile->isCooperative();

        if ($isCoopBuyer) {
            self::notifyDealFinalizedToCoop($buyer->id, $freshHarvest->id, $cropLabel);
        }
        self::notifyDealFinalizedToFarmer($negotiation->farmer_id, $freshHarvest->id, $cropLabel);

        return redirect()->route('buyer.negotiations')
            ->with('success', $msg)
            ->with('next_steps', [
                'title'   => 'Deal finalized',
                'message' => $msg,
                'steps'   => $steps,
                'cta'     => ['label' => 'Go to Deliveries', 'url' => route('buyer.tracking')],
            ]);
    }

    public function cancelDeal(Request $request, Negotiation $negotiation)
    {
        $user = Auth::user();
        $this->authorize('update', $negotiation);

        try {
            $this->negotiationService->cancelDeal($negotiation, $user);
        } catch (NegotiationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Negotiation cancelled.');
    }

    public function farmerNegotiations()
    {
        $user = Auth::user();
        if ($user->role !== 'farmer') {
            abort(403, 'Farmer access only.');
        }

        $negotiations = Negotiation::where('farmer_id', $user->id)
            ->with(['buyer', 'harvest.crop', 'harvest.cropVariety'])
            ->latest('last_activity_at')
            ->paginate(20);

        $haulIntents = HaulIntent::whereHas('haulRequest', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->with(['haulRequest.harvest.crop', 'logisticsProfile.user'])
            ->latest('updated_at')
            ->get();

        return view('farmers.negotiations', compact('negotiations', 'haulIntents'));
    }

    public function dealRoom(Negotiation $negotiation)
    {
        $user = Auth::user();
        if ($user->role !== 'farmer' || $negotiation->farmer_id !== $user->id) {
            abort(403, 'Farmer access only.');
        }

        $column = $negotiation->buyer_id === $user->id ? 'buyer_last_read_at' : 'farmer_last_read_at';
        $negotiation->update([$column => now()]);

        $negotiation->load([
            'buyer.buyerProfile',
            'buyer.logisticsProfile',
            'messages.sender',
            'harvest.crop',
            'harvest.cropVariety',
            'harvest.destination',
            'harvest.farmer.farmerProfile',
        ]);

        $haulRequest = $negotiation->haulRequests()->latest()->first();

        $haulIntents = HaulIntent::whereHas('haulRequest', function ($q) use ($negotiation) {
                $q->where('negotiation_id', $negotiation->id);
            })
            ->with(['haulRequest.harvest.crop', 'logisticsProfile.user'])
            ->latest('updated_at')
            ->get();

        return view('deals.deal-room', compact('negotiation', 'haulRequest', 'haulIntents'));
    }

    public function listJson()
    {
        $user = Auth::user();
        $negotiations = $this->negotiationService->getActiveNegotiationsForUser($user);

        $items = $negotiations->map(function ($n) use ($user) {
            $counterpart = $n->buyer_id === $user->id ? $n->farmer : $n->buyer;
            return [
                'type'              => 'crop',
                'id'                => $n->id,
                'crop'              => $n->harvest?->crop?->name ?? $n->harvest?->crop_type ?? 'Unknown',
                'variety'           => $n->harvest?->cropVariety?->name ?? $n->harvest?->variety ?? '',
                'lot'               => $n->harvest_id,
                'counterpart_name'  => $counterpart?->name ?? '—',
                'counterpart_role'  => $counterpart?->role ?? '',
                'status'            => $n->status,
                'price'             => $n->negotiated_price,
                'volume'            => $n->negotiated_volume,
                'last_activity'     => $n->last_activity_at?->diffForHumans(),
                'last_activity_at'  => $n->last_activity_at,
                'url'               => $user->role === 'farmer'
                    ? route('farmer.deal-room', $n->id)
                    : route('negotiations.room', $n->id),
                'is_buyer'          => $n->buyer_id === $user->id,
                'unread_count'      => (int) ($n->unread_count ?? 0),
            ];
        });

        $haulItems = [];
        if ($user->role === 'farmer') {
            $intents = HaulIntent::whereHas('haulRequest', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                })
                ->with(['haulRequest.harvest.crop', 'logisticsProfile.user'])
                ->latest('updated_at')
                ->get();
            foreach ($intents as $intent) {
                $haulItems[] = $this->haulFeedItem($intent, $intent->logisticsProfile?->user);
            }
        } elseif ($user->role === 'logistics_partner' && $user->logisticsProfile) {
            $intents = HaulIntent::where('logistics_profile_id', $user->logisticsProfile->id)
                ->with(['haulRequest.harvest.crop', 'haulRequest.farmer'])
                ->latest('updated_at')
                ->get();
            foreach ($intents as $intent) {
                $haulItems[] = $this->haulFeedItem($intent, $intent->haulRequest?->farmer);
            }
        }

        $items = $items->concat($haulItems)
            ->sortByDesc('last_activity_at')
            ->values()
            ->map(function ($item) {
                unset($item['last_activity_at']);
                return $item;
            });

        return response()->json(['negotiations' => $items]);
    }

    private function haulFeedItem(HaulIntent $intent, ?User $counterpart): array
    {
        $harvest = $intent->haulRequest?->harvest;
        $rate = $intent->hauling_rate_php_per_kg ?? $intent->currentRate();

        return [
            'type'              => 'haul',
            'id'                => $intent->id,
            'crop'              => $harvest?->crop?->name ?? $harvest?->crop_type ?? 'Haul',
            'variety'           => '',
            'lot'               => $intent->haul_request_id,
            'counterpart_name'  => $counterpart?->name ?? '—',
            'counterpart_role'  => 'logistics_partner',
            'status'            => $intent->status,
            'price'             => null,
            'volume'            => $harvest?->remaining_quantity_kg ?? $harvest?->quantity_kg,
            'rate_label'        => $rate ? '₱' . number_format((float) $rate, 2) . '/kg' : '',
            'last_activity'     => $intent->updated_at?->diffForHumans(),
            'last_activity_at'  => $intent->updated_at,
            'url'               => route('haul-negotiations.room', $intent->id),
            'is_buyer'          => false,
            'unread_count'      => 0,
        ];
    }

    public function getMessages(Request $request, Negotiation $negotiation)
    {
        $user = Auth::user();
        $this->authorize('view', $negotiation);

        $sinceId = (int) $request->query('since_id', 0);

        $messages = $sinceId > 0
            ? $negotiation->messages()->where('id', '>', $sinceId)->with('sender')->orderBy('id')->get()
            : $negotiation->messages()->with('sender')->orderBy('id')->take(100)->get();

        return response()->json([
            'messages'           => $messages,
            'negotiated_price'   => $negotiation->negotiated_price,
            'negotiated_volume'  => $negotiation->negotiated_volume,
            'hauling_rate_per_kg' => $negotiation->hauling_rate_per_kg,
            'status'             => $negotiation->status,
        ]);
    }
}
