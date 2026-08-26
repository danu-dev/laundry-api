<?php

namespace Tests\Feature;

use App\Events\OrderReady;
use App\Jobs\SendCustomerNotificationJob;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AutomationTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_ready_event_dispatches_notification_job()
    {
        Queue::fake();

        $business = Business::create(['name' => 'Laundry']);
        $business->automationSettings()->create([
            'ready_notification_enabled' => true,
        ]);
        $customer = Customer::create(['business_id' => $business->id, 'name' => 'Andi', 'phone' => '0812']);
        $order = Order::create([
            'business_id' => $business->id,
            'customer_id' => $customer->id,
            'order_number' => 'LD-TEST',
            'status' => Order::STATUS_READY,
        ]);

        OrderReady::dispatch($order);

        Queue::assertPushed(SendCustomerNotificationJob::class, function ($job) use ($order) {
            return $job->order->id === $order->id && $job->type === 'ORDER_READY';
        });
    }

    public function test_order_ready_event_does_not_dispatch_if_setting_disabled()
    {
        Queue::fake();

        $business = Business::create(['name' => 'Laundry']);
        $business->automationSettings()->create([
            'ready_notification_enabled' => false,
        ]);
        $customer = Customer::create(['business_id' => $business->id, 'name' => 'Andi', 'phone' => '0812']);
        $order = Order::create([
            'business_id' => $business->id,
            'customer_id' => $customer->id,
            'order_number' => 'LD-TEST',
            'status' => Order::STATUS_READY,
        ]);

        OrderReady::dispatch($order);

        Queue::assertNotPushed(SendCustomerNotificationJob::class);
    }
}
