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
use App\Models\News;
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
use Illuminate\Support\Facades\RateLimiter;
use App\Mail\AppPasswordResendMail;

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

    // public function login_store(Request $request) {
    //     $validator = Validator::make($request->all(), [
    //         'user_id' => 'required',
    //         'password' => 'required',
    //     ],[
    //         'password.required' => ERR_PASSWORD_REQUIRED
    //     ]);
    //     if($validator->fails()){
    //         return $this->sendError($validator->errors()->first());
    //     }
    //     if (Auth::attempt(['user_id' => $request->user_id, 'password' => $request->password])) {
    //         $user = Auth::user();
    //         $success['token'] = $user->createToken('MyApp')->accessToken;
    //         $success['id'] = $user->id;
    //         $success['name'] = $user->name;
    //         return $this->sendResponse($success, 'User login successfully.');
    //     } else {
    //         return $this->sendError('Customer not found');

    //     }
    // }
    // public function login_store(Request $request) {
    //     // Sanitize input to avoid any unwanted input manipulation
    //     $validator = Validator::make($request->only(['user_id', 'password']), [
    //         'user_id' => 'required|string|max:15',
    //         'password' => 'required|string|min:8',
    //     ],[
    //         'password.required' => ERR_PASSWORD_REQUIRED
    //     ]);
    
    //     // Return validation errors if validation fails
    //     if ($validator->fails()) {
    //         return $this->sendError($validator->errors()->first());
    //     }
    
    //     // Throttle login attempts to prevent brute force
    //     if (RateLimiter::tooManyAttempts('login_attempt:' . $request->ip(), 3)) {
    //         return $this->sendError('Too many login attempts. Please try again later.');
    //     }
    
    //     // Check for valid credentials and lock for too many attempts
    //     if (Auth::attempt(['user_id' => $request->user_id, 'password' => $request->password])) {
    //         RateLimiter::clear('login_attempt:' . $request->ip()); // Clear throttling on success
    //         $user = Auth::user();
    
    //         // Ensure the account is active
    //         if ($user->status != 'Active') {
    //             return $this->sendError('User account is inactive.');
    //         }
    
    //         // Create access token for the user
    //         $success['token'] = $user->createToken('MyApp')->accessToken;
    //         $success['id'] = $user->id;
    //         $success['name'] = $user->name;
    //         return $this->sendResponse($success, 'User logged in successfully.');
    
    //     } else {
    //         // Increment login attempt count
    //         RateLimiter::hit('login_attempt:' . $request->ip());
    
    //         return $this->sendError('Invalid credentials.');
    //     }
    // }
    
    public function login_store(Request $request) {
        // Validation logic
        $validator = Validator::make($request->only(['user_id', 'password']), [
            'user_id' => 'required|string|max:15',
            'password' => 'required|string',
        ], [
            'password.required' => ERR_PASSWORD_REQUIRED
        ]);
    
        // Return validation errors if validation fails
        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first());
        }
    
        // Define the rate limit key, based on the IP address or user ID
        $rateLimitKey = 'login_attempt:' . $request->ip();
    
        // Check if the user has exceeded the login attempt limit (5 attempts in 1 minute)
        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            return $this->sendError('Too many login attempts. Please try again in ' . ceil($seconds / 60) . ' minutes.');
        }
    
    // Attempt login in a single DB query, fetching user in the same step
        $credentials = $request->only('user_id', 'password');
        if (Auth::attempt($credentials)) {
            // Clear the rate limit on successful login
            RateLimiter::clear($rateLimitKey);
            
            // Retrieve the authenticated user without additional DB query
            $user = Auth::user();
    
            // Check if the user account is active
            if ($user->status !== 'Active') {
                return $this->sendError('User account is inactive.');
            }
    
            // Generate an access token
            $success = [
                'token' => $user->createToken('MyApp')->accessToken,
                'id' => $user->id,
                'name' => $user->name,
                'user_id' => $user->user_id,
            ];
    
            return $this->sendResponse($success, 'User logged in successfully.');
        } else {
            // Increment rate limit attempt count on failed login
            RateLimiter::hit($rateLimitKey, 60); // 60 seconds lockout for each failed attempt
    
            return $this->sendError('Invalid credentials.');
        }
    }
    
    // public function logout() {
    //     Auth::guard('web')->logout();
    //     return $this->sendResponse(true, 'Logout successfully.');
    // }

    public function logout(Request $request) {
        // Check if the user is authenticated before attempting to log out
        if (Auth::check()) {
            // Log the user out using the default guard ('web' in this case)
            Auth::logout();
    
            // Invalidate the session to prevent reuse
            $request->session()->invalidate();
    
            // Regenerate the session token to prevent session fixation
            $request->session()->regenerateToken();
    
            // Optionally, destroy the session to remove all session data
            $request->session()->flush();
    
            // Return a successful logout response
            return $this->sendResponse(true, 'Logout successful.');
        } else {
            // If the user is not authenticated, return an error response
            return $this->sendError('Logout failed.', ['error' => 'User is not authenticated.']);
        }
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
            return $this->sendError($validator->errors()->first(),'Validation Error.');
        }
        // DB::beginTransaction();
        try{

            $sponsor = User::where('user_id',$request->sponsor_id)->select('user_id','sponsor_id','is_active','activated_date','status','is_green')->first();
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
                $epin = EPinTransfer::select('e_pin','is_used')->where('e_pin', $request->registration_code)
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
                            /**
                              *Implement the 7-level transaction logic start
                            */ 
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

                              /**
                              *Implement the 7-level transaction logic end
                            */ 
                       
                            /** mlm code start from here */
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
                        $receiver = User::where('user_id', $currentUserId)->select('package_id','user_id','received_payments_count')->first(); // payment receive
                        $receiverPackage = Package::where('id', $receiver->package_id)->select('help','id','help_count')->first();
                        // $nextPackage = Package::where('id', ($receiverPackage->package_order + 1))->first();
             
                        // Check how many times this user has received help
                        $helpReceived_count = HelpStar::where('receiver_id', $currentUserId)->where('receiver_position',2)->count();
                        // echo '$receiverPackage->help_count'.$receiverPackage->help_count.'<br>'; 
                        // echo '$helpReceived_count'.$helpReceived_count;
                           // If the user is not eligible for help, redirect the payment to the admin
                           if($helpReceived_count == 1){
                            $this->star_level_users($userId);
                           }
                        if ($helpReceived_count < 3) {  //0 <= 3
                            // Create a new HelpStar entry
                            $HelpStar = new HelpStar();
                            $HelpStar->sender_id = $userId;
                            $HelpStar->receiver_id = $currentUserId;
                            $HelpStar->amount = $receiverPackage->help; // Use the help amount from the package
                            $HelpStar->sender_position = '1'; 
                            $HelpStar->receiver_position = $receiverPackage->id;
                            $HelpStar->received_payments_count = 1;
                            $HelpStar->commitment_date = now();
                            $HelpStar->confirm_date = null;
                            $HelpStar->status = 'Pending';
                            $HelpStar->save();
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
                            $HelpStarAdmin = new HelpStar();
                            $HelpStarAdmin->sender_id = $user_id_sender;
                            $HelpStarAdmin->receiver_id = 'PHC123456'; // Payment goes to admin
                            $HelpStarAdmin->amount = 300; // Use the help amount from the package
                            $HelpStarAdmin->sender_position =1;
                            $HelpStarAdmin->receiver_position = 2;
                            $HelpStarAdmin->received_payments_count = 1;
                            $HelpStarAdmin->commitment_date = now();
                            $HelpStarAdmin->confirm_date = null;
                            $HelpStarAdmin->status = 'Pending';
                            $HelpStarAdmin->save();   
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
        // $active_users =   User::where('is_active', 1)
        //     ->where('is_green', 1)
        //     ->where('status', 'Active')
        //     ->whereNull('deleted_at')
        //     ->where('package_id', 2)
        //     ->orderBy('activated_date')
        //     ->pluck('user_id')
        //     ->toArray();
        //     // $active_users =   $active_users ?  $active_users : ['PHC123456'];

        // $herlp_star_users =   HelpStar::whereIn('receiver_id', $active_users)->count(3);
        //     return $active_users;

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
                ->limit(10) 
                ->pluck('receiver_id')
                ->toArray();
                return $users_with_exactly_three_helps;
             
    }

    public function star_level_users($currentUserId) {
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
           
            $helpReceived_count = 0;

            if ($lastUserId) {
                $lastUserIndex = array_search($lastUserId, $third_level_users);

                if ($lastUserIndex !== false && isset($third_level_users[$lastUserIndex + 1])) {
                    $currentUserId = $third_level_users[$lastUserIndex + 1];
                } else {
                    $currentUserId = $third_level_users[0];
                }
            } else {
                $currentUserId = $third_level_users[0];
            }
        // Retrieve the receiver user and their package details
        $receiver = User::where('user_id', $currentUserId)->first(); // payment receive
        $receiverPackage = Package::where('id', $receiver->package_id)->first();
        $helpReceived_count = HelpStar::where('receiver_id', $currentUserId)->where('receiver_position',3)->count();

        if($helpReceived_count == 4){
            $this->silver_level_users($currentUserId);
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
        Redis::set('third_level_last_user_id', $currentUserId);
        $success['current_transaction'] =$currentUserId;
        $success['new_transaction'] =$lastUserId;
    }


    public function silver($currentUserId){
        $userId = $currentUserId;
        $four_level_users =   User::where('is_active', 1)
            ->where('is_green', 1)
            ->where('status', 'Active')
            ->whereNull('deleted_at')
            ->where('package_id', 4)
            ->orderBy('activated_date')
            ->pluck('user_id')
            ->toArray();
         
            if (!is_array($four_level_users) || empty($four_level_users)) {
                $four_level_users = ['PHC123456'];
            }
            // Retrieve last processed user ID from Redis
            $lastUserId = Redis::get('four_level_last_user_id');
           
            $helpReceived_count = 0;

            if ($lastUserId) {
                $lastUserIndex = array_search($lastUserId, $four_level_users);

                if ($lastUserIndex !== false && isset($four_level_users[$lastUserIndex + 1])) {
                    $currentUserId = $four_level_users[$lastUserIndex + 1];
                } else {
                    $currentUserId = $four_level_users[0];
                }
            } else {
                $currentUserId = $four_level_users[0];
            }
        // Retrieve the receiver user and their package details
        $receiver = User::where('user_id', $currentUserId)->first(); // payment receive
        $receiverPackage = Package::where('id', $receiver->package_id)->first();
        if($currentUserId == 'PHC123456'){
            $receiverPackage = Package::where('id', '4')->first();
        }
        $helpReceived_count = HelpStar::where('receiver_id', $currentUserId)->where('receiver_position',4)->count();

        if($helpReceived_count == 7){
            $this->gold_level_users($currentUserId);
        }
        if ($helpReceived_count < 9) {  //0 <= 3
            // Create a new HelpStar entry
            $data = new HelpStar();
            $data->sender_id = $userId;
            $data->receiver_id = $currentUserId;
            $data->amount = $receiverPackage->help; // Use the help amount from the package
            $data->sender_position = '3'; 
            $data->receiver_position = $receiverPackage->id;
            $data->received_payments_count = 111;
            $data->commitment_date = now();
            $data->confirm_date = null;
            $data->status = 'Pending';
            $data->save();
            // If the user has received the maximum number of helps, update their package
            if ($helpReceived_count+1  == 9) {
                    $receiver->package_id = 5;
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
            $data->amount = 2000; // Use the help amount from the package
            $data->sender_position ='3';
            $data->receiver_position = '4';
            $data->received_payments_count = 1;
            $data->commitment_date = now();
            $data->confirm_date = null;
            $data->status = 'Pending';
            $data->save();   
        }
        Redis::set('four_level_last_user_id', $currentUserId);
        $success['current_transaction'] =$currentUserId;
        $success['new_transaction'] =$lastUserId;
    }

     
    public function gold_level_users($currentUserId){
        $userId = $currentUserId;
        $four_level_users =   User::where('is_active', 1)
            ->where('is_green', 1)
            ->where('status', 'Active')
            ->whereNull('deleted_at')
            ->where('package_id', 4)
            ->orderBy('activated_date')
            ->pluck('user_id')
            ->toArray();
         
            if (!is_array($four_level_users) || empty($four_level_users)) {
                $four_level_users = ['PHC123456'];
            }
            // Retrieve last processed user ID from Redis
            $lastUserId = Redis::get('four_level_last_user_id');
           
            $helpReceived_count = 0;

            if ($lastUserId) {
                $lastUserIndex = array_search($lastUserId, $four_level_users);

                if ($lastUserIndex !== false && isset($four_level_users[$lastUserIndex + 1])) {
                    $currentUserId = $four_level_users[$lastUserIndex + 1];
                } else {
                    $currentUserId = $four_level_users[0];
                }
            } else {
                $currentUserId = $four_level_users[0];
            }
        // Retrieve the receiver user and their package details
        $receiver = User::where('user_id', $currentUserId)->first(); // payment receive
        $receiverPackage = Package::where('id', $receiver->package_id)->first();
        if($currentUserId == 'PHC123456'){
            $receiverPackage = Package::where('id', '4')->first();
        }
        $helpReceived_count = HelpStar::where('receiver_id', $currentUserId)->where('receiver_position',4)->count();

        if($helpReceived_count == 7){
            $this->platinum_level_users($currentUserId);
        }
        if ($helpReceived_count < 9) {  //0 <= 3
            // Create a new HelpStar entry
            $data = new HelpStar();
            $data->sender_id = $userId;
            $data->receiver_id = $currentUserId;
            $data->amount = $receiverPackage->help; // Use the help amount from the package
            $data->sender_position = '3'; 
            $data->receiver_position = $receiverPackage->id;
            $data->received_payments_count = 111;
            $data->commitment_date = now();
            $data->confirm_date = null;
            $data->status = 'Pending';
            $data->save();
            // If the user has received the maximum number of helps, update their package
            if ($helpReceived_count+1  == 9) {
                    $receiver->package_id = 5;
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
            $data->amount = 2000; // Use the help amount from the package
            $data->sender_position ='3';
            $data->receiver_position = '4';
            $data->received_payments_count = 1;
            $data->commitment_date = now();
            $data->confirm_date = null;
            $data->status = 'Pending';
            $data->save();   
        }
        Redis::set('four_level_last_user_id', $currentUserId);
        $success['current_transaction'] =$currentUserId;
        $success['new_transaction'] =$lastUserId;
    }
 
     

    public function dashboard(Request $request){
        $validator = Validator::make($request->all(),[
            'user_id' => 'required|exists:users,user_id',
        ]);
        if($validator->fails()){
            return $this->sendError($validator->errors()->first());
        }
    
        $user_id = $request->user_id;
        $restricted_user_ids = [
            'PHC123456', 'PHC674962', 'PHC636527', 'PHC315968', 'PHC985875', 
            'PHC746968', 'PHC666329', 'PHC415900', 'PHC173882', 'PHC571613', 
            'PHC663478', 'PHC875172'
        ];
    try{
         $user = User::where('user_id', $user_id)
            ->with('package:id,package_name')
            ->first();

        if (!$user) {
            return $this->sendError('User not found.');
        }
        $active = $user->where('is_active',0)->whereNotNull('status')->whereNotNull('activated_date')->first();
         
       
        $seven_level_transaction = null;
        $giving_help = null;
        
     
        if (!in_array($user_id, $restricted_user_ids)) {
            $seven_level_transaction = $this->seven_level_transaction($user_id);
            $giving_help = $this->giving_help($user_id);
        } 

        // return $seven_level_transaction;
        $taking_help_n = $this->taking_help_n($user_id); // Newly added function
        $taking_transaction = $this->taking_transaction($user_id); // Newly added function

        $success = [
            'user' => $user->only(['id','user_id', 'name', 'activated_date', 'created_at', 'package_id','sponsor_id']),
            'package_name' => $user->package ? $user->package->package_name : null,
            'direct_team' => User::where('sponsor_id', $user_id)->count(),
            'total_team' => $this->getAllTeamMembers($user_id),
            'referral_link' => url('api/customer/registration/' . $user_id),
            'giving_help' => $giving_help,
            'seven_level_transaction' => $seven_level_transaction,
            'receiving_help' => $taking_help_n,
            'taking_seven_level_transaction' => $taking_transaction, // Newly added key
            // 'taking_sponcer' => 0,
            'e_pin' => EPinTransfer::where('member_id', $user_id)->where('is_used', '0')->count(),
            'news' => News::where('status', 'Active')->select('news_title','news_content','news_order')->orderBy('news_order')->get()
        ];
        return $this->sendResponse($success, 'User Data Retrieve Successfully.');
      
      }catch (\Exception $e) {
            return $this->sendError('Oops! Something went wrong. Please try again.');
        }
    }
    public function getAllTeamMembers($userId)
    {
        $children = User::where('sponsor_id', $userId)->count();
    
        return $children;
    }
    
    private function giving_help($user_id) {
        // Fetch the HelpStar data based on the given user_id
        $admin = HelpStar::where('sender_id', $user_id)->where('confirm_date',null)
                    ->select('sender_id', 'receiver_id', 'amount', 'commitment_date', 'confirm_date', 'status')
                    ->first();
    
        // Ensure that the $admin object is not null before fetching sender and receiver details
        if ($admin) {
            // Fetch sender details
            $sender = User::where('user_id', $admin->sender_id)
                        ->select('name', 'phone', 'phone_pay_no')
                        ->first();
    
            // Fetch receiver details
            $receiver = User::where('user_id', $admin->receiver_id)
                        ->select('name', 'phone', 'phone_pay_no')
                        ->first();
    
            // Merge sender and receiver details into the $admin object
            $admin->title = 'Giving Help';
            $admin->sender_name = $sender->name ?? null;
            $admin->sender_phone = $sender->phone ?? null;
            $admin->sender_phone_pay_no = $sender->phone_pay_no ?? null;
    
            $admin->receiver_name = $receiver->name ?? null;
            $admin->receiver_phone = $receiver->phone ?? null;
            $admin->receiver_phone_pay_no = $receiver->phone_pay_no ?? null;
    
            // Return all data as a single associative array
            return $admin->toArray();
        }
    
        return null; // Return null if no data is found
    }

    private function taking_help_n($user_id) {
        // Fetch the HelpStar data based on the given user_id
        $admin = HelpStar::where('receiver_id', $user_id)->where('confirm_date',null)
                    ->select('id','sender_id', 'receiver_id', 'amount', 'commitment_date', 'confirm_date', 'status')
                    ->get();
    
        // Ensure that the $admin collection is not empty before proceeding
        if ($admin->isNotEmpty()) {
            // Map over the $admin collection to append sender and receiver details
            $admin->map(function ($item) {
                // Fetch sender details
                $sender = User::where('user_id', $item->sender_id)
                            ->select('name', 'phone', 'phone_pay_no')
                            ->first();
        
                // Fetch receiver details
                $receiver = User::where('user_id', $item->receiver_id)
                            ->select('name', 'phone', 'phone_pay_no')
                            ->first();
        
                // Append sender details to the current HelpStar item
                $item->title = 'Taking Help';
                $item->sender_name = $sender->name ?? null;
                $item->sender_phone = $sender->phone ?? null;
                $item->sender_phone_pay_no = $sender->phone_pay_no ?? null;
        
                // Append receiver details to the current HelpStar item
                $item->receiver_name = $receiver->name ?? null;
                $item->receiver_phone = $receiver->phone ?? null;
                $item->receiver_phone_pay_no = $receiver->phone_pay_no ?? null;
            });
    
            // Return all data as an array
            return $admin->toArray();
        }
    
        return null; // Return null if no data is found
    }
    
    
    
        
    private function seven_level_transaction($user_id) {
        $restricted_user_ids = ['PHC123456', 'PHC674962', 'PHC636527', 'PHC315968', 'PHC985875', 'PHC746968', 'PHC666329', 'PHC415900', 'PHC173882', 'PHC571613', 'PHC663478', 'PHC875172'];
        if(in_array($user_id,$restricted_user_ids )){
            return null;
        }
        // Fetch the seven-level transaction for the given user
        $seven_level_transaction = SevenLevelTransaction::where('sender_id', $user_id)
            ->select('first_level', 'second_level', 'third_level', 'fourth_level', 'five_level', 'six_level', 'seven_level')->first();
     
        // Check if a transaction was found
        if (!$seven_level_transaction) {
            return null; // Return null or handle the error as needed
        }
    
        // Define the levels to iterate through
        $levels = ['first_level', 'second_level', 'third_level', 'fourth_level', 'five_level', 'six_level', 'seven_level'];
     
        foreach ($levels as $level) {
            if ($seven_level_transaction->$level) {
                // Fetch the user details for each level if the level has a value
                $user = User::where('user_id', $seven_level_transaction->$level)
                    ->select('name', 'phone', 'phone_pay_no', 'user_id')
                    ->first();
    
                // Check if the user_id is in the restricted list
                if ($user && in_array($user->user_id, $restricted_user_ids)) {
                    $user->phone_pay_no = null; // Hide phone_pay_no for restricted users
                }
    
                // Assign the fetched user object back to the level in seven_level_transaction
                $seven_level_transaction->$level = $user;
            }
        }
    
        return $seven_level_transaction;
    }
    
    

    // private function taking_transaction($user_id) {
    //     // Define sponsor level amounts and titles for each level
    //     $sponsor_level_data = [
    //         ['amount' => 100, 'title' => 'First Level'],
    //         ['amount' => 50, 'title' => 'Second Level'],
    //         ['amount' => 40, 'title' => 'Third Level'],
    //         ['amount' => 20, 'title' => 'Fourth Level'],
    //         ['amount' => 20, 'title' => 'Fifth Level'],
    //         ['amount' => 10, 'title' => 'Sixth Level'],
    //         ['amount' => 10, 'title' => 'Seventh Level']
    //     ];
    
    //     // Initialize an empty collection to store all taking transactions
    //     $taking_transactions = collect();
    
    //     // Fetch transactions where the user is the receiver at any level
    //     $levels = [
    //         'first_level', 'second_level', 'third_level', 
    //         'fourth_level', 'five_level', 'six_level', 'seven_level'
    //     ];
    
    //     // Iterate through each level and retrieve corresponding transactions
    //     foreach ($levels as $index => $level) {
    //         $column = $level; // level column name (e.g., 'first_level', 'second_level', etc.)
    //         $confirm_date_column = $level . '_confirm_date'; // confirm date column (e.g., 'first_level_confirm_date')
    
    //         $transactions = SevenLevelTransaction::with('senderDetail')
    //             ->where($column, $user_id)
    //             ->select('id', 'sender_id', $column, $confirm_date_column)
    //             ->get()
    //             ->map(function($transaction) use ($sponsor_level_data, $index) {
    //                 // Add level-specific data to each transaction
    //                 $transaction->level = $sponsor_level_data[$index]['title']; // Level title
    //                 $transaction->amount = $sponsor_level_data[$index]['amount']; // Level amount
    //                 return $transaction;
    //             });
    
    //         // Merge the retrieved transactions into the collection
    //         $taking_transactions = $taking_transactions->merge($transactions);
    //     }
    
    //     // Return all taking transactions for the user with level and amount information
    //     return $taking_transactions;
    // }
    // private function taking_transaction($user_id) {
    //     // Define sponsor level amounts and titles for each level
    //     $sponsor_level_data = [
    //         ['amount' => 100, 'title' => 'First Level'],
    //         ['amount' => 50, 'title' => 'Second Level'],
    //         ['amount' => 40, 'title' => 'Third Level'],
    //         ['amount' => 20, 'title' => 'Fourth Level'],
    //         ['amount' => 20, 'title' => 'Fifth Level'],
    //         ['amount' => 10, 'title' => 'Sixth Level'],
    //         ['amount' => 10, 'title' => 'Seventh Level']
    //     ];
    
    //     // Initialize an empty collection to store all taking transactions
    //     $taking_transactions = collect();
    
    //     // Define the levels for the query
    //     $levels = [
    //         'first_level', 'second_level', 'third_level', 
    //         'fourth_level', 'five_level', 'six_level', 'seven_level'
    //     ];
    
    //     // Iterate through each level and retrieve corresponding transactions
    //     foreach ($levels as $index => $level) {
    //         $level_column = $level; // e.g., 'first_level', 'second_level', etc.
    //         $status_column = $level . '_status'; // e.g., 'first_level_status', 'second_level_status', etc.
    //         $confirm_date_column = $level . '_confirm_date'; // e.g., 'first_level_confirm_date', etc.
    
    //         // Retrieve transactions where status is 0
    //         $transactions = SevenLevelTransaction::with('senderDetail')
    //             ->where($level_column, $user_id)   // User matches this level
    //             // ->select('id', 'sender_id', $level_column, $status_column, $confirm_date_column)
    //             ->get();
              
    //         // Merge the retrieved transactions into the collection
    //         $taking_transactions = $taking_transactions->merge($transactions);
    //     }
    
    //     // Return all taking transactions for the user with level and amount information
    //     return $taking_transactions;
    // }
    
public function taking_transaction($user_id) {
    // Define levels and their corresponding status and confirm date columns
    $levels = [
        'first_level' => 'first_level_status',
        'second_level' => 'second_level_status',
        'third_level' => 'third_level_status',
        'fourth_level' => 'fourth_level_status',
        'five_level' => 'five_level_status',
        'six_level' => 'six_level_status',
        'seven_level' => 'seven_level_status',
    ];

    // Initialize a collection to store the separate level transactions
    $separate_transactions = collect();

    // Iterate through each level and retrieve corresponding transactions
    foreach ($levels as $level => $status) {
        $transactions = SevenLevelTransaction::where($level, $user_id) // Match user ID with level
            ->where($status, '0') // Filter transactions where status is 0
            ->select('id', 'sender_id', $level, $status) // Select necessary fields
            ->get()
            ->map(function ($transaction) use ($level) {
                $transaction->level = $level; // Add the level name to the transaction
                return $transaction;
            });

        // Merge the retrieved transactions into the collection
        $separate_transactions = $separate_transactions->merge($transactions);
    }

    // Return the collection of separate level transactions
    return $separate_transactions;
}

	
// private function taking_transaction($user_id) {
//     $sponser_level_amount =  [100, 50, 40, 20, 20, 10, 10];
//     // Initialize an empty collection to store all taking transactions
//     $taking_transactions = collect();

//     // Fetch transactions where the user is the receiver at any level
//     $first_level = SevenLevelTransaction::with('senderDetail')->where('first_level', $user_id)->select('id', 'sender_id', 'first_level', 'first_level_confirm_date')->get();
//     $second_level = SevenLevelTransaction::with('senderDetail')->where('second_level', $user_id)->select('id', 'sender_id', 'second_level', 'second_level_confirm_date')->get();
//     $third_level = SevenLevelTransaction::with('senderDetail')->where('third_level', $user_id)->select('id', 'sender_id', 'third_level', 'third_level_confirm_date')->get();
//     $fourth_level = SevenLevelTransaction::with('senderDetail')->where('fourth_level', $user_id)->select('id', 'sender_id', 'fourth_level', 'fourth_level_confirm_date')->get();
//     $fifth_level = SevenLevelTransaction::with('senderDetail')->where('five_level', $user_id)->select('id', 'sender_id', 'five_level', 'five_level_confirm_date')->get();
//     $sixth_level = SevenLevelTransaction::with('senderDetail')->where('six_level', $user_id)->select('id', 'sender_id', 'six_level', 'six_level_confirm_date')->get();
//     $seventh_level = SevenLevelTransaction::with('senderDetail')->where('seven_level', $user_id)->select('id', 'sender_id', 'seven_level', 'seven_level_confirm_date')->get();

//     // Merge all level transactions into a single collection
//     $taking_transactions = $taking_transactions->merge($first_level);
//     $taking_transactions = $taking_transactions->merge($second_level);
//     $taking_transactions = $taking_transactions->merge($third_level);
//     $taking_transactions = $taking_transactions->merge($fourth_level);
//     $taking_transactions = $taking_transactions->merge($fifth_level);
//     $taking_transactions = $taking_transactions->merge($sixth_level);
//     $taking_transactions = $taking_transactions->merge($seventh_level);

//     // Return all taking transactions for the user
//     return $taking_transactions;
// }

	
	
	


    public function my_profile(Request $request){
        $validator = Validator::make($request->all(),[
            'user_id' => 'required|exists:users,user_id',
        ]);
        if($validator->fails()){
            return $this->sendError($validator->errors()->first());

        }
       
        $user_id = $request->user_id;
        $user = User::where('user_id',$user_id)->select('id','user_id','name','email',
        'phone','sponsor_id','phone_pay_no','registration_code','is_active','is_green',
        'package_id','activated_date','status','green_date','created_at')->first(); // Use with() to eager load the package relationship
        $bank_details = Bank::where('user_id',$user_id)->select("id","user_id","district","state","address","pin_code","bank_name","account_number","ifsc_code","branch","account_holder_name","upi","paytm","phone_pe","google_pay")->first(); // Use with() to eager load the package relationship
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
            'address' => 'required',
            'bank_name' => 'required',
            'account_number' => 'required',
            'ifsc_code' => 'required',
            'branch' => 'required',
            'account_holder_name' => 'required',
            // 'upi' => 'required',
            // 'paytm' => 'required',
            // 'phone_pe' => 'required',
            // 'google_pay' => 'required',
            // 'trx_rc20' => 'required',
            // 'usdt_bep20' => 'required',
        ]);
        if($validator->fails()){
            return $this->sendError($validator->errors()->first());
        }
       
        $user_id = $request->user_id;
        $fields = [
            'user_id', 'address', 'bank_name', 'account_number', 'ifsc_code', 'branch', 'account_holder_name', 'upi', 'paytm', 'phone_pe', 'google_pay', 'trx_rc20', 'usdt_bep20'
        ];
      $success['bank'] =   Bank::updateOrCreate(['user_id' => $user_id], $request->only($fields));

        if($success){
            return $this->sendResponse($success, 'User Data Retrieve Successfully.');
        }else{
            return $this->sendError('Data not found.');

        }
    } 

    // public function update_password(Request $request){
    //         $validator = Validator::make($request->all(), [
    //             'user_id'    => 'required',
    //             'old_password' => 'required',
    //             'password'     => 'required',
    //             'password_confirmation' => 'required',
    //         ]);
    //         if ($validator->fails()) {
    //             return $this->sendError('Validation Error.', $validator->errors());
    //         }
    //         try{
    
    //             $old_password= $request->old_password;
    //             $password= $request->password;
    //             $password_confirmation= $request->password_confirmation;
    //             $obj = User::where('user_id', $request->user_id)->first();
    //             if (!Hash::check($request->old_password, $obj->password)) {
    //                 return $this->sendSuccessError('Oops! The old password does not match our records.',$old_password);
    //             }
    //             if ($password_confirmation === $password) {
    //                 $obj->password = Hash::make($request->password);
            
    //                 if ($obj->save()) {
    //                     return $this->sendResponse($obj, 'Password updated successfully.');
    //                 } else {
    //                     return $this->sendError('Oops! Unable to  update password. Please try again.');
    //                 }
    //             }else{
    //                 return $this->sendSuccessError('The password confirmation does not match','The password confirmation does not match');
    
    //             }
    //         }catch (\Exception $e) {
    //             return $this->sendError('Oops! Something went wrong. Please try again.');
    //         }
    // } 

    function update_password(Request $request) {
        // Validate the request with secure rules
        $validator = Validator::make($request->all(), [
            'user_id'              => 'required|exists:users,user_id', // Ensure user_id exists in the users table
            'old_password'         => 'required|string',
            'password'             => 'required|string|min:8|confirmed', // Ensure the password is at least 8 characters and matches confirmation
            'password_confirmation'=> 'required|string|min:8'
        ]);
    
        // Return validation errors if validation fails
        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first());

        }
    
        try {
            // Retrieve user by user_id
            $user = User::where('user_id', $request->user_id)->first();
    
            // Check if the old password matches the current password
            if (!Hash::check($request->old_password, $user->password)) {
                return $this->sendError('The old password does not match our records.');
            }
    
            // Update the password and save the user
            $user->password = Hash::make($request->password);
    
            // Save the updated user data
            if ($user->save()) {
                return $this->sendResponse([], 'Password updated successfully.');
            } else {
                return $this->sendError('Unable to update the password. Please try again.');
            }
    
        } catch (\Exception $e) {
            // Log the error for debugging purposes
            Log::error('Password update error: ', ['error' => $e->getMessage()]);
            return $this->sendError('Something went wrong. Please try again later.');
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
            // dd($helpReceived);
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
 
    public function view_direct(Request $request) {
        // Validate the request input
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,user_id',
            'fromDate' => 'nullable|date', // Optional date field with date validation
            'toDate' => 'nullable|date',   // Optional date field with date validation
            'status' => 'nullable|string', // Optional status field
        ]);
    
        // If validation fails, return the first error
        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first());
        }
    
        // Create a query instance for the User model
        $user_id = $request->user_id; // Use sponsor_id to filter
        $query = User::where('sponsor_id', $user_id); // Use sponsor_id to filter
    
        if ($request->fromDate) {
            $fromDate = \Carbon\Carbon::createFromFormat('Y-m-d', $request->fromDate)->startOfDay();
            $query->where('created_at', '>=', $fromDate);
        }
    
        // Filter by toDate
        if ($request->toDate) {

            $toDate = \Carbon\Carbon::createFromFormat('Y-m-d', $request->toDate)->endOfDay();
            $query->where('created_at', '<=', $toDate);
        }
    
        // Filter by status
        if ($request->status) {
            $query->where('status', $request->status);
        }
    
        // Execute the query and get the results
        $view_direct = $query->select('user_id','name','phone','created_at','activated_date','sponsor_id','status')->get();
        $view_direct->map(function ($user) {
            $user->sponsor_name = $this->get_name($user->sponsor_id);
            return $user;
        });

        // Return the filtered list of direct users
        return $this->sendResponse($view_direct, 'Retrieved successfully.');
    }
    
    public function get_name($user_id) {
        // Get the user name based on the user ID, with a fallback to 'anonymous'
        $user = User::where('user_id', $user_id)->value('name');
        return $user ? $user : 'anonymous';
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
    // public function view_downline(Request $request) {
    //     // Validate the request input
    //     $validator = Validator::make($request->all(), [
    //         'user_id' => 'required|exists:users,user_id',
    //         'fromDate' => 'nullable|date', // Optional date field with date validation
    //         'toDate' => 'nullable|date',   // Optional date field with date validation
    //         'status' => 'nullable|string', // Optional status field
    //     ]);
    
    //     // If validation fails, return the first error
    //     if ($validator->fails()) {
    //         return $this->sendError($validator->errors()->first());
    //     }
    
    //     // Create a query instance for the User model
    //     $user_id = $request->user_id; // Use sponsor_id to filter
    //     $query = User::where('sponsor_id', $user_id); // Use sponsor_id to filter
    
    //     if ($request->fromDate) {
    //         $fromDate = \Carbon\Carbon::createFromFormat('Y-m-d', $request->fromDate)->startOfDay();
    //         $query->where('created_at', '>=', $fromDate);
    //     }
    
    //     // Filter by toDate
    //     if ($request->toDate) {

    //         $toDate = \Carbon\Carbon::createFromFormat('Y-m-d', $request->toDate)->endOfDay();
    //         $query->where('created_at', '<=', $toDate);
    //     }
    
    //     // Filter by status
    //     if ($request->status) {
    //         $query->where('status', $request->status);
    //     }
    
    //     // Execute the query and get the results
    //     $view_direct = $query->select('id','user_id','name','phone','created_at','activated_date','sponsor_id','status')->get();
    //     $view_direct->map(function ($user) {
    //         $user->sponsor_name = $this->get_name($user->sponsor_id);
    //         return $user;
    //     });

    //     // Return the filtered list of direct users
    //     return $this->sendResponse($view_direct, 'Retrieved successfully.');
    // }
    
        public function view_downlinen(Request $request)
        {
            // Validate the request input
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,user_id',
            ]);
        
            // If validation fails, return the first error
            if ($validator->fails()) {
                return $this->sendError($validator->errors()->first());
            }
        
            // Find the user by their user_id
            $user = User::where('sponsor_id', $request->user_id)->first();
       
            // Get all downline users using the recursive relationship
            $downline_users = $user->allReferrals()->get();
            dd($downline_users);
            // Return the downline users
            return $this->sendResponse($downline_users, 'Retrieved successfully.');
        }
        
    

    // public function view_downline(Request $request) {
    //     $validator = Validator::make($request->all(), [
    //         'user_id' => 'required|exists:users,user_id',
    //         'fromDate' => 'nullable|date',
    //         'toDate' => 'nullable|date',
    //         'status' => 'nullable|string',
    //     ]);
    
    //     if ($validator->fails()) {
    //         return $this->sendError($validator->errors()->first());
    //     }
    
    //     $sponsorId = $request->user_id;
        
    //     // Retrieve all downline members in one query using a breadth-first approach
    //     $allMemberIds = $this->getDownlineMembers($sponsorId);
    
    //     // Create query for retrieving filtered users
    //     $query = User::select('id','user_id','name','phone','created_at','activated_date','sponsor_id','status')->whereIn('id', $allMemberIds);
    
    //     // Apply date range filters
    //     if ($request->fromDate) {
    //         $fromDate = \Carbon\Carbon::parse($request->fromDate)->startOfDay();
    //         $query->where('created_at', '>=', $fromDate);
    //     }
    
    //     if ($request->toDate) {
    //         $toDate = \Carbon\Carbon::parse($request->toDate)->endOfDay();
    //         $query->where('created_at', '<=', $toDate);
    //     }
    
    //     // Filter by status if provided
    //     if ($request->status) {
    //         $query->where('status', $request->status);
    //     }
    
    //     // Get results with pagination to handle large datasets
    //     $customers = $query->orderBy('created_at', 'DESC')->get();
    
    //     return $this->sendResponse($customers, 'Retrieve successfully.');
    // }
