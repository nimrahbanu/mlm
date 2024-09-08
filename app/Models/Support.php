<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Support extends Model
{
    protected $fillable = [
        'user_id', 'subject', 'department_id', 'priority', 'status','user_message', 'user_image', 'admin_message', 'admin_image'
    ];
    public function userData()
    {
        return $this->belongsTo(User::class,'user_id','user_id');
    }

    public function departmentData()
    {
        return $this->belongsTo(Department::class,'department_id');
    }

}
