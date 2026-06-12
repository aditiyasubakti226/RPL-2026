# Design Spec: Hybrid PR Review Workflow using Antigravity SDK

**Date:** 2026-06-12  
**Status:** Under Review  
**Target File:** `.agents/scripts/pr-reviewer.py`

---

## 1. Overview
This design implements a Python-based Hybrid PR Review workflow using the Google Antigravity SDK. It is a modification of the original Node.js design to run programmatically with the local Antigravity environment, leveraging the `gemini-3.5-flash` model.

The Python script will:
1. Fetch active Pull Requests (PR) from GitHub via `gh pr list`.
2. Allow interactive selection of PRs using the Python `inquirer` library.
3. Fetch the diff for each selected PR via `gh pr diff`.
4. Perform automatic code review using the Google Antigravity SDK and the `gemini-3.5-flash` model, using instructions defined in `.agents/workflows/review-instructions.md` as context.
5. Save the generated review reports locally under `.agents/reviews/PR-<number>-review.md`.
6. Ask for confirmation to post the review comment to the GitHub PR using `gh pr comment`.

---

## 2. Directory Structure & Dependencies

The following files will be added/modified:
* [NEW] `.agents/scripts/pr-reviewer.py` — Main Python review script.
* [MODIFY] `package.json` — Update the npm script to run the Python script:
  ```json
  "scripts": {
      "review:prs": "python .agents/scripts/pr-reviewer.py"
  }
  ```

### Dependencies:
* Python 3.x
* `google-antigravity` (Official SDK)
* `inquirer` (Terminal prompting)

---

## 3. Logic Flow

### A. Load Configuration & SDK
1. Script initializes Google Antigravity SDK client.
2. Model is set to `gemini-3.5-flash`.

### B. Fetch Active PRs
Runs the following command:
```bash
gh pr list --json number,title,author,headRefName
```
Parses the output JSON to display choices.

### C. Interactive Choice
Uses `inquirer.Checkbox` to present active PRs:
Format: `[#number] title (by @author)`

### D. Loop Review
For each selected PR:
1. Fetch diff: `gh pr diff <number>`
2. Read system instructions from `.agents/workflows/review-instructions.md` (if it does not exist, use a default fallback or show a message).
3. Send diff to Antigravity SDK:
   - System instructions: content of `review-instructions.md`.
   - Prompt: PR details and diff content.
4. Save Markdown report locally to `.agents/reviews/PR-<number>-review.md`.
5. Display decision summary (`✅ APPROVED` or `❌ REQUEST CHANGES`).
6. Prompt: *"Apakah Anda ingin memposting komentar review ini ke GitHub PR #ID? (y/N)"*
   - If yes, execute: `gh pr comment <number> --body-file .agents/reviews/PR-<number>-review.md`

---

## 4. Verification Plan
1. Check execution with active PRs.
2. Verify local markdown files save successfully under `.agents/reviews/`.
3. Verify git comment action commands work.
