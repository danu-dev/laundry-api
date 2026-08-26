<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::create(['name' => 'Laundry Express', 'timezone' => 'Asia/Jakarta']);
        $this->user = User::create([
            'name' => 'Owner',
            'email' => 'owner@example.com',
            'password' => 'password',
            'business_id' => $this->business->id,
        ]);
    }

    public function test_dashboard_metrics_and_needs_attention(): void
    {
        $customer = Customer::create(['business_id' => $this->business->id, 'name' => 'Budi']);
        $service = Service::create([
            'business_id' => $this->business->id,
            'name' => 'Express Wash',
            'price' => 20000,
            'pricing_type' => 'PER_KG',
        ]);

        // Create an overdue order
        $overdueOrder = Order::create([
            'business_id' => $this->business->id,
            'customer_id' => $customer->id,
            'order_number' => 'LD-260826-001',
            'status' => Order::STATUS_WASHING,
            'payment_status' => Order::PAYMENT_UNPAID,
            'subtotal' => 40000,
            'extras_total' => 0,
            'total' => 40000,
            'estimated_completion_at' => now()->subHours(2),
            'tracking_token_hash' => hash('sha256', 'token-1'),
        ]);

        // Create a ready order
        $readyOrder = Order::create([
            'business_id' => $this->business->id,
            'customer_id' => $customer->id,
            'order_number' => 'LD-260826-002',
            'status' => Order::STATUS_READY,
            'payment_status' => Order::PAYMENT_PAID,
            'subtotal' => 20000,
            'extras_total' => 0,
            'total' => 20000,
            'ready_at' => now(),
            'tracking_token_hash' => hash('sha256', 'token-2'),
        ]);

        // Create payment for today
        Payment::create([
            'order_id' => $readyOrder->id,
            'amount' => 20000,
            'method' => Payment::METHOD_CASH,
            'status' => Payment::STATUS_PAID,
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/dashboard');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.today.revenue', 60000)
            ->assertJsonPath('data.today.orders', 2)
            ->assertJsonPath('data.attention.overdue', 1)
            ->assertJsonPath('data.attention.ready_for_pickup', 1)
            ->assertJsonPath('data.attention.unpaid', 1);
    }
}
