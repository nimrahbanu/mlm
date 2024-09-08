<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SevenLevelTransaction extends Model
{
    protected $table = 'seven_level_transaction';
    protected $fillable = [
        'sender_id',
        'receiver_id',
        'first_level',
        'second_level',
        'third_level',
        'fourth_level',
        'five_level',
        'six_level',
        'seven_level',
        'extra_details',
        'status',
    
    ];

   

}
