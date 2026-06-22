<?php

namespace App\Providers;

use App\Jobs\Middleware\ThrottleMailtrapMiddleware;
use App\Listeners\RecordDonorEmailDelivery;
use App\Listeners\SendLoginAlertEmail;
use App\Listeners\UpdateLastLoginAt;
use App\Models\Setting;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        $this->applyMailConfig();
        $this->applyStripeConfig();
        $this->throttleMailtrapSends();

        Event::listen(
            Login::class,
            UpdateLastLoginAt::class,
        );

        Event::listen(
            Login::class,
            SendLoginAlertEmail::class,
        );

        Event::listen(
            MessageSent::class,
            RecordDonorEmailDelivery::class,
        );

        Blade::directive('myrtime', function ($expression) {
            return "<?php echo myrTime({$expression}); ?>";
        });
    }

    protected function applyStripeConfig(): void
    {
        try {
            $fee = Setting::get('payment_processing_fee_percent');

            if (blank($fee)) {
                return;
            }

            config(['services.stripe.processing_fee_percent' => (float) $fee]);
        } catch (\Throwable $e) {
            // Settings table might not exist yet
        }
    }

    protected function applyMailConfig(): void
    {
        try {
            $driver = Setting::get('mail_driver');

            if (blank($driver)) {
                return;
            }

            config([
                'mail.default' => $driver,
                'mail.from.address' => Setting::get('mail_from_address'),
                'mail.from.name' => Setting::get('mail_from_name'),
                'mail.mailers.smtp.host' => Setting::get('mail_host'),
                'mail.mailers.smtp.port' => Setting::get('mail_port'),
                'mail.mailers.smtp.username' => Setting::get('mail_username'),
                'mail.mailers.smtp.password' => Setting::get('mail_password'),
                'mail.mailers.smtp.encryption' => Setting::get('mail_encryption'),
                'mail.mailers.sendmail.path' => Setting::get('sendmail_path', '/usr/sbin/sendmail -bs'),
                'services.ses.key' => Setting::get('ses_key'),
                'services.ses.secret' => Setting::get('ses_secret'),
                'services.ses.region' => Setting::get('ses_region', 'us-east-1'),
                'services.mailgun.domain' => Setting::get('mailgun_domain'),
                'services.mailgun.secret' => Setting::get('mailgun_secret'),
                'services.postmark.token' => Setting::get('postmark_token'),
            ]);
        } catch (\Throwable $e) {
            // Settings table might not exist yet (first deploy)
        }
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : Password::min(8),
        );

        RateLimiter::for('donor-magic-link', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip())
                ->response(function () use ($request) {
                    return redirect()->route('donorportal.login', $request->route('organization'))
                        ->with('error', 'Too many login attempts. Please try again in a minute.');
                });
        });

        RateLimiter::for('donor-magic-link-email', function (Request $request) {
            return Limit::perMinute(3)->by($request->input('email', $request->ip()));
        });
    }

    protected function throttleMailtrapSends(): void
    {
        Queue::before(function (JobProcessing $event): void {
            app(ThrottleMailtrapMiddleware::class)->handle(
                $event->job,
                fn () => null,
            );
        });
    }
}