//     public function view_downline(Request $request)
// {
//     // Validate the request input
//     $validator = Validator::make($request->all(), [
//         'user_id' => 'required|exists:users,user_id',
//         'fromDate' => 'nullable|date_format:Y-m-d', // Validate date format
//         'toDate' => 'nullable|date_format:Y-m-d',   // Validate date format
//         'status' => 'nullable|string',
//     ]);

//     // If validation fails, return the first error
//     if ($validator->fails()) {
//         return $this->sendError($validator->errors()->first());
//     }

//     // Find the sponsor user
//     $user = User::where('user_id', $request->user_id)->select('id','user_id','name','phone','created_at','activated_date','sponsor_id','status')->first();


//     // Fetch all direct and indirect members (recursive relationship)
//     $all_members = $user->allReferralsFlat();

//     // Apply additional filters, if necessary
//     $filtered_members = $all_members;

//     if ($request->filled('fromDate')) {
//         $fromDate = \Carbon\Carbon::createFromFormat('Y-m-d', $request->fromDate)->startOfDay();
//         $filtered_members = $filtered_members->filter(function ($user) use ($fromDate) {
//             return $user->created_at >= $fromDate;
//         });
//     }

//     // Filter by toDate
//     if ($request->filled('toDate')) {
//         $toDate = \Carbon\Carbon::createFromFormat('Y-m-d', $request->toDate)->endOfDay();
//         $filtered_members = $filtered_members->filter(function ($user) use ($toDate) {
//             return $user->created_at <= $toDate;
//         });
//     }

