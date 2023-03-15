<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
         \App\Models\User::factory(2)->create();
        //  \App\Models\Customers::factory(10)->create({
        //     "first_name", "middle_name","last_name", "email","phone",
        //     "living_address", "username","password", "license", "subscription", "payment_method", "date_created"
        //  });

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
