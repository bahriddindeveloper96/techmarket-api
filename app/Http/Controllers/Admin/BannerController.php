<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Admin Banners",
 *     description="API Endpoints for managing banners"
 * )
 */
class BannerController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/admin/banners",
     *     summary="Get all banners",
     *     tags={"Admin Banners"},
     *     security={{"bearer_token":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     */
    public function index()
    {
        $banners = Banner::orderBy('order')->get();

        return response()->json([
            'success' => true,
            'data' => $banners
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/banners",
     *     summary="Create a new banner",
     *     tags={"Admin Banners"},
     *     security={{"bearer_token":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string", example="Special Offer"),
     *             @OA\Property(property="description", type="string", example="Get 20% off on all products"),
     *             @OA\Property(property="image", type="string", example="/storage/banners/special-offer.jpg"),
     *             @OA\Property(property="url", type="string", example="/special-offers"),
     *             @OA\Property(property="button_text", type="string", example="Shop Now"),
     *             @OA\Property(property="order", type="integer", example=1),
     *             @OA\Property(property="active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Banner created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Banner created successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'required|string',
            'url' => 'nullable|string|max:255',
            'button_text' => 'nullable|string|max:255',
            'order' => 'nullable|integer',
            'active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $banner = Banner::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Banner created successfully',
            'data' => $banner
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/banners/{id}",
     *     summary="Get banner by ID",
     *     tags={"Admin Banners"},
     *     security={{"bearer_token":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function show(Banner $banner)
    {
        return response()->json([
            'success' => true,
            'data' => $banner
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/admin/banners/{id}",
     *     summary="Update banner",
     *     tags={"Admin Banners"},
     *     security={{"bearer_token":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string", example="Special Offer"),
     *             @OA\Property(property="description", type="string", example="Get 20% off on all products"),
     *             @OA\Property(property="image", type="string", example="/storage/banners/special-offer.jpg"),
     *             @OA\Property(property="url", type="string", example="/special-offers"),
     *             @OA\Property(property="button_text", type="string", example="Shop Now"),
     *             @OA\Property(property="order", type="integer", example=1),
     *             @OA\Property(property="active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Banner updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Banner updated successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function update(Request $request, Banner $banner)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'required|string',
            'url' => 'nullable|string|max:255',
            'button_text' => 'nullable|string|max:255',
            'order' => 'nullable|integer',
            'active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $banner->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Banner updated successfully',
            'data' => $banner
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/banners/{id}",
     *     summary="Delete banner",
     *     tags={"Admin Banners"},
     *     security={{"bearer_token":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Banner deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Banner deleted successfully")
     *         )
     *     )
     * )
     */
    public function destroy(Banner $banner)
    {
        $banner->delete();

        return response()->json([
            'success' => true,
            'message' => 'Banner deleted successfully'
        ]);
    }
}
