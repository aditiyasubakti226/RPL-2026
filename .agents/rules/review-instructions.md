# PR Review Guidelines for JurnalMu

This document defines the rules and guidelines for reviewing Pull Requests (PRs) in the RPL-2026 Integrated Submission System (`jurnal_mu`) project.

## 1. Administrative Validations (CRITICAL)

- **Target Branch Check**:
  - Every PR must target the `development` branch.
  - If the base branch of the PR is NOT `development` (e.g., it is targeting `main`), you MUST set the review verdict to **REQUEST_CHANGES** and add a recommendation instructing the author to change the target branch to `development`.

- **Author Matching Check**:
  - Read the PR author's GitHub username.
  - Search for this username or task matching in the assignment documents:
    - `docs/guidence rpl 2026/Penugasan_RPL_Kelas_B.md`
    - `docs/guidence rpl 2026/Penugasan_RPL_Kelas_G.md`
  - Locate the student's real/full name.
  - Output the header at the top of the review:
    `**PR Review Report for:** [Real Name] (@[GitHub Username])`
  - If the student's name cannot be identified, output:
    `**PR Review Report for:** Unknown Student (@[GitHub Username])`
    `> [!WARNING]`
    `> GitHub username @[GitHub Username] was not found in the Penugasan assignment documents. Please verify the author's identity.`

## 2. Multi-Tenant Data Isolation (CRITICAL)

- **Scope Isolation**:
  - For actions taken by university administrators (Admin Kampus), check that database queries enforce isolation using `university_id` filters (e.g., using query scopes like `User::forUniversity($user->university_id)` or `->where('university_id', $user->university_id)`).
  - For general users (User/Author/Reviewer), check that queries filter by ownership (e.g., `user_id` or relations).
- **Authorization**:
  - Check that Controllers call policies (e.g., `$this->authorize()`) before retrieving or modifying data.
  - Ensure routes are grouped under the correct role middleware (e.g., `role:Admin Kampus`, `role:User`).

## 3. Laravel & Backend (PHP) Conventions

- **Namespaces & Locations**:
  - Controllers must be located under namespace directories based on their roles:
    - Admin: `app/Http/Controllers/Admin`
    - Admin Kampus: `app/Http/Controllers/AdminKampus`
    - Users/Other: `app/Http/Controllers` or appropriate directories.
- **Validation**:
  - Ensure form data is validated using dedicated Request classes (e.g., `app/Http/Requests/...`) rather than inline controller validation.
- **Code Quality**:
  - Ensure soft deletes are utilized for models that have audits (`users`, `journals`, `journal_assessments`).
  - Verify database migrations follow standard naming conventions.

## 4. Inertia.js & React Frontend (TypeScript) Conventions

- **Structure**:
  - React page components must be in `resources/js/pages/{Role}/{Resource}/{Action}.tsx`.
- **JSDoc Convention**:
  - Every page component must contain a JSDoc header at the top:
    ```typescript
    /**
     * @route [Method] /path/to/route
     * @features [List of key features]
     * @description [Short description of page purpose]
     */
    ```
- **Forms**:
  - Forms must use the Inertia `useForm` helper from `@inertiajs/react`.
  - Submit buttons must be disabled when `processing` is true.
  - Input fields must show validation errors properly using `errors.field_name`.

## 5. Testing Requirements

- **Backend Tests**: Ensure Pest tests exist for new features.
- **Browser Tests**: Ensure Laravel Dusk tests cover critical user flows.

## 6. Bahasa & Template Laporan Review
Laporan review **wajib** ditulis dalam **Bahasa Indonesia** secara profesional dan terstruktur.

Semua laporan review harus diformat dalam Markdown menggunakan template berikut:
```markdown
# Laporan Review PR: PR #<PR_NUMBER> - <PR_TITLE>
**Penulis:** <Nama Lengkap> (@<Username GitHub>)
**Target Branch:** <Target Branch> (Keputusan Target Branch: <SESUAI/TIDAK SESUAI - TARGET BRANCH HARUS development>)

---

## 📊 Keputusan (Verdict)
* **Status:** <APPROVED | REQUEST_CHANGES | COMMENT>
* **Ringkasan:** <Ringkasan singkat 2-3 kalimat mengenai PR dan hasil review.>

## ⚠️ Masalah Kritis / Keamanan (Multi-Tenancy)
- <Detail celah otorisasi, kebocoran multi-tenancy, atau bug logika utama. Jika tidak ada, tulis "Tidak ditemukan masalah kritis.">

## 🛠️ Arsitektur & Standar Kode
- <Detail pelanggaran konvensi penamaan, namespace, header JSDoc, atau pola form Inertia. Jika tidak ada, tulis "Sesuai dengan standar proyek.">

## 🧪 Pengujian & Kualitas Kode
- <Detail kurangnya cakupan pengujian (test coverage) atau masalah linting. Jika tidak ada, tulis "Semua pengujian dan pengecekan linting lolos.">

## 💡 Rekomendasi Tindakan
- <Poin-poin tindakan yang direkomendasikan untuk diperbaiki oleh penulis PR, termasuk instruksi pemindahan target branch ke development jika tidak sesuai.>
```
