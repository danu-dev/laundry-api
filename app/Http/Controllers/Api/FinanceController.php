<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FinanceController extends Controller
{
    public function summary(Request $request)
    {
        $outletId = $request->user()->outlet_id;
        $thisMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();

        // Calculate Revenue This Month
        $thisMonthRevenue = Order::where('outlet_id', $outletId)
            ->whereDate('created_at', '>=', $thisMonth)
            ->sum('total');

        // Calculate Revenue Last Month
        $lastMonthRevenue = Order::where('outlet_id', $outletId)
            ->whereBetween('created_at', [$lastMonth, $thisMonth->copy()->subDay()])
            ->sum('total');

        $growth = 0;
        if ($lastMonthRevenue > 0) {
            $growth = (($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100;
        } elseif ($thisMonthRevenue > 0) {
            $growth = 100; // 100% growth if last month was 0
        }

        // Cash vs Transfer breakdown (mock for now based on total)
        $cashRevenue = Order::where('outlet_id', $outletId)
            ->whereDate('created_at', '>=', $thisMonth)
            ->where('payment_method', 'Cash')
            ->sum('total');

        $transferRevenue = $thisMonthRevenue - $cashRevenue;

        // Total Expenses This Month
        $thisMonthExpenses = Expense::where('outlet_id', $outletId)
            ->whereDate('created_at', '>=', $thisMonth)
            ->sum('amount');

        $estimatedNet = $thisMonthRevenue - $thisMonthExpenses;

        return response()->json([
            'this_month_revenue' => $thisMonthRevenue,
            'growth_percentage' => round($growth, 1),
            'cash_revenue' => $cashRevenue,
            'transfer_revenue' => $transferRevenue,
            'total_expenses' => $thisMonthExpenses,
            'estimated_net' => $estimatedNet,
        ]);
    }
}
