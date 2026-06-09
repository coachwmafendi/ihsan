# Product Requirements Document (PRD)
## Ihsan — Platform Derma & Fundraising Berulang untuk NGO Malaysia

**Version:** 1.5
**Tarikh:** 9 Jun 2026
**Status:** Draft
**Pemilik Produk:** TBD

---

## 1. Gambaran Keseluruhan

### 1.1 Ringkasan Eksekutif

Ihsan ialah platform SaaS fundraising yang membolehkan NGO, masjid, dan badan amal Malaysia menerima derma secara online — termasuk derma berulang (recurring) — melalui donation elements, halaman kempen, dan dashboard pengurusan donor. Produk ini mengambil inspirasi UX dan model operasi daripada platform seperti Fundraise Up, tetapi disesuaikan untuk NGO Malaysia, Stripe Malaysia, dan keperluan onboarding tempatan.

Nama "Ihsan" (إحسان) membawa maksud berbuat kebaikan dengan sepenuh hati — mencerminkan misi platform untuk memudahkan amalan kebajikan secara berterusan.

Platform ini mengisi jurang yang ada di Malaysia, di mana hanya 37% rakyat Malaysia pernah buat derma secara digital, dan kebanyakan NGO tempatan masih bergantung pada pemindahan bank manual.

### 1.2 Benchmark Produk

Fundraise Up digunakan sebagai rujukan utama untuk:

- Struktur app admin NGO: dashboard, campaigns, donation elements, supporters/donors, recurring plans, transactions, exports, dan settings
- UX checkout yang pantas, mobile-first, dan conversion-oriented
- Embeddable donation elements yang boleh dipasang di website NGO
- Analytics fundraising yang mudah difahami oleh operator bukan teknikal

Benchmark ini bukan arahan untuk menyalin produk tersebut secara literal. Ciri sebenar akan dipilih mengikut MVP Ihsan, kekangan Stripe Malaysia, dan maklum balas early adopter NGO.

### 1.2.1 Pemerhatian Screenshot Fundraise Up — Insights

Screenshot pertama menunjukkan Fundraise Up admin console dengan struktur navigasi berikut:

| Menu | Status Ihsan MVP | Nota |
|------|------------------|------|
| Insights | MVP | Halaman analytics utama untuk NGO admin |
| Donations | MVP | Senarai transaksi one-time dan recurring payments |
| Recurring | MVP | Senarai recurring plans/subscriptions |
| Campaigns | MVP | Pengurusan kempen |
| Designations | V2 | Ditunda; MVP guna campaign sahaja |
| Fundraisers | V3 | Peer-to-peer fundraising |
| Benefits | Backlog | Tidak perlu untuk donation MVP |
| Gift catalogs | Backlog | Tidak perlu untuk donation MVP |
| Supporters | MVP | Nama Fundraise Up untuk donor list; Ihsan boleh guna "Donors" atau "Supporters" |
| Elements | MVP | Donation forms/buttons/popups/widget config |
| Exports | MVP | CSV export untuk audit dan LHDN/manual reporting |
| Virtual Terminal | MVP | Admin-created manual donations/payment entry — Shipped |
| Settings | MVP | Organization, Stripe, branding, receipts |
| Help | MVP ringan | Link dokumentasi/support sahaja |

Halaman **Insights** dalam screenshot mempunyai:

- Filter bar: Date range, Aggregation, Compare, Campaign, Designation, Source, Frequency
- Section navigation: Overview, Performance, Recurring plans, Recurring revenue, Retention, Day & time, Countries, Device, Frequencies, Payment methods, Designations, Tributes, Fundraisers, Elements, URL, UTM
- Chart utama: total raised mengikut tarikh dengan pilihan metric
- Cards tambahan seperti first installments dan one-time donations

Untuk Ihsan MVP, Insights perlu bermula dengan metrik yang boleh terus dikira daripada `donations`, `subscriptions`, `campaigns`, dan `utm_params`. Section yang bergantung pada feature belum wujud seperti Designations, Tributes, Fundraisers, Benefits, dan Gift catalogs ditunda.

