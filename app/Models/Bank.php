<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
    protected $table = 'banks';
    protected $fillable = [
        'user_id', 'district', 'state', 'address', 'pin_code', 'bank_name', 'account_number', 'ifsc_code', 'branch', 'account_holder_name', 'upi', 'paytm', 'phone_pe', 'google_pay', 'trx_rc20', 'usdt_bep20'
    ];

}
