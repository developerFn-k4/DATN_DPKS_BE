<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function createVnpay(Request $request, $bookingId)
    {
        $booking = Booking::findOrFail($bookingId);

        $vnp_TmnCode = env('VNPAY_TMN_CODE');
        $vnp_HashSecret = env('VNPAY_HASH_SECRET');
        $vnp_Url = env('VNPAY_URL');
        $vnp_Returnurl = env('VNPAY_RETURN_URL');

        // Mã giao dịch
        $vnp_TxnRef = time();

        $vnp_OrderInfo = "Thanh toan dat phong #" . $booking->id;
        $vnp_OrderType = "billpayment";
        $vnp_Amount = $booking->total_price * 100;
        $vnp_Locale = "vn";
        $vnp_IpAddr = $request->ip();

        /**
         * LƯU PAYMENT TRƯỚC KHI THANH TOÁN
         */
        Payment::create([
            'booking_id' => $booking->id,
            'order_id' => $vnp_TxnRef,
            'request_id' => uniqid(),
            'amount' => $booking->total_price,
            'status' => 'pending'
        ]);

        $inputData = [
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $vnp_TmnCode,
            "vnp_Amount" => $vnp_Amount,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $vnp_IpAddr,
            "vnp_Locale" => $vnp_Locale,
            "vnp_OrderInfo" => $vnp_OrderInfo,
            "vnp_OrderType" => $vnp_OrderType,
            "vnp_ReturnUrl" => $vnp_Returnurl,
            "vnp_TxnRef" => $vnp_TxnRef,
        ];

        ksort($inputData);

        $query = "";
        $hashdata = "";
        $i = 0;

        foreach ($inputData as $key => $value) {

            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }

            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }

        $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);

        $paymentUrl = $vnp_Url . "?" . $query . 'vnp_SecureHash=' . $vnpSecureHash;

        return response()->json([
            "payment_url" => $paymentUrl
        ]);
    }
    public function vnpayReturn(Request $request)
    {
        $vnp_HashSecret = env('VNPAY_HASH_SECRET');

        $inputData = $request->all();
        $vnp_SecureHash = $inputData['vnp_SecureHash'];

        unset($inputData['vnp_SecureHash']);
        unset($inputData['vnp_SecureHashType']);

        ksort($inputData);

        $hashData = "";
        $i = 0;

        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashData .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashData .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
        }

        $secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

        if ($secureHash != $vnp_SecureHash) {
            return response()->json([
                'message' => 'Invalid signature'
            ], 400);
        }

        $vnp_ResponseCode = $request->vnp_ResponseCode;
        $orderId = $request->vnp_TxnRef;

        $payment = Payment::where('order_id', $orderId)->first();

        if (!$payment) {
            return response()->json([
                'message' => 'Payment not found'
            ], 404);
        }

        if ($vnp_ResponseCode == '00') {

            $payment->status = 'success';
            $payment->save();

            $booking = $payment->booking;

            $booking->status = 'confirmed';
            $booking->save();

            $booking->room->status = 'booked';
            $booking->room->save();

            return response()->json([
                'message' => 'Thanh toán thành công'
            ]);
        }

        $payment->status = 'failed';
        $payment->save();

        return response()->json([
            'message' => 'Thanh toán thất bại'
        ]);
    }
}
