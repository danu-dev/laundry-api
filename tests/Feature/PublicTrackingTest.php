<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PublicTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_track_order_with_valid_token()
    {
        $business = Business::create(['name' => 'Laundry Express', 'timezone' => 'Asia/Jakarta', 'phone' => '12345']);
        $customer = Customer::create(['business_id' => $business->id, 'name' => 'Andi Pratama', 'phone' => '0812']);

        $rawToken = Str::random(32);
        $tokenHash = hash('sha256', $rawToken);

        $order = Order::create([
            'business_id' => $business->id,
            'customer_id' => $customer->id,
            'order_number' => 'LD-TEST-001',
            'total' => 48000,
            'status' => Order::STATUS_WASHING,
            'payment_status' => Order::PAYMENT_UNPAID,
            'tracking_token_hash' => $tokenHash,
        ]);

        $order->statusHistories()->create(['to_status' => Order::STATUS_NEW]);
        $order->statusHistories()->create(['to_status' => Order::STATUS_WASHING]);

        $response = $this->getJson("/api/v1/public/orders/{$rawToken}");

        $response->assertStatus(200)
            ->assertJsonPath('data.business_name', 'Laundry Express')
            ->assertJsonPath('data.order_number', 'LD-TEST-001')
            ->assertJsonPath('data.status', 'WASHING')
            ->assertJsonPath('data.total', 48000)
            ->assertJsonCount(2, 'data.status_history')
            ->assertJsonMissing(['customer_name' => 'Andi Pratama']) // Ensure PII is reasonably protected
            ->assertJsonMissing(['internal_id' => $order->id]);
    }

    public function test_invalid_tracking_token_returns_generic_404()
    {
        $response = $this->getJson('/api/v1/public/orders/invalid-token-123');

        $response->assertStatus(404)
            ->assertJsonPath('error.code', 'ORDER_NOT_FOUND');
    }
}
