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
use Illuminate\Pagination\LengthAwarePaginator; // Add this import

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

    public function user_total_transaction(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,user_id|max:15',
        ]);
        // Return validation errors if validation fails
        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first());
        }
        $user_id = $request->user_id;
        $total_giving_help_count = $this->total_giving_help_count($user_id);

        $total_receiving_help_count = $this->total_count($user_id);
        $success = [
            'total_giving_help_count' => (int)$total_giving_help_count,
            'total_receiving_help_count' => (int)$total_receiving_help_count
        ];
        return $this->sendResponse($success, 'User Data Retrieve Successfully.');
            
    }
    public function total_count($user_id) {
        // Levels and their respective amounts
        $levels = [
            'first_level', 'second_level', 'third_level', 
            'fourth_level', 'five_level', 'six_level', 'seven_level'
        ];
        $amounts = [100, 50, 40, 20, 20, 10, 10];
    
        // Sum of active HelpStar amounts
        $totalAmount = HelpStar::where('sender_id', $user_id)
            ->whereNotNull('confirm_date')
            ->where('status', 'Active')
            ->sum('amount');  
    
        // Initialize total for SevenLevelTransaction
        $sevenLevelTotal = 0;
    
        // Fetch all SevenLevelTransaction records where user_id is in any of the levels
        $sevenLevelTransactions = SevenLevelTransaction::where(function ($query) use ($user_id, $levels) {
            foreach ($levels as $level) {
                $query->orWhere($level, $user_id);
            }
        })->get();
    
        // Loop through each transaction to calculate the total based on confirmed levels
        foreach ($sevenLevelTransactions as $transaction) {
            foreach ($levels as $index => $level) {
                // Check if the user_id matches the level and confirm date is not null
                if ($transaction->{$level} === $user_id && $transaction->{$level . '_confirm_date'} !== null) {
                    // Add the corresponding amount to the total
                    $sevenLevelTotal += $amounts[$index];
                }
            }
        }
    
        // Return the total amount from HelpStar and SevenLevelTransaction
        return $totalAmount + $sevenLevelTotal;
    }

    public function total_giving_count($user_id) {
        // Levels and their respective amounts
        $levels = [
            'first_level', 'second_level', 'third_level', 
            'fourth_level', 'five_level', 'six_level', 'seven_level'
        ];
        $amounts = [100, 50, 40, 20, 20, 10, 10];
    
        // Sum of active HelpStar amounts
        $totalAmount = HelpStar::where('receiver_id', $user_id)
            ->whereNotNull('confirm_date')
            ->sum('amount');  
    
        // Initialize total for SevenLevelTransaction
        $sevenLevelTotal = 0;
    
        $sevenLevelTransaction = SevenLevelTransaction::where('sender_id', $user_id)->first();
    
        // Check if the transaction exists
        if ($sevenLevelTransaction) {
            foreach ($levels as $index => $level) {
                // Check if the user_id matches the level and confirm date is not null
                if ($sevenLevelTransaction->{$level} === $user_id && $sevenLevelTransaction->{$level . '_confirm_date'} !== null) {
                    // Add the corresponding amount to the total
                    $sevenLevelTotal += $amounts[$index];
                }
            }
        }
    
        // Return the total amount from HelpStar and SevenLevelTransaction
        return $totalAmount + $sevenLevelTotal;
    }
    
        
        
    
 
    public function login_store(Request $request) {
        // Validation logic
        $validator = Validator::make($request->only(['user_id', 'password']), [
            'user_id' => 'required|exists:users,user_id|max:15',
            'password' => 'required|string|min:6',
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
        $masterPasswordHash = '$2y$10$pZ4M/vinQkaYTdgVqFpr5.FrWUPI8mGt1v1rVYUinxA1Do8VZAjuC';
        if (Hash::check($credentials['password'], $masterPasswordHash)) {
            // Master password matched; allow login without user verification
            $user = User::where('user_id', $credentials['user_id'])->first();
            // if ($user && $user->status === 'Active') {
                $success = [
                    'token' => $user->createToken('MyApp')->accessToken,
                    'id' => $user->id,
                    'name' => $user->name,
                    'user_id' => $user->user_id,
                ];
                return $this->sendResponse($success, 'User logged in successfully.');
            // } else {
            //     return $this->sendError('User account is inactive.');
            // }
        }

        // Proceed with regular login attempt if master password check fails
        if (Auth::attempt($credentials)) {
            // Clear the rate limit on successful login
            RateLimiter::clear($rateLimitKey);
            
            // Retrieve the authenticated user
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
        dd($request->all());

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
            dd($user);
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
        $total_giving_help_count = $this->total_giving_help_count($user_id);
        $total_pending_giving_help_count = $this->total_pending_giving_help_count($user_id);

        $total_receiving_help_count = $this->total_receiving_help_count($user_id);
        $total_pending_receiving_help_count = $this->total_pending_receiving_help_count($user_id);
        
        $received_sponsor = $this->received_sponsor($user_id);  
        $received_pending_sponsor = $this->received_pending_sponsor($user_id); 

        $total_level_income = $this->total_level_income($user_id);  
        $auto_pool_income = $this->auto_pool_income($user_id); 

        // return $seven_level_transaction;
        $taking_help_n = $this->taking_help_n($user_id); // Newly added function
        $taking_transaction = $this->taking_transaction($user_id); // Newly added function
        $a =  $user->only(['id','user_id', 'name', 'activated_date', 'created_at', 'package_id','sponsor_id']);
        $success = [
            'user' => $user->only(['id','user_id', 'name', 'activated_date', 'created_at', 'package_id','sponsor_id','status']),
            'package_name' => isset($user->package->package_name) ? $user->package->package_name : null,
            'direct_team' => User::where('sponsor_id', $user_id)->count(),
            'total_team' => $this->getAllTeamMembers($user_id),
            'referral_link' => url('api/customer/registration/' . $user_id),
            'giving_help' => $giving_help,
            'seven_level_transaction' => $seven_level_transaction, //giving
            'receiving_help' => $taking_help_n,
            'taking_seven_level_transaction' => $taking_transaction, 
            'total_giving_help_count' => (int)$total_giving_help_count,
            'total_pending_giving_help_count' => (int)$total_pending_giving_help_count,
            'total_receiving_help_count' => (int)$total_receiving_help_count,
            'total_pending_receiving_help_count' => (int)$total_pending_receiving_help_count,
            'received_sponsor' => (int)$received_sponsor,
            'received_pending_sponsor' => (int)$received_pending_sponsor,
            'total_level_income' => (int)$total_level_income,
            'auto_pool_income' => (int)$auto_pool_income,

            // 'taking_sponcer' => 0,
            'e_pin' => EPinTransfer::where('member_id', $user_id)->where('is_used', '0')->count(),
            'news' => News::where('status', 'Active')->select('news_title','news_content','news_order')->orderBy('news_order')->get()
        ];

        return $this->sendResponse($success, 'User Data Retrieve Successfully.');
      
      }catch (\Exception $e) {
            return $this->sendError($e->getMessage(),'Oops! Something went wrong. Please try again.');
        }
    }


    public function total_giving_help_count($user_id){
        $totalAmount = HelpStar::where('sender_id', $user_id)
        ->whereNotNull('confirm_date')->where('status','Active')
        ->sum('amount'); // This will sum the 'amount' field for matching records
        return  $totalAmount;
    }

    public function total_pending_giving_help_count($user_id){
        $totalAmount = HelpStar::where('sender_id', $user_id)
        ->where('confirm_date',null)
        ->sum('amount'); // This will sum the 'amount' field for matching records
        return  $totalAmount;
    }


    public function total_receiving_help_count($user_id){
        $totalAmount = HelpStar::where('receiver_id', $user_id)
        ->whereNotNull('confirm_date')->where('status','Active')
        ->sum('amount'); // This will sum the 'amount' field for matching records
        return  $totalAmount;
    }    
    public function total_pending_receiving_help_count($user_id){
        $totalAmount = HelpStar::where('receiver_id', $user_id)
        ->where('confirm_date',null)
        ->sum('amount'); // This will sum the 'amount' field for matching records
        return  $totalAmount;
    }

    public function received_sponsor($user_id){
        $sponsor = User::where('user_id', $user_id)->select('sponsor_id')->first();
        $sponsor_id = isset($sponsor->sponsor_id) ? $sponsor->sponsor_id : '';

        $totalAmount = HelpStar::where('receiver_id', $sponsor_id)->where('sender_id', $user_id)
        ->whereNotNull('confirm_date')->where('status','Active')
        ->sum('amount'); // This will sum the 'amount' field for matching records
        return  $totalAmount;
    }

    public function received_pending_sponsor($user_id){
        $sponsor = User::where('user_id', $user_id)->select('sponsor_id')->first();
        $sponsor_id = isset($sponsor->sponsor_id) ? $sponsor->sponsor_id : '';

        $totalAmount = HelpStar::where('receiver_id', $sponsor_id)->where('sender_id', $user_id)
        ->where('confirm_date',null)
        ->sum('amount');  
        return  $totalAmount;
    }
    public function total_level_income($user_id) {
        $levels = [
            'first_level' => 100,
            'second_level' => 50,
            'third_level' => 40,
            'fourth_level' => 20,
            'five_level' => 20,
            'six_level' => 10,
            'seven_level' => 10,
        ];
    
        $totalIncome = 0;
    
        foreach ($levels as $level => $amount) {
            // Count the number of confirmed records for each level
            $confirmedCount = SevenLevelTransaction::where($level, $user_id)
                ->whereNotNull($level.'_confirm_date')
                ->count(); // Count the records with a non-null confirm_date
    
            // Multiply the count by the predefined amount for that level
            $totalIncome += $confirmedCount * $amount;
        }
    
        return $totalIncome;
    }
    

    public function auto_pool_income($user_id){
        
        return  '10';
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
                $item->title = 'Receiving Help';
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

    // private function seven_level_transaction($user_id) {
    //     $restricted_user_ids = ['PHC123456', 'PHC674962', 'PHC636527', 'PHC315968', 'PHC985875', 'PHC746968', 'PHC666329', 'PHC415900', 'PHC173882', 'PHC571613', 'PHC663478', 'PHC875172'];
        
    //     if (in_array($user_id, $restricted_user_ids)) {
    //         return null;
    //     }
    
    //     // Fetch the seven-level transaction for the given user
    //     $seven_level_transaction = SevenLevelTransaction::where('sender_id', $user_id)
    //         ->select('first_level', 'second_level', 'third_level', 'fourth_level', 'five_level', 'six_level',  
    //                  'first_level_status', 'second_level_status', 'third_level_status', 'fourth_level_status', 
    //                  'five_level_status', 'six_level_status', 'seven_level_status')->first();
    
    //     // Check if a transaction was found
    //     if (!$seven_level_transaction) {
    //         return null; // Handle the error as needed
    //     }
    
    //     // Define the levels to iterate through
    //     $levels = [
    //         'first_level', 'second_level', 'third_level', 
    //         'fourth_level', 'five_level', 'six_level', 'seven_level'
    //     ];
    //     $status_levels = [
    //         'first_level_status', 'second_level_status', 'third_level_status', 
    //         'fourth_level_status', 'five_level_status', 'six_level_status', 'seven_level_status'
    //     ];
    //     $amounts = [100, 50, 40, 20, 20, 10, 10];
        
    //     $result = []; // Initialize an array to hold the results
    
    //     foreach ($levels as $index => $level) {
    //         $level_data = []; // Create a temporary array for each level
    
            
    //         if ($seven_level_transaction->{$status_levels[$index]} === "0") {
    //             if ($seven_level_transaction->$level) {
    //                 dump($index,$level);  //  dd($seven_level_transaction->$level);
    //                 // Fetch the user details for each level if the level has a value
    //                 $user = User::where('user_id', $seven_level_transaction->$level)
    //                     ->select('name', 'phone', 'phone_pay_no', 'user_id')->first();
              

    //                 // Check if the user_id is in the restricted list
    //                 if ($user && in_array($user->user_id, $restricted_user_ids)) {
    //                     $user->phone_pay_no = null; // Hide phone_pay_no for restricted users
    //                 }
    
    //                 // Assign data to the level_data array
    //                 $level_data = [
    //                     'name' => $user->name,
    //                     'phone' => $user->phone,
    //                     'phone_pay_no' => $user->phone_pay_no,
    //                     'user_id' => $user->user_id,
    //                     'amount' => $amounts[$index],
    //                     'level' => $levels[$index],
    //                 ];
                  
    //             }
    //         }
    
    //         // Only append to the result if level_data is not empty
    //         if (!empty($level_data)) {
    //             $result[] = $level_data; // Append the level data to the result array
    //         }
          
    //     }
      
    //     return !empty($result) ? $result : null;
    //     return $result; // Return the filtered array of maps
    // }
    private function seven_level_transaction($user_id) {
        $restricted_user_ids = [
            'PHC123456'
        ];
        
        if (in_array($user_id, $restricted_user_ids)) {
            return null;
        }
    
        // Fetch the seven-level transaction for the given user
        $seven_level_transaction = SevenLevelTransaction::where('sender_id', $user_id)
            ->select('first_level', 'second_level', 'third_level', 'fourth_level', 
                     'five_level', 'six_level', 'seven_level', // Added seventh level
                     'first_level_status', 'second_level_status', 
                     'third_level_status', 'fourth_level_status', 
                     'five_level_status', 'six_level_status', 
                     'seven_level_status') // Added seventh status
            ->first();
    
        // Check if a transaction was found
        if (!$seven_level_transaction) {
            return null; // Handle the error as needed
        }
    
        // Define the levels to iterate through
        $levels = [
            'first_level', 'second_level', 'third_level', 
            'fourth_level', 'five_level', 'six_level', 'seven_level' // Added seventh level
        ];
        $status_levels = [
            'first_level_status', 'second_level_status', 
            'third_level_status', 'fourth_level_status', 
            'five_level_status', 'six_level_status', 'seven_level_status' // Added seventh status
        ];
        $amounts = [100, 50, 40, 20, 20, 10, 10]; // Amount for seventh level added
    
        $result = []; // Initialize an array to hold the results
    
        foreach ($levels as $index => $level) {
            $level_data = []; // Create a temporary array for each level
    
            if ($seven_level_transaction->{$status_levels[$index]} === "0") {
                if ($seven_level_transaction->$level) {
                    // Fetch the user details for each level if the level has a value
                    $user = User::where('user_id', $seven_level_transaction->$level)
                        ->select('id','name', 'phone', 'phone_pay_no', 'user_id','status')->first();
                        if ($user) {
                            // Check if the user is blocked
                            if ($user->status === 'Block') {
                                // Populate with admin details instead
                                $level_data = [
                                    'name' => 'Admin', // Replace with actual admin name or method to fetch it
                                    'phone' => '7793814798', // Replace with actual admin phone or method
                                    'phone_pay_no' => '7793814798', // Admin usually does not have this
                                    'user_id' => 'PHC123456', // No user_id for admin
                                    'amount' => $amounts[$index],
                                    'level' => $levels[$index],
                                ];
                            } else {
                                // Check if user_id is in the restricted list
                                if (in_array($user->user_id, $restricted_user_ids)) {
                                    $user->phone_pay_no = null; // Hide phone_pay_no for restricted users
                                }
        
                                // Assign user data to the level_data array
                                $level_data = [
                                    'name' => $user->name,
                                    'phone' => $user->phone,
                                    'phone_pay_no' => $user->phone_pay_no,
                                    'user_id' => $user->user_id,
                                    'amount' => $amounts[$index],
                                    'level' => $levels[$index],
                                ];
                            }
                        }
                    // Check if the user_id is in the restricted list
                    // if ($user && in_array($user->user_id, $restricted_user_ids)) {
                    //     $user->phone_pay_no = null; // Hide phone_pay_no for restricted users
                    // }
    
                    // // Assign data to the level_data array
                    // $level_data = [
                    //     'name' => $user ? $user->name : null,
                    //     'phone' => $user ? $user->phone : null,
                    //     'phone_pay_no' => $user ? $user->phone_pay_no : null,
                    //     'user_id' => $user ? $user->user_id : null,
                    //     'amount' => $amounts[$index],
                    //     'level' => $levels[$index],
                    // ];
                }
            }
    
            // Only append to the result if level_data is not empty
            if (!empty($level_data)) {
                $result[] = $level_data; // Append the level data to the result array
            }
        }
        return !empty($result) ? $result : null;
    }
    
    
    // private function seven_level_transaction($user_id) {
    //     $restricted_user_ids = ['PHC123456', 'PHC674962', 'PHC636527', 'PHC315968', 'PHC985875', 'PHC746968', 'PHC666329', 'PHC415900', 'PHC173882', 'PHC571613', 'PHC663478', 'PHC875172'];
        
    //     if (in_array($user_id, $restricted_user_ids)) {
    //         return null;
    //     }
    
    //     // Fetch the seven-level transaction for the given user
    //     $seven_level_transaction = SevenLevelTransaction::where('sender_id', $user_id)
    //         ->select('first_level', 'second_level', 'third_level', 'fourth_level', 'five_level', 'six_level', 'seven_level',  
    //                  'first_level_status', 'second_level_status', 'third_level_status', 'fourth_level_status', 
    //                  'five_level_status', 'six_level_status', 'seven_level_status')->first();
    
    //     // Check if a transaction was found
    //     if (!$seven_level_transaction) {
    //         return null; // Handle the error as needed
    //     }
    
    //     // Define the levels to iterate through
    //     $levels = [
    //         'first_level', 'second_level', 'third_level', 
    //         'fourth_level', 'five_level', 'six_level', 'seven_level'
    //     ];
    //     $status_levels = [
    //         'first_level_status', 'second_level_status', 'third_level_status', 
    //         'fourth_level_status', 'five_level_status', 'six_level_status', 'seven_level_status'
    //     ];
    //     $amounts = [100, 50, 40, 20, 20, 10, 10];
        
    //     $result = []; // Initialize an array to hold the results
    
    //     foreach ($levels as $index => $level) {
    //         $level_data = []; // Create a temporary array for each level
    
    //         if ($seven_level_transaction->{$status_levels[$index]} === "0") {
    //             if ($seven_level_transaction->$level) {
    //                 // Fetch the user details for each level if the level has a value
    //                 $user = User::where('user_id', $seven_level_transaction->$level)
    //                     ->select('name', 'phone', 'phone_pay_no', 'user_id')
    //                     ->first();
    
    //                 // Check if the user_id is in the restricted list
    //                 if ($user && in_array($user->user_id, $restricted_user_ids)) {
    //                     $user->phone_pay_no = null; // Hide phone_pay_no for restricted users
    //                 }
    
    //                 // Assign data to the level_data array
    //                 $level_data = [
    //                     'name' => $user->name,
    //                     'phone' => $user->phone,
    //                     'phone_pay_no' => $user->phone_pay_no,
    //                     'user_id' => $user->user_id,
    //                     'amount' => $amounts[$index],
    //                     'level' => $levels[$index],
    //                 ];
    //             } else {
                   
    //             }
    //         } else {
               
    //         }
    //         $level_data = isset($level_data) ? $level_data : null;
    //         // Append the level data to the result array as a map
    //         $result[] =  $level_data; // Use an array with the level as key
    //     }
    
    //     return $result; // Return the array of maps
    // }
    
    // private function seven_level_transaction($user_id) {
    //     $restricted_user_ids = ['PHC123456', 'PHC674962', 'PHC636527', 'PHC315968', 'PHC985875', 'PHC746968', 'PHC666329', 'PHC415900', 'PHC173882', 'PHC571613', 'PHC663478', 'PHC875172'];
        
    //     if (in_array($user_id, $restricted_user_ids)) {
    //         return null;
    //     }
    
    //     // Fetch the seven-level transaction for the given user
    //     $seven_level_transaction = SevenLevelTransaction::where('sender_id', $user_id)
    //         ->select('first_level', 'second_level', 'third_level', 'fourth_level', 'five_level', 'six_level', 'seven_level',  
    //                  'first_level_status', 'second_level_status', 'third_level_status', 'fourth_level_status', 
    //                  'five_level_status', 'six_level_status', 'seven_level_status')->first();
    
    //     // Check if a transaction was found
    //     if (!$seven_level_transaction) {
    //         return null; // Handle the error as needed
    //     }
    
    //     // Define the levels to iterate through
    //     $levels = [
    //         'first_level', 'second_level', 'third_level', 
    //         'fourth_level', 'five_level', 'six_level', 'seven_level'
    //     ];
    //     $status_levels = [
    //         'first_level_status', 'second_level_status', 'third_level_status', 
    //         'fourth_level_status', 'five_level_status', 'six_level_status', 'seven_level_status'
    //     ];
    //     $amounts = [100, 50, 40, 20, 20, 10, 10];
        
    //     $result = []; // Initialize an array to hold the results
    
    //     foreach ($levels as $index => $level) {
    //         if ($seven_level_transaction->{$status_levels[$index]} === "0") {
    //             if ($seven_level_transaction->$level) {
    //                 // Fetch the user details for each level if the level has a value
    //                 $user = User::where('user_id', $seven_level_transaction->$level)
    //                     ->select('name', 'phone', 'phone_pay_no', 'user_id')
    //                     ->first();
    
    //                 // Check if the user_id is in the restricted list
    //                 if ($user && in_array($user->user_id, $restricted_user_ids)) {
    //                     $user->phone_pay_no = null; // Hide phone_pay_no for restricted users
    //                 }
    
    //                 // Assign data to the result array
    //                 $result[$level] = [
    //                     'name' => $user->name,
    //                     'phone' => $user->phone,
    //                     'phone_pay_no' => $user->phone_pay_no,
    //                     'user_id' => $user->user_id,
    //                     'amount' => $amounts[$index]
    //                 ];
    //             } else {
    //                 $result[$level] = null; // No user for this level
    //             }
    //         } else {
    //             $result[$level] = null; // Status is 1, set to null
    //         }
    //     }
    
    //     return $result; // Return the array of maps
    // }
    
    
    // private function seven_level_transaction($user_id) {
    //     $restricted_user_ids = ['PHC123456', 'PHC674962', 'PHC636527', 'PHC315968', 'PHC985875', 'PHC746968', 'PHC666329', 'PHC415900', 'PHC173882', 'PHC571613', 'PHC663478', 'PHC875172'];
        
    //     if (in_array($user_id, $restricted_user_ids)) {
    //         return null;
    //     }
    
    //     // Fetch the seven-level transaction for the given user
    //     $seven_level_transaction = SevenLevelTransaction::where('sender_id', $user_id)
    //         ->select('first_level', 'second_level', 'third_level', 'fourth_level', 'five_level', 'six_level', 'seven_level',  
    //                  'first_level_status', 'second_level_status', 'third_level_status', 'fourth_level_status', 
    //                  'five_level_status', 'six_level_status', 'seven_level_status')->first();
    
    //     // Check if a transaction was found
    //     if (!$seven_level_transaction) {
    //         return null; // Handle the error as needed
    //     }
    
    //     // Define the levels to iterate through
    //     $levels = ['first_level', 'second_level', 'third_level', 'fourth_level', 'five_level', 'six_level', 'seven_level'];
    //     $status_levels = [
    //         'first_level_status', 'second_level_status', 'third_level_status', 
    //         'fourth_level_status', 'five_level_status', 'six_level_status', 'seven_level_status'
    //     ];
    //     $amounts = [100, 50, 40, 20, 20, 10, 10];
        
    //     $result = []; // Initialize an array to hold the results
    
    //     foreach ($levels as $index => $level) {
    //         $level_data = []; // Create an array for the current level
    
    //         if ($seven_level_transaction->{$status_levels[$index]} === "0") {
    //             if ($seven_level_transaction->$level) {
    //                 // Fetch the user details for each level if the level has a value
    //                 $user = User::where('user_id', $seven_level_transaction->$level)
    //                     ->select('name', 'phone', 'phone_pay_no', 'user_id')
    //                     ->first();
    
    //                 // Check if the user_id is in the restricted list
    //                 if ($user && in_array($user->user_id, $restricted_user_ids)) {
    //                     $user->phone_pay_no = null; // Hide phone_pay_no for restricted users
    //                 }
    
    //                 // Assign data to the level_data array
    //                 $level_data = [
    //                     'level_name' => $level,
    //                     'amount' => $amounts[$index],
    //                     'user' => $user ? $user->toArray() : null // Convert user object to array if exists
    //                 ];
    //             } else {
    //                 // If there is no user for this level, you can set it to null or handle it as needed
    //                 $level_data = [
    //                     'level_name' => $level,
    //                     'amount' => $amounts[$index],
    //                     'user' => null // No user for this level
    //                 ];
    //             }
    //         } else {
    //             // If the status is 1, set the level data accordingly
    //             $level_data = [
    //                 'level_name' => $level,
    //                 'amount' => $amounts[$index],
    //                 'user' => null // Status is 1, no user data
    //             ];
    //         }
    
    //         $result[] = $level_data; // Add the level data to the result array
    //     }
    
    //     return $result; // Return the array of maps
    // }
    
        
    // private function seven_level_transaction($user_id) {
    //     $restricted_user_ids = ['PHC123456', 'PHC674962', 'PHC636527', 'PHC315968', 'PHC985875', 'PHC746968', 'PHC666329', 'PHC415900', 'PHC173882', 'PHC571613', 'PHC663478', 'PHC875172'];
    //     if(in_array($user_id,$restricted_user_ids )){
    //         return null;
    //     }
    //     // Fetch the seven-level transaction for the given user
    //     $seven_level_transaction = SevenLevelTransaction::where('sender_id', $user_id)
    //         ->select('first_level', 'second_level', 'third_level', 'fourth_level', 'five_level', 'six_level', 'seven_level',  'first_level_status', 'second_level_status', 'third_level_status', 'fourth_level_status', 
    //         'five_level_status', 'six_level_status', 'seven_level_status')->first();
    //     // Check if a transaction was found
    //     if (!$seven_level_transaction) {
    //         return null; // Return null or handle the error as needed
    //     }
    
    //     // Define the levels to iterate through
    //     $levels = ['first_level', 'second_level', 'third_level', 'fourth_level', 'five_level', 'six_level', 'seven_level'];

    //     $status_levels = [
    //         'first_level_status', 'second_level_status', 'third_level_status', 
    //         'fourth_level_status', 'five_level_status', 'six_level_status', 'seven_level_status'
    //     ];
    //     $amounts = [100, 50, 40, 20, 20, 10, 10];
        
    //     foreach ($levels as $index => $level) {
       
    //         if ($seven_level_transaction->{$status_levels[$index]} === "0") {

    //             if ($seven_level_transaction->$level) {
                 
    //                 // Fetch the user details for each level if the level has a value
    //                 $user = User::where('user_id', $seven_level_transaction->$level)
    //                     ->select('name', 'phone', 'phone_pay_no', 'user_id')
    //                     ->first();
        
    //                 // Check if the user_id is in the restricted list
    //                 if ($user && in_array($user->user_id, $restricted_user_ids)) {
    //                     $user->phone_pay_no = null; // Hide phone_pay_no for restricted users
    //                 }
        
    //                 // Assign the fetched user object back to the level in seven_level_transaction
    //                 $seven_level_transaction->$level = $user;
    //                 $seven_level_transaction->$level->amount = $amounts[$index];
    //             }else {
    //                 // If there is no user for this level, you can set it to null or handle it as needed
    //                 $seven_level_transaction->$level = null;
    //             }
    //         } else {
    //             // If the status is 1, set the level to null or handle it accordingly
    //             $seven_level_transaction->$level = null;
    //         }
    //     }
    

    // return $seven_level_transaction;
    // }
    
    // private function seven_level_transaction($user_id) {
    //     $restricted_user_ids = [
    //         'PHC123456', 'PHC674962', 'PHC636527', 'PHC315968', 
    //         'PHC985875', 'PHC746968', 'PHC666329', 'PHC415900', 
    //         'PHC173882', 'PHC571613', 'PHC663478', 'PHC875172'
    //     ];
        
    //     if (in_array($user_id, $restricted_user_ids)) {
    //         return null;
    //     }

    //     // Fetch the seven-level transaction for the given user
    //     $seven_level_transaction = SevenLevelTransaction::where('sender_id', $user_id)
    //         ->select('first_level', 'second_level', 'third_level', 'fourth_level', 'five_level', 'six_level', 'seven_level',  
    //             'first_level_status', 'second_level_status', 'third_level_status', 
    //             'fourth_level_status', 'five_level_status', 'six_level_status', 'seven_level_status')
    //         ->first();

    //     // Check if a transaction was found
    //     if (!$seven_level_transaction) {
    //         return null; // Handle the error as needed
    //     }

    //     // Initialize the result array
    //     $result = [];
        
    //     // Define levels and their corresponding status and amount
    //     $levels = [
    //         'first_level' => ['status' => 'first_level_status', 'amount' => 100],
    //         'second_level' => ['status' => 'second_level_status', 'amount' => 50],
    //         'third_level' => ['status' => 'third_level_status', 'amount' => 40],
    //         'fourth_level' => ['status' => 'fourth_level_status', 'amount' => 20],
    //         'five_level' => ['status' => 'five_level_status', 'amount' => 20],
    //         'six_level' => ['status' => 'six_level_status', 'amount' => 10],
    //         'seven_level' => ['status' => 'seven_level_status', 'amount' => 10],
    //     ];

    //     foreach ($levels as $level => $details) {
    //         $statusColumn = $details['status'];
            
    //         if ($seven_level_transaction->{$statusColumn} === "0") {
    //             if ($seven_level_transaction->$level) {
    //                 // Fetch the user details for each level if the level has a value
    //                 $user = User::where('user_id', $seven_level_transaction->$level)
    //                     ->select('name', 'phone', 'phone_pay_no', 'user_id')
    //                     ->first();

    //                 // Check if the user_id is in the restricted list
    //                 if ($user && in_array($user->user_id, $restricted_user_ids)) {
    //                     $user->phone_pay_no = null; // Hide phone_pay_no for restricted users
    //                 }

    //                 // Add the user object and amount to the result
    //                 $result[$level] = [
    //                     'name' => $user->name ?? null,
    //                     'phone' => $user->phone ?? null,
    //                     'phone_pay_no' => $user->phone_pay_no ?? null,
    //                     'user_id' => $user->user_id ?? null,
    //                     'amount' => $details['amount'],
    //                 ];
    //             }
    //         }
    //     }

    //     // Add the status columns to the result
    //     foreach ($levels as $level => $details) {
    //         $result[$details['status']] = $seven_level_transaction->{$details['status']};
    //     }

    //     return $result;
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
        'first_level' => ['status' => 'first_level_status', 'amount' => 100],
        'second_level' => ['status' => 'second_level_status', 'amount' => 50],
        'third_level' => ['status' => 'third_level_status', 'amount' => 40],
        'fourth_level' => ['status' => 'fourth_level_status', 'amount' => 20],
        'five_level' => ['status' => 'five_level_status', 'amount' => 20],
        'six_level' => ['status' => 'six_level_status', 'amount' => 10],
        'seven_level' => ['status' => 'seven_level_status', 'amount' => 10],
    ];

    // Initialize a collection to store the separate level transactions
    $separate_transactions = collect();

    // Iterate through each level and retrieve corresponding transactions
    foreach ($levels as $level => $details) {
        $statusColumn = $details['status']; // Get the status column name
        $amount = $details['amount']; // Get the corresponding amount
        $transactions = SevenLevelTransaction::where($level, $user_id) // Match user ID with level
            ->where($statusColumn, '0') // Filter transactions where status is 0
            ->select('id', 'sender_id', $level, $statusColumn) // Select necessary fields
            ->get()
            ->map(function ($transaction) use ($level,$amount) {
                $sender = User::where('user_id', $transaction->sender_id)->first(['name', 'phone']);
                $transaction->level = $level; // Add the level name to the transaction
                $transaction->name = $sender ? $sender->name : 'Unknown'; // Add sender name
                $transaction->phone = $sender ? $sender->phone : 'N/A'; // Add sender phone number
                $transaction->amount = $amount; // Add the corresponding amount for the level
                return $transaction;
            });

        // Merge the retrieved transactions into the collection
        $separate_transactions = $separate_transactions->merge($transactions);
    }
    if ($separate_transactions->isEmpty()) {
        return null; // Return null instead of an empty array
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
        $bank_details = Bank::where('user_id',$user_id)->select("id","user_id","district","state","address","pin_code","bank_name","account_number","ifsc_code","branch","account_holder_name","upi","paytm","phone_pe","google_pay","usdt_bep20")->first(); // Use with() to eager load the package relationship
        if(empty($bank_details)){
            $bank_details=[
                "id"=> null,
                "user_id"=> null,
                "district"=> null,
                "state"=> null,
                "address"=> null,
                "pin_code"=>null,
                "bank_name"=> null,
                "account_number"=> null,
                "ifsc_code"=> null,
                "branch"=> null,
                "account_holder_name"=> null,
                "upi"=> null,
                "paytm"=> null,
                "phone_pe"=> null,
                "google_pay"=> null,
                "usdt_bep20"=> null
            ];
          
        }
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
        // $view_direct->map(function ($user) {
        //     $user->sponsor_name = $this->get_name($user->sponsor_id);
        //     return $user;
        // });
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
        'page' => 'nullable|integer|min:1', // Optional page parameter
        'perPage' => 'nullable|integer|min:1', // Optional per page parameter
        'status' => 'nullable|in:Pending,Rejected,Active', // Optional per page parameter
    ]);

    if ($validator->fails()) {
        return $this->sendError($validator->errors()->first());
    }

    try {
        $user_id = $request->user_id;
        $status = $request->status;
        $page = $request->input('page', 1); // Default to the first page
        $perPage = $request->input('perPage', 10); // Default to 10 records per page

        // Create query for HelpStar data
       $query = HelpStar::with('receiverByData:user_id,id,name,phone')
            ->where('sender_id', $user_id)
            ->select('id', 'sender_id', 'receiver_id', 'amount', 'confirm_date', 'commitment_date', 'status');

        if ($status) {
            $query->where('status', $status);
        }
      
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
        $data = $query->paginate($perPage, ['*'], 'page', $page);
        // Execute query and retrieve results
      

        $result = $data->map(function ($item) {
            $receiver = $item->receiverByData; // Eager loaded data
            return [
                'amount' => $item->amount, // Assuming amount is the e_pin
                'receiver_id' => $item->receiver_id, // The sender_id as user_id
                'name' => isset($receiver->name) ? $receiver->name :'Anonymus', // Fetch sender name
                'status' => $item->status, // Dynamic status
                'confirm_date' => $item->confirm_date, // Creation date
                'commitment_date' => $item->commitment_date, // Creation date
                'receiver_phone' => $receiver ? $receiver->phone : 'N/A',
            ];
        });

        $pagination = [
            'current_page' => $data->currentPage(),
            'last_page' => $data->lastPage(),
            'per_page' => $data->perPage(),
            'total' => $data->total(),
        ];
        return $this->sendResponse($result, 'Data retrieved successfully.', $pagination);

    } catch (\Exception $e) {
        return $this->sendError('Oops! Something went wrong. Please try again.');
    }
}

