<?php

namespace App\Jobs;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class CancelBookingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $bookingId;

    public function __construct($bookingId)
    {
        $this->bookingId = $bookingId;
    }

    public function handle()
    {
        $booking = Booking::find($this->bookingId);

        // Chỉ hủy nếu trạng thái vẫn là 'pending'
        if ($booking && $booking->status === 'pending') {
            DB::transaction(function () use ($booking) {
                // 1. Hủy đơn
                $booking->update(['status' => 'cancelled']);

                // 2. Trả lại trạng thái phòng trống
                foreach ($booking->bookingRooms as $bookingRoom) {
                    if ($bookingRoom->room) {
                        $bookingRoom->room->update(['status' => 'available']);
                    }
                }

                // 3. Hủy payment luôn
                $booking->payments()->where('status', 'pending')->update(['status' => 'failed']);
            });
        }
    }
}
