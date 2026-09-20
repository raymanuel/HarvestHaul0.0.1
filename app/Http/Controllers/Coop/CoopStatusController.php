<?php

namespace App\Http\Controllers\Coop;

use App\Http\Controllers\Controller;
use App\Mail\CooperativeStatusMail;
use App\Models\AuditLog;
use App\Models\Cooperative;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class CoopStatusController extends Controller
{
    public function show()
    {
        $user = Auth::user();
        $cooperative = $user->cooperative;

        if ($cooperative && $cooperative->isApproved()) {
            return redirect()->route('coop.dashboard');
        }

        return view('coop.status', [
            'cooperative' => $cooperative,
        ]);
    }

    public function resubmit(Request $request)
    {
        $user = Auth::user();
        $cooperative = $user->cooperative;

        abort_unless($cooperative, 404, 'No cooperative application found on your account.');

        abort_if(in_array($cooperative->status, [
            Cooperative::STATUS_APPROVED,
            Cooperative::STATUS_SUSPENDED,
        ], true), 403, 'Your cooperative is not in a state that allows resubmission.');

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'contact_number' => 'required|string|max:20',
            'official_email' => 'required|email|max:255',
            'street_address' => 'required|string|max:1000',
            'cert_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'articles_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'bylaws_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'resubmission_note' => 'nullable|string|max:2000',
        ]);

        $updates = [
            'name' => $data['name'],
            'type' => $data['type'],
            'contact_number' => $data['contact_number'],
            'official_email' => strtolower($data['official_email']),
            'street_address' => $data['street_address'],
            'status' => Cooperative::STATUS_PENDING,
            'reviewer_id' => null,
            'reviewed_at' => null,
            'rejection_reason' => null,
            'admin_notes' => null,
        ];

        foreach (['cert_document', 'articles_document', 'bylaws_document'] as $field) {
            if ($request->hasFile($field)) {
                $updates[str_replace('_document', '_document_path', $field)] =
                    Storage::disk('local')->putFile('coop-documents', $request->file($field));
            }
        }

        $cooperative->update($updates);

        AuditLog::create([
            'admin_id' => $user->id,
            'action' => 'coop_resubmitted',
            'target_type' => 'cooperative',
            'target_id' => $cooperative->id,
            'notes' => "Cooperative {$cooperative->name} was resubmitted by {$user->name}. Back in the review queue.",
        ]);

        if ($cooperative->coopAdminUser) {
            Mail::to($cooperative->coopAdminUser->email)->send(
                new CooperativeStatusMail($cooperative, 'resubmitted')
            );
        }

        return redirect()->route('coop.status')
            ->with('success', 'Your cooperative application was resubmitted successfully. It is now back in the review queue.');
    }
}