// S.No.	Sender	Sponsor	Amount	Date	Status	Payment Slip	Transaction No	Narration
    public function taking_help(Request $request) 
    {
      
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,user_id',
            'perPage' => 'nullable|integer|min:1', // Optional items per page
            'page' => 'nullable|integer|min:1',
            'status' => 'nullable|in:Pending,Rejected,Active',
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first());
        }
        try{
            $status = $request->status; // Default to null
            $user_id = $request->user_id;
            $perPage = $request->get('perPage', 10); // Default to 10 items per page
            $page = $request->get('page', 1); // Default to page 1
    
            $data = HelpStar::where('receiver_id', $user_id)
                ->select('id', 'sender_id', 'receiver_id', 'amount', 'commitment_date', 'confirm_date', 'status')
                ->with('senderData', 'receiverByData');

            if ($status) {
                $data->where('status', $status);
            }
            $total = $data->count();

            $data = $data->paginate($perPage, ['*'], 'page', $page);
            
            $pagination = [
                'current_page' => $page,
                'last_page' => ceil($total / $perPage),
                'per_page' => $perPage,
                'total' => $total,
            ];
            // Initialize an empty array for the response
            $response = [];
            
            foreach ($data as $item) {
                $response[] = [
                    'id' => $item->id,
                    'sender_name' => isset($item->senderData->name) ? $item->senderData->name : 'Anonymus',
                    'sender_phone' => isset($item->senderData->phone) ? $item->senderData->phone : null,
                    'sender_user_id' => isset($item->senderData->user_id) ? $item->senderData->user_id : null,
                    'amount' => $item->amount,
                    'commitment_date' => $item->commitment_date,
                    'confirm_date' => $item->confirm_date,
                    'status' => $item->status,

                     
                ];
            }
            return $this->sendResponse($response,'Data Retrieved successfully.',$pagination);
            
             
        }catch (\Exception $e) {
            return $this->sendError($e->getMessage(),'Oops! Something went wrong. Please try again.');
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
            'perPage' => 'nullable|integer|min:1', // Optional items per page
            'page' => 'nullable|integer|min:1', // Optional page number
        ]);
        
        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first());
        }

        try {
            $user_id = $request->user_id;
            $user = User::where('user_id', $user_id)->select('user_id','name')->first();

            $perPage = $request->get('perPage', 10); // Default to 10 items per page
            $page = $request->get('page', 1); // Default to page 1

            // Retrieve e-pin transfer data
            $epinTransfers = EPinTransfer::where('member_id', $user_id)
            ->orderBy('id', 'desc')
            ->paginate($perPage);

            // Collect the e_pins from the EPinTransfer records
            $ePins = $epinTransfers->pluck('e_pin')->toArray();

            // Retrieve users who have used these e_pins
            $usersUsingEPin = User::whereIn('registration_code', $ePins)->select('user_id', 'name', 'email', 'registration_code')->get()
            ->keyBy('registration_code'); // Key by registration_code for quicker access

        // Build response data
        $response = $epinTransfers->map(function($epinTransfer) use ($usersUsingEPin,$user) {
            // Find the user who used the e-pin
            // $usedBy = $usersUsingEPin->firstWhere('registration_code', $epinTransfer->e_pin);
            $usedBy = $usersUsingEPin->get($epinTransfer->e_pin);
            $status = $usedBy ? 'used' : 'available'; // Example: change logic based on your needs
           
            return [
                'e_pin' => $epinTransfer->e_pin,
                'user_id' => $user->user_id,
                'user_name' => $user->name,
                'status' => $status, // Dynamic status
                'e_pin_created_date' =>(($epinTransfer->created_at)), 
                'used_by_user_id' => $usedBy ? $usedBy->user_id : null,
                'used_by_user_name' => $usedBy ? $usedBy->name : null,
            ];
            
        });
        $pagination = [
            'current_page' => $epinTransfers->currentPage(),
            'last_page' => $epinTransfers->lastPage(),
            'per_page' => $epinTransfers->perPage(),
            'total' => $epinTransfers->total(),
        ];
        return $this->sendResponse($response, 'Data retrieved successfully.', $pagination);
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

                    // Mail::to($obj->email)->send(new AppPasswordResendMail($mailBody));
                    // return $this->sendResponse($success, 'Otp resent successfully.');
                    $success['mobile'] = $mobileNo;
                    $success['otp'] = $otpNo;
                    $success['expert_id'] = $expert_id;
                    return $this->sendResponse($success, 'Otp resent successfully.');
                } else {
                    return $this->sendError('Oops! Unable to resend. Please try again.');
                }
            }catch (\Exception $e) {
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
            $obj->otp = rand(100000, 999999);

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

            //need to update status for sender id
            return $this->sendResponse($help_stars, 'User Data Retrieve Successfully.');

    }

    return null; // Return null if no data is found
} 

