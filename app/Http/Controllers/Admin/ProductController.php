<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FileType;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\FileController;

use App\Models\Attribute;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductTranslation;
use App\Models\ProductVariant;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\ProductResource;

class ProductController extends Controller
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
                    $path = $image->store('products', 'public'); // Store in the 'products' directory within 'public'
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

    public function index(Request $request)
    {
        $query = Product::query()->with(['category', 'variants']);

        // Search by name
        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('translations', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by price range
        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        // Sort products
        if ($request->has('sort')) {
            switch ($request->sort) {
                case 'price_asc':
                    $query->orderBy('price', 'asc');
                    break;
                case 'price_desc':
                    $query->orderBy('price', 'desc');
                    break;
                case 'rating':
                    $query->withAvg('reviews', 'rating')
                          ->orderByDesc('reviews_avg_rating');
                    break;
                case 'latest':
                    $query->latest();
                    break;
                default:
                    $query->latest();
            }
        } else {
            $query->latest();
        }

        // Get user's favorites if authenticated
        if (auth()->check()) {
            $query->with(['favorites' => function ($q) {
                $q->where('user_id', auth()->id());
            }]);
        }

        return response()->json([
            'products' => ProductResource::collection($query->paginate($request->per_page ?? 10))
        ]);
    }

    public function show($id)
    {
        $product = Product::with([
            'translations',
            'category',
            'variants' => function ($query) {
                $query->with('attributes');
            },
            'attributes'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new ProductResource($product)
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:categories,id',
            'translations' => 'required|array',
            'translations.*' => 'required|array',
            'translations.*.name' => 'required|string',
            'translations.*.description' => 'required|string',
            'attributes' => 'required|array',
            'attributes.*' => 'required|string',
            'variants' => 'required|array',
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.stock' => 'required|integer|min:0',
            'variants.*.attribute_values' => 'required|array',
            'variants.*.attribute_values.*' => 'required|string',
            'variants.*.images' => 'required|array',
            'variants.*.images.*' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Create product
            $product = new Product([
                'category_id' => $request->category_id,
                'user_id' => auth()->id(),
                'slug' => Str::slug($request->translations['en']['name']),
                'active' => $request->input('active', true),
                'featured' => $request->input('featured', false)
            ]);

            $product->save();

            // Save translations
            foreach ($request->translations as $locale => $translation) {
                $product->translations()->create([
                    'locale' => $locale,
                    'name' => $translation['name'],
                    'description' => $translation['description']
                ]);
            }

            // Get category attributes
            $category = Category::with('attributeGroups.attributes')->findOrFail($request->category_id);
            $categoryAttributes = collect();
            
            foreach ($category->attributeGroups as $group) {
                $categoryAttributes = $categoryAttributes->merge($group->attributes);
            }

            // Save product attributes
            foreach ($request->attributes as $attributeId => $value) {
                if ($categoryAttributes->contains('id', $attributeId)) {
                    $product->attributes()->attach($attributeId, ['value' => $value]);
                }
            }

            // Create variants
            foreach ($request->variants as $variantData) {
                // Format attribute values for storage
                $attributeValuesFormatted = [];
                foreach ($variantData['attribute_values'] as $attributeId => $value) {
                    if ($categoryAttributes->contains('id', $attributeId)) {
                        $attribute = $categoryAttributes->firstWhere('id', $attributeId);
                        $attributeValuesFormatted[$attribute->name] = $value;
                    }
                }

                $variant = new ProductVariant([
                    'price' => $variantData['price'],
                    'stock' => $variantData['stock'],
                    'images' => $variantData['images'],
                    'sku' => strtoupper(Str::slug($request->translations['en']['name']) . '-' . Str::random(5)),
                    'attribute_values' => $attributeValuesFormatted
                ]);

                $variant->product()->associate($product);
                $variant->save();

                // Save variant attributes
                foreach ($variantData['attribute_values'] as $attributeId => $value) {
                    if ($categoryAttributes->contains('id', $attributeId)) {
                        $variant->attributes()->attach($attributeId, ['value' => $value]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully',
                'data' => new ProductResource($product->load(['translations', 'category', 'variants']))
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating product: ' . $e->getMessage());
            Log::error($e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Error creating product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:categories,id',
            'translations' => 'required|array',
            'translations.*' => 'required|array',
            'translations.*.name' => 'required|string',
            'translations.*.description' => 'required|string',
            'attributes' => 'required|array',
            'attributes.*' => 'required|string',
            'variants' => 'required|array',
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.stock' => 'required|integer|min:0',
            'variants.*.attribute_values' => 'required|array',
            'variants.*.attribute_values.*' => 'required|string',
            'variants.*.images' => 'required|array',
            'variants.*.images.*' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $product = Product::findOrFail($id);

            // Update basic product info
            $product->update([
                'category_id' => $request->category_id,
                'slug' => Str::slug($request->translations['en']['name']),
                'active' => $request->input('active', $product->active),
                'featured' => $request->input('featured', $product->featured)
            ]);

            // Update translations
            foreach ($request->translations as $locale => $translation) {
                $product->translations()->updateOrCreate(
                    ['locale' => $locale],
                    [
                        'name' => $translation['name'],
                        'description' => $translation['description']
                    ]
                );
            }

            // Get category attributes
            $category = Category::with('attributeGroups.attributes')->findOrFail($request->category_id);
            $categoryAttributes = collect();
            
            foreach ($category->attributeGroups as $group) {
                $categoryAttributes = $categoryAttributes->merge($group->attributes);
            }

            // Update product attributes
            $product->attributes()->detach();
            foreach ($request->attributes as $attributeId => $value) {
                if ($categoryAttributes->contains('id', $attributeId)) {
                    $product->attributes()->attach($attributeId, ['value' => $value]);
                }
            }

            // Update variants
            $product->variants()->delete(); // Remove old variants
            foreach ($request->variants as $variantData) {
                // Format attribute values for storage
                $attributeValuesFormatted = [];
                foreach ($variantData['attribute_values'] as $attributeId => $value) {
                    if ($categoryAttributes->contains('id', $attributeId)) {
                        $attribute = $categoryAttributes->firstWhere('id', $attributeId);
                        $attributeValuesFormatted[$attribute->name] = $value;
                    }
                }

                $variant = new ProductVariant([
                    'price' => $variantData['price'],
                    'stock' => $variantData['stock'],
                    'images' => $variantData['images'],
                    'sku' => strtoupper(Str::slug($request->translations['en']['name']) . '-' . Str::random(5)),
                    'attribute_values' => $attributeValuesFormatted
                ]);

                $variant->product()->associate($product);
                $variant->save();

                // Save variant attributes
                foreach ($variantData['attribute_values'] as $attributeId => $value) {
                    if ($categoryAttributes->contains('id', $attributeId)) {
                        $variant->attributes()->attach($attributeId, ['value' => $value]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully',
                'data' => new ProductResource($product->load(['translations', 'category', 'variants']))
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating product: ' . $e->getMessage());
            Log::error($e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Error updating product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Product $product)
    {
        try {
            DB::beginTransaction();

            // Delete translations
            $product->translations()->delete();

            // Delete variants
            $product->variants()->delete();

            // Delete product
            $product->delete();

            DB::commit();

            return response()->json([
                'message' => 'Product deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error deleting product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function featured()
    {
        try {
            $products = Product::with(['translations', 'variants', 'category'])
                ->where('featured', true)
                ->where('active', true)
                ->latest()
                ->get();

            return response()->json([
                'message' => 'Featured products retrieved successfully',
                'data' => $products
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error retrieving featured products',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Variant metodlari
    public function updateVariantStock(Request $request, $productId, $variantId)
    {
        $validator = Validator::make($request->all(), [
            'stock' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $variant = ProductVariant::where('product_id', $productId)
            ->where('id', $variantId)
            ->firstOrFail();

        $variant->stock = $request->stock;
        $variant->save();

        return response()->json([
            'success' => true,
            'data' => $variant
        ]);
    }

    public function updateVariantPrice(Request $request, $productId, $variantId)
    {
        $validator = Validator::make($request->all(), [
            'price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $variant = ProductVariant::where('product_id', $productId)
            ->where('id', $variantId)
            ->firstOrFail();

        $variant->price = $request->price;
        $variant->save();

        return response()->json([
            'success' => true,
            'data' => $variant
        ]);
    }

    public function getVariantStock($productId, $variantId)
    {
        try {
            $product = Product::findOrFail($productId);
            $variant = $product->variants()->findOrFail($variantId);

            return response()->json([
                'message' => 'Variant stock retrieved successfully',
                'data' => [
                    'stock' => $variant->stock,
                    'sku' => $variant->sku
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Product or variant not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error retrieving variant stock',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all variants of a product
     */
    public function getVariants($productId)
    {
        try {
            $product = Product::with(['variants' => function($query) {
                $query->orderBy('created_at', 'desc');
            }])->findOrFail($productId);

            return response()->json([
                'success' => true,
                'data' => [
                    'product_id' => $product->id,
                    'product_name' => $product->translations->where('locale', 'en')->first()->name,
                    'variants' => $product->variants->map(function($variant) {
                        return [
                            'id' => $variant->id,
                            'sku' => $variant->sku,
                            'price' => $variant->price,
                            'stock' => $variant->stock,
                            'active' => $variant->active,
                            'attribute_values' => $variant->attribute_values,
                            'images' => $variant->images,
                            'created_at' => $variant->created_at,
                            'updated_at' => $variant->updated_at
                        ];
                    })
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving product variants: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get specific variant of a product
     */
    public function getVariant($productId, $variantId)
    {
        try {
            $product = Product::findOrFail($productId);
            
            $variant = $product->variants()
                ->where('id', $variantId)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => [
                    'product_id' => $product->id,
                    'product_name' => $product->translations->where('locale', 'en')->first()->name,
                    'variant' => [
                        'id' => $variant->id,
                        'sku' => $variant->sku,
                        'price' => $variant->price,
                        'stock' => $variant->stock,
                        'active' => $variant->active,
                        'attribute_values' => $variant->attribute_values,
                        'images' => $variant->images,
                        'created_at' => $variant->created_at,
                        'updated_at' => $variant->updated_at
                    ]
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product or variant not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving variant: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add new variant to product
     */
    public function addVariant(Request $request, $productId)
    {
        $validator = Validator::make($request->all(), [
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'attribute_values' => 'required|array',
            'attribute_values.*' => 'required|string',
            'images' => 'required|array',
            'images.*' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $product = Product::with('category.attributeGroups.attributes')->findOrFail($productId);
            
            // Get all category attributes
            $categoryAttributes = collect();
            foreach ($product->category->attributeGroups as $group) {
                $categoryAttributes = $categoryAttributes->merge($group->attributes);
            }

            // Format attribute values for storage
            $attributeValuesFormatted = [];
            foreach ($request->attribute_values as $attributeId => $value) {
                if ($categoryAttributes->contains('id', $attributeId)) {
                    $attribute = $categoryAttributes->firstWhere('id', $attributeId);
                    $attributeValuesFormatted[$attribute->name] = $value;
                }
            }

            // Create variant
            $variant = new ProductVariant([
                'price' => $request->price,
                'stock' => $request->stock,
                'images' => $request->images,
                'sku' => strtoupper($product->slug . '-' . Str::random(5)),
                'attribute_values' => $attributeValuesFormatted,
                'active' => true
            ]);

            $variant->product()->associate($product);
            $variant->save();

            // Save variant attributes
            foreach ($request->attribute_values as $attributeId => $value) {
                if ($categoryAttributes->contains('id', $attributeId)) {
                    $variant->attributes()->attach($attributeId, ['value' => $value]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Variant added successfully',
                'data' => $variant->load('attributes')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error adding variant: ' . $e->getMessage());
            Log::error($e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Error adding variant',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update specific variant of a product
     */
    public function updateVariant(Request $request, $productId, $variantId)
    {
        $validator = Validator::make($request->all(), [
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'attribute_values' => 'required|array',
            'attribute_values.*' => 'required|string',
            'images' => 'required|array',
            'images.*' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $product = Product::with('category.attributeGroups.attributes')->findOrFail($productId);
            $variant = $product->variants()->findOrFail($variantId);
            
            // Get all category attributes
            $categoryAttributes = collect();
            foreach ($product->category->attributeGroups as $group) {
                $categoryAttributes = $categoryAttributes->merge($group->attributes);
            }

            // Format attribute values for storage
            $attributeValuesFormatted = [];
            foreach ($request->attribute_values as $attributeId => $value) {
                if ($categoryAttributes->contains('id', $attributeId)) {
                    $attribute = $categoryAttributes->firstWhere('id', $attributeId);
                    $attributeValuesFormatted[$attribute->name] = $value;
                }
            }

            // Update variant
            $variant->update([
                'price' => $request->price,
                'stock' => $request->stock,
                'images' => $request->images,
                'attribute_values' => $attributeValuesFormatted
            ]);

            // Update variant attributes
            $variant->attributes()->detach();
            foreach ($request->attribute_values as $attributeId => $value) {
                if ($categoryAttributes->contains('id', $attributeId)) {
                    $variant->attributes()->attach($attributeId, ['value' => $value]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Variant updated successfully',
                'data' => $variant->load('attributes')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating variant: ' . $e->getMessage());
            Log::error($e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Error updating variant',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete specific variant of a product
     */
    public function deleteVariant($productId, $variantId)
    {
        try {
            $product = Product::findOrFail($productId);
            
            $variant = $product->variants()
                ->where('id', $variantId)
                ->firstOrFail();

            // Check if variant is used in any orders
            $isUsedInOrders = \App\Models\OrderItem::where('product_variant_id', $variantId)->exists();
            
            if ($isUsedInOrders) {
                // Instead of deleting, just deactivate the variant
                $variant->update(['active' => false]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Variant has been deactivated because it is used in orders',
                    'data' => [
                        'id' => $variant->id,
                        'active' => false
                    ]
                ]);
            }

            // If not used in orders, delete it
            $variant->delete();

            return response()->json([
                'success' => true,
                'message' => 'Variant deleted successfully'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product or variant not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting variant: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getAttributesByCategory($categoryId)
    {
        $category = Category::with(['attributeGroups.attributes.translations' => function ($query) {
            $query->select('attribute_id', 'locale', 'name');
        }])->findOrFail($categoryId);

        $attributeGroups = $category->attributeGroups->map(function ($group) {
            return [
                'name' => $group->name,
                'attributes' => $group->attributes->map(function ($attribute) {
                    return [
                        'id' => $attribute->id,
                        'name' => $attribute->name,
                        'translations' => $attribute->translations->mapWithKeys(function ($translation) {
                            return [$translation->locale => [
                                'name' => $translation->name
                            ]];
                        })
                    ];
                })
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $attributeGroups
        ]);
    }
}
