<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\HomeAdvertisement;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use DB;
use Auth;

class HomeAdvertisementController extends Controller
{
    public function __construct() {
        $this->middleware('auth.admin:admin');
    }

    public function edit()
    {
        $adv_data = HomeAdvertisement::where('id',1)->first();
        return view('admin.advertisement_home', compact('adv_data'));
    }

    public function update(Request $request)
    {

        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }

        if($request->above_category_1 != '')
        {
            $request->validate([
                'above_category_1' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
            ],[
                'above_category_1.image' => ERR_PHOTO_IMAGE,
                'above_category_1.mimes' => ERR_PHOTO_JPG_PNG_GIF,
                'above_category_1.max' => ERR_PHOTO_MAX
            ]);
            @unlink(public_path('uploads/advertisements/'.$request->current_above_category_1));

            $ext = $request->file('above_category_1')->extension();
            $rand_value = md5(mt_rand(11111111,99999999));
            $final_name = $rand_value.'.'.$ext;
            $request->file('above_category_1')->move(public_path('uploads/advertisements/'), $final_name);

            $data['above_category_1'] = $final_name;
        }


        if($request->above_category_2 != '')
        {
            $request->validate([
                'above_category_2' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
            ],[
                'above_category_2.image' => ERR_PHOTO_IMAGE,
                'above_category_2.mimes' => ERR_PHOTO_JPG_PNG_GIF,
                'above_category_2.max' => ERR_PHOTO_MAX
            ]);
            @unlink(public_path('uploads/advertisements/'.$request->current_above_category_2));

            $ext = $request->file('above_category_2')->extension();
            $rand_value = md5(mt_rand(11111111,99999999));
            $final_name = $rand_value.'.'.$ext;
            $request->file('above_category_2')->move(public_path('uploads/advertisements/'), $final_name);

            $data['above_category_2'] = $final_name;
        }


        if($request->above_featured_property_1 != '')
        {
            $request->validate([
                'above_featured_property_1' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
            ],[
                'above_featured_property_1.image' => ERR_PHOTO_IMAGE,
                'above_featured_property_1.mimes' => ERR_PHOTO_JPG_PNG_GIF,
                'above_featured_property_1.max' => ERR_PHOTO_MAX
            ]);
            @unlink(public_path('uploads/advertisements/'.$request->current_above_featured_property_1));

            $ext = $request->file('above_featured_property_1')->extension();
            $rand_value = md5(mt_rand(11111111,99999999));
            $final_name = $rand_value.'.'.$ext;
            $request->file('above_featured_property_1')->move(public_path('uploads/advertisements/'), $final_name);

            $data['above_featured_property_1'] = $final_name;
        }


        if($request->above_featured_property_2 != '')
        {
            $request->validate([
                'above_featured_property_2' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
            ],[
                'above_featured_property_2.image' => ERR_PHOTO_IMAGE,
                'above_featured_property_2.mimes' => ERR_PHOTO_JPG_PNG_GIF,
                'above_featured_property_2.max' => ERR_PHOTO_MAX
            ]);
            @unlink(public_path('uploads/advertisements/'.$request->current_above_featured_property_2));

            $ext = $request->file('above_featured_property_2')->extension();
            $rand_value = md5(mt_rand(11111111,99999999));
            $final_name = $rand_value.'.'.$ext;
            $request->file('above_featured_property_2')->move(public_path('uploads/advertisements/'), $final_name);

            $data['above_featured_property_2'] = $final_name;
        }


        if($request->above_location_1 != '')
        {
            $request->validate([
                'above_location_1' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
            ],[
                'above_location_1.image' => ERR_PHOTO_IMAGE,
                'above_location_1.mimes' => ERR_PHOTO_JPG_PNG_GIF,
                'above_location_1.max' => ERR_PHOTO_MAX
            ]);
            @unlink(public_path('uploads/advertisements/'.$request->current_above_location_1));

            $ext = $request->file('above_location_1')->extension();
            $rand_value = md5(mt_rand(11111111,99999999));
            $final_name = $rand_value.'.'.$ext;
            $request->file('above_location_1')->move(public_path('uploads/advertisements/'), $final_name);

            $data['above_location_1'] = $final_name;
        }


        if($request->above_location_2 != '')
        {
            $request->validate([
                'above_location_2' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
            ],[
                'above_location_2.image' => ERR_PHOTO_IMAGE,
                'above_location_2.mimes' => ERR_PHOTO_JPG_PNG_GIF,
                'above_location_2.max' => ERR_PHOTO_MAX
            ]);
            @unlink(public_path('uploads/advertisements/'.$request->current_above_location_2));

            $ext = $request->file('above_location_2')->extension();
            $rand_value = md5(mt_rand(11111111,99999999));
            $final_name = $rand_value.'.'.$ext;
            $request->file('above_location_2')->move(public_path('uploads/advertisements/'), $final_name);

            $data['above_location_2'] = $final_name;
        }
        
        $data['above_category_1_url'] = $request->input('above_category_1_url');
        $data['above_category_2_url'] = $request->input('above_category_2_url');

        $data['above_featured_property_1_url'] = $request->input('above_featured_property_1_url');
        $data['above_featured_property_2_url'] = $request->input('above_featured_property_2_url');

        $data['above_location_1_url'] = $request->input('above_location_1_url');
        $data['above_location_2_url'] = $request->input('above_location_2_url');

        $data['above_category_status'] = $request->input('above_category_status');
        $data['above_featured_property_status'] = $request->input('above_featured_property_status');
        $data['above_location_status'] = $request->input('above_location_status');
        

        HomeAdvertisement::where('id',1)->update($data);
        return redirect()->back()->with('success', SUCCESS_ACTION);
    }


    public function payment_edit()
    {
        $g_setting = GeneralSetting::where('id',1)->first();
        return view('admin.setting_payment', compact('g_setting'));
    }

    public function payment_update(Request $request)
    {
        
        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }

        $data['paypal_environment'] = $request->get('paypal_environment');
        $data['paypal_client_id'] = $request->get('paypal_client_id');
        $data['paypal_secret_key'] = $request->get('paypal_secret_key');

        GeneralSetting::where('id',1)->update($data);

        return redirect()->back()->with('success', SUCCESS_ACTION);
    }


}
