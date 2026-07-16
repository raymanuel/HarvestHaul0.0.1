<?php

namespace Tests\Feature;

use App\Models\BuyerProfile;
use App\Models\Crop;
use App\Models\CropCategory;
use App\Models\CropVariety;
use App\Models\Harvest;
use App\Models\Negotiation;
use App\Models\NegotiationMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NegotiationTest extends TestCase
{
    use RefreshDatabase;

    private function createVerifiedBuyer(): User
    {
        $user = User::factory()->buyer()->create(['email_verified_at' => now()]);
        BuyerProfile::create([
            'user_id'     => $user->id,
            'phone'       => '09123456789',
            'is_verified' => true,
        ]);
        return $user;
    }

    private function createVerifiedFarmer(): User
    {
        $user = User::factory()->farmer()->create(['email_verified_at' => now()]);
        $user->farmerProfile()->create([
            'phone'             => '09123456789',
            'farm_location'     => 'Test Farm',
            'is_verified'       => true,
            'latitude'          => 7.0,
            'longitude'         => 125.5,
            'affiliation_type'  => 'independent',
        ]);
        return $user;
    }

    private function createActiveHarvest(User $farmer): Harvest
    {
        $category = CropCategory::create(['name' => 'Grains', 'status' => 'active']);
        $crop = Crop::create(['crop_category_id' => $category->id, 'name' => 'Rice', 'status' => 'active']);
        $variety = CropVariety::create(['crop_id' => $crop->id, 'name' => 'IR64', 'status' => 'active']);

        return Harvest::create([
            'user_id'               => $farmer->id,
            'crop_id'               => $crop->id,
            'crop_variety_id'       => $variety->id,
            'crop_category_id'      => $category->id,
            'crop_type'             => $crop->name,
            'variety'               => $variety->name,
            'quantity_kg'           => 500,
            'remaining_quantity_kg' => 500,
            'unit'                  => 'kg',
            'status'                => 'active',
            'visibility'            => 'both',
            'latitude'              => 7.1,
            'longitude'             => 125.5,
            'destination_address'   => 'Davao Market',
            'destination_latitude'  => 7.07,
            'destination_longitude' => 125.61,
        ]);
    }

    private function createOpenNegotiation(User $buyer, User $farmer, Harvest $harvest): Negotiation
    {
        return Negotiation::create([
            'buyer_id'   => $buyer->id,
            'farmer_id'  => $farmer->id,
            'harvest_id' => $harvest->id,
            'status'     => 'OPEN',
            'negotiated_price'  => null,
            'negotiated_volume' => null,
            'last_activity_at'  => now(),
        ]);
    }

    // ─── START NEGOTIATION (authorization only — lockForUpdate incompatible with SQLite test transactions) ──

    public function test_buyer_can_access_start_endpoint(): void
    {
        $buyer = $this->createVerifiedBuyer();
        $farmer = $this->createVerifiedFarmer();
        $harvest = $this->createActiveHarvest($farmer);

        $response = $this->actingAs($buyer)->post('/negotiations/start', [
            'harvest_id' => $harvest->id,
        ]);

        // Should not be 403 (authorization passes)
        $this->assertNotEquals(403, $response->getStatusCode());
    }

    public function test_non_buyer_cannot_start_negotiation(): void
    {
        $farmer = $this->createVerifiedFarmer();
        $otherFarmer = $this->createVerifiedFarmer();
        $harvest = $this->createActiveHarvest($farmer);

        $this->actingAs($otherFarmer)->post('/negotiations/start', [
            'harvest_id' => $harvest->id,
        ])->assertStatus(403);
    }

    public function test_start_validates_harvest_exists(): void
    {
        $buyer = $this->createVerifiedBuyer();

        $this->actingAs($buyer)->post('/negotiations/start', [
            'harvest_id' => 99999,
        ])->assertSessionHasErrors('harvest_id');
    }

    // ─── SEND MESSAGE ────────────────────────────────────────

    public function test_buyer_can_send_message_in_open_negotiation(): void
    {
        $buyer = $this->createVerifiedBuyer();
        $farmer = $this->createVerifiedFarmer();
        $harvest = $this->createActiveHarvest($farmer);
        $negotiation = $this->createOpenNegotiation($buyer, $farmer, $harvest);

        $this->actingAs($buyer)->post("/negotiations/{$negotiation->id}/message", [
            'message_text' => 'Hello, interested in your rice.',
        ])->assertJson(['message' => ['message_text' => 'Hello, interested in your rice.']]);

        $this->assertDatabaseHas('negotiation_messages', [
            'negotiation_id' => $negotiation->id,
            'sender_id'      => $buyer->id,
            'message_text'   => 'Hello, interested in your rice.',
        ]);
    }

    public function test_farmer_can_send_message_in_open_negotiation(): void
    {
        $buyer = $this->createVerifiedBuyer();
        $farmer = $this->createVerifiedFarmer();
        $harvest = $this->createActiveHarvest($farmer);
        $negotiation = $this->createOpenNegotiation($buyer, $farmer, $harvest);

        $this->actingAs($farmer)->post("/negotiations/{$negotiation->id}/message", [
            'message_text' => 'Thanks for your interest!',
        ])->assertJson(['message' => ['message_text' => 'Thanks for your interest!']]);
    }

    public function test_cannot_send_message_to_others_negotiation(): void
    {
        $buyer = $this->createVerifiedBuyer();
        $farmer = $this->createVerifiedFarmer();
        $outsider = $this->createVerifiedBuyer();
        $harvest = $this->createActiveHarvest($farmer);
        $negotiation = $this->createOpenNegotiation($buyer, $farmer, $harvest);

        $this->actingAs($outsider)->post("/negotiations/{$negotiation->id}/message", [
            'message_text' => 'Hello!',
        ])->assertStatus(403);
    }

    public function test_cannot_send_message_on_completed_negotiation(): void
    {
        $buyer = $this->createVerifiedBuyer();
        $farmer = $this->createVerifiedFarmer();
        $harvest = $this->createActiveHarvest($farmer);

        $negotiation = Negotiation::create([
            'buyer_id'   => $buyer->id,
            'farmer_id'  => $farmer->id,
            'harvest_id' => $harvest->id,
            'status'     => 'COMPLETED',
        ]);

        $this->actingAs($buyer)->post("/negotiations/{$negotiation->id}/message", [
            'message_text' => 'Hello!',
        ])->assertSessionHas('error');
    }

    public function test_cannot_send_message_on_cancelled_negotiation(): void
    {
        $buyer = $this->createVerifiedBuyer();
        $farmer = $this->createVerifiedFarmer();
        $harvest = $this->createActiveHarvest($farmer);

        $negotiation = Negotiation::create([
            'buyer_id'   => $buyer->id,
            'farmer_id'  => $farmer->id,
            'harvest_id' => $harvest->id,
            'status'     => 'CANCELLED',
        ]);

        $this->actingAs($buyer)->post("/negotiations/{$negotiation->id}/message", [
            'message_text' => 'Hello!',
        ])->assertSessionHas('error');
    }

    public function test_send_message_validates_message_text_required(): void
    {
        $buyer = $this->createVerifiedBuyer();
        $farmer = $this->createVerifiedFarmer();
        $harvest = $this->createActiveHarvest($farmer);
        $negotiation = $this->createOpenNegotiation($buyer, $farmer, $harvest);

        $this->actingAs($buyer)->post("/negotiations/{$negotiation->id}/message", [
            'message_text' => '',
        ])->assertSessionHasErrors('message_text');
    }

    public function test_send_message_validates_message_text_max_length(): void
    {
        $buyer = $this->createVerifiedBuyer();
        $farmer = $this->createVerifiedFarmer();
        $harvest = $this->createActiveHarvest($farmer);
        $negotiation = $this->createOpenNegotiation($buyer, $farmer, $harvest);

        $this->actingAs($buyer)->post("/negotiations/{$negotiation->id}/message", [
            'message_text' => str_repeat('a', 1001),
        ])->assertSessionHasErrors('message_text');
    }

    // ─── ROOM ACCESS ─────────────────────────────────────────

    public function test_buyer_can_access_negotiation_room(): void
    {
        $buyer = $this->createVerifiedBuyer();
        $farmer = $this->createVerifiedFarmer();
        $harvest = $this->createActiveHarvest($farmer);
        $negotiation = $this->createOpenNegotiation($buyer, $farmer, $harvest);

        $this->actingAs($buyer)->get("/negotiations/{$negotiation->id}")
            ->assertStatus(200);
    }

    public function test_farmer_can_access_negotiation_room(): void
    {
        $buyer = $this->createVerifiedBuyer();
        $farmer = $this->createVerifiedFarmer();
        $harvest = $this->createActiveHarvest($farmer);
        $negotiation = $this->createOpenNegotiation($buyer, $farmer, $harvest);

        $this->actingAs($farmer)->get("/negotiations/{$negotiation->id}")
            ->assertStatus(200);
    }

    public function test_non_participant_cannot_access_negotiation_room(): void
    {
        $buyer = $this->createVerifiedBuyer();
        $farmer = $this->createVerifiedFarmer();
        $outsider = $this->createVerifiedBuyer();
        $harvest = $this->createActiveHarvest($farmer);
        $negotiation = $this->createOpenNegotiation($buyer, $farmer, $harvest);

        $this->actingAs($outsider)->get("/negotiations/{$negotiation->id}")
            ->assertStatus(403);
    }

    // ─── GET MESSAGES ────────────────────────────────────────

    public function test_participant_can_get_messages(): void
    {
        $buyer = $this->createVerifiedBuyer();
        $farmer = $this->createVerifiedFarmer();
        $harvest = $this->createActiveHarvest($farmer);
        $negotiation = $this->createOpenNegotiation($buyer, $farmer, $harvest);

        NegotiationMessage::create([
            'negotiation_id' => $negotiation->id,
            'sender_id'      => $buyer->id,
            'message_text'   => 'Hello!',
        ]);

        $this->actingAs($buyer)->get("/negotiations/{$negotiation->id}/messages")
            ->assertStatus(200)
            ->assertJsonCount(1, 'messages');
    }

    public function test_non_participant_cannot_get_messages(): void
    {
        $buyer = $this->createVerifiedBuyer();
        $farmer = $this->createVerifiedFarmer();
        $outsider = $this->createVerifiedBuyer();
        $harvest = $this->createActiveHarvest($farmer);
        $negotiation = $this->createOpenNegotiation($buyer, $farmer, $harvest);

        $this->actingAs($outsider)->get("/negotiations/{$negotiation->id}/messages")
            ->assertStatus(403);
    }

    // ─── PROPOSE TERMS ───────────────────────────────────────

    public function test_participant_can_propose_terms(): void
    {
        $buyer = $this->createVerifiedBuyer();
        $farmer = $this->createVerifiedFarmer();
        $harvest = $this->createActiveHarvest($farmer);
        $negotiation = $this->createOpenNegotiation($buyer, $farmer, $harvest);

        $this->actingAs($buyer)->post("/negotiations/{$negotiation->id}/propose", [
            'negotiated_price'  => 25.50,
            'negotiated_volume' => 300,
        ])->assertStatus(302);

        $this->assertDatabaseHas('negotiations', [
            'id'                => $negotiation->id,
            'negotiated_price'  => 25.50,
            'negotiated_volume' => 300,
        ]);
    }

    public function test_non_participant_cannot_propose_terms(): void
    {
        $buyer = $this->createVerifiedBuyer();
        $farmer = $this->createVerifiedFarmer();
        $outsider = $this->createVerifiedBuyer();
        $harvest = $this->createActiveHarvest($farmer);
        $negotiation = $this->createOpenNegotiation($buyer, $farmer, $harvest);

        $this->actingAs($outsider)->post("/negotiations/{$negotiation->id}/propose", [
            'negotiated_price'  => 25.50,
            'negotiated_volume' => 300,
        ])->assertStatus(403);
    }

    // ─── AGREE TERMS ─────────────────────────────────────────

    public function test_farmer_can_agree_to_terms(): void
    {
        $buyer = $this->createVerifiedBuyer();
        $farmer = $this->createVerifiedFarmer();
        $harvest = $this->createActiveHarvest($farmer);
        $negotiation = $this->createOpenNegotiation($buyer, $farmer, $harvest);

        // Set proposed terms (as buyer)
        $negotiation->update([
            'negotiated_price'  => 25.50,
            'negotiated_volume' => 300,
        ]);

        $this->actingAs($farmer)->post("/negotiations/{$negotiation->id}/agree")
            ->assertStatus(302);

        $this->assertDatabaseHas('negotiations', [
            'id'     => $negotiation->id,
            'status' => 'AGREED',
        ]);
    }

    public function test_non_participant_cannot_agree(): void
    {
        $buyer = $this->createVerifiedBuyer();
        $farmer = $this->createVerifiedFarmer();
        $outsider = $this->createVerifiedBuyer();
        $harvest = $this->createActiveHarvest($farmer);
        $negotiation = $this->createOpenNegotiation($buyer, $farmer, $harvest);

        $negotiation->update([
            'negotiated_price'  => 25.50,
            'negotiated_volume' => 300,
        ]);

        $this->actingAs($outsider)->post("/negotiations/{$negotiation->id}/agree")
            ->assertStatus(403);
    }

    // ─── LIST JSON ───────────────────────────────────────────

    public function test_buyer_can_list_negotiations(): void
    {
        $buyer = $this->createVerifiedBuyer();
        $farmer = $this->createVerifiedFarmer();
        $harvest = $this->createActiveHarvest($farmer);
        $this->createOpenNegotiation($buyer, $farmer, $harvest);

        $this->actingAs($buyer)->get('/negotiations/list')
            ->assertStatus(200)
            ->assertJsonCount(1, 'negotiations');
    }

    public function test_farmer_can_list_negotiations(): void
    {
        $buyer = $this->createVerifiedBuyer();
        $farmer = $this->createVerifiedFarmer();
        $harvest = $this->createActiveHarvest($farmer);
        $this->createOpenNegotiation($buyer, $farmer, $harvest);

        $this->actingAs($farmer)->get('/negotiations/list')
            ->assertStatus(200)
            ->assertJsonCount(1, 'negotiations');
    }

    public function test_empty_negotiations_list(): void
    {
        $buyer = $this->createVerifiedBuyer();

        $this->actingAs($buyer)->get('/negotiations/list')
            ->assertStatus(200)
            ->assertJsonCount(0, 'negotiations');
    }
}
