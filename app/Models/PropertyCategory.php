<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyCategory extends Model
{
    protected $fillable = [
        'property_category_name',
        'property_category_slug',
        'property_category_photo',
        'seo_title',
        'seo_meta_description',
        'property_category_description'
    ];

    public function rProperty() {
        return $this->hasMany( Property::class, 'property_category_id', 'id' );
    }

}
