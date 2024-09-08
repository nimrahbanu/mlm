<?php
namespace App\Http\Controllers\Front;
use App\Http\Controllers\Controller;
use App\Mail\PropertyPageMessage;
use App\Mail\PropertyPageReport;
use App\Models\EmailTemplate;
use App\Models\GeneralSetting;
use App\Models\Property;
use App\Models\PropertyAdditionalFeature;
use App\Models\PropertyAmenity;
use App\Models\PropertyCategory;
use App\Models\PropertyLocation;
use App\Models\PropertyPhoto;
use App\Models\PropertySocialItem;
use App\Models\PropertyVideo;
use App\Models\Amenity;
use App\Models\PagePropertyCategoryItem;
use App\Models\PagePropertyItem;
use App\Models\PagePropertyLocationItem;
use App\Models\Review;
use App\Models\Wishlist;
use App\Models\User;
use App\Models\Admin;
use Illuminate\Http\Request;
use DB;
use Auth;
use Illuminate\Support\Facades\Mail;

class PropertyController extends Controller
{
	public function index()
    {
        abort(404);
	}

    public function detail($slug)
    {
        $g_setting = GeneralSetting::where('id', 1)->first();
        $detail = Property::with('rPropertyLocation', 'rPropertyCategory')
        	->where('property_slug', $slug)
        	->first();

        $property_social_items = PropertySocialItem::where('property_id', $detail->id)->get();
        $property_photos = PropertyPhoto::where('property_id', $detail->id)->where('is_approved','1')->get();
        $property_videos = PropertyVideo::where('property_id', $detail->id)->where('is_approved','1')->get();
        $property_amenities = PropertyAmenity::where('property_id', $detail->id)->get();
        $property_additional_features = PropertyAdditionalFeature::where('property_id', $detail->id)->get();
        $property_categories = PropertyCategory::orderBy('property_category_name', 'asc')->get();
        $property_locations = PropertyLocation::orderBy('property_location_name', 'asc')->get();

        $reviews = Review::where('property_id',$detail->id)
            ->where('status',1)
            ->orderBy('id', 'asc')
            ->get();

        // Getting overall rating
        if($reviews->isEmpty()) {
            $overall_rating = 0;
        } else {
            $total_number = 0;
            $count = 0;
            foreach($reviews as $item) {
                $count++;
                $total_number = $total_number+$item->rating;
            }
            $overall_rating = $total_number/$count;
            if($overall_rating>0 && $overall_rating<=1) {
                $overall_rating = 1;
            }
            elseif($overall_rating>1 && $overall_rating<=1.5) {
                $overall_rating = 1.5;
            }
            elseif($overall_rating>1.5 && $overall_rating<=2) {
                $overall_rating = 2;
            }
            elseif($overall_rating>2 && $overall_rating<=2.5) {
                $overall_rating = 2.5;
            }
            elseif($overall_rating>2.5 && $overall_rating<=3) {
                $overall_rating = 3;
            }
            elseif($overall_rating>3 && $overall_rating<=3.5) {
                $overall_rating = 3.5;
            }
            elseif($overall_rating>3.5 && $overall_rating<=4) {
                $overall_rating = 4;
            }
            elseif($overall_rating>4 && $overall_rating<=4.5) {
                $overall_rating = 4.5;
            }
            elseif($overall_rating>4.5 && $overall_rating<=5) {
                $overall_rating = 5;
            }
        }

        if($detail->user_id == 0) {
            $agent_detail = Admin::where('id',$detail->admin_id)->first();
        } elseif($detail->admin_id == 0) {
            $agent_detail = User::where('id',$detail->user_id)->first();
        }

        $current_auth_user_id = 0;
        if(Auth::user()) {
            $current_auth_user_id = Auth::user()->id;
        }

        // If he already given review for this item
        $already_given = 0;
        $already_given = Review::where('property_id', $detail->id)
            ->where('status',1)
            ->where('agent_id', $current_auth_user_id)
            ->where('agent_type', 'Customer')
            ->count();

    	return view('front.property_detail', compact('detail','g_setting','property_social_items','property_photos','property_videos','property_amenities','property_additional_features','property_categories','property_locations','agent_detail','reviews','current_auth_user_id', 'already_given', 'overall_rating'));
    }

