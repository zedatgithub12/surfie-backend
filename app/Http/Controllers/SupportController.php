<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Models\Support;
use App\Mail\WelcomeMail;


class SupportController extends Controller

{
    public function index(Request $request)
    {
     
         $queries = Support::query()->orderByDesc('id');

         return response()-> json(Support::get());
        //
    }

    public function store(Request $request)
    {
      

        Support::create([
             'fullname' => $request->fullname,
             'email' => $request->email,
             'description'  => $request->message,
             'status' => $request->status,
        
        ]);
        return response()->json('succeed');
    }

    public function close(Request $request, string $id){
        $customer = Support::whereId($id)->first();

        $customer->update([
            'status'=>'2'
        ]);
        return response()->json('done');
    }
}
