<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class EPinTransfer extends Model
{

    use SoftDeletes;

    protected $table ='e_pin_transfer';
    protected $fillable = [
         
        'provided_by', 'member_id', 'member_name', 'balance', 'quantity', 'status', 'flag','e_pin','is_used'
    ];
    public function MemberData(){
        return $this->hasOne(User::class,'id','member_id');
    }
    public function providedByData(){
        return $this->hasOne(User::class,'id','provided_by');
    }
    public function EpinUsed(){
        return $this->hasOne(User::class,'registration_code','e_pin')->select('name','id','registration_code');
    }
}
