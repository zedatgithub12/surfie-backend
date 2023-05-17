<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Customers;
use App\Models\Coupon;
use App\Models\Partners;
use App\Mail\WelcomeMail;
use App\Mail\activation;
use App\Mail\deactivation;
use App\Mail\reactivation;
use App\Mail\expired;
use App\Mail\renewed;
use SimpleXMLElement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Mail\CustomerPasswordResetLink;
use App\Http\Controllers\ChapaController;

class CustomerController extends Controller
{
    //
    protected $publicKey;
    protected $secretKey;
    protected $baseUrl;
    protected $secretHash;
    public $surfieUrl;
    protected $username;
    protected $password;
    protected $remoteUrl;

    function __construct()
    {

        $this->publicKey = env('CHAPA_PUBLIC_KEY');
        $this->secretKey = env('CHAPA_SECRET_KEY');
        $this->secretHash = env('CHAPA_WEBHOOK_SECRET');
        $this->baseUrl = 'https://api.chapa.co/v1';
        $this->surfieUrl = 'http://localhost:8000/api/chapap/';
        $this->username = env('REMOTE_USERNAME');
        $this->password = env('REMOTE_PASSWORD');
        $this->remoteUrl = 'https://surfie.puresight.com/cgi-bin/ProvisionAPI/';
    }



    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        $customers = Customers::query()->where('status', $request->status)->orderByDesc('id')->paginate(12);
        return response()->json($customers, 200);

