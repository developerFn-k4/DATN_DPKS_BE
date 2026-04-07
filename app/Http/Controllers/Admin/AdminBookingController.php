<?php

namespace App\Http\Controllers\Admin;

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
use App\Http\Controllers\Controller;
use App\Jobs\CancelBookingJob;

class AdminBookingController extends Controller
{



    /*
|---------------------------------------------------------------------------
| VNPay REDIRECT
|---------------------------------------------------------------------------
*/
    public function vnpayPay($orderId)
    {
        $payment = Payment::where('order_id', $orderId)->firstOrFail();

        // Lấy thông tin từ env
        $vnp_TmnCode = env('VNPAY_TMN_CODE');
        $vnp_HashSecret = env('VNPAY_HASH_SECRET');
        $vnp_Url = env('VNPAY_URL');
        $vnp_Returnurl = env('VNPAY_RETURN_URL');

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

        $secureHash = hash_hmac('sha512', $hashData, env('VNPAY_HASH_SECRET'));

        if ($secureHash !== $vnp_SecureHash) {
            return response()->json([
                'message' => 'Invalid signature'
            ]);
        }

        $payment = Payment::where('order_id', $inputData['vnp_TxnRef'])->first();

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
    | CANCEL BOOKING
    |--------------------------------------------------------------------------
    */
    public function cancel($id)
    {
        $booking = Booking::findOrFail($id);

        if ($booking->status == 'confirmed') {
            return response()->json(['message' => 'Cannot cancel confirmed booking'], 400);
        }

        $booking->update(['status' => 'cancelled']);

        return response()->json(['message' => 'Booking cancelled']);
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
}
