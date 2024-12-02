<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function index(): JsonResponse
    {
        $orders = Order::where('user_id', Auth::id())
            ->with(['deliveryMethod', 'paymentMethod', 'items.product', 'items.productVariant'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $orders
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'delivery_method_id' => 'required|exists:delivery_methods,id',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'delivery_name' => 'required|string|max:255',
            'delivery_phone' => 'required|string|max:255',
            'delivery_region' => 'required|string|max:255',
            'delivery_district' => 'required|string|max:255',
            'delivery_address' => 'required|string',
            'delivery_comment' => 'nullable|string',
            'desired_delivery_date' => 'nullable|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.product_variant_id' => 'nullable|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            $order = new Order($validated);
            $order->user_id = Auth::id();
            $order->status = 'new';
            $order->payment_status = 'pending';
            
            // Calculate totals
            $totalAmount = 0;
            $totalDiscount = 0;

            foreach ($validated['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                $variant = isset($item['product_variant_id']) 
                    ? ProductVariant::findOrFail($item['product_variant_id'])
                    : null;

                $price = $variant ? $variant->price : $product->price;
                $discount = $variant ? $variant->discount : $product->discount;

                $totalAmount += $price * $item['quantity'];
                $totalDiscount += $discount * $item['quantity'];
            }

            $order->total_amount = $totalAmount;
            $order->total_discount = $totalDiscount;
            
            // Add delivery cost
            $deliveryMethod = DeliveryMethod::findOrFail($validated['delivery_method_id']);
            $order->delivery_cost = $deliveryMethod->base_cost;
            
            $order->save();

            // Create order items
            foreach ($validated['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                $variant = isset($item['product_variant_id']) 
                    ? ProductVariant::findOrFail($item['product_variant_id'])
                    : null;

                $price = $variant ? $variant->price : $product->price;
                $discount = $variant ? $variant->discount : $product->discount;

                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'price' => $price,
                    'discount' => $discount,
                    'options' => $variant ? $variant->options : null,
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => __('order.created_successfully'),
                'data' => $order->load(['items', 'deliveryMethod', 'paymentMethod'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => __('order.creation_failed')
            ], 500);
        }
    }

    public function show(Order $order): JsonResponse
    {
        if ($order->user_id !== Auth::id()) {
            return response()->json([
                'status' => 'error',
                'message' => __('order.not_found')
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $order->load(['items.product', 'items.productVariant', 'deliveryMethod', 'paymentMethod'])
        ]);
    }

    public function cancel(Order $order): JsonResponse
    {
        if ($order->user_id !== Auth::id()) {
            return response()->json([
                'status' => 'error',
                'message' => __('order.not_found')
            ], 404);
        }

        if (!in_array($order->status, ['new', 'pending'])) {
            return response()->json([
                'status' => 'error',
                'message' => __('order.cannot_cancel')
            ], 400);
        }

        $order->status = 'cancelled';
        $order->status_history = array_merge($order->status_history ?? [], [
            [
                'status' => 'cancelled',
                'timestamp' => now(),
                'comment' => 'Cancelled by user'
            ]
        ]);
        $order->save();

        return response()->json([
            'status' => 'success',
            'message' => __('order.cancelled_successfully'),
            'data' => $order
        ]);
    }
}
