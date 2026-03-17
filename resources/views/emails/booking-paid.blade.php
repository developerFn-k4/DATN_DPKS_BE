<!doctype html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Xac nhan thanh toan</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.6;">
    <h2>Xin chao {{ $booking->user->name }},</h2>

    <p>He thong da xac nhan thanh toan VNPay thanh cong cho don dat phong <strong>#{{ $booking->id }}</strong>.</p>

    <ul>
        <li>Phong: {{ $booking->room->room_number ?? 'N/A' }}</li>
        <li>Loai phong: {{ $booking->room->roomType->name ?? 'N/A' }}</li>
        <li>Check-in: {{ optional($booking->check_in)->format('d/m/Y') }}</li>
        <li>Check-out: {{ optional($booking->check_out)->format('d/m/Y') }}</li>
        <li>Tong tien: {{ number_format((float) $booking->total_price, 0, ',', '.') }} VND</li>
    </ul>

    <p>Hoa don PDF da duoc dinh kem trong email nay.</p>

    <p>Cam on ban da su dung dich vu.</p>
</body>
</html>
