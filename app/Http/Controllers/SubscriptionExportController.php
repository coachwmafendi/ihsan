<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubscriptionExportController extends Controller
{
    /**
     * @var array<int, array{key: string, label: string, default?: bool}>
     */
    public static array $fields = [
        ['key' => 'public_id', 'label' => 'Subscription ID', 'default' => true],
        ['key' => 'created_at', 'label' => 'Created', 'default' => true],
        ['key' => 'status', 'label' => 'Status', 'default' => true],
        ['key' => 'amount', 'label' => 'Amount', 'default' => true],
        ['key' => 'currency', 'label' => 'Currency', 'default' => true],
        ['key' => 'interval', 'label' => 'Interval', 'default' => true],
        ['key' => 'payment_count', 'label' => 'Installments', 'default' => true],
        ['key' => 'donor.name', 'label' => 'Supporter Name', 'default' => true],
        ['key' => 'donor.email', 'label' => 'Supporter Email', 'default' => true],
        ['key' => 'donor.phone', 'label' => 'Supporter Phone'],
        ['key' => 'campaign.title', 'label' => 'Campaign', 'default' => true],
        ['key' => 'current_period_start', 'label' => 'Current Period Start'],
        ['key' => 'current_period_end', 'label' => 'Current Period End'],
        ['key' => 'cancelled_at', 'label' => 'Cancelled At'],
        ['key' => 'paused_until', 'label' => 'Paused Until'],
        ['key' => 'cancel_at', 'label' => 'Cancel At'],
        ['key' => 'cover_fee', 'label' => 'Cover Fee'],
        ['key' => 'fee_cover_amount', 'label' => 'Fee Cover Amount'],
        ['key' => 'max_plan_amount', 'label' => 'Max Plan Amount'],
        ['key' => 'max_plan_installments', 'label' => 'Max Plan Installments'],
        ['key' => 'stripe_subscription_id', 'label' => 'Stripe Subscription ID'],
        ['key' => 'source', 'label' => 'Source'],
    ];

    public function __invoke(Request $request)
    {
        $user = Auth::user();
        $org = $user?->organization;

        $format = $request->input('format') ?? 'csv';
        $selectedFields = $request->input('fields') ?? [];

        if (! is_array($selectedFields) || $selectedFields === []) {
            return redirect()->route('app.recurring-plans');
        }

        $validKeys = collect(self::$fields)->pluck('key')->all();
        $fields = array_values(array_filter($selectedFields, fn (string $key): bool => in_array($key, $validKeys, true)));

        if ($fields === []) {
            return redirect()->route('app.recurring-plans');
        }

        $query = $this->buildQuery($request, $org);
        $subscriptions = $query->with(['campaign', 'donor'])->get();

        $fileName = 'recurring-plans_'.now()->format('Y-m-d');

        return match ($format) {
            'xls' => $this->exportAsXls($subscriptions, $fields, $fileName),
            default => $this->exportAsCsv($subscriptions, $fields, $fileName),
        };
    }

    private function buildQuery(Request $request, ?Organization $org): Builder
    {
        $query = Subscription::query()
            ->when($org, function (Builder $q) use ($org): void {
                $q->whereHas('campaign', fn (Builder $cq) => $cq->where('organization_id', $org->id));
            })
            ->when(! $org, fn (Builder $q) => $q->whereRaw('1 = 0'));

        $search = $request->input('search') ?? '';
        $statusFilter = $request->input('statusFilter') ?? '';
        $campaignFilter = $request->input('campaignFilter') ?? '';
        $period = $request->input('period') ?? 'all_time';
        $dateFrom = $request->input('dateFrom') ?? '';
        $dateTo = $request->input('dateTo') ?? '';

        if (filled($search)) {
            $like = '%'.$search.'%';
            $query->whereHas('donor', function (Builder $q) use ($like): void {
                $q->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like);
            });
        }

        if (filled($statusFilter)) {
            $query->where('status', $statusFilter);
        }

        if (filled($campaignFilter)) {
            $query->where('campaign_id', $campaignFilter);
        }

        [$start, $end] = $this->periodRange($period, $dateFrom, $dateTo);

        if ($start !== null && $end !== null) {
            $query->whereBetween('subscriptions.created_at', [$start, $end]);
        }

        return $query;
    }

    /**
     * @return array{?Carbon, ?Carbon}
     */
    private function periodRange(string $period, ?string $dateFrom, ?string $dateTo): array
    {
        if ($period === 'custom' && filled($dateFrom) && filled($dateTo)) {
            return [
                Carbon::parse($dateFrom)->startOfDay(),
                Carbon::parse($dateTo)->endOfDay(),
            ];
        }

        return match ($period) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'yesterday' => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
            '7_days' => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
            '14_days' => [now()->subDays(13)->startOfDay(), now()->endOfDay()],
            '30_days' => [now()->subDays(29)->startOfDay(), now()->endOfDay()],
            '90_days' => [now()->subDays(89)->startOfDay(), now()->endOfDay()],
            'this_week' => [now()->startOfWeek(), now()->endOfWeek()],
            'this_month' => [now()->startOfMonth(), now()->endOfMonth()],
            'this_year' => [now()->startOfYear(), now()->endOfYear()],
            'last_week' => [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()],
            'last_month' => [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()],
            'last_year' => [now()->subYear()->startOfYear(), now()->subYear()->endOfYear()],
            default => [null, null],
        };
    }

    /**
     * @param  Collection<int, Subscription>  $subscriptions
     * @param  array<int, string>  $fields
     */
    private function exportAsCsv($subscriptions, array $fields, string $fileName): StreamedResponse
    {
        $labels = $this->fieldLabels($fields);

        return response()->streamDownload(function () use ($subscriptions, $fields, $labels): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, $labels);

            foreach ($subscriptions as $subscription) {
                $row = [];
                foreach ($fields as $field) {
                    $row[] = $this->sanitizeCsvValue($this->resolveFieldValue($subscription, $field));
                }
                fputcsv($output, $row);
            }

            fclose($output);
        }, $fileName.'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @param  Collection<int, Subscription>  $subscriptions
     * @param  array<int, string>  $fields
     */
    private function exportAsXls($subscriptions, array $fields, string $fileName): StreamedResponse
    {
        $labels = $this->fieldLabels($fields);

        return response()->streamDownload(function () use ($subscriptions, $fields, $labels): void {
            echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
            echo "<Workbook xmlns=\"urn:schemas-microsoft-com:office:spreadsheet\" xmlns:ss=\"urn:schemas-microsoft-com:office:spreadsheet\">\n";
            echo "<Worksheet ss:Name=\"Recurring Plans\"><Table>\n";

            echo '<Row>';
            foreach ($labels as $label) {
                echo '<Cell><Data ss:Type="String">'.$this->escapeXml($label).'</Data></Cell>';
            }
            echo "</Row>\n";

            foreach ($subscriptions as $subscription) {
                echo '<Row>';
                foreach ($fields as $field) {
                    $value = $this->resolveFieldValue($subscription, $field);
                    echo '<Cell><Data ss:Type="String">'.$this->escapeXml($value).'</Data></Cell>';
                }
                echo "</Row>\n";
            }

            echo "</Table></Worksheet>\n";
            echo "</Workbook>\n";
        }, $fileName.'.xls', [
            'Content-Type' => 'application/vnd.ms-excel',
        ]);
    }

    /**
     * @param  array<int, string>  $fields
     * @return array<int, string>
     */
    private function fieldLabels(array $fields): array
    {
        $map = collect(self::$fields)->mapWithKeys(fn (array $field): array => [$field['key'] => $field['label']]);

        return collect($fields)->map(fn (string $key): string => $map[$key] ?? $key)->all();
    }

    private function resolveFieldValue(Subscription $subscription, string $field): string
    {
        $value = match ($field) {
            'public_id' => $subscription->public_id,
            'created_at' => $subscription->created_at?->format('Y-m-d H:i:s') ?? '',
            'status' => $subscription->status->getLabel(),
            'currency' => strtoupper($subscription->currency),
            'amount' => number_format((float) $subscription->amount, 2),
            'interval' => ucfirst($subscription->interval->value),
            'payment_count' => (string) $subscription->payment_count,
            'cover_fee' => $subscription->cover_fee ? 'Yes' : 'No',
            'fee_cover_amount' => $subscription->fee_cover_amount !== null ? number_format((float) $subscription->fee_cover_amount, 2) : '',
            'max_plan_amount' => $subscription->max_plan_amount !== null ? number_format((float) $subscription->max_plan_amount, 2) : '',
            'max_plan_installments' => $subscription->max_plan_installments !== null ? (string) $subscription->max_plan_installments : '',
            'current_period_start' => $subscription->current_period_start?->format('Y-m-d H:i:s') ?? '',
            'current_period_end' => $subscription->current_period_end?->format('Y-m-d H:i:s') ?? '',
            'cancelled_at' => $subscription->cancelled_at?->format('Y-m-d H:i:s') ?? '',
            'paused_until' => $subscription->paused_until?->format('Y-m-d H:i:s') ?? '',
            'cancel_at' => $subscription->cancel_at?->format('Y-m-d H:i:s') ?? '',
            'source' => $subscription->source ?? '',
            default => data_get($subscription, $field) ?? '',
        };

        if ($value instanceof \BackedEnum) {
            $value = $value->value;
        }

        return (string) $value;
    }

    private function sanitizeCsvValue(string $value): string
    {
        if ($value === '') {
            return $value;
        }

        $first = substr($value, 0, 1);

        if (in_array($first, ['=', '+', '-', '@', "\t", "\r", "\n"], true)) {
            return "'".$value;
        }

        return $value;
    }

    private function escapeXml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
