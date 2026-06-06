# Entity Relationship Diagram (ERD)
## Ihsan — MVP Database Design

**Version:** 1.8
**Tarikh:** 6 Jun 2026
**Database:** SQLite untuk local dev, MySQL 8/PostgreSQL untuk production
**Framework:** Laravel 13

---

## Keputusan Rekabentuk

| Keputusan | Pilihan |
|-----------|---------|
| Multi-tenancy | Shared Database + `organization_id` di semua table |
| Donor Identity | Global Donor — satu rekod merentasi semua NGO |
| Designations | Skip MVP — masuk V2 |
| MVP pertama | NGO Admin app + donation flow minimum |
| Product benchmark | Fundraise Up-style admin UX, disesuaikan untuk Ihsan |

## Fokus MVP Pertama

ERD ini menyokong MVP yang bermula dengan app untuk NGO admin:

- `organizations`, `users`, dan `organization_documents` untuk onboarding dan approval NGO
- `campaigns` dan `elements` untuk fundraising setup yang boleh diurus sendiri oleh NGO admin
- `donors`, `donations`, dan `subscriptions` untuk supporter, transaksi, dan recurring revenue
- `processing_fees`, `monthly_invoices`, dan `webhook_logs` untuk operasi pembayaran, rekonsiliasi, invois fee, dan audit

Donor Portal menggunakan magic link tanpa password. MVP kini menyokong sejarah derma, download receipt, pembatalan subscription, pause/resume, tukar amount, dan update payment method. Fungsi lanjutan seperti preferences donor atau komunikasi segmentation boleh ditambah kemudian tanpa menukar struktur data utama.

Insights MVP tidak memerlukan table analytics khas. Halaman Insights boleh dikira terus daripada `donations`, `subscriptions`, `campaigns`, `elements`, `donor_country`, payment method fields, dan `utm_params` pada `donations`. Jika volume data meningkat, aggregate/materialized summary table boleh ditambah kemudian tanpa mengubah model transaksi utama.

---

## 1. Diagram ERD (Mermaid)

