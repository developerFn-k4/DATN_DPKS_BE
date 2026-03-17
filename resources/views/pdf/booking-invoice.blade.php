<!doctype html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Invoice Booking #{{ $booking->id }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #111827; font-size: 13px; }
        .header { margin-bottom: 16px; }
        .title { font-size: 22px; font-weight: bold; }
        .muted { color: #6b7280; }
        .card { border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px; margin-top: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #e5e7eb; padding: 8px; text-align: left; }
        th { background: #f3f4f6; }
        .total { text-align: right; font-size: 16px; font-weight: bold; margin-top: 14px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">VietStay - Hoa don thanh toan</div>
        <div class="muted">Ma booking: #{{ $booking->id }}</div>
        <div class="muted">Ngay thanh toan: {{ optional($booking->paid_at)->format('d/m/Y H:i') }}</div>
    </div>

    <div class="card">
        <div><strong>Khach hang:</strong> {{ $booking->user->name }}</div>
        <div><strong>Email:</strong> {{ $booking->user->email }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Phong</th>
                <th>Loai phong</th>
                <th>Check-in</th>
                <th>Check-out</th>
                <th>So khach</th>
                <th>Gia tri</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $booking->room->room_number ?? 'N/A' }}</td>
                <td>{{ $booking->room->roomType->name ?? 'N/A' }}</td>
                <td>{{ optional($booking->check_in)->format('d/m/Y') }}</td>
                <td>{{ optional($booking->check_out)->format('d/m/Y') }}</td>
                <td>{{ $booking->guests }}</td>
                <td>{{ number_format((float) $booking->total_price, 0, ',', '.') }} VND</td>
            </tr>
        </tbody>
    </table>

    <div class="total">Tong thanh toan: {{ number_format((float) $booking->total_price, 0, ',', '.') }} VND</div>
</body>
</html>
