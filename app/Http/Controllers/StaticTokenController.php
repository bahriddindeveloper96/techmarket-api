<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StaticTokenController extends Controller
{
    private const STATIC_TOKEN = 'turbo_api_access_token_2024';

    public function generateToken()
    {
        return response()->json([
            'token' => self::STATIC_TOKEN,
            'type' => 'Bearer'
        ]);
    }

    public function validateToken(Request $request)
    {
        $token = $request->bearerToken();
        
        if ($token === self::STATIC_TOKEN) {
            return response()->json([
                'valid' => true,
                'message' => 'Token is valid'
            ]);
        }

        return response()->json([
            'valid' => false,
            'message' => 'Invalid token'
        ], 401);
    }
}
