<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function index(Request $request)
    {

        $admin = $request->user();
        /*  
        |--------------------------------------------------------------------------
        | 1. STATS
        |--------------------------------------------------------------------------
        */

        $totalRooms = Room::count();
        $totalRoomTypes = RoomType::count();
        $totalUsers = User::count();

        /*
        |--------------------------------------------------------------------------
        | 2. BOOKINGS
        |--------------------------------------------------------------------------
        */

        // booking theo ngày
        $bookingsDaily = Booking::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as total')
        )
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->get();

        // booking theo tháng
        $bookingsMonthly = Booking::select(
            DB::raw('YEAR(created_at) as year'),
            DB::raw('MONTH(created_at) as month'),
            DB::raw('COUNT(*) as total')
        )
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        // booking theo năm
        $bookingsYearly = Booking::select(
            DB::raw('YEAR(created_at) as year'),
            DB::raw('COUNT(*) as total')
        )
            ->groupBy('year')
            ->orderBy('year')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 3. REVENUE
        |--------------------------------------------------------------------------
        */

        // doanh thu theo ngày
        $revenueDaily = Booking::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('SUM(total_price) as total')
        )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // doanh thu theo tháng
        $revenueMonthly = Booking::select(
            DB::raw('YEAR(created_at) as year'),
            DB::raw('MONTH(created_at) as month'),
            DB::raw('SUM(total_price) as total')
        )
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        // doanh thu theo năm
        $revenueYearly = Booking::select(
            DB::raw('YEAR(created_at) as year'),
            DB::raw('SUM(total_price) as total')
        )
            ->groupBy('year')
            ->orderBy('year')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 4. RESPONSE
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'success' => true,

            'admin' => [
                'name' => $admin->name,
                'email' => $admin->email,
                'avatar' => $admin->avatar
            ],

            'stats' => [
                'total_rooms' => $totalRooms,
                'total_room_types' => $totalRoomTypes,
                'total_users' => $totalUsers,
            ],

            'bookings' => [
                'daily' => $bookingsDaily,
                'monthly' => $bookingsMonthly,
                'yearly' => $bookingsYearly,
            ],

            'revenue' => [
                'daily' => $revenueDaily,
                'monthly' => $revenueMonthly,
                'yearly' => $revenueYearly,
            ]
        ]);
    }
}
