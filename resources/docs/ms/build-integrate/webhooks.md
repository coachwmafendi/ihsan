# Webhooks

Ihsan boleh menghantar peristiwa webhook ke pelayan anda apabila peristiwa penting berlaku.

![Senarai elemen](/images/docs/app-elements.png)

## Peristiwa biasa

- `donation.created` — derma baharu telah diterima.
- `subscription.created` — langganan berulang telah bermula.
- `subscription.cancelled` — langganan telah dibatalkan.
- `refund.created` — bayaran balik telah diproses.

## Menyiapkan webhooks

1. Berikan endpoint HTTPS yang boleh diakses secara umum dalam tetapan organisasi anda.
2. Sahkan tandatangan webhook menggunakan rahsia perkongsian.
3. Balas dengan status `200 OK` selepas memproses peristiwa.

Sediakan endpoint peringkatan dahulu untuk menguji pengendalian muatan sebelum digunakan secara langsung.