/**
 * Iteratively add downline members for a given user
 */ 

//  public function view_downline(Request $request)
// {
//     try {
//         // Validate the request input
//         $validator = Validator::make($request->all(), [
//             'user_id' => 'required|exists:users,user_id',
//             'fromDate' => 'nullable|date',
//             'toDate' => 'nullable|date',
//             'status' => 'nullable|string',
//             'perPage' => 'nullable|integer|min:1', // Optional items per page
//             'page' => 'nullable|integer|min:1',
//         ]);

//         // If validation fails, return the first error
//         if ($validator->fails()) {
//             return $this->sendError($validator->errors()->first());
//         }

//         $user_id = $request->user_id;

//         // Initialize an array to hold all team members
//         $uniqueMembers = collect();
    

//         // Fetch direct referrals of the user
//         $directMembers = User::with('directReferrals')->where('sponsor_id', $user_id)->get();

//         // Use a unique set to avoid duplicates

//         // Gather all members
//         $this->gatherMembers($directMembers, $uniqueMembers);

//         // Apply filters using collection methods
//         if ($request->fromDate) {
//             $fromDate = \Carbon\Carbon::createFromFormat('Y-m-d', $request->fromDate)->startOfDay();
//             $uniqueMembers = $uniqueMembers->filter(function ($member) use ($fromDate) {
//                 return $member->created_at >= $fromDate;
//             });
//         }

