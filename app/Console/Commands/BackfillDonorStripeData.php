<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:backfill-donor-stripe-data')]
#[Description('Command description')]
class BackfillDonorStripeData extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
    }
}