```mermaid
erDiagram

    USERS {
        bigint id PK
        string public_id UK "U + 7 chars - public-facing ID"
        bigint organization_id FK "nullable - null for super_admin"
        string name
        string email UK
        string password
        enum role "super_admin|ngo_admin"
        timestamp email_verified_at
        text two_factor_secret "nullable"
        text two_factor_recovery_codes "nullable"
        timestamp two_factor_confirmed_at "nullable"
        string avatar_url "nullable"
        timestamp last_login_at "nullable"
        timestamps created_at updated_at
    }

    ORGANIZATIONS {
        bigint id PK
        string code UK
        string name
        string ros_rob_number UK "nullable"
        enum registration_type "ros|rob|others"
        text description
        string logo_path
        string website_url
        string contact_email
        string contact_phone
        string address_line_1 "nullable"
        string address_line_2 "nullable"
        string city "nullable"
        string state "nullable"
        string postcode "nullable"
        string country "nullable"
        string sector "nullable"
        boolean tax_exempt
        enum status "pending|active|suspended|rejected"
        string stripe_account_id UK "nullable"
        boolean stripe_onboarded
        timestamp stripe_onboarded_at "nullable"
        string bank_account_name
        string bank_account_number
        string bank_name
        json settings
        decimal processing_fee_override "nullable"
        string fee_collection_method "nullable"
        text admin_notes "nullable"
        timestamp approved_at
        bigint approved_by FK "nullable - FK to users"
        timestamps created_at updated_at
    }

    ORGANIZATION_DOCUMENTS {
        bigint id PK
        bigint organization_id FK
        enum document_type "registration_cert|bank_statement|others"
        string file_path
        string original_filename
        timestamps created_at updated_at
    }

    CAMPAIGNS {
        bigint id PK
        string public_id UK "IH + 6 chars - public-facing ID"
        bigint organization_id FK
        string title
        text description
        string image_path
        string headline "nullable"
        string short_summary "nullable"
        decimal target_amount "nullable"
        decimal collected_amount "default 0"
        decimal minimum_amount "nullable"
        boolean has_target
        boolean allow_recurring
        boolean allow_custom_amount
        date end_date "nullable"
        enum status "draft|active|paused|ended"
        json suggested_amounts
        json suggested_amounts_one_time "nullable"
        json suggested_amounts_monthly "nullable"
        boolean impact_descriptions_enabled
        decimal default_monthly_amount "nullable"
        string payment_gateway "nullable"
        text thank_you_message "nullable"
        string redirect_url "nullable"
        string form_parameter UK "nullable"
        boolean checkout_modal_enabled
        json checkout_allowed_domains "nullable"
        json milestones_notified "nullable"
        json config "nullable"
        boolean has_end_date
        timestamps created_at updated_at
    }

    DONORS {
        bigint id PK
        string public_id UK "DR + 6 chars - public-facing ID"
        string title "nullable - Mr|Mrs|Ms|etc"
        string name
        string occupation "nullable"
        string email UK "global - merentasi semua NGO"
        string phone "nullable"
        string stripe_customer_id UK "nullable"
        string magic_token "nullable"
        timestamp magic_token_expires_at "nullable"
        string photo_path "nullable"
        string address_line1 "nullable"
        string address_line2 "nullable"
        string address_city "nullable"
        string address_state "nullable"
        string address_postal_code "nullable"
        string country "nullable - ISO 3166-1 alpha-2"
        string locale "nullable - en|ms"
        timestamps created_at updated_at
    }

    DONATIONS {
        bigint id PK
        string public_id UK "D + 7 chars - public-facing ID"
        bigint campaign_id FK
        bigint donor_id FK
        bigint subscription_id FK "nullable - null if one-time"
        string stripe_payment_intent_id UK "nullable"
        string stripe_charge_id "nullable"
        decimal gross_amount
        decimal stripe_fee
        decimal processing_fee
        decimal net_amount
        string base_currency "nullable"
        decimal base_amount "nullable"
        decimal exchange_rate "nullable"
        string currency "default myr"
        enum status "pending|succeeded|failed|refunded"
        enum type "one_time|recurring"
        text donor_message "nullable"
        boolean is_anonymous
        json utm_params "nullable"
        string payment_method_brand "nullable - visa|mastercard|fpx|etc"
        string payment_method_type "nullable - card|fpx|grabpay|wallet|etc"
        string payment_method_last4 "nullable"
        string donor_country "nullable - card country ISO alpha-2"
        decimal donor_fee_covered "nullable"
        string invoice_number "nullable"
        timestamp receipt_sent_at "nullable"
        timestamp refunded_at "nullable"
        string device_type "nullable"
        string ip_address "nullable"
        string browser "nullable"
        string os "nullable"
        string page_url "nullable"
        string geo_city "nullable"
        string geo_region "nullable"
        string billing_address_line1 "nullable"
        string billing_address_line2 "nullable"
        string billing_address_city "nullable"
        string billing_address_state "nullable"
        string billing_address_postal_code "nullable"
        string billing_country "nullable"
        integer risk_score "nullable"
        string risk_level "nullable"
        string avs_result "nullable"
        string cvc_result "nullable"
        string fraud_status "nullable - clean|flagged|blocked"
        json stripe_fee_details "nullable"
        timestamps created_at updated_at
    }

    SUBSCRIPTIONS {
        bigint id PK
        string public_id UK "R + 7 chars - public-facing ID"
        bigint campaign_id FK
        bigint donor_id FK
        string stripe_subscription_id UK
        string stripe_price_id "nullable"
        decimal amount
        string currency "default myr"
        enum interval "weekly|monthly|yearly"
        enum status "active|paused|cancelled|past_due|incomplete"
        tinyint retry_count "default 0 max 3"
        tinyint payment_count "default 0 - berapa kali dah bayar"
        timestamp current_period_start "nullable"
        timestamp current_period_end "nullable"
        timestamp paused_until "nullable"
        timestamp cancelled_at "nullable"
        boolean cancel_at_period_end
        timestamp cancel_at "nullable"
        boolean cover_fee
        timestamps created_at updated_at
    }

    PROCESSING_FEES {
        bigint id PK
        bigint donation_id FK
        bigint organization_id FK
        decimal fee_amount
        decimal fee_percentage "snapshot kadar semasa transaksi"
        string stripe_transfer_id "nullable"
        enum status "pending|paid|failed"
        bigint monthly_invoice_id FK "nullable"
        timestamps created_at updated_at
    }

    MONTHLY_INVOICES {
        bigint id PK
        string public_id UK "I + 7 chars - public-facing ID"
        bigint organization_id FK
        string stripe_invoice_id UK
        string invoice_number UK
        date period
        decimal total_fees
        enum stripe_status "draft|open|paid|void|uncollectible"
        timestamp paid_at "nullable"
        string stripe_invoice_url "nullable"
        string stripe_invoice_pdf "nullable"
        timestamps created_at updated_at
    }

    ELEMENTS {
        bigint id PK
        string public_id UK "E + 7 chars - public-facing ID"
        bigint organization_id FK
        bigint campaign_id FK "nullable"
        string name
        string token UK "untuk embed widget"
        enum type "button|floating_button|form|popup|qr_code|link"
        json config "warna, copy, amounts, behavior, dll"
        string form_slug UK "nullable"
        boolean is_donor_portal_default
        boolean is_active
        timestamps created_at updated_at
    }

    WEBHOOK_LOGS {
        bigint id PK
        string stripe_event_id UK
        string event_type
        json payload
        enum status "received|processed|failed|ignored"
        text error_message "nullable"
        timestamp processed_at "nullable"
        timestamps created_at updated_at
    }

    FRAUD_RULES {
        bigint id PK
        bigint organization_id FK "nullable - null = global rule"
        string rule_type "velocity|amount|pattern|country|card"
        json config "threshold, window, countries, patterns, dll"
        string action "flag|block|notify"
        boolean is_active
        timestamps created_at updated_at
    }

    FRAUD_ATTEMPTS {
        bigint id PK
        bigint donor_id FK "nullable"
        string email
        string ip_address
        string card_fingerprint "nullable"
        decimal amount
        string currency
        string reason
        string action "flagged|blocked"
        json metadata "nullable"
        timestamps created_at updated_at
    }

    BLOCKED_DONATIONS {
        bigint id PK
        bigint donation_id FK
        string reason
        enum review_status "pending|reviewed|released"
        bigint reviewed_by FK "nullable - FK to users"
        timestamp reviewed_at "nullable"
        text review_notes "nullable"
        timestamps created_at updated_at
    }

    SETTINGS {
        bigint id PK
        string key UK
        text value
        timestamps created_at updated_at
    }

    %% Relationships
    USERS }o--|| ORGANIZATIONS : "belongs to (ngo_admin)"
    ORGANIZATIONS ||--o{ ORGANIZATION_DOCUMENTS : "has many"
    ORGANIZATIONS ||--o{ CAMPAIGNS : "has many"
    ORGANIZATIONS ||--o{ ELEMENTS : "has many"
    ORGANIZATIONS ||--o{ PROCESSING_FEES : "has many"
    ORGANIZATIONS ||--o{ MONTHLY_INVOICES : "has many"
    CAMPAIGNS ||--o{ DONATIONS : "receives"
    CAMPAIGNS ||--o{ SUBSCRIPTIONS : "has many"
    CAMPAIGNS ||--o{ ELEMENTS : "has many (optional)"
    DONORS ||--o{ DONATIONS : "makes"
    DONORS ||--o{ SUBSCRIPTIONS : "holds"
    SUBSCRIPTIONS ||--o{ DONATIONS : "generates recurring"
    DONATIONS ||--|| PROCESSING_FEES : "generates one"
    MONTHLY_INVOICES ||--o{ PROCESSING_FEES : "collects"
    ORGANIZATIONS ||--o{ FRAUD_RULES : "defines"
    DONORS ||--o{ FRAUD_ATTEMPTS : "triggers"
    DONATIONS ||--|| BLOCKED_DONATIONS : "may be blocked"
    USERS ||--o{ BLOCKED_DONATIONS : "reviews"
    ```

