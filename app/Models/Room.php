<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;


class Room extends Model
{
    protected $fillable = [
        'room_type_id',
        'room_number',
        'floor',

        'status',
    ];

    // phòng thuộc về 1 loại phòng
    public function roomType(): BelongsTo

    {
        return $this->belongsTo(RoomType::class);
    }
}
