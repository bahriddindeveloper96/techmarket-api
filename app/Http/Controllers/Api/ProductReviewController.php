<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
            ->with(['translations', 'user.translations'])
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
        $request->validate([
            'rating' => 'required|integer|between:1,5',
            'translations' => 'required|array',
            'translations.*.comment' => 'required|string|max:1000',
        ]);

        $review = $product->reviews()->create([
            'user_id' => auth()->id(),
            'rating' => $request->rating,
            'is_approved' => false
        ]);

        // Create translations
        foreach ($request->translations as $locale => $data) {
            $review->translations()->create([
                'locale' => $locale,
                'comment' => $data['comment']
            ]);
        }

        return response()->json([
            'message' => 'Review added successfully',
            'review' => $review->load(['user.translations', 'translations'])
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
            'comment' => 'required|string|min:3',
            'locale' => 'required|string|size:2'
        ]);

        $review->update([
            'rating' => $request->rating
        ]);

        $review->translations()->updateOrCreate(
            ['locale' => $request->locale],
            ['comment' => $request->comment]
        );

        return response()->json([
            'message' => 'Review updated successfully',
            'review' => $review->fresh()->load(['user.translations', 'translations'])
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
