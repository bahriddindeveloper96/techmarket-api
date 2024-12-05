<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $methods = PaymentMethod::where('is_active', true)
            ->with(['translations' => function($query) {
                $query->where('locale', app()->getLocale());
            }])
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $methods
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(PaymentMethod $paymentMethod): JsonResponse
    {
        if (!$paymentMethod->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => __('payment.method_not_available')
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $paymentMethod->load(['translations' => function($query) {
                $query->where('locale', app()->getLocale());
            }])
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
