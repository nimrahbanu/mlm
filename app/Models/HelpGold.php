<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HelpGold extends Model
{
    protected $table='help_gold';
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
}
