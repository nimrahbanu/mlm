<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Api\BaseController as BaseController;
use App\Http\Controllers\Controller;
use App\Models\LanguageMenuText;
use App\Models\LanguageNotificationText;
use App\Models\LanguageWebsiteText;
use App\Models\User;
use App\Models\Bank;
use App\Models\Payment;
use App\Models\Package;
use App\Models\HelpStar;
use App\Models\Faq;
use App\Models\Support;
use App\Models\EmailTemplate;
use App\Models\PageOtherItem;
use App\Models\EPinTransfer;
use App\Models\SevenLevelTransaction;
use App\Mail\RegistrationEmailToCustomer;
use App\Mail\ResetPasswordMessageToCustomer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use DB;
use Hash;
use Auth;
use Validator;
use Illuminate\Support\Facades\Mail;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Redis;
class CustomerAuthController extends BaseController
{
    protected $transactionService;
    // public function __construct(TransactionService $transactionService)
    // {
    //     $this->transactionService = $transactionService;
    //     $this->middleware('guest:web')->except('logout');

    // }
    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;

        // Apply middleware to restrict access to authenticated users only
        // 'guest:web' is typically used to restrict access to guests
        $this->middleware('guest:web')->except('logout');
    }

    public function basepath(){
        $basePath = rtrim(public_path(), DIRECTORY_SEPARATOR);
        // $basePath = 'http://192.168.29.89/astroweds/api/public';
        //  $user['path'] =  str_replace('\\', '/', $basePath . DIRECTORY_SEPARATOR . 'cms-images' . DIRECTORY_SEPARATOR . 'user-images' . DIRECTORY_SEPARATOR);
        $finallPath =  str_replace('\\', '/', $basePath);
        return $finallPath;
    }

    public function login() {
    	return view('front.user.customer_login');
    }

    public function login_store(Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ],[
            'email.required' => ERR_EMAIL_REQUIRED,
            'email.email' => ERR_EMAIL_INVALID,
            'password.required' => ERR_PASSWORD_REQUIRED
        ]);
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());
        }
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $user = Auth::user();
            $success['token'] = $user->createToken('MyApp')->accessToken;
            $success['id'] = $user->id;
            $success['name'] = $user->name;
            return $this->sendResponse($success, 'User login successfully.');
        } else {
            return $this->sendError('Customer not found');

        }
    }
   
    
    public function logout() {
        Auth::guard('web')->logout();
        return $this->sendResponse(true, 'Logout successfully.');

    }

    public function registration_store(Request $request) {


        $token = hash('sha256',time());
       
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            // 'email' => 'required|email|unique:users',
            'password' => 'required',
            're_password' => 'required|same:password',
            'sponsor_id' => 'required|exists:users,user_id',
            'phone' => 'required|numeric', // Example for a 10-digit phone number
            // 'phone' => 'required|numeric|unique:users,phone', // Example for a 10-digit phone number
            "phone_pay_no" => "required|numeric",
            "confirm_phone_pay_no"=>"required|same:phone_pay_no",
            "registration_code" => "required|unique:users,registration_code"
        ], [
            'name.required' => ERR_NAME_REQUIRED,
            'email.required' => ERR_EMAIL_REQUIRED,
            'email.email' => ERR_EMAIL_INVALID,
            'password.required' => ERR_PASSWORD_REQUIRED,
            're_password.required' => ERR_RE_PASSWORD_REQUIRED,
            're_password.same' => ERR_PASSWORDS_MATCH,
            'registration_code.required' => 'The registration code is required.',
            'registration_code.unique' => 'The registration code has already been used.',
            'phone.unique' => 'The phone has already been used.'
        ]);
        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }
        // DB::beginTransaction();
        try{

            $sponsor = User::where('user_id',$request->sponsor_id)->first();
            if ($sponsor && $sponsor->is_active == '0') {
                $sponsor->update([
                    'is_active' => '1',
                    // 'package_id' => '1',
                    'activated_date' => now()
                ]);
                if($sponsor->is_green == '1' && 'is_active' == '1'){
                    $sponsor->update([
                        'status' => 'Active',
                        // 'package_id' => '2',
                    ]);
                }
            }
            if ($request->has('registration_code')) {
                $epin = EPinTransfer::where('e_pin', $request->registration_code)
                    ->where('is_used', '0')
                    ->first();

                    if ($epin) {
                        $userId = $this->generateUniqueUserId();
                        $epin->is_used = '1';
                        $epin->save();
                        $data = $request->only((new User)->getFillable());
                        $data['password'] = Hash::make($request->password);
                        $data['token'] = $token;
                        $data['status'] = 'InActive';
                        $data['sponsor_id'] = $request->sponsor_id ?? 'PHC123456';
                        $data['user_id'] = $userId;
                        $data['package_id'] = 1;

                        $user = User::create($data);

                        if ($user) {
                        $user_id_sender = $user->user_id;
                            // Implement the 7-level transaction logic
                            $levels = [100, 50, 40, 20, 20, 10, 10];
                            $sponsorId = $user->sponsor_id;
                            $userId = $user->user_id;
                            $adminId = 'PHC123456'; // The admin ID to be used if a sponsor is not found
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
                        
                            $sevenLevelTransaction = SevenLevelTransaction::create([
                                'sender_id' => $userId,
                                'first_level' => $sponsorIds[0],
                                'second_level' => $sponsorIds[1],
                                'third_level' => $sponsorIds[2],
                                'fourth_level' => $sponsorIds[3],
                                'five_level' => $sponsorIds[4],
                                'six_level' => $sponsorIds[5],
                                'seven_level' => $sponsorIds[6],
                                'extra_details' => implode(', ', $levels),
                                'status' => '1'
                            ]);
      // -----------------------------------------start code fromhere------------------------------------------------------------
                        $data = $this->active_users();
                        if (!is_array($data) || empty($data)) {
                            $data = ['PHC123456'];
                        }
                        // Retrieve last processed user ID from Redis
                        $lastUserId = Redis::get('last_user_id');
                        $currentUserId = null;
                        $helpReceived_count = 0;

                        if ($lastUserId) {
                            $lastUserIndex = array_search($lastUserId, $data);

                            // Determine the next user in line
                            if ($lastUserIndex !== false && isset($data[$lastUserIndex + 1])) {
                                $currentUserId = $data[$lastUserIndex + 1];
                            } else {
                                // If we're at the end of the list, start from the beginning
                                $currentUserId = $data[0];
                            }
                        } else {
                            // First time, start with the first user in the list
                            $currentUserId = $data[0];
                        }
                        // Retrieve the receiver user and their package details
                        $receiver = User::where('user_id', $currentUserId)->first(); // payment receive
                        $receiverPackage = Package::where('id', $receiver->package_id)->first();
                        // $nextPackage = Package::where('id', ($receiverPackage->package_order + 1))->first();
             
                        // Check how many times this user has received help
                        $helpReceived_count = HelpStar::where('receiver_id', $currentUserId)->where('receiver_position',2)->count();
                        // echo '$receiverPackage->help_count'.$receiverPackage->help_count.'<br>'; 
                        // echo '$helpReceived_count'.$helpReceived_count;
                           // If the user is not eligible for help, redirect the payment to the admin
                           if($helpReceived_count == 2){
                            $this->third_level_users($currentUserId);
                           }
                        if ($helpReceived_count < 3) {  //0 <= 3
                            // Create a new HelpStar entry
                            $data = new HelpStar();
                            $data->sender_id = $userId;
                            $data->receiver_id = $currentUserId;
                            $data->amount = $receiverPackage->help; // Use the help amount from the package
                            $data->sender_position = '1'; 
                            $data->receiver_position = $receiverPackage->id;
                            $data->received_payments_count = 1;
                            $data->commitment_date = now();
                            $data->confirm_date = null;
                            $data->status = 'Pending';
                            $data->save();
                            $help_Received_count_star = HelpStar::where('receiver_id', $currentUserId)->where('receiver_position',2)->count();
                            
                            // If the user has received the maximum number of helps, update their package
                            if ($helpReceived_count+1  == 3) {
                                    $receiver->package_id = 3;
                                    $receiver->save();
                            }
                            // Update the user's received payment count
                            $receiver->received_payments_count = $helpReceived_count + 1;
                            $receiver->save();

                            // Update the last processed user ID for the next call
                        }else{
                            $adminId = 'PHC123456';
                            $data = new HelpStar();
                            $data->sender_id = $user_id_sender;
                            $data->receiver_id = 'PHC123456'; // Payment goes to admin
                            $data->amount = 300; // Use the help amount from the package
                            $data->sender_position =1;
                            $data->receiver_position = 2;
                            $data->received_payments_count = 1;
                            $data->commitment_date = now();
                            $data->confirm_date = null;
                            $data->status = 'Pending';
                            $data->save();   
                        }
                        Redis::set('last_user_id', $currentUserId);
                        $success['user'] =$user;
                        $success['current_transaction'] =$currentUserId;
                        $success['new_transaction'] =$lastUserId;
                            return $this->sendResponse($success, 'User registered successfully');
                        } else {
                            return $this->sendError('Unable to register. Please try again.');
                        }
                    } else {
                        return $this->sendError('Invalid Registration code');
                    }
                } else {
                    return $this->sendError('Registration code not provided.');
                }
            // DB::commit();

        } catch (\Exception $e) {
            // DB::rollBack();
            dd($e);

            return $this->sendError('Unable to register. Please try again.');
        }
    }

    private function generateUniqueUserId() {
        do {
            $userId = 'PHC' . mt_rand(100000, 999999); // Generate a random 6-digit number
        } while (User::where('user_id', $userId)->exists()); // Check if the user_id already exists
    
        return $userId;
    }

    public function active_users() {
        $active_users =   User::where('is_active', 1)
            ->where('is_green', 1)
            ->where('status', 'Active')
            ->whereNull('deleted_at')
            ->where('package_id', 2)
            ->orderBy('activated_date')
            ->pluck('user_id')
            ->toArray();
            // $active_users =   $active_users ?  $active_users : ['PHC123456'];
            return $active_users;
    }

    public function third_level_users($currentUserId) {
        $userId = $currentUserId;
        $third_level_users =   User::where('is_active', 1)
            ->where('is_green', 1)
            ->where('status', 'Active')
            ->whereNull('deleted_at')
            ->where('package_id', 3)
            ->orderBy('activated_date')
            ->pluck('user_id')
            ->toArray();
            if (!is_array($third_level_users) || empty($third_level_users)) {
                $third_level_users = ['PHC123456'];
            }
            // Retrieve last processed user ID from Redis
            $lastUserId = Redis::get('third_level_last_user_id');
            $currentUserId = null;
            $helpReceived_count = 0;

            if ($lastUserId) {
                $lastUserIndex = array_search($lastUserId, $data);

                if ($lastUserIndex !== false && isset($data[$lastUserIndex + 1])) {
                    $currentUserId = $data[$lastUserIndex + 1];
                } else {
                    $currentUserId = $data[0];
                }
            } else {
                $currentUserId = $data[0];
            }
           
    // Retrieve the receiver user and their package details
    $receiver = User::where('user_id', $currentUserId)->first(); // payment receive
    $receiverPackage = Package::where('id', $receiver->package_id)->first();
    $helpReceived_count = HelpStar::where('receiver_id', $currentUserId)->where('receiver_position',3)->count();

    if($helpReceived_count == 8){
        $this->four_level_users($currentUserId);
    }
    if ($helpReceived_count < 9) {  //0 <= 3
        // Create a new HelpStar entry
        $data = new HelpStar();
        $data->sender_id = $userId;
        $data->receiver_id = $currentUserId;
        $data->amount = $receiverPackage->help; // Use the help amount from the package
        $data->sender_position = '2'; 
        $data->receiver_position = $receiverPackage->id;
        $data->received_payments_count = 1;
        $data->commitment_date = now();
        $data->confirm_date = null;
        $data->status = 'Pending';
        $data->save();
        // If the user has received the maximum number of helps, update their package
        if ($helpReceived_count+1  == 9) {
                $receiver->package_id = 4;
                $receiver->save();
        }
        // Update the user's received payment count
        $receiver->received_payments_count = $helpReceived_count + 1;
        $receiver->save();

        // Update the last processed user ID for the next call
    }else{
        $adminId = 'PHC123456';
        $data = new HelpStar();
        $data->sender_id = $userId;
        $data->receiver_id = 'PHC123456'; // Payment goes to admin
        $data->amount = 600; // Use the help amount from the package
        $data->sender_position ='2';
        $data->receiver_position = '3';
        $data->received_payments_count = 1;
        $data->commitment_date = now();
        $data->confirm_date = null;
        $data->status = 'Pending';
        $data->save();   
    }
    Redis::set('last_user_id', $currentUserId);
    $success['user'] =$user;
    $success['current_transaction'] =$currentUserId;
    $success['new_transaction'] =$lastUserId;
    }


    public function forget_password() {
        return view('front.user.customer_forget_password');
    }

    public function forget_password_store(Request $request) {

        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }

        $g_setting = GeneralSetting::where('id', 1)->first();
        $request->validate([
            'email' => 'required|email'
        ],[
            'email.required' => ERR_EMAIL_REQUIRED,
            'email.email' => ERR_EMAIL_INVALID
        ]);

        if($g_setting->google_recaptcha_status == 'Show') {
            $request->validate([
                'g-recaptcha-response' => 'required'
            ], [
                'g-recaptcha-response.required' => ERR_RECAPTCHA_REQUIRED
            ]);
        }

        $check_email = User::where('email',$request->email)->where('status','Active')->first();
        if(!$check_email) {
            return redirect()->back()->with('error', ERR_EMAIL_NOT_FOUND);
        } else {
            $et_data = EmailTemplate::where('id', 7)->first();
            $subject = $et_data->et_subject;
            $message = $et_data->et_content;
            $token = hash('sha256',time());
            $reset_link = url('customer/reset-password/'.$token.'/'.$request->email);
            $message = str_replace('[[reset_link]]', $reset_link, $message);

            $data['token'] = $token;
            User::where('email',$request->email)->update($data);
            Mail::to($request->email)->send(new ResetPasswordMessageToCustomer($subject,$message));
        }
        return redirect()->back()->with('success', SUCCESS_FORGET_PASSWORD_EMAIL_SEND);
    }


    public function reset_password() {

        $page_other_item = PageOtherItem::where('id',1)->first();

        $g_setting = GeneralSetting::where('id', 1)->first();
        $email_from_url = request()->segment(count(request()->segments()));

        $aa = User::where('email', $email_from_url)->first();

        if(!$aa) {
            return redirect()->route('customer_login');
        }

        $expected_url = url('customer/reset-password/'.$aa->token.'/'.$aa->email);
        $current_url = url()->current();
        if($expected_url != $current_url) {
            return redirect()->route('customer_login');
        }
        $email = $aa->email;
        return view('front.user.customer_reset_password', compact('g_setting', 'email', 'page_other_item'));
    }

    public function reset_password_update(Request $request) {

        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }

        $g_setting = GeneralSetting::where('id', 1)->first();
        $request->validate([
            'new_password' => 'required',
            'retype_password' => 'required|same:new_password',
        ], [
            'new_password.required' => ERR_NEW_PASSWORD_REQUIRED,
            'retype_password.required' => ERR_RE_PASSWORD_REQUIRED,
            'retype_password.same' => ERR_PASSWORDS_MATCH
        ]);

        if($g_setting->google_recaptcha_status == 'Show') {
            $request->validate([
                'g-recaptcha-response' => 'required'
            ], [
                'g-recaptcha-response.required' => ERR_RECAPTCHA_REQUIRED
            ]);
        }

        $data['password'] = Hash::make($request->new_password);
        $data['token'] = '';
        User::where('email', $request->current_email)->update($data);
        return redirect()->route('customer_login')->with('success', SUCCESS_RESET_PASSWORD);
    }

    public function dashboard(Request $request){
        $validator = Validator::make($request->all(),[
            'user_id' => 'required|exists:users,user_id',
        ]);
        if($validator->fails()){
            return $this->sendError('Validation Error.',$validator->errors());
        }
       
        $user_id = $request->user_id;
        $userData = User::where('user_id',$user_id);
        $user = $userData->with('package:id,package_name')
                ->first(); 
        $active = $userData->where('is_active',0)->whereNotNull('status')->whereNotNull('activated_date')->first();
        if (!$userData) {
            return $this->sendError('User not found.');
        }
        $success = [
            'user' => $user->only(['id','user_id', 'name', 'activated_date', 'created_at', 'package_id']),
            'package_name' => $user->package ? $user->package->package_name : null,
            'direct_team' => User::where('sponsor_id', $user_id)->count(),
            'total_team' => $user->getTotalDescendantCount(),
            'referral_link' => url('api/customer/registration/' . $user_id),
            'giving_help' => $active ?? $this->giving_help(),
            'taking_help' => 0,
            'taking_sponcer' => 0,
            'e_pin' => EPinTransfer::where('member_id', $user_id)->where('is_used', '0')->count(),
            'news' => Faq::where('status', 'Active')->orderBy('faq_order')->get()
        ];
        if($user){
            return $this->sendResponse($success, 'User Data Retrieve Successfully.');
        }
    }

    private function giving_help() {
  
     $admin =  User::with('bankDetails')->first(); // Check if the user_id already exists
    
        return $admin;
    }

    public function my_profile(Request $request){
        $validator = Validator::make($request->all(),[
            'user_id' => 'required|exists:users,user_id',
        ]);
        if($validator->fails()){
            return $this->sendError('Validation Error.',$validator->errors());
        }
       
        $user_id = $request->user_id;
        $user = User::where('user_id',$user_id)->first(); // Use with() to eager load the package relationship
        $bank_details = Bank::where('user_id',$user_id)->first(); // Use with() to eager load the package relationship
        if (!$user) {
            return $this->sendError('User not found.');
        }
        $success = [
            'user' => $user,
            'bank_details' => $bank_details
        ];
        if($user){
            return $this->sendResponse($success, 'User Data Retrieve Successfully.');
        }else{
            return $this->sendError('Data not found.');

        }
    }

    public function profile_update(Request $request){
        $validator = Validator::make($request->all(),[
            'user_id' => 'required|exists:users,user_id',
        ]);
        if($validator->fails()){
            return $this->sendError('Validation Error.',$validator->errors());
        }
       
        $user_id = $request->user_id;
        $fields = [
            'user_id', 'district', 'state', 'address', 'pin_code', 'bank_name', 'account_number', 'ifsc_code', 'branch', 'account_holder_name', 'upi', 'paytm', 'phone_pe', 'google_pay', 'trx_rc20', 'usdt_bep20'
        ];
      $success['bank'] =   Bank::updateOrCreate(['user_id' => $user_id], $request->only($fields));

        if($success){
            return $this->sendResponse($success, 'User Data Retrieve Successfully.');
        }else{
            return $this->sendError('Data not found.');

        }
    } 

    public function update_password(Request $request){
            $validator = Validator::make($request->all(), [
                'user_id'    => 'required',
                'old_password' => 'required',
                'password'     => 'required',
                'password_confirmation' => 'required',
            ]);
            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }
            try{
    
                $old_password= $request->old_password;
                $password= $request->password;
                $password_confirmation= $request->password_confirmation;
                $obj = User::where('user_id', $request->user_id)->first();
                if (!Hash::check($request->old_password, $obj->password)) {
                    return $this->sendSuccessError('Oops! The old password does not match our records.',$old_password);
                }
                if ($password_confirmation === $password) {
                    $obj->password = Hash::make($request->password);
            
                    if ($obj->save()) {
                        return $this->sendResponse($obj, 'Password updated successfully.');
                    } else {
                        return $this->sendError('Oops! Unable to  update password. Please try again.');
                    }
                }else{
                    return $this->sendSuccessError('The password confirmation does not match','The password confirmation does not match');
    
                }
            }catch (\Exception $e) {
                return $this->sendError('Oops! Something went wrong. Please try again.');
            }
    } 
    public function active_users_id(){
        $data = User::where(['is_active'=>1,'is_green'=>1,'status'=>'Active','deleted_at'=>null])->orderBy('activated_date')->pluck('id')->toArray();
        return $data;
    }
    public function testRedis()
    {
        
        Redis::set('ping','pong');
        $ping = Redis::get('ping');
        return $ping;
    }

    public function active_users_old()
    {
   
        $data = User::where(['is_active' => 1, 'is_green' => 1, 'status' => 'Active', 'deleted_at' => null])
                    ->orderBy('activated_date')
                    ->pluck('user_id')
                    ->toArray();

        // Retrieve last processed user ID from Redis
        $lastUserId = Redis::get('last_user_id');
        $currentUserId = null;
        $helpReceived = 0;

        do {
            if ($lastUserId) {
                $lastUserIndex = array_search($lastUserId, $data);
    
                // Determine the next user in line
                if ($lastUserIndex !== false && isset($data[$lastUserIndex + 1])) {
                    $currentUserId = $data[$lastUserIndex + 1];
                } else {
                    // If we're at the end of the list, start from the beginning
                    $currentUserId = $data[0];
                }
            } else {
                // First time, start with the first user in the list
                $currentUserId = $data[0];
            }
    
            // Retrieve the receiver user and their package details
            $receiver = User::where('user_id', $currentUserId)->first();
            $receiverPackage = Package::where('id', $receiver->package_id)->first();
            $receiverPackage = $receiver->package_id;
    
            // Check how many times this user has received help
            $helpReceived = HelpStar::where('receiver_id', $currentUserId)->count();
            dd($helpReceived);
            if ($helpReceived < $receiverPackage->member) {
                break;  // Found a user who can still receive help
            }
    
            // Update the last processed user ID for the next iteration
            Redis::set('last_user_id', $currentUserId);
    
        } while ($helpReceived >= $receiverPackage->help);
    
        // Create a new HelpStar entry
        $data = new HelpStar();
        $data->sender_id = auth()->user()->user_id;
        $data->receiver_id = $currentUserId;
        $data->amount = $receiverPackage->help; // Use the help amount from the package
        $data->sender_position = auth()->user()->package->package_name;
        $data->receiver_position = $receiverPackage->package_name;
        $data->received_payments_count = 1;
        $data->commitment_date = now();
        $data->confirm_date = now();
        $data->status = 1;
        $data->save();

        // if($helpReceived == 3){
        // $receiver->package_id = $receiver->package_id+1;// update count for users
        // $receiver->Save();

        // }
        // $receiver->received_payments_count = $receiver->received_payments_count+1;// update count for users
        // $receiver->Save();
        // Update the last processed user ID for the next call
        Redis::set('last_user_id', $currentUserId);
        // / Update the package and payment count if the user has received the maximum number of helps
    if($helpReceived + 1 == $receiverPackage->help_count){
        // Get the next package
        $nextPackage = Package::where('package_order', $receiverPackage->package_order + 1)->first();
        if ($nextPackage) {
            $receiver->package_id = $nextPackage->id;
        }
    }

    // Update the user's received payment count
    $receiver->received_payments_count = $helpReceived + 1;
    $receiver->save();

    // Update the last processed user ID for the next call
    Redis::set('last_user_id', $currentUserId);
    }
    //     if ($lastUserId) {
    //         $lastUserIndex = array_search($lastUserId, $data);

    //         // Determine the next user in line
    //         if ($lastUserIndex !== false && isset($data[$lastUserIndex + 1])) {
    //             $currentUserId = $data[$lastUserIndex + 1];
    //         } else {
    //             // If we're at the end of the list, start from the beginning
    //             $currentUserId = $data[0];
    //         }
    //     } else {
    //         // First time, start with the first user in the list
    //         $currentUserId = $data[0];
    //     }

    //     // Store the current user ID as the last processed in Redis
    //     Redis::set('last_user_id', $currentUserId);
    //     // Store payer and receiver details in Redis (optional)
    //     // Redis::lpush('payment_queue', json_encode(['payer' => auth()->user()->id, 'receiver' => $currentUserId]));

    //     // Return the user who needs to be paid ₹300
    //     return $currentUserId;
    //     $receiver = User::where('user_id',$currentUserId)->first();
    //     $data = new HelpStar();
    //     $data->sender_id = auth()->user()->id;
    //     $data->receiver_id = $currentUserId;
    //     $data->amount = 300;
    //     $data->sender_position = auth()->user()->package_id;
    //     $data->receiver_position = $receiver->package_id ;
    //     $data->received_payments_count = 1;
    //     $data->commitment_date = now() ;
    //     $data->confirm_date = now();
    //     $data->status = 1;
    //     $data->save();
    // }
 
    public function view_direct(Request $request){
        $validator = Validator::make($request->all(),[
            'user_id' => 'required|exists:users,user_id',
        ]);
        if($validator->fails()){
            return $this->sendError('Validation Error.',$validator->errors());
        }
       
        $user_id = $request->user_id;
        $user = User::where('user_id',$user_id)->first(); // Use with() to eager load the package relationship
       $view_direct = User::where('sponsor_id',$request->user_id)->get();
       return $this->sendResponse($view_direct, 'Retrieve successfully.');
    }
    

    public function get_name_by_id(Request $request){
        $validator = Validator::make($request->all(),[
            'user_id' => 'required|exists:users,user_id',
        ]);
        if($validator->fails()){
            return $this->sendError('Validation Error.',$validator->errors());
        }
       
        $user_id = $request->user_id;
        $user = User::where('user_id',$user_id)->value('name'); // Use with() to eager load the package relationship
        if($user){
            $name = $user;

        }else{
            
            $name = 'anonymus';
        }
        return $this->sendResponse($name, 'Retrieve successfully.');
    }


  
    public function view_downline(Request $request){
        $validator = Validator::make($request->all(),[
            'user_id' => 'required|exists:users,user_id',
        ]);
        if($validator->fails()){
            return $this->sendError('Validation Error.',$validator->errors());
        }
        $query = User::query();
       
        $sponsorId = $request->user_id;
        $directMembers = User::where('sponsor_id', $sponsorId)->pluck('id');
        $allMemberIds = $directMembers->toArray();
        $this->getDownlineMembers($directMembers, $allMemberIds);
        $query->whereIn('id', $allMemberIds);
        
        $customers = $query->orderby('created_at','DESC')->get();
        
        return $this->sendResponse($customers, 'Retrieve successfully.');

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

    public function support_form(Request $request){
       
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,user_id',
            'subject' => 'required',
            'department_id'     => 'required',
            'priority' => 'required',
            'user_message' => 'required',
            'user_image' => 'nullable',
        ]);
        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }
        try{

                $obj = new Support();
                $obj->user_id = $request->user_id;
                $obj->subject = $request->subject;
                $obj->department_id = $request->department_id;
                $obj->priority = $request->priority;
                $obj->user_message = $request->user_message;
                $obj->user_image = $request->user_image;
                if ($obj->save()) {
                    return $this->sendResponse($obj, 'Form submitted successfully.');
                } else {
                    return $this->sendError('Oops! Unable to submit form. Please try again.');
                }
        }catch (\Exception $e) {
            return $this->sendError('Oops! Something went wrong. Please try again.');
        }
    }

    public function help_history(Request $request) 
    {
      
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,user_id',
        ]);
        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }
        try{
            $user_id = $request->user_id;
            $data = HelpStar::where('sender_id', $user_id)->with('receiverByData')->get();
            return $this->sendResponse($data, 'Data Retrieve successfully.');
             
        }catch (\Exception $e) {
            return $this->sendError('Oops! Something went wrong. Please try again.');
        }
    }

    public function taking_help(Request $request) 
    {
      
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,user_id',
        ]);
        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }
        try{
            $user_id = $request->user_id;
            $data = HelpStar::where('receiver_id', $user_id)->with('senderData')->get();
            return $this->sendResponse($data, 'Data Retrieve successfully.');
             
        }catch (\Exception $e) {
            return $this->sendError('Oops! Something went wrong. Please try again.');
        }
    }

    public function view_epin(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,user_id',
        ]);
        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }
        try{
            $user_id = $request->user_id;
        $data = EPinTransfer::orderBy('id', 'desc')->where('member_id',$user_id)->where('is_used','0')->with('MemberData','providedByData','EpinUsed')->get();
      
            return $this->sendResponse($data, 'Data Retrieve successfully.');
             
        }catch (\Exception $e) {
            return $this->sendError('Oops! Something went wrong. Please try again.');
        }
    }
    public function total_available_pin(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,user_id',
        ]);
        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }
        try{
            $user_id = $request->user_id;
        $data = EPinTransfer::orderBy('id', 'desc')->where('member_id',$user_id)->where('is_used','0')->with('MemberData','providedByData','EpinUsed')->count();
      
            return $this->sendResponse($data, 'Data Retrieve successfully.');
             
        }catch (\Exception $e) {
            return $this->sendError('Oops! Something went wrong. Please try again.');
        }
    }

    public function epin_transfer(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,user_id',
            'member_id' => 'required',
            'quantity' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }
        try{
            $user_id = $request->user_id;
            $member_id = $request->member_id;
            $quantity = $request->quantity;
            $member_name = $user = User::where('user_id',$user_id)->value('name');  

            $epins = EPinTransfer::orderBy('id', 'asc')->take($quantity)->take($quantity)->get();
            // Step 3: Fetch the exact number of EPinTransfer records to be updated
            $epin_transfers = EPinTransfer::where('member_id', $user_id)
                                ->where('is_used', '0')
                                ->orderBy('id', 'asc') // Ensure the order is consistent
                                ->take($quantity)
                                ->get();
    
            // Step 4: Update the EPinTransfer records
            foreach ($epin_transfers as $index => $transfer) {
                if (isset($epins[$index])) {
                    $epin = $epins[$index]; // Get the corresponding EPin record
    
                    // Update the EPinTransfer record with data from the EPin
                    $transfer->member_id = $member_id;
                    $transfer->provided_by = $user_id;
                    $transfer->member_name = $member_name;
                    $transfer->balance = $epin->balance;
                    $transfer->quantity = 1;
                    $transfer->status = $epin->status;
                    $transfer->flag = $epin->flag;
                    $transfer->e_pin = $epin->e_pin;
                    $transfer->save(); // Save the updated EPinTransfer
    
                }
            }
      
            return $this->sendResponse($transfer, 'Data Retrieve successfully.');
             
        }catch (\Exception $e) {
            return $this->sendError('Oops! Something went wrong. Please try again.');
        }
    }
 
}