<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\News;
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
        $news = News::orderBy('news_order')->get();
        return view('admin.news_view', compact('news'));
    }

    public function create() {
        return view('admin.news_create');
    }

    public function store(Request $request) {

        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }

        $news = new news();
        $data = $request->only($news->getFillable());

        $request->validate([
            'news_title' => 'required',
            'news_content' => 'required',
            'news_order' => 'numeric|min:0|max:32767'
        ],[
            'news_title.required' => ERR_TITLE_REQUIRED,
            'news_content.required' => ERR_CONTENT_REQUIRED,
            'news_order.numeric' => ERR_ORDER_NUMERIC,
            'news_order.min' => ERR_ORDER_MIN,
            'news_order.max' => ERR_ORDER_MAX,
        ]);

        $news->fill($data)->save();
        return redirect()->route('admin_news_view')->with('success', SUCCESS_ACTION);
    }

    public function edit($id) {
        $news = News::findOrFail($id);
        return view('admin.news_edit', compact('news'));
    }

    public function update(Request $request, $id) {

        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }

        $news = News::findOrFail($id);
        $data = $request->only($news->getFillable());

        $request->validate([
            'news_title' => 'required',
            'news_content' => 'required',
            'news_order' => 'numeric|min:0|max:32767'
        ],[
            'news_title.required' => ERR_TITLE_REQUIRED,
            'news_content.required' => ERR_CONTENT_REQUIRED,
            'news_order.numeric' => ERR_ORDER_NUMERIC,
            'news_order.min' => ERR_ORDER_MIN,
            'news_order.max' => ERR_ORDER_MAX,
        ]);

        $news->fill($data)->save();
        return redirect()->route('admin_news_view')->with('success', SUCCESS_ACTION);
    }

    public function destroy($id) {

        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }

        $news = News::findOrFail($id);
        $news->delete();
        return Redirect()->back()->with('success', SUCCESS_ACTION);
    }

   
    public function news_change_status($id) {
        $customer = News::find($id);
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

        $news = Department::findOrFail($id);
        $news->delete();
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
        $news = News::orderBy('news_order')->get();
        if ($news) {
            $success = $data;
            return $this->sendResponse($success, 'Retrieve successfully.');
        } else {
            return $this->sendError('Oops! Unable to retrieve. Please try again.');
        }
    }
}
