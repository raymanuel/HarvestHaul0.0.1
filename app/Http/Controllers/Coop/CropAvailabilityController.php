<?php

namespace App\Http\Controllers\Coop;

use App\Http\Controllers\Controller;
use App\Models\CropAvailability;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CropAvailabilityController extends Controller
{
    public function index()
    {
        $listings = CropAvailability::with(['crop', 'cropVariety', 'cropGrade', 'receivingRecord'])
            ->where('cooperative_id', $this->cooperativeId())
            ->orderByDesc('created_at')
            ->get();

        return view('coop.availability.index', compact('listings'));
    }

    public function updatePrice(Request $request, CropAvailability $cropAvailability)
    {
        $this->authorizeCoop($cropAvailability);

        $data = $request->validate([
            'selling_price_per_kg' => 'required|numeric|min:0',
        ]);

        $cropAvailability->update($data);

        return back()->with('success', 'Selling price updated.');
    }

    public function archive(CropAvailability $cropAvailability)
    {
        $this->authorizeCoop($cropAvailability);

        $cropAvailability->update(['status' => CropAvailability::STATUS_ARCHIVED]);

        return back()->with('success', 'Listing pulled from sale.');
    }

    public function restore(CropAvailability $cropAvailability)
    {
        $this->authorizeCoop($cropAvailability);

        $cropAvailability->update(['status' => CropAvailability::STATUS_AVAILABLE]);

        return back()->with('success', 'Listing restored to available.');
    }

    private function cooperativeId(): int
    {
        $id = Auth::user()?->cooperative_id;
        if (! $id) {
            abort(403, 'You are not an active cooperative admin.');
        }

        return $id;
    }

    private function authorizeCoop(CropAvailability $availability): void
    {
        if ($availability->cooperative_id !== Auth::user()?->cooperative_id) {
            abort(403, 'This listing does not belong to your cooperative.');
        }
    }
}