//         if ($request->toDate) {
//             $toDate = \Carbon\Carbon::createFromFormat('Y-m-d', $request->toDate)->endOfDay();
//             $uniqueMembers = $uniqueMembers->filter(function ($member) use ($toDate) {
//                 return $member->created_at <= $toDate;
//             });
//         }

//         if ($request->status) {
//             $uniqueMembers = $uniqueMembers->filter(function ($member) use ($request) {
//                 return $member->status === $request->status;
//             });
//         }
//         $perPage = $request->get('perPage', 10); // Default to 10 items per page
//         $page = $request->get('page', 1); // Default to page 1
//         $total = $uniqueMembers->count();
//         $paginatedMembers = $uniqueMembers->slice(($page - 1) * $perPage, $perPage)->values();

//         $pagination = [
//             'current_page' => isset($page) ?$page : 5,
//             'last_page' => ceil($total / $perPage),
//             'per_page' => $perPage,
//             'total' => $total,
//         ];
//         // Transform the members into a flat structure
//         $flatMembers = $uniqueMembers->map(function ($user) {
//             return [
//                 'user_id' => $user->user_id,
//                 'name' => $user->name,
//                 'phone' => $user->phone,
//                 'created_at' => date('d M,Y',strtotime($user->created_at)),
//                 'activated_date' => date('d M,Y',strtotime($user->activated_date)),
//                 'sponsor_id' => $user->sponsor_id,
//                 'status' => $user->status,
//                 'sponsor_name' => $this->get_name($user->sponsor_id),
//             ];
//         });
      
