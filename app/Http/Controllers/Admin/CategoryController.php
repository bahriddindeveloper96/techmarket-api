<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\AttributeGroup;
use App\Models\Attribute;
use App\Models\CategoryTranslation;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
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
        $request->validate([
            'translations' => 'required|array',
            'translations.*' => 'required|array',
            'translations.*.name' => 'required|string',
            'translations.*.description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
            'image' => 'nullable|image|max:2048',
            'active' => 'boolean',
            'featured' => 'boolean',
            'order' => 'integer',
            'attribute_groups' => 'array',
            'attribute_groups.*.name' => 'required|string',
            'attribute_groups.*.attributes' => 'array',
            'attribute_groups.*.attributes.*.name' => 'required|string',
            'attribute_groups.*.attributes.*.translations' => 'required|array',
            'attribute_groups.*.attributes.*.translations.*' => 'required|array',
            'attribute_groups.*.attributes.*.translations.*.name' => 'required|string'
        ]);

        try {
            DB::beginTransaction();

            $category = new Category();
            $category->user_id = auth()->id();
            $category->parent_id = $request->parent_id;
            $category->slug = Str::slug($request->translations['en']['name']);
            $category->active = $request->input('active', true);
            $category->featured = $request->input('featured', false);
            $category->order = $request->input('order', 0);

            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('categories', 'public');
                $category->image = '/storage/' . $path;
            }

            $category->save();

            // Save translations
            foreach ($request->translations as $locale => $translation) {
                $category->translations()->create([
                    'locale' => $locale,
                    'name' => $translation['name'],
                    'description' => $translation['description'] ?? null
                ]);
            }

            // Create and attach attribute groups with their attributes
            if ($request->has('attribute_groups')) {
                foreach ($request->attribute_groups as $groupData) {
                    // Create or find attribute group
                    $attributeGroup = AttributeGroup::firstOrCreate([
                        'name' => $groupData['name']
                    ]);

                    $attributeIds = [];

                    // Create attributes for the group
                    foreach ($groupData['attributes'] as $attributeData) {
                        $attribute = new Attribute([
                            'name' => $attributeData['name']
                        ]);
                        $attributeGroup->attributes()->save($attribute);

                        // Save attribute translations
                        foreach ($attributeData['translations'] as $locale => $translation) {
                            $attribute->translations()->create([
                                'locale' => $locale,
                                'name' => $translation['name']
                            ]);
                        }

                        $attributeIds[] = $attribute->id;
                    }

                    // Attach attribute group with its attributes to category
                    $category->attributeGroups()->attach($attributeGroup->id, [
                        'attributes' => json_encode($attributeIds)
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Category created successfully',
                'category' => $category->load(['translations', 'attributeGroups.attributes.translations'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error creating category',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Category $category)
    {
        return response()->json($category->load(['translations', 'parent.translations', 'attributeGroups.attributes.translations']));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'translations' => 'required|array',
            'translations.*' => 'required|array',
            'translations.*.name' => 'required|string',
            'translations.*.description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
            'image' => 'nullable|image|max:2048',
            'active' => 'boolean',
            'featured' => 'boolean',
            'order' => 'integer',
            'attribute_groups' => 'array',
            'attribute_groups.*.name' => 'required|string',
            'attribute_groups.*.attributes' => 'array',
            'attribute_groups.*.attributes.*.name' => 'required|string',
            'attribute_groups.*.attributes.*.translations' => 'required|array',
            'attribute_groups.*.attributes.*.translations.*' => 'required|array',
            'attribute_groups.*.attributes.*.translations.*.name' => 'required|string'
        ]);

        try {
            DB::beginTransaction();

            $category = Category::findOrFail($id);
            $category->parent_id = $request->parent_id;
            $category->slug = Str::slug($request->translations['en']['name']);
            $category->active = $request->input('active', true);
            $category->featured = $request->input('featured', false);
            $category->order = $request->input('order', 0);

            if ($request->hasFile('image')) {
                // Delete old image
                if ($category->image) {
                    Storage::disk('public')->delete(str_replace('/storage/', '', $category->image));
                }
                
                $path = $request->file('image')->store('categories', 'public');
                $category->image = '/storage/' . $path;
            }

            $category->save();

            // Update translations
            foreach ($request->translations as $locale => $translation) {
                $category->translations()->updateOrCreate(
                    ['locale' => $locale],
                    [
                        'name' => $translation['name'],
                        'description' => $translation['description'] ?? null
                    ]
                );
            }

            // Update attribute groups
            if ($request->has('attribute_groups')) {
                // Remove old attribute groups
                $category->attributeGroups()->detach();

                foreach ($request->attribute_groups as $groupData) {
                    // Create or find attribute group
                    $attributeGroup = AttributeGroup::firstOrCreate([
                        'name' => $groupData['name']
                    ]);

                    $attributeIds = [];

                    // Create or update attributes
                    foreach ($groupData['attributes'] as $attributeData) {
                        $attribute = new Attribute([
                            'name' => $attributeData['name']
                        ]);
                        $attributeGroup->attributes()->save($attribute);

                        // Update attribute translations
                        foreach ($attributeData['translations'] as $locale => $translation) {
                            $attribute->translations()->updateOrCreate(
                                ['locale' => $locale],
                                ['name' => $translation['name']]
                            );
                        }

                        $attributeIds[] = $attribute->id;
                    }

                    // Attach attribute group with its attributes to category
                    $category->attributeGroups()->attach($attributeGroup->id, [
                        'attributes' => json_encode($attributeIds)
                    ]);
                }
            }

            DB::commit();

            // Load the category with translations
            $category->load(['translations', 'attributeGroups.attributes.translations']);

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

            // Delete image if exists
            if ($category->image) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $category->image));
            }

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
