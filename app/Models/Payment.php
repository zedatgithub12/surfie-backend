<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;
    protected $fillable= ["customer_id","txn_id", "txn_reference","first_name","last_name","amount","currency","email","status"];
}
