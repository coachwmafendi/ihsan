<?php

namespace App\Http\Controllers;

use App\Actions\Stripe\CreatePaymentIntent;
use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Support\ClientInfo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StripePaymentIntentController extends Controller
{
    public function __invoke(Request $request, CreatePaymentIntent $createPaymentIntent): JsonResponse
    {
        $validated = $request->validate([
            'campaign_id' => ['required', 'exists:campaigns,id'],
            'donor_first_name' => ['required', 'string', 'max:120'],
            'donor_last_name' => ['nullable', 'string', 'max:120'],
            'donor_email' => ['required', 'email', 'max:255'],
            'donor_phone' => ['nullable', 'string', 'max:40'],
            'amount' => ['required', 'numeric', 'min:1', 'max:100000'],
            'currency' => ['required', 'string', 'in:myr,usd,sgd'],
            'type' => ['required', 'in:one_time,monthly'],
        ]);

        $campaign = Campaign::query()->findOrFail($validated['campaign_id']);

        $donor = Donor::query()->updateOrCreate(
            ['email' => Str::lower($validated['donor_email'])],
            [
                'first_name' => $validated['donor_first_name'],
                'last_name' => $validated['donor_last_name'] ?? null,
                'phone' => $validated['donor_phone'] ?? null,
            ],
        );

        $clientInfo = ClientInfo::fromRequest($request);

        $donation = Donation::query()->create([
            'campaign_id' => $campaign->getKey(),
            'donor_id' => $donor->getKey(),
            'gross_amount' => $validated['amount'],
            'stripe_fee' => 0,
            'processing_fee' => 0,
            'net_amount' => $validated['amount'],
            'currency' => $validated['currency'],
            'status' => DonationStatus::Pending,
            'type' => $validated['type'] === 'monthly' ? DonationType::Recurring : DonationType::OneTime,
            ...$clientInfo,
        ]);

        try {
            $paymentIntent = $createPaymentIntent->create($donation);

            $donation->update([
                'stripe_payment_intent_id' => $paymentIntent->id,
            ]);

            return response()->json([
                'client_secret' => $paymentIntent->client_secret,
                'donation_id' => $donation->getKey(),
            ]);
        } catch (\Exception $e) {
            $stripeFeeDetails = $donation->stripe_fee_details ?? [];
            $stripeFeeDetails['last_payment_error'] = [
                'message' => $e->getMessage(),
                'decline_code' => null,
                'code' => null,
            ];

            $donation->update([
                'status' => DonationStatus::Failed,
                'stripe_fee_details' => $stripeFeeDetails,
            ]);

            throw ValidationException::withMessages([
                'payment' => ['Payment could not be processed. Please try again.'],
            ]);
        }
    }
}
