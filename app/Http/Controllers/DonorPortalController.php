<?php

namespace App\Http\Controllers;

use App\Actions\Stripe\ManageStripeSubscription;
use App\Enums\DonationStatus;
use App\Enums\ElementType;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Models\Campaign;
use App\Models\Donor;
use App\Models\Element;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;
use App\Support\Currency;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Stripe\Customer;
use Stripe\Stripe;

class DonorPortalController extends Controller
{
    private function getDonor(Organization $organization): ?Donor
    {
        $donorId = session('donor_id');
        $organizationId = session('organization_id');

        if ($donorId === null || (string) $organizationId !== (string) $organization->getKey()) {
            return null;
        }

        return Donor::query()->find($donorId);
    }

    private function scopeToOrg($query, Organization $organization)
    {
        return $query->whereHas('campaign', fn ($query) => $query->where('organization_id', $organization->getKey()));
    }

    private function getTotalGiven(Donor $donor, Organization $organization): float
    {
        return (float) $this->scopeToOrg($donor->donations(), $organization)
            ->where('status', DonationStatus::Succeeded)
            ->sum(DB::raw('COALESCE(base_amount, gross_amount)'));
    }

    private function getCurrencyBreakdown(Donor $donor, Organization $organization): array
    {
        return $this->scopeToOrg($donor->donations(), $organization)
            ->where('status', DonationStatus::Succeeded)
            ->selectRaw('currency, SUM(gross_amount) as total')
            ->groupBy('currency')
            ->pluck('total', 'currency')
            ->mapWithKeys(function ($total, $currency) {
                return [$currency => Currency::symbol($currency).' '.number_format((float) $total, 2)];
            })
            ->toArray();
    }

    /**
     * @return array{href: string, desktop: string, mobile: string}
     */
    private function donationModalUrlsFor(?Campaign $campaign): array
    {
        if ($campaign === null) {
            $home = route('home');

            return ['href' => $home, 'desktop' => $home, 'mobile' => $home];
        }

        $elements = Element::query()
            ->where('campaign_id', $campaign->getKey())
            ->where('is_active', true)
            ->get();

        foreach ([ElementType::Form, ElementType::Button, ElementType::Popup, ElementType::FloatingButton] as $type) {
            $element = $elements->firstWhere('type', $type);

            if ($element !== null) {
                return [
                    'href' => route('donations.show', ['element' => $element, 'popup' => 1]),
                    'desktop' => route('donations.show', ['element' => $element, 'popup' => 1]),
                    'mobile' => route('donations.show', $element),
                ];
            }
        }

        if (! $campaign->checkout_modal_enabled) {
            $home = route('home');

            return ['href' => $home, 'desktop' => $home, 'mobile' => $home];
        }

        return [
            'href' => route('donations.campaign-show', ['campaign' => $campaign, 'popup' => 1]),
            'desktop' => route('donations.campaign-show', ['campaign' => $campaign, 'popup' => 1]),
            'mobile' => route('donations.campaign-show', $campaign),
        ];
    }

