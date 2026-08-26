<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\InventoryItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::create(['name' => 'Laundry Hub', 'timezone' => 'Asia/Jakarta']);
        $this->user = User::create([
            'name' => 'Owner',
            'email' => 'owner@example.com',
            'password' => 'password',
            'business_id' => $this->business->id,
        ]);
    }

    public function test_can_list_inventory_items(): void
    {
        InventoryItem::create([
            'business_id' => $this->business->id,
            'name' => 'Detergent Liquid',
            'unit' => 'LITER',
            'quantity' => 15.5,
            'minimum_quantity' => 5.0,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/inventory');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Detergent Liquid')
            ->assertJsonPath('data.0.quantity', 15.5)
            ->assertJsonPath('data.0.is_low_stock', false);
    }

    public function test_can_create_inventory_item(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/inventory', [
            'name' => 'Fabric Softener',
            'unit' => 'BOTTLE',
            'quantity' => 2.0,
            'minimum_quantity' => 5.0,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Fabric Softener')
            ->assertJsonPath('data.is_low_stock', true);

        $this->assertDatabaseHas('inventory_items', [
            'business_id' => $this->business->id,
            'name' => 'Fabric Softener',
        ]);
    }

    public function test_can_adjust_inventory_stock(): void
    {
        $item = InventoryItem::create([
            'business_id' => $this->business->id,
            'name' => 'Plastic Bags',
            'unit' => 'PACK',
            'quantity' => 10,
            'minimum_quantity' => 3,
        ]);

        $response = $this->actingAs($this->user)->postJson("/api/v1/inventory/{$item->id}/adjust", [
            'quantity_delta' => -4,
            'reason' => 'Daily usage',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.quantity', 6);

        $this->assertEquals(6, $item->fresh()->quantity);
    }

    public function test_cannot_access_other_business_inventory(): void
    {
        $otherBusiness = Business::create(['name' => 'Competitor Laundry', 'timezone' => 'Asia/Jakarta']);
        $otherItem = InventoryItem::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Secret Detergent',
            'unit' => 'KG',
            'quantity' => 100,
            'minimum_quantity' => 10,
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/inventory/{$otherItem->id}");

        $response->assertStatus(404);
    }
}
