<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Harvest;
use App\Models\HarvestStatus;
use App\Models\Negotiation;
use App\Models\NegotiationMessage;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * NegotiationController
 *
 * Manages the B2B Crop Negotiation Chat Rooms and deal-closing workflow.
 */
class NegotiationController extends Controller
{
    /**
     * Start a negotiation (usually initiated by a buyer clicking a product on Crop Board).
     */
    public function start(Request $request)
    {
        $request->validate([
            'harvest_id' => 'required|exists:harvests,id',
        ]);

        $buyer = Auth::user();
        $isCoopLogistics = $buyer->role === 'logistics_partner' 
            && $buyer->logisticsProfile 
            && $buyer->logisticsProfile->isCooperative();

        if ($buyer->role !== 'buyer' && !$isCoopLogistics) {
            abort(403, 'Only commercial buyers or cooperative logistics partners can initiate B2B negotiations.');
        }

        // Verify buyer account is verified
        if ($buyer->role === 'buyer') {
            $buyerProfile = $buyer->buyerProfile;
            if (!$buyerProfile || !$buyerProfile->is_verified) {
                return back()->with('error', 'Your buyer account must be verified before starting negotiations.');
            }
        } elseif ($isCoopLogistics) {
            if (!$buyer->logisticsProfile->is_verified) {
                return back()->with('error', 'Your logistics account must be verified before starting negotiations.');
            }
        }

        $harvest = Harvest::findOrFail($request->harvest_id);

        if (!in_array($harvest->status, HarvestStatus::BUYER_AVAILABLE)) {
            return back()->with('error', 'This product is no longer available for negotiation.');
        }

        // Check harvest has pickup coordinates
        if (is_null($harvest->latitude) || is_null($harvest->longitude)) {
            return back()->with('error', 'This product has no pickup coordinates and cannot be negotiated.');
        }

        // Avoid duplicate active negotiations for the same lot
        $existing = Negotiation::where('buyer_id', $buyer->id)
            ->where('harvest_id', $harvest->id)
            ->whereIn('status', ['OPEN', 'AGREED'])
            ->first();

        if ($existing) {
            return redirect()->route('negotiations.room', $existing->id)
                ->with('warning', 'You already have an ongoing negotiation for this product. Redirected to your existing conversation.');
        }

        // Mark harvest as under negotiation (keep visibility for partial sales)
        if (in_array($harvest->status, HarvestStatus::BUYER_AVAILABLE)) {
            $harvest->update([
                'status' => 'negotiating',
                'negotiation_locked_at' => now(),
            ]);
        }

        // Create new negotiation
        $negotiation = Negotiation::create([
            'buyer_id'          => $buyer->id,
            'farmer_id'         => $harvest->user_id,
            'harvest_id'        => $harvest->id,
            'negotiated_price'  => null,
            'negotiated_volume' => $harvest->remaining_quantity_kg ?? $harvest->quantity_kg,
            'status'            => 'OPEN',
        ]);

        // Post default greeting message
        NegotiationMessage::create([
            'negotiation_id' => $negotiation->id,
            'sender_id'      => $buyer->id,
            'message_text'   => "Hello! I am interested in your product #{$harvest->id} ({$harvest->crop_type}). Let's discuss pricing and volume.",
        ]);

        $negotiation->update(['last_activity_at' => now()]);

        Notification::create([
            'user_id'  => $negotiation->farmer_id,
            'title'    => 'New B2B Negotiation',
            'message'  => "{$buyer->name} is interested in your product #{$harvest->id} ({$harvest->crop_type}).",
            'link'     => route('negotiations.room', $negotiation->id),
            'type'     => 'negotiation_started',
            'category' => 'negotiation',
        ]);

        return redirect()->route('negotiations.room', $negotiation->id);
    }

