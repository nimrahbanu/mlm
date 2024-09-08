<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageBlogItem extends Model
{
    protected $fillable = [
        'name',
        'detail',
        'banner',
        'status',
        'seo_title',
        'seo_meta_description',
        'blog_title',
        'blog_red_title',

    ];

}
