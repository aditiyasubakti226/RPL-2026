import subprocess
import json
import sys
import os
import asyncio
import inquirer
from google.antigravity import Agent, LocalAgentConfig

def run_command(cmd):
    try:
        result = subprocess.run(cmd, shell=True, check=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True)
        return result.stdout.strip()
    except subprocess.CalledProcessError as e:
        print(f"Error running command: {cmd}")
        print(e.stderr)
        sys.exit(1)

def fetch_prs():
    print("Mengambil daftar Pull Request aktif dari GitHub...")
    output = run_command("gh pr list --json number,title,author,headRefName")
    return json.loads(output)

def get_pr_diff(pr_number):
    print(f"Mengambil diff untuk PR #{pr_number}...")
    return run_command(f"gh pr diff {pr_number}")

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