    /**
     * Enter the negotiation room chat (accessible by both buyer and farmer).
     */
    public function room(Negotiation $negotiation)
    {
        $user = Auth::user();

        // Security check
        if ($negotiation->buyer_id !== $user->id && $negotiation->farmer_id !== $user->id) {
            abort(403, 'Unauthorized access to this negotiation room.');
        }

        // Mark messages as read for this user
        $column = $negotiation->buyer_id === $user->id ? 'buyer_last_read_at' : 'farmer_last_read_at';
        $negotiation->update([$column => now()]);

        $negotiation->load(['buyer', 'farmer', 'harvest.crop', 'harvest.cropVariety', 'messages.sender']);

        return view('negotiations.room', compact('negotiation'));
    }

    /**
     * Post a new message in the chat room.
     */
    public function sendMessage(Request $request, Negotiation $negotiation)
    {
        $user = Auth::user();

        if ($negotiation->buyer_id !== $user->id && $negotiation->farmer_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }

        if ($negotiation->status === 'COMPLETED') {
            return back()->with('error', 'This negotiation is closed. No further messages.');
        }

        $request->validate([
            'message_text' => 'required|string|max:1000',
        ]);

        $msg = NegotiationMessage::create([
            'negotiation_id' => $negotiation->id,
            'sender_id'      => $user->id,
            'message_text'   => $request->message_text,
        ]);

        $msg->load('sender');
        return response()->json(['message' => $msg]);
    }

    /**
     * Propose custom B2B unit price and volume terms.
     */
    public function proposeTerms(Request $request, Negotiation $negotiation)
    {
        $user = Auth::user();

        if ($negotiation->buyer_id !== $user->id && $negotiation->farmer_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }

        $maxVolume = $negotiation->harvest->remaining_quantity_kg ?? $negotiation->harvest->quantity_kg;
        $request->validate([
            'negotiated_price'  => 'required|numeric|min:0.01|max:999999.99',
            'negotiated_volume' => "required|numeric|min:0.01|max:{$maxVolume}",
        ]);

        // Soft price bounds check — warn if significantly outside baseline range
        $crop = $negotiation->harvest->crop;
        if ($crop && $crop->baseline_price_per_kg) {
            $baseline = (float) $crop->baseline_price_per_kg;
            $minAllowed = $baseline * 0.10;
            $maxAllowed = $baseline * 5.00;
            if ($request->negotiated_price < $minAllowed || $request->negotiated_price > $maxAllowed) {
                return back()->with('warning', 'Proposed price ₱' . number_format($request->negotiated_price, 2) . '/kg is significantly outside the expected range (₱' . number_format($minAllowed, 2) . ' – ₱' . number_format($maxAllowed, 2) . '). Please confirm your offer.');
            }
        }

        // Don't allow re-proposing if already AGREED or COMPLETED
        if (in_array($negotiation->status, ['AGREED', 'COMPLETED'])) {
            return back()->with('error', 'Terms are locked. Cannot propose new terms on a ' . $negotiation->status . ' negotiation.');
        }

        // Enforce negotiation rounds limit (max 10)
        $roundCount = NegotiationMessage::where('negotiation_id', $negotiation->id)
            ->where('message_text', 'LIKE', '[System Offer]%')
            ->count();
        if ($roundCount >= 10) {
            return back()->with('error', 'Maximum negotiation rounds reached (10). Accept the current offer or end the negotiation.');
        }

        $negotiation->update([
            'negotiated_price'  => $request->negotiated_price,
            'negotiated_volume' => $request->negotiated_volume,
            'status'            => 'OPEN',
            'last_activity_at'  => now(),
        ]);

        $formattedPrice = number_format($request->negotiated_price, 2);
        $formattedVolume = number_format($request->negotiated_volume);

        // System message update log in chat
        $sysMsg = NegotiationMessage::create([
            'negotiation_id' => $negotiation->id,
            'sender_id'      => $user->id,
            'message_text'   => "[System Offer] Proposes terms: ₱{$formattedPrice}/kg for {$formattedVolume} kg.",
        ]);

        if ($request->ajax()) {
            $sysMsg->load('sender');
            return response()->json([
                'message' => $sysMsg,
                'negotiated_price' => $request->negotiated_price,
                'negotiated_volume' => $request->negotiated_volume,
            ]);
        }

        return back()->with('success', 'Terms proposed successfully.');
    }

