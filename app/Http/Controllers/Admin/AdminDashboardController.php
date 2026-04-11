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
        $totalUsers = User::where('role', '!=', 'admin')->count();

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
        | 4. TOTAL BOOKINGS
        |--------------------------------------------------------------------------
        */

        $totalBookings = Booking::count();

        /*
        |--------------------------------------------------------------------------
        | 5. TOP ROOM TYPES MOST BOOKED
        |--------------------------------------------------------------------------
        */

        $topRoomTypes = RoomType::select(
            'room_types.id',
            'room_types.name',
            DB::raw('COUNT(bookings.id) as total_bookings')
        )
            ->leftJoin('rooms', 'rooms.room_type_id', '=', 'room_types.id')
            ->leftJoin('booking_rooms', 'booking_rooms.room_id', '=', 'rooms.id')
            ->leftJoin('bookings', 'bookings.id', '=', 'booking_rooms.booking_id')
            ->groupBy('room_types.id', 'room_types.name')
            ->orderByDesc('total_bookings')
            ->limit(5)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 6. ROOM TYPE BOOKING PERCENTAGE
        |--------------------------------------------------------------------------
        */

        $roomTypeStats = RoomType::select(
            'room_types.id',
            'room_types.name',
            DB::raw('COUNT(bookings.id) as total_bookings'),
            DB::raw('ROUND((COUNT(bookings.id) / ' . max($totalBookings, 1) . ') * 100,2) as percentage')
        )
            ->leftJoin('rooms', 'rooms.room_type_id', '=', 'room_types.id')
            ->leftJoin('booking_rooms', 'booking_rooms.room_id', '=', 'rooms.id')
            ->leftJoin('bookings', 'bookings.id', '=', 'booking_rooms.booking_id')
            ->groupBy('room_types.id', 'room_types.name')
            ->orderByDesc('total_bookings')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 7. LATEST BOOKINGS
        |--------------------------------------------------------------------------
        */

        $latestBookings = Booking::with(['user'])
            ->latest()
            ->limit(5)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 8. RESPONSE
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

            'top_room_types' => $topRoomTypes,

            'room_type_percentage' => $roomTypeStats,

            'latest_bookings' => $latestBookings
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | THỐNG KÊ CÓ BỘ LỌC
    | GET /api/admin/stats?mode=daily&year=2026&month=4
    | GET /api/admin/stats?mode=monthly&year=2026
    | GET /api/admin/stats?mode=yearly
    | GET /api/admin/stats?from_date=2026-01-01&to_date=2026-04-30  ← lọc khoảng ngày
    |--------------------------------------------------------------------------
    */
    public function stats(Request $request)
    {
        $request->validate([
            'mode'      => 'nullable|in:daily,monthly,yearly,range',
            'year'      => 'nullable|integer|min:2000|max:2100',
            'month'     => 'nullable|integer|min:1|max:12',
            'from_date' => 'nullable|date_format:Y-m-d',
            'to_date'   => 'nullable|date_format:Y-m-d|after_or_equal:from_date',
        ]);

        $fromDate = $request->get('from_date');
        $toDate   = $request->get('to_date');

        // Nếu truyền from_date + to_date → ưu tiên mode range
        if ($fromDate && $toDate) {
            $mode = 'range';
        } else {
            $mode  = $request->get('mode', 'daily');
            $year  = (int) $request->get('year', now()->year);
            $month = (int) $request->get('month', now()->month);
        }

        /*
        |------------------------------------------------------------------
        | XÂY DỰNG CHART DATA + TÍNH TOÁN THEO MODE
        |------------------------------------------------------------------
        */
        switch ($mode) {

            /*
             * KHOẢNG NGÀY TÙY CHỌN: from_date → to_date
             * Chart: từng ngày trong khoảng, label = dd/mm
             */
            case 'range':
                $from = \Carbon\Carbon::parse($fromDate)->startOfDay();
                $to   = \Carbon\Carbon::parse($toDate)->endOfDay();

                $rawBookings = Booking::select(
                    DB::raw('DATE(created_at) as period'),
                    DB::raw('COUNT(*) as total_bookings'),
                    DB::raw('SUM(total_price) as total_revenue')
                )
                    ->whereBetween('created_at', [$from, $to])
                    ->groupBy('period')
                    ->orderBy('period')
                    ->get()
                    ->keyBy('period');

                // Tạo đủ mỗi ngày trong khoảng, ngày không có data = 0
                $days      = [];
                $current   = $from->copy();
                while ($current->lte($to)) {
                    $key  = $current->toDateString();
                    $item = $rawBookings->get($key);
                    $days[] = [
                        'label'          => $current->format('d/m'),
                        'total_bookings' => $item ? (int) $item->total_bookings : 0,
                        'total_revenue'  => $item ? (float) $item->total_revenue : 0,
                    ];
                    $current->addDay();
                }

                $chartData    = collect($days);
                $totalPeriods = $chartData->count();
                $periodLabel  = "Từ {$from->format('d/m/Y')} đến {$to->format('d/m/Y')}";
                break;

            /*
             * THEO NGÀY: lấy từng ngày trong tháng được chọn
             */
            case 'daily':
                $daysInMonth = \Carbon\Carbon::create($year, $month)->daysInMonth;

                $rawBookings = Booking::select(
                    DB::raw('DAY(created_at) as period'),
                    DB::raw('COUNT(*) as total_bookings'),
                    DB::raw('SUM(total_price) as total_revenue')
                )
                    ->whereYear('created_at', $year)
                    ->whereMonth('created_at', $month)
                    ->groupBy('period')
                    ->get()
                    ->keyBy('period');

                // Tạo đủ 30/31 ngày, ngày nào không có data thì = 0
                $chartData = collect(range(1, $daysInMonth))->map(function ($day) use ($rawBookings) {
                    $item = $rawBookings->get($day);
                    return [
                        'label'          => str_pad($day, 2, '0', STR_PAD_LEFT),
                        'total_bookings' => $item ? (int) $item->total_bookings : 0,
                        'total_revenue'  => $item ? (float) $item->total_revenue : 0,
                    ];
                });

                $totalPeriods = $daysInMonth;
                $periodLabel  = "Theo ngày - Tháng {$month}/{$year}";
                break;

            /*
             * THEO THÁNG: lấy từng tháng trong năm được chọn
             */
            case 'monthly':
                $rawBookings = Booking::select(
                    DB::raw('MONTH(created_at) as period'),
                    DB::raw('COUNT(*) as total_bookings'),
                    DB::raw('SUM(total_price) as total_revenue')
                )
                    ->whereYear('created_at', $year)
                    ->groupBy('period')
                    ->get()
                    ->keyBy('period');

                $chartData = collect(range(1, 12))->map(function ($m) use ($rawBookings) {
                    $item = $rawBookings->get($m);
                    return [
                        'label'          => "T{$m}",
                        'total_bookings' => $item ? (int) $item->total_bookings : 0,
                        'total_revenue'  => $item ? (float) $item->total_revenue : 0,
                    ];
                });

                $totalPeriods = 12;
                $periodLabel  = "Theo tháng - Năm {$year}";
                break;

            /*
             * THEO NĂM: lấy từng năm có dữ liệu
             */
            case 'yearly':
            default:
                $rawBookings = Booking::select(
                    DB::raw('YEAR(created_at) as period'),
                    DB::raw('COUNT(*) as total_bookings'),
                    DB::raw('SUM(total_price) as total_revenue')
                )
                    ->groupBy('period')
                    ->orderBy('period')
                    ->get();

                $chartData = $rawBookings->map(function ($item) {
                    return [
                        'label'          => (string) $item->period,
                        'total_bookings' => (int) $item->total_bookings,
                        'total_revenue'  => (float) $item->total_revenue,
                    ];
                });

                $totalPeriods = $chartData->count();
                $periodLabel  = "Theo năm";
                break;
        }

        /*
        |------------------------------------------------------------------
        | TÍNH TOÁN CÁC CHỈ SỐ TỔNG HỢP
        |------------------------------------------------------------------
        */
        $totalBookings = $chartData->sum('total_bookings');
        $totalRevenue  = $chartData->sum('total_revenue');

        // Mốc có booking cao nhất
        $maxBookingItem = $chartData->sortByDesc('total_bookings')->first();
        // Mốc có doanh thu cao nhất
        $maxRevenueItem = $chartData->sortByDesc('total_revenue')->first();
        // Doanh thu trung bình (chỉ tính các mốc có dữ liệu > 0)
        $activePeriods  = $chartData->where('total_revenue', '>', 0)->count();
        $avgRevenue     = $activePeriods > 0 ? round($totalRevenue / $activePeriods) : 0;

        return response()->json([
            'success' => true,
            'period_label' => $periodLabel,

            /*
             * Thẻ tổng hợp
             */
            'summary' => [
                'total_periods'   => $totalPeriods,
                'total_bookings'  => $totalBookings,
                'total_revenue'   => $totalRevenue,

                'max_booking' => [
                    'value' => $maxBookingItem ? $maxBookingItem['total_bookings'] : 0,
                    'label' => $maxBookingItem ? $maxBookingItem['label'] : '-',
                ],

                'max_revenue' => [
                    'value' => $maxRevenueItem ? $maxRevenueItem['total_revenue'] : 0,
                    'label' => $maxRevenueItem ? $maxRevenueItem['label'] : '-',
                ],

                'avg_revenue'        => $avgRevenue,
                'active_periods'     => $activePeriods,
            ],

            /*
             * Dữ liệu cho biểu đồ
             */
            'chart' => $chartData->values(),
        ]);
    }
}
