# Monthly Upsell

A guide for the people who configure campaigns. English first, Bahasa Melayu below.

---

## What it does

After a donor picks a one-time amount and taps **Continue**, one extra screen appears before they enter their details:

> **Become a monthly supporter**
>
> Would you consider making your **RM 100** contribution a monthly donation? Your ongoing support helps us continue our work and make a lasting impact.
>
> `Donate RM 100/month`
> `Donate RM 50/month`
>
> _No, keep my one-time RM 100 gift_

The first button is always **the donor's own amount**, so it matches the sentence above it. The second is a lighter amount you configure. Declining takes them straight to checkout with the one-time gift they already chose — nothing is lost, and the amount is never changed behind their back.

The feature is **off by default**. Existing campaigns are unaffected until someone turns it on.

## Where to configure it

Campaign → **Checkout** tab → **Monthly Upsell** panel.

There is also a Monthly Upsell summary card on the campaign Overview tab; its **Edit** button jumps straight to the panel.

## The fastest setup

1. Switch on **Offer a monthly plan to one-time donors**
2. A starter tier appears already filled in: from RM 50, no upper limit, offering 33% and 50%
3. Save

That gives you: every one-time gift of RM 50 or more is offered the donor's own amount, plus roughly half of it as an easier option.

## Tiers

A tier says: *for one-time gifts in this range, offer this much monthly.*

| Field | Meaning |
| --- | --- |
| One-time from | The smallest one-time gift this tier covers |
| One-time up to | The largest. **Leave empty for no upper limit** |
| Offer 1 / Offer 2 | The lighter monthly amount. `% of gift` takes a share of what they were about to give; `RM fixed` is the same amount every time |

Ranges **cannot overlap** — saving is blocked if they do. You can have up to 6 tiers and up to 2 offers per tier.

### Two things that surprise people

**Offers are rounded to the nearest 5.** 33% of RM 50 is RM 16.50, which the donor sees as RM 15. This keeps donors from being shown amounts like RM 16.50.

**Only the highest surviving offer is shown.** You can configure two offers, but the donor only ever sees two buttons: their own amount, and the larger of your offers that still falls below it. The second offer acts as a fallback for when the first is filtered out.

An offer is dropped when it lands below the campaign's minimum amount, or at or above the donor's one-time gift. If every offer is dropped, the donor still sees the same-amount button on its own.

### Worked example

A tier of **RM 50 to RM 199** offering **33% and 50%**:

| Donor gives | Sees |
| --- | --- |
| RM 40 | Nothing — below the tier |
| RM 50 | RM 50/month or RM 25/month |
| RM 120 | RM 120/month or RM 60/month |
| RM 199 | RM 199/month or RM 100/month |
| RM 500 | Nothing — above the tier |

You do not have to work this out by hand. The panel shows a **What donors would see** box under each tier, computed by the same code the donation form runs. If a tier leaves some donors without a lighter option, it warns you there.

## Decline cooldown

If a donor declines, the offer stays hidden on that device for this many days. Default 30.

This is per device, not per person — it is remembered in the browser, because the donor's email is only collected on the next screen. Someone who declines on their phone may still be asked on their laptop.

The same limitation applies to existing monthly supporters: once someone starts a plan, that browser stops offering the upsell for a year, but a different device will not know.

## Wording overrides

Leave all three empty to use the defaults.

| Field | Default |
| --- | --- |
| Heading override | Become a monthly supporter |
| Message override | Would you consider making your :amount contribution a monthly donation? … |
| Decline link override | No, keep my one-time :amount gift |

Write `:amount` wherever the donor's one-time amount should appear. It is replaced with the real figure and shown in bold. Whole amounts drop the cents: `RM 100`, not `RM 100.00`.

## When the offer will not appear

- The campaign has recurring donations turned off
- The donor already chose Monthly on the amount screen
- The one-time amount falls outside every tier
- The donor declined recently on this device, or started a plan on it within the past year
- The embedded form already made the offer before opening the checkout window
- A CHIP campaign whose organisation only accepts FPX, since FPX cannot charge a subscription

## What gets recorded

Every donation stores three values you can use for reporting: whether the offer was shown, whether it was accepted, and the original one-time amount before conversion.

---

# Upsell Bulanan

Panduan untuk sesiapa yang menetapkan konfigurasi campaign.

## Apa yang ia buat

Selepas penderma pilih jumlah one-time dan tekan **Continue**, satu skrin tambahan muncul sebelum mereka isi butiran:

> **Become a monthly supporter**
>
> Would you consider making your **RM 100** contribution a monthly donation? …
>
> `Donate RM 100/month`
> `Donate RM 50/month`
>
> _No, keep my one-time RM 100 gift_

Butang pertama sentiasa **jumlah penderma itu sendiri**, supaya ia sepadan dengan ayat di atasnya. Butang kedua ialah jumlah lebih ringan yang anda tetapkan. Jika mereka menolak, mereka terus ke pembayaran dengan sumbangan one-time yang asal — tiada apa yang hilang, dan jumlahnya tidak pernah diubah tanpa pengetahuan mereka.

Ciri ini **dimatikan secara lalai**. Campaign sedia ada tidak terjejas sehingga seseorang menghidupkannya.

