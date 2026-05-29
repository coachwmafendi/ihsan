# Entity Relationship Diagram (ERD)
## Ihsan — MVP Database Design

**Version:** 1.5  
**Tarikh:** 29 Mei 2026  
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
- `platform_fees` dan `webhook_logs` untuk operasi pembayaran, rekonsiliasi, dan audit

Donor Portal penuh tidak menjadi fokus awal. MVP hanya perlukan Donor Portal Lite untuk magic link, sejarah derma, dan pembatalan subscription. Fungsi self-service lanjutan seperti pause sendiri, tukar amount, dan update payment method boleh dinaikkan ke V2 tanpa menukar struktur data utama.

Insights MVP tidak memerlukan table analytics khas. Halaman Insights boleh dikira terus daripada `donations`, `subscriptions`, `campaigns`, `elements`, dan `utm_params` pada `donations`. Jika volume data meningkat, aggregate/materialized summary table boleh ditambah kemudian tanpa mengubah model transaksi utama.

---

## 1. Diagram ERD (Mermaid)

```mermaid
erDiagram

    USERS {
        bigint id PK
        bigint organization_id FK "nullable - null for super_admin"
        string name
        string email UK
        string password
        enum role "super_admin|ngo_admin"
        timestamp email_verified_at
        timestamps created_at updated_at
    }

    ORGANIZATIONS {
        bigint id PK
        string name
        string slug UK
        string ros_rob_number UK "nullable"
        enum registration_type "ros|rob|others"
        text description
        string logo_path
        string website_url
        string contact_email
        string contact_phone
        enum status "pending|active|suspended|rejected"
        string stripe_account_id UK "nullable"
        boolean stripe_onboarded
        string bank_account_name
        string bank_account_number
        string bank_name
        json settings
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
        bigint organization_id FK
        string title
        string slug UK
        text description
        string image_path
        decimal target_amount "nullable"
        decimal collected_amount "default 0"
        boolean has_target
        boolean allow_recurring
        date end_date "nullable"
        enum status "draft|active|paused|ended"
        json suggested_amounts
        timestamps created_at updated_at
    }

    DONORS {
        bigint id PK
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
        bigint campaign_id FK
        bigint donor_id FK
        bigint subscription_id FK "nullable - null if one-time"
        string stripe_payment_intent_id UK "nullable"
        string stripe_charge_id "nullable"
        decimal gross_amount
        decimal stripe_fee
        decimal platform_fee
        decimal net_amount
        string currency "default myr"
        enum status "pending|succeeded|failed|refunded"
        enum type "one_time|recurring"
        text donor_message "nullable"
        boolean is_anonymous
        json utm_params "nullable"
        string payment_method_brand "nullable - visa|mastercard|fpx|etc"
        string payment_method_type "nullable - card|fpx|grabpay|wallet|etc"
        timestamps created_at updated_at
    }

    SUBSCRIPTIONS {
        bigint id PK
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
        timestamps created_at updated_at
    }

    PLATFORM_FEES {
        bigint id PK
        bigint donation_id FK
        bigint organization_id FK
        decimal fee_amount
        decimal fee_percentage "snapshot kadar semasa transaksi"
        string stripe_transfer_id "nullable"
        enum status "pending|transferred|failed"
        timestamps created_at updated_at
    }

    ELEMENTS {
        bigint id PK
        bigint organization_id FK
        bigint campaign_id FK "nullable"
        string name
        string token UK "untuk embed widget"
        enum type "button|form|popup"
        json config "warna, copy, amounts, behavior, dll"
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

    %% Relationships
    USERS }o--|| ORGANIZATIONS : "belongs to (ngo_admin)"
    ORGANIZATIONS ||--o{ ORGANIZATION_DOCUMENTS : "has many"
    ORGANIZATIONS ||--o{ CAMPAIGNS : "has many"
    ORGANIZATIONS ||--o{ ELEMENTS : "has many"
    ORGANIZATIONS ||--o{ PLATFORM_FEES : "has many"
    CAMPAIGNS ||--o{ DONATIONS : "receives"
    CAMPAIGNS ||--o{ SUBSCRIPTIONS : "has many"
    CAMPAIGNS ||--o{ ELEMENTS : "has many (optional)"
    DONORS ||--o{ DONATIONS : "makes"
    DONORS ||--o{ SUBSCRIPTIONS : "holds"
    SUBSCRIPTIONS ||--o{ DONATIONS : "generates recurring"
    DONATIONS ||--|| PLATFORM_FEES : "generates one"
```

