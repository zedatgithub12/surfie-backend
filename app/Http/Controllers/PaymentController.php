<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use SimpleXMLElement;
use App\Mail\activation;
use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Customers;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class PaymentController extends Controller
{
    //
    protected $publicKey;
    protected $secretKey;
    protected $baseUrl;
    protected $secretHash;
    protected $username;
    protected $password;
    protected $remoteUrl;
    
    function __construct()
    {
        
        $this->publicKey = env('CHAPA_PUBLIC_KEY');
        $this->secretKey = env('CHAPA_SECRET_KEY');
        $this->secretHash = env('CHAPA_WEBHOOK_SECRET');
        $this->baseUrl = 'https://api.chapa.co/v1';
        $this->username=  env('REMOTE_USERNAME');
        $this->password =env('REMOTE_PASSWORD');
        $this->remoteUrl = 'https://surfie-t.puresight.com/cgi-bin/ProvisionAPI/';
        
    }    

/**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
     
         $payments = Payment::query()->orderByDesc('id')->paginate(12);
         return response()-> json($payments, 200);
        

    }


    
  
    public function chapaResponse($id)
    {
        $verification =  Http::withToken($this->secretKey)->get($this->baseUrl . "/transaction/" . 'verify/'. $id )->json();
        $stringifyVerify = json_encode($verification);
        $response = json_decode($stringifyVerify);
        $message="";
        if($response->status === "success"){

            $paid = Payment::create([
                'customer_id'=> $response->data->customization->title,
                'txn_id'=> $response->data->reference,
                'txn_reference'=> $response->data->tx_ref,
                'first_name'=>$response->data->first_name,
                'last_name' => $response->data->last_name,
                'amount'=>$response->data->amount,
                'currency'=> $response->data->currency,
                'email'=> $response->data->email,
                'status' => $response->status,
            ]);

            if($paid){
                $message="yes";
                $cid = $response->data->customization->title;
                $this->activate($cid);
            }
            else{
                $message="no";
            }
        }    
}        
          public function activate($id)
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
                         if($customer->subscription === "annual"){
                         $dueDate = Carbon::now()->addYear();
                         } 
                         else {
                           $dueDate = Carbon::now()->addMonth();
                         }
            $customer->update([
                'remote_id' => $accountid,
                'duedate' => $dueDate,
                'status'=>'1',
                
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
          }
