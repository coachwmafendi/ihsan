@php
    use App\Models\Campaign;

    $campaigns = $this->getCampaigns();
    $preloadedSupporter = $this->preloadedSupporter;
@endphp

<x-filament-panels::page>
    <div class="max-w-7xl mx-auto">
        {{-- Header --}}
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-gray-950 dark:text-white">Virtual Terminal</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Use the Virtual Terminal to process an in-person or over the phone donation.
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- Left column: form --}}
            <div class="lg:col-span-2 space-y-8">
                {{-- Campaign --}}
                <section>
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white mb-3">Campaign</h2>
                    <select
                        id="campaign_id"
                        wire:model.live="formData.campaign_id"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                    >
                        <option value="">Select a campaign</option>
                        @foreach ($campaigns as $id => $title)
                            <option value="{{ $id }}">{{ $title }}</option>
                        @endforeach
                    </select>
                    @error('formData.campaign_id')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </section>

                {{-- Donation --}}
                <section>
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white mb-3">Donation</h2>
                    <div class="space-y-4">
                        <div>
                            <label for="frequency" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Donation frequency
                            </label>
                            <select
                                id="frequency"
                                wire:model.live="formData.frequency"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                            >
                                <option value="once">Once</option>
                                <option value="monthly">Monthly</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Amount
                                </label>
                                <div class="flex">
                                    <input
                                        id="amount"
                                        type="number"
                                        step="0.01"
                                        min="1"
                                        wire:model.live="formData.amount"
                                        class="flex-1 rounded-l-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                        placeholder="0.00"
                                    />
                                    <span class="inline-flex items-center rounded-r-lg border border-l-0 border-gray-300 bg-gray-50 px-3 text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                                        MYR
                                    </span>
                                </div>
                                @error('formData.amount')
                                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Supporter --}}
                <section>
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white mb-3">Supporter</h2>

                    @if ($preloadedSupporter)
                        <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 mb-4 dark:border-yellow-900 dark:bg-yellow-900/20">
                            <div class="flex items-start justify-between">
                                <div>
                                    <p class="text-xs text-yellow-800 dark:text-yellow-200">
                                        The donation will be processed for an existing supporter.
                                    </p>
                                    <p class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ $preloadedSupporter->name }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $preloadedSupporter->email }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                        ID: {{ $preloadedSupporter->public_id }}
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    wire:click="clearPreloadedSupporter"
                                    aria-label="Clear supporter"
                                    class="text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300"
                                >
                                    <x-heroicon-o-x-mark class="size-5" />
                                </button>
                            </div>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="first_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                First name
                            </label>
                            <input
                                id="first_name"
                                type="text"
                                wire:model="formData.first_name"
                                autocomplete="given-name"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                            />
                            @error('formData.first_name')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="last_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Last name
                            </label>
                            <input
                                id="last_name"
                                type="text"
                                wire:model="formData.last_name"
                                autocomplete="family-name"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                            />
                            @error('formData.last_name')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div class="mt-4">
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Receipt email
                        </label>
                        <input
                            id="email"
                            type="email"
                            wire:model="formData.email"
                            autocomplete="email"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                        />
                        @error('formData.email')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>
                </section>

                {{-- Transaction costs --}}
                <section>
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white mb-3">Transaction costs</h2>
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800">
                        <p class="text-sm text-gray-700 dark:text-gray-300">
                            Estimated costs for the selected payment method:
                            <span class="font-semibold text-gray-900 dark:text-white">{{ $this->getProcessingFeeEstimate() }}</span>
                        </p>
                    </div>
                </section>
            </div>

            {{-- Right column: summary --}}
            <div class="lg:col-span-1">
                <div class="sticky top-8 rounded-lg border border-gray-200 bg-gray-50 p-6 dark:border-gray-700 dark:bg-gray-800">
                    <h2 class="text-lg font-semibold text-gray-950 dark:text-white mb-4">Summary</h2>
                    <div class="space-y-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">
                                {{ $this->formData['frequency'] === 'monthly' ? 'Monthly donation' : 'One-time donation' }}
                            </span>
                            <span class="text-gray-900 dark:text-white">{{ $this->getTotalAmount() }}</span>
                        </div>
                        <div class="border-t border-gray-200 pt-3 flex justify-between text-sm font-semibold dark:border-gray-700">
                            <span class="text-gray-900 dark:text-white">Total donation amount</span>
                            <span class="text-gray-900 dark:text-white">{{ $this->getTotalAmount() }}</span>
                        </div>
                    </div>
                    <button
                        type="button"
                        wire:click="mountAction('processDonation')"
                        wire:loading.attr="disabled"
                        wire:target="mountAction('processDonation')"
                        @disabled(empty($this->formData['amount']) || empty($this->formData['campaign_id']))
                        class="mt-6 w-full rounded-lg bg-gray-900 px-4 py-3 text-sm font-medium text-white transition hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed dark:bg-white dark:text-gray-950 dark:hover:bg-gray-100"
                    >
                        <span wire:loading.remove wire:target="mountAction('processDonation')">Make a donation</span>
                        <span wire:loading wire:target="mountAction('processDonation')">Processing...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
