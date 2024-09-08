<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageDreamProperty extends Model
{
    protected $fillable = [
        'name',
        'red_title',
        'image_1',
        'city_1',
        'image_2',
        'city_2',
        'image_3',
        'city_3',
        'image_4',
        'city_4',
        'seo_title',
        'seo_meta_description'
    ];

}
