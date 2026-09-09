<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class InvoiceReady extends Notification implements ShouldQueue
{
    use Queueable;

    public Invoice $invoice;
    public ?float $farmerShare = null;

    public function __construct(Invoice $invoice, ?float $farmerShare = null)
    {
        $this->invoice = $invoice;
        $this->farmerShare = $farmerShare;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    protected function displayAmount(): string
    {
        return '₱' . number_format($this->farmerShare ?? $this->invoice->total_amount, 2);
    }

    public function toMail($notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject("Hauling Invoice {$this->invoice->invoice_number} from HarvestHaul")
            ->line("Your hauling invoice #{$this->invoice->invoice_number} has been generated.")
            ->line('This invoice covers hauling services only.');

        if ($this->farmerShare !== null) {
            $message->line('Your share: ' . $this->displayAmount());
        } else {
            $message->line('Total amount: ' . $this->displayAmount());
        }

        return $message->action('Download Invoice', url("/invoices/{$this->invoice->invoice_number}/download"));
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => "Hauling Invoice #{$this->invoice->invoice_number} Ready",
            'message' => "Hauling invoice #{$this->invoice->invoice_number} for " . $this->displayAmount() . ' is ready for download.',
            'link' => "/invoices/{$this->invoice->invoice_number}/download",
            'category' => 'payments',
        ];
    }
}
