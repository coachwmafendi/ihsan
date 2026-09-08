<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\DeploymentDriftAlert;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Report when production is not running the latest commit on the deploy branch.
 *
 * A push once failed to trigger a build with no error anywhere: the fix looked
 * shipped, the code was on main, and production quietly kept serving the
 * previous commit. It was only found by opening a shell on the container. This
 * asks the question on a schedule instead.
 */
class CheckDeployedVersion extends Command
{
    protected $signature = 'ihsan:check-deployed-version {--grace=20 : Minutes a deploy is allowed to take before it counts as missed}';

    protected $description = 'Report when production is not running the latest commit on the deploy branch';

    public function handle(): int
    {
        $deployed = (string) env('SOURCE_COMMIT', '');

        if ($deployed === '') {
            // Local and CI have no deployment to compare against.
            $this->info('No SOURCE_COMMIT in the environment; nothing to compare.');

            return self::SUCCESS;
        }

        $repository = (string) config('services.github.repository');

        if (blank($repository)) {
            $this->warn('services.github.repository is not set; cannot check the deployed version.');

            return self::SUCCESS;
        }

        $latest = $this->latestCommitOnBranch($repository);

        if ($latest === null) {
            // A GitHub outage must not page anyone about a deployment.
            $this->warn('Could not read the latest commit; skipping this check.');

            return self::SUCCESS;
        }

        if (str_starts_with($latest['sha'], $deployed) || str_starts_with($deployed, $latest['sha'])) {
            $this->info('Production is running the latest commit.');

            return self::SUCCESS;
        }

        // A deploy in flight is not a missed one. Measured from the commit
        // forward: the other way round Carbon returns a negative, and every
        // commit looked like it was still being built.
        $committedMinutesAgo = $latest['committed_at']->diffInMinutes(now());

        if ($committedMinutesAgo < (int) $this->option('grace')) {
            $this->info('A newer commit exists but is still within the deploy window.');

            return self::SUCCESS;
        }

        $this->warn('Production is behind '.$this->branch().': running '.substr($deployed, 0, 8).', latest is '.substr($latest['sha'], 0, 8).'.');

        $adminEmail = config('app.admin_email');

        if (blank($adminEmail)) {
            Log::warning('Production is behind the deploy branch but app.admin_email is not set.', [
                'deployed' => $deployed,
                'latest' => $latest['sha'],
            ]);

            return self::SUCCESS;
        }

        Mail::to($adminEmail)->queue(new DeploymentDriftAlert(
            deployed: $deployed,
            latest: $latest['sha'],
            latestMessage: $latest['message'],
            committedAt: $latest['committed_at'],
            branch: $this->branch(),
        ));

        return self::SUCCESS;
    }

    /**
     * @return array{sha: string, message: string, committed_at: Carbon}|null
     */
    private function latestCommitOnBranch(string $repository): ?array
    {
        $token = (string) config('services.github.token');

        $request = Http::timeout(15)->acceptJson();

        if ($token !== '') {
            $request = $request->withToken($token);
        }

        try {
            $response = $request->get("https://api.github.com/repos/{$repository}/commits/".$this->branch());
        } catch (\Throwable $e) {
            Log::warning('Could not reach GitHub to check the deployed version.', ['error' => $e->getMessage()]);

            return null;
        }

        if (! $response->successful()) {
            Log::warning('GitHub refused the deployed-version check.', ['status' => $response->status()]);

            return null;
        }

        return [
            'sha' => (string) $response->json('sha'),
            'message' => (string) $response->json('commit.message'),
            'committed_at' => Carbon::parse($response->json('commit.committer.date')),
        ];
    }

    private function branch(): string
    {
        return (string) config('services.github.deploy_branch', 'main');
    }
}
