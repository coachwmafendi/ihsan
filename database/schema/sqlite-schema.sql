CREATE TABLE IF NOT EXISTS "migrations"(
  "id" integer primary key autoincrement not null,
  "migration" varchar not null,
  "batch" integer not null
);
CREATE TABLE IF NOT EXISTS "users"(
  "id" integer primary key autoincrement not null,
  "organization_id" integer,
  "name" varchar not null,
  "email" varchar not null,
  "role" varchar not null default 'ngo_admin',
  "email_verified_at" datetime,
  "password" varchar not null,
  "remember_token" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  "two_factor_secret" text,
  "two_factor_recovery_codes" text,
  "two_factor_confirmed_at" datetime,
  "avatar_url" varchar,
  "last_login_at" datetime
);
CREATE INDEX "users_organization_id_index" on "users"("organization_id");
CREATE UNIQUE INDEX "users_email_unique" on "users"("email");
CREATE INDEX "users_role_index" on "users"("role");
CREATE TABLE IF NOT EXISTS "password_reset_tokens"(
  "email" varchar not null,
  "token" varchar not null,
  "created_at" datetime,
  primary key("email")
);
CREATE TABLE IF NOT EXISTS "sessions"(
  "id" varchar not null,
  "user_id" integer,
  "ip_address" varchar,
  "user_agent" text,
  "payload" text not null,
  "last_activity" integer not null,
  primary key("id")
);
CREATE INDEX "sessions_user_id_index" on "sessions"("user_id");
CREATE INDEX "sessions_last_activity_index" on "sessions"("last_activity");
CREATE TABLE IF NOT EXISTS "cache"(
  "key" varchar not null,
  "value" text not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE INDEX "cache_expiration_index" on "cache"("expiration");
CREATE TABLE IF NOT EXISTS "cache_locks"(
  "key" varchar not null,
  "owner" varchar not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE INDEX "cache_locks_expiration_index" on "cache_locks"("expiration");
CREATE TABLE IF NOT EXISTS "jobs"(
  "id" integer primary key autoincrement not null,
  "queue" varchar not null,
  "payload" text not null,
  "attempts" integer not null,
  "reserved_at" integer,
  "available_at" integer not null,
  "created_at" integer not null
);
CREATE INDEX "jobs_queue_index" on "jobs"("queue");
CREATE TABLE IF NOT EXISTS "job_batches"(
  "id" varchar not null,
  "name" varchar not null,
  "total_jobs" integer not null,
  "pending_jobs" integer not null,
  "failed_jobs" integer not null,
  "failed_job_ids" text not null,
  "options" text,
  "cancelled_at" integer,
  "created_at" integer not null,
  "finished_at" integer,
  primary key("id")
);
CREATE TABLE IF NOT EXISTS "failed_jobs"(
  "id" integer primary key autoincrement not null,
  "uuid" varchar not null,
  "connection" text not null,
  "queue" text not null,
  "payload" text not null,
  "exception" text not null,
  "failed_at" datetime not null default CURRENT_TIMESTAMP
);
CREATE UNIQUE INDEX "failed_jobs_uuid_unique" on "failed_jobs"("uuid");
CREATE TABLE IF NOT EXISTS "organization_documents"(
  "id" integer primary key autoincrement not null,
  "organization_id" integer not null,
  "document_type" varchar not null,
  "file_path" varchar not null,
  "original_filename" varchar not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("organization_id") references "organizations"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "campaigns"(
  "id" integer primary key autoincrement not null,
  "organization_id" integer not null,
  "title" varchar not null,
  "description" text,
  "image_path" varchar,
  "target_amount" numeric,
  "collected_amount" numeric not null default '0',
  "has_target" tinyint(1) not null default '0',
  "allow_recurring" tinyint(1) not null default '1',
  "end_date" date,
  "status" varchar not null default 'draft',
  "suggested_amounts" text,
  "created_at" datetime,
  "updated_at" datetime,
  "headline" varchar,
  "short_summary" varchar,
  "minimum_amount" numeric,
  "allow_custom_amount" tinyint(1) not null default '1',
  "payment_gateway" varchar,
  "thank_you_message" text,
  "redirect_url" varchar,
  "suggested_amounts_one_time" text,
  "suggested_amounts_monthly" text,
  "impact_descriptions_enabled" tinyint(1) not null default '0',
  "default_monthly_amount" numeric,
  "form_parameter" varchar,
  "checkout_modal_enabled" tinyint(1) not null default '0',
  "checkout_allowed_domains" text,
  foreign key("organization_id") references "organizations"("id") on delete cascade
);
CREATE INDEX "campaigns_organization_id_index" on "campaigns"(
  "organization_id"
);
CREATE INDEX "campaigns_status_index" on "campaigns"("status");
CREATE TABLE IF NOT EXISTS "donors"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "email" varchar not null,
  "phone" varchar,
  "stripe_customer_id" varchar,
  "magic_token" varchar,
  "magic_token_expires_at" datetime,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "donors_email_unique" on "donors"("email");
CREATE UNIQUE INDEX "donors_stripe_customer_id_unique" on "donors"(
  "stripe_customer_id"
);
CREATE INDEX "donors_magic_token_index" on "donors"("magic_token");
CREATE TABLE IF NOT EXISTS "donations"(
  "id" integer primary key autoincrement not null,
  "campaign_id" integer not null,
  "donor_id" integer not null,
  "subscription_id" integer,
  "stripe_payment_intent_id" varchar,
  "stripe_charge_id" varchar,
  "gross_amount" numeric not null,
  "stripe_fee" numeric not null default '0',
  "processing_fee" numeric not null default '0',
  "net_amount" numeric not null default '0',
  "currency" varchar not null default 'myr',
  "status" varchar not null default 'pending',
  "type" varchar not null default 'one_time',
  "donor_message" text,
  "is_anonymous" tinyint(1) not null default '0',
  "utm_params" text,
  "created_at" datetime,
  "updated_at" datetime,
  "payment_method_brand" varchar,
  "payment_method_type" varchar,
  foreign key("campaign_id") references "campaigns"("id") on delete cascade,
  foreign key("donor_id") references "donors"("id") on delete cascade,
  foreign key("subscription_id") references "subscriptions"("id") on delete set null
);
CREATE INDEX "donations_campaign_id_index" on "donations"("campaign_id");
CREATE INDEX "donations_donor_id_index" on "donations"("donor_id");
CREATE INDEX "donations_subscription_id_index" on "donations"(
  "subscription_id"
);
CREATE INDEX "donations_created_at_index" on "donations"("created_at");
CREATE UNIQUE INDEX "donations_stripe_payment_intent_id_unique" on "donations"(
  "stripe_payment_intent_id"
);
CREATE INDEX "donations_status_index" on "donations"("status");
CREATE INDEX "donations_type_index" on "donations"("type");
CREATE TABLE IF NOT EXISTS "elements"(
  "id" integer primary key autoincrement not null,
  "organization_id" integer not null,
  "campaign_id" integer,
  "name" varchar not null,
  "token" varchar not null,
  "type" varchar not null,
  "config" text,
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  "form_slug" varchar,
  foreign key("organization_id") references "organizations"("id") on delete cascade,
  foreign key("campaign_id") references "campaigns"("id") on delete set null
);
CREATE INDEX "elements_organization_id_index" on "elements"("organization_id");
CREATE INDEX "elements_campaign_id_index" on "elements"("campaign_id");
CREATE UNIQUE INDEX "elements_token_unique" on "elements"("token");
CREATE TABLE IF NOT EXISTS "webhook_logs"(
  "id" integer primary key autoincrement not null,
  "stripe_event_id" varchar not null,
  "event_type" varchar not null,
  "payload" text not null,
  "status" varchar not null default 'received',
  "error_message" text,
  "processed_at" datetime,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "webhook_logs_stripe_event_id_unique" on "webhook_logs"(
  "stripe_event_id"
);
CREATE INDEX "webhook_logs_event_type_index" on "webhook_logs"("event_type");
CREATE INDEX "webhook_logs_status_index" on "webhook_logs"("status");
CREATE TABLE IF NOT EXISTS "organizations"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "ros_rob_number" varchar,
  "registration_type" varchar not null default('others'),
  "description" text,
  "logo_path" varchar,
  "website_url" varchar,
  "contact_email" varchar,
  "contact_phone" varchar,
  "status" varchar not null default('pending'),
  "stripe_account_id" varchar,
  "stripe_onboarded" tinyint(1) not null default('0'),
  "bank_account_name" varchar,
  "bank_account_number" varchar,
  "bank_name" varchar,
  "settings" text,
  "approved_at" datetime,
  "approved_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  "code" varchar not null,
  "address_line_1" varchar,
  "address_line_2" varchar,
  "city" varchar,
  "state" varchar,
  "postcode" varchar,
  "country" varchar default 'Malaysia',
  "sector" varchar,
  "tax_exempt" tinyint(1) not null default '0',
  "stripe_onboarded_at" datetime,
  "processing_fee_override" numeric,
  "admin_notes" text,
  foreign key("approved_by") references users("id") on delete set null on update no action
);
CREATE UNIQUE INDEX "organizations_ros_rob_number_unique" on "organizations"(
  "ros_rob_number"
);
CREATE INDEX "organizations_status_index" on "organizations"("status");
CREATE UNIQUE INDEX "organizations_stripe_account_id_unique" on "organizations"(
  "stripe_account_id"
);
CREATE UNIQUE INDEX "organizations_code_unique" on "organizations"("code");
CREATE UNIQUE INDEX "campaigns_form_parameter_unique" on "campaigns"(
  "form_parameter"
);
CREATE TABLE IF NOT EXISTS "subscriptions"(
  "id" integer primary key autoincrement not null,
  "campaign_id" integer not null,
  "donor_id" integer not null,
  "stripe_subscription_id" varchar,
  "stripe_price_id" varchar,
  "amount" numeric not null,
  "currency" varchar not null default('myr'),
  "interval" varchar not null,
  "status" varchar not null default('incomplete'),
  "retry_count" integer not null default('0'),
  "current_period_start" datetime,
  "current_period_end" datetime,
  "paused_until" datetime,
  "cancelled_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  "payment_count" integer not null default '0',
  "cancel_at_period_end" tinyint(1) not null default '0',
  foreign key("donor_id") references donors("id") on delete cascade on update no action,
  foreign key("campaign_id") references campaigns("id") on delete cascade on update no action
);
CREATE INDEX "subscriptions_campaign_id_index" on "subscriptions"(
  "campaign_id"
);
CREATE INDEX "subscriptions_donor_id_index" on "subscriptions"("donor_id");
CREATE INDEX "subscriptions_status_index" on "subscriptions"("status");
CREATE UNIQUE INDEX "subscriptions_stripe_subscription_id_unique" on "subscriptions"(
  "stripe_subscription_id"
);
CREATE UNIQUE INDEX "elements_form_slug_unique" on "elements"("form_slug");
CREATE TABLE IF NOT EXISTS "notifications"(
  "id" varchar not null,
  "type" varchar not null,
  "notifiable_type" varchar not null,
  "notifiable_id" integer not null,
  "data" text not null,
  "read_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  primary key("id")
);
CREATE INDEX "notifications_notifiable_type_notifiable_id_index" on "notifications"(
  "notifiable_type",
  "notifiable_id"
);
CREATE TABLE IF NOT EXISTS "settings"(
  "id" integer primary key autoincrement not null,
  "key" varchar not null,
  "value" text,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "settings_key_unique" on "settings"("key");
CREATE TABLE IF NOT EXISTS "monthly_invoices"(
  "id" integer primary key autoincrement not null,
  "organization_id" integer not null,
  "stripe_invoice_id" varchar not null,
  "invoice_number" varchar not null,
  "period" date not null,
  "total_fees" numeric not null,
  "stripe_status" varchar not null default 'open',
  "paid_at" datetime,
  "stripe_invoice_url" varchar,
  "stripe_invoice_pdf" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("organization_id") references "organizations"("id") on delete cascade
);
CREATE UNIQUE INDEX "monthly_invoices_stripe_invoice_id_unique" on "monthly_invoices"(
  "stripe_invoice_id"
);
CREATE UNIQUE INDEX "monthly_invoices_invoice_number_unique" on "monthly_invoices"(
  "invoice_number"
);
CREATE INDEX "monthly_invoices_stripe_status_index" on "monthly_invoices"(
  "stripe_status"
);
CREATE TABLE IF NOT EXISTS "processing_fees"(
  "id" integer primary key autoincrement not null,
  "donation_id" integer not null,
  "organization_id" integer not null,
  "fee_amount" numeric not null,
  "fee_percentage" numeric not null,
  "stripe_transfer_id" varchar,
  "status" varchar not null default('pending'),
  "created_at" datetime,
  "updated_at" datetime,
  "monthly_invoice_id" integer,
  foreign key("organization_id") references organizations("id") on delete cascade on update no action,
  foreign key("donation_id") references donations("id") on delete cascade on update no action,
  foreign key("monthly_invoice_id") references "monthly_invoices"("id") on delete set null
);
CREATE INDEX "platform_fees_status_index" on "processing_fees"("status");

INSERT INTO migrations VALUES(1,'0001_01_01_000000_create_users_table',1);
INSERT INTO migrations VALUES(2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO migrations VALUES(3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO migrations VALUES(4,'2025_08_14_170933_add_two_factor_columns_to_users_table',1);
INSERT INTO migrations VALUES(5,'2026_05_18_154519_create_ihsan_foundation_tables',1);
INSERT INTO migrations VALUES(6,'2026_05_18_225316_replace_organization_slug_with_code',1);
INSERT INTO migrations VALUES(7,'2026_05_20_045627_add_extra_fields_to_campaigns_table',2);
INSERT INTO migrations VALUES(8,'2026_05_20_062808_update_suggested_amounts_in_campaigns_table',3);
INSERT INTO migrations VALUES(9,'2026_05_20_113103_add_form_parameter_to_campaigns_table',4);
INSERT INTO migrations VALUES(10,'2026_05_20_115726_add_payment_method_columns_to_donations_table',5);
INSERT INTO migrations VALUES(11,'2026_05_20_131718_make_stripe_subscription_id_nullable',6);
INSERT INTO migrations VALUES(12,'2026_05_20_133041_add_form_slug_to_elements_table',7);
INSERT INTO migrations VALUES(13,'2026_05_20_153035_add_checkout_modal_settings_to_campaigns_table',8);
INSERT INTO migrations VALUES(14,'2026_05_21_044720_create_notifications_table',9);
INSERT INTO migrations VALUES(15,'2026_05_22_024403_add_avatar_url_to_users_table',10);
INSERT INTO migrations VALUES(16,'2026_05_22_083707_add_address_sector_and_tax_fields_to_organizations_table',11);
INSERT INTO migrations VALUES(17,'2026_05_22_120954_add_last_login_at_to_users_table',12);
INSERT INTO migrations VALUES(18,'2026_05_22_145140_add_stripe_onboarded_at_to_organizations_table',13);
INSERT INTO migrations VALUES(19,'2026_05_23_033000_replace_payment_method_last4_with_type_on_donations_table',14);
INSERT INTO migrations VALUES(20,'2026_05_23_034502_add_payment_count_to_subscriptions_table',15);
INSERT INTO migrations VALUES(21,'2026_05_23_063427_create_settings_table',16);
INSERT INTO migrations VALUES(24,'2026_05_23_080000_create_monthly_invoices_table',17);
INSERT INTO migrations VALUES(25,'2026_05_23_081000_update_platform_fees_table',17);
INSERT INTO migrations VALUES(26,'2026_05_23_153425_remove_slug_from_campaigns_table',18);
INSERT INTO migrations VALUES(27,'2026_05_23_160724_add_cancel_at_period_end_to_subscriptions_table',19);
INSERT INTO migrations VALUES(28,'2026_05_24_040231_rename_platform_fees_to_processing_fees',20);
INSERT INTO migrations VALUES(29,'2026_05_24_082948_add_platform_fee_override_and_admin_notes_to_organizations_table',21);
INSERT INTO migrations VALUES(30,'2026_05_24_083420_rename_platform_fee_override_to_processing_fee_override_on_organizations_table',22);
