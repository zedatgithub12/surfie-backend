<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use App\Models\Customers;
use App\Mail\WelcomeMail;
use App\Mail\activation;
use App\Mail\deactivation;
use App\Mail\reactivation;
use App\Mail\expired;
use App\Mail\renewed;
use SimpleXMLElement;


class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
     
         $customers = Customers::query()->where('status',$request->status)->orderByDesc('id')->paginate(12);
         return response()-> json($customers, 200);
        
        //  return response()-> json(Customers::get());
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user= "PSTEST-7620f683";
        $password= "pste5682bb";

        $response = Http::get("https://surfie-t.puresight.com/cgi-bin/ProvisionAPI/CreateAccountWithPackageId.py?adminUser=$user&adminPassword=$password&email=$request->emailaddress&phoneNumber=$request->phone&packageId=$request->package&subscriptionId=1&externalRef=AFROMINA");
      
        $xml = new SimpleXMLElement($response);
            $status = $xml->Status;
            $resid = (int) $status->attributes()->id;

        if($response->ok() &&  $resid == 0){

            $accountid = (int) $xml->Status->DATA->Account['account_id'];
            // $accountid = (string) $account_id->attributes()->account_id;
           
            $dueDate = "";
                if($request->subscription === "annual"){
                $dueDate = Carbon::now()->addYear();
                return $dueDate;
                } 
                else {
                  $dueDate = Carbon::now()->addMonth();
                  return $dueDate;
                }

        Customers::create([
             'remote_id' => $accountid,
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
             'duedate' => $dueDate,
             'payment_method'=> $request->payment,
             'status' => $request->status,
        
        ]);
        $message = $resid;
    }
    else if($response->serverError()){

        $message = "500";
    }
    else {
        $message = $resid;
    }
        return response()->json($message, 200);
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

        $response = Http::get("https://surfie-t.puresight.com/cgi-bin/ProvisionAPI/AddSubscription.py?accountId=$request->reomteid&subscriptionId=1&packageId=$request->package&adminUser=$user&adminPassword=$password");
      
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
        $user= "PSTEST-7620f683";
        $password= "pste5682bb";

        $response = Http::get("https://surfie-t.puresight.com/cgi-bin/ProvisionAPI/AddSubscription.py?accountId=$request->reomteid&subscriptionId=1&packageId=$request->package&adminUser=$user&adminPassword=$password");
      
        $xml = new SimpleXMLElement($response);
            $status = $xml->Status;
            $resid = (int) $status->attributes()->id;

        if($response->ok() &&  $resid == 0){
            
            $response = Http::get("https://surfie-t.puresight.com/cgi-bin/ProvisionAPI/RemoveSubscription.py?accountId=$request->reomteid&subscriptionId=1&packageId=$request->currentPackage&adminUser=$user&adminPassword=$password");
      
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
          
        $user= "PSTEST-7620f683";
        $password= "pste5682bb";

        $response = Http::get("https://surfie-t.puresight.com/cgi-bin/ProvisionAPI/DeactivateAccount.py?accountId=$request->reomteid&adminUser=$user&adminPassword=$password");
      
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

        $response = Http::get("https://surfie-t.puresight.com/cgi-bin/ProvisionAPI/DetachUserCredentials.py?accountId=$request->reomteid&adminUser=$user&adminPassword=$password");
      
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

              public function activate(Request $request, string $id)
              {
                $customer = Customers::whereId($id)->first();
  
                $customer->update([
                    'status'=>$request->status
                ]);

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
                return response()->json('activated');
              }

              public function reactivate(Request $request, string $id)
              {

                $user= "PSTEST-7620f683";
                $password= "pste5682bb";
        
                $response = Http::get("https://surfie-t.puresight.com/cgi-bin/ProvisionAPI/ActivateAccount.py?accountId=$request->remote_id&adminUser=$user&adminPassword=$password");
              
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

}