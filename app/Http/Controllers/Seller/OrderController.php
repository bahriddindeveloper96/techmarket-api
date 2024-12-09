<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['user', 'items.product', 'deliveryMethod', 'paymentMethod']);

        // Search by order number
        if ($request->has('search')) {
            $query->where('order_number', 'like', "%{$request->search}%");
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by payment status
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Sort
        $sortField = $request->get('sort_field', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortField, $sortOrder);

        return response()->json([
            'orders' => $query->paginate($request->get('per_page', 15))
        ]);
    }

    public function show(Order $order)
    {
        return response()->json([
            'order' => $order->load(['user', 'items.product', 'deliveryMethod', 'paymentMethod'])
        ]);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|string|in:pending,processing,shipped,delivered,cancelled',
            'comment' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            $oldStatus = $order->status;
            $newStatus = $request->status;

            // Update order status
            $order->status = $newStatus;

            // Add to status history
            $statusHistory = $order->status_history ?? [];
            $statusHistory[] = [
                'from' => $oldStatus,
                'to' => $newStatus,
                'comment' => $request->comment,
                'user_id' => auth()->id(),
                'timestamp' => now()
            ];
            $order->status_history = $statusHistory;

            $order->save();

            DB::commit();

            return response()->json([
                'message' => 'Order status updated successfully',
                'order' => $order->fresh()
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update order status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function export(Request $request)
    {
        $query = Order::with(['user', 'items.product']);

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $orders = $query->get();

        // Format data for export
        $exportData = $orders->map(function ($order) {
            return [
                'Order Number' => $order->order_number,
                'Date' => $order->created_at->format('Y-m-d H:i:s'),
                'Customer' => $order->user->name,
                'Total Amount' => $order->total_amount,
                'Status' => $order->status,
                'Payment Status' => $order->payment_status,
                'Delivery Method' => $order->deliveryMethod->name,
                'Payment Method' => $order->paymentMethod->name
            ];
        });

        return response()->json([
            'data' => $exportData
        ]);
    }
}
