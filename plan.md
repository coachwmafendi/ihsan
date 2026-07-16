# Plan: Read Real Visitor IP Behind Cloudflare

## Problem
App sekarang catat IP Cloudflare (`172.71.x.x`) sebagai IP pengguna, jadi lokasi geolocation sentiasa tunjuk Singapore/North West walaupun pengguna sebenar di Malaysia atau lain-lain.

## Goal
Pastikan `request()->ip()` dan servis geolocation (`App\Support\ClientInfo`) mengesan IP asal pengguna apabila request lalu Cloudflare.

## Approach (tanpa tambah Composer dependency)
1. **Simpan senarai IP Cloudflare** dalam cache + fallback default (`App\Services\Cloudflare\IpRanges`).
2. **Command refresh** (`app:cloudflare-update-ips`) untuk ambil senarai terkini dari `https://www.cloudflare.com/ips-v4` dan `ips-v6`, kemudian cache.
3. **Schedule command** mingguan dalam `routes/console.php` supaya senarai sentiasa fresh.
4. **Middleware `TrustCloudflare`** yang set trusted proxies kepada range Cloudflare di awal setiap request menggunakan `setTrustedProxies` dengan header `X-Forwarded-For` / `X-Forwarded-Proto`.
5. **Daftar middleware** di `bootstrap/app.php` menggunakan `prepend()` supaya jalan sebelum middleware lain.
6. **Ujian**:
   - Command berjaya cache ranges.
   - Request dari IP Cloudflare dengan header `X-Forwarded-For` akan return IP asal.
   - Request direct/lokal tidak diubah.

## Files
- Create `app/Services/Cloudflare/IpRanges.php`
- Create `app/Console/Commands/UpdateCloudflareIps.php`
- Create `app/Http/Middleware/TrustCloudflare.php`
- Create `tests/Unit/CloudflareIpRangesTest.php`
- Create `tests/Feature/TrustCloudflareMiddlewareTest.php`
- Modify `bootstrap/app.php`
- Modify `routes/console.php`

## Nota Keselamatan
- Hanya trust range IP Cloudflare yang rasmi; fallback adalah default range yang sama.
- Direct request (bypass Cloudflare) tidak akan terjejas kerana remote IP tidak dalam trusted list.
