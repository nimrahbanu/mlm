<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeAdvertisement extends Model
{
    protected $fillable = [
        'above_category_1',
        'above_category_1_url',
        'above_category_2',
        'above_category_2_url',
        'above_category_status',
        'above_featured_property_1',
        'above_featured_property_1_url',
        'above_featured_property_2',
        'above_featured_property_2_url',
        'above_featured_property_status',
        'above_location_1',
        'above_location_1_url',
        'above_location_2',
        'above_location_2_url',
        'above_location_status'
    ];

}
