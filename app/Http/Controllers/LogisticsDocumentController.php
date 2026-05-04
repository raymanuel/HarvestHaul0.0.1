<?php

namespace App\Http\Controllers;

use App\Models\LogisticsDocument;
use App\Models\LogisticsProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class LogisticsDocumentController extends Controller
{
    private function authorizeLogistics(): void
    {
        if (Auth::user()->role !== 'logistics_partner') {
            abort(403);
        }
    }

    public function index()
    {
        $this->authorizeLogistics();

        $documents = LogisticsDocument::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        $profile = LogisticsProfile::where('user_id', Auth::id())->first();

        return view('logistics.documents', compact('documents', 'profile'));
    }

    public function store(Request $request)
    {
        $this->authorizeLogistics();

        $request->validate([
            'document_type' => ['required', 'in:dti_sec,business_permit,bir_cert,mayors_permit'],
            'document_file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $file = $request->file('document_file');
        $originalFilename = $file->getClientOriginalName();
        $path = $file->store('logistics-documents/' . Auth::id(), 'public');

        LogisticsDocument::create([
            'user_id'           => Auth::id(),
            'document_type'     => $request->document_type,
            'file_path'         => $path,
            'original_filename' => $originalFilename,
            'status'            => 'pending',
        ]);

        return redirect()->route('logistics.documents')->with('success', 'Document uploaded successfully.');
    }

    public function destroy(LogisticsDocument $document)
    {
        $this->authorizeLogistics();

        if ($document->user_id !== Auth::id()) {
            abort(403);
        }

        if ($document->status === 'approved') {
            return redirect()->route('logistics.documents')->with('error', 'Approved documents cannot be deleted.');
        }

        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return redirect()->route('logistics.documents')->with('success', 'Document removed.');
    }
}
