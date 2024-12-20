<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CategoryTranslation;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * @OA\Tag(
 *     name="Categories",
 *     description="API Endpoints of Categories"
 * )
 */
class CategoryController extends Controller
{

    public function uploadImages(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'images' => 'required|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048', // Validate each file
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $paths = [];

        try {
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $image->store('category', 'public'); // Store in the 'products' directory within 'public'
                    $paths[] = '/storage/' . $path; // Build the public URL
                }
            }

            return response()->json([
                'success' => true,
                'paths' => $paths,
            ]);
        } catch (\Exception $e) {
            Log::error('Error uploading images: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error uploading images',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function index()
    {
        // Faqat asosiy kategoriyalarni olamiz (parent_id = null)
        $categories = Category::with(['translations', 'children.translations'])
            ->whereNull('parent_id')
            ->get()
            ->map(function ($category) {
                return $this->formatCategory($category);
            });

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * Kategoriyani formatlash uchun yordamchi metod
     */
    private function formatCategory($category)
    {
        $formattedCategory = [
            'id' => $category->id,
            'slug' => $category->slug,
            'active' => $category->active,
            'featured' => $category->featured,
            'order' => $category->order,
            'image' => $category->image,
            'translations' => $category->translations,
            'created_at' => $category->created_at,
            'updated_at' => $category->updated_at,
        ];

        // Agar kategoriyaning bolalari bo'lsa
        if ($category->children && $category->children->count() > 0) {
            $formattedCategory['children'] = $category->children->map(function ($child) {
                return $this->formatCategory($child);
            });
        }

        return $formattedCategory;
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'translations' => 'required|array',
            'translations.en' => 'required|array',
            'translations.en.name' => 'required|string|max:255',
            'translations.en.description' => 'required|string',
            'translations.ru' => 'required|array',
            'translations.ru.name' => 'required|string|max:255',
            'translations.ru.description' => 'required|string',
            'translations.uz' => 'required|array',
            'translations.uz.name' => 'required|string|max:255',
            'translations.uz.description' => 'required|string',
            'parent_id' => 'nullable|exists:categories,id',
            'active' => 'boolean',
            'image' => 'nullable|string',
            'featured' => 'boolean',
            'order' => 'nullable|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Generate slug from English name
            $slug = Str::slug($request->translations['en']['name']);
            $originalSlug = $slug;
            $count = 1;

            // Ensure unique slug
            while (Category::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $count;
                $count++;
            }

            // Create category
            $category = Category::create([
                'slug' => $slug,
                'parent_id' => $request->input('parent_id'),
                'active' => $request->input('active', true),
                'image' => $request->input('image'),
                'featured' => $request->input('featured', false),
                'order' => $request->input('order', 0),
                'user_id' => auth()->id() // Autentifikatsiya qilingan foydalanuvchi ID'sini qo'shamiz
            ]);

            // Create translations
            foreach ($request->translations as $locale => $translation) {
                CategoryTranslation::create([
                    'category_id' => $category->id,
                    'locale' => $locale,
                    'name' => $translation['name'],
                    'description' => $translation['description']
                ]);
            }

            DB::commit();

            // Load the category with translations
            $category->load('translations');

            return response()->json([
                'success' => true,
                'message' => 'Category created successfully',
                'data' => $category
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error creating category: ' . $e->getMessage()
            ], 500);
        }
    }

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
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'translations' => 'required|array',
            'translations.en' => 'required|array',
            'translations.en.name' => 'required|string|max:255',
            'translations.en.description' => 'required|string',
            'translations.ru' => 'required|array',
            'translations.ru.name' => 'required|string|max:255',
            'translations.ru.description' => 'required|string',
            'translations.uz' => 'required|array',
            'translations.uz.name' => 'required|string|max:255',
            'translations.uz.description' => 'required|string',
            'parent_id' => 'nullable|exists:categories,id',
            'active' => 'boolean',
            'image' => 'nullable|string',
            'featured' => 'boolean',
            'order' => 'nullable|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $category = Category::findOrFail($id);

            // Check if English name is changed
            if ($category->translations()->where('locale', 'en')->first()->name !== $request->translations['en']['name']) {
                // Generate new slug from English name
                $slug = Str::slug($request->translations['en']['name']);
                $originalSlug = $slug;
                $count = 1;

                // Ensure unique slug
                while (Category::where('slug', $slug)->where('id', '!=', $id)->exists()) {
                    $slug = $originalSlug . '-' . $count;
                    $count++;
                }

                $category->slug = $slug;
            }

            // Update category
            $category->update([
                'parent_id' => $request->input('parent_id'),
                'active' => $request->input('active', true),
                'image' => $request->input('image'),
                'featured' => $request->input('featured', false),
                'order' => $request->input('order', 0)
            ]);

            // Update translations
            foreach ($request->translations as $locale => $translation) {
                $category->translations()->updateOrCreate(
                    ['locale' => $locale],
                    [
                        'name' => $translation['name'],
                        'description' => $translation['description']
                    ]
                );
            }

            DB::commit();

            // Load the category with translations
            $category->load('translations');

            return response()->json([
                'success' => true,
                'message' => 'Category updated successfully',
                'data' => $category
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Category not found'
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error updating category: ' . $e->getMessage()
            ], 500);
        }
    }

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
    public function destroy(Category $category)
    {
        try {
            DB::beginTransaction();

            // Delete translations first
            $category->translations()->delete();

            // Then delete the category
            $category->delete();

            DB::commit();

            return response()->json([
                'message' => 'Category deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error deleting category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get products of a specific category, its subcategories and parent categories
     */
    public function products($categoryId)
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
                        'active' => $product->active,
                        'featured' => $product->featured,
                        'views' => $product->views,
                        'image' => $product->image,
                        'images' => $product->images,
                        'variants' => $product->variants->map(function ($variant) {
                            return [
                                'id' => $variant->id,
                                'sku' => $variant->sku,
                                'price' => $variant->price,
                                'stock' => $variant->stock,
                                'active' => $variant->active,
                                'images' => $variant->images,
                                'attribute_values' => $variant->attribute_values,
                                'translations' => $variant->translations
                            ];
                        }),
                        'variants_count' => $product->variants->count(),
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

    /**
     * Get child categories of a specific category
     */
    public function childCategories($parentId)
    {
        try {
            // Parent kategoriyani tekshirish
            $parentCategory = Category::findOrFail($parentId);

            // Child kategoriyalarni olish
            $childCategories = Category::with(['translations'])
                ->where('parent_id', $parentId)
                ->orderBy('order')
                ->get()
                ->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'slug' => $category->slug,
                        'active' => $category->active,
                        'featured' => $category->featured,
                        'order' => $category->order,
                        'image' => $category->image,
                        'translations' => $category->translations,
                        'has_children' => $category->children()->count() > 0,
                        'created_at' => $category->created_at,
                        'updated_at' => $category->updated_at
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'parent_category' => [
                        'id' => $parentCategory->id,
                        'slug' => $parentCategory->slug,
                        'translations' => $parentCategory->translations
                    ],
                    'child_categories' => $childCategories
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Parent category not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving child categories: ' . $e->getMessage()
            ], 500);
        }
    }
}
