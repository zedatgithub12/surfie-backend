<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Mail\Remainder;
use App\Models\Customers;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class Remindermail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:remindermail';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {       
        $dueDate = Carbon::now()->addDays(7); // due date is 7 days from now
        $customers = Customers::where('duedate', $dueDate)->get();

             if ($customers->count() > 0) {

             foreach ($customers as $customer) {

                 Mail::to($customer->email)->send(new Remainder($customer));

             }
             }
    }
}