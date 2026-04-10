<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HarvestController extends Controller
{
    public function index()
    {
        if (Auth::user()->role !== 'farmer') {
            abort(403, 'Unauthorized action.');
        }

        $harvests = Auth::user()->harvests()->latest()->get();

        return view('harvests.index', compact('harvests'));
    }

    public function create()
    {
        if (Auth::user()->role !== 'farmer') {
            abort(403, 'Unauthorized action.');
        }

        return view('harvests.create');
    }

    public function store(Request $request)
    {
        if (Auth::user()->role !== 'farmer') {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'crop_type'   => 'required|string|max:255',
            'quantity_kg' => 'required|numeric|min:1',
            'notes'       => 'nullable|string|max:500',
        ]);

        Auth::user()->harvests()->create([
            'crop_type'   => $request->crop_type,
            'quantity_kg' => $request->quantity_kg,
            'notes'       => $request->notes,
            'status'      => 'active',
        ]);

        return redirect()->route('harvests.index')->with('success', 'Harvest listing posted! You are now visible on the logistics map.');
    }

    public function destroy($id)
    {
        $harvest = Auth::user()->harvests()->findOrFail($id);
        $harvest->delete();

        return back()->with('success', 'Harvest listing removed.');
    }
}
