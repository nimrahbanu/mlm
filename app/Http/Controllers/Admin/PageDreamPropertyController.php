<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\PageDreamProperty;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use DB;
use Auth;

class PageDreamPropertyController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth.admin:admin');
    }

    public function edit()
    {
        $page_dream_property = PageDreamProperty::where('id',1)->first();
        return view('admin.page_dream_property', compact('page_dream_property'));
    }

    public function update(Request $request)
    {

        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }

        if ($request->hasFile('image_1')) {
            $request->validate([
                'image_1' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
            ],[
                'image_1.image' => ERR_PHOTO_IMAGE,
                'image_1.mimes' => ERR_PHOTO_JPG_PNG_GIF,
                'image_1.max' => ERR_PHOTO_MAX
            ]);

            @unlink(public_path('uploads/page_dream_property/'.$request->image_1));

            $rand_value = md5(mt_rand(11111111,99999999));
            $ext = $request->file('image_1')->extension();
            $final_name = $rand_value.'.'.$ext;
            $request->file('image_1')->move(public_path('uploads/page_dream_property/'), $final_name);

            $data['image_1'] = $final_name;
        }

        if ($request->hasFile('image_2')) {
            $request->validate([
                'image_2' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
            ],[
                'image_2.image' => ERR_PHOTO_IMAGE,
                'image_2.mimes' => ERR_PHOTO_JPG_PNG_GIF,
                'image_2.max' => ERR_PHOTO_MAX
            ]);

            @unlink(public_path('uploads/page_dream_property/'.$request->image_2));

            $rand_value = md5(mt_rand(11111111,99999999));
            $ext = $request->file('image_2')->extension();
            $final_name = $rand_value.'.'.$ext;
            $request->file('image_2')->move(public_path('uploads/page_dream_property/'), $final_name);

            $data['image_2'] = $final_name;
        }
        if ($request->hasFile('image_3')) {
            $request->validate([
                'image_3' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
            ],[
                'image_3.image' => ERR_PHOTO_IMAGE,
                'image_3.mimes' => ERR_PHOTO_JPG_PNG_GIF,
                'image_3.max' => ERR_PHOTO_MAX
            ]);

            @unlink(public_path('uploads/page_dream_property/'.$request->image_3));

            $rand_value = md5(mt_rand(11111111,99999999));
            $ext = $request->file('image_3')->extension();
            $final_name = $rand_value.'.'.$ext;
            $request->file('image_3')->move(public_path('uploads/page_dream_property/'), $final_name);

            $data['image_3'] = $final_name;
        }
        if ($request->hasFile('image_4')) {
            $request->validate([
                'image_4' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
            ],[
                'image_4.image' => ERR_PHOTO_IMAGE,
                'image_4.mimes' => ERR_PHOTO_JPG_PNG_GIF,
                'image_4.max' => ERR_PHOTO_MAX
            ]);

            @unlink(public_path('uploads/page_dream_property/'.$request->image_4));

            $rand_value = md5(mt_rand(11111111,99999999));
            $ext = $request->file('image_4')->extension();
            $final_name = $rand_value.'.'.$ext;
            $request->file('image_4')->move(public_path('uploads/page_dream_property/'), $final_name);

            $data['image_4'] = $final_name;
        }

        $data['name'] = $request->input('name');
        $data['red_title'] = $request->input('red_title');
        $data['city_1'] = $request->input('city_1');
        $data['city_2'] = $request->input('city_2');
        $data['city_3'] = $request->input('city_3');
        $data['city_4'] = $request->input('city_4');
        $data['seo_title'] = $request->input('seo_title');
        $data['seo_meta_description'] = $request->input('seo_meta_description');

        $success = PageDreamProperty::where('id',1)->update($data);
        if($success){

            return redirect()->back()->with('success', SUCCESS_ACTION);
        }else{
            PageDreamProperty::create($data);
            return redirect()->back()->with('success', SUCCESS_ACTION);

        }

    }

}
