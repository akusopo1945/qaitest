# Contributing to Qaitest

Kalau kamu mau ikut ngoprek Qaitest, santai aja. Project ini sengaja dijaga kecil, jelas, dan gampang diubah tanpa harus mikir arsitektur terlalu berat.

## Prinsip Dasar

Yang paling penting saat kontribusi:

- jangan bikin perubahan yang terlalu besar kalau kebutuhan belum jelas
- jaga supaya kode tetap gampang dibaca
- kalau nambah fitur, tambah test juga
- kalau ada helper atau partial yang bisa dipakai ulang, jangan ditulis dobel
- kalau data mulai penting, pertimbangkan database setelah JSON mulai terasa sempit
- kalau storage sudah pindah ke MySQL, jaga skema tetap sinkron dengan `database/guestbook_schema.sql`

## Setup Lokal

### Install dependency

```bash
pnpm install
```

### Jalankan app

Pastikan host lokal `qaitest.test` sudah mengarah ke environment yang benar, lalu buka:

```text
http://qaitest.test/
```

### Jalankan test

```bash
pnpm test
```

## Struktur Kerja

File yang biasanya kamu sentuh:

- `index.php` untuk homepage
- `about.php` untuk halaman tambahan
- `entries.php` untuk list dan aksi data
- `edit.php` untuk update data
- `app/bootstrap.php` untuk helper umum
- `partials/` untuk potongan UI yang dipakai ulang
- `tests/` untuk Playwright smoke test
- `playwright.config.ts` untuk konfigurasi browser test
- kalau pakai search/pagination/sort/filter tanggal, jaga query param tetap konsisten
- kalau storage pindah ke MySQL, update `.env.local` dan test sekalian

Kalau kamu bikin bagian baru, usahakan:

- logic PHP tetap simpel
- HTML tetap rapi
- CSS jangan bikin file yang susah dirawat
- selector penting diberi `data-testid` biar test stabil

## Cara Ngerjain Fitur Baru

Urutan yang enak dipakai:

1. tentukan perilaku apa yang mau ditambah
2. implementasikan versi paling kecil yang jalan dulu
3. tambah test Playwright
4. rapikan kalau ada duplikasi

## Styling Dan UI

Project ini pakai visual yang agak gelap, clean, dan modern. Kalau kamu nambah UI:

- pertahankan vibe yang konsisten
- jangan terlalu ramai
- mobile harus tetap enak dibuka
- kalau ada elemen baru, pastikan aksesibilitas dasar tetap aman

## PHP Rules

Beberapa aturan yang perlu dijaga:

- pakai `declare(strict_types=1);` untuk file PHP baru
- escape semua output dari user pakai helper `h()`
- jangan langsung echo input mentah ke HTML
- kalau perlu partial, pakai `require`

## Testing Rules

Kalau kamu ubah behavior aplikasi:

- update test yang kena dampak
- kalau tambah page baru, minimal bikin smoke test
- kalau ada selector penting, prefer `data-testid`

## Pull Request Checklist

Sebelum dianggap selesai, cek ini:

- `pnpm test` lulus
- halaman yang diubah bisa dibuka di browser
- tidak ada output PHP mentah yang kelihatan
- perubahan sesuai konteks di [project_context.md](/var/www/html/Qaitest/project_context.md)

## Catatan WSL / Windows

Kalau kerja di WSL tapi buka browser dari Windows:

- pastikan host `qaitest.test` resolve di environment yang dipakai
- kalau hasil browser beda dengan test, cek hosts dan cache dulu
- jangan asumsi masalahnya di PHP sebelum cek DNS/hosts
