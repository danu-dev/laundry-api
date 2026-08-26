<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTest extends TestCase
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

    public function test_owner_can_create_customer()
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/customers', [
            'name' => 'Andi Pratama',
            'phone' => '08123456789',
            'notes' => 'VIP Customer',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Andi Pratama');

        $this->assertDatabaseHas('customers', [
            'business_id' => $this->business->id,
            'name' => 'Andi Pratama',
            'phone' => '08123456789',
        ]);
    }

    public function test_owner_cannot_access_other_business_customer()
    {
        $otherBusiness = Business::create(['name' => 'Other Laundry']);
        $otherCustomer = Customer::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Budi',
            'phone' => '08111',
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/customers/'.$otherCustomer->id);

        $response->assertStatus(404);
    }
}
