<x-layouts::auth.card>
    <div class="text-center">
        <h1 class="text-xl font-semibold text-stone-900 dark:text-stone-100">Email preferences</h1>
        <p class="mt-2 text-sm text-stone-500 dark:text-stone-400">
            Manage email notifications for {{ $donor->email }}
        </p>
    </div>

    @if (session('status'))
        <div class="mt-4 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ $updateUrl }}" class="mt-6 space-y-4">
        @csrf

        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-stone-200 bg-stone-50 p-4 dark:border-stone-800 dark:bg-stone-900">
            <input type="hidden" name="opt_out" value="0">
            <input
                type="checkbox"
                name="opt_out"
                value="1"
                @checked($isOptedOut)
                class="mt-1 size-5 rounded border-stone-300 text-teal-600 focus:ring-teal-600 dark:border-stone-700"
            >
            <div class="text-sm">
                <span class="block font-medium text-stone-900 dark:text-stone-100">Don’t send me these emails anymore</span>
                <span class="block text-stone-500 dark:text-stone-400">Uncheck this to resubscribe at any time.</span>
            </div>
        </label>

        <button
            type="submit"
            class="w-full rounded-lg bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-700"
        >
            Save preference
        </button>
    </form>
</x-layouts::auth.card>
