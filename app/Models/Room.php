<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = [
        'room_number',
        'room_type_id',
        'floor',
        'status'
    ];


    public function roomType()
    {
        return $this->belongsTo(RoomType::class);
    }
}
