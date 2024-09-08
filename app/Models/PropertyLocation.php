<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyLocation extends Model
{
    protected $fillable = [
        'property_location_name',
        'property_location_slug',
        'property_location_photo',
        'seo_title',
        'seo_meta_description'
    ];

    public function rProperty() {
        return $this->hasMany( Property::class, 'property_location_id', 'id' );
    }

}
