<h2>Thêm loại phòng</h2>

<form method="POST" action="{{ route('room_types.store') }}">
@csrf

Hotel ID: <input name="hotel_id"><br>
Tên: <input name="name"><br>
Mô tả: <textarea name="description"></textarea><br>
Sức chứa: <input name="capacity"><br>
Loại giường: <input name="bed_type"><br>
Giá: <input name="base_price"><br>

<button>Lưu</button>
</form>
