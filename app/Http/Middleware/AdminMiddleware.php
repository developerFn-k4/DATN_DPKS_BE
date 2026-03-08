<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Chưa đăng nhập
        if (!$request->user()) {
            return response()->json([
                'message' => 'Bạn Chưa Đăng Nhập'
            ], 401);
        }

        // Không phải admin
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'message' => 'Bạn không có quyền truy cập (Admin only)'
            ], 403);
        }
        return $next($request);
    }
}
