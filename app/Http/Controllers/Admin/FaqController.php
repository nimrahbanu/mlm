<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Faq;
use App\Models\Support;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use DB;
use Auth;

class FaqController extends Controller
{
    public function __construct() {
        $this->middleware('auth.admin:admin');
    }

    public function index() {
        $faq = Faq::orderBy('faq_order')->get();
        return view('admin.faq_view', compact('faq'));
    }

    public function create() {
        return view('admin.faq_create');
    }

    public function store(Request $request) {

        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }

        $faq = new Faq();
        $data = $request->only($faq->getFillable());

        $request->validate([
            'faq_title' => 'required',
            'faq_content' => 'required',
            'faq_order' => 'numeric|min:0|max:32767'
        ],[
            'faq_title.required' => ERR_TITLE_REQUIRED,
            'faq_content.required' => ERR_CONTENT_REQUIRED,
            'faq_order.numeric' => ERR_ORDER_NUMERIC,
            'faq_order.min' => ERR_ORDER_MIN,
            'faq_order.max' => ERR_ORDER_MAX,
        ]);

        $faq->fill($data)->save();
        return redirect()->route('admin_news_view')->with('success', SUCCESS_ACTION);
    }

    public function edit($id) {
        $faq = Faq::findOrFail($id);
        return view('admin.faq_edit', compact('faq'));
    }

    public function update(Request $request, $id) {

        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }

        $faq = Faq::findOrFail($id);
        $data = $request->only($faq->getFillable());

        $request->validate([
            'faq_title' => 'required',
            'faq_content' => 'required',
            'faq_order' => 'numeric|min:0|max:32767'
        ],[
            'faq_title.required' => ERR_TITLE_REQUIRED,
            'faq_content.required' => ERR_CONTENT_REQUIRED,
            'faq_order.numeric' => ERR_ORDER_NUMERIC,
            'faq_order.min' => ERR_ORDER_MIN,
            'faq_order.max' => ERR_ORDER_MAX,
        ]);

        $faq->fill($data)->save();
        return redirect()->route('admin_news_view')->with('success', SUCCESS_ACTION);
    }

    public function destroy($id) {

        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }

        $faq = Faq::findOrFail($id);
        $faq->delete();
        return Redirect()->back()->with('success', SUCCESS_ACTION);
    }

   
    public function news_change_status($id) {
        $customer = Faq::find($id);
        if($customer->status == 'Active') {
            if(env('PROJECT_MODE') == 0) {
                $message=env('PROJECT_NOTIFICATION');
            } else {
                $customer->status = 'Pending';
                $message=SUCCESS_ACTION;
                $customer->save();
            }
        } else {
            if(env('PROJECT_MODE') == 0) {
                $message=env('PROJECT_NOTIFICATION');
            } else {
                $customer->status = 'Active';
                $message=SUCCESS_ACTION;
                $customer->save();
            }
        }
        return response()->json($message);
    }

    public function department(){
        $departments = Department::orderBy('id','DESC')->get();
        return view('admin.department',compact('departments'));
    }

    public function department_create(){
        return view('admin.department_create');
    }
    

    public function department_status($id){
        $customer = Department::find($id);
        if($customer->status == 'Active') {
            if(env('PROJECT_MODE') == 0) {
                $message=env('PROJECT_NOTIFICATION');
            } else {
                $customer->status = 'Pending';
                $message=SUCCESS_ACTION;
                $customer->save();
            }
        } else {
            if(env('PROJECT_MODE') == 0) {
                $message=env('PROJECT_NOTIFICATION');
            } else {
                $customer->status = 'Active';
                $message=SUCCESS_ACTION;
                $customer->save();
            }
        }
        return response()->json($message);
    }

    public function department_store(Request $request) {

        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }

        $department = new Department();
        $data = $request->only($department->getFillable());
        $request->validate([
            'name' => 'required|unique:departments',
        ]);
        $department->fill($data)->save();
        return redirect()->route('department')->with('success', SUCCESS_ACTION);
    }
    public function department_destroy($id){
        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }

        $faq = Department::findOrFail($id);
        $faq->delete();
        return Redirect()->back()->with('success', SUCCESS_ACTION);
    } 
    public function support(){
        $supports = Support::orderBy('created_at')->with('userData','departmentData')->get();
        return view('admin.support', compact('supports'));
    } 

    public function support_status($id){
        $customer = Support::find($id);
        if($customer->status == 'Open') {
            if(env('PROJECT_MODE') == 0) {
                $message=env('PROJECT_NOTIFICATION');
            } else {
                $customer->status = 'Close';
                $message=SUCCESS_ACTION;
                $customer->save();
            }
        } else {
            if(env('PROJECT_MODE') == 0) {
                $message=env('PROJECT_NOTIFICATION');
            } else {
                $customer->status = 'Open';
                $message=SUCCESS_ACTION;
                $customer->save();
            }
        }
        return response()->json($message);
    }
 
    public function support_edit($id) {
        $support = Support::with('userData', 'departmentData')->findOrFail($id);
        return view('admin.support_edit', compact('support'));
    }
    
    public function gift(){
        return view('admin.gift');
    } 

        public function admin_support_update(Request $request, $id) {

            if(env('PROJECT_MODE') == 0) {
                return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
            }
    
            $support = Support::findOrFail($id);
         
            $data = $request->only($support->getFillable());
            $support->fill($data)->save();
            
            return redirect()->route('support')->with('success', SUCCESS_ACTION);
        }
    public function api_index(){
        $faq = Faq::orderBy('faq_order')->get();
        if ($faq) {
            $success = $data;
            return $this->sendResponse($success, 'Retrieve successfully.');
        } else {
            return $this->sendError('Oops! Unable to retrieve. Please try again.');
        }
    }
}