    /**
     * Agree to the proposed terms.
     */
    public function agreeTerms(Request $request, Negotiation $negotiation)
    {
        $user = Auth::user();

        if ($negotiation->buyer_id !== $user->id && $negotiation->farmer_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }

        if (is_null($negotiation->negotiated_price) || is_null($negotiation->negotiated_volume)) {
            return back()->with('error', 'Cannot agree. No terms have been proposed yet.');
        }

        if ($negotiation->status !== 'OPEN') {
            return back()->with('error', 'Cannot agree. Current status is ' . $negotiation->status . '.');
        }

        // Prevent self-agreement: the person who proposed the last terms cannot be the one who agrees
        $lastProposal = NegotiationMessage::where('negotiation_id', $negotiation->id)
            ->where('message_text', 'LIKE', '[System Offer]%')
            ->latest()
            ->first();

        if ($lastProposal && $lastProposal->sender_id === $user->id) {
            return back()->with('error', 'You proposed these terms. The other party must agree first.');
        }

        $negotiation->update([
            'status'           => 'AGREED',
            'last_activity_at' => now(),
        ]);

        $sysMsg = NegotiationMessage::create([
            'negotiation_id' => $negotiation->id,
            'sender_id'      => $user->id,
            'message_text'   => "[System Message] Agreed to the proposed terms. Ready to finalize drop-off.",
        ]);

        if ($request->ajax()) {
            $sysMsg->load('sender');
            return response()->json(['message' => $sysMsg, 'status' => 'AGREED']);
        }

        return back()->with('success', 'You agreed to the proposed terms.');
    }

