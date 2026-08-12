---
title: 'Pemasangan'
order: 1
---
# Pemasangan

Menambah Ihsan ke laman web anda memerlukan satu tag skrip dan token elemen.

![Senarai elemen](/images/docs/app-elements.png)

## Permulaan pantas

1. Salin token elemen anda daripada papan pemuka Ihsan.
2. Tampal skrip terbenam sebelum tag penutup `</body>` pada halaman di mana anda mahu sumbangan dipaparkan.
3. Sesuaikan atribut `data-type` untuk memaparkan butang terapung, butang sebaris, tetingkap timbul, atau borang terbenam.

## Contoh

```html
<script
    src="https://your-domain.test/e/widget.js"
    data-token="E3N4O5P6"
    data-type="floating-button"
    async
></script>
```

Selepas skrip dimuatkan, widget akan dipaparkan secara automatik. Anda boleh mengurus kandungan dan gaya widget daripada papan pemuka Ihsan tanpa menyunting kod sekali lagi.