---

## 2. Penerangan Setiap Entiti

### 2.1 `users`
Pengguna platform yang ada akses kepada admin panel — bukan donor. Donor diurus dalam jadual `donors` berasingan kerana mereka tidak perlu akaun penuh.

| Kolum | Jenis | Keterangan |
|-------|-------|------------|
| `role` | enum | `super_admin` = pemilik platform Ihsan; `ngo_admin` = pentadbir NGO |
| `organization_id` | FK nullable | NULL untuk super_admin |

---

### 2.2 `organizations`
Entiti utama yang mewakili NGO, masjid, atau badan amal yang berdaftar di Ihsan.

| Kolum | Jenis | Keterangan |
|-------|-------|------------|
| `slug` | string unique | Digunakan dalam URL, contoh: `/org/masjid-al-islah` |
| `ros_rob_number` | string unique nullable | Nombor pendaftaran ROS/ROB — wajib untuk KYC |
| `stripe_account_id` | string | ID Stripe Connect Express account NGO |
| `stripe_onboarded` | boolean | TRUE bila NGO dah selesai Stripe onboarding flow |
| `status` | enum | `pending` selepas daftar; `active` selepas approved oleh super_admin |
| `settings` | json | Konfigurasi widget: warna, default amounts, dll |

---

### 2.3 `campaigns`
Kempen fundraising yang dibuat oleh NGO. Satu NGO boleh ada berbilang kempen aktif.

| Kolum | Jenis | Keterangan |
|-------|-------|------------|
| `has_target` | boolean | FALSE = general fund tanpa target |
| `collected_amount` | decimal | Dikemas kini setiap kali `donations.status = succeeded` |
| `suggested_amounts` | json | Contoh: `[10, 25, 50, 100]` dalam MYR |
| `allow_recurring` | boolean | FALSE = one-time sahaja |
| `status` | enum | `draft` = belum published; `active` = live |

---

### 2.4 `donors`
**Global donor** — satu rekod per email merentasi semua NGO. Privacy dijaga melalui query scope melalui campaigns, bukan data separation.

| Kolum | Jenis | Keterangan |
|-------|-------|------------|
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
| `subscription_id` | FK nullable | NULL = one-time; ada nilai = dijana oleh subscription |
| `type` | enum | `one_time` atau `recurring` |
| `gross_amount` | decimal | Jumlah yang donor bayar |
| `stripe_fee` | decimal | Fee Stripe yang sebenar dari BalanceTransaction |
| `platform_fee` | decimal | Fee Ihsan (5%) — dikira sendiri, bukan dari Stripe |
| `net_amount` | decimal | Yang masuk ke NGO (`gross - stripe_fee - platform_fee`) |
| `payment_method_brand` | string | Jenama kad: `visa`, `mastercard`, atau type method untuk non-card |
| `payment_method_type` | string | Method type dari Stripe: `card`, `fpx`, `grabpay`, `wallet` |
| `utm_params` | json | Track sumber traffic: `{source, medium, campaign}` |
| `is_anonymous` | boolean | TRUE = nama donor tidak dipaparkan di halaman kempen |

---

### 2.6 `subscriptions`
Rekod recurring subscription. Satu subscription = satu donor → satu campaign dengan interval tertentu.

| Kolum | Jenis | Keterangan |
|-------|-------|------------|
| `stripe_subscription_id` | string unique | ID dari Stripe untuk sync status |
| `status` | enum | Sync dengan Stripe Subscription status |
| `retry_count` | tinyint | Bilangan kali bayaran gagal dicuba (max 3, dunning logic) |
| `payment_count` | tinyint | Bilangan kali bayaran berjaya — dikira dari `invoice.paid` webhook |
| `paused_until` | timestamp | Set bila donor pause — resume otomatik selepas tarikh ini |
| `current_period_start/end` | timestamp | Kitaran billing semasa dari Stripe |

---

