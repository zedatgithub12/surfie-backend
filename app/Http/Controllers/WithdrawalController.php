<?php

namespace App\Http\Controllers;

use App\Models\Partners;
use Illuminate\Http\Request;
use App\Models\Withdrawals;


class WithdrawalController extends Controller
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
        $issueddate = now();
        $withdraw = Withdrawals::create([
            "partnerid" => $request->partnerid,
            "amount" => $request->amount,
            "channel" => $request->channel,
            "accountno" => $request->accountno,
            "issueddate" => $issueddate,
            "status" => 0,
        ]);

        if ($withdraw) {
            $message = 'succeed';
        } else {
            $message = 'not succeed';
        }

        return response()->json($message, 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $withdraws = Withdrawals::where('partnerid', $id)->orderByDesc('id')->get();
        return response()->json($withdraws, 200);
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