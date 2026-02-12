<h2>Sửa loại phòng</h2>

<form method="POST" action="{{ route('room_types.update',$roomType->id) }}">
@csrf
@method('PUT')

Hotel ID:
<input name="hotel_id" value="{{ $roomType->hotel_id }}"><br>

Tên:
<input name="name" value="{{ $roomType->name }}"><br>

Mô tả:
<textarea name="description">{{ $roomType->description }}</textarea><br>

Sức chứa:
<input name="capacity" value="{{ $roomType->capacity }}"><br>

Loại giường:
<input name="bed_type" value="{{ $roomType->bed_type }}"><br>

Giá:
<input name="base_price" value="{{ $roomType->base_price }}"><br>

<button>Cập nhật</button>
</form>
