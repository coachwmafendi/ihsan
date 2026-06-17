@php
    $isImpersonating = session()->has('admin_impersonating_donor_id')
        && auth()->user()?->role === App\Enums\UserRole::NgoAdmin;
@endphp

@if ($isImpersonating)
    <div
        role="status"
        aria-live="polite"
        class="fixed inset-x-0 top-0 z-[100] flex h-14 items-center border-b border-amber-200 bg-amber-50 px-4 shadow-sm"
    >
        <div class="mx-auto flex w-full max-w-7xl items-center justify-between gap-3 py-2 md:grid md:grid-cols-3">
            {{-- Left: Warning icon + customer name --}}
            <div class="flex min-w-0 items-center gap-2 md:col-span-1">
                <svg
                    class="h-4 w-4 shrink-0 text-amber-600"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                    aria-hidden="true"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"
                    />
                </svg>

                <span class="truncate text-sm font-medium text-amber-900">
                    👤 <span class="hidden sm:inline">Viewing customer account:</span>
                    {{ session('admin_impersonating_donor_name', 'Customer') }}
                </span>
            </div>

            {{-- Center: Explanatory text --}}
            <div class="hidden text-center md:col-span-1 md:block">
                <p class="text-sm text-amber-800">
                    You are currently browsing this portal as an administrator.
                </p>
            </div>

            {{-- Right: Actions --}}
            <div class="flex shrink-0 items-center justify-end gap-2 md:col-span-1">
                @php
                    $returnPublicId = session('admin_impersonating_donor_public_id');
                @endphp

                @if ($returnPublicId)
                    <a
                        href="{{ route('app.supporters.show', $returnPublicId) }}"
                        class="hidden rounded-md px-3 py-1.5 text-sm font-medium text-amber-700 transition hover:bg-amber-100 hover:text-amber-900 sm:inline-flex"
                    >
                        Open Customer Profile
                    </a>
                @endif

                <form method="POST" action="{{ route('admin.donor-portal.exit') }}">
                    @csrf
                    <button
                        type="submit"
                        class="inline-flex items-center rounded-full bg-amber-600 px-4 py-1.5 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 focus:ring-offset-amber-50"
                    >
                        Exit Customer View
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Push page content down so bar doesn't overlap --}}
    <div class="h-14" aria-hidden="true"></div>
@endif
