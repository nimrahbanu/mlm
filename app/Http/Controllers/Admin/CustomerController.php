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
            $active_users = User::where('is_active', 1)
            ->where('is_green', 1)
            ->where('status', 'Active')
            ->whereNull('deleted_at')
            ->where('package_id', 2)
            ->orderBy('activated_date')
            ->pluck('user_id')
            ->toArray();
            $users_with_exactly_three_helps = HelpStar::select('receiver_id')
                ->whereIn('receiver_id', $active_users)
                ->groupBy('receiver_id')
                ->havingRaw('COUNT(*) < 3')
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
                $data =   User::where('is_active', 1)
                ->where('is_green', 1)
                ->where('status', 'Active')
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
        
            // Initialize arrays for different positions
            $helpReceivedCounts = $silver = $gold = $platinum = $ruby = $emrald = $diamond = [];
        
            // Populate the arrays based on the help data
            foreach ($data as $id) {
                $helpReceivedCounts[$id] = isset($helpData[$id]) ? $helpData[$id]->where('receiver_position', 2)->sum('count') : 0;
                $silver[$id] = isset($helpData[$id]) ? $helpData[$id]->where('receiver_position', 3)->sum('count') : 0;
                $gold[$id] = isset($helpData[$id]) ? $helpData[$id]->where('receiver_position', 4)->sum('count') : 0;
                $platinum[$id] = isset($helpData[$id]) ? $helpData[$id]->where('receiver_position', 5)->sum('count') : 0;
                $ruby[$id] = isset($helpData[$id]) ? $helpData[$id]->where('receiver_position', 6)->sum('count') : 0;
                $emrald[$id] = isset($helpData[$id]) ? $helpData[$id]->where('receiver_position', 7)->sum('count') : 0;
                $diamond[$id] = isset($helpData[$id]) ? $helpData[$id]->where('receiver_position', 8)->sum('count') : 0;
            }
        
            // Retrieve the last processed user ID from Redis
            $lastUserId = Redis::get('last_user_id');
            $third_level_last_user_id = Redis::get('third_level_last_user_id');
        
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
                'third_level_last_user_id' =>$third_level_last_user_id
            ];
            // $active_users =   HelpStar::select('receiver_id')->count(3);

            // $active_users =   $active_users ?  $active_users : ['PHC123456'];
            $active_users = User::where('is_active', 1)
            ->where('is_green', 1)
            ->where('status', 'Active')
            ->whereNull('deleted_at')
            ->where('package_id', 2)
            ->orderBy('activated_date')
            ->pluck('user_id')
            ->toArray();
        
        // Ensure $active_users is not empty
        if (!empty($active_users)) {
            // Get users whose IDs appear exactly 3 times in the HelpStar table
            $users_with_exactly_three_helps = HelpStar::select('receiver_id')
                ->whereIn('receiver_id', $active_users)
                ->groupBy('receiver_id')
                ->havingRaw('COUNT(*) > 3')
                ->pluck('receiver_id')
                ->toArray();
        } else {
            $users_with_exactly_three_helps = [];
        }
        
        $help_star_records = HelpStar::whereIn('receiver_id', $users_with_exactly_three_helps)->get();
        $sender_id = 'PHC123456';
        $sender_package =  User::where('user_id',$sender_id)->select('package_id')->first();
$receiver = $sender_package->package_id + 1;
        DD($sender_package, $receiver,1);
            // dd($active_users);
            // Return the view with the prepared data
            return view('admin.redis_data_view', compact('success'));
        }
        
        
}
