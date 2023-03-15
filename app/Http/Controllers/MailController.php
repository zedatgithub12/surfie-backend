<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Models\Email;
use App\Mail\Compose;

class MailController extends Controller
{
    //

    public function Send(Request $request){

        Email::create([

            'fullname' => $request->fullname,
            'email' => $request->email,
            'subject'=> $request->subject,
            'description'  => $request->description,
            'status' => $request->status,
       
       ]);

       $email = $request->get('email');
       
       $data = ([
           'subject' => $request->get('subject'),
           'email' => $request->get('email'),
           'description'=> $request->get('description'),
          ]);
           
           Mail::to($email)->send(new Compose($data));
         
        
       return response()->json('succeed');

    }
}
