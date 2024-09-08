<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\UnderConstructionProperty;
use App\Models\UnderConstructionPropertySocialItem;
use App\Models\PropertyAdditionalFeature;
use App\Models\PropertyPhoto;
use App\Models\PropertyVideo;
use App\Models\PropertyCategory;
use App\Models\PropertyLocation;
use App\Models\PropertyAmenity;
// use App\Models\UnderConstructionPropertyVideo;
use App\Models\UnderConstructionPropertyPhoto;
use App\Models\UnderConstructionPropertyAdditionalFeature;
use App\Models\UnderConstructionPropertyAmenity;
use App\Models\Amenity;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Mail;
use DB;
use Auth;

class UnderConstructionPropertyController extends Controller
{
    public function __construct() {
        $this->middleware('auth.admin:admin');
    }

    public function index() {
        $property = UnderConstructionProperty::with('UnderConstructionPropertyCategory','UnderConstructionPropertyLocation')->get();
        return view('admin.under_construction_property_view', compact('property'));
    }

    public function create() {
        $property = UnderConstructionProperty::get();
        $property_category = PropertyCategory::orderBy('id','asc')->get();
        $property_location = PropertyLocation::orderBy('id','asc')->get();
        $amenity = Amenity::orderBy('id','asc')->get();
        return view('admin.under_construction_property_create', compact('property','property_category','property_location','amenity'));
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

        $statement = DB::select("SHOW TABLE STATUS LIKE 'under_construction_properties'");
        $ai_id = $statement[0]->Auto_increment;

        $rand_value = md5(mt_rand(11111111,99999999));
        $ext = $request->file('property_featured_photo')->extension();
        $final_name = $rand_value.'.'.$ext;
        $request->file('property_featured_photo')->move(public_path('uploads/under_construction_property_featured_photos'), $final_name);

        $obj = new UnderConstructionProperty();
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
                $obj = new UnderConstructionPropertyAmenity;
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
                    $item->move(public_path('uploads/under_construction_property_photos'), $final_photo_name);

                    // $obj = new UnderConstructionPropertyPhoto;
                    // $obj->property_id = $ai_id;
                    // $obj->photo = $final_photo_name;
                    // $obj->save();
                    $obj = new UnderConstructionPropertyPhoto;
                    $obj->property_id = $ai_id;
                    $obj->type = 'Photo';
                    $obj->value = $final_photo_name;
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
                    // $obj = new UnderConstructionPropertyVideo;
                    // $obj->property_id = $ai_id;
                    // $obj->youtube_video_id = $arr_youtube_video_id[$i];
                    // $obj->save();
                    $obj = new UnderConstructionPropertyPhoto;
                    $obj->property_id = $ai_id;
                    $obj->type = 'Video';
                    $obj->value = $arr_youtube_video_id[$i];
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
                    $obj = new UnderConstructionPropertySocialItem;
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
                    $obj = new UnderConstructionPropertyAdditionalFeature;
                    $obj->property_id = $ai_id;
                    $obj->additional_feature_name = $arr_additional_feature_name[$i];
                    $obj->additional_feature_value = $arr_additional_feature_value[$i];
                    $obj->save();
                }
            }
        }

        return redirect()->route('admin_underconstruction_property_view')->with('success', SUCCESS_ACTION);
    }

    public function edit($id) {

        $user_data = Auth::user();

        $property = UnderConstructionProperty::where('id', $id)->first();

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
        $property_amenities = UnderConstructionPropertyAmenity::where('property_id',$id)->orderBy('id','asc')->get();
        foreach($property_amenities as $row) {
            $existing_amenities_array[] = $row->amenity_id;
        }

        $property_photos = UnderConstructionPropertyPhoto::where('property_id',$id)->orderBy('id','asc')->get();
        // $property_videos = UnderConstructionPropertyVideo::where('property_id',$id)->orderBy('id','asc')->get();
        $property_additional_features = UnderConstructionPropertyAdditionalFeature::where('property_id',$id)->orderBy('id','asc')->get();

        $property_social_items = UnderConstructionPropertySocialItem::where('property_id',$id)->orderBy('id','asc')->get();

        return view('admin.under_construction_property_edit', compact('property','property_category','property_location','amenity','property_photos','property_additional_features','property_social_items','property_amenities','existing_amenities_array'));

    }

    public function update(Request $request, $id) {

        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }

        $obj = UnderConstructionProperty::findOrFail($id);
        $data = $request->only($obj->getFillable());
        if($request->hasFile('property_featured_photo')) {

            $request->validate([
                'property_featured_photo' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
            ],[
                'property_featured_photo.image' => ERR_PHOTO_IMAGE,
                'property_featured_photo.mimes' => ERR_PHOTO_JPG_PNG_GIF,
                'property_featured_photo.max' => ERR_PHOTO_MAX
            ]);

            @unlink(public_path('uploads/under_construction_property_featured_photos/'.$request->current_photo));

            // Uploading the file
            $ext = $request->file('property_featured_photo')->extension();
            $rand_value = md5(mt_rand(11111111,99999999));
            $final_name = $rand_value.'.'.$ext;
            $request->file('property_featured_photo')->move(public_path('uploads/under_construction_property_featured_photos/'), $final_name);

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

        $property_amenities = UnderConstructionPropertyAmenity::where('property_id',$id)->orderBy('id','asc')->get();
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
                UnderConstructionPropertyAmenity::where('property_id', $id)
                    ->where('amenity_id', $result1[$i])
                    ->delete();
            }
        }

        $result2 = array_values(array_diff($arr_amenity,$existing_amenities_array));
        if(!empty($result2)) {
            for($i=0;$i<count($result2);$i++) {
                $obj = new UnderConstructionPropertyAmenity;
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
                    $item->move(public_path('uploads/under_construction_property_photos'), $final_photo_name);

                    $obj = new UnderConstructionPropertyPhoto;
                    $obj->property_id = $id;
                    $obj->type = 'Photo';
                    $obj->value = $final_photo_name;
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
                    // $obj = new UnderConstructionPropertyVideo;
                    // $obj->property_id = $id;
                    // $obj->youtube_video_id = $arr_youtube_video_id[$i];
                    // $obj->save();

                    $obj = new UnderConstructionPropertyPhoto;
                    $obj->property_id = $id;
                    $obj->type = 'Video';
                    $obj->value = $arr_youtube_video_id[$i];
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
                    $obj = new UnderConstructionPropertySocialItem;
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
                    $obj = new UnderConstructionPropertyAdditionalFeature;
                    $obj->property_id = $id;
                    $obj->additional_feature_name = $arr_additional_feature_name[$i];
                    $obj->additional_feature_value = $arr_additional_feature_value[$i];
                    $obj->save();
                }
            }
        }
        return redirect()->route('admin_underconstruction_property_view')->with('success', SUCCESS_ACTION);
    }

    public function destroy($id) {
        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }

        $property = UnderConstructionProperty::findOrFail($id);
        @unlink(public_path('uploads/under_construction_property_featured_photos/'.$property->property_featured_photo));
        $property->delete();

        UnderConstructionPropertyAmenity::where('property_id', $id)->delete();
        UnderConstructionPropertySocialItem::where('property_id', $id)->delete();
        // UnderConstructionPropertyVideo::where('property_id', $id)->delete();
        UnderConstructionPropertyAdditionalFeature::where('property_id', $id)->delete();

        $all_photos = UnderConstructionPropertyPhoto::where('property_id',$id)->where('type','Photo')->get();
        foreach($all_photos as $item) {
            @unlink(public_path('uploads/under_construction_property_photos/'.$item->value));
        }

        UnderConstructionPropertyPhoto::where('property_id', $id)->delete();

        // Success Message and redirect
        return Redirect()->back()->with('success', SUCCESS_ACTION);
    }


    public function delete_social_item($id) {

        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }

        $property_social_item = UnderConstructionPropertySocialItem::findOrFail($id);
        $property_social_item->delete();
        return Redirect()->back()->with('success', SUCCESS_ACTION);
    }

    public function delete_photo($id) {
        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }

        $property_photo = UnderConstructionPropertyPhoto::findOrFail($id);
        @unlink(public_path('uploads/under_construction_property_photos/'.$property_photo->value));
        $property_photo->delete();
        return Redirect()->back()->with('success', SUCCESS_ACTION);
    }

    public function delete_video($id) {

        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }
        $property_video = UnderConstructionPropertyPhoto::findOrFail($id);

        // $property_video = UnderConstructionPropertyVideo::findOrFail($id);
        $property_video->delete();
        return Redirect()->back()->with('success', SUCCESS_ACTION);
    }

    public function delete_additional_feature($id) {

        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }

        $property_additional_feature = UnderConstructionPropertyAdditionalFeature::findOrFail($id);
        $property_additional_feature->delete();
        return Redirect()->back()->with('success', SUCCESS_ACTION);
    }

    public function change_status($id) {
        $property = UnderConstructionProperty::find($id);
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

}
