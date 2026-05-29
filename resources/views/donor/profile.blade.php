@extends('donor.layout')

@section('title', 'Profile')

@section('content')
<div class="donor-wrap" x-data="{ loaded: false }" x-init="$nextTick(() => setTimeout(() => loaded = true, 400))">
    <div class="donor-skeleton" x-show="!loaded" x-transition.opacity.duration.250ms x-cloak aria-hidden="true">
        <div class="mb-8">
            <div class="h-8 w-48 animate-pulse rounded-lg bg-slate-200"></div>
            <div class="mt-1 h-4 w-72 animate-pulse rounded bg-slate-100"></div>
        </div>
        <div class="space-y-3">
            <div class="h-64 animate-pulse rounded-xl bg-slate-100" style="border:1.5px solid transparent;"></div>
            <div class="h-52 animate-pulse rounded-xl bg-slate-100" style="border:1.5px solid transparent;"></div>
        </div>
    </div>
    <div>
        <div class="mb-8">
            <h1 class="text-2xl font-black tracking-tight text-slate-900 [letter-spacing:-0.02em]">Profile</h1>
            <p class="mt-0.5 text-xs text-slate-500">Manage your personal information and preferences.</p>
        </div>

        <form method="POST" action="{{ route('donorportal.profile.update', $organization) }}" class="space-y-4" enctype="multipart/form-data">
            @csrf

            {{-- Personal Information --}}
            <div class="rounded-xl bg-white p-5" style="border:1.5px solid #e2e8f0;">
                <div class="mb-4 flex items-center gap-2">
                    <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                    </svg>
                    <span class="text-sm font-bold text-slate-900">Personal Information</span>
                </div>
                <div class="mb-6 flex items-center gap-5" x-data="{ preview: null }">
                    <div class="relative h-20 w-20 flex-shrink-0 overflow-hidden rounded-full">
                        <template x-if="preview">
                            <img :src="preview" class="h-full w-full rounded-full object-cover">
                        </template>
                        <template x-if="!preview">
                            @if ($donor->photo_url)
                                <img src="{{ $donor->photo_url }}" class="h-full w-full rounded-full object-cover">
                            @else
                                <div class="flex h-full w-full items-center justify-center rounded-full bg-slate-100">
                                    <svg class="h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                    </svg>
                                </div>
                            @endif
                        </template>
                    </div>
                    <div class="flex-1">
                        <label class="cursor-pointer">
                            <span class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z" />
                                </svg>
                                Upload Photo
                            </span>
                            <input type="file" name="photo" accept="image/jpeg,image/png,image/webp"
                                   class="hidden"
                                   @change="preview = URL.createObjectURL($event.target.files[0])">
                        </label>
                        <p class="mt-1 text-[10px] text-slate-400">JPEG, PNG or WebP. Max 2MB.</p>
                        @error('photo') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2 flex gap-4">
                        <div class="w-28 flex-shrink-0">
                            <label class="mb-1 block text-xs font-semibold text-slate-600">Title</label>
                            <select name="title"
                                    class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm text-slate-900 transition focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 @error('title') border-red-300 @enderror">
                                <option value="">Select</option>
                                @foreach (['Mr', 'Mrs', 'Ms', 'Miss', 'Dr', 'Prof', 'Sir', 'Madam'] as $t)
                                    <option value="{{ $t }}" {{ old('title', $donor->title) === $t ? 'selected' : '' }}>{{ $t }}</option>
                                @endforeach
                            </select>
                            @error('title') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div class="flex-1">
                            <label class="mb-1 block text-xs font-semibold text-slate-600">Name</label>
                            <input type="text" name="name" value="{{ old('name', $donor->name) }}"
                                   class="w-full rounded-lg border border-slate-200 px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 @error('name') border-red-300 @enderror"
                                   placeholder="Your full name">
                            @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-600">Email</label>
                        <input type="email" name="email" value="{{ old('email', $donor->email) }}"
                               class="w-full rounded-lg border border-slate-200 px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 @error('email') border-red-300 @enderror"
                               placeholder="your@email.com">
                        @error('email') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-600">Phone</label>
                        <input type="tel" name="phone" value="{{ old('phone', $donor->phone) }}"
                               class="w-full rounded-lg border border-slate-200 px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 @error('phone') border-red-300 @enderror"
                               placeholder="+60 12-345 6789">
                        @error('phone') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-xs font-semibold text-slate-600">Occupation</label>
                        <select name="occupation"
                                class="w-full rounded-lg border border-slate-200 px-3.5 py-2.5 text-sm text-slate-900 transition focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 @error('occupation') border-red-300 @enderror">
                            <option value="">Select</option>
                            @foreach (['Employed', 'Self-employed', 'Business owner', 'Student', 'Retired', 'Unemployed', 'Other'] as $o)
                                <option value="{{ $o }}" {{ old('occupation', $donor->occupation) === $o ? 'selected' : '' }}>{{ $o }}</option>
                            @endforeach
                        </select>
                        @error('occupation') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- Address --}}
            <div class="rounded-xl bg-white p-5" style="border:1.5px solid #e2e8f0;">
                <div class="mb-4 flex items-center gap-2">
                    <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                    </svg>
                    <span class="text-sm font-bold text-slate-900">Mailing Address</span>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-xs font-semibold text-slate-600">Address Line 1</label>
                        <input type="text" name="address_line1" value="{{ old('address_line1', $donor->address_line1) }}"
                               class="w-full rounded-lg border border-slate-200 px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 @error('address_line1') border-red-300 @enderror"
                               placeholder="Street address, P.O. box">
                        @error('address_line1') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-xs font-semibold text-slate-600">Address Line 2</label>
                        <input type="text" name="address_line2" value="{{ old('address_line2', $donor->address_line2) }}"
                               class="w-full rounded-lg border border-slate-200 px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 @error('address_line2') border-red-300 @enderror"
                               placeholder="Apartment, suite, unit, building, floor">
                        @error('address_line2') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-600">City</label>
                        <input type="text" name="address_city" value="{{ old('address_city', $donor->address_city) }}"
                               class="w-full rounded-lg border border-slate-200 px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 @error('address_city') border-red-300 @enderror"
                               placeholder="Kuala Lumpur">
                        @error('address_city') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-600">State</label>
                        <input type="text" name="address_state" value="{{ old('address_state', $donor->address_state) }}"
                               class="w-full rounded-lg border border-slate-200 px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 @error('address_state') border-red-300 @enderror"
                               placeholder="Wilayah Persekutuan">
                        @error('address_state') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-600">Postal Code</label>
                        <input type="text" name="address_postal_code" value="{{ old('address_postal_code', $donor->address_postal_code) }}"
                               class="w-full rounded-lg border border-slate-200 px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 @error('address_postal_code') border-red-300 @enderror"
                               placeholder="50480">
                        @error('address_postal_code') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-600">Country</label>
                        <select name="country"
                                class="w-full rounded-lg border border-slate-200 px-3.5 py-2.5 text-sm text-slate-900 transition focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 @error('country') border-red-300 @enderror">
                            <option value="">Select a country</option>
                            @foreach ($countries as $code => $name)
                                <option value="{{ $code }}" {{ old('country', $donor->country) === $code ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('country') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- Preferences --}}
            <div class="rounded-xl bg-white p-5" style="border:1.5px solid #e2e8f0;">
                <div class="mb-4 flex items-center gap-2">
                    <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span class="text-sm font-bold text-slate-900">Preferences</span>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-600">Language</label>
                        <select name="locale"
                                class="w-full rounded-lg border border-slate-200 px-3.5 py-2.5 text-sm text-slate-900 transition focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 @error('locale') border-red-300 @enderror">
                            <option value="en" {{ old('locale', $donor->locale) === 'en' ? 'selected' : '' }}>English</option>
                            <option value="ms" {{ old('locale', $donor->locale) === 'ms' ? 'selected' : '' }}>Bahasa Melayu</option>
                        </select>
                        @error('locale') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            @if ($hasActiveSubscriptions)
<div class="rounded-xl bg-blue-50 p-5" style="border:1.5px solid #e2e8f0;">
                    <div class="flex items-start gap-3">
                        <input type="checkbox" name="sync_stripe" id="sync_stripe" value="1"
                               class="mt-0.5 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        <div>
                            <label for="sync_stripe" class="text-sm font-bold text-slate-900 cursor-pointer">Update recurring plans</label>
                            <p class="mt-0.5 text-xs text-slate-500">Applies changes to all your active recurring plans.</p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="flex justify-end">
                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-xl px-6 py-2.5 text-sm font-bold text-white shadow-sm transition hover:shadow-md"
                        style="background:#0d9488;">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
