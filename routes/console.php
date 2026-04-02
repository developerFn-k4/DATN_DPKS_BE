<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use App\Models\Booking;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Quét mỗi phút để tìm các booking đã hết hạn
Schedule::call(function () {
    // 1. Lấy danh sách booking quá hạn (expired_at < hiện tại) và vẫn đang là 'pending'
    $expiredBookings = Booking::where('status', 'pending')
        ->where('expired_at', '<', now())
        ->with('bookingRooms.room') // Eager load để tối ưu truy vấn
        ->get();

    foreach ($expiredBookings as $booking) {
        DB::transaction(function () use ($booking) {
            // 2. Cập nhật trạng thái Booking thành 'cancelled'
            $booking->update(['status' => 'cancelled']);

            // 3. Trả trạng thái phòng về 'available'
            foreach ($booking->bookingRooms as $bookingRoom) {
                if ($bookingRoom->room) {
                    $bookingRoom->room->update(['status' => 'available']);
                }
            }

            Log::info("Auto-cancelled Booking ID: {$booking->id} and released rooms.");
        });
    }
})->everyMinute(); // Chạy mỗi phút 1 lần
