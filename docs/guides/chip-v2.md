# CHIP v2 — confirm the fee rates before reopening the gateway

CHIP is locked. `CHIP_DONATIONS_ENABLED` defaults to `false`, so the option is
hidden from the processor picker and both campaign forms refuse a move onto it.
Campaigns already on CHIP keep working. Nothing in production uses it today: 0
CHIP donations, 0 campaigns on the gateway, though all 15 organizations have
`chip_enabled` set.

## Why it is locked

`DonationFeeEstimator` quotes a single blended CHIP rate that was carried over
from older figures and never measured against a settled transaction — and no
CHIP donation exists to measure it with. CHIP's published rates are per payment
method and nothing like a single blend:

| Payment method | CHIP published rate | What we quote |
| --- | --- | --- |
| FPX B2C | RM1.00 flat, no percentage | 2.5% + RM1.00 |
| FPX B2B | RM2.00 flat | 2.5% + RM1.00 |
| Local credit card | 2.0% | 2.5% + RM1.00 |
| Local debit card | 1.0% | 2.5% + RM1.00 |
| Foreign card | 3.0% | 2.5% + RM1.00 |
| E-wallet | 1.4% | 2.5% + RM1.00 |
| DuitNow QR | 1.0%, min RM0.15 | 2.5% + RM1.00 |

Transaction fees are subject to 8% SST. Source: <https://www.chip-in.asia/pricing>

On a RM100 FPX donation the donor fee cover therefore asks for about RM6.16
against a real cost of roughly RM1.08, overcharging the donor by around RM2.50.
On a foreign card it undercharges. Both directions are wrong, and the donor sees
the number before paying.

This is the same class of defect that was found and fixed for Stripe: the
formula was right and the constants were not. There, production balance
transactions settled the Stripe fee at exactly 3% + RM1.00 domestic and 6% +
RM1.00 for foreign currency, and the estimator was corrected to match. CHIP has
no such evidence available yet.

## What v2 has to do

1. **Get the contracted rates.** CHIP negotiates; the published table may not be
   what Ihsan pays. Needed per method: percentage, fixed amount, minimum, and
   whether SST is added on top or already included.
2. **Price per payment method, not per gateway.** The CHIP checkout already asks
   the donor to choose FPX or card before paying (`chipPaymentMethod`, plus the
   FPX bank picker), so the cover can be exact rather than blended. The
   `PROCESSOR_RATES` table in `DonationFeeEstimator` takes a gateway key today
   and needs a method layer under it. The estimate has to recompute when the
   donor changes method — it is shown at step 1 and the method is chosen later
   in the flow, so the two have to stay in sync.
3. **Handle SST explicitly.** Stripe's recorded fees carry no separate SST line;
   do not assume CHIP behaves the same way.
4. **Verify against a real settlement.** Put one live donation through each
   method, read `chip_fee` back off the donation, and check it against the
   quoted rate — the same method that caught the Stripe foreign-currency gap.
   Only then lift the flag.

## Related work already done

- `App\Actions\Chip\ChargeRecurringInstallment` had no state guard and never
  advanced `next_charge_at`, so the every-minute scheduler would have charged a
  CHIP subscriber repeatedly. Fixed, with the schedule itself acting as the
  claim, since CHIP offers no idempotency key.
- `App\Actions\Chip\FinalizeDonation` returned from its transaction closure
  rather than the method, re-queueing receipts and notifications on repeat
  callbacks. Fixed.
- The CHIP webhook now skips a replay of an event type it has already logged
  while still processing a progression, since CHIP identifies events by purchase
  ID.

## Lifting the lock

Set `CHIP_DONATIONS_ENABLED=true` once the rates are confirmed and the estimator
prices each method. The flag only controls whether campaigns may move onto CHIP;
it does not correct the rates.
