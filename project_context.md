# Project Context - Qaitest

Dokumen ini menjelaskan konteks sistem untuk repo `Qaitest` agar pengembangan berikutnya konsisten.

## Tujuan Aplikasi

Qaitest adalah local PHP playground yang dapat diakses lewat browser Windows melalui host lokal `http://qaitest.test/`.

Tujuan utamanya saat ini:

- memastikan stack Nginx + PHP-FPM berjalan
- menyediakan landing page sederhana yang layak dibuka di browser
- menyediakan contoh halaman tambahan dan form sederhana
- menyediakan guestbook lokal yang menyimpan data ke JSON atau MySQL
- menyediakan edit/delete untuk entries
- menyediakan search dan pagination di entries
- menyediakan sorting dan date filter di entries
- menjadi target smoke test otomatis dengan Playwright
- menyediakan spesifikasi plan QA netral untuk alur AI -> plan -> Playwright -> summary
- menyediakan planner OpenAI yang mengubah prompt natural language menjadi plan JSON
- menyediakan runner QA minimal yang bisa mengeksekusi plan terstruktur
- menyediakan dashboard web untuk menulis prompt, generate plan, dan menjalankan plan

## Stack Dan Runtime

- PHP: aplikasi utama ditulis langsung dengan PHP native
- Web server: Nginx
- PHP handler: PHP-FPM 8.3
- Browser test: Playwright
- Test runner: `pnpm test`

## URL Dan Environment

- Canonical local URL: `http://qaitest.test/`
- About page: `http://qaitest.test/about.php`
- WSL dan Windows sama-sama bisa dipakai untuk akses lokal, selama host resolve ke server yang benar
- Playwright membaca `PLAYWRIGHT_BASE_URL` dari `.env.local` atau `.env`

Default test base URL:

```bash
PLAYWRIGHT_BASE_URL=http://qaitest.test/
```

## Struktur File

File utama yang relevan:

- `index.php`: landing page utama
- `about.php`: halaman tambahan
- `entries.php`: daftar semua guestbook entries
- `edit.php`: halaman update entry
- `app/bootstrap.php`: helper umum seperti escape HTML
- `app/layout.php`: template reusable
- `app/storage.php`: helper baca/tulis guestbook JSON
- `database/guestbook_schema.sql`: referensi schema MySQL produksi
- `partials/footer.php`: footer credit reusable
- `partials/topbar.php`: navigasi reusable
- `qa_plan_format.md`: spesifikasi format plan QA netral
- `qa/qa_plan.schema.json`: schema plan QA
- `qa/plan.js`: CLI planner dari prompt natural language
- `qa/runner.js`: runner Playwright untuk plan QA
- `qa/plans/guestbook-happy.json`: contoh plan yang bisa dijalankan
- `qa.php`: dashboard web QA
- `playwright.config.ts`: konfigurasi test runner
- `tests/*.spec.ts`: smoke test Playwright
- `README.md`: setup singkat untuk manusia
- `project_context.md`: pedoman sistem ini

## Halaman Saat Ini

### Home

`index.php` menampilkan:

- judul halaman dengan hero produk yang lebih kuat
- status chip
- demo flow hardcode tentang prompt -> plan -> run -> summary
- form sederhana untuk memasukkan nama
- greeting personal berdasarkan query string `?name=...`
- server name
- request URI
- recent entries dari guestbook
- footer credit

### About

`about.php` menampilkan:

- deskripsi singkat project
- server name
- footer credit

### Entries

`entries.php` menampilkan:

- jumlah data guestbook
- semua data yang tersimpan
- link balik ke homepage
- search nama/pesan
- sorting newest/oldest/name
- filter tanggal from/to
- pagination
- aksi edit/delete

### Edit

`edit.php` menampilkan:

- form update entry
- detail entry yang sedang diedit

## Konvensi Implementasi

- Gunakan PHP native yang sederhana dan eksplisit
- Escape semua output user dengan helper `h()`
- Jangan render HTML mentah dari input pengguna
- Pertahankan tampilan yang modern tetapi tetap ringan
- Bila menambah halaman baru, usahakan reusable lewat partial atau helper

## Footer Credit

Footer resmi aplikasi saat ini:

```text
dev with ❤️by akuncilik7
```

Gunakan string ini secara konsisten pada halaman yang memang memakai footer project.

## Testing

Test Playwright saat ini mencakup:

- status landing page
- server name rendering
- greeting personal
- guestbook save flow
- edit/delete flow
- search/pagination flow
- sorting/date filter flow
- entries page rendering
- QA dashboard rendering
- about page footer credit

Runner QA plan saat ini mencakup:

- planner OpenAI via Responses API + structured outputs
- dashboard web untuk prompt -> plan -> run
- eksekusi step deterministik via Playwright
- assertions eksplisit
- cleanup dasar untuk data yang dibuat saat test
- output teknis yang bisa diserialisasi ke JSON

Perintah utama:

```bash
pnpm test
```

Jika test dijalankan dari WSL, pastikan dependency browser Playwright sudah terpasang di WSL.

## Nginx Notes

Konfigurasi vhost lokal ada di environment, bukan di repo.

Hal penting:

- `server_name` mengarah ke `qaitest.test`
- `root` mengarah ke `/var/www/html/Qaitest`
- PHP diteruskan ke `php8.3-fpm.sock`
- reload Nginx setelah perubahan config environment
- MySQL backend aktif lewat `.env.local` saat tersedia
- skema MySQL produksi yang disarankan ada di `database/guestbook_schema.sql`

## Prinsip Perubahan Berikutnya

Saat menambah fitur baru:

1. mulai dari intent test dalam natural language
2. ubah intent itu jadi plan terstruktur yang eksplisit
3. jalankan plan lewat Playwright
4. simpan output teknis yang detail
5. tambahkan AI summary sebagai lapisan penjelas
6. jaga struktur file tetap kecil dan mudah dibaca
7. jangan memecah ke arsitektur berat sebelum ada kebutuhan nyata

## Catatan Praktis

- Jangan ubah host lokal tanpa alasan kuat; `qaitest.test` adalah source of truth untuk lingkungan ini
- Bila browser Windows tidak cocok dengan hasil test WSL, cek resolusi host dan caching browser dulu
- Jika ingin menambah form atau data flow baru, lebih baik tambah satu halaman atau satu endpoint kecil lebih dulu
