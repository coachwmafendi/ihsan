<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Jobs\SendDonorProblemReport;
use App\Mail\DonorProblemReportNotification;
use App\Mail\DonorProblemReportReceived;
use App\Models\Donor;
use App\Models\DonorEmailLog;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    config(['app.admin_email' => 'wmafendi@getihsan.my']);

    $this->organization = Organization::factory()->create(['name' => 'Masjid Test']);
    $this->donor = Donor::factory()->create(['email' => 'donor@example.test', 'name' => 'Aisyah']);

    // The organization's own admin must no longer be copied on these.
    $this->ngoAdmin = User::factory()->create([
        'organization_id' => $this->organization->getKey(),
        'email' => 'ngo-admin@example.test',
        'role' => UserRole::NgoAdmin,
    ]);
});

it('acknowledges the report to the donor who sent it', function () {
    Mail::fake();

    (new SendDonorProblemReport($this->donor, $this->organization, 'The receipt link is broken.'))->handle();

    Mail::assertQueued(DonorProblemReportReceived::class, function (DonorProblemReportReceived $mail): bool {
        return $mail->hasTo('donor@example.test')
            && $mail->reportMessage === 'The receipt link is broken.';
    });
});

it('sends the report details to the platform admin', function () {
    Mail::fake();

    (new SendDonorProblemReport($this->donor, $this->organization, 'The receipt link is broken.'))->handle();

    Mail::assertQueued(DonorProblemReportNotification::class, function (DonorProblemReportNotification $mail): bool {
        return $mail->hasTo('wmafendi@getihsan.my')
            && $mail->reportMessage === 'The receipt link is broken.'
            && $mail->donor->is($this->donor);
    });
});

it('no longer copies the organization admins', function () {
    // The report is about the donor portal, which the organization cannot fix,
    // and it carries the donor's own words to a third party.
    Mail::fake();

    (new SendDonorProblemReport($this->donor, $this->organization, 'Something is wrong.'))->handle();

    Mail::assertNotQueued(DonorProblemReportNotification::class, function (DonorProblemReportNotification $mail): bool {
        return $mail->hasTo('ngo-admin@example.test');
    });

    Mail::assertNotQueued(DonorProblemReportReceived::class, function (DonorProblemReportReceived $mail): bool {
        return $mail->hasTo('ngo-admin@example.test');
    });
});

it('lets the admin reply straight to the donor', function () {
    $mail = new DonorProblemReportNotification($this->donor, $this->organization, 'Something is wrong.');

    expect($mail->envelope()->replyTo[0]->address)->toBe('donor@example.test');
});

it('records the donor acknowledgement in the donor email log', function () {
    Mail::fake();

    (new SendDonorProblemReport($this->donor, $this->organization, 'Something is wrong.'))->handle();

    $log = DonorEmailLog::query()->where('mailable_class', DonorProblemReportReceived::class)->first();

    expect($log)->not->toBeNull()
        ->and($log->donor_id)->toBe($this->donor->getKey())
        ->and($log->organization_id)->toBe($this->organization->getKey());
});

it('does not send anything for a deleted organization', function () {
    Mail::fake();

    $this->organization->delete();

    (new SendDonorProblemReport($this->donor, $this->organization->fresh(), 'Something is wrong.'))->handle();

    Mail::assertNothingQueued();
});

it('keeps the report when no admin address is configured', function () {
    // Silently dropping it is how the old behaviour hid itself.
    Mail::fake();
    config(['app.admin_email' => null]);

    (new SendDonorProblemReport($this->donor, $this->organization, 'Something is wrong.'))->handle();

    Mail::assertQueued(DonorProblemReportReceived::class);
    Mail::assertNotQueued(DonorProblemReportNotification::class);
});
