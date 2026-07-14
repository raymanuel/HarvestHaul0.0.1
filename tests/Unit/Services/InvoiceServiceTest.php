<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\InvoiceService;
use App\Models\PoolingJob;

class InvoiceServiceTest extends TestCase
{
    public function test_generate_invoice_number_format(): void
    {
        $service = new InvoiceService();
        $job = new PoolingJob();
        $job->setAttribute('id', 42);

        $invoiceNumber = $service->generateInvoiceNumber($job);

        $this->assertStringStartsWith('HH-INV-', $invoiceNumber);
        $this->assertMatchesRegularExpression('/^HH-INV-\d{8}-\d{5}$/', $invoiceNumber);
        $this->assertStringEndsWith('-00042', $invoiceNumber);
    }

    public function test_generate_invoice_number_pads_small_ids(): void
    {
        $service = new InvoiceService();
        $job = new PoolingJob();
        $job->setAttribute('id', 1);

        $this->assertStringEndsWith('-00001', $service->generateInvoiceNumber($job));
    }

    public function test_generate_invoice_number_pads_large_ids(): void
    {
        $service = new InvoiceService();
        $job = new PoolingJob();
        $job->setAttribute('id', 99999);

        $this->assertStringEndsWith('-99999', $service->generateInvoiceNumber($job));
    }

    public function test_generate_invoice_number_includes_current_date(): void
    {
        $service = new InvoiceService();
        $job = new PoolingJob();
        $job->setAttribute('id', 7);

        $datePart = now()->format('Ymd');
        $invoiceNumber = $service->generateInvoiceNumber($job);

        $this->assertStringContainsString($datePart, $invoiceNumber);
    }

    public function test_generate_invoice_number_unique_for_different_jobs(): void
    {
        $service = new InvoiceService();

        $job1 = new PoolingJob();
        $job1->setAttribute('id', 1);

        $job2 = new PoolingJob();
        $job2->setAttribute('id', 2);

        $inv1 = $service->generateInvoiceNumber($job1);
        $inv2 = $service->generateInvoiceNumber($job2);

        $this->assertNotSame($inv1, $inv2);
    }
}