    public function category_all()
    {
        $g_setting = GeneralSetting::where('id', 1)->first();
        $property_category_page_data = PagePropertyCategoryItem::where('id', 1)->first();
        $orderwise_property_categories = DB::select('SELECT *
                        FROM property_categories as r1
                        LEFT JOIN (SELECT property_category_id, count(*) as total
                            FROM properties as l
                            JOIN property_categories as lc
                            ON l.property_category_id = lc.id
                            GROUP BY property_category_id
                            ORDER BY total DESC) as r2
                        ON r1.id = r2.property_category_id
                        ORDER BY r2.total DESC');
        return view('front.property_categories', compact('g_setting', 'property_category_page_data', 'orderwise_property_categories'));
    }

    public function category_detail($slug)
    {
    	$g_setting = GeneralSetting::where('id', 1)->first();
        $property_category_page_data = PagePropertyCategoryItem::where('id', 1)->first();
        $property_category_detail = PropertyCategory::where('property_category_slug',$slug)->first();
    	$property_items = Property::with('rPropertyCategory','rPropertyLocation')->where(['property_category_id'=>$property_category_detail->id, 'is_approved'=>'1'])->paginate(15);
    	return view('front.property_category_detail', compact('g_setting', 'property_category_detail', 'property_items', 'property_category_page_data'));
    }

    public function location_all()
    {
        $g_setting = GeneralSetting::where('id', 1)->first();
        $property_location_page_data = PagePropertyLocationItem::where('id', 1)->first();
        $orderwise_property_locations = DB::select('SELECT *
                        FROM property_locations as r1
                        LEFT JOIN (SELECT property_location_id, count(*) as total
                            FROM properties as l
                            JOIN property_categories as lc
                            ON l.property_location_id = lc.id
                            GROUP BY property_location_id
                            ORDER BY total DESC) as r2
                        ON r1.id = r2.property_location_id
                        ORDER BY r2.total DESC');

        return view('front.property_locations', compact('g_setting', 'property_location_page_data', 'orderwise_property_locations'));
    }

    public function location_detail($slug)
    {
        $g_setting = GeneralSetting::where('id', 1)->first();
        $property_location_page_data = PagePropertyLocationItem::where('id', 1)->first();
        $property_location_detail = PropertyLocation::where('property_location_slug',$slug)->first();
        $property_items = Property::with('rPropertyCategory','rPropertyLocation')->where('property_location_id',$property_location_detail->id)->paginate(15);
        return view('front.property_location_detail', compact('g_setting', 'property_location_detail', 'property_items', 'property_location_page_data'));
    }

    public function agent_detail($type,$id)
    {
        $g_setting = GeneralSetting::where('id', 1)->first();

	    if($type == 'admin') {
            $agent_detail = Admin::where('id',$id)->first();
            $all_properties = Property::with('rPropertyCategory', 'rPropertyLocation')
                ->where('admin_id',$id)
                ->where('property_status','Active')
                ->get();
        } else {
            $agent_detail = User::where('id',$id)->first();
            $all_properties = Property::with('rPropertyCategory', 'rPropertyLocation')
                ->where('user_id',$id)
                ->where('property_status','Active')
                ->get();
        }
    	return view('front.property_agent_detail', compact('g_setting','agent_detail','all_properties'));
    }

    public function property_result(Request $request)
    {

        $g_setting = GeneralSetting::where('id', 1)->first();
        $property_page_data = PagePropertyItem::where('id', 1)->first();
        $property_categories = PropertyCategory::get();
        $property_locations = PropertyLocation::get();
        $amenities = Amenity::get();

        // Breaking Urls
        $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $actual_link_len = strlen($actual_link);

        $first_part = url()->current();
        $first_part_len = strlen($first_part);

        $all_category = [];
        $all_location = [];
        $all_amenity = [];

        $aa = substr($actual_link,($first_part_len+1),($actual_link_len-1));
        $arr = explode('&',$aa);

        if($request->amenity){
            $properties = Property::whereHas('propertyAminities', function($query) use ($request){
                $sortArr = [];
                if($request->amenity){
                    foreach($request->amenity as $amnty){
                        $sortArr[] = $amnty;
                    }
                    $query->whereIn('amenity_id', $sortArr);
                }
            })->with('user')->orderBy('id','desc');
        }else{
            $properties = Property::with('user')->orderBy('id','desc');
        }

        if($request->location){
            $location_arr = $request->location;
            $properties = $properties->whereIn('property_location_id', $location_arr);
        }

        if($request->category){
            $category_arr = $request->category;
            $properties = $properties->whereIn('property_category_id', $category_arr);
        }


        if($request->property_type){
            if($request->property_type == 'sale'){
                $properties = $properties->where('property_type','For Sale');
            }

            if($request->property_type == 'rent'){
                $properties = $properties->where('property_type','For Rent');
            }
            if($request->property_type == 'For Home Stay'){
                $properties = $properties->where('property_type','For Home Stay');
            }

            if($request->property_type == 'For Construction'){
                $properties = $properties->where('property_type','For Construction');
            }
        }

        if($request->text){
            $properties = $properties->where('property_name', 'LIKE', '%'.$request->text.'%');
        }

        $properties = $properties->paginate(10);
        $properties = $properties->appends($request->all());

        return view('front.property_result', compact('g_setting','property_page_data','property_categories', 'property_locations', 'amenities', 'all_category', 'all_location', 'all_amenity', 'properties'));
    }


    public function search_property_result(Request $request){
        if($request->amenity){
            $properties = Property::whereHas('propertyAminities', function($query) use ($request){
                $sortArr = [];
                if($request->amenity){
                    foreach($request->amenity as $amnty){
                        $sortArr[] = $amnty;
                    }
                    $query->whereIn('amenity_id', $sortArr);
                }
            })->with('user')->orderBy('id','desc');
        }else{
            $properties = Property::with('user')->orderBy('id','desc');
        }

        if($request->location){
            $location_arr = $request->location;
            $properties = $properties->whereIn('property_location_id', $location_arr);
        }

        if($request->category){
            $category_arr = $request->category;
            $properties = $properties->whereIn('property_category_id', $category_arr);
        }


        if($request->property_type){
            if($request->property_type == 'sale'){
                $properties = $properties->where('property_type','For Sale');
            }

            if($request->property_type == 'rent'){
                $properties = $properties->where('property_type','For Rent');
            }
            if($request->property_type == 'For Home Stay'){
                $properties = $properties->where('property_type','For Home Stay');
            }

            if($request->property_type == 'For Construction'){
                $properties = $properties->where('property_type','For Construction');
            }
        }

        if($request->text){
            $properties = $properties->where('property_name', 'LIKE', '%'.$request->text.'%');
        }
        $properties = $properties->where('property_status','Active');
        $properties = $properties->where('is_approved','1');
        $properties = $properties->paginate(1000);
        $properties = $properties->appends($request->all());


        return view('front.ajax_search_property', compact('properties'));
    }

    public function send_message(Request $request)
    {
        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }

        $g_setting = GeneralSetting::where('id', 1)->first();
        $request->validate([
            'name' => 'required',
            'email' => 'required|email',
            'message' => 'required'
        ], [
            'name.required' => ERR_NAME_REQUIRED,
            'email.required' => ERR_EMAIL_REQUIRED,
            'email.email' => ERR_EMAIL_INVALID,
            'message.required' => ERR_MESSAGE_REQUIRED
        ]);

        if($g_setting->google_recaptcha_status == 'Show') {
            $request->validate([
                'g-recaptcha-response' => 'required'
            ], [
                'g-recaptcha-response.required' => ERR_RECAPTCHA_REQUIRED
            ]);
        }

        $property_name = $request->property_name;
        $property_url = '<a href="'.url('property/'.$request->property_slug).'">'.url('property/'.$request->property_slug).'</a>';
        $agent_name = $request->agent_name;

        // Send Email
        $email_template_data = EmailTemplate::where('id', 9)->first();
        $subject = $email_template_data->et_subject;
        $message = $email_template_data->et_content;

        $message = str_replace('[[agent_name]]', $agent_name, $message);
        $message = str_replace('[[property_name]]', $property_name, $message);
        $message = str_replace('[[property_url]]', $property_url, $message);
        $message = str_replace('[[name]]', $request->name, $message);
        $message = str_replace('[[email]]', $request->email, $message);
        $message = str_replace('[[phone]]', $request->phone, $message);
        $message = str_replace('[[message]]', $request->message, $message);

        Mail::to($request->agent_email)->send(new PropertyPageMessage($subject,$message));

        return redirect()->back()->with('success', SUCCESS_MESSAGE_SENT);
    }

    public function report_property(Request $request)
    {
        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }

        $g_setting = GeneralSetting::where('id', 1)->first();
        $request->validate([
            'name' => 'required',
            'email' => 'required|email',
            'message' => 'required'
        ], [
            'name.required' => ERR_NAME_REQUIRED,
            'email.required' => ERR_EMAIL_REQUIRED,
            'email.email' => ERR_EMAIL_INVALID,
            'message.required' => ERR_MESSAGE_REQUIRED,
        ]);

        if($g_setting->google_recaptcha_status == 'Show') {
            $request->validate([
                'g-recaptcha-response' => 'required'
            ], [
                'g-recaptcha-response.required' => ERR_RECAPTCHA_REQUIRED
            ]);
        }

        $property_name = $request->property_name;
        $property_url = '<a href="'.url('property/'.$request->property_slug).'">'.url('property/'.$request->property_slug).'</a>';

        // Send Email
        $email_template_data = EmailTemplate::where('id', 10)->first();
        $subject = $email_template_data->et_subject;
        $message = $email_template_data->et_content;

        $message = str_replace('[[property_name]]', $property_name, $message);
        $message = str_replace('[[property_url]]', $property_url, $message);
        $message = str_replace('[[name]]', $request->name, $message);
        $message = str_replace('[[email]]', $request->email, $message);
        $message = str_replace('[[phone]]', $request->phone, $message);
        $message = str_replace('[[message]]', $request->message, $message);

        $admin_data = Admin::where('id',1)->first();

        Mail::to($admin_data->email)->send(new PropertyPageReport($subject,$message));

        return redirect()->back()->with('success', SUCCESS_REPORT_SENT);
    }