    public function dashboard(Organization $organization)
    {
        $donor = $this->getDonor($organization);
        if ($donor === null) {
            return redirect()->route('donorportal.login', $organization);
        }

        $totalGiven = $this->getTotalGiven($donor, $organization);
        $currencyBreakdown = $this->getCurrencyBreakdown($donor, $organization);

        $activeSubscriptions = $this->scopeToOrg($donor->subscriptions(), $organization)
            ->where('status', SubscriptionStatus::Active)
            ->count();

        $activeSubscriptionsList = $this->scopeToOrg($donor->subscriptions(), $organization)
            ->where('status', SubscriptionStatus::Active)
            ->get();

        $monthlyRecurringByCurrency = $activeSubscriptionsList
            ->groupBy('currency')
            ->map(function ($subs, $currency) {
                $total = $subs->sum('amount');

                return Currency::symbol($currency).' '.number_format((float) $total, 2);
            })
            ->toArray();

        $monthlyDonations = $this->scopeToOrg($donor->donations(), $organization)
            ->where('status', DonationStatus::Succeeded)
            ->selectRaw("strftime('%Y-%m', created_at) as month, SUM(COALESCE(base_amount, gross_amount)) as total")
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->limit(12)
            ->get()
            ->reverse()
            ->values();

        $campaignBreakdown = $this->scopeToOrg($donor->donations(), $organization)
            ->where('donations.status', DonationStatus::Succeeded)
            ->join('campaigns', 'donations.campaign_id', '=', 'campaigns.id')
            ->selectRaw('campaigns.title as campaign, donations.currency, SUM(donations.gross_amount) as total')
            ->groupBy('campaigns.title', 'donations.currency')
            ->orderByDesc('total')
            ->get()
            ->groupBy('campaign')
            ->map(function ($items) {
                return $items->mapWithKeys(function ($item) {
                    $symbol = match ($item->currency) {
                        'usd' => '$',
                        'sgd' => 'S$',
                        default => 'RM',
                    };

                    return [$item->currency => $symbol.' '.number_format((float) $item->total, 2)];
                });
            });

        $campaignChartData = $this->scopeToOrg($donor->donations(), $organization)
            ->where('donations.status', DonationStatus::Succeeded)
            ->join('campaigns', 'donations.campaign_id', '=', 'campaigns.id')
            ->selectRaw('campaigns.title as campaign, SUM(donations.gross_amount) as total')
            ->groupBy('campaigns.title')
            ->orderByDesc('total')
            ->get()
            ->values();

        $recentDonations = $this->scopeToOrg($donor->donations(), $organization)
            ->where('status', DonationStatus::Succeeded)
            ->with('campaign.organization')
            ->latest()
            ->limit(5)
            ->get();
        $latestCampaign = $recentDonations->first()?->campaign;
        $donationModalUrls = $this->donationModalUrlsFor($latestCampaign);

        return view('donor.dashboard', [
            'donor' => $donor,
            'organization' => $organization,
            'donationModalUrl' => $donationModalUrls['href'],
            'donationModalDesktopUrl' => $donationModalUrls['desktop'],
            'donationModalMobileUrl' => $donationModalUrls['mobile'],
            'totalGiven' => $totalGiven,
            'currencyBreakdown' => $currencyBreakdown,
            'activeSubscriptions' => $activeSubscriptions,
            'monthlyRecurringFormatted' => $monthlyRecurringByCurrency,
            'monthlyDonations' => $monthlyDonations,
            'campaignBreakdown' => $campaignBreakdown,
            'campaignChartData' => $campaignChartData,
            'recentDonations' => $recentDonations,
        ]);
    }

    public function donations(Organization $organization)
    {
        $donor = $this->getDonor($organization);
        if ($donor === null) {
            return redirect()->route('donorportal.login', $organization);
        }

        $subscriptionFilter = request()->query('subscription');
        $subscription = null;

        $query = $this->scopeToOrg($donor->donations(), $organization)->with('campaign.organization');

        if ($subscriptionFilter !== null) {
            $subscription = $this->scopeToOrg($donor->subscriptions(), $organization)->find($subscriptionFilter);
            if ($subscription !== null) {
                $query->where('subscription_id', $subscription->getKey());
            }
        }

        $totalGiven = $this->getTotalGiven($donor, $organization);
        $currencyBreakdown = $this->getCurrencyBreakdown($donor, $organization);

        return view('donor.donations', [
            'donor' => $donor,
            'organization' => $organization,
            'totalGiven' => $totalGiven,
            'currencyBreakdown' => $currencyBreakdown,
            'donationCount' => $this->scopeToOrg($donor->donations(), $organization)
                ->where('status', DonationStatus::Succeeded)
                ->count(),
            'donations' => $query->latest()->paginate(10),
            'subscription' => $subscription,
        ]);
    }

    public function subscriptions(Organization $organization)
    {
        $donor = $this->getDonor($organization);
        if ($donor === null) {
            return redirect()->route('donorportal.login', $organization);
        }

        return view('donor.subscriptions', [
            'donor' => $donor,
            'organization' => $organization,
            'subscriptions' => $this->scopeToOrg($donor->subscriptions(), $organization)
                ->with('campaign.organization')
                ->latest()
                ->paginate(10),
        ]);
    }

