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

        DB::beginTransaction();

        try {

            $bookings = Booking::with('room')
                ->where('status', 'pending')
                ->whereNotNull('expired_at')
                ->where('expired_at', '<=', now())
                ->lockForUpdate()
                ->get();

            foreach ($bookings as $booking) {

                $booking->status = 'cancelled';
                $booking->save();

                if ($booking->room) {

                    $booking->room->status = 'available';
                    $booking->room->save();
                }
            }

            DB::commit();

            $this->info('Expired bookings cancelled');
        } catch (\Exception $e) {

            DB::rollBack();

            $this->error($e->getMessage());
        }
    }
}