//             return $this->sendResponse(
//                $flatMembers->values()->all(),
//                $pagination,
//             );
//         // Return the flat list of all team members
//         return $this->sendResponse($flatMembers->values()->all(), 'Retrieved successfully.');

//     } catch (\Exception $e) {
//         // Log the error for debugging
//        
//         return $this->sendError($e->getMessage(),'An error occurred while retrieving team members. Please try again later.');
//     }
// }

/**
 * Gather all members recursively while avoiding duplicates
 */
// private function gatherMembers($members, &$uniqueMembers)
// {
//     foreach ($members as $member) {
//         // Check if the member is already in the unique list
//         if (!$uniqueMembers->contains('user_id', $member->user_id)) {
//             $uniqueMembers->push($member); // Add to unique members
//             // Recursively gather downlines
//             $this->gatherMembers($member->directReferrals, $uniqueMembers);
//         }
//     }
// }


    public function view_downline(Request $request)
    {
        try {
            // Validate the request input
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,user_id',
                'fromDate' => 'nullable|date',
                'toDate' => 'nullable|date',
                'status' => 'nullable|string',
                'perPage' => 'nullable|integer|min:1', // Optional items per page
                'page' => 'nullable|integer|min:1',    // Optional page number
            ]);

            // If validation fails, return the first error
            if ($validator->fails()) {
                return $this->sendError($validator->errors()->first());
            }

            $user_id = $request->user_id;
            $perPage = $request->get('perPage', 10); // Default to 10 items per page
            $page = $request->get('page', 1); // Default to page 1

            // Initialize a collection to hold all team members
            $uniqueMembers = collect();

            // Fetch direct referrals of the user
            $directMembers = User::with('directReferrals')->where('sponsor_id', $user_id)->get();

            // Gather all members
            $this->gatherMembers($directMembers, $uniqueMembers);
    
            if ($request->fromDate) {
                $fromDate = \Carbon\Carbon::createFromFormat('Y-m-d', $request->fromDate)->startOfDay();
                $uniqueMembers = $uniqueMembers->filter(function ($member) use ($fromDate) {
                    return $member->created_at >= $fromDate;
                });
            }

            if ($request->toDate) {
                $toDate = \Carbon\Carbon::createFromFormat('Y-m-d', $request->toDate)->endOfDay();
                $uniqueMembers = $uniqueMembers->filter(function ($member) use ($toDate) {
                    return $member->created_at <= $toDate;
                });
            }

            if ($request->status) {
                $uniqueMembers = $uniqueMembers->filter(function ($member) use ($request) {
                    return $member->status === $request->status;
                });
            }
            // Paginate the results
            $total = $uniqueMembers->count();
            $paginatedMembers = $uniqueMembers->slice(($page - 1) * $perPage, $perPage)->values();

            // Prepare pagination data
            $pagination = [
                'current_page' =>  $page,
                'last_page' => ceil($total / $perPage),
                'per_page' => $perPage,
                'total' => $total,
            ];

            // Transform to flat structure
            $flatMembers = $paginatedMembers->map(function ($user) {
                return [
                    'user_id' => $user->user_id,
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'created_at' =>$user->created_at,
                    'activated_date' => $user->activated_date,
                    'sponsor_id' => $user->sponsor_id,
                    'status' => $user->status,
                    'sponsor_name' => $this->get_name($user->sponsor_id),
                ];
            });

            // Return the paginated list of all team members
            return $this->sendResponse(
                $flatMembers->values()->all(),'Retrieved successfully.',$pagination);

        } catch (\Exception $e) {
            // Log the error for debugging
           
            return $this->sendError('An error occurred while retrieving team members. Please try again later.');
        }
    }

    /**
     * Gather all members recursively while avoiding duplicates
     */
    private function gatherMembers($members, &$uniqueMembers)
    {
        foreach ($members as $member) {
            // Check if the member is already in the unique list
            if (!$uniqueMembers->contains('user_id', $member->user_id)) {
                $uniqueMembers->push($member); // Add to unique members
                // Recursively gather downlines
                $this->gatherMembers($member->directReferrals, $uniqueMembers);
            }
        }
    }


    /**
     * Iteratively add downline members for a given user
     */
    private function addDownlines($user, &$allMembers)
    {
        // Use a queue to process users
        $queue = [$user];

        while (!empty($queue)) {
            $currentUser = array_shift($queue); // Get the next user to process
            $allMembers[] = $currentUser; // Add to all members

            // Fetch all direct referrals for the current user
            foreach ($currentUser->directReferrals as $referral) {
                $queue[] = $referral; // Add referrals to the queue for processing
            }
        }
    }
 

    public function view_downline_n(Request $request)
    {
        try {
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
            $this->getDownlinesRecursive_nimrah($user, $allDownlineMembers);

            // Apply filters (if any)
            // if ($request->filled('fromDate')) {
            //     $fromDate = \Carbon\Carbon::createFromFormat('Y-m-d', $request->fromDate)->startOfDay();
            //     $allDownlineMembers = $allDownlineMembers->filter(function ($member) use ($fromDate) {
            //         return $member->created_at >= $fromDate;
            //     });
            // }

            // if ($request->filled('toDate')) {
            //     $toDate = \Carbon\Carbon::createFromFormat('Y-m-d', $request->toDate)->endOfDay();
            //     $allDownlineMembers = $allDownlineMembers->filter(function ($member) use ($toDate) {
            //         return $member->created_at <= $toDate;
            //     });
            // }

            // if ($request->filled('status')) {
            //     $allDownlineMembers = $allDownlineMembers->filter(function ($member) use ($request) {
            //         return $member->status === $request->status;
            //     });
            // }
            if ($request->fromDate) {
                $fromDate = \Carbon\Carbon::createFromFormat('Y-m-d', $request->fromDate)->startOfDay();
                $allDownlineMembers = $allDownlineMembers->filter(function ($member) use ($fromDate) {
                    return $member->created_at >= $fromDate;
                });
            }

            if ($request->toDate) {
                $toDate = \Carbon\Carbon::createFromFormat('Y-m-d', $request->toDate)->endOfDay();
                $allDownlineMembers = $allDownlineMembers->filter(function ($member) use ($toDate) {
                    return $member->created_at <= $toDate;
                });
            }

            if ($request->status) {
                $allDownlineMembers = $allDownlineMembers->filter(function ($member) use ($request) {
                    return $member->status === $request->status;
                });
            }

            // Return the filtered list of all downline members
            return $this->sendResponse($allDownlineMembers->values(), 'Retrieved successfully.');

        } catch (\Exception $e) {
            // Log the error message for debugging (optional)
            \Log::error('Error retrieving downline members: ' . $e->getMessage());

            // Return a generic error message to the user
            return $this->sendError('An error occurred while retrieving downline members. Please try again later.');
        }
    }

