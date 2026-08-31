<?php

namespace App\Filament\Pages;

use App\Models\Donation;
use App\Models\Fraud\BlockedDonation;
use App\Models\Fraud\FraudAttempt;
use App\Models\Fraud\FraudRule;
use App\Support\ReportingPeriod;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FraudPrevention extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.admin.pages.fraud-prevention';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static ?string $navigationLabel = 'Fraud Prevention';

    protected static ?int $navigationSort = 25;

    public string $period = 'today';

    public function getStatsProperty(): array
    {
        [$from, $to] = $this->dateRange();

        $donations = Donation::query()
            ->when($from, fn (Builder $q) => $q->where('created_at', '>=', $from))
            ->when($to, fn (Builder $q) => $q->where('created_at', '<=', $to));

        $total = (clone $donations)->count();
        $blocked = (clone $donations)->where('fraud_status', 'blocked')->count();
        $flagged = (clone $donations)->where('fraud_status', 'flagged')->count();
        $highRisk = (clone $donations)->where('risk_score', '>=', 65)->count();

        return [
            'total' => $total,
            'blocked' => $blocked,
            'flagged' => $flagged,
            'high_risk' => $highRisk,
            'block_rate' => $total > 0 ? round(($blocked / $total) * 100, 2) : 0,
        ];
    }

    public function getRecentAttemptsProperty(): array
    {
        return FraudAttempt::query()
            ->with('donor')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn ($attempt) => [
                'id' => $attempt->id,
                'email' => $attempt->email,
                'amount' => $attempt->amount,
                'currency' => $attempt->currency,
                'reason' => $attempt->reason,
                'action' => $attempt->action,
                'created_at' => myrTime($attempt->created_at),
            ])
            ->all();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function (): Builder {
                return BlockedDonation::query()
                    ->with(['donation.campaign.organization', 'donation.donor', 'reviewer']);
            })
            ->columns([
                TextColumn::make('donation.donor.name')
                    ->label('Donor')
                    ->default('Unknown')
                    ->searchable(),
                TextColumn::make('donation.donor.email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('donation.campaign.title')
                    ->label('Campaign')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->donation?->campaign?->title ?? ''),
                TextColumn::make('donation.gross_amount')
                    ->label('Amount')
                    ->formatStateUsing(function (string $state, BlockedDonation $record): string {
                        $donation = $record->donation;
                        if (! $donation) {
                            return '—';
                        }

                        return strtoupper($donation->currency).' '.number_format((float) $donation->gross_amount, 2);
                    }),
                TextColumn::make('reason')
                    ->label('Reason')
                    ->badge()
                    ->color('danger'),
                TextColumn::make('donation.risk_score')
                    ->label('Risk Score')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state >= 80 => 'danger',
                        $state >= 65 => 'warning',
                        $state >= 40 => 'info',
                        default => 'success',
                    }),
                TextColumn::make('review_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('reviewer.name')
                    ->label('Reviewed By')
                    ->default('—'),
                TextColumn::make('created_at')
                    ->label('Blocked At')
                    ->dateTime('M j, Y H:i', timezone: 'Asia/Kuala_Lumpur')
                    ->formatStateUsing(fn ($state) => $state ? myrTime($state) : '—')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                // Actions will be added here later
            ]);
    }

    public function getRulesProperty(): array
    {
        return FraudRule::query()
            ->with('organization')
            ->orderBy('organization_id')
            ->orderBy('rule_type')
            ->get()
            ->map(fn ($rule) => [
                'id' => $rule->id,
                'organization' => $rule->organization?->name ?? 'Global',
                'type' => str($rule->rule_type)->headline()->toString(),
                'config' => collect($rule->config ?? [])
                    ->map(fn ($value, $key) => is_array($value)
                        ? "{$key}: ".json_encode($value)
                        : "{$key}: {$value}")
                    ->join(', '),
                'action' => $rule->action,
                'is_active' => $rule->is_active,
            ])
            ->all();
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function dateRange(): array
    {
        // Malaysian days, handed to the query as UTC instants.
        return ReportingPeriod::platform()->utc(match ($this->period) {
            'last_7_days' => '7_days',
            'last_30_days' => '30_days',
            default => $this->period,
        });
    }
}
