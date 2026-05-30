@extends('donor.layout')

@section('title', 'Profile')

@section('content')
<div class="donor-wrap" x-data="{ loaded: false, editing: false }" x-init="$nextTick(() => setTimeout(() => loaded = true, 400))">
    <div class="donor-skeleton" x-show="!loaded" x-transition.opacity.duration.250ms x-cloak aria-hidden="true">
        <div class="mb-8">
            <div class="h-8 w-48 animate-pulse rounded-lg bg-slate-200"></div>
            <div class="mt-1 h-4 w-72 animate-pulse rounded bg-slate-100"></div>
        </div>
        <div class="space-y-3">
            <div class="h-64 animate-pulse rounded-xl bg-slate-100 donor-card-skeleton"></div>
            <div class="h-52 animate-pulse rounded-xl bg-slate-100 donor-card-skeleton"></div>
        </div>
    </div>
    <div>
        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-black tracking-tight text-slate-900 [letter-spacing:-0.02em]">Profile</h1>
                <p class="mt-0.5 text-xs text-slate-500">Manage your personal information and preferences.</p>
            </div>
            <template x-if="!editing">
                <button type="button" @click="editing = true"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                    <x-heroicon name="pencil-square" class="h-3.5 w-3.5" />
                    Edit
                </button>
            </template>
        </div>

        <form method="POST" action="{{ route('donorportal.profile.update', $organization) }}"
              class="space-y-4" enctype="multipart/form-data">
            @csrf

            {{-- View Mode --}}
            <template x-if="!editing">
                <div class="space-y-4">
                    <div class="rounded-xl bg-white p-5 donor-card">
                        <div class="mb-4 flex items-center gap-2">
                            <x-heroicon name="user" class="h-4 w-4 text-slate-500" />
                            <span class="text-sm font-bold text-slate-900">Personal Information</span>
                        </div>
                        <div class="flex items-center gap-6">
                            <div class="h-28 w-28 flex-shrink-0 overflow-hidden rounded-full">
                                @if ($donor->photo_url)
                                    <img src="{{ $donor->photo_url }}" class="h-full w-full rounded-full object-cover">
                                @else
                                    <div class="flex h-full w-full items-center justify-center rounded-full bg-slate-100">
                                        <x-heroicon name="user" class="h-8 w-8 text-slate-400" />
                                    </div>
                                @endif
                            </div>
                            <div>
                                <p class="text-lg font-bold text-slate-900">{{ $donor->title ? $donor->title.' ' : '' }}{{ $donor->name }}</p>
                                <p class="text-sm text-slate-500">{{ $donor->email }}</p>
                                @if ($donor->phone)
                                    <p class="text-sm text-slate-500">{{ $donor->phone }}</p>
                                @endif
                            </div>
                        </div>
                        <hr class="my-4 border-slate-100">
                        <div class="grid gap-3 sm:grid-cols-2">
                            @if ($donor->occupation)
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Occupation</p>
                                    <p class="mt-0.5 text-sm text-slate-900">{{ $donor->occupation }}</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="rounded-xl bg-white p-5 donor-card">
                        <div class="mb-4 flex items-center gap-2">
                            <x-heroicon name="map-pin" class="h-4 w-4 text-slate-500" />
                            <span class="text-sm font-bold text-slate-900">Mailing Address</span>
                        </div>
                        @php
                            $hasAddress = $donor->address_line1 || $donor->address_line2 || $donor->address_city || $donor->address_state || $donor->address_postal_code || $donor->country;
                        @endphp
                        @if ($hasAddress)
                            <div class="text-sm text-slate-900">
                                @if ($donor->address_line1)<p>{{ $donor->address_line1 }}</p>@endif
                                @if ($donor->address_line2)<p>{{ $donor->address_line2 }}</p>@endif
                                <p>{{ implode(', ', array_filter([$donor->address_city, $donor->address_state, $donor->address_postal_code])) }}</p>
                                @if ($donor->country)<p>{{ \Locale::getDisplayRegion('-'.strtoupper($donor->country), 'en') }}</p>@endif
                            </div>
                        @else
                            <p class="text-sm text-slate-400">No address on file.</p>
                        @endif
                    </div>

                    <div class="rounded-xl bg-white p-5 donor-card">
                        <div class="mb-4 flex items-center gap-2">
                            <x-heroicon name="cog-6-tooth" class="h-4 w-4 text-slate-500" />
                            <span class="text-sm font-bold text-slate-900">Preferences</span>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Language</p>
                                <p class="mt-0.5 text-sm text-slate-900">{{ $donor->locale === 'ms' ? 'Bahasa Melayu' : 'English' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            {{-- Edit Mode --}}
            <template x-if="editing">
                <div class="space-y-4">
                    <div class="rounded-xl bg-white p-5 donor-card">
                        <div class="mb-4 flex items-center gap-2">
                            <x-heroicon name="user" class="h-4 w-4 text-slate-500" />
                            <span class="text-sm font-bold text-slate-900">Personal Information</span>
                        </div>
                        <div class="mb-6 flex items-center gap-6" x-data="{ preview: null }">
                            <div class="relative h-28 w-28 flex-shrink-0 overflow-hidden rounded-full">
                                <template x-if="preview">
                                    <img :src="preview" class="h-full w-full rounded-full object-cover">
                                </template>
                                <template x-if="!preview">
                                    @if ($donor->photo_url)
                                        <img src="{{ $donor->photo_url }}" class="h-full w-full rounded-full object-cover">
                                    @else
                                        <div class="flex h-full w-full items-center justify-center rounded-full bg-slate-100">
                                            <x-heroicon name="user" class="h-8 w-8 text-slate-400" />
                                        </div>
                                    @endif
                                </template>
                            </div>
                            <div class="flex-1">
                                <label class="cursor-pointer">
                                    <span class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
                                        <x-heroicon name="camera" class="h-4 w-4" />
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
                                           class="w-full rounded-lg border border-slate-200 px-3.5 py-2.5 text-sm text-slate-900 transition focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 @error('name') border-red-300 @enderror">
                                    @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-600">Email</label>
                                <input type="email" name="email" value="{{ old('email', $donor->email) }}"
                                       class="w-full rounded-lg border border-slate-200 px-3.5 py-2.5 text-sm text-slate-900 transition focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 @error('email') border-red-300 @enderror">
                                @error('email') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-600">Phone</label>
                                <input type="tel" name="phone" value="{{ old('phone', $donor->phone) }}"
                                       class="w-full rounded-lg border border-slate-200 px-3.5 py-2.5 text-sm text-slate-900 transition focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 @error('phone') border-red-300 @enderror">
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

                    <div class="rounded-xl bg-white p-5 donor-card">
                        <div class="mb-4 flex items-center gap-2">
                            <x-heroicon name="map-pin" class="h-4 w-4 text-slate-500" />
                            <span class="text-sm font-bold text-slate-900">Mailing Address</span>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label class="mb-1 block text-xs font-semibold text-slate-600">Address Line 1</label>
                                <input type="text" name="address_line1" value="{{ old('address_line1', $donor->address_line1) }}"
                                       class="w-full rounded-lg border border-slate-200 px-3.5 py-2.5 text-sm text-slate-900 transition focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 @error('address_line1') border-red-300 @enderror">
                                @error('address_line1') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label class="mb-1 block text-xs font-semibold text-slate-600">Address Line 2</label>
                                <input type="text" name="address_line2" value="{{ old('address_line2', $donor->address_line2) }}"
                                       class="w-full rounded-lg border border-slate-200 px-3.5 py-2.5 text-sm text-slate-900 transition focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 @error('address_line2') border-red-300 @enderror">
                                @error('address_line2') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-600">City</label>
                                <input type="text" name="address_city" value="{{ old('address_city', $donor->address_city) }}"
                                       class="w-full rounded-lg border border-slate-200 px-3.5 py-2.5 text-sm text-slate-900 transition focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 @error('address_city') border-red-300 @enderror">
                                @error('address_city') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-600">State</label>
                                <input type="text" name="address_state" value="{{ old('address_state', $donor->address_state) }}"
                                       class="w-full rounded-lg border border-slate-200 px-3.5 py-2.5 text-sm text-slate-900 transition focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 @error('address_state') border-red-300 @enderror">
                                @error('address_state') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-600">Postal Code</label>
                                <input type="text" name="address_postal_code" value="{{ old('address_postal_code', $donor->address_postal_code) }}"
                                       class="w-full rounded-lg border border-slate-200 px-3.5 py-2.5 text-sm text-slate-900 transition focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 @error('address_postal_code') border-red-300 @enderror">
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

                    <div class="rounded-xl bg-white p-5 donor-card">
                        <div class="mb-4 flex items-center gap-2">
                            <x-heroicon name="cog-6-tooth" class="h-4 w-4 text-slate-500" />
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
                        <div class="rounded-xl bg-blue-50 p-5 donor-card">
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

                    <div class="flex items-center justify-end gap-3">
                        <button type="button" @click="editing = false"
                                class="rounded-lg border border-slate-200 px-5 py-2.5 text-xs font-bold text-slate-600 transition hover:bg-slate-50">
                            Cancel
                        </button>
                        <button type="submit"
                                class="inline-flex items-center gap-2 rounded-lg px-5 py-2.5 text-xs font-bold text-white shadow-sm transition hover:shadow-md bg-emerald-600">
                            <x-heroicon name="check" class="h-4 w-4" />
                            Save Changes
                        </button>
                    </div>
                </div>
            </template>
        </form>
    </div>
</div>
@endsection