### 1.3 Identiti Jenama

| Elemen | Nilai |
|--------|-------|
| **Nama** | Ihsan |
| **Sebutan** | ih-saan (إحسان) |
| **Tagline** | *"Beri dengan ikhlas. Secara berterusan."* |
| **Domain cadangan** | `ihsan.my` / `ihsan.app` |
| **Nada jenama** | Dipercayai, hangat, profesional, inklusif |
| **Warna utama** | Teal (kepercayaan + ketenangan) + neutral warm |

### 1.4 Masalah yang Diselesaikan

**Untuk NGO:**
- Tiada cara mudah terima derma online dengan recurring built-in
- Tiada visibility terhadap donor base dan MRR (Monthly Recurring Revenue)
- Proses resit manual, membuang masa
- Bergantung pada Maybank2U atau WhatsApp — tidak profesional, tidak scalable

**Untuk Donor:**
- Tiada cara mudah set auto-debit derma bulanan kepada badan amal pilihan
- Tiada portal untuk urus atau pause derma sendiri
- Tidak yakin sama ada derma sampai kepada penerima (trust & transparency issue)

### 1.5 Visi Produk

> "Platform paling dipercayai untuk NGO Malaysia mengumpul dana secara berterusan — mudah untuk setup, telus untuk donor, dan powerful untuk pertumbuhan."

---

## 2. Objektif MVP

MVP ini bermula dengan **NGO Admin app** terlebih dahulu. Sasaran awal ialah membolehkan satu organisasi mengurus profil, kempen, donation elements, donor, transaksi, dan recurring revenue dari satu dashboard yang kemas. Donor checkout tetap diperlukan untuk menguji aliran sebenar, tetapi pengalaman admin NGO menjadi permukaan produk utama untuk fasa pertama.

MVP ini bertujuan membuktikan nilai produk kepada segmen awal (early adopters) dalam masa 3–6 bulan. Kejayaan diukur berdasarkan:

| Metrik | Sasaran MVP (bulan ke-6) |
|--------|--------------------------|
| NGO aktif di platform | 5 organisasi |
| Total donation diproses | RM 50,000+ |
| Recurring donors aktif | 100+ donor |
| Kadar pengekalan donor | >70% selepas bulan ke-3 |

### 2.1 Objektif Produk Fasa Pertama

1. NGO admin boleh onboarding, lengkapkan profil organisasi, dan sambung Stripe Connect.
2. NGO admin boleh cipta kempen dan donation element tanpa bantuan developer.
3. NGO admin boleh pantau donation, recurring subscription, donor, dan payout/revenue secara harian.
4. Donor boleh membuat one-time atau recurring donation melalui hosted donation page atau embedded element.
5. NGO admin boleh kawal tetapan pembayaran dan email notification organisasi tanpa bantuan developer.
6. Platform owner boleh approve NGO, mengurus kadar processing fee, dan memantau transaksi asas.

---

## 3. Pengguna Sasaran

### 3.1 Persona Utama

**Persona A — Admin NGO (Platform User)**

- **Nama watak:** Ustaz Farouk, 40-an, pentadbir masjid di Kota Bharu
- **Keperluan:** Terima derma online, lihat senarai donor, jana resit automatik
- **Tahap teknologi:** Sederhana — guna WhatsApp dan spreadsheet Excel
- **Pain point:** Kena check Maybank statement manual tiap hari, susah nak tahu berapa yang masuk bulan ni

**Persona B — Donor (End User)**

- **Nama watak:** Hafizah, 32, pekerja swasta di KL
- **Keperluan:** Nak sedekah setiap bulan tapi malas nak ingat buat manual transfer
- **Tahap teknologi:** Tinggi — guna TNG eWallet, buat belian online selalu
- **Pain point:** Selalu lupa nak transfer derma, rasa bersalah bila skip bulan

### 3.2 Pengguna Sekunder

