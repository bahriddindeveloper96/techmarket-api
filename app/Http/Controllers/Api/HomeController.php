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
    /**
     * @OA\Get(
     *     path="/api/homepage",
     *     summary="Get homepage data",
     *     tags={"Homepage"},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="banners",
     *                     type="array",
     *                     @OA\Items(type="object")
     *                 ),
     *                 @OA\Property(
     *                     property="categories",
     *                     type="array",
     *                     @OA\Items(type="object")
     *                 ),
     *                 @OA\Property(
     *                     property="featured_products",
     *                     type="array",
     *                     @OA\Items(type="object")
     *                 ),
     *                 @OA\Property(
     *                     property="new_products",
     *                     type="array",
     *                     @OA\Items(type="object")
     *                 ),
     *                 @OA\Property(
     *                     property="popular_products",
     *                     type="array",
     *                     @OA\Items(type="object")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
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

        return response()->json([
            'success' => true,
            'data' => [
                'banners' => $banners,
                'categories' => $categories,
                'featured_products' => ProductResource::collection($featuredProducts),
                'new_products' => ProductResource::collection($newProducts),
                'popular_products' => ProductResource::collection($popularProducts)
            ]
        ]);
    }
}