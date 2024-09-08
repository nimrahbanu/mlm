<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\PageHomeItem;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use DB;
use Auth;

class PageHomeController extends Controller
{
    public function __construct() {
        $this->middleware('auth.admin:admin');
    }

    public function edit() {
        $page_home = PageHomeItem::where('id',1)->first();
        return view('admin.page_home', compact('page_home'));
    }

    public function update(Request $request) {

        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }

        if($request->search_background != '') {
            $request->validate([
                'search_background' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
            ],[
                'search_background.image' => ERR_PHOTO_IMAGE,
                'search_background.mimes' => ERR_PHOTO_JPG_PNG_GIF,
                'search_background.max' => ERR_PHOTO_MAX
            ]);
            @unlink(public_path('uploads/site_photos/'.$request->current_search_background));
            $rand_value = md5(mt_rand(11111111,99999999));
            $ext = $request->file('search_background')->extension();
            $final_name = $rand_value.'.'.$ext;
            $request->file('search_background')->move(public_path('uploads/site_photos/'), $final_name);
            $data['search_background'] = $final_name;
        }

        if($request->testimonial_background != '') {
            $request->validate([
                'testimonial_background' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
            ],[
                'testimonial_background.image' => ERR_PHOTO_IMAGE,
                'testimonial_background.mimes' => ERR_PHOTO_JPG_PNG_GIF,
                'testimonial_background.max' => ERR_PHOTO_MAX
            ]);
            @unlink(public_path('uploads/site_photos/'.$request->current_testimonial_background));
            $rand_value = md5(mt_rand(11111111,99999999));
            $ext = $request->file('testimonial_background')->extension();
            $final_name = $rand_value.'.'.$ext;
            $request->file('testimonial_background')->move(public_path('uploads/site_photos/'), $final_name);
            $data['testimonial_background'] = $final_name;
        }

        if($request->view_all_image != '') {
            $request->validate([
                'view_all_image' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
            ],[
                'view_all_image.image' => ERR_PHOTO_IMAGE,
                'view_all_image.mimes' => ERR_PHOTO_JPG_PNG_GIF,
                'view_all_image.max' => ERR_PHOTO_MAX
            ]);
            @unlink(public_path('uploads/site_photos/'.$request->view_all_image));
            $rand_value = md5(mt_rand(11111111,99999999));
            $ext = $request->file('view_all_image')->extension();
            $view_all_image = $rand_value.'.'.$ext;
            $request->file('view_all_image')->move(public_path('uploads/site_photos/'), $view_all_image);
            $data['view_all_image'] = $view_all_image;
        }


        $data['seo_title'] = $request->input('seo_title');
        $data['seo_meta_description'] = $request->input('seo_meta_description');
        $data['search_heading'] = $request->input('search_heading');
        $data['search_text'] = $request->input('search_text');
        $data['category_heading'] = $request->input('category_heading');
        $data['category_subheading'] = $request->input('category_subheading');
        $data['category_total'] = $request->input('category_total');
        $data['category_status'] = $request->input('category_status');
        $data['property_heading'] = $request->input('property_heading');
        $data['property_red_heading'] = $request->input('property_red_heading');
        $data['property_subheading'] = $request->input('property_subheading');
        $data['property_total'] = $request->input('property_total');
        $data['property_status'] = $request->input('property_status');
        $data['testimonial_heading'] = $request->input('testimonial_heading');
        $data['testimonial_subheading'] = $request->input('testimonial_subheading');
        $data['testimonial_status'] = $request->input('testimonial_status');
        $data['location_heading'] = $request->input('location_heading');
        $data['location_subheading'] = $request->input('location_subheading');
        $data['location_total'] = $request->input('location_total');
        $data['location_status'] = $request->input('location_status');
        $data['view_all_title'] = $request->input('view_all_title');
        PageHomeItem::where('id',1)->update($data);
        return redirect()->back()->with('success', SUCCESS_ACTION);
    }

}
