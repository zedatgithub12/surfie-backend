<?php

use App\Http\Controllers\ChapaController;
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
use App\Models\Customers;

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
Route::put('add/{id}', [CustomerController::class, 'change']);
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

//Chapa Api's
Route::get('chapa', [PaymentController::class, 'index']);
Route::get('chapap/{id}', [PaymentController::class, 'chapaResponse']);
Route::get('chaparenew/{id}', [ChapaController::class, 'chapaRenewResponse']);
Route::get('chapaupgrade/{id}', [ChapaController::class, 'chapaUpgradeResponse']);
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

//Parent Api's
Route::post('parentlogin', [CustomerController::class, 'login']);
Route::post('parentfpassword', [CustomerController::class, 'forgotpassword']);
Route::post('parentrpassword', [CustomerController::class, 'resetpassword']);
Route::put('changeppass/{id}', [CustomerController::class, 'changepass']);


Route::post('/renewal/{id}', function ($id, Request $request) {
    $selectedGateway = $request->channel;

    // Check if the selected payment gateway is valid
    if ($selectedGateway != 1001 && $selectedGateway != 1002) {
        return response()->json(['message' => 'Invalid payment gateway'], 400);
    }

    // Get the customer from the database
    $customer = Customers::find($id);
    if (!$customer) {
        return response()->json(['message' => 'Customer not found'], 404);
    }

    // Call the appropriate payment controller based on the selected gateway
    switch ($selectedGateway) {
        case 1001:
            $controller = app()->make('App\Http\Controllers\ChapaController');
            break;
        case 1002:
            $controller = app()->make('App\Http\Controllers\TelebirrController');
            break;
        default:
            return response()->json(['message' => 'Invalid payment gateway'], 400);
    }

    // Call the makePayment method on the payment controller
    $result = $controller->makeRenewalPayment($customer);
    return response()->json($result, 200);
    // Do something with the payment result

});
Route::post('/upgrade/{id}', function ($id, Request $request) {
    $selectedGateway = $request->channel;

    // Check if the selected payment gateway is valid
    if ($selectedGateway != 1001 && $selectedGateway != 1002) {
        return response()->json(['message' => 'Invalid payment gateway'], 400);
    }

    // Get the customer from the database
    $customer = Customers::find($id);
    if (!$customer) {
        return response()->json(['message' => 'Customer not found'], 404);
    }

    // Call the appropriate payment controller based on the selected gateway
    switch ($selectedGateway) {
        case 1001:
            $controller = app()->make('App\Http\Controllers\ChapaController');
            break;
        case 1002:
            $controller = app()->make('App\Http\Controllers\TelebirrController');
            break;
        default:
            return response()->json(['message' => 'Invalid payment gateway'], 400);
    }

    // Call the makePayment method on the payment controller
    $result = $controller->makeUpgradePayment($customer);
    return response()->json($result, 200);
    // Do something with the payment result

});