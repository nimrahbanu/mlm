<?php

namespace App\Console\Commands;
use App\Models\User;
use App\Models\HelpStar;
use App\Models\SevenLevelTransaction;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
class UpdateUserStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:update-status';
    protected $description = 'Update user statuses based on their activity';


    /**
     * The console command description.
     *
     * @var string
     */
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
          // Fetch all sponsor_ids for users who are green and inactive
          $sponsor_ids = User::where('is_green', '1')
          ->where('status', 'InActive')
          ->pluck('sponsor_id'); // Get only the sponsor_id values
  
            // Log the sponsor IDs
            Log::info('Updating status for the following sponsor IDs:', $sponsor_ids->toArray());
        
            // Update users with those sponsor_ids to active 
            // Updating User Statuses
            User::whereIn('user_id', $sponsor_ids)
                ->update(['status' => 'active']);
            $this->info('User statuses updated successfully!');

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
                Log::info('Fetch user IDs that are inactive and not green.',$user_ids->toArray());
    
            // Get users who have given help and confirmed it
            $giving_users = HelpStar::whereIn('sender_id', $user_ids)
                ->whereNotNull('confirm_date')
                ->pluck('sender_id'); // Fetch sender IDs
                Log::info('Get users who have given help and confirmed it.',$giving_users->toArray());
    
          
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
            Log::info('User statuses updated successfully for is_green.');
            $this->info('User statuses updated successfully for is_green.');

            $sponsor_ids = User::where('is_green', '1')
            ->where('status', 'InActive')
            ->pluck('sponsor_id'); // Get only the sponsor_id values
    
              // Log the sponsor IDs
              Log::info('Updating status for the following sponsor IDs:', $sponsor_ids->toArray());
          
              // Update users with those sponsor_ids to active 
              // Updating User Statuses
              User::whereIn('user_id', $sponsor_ids)
                  ->update(['status' => 'active']);
    }
}