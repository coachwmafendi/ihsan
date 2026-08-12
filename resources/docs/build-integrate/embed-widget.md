---
title: 'Embed widget'
order: 1
---
# Embed widget

The Ihsan embed widget is a single JavaScript file that renders donation components on your site.

![Donation form](/images/docs/donation-form.png)

## Script tag

```html
<script
    src="https://your-domain.test/e/widget.js"
    data-token="E3N4O5P6"
    data-type="floating-button"
    async
></script>
```

## Attributes

| Attribute | Required | Description |
| --- | --- | --- |
| `data-token` | Yes | Element token |
| `data-type` | No | `floating-button`, `button`, `popup`, or `form` |
| `data-campaign` | No | Restrict the widget to a specific campaign |
| `data-theme` | No | Override the default color theme |

The widget loads asynchronously and will not block the rest of your page.
