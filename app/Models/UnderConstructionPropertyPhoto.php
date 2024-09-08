<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnderConstructionPropertyPhoto extends Model
{
    protected $fillable = [
        'property_id',
        'type',
        'value'
    ];

}