- **Super Admin (Platform Owner):** Pantau semua NGO, transaksi, handle onboarding
- **Auditor/Compliance NGO:** Eksport data derma untuk pelaporan LHDN

---

## 4. Skop MVP

### 4.1 Dalam Skop MVP Pertama (Must Have)

Fokus MVP pertama ialah **admin experience untuk NGO**, dengan donation flow minimum yang cukup untuk menghasilkan transaksi sebenar.

#### 4.1.1 Onboarding NGO
- [x] Borang pendaftaran NGO (nama, ROB/ROS number, maklumat bank)
- [x] Upload dokumen pengesahan (sijil pendaftaran)
- [x] Approval manual oleh platform admin
- [x] Setup profil organisasi (logo, penerangan, kategori)

#### 4.1.2 Pengurusan Kempen
- [x] Buat kempen dengan tajuk, penerangan, target amount, dan tarikh tutup
- [x] Upload gambar kempen
- [x] Toggle: kempen dengan target / tanpa target (general fund)
- [x] Share link kempen (URL unik per kempen)

#### 4.1.3 NGO Admin Console
- [x] Insights ringkasan: total raised, MRR, active recurring donors, donor baru, donation conversion signal asas
- [x] Navigation utama: Insights, Donations, Recurring, Campaigns, Supporters/Donors, Elements, Exports, Settings
- [x] Insights dengan filter asas: date range, aggregation (daily/weekly/monthly), campaign, source/UTM, frequency
- [x] Insights sections MVP: Overview, Performance, Recurring plans, Recurring revenue, Retention asas, Payment methods, Elements, URL, UTM
- [x] Senarai donor/supporter dengan status, total given, last donation, dan recurring status
- [x] Senarai recurring plans/subscriptions dengan status aktif, past due, paused, cancelled
- [x] Senarai transaksi dengan filter campaign, status, tarikh, dan type
- [ ] Eksport CSV untuk donors dan transactions
- [x] Settings organisasi berpecah kepada Profil Organisasi, Pembayaran / Stripe Connect, dan Pemberitahuan
- [x] Email notification settings disimpan dalam `organizations.settings` dan auto-save apabila toggle berubah
- [x] Fraud Prevention dashboard untuk super admin — senarai peraturan fraud, cubaan yang ditandakan/blocked, dan blocked donations yang menunggu semakan

#### 4.1.4 Fraud Prevention & Security
- [x] Sistem fraud detection secara real-time semasa checkout — menilai risk score berdasarkan peraturan configurable
- [x] Peraturan fraud global dan per-organisasi: velocity, amount threshold, country block, card fingerprint, dan pattern matching
- [x] Tindakan automatik: `flag` (tandakan untuk semakan), `block` (halang transaksi), atau `notify` (hantar alert sahaja)
- [x] Blocked donations disemak oleh super admin — boleh `release` jika kesilapan positif (false positive)
- [x] Fraud alert email dihantar kepada super admin apabila transaksi ditandakan atau dihalang
- [x] Log semua fraud attempts dalam `fraud_attempts` untuk audit dan analisis trend

#### 4.1.5 Donation Elements & Checkout
- [x] Suggested amounts (3 pilihan + custom amount)
- [x] Pilihan: One-time atau Recurring (weekly / monthly / yearly)
- [x] Payment via **Stripe Connect** (kad, Apple Pay, Google Pay apabila tersedia)
- [x] Donor boleh memilih untuk cover estimated Stripe processing fee apabila kempen/element membenarkan
- [x] Embeddable donation elements: Button, Floating Button, Form, dan Popup
- [x] Semua embed script menggunakan widget tunggal `/e/widget.js` dengan `data-token` dan `data-type`
- [x] Standalone donation page (hosted di Ihsan)
- [x] Email resit automatik kepada donor selepas bayar, termasuk PDF receipt formal untuk download

