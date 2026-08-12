---
title: 'JavaScript API'
order: 2
---
# JavaScript API

The JavaScript API lets advanced users control the widget programmatically.

![Elements list](/images/docs/app-elements.png)

## Available methods

- `Ihsan.open()` — open the checkout modal manually.
- `Ihsan.close()` — close any open widget overlay.
- `Ihsan.setCampaign(id)` — switch the active campaign.
- `Ihsan.on(event, callback)` — listen for widget events.

## Example

```javascript
Ihsan.open({
    amount: 50,
    campaign: 'IH7A3B9C',
});
```

Events include `donation:success`, `modal:open`, and `modal:close`. Use them to trigger your own analytics or UI updates.
