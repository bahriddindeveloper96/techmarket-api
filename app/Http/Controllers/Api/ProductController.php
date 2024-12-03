<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductTranslation;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with(['translations', 'category', 'variants'])
            ->where('active', true)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $products
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
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'translations' => 'required|array',
            'translations.uz' => 'required|array',
            'translations.uz.name' => 'required|string|max:255',
            'translations.uz.description' => 'required|string',
            'translations.ru' => 'required|array',
            'translations.ru.name' => 'required|string|max:255',
            'translations.ru.description' => 'required|string',
            'translations.en' => 'required|array',
            'translations.en.name' => 'required|string|max:255',
            'translations.en.description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'attributes' => 'array',
        ]);

        try {
            DB::beginTransaction();

            // Generate unique slug
            $slug = Str::slug($request->input('translations.en.name'));
            $originalSlug = $slug;
            $count = 1;
            
            while (Product::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $count;
                $count++;
            }

            // Create product
            $product = Product::create([
                'category_id' => $request->input('category_id'),
                'slug' => $slug,
                'price' => $request->input('price'),
                'active' => true,
                'attributes' => $request->input('attributes', [])
            ]);

            // Create default variant if no variants provided
            $product->variants()->create([
                'name' => 'Default',
                'price' => $request->input('price'),
                'stock' => 0,
                'active' => true,
                'sku' => strtoupper(Str::slug($request->input('translations.en.name'))) . '-' . strtoupper(Str::random(4)),
                'attribute_values' => []
            ]);

            // Create translations
            foreach ($request->translations as $locale => $translation) {
                $product->translations()->create([
                    'locale' => $locale,
                    'name' => $translation['name'],
                    'description' => $translation['description']
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Product created successfully',
                'data' => $product->load(['translations', 'variants'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error creating product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'slug' => 'unique:products,slug,' . $id,
            'category_id' => 'exists:categories,id',
            'images' => 'nullable|array',
            'active' => 'boolean',
            'featured' => 'boolean',
            'translations' => 'array',
            'translations.*.locale' => 'required|in:en,ru,uz',
            'translations.*.name' => 'required|string',
            'translations.*.description' => 'required|string',
            'attributes' => 'array',
            'variants' => 'array',
            'variants.*.attribute_values' => 'required|array',
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.stock' => 'required|integer|min:0'
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
            $product->update($request->only([
                'slug', 'category_id', 'images', 'active', 'featured'
            ]));

            // Update translations
            if ($request->has('translations')) {
                foreach ($request->translations as $translation) {
                    ProductTranslation::updateOrCreate(
                        [
                            'product_id' => $product->id,
                            'locale' => $translation['locale']
                        ],
                        [
                            'name' => $translation['name'],
                            'description' => $translation['description']
                        ]
                    );
                }
            }

            // Update attributes
            if ($request->has('attributes')) {
                $product->attributes()->detach();
                foreach ($request->attributes as $attributeName => $value) {
                    $attribute = Attribute::where('name', $attributeName)->first();
                    if ($attribute) {
                        $product->attributes()->attach($attribute->id, ['value' => $value]);
                    }
                }
            }

            // Update variants
            if ($request->has('variants')) {
                // Delete old variants
                $product->variants()->delete();
                
                // Create new variants
                foreach ($request->variants as $variantData) {
                    $product->createVariant(
                        $variantData['attribute_values'],
                        $variantData['price'],
                        $variantData['stock']
                    );
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $product->load(['translations', 'category', 'variants', 'attributes.group'])
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
}
