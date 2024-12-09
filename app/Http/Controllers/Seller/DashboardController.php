<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\ProductReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $totalOrders = Order::count();
        $totalProducts = Product::count();
        $totalUsers = User::where('role', 'user')->count();
        $totalRevenue = Order::where('status', 'completed')->sum('total_amount');

        return response()->json([
            'total_orders' => $totalOrders,
            'total_products' => $totalProducts,
            'total_users' => $totalUsers,
            'total_revenue' => $totalRevenue
        ]);
    }

    public function stats()
    {
        $lastMonth = now()->subMonth();

        $newOrders = Order::where('created_at', '>=', $lastMonth)->count();
        $newUsers = User::where('role', 'user')
            ->where('created_at', '>=', $lastMonth)
            ->count();
        $activeProducts = Product::where('active', true)->count();
        $pendingReviews = ProductReview::where('is_approved', false)->count();

        return response()->json([
            'new_orders' => $newOrders,
            'new_users' => $newUsers,
            'active_products' => $activeProducts,
            'pending_reviews' => $pendingReviews
        ]);
    }

    public function chartData(Request $request)
    {
        $days = $request->get('days', 30);
        $startDate = now()->subDays($days);

        $orderStats = Order::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(total_amount) as revenue')
        )
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->get();

        $userStats = User::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count')
        )
            ->where('role', 'user')
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->get();

        return response()->json([
            'orders' => $orderStats,
            'users' => $userStats
        ]);
    }
}
