<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\DonationStatus;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Organization;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MonthlyDonationReportController extends Controller
{
    private const ALLOWED_FORMATS = ['csv', 'pdf'];

    public function download(Request $request): StreamedResponse
    {
        $format = $request->query('format', 'csv');

        if (! in_array($format, self::ALLOWED_FORMATS, true)) {
            throw new NotFoundHttpException;
        }

        $organization = Auth::user()?->organization;

        abort_unless($organization instanceof Organization, 403);

        [$from, $to] = $this->dateRange($request);

        $summary = $this->summary($organization, $from, $to);
        $campaigns = $this->campaignBreakdown($organization, $from, $to);

        $sanitizedName = Str::limit(Str::slug($organization->name, '-'), 50, '');
        $periodSlug = "{$from->toDateString()}-to-{$to->toDateString()}";
        $filename = "ihsan-{$organization->public_id}-{$sanitizedName}-monthly-donations-{$periodSlug}.{$format}";

        return match ($format) {
            'csv' => $this->csvResponse($organization, $summary, $campaigns, $from, $to, $filename),
            'pdf' => $this->pdfResponse($organization, $summary, $campaigns, $from, $to, $filename),
        };
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function dateRange(Request $request): array
    {
        $dateFrom = $request->query('dateFrom');
        $dateTo = $request->query('dateTo');

        if (blank($dateFrom) || blank($dateTo)) {
            $from = now()->startOfMonth();
            $to = now()->endOfMonth();
        } else {
            $from = CarbonImmutable::parse($dateFrom)->startOfDay();
            $to = CarbonImmutable::parse($dateTo)->endOfDay();
        }

        if ($from->gt($to)) {
            [$from, $to] = [$to->startOfDay(), $from->endOfDay()];
        }

        return [
            CarbonImmutable::instance($from),
            CarbonImmutable::instance($to),
        ];
    }

    /**
     * @return array{
     *     total_gross: float,
     *     processing_fee: float,
     *     net_received: float,
     *     total_donations: int,
     *     unique_donors: int,
     *     has_approximations: bool
     * }
     */
    private function summary(Organization $organization, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $donations = Donation::query()
            ->whereHas('campaign', fn ($q) => $q->where('organization_id', $organization->id))
            ->where('status', DonationStatus::Succeeded)
            ->where('donations.created_at', '>=', $from)
            ->where('donations.created_at', '<=', $to);

        $result = (clone $donations)
            ->selectRaw('COALESCE(SUM('.Donation::reportAmountSql().'), 0) as total_gross')
            ->selectRaw('COALESCE(SUM(processing_fee + stripe_fee), 0) as processing_fee')
            ->selectRaw('COALESCE(SUM(net_amount), 0) as net_received')
            ->selectRaw('COUNT(*) as total_donations')
            ->selectRaw('COUNT(DISTINCT donor_id) as unique_donors')
            ->first();

        return [
            'total_gross' => (float) $result->total_gross,
            'processing_fee' => (float) $result->processing_fee,
            'net_received' => (float) $result->net_received,
            'total_donations' => (int) $result->total_donations,
            'unique_donors' => (int) $result->unique_donors,
            'has_approximations' => Donation::hasReportApproximations($donations),
        ];
    }

    /**
     * @return Collection<int, Campaign>
     */
    private function campaignBreakdown(Organization $organization, CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        return Campaign::query()
            ->where('organization_id', $organization->id)
            ->select('campaigns.*')
            ->withCount([
                'donations' => fn ($q) => $q
                    ->where('status', DonationStatus::Succeeded)
                    ->where('created_at', '>=', $from)
                    ->where('created_at', '<=', $to),
            ])
            ->selectSub(
                fn ($q) => $q->from('donations')
                    ->whereColumn('donations.campaign_id', 'campaigns.id')
                    ->where('donations.status', DonationStatus::Succeeded)
                    ->where('donations.created_at', '>=', $from)
                    ->where('donations.created_at', '<=', $to)
                    ->selectRaw('COALESCE(SUM('.Donation::reportAmountSql().'), 0)'),
                'gross_amount'
            )
            ->selectSub(
                fn ($q) => $q->from('donations')
                    ->whereColumn('donations.campaign_id', 'campaigns.id')
                    ->where('donations.status', DonationStatus::Succeeded)
                    ->where('donations.created_at', '>=', $from)
                    ->where('donations.created_at', '<=', $to)
                    ->selectRaw('COALESCE(SUM(donations.processing_fee + donations.stripe_fee), 0)'),
                'processing_fee'
            )
            ->selectSub(
                fn ($q) => $q->from('donations')
                    ->whereColumn('donations.campaign_id', 'campaigns.id')
                    ->where('donations.status', DonationStatus::Succeeded)
                    ->where('donations.created_at', '>=', $from)
                    ->where('donations.created_at', '<=', $to)
                    ->selectRaw('COALESCE(SUM(donations.net_amount), 0)'),
                'net_amount'
            )
            ->orderByDesc('gross_amount')
            ->get();
    }

    /**
     * @param  Collection<int, Campaign>  $campaigns
     * @param  array<string, mixed>  $summary
     */
    private function csvResponse(
        Organization $organization,
        array $summary,
        Collection $campaigns,
        CarbonImmutable $from,
        CarbonImmutable $to,
        string $filename,
    ): StreamedResponse {
        return response()->streamDownload(function () use ($organization, $summary, $campaigns, $from, $to): void {
            $handle = fopen('php://output', 'w');
            $generatedAt = now()->setTimezone('Asia/Kuala_Lumpur')->toDateTimeString().' (MYT)';

            fputcsv($handle, ['Organization', $organization->name]);
            fputcsv($handle, ['Public ID', $organization->public_id]);
            fputcsv($handle, ['Report type', 'Report']);
            fputcsv($handle, ['Period', $from->toDateString().' to '.$to->toDateString()]);
            fputcsv($handle, ['Generated at', $generatedAt]);
            fputcsv($handle, []);

            fputcsv($handle, ['Metric', 'Value']);
            fputcsv($handle, ['Total gross', number_format($summary['total_gross'], 2)]);
            fputcsv($handle, ['Processing fee', number_format($summary['processing_fee'], 2)]);
            fputcsv($handle, ['Net received', number_format($summary['net_received'], 2)]);
            fputcsv($handle, ['Total donations', $summary['total_donations']]);
            fputcsv($handle, ['Unique donors', $summary['unique_donors']]);
            fputcsv($handle, []);

            fputcsv($handle, ['Campaign', 'Donations', 'Gross', 'Processing Fee', 'Net']);

            foreach ($campaigns as $campaign) {
                fputcsv($handle, [
                    $campaign->title,
                    $campaign->donations_count,
                    number_format((float) $campaign->gross_amount, 2),
                    number_format((float) $campaign->processing_fee, 2),
                    number_format((float) $campaign->net_amount, 2),
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * @param  Collection<int, Campaign>  $campaigns
     * @param  array<string, mixed>  $summary
     */
    private function pdfResponse(
        Organization $organization,
        array $summary,
        Collection $campaigns,
        CarbonImmutable $from,
        CarbonImmutable $to,
        string $filename,
    ): StreamedResponse {
        return response()->streamDownload(function () use ($organization, $summary, $campaigns, $from, $to): void {
            $pdf = Pdf::loadView('exports.monthly-donations-report', [
                'organization' => $organization,
                'summary' => $summary,
                'campaigns' => $campaigns,
                'from' => $from,
                'to' => $to,
                'generatedAt' => now()->setTimezone('Asia/Kuala_Lumpur')->toDateTimeString().' (MYT)',
            ]);

            echo $pdf->output();
        }, $filename, ['Content-Type' => 'application/pdf']);
    }
}
