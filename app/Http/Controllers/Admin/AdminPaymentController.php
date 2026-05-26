<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminPaymentController extends Controller
{
    public function cancel(Request $request, $id)
    {
        $payment = Payment::findOrFail($id);

        if ($payment->status === 'success') {
            return response()->json([
                'success' => false,
                'message' => 'Không thể hủy giao dịch đã thanh toán thành công.',
            ], 422);
        }

        if ($payment->status === 'failed') {
            return response()->json([
                'success' => false,
                'message' => 'Giao dịch này đã thất bại.',
            ], 422);
        }

        DB::transaction(function () use ($payment) {
            $payment->status = 'failed';
            $payment->save();

            $booking = $payment->booking;
            if ($booking) {
                $booking->payment_status = 'unpaid';
                $booking->status = 'cancelled';
                $booking->save();
            }
        });

        Log::info('Admin cancelled payment', ['payment_id' => $id, 'admin' => $request->user()->id]);

        return response()->json([
            'success' => true,
            'message' => 'Đã hủy giao dịch thành công.',
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $payment = Payment::findOrFail($id);

        if ($payment->status === 'success') {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa giao dịch đã thanh toán thành công.',
            ], 422);
        }

        DB::transaction(function () use ($payment) {
            $booking = $payment->booking;
            $payment->delete();

            if ($booking) {
                $booking->payment_status = 'unpaid';
                $booking->save();
            }
        });

        Log::info('Admin deleted payment', ['payment_id' => $id, 'admin' => $request->user()->id]);

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa giao dịch thành công.',
        ]);
    }
}