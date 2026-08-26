<?php

namespace App\Services;

use App\Exceptions\InvalidOrderStateException;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderStatusService
{
    /**
     * Allowed state transitions per the PRD
     */
    protected array $allowedTransitions = [
        Order::STATUS_NEW => [Order::STATUS_WASHING],
        Order::STATUS_WASHING => [Order::STATUS_IRONING],
        Order::STATUS_IRONING => [Order::STATUS_READY],
        Order::STATUS_READY => [Order::STATUS_COMPLETED],
    ];

    public function canTransition(string $currentStatus, string $newStatus): bool
    {
        if ($currentStatus === $newStatus) {
            return false;
        }

        $allowed = $this->allowedTransitions[$currentStatus] ?? [];

        return in_array($newStatus, $allowed, true);
    }

    public function changeStatus(Order $order, string $newStatus, ?User $changedBy = null): Order
    {
        if (! $this->canTransition($order->status, $newStatus)) {
            throw new InvalidOrderStateException("Cannot transition order from {$order->status} to {$newStatus}.");
        }

        return DB::transaction(function () use ($order, $newStatus, $changedBy) {
            $oldStatus = $order->status;
            $now = now();

            $order->status = $newStatus;

            // Set timestamps based on status
            if ($newStatus === Order::STATUS_READY) {
                $order->ready_at = $now;
            } elseif ($newStatus === Order::STATUS_COMPLETED) {
                $order->completed_at = $now;
            }

            $order->save();

            $order->statusHistories()->create([
                'from_status' => $oldStatus,
                'to_status' => $newStatus,
                'changed_by' => $changedBy?->id,
            ]);

            return $order;
        });
    }
}
