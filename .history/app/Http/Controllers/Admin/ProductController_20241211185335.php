<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FileType;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\FileController;
use App\Http\Requests\ProductImageRequest;
use App\Models\Attribute;
use App\Models\Product;
use App\Models\ProductTranslation;
use App\Models\ProductVariant;
use App\Services\FileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    // protected FileService $fileService;

    // public function __construct(FileService $fileService)
    // {
    //     $this->fileService = $fileService;
    // }

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
            'products' => $query->paginate($request->per_page ?? 10)
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
            $product->variants()->delete(); // Delete old variants
            foreach ($request->variants as $variantData) {
                $product->variants()->create([
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

    public function uploadImages(ProductImageRequest $request)
    {
        try {
            DB::beginTransaction();

            $product = Product::findOrFail($request->product_id);
            $uploadedImages = [];

            $fileController = new FileController($this->fileService);

            foreach ($request->file('images') as $image) {
                $uploadRequest = new \Illuminate\Http\Request(['file' => $image, 'type' => FileType::PRODUCT]);
                $uploadResponse = $fileController->upload($uploadRequest);
                $result = json_decode($uploadResponse->getContent(), true);

                if ($result['status'] === 'success') {
                    $uploadedImages[] = $result['data']['path'];
                } else {
                    throw new \Exception($result['message']);
                }
            }

            // Update product images
            $currentImages = $product->images ?? [];
            $product->images = array_merge($currentImages, $uploadedImages);

            // If this is the first image or is_main is true, set it as the main image
            if (empty($currentImages) || $request->is_main) {
                $product->main_image = end($uploadedImages);
            }

            $product->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Images uploaded successfully',
                'data' => [
                    'images' => $product->images,
                    'main_image' => $product->main_image
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Product image upload failed', [
                'error' => $e->getMessage(),
                'product_id' => $request->product_id
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to upload images: ' . $e->getMessage()
            ], 500);
        }
    }

    public function deleteImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'image_path' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $product = Product::findOrFail($request->product_id);

            // Check if image exists in product images
            if (!in_array($request->image_path, $product->images)) {
                throw new \Exception('Image not found in product images');
            }

            // Delete file from storage
            $this->fileService->delete($request->image_path);

            // Remove image from product images array
            $images = array_diff($product->images, [$request->image_path]);
            $product->images = array_values($images);

            // If deleted image was main image, set first available image as main
            if ($product->main_image === $request->image_path) {
                $product->main_image = !empty($images) ? reset($images) : null;
            }

            $product->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Image deleted successfully',
                'data' => [
                    'images' => $product->images,
                    'main_image' => $product->main_image
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Product image deletion failed', [
                'error' => $e->getMessage(),
                'product_id' => $request->product_id,
                'image_path' => $request->image_path
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete image: ' . $e->getMessage()
            ], 500);
        }
    }

    public function setMainImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'image_path' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $product = Product::findOrFail($request->product_id);

            // Check if image exists in product images
            if (!in_array($request->image_path, $product->images)) {
                throw new \Exception('Image not found in product images');
            }

            $product->main_image = $request->image_path;
            $product->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Main image updated successfully',
                'data' => [
                    'main_image' => $product->main_image
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Setting main image failed', [
                'error' => $e->getMessage(),
                'product_id' => $request->product_id,
                'image_path' => $request->image_path
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to set main image: ' . $e->getMessage()
            ], 500);
        }
    }
}
