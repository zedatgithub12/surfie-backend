<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string("fname");
            $table->string("mname");
            $table->string("lname")->nullable();
            $table->string("email");
            $table->string("phone");
            $table->string("organization")->nullable();
            $table->string("referralcode")->nullable();
            $table->integer("noreferral")->nullable();
            $table->integer("balance")->nullable();
            $table->string("password");
            $table->integer("status");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partners');
    }
};
