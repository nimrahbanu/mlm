<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\PropertyLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Mail;
use DB;
use Auth;

class PropertyLocationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth.admin:admin');
    }

    public function index()
    {
        $property_location = PropertyLocation::orderBy('id', 'asc')->get();
        return view('admin.property_location_view', compact('property_location'));
    }

    public function create()
    {
        $property_location = PropertyLocation::get();
        return view('admin.property_location_create', compact('property_location'));
    }

    public function store(Request $request)
    {

        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }

        $request->validate([
            'property_location_name' => 'required|unique:property_locations',
            'property_location_slug' => 'unique:property_locations',
            'property_location_photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ],[
            'property_location_name.required' => ERR_NAME_REQUIRED,
            'property_location_name.unique' => ERR_NAME_EXIST,
            'property_location_slug.unique' => ERR_SLUG_UNIQUE,
            'property_location_photo.required' => ERR_PHOTO_REQUIRED,
            'property_location_photo.image' => ERR_PHOTO_IMAGE,
            'property_location_photo.mimes' => ERR_PHOTO_JPG_PNG_GIF,
            'property_location_photo.max' => ERR_PHOTO_MAX
        ]);

        $statement = DB::select("SHOW TABLE STATUS LIKE 'property_locations'");
        $ai_id = $statement[0]->Auto_increment;

        $ext = $request->file('property_location_photo')->extension();
        $rand_value = md5(mt_rand(11111111,99999999));
        $final_name = $rand_value.'.'.$ext;
        $request->file('property_location_photo')->move(public_path('uploads/property_location_photos/'), $final_name);

        $property_location = new PropertyLocation();
        $data = $request->only($property_location->getFillable());
        if(empty($data['property_location_slug']))
        {
            unset($data['property_location_slug']);
            $data['property_location_slug'] = Str::slug($request->property_location_name);
        }

        if(preg_match('/\s/',$data['property_location_slug']))
        {
            return Redirect()->back()->with('error', ERR_SLUG_WHITESPACE);
        }

        unset($data['property_location_photo']);
        $data['property_location_photo'] = $final_name;
       
        $property_location->fill($data)->save();

        return redirect()->route('admin_property_location_view')->with('success', SUCCESS_ACTION);
    }

    public function edit($id)
    {
        $property_location = PropertyLocation::findOrFail($id);
        return view('admin.property_location_edit', compact('property_location'));
    }

    public function update(Request $request, $id)
    {

        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }

        $property_location = PropertyLocation::findOrFail($id);
        $data = $request->only($property_location->getFillable());

        if ($request->hasFile('property_location_photo')) {

            $request->validate([
                'property_location_photo' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
            ],[
                'property_location_photo.image' => ERR_PHOTO_IMAGE,
                'property_location_photo.mimes' => ERR_PHOTO_JPG_PNG_GIF,
                'property_location_photo.max' => ERR_PHOTO_MAX
            ]);

            @unlink(public_path('uploads/property_location_photos/'.$property_location->property_location_photo));

            // Uploading the file
            $ext = $request->file('property_location_photo')->extension();
            $rand_value = md5(mt_rand(11111111,99999999));
            $final_name = $rand_value.'.'.$ext;
            $request->file('property_location_photo')->move(public_path('uploads/property_location_photos/'), $final_name);

            unset($data['property_location_photo']);
            $data['property_location_photo'] = $final_name;
        }

        $request->validate([
            'property_location_name'   =>  [
                'required',
                Rule::unique('property_locations')->ignore($id),
            ],
            'property_location_slug'   =>  [
                Rule::unique('property_locations')->ignore($id),
            ]
        ],[
            'property_location_name.required' => ERR_NAME_REQUIRED,
            'property_location_name.unique' => ERR_NAME_EXIST,
            'property_location_slug.unique' => ERR_SLUG_UNIQUE,
        ]);

        if(empty($data['property_location_slug']))
        {
            unset($data['property_location_slug']);
            $data['property_location_slug'] = Str::slug($request->property_location_name);
        }

        if(preg_match('/\s/',$data['property_location_slug']))
        {
            return Redirect()->back()->with('error', ERR_SLUG_WHITESPACE);
        }

        $property_location->fill($data)->save();

        return redirect()->route('admin_property_location_view')->with('success', SUCCESS_ACTION);
    }

    public function destroy($id)
    {

        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }
        
        $tot = Property::where('property_location_id',$id)->count();
        if($tot)
        {
            return Redirect()->back()->with('error', ERR_ITEM_DELETE);   
        }

        $property_location = PropertyLocation::findOrFail($id);
        @unlink(public_path('uploads/property_location_photos/'.$property_location->property_location_photo));
        $property_location->delete();

        // Success Message and redirect
        return Redirect()->back()->with('success', SUCCESS_ACTION);
    }

}
