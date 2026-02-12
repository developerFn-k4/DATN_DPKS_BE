<?php

namespace App\Http\Controllers;

use App\Models\RoomType;
use Illuminate\Http\Request;

class RoomTypeController extends Controller
{
    public function index()
    {
        $roomTypes = RoomType::latest()->get();
        return view('room_types.index', compact('roomTypes'));
    }

    public function create()
    {
        return view('room_types.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'capacity' => 'required|integer|min:1',
            'bed_type' => 'required|string|max:255',
            'base_price' => 'required|numeric|min:0'
        ]);

        RoomType::create($validated);

        return redirect()
            ->route('room_types.index')
            ->with('success','Thêm loại phòng thành công');
    }

    public function edit($id)
    {
        $roomType = RoomType::findOrFail($id);
        return view('room_types.edit', compact('roomType'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'capacity' => 'required|integer|min:1',
            'bed_type' => 'required|string|max:255',
            'base_price' => 'required|numeric|min:0'
        ]);

        $roomType = RoomType::findOrFail($id);
        $roomType->update($validated);

        return redirect()
            ->route('room_types.index')
            ->with('success','Cập nhật thành công');
    }

    public function destroy($id)
    {
        $roomType = RoomType::findOrFail($id);
        $roomType->delete();

        return redirect()
            ->route('room_types.index')
            ->with('success','Xóa thành công');
    }
}
