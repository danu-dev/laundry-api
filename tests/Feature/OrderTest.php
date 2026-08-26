<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Customer;
use App\Models\Extra;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Business $business;

    private Customer $customer;

    private Service $service;

    private Extra $extra;

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
        $this->customer = Customer::create(['business_id' => $this->business->id, 'name' => 'Andi']);
        $this->service = Service::create([
            'business_id' => $this->business->id,
            'name' => 'Wash + Iron',
            'price' => 10000,
            'pricing_type' => 'PER_KG',
        ]);
        $this->extra = Extra::create([
            'business_id' => $this->business->id,
            'name' => 'Premium fragrance',
            'price' => 3000,
        ]);
    }

    public function test_owner_can_create_order_with_server_side_calculation()
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/orders', [
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'weight' => 4.5,
            'extras' => [$this->extra->id],
            'payment' => [
                'amount' => 50000,
                'method' => 'CASH',
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.total', 48000) // 10000 * 4.5 = 45000 + 3000 = 48000
            ->assertJsonPath('data.payment_status', 'PAID');

        $this->assertNotNull($response->json('data.tracking_url'));

        $this->assertDatabaseHas('orders', [
            'business_id' => $this->business->id,
            'subtotal' => 45000,
            'extras_total' => 3000,
            'total' => 48000,
            'payment_status' => 'PAID',
            'status' => 'NEW',
        ]);

        $this->assertDatabaseHas('payments', [
            'amount' => 48000, // For CASH overpayment, it registers total only
            'method' => 'CASH',
        ]);
    }

    public function test_owner_can_transition_order_status_validly()
    {
        $order = Order::create([
            'business_id' => $this->business->id,
            'customer_id' => $this->customer->id,
            'order_number' => 'LD-TEST-001',
            'status' => Order::STATUS_NEW,
        ]);

        $response = $this->actingAs($this->user)->patchJson("/api/v1/orders/{$order->id}/status", [
            'status' => Order::STATUS_WASHING,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', Order::STATUS_WASHING);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => Order::STATUS_WASHING,
        ]);
    }

    public function test_owner_cannot_make_invalid_status_transition()
    {
        $order = Order::create([
            'business_id' => $this->business->id,
            'customer_id' => $this->customer->id,
            'order_number' => 'LD-TEST-002',
            'status' => Order::STATUS_NEW,
        ]);

        $response = $this->actingAs($this->user)->patchJson("/api/v1/orders/{$order->id}/status", [
            'status' => Order::STATUS_COMPLETED,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'INVALID_STATE_TRANSITION');
    }
}
