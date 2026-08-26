<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreInventoryItemRequest;
use App\Http\Requests\Api\V1\UpdateInventoryItemRequest;
use App\Http\Resources\InventoryItemResource;
use App\Models\InventoryItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = $request->user()->business->inventoryItems()->paginate(20);

        return response()->json([
            'success' => true,
            'data' => InventoryItemResource::collection($items->items()),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function store(StoreInventoryItemRequest $request): JsonResponse
    {
        $item = $request->user()->business->inventoryItems()->create($request->validated());

        return response()->json([
            'success' => true,
            'data' => new InventoryItemResource($item),
        ], 201);
    }

    public function show(Request $request, InventoryItem $item): JsonResponse
    {
        if ($item->business_id !== $request->user()->business_id) {
            abort(404);
        }

        return response()->json([
            'success' => true,
            'data' => new InventoryItemResource($item),
        ]);
    }

    public function update(UpdateInventoryItemRequest $request, InventoryItem $item): JsonResponse
    {
        if ($item->business_id !== $request->user()->business_id) {
            abort(404);
        }

        $item->update($request->validated());

        return response()->json([
            'success' => true,
            'data' => new InventoryItemResource($item),
        ]);
    }

    public function destroy(Request $request, InventoryItem $item): JsonResponse
    {
        if ($item->business_id !== $request->user()->business_id) {
            abort(404);
        }

        $item->delete();

        return response()->json([
            'success' => true,
            'data' => null,
        ]);
    }

    public function adjust(Request $request, InventoryItem $item): JsonResponse
    {
        if ($item->business_id !== $request->user()->business_id) {
            abort(404);
        }

        $request->validate([
            'quantity_delta' => ['required', 'numeric'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $item->quantity += $request->quantity_delta;

        // Prevent negative inventory
        if ($item->quantity < 0) {
            $item->quantity = 0;
        }

        $item->save();

        // Check if low stock
        if ($item->quantity <= $item->minimum_quantity) {
            // TODO: dispatch InventoryLow event / send notification
        }

        return response()->json([
            'success' => true,
            'data' => new InventoryItemResource($item),
        ]);
    }
}
