<x-filament-panels::page>
    @php
        $pageClass = App\Filament\App\Resources\Donors\Pages\ViewDonor::class;
    @endphp

    <div
        x-data="{
            activeSection: 'supporter-information',
            scrollTo(id) {
                const el = document.getElementById(id);
                if (el) {
                    const offset = 80;
                    const top = el.getBoundingClientRect().top + window.scrollY - offset;
                    window.scrollTo({ top, behavior: 'smooth' });
                }
            }
        }"
        x-init="$nextTick(() => {
            const sectionMap = {
                'Supporter Information': 'supporter-information',
                'Mailing Address': 'supporter-information',
            };

            const navMap = {
                'supporter-information': 'supporter-information',
                'donations-section': 'donations',
                'recurring-plans-section': 'recurring-plans',
            };

            // Assign IDs to form section wrappers based on their heading text
            document.querySelectorAll('.fi-sc-section').forEach(section => {
                const heading = section.querySelector('h2');
                if (heading) {
                    const id = sectionMap[heading.textContent.trim()];
                    if (id) section.id = id;
                }
            });

            const sections = [
                document.getElementById('supporter-information'),
                document.getElementById('donations-section'),
                document.getElementById('recurring-plans-section'),
            ].filter(Boolean);

            if (!sections.length) return;

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        activeSection = navMap[entry.target.id] ?? entry.target.id;
                    }
                });
            }, {
                rootMargin: '-120px 0px -60% 0px',
                threshold: 0,
            });

            sections.forEach(section => observer.observe(section));
        })"
        class="flex gap-6"
    >
        {{-- Left Content --}}
        <div class="flex-1 min-w-0 space-y-6 pb-4">
            {{ $this->content }}

            {{-- Donations Table --}}
            <div id="donations-section" class="scroll-mt-6">
                @livewire(App\Filament\App\Resources\Donors\RelationManagers\DonationsRelationManager::class, ['ownerRecord' => $this->record, 'pageClass' => $pageClass])
            </div>

            {{-- Subscriptions / Recurring Plans Table --}}
            <div id="recurring-plans-section" class="scroll-mt-6">
                @livewire(App\Filament\App\Resources\Donors\RelationManagers\SubscriptionsRelationManager::class, ['ownerRecord' => $this->record, 'pageClass' => $pageClass])
            </div>
        </div>

        {{-- Right Sticky Sidebar --}}
        <div class="w-48 shrink-0 hidden md:block">
            <div class="sticky top-24 space-y-1">
                {{-- Actions --}}
                <div class="pb-2 mb-2 border-b border-gray-100 dark:border-gray-800 space-y-1">
                    <a
                        href="{{ route('donations.show', ['element' => $this->record->donations()->first()?->campaign?->organization?->elements()->first()?->token ?? 'default']) }}"
                        target="_blank"
                        class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-gray-800/50 transition-colors"
                    >
                        <x-heroicon-o-heart class="size-4 shrink-0" />
                        Make donation
                    </a>

                    @php
                        $donorOrganization = $this->record->donations()->first()?->campaign?->organization
                            ?? $this->record->subscriptions()->first()?->campaign?->organization;
                    @endphp

                    @if ($donorOrganization)
                        <a
                            href="{{ route('donorportal.login', $donorOrganization) }}"
                            target="_blank"
                            class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-gray-800/50 transition-colors"
                        >
                            <x-heroicon-o-arrow-top-right-on-square class="size-4 shrink-0" />
                            Open Donor Portal
                        </a>
                    @endif
                </div>

                {{-- Menu Nav --}}
                @foreach ([
                    ['id' => 'supporter-information', 'label' => 'Information', 'icon' => 'heroicon-o-user', 'target' => 'supporter-information'],
                    ['id' => 'donations', 'label' => 'Donations', 'icon' => 'heroicon-o-currency-dollar', 'target' => 'donations-section'],
                    ['id' => 'recurring-plans', 'label' => 'Recurring plans', 'icon' => 'heroicon-o-arrow-path', 'target' => 'recurring-plans-section'],
                    ['id' => 'receipts', 'label' => 'Receipts', 'icon' => 'heroicon-o-document-text', 'target' => 'donations-section'],
                ] as $item)
                    <button
                        type="button"
                        @click.prevent="scrollTo('{{ $item['target'] }}')"
                        class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                        :class="activeSection === '{{ $item['id'] }}'
                            ? 'bg-primary-600 text-white shadow-sm dark:bg-primary-500'
                            : 'text-gray-600 hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-gray-800/50'"
                    >
                        <x-dynamic-component :component="$item['icon']" class="size-4 shrink-0" />
                        {{ $item['label'] }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>
</x-filament-panels::page>