#### 4.1.6 Recurring Subscription Management
- [x] Stripe Subscription untuk handle auto-billing
- [ ] Smart dunning — retry bayaran gagal pada hari ke-3, 7, dan 14
- [x] Notifikasi email kepada donor bila bayaran gagal
- [x] **Donor Portal** — login tanpa password (magic link), boleh:
  - Lihat sejarah derma
  - Cancel derma
  - Pause / resume recurring subscription
  - Tukar amount
  - Update payment method
  - Muat turun receipt individu atau semua receipt

#### 4.1.7 Admin Platform (Super Admin)
- [x] Senarai semua NGO dan status (pending/active/suspended)
- [x] Approve/reject permohonan NGO baru
- [x] Lihat semua transaksi platform
- [x] Overview revenue (platform fees collected)

### 4.2 Luar Skop MVP (Backlog)

Ini adalah feature yang penting tapi **tidak** dibina dalam MVP. Akan dimasukkan dalam versi seterusnya berdasarkan feedback:

| Feature | Fasa |
|---------|------|
| FPX / DuitNow / TNG eWallet | V2 |
| Zakat & Sedekah module | V2 |
| LHDN tax-exempt receipt automation | V2 |
| White-label (NGO guna domain sendiri) | V2 |
| Advanced conversion analytics seperti benchmark Fundraise Up | V2 |
| Designations | V2 |
| Virtual Terminal | ~~V2~~ Shipped |
| A/B testing donation elements | V3 |
| AI-suggested ask amounts | V3 |
| Peer-to-peer fundraising | V3 |
| Benefits / membership perks | Backlog |
| Gift catalogs | Backlog |
| Mobile app (React Native / NativePHP) | V3 |
| Multi-language (BM/EN) | V2 |
| Crypto donations | V4 |

---

## 5. Keperluan Fungsian

### 5.1 Authentication & Authorization

| Keperluan | Keterangan |
|-----------|------------|
| Auth NGO Admin | Email + password, Laravel Fortify |
| Auth Donor Portal | Magic link via email (tanpa password) |
| Auth Super Admin | Email + password + 2FA |
| Role-based access | Super Admin dan NGO Admin dalam `users`; donor portal melalui magic link |
| Session management | Remember me 30 hari |

### 5.2 Payment Processing

| Keperluan | Keterangan |
|-----------|------------|
| Payment gateway | Stripe (Malaysia-enabled account) |
| One-time payment | Stripe Elements / PaymentIntent melalui connected account |
| Recurring payment | Stripe Subscriptions + Stripe Billing |
| Webhook handling | Stripe webhooks untuk event utama: `payment_intent.succeeded`, `payment_intent.payment_failed`, `invoice.paid`, `invoice.payment_failed`, `customer.subscription.deleted`, `customer.subscription.updated`, `charge.refunded`, `account.updated` |
| Processing fee | Default 2.5% daripada jumlah kasar; boleh dikonfigurasi oleh platform owner dan override per organisasi |
| Fee collection | Rekod dalam `processing_fees`; boleh dikutip sebagai application fee / monthly invoice bergantung aliran pembayaran |
| Payout ke NGO | Stripe Connect — auto payout setiap 7 hari |
| Refund | Manual oleh NGO Admin dalam 7 hari |
| Fraud detection | Penilaian real-time semasa checkout berdasarkan `fraud_rules` — flag, block, atau notify |
| Blocked donation review | Super admin boleh semak dan release blocked donations dari Fraud Prevention dashboard |

### 5.3 Email Notifications

