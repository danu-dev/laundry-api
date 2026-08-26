<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;

class PublicTrackingController extends Controller
{
    public function show(string $token): JsonResponse
    {
        $tokenHash = hash('sha256', $token);

        // Fetch order with business relation to access business details and timezone
        $order = Order::with(['business', 'statusHistories' => function ($query) {
            $query->oldest('created_at');
        }])->where('tracking_token_hash', $tokenHash)->first();

        if (! $order) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'ORDER_NOT_FOUND',
                    'message' => 'Order could not be found.',
                ],
            ], 404);
        }

        // PRD 32, 42-44: Return only safe public information
        return response()->json([
            'success' => true,
            'data' => [
                'business_name' => $order->business->name,
                'business_phone' => $order->business->phone,
                'order_number' => $order->order_number,
                // Do not expose full customer name or phone
                'status' => $order->status,
                'total' => $order->total,
                'estimated_completion_at' => $order->estimated_completion_at,
                'status_history' => $order->statusHistories->map(function ($history) {
                    return [
                        'status' => $history->to_status,
                        'timestamp' => $history->created_at,
                    ];
                }),
            ],
        ]);
    }
}
