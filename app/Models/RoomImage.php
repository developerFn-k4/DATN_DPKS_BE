<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomImage extends Model
{
    public $timestamps = false; // chỉ có created_at

    protected $fillable = [
        'room_type_id',
        'image_url',
        'created_at',
    ];

    // Ảnh thuộc về 1 loại phòng
    public function roomType()
    {
        return $this->belongsTo(RoomType::class);
    }
}
