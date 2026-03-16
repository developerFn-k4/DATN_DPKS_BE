<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AdminBookingController extends Controller
{

    /**
     * Lấy danh sách phòng trống
     */
    public function availableRooms(Request $request)
    {
        $data = $request->validate([
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in'
        ]);

        $rooms = Room::whereDoesntHave('bookings', function ($query) use ($data) {

            // ✅ SỬA: không tính pending đã hết hạn
            $query->where(function ($q) {

                $q->where('status', 'confirmed')
                    ->orWhere(function ($sub) {

                        $sub->where('status', 'pending')
                            ->where('expired_at', '>', now());
                    });
            })
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
     * USER ĐẶT PHÒNG
     * =====================================================
     */
    public function store(Request $request)
    {

        if (!Auth::check()) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

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

            $room = Room::with('roomType')
                ->lockForUpdate()
                ->find($data['room_id']);

            if (!$room) {
                return response()->json([
                    'message' => 'Phòng không tồn tại'
                ], 404);
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

            $pricePerDay = $room->roomType->base_price;

            $totalPrice = $days * $pricePerDay;

            /**
             * CREATE BOOKING
             */
            $booking = Booking::create([
                'room_id' => $data['room_id'],
                'user_id' => Auth::id(),
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'check_in' => $data['check_in'],
                'check_out' => $data['check_out'],
                'guests' => $data['guests'],
                'total_price' => $totalPrice,
                'status' => 'pending',
                'expired_at' => now()->addMinutes(5)
            ]);

            /**
             * UPDATE ROOM
             */
            $room->status = 'reserved';
            $room->save();

            DB::commit();

            return response()->json([
                'message' => 'Đặt phòng thành công',
                'data' => $booking,
                'room_info' => [
                    'room_id' => $room->id,
                    'room_number' => $room->room_number
                ],
                'price_per_day' => $pricePerDay,
                'days' => $days,
                'total_price' => $totalPrice
            ], 201);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'message' => 'Có lỗi xảy ra'
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

        $booking = Booking::with('room')->find($id);

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

        DB::beginTransaction();

        try {

            $booking->status = 'confirmed';
            $booking->expired_at = null;
            $booking->save();

            $booking->room->status = 'booked';
            $booking->room->save();

            DB::commit();

            return response()->json([
                'message' => 'Booking đã được xác nhận',
                'data' => $booking
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

            $booking->room->status = 'occupied';
            $booking->room->save();

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

        if ($booking->status !== 'confirmed') {
            return response()->json([
                'message' => 'Booking chưa được xác nhận'
            ], 422);
        }

        DB::beginTransaction();

        try {

            $booking->status = 'completed';
            $booking->save();

            $booking->room->status = 'available';
            $booking->room->save();

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

        $booking = Booking::with('room')->find($id);

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

        DB::beginTransaction();

        try {

            $booking->status = 'cancelled';
            $booking->save();

            $booking->room->status = 'available';
            $booking->room->save();

            DB::commit();

            return response()->json([
                'message' => 'Đã hủy booking'
            ]);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'message' => 'Có lỗi xảy ra'
            ], 500);
        }
    }


    /**
     * Booking của user
     */
    public function myBookings()
    {
        $bookings = Booking::where('user_id', Auth::id())
            ->with(['room', 'room.roomType'])
            ->latest()
            ->paginate(10);

        return response()->json($bookings);
    }



    /**
     * Admin xem tất cả booking
     */
    public function index(Request $request)
    {
        $query = Booking::with(['room', 'room.roomType', 'user'])
            ->latest();

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->date) {
            $query->whereDate('check_in', $request->date);
        }

        $bookings = $query->paginate(15);

        return response()->json($bookings);
    }
}
