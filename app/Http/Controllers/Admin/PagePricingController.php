<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\PagePricingItem;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use DB;
use Auth;

class PagePricingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth.admin:admin');
    }

    public function edit()
    {
        $page_pricing = PagePricingItem::where('id',1)->first();
        return view('admin.page_pricing', compact('page_pricing'));
    }

    public function update(Request $request)
    {

        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }

        if ($request->hasFile('banner')) {
            $request->validate([
                'banner' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
            ],[
                'banner.image' => ERR_PHOTO_IMAGE,
                'banner.mimes' => ERR_PHOTO_JPG_PNG_GIF,
                'banner.max' => ERR_PHOTO_MAX
            ]);

            @unlink(public_path('uploads/page_banners/'.$request->current_banner));
            
            $rand_value = md5(mt_rand(11111111,99999999));
            $ext = $request->file('banner')->extension();
            $final_name = $rand_value.'.'.$ext;
            $request->file('banner')->move(public_path('uploads/page_banners/'), $final_name);

            $data['banner'] = $final_name;
        }
        
        $data['name'] = $request->input('name');
        $data['detail'] = $request->input('detail');
        $data['status'] = $request->input('status');
        $data['seo_title'] = $request->input('seo_title');
        $data['seo_meta_description'] = $request->input('seo_meta_description');

        PagePricingItem::where('id',1)->update($data);

        return redirect()->back()->with('success', SUCCESS_ACTION);

    }

}
