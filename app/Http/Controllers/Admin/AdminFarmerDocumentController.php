<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FarmerDocument;
use App\Models\FarmerProfile;
use App\Models\User;
use App\Traits\Notifiable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminFarmerDocumentController extends Controller
{
    use Notifiable;

    public function index()
    {
        $documents = FarmerDocument::with('farmer')
            ->orderByRaw("FIELD(status, 'pending', 'approved', 'rejected')")
            ->orderBy('created_at', 'asc')
            ->take(200)
            ->get()
            ->groupBy('user_id');

        return view('admin.farmer-documents.index', compact('documents'));
    }

    public function approve(Request $request, FarmerDocument $document)
    {
        $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $document->update(['status' => 'approved', 'notes' => $request->input('notes')]);

        self::logAudit(
            Auth::id(),
            'approved_farmer_document',
            'farmer',
            $document->user_id,
            "Approved document \"{$document->original_filename}\" (type: {$document->document_type}) for farmer ID {$document->user_id}."
        );

        self::notifyDocumentApproved(
            User::find($document->user_id),
            $document->original_filename,
            route('farmer.documents')
        );

        $this->checkAndVerifyFarmer($document->user_id);

        return back()->with('success', 'Document approved.')
            ->with('next_steps', [
                'title'   => 'Document approved',
                'message' => 'Document approved.',
                'steps'   => [
                    'The farmer is notified of the outcome.',
                    'If their government ID and secondary documents are now approved, their profile auto-verifies so they can post and trade.',
                    'Continue with the next document in the review queue.',
                ],
            ]);
    }

    public function reject(Request $request, FarmerDocument $document)
    {
        $request->validate([
            'notes' => ['required', 'string', 'max:500'],
        ]);

        $document->update(['status' => 'rejected', 'notes' => $request->input('notes')]);

        self::logAudit(
            Auth::id(),
            'rejected_farmer_document',
            'farmer',
            $document->user_id,
            "Rejected document \"{$document->original_filename}\" (type: {$document->document_type}) for farmer ID {$document->user_id}. Reason: {$request->input('notes')}"
        );

        self::notifyDocumentRejected(
            User::find($document->user_id),
            $document->original_filename,
            $request->input('notes'),
            route('farmer.documents')
        );

        return back()->with('success', 'Document rejected.')
            ->with('next_steps', [
                'title'   => 'Document rejected',
                'message' => 'Document rejected.',
                'steps'   => [
                    'The farmer is notified with your reason.',
                    'They may re-upload a corrected document.',
                    'If required documents are missing, the farmer stays unverified until re-approved.',
                ],
            ]);
    }

    /**
     * Auto-verify farmer if they have at least one approved government_id
     * and at least one approved secondary document.
     */
    private function checkAndVerifyFarmer(int $userId): void
    {
        $approved = FarmerDocument::where('user_id', $userId)
            ->where('status', 'approved')
            ->get();

        $hasId = $approved->where('document_type', 'government_id')->isNotEmpty();
        $hasSecondary = $approved->whereIn('document_type', [
            'rsbsa', 'land_title', 'barangay_cert', 'mao_cert', 'other'
        ])->isNotEmpty();

        if ($hasId && $hasSecondary) {
            $profile = FarmerProfile::where('user_id', $userId)->first();

            if ($profile && !$profile->is_verified) {
                $profile->update(['is_verified' => true]);

                self::logAudit(
                    Auth::id(),
                    'verified_farmer',
                    'farmer',
                    $userId,
                    "Farmer ID {$userId} auto-verified after both required document types approved."
                );
            }
        }
    }
}
