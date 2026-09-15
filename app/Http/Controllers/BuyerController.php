<?php

namespace App\Http\Controllers;

use App\Models\Harvest;
use App\Models\HarvestStatus;
use App\Models\Negotiation;
use App\Models\PoolingJob;
use App\Models\PoolingJobStatus;
use Illuminate\Support\Facades\DB;

use App\Traits\Notifiable;
use Illuminate\Support\Facades\Auth;

/**
 * BuyerController
 *
 * Manages the Buyer workspace:
 * - Dashboard overview (active negotiations, recent posts)
 * - B2B Crop Board (cooperative-scoped or public independent posts)
 * - Initiate and manage negotiations with farmers
 * - Track incoming deliveries and confirm receipt
 */
class BuyerController extends Controller
{
    use Notifiable;

    /**
     * Buyer Dashboard — overview metrics and active negotiations.
     */
    public function dashboard()
    {
        $user = Auth::user();

        $activeNegotiations = Negotiation::where('buyer_id', $user->id)
            ->whereIn('status', ['OPEN', 'AGREED'])
            ->with(['farmer', 'harvest.crop', 'harvest.cropVariety'])
            ->latest()
            ->take(20)
            ->get();

        $completedDeals = Negotiation::where('buyer_id', $user->id)
            ->where('status', 'COMPLETED')
            ->count();

        $pendingConfirmations = PoolingJob::where('buyer_id', $user->id)
            ->where('status', 'awaiting_confirmation')
            ->with(['truck', 'harvests.crop', 'driver'])
            ->latest()
            ->take(20)
            ->get();

        $monthlySpent = (float) Negotiation::where('buyer_id', $user->id)
            ->where('status', 'COMPLETED')
            ->whereMonth('last_activity_at', now()->month)
            ->whereYear('last_activity_at', now()->year)
            ->sum(DB::raw('negotiated_price * negotiated_volume'));

        $monthlyKg = (float) Negotiation::where('buyer_id', $user->id)
            ->where('status', 'COMPLETED')
            ->whereMonth('last_activity_at', now()->month)
            ->whereYear('last_activity_at', now()->year)
            ->sum('negotiated_volume');

        $unreadMessagesCount = Negotiation::where('buyer_id', $user->id)
            ->whereIn('status', ['OPEN', 'AGREED'])
            ->whereColumn('last_activity_at', '>', 'buyer_last_read_at')
            ->count();

        return view('buyer.dashboard', [
            'activeNegotiations'    => $activeNegotiations,
            'completedDeals'        => $completedDeals,
            'pendingConfirmations'  => $pendingConfirmations,
            'monthlySpent'          => $monthlySpent,
            'monthlyKg'             => $monthlyKg,
            'unreadMessagesCount'   => $unreadMessagesCount,
        ]);
    }

    /**
     * B2B Crop Board — full paginated list of available harvests.
     * Scoped by buyer's affiliation:
     *   - Cooperative buyer → sees ONLY their cooperative's farmers' posts
     *   - Independent buyer → sees only independent farmer posts
     */
    public function cropBoard()
    {
        $buyer = Auth::user();

        // Include negotiating products so they appear grayed out on the crop board
        $posts = $this->scopedHarvestQuery(true)
            ->with(['farmer.farmerProfile', 'crop', 'cropVariety'])
            ->latest()
            ->paginate(12);

        // Map of negotiating harvest IDs (scoped to same filters as crop board)
        $negotiatingHarvestIds = [];
        $negotiationRoomMap = [];

        // Current buyer's own negotiations — use select() to avoid loading full models
        $buyerNegotiations = Negotiation::where('buyer_id', $buyer->id)
            ->whereIn('status', ['OPEN', 'AGREED'])
            ->select('harvest_id', 'id')
            ->get();

        foreach ($buyerNegotiations as $n) {
            $negotiatingHarvestIds[] = $n->harvest_id;
            $negotiationRoomMap[$n->harvest_id] = $n->id;
        }

        // All negotiating harvest IDs visible to this buyer (same scope as crop board)
        // Must pass true to include 'negotiating' status in the base query,
        // otherwise ->where('status', 'negotiating') contradicts the default
        // buyerAvailable() filter and returns zero rows.
        $allNegotiatingIds = $this->scopedHarvestQuery(true)
            ->where('status', 'negotiating')
            ->pluck('id')
            ->toArray();

        return view('buyer.crop-board', compact('posts', 'negotiatingHarvestIds', 'negotiationRoomMap', 'allNegotiatingIds'));
    }

