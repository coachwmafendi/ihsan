{{-- resources/views/livewire/app/stripe-onboarding.blade.php --}}
<div class="mx-auto max-w-2xl space-y-8 py-12">
    <div class="text-center">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-indigo-50">
            <x-icons.stripe class="h-8 w-auto" />
        </div>

        <h1 class="mt-6 text-3xl font-bold tracking-tight text-slate-900">
            Connect with Stripe Connect
        </h1>

        <p class="mt-4 text-slate-600">
            To start receiving donations, you need to connect your organisation with Stripe Connect.
            This allows us to securely process payments on your behalf and transfer funds directly to your account.
        </p>
    </div>

    @if (session('success'))
        <x-ui.card class="text-center">
            <p class="text-sm font-medium text-teal-700">{{ session('success') }}</p>
        </x-ui.card>
    @endif

    @if (session('error'))
        <x-ui.card class="text-center">
            <p class="text-sm font-medium text-red-700">{{ session('error') }}</p>
        </x-ui.card>
    @endif

    <x-ui.card class="text-center">
        <p class="text-sm text-slate-500 mb-6">
            Click the button below to connect with Stripe Connect. We will create a secure Stripe account for your organisation and redirect you to Stripe to complete the onboarding.
        </p>

        <button
            type="button"
            wire:click="connect"
            wire:loading.attr="disabled"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 transition disabled:opacity-50 disabled:cursor-not-allowed"
        >
            <svg wire:loading.delay wire:target="connect" class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            </svg>

            <span wire:loading.remove.delay wire:target="connect">Connect with Stripe Connect</span>
            <span wire:loading.delay wire:target="connect">Connecting…</span>
        </button>
    </x-ui.card>

    @if ($showSuccessModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-md rounded-xl bg-white p-6 text-center shadow-lg">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-teal-100">
                    <svg class="h-6 w-6 text-teal-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                </div>

                <h2 class="mt-4 text-lg font-semibold text-slate-900">Stripe Onboarding Successful</h2>

                <p class="mt-2 text-sm text-slate-600">
                    Your organisation is now connected with Stripe Connect. You can start receiving donations.
                </p>

                <button
                    type="button"
                    wire:click="closeSuccessModal"
                    class="mt-6 inline-flex items-center justify-center rounded-lg bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 transition"
                >
                    Close
                </button>
            </div>
        </div>
    @endif
</div>
