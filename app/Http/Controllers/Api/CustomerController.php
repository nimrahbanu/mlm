<?php
namespace App\Http\Controllers\Front;
use App\Http\Controllers\Controller;
use App\Models\LanguageMenuText;
use App\Models\LanguageWebsiteText;
use App\Models\LanguageNotificationText;
use App\Models\User;
use App\Models\Wishlist;
use App\Models\Amenity;
use App\Models\Property;
use App\Models\PropertyCategory;
use App\Models\PropertyLocation;
use App\Models\PropertySocialItem;
use App\Models\PropertyAdditionalFeature;
use App\Models\PropertyPhoto;
use App\Models\PropertyVideo;
use App\Models\PropertyAmenity;
use App\Models\Package;
use App\Models\PackagePurchase;
use App\Models\Review;
use App\Models\GeneralSetting;
use App\Models\EmailTemplate;
use App\Models\PageOtherItem;
use App\Models\Payment;
use App\Mail\PurchaseCompletedEmailToCustomer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use DB;
use Hash;
use Auth;
use Illuminate\Support\Facades\Mail;
use Razorpay\Api\Api;
use Mollie\Laravel\Facades\Mollie;

class CustomerController extends Controller
{
	public function __construct() {
    	$this->middleware('auth:web');
    }

    public function dashboard()
    {
        $page_other_item = PageOtherItem::where('id',1)->first();

        $g_setting = GeneralSetting::where('id', 1)->first();
         

        $detail = User::where('package_id', 2)
            ->first();
            $approve_person = User::where('is_green', '0')
            ->first();

        return view('front.customer_dashboard', compact('g_setting','detail','page_other_item','approve_person'));
    }

    public function update_profile()
    {
        $user_data = Auth::user();
        $page_other_item = PageOtherItem::where('id',1)->first();
        $g_setting = GeneralSetting::where('id', 1)->first();
        return view('front.user.customer_update_profile', compact('user_data','g_setting','page_other_item'));
    }

    public function update_profile_confirm(Request $request)
    {

        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }

        $user_data = Auth::user();
        $obj = User::findOrFail($user_data->id);
        $data = $request->only($obj->getFillable());
        $request->validate([
            'email'   =>  [
                'required',
                'email',
                Rule::unique('users')->ignore($user_data->id),
            ]
        ],[
            'email.required' => ERR_EMAIL_REQUIRED,
            'email.email' => ERR_EMAIL_INVALID,
            'email.unique' => ERR_EMAIL_EXIST
        ]);
        $obj->fill($data)->save();
        return redirect()->back()->with('success', SUCCESS_PROFILE_UPDATE);
    }

    public function update_password()
    {
        $g_setting = GeneralSetting::where('id', 1)->first();
        $page_other_item = PageOtherItem::where('id',1)->first();
        return view('front.customer_update_password', compact('g_setting','page_other_item'));
    }
 

    public function approvePayment(Request $request,$id)
    {
        // Start a database transaction
        DB::beginTransaction();
    
        try {
            // Find the user who is making the payment
            $payer = User::findOrFail($id);
    
            // Update the payer's status to "is_green"
            $payer->is_green = '1';
            $payer->save();  
            // Create a new payment record
            $payment = Payment::create([
                'sender' => $payer->id,
                'receiver' => Auth::id(),
                'amount' => 300,
                'sender_position' => $payer->package_id,
                'receiver_position' => Auth::user()->package_id,
            ]);
    
            if ($payment) {
                // Update the receiver's received payments count
                $receiver = Auth::user();
                $user=  User::where('id', Auth::user()->id)->first();
                $user->received_payments_count+=1;
                $user->save();
                if($user->received_payments_count == 3){
                    $user->package_id =3;
                    $user->received_payments_count =0;
                    $user->save();

                }
                // $receiver->increment('received_payments_count');
    
                // Commit the transaction
                DB::commit();
    
                return redirect()->back()->with('success', SUCCESS_PROFILE_UPDATE);
            }
        } catch (\Exception $e) {
            dd($e);
            // Rollback the transaction on error
            DB::rollBack();
            
            return redirect()->back()->with('error', 'Payment processing failed.');
        }
    }

    public function taking_help(Request $request){
        $page_other_item = PageOtherItem::where('id',1)->first();
        $data = Payment::where('receiver', Auth::user()->id)->orderBy('id', 'asc')->paginate(10);
        return view('front.taking_help', compact('data','page_other_item'));

    }

    public function giving_help(Request $request){
        $page_other_item = PageOtherItem::where('id',1)->first();
        $data = Payment::where('sender', Auth::user()->id)->orderBy('id', 'asc')->paginate(10);
        return view('front.giving_help', compact('data','page_other_item'));
    }
    public function wishlist()
    {
        $user_data = Auth::user();
        $g_setting = GeneralSetting::where('id', 1)->first();

        $page_other_item = PageOtherItem::where('id',1)->first();

        $detail = PackagePurchase::with('rPackage')
            ->where('user_id', $user_data->id)
            ->where('currently_active', 1)
            ->first();

        if($detail == null) {
            return Redirect()->route('customer_package')->with('error', ERR_ENROLL_PACKAGE_FIRST);
        }

        // Date Over Check
        $today = date('Y-m-d');
        $expire_date = $detail->package_end_date;
        if($today > $expire_date) {
            return Redirect()->route('customer_package')->with('error', ERR_PROPERTY_DATE_EXPIRED);
        }


        $wishlist = Wishlist::where('user_id', $user_data->id)->orderBy('id', 'asc')->paginate(10);
        return view('front.customer_wishlist', compact('g_setting','wishlist','page_other_item'));
    }

    public function view_direct(){
        $page_other_item = PageOtherItem::where('id',1)->first();
       $view_direct = User::where('sponsor_id',Auth::id())->get();
       return view('front.view_direct', compact('view_direct','page_other_item'));
    }


    public function view_downline(){
        $page_other_item = PageOtherItem::where('id',1)->first();
        $userId = Auth::id(); // or use a specific user ID
        $user = User::find($userId);

        // Fetch direct children
        // $children = $user->children()->get();
        $children = $user->children()->with('parent')->get();
        
        // Fetch all descendants of the children
        $descendants = $this->getDescendants($children);

        // Concatenate both collections
        $view_downline = $children->concat($descendants);
        
        return view('front.view_downline', compact('view_downline','page_other_item'));
    }

   
    private function getDescendants($users)
    {
        $descendants = collect();

        foreach ($users as $user) {
            // Add current user's children
            $descendants = $descendants->concat($user->children);

            // Recursively add descendants of the current user's children
            $descendants = $descendants->concat($this->getDescendants($user->children));
        }

        return $descendants;
    }
  

    

}