//     // Filter by status
//     if ($request->filled('status')) {
//         $filtered_members = $filtered_members->filter(function ($user) use ($request) {
//             return $user->status === $request->status;
//         });
//     }

//     // Convert the filtered result back to a collection
//     $filteredMembers = $filtered_members->values(); // Reset the keys

//     // Return the filtered list of all members
//     return $this->sendResponse($filteredMembers, 'Retrieved successfully.');
// }

    // private function getAllDownlineMembersnew($sponsorId) {
    //     // Initialize arrays to hold current and all downline member IDs
    //     $allMemberIds = [$sponsorId];
    //     $currentMembers = [$sponsorId];
    
    //     while (!empty($currentMembers)) {
    //         // Retrieve the next level of downline members in one query
    //         $nextMembers = User::whereIn('sponsor_id', $currentMembers)->pluck('id')->toArray();
    
    //         // If no more members, break the loop
    //         if (empty($nextMembers)) {
    //             break;
    //         }
    
    //         // Add next level of members to the allMemberIds array
    //         $allMemberIds = array_merge($allMemberIds, $nextMembers);
    
    //         // Set currentMembers to the nextMembers for the next iteration
    //         $currentMembers = $nextMembers;
    //     }
    
    //     return $allMemberIds;
    // }
    
    // public function view_downline(Request $request){
    //     $validator = Validator::make($request->all(),[
    //         'user_id' => 'required|exists:users,user_id',
    //     ]);
    //     if($validator->fails()){
    //         return $this->sendError($validator->errors()->first());
    //     }
    //     $query = User::query();
    //     $sponsorId = $request->user_id;

    //     if ($request->fromDate) {
    //         $fromDate = \Carbon\Carbon::createFromFormat('Y-m-d', $request->fromDate)->startOfDay();
    //         $query->where('created_at', '>=', $fromDate);
    //     }
    
    //     // Filter by toDate
    //     if ($request->toDate) {
    
    //         $toDate = \Carbon\Carbon::createFromFormat('Y-m-d', $request->toDate)->endOfDay();
    //         $query->where('created_at', '<=', $toDate);
    //     }
    
    //     // Filter by status
    //     if ($request->status) {
    //         $query->where('status', $request->status);
    //     }
    
    //     $directMembers = User::where('sponsor_id', $sponsorId)->pluck('id');
    //     $allMemberIds = $directMembers->toArray();
    //     $this->getDownlineMembers($directMembers, $allMemberIds);
    //     $query->whereIn('id', $allMemberIds);
        
    //     $customers = $query->orderby('created_at','DESC')->get();
        
    //     return $this->sendResponse($customers, 'Retrieve successfully.');

    // }
    // public function view_downline(Request $request)
    // {
    //     // Validate the request input
    //     $validator = Validator::make($request->all(), [
    //         'user_id' => 'required|exists:users,user_id',
    //         'fromDate' => 'nullable|date_format:Y-m-d', // Validate date format
    //         'toDate' => 'nullable|date_format:Y-m-d',   // Validate date format
    //         'status' => 'nullable|string',
    //     ]);

    //     // If validation fails, return the first error
    //     if ($validator->fails()) {
    //         return $this->sendError($validator->errors()->first());
    //     }

    //     // Find the sponsor user
    //     $user = User::where('user_id', $request->user_id)->select('id', 'user_id', 'name', 'phone', 'created_at', 'activated_date', 'sponsor_id', 'status')->first();

    //     // Function to get all referrals recursively
    //     $all_members = collect();
    //     $getAllReferrals = function($user) use (&$getAllReferrals, &$all_members) {
    //         // Fetch direct referrals
    //         $directReferrals = $user->directReferrals()->select('user_id', 'created_at', 'status')->get();
            
    //         // Merge direct referrals into the collection
    //         $all_members = $all_members->merge($directReferrals);
    //         // Recursively get referrals of referrals
    //         foreach ($directReferrals as $referral) {
    //             $getAllReferrals($referral); // Call recursively
    //         }
    //     };

    //     // Start recursion from the given user
    //     $getAllReferrals($user);

    //     // Apply additional filters, if necessary
    //     $filtered_members = $all_members;

    //     if ($request->filled('fromDate')) {
    //         $fromDate = \Carbon\Carbon::createFromFormat('Y-m-d', $request->fromDate)->startOfDay();
    //         $filtered_members = $filtered_members->filter(function ($user) use ($fromDate) {
    //             return $user->created_at >= $fromDate;
    //         });
    //     }

    //     if ($request->filled('toDate')) {
    //         $toDate = \Carbon\Carbon::createFromFormat('Y-m-d', $request->toDate)->endOfDay();
    //         $filtered_members = $filtered_members->filter(function ($user) use ($toDate) {
    //             return $user->created_at <= $toDate;
    //         });
    //     }

    //     if ($request->filled('status')) {
    //         $filtered_members = $filtered_members->filter(function ($user) use ($request) {
    //             return $user->status === $request->status;
    //         });
    //     }

    //     // Convert to a collection of user_ids only
    //     $filtered_user_ids = $filtered_members->pluck('user_id')->values(); // Reset the keys

    //     // Return the filtered list of user IDs
    //     return $this->sendResponse($filtered_user_ids, 'Retrieved successfully.');
    // }

 
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
            'subject'       => 'required|string|max:255',
            'department_id' => 'required',
            'priority' => 'required',
            'user_message' => 'required',
            'user_image'    => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120'
        ], [
                'user_image.image'   => 'The file must be an image (jpeg, png, jpg, gif).',
                'user_image.mimes'   => 'Only jpeg, png, jpg, and gif images are allowed.',
                'user_image.max'     => 'The image size must not exceed 5MB.'
            ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first());
        }
        try{

                $obj = new Support();
                $obj->user_id = $request->user_id;
                $obj->subject = $request->subject;
                $obj->department_id = $request->department_id;
                $obj->priority = $request->priority;
                $obj->user_message = $request->user_message;
                if ($request->hasFile('user_image')) {
                    // Generate a unique file name
                    $ext = $request->file('user_image')->extension();
                    $rand_value = md5(mt_rand(11111111, 99999999));
                    $final_name = $rand_value . '.' . $ext;
        
                    // Move the uploaded file to the user photos directory
                    $request->file('user_image')->move(public_path('uploads/support-form/'), $final_name);
        
                    // Save the image name in the database
                    $obj->user_image = $final_name;
                }
                if ($obj->save()) {
                    return $this->sendResponse($obj, 'Form submitted successfully.');
                } else {
                    return $this->sendError('Oops! Unable to submit form. Please try again.');
                }
        }catch (\Exception $e) {
        Log::error('Form submission error: ', ['error' => $e->getMessage()]);

            return $this->sendError('Oops! Something went wrong. Please try again.');
        }
    }

    public function help_history(Request $request) 
{
    // Validate the request
    $validator = Validator::make($request->all(), [
        'user_id' => 'required|exists:users,user_id',
        'fromDate' => 'nullable|date',
        'toDate' => 'nullable|date',
    ]);

    if ($validator->fails()) {
        return $this->sendError($validator->errors()->first());
    }

    try {
        $user_id = $request->user_id;

        // Create query for HelpStar data
        $query = HelpStar::where('sender_id', $user_id)->whereNotNull('confirm_date')
                ->select('id', 'sender_id', 'receiver_id', 'amount', 'confirm_date', 'created_at','status');

        // Filter by fromDate if provided
        if ($request->filled('fromDate')) {
            $fromDate = \Carbon\Carbon::parse($request->fromDate)->startOfDay();
            $query->where('created_at', '>=', $fromDate);
        }

        // Filter by toDate if provided
        if ($request->filled('toDate')) {
            $toDate = \Carbon\Carbon::parse($request->toDate)->endOfDay();
            $query->where('created_at', '<=', $toDate);
        }

        // Execute query and retrieve results
        $data = $query->get();

        // Map over the results to add sponsor and member names
        $data->map(function ($record) use ($user_id) {
            $record->sponsor_name = $this->get_name($record->receiver_id);
            $record->member_name = $this->get_name($user_id);
            return $record;
        });

        return $this->sendResponse($data, 'Data retrieved successfully.');

    } catch (\Exception $e) {
        return $this->sendError('Oops! Something went wrong. Please try again.');
    }
}

