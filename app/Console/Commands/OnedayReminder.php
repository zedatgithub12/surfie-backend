<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Mail\onedReminder;
use App\Models\Customers;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class OnedayReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:oneday-reminder';

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
        $dueDate = Carbon::now()->addDays(1); // due date is tommorow
        $customers = Customers::where('duedate', $dueDate)->get();

             if ($customers->count() > 0) {

             foreach ($customers as $customer) {

                 Mail::to($customer->email)->send(new onedReminder($customer));

             }
             }
    }
}
