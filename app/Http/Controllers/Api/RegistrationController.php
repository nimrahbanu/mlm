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
use App\Models\Support;
use App\Models\EPinTransfer;
use App\Models\SevenLevelTransaction;
use App\Mail\RegistrationEmailToCustomer;
use App\Mail\ResetPasswordMessageToCustomer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Jobs\UpdateUserStatus;
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

        $this->middleware('guest:web')->except('logout');
    }
    public function first_user_id() {
        $firstUser = User::first();
        if ($firstUser) {
            return $firstUser->user_id;
        }
        return null; // Return null if no user is found
    }
    

    public function registration_store(Request $request) {
        $firstUserId = $this->first_user_id(); // Call the function to get the first user ID
      
        $token = hash('sha256',time());
       
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'password' => 'required',
            're_password' => 'required|same:password',
            'sponsor_id' => 'required|exists:users,user_id',
            'phone' => 'required|numeric|digits:10|unique:users,phone', 
            'phone_pay_no' => 'required|numeric|digits:10',
            'confirm_phone_pay_no' => 'required|same:phone_pay_no|digits:10',
            'registration_code' => 'required|unique:users,registration_code'
        ], [
            're_password.required' => 'Retype Password is required',
            're_password.same' => '	Both Passwords must match',
            'registration_code.required' => 'The registration code is required.',
            'registration_code.unique' => 'The registration code has already been used.',
            'phone.unique' => 'The phone has already been used.'
        ]);
        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(),'Validation Error.');
        }
        try{
        DB::beginTransaction();

            $sponsor = User::where('user_id',$request->sponsor_id)
            ->select('id','user_id','sponsor_id', 'activated_date','status','is_green','package_id','updated_at')->where('is_green','1')->first();
            if ($sponsor) {
                if ($sponsor->package_id == 1) {
                    $sponsor->package_id = 2;
                }
                $sponsor->update([
                    // 'is_active' => 1,
                    'package_id' => $sponsor->package_id, // update package_id
                    'activated_date' => now()
                ]);
                // if($sponsor->is_green == 1 && $sponsor->is_active == 1 && $sponsor->status=='InActive'){
                //     $sponsor->update([
                //         'status' => 'Active',
                //     ]);
                // }
            }else{
                return $this->sendError('Sponsor Id is not activated. Please try again.');

            }
     
            if ($request->has('registration_code')) {
                $epin = EPinTransfer::select('id','e_pin','is_used','updated_at')->where('e_pin', $request->registration_code)
                    ->where('is_used', '0')
                    ->first();
                    if ($epin) {
                        $userId = $this->generateUniqueUserId(); // generate user_id
                        $epin->is_used = '1';
                        $epin->save();

                        $data = $request->only((new User)->getFillable());
                        $data['password'] = Hash::make($request->password);
                        $data['token'] = $token;
                        $data['status'] = 'InActive';
                        $data['sponsor_id'] = $request->sponsor_id ?? $firstUserId;
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
                        DB::commit();
                            
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
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->sendError($e->getMessage(),'Unable to register. Please try again.');
        }
    }

    public function generateUniqueUserId() {
        // Get the current time formatted as HHMM + SS
        $timestamp = now()->format('Hi') . now()->format('s'); // Get HHMM + SS
        $uniquePart = substr($timestamp, -5); // Get the last 6 digits
    
        // Add a random number to ensure uniqueness
        $randomNumber = mt_rand(100, 999); // Generate a random 4-digit number
        $userId = 'HC' . $uniquePart . $randomNumber; // Combine with the prefix and random number
    
        // Check if the user ID already exists
        while (User::where('user_id', $userId)->exists()) {
            // If it exists, regenerate the random number
            $randomNumber = mt_rand(1000, 9999);
            $userId = 'HC' . $uniquePart . $randomNumber; // Combine again
        }
    
        return $userId;
    }
    public function star_level_transaction($userId){  
       $seven_level =  Helper::seven_level_sponser_transaction($userId);
        $data = Helper::active_users(2,'star_complete');
        $lastUserId = Redis::get('last_user_id');
        $receiverUserId = null;

        $receiverUserId = $this->getNextReceiverUserId($data, $lastUserId);

        // Retrieve the receiver user and their package details
        $receiver = User::select('package_id','user_id','received_payments_count','id')->where('user_id', $receiverUserId)->first(); // payment receive
        $receiverPackage = Package::select('help','id','help_count')->where('id', $receiver->package_id)->first();

        // Check how many times this user has received help
        $helpReceived_count = HelpStar::where('receiver_id', $receiverUserId)->where('receiver_position',2)->count();
        if($helpReceived_count == 1){
           $this->level_upgrade_to_silver_users($receiverUserId);
        }

        if ($helpReceived_count < 4) {  //0 <= 3
        // Create a new HelpStar entry
            $this->createHelpStarEntry($userId, $receiverUserId, $receiverPackage, $helpReceived_count);
        }else{
        // Payment goes to admin
            $this->createHelpStarEntry($userId, $this->first_user_id(), 1, 300); // Admin ID
        }
        Redis::set('last_user_id', $receiverUserId);
        $success = [
            'user_id' =>$userId,
            'next_transaction' =>$receiverUserId,
            'new_transaction' =>$lastUserId
        ];
        return $success;
    }

    private function createHelpStarEntry($senderId, $receiverId, $receiverPackage, $helpReceivedCount) {
        $HelpStar = new HelpStar();
        $HelpStar->sender_id = $senderId;
        $HelpStar->receiver_id = $receiverId;
        $HelpStar->amount = 300; // Use the help amount from the package
        $HelpStar->sender_position = 1;
    
        if ($receiverPackage) {
            $HelpStar->receiver_position = isset($receiverPackage->id) ? $receiverPackage->id : 2;
            // If the user has received the maximum number of helps, update their package
            if ($helpReceivedCount + 1 == 3) {
                $receiver = User::where('user_id', $receiverId)->first();
                $receiver->package_id = '3';
                $receiver->star_complete = '1';
                $receiver->save();
            }
        } else {
            $HelpStar->receiver_position = 2; // Admin position
        }
    
        $HelpStar->received_payments_count = 1;
        $HelpStar->commitment_date = now();
        $HelpStar->confirm_date = null;
        $HelpStar->status = 'Pending';
        $HelpStar->save();
    
        // Update the receiver's received payment count if not admin
        if ($receiverPackage) {
            $receiver = User::where('user_id', $receiverId)->first();
            $receiver->received_payments_count = $helpReceivedCount + 1;
            $receiver->save();
        }
    }


    private function getNextReceiverUserId(array $data, ?string $lastUserId): ?string {
        if ($lastUserId) {
            $lastUserIndex = array_search($lastUserId, $data);
            return $lastUserIndex !== false && isset($data[$lastUserIndex + 1]) ? $data[$lastUserIndex + 1] : $data[0];
        }
        return $data[0]; // First time, start with the first user in the list
    }

    public function level_upgrade_to_silver_users($sender_id) {
        $userId = $sender_id;
        $silver_level_users = Helper::active_users(3,'silver_complete');
            $lastUserId = Redis::get('silver_level_user_id');
           
            $helpReceived_count = 0;
            $receiverUserId = $this->getNextReceiverUserId($silver_level_users, $lastUserId);
 
        // Retrieve the receiver user and their package details
        $receiver = User::select('package_id','received_payments_count')->where('user_id', $receiverUserId)->first(); // payment receive
        $receiverPackage = Package::select('id','help','help_count')->where('id', $receiver->package_id)->first();
        $helpReceived_count = HelpStar::where('receiver_id', $receiverUserId)->where('receiver_position',3)->count();

        if($helpReceived_count == 4){
            $this->level_upgrade_to_gold_users($receiverUserId);
            Helper::sponser_help($sender_id,'600'); // send sponser help
        } 
        if($helpReceived_count  == 6){
            $this->star_level_transaction($userId); // re birth for star level 1 times
        }
        // if($helpReceived_count  == 8){
        //     $this->re_entry_payment_to_admin($userId,300); // send payment to admin 1 time
        // }
        if ($helpReceived_count < 10) {   
            // Create a new HelpStar entry
            $data = new HelpStar();
            $data->sender_id = $userId;
            $data->receiver_id = $receiverUserId;
            $data->amount = 600; //$receiverPackage->help; // Use the help amount from the package ----600
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
            $adminId = $this->first_user_id();
            $data = new HelpStar();
            $data->sender_id = $userId;
            $data->receiver_id = $adminId; // Payment goes to admin
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
        $gold_active_users = Helper::active_users(4,'gold_complete');

            // Retrieve last processed user ID from Redis
        $lastUserId = Redis::get('gold_level_last_user_id');
        $helpReceived_count = 0;

        $receiverUserId = $this->getNextReceiverUserId($gold_active_users, $lastUserId);

        // Retrieve the receiver user and their package details
        $receiver = User::select('package_id','received_payments_count')->where('user_id', $receiverUserId)->first(); // payment receive

        $receiverPackage = Package::select('id','help')->where('id', $receiver->package_id)->first();

        $helpReceived_count = HelpStar::where('receiver_id', $receiverUserId)->where('receiver_position',4)->count();

        if($helpReceived_count == 4){
            $this->level_upgrade_to_platinum_users($receiverUserId); 
            Helper::sponser_help($userId,'2000');
        }
   
        if ($helpReceived_count  == [5,6,7]) {
            // for ($i = 0; $i < 3; $i++) {
                $this->star_level_transaction($userId); // re birth for star level 3 times
            // }
        }
      
        if($helpReceived_count  == 8){
            // for ($i = 0; $i < 3; $i++) {
                $this->re_entry_payment_to_admin($userId,300);  // send payment to admin 1// 
            // }
            
        }
        if ($helpReceived_count < 11) {  //0 <= 3
            // Create a new Help Gold entry

            $data = new HelpStar();
            $data->sender_id = $userId;
            $data->receiver_id = $receiverUserId;
            $data->amount = 2000; // Use the help amount from the package -- 2000
            $data->sender_position = '3'; 
            $data->receiver_position = $receiverPackage->id;
            $data->received_payments_count = 1;
            $data->commitment_date = now();
            $data->confirm_date = null;
            $data->status = 'Pending';
            $data->save();
            // If the user has received the maximum number of helps, update their package
            if ($helpReceived_count+1  == 10) {
                    $receiver->package_id = 5;
                    $receiver->save();
            }
            // Update the user's received payment count
            $receiver->received_payments_count = $helpReceived_count + 1;
            $receiver->save();

            // Update the last processed user ID for the next call
        }else{
            $adminId = $this->first_user_id();

            $data = new HelpStar();
            $data->sender_id = $userId;
            $data->receiver_id = $adminId; // Payment goes to admin
            $data->amount = 2000; // Use the help amount from the package
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
        $platinum_active_users = Helper::active_users(5,'platinum_complete');
 
        // Retrieve last processed user ID from Redis
        $lastUserId = Redis::get('platinum_level_last_user_id');
           
        $helpReceived_count = 0;
        $receiverUserId = $this->getNextReceiverUserId($platinum_active_users, $lastUserId);
    
        // Retrieve the receiver user and their package details
        $receiver = User::select('package_id','received_payments_count')->where('user_id', $receiverUserId)->first(); // payment receive
        $receiverPackage = Package::select('id','help')->where('id', $receiver->package_id)->first();
        $helpReceived_count = HelpStar::where('receiver_id', $receiverUserId)->where('receiver_position',5)->count();

        if($helpReceived_count == 4){
            $this->level_upgrade_to_ruby_users($receiverUserId);
            Helper::sponser_help($userId,'6000');
        }
        if ($helpReceived_count  == [5,7,9,11,13]) {
            for ($i = 0; $i < 3; $i++) {
                $this->star_level_transaction($userId); // re birth for platinum level 15
            }
        }
      
        if($helpReceived_count  == [15,16,17,18,19]){
            // for ($i = 0; $i < 2; $i++) {
                $this->re_entry_payment_to_admin($userId,300); // send payment to admin 5 time
                // send payment to admin
            // }
            
        }
        if ($helpReceived_count < 21) {  //0 <= 3
            // Create a new Help Platinum entry

            $data = new HelpStar();
            $data->sender_id = $userId;
            $data->receiver_id = $receiverUserId;
            $data->amount = 6000; // Use the help amount from the package --6000
            $data->sender_position = '4'; 
            $data->receiver_position = $receiverPackage->id;
            $data->received_payments_count = 1;
            $data->commitment_date = now();
            $data->confirm_date = null;
            $data->status = 'Pending';
            $data->save();
            // If the user has received the maximum number of helps, update their package
            if ($helpReceived_count+1  == 20) {
                    $receiver->package_id = 6;
                    $receiver->save();
            }
            // Update the user's received payment count
            $receiver->received_payments_count = $helpReceived_count + 1;
            $receiver->save();

            // Update the last processed user ID for the next call
        }else{
            $adminId = $this->first_user_id();
            $data = new HelpStar();
            $data->sender_id = $userId;
            $data->receiver_id = $adminId; // Payment goes to admin
            $data->amount = 6000; // Use the help amount from the package
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
        $ruby_active_users = Helper::active_users(6,'ruby_complete');

            // Retrieve last processed user ID from Redis
        $lastUserId = Redis::get('ruby_level_last_user_id');
        
        $helpReceived_count = 0;
        $receiverUserId = $this->getNextReceiverUserId($ruby_active_users, $lastUserId);
    
        // Retrieve the receiver user and their package details
            $receiver = User::select('package_id','received_payments_count')->where('user_id', $receiverUserId)->first(); // payment receive
            $receiverPackage = Package::select('id','help')->where('id', $receiver->package_id)->first();
            $helpReceived_count = HelpStar::where('receiver_id', $receiverUserId)->where('receiver_position',6)->count();

        if($helpReceived_count == 6){ // after receiving 7 help
            $this->level_upgrade_to_emerald_users($receiverUserId);
            Helper::sponser_help($userId,'20000');
        }
        if ($helpReceived_count  == [7,8,9,10,11,12,13,14,15,16,17,18,19,20]) {
            for ($i = 0; $i < 3; $i++) {
                $this->star_level_transaction($userId); // re birth for star level
            }
        }

        if ($helpReceived_count  == 21) {
                $this->star_level_transaction($userId); // re birth for star level
        }
    
        if($helpReceived_count  == [22,23,24]){
            for ($i = 0; $i < 2; $i++) {
                $this->re_entry_payment_to_admin($userId,300);  // send payment to admin
            }
        }

        if($helpReceived_count  == [25]){
            for ($i = 0; $i < 2; $i++) {
                $this->re_entry_payment_to_admin($userId,300);  // send payment to admin
            }
        }
        if ($helpReceived_count < 31) {  //0 <= 3
            // Create a new HelpStar entry

            $data = new HelpStar();
            $data->sender_id = $userId;
            $data->receiver_id = $receiverUserId;
            $data->amount = 20000; // Use the help amount from the package  -- 20000
            $data->sender_position = '5'; 
            $data->receiver_position = $receiverPackage->id;
            $data->received_payments_count = 1;
            $data->commitment_date = now();
            $data->confirm_date = null;
            $data->status = 'Pending';
            $data->save();
            // If the user has received the maximum number of helps, update their package
            if ($helpReceived_count+1  == 30) {
                    $receiver->package_id = 7;
                    $receiver->save();
            }
            // Update the user's received payment count
            $receiver->received_payments_count = $helpReceived_count + 1;
            $receiver->save();

            // Update the last processed user ID for the next call
        }else{
            $adminId = $this->first_user_id();
            $data = new HelpStar();
            $data->sender_id = $userId;
            $data->receiver_id = $adminId; // Payment goes to admin
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
        $emerald_active_users = Helper::active_users(7,'emrald_complete');
    
            // Retrieve last processed user ID from Redis
            $lastUserId = Redis::get('emrald_level_last_user_id');
            $receiverUserId = $this->getNextReceiverUserId($emerald_active_users, $lastUserId);
        
            $helpReceived_count = 0;

        // Retrieve the receiver user and their package details
            $receiver = User::select('package_id','received_payments_count')->where('user_id', $receiverUserId)->first(); // payment receive
            $receiverPackage = Package::select('id','help')->where('id', $receiver->package_id)->first();
            $helpReceived_count = HelpStar::where('receiver_id', $receiverUserId)->where('receiver_position',7)->count();

        if($helpReceived_count == 6){
            $this->level_upgrade_to_diamond_users($receiverUserId);
            Helper::sponser_help($userId,'100000');
        }
        if ($helpReceived_count  == [7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26]) {
            for ($i = 0; $i < 3; $i++) {
                $this->star_level_transaction($userId); // re birth for star level
            }
        }
    
        if($helpReceived_count  == [35,36,37,38,39,40]){
            for ($i = 0; $i < 2; $i++) {
                $this->re_entry_payment_to_admin($userId,300);  // send payment to admin
            }
            
        }
        if ($helpReceived_count < 41) {  //0 <= 3
            // Create a new Help Emrald entry

            $data = new HelpStar();
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
                    $receiver->package_id = 8;
                    $receiver->save();
            }
            // Update the user's received payment count
            $receiver->received_payments_count = $helpReceived_count + 1;
            $receiver->save();

            // Update the last processed user ID for the next call
        }else{
            $adminId = $this->first_user_id();
            $data = new HelpStar();
            $data->sender_id = $userId;
            $data->receiver_id = $adminId; // Payment goes to admin
            $data->amount = 100000; // Use the help amount from the package
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
        $diamond_active_users = Helper::active_users(8,'diamond_complete');

            // Retrieve last processed user ID from Redis
            $lastUserId = Redis::get('diamond_level_last_user_id');
        
            $helpReceived_count = 0;
            $receiverUserId = $this->getNextReceiverUserId($diamond_active_users, $lastUserId);

        // Retrieve the receiver user and their package details
            $receiver = User::select('package_id','received_payments_count')->where('user_id', $receiverUserId)->first(); // payment receive
            $receiverPackage = Package::select('id','help')->where('id', $receiver->package_id)->first();
            $helpReceived_count = HelpStar::where('receiver_id', $receiverUserId)->where('receiver_position',8)->count();

        if($helpReceived_count == 3){
        
            Helper::sponser_help($userId,'1000000');
        }
        if ($helpReceived_count < 51) {  //0 <= 3
            // Create a new Help Diamond entry
            $data = new HelpStar();
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
                    $receiver->package_id = 1;
                    $receiver->status = 'InActive';
                    $receiver->save();
            }
            // Update the user's received payment count
            $receiver->received_payments_count = $helpReceived_count + 1;
            $receiver->save();

            // Update the last processed user ID for the next call
        }else{
            $adminId = $this->first_user_id();
            $data = new HelpStar();
            $data->sender_id = $userId;
            $data->receiver_id = $adminId; // Payment goes to admin
            $data->amount = 500000; // Use the help amount from the package
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

 

 
    public function re_entry_payment_to_admin($userId,$amount){
        $this->seven_level_sponser_transaction($user_id);

        $sender_package =  User::where('user_id',$user_id)->select('package_id')->first();
        $receiver_package_id = $sender_package->package_id + 1;

        $adminId = $this->first_user_id();
        $admin_payment = new HelpStar();
        $admin_payment->sender_id = $user_id;
        $admin_payment->receiver_id = $this->first_user_id(); // Payment goes to admin
        $admin_payment->amount = $amount; // Use the help amount from the package
        $admin_payment->sender_position =$sender_package->package_id;
        $admin_payment->receiver_position = 1;
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

    public  function test($package_id,$key){
        $user_ids = User::where('is_green', '0')
        ->where('status', 'InActive')
        ->pluck('user_id');
        $giving_users = HelpStar::whereIn('sender_id', $user_ids)
        ->whereNotNull('confirm_date')
        ->pluck('sender_id');

        return $giving_users;
 

$success = [$a,$b,$c,$d,$e,$f,$lastUserId];
return $success;
            $active_users = User::where('is_green', 1)
            ->where('status', 'Active')
            ->whereNull('deleted_at')
            ->where('package_id', $package_id)
            ->where($key, '0')
            // ->where('star_complete', '0')
            ->orderBy('activated_date')
            ->limit(10) // Apply limit early
            ->pluck('user_id') // Fetch only user_id
            ->toArray();
            return !empty($active_users) ? $active_users : [self::first_user_id()]; // Return the dynamic ID if no active users
        }
    public  function test_n($package_id,$key){

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