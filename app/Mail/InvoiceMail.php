<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Invoice $invoice;
    public ?string $scopedPdfPath = null;
    public ?float $farmerShare = null;

    public function __construct(Invoice $invoice, ?string $scopedPdfPath = null, ?float $farmerShare = null)
    {
        $this->invoice = $invoice;
        $this->scopedPdfPath = $scopedPdfPath;
        $this->farmerShare = $farmerShare;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Hauling Invoice {$this->invoice->invoice_number} from HarvestHaul",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice',
            with: [
                'farmerShare' => $this->farmerShare,
            ],
        );
    }

    public function attachments(): array
    {
        $path = $this->scopedPdfPath !== null
            ? storage_path("app/private/{$this->scopedPdfPath}")
            : storage_path("app/private/{$this->invoice->pdf_path}");

        if (file_exists($path)) {
            return [
                Attachment::fromPath($path)
                    ->as("{$this->invoice->invoice_number}.pdf")
                    ->withMime('application/pdf'),
            ];
        }

        return [];
    }
}
