<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Customers;


class ChapaController extends Controller
{
    protected $publicKey;
    protected $secretKey;
    protected $baseUrl;
    protected $secretHash;
    protected $username;
    protected $password;
    public $surfieUrl;
    public $renewurl;
    public $upgrade;

    protected $remoteUrl;

    function __construct()
    {
        $this->publicKey = env('CHAPA_PUBLIC_KEY');
        $this->secretKey = env('CHAPA_SECRET_KEY');
        $this->secretHash = env('CHAPA_WEBHOOK_SECRET');
        $this->baseUrl = 'https://api.chapa.co/v1';
        $this->surfieUrl = 'http://localhost:8000/api/chapap/';
        $this->renewurl = 'http://localhost:8000/api/chaparenew/';
        $this->upgrade = 'http://localhost:8000/api/chapaupgrade/';
        $this->username = env('REMOTE_USERNAME');
        $this->password = env('REMOTE_PASSWORD');
        $this->remoteUrl = 'https://surfie-t.puresight.com/cgi-bin/ProvisionAPI/';

    }
    public function pricing($license, $subscription)
    {
        $prices = array(
            "1_monthly" => 512,
            "5_monthly" => 675,
            "10_monthly" => 925,
            "1_annual" => 3595,
            "5_annual" => 4740,
            "10_annual" => 6465,
            "default" => 0
        );

        $key = $license . "_" . $subscription;
        $price = isset($prices[$key]) ? $prices[$key] : $prices["default"];

        return intval($price);
    }
    function referenceNo()
    {
        // Get the current timestamp
        $timestamp = microtime(true);

        // Generate a random number
        $randomNumber = mt_rand(1000, 9999);

        // Combine the timestamp and random number
        $referenceNumber = $timestamp . $randomNumber;

        return 'surfie' . $referenceNumber;
    }
    public function makeRenewalPayment($customer)
    {
        $amount = $this->pricing($customer->license, $customer->subscription);
        // we check if the coupon code applied is exist in the database 
        //we retrive the amount of money coupon code holds -- which is subsctructed from the price
        $txn_ref = $this->referenceNo(); //chapa transaction reference
        $data = ([
            'amount' => $amount,
            'currency' => 'ETB',
            'first_name' => $customer->first_name,
            'last_name' => $customer->last_name,
            'email' => $customer->email,
            'tx_ref' => $txn_ref,
            'callback_url' => $this->renewurl . $txn_ref,
            "customization[title]" => $customer->id,

        ]);
        $message = "";
        $chapaResponse = Http::withToken($this->secretKey)->post(
            $this->baseUrl . '/transaction/initialize',
            $data
        )->json();
        if ($chapaResponse) {
            $payment = json_encode($chapaResponse);
            $link = json_decode($payment);
            if ($link->status == "success") {
                // $checkout_url = $link->data->checkout_url;
                $message = $chapaResponse;
            } else {
                $message = "41";
            }
        } else {
            $message = "41";
        }

        return response()->json($link, 200);
    }
    public function chapaRenewResponse($id)
    {
        $timestamp = Carbon::now();
        $verification = Http::withToken($this->secretKey)->get($this->baseUrl . "/transaction/" . 'verify/' . $id)->json();
        $stringifyVerify = json_encode($verification);
        $response = json_decode($stringifyVerify);
        $message = "";

        if ($response->status === "success") {
            $paid = Payment::create([
                'customer_id' => $response->data->customization->title,
                'txn_id' => $response->data->reference,
                'txn_reference' => $response->data->tx_ref,
                'first_name' => $response->data->first_name,
                'last_name' => $response->data->last_name,
                'amount' => $response->data->amount,
                'currency' => $response->data->currency,
                'email' => $response->data->email,
                'status' => $response->status,
            ]);

            if ($paid) {
                $message = "yes";
                $cid = $response->data->customization->title;
                $this->renew($cid);
            } else {
                $message = "no";
                $customerId = $response->data->customization->title;
                $amount = $response->data->amount;
                $Currency = $response->data->currency;
                $reference = $response->data->reference;
                Storage::disk('local')->append('Paymentlogs.txt', 'The customer with ID ' . $customerId . ' and reference $reference have paid' . $amount . $Currency . 'at' . $timestamp . 'and the info did not added to database');
            }
        } else {
            Storage::disk('local')->append('Paymentlogs.txt', 'payment verification with reference number ' . $id . ' have failed');

        }
    }
    public function renew($cid)
    {
        $customer = Customers::find($cid);
        $dueDate = "";
        if ($customer->subscription == 'annual') {
            $dueDate = date('Y-m-d', strtotime($customer->duedate . ' +1 year'));
        } else {
            $dueDate = date('Y-m-d', strtotime($customer->duedate . ' +1 month'));
        }
        $customer->update([
            'duedate' => $dueDate,
            'status' => '1',
        ]);
    }
    public function makeUpgradePayment($customer)
    {
        $amount = $this->pricing($customer->license, 'annual');
        // we check if the coupon code applied is exist in the database 
        //we retrive the amount of money coupon code holds -- which is subsctructed from the price
        $txn_ref = $this->referenceNo(); //chapa transaction reference
        $data = ([
            'amount' => $amount,
            'currency' => 'ETB',
            'first_name' => $customer->first_name,
            'last_name' => $customer->last_name,
            'email' => $customer->email,
            'tx_ref' => $txn_ref,
            'callback_url' => $this->renewurl . $txn_ref,
            "customization[title]" => $customer->id,

        ]);
        $message = "";
        $chapaResponse = Http::withToken($this->secretKey)->post(
            $this->baseUrl . '/transaction/initialize',
            $data
        )->json();
        if ($chapaResponse) {
            $payment = json_encode($chapaResponse);
            $link = json_decode($payment);
            if ($link->status == "success") {
                // $checkout_url = $link->data->checkout_url;
                $message = $chapaResponse;
            } else {
                $message = "41";
            }
        } else {
            $message = "41";
        }

        return response()->json($link, 200);
    }
    public function chapaUpgradeResponse($id)
    {
        $timestamp = Carbon::now();
        $verification = Http::withToken($this->secretKey)->get($this->baseUrl . "/transaction/" . 'verify/' . $id)->json();
        $stringifyVerify = json_encode($verification);
        $response = json_decode($stringifyVerify);
        $message = "";

        if ($response->status === "success") {
            $paid = Payment::create([
                'customer_id' => $response->data->customization->title,
                'txn_id' => $response->data->reference,
                'txn_reference' => $response->data->tx_ref,
                'first_name' => $response->data->first_name,
                'last_name' => $response->data->last_name,
                'amount' => $response->data->amount,
                'currency' => $response->data->currency,
                'email' => $response->data->email,
                'status' => $response->status,
            ]);

            if ($paid) {
                $message = "yes";
                $cid = $response->data->customization->title;
                $this->upgrade($cid);
            } else {
                $message = "no";
                $customerId = $response->data->customization->title;
                $amount = $response->data->amount;
                $Currency = $response->data->currency;
                $reference = $response->data->reference;
                Storage::disk('local')->append('Paymentlogs.txt', 'The customer with ID ' . $customerId . ' and reference $reference have paid' . $amount . $Currency . 'at' . $timestamp . 'and the info did not added to database');
            }
        } else {
            Storage::disk('local')->append('Paymentlogs.txt', 'payment verification with reference number ' . $id . ' have failed');

        }
    }
    public function upgrade($cid)
    {
        $customer = Customers::find($cid);
        $dueDate = date('Y-m-d', strtotime($customer->duedate . ' +1 year'));
        $customer->update([
            'subscription' => 'annual',
            'duedate' => $dueDate,
            'status' => '1',
        ]);
    }

}