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
use App\Mail\WelcomeMail;
use App\Mail\activation;
use App\Mail\deactivation;
use App\Mail\reactivation;
use App\Mail\expired;
use App\Mail\renewed;
use SimpleXMLElement;


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
        $this->surfieUrl = 'http://localhost:8000/api/chapa/';
        $this->username=  env('REMOTE_USERNAME');
        $this->password =env('REMOTE_PASSWORD');
        $this->remoteUrl = 'https://surfie-t.puresight.com/cgi-bin/ProvisionAPI/';
    } 

 

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
     
         $customers = Customers::query()->where('status',$request->status)->orderByDesc('id')->paginate(12);
         return response()-> json($customers, 200);
        
        //  return response()-> json(Customers::get());
    }

    public function singlec(string $id){
        $customer = Customers::whereId($id)->get();
        return response()->json($customer, 200);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $emails = Customers::where('email', $request->emailaddress)->exists();
        $phones = Customers::where('phone', $request->phone )->exists();
        
        if($emails){
            $message = "1021";
       }
        else if($phones){
        $message = "1022";
         }
        else{
                $cid = Customers::create([
                 'first_name' => $request->firstname,
                 'middle_name' => $request->middlename,
                 'last_name'  => $request->lastname,
                 'email' => $request->emailaddress,
                 'phone' => $request->phone,
                 'living_address' => $request->address,
                 'username' => $request->username,
                 'password'  => bcrypt($request->password),
                 'license' => $request->license,
                 'subscription' => $request->subscription,
                 'payment_method'=> $request->payment,
                 'coupon'=> $request->coupon,
                 'status' => $request->status,
            ]);

            $message = "0";
        }

        if(!($request->payment== "1000") && $message == "0"){
            return $this->payment($request, $cid);
          }
          else {
            return response()->json($message, 200);
          }
    }

  // price generator 
    public function pricing($license, $subscription){
        $price = "";
    // reinitiate price depending on user preferences
     if($license == "3" && $subscription === "monthly"){
         $price = "450";
           }
           else if($license == "5" && $subscription === "monthly"){
             $price = "600";
               }
               
               else if($license == "1" && $subscription === "annual"){
                 $price = "3300";
                   }
                   else if($license == "3" && $subscription === "annual"){
                     $price = "4950";
                       }
                       else if($license == "5" && $subscription === "annual"){
                         $price = "6600";
                           }
                           else {
                            $price = "300";
                              }

                           return intval($price);
    }
    function referenceNo() {
        // Get the current timestamp
        $timestamp = microtime(true);
    
        // Generate a random number
        $randomNumber = mt_rand(1000, 9999);
    
        // Combine the timestamp and random number
        $referenceNumber =  $timestamp . $randomNumber;

        return 'surfie' . $referenceNumber;
    }


    // payment initializer
    public function Payment($request, $cid){

            $amount= $this->pricing($request->license, $request->subscription);
               // we check if the coupon code applied is exist in the database 
               //we retrive the amount of money coupon code holds -- which is subsctructed from the price
                  if (isset($request->coupon)) {
                      $exist =  Coupon::query()->where('code',$request->coupon)->exists(); //check if the coupon code exist
                       if($exist){
                             $coupons = Coupon::where('code',$request->coupon)->where('quantity', '>', 2)->first();
                             $amount = $this->pricing($request->license, $request->subscription) - $coupons->amount;
                         if ($coupons) {
                             Coupon::where('id', $coupons->id)->decrement('quantity');
                         }
                      }
                  }

          $id = $cid['id'];
          $txn_ref = $this->referenceNo(); //chapa transaction reference
            $data = ([
                'amount'=>$amount,
                'currency'=> 'ETB',
                'first_name' => $request->firstname,
                'last_name'  => $request->lastname,
                'email' => $request->emailaddress,
                'tx_ref' => $txn_ref, 
                'callback_url'=> $this->surfieUrl . $txn_ref,
                "customization[title]"=> $id,
                
            ]);
            $message="";
                      if($request->payment == "1001"){
                      $chapaResponse = Http::withToken($this->secretKey)->post(
                          $this->baseUrl . '/transaction/initialize',
                          $data
                      )->json();

                      if($chapaResponse){
                      $payment = json_encode($chapaResponse);
                      $link = json_decode($payment);

                      if($link->status == "success"){
                        $checkout_url = $link->data->checkout_url;
                        $message= $chapaResponse;
                       
                      }
                      else {
                          $message ="41";
                      }
                    }
                    else {
                        $message ="41";
                    }
 }
                  return response()->json($link, 200);
    }

    public function search(Request $request)
    {

        $q = $request->get ( 'name' );
       return Customers::where('first_name','LIKE','%'.$q.'%')->orWhere('email','LIKE','%'.$q.'%')->orWhere('id','LIKE','%'.$q.'%')->get();
       
    }

     /**
     * Update the specified resource in storage.
     */
    public function add(Request $request, string $id)
    {
        $user= "PSTEST-7620f683";
        $password= "pste5682bb";

        $response = Http::get("https://surfie-t.puresight.com/cgi-bin/ProvisionAPI/AddSubscription.py?accountId=$request->remoteid&subscriptionId=1&packageId=$request->package&adminUser=$this->username&adminPassword=$this->password");
      
        $xml = new SimpleXMLElement($response);
            $status = $xml->Status;
            $resid = (int) $status->attributes()->id;

        if($response->ok() &&  $resid == 0){
            
               $customer = Customers::whereId($id)->first();
               $customer->update([
              'license'=>$request->license
             ]);
             $message = "0";

        }else {
            $message = $resid;
        }
     
        return response()->json($message, 200);
    }

    // downgrade number of license 
    public function remove(Request $request, string $id)
    {
      

        $response = Http::get($this->remoteUrl . "AddSubscription.py?accountId=$request->remoteid&subscriptionId=1&packageId=$request->package&adminUser=$this->username&adminPassword=$this->password");
      
        $xml = new SimpleXMLElement($response);
            $status = $xml->Status;
            $resid = (int) $status->attributes()->id;

        if($response->ok() &&  $resid == 0){
            
            $response = Http::get($this->remoteUrl . "RemoveSubscription.py?accountId=$request->remoteid&subscriptionId=1&packageId=$request->currentPackage&adminUser=$this->username&adminPassword=$this->password");
      
            $xml = new SimpleXMLElement($response);
                $status = $xml->Status;
                $identity = (int) $status->attributes()->id;
    
            if($response->ok() &&  $identity == 0){

               $customer = Customers::whereId($id)->first();

                $customer->update([
                'license'=>$request->license
               ]);
             $message = $identity;
            }
            else {
            $message = $identity;
            }

       }else {
        $message = $resid;
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

        if($response->ok() &&  $resid == 0){
          $customer = Customers::whereId($id)->first();
          $customer->update([
              'status'=>$request->cstatus
          ]);
          $email = $customer['email'];
          $body = 'It appears that your Surfie Ethiopia  account has been deactivated. Please contact the system administrator or customer support for further assistance in reactivating your account.';
          $data = ([
              'name' => $customer['first_name'],
              'email' => $customer['email'],
              'message'=>$body,
              ]);
      
         Mail::to($email)->send(new deactivation($data));

         $message = $resid;
            }
            else {
                $message = $resid;
            }

          return response()->json($message, 200);
      }

       // detach user account
    public function detach(Request $request, string $id)
       {
               
        $user= "PSTEST-7620f683";
        $password= "pste5682bb";

        $response = Http::get($this->remoteUrl . "DetachUserCredentials.py?accountId=$request->remoteid&adminUser=$this->username&adminPassword=$this->password");
      
        $xml = new SimpleXMLElement($response);
            $status = $xml->Status;
            $resid = (int) $status->attributes()->id;

        if($response->ok() &&  $resid == 0){
          $customer = Customers::whereId($id)->first();
          $customer->update([
              'status'=>$request->cstatus
          ]);
        
         
         $message = $resid;
            }
            else {
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

                if($response->ok() &&  $resid == 0){
                   $data = $xml->Data->Account;
                   $accountid = (string)  $data->attributes()->account_id;
                
                             $dueDate = "";
                             if($request->subscription === "annual"){
                             $dueDate = Carbon::now()->addYear();
                             } 
                             else {
                               $dueDate = Carbon::now()->addMonth();
                             }


                $customer->update([
                    'remote_id' => $accountid,
                    'duedate' => $dueDate,
                    'status'=>$request->status,
                    
                ]);
                $message = $resid;
    
                   }
                   else if($response->serverError()){
               
                       $message = "500";
                   }
                   else {
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
                           'message'=>$body,
                           'closing' => $closing,
                           ]);
                   
                      Mail::to($email)->send(new activation($data));
                       return response()->json($message, 200);
                     }
       
                  public function reactivate(Request $request, string $id)
              {

                $user= "PSTEST-7620f683";
                $password= "pste5682bb";
        
                $response = Http::get($this->remoteUrl . "ActivateAccount.py?accountId=$request->remote_id&adminUser=$user&adminPassword=$password");
              
                $xml = new SimpleXMLElement($response);
                    $status = $xml->Status;
                    $resid = (int) $status->attributes()->id;
        
                if($response->ok() &&  $resid == 0){

                $customer = Customers::whereId($id)->first();
  
                $customer->update([
                    'status'=>$request->status
                ]);

                $email = $customer['email'];
                $greating = 'We would like to inform you that your Surfie Ethiopia account has been successfully reactivated. You can now start using the system to monitor and control your childs online activities.';
                $body = 'Please make sure to regularly check for any updates or changes in our website  If you have any questions or concerns, please do not hesitate to reach out to our support team.';
                $closing = 'Thank you for choosing Surfie Ethiopia  to help keep your child safe online.';

                $data = ([
                    'name' => $customer['first_name'],
                    'email' => $customer['email'],
                    'greating' => $greating,
                    'message'=>$body,
                    'closing' => $closing,
                    ]);
            
               Mail::to($email)->send(new activation($data));
               $message =$resid;
                }
                else {
                $message =$resid;
                }


                return response()->json($message, 200);
              }

              // retrive counts from database
    public function counts()
              {                   
                $pendings = Customers::get()->where('status',0)->count();
                $actives = Customers::get()->where('status',1)->count();

                $monthly = Customers::get()->where('subscription','monthly')->where('status',1)->count();
                $annual = Customers::get()->where('subscription','annual')->where('status',1)->count();

                $newm = Customers::whereDate('created_at', Carbon::today())->where('subscription','monthly')->get()->count();
                $newa = Customers::whereDate('created_at', Carbon::today())->where('subscription','annual')->get()->count();


                $mfive = Customers::get()->where('subscription','monthly')->where('license',5)->where('status',1)->count();
                $mten = Customers::get()->where('subscription','monthly')->where('license',10)->where('status',1)->count();
                $mfifty = Customers::get()->where('subscription','monthly')->where('license',15)->where('status',1)->count();
          
                $afive = Customers::get()->where('subscription','annual')->where('license',5)->where('status',1)->count();
                $aten = Customers::get()->where('subscription','annual')->where('license',10)->where('status',1)->count();
                $afifty = Customers::get()->where('subscription','annual')->where('license',15)->where('status',1)->count();
          
            



                 $compacted = compact('pendings','actives','monthly','annual','newm','newa', 'mfive', 'mten','mfifty','afive', 'aten','afifty');
                 return response()->json($compacted);
              }
}