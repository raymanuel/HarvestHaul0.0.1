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
use App\Models\Negotiation;
use App\Models\NegotiationStatus;
use App\Models\NegotiationMessage;
use App\Services\Darfo12Service;
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
        $this->authorize('view', $negotiation);

        $column = $negotiation->buyer_id === $user->id ? 'buyer_last_read_at' : 'farmer_last_read_at';
        $negotiation->update([$column => now()]);

        $negotiation->load(['buyer.logisticsProfile', 'farmer', 'harvest.crop', 'harvest.cropVariety', 'messages.sender']);

        $cropName = $negotiation->harvest->crop->name ?? $negotiation->harvest->crop_type;
        $marketPrice = $cropName ? app(Darfo12Service::class)->getLatestCropPrice($cropName) : null;

        $haulDistanceKm = null;
        $h = $negotiation->harvest;
        $pickupLat = (float) ($h->latitude ?? $h->farmer?->farmerProfile?->latitude);
        $pickupLng = (float) ($h->longitude ?? $h->farmer?->farmerProfile?->longitude);
        $destLat = (float) ($h->destination_latitude ?? $negotiation->buyer?->logisticsProfile?->latitude);
        $destLng = (float) ($h->destination_longitude ?? $negotiation->buyer?->logisticsProfile?->longitude);
        if ($pickupLat && $pickupLng && $destLat && $destLng) {
            $haulDistanceKm = round($this->haversine($pickupLat, $pickupLng, $destLat, $destLng), 2);
        }

        return view('negotiations.room', compact('negotiation', 'marketPrice', 'haulDistanceKm'));
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

        try {
            $result = $this->negotiationService->proposeTerms(
                $negotiation,
                $user,
                (float) $validated['negotiated_price'],
                (float) $validated['negotiated_volume'],
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
        return view('farmers.negotiations', compact('negotiations'));
    }

    public function listJson()
    {
        $user = Auth::user();
        $negotiations = $this->negotiationService->getActiveNegotiationsForUser($user);

        return response()->json([
            'negotiations' => $negotiations->map(function ($n) use ($user) {
                $counterpart = $n->buyer_id === $user->id ? $n->farmer : $n->buyer;
                return [
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
                    'url'               => route('negotiations.room', $n->id),
                    'is_buyer'          => $n->buyer_id === $user->id,
                    'unread_count'      => (int) ($n->unread_count ?? 0),
                ];
            }),
        ]);
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
            'messages'          => $messages,
            'negotiated_price'  => $negotiation->negotiated_price,
            'negotiated_volume' => $negotiation->negotiated_volume,
            'status'            => $negotiation->status,
        ]);
    }
}