---

## 2. Penerangan Setiap Entiti

### 2.1 `users`
Pengguna platform yang ada akses kepada admin panel — bukan donor. Donor diurus dalam jadual `donors` berasingan kerana mereka tidak perlu akaun penuh.

| Kolum | Jenis | Keterangan |
|-------|-------|------------|
| `public_id` | string unique | ID public-facing 8 aksara: `U` + 7 aksara rawak (A–Z, 1–9). Digunakan di UI dan URL untuk menyembunyikan auto-increment ID |
| `role` | enum | `super_admin` = pemilik platform Ihsan; `ngo_admin` = pentadbir NGO |
| `organization_id` | FK nullable | NULL untuk super_admin |
| `two_factor_*` | text/timestamp nullable | Fortify 2FA fields untuk admin access |
| `avatar_url` | string nullable | Avatar admin dalam panel |
| `last_login_at` | timestamp nullable | Audit aktiviti login terakhir |

---

### 2.2 `organizations`
Entiti utama yang mewakili NGO, masjid, atau badan amal yang berdaftar di Ihsan.

| Kolum | Jenis | Keterangan |
|-------|-------|------------|
| `code` | string unique | Kod organisasi untuk route donor portal, contoh: `/donorportal/MSJ001` |
| `ros_rob_number` | string unique nullable | Nombor pendaftaran ROS/ROB — wajib untuk KYC |
| `stripe_account_id` | string | ID Stripe Connect Express account NGO |
| `stripe_onboarded` | boolean | TRUE bila NGO dah selesai Stripe onboarding flow |
| `stripe_onboarded_at` | timestamp nullable | Masa onboarding Stripe Connect selesai |
| `status` | enum | `pending` selepas daftar; `active` selepas approved oleh super_admin |
| `settings` | json | Konfigurasi ringan organisasi: notification preferences, default currency, dll |
| `processing_fee_override` | decimal nullable | Kadar processing fee khusus NGO, jika berbeza daripada default platform |
| `fee_collection_method` | string nullable | Cara kutipan fee, contohnya application fee atau monthly invoice |
| `admin_notes` | text nullable | Nota dalaman platform owner |
| `tax_exempt` | boolean | Flag untuk organisasi yang layak receipt tax-exempt |

