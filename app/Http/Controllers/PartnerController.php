<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Partners;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class PartnerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function login(Request $request)
    {
     

        $credentials = $request->only('email', 'password');

        $user = Partners::where('email', $credentials['email'])->first();

     

          if ($user && Hash::check($credentials['password'], $user->password)) {
              // The email and password match a record in the database
              $message= $user;
           
          } else {
              // No record was found with the given email and password
              $message= "83";
           }

         return response()->json($message, 200);
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
    public function register(Request $request)
    {
        $referralCode="";
        do {
            $referralCode = "surfie" . Str::random(6); // Generate a random value
        } while (Partners::where('referralcode', $referralCode)->exists());
        

        $emails = Partners::where('email', $request->email)->exists();
        $phones = Partners::where('phone', $request->phone )->exists();
        
        if($emails){
            $message = "80";
       }
        else if($phones){
        $message = "81";
         }
        else{
      
        Partners::create([
            "fname"=>$request->fname,
            "mname"=>$request->mname,
            "lname"=>$request->lname,
            "email"=>$request->email,
            "phone"=>$request->phone,
            "organization"=>$request->organization,
            "password" =>bcrypt($request->password),
            "referralcode" => $referralCode,
            "status" => 0,
        ]);
        $message = "200";
    }

    return response()->json($message, 200);
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
}
