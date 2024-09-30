<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\PropertyCategory;
use App\Models\EPin;
use App\Models\EPinTransfer;
use App\Models\User;
use App\Models\GeneralSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Mail;
use DB;
use Auth;


class EpinController extends Controller
{
    public function __construct() {
        $this->middleware('auth.admin:admin');
    }

 
    public function index() {
        $property_category = EPin::orderBy('id', 'asc')->paginate(10);
        $g_setting = GeneralSetting::find(1);
        return view('admin.e_pin.e_pin_view', compact('property_category','g_setting'));
    }
    public function e_pin_used_view() {
        $e_pin_used = EPinTransfer::orderBy('id', 'asc')->where('is_used',1)->with('MemberData','providedByData','EpinUsed')->paginate(10);
        $g_setting = GeneralSetting::find(1);
        return view('admin.e_pin.e_pin_used_view', compact('e_pin_used','g_setting'));
    }

    public function e_pin_transfer() {
        $property_category = EPinTransfer::orderBy('id', 'asc')->where('is_used','0')->with('MemberData','providedByData','EpinUsed')->paginate(20);
        $g_setting = GeneralSetting::find(1);
        return view('admin.e_pin.e_pin_transfer', compact('property_category','g_setting'));
    }
    public function e_pin_transfer_create() {
        $property_category = EPinTransfer::orderBy('id', 'asc')->paginate(10);
        $g_setting = GeneralSetting::find(1);
        // $users = User::select('id','name')->where('is_green',1)->where('is_active',1)->get();
        $users = User::select('id','name','user_id')->get();
        return view('admin.e_pin.e_pin_transfer_create', compact('property_category','users','g_setting'));
    }
    

    public function create() {
        $g_setting = GeneralSetting::find(1);
        return view('admin.e_pin.e_pin_create', compact('g_setting'));
    }

    public function store(Request $request) {

        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }
        $request->validate([
            'member_id' => 'required',
            'member_name' => 'required',
            'balance' => 'required',
            'quantity' => 'required',
            'status' => 'required',
            'flag' => 'required',

        ],[
            'member_id.required' => ERR_NAME_REQUIRED,
           
        ]);
        $quantity = $request->quantity;

