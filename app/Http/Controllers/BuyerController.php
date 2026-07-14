<?php

namespace App\Http\Controllers;

use App\Models\Harvest;
use App\Models\HarvestStatus;
use App\Models\Negotiation;
use App\Models\PoolingJob;
use Illuminate\Http\Request;
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
            ->get();

        $completedDeals = Negotiation::where('buyer_id', $user->id)
            ->where('status', 'COMPLETED')
            ->count();

        // Scoped crop board preview (6 items)
        $recentPosts = $this->scopedHarvestQuery()
            ->with(['farmer.farmerProfile', 'crop', 'cropVariety'])
            ->latest()
            ->take(6)
            ->get();

       
        $pendingConfirmations = PoolingJob::where('buyer_id', $user->id)
            ->where('status', 'awaiting_confirmation')
            ->with(['truck', 'harvests.crop', 'driver'])
            ->latest()
            ->get();

        return view('buyer.dashboard', [
            'activeNegotiations'    => $activeNegotiations,
            'completedDeals'        => $completedDeals,
            'recentPosts'           => $recentPosts,
            'pendingConfirmations'  => $pendingConfirmations,
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
        $posts = $this->scopedHarvestQuery()
            ->orWhere('status', 'negotiating')
            ->with(['farmer.farmerProfile', 'crop', 'cropVariety'])
            ->latest()
            ->paginate(12);

        // Map of ALL negotiating harvest IDs (for any buyer, not just current)
        $negotiatingHarvestIds = [];
        $negotiationRoomMap = [];

        // Current buyer's own negotiations
        Negotiation::where('buyer_id', $buyer->id)
            ->whereIn('status', ['OPEN', 'AGREED'])
            ->each(function ($n) use (&$negotiatingHarvestIds, &$negotiationRoomMap) {
                $negotiatingHarvestIds[] = $n->harvest_id;
                $negotiationRoomMap[$n->harvest_id] = $n->id;
            });

        // All negotiating harvest IDs (to show grayed out to other buyers)
        $allNegotiatingIds = Harvest::where('status', 'negotiating')
            ->pluck('id')
            ->toArray();

        return view('buyer.crop-board', compact('posts', 'negotiatingHarvestIds', 'negotiationRoomMap', 'allNegotiatingIds'));
    }

    /**
     * Buyer's active negotiations list.
     */
    public function negotiations()
    {
        return redirect()->route('dashboard');
    }

    /**
     * Buyer's incoming delivery tracking page.
     */
    public function tracking()
    {
        $user = Auth::user();

        $activeDeliveries = PoolingJob::where('buyer_id', $user->id)
            ->whereIn('status', ['in_progress', 'awaiting_confirmation'])
            ->with(['truck', 'harvests.crop', 'harvests.farmer', 'driver', 'logisticsProfile', 'latestTracking'])
            ->latest()
            ->get();

        $completedDeliveries = PoolingJob::where('buyer_id', $user->id)
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

        if ($poolingJob->buyer_id !== $user->id) {
            abort(403, 'Only the buyer can confirm receipt for this delivery.');
        }

        if ($poolingJob->status !== 'awaiting_confirmation') {
            return back()->with('error', 'This delivery is not awaiting your confirmation.');
        }

        $poolingJob->update(['status' => 'completed']);

        // Mark timestamp on all pivot entries
        foreach ($poolingJob->harvests as $harvest) {
            $poolingJob->harvests()->updateExistingPivot($harvest->id, [
                'buyer_confirmed_at' => now(),
            ]);
        }

        \App\Models\AuditLog::create([
            'admin_id'    => $user->id,
            'action'      => 'buyer_confirmed_receipt',
            'target_type' => 'pooling_jobs',
            'target_id'   => $poolingJob->id,
            'notes'       => "Buyer {$user->name} confirmed receipt for Route #{$poolingJob->id}.",
        ]);

        // Notify logistics partner
        if ($poolingJob->logisticsProfile && $poolingJob->logisticsProfile->user_id) {
            \App\Models\Notification::create([
                'user_id' => $poolingJob->logisticsProfile->user_id,
                'title'   => 'Buyer Confirmed Receipt',
                'message' => "Buyer {$user->name} confirmed receipt for Route #{$poolingJob->id}.",
                'link'    => route('pooling.cost-ledger', $poolingJob),
            ]);
        }

        return back()->with('success', 'Delivery receipt confirmed! Thank you.');
    }

    /**
     * Show a single crop/harvest detail page (marketplace product style).
     */
    public function showCropDetail(Harvest $harvest)
    {
        $buyer = Auth::user();

        // If product is under negotiation by another buyer, block initiation
        if ($harvest->status === 'negotiating') {
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
    private function scopedHarvestQuery()
    {
        $user = Auth::user();

        $cooperativeId = null;
        if ($user->role === 'buyer' && $user->affiliation_type === 'cooperative') {
            $cooperativeId = $user->cooperative_id;
        } elseif ($user->role === 'logistics_partner' && $user->logisticsProfile && $user->logisticsProfile->isCooperative()) {
            $cooperativeId = $user->logisticsProfile->id;
        }

        if ($cooperativeId) {
            return Harvest::whereIn('status', HarvestStatus::BUYER_AVAILABLE)
                ->whereIn('visibility', ['buyers_only', 'both'])
                ->where('remaining_quantity_kg', '>', 0)
                ->whereHas('farmer.farmerProfile', function ($q) use ($cooperativeId) {
                    $q->where('is_verified', true)
                      ->where('affiliation_type', 'cooperative')
                      ->where('cooperative_id', $cooperativeId);
                });
        }

        return Harvest::whereIn('status', HarvestStatus::BUYER_AVAILABLE)
            ->whereIn('visibility', ['buyers_only', 'both'])
            ->where('remaining_quantity_kg', '>', 0)
            ->whereHas('farmer.farmerProfile', function ($q) {
                $q->where('is_verified', true)
                  ->where('affiliation_type', 'independent');
            });
    }
}
