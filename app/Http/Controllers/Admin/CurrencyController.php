<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use DB;
use Auth;

class CurrencyController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth.admin:admin');
    }

    public function index()
    {
        $currency = Currency::all();
        return view('admin.currency_view', compact('currency'));
    }

    public function create()
    {
        return view('admin.currency_create');
    }

    public function store(Request $request)
    {

        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }

        $request->validate([
            'name' => 'required|unique:currencies',
            'symbol' => 'required',
            'value' => 'required'
        ],[
            'name.required' => ERR_NAME_REQUIRED,
            'name.unique' => ERR_NAME_EXIST,
            'symbol.required' => ERR_SYMBOL_REQUIRED,
            'value.required' => ERR_VALUE_REQUIRED
        ]);

        $currency = new Currency();
        
        if($request->is_default == 'Yes') {
            DB::table('currencies')->update(['is_default' => 'No']);
        }

        $currency->name = $request->name;
        $currency->symbol = $request->symbol;
        $currency->value = $request->value;
        $currency->is_default = $request->is_default;
        $currency->save();

        return redirect()->route('admin_currency_view')->with('success', SUCCESS_DATA_ADD);
    }

    public function edit($id)
    {
        $currency = Currency::findOrFail($id);
        return view('admin.currency_edit', compact('currency'));
    }

    public function update(Request $request, $id)
    {

        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }

        $request->validate([
            'name' =>  [
                'required',
                Rule::unique('currencies')->ignore($id),
            ],
            'symbol' => 'required',
            'value' => 'required'
        ],[
            'name.required' => ERR_NAME_REQUIRED,
            'name.unique' => ERR_NAME_EXIST,
            'symbol.required' => ERR_SYMBOL_REQUIRED,
            'value.required' => ERR_VALUE_REQUIRED
        ]);

        $currency = Currency::where('id',$id)->first();
        
        if($request->is_default == 'Yes') {
            DB::table('currencies')->update(['is_default' => 'No']);
        }

        $currency->name = $request->name;
        $currency->symbol = $request->symbol;
        $currency->value = $request->value;
        $currency->is_default = $request->is_default;
        $currency->update();

        return redirect()->route('admin_currency_view')->with('success', SUCCESS_DATA_UPDATE);
    }

    public function destroy($id)
    {
        if(env('PROJECT_MODE') == 0) {
            return redirect()->back()->with('error', env('PROJECT_NOTIFICATION'));
        }

        if($id == 1) {
            return Redirect()->back();
        }
        
        $currency = Currency::where('id',$id)->first();

        if($currency->is_default == 'Yes') {
            DB::table('currencies')->where('id',1)->update(['is_default' => 'Yes']);
        }
        $currency->delete();

        return Redirect()->back()->with('success', SUCCESS_DATA_DELETE);
    }

}
