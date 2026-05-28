<?php

namespace App\Providers;

use App\Listeners\UpdateLastLoginAt;
use App\Models\Setting;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
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

        if (session()->has('locale')) {
            app()->setLocale(session('locale'));
        }

        $this->applyMailConfig();
        $this->applyStripeConfig();

        Event::listen(
            Login::class,
            UpdateLastLoginAt::class,
        );
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

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );

        RateLimiter::for('donor-magic-link', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip())->response(function () use ($request) {
                return redirect()->route('donorportal.login', $request->route('organization'))
                    ->with('error', 'Too many login attempts. Please try again in a minute.');
            });
        });
    }
}
