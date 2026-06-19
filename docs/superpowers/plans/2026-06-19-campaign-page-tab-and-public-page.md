# Campaign Page Tab & Public Campaign Page Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a Campaign Page tab to the campaign edit screen for configuring the thank-you screen and sharing, and build a public campaign landing page at `/campaigns/{public_id}`.

**Architecture:** Reuse the existing `thank_you_message` and `redirect_url` columns plus `campaigns.config` JSON for the new settings. Add a new admin tab with a left sidebar sub-navigation (only Thank you screen implemented first; others are placeholders). Render the public page using a new Livewire component that shows campaign info and embeds the existing `DonationForm` inline, so post-donation behaviour works out of the box.

**Tech Stack:** Laravel 13, Livewire 4, Flux UI, Tailwind CSS, Pest 4

---

## File Map

| File | Responsibility |
|------|----------------|
| `app/Livewire/App/Campaigns/CampaignEdit.php` | Holds new campaign-page properties and persists them on save. |
| `resources/views/livewire/app/campaigns/edit.blade.php` | Adds the Campaign Page tab, sidebar, and Thank you screen form. |
| `app/Livewire/CampaignPublicPage.php` | New public page component; resolves campaign by public_id. |
| `resources/views/livewire/campaign-public-page.blade.php` | Public campaign landing page view. |
| `resources/views/livewire/donation-form.blade.php` | Adds share buttons to the success step. |
| `routes/web.php` | Registers `/campaigns/{campaign:public_id}` route. |
| `tests/Feature/CampaignPageSettingsTest.php` | Tests for the admin Campaign Page tab. |
| `tests/Feature/CampaignPublicPageTest.php` | Tests for the public campaign landing page. |

---

### Task 1: Add Campaign Page properties to CampaignEdit

**Files:**
- Modify: `app/Livewire/App/Campaigns/CampaignEdit.php`
- Test: `tests/Feature/CampaignPageSettingsTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/CampaignPageSettingsTest.php`:

```php
<?php

use App\Models\Campaign;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

it('renders the campaign page tab', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();
    $campaign = Campaign::factory()->for($organization)->create();

    Livewire::actingAs($user)
        ->test(\App\Livewire\App\Campaigns\CampaignEdit::class, ['campaign' => $campaign, 'activeTab' => 'campaign-page'])
        ->assertOk()
        ->assertSee('Campaign Page')
        ->assertSee('Thank you screen')
        ->assertSee('Show supporters the default thank you screen')
        ->assertSee('Redirect supporters to a specific URL')
        ->assertSee('Sharing URL')
        ->assertSee('Default sharing message');
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --compact tests/Feature/CampaignPageSettingsTest.php
```

Expected: FAIL — `Campaign Page` not found.

- [ ] **Step 3: Add properties and mount initialization**

In `app/Livewire/App/Campaigns/CampaignEdit.php`, after `$redirect_url` property add:

```php
    public string $activeTab = 'overview';
    // ... existing properties ...
    public ?string $redirect_url = null;

    public string $campaignPagePanel = 'thank-you';

    public string $postDonationMode = 'default';

    /** @var string[] */
    public array $shareChannels = ['facebook', 'x', 'linkedin', 'email'];

    #[Validate('nullable|string|max:280')]
    public ?string $shareMessage = null;
```

In the `mount()` method, after `$this->redirect_url = $campaign->redirect_url;` add:

```php
        $this->postDonationMode = $campaign->config['post_donation_mode'] ?? 'default';
        $this->shareChannels = $campaign->config['share_channels'] ?? ['facebook', 'x', 'linkedin', 'email'];
        $this->shareMessage = $campaign->config['share_message'] ?? null;
```

- [ ] **Step 4: Run test to verify it passes (form render only)**

```bash
php artisan test --compact tests/Feature/CampaignPageSettingsTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Livewire/App/Campaigns/CampaignEdit.php tests/Feature/CampaignPageSettingsTest.php
git commit -m "feat(campaigns): add Campaign Page state properties and render test"
```

---

### Task 2: Persist Campaign Page settings on save