    /**
     * Finalize the deal by submitting the drop-off coordinates (only buyer does this).
     */
    public function finalizeDeal(Request $request, Negotiation $negotiation)
    {
        $buyer = Auth::user();

        if ($negotiation->buyer_id !== $buyer->id) {
            abort(403, 'Only the buyer can finalize this deal with drop-off details.');
        }

        if ($negotiation->status !== 'AGREED') {
            return back()->with('error', 'Cannot finalize. You must both agree to terms first.');
        }

        // Check buyer account is active
        if ($buyer->status !== 'active') {
            return back()->with('error', 'Your account is not active. Cannot finalize deal.');
        }

        $request->validate([
            'destination_address'   => 'required|string|max:500',
            'destination_latitude'  => 'required|numeric|between:-90,90',
            'destination_longitude' => 'required|numeric|between:-180,180',
        ]);

        $harvest = $negotiation->harvest;

        // Check negotiated volume doesn't exceed remaining harvest quantity (quick check before lock)
        if ((float) $negotiation->negotiated_volume > (float) ($harvest->remaining_quantity_kg ?? $harvest->quantity_kg)) {
            return back()->with('error', 'Negotiated volume exceeds remaining harvest quantity.');
        }

        DB::transaction(function () use ($harvest, $negotiation, $request, $buyer) {
            // Pessimistic lock — must be INSIDE the transaction to prevent double-sell race condition
            $locked = Harvest::lockForUpdate()->find($harvest->id);

            // Re-check remaining quantity under lock
            $remaining = (float) ($locked->remaining_quantity_kg ?? $locked->quantity_kg);
            if ((float) $negotiation->negotiated_volume > $remaining) {
                return back()->with('error', 'Negotiated volume exceeds remaining harvest quantity. Another deal may have been finalized.');
            }

            $newRemaining = round($remaining - (float) $negotiation->negotiated_volume, 2);

            // Update negotiation with deal-specific destination and finalize
            $negotiation->update([
                'status'              => 'COMPLETED',
                'destination_address' => $request->destination_address,
                'destination_latitude' => $request->destination_latitude,
                'destination_longitude' => $request->destination_longitude,
                'last_activity_at'    => now(),
            ]);

            // Determine new harvest status and visibility based on remaining quantity
            if ($newRemaining <= 0) {
                // Fully sold — visible to logistics partners
                $locked->update([
                    'remaining_quantity_kg' => 0,
                    'status'                => 'sold',
                    'visibility'            => 'logistics_only',
                    'destination_address'   => $request->destination_address,
                    'destination_latitude'  => $request->destination_latitude,
                    'destination_longitude' => $request->destination_longitude,
                ]);
            } else {
                // Partially sold — harvest stays visible to buyers for the remaining quantity
                $isIndependent = $locked->user?->farmerProfile?->affiliation_type === 'independent';
                $locked->update([
                    'remaining_quantity_kg' => $newRemaining,
                    'status'                => 'partially_sold',
                    'visibility'            => $isIndependent ? 'buyers_only' : 'both',
                ]);
            }

            NegotiationMessage::create([
                'negotiation_id' => $negotiation->id,
                'sender_id'      => $buyer->id,
                'message_text'   => "[System Message] Deal finalized! Drop-off submitted: {$request->destination_address}" .
                    ($newRemaining > 0 ? " ({$newRemaining} kg remaining available)" : ''),
            ]);

            AuditLog::create([
                'admin_id'    => $buyer->id,
                'action'      => $newRemaining <= 0 ? 'harvest_fully_sold' : 'harvest_partially_sold',
                'target_type' => 'harvest',
                'target_id'   => $locked->id,
                'notes'       => "Buyer {$buyer->name} purchased {$negotiation->negotiated_volume}kg at ₱{$negotiation->negotiated_price}/kg. Remaining: {$newRemaining}kg.",
            ]);
        });

        $msg = $negotiation->harvest->fresh()->status === 'sold'
            ? 'B2B deal closed! Harvest fully sold. Now visible to logistics partners.'
            : 'B2B deal closed! Remaining quantity still available on the crop board.';
        return redirect()->route('buyer.negotiations')->with('success', $msg);
    }

