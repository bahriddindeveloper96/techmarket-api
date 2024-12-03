<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Favorite;
use App\Models\Product;

class FavoriteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json([
            'favorites' => auth()->user()->favorites()
                ->with('product')
                ->latest()
                ->paginate(10)
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Product $product)
    {
        $user = auth()->user();
        
        // Check if already in favorites
        $exists = $user->favorites()->where('product_id', $product->id)->exists();
        if ($exists) {
            return response()->json([
                'message' => 'Product already in favorites'
            ], 409);
        }

        $favorite = $user->favorites()->create([
            'product_id' => $product->id
        ]);

        return response()->json([
            'message' => 'Product added to favorites',
            'favorite' => $favorite->load('product.translations')
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
    public function destroy(Product $product)
    {
        $user = auth()->user();
        
        $favorite = $user->favorites()->where('product_id', $product->id)->first();
        
        if (!$favorite) {
            return response()->json([
                'message' => 'Product not found in favorites'
            ], 404);
        }

        $favorite->delete();

        return response()->json([
            'message' => 'Product removed from favorites'
        ]);
    }
}
