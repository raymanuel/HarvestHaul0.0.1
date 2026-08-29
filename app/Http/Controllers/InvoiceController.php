<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class InvoiceController extends Controller
{
    public function download(Invoice $invoice)
    {
        $user = Auth::user();
        $job = $invoice->poolingJob()->with('harvests')->first();

        $isOwner = $job->logisticsProfile->user_id === $user->id;
        $isParticipatingFarmer = $job->harvests->contains('user_id', $user->id);

        if (!$isOwner && !$isParticipatingFarmer) {
            abort(403);
        }

        if (!$invoice->pdf_path || !Storage::disk('local')->exists($invoice->pdf_path)) {
            abort(404, 'Invoice PDF not found.');
        }

        return Storage::disk('local')->download(
            $invoice->pdf_path,
            "{$invoice->invoice_number}.pdf"
        );
    }
}
