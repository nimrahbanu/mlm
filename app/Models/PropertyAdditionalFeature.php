<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyAdditionalFeature extends Model
{
    protected $fillable = [
        'property_id',
        'additional_feature_name',
        'additional_feature_value'
    ];

}
