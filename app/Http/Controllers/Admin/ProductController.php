<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FileType;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\FileController;

use App\Models\Attribute;
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
        try {
            $product = Product::with(['translations', 'variants', 'category'])
                ->findOrFail($id);

            return response()->json([
                'message' => 'Product retrieved successfully',
                'data' => $product
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Product not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:categories,id',
            'user_id' => 'required|exists:users,id',
            'slug' => 'required|string|unique:products',
            'active' => 'required|boolean',
            'featured' => 'required|boolean',
            'attributes' => 'required|array',
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
            'variants' => 'required|array',
            'variants.*.attribute_values' => 'required|array',
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.stock' => 'required|integer|min:0',
            'variants.*.images' => 'array'
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
            $product = Product::create([
                'user_id' => $request->user_id,
                'category_id' => $request->category_id,
                'slug' => $request->slug,
                'active' => $request->active,
                'featured' => $request->featured,
                'attributes' => $request->attributes
            ]);

            // Create translations
            foreach ($request->translations as $locale => $translation) {
                $product->translations()->create([
                    'locale' => $locale,
                    'name' => $translation['name'],
                    'description' => $translation['description']
                ]);
            }

            // Create variants
            foreach ($request->variants as $variantData) {
                $variant = $product->variants()->create([
                    'attribute_values' => $variantData['attribute_values'],
                    'price' => $variantData['price'],
                    'stock' => $variantData['stock'],
                    'active' => true,
                    'images' => $variantData['images'] ?? [],
                    'sku' => strtoupper(Str::slug($request->translations['en']['name'])) . '-' . strtoupper(Str::random(4))
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully',
                'data' => $product->load(['translations', 'variants'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error creating product: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:categories,id',
            'user_id' => 'required|exists:users,id',
            'slug' => 'required|string|unique:products,slug,' . $id,
            'active' => 'required|boolean',
            'featured' => 'required|boolean',
            'attributes' => 'required|array',
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
            'variants' => 'required|array',
            'variants.*.attribute_values' => 'required|array',
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.stock' => 'required|integer|min:0',
            'variants.*.images' => 'array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Update product
            $product->update([
                'user_id' => $request->user_id,
                'category_id' => $request->category_id,
                'slug' => $request->slug,
                'active' => $request->active,
                'featured' => $request->featured,
                'attributes' => $request->attributes
            ]);

            // Update translations
            foreach ($request->translations as $locale => $translation) {
                ProductTranslation::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'locale' => $locale
                    ],
                    [
                        'name' => $translation['name'],
                        'description' => $translation['description']
                    ]
                );
            }

            // Update variants
            if ($request->has('variants')) {
                foreach ($request->variants as $variantData) {
                    // Existing variant bo'lsa update qilish
                    $variant = $product->variants()
                        ->where('attribute_values', json_encode($variantData['attribute_values']))
                        ->first();
                    
                    if ($variant) {
                        $variant->update([
                            'price' => $variantData['price'],
                            'stock' => $variantData['stock'],
                            'active' => true,
                            'images' => $variantData['images'] ?? [],
                            'sku' => strtoupper(Str::slug($request->translations['en']['name'])) . '-' . strtoupper(Str::random(4))
                        ]);
                    } else {
                        // Yangi variant yaratish
                        $product->variants()->create([
                            'attribute_values' => $variantData['attribute_values'],
                            'price' => $variantData['price'],
                            'stock' => $variantData['stock'],
                            'active' => true,
                            'images' => $variantData['images'] ?? [],
                            'sku' => strtoupper(Str::slug($request->translations['en']['name'])) . '-' . strtoupper(Str::random(4))
                        ]);
                    }
                }
                
                // Eski, ishlatilmagan variantlarni deactivate qilish
                $product->variants()
                    ->whereNotIn('attribute_values', collect($request->variants)->pluck('attribute_values')->map(fn($av) => json_encode($av)))
                    ->update(['active' => false]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully',
                'data' => $product->load(['translations', 'variants', 'category'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error updating product: ' . $e->getMessage()
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
            'attribute_values' => 'required|array',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'images' => 'nullable|array',
            'active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $product = Product::findOrFail($productId);

            // Check if variant with same attributes exists
            $existingVariant = $product->variants()
                ->where('attribute_values', json_encode($request->attribute_values))
                ->first();

            if ($existingVariant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Variant with these attributes already exists'
                ], 422);
            }

            // Create new variant
            $variant = $product->variants()->create([
                'attribute_values' => $request->attribute_values,
                'price' => $request->price,
                'stock' => $request->stock,
                'active' => $request->input('active', true),
                'images' => $request->input('images', []),
                'sku' => strtoupper(Str::slug($product->translations->where('locale', 'en')->first()->name)) 
                    . '-' . strtoupper(Str::random(4))
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Variant added successfully',
                'data' => [
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
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error adding variant: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update specific variant of a product
     */
    public function updateVariant(Request $request, $productId, $variantId)
    {
        $validator = Validator::make($request->all(), [
            'attribute_values' => 'array',
            'price' => 'numeric|min:0',
            'stock' => 'integer|min:0',
            'images' => 'nullable|array',
            'active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $product = Product::findOrFail($productId);
            
            $variant = $product->variants()
                ->where('id', $variantId)
                ->firstOrFail();

            // Check if attribute values are being updated and if they would conflict
            if ($request->has('attribute_values')) {
                $existingVariant = $product->variants()
                    ->where('id', '!=', $variantId)
                    ->where('attribute_values', json_encode($request->attribute_values))
                    ->first();

                if ($existingVariant) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Another variant with these attributes already exists'
                    ], 422);
                }
            }

            // Update variant
            $updateData = [];
            
            if ($request->has('attribute_values')) {
                $updateData['attribute_values'] = $request->attribute_values;
            }
            if ($request->has('price')) {
                $updateData['price'] = $request->price;
            }
            if ($request->has('stock')) {
                $updateData['stock'] = $request->stock;
            }
            if ($request->has('images')) {
                $updateData['images'] = $request->images;
            }
            if ($request->has('active')) {
                $updateData['active'] = $request->active;
            }

            $variant->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Variant updated successfully',
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
                'message' => 'Error updating variant: ' . $e->getMessage()
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

    // public function purchaseHistory()
    // {
    //     $products = auth()->user()->orders()
    //         ->with(['items.product'])
    //         ->latest()
    //         ->get()
    //         ->pluck('items')
    //         ->flatten()
    //         ->pluck('product')
    //         ->unique('id');

    //     return response()->json([
    //         'products' => $products
    //     ]);
    // }
}
