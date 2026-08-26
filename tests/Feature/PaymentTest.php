<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Business $business;

    private Customer $customer;

    private Order $order;

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
        $this->order = Order::create([
            'business_id' => $this->business->id,
            'customer_id' => $this->customer->id,
            'order_number' => 'LD-TEST-001',
            'total' => 48000,
            'payment_status' => Order::PAYMENT_UNPAID,
        ]);
    }

    public function test_owner_can_record_partial_payment()
    {
        $response = $this->actingAs($this->user)->postJson("/api/v1/orders/{$this->order->id}/payments", [
            'amount' => 20000,
            'method' => 'TRANSFER',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.payment_status', 'PARTIAL');

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->id,
            'payment_status' => 'PARTIAL',
        ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $this->order->id,
            'amount' => 20000,
            'method' => 'TRANSFER',
        ]);
    }

    public function test_owner_can_record_full_cash_payment_with_change()
    {
        $response = $this->actingAs($this->user)->postJson("/api/v1/orders/{$this->order->id}/payments", [
            'amount' => 50000,
            'method' => 'CASH',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.payment_status', 'PAID');

        // Should only record 48000
        $this->assertDatabaseHas('payments', [
            'order_id' => $this->order->id,
            'amount' => 48000,
            'method' => 'CASH',
        ]);
    }

    public function test_cannot_pay_fully_paid_order()
    {
        $this->order->update(['payment_status' => Order::PAYMENT_PAID]);

        $response = $this->actingAs($this->user)->postJson("/api/v1/orders/{$this->order->id}/payments", [
            'amount' => 20000,
            'method' => 'TRANSFER',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'ORDER_ALREADY_PAID');
    }
}
