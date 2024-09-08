<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\PropertySocialItem;
use App\Models\PropertyAdditionalFeature;
use App\Models\PropertyPhoto;
use App\Models\PropertyVideo;
use App\Models\PropertyCategory;
use App\Models\PropertyLocation;
use App\Models\PropertyAmenity;
use App\Models\Amenity;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Mail;
use DB;
use Auth;

class PropertyController extends Controller
{
    public function __construct() {
        $this->middleware('auth.admin:admin');
    }

    public function index() {
        $property = Property::with('rPropertyCategory','rPropertyLocation')->orderby('created_at','DESC')->get();
        return view('admin.property_view', compact('property'));
    }

    public function create() {
        $property = Property::get();
        $property_category = PropertyCategory::orderBy('id','asc')->get();
        $property_location = PropertyLocation::orderBy('id','asc')->get();
        $amenity = Amenity::orderBy('id','asc')->get();
        return view('admin.property_create', compact('property','property_category','property_location','amenity'));
    }

    public function store(Request $request) {

        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }

        $user_data = Auth::user();

        $request->validate([
            'property_name' => 'required|unique:properties',
            'property_slug' => 'unique:properties',
            'property_description' => 'required',
            'property_featured_photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'property_price' => 'required|numeric',
            'property_bedroom' => 'required',
            'property_bathroom' => 'required',
            'property_size' => 'required'
        ],[
            'property_name.required' => ERR_NAME_REQUIRED,
            'property_name.unique' => ERR_NAME_EXIST,
            'property_slug.unique' => ERR_SLUG_UNIQUE,
            'property_description.required' => ERR_DESCRIPTION_REQUIRED,
            'property_featured_photo.required' => ERR_PHOTO_REQUIRED,
            'property_featured_photo.image' => ERR_PHOTO_IMAGE,
            'property_featured_photo.mimes' => ERR_PHOTO_JPG_PNG_GIF,
            'property_featured_photo.max' => ERR_PHOTO_MAX,
            'property_price.required' => ERR_PRICE_REQUIRED,
            'property_price.numeric' => ERR_PRICE_NUMERIC,
            'property_bedroom.required' => ERR_BEDROOM_REQUIRED,
            'property_bathroom.required' => ERR_BATHROOM_REQUIRED,
            'property_size.required' => ERR_SIZE_REQUIRED
        ]);

        $statement = DB::select("SHOW TABLE STATUS LIKE 'properties'");
        $ai_id = $statement[0]->Auto_increment;

        $rand_value = md5(mt_rand(11111111,99999999));
        $ext = $request->file('property_featured_photo')->extension();
        $final_name = $rand_value.'.'.$ext;
        $request->file('property_featured_photo')->move(public_path('uploads/property_featured_photos'), $final_name);

        $obj = new Property();
        $data = $request->only($obj->getFillable());
        if(empty($data['property_slug'])) {
            unset($data['property_slug']);
            $data['property_slug'] = Str::slug($request->property_name);
        }
        if(preg_match('/\s/',$data['property_slug'])) {
            return Redirect()->back()->with('error', ERR_SLUG_WHITESPACE);
        }
        $data['property_featured_photo'] = $final_name;
        $data['user_id'] = 0;
        $data['admin_id'] = $user_data->id;
        $obj->fill($data)->save();


        // Amenity
        if($request->amenity != '') {
            $arr_amenity = array();
            foreach($request->amenity as $item) {
                $arr_amenity[] = $item;
            }
            for($i=0;$i<count($arr_amenity);$i++) {
                $obj = new PropertyAmenity;
                $obj->property_id = $ai_id;
                $obj->amenity_id = $arr_amenity[$i];
                $obj->save();
            }
        }

        // Photo
        if($request->photo_list == '') {
            //echo 'No photo selected';
        } else {
            foreach($request->photo_list as $item) {
                $file_in_mb = $item->getSize()/1024/1024;
                $main_file_ext = $item->extension();
                $main_mime_type = $item->getMimeType();

                if( ($main_mime_type == 'image/jpeg' || $main_mime_type == 'image/png' || $main_mime_type == 'image/gif') && $file_in_mb <= 2 ) {
                    $rand_value = md5(mt_rand(11111111,99999999));
                    $final_photo_name = $rand_value.'.'.$main_file_ext;
                    $item->move(public_path('uploads/property_photos'), $final_photo_name);

                    $obj = new PropertyPhoto;
                    $obj->property_id = $ai_id;
                    $obj->photo = $final_photo_name;
                    $obj->save();
                }
            }
        }


