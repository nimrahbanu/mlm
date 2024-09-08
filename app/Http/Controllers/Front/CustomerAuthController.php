<?php
namespace App\Http\Controllers\Front;
use App\Http\Controllers\Controller;
use App\Models\LanguageMenuText;
use App\Models\LanguageNotificationText;
use App\Models\LanguageWebsiteText;
use App\Models\User;
use App\Models\Review;
use App\Models\GeneralSetting;
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
use Illuminate\Support\Facades\Mail;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Redis;

class CustomerAuthController extends Controller
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

    public function login() {
    	return view('front.user.customer_login');
    }

    public function login_store(Request $request) {
        $g_setting = GeneralSetting::where('id', 1)->first();
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ],[
            'email.required' => ERR_EMAIL_REQUIRED,
            'email.email' => ERR_EMAIL_INVALID,
            'password.required' => ERR_PASSWORD_REQUIRED
        ]);

        if($g_setting->google_recaptcha_status == 'Show') {
            $request->validate([
                'g-recaptcha-response' => 'required'
            ], [
                'g-recaptcha-response.required' => ERR_RECAPTCHA_REQUIRED
            ]);
        }

        $credential = [
            'email'=> $request->email,
            'password'=> $request->password,
            // 'status'=> 'Active'
        ];

        if(Auth::guard('web')->attempt($credential)) {
            return redirect()->intended(route('customer_dashboard'))->with('success', SUCCESS_LOGIN);
        } else {
            return redirect()->back()->with('error', ERR_CUSTOMER_NOT_FOUND);
        }
    }

    public function logout() {
        Auth::guard('web')->logout();
        return redirect()->route('customer_login');
    }

    public function registration() {
    	return view('front.user.customer_registration');
    }
    public function registration_store(Request $request) {
        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required',
            're_password' => 'required|same:password',
            'sponsor_id' => 'nullable|exists:users,id',
            "phone" => "required|numeric",
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
            'registration_code.unique' => 'The registration code has already been used.'
        ]);
        try{
            $sponsor = User::find($request->sponsor_id);
      
        if ($sponsor && $sponsor->is_active == '0') {
            $sponsor->update([
                'is_active' => '1',
                'package_id' => '1',
                'activated_date' => now()
            ]);
        }

        if ($request->has('registration_code')) {
            $epin = EPinTransfer::where('e_pin', $request->registration_code)
                ->where('is_used', '0')
                ->first();
                $token = hash('sha256',time());

                if ($epin) {
                    $userId = $this->generateUniqueUserId();
                    $epin->is_used = '1';
                    $epin->save();
                    $data = $request->only((new User)->getFillable());
                    $data['password'] = Hash::make($request->password);
                    $data['token'] = $token;
                    $data['status'] = 'InActive';
                    $data['sponsor_id'] = $request->sponsor_id ?? '100001';
                    $data['user_id'] = $userId;

                    $user = User::create($data);
                    if ($user) {
                        return redirect()->back()->with('success', 'User registered successfully');
                    } else {
                        return redirect()->back()->with('error', 'Unable to register. Please try again.');
                    }
                } else {
                    return redirect()->back()->with('error', 'Invalid Registration code.');
                }
            } else {
                return redirect()->back()->with('error', 'Registration code not provided.');
            }
        } catch (\Exception $e) {
            dd($e);
            return redirect()->back()->with('error', 'Unable to register. Please try again.');

        }

    }

    private function generateUniqueUserId() {
        do {
            $userId = 'PHC' . mt_rand(100000, 999999); // Generate a random 6-digit number
        } while (User::where('user_id', $userId)->exists()); // Check if the user_id already exists
    
        return $userId;
    }
    // public function registration_store(Request $request) {

    //     if(env('PROJECT_MODE') == 0) {
    //         return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
    //     }

    //     $token = hash('sha256',time());
    //     $obj = new User();
    //     $data = $request->only($obj->getFillable());
    //         // Process the transaction and get details
    //         // $transactionData = $this->transactionService->processTransaction($data);
    //         // dd($transactionData); // Debug output to inspect the result
    //     $request->validate([
    //         'name' => 'required',
    //         'email' => 'required|email|unique:users',
    //         'password' => 'required',
    //         're_password' => 'required|same:password',
    //         // 'sponsor_id' => 'nullable|exists:users,id',
    //         "phone" => "required",
    //         "phone_pay_no" => "required",
    //         "confirm_phone_pay_no"=>"required|same:phone_pay_no",
    //         "registration_code" => "required"
    //     ], [
    //         'name.required' => ERR_NAME_REQUIRED,
    //         'email.required' => ERR_EMAIL_REQUIRED,
    //         'email.email' => ERR_EMAIL_INVALID,
    //         'password.required' => ERR_PASSWORD_REQUIRED,
    //         're_password.required' => ERR_RE_PASSWORD_REQUIRED,
    //         're_password.same' => ERR_PASSWORDS_MATCH
    //     ]);

    //    $sponser_Activate = User::findorfail($request->sponsor_id);
    //    if($sponser_Activate->is_active == '0'){
    //     $sponser_Activate->is_active = '1';
    //     $sponser_Activate->package_id = '1';
    //     $sponser_Activate->save();   
    //    }

    //     if($request->registration_code){
    //         $epin = EPinTransfer::where('e_pin', $request->registration_code)
    //         ->where('is_used', '0')
    //         ->first();   
    //         if(isset($epin)){
    //             $epin->is_used = '1';
    //             $epin->save();   
                
    //             $data['password'] = Hash::make($request->password);
    //             $data['token'] = $token;
    //             $data['status'] = 'Pending';
    //             $obj->fill($data)->save();
    //         }else{
    //             return redirect()->back()->with('error', 'e-pin is wrong or may be sponser id is wrong');
    //         }
    //     }

    //     // Send Email
    //     // $et_data = EmailTemplate::where('id', 6)->first();
    //     // $subject = $et_data->et_subject;
    //     // $message = $et_data->et_content;
    //     // $verification_link = url('customer/registration/verify/'.$token.'/'.$request->email);
    //     // $message = str_replace('[[verification_link]]', $verification_link, $message);
    //     // Mail::to($request->email)->send(new RegistrationEmailToCustomer($subject,$message));
    //     return redirect()->route('customer_login')->with('success', SUCCESS_REGISTRATION_VERIFY_DONE);

    // }

    public function registration_verify() {

        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }

        $email_from_url = request()->segment(count(request()->segments()));
        $aa = User::where('email', $email_from_url)->first();
        if(!$aa) {
            return redirect()->route('customer_login');
        }
        $expected_url = url('customer/registration/verify/'.$aa->token.'/'.$aa->email);
        $current_url = url()->current();
        if($expected_url != $current_url) {
            return redirect()->route('customer_login');
        }
        $data['status'] = 'Active';
        $data['token'] = '';
        User::where('email',$email_from_url)->update($data);
        return redirect()->route('customer_login')->with('success', SUCCESS_REGISTRATION_VERIFY_DONE);
    }


    public function forget_password() {
        $page_other_item = PageOtherItem::where('id',1)->first();
        return view('front.user.customer_forget_password', compact('page_other_item'));
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
}
