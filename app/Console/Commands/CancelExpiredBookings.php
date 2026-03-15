<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Booking;
use Carbon\Carbon;

class CancelExpiredBookings extends Command
{
    protected $signature = 'booking:cancel-expired';

    protected $description = 'Cancel bookings not paid after 5 minutes';

    public function handle()
    {

        $expiredBookings = Booking::where('status', 'pending')
            ->where('created_at', '<=', Carbon::now()->subMinutes(5))
            ->get();

        foreach ($expiredBookings as $booking) {

            $booking->status = 'cancelled';
            $booking->save();
        }

        $this->info('Expired bookings cancelled.');
    }
}
