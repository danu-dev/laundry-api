<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateBusinessRequest;
use App\Http\Resources\BusinessResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BusinessController extends Controller
{
    /**
     * Get the authenticated owner's business details.
     */
    public function show(Request $request): JsonResponse
    {
        $business = $request->user()->business;

        return response()->json([
            'success' => true,
            'data' => [
                'business' => new BusinessResource($business),
            ],
        ]);
    }

    /**
     * Update the authenticated owner's business details.
     */
    public function update(UpdateBusinessRequest $request): JsonResponse
    {
        $business = $request->user()->business;

        $business->update($request->validated());

        return response()->json([
            'success' => true,
            'data' => [
                'business' => new BusinessResource($business),
            ],
        ]);
    }
}