---

### 2.3 `campaigns`
Kempen fundraising yang dibuat oleh NGO. Satu NGO boleh ada berbilang kempen aktif.

| Kolum | Jenis | Keterangan |
|-------|-------|------------|
| `public_id` | string unique | ID public-facing 8 aksara: `IH` + 6 aksara rawak (A–Z, 1–9). Digunakan di URL kempen dan share link |
| `has_target` | boolean | FALSE = general fund tanpa target |
| `collected_amount` | decimal | Dikemas kini setiap kali `donations.status = succeeded` |
| `suggested_amounts` | json | Legacy/default suggested amount set |
| `suggested_amounts_one_time` | json nullable | Amount cadangan untuk one-time donations |
| `suggested_amounts_monthly` | json nullable | Amount cadangan untuk recurring monthly donations |
| `allow_recurring` | boolean | FALSE = one-time sahaja |
| `allow_custom_amount` | boolean | FALSE = donor hanya boleh pilih amount cadangan |
| `form_parameter` | string unique nullable | Slug/token URL untuk campaign checkout modal |
| `checkout_modal_enabled` | boolean | TRUE bila campaign boleh dibuka dalam modal embed |
| `checkout_allowed_domains` | json nullable | Senarai domain yang dibenarkan membuka checkout modal |
| `milestones_notified` | json nullable | Senarai milestone kempen yang sudah dihantar notification |
| `config` | json nullable | Konfigurasi tambahan campaign yang tidak memerlukan kolum khusus |
| `has_end_date` | boolean | TRUE bila kempen mempunyai tarikh tutup; FALSE = kempen berterusan (general fund) |
| `status` | enum | `draft` = belum published; `active` = live |

---

### 2.4 `donors`
**Global donor** — satu rekod per email merentasi semua NGO. Privacy dijaga melalui query scope melalui campaigns, bukan data separation.

| Kolum | Jenis | Keterangan |
|-------|-------|------------|
| `public_id` | string unique | ID public-facing 8 aksara: `DR` + 6 aksara rawak (A–Z, 1–9). Digunakan di donor portal dan receipt |
| `title` | string nullable | Panggilan hormat: `Mr`, `Mrs`, `Ms`, `Miss`, `Dr`, dll |
| `name` | string | Nama penuh donor |
| `occupation` | string nullable | Pekerjaan: `Employed`, `Self-employed`, `Business owner`, `Student`, `Retired`, `Unemployed`, `Other` |
| `email` | string unique globally | Satu donor = satu email, walaupun derma kepada berbilang NGO |
| `phone` | string nullable | Nombor telefon |
| `stripe_customer_id` | string unique | Satu Stripe Customer ID untuk semua transaksi donor ini |
| `magic_token` | string | Token sementara untuk akses donor portal (tanpa password) |
| `magic_token_expires_at` | timestamp | Token expired selepas 30 minit |
| `photo_path` | string nullable | Path foto profil di storage private |
| `address_line1` | string nullable | Alamat baris 1 |
| `address_line2` | string nullable | Alamat baris 2 |
| `address_city` | string nullable | Bandar |
| `address_state` | string nullable | Negeri |
| `address_postal_code` | string nullable | Poskod |
| `country` | string nullable | Kod negara ISO 3166-1 alpha-2 |
| `locale` | string nullable | Bahasa pilihan: `en` (English), `ms` (Bahasa Melayu) |

> **Nota Privacy:** NGO hanya boleh "nampak" donor yang ada donations/subscriptions kepada campaign mereka. Query mesti sentiasa scope melalui `campaigns.organization_id`.

---

### 2.5 `donations`
Rekod setiap transaksi tunggal — sama ada one-time atau satu bayaran daripada subscription berulang.

