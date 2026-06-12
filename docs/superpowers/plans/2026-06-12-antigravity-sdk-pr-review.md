# Antigravity SDK PR Reviewer Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Membangun script otomatisasi peninjauan (review) tugas mahasiswa berbasis Python yang mengambil PR aktif, melakukan analisa menggunakan Google Antigravity SDK (`gemini-3.5-flash`) secara lokal, dan memposting komentar secara interaktif ke GitHub.

**Architecture:** Script Python yang menggunakan `google-antigravity` SDK untuk pemanggilan model, `inquirer` untuk input interaktif pemilihan PR dari terminal, dan `subprocess` untuk integrasi dengan GitHub CLI (`gh`).

**Tech Stack:** Python 3, `google-antigravity`, `inquirer`, GitHub CLI (`gh`).

---

### Task 1: Setup Dependensi Python & package.json

**Files:**
- Create: [.agents/scripts/requirements.txt](file:///c:/xampp/htdocs/RPL-2026/.agents/scripts/requirements.txt)
- Modify: [package.json](file:///c:/xampp/htdocs/RPL-2026/package.json)

- [ ] **Step 1: Buat file requirements.txt di folder scripts**
  Buat file `.agents/scripts/requirements.txt` dengan isi:
  ```text
  google-antigravity
  inquirer
  ```

- [ ] **Step 2: Install dependensi Python**
  Jalankan perintah berikut di terminal:
  ```bash
  pip install -r .agents/scripts/requirements.txt
  ```
  *Verifikasi:* Pastikan paket terinstal sukses tanpa error.

- [ ] **Step 3: Edit package.json untuk mengubah command runner review:prs**
  Ubah script `"review:prs"` di `package.json` untuk menjalankan script Python.

  Ubah baris di `package.json`:
  ```json
  "review:prs": "python .agents/scripts/pr-reviewer.py"
  ```

- [ ] **Step 4: Commit**
  ```bash
  git add package.json .agents/scripts/requirements.txt
  git commit -m "chore: configure python dependencies and npm script runner"
  ```

---

### Task 2: Inisialisasi Script pr-reviewer.py & Integrasi GitHub CLI

**Files:**
- Create: [.agents/scripts/pr-reviewer.py](file:///c:/xampp/htdocs/RPL-2026/.agents/scripts/pr-reviewer.py)

- [ ] **Step 1: Tulis impor modul dan helper untuk shell commands**
  Buat script `.agents/scripts/pr-reviewer.py` dan tambahkan fungsi `run_command` untuk menjalankan perintah GitHub CLI (`gh`) via `subprocess`.

  Tulis kode berikut:
  ```python
  import subprocess
  import json
  import sys
  import os
  import asyncio

  def run_command(cmd):
      try:
          result = subprocess.run(cmd, shell=True, check=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True)
          return result.stdout.strip()
      except subprocess.CalledProcessError as e:
          print(f"Error running command: {cmd}")
          print(e.stderr)
          sys.exit(1)
  ```

- [ ] **Step 2: Tambahkan fungsi fetch_prs and get_pr_diff**
  Tulis fungsi untuk berinteraksi dengan GitHub CLI untuk mengambil list PR dan diff.

  Tambahkan kode berikut:
  ```python
  def fetch_prs():
      print("Mengambil daftar Pull Request aktif dari GitHub...")
      output = run_command("gh pr list --json number,title,author,headRefName")
      return json.loads(output)

  def get_pr_diff(pr_number):
      print(f"Mengambil diff untuk PR #{pr_number}...")
      return run_command(f"gh pr diff {pr_number}")
  ```

- [ ] **Step 3: Uji pemanggilan CLI sederhana**
  Tambahkan fungsi main sementara untuk menguji `fetch_prs()`.

  Tambahkan kode:
  ```python
  async def main():
      try:
          prs = fetch_prs()
          print(f"Berhasil mengambil {len(prs)} PR.")
      except Exception as e:
          print(f"Error: {e}")

  if __name__ == "__main__":
      asyncio.run(main())
  ```

- [ ] **Step 4: Jalankan verifikasi**
  Jalankan:
  ```bash
  python .agents/scripts/pr-reviewer.py
  ```
  *Expected:* Output menampilkan daftar PR aktif dan jumlahnya tanpa error.

- [ ] **Step 5: Hapus kode tes sementara dan Commit**
  Hapus kode tes di Step 3, lalu jalankan:
  ```bash
  git add .agents/scripts/pr-reviewer.py
  git commit -m "feat: initialize python pr-reviewer with github cli integrations"
  ```

---

### Task 3: Implementasi UI Interaktif & Analisis dengan Antigravity SDK

**Files:**
- Modify: [.agents/scripts/pr-reviewer.py](file:///c:/xampp/htdocs/RPL-2026/.agents/scripts/pr-reviewer.py)

- [ ] **Step 1: Implementasi multi-select prompt menggunakan inquirer**
  Tambahkan library `inquirer` untuk memilih PR.

  Tambahkan kode ke `.agents/scripts/pr-reviewer.py`:
  ```python
  import inquirer

  def select_prs(prs):
      if not prs:
          print("Tidak ada Pull Request aktif.")
          return []
      
      choices = [
          (f"#{pr['number']} - {pr['title']} (oleh @{pr['author']['login']})", pr)
          for pr in prs
      ]
      
      questions = [
          inquirer.Checkbox(
              'selected_prs',
              message="Pilih Pull Request untuk di-review (Spasi untuk memilih, Enter untuk selesai)",
              choices=choices
          )
      ]
      
      answers = inquirer.prompt(questions)
      return answers.get('selected_prs', []) if answers else []
  ```

- [ ] **Step 2: Implementasi fungsi review_pr dengan Google Antigravity SDK**
  Membaca instruksi review dari `.agents/workflows/review-instructions.md`, memanggil `google-antigravity` menggunakan model `gemini-3.5-flash`, dan menulis laporannya ke `.agents/reviews/PR-<number>-review.md`.

  Tambahkan kode ke `.agents/scripts/pr-reviewer.py`:
  ```python
  from google.antigravity import Agent, LocalAgentConfig

  async def review_pr(pr, diff):
      # Dapatkan path instruksi
      script_dir = os.path.dirname(os.path.abspath(__file__))
      root_dir = os.path.dirname(os.path.dirname(script_dir))
      instructions_path = os.path.join(root_dir, '.agents', 'workflows', 'review-instructions.md')
      
      if not os.path.exists(instructions_path):
          print(f"Warning: File panduan review tidak ditemukan di {instructions_path}. Menggunakan default.")
          instructions = "Lakukan code review secara umum pada perubahan kode ini."
      else:
          with open(instructions_path, 'r', encoding='utf-8') as f:
              instructions = f.read()

      print(f"\nMenganalisis PR #{pr['number']} dengan Antigravity SDK...")
      
      prompt = f"""
Anda adalah reviewer proyek RPL-2026. Lakukan review kode berdasarkan panduan instruksi sistem yang diberikan di bawah ini.

INFORMASI PR:
Nomor PR: #{pr['number']}
Judul: {pr['title']}
Penulis: @{pr['author']['login']}
Branch: {pr['headRefName']}

KODE DIFF YANG BERUBAH:
```diff
{diff}
```
"""
      try:
          config = LocalAgentConfig(
              model="gemini-3.5-flash",
              system_instructions=instructions
          )
          
          async with Agent(config) as agent:
              response = await agent.chat(prompt)
              review_report = await response.text()

          reviews_dir = os.path.join(root_dir, '.agents', 'reviews')
          os.makedirs(reviews_dir, exist_ok=True)
          
          report_path = os.path.join(reviews_dir, f"PR-{pr['number']}-review.md")
          with open(report_path, 'w', encoding='utf-8') as f:
              f.write(review_report)

          return report_path, review_report
      except Exception as e:
          print(f"Error saat melakukan review PR #{pr['number']}: {e}")
          return None, None
  ```

- [ ] **Step 3: Commit**
  ```bash
  git add .agents/scripts/pr-reviewer.py
  git commit -m "feat: implement interactive selection and antigravity sdk model review"
  ```

---

### Task 4: Logika Main Runner & Posting Komentar GitHub

**Files:**
- Modify: [.agents/scripts/pr-reviewer.py](file:///c:/xampp/htdocs/RPL-2026/.agents/scripts/pr-reviewer.py)

- [ ] **Step 1: Implementasi fungsi posting komentar dan alur utama main()**
  Tambahkan fungsi `post_comment` dan fungsi async `main()` untuk merangkai semua alur dari fetch, select, review, hingga comment.

  Tambahkan kode berikut ke `.agents/scripts/pr-reviewer.py`:
  ```python
  def post_comment(pr_number, report_path):
      print(f"Memposting komentar review ke PR #{pr_number} di GitHub...")
      run_command(f'gh pr comment {pr_number} --body-file "{report_path}"')
      print(f"✔ Komentar berhasil diposting ke PR #{pr_number}!")

  async def main():
      print("=== RPL-2026 Hybrid PR Reviewer (Python & Antigravity SDK) ===")
      try:
          prs = fetch_prs()
          selected = select_prs(prs)
          
          if not selected:
              print("Tidak ada PR yang dipilih. Keluar.")
              return

          for pr in selected:
              print(f"\n--------------------------------------------")
              print(f"Memproses PR #{pr['number']}: \"{pr['title']}\"")
              print(f"--------------------------------------------")
              
              diff = get_pr_diff(pr['number'])
              if not diff:
                  print(f"PR #{pr['number']} tidak memiliki perubahan kode.")
                  continue
                  
              report_path, report_content = await review_pr(pr, diff)
              if not report_path:
                  continue
                  
              print(f"✔ Review selesai. Laporan disimpan ke: {report_path}")
              
              # Cek keputusan AI
              decision = "TIDAK TERDETEKSI"
              if "APPROVED" in report_content:
                  decision = "✅ APPROVED"
              elif "REQUEST CHANGES" in report_content:
                  decision = "❌ REQUEST CHANGES"
              print(f"Hasil Keputusan AI: {decision}")
              
              # Konfirmasi posting
              questions = [
                  inquirer.Confirm(
                      'post',
                      message=f"Apakah Anda ingin memposting komentar review ke GitHub PR #{pr['number']}?",
                      default=False
                  )
              ]
              confirm = inquirer.prompt(questions)
              
              if confirm and confirm.get('post'):
                  post_comment(pr['number'], report_path)
              else:
                  print("Review disimpan di lokal saja.")
                  
          print("\n✔ Semua proses review PR selesai.")
      except Exception as e:
          print(f"Fatal Error: {e}")

  if __name__ == "__main__":
      asyncio.run(main())
  ```

- [ ] **Step 2: Uji coba jalan script secara utuh**
  Jalankan script:
  ```bash
  python .agents/scripts/pr-reviewer.py
  ```
  *Expected:* Program dapat berjalan dari mengambil daftar PR hingga input interaktif pemilihan PR.

- [ ] **Step 3: Commit**
  ```bash
  git add .agents/scripts/pr-reviewer.py
  git commit -m "feat: complete pr-reviewer.py main execution flow and github posting"
  ```
