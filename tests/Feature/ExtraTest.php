<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExtraTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::create(['name' => 'Laundry Express']);
        $this->user = User::create([
            'name' => 'Owner',
            'email' => 'owner@example.com',
            'password' => 'password',
            'business_id' => $this->business->id,
        ]);
    }

    public function test_owner_can_create_extra()
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/extras', [
            'name' => 'Premium fragrance',
            'price' => 3000,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Premium fragrance')
            ->assertJsonPath('data.price', 3000);

        $this->assertDatabaseHas('extras', [
            'business_id' => $this->business->id,
            'name' => 'Premium fragrance',
            'price' => 3000,
        ]);
    }
}
