<?php

namespace App\Http\Controllers\Coop;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingsController extends Controller
{
    public function edit()
    {
        $cooperative = Auth::user()->cooperative;

        return view('coop.settings.edit', compact('cooperative'));
    }

    public function update(Request $request)
    {
        $cooperative = Auth::user()->cooperative;

        $data = $request->validate([
            'max_cluster_radius_km' => 'nullable|numeric|min:1|max:500',
        ]);

        $cooperative->update([
            'max_cluster_radius_km' => $data['max_cluster_radius_km'] ?? null,
        ]);

        AuditLog::create([
            'admin_id'    => Auth::id(),
            'action'      => 'update_coop_settings',
            'target_type' => 'cooperative',
            'target_id'   => $cooperative->id,
            'notes'       => 'Pickup consolidation radius set to '
                .($data['max_cluster_radius_km'] ?? 'platform default').'.',
        ]);

        return redirect()->route('coop.settings.edit')->with('success', 'Settings saved.');
    }
}
