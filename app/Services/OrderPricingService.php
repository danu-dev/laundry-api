<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Service;
use Illuminate\Support\Collection;

class OrderPricingService
{
    /**
     * Calculate item subtotal based on integer math
     */
    public function calculateItemSubtotal(Service $service, float $quantity): int
    {
        // Multiply first to prevent floating point issues, round, then cast to int
        return (int) round($service->price * $quantity);
    }

    /**
     * Calculate the total for the extras
     */
    public function calculateExtrasTotal(Collection $extras): int
    {
        return $extras->sum('price');
    }

    /**
     * Determine the payment status based on paid vs total
     */
    public function determinePaymentStatus(int $total, int $amountPaid): string
    {
        if ($amountPaid === 0) {
            return Order::PAYMENT_UNPAID;
        }

        if ($amountPaid >= $total) {
            return Order::PAYMENT_PAID;
        }

        return Order::PAYMENT_PARTIAL;
    }

    /**
     * Calculate payment amounts taking CASH change into account.
     *
     * If the method is CASH and they give more than the total, we only register the total amount.
     * For other methods, assume they exactly transfer the amount or we just register the amount given.
     */
    public function calculateActualPaymentAmount(int $total, int $givenAmount, string $method): int
    {
        if ($method === Payment::METHOD_CASH && $givenAmount > $total) {
            return $total; // Change will be (givenAmount - total), but we only register total against the order
        }

        return $givenAmount;
    }
}
