<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Api\CustomerAuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RegistrationController;
use Illuminate\Support\Facades\Redis;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
/** forget password start */
Route::post('customer/forget-passowrd', [CustomerAuthController::class,'send_email_phone_otp']); //dome
Route::post('customer/verify-otp', [CustomerAuthController::class,'verify_otp']); //dome
Route::post('customer/verify-password', [CustomerAuthController::class,'forgetPassword']); //dome


/** forget password end */

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::get('sponser_help', [RegistrationController::class,'sponser_help']); //dome
Route::post('star_active_users', [RegistrationController::class,'star_active_users']); //dome
Route::post('customer/registration/{id?}', [RegistrationController::class,'registration_store']); //dome


Route::post('customer/login', [CustomerAuthController::class,'login_store']);//dome


Route::post('customer/dashboard', [CustomerAuthController::class,'dashboard']);//dome


Route::post('customer/update-password', [CustomerAuthController::class,'update_password']);//dome

Route::post('customer/support-form', [CustomerAuthController::class,'support_form']);//dome--image


Route::post('customer/my-profile', [CustomerAuthController::class,'my_profile']);//dome

Route::post('customer/profile-update', [CustomerAuthController::class,'profile_update']);//dome city, country, state


Route::post('customer/view-direct', [CustomerAuthController::class,'view_direct']);//dome

Route::post('customer/view-downline', [CustomerAuthController::class,'view_downline']); //dome

Route::post('customer/helping-tree', [CustomerAuthController::class,'helping-tree']);//------------------------------

Route::post('customer/level-team-report', [CustomerAuthController::class,'level-team-report']);//------------------------------

Route::post('customer/help-history', [CustomerAuthController::class,'help_history']);//dome

Route::post('customer/taking-help', [CustomerAuthController::class,'taking_help']);//dome

Route::post('customer/view-sponsor-get-help', [CustomerAuthController::class,'view_sponsor_get_help']);//------------------------------

Route::post('customer/view-epin', [CustomerAuthController::class,'view_epin']); //dome

Route::post('customer/get-name-by-id', [CustomerAuthController::class,'get_name_by_id']);//dome


Route::post('customer/total-available-pin', [CustomerAuthController::class,'total_available_pin']); //dome

Route::post('customer/epin-transfer', [CustomerAuthController::class,'epin_transfer']);//dome

Route::post('customer/logout', [CustomerAuthController::class,'logout']);//------------------------------

Route::get('customer/active_users_id', [CustomerAuthController::class,'active_users_id']);//dome
Route::get('customer/active-users', [CustomerAuthController::class,'active_users']);//dome
// Route::get('testRedis', [CustomerAuthController::class,'testRedis']);//dome


Route::post('customer/bank-detail', [UserController::class,'bank_detail']);//dome
Route::post('customer/support-form', [CustomerAuthController::class,'support_form']);//dome
Route::post('customer/third_level_users/{id}', [CustomerAuthController::class,'third_level_users']);//dome


// Route::get('testRediss', function () {
//     $redis = Redis::connection();
//     $redis->set('foo', 'bar');
//     $value = $redis->get('foo');
//     return response()->json(['redis_value' => $value]);
// });