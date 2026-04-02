<?php

namespace App\Http\Controllers\Admin;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;


class AdminUserController extends Controller
{
    /**
     * GET /api/admin/users
     * Lấy danh sách người dùng
     * Có thể lọc theo status
     */
    public function index(Request $request)
    {
        $query = User::query()->where('role', '!=', 'admin');

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
        // Cho phép tìm kiếm cả admin
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy người dùng'
            ], 404);
        }

        // Kiểm tra logic auth code/role: Nếu user cần lấy là admin, 
        // người thực hiện request bắt buộc cũng phải là admin
        if ($user->role === 'admin') {
            $currentUser = Auth::user();
            if (!$currentUser || $currentUser->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền truy cập thông tin của quản trị viên'
                ], 403);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    /**
     * POST /api/admin/users
     */
    // public function store(Request $request)
    // {
    //     $validated = $request->validate([
    //         'name' => 'required|string|max:255',
    //         'email' => 'required|email|unique:users,email',
    //         'password' => 'required|string|min:6',

    //         'phone' => 'nullable|string|max:20',
    //         'address' => 'nullable|string|max:255',
    //         'date_of_birth' => 'nullable|date',

    //         'avatar' => 'nullable|string',
    //         'role' => 'nullable|string|max:255',

    //         'status' => 'nullable|in:active,blocked',
    //         'email_verified_at' => 'nullable|date'
    //     ]);

    //     $validated['password'] = Hash::make($validated['password']);
    //     $validated['role'] = $validated['role'] ?? 'user';
    //     $validated['status'] = $validated['status'] ?? 'active';

    //     $user = User::create($validated);

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Tạo người dùng thành công',
    //         'data' => $user
    //     ], 201);
    // }

    /**
     * PUT /api/admin/users/{id}
     */
    // public function update(Request $request, int $id)
    // {
    //     $user = User::find($id);

    //     if (!$user) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Không tìm thấy người dùng'
    //         ], 404);
    //     }

    //     $validated = $request->validate([
    //         'name' => 'sometimes|string|max:255',
    //         'email' => 'sometimes|email|unique:users,email,' . $id,

    //         'phone' => 'nullable|string|max:20',
    //         'address' => 'nullable|string|max:255',
    //         'date_of_birth' => 'nullable|date',

    //         'avatar' => 'nullable|string',
    //         'role' => 'sometimes|string|max:255',

    //         'status' => 'sometimes|in:active,blocked',
    //         'email_verified_at' => 'nullable|date',

    //         'password' => 'nullable|string|min:6'
    //     ]);

    //     if (!empty($validated['password'])) {
    //         $validated['password'] = Hash::make($validated['password']);
    //     }

    //     $user->update($validated);

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Cập nhật người dùng thành công',
    //         'data' => $user
    //     ]);
    // }

    /**
     * PATCH /api/admin/users/{id}/toggle-status
     * Chuyển trạng thái active <-> blocked
     */
    public function toggleStatus(int $id)
    {
        $user = User::query()->find($id);

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
        $user = User::query()->find($id);

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
    /**
     * GET /api/admin/users/stats
     * Thống kê tổng quan user
     */
    public function stats()
    {
        $totalUsers = User::query()->count();

        $activeUsers = User::query()->where('status', 'active')->count();
        $blockedUsers = User::query()->where('status', 'blocked')->count();

        // User mới
        $today = Carbon::today();
        $thisWeek = Carbon::now()->startOfWeek();
        $thisMonth = Carbon::now()->startOfMonth();

        $newToday = User::query()->whereDate('created_at', $today)->count();
        $newWeek = User::query()->where('created_at', '>=', $thisWeek)->count();
        $newMonth = User::query()->where('created_at', '>=', $thisMonth)->count();

        // User chưa verify email
        $unverifiedUsers = User::query()->whereNull('email_verified_at')->count();

        // User lâu không login (nếu có last_login_at)
        // $inactiveUsers = User::where('last_login_at', '<', Carbon::now()->subDays(30))->count();

        // Thống kê theo role
        $usersByRole = User::query()
            ->select('role', DB::raw('count(*) as total'))
            ->groupBy('role')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total_users' => $totalUsers,
                'active_users' => $activeUsers,
                'blocked_users' => $blockedUsers,

                'new_users' => [
                    'today' => $newToday,
                    'this_week' => $newWeek,
                    'this_month' => $newMonth,
                ],

                'unverified_users' => $unverifiedUsers,
                // 'inactive_30_days' => $inactiveUsers,

                'users_by_role' => $usersByRole,
            ]
        ]);
    }
    /**
     * GET /api/admin/users/chart
     * Biểu đồ user theo thời gian
     */
    public function chart(Request $request)
    {
        $type = $request->get('type', '7_days');

        if ($type === '7_days') {
            $data = User::query()
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('count(*) as total')
                )
                ->where('created_at', '>=', Carbon::now()->subDays(7))
                ->groupBy('date')
                ->orderBy('date')
                ->get();
        } elseif ($type === '12_months') {
            $data = User::query()
                ->select(
                    DB::raw('MONTH(created_at) as month'),
                    DB::raw('count(*) as total')
                )
                ->where('created_at', '>=', Carbon::now()->subMonths(12))
                ->groupBy('month')
                ->orderBy('month')
                ->get();
        } else {
            $data = [];
        }

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
    /**
     * GET /api/admin/users/top
     */
    public function topUsers()
    {
        $topUsers = Booking::select('user_id', DB::raw('count(*) as total_bookings'))
            ->groupBy('user_id')
            ->orderByDesc('total_bookings')
            ->with('user:id,name,email')
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $topUsers
        ]);
    }
    
}
