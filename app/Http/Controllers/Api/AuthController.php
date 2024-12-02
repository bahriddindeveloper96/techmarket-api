<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();
        
        try {
            DB::beginTransaction();
            
            $user = User::create([
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'phone' => $validated['phone']
            ]);

            // Create translations for all supported languages
            foreach (config('app.supported_locales', ['en', 'ru', 'uz']) as $locale) {
                $user->translations()->create([
                    'locale' => $locale,
                    'name' => $validated['name']
                ]);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => __('auth.register_success'),
                'data' => [
                    'user' => $user->load('translations'),
                    'token' => $token
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => __('messages.login_failed')
            ], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
            'message' => __('messages.login_success')
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => __('messages.logout_success')
        ]);
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }
}