| Trigger | Penerima | Kandungan |
|---------|----------|-----------|
| Derma berjaya | Donor | Resit + ucapan terima kasih |
| Derma berulang berjaya | Donor | Resit bulanan |
| Bayaran gagal (percubaan 1) | Donor | Notifikasi + link update kad |
| Bayaran gagal (percubaan 3) | Donor | Peringatan akhir |
| Subscription dibatalkan | Donor + NGO Admin | Pengesahan pembatalan |
| NGO diluluskan | NGO Admin | Welcome email + link setup |
| Donor baru | NGO Admin | Notifikasi donor baru |
| Derma besar | NGO Admin | Alert bila jumlah derma melepasi threshold organisasi |
| Refund | NGO Admin | Notifikasi bila charge dikembalikan |
| Campaign milestone | NGO Admin | Alert apabila kempen mencapai milestone kutipan |
| Daily summary | NGO Admin | Ringkasan derma harian, jika diaktifkan |
| Weekly report | NGO Admin | Ringkasan mingguan, jika diaktifkan |
| Monthly report | NGO Admin | Ringkasan bulanan, jika diaktifkan |
| Fraud alert | Super Admin | Alert apabila transaksi ditandakan (flagged) atau dihalang (blocked) oleh sistem fraud detection |

Tetapan notification disimpan dalam `organizations.settings`. Default MVP:

| Key | Default |
|-----|---------|
| `notify_new_donation` | ON |
| `daily_donation_summary` | OFF |
| `failed_payment_notification` | ON |
| `notify_new_subscription` | ON |
| `notify_subscription_cancelled` | ON |
| `notify_large_donation` | OFF |
| `large_donation_threshold` | 1000 |
| `notify_refund` | ON |
| `notify_campaign_milestone` | OFF |
| `weekly_report` | OFF |
| `monthly_report` | OFF |

### 5.4 Embeddable Widget

```html
<!-- Cara NGO embed element Ihsan di website mereka -->
<script
    src="https://ihsan.my/e/widget.js"
    data-token="ELEMENT_TOKEN"
    data-type="floating_button"
    data-api-base="https://ihsan.my"
    async>
</script>
```

- Widget fetch public config melalui `/api/public/elements/{token}` dan render berdasarkan `elements.type`
- Fallback boleh render daripada `data-*` attributes jika API tidak tersedia
- Form embed render dalam `<iframe>`; Button, Floating Button, dan Popup render sebagai host-page widget
- Responsive (mobile-first, minimum 320px width)
- Customizable: text, action, trigger, frequency, visibility, layout, image, color, dan button effect mengikut type
- Load async — tidak block website host
- Embed code dipaparkan selepas element disimpan dan copy menggunakan Alpine `@js()` supaya HTML entities tidak rosak ketika paste

---

## 6. Keperluan Bukan Fungsian

### 6.1 Prestasi

| Metrik | Sasaran |
|--------|---------|
| Masa load halaman donasi | < 2 saat |
| Uptime | 99.5% |
| Masa respons API | < 500ms (p95) |
| Proses pembayaran | < 3 saat end-to-end |

### 6.2 Keselamatan

- PCI DSS compliance melalui Stripe (data kad tidak disimpan di server kita)
- HTTPS wajib untuk semua endpoint
- CSRF protection (Laravel built-in)
- Rate limiting pada donation endpoint (10 req/min per IP)
- Stripe webhook signature verification
- Sensitive data (bank account info NGO) encrypted at rest

### 6.3 Kebolehskalaan

- Horizontal scaling melalui containerization (Docker)
- Queue-based email sending (Laravel Horizon + Redis)
- Database indexing pada `donations`, `subscriptions`, `donors` tables

---

## 7. Rekabentuk & UX

### 7.1 Prinsip Rekabentuk

1. **Mudah untuk donor** — checkout dalam kurang dari 60 saat
2. **Jelas untuk NGO** — dashboard yang boleh difahami tanpa training
3. **Dipercayai** — design yang kelihatan selamat dan profesional
4. **Mobile-first** — majoriti donor Malaysia akses melalui telefon

### 7.2 Halaman / Screen Utama

**Untuk Donor (Public)**
- Halaman kempen (`/campaign/[slug]`)
- Checkout flow (amount → frequency → payment → confirmation)
- Donor portal (`/my/donations` — magic link access)

**Untuk NGO Admin**
- Login (`/login`)
- Insights (`/insights`)
- Donations (`/donations`)
- Recurring (`/recurring`)
- Kempen — senarai & buat baru (`/campaigns`)
- Elements — donation forms/buttons/popups (`/elements`)
- Supporters / Donors (`/supporters`)
- Exports / CSV export (`/exports`)
- Tetapan — Profil Organisasi, Pembayaran / Stripe Connect, Pemberitahuan (`/app/profil-organisasi`, `/app/pembayaran`, `/app/pemberitahuan`)

