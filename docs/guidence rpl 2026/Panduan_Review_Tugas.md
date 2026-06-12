# Panduan Review Tugas Mahasiswa (RPL-2026)
## Alur Kerja Hybrid Code Review (Dosen/Asisten + AI)

Mata kuliah **Rekayasa Perangkat Lunak (RPL-2026)** menggunakan alur peninjauan kode (code review) secara hybrid. Peninjauan dilakukan menggunakan **script otomatis berbasis Antigravity SDK** (`gemini-3.5-flash`) yang dipadukan dengan **penilaian kualitatif manual** oleh Dosen atau Asisten sebelum digabungkan (merge) ke branch utama.

---

## 1. Persiapan Lingkungan (Prerequisites)

Sebelum mulai melakukan review, pastikan komputer Anda telah dikonfigurasi dengan tools berikut:

1. **GitHub CLI (`gh`)**:
   Script otomatis menggunakan GitHub CLI untuk membaca daftar Pull Request dan memposting komentar.
   - Instal GitHub CLI dan login dengan akun Anda:
     ```bash
     gh auth login
     ```
   - Pastikan Anda memiliki akses write/collaborator pada repository proyek mahasiswa.

2. **Dependensi Python**:
   - Pastikan Python 3 sudah terinstal di sistem Anda.
   - Instal paket-paket yang diperlukan:
     ```bash
     pip install -r .agents/scripts/requirements.txt
     ```

---

## 2. Cara Menjalankan Review Otomatis (AI Reviewer)

Review otomatis dapat dijalankan melalui menu interaktif di terminal dengan langkah-langkah berikut:

1. Pastikan Anda berada di direktori root proyek `RPL-2026` dan berada di branch review yang benar.
2. Jalankan perintah runner review:
   ```bash
   npm run review:prs
   ```
   *Atau langsung jalankan via Python:*
   ```bash
   python .agents/scripts/pr-reviewer.py
   ```
3. **Pilih Pull Request**:
   Script akan mengambil daftar PR aktif. Gunakan tombol **panah atas/bawah** untuk navigasi, tombol **Spasi** untuk memilih satu atau lebih PR mahasiswa yang ingin direview, lalu tekan **Enter**.
4. **Analisis AI**:
   Script akan mengunduh kode diff dari GitHub dan memanggil **Google Antigravity SDK** secara lokal untuk menganalisis kode berdasarkan panduan di `.agents/workflows/review-instructions.md`.
5. **Output Hasil**:
   - Laporan review lengkap akan disimpan secara lokal di folder `.agents/reviews/PR-<nomor>-review.md`.
   - Di terminal, Anda akan melihat kesimpulan hasil analisis AI berupa keputusan: **`✅ APPROVED`** atau **`❌ REQUEST CHANGES`**.
6. **Posting ke GitHub**:
   Script akan menanyakan konfirmasi: *"Apakah Anda ingin memposting komentar review ke GitHub PR #..."*. Ketik **Y** jika ingin mengirimkan langsung laporan review tersebut sebagai komentar di GitHub PR mahasiswa.

---

## 3. Kriteria Penilaian Manual (Dosen/Asisten)

Reviewer manusia wajib memvalidasi aspek kualitatif dan keselarasan arsitektur sistem yang tidak dapat ditangkap sepenuhnya oleh AI:

### A. Data Layer (Database & Model)
- **Migrasi**: Cek apakah penamaan tabel mematuhi aturan (Kelas B menggunakan tabel standar, Kelas G wajib menggunakan tabel berkonteks OJS seperti `submissions`, `review_assignments`).
- **Relasi**: Semua Foreign Key wajib memiliki batasan yang jelas (misalnya `id_user` harus menunjuk `constrained('users')`).
- **Seeder**: Cek file seeder apakah data dummy sudah lengkap dan tidak memicu error saat menjalankan `php artisan db:seed`.

### B. Logical Layer (Controller & Routing)
- **Multi-tenancy (Sangat Kritis)**: Periksa apakah query database menyaring data spesifik milik pengguna yang sedang masuk. Mahasiswa **dilarang** mengambil data global tanpa pengecekan kepemilikan/role.
- **Validasi**: Input request wajib divalidasi menggunakan Request Class (bukan validasi manual di dalam logika controller).
- **Inertia Response**: Pastikan controller mereturn respon Inertia dengan benar dan menyertakan props yang dibutuhkan oleh React Component.

### C. View Layer (React & Inertia Frontend)
- **Single Page Application (SPA)**: Pindah halaman wajib menggunakan komponen `<Link>` dari `@inertiajs/react`. Periksa jika ada mahasiswa yang menggunakan tag `<a>` biasa.
- **TypeScript & Clean Console**: Buka halaman di browser, tekan `F12` (Console) dan pastikan tidak ada warning dari React (misalnya missing `key` pada loop) serta pastikan semua props memiliki interface TypeScript.

### D. Pengujian (Testing)
- Pastikan mahasiswa menyertakan unit test/feature test menggunakan **Pest** untuk kode baru mereka di folder `tests/Feature/`.
- Jalankan test untuk memverifikasi fungsionalitas:
  ```bash
  php artisan test
  ```

---

## 4. Validasi Lintas Kelas (Integration Check)

Sesuai dokumen [Integration_Guide.md](file:///c:/xampp/htdocs/RPL-2026/docs/guidence%20rpl%202026/Integration_Guide.md), perhatikan poin-poin integrasi berikut saat mereview tugas lintas kelas:

1. **Shared Tables**:
   - Kelas G hanya boleh membaca data dari tabel `users`, `research_schemas`, dan `research_outputs`.
   - Pastikan **TIDAK ADA** perubahan migrasi dari mahasiswa Kelas G yang memodifikasi skema tabel Kelas B tersebut.
2. **Namespace & Folder**:
   - Pastikan mahasiswa Kelas G meletakkan Controller mereka di dalam subfolder spesifik seperti `app/Http/Controllers/Review/` dan file Mailable email di `app/Mail/Submission/` agar tidak menimpa file milik Kelas B.
3. **API Contracts**:
   - Jika mahasiswa mengerjakan endpoint integrasi (seperti `PublishedArticleController` dari Kelas G untuk portal publik Kelas B), verifikasi bahwa tipe dan struktur data JSON yang dikembalikan sudah sesuai dengan kebutuhan integrasi.

---

## 5. Alur Pengambilan Keputusan (Decision Flow)

| Hasil Pengecekan | Tindakan Reviewer | Penjelasan |
| :--- | :--- | :--- |
| **Lolos Semua Kriteria** (AI Approved + Pest Pass + Manual OK) | **Approve & Merge** | Merge PR ke branch `development` (bukan langsung ke `main`). |
| **Temuan Minor** (Typos, format kode kurang rapi) | **Approve** + Beri Catatan | Berikan saran perbaikan langsung pada komentar PR tetapi izinkan merge. |
| **Temuan Kritis** (Query DB di React, celah multi-tenant, melanggar Integration Guide, test fail) | **Request Changes** | Tolak sementara PR tersebut, berikan checklist perbaikan konkrit, dan minta mahasiswa melakukan perbaikan sebelum direview kembali. |

---
*Dokumen ini merupakan bagian dari standar penjaminan mutu perkuliahan RPL-2026.*
