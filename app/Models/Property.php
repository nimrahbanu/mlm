<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    protected $fillable = [
        'property_name',
        'property_slug',
        'property_description',
        'property_address',
        'property_phone',
        'property_email',
        'property_website',
        'property_map',
        'property_price',
        'property_bedroom',
        'property_bathroom',
        'property_size',
        'property_built_year',
        'property_garage',
        'property_block',
        'property_floor',
        'property_type',
        'property_oh_monday',
        'property_oh_tuesday',
        'property_oh_wednesday',
        'property_oh_thursday',
        'property_oh_friday',
        'property_oh_saturday',
        'property_oh_sunday',
        'property_featured_photo',
        'property_category_id',
        'property_location_id',
        'user_id',
        'admin_id',
        'user_type',
        'seo_title',
        'seo_meta_description',
        'property_status',
        'is_featured'
    ];

    public function rPropertyCategory() {
        return $this->belongsTo( PropertyCategory::class, 'property_category_id' );
    }

    public function rPropertyLocation() {
        return $this->belongsTo( PropertyLocation::class, 'property_location_id' );
    }

    public function propertyAminities() {
        return $this->hasMany( PropertyAmenity::class,);
    }


    public function user() {
        return $this->belongsTo( User::class);
    }



}
