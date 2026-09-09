<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LogisticsDocument;
use App\Models\LogisticsProfile;
use App\Models\User;
use App\Traits\Notifiable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminLogisticsDocumentController extends Controller
{
    use Notifiable;

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
        $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $data = [
            'status' => 'approved',
            'notes'  => $request->input('notes'),
        ];

        // If this is a business_permit doc and admin confirmed the match
        if ($document->document_type === 'business_permit' && $request->boolean('permit_match_confirmed')) {
            $data['business_permit_match_confirmed'] = true;

            LogisticsProfile::where('user_id', $document->user_id)
                ->update(['business_permit_verified' => true]);

            self::logAudit(
                Auth::id(),
                'verified_business_permit',
                'logistics_partner',
                $document->user_id,
                "Business permit number confirmed and verified for logistics partner ID {$document->user_id}."
            );
        }

        $document->update($data);

        self::logAudit(
            Auth::id(),
            'approved_logistics_document',
            'logistics_partner',
            $document->user_id,
            "Approved document \"{$document->original_filename}\" (type: {$document->document_type}) for logistics partner ID {$document->user_id}."
        );

        self::notifyDocumentApproved(
            User::find($document->user_id),
            $document->original_filename,
            route('logistics.documents')
        );

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

        self::logAudit(
            Auth::id(),
            'rejected_logistics_document',
            'logistics_partner',
            $document->user_id,
            "Rejected document \"{$document->original_filename}\" (type: {$document->document_type}) for logistics partner ID {$document->user_id}. Reason: {$request->input('notes')}"
        );

        self::notifyDocumentRejected(
            User::find($document->user_id),
            $document->original_filename,
            $request->input('notes'),
            route('logistics.documents')
        );

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

            self::logAudit(
                Auth::id(),
                'verified_logistics',
                'logistics_partner',
                $userId,
                "Logistics partner ID {$userId} auto-verified after business permit confirmed and additional documents approved."
            );
        }
    }
}
