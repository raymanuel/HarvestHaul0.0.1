<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class InvoiceReady extends Notification
{
    use Queueable;

    public Invoice $invoice;

    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Invoice {$this->invoice->invoice_number} from HarvestHaul")
            ->line("Your invoice #{$this->invoice->invoice_number} has been generated.")
            ->line("Total amount: ₱" . number_format($this->invoice->total_amount, 2))
            ->action('Download Invoice', url("/invoices/{$this->invoice->invoice_number}/download"));
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => "Invoice #{$this->invoice->invoice_number} Ready",
            'message' => "Invoice #{$this->invoice->invoice_number} for ₱" . number_format($this->invoice->total_amount, 2) . ' is ready for download.',
            'link' => "/invoices/{$this->invoice->invoice_number}/download",
        ];
    }
}
