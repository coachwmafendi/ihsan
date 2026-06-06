<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Element;
use App\Models\MonthlyInvoice;
use App\Models\Subscription;
use App\Models\User;
use App\Services\PublicIdGenerator;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

#[Signature('app:backfill-public-ids')]
#[Description('Generate public_id for all existing records across supported tables')]
class BackfillPublicIds extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $models = [
            ['label' => 'Users', 'class' => User::class],
            ['label' => 'Campaigns', 'class' => Campaign::class],
            ['label' => 'Donors', 'class' => Donor::class],
            ['label' => 'Donations', 'class' => Donation::class],
            ['label' => 'Subscriptions', 'class' => Subscription::class],
            ['label' => 'Elements', 'class' => Element::class],
            ['label' => 'Monthly Invoices', 'class' => MonthlyInvoice::class],
        ];

        foreach ($models as $model) {
            $this->backfillModel($model['label'], $model['class']);
        }

        $this->components->info('Backfill complete.');

        return self::SUCCESS;
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function backfillModel(string $label, string $modelClass): void
    {
        $query = $modelClass::whereNull('public_id');
        $count = $query->count();

        if ($count === 0) {
            $this->components->info("{$label}: no records need backfill.");

            return;
        }

        $bar = $this->output->createProgressBar($count);
        $bar->setFormat("{$label}: %current%/%max% [%bar%] %percent:3s%%");
        $bar->start();

        $query->chunkById(100, function ($records) use ($bar, $modelClass) {
            foreach ($records as $record) {
                $record->public_id = PublicIdGenerator::generate($modelClass);
                $record->saveQuietly();
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
    }
}
