<?php

use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Mail\DonationReceipt;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Support\Facades\Storage;

it('builds donation receipt mailable with correct subject', function () {
    $organization = Organization::factory()->create(['name' => 'Test Org']);
    $campaign = Campaign::factory()->for($organization)->create(['title' => 'Test Campaign']);
    $donor = Donor::factory()->create(['name' => 'John', 'email' => 'john@test.com']);
    $donation = Donation::factory()->create([
        'campaign_id' => $campaign->getKey(),
        'donor_id' => $donor->getKey(),
        'gross_amount' => 100,
        'donor_fee_covered' => 0,
        'status' => DonationStatus::Succeeded,
        'type' => DonationType::OneTime,
    ]);

    $mailable = new DonationReceipt($donation);

    $mailable->assertHasSubject('Your Donation Receipt — Test Org');
    $mailable->assertSeeInHtml('MYR 100');
    $mailable->assertSeeInHtml('Test Campaign');
    $mailable->assertSeeInHtml('Thank you');
});

it('shows fee breakdown in donor receipt when donor covered fees', function () {
    $organization = Organization::factory()->create(['name' => 'Test Org']);
    $campaign = Campaign::factory()->for($organization)->create(['title' => 'Test Campaign']);
    $donor = Donor::factory()->create(['name' => 'John', 'email' => 'john@test.com']);
    $donation = Donation::factory()->create([
        'campaign_id' => $campaign->getKey(),
        'donor_id' => $donor->getKey(),
        'gross_amount' => 100.00,
        'donor_fee_covered' => 3.20,
        'net_amount' => 96.80,
        'status' => DonationStatus::Succeeded,
        'type' => DonationType::OneTime,
    ]);

    $mailable = new DonationReceipt($donation);

    $mailable->assertSeeInHtml('Donation');
    $mailable->assertSeeInHtml('Processing Fee');
    $mailable->assertSeeInHtml('Total Charged');
    $mailable->assertSeeInHtml('100.00'); // donation amount
    $mailable->assertSeeInHtml('3.20');   // fee
    $mailable->assertSeeInHtml('103.20'); // total charged
});

it('includes organization contact footer in donor receipt', function () {
    $organization = Organization::factory()->create([
        'name' => 'Maahad Tahfiz Muntazatut Taqwa',
        'address_line_1' => 'Lot 3234, Jalan Dengkil',
        'city' => 'Banting',
        'state' => 'Selangor',
        'postcode' => '42700',
        'country' => 'Malaysia',
        'contact_phone' => '+60123244895',
        'contact_email' => 'maahad@example.com',
        'website_url' => 'https://maahad.example.com',
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create();

    $mailable = new DonationReceipt($donation);
    $mailable->assertSeeInHtml('Maahad Tahfiz Muntazatut Taqwa');
    $mailable->assertSeeInHtml('Lot 3234, Jalan Dengkil, Banting, Selangor, 42700, Malaysia');
    $mailable->assertSeeInHtml('+60123244895');
    $mailable->assertSeeInHtml('maahad@example.com');
    $mailable->assertSeeInHtml('https://maahad.example.com');
});

it('renders receipt in donor locale when set to malay', function () {
    $organization = Organization::factory()->create(['name' => 'Test Org']);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create(['locale' => 'ms', 'name' => 'Ahmad']);
    $donation = Donation::factory()->for($campaign)->for($donor)->create();

    $mailable = new DonationReceipt($donation);

    $mailable->assertHasSubject('Resit Derma Anda — Test Org');
    $mailable->assertSeeInHtml('Terima kasih atas derma anda!');
    $mailable->assertSeeInHtml('Hi Ahmad');
    $mailable->assertSeeInHtml('Organisasi');
});

it('attaches pdf receipt for one-time donations', function () {
    $organization = Organization::factory()->create(['code' => 'testorg']);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'type' => DonationType::OneTime,
        'status' => DonationStatus::Succeeded,
        'invoice_number' => 'INV-123',
    ]);

    $mailable = new DonationReceipt($donation);
    $attachments = $mailable->attachments();

    expect($attachments)->toHaveCount(1)
        ->and($attachments[0])->toBeInstanceOf(Attachment::class)
        ->and($attachments[0]->as)->toBe('Ihsan-testorg-INV-123.pdf');
});

it('does not attach pdf receipt for recurring donations', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'type' => DonationType::Recurring,
        'status' => DonationStatus::Succeeded,
    ]);

    $mailable = new DonationReceipt($donation);

    expect($mailable->attachments())->toBeEmpty();
});

it('shows signed download receipt link for recurring donations', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'type' => DonationType::Recurring,
        'status' => DonationStatus::Succeeded,
    ]);

    $mailable = new DonationReceipt($donation);

    $mailable->assertSeeInHtml('Download Receipt');
    $mailable->assertSeeInHtml('/receipts/'.$donation->public_id);
});

