
<h2>Quản lý loại phòng</h2>

<a href="{{ route('room_types.create') }}">+ Thêm loại phòng</a>

<table border="1">
<tr>
    <th>ID</th>
    <th>Tên</th>
    <th>Sức chứa</th>
    <th>Giá</th>
    <th>Action</th>
</tr>

@foreach($roomTypes as $room)
<tr>
    <td>{{ $room->id }}</td>
    <td>{{ $room->name }}</td>
    <td>{{ $room->capacity }}</td>
    <td>{{ $room->base_price }}</td>

    <td>
        <a href="{{ route('room_types.edit',$room->id) }}">Sửa</a>

        <form action="{{ route('room_types.destroy',$room->id) }}" method="POST">
            @csrf
            @method('DELETE')
            <button>Xóa</button>
        </form>
    </td>
</tr>
@endforeach
</table>
>