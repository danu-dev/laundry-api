<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_creates_business()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'business_name' => 'John Laundry',
            'phone' => '081234567890',
            'timezone' => 'Asia/Jakarta',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user' => [
                        'id', 'name', 'email', 'business',
                    ],
                    'token',
                ],
            ]);

        $this->assertDatabaseHas('users', ['email' => 'john@example.com']);
        $this->assertDatabaseHas('businesses', ['name' => 'John Laundry', 'timezone' => 'Asia/Jakarta']);

        $user = User::where('email', 'john@example.com')->first();
        $this->assertNotNull($user->business_id);

        $this->assertDatabaseHas('automation_settings', [
            'business_id' => $user->business_id,
            'tracking_enabled' => 1,
        ]);
    }

    public function test_user_can_login()
    {
        $business = Business::create(['name' => 'Test Business', 'timezone' => 'UTC']);
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'business_id' => $business->id,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user', 'token',
                ],
            ]);
    }

    public function test_authenticated_user_can_get_profile()
    {
        $business = Business::create(['name' => 'Test Business', 'timezone' => 'UTC']);
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'business_id' => $business->id,
        ]);

        $response = $this->actingAs($user)->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJsonPath('data.user.email', 'test@example.com')
            ->assertJsonPath('data.user.business.name', 'Test Business');
    }
}
