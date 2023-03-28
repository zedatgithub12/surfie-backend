<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Models\Customers;
use App\Mail\WelcomeMail;
use App\Mail\activation;
use App\Mail\deactivation;
use App\Mail\reactivation;
use App\Mail\expired;
use App\Mail\renewed;


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
  if($request->subscription === "annual"){
    $dueDate = Carbon::now()->addYear();

        Customers::create([
             'remote_id' => $request->remote_id,
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
    }
    else {
        $dueDate = Carbon::now()->addMonth();

        Customers::create([
             'remote_id' => $request->remote_id,
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
    
    }
        
        return response()->json('succeed');
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
        $customer = Customers::whereId($id)->first();

        $customer->update([
            'license'=>$request->license
        ]);
        return response()->json('succeed');
    }


    // downgrade number of license 
    public function remove(Request $request, string $id)
    {
        $customer = Customers::whereId($id)->first();

        $customer->update([
            'license'=>$request->license
        ]);
        return response()->json('succeed');
    }


      // deactivate user account
      public function deactivate(Request $request, string $id)
      {
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
    
   
          return response()->json('deactivated');
      }

       // detach user account
       public function detach(Request $request, string $id)
       {
           $customer = Customers::whereId($id)->first();
   
           $customer->update([
               'status'=>$request->cstatus
           ]);
           return response()->json('detached');
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

}