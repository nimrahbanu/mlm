<?php
namespace App\Http\Controllers\Front;
use App\Http\Controllers\Controller;
use App\Models\PageHomeItem;
use Illuminate\Http\Request;
use App\Models\Property;
use App\Models\PropertyCategory;
use App\Models\PropertyLocation;
use App\Models\HomeAdvertisement;
use App\Models\Testimonial;
use App\Models\PageDreamProperty;
use App\Models\PageBlogItem;
use App\Models\Blog;
use App\Models\PageOtherItem;
use DB;

class HomeController extends Controller
{
    public function index()
    {
    
        return view('front.index');
    }
}
