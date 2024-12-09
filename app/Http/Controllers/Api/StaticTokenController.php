<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StaticTokenController extends Controller
{
    public function generateToken()
    {
        // Agar statik foydalanuvchi mavjud bo'lmasa, yaratamiz
        $staticUser = User::firstOrCreate(
            ['email' => 'static_access@techmarket.api'],
            [
                'name' => 'Static API Access',
                'password' => bcrypt(Str::random(40)),
                'role' => 'api_access',
                'status' => 'active'
            ]
        );

        // Agar oldindan token yaratilgan bo'lsa, uni o'chiramiz
        $staticUser->tokens()->delete();

        // Yangi token yaratamiz
        $token = $staticUser->createToken('static_api_token', ['*'])->plainTextToken;

        return response()->json([
            'token' => $token,
            'type' => 'Bearer',
            'user' => [
                'id' => $staticUser->id,
                'name' => $staticUser->name,
                'role' => $staticUser->role
            ]
        ]);
    }

    public function validateToken(Request $request)
    {
        $user = $request->user();
        
        if ($user && $user->email === 'static_access@techmarket.api') {
            return response()->json([
                'valid' => true,
                'message' => 'Token is valid',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'role' => $user->role
                ]
            ]);
        }

        return response()->json([
            'valid' => false,
            'message' => 'Invalid or expired token'
        ], 401);
    }
}
