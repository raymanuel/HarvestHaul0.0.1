<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\LogisticsDocument;
use App\Models\LogisticsProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminLogisticsDocumentController extends Controller
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
        $documents = LogisticsDocument::with(['logisticsPartner', 'logisticsPartner.logisticsProfile'])
            ->orderByRaw("FIELD(status, 'pending', 'approved', 'rejected')")
            ->orderBy('created_at', 'asc')
            ->take(200)
            ->get()
            ->groupBy('user_id');

        return view('admin.logistics-documents.index', compact('documents'));
    }

    public function approve(Request $request, LogisticsDocument $document)
    {
        $data = [
            'status' => 'approved',
            'notes'  => $request->input('notes'),
        ];

        // If this is a business_permit doc and admin confirmed the match
        if ($document->document_type === 'business_permit' && $request->boolean('permit_match_confirmed')) {
            $data['business_permit_match_confirmed'] = true;

            LogisticsProfile::where('user_id', $document->user_id)
                ->update(['business_permit_verified' => true]);

            $this->log(
                'verified_business_permit',
                'logistics_partner',
                $document->user_id,
                "Business permit number confirmed and verified for logistics partner ID {$document->user_id}."
            );
        }

        $document->update($data);

        $this->log(
            'approved_logistics_document',
            'logistics_partner',
            $document->user_id,
            "Approved document \"{$document->original_filename}\" (type: {$document->document_type}) for logistics partner ID {$document->user_id}."
        );

        \App\Models\Notification::create([
            'user_id' => $document->user_id,
            'title' => 'Document Approved',
            'message' => "Your uploaded document '{$document->original_filename}' has been approved.",
            'link' => route('logistics.documents'),
        ]);

        $this->checkAndVerifyLogistics($document->user_id);

        return back()->with('success', 'Document approved.');
    }

    public function reject(Request $request, LogisticsDocument $document)
    {
        $request->validate([
            'notes' => ['required', 'string', 'max:500'],
        ]);

        $document->update([
            'status' => 'rejected',
            'notes'  => $request->input('notes'),
        ]);

        $this->log(
            'rejected_logistics_document',
            'logistics_partner',
            $document->user_id,
            "Rejected document \"{$document->original_filename}\" (type: {$document->document_type}) for logistics partner ID {$document->user_id}. Reason: {$request->input('notes')}"
        );

        \App\Models\Notification::create([
            'user_id' => $document->user_id,
            'title' => 'Document Rejected',
            'message' => "Your uploaded document '{$document->original_filename}' was rejected. Reason: {$request->input('notes')}",
            'link' => route('logistics.documents'),
        ]);

        return back()->with('success', 'Document rejected.');
    }

    /**
     * Auto-verify logistics partner if:
     * - business_permit_verified = true (hard condition)
     * - At least one other document type is approved
     */
    private function checkAndVerifyLogistics(int $userId): void
    {
        $profile = LogisticsProfile::where('user_id', $userId)->first();

        if (!$profile || $profile->is_verified) {
            return;
        }

        // Hard condition — business permit must be verified
        if (!$profile->business_permit_verified) {
            return;
        }

        $approved = LogisticsDocument::where('user_id', $userId)
            ->where('status', 'approved')
            ->get();

        $hasOtherDoc = $approved->whereIn('document_type', [
            'dti_sec', 'bir_cert', 'mayors_permit'
        ])->isNotEmpty();

        if ($hasOtherDoc) {
            $profile->update(['is_verified' => true]);

            $this->log(
                'verified_logistics',
                'logistics_partner',
                $userId,
                "Logistics partner ID {$userId} auto-verified after business permit confirmed and additional documents approved."
            );
        }
    }
}
