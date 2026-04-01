<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Booking;
use Illuminate\Support\Facades\DB;

class CancelExpiredBookings extends Command
{
    protected $signature = 'booking:cancel-expired';

    protected $description = 'Cancel expired pending bookings';

    public function handle()
    {
        DB::transaction(function () {

            $bookings = Booking::where('status', 'pending')
                ->whereNotNull('expired_at')
                ->where('expired_at', '<=', now())
                ->lockForUpdate()
                ->get();

            foreach ($bookings as $booking) {

                $booking->update([
                    'status' => 'cancelled'
                ]);
            }
        });

        $this->info('Expired bookings cancelled successfully.');
    }
}