    public function downloadAllReceipts(Organization $organization)
    {
        $donor = $this->getDonor($organization);
        if ($donor === null) {
            return redirect()->route('donorportal.login', $organization);
        }

        $donations = $this->scopeToOrg($donor->donations(), $organization)
            ->where('status', DonationStatus::Succeeded)
            ->with(['campaign.organization', 'donor'])
            ->latest()
            ->get();

        if ($donations->isEmpty()) {
            return redirect()->route('donorportal.donations', $organization)
                ->with('error', 'No receipts available to download.');
        }

        $filename = config('app.name').'-'.$organization->code.'-all-receipts-'.now()->format('Y-m-d').'.pdf';

        $pdf = Pdf::loadView('emails.donation-receipt-pdf-bulk', [
            'donations' => $donations,
        ]);

        return $pdf->download($filename);
    }

    public function cancelSubscription(Organization $organization, Subscription $subscription)
    {
        $donor = $this->getDonor($organization);
        if ($donor === null) {
            return redirect()->route('donorportal.login', $organization);
        }

        $subscription->loadMissing('campaign');

        if (
            $subscription->donor_id !== $donor->getKey()
            || $subscription->campaign?->organization_id !== $organization->getKey()
        ) {
            abort(403);
        }

        try {
            $immediately = request()->boolean('immediately', false);

            app(ManageStripeSubscription::class)->cancel($subscription, $immediately);

            return redirect()->route('donorportal.subscriptions', $organization)
                ->with('success', $immediately
                    ? 'Subscription cancelled immediately.'
                    : 'Subscription will cancel at the end of the billing period.');
        } catch (\Exception $e) {
            return redirect()->route('donorportal.subscriptions', $organization)
                ->with('error', 'Failed to cancel: '.$e->getMessage());
        }
    }

    public function pauseSubscription(Organization $organization, Subscription $subscription)
    {
        $donor = $this->getDonor($organization);
        if ($donor === null) {
            return redirect()->route('donorportal.login', $organization);
        }

        $subscription->loadMissing('campaign');

        if (
            $subscription->donor_id !== $donor->getKey()
            || $subscription->campaign?->organization_id !== $organization->getKey()
        ) {
            abort(403);
        }

        try {
            app(ManageStripeSubscription::class)->pause($subscription);

            return redirect()->route('donorportal.subscriptions', $organization)
                ->with('success', 'Subscription paused.');
        } catch (\Exception $e) {
            return redirect()->route('donorportal.subscriptions', $organization)
                ->with('error', 'Failed to pause: '.$e->getMessage());
        }
    }

    public function resumeSubscription(Organization $organization, Subscription $subscription)
    {
        $donor = $this->getDonor($organization);
        if ($donor === null) {
            return redirect()->route('donorportal.login', $organization);
        }

        $subscription->loadMissing('campaign');

        if (
            $subscription->donor_id !== $donor->getKey()
            || $subscription->campaign?->organization_id !== $organization->getKey()
        ) {
            abort(403);
        }

        try {
            app(ManageStripeSubscription::class)->resume($subscription);

            return redirect()->route('donorportal.subscriptions', $organization)
                ->with('success', 'Subscription resumed.');
        } catch (\Exception $e) {
            return redirect()->route('donorportal.subscriptions', $organization)
                ->with('error', 'Failed to resume: '.$e->getMessage());
        }
    }

