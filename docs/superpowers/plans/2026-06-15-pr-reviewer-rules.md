# PR Reviewer Rules & Integration Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Create a PR review rules file at `.agents/rules/review-instructions.md` containing strict guidelines for code reviews (target branch checks, student author matching, multi-tenant isolation, Laravel/Inertia conventions) and update the PR Reviewer Skill at `.agents/skills/pr-reviewer/SKILL.md` to support this workflow.

**Architecture:** We will create a comprehensive markdown review guide. We will update the skill file to fetch PR target branches and author usernames using the GitHub CLI (`gh pr view`), instructing the reviewing agent to match names in `docs/guidence rpl 2026/Penugasan_RPL_Kelas_B.md` and `docs/guidence rpl 2026/Penugasan_RPL_Kelas_G.md` and enforce target branch checks.

**Tech Stack:** Markdown, Bash (GitHub CLI).

---

### Task 1: Create the Review Rule File

**Files:**
- Create: `.agents/rules/review-instructions.md`

- [ ] **Step 1: Write the content of the review instructions**
  Create the file `.agents/rules/review-instructions.md` with complete and non-placeholder review criteria.

  ```markdown
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

  ## 6. Output Review Report Template
  All review reports must be formatted in Markdown using this exact template:
  ```markdown
  # PR Review Report: PR #<PR_NUMBER> - <PR_TITLE>
  **Author:** <Real Name> (@<GitHub Username>)
  **Target Branch:** <Base Branch> (Verdict: <PASS/FAIL - TARGET BRANCH MUST BE development>)

  ---

  ## 📊 Verdict
  * **Status:** <APPROVED | REQUEST_CHANGES | COMMENT>
  * **Summary:** <A concise 2-3 sentence summary of the PR and review outcome.>

  ## ⚠️ Critical Issues / Security (Multi-Tenancy)
  - <Detail any authorization bypasses, multi-tenancy leakage, or major logic bugs. If none, write "None found.">

  ## 🛠️ Architecture & Code Standards
  - <Detail any violations of naming conventions, namespaces, JSDoc headers, or Inertia form patterns. If none, write "Complies with standards.">

  ## 🧪 Testing & Code Quality
  - <Detail any lacking test coverage or issues with linting. If none, write "All checks pass.">

  ## 💡 Recommendations & Actions
  - <Bullet points of actionable recommendations, including branch retargeting if needed.>
  ```
  ```

- [ ] **Step 2: Commit the created review rule file**
  Run:
  ```bash
  git add .agents/rules/review-instructions.md
  git commit -m "feat: add review instructions rules for pr-reviewer"
  ```

---

### Task 2: Update the PR Reviewer Skill File

**Files:**
- Modify: `.agents/skills/pr-reviewer/SKILL.md`

- [ ] **Step 1: Modify path references and metadata command in SKILL.md**
  Open `.agents/skills/pr-reviewer/SKILL.md` and replace the path of the review rules and update the `gh pr diff` step to also query metadata.

  Change target contents:
  ```markdown
  2. **Get PR Code Changes**:
     - Run: `gh pr diff <PR_NUMBER>` to extract the code changes.

  3. **Read Review Guidelines**:
     - Read the project's review rules from: `.agents/workflows/review-instructions.md` (if the file doesn't exist, fall back to general code review focusing on Laravel/PHP/React/Tailwind best practices, security, and logical bugs).
  ```

  With:
  ```markdown
  2. **Get PR Metadata & Code Changes**:
     - Run: `gh pr view <PR_NUMBER> --json baseRefName,author,title,body` to extract PR target branch and author details.
     - Run: `gh pr diff <PR_NUMBER>` to extract the code changes.

  3. **Read Review Guidelines**:
     - Read the project's review rules from: `.agents/rules/review-instructions.md` (if the file doesn't exist, fall back to general code review focusing on Laravel/PHP/React/Tailwind best practices, security, and logical bugs).
     - In addition, read the class assignment documents to map the author's GitHub username to their real name:
       - `docs/guidence rpl 2026/Penugasan_RPL_Kelas_B.md`
       - `docs/guidence rpl 2026/Penugasan_RPL_Kelas_G.md`
  ```

- [ ] **Step 2: Commit the modified SKILL.md file**
  Run:
  ```bash
  git add .agents/skills/pr-reviewer/SKILL.md
  git commit -m "feat: update pr-reviewer skill to fetch metadata and use new rules path"
  ```