it('renders the pdf receipt template with the new layout', function () {
    $organization = Organization::factory()->create([
        'name' => 'Darul Mujtaba',
        'city' => 'Johor Bahru',
        'state' => 'Johor',
        'country' => 'Malaysia',
        'contact_email' => 'info@darulmujtaba.org',
        'contact_phone' => '+60 7-XXX XXXX',
    ]);
    $campaign = Campaign::factory()->for($organization)->create(['title' => 'Tabung Lillah Darul Mujtaba 2026']);
    $donor = Donor::factory()->create(['name' => 'Yusof Musa', 'email' => 'alqurra.my@gmail.com']);
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'gross_amount' => 54.00,
        'donor_fee_covered' => 4.03,
        'currency' => 'myr',
        'status' => DonationStatus::Succeeded,
        'type' => DonationType::OneTime,
        'invoice_number' => 'RECEIPT-OQEDNW6S-2026-000028',
        'payment_method_brand' => 'visa',
        'payment_method_last4' => '4242',
    ]);

    $html = view('emails.donation-receipt-pdf', ['donation' => $donation])->render();

    expect($html)
        ->toContain('Donation Receipt')
        ->toContain('Thank you, Yusof Musa.')
        ->toContain('Darul Mujtaba')
        ->toContain('D</span>') // org initial badge
        ->toContain('RECEIPT-OQEDNW6S-2026-000028')
        ->toContain('Successful')
        ->toContain($donation->public_id) // transaction no.
        ->toContain('Tabung Lillah Darul Mujtaba 2026')
        ->toContain('Amount (MYR)')
        ->toContain('Total Paid')
        ->toContain('MYR 58.03')
        ->toContain('Johor Bahru, Johor, Malaysia')
        ->toContain('info@darulmujtaba.org');
});

it('embeds the organization logo in the pdf receipt when available', function () {
    Storage::fake('public');
    // 1x1 transparent PNG
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
    Storage::disk('public')->put('logos/org.png', $png);

    $organization = Organization::factory()->create(['name' => 'Darul Mujtaba', 'logo_path' => 'logos/org.png']);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create();

    $html = view('emails.donation-receipt-pdf', ['donation' => $donation])->render();

    expect($html)
        ->toContain('data:image/png;base64,')
        ->not->toContain('<span class="badge">'); // initial fallback suppressed
});

it('renders the pdf receipt for recurring donations with recurring labels', function () {
    $organization = Organization::factory()->create(['name' => 'Darul Mujtaba']);
    $campaign = Campaign::factory()->for($organization)->create(['title' => 'Monthly Sadaqah']);
    $donor = Donor::factory()->create(['name' => 'Yusof Musa']);
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'status' => DonationStatus::Succeeded,
        'type' => DonationType::Recurring,
    ]);

    $html = view('emails.donation-receipt-pdf', ['donation' => $donation])->render();

    expect($html)
        ->toContain('Donation Receipt')
        ->toContain('Type: Recurring donation')
        ->toContain('A receipt is issued for each successful payment.');
});

it('renders the bulk receipts pdf with one receipt per donation', function () {
    $organization = Organization::factory()->create(['name' => 'Darul Mujtaba']);
    $campaign = Campaign::factory()->for($organization)->create(['title' => 'Tabung Lillah']);
    $donor = Donor::factory()->create(['name' => 'Yusof Musa']);
    $donations = Donation::factory()->count(3)->for($campaign)->for($donor)->create([
        'status' => DonationStatus::Succeeded,
        'type' => DonationType::OneTime,
    ]);

    $html = view('emails.donation-receipt-pdf-bulk', ['donations' => $donations])->render();

    expect(substr_count($html, 'class="receipt-page"'))->toBe(3)
        ->and(substr_count($html, 'Donation Receipt'))->toBe(3)
        ->and($html)->toContain('Darul Mujtaba');

    $output = Pdf::loadView('emails.donation-receipt-pdf-bulk', ['donations' => $donations])->output();
    expect($output)->toStartWith('%PDF');
});

it('produces a valid pdf document for the receipt attachment', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'type' => DonationType::OneTime,
        'status' => DonationStatus::Succeeded,
    ]);

    $output = Pdf::loadView('emails.donation-receipt-pdf', ['donation' => $donation])->output();

    expect($output)->toStartWith('%PDF');
});

it('does not show download receipt button for one-time donations', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'type' => DonationType::OneTime,
        'status' => DonationStatus::Succeeded,
    ]);

    $mailable = new DonationReceipt($donation);

    $mailable->assertDontSeeInHtml('Download Receipt');
});
