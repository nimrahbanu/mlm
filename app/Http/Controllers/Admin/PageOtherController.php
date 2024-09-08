<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\PageOtherItem;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use DB;
use Auth;

class PageOtherController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth.admin:admin');
    }

    public function edit()
    {
        return view('admin.page_other');
    }

    public function update(Request $request)
    {
        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }
        
        $data['login_page_seo_title'] = $request->input('login_page_seo_title');
        $data['login_page_seo_meta_description'] = $request->input('login_page_seo_meta_description');

        if ($request->hasFile('login_page_banner')) {
            $request->validate([
                'login_page_banner' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
            ],[
                'login_page_banner.image' => ERR_PHOTO_IMAGE,
                'login_page_banner.mimes' => ERR_PHOTO_JPG_PNG_GIF,
                'login_page_banner.max' => ERR_PHOTO_MAX
            ]);

            @unlink(public_path('uploads/page_banners/'.$request->current_login_page_banner));
            
            $rand_value = md5(mt_rand(11111111,99999999));
            $ext = $request->file('login_page_banner')->extension();
            $final_name = $rand_value.'.'.$ext;
            $request->file('login_page_banner')->move(public_path('uploads/page_banners/'), $final_name);

            $data['login_page_banner'] = $final_name;
        }

        $data['registration_page_seo_title'] = $request->input('registration_page_seo_title');
        $data['registration_page_seo_meta_description'] = $request->input('registration_page_seo_meta_description');

        if ($request->hasFile('registration_page_banner')) {
            $request->validate([
                'registration_page_banner' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
            ],[
                'registration_page_banner.image' => ERR_PHOTO_IMAGE,
                'registration_page_banner.mimes' => ERR_PHOTO_JPG_PNG_GIF,
                'registration_page_banner.max' => ERR_PHOTO_MAX
            ]);

            @unlink(public_path('uploads/page_banners/'.$request->current_registration_page_banner));
            
            $rand_value = md5(mt_rand(11111111,99999999));
            $ext = $request->file('registration_page_banner')->extension();
            $final_name = $rand_value.'.'.$ext;
            $request->file('registration_page_banner')->move(public_path('uploads/page_banners/'), $final_name);

            $data['registration_page_banner'] = $final_name;
        }

        $data['forget_password_page_seo_title'] = $request->input('forget_password_page_seo_title');
        $data['forget_password_page_seo_meta_description'] = $request->input('forget_password_page_seo_meta_description');

        if ($request->hasFile('forget_password_page_banner')) {
            $request->validate([
                'forget_password_page_banner' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
            ],[
                'forget_password_page_banner.image' => ERR_PHOTO_IMAGE,
                'forget_password_page_banner.mimes' => ERR_PHOTO_JPG_PNG_GIF,
                'forget_password_page_banner.max' => ERR_PHOTO_MAX
            ]);

            @unlink(public_path('uploads/page_banners/'.$request->current_forget_password_page_banner));
            
            $rand_value = md5(mt_rand(11111111,99999999));
            $ext = $request->file('forget_password_page_banner')->extension();
            $final_name = $rand_value.'.'.$ext;
            $request->file('forget_password_page_banner')->move(public_path('uploads/page_banners/'), $final_name);

            $data['forget_password_page_banner'] = $final_name;
        }

        $data['customer_panel_page_seo_title'] = $request->input('customer_panel_page_seo_title');
        $data['customer_panel_page_seo_meta_description'] = $request->input('customer_panel_page_seo_meta_description');

        if ($request->hasFile('customer_panel_page_banner')) {
            $request->validate([
                'customer_panel_page_banner' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
            ],[
                'customer_panel_page_banner.image' => ERR_PHOTO_IMAGE,
                'customer_panel_page_banner.mimes' => ERR_PHOTO_JPG_PNG_GIF,
                'customer_panel_page_banner.max' => ERR_PHOTO_MAX
            ]);

            @unlink(public_path('uploads/page_banners/'.$request->current_customer_panel_page_banner));
            
            $rand_value = md5(mt_rand(11111111,99999999));
            $ext = $request->file('customer_panel_page_banner')->extension();
            $final_name = $rand_value.'.'.$ext;
            $request->file('customer_panel_page_banner')->move(public_path('uploads/page_banners/'), $final_name);

            $data['customer_panel_page_banner'] = $final_name;
        }

    
        return redirect()->back()->with('success', SUCCESS_ACTION);
    }
}
