<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
<<<<<<< HEAD
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomImage extends Model
{
    public $timestamps = false;
=======

class RoomImage extends Model
{
    public $timestamps = false; // chỉ có created_at
>>>>>>> main

    protected $fillable = [
        'room_type_id',
        'image_url',
        'created_at',
    ];

<<<<<<< HEAD
    // ảnh thuộc về 1 loại phòng
    public function roomType(): BelongsTo
=======
    // Ảnh thuộc về 1 loại phòng
    public function roomType()
>>>>>>> main
    {
        return $this->belongsTo(RoomType::class);
    }
}
