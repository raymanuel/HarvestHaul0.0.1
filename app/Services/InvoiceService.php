<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\PoolingJob;
use App\Models\User;
use App\Notifications\InvoiceReady;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class InvoiceService
{
    public function generateInvoice(PoolingJob $job): Invoice
    {
        $job->loadMissing(['harvests.farmer', 'harvests.crop', 'logisticsProfile', 'truck']);

        $invoiceNumber = $this->generateInvoiceNumber($job);

        // Use sum of individual cost_shares for accurate totals
        $costShares = $job->harvests->pluck('pivot.cost_share')->filter();
        $totalAmount = $costShares->isNotEmpty()
            ? (float) $costShares->sum()
            : (float) ($job->negotiated_price ?? $job->price_reference ?? 0);

        $totalKg = (float) $job->total_kg;
        $farmCount = $job->farm_count;

        $invoice = Invoice::create([
            'pooling_job_id' => $job->id,
            'logistics_profile_id' => $job->logistics_profile_id,
            'invoice_number' => $invoiceNumber,
            'total_amount' => $totalAmount,
            'total_kg' => $totalKg,
            'farm_count' => $farmCount,
            'status' => 'draft',
            'due_at' => now()->addDays(30),
            'generated_at' => now(),
        ]);

        $html = $this->renderInvoiceHtml($job, $invoice);
        $pdfPath = "invoices/{$invoiceNumber}.pdf";
        Storage::disk('public')->put($pdfPath, '');

        Pdf::loadHTML($html)->save(storage_path("app/public/{$pdfPath}"));

        $invoice->update([
            'pdf_path' => $pdfPath,
            'status' => 'generated',
        ]);

        $this->sendInvoiceEmails($job, $invoice);

        \App\Models\AuditLog::create([
            'admin_id' => $job->logisticsProfile?->user_id ?? 1,
            'action' => 'invoice_generated',
            'target_type' => 'invoice',
            'target_id' => $invoice->id,
            'notes' => "Invoice #{$invoiceNumber} auto-generated for Route #{$job->id} (₱" . number_format($totalAmount, 2) . ").",
        ]);

        return $invoice;
    }

    public function generateInvoiceNumber(PoolingJob $job): string
    {
        $prefix = 'HH-INV-';
        $date = now()->format('Ymd');
        $sequence = str_pad($job->id, 5, '0', STR_PAD_LEFT);
        return $prefix . $date . '-' . $sequence;
    }

    public function getOrCreateInvoice(PoolingJob $job): Invoice
    {
        $existing = Invoice::where('pooling_job_id', $job->id)->first();
        if ($existing) return $existing;

        return $this->generateInvoice($job);
    }

    public function voidInvoice(Invoice $invoice, string $reason = ''): Invoice
    {
        $invoice->update([
            'status' => 'voided',
            'voided_at' => now(),
            'void_reason' => $reason,
        ]);

        \App\Models\AuditLog::create([
            'admin_id' => request()->user()?->id ?? 1,
            'action' => 'invoice_voided',
            'target_type' => 'invoice',
            'target_id' => $invoice->id,
            'notes' => "Invoice #{$invoice->invoice_number} voided: {$reason}",
        ]);

        return $invoice;
    }

    private function sendInvoiceEmails(PoolingJob $job, Invoice $invoice): void
    {
        $recipientEmails = [];

        if ($job->logisticsProfile?->user_id) {
            $user = User::find($job->logisticsProfile->user_id);
            if ($user?->email) {
                $recipientEmails[] = $user->email;
                $user->notify(new InvoiceReady($invoice));
            }
        }

        foreach ($job->harvests as $h) {
            if ($h->user_id) {
                $user = User::find($h->user_id);
                if ($user?->email) {
                    $recipientEmails[] = $user->email;
                    $user->notify(new InvoiceReady($invoice));
                }
            }
        }

        $recipientEmails = array_unique($recipientEmails);

        foreach ($recipientEmails as $email) {
            try {
                Mail::to($email)->send(new \App\Mail\InvoiceMail($invoice));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Failed to send invoice email to {$email}: {$e->getMessage()}");
            }
        }

        $invoice->update(['sent_at' => now()]);
    }

    private function renderInvoiceHtml(PoolingJob $job, Invoice $invoice): string
    {
        $entries = $job->harvests->map(function ($h) {
            $costShare = $h->pivot->cost_share !== null
                ? (float) $h->pivot->cost_share
                : 0;

            return [
                'farmer' => $h->farmer->name ?? 'Unknown',
                'crop' => $h->crop->name ?? $h->crop_type ?? '—',
                'qty' => (float) $h->pivot->quantity_kg,
                'cost' => number_format($costShare, 2),
            ];
        });

        $rows = '';
        foreach ($entries as $i => $e) {
            $rows .= "<tr>
                <td style='padding:8px;border-bottom:1px solid #e5e7eb;text-align:center'>{$i}</td>
                <td style='padding:8px;border-bottom:1px solid #e5e7eb'>{$e['farmer']}</td>
                <td style='padding:8px;border-bottom:1px solid #e5e7eb'>{$e['crop']}</td>
                <td style='padding:8px;border-bottom:1px solid #e5e7eb;text-align:right'>" . number_format($e['qty'], 2) . "</td>
                <td style='padding:8px;border-bottom:1px solid #e5e7eb;text-align:right'>₱{$e['cost']}</td>
            </tr>";
        }

        $company = $job->logisticsProfile->company_name ?? 'HarvestHaul Logistics';
        $plate = $job->truck->plate_number ?? '—';
        $date = now()->format('F d, Y');

        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head><meta charset="UTF-8"><title>Invoice {$invoice->invoice_number}</title>
        <style>
            body { font-family: 'Segoe UI', Arial, sans-serif; margin: 0; padding: 40px; color: #1e293b; }
            .header { border-bottom: 3px solid #059669; padding-bottom: 20px; margin-bottom: 30px; }
            .header h1 { font-size: 28px; color: #059669; margin: 0; }
            .header p { color: #64748b; margin: 4px 0 0; }
            .meta { display: flex; justify-content: space-between; margin-bottom: 30px; }
            .meta-box { background: #f8fafc; padding: 16px; border-radius: 8px; flex: 1; margin: 0 8px; }
            .meta-box h3 { margin: 0 0 4px; font-size: 11px; text-transform: uppercase; color: #94a3b8; }
            .meta-box p { margin: 0; font-size: 16px; font-weight: 700; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th { background: #059669; color: white; padding: 10px 8px; text-align: left; font-size: 12px; text-transform: uppercase; }
            .total-row td { font-weight: 700; padding: 12px 8px; border-top: 2px solid #059669; font-size: 16px; }
            .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #e5e7eb; text-align: center; color: #94a3b8; font-size: 12px; }
        </style>
        </head>
        <body>
            <div class="header">
                <h1>INVOICE</h1>
                <p>{$company} — Route #{$job->id}</p>
            </div>
            <div class="meta">
                <div class="meta-box"><h3>Invoice No.</h3><p>{$invoice->invoice_number}</p></div>
                <div class="meta-box"><h3>Date</h3><p>{$date}</p></div>
                <div class="meta-box"><h3>Vehicle</h3><p>{$plate}</p></div>
                <div class="meta-box"><h3>Total</h3><p>₱" . number_format($invoice->total_amount, 2) . "</p></div>
            </div>
            <table>
                <tr><th>#</th><th>Farmer</th><th>Crop</th><th style='text-align:right'>Qty (kg)</th><th style='text-align:right'>Amount</th></tr>
                {$rows}
                <tr class="total-row">
                    <td colspan="3"></td>
                    <td style='text-align:right'>" . number_format($invoice->total_kg, 2) . " kg</td>
                    <td style='text-align:right'>₱" . number_format($invoice->total_amount, 2) . "</td>
                </tr>
            </table>
            <div class="footer">
                <p>HarvestHaul — Smart Farm Logistics Platform &bull; Generated {$date}</p>
                <p>This is a computer-generated invoice.</p>
            </div>
        </body>
        </html>
        HTML;
    }
}
