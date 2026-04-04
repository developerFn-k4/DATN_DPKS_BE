<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Booking;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CancelExpiredBookings extends Command
{
    protected $signature = 'booking:cancel-expired';
    protected $description = 'Hủy các booking pending đã quá hạn';

    public function handle()
    {
        $now = Carbon::now();

        $bookings = Booking::where('status', 'pending')
            ->where('expired_at', '<', $now)
            ->get();

        foreach ($bookings as $booking) {
            DB::transaction(function () use ($booking) {
                // 1️⃣ Hủy booking
                $booking->update(['status' => 'cancel']);

                // 2️⃣ Trả phòng về available
                foreach ($booking->bookingRooms as $br) {
                    $br->room->update(['status' => 'available']);
                }
            });

            $this->info("Booking #{$booking->id} đã bị hủy.");
        }
    }
}
