<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
<<<<<<< HEAD
use Illuminate\Database\Eloquent\Relations\BelongsTo;
=======
>>>>>>> main

class Room extends Model
{
    protected $fillable = [
        'room_type_id',
        'room_number',
        'floor',
<<<<<<< HEAD
        'status',
    ];

    // phòng thuộc về 1 loại phòng
    public function roomType(): BelongsTo
=======
        'status'
    ];


    public function roomType()
>>>>>>> main
    {
        return $this->belongsTo(RoomType::class);
    }
}