    public function changeSubscriptionAmount(Organization $organization, Subscription $subscription)
    {
        $donor = $this->getDonor($organization);
        if ($donor === null) {
            return redirect()->route('donorportal.login', $organization);
        }

        $subscription->loadMissing('campaign');

        if (
            $subscription->donor_id !== $donor->getKey()
            || $subscription->campaign?->organization_id !== $organization->getKey()
        ) {
            abort(403);
        }

        $data = request()->validate([
            'new_amount' => 'required|numeric|min:1',
        ]);

        try {
            app(ManageStripeSubscription::class)->changeAmount(
                $subscription,
                (float) $data['new_amount'],
            );

            return redirect()->route('donorportal.subscriptions', $organization)
                ->with('success', 'Subscription amount updated.');
        } catch (\Exception $e) {
            return redirect()->route('donorportal.subscriptions', $organization)
                ->with('error', 'Failed to change amount: '.$e->getMessage());
        }
    }

    public function paymentMethodClientSecret(Organization $organization, Subscription $subscription)
    {
        $donor = $this->getDonor($organization);
        if ($donor === null) {
            abort(403);
        }

        $subscription->loadMissing('campaign');

        if (
            $subscription->donor_id !== $donor->getKey()
            || $subscription->campaign?->organization_id !== $organization->getKey()
        ) {
            abort(403);
        }

        try {
            $clientSecret = app(ManageStripeSubscription::class)->createSetupIntent($subscription);

            $org = $subscription->campaign?->organization;

            return response()->json([
                'client_secret' => $clientSecret,
                'stripe_account_id' => $org?->stripe_account_id,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function updatePaymentMethod(Organization $organization, Subscription $subscription)
    {
        $donor = $this->getDonor($organization);
        if ($donor === null) {
            abort(403);
        }

        $subscription->loadMissing('campaign');

        if (
            $subscription->donor_id !== $donor->getKey()
            || $subscription->campaign?->organization_id !== $organization->getKey()
        ) {
            abort(403);
        }

        $data = request()->validate([
            'payment_method_id' => 'required|string',
        ]);

        try {
            app(ManageStripeSubscription::class)->updatePaymentMethod(
                $subscription,
                $data['payment_method_id'],
            );

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /** @return array<string, string> */
    private static function countryOptions(): array
    {
        $codes = ['AF', 'AL', 'DZ', 'AD', 'AO', 'AG', 'AR', 'AM', 'AU', 'AT', 'AZ', 'BS', 'BH', 'BD', 'BB', 'BY', 'BE', 'BZ', 'BJ', 'BT', 'BO', 'BA', 'BW', 'BR', 'BN', 'BG', 'BF', 'BI', 'CV', 'KH', 'CM', 'CA', 'CF', 'TD', 'CL', 'CN', 'CO', 'KM', 'CG', 'CD', 'CR', 'HR', 'CU', 'CY', 'CZ', 'DK', 'DJ', 'DM', 'DO', 'EC', 'EG', 'SV', 'GQ', 'ER', 'EE', 'SZ', 'ET', 'FJ', 'FI', 'FR', 'GA', 'GM', 'GE', 'DE', 'GH', 'GR', 'GD', 'GT', 'GN', 'GW', 'GY', 'HT', 'HN', 'HU', 'IS', 'IN', 'ID', 'IR', 'IQ', 'IE', 'IL', 'IT', 'JM', 'JP', 'JO', 'KZ', 'KE', 'KI', 'KP', 'KR', 'KW', 'KG', 'LA', 'LV', 'LB', 'LS', 'LR', 'LY', 'LI', 'LT', 'LU', 'MG', 'MW', 'MY', 'MV', 'ML', 'MT', 'MH', 'MR', 'MU', 'MX', 'FM', 'MD', 'MC', 'MN', 'ME', 'MA', 'MZ', 'MM', 'NA', 'NR', 'NP', 'NL', 'NZ', 'NI', 'NE', 'NG', 'NO', 'OM', 'PK', 'PW', 'PA', 'PG', 'PY', 'PE', 'PH', 'PL', 'PT', 'QA', 'RO', 'RU', 'RW', 'KN', 'LC', 'VC', 'WS', 'SM', 'ST', 'SA', 'SN', 'RS', 'SC', 'SL', 'SG', 'SK', 'SI', 'SB', 'SO', 'ZA', 'SS', 'ES', 'LK', 'SD', 'SR', 'SE', 'CH', 'SY', 'TW', 'TJ', 'TZ', 'TH', 'TL', 'TG', 'TO', 'TT', 'TN', 'TR', 'TM', 'TV', 'UG', 'UA', 'AE', 'GB', 'US', 'UY', 'UZ', 'VU', 'VE', 'VN', 'YE', 'ZM', 'ZW'];

        $countries = [];
        foreach ($codes as $code) {
            $name = \Locale::getDisplayRegion('-'.$code, 'en');
            if ($name && $name !== $code) {
                $countries[$code] = $name;
            }
        }

        asort($countries);

        return $countries;
    }

    public function profile(Organization $organization)
    {
        $donor = $this->getDonor($organization);
        if ($donor === null) {
            return redirect()->route('donorportal.login', $organization);
        }

        $hasActiveSubscriptions = $this->scopeToOrg($donor->subscriptions(), $organization)
            ->where('status', SubscriptionStatus::Active)
            ->exists();

        return view('donor.profile', [
            'donor' => $donor,
            'organization' => $organization,
            'countries' => self::countryOptions(),
            'hasActiveSubscriptions' => $hasActiveSubscriptions,
        ]);
    }

    public function updateProfile(Organization $organization): RedirectResponse
    {
        $donor = $this->getDonor($organization);
        if ($donor === null) {
            return redirect()->route('donorportal.login', $organization);
        }

        $data = request()->validate([
            'title' => 'nullable|string|max:10',
            'name' => 'required|string|max:255',
            'occupation' => 'nullable|string|max:255',
            'email' => 'required|email|max:255|unique:donors,email,'.$donor->getKey(),
            'phone' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'address_city' => 'nullable|string|max:255',
            'address_state' => 'nullable|string|max:255',
            'address_postal_code' => 'nullable|string|max:255',
            'country' => 'nullable|string|size:2',
            'locale' => 'nullable|string|in:en,ms',
            'sync_stripe' => 'nullable|boolean',
        ]);

        $syncStripe = (bool) ($data['sync_stripe'] ?? false);

        if (request()->hasFile('photo')) {
            if ($donor->photo_path !== null) {
                Storage::disk('local')->delete($donor->photo_path);
            }

            $data['photo_path'] = request()->file('photo')->store('donor-photos', 'local');
        }

        $donor->update($data);

        if ($syncStripe && $donor->stripe_customer_id) {
            Stripe::setApiKey(config('services.stripe.secret'));

            $stripeOptions = $organization->stripe_account_id
                ? ['stripe_account' => $organization->stripe_account_id]
                : [];

            Customer::update($donor->stripe_customer_id, [
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? '',
                'address' => [
                    'line1' => $data['address_line1'] ?? '',
                    'line2' => $data['address_line2'] ?? '',
                    'city' => $data['address_city'] ?? '',
                    'state' => $data['address_state'] ?? '',
                    'postal_code' => $data['address_postal_code'] ?? '',
                    'country' => $data['country'] ?? '',
                ],
            ], $stripeOptions);
        }

        return redirect()->route('donorportal.profile', $organization)
            ->with('success', 'Profile updated successfully.');
    }

    public function reportProblem(Organization $organization): RedirectResponse
    {
        $donor = $this->getDonor($organization);
        if ($donor === null) {
            return redirect()->route('donorportal.login', $organization);
        }

        $data = request()->validate([
            'message' => 'required|string|max:5000',
        ]);

        $admins = User::query()
            ->where('organization_id', $organization->getKey())
            ->where('role', UserRole::NgoAdmin)
            ->get();

        foreach ($admins as $admin) {
            Mail::raw("A donor has reported a problem:\n\n".$data['message']."\n\n---\nDonor: {$donor->name} ({$donor->email})\nOrganization: {$organization->name}", function ($message) use ($admin, $organization) {
                $message->to($admin->email)
                    ->subject('Report a Problem — '.$organization->name)
                    ->replyTo($admin->email);
            });
        }

        return redirect()->route('donorportal.dashboard', $organization)
            ->with('success', 'Thank you for your feedback. We will review it shortly.');
    }
}