**Untuk Super Admin**
- Admin panel (`/admin`)
- Senarai NGO + approval
- Overview transaksi platform

### 7.3 Design System

- **Framework:** Livewire 4 (SFC) + Flux UI
- **Font:** Satoshi (Fontshare) — bersih, modern, readable
- **Warna:** Neutral warm + satu accent teal (trust + ketenangan)
- **Dark/Light mode:** Ya, dengan toggle

---

## 8. Tech Stack

| Layer | Teknologi | Justifikasi |
|-------|-----------|-------------|
| Backend | Laravel 13 | Familiar, mature ecosystem |
| Frontend | Livewire 4 SFC | Real-time UI tanpa full SPA complexity |
| UI Components | Flux UI | Built for Livewire, professional look |
| Database | SQLite untuk local dev, MySQL 8/PostgreSQL untuk production | Relational, mature, hosting mudah |
| Cache/Queue | Redis + Laravel Horizon | Queue email & webhook processing |
| Payment | Stripe + Stripe Connect | Best recurring support, Malaysia-enabled |
| Email | Laravel Mail + Mailgun/Resend | Reliable delivery, template-based |
| Storage | Cloudflare R2 | Murah, S3-compatible, gambar kempen |
| Hosting | Hetzner VPS / Laravel Forge | Cost-effective, control penuh |
| Tunnel (dev) | Cloudflare Tunnel | Test Stripe webhooks secara local |

---

## 9. Model Data (Ringkasan)

```
organizations          → NGO/badan amal berdaftar
  campaigns            → Kempen fundraising
  donors               → Profil donor (boleh derma kepada berbilang NGO)
  donations            → Rekod setiap transaksi, termasuk receipt, fee, card country, UTM, device, geo, dan fraud fields
  subscriptions        → Recurring subscription (link ke Stripe Subscription)
  elements             → Button, Floating Button, Form, Popup, QR Code, Link
  processing_fees      → Rekod fee yang dikutip platform
  monthly_invoices     → Invois bulanan untuk accumulated processing fees
  webhook_logs         → Log semua Stripe webhook events
  fraud_rules          → Peraturan deteksi penipuan (global atau per organisasi)
  fraud_attempts       → Log cubaan transaksi yang ditandakan/dihalang
  blocked_donations    → Rekod donation yang dihalang untuk semakan super admin
  settings             → Tetapan global aplikasi (key-value)
```

Hubungan penting:
- `donor` → boleh ada banyak `subscriptions` kepada banyak `campaigns`
- `subscription` → ada banyak `donations` recurring (satu donation per billing cycle)
- `campaign` → belongs to `organization`, ada banyak `donations` dan `subscriptions`
- `processing_fee` → belongs to `donation`, `organization`, dan optional `monthly_invoice`
- `organization.settings` → menyimpan notification preferences, default currency, dan konfigurasi ringan organisasi

---

## 10. Model Perniagaan

### 10.1 Struktur Fee MVP

| Jenis | Kadar |
|-------|-------|
| Ihsan processing fee | 2.5% per transaksi secara default, boleh dikonfigurasi |
| Stripe processing fee | Anggaran kad tempatan dalam UI: ~3% + RM 0.50; nilai sebenar disync daripada Stripe BalanceTransaction |
| Donor cover fee | Optional, pre-checked jika campaign/element mengaktifkannya |
| Monthly subscription (NGO) | **Percuma** semasa MVP |

> **Nota:** Semasa MVP, tiada monthly fee dikenakan kepada NGO. Ini untuk kurangkan barrier onboarding. Selepas terbukti nilai, plan berbayar boleh diperkenalkan.

### 10.2 Aliran Wang (Stripe Connect)

