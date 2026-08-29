<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\InvoiceStatus;
use Illuminate\Console\Command;

class MarkOverdueInvoices extends Command
{
    protected $signature = 'invoices:mark-overdue';
    protected $description = 'Mark sent invoices past their due date as overdue.';

    public function handle(): int
    {
        $count = Invoice::where('status', InvoiceStatus::SENT->value)
            ->where('due_at', '<', now())
            ->update(['status' => InvoiceStatus::OVERDUE->value]);

        $this->info("Marked {$count} invoices as overdue.");

        return self::SUCCESS;
    }
}
