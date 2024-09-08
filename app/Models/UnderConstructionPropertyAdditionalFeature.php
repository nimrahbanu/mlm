<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnderConstructionPropertyAdditionalFeature extends Model
{
    protected $table ='under_construction_property_additional_features';
    protected $fillable = [
        'property_id',
        'additional_feature_name',
        'additional_feature_value'
    ];

}
