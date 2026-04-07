<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Booking;
use App\Models\BookingRoom;
use App\Models\Service;
use App\Models\BookingService;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class UserBookingController extends Controller
{
    /*
    |-----------------------------------------------------------
    | CREATE BOOKING
    |-----------------------------------------------------------
    */

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'required',
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
            'rooms' => 'required|array',
            'rooms.*.room_type_id' => 'required|exists:room_types,id',
            'rooms.*.quantity' => 'required|integer|min:1'
        ]);

        DB::beginTransaction();

        try {

            $checkIn = Carbon::parse($request->check_in);
            $checkOut = Carbon::parse($request->check_out);
            $nights = $checkIn->diffInDays($checkOut);

            /*
        CREATE BOOKING
        */

            $booking = Booking::create([
                'booking_code' => 'BK-' . strtoupper(Str::random(6)),
                'user_id' => Auth::id(),
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'nights' => $nights,
                'guests' => $request->guests ?? 1,
                'special_request' => $request->special_request,
                'status' => 'pending',
                'source' => 'website',
                'payment_status' => 'unpaid',
                'expired_at' => now()->addMinutes(15)
            ]);

            $subTotal = 0;

            /*
        BOOKING ROOMS
        */

            foreach ($request->rooms as $roomData) {

                $roomType = RoomType::findOrFail($roomData['room_type_id']);
                $quantity = $roomData['quantity'];

                $rooms = Room::where('room_type_id', $roomType->id)
                    ->where('status', 'available')
                    ->lockForUpdate()
                    ->take($quantity)
                    ->get();

                if ($rooms->count() < $quantity) {
                    throw new \Exception("Not enough rooms available");
                }

                foreach ($rooms as $room) {

                    $price = $roomType->base_price * $nights;

                    BookingRoom::create([
                        'booking_id' => $booking->id,
                        'room_id' => $room->id,
                        'room_type_id' => $roomType->id,
                        'quantity' => 1,
                        'price' => $price
                    ]);

                    $subTotal += $price;

                    $room->update([
                        'status' => 'occupied'
                    ]);
                }
            }

            /*
        CALCULATE TOTAL
        */

            $tax = $subTotal * 0.05;
            $total = $subTotal + $tax;

            $booking->update([
                'total_price' => $total
            ]);

            /*
        CREATE PAYMENT
        */

            $payment = Payment::create([
                'booking_id' => $booking->id,
                'order_id' => 'PAY-' . strtoupper(Str::random(10)),
                'request_id' => 'REQ-' . strtoupper(Str::random(10)),
                'amount' => $total,
                'method' => 'vnpay',
                'status' => 'pending'
            ]);

            /*
        CREATE PAYMENT URL
        */

            $paymentUrl = secure_url('/api/vnpay/pay/' . $payment->order_id);

            DB::commit();

            return response()->json([
                'message' => 'Booking created successfully',
                'booking_id' => $booking->id,
                'order_id' => $payment->order_id,
                'amount' => $total,
                'payment_url' => $paymentUrl
            ]);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /*
|---------------------------------------------------------------------------
| VNPay REDIRECT
|---------------------------------------------------------------------------
*/
    public function vnpayPay($orderId)
    {
        $payment = Payment::where('order_id', $orderId)->firstOrFail();

        // Lấy thông tin từ cấu hình
        $vnp_TmnCode = config('vnpay.tmn_code');
        $vnp_HashSecret = config('vnpay.hash_secret');
        $vnp_Url = config('vnpay.url');
        $vnp_Returnurl = config('vnpay.return_url');

        // Chuẩn hóa tham số
        $vnp_TxnRef = preg_replace('/[^A-Z0-9]/i', '', $payment->order_id);
        $vnp_Amount = (int) ($payment->amount * 100);
        // Quan trọng: Loại bỏ ký tự đặc biệt trong OrderInfo để tránh lỗi encoding
        $vnp_OrderInfo = 'Booking ' . preg_replace('/[^A-Z0-9]/i', '', $payment->booking->booking_code);
        $vnp_CreateDate = now()->format('YmdHis');

        $inputData = [
            "vnp_Version"    => "2.1.0",
            "vnp_TmnCode"    => $vnp_TmnCode,
            "vnp_Amount"     => $vnp_Amount,
            "vnp_Command"    => "pay",
            "vnp_CreateDate" => $vnp_CreateDate,
            "vnp_CurrCode"   => "VND",
            "vnp_IpAddr"     => request()->ip(),
            "vnp_Locale"     => "vn",
            "vnp_OrderInfo"  => $vnp_OrderInfo,
            "vnp_OrderType"  => "other", // Thêm tham số bắt buộc theo chuẩn 2.1.0
            "vnp_ReturnUrl"  => $vnp_Returnurl,
            "vnp_TxnRef"     => $vnp_TxnRef,
        ];

        // 1. Sắp xếp key theo bảng chữ cái ASCII
        ksort($inputData);

        // 2. Tạo chuỗi Hash và Query đồng nhất
        $query = "";
        $i = 0;
        $hashData = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashData .= '&' . urlencode($key) . "=" . urlencode($value);
                $query .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashData .= urlencode($key) . "=" . urlencode($value);
                $query .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
        }

        // 3. Tạo SecureHash (Sử dụng chuỗi đã được urlencode)
        $vnpSecureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

        // 4. Xây dựng URL cuối cùng
        $finalUrl = $vnp_Url . "?" . $query . '&vnp_SecureHash=' . $vnpSecureHash;

        return redirect($finalUrl);
    }
    /*
|---------------------------------------------------------------------------
| VNPay WEBHOOK
|---------------------------------------------------------------------------
*/
    public function vnpayWebhook(Request $request)
    {
        $inputData = $request->all();

        $vnp_SecureHash = $inputData['vnp_SecureHash'];
        unset($inputData['vnp_SecureHash'], $inputData['vnp_SecureHashType']);

        ksort($inputData);

        $hashData = "";
        foreach ($inputData as $key => $value) {
            $hashData .= $key . "=" . $value . "&";
        }

        $hashData = rtrim($hashData, '&');

        $secureHash = hash_hmac('sha512', $hashData, config('vnpay.hash_secret'));

        if ($secureHash !== $vnp_SecureHash) {
            return response()->json([
                'message' => 'Invalid signature'
            ]);
        }

        $txnRef = $inputData['vnp_TxnRef'];
        $payment = Payment::where('order_id', $txnRef)
            ->orWhereRaw("REPLACE(order_id, '-', '') = ?", [$txnRef])
            ->first();

        if (!$payment) {
            return response()->json([
                'message' => 'Payment not found'
            ]);
        }

        $booking = $payment->booking;

        if ($inputData['vnp_ResponseCode'] == '00') {

            DB::transaction(function () use ($payment, $booking) {

                $payment->update([
                    'status' => 'success'
                ]);

                $booking->update([
                    'status' => 'confirmed'
                ]);

                foreach ($booking->bookingRooms as $bookingRoom) {

                    $room = Room::find($bookingRoom->room_id);

                    if ($room) {
                        $room->update([
                            'status' => 'booked'
                        ]);
                    }
                }
            });

            return redirect('/payment-success');
        }

        /*
    PAYMENT FAIL
    */

        DB::transaction(function () use ($payment, $booking) {

            $payment->update([
                'status' => 'failed'
            ]);

            $booking->update([
                'status' => 'cancelled'
            ]);

            foreach ($booking->bookingRooms as $bookingRoom) {

                $room = Room::find($bookingRoom->room_id);

                if ($room) {
                    $room->update([
                        'status' => 'available'
                    ]);
                }
            }
        });

        return redirect('/payment-failed');
    }

    /*
    |--------------------------------------------------------------------------
    | CRON AUTO CANCEL
    |--------------------------------------------------------------------------
    */
    public function createBooking(Request $request)
    {
        $booking = Booking::create([
            'user_id' => $request->user_id,
            'room_id' => $request->room_id,
            'status' => 'pending',
            'expired_at' => Carbon::now()->addMinutes(2) // giữ phòng 2 phút
        ]);

        // phòng sẽ giữ trạng thái 'occupied' khi tạo booking
        $room = Room::find($request->room_id);
        if ($room) {
            $room->update(['status' => 'occupied']);
        }

        return response()->json([
            'message' => 'Booking created. Please pay within 2 minutes.',
            'booking' => $booking
        ]);
    }

    /*
    |-----------------------------------------------------------
    | CANCEL BOOKING
    |-----------------------------------------------------------
    */

    public function cancel($id)
    {
        $booking = Booking::findOrFail($id);

        if ($booking->status == 'confirmed') {

            return response()->json([
                'message' => 'Cannot cancel confirmed booking'
            ], 400);
        }

        $booking->update([
            'status' => 'cancelled'
        ]);

        return response()->json([
            'message' => 'Booking cancelled'
        ]);
    }

    public function myBookings()
    {
        $bookings = Booking::where('user_id', Auth::id())
            ->with(['room', 'room.roomType'])
            ->latest()
            ->paginate(10);

        return response()->json($bookings);
    }

    /**
     * =====================================================
     * BOOKINGS CHƯA THANH TOÁN CỦA USER
     * =====================================================
     */
    public function unpaidBookings(Request $request)
    {
        $userId = $request->user()->id;

        // DEBUG: xem tất cả bookings của user này (xoá sau khi xác định vấn đề)
        $allBookings = Booking::where('user_id', $userId)->get(['id', 'status', 'deleted_at']);

        $bookings = Booking::where('user_id', $userId)
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->with(['room', 'room.roomType'])
            ->latest()
            ->get();

        return response()->json([
            'success'      => true,
            'data'         => $bookings,
            '_debug'       => [
                'logged_in_user_id' => $userId,
                'all_bookings'      => $allBookings,
            ],
        ]);
    }
}
