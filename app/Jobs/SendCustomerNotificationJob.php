<?php

namespace App\Jobs;

use App\Models\AutomationLog;
use App\Models\Notification;
use App\Models\Order;
use App\Services\Notifications\NotificationProvider;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendCustomerNotificationJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Order $order,
        public string $type
    ) {}

    /**
     * Execute the job.
     */
    public function handle(NotificationProvider $provider): void
    {
        $order = $this->order;
        $businessId = $order->business_id;

        // Idempotency check: don't send ORDER_READY twice for the same order
        $idempotencyKey = "order:{$order->id}:{$this->type}";

        $exists = Notification::where('business_id', $businessId)
            ->where('idempotency_key', $idempotencyKey)
            ->exists();

        if ($exists) {
            // Log skip
            AutomationLog::create([
                'business_id' => $businessId,
                'order_id' => $order->id,
                'event' => $this->type,
                'action' => 'SEND_NOTIFICATION',
                'status' => 'SKIPPED',
                'metadata' => json_encode(['reason' => 'Duplicate prevented']),
                'executed_at' => now(),
            ]);

            return;
        }

        $customer = $order->customer;
        if (! $customer || ! $customer->phone) {
            return; // Can't send if no phone
        }

        // Generate message based on type
        $message = $this->generateMessage();

        $notificationRecord = Notification::create([
            'business_id' => $businessId,
            'customer_id' => $customer->id,
            'order_id' => $order->id,
            'type' => $this->type,
            'channel' => 'WHATSAPP',
            'status' => 'PENDING',
            'payload' => $message,
            'idempotency_key' => $idempotencyKey,
        ]);

        $success = $provider->send($customer->phone, $message);

        if ($success) {
            $notificationRecord->update([
                'status' => 'SENT',
                'sent_at' => now(),
            ]);

            AutomationLog::create([
                'business_id' => $businessId,
                'order_id' => $order->id,
                'event' => $this->type,
                'action' => 'SEND_NOTIFICATION',
                'status' => 'SUCCESS',
                'executed_at' => now(),
            ]);
        } else {
            $notificationRecord->update([
                'status' => 'FAILED',
                'failed_at' => now(),
            ]);

            AutomationLog::create([
                'business_id' => $businessId,
                'order_id' => $order->id,
                'event' => $this->type,
                'action' => 'SEND_NOTIFICATION',
                'status' => 'FAILED',
                'executed_at' => now(),
            ]);

            // Allow retry if necessary depending on business logic
            // For now just fail gracefully
        }
    }

    private function generateMessage(): string
    {
        $orderNumber = $this->order->order_number;

        return match ($this->type) {
            'ORDER_CREATED' => "Your laundry order {$orderNumber} has been created. Track it here: ".$this->order->tracking_url,
            'ORDER_READY' => "Your laundry order {$orderNumber} is ready for pickup.",
            'PICKUP_REMINDER' => "Your laundry order {$orderNumber} is still waiting for pickup.",
            default => "Update for order {$orderNumber}.",
        };
    }
}
