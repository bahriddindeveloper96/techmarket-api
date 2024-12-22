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
            'translations.*.description' => 'required|string'
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

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully',
                'product' => new ProductResource($product->load('translations'))
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Product creation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error creating product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function storeAttributes(Request $request, $productId)
    {
        $validator = Validator::make($request->all(), [
            'attributes' => 'required|array',
            'attributes.*' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $product = Product::findOrFail($productId);
            
            // Get category attributes
            $category = Category::with('attributeGroups.attributes')->findOrFail($product->category_id);
            $categoryAttributes = collect();
            
            foreach ($category->attributeGroups as $group) {
                $categoryAttributes = $categoryAttributes->merge($group->attributes);
            }

            // Remove existing attributes
            $product->attributes()->detach();

            // Get attributes from request
            $requestAttributes = $request->input('attributes', []);

            // Save product attributes
            foreach ($requestAttributes as $attributeId => $value) {
                // Check if this attribute belongs to the category
                if ($categoryAttributes->contains('id', $attributeId)) {
                    $product->attributes()->attach($attributeId, ['value' => $value]);
                }
            }

            // Add remaining category attributes with empty string values if they don't exist
            foreach ($categoryAttributes as $attribute) {
                if (!isset($requestAttributes[$attribute->id])) {
                    $product->attributes()->attach($attribute->id, ['value' => '']);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product attributes added successfully',
                'product' => $product->load('attributes')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Adding product attributes failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error adding product attributes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function storeVariants(Request $request, $productId)
    {
        try {
            $product = Product::with('category.attributeGroups.attributes')->findOrFail($productId);
            
            // Validate request structure
            $validator = Validator::make($request->all(), [
                'variants' => 'required|array',
                'variants.*.price' => 'required|numeric|min:0',
                'variants.*.stock' => 'required|integer|min:0',
                'variants.*.attribute_values' => 'required|array',
                'variants.*.images' => 'required|array',
                'variants.*.images.*' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            // Get category attributes
            $categoryAttributes = collect();
            foreach ($product->category->attributeGroups as $group) {
                $categoryAttributes = $categoryAttributes->merge($group->attributes);
            }

            // Validate attribute values against category attributes
            foreach ($request->variants as $variant) {
                foreach ($variant['attribute_values'] as $attributeId => $value) {
                    if (!$categoryAttributes->contains('id', $attributeId)) {
                        return response()->json([
                            'success' => false,
                            'errors' => ['attribute_values' => ["Attribute ID {$attributeId} is not valid for this category"]]
                        ], 422);
                    }
                }
            }

            DB::beginTransaction();

            // Save variants
            foreach ($request->variants as $variantData) {
                $variant = new ProductVariant();
                $variant->product_id = $product->id;
                $variant->price = $variantData['price'];
                $variant->stock = $variantData['stock'];
                $variant->images = $variantData['images'];
                $variant->attribute_values = $variantData['attribute_values'];
                $variant->save();

                // Save variant attributes
                foreach ($variantData['attribute_values'] as $attributeId => $value) {
                    $variant->attributes()->attach($attributeId, ['value' => $value]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product variants added successfully',
                'product' => new ProductResource($product->load(['translations', 'attributes', 'variants.attributes']))
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Adding product variants failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error adding product variants',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), [
                'category_id' => 'required|exists:categories,id',
                'translations' => 'required|array',
                'translations.*' => 'required|array',
                'translations.*.name' => 'required|string',
                'translations.*.description' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

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

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully',
                'product' => new ProductResource($product->load(['translations']))
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Product update failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateAttributes(Request $request, $productId)
    {
        $validator = Validator::make($request->all(), [
            'attributes' => 'required|array',
            'attributes.*' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $product = Product::findOrFail($productId);
            
            // Get category attributes
            $category = Category::with('attributeGroups.attributes')->findOrFail($product->category_id);
            $categoryAttributes = collect();
            
            foreach ($category->attributeGroups as $group) {
                $categoryAttributes = $categoryAttributes->merge($group->attributes);
            }

            // Remove existing attributes
            $product->attributes()->detach();

            // Get attributes from request
            $requestAttributes = $request->input('attributes', []);

            // Save product attributes
            foreach ($requestAttributes as $attributeId => $value) {
                // Check if this attribute belongs to the category
                if ($categoryAttributes->contains('id', $attributeId)) {
                    $product->attributes()->attach($attributeId, ['value' => $value]);
                }
            }

            // Add remaining category attributes with empty string values if they don't exist
            foreach ($categoryAttributes as $attribute) {
                if (!isset($requestAttributes[$attribute->id])) {
                    $product->attributes()->attach($attribute->id, ['value' => '']);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product attributes updated successfully',
                'product' => $product->load('attributes')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Updating product attributes failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating product attributes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateVariants(Request $request, $productId)
    {
        try {
            $product = Product::with('category.attributeGroups.attributes')->findOrFail($productId);
            
            // Validate request structure
            $validator = Validator::make($request->all(), [
                'variants' => 'required|array',
                'variants.*.id' => 'sometimes|exists:product_variants,id',
                'variants.*.price' => 'required|numeric|min:0',
                'variants.*.stock' => 'required|integer|min:0',
                'variants.*.attribute_values' => 'required|array',
                'variants.*.images' => 'required|array',
                'variants.*.images.*' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            // Get category attributes
            $categoryAttributes = collect();
            foreach ($product->category->attributeGroups as $group) {
                $categoryAttributes = $categoryAttributes->merge($group->attributes);
            }

            // Validate attribute values against category attributes
            foreach ($request->variants as $variant) {
                foreach ($variant['attribute_values'] as $attributeId => $value) {
                    if (!$categoryAttributes->contains('id', $attributeId)) {
                        return response()->json([
                            'success' => false,
                            'errors' => ['attribute_values' => ["Attribute ID {$attributeId} is not valid for this category"]]
                        ], 422);
                    }
                }
            }

            DB::beginTransaction();

            // Get existing variant IDs
            $existingVariantIds = $product->variants()->pluck('id')->toArray();
            $updatedVariantIds = [];

            // Update or create variants
            foreach ($request->variants as $variantData) {
                if (isset($variantData['id'])) {
                    // Update existing variant
                    $variant = ProductVariant::find($variantData['id']);
                    if ($variant && $variant->product_id == $product->id) {
                        $variant->price = $variantData['price'];
                        $variant->stock = $variantData['stock'];
                        $variant->images = $variantData['images'];
                        $variant->attribute_values = $variantData['attribute_values'];
                        $variant->save();

                        // Update variant attributes
                        $variant->attributes()->detach();
                        foreach ($variantData['attribute_values'] as $attributeId => $value) {
                            $variant->attributes()->attach($attributeId, ['value' => $value]);
                        }

                        $updatedVariantIds[] = $variant->id;
                    }
                } else {
                    // Create new variant
                    $variant = new ProductVariant();
                    $variant->product_id = $product->id;
                    $variant->price = $variantData['price'];
                    $variant->stock = $variantData['stock'];
                    $variant->images = $variantData['images'];
                    $variant->attribute_values = $variantData['attribute_values'];
                    $variant->save();

                    // Save variant attributes
                    foreach ($variantData['attribute_values'] as $attributeId => $value) {
                        $variant->attributes()->attach($attributeId, ['value' => $value]);
                    }

                    $updatedVariantIds[] = $variant->id;
                }
            }

            // Delete variants that were not updated or created
            $variantsToDelete = array_diff($existingVariantIds, $updatedVariantIds);
            if (!empty($variantsToDelete)) {
                ProductVariant::whereIn('id', $variantsToDelete)->delete();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product variants updated successfully',
                'product' => new ProductResource($product->load(['translations', 'attributes', 'variants.attributes']))
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Updating product variants failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating product variants',
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
        try {
            $category = Category::with('attributeGroups.attributes.translations')
                ->findOrFail($categoryId);

            $attributes = [];
            foreach ($category->attributeGroups as $group) {
                foreach ($group->attributes as $attribute) {
                    $attributes[] = [
                        'id' => $attribute->id,
                        'name' => $attribute->translations->first()->name,
                        'group' => $group->translations->first()->name
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'attributes' => $attributes
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error getting category attributes',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
