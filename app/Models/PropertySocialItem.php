<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertySocialItem extends Model
{
    protected $fillable = [
        'property_id',
        'social_icon',
        'social_url'
    ];

}