| Kolum | Jenis | Keterangan |
|-------|-------|------------|
| `public_id` | string unique | ID public-facing 8 aksara: `D` + 7 aksara rawak (A–Z, 1–9). Digunakan di receipt, URL, dan komunikasi dengan donor |
| `subscription_id` | FK nullable | NULL = one-time; ada nilai = dijana oleh subscription |
| `type` | enum | `one_time` atau `recurring` |
| `gross_amount` | decimal | Jumlah yang donor bayar |
| `stripe_fee` | decimal | Fee Stripe yang sebenar dari BalanceTransaction |
| `processing_fee` | decimal | Fee Ihsan, default 2.5% daripada gross amount atau override organisasi |
| `net_amount` | decimal | Yang masuk ke NGO (`gross - stripe_fee - processing_fee`) |
| `payment_method_brand` | string | Jenama kad: `visa`, `mastercard`, atau type method untuk non-card |
| `payment_method_type` | string | Method type dari Stripe: `card`, `fpx`, `grabpay`, `wallet` |
| `donor_country` | string nullable | Negara kad donor daripada PaymentMethod card country; digunakan untuk filter/analytics |
| `donor_fee_covered` | decimal nullable | Tambahan amount yang donor cover untuk estimated processing fee |
| `base_currency` / `base_amount` / `exchange_rate` | mixed nullable | Snapshot conversion jika donation bukan dalam currency asas organisasi |
| `receipt_sent_at` | timestamp nullable | Masa receipt email dihantar |
| `refunded_at` | timestamp nullable | Masa donation ditanda refunded |
| `stripe_fee_details` | json nullable | Pecahan fee daripada Stripe BalanceTransaction |
| `utm_params` | json | Track sumber traffic: `{source, medium, campaign}` |
| `is_anonymous` | boolean | TRUE = nama donor tidak dipaparkan di halaman kempen |
| `risk_score` | integer nullable | Skor risiko dari 0–100 oleh sistem fraud detection |
| `risk_level` | string nullable | `low`, `medium`, `high` — dikira daripada `risk_score` |
| `avs_result` | string nullable | Keputusan Address Verification Service daripada Stripe |
| `cvc_result` | string nullable | Keputusan CVC check daripada Stripe |
| `fraud_status` | string nullable | `clean`, `flagged` (untuk semakan), atau `blocked` (dihalang) |

---

### 2.6 `subscriptions`
Rekod recurring subscription. Satu subscription = satu donor → satu campaign dengan interval tertentu.

| Kolum | Jenis | Keterangan |
|-------|-------|------------|
| `public_id` | string unique | ID public-facing 8 aksara: `R` + 7 aksara rawak (A–Z, 1–9). Digunakan di donor portal untuk manage subscription |
| `stripe_subscription_id` | string unique | ID dari Stripe untuk sync status |
| `status` | enum | Sync dengan Stripe Subscription status |
| `retry_count` | tinyint | Bilangan kali bayaran gagal dicuba (max 3, dunning logic) |
| `payment_count` | tinyint | Bilangan kali bayaran berjaya — dikira dari `invoice.paid` webhook |
| `paused_until` | timestamp | Set bila donor pause — resume otomatik selepas tarikh ini |
| `current_period_start/end` | timestamp | Kitaran billing semasa dari Stripe |
| `cancel_at_period_end` | boolean | TRUE bila subscription dijadualkan batal hujung period |
| `cancel_at` | timestamp nullable | Masa pembatalan berjadual dari Stripe |
| `cover_fee` | boolean | TRUE bila recurring donation cover estimated processing fee |

---

### 2.7 `processing_fees`
Rekod asing untuk setiap fee yang dikutip Ihsan. Memudahkan rekonsiliasi kewangan, pelaporan revenue, dan invois bulanan.

| Kolum | Jenis | Keterangan |
|-------|-------|------------|
| `stripe_transfer_id` | string | ID Stripe Transfer bila fee dipindahkan ke akaun platform Ihsan |
| `fee_percentage` | decimal | Snapshot kadar fee semasa transaksi (in case kadar berubah masa depan) |
| `monthly_invoice_id` | FK nullable | Link kepada `monthly_invoices` jika fee dikutip melalui invois bulanan |
| `status` | enum | `pending`, `invoiced`, `paid`, atau `failed` |

---

### 2.8 `monthly_invoices`
Rekod Stripe Invoice untuk accumulated processing fees setiap organisasi dan period.

| Kolum | Jenis | Keterangan |
|-------|-------|------------|
| `public_id` | string unique | ID public-facing 8 aksara: `I` + 7 aksara rawak (A–Z, 1–9). Digunakan di URL invois dan download link |
| `period` | date | Bulan invois, disimpan sebagai tarikh permulaan bulan |
| `total_fees` | decimal | Jumlah `processing_fees` yang dimasukkan ke invoice |
| `stripe_status` | string | Status invoice dari Stripe |
| `paid_at` | timestamp nullable | Masa invoice dibayar |
| `stripe_invoice_url` | string nullable | Hosted invoice URL dari Stripe |
| `stripe_invoice_pdf` | string nullable | PDF invoice URL dari Stripe |

---

### 2.9 `elements`
Donation element instances yang dibuat oleh NGO untuk embed di website mereka. Satu NGO boleh ada berbilang elements dengan config berbeza, sama ada inline form, button, atau popup. Konsep ini mengambil inspirasi daripada fundraising platforms seperti Fundraise Up, tetapi config sebenar untuk MVP kekal ringkas.

