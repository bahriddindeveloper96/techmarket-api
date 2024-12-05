<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryMethod;
use Illuminate\Http\JsonResponse;

class DeliveryMethodController extends Controller
{
    public function index(): JsonResponse
    {
        $methods = DeliveryMethod::where('is_active', true)
            ->with(['translations' => function($query) {
                $query->where('locale', app()->getLocale());
            }])
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $methods
        ]);
    }

    public function show(DeliveryMethod $deliveryMethod): JsonResponse
    {
        if (!$deliveryMethod->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => __('delivery.method_not_available')
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $deliveryMethod->load(['translations' => function($query) {
                $query->where('locale', app()->getLocale());
            }])
        ]);
    }

    public function calculateCost(DeliveryMethod $deliveryMethod): JsonResponse
    {
        // Bu yerda yetkazib berish narxini hisoblash logikasi bo'ladi
        // Masalan: masofa, vazn, hajm va boshqa parametrlarga qarab

        $cost = $deliveryMethod->base_cost;

        return response()->json([
            'status' => 'success',
            'data' => [
                'cost' => $cost,
                'estimated_days' => $deliveryMethod->estimated_days
            ]
        ]);
    }
}
