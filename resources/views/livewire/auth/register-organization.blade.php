<div class="min-h-screen flex items-center justify-center py-20 px-4">
    <div class="w-full max-w-lg">
        {{-- Header --}}
        <div class="text-center mb-8">
            <a href="{{ route('home') }}" class="text-white font-bold text-2xl tracking-tight">Ihsan</a>
            <h1 class="mt-6 text-2xl font-bold text-white">Daftar Organisasi</h1>
            <p class="mt-2 text-sm text-slate-400">Isi maklumat organisasi anda untuk mendaftar.</p>
        </div>

        @if ($submitted)
            {{-- Success state --}}
            <div class="bg-teal-500/10 border border-teal-500/30 rounded-2xl p-8 text-center">
                <div class="w-12 h-12 bg-teal-600/20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-teal-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                    </svg>
                </div>
                <h2 class="text-lg font-semibold text-white">Permohonan Diterima</h2>
                <p class="mt-2 text-sm text-slate-400 leading-relaxed">
                    Terima kasih! Permohonan anda telah diterima. Pasukan kami akan menyemak dan menghubungi anda tidak lama lagi.
                </p>
                <a href="{{ route('home') }}" class="mt-6 inline-block text-sm text-teal-400 hover:text-teal-300 transition-colors">
                    Kembali ke laman utama
                </a>
            </div>
        @else
            {{-- Form --}}
            <div class="bg-slate-800/50 border border-white/10 rounded-2xl p-8">
                <form wire:submit="submit" class="space-y-5">
                    <flux:input
                        wire:model="name"
                        label="Nama Organisasi"
                        placeholder="Masjid Al-Falah"
                        required
                        autofocus
                    />

                    <flux:select
                        wire:model="registration_type"
                        label="Jenis Pendaftaran"
                        required
                    >
                        <flux:select.option value="" disabled>-- Pilih jenis --</flux:select.option>
                        <flux:select.option value="ROS">ROS (Pertubuhan)</flux:select.option>
                        <flux:select.option value="ROB">ROB (Syarikat)</flux:select.option>
                        <flux:select.option value="Others">Lain-lain</flux:select.option>
                    </flux:select>

                    <flux:input
                        wire:model="ros_rob_number"
                        label="Nombor ROB/ROS"
                        placeholder="PPM-001-10-01012020"
                        required
                    />

                    <flux:select
                        wire:model="sector"
                        label="Sektor"
                        required
                    >
                        <flux:select.option value="" disabled>-- Pilih sektor --</flux:select.option>
                        <flux:select.option value="Agama">Agama</flux:select.option>
                        <flux:select.option value="Pendidikan">Pendidikan</flux:select.option>
                        <flux:select.option value="Kebajikan Sosial">Kebajikan Sosial</flux:select.option>
                        <flux:select.option value="Kesihatan">Kesihatan</flux:select.option>
                        <flux:select.option value="Alam Sekitar">Alam Sekitar</flux:select.option>
                        <flux:select.option value="Sukan & Rekreasi">Sukan & Rekreasi</flux:select.option>
                        <flux:select.option value="Lain-lain">Lain-lain</flux:select.option>
                    </flux:select>

                    <flux:input
                        wire:model="contact_email"
                        label="Emel"
                        type="email"
                        placeholder="admin@organisasi.org"
                        required
                    />

                    <flux:input
                        wire:model="website_url"
                        label="Laman Web"
                        type="url"
                        placeholder="https://organisasi.org"
                        required
                    />

                    <flux:input
                        wire:model="facebook_url"
                        label="Facebook URL"
                        type="url"
                        placeholder="https://facebook.com/organisasi"
                        description="Tidak wajib"
                    />

                    <flux:button type="submit" variant="primary" class="w-full">
                        Hantar Permohonan
                    </flux:button>
                </form>
            </div>

            <p class="mt-4 text-center text-sm text-slate-500">
                Sudah ada akaun?
                <a href="{{ route('login') }}" class="text-teal-400 hover:text-teal-300 transition-colors">Log masuk</a>
            </p>
        @endif
    </div>
</div>
