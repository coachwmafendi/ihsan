{{-- resources/views/livewire/app/virtual-terminal.blade.php --}}
<div class="space-y-8">
    {{-- Page Header --}}
    <div>
        <h1 class="text-3xl font-bold tracking-tight text-slate-900">Virtual Terminal</h1>
        <p class="mt-1 text-sm text-slate-500">Manually record a donation for your organisation</p>
    </div>

    <form wire:submit="save" class="max-w-3xl space-y-6">
        {{-- Campaign --}}
        <x-ui.card title="Campaign" description="Select the campaign this donation belongs to">
            <div class="space-y-4">
                <div>
                    <label for="campaign_id" class="block text-sm font-medium text-slate-700">Campaign</label>
                    <select
                        id="campaign_id"
                        wire:model.live="campaign_id"
                        class="mt-1 block w-full rounded-lg border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"
                    >
                        @foreach ($this->campaigns as $id => $title)
                            <option value="{{ $id }}">{{ $title }}</option>
                        @endforeach
                    </select>
                    @error('campaign_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </x-ui.card>

        {{-- Donor Information --}}
        <x-ui.card title="Donor Information" description="Enter the donor's contact details">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="first_name" class="block text-sm font-medium text-slate-700">First name</label>
                    <input
                        type="text"
                        id="first_name"
                        wire:model="first_name"
                        class="mt-1 block w-full rounded-lg border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"
                        placeholder="e.g. Ahmad"
                    >
                    @error('first_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="last_name" class="block text-sm font-medium text-slate-700">Last name</label>
                    <input
                        type="text"
                        id="last_name"
                        wire:model="last_name"
                        class="mt-1 block w-full rounded-lg border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"
                        placeholder="e.g. Ibrahim"
                    >
                    @error('last_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
                    <input
                        type="email"
                        id="email"
                        wire:model="email"
                        class="mt-1 block w-full rounded-lg border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"
                        placeholder="donor@example.com"
                    >
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-slate-700">Phone <span class="text-slate-400">(optional)</span></label>
                    <input
                        type="text"
                        id="phone"
                        wire:model="phone"
                        class="mt-1 block w-full rounded-lg border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"
                        placeholder="+60 12-345 6789"
                    >
                    @error('phone')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </x-ui.card>

        {{-- Donation Details --}}
        <x-ui.card title="Donation Details" description="Enter the amount and payment information">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="amount" class="block text-sm font-medium text-slate-700">
                        Amount
                        <span class="text-xs text-slate-500">
                            (min {{ strtoupper($this->currency) }} {{ number_format($this->getMinimumAmount(), 2) }})
                        </span>
                    </label>
                    <input
                        type="number"
                        id="amount"
                        wire:model="amount"
                        step="0.01"
                        min="{{ $this->getMinimumAmount() }}"
                        class="mt-1 block w-full rounded-lg border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"
                        placeholder="0.00"
                    >
                    @error('amount')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="currency" class="block text-sm font-medium text-slate-700">Currency</label>
                    <select
                        id="currency"
                        wire:model="currency"
                        class="mt-1 block w-full rounded-lg border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 uppercase"
                    >
                        @foreach ($this->acceptedCurrencies as $curr)
                            <option value="{{ $curr }}">{{ strtoupper($curr) }}</option>
                        @endforeach
                    </select>
                    @error('currency')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="frequency" class="block text-sm font-medium text-slate-700">Frequency</label>
                    <select
                        id="frequency"
                        wire:model="frequency"
                        class="mt-1 block w-full rounded-lg border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"
                    >
                        <option value="one_time">One-time</option>
                        <option value="monthly">Monthly</option>
                    </select>
                    @error('frequency')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="payment_method" class="block text-sm font-medium text-slate-700">Payment method</label>
                    <select
                        id="payment_method"
                        wire:model="payment_method"
                        class="mt-1 block w-full rounded-lg border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"
                    >
                        <option value="cash">Cash</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="card">Card</option>
                        <option value="check">Check</option>
                    </select>
                    @error('payment_method')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </x-ui.card>

        {{-- Options --}}
        <x-ui.card title="Options">
            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    <input
                        type="checkbox"
                        id="cover_fee"
                        wire:model="cover_fee"
                        class="h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500"
                    >
                    <label for="cover_fee" class="text-sm text-slate-700">Donor opted to cover processing fee</label>
                </div>

                <div>
                    <label for="notes" class="block text-sm font-medium text-slate-700">Notes <span class="text-slate-400">(optional)</span></label>
                    <textarea
                        id="notes"
                        wire:model="notes"
                        rows="3"
                        class="mt-1 block w-full rounded-lg border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"
                        placeholder="Any additional notes about this donation..."
                    ></textarea>
                    @error('notes')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </x-ui.card>

        {{-- Submit --}}
        <div class="flex items-center justify-end gap-4">
            <x-ui.button type="submit" variant="primary" size="lg">
                <span wire:loading.remove wire:target="save">Record Donation</span>
                <span wire:loading wire:target="save">Recording…</span>
            </x-ui.button>
        </div>
    </form>
</div>
