---
title: 'REST API'
order: 4
---
# REST API

REST API Ihsan membolehkan anda membaca dan mengurus derma, kempen, penderma, dan langganan secara programatik.

![Senarai elemen](/images/docs/app-elements.png)

## Pengesahan

Sahkan diri menggunakan token pembawa yang dicipta dari bahagian **Kunci API** dalam tetapan organisasi anda. Sertakan token dalam header `Authorization` bagi setiap permintaan.

## Contoh permintaan

```bash
curl https://your-domain.test/api/v1/donations \
    -H "Authorization: Bearer {token}"
```

## Konvensyen

- Permintaan mengembalikan respons JSON.
- Endpoint senarai menyokong penomboran halaman, penapisan, dan penapis susunan.
- Had kadar dikenakan ke atas semua kunci API.

Rujuk dokumentasi endpoint dalam dashboard untuk skema penuh dan parameter yang tersedia.
