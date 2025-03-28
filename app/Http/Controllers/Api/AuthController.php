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
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            DB::beginTransaction();

            $user = User::create([
                'firstname' => $request->firstname,
                'lastname' => $request->lastname,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'role' => $request->role ?? 'user',
                'status' => 'active',
                'bio' => $request->bio,
                'address' => $request->address
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => __('auth.register_success'),
                'data' => [
                    'user' => $user,
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

    public function createStaticTokenUser(Request $request)
    {
        // Faqat admin ro'yxatdan o'tkazishi mumkin
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            return response()->json([
                'message' => 'Unauthorized. Only admin can create static token users.'
            ], 403);
        }

        // Validatsiya
        $validator = Validator::make($request->all(), [
            'translations' => 'required|array',
            'translations.*.locale' => 'required|string|in:uz,ru,en',
            'translations.*.name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')
            ],
            'password' => 'required|string|min:8|confirmed',
            'role' => 'in:user,seller' // Faqat user yoki seller
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        // Foydalanuvchi yaratish
        $user = User::create([
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => $request->role ?? 'user', // Agar role ko'rsatilmagan bo'lsa, default user
            'status' => 'active'
        ]);

        // Token yaratish
        $token = $user->createToken('static_api_token', ['*'])->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
            'message' => 'Static token user created successfully'
        ], 201);
    }
}
