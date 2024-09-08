<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyVideo extends Model
{
    protected $fillable = [
        'property_id',
        'youtube_video_id',
        'is_approved'

    ];

}