## Di mana nak tetapkan

Campaign → tab **Checkout** → panel **Monthly Upsell**.

Ada juga kad ringkasan Monthly Upsell pada tab Overview; butang **Edit** padanya terus membuka panel tersebut.

## Cara paling pantas

1. Hidupkan **Offer a monthly plan to one-time donors**
2. Satu tier permulaan muncul siap terisi: dari RM 50, tiada had atas, menawarkan 33% dan 50%
3. Simpan

Hasilnya: setiap sumbangan one-time RM 50 ke atas ditawarkan jumlah penderma itu sendiri, serta kira-kira separuh daripadanya sebagai pilihan lebih mudah.

## Tier

Satu tier bermaksud: *untuk sumbangan one-time dalam julat ini, tawarkan sekian banyak sebulan.*

| Medan | Maksud |
| --- | --- |
| One-time from | Sumbangan one-time terkecil yang tier ini liputi |
| One-time up to | Yang terbesar. **Biarkan kosong untuk tiada had atas** |
| Offer 1 / Offer 2 | Jumlah bulanan yang lebih ringan. `% of gift` ambil sebahagian daripada jumlah mereka; `RM fixed` sentiasa jumlah yang sama |

Julat **tidak boleh bertindih** — simpanan akan disekat jika bertindih. Maksimum 6 tier, dan 2 offer bagi setiap tier.

### Dua perkara yang selalu mengejutkan

**Offer dibundarkan ke gandaan 5 terdekat.** 33% daripada RM 50 ialah RM 16.50, yang penderma lihat sebagai RM 15. Ini mengelakkan penderma nampak jumlah seperti RM 16.50.

**Hanya offer tertinggi yang terselamat dipaparkan.** Anda boleh tetapkan dua offer, tetapi penderma hanya nampak dua butang: jumlah mereka sendiri, dan offer anda yang paling besar yang masih di bawahnya. Offer kedua berfungsi sebagai sandaran apabila yang pertama tersingkir.

Sesuatu offer disingkirkan apabila ia jatuh di bawah jumlah minimum campaign, atau sama dengan / melebihi sumbangan one-time penderma. Jika semua offer tersingkir, penderma tetap nampak butang jumlah-sama itu bersendirian.

### Contoh dikira penuh

Tier **RM 50 hingga RM 199** menawarkan **33% dan 50%**:

| Penderma beri | Nampak |
| --- | --- |
| RM 40 | Tiada — di bawah tier |
| RM 50 | RM 50/bulan atau RM 25/bulan |
| RM 120 | RM 120/bulan atau RM 60/bulan |
| RM 199 | RM 199/bulan atau RM 100/bulan |
| RM 500 | Tiada — melebihi tier |

Anda tidak perlu mengira sendiri. Panel memaparkan kotak **What donors would see** di bawah setiap tier, dikira oleh kod yang sama dengan borang derma. Jika sesuatu tier menyebabkan sebahagian penderma tiada pilihan ringan, amaran akan dipaparkan di situ.

## Decline cooldown

Jika penderma menolak, offer disembunyikan pada peranti itu selama sekian hari. Lalai 30 hari.

Ini mengikut peranti, bukan mengikut orang — ia diingat dalam pelayar, kerana e-mel penderma hanya dikutip pada skrin berikutnya. Seseorang yang menolak pada telefon mungkin masih ditanya pada komputer riba mereka.

Had yang sama terpakai kepada penyokong bulanan sedia ada: apabila seseorang memulakan pelan, pelayar itu berhenti menawarkan upsell selama setahun, tetapi peranti lain tidak akan tahu.

## Menukar perkataan

Biarkan ketiga-tiganya kosong untuk guna teks lalai.

| Medan | Lalai |
| --- | --- |
| Heading override | Become a monthly supporter |
| Message override | Would you consider making your :amount contribution a monthly donation? … |
| Decline link override | No, keep my one-time :amount gift |

Tulis `:amount` di mana jumlah one-time penderma sepatutnya muncul. Ia diganti dengan angka sebenar dan dipaparkan dalam huruf tebal. Jumlah bulat tidak berkoma sen: `RM 100`, bukan `RM 100.00`.

Contoh dalam Bahasa Melayu:

- Heading: `Jadi penyokong bulanan`
- Message: `Sudikah anda menjadikan sumbangan :amount anda sebagai derma bulanan? Sokongan berterusan anda membantu kami meneruskan kerja ini.`
- Decline: `Tidak, kekalkan derma :amount sekali sahaja`

## Bila offer tidak akan muncul

- Campaign mematikan derma berulang
- Penderma sudah memilih Monthly pada skrin jumlah
- Jumlah one-time berada di luar semua tier
- Penderma baru menolak pada peranti ini, atau memulakan pelan padanya dalam tempoh setahun lepas
- Borang embed sudah membuat tawaran sebelum membuka tetingkap pembayaran
- Campaign CHIP yang organisasinya hanya menerima FPX, kerana FPX tidak boleh mengecaj langganan

## Apa yang direkodkan

Setiap derma menyimpan tiga nilai untuk pelaporan: sama ada offer dipaparkan, sama ada ia diterima, dan jumlah one-time asal sebelum ditukar.
