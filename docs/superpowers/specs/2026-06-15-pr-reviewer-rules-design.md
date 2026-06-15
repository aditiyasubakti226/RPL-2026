# Design Specification: PR Reviewer Rules & Skill Integration

This specification details the creation of the PR review rules file at `.agents/rules/review-instructions.md` and the updates to the PR Reviewer Skill at `.agents/skills/pr-reviewer/SKILL.md`.

## Goal
To implement a robust code review workflow for the RPL-2026 integrated submission system project (`jurnal_mu`), ensuring:
1. All PRs target the correct branch (`development`).
2. PR authors are identified by their real names matching the class assignment documents.
3. Code quality meets project-specific security, multi-tenancy, architecture, linting, and testing guidelines.

---

## Proposed Changes

### 1. New Rule File: `.agents/rules/review-instructions.md`
This file will serve as the source of truth for PR reviews. It defines rules in the following areas:

- **Administrative Validations**:
  - **Target Branch**: Must be `development`. If it is any other branch (such as `main`), the review verdict MUST be **Request Changes**, with a recommendation to retarget the PR to `development`.
  - **Author Identification**: Match the PR author's GitHub username against `docs/guidence rpl 2026/Penugasan_RPL_Kelas_B.md` and `docs/guidence rpl 2026/Penugasan_RPL_Kelas_G.md` to resolve their full name. Display the resolved name and GitHub username at the top of the review.

- **Security & Multi-Tenant Data Isolation**:
  - Ensure all database queries by campus admins or general users are scoped using `university_id` or `user_id` to prevent cross-tenant data leakage.
  - Verify that Controllers call authorization policies (e.g. `$this->authorize()`) and routes use role-based middleware.

- **Architecture & Conventions**:
  - Validate controller namespaces (`App\Http\Controllers\{Role}`) and React page paths (`resources/js/pages/{Role}/{Resource}/{Action}.tsx`).
  - Check correct usage of Inertia's `useForm` (handling processing, errors, and resets).
  - Verify soft deletes are implemented on key models.

- **Linting & Code Quality**:
  - Enforce code formatting standard checks (Laravel Pint, ESLint, Prettier).
  - Check for appropriate test coverage (Pest for feature/unit, Dusk for browser-based critical flows).

- **Output Report Template**:
  - Define a strict Markdown structure for the generated review comments.

---

### 2. Modifying Skill File: `.agents/skills/pr-reviewer/SKILL.md`
We will update the execution steps to support the rule file:

- **Path Update**: Change the rule path search from `.agents/workflows/review-instructions.md` to `.agents/rules/review-instructions.md`.
- **PR Metadata Retrieval**: Update step 2 to fetch PR metadata (base branch name and author) in addition to the code diff:
  ```bash
  gh pr view <PR_NUMBER> --json baseRefName,author,title,body
  gh pr diff <PR_NUMBER>
  ```
- **Context Injection**: Instruct the agent to read the assignment documents:
  - `docs/guidence rpl 2026/Penugasan_RPL_Kelas_B.md`
  - `docs/guidence rpl 2026/Penugasan_RPL_Kelas_G.md`

---

## Verification Plan

### Manual Verification
- Simulate triggering the `pr-reviewer` skill on a dummy or existing PR number.
- Verify that the agent successfully fetches the PR metadata (base branch and author), resolves the author's real name from the assignment docs, validates the target branch, and conducts the review using the newly defined rules.