| Kolum | Jenis | Keterangan |
|-------|-------|------------|
| `public_id` | string unique | ID public-facing 8 aksara: `E` + 7 aksara rawak (A–Z, 1–9). Digunakan di URL element dan share link |
| `token` | string unique | Public token untuk widget script: `data-token="TOKEN"` |
| `type` | enum | `button`, `floating_button`, `form`, `popup`, `qr_code`, `link` |
| `config` | json | Warna, copy, action, trigger, layout, image, behavior, dll |
| `campaign_id` | FK nullable | NULL = donor pilih campaign sendiri; ada nilai = locked ke campaign tertentu |
| `form_slug` | string unique nullable | Slug tambahan untuk hosted form/embed legacy |
| `is_donor_portal_default` | boolean | TRUE jika element digunakan sebagai default donor portal entry |

Untuk MVP pertama, `elements.config` boleh menyimpan struktur minimum seperti:

```json
{
  "title": "Support Our Campaign Today",
  "message": "Every contribution helps.",
  "button_text": "Donate Now",
  "action": "checkout_modal",
  "trigger": "after_delay",
  "delay": 8,
  "frequency": "once_per_day",
  "visibility": "desktop_mobile",
  "layout": "simple",
  "image": null,
  "color": "campaign"
}
```

---

### 2.10 `webhook_logs`
Log semua Stripe webhook events yang diterima. Kritikal untuk debugging dan memastikan tiada event yang terlepas atau diproses dua kali.

| Kolum | Jenis | Keterangan |
|-------|-------|------------|
| `stripe_event_id` | string unique | Unique ID dari Stripe — mencegah duplicate processing (idempotency) |
| `event_type` | string | Contoh: `invoice.paid`, `customer.subscription.deleted` |
| `status` | enum | `processing` semasa job berjalan; `completed` bila berjaya; `failed` jika exception/error perlu diulang siasat |

---

### 2.11 `fraud_rules`
Peraturan deteksi penipuan (fraud detection) yang boleh ditakrif oleh super admin atau per organisasi. Mengandungi logik threshold, velocity check, dan pattern matching.

| Kolum | Jenis | Keterangan |
|-------|-------|------------|
| `rule_type` | string | Jenis peraturan: `velocity` (terlalu banyak cubaan), `amount` (jumlah luar biasa), `pattern` (corak mencurigakan), `country` (negara berisiko), `card` (kad berulang) |
| `config` | json | Konfigurasi spesifik peraturan: threshold amount, time window, senarai negara/card yang diblok, dll |
| `action` | string | `flag` = tandakan untuk semakan; `block` = halang terus; `notify` = hantar alert sahaja |
| `is_active` | boolean | TRUE = peraturan aktif dan digunakan semasa penilaian transaksi |
| `organization_id` | FK nullable | NULL = peraturan global untuk semua NGO; ada nilai = khusus untuk NGO tersebut |

---

### 2.12 `fraud_attempts`
Log setiap cubaan transaksi yang ditandakan atau dihalang oleh sistem fraud detection. Digunakan untuk audit dan analisis trend penipuan.

| Kolum | Jenis | Keterangan |
|-------|-------|------------|
| `donor_id` | FK nullable | Link kepada donor jika dikenal pasti; NULL untuk guest/checkout awal |
| `email` | string | Email yang digunakan semasa cubaan |
| `ip_address` | string | Alamat IP sumber |
| `card_fingerprint` | string nullable | Fingerprint kad daripada Stripe untuk mengesan kad yang sama digunakan berulang kali |
| `amount` / `currency` | decimal/string | Jumlah dan mata wang cubaan |
| `reason` | string | Sebab tindakan diambil — contoh: "Velocity threshold exceeded" |
| `action` | string | `flagged` atau `blocked` |
| `metadata` | json nullable | Butiran lanjut tentang peraturan yang dipicu |

---

### 2.13 `blocked_donations`
Rekod donation yang dihalang (blocked) oleh sistem fraud. Donation tetap wujud dalam jadual `donations` dengan `fraud_status = blocked`, tetapi bayaran tidak diproses. Super admin boleh menyemak dan melepaskan (release) sekiranya kesilapan.

| Kolum | Jenis | Keterangan |
|-------|-------|------------|
| `donation_id` | FK | Link kepada donation yang dihalang |
| `reason` | string | Sebab blockage — sama dengan `fraud_attempts.reason` |
| `review_status` | enum | `pending` = belum disemak; `reviewed` = disemak dan kekal blocked; `released` = dilepaskan oleh super admin |
| `reviewed_by` | FK nullable | Super admin yang menyemak |
| `reviewed_at` | timestamp nullable | Masa semakan dibuat |
| `review_notes` | text nullable | Nota semakan |

---

