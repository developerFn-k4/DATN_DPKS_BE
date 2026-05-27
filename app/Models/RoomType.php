<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoomType extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'hotel_id',
        'name',
        'capacity',
        'bed_type',
        'area',
        'amenities',
        'base_price',
        'currency',
        'status'
    ];

    /**
     * TỰ ĐỘNG ÉP KIỂU DỮ LIỆU (Casts)
     * Fix triệt để lỗi 500 do xung đột định dạng mảng/chuỗi của trường amenities
     */
    protected $casts = [
        'hotel_id'   => 'integer',
        'capacity'   => 'integer',
        'area'       => 'integer',
        'base_price' => 'decimal:2',
        'amenities'  => 'array', // Ép kiểu cột json/text trong CSDL thành mảng Array khi làm việc với React
    ];

    /**
     * Mối quan hệ: Loại phòng thuộc về 1 khách sạn
     */
    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    /**
     * Mối quan hệ: Loại phòng có nhiều phòng cụ thể
     */
    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    /**
     * Mối quan hệ: Loại phòng có nhiều ảnh minh họa
     */
    public function images(): HasMany
    {
        return $this->hasMany(RoomImage::class, 'room_type_id');
    }

    /**
     * Mối quan hệ: Loại phòng có nhiều đánh giá từ khách hàng
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'room_type_id');
    }
}