        // Video
        if($request->youtube_video_id[0] != '') {
            $arr_youtube_video_id = array();
            foreach($request->youtube_video_id as $item) {
                $arr_youtube_video_id[] = $item;
            }
            for($i=0;$i<count($arr_youtube_video_id);$i++) {
                if($arr_youtube_video_id[$i] != '') {
                    $obj = new PropertyVideo;
                    $obj->property_id = $ai_id;
                    $obj->youtube_video_id = $arr_youtube_video_id[$i];
                    $obj->save();
                }
            }
        }


        // Social Icons
        if($request->social_icon[0] != '') {
            $arr_social_icon = array();
            $arr_social_url = array();
            foreach($request->social_icon as $item) {
                $arr_social_icon[] = $item;
            }
            foreach($request->social_url as $item) {
                $arr_social_url[] = $item;
            }
            for($i=0;$i<count($arr_social_icon);$i++) {
                if( ($arr_social_icon[$i] != '') && ($arr_social_url[$i] != '') ) {
                    $obj = new PropertySocialItem;
                    $obj->property_id = $ai_id;
                    $obj->social_icon = $arr_social_icon[$i];
                    $obj->social_url = $arr_social_url[$i];
                    $obj->save();
                }
            }
        }


        // Additional Features
        if($request->additional_feature_name[0] != '') {
            $arr_additional_feature_name = array();
            $arr_additional_feature_value = array();
            foreach($request->additional_feature_name as $item) {
                $arr_additional_feature_name[] = $item;
            }
            foreach($request->additional_feature_value as $item) {
                $arr_additional_feature_value[] = $item;
            }
            for($i=0;$i<count($arr_additional_feature_name);$i++) {
                if( ($arr_additional_feature_name[$i] != '') && ($arr_additional_feature_value[$i] != '') ) {
                    $obj = new PropertyAdditionalFeature;
                    $obj->property_id = $ai_id;
                    $obj->additional_feature_name = $arr_additional_feature_name[$i];
                    $obj->additional_feature_value = $arr_additional_feature_value[$i];
                    $obj->save();
                }
            }
        }
        return redirect()->route('admin_property_view')->with('success', SUCCESS_ACTION);
    }

    public function edit($id) {

        $user_data = Auth::user();

        $property = Property::where('id', $id)->first();

        if($property->admin_id == 0) {
            abort(404);
        }
        if($property->admin_id != $user_data->id) {
            abort(404);
        }

        $property_category = PropertyCategory::orderBy('id','asc')->get();
        $property_location = PropertyLocation::orderBy('id','asc')->get();
        $amenity = Amenity::orderBy('id','asc')->get();

        $existing_amenities_array = array();
        $property_amenities = PropertyAmenity::where('property_id',$id)->orderBy('id','asc')->get();
        foreach($property_amenities as $row) {
            $existing_amenities_array[] = $row->amenity_id;
        }

        $property_photos = PropertyPhoto::where('property_id',$id)->orderBy('id','asc')->get();
        $property_videos = PropertyVideo::where('property_id',$id)->orderBy('id','asc')->get();
        $property_additional_features = PropertyAdditionalFeature::where('property_id',$id)->orderBy('id','asc')->get();

        $property_social_items = PropertySocialItem::where('property_id',$id)->orderBy('id','asc')->get();

        return view('admin.property_edit', compact('property','property_category','property_location','amenity','property_photos','property_videos','property_additional_features','property_social_items','property_amenities','existing_amenities_array'));

    }

    public function update(Request $request, $id) {

        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }

        $obj = Property::findOrFail($id);
        $data = $request->only($obj->getFillable());
        if($request->hasFile('property_featured_photo')) {

            $request->validate([
                'property_featured_photo' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
            ],[
                'property_featured_photo.image' => ERR_PHOTO_IMAGE,
                'property_featured_photo.mimes' => ERR_PHOTO_JPG_PNG_GIF,
                'property_featured_photo.max' => ERR_PHOTO_MAX
            ]);

            @unlink(public_path('uploads/property_featured_photos/'.$request->current_photo));

            // Uploading the file
            $ext = $request->file('property_featured_photo')->extension();
            $rand_value = md5(mt_rand(11111111,99999999));
            $final_name = $rand_value.'.'.$ext;
            $request->file('property_featured_photo')->move(public_path('uploads/property_featured_photos/'), $final_name);

            unset($data['property_featured_photo']);
            $data['property_featured_photo'] = $final_name;
        }

        $request->validate([
            'property_name'   =>  [
                'required',
                Rule::unique('properties')->ignore($id),
            ],
            'property_slug'   =>  [
                Rule::unique('properties')->ignore($id),
            ],
            'property_description' => 'required',
            'property_price' => 'required|numeric',
            'property_bedroom' => 'required',
            'property_bathroom' => 'required',
            'property_size' => 'required'
        ],[
            'property_name.required' => ERR_NAME_REQUIRED,
            'property_name.unique' => ERR_NAME_EXIST,
            'property_slug.unique' => ERR_SLUG_UNIQUE,
            'property_description.required' => ERR_DESCRIPTION_REQUIRED,
            'property_price.required' => ERR_PRICE_REQUIRED,
            'property_price.numeric' => ERR_PRICE_NUMERIC,
            'property_bedroom.required' => ERR_BEDROOM_REQUIRED,
            'property_bathroom.required' => ERR_BATHROOM_REQUIRED,
            'property_size.required' => ERR_SIZE_REQUIRED
        ]);
        if(empty($data['property_slug'])) {
            unset($data['property_slug']);
            $data['property_slug'] = Str::slug($request->property_name);
        }
        if(preg_match('/\s/',$data['property_slug'])) {
            return Redirect()->back()->with('error', ERR_SLUG_WHITESPACE);
        }
        $obj->fill($data)->save();


        // Amenity
        $existing_amenities_array = array();
        $arr_amenity = array();
        $result1 = array();
        $result2 = array();

        $property_amenities = PropertyAmenity::where('property_id',$id)->orderBy('id','asc')->get();
        foreach($property_amenities as $row) {
            $existing_amenities_array[] = $row->amenity_id;
        }

        if($request->amenity != '') {
            foreach($request->amenity as $item) {
                $arr_amenity[] = $item;
            }
        }

        $result1 = array_values(array_diff($existing_amenities_array, $arr_amenity));
        if(!empty($result1)) {
            for($i=0;$i<count($result1);$i++) {
                PropertyAmenity::where('property_id', $id)
                    ->where('amenity_id', $result1[$i])
                    ->delete();
            }
        }

        $result2 = array_values(array_diff($arr_amenity,$existing_amenities_array));
        if(!empty($result2)) {
            for($i=0;$i<count($result2);$i++) {
                $obj = new PropertyAmenity;
                $obj->property_id = $id;
                $obj->amenity_id = $result2[$i];
                $obj->save();
            }
        }


        // Photo
        if($request->photo_list == '') {
            //echo 'No photo selected';
        } else {
            foreach($request->photo_list as $item) {
                $file_in_mb = $item->getSize()/1024/1024;
                $main_file_ext = $item->extension();
                $main_mime_type = $item->getMimeType();

                if( ($main_mime_type == 'image/jpeg' || $main_mime_type == 'image/png' || $main_mime_type == 'image/gif') && $file_in_mb <= 2 ) {
                    $rand_value = md5(mt_rand(11111111,99999999));
                    $final_photo_name = $rand_value.'.'.$main_file_ext;
                    $item->move(public_path('uploads/property_photos'), $final_photo_name);

                    $obj = new PropertyPhoto;
                    $obj->property_id = $id;
                    $obj->photo = $final_photo_name;
                    $obj->save();
                }
            }
        }


        // Video
        if($request->youtube_video_id[0] != '') {
            $arr_youtube_video_id = array();
            foreach($request->youtube_video_id as $item) {
                $arr_youtube_video_id[] = $item;
            }
            for($i=0;$i<count($arr_youtube_video_id);$i++) {
                if($arr_youtube_video_id[$i] != '') {
                    $obj = new PropertyVideo;
                    $obj->property_id = $id;
                    $obj->youtube_video_id = $arr_youtube_video_id[$i];
                    $obj->save();
                }
            }
        }


        // Social Icons
        if($request->social_icon[0] != '')
        {
            $arr_social_icon = array();
            $arr_social_url = array();
            foreach($request->social_icon as $item) {
                $arr_social_icon[] = $item;
            }
            foreach($request->social_url as $item) {
                $arr_social_url[] = $item;
            }
            for($i=0;$i<count($arr_social_icon);$i++) {
                if( ($arr_social_icon[$i] != '') && ($arr_social_url[$i] != '') ) {
                    $obj = new PropertySocialItem;
                    $obj->property_id = $id;
                    $obj->social_icon = $arr_social_icon[$i];
                    $obj->social_url = $arr_social_url[$i];
                    $obj->save();
                }
            }
        }

        // Additional Features
        if($request->additional_feature_name[0] != '') {
            $arr_additional_feature_name = array();
            $arr_additional_feature_value = array();
            foreach($request->additional_feature_name as $item) {
                $arr_additional_feature_name[] = $item;
            }
            foreach($request->additional_feature_value as $item) {
                $arr_additional_feature_value[] = $item;
            }
            for($i=0;$i<count($arr_additional_feature_name);$i++) {
                if( ($arr_additional_feature_name[$i] != '') && ($arr_additional_feature_value[$i] != '') ) {
                    $obj = new PropertyAdditionalFeature;
                    $obj->property_id = $id;
                    $obj->additional_feature_name = $arr_additional_feature_name[$i];
                    $obj->additional_feature_value = $arr_additional_feature_value[$i];
                    $obj->save();
                }
            }
        }
        return redirect()->route('admin_property_view')->with('success', SUCCESS_ACTION);
    }

    public function destroy($id) {

        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }

        $property = Property::findOrFail($id);
        @unlink(public_path('uploads/property_featured_photos/'.$property->property_featured_photo));
        $property->delete();

        PropertyAmenity::where('property_id', $id)->delete();
        PropertySocialItem::where('property_id', $id)->delete();
        PropertyVideo::where('property_id', $id)->delete();
        PropertyAdditionalFeature::where('property_id', $id)->delete();

        $all_photos = PropertyPhoto::where('property_id',$id)->get();
        foreach($all_photos as $item) {
            @unlink(public_path('uploads/property_photos/'.$item->photo));
        }

        PropertyPhoto::where('property_id', $id)->delete();

        // Success Message and redirect
        return Redirect()->back()->with('success', SUCCESS_ACTION);
    }


    public function delete_social_item($id) {

        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }

        $property_social_item = PropertySocialItem::findOrFail($id);
        $property_social_item->delete();
        return Redirect()->back()->with('success', SUCCESS_ACTION);
    }

    public function delete_photo($id) {

        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }

        $property_photo = PropertyPhoto::findOrFail($id);
        @unlink(public_path('uploads/property_photos/'.$property_photo->photo));
        $property_photo->delete();
        return Redirect()->back()->with('success', SUCCESS_ACTION);
    }

    public function delete_video($id) {

        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }

        $property_video = PropertyVideo::findOrFail($id);
        $property_video->delete();
        return Redirect()->back()->with('success', SUCCESS_ACTION);
    }

    public function delete_additional_feature($id) {

        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }

        $property_additional_feature = PropertyAdditionalFeature::findOrFail($id);
        $property_additional_feature->delete();
        return Redirect()->back()->with('success', SUCCESS_ACTION);
    }

    public function change_status($id) {
        $property = Property::find($id);
        if($property->property_status == 'Active') {
            if(env('PROJECT_MODE') == 0) {
                $message=env('PROJECT_NOTIFICATION');
            } else {
                $property->property_status = 'Pending';
                $message=SUCCESS_ACTION;
                $property->save();
            }
        } else {
            if(env('PROJECT_MODE') == 0) {
                $message=env('PROJECT_NOTIFICATION');
            } else {
                $property->property_status = 'Active';
                $message=SUCCESS_ACTION;
                $property->save();
            }
        }
        return response()->json($message);
    }
    public function property_approve_status($id) {
        $property = Property::find($id);

        if($property->is_approved == '1') {
            if(env('PROJECT_MODE') == 0) {
                $message=env('PROJECT_NOTIFICATION');
            } else {
                $property->is_approved = '0';
                $message=SUCCESS_ACTION;
                $property->save();
            }
        } else {
            if(env('PROJECT_MODE') == 0) {
                $message=env('PROJECT_NOTIFICATION');
            } else {
                $property->is_approved = '1';
                $message=SUCCESS_ACTION;
                $property->save();
            }
        }
        return response()->json($message);
    }
    public function property_photo_approve_status($id) {
        $property = PropertyPhoto::find($id);

        if($property->is_approved == '1') {
            if(env('PROJECT_MODE') == 0) {
                $message=env('PROJECT_NOTIFICATION');
            } else {
                $property->is_approved = '0';
                $message=SUCCESS_ACTION;
                $property->save();
            }
        } else {
            if(env('PROJECT_MODE') == 0) {
                $message=env('PROJECT_NOTIFICATION');
            } else {
                $property->is_approved = '1';
                $message=SUCCESS_ACTION;
                $property->save();
            }
        }
        return response()->json($message);
    }
    public function property_video_approve_status($id) {
        $property = PropertyVideo::find($id);

        if($property->is_approved == '1') {
            if(env('PROJECT_MODE') == 0) {
                $message=env('PROJECT_NOTIFICATION');
            } else {
                $property->is_approved = '0';
                $message=SUCCESS_ACTION;
                $property->save();
            }
        } else {
            if(env('PROJECT_MODE') == 0) {
                $message=env('PROJECT_NOTIFICATION');
            } else {
                $property->is_approved = '1';
                $message=SUCCESS_ACTION;
                $property->save();
            }
        }
        return response()->json($message);
    }

}
