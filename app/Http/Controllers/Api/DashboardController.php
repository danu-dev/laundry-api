<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $outletId = $request->user()->outlet_id;
        $today = Carbon::today();

        $query = Order::when($outletId, fn ($q) => $q->where('outlet_id', $outletId));

        $todayRevenue = (clone $query)
            ->whereDate('created_at', $today)
            ->sum('total');

        $todayOrdersCount = (clone $query)
            ->whereDate('created_at', $today)
            ->count();

        $readyOrdersCount = (clone $query)
            ->where('status', 'READY')
            ->count();

        $processingOrdersCount = (clone $query)
            ->whereIn('status', ['NEW', 'WASHING', 'IRONING'])
            ->count();

        $needsAttention = (clone $query)
            ->with(['customer', 'items.service'])
            ->whereIn('status', ['NEW', 'WASHING', 'IRONING', 'READY'])
            ->orderBy('created_at', 'asc')
            ->limit(5)
            ->get();

        return response()->json([
            'metrics' => [
                'today_revenue' => $todayRevenue,
                'today_orders' => $todayOrdersCount,
                'ready_orders' => $readyOrdersCount,
                'processing_orders' => $processingOrdersCount,
            ],
            'needs_attention' => $needsAttention,
        ]);
    }
}
