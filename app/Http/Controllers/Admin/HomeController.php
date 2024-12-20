<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use App\Http\Resources\ProductResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        // Get all banners ordered by order field
        $banners = Banner::orderBy('order')->get();

        // Get main categories with translations
        $categories = Category::with('translations')
            ->whereNull('parent_id')
            ->orderBy('order')
            ->take(10)
            ->get();

        // Get featured products
        $featuredProducts = Product::with(['translations', 'category.translations'])
            ->where('featured', true)
            ->latest()
            ->take(8)
            ->get();

        // Get new products
        $newProducts = Product::with(['translations', 'category.translations'])
            ->latest()
            ->take(8)
            ->get();

        // Get popular products (based on views)
        $popularProducts = Product::with(['translations', 'category.translations'])
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

    public function update(Request $request)
    {
        $request->validate([
            'banners' => 'required|array',
            'banners.*.id' => 'required|exists:banners,id',
            'banners.*.order' => 'required|integer|min:0',
            'banners.*.active' => 'required|boolean',
            'categories' => 'required|array',
            'categories.*.id' => 'required|exists:categories,id',
            'categories.*.order' => 'required|integer|min:0',
            'featured_products' => 'required|array',
            'featured_products.*' => 'required|exists:products,id'
        ]);

        try {
            DB::beginTransaction();

            // Update banners
            foreach ($request->banners as $banner) {
                Banner::where('id', $banner['id'])->update([
                    'order' => $banner['order'],
                    'active' => $banner['active']
                ]);
            }

            // Update categories order
            foreach ($request->categories as $category) {
                Category::where('id', $category['id'])->update([
                    'order' => $category['order']
                ]);
            }

            // Update featured products
            Product::whereIn('id', $request->featured_products)->update(['featured' => true]);
            Product::whereNotIn('id', $request->featured_products)->update(['featured' => false]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Homepage updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error updating homepage',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