// S.No.	Sender	Sponsor	Amount	Date	Status	Payment Slip	Transaction No	Narration
    public function taking_help(Request $request) 
    {
      
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,user_id',
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first());
        }
        try{
            $user_id = $request->user_id;
            $data = HelpStar::where('receiver_id', $user_id)
                ->select('id', 'sender_id', 'receiver_id', 'amount', 'commitment_date', 'confirm_date', 'status')
                ->with('senderData', 'receiverByData')
                ->get();
            
            // Initialize an empty array for the response
            $response = [];
            
            foreach ($data as $item) {
                $response[] = [
                    'id' => $item->id,
                    'sender_name' => $item->senderData->name ?? null,
                    'sender_phone' => $item->senderData->phone ?? null,
                    'sender_sponsor_id' => $item->senderData->sponsor_id ?? null,
                    'sender_sponsor_name' => $item->senderData->sponsor->name ?? null, // Assuming you have a relationship to fetch sponsor data
                    'sender_sponsor_phone' => $item->senderData->sponsor->phone ?? null, // Assuming you have a relationship to fetch sponsor data
                    'amount' => $item->amount,
                    'commitment_date' => $item->commitment_date,
                    'confirm_date' => $item->confirm_date,
                    'status' => $item->status,
                ];
            }
            
            // Return the response
            return $this->sendResponse($response, 'Data retrieved successfully.');
            
             
        }catch (\Exception $e) {
            return $e;
            return $this->sendError('Oops! Something went wrong. Please try again.');
        }
    }

    // public function view_epin(Request $request){
    //     $validator = Validator::make($request->all(), [
    //         'user_id' => 'required|exists:users,user_id',
    //     ]);
    //     if ($validator->fails()) {
    //         return $this->sendError($validator->errors()->first());
    //     }
    //     try{
    //         $user_id = $request->user_id;
    //     $data = EPinTransfer::orderBy('id', 'desc')->where('member_id',$user_id)->with('MemberData','providedByData','EpinUsed')->get();
      
    //         return $this->sendResponse($data, 'Data Retrieve successfully.');
             
    //     }catch (\Exception $e) {
    //         return $this->sendError('Oops! Something went wrong. Please try again.');
    //     }
    // }

    public function view_epin(Request $request)
    {
        // Validate the request input
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,user_id',
        ]);
        
        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first());
        }

        try {
            $user_id = $request->user_id;
            $user = User::where('user_id', $user_id)->select('user_id','name')->first();

            // Retrieve e-pin transfer data
            $epinTransfers = EPinTransfer::orderBy('id', 'desc')
                ->where('member_id', $user_id)
                ->where('is_used','1')
                ->get();

            // Collect the e_pins from the EPinTransfer records
            $ePins = $epinTransfers->pluck('e_pin')->toArray();

            // Retrieve users who have used these e_pins
            $usersUsingEPin = User::whereIn('registration_code', $ePins)->select('user_id', 'name', 'email', 'registration_code')->get();

        // Build response data
        $response = $epinTransfers->map(function($epinTransfer) use ($usersUsingEPin,$user) {
            // Find the user who used the e-pin
            $usedBy = $usersUsingEPin->firstWhere('registration_code', $epinTransfer->e_pin);

            return [
                'e_pin' => $epinTransfer->e_pin,
                'user_id' => $user->user_id,
                'user_name' => $user->name,
                'status' => 'used',
                'e_pin_created_date' =>date('d M,y', strtotime($epinTransfer->created_at)), 
                'used_by_user_id' => $usedBy ? $usedBy->user_id : null,
                'used_by_user_name' => $usedBy ? $usedBy->name : null,
            ];
        });
         

            return $this->sendResponse($response, 'Data retrieved successfully.');

        } catch (\Exception $e) {
            return $this->sendError('Oops! Something went wrong. Please try again.',$e->getMessage());
        }
    }


    public function total_available_pin(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,user_id',
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first());
        }
        try{
            $user_id = $request->user_id;
        $data = EPinTransfer::orderBy('id', 'desc')->where('member_id',$user_id)->where('is_used','0')->with('MemberData','providedByData','EpinUsed')->count();
      
            return $this->sendResponse($data, 'Data Retrieve successfully.');
             
        }catch (\Exception $e) {
            return $this->sendError('Oops! Something went wrong. Please try again.');
        }
    }

    // public function epin_transfer(Request $request){
    //     $validator = Validator::make($request->all(), [
    //         'user_id' => 'required|exists:users,user_id',
    //         'member_id' => 'required|exists:users,user_id',
    //         'quantity' => 'required|integer|min:1',
    //     ]);
    //     if ($validator->fails()) {
    //         return $this->sendError($validator->errors()->first());
    //     }
    //     try{
    //         $user_id = $request->user_id;
    //         $member_id = $request->member_id;
    //         $quantity = $request->quantity;
    //         $member_name = $user = User::where('user_id',$user_id)->value('name');  

    //         $epins = EPinTransfer::orderBy('id', 'asc')->take($quantity)->take($quantity)->get();
    //         // Step 3: Fetch the exact number of EPinTransfer records to be updated
    //         $epin_transfers = EPinTransfer::where('member_id', $user_id)
    //                             ->where('is_used', '0')
    //                             ->orderBy('id', 'asc') // Ensure the order is consistent
    //                             ->take($quantity)
    //                             ->get();
    
    //         // Step 4: Update the EPinTransfer records
    //         foreach ($epin_transfers as $index => $transfer) {
    //             if (isset($epins[$index])) {
    //                 $epin = $epins[$index]; // Get the corresponding EPin record
    
    //                 // Update the EPinTransfer record with data from the EPin
    //                 $transfer->member_id = $member_id;
    //                 $transfer->provided_by = $user_id;
    //                 $transfer->member_name = $member_name;
    //                 $transfer->balance = $epin->balance;
    //                 $transfer->quantity = 1;
    //                 $transfer->status = $epin->status;
    //                 $transfer->flag = $epin->flag;
    //                 $transfer->e_pin = $epin->e_pin;
    //                 $transfer->save(); // Save the updated EPinTransfer
    
    //             }
    //         }
      
    //         return $this->sendResponse($transfer, 'Data Retrieve successfully.');
             
    //     }catch (\Exception $e) {
    //         dd($e);
    //         return $this->sendError('Oops! Something went wrong. Please try again.');
    //     }
    // }
    
    public function epin_transfer(Request $request){
        // Validate the input data
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,user_id',
            'member_id' => 'required|exists:users,user_id',
            'quantity' => 'required|integer|min:1',
        ]);
    
        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first());
        }
    
        try {
            $user_id = $request->user_id;
            $member_id = $request->member_id;
            $quantity = $request->quantity;
    
            // Fetch the user name for the provided user_id
            $member_name = User::where('user_id', $user_id)->value('name');  
    
            // Fetch the EPinTransfer records that need to be transferred
            $epin_transfers = EPinTransfer::where('member_id', $user_id)
                                ->where('is_used', '0')
                                ->orderBy('id', 'asc') // Get the earliest unused pins
                                ->take($quantity)
                                ->get();
    
            if ($epin_transfers->isEmpty()) {
                return $this->sendError('No available pins to transfer.');
            }
    
            // Fetch the actual EPin details
            $epins = EPinTransfer::orderBy('id', 'asc')
                                ->take($quantity)
                                ->get();
    
            // Initialize variable to avoid undefined variable issue
            $updated_transfers = [];
    
            // Update the EPinTransfer records with the corresponding EPin details
            foreach ($epin_transfers as $index => $transfer) {
                if (isset($epins[$index])) {
                    $epin = $epins[$index]; // Get the corresponding EPin record
    
                    // Update the EPinTransfer record
                    $transfer->member_id = $member_id;
                    $transfer->provided_by = $user_id;
                    $transfer->member_name = $member_name;
                    $transfer->balance = $epin->balance;
                    $transfer->quantity = 1; // Set the quantity to 1 as requested
                    $transfer->status = $epin->status;
                    $transfer->flag = $epin->flag;
                    $transfer->e_pin = $epin->e_pin;
                    $transfer->save(); // Save the updated EPinTransfer
    
                    // Collect the updated transfer records
                    $updated_transfers[] = $transfer;
                }
            }
    
            return $this->sendResponse($updated_transfers, 'EPin transferred successfully.');
            
        } catch (\Exception $e) {
            // Log the exception for debugging (optional)
            // Log::error($e->getMessage());
    
            return $this->sendError('Oops! Something went wrong. Please try again.');
        }
    }
    
    public function send_email_phone_otp(Request $request){
        $validator = Validator::make($request->all(), [
            'email_or_mobile' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first());
        }
        $otpNo    = rand(100000, 999999);
        $user_email_phone = $request['email_or_mobile'];
        $obj = User::where(['phone' => $user_email_phone])->orWhere(['email' => $user_email_phone])->first();
        // dd($obj->email);
        if($obj && $obj->email){
            $obj->otp = $otpNo;
            try{
                $mailBody = [
                    'name'        => @$obj->name,
                    'email'       => @$obj->email,
                    'phone'      => @$obj->mobile,
                    'otp'      => @$otpNo,
                ];
                    $mobileNo = $obj->mobile;
                    $expert_id = $obj->user_id;
                if ($obj->save()) {
                    $success['name']  = $obj->name;
                    $success['email'] = $obj->email;

                    Mail::to($obj->email)->send(new AppPasswordResendMail($mailBody));
                    // return $this->sendResponse($success, 'Otp resent successfully.');
                    $success['mobile'] = $mobileNo;
                    $success['otp'] = $otpNo;
                    $success['expert_id'] = $expert_id;
                    return $this->sendResponse($success, 'Otp resent successfully.');
                } else {
                    return $this->sendError('Oops! Unable to resend. Please try again.');
                }
            }catch (\Exception $e) {
                dd($e);
                return $this->sendError('Oops! Unable to resend. Please try again.');

            }
            }else{
                return $this->sendError('Oops! User not found. Please try again.');
        }
    }

    public function verify_otp(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id'    => 'required',
            'otp' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first());
        }

        $obj = User::where('user_id', $request->user_id)->Where(['otp' => $request->otp])->first();
        if ($obj) {

            $success = "";
            return $this->sendResponse($success, 'Otp Verify successfully.');
        } else {
            return $this->sendError('Oops! Unable to verify otp. Please try again.');
        }
    }

    public function forgetPassword(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,user_id', 
            'otp' => 'required',
            'password' => 'required|confirmed',
            'password_confirmation' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first());
        }

        $obj = User::where('user_id', $request->user_id)->Where(['otp' => $request->otp])->first();
        if($obj){
        $obj->password = Hash::make($request->password);
        }else{
            return $this->sendError('Oops! Unable to  update password. Please try again.');
        }
        if ($obj->save()) {
            $success = "";
            return $this->sendResponse($success, 'Password updated successfully.');
        } else {
            return $this->sendError('Oops! Unable to  update password. Please try again.');
        }
    }
    // public function seven_level_confirmation(Request $request){
    //     $validator = Validator::make($request->all(), [
    //         'user_id' => 'required|exists:users,user_id', 
    //         'level_key' => 'required',
    //         'id' => 'required',
    //     ]);
    //     if ($validator->fails()) {
    //         return $this->sendError($validator->errors()->first());
    //     }
    //     $level_key = $request->level_key;
    //     $status = $level_key.'_status';
    //     $confirm_date = $level_key.'_confirm_date';
    //     $level_key = $request->level_key;
    //     $user_id = $request->user_id;
    //     $id = $request->id;
    //     $seven_level_transaction = SevenLevelTransaction::where('id', $id)->where($level_key, $user_id)
    //     ->select($status,$confirm_date)
    //     ->first();
    //     if($seven_level_transaction){
    //         $seven_level_transaction->$confirm_date = now();
    //         $seven_level_transaction->$status = '1';
    //         $seven_level_transaction->save()
    //     }
    // }
    public function seven_level_confirmation(Request $request) {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,user_id', 
            'level_key' => 'required',
            'id' => 'required',
        ]);
    
        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first());
        }
    
        $level_key = $request->level_key;
        $status = $level_key . '_status';
        $confirm_date = $level_key . '_confirm_date';
        $user_id = $request->user_id;
        $id = $request->id;
    try{

        // Find the SevenLevelTransaction where the specified level matches the user_id
        $seven_level_transaction = SevenLevelTransaction::where('id', $id)
            ->where($level_key, $user_id)
            ->select('id',$status, $confirm_date)
            ->first();
        if ($seven_level_transaction) {
            // Update the confirmation date and status
            // $seven_level_transaction->$confirm_date = now();
            // $seven_level_transaction->$status = 1;
               // Update the confirmation date and status
               $seven_level_transaction->{$confirm_date} = now();
               $seven_level_transaction->{$status} = 1;
            if($seven_level_transaction->save()){

                return $this->sendResponse([], 'Level confirmation updated successfully.');
            }  // Fixed the missing semicolon
        } else {
            return $this->sendError('Transaction not found or invalid level/user ID.');
        }
    }catch(Exception $e){
        return $this->sendError('Transaction not found or invalid level/user ID.');

    }
}

    public function giving_person_confirmation(Request $request){
        $validator = Validator::make($request->all(),[
            'user_id' => 'required|exists:users,user_id',
            'id' => 'required|exists:help_star,id',
            'status' => 'required|in:Active,Pending,Rejected',
        ]);
        if($validator->fails()){
            return $this->sendError('Validation Error.',$validator->errors());
        }
       
        $user_id = $request->user_id;
        $id = $request->id;
        $status = $request->status;
    // Fetch the HelpStar data based on the given user_id
    $help_stars = HelpStar::where('receiver_id', $user_id)->where('id',$id)
                ->select('id','sender_id', 'receiver_id', 'amount', 'commitment_date', 'confirm_date', 'status')
                ->first();

    // Ensure that the $help_stars collection is not empty before proceeding
    if (isset($help_stars)) {
        // Map over the $help_stars collection to append sender and receiver details
        $help_stars->confirm_date = now();
        $help_stars->status = $status;
        $help_stars->save();
        return $this->sendResponse($help_stars, 'User Data Retrieve Successfully.');

    }

    return null; // Return null if no data is found
}
    
