<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Coop\LocationMonitoringController;
use App\Models\BuyerOrder;
use App\Models\BuyerOrderItem;
use App\Models\BuyerProfile;
use App\Models\Crop;
use App\Models\CropAvailability;
use App\Models\CropGrade;
use App\Models\Notification;
use App\Models\User;
use App\Models\UserRole;
use App\Services\Matching\OrderMatchingService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function browse(Request $request, OrderMatchingService $matcher)
    {
        $filters = $request->validate([
            'crop_id'       => 'nullable|integer|exists:crops,id',
            'crop_grade_id' => 'nullable|integer|exists:crop_grades,id',
            'min_kg'        => 'nullable|numeric|min:0',
            'max_price'     => 'nullable|numeric|min:0',
            'sort'          => 'nullable|in:best_match,nearest,cheapest,newest',
        ]);

        $sort = $filters['sort'] ?? 'best_match';

        $query = CropAvailability::availableForSale()
            ->with(['cooperative', 'crop', 'cropVariety', 'cropGrade']);

        if (! empty($filters['crop_id'])) {
            $query->where('crop_id', $filters['crop_id']);
        }

        if (! empty($filters['crop_grade_id'])) {
            $query->where('crop_grade_id', $filters['crop_grade_id']);
        }

        if (! empty($filters['max_price'])) {
            $query->where('selling_price_per_kg', '<=', $filters['max_price']);
        }

        $listings = $query->latest()->limit(500)->get();

        if (! empty($filters['min_kg'])) {
            $listings = $listings->filter(fn ($listing) => $listing->remaining_kg >= (float) $filters['min_kg'])->values();
        }

        $buyerProfile = Auth::user()?->buyerProfile;
        $hasLocation = $buyerProfile && $buyerProfile->latitude !== null && $buyerProfile->longitude !== null;

        $listings = $matcher->rank($listings, $buyerProfile, ['min_kg' => $filters['min_kg'] ?? null]);

        $listings = match ($sort) {
            'nearest' => $listings->sortBy(fn ($l) => $l->distance_km ?? PHP_FLOAT_MAX)->values(),
            'cheapest' => $listings->sortBy('selling_price_per_kg')->values(),
            'newest' => $listings->sortByDesc('created_at')->values(),
            default => $listings, // already best_match sorted by the matcher
        };

        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 20;
        $listings = (new LengthAwarePaginator(
            $listings->forPage($page, $perPage),
            $listings->count(),
            $perPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        ))->withQueryString();

        $crops = Crop::orderBy('name')->get();
        $cropGrades = CropGrade::active()->get();

        return view('buyer.listings.index', [
            'listings' => $listings,
            'crops' => $crops,
            'cropGrades' => $cropGrades,
            'filters' => $filters + ['sort' => $sort],
            'hasLocation' => $hasLocation,
        ]);
    }

    public function show(CropAvailability $cropAvailability)
    {
        if ($cropAvailability->status !== CropAvailability::STATUS_AVAILABLE) {
            abort(404);
        }

        $cropAvailability->load(['cooperative', 'crop', 'cropVariety', 'cropGrade']);

        return view('buyer.listings.show', ['listing' => $cropAvailability]);
    }

    public function index()
    {
        $orders = BuyerOrder::forBuyer(Auth::id())
            ->with(['cooperative', 'items'])
            ->latest()
            ->paginate(15);

        return view('buyer.orders.index', compact('orders'));
    }

    public function showOrder(BuyerOrder $buyerOrder)
    {
        $this->authorizeBuyer($buyerOrder);
        $buyerOrder->load(['cooperative', 'items.crop', 'items.cropGrade', 'stop', 'payments']);

        return view('buyer.orders.show', compact('buyerOrder'));
    }

    public function store(Request $request)
    {
        $profile = Auth::user()->buyerProfile;
        if (! $profile || $profile->status !== BuyerProfile::STATUS_APPROVED) {
            throw ValidationException::withMessages([
                'buyer' => 'Your buyer account must be approved before you can place orders.',
            ]);
        }

        $data = $request->validate([
            'crop_availability_id' => 'required|exists:crop_availabilities,id',
            'quantity_kg'          => 'required|numeric|min:0.01',
            'preferred_delivery_date' => 'nullable|date|after_or_equal:today',
            'delivery_address'     => 'required|string|max:1000',
            'delivery_latitude'    => 'required|numeric|between:-90,90',
            'delivery_longitude'   => 'required|numeric|between:-180,180',
            'notes'                => 'nullable|string|max:1000',
        ]);

        $listing = CropAvailability::findOrFail($data['crop_availability_id']);

        if ($listing->status !== CropAvailability::STATUS_AVAILABLE) {
            throw ValidationException::withMessages(['crop_availability_id' => 'This listing is no longer available.']);
        }

        if ((float) $data['quantity_kg'] > $listing->remaining_kg) {
            throw ValidationException::withMessages(['quantity_kg' => "Only {$listing->remaining_kg} kg remains for this listing."]);
        }

        if ($listing->selling_price_per_kg === null) {
            throw ValidationException::withMessages(['crop_availability_id' => 'This listing does not have a selling price set yet.']);
        }

        $quantity = (float) $data['quantity_kg'];
        $rate = (float) $listing->selling_price_per_kg;
        $subtotal = round($quantity * $rate, 2);

        $order = DB::transaction(function () use ($listing, $data, $quantity, $rate, $subtotal) {
            $order = BuyerOrder::create([
                'buyer_id'       => Auth::id(),
                'cooperative_id' => $listing->cooperative_id,
                'reference'      => 'ORD-'.strtoupper(Str::random(6)),
                'status'         => BuyerOrder::STATUS_SUBMITTED,
                'total_kg'       => $quantity,
                'total_amount'   => $subtotal,
                'preferred_delivery_date' => $data['preferred_delivery_date'] ?? null,
                'delivery_address' => $data['delivery_address'],
                'delivery_latitude' => $data['delivery_latitude'],
                'delivery_longitude' => $data['delivery_longitude'],
                'notes'          => $data['notes'] ?? null,
            ]);

            BuyerOrderItem::create([
                'buyer_order_id'       => $order->id,
                'crop_availability_id' => $listing->id,
                'crop_id'              => $listing->crop_id,
                'crop_variety_id'      => $listing->crop_variety_id,
                'crop_grade_id'        => $listing->crop_grade_id,
                'quantity_kg'          => $quantity,
                'rate_per_kg'          => $rate,
                'subtotal'             => $subtotal,
            ]);

            return $order;
        });

        $this->notifyCoopAdmins($listing->cooperative_id, [
            'title'   => 'New buyer order',
            'message' => Auth::user()->name." submitted order {$order->reference}: {$quantity} kg at ₱".number_format($rate, 2).'/kg.',
            'link'    => route('coop.buyer-orders.show', $order),
        ]);

        return redirect()->route('buyer.orders.show', $order)
            ->with('success', "Order {$order->reference} submitted. The cooperative will review it.");
    }

    public function confirmReceipt(BuyerOrder $buyerOrder)
    {
        $this->authorizeBuyer($buyerOrder);

        if ($buyerOrder->status !== BuyerOrder::STATUS_DELIVERED) {
            throw ValidationException::withMessages([
                'order' => 'This order cannot be confirmed yet.',
            ]);
        }

        $buyerOrder->update([
            'status' => BuyerOrder::STATUS_COMPLETED,
            'confirmed_at' => now(),
        ]);

        \App\Models\AuditLog::create([
            'admin_id' => Auth::id(),
            'action' => 'confirm_buyer_order_receipt',
            'target_type' => 'buyer_order',
            'target_id' => $buyerOrder->id,
            'notes' => "Buyer confirmed receipt of order {$buyerOrder->reference}.",
        ]);

        $this->notifyCoopAdmins($buyerOrder->cooperative_id, [
            'title'   => 'Order receipt confirmed',
            'message' => "The buyer confirmed receipt of order {$buyerOrder->reference}.",
            'link'    => route('coop.buyer-orders.show', $buyerOrder),
        ]);

        return redirect()->route('buyer.orders.show', $buyerOrder)
            ->with('success', 'Thanks for confirming — order marked complete.');
    }

    public function track(BuyerOrder $buyerOrder)
    {
        $this->authorizeBuyer($buyerOrder);
        $buyerOrder->load('stop.haulJob.cooperative');

        $haulJob = $buyerOrder->stop?->haulJob;
        $stop = $buyerOrder->stop;

        return view('buyer.orders.track', compact('buyerOrder', 'haulJob', 'stop'));
    }

    public function trackLocation(BuyerOrder $buyerOrder)
    {
        $this->authorizeBuyer($buyerOrder);

        $haulJob = $buyerOrder->stop?->haulJob;
        if (! $haulJob) {
            return response()->json(['has_position' => false]);
        }

        return LocationMonitoringController::positionJson($haulJob);
    }

    private function authorizeBuyer(BuyerOrder $order): void
    {
        if ($order->buyer_id !== Auth::id()) {
            abort(403, 'This order does not belong to you.');
        }
    }

    private function notifyCoopAdmins(int $cooperativeId, array $payload): void
    {
        $admins = User::where('role', UserRole::COOP_ADMIN->value)->where('cooperative_id', $cooperativeId)->get();

        foreach ($admins as $admin) {
            Notification::create([
                'user_id'  => $admin->id,
                'title'    => $payload['title'],
                'message'  => $payload['message'],
                'link'     => $payload['link'],
                'category' => 'buyer_order',
            ]);
        }
    }
}
