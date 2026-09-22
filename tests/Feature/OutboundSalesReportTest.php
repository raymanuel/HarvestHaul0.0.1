<?php

namespace Tests\Feature;

use App\Models\CustomerCard;
use App\Models\OutboundOrder;
use App\Models\OutboundOrderLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OutboundSalesReportTest extends TestCase
{
    use RefreshDatabase;

    private function coop(): User
    {
        $user = User::factory()->logisticsPartner()->create(['email_verified_at' => now()]);
        $user->logisticsProfile()->create([
            'company_name' => 'GenSan AgCoop',
            'business_permit_no' => 'BL-'.strtoupper(Str::random(6)),
            'phone' => '09171234567',
            'is_verified' => true,
            'logistics_type' => 'cooperative',
        ]);

        return $user;
    }

    private function independent(): User
    {
        $user = User::factory()->logisticsPartner()->create(['email_verified_at' => now()]);
        $user->logisticsProfile()->create([
            'company_name' => 'Solo Hauler',
            'business_permit_no' => 'BL-'.strtoupper(Str::random(6)),
            'phone' => '09171112222',
            'is_verified' => true,
            'logistics_type' => 'company',
        ]);

        return $user;
    }

    private function cardFor(User $coop, string $name): CustomerCard
    {
        return CustomerCard::factory()->create([
            'logistics_profile_id' => $coop->logisticsProfile->id,
            'name' => $name,
        ]);
    }

    private function dispatchedOrder(User $coop, CustomerCard $card, array $overrides = []): OutboundOrder
    {
        $order = OutboundOrder::factory()->create(array_merge([
            'customer_card_id' => $card->id,
            'logistics_profile_id' => $coop->logisticsProfile->id,
            'status' => 'completed',
            'total_kg' => 150,
            'total_amount' => 3000,
            'dispatched_at' => now()->subDays(2)->startOfDay(),
        ], $overrides));
        OutboundOrderLine::factory()->create([
            'outbound_order_id' => $order->id,
            'crop_type' => 'Banana',
            'quantity_kg' => 100,
            'rate_per_kg' => 20,
            'subtotal' => 2000,
        ]);
        OutboundOrderLine::factory()->create([
            'outbound_order_id' => $order->id,
            'crop_type' => 'Tomato',
            'quantity_kg' => 50,
            'rate_per_kg' => 20,
            'subtotal' => 1000,
        ]);

        return $order;
    }

    public function test_report_page_renders_dispatched_sales_for_coop(): void
    {
        $coop = $this->coop();
        $card = $this->cardFor($coop, 'KCC Fresh Mart');
        $this->dispatchedOrder($coop, $card);

        $response = $this->actingAs($coop)->get(route('coop.reports.outbound-sales'));

        $response->assertOk();
        $response->assertSee('Outbound Sales Report');
        $response->assertSee('Total Sales');
        $response->assertSee('KCC Fresh Mart');
        $response->assertSee('Banana');
        $response->assertSee('₱3,000.00');
    }

    public function test_report_only_includes_the_coops_own_orders(): void
    {
        $coop = $this->coop();
        $other = $this->coop();
        $this->dispatchedOrder($coop, $this->cardFor($coop, 'Own Customer'));
        $this->dispatchedOrder($other, $this->cardFor($other, 'Other Coop Customer'));

        $response = $this->actingAs($coop)->get(route('coop.reports.outbound-sales'));

        $response->assertOk();
        $response->assertSee('Own Customer');
        $response->assertDontSee('Other Coop Customer');
        $response->assertSee('₱3,000.00');
    }

    public function test_drafted_and_cancelled_orders_are_excluded(): void
    {
        $coop = $this->coop();
        $card = $this->cardFor($coop, 'Test Customer');

        $this->dispatchedOrder($coop, $card);
        OutboundOrder::factory()->create([
            'customer_card_id' => $card->id,
            'logistics_profile_id' => $coop->logisticsProfile->id,
            'status' => 'drafted',
            'total_kg' => 999,
            'total_amount' => 99999,
            'dispatched_at' => null,
        ]);
        OutboundOrder::factory()->create([
            'customer_card_id' => $card->id,
            'logistics_profile_id' => $coop->logisticsProfile->id,
            'status' => 'cancelled',
            'total_kg' => 777,
            'total_amount' => 77777,
            'dispatched_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($coop)->get(route('coop.reports.outbound-sales'));

        $response->assertOk();
        $response->assertSee('₱3,000.00');
        $response->assertDontSee('₱99,999.00');
        $response->assertDontSee('₱77,777.00');
    }

    public function test_report_filters_by_customer_and_crop(): void
    {
        $coop = $this->coop();
        $cardA = $this->cardFor($coop, 'Customer A');
        $cardB = $this->cardFor($coop, 'Customer B');
        $this->dispatchedOrder($coop, $cardA);
        $this->dispatchedOrder($coop, $cardB);

        $byCustomer = $this->actingAs($coop)->get(route('coop.reports.outbound-sales', ['customer_card_id' => $cardA->id]));
        $byCustomer->assertOk();
        $byCustomer->assertSee('Customer A');
        $byCustomer->assertSee('₱3,000.00');
        $byCustomer->assertDontSee('₱6,000.00');

        $byCrop = $this->actingAs($coop)->get(route('coop.reports.outbound-sales', ['crop_type' => 'Banana']));
        $byCrop->assertOk();
        $byCrop->assertSee('Banana');
        $byCrop->assertSee('₱4,000.00');
        $byCrop->assertDontSee('₱1,000.00');
    }

    public function test_csv_download_contains_order_lines(): void
    {
        $coop = $this->coop();
        $card = $this->cardFor($coop, 'KCC Fresh Mart');
        $this->dispatchedOrder($coop, $card);

        $response = $this->actingAs($coop)->get(route('coop.reports.outbound-sales.download'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('Banana', $response->streamedContent());
        $this->assertStringContainsString('KCC Fresh Mart', $response->streamedContent());
    }

    public function test_independent_logistics_partner_is_blocked(): void
    {
        $independent = $this->independent();

        $this->actingAs($independent)->get(route('coop.reports.outbound-sales'))->assertForbidden();
        $this->actingAs($independent)->get(route('coop.reports.outbound-sales.download'))->assertForbidden();
    }
}
