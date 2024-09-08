<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class Payment extends Model
{
    protected $fillable = [
        'sender',
        'receiver',
        'amount',
        'sender_position',
        'receiver_position',
        'received_payments_count'
        
    ];
    public function senderData(){
        return $this->hasOne(User::class,'id','sender');
    }
    public function receiverByData(){
        return $this->hasOne(User::class,'id','receiver');
    }
}
