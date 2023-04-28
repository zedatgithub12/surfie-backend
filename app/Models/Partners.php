<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Partners extends Model
{
    use HasFactory;
    protected  $fillable = ["fname","mname","lname","email","phone","organization","referralcode","noreferral","balance","password","status"];
}
