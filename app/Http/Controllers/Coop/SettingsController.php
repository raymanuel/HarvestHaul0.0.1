<?php

namespace App\Http\Controllers\Coop;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingsController extends Controller
{
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

        // Submitted from the pickup planner (the only place this setting
        // affects) — return there rather than to a dedicated settings page.
        return back()->with('success', 'Settings saved.');
    }
}
