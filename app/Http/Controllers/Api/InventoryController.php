<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $outletId = $request->user()->outlet_id;

        $items = InventoryItem::where('outlet_id', $outletId)
            ->orderBy('name')
            ->get()
            ->map(function ($item) {
                // Add status flag
                $item->status = $item->quantity <= $item->minimum_stock ? 'low' : 'ok';
                return $item;
            });

        return response()->json($items);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'quantity' => 'required|numeric|min:0',
            'unit' => 'required|string',
            'minimum_stock' => 'required|numeric|min:0',
        ]);

        $validated['outlet_id'] = $request->user()->outlet_id;

        $item = InventoryItem::create($validated);

        return response()->json($item, 201);
    }

    public function update(Request $request, InventoryItem $inventory)
    {
        $validated = $request->validate([
            'quantity' => 'required|numeric|min:0',
        ]);

        $inventory->update($validated);

        return response()->json($inventory);
    }
}
