<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Donation;
use App\Models\Organization;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DonationExportController extends Controller
{
    /**
     * @var array<int, array{key: string, label: string, default?: bool}>
     */
    public static array $fields = [
        ['key' => 'public_id', 'label' => 'Donation ID', 'default' => true],
        ['key' => 'created_at', 'label' => 'Date', 'default' => true],
        ['key' => 'status', 'label' => 'Donation Status', 'default' => true],
        ['key' => 'type', 'label' => 'Donation Frequency', 'default' => true],
        ['key' => 'donor.name', 'label' => 'Supporter Name', 'default' => true],
        ['key' => 'donor.email', 'label' => 'Supporter Email', 'default' => true],
        ['key' => 'donor.phone', 'label' => 'Supporter Phone'],
        ['key' => 'gross_amount', 'label' => 'Gross Amount', 'default' => true],
        ['key' => 'currency', 'label' => 'Currency', 'default' => true],
        ['key' => 'base_amount', 'label' => 'Base Amount (MYR)'],
        ['key' => 'net_amount', 'label' => 'Net Amount'],
        ['key' => 'stripe_fee', 'label' => 'Stripe Fee'],
        ['key' => 'processing_fee', 'label' => 'Processing Fee'],
        ['key' => 'donor_fee_covered', 'label' => 'Fee Covered by Donor'],
        ['key' => 'payment_method_type', 'label' => 'Payment Method'],
        ['key' => 'payment_method_brand', 'label' => 'Card Brand'],
        ['key' => 'payment_method_last4', 'label' => 'Card Last 4'],
        ['key' => 'campaign.title', 'label' => 'Campaign', 'default' => true],
        ['key' => 'subscription.interval', 'label' => 'Recurring Interval'],
        ['key' => 'subscription.payment_count', 'label' => 'Installment #'],
        ['key' => 'billing_address_line1', 'label' => 'Billing Address Line 1'],
        ['key' => 'billing_address_city', 'label' => 'Billing City'],
        ['key' => 'billing_address_state', 'label' => 'Billing State'],
        ['key' => 'billing_address_postal_code', 'label' => 'Billing Postal Code'],
        ['key' => 'billing_country', 'label' => 'Billing Country'],
        ['key' => 'donor_country', 'label' => 'Donor Country'],
        ['key' => 'device_type', 'label' => 'Device Type'],
        ['key' => 'is_anonymous', 'label' => 'Anonymous'],
        ['key' => 'donor_message', 'label' => 'Message'],
    ];

    public function __invoke(Request $request)
    {
        $user = Auth::user();
        $org = $user?->organization;

        $format = $request->input('format', 'csv');
        $selectedFields = $request->input('fields', []);

        if (! is_array($selectedFields) || $selectedFields === []) {
            return redirect()->route('app.donations.index');
        }

        $validKeys = collect(self::$fields)->pluck('key')->all();
        $fields = array_values(array_filter($selectedFields, fn (string $key): bool => in_array($key, $validKeys, true)));

        if ($fields === []) {
            return redirect()->route('app.donations.index');
        }

        $query = $this->buildQuery($request, $org);
        $donations = $query->with(['campaign', 'donor', 'subscription'])->get();

        $fileName = 'donations_'.now()->format('Y-m-d');

        return match ($format) {
            'xls' => $this->exportAsXls($donations, $fields, $fileName),
            default => $this->exportAsCsv($donations, $fields, $fileName),
        };
    }

    private function buildQuery(Request $request, ?Organization $org): Builder
    {
        $query = Donation::query()
            ->when($org, function (Builder $q) use ($org): void {
                $q->whereHas('campaign', fn (Builder $cq) => $cq->where('organization_id', $org->id));
            })
            ->when(! $org, fn (Builder $q) => $q->whereRaw('1 = 0'));

        $search = $request->input('search') ?? '';
        $statusFilter = $request->input('statusFilter') ?? '';
        $campaignFilter = $request->input('campaignFilter') ?? '';
        $frequencyFilter = $request->input('frequencyFilter') ?? '';
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

        if (filled($frequencyFilter)) {
            $query->where('type', $frequencyFilter);
        }

        [$start, $end] = $this->periodRange($period, $dateFrom, $dateTo);

        if ($start !== null && $end !== null) {
            $query->whereBetween('donations.created_at', [$start, $end]);
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
     * @param  Collection<int, Donation>  $donations
     * @param  array<int, string>  $fields
     */
    private function exportAsCsv($donations, array $fields, string $fileName): StreamedResponse
    {
        $labels = $this->fieldLabels($fields);

        return response()->streamDownload(function () use ($donations, $fields, $labels): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, $labels);

            foreach ($donations as $donation) {
                $row = [];
                foreach ($fields as $field) {
                    $row[] = $this->sanitizeCsvValue($this->resolveFieldValue($donation, $field));
                }
                fputcsv($output, $row);
            }

            fclose($output);
        }, $fileName.'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @param  Collection<int, Donation>  $donations
     * @param  array<int, string>  $fields
     */
    private function exportAsXls($donations, array $fields, string $fileName): StreamedResponse
    {
        $labels = $this->fieldLabels($fields);

        return response()->streamDownload(function () use ($donations, $fields, $labels): void {
            echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
            echo "<Workbook xmlns=\"urn:schemas-microsoft-com:office:spreadsheet\" xmlns:ss=\"urn:schemas-microsoft-com:office:spreadsheet\">\n";
            echo "<Worksheet ss:Name=\"Donations\"><Table>\n";

            echo '<Row>';
            foreach ($labels as $label) {
                echo '<Cell><Data ss:Type="String">'.$this->escapeXml($label).'</Data></Cell>';
            }
            echo "</Row>\n";

            foreach ($donations as $donation) {
                echo '<Row>';
                foreach ($fields as $field) {
                    $value = $this->resolveFieldValue($donation, $field);
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

    private function resolveFieldValue(Donation $donation, string $field): string
    {
        $value = match ($field) {
            'public_id' => $donation->public_id,
            'created_at' => $donation->created_at?->format('Y-m-d H:i:s') ?? '',
            'status' => ucfirst($donation->status->value),
            'type' => ucfirst($donation->type->value),
            'currency' => strtoupper($donation->currency),
            'gross_amount' => number_format((float) $donation->gross_amount, 2),
            'base_amount' => $donation->base_amount !== null ? number_format((float) $donation->base_amount, 2) : '',
            'net_amount' => number_format((float) $donation->net_amount, 2),
            'stripe_fee' => number_format((float) $donation->stripe_fee, 2),
            'processing_fee' => number_format((float) $donation->processing_fee, 2),
            'donor_fee_covered' => number_format((float) $donation->donor_fee_covered, 2),
            'payment_method_type' => ucfirst($donation->payment_method_type ?? ''),
            'payment_method_brand' => $donation->payment_method_brand ? ucfirst($donation->payment_method_brand) : '',
            'device_type' => ucfirst($donation->device_type ?? ''),
            'is_anonymous' => $donation->is_anonymous ? 'Yes' : 'No',
            'donor_message' => $donation->donor_message ?? '',
            default => data_get($donation, $field) ?? '',
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