public function view_downline(Request $request)
{
    // Validate the request input
    $validator = Validator::make($request->all(), [
        'user_id' => 'required|exists:users,user_id',
    ]);

    // If validation fails, return the first error
    if ($validator->fails()) {
        return $this->sendError($validator->errors()->first());
    }

    // Fetch the main user based on user_id
    $user = User::where('user_id', $request->user_id)->first();

    // Initialize collection for all downline members
    $allDownlineMembers = collect();

    // Get all downline members (direct and indirect)
    $this->getDownlinesRecursive($user, $allDownlineMembers);

    // Apply filters (if any)
    if ($request->filled('fromDate')) {
        $fromDate = \Carbon\Carbon::createFromFormat('Y-m-d', $request->fromDate)->startOfDay();
        $allDownlineMembers = $allDownlineMembers->filter(function ($member) use ($fromDate) {
            return $member->created_at >= $fromDate;
        });
    }

    if ($request->filled('toDate')) {
        $toDate = \Carbon\Carbon::createFromFormat('Y-m-d', $request->toDate)->endOfDay();
        $allDownlineMembers = $allDownlineMembers->filter(function ($member) use ($toDate) {
            return $member->created_at <= $toDate;
        });
    }

    if ($request->filled('status')) {
        $allDownlineMembers = $allDownlineMembers->filter(function ($member) use ($request) {
            return $member->status === $request->status;
        });
    }

    // Return the filtered list of all downline members
    return $this->sendResponse($allDownlineMembers->values(), 'Retrieved successfully.');
}

