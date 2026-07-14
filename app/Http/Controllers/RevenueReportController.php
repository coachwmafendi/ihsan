<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\User;
use App\Services\RevenueReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RevenueReportController extends Controller
{
    private const ALLOWED_FORMATS = ['csv', 'pdf'];

    private const ALLOWED_PERIODS = [
        'today',
        'yesterday',
        'last_7_days',
        'last_30_days',
        'last_90_days',
        'last_week',
        'last_month',
        'last_6_months',
        'last_year',
        'this_week',
        'this_month',
        'this_year',
        'all_time',
    ];

    public function __construct(private RevenueReportService $reportService) {}

    public function download(string $organizationPublicId, string $format, ?string $period = null): StreamedResponse|Response
    {
        /** @var User|null $user */
        $user = auth()->user();

        abort_unless($user !== null && $user->isSuperAdmin(), 403);

        if (! in_array($format, self::ALLOWED_FORMATS, true)) {
            throw new NotFoundHttpException;
        }

        $period ??= 'this_month';

        if (! in_array($period, self::ALLOWED_PERIODS, true)) {
            throw new NotFoundHttpException;
        }

        $organization = Organization::query()
            ->where('public_id', $organizationPublicId)
            ->first();

        if ($organization === null) {
            throw new NotFoundHttpException;
        }

        $row = $this->reportService->rowForOrganization($organization, $period);

        if ($row === null) {
            throw new NotFoundHttpException;
        }

        $sanitizedName = Str::limit(Str::slug($organization->name, '-'), 50, '');
        $date = now()->format('Y-m-d');
        $filename = "ihsan-{$organization->public_id}-{$sanitizedName}-revenue-{$period}-{$date}.{$format}";

        return match ($format) {
            'csv' => $this->csvResponse($row, $period, $filename),
            'pdf' => $this->pdfResponse($row, $period, $filename),
        };
    }

    private function csvResponse(array $row, string $period, string $filename): StreamedResponse
    {
        $periodLabel = $this->reportService->periodLabel($period);
        $dateRangeLabel = $this->reportService->periodDateRangeLabel($period);

        return response()->streamDownload(function () use ($row, $periodLabel, $dateRangeLabel): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Organization', $row['name']]);
            fputcsv($handle, ['Public ID', $row['public_id']]);
            fputcsv($handle, ['Period', $periodLabel]);
            fputcsv($handle, ['Date range', $dateRangeLabel]);
            fputcsv($handle, ['Generated at', now()->toDateTimeString()]);
            fputcsv($handle, []);

            fputcsv($handle, ['Metric', 'Value']);
            fputcsv($handle, ['Total donations', $row['donations']]);
            fputcsv($handle, ['Donation volume', number_format($row['volume_raw'], 2)]);
            fputcsv($handle, ['Average donation size', number_format($row['avg_donation_raw'], 2)]);
            fputcsv($handle, ['Total Stripe / processing fees', number_format($row['fees_raw'], 2)]);
            fputcsv($handle, ['Effective fee rate', number_format($row['effective_rate_raw'], 2).'%']);

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function pdfResponse(array $row, string $period, string $filename): Response
    {
        $pdf = Pdf::loadView('filament.admin.exports.revenue-organization-report', [
            'row' => $row,
            'period' => $this->reportService->periodLabel($period),
            'dateRange' => $this->reportService->periodDateRangeLabel($period),
            'generatedAt' => now()->toDateTimeString(),
        ]);

        return $pdf->download($filename);
    }
}