**Files:**
- Modify: `app/Livewire/App/Campaigns/CampaignEdit.php`
- Test: `tests/Feature/CampaignPageSettingsTest.php`

- [ ] **Step 1: Write the failing test**

Add to `tests/Feature/CampaignPageSettingsTest.php`:

```php
it('saves campaign page thank you and sharing settings', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create();
    $campaign = Campaign::factory()->for($organization)->create([
        'thank_you_message' => null,
        'redirect_url' => null,
        'config' => [],
    ]);

    Livewire::actingAs($user)
        ->test(\App\Livewire\App\Campaigns\CampaignEdit::class, ['campaign' => $campaign, 'activeTab' => 'campaign-page'])
        ->set('postDonationMode', 'redirect')
        ->set('thank_you_message', 'Thanks!')
        ->set('redirect_url', 'https://example.com/thanks')
        ->set('shareChannels', ['facebook', 'email'])
        ->set('shareMessage', 'Support this campaign!')
        ->call('save')
        ->assertHasNoErrors();

    $campaign->refresh();

    expect($campaign->thank_you_message)->toBe('Thanks!')
        ->and($campaign->redirect_url)->toBe('https://example.com/thanks')
        ->and($campaign->config['post_donation_mode'])->toBe('redirect')
        ->and($campaign->config['share_channels'])->toBe(['facebook', 'email'])
        ->and($campaign->config['share_message'])->toBe('Support this campaign!');
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --compact --filter="saves campaign page thank you" tests/Feature/CampaignPageSettingsTest.php
```

Expected: FAIL — config keys not persisted.

- [ ] **Step 3: Update save() to persist new config keys**

In `app/Livewire/App/Campaigns/CampaignEdit.php`, update the `$config` array merge in `save()`:

```php
        $config = array_merge($this->campaign->config ?? [], [
            'default_frequency' => $this->default_frequency,
            'default_amount' => (float) ($this->default_amount ?? 50),
            'default_currency' => $this->default_currency,
            'currency_autodetect' => $this->currency_autodetect,
            'suggested_amounts_by_currency' => $this->allSuggestedAmounts,
            'allow_cover_fee' => $this->allow_cover_fee,
            'show_comment' => $this->show_comment,
            'show_phone' => $this->show_phone,
            'post_donation_mode' => $this->postDonationMode,
            'share_channels' => $this->shareChannels,
            'share_message' => $this->shareMessage,
        ]);
```

The `thank_you_message` and `redirect_url` lines already use `$this->thank_you_message` and `$this->redirect_url` from a previous commit.

- [ ] **Step 4: Run test to verify it passes**

```bash
php artisan test --compact tests/Feature/CampaignPageSettingsTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Livewire/App/Campaigns/CampaignEdit.php tests/Feature/CampaignPageSettingsTest.php
git commit -m "feat(campaigns): persist Campaign Page settings on save"
```

---

### Task 3: Add Campaign Page tab UI

**Files:**
- Modify: `resources/views/livewire/app/campaigns/edit.blade.php`

- [ ] **Step 1: Add tab button**

Locate the nav tabs (around line 31-64) and add after Checkout Modal:

```blade
            <button type="button"
                @click="tab = 'campaign-page'"
                :class="tab === 'campaign-page' ? 'border-teal-500 text-teal-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium transition-colors">
                Campaign Page
            </button>
```

- [ ] **Step 2: Add tab content**

After the Settings tab closing `</div>` and before the Checkout Modal tab, add:

