<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreCustomerRequest;
use App\Http\Requests\Api\V1\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = $request->user()->business->customers();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $customers = $query->latest()->paginate(20);

        return response()->json([
            'success' => true,
            'data' => CustomerResource::collection($customers->items()),
            'meta' => [
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
            ],
        ]);
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $customer = $request->user()->business->customers()->create($request->validated());

        return response()->json([
            'success' => true,
            'data' => new CustomerResource($customer),
        ], 201);
    }

    public function show(Request $request, Customer $customer): JsonResponse
    {
        if ($customer->business_id !== $request->user()->business_id) {
            abort(404);
        }

        return response()->json([
            'success' => true,
            'data' => new CustomerResource($customer),
        ]);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): JsonResponse
    {
        if ($customer->business_id !== $request->user()->business_id) {
            abort(404);
        }

        $customer->update($request->validated());

        return response()->json([
            'success' => true,
            'data' => new CustomerResource($customer),
        ]);
    }

    public function destroy(Request $request, Customer $customer): JsonResponse
    {
        if ($customer->business_id !== $request->user()->business_id) {
            abort(404);
        }

        $customer->delete();

        return response()->json([
            'success' => true,
            'data' => null,
        ]);
    }
}
