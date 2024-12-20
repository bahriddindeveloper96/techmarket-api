<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Http\Resources\ProductResource;
use App\Models\CategoryTranslation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * @OA\Tag(
 *     name="Categories",
 *     description="API Endpoints of Categories"
 * )
 */
class CategoryController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/categories",
     *     summary="Get list of categories",
     *     tags={"Categories"},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *     )
     * )
     */
    public function index()
    {
        $categories = Category::with('translations')->get();
        return response()->json($categories);
    }

    /**
     * @OA\Post(
     *     path="/api/categories",
     *     summary="Create a new category",
     *     tags={"Categories"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"translations", "image", "active"},
     *             @OA\Property(property="translations", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="en", type="array",
     *                         @OA\Property(property="name", type="string"),
     *                         @OA\Property(property="description", type="string")
     *                     ),
     *                     @OA\Property(property="ru", type="array",
     *                         @OA\Property(property="name", type="string"),
     *                         @OA\Property(property="description", type="string")
     *                     ),
     *                     @OA\Property(property="uz", type="array",
     *                         @OA\Property(property="name", type="string"),
     *                         @OA\Property(property="description", type="string")
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="image", type="string"),
     *             @OA\Property(property="active", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Category created successfully"
     *     )
     * )
     */
    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'translations' => 'required|array',
    //         'translations.en' => 'required|array',
    //         'translations.en.name' => 'required|string|max:255',
    //         'translations.en.description' => 'required|string',
    //         'translations.ru' => 'required|array',
    //         'translations.ru.name' => 'required|string|max:255',
    //         'translations.ru.description' => 'required|string',
    //         'translations.uz' => 'required|array',
    //         'translations.uz.name' => 'required|string|max:255',
    //         'translations.uz.description' => 'required|string',
    //         'image' => 'nullable|string',
    //         'active' => 'boolean'
    //     ]);

    //     try {
    //         DB::beginTransaction();

    //         // Generate unique slug
    //         $slug = Str::slug($request->input('translations.en.name'));
    //         $originalSlug = $slug;
    //         $count = 1;

    //         while (Category::where('slug', $slug)->exists()) {
    //             $slug = $originalSlug . '-' . $count;
    //             $count++;
    //         }

    //         // Create category
    //         $category = Category::create([
    //             'user_id' => auth()->id(),
    //             'slug' => $slug,
    //             'image' => $request->input('image'),
    //             'active' => $request->input('active', true)
    //         ]);

    //         // Create translations
    //         foreach ($request->translations as $locale => $translation) {
    //             $category->translations()->create([
    //                 'locale' => $locale,
    //                 'name' => $translation['name'],
    //                 'description' => $translation['description']
    //             ]);
    //         }

    //         DB::commit();

    //         return response()->json([
    //             'message' => 'Category created successfully',
    //             'data' => $category->load('translations')
    //         ], 201);

    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return response()->json([
    //             'message' => 'Error creating category',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    /**
     * @OA\Get(
     *     path="/api/categories/{id}",
     *     summary="Get category by ID",
     *     tags={"Categories"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Category not found"
     *     )
     * )
     */
    public function show(Category $category)
    {
        return response()->json($category->load('translations'));
    }

    /**
     * @OA\Put(
     *     path="/api/categories/{id}",
     *     summary="Update category",
     *     tags={"Categories"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"translations", "image", "active"},
     *             @OA\Property(property="translations", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="en", type="array",
     *                         @OA\Property(property="name", type="string"),
     *                         @OA\Property(property="description", type="string")
     *                     ),
     *                     @OA\Property(property="ru", type="array",
     *                         @OA\Property(property="name", type="string"),
     *                         @OA\Property(property="description", type="string")
     *                     ),
     *                     @OA\Property(property="uz", type="array",
     *                         @OA\Property(property="name", type="string"),
     *                         @OA\Property(property="description", type="string")
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="image", type="string"),
     *             @OA\Property(property="active", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Category updated successfully"
     *     )
     * )
     */
    // public function update(Request $request, Category $category)
    // {
    //     $request->validate([
    //         'translations' => 'required|array',
    //         'translations.en' => 'required|array',
    //         'translations.en.name' => 'required|string|max:255',
    //         'translations.en.description' => 'required|string',
    //         'translations.ru' => 'required|array',
    //         'translations.ru.name' => 'required|string|max:255',
    //         'translations.ru.description' => 'required|string',
    //         'translations.uz' => 'required|array',
    //         'translations.uz.name' => 'required|string|max:255',
    //         'translations.uz.description' => 'required|string',
    //         'image' => 'nullable|string',
    //         'active' => 'boolean'
    //     ]);

    //     try {
    //         DB::beginTransaction();

    //         // Update category
    //         $category->update([
    //             'slug' => Str::slug($request->input('translations.en.name')),
    //             'image' => $request->input('image'),
    //             'active' => $request->input('active', true)
    //         ]);

    //         // Update translations
    //         foreach ($request->translations as $locale => $translation) {
    //             $category->translations()->updateOrCreate(
    //                 ['locale' => $locale],
    //                 [
    //                     'name' => $translation['name'],
    //                     'description' => $translation['description']
    //                 ]
    //             );
    //         }

    //         DB::commit();

    //         return response()->json([
    //             'message' => 'Category updated successfully',
    //             'data' => $category->load('translations')
    //         ]);

    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return response()->json([
    //             'message' => 'Error updating category',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    /**
     * @OA\Delete(
     *     path="/api/categories/{id}",
     *     summary="Delete category",
     *     tags={"Categories"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Category deleted successfully"
     *     )
     * )
     */
    // public function destroy(Category $category)
    // {
    //     try {
    //         DB::beginTransaction();

    //         // Delete translations first
    //         $category->translations()->delete();

    //         // Then delete the category
    //         $category->delete();

    //         DB::commit();

    //         return response()->json([
    //             'message' => 'Category deleted successfully'
    //         ]);

    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return response()->json([
    //             'message' => 'Error deleting category',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    /**
     * Kategoriya bo'yicha mahsulotlarni olish uchun method
     */
    public function products(Category $category)
    {
        // return response()->json([
        //     'data' => $category->products()->with('translations')->get()
        // ]);
        return response()->json([
            'data' => ProductResource::collection(
                $category->products()->with('translations')->get()
            )
        ]);
    }

    /**
     * Get products of a specific category, its subcategories and parent categories
     */
    public function productsByCategoryId($categoryId)
    {
        try {
            $category = Category::findOrFail($categoryId);
            
            // Get all subcategory IDs including the current category
            $categoryIds = [$categoryId];
            $this->getSubcategoryIds($category, $categoryIds);

            // Get parent category IDs
            $this->getParentCategoryIds($category, $categoryIds);

            // Get products from all these categories
            $products = Product::with([
                'translations', 
                'category.translations', 
                'variants.translations'
            ])
                ->whereIn('category_id', $categoryIds)
                ->where('active', true) // Only active products for public API
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'slug' => $product->slug,
                        'category' => [
                            'id' => $product->category->id,
                            'name' => $product->category->translations->where('locale', app()->getLocale())->first()->name
                        ],
                        'translations' => $product->translations,
                        'price' => $product->price,
                        'old_price' => $product->old_price,
                        'quantity' => $product->quantity,
                        'featured' => $product->featured,
                        'views' => $product->views,
                        'image' => $product->image,
                        'images' => $product->images,
                        'variants' => $product->variants->where('active', true)->map(function ($variant) {
                            return [
                                'id' => $variant->id,
                                'sku' => $variant->sku,
                                'price' => $variant->price,
                                'stock' => $variant->stock,
                                'images' => $variant->images,
                                'attribute_values' => $variant->attribute_values,
                                'translations' => $variant->translations
                            ];
                        }),
                        'variants_count' => $product->variants->where('active', true)->count(),
                        'created_at' => $product->created_at,
                        'updated_at' => $product->updated_at
                    ];
                });

            // Get breadcrumb data
            $breadcrumbs = $this->getBreadcrumbs($category);

            return response()->json([
                'success' => true,
                'data' => [
                    'category' => [
                        'id' => $category->id,
                        'slug' => $category->slug,
                        'translations' => $category->translations
                    ],
                    'breadcrumbs' => $breadcrumbs,
                    'products_count' => $products->count(),
                    'products' => $products
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving products: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Recursive function to get all subcategory IDs
     */
    private function getSubcategoryIds($category, &$ids)
    {
        $subcategories = Category::where('parent_id', $category->id)->get();
        foreach ($subcategories as $subcategory) {
            $ids[] = $subcategory->id;
            $this->getSubcategoryIds($subcategory, $ids);
        }
    }

    /**
     * Recursive function to get all parent category IDs
     */
    private function getParentCategoryIds($category, &$ids)
    {
        if ($category->parent_id) {
            $parentCategory = Category::find($category->parent_id);
            if ($parentCategory) {
                $ids[] = $parentCategory->id;
                $this->getParentCategoryIds($parentCategory, $ids);
            }
        }
    }

    /**
     * Get category breadcrumbs
     */
    private function getBreadcrumbs($category)
    {
        $breadcrumbs = [];
        $current = $category;

        // Add current category
        $breadcrumbs[] = [
            'id' => $current->id,
            'slug' => $current->slug,
            'name' => $current->translations->where('locale', app()->getLocale())->first()->name
        ];

        // Add parent categories
        while ($current->parent_id) {
            $current = Category::with('translations')->find($current->parent_id);
            if ($current) {
                array_unshift($breadcrumbs, [
                    'id' => $current->id,
                    'slug' => $current->slug,
                    'name' => $current->translations->where('locale', app()->getLocale())->first()->name
                ]);
            }
        }

        return $breadcrumbs;
    }
}
