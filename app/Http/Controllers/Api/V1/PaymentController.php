<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StorePaymentRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Payment;
use App\Services\OrderPricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function __construct(private readonly OrderPricingService $pricingService) {}

    public function store(StorePaymentRequest $request, Order $order): JsonResponse
    {
        if ($order->business_id !== $request->user()->business_id) {
            abort(404);
        }

        if ($order->payment_status === Order::PAYMENT_PAID) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'ORDER_ALREADY_PAID',
                    'message' => 'This order is already fully paid.',
                ],
            ], 422);
        }

        $data = $request->validated();

        DB::transaction(function () use ($order, $data) {
            $totalPaidSoFar = $order->payments()->sum('amount');
            $remainingBalance = $order->total - $totalPaidSoFar;

            $givenAmount = (int) $data['amount'];

            $actualPaymentAmount = $this->pricingService->calculateActualPaymentAmount(
                $remainingBalance,
                $givenAmount,
                $data['method']
            );

            $order->payments()->create([
                'amount' => $actualPaymentAmount,
                'method' => $data['method'],
                'status' => Payment::STATUS_PAID,
                'paid_at' => now(),
            ]);

            $newTotalPaid = $totalPaidSoFar + $actualPaymentAmount;
            $newPaymentStatus = $this->pricingService->determinePaymentStatus($order->total, $newTotalPaid);

            $order->update([
                'payment_status' => $newPaymentStatus,
            ]);
        });

        $order->load('payments');

        return response()->json([
            'success' => true,
            'data' => new OrderResource($order),
        ]);
    }
}
