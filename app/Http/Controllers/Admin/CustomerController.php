<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Property;
use App\Models\PackagePurchase;
use App\Models\Review;
use App\Models\Payment;
use Illuminate\Http\Request;
use App\Models\HelpStar;
use App\Models\HelpSilver;
use App\Models\HelpGold;
use App\Models\HelpPlatinum;
use App\Models\HelpRuby;
use App\Models\HelpEmrald;
use App\Models\SevenLevelTransaction;

use App\Models\HelpDiamond;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use DB;
Use Auth;
use Hash;
use Illuminate\Support\Facades\Redis;

class CustomerController extends Controller
{
    public function __construct() {
        $this->middleware('auth.admin:admin');
    }
    public function admin_direct_view(Request $request) 
    {
        // Start with a base query
        $query = User::query();

        // Apply filters dynamically based on request parameters

        // Filter by fromDate
        if ($request->has('fromDate') && !empty($request->fromDate)) {
            $query->where('created_at', '>=', $request->fromDate);
        }

        // Filter by toDate
        if ($request->has('toDate') && !empty($request->toDate)) {
            $query->where('created_at', '<=', $request->toDate);
        }

        // Filter by memberID
        if ($request->has('SponsorID') && !empty($request->SponsorID)) {
            $query->where('sponsor_id', $request->SponsorID);

        }

        // Filter by status
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        // Get the results
        $customers = $query->orderby('created_at','DESC')->get();

        // Return the view with the results
        return view('admin.admin_direct_view', compact('customers'));
    }
    
    public function admin_downline_view(Request $request) 
    {
        // Start with a base query
        $query = User::query();

        // Apply filters dynamically based on request parameters

        // Filter by fromDate
        if ($request->has('fromDate') && !empty($request->fromDate)) {
            $query->where('created_at', '>=', $request->fromDate);
        }

        // Filter by toDate
        if ($request->has('toDate') && !empty($request->toDate)) {
            $query->where('created_at', '<=', $request->toDate);
        }

        // Filter by memberID
        if ($request->has('SponsorID') && !empty($request->SponsorID)) {
            $sponsorId = $request->SponsorID;
            // Get all users with the given sponsor_id
            $directMembers = User::where('sponsor_id', $sponsorId)->pluck('id');
            
            // Initialize an array to hold all member IDs
            $allMemberIds = $directMembers->toArray();
            
            // Recursive function to get all downline members
            $this->getDownlineMembers($directMembers, $allMemberIds);
            
            // Apply the filter to the main query
            $query->whereIn('id', $allMemberIds);
        }

        // Filter by status
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        // Get the results
        $customers = $query->orderby('created_at','DESC')->get();

        // Return the view with the results
        return view('admin.admin_downline_view', compact('customers'));
    }

    public function index(Request $request) 
    {
        // Start with a base query
        $query = User::query();

        // Apply filters dynamically based on request parameters

        // Filter by fromDate
        if ($request->has('fromDate') && !empty($request->fromDate)) {
            $query->where('created_at', '>=', $request->fromDate);
        }

        // Filter by toDate
        if ($request->has('toDate') && !empty($request->toDate)) {
            $query->where('created_at', '<=', $request->toDate);
        }

        // Filter by memberID
        if ($request->has('memberID') && !empty($request->memberID)) {
            $query->where('user_id', $request->memberID);
        }

        // Filter by status
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        // Get the results
        $customers = $query->orderby('created_at','DESC')->get();

        // Return the view with the results
        return view('admin.customer_view', compact('customers'));
    }


    public function detail($id) {
        $customer_detail = User::where('id',$id)->first();
        return view('admin.customer_detail', compact('customer_detail'));
    }
    public function edit_customer(Request $request,$id)
    {

        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }

