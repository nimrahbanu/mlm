<?php
use App\Models\User;
use App\Models\SevenLevelTransaction;
use App\Models\Package;
use App\Models\HelpStar;
class Helper

{
    public  function generateUniqueUserId() {
        do {
            $userId = 'PHC' . mt_rand(100000, 999999); // Generate a random 6-digit number
        } while (User::where('user_id', $userId)->exists()); // Check if the user_id already exists

        return $userId;
    }

    public  function active_users($package_id,$key) {
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
    public function silver_active_users() {
        $silver_users = User::where('is_green', 1)
        ->where('status', 'Active')
        ->whereNull('deleted_at')
        ->where('package_id', 3)
        ->where('silver_complete', '0')
        ->orderBy('activated_date')
        ->limit(10) // Apply limit early
        ->pluck('user_id') // Fetch only user_id
        ->toArray();
        return !empty($silver_users) ? $silver_users : [self::first_user_id()]; // Return the dynamic ID if no active users

    }

    public function gold_active_users() {
        $gold_helps = User::where('is_green', 1)
        ->where('status', 'Active')
        ->whereNull('deleted_at')
        ->where('package_id', 4)
        ->where('gold_complete', '0')
        ->orderBy('activated_date')
        ->limit(10) // Apply limit early
        ->pluck('user_id') // Fetch only user_id
        ->toArray();
        return !empty($gold_helps) ? $gold_helps : [self::first_user_id()]; // Return the dynamic ID if no active users
    }
   
    public function platinum_active_users() {
        $platinum_helps = User::where('is_green', 1)
        ->where('status', 'Active')
        ->whereNull('deleted_at')
        ->where('package_id', 5)
        ->where('platinum_complete', '0')
        ->orderBy('activated_date')
        ->limit(10) // Apply limit early
        ->pluck('user_id') // Fetch only user_id
        ->toArray();
        return !empty($platinum_helps) ? $platinum_helps : [self::first_user_id()]; // Return the dynamic ID if no active users

    }

    public function ruby_active_users() {
        $ruby_helps = User::where('is_green', 1)
        ->where('status', 'Active')
        ->whereNull('deleted_at')
        ->where('package_id', 6)
        ->where('ruby_complete', 0)
        ->orderBy('activated_date')
        ->limit(10) // Apply limit early
        ->pluck('user_id') // Fetch only user_id
        ->toArray();
        return !empty($ruby_helps) ? $platinum_helps : [self::first_user_id()]; // Return the dynamic ID if no active users

    }

    public function emerald_active_users() {
        // ('COUNT(*) >= 72 AND COUNT(*) <= 112')
        $emrald_helps = User::where('is_green', 1)
        ->where('status', 'Active')
        ->whereNull('deleted_at')
        ->where('package_id', 7)
        ->where('emrald_complete', 0)
        ->orderBy('activated_date')
        ->limit(10) // Apply limit early
        ->pluck('user_id') // Fetch only user_id
        ->toArray();
        return !empty($emrald_helps) ? $emrald_helps : [self::first_user_id()]; // Return the dynamic ID if no active users

    }


    public function diamond_active_users() { 
        //('COUNT(*) >= 112 AND COUNT(*) <= 162')
        $diamond_helps = User::where('is_green', 1)
        ->where('status', 'Active')
        ->whereNull('deleted_at')
        ->where('package_id', 8)
        ->where('diamond_complete', 0)
        ->orderBy('activated_date')
        ->limit(10) // Apply limit early
        ->pluck('user_id') // Fetch only user_id
        ->toArray();
        return !empty($diamond_helps) ? $diamond_helps : [self::first_user_id()]; // Return the dynamic ID if no active users

    }

    public  function star_level_transaction($userId){  
        self::seven_level_sponser_transaction($user_id);

        $data = self::star_active_users();
        
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
            self::level_upgrade_to_silver_users($userId);
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
                    $receiver->star_complete = 1;
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
        $success = [
            'user_id' =>$user_id,
            'current_transaction' =>$receiverUserId,
            'new_transaction' =>$lastUserId
        ];
        return $success;
    }
    public function first_user_id() {
        $firstUser = User::first();
        if ($firstUser) {
            return $firstUser->user_id;
        }
        return null; // Return null if no user is found
    }

    public  function seven_level_sponser_transaction($user_id){
        $user = User::where('user_id',$user_id)->select('user_id','sponsor_id','id')->with('sponsor')->first();
        $user_id_sender = $user_id;
        /**
         *Implement the 7-level transaction logic start
        */ 
        $sponsorIds = [];

        for ($i = 0; $i < 7; $i++) {
            if ($user && $user->sponsor) {
                $sponsorIds[] = $user->sponsor->user_id;
                $user = $user->sponsor;
            } else {
                $sponsorIds[] = self::first_user_id(); // Dynamic admin ID
                $user = null; // Exit if no further sponsors are found
            }
        }
        $sevenLevelTransaction = SevenLevelTransaction::create([
            'sender_id' => $user_id,
            'first_level' => $sponsorIds[0],
            'second_level' => $sponsorIds[1] ?? null,
            'third_level' => $sponsorIds[2] ?? null,
            'fourth_level' => $sponsorIds[3] ?? null,
            'five_level' => $sponsorIds[4] ?? null,
            'six_level' => $sponsorIds[5] ?? null,
            'seven_level' => $sponsorIds[6],
            'extra_details' => implode(', ', [100, 50, 40, 20, 20, 10, 10]),
            'status' => '1'
        ]);
        return $user_id;
    }


     

    public function re_entry_payment_to_admin($sender_id,$amount){
        self::seven_level_sponser_transaction($user_id);

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

    public function sponser_help($user_id,$amount){
        $user = User::where('user_id',$user_id)->select('sponsor_id','package_id')->first();
        if (!$user) {
            // Handle case where the user is not found
            throw new \Exception("User not found: " . $user_id);
        }
        $sponsor_id = $user->sponsor_id ?? self::first_user_id(); // Use null coalescing operator
        $package_id = $user->package_id ?? '1'; // Default package_id

        $sponsor = User::where('user_id',$sponsor_id)->select('sponsor_id','package_id')->first();
         
        if (!$sponsor) {
            // Handle case where the sponsor is not found
            throw new \Exception("Sponsor not found: " . $sponsor_id);
        }
        $sponser_help = new HelpStar();
        $sponser_help->sender_id = $user_id;
        $sponser_help->receiver_id =  $sponsor_id; // Payment goes to admin
        $sponser_help->amount = $amount; // Use the help amount from the package
        $sponser_help->sender_position =$user->package_id;
        $sponser_help->receiver_position =$sponsor->package_id;
        $sponser_help->received_payments_count = 1;
        $sponser_help->commitment_date = now();
        $sponser_help->confirm_date = null;
        $sponser_help->status = 'Pending';
        $sponser_help->save(); 
        return true;  

    }

    public function re_entry_to_star($sender_id, $level_name){

        $data = self::level_name();
        $user_id = $sender_id;
        Helper::seven_level_sponser_transaction($user_id);

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


    // public function ruby_active_users() {
    //     // Retrieve active third-level users in a single query, select only 'user_id'
    //     $ruby_active_users = HelpStar::select('receiver_id')
    //         ->whereIn('receiver_id', function ($query) {
    //             $query->select('user_id')
    //                 ->from('users')
    //                 ->where('is_active', 1)
    //                 ->where('is_green', 1)
    //                 ->where('status', 'Active')
    //                 ->whereNull('deleted_at')
    //                 ->where('package_id', 6)
    //                 ->orderBy('activated_date');
    //         })
    //         ->groupBy('receiver_id')
    //         ->havingRaw('COUNT(*) >= 42 AND COUNT(*) <= 72')
    //         ->limit(10)
    //         ->pluck('receiver_id')
    //         ->toArray();
        
    //     return $ruby_active_users;
    // }

   

    
}