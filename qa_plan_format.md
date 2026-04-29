# QA Plan Format

Dokumen ini menjelaskan format plan netral untuk mesin QA Qaitest.

Target alurnya:

1. prompt test ditulis pakai natural language
2. AI mengubah intent jadi plan terstruktur
3. Playwright mengeksekusi plan
4. sistem mengeluarkan log teknis eksplisit
5. AI membuat summary akhir

Format ini sengaja dibuat provider-agnostic supaya bisa dipakai dengan OpenAI sekarang dan Gemini nanti tanpa mengubah core engine.

## Prinsip

- prompt awal boleh santai dan natural
- plan harus deterministik, eksplisit, dan mudah diaudit
- Playwright tetap jadi eksekutor utama
- AI hanya membuat plan, membantu analisis gagal, dan menulis summary
- output teknis jangan disamarkan oleh AI

## Struktur Plan

Contoh bentuk data plan:

```json
{
  "id": "qa-plan-001",
  "title": "Guestbook happy path",
  "objective": "Pastikan user bisa simpan entry guestbook lalu melihatnya di halaman entries",
  "provider_hint": "openai",
  "base_url": "http://qaitest.test/",
  "priority": "normal",
  "preconditions": [
    "App bisa diakses di base_url",
    "Database atau storage aktif"
  ],
  "input_data": {
    "name": "Tester QA",
    "message": "Halo dari mesin QA"
  },
  "steps": [
    {
      "id": "step-1",
      "type": "navigate",
      "url": "/",
      "description": "Buka homepage"
    },
    {
      "id": "step-2",
      "type": "fill",
      "target": "Nama pengunjung",
      "value_ref": "input_data.name",
      "description": "Isi nama"
    },
    {
      "id": "step-3",
      "type": "fill",
      "target": "Pesan",
      "value_ref": "input_data.message",
      "description": "Isi pesan"
    },
    {
      "id": "step-4",
      "type": "click",
      "target": "Simpan ke guestbook",
      "description": "Submit form"
    },
    {
      "id": "step-5",
      "type": "assert_visible",
      "target": "[data-testid=\"saved-notice\"]",
      "description": "Pastikan notifikasi sukses muncul"
    },
    {
      "id": "step-6",
      "type": "navigate",
      "url": "/entries.php",
      "description": "Buka halaman entries"
    },
    {
      "id": "step-7",
      "type": "assert_text",
      "target": "[data-testid=\"entries-list\"]",
      "contains_ref": "input_data.message",
      "description": "Pastikan entry tersimpan muncul di list"
    }
  ],
  "assertions": [
    {
      "id": "assert-1",
      "type": "url_contains",
      "value": "saved=1"
    },
    {
      "id": "assert-2",
      "type": "text_contains",
      "target": "[data-testid=\"entries-list\"]",
      "value_ref": "input_data.message"
    }
  ],
  "cleanup": [
    {
      "type": "delete_created_entries",
      "strategy": "by_text_match"
    }
  ],
  "reporting": {
    "log_level": "technical",
    "include_screenshots": true,
    "include_trace": true,
    "include_dom_snapshot": true
  },
  "ai_summary": {
    "tone": "plain",
    "include_root_cause": true,
    "include_suggestions": true
  }
}
```

## Tipe Step Yang Disarankan

- `navigate`: buka URL
- `fill`: isi input atau textarea
- `click`: klik tombol atau link
- `select`: pilih value di select
- `wait_for`: tunggu elemen atau state tertentu
- `assert_text`: cek teks pada target
- `assert_visible`: cek elemen terlihat
- `assert_count`: cek jumlah elemen
- `confirm_dialog`: terima atau tolak dialog
- `screenshot`: simpan screenshot
- `custom`: fallback kalau butuh tindakan khusus

## Output Yang Harus Dihasilkan

Output final idealnya terdiri dari:

- ringkasan eksekusi per step
- status tiap assertion
- error teknis yang jelas
- screenshot atau trace bila perlu
- AI summary yang menjelaskan hasil dengan bahasa manusia

Format ini sengaja dibuat supaya manusia tetap bisa audit hasil test tanpa harus nebak-nebak intent AI.
