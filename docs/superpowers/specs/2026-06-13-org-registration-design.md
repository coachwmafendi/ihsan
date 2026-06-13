# Organisation Registration — Design Spec

Date: 2026-06-13

## Goal

Public self-service registration form for NGOs/organisations. Creates an `Organization` record with `status = pending`. Admin reviews and approves via Filament. No user account created at this stage.

## Route

| Method | URI | Name |
|--------|-----|------|
| GET | `/daftar` | `register.org` |
| POST | `/daftar` | `register.org.store` |

## Fields

| Field | Column | Type | Required |
|-------|--------|------|----------|
| Name | `name` | text | yes |
| Type | `registration_type` | select | yes |
| ROB/ROS Number | `ros_rob_number` | text | yes |
| Sector | `sector` | select | yes |
| Email | `contact_email` | email | yes |
| Website | `website_url` | url | yes |
| Facebook URL | `facebook_url` | url | no |

### Registration Type options
- ROS
- ROB
- Others

### Sector options
- Agama
- Pendidikan
- Kebajikan Sosial
- Kesihatan
- Alam Sekitar
- Sukan & Rekreasi
- Lain-lain

## Database

Migration: add `facebook_url string nullable` to `organizations` table.

`Organization::$fillable` already managed via `#[Fillable]` attribute — add `facebook_url` to it.

## Component

**Class:** `App\Livewire\Auth\RegisterOrganization`
**View:** `resources/views/livewire/auth/register-organization.blade.php`

### State
```php
public string $name = '';
public string $registration_type = '';
public string $ros_rob_number = '';
public string $sector = '';
public string $contact_email = '';
public string $website_url = '';
public string $facebook_url = '';
public bool $submitted = false;
```

### Validation rules
```php
'name'              => ['required', 'string', 'max:255'],
'registration_type' => ['required', 'in:ROS,ROB,Others'],
'ros_rob_number'    => ['required', 'string', 'max:100'],
'sector'            => ['required', 'string', 'max:100'],
'contact_email'     => ['required', 'email', 'max:255'],
'website_url'       => ['required', 'url', 'max:255'],
'facebook_url'      => ['nullable', 'url', 'max:255'],
```

### Submit action
1. Validate
2. `Organization::create([..., 'status' => OrganizationStatus::Pending])`
3. Set `$submitted = true`
4. View switches to success state (no redirect)

## Layout

Uses `x-layouts::landing` (same as welcome page). Flux UI components for form inputs/selects. Styled consistent with existing auth pages but on the dark landing background.

## Success State

Inline — no redirect. Show confirmation message:
> "Terima kasih! Permohonan anda telah diterima. Pasukan kami akan menyemak dan menghubungi anda tidak lama lagi."

## Navigation

Add "Daftar Organisasi" link on the landing page register flow — e.g. below the existing register CTA or as a secondary link.

## Out of Scope

- Email notifications (can add later)
- User account creation (separate step, handled by admin)
- File/document uploads
