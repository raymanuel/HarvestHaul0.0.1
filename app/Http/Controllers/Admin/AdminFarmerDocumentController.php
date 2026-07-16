<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\FarmerDocument;
use App\Models\FarmerProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminFarmerDocumentController extends Controller
{
    private function log(string $action, string $targetType, int $targetId, string $notes): void
    {
        AuditLog::create([
            'admin_id'    => Auth::id(),
            'action'      => $action,
            'target_type' => $targetType,
            'target_id'   => $targetId,
            'notes'       => $notes,
        ]);
    }

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
        $document->update(['status' => 'approved', 'notes' => $request->input('notes')]);

        $this->log(
            'approved_farmer_document',
            'farmer',
            $document->user_id,
            "Approved document \"{$document->original_filename}\" (type: {$document->document_type}) for farmer ID {$document->user_id}."
        );

        \App\Models\Notification::create([
            'user_id' => $document->user_id,
            'title' => 'Document Approved',
            'message' => "Your uploaded document '{$document->original_filename}' has been approved.",
            'link' => route('farmer.documents'),
        ]);

        $this->checkAndVerifyFarmer($document->user_id);

        return back()->with('success', 'Document approved.');
    }

    public function reject(Request $request, FarmerDocument $document)
    {
        $request->validate([
            'notes' => ['required', 'string', 'max:500'],
        ]);

        $document->update(['status' => 'rejected', 'notes' => $request->input('notes')]);

        $this->log(
            'rejected_farmer_document',
            'farmer',
            $document->user_id,
            "Rejected document \"{$document->original_filename}\" (type: {$document->document_type}) for farmer ID {$document->user_id}. Reason: {$request->input('notes')}"
        );

        \App\Models\Notification::create([
            'user_id' => $document->user_id,
            'title' => 'Document Rejected',
            'message' => "Your uploaded document '{$document->original_filename}' was rejected. Reason: {$request->input('notes')}",
            'link' => route('farmer.documents'),
        ]);

        return back()->with('success', 'Document rejected.');
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

                $this->log(
                    'verified_farmer',
                    'farmer',
                    $userId,
                    "Farmer ID {$userId} auto-verified after both required document types approved."
                );
            }
        }
    }
}
