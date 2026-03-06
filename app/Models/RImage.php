<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RImage extends Model
{
    protected $fillable = [
        'room_id',
        'image_url'
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}