    /**
     * Lightweight JSON snapshot for crop board polling.
     * Returns only IDs and statuses — not full post data.
     */
    public function cropBoardJson()
    {
        $buyer = Auth::user();

        $postIds = $this->scopedHarvestQuery(true)
            ->pluck('id')
            ->toArray();

        $allNegotiatingIds = $this->scopedHarvestQuery(true)
            ->where('status', 'negotiating')
            ->pluck('id')
            ->toArray();

        $buyerNegotiations = Negotiation::where('buyer_id', $buyer->id)
            ->whereIn('status', ['OPEN', 'AGREED'])
            ->pluck('harvest_id')
            ->toArray();

        return response()->json([
            'post_ids' => $postIds,
            'negotiating_ids' => $allNegotiatingIds,
            'my_negotiating_ids' => $buyerNegotiations,
            'count' => count($postIds),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Buyer's active negotiations list.
     */
    public function negotiations()
    {
        $user = Auth::user();
        $negotiations = Negotiation::where('buyer_id', $user->id)
            ->with(['farmer', 'harvest.crop', 'harvest.cropVariety'])
            ->latest('last_activity_at')
            ->paginate(20);
        return view('buyer.negotiations', compact('negotiations'));
    }

    /**
     * Buyer's incoming delivery tracking page.
     */
    public function tracking()
    {
        $user = Auth::user();

        // Multi-buyer: derive access via completed negotiations, not buyer_id (which is always null).
        $jobScope = function ($q) use ($user) {
            $q->whereHas('harvests', function ($hq) use ($user) {
                $hq->whereHas('negotiations', function ($nq) use ($user) {
                    $nq->where('buyer_id', $user->id)
                        ->where('status', \App\Models\NegotiationStatus::COMPLETED);
                });
            });
        };

        $activeDeliveries = PoolingJob::where($jobScope)
            ->where(function ($q) {
                $q->whereIn('status', ['in_progress', 'awaiting_confirmation'])
                  ->orWhere(function ($q2) {
                      $q2->where('status', 'confirmed')->whereNotNull('accepted_at');
                  });
            })
            ->with(['truck', 'harvests.crop', 'harvests.farmer', 'driver', 'logisticsProfile', 'latestTracking'])
            ->latest()
            ->take(20)
            ->get();

        $completedDeliveries = PoolingJob::where($jobScope)
            ->where('status', 'completed')
            ->with(['truck', 'harvests.crop', 'driver'])
            ->latest()
            ->take(10)
            ->get();

        return view('buyer.tracking', compact('activeDeliveries', 'completedDeliveries'));
    }

    /**
     * Confirm receipt of a delivered shipment.
     * Transitions job from 'awaiting_confirmation' → 'completed'.
     */
    public function confirmReceipt(PoolingJob $poolingJob)
    {
        $user = Auth::user();

        $isCoop = $user->role === 'logistics_partner'
            && $user->logisticsProfile
            && $user->logisticsProfile->isCooperative()
            && $user->logisticsProfile->is_verified;

        if ($user->role !== 'buyer' && !$isCoop) {
            abort(403, 'Only the buyer can confirm receipt for this delivery.');
        }

        if (!$poolingJob->isBuyer($user)) {
            abort(403, 'Only the buyer can confirm receipt for this delivery.');
        }

        if ($poolingJob->status !== PoolingJobStatus::AWAITING_CONFIRMATION) {
            return back()->with('error', 'This delivery is not awaiting your confirmation.');
        }

        $poolingJob->update(['status' => PoolingJobStatus::COMPLETED]);

        // Batch update all pivot entries with buyer_confirmed_at
        $harvestIds = $poolingJob->harvests->pluck('id')->toArray();
        $poolingJob->harvests()->updateExistingPivot($harvestIds, [
            'buyer_confirmed_at' => now(),
        ]);

        self::logAudit($user->id, 'buyer_confirmed_receipt', 'pooling_jobs', $poolingJob->id,
            "Buyer {$user->name} confirmed receipt for Route #{$poolingJob->id}.");

        // Notify logistics partner
        if ($poolingJob->logisticsProfile && $poolingJob->logisticsProfile->user_id) {
            self::notifyBuyerConfirmedReceipt(
                $poolingJob->logisticsProfile->user_id,
                $user->name,
                $poolingJob->id
            );
        }

        return back()->with('success', 'Delivery receipt confirmed! Thank you.')
            ->with('next_steps', [
                'title'   => 'Delivery complete',
                'message' => 'Delivery receipt confirmed! Thank you.',
                'steps'   => [
                    'This route is now completed and moved to your Completed deliveries.',
                    'Logistics partners will now handle invoicing and payment settlement.',
                    'Farmers on this route receive payment settlement next.',
                ],
                'cta' => ['label' => 'View Deliveries', 'url' => route('buyer.tracking')],
            ]);
    }

    /**
     * Show a single crop/harvest detail page (marketplace product style).
     */
    public function showCropDetail(Harvest $harvest)
    {
        $buyer = Auth::user();

        // Hide harvests that are no longer purchasable (sold, booked, assigned,
        // in progress, completed, cancelled) — prevents IDOR access to stale listings.
        if (!in_array($harvest->status, HarvestStatus::buyerAvailable(), true)
            && $harvest->status !== HarvestStatus::NEGOTIATING) {
            abort(404);
        }

        // Restrict cooperative buyers to their cooperative's harvests
        if ($buyer->affiliation_type === 'cooperative' && $buyer->cooperative_id) {
            $farmerCooperative = $harvest->farmer->cooperative_id ?? null;
            if ($farmerCooperative !== $buyer->cooperative_id) {
                abort(404);
            }
        }

        // If product is under negotiation by another buyer, block initiation
        if ($harvest->status === HarvestStatus::NEGOTIATING) {
            $myNegotiation = Negotiation::where('buyer_id', $buyer->id)
                ->where('harvest_id', $harvest->id)
                ->whereIn('status', ['OPEN', 'AGREED'])
                ->first();

            if (!$myNegotiation) {
                return back()->with('error', 'This product is currently under negotiation with another buyer.');
            }
        }

        $harvest->load(['farmer.farmerProfile', 'crop.category', 'cropVariety', 'destination']);

        $negotiation = Negotiation::where('buyer_id', $buyer->id)
            ->where('harvest_id', $harvest->id)
            ->whereIn('status', ['OPEN', 'AGREED'])
            ->first();

        return view('buyer.crop-detail', compact('harvest', 'negotiation'));
    }

    /**
     * Build a scoped harvest query based on buyer's cooperative affiliation.
     */
    private function scopedHarvestQuery(bool $includeNegotiating = false)
    {
        $user = Auth::user();

        $cooperativeId = null;
        if ($user->role === 'buyer' && $user->affiliation_type === 'cooperative') {
            $cooperativeId = $user->cooperative_id;
        } elseif ($user->role === 'logistics_partner' && $user->logisticsProfile && $user->logisticsProfile->isCooperative()) {
            $cooperativeId = $user->logisticsProfile->id;
        }

        $statuses = $includeNegotiating
            ? [...HarvestStatus::buyerAvailable(), 'negotiating']
            : HarvestStatus::buyerAvailable();

        if ($cooperativeId) {
            return Harvest::whereIn('status', $statuses)
                ->whereIn('visibility', ['buyers_only', 'both'])
                ->where('remaining_quantity_kg', '>', 0)
                ->whereHas('farmer.farmerProfile', function ($q) use ($cooperativeId) {
                    $q->where('is_verified', true)
                      ->where('affiliation_type', 'cooperative')
                      ->where('cooperative_id', $cooperativeId);
                });
        }

        return Harvest::whereIn('status', $statuses)
            ->whereIn('visibility', ['buyers_only', 'both'])
            ->where('remaining_quantity_kg', '>', 0)
            ->whereHas('farmer.farmerProfile', function ($q) {
                $q->where('is_verified', true)
                  ->where('affiliation_type', 'independent');
            });
    }
}
