<?php

namespace Tests\Feature;

use App\Models\CustomerCard;
use App\Models\OutboundOrder;
use App\Models\OutboundOrderLine;
use App\Models\PoolingJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutboundSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_card_belongs_to_logistics_profile(): void
    {
        $card = CustomerCard::factory()->create();
        $this->assertNotNull($card->logistics_profile_id);
        $this->assertNotNull($card->name);
        $this->assertNotNull($card->latitude);
        $this->assertNotNull($card->longitude);
    }

    public function test_outbound_order_relations_and_totals(): void
    {
        $card = CustomerCard::factory()->create();
        $order = OutboundOrder::factory()->create([
            'customer_card_id' => $card->id,
            'logistics_profile_id' => $card->logistics_profile_id,
            'status' => 'drafted',
        ]);

        $line1 = OutboundOrderLine::factory()->create([
            'outbound_order_id' => $order->id,
            'quantity_kg' => 100,
            'rate_per_kg' => 20,
        ]);
        $line2 = OutboundOrderLine::factory()->create([
            'outbound_order_id' => $order->id,
            'quantity_kg' => 50,
            'rate_per_kg' => 30,
        ]);

        $this->assertSame($card->id, $order->customerCard->id);
        $this->assertCount(2, $order->orderLines);
        $this->assertSame(150.0, (float) $order->totalKg());
        $this->assertSame(3500.0, (float) $order->subtotal());
    }

    public function test_pooling_job_outbound_fields(): void
    {
        $job = PoolingJob::factory()->create();
        $this->assertSame('inbound', $job->leg_type);

        $card = CustomerCard::factory()->create();
        $order = OutboundOrder::factory()->create([
            'customer_card_id' => $card->id,
            'logistics_profile_id' => $card->logistics_profile_id,
        ]);
        $job->update(['leg_type' => 'outbound', 'customer_card_id' => $card->id, 'outbound_order_id' => $order->id]);

        $this->assertSame('outbound', $job->fresh()->leg_type);
        $this->assertSame($card->id, $job->fresh()->customerCard->id);
        $this->assertSame($order->id, $job->fresh()->outboundOrder->id);
    }
}