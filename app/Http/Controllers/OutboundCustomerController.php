<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerCardRequest;
use App\Models\CustomerCard;
use App\Traits\Notifiable;
use Illuminate\Support\Facades\Auth;

class OutboundCustomerController extends Controller
{
    use Notifiable;

    public function index()
    {
        $customers = CustomerCard::forProfile(Auth::user()->logisticsProfile->id)
            ->withCount('outboundOrders')
            ->latest()
            ->get();

        return view('customers.index', compact('customers'));
    }

    public function create()
    {
        return view('customers.form', ['customer' => null]);
    }

    public function store(StoreCustomerCardRequest $request)
    {
        $card = CustomerCard::create([
            'logistics_profile_id' => Auth::user()->logisticsProfile->id,
            ...$request->validated(),
        ]);

        self::logAudit(Auth::id(), 'added_customer_card', 'customer_cards', $card->id, "Coop added customer {$card->name}.");

        return redirect()->route('coop.customers.index')
            ->with('success', "Customer {$card->name} added to your list. You can now send them orders from Outbound.")
            ->with('next_steps', [
                'title'   => 'Customer saved',
                'message' => "Customer {$card->name} is on your list.",
                'steps'   => [
                    'You can now create an outbound order for this customer.',
                    'Open Outbound Orders and press New Order.',
                ],
            ]);
    }

    public function edit(CustomerCard $customerCard)
    {
        $this->authorizeOwnership($customerCard);

        return view('customers.form', ['customer' => $customerCard]);
    }

    public function update(StoreCustomerCardRequest $request, CustomerCard $customerCard)
    {
        $this->authorizeOwnership($customerCard);
        $customerCard->update($request->validated());

        return redirect()->route('coop.customers.index')
            ->with('success', "Customer {$customerCard->name} updated. Your next outbound order will use the new details.");
    }

    public function destroy(CustomerCard $customerCard)
    {
        $this->authorizeOwnership($customerCard);

        $orderCount = $customerCard->outboundOrders()->count();
        if ($orderCount > 0) {
            return back()->with('error', "Customer {$customerCard->name} has {$orderCount} order(s), so they can't be removed. Keep the card, or finish their open orders first.");
        }

        $customerCard->delete();

        return redirect()->route('coop.customers.index')
            ->with('success', "Customer {$customerCard->name} removed from your list.");
    }

    private function authorizeOwnership(CustomerCard $customerCard): void
    {
        if ($customerCard->logistics_profile_id !== Auth::user()->logisticsProfile->id) {
            abort(404);
        }
    }
}