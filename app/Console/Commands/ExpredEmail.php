<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Mail\expired;
use App\Models\Customers;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class ExpiredEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:expired-email';

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
        $yesterday = Carbon::yesterday(); // due date is 7 days from now
        $customers = Customers::where('duedate', $yesterday)->get();

             if ($customers->count() > 0) {

             foreach ($customers as $customer) {
                $customer->update([
                    'status'=>'2',
                ]);
                 Mail::to($customer->email)->send(new expired($customer));

             }
    }
}
}