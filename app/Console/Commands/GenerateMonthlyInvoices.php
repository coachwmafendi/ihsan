<?php

namespace App\Console\Commands;

use App\Models\MonthlyInvoice;
use App\Models\Organization;
use App\Models\PlatformFee;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Stripe\Customer as StripeCustomer;
use Stripe\Invoice as StripeInvoice;
use Stripe\InvoiceItem;
use Stripe\Stripe;

#[Signature('ihsan:generate-monthly-invoices {--period= : The period to invoice for (Y-m-d format, defaults to previous month)}')]
#[Description('Generate Stripe Invoices for accumulated platform fees')]
class GenerateMonthlyInvoices extends Command
{
    public function handle(): int
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $period = $this->option('period')
            ? carbon($this->option('period'))->startOfMonth()
            : now()->subMonth()->startOfMonth();

        $this->info("Generating invoices for period: {$period->format('Y-m')}");

        $pendingFees = PlatformFee::query()
            ->where('status', 'pending')
            ->where('created_at', '>=', $period->copy()->startOfMonth())
            ->where('created_at', '<', $period->copy()->addMonth()->startOfMonth())
            ->get();

        if ($pendingFees->isEmpty()) {
            $this->info('No pending fees found for this period.');

            return Command::SUCCESS;
        }

        $feesByOrg = $pendingFees->groupBy('organization_id');
        $generated = 0;
        $skipped = 0;

        foreach ($feesByOrg as $organizationId => $fees) {
            $organization = Organization::find($organizationId);

            if ($organization === null || $organization->contact_email === null) {
                $this->warn("Skipping organization #{$organizationId}: no contact email");

                $skipped++;

                continue;
            }

            $totalFees = $fees->sum('fee_amount');

            if ($totalFees <= 0) {
                $skipped++;

                continue;
            }

            try {
                $customerParams = [
                    'email' => $organization->contact_email,
                    'name' => $organization->name,
                    'metadata' => [
                        'organization_id' => (string) $organization->id,
                    ],
                ];

                $customers = StripeCustomer::all(['email' => $organization->contact_email, 'limit' => 1]);
                $customer = $customers->first() ?? StripeCustomer::create($customerParams);

                InvoiceItem::create([
                    'customer' => $customer->id,
                    'amount' => (int) ($totalFees * 100),
                    'currency' => 'myr',
                    'description' => "Ihsan Platform Fees — {$period->format('F Y')}",
                ]);

                $stripeInvoice = StripeInvoice::create([
                    'customer' => $customer->id,
                    'collection_method' => 'send_invoice',
                    'days_until_due' => 14,
                    'description' => "Ihsan Platform Fees for {$period->format('F Y')} — {$organization->name}",
                    'metadata' => [
                        'organization_id' => (string) $organization->id,
                        'period' => $period->format('Y-m-d'),
                        'type' => 'platform_fees',
                    ],
                ]);

                $stripeInvoice->finalizeInvoice();

                $invoiceNumber = 'INV-'.$period->format('Ym').'-'.str_pad((string) ($generated + 1), 3, '0', STR_PAD_LEFT);

                $monthlyInvoice = MonthlyInvoice::create([
                    'organization_id' => $organization->id,
                    'stripe_invoice_id' => $stripeInvoice->id,
                    'invoice_number' => $invoiceNumber,
                    'period' => $period->format('Y-m-d'),
                    'total_fees' => $totalFees,
                    'stripe_status' => $stripeInvoice->status,
                    'stripe_invoice_url' => $stripeInvoice->hosted_invoice_url,
                    'stripe_invoice_pdf' => $stripeInvoice->invoice_pdf,
                ]);

                PlatformFee::whereIn('id', $fees->pluck('id'))->update([
                    'status' => 'invoiced',
                    'monthly_invoice_id' => $monthlyInvoice->id,
                ]);

                $this->info("Invoice {$invoiceNumber} sent to {$organization->name}: MYR {$totalFees}");

                $generated++;
            } catch (\Exception $e) {
                $this->error("Failed to generate invoice for {$organization->name}: {$e->getMessage()}");
            }
        }

        $this->info("Done. Generated: {$generated}, Skipped: {$skipped}");

        return Command::SUCCESS;
    }
}
