<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $outletId = $request->user()->outlet_id;

        $expenses = Expense::where('outlet_id', $outletId)
            ->latest()
            ->paginate(15);

        return response()->json($expenses);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'note' => 'nullable|string',
        ]);

        $validated['outlet_id'] = $request->user()->outlet_id;

        $expense = Expense::create($validated);

        return response()->json($expense, 201);
    }
}
