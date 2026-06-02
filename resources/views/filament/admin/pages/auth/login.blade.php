<div>
    <div class="min-h-screen flex">
        {{-- Left: Login Form --}}
        <div class="w-full lg:w-1/2 flex flex-col justify-center items-center px-8 py-12" style="background-color: #f7f2ea;">
            <div class="w-full max-w-sm">
                {{-- Logo --}}
                <div class="flex justify-center mb-8">
                    <img src="{{ asset('logo-ihsan.png') }}" alt="ihsan" class="h-10">
                </div>

                {{-- Heading --}}
                <div class="text-center mb-8">
                    <h1 style="font-size: 1.5rem; font-weight: 600; color: #251811;">Sign in</h1>
                    <p style="font-size: 0.875rem; color: #75685d; margin-top: 4px;">Admin Panel</p>
                </div>

                {{-- Form: override Filament label colors for light background --}}
                <style>
                    .ihsan-admin-login-form .fi-fo-field-label-content,
                    .ihsan-admin-login-form .fi-checkbox-input + span,
                    .ihsan-admin-login-form label { color: #251811 !important; }
                    .ihsan-admin-login-form .fi-fo-field-label-required-mark { color: #dc2626 !important; }
                    .ihsan-admin-login-form .fi-checkbox-input {
                        border: 2px solid #75685d !important;
                        border-radius: 5px !important;
                        width: 1.15rem !important;
                        height: 1.15rem !important;
                        background-color: #fff !important;
                    }
                    .ihsan-admin-login-form .fi-checkbox-input:focus {
                        box-shadow: 0 0 0 3px rgba(182, 95, 58, 0.15) !important;
                        border-color: #b65f3a !important;
                    }
                    .ihsan-admin-login-form .fi-checkbox-input:checked {
                        background-color: #b65f3a !important;
                        border-color: #b65f3a !important;
                    }
                    .ihsan-admin-login-form .fi-fo-text-input,
                    .ihsan-admin-login-form input[type="email"],
                    .ihsan-admin-login-form input[type="password"] {
                        border-color: #d1c7bc !important;
                    }
                    .ihsan-admin-login-form .fi-fo-text-input:focus,
                    .ihsan-admin-login-form input[type="email"]:focus,
                    .ihsan-admin-login-form input[type="password"]:focus {
                        border-color: #b65f3a !important;
                        box-shadow: 0 0 0 3px rgba(182, 95, 58, 0.1) !important;
                    }
                </style>
                <div class="ihsan-admin-login-form fi-simple-page-content">
                    {{ $this->content }}
                </div>
            </div>
        </div>

        {{-- Right: Decorative Panel --}}
        <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden flex-col justify-between p-12" style="background-color: #1a120e;">
            {{-- Gradient orbs --}}
            <div class="absolute inset-0 pointer-events-none">
                <div class="absolute" style="top: -150px; right: -150px; height: 700px; width: 700px; border-radius: 9999px; background-color: rgba(182, 95, 58, 0.3); filter: blur(130px);"></div>
                <div class="absolute" style="bottom: -100px; left: -100px; height: 600px; width: 600px; border-radius: 9999px; background-color: rgba(182, 95, 58, 0.2); filter: blur(120px);"></div>
                <div class="absolute" style="top: 35%; left: 25%; height: 400px; width: 400px; border-radius: 9999px; background-color: rgba(212, 165, 116, 0.15); filter: blur(100px);"></div>
            </div>

            {{-- Top label --}}
            <div class="relative z-10">
                <p class="text-xs font-medium tracking-[0.2em] uppercase" style="color: rgba(255,255,255,0.5);">Ihsan Platform</p>
            </div>

            {{-- Middle: Platform cards --}}
            <div class="relative z-10 space-y-4 max-w-sm">
                {{-- Overview card --}}
                <div class="rounded-2xl p-6" style="background-color: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12); backdrop-filter: blur(8px);">
                    <div class="flex items-center gap-3 mb-5">
                        <div class="h-10 w-10 rounded-xl flex items-center justify-center" style="background-color: rgba(182, 95, 58, 0.25); border: 1px solid rgba(182, 95, 58, 0.4);">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="color: #d4a574;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold" style="color: rgba(255,255,255,0.9);">Platform Overview</p>
                            <p class="text-xs" style="color: rgba(255,255,255,0.5);">Admin dashboard</p>
                        </div>
                    </div>
                    <p class="text-sm leading-relaxed" style="color: rgba(255,255,255,0.65);">
                        Manage organisations, track donations, monitor platform revenue, and oversee operations from a unified admin console.
                    </p>
                </div>

                {{-- Stats card --}}
                <div class="rounded-2xl p-5" style="background-color: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12); backdrop-filter: blur(8px);">
                    <p class="text-xs font-medium mb-4" style="color: rgba(255,255,255,0.5);">Platform at a glance</p>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="rounded-lg p-3" style="background-color: rgba(255,255,255,0.05);">
                            <p class="text-xs mb-1" style="color: rgba(255,255,255,0.45);">Organisations</p>
                            <p class="text-xl font-bold" style="color: rgba(255,255,255,0.95);">142</p>
                        </div>
                        <div class="rounded-lg p-3" style="background-color: rgba(255,255,255,0.05);">
                            <p class="text-xs mb-1" style="color: rgba(255,255,255,0.45);">Donations</p>
                            <p class="text-xl font-bold" style="color: rgba(255,255,255,0.95);">RM 2.4M</p>
                        </div>
                        <div class="rounded-lg p-3" style="background-color: rgba(255,255,255,0.05);">
                            <p class="text-xs mb-1" style="color: rgba(255,255,255,0.45);">Campaigns</p>
                            <p class="text-xl font-bold" style="color: rgba(255,255,255,0.95);">328</p>
                        </div>
                        <div class="rounded-lg p-3" style="background-color: rgba(255,255,255,0.05);">
                            <p class="text-xs mb-1" style="color: rgba(255,255,255,0.45);">Donors</p>
                            <p class="text-xl font-bold" style="color: rgba(255,255,255,0.95);">18.5K</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Bottom --}}
            <div class="relative z-10 flex items-center gap-2">
                <div class="h-1.5 w-1.5 rounded-full" style="background-color: rgba(182, 95, 58, 0.7);"></div>
                <p class="text-sm" style="color: rgba(255,255,255,0.45);">Empowering generosity across Malaysia</p>
            </div>
        </div>
    </div>

    {{-- Modals (required for Filament actions) --}}
    <x-filament-actions::modals />
</div>
