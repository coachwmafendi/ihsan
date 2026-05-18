# Product Requirements Document (PRD)
## Ihsan — Platform Derma & Fundraising Berulang untuk NGO Malaysia

**Version:** 1.2  
**Tarikh:** 18 Mei 2026  
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
| Virtual Terminal | V2 | Admin-created manual donations/payment entry |
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
5. Platform owner boleh approve NGO dan memantau transaksi asas.

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
- [ ] Borang pendaftaran NGO (nama, ROB/ROS number, maklumat bank)
- [ ] Upload dokumen pengesahan (sijil pendaftaran)
- [ ] Approval manual oleh platform admin
- [ ] Setup profil organisasi (logo, penerangan, kategori)

#### 4.1.2 Pengurusan Kempen
- [ ] Buat kempen dengan tajuk, penerangan, target amount, dan tarikh tutup
- [ ] Upload gambar kempen
- [ ] Toggle: kempen dengan target / tanpa target (general fund)
- [ ] Share link kempen (URL unik per kempen)

#### 4.1.3 NGO Admin Console
- [ ] Insights ringkasan: total raised, MRR, active recurring donors, donor baru, donation conversion signal asas
- [ ] Navigation utama: Insights, Donations, Recurring, Campaigns, Supporters/Donors, Elements, Exports, Settings
- [ ] Insights dengan filter asas: date range, aggregation (daily/weekly/monthly), campaign, source/UTM, frequency
- [ ] Insights sections MVP: Overview, Performance, Recurring plans, Recurring revenue, Retention asas, Payment methods, Elements, URL, UTM
- [ ] Senarai donor/supporter dengan status, total given, last donation, dan recurring status
- [ ] Senarai recurring plans/subscriptions dengan status aktif, past due, paused, cancelled
- [ ] Senarai transaksi dengan filter campaign, status, tarikh, dan type
- [ ] Eksport CSV untuk donors dan transactions
- [ ] Settings organisasi: profil, logo, warna brand, bank/Stripe, email receipt settings

#### 4.1.4 Donation Elements & Checkout
- [ ] Suggested amounts (3 pilihan + custom amount)
- [ ] Pilihan: One-time atau Recurring (weekly / monthly / yearly)
- [ ] Payment via **Stripe** (Visa/Mastercard, Apple Pay, Google Pay)
- [ ] Embeddable donation element — JavaScript snippet yang NGO boleh letak di website sendiri
- [ ] Standalone donation page (hosted di Ihsan)
- [ ] Email resit automatik kepada donor selepas bayar

#### 4.1.5 Recurring Subscription Management
- [ ] Stripe Subscription untuk handle auto-billing
- [ ] Smart dunning — retry bayaran gagal pada hari ke-3, 7, dan 14
- [ ] Notifikasi email kepada donor bila bayaran gagal
- [ ] **Donor Portal Lite** — login tanpa password (magic link), boleh:
  - Lihat sejarah derma
  - Cancel derma
- [ ] Pause dan tukar amount boleh dibuat oleh NGO admin dalam MVP pertama; self-service donor penuh masuk fasa selepas MVP jika perlu.

#### 4.1.6 Admin Platform (Super Admin)
- [ ] Senarai semua NGO dan status (pending/active/suspended)
- [ ] Approve/reject permohonan NGO baru
- [ ] Lihat semua transaksi platform
- [ ] Overview revenue (platform fees collected)

### 4.2 Luar Skop MVP (Backlog)

Ini adalah feature yang penting tapi **tidak** dibina dalam MVP. Akan dimasukkan dalam versi seterusnya berdasarkan feedback:

| Feature | Fasa |
|---------|------|
| FPX / DuitNow / TNG eWallet | V2 |
| Zakat & Sedekah module | V2 |
| LHDN tax-exempt receipt automation | V2 |
| White-label (NGO guna domain sendiri) | V2 |
| Donor Portal penuh: tukar amount, pause sendiri, update payment method | V2 |
| Advanced conversion analytics seperti benchmark Fundraise Up | V2 |
| Designations | V2 |
| Virtual Terminal | V2 |
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
| One-time payment | Stripe Checkout atau Stripe Elements |
| Recurring payment | Stripe Subscriptions + Stripe Billing |
| Webhook handling | Stripe webhooks untuk event: `invoice.paid`, `invoice.payment_failed`, `customer.subscription.deleted` |
| Platform fee | Deducted via Stripe Connect (NGO connect account) |
| Payout ke NGO | Stripe Connect — auto payout setiap 7 hari |
| Refund | Manual oleh NGO Admin dalam 7 hari |

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

### 5.4 Embeddable Widget

```html
<!-- Cara NGO embed widget Ihsan di website mereka -->
<script src="https://ihsan.my/widget.js" 
        data-campaign="abc123"
        data-theme="light">
</script>
```

