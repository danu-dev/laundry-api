<?php

namespace App\Jobs;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckPickupRemindersJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        // Query orders that are READY
        $orders = Order::with('business.automationSettings')
            ->where('status', Order::STATUS_READY)
            ->whereNotNull('ready_at')
            ->get();

        foreach ($orders as $order) {
            $settings = $order->business->automationSettings;

            if (! $settings || ! $settings->pickup_reminder_enabled) {
                continue;
            }

            $delayHours = $settings->pickup_reminder_delay_hours;
            $thresholdTime = Carbon::now()->subHours($delayHours);

            if ($order->ready_at->lt($thresholdTime)) {
                // Determine if we already sent a reminder today or at all for this cycle
                // We'll use idempotency key with a date component if daily, or just a single reminder
                // PRD suggests a reminder. We will dispatch the job, and the SendCustomerNotificationJob handles idempotency.
                SendCustomerNotificationJob::dispatch($order, 'PICKUP_REMINDER');
            }
        }
    }
}
