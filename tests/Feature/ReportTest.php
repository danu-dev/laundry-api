<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
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

    public function test_reports_summary_aggregations(): void
    {
        $customer = Customer::create(['business_id' => $this->business->id, 'name' => 'Siti']);
        $service = Service::create([
            'business_id' => $this->business->id,
            'name' => 'Bedcover Heavy',
            'price' => 35000,
            'pricing_type' => 'PER_PIECE',
        ]);

        $order = Order::create([
            'business_id' => $this->business->id,
            'customer_id' => $customer->id,
            'order_number' => 'LD-260826-003',
            'status' => Order::STATUS_READY,
            'payment_status' => Order::PAYMENT_PARTIAL,
            'subtotal' => 70000,
            'extras_total' => 0,
            'total' => 70000,
            'tracking_token_hash' => hash('sha256', 'token-3'),
            'created_at' => now(),
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'service_id' => $service->id,
            'service_name_snapshot' => $service->name,
            'quantity' => 2,
            'unit_price' => 35000,
            'subtotal' => 70000,
        ]);

        Payment::create([
            'order_id' => $order->id,
            'amount' => 35000,
            'method' => Payment::METHOD_QRIS,
            'status' => Payment::STATUS_PAID,
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/reports/summary?date_from='.now()->subDays(1)->format('Y-m-d').'&date_to='.now()->addDays(1)->format('Y-m-d'));

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.orders', 1)
            ->assertJsonPath('data.order_value', 70000)
            ->assertJsonPath('data.collected_payment', 35000)
            ->assertJsonPath('data.outstanding_payment', 35000)
            ->assertJsonPath('data.popular_services.0.service_name_snapshot', 'Bedcover Heavy')
            ->assertJsonPath('data.popular_services.0.count', 1);
    }
}
