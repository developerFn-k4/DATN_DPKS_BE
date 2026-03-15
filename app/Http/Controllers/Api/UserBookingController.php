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
     * Lấy danh sách phòng trống
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
     * Đặt phòng
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
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:100',
            'phone' => 'required|string|max:20',
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
            'guests' => 'required|integer|min:1'
        ], [
            'check_out.after' => 'Ngày trả phòng phải sau ngày nhận phòng',
            'guests.min' => 'Số lượng khách phải lớn hơn 0'
        ]);

        DB::beginTransaction();

        try {

            /**
             * Khóa phòng để tránh race condition
             */
            $room = Room::lockForUpdate()->find($data['room_id']);

            if (!$room) {
                return response()->json([
                    'message' => 'Phòng không tồn tại'
                ], 404);
            }

            /**
             * Kiểm tra trạng thái phòng
             */
            if ($room->status !== 'available') {

                DB::rollBack();

                return response()->json([
                    'message' => 'Phòng hiện không khả dụng'
                ], 422);
            }

            /**
             * Kiểm tra trùng lịch
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
             * Tính số ngày
             */
            $checkIn = Carbon::parse($data['check_in']);
            $checkOut = Carbon::parse($data['check_out']);

            $days = $checkIn->diffInDays($checkOut);

            if ($days <= 0) {
                $days = 1;
            }

            /**
             * Giá phòng
             */
            $price = (float) $room->price;

            /**
             * Tổng tiền
             */
            $totalPrice = $price * $days;

            /**
             * Tạo booking
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
                'status' => 'pending'
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Đặt phòng thành công',

                'data' => $booking,

                'room_info' => [
                    'room_id' => $room->id,
                    'room_number' => $room->room_number
                ],

                'price_per_day' => $price,
                'days' => $days,
                'total_price' => $totalPrice

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
     * Hủy booking
     */
    public function cancel($id)
    {

        $booking = Booking::find($id);

        if (!$booking) {
            return response()->json([
                'message' => 'Booking không tồn tại'
            ], 404);
        }

        if ($booking->status === 'cancelled') {
            return response()->json([
                'message' => 'Booking đã bị hủy'
            ], 422);
        }

        if ($booking->status === 'completed') {
            return response()->json([
                'message' => 'Booking đã hoàn thành'
            ], 422);
        }


        $booking->status = 'cancelled';
        $booking->save();


        return response()->json([
            'message' => 'Đặt phòng đã được hủy',
            'data' => $booking
        ]);
    }



    /**
     * Admin xác nhận booking
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

        $booking->status = 'confirmed';
        $booking->save();

        return response()->json([
            'message' => 'Booking đã được xác nhận',
            'data' => $booking
        ]);
    }



    /**
     * Check-out
     */
    public function complete($id)
    {

        $booking = Booking::find($id);

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

        $booking->status = 'completed';
        $booking->save();

        return response()->json([
            'message' => 'Booking đã hoàn thành',
            'data' => $booking
        ]);
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
}
