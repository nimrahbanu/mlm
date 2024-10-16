<?php
namespace App\Http\Controllers\Admin;
use App\Models\User;
use App\Models\Admin;
use App\Models\EPin;
use App\Models\Property;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Auth;
use App\Console\Commands\UpdateUserStatus;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan; // Make sure to import Artisan

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
            case 'cron':
                Log::info('Triggering users:update-status command.');
                // Artisan::call('users:update-status');
                Artisan::call('schedule:run');
                break;
            default:
                return response()->json(['success' => false, 'message' => 'Invalid level'], 400);
        }

        return response()->json(['success' => true]);
    }
}
