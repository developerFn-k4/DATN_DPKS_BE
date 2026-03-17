<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    /**
     * GET /api/admin/users
     * Lấy danh sách người dùng
     * Có thể lọc theo status
     */
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $users = $query->orderBy('id', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * GET /api/admin/users/{id}
     */
    public function show(int $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy người dùng'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    /**
     * POST /api/admin/users
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',

            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date',

            'avatar' => 'nullable|string',
            'role' => 'nullable|string|max:255',

            'status' => 'nullable|in:active,blocked',
            'email_verified_at' => 'nullable|date'
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['role'] = $validated['role'] ?? 'user';
        $validated['status'] = $validated['status'] ?? 'active';

        $user = User::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tạo người dùng thành công',
            'data' => $user
        ], 201);
    }

    /**
     * PUT /api/admin/users/{id}
     */
    public function update(Request $request, int $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy người dùng'
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,

            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date',

            'avatar' => 'nullable|string',
            'role' => 'sometimes|string|max:255',

            'status' => 'sometimes|in:active,blocked',
            'email_verified_at' => 'nullable|date',

            'password' => 'nullable|string|min:6'
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật người dùng thành công',
            'data' => $user
        ]);
    }

    /**
     * PATCH /api/admin/users/{id}/toggle-status
     * Chuyển trạng thái active <-> blocked
     */
    public function toggleStatus(int $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy người dùng'
            ], 404);
        }

        $user->status = $user->status === 'active' ? 'blocked' : 'active';
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật trạng thái thành công',
            'data' => [
                'id' => $user->id,
                'status' => $user->status
            ]
        ]);
    }

    /**
     * DELETE /api/admin/users/{id}
     */
    public function destroy(int $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy người dùng'
            ], 404);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa người dùng thành công'
        ]);
    }
}