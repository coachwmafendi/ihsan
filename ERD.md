# Entity Relationship Diagram (ERD)
## Ihsan — MVP Database Design

**Version:** 2.2
**Tarikh:** 15 Ogos 2026
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
- `processing_fees`, `monthly_invoices`, `payouts`, dan `webhook_logs` untuk operasi pembayaran, rekonsiliasi, invois fee, payout, dan audit
- `donor_email_logs` untuk sejarah email, tracking, dan responsive preview
- `activity_log` (disediakan oleh package `spatie/laravel-activitylog`, bukan entiti custom) menyokong halaman Audit Log — merekod perubahan model (organizations, campaigns, donations, dll) dan artisan command activity secara polymorphic (`subject_type`/`subject_id`, `causer_type`/`causer_id`). Tidak dimasukkan dalam Mermaid diagram di bawah kerana ia struktur generik vendor, bukan domain entity Ihsan

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
        string public_id UK "O + 7 chars - public-facing ID"
        string code UK
        string name
        string ros_rob_number UK "nullable"
        enum registration_type "ros|rob|others"
        text description
        string logo_path
        string website_url
        string facebook_url "nullable"
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
        string chip_brand_id "nullable"
        text chip_api_key "nullable - encrypted"
        string chip_webhook_id "nullable"
        text chip_webhook_public_key "nullable - encrypted"
        boolean stripe_onboarded
        timestamp stripe_onboarded_at "nullable"
        boolean stripe_enabled "default true"
        boolean chip_enabled "default true"
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
        string slug UK
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
        boolean campaign_page_enabled
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
        string first_name "nullable"
        string last_name "nullable"
        string occupation "nullable"
        string email UK "global - merentasi semua NGO"
        string phone "nullable"
        string stripe_customer_id UK "nullable"
        string magic_token "nullable"
        timestamp magic_token_expires_at "nullable"
        timestamp email_opt_out_at "nullable"
        timestamp email_bounced_at "nullable"
        timestamp email_validated_at "nullable - disahkan melalui SES/SNS delivery webhook"
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
        string receipt_token UK "nullable - signed receipt download token"
        bigint campaign_id FK
        bigint donor_id FK
        bigint subscription_id FK "nullable - null if one-time"
        string source "nullable - element|campaign_page|checkout_modal|virtual_terminal"
        string stripe_payment_intent_id UK "nullable"
        string stripe_charge_id "nullable"
        string stripe_invoice_id "nullable"
        string chip_purchase_id "nullable"
        string chip_recurring_token "nullable"
        text chip_checkout_url "nullable"
        decimal gross_amount
        decimal stripe_fee
        decimal chip_fee "default 0"
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
        tinyint payment_method_exp_month "nullable"
        smallint payment_method_exp_year "nullable"
        string donor_country "nullable - card country ISO alpha-2"
        decimal donor_fee_covered "nullable"
        string invoice_number "nullable"
        timestamp receipt_sent_at "nullable"
        timestamp new_donation_notification_sent_at "nullable"
        timestamp large_donation_notification_sent_at "nullable"
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
        string source "nullable - element|campaign_page|checkout_modal|virtual_terminal"
        string stripe_subscription_id UK
        string stripe_price_id "nullable"
        string chip_recurring_token "nullable"
        decimal amount
        string currency "default myr"
        enum interval "weekly|monthly|yearly"
        enum status "active|paused|cancelled|past_due|incomplete"
        text last_failure_message "nullable - Stripe/CHIP failure reason"
        tinyint retry_count "default 0 max 3"
        tinyint payment_count "default 0 - berapa kali dah bayar"
        timestamp current_period_start "nullable"
        timestamp current_period_end "nullable"
        timestamp paused_until "nullable"
        timestamp cancelled_at "nullable"
        boolean cancel_at_period_end
        timestamp cancel_at "nullable"
        boolean cover_fee
        decimal fee_cover_amount "nullable"
        decimal max_plan_amount "nullable"
        integer max_plan_installments "nullable"
        string cancellation_reason "nullable"
        timestamps created_at updated_at
    }

    DONOR_EMAIL_LOGS {
        bigint id PK
        string public_id UK "EL + 6 chars - public-facing ID"
        bigint donor_id FK
        bigint organization_id FK "nullable"
        bigint donation_id FK "nullable - trigger email"
        bigint subscription_id FK "nullable - trigger email"
        bigint resent_from_id FK "nullable - self-referential resend"
        string mailable_class
        string message_id UK "nullable - provider message id"
        string provider_message_id "nullable"
        string subject
        string delivery_status "nullable"
        json metadata "nullable"
        timestamp sent_at "nullable"
        timestamp opened_at "nullable"
        timestamp delivered_at "nullable"
        timestamp bounced_at "nullable"
        string bounce_reason "nullable"
        timestamp complained_at "nullable"
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

    PAYOUTS {
        bigint id PK
        bigint organization_id FK
        string stripe_payout_id
        integer amount
        string currency
        string status
        date arrival_date
        date paid_at "nullable"
        string bank_name "nullable"
        string bank_account_last4 "nullable"
        string failure_code "nullable"
        string failure_message "nullable"
        json metadata "nullable"
        timestamps created_at updated_at
    }

    ELEMENTS {
        bigint id PK
        string public_id UK "E + 7 chars - public-facing ID"
        bigint organization_id FK
        bigint campaign_id FK "nullable"
        string name
        string token UK "untuk embed widget"
        enum type "button|floating_button|sticky_button|form|popup|qr_code|link"
        json config "warna, copy, amounts, behavior, dll"
        string form_slug UK "nullable"
        boolean is_donor_portal_default
        boolean is_active
        timestamp archived_at "nullable"
        timestamps created_at updated_at
    }

    DONOR_PAYMENT_METHODS {
        bigint id PK
        bigint donor_id FK
        string stripe_payment_method_id UK
        string brand
        string last4
        tinyint exp_month
        smallint exp_year
        string country "nullable - ISO alpha-2"
        boolean is_default
        timestamps created_at updated_at
    }

    TRACKING_CONFIGURATIONS {
        bigint id PK
        bigint organization_id FK
        string provider
        boolean is_enabled
        text credentials "nullable"
        json options "nullable"
        string status
        text error_message "nullable"
        timestamp last_tested_at "nullable"
        timestamp last_event_at "nullable"
        timestamps created_at updated_at
    }

    TRACKING_EVENTS {
        bigint id PK
        bigint organization_id FK
        bigint donation_id FK "nullable"
        string provider
        string event_name
        string status
        decimal amount "nullable"
        char currency "nullable"
        string campaign_name "nullable"
        json payload "nullable"
        json response "nullable"
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
        bigint organization_id FK
        string rule_type "velocity|amount|pattern|country|card|risk_score"
        json config "threshold, window, countries, patterns, dll"
        string action "block|flag|3ds"
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
        enum review_status "pending|approved|rejected"
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
    ORGANIZATIONS ||--o{ PAYOUTS : "receives"
    CAMPAIGNS ||--o{ DONATIONS : "receives"
    CAMPAIGNS ||--o{ SUBSCRIPTIONS : "has many"
    CAMPAIGNS ||--o{ ELEMENTS : "has many (optional)"
    DONORS ||--o{ DONATIONS : "makes"
    DONORS ||--o{ SUBSCRIPTIONS : "holds"
    DONORS ||--o{ DONOR_PAYMENT_METHODS : "has saved payment methods"
    DONORS ||--o{ DONOR_EMAIL_LOGS : "receives emails"
    SUBSCRIPTIONS ||--o{ DONATIONS : "generates recurring"
    DONATIONS ||--|| PROCESSING_FEES : "generates one"
    DONATIONS ||--o{ DONOR_EMAIL_LOGS : "may trigger email"
    SUBSCRIPTIONS ||--o{ DONOR_EMAIL_LOGS : "may trigger email"
    ORGANIZATIONS ||--o{ DONOR_EMAIL_LOGS : "sends emails"
    DONOR_EMAIL_LOGS ||--o{ DONOR_EMAIL_LOGS : "resent from"
    MONTHLY_INVOICES ||--o{ PROCESSING_FEES : "collects"
    ORGANIZATIONS ||--o{ TRACKING_CONFIGURATIONS : "configures"
    ORGANIZATIONS ||--o{ TRACKING_EVENTS : "generates"
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
| `public_id` | string unique | ID public-facing 8 aksara: `O` + 7 aksara rawak (A–Z, 1–9). Digunakan di UI dan URL untuk menyembunyikan auto-increment ID |
| `code` | string unique | Kod organisasi untuk route donor portal, contoh: `/donorportal/MSJ001` |
| `ros_rob_number` | string unique nullable | Nombor pendaftaran ROS/ROB — wajib untuk KYC |
| `stripe_account_id` | string | ID Stripe Connect Express account NGO |
| `stripe_onboarded` | boolean | TRUE bila NGO dah selesai Stripe onboarding flow |
| `stripe_onboarded_at` | timestamp nullable | Masa onboarding Stripe Connect selesai |
| `stripe_enabled` | boolean, default TRUE | Toggle NGO admin untuk aktif/nyahaktifkan Stripe sebagai processor |
| `chip_brand_id` | string nullable | Brand ID CHIP untuk organisasi |
| `chip_api_key` | text nullable, encrypted | API key CHIP (disulitkan at-rest) |
| `chip_webhook_id` / `chip_webhook_public_key` | string/text nullable | ID webhook CHIP dan public key untuk verifikasi signature (`chip_webhook_public_key` disulitkan) |
| `chip_enabled` | boolean, default TRUE | Toggle NGO admin untuk aktif/nyahaktifkan CHIP sebagai processor |
| `status` | enum | `pending` selepas daftar; `active` selepas approved oleh super_admin |
| `settings` | json | Konfigurasi ringan organisasi: notification preferences, default currency, dll |
| `processing_fee_override` | decimal nullable | Kadar processing fee khusus NGO, jika berbeza daripada default platform |
| `fee_collection_method` | string, default `upfront` | Cara kutipan fee: `upfront` (default sejak Jul 2026) atau `invoice` (monthly invoice) |
| `admin_notes` | text nullable | Nota dalaman platform owner |
| `tax_exempt` | boolean | Flag untuk organisasi yang layak receipt tax-exempt |
| `facebook_url` | string nullable | URL Facebook rasmi organisasi |
| `address_line_1/2`, `city`, `state`, `postcode`, `country`, `sector` | string nullable | Alamat dan sektor organisasi |

> **Nota:** `stripe_active` dan `chip_active` **bukan** kolum DB — ia computed attribute pada model (`stripe_onboarded && stripe_enabled`, dan `chip_onboarded && chip_enabled` di mana `chip_onboarded` = ada `chip_brand_id` dan `chip_api_key`). Digunakan untuk tentukan processor mana yang live semasa checkout.

---

### 2.3 `campaigns`
Kempen fundraising yang dibuat oleh NGO. Satu NGO boleh ada berbilang kempen aktif.

| Kolum | Jenis | Keterangan |
|-------|-------|------------|
| `public_id` | string unique | ID public-facing 8 aksara: `IH` + 6 aksara rawak (A–Z, 1–9). Digunakan di URL kempen dan share link |
| `slug` | string unique | Slug SEO-friendly untuk halaman kempen awam |
| `has_target` | boolean | FALSE = general fund tanpa target |
| `collected_amount` | decimal | Dikemas kini setiap kali `donations.status = succeeded` |
| `suggested_amounts` | json | Legacy/default suggested amount set |
| `suggested_amounts_one_time` | json nullable | Amount cadangan untuk one-time donations |
| `suggested_amounts_monthly` | json nullable | Amount cadangan untuk recurring monthly donations |
| `allow_recurring` | boolean | FALSE = one-time sahaja |
| `allow_custom_amount` | boolean | FALSE = donor hanya boleh pilih amount cadangan |
| `form_parameter` | string unique nullable | Slug/token URL untuk campaign checkout modal |
| `checkout_modal_enabled` | boolean | TRUE bila campaign boleh dibuka dalam modal embed |
| `campaign_page_enabled` | boolean | TRUE bila campaign mempunyai halaman donation khas |
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
| `name` | string | Nama penuh donor (auto-generated dari first_name + last_name) |
| `first_name` | string nullable | Nama pertama donor |
| `last_name` | string nullable | Nama akhir donor |
| `occupation` | string nullable | Pekerjaan: `Employed`, `Self-employed`, `Business owner`, `Student`, `Retired`, `Unemployed`, `Other` |
| `email` | string unique globally | Satu donor = satu email, walaupun derma kepada berbilang NGO |
| `phone` | string nullable | Nombor telefon |
| `stripe_customer_id` | string unique | Satu Stripe Customer ID untuk semua transaksi donor ini |
| `magic_token` | string | Token sementara untuk akses donor portal (tanpa password) |
| `magic_token_expires_at` | timestamp | Token expired selepas 24 jam |
| `email_opt_out_at` | timestamp nullable | Masa donor memilih opt-out daripada email |
| `email_bounced_at` | timestamp nullable | Masa email donor ditandakan bounced |
| `email_validated_at` | timestamp nullable | Masa email donor disahkan delivered oleh SES/SNS webhook; memaparkan badge "Validated" di UI |
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
| `receipt_token` | string unique nullable | Token unik untuk muat turun receipt melalui URL signed/private tanpa login |
| `subscription_id` | FK nullable | NULL = one-time; ada nilai = dijana oleh subscription |
| `source` | string nullable | Sumber transaksi: `element`, `campaign_page`, `checkout_modal`, `virtual_terminal` |
| `stripe_invoice_id` | string nullable | ID invois Stripe untuk subscription payment |
| `chip_purchase_id` / `chip_recurring_token` / `chip_checkout_url` | string/text nullable | ID purchase CHIP, token recurring CHIP, dan URL checkout hosted CHIP |
| `type` | enum | `one_time` atau `recurring` |
| `gross_amount` | decimal | Jumlah yang donor bayar |
| `stripe_fee` | decimal | Fee Stripe yang sebenar dari BalanceTransaction |
| `chip_fee` | decimal, default 0 | Fee CHIP yang sebenar apabila transaksi diproses melalui CHIP |
| `processing_fee` | decimal | Fee Ihsan, default 2.5% daripada gross amount atau override organisasi |
| `net_amount` | decimal | Yang masuk ke NGO (`gross - stripe_fee/chip_fee - processing_fee`) |
| `payment_method_brand` | string | Jenama kad: `visa`, `mastercard`, atau type method untuk non-card |
| `payment_method_type` | string | Method type dari Stripe/CHIP: `card`, `fpx`, `grabpay`, `wallet` |
| `payment_method_exp_month` / `payment_method_exp_year` | integer nullable | Tarikh luput kad yang digunakan untuk derma; dipaparkan pada butiran resit |
| `donor_country` | string nullable | Negara kad donor daripada PaymentMethod card country; digunakan untuk filter/analytics |
| `geo_city` / `geo_region` | string nullable | Bandar/negeri donor daripada geolocation IP (MaxMind GeoLite2, fallback ip-api) |
| `donor_fee_covered` | decimal nullable | Tambahan amount yang donor cover untuk estimated processing fee |
| `base_currency` / `base_amount` / `exchange_rate` | mixed nullable | Snapshot conversion jika donation bukan dalam currency asas organisasi |
| `receipt_sent_at` | timestamp nullable | Masa receipt email dihantar |
| `new_donation_notification_sent_at` | timestamp nullable | Masa notifikasi derma baru dihantar kepada NGO admin |
| `large_donation_notification_sent_at` | timestamp nullable | Masa notifikasi derma besar dihantar kepada NGO admin |
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
| `source` | string nullable | Sumber subscription: `element`, `campaign_page`, `checkout_modal`, `virtual_terminal` |
| `stripe_subscription_id` | string unique | ID dari Stripe untuk sync status |
| `chip_recurring_token` | string nullable | Token recurring CHIP untuk auto-charge berulang apabila subscription diproses melalui CHIP |
| `status` | enum | Sync dengan Stripe/CHIP subscription status |
| `last_failure_message` | text nullable | Sebab kegagalan bayaran terakhir dari Stripe/CHIP, dipaparkan sebagai tooltip retry pada UI |
| `retry_count` | tinyint | Bilangan kali bayaran gagal dicuba (max 3, dunning logic) |
| `payment_count` | tinyint | Bilangan kali bayaran berjaya — dikira dari `invoice.paid` webhook |
| `paused_until` | timestamp | Set bila donor pause — resume otomatik selepas tarikh ini |
| `current_period_start/end` | timestamp | Kitaran billing semasa dari Stripe |
| `cancel_at_period_end` | boolean | TRUE bila subscription dijadualkan batal hujung period |
| `cancel_at` | timestamp nullable | Masa pembatalan berjadual dari Stripe |
| `cover_fee` | boolean | TRUE bila recurring donation cover estimated processing fee |
| `fee_cover_amount` | decimal nullable | Jumlah tambahan yang donor bayar untuk cover fee |
| `max_plan_amount` | decimal nullable | Jumlah maksimum untuk subscription dengan plan limit |
| `max_plan_installments` | integer nullable | Bilangan maksimum bayaran untuk plan limit |
| `cancellation_reason` | string nullable | Sebab pembatalan subscription |

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
| `type` | enum | `button`, `floating_button`, `sticky_button`, `form`, `popup`, `qr_code`, `link` |
| `config` | json | Warna, copy, action, trigger, layout, image, behavior, dll |
| `campaign_id` | FK nullable | NULL = donor pilih campaign sendiri; ada nilai = locked ke campaign tertentu |
| `form_slug` | string unique nullable | Slug tambahan untuk hosted form/embed legacy |
| `is_donor_portal_default` | boolean | TRUE jika element digunakan sebagai default donor portal entry |
| `archived_at` | timestamp nullable | Masa element diarkibkan; jika tidak NULL element tidak aktif |

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
Peraturan deteksi penipuan (fraud detection) yang ditakrif oleh super admin untuk setiap organisasi. Mengandungi logik threshold, velocity check, pattern matching, dan risk score.

| Kolum | Jenis | Keterangan |
|-------|-------|------------|
| `rule_type` | string | Jenis peraturan: `velocity`, `amount`, `pattern`, `country`, `card`, `risk_score` |
| `config` | json | Konfigurasi spesifik peraturan: threshold amount, time window, senarai negara/card yang diblok, dll |
| `action` | string | `block` = halang terus; `flag` = tandakan untuk semakan; `3ds` = paksakan 3D Secure |
| `is_active` | boolean | TRUE = peraturan aktif dan digunakan semasa penilaian transaksi |
| `organization_id` | FK | Setiap peraturan dimiliki oleh satu organisasi |

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
| `review_status` | enum | `pending` = belum disemak; `approved` = disemak dan kekal blocked; `rejected` = dilepaskan/dibenarkan oleh super admin |
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

### 2.15 `donor_email_logs`
Log setiap email yang dihantar kepada donor (donation receipt, notification, summary, dll). Menyokong resend, tracking delivery, dan responsive preview.

| Kolum | Jenis | Keterangan |
|-------|-------|------------|
| `public_id` | string unique | ID public-facing 8 aksara: `EL` + 6 aksara rawak (A–Z, 1–9). Digunakan di URL responsive preview untuk sembunyikan auto-increment ID |
| `donor_id` | FK | Donor penerima email |
| `organization_id` | FK nullable | NGO yang menghantar email; NULL untuk Email platform/global |
| `donation_id` | FK nullable | Link kepada donation jika email dijana oleh transaksi |
| `subscription_id` | FK nullable | Link kepada subscription jika email berkaitan recurring |
| `resent_from_id` | FK nullable | Self-referential — rekod asal yang di-resend |
| `mailable_class` | string | Class Mailable yang digunakan untuk rebuild preview/resend |
| `message_id` | string unique nullable | Message ID daripada email provider (SES/Postmark) |
| `provider_message_id` | string nullable | Provider-specific message ID |
| `subject` | string | Subjek email |
| `delivery_status` | string nullable | Status delivery: `sent`, `delivered`, `bounced`, `complained`, dll |
| `metadata` | json nullable | Data tambahan mengenai email, contohnya `resent_to_email` |
| `sent_at` | timestamp nullable | Masa email dihantar |
| `opened_at` | timestamp nullable | Masa email dibuka (tracking pixel) |
| `delivered_at` | timestamp nullable | Masa email disahkan delivered oleh provider |
| `bounced_at` | timestamp nullable | Masa email bounced |
| `bounce_reason` | string nullable | Sebab bounce |
| `complained_at` | timestamp nullable | Masa email complaint/spam dilaporkan |

---

### 2.16 `donor_payment_methods`
Rekod payment method yang disimpan oleh donor untuk update payment method atau prefill dalam pembayaran seterusnya. Data sensitif (nombor penuh kad, CVC) tidak disimpan — hanya metadata stripe yang tidak sensitif.

| Kolum | Jenis | Keterangan |
|-------|-------|------------|
| `donor_id` | FK | Donor yang memiliki payment method |
| `stripe_payment_method_id` | string unique | ID payment method di Stripe |
| `brand` | string | Jenama kad, contoh `visa`, `mastercard` |
| `last4` | string | 4 digit terakhir kad |
| `exp_month` / `exp_year` | integer | Tarikh luput kad |
| `country` | string nullable | Kod negara ISO alpha-2 payment method |
| `is_default` | boolean | TRUE = payment method utama untuk donor |

---

### 2.17 `tracking_configurations`
Konfigurasi integrasi analytics/pixel (contohnya Meta Pixel, Google Tag) per organisasi. Setiap organisasi boleh mempunyai satu konfigurasi untuk setiap provider.

| Kolum | Jenis | Keterangan |
|-------|-------|------------|
| `organization_id` | FK | Organisasi yang memiliki konfigurasi |
| `provider` | string | Nama provider tracking, contoh `meta_pixel`, `google_tag` |
| `is_enabled` | boolean | TRUE = tracking aktif |
| `credentials` | text nullable | Token/API key (disimpan dengan selamat jika mungkin) |
| `options` | json nullable | Tetapan tambahan provider |
| `status` | string | `not_configured`, `active`, `error`, dll |
| `error_message` | text nullable | Mesej ralat terakhir |
| `last_tested_at` | timestamp nullable | Masa konfigurasi diuji |
| `last_event_at` | timestamp nullable | Masa event terakhir dihantar |

---

### 2.18 `tracking_events`
Log event tracking yang dihantar ke provider analytics/pixel — contohnya `PageView`, `InitiateCheckout`, `DonationCompleted`. Membolehkan audit dan troubleshooting integrasi.

| Kolum | Jenis | Keterangan |
|-------|-------|------------|
| `organization_id` | FK | Organisasi yang menghantar event |
| `donation_id` | FK nullable | Link kepada donation jika event berkaitan transaksi |
| `provider` | string | Provider penerima event |
| `event_name` | string | Nama event |
| `status` | string | `pending`, `sent`, `failed`, dll |
| `amount` / `currency` | decimal/char nullable | Nilai dan mata wang jika berkaitan donation |
| `campaign_name` | string nullable | Nama kempen yang berkaitan |
| `payload` | json nullable | Data yang dihantar kepada provider |
| `response` | json nullable | Response daripada provider |

---

### 2.19 `payouts`
Rekod payout Stripe Connect ke akaun bank NGO. Disegerak daripada Stripe melalui command terjadual `app:sync-payouts` dan webhook payout (`payout.paid`, `payout.failed`). Halaman ini **read-only** untuk NGO admin — tiada tindakan tulis dari app panel.

| Kolum | Jenis | Keterangan |
|-------|-------|------------|
| `stripe_payout_id` | string | ID payout daripada Stripe; unik bersama `organization_id` |
| `amount` | integer | Jumlah payout dalam unit terkecil (sen) |
| `currency` | string | Mata wang payout |
| `status` | string | Status payout Stripe: `paid`, `pending`, `in_transit`, `failed`, `canceled`, dll |
| `arrival_date` | date | Tarikh jangkaan/sebenar payout masuk bank |
| `paid_at` | date nullable | Tarikh payout disahkan dibayar |
| `bank_name` | string nullable | Nama bank destinasi |
| `bank_account_last4` | string nullable | 4 digit terakhir akaun bank destinasi |
| `failure_code` / `failure_message` | string nullable | Sebab kegagalan jika payout gagal |
| `metadata` | json nullable | Data tambahan daripada Stripe |

> **Nota:** `payouts` **tidak** menggunakan skema `public_id` — ia jadual internal yang di-scope oleh `organization_id`, tiada keperluan URL public-facing.

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

`public_id` ialah pengenal unik berorientasi public untuk 9 entiti utama. Ia menyembunyikan auto-increment `id` daripada pengguna akhir dan URL, meningkatkan keselamatan dan estetik.

### Format
- **8 aksara total**, huruf besar A–Z + digit **1–9** (tiada 0).
- **Prefix tetap** per table + aksara rawak.

| Table | Prefix | Contoh |
|-------|--------|--------|
| `users` | `U` | `UAB3C9D2` |
| `organizations` | `O` | `O2A4B6C8` |
| `campaigns` | `IH` | `IH7A3B9C` |
| `donors` | `DR` | `DR2E8F1G` |
| `donations` | `D` | `D4H5I6J7` |
| `subscriptions` | `R` | `R8K9L1M2` |
| `elements` | `E` | `E3N4O5P6` |
| `donor_email_logs` | `EL` | `EL7A3B9C` |
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
| `payout.paid` / `payout.failed` | Upsert rekod dalam `payouts` (unik `organization_id` + `stripe_payout_id`) dengan status, tarikh, dan sebab kegagalan terkini |

CHIP menghantar webhook berasingan ke `chip/webhook/{organization?}`, disahkan melalui `chip_webhook_public_key` per organisasi, untuk sync status purchase (`chip_purchase_id`) dan recurring token.

---

## 7. Indeks Database

### 6.1 Indeks `public_id`
Setiap table dengan `public_id` mempunyai unique index:

```sql
CREATE UNIQUE INDEX idx_users_public_id ON users(public_id);
CREATE UNIQUE INDEX idx_organizations_public_id ON organizations(public_id);
CREATE UNIQUE INDEX idx_campaigns_public_id ON campaigns(public_id);
CREATE UNIQUE INDEX idx_donors_public_id ON donors(public_id);
CREATE UNIQUE INDEX idx_donations_public_id ON donations(public_id);
CREATE UNIQUE INDEX idx_subscriptions_public_id ON subscriptions(public_id);
CREATE UNIQUE INDEX idx_elements_public_id ON elements(public_id);
CREATE UNIQUE INDEX idx_donor_email_logs_public_id ON donor_email_logs(public_id);
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
CREATE UNIQUE INDEX idx_donation_receipt_token ON donations(receipt_token);

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

-- payouts
CREATE UNIQUE INDEX idx_payout_org_stripe_payout ON payouts(organization_id, stripe_payout_id);

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

-- donor_payment_methods
CREATE INDEX idx_donor_payment_method_donor ON donor_payment_methods(donor_id);
CREATE INDEX idx_donor_payment_method_default ON donor_payment_methods(donor_id, is_default);

-- tracking_configurations
CREATE INDEX idx_tracking_config_org_provider ON tracking_configurations(organization_id, provider);
CREATE INDEX idx_tracking_config_org_status ON tracking_configurations(organization_id, status);

-- tracking_events
CREATE INDEX idx_tracking_event_org_provider_created ON tracking_events(organization_id, provider, created_at);
CREATE INDEX idx_tracking_event_org_status ON tracking_events(organization_id, status);
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
