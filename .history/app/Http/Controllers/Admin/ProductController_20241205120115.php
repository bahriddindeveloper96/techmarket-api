<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['category', 'translations']);

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('translations', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by status
        if ($request->has('active')) {
            $query->where('active', $request->boolean('active'));
        }

        // Filter by featured
        if ($request->has('featured')) {
            $query->where('featured', $request->boolean('featured'));
        }

        // Sort
        $sortField = $request->get('sort_field', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortField, $sortOrder);

        return response()->json([
            'products' => $query->paginate($request->get('per_page', 15))
        ]);
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
                'user_id' => auth()->id(),
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
            $product->delete();
            return response()->json([
                'message' => 'Product deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function toggleActive(Product $product)
    {
        $product->update(['active' => !$product->active]);
        return response()->json([
            'message' => 'Product status updated successfully',
            'active' => $product->active
        ]);
    }

    public function toggleFeatured(Product $product)
    {
        $product->update(['featured' => !$product->featured]);
        return response()->json([
            'message' => 'Product featured status updated successfully',
            'featured' => $product->featured
        ]);
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:products,id'
        ]);

        try {
            Product::whereIn('id', $request->ids)->delete();
            return response()->json([
                'message' => 'Products deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete products',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:products,id',
            'data' => 'required|array'
        ]);

        try {
            Product::whereIn('id', $request->ids)->update($request->data);
            return response()->json([
                'message' => 'Products updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update products',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
