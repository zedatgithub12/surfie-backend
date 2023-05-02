<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customers extends Model
{
    use HasFactory;
    
    protected $fillable = ["remote_id","first_name", "middle_name","last_name", "email","phone",
    "living_address", "username","password", "license", "subscription","duedate", "payment_method", "coupon", "referralcode", "status"];
}
