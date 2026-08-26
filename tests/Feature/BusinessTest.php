<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BusinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_get_business_details()
    {
        $business = Business::create(['name' => 'Laundry Express', 'timezone' => 'Asia/Jakarta']);
        $user = User::create([
            'name' => 'Owner',
            'email' => 'owner@example.com',
            'password' => Hash::make('password'),
            'business_id' => $business->id,
        ]);

        $response = $this->actingAs($user)->getJson('/api/v1/business');

        $response->assertStatus(200)
            ->assertJsonPath('data.business.name', 'Laundry Express')
            ->assertJsonPath('data.business.timezone', 'Asia/Jakarta');
    }

    public function test_owner_can_update_business_details()
    {
        $business = Business::create(['name' => 'Laundry Express', 'timezone' => 'Asia/Jakarta']);
        $user = User::create([
            'name' => 'Owner',
            'email' => 'owner@example.com',
            'password' => Hash::make('password'),
            'business_id' => $business->id,
        ]);

        $response = $this->actingAs($user)->patchJson('/api/v1/business', [
            'name' => 'Laundry Pro',
            'address' => 'New Address',
            'receipt_footer_message' => 'Thank you for washing with us!',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.business.name', 'Laundry Pro')
            ->assertJsonPath('data.business.receipt_footer_message', 'Thank you for washing with us!');

        $this->assertDatabaseHas('businesses', [
            'id' => $business->id,
            'name' => 'Laundry Pro',
            'receipt_footer_message' => 'Thank you for washing with us!',
        ]);
    }
}
