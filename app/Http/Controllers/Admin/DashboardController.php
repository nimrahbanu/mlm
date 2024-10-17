<?php
namespace App\Http\Controllers\Admin;
use App\Models\User;
use App\Models\Admin;
use App\Models\EPin;
use App\Models\Property;
use App\Models\SevenLevelTransaction;
use App\Models\HelpStar;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Auth;
use App\Console\Commands\UpdateUserStatus;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan; // Make sure to import Artisan
use DB;
use Illuminate\Support\Facades\Redis;

class DashboardController extends Controller
{
    public function __construct() {
        $this->middleware('auth.admin:admin');
    }

    public function index() {
        $total_customers = User::count();
        $total_active_customers = User::where(['is_active'=>'1', 'is_green'=>'1'])->count();
        $total_pending_customers = User::where(['is_active'=>'0', 'is_green'=>'0'])->count();
        $total_pins = EPin::count();
        
        return view('admin.home', compact('total_customers','total_active_customers','total_pending_customers','total_pins'));
    }


    public function updateRedis($level)
    {
        // Set Redis values based on the level
        switch ($level) {
            case 'bronze':
                Redis::set('last_user_id', null);
                break;
            case 'star':
                Redis::set('silver_level_user_id', null);
                break;
            case 'silver':
                Redis::set('gold_level_last_user_id', null);
                break;
            case 'gold':
                Redis::set('platinum_level_last_user_id', null);
                break;
            case 'sapphire':
                Redis::set('ruby_level_last_user_id', null);
                break;
            case 'platinum':
                Redis::set('emrald_level_last_user_id', null);
                break;
            case 'diamond':
                Redis::set('diamond_level_last_user_id', null);
                break;
            case 'admin':
                Redis::set('last_user_id', 'PHC123456');
                break;

            case 'database':
                HelpStar::truncate();
                SevenLevelTransaction::truncate();
                User::truncate();
                DB::table('users')->insert([
                    'id' => 1,
                    'user_id' => 'PHC123456',
                    'name' => 'admin',
                    'email' => 'admin@gmail.com',
                    'phone' => '7793814798',
                    'sponsor_id' => 'PHC123456',
                    'phone_pay_no' => '7793814798',
                    'registration_code' => 'HCqVf07w',
                    'photo' => null,
                    'password' => '$2y$10$TWijNnT4JQ1kJ.HVPn0Fc.pdMlzSqCSU6/rmXCSI/EzWAHraIkAa2',
                    'token' => 'badf507825e61c462e1666366b2e0a0053ef8786004f53a15459f5036c853b26',
                    'is_active' => 1,
                    'is_green' => 1,
                    'package_id' => 2,
                    'deleted_at' => null,
                    '7_level_payment_status' => null,
                    'ustd_no' => null,
                    'received_payments_count' => 1,
                    'activated_date' => '2024-10-16 03:08:03',
                    'status' => 'Active',
                    'block_reason' => null,
                    'green_date' => '2024-09-17 13:25:40',
                    'created_at' => '2024-09-04 00:26:05',
                    'star_complete' => "0",  // Ensure this is an INT
                    'silver_complete' => '0', // Ensure this is also an INT
                    'gold_complete' => '0',
                    'platinum_complete' => '0',
                    'ruby_complete' => '0',
                    'emrald_complete' => '0',
                    'diamond_complete' => '0',
                    'updated_at' => '2024-10-16 03:29:02',
                    'ip_address' => null,
                    'otp' => null,
                ]);
                break; 
         
            case 'cron':
                Log::info('Triggering users:update-status command.');
                // Artisan::call('users:update-status');
                Artisan::call('schedule:run');
                break;
                return redirect()->back()->with('success', SUCCESS_DATABASE_CLEAR);
            default:
                return response()->json(['success' => false, 'message' => 'Invalid level'], 400);
        }
        return redirect()->back()->with('success', SUCCESS_DATABASE_CLEAR);
        return response()->json(['success' => true]);
    }
}
