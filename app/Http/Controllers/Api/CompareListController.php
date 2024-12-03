<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CompareList;

class CompareListController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json([
            'compare_list' => auth()->user()->compareLists()
                ->with('product')
                ->latest()
                ->paginate(10)
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id'
        ]);

        // Check if user has reached maximum products to compare (e.g., 4)
        $compareCount = auth()->user()->compareLists()->count();
        if ($compareCount >= 4) {
            return response()->json([
                'message' => 'Maximum products to compare reached (4)'
            ], 400);
        }

        $compareItem = auth()->user()->compareLists()->create([
            'product_id' => $request->product_id
        ]);

        return response()->json([
            'message' => 'Product added to compare list',
            'compare_item' => $compareItem->load('product')
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CompareList $compareList)
    {
        $this->authorize('delete', $compareList);
        
        $compareList->delete();

        return response()->json([
            'message' => 'Product removed from compare list'
        ]);
    }
}