        //  return response()-> json(Customers::get());
    }
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');
        $user = Customers::where('email', $credentials['email'])->first();
        if ($user && Hash::check($credentials['password'], $user->password)) {
            // The email and password match a record in the database
            $message = $user;
        } else {
            // No record was found with the given email and password
            $message = "83";
        }
        return response()->json($message, 200);
    }
    public function forgotpassword(Request $request)
    {
        $email = $request->email;
        $user = Customers::where('email', $email)->first();

        if (!$user) {
            return response()->json(['message' => 'Email not found'], 404);
        }
        $token = Str::random(60);
        $addtotable = DB::table('password_reset_tokens')->where('email', $email)->first();
        if (!$addtotable) {
            DB::table('password_reset_tokens')->insert([
                'email' => $email,
                'token' => $token,
                'created_at' => now()
            ]);
            $message = "Reset your password";
            $data = ([
                'token' => $token,
                'message' => $message,

            ]);
            Mail::to($email)->send(new CustomerPasswordResetLink($data));

            return response()->json(['message' => 'Password reset link sent to your email'], 200);
        } else {
            return response()->json(['message' => 'We have already sent you reset link check your inbox or spam folder'], 404);
        }

    }

    public function resetpassword(Request $request)
    {
        $token = $request->token;
        $password = $request->password;
        $reset = DB::table('password_reset_tokens')->where('token', $token)->first();
        if (!$reset) {
            return response()->json(['message' => 'Invalid token'], 404);
        }
        $user = Customers::where('email', $reset->email)->first();
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        Customers::where('email', $user->email)->update(['password' => Hash::make($password)]);
        DB::table('password_reset_tokens')->where('email', $user->email)->delete();

        return response()->json(['message' => 'Password reset successful'], 200);
    }
    public function changepass(Request $request, string $id)
    {
        $user = Customers::find($id);
        if (!$user) {
            return response()->json(['message' => 'Partner not found'], 404);
        }

        $this->validate($request, [
            'oldpass' => 'required',
            'newpass' => 'required|string',
        ]);

        $hashedPassword = $user->password;

        if (Hash::check($request->oldpass, $hashedPassword)) {

            $user->fill([
                'password' => Hash::make($request->newpass)
            ])->save();
            return response()->json(['message' => 'changed successfully'], 200);

        } else {
            return response()->json(['message' => 'Old password does not match'], 500);
        }

    }
    public function singlec(string $id)
    {
        $customer = Customers::whereId($id)->get();
        return response()->json($customer, 200);
    }


    // price generator 
    public function pricing($license, $subscription)
    {
        $prices = array(
            "1_monthly" => 512,
            "5_monthly" => 675,
            "10_monthly" => 925,
            "1_annual" => 3595,
            "5_annual" => 4740,
            "10_annual" => 6465,
            "default" => 0
        );

        $key = $license . "_" . $subscription;
        $price = isset($prices[$key]) ? $prices[$key] : $prices["default"];

        return intval($price);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $referral_code = $request->referral;
        // $amount = $this->pricing($request->license, $request->subscription);
        // $startDate = now()->startOfMonth();
        // $endDate = now()->endOfMonth();
        //check if email and phone exist
        $emails = Customers::where('email', $request->emailaddress)->exists();
        $phones = Customers::where('phone', $request->phone)->exists();

        if ($emails) {
            $message = "1021";
        } else if ($phones) {
            $message = "1022";
        } else {
            $cid = Customers::create([
                'first_name' => $request->firstname,
                'middle_name' => $request->middlename,
                'last_name' => $request->lastname,
                'email' => $request->emailaddress,
                'phone' => $request->phone,
                'living_address' => $request->address,
                'username' => $request->username,
                'password' => bcrypt($request->password),
                'license' => $request->license,
                'subscription' => $request->subscription,
                'payment_method' => $request->payment,
                'coupon' => $request->coupon,
                'referralcode' => $request->referral,
                'status' => $request->status,
            ]);

            if ($referral_code) {

                // Get the partner with the provided referral code
                $partner = Partners::where('referralcode', $referral_code)->first();

                // If a partner was found with the provided referral code, update their balance and referral count
                if ($partner) {

                    // Increment the partner's referral count and check if they've referred 50 or more customers
                    $partner->noreferral++;
                }

                // Save the partner's updated balance and referral count to the database
                $partner->save();
            }
            $message = "0";
        }

        if ($request->payment === "1001" && $message == "0") {
            return $this->payment($request, $cid);
        } else {
            return response()->json($message, 200);
        }
    }

    public function upgradeCommissions($referralCode)
    {
        // Get the current month and year
        $month = date('m');
        $year = date('Y');

        // Get the partner associated with the referral code
        $partner = Partners::where('referralcode', $referralCode)->first();

        // If no partner is found, return an error response
        if (!$partner) {
            return response()->json(['error' => 'Invalid referral code']);
        }

        // Get all customers referred by the partner this month
        $customers = $partner->customers()->whereRaw("MONTH(created_at) = $month AND YEAR(created_at) = $year")->get();

        // Loop through each customer and calculate their commission for this month
        $commission = 0;
        foreach ($customers as $customer) {

            $commission += $customer->subscription_payment * ($partner->commission_rate / 100);
            $customer->save();

        }
        // // If the partner's commission rate is 5%, subtract the commission from their balance
        // if ($partner->commission_rate == 5) {
        //     $partner->balance -= $commission;
        // }

        // If the partner's commission rate is 10%, mark them as having received their commission for this month
        if ($partner->commission_rate == 10) {
            $partner->commission_received = true;
        }

        // If the partner has referred 50 or more customers, upgrade their commission rate to 10%
        if ($partner->noreferral >= 50 && $partner->commission_rate != 10) {
            $partner->commission_rate = 10;
        }

        // Save the partner's updated balance, commission rate, and commission received status to the database
        $partner->save();

        // Return a response indicating success
        return response()->json(['success' => true, 'message' => 'Commission upgrade successful']);
    }
    function referenceNo()
    {
        // Get the current timestamp
        $timestamp = microtime(true);

        // Generate a random number
        $randomNumber = mt_rand(1000, 9999);

        // Combine the timestamp and random number
        $referenceNumber = $timestamp . $randomNumber;

        return 'surfie' . $referenceNumber;
    }


    // payment initializer
    public function Payment($request, $cid)
    {

        $amount = $this->pricing($request->license, $request->subscription);
        // we check if the coupon code applied is exist in the database 
        //we retrive the amount of money coupon code holds -- which is subsctructed from the price
        if (isset($request->coupon)) {
            $exist = Coupon::query()->where('code', $request->coupon)->exists(); //check if the coupon code exist
            if ($exist) {
                $coupons = Coupon::where('code', $request->coupon)->where('quantity', '>', 2)->first();
                $amount = $this->pricing($request->license, $request->subscription) - $coupons->amount;
                if ($coupons) {
                    Coupon::where('id', $coupons->id)->decrement('quantity');
                }
            }
        }

        $id = $cid['id'];
        $txn_ref = $this->referenceNo(); //chapa transaction reference
        $data = ([
            'amount' => $amount,
            'currency' => 'ETB',
            'first_name' => $request->firstname,
            'last_name' => $request->lastname,
            'email' => $request->emailaddress,
            'tx_ref' => $txn_ref,
            'callback_url' => $this->surfieUrl . $txn_ref,
            "customization[title]" => $id,

        ]);
        $message = "";
        if ($request->payment === "1001") {
            $chapaResponse = Http::withToken($this->secretKey)->post(
                $this->baseUrl . '/transaction/initialize',
                $data
            )->json();

            if ($chapaResponse) {
                $payment = json_encode($chapaResponse);
                $link = json_decode($payment);

                if ($link->status == "success") {
                    $checkout_url = $link->data->checkout_url;
                    $message = $chapaResponse;

                } else {
                    $message = "41";
                }
            } else {
                $message = "41";
            }
        }
        return response()->json($link, 200);
    }

    public function search(Request $request)
    {

        $q = $request->get('name');
        return Customers::where('first_name', 'LIKE', '%' . $q . '%')->orWhere('email', 'LIKE', '%' . $q . '%')->orWhere('id', 'LIKE', '%' . $q . '%')->get();

    }
    /**
     * Update the specified resource in storage.
     */
    public function change(Request $request, string $id)
    {
        $response = Http::get($this->remoteUrl . "RemoveSubscription.py?accountId=$request->remoteid&subscriptionId=1&packageId=$request->currentPackage&adminUser=$this->username&adminPassword=$this->password");

        $xml = new SimpleXMLElement($response);
        $status = $xml->Status;
        $resid = (int) $status->attributes()->id;

        if ($response->ok() && $resid == 0 || $resid == 1014 || $resid == 2003) {
            $response = Http::get($this->remoteUrl . "AddSubscription.py?accountId=$request->remoteid&subscriptionId=1&packageId=$request->package&adminUser=$this->username&adminPassword=$this->password");

            $xml = new SimpleXMLElement($response);
            $status = $xml->Status;
            $resid = (int) $status->attributes()->id;

            if ($response->ok() && $resid == 0) {

                $customer = Customers::whereId($id)->first();
                $customer->update([
                    'license' => $request->license
                ]);
                $message = "0";

            } else {
                $message = $resid;
            }
        } else {
            $message = $resid;
        }
        return response()->json($message, 200);
    }

    // downgrade number of license 
    public function remove(Request $request, string $id)
    {
        $response = Http::get($this->remoteUrl . "RemoveSubscription.py?accountId=$request->remoteid&subscriptionId=1&packageId=$request->currentPackage&adminUser=$this->username&adminPassword=$this->password");
        $xml = new SimpleXMLElement($response);
        $status = $xml->Status;
        $identity = (int) $status->attributes()->id;
        if ($response->ok() && $identity == 0) {
            $customer = Customers::whereId($id)->first();
            $customer->update([
                'license' => $request->license
            ]);
            $message = $identity;
        } else {
            $message = $identity;
        }
        return response()->json($message, 200);
    }
    // deactivate user account
    public function deactivate(Request $request, string $id)
    {

        $response = Http::get($this->remoteUrl . "DeactivateAccount.py?accountId=$request->remoteid&adminUser=$this->username&adminPassword=$this->password");
        $xml = new SimpleXMLElement($response);
        $status = $xml->Status;
        $resid = (int) $status->attributes()->id;
        if ($response->ok() && $resid == 0) {
            $customer = Customers::whereId($id)->first();
            $customer->update([
                'status' => $request->cstatus
            ]);
            $email = $customer['email'];
            $body = 'It appears that your Surfie Ethiopia  account has been deactivated. Please contact the system administrator or customer support for further assistance in reactivating your account.';
            $data = ([
                'name' => $customer['first_name'],
                'email' => $customer['email'],
                'message' => $body,
            ]);

            Mail::to($email)->send(new deactivation($data));
            $message = $resid;
        } else {
            $message = $resid;
        }

        return response()->json($message, 200);
    }

    // detach user account
    public function detach(Request $request, string $id)
    {

        $response = Http::get($this->remoteUrl . "DetachUserCredentials.py?accountId=$request->remoteid&adminUser=$this->username&adminPassword=$this->password");
        $xml = new SimpleXMLElement($response);
        $status = $xml->Status;
        $resid = (int) $status->attributes()->id;
        if ($response->ok() && $resid == 0) {
            $customer = Customers::whereId($id)->first();
            $customer->update([
                'status' => $request->cstatus
            ]);
            $message = $resid;
        } else {
            $message = $resid;
        }

        return response()->json($message, 200);
    }

    public function activate(Request $request, string $id)
    {
        $customer = Customers::whereId($id)->first();
        $response = Http::get($this->remoteUrl . "CreateAccountWithPackageId.py?adminUser=$this->username&adminPassword=$this->password&email=$customer[email]&phoneNumber=$customer[phone]&packageId=AFROMINA_$customer[license]&subscriptionId=1&externalRef=AFROMINA");
        $xml = new SimpleXMLElement($response);
        $status = $xml->Status;
        $resid = (int) $status->attributes()->id;

        if ($response->ok() && $resid == 0) {
            $data = $xml->Data->Account;
            $accountid = (string) $data->attributes()->account_id;

            $dueDate = "";
            if ($customer->subscription === "annual") {
                $dueDate = Carbon::now()->addYear();
            } else {
                $dueDate = Carbon::now()->addMonth();
            }

            $customer->update([
                'remote_id' => $accountid,
                'duedate' => $dueDate,
                'status' => $request->status,
            ]);

            if ($customer->referralcode) {

                $amount = $this->pricing($customer->license, $customer->subscription);
                // Get the partner with the provided referral code
                $partner = Partners::where('referralcode', $customer->referralcode)->first();

                // If a partner was found with the provided referral code, update their balance and referral count
                if ($partner) {
                    // Add the referral incentive to the partner's balance
                    $incentive = $amount * 0.05;
                    $partner->balance += $incentive;

                }

                // Save the partner's updated balance and referral count to the database
                $partner->save();
            }
            $message = $resid;
        } else if ($response->serverError()) {
            $message = "500";
        } else {
            $message = $resid;
        }

        $email = $customer['email'];
        $greating = 'We would like to inform you that your Surfie Ethiopia account has been successfully activated. You can now start using the system to monitor and control your childs online activities.';
        $body = 'Please make sure to regularly check for any updates or changes in our website  If you have any questions or concerns, please do not hesitate to reach out to our support team.';
        $closing = 'Thank you for choosing Surfie Ethiopia  to help keep your child safe online.';

        $data = ([
            'name' => $customer['first_name'],
            'email' => $customer['email'],
            'greating' => $greating,
            'message' => $body,
            'closing' => $closing,
        ]);

        Mail::to($email)->send(new activation($data));
        return response()->json($message, 200);
    }

    public function reactivate(Request $request, string $id)
    {


        $response = Http::get($this->remoteUrl . "ActivateAccount.py?accountId=$request->remote_id&adminUser=$this->username&adminPassword=$this->password");
        $xml = new SimpleXMLElement($response);
        $status = $xml->Status;
        $resid = (int) $status->attributes()->id;
        if ($response->ok() && $resid == 0) {
            $customer = Customers::whereId($id)->first();
            $customer->update([
                'status' => $request->status
            ]);

            $email = $customer['email'];
            $greating = 'We would like to inform you that your Surfie Ethiopia account has been successfully reactivated. You can now start using the system to monitor and control your childs online activities.';
            $body = 'Please make sure to regularly check for any updates or changes in our website  If you have any questions or concerns, please do not hesitate to reach out to our support team.';
            $closing = 'Thank you for choosing Surfie Ethiopia  to help keep your child safe online.';

            $data = ([
                'name' => $customer['first_name'],
                'email' => $customer['email'],
                'greating' => $greating,
                'message' => $body,
                'closing' => $closing,
            ]);

            Mail::to($email)->send(new activation($data));
            $message = $resid;
        } else {
            $message = $resid;
        }


        return response()->json($message, 200);
    }


    // retrive counts from database
    public function counts()
    {
        $pendings = Customers::get()->where('status', 0)->count();
        $actives = Customers::get()->where('status', 1)->count();

        $monthly = Customers::get()->where('subscription', 'monthly')->where('status', 1)->count();
        $annual = Customers::get()->where('subscription', 'annual')->where('status', 1)->count();

        $newm = Customers::whereDate('created_at', Carbon::today())->where('subscription', 'monthly')->get()->count();
        $newa = Customers::whereDate('created_at', Carbon::today())->where('subscription', 'annual')->get()->count();


        $mfive = Customers::get()->where('subscription', 'monthly')->where('license', 5)->where('status', 1)->count();
        $mten = Customers::get()->where('subscription', 'monthly')->where('license', 10)->where('status', 1)->count();
        $mfifty = Customers::get()->where('subscription', 'monthly')->where('license', 15)->where('status', 1)->count();

        $afive = Customers::get()->where('subscription', 'annual')->where('license', 5)->where('status', 1)->count();
        $aten = Customers::get()->where('subscription', 'annual')->where('license', 10)->where('status', 1)->count();
        $afifty = Customers::get()->where('subscription', 'annual')->where('license', 15)->where('status', 1)->count();





        $compacted = compact('pendings', 'actives', 'monthly', 'annual', 'newm', 'newa', 'mfive', 'mten', 'mfifty', 'afive', 'aten', 'afifty');
        return response()->json($compacted);
    }
}