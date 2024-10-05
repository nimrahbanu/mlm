<?php
use App\Models\User;
use App\Models\SevenLevelTransaction;
use App\Models\Package;
use App\Models\HelpStar;
class Helper

{
    public static function generateUniqueUserId() {
        do {
            $userId = 'PHC' . mt_rand(100000, 999999); // Generate a random 6-digit number
        } while (User::where('user_id', $userId)->exists()); // Check if the user_id already exists

        return $userId;
    }

    public static function star_active_users() {
        $active_users = User::where('is_active', 1)
        ->where('is_green', 1)
        ->where('status', 'Active')
        ->whereNull('deleted_at')
        ->where('package_id', 2)
        ->where('star_complete', '0')
        ->orderBy('activated_date')
        ->limit(10) // Apply limit early
        ->pluck('user_id') // Fetch only user_id
        ->toArray();
        if (!is_array($active_users) || empty($active_users)) {
            $active_users = ['PHC123456'];
        }
        return $active_users;
    }
    public function silver_active_users() {
        $users_with_exactly_three_helps = User::where('is_active', 1)
        ->where('is_green', 1)
        ->where('status', 'Active')
        ->whereNull('deleted_at')
        ->where('package_id', 3)
        ->where('silver_complete', '0')
        ->orderBy('activated_date')
        ->limit(10) // Apply limit early
        ->pluck('user_id') // Fetch only user_id
        ->toArray();
        if (!is_array($users_with_exactly_three_helps) || empty($users_with_exactly_three_helps)) {
            $users_with_exactly_three_helps = ['PHC123456'];
        }
        return $users_with_exactly_three_helps;
    }

    public function gold_active_users() {
        $gold_helps = User::where('is_active', 1)
        ->where('is_green', 1)
        ->where('status', 'Active')
        ->whereNull('deleted_at')
        ->where('package_id', 4)
        ->where('gold_complete', 0)
        ->orderBy('activated_date')
        ->limit(10) // Apply limit early
        ->pluck('user_id') // Fetch only user_id
        ->toArray();
        if (!is_array($gold_helps) || empty($gold_helps)) {
            $gold_helps = ['PHC123456'];
        }
        return $gold_helps;
    }
   
    public function platinum_active_users() {
        $platinum_helps = User::where('is_active', 1)
        ->where('is_green', 1)
        ->where('status', 'Active')
        ->whereNull('deleted_at')
        ->where('package_id', 5)
        ->where('platinum_complete', 0)
        ->orderBy('activated_date')
        ->limit(10) // Apply limit early
        ->pluck('user_id') // Fetch only user_id
        ->toArray();
        if (!is_array($platinum_helps) || empty($platinum_helps)) {
            $platinum_helps = ['PHC123456'];
        }
        return $platinum_helps;
    }

    public function ruby_active_users() {
        $ruby_helps = User::where('is_active', 1)
        ->where('is_green', 1)
        ->where('status', 'Active')
        ->whereNull('deleted_at')
        ->where('package_id', 6)
        ->where('ruby_complete', 0)
        ->orderBy('activated_date')
        ->limit(10) // Apply limit early
        ->pluck('user_id') // Fetch only user_id
        ->toArray();
        if (!is_array($ruby_helps) || empty($ruby_helps)) {
            $ruby_helps = ['PHC123456'];
        }
        return $ruby_helps;
    }

    public function emerald_active_users() {
        // ('COUNT(*) >= 72 AND COUNT(*) <= 112')
        $emrald_helps = User::where('is_active', 1)
        ->where('is_green', 1)
        ->where('status', 'Active')
        ->whereNull('deleted_at')
        ->where('package_id', 7)
        ->where('emrald_complete', 0)
        ->orderBy('activated_date')
        ->limit(10) // Apply limit early
        ->pluck('user_id') // Fetch only user_id
        ->toArray();
        if (!is_array($emrald_helps) || empty($emrald_helps)) {
            $emrald_helps = ['PHC123456'];
        }
        return $emrald_helps;
    }


    public function diamond_active_users() { 
        //('COUNT(*) >= 112 AND COUNT(*) <= 162')
        $diamond_helps = User::where('is_active', 1)
        ->where('is_green', 1)
        ->where('status', 'Active')
        ->whereNull('deleted_at')
        ->where('package_id', 8)
        ->where('diamond_complete', 0)
        ->orderBy('activated_date')
        ->limit(10) // Apply limit early
        ->pluck('user_id') // Fetch only user_id
        ->toArray();
        if (!is_array($diamond_helps) || empty($diamond_helps)) {
            $diamond_helps = ['PHC123456'];
        }
        return $diamond_helps;
    }

    public static function star_level_transaction($userId){  
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

    public static function seven_level_sponser_transaction($user_id){
        $user = User::where('user_id',$user_id)->select('user_id','sponsor_id','id')->with('sponsor')->first();
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
        $user = User::where('user_id',$user_id)->where('is_active','Active')->select('sponsor_id','package_id')->first();
       print_r($user);
        $sponsor_id = isset($user->sponsor_id) ? $user->sponsor_id : 'PHC123456';
        $package_id = isset($user->package_id) ? $user->package_id : '1';
        $sponsor_package_id = User::where('user_id',$sponsor_id)->where('is_active','Active')->select('sponsor_id','package_id')->first();
        if(!empty($sponsor_package_id && isset($sponsor_package_id))){
           $sponser_id =  $sponsor_package_id->package_id;
        }else{
           $sponser_id =  'PHC123456';
        }
        $sponser_help = new HelpStar();
        $sponser_help->sender_id = $user_id;
        $sponser_help->receiver_id =  $sponsor_id; // Payment goes to admin
        $sponser_help->amount = $amount; // Use the help amount from the package
        $sponser_help->sender_position =$package_id;
        $sponser_help->receiver_position =$sponser_id;
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