        $obj = User::findOrFail($id);
        $data = $request->only($obj->getFillable());
        $request->validate([
            'email'   =>  [
                'required',
                'email',
                Rule::unique('users')->ignore($id),
            ]
        ],[
            'email.required' => ERR_EMAIL_REQUIRED,
            'email.email' => ERR_EMAIL_INVALID,
            'email.unique' => ERR_EMAIL_EXIST
        ]);
        $data['password'] =  Hash::make($request->password);
        $obj->fill($data)->save();
        return redirect()->back()->with('success', SUCCESS_PROFILE_UPDATE);
    }
    public function edit_customer_status(Request $request,$id)
    {

        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }
        
        $obj = User::findOrFail($id);
        $obj->is_active = $request->is_active;
        $obj->is_green = $request->is_green;
        $obj->status = $request->status;
        $obj->activated_date = $request->activated_date;
        $obj->green_date = $request->green_date;
        $obj->package_id = $request->package_id;
        $obj->save();
        return redirect()->back()->with('success', 'SUCCESS_PROFILE_UPDATE');
    }


    
    public function destroy($id) {

        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }

        // Before deleting, check this customer is used in another table
        $cnt = Property::where('admin_id',0)->where('user_id',$id)->count();
        if($cnt>0) {
            return redirect()->back()->with('error', ERR_ITEM_DELETE);
        }

        $cnt1 = PackagePurchase::where('user_id',$id)->count();
        if($cnt1>0) {
            return redirect()->back()->with('error', ERR_ITEM_DELETE);
        }

        $cnt2 = Review::where('agent_id',$id)->where('agent_type','Customer')->count();
        if($cnt2>0) {
            return redirect()->back()->with('error', ERR_ITEM_DELETE);
        }

        User::where('id', $id)->delete();
        return Redirect()->back()->with('success', SUCCESS_ACTION);
    }

    public function change_status(Request $request, $id) {
        $customer = User::find($id);
            $customer->status = $request->status;
            $message=SUCCESS_ACTION;
            $customer->save();
        return response()->json($message);
    }

    public function active_date_status(Request $request, $id) {
        $customer = User::find($id);
            $customer->is_active = $request->is_active;
            $message=SUCCESS_ACTION;
            $customer->save();
        return response()->json($message);
    }

    public function joining_date_status(Request $request, $id) {
        $customer = User::find($id);
            $customer->is_green = $request->is_green;
            $message=SUCCESS_ACTION;
            $customer->save();
        return response()->json($message);
    }

    private function getDownlineMembers($directMembers, &$allMemberIds)
    {
        // Get all users with sponsor_id in the list of directMembers
        $downlineMembers = User::whereIn('sponsor_id', $directMembers)->pluck('id');
    
        // If there are downline members, process them
        if ($downlineMembers->isNotEmpty()) {
            // Add downline members to the allMemberIds array
            $allMemberIds = array_merge($allMemberIds, $downlineMembers->toArray());
    
            // Recurse to get further downline members
            $this->getDownlineMembers($downlineMembers, $allMemberIds);
        }
    }

    public function admin_activate_member(Request $request){
        return view('admin.admin_activate_member');
    }
    // YourController.php
    public function getMemberName(Request $request)
    {
        $memberId = $request->input('member_id');
        // Fetch the member's name from the database
        $member = User::find($memberId);
        
        if ($member) {
            return response()->json(['name' => $member->name]);
        } else {
            return response()->json(['name' => 'Member not found'], 404);
        }
    }

    public function activate_member(Request $request)
    {
        $memberId = $request->input('member_id');
     
        $member = User::find($memberId);
        if($member){
            if($member->is_green == '0'){
                $member->is_green ='1';
                $member->green_date =now();
                $member->save();
                return redirect()->back()->with('success', 'Activate Successfully');
            }else{
                return redirect()->back()->with('error', 'Already Activated');
    
            }
        }else{
            return redirect()->back()->with('error', 'User not found');

        }
    }

    public function admin_commitment_history(Request $request){
        $query = Payment::query();

        // Apply filters dynamically based on request parameters

        // Filter by fromDate
        if ($request->has('fromDate') && !empty($request->fromDate)) {
            $query->where('created_at', '>=', $request->fromDate);
        }

        // Filter by toDate
        if ($request->has('toDate') && !empty($request->toDate)) {
            $query->where('created_at', '<=', $request->toDate);
        }
        // Filter by memberID
        if ($request->has('id') && !empty($request->id)) {
            $query->where('sender', $request->id);

        }
        // Get the results
        $customers = $query->orderby('created_at','DESC')->get();

        // Return the view with the results
        return view('admin.admin_commitment_history', compact('customers'));
    }

    public function admin_payment_report_view(Request $request){
        $query = Payment::query();

        // Apply filters dynamically based on request parameters

        // Filter by fromDate
        if ($request->has('fromDate') && !empty($request->fromDate)) {
            $query->where('created_at', '>=', $request->fromDate);
        }

        // Filter by toDate
        if ($request->has('toDate') && !empty($request->toDate)) {
            $query->where('created_at', '<=', $request->toDate);
        }
        // Filter by memberID
        if ($request->has('sender') && !empty($request->sender)) {
            $query->where('sender', $request->sender);
        }
        if ($request->has('receiver') && !empty($request->receiver)) {
            $query->where('receiver', $request->receiver);
        }
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }
        // Get the results
        $customers = $query->orderby('created_at','DESC')->get();

        // Return the view with the results
        return view('admin.admin_payment_report_view', compact('customers'));
    }
    public function view_sponsor_help(){
        $query = Payment::query();

        // // Apply filters dynamically based on request parameters

        // // Filter by fromDate
        // if ($request->has('fromDate') && !empty($request->fromDate)) {
        //     $query->where('created_at', '>=', $request->fromDate);
        // }

        // // Filter by toDate
        // if ($request->has('toDate') && !empty($request->toDate)) {
        //     $query->where('created_at', '<=', $request->toDate);
        // }
        // // Filter by memberID
        // if ($request->has('sender') && !empty($request->sender)) {
        //     $query->where('sender', $request->sender);
        // }
        // if ($request->has('receiver') && !empty($request->receiver)) {
        //     $query->where('receiver', $request->receiver);
        // }
        // if ($request->has('status') && !empty($request->status)) {
        //     $query->where('status', $request->status);
        // }
        // // Get the results
        $customers = $query->orderby('created_at','DESC')->get();

        // Return the view with the results
        return view('admin.view_sponsor_help', compact('customers'));
    }


    public function active_users(){
        $datas = User::where(['is_active'=>1,'is_green'=>1,'status'=>'Active'])->orderBy('activated_date')->pluck('id')->toArray();
        return $datas;
    }
    public function active_userss()
    {
    // Step 1: Fetch all active users
    $activeUsers = User::where(['is_active' => 1, 'is_green' => 1, 'status' => 'Active'])
                        ->orderBy('activated_date')
                        ->get();

        // Step 1: Get all user IDs who are active, green, and in the 'star' package
        $starUserIds = User::where(['is_active' => 1, 'is_green' => 1, 'status' => 'Active', 'package' => 'star'])
        ->orderBy('activated_date')
        ->pluck('id')
        ->toArray();

        // Step 2: Get the last paid user's ID from a configuration or a table (for simplicity, assume it's stored in a variable)
        $lastPaidUserId = Setting::get('last_paid_user_id');

        // Step 3: Find the index of the last paid user in the list
        $lastPaidIndex = array_search($lastPaidUserId, $starUserIds);

        // Step 4: Determine the next user in line
        if ($lastPaidIndex === false || $lastPaidIndex === count($starUserIds) - 1) {
        // If lastPaidUserId is not found or it's the last user in the list, start from the beginning
            $nextReceiver = User::find($starUserIds[0]);
        } else {
        // Otherwise, pick the next user in line
            $nextReceiver = User::find($starUserIds[$lastPaidIndex + 1]);
        }

        // Step 5: Process the payment for the next receiver
        if ($nextReceiver) {
        // Assuming the new user pays 300 rupees
        $paymentAmount = 300;

        // Update the receiver's payment count and help amount
        $nextReceiver->received_payments_count += 1;
        $nextReceiver->help += $paymentAmount;
        $nextReceiver->save();

        // Update the last paid user ID in the settings table
        Setting::set('last_paid_user_id', $nextReceiver->id);

        // Perform any additional updates or notifications
        }

        // 2. Storing the last_user_id in Redis
        // After determining the next receiver
        $nextReceiverId = $nextReceiver->id;

        // Store the last user ID in Redis
        Redis::set('last_paid_user_id', $nextReceiverId);

        // 3. Retrieving the last_user_id from Redis
        $lastPaidUserId = Redis::get('last_paid_user_id');

        // If it's null, you might want to initialize it
        if (is_null($lastPaidUserId)) {
            $lastPaidUserId = 0; // Or any default value
        }

        // 4. Incrementing or Cycling Through User IDs

        $datas = User::where(['is_active'=>1,'is_green'=>1,'status'=>'Active','package'=>'star'])->orderBy('activated_date')->pluck('id')->toArray();

        // Retrieve the last user ID from Redis
        $lastPaidUserId = Redis::get('last_paid_user_id');

        // Find the index of the last paid user
        $currentIndex = array_search($lastPaidUserId, $datas);

        // Determine the next user in the list
        $nextIndex = ($currentIndex === false || $currentIndex === (count($datas) - 1)) ? 0 : $currentIndex + 1;
        $nextReceiverId = $datas[$nextIndex];

        // Store the next user ID in Redis
        Redis::set('last_paid_user_id', $nextReceiverId);

        // Now you have the next receiver ID to use in your logic
    
    
        // 5. TTL (Optional)
        // If you want the key to expire after a certain amount of time (e.g., for temporary values), you can set a TTL (time to live):
   
        // Store the key with a TTL of 1 hour (3600 seconds)
        Redis::setex('last_paid_user_id', 3600, $nextReceiverId);
    
    }

    public function get_sponser($id) {
            $user = User::with('sponsor')->where('user_id', $id)->first();
            $adminId = 'PHC123456'; // Admin ID if a sponsor is not found
            $sponsorIds = [];
            
            for ($i = 0; $i < 7; $i++) {
                if ($user && $user->sponsor) {
                    $sponsorIds[] = $user->sponsor->user_id;
                    $user = $user->sponsor;
                } else {
                    $sponsorIds[] = $adminId;
                    $user = null; // Exit if no further sponsors are found
                }
            }
        
            $success = [
                'first' => $sponsorIds[0],
                'second' => $sponsorIds[1],
                'third' => $sponsorIds[2],
                'fourth' => $sponsorIds[3],
                'fifth' => $sponsorIds[4],
                'sixth' => $sponsorIds[5],
                'seventh' => $sponsorIds[6],
            ];
        
            return $success;
        }
        public function redis_view_old(){
            // Retrieve user IDs based on the conditions
            $data = User::where('is_active', 1)
                ->where('is_green', 1)
                ->where('status', 'Active')
                ->whereNull('deleted_at')
                ->where('package_id', '>=', 2)
                ->orderBy('activated_date')
                ->pluck('user_id')
                ->toArray();
        
            // Initialize an array to store help received count
            $helpReceivedCounts = [];
        
            // Loop through each user ID and calculate the help received count
            foreach ($data as $id) {
                $helpReceivedCounts[$id] = HelpStar::where('receiver_id', $id)
                ->where('receiver_position', 2)
                ->count();

                $silver[$id] = HelpStar::where('receiver_id', $id)
                ->where('receiver_position', 3)
                ->count();

                $gold[$id] = HelpStar::where('receiver_id', $id)
                ->where('receiver_position', 4)
                ->count();

                $platinum[$id] = HelpStar::where('receiver_id', $id)
                ->where('receiver_position', 5)
                ->count();

                $ruby[$id] = HelpStar::where('receiver_id', $id)
                ->where('receiver_position', 6)
                ->count();

                $emrald[$id] = HelpStar::where('receiver_id', $id)
                ->where('receiver_position', 7)
                ->count();

                $diamond[$id] = HelpStar::where('receiver_id', $id)
                ->where('receiver_position', 8)
                ->count();

            }
        
            // Retrieve the last processed user ID from Redis
            $lastUserId = Redis::get('last_user_id');
        
            // Prepare the data to be passed to the view
            $success['arraydata'] = $data;
            $success['lastUserId'] = $lastUserId;
            $success['helpReceivedCounts'] = $helpReceivedCounts;
            $success['silver'] = $silver;
            $success['gold'] = $gold;
            $success['platinum'] = $platinum;
            $success['ruby'] = $ruby;
            $success['emrald'] = $emrald;
            $success['diamond'] = $diamond;
        
            // Return the view with the prepared data
            return view('admin.redis_data_view', compact('success'));
        }
        public function redis_view() {
            $active_users = User::where('is_green', 1)
            ->where('status', 'Active')
            ->whereNull('deleted_at')
            // ->where('package_id', 2)
            ->pluck('user_id')
            ->toArray();
            $lastUserId = Redis::get('last_user_id');
            $second = Redis::get('silver_level_user_id');
            $third = Redis::get('gold_level_last_user_id');
            $four = Redis::get('platinum_level_last_user_id');
            $five = Redis::get('ruby_level_last_user_id');
            $six = Redis::get('emrald_level_last_user_id');
            $seven = Redis::get('diamond_level_last_user_id');

             
            // $success = [
            //     'active_users' => $active_users,
            //     'lastUserId' => $lastUserId,
            //     'second' => $second,
            //     'third' => $third,
            //     'four' => $four,
            //     'five' => $five,
            //     'six' => $six,
            //     'seven' => $seven,
            // ];
            // // dd($success);
            // return view('admin.redis_data_view', compact('success'));
            $users_with_exactly_three_helps = HelpStar::select('receiver_id')
                ->whereIn('receiver_id', $active_users)
                ->groupBy('receiver_id')
                ->havingRaw('COUNT(*) < 4')
                ->pluck('receiver_id')
                ->toArray();
        // DD($users_with_exactly_three_helps,$active_users);
         
            $third_level_users =   User::where('is_active', 1)
            ->where('is_green', 1)
            ->where('status', 'Active')
            ->whereNull('deleted_at')
            ->where('package_id', 3)
            ->orderBy('activated_date')
            ->pluck('user_id')
            ->toArray();
                $data =   User::where('status', 'Active')
                ->whereNull('deleted_at')
                ->where('package_id','>=', 2)
                ->orderBy('activated_date')
                ->pluck('user_id')
                ->toArray();
            // Retrieve all help data in one query, grouped by receiver_id and receiver_position
            $helpData = HelpStar::whereIn('receiver_id', $data)
                ->select('receiver_id', 'receiver_position', DB::raw('count(*) as count'))
                ->groupBy('receiver_id', 'receiver_position')
                ->get()
                ->groupBy('receiver_id');

                $HelpSilver = HelpStar::whereIn('receiver_id', $data)
                ->select('receiver_id', 'receiver_position', DB::raw('count(*) as count'))
                ->groupBy('receiver_id', 'receiver_position')
                ->get()
                ->groupBy('receiver_id');

                $HelpGold = HelpStar::whereIn('receiver_id', $data)
                ->select('receiver_id', 'receiver_position', DB::raw('count(*) as count'))
                ->groupBy('receiver_id', 'receiver_position')
                ->get()
                ->groupBy('receiver_id');

                $HelpPlatinum = HelpStar::whereIn('receiver_id', $data)
                ->select('receiver_id', 'receiver_position', DB::raw('count(*) as count'))
                ->groupBy('receiver_id', 'receiver_position')
                ->get()
                ->groupBy('receiver_id');

                $HelpRuby = HelpStar::whereIn('receiver_id', $data)
                ->select('receiver_id', 'receiver_position', DB::raw('count(*) as count'))
                ->groupBy('receiver_id', 'receiver_position')
                ->get()
                ->groupBy('receiver_id');

                $HelpEmrald = HelpStar::whereIn('receiver_id', $data)
                ->select('receiver_id', 'receiver_position', DB::raw('count(*) as count'))
                ->groupBy('receiver_id', 'receiver_position')
                ->get()
                ->groupBy('receiver_id');

                $HelpDiamond = HelpStar::whereIn('receiver_id', $data)
                ->select('receiver_id', 'receiver_position', DB::raw('count(*) as count'))
                ->groupBy('receiver_id', 'receiver_position')
                ->get()
                ->groupBy('receiver_id');
        
            // Initialize arrays for different positions
            $helpReceivedCounts = $silver = $gold = $platinum = $ruby = $emrald = $diamond = [];
        
            // Populate the arrays based on the help data
            foreach ($data as $id) {
                $helpReceivedCounts[$id] = isset($helpData[$id]) ? $helpData[$id]->where('receiver_position', 2)->sum('count') : 0;
                $silver[$id] = isset($HelpSilver[$id]) ? $HelpSilver[$id]->where('receiver_position', 3)->sum('count') : 0;
                $gold[$id] = isset($HelpGold[$id]) ? $HelpGold[$id]->where('receiver_position', 4)->sum('count') : 0;
                $platinum[$id] = isset($HelpPlatinum[$id]) ? $HelpPlatinum[$id]->where('receiver_position', 5)->sum('count') : 0;
                $ruby[$id] = isset($HelpRuby[$id]) ? $HelpRuby[$id]->where('receiver_position', 6)->sum('count') : 0;
                $emrald[$id] = isset($HelpEmrald[$id]) ? $HelpEmrald[$id]->where('receiver_position', 7)->sum('count') : 0;
                $diamond[$id] = isset($HelpDiamond[$id]) ? $HelpDiamond[$id]->where('receiver_position', 8)->sum('count') : 0;
            }
        
            // Retrieve the last processed user ID from Redis
            $lastUserId = Redis::get('last_user_id');
            $third_level_last_user_id = Redis::get('third_level_last_user_id');
            $a1 = Redis::get('last_user_id');
            $a2 = Redis::get('silver_level_user_id');
            $a3 = Redis::get('gold_level_last_user_id');
            $a4 = Redis::get('platinum_level_last_user_id');
            $a5 = Redis::get('ruby_level_last_user_id');
            $a6 = Redis::get('emrald_level_last_user_id');
            $a7 = Redis::get('diamond_level_last_user_id');

            // Prepare the data to be passed to the view
            $success = [
                'arraydata' => $data,
                'lastUserId' => $lastUserId,
                'helpReceivedCounts' => $helpReceivedCounts,
                'silver' => $silver,
                'gold' => $gold,
                'platinum' => $platinum,
                'ruby' => $ruby,
                'emrald' => $emrald,
                'diamond' => $diamond,
                'third_level_users' =>$third_level_users,
                'third_level_last_user_id' =>$third_level_last_user_id,
                'a1' =>$a1,
                'a2' =>$a2,
                'a3' =>$a3,
                'a4' =>$a4,
                'a5' =>$a5,
                'a6' =>$a6,
                'a7' =>$a7,
            ];
            return view('admin.redis_data_view', compact('success'));
            // $active_users =   HelpStar::select('receiver_id')->count(3);

            // $active_users =   $active_users ?  $active_users : ['PHC123456'];
        //     $active_users = User::where('is_active', 1)
        //     ->where('is_green', 1)
        //     ->where('status', 'Active')
        //     ->whereNull('deleted_at')
        //     ->where('package_id', 2)
        //     ->orderBy('activated_date')
        //     ->pluck('user_id')
        //     ->toArray();
        
        // // Ensure $active_users is not empty
        // if (!empty($active_users)) {
        //     // Get users whose IDs appear exactly 3 times in the HelpStar table
        //     $users_with_exactly_three_helps = HelpStar::select('receiver_id')
        //         ->whereIn('receiver_id', $active_users)
        //         ->groupBy('receiver_id')
        //         ->havingRaw('COUNT(*) > 3')
        //         ->pluck('receiver_id')
        //         ->toArray();
        // } else {
        //     $users_with_exactly_three_helps = [];
        // }
        
        // $help_star_records = HelpStar::whereIn('receiver_id', $users_with_exactly_three_helps)->get();

        // DD($help_star_records);
            // dd($active_users);
            // Return the view with the prepared data
      
      
      
            // $active_users = User::where('is_active', 1)
            // ->where('is_green', 1)
            // ->where('status', 'Active')
            // ->whereNull('deleted_at')
            // ->where('package_id', 2)
            // ->orderBy('activated_date')
            // ->limit(10) // Apply limit early
            // ->pluck('user_id') // Fetch only user_id
            // ->toArray();
        
      }
      public function test(Request $request){
    
        $sponsor_ids = User::where('is_green', '1')
        ->where('status', 'InActive')
        ->pluck('sponsor_id'); // Get only the sponsor_id values

      
          // Update users with those sponsor_ids to active 
          // Updating User Statuses
          User::whereIn('user_id', $sponsor_ids)
              ->update(['status' => 'active']);
     
         

          $levels = [
              'first_level',
              'second_level',
              'third_level',
              'fourth_level',
              'five_level',
              'six_level',
              'seven_level',
          ];
  
          // Fetch user IDs that are inactive and not green
          $user_ids = User::where('is_green', '0')
              ->where('status', 'InActive')
              ->pluck('user_id');
            
          // Get users who have given help and confirmed it
          $giving_users = HelpStar::whereIn('sender_id', $user_ids)
              ->whereNotNull('confirm_date')
              ->pluck('sender_id'); // Fetch sender IDs
             
  
          // Iterate through levels and update users accordingly
       
        foreach ($giving_users as $user_id) {
            
            // Check if the user has confirmed transactions for all levels
            $confirmedLevelsCount = SevenLevelTransaction::where('sender_id', $user_id)
            ->whereNotNull($levels[0] . '_confirm_date')
            ->whereNotNull($levels[1] . '_confirm_date')
            ->whereNotNull($levels[2] . '_confirm_date')
            ->whereNotNull($levels[3] . '_confirm_date')
            ->whereNotNull($levels[4] . '_confirm_date')
            ->whereNotNull($levels[5] . '_confirm_date')
            ->whereNotNull($levels[6] . '_confirm_date')
            ->count();
            if ($confirmedLevelsCount === 1) {
           
                User::where('user_id', $user_id)
                    ->update(['is_green' => 1]);
            }
        }

        //   foreach ($giving_users as $user_id) {
        //       // Check if the user has confirmed transactions for all levels
        //       $allLevelsConfirmed = true; // Assume true initially
      
        //       foreach ($levels as $level) {
        //           // Check if there is a confirm date for this level for the current user
        //           $levelConfirmed = SevenLevelTransaction::where('sender_id', $user_id)
        //               ->whereNotNull($level . '_confirm_date')
        //               ->exists(); // Use exists to check for presence
      
        //           // If any level is not confirmed, set flag to false and break
        //           if (!$levelConfirmed) {
        //               $allLevelsConfirmed = false;
        //               break; // No need to check further levels for this user
        //           }
        //       }
        //       if ($allLevelsConfirmed) {
        //           User::where('user_id', $user_id)
        //               ->update(['is_green' => 1]);
        //       }
        //   }

        // $sponsor_ids = User::where('is_green', '1')
        //   ->where('status', 'InActive')
        //   ->pluck('sponsor_id'); // Get only the sponsor_id values
        //     // Log the sponsor IDs
        
        //     // Update users with those sponsor_ids to active 
        //     // Updating User Statuses
        //   $a =  User::whereIn('user_id', $sponsor_ids)
        //         ->update(['status' => 'active']);
         

        //     $levels = [
        //         'first_level',
        //         'second_level',
        //         'third_level',
        //         'fourth_level',
        //         'five_level',
        //         'six_level',
        //         'seven_level',
        //     ];
    
            // Fetch user IDs that are inactive and not green
            $user_ids = User::where('is_green', '0')
                ->where('status', 'InActive')
                ->pluck('user_id');

    
            // Get users who have given help and confirmed it
            $giving_users = HelpStar::whereIn('sender_id', $user_ids)
                ->whereNotNull('confirm_date')
                ->pluck('sender_id'); // Fetch sender IDs
            dd(  '....',$giving_users,'...');

            foreach ($giving_users as $user_id) {
                // Check if the user has confirmed transactions for all levels
                $allLevelsConfirmed = true; // Assume true initially
        
                foreach ($levels as $level) {
                    // Check if there is a confirm date for this level for the current user
                    $levelConfirmed = SevenLevelTransaction::where('sender_id', $user_id)
                        ->whereNotNull($level . '_confirm_date')
                        ->exists(); // Use exists to check for presence
            dd(  $levelConfirmed);
                }}
            // Iterate through levels and update users accordingly
            // foreach ($levels as $level) {
            //     foreach ($giving_users as $user_id) {
            //         $seven_level_users = SevenLevelTransaction::where('sender_id', $user_id)
            //             ->whereNotNull($level . '_confirm_date')
            //             ->pluck('sender_id');
    
            //         // Update users to is_green = 1 if they qualify
            //         if ($seven_level_users->isNotEmpty()) {
            //             User::where('user_id', $user_id)
            //                 ->update(['is_green' => 1]);
            //             Log::info('User statuses updated successfully for is_green.',$seven_level_users->toArray());
            //         }
            //     }
            // }

            foreach ($giving_users as $user_id) {
                // Check if the user has confirmed transactions for all levels
                $allLevelsConfirmed = true; // Assume true initially
        
                foreach ($levels as $level) {
                    // Check if there is a confirm date for this level for the current user
                    $levelConfirmed = SevenLevelTransaction::where('sender_id', $user_id)
                        ->whereNotNull($level . '_confirm_date')
                        ->exists(); // Use exists to check for presence
            dd(  $levelConfirmed);
        
                    // If any level is not confirmed, set flag to false and break
                    if (!$levelConfirmed) {
                        $allLevelsConfirmed = false;
                        break; // No need to check further levels for this user
                    }
                }
                if ($allLevelsConfirmed) {
                    User::where('user_id', $user_id)
                        ->update(['is_green' => 1]);
                }
            }


        $user_id = 'PHC123456';
        $userData = User::where('user_id',$user_id);
        $user = $userData->with('package:id,package_name')
                ->first(); 
        $active = $userData->where('is_active',0)->whereNotNull('status')->whereNotNull('activated_date')->first();
        if (!$userData) {
            return $this->sendError('User not found.');
        }
        $restricted_user_ids = [
            'PHC123456', 'PHC674962', 'PHC636527', 'PHC315968', 'PHC985875', 
            'PHC746968', 'PHC666329', 'PHC415900', 'PHC173882', 'PHC571613', 
            'PHC663478', 'PHC875172'
        ];
        
      
        if (!in_array($user_id, $restricted_user_ids)) {
            $seven_level_transaction = $this->seven_level_transaction($user_id);
            $giving_help = $this->giving_help($user_id);
        } else {
            // Handle the case when the user_id is restricted (optional)
            $seven_level_transaction = null; // or you can return an error message, e.g., $this->sendError('Restricted user.');
            $giving_help = null; // or you can return an error message, e.g., $this->sendError('Restricted user.');
        }
        // return $seven_level_transaction;
        // $taking_help_n = $this->taking_help_n($user_id); // Newly added function
        // $taking_transaction = $this->taking_transaction($user_id); // Newly added function
        $success = [
            'user' => $user->only(['id','user_id', 'name', 'activated_date', 'created_at', 'package_id']),
            'package_name' => $user->package ? $user->package->package_name : null,
            'direct_team' => User::where('sponsor_id', $user_id)->count(),
            'total_team' => $user->getTotalDescendantCount(),
            'referral_link' => url('api/customer/registration/' . $user_id),
            'giving_help' => $giving_help,
            'seven_level_transaction' => $seven_level_transaction,
            // 'taking_help' => $taking_help_n,
            // 'taking_seven_level_transaction' => $taking_transaction, // Newly added key
            'taking_sponcer' => 0,
            'e_pin' => EPinTransfer::where('member_id', $user_id)->where('is_used', '0')->count(),
            'news' => News::where('status', 'Active')->select('news_title','news_content','news_order')->orderBy('news_order')->get()
        ];
        if($user){
            return $success;
        }
    }
        
}
