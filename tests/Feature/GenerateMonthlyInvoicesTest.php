<?php

use App\Mail\PlatformInvoiceCreated;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Organization;
use App\Models\ProcessingFee;
use Illuminate\Support\Facades\Mail;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;
use Stripe\HttpClient\CurlClient;

it('generates monthly invoices and queues email to organization', function () {
    Mail::fake();
    config(['services.stripe.secret' => 'sk_test_fake']);

    $stripeClient = new class implements ClientInterface
    {
        public int $requestCount = 0;

        public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null): array
        {
            $this->requestCount++;

            $response = match (true) {
                str_ends_with($absUrl, '/v1/customers') && $method === 'get' => [
                    'object' => 'list',
                    'data' => [],
                ],
                str_ends_with($absUrl, '/v1/customers') && $method === 'post' => [
                    'id' => 'cus_test_'.uniqid(),
                    'object' => 'customer',
                    'email' => $params['email'] ?? 'test@example.com',
                ],
                str_ends_with($absUrl, '/v1/invoices') => [
                    'id' => 'in_test_'.uniqid(),
                    'object' => 'invoice',
                    'status' => 'draft',
                    'hosted_invoice_url' => 'https://invoice.stripe.com/test',
                    'invoice_pdf' => 'https://invoice.stripe.com/test.pdf',
                ],
                str_ends_with($absUrl, '/v1/invoiceitems') => [
                    'id' => 'ii_test_'.uniqid(),
                    'object' => 'invoiceitem',
                ],
                str_contains($absUrl, '/v1/invoices/') && str_ends_with($absUrl, '/finalize') => [
                    'id' => str_replace('/finalize', '', str_replace('/v1/invoices/', '', parse_url($absUrl, PHP_URL_PATH))),
                    'object' => 'invoice',
                    'status' => 'open',
                    'hosted_invoice_url' => 'https://invoice.stripe.com/test',
                    'invoice_pdf' => 'https://invoice.stripe.com/test.pdf',
                ],
                default => throw new RuntimeException('Unexpected Stripe request: '.$method.' '.$absUrl),
            };

            return [json_encode($response), 200, []];
        }
    };

    ApiRequestor::setHttpClient($stripeClient);

    $organization = Organization::factory()->create([
        'contact_email' => 'finance@ngo.test',
        'fee_collection_method' => 'invoice',
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donation = Donation::factory()->for($campaign)->create([
        'gross_amount' => 100,
        'created_at' => now()->subMonth()->startOfMonth()->addDay(),
    ]);
    $fee = ProcessingFee::factory()->create([
        'organization_id' => $organization->id,
        'donation_id' => $donation->id,
        'fee_amount' => 2.50,
        'status' => 'pending',
        'created_at' => now()->subMonth()->startOfMonth()->addDay(),
    ]);

    try {
        $exitCode = Artisan::call('ihsan:generate-monthly-invoices', [
            '--period' => now()->subMonth()->format('Y-m-d'),
        ]);
    } finally {
        ApiRequestor::setHttpClient(CurlClient::instance());
    }

    expect($exitCode)->toBe(0);

    $fee->refresh();
    expect($fee->status)->toBe('invoiced');

    Mail::assertQueued(PlatformInvoiceCreated::class, function (PlatformInvoiceCreated $mailable) use ($organization) {
        return $mailable->invoice->organization_id === $organization->id;
    });
});
