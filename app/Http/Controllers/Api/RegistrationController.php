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
use App\Models\HelpSilver;
use App\Models\HelpGold;
use App\Models\HelpPlatinum;
use App\Models\HelpRuby;
use App\Models\HelpEmrald;
use App\Models\HelpDiamond;
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
use Helper;
use Validator;
use Illuminate\Support\Facades\Mail;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Redis;

use App\Mail\AppRegistrationMail;

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
            'phone' => 'required|numeric|digit:10', // Example for a 10-digit phone number
            // 'phone' => 'required|numeric|unique:users,phone', // Example for a 10-digit phone number
            "phone_pay_no" => "required|numeric|digit:10",
            "confirm_phone_pay_no"=>"required|same:phone_pay_no|digit:10",
            "registration_code" => "required|unique:users,registration_code"
            // "registration_code" => "required|unique:users,registration_code"
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
                if($sponsor->is_green == 1 && $sponsor->is_active == 1){
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
                        $userId = Helper::generateUniqueUserId(); // generate user_id
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
                           $success = $this->star_level_transaction($user->user_id);
                           $mailBody = [
                            'name'        => @$data['name'],
                            'email'       => @$data['email'],
                            'password'      => $request->password,
                            'sponsor_id'      => @$data['sponsor_id'],
                            'phone_pay_no'      => @$data['phone_pay_no'],
                        ];

                    
                        Mail::to($request->email)->send(new AppRegistrationMail($mailBody));
                            
                            return $this->sendResponse($data, 'User registered successfully');
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
            dd($e);
            // DB::rollBack();
            return $this->sendError('Unable to register. Please try again.');
        }
    }

    public function star_level_transaction($userId){  
       $seven_level =  Helper::seven_level_sponser_transaction($userId);
        $data = Helper::star_active_users();
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
        $receiver = User::select('package_id','user_id','received_payments_count','id')->where('user_id', $receiverUserId)->first(); // payment receive
        $receiverPackage = Package::select('help','id','help_count')->where('id', $receiver->package_id)->first();

        // Check how many times this user has received help
        $helpReceived_count = HelpStar::where('receiver_id', $receiverUserId)->where('receiver_position',2)->count();
        // echo '$receiverPackage->help_count'.$receiverPackage->help_count.'<br>'; 
            // echo '$helpReceived_count'.$helpReceived_count;
                // If the user is not eligible for help, redirect the payment to the admin
        if($helpReceived_count == 1){
           $this->level_upgrade_to_silver_users($receiverUserId);
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
                    $receiver->package_id = '3';
                    $receiver->star_complete = '1';
                $receiver->save();
        }
        // Update the user's received payment count
        $receiver->received_payments_count = $helpReceived_count + 1;
        $receiver->save();

            // Update the last processed user ID for the next call
        }else{
            $adminId = 'PHC123456';
            $HelpStarAdmin = new HelpStar();
            $HelpStarAdmin->sender_id = $userId;
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
        $success = [
            'user_id' =>$userId,
            'next_transaction' =>$receiverUserId,
            'new_transaction' =>$lastUserId
        ];
        return $success;
    }

    public function level_upgrade_to_silver_users($sender_id) {
        $userId = $sender_id;
        $silver_level_users = Helper::silver_active_users();
         
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
            $this->level_upgrade_to_gold_users($receiverUserId);
            Helper::sponser_help($sender_id,'600'); // send sponser help
        } 
        if($helpReceived_count  == [6,7]){
            $this->star_level_transaction($userId); // re birth for star level
        }
        if($helpReceived_count  == 8){
            $this->re_entry_payment_to_admin($userId); // send payment to admin
        }
        if ($helpReceived_count < 9) {  //0 <= 3
            // Create a new HelpSilver entry
            $data = new HelpSilver();
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
            $data = new HelpSilver();
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

    
    public function level_upgrade_to_gold_users($sender_id) {
        /** help 2000*10 =20k
         * upgrade 6000
         * 5 help = send sponser help 2000
         * re-entry 9 id 550*9 = 4950
         * profit 7050
         */
        $userId = $sender_id;
        $gold_active_users = Helper::gold_active_users();

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
            $this->level_upgrade_to_platinum_users($receiverUserId); 
            Helper::sponser_help($userId,'2000');
        }
   
        if ($helpReceived_count  == [6,7]) {
            for ($i = 0; $i < 3; $i++) {
                $this->star_level_transaction($userId); // re birth for star level  need to hold if any person is not exist
            }
        }
      
        if($helpReceived_count  == 8){
            for ($i = 0; $i < 3; $i++) {
                $this->re_entry_payment_to_admin($userId);  // send payment to admin // 
            }
            
        }
        if ($helpReceived_count < 10) {  //0 <= 3
            // Create a new HelpGold entry

            $data = new HelpGold();
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
            $data = new HelpGold();
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


    public function level_upgrade_to_platinum_users($sender_id) {
         /** help 6000*20 =1,20,000 k
         *4 help =  upgrade 20,000 
         * 5 help = send sponser help 6000
         * re-entry 30 id 550*30 = 16,500
         * profit 77,500
         * */
        $userId = $sender_id;
        $platinum_active_users = Helper::platinum_active_users();

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
            $this->level_upgrade_to_ruby_users($receiverUserId);
            Helper::sponser_help($userId,'6000');
        }
        if ($helpReceived_count  == [6,7]) {
            for ($i = 0; $i < 10; $i++) {
                $this->star_level_transaction($userId); // re birth for star level
            }
        }
      
        if($helpReceived_count  == 8){
            for ($i = 0; $i < 10; $i++) {
                $this->re_entry_payment_to_admin($userId);  // send payment to admin
            }
            
        }
        if ($helpReceived_count < 20) {  //0 <= 3
            // Create a new HelpPlatinum entry

            $data = new HelpPlatinum();
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
            $data = new HelpPlatinum();
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

    
 


public function level_upgrade_to_ruby_users($sender_id) {
     /** help 20,000*30 =6,00,000k
         *  upgrade 1,00,000 K
         * 7 help = send sponser help 20,000
         * re-entry 60 id 550*60 = 33,000
         * profit 4,47,000
         */
    $userId = $sender_id;
    $ruby_active_users =   Helper::ruby_active_users();

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

    if($helpReceived_count == 6){ // after receiving 7 help
        $this->level_upgrade_to_emerald_users($receiverUserId);
        Helper::sponser_help($userId,'20000');
    }
    if ($helpReceived_count  == [7,8,9,10,11]) {
        for ($i = 0; $i < 10; $i++) {
            $this->star_level_transaction($userId); // re birth for star level
        }
    }
  
    if($helpReceived_count  == 12){
        for ($i = 0; $i < 10; $i++) {
            $this->re_entry_payment_to_admin($userId);  // send payment to admin
        }
        
    }
    if ($helpReceived_count < 30) {  //0 <= 3
        // Create a new HelpRuby entry

        $data = new HelpRuby();
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
        $data = new HelpRuby();
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
 

public function level_upgrade_to_emerald_users($sender_id) {
     /** help 1,00,000 * 40 =40,00,000k
         *5 help =  upgrade 5,00,000 K
         * 7 help = send sponser help 1,00,000
         * re-entry 101 id 550*101 = 33,000
         * profit 33,44,450
         */
    $userId = $sender_id;
    $emerald_active_users =   Helper::emerald_active_users();

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

    if($helpReceived_count == 6){
        $this->level_upgrade_to_diamond_users($receiverUserId);
        Helper::sponser_help($userId,'100000');
    }
    if ($helpReceived_count  == [7,8,9,10,11,12,13,14,15]) {
        for ($i = 0; $i < 10; $i++) {
            $this->star_level_transaction($userId); // re birth for star level
        }
    }
  
    if($helpReceived_count  == 16){
        for ($i = 0; $i < 11; $i++) {
            $this->re_entry_payment_to_admin($userId);  // send payment to admin
        }
        
    }
    if ($helpReceived_count < 40) {  //0 <= 3
        // Create a new HelpEmrald entry

        $data = new HelpEmrald();
        $data->sender_id = $userId;
        $data->receiver_id = $receiverUserId;
        $data->amount = $receiverPackage->help; // Use the help amount from the package -- 100000
        $data->sender_position = '6'; 
        $data->receiver_position = $receiverPackage->id;
        $data->received_payments_count = 1;
        $data->commitment_date = now();
        $data->confirm_date = null;
        $data->status = 'Pending';
        $data->save();
        // If the user has received the maximum number of helps, update their package
        if ($helpReceived_count+1  == 40) {
                $receiver->package_id = 4;
                $receiver->save();
        }
        // Update the user's received payment count
        $receiver->received_payments_count = $helpReceived_count + 1;
        $receiver->save();

        // Update the last processed user ID for the next call
    }else{
        $adminId = 'PHC123456';
        $data = new HelpEmrald();
        $data->sender_id = $userId;
        $data->receiver_id = 'PHC123456'; // Payment goes to admin
        $data->amount = 600; // Use the help amount from the package
        $data->sender_position ='6';
        $data->receiver_position = '7';
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
 
    public function level_upgrade_to_diamond_users($sender_id) {
        /** help 5,00,000*50 =2,50k
         * 3 help = send sponser help 10,,00,000
         * profit 2,40,00,000
         */
        $userId = $sender_id;
        $diamond_active_users =   Helper::diamond_active_users();

            if (!is_array($diamond_active_users) || empty($diamond_active_users)) {
                $diamond_active_users = ['PHC123456'];
            }
            // Retrieve last processed user ID from Redis
            $lastUserId = Redis::get('diamond_level_last_user_id');
        
            $helpReceived_count = 0;

            if ($lastUserId) {
                $lastUserIndex = array_search($lastUserId, $diamond_active_users);

                if ($lastUserIndex !== false && isset($diamond_active_users[$lastUserIndex + 1])) {
                    $receiverUserId = $diamond_active_users[$lastUserIndex + 1];
                } else {
                    $receiverUserId = $diamond_active_users[0];
                }
            } else {
                $receiverUserId = $diamond_active_users[0];
            }
    
        // Retrieve the receiver user and their package details
            $receiver = User::select('package_id','received_payments_count')->where('user_id', $receiverUserId)->first(); // payment receive
            $receiverPackage = Package::select('id','help')->where('id', $receiver->package_id)->first();
            $helpReceived_count = HelpStar::where('receiver_id', $receiverUserId)->where('receiver_position',8)->count();

        if($helpReceived_count == 3){
        
            Helper::sponser_help($userId,'1000000');
        }
        if ($helpReceived_count < 50) {  //0 <= 3
            // Create a new HelpDiamond entry
            $data = new HelpDiamond();
            $data->sender_id = $userId;
            $data->receiver_id = $receiverUserId;
            $data->amount = $receiverPackage->help; // Use the help amount from the package
            $data->sender_position = '7'; 
            $data->receiver_position = $receiverPackage->id;
            $data->received_payments_count = 1;
            $data->commitment_date = now();
            $data->confirm_date = null;
            $data->status = 'Pending';
            $data->save();
            // If the user has received the maximum number of helps, update their package
            if ($helpReceived_count+1  == 50) {
                    $receiver->package_id = 8;
                    $receiver->save();
            }
            // Update the user's received payment count
            $receiver->received_payments_count = $helpReceived_count + 1;
            $receiver->save();

            // Update the last processed user ID for the next call
        }else{
            $adminId = 'PHC123456';
            $data = new HelpDiamond();
            $data->sender_id = $userId;
            $data->receiver_id = 'PHC123456'; // Payment goes to admin
            $data->amount = 600; // Use the help amount from the package
            $data->sender_position ='7';
            $data->receiver_position = '8';
            $data->received_payments_count = 1;
            $data->commitment_date = now();
            $data->confirm_date = null;
            $data->status = 'Pending';
            $data->save();   
        }
        Redis::set('diamond_level_last_user_id', $receiverUserId);
        $success['current_transaction'] =$receiverUserId;
        $success['new_transaction'] =$lastUserId;
    }

 

 
    public function re_entry_payment_to_admin($sender_id,$amount){
        $this->seven_level_sponser_transaction($user_id);

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

    public function sponser_help(Request $request){
        $id = $request->id;
      $data =  Helper::sponser_help($id,100);
      return $data;
      
    }

    public  function test($user_id){
        $user = User::where('user_id',$user_id)->select('user_id','sponsor_id','id')->with('sponsor:id,user_id')->first();
        $user_id_sender = $user_id;
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
        return $user_id;
    }

}