```blade
        {{-- Campaign Page Tab --}}
        <div x-show="tab === 'campaign-page'" x-cloak class="space-y-6">
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-4">
                {{-- Sidebar --}}
                <div class="lg:col-span-1">
                    <x-ui.card>
                        <nav class="flex flex-col space-y-1" aria-label="Campaign page sections">
                            <button type="button"
                                wire:click="$set('campaignPagePanel', 'thank-you')"
                                class="inline-flex w-full items-center rounded-lg px-3 py-2.5 text-left text-sm font-medium transition-colors {{ $campaignPagePanel === 'thank-you' ? 'bg-teal-50 text-teal-700' : 'text-slate-600 hover:bg-slate-50' }}">
                                Thank you screen
                            </button>
                            <button type="button"
                                class="inline-flex w-full items-center rounded-lg px-3 py-2.5 text-left text-sm font-medium text-slate-400 cursor-not-allowed"
                                disabled>
                                Content
                            </button>
                            <button type="button"
                                class="inline-flex w-full items-center rounded-lg px-3 py-2.5 text-left text-sm font-medium text-slate-400 cursor-not-allowed"
                                disabled>
                                Campaign progress
                            </button>
                            <button type="button"
                                class="inline-flex w-full items-center rounded-lg px-3 py-2.5 text-left text-sm font-medium text-slate-400 cursor-not-allowed"
                                disabled>
                                Supporter impact
                            </button>
                            <button type="button"
                                class="inline-flex w-full items-center rounded-lg px-3 py-2.5 text-left text-sm font-medium text-slate-400 cursor-not-allowed"
                                disabled>
                                Multiple designations
                            </button>
                            <button type="button"
                                class="inline-flex w-full items-center rounded-lg px-3 py-2.5 text-left text-sm font-medium text-slate-400 cursor-not-allowed"
                                disabled>
                                Benefits
                            </button>
                        </nav>
                    </x-ui.card>
                </div>

                {{-- Thank you screen panel --}}
                <div class="lg:col-span-3 space-y-6">
                    <x-ui.card title="Thank you screen" description="Choose what to show supporters after they donate.">
                        <div class="space-y-6">
                            {{-- Mode --}}
                            <div class="space-y-3">
                                <label class="flex items-start gap-3">
                                    <input type="radio" wire:model.live="postDonationMode" value="default" class="mt-1 h-4 w-4 border-slate-300 text-teal-600 focus:ring-teal-500">
                                    <div>
                                        <span class="block text-sm font-medium text-slate-900">Show supporters the default thank you screen</span>
                                        <span class="block text-xs text-slate-500">Display a thank-you message on the campaign page after a successful donation.</span>
                                    </div>
                                </label>
                                <label class="flex items-start gap-3">
                                    <input type="radio" wire:model.live="postDonationMode" value="redirect" class="mt-1 h-4 w-4 border-slate-300 text-teal-600 focus:ring-teal-500">
                                    <div>
                                        <span class="block text-sm font-medium text-slate-900">Redirect supporters to a specific URL</span>
                                        <span class="block text-xs text-slate-500">Send donors to an external thank-you page instead.</span>
                                    </div>
                                </label>
                            </div>

                            @if ($postDonationMode === 'default')
                                <div>
                                    <label for="thank_you_message" class="block text-sm font-medium text-slate-700">Thank you message</label>
                                    <textarea
                                        id="thank_you_message"
                                        wire:model="thank_you_message"
                                        rows="3"
                                        class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                        placeholder="Thank you for your generous donation!"
                                    ></textarea>
                                    @error('thank_you_message') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                            @else
                                <div>
                                    <label for="redirect_url" class="block text-sm font-medium text-slate-700">Redirect URL</label>
                                    <input
                                        type="url"
                                        id="redirect_url"
                                        wire:model="redirect_url"
                                        class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                        placeholder="https://example.com/thank-you"
                                    />
                                    @error('redirect_url') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                            @endif

                            <div class="border-t border-slate-100 pt-6">
                                <h3 class="text-sm font-medium text-slate-900">Sharing channels</h3>
                                <p class="text-xs text-slate-500">Show share buttons on the thank-you screen.</p>
                                <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" wire:model="shareChannels" value="facebook" class="h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                                        <span class="text-sm text-slate-700">Facebook</span>
                                    </label>
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" wire:model="shareChannels" value="x" class="h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                                        <span class="text-sm text-slate-700">X (Twitter)</span>
                                    </label>
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" wire:model="shareChannels" value="linkedin" class="h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                                        <span class="text-sm text-slate-700">LinkedIn</span>
                                    </label>
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" wire:model="shareChannels" value="email" class="h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                                        <span class="text-sm text-slate-700">Email</span>
                                    </label>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700">Sharing URL</label>
                                <div class="mt-1.5 flex items-center gap-2">
                                    <input
                                        type="text"
                                        readonly
                                        value="{{ route('campaigns.public', $campaign) }}"
                                        class="block w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-600 shadow-sm"
                                    />
                                    <x-ui.copy-button value="{{ route('campaigns.public', $campaign) }}" title="Copy URL" />
                                </div>
                            </div>

                            <div>
                                <label for="share_message" class="block text-sm font-medium text-slate-700">Default sharing message</label>
                                <textarea
                                    id="share_message"
                                    wire:model="shareMessage"
                                    rows="3"
                                    maxlength="280"
                                    class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                    placeholder="Support this campaign!"
                                ></textarea>
                                <p class="mt-1 text-right text-xs text-slate-500">{{ strlen($shareMessage ?? '') }}/280</p>
                                @error('shareMessage') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </x-ui.card>
                </div>
            </div>
        </div>
```

- [ ] **Step 3: Compile views and run tests**

```bash
php artisan view:cache
php artisan test --compact tests/Feature/CampaignPageSettingsTest.php
```

Expected: PASS.

- [ ] **Step 4: Commit**

```bash
git add resources/views/livewire/app/campaigns/edit.blade.php
git commit -m "feat(campaigns): add Campaign Page tab UI with thank-you screen settings"
```

---

### Task 4: Create public campaign page route and component

**Files:**
- Create: `app/Livewire/CampaignPublicPage.php`
- Create: `resources/views/livewire/campaign-public-page.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/CampaignPublicPageTest.php`

- [ ] **Step 1: Register the route**

In `routes/web.php`, near the other donation routes add:

```php
Route::livewire('/campaigns/{campaign:public_id}', \App\Livewire\CampaignPublicPage::class)->name('campaigns.public');
```

- [ ] **Step 2: Create the component class**

Create `app/Livewire/CampaignPublicPage.php`:

```php
<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Campaign')]
class CampaignPublicPage extends Component
{
    public Campaign $campaign;

    public function mount(Campaign $campaign): void
    {
        abort_if(
            ! in_array($campaign->status, [CampaignStatus::Active, CampaignStatus::Paused], true),
            404
        );

        $this->campaign = $campaign;
    }

    public function render()
    {
        $campaign = Campaign::query()->find($this->campaign->getKey());

        return view('livewire.campaign-public-page', [
            'campaign' => $campaign,
            'organization' => $campaign->organization,
            'progressPercentage' => $this->progressPercentage($campaign),
        ]);
    }

    private function progressPercentage(Campaign $campaign): int
    {
        if (! $campaign->has_target || ! $campaign->target_amount) {
            return 0;
        }

        $target = (float) $campaign->target_amount;
        $collected = (float) $campaign->collected_amount;

        if ($target <= 0) {
            return 0;
        }

        return (int) min(100, round(($collected / $target) * 100));
    }
}
```

- [ ] **Step 3: Write the failing test**

Create `tests/Feature/CampaignPublicPageTest.php`:

```php
<?php

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\Organization;

it('renders a public campaign page for an active campaign', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create([
        'title' => 'Rebuild the Surau',
        'description' => 'Help us rebuild the community surau.',
        'has_target' => true,
        'target_amount' => 100000,
        'collected_amount' => 25000,
        'status' => CampaignStatus::Active,
    ]);

    $this->get(route('campaigns.public', $campaign))
        ->assertOk()
        ->assertSee('Rebuild the Surau')
        ->assertSee('Help us rebuild the community surau')
        ->assertSee('25,000.00')
        ->assertSee('100,000.00')
        ->assertSee('Donate Now');
});

it('returns 404 for inactive campaigns', function () {
    $campaign = Campaign::factory()->create([
        'status' => CampaignStatus::Draft,
    ]);

    $this->get(route('campaigns.public', $campaign))->assertNotFound();
});
```

- [ ] **Step 4: Run test to verify it fails**

```bash
php artisan test --compact tests/Feature/CampaignPublicPageTest.php
```

Expected: FAIL — component/view missing.

- [ ] **Step 5: Create the public page view**

Create `resources/views/livewire/campaign-public-page.blade.php`:

```blade
<div class="min-h-screen bg-slate-50">
    <div class="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
            {{-- Campaign info --}}
            <div class="space-y-6">
                <div>
                    <span class="inline-flex items-center rounded-full bg-teal-50 px-2.5 py-0.5 text-xs font-medium text-teal-700">
                        {{ ucfirst($campaign->status->value) }}
                    </span>
                    <h1 class="mt-3 text-3xl font-bold tracking-tight text-slate-900">{{ $campaign->title }}</h1>
                    <p class="mt-3 text-base leading-relaxed text-slate-600">{{ $campaign->description }}</p>
                </div>

                @if ($campaign->image_path)
                    <img src="{{ Storage::disk('public')->url($campaign->image_path) }}" alt="{{ $campaign->title }}" class="w-full rounded-xl object-cover shadow-sm">
                @endif

                @if ($campaign->has_target && $campaign->target_amount)
                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium text-slate-900">{{ $organization->currency ?? 'MYR' }} {{ number_format((float) $campaign->collected_amount, 2) }}</span>
                            <span class="text-slate-500">of {{ $organization->currency ?? 'MYR' }} {{ number_format((float) $campaign->target_amount, 2) }}</span>
                        </div>
                        <div class="mt-3 h-2.5 w-full rounded-full bg-slate-100">
                            <div class="h-2.5 rounded-full bg-teal-600" style="width: {{ $progressPercentage }}%"></div>
                        </div>
                        <p class="mt-2 text-xs text-slate-500">{{ $progressPercentage }}% funded</p>
                    </div>
                @endif
            </div>

            {{-- Donation form --}}
            <div>
                <div class="rounded-xl border border-slate-200 bg-white p-1 shadow-sm">
                    <livewire:donation-form :campaign="$campaign" />
                </div>
            </div>
        </div>
    </div>
</div>
```

- [ ] **Step 6: Run test to verify it passes**

