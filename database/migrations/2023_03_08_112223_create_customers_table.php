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
        Schema::create('customers', function (Blueprint $table) {

            $table->id();
            $table->string("first_name");
            $table->string("middle_name")->nullable();
            $table->string("last_name")->nullable();
            $table->string("email");
            $table->string("phone");
            $table->string("living_address")->nullable();
            $table->string("username");
            $table->string("password");
            $table->integer("license");
            $table->string("subscription");
            $table->integer("payment_method");
            $table->integer("status")->nullable();
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
