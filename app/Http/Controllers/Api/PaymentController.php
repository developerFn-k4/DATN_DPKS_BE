<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function checkout(Request $request, $bookingId)
    {
        $validated = $request->validate([
            'method' => 'required|in:vnpay,momo,cash'
        ]);

        if ($validated['method'] === 'vnpay') {
            return $this->createVnpay($request, $bookingId);
        }

        if ($validated['method'] === 'momo') {
            return $this->createMomo($request, $bookingId);
        }

        return $this->payCash($request, $bookingId);
    }

    /**
     * CREATE VNPAY PAYMENT URL
     */
    public function createVnpay(Request $request, $bookingId)
    {
        $booking = $this->resolveBookingForUser($request, $bookingId);

        if ($booking->payment_status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Booking already paid'
            ], 400);
        }

        $vnp_TmnCode    = config('vnpay.tmn_code');
        $vnp_HashSecret = config('vnpay.hash_secret');
        $vnp_Url        = config('vnpay.url');
        $vnp_Returnurl  = route('api.payment.vnpay-return');

        $vnp_TxnRef    = 'VNP' . now()->format('YmdHis') . rand(1000, 9999);
        $vnp_OrderInfo = 'Thanh_toan_booking_' . $booking->id;
        $vnp_OrderType = 'billpayment';

        // VNPAY amount must x100
        $vnp_Amount = (int) ($booking->total_price * 100);

        $vnp_Locale = 'vn';

        // tránh lỗi ipv6 (::1)
        $vnp_IpAddr = '127.0.0.1';

        /**
         * SAVE PAYMENT
         */
        Payment::create([
            'booking_id' => $booking->id,
            'order_id'   => $vnp_TxnRef,
            'request_id' => (string) Str::uuid(),
            'amount'     => $booking->total_price,
            'method'     => 'vnpay',
            'status'     => 'pending',
        ]);

        /**
         * VNPAY PARAMS
         */
        $inputData = [
            'vnp_Version'    => '2.1.0',
            'vnp_TmnCode'    => $vnp_TmnCode,
            'vnp_Amount'     => $vnp_Amount,
            'vnp_Command'    => 'pay',
            'vnp_CreateDate' => date('YmdHis'),
            'vnp_CurrCode'   => 'VND',
            'vnp_IpAddr'     => $vnp_IpAddr,
            'vnp_Locale'     => $vnp_Locale,
            'vnp_OrderInfo'  => $vnp_OrderInfo,
            'vnp_OrderType'  => $vnp_OrderType,
            'vnp_ReturnUrl'  => $vnp_Returnurl,
            'vnp_TxnRef'     => $vnp_TxnRef,
        ];

        ksort($inputData);

        $query = "";
        $hashData = "";

        foreach ($inputData as $key => $value) {

            if ($hashData != "") {
                $hashData .= '&';
            }

            $hashData .= urlencode($key) . "=" . urlencode($value);

            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }

        $query = rtrim($query, '&');

        /**
         * CREATE SECURE HASH
         */
        $vnpSecureHash = hash_hmac(
            'sha512',
            $hashData,
            $vnp_HashSecret
        );

        /**
         * PAYMENT URL
         */
        $paymentUrl = $vnp_Url
            . "?"
            . $query
            . '&vnp_SecureHash='
            . $vnpSecureHash;

        Log::info('VNPAY HASH DATA', [
            'hashData' => $hashData,
            'hash' => $vnpSecureHash,
            'url' => $paymentUrl,
        ]);

        return response()->json([
            'success' => true,
            'method' => 'vnpay',
            'order_id' => $vnp_TxnRef,
            'payment_url' => $paymentUrl,
        ]);
    }

    /**
     * VNPAY RETURN
     */
    public function vnpayReturn(Request $request)
    {
        $vnp_HashSecret = config('vnpay.hash_secret');

        $inputData = $request->all();

        $vnp_SecureHash = $inputData['vnp_SecureHash'] ?? '';

        unset(
            $inputData['vnp_SecureHash'],
            $inputData['vnp_SecureHashType']
        );

        ksort($inputData);

        $hashData = "";

        foreach ($inputData as $key => $value) {

            if ($hashData != "") {
                $hashData .= '&';
            }

            $hashData .= urlencode($key) . "=" . urlencode($value);
        }

        $secureHash = hash_hmac(
            'sha512',
            $hashData,
            $vnp_HashSecret
        );

        /**
         * VERIFY SIGNATURE
         */
        if (!hash_equals($secureHash, $vnp_SecureHash)) {

            Log::error('VNPay Invalid Signature', [
                'hashData' => $hashData,
                'secureHash' => $secureHash,
                'vnpHash' => $vnp_SecureHash,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid signature'
            ], 400);
        }

        $orderId      = $request->vnp_TxnRef;
        $responseCode = $request->vnp_ResponseCode;
        $amount       = (int) $request->vnp_Amount / 100;

        $payment = Payment::where('order_id', $orderId)->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found'
            ], 404);
        }

        if ((float) $payment->amount !== (float) $amount) {

            Log::error('VNPay Amount Mismatch', [
                'expected' => $payment->amount,
                'received' => $amount,
            ]);

            $payment->status = 'failed';
            $payment->save();

            return response()->json([
                'success' => false,
                'message' => 'Amount mismatch'
            ], 400);
        }

        if ($responseCode === '00') {

            $this->markPaymentSuccess($payment);

            return response()->json([
                'success' => true,
                'message' => 'Payment success'
            ]);
        }

        $payment->status = 'failed';
        $payment->save();

        return response()->json([
            'success' => false,
            'message' => 'Payment failed'
        ]);
    }

    /**
     * CASH PAYMENT
     */
    public function payCash(Request $request, $bookingId)
    {
        $booking = $this->resolveBookingForUser($request, $bookingId);

        if ($booking->payment_status === 'paid') {
            return response()->json([
                'message' => 'Booking already paid'
            ], 400);
        }

        $orderId = 'CASH' . now()->format('YmdHis') . rand(1000, 9999);

        $payment = Payment::create([
            'booking_id' => $booking->id,
            'order_id' => $orderId,
            'request_id' => (string) Str::uuid(),
            'amount' => $booking->total_price,
            'method' => 'cash',
            'status' => 'success',
        ]);

        $this->markPaymentSuccess($payment);

        return response()->json([
            'success' => true,
            'message' => 'Cash payment success',
        ]);
    }

    /**
     * MOMO MOCK
     */
    public function createMomo(Request $request, $bookingId)
    {
        return response()->json([
            'success' => true,
            'message' => 'Momo demo'
        ]);
    }

    /**
     * FIND BOOKING
     */
    private function resolveBookingForUser(Request $request, $bookingId): Booking
    {
        return Booking::where('id', $bookingId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();
    }

    /**
     * PAYMENT SUCCESS
     */
    private function markPaymentSuccess(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {

            if ($payment->status !== 'success') {
                $payment->status = 'success';
                $payment->save();
            }

            $booking = $payment->booking;

            if (!$booking) {
                return;
            }

            $booking->status = 'confirmed';
            $booking->payment_status = 'paid';
            $booking->paid_at = now();

            $booking->save();

            foreach ($booking->bookingRooms as $bookingRoom) {

                if ($bookingRoom->room) {
                    $bookingRoom->room->status = 'booked';
                    $bookingRoom->room->save();
                }
            }
        });
    }
}