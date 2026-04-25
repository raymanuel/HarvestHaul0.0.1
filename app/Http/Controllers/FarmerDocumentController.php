<?php

namespace App\Http\Controllers;

use App\Models\FarmerDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class FarmerDocumentController extends Controller
{
    private function authorizeFarmer(): void
    {
        if (Auth::user()->role !== 'farmer') {
            abort(403);
        }
    }

    public function index()
    {
        $this->authorizeFarmer();

        $documents = FarmerDocument::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return view('farmer.documents', compact('documents'));
    }

    public function store(Request $request)
    {
        $this->authorizeFarmer();

        $request->validate([
            'document_type' => ['required', 'in:government_id,rsbsa,land_title,barangay_cert,mao_cert,other'],
            'document_file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $file = $request->file('document_file');
        $originalFilename = $file->getClientOriginalName();
        $path = $file->store('farmer-documents/' . Auth::id(), 'public');

        FarmerDocument::create([
            'user_id'           => Auth::id(),
            'document_type'     => $request->document_type,
            'file_path'         => $path,
            'original_filename' => $originalFilename,
            'status'            => 'pending',
        ]);

        return redirect()->route('farmer.documents')->with('success', 'Document uploaded successfully.');
    }

    public function destroy(FarmerDocument $document)
    {
        $this->authorizeFarmer();

        if ($document->user_id !== Auth::id()) {
            abort(403);
        }

        if ($document->status === 'approved') {
            return redirect()->route('farmer.documents')->with('error', 'Approved documents cannot be deleted.');
        }

        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return redirect()->route('farmer.documents')->with('success', 'Document removed.');
    }
}