/**
 * Recursively fetch all downlines (direct and indirect)
 */
private function getDownlinesRecursive($user, &$allDownlineMembers)
{
    // Get direct referrals of the user
    $directReferrals = $user->directReferrals()->get();

    foreach ($directReferrals as $referral) {
        // Add this referral to the collection
        $allDownlineMembers->push($referral);

        // Recursively get downlines of this referral
        $this->getDownlinesRecursive($referral, $allDownlineMembers);
    }
}

private function getDownlineMemberss($directMembers, &$allMemberIds)
    {
        // Get all users with sponsor_id in the list of directMembers
        $downlineMembers = User::whereIn('sponsor_id', $directMembers)->pluck('id');
        // If there are downline members, process them
        if ($downlineMembers->isNotEmpty()) {
            // Add downline members to the allMemberIds array
            $allMemberIds = array_merge($allMemberIds, $downlineMembers->toArray());
    
            // Recurse to get further downline members
            $this->getDownlineMemberss($downlineMembers, $allMemberIds);
        }
    }
/**
 * Function to recursively fetch all downlines for a user.
 */
private function getAllDownlinesRecursive($user) {
    // Fetch direct referrals (downlines) for this user
    $downlines = User::where('sponsor_id', $user->user_id)->get();

    // Recursively fetch their downlines
    foreach ($downlines as $downline) {
        $downlines = $downlines->merge($this->getAllDownlinesRecursive($downline));
    }

    return $downlines;
}

}