<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceTest extends TestCase
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

    public function test_owner_can_create_service()
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/services', [
            'name' => 'Wash + Iron',
            'price' => 10000,
            'estimated_duration_minutes' => 2880, // 2 days
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Wash + Iron')
            ->assertJsonPath('data.price', 10000);

        $this->assertDatabaseHas('services', [
            'business_id' => $this->business->id,
            'name' => 'Wash + Iron',
            'price' => 10000,
        ]);
    }
}
