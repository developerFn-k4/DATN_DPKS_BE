<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class UserBookingController extends Controller
{

    /**
     * =====================================================
     * DANH SÁCH PHÒNG TRỐNG
     * =====================================================
     */
    public function availableRooms(Request $request)
    {
        $data = $request->validate([
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in'
        ]);

        $rooms = Room::whereDoesntHave('bookings', function ($query) use ($data) {

            $query->whereIn('status', ['pending', 'confirmed'])
                ->where(function ($q) use ($data) {
                    $q->where('check_in', '<', $data['check_out'])
                        ->where('check_out', '>', $data['check_in']);
                });
        })->with('roomType')->get();

        return response()->json([
            'data' => $rooms
        ]);
    }

    /**
     * =====================================================
     * USER ĐẶT PHÒNG (GIỮ PHÒNG 5 PHÚT)
     * =====================================================
     */
    public function store(Request $request)
    {

        /**
         * CHECK LOGIN
         */
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        /**
         * VALIDATE
         */
        $data = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'required|string|max:20',
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
            'guests' => 'required|integer|min:1'
        ]);

        DB::beginTransaction();

        try {

            /**
             * LOCK ROOM
             */
            $room = Room::with('roomType')
                ->lockForUpdate()
                ->find($data['room_id']);

            if (!$room) {

                DB::rollBack();

                return response()->json([
                    'message' => 'Phòng không tồn tại'
                ], 404);
            }

            /**
             * CHECK ROOM STATUS
             */
            if ($room->status !== 'available') {

                DB::rollBack();

                return response()->json([
                    'message' => 'Phòng hiện không khả dụng'
                ], 409);
            }

            /**
             * CHECK TRÙNG LỊCH
             */
            $conflict = Booking::where('room_id', $data['room_id'])
                ->whereIn('status', ['pending', 'confirmed'])
                ->where(function ($query) use ($data) {

                    $query->where('check_in', '<', $data['check_out'])
                        ->where('check_out', '>', $data['check_in']);
                })
                ->lockForUpdate()
                ->exists();

            if ($conflict) {

                DB::rollBack();

                return response()->json([
                    'message' => 'Phòng đã được đặt trong khoảng thời gian này'
                ], 409);
            }

            /**
             * TÍNH SỐ NGÀY
             */
            $days = Carbon::parse($data['check_in'])
                ->diffInDays(Carbon::parse($data['check_out']));

            if ($days <= 0) {
                $days = 1;
            }

            /**
             * TÍNH GIÁ
             */
            $pricePerDay = $room->roomType->base_price;

            $totalPrice = $days * $pricePerDay;

            /**
             * CREATE BOOKING
             */
            $booking = Booking::create([
                'room_id' => $room->id,
                'user_id' => Auth::id(),
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'check_in' => $data['check_in'],
                'check_out' => $data['check_out'],
                'guests' => $data['guests'],
                'total_price' => $totalPrice,
                'status' => 'pending',
                'expired_at' => now()->addMinutes(10)
                // 'expired_at' => now()->addSeconds(30)
            ]);

            /**
             * UPDATE ROOM STATUS -> RESERVED (GIỮ PHÒNG)
             */
            $room->update([
                'status' => 'reserved'
            ]);

            DB::commit();

            /**
             * RESPONSE
             */
            return response()->json([
                'message' => 'Đặt phòng thành công. Phòng được giữ trong 5 phút.',
                'booking' => $booking,
                'room' => [
                    'room_id' => $room->id,
                    'room_number' => $room->room_number,
                    'status' => $room->status
                ],
                'price_per_day' => $pricePerDay,
                'days' => $days,
                'total_price' => $totalPrice,
                'expired_at' => $booking->expired_at
            ], 201);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'message' => 'Có lỗi xảy ra',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * =====================================================
     * ADMIN CONFIRM BOOKING
     * =====================================================
     */
    public function confirm($id)
    {

        $booking = Booking::find($id);

        if (!$booking) {
            return response()->json([
                'message' => 'Booking không tồn tại'
            ], 404);
        }

        if ($booking->status !== 'pending') {
            return response()->json([
                'message' => 'Chỉ booking pending mới được xác nhận'
            ], 422);
        }

        $booking->update([
            'status' => 'confirmed',
            'expired_at' => null
        ]);

        return response()->json([
            'message' => 'Booking đã được xác nhận',
            'data' => $booking
        ]);
    }

    /**
     * =====================================================
     * CHECK-IN
     * =====================================================
     */
    public function checkIn($id)
    {

        $booking = Booking::with('room')->find($id);

        if (!$booking) {
            return response()->json([
                'message' => 'Booking không tồn tại'
            ], 404);
        }

        if ($booking->status !== 'confirmed') {
            return response()->json([
                'message' => 'Booking chưa được xác nhận'
            ], 422);
        }

        DB::beginTransaction();

        try {

            $booking->update([
                'status' => 'checked_in'
            ]);

            $booking->room->update([
                'status' => 'occupied'
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Khách đã check-in'
            ]);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'message' => 'Có lỗi xảy ra'
            ], 500);
        }
    }

    /**
     * =====================================================
     * CHECK-OUT
     * =====================================================
     */
    public function complete($id)
    {

        $booking = Booking::with('room')->find($id);

        if (!$booking) {
            return response()->json([
                'message' => 'Booking không tồn tại'
            ], 404);
        }

        if ($booking->status !== 'checked_in') {
            return response()->json([
                'message' => 'Khách chưa check-in'
            ], 422);
        }

        DB::beginTransaction();

        try {

            $booking->update([
                'status' => 'completed'
            ]);

            $booking->room->update([
                'status' => 'available'
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Booking đã hoàn thành'
            ]);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'message' => 'Có lỗi xảy ra'
            ], 500);
        }
    }

    /**
     * =====================================================
     * CANCEL BOOKING
     * =====================================================
     */
    public function cancel($id)
    {

        $booking = Booking::find($id);

        if (!$booking) {
            return response()->json([
                'message' => 'Booking không tồn tại'
            ], 404);
        }

        if (in_array($booking->status, ['cancelled', 'completed'])) {
            return response()->json([
                'message' => 'Booking không thể hủy'
            ], 422);
        }

        $booking->update([
            'status' => 'cancelled'
        ]);

        return response()->json([
            'message' => 'Đã hủy booking'
        ]);
    }

    /**
     * =====================================================
     * BOOKING CỦA USER
     * =====================================================
     */
    public function myBookings()
    {

        $bookings = Booking::where('user_id', Auth::id())
            ->with(['room', 'room.roomType'])
            ->latest()
            ->paginate(10);

        return response()->json($bookings);
    }
}
