<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:jpeg,png,jpg,webp|max:2048',
            'type' => 'required|in:categories,products,brands'
        ]);

        try {
            $file = $request->file('file');
            $filename = Str::random(32) . '.' . $file->getClientOriginalExtension();
            $path = $request->type . '/' . $filename;

            // Save file to storage
            Storage::disk('public')->put($path, file_get_contents($file));

            return response()->json([
                'status' => 'success',
                'message' => 'File uploaded successfully',
                'data' => [
                    'path' => $path,
                  //  'url' => Storage::disk('public')->url($path)
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'File upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function delete(Request $request)
    {
        $request->validate([
            'path' => 'required|string'
        ]);

        try {
            if (Storage::disk('public')->exists($request->path)) {
                Storage::disk('public')->delete($request->path);
                return response()->json([
                    'success' => true,
                    'message' => 'File deleted successfully'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'File not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'File deletion failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
