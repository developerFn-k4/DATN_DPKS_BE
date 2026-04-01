<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


class RoomType extends Model
{
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

    protected $casts = [
        'capacity' => 'integer',
        'base_price' => 'decimal:2',
    ];


    // loại phòng thuộc về 1 khách sạn
    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    // loại phòng có nhiều phòng cụ thể
    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    // loại phòng có nhiều ảnh
    public function images()
    {
        return $this->hasMany(RoomImage::class, 'room_type_id');
    }
}
