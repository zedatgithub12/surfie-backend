<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Models\Customers;
use App\Mail\WelcomeMail;

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
             'payment_method'=> $request->payment,
             'status' => $request->status,
        
        ]);

        
        return response()->json('succeed');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
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

              // deactivate user account
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
                $greating = 'We are thrilled to welcome you to our Surfie Ethiopia family! Thank you for choosing us as your service provider';
                $body = 'Surfie Ethiopia is a parental control Application which enables parents to monitor their childrens digital world interaction without limiting them, but supporting them. To learn more click the button below';
                $closing = 'We promise to provide you with exceptional customer service and top-quality products to meet your needs. Our team is dedicated to ensuring your satisfaction and we are here to assist you at any time';
                $footer = 'If you have any questions, concerns, or feedback. We value your input and look forward to hearing from you.';

                $data = ([
                    'name' => $customer['first_name'],
                    'email' => $customer['email'],
                    'username' => $customer['username'],
                    'phone' => $customer['phone'],
                    'greating' => $greating,
                    'message'=>$body,
                    'closing' => $closing,
                    'footer' => $footer,
                    ]);
            
            Mail::to($email)->send(new WelcomeMail($data));
          
         
                return response()->json('activated');
              }

            

       
}