```bash
php artisan view:cache
php artisan test --compact tests/Feature/CampaignPublicPageTest.php
```

Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add app/Livewire/CampaignPublicPage.php resources/views/livewire/campaign-public-page.blade.php routes/web.php tests/Feature/CampaignPublicPageTest.php
git commit -m "feat(campaigns): add public campaign landing page"
```

---

### Task 5: Add share buttons to the donation form success step

**Files:**
- Modify: `resources/views/livewire/donation-form.blade.php`
- Test: `tests/Feature/CampaignPublicPageTest.php`

- [ ] **Step 1: Write the failing test**

Add to `tests/Feature/CampaignPublicPageTest.php`:

```php
it('shows configured share channels on public page donation success', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create([
        'status' => CampaignStatus::Active,
        'config' => [
            'share_channels' => ['facebook', 'x'],
            'share_message' => 'Please support this campaign',
        ],
    ]);

    $html = $this->get(route('campaigns.public', $campaign))
        ->assertOk()
        ->getContent();

    expect($html)
        ->toContain('ihsan:share')
        ->toContain('facebook')
        ->toContain('x')
        ->toContain('Please support this campaign');
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --compact --filter="shows configured share channels" tests/Feature/CampaignPublicPageTest.php
```

Expected: FAIL.

- [ ] **Step 3: Add share button markup to the success step**

In `resources/views/livewire/donation-form.blade.php`, locate the success step around line 576 and replace the success block with:

```blade
                        {{-- Success --}}
                        <div x-show="currentStep === 'success'" x-cloak class="py-10 text-center">
                            <div class="mx-auto mb-5 flex h-20 w-20 shrink-0 items-center justify-center rounded-full bg-emerald-100 ring-8 ring-emerald-50">
                                <svg class="size-10 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                            </div>
                            <h2 class="text-lg font-semibold text-slate-900">Thank you, <span x-text="donorName"></span>!</h2>
                            <p class="mt-1 text-sm text-slate-500">Receipt sent to <span x-text="donorEmail"></span>.</p>
                            <p class="mt-1 text-sm text-slate-500">{{ ($this->campaign ?? $this->element?->campaign)?->thank_you_message ?: $this->config('success_message', 'Thank you for your donation!') }}</p>

                            @php
                                $shareBase = ($this->campaign ?? $this->element?->campaign)?->public_id;
                                $shareUrl = $shareBase ? route('campaigns.public', $shareBase) : url()->current();
                                $shareChannels = ($this->campaign ?? $this->element?->campaign)?->config['share_channels'] ?? ['facebook', 'x', 'linkedin', 'email'];
                                $shareMessage = ($this->campaign ?? $this->element?->campaign)?->config['share_message'] ?? 'Support this campaign!';
                            @endphp

                            @if (! empty($shareChannels))
                                <div class="mt-6 flex items-center justify-center gap-3" x-data="{ shareUrl: @js($shareUrl), shareMessage: @js($shareMessage) }">
                                    @if (in_array('facebook', $shareChannels, true))
                                        <a
                                            href="#"
                                            x-on:click.prevent="window.open('https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(shareUrl), '_blank', 'width=600,height=400')"
                                            class="inline-flex items-center rounded-lg bg-slate-100 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-200"
                                        >
                                            Facebook
                                        </a>
                                    @endif
                                    @if (in_array('x', $shareChannels, true))
                                        <a
                                            href="#"
                                            x-on:click.prevent="window.open('https://x.com/intent/tweet?url=' + encodeURIComponent(shareUrl) + '&text=' + encodeURIComponent(shareMessage), '_blank', 'width=600,height=400')"
                                            class="inline-flex items-center rounded-lg bg-slate-100 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-200"
                                        >
                                            X
                                        </a>
                                    @endif
                                    @if (in_array('linkedin', $shareChannels, true))
                                        <a
                                            href="#"
                                            x-on:click.prevent="window.open('https://www.linkedin.com/sharing/share-offsite/?url=' + encodeURIComponent(shareUrl), '_blank', 'width=600,height=400')"
                                            class="inline-flex items-center rounded-lg bg-slate-100 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-200"
                                        >
                                            LinkedIn
                                        </a>
                                    @endif
                                    @if (in_array('email', $shareChannels, true))
                                        <a
                                            :href="'mailto:?subject=' + encodeURIComponent(shareMessage) + '&body=' + encodeURIComponent(shareUrl)"
                                            class="inline-flex items-center rounded-lg bg-slate-100 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-200"
                                        >
                                            Email
                                        </a>
                                    @endif
                                </div>
                            @endif

                            @if ($isPopup)
                                <button
                                    type="button"
                                    x-on:click="$dispatch('close-popup'); window.parent.postMessage({type:'ihsan:close-modal'}, '*')"
                                    class="mt-6 w-full rounded-lg bg-emerald-600 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
                                >
                                    Close
                                </button>
                            @endif
                        </div>
```

- [ ] **Step 4: Run tests**

```bash
php artisan view:cache
php artisan test --compact tests/Feature/CampaignPublicPageTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add resources/views/livewire/donation-form.blade.php tests/Feature/CampaignPublicPageTest.php
git commit -m "feat(donation): add share buttons to success screen using campaign sharing config"
```

---

### Task 6: Final verification

- [ ] **Run targeted test suites**

```bash
php artisan test --compact tests/Feature/CampaignEditTest.php tests/Feature/CampaignPageSettingsTest.php tests/Feature/CampaignPublicPageTest.php tests/Feature/DonationFormTrackingTest.php
```

Expected: PASS for all new tests; pre-existing HostedDonationFormTest failures are unrelated and should not appear here.

- [ ] **Run code style check**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Commit any style fixes**

```bash
git add -A
git commit -m "style: apply pint formatting"
```

---

## Spec Coverage Check

| Spec Requirement | Plan Task |
|---|---|
| New Campaign Page admin tab | Task 3 |
| Left sidebar sub-navigation | Task 3 |
| Thank you screen post-donation mode | Task 1-3 |
| Sharing channels, URL, message | Task 1-3, 5 |
| Public page route `/campaigns/{public_id}` | Task 4 |
| Public page campaign info + donate CTA | Task 4 |
| Public page uses post-donation settings | Task 4 (reuses DonationForm) |
| Tests | All tasks |

No placeholders remain in this plan.
