<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HelpStar extends Model
{
    protected $table='help_star';
    protected $fillable = [
        'sender_id',
        'receiver_id',
        'amount',
        'sender_position',
        'receiver_position',
        'received_payments_count',
        'commitment_date',
        'confirm_date',
        'status',
        'transaction_no',
        'narration',
        'image',
    ];

    public function receiverByData(){
        return $this->hasOne(User::class,'user_id','receiver_id');
    }
    public function senderData(){
        return $this->hasOne(User::class,'user_id','sender_id');
    }

    public function getCreatedAtAttribute($value)
    {
        return \Carbon\Carbon::parse($value)->format('d M, Y h:i A');
    }

    public function getConfirmDateAttribute($value)
    {
        if (is_null($value)) {
            return null; // Return null if the value is null
        }
    
        return \Carbon\Carbon::parse($value)->format('d M, Y h:i A');
    }
    
}
