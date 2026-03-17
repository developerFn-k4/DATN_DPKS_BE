<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportTestCases extends Command
{
    protected $signature = 'export:test-cases';
    protected $description = 'Export API test cases to Excel file';

    public function handle()
    {
        $spreadsheet = new Spreadsheet();
        
        // Add booking tests
        $this->addBookingTests($spreadsheet);
        
        // Add payment tests
        $this->addPaymentTests($spreadsheet);

        // Save file
        $fileName = storage_path('test-cases-' . now()->format('Y-m-d-H-i-s') . '.xlsx');
        $writer = new Xlsx($spreadsheet);
        $writer->save($fileName);

        $this->info("✅ Test cases exported to: $fileName");
    }

    private function addBookingTests($spreadsheet)
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Booking Tests');

        // Headers
        $headers = ['No.', 'Test Case', 'Method', 'Link API', 'Status', 'Message', 'Data Validation', 'Ghi chú'];
        $this->addHeaders($sheet, $headers);

        $testCases = [
            [
                'no' => 1,
                'name' => 'Lấy danh sách phòng trống',
                'method' => 'GET',
                'endpoint' => '/api/available-rooms?check_in=2026-03-18&check_out=2026-03-21',
                'status' => 200,
                'message' => 'check_incheck_out_outguests',
                'validation' => 'data không null, mục id, room_number, room_type_id, status, floor',
            ],
            [
                'no' => 2,
                'name' => 'Thiếu ngày check_in',
                'method' => 'GET',
                'endpoint' => '/api/available-rooms?check_out=2026-03-21',
                'status' => 422,
                'message' => 'Unprocessable Entity "check_in field is required"',
                'validation' => 'Message lỗi trả về',
            ],
            [
                'no' => 3,
                'name' => 'Đặt phòng thành công',
                'method' => 'POST',
                'endpoint' => '/api/bookings',
                'status' => 201,
                'message' => 'Created',
                'validation' => 'Booking được tạo với status=pending, payment_status=unpaid',
            ],
            [
                'no' => 4,
                'name' => 'Thiếu check_in',
                'method' => 'POST',
                'endpoint' => '/api/bookings',
                'status' => 422,
                'message' => 'Unprocessable Entity',
                'validation' => 'Lỗi validation',
            ],
            [
                'no' => 5,
                'name' => 'Check_out <= check_in',
                'method' => 'POST',
                'endpoint' => '/api/bookings',
                'status' => 422,
                'message' => 'Unprocessable Entity',
                'validation' => 'Check ngày hợp lệ',
            ],
            [
                'no' => 6,
                'name' => 'Số khách không hợp lệ',
                'method' => 'POST',
                'endpoint' => '/api/bookings',
                'status' => 422,
                'message' => 'Unprocessable Entity "Sô khách phải hợp lệ"',
                'validation' => 'guests <= room.capacity',
            ],
            [
                'no' => 7,
                'name' => 'Phòng không tồn tại',
                'method' => 'POST',
                'endpoint' => '/api/bookings',
                'status' => 404,
                'message' => 'Room not found',
                'validation' => 'Kiểm tra room_id',
            ],
            [
                'no' => 8,
                'name' => 'Chưa login',
                'method' => 'POST',
                'endpoint' => '/api/bookings',
                'status' => 401,
                'message' => 'Unauthenticated',
                'validation' => 'Require Sanctum token',
            ],
            [
                'no' => 9,
                'name' => 'Lấy danh sách booking của user',
                'method' => 'GET',
                'endpoint' => '/api/my-bookings',
                'status' => 200,
                'message' => 'OK',
                'validation' => 'Trả về list booking của user đó',
            ],
            [
                'no' => 10,
                'name' => 'Hủy booking thành công',
                'method' => 'PUT',
                'endpoint' => '/api/bookings/{id}/cancel',
                'status' => 200,
                'message' => 'OK',
                'validation' => 'Booking status = cancelled',
            ],
            [
                'no' => 11,
                'name' => 'Hủy booking không tồn tại',
                'method' => 'PUT',
                'endpoint' => '/api/bookings/99999/cancel',
                'status' => 404,
                'message' => 'Not Found',
                'validation' => 'Kiểm tra booking_id',
            ],
            [
                'no' => 12,
                'name' => 'Không thể hủy booking của user khác',
                'method' => 'PUT',
                'endpoint' => '/api/bookings/{id}/cancel',
                'status' => 404,
                'message' => 'Not Found',
                'validation' => 'Authorization check',
            ],
            [
                'no' => 13,
                'name' => 'Không thể hủy booking đã hoàn thành',
                'method' => 'PUT',
                'endpoint' => '/api/bookings/{id}/cancel',
                'status' => 422,
                'message' => 'Unprocessable Entity',
                'validation' => 'Check booking status',
            ],
            [
                'no' => 14,
                'name' => 'Không thể book phòng đã booked',
                'method' => 'POST',
                'endpoint' => '/api/bookings',
                'status' => 422,
                'message' => 'Unprocessable Entity "Sô khách phải hợp lệ"',
                'validation' => 'Check room availability',
            ],
        ];

        $row = 2;
        foreach ($testCases as $test) {
            $sheet->setCellValue("A$row", $test['no']);
            $sheet->setCellValue("B$row", $test['name']);
            $sheet->setCellValue("C$row", $test['method']);
            $sheet->setCellValue("D$row", $test['endpoint']);
            $sheet->setCellValue("E$row", $test['status']);
            $sheet->setCellValue("F$row", $test['message']);
            $sheet->setCellValue("G$row", $test['validation']);
            $row++;
        }

        $this->formatSheet($sheet);
    }

    private function addPaymentTests($spreadsheet)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Payment Tests');

        // Headers
        $headers = ['No.', 'Test Case', 'Method', 'Link API', 'Status', 'Message', 'Data Validation', 'Ghi chú'];
        $this->addHeaders($sheet, $headers);

        $testCases = [
            [
                'no' => 1,
                'name' => 'Tạo link thanh toán VNPay',
                'method' => 'POST',
                'endpoint' => '/api/bookings/{id}/payment/vnpay',
                'status' => 200,
                'message' => 'Tao link thanh toan thanh cong',
                'validation' => 'payment_url, txn_ref, booking_id',
            ],
            [
                'no' => 2,
                'name' => 'Tạo link thanh toán không login',
                'method' => 'POST',
                'endpoint' => '/api/bookings/{id}/payment/vnpay',
                'status' => 401,
                'message' => 'Unauthenticated',
                'validation' => 'Require Sanctum token',
            ],
            [
                'no' => 3,
                'name' => 'Tạo link cho booking không tồn tại',
                'method' => 'POST',
                'endpoint' => '/api/bookings/99999/payment/vnpay',
                'status' => 404,
                'message' => 'Booking khong ton tai',
                'validation' => 'Check booking_id',
            ],
            [
                'no' => 4,
                'name' => 'Tạo link cho booking of user khác',
                'method' => 'POST',
                'endpoint' => '/api/bookings/{id}/payment/vnpay',
                'status' => 404,
                'message' => 'Booking khong ton tai',
                'validation' => 'Authorization check',
            ],
            [
                'no' => 5,
                'name' => 'Không thể tạo nếu đã thanh toán',
                'method' => 'POST',
                'endpoint' => '/api/bookings/{id}/payment/vnpay',
                'status' => 422,
                'message' => 'Booking da duoc thanh toan',
                'validation' => 'Check payment_status',
            ],
            [
                'no' => 6,
                'name' => 'Không thể tạo nếu booking cancelled',
                'method' => 'POST',
                'endpoint' => '/api/bookings/{id}/payment/vnpay',
                'status' => 422,
                'message' => 'Booking hien tai khong the thanh toan',
                'validation' => 'Check status',
            ],
            [
                'no' => 7,
                'name' => 'VNPay callback - chữ ký không hợp lệ',
                'method' => 'GET',
                'endpoint' => '/api/bookings/payment/vnpay-return?vnp_SecureHash=invalid',
                'status' => 302,
                'message' => 'Redirect to failure page',
                'validation' => 'Verify signature',
            ],
            [
                'no' => 8,
                'name' => 'VNPay callback - thanh toán thành công',
                'method' => 'GET',
                'endpoint' => '/api/bookings/payment/vnpay-return?vnp_ResponseCode=00&...',
                'status' => 302,
                'message' => 'Redirect to success page',
                'validation' => 'payment_status=paid, status=confirmed',
            ],
            [
                'no' => 9,
                'name' => 'VNPay callback - amount mismatch',
                'method' => 'GET',
                'endpoint' => '/api/bookings/payment/vnpay-return?vnp_Amount=wrong',
                'status' => 302,
                'message' => 'Redirect to failure page',
                'validation' => 'payment_status=failed',
            ],
            [
                'no' => 10,
                'name' => 'VNPay callback - gateway rejection',
                'method' => 'GET',
                'endpoint' => '/api/bookings/payment/vnpay-return?vnp_ResponseCode=01',
                'status' => 302,
                'message' => 'Redirect to failure page',
                'validation' => 'payment_status=failed',
            ],
            [
                'no' => 11,
                'name' => 'VNPay IPN callback - thành công',
                'method' => 'POST/GET',
                'endpoint' => '/api/bookings/payment/vnpay-ipn',
                'status' => 200,
                'message' => '{"RspCode":"00","Message":"Confirm success"}',
                'validation' => 'payment_status=paid, send mail',
            ],
            [
                'no' => 12,
                'name' => 'VNPay IPN - idempotent check',
                'method' => 'POST/GET',
                'endpoint' => '/api/bookings/payment/vnpay-ipn',
                'status' => 200,
                'message' => '{"RspCode":"02","Message":"Order already confirmed"}',
                'validation' => 'Prevent double-charging',
            ],
            [
                'no' => 13,
                'name' => 'Mock payment mode - tạo mock URL',
                'method' => 'POST',
                'endpoint' => '/api/bookings/{id}/payment/vnpay (PAYMENT_MODE=mock)',
                'status' => 200,
                'message' => 'payment_url contains mock-return',
                'validation' => 'Check mock URL',
            ],
            [
                'no' => 14,
                'name' => 'Mock payment return - auto mark paid',
                'method' => 'GET',
                'endpoint' => '/api/bookings/{id}/payment/mock-return',
                'status' => 302,
                'message' => 'Redirect to success page',
                'validation' => 'payment_status=paid, send mail',
            ],
        ];

        $row = 2;
        foreach ($testCases as $test) {
            $sheet->setCellValue("A$row", $test['no']);
            $sheet->setCellValue("B$row", $test['name']);
            $sheet->setCellValue("C$row", $test['method']);
            $sheet->setCellValue("D$row", $test['endpoint']);
            $sheet->setCellValue("E$row", $test['status']);
            $sheet->setCellValue("F$row", $test['message']);
            $sheet->setCellValue("G$row", $test['validation']);
            $row++;
        }

        $this->formatSheet($sheet);
    }

    private function addHeaders($sheet, $headers)
    {
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }

        // Style header row
        $headerStyle = [
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];

        $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);
    }

    private function formatSheet($sheet)
    {
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(35);
        $sheet->getColumnDimension('C')->setWidth(10);
        $sheet->getColumnDimension('D')->setWidth(45);
        $sheet->getColumnDimension('E')->setWidth(8);
        $sheet->getColumnDimension('F')->setWidth(35);
        $sheet->getColumnDimension('G')->setWidth(35);
        $sheet->getColumnDimension('H')->setWidth(20);

        // Center align status column
        for ($i = 2; $i <= 101; $i++) {
            $sheet->getStyle('E' . $i)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $sheet->freezePane('A2');
    }
}