1. Donor bayar RM 100 kepada kempen NGO
2. Stripe fee sebenar disync daripada Stripe BalanceTransaction
3. Ihsan processing fee default 2.5% = RM 2.50, direkod dalam `processing_fees`
4. NGO menerima baki bersih selepas Stripe fee dan processing fee yang berkaitan
5. Payout automatik ke akaun bank NGO mengikut jadual Stripe Connect
6. Processing fee boleh direkonsiliasi melalui `monthly_invoices` jika ia dikutip secara invois bulanan

---

## 11. Risiko & Mitigasi

| Risiko | Tahap | Mitigasi |
|--------|-------|----------|
| NGO lambat adopt teknologi baru | Tinggi | Tawar onboarding hands-on percuma, video tutorial BM |
| Stripe ditolak oleh donor (guna card) | Sederhana | Tambah FPX dalam V2 |
| Gagal dapatkan early adopter | Tinggi | Identify 3 NGO sebelum build, validate problem dulu |
| Regulatory/legal (perlu lesen?) | Sederhana | Semak dengan SC Malaysia — platform facilitation vs. fund-holding |
| Fraud (NGO palsu) | Sederhana | KYC manual approval + dokumen ROS/ROB wajib |
| Stripe account termination | Rendah | Ikut Stripe ToS, maintain chargeback rate < 0.5% |

---

## 12. Pelan Pembangunan MVP

### Fasa 1 — NGO Admin Foundation (Minggu 1–3)

- [x] Setup Laravel project, database migrations, seeding
- [x] Auth system (NGO Admin + Super Admin)
- [x] Stripe Connect integration (test mode)
- [x] Organization onboarding flow
- [x] NGO Admin shell: navigation, settings, dashboard kosong

### Fasa 2 — Campaigns, Elements & Core Donation Flow (Minggu 4–6)

- [x] Campaign CRUD
- [x] Elements CRUD (Button, Floating Button, Form, Popup config asas)
- [x] Donation form (one-time + recurring)
- [x] Stripe Elements / PaymentIntent integration
- [x] Webhook handler (`payment_intent.succeeded`, `invoice.paid`, `invoice.payment_failed`, `subscription.deleted`, `charge.refunded`)
- [x] Email resit automatik

### Fasa 3 — NGO Operations Dashboard (Minggu 7–9)

- [x] Insights page: total raised chart, first installments, one-time donations, recurring revenue, payment methods
- [x] Donations, Supporters, dan Recurring list pages
- [x] Recurring subscription list dan status management
- [x] Donor Portal (magic link, history, receipts, cancel, pause/resume, change amount, update payment method)
- [ ] CSV export
- [ ] Smart dunning logic

### Fasa 4 — Widget, Super Admin & Polish (Minggu 10–12)

- [x] Embeddable JavaScript widget `/e/widget.js`
- [x] Super Admin panel
- [x] Fraud Prevention dashboard
- [x] Virtual Terminal — admin manual donation processing dengan Stripe Card Element
- [x] Platform Overview report (admin) — MRR/MTD financial health, operational alerts, donor & subscription health metrics
- [x] Stripe Connect platform overview dalam admin Stripe settings
- [x] Domain routing — app panel di `app.getihsan.my`, admin panel di `admin.getihsan.my`
- [x] UI component system — reusable `x-ui-*` blade components untuk semua app panel pages
- [ ] QA testing menyeluruh
- [ ] Onboard 3 early adopter NGO (beta)

### 12.1 Status Implementasi Semasa (9 Jun 2026)

