<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomImage extends Model
{
    public $timestamps = false;


    protected $fillable = [
        'room_type_id',
        'image_url',
        'created_at',
    ];

    // ảnh thuộc về 1 loại phòng
    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }
}
