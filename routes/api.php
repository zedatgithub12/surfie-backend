<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\HTTP\Controllers\AuthController;
use App\HTTP\Controllers\CustomerController;
use App\HTTP\Controllers\SupportController;
use App\HTTP\Controllers\MailController;
use App\HTTP\Controllers\TrialController;
use App\HTTP\Controllers\CouponController;
use App\HTTP\Controllers\PaymentController;
use App\HTTP\Controllers\PartnerController;
use App\HTTP\Controllers\WithdrawalController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')->get('/partners', function (Request $request) {
    return $request->partners();
});


Route::post('login', [AuthController::class, 'login']);

Route::group(['middleware' => 'api'], function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::post('me', [AuthController::class, 'me']);
});

Route::resource('customers', CustomerController::class);
Route::get('singlecustomer/{id}', [CustomerController::class, 'singlec']);
Route::get('search', [CustomerController::class, 'search']);
Route::put('add/{id}', [CustomerController::class, 'add']);
Route::put('remove/{id}', [CustomerController::class, 'remove']);
Route::put('deactivate/{id}', [CustomerController::class, 'deactivate']);
Route::put('detach/{id}', [CustomerController::class, 'detach']);

Route::get('pending', [CustomerController::class, 'counts']);
Route::put('activate/{id}', [CustomerController::class, 'activate']);
Route::put('reactivate/{id}', [CustomerController::class, 'reactivate']);
Route::post('newquery', [SupportController::class, 'store']);
Route::get('support', [SupportController::class, 'index']);
Route::put('closeticket/{id}', [SupportController::class, 'close']);

Route::post('compose', [MailController::class, 'send']);

//free trial email collection field
Route::resource('trial', TrialController::class);

// coupon code 
Route::post('coupon/{id}', [CouponController::class, 'show']);

//payment Gateways
Route::get('chapa', [PaymentController::class, 'index']);
Route::get('chapap/{id}', [PaymentController::class, 'chapaResponse']);

//partner Api's
Route::post('pregister', [PartnerController::class, 'register']);
Route::post('partnerlogin', [PartnerController::class, 'login']);
Route::resource('update', PartnerController::class);
Route::put('changepass/{id}', [PartnerController::class, 'changepass']);
Route::get('referred/{id}', [PartnerController::class, 'show']);
Route::post('withdraw', [WithdrawalController::class, 'store']);
Route::get('withdrawals/{id}', [WithdrawalController::class, 'show']);
Route::get('referrals/{id}', [PartnerController::class, 'balance']);

Route::post('forgotpassword', [PartnerController::class, 'forgotpassword']);
Route::post('resetpassword', [PartnerController::class, 'resetpassword']);