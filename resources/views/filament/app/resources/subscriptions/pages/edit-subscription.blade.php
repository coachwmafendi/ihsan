<x-filament-panels::page>
    <div
        x-data="{
            activeSection: 'recurring-plan',
            scrollTo(id) {
                const el = document.getElementById(id);
                if (el) {
                    el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    setTimeout(() => {
                        const offset = 80;
                        window.scrollBy({ top: -offset, behavior: 'smooth' });
                    }, 0);
                }
            }
        }"
        x-init="$nextTick(() => {
            const sections = $el.querySelectorAll('section[id]');
            const updateActive = () => {
                const scrollTop = window.scrollY + 120;
                let current = sections[0]?.id || 'recurring-plan';
                for (const section of sections) {
                    if (section.offsetTop <= scrollTop) {
                        current = section.id;
                    } else {
                        break;
                    }
                }
                activeSection = current;
            };
            window.addEventListener('scroll', updateActive, { passive: true });
            updateActive();
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
                    @if ($this->record->status->value !== 'cancelled')
                        <button
                            type="button"
                            wire:click="mountAction('updatePaymentDetails')"
                            class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-gray-800/50 transition-colors"
                        >
                            <x-heroicon-o-credit-card class="size-4 shrink-0" />
                            Edit Payment Details
                        </button>
                    @endif

                    @if ($this->record->status->value === 'active' && ! $this->record->paused_until)
                        <button
                            type="button"
                            wire:click="mountAction('skipInstallments')"
                            class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-gray-800/50 transition-colors"
                        >
                            <x-heroicon-o-forward class="size-4 shrink-0" />
                            Skip Installments
                        </button>
                    @endif

                    @if ($this->record->status->value !== 'cancelled')
                        <button
                            type="button"
                            wire:click="mountAction('offerPlanUpgrade')"
                            class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-gray-800/50 transition-colors"
                        >
                            <x-heroicon-o-arrow-trending-up class="size-4 shrink-0" />
                            Offer Plan Upgrade
                        </button>
                    @endif

                    @if ($this->record->status->value !== 'cancelled')
                        <button
                            type="button"
                            wire:click="mountAction('cancel')"
                            class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20 transition-colors"
                        >
                            <x-heroicon-o-x-circle class="size-4 shrink-0" />
                            Cancel Recurring
                        </button>
                    @endif
                </div>

                {{-- Menu Nav --}}
                @foreach ([
                    ['id' => 'recurring-plan', 'label' => 'Recurring plan', 'icon' => 'heroicon-o-arrow-path'],
                    ['id' => 'personal-information', 'label' => 'Personal Information', 'icon' => 'heroicon-o-user'],
                    ['id' => 'sources', 'label' => 'Sources', 'icon' => 'heroicon-o-globe-alt'],
                    ['id' => 'installments', 'label' => 'Installments', 'icon' => 'heroicon-o-calendar-days'],
                    ['id' => 'receipts', 'label' => 'Receipts', 'icon' => 'heroicon-o-document-text'],
                ] as $item)
                    <button
                        type="button"
                        @click.prevent="scrollTo('{{ $item['id'] }}')"
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
