<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Jobs\SendCustomerNotificationJob;

class ProcessOrderCreationAutomation
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(OrderCreated $event): void
    {
        $order = $event->order;
        $order->loadMissing('business.automationSettings');

        $settings = $order->business->automationSettings;

        if ($settings && $settings->tracking_enabled) {
            // Dispatch job to send tracking link automatically if enabled
            SendCustomerNotificationJob::dispatch($order, 'ORDER_CREATED');
        }
    }
}
