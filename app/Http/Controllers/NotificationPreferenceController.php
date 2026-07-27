<?php

namespace App\Http\Controllers;

use App\Models\NotificationPreference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationPreferenceController extends Controller
{
    public function index()
    {
        $preferences = NotificationPreference::getAllForUser(Auth::id());

        return view('settings.notifications', compact('preferences'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'preferences' => 'required|array',
            'preferences.*' => 'boolean',
        ]);

        $userId = Auth::id();

        foreach ($validated['preferences'] as $category => $enabled) {
            NotificationPreference::updateOrCreate(
                ['user_id' => $userId, 'category' => $category],
                ['enabled' => $enabled]
            );
        }

        return back()->with('success', 'Notification preferences updated.');
    }
}
