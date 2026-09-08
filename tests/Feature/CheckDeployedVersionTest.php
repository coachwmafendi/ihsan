<?php

declare(strict_types=1);

use App\Mail\DeploymentDriftAlert;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

/**
 * A push once failed to start a build with no error anywhere. The commit was on
 * main, the fix looked shipped, and production kept serving the previous one
 * until someone opened a shell on the container.
 */
beforeEach(function () {
    config([
        'services.github.repository' => 'owner/repo',
        'services.github.deploy_branch' => 'main',
        'app.admin_email' => 'ops@example.com',
    ]);
});

function fakeHeadCommit(string $sha, string $committedAt): void
{
    Http::fake([
        'api.github.com/*' => Http::response([
            'sha' => $sha,
            'commit' => [
                'message' => 'fix(checkout): something important',
                'committer' => ['date' => $committedAt],
            ],
        ]),
    ]);
}

it('reports a commit that never reached production', function () {
    Mail::fake();
    fakeHeadCommit('b'.str_repeat('2', 39), now()->subHour()->toIso8601String());

    putenv('SOURCE_COMMIT='.str_repeat('a', 40));

    $this->artisan('ihsan:check-deployed-version')->assertSuccessful();

    Mail::assertQueued(DeploymentDriftAlert::class);

    putenv('SOURCE_COMMIT');
});

it('says nothing when production is on the latest commit', function () {
    Mail::fake();

    $sha = str_repeat('c', 40);
    fakeHeadCommit($sha, now()->subHours(2)->toIso8601String());
    putenv('SOURCE_COMMIT='.$sha);

    $this->artisan('ihsan:check-deployed-version')->assertSuccessful();

    Mail::assertNothingQueued();

    putenv('SOURCE_COMMIT');
});

it('allows a deploy that is still running', function () {
    // A commit pushed a minute ago is being built, not missed.
    Mail::fake();
    fakeHeadCommit(str_repeat('d', 40), now()->subMinutes(2)->toIso8601String());
    putenv('SOURCE_COMMIT='.str_repeat('e', 40));

    $this->artisan('ihsan:check-deployed-version')->assertSuccessful();

    Mail::assertNothingQueued();

    putenv('SOURCE_COMMIT');
});

it('stays quiet when GitHub cannot be reached', function () {
    // An outage there must never page anyone about a deployment.
    Mail::fake();
    Http::fake(['api.github.com/*' => Http::response(null, 503)]);
    putenv('SOURCE_COMMIT='.str_repeat('f', 40));

    $this->artisan('ihsan:check-deployed-version')->assertSuccessful();

    Mail::assertNothingQueued();

    putenv('SOURCE_COMMIT');
});

it('does nothing outside a deployment', function () {
    Mail::fake();
    putenv('SOURCE_COMMIT');

    $this->artisan('ihsan:check-deployed-version')->assertSuccessful();

    Mail::assertNothingQueued();
});
