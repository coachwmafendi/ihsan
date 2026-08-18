{{-- resources/views/livewire/app/dashboard.blade.php --}}
<div
    x-data="{ loaded: false }"
    x-init="$nextTick(() => setTimeout(() => loaded = true, 350))"
    class="relative"
>
    {{-- Initial loading skeleton --}}
    <div
        x-show="! loaded"
        x-transition.opacity.duration.250ms
        x-cloak
        aria-hidden="true"
        class="absolute inset-0 z-10 bg-[#f7f7fb]"
    >
        <div class="space-y-8">
            {{-- Header skeleton --}}
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <div class="h-8 w-48 animate-pulse rounded bg-slate-200"></div>
                    <div class="mt-1 h-4 w-36 animate-pulse rounded bg-slate-200"></div>
                </div>
                <div class="h-9 w-80 animate-pulse rounded-lg bg-slate-200"></div>
            </div>

            {{-- Quick actions skeleton --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div class="h-10 animate-pulse rounded-lg bg-slate-200"></div>
                    <div class="h-10 animate-pulse rounded-lg bg-slate-200"></div>
                    <div class="h-10 animate-pulse rounded-lg bg-slate-200"></div>
                </div>
            </div>

            {{-- Stats grid skeleton --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @for ($i = 0; $i < 8; $i++)
                    <div class="h-28 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="h-3 w-24 animate-pulse rounded bg-slate-200"></div>
                        <div class="mt-3 h-7 w-32 animate-pulse rounded bg-slate-200"></div>
                    </div>
                @endfor
            </div>

            {{-- Charts grid skeleton --}}
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                @for ($i = 0; $i < 4; $i++)
                    <div class="h-80 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="mb-1 h-5 w-40 animate-pulse rounded bg-slate-200"></div>
                        <div class="h-4 w-56 animate-pulse rounded bg-slate-200"></div>
                        <div class="mt-8 space-y-4">
                            <div class="h-3 w-full animate-pulse rounded bg-slate-200"></div>
                            <div class="h-3 w-5/6 animate-pulse rounded bg-slate-200"></div>
                            <div class="h-3 w-4/5 animate-pulse rounded bg-slate-200"></div>
                            <div class="h-3 w-full animate-pulse rounded bg-slate-200"></div>
                            <div class="h-3 w-2/3 animate-pulse rounded bg-slate-200"></div>
                        </div>
                    </div>
                @endfor
                <div class="h-80 rounded-xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2">
                    <div class="mb-1 h-5 w-48 animate-pulse rounded bg-slate-200"></div>
                    <div class="h-4 w-32 animate-pulse rounded bg-slate-200"></div>
                    <div class="mt-8 space-y-4">
                        <div class="h-3 w-full animate-pulse rounded bg-slate-200"></div>
                        <div class="h-3 w-5/6 animate-pulse rounded bg-slate-200"></div>
                        <div class="h-3 w-4/5 animate-pulse rounded bg-slate-200"></div>
                        <div class="h-3 w-full animate-pulse rounded bg-slate-200"></div>
                        <div class="h-3 w-2/3 animate-pulse rounded bg-slate-200"></div>
                    </div>
                </div>
            </div>

            {{-- Recent donations skeleton --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-4 h-5 w-40 animate-pulse rounded bg-slate-200"></div>
                <div class="space-y-3">
                    @for ($i = 0; $i < 5; $i++)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="size-8 animate-pulse rounded-full bg-slate-200"></div>
                                <div class="h-4 w-32 animate-pulse rounded bg-slate-200"></div>
                            </div>
                            <div class="h-4 w-20 animate-pulse rounded bg-slate-200"></div>
                        </div>
                    @endfor
                </div>
            </div>
        </div>
    </div>

    {{-- Loading overlay for period/filter changes --}}
    <div wire:loading.delay class="absolute inset-0 z-20 bg-white/80 backdrop-blur-sm" aria-hidden="true">
        <div class="flex h-full items-center justify-center">
            <x-heroicon-o-arrow-path class="size-8 animate-spin text-slate-400" />
        </div>
    </div>

    <div class="space-y-8">
        <script>
            function downloadChartPng(canvas, filename) {
                const a = document.createElement('a');
                a.href = canvas.toDataURL('image/png');
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                a.remove();
            }

            function chartExportCanvas(width, contentHeight, title, description) {
                const scale = 2;
                const headerHeight = 64;
                const canvas = document.createElement('canvas');
                canvas.width = width * scale;
                canvas.height = (contentHeight + headerHeight) * scale;

                const ctx = canvas.getContext('2d');
                ctx.scale(scale, scale);
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, width, contentHeight + headerHeight);
                ctx.font = 'bold 18px ui-sans-serif, system-ui, sans-serif';
                ctx.fillStyle = '#0f172a';
                ctx.textAlign = 'left';
                ctx.fillText(title, 16, 30);
                ctx.font = '12px ui-sans-serif, system-ui, sans-serif';
                ctx.fillStyle = '#64748b';
                ctx.fillText(description, 16, 48);

                return { canvas, ctx, offsetY: headerHeight };
            }

            function downloadCanvasChartPng(canvasId, title, description, filename) {
                const chartCanvas = document.getElementById(canvasId);
                if (! chartCanvas) {
                    return;
                }

                const paddingTop = 16;
                const titleHeight = 24;
                const descHeight = 18;
                const canvas = document.createElement('canvas');
                canvas.width = chartCanvas.width;
                canvas.height = chartCanvas.height + paddingTop + titleHeight + descHeight;

                const ctx = canvas.getContext('2d');
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                ctx.font = 'bold 18px ui-sans-serif, system-ui, sans-serif';
                ctx.fillStyle = '#0f172a';
                ctx.textAlign = 'left';
                ctx.fillText(title, 12, paddingTop + 16);
                ctx.font = '12px ui-sans-serif, system-ui, sans-serif';
                ctx.fillStyle = '#64748b';
                ctx.fillText(description, 12, paddingTop + 16 + descHeight);
                ctx.drawImage(chartCanvas, 0, paddingTop + titleHeight + descHeight);

                downloadChartPng(canvas, filename);
            }

            function downloadBarRowsPng(title, description, rows, footer, filename) {
                if (! rows.length) {
                    return;
                }

                const width = 720;
                const rowHeight = 56;
                const footerHeight = footer ? 28 : 8;
                const { canvas, ctx, offsetY } = chartExportCanvas(width, rows.length * rowHeight + footerHeight, title, description);

                rows.forEach((row, i) => {
                    const y = offsetY + i * rowHeight;

                    ctx.font = '600 14px ui-sans-serif, system-ui, sans-serif';
                    ctx.fillStyle = '#334155';
                    ctx.textAlign = 'left';
                    ctx.fillText(row.label, 16, y + 20);

                    ctx.textAlign = 'right';
                    ctx.fillStyle = '#0f172a';
                    ctx.fillText(`${row.value}`, width - 64, y + 20);
                    ctx.font = '12px ui-sans-serif, system-ui, sans-serif';
                    ctx.fillStyle = '#94a3b8';
                    ctx.fillText(`${row.percentage}%`, width - 16, y + 20);

                    ctx.beginPath();
                    ctx.roundRect(16, y + 30, width - 32, 8, 4);
                    ctx.fillStyle = '#f1f5f9';
                    ctx.fill();

                    if (row.percentage > 0) {
                        ctx.beginPath();
                        ctx.roundRect(16, y + 30, (width - 32) * Math.min(row.percentage, 100) / 100, 8, 4);
                        ctx.fillStyle = '#3b82f6';
                        ctx.fill();
                    }
                });

                if (footer) {
                    ctx.font = '12px ui-sans-serif, system-ui, sans-serif';
                    ctx.fillStyle = '#94a3b8';
                    ctx.textAlign = 'center';
                    ctx.fillText(footer, width / 2, offsetY + rows.length * rowHeight + 12);
                }

                downloadChartPng(canvas, filename);
            }

            function downloadTrendPng(title, description, points, totalLabel, filename) {
                if (! points.length) {
                    return;
                }

                const width = 720;
                const chartHeight = 220;
                const labelHeight = 24;
                const totalHeight = 34;
                const { canvas, ctx, offsetY } = chartExportCanvas(width, totalHeight + chartHeight + labelHeight, title, description);

                ctx.font = 'bold 16px ui-sans-serif, system-ui, sans-serif';
                ctx.fillStyle = '#0f172a';
                ctx.textAlign = 'left';
                ctx.fillText(totalLabel, 16, offsetY + 16);

                const chartTop = offsetY + totalHeight;
                const maxAmount = Math.max(...points.map(p => Number(p.amount) || 0), 1);
                const gap = 2;
                const barWidth = (width - 32 - gap * (points.length - 1)) / points.length;

                points.forEach((point, i) => {
                    const value = Number(point.amount) || 0;
                    const barHeight = Math.max((value / maxAmount) * chartHeight, 3);
                    const x = 16 + i * (barWidth + gap);

                    ctx.fillStyle = 'rgba(59, 130, 246, 0.35)';
                    ctx.beginPath();
                    ctx.roundRect(x, chartTop + chartHeight - barHeight, barWidth, barHeight, [3, 3, 0, 0]);
                    ctx.fill();
                });

                const labelStep = Math.max(1, Math.ceil(points.length / 7));
                ctx.font = '11px ui-sans-serif, system-ui, sans-serif';
                ctx.fillStyle = '#94a3b8';
                ctx.textAlign = 'center';

                points.forEach((point, i) => {
                    if (i % labelStep === 0) {
                        const x = 16 + i * (barWidth + gap) + barWidth / 2;
                        ctx.fillText(point.date, x, chartTop + chartHeight + 16);
                    }
                });

                downloadChartPng(canvas, filename);
            }

            function renderStackedBarChart(el, data, description, title) {
                const canvas = el.querySelector('canvas');
                if (! canvas) {
                    return;
                }

                if (typeof window.Chart === 'undefined') {
                    setTimeout(() => renderStackedBarChart(el, data, description, title), 100);
                    return;
                }

                if (canvas._chart) {
                    canvas._chart.destroy();
                }

                const ctx = canvas.getContext('2d');
                const labels = data.days.map(d => d.date);
                const oneTime = data.days.map(d => d.one_time);
                const recurring = data.days.map(d => d.recurring);

                canvas._chart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels,
                        datasets: [
                            {
                                label: `Recurring donations (${Number(data.recurring_total).toLocaleString()})`,
                                data: recurring,
                                backgroundColor: '#60a5fa',
                                borderRadius: 2,
                            },
                            {
                                label: `One-time donations (${Number(data.one_time_total).toLocaleString()})`,
                                data: oneTime,
                                backgroundColor: '#1e40af',
                                borderRadius: 2,
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: {
                            duration: 900,
                            easing: 'easeOutQuart',
                        },
                        onHover(event, elements) {
                            event.native.target.style.cursor = elements.length ? 'pointer' : 'default';
                        },
                        onClick(event, elements) {
                            if (! elements.length || ! data.donations_url) {
                                return;
                            }

                            const day = data.days[elements[0].index];
                            if (! day || ! day.date_from_key) {
                                return;
                            }

                            const params = new URLSearchParams({
                                frequencyFilter: elements[0].datasetIndex === 0 ? 'recurring' : 'one_time',
                                period: 'custom',
                                dateFrom: day.date_from_key,
                                dateTo: day.date_to_key,
                            });

                            window.location.href = `${data.donations_url}?${params.toString()}`;
                        },
                        scales: {
                            x: {
                                stacked: true,
                                grid: { display: false },
                                ticks: { font: { size: 11 }, color: '#94a3b8' },
                            },
                            y: {
                                stacked: true,
                                beginAtZero: true,
                                suggestedMax: data.max_scale,
                                ticks: { stepSize: data.step, color: '#94a3b8' },
                                grid: { color: '#f1f5f9' },
                            },
                        },
                        plugins: {
                            legend: {
                                position: 'top',
                                align: 'start',
                                labels: { usePointStyle: true, boxWidth: 8, color: '#475569' },
                            },
                            tooltip: {
                                mode: 'index',
                                intersect: false,
                                callbacks: {
                                    title: (items) => items.length ? (data.days[items[0].dataIndex]?.label ?? items[0].label) : '',
                                },
                            },
                        },
                    },
                });
            }

            function renderDonutChart(el, data, description, title) {
                const canvas = el.querySelector('canvas');
                if (! canvas) {
                    return;
                }

                if (typeof window.Chart === 'undefined') {
                    setTimeout(() => renderDonutChart(el, data, description, title), 100);
                    return;
                }

                if (canvas._chart) {
                    canvas._chart.destroy();
                }

                const ctx = canvas.getContext('2d');
                const colors = ['#2563eb', '#3b82f6', '#14b8a6', '#f59e0b', '#f43f5e', '#6366f1', '#10b981', '#06b6d4', '#f97316', '#a855f7'];
                const backgroundColor = data.map((_, i) => colors[i % colors.length]);
                const total = data.reduce((sum, item) => sum + (Number(item.value) || 0), 0);

                        const centerTextPlugin = {
                            id: 'centerText',
                            beforeDraw(chart) {
                                if (chart.config.type !== 'doughnut') return;
                                const { ctx } = chart;
                                const area = chart.chartArea;
                                if (! area) return;
                                ctx.save();
                                const largest = total > 0
                                    ? Math.round(Math.max(...chart.data.datasets[0].data.map(v => (v / total) * 100)))
                                    : 0;
                                const fontSize = Math.min(area.width, area.height) / 5;
                                ctx.font = `bold ${fontSize}px ui-sans-serif, system-ui, sans-serif`;
                                ctx.textAlign = 'center';
                                ctx.textBaseline = 'middle';
                                ctx.fillStyle = '#0f172a';
                                const x = area.left + area.width / 2;
                                const y = area.top + area.height / 2;
                                ctx.fillText(`${largest}%`, x, y);
                                ctx.restore();
                            },
                        };

                canvas._chart = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: data.map(d => d.name),
                        datasets: [{
                            data: data.map(d => d.value),
                            backgroundColor,
                            borderWidth: 2,
                            borderColor: '#ffffff',
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '60%',
                        animation: {
                            animateRotate: true,
                            animateScale: false,
                            duration: 900,
                            easing: 'easeOutQuart',
                        },
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    generateLabels(chart) {
                                        const values = chart.data.datasets[0].data;
                                        const totalValue = values.reduce((a, b) => a + b, 0);
                                        return chart.data.labels.map((label, i) => {
                                            const value = Number(values[i]) || 0;
                                            const pct = totalValue > 0 ? Math.round((value / totalValue) * 100) : 0;
                                            return {
                                                text: `${label} MYR ${value.toLocaleString()} (${pct}%)`,
                                                fillStyle: chart.data.datasets[0].backgroundColor[i],
                                                hidden: false,
                                                index: i,
                                            };
                                        });
                                    },
                                    usePointStyle: true,
                                    boxWidth: 8,
                                    color: '#475569',
                                },
                            },
                        },
                    },
                    plugins: [centerTextPlugin],
                });
            }
        </script>

    {{-- Page Header --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">Dashboard</h1>
            <p class="mt-1 text-sm text-slate-500">Overview of your fundraising activity</p>
        </div>

        <div class="flex flex-col items-start gap-3 sm:items-end">
            <div class="flex max-w-full overflow-x-auto rounded-xl border border-slate-200 bg-slate-100 p-1 shadow-sm [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                <button
                    type="button"
                    wire:click="$set('period', 'today')"
                    class="shrink-0 rounded-lg px-3 py-1.5 text-sm font-medium transition-all {{ $period === 'today' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}"
                >
                    Today
                </button>
                <button
                    type="button"
                    wire:click="$set('period', 'yesterday')"
                    class="shrink-0 rounded-lg px-3 py-1.5 text-sm font-medium transition-all {{ $period === 'yesterday' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}"
                >
                    Yesterday
                </button>
                <button
                    type="button"
                    wire:click="$set('period', '7_days')"
                    class="shrink-0 rounded-lg px-3 py-1.5 text-sm font-medium transition-all {{ $period === '7_days' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}"
                >
                    7D
                </button>
                <button
                    type="button"
                    wire:click="$set('period', '30_days')"
                    class="shrink-0 rounded-lg px-3 py-1.5 text-sm font-medium transition-all {{ $period === '30_days' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}"
                >
                    30D
                </button>
                <button
                    type="button"
                    wire:click="$set('period', '90_days')"
                    class="shrink-0 rounded-lg px-3 py-1.5 text-sm font-medium transition-all {{ $period === '90_days' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}"
                >
                    90D
                </button>
                <button
                    type="button"
                    wire:click="$set('period', 'custom')"
                    class="shrink-0 rounded-lg px-3 py-1.5 text-sm font-medium transition-all {{ $period === 'custom' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}"
                >
                    Custom
                </button>
            </div>

            @if($period === 'custom')
                <div class="flex flex-wrap items-center gap-2">
                    <input
                        type="date"
                        wire:model.live="customFrom"
                        class="block rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                    >
                    <span class="text-sm text-slate-400">to</span>
                    <input
                        type="date"
                        wire:model.live="customTo"
                        class="block rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                    >
                </div>
            @endif
        </div>
    </div>

    {{-- Quick Actions --}}
    <x-ui.card title="Quick actions">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <x-ui.button wire:click="openCreateCampaignModal" variant="primary">
                <x-heroicon-o-plus class="size-4" />
                Create Campaign
            </x-ui.button>

            <x-ui.button href="/donations" variant="outline">
                <x-heroicon-o-banknotes class="size-4" />
                View Donations
            </x-ui.button>

            <x-ui.button href="/virtual-terminal" variant="outline" target="_blank">
                <x-heroicon-o-device-phone-mobile class="size-4" />
                Virtual Terminal
                <x-heroicon-o-arrow-top-right-on-square aria-label="Opens in new tab" class="size-4" />
            </x-ui.button>
        </div>
    </x-ui.card>

    {{-- Stats Grid --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-ui.stat-card
            label="Total Donations"
            value="{{ ($this->stats['has_approximation'] ? '≈ ' : '').'MYR '.number_format($this->stats['total_amount'] ?? 0, 2) }}"
            subtext="{{ number_format($this->stats['total_count'] ?? 0) }} donations"
            value-class="text-2xl font-semibold tracking-tight text-gray-950 dark:text-white"
        />
        <x-ui.stat-card
            label="Donors"
            value="{{ number_format($this->stats['total_donors'] ?? 0) }}"
            value-class="text-2xl font-semibold tracking-tight text-gray-950 dark:text-white"
        />
        <x-ui.stat-card
            label="Active Campaigns"
            value="{{ number_format($this->stats['active_campaigns'] ?? 0) }}"
            value-class="text-2xl font-semibold tracking-tight text-gray-950 dark:text-white"
        />
        <x-ui.stat-card
            label="Active Subscriptions"
            value="{{ number_format($this->stats['active_subscriptions'] ?? 0) }}"
            value-class="text-2xl font-semibold tracking-tight text-gray-950 dark:text-white"
        />
        <x-ui.stat-card
            label="Avg Donation"
            value="{{ ($this->stats['has_approximation'] ? '≈ ' : '') }}MYR {{ number_format(($this->stats['total_count'] ?? 0) > 0 ? ($this->stats['total_amount'] ?? 0) / ($this->stats['total_count'] ?? 1) : 0, 2) }}"
            value-class="text-2xl font-semibold tracking-tight text-gray-950 dark:text-white"
        />
        <x-ui.stat-card
            label="MRR"
            value="{{ ($this->recurringHealth['mrr_has_approximation'] ? '≈ ' : '').'MYR '.number_format($this->recurringHealth['mrr'] ?? 0, 2) }}"
            subtext="Monthly recurring revenue"
            value-class="text-2xl font-semibold tracking-tight text-gray-950 dark:text-white"
        />
        <x-ui.stat-card
            label="At-risk Subscriptions"
            value="{{ number_format($this->recurringHealth['at_risk_count'] ?? 0) }}"
            subtext="Past due or failed"
            value-class="text-2xl font-semibold tracking-tight text-gray-950 dark:text-white"
        />
        <x-ui.stat-card
            label="Expected (30 days)"
            value="{{ ($this->recurringHealth['expected_30_days_has_approximation'] ? '≈ ' : '').'MYR '.number_format($this->recurringHealth['expected_30_days'] ?? 0, 2) }}"
            subtext="Scheduled recurring charges"
            value-class="text-2xl font-semibold tracking-tight text-gray-950 dark:text-white"
        />
    </div>

    {{-- Charts Grid --}}
    @php
        $donationsFilterUrl = $period === 'custom'
            ? route('app.donations.index', ['period' => 'custom', 'dateFrom' => $customFrom, 'dateTo' => $customTo])
            : route('app.donations.index', ['period' => $period]);
    @endphp
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Donation Trend --}}
        @php
            $trendExportPoints = collect($this->donationTrend)->map(fn ($p) => ['date' => $p['date'], 'amount' => $p['amount']])->values();
            $trendExportTotal = (collect($this->donationTrend)->contains('has_approximation', true) ? '≈ ' : '')
                .'MYR '.number_format($trendExportPoints->sum('amount'), 2).' total in period';
        @endphp
        <x-ui.card title="Donation Trend" description="Donations over the selected period">
            <x-slot:actions>
                @if($trendExportPoints->isNotEmpty())
                    <x-ui.chart-download-button
                        x-data
                        @click="downloadTrendPng('Donation Trend', 'Donations over the selected period', {{ \Illuminate\Support\Js::from($trendExportPoints) }}, {{ \Illuminate\Support\Js::from($trendExportTotal) }}, 'donation-trend.png')"
                    />
                @endif
                <x-ui.chart-link-button :href="$donationsFilterUrl" />
            </x-slot:actions>
            @if(count($this->donationTrend) > 0)
                @php
                    $sparklineData = collect($this->donationTrend)->pluck('amount')->toArray();
                    $donationTrendTotal = array_sum($sparklineData);
                    $donationTrendHasApproximation = collect($this->donationTrend)->contains('has_approximation', true);
                @endphp

                <div class="mb-4">
                    <div class="text-2xl font-bold text-slate-900">
                        @if($donationTrendHasApproximation)
                            <x-ui.tooltip text="Includes converted foreign currencies">
                                <span>≈ MYR {{ number_format($donationTrendTotal, 2) }}</span>
                            </x-ui.tooltip>
                        @else
                            <span>MYR {{ number_format($donationTrendTotal, 2) }}</span>
                        @endif
                    </div>
                    <div class="text-sm text-slate-500">Total in period</div>
                </div>

                <div
                    wire:key="donation-trend-apex-{{ $period }}-{{ $customFrom }}-{{ $customTo }}"
                    x-data="{
                        chart: null,
                        renderChart() {
                            const data = @js($this->donationTrend);
                            const hasApprox = data.some((p) => p.has_approximation);
                            const pointCount = data.length;
                            const isSparse = pointCount <= 2;

                            const options = {
                                series: [{
                                    name: 'Total raised',
                                    data: data.map((p) => ({ x: p.date, y: p.amount })),
                                }],
                                chart: {
                                    type: 'area',
                                    height: 280,
                                    toolbar: { show: false },
                                    animations: { enabled: true },
                                    fontFamily: 'inherit',
                                    dropShadow: {
                                        enabled: true,
                                        top: 4,
                                        left: 0,
                                        blur: 6,
                                        color: '#3b82f6',
                                        opacity: 0.35,
                                    },
                                },
                                colors: ['#2563eb'],
                                fill: {
                                    type: 'gradient',
                                    gradient: {
                                        shadeIntensity: 1,
                                        opacityFrom: 0.26,
                                        opacityTo: 0.04,
                                        stops: [0, 100],
                                    },
                                },
                                stroke: { curve: isSparse ? 'straight' : 'smooth', width: 3 },
                                dataLabels: { enabled: false },
                                markers: {
                                    size: isSparse ? 6 : 4,
                                    colors: ['#ffffff'],
                                    strokeColors: ['#2563eb'],
                                    strokeWidth: 2,
                                    hover: { size: 7 },
                                },
                                grid: {
                                    borderColor: '#f1f5f9',
                                    strokeDashArray: 4,
                                    xaxis: { lines: { show: false } },
                                    yaxis: { lines: { show: true } },
                                    padding: { left: 8, right: 8 },
                                },
                                xaxis: {
                                    tooltip: { enabled: false },
                                    axisBorder: { show: false },
                                    axisTicks: { show: false },
                                    crosshairs: { stroke: { color: '#cbd5e1', width: 1, dashArray: 4 } },
                                    tickAmount: isSparse ? pointCount : (pointCount <= 7 ? pointCount : 10),
                                    labels: {
                                        rotate: pointCount > 14 ? 45 : 0,
                                        rotateAlways: pointCount > 14,
                                        hideOverlappingLabels: true,
                                    },
                                },
                                yaxis: {
                                    tickAmount: 3,
                                    labels: {
                                        formatter: (value) => 'MYR ' + Number(value).toLocaleString('en-MY'),
                                    },
                                },
                                tooltip: {
                                    theme: 'light',
                                    x: { format: 'dd MMM' },
                                    y: {
                                        formatter: (value) => (hasApprox ? '≈ ' : '') + 'MYR ' + Number(value).toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 }),
                                    },
                                },
                            };

                            if (this.chart) {
                                this.chart.destroy();
                            }

                            this.chart = new ApexCharts(this.$refs.donationTrendChart, options);
                            this.chart.render();
                        },
                        init() {
                            this.$nextTick(() => this.renderChart());
                        },
                    }"
                    x-init="init()"
                >
                    <div x-ref="donationTrendChart"></div>
                </div>
            @else
                <div class="py-8 text-center text-sm text-slate-400">No donation data for this period</div>
            @endif
        </x-ui.card>

        {{-- Donations by Campaign --}}
        @php
            $totalCampaignAmount = array_sum(array_column($this->campaignsBreakdown, 'amount')) ?: 1;
            $campaignExportRows = collect($this->campaignsBreakdown)->map(fn ($c) => [
                'label' => $c['name'],
                'value' => ($c['has_approximation'] ? '≈ ' : '').'MYR '.number_format($c['amount'], 2),
                'percentage' => round(($c['amount'] / $totalCampaignAmount) * 100),
            ])->values();
        @endphp
        <x-ui.card title="Donations by Campaign" description="Top 5 campaigns by amount raised">
            <x-slot:actions>
                @if($campaignExportRows->isNotEmpty())
                    <x-ui.chart-download-button
                        x-data
                        @click="downloadBarRowsPng('Donations by Campaign', 'Top 5 campaigns by amount raised', {{ \Illuminate\Support\Js::from($campaignExportRows) }}, '', 'donations-by-campaign.png')"
                    />
                @endif
                <x-ui.chart-link-button :href="$donationsFilterUrl" />
            </x-slot:actions>
            @if(count($this->campaignsBreakdown) > 0)
                <div class="space-y-4">
                    @foreach($this->campaignsBreakdown as $campaign)
                        @php
                            $percentage = $totalCampaignAmount > 0 ? round(($campaign['amount'] / $totalCampaignAmount) * 100) : 0;
                            $campaignApprox = $campaign['has_approximation'] ? '≈ ' : '';
                        @endphp
                        <div>
                            <div class="mb-1.5 flex items-center justify-between">
                                <span class="text-sm font-medium text-slate-700">{{ $campaign['name'] }}</span>
                                <div class="flex items-center gap-2">
                                    @if ($campaign['has_approximation'])
                                        <x-ui.tooltip text="Includes donations converted from foreign currencies">
                                            <span class="text-sm font-semibold text-slate-900">{{ $campaignApprox }}MYR {{ number_format($campaign['amount'], 2) }}</span>
                                        </x-ui.tooltip>
                                    @else
                                        <span class="text-sm font-semibold text-slate-900">{{ $campaignApprox }}MYR {{ number_format($campaign['amount'], 2) }}</span>
                                    @endif
                                    <span class="text-xs text-slate-400">{{ $percentage }}%</span>
                                </div>
                            </div>
                            <div class="w-full overflow-hidden rounded-full bg-slate-100">
                                <div class="h-2 rounded-full bg-blue-500 transition-all duration-500" style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="py-8 text-center text-sm text-slate-400">No campaign data for this period</div>
            @endif
        </x-ui.card>

        {{-- Donation Sizes --}}
        @php
            $totalSizeCount = array_sum(array_column($this->donationSizes, 'count')) ?: 1;
            $sizeExportRows = collect($this->donationSizes)->map(fn ($s) => [
                'label' => $s['label'],
                'value' => number_format($s['count']),
                'percentage' => $s['percentage'],
            ])->values();
            $sizeExportFooter = number_format($totalSizeCount).' total donations';
        @endphp
        <x-ui.card title="Donation Sizes" description="Distribution by amount range">
            <x-slot:actions>
                @if($sizeExportRows->isNotEmpty())
                    <x-ui.chart-download-button
                        x-data
                        @click="downloadBarRowsPng('Donation Sizes', 'Distribution by amount range', {{ \Illuminate\Support\Js::from($sizeExportRows) }}, {{ \Illuminate\Support\Js::from($sizeExportFooter) }}, 'donation-sizes.png')"
                    />
                @endif
                <x-ui.chart-link-button :href="$donationsFilterUrl" />
            </x-slot:actions>
            @if(count($this->donationSizes) > 0)
                <div class="space-y-4">
                    @foreach($this->donationSizes as $size)
                        <div>
                            <div class="mb-1.5 flex items-center justify-between">
                                <span class="text-sm font-medium text-slate-700">{{ $size['label'] }}</span>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-semibold text-slate-900">{{ number_format($size['count']) }}</span>
                                    <span class="text-xs text-slate-400">{{ $size['percentage'] }}%</span>
                                </div>
                            </div>
                            <div class="w-full overflow-hidden rounded-full bg-slate-100">
                                <div class="h-2.5 rounded-full bg-blue-500 transition-all duration-500" style="width: {{ $size['percentage'] }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-4 text-center text-xs text-slate-400">{{ number_format($totalSizeCount) }} total donations</div>
            @else
                <div class="py-8 text-center text-sm text-slate-400">No donation data for this period</div>
            @endif
        </x-ui.card>

        {{-- Payment Methods --}}
        <x-ui.card title="Payment Methods" description="Breakdown by payment type">
            <x-slot:actions>
                @if(count($this->paymentMethods) > 0)
                    <x-ui.chart-download-button
                        x-data
                        @click="downloadCanvasChartPng('payment-methods-chart', 'Payment Methods', 'Breakdown by payment type', 'payment-methods.png')"
                    />
                @endif
                <x-ui.chart-link-button :href="$donationsFilterUrl" />
            </x-slot:actions>
            @if(count($this->paymentMethods) > 0)
                <x-ui.donut-chart
                    wire:key="payment-methods-chart-{{ $period }}-{{ $customFrom }}-{{ $customTo }}"
                    canvas-id="payment-methods-chart"
                    title="Payment Methods"
                    description="Breakdown by payment type"
                    :data="$this->paymentMethods"
                />
            @else
                <div class="py-8 text-center text-sm text-slate-400">No payment data for this period</div>
            @endif
        </x-ui.card>

        {{-- Donations by Frequency --}}
        @php
            $frequency = $this->donationsByFrequency;
            $frequencyDescription = match ($period) {
                'today' => 'Today',
                'yesterday' => 'Yesterday',
                '7_days' => 'Last 7 days',
                '30_days' => 'Last 30 days',
                '90_days' => 'Last 90 days',
                'this_month' => 'This month',
                'custom' => 'Custom range',
                default => 'Selected period',
            };
        @endphp

        @php
            $frequencyPeriodOptions = [
                'today' => 'Today',
                'yesterday' => 'Yesterday',
                '7_days' => 'Last 7 days',
                '30_days' => 'Last 30 days',
                '90_days' => 'Last 90 days',
                'this_month' => 'This month',
            ];
        @endphp

        <div class="lg:col-span-2" x-data="{ showFilter: false, chartDescription: @js($frequencyDescription) }">
            <x-ui.card
                title="Donations by Frequency"
                :description="$frequencyDescription"
            >
                <x-slot:actions>
                    <x-ui.chart-download-button
                        @click="downloadCanvasChartPng('frequency-chart', 'Donations by Frequency', chartDescription, 'donations-by-frequency.png')"
                    />
                    <x-ui.chart-link-button :href="$donationsFilterUrl" />
                    <div class="relative">
                        <button
                            type="button"
                            class="rounded-lg border border-slate-200 p-2 text-slate-400 hover:border-slate-300 hover:text-slate-600 focus:outline-none focus:ring-2 focus:ring-teal-500"
                            aria-label="Filter time period"
                            @click="showFilter = !showFilter"
                        >
                            <x-heroicon-o-funnel class="size-4" />
                        </button>
                        <div
                            x-show="showFilter"
                            x-cloak
                            @click.outside="showFilter = false"
                            @keydown.escape.window="showFilter = false"
                            class="absolute right-0 z-20 mt-2 w-44 rounded-lg border border-slate-200 bg-white py-1 shadow-lg"
                        >
                            @foreach($frequencyPeriodOptions as $value => $label)
                                <button
                                    type="button"
                                    wire:click="$set('period', '{{ $value }}')"
                                    @click="showFilter = false"
                                    class="block w-full px-4 py-2 text-left text-sm hover:bg-slate-50 {{ $period === $value ? 'bg-slate-50 font-medium text-slate-900' : 'text-slate-600' }}"
                                >
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </x-slot:actions>

                @if(count($frequency['days']) > 0)
                    <x-ui.stacked-bar-chart
                        wire:key="frequency-chart-{{ $period }}-{{ $customFrom }}-{{ $customTo }}"
                        canvas-id="frequency-chart"
                        title="Donations by Frequency"
                        :description="$frequencyDescription"
                        :data="$frequency"
                        height-class="h-80"
                    />
                @else
                    <div class="py-8 text-center text-sm text-slate-400">No donation data for this period</div>
                @endif
            </x-ui.card>
        </div>
    </div>

    {{-- Recent Donations --}}
    <x-ui.card title="Recent Donations" description="Last 10 donations in the selected period">
        @if($this->recentDonations->isNotEmpty())
            <div class="overflow-x-auto -mx-5 -my-5">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead>
                        <tr class="bg-slate-50">
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Donor</th>
                            <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Amount</th>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Campaign</th>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach($this->recentDonations as $donation)
                            <tr class="hover:bg-slate-50 cursor-pointer transition-colors" onclick="window.location='{{ route('app.donations.show', $donation) }}'">
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="size-6 rounded-full bg-slate-100 flex items-center justify-center">
                                            <x-heroicon-o-user class="size-3 text-slate-400" />
                                        </div>
                                        <span class="text-sm text-slate-900">{{ $donation->donor?->name ?? 'Anonymous' }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <x-donation-report-amount :donation="$donation" />
                                </td>
                                <td class="px-5 py-3 text-sm text-slate-600">
                                    {{ $donation->campaign?->title ?? '—' }}
                                </td>
                                <td class="px-5 py-3">
                                    <x-ui.badge status="{{ $donation->status->value }}" size="sm">
                                        {{ ucfirst($donation->status->value) }}
                                    </x-ui.badge>
                                </td>
                                <td class="px-5 py-3 text-sm text-slate-500">
                                    {{ myrTime($donation->created_at, withLabel: false, format: 'M d, Y') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="py-8 text-center text-sm text-slate-400">No donations in this period</div>
        @endif
    </x-ui.card>

    <livewire:app.campaigns.campaign-create-modal />
</div>
</div>
