<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Http\Request;

class ProductReviewController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Product $product)
    {
        $reviews = $product->reviews()
            ->with(['user'])
            ->where('is_approved', true)
            ->latest()
            ->paginate(10);

        return response()->json($reviews);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Product $product)
    {
        $user = auth()->user();
        
        // Check if user has ordered this product
        $hasOrdered = OrderItem::whereHas('order', function ($query) use ($user) {
            $query->where('user_id', $user->id)
                  ->where('status', 'completed');
        })->where('product_id', $product->id)->exists();
        
        if (!$hasOrdered) {
            return response()->json([
                'message' => 'You can only review products that you have purchased'
            ], 403);
        }

        // Check if user has already reviewed this product
        $hasReviewed = $product->reviews()->where('user_id', $user->id)->exists();
        
        if ($hasReviewed) {
            return response()->json([
                'message' => 'You have already reviewed this product'
            ], 403);
        }

        $request->validate([
            'rating' => 'required|integer|between:1,5',
            'comment' => 'required|string|max:1000',
        ]);

        $review = $product->reviews()->create([
            'user_id' => $user->id,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'is_approved' => false
        ]);

        return response()->json([
            'message' => 'Review added successfully',
            'review' => $review->load(['user'])
        ], 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product, ProductReview $review)
    {
        $this->authorize('update', $review);

        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|min:3'
        ]);

        $review->update([
            'rating' => $request->rating,
            'comment' => $request->comment
        ]);

        return response()->json([
            'message' => 'Review updated successfully',
            'review' => $review->fresh()->load(['user'])
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product, ProductReview $review)
    {
        $this->authorize('delete', $review);
        
        $review->delete();

        return response()->json([
            'message' => 'Review deleted successfully'
        ]);
    }
}