- Widget render dalam `<iframe>` sandboxed
- Responsive (mobile-first, minimum 320px width)
- Customizable: warna primary, logo NGO
- Load async — tidak block website host

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
- Tetapan — profil NGO, bank/Stripe, branding, receipt settings (`/settings`)

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
  donations            → Rekod setiap transaksi (one-time)
  subscriptions        → Recurring subscription (link ke Stripe Subscription)
  platform_fees        → Rekod fee yang dikutip platform
  webhook_logs         → Log semua Stripe webhook events
```

Hubungan penting:
- `donor` → boleh ada banyak `subscriptions` kepada banyak `campaigns`
- `subscription` → ada banyak `donations` recurring (satu donation per billing cycle)
- `campaign` → belongs to `organization`, ada banyak `donations` dan `subscriptions`

---

## 10. Model Perniagaan

### 10.1 Struktur Fee MVP

| Jenis | Kadar |
|-------|-------|
| Platform fee | 3% per transaksi |
| Stripe processing fee | 2.2% + RM 1.30 per transaksi |
| **Total donor tanggung** | ~5.2% + RM 1.30 |
| Monthly subscription (NGO) | **Percuma** semasa MVP |

> **Nota:** Semasa MVP, tiada monthly fee dikenakan kepada NGO. Ini untuk kurangkan barrier onboarding. Selepas terbukti nilai, plan berbayar boleh diperkenalkan.

### 10.2 Aliran Wang (Stripe Connect)

1. Donor bayar RM 100 kepada kempen NGO
2. Stripe potong 2.2% + RM 1.30 = RM 3.50
3. Platform Ihsan potong 3% dari jumlah kasar = RM 3.00 (via Stripe application_fee_amount)
4. NGO terima RM 93.50 terus dalam Stripe Connect account mereka
5. Payout automatik ke akaun bank NGO setiap 7 hari

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

- [ ] Setup Laravel project, database migrations, seeding
- [ ] Auth system (NGO Admin + Super Admin)
- [ ] Stripe Connect integration (test mode)
- [ ] Organization onboarding flow
- [ ] NGO Admin shell: navigation, settings, dashboard kosong

### Fasa 2 — Campaigns, Elements & Core Donation Flow (Minggu 4–6)

- [ ] Campaign CRUD
- [ ] Elements CRUD (form/button/popup config asas)
- [ ] Donation form (one-time + recurring)
- [ ] Stripe Checkout integration
- [ ] Webhook handler (`invoice.paid`, `payment_failed`, `subscription.deleted`)
- [ ] Email resit automatik

### Fasa 3 — NGO Operations Dashboard (Minggu 7–9)

- [ ] Insights page: total raised chart, first installments, one-time donations, recurring revenue, payment methods
- [ ] Donations, Supporters, dan Recurring list pages
- [ ] Recurring subscription list dan status management
- [ ] Donor Portal Lite (magic link, history, cancel)
- [ ] CSV export
- [ ] Smart dunning logic

### Fasa 4 — Widget, Super Admin & Polish (Minggu 10–12)

- [ ] Embeddable JavaScript widget
- [ ] Super Admin panel
- [ ] QA testing menyeluruh
- [ ] Onboard 3 early adopter NGO (beta)

### 12.1 Rujukan Screenshot Fundraise Up

Pemilik produk mempunyai akses login Fundraise Up dan boleh menyediakan screenshot/menu sebagai rujukan. Setiap screenshot yang diterima akan diklasifikasikan kepada:

- **MVP now:** penting untuk NGO admin app pertama
- **V2:** berguna tetapi tidak perlu untuk transaksi pertama
- **Backlog:** advanced optimization atau nice-to-have
- **Not applicable:** tidak sesuai untuk konteks NGO Malaysia atau Stripe Malaysia

Screenshot pertama yang diterima: **Insights page**. Keputusan awal:

- **MVP now:** Insights, Donations, Recurring, Campaigns, Supporters, Elements, Exports, Settings
- **V2:** Designations, Virtual Terminal, advanced compare/performance analytics
- **V3/Backlog:** Fundraisers, Benefits, Gift catalogs, Tributes
- **MVP Insights filters:** date range, aggregation, campaign, source/UTM, frequency

---

## 13. Kriteria Penerimaan MVP

MVP dianggap **selesai** apabila:

1. ✅ Seorang NGO boleh daftar, dapat approved, dan setup kempen dalam < 30 minit
2. ✅ Seorang donor boleh buat one-time donation dan terima resit email dalam < 2 minit
3. ✅ NGO admin boleh cipta campaign dan donation element tanpa bantuan developer
4. ✅ Seorang donor boleh setup recurring donation bulanan dan platform auto-charge bulan berikutnya tanpa intervensi manual
5. ✅ Donor boleh cancel subscription melalui Donor Portal Lite
6. ✅ NGO boleh lihat MRR, senarai donor, recurring subscriptions, transactions, dan eksport CSV dari dashboard
7. ✅ Widget berfungsi bila embed di website luaran
8. ✅ Semua email notification berfungsi (resit, payment failed, cancellation)
9. ✅ Bayaran gagal di-retry secara automatik mengikut jadual dunning

---

*Dokumen ini akan dikemas kini mengikut keperluan semasa pembangunan. Sebarang perubahan skop mesti dipersetujui oleh pemilik produk.*
