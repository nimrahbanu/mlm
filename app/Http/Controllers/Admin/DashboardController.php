<?php
namespace App\Http\Controllers\Admin;
use App\Models\User;
use App\Models\Admin;
use App\Models\EPin;
use App\Models\Property;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Auth;

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
}
