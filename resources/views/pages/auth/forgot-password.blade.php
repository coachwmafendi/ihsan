<x-layouts::auth :title="__('Forgot password')">
    <div class="flex flex-col gap-7">
        <x-auth-header :title="__('Forgot password')" :description="__('Enter your email to receive a password reset link')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-5">
            @csrf

            <div class="flex flex-col gap-1.5">
                <label for="email" class="text-sm font-medium text-neutral-700">{{ __('Email address') }}</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    autocomplete="email"
                    placeholder="email@example.com"
                    class="w-full rounded-lg border border-neutral-200 bg-white px-3.5 py-2.5 text-[15px] text-neutral-900 placeholder-neutral-400 outline-none transition focus:border-neutral-400 focus:ring-2 focus:ring-neutral-900/10 @error('email') border-red-400 @enderror"
                />
                @error('email')
                    <p class="text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <button
                type="submit"
                data-test="email-password-reset-link-button"
                class="w-full rounded-lg bg-neutral-900 px-4 py-3 text-[15px] font-medium text-white transition hover:bg-neutral-800 focus:outline-none focus:ring-2 focus:ring-neutral-900 focus:ring-offset-2"
            >
                {{ __('Email password reset link') }}
            </button>
        </form>

        <p class="text-center text-sm text-neutral-500">
            <span>{{ __('Or, return to') }}</span>
            <a href="{{ route('login') }}" class="font-medium text-neutral-900 hover:underline" wire:navigate>{{ __('log in') }}</a>
        </p>
    </div>
</x-layouts::auth>
