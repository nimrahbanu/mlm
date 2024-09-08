<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\DynamicPage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use DB;
use Auth;

class DynamicPageController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth.admin:admin');
    }

    public function index()
    {
        $dynamic_page = DynamicPage::all();
        return view('admin.dynamic_page_view', compact('dynamic_page'));
    }

    public function create()
    {
        return view('admin.dynamic_page_create');
    }

    public function store(Request $request)
    {

        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }

        $dynamic_page = new DynamicPage();
        $data = $request->only($dynamic_page->getFillable());

        $request->validate([
            'dynamic_page_name' => 'required|unique:dynamic_pages',
            'dynamic_page_slug' => 'unique:dynamic_pages',
            'dynamic_page_banner' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ],[
            'dynamic_page_name.required' => ERR_NAME_REQUIRED,
            'dynamic_page_name.unique' => ERR_NAME_EXIST,
            'dynamic_page_slug.unique' => ERR_SLUG_UNIQUE,
            'dynamic_page_banner.required' => ERR_PHOTO_REQUIRED,
            'dynamic_page_banner.image' => ERR_PHOTO_IMAGE,
            'dynamic_page_banner.mimes' => ERR_PHOTO_JPG_PNG_GIF,
            'dynamic_page_banner.max' => ERR_PHOTO_MAX
        ]);

        if(empty($data['dynamic_page_slug'])) {
            $data['dynamic_page_slug'] = Str::slug($request->dynamic_page_name);
        }

        $rand_value = md5(mt_rand(11111111,99999999));
        $ext = $request->file('dynamic_page_banner')->extension();
        $final_name = $rand_value.'.'.$ext;
        $request->file('dynamic_page_banner')->move(public_path('uploads/page_banners/'), $final_name);

        unset($data['dynamic_page_banner']);
        $data['dynamic_page_banner'] = $final_name;
        
        $dynamic_page->fill($data)->save();
        return redirect()->route('admin_dynamic_page_view')->with('success', SUCCESS_ACTION);
    }

    public function edit($id)
    {
        $dynamic_page = DynamicPage::findOrFail($id);
        return view('admin.dynamic_page_edit', compact('dynamic_page'));
    }

    public function update(Request $request, $id)
    {

        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }

        $dynamic_page = DynamicPage::findOrFail($id);
        $data = $request->only($dynamic_page->getFillable());

        if ($request->hasFile('dynamic_page_banner')) {

            $request->validate([
                'dynamic_page_banner' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
            ],[
                'dynamic_page_banner.image' => ERR_PHOTO_IMAGE,
                'dynamic_page_banner.mimes' => ERR_PHOTO_JPG_PNG_GIF,
                'dynamic_page_banner.max' => ERR_PHOTO_MAX
            ]);

            @unlink(public_path('uploads/page_banners/'.$request->current_banner));

            // Uploading the file
            $rand_value = md5(mt_rand(11111111,99999999));
            $ext = $request->file('dynamic_page_banner')->extension();
            $final_name = $rand_value.'.'.$ext;
            $request->file('dynamic_page_banner')->move(public_path('uploads/page_banners/'), $final_name);

            unset($data['dynamic_page_banner']);
            $data['dynamic_page_banner'] = $final_name;
        }

        $request->validate([
            'dynamic_page_name'   =>  [
                'required',
                Rule::unique('dynamic_pages')->ignore($id),
            ],
            'dynamic_page_slug'   =>  [
                Rule::unique('dynamic_pages')->ignore($id),
            ]
        ],[
            'dynamic_page_name.required' => ERR_NAME_REQUIRED,
            'dynamic_page_name.unique' => ERR_NAME_EXIST,
            'dynamic_page_slug.unique' => ERR_SLUG_UNIQUE,
        ]);
        
        $dynamic_page->fill($data)->save();
        
        return redirect()->route('admin_dynamic_page_view')->with('success', SUCCESS_ACTION);
    }

    public function destroy($id)
    {

        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }
        
        $dynamic_page = DynamicPage::findOrFail($id);
        @unlink(public_path('uploads/page_banners/'.$dynamic_page->dynamic_page_banner));
        $dynamic_page->delete();
        return Redirect()->back()->with('success', SUCCESS_ACTION);
    }
}
