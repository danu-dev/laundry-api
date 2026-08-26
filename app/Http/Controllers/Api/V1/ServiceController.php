<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreServiceRequest;
use App\Http\Requests\Api\V1\UpdateServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $services = $request->user()->business->services()->latest()->get();

        return response()->json([
            'success' => true,
            'data' => ServiceResource::collection($services),
        ]);
    }

    public function store(StoreServiceRequest $request): JsonResponse
    {
        $service = $request->user()->business->services()->create($request->validated());

        return response()->json([
            'success' => true,
            'data' => new ServiceResource($service),
        ], 201);
    }

    public function show(Request $request, Service $service): JsonResponse
    {
        if ($service->business_id !== $request->user()->business_id) {
            abort(404);
        }

        return response()->json([
            'success' => true,
            'data' => new ServiceResource($service),
        ]);
    }

    public function update(UpdateServiceRequest $request, Service $service): JsonResponse
    {
        if ($service->business_id !== $request->user()->business_id) {
            abort(404);
        }

        $service->update($request->validated());

        return response()->json([
            'success' => true,
            'data' => new ServiceResource($service),
        ]);
    }

    public function destroy(Request $request, Service $service): JsonResponse
    {
        if ($service->business_id !== $request->user()->business_id) {
            abort(404);
        }

        $service->delete();

        return response()->json([
            'success' => true,
            'data' => null,
        ]);
    }
}
