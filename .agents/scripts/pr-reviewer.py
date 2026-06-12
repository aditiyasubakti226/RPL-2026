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

def fetch_prs():
    print("Mengambil daftar Pull Request aktif dari GitHub...")
    output = run_command("gh pr list --json number,title,author,headRefName")
    return json.loads(output)

def get_pr_diff(pr_number):
    print(f"Mengambil diff untuk PR #{pr_number}...")
    return run_command(f"gh pr diff {pr_number}")
