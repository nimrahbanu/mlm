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

class UserController extends BaseController
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

    public function bank_detail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'district' => 'required',
            'state' => 'required',
            'address' => 'required',
            'pin_code' => 'required',
            'bank_name' => 'required',
            "account_number" => 'required',
            "ifsc_code"=> 'required',
            "branch" => 'required',
            "account_holder_name" => 'required',
            "upi" => 'required',
            "phone_pe" => 'required',
            "usdt_bep20" => 'required',
            // "trx_rc20" => 'required',
        ]);
        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }
        try {
            $fields = [
                'user_id', 'district', 'state', 'address', 'pin_code', 'bank_name', 
                'account_number', 'ifsc_code', 'branch', 'account_holder_name', 
                'upi', 'phone_pe', 'usdt_bep20', 'trx_rc20'
            ];
    
            // Update or create bank details for the user
            $bankDetail = Bank::updateOrCreate(
                ['user_id' => $request->user_id], 
                $request->only($fields)
            );
    
            $success = [
                'bank_detail' => $bankDetail
            ];
    
            return $this->sendResponse($success, 'Bank details saved successfully.');
    
        } catch (\Exception $e) {
            return $this->sendError('Unable to save bank details. Please try again.');
        }
    }
}