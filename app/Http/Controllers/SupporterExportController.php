<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupporterExportController extends Controller
{
    /**
     * @var array<int, array{key: string, label: string, default?: bool}>
     */
    public static array $fields = [
        ['key' => 'public_id', 'label' => 'Supporter ID', 'default' => true],
        ['key' => 'name', 'label' => 'Name', 'default' => true],
        ['key' => 'email', 'label' => 'Email', 'default' => true],
        ['key' => 'phone', 'label' => 'Phone'],
        ['key' => 'title', 'label' => 'Title'],
        ['key' => 'occupation', 'label' => 'Occupation'],
        ['key' => 'address_line1', 'label' => 'Address Line 1'],
        ['key' => 'address_line2', 'label' => 'Address Line 2'],
        ['key' => 'address_city', 'label' => 'City'],
        ['key' => 'address_state', 'label' => 'State'],
        ['key' => 'address_postal_code', 'label' => 'Postal Code'],
        ['key' => 'country', 'label' => 'Country'],
        ['key' => 'locale', 'label' => 'Locale'],
        ['key' => 'lifetime_total_myr', 'label' => 'Lifetime Total (MYR)', 'default' => true],
        ['key' => 'first_donation', 'label' => 'First Donation'],
        ['key' => 'last_donation', 'label' => 'Last Donation'],
        ['key' => 'created_at', 'label' => 'Created At'],
    ];

    public function __invoke(Request $request)
    {
        $user = Auth::user();
        $org = $user?->organization;

        $format = $request->input('format') ?? 'csv';
        $selectedFields = $request->input('fields') ?? [];

        if (! is_array($selectedFields) || $selectedFields === []) {
            return redirect()->route('app.supporters.index');
        }

        $validKeys = collect(self::$fields)->pluck('key')->all();
        $fields = array_values(array_filter($selectedFields, fn (string $key): bool => in_array($key, $validKeys, true)));

        if ($fields === []) {
            return redirect()->route('app.supporters.index');
        }

        $query = $this->buildQuery($request, $org);
        $donors = $query->get();

        $fileName = 'supporters_'.now()->format('Y-m-d');

        return match ($format) {
            'xls' => $this->exportAsXls($donors, $fields, $fileName),
            default => $this->exportAsCsv($donors, $fields, $fileName),
        };
    }

    private function buildQuery(Request $request, ?Organization $org): Builder
    {
        $query = Donor::query()
            ->select('donors.*')
            ->when($org, function (Builder $q) use ($org): void {
                $q->whereHas('donations.campaign', fn (Builder $cq) => $cq->where('organization_id', $org->id));
            })
            ->when(! $org, fn (Builder $q) => $q->whereRaw('1 = 0'))
            ->withCount('donations')
            ->withMin('donations', 'created_at')
            ->withMax('donations', 'created_at')
            ->selectSub(
                fn ($q) => $q->from('donations')
                    ->whereColumn('donations.donor_id', 'donors.id')
                    ->select(Donation::reportSumColumn()),
                'lifetime_report_amount'
            );

        $search = $request->input('search') ?? '';
        $period = $request->input('period') ?? 'all_time';
        $dateFrom = $request->input('dateFrom') ?? '';
        $dateTo = $request->input('dateTo') ?? '';

        if (filled($search)) {
            $like = '%'.$search.'%';
            $query->where(function (Builder $q) use ($like): void {
                $q->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like);
            });
        }

        [$start, $end] = $this->periodRange($period, $dateFrom, $dateTo);

        if ($start !== null && $end !== null) {
            $query->whereHas('donations', function (Builder $q) use ($start, $end): void {
                $q->whereBetween('created_at', [$start, $end]);
            });
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
     * @param  Collection<int, Donor>  $donors
     * @param  array<int, string>  $fields
     */
    private function exportAsCsv($donors, array $fields, string $fileName): StreamedResponse
    {
        $labels = $this->fieldLabels($fields);

        return response()->streamDownload(function () use ($donors, $fields, $labels): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, $labels);

            foreach ($donors as $donor) {
                $row = [];
                foreach ($fields as $field) {
                    $row[] = $this->sanitizeCsvValue($this->resolveFieldValue($donor, $field));
                }
                fputcsv($output, $row);
            }

            fclose($output);
        }, $fileName.'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @param  Collection<int, Donor>  $donors
     * @param  array<int, string>  $fields
     */
    private function exportAsXls($donors, array $fields, string $fileName): StreamedResponse
    {
        $labels = $this->fieldLabels($fields);

        return response()->streamDownload(function () use ($donors, $fields, $labels): void {
            echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
            echo "<Workbook xmlns=\"urn:schemas-microsoft-com:office:spreadsheet\" xmlns:ss=\"urn:schemas-microsoft-com:office:spreadsheet\">\n";
            echo "<Worksheet ss:Name=\"Supporters\"><Table>\n";

            echo '<Row>';
            foreach ($labels as $label) {
                echo '<Cell><Data ss:Type="String">'.$this->escapeXml($label).'</Data></Cell>';
            }
            echo "</Row>\n";

            foreach ($donors as $donor) {
                echo '<Row>';
                foreach ($fields as $field) {
                    $value = $this->resolveFieldValue($donor, $field);
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

    private function resolveFieldValue(Donor $donor, string $field): string
    {
        $value = match ($field) {
            'public_id' => $donor->public_id,
            'name' => Str::title($donor->name ?? ''),
            'email' => $donor->email ?? '',
            'country' => strtoupper($donor->country ?? ''),
            'lifetime_total_myr' => 'MYR '.number_format((float) ($donor->lifetime_report_amount ?? 0), 2),
            'first_donation' => $donor->donations_min_created_at ? Carbon::parse($donor->donations_min_created_at)->format('Y-m-d') : '',
            'last_donation' => $donor->donations_max_created_at ? Carbon::parse($donor->donations_max_created_at)->format('Y-m-d') : '',
            'created_at' => $donor->created_at?->format('Y-m-d H:i:s') ?? '',
            default => data_get($donor, $field) ?? '',
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
