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
use App\Models\Faq;
use App\Models\Support;
use App\Models\EmailTemplate;
use App\Models\PageOtherItem;
use App\Models\EPinTransfer;
use App\Mail\RegistrationEmailToCustomer;
use App\Mail\ResetPasswordMessageToCustomer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use DB;
use Hash;
use Auth;
use Validator;
use Illuminate\Support\Facades\Mail;
use App\Services\TransactionService;

class CustomerAuthController extends BaseController
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

    public function basepath(){
        $basePath = rtrim(public_path(), DIRECTORY_SEPARATOR);
        // $basePath = 'http://192.168.29.89/astroweds/api/public';
        //  $user['path'] =  str_replace('\\', '/', $basePath . DIRECTORY_SEPARATOR . 'cms-images' . DIRECTORY_SEPARATOR . 'user-images' . DIRECTORY_SEPARATOR);
        $finallPath =  str_replace('\\', '/', $basePath);
        return $finallPath;
    }

    public function login() {
    	return view('front.user.customer_login');
    }

    public function login_store(Request $request) {
        $g_setting = GeneralSetting::where('id', 1)->first();
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ],[
            'email.required' => ERR_EMAIL_REQUIRED,
            'email.email' => ERR_EMAIL_INVALID,
            'password.required' => ERR_PASSWORD_REQUIRED
        ]);
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());
        }
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $user = Auth::user();
            $success['token'] = $user->createToken('MyApp')->accessToken;
            $success['id'] = $user->id;
            $success['name'] = $user->name;
            return $this->sendResponse($success, 'User login successfully.');
        } else {
            return $this->sendError('Customer not found');

        }
    }
   
    
    public function logout() {
        Auth::guard('web')->logout();
        return $this->sendResponse(true, 'Logout successfully.');

    }

    public function registration_store(Request $request) {


        $token = hash('sha256',time());
       
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required',
            're_password' => 'required|same:password',
            'sponsor_id' => 'required|exists:users,user_id',
            'phone' => 'required|numeric|unique:users,phone', // Example for a 10-digit phone number
            "phone_pay_no" => "required|numeric",
            "confirm_phone_pay_no"=>"required|same:phone_pay_no",
            "registration_code" => "required|unique:users,registration_code"
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
            return $this->sendError('Validation Error.', $validator->errors());
        }
        // DB::beginTransaction();
        try{

            $sponsor = User::where('user_id',$request->sponsor_id)->first();
            if ($sponsor && $sponsor->is_active == '0') {
                $sponsor->update([
                    'is_active' => '1',
                    'package_id' => '1',
                    'activated_date' => now()
                ]);
                if($sponsor->is_green == '1'){
                    $sponsor->update([
                        'status' => 'Active',
                    ]);
                }
            }
            if ($request->has('registration_code')) {
                $epin = EPinTransfer::where('e_pin', $request->registration_code)
                    ->where('is_used', '0')
                    ->first();

                    if ($epin) {
                        $userId = $this->generateUniqueUserId();
                        $epin->is_used = '1';
                        $epin->save();
                        $data = $request->only((new User)->getFillable());
                        $data['password'] = Hash::make($request->password);
                        $data['token'] = $token;
                        $data['status'] = 'InActive';
                        $data['sponsor_id'] = $request->sponsor_id ?? '1';
                        $data['user_id'] = $userId;
                        $data['package_id'] = 1;

                        $user = User::create($data);
                        if ($user) {
                            return $this->sendResponse($user, 'User registered successfully');
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
            // DB::rollBack();
            dd($e);

            return $this->sendError('Unable to register. Please try again.');
        }
    }

    private function generateUniqueUserId() {
        do {
            $userId = 'PHC' . mt_rand(100000, 999999); // Generate a random 6-digit number
        } while (User::where('user_id', $userId)->exists()); // Check if the user_id already exists
    
        return $userId;
    }

    public function registration_verify() {

    }


    public function forget_password() {
        return view('front.user.customer_forget_password');
    }

    public function forget_password_store(Request $request) {

        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }

        $g_setting = GeneralSetting::where('id', 1)->first();
        $request->validate([
            'email' => 'required|email'
        ],[
            'email.required' => ERR_EMAIL_REQUIRED,
            'email.email' => ERR_EMAIL_INVALID
        ]);

        if($g_setting->google_recaptcha_status == 'Show') {
            $request->validate([
                'g-recaptcha-response' => 'required'
            ], [
                'g-recaptcha-response.required' => ERR_RECAPTCHA_REQUIRED
            ]);
        }

        $check_email = User::where('email',$request->email)->where('status','Active')->first();
        if(!$check_email) {
            return redirect()->back()->with('error', ERR_EMAIL_NOT_FOUND);
        } else {
            $et_data = EmailTemplate::where('id', 7)->first();
            $subject = $et_data->et_subject;
            $message = $et_data->et_content;
            $token = hash('sha256',time());
            $reset_link = url('customer/reset-password/'.$token.'/'.$request->email);
            $message = str_replace('[[reset_link]]', $reset_link, $message);

            $data['token'] = $token;
            User::where('email',$request->email)->update($data);
            Mail::to($request->email)->send(new ResetPasswordMessageToCustomer($subject,$message));
        }
        return redirect()->back()->with('success', SUCCESS_FORGET_PASSWORD_EMAIL_SEND);
    }


    public function reset_password() {

        $page_other_item = PageOtherItem::where('id',1)->first();

        $g_setting = GeneralSetting::where('id', 1)->first();
        $email_from_url = request()->segment(count(request()->segments()));

        $aa = User::where('email', $email_from_url)->first();

        if(!$aa) {
            return redirect()->route('customer_login');
        }

        $expected_url = url('customer/reset-password/'.$aa->token.'/'.$aa->email);
        $current_url = url()->current();
        if($expected_url != $current_url) {
            return redirect()->route('customer_login');
        }
        $email = $aa->email;
        return view('front.user.customer_reset_password', compact('g_setting', 'email', 'page_other_item'));
    }

    public function reset_password_update(Request $request) {

        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }

        $g_setting = GeneralSetting::where('id', 1)->first();
        $request->validate([
            'new_password' => 'required',
            'retype_password' => 'required|same:new_password',
        ], [
            'new_password.required' => ERR_NEW_PASSWORD_REQUIRED,
            'retype_password.required' => ERR_RE_PASSWORD_REQUIRED,
            'retype_password.same' => ERR_PASSWORDS_MATCH
        ]);

        if($g_setting->google_recaptcha_status == 'Show') {
            $request->validate([
                'g-recaptcha-response' => 'required'
            ], [
                'g-recaptcha-response.required' => ERR_RECAPTCHA_REQUIRED
            ]);
        }

        $data['password'] = Hash::make($request->new_password);
        $data['token'] = '';
        User::where('email', $request->current_email)->update($data);
        return redirect()->route('customer_login')->with('success', SUCCESS_RESET_PASSWORD);
    }

    public function dashboard(Request $request){
        $validator = Validator::make($request->all(),[
            'user_id' => 'required|exists:users,user_id',
        ]);
        if($validator->fails()){
            return $this->sendError('Validation Error.',$validator->errors());
        }
       
        $user_id = $request->user_id;
        $userData = User::where('user_id',$user_id);
        $user = $userData->with('package:id,package_name')
                ->first(); 
        $active = $userData->where('is_active',0)->whereNotNull('status')->whereNotNull('activated_date')->first();
        if (!$user) {
            return $this->sendError('User not found.');
        }
        $success = [
            'user' => $user->only(['id','user_id', 'name', 'activated_date', 'created_at', 'package_id']),
            'package_name' => $user->package ? $user->package->package_name : null,
            'direct_team' => User::where('sponsor_id', $user_id)->count(),
            'total_team' => $user->getTotalDescendantCount(),
            'referral_link' => url('api/customer/registration/' . $user_id),
            'giving_help' => $active ?? $this->giving_help(),
            'taking_help' => 0,
            'taking_sponcer' => 0,
            'e_pin' => EPinTransfer::where('member_id', $user_id)->where('is_used', '0')->count(),
            'news' => Faq::where('status', 'Active')->orderBy('faq_order')->get()
        ];
        if($user){
            return $this->sendResponse($success, 'User Data Retrieve Successfully.');
        }
    }

    private function giving_help() {
  
     $admin =  User::with('bankDetails')->first(); // Check if the user_id already exists
    
        return $admin;
    }

    public function my_profile(Request $request){
        $validator = Validator::make($request->all(),[
            'user_id' => 'required|exists:users,id',
        ]);
        if($validator->fails()){
            return $this->sendError('Validation Error.',$validator->errors());
        }
       
        $user_id = $request->user_id;
        $user = User::find($user_id); // Use with() to eager load the package relationship
        $bank_details = Bank::where('user_id',$user_id)->first(); // Use with() to eager load the package relationship
        if (!$user) {
            return $this->sendError('User not found.');
        }
        $success = [
            'user' => $user,
            'bank_details' => $bank_details
        ];
        if($user){
            return $this->sendResponse($success, 'User Data Retrieve Successfully.');
        }else{
            return $this->sendError('Data not found.');

        }
    }

    public function profile_update(Request $request){
        $validator = Validator::make($request->all(),[
            'user_id' => 'required|exists:users,id',
        ]);
        if($validator->fails()){
            return $this->sendError('Validation Error.',$validator->errors());
        }
       
        $user_id = $request->user_id;
        $fields = [
            'user_id', 'district', 'state', 'address', 'pin_code', 'bank_name', 'account_number', 'ifsc_code', 'branch', 'account_holder_name', 'upi', 'paytm', 'phone_pe', 'google_pay', 'trx_rc20', 'usdt_bep20'
        ];
      $success['bank'] =   Bank::updateOrCreate(['user_id' => $user_id], $request->only($fields));

        if($success){
            return $this->sendResponse($success, 'User Data Retrieve Successfully.');
        }else{
            return $this->sendError('Data not found.');

        }
    } 

    public function update_password(Request $request){
            $validator = Validator::make($request->all(), [
                'user_id'    => 'required',
                'old_password' => 'required',
                'password'     => 'required',
                'password_confirmation' => 'required',
            ]);
            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }
            try{
    
                $old_password= $request->old_password;
                $password= $request->password;
                $password_confirmation= $request->password_confirmation;
                $obj = User::where('id', $request->user_id)->first();
                if (!Hash::check($request->old_password, $obj->password)) {
                    return $this->sendSuccessError('Oops! The old password does not match our records.',$old_password);
                }
                if ($password_confirmation === $password) {
                    $obj->password = Hash::make($request->password);
            
                    if ($obj->save()) {
                        return $this->sendResponse($obj, 'Password updated successfully.');
                    } else {
                        return $this->sendError('Oops! Unable to  update password. Please try again.');
                    }
                }else{
                    return $this->sendSuccessError('The password confirmation does not match','The password confirmation does not match');
    
                }
            }catch (\Exception $e) {
                return $this->sendError('Oops! Something went wrong. Please try again.');
            }
    } 
    public function active_users(){
        $data = User::where(['is_active'=>1,'is_green'=>1,'status'=>'Active','deleted_at'=>null])->orderBy('activated_date')->pluck('id')->toArray();
        
    }
}