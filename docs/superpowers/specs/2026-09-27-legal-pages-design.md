# Privacy Policy and Terms of Service Pages — Design

## Goal

Ihsan has no privacy policy and no terms of service anywhere: not a page, not a
link, not a stored URL. Publish both as pages on the landing site and link to
them from its footer, so the platform has a public address to give a payment
processor, an ad network or a donor who asks what happens to their data.

## Decisions

| Question | Decision |
| --- | --- |
| Whose policy | Ihsan's own, platform-wide. Not per-organization. |
| Pages | Built in this application, not linked out to a document elsewhere. |
| Language | English only. Footer link labels translate to Malay. |
| Where linked | Landing footer only. |
| Content | Drafted here; reviewed by the user and a lawyer before merging to `main`. |
| Approach | Blade views behind static routes, matching `Route::view('/', 'welcome')`. |

English is the authoritative text: Stripe, Meta and Google reviewers read these
pages in English during verification, and one document means one thing to keep
current rather than two that drift.

## Scope

### In scope

- `/privacy` and `/terms` routes and views on the landing domain.
- Extracting the landing footer into a component so all three pages share it.
- Two footer links, with translated labels in `lang/en.json` and `lang/ms.json`.
- Drafted content for both documents.
- Tests for the routes, the footer links and the translated labels.

### Out of scope

- Links from the checkout, the donor portal, the NGO panel or the auth screens.
- Malay translations of the documents themselves.
- A cookie consent banner.
- Per-organization policy URLs.

## Architecture

### Routes

In `routes/web.php`, beside the existing landing routes:

```php
Route::view('/privacy', 'legal.privacy')->name('legal.privacy');
Route::view('/terms', 'legal.terms')->name('legal.terms');
```

Short paths, because they are typed into verification forms by hand.

### The footer, extracted

The landing footer is written inline in `welcome.blade.php` rather than in
`x-layouts::landing`. A page wrapped in that layout gets the nav and no footer —
and the footer is exactly where these links belong.

Extract it to `resources/views/components/landing-footer.blade.php` and render
`<x-landing-footer />` from `welcome.blade.php` and both legal views. The links
are then written once and every page carrying that footer has them. Without the
extraction the markup is copied three times and will drift.

### Views

- `resources/views/legal/privacy.blade.php`
- `resources/views/legal/terms.blade.php`

Each wraps `<x-layouts::landing>` with a title slot, so both inherit the dark
theme, the nav, the language switch and the fonts the landing already uses.

A `<x-legal-document>` component holds the page shell — heading, "Last updated"
date, and a readable single column — so the two documents stay visually
identical and only their prose differs.

`landing.css` imports Tailwind without `source(none)`, so it scans the project
and new views get their classes with no registration. This is unlike
`checkout.css`, whose allow-list left the campaign page unstyled; nothing here
needs that treatment.

### Translations

New keys in both locale files:

| Key | `en` | `ms` |
| --- | --- | --- |
| `footer.privacy` | Privacy Policy | Dasar Privasi |
| `footer.terms` | Terms of Service | Terma Perkhidmatan |

## Content

Drafted here as a starting point, not as legal advice. It describes how this
application actually behaves, which is the part a lawyer cannot supply.

### Privacy Policy

1. Who Ihsan is, and how to make contact.
2. What is collected: donor name, email, phone and address; donation and
   recurring plan records; IP address, device and browser, used for fraud
   checks and to decide which currency to offer.
3. Card details are never held by Ihsan. Stripe and CHIP process them.
4. Why each category is collected: to take the donation, issue a receipt, run a
   recurring plan, check for fraud, and report to the organization.
5. Who it is shared with: the organization receiving the donation, Stripe, CHIP,
   AWS SES for email, and the advertising pixels an organization may enable
   (Meta, Google, LinkedIn, X, Snapchat).
6. How long records are kept.
7. What a donor may ask for: access, correction, deletion, and stopping email.
8. How changes are made, and the date this version took effect.

### Terms of Service

1. What Ihsan is: a platform. A donation is made to the registered organization,
   not to Ihsan.
2. Organization accounts and who may open one.
3. Donations: one-time and recurring, currency, covering the transaction costs,
   and when a card is charged.
4. Recurring plans: how a failed installment is retried, and how to cancel.
5. Fees: the platform fee and payment processing.
6. Refunds: the organization decides, and how a donor asks.
7. Receipts and tax.
8. What an organization undertakes: accurate campaigns, and using funds as
   described.
9. Limits of liability.
10. Governing law: Malaysia.
11. How changes are made, and the date this version took effect.

## Testing

- `/privacy` and `/terms` each return 200 and render their heading and a
  "Last updated" date.
- The landing footer links to both.
- Both legal pages render the same footer, which guards the extraction.
- The footer labels render in Malay when the locale is `ms`.

## Risks

**The text is unreviewed.** It is a draft written by an assistant, not a lawyer.
It stays unpublished until it is merged to `main`, so the review gate is the
merge. Nothing about the pages is live before then.

**The footer extraction touches the landing page.** `welcome.blade.php` is 550
lines and the footer is the last block in it. The tests above cover the landing
footer rendering its links, so a botched extraction fails rather than silently
dropping the footer.
