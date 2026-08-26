<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreExtraRequest;
use App\Http\Requests\Api\V1\UpdateExtraRequest;
use App\Http\Resources\ExtraResource;
use App\Models\Extra;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExtraController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $extras = $request->user()->business->extras()->latest()->get();

        return response()->json([
            'success' => true,
            'data' => ExtraResource::collection($extras),
        ]);
    }

    public function store(StoreExtraRequest $request): JsonResponse
    {
        $extra = $request->user()->business->extras()->create($request->validated());

        return response()->json([
            'success' => true,
            'data' => new ExtraResource($extra),
        ], 201);
    }

    public function show(Request $request, Extra $extra): JsonResponse
    {
        if ($extra->business_id !== $request->user()->business_id) {
            abort(404);
        }

        return response()->json([
            'success' => true,
            'data' => new ExtraResource($extra),
        ]);
    }

    public function update(UpdateExtraRequest $request, Extra $extra): JsonResponse
    {
        if ($extra->business_id !== $request->user()->business_id) {
            abort(404);
        }

        $extra->update($request->validated());

        return response()->json([
            'success' => true,
            'data' => new ExtraResource($extra),
        ]);
    }

    public function destroy(Request $request, Extra $extra): JsonResponse
    {
        if ($extra->business_id !== $request->user()->business_id) {
            abort(404);
        }

        $extra->delete();

        return response()->json([
            'success' => true,
            'data' => null,
        ]);
    }
}
