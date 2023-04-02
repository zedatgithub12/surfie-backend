<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Trial;

class TrialController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
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
       
       $exist = Trial::where('email', $request->email)->exists();
       
       if($exist){
            $message = "exists";
       }else{
        Trial::tcreate([
            'email'=> $request->email
        ]);
        $message = "succeed";
       }
        
        return response()->json( $message, 200);
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
