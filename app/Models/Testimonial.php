<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    protected $fillable = [
        'name',
        'designation',
        'comment',
        'photo',
        'project_name',
        'project_description',
        'project_start_date',
        'project_end_date',
        'service_rating',
        'schedule_rating',
        'cost_rating',
        'willing_to_refer_rating'

    ];

}
