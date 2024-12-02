<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductTranslation;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

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
        $product = Product::with(['translations', 'category', 'variants', 'attributes.group'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $product
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'slug' => 'required|unique:products',
            'category_id' => 'required|exists:categories,id',
            'images' => 'nullable|array',
            'active' => 'boolean',
            'featured' => 'boolean',
            'translations' => 'required|array',
            'translations.*.locale' => 'required|in:en,ru,uz',
            'translations.*.name' => 'required|string',
            'translations.*.description' => 'required|string',
            'attributes' => 'required|array',
            'variants' => 'required|array',
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

            // Create product
            $product = Product::create($request->only([
                'slug', 'category_id', 'images', 'active', 'featured'
            ]));

            // Create translations
            foreach ($request->translations as $translation) {
                ProductTranslation::create([
                    'product_id' => $product->id,
                    'locale' => $translation['locale'],
                    'name' => $translation['name'],
                    'description' => $translation['description']
                ]);
            }

            // Add attributes
            foreach ($request->attributes as $attributeName => $value) {
                $attribute = Attribute::where('name', $attributeName)->first();
                if ($attribute) {
                    $product->attributes()->attach($attribute->id, ['value' => $value]);
                }
            }

            // Create variants
            foreach ($request->variants as $variantData) {
                $product->createVariant(
                    $variantData['attribute_values'],
                    $variantData['price'],
                    $variantData['stock']
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $product->load(['translations', 'category', 'variants', 'attributes.group'])
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

    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully'
        ]);
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
        $variant = ProductVariant::where('product_id', $productId)
            ->where('id', $variantId)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'stock' => $variant->stock
            ]
        ]);
    }
}
