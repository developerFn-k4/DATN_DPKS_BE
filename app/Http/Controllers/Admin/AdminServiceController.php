<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;

class AdminServiceController extends Controller
{
    /**
     * GET /api/admin/services
     * Danh sách dịch vụ (có thể filter theo type)
     */
    public function index(Request $request)
    {
        $query = Service::query();

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $services = $query->latest()->get();

        return response()->json([
            'success' => true,
            'data'    => $services,
        ]);
    }

    /**
     * POST /api/admin/services
     * Tạo dịch vụ mới
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'  => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'type'  => 'required|string|in:Ẩm thực,Di chuyển,Tiện ích,Thư giãn,Phòng',
        ]);

        $service = Service::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tạo dịch vụ thành công',
            'data'    => $service,
        ], 201);
    }

    /**
     * GET /api/admin/services/{id}
     * Chi tiết dịch vụ
     */
    public function show($id)
    {
        $service = Service::findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $service,
        ]);
    }

    /**
     * PUT /api/admin/services/{id}
     * Cập nhật dịch vụ
     */
    public function update(Request $request, $id)
    {
        $service = Service::findOrFail($id);

        $validated = $request->validate([
            'name'  => 'sometimes|required|string|max:255',
            'price' => 'sometimes|required|numeric|min:0',
            'type'  => 'sometimes|required|string|in:Ẩm thực,Di chuyển,Tiện ích,Thư giãn,Phòng',
        ]);

        $service->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật dịch vụ thành công',
            'data'    => $service,
        ]);
    }

    /**
     * DELETE /api/admin/services/{id}
     * Xóa dịch vụ
     */
    public function destroy($id)
    {
        $service = Service::findOrFail($id);
        $service->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa dịch vụ thành công',
        ]);
    }
}
