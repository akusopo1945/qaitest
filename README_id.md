# Qaitest

Qaitest adalah PHP playground ringan untuk guestbook, halaman demo, dan workflow QA berbasis Playwright.

## Gambaran

Project ini memakai:

- PHP native
- Nginx
- PHP-FPM
- Playwright untuk test otomatis

Tujuannya sederhana:

- menyediakan landing page yang rapi
- menyediakan halaman demo tambahan
- menyediakan guestbook kecil dengan CRUD
- menyediakan dashboard QA untuk prompt, plan, dan eksekusi
- menjadi baseline project yang mudah dirawat

## Isi Project

Komponen utama project:

- homepage di `index.php`
- halaman about di `about.php`
- halaman entries di `entries.php`
- halaman QA dashboard di `qa.php`
- halaman edit di `edit.php`
- helper PHP di `app/bootstrap.php`
- template reusable di `app/layout.php`
- storage helper di `app/storage.php`
- schema MySQL referensi di `database/guestbook_schema.sql`
- footer reusable di `partials/footer.php`
- navigasi reusable di `partials/topbar.php`
- test otomatis di folder `tests/`
- config Playwright di `playwright.config.ts`
- pedoman sistem di `project_context.md`
- format plan QA netral di `qa_plan_format.md`
- schema plan QA di `qa/qa_plan.schema.json`
- runner plan QA di `qa/runner.js`
- planner OpenAI di `qa/plan.js`
- contoh plan QA di `qa/plans/guestbook-happy.json`

## Halaman Utama

### Home

Halaman ini menampilkan:

- judul utama dengan hero yang lebih serius
- status chip
- demo flow ringkas yang menunjukkan alur AI planning
- form guestbook kecil untuk isi nama
- greeting personal
- server name
- request URI
- recent entries

### About

Halaman ini dipakai untuk menjelaskan project secara singkat.

### Entries

Halaman ini menampilkan semua data yang tersimpan lewat guestbook homepage.

Fitur yang tersedia:

- search nama atau pesan
- sorting newest, oldest, name A-Z, atau name Z-A
- filter rentang tanggal dengan `from` dan `to`
- pagination saat data mulai banyak
- aksi edit dan delete

### Edit

Halaman ini dipakai untuk memperbarui entry yang sudah disimpan.

### QA Dashboard

Halaman ini dipakai untuk:

- menulis prompt test dengan bahasa manusia
- generate plan terstruktur dari OpenAI
- menjalankan plan lewat Playwright
- melihat output teknis dan hasil eksekusi

## Cara Jalanin

### 1. Install dependency

```bash
pnpm install
```

### 2. Siapkan base URL

Default base URL project ini:

```bash
http://qaitest.test/
```

Kalau perlu, sesuaikan mapping host atau base URL sesuai environment yang dipakai.

### 3. Buka di browser

```text
http://qaitest.test/
```

Kalau berhasil, landing page Qaitest akan tampil normal di browser.

## Testing

Project ini punya dua lapis utama:

- layer app: PHP native playground
- layer QA: Playwright sebagai executor deterministik

Alur QA yang dipakai:

1. prompt natural language
2. plan terstruktur
3. eksekusi Playwright
4. technical output
5. AI summary

Untuk eksekusi plan QA manual:

```bash
pnpm qa:run qa/plans/guestbook-happy.json
```

Hasil teknis akan ditulis ke `qa-output/<plan-id>.result.json`.

Untuk bikin plan dari prompt natural language:

```bash
pnpm qa:plan --prompt "cek user bisa submit guestbook lalu entry muncul di halaman entries"
```

Untuk simpan hasil plan ke file:

```bash
pnpm qa:plan --prompt "cek guestbook happy path" --output qa/plans/generated.json
```

Smoke test Playwright:

```bash
pnpm test
```

Test yang dicek sekarang:

- homepage menampilkan status sukses
- homepage menampilkan server name
- greeting personal muncul saat query `name` dikirim
- guestbook entry bisa disimpan
- entry bisa diedit dan dihapus
- entries page bisa search dan pagination
- entries page bisa sorting dan filter tanggal
- entries page bisa menampilkan data
- QA dashboard page menampilkan form planning
- halaman about menampilkan ringkasan project

## Environment

File env yang dipakai:

- `.env.local`
- `.env`

Setting yang penting:

```bash
PLAYWRIGHT_BASE_URL=http://qaitest.test/
OPENAI_API_KEY=your_openai_api_key
OPENAI_MODEL=gpt-5.5
```

Kalau ingin pakai URL lain, override lewat `.env.local`.

Kalau ingin biaya lebih ringan saat generate plan, override `OPENAI_MODEL` ke model yang lebih kecil seperti `gpt-5.4-mini`.

Kalau mau pakai MySQL:

```bash
GUESTBOOK_STORAGE=mysql
GUESTBOOK_DB_HOST=127.0.0.1
GUESTBOOK_DB_PORT=3306
GUESTBOOK_DB_NAME=qaitest
GUESTBOOK_DB_USER=akusopo
GUESTBOOK_DB_PASSWORD=...
```

Mode ini aktif otomatis kalau `.env.local` sudah diisi.

Schema MySQL yang dipakai sekarang:

- `created_at` memakai `DATETIME(3)`
- ada `updated_at`
- ada index untuk `created_at`, `name + created_at`, dan `updated_at`
- ada referensi SQL manual di `database/guestbook_schema.sql`

## Struktur File

Struktur utama yang perlu diketahui:

- `index.php` untuk homepage
- `about.php` untuk halaman about
- `entries.php` untuk list data
- `edit.php` untuk update data
- `app/bootstrap.php` untuk helper umum
- `app/layout.php` untuk template reusable
- `app/storage.php` untuk simpan dan baca guestbook
- `partials/footer.php` untuk footer reusable
- `partials/topbar.php` untuk navigasi
- `tests/*.spec.ts` untuk Playwright
- `playwright.config.ts` untuk konfigurasi test
- `project_context.md` untuk pedoman sistem

## Konvensi Yang Dipakai

Aturan main project:

- pakai PHP native biar sederhana
- escape semua output yang datang dari user
- jangan render HTML mentah dari input
- kalau bikin halaman baru, usahakan reusable
- kalau nambah fitur baru, tambah test sekalian
- kalau data mulai tumbuh, MySQL sudah disiapkan sebagai jalur berikutnya

## Flow Pengembangan Yang Disarankan

Urutan kerja yang paling enak:

1. definisikan intent test dalam natural language
2. ubah intent itu jadi plan terstruktur
3. jalankan plan lewat Playwright
4. simpan output teknis yang eksplisit
5. buat AI summary di akhir
6. kalau perlu, tambah fitur aplikasi atau coverage test

Jangan langsung bikin arsitektur terlalu berat kalau belum ada kebutuhan nyata. Project ini lebih baik dijaga tetap kecil, jelas, dan mudah dirawat.

## Next Step Yang Cocok

Kalau mau lanjut, arah yang masuk akal:

1. tambah filter lanjutan di entries
2. tambah auth sederhana kalau butuh area private
3. tambah CI supaya `pnpm test` jalan otomatis
