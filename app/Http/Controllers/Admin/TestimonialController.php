<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use DB;
use Auth;

class TestimonialController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth.admin:admin');
    }

    public function index()
    {
        $testimonial = Testimonial::all();
        return view('admin.testimonial_view', compact('testimonial'));
    }

    public function create()
    {
        return view('admin.testimonial_create');
    }

    public function store(Request $request)
    {
        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }

        $testimonial = new Testimonial();
        $data = $request->only($testimonial->getFillable());

        $request->validate([
            'name' => 'required',
            'designation' => 'required',
            'comment' => 'required',
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ],[
            'name.required' => ERR_NAME_REQUIRED,
            'designation.required' => ERR_DESIGNATION_REQUIRED,
            'comment.required' => ERR_COMMENT_REQUIRED,
            'photo.required' => ERR_PHOTO_REQUIRED,
            'photo.image' => ERR_PHOTO_IMAGE,
            'photo.mimes' => ERR_PHOTO_JPG_PNG_GIF,
            'photo.max' => ERR_PHOTO_MAX
        ]);

        $rand_value = md5(mt_rand(11111111,99999999));
        $ext = $request->file('photo')->extension();
        $final_name = $rand_value.'.'.$ext;
        $request->file('photo')->move(public_path('uploads/testimonials/'), $final_name);

        unset($data['photo']);
        $data['photo'] = $final_name;
        if(isset($request->ongoing)){
            $data['project_end_date'] = null;
        }
        $testimonial->fill($data)->save();
        return redirect()->route('admin_testimonial_view')->with('success', SUCCESS_ACTION);
    }

    public function edit($id)
    {
        $testimonial = Testimonial::findOrFail($id);
        return view('admin.testimonial_edit', compact('testimonial'));
    }

    public function update(Request $request, $id)
    {

        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }

        $testimonial = Testimonial::findOrFail($id);
        $data = $request->only($testimonial->getFillable());

        if ($request->hasFile('photo')) {

            $request->validate([
                'photo' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
            ],[
                'photo.image' => ERR_PHOTO_IMAGE,
                'photo.mimes' => ERR_PHOTO_JPG_PNG_GIF,
                'photo.max' => ERR_PHOTO_MAX
            ]);

            @unlink(public_path('uploads/testimonials/'.$request->current_photo));

            // Uploading the file
            $rand_value = md5(mt_rand(11111111,99999999));
            $ext = $request->file('photo')->extension();
            $final_name = $rand_value.'.'.$ext;
            $request->file('photo')->move(public_path('uploads/testimonials/'), $final_name);

            unset($data['photo']);
            $data['photo'] = $final_name;
        }

        $request->validate([
            'name' => 'required',
            'designation' => 'required',
            'comment' => 'required'
        ],[
            'name.required' => ERR_NAME_REQUIRED,
            'designation.required' => ERR_DESIGNATION_REQUIRED,
            'comment.required' => ERR_COMMENT_REQUIRED
        ]);
        if(isset($request->ongoing)){
            $data['project_end_date'] = null;
        }
        $testimonial->fill($data)->save();

        return redirect()->route('admin_testimonial_view')->with('success', SUCCESS_ACTION);
    }

    public function destroy($id)
    {

        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }

        $testimonial = Testimonial::findOrFail($id);
        @unlink(public_path('uploads/testimonials/'.$testimonial->photo));
        $testimonial->delete();
        return Redirect()->back()->with('success', SUCCESS_ACTION);
    }
}