### 2.14 `settings`
Jadual global untuk tetapan aplikasi yang tidak berkaitan dengan organisasi tertentu (kecuali dipersetujui untuk V2). MVP menggunakan ini untuk konfigurasi platform asas yang boleh diubah tanpa deploy kod.

| Kolum | Jenis | Keterangan |
|-------|-------|------------|
| `key` | string unique | Nama tetapan |
| `value` | text | Nilai tetapan (string/JSON) |

---

## 3. Hubungan Penting

### 3.1 Global Donor → Multi-NGO (melalui campaigns)

```text
donors ──< donations >── campaigns ──> organizations
donors ──< subscriptions >── campaigns ──> organizations
```

Donor privacy dijaga kerana NGO query donor melalui campaign mereka — bukan terus dari table `donors`.

### 3.2 Subscription → Donations (One-to-Many)

```text
subscriptions ──< donations
```

Setiap `invoice.paid` webhook dari Stripe → cipta satu rekod baru dalam `donations` dengan `subscription_id` yang sama.

### 3.3 Donation → Processing Fee (One-to-One)

```text
donations ──── processing_fees
```

Setiap donation berjaya (`status = succeeded`) boleh menjana tepat satu rekod `processing_fees` apabila processing fee dikenakan.

---

## 4. Sistem `public_id`

`public_id` ialah pengenal unik berorientasi public untuk 7 entiti utama. Ia menyembunyikan auto-increment `id` daripada pengguna akhir dan URL, meningkatkan keselamatan dan estetik.

### Format
- **8 aksara total**, huruf besar A–Z + digit **1–9** (tiada 0).
- **Prefix tetap** per table + aksara rawak.

| Table | Prefix | Contoh |
|-------|--------|--------|
| `users` | `U` | `UAB3C9D2` |
| `campaigns` | `IH` | `IH7A3B9C` |
| `donors` | `DR` | `DR2E8F1G` |
| `donations` | `D` | `D4H5I6J7` |
| `subscriptions` | `R` | `R8K9L1M2` |
| `elements` | `E` | `E3N4O5P6` |
| `monthly_invoices` | `I` | `I7Q8R9S1` |

### Penjanaan
- Di-generate secara automatik oleh model observer semasa `creating`.
- Retry on collision (max 10 attempts).
- Boleh di-assign manual; observer tidak menimpa nilai sedia ada.
- Backfill command: `php artisan app:backfill-public-ids` untuk rekod lama.

---

## 5. Stripe Connect Money Flow

```text
Donor bayar RM 100
        │
        ▼
Stripe memproses bayaran
        │
        ├─► Stripe fee sebenar dari BalanceTransaction
        │
        ├─► Ihsan processing fee: RM 2.50 default (2.5%)
        │   └─► Rekod dalam processing_fees / application fee / monthly invoice
        │
        └─► NGO terima baki bersih
            └─► Payout ke bank NGO setiap 7 hari (Stripe Connect)
```

---

## 6. Stripe Webhook Events

| Event | Tindakan |
|-------|----------|
| `payment_intent.succeeded` | Sync Stripe fee/card details, tandakan donation berjaya, increment campaign, hantar receipt dan NGO notifications. Jalankan fraud detection — jika blocked, catat dalam `blocked_donations` dan hantar fraud alert. |
| `payment_intent.payment_failed` | Tandakan donation gagal; jika recurring, hantar failed payment notification |
| `invoice.paid` | Jika donor subscription: cipta recurring `donations`; jika processing fee invoice: update `monthly_invoices` dan `processing_fees` |
| `invoice.payment_failed` | Tambah `retry_count`, email donor, update `status = past_due` |
| `customer.subscription.deleted` | Update `status = cancelled`, set `cancelled_at`, email pengesahan |
| `customer.subscription.updated` | Sync `amount`, `status`, `current_period_*` |
| `charge.refunded` | Update `donations.status = refunded`, set `refunded_at`, decrement campaign collected amount, hantar refund notification |
| `account.updated` | Update `stripe_onboarded = true` bila NGO selesai onboarding |

---

## 7. Indeks Database

### 6.1 Indeks `public_id`
Setiap table dengan `public_id` mempunyai unique index:

```sql
CREATE UNIQUE INDEX idx_users_public_id ON users(public_id);
CREATE UNIQUE INDEX idx_campaigns_public_id ON campaigns(public_id);
CREATE UNIQUE INDEX idx_donors_public_id ON donors(public_id);
CREATE UNIQUE INDEX idx_donations_public_id ON donations(public_id);
CREATE UNIQUE INDEX idx_subscriptions_public_id ON subscriptions(public_id);
CREATE UNIQUE INDEX idx_elements_public_id ON elements(public_id);
CREATE UNIQUE INDEX idx_monthly_invoices_public_id ON monthly_invoices(public_id);
```

