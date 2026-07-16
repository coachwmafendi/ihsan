<?php

use App\Mail\RefundNotification;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;

test('refund notification displays supporter details and public ids', function () {
    $organization = Organization::factory()->create(['name' => 'Darul Mujtaba']);
    $campaign = Campaign::factory()->for($organization)->create(['title' => 'Tabung Lillah Darul Mujtaba']);
    $donor = Donor::factory()->create(['name' => 'Hajan Amanina Zamam']);

    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'currency' => 'myr',
        'gross_amount' => 300.00,
    ]);

    $mailable = new RefundNotification($donation, 'MYR 300.00');

    expect($mailable->envelope()->subject)
        ->toBe('Donation Refunded — '.config('app.name'));

    $html = $mailable->render();

    expect($html)
        ->toContain('Donation Refunded')
        ->toContain('Hi <strong>Darul Mujtaba</strong>')
        ->toContain('MYR 300.00')
        ->toContain('Hajan Amanina Zamam')
        ->toContain('Supporter')
        ->toContain('Supporter ID')
        ->toContain($donor->public_id)
        ->toContain('Donation ID')
        ->toContain($donation->public_id)
        ->toContain('Tabung Lillah Darul Mujtaba')
        ->toContain('View in '.config('app.name'))
        ->toContain('app/donations/'.$donation->public_id)
        ->not->toContain('>Donor<')
        ->not->toContain('Original Donation');
});
