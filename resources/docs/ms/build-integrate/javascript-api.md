# JavaScript API

JavaScript API membolehkan pengguna mahir mengawal widget secara programatik.

![Senarai elemen](/images/docs/app-elements.png)

## Kaedah yang tersedia

- `Ihsan.open()` — buka modal pembayaran secara manual.
- `Ihsan.close()` — tutup sebarang tindihan widget yang terbuka.
- `Ihsan.setCampaign(id)` — tukar kempen aktif.
- `Ihsan.on(event, callback)` — dengar peristiwa widget.

## Contoh

```javascript
Ihsan.open({
    amount: 50,
    campaign: 'IH7A3B9C',
});
```

Peristiwa termasuk `donation:success`, `modal:open`, dan `modal:close`. Gunakannya untuk mencetuskan analitik anda sendiri atau kemas kini UI.
