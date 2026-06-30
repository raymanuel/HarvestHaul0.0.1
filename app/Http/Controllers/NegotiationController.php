<?php

namespace App\Http\Controllers;

use App\Models\Harvest;
use App\Models\Negotiation;
use App\Models\NegotiationMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * NegotiationController
 *
 * Manages the B2B Crop Negotiation Chat Rooms and deal-closing workflow.
 */
class NegotiationController extends Controller
{
    /**
     * Start a negotiation (usually initiated by a buyer clicking a lot on Crop Board).
     */
    public function start(Request $request)
    {
        $request->validate([
            'harvest_id' => 'required|exists:harvests,id',
        ]);

        $buyer = Auth::user();
        if ($buyer->role !== 'buyer') {
            abort(403, 'Only commercial buyers can initiate B2B negotiations.');
        }

        $harvest = Harvest::findOrFail($request->harvest_id);

        if ($harvest->status !== 'active') {
            return back()->with('error', 'This harvest lot is no longer active.');
        }

        // Avoid duplicate active negotiations for the same lot
        $existing = Negotiation::where('buyer_id', $buyer->id)
            ->where('harvest_id', $harvest->id)
            ->whereIn('status', ['OPEN', 'AGREED'])
            ->first();

        if ($existing) {
            return redirect()->route('negotiations.room', $existing->id);
        }

        // Mark harvest as under negotiation
        $harvest->update(['status' => 'negotiating']);

        // Create new negotiation
        $negotiation = Negotiation::create([
            'buyer_id'          => $buyer->id,
            'farmer_id'         => $harvest->user_id,
            'harvest_id'        => $harvest->id,
            'negotiated_price'  => null,
            'negotiated_volume' => $harvest->quantity_kg,
            'status'            => 'OPEN',
        ]);

        // Post default greeting message
        NegotiationMessage::create([
            'negotiation_id' => $negotiation->id,
            'sender_id'      => $buyer->id,
            'message_text'   => "Hello! I am interested in your harvest lot #{$harvest->id} ({$harvest->crop_type}). Let's discuss pricing and volume.",
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

        $request->validate([
            'message_text' => 'required|string|max:1000',
        ]);

        NegotiationMessage::create([
            'negotiation_id' => $negotiation->id,
            'sender_id'      => $user->id,
            'message_text'   => $request->message_text,
        ]);

        return back()->with('success', 'Message sent.');
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

        $request->validate([
            'negotiated_price'  => 'required|numeric|min:0.01|max:999999.99',
            'negotiated_volume' => 'required|numeric|min:0.01|max:999999.99',
        ]);

        $negotiation->update([
            'negotiated_price'  => $request->negotiated_price,
            'negotiated_volume' => $request->negotiated_volume,
            'status'            => 'OPEN', // Resets back to open for response
        ]);

        $formattedPrice = number_format($request->negotiated_price, 2);
        $formattedVolume = number_format($request->negotiated_volume);

        // System message update log in chat
        NegotiationMessage::create([
            'negotiation_id' => $negotiation->id,
            'sender_id'      => $user->id,
            'message_text'   => "[System Offer] Proposes terms: ₱{$formattedPrice}/kg for {$formattedVolume} kg.",
        ]);

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

        $negotiation->update([
            'status' => 'AGREED',
        ]);

        NegotiationMessage::create([
            'negotiation_id' => $negotiation->id,
            'sender_id'      => $user->id,
            'message_text'   => "[System Message] Agreed to the proposed terms. Ready to finalize drop-off.",
        ]);

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

        $request->validate([
            'destination_address'   => 'required|string|max:500',
            'destination_latitude'  => 'required|numeric|between:-90,90',
            'destination_longitude' => 'required|numeric|between:-180,180',
        ]);

        // Update the harvest listing's destination details and quantity (representing the haul request)
        $harvest = $negotiation->harvest;
        $harvest->update([
            'destination_address'   => $request->destination_address,
            'destination_latitude'  => $request->destination_latitude,
            'destination_longitude' => $request->destination_longitude,
            'quantity_kg'           => $negotiation->negotiated_volume,
            'status'                => 'sold', // Deal closed — now awaiting logistics pickup
        ]);

        $negotiation->update([
            'status' => 'COMPLETED',
        ]);

        NegotiationMessage::create([
            'negotiation_id' => $negotiation->id,
            'sender_id'      => $buyer->id,
            'message_text'   => "[System Message] Deal finalized! Drop-off submitted: {$request->destination_address}",
        ]);

        return redirect()->route('buyer.negotiations')->with('success', 'B2B deal closed! Harvest lot updated with drop-off coordinates.');
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

        $negotiations = Negotiation::where('farmer_id', $user->id)
            ->with(['buyer', 'harvest.crop', 'harvest.cropVariety', 'messages' => function ($q) {
                $q->latest()->take(1);
            }])
            ->latest()
            ->paginate(15);

        return view('farmers.negotiations', compact('negotiations'));
    }
}
