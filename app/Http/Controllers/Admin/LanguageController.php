<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\LanguageMenuText;
use App\Models\LanguageWebsiteText;
use App\Models\LanguageNotificationText;
use App\Models\LanguageAdminPanelText;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use DB;
use Auth;

class LanguageController extends Controller
{
    public function __construct() {
        $this->middleware('auth.admin:admin');
    }

    public function language_menu_text() {
        $language_data = LanguageMenuText::orderBy('id', 'asc')->get();
        return view('admin.language_menu_text_view', compact('language_data'));
    }

    public function language_menu_text_update(Request $request) {

        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }

        $i=0;
        foreach(request('lang_id') as $value) {
            $arr1[$i] = $value;
            $i++;
        }
        $i=0;
        foreach(request('lang_key') as $value){
            $arr2[$i] = $value;
            $i++;
        }
        $i=0;
        foreach(request('lang_value') as $value){
            $arr3[$i] = $value;
            $i++;
        }
        for($i=0;$i<count($arr1);$i++){
            $data = array();
            $data['lang_value'] = $arr3[$i];
            LanguageMenuText::where('id', $arr1[$i])->update($data);
        }
        return redirect()->back()->with('success', SUCCESS_ACTION);
    }


    public function language_website_text() {
        $language_data = LanguageWebsiteText::orderBy('id', 'asc')->get();
        return view('admin.language_website_text_view', compact('language_data'));
    }

    public function language_website_text_update(Request $request) {

        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }

        $i=0;
        foreach(request('lang_id') as $value) {
            $arr1[$i] = $value;
            $i++;
        }
        $i=0;
        foreach(request('lang_key') as $value){
            $arr2[$i] = $value;
            $i++;
        }
        $i=0;
        foreach(request('lang_value') as $value){
            $arr3[$i] = $value;
            $i++;
        }
        for($i=0;$i<count($arr1);$i++){
            $data = array();
            $data['lang_value'] = $arr3[$i];
            LanguageWebsiteText::where('id', $arr1[$i])->update($data);
        }
        return redirect()->back()->with('success', SUCCESS_ACTION);
    }

    public function language_notification_text() {
        $language_data = LanguageNotificationText::orderBy('id', 'asc')->get();
        return view('admin.language_notification_text_view', compact('language_data'));
    }

    public function language_notification_text_update(Request $request) {

        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }

        $i=0;
        foreach(request('lang_id') as $value) {
            $arr1[$i] = $value;
            $i++;
        }
        $i=0;
        foreach(request('lang_key') as $value){
            $arr2[$i] = $value;
            $i++;
        }
        $i=0;
        foreach(request('lang_value') as $value){
            $arr3[$i] = $value;
            $i++;
        }
        for($i=0;$i<count($arr1);$i++){
            $data = array();
            $data['lang_value'] = $arr3[$i];
            LanguageNotificationText::where('id', $arr1[$i])->update($data);
        }
        return redirect()->back()->with('success', SUCCESS_ACTION);
    }


    public function language_admin_panel_text() {
        $language_data = LanguageAdminPanelText::orderBy('id', 'asc')->get();
        return view('admin.language_admin_panel_text_view', compact('language_data'));
    }

    public function language_admin_panel_text_update(Request $request) {

        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }

        $i=0;
        foreach(request('lang_id') as $value) {
            $arr1[$i] = $value;
            $i++;
        }
        $i=0;
        foreach(request('lang_key') as $value){
            $arr2[$i] = $value;
            $i++;
        }
        $i=0;
        foreach(request('lang_value') as $value){
            $arr3[$i] = $value;
            $i++;
        }
        for($i=0;$i<count($arr1);$i++){
            $data = array();
            $data['lang_value'] = $arr3[$i];
            LanguageAdminPanelText::where('id', $arr1[$i])->update($data);
        }
        return redirect()->back()->with('success', SUCCESS_ACTION);
    }
// start menu create and store
    public function  admin_language_menu_text_create(Request $request){
        return view('admin.admin_language_menu_text_create');

    }
    public function  admin_language_menu_text_store(Request $request){
        $request->validate([
            'lang_value' => 'required|unique:language_menu_texts',
            'lang_key' => 'required|unique:language_menu_texts',
        ],[
            'lang_value.required' => ERR_VALUE_REQUIRED,
            'lang_key.unique' => ERR_KEY_EXIST,
            'lang_key.required' => ERR_KEY_REQUIRED,
            'lang_value.unique' => ERR_VALUE_EXIST
        ]);


        $data = $request->all();
        LanguageMenuText::create($data);
        return redirect()->route('admin_language_menu_text')->with('success', SUCCESS_ACTION);
    }
// end menu create and store

// start website menu create and store
    public function  admin_language_website_text_create(Request $request){
        return view('admin.language_website_text_create');

    }
    public function  admin_language_website_text_store(Request $request){
        $request->validate([
            'lang_value' => 'required|unique:language_website_texts',
            'lang_key' => 'required|unique:language_website_texts',
        ],[
            'lang_value.required' => ERR_VALUE_REQUIRED,
            'lang_key.unique' => ERR_KEY_EXIST,
            'lang_key.required' => ERR_KEY_REQUIRED,
            'lang_value.unique' => ERR_VALUE_EXIST
        ]);

        $data = $request->all();
        LanguageWebsiteText::create($data);
        return redirect()->route('admin_language_website_text')->with('success', SUCCESS_ACTION);
    }
// end  website menu create and store


// start notification menu create and store
    public function  admin_language_notification_text_create(Request $request){
        return view('admin.admin_language_notification_text_create');

    }
    public function  admin_language_notification_text_store(Request $request){
        $request->validate([
            'lang_value' => 'required|unique:language_notification_texts',
            'lang_key' => 'required|unique:language_notification_texts',
        ],[
            'lang_value.required' => ERR_VALUE_REQUIRED,
            'lang_key.unique' => ERR_KEY_EXIST,
            'lang_key.required' => ERR_KEY_REQUIRED,
            'lang_value.unique' => ERR_VALUE_EXIST
        ]);

        $data = $request->all();
        LanguageNotificationText::create($data);
        return redirect()->route('admin_language_notification_text')->with('success', SUCCESS_ACTION);
    }
// end  notification menu create and store

// start admin panel menu create and store
    public function  language_admin_panel_text_create(Request $request){
        return view('admin.language_admin_panel_text_create');

    }
    public function  language_admin_panel_text_store(Request $request){
        $request->validate([
            'lang_value' => 'required|unique:language_admin_panel_texts',
            'lang_key' => 'required|unique:language_admin_panel_texts',
        ],[
            'lang_value.required' => ERR_VALUE_REQUIRED,
            'lang_value.unique' => ERR_VALUE_EXIST,
            'lang_key.unique' => ERR_KEY_EXIST,
            'lang_key.required' => ERR_KEY_REQUIRED
        ]);

        $data = $request->all();
        LanguageAdminPanelText::create($data);
        return redirect()->route('admin_language_admin_panel_text')->with('success', SUCCESS_ACTION);
    }
    // end  admin panel menu create and store

}
