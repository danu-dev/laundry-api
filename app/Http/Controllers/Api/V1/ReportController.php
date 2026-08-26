<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $business = $request->user()->business;
        $tz = $business->timezone;

        // Date range from request, defaulting to "this_week" concept if missing, or just passing dates
        $dateFrom = $request->date_from ? Carbon::parse($request->date_from, $tz)->startOfDay()->utc() : Carbon::today($tz)->subDays(7)->utc();
        $dateTo = $request->date_to ? Carbon::parse($request->date_to, $tz)->endOfDay()->utc() : Carbon::tomorrow($tz)->utc();

        // Use aggregations, avoid loading objects to memory
        $orderStats = Order::where('business_id', $business->id)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('COUNT(*) as total_orders')
            ->selectRaw('SUM(total) as order_value')
            ->first();

        // Collected payment within period
        $collectedPayment = Payment::whereHas('order', function ($query) use ($business) {
            $query->where('business_id', $business->id);
        })
            ->whereBetween('paid_at', [$dateFrom, $dateTo])
            ->where('status', Payment::STATUS_PAID)
            ->sum('amount');

        // Popular services
        $popularServices = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.business_id', $business->id)
            ->whereBetween('orders.created_at', [$dateFrom, $dateTo])
            ->select('order_items.service_name_snapshot', DB::raw('COUNT(*) as count'))
            ->groupBy('order_items.service_name_snapshot')
            ->orderByDesc('count')
            ->take(5)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'period' => [
                    'from' => $dateFrom,
                    'to' => $dateTo,
                ],
                'orders' => $orderStats->total_orders ?? 0,
                'order_value' => (int) ($orderStats->order_value ?? 0),
                'collected_payment' => (int) $collectedPayment,
                'outstanding_payment' => max(0, (int) ($orderStats->order_value ?? 0) - (int) $collectedPayment),
                'popular_services' => $popularServices,
            ],
        ]);
    }
}
