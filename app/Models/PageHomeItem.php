<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageHomeItem extends Model
{
    protected $fillable = [
        'seo_title',
        'seo_meta_description',
        'search_heading',
        'search_text',
        'search_background',
        'category_heading',
        'category_subheading',
        'category_total',
        'category_status',
        'property_heading',
        'property_red_heading',
        'property_subheading',
        'property_total',
        'property_status',
        'testimonial_heading',
        'testimonial_subheading',
        'testimonial_background',
        'testimonial_status',
        'location_heading',
        'location_subheading',
        'location_total',
        'location_status',
        'view_all_image',
        'view_all_title'
    ];

}
