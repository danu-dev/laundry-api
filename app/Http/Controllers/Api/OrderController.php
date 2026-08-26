<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');
        $search = $request->query('search');
        $outletId = $request->user()->outlet_id;

        $orders = Order::with(['customer', 'items.service'])
            ->when($outletId, fn ($q) => $q->where('outlet_id', $outletId))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($search, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('order_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($c) use ($search) {
                            $c->where('name', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate(20);

        return response()->json($orders);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'items' => 'required|array|min:1',
            'items.*.service_id' => 'required|exists:services,id',
            'items.*.quantity' => 'required|numeric|min:0.1',
            'discount' => 'numeric|min:0',
            'payment_method' => 'nullable|string|in:Cash,Transfer,QRIS,Pay later',
            'amount_paid' => 'nullable|numeric|min:0',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $orderNumber = 'LD-'.now()->format('dmy').'-'.str_pad(Order::whereDate('created_at', today())->count() + 1, 3, '0', STR_PAD_LEFT);

            $subtotal = 0;

            $order = Order::create([
                'order_number' => $orderNumber,
                'customer_id' => $validated['customer_id'],
                'outlet_id' => $request->user()->outlet_id ?? 1, // Fallback for dev
                'status' => 'NEW',
                'discount' => $validated['discount'] ?? 0,
            ]);

            foreach ($validated['items'] as $itemData) {
                $service = Service::findOrFail($itemData['service_id']);
                $itemPrice = $service->price;
                $itemTotal = $itemPrice * $itemData['quantity'];

                $order->items()->create([
                    'service_id' => $service->id,
                    'quantity' => $itemData['quantity'],
                    'unit' => $service->unit,
                    'price' => $itemPrice,
                ]);

                $subtotal += $itemTotal;
            }

            $discount = $validated['discount'] ?? 0;
            $total = max(0, $subtotal - $discount);

            $paymentStatus = 'UNPAID';
            $amountPaid = $validated['amount_paid'] ?? 0;

            if ($amountPaid > 0) {
                $order->payments()->create([
                    'amount' => $amountPaid,
                    'method' => $validated['payment_method'] ?? 'Cash',
                ]);

                if ($amountPaid >= $total) {
                    $paymentStatus = 'PAID';
                } else {
                    $paymentStatus = 'PARTIAL';
                }
            }

            $order->update([
                'subtotal' => $subtotal,
                'total' => $total,
                'payment_status' => $paymentStatus,
                'payment_method' => $validated['payment_method'] ?? null,
            ]);

            $order->statusHistories()->create([
                'to_status' => 'NEW',
                'changed_by' => $request->user()->id,
            ]);

            return response()->json($order->load(['customer', 'items.service', 'payments']));
        });
    }

    public function show(Order $order)
    {
        $order->load(['customer', 'items.service', 'payments', 'statusHistories.changedBy', 'outlet']);

        return response()->json($order);
    }

    public function update(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'sometimes|required|string|in:NEW,WASHING,IRONING,READY,COMPLETED,CANCELLED',
        ]);

        if (isset($validated['status']) && $validated['status'] !== $order->status) {
            DB::transaction(function () use ($order, $validated, $request) {
                $oldStatus = $order->status;

                $updateData = ['status' => $validated['status']];
                if ($validated['status'] === 'COMPLETED') {
                    $updateData['completed_at'] = now();
                }

                $order->update($updateData);

                $order->statusHistories()->create([
                    'from_status' => $oldStatus,
                    'to_status' => $validated['status'],
                    'changed_by' => $request->user()->id,
                ]);
            });
        }

        return response()->json($order->fresh(['statusHistories.changedBy']));
    }

    public function track($orderNumber)
    {
        $order = Order::with(['customer', 'items.service', 'statusHistories'])
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        // Return limited data for public tracking
        return response()->json([
            'order_number' => $order->order_number,
            'customer_name' => $order->customer->name,
            'status' => $order->status,
            'total' => $order->total,
            'payment_status' => $order->payment_status,
            'created_at' => $order->created_at,
            'completed_at' => $order->completed_at,
            'histories' => $order->statusHistories->map(function ($history) {
                return [
                    'status' => $history->to_status,
                    'created_at' => $history->created_at,
                ];
            }),
            'items' => $order->items->map(function ($item) {
                return [
                    'name' => $item->service->name,
                    'quantity' => $item->quantity,
                    'unit' => $item->unit,
                ];
            }),
        ]);
    }
}
