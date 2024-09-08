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
class RegistrationController extends BaseController
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
        try{

            $sponsor = User::where('user_id',$request->sponsor_id)
            ->select('id','user_id','sponsor_id','is_active','activated_date','status','is_green','package_id','updated_at')->first();
           if ($sponsor && $sponsor->is_active == 0) {
                if ($sponsor->package_id == 1) {
                    $sponsor->package_id = 2;
                }
                $sponsor->update([
                    'is_active' => 1,
                    'package_id' => $sponsor->package_id, // update package_id
                    // 'package_id' => '1',
                    'activated_date' => now()
                ]);
                if($sponsor->is_green == 1 && 'is_active' == 1){
                    $sponsor->update([
                        'status' => 'Active',
                        // 'package_id' => '2',
                    ]);
                }
            }
     
            if ($request->has('registration_code')) {
                $epin = EPinTransfer::select('id','e_pin','is_used','updated_at')->where('e_pin', $request->registration_code)
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
                       
                            /*
                            * --------- mlm code start from here-------------
                             */
                        $data = $this->active_users();

                        if (!is_array($data) || empty($data)) {
                            $data = ['PHC123456'];
                        }
                        // Retrieve last processed user ID from Redis
                        $lastUserId = Redis::get('last_user_id');
                        $receiverUserId = null;
                        $helpReceived_count = 0;

                        if ($lastUserId) {
                            $lastUserIndex = array_search($lastUserId, $data);

                            // Determine the next user in line
                            if ($lastUserIndex !== false && isset($data[$lastUserIndex + 1])) {
                                $receiverUserId = $data[$lastUserIndex + 1];
                            } else {
                                // If we're at the end of the list, start from the beginning
                                $receiverUserId = $data[0];
                            }
                        } else {
                            // First time, start with the first user in the list
                            $receiverUserId = $data[0];
                        }
                        // Retrieve the receiver user and their package details
                        $receiver = User::select('package_id','user_id','received_payments_count')->where('user_id', $receiverUserId)->first(); // payment receive
                        $receiverPackage = Package::select('help','id','help_count')->where('id', $receiver->package_id)->first();
             
                        // Check how many times this user has received help
                        $helpReceived_count = HelpStar::where('receiver_id', $receiverUserId)->where('receiver_position',2)->count();
                        // echo '$receiverPackage->help_count'.$receiverPackage->help_count.'<br>'; 
                        // echo '$helpReceived_count'.$helpReceived_count;
                           // If the user is not eligible for help, redirect the payment to the admin
                        if($helpReceived_count == 1){
                            $this->level_upgrade_to_silver_users($userId);
                        }
                    
                        if ($helpReceived_count < 3) {  //0 <= 3
                            // Create a new HelpStar entry
                            $HelpStar = new HelpStar();
                            $HelpStar->sender_id = $userId;
                            $HelpStar->receiver_id = $receiverUserId;
                            $HelpStar->amount = $receiverPackage->help; // Use the help amount from the package
                            $HelpStar->sender_position = '1'; 
                            $HelpStar->receiver_position = $receiverPackage->id;
                            $HelpStar->received_payments_count = 1;
                            $HelpStar->commitment_date = now();
                            $HelpStar->confirm_date = null;
                            $HelpStar->status = 'Pending';
                            $HelpStar->save();
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
                        Redis::set('last_user_id', $receiverUserId);
                        $success['user'] =$user;
                        $success['current_transaction'] =$receiverUserId;
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
        $active_users = User::where('is_active', 1)
        ->where('is_green', 1)
        ->where('status', 'Active')
        ->whereNull('deleted_at')
        ->where('package_id', 2)
        ->orderBy('activated_date')
        ->limit(10) // Apply limit early
        ->pluck('user_id') // Fetch only user_id
        ->toArray();
        return $active_users;
    }

    public function level_upgrade_to_silver_users($sender_id) {
        $userId = $sender_id;
        $silver_level_users = $this->silver_active_users();
         
            if (!is_array($silver_level_users) || empty($silver_level_users)) {
                $silver_level_users = ['PHC123456'];
            }
            // Retrieve last processed user ID from Redis
            $lastUserId = Redis::get('silver_level_user_id');
           
            $helpReceived_count = 0;

            if ($lastUserId) {
                $lastUserIndex = array_search($lastUserId, $silver_level_users);

                if ($lastUserIndex !== false && isset($silver_level_users[$lastUserIndex + 1])) {
                    $receiverUserId = $silver_level_users[$lastUserIndex + 1];
                } else {
                    $receiverUserId = $silver_level_users[0];
                }
            } else {
                $receiverUserId = $silver_level_users[0];
            }
        // Retrieve the receiver user and their package details
        $receiver = User::select('package_id','received_payments_count')->where('user_id', $receiverUserId)->first(); // payment receive
        $receiverPackage = Package::select('id','help')->where('id', $receiver->package_id)->first();
        $helpReceived_count = HelpStar::where('receiver_id', $receiverUserId)->where('receiver_position',3)->count();

        if($helpReceived_count == 4){
            $this->level_upgrade_to_gold_users($userId);
            $this->sponser_help_gold_users($userId,'600');
        } 
        if($helpReceived_count  == [6,7]){
            $this->re_entry_to_star($userId);
        }
        if($helpReceived_count  == 8){
            $this->re_entry_payment_to_admin($userId);
        }
        if ($helpReceived_count < 9) {  //0 <= 3
            // Create a new HelpStar entry
            $data = new HelpStar();
            $data->sender_id = $userId;
            $data->receiver_id = $receiverUserId;
            $data->amount = $receiverPackage->help; // Use the help amount from the package ----600
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
        Redis::set('silver_level_user_id', $receiverUserId);
        $success['current_transaction'] =$receiverUserId;
        $success['new_transaction'] =$lastUserId;
    }

    public function sponser_help_gold_users($user_id,$amount){
        $sponsor = User::where('user_id',$user_id)->where('is_active','Active')->select('sponsor_id','package_id')->first();
        $sponsor_package_id = User::where('user_id',$sponsor->sponsor_id)->where('is_active','Active')->select('sponsor_id','package_id')->first();
        $sponser_help = new HelpStar();
        $sponser_help->sender_id = $user_id;
        $sponser_help->receiver_id =  $sponsor->sponsor_id; // Payment goes to admin
        $sponser_help->amount = $amount; // Use the help amount from the package
        $sponser_help->sender_position =$sponsor->package_id;
        $sponser_help->receiver_position =$sponsor_package_id->package_id;
        $sponser_help->received_payments_count = 1;
        $sponser_help->commitment_date = now();
        $sponser_help->confirm_date = null;
        $sponser_help->status = 'Pending';
        $sponser_help->save(); 
        return true;  

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
 

    public function silver_active_users() {
        // Retrieve active third-level users in a single query, select only 'user_id'
        $users_with_exactly_three_helps = HelpStar::select('receiver_id')
            ->whereIn('receiver_id', function ($query) {
                $query->select('user_id')
                    ->from('users')
                    ->where('is_active', 1)
                    ->where('is_green', 1)
                    ->where('status', 'Active')
                    ->whereNull('deleted_at')
                    ->where('package_id', 3)
                    ->orderBy('activated_date');
            })
            ->groupBy('receiver_id')
            ->havingRaw('COUNT(*) >= 3 AND COUNT(*) <= 12')
            ->limit(10)
            ->pluck('receiver_id')
            ->toArray();
        
        return $users_with_exactly_three_helps;
    }
    

    public function gold_active_users() {
        $gold_active_users = HelpStar::select('receiver_id')
            ->whereIn('receiver_id', function ($query) {
                $query->select('user_id')
                    ->from('users')
                    ->where('is_active', 1)
                    ->where('is_green', 1)
                    ->where('status', 'Active')
                    ->whereNull('deleted_at')
                    ->where('package_id', 4)
                    ->orderBy('activated_date');
            })
            ->groupBy('receiver_id')
            ->havingRaw('COUNT(*) >= 12 AND COUNT(*) <= 22')
            ->limit(10)
            ->pluck('receiver_id')
            ->toArray();
        
        return $gold_active_users;
    }


    public function level_upgrade_to_gold_users($sender_id) {
            $userId = $sender_id;
            $gold_active_users =   $this->gold_active_users();
   
                if (!is_array($gold_active_users) || empty($gold_active_users)) {
                    $gold_active_users = ['PHC123456'];
                }
                // Retrieve last processed user ID from Redis
                $lastUserId = Redis::get('gold_level_last_user_id');
               
                $helpReceived_count = 0;
    
                if ($lastUserId) {
                    $lastUserIndex = array_search($lastUserId, $gold_active_users);
    
                    if ($lastUserIndex !== false && isset($gold_active_users[$lastUserIndex + 1])) {
                        $receiverUserId = $gold_active_users[$lastUserIndex + 1];
                    } else {
                        $receiverUserId = $gold_active_users[0];
                    }
                } else {
                    $receiverUserId = $gold_active_users[0];
                }
         
               // Retrieve the receiver user and their package details
                $receiver = User::select('package_id','received_payments_count')->where('user_id', $receiverUserId)->first(); // payment receive
                $receiverPackage = Package::select('id','help')->where('id', $receiver->package_id)->first();
                $helpReceived_count = HelpStar::where('receiver_id', $receiverUserId)->where('receiver_position',4)->count();

            if($helpReceived_count == 4){
                $this->level_upgrade_to_platinum_users($userId);
                $this->sponser_help_gold_users($userId,'2000');
            }
            if ($helpReceived_count < 9) {  //0 <= 3
                // Create a new HelpStar entry
                $data = new HelpStar();
                $data->sender_id = $userId;
                $data->receiver_id = $receiverUserId;
                $data->amount = $receiverPackage->help; // Use the help amount from the package -- 2000
                $data->sender_position = '3'; 
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
                $data->sender_position ='3';
                $data->receiver_position = '4';
                $data->received_payments_count = 1;
                $data->commitment_date = now();
                $data->confirm_date = null;
                $data->status = 'Pending';
                $data->save();   
            }
            Redis::set('gold_level_last_user_id', $receiverUserId);
            $success['current_transaction'] =$receiverUserId;
            $success['new_transaction'] =$lastUserId;
    }
    public function platinum_active_users() {
        // Retrieve active third-level users in a single query, select only 'user_id'
        $platinum_active_users = HelpStar::select('receiver_id')
            ->whereIn('receiver_id', function ($query) {
                $query->select('user_id')
                    ->from('users')
                    ->where('is_active', 1)
                    ->where('is_green', 1)
                    ->where('status', 'Active')
                    ->whereNull('deleted_at')
                    ->where('package_id', 5)
                    ->orderBy('activated_date');
            })
            ->groupBy('receiver_id')
            ->havingRaw('COUNT(*) >= 22 AND COUNT(*) <= 42')
            ->limit(10)
            ->pluck('receiver_id')
            ->toArray();
        
        return $platinum_active_users;
    }

    public function level_upgrade_to_platinum_users($sender_id) {
        $userId = $sender_id;
        $platinum_active_users =   $this->platinum_active_users();

            if (!is_array($platinum_active_users) || empty($platinum_active_users)) {
                $platinum_active_users = ['PHC123456'];
            }
            // Retrieve last processed user ID from Redis
            $lastUserId = Redis::get('platinum_level_last_user_id');
           
            $helpReceived_count = 0;

            if ($lastUserId) {
                $lastUserIndex = array_search($lastUserId, $platinum_active_users);

                if ($lastUserIndex !== false && isset($platinum_active_users[$lastUserIndex + 1])) {
                    $receiverUserId = $platinum_active_users[$lastUserIndex + 1];
                } else {
                    $receiverUserId = $platinum_active_users[0];
                }
            } else {
                $receiverUserId = $platinum_active_users[0];
            }
     
           // Retrieve the receiver user and their package details
            $receiver = User::select('package_id','received_payments_count')->where('user_id', $receiverUserId)->first(); // payment receive
            $receiverPackage = Package::select('id','help')->where('id', $receiver->package_id)->first();
            $helpReceived_count = HelpStar::where('receiver_id', $receiverUserId)->where('receiver_position',5)->count();

        if($helpReceived_count == 4){
            $this->level_upgrade_to_ruby_users($userId);
            $this->sponser_help_gold_users($userId,'6000');
        }
        if ($helpReceived_count < 9) {  //0 <= 3
            // Create a new HelpStar entry
            $data = new HelpStar();
            $data->sender_id = $userId;
            $data->receiver_id = $receiverUserId;
            $data->amount = $receiverPackage->help; // Use the help amount from the package --6000
            $data->sender_position = '4'; 
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
            $data->sender_position ='4';
            $data->receiver_position = '5';
            $data->received_payments_count = 1;
            $data->commitment_date = now();
            $data->confirm_date = null;
            $data->status = 'Pending';
            $data->save();   
        }
        Redis::set('platinum_level_last_user_id', $receiverUserId);
        $success['current_transaction'] =$receiverUserId;
        $success['new_transaction'] =$lastUserId;
}

public function ruby_active_users() {
    // Retrieve active third-level users in a single query, select only 'user_id'
    $ruby_active_users = HelpStar::select('receiver_id')
        ->whereIn('receiver_id', function ($query) {
            $query->select('user_id')
                ->from('users')
                ->where('is_active', 1)
                ->where('is_green', 1)
                ->where('status', 'Active')
                ->whereNull('deleted_at')
                ->where('package_id', 6)
                ->orderBy('activated_date');
        })
        ->groupBy('receiver_id')
        ->havingRaw('COUNT(*) >= 42 AND COUNT(*) <= 72')
        ->limit(10)
        ->pluck('receiver_id')
        ->toArray();
    
    return $ruby_active_users;
}


public function level_upgrade_to_ruby_users($sender_id) {
    $userId = $sender_id;
    $ruby_active_users =   $this->ruby_active_users();

        if (!is_array($ruby_active_users) || empty($ruby_active_users)) {
            $ruby_active_users = ['PHC123456'];
        }
        // Retrieve last processed user ID from Redis
        $lastUserId = Redis::get('ruby_level_last_user_id');
       
        $helpReceived_count = 0;

        if ($lastUserId) {
            $lastUserIndex = array_search($lastUserId, $ruby_active_users);

            if ($lastUserIndex !== false && isset($ruby_active_users[$lastUserIndex + 1])) {
                $receiverUserId = $ruby_active_users[$lastUserIndex + 1];
            } else {
                $receiverUserId = $ruby_active_users[0];
            }
        } else {
            $receiverUserId = $ruby_active_users[0];
        }
 
       // Retrieve the receiver user and their package details
        $receiver = User::select('package_id','received_payments_count')->where('user_id', $receiverUserId)->first(); // payment receive
        $receiverPackage = Package::select('id','help')->where('id', $receiver->package_id)->first();
        $helpReceived_count = HelpStar::where('receiver_id', $receiverUserId)->where('receiver_position',6)->count();

    if($helpReceived_count == 4){
        $this->level_upgrade_to_emerald_users($userId);
        $this->sponser_help_gold_users($userId,'20000');
    }
    if ($helpReceived_count < 9) {  //0 <= 3
        // Create a new HelpStar entry
        $data = new HelpStar();
        $data->sender_id = $userId;
        $data->receiver_id = $receiverUserId;
        $data->amount = $receiverPackage->help; // Use the help amount from the package  -- 20000
        $data->sender_position = '5'; 
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
        $data->sender_position ='5';
        $data->receiver_position = '6';
        $data->received_payments_count = 1;
        $data->commitment_date = now();
        $data->confirm_date = null;
        $data->status = 'Pending';
        $data->save();   
    }
    Redis::set('ruby_level_last_user_id', $receiverUserId);
    $success['current_transaction'] =$receiverUserId;
    $success['new_transaction'] =$lastUserId;
}

public function emerald_active_users() {
    // Retrieve active third-level users in a single query, select only 'user_id'
    $ruby_active_users = HelpStar::select('receiver_id')
        ->whereIn('receiver_id', function ($query) {
            $query->select('user_id')
                ->from('users')
                ->where('is_active', 1)
                ->where('is_green', 1)
                ->where('status', 'Active')
                ->whereNull('deleted_at')
                ->where('package_id', 7)
                ->orderBy('activated_date');
        })
        ->groupBy('receiver_id')
        ->havingRaw('COUNT(*) >= 72 AND COUNT(*) <= 112')
        ->limit(10)
        ->pluck('receiver_id')
        ->toArray();
    
    return $ruby_active_users;
}

public function level_upgrade_to_emerald_users($sender_id) {
    $userId = $sender_id;
    $emerald_active_users =   $this->emerald_active_users();

        if (!is_array($emerald_active_users) || empty($emerald_active_users)) {
            $emerald_active_users = ['PHC123456'];
        }
        // Retrieve last processed user ID from Redis
        $lastUserId = Redis::get('emrald_level_last_user_id');
       
        $helpReceived_count = 0;

        if ($lastUserId) {
            $lastUserIndex = array_search($lastUserId, $emerald_active_users);

            if ($lastUserIndex !== false && isset($emerald_active_users[$lastUserIndex + 1])) {
                $receiverUserId = $emerald_active_users[$lastUserIndex + 1];
            } else {
                $receiverUserId = $emerald_active_users[0];
            }
        } else {
            $receiverUserId = $emerald_active_users[0];
        }
 
       // Retrieve the receiver user and their package details
        $receiver = User::select('package_id','received_payments_count')->where('user_id', $receiverUserId)->first(); // payment receive
        $receiverPackage = Package::select('id','help')->where('id', $receiver->package_id)->first();
        $helpReceived_count = HelpStar::where('receiver_id', $receiverUserId)->where('receiver_position',7)->count();

    if($helpReceived_count == 4){
        $this->level_upgrade_to_diamond_users($userId);
        $this->sponser_help_gold_users($userId,'100000');
    }
    if ($helpReceived_count < 9) {  //0 <= 3
        // Create a new HelpStar entry
        $data = new HelpStar();
        $data->sender_id = $userId;
        $data->receiver_id = $receiverUserId;
        $data->amount = $receiverPackage->help; // Use the help amount from the package -- 100000
        $data->sender_position = '5'; 
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
        $data->sender_position ='5';
        $data->receiver_position = '6';
        $data->received_payments_count = 1;
        $data->commitment_date = now();
        $data->confirm_date = null;
        $data->status = 'Pending';
        $data->save();   
    }
    Redis::set('emrald_level_last_user_id', $receiverUserId);
    $success['current_transaction'] =$receiverUserId;
    $success['new_transaction'] =$lastUserId;
}

public function diamond_active_users() {
    // Retrieve active third-level users in a single query, select only 'user_id'
    $diamond_active_users = HelpStar::select('receiver_id')
        ->whereIn('receiver_id', function ($query) {
            $query->select('user_id')
                ->from('users')
                ->where('is_active', 1)
                ->where('is_green', 1)
                ->where('status', 'Active')
                ->whereNull('deleted_at')
                ->where('package_id', 8)
                ->orderBy('activated_date');
        })
        ->groupBy('receiver_id')
        ->havingRaw('COUNT(*) >= 112 AND COUNT(*) <= 162')
        ->limit(10)
        ->pluck('receiver_id')
        ->toArray();
    
    return $diamond_active_users;
}

    public function level_upgrade_to_diamond_users($sender_id) {
        $userId = $sender_id;
        $ruby_active_users =   $this->diamond_active_users();

            if (!is_array($ruby_active_users) || empty($ruby_active_users)) {
                $ruby_active_users = ['PHC123456'];
            }
            // Retrieve last processed user ID from Redis
            $lastUserId = Redis::get('ruby_level_last_user_id');
        
            $helpReceived_count = 0;

            if ($lastUserId) {
                $lastUserIndex = array_search($lastUserId, $ruby_active_users);

                if ($lastUserIndex !== false && isset($ruby_active_users[$lastUserIndex + 1])) {
                    $receiverUserId = $ruby_active_users[$lastUserIndex + 1];
                } else {
                    $receiverUserId = $ruby_active_users[0];
                }
            } else {
                $receiverUserId = $ruby_active_users[0];
            }
    
        // Retrieve the receiver user and their package details
            $receiver = User::select('package_id','received_payments_count')->where('user_id', $receiverUserId)->first(); // payment receive
            $receiverPackage = Package::select('id','help')->where('id', $receiver->package_id)->first();
            $helpReceived_count = HelpStar::where('receiver_id', $receiverUserId)->where('receiver_position',8)->count();

        if($helpReceived_count == 4){
        
            $this->sponser_help_gold_users($userId,'1000000');
        }
        if ($helpReceived_count < 9) {  //0 <= 3
            // Create a new HelpStar entry
            $data = new HelpStar();
            $data->sender_id = $userId;
            $data->receiver_id = $receiverUserId;
            $data->amount = $receiverPackage->help; // Use the help amount from the package
            $data->sender_position = '5'; 
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
            $data->sender_position ='5';
            $data->receiver_position = '6';
            $data->received_payments_count = 1;
            $data->commitment_date = now();
            $data->confirm_date = null;
            $data->status = 'Pending';
            $data->save();   
        }
        Redis::set('ruby_level_last_user_id', $receiverUserId);
        $success['current_transaction'] =$receiverUserId;
        $success['new_transaction'] =$lastUserId;
    }

    public function re_entry_payment_to_admin($sender_id,$amount){
        $sender_package =  User::where('user_id',$sender_id)->select('package_id')->first();
        $receiver_package_id = $sender_package->package_id + 1;

        $adminId = 'PHC123456';
        $admin_payment = new HelpStar();
        $admin_payment->sender_id = $sender_id;
        $admin_payment->receiver_id = 'PHC123456'; // Payment goes to admin
        $admin_payment->amount = $amount; // Use the help amount from the package
        $admin_payment->sender_position =$sender_package->package_id;
        $admin_payment->receiver_position = $receiver_package_id;
        $admin_payment->received_payments_count = 1;
        $admin_payment->commitment_date = now();
        $admin_payment->confirm_date = null;
        $admin_payment->status = 'Pending';
        $admin_payment->save();   
    }

    public function re_entry_to_star($sender_id){

        $data = $this->active_users();

        if (!is_array($data) || empty($data)) {
            $data = ['PHC123456'];
        }
        $lastUserId = Redis::get('last_user_id');
        $receiverUserId = null;
        $helpReceived_count = 0;

        if ($lastUserId) {
            $lastUserIndex = array_search($lastUserId, $data);

            // Determine the next user in line
            if ($lastUserIndex !== false && isset($data[$lastUserIndex + 1])) {
                $receiverUserId = $data[$lastUserIndex + 1];
            } else {
                // If we're at the end of the list, start from the beginning
                $receiverUserId = $data[0];
            }
        } else {
            // First time, start with the first user in the list
            $receiverUserId = $data[0];
        }

        $this->seven_level_sponser($receiverUserId);
        // Retrieve the receiver user and their package details
        $receiver = User::where('user_id', $receiverUserId)->select('package_id','user_id','received_payments_count')->first(); // payment receive
        $receiverPackage = Package::where('id', $receiver->package_id)->select('help','id','help_count')->first();

        $helpReceived_count = HelpStar::where('receiver_id', $receiverUserId)->where('receiver_position',2)->count();
        if($helpReceived_count == 1){
            $this->level_upgrade_to_silver_users($userId);
        }
        if ($helpReceived_count < 3) {  //0 <= 3
            // Create a new HelpStar entry
            $HelpStar = new HelpStar();
            $HelpStar->sender_id = $sender_id;
            $HelpStar->receiver_id = $receiverUserId;
            $HelpStar->amount = $receiverPackage->help; // Use the help amount from the package
            $HelpStar->sender_position = '1'; 
            $HelpStar->receiver_position = $receiverPackage->id;
            $HelpStar->received_payments_count = 1;
            $HelpStar->commitment_date = now();
            $HelpStar->confirm_date = null;
            $HelpStar->status = 'Pending';
            $HelpStar->save();
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
            $HelpStarAdmin->sender_id = $sender_id;
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
        Redis::set('last_user_id', $receiverUserId);
        $success['user'] =$user;
        $success['current_transaction'] =$receiverUserId;
        $success['new_transaction'] =$lastUserId;
            return $this->sendResponse($success, 'User registered successfully');
    }


    public function seven_level_sponser($sender_id){
        $user = User::where('user_id',$sender_id)->select('user_id','sponsor_id')->first();
   
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
 
                       
    }
}