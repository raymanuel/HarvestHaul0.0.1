<?php

namespace App\Console\Commands;

use App\Models\PoolingJob;
use App\Services\InvoiceService;
use Illuminate\Console\Command;

class GenerateInvoices extends Command
{
    protected $signature = 'invoices:generate {--job= : Specific job ID to invoice}';
    protected $description = 'Auto-generate invoices for completed pooling jobs.';

    public function handle(InvoiceService $invoiceService): int
    {
        $jobId = $this->option('job');

        if ($jobId) {
            $job = PoolingJob::find($jobId);
            if (!$job) {
                $this->error("Job #{$jobId} not found.");
                return self::FAILURE;
            }
            $invoice = $invoiceService->getOrCreateInvoice($job);
            $this->info("Invoice #{$invoice->invoice_number} generated for Route #{$job->id}.");
            return self::SUCCESS;
        }

        $jobs = PoolingJob::where('status', 'completed')
            ->whereDoesntHave('invoices')
            ->get();

        if ($jobs->isEmpty()) {
            $this->info('No completed jobs pending invoice generation.');
            return self::SUCCESS;
        }

        $count = 0;
        foreach ($jobs as $job) {
            $invoice = $invoiceService->generateInvoice($job);
            $this->info("Invoice #{$invoice->invoice_number} generated for Route #{$job->id}.");
            $count++;
        }

        $this->info("Generated {$count} invoice(s).");

        return self::SUCCESS;
    }
}
