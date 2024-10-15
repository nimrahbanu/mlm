<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\HelpStar;
use App\Models\SevenLevelTransaction;
use Illuminate\Support\Facades\Redis;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log; 

class UpdateUserStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

   

    public function handle()
    {
        // Fetch all sponsor_ids for users who are green and inactive
        $sponsor_ids = User::where('is_green', '1')
            ->where('status', 'InActive')
            ->pluck('sponsor_id');

        // Log the sponsor IDs
        Log::info('Updating status for the following sponsor IDs:', $sponsor_ids->toArray());
        
        // Update users with those sponsor_ids to active
        User::whereIn('user_id', $sponsor_ids)
            ->update(['status' => 'active']);
        Log::info('User statuses updated successfully!');

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
        Log::info('Fetched user IDs that are inactive and not green.', $user_ids->toArray());

        // Get users who have given help and confirmed it
        $giving_users = HelpStar::whereIn('sender_id', $user_ids)
            ->whereNotNull('confirm_date')
            ->pluck('sender_id');
        Log::info('Fetched users who have given help and confirmed it.', $giving_users->toArray());

        // Iterate through levels and update users accordingly
        foreach ($levels as $level) {
            foreach ($giving_users as $user_id) {
                $seven_level_users = SevenLevelTransaction::where('sender_id', $user_id)
                    ->whereNotNull($level . '_confirm_date')
                    ->pluck('sender_id');

                // Update users to is_green = 1 if they qualify
                if ($seven_level_users->isNotEmpty()) {
                    User::where('user_id', $user_id)
                        ->update(['is_green' => 1]);
                    Log::info('User status updated to green for user ID:', [$user_id]);
                }
            }
        }

        Log::info('User statuses updated successfully for is_green.');

        $now = now();

        // Fetch HelpStar records that were committed more than 24 hours ago and are not confirmed
        $helpstars = HelpStar::where('commitment_date', '<=', $now->subHours(24))
            ->whereNull('confirm_date')
            ->get();

        // Store the last user ID temporarily
        $lastUserId = Redis::get('last_user_id');

        foreach ($helpstars as $help) {
            // Set the last user ID to the current help's receiver ID
            Redis::set('last_user_id', $help->receiver_id);

            // Reject the transaction here
            $this->rejectTransaction($help);
        }

        // Restore the last user ID from Redis
        Redis::set('last_user_id', $lastUserId);
    }

    private function rejectTransaction($help)
    {
        // Implement your rejection logic here
        $help->status = 'rejected'; // Assuming you have a status field
        $help->save();

        // Log this action
        Log::info("Rejected HelpStar transaction for receiver ID: {$help->receiver_id}");
    }
}
