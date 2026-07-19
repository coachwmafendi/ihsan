<?php

namespace App\Providers;

use App\Listeners\LogArtisanCommand;
use App\Listeners\RecordDonorEmailDelivery;
use App\Listeners\SendLoginAlertEmail;
use App\Listeners\UpdateLastLoginAt;
use App\Models\Setting;
use App\Services\GeoLocation\MaxMindGeoIp;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Http\Request;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(MaxMindGeoIp::class, function ($app): MaxMindGeoIp {
            $defaultPath = storage_path('app/maxmind/GeoLite2-City.mmdb');

            return new MaxMindGeoIp(
                $app['config']['services.maxmind.database_path'] ?: $defaultPath,
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureUrlGeneration();

        $this->applyMailConfig();
        $this->applyStripeConfig();

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

        Event::listen(
            CommandStarting::class,
            LogArtisanCommand::class,
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
                'services.mailgun.domain' => Setting::get('mailgun_domain'),
                'services.mailgun.secret' => Setting::get('mailgun_secret'),
                'services.postmark.token' => Setting::get('postmark_token'),
            ]);

            $this->applySesConfig();
        } catch (\Throwable $e) {
            // Settings table might not exist yet (first deploy)
        }
    }

    protected function applySesConfig(): void
    {
        $settings = [
            'services.ses.key' => Setting::get('ses_key'),
            'services.ses.secret' => Setting::get('ses_secret'),
            'services.ses.region' => Setting::get('ses_region'),
            'services.ses.webhook_token' => Setting::get('ses_webhook_token'),
            'services.ses.topic_arn' => Setting::get('ses_webhook_topic_arn'),
            'mail.mailers.ses.options.ConfigurationSetName' => Setting::get('ses_configuration_set'),
        ];

        foreach ($settings as $key => $value) {
            if (! blank($value)) {
                config([$key => $value]);
            }
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

    /**
     * Ensure queued/console URL generation uses the configured APP_URL
     * so email links don't fall back to http://localhost.
     */
    protected function configureUrlGeneration(): void
    {
        if (app()->runningInConsole()) {
            URL::forceRootUrl(config('app.url'));
        }
    }
}
