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
        $totalBookings = Booking::count();

        /*
        |--------------------------------------------------------------------------
        | 2. BOOKINGS CHART
        |--------------------------------------------------------------------------
        */

        $bookingsDaily = Booking::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as total')
        )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $bookingsMonthly = Booking::select(
            DB::raw('YEAR(created_at) as year'),
            DB::raw('MONTH(created_at) as month'),
            DB::raw('COUNT(*) as total')
        )
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        $bookingsYearly = Booking::select(
            DB::raw('YEAR(created_at) as year'),
            DB::raw('COUNT(*) as total')
        )
            ->groupBy('year')
            ->orderBy('year')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 3. REVENUE CHART
        |--------------------------------------------------------------------------
        */

        $revenueDaily = Booking::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('SUM(total_price) as total')
        )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $revenueMonthly = Booking::select(
            DB::raw('YEAR(created_at) as year'),
            DB::raw('MONTH(created_at) as month'),
            DB::raw('SUM(total_price) as total')
        )
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        $revenueYearly = Booking::select(
            DB::raw('YEAR(created_at) as year'),
            DB::raw('SUM(total_price) as total')
        )
            ->groupBy('year')
            ->orderBy('year')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 4. TOP 5 ROOMS MOST BOOKED
        |--------------------------------------------------------------------------
        */

        $topRooms = Room::select(
            'rooms.id',
            'rooms.room_number',
            DB::raw('COUNT(bookings.id) as total_bookings')
        )
            ->leftJoin('bookings', 'bookings.room_id', '=', 'rooms.id')
            ->groupBy('rooms.id', 'rooms.room_number')
            ->orderByDesc('total_bookings')
            ->limit(5)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 5. ROOM TYPE BOOKING PERCENTAGE
        |--------------------------------------------------------------------------
        */

        $roomTypeStats = RoomType::select(
            'room_types.id',
            'room_types.name',
            DB::raw('COUNT(bookings.id) as total_bookings'),
            DB::raw('ROUND((COUNT(bookings.id) / ' . $totalBookings . ') * 100,2) as percentage')
        )
            ->leftJoin('rooms', 'rooms.room_type_id', '=', 'room_types.id')
            ->leftJoin('bookings', 'bookings.room_id', '=', 'rooms.id')
            ->groupBy('room_types.id', 'room_types.name')
            ->orderByDesc('total_bookings')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 6. LATEST BOOKINGS
        |--------------------------------------------------------------------------
        */

        $latestBookings = Booking::with(['user', 'room'])
            ->latest()
            ->limit(5)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 7. RESPONSE
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
                'total_bookings' => $totalBookings
            ],

            'bookings' => [
                'daily' => $bookingsDaily,
                'monthly' => $bookingsMonthly,
                'yearly' => $bookingsYearly
            ],

            'revenue' => [
                'daily' => $revenueDaily,
                'monthly' => $revenueMonthly,
                'yearly' => $revenueYearly
            ],

            'top_rooms' => $topRooms,

            'room_type_percentage' => $roomTypeStats,

            'latest_bookings' => $latestBookings
        ]);
    }
}
