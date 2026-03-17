<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\BookingPaidMail;
use App\Models\Booking;
use App\Services\VnpayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BookingPaymentController extends Controller
{
    public function __construct(private readonly VnpayService $vnpayService)
    {
    }

    public function createVnpayPayment(Request $request, int $bookingId)
    {
        $booking = Booking::where('id', $bookingId)
            ->where('user_id', Auth::id())
            ->first();

        if (! $booking) {
            return response()->json(['message' => 'Booking khong ton tai'], 404);
        }

        if (in_array($booking->status, ['cancelled', 'completed'], true)) {
            return response()->json(['message' => 'Booking hien tai khong the thanh toan'], 422);
        }

        if ($booking->payment_status === 'paid') {
            return response()->json(['message' => 'Booking da duoc thanh toan'], 422);
        }

        $paymentMode = config('app.payment_mode', 'vnpay');

        if ($paymentMode === 'mock') {
            $paymentUrl = $this->createMockPaymentUrl($booking);
        } else {
            try {
                $paymentUrl = $this->vnpayService->createPaymentUrl($booking, (string) $request->ip());
            } catch (\RuntimeException $e) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], 422);
            }
        }

        return response()->json([
            'message' => 'Tao link thanh toan thanh cong',
            'data' => [
                'booking_id' => $booking->id,
                'payment_url' => $paymentUrl,
                'txn_ref' => $booking->fresh()->payment_txn_ref,
            ],
        ]);
    }

    public function vnpayReturn(Request $request)
    {
        $params = $request->query();

        if (! $this->vnpayService->verifySignature($params)) {
            return $this->redirectFrontend('failed', [
                'reason' => 'invalid_signature',
            ]);
        }

        $txnRef = (string) ($params['vnp_TxnRef'] ?? '');
        $amount = (int) ($params['vnp_Amount'] ?? 0);

        $booking = Booking::where('payment_txn_ref', $txnRef)->with(['user', 'room.roomType'])->first();

        if (! $booking) {
            return $this->redirectFrontend('failed', [
                'reason' => 'booking_not_found',
            ]);
        }

        if ($amount !== ((int) round(((float) $booking->total_price) * 100))) {
            $this->markBookingFailed($booking, $params, 'amount_mismatch');

            return $this->redirectFrontend('failed', [
                'booking_id' => $booking->id,
                'reason' => 'amount_mismatch',
            ]);
        }

        $isSuccess = ($params['vnp_ResponseCode'] ?? '') === '00' && ($params['vnp_TransactionStatus'] ?? '') === '00';

        if (! $isSuccess) {
            $this->markBookingFailed($booking, $params, 'gateway_rejected');

            return $this->redirectFrontend('failed', [
                'booking_id' => $booking->id,
                'reason' => (string) ($params['vnp_ResponseCode'] ?? 'unknown'),
            ]);
        }

        $this->markBookingPaid($booking, $params);

        return $this->redirectFrontend('success', [
            'booking_id' => $booking->id,
        ]);
    }

    public function vnpayIpn(Request $request)
    {
        $params = $request->all();

        if (! $this->vnpayService->verifySignature($params)) {
            return response()->json(['RspCode' => '97', 'Message' => 'Invalid signature']);
        }

        $booking = Booking::where('payment_txn_ref', $params['vnp_TxnRef'] ?? null)->with(['user', 'room.roomType'])->first();

        if (! $booking) {
            return response()->json(['RspCode' => '01', 'Message' => 'Order not found']);
        }

        $amount = (int) ($params['vnp_Amount'] ?? 0);
        if ($amount !== ((int) round(((float) $booking->total_price) * 100))) {
            $this->markBookingFailed($booking, $params, 'amount_mismatch');
            return response()->json(['RspCode' => '04', 'Message' => 'Invalid amount']);
        }

        if ($booking->payment_status === 'paid') {
            return response()->json(['RspCode' => '02', 'Message' => 'Order already confirmed']);
        }

        $isSuccess = ($params['vnp_ResponseCode'] ?? '') === '00' && ($params['vnp_TransactionStatus'] ?? '') === '00';

        if ($isSuccess) {
            $this->markBookingPaid($booking, $params);
            return response()->json(['RspCode' => '00', 'Message' => 'Confirm success']);
        }

        $this->markBookingFailed($booking, $params, 'gateway_rejected');

        return response()->json(['RspCode' => '00', 'Message' => 'Confirm success']);
    }

    public function mockPaymentReturn(Request $request, int $bookingId)
    {
        $booking = Booking::where('id', $bookingId)
            ->with(['user', 'room.roomType'])
            ->first();

        if (! $booking) {
            return $this->redirectFrontend('failed', [
                'reason' => 'booking_not_found',
            ]);
        }

        // Mock: simulate successful payment
        $mockParams = [
            'vnp_TransactionNo' => 'MOCK_' . uniqid(),
            'vnp_ResponseCode' => '00',
        ];

        $this->markBookingPaid($booking, $mockParams);

        return $this->redirectFrontend('success', [
            'booking_id' => $booking->id,
        ]);
    }

    private function createMockPaymentUrl(Booking $booking): string
    {
        $baseUrl = (string) config('app.url', env('APP_URL', 'http://localhost:8000'));
        return rtrim($baseUrl, '/') . '/api/bookings/' . $booking->id . '/payment/mock-return';
    }

    private function markBookingPaid(Booking $booking, array $params): void
    {
        DB::transaction(function () use ($booking, $params): void {
            $freshBooking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();

            if ($freshBooking->payment_status === 'paid') {
                return;
            }

            $freshBooking->update([
                'payment_method' => 'vnpay',
                'payment_status' => 'paid',
                'status' => 'confirmed',
                'payment_transaction_no' => $params['vnp_TransactionNo'] ?? null,
                'paid_at' => now(),
                'payment_response' => $params,
            ]);

            try {
                $freshBooking->loadMissing(['user', 'room.roomType']);
                Mail::to($freshBooking->user->email)->send(new BookingPaidMail($freshBooking));
            } catch (\Throwable $e) {
                Log::error('Can not send booking payment email', [
                    'booking_id' => $freshBooking->id,
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }

    private function markBookingFailed(Booking $booking, array $params, string $reason): void
    {
        $booking->update([
            'payment_method' => 'vnpay',
            'payment_status' => 'failed',
            'payment_transaction_no' => $params['vnp_TransactionNo'] ?? null,
            'payment_response' => array_merge($params, ['failure_reason' => $reason]),
        ]);
    }

    private function redirectFrontend(string $status, array $params = [])
    {
        $frontendBaseUrl = (string) config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173'));

        $query = http_build_query(array_merge(['status' => $status], $params));

        return redirect()->away(rtrim($frontendBaseUrl, '/') . '/payment/result?' . $query);
    }
}
