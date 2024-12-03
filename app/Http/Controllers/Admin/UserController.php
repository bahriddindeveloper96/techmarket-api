<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Display a listing of users
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Apply search filter
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Apply role filter
        if ($request->has('role')) {
            $query->where('role', $request->get('role'));
        }

        // Apply sorting
        $sortField = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortField, $sortOrder);

        $users = $query->paginate($request->get('per_page', 10));

        return response()->json([
            'data' => $users->items(),
            'total' => $users->total(),
            'per_page' => $users->perPage(),
            'current_page' => $users->currentPage(),
            'last_page' => $users->lastPage()
        ]);
    }

    /**
     * Store a new user
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'phone' => 'nullable|string',
            'role' => 'required|in:admin,user',
            'status' => 'required|in:active,inactive'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'role' => $request->role,
            'status' => $request->status
        ]);

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user
        ]);
    }

    /**
     * Update user details
     */
    public function update(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string',
            'role' => 'required|in:admin,user',
            'status' => 'required|in:active,inactive',
            'password' => 'nullable|min:6'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = [
            'email' => $request->email,
            'phone' => $request->phone,
            'role' => $request->role,
            'status' => $request->status
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user
        ]);
    }

    /**
     * Delete a user
     */
    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return response()->json([
                'message' => 'Cannot delete your own account'
            ], 403);
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully'
        ]);
    }

    /**
     * Toggle user active status
     */
    public function toggleActive(User $user)
    {
        if ($user->id === auth()->id()) {
            return response()->json([
                'message' => 'Cannot toggle your own account status'
            ], 403);
        }

        $user->update([
            'status' => $user->status === 'active' ? 'inactive' : 'active'
        ]);

        return response()->json([
            'message' => 'User status updated successfully',
            'user' => $user
        ]);
    }
}