### 6.2 Indeks Lain

```sql
-- organizations
CREATE INDEX idx_org_status ON organizations(status);
CREATE INDEX idx_org_stripe ON organizations(stripe_account_id);

-- campaigns
CREATE INDEX idx_campaign_org ON campaigns(organization_id);
CREATE INDEX idx_campaign_status ON campaigns(status);

-- donations
CREATE INDEX idx_donation_campaign ON donations(campaign_id);
CREATE INDEX idx_donation_donor ON donations(donor_id);
CREATE INDEX idx_donation_subscription ON donations(subscription_id);
CREATE INDEX idx_donation_status ON donations(status);
CREATE INDEX idx_donation_type ON donations(type);
CREATE INDEX idx_donation_created ON donations(created_at);
CREATE INDEX idx_donation_payment_method ON donations(payment_method_type);
CREATE INDEX idx_donation_donor_country ON donations(donor_country);

-- subscriptions
CREATE INDEX idx_sub_donor ON subscriptions(donor_id);
CREATE INDEX idx_sub_campaign ON subscriptions(campaign_id);
CREATE INDEX idx_sub_status ON subscriptions(status);
CREATE INDEX idx_sub_stripe ON subscriptions(stripe_subscription_id);

-- donors
CREATE INDEX idx_donor_email ON donors(email);
CREATE INDEX idx_donor_stripe ON donors(stripe_customer_id);
CREATE INDEX idx_donor_magic_token ON donors(magic_token);

-- elements
CREATE INDEX idx_element_org ON elements(organization_id);
CREATE INDEX idx_element_campaign ON elements(campaign_id);
CREATE INDEX idx_element_token ON elements(token);
CREATE INDEX idx_element_form_slug ON elements(form_slug);

-- processing_fees
CREATE INDEX idx_processing_fee_org ON processing_fees(organization_id);
CREATE INDEX idx_processing_fee_donation ON processing_fees(donation_id);
CREATE INDEX idx_processing_fee_status ON processing_fees(status);
CREATE INDEX idx_processing_fee_monthly_invoice ON processing_fees(monthly_invoice_id);

-- monthly_invoices
CREATE INDEX idx_monthly_invoice_org ON monthly_invoices(organization_id);
CREATE INDEX idx_monthly_invoice_status ON monthly_invoices(stripe_status);
CREATE INDEX idx_monthly_invoice_period ON monthly_invoices(period);

-- webhook_logs
CREATE INDEX idx_webhook_event_id ON webhook_logs(stripe_event_id);
CREATE INDEX idx_webhook_type ON webhook_logs(event_type);
CREATE INDEX idx_webhook_status ON webhook_logs(status);

-- fraud_rules
CREATE INDEX idx_fraud_rule_org ON fraud_rules(organization_id);
CREATE INDEX idx_fraud_rule_type ON fraud_rules(rule_type);
CREATE INDEX idx_fraud_rule_active ON fraud_rules(is_active);

-- fraud_attempts
CREATE INDEX idx_fraud_attempt_email ON fraud_attempts(email);
CREATE INDEX idx_fraud_attempt_ip ON fraud_attempts(ip_address);
CREATE INDEX idx_fraud_attempt_created ON fraud_attempts(created_at);

-- blocked_donations
CREATE INDEX idx_blocked_donation ON blocked_donations(donation_id);
CREATE INDEX idx_blocked_review_status ON blocked_donations(review_status);
```

---

## 8. Query Penting

### MRR per NGO
```sql
SELECT SUM(s.amount) as mrr
FROM subscriptions s
JOIN campaigns c ON s.campaign_id = c.id
WHERE c.organization_id = ?
  AND s.status = 'active'
  AND s.interval = 'monthly';
```

### Donor Aktif per NGO
```sql
SELECT d.name, d.email,
       COUNT(DISTINCT dn.id) as total_donations,
       SUM(dn.gross_amount) as total_given,
       MAX(dn.created_at) as last_donation,
       s.status as subscription_status
FROM donors d
JOIN donations dn ON dn.donor_id = d.id
JOIN campaigns c ON dn.campaign_id = c.id
LEFT JOIN subscriptions s ON s.donor_id = d.id AND s.campaign_id = dn.campaign_id
WHERE c.organization_id = ?
GROUP BY d.id, s.status
ORDER BY total_given DESC;
```

### Dunning — Subscription Perlu Di-Retry

Nota: logik sebenar perlu DB-agnostic untuk SQLite test. Kira cutoff tarikh dalam PHP/Carbon mengikut `retry_count`, kemudian query dengan `updated_at <= ?`.

```sql
SELECT s.*
FROM subscriptions s
WHERE s.status = 'past_due'
  AND s.retry_count < 3
  AND s.updated_at <= ?;
```
