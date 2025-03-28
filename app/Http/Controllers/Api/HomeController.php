<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use App\Http\Resources\ProductResource;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Homepage",
 *     description="API Endpoints for Homepage"
 * )
 */
class HomeController extends Controller
{
    
    public function index()
    {
        // Get active banners ordered by order field
        $banners = Banner::where('active', true)
            ->orderBy('order')
            ->get();

        // Get main categories with translations
        $categories = Category::with('translations')
            ->whereNull('parent_id')
            ->where('active', true)
            ->orderBy('order')
            ->take(10)
            ->get();

        // Get featured products
        $featuredProducts = Product::with(['translations', 'category.translations'])
            ->where('featured', true)
            ->where('active', true)
            ->latest()
            ->take(8)
            ->get();

        // Get new products
        $newProducts = Product::with(['translations', 'category.translations'])
            ->where('active', true)
            ->latest()
            ->take(8)
            ->get();

        // Get popular products (based on views)
        $popularProducts = Product::with(['translations', 'category.translations'])
            ->where('active', true)
            ->orderBy('views', 'desc')
            ->take(8)
            ->get();

        // Get all products with pagination
        $allProducts = Product::with(['translations', 'category.translations'])
            ->where('active', true)
            ->latest()
            ->paginate(12);

        return response()->json([
            'success' => true,
            'data' => [
                'banners' => $banners,
                'categories' => $categories,
                'featured_products' => ProductResource::collection($featuredProducts),
                'new_products' => ProductResource::collection($newProducts),
                'popular_products' => ProductResource::collection($popularProducts),
                'all_products' => ProductResource::collection($allProducts)
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/products",
     *     summary="Get paginated products",
     *     tags={"Homepage"},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     )
     * )
     */
    public function products()
    {
        $products = Product::with(['translations', 'category.translations'])
            ->where('active', true)
            ->latest()
            ->paginate(12);

        return response()->json([
            'success' => true,
            'data' => ProductResource::collection($products)
        ]);
    }
}
