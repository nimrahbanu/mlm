<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SevenLevelTransaction extends Model
{
    protected $table = 'seven_level_transaction';
    protected $fillable = [
       'sender_id', 'receiver_id', 'first_level', 'second_level', 'third_level', 'fourth_level', 'five_level', 'six_level', 'seven_level', 'first_level_status', 'second_level_status', 'third_level_status', 'fourth_level_status', 'five_level_status', 'six_level_status', 'seven_level_status', 'first_level_confirm_date', 'second_level_confirm_date', 'third_level_confirm_date', 'fourth_level_confirm_date', 'five_level_confirm_date', 'six_level_confirm_date', 'seven_level_confirm_date', 'extra_details', 'status',
    ];
    public function senderDetail(){
        return $this->belongsTo(User::class, 'user_id','sender_id')->select('name','phone','user_id');

    }
   
    public function getCreatedAtAttribute($value)
    {
        return \Carbon\Carbon::parse($value)->format('d M, Y h:i A');
    }

}
 