<?php
namespace App\Http\Controllers\Front;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Currency;
use DB;
use Illuminate\Support\Facades\Mail;

class CurrencyController extends Controller
{
    public function index(Request $request) 
    {
        $currency_single = Currency::where('name',$request->currency_name)->first();
        session()->put('currency_name',$currency_single->name);
        session()->put('currency_symbol',$currency_single->symbol);
        session()->put('currency_value',$currency_single->value);

        return Redirect()->back();
    }
}
