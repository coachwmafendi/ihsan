<?php

use App\Http\Controllers\DonorNotificationController;
use App\Mail\CampaignCompletedDonorNotification;
use App\Models\Campaign;
use App\Models\Donor;
use Illuminate\Support\Facades\URL;

it('renders the donor notification preferences page with a valid signed url', function () {
    $donor = Donor::factory()->create();

    $url = DonorNotificationController::unsubscribeUrl($donor);

    $this->get($url)
        ->assertOk()
        ->assertSee('Email preferences')
        ->assertSee($donor->email)
        ->assertSee('Don’t send me these emails anymore');
});

it('rejects the preferences page without a valid signature', function () {
    $donor = Donor::factory()->create();

    $this->get(route('donor.notifications.edit', $donor))
        ->assertForbidden();
});

it('opts a donor out of emails', function () {
    $donor = Donor::factory()->create();

    $url = URL::signedRoute('donor.notifications.update', ['donor' => $donor]);

    $this->post($url, ['opt_out' => '1'])
        ->assertRedirect();

    expect($donor->fresh()->hasOptedOutOfEmails())->toBeTrue();
});

it('opts a donor back in to emails', function () {
    $donor = Donor::factory()->create(['email_opt_out_at' => now()]);

    $url = URL::signedRoute('donor.notifications.update', ['donor' => $donor]);

    $this->post($url, ['opt_out' => '0'])
        ->assertRedirect();

    expect($donor->fresh()->hasOptedOutOfEmails())->toBeFalse();
});

it('includes an unsubscribe link in donor campaign completed emails', function () {
    $donor = Donor::factory()->create();
    $campaign = Campaign::factory()->create();

    $mail = new CampaignCompletedDonorNotification($campaign, $donor);
    $content = $mail->content();

    expect($content->with['donor'])->toBe($donor);
    expect($content->with['unsubscribeUrl'])->toContain('/donor/notifications/'.$donor->public_id);
});
