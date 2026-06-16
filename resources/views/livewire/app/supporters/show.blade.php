{{-- resources/views/livewire/app/supporters/show.blade.php --}}
<div class="space-y-6">
    {{-- Page Header --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">{{ \Illuminate\Support\Str::title($donor->name) }}</h1>
            <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-500">
                <span class="inline-flex items-center gap-1">
                    ID {{ $donor->public_id }}
                    <x-ui.copy-button value="{{ $donor->public_id }}" size="sm" />
                </span>
                <span class="text-slate-300">·</span>
                <span>Lifetime donated MYR {{ number_format($this->totalAmount['amount'], 2) }}</span>
                <span class="text-slate-300">·</span>
                <span>Last donation {{ $this->lastDonationDate ?? '—' }}</span>
                <span class="text-slate-300">·</span>
                <span>Supporter since {{ $donor->created_at->format('M d, Y') }}</span>
            </p>
        </div>
        <div class="flex items-center gap-2 lg:hidden">
            <x-ui.button
                href="/app/virtual-terminal?vt-supporter={{ $donor->public_id }}"
                variant="outline"
                target="_blank"
            >
                <x-heroicon-o-device-phone-mobile class="size-4" />
                View in Virtual Terminal
                <x-heroicon-o-arrow-top-right-on-square class="size-4" />
            </x-ui.button>
        </div>
    </div>

    <div
        class="grid grid-cols-1 gap-6 lg:grid-cols-3"
        x-data="{ active: 'information' }"
        x-init="
            const observer = new IntersectionObserver(
                (entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            active = entry.target.id;
                        }
                    });
                },
                { rootMargin: '-20% 0px -60% 0px', threshold: 0 }
            );
            ['information', 'donations', 'recurring-plans', 'receipts'].forEach((id) => {
                const el = document.getElementById(id);
                if (el) observer.observe(el);
            });
        "
    >
        {{-- Left Column --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Information --}}
            <section id="information">
                <x-ui.card title="Information" icon="heroicon-o-user">
                    <x-slot:actions>
                        <button
                            type="button"
                            wire:click="openEditModal"
                            class="text-sm font-medium text-teal-600 hover:text-teal-700"
                        >
                            Edit
                        </button>
                    </x-slot:actions>

                    <dl class="space-y-3">
                        <div class="flex gap-4">
                            <dt class="w-36 text-sm text-slate-500">Name</dt>
                            <dd class="flex-1 text-sm font-medium text-slate-900">{{ \Illuminate\Support\Str::title($donor->name) }}</dd>
                        </div>
                        <div class="flex gap-4">
                            <dt class="w-36 text-sm text-slate-500">Email</dt>
                            <dd class="flex-1 text-sm text-slate-900">{{ $donor->email }}</dd>
                        </div>
                        <div class="flex gap-4">
                            <dt class="w-36 text-sm text-slate-500">Language</dt>
                            <dd class="flex-1 text-sm text-slate-900">{{ $this->donorLanguage ?? '—' }}</dd>
                        </div>
                        <div class="flex gap-4">
                            <dt class="w-36 text-sm text-slate-500">Phone</dt>
                            <dd class="flex-1 text-sm text-slate-900">{{ filled($donor->phone) ? $donor->phone : '—' }}</dd>
                        </div>
                        <div class="flex gap-4">
                            <dt class="w-36 text-sm text-slate-500">Mailing address</dt>
                            <dd class="flex-1 text-sm text-slate-900">{{ $this->fullAddress ?? '—' }}</dd>
                        </div>
                    </dl>
                </x-ui.card>
            </section>

            {{-- Edit Supporter Modal --}}
            <div
                x-cloak
                x-show="$wire.editing"
                x-on:keydown.escape.window="$wire.closeEditModal()"
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
            >
                <div
                    x-on:click.outside="$wire.closeEditModal()"
                    class="w-full max-w-2xl rounded-xl bg-white shadow-lg"
                >
                    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                        <h3 class="text-lg font-semibold text-slate-900">Edit supporter</h3>
                        <button
                            type="button"
                            wire:click="closeEditModal"
                            class="text-slate-400 transition hover:text-slate-600"
                        >
                            <x-heroicon-o-x-mark class="size-5" />
                        </button>
                    </div>

                    <div class="space-y-5 p-6">
                        <div class="grid grid-cols-[140px_1fr] items-center gap-4">
                            <label for="firstName" class="text-right text-sm font-medium text-slate-700">First name</label>
                            <input
                                id="firstName"
                                type="text"
                                wire:model="firstName"
                                class="rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                            />
                        </div>

                        <div class="grid grid-cols-[140px_1fr] items-center gap-4">
                            <label for="lastName" class="text-right text-sm font-medium text-slate-700">Last name</label>
                            <input
                                id="lastName"
                                type="text"
                                wire:model="lastName"
                                class="rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                            />
                        </div>

                        <div class="grid grid-cols-[140px_1fr] items-center gap-4">
                            <label for="editEmail" class="text-right text-sm font-medium text-slate-700">Email</label>
                            <input
                                id="editEmail"
                                type="email"
                                wire:model="email"
                                class="rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                            />
                        </div>

                        <div class="grid grid-cols-[140px_1fr] items-start gap-4">
                            <div></div>
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input
                                    type="checkbox"
                                    wire:model="updateRecurringPlans"
                                    class="size-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500"
                                />
                                Update recurring plans
                                <x-heroicon-o-question-mark-circle class="size-4 text-slate-400" />
                            </label>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-slate-200 px-5 py-4">
                        <x-ui.button wireClick="closeEditModal" variant="outline">Cancel</x-ui.button>
                        <x-ui.button wireClick="save" variant="primary">Save changes</x-ui.button>
                    </div>
                </div>
            </div>

            {{-- Donations --}}
            <section id="donations">
                <x-ui.card title="Donations" description="Last 10 donations">
                    @if ($this->recentDonations->isNotEmpty())
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead>
                                    <tr class="bg-slate-50">
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Date</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Amount</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Campaign</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @foreach ($this->recentDonations as $donation)
                                        <tr class="transition-colors hover:bg-slate-50">
                                            <td class="px-4 py-3 text-sm text-slate-500">
                                                {{ $donation->created_at->format('M d, Y') }}
                                            </td>
                                            <td class="px-4 py-3">
                                                <x-donation-report-amount :donation="$donation" />
                                            </td>
                                            <td class="px-4 py-3 text-sm text-slate-600">
                                                @if ($donation->campaign)
                                                    <a href="{{ route('app.campaigns.edit', $donation->campaign) }}" wire:navigate.stop class="hover:text-teal-600">
                                                        {{ $donation->campaign->title }}
                                                    </a>
                                                @else
                                                    <span class="text-slate-400">—</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3">
                                                <x-ui.badge status="{{ $donation->status->value }}" size="sm">
                                                    {{ ucfirst($donation->status->value === 'succeeded' ? 'Paid' : $donation->status->value) }}
                                                </x-ui.badge>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <x-ui.empty-state
                            icon="heroicon-o-banknotes"
                            title="No donations yet"
                            description="Donations from this donor will appear here."
                        />
                    @endif
                </x-ui.card>
            </section>

            {{-- Recurring plans --}}
            <section id="recurring-plans">
                <x-ui.card title="Recurring plans" description="Active and past recurring donations">
                    @if ($this->recentSubscriptions->isNotEmpty())
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead>
                                    <tr class="bg-slate-50">
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Amount</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Frequency</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Campaign</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @foreach ($this->recentSubscriptions as $subscription)
                                        <tr class="transition-colors hover:bg-slate-50">
                                            <td class="px-4 py-3 text-sm font-semibold text-slate-900">
                                                {{ $subscription->currency_symbol }} {{ number_format((float) $subscription->amount, 2) }}
                                            </td>
                                            <td class="px-4 py-3 text-sm text-slate-600">
                                                {{ ucfirst($subscription->interval->value) }}
                                            </td>
                                            <td class="px-4 py-3">
                                                <x-ui.badge status="{{ $subscription->status->value }}" size="sm">
                                                    {{ $subscription->status->getLabel() }}
                                                </x-ui.badge>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-slate-600">
                                                @if ($subscription->campaign)
                                                    <a href="{{ route('app.campaigns.edit', $subscription->campaign) }}" wire:navigate.stop class="hover:text-teal-600">
                                                        {{ $subscription->campaign->title }}
                                                    </a>
                                                @else
                                                    <span class="text-slate-400">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <x-ui.empty-state
                            icon="heroicon-o-arrow-path"
                            title="No recurring plans"
                            description="Recurring plans from this donor will appear here."
                        />
                    @endif
                </x-ui.card>
            </section>

            {{-- Receipts --}}
            <section id="receipts">
                <x-ui.card title="Receipts" description="Donation receipts available for download">
                    @if ($this->receiptDonations->isNotEmpty())
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead>
                                    <tr class="bg-slate-50">
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Date</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Receipt</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Amount</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Campaign</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @foreach ($this->receiptDonations as $donation)
                                        <tr class="transition-colors hover:bg-slate-50">
                                            <td class="px-4 py-3 text-sm text-slate-500">
                                                {{ $donation->created_at->format('M d, Y') }}
                                            </td>
                                            <td class="px-4 py-3 text-sm font-medium text-slate-900">
                                                <a href="{{ route('donations.receipt.download', ['donation' => $donation->public_id]) }}" target="_blank" class="inline-flex items-center gap-1.5 text-teal-600 hover:text-teal-700">
                                                    <x-heroicon-o-document-text class="size-4" />
                                                    {{ $donation->invoice_number ?? 'Receipt' }}
                                                    <x-heroicon-o-arrow-down-tray class="size-4" />
                                                </a>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-slate-900">
                                                {{ $donation->currency_symbol }} {{ number_format((float) $donation->gross_amount, 2) }}
                                            </td>
                                            <td class="px-4 py-3 text-sm text-slate-600">
                                                @if ($donation->campaign)
                                                    <a href="{{ route('app.campaigns.edit', $donation->campaign) }}" wire:navigate.stop class="hover:text-teal-600">
                                                        {{ $donation->campaign->title }}
                                                    </a>
                                                @else
                                                    <span class="text-slate-400">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <x-ui.empty-state
                            icon="heroicon-o-document-text"
                            title="No receipts yet"
                            description="Receipts for this donor will appear here once donations are made."
                        />
                    @endif
                </x-ui.card>
            </section>
        </div>

        {{-- Right Sticky Menu --}}
        <div class="hidden lg:block lg:col-span-1">
            <div class="sticky top-24 space-y-6">
                {{-- Actions --}}
                <div class="rounded-xl border border-slate-200 bg-white p-1 shadow-sm">
                    <a
                        href="/app/virtual-terminal?vt-supporter={{ $donor->public_id }}"
                        target="_blank"
                        class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                    >
                        <x-heroicon-o-currency-dollar class="size-5 text-slate-400" />
                        Make donation
                    </a>
                    @if ($this->donorPortalUrl)
                        <a
                            href="{{ $this->donorPortalUrl }}"
                            target="_blank"
                            class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                        >
                            <x-heroicon-o-face-smile class="size-5 text-slate-400" />
                            Open Donor Portal
                        </a>
                    @endif
                </div>

                {{-- Navigation --}}
                <nav class="rounded-xl border border-slate-200 bg-white p-1 shadow-sm" aria-label="Section navigation">
                    <a
                        href="#information"
                        :class="active === 'information'
                            ? 'bg-slate-100 text-slate-900'
                            : 'text-slate-600 hover:bg-slate-50'"
                        class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition"
                    >
                        <x-heroicon-o-user class="size-5 text-slate-400" />
                        Information
                    </a>
                    <a
                        href="#donations"
                        :class="active === 'donations'
                            ? 'bg-slate-100 text-slate-900'
                            : 'text-slate-600 hover:bg-slate-50'"
                        class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition"
                    >
                        <x-heroicon-o-currency-dollar class="size-5 text-slate-400" />
                        Donations
                    </a>
                    <a
                        href="#recurring-plans"
                        :class="active === 'recurring-plans'
                            ? 'bg-slate-100 text-slate-900'
                            : 'text-slate-600 hover:bg-slate-50'"
                        class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition"
                    >
                        <x-heroicon-o-arrow-path class="size-5 text-slate-400" />
                        Recurring plans
                    </a>
                    <a
                        href="#receipts"
                        :class="active === 'receipts'
                            ? 'bg-slate-100 text-slate-900'
                            : 'text-slate-600 hover:bg-slate-50'"
                        class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition"
                    >
                        <x-heroicon-o-document-text class="size-5 text-slate-400" />
                        Receipts
                    </a>
                </nav>
            </div>
        </div>
    </div>

    {{-- Back Link --}}
    <div>
        <a href="{{ route('app.supporters.index') }}" wire:navigate class="inline-flex items-center text-sm font-medium text-teal-600 hover:text-teal-700">
            <x-heroicon-o-arrow-left class="size-4 mr-1" />
            Back to donors
        </a>
    </div>
</div>
