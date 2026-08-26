<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\InvalidOrderStateException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ChangeOrderStatusRequest;
use App\Http\Requests\Api\V1\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Extra;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Service;
use App\Services\OrderPricingService;
use App\Services\OrderStatusService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function __construct(private readonly OrderPricingService $pricingService) {}

    public function index(Request $request): JsonResponse
    {
        $query = $request->user()->business->orders()
            ->with(['customer', 'items', 'payments', 'extras']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        $orders = $query->latest()->paginate(20);

        return response()->json([
            'success' => true,
            'data' => OrderResource::collection($orders->items()),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $data = $request->validated();
        $business = $request->user()->business;

        $service = Service::where('business_id', $business->id)
            ->where('id', $data['service_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $customer = $business->customers()->findOrFail($data['customer_id']);

        $extras = collect();
        if (! empty($data['extras'])) {
            $extras = Extra::where('business_id', $business->id)
                ->whereIn('id', $data['extras'])
                ->where('is_active', true)
                ->get();
        }

        $order = DB::transaction(function () use ($data, $business, $customer, $service, $extras, $request) {
            // 1. Calculate pricing securely
            $subtotal = $this->pricingService->calculateItemSubtotal($service, $data['weight']);
            $extrasTotal = $this->pricingService->calculateExtrasTotal($extras);
            $total = $subtotal + $extrasTotal;

            // 2. Generate unique numbers and tokens
            // E.g. LD-260826-001 format
            $datePrefix = Carbon::now($business->timezone)->format('ymd');
            $latestOrderToday = Order::where('business_id', $business->id)
                ->whereDate('created_at', Carbon::today($business->timezone))
                ->count();
            $orderNumber = sprintf('LD-%s-%03d', $datePrefix, $latestOrderToday + 1);

            $rawToken = Str::random(32);
            $tokenHash = hash('sha256', $rawToken);

            // 3. Estimated completion
            $estimatedCompletion = null;
            if ($service->estimated_duration_minutes) {
                $estimatedCompletion = Carbon::now($business->timezone)
                    ->addMinutes($service->estimated_duration_minutes)
                    ->utc();
            }

            // 4. Determine initial payment status if payment is provided
            $amountPaid = 0;
            $paymentStatus = Order::PAYMENT_UNPAID;
            $actualPaymentAmount = 0;

            if (! empty($data['payment'])) {
                $amountPaid = (int) $data['payment']['amount'];
                $paymentStatus = $this->pricingService->determinePaymentStatus($total, $amountPaid);
                $actualPaymentAmount = $this->pricingService->calculateActualPaymentAmount($total, $amountPaid, $data['payment']['method']);
            }

            // 5. Create Order
            $order = Order::create([
                'business_id' => $business->id,
                'customer_id' => $customer->id,
                'order_number' => $orderNumber,
                'status' => Order::STATUS_NEW,
                'subtotal' => $subtotal,
                'extras_total' => $extrasTotal,
                'total' => $total,
                'payment_status' => $paymentStatus,
                'estimated_completion_at' => $estimatedCompletion,
                'tracking_token_hash' => $tokenHash,
            ]);

            // 6. Create Order Item
            $order->items()->create([
                'service_id' => $service->id,
                'service_name_snapshot' => $service->name,
                'unit_price' => $service->price,
                'quantity' => $data['weight'],
                'unit' => $service->pricing_type === 'PER_KG' ? 'kg' : 'pcs', // Simplified mapping based on PRD
                'subtotal' => $subtotal,
            ]);

            // 7. Create Extras
            foreach ($extras as $extra) {
                $order->extras()->create([
                    'extra_id' => $extra->id,
                    'extra_name_snapshot' => $extra->name,
                    'price' => $extra->price,
                ]);
            }

            // 8. Create Payment if exists
            if ($actualPaymentAmount > 0) {
                $order->payments()->create([
                    'amount' => $actualPaymentAmount,
                    'method' => $data['payment']['method'],
                    'status' => Payment::STATUS_PAID,
                    'paid_at' => now(),
                ]);
            }

            // 9. Initial status history
            $order->statusHistories()->create([
                'from_status' => null,
                'to_status' => Order::STATUS_NEW,
                'changed_by' => $request->user()->id,
            ]);

            // Inject the raw token so the resource can build the URL for the response only this once
            $order->tracking_url = rtrim(config('app.url'), '/')."/track/{$order->order_number}/{$rawToken}";

            return $order;
        });

        // Eager load relations for the response
        $order->load(['customer', 'items', 'extras', 'payments']);

        return response()->json([
            'success' => true,
            'data' => new OrderResource($order),
        ], 201);
    }

    public function updateStatus(ChangeOrderStatusRequest $request, Order $order, OrderStatusService $statusService): JsonResponse
    {
        if ($order->business_id !== $request->user()->business_id) {
            abort(404);
        }

        try {
            $order = $statusService->changeStatus($order, $request->validated()['status'], $request->user());
        } catch (InvalidOrderStateException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_STATE_TRANSITION',
                    'message' => $e->getMessage(),
                ],
            ], 422);
        }

        // TODO: Dispatch domain event (OrderReady, OrderCompleted, etc)

        $order->load(['statusHistories' => function ($query) {
            $query->latest();
        }]);

        return response()->json([
            'success' => true,
            'data' => new OrderResource($order),
        ]);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        if ($order->business_id !== $request->user()->business_id) {
            abort(404);
        }

        $order->load(['customer', 'items', 'extras', 'payments', 'statusHistories' => function ($query) {
            $query->latest();
        }]);

        return response()->json([
            'success' => true,
            'data' => new OrderResource($order),
        ]);
    }
}
