<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    protected $fillable = [
       
        'package_name', 'member', 'help', 'total_help', 'level_upgrade', 'sponser_help', 'profit', 're_birth', 'package_order'	

    ];

    public function users()
    {
        return $this->hasMany(User::class, 'package_id');
    }
}