    public function wishlist_add($id)
    {
        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }

	    if(Auth::user() == null) {
            return redirect()->back()->with('error', ERR_LOGIN_FIRST);
        }

	    $check_previous = Wishlist::where('property_id',$id)->count();
	    if($check_previous > 0) {
            return redirect()->back()->with('error', ERR_ALREADY_TO_WISHLIST);
        }

	    $user_data = Auth::user();

        $obj = new Wishlist;
        $obj->user_id = $user_data->id;
        $obj->property_id = $id;
        $obj->save();

        return redirect()->back()->with('success', SUCCESS_WISHLIST_ADD);
    }

    public function ajax_wishlist_add($id)
    {
        if(env('PROJECT_MODE') == 0) {
            return response()->json(['is_success' => false ,'message' => env('PROJECT_NOTIFICATION')]);
        }

	    if(Auth::user() == null) {
            return response()->json(['is_success' => false, 'message' => ERR_LOGIN_FIRST]);
        }

	    $check_previous = Wishlist::where('property_id',$id)->count();
	    if($check_previous > 0) {
            return response()->json(['is_success' => false, 'message' => ERR_ALREADY_TO_WISHLIST]);
        }

	    $user_data = Auth::user();

        $obj = new Wishlist;
        $obj->user_id = $user_data->id;
        $obj->property_id = $id;
        $obj->save();

        return response()->json(['is_success' => true,'message' => SUCCESS_WISHLIST_ADD]);
    }
}
