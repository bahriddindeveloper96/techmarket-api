<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\DeliveryMethod;
use App\Models\PaymentMethod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $orders = Order::where('user_id', Auth::id())
                ->with(['items.product.translations', 'items.productVariant', 'deliveryMethod.translations', 'paymentMethod.translations'])
                ->latest()
                ->paginate(20);

            return response()->json([
                'status' => 'success',
                'data' => $orders
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve orders'
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'delivery_method_id' => 'required|exists:delivery_methods,id',
                'payment_method_id' => 'required|exists:payment_methods,id',
                'delivery_name' => 'nullable|string|max:255',
                'delivery_phone' => 'nullable|string|max:255',
                'delivery_region' => 'nullable|string|max:255',
                'delivery_district' => 'nullable|string|max:255',
                'delivery_address' => 'required|string',
                'delivery_comment' => 'nullable|string',
                'desired_delivery_date' => 'nullable|date',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
            ]);

            DB::beginTransaction();

            // Get authenticated user
            $user = Auth::user();
            $userTranslation = $user->translations()
                ->where('locale', app()->getLocale())
                ->first();

            // Create order
            $order = new Order();
            $order->user_id = $user->id;
            $order->delivery_method_id = $validated['delivery_method_id'];
            $order->payment_method_id = $validated['payment_method_id'];
            $order->delivery_name = $validated['delivery_name'] ?? $userTranslation?->name ?? $user->email;
            $order->delivery_phone = $validated['delivery_phone'] ?? $user->phone;
            $order->delivery_region = $validated['delivery_region'] ?? '';
            $order->delivery_district = $validated['delivery_district'] ?? '';
            $order->delivery_address = $validated['delivery_address'];
            $order->delivery_comment = $validated['delivery_comment'] ?? null;
            $order->desired_delivery_date = $validated['desired_delivery_date'] ?? null;
            $order->status = 'new';
            $order->payment_status = 'pending';
            $order->total_amount = 0;
            $order->total_discount = 0;

            // Calculate delivery cost
            $deliveryMethod = DeliveryMethod::findOrFail($validated['delivery_method_id']);
            $order->delivery_cost = $deliveryMethod->base_cost;

            // Save order to get ID
            $order->save();

            $totalAmount = 0;
            $totalDiscount = 0;

            // Create order items
            foreach ($validated['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);

                if (!$product->active) {
                    DB::rollBack();
                    return response()->json([
                        'status' => 'error',
                        'message' => "Product {$product->id} is not active"
                    ], 400);
                }

                $price = $product->price;
                $discount = $product->discount;

                $orderItem = $order->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'price' => $price,
                    'discount' => $discount
                ]);

                $totalAmount += $price * $item['quantity'];
                $totalDiscount += $discount * $item['quantity'];
            }

            // Update order totals
            $order->total_amount = $totalAmount;
            $order->total_discount = $totalDiscount;
            $order->save();

            DB::commit();

            // Load relationships for response
            $order->load(['items.product.translations', 'deliveryMethod.translations', 'paymentMethod.translations']);

            return response()->json([
                'status' => 'success',
                'message' => 'Order created successfully',
                'data' => $order
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create order: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $order = Order::findOrFail($id);

            if ($order->user_id !== Auth::id()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Order not found'
                ], 404);
            }

            $order->load(['items.product.translations', 'deliveryMethod.translations', 'paymentMethod.translations']);

            return response()->json([
                'status' => 'success',
                'data' => $order
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve order'
            ], 500);
        }
    }

    public function cancel(Order $order): JsonResponse
    {
        try {
            if ($order->user_id !== Auth::id()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Order not found'
                ], 404);
            }

            if (!in_array($order->status, ['new', 'pending'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Order cannot be cancelled'
                ], 400);
            }

            $order->status = 'cancelled';
            $order->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Order cancelled successfully',
                'data' => $order
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to cancel order'
            ], 500);
        }
    }
}