### 2.7 `platform_fees`
Rekod asing untuk setiap fee yang dikutip Ihsan. Memudahkan rekonsiliasi kewangan dan pelaporan revenue.

| Kolum | Jenis | Keterangan |
|-------|-------|------------|
| `stripe_transfer_id` | string | ID Stripe Transfer bila fee dipindahkan ke akaun platform Ihsan |
| `fee_percentage` | decimal | Snapshot kadar fee semasa transaksi (in case kadar berubah masa depan) |

---

### 2.8 `elements`
Donation element instances yang dibuat oleh NGO untuk embed di website mereka. Satu NGO boleh ada berbilang elements dengan config berbeza, sama ada inline form, button, atau popup. Konsep ini mengambil inspirasi daripada fundraising platforms seperti Fundraise Up, tetapi config sebenar untuk MVP kekal ringkas.

| Kolum | Jenis | Keterangan |
|-------|-------|------------|
| `token` | string unique | Public token untuk widget script: `data-element="TOKEN"` |
| `type` | enum | `button` = floating button; `form` = inline form; `popup` = modal |
| `config` | json | Warna, suggested amounts, teks butang, behavior popup, dll |
| `campaign_id` | FK nullable | NULL = donor pilih campaign sendiri; ada nilai = locked ke campaign tertentu |

Untuk MVP pertama, `elements.config` boleh menyimpan struktur minimum seperti:

```json
{
  "theme": "light",
  "primary_color": "#0f766e",
  "suggested_amounts": [30, 50, 100],
  "default_frequency": "monthly",
  "button_label": "Donate"
}
```

---

### 2.9 `webhook_logs`
Log semua Stripe webhook events yang diterima. Kritikal untuk debugging dan memastikan tiada event yang terlepas atau diproses dua kali.

| Kolum | Jenis | Keterangan |
|-------|-------|------------|
| `stripe_event_id` | string unique | Unique ID dari Stripe — mencegah duplicate processing (idempotency) |
| `event_type` | string | Contoh: `invoice.paid`, `customer.subscription.deleted` |
| `status` | enum | `processed` = berjaya; `failed` = ada error; `ignored` = event tak relevan |

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

### 3.3 Donation → Platform Fee (One-to-One)

```text
donations ──── platform_fees
```

Setiap donation berjaya (`status = succeeded`) menjana tepat satu rekod `platform_fees`.

---

## 4. Stripe Connect Money Flow

```text
Donor bayar RM 100
        │
        ▼
Stripe memproses bayaran
        │
        ├─► Stripe fee: RM 3.50 (2.2% + RM 1.30)
        │
        ├─► Ihsan platform fee: RM 5.00 (5%)
        │   └─► Stripe Connect application_fee
        │
        └─► NGO terima: RM 91.50
            └─► Payout ke bank NGO setiap 7 hari (Stripe Connect)
```

---

## 5. Stripe Webhook Events

| Event | Tindakan |
|-------|----------|
| `checkout.session.completed` | Cipta `donations` (one-time), hantar resit email |
| `invoice.paid` | Cipta `donations` dari subscription, hantar resit, update `current_period_*` |
| `invoice.payment_failed` | Tambah `retry_count`, email donor, update `status = past_due` |
| `customer.subscription.deleted` | Update `status = cancelled`, set `cancelled_at`, email pengesahan |
| `customer.subscription.updated` | Sync `amount`, `status`, `current_period_*` |
| `account.updated` | Update `stripe_onboarded = true` bila NGO selesai onboarding |

---

## 6. Indeks Database

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
CREATE INDEX idx_element_token ON elements(token);

-- webhook_logs
CREATE INDEX idx_webhook_event_id ON webhook_logs(stripe_event_id);
CREATE INDEX idx_webhook_type ON webhook_logs(event_type);
CREATE INDEX idx_webhook_status ON webhook_logs(status);
```

---

## 7. Query Penting

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
```sql
SELECT s.*
FROM subscriptions s
WHERE s.status = 'past_due'
  AND s.retry_count < 3
  AND s.updated_at <= NOW() - INTERVAL (
      CASE s.retry_count WHEN 0 THEN 3 WHEN 1 THEN 7 WHEN 2 THEN 14 END
  ) DAY;
```
