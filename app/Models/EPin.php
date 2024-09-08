<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class EPin extends Model
{

    use SoftDeletes;

    protected $table ='e_pin';
    protected $fillable = [
         
          'member_id', 'member_name', 'balance', 'quantity', 'status', 'flag','e_pin'
    ];
}