        for ($i = 0; $i < $quantity; $i++) {
            $obj = new EPin;
            $obj->member_id = $request->member_id;
            $obj->member_name = $request->member_name;
            $obj->balance = $request->balance;
            $obj->quantity = $request->quantity; // Each record will have a quantity of 1
            $obj->status = $request->status;
            $obj->flag = $request->flag;
            $obj->e_pin = 'HC'.Str::random(6);
            $obj->save();
        }
        return redirect()->route('admin_e_pin_master')->with('success', SUCCESS_ACTION);
    }
    public function e_pin_transfer_store(Request $request) {
        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }
        $request->validate([
            'member_id' => 'required',
            // 'member_name' => 'required',
            'balance' => 'required',
            'quantity' => 'required',
            // 'status' => 'required',
            // 'flag' => 'required',

        
        // ],[
        //     'member_id.required' => ERR_NAME_REQUIRED,
           
        ]);
        $users = User::where('user_id',$request->member_id)->first();
        $member_name=  $users->name;
            $quantity = $request->quantity;
             
            if($request->provided_by=='PHC123456'){
                
                $epins = EPin::orderBy('id', 'desc')->take($quantity)->get();
                foreach ($epins as $epin) {
                    $transfer = new EPinTransfer;
                    $transfer->member_id = $request->member_id; // Assuming transfer to the same member
                    $transfer->provided_by = $request->provided_by; // Assuming transfer to the same member
                    $transfer->member_name = $member_name;
                    $transfer->balance = $epin->balance;
                    $transfer->quantity = $quantity; // Each record will have a quantity of 1
                    $transfer->status = $epin->status;
                    $transfer->flag = $epin->flag;
                    $transfer->e_pin = $epin->e_pin;
                    $transfer->save();
                    $epin->delete();
                }
                return redirect()->route('e_pin_transfer')->with('success', SUCCESS_ACTION);
            }
            $epin_count = EPinTransfer::where('member_id', $request->provided_by)
                        ->where('is_used', '0')
                        ->count();
            // dd($epin_count);
            if ($epin_count >= $quantity) {
                // Begin a transaction
                DB::beginTransaction();
            
                try {
                    // Step 2: Fetch the exact number of EPin records to be transferred
                    $epins = EPinTransfer::orderBy('id', 'asc')->take($quantity)->get();
                    // Step 3: Fetch the exact number of EPinTransfer records to be updated
                    $epin_transfers = EPinTransfer::where('member_id', $request->provided_by)
                                        ->where('is_used', '0')
                                        ->orderBy('id', 'asc') // Ensure the order is consistent
                                        ->take($quantity)
                                        ->get();
            
                    // Step 4: Update the EPinTransfer records
                    foreach ($epin_transfers as $index => $transfer) {
                        if (isset($epins[$index])) {
                            $epin = $epins[$index]; // Get the corresponding EPin record
            
                            // Update the EPinTransfer record with data from the EPin
                            $transfer->member_id = $request->member_id;
                            $transfer->provided_by = $request->provided_by;
                            $transfer->member_name = $member_name;
                            $transfer->balance = $epin->balance;
                            $transfer->quantity = 1;
                            $transfer->status = $epin->status;
                            $transfer->flag = $epin->flag;
                            $transfer->e_pin = $epin->e_pin;
                            $transfer->save(); // Save the updated EPinTransfer
            
                        }
                    }
            
                    // Commit the transaction if everything went well
                    DB::commit();
                } catch (\Exception $e) {
                    // Rollback the transaction if something went wrong
                    DB::rollBack();
            
                    // Optionally, return an error message or handle the exception
                    return redirect()->back()->with('error', 'An error occurred while transferring the E-pins: ' . $e->getMessage());
                }
            } else {
                return redirect()->back()->with('error', 'Your E-pin quantity='.$epin_count.' is less than the required amount.');
            }
        return redirect()->route('e_pin_transfer')->with('success', SUCCESS_ACTION);
    }
    public function admin_e_pin_store(Request $request) {
        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }
        $request->validate([
            // 'member_id' => 'required',
            // 'member_name' => 'required',
            'balance' => 'required',
            'quantity' => 'required',
            'status' => 'required',
            'flag' => 'required',

        ],[
            'member_id.required' => ERR_NAME_REQUIRED,
        
        ]);
        $quantity = $request->quantity;

        for ($i = 0; $i < $quantity; $i++) {
            $obj = new EPin;
            $obj->member_id ='PHC123456';
            $obj->member_name = 'admin';
            $obj->balance = $request->balance;
            $obj->quantity = $request->quantity; // Each record will have a quantity of 1
            $obj->status = $request->status;
            $obj->flag = $request->flag;
            $obj->e_pin = $this->generateRandomCode();
            $obj->save();
        }
        return redirect()->route('admin_e_pin_master')->with('success', SUCCESS_ACTION);

    }
    private function generateRandomCode($length = 13) {
        return strtoupper(bin2hex(random_bytes($length / 2)));
    }
    
    

   
    public function destroy($id) {

        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }

        $faq = EPin::findOrFail($id);
        $faq->delete();
        return Redirect()->back()->with('success', SUCCESS_ACTION);
    }

    
    public function change_status($id) {
        $property = EPin::find($id);
        if($property->status == '1') {
            if(env('PROJECT_MODE') == 0) {
                $message=env('PROJECT_NOTIFICATION');
            } else {
                $property->status = '0';
                $message=SUCCESS_ACTION;
                $property->save();
            }
        } else {
            if(env('PROJECT_MODE') == 0) {
                $message=env('PROJECT_NOTIFICATION');
            } else {
                $property->status = '1';
                $message=SUCCESS_ACTION;
                $property->save();
            }
        }
        return response()->json($message);
    }
    public function change_flag($id) {
        $property = EPin::find($id);
        if($property->flag == '1') {
            if(env('PROJECT_MODE') == 0) {
                $message=env('PROJECT_NOTIFICATION');
            } else {
                $property->flag = '0';
                $message=SUCCESS_ACTION;
                $property->save();
            }
        } else {
            if(env('PROJECT_MODE') == 0) {
                $message=env('PROJECT_NOTIFICATION');
            } else {
                $property->flag = '1';
                $message=SUCCESS_ACTION;
                $property->save();
            }
        }
        return response()->json($message);
    }
    // public function store(Request $request) {

    //     if(env('PROJECT_MODE') == 0) {
    //         return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
    //     }
    //     $request->validate([
    //         'member_id' => 'required',
    //         'member_name' => 'required',
    //         'balance' => 'required',
    //         'quantity' => 'required',
    //         'status' => 'required',
    //         'flag' => 'required',

    //     ],[
    //         'member_id.required' => ERR_NAME_REQUIRED,
           
    //     ]);
    //     $quantity = $request->quantity;

    //     for ($i = 0; $i < $quantity; $i++) {
    //         $obj = new EPin;
    //         $obj->member_id = $request->member_id;
    //         $obj->member_name = $request->member_name;
    //         $obj->balance = $request->balance;
    //         $obj->quantity = $request->quantity; // Each record will have a quantity of 1
    //         $obj->status = $request->status;
    //         $obj->flag = $request->flag;
    //         $obj->e_pin = 'HC'.Str::random(6);
    //         $obj->save();
    //     }
    //     return redirect()->route('admin_e_pin_master')->with('success', SUCCESS_ACTION);
    // }
    // public function e_pin_transfer_store(Request $request) {
    //     if(env('PROJECT_MODE') == 0) {
    //         return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
    //     }
    //     $request->validate([
    //         'member_id' => 'required',
    //         'member_name' => 'required',
    //         'balance' => 'required',
    //         'quantity' => 'required',
    //         // 'status' => 'required',
    //         // 'flag' => 'required',

    //     ],[
    //         'member_id.required' => ERR_NAME_REQUIRED,
           
    //     ]);
    //     $quantity = $request->quantity;
    //     $user_data = Auth::user();

    //     $epins = EPin::orderBy('id', 'desc')->take($quantity)->get();

    //     // Step 3: Create EPinTransfer records from the fetched EPin records
    //     foreach ($epins as $epin) {
    //         $transfer = new EPinTransfer;
    //         $transfer->member_id = $request->member_id; // Assuming transfer to the same member
    //         $transfer->provided_by = $user_data->id; // Assuming transfer to the same member
    //         $transfer->member_name = $request->member_name;
    //         $transfer->balance = $epin->balance;
    //         $transfer->quantity = $quantity; // Each record will have a quantity of 1
    //         $transfer->status = $epin->status;
    //         $transfer->flag = $epin->flag;
    //         $transfer->e_pin = $epin->e_pin;
    //         $transfer->save();
    //         $epin->delete();
    //     }
    //     return redirect()->route('e_pin_transfer')->with('success', SUCCESS_ACTION);
    // }
}
