<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PaymentController extends Controller
{
    //
    protected $publicKey;
    protected $secretKey;
    protected $baseUrl;
    protected $secretHash;

    
    function __construct()
    {
        
        $this->publicKey = env('CHAPA_PUBLIC_KEY');
        $this->secretKey = env('CHAPA_SECRET_KEY');
        $this->secretHash = env('CHAPA_WEBHOOK_SECRET');
        $this->baseUrl = 'https://api.chapa.co/v1';
        
    }    
    public static function generateReference(String $transactionPrefix = NULL)
    {
        if ($transactionPrefix) {
            return $transactionPrefix . '_' . uniqid(time());
        }
        
        return 'chapa_' . uniqid(time());
    }

    /**
     * Reaches out to Chapa to initialize a payment
     * @param $data
     * @return object
     */

    public function initializePayment(array $data)
    {
        
        $payment = Http::withToken($this->secretKey)->post(
            $this->baseUrl . '/transaction/initialize',
            $data
        )->json();

       return $payment;
    }

    
    /**
     * Reaches out to Chapa to verify a transaction
     * @param $id
     * @return object
     */
    public function verifyTransaction($id)
    {
        $data =  Http::withToken($this->secretKey)->get($this->baseUrl . "/transaction/" . 'verify/'. $id )->json();
        return $data;
    }

}