    /**
     * Cancel a deal (only allowed if harvest is not assigned to a confirmed/in-progress pooling job).
     * Auto-detaches from pending pooling jobs.
     */
    public function cancelDeal(Request $request, Negotiation $negotiation)
    {
        $user = Auth::user();

        if ($negotiation->buyer_id !== $user->id && $negotiation->farmer_id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }

        if (!in_array($negotiation->status, ['OPEN', 'AGREED'])) {
            return back()->with('error', 'Cannot cancel this negotiation. Current status: ' . $negotiation->status);
        }

        $harvest = Harvest::find($negotiation->harvest_id);

        // Block if assigned to confirmed/in_progress jobs (cargo physically loaded)
        $activeJobs = $harvest->poolingJobs()->where('pooling_jobs.status', 'in', ['confirmed', 'in_progress'])->exists();
        if ($activeJobs) {
            return back()->with('error', 'Cannot cancel — harvest is assigned to an active logistics route that is already confirmed.');
        }

        DB::transaction(function () use ($negotiation, $harvest) {
            // Pessimistic lock — prevent race condition with concurrent cancel/finalize
            $locked = Harvest::lockForUpdate()->find($harvest->id);

            $negotiation->update([
                'status'           => 'CANCELLED',
                'last_activity_at' => now(),
            ]);

            NegotiationMessage::create([
                'negotiation_id' => $negotiation->id,
                'sender_id'      => Auth::id(),
                'message_text'   => '[System Message] Negotiation cancelled.',
            ]);

            // Restore harvest status — check if there are other completed deals
            if ($locked->status === 'negotiating') {
                $hasCompletedDeals = $locked->negotiations()
                    ->where('id', '!=', $negotiation->id)
                    ->where('status', 'COMPLETED')
                    ->exists();

                if ($hasCompletedDeals) {
                    // Other deals exist — revert to partially_sold so remaining quantity stays visible to buyers
                    $isIndependent = $locked->user?->farmerProfile?->affiliation_type === 'independent';
                    $locked->update([
                        'status'     => 'partially_sold',
                        'visibility' => $isIndependent ? 'buyers_only' : 'both',
                    ]);
                } else {
                    // No other deals — revert to active
                    $locked->update(['status' => 'active']);
                }
            }

            // Auto-detach from pending pooling jobs
            $pendingJobs = $locked->poolingJobs()->where('status', 'pending')->get();
            foreach ($pendingJobs as $job) {
                $job->harvests()->detach($locked->id);
                $job->load('harvests');
                if ($job->harvests->isEmpty()) {
                    $job->status = 'cancelled';
                    $job->save();
                    $job->truck?->update(['status' => 'available']);
                } else {
                    $job->total_kg = $job->harvests->sum('pivot.quantity_kg');
                    $job->farm_count = $job->harvests->count();
                    $job->save();
                }

                Notification::create([
                    'user_id' => $job->logisticsProfile->user_id,
                    'title'   => 'Deal Cancelled — Harvest Removed from Route',
                    'message' => "A deal for harvest #{$locked->id} ({$locked->crop_type}) was cancelled. Route #{$job->id} has been updated.",
                    'link'    => route('pooling.index'),
                ]);
            }
        });

        return back()->with('success', 'Negotiation cancelled.');
    }

    /**
     * Farmer-specific incoming crop negotiations list.
     */
    public function farmerNegotiations()
    {
        $user = Auth::user();
        if ($user->role !== 'farmer') {
            abort(403, 'Farmer access only.');
        }

        return redirect()->route('dashboard');
    }

    /**
     * API: return current user's negotiations as JSON (for widget popup).
     */
    public function listJson()
    {
        $user = Auth::user();
        $userId = $user->id;

        $negotiations = Negotiation::where(function ($q) use ($userId) {
                $q->where('buyer_id', $userId)
                  ->orWhere('farmer_id', $userId);
            })
            ->with([
                'buyer',
                'farmer',
                'harvest.crop',
                'harvest.cropVariety',
                'messages' => fn($q) => $q->latest()->take(1),
            ])
            ->addSelect(['unread_count' => NegotiationMessage::selectRaw('COUNT(*)')
                ->whereColumn('negotiation_id', 'negotiations.id')
                ->where('sender_id', '!=', $userId)
                ->whereRaw('created_at > COALESCE(
                    CASE WHEN ? = negotiations.buyer_id THEN negotiations.buyer_last_read_at
                         ELSE negotiations.farmer_last_read_at
                    END,
                    "1970-01-01 00:00:00"
                )', [$userId])
            ])
            ->latest('last_activity_at')
            ->get();

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

    /**
     * API: return negotiation messages as JSON (for polling).
     * Accepts optional `since_id` param to only return newer messages.
     */
    public function getMessages(Request $request, Negotiation $negotiation)
    {
        $user = Auth::user();
        if ($negotiation->buyer_id !== $user->id && $negotiation->farmer_id !== $user->id) {
            abort(403);
        }

        $sinceId = (int) $request->query('since_id', 0);

        $messages = $sinceId > 0
            ? $negotiation->messages()->where('id', '>', $sinceId)->with('sender')->orderBy('id')->get()
            : $negotiation->messages()->with('sender')->orderBy('id')->get();

        return response()->json([
            'messages'          => $messages,
            'negotiated_price'  => $negotiation->negotiated_price,
            'negotiated_volume' => $negotiation->negotiated_volume,
            'status'            => $negotiation->status,
        ]);
    }
}
