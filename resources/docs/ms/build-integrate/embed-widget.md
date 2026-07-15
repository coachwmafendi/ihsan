# Benam widget

Widget benam Ihsan ialah satu fail JavaScript yang memaparkan komponen derma di laman web anda.

![Borang derma](/images/docs/donation-form.png)

## Tag skrip

```html
<script
    src="https://your-domain.test/e/widget.js"
    data-token="E3N4O5P6"
    data-type="floating-button"
    async
></script>
```

## Atribut

| Atribut | Wajib | Penerangan |
| --- | --- | --- |
| `data-token` | Ya | Token elemen |
| `data-type` | Tidak | `floating-button`, `button`, `popup`, atau `form` |
| `data-campaign` | Tidak | Hadkan widget kepada kempen tertentu |
| `data-theme` | Tidak | Ganti tema warna lalai |

Widget dimuatkan secara asinkron dan tidak akan menyekat halaman anda yang lain.
