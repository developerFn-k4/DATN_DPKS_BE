<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{

    /**
     * TẠO LINK THANH TOÁN VNPAY
     */
    public function createVnpay(Request $request, $bookingId)
    {
        $booking = Booking::findOrFail($bookingId);

        if ($booking->status === 'confirmed') {
            return response()->json([
                'message' => 'Booking already paid'
            ], 400);
        }

        $vnp_TmnCode = config('vnpay.tmn_code');
        $vnp_HashSecret = config('vnpay.hash_secret');
        $vnp_Url = config('vnpay.url');
        $vnp_Returnurl = config('vnpay.return_url');

        $vnp_TxnRef = 'PAY' . time() . rand(1000, 9999);

        $vnp_OrderInfo = "Thanh toan booking #" . $booking->id;
        $vnp_OrderType = "billpayment";
        $vnp_Amount = $booking->total_price * 100;
        $vnp_Locale = "vn";
        $vnp_IpAddr = $request->ip();

        /**
         * LƯU PAYMENT
         */
        Payment::create([
            'booking_id' => $booking->id,
            'order_id' => $vnp_TxnRef,
            'request_id' => uniqid(),
            'amount' => $booking->total_price,
            'method' => 'vnpay',
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

    /**
     * CALLBACK SAU KHI THANH TOÁN
     */
    public function vnpayReturn(Request $request)
    {

        $vnp_HashSecret = config('vnpay.hash_secret');

        $inputData = $request->all();
        $vnp_SecureHash = $inputData['vnp_SecureHash'] ?? null;

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

        /**
         * VERIFY SIGNATURE
         */
        if (!$vnp_SecureHash || !hash_equals($secureHash, $vnp_SecureHash)) {

            Log::error("VNPay Invalid Signature", [
                "data" => $request->all()
            ]);

            return response()->json([
                "message" => "Invalid signature"
            ], 400);
        }

        $orderId = $request->vnp_TxnRef;
        $responseCode = $request->vnp_ResponseCode;
        $amount = $request->vnp_Amount / 100;

        $payment = Payment::where('order_id', $orderId)
            ->orWhereRaw("REPLACE(order_id, '-', '') = ?", [$orderId])
            ->first();

        if (!$payment) {
            return response()->json([
                "message" => "Payment not found"
            ], 404);
        }

        /**
         * CHỐNG DOUBLE PAYMENT
         */
        if ($payment->status == 'success') {
            return response()->json([
                "message" => "Payment already confirmed"
            ]);
        }

        /**
         * VERIFY AMOUNT
         */
        if ($payment->amount != $amount) {
            return response()->json([
                "message" => "Invalid amount"
            ], 400);
        }

        if ($responseCode == '00') {

            $payment->status = 'success';
            $payment->save();

            $booking = $payment->booking;

            $booking->status = 'confirmed';
            $booking->save();

            if ($booking->room) {
                $booking->room->status = 'booked';
                $booking->room->save();
            }

            return response()->json([
                "message" => "Thanh toán thành công"
            ]);
        }

        $payment->status = 'failed';
        $payment->save();

        return response()->json([
            "message" => "Thanh toán thất bại"
        ]);
    }


    /**
     * LỊCH SỬ THANH TOÁN
     */
    public function history(Request $request)
    {

        $payments = Payment::with(['booking.room.roomType'])
            ->whereHas('booking', function ($q) use ($request) {
                $q->where('user_id', $request->user()->id);
            })
            ->latest()
            ->get();

        return response()->json([
            "success" => true,
            "data" => $payments
        ]);
    }
}
