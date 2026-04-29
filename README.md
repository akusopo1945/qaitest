# Qaitest

Qaitest ini intinya local PHP playground yang bisa kamu buka di browser lewat domain lokal `http://qaitest.test/`.

Kalau mau ngomong simpel:

- ini bukan app gede yang ribet
- ini bukan framework berat
- ini PHP native, Nginx, PHP-FPM, dan Playwright buat test
- cocok buat ngecek flow lokal, eksperimen, atau jadi base project kecil yang rapi

Footer resmi project ini:

```text
dev with ❤️by akuncilik7
```

## Isi Project

Sekarang project ini punya beberapa bagian:

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

## Yang Bisa Dibuka Di Browser

1. Home

```text
http://qaitest.test/
```

Halaman ini nampilin:

- judul utama dengan hero yang lebih serius
- status chip
- demo flow hardcode yang nunjukin alur AI planning
- form guestbook kecil buat isi nama
- greeting personal
- server name
- request URI
- recent entries
- footer credit

2. About

```text
http://qaitest.test/about.php
```

Halaman ini dipakai buat ngejelasin project secara singkat dan tetap punya footer credit yang sama.

3. Entries

```text
http://qaitest.test/entries.php
```

Halaman ini nunjukin semua data yang kamu simpan lewat guestbook homepage.

Di halaman ini juga ada:

- search nama/pesan
- sorting data by newest, oldest, name A-Z, atau name Z-A
- filter rentang tanggal pakai `from` dan `to`
- pagination kalau data mulai banyak
- aksi edit dan delete

4. Edit

```text
http://qaitest.test/edit.php?id=<entry-id>
```

Halaman ini dipakai buat update entry yang sudah disimpan.

5. QA Dashboard

```text
http://qaitest.test/qa.php
```

Halaman ini dipakai buat:

- nulis prompt test pakai bahasa manusia
- generate plan terstruktur dari OpenAI
- menjalankan plan itu lewat Playwright
- melihat output teknis dan hasil eksekusi

## Cara Jalanin

### 1. Install dependency

```bash
pnpm install
```

### 2. Pastikan host lokal siap

Default base URL project ini:

```bash
http://qaitest.test/
```

Kalau kamu pakai WSL:
- pastikan domain itu resolve dari WSL
- kalau belum, cek `/etc/hosts`

Kalau kamu pakai Windows:
- pastikan `C:\Windows\System32\drivers\etc\hosts` sudah ada mapping ke host yang benar

### 3. Buka di browser

```text
http://qaitest.test/
```

Kalau berhasil, kamu bakal lihat landing page Qaitest yang sekarang lebih proper, bukan output PHP mentah lagi.

## Testing

Project ini sekarang ada di dua lapis:

- layer app: PHP native local playground
- layer QA: Playwright sebagai executor deterministik

Nanti, kalau mesin QA AI mulai dipasang, alurnya pakai:

1. prompt natural language
2. plan terstruktur
3. eksekusi Playwright
4. technical output
5. AI summary

Project ini masih belum jadi engine QA AI penuh, tapi fondasi arah itu sudah disiapkan.

Kalau kamu mau eksekusi plan QA manual, pakai:

```bash
pnpm qa:run qa/plans/guestbook-happy.json
```

Hasil teknis akan ditulis ke `qa-output/<plan-id>.result.json`.

Kalau kamu mau bikin plan dari prompt natural language:

```bash
pnpm qa:plan --prompt "cek user bisa submit guestbook lalu entry muncul di halaman entries"
```

Kalau mau simpan hasilnya ke file:

```bash
pnpm qa:plan --prompt "cek guestbook happy path" --output qa/plans/generated.json
```

Project ini juga sudah punya smoke test pakai Playwright.

Jalankan:

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
- halaman about menampilkan footer credit

Kalau kamu jalanin test dari WSL:
- browser Playwright harus terpasang di WSL
- dependency sistem browser juga harus tersedia

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

Kalau kamu mau pakai URL lain, tinggal override lewat `.env.local`.

Kalau ingin biaya lebih ringan saat generate plan, kamu bisa override `OPENAI_MODEL` ke model yang lebih kecil seperti `gpt-5.4-mini`.

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

Schema MySQL yang dipakai sekarang juga sudah dirapikan:

- `created_at` pakai `DATETIME(3)`
- ada `updated_at`
- ada index untuk `created_at`, `name + created_at`, dan `updated_at`
- ada referensi SQL manual di `database/guestbook_schema.sql`

## Struktur File

Struktur utama yang perlu kamu tahu:

- `index.php` untuk homepage
- `about.php` untuk halaman about
- `entries.php` untuk list data
- `edit.php` untuk update data
- `app/bootstrap.php` untuk helper umum
- `app/layout.php` untuk template reusable
- `app/storage.php` untuk simpan/baca guestbook
- `partials/footer.php` untuk footer credit
- `partials/topbar.php` untuk navigasi
- `tests/*.spec.ts` untuk Playwright
- `playwright.config.ts` untuk konfigurasi test
- `project_context.md` untuk pedoman sistem

## Konvensi Yang Dipakai

Ini beberapa aturan main yang dipakai di project ini:

- pakai PHP native biar sederhana
- escape semua output yang datang dari user
- jangan render HTML mentah dari input
- kalau bikin halaman baru, usahakan reusable
- kalau nambah fitur baru, tambah test sekalian
- kalau data mulai tumbuh, MySQL sudah disiapkan sebagai jalur berikutnya

## Flow Pengembangan Yang Disarankan

Kalau mau lanjut develop, urutan paling enak biasanya:

1. definisikan intent test dalam natural language
2. ubah intent itu jadi plan terstruktur
3. jalankan plan lewat Playwright
4. simpan output teknis yang eksplisit
5. baru bikin AI summary di akhir
6. setelah itu, kalau perlu, tambah fitur aplikasi atau coverage test

Jangan langsung bikin arsitektur terlalu berat kalau belum ada kebutuhan nyata. Project ini enaknya dijaga tetap kecil, jelas, dan gampang di-maintain.

## Catatan Buat WSL / Windows

Karena project ini dipakai di WSL tapi juga diakses dari browser Windows:

- browser Windows baca host dari Windows `hosts`
- Playwright di WSL baca host dari lingkungan WSL
- kalau hasil browser dan hasil test beda, biasanya masalahnya ada di DNS/hosts/cache, bukan di PHP-nya

## Next Step Yang Cocok

Kalau kamu mau lanjut, arah yang paling masuk akal:

1. tambah filter lanjutan di entries
2. tambah auth sederhana kalau butuh area private
3. tambah CI biar `pnpm test` jalan otomatis
