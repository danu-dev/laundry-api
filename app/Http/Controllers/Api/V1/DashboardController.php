<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $business = $request->user()->business;
        $businessId = $business->id;
        $tz = $business->timezone;

        // 1. Attention Metrics
        $overdueCount = Order::where('business_id', $businessId)
            ->where('status', '!=', Order::STATUS_COMPLETED)
            ->where('estimated_completion_at', '<', now())
            ->count();

        $readyCount = Order::where('business_id', $businessId)
            ->where('status', Order::STATUS_READY)
            ->count();

        $unpaidCount = Order::where('business_id', $businessId)
            ->where('payment_status', Order::PAYMENT_UNPAID)
            ->count();

        // 2. Today Summary (respecting business timezone)
        $todayStart = Carbon::today($tz)->utc();
        $todayEnd = Carbon::tomorrow($tz)->utc();

        $todayOrdersQuery = Order::where('business_id', $businessId)
            ->whereBetween('created_at', [$todayStart, $todayEnd]);

        $todayOrders = $todayOrdersQuery->count();
        $todayRevenue = $todayOrdersQuery->sum('total');

        $processing = Order::where('business_id', $businessId)
            ->whereIn('status', [Order::STATUS_NEW, Order::STATUS_WASHING, Order::STATUS_IRONING])
            ->count();

        // 3. Recent Orders
        $recentOrders = Order::where('business_id', $businessId)
            ->with(['customer', 'items'])
            ->latest()
            ->take(5)
            ->get();

        // 4. Automation Health
        $settings = $business->automationSettings;
        $automationHealth = [
            'tracking' => (bool) $settings?->tracking_enabled,
            'reminders' => (bool) $settings?->pickup_reminder_enabled,
            'daily_summary' => (bool) $settings?->daily_summary_enabled,
            'whatsapp_connected' => false, // Hardcoded for MVP, actual logic would check provider credentials
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'attention' => [
                    'overdue' => $overdueCount,
                    'ready_for_pickup' => $readyCount,
                    'unpaid' => $unpaidCount,
                ],
                'today' => [
                    'orders' => $todayOrders,
                    'revenue' => (int) $todayRevenue,
                    'processing' => $processing,
                    'ready' => $readyCount,
                ],
                'recent_orders' => OrderResource::collection($recentOrders),
                'automation_health' => $automationHealth,
            ],
        ]);
    }
}
