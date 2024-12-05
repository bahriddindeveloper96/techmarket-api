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
            'translations.*.name' => 'required|string|max:255',
            'translations.*.description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'images' => 'required|array',
            'images.*' => 'string'
        ]);

        try {
            DB::beginTransaction();

            // Create product
            $product = Product::create([
                'category_id' => $request->category_id,
                'price' => $request->price,
                'stock' => $request->stock,
                'images' => $request->images,
                'active' => $request->boolean('active', true),
                'featured' => $request->boolean('featured', false),
                'slug' => Str::slug($request->translations['en']['name'])
            ]);

            // Create translations
            foreach ($request->translations as $locale => $translation) {
                $product->translations()->create([
                    'locale' => $locale,
                    'name' => $translation['name'],
                    'description' => $translation['description']
                ]);
            }

            // Add attributes if any
            if ($request->has('attributes')) {
                foreach ($request->attributes as $attributeId => $value) {
                    $product->attributes()->attach($attributeId, ['value' => $value]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Product created successfully',
                'product' => $product->load('translations', 'category')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'translations' => 'required|array',
            'translations.*.name' => 'required|string|max:255',
            'translations.*.description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'images' => 'required|array',
            'images.*' => 'string'
        ]);

        try {
            DB::beginTransaction();

            // Update product
            $product->update([
                'category_id' => $request->category_id,
                'price' => $request->price,
                'stock' => $request->stock,
                'images' => $request->images,
                'active' => $request->boolean('active', true),
                'featured' => $request->boolean('featured', false),
                'slug' => Str::slug($request->translations['en']['name'])
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

            // Update attributes
            if ($request->has('attributes')) {
                $product->attributes()->sync($request->attributes);
            }

            DB::commit();

            return response()->json([
                'message' => 'Product updated successfully',
                'product' => $product->load('translations', 'category')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update product',
                'error' => $e->getMessage()
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
