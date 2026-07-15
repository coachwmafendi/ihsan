# Webhooks

Ihsan can send webhook events to your server when key events happen.

## Common events

- `donation.created` — a new donation was received.
- `subscription.created` — a recurring subscription started.
- `subscription.cancelled` — a subscription was cancelled.
- `refund.created` — a refund was processed.

## Setting up webhooks

1. Provide a publicly reachable HTTPS endpoint in your organization settings.
2. Verify the webhook signature using the shared secret.
3. Respond with a `200 OK` status after processing the event.

Set up a staging endpoint first to test payload handling before going live.
