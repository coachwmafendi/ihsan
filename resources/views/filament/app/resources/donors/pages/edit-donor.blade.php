<x-filament-panels::page>
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
            },
            switchTab(label) {
                const tabs = document.querySelectorAll('.fi-tabs button, .fi-tabs a');
                tabs.forEach(tab => {
                    if (tab.textContent.trim().toLowerCase().includes(label.toLowerCase())) {
                        tab.click();
                        this.activeSection = label === 'subscriptions' ? 'recurring-plans' : 'receipts';
                    }
                });
            }
        }"
        x-init="$nextTick(() => {
            const sectionMap = {
                'Supporter Information': 'supporter-information',
                'Mailing Address': 'mailing-address',
            };

            // Assign IDs to section wrappers based on their heading text
            document.querySelectorAll('.fi-sc-section').forEach(section => {
                const heading = section.querySelector('h2');
                if (heading) {
                    const id = sectionMap[heading.textContent.trim()];
                    if (id) section.id = id;
                }
            });

            const sections = Object.values(sectionMap).map(id => document.getElementById(id)).filter(Boolean);
            if (!sections.length) return;

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        activeSection = entry.target.id;
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
        </div>

        {{-- Right Sticky Sidebar --}}
        <div class="w-48 shrink-0 hidden md:block">
            <div class="sticky top-24 space-y-1">
                {{-- Actions --}}
                <div class="pb-2 mb-2 border-b border-gray-100 dark:border-gray-800 space-y-1">
                    <a
                        href="{{ route('donations.show', ['element' => $this->record->organization->elements()->first()?->token ?? 'default']) }}"
                        target="_blank"
                        class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-gray-800/50 transition-colors"
                    >
                        <x-heroicon-o-heart class="size-4 shrink-0" />
                        Make donation
                    </a>

                    @if ($organization)
                        <a
                            href="{{ route('donorportal.login', $organization) }}"
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
                    ['id' => 'supporter-information', 'label' => 'Information', 'icon' => 'heroicon-o-user', 'type' => 'scroll'],
                    ['id' => 'mailing-address', 'label' => 'Mailing Address', 'icon' => 'heroicon-o-map-pin', 'type' => 'scroll'],
                    ['id' => 'recurring-plans', 'label' => 'Recurring plans', 'icon' => 'heroicon-o-arrow-path', 'type' => 'tab', 'tabLabel' => 'subscriptions'],
                    ['id' => 'receipts', 'label' => 'Receipts', 'icon' => 'heroicon-o-document-text', 'type' => 'tab', 'tabLabel' => 'donations'],
                ] as $item)
                    <button
                        type="button"
                        @if ($item['type'] === 'scroll')
                            @click.prevent="scrollTo('{{ $item['id'] }}')"
                        @else
                            @click.prevent="switchTab('{{ $item['tabLabel'] }}')"
                        @endif
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