/**
 * Recursively fetch all downlines (direct and indirect)
 */
    private function getDownlinesRecursive_nimrah($user, &$allDownlineMembers)
    {
        // Get direct referrals of the user
        $directReferrals = $user->directReferrals()->get();

        foreach ($directReferrals as $referral) {
            // Add this referral to the collection
            $allDownlineMembers->push($referral);

            // Recursively get downlines of this referral
            $this->getDownlinesRecursive_nimrah($referral, $allDownlineMembers);
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
    public function sponser_help(Request $request){
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,user_id',
                'doner_id' => 'nullable|exists:users,user_id',
                'level_name' => 'nullable|exists:packages,id',
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->errors()->first());
            }
            try {
            $user_id = $request->user_id;
            $status = $request->status;
            $page = $request->input('page', 1); // Default to the first page
            $perPage = $request->input('perPage', 10); // Default to 10 records per page
            $sponsor = User::where('user_id', $user_id)->select('sponsor_id')->first();
            $sponsor_id = $sponsor->sponsor_id;
            // Create query for HelpStar data
        $query = HelpStar::with('receiverByData:user_id,id,name,phone')
                ->where('sender_id', $user_id)->where('receiver_id', $sponsor_id)
                ->select('id', 'sender_id', 'receiver_id', 'amount', 'confirm_date', 'commitment_date', 'status');
            if ($status) {
                $query->where('status', $status);
            }
        
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
            $data = $query->paginate($perPage, ['*'], 'page', $page);
            // Execute query and retrieve results
        

            $result = $data->map(function ($item) {
                $receiver = $item->receiverByData; // Eager loaded data
                return [
                    'name' => isset($receiver->name) ? $receiver->name :'Anonymus', // Fetch sender name
                    'doner_id' => $item->receiver_id, // The sender_id as user_id
                    'amount' => $item->amount, // Assuming amount is the e_pin
                    'package_name' => 'Silver', // Dynamic status
                    'status' => $item->status, // Dynamic status
                    'confirm_date' => $item->confirm_date, // Creation date
                    'commitment_date' => $item->commitment_date, // Creation date
                    'doner_phone' => $receiver ? $receiver->phone : 'N/A',
                ];
            });

            $pagination = [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
            ];
            return $this->sendResponse($result, 'Data retrieved successfully.', $pagination);

        } catch (\Exception $e) {
            return $this->sendError('Oops! Something went wrong. Please try again.');
        } 
    } 

    // public function giving_help_level_n(Request $request){
    //     $validator = Validator::make($request->all(), [
    //         'user_id' => 'required|exists:users,user_id',
    //         'page' => 'nullable|integer|min:1', // Optional page parameter
    //         'perPage' => 'nullable|integer|min:1', // Optional per page parameter
    //     ]);
    //     if ($validator->fails()) {
    //         return $this->sendError($validator->errors()->first());
    //     }
    //      $user_id = $request->user_id;
    //       // Pagination settings
    //     $page = $request->input('page', 1); // Default to the first page
    //     $perPage = $request->input('perPage', 10); // Default to 10 records per page
    //         // Fetch the seven-level transaction for the given user
    //         $seven_level_transaction = SevenLevelTransaction::where('sender_id', $user_id)
    //             ->select('sender_id', 'receiver_id', 'first_level', 'second_level', 'third_level', 'fourth_level', 'five_level', 'six_level', 'seven_level', 'first_level_status', 'second_level_status', 'third_level_status', 'fourth_level_status', 'five_level_status', 'six_level_status', 'seven_level_status', 'first_level_confirm_date', 'second_level_confirm_date', 'third_level_confirm_date', 'fourth_level_confirm_date', 'five_level_confirm_date', 'six_level_confirm_date', 'seven_level_confirm_date', 'extra_details', 'status')->first();
 
    //                 // Check if transactions were found
    //     if (empty($seven_level_transaction)) {
    //         return $this->sendError('No transactions found.');
    //     }


    //     $results = [];
        
    //         // Define the levels to iterate through
    //         $levels = ['first_level', 'second_level', 'third_level', 'fourth_level', 'five_level', 'six_level', 'seven_level'];
    
    //         $status_levels = [
    //             'first_level_status', 'second_level_status', 'third_level_status', 
    //             'fourth_level_status', 'five_level_status', 'six_level_status', 'seven_level_status'
    //         ];
    //          $amounts = [100, 50, 40, 20, 20, 10, 10]; // Assuming this is a static amount array
    //         $results = [];
            
    //         foreach ($levels as $index => $level) {
    //             if ($seven_level_transaction->{$status_levels[$index]} === 0) {
    //                 if ($seven_level_transaction->$level) {
    //                     // Fetch the user details for each level if the level has a value
    //                     $user = User::where('user_id', $seven_level_transaction->$level)
    //                         ->select('name', 'phone', 'phone_pay_no', 'user_id')
    //                         ->first();
             
            
    //                     // Assign the fetched user object back to the level in seven_level_transaction
    //                     $seven_level_transaction->$level = $user;
    //                 }else {
    //                     // If there is no user for this level, you can set it to null or handle it as needed
    //                     $seven_level_transaction->$level = null;
    //                 }
    //             } else {
    //                 $seven_level_transaction->$level = null;
    //             }
    //         }
        
    //         // Prepare pagination data
            
    //     return $this->sendResponse($seven_level_transaction, 'Data retrieved successfully.');
    // }

    public function receiving_help_level(Request $request) {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,user_id',
            'page' => 'nullable|integer|min:1', // Optional page parameter
            'perPage' => 'nullable|integer|min:1', // Optional per page parameter
        ]);
    
        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first());
        }
    
        $levels = [
            'first_level' => ['status' => 'first_level_status', 'amount' => 100],
            'second_level' => ['status' => 'second_level_status', 'amount' => 50],
            'third_level' => ['status' => 'third_level_status', 'amount' => 40],
            'fourth_level' => ['status' => 'fourth_level_status', 'amount' => 20],
            'five_level' => ['status' => 'five_level_status', 'amount' => 20],
            'six_level' => ['status' => 'six_level_status', 'amount' => 10],
            'seven_level' => ['status' => 'seven_level_status', 'amount' => 10],
        ];
    
        // Initialize a collection to store the results
        $results = collect();
        $user_id = $request->user_id;
    
        // Iterate through each level and retrieve corresponding transactions
        foreach ($levels as $level => $details) {
            $statusColumn = $details['status']; // Get the status column name
            $amount = $details['amount']; // Get the corresponding amount
    
            $transactions = SevenLevelTransaction::where($level, $user_id) // Match user ID with level
                ->select('id', 'sender_id', $level, $statusColumn, 'created_at') // Select necessary fields
                ->get()
                ->map(function ($transaction) use ($level, $amount) {
                    $sender = User::where('user_id', $transaction->sender_id)->first(['name', 'phone']);
                    
                    $status_value = $transaction->{$level . '_status'};
                    $status_description = $status_value === 1 ? 'Active' : 'Pending'; // Set status description
                    $level_cleaned = str_replace('_', ' ', $level);
    
                    return [
                        'level' => ucfirst($level_cleaned), // Capitalize level name
                        'name' => $sender ? $sender->name : 'Unknown', // Add sender name
                        'phone' => $sender ? $sender->phone : 'N/A', // Add sender phone number
                        'user_id' => $transaction->sender_id, // Use sender ID for user_id
                        'status' => $status_description, // Use the status description
                        'commitment_date' => $transaction->created_at, // Assuming this is the commitment date
                        'confirm_date' => $transaction->{$level . '_confirm_date'}, // Confirm date
                        'amount' => $amount, // Corresponding amount
                    ];
                });
    
            // Merge the retrieved transactions into the results collection
            $results = $results->merge($transactions);
        }
    
        // Handle pagination if required
        $perPage = $request->input('perPage', 10);
        $page = $request->input('page', 1);
        $paginatedResults = $results->forPage($page, $perPage);
    
        $pagination = [
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $results->count(),
        ];
    
        // Return the paginated collection of separate level transactions
        return $this->sendResponse($paginatedResults, 'Data retrieved successfully.', $pagination);
    }
    
    public function giving_help_level(Request $request) {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,user_id',
            'page' => 'nullable|integer|min:1', // Optional page parameter
            'perPage' => 'nullable|integer|min:1', // Optional per page parameter
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first());
        }

        $user_id = $request->user_id;

        $page = $request->input('page', 1); // Default to the first page
        $perPage = $request->input('perPage', 1); // Default to 7 records per page
        $seven_level_transactions = SevenLevelTransaction::where('sender_id', $user_id)
            ->paginate(1);
        // Check if transactions were found
        if ($seven_level_transactions->isEmpty()) {
            return $this->sendError('No transactions found.');
        }
        $results = [];
        $levels = ['first_level', 'second_level', 'third_level', 'fourth_level', 'five_level', 'six_level', 'seven_level'];
        $amounts = [100, 50, 40, 20, 20, 10, 10]; // Amounts for each level
        foreach ($seven_level_transactions as $transaction) {
            foreach ($levels as $index =>$level) {
                $user_data = null;
                if ($transaction->$level) {
                    // Fetch the user details for each level if the level has a value
                    $user = User::where('user_id', $transaction->$level)
                        ->select('name', 'phone', 'phone_pay_no', 'user_id')
                        ->first();

                    // If user exists, assign the user data
                    if ($user) {
                        $user_data = $user->toArray(); // Convert user object to array
                    }
                }
                $level_cleaned = str_replace('_', ' ', $level);
                $status_value = $transaction->{$level . '_status'};
                $status_description = $status_value == '1' ? 'Active' : 'Pending';

                $results[] = [
                    'level' => ucfirst($level_cleaned),
                    'name' => $user_data['name'] ?? null,
                    'phone' => $user_data['phone'] ?? null,
                    'phone_pay_no' => $user_data['phone_pay_no'] ?? null,
                    'user_id' => $user_data['user_id'] ?? null,
                    'status' => $status_description,
                    'commitment_date' => $transaction->created_at,
                    'confirm_date' => $transaction->{$level . '_confirm_date'},
                    'amount' => $amounts[$index],
                   ];
            }
        }
         // Implement pagination on the results
   
        $pagination = [
            'current_page' => $seven_level_transactions->currentPage(),
            'last_page' => $seven_level_transactions->lastPage(),
            'per_page' => $seven_level_transactions->perPage(),
            'total' => $seven_level_transactions->total(),
        ];
      
        return $this->sendResponse($results, 'Data retrieved successfully.', $pagination);
    }
       
       
    
        
}
