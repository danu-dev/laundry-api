<?php

namespace App\Listeners;

use App\Events\OrderReady;
use App\Jobs\SendCustomerNotificationJob;

class SendOrderReadyNotification
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
    public function handle(OrderReady $event): void
    {
        $order = $event->order;
        $order->loadMissing('business.automationSettings');

        $settings = $order->business->automationSettings;

        if ($settings && $settings->ready_notification_enabled) {
            // Dispatch job to send notification asynchronously
            SendCustomerNotificationJob::dispatch($order, 'ORDER_READY');
        }
    }
}