- Settings NGO telah dipecahkan kepada Profil Organisasi, Pembayaran, dan Pemberitahuan.
- Notification preferences auto-save ke `organizations.settings` dan email dihantar melalui queued jobs.
- Donation list mempunyai period filter gaya Insights dan menyimpan `donor_country` untuk analitik negara.
- Popup element form telah diringkaskan kepada Content, Action, Display Rules, Appearance, dan Status.
- Widget endpoint `/e/widget.js` dan public element API tersedia untuk Button, Floating Button, Form, dan Popup.
- Revenue/processing fee page menggunakan kadar config-driven 2.5% dan mengira effective rate daripada data sebenar.
- **Fraud Prevention** — dashboard super admin, peraturan configurable (velocity/amount/country/card/pattern), tindakan automatik flag/block/notify, blocked donation review, dan fraud alert email.
- Monthly invoice generation (`ihsan:generate-monthly-invoices`) menghasilkan Stripe Invoice untuk accumulated processing fees dan menghantar `PlatformInvoiceCreated` melalui queue.
- Webhook `invoice.paid` untuk processing fee invoices mengemaskini status dan menghantar `PlatformInvoicePaid` melalui queue.
- Subscription edit page dengan pause/resume, cancel (immediate/period end), dan update payment method.
- Embed code menggunakan Alpine `@js()` untuk copy supaya HTML entities tidak rosak ketika paste.
- **Virtual Terminal** — NGO admin boleh proses manual donation melalui Stripe Card Element dalam popup, dengan preloaded supporter link generator dan full payment flow. Dipindahkan ke Profil tab.
- **Platform Overview** (admin panel) — laporan kesihatan platform merangkumi MRR/MTD financial health dengan MoM%, operational alerts (failed payments, past-due subscriptions, pending approvals, blocked donations), serta donor & subscription health metrics.
- **Stripe Connect platform overview** dalam admin Stripe settings — verifikasi sambungan, environment banners, webhook/redirect URI display.
- **Domain routing** — app panel di `app.getihsan.my`, admin panel di `admin.getihsan.my`.
- **UI component system** — `x-ui-*` blade components reusable untuk standardize semua app panel pages (stat cards, section headers, data rows, filter pills). Page headers tidak diulang dalam list pages.
- Locale restoration dipindahkan daripada `AppServiceProvider` ke `SetLocale` middleware untuk fix session-timing bug.

### 12.2 Rujukan Screenshot Fundraise Up

Pemilik produk mempunyai akses login Fundraise Up dan boleh menyediakan screenshot/menu sebagai rujukan. Setiap screenshot yang diterima akan diklasifikasikan kepada:

- **MVP now:** penting untuk NGO admin app pertama
- **V2:** berguna tetapi tidak perlu untuk transaksi pertama
- **Backlog:** advanced optimization atau nice-to-have
- **Not applicable:** tidak sesuai untuk konteks NGO Malaysia atau Stripe Malaysia

Screenshot pertama yang diterima: **Insights page**. Keputusan awal:

- **MVP now:** Insights, Donations, Recurring, Campaigns, Supporters, Elements, Exports, Settings
- **V2:** Designations, advanced compare/performance analytics
- **V3/Backlog:** Fundraisers, Benefits, Gift catalogs, Tributes
- **MVP Insights filters:** date range, aggregation, campaign, source/UTM, frequency

---

## 13. Kriteria Penerimaan MVP

MVP dianggap **selesai** apabila:

1. ✅ Seorang NGO boleh daftar, dapat approved, dan setup kempen dalam < 30 minit
2. ✅ Seorang donor boleh buat one-time donation dan terima resit email dalam < 2 minit
3. ✅ NGO admin boleh cipta campaign dan donation element tanpa bantuan developer
4. ✅ Seorang donor boleh setup recurring donation bulanan dan platform auto-charge bulan berikutnya tanpa intervensi manual
5. ✅ Donor boleh urus subscription melalui Donor Portal
6. ✅ NGO boleh lihat MRR, senarai donor, recurring subscriptions, transactions, dan eksport CSV dari dashboard
7. ✅ Widget berfungsi bila embed di website luaran
8. ✅ Semua email notification berfungsi (resit, payment failed, cancellation)
9. ✅ Bayaran gagal di-retry secara automatik mengikut jadual dunning

---

*Dokumen ini akan dikemas kini mengikut keperluan semasa pembangunan. Sebarang perubahan skop mesti dipersetujui oleh pemilik produk.*
