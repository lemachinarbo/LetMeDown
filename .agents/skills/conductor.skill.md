# Conductor Finalization Skill

This skill automates **Step 4** of the Conductor Protocol: Verifying and merging Jules' work.

## Prerequisites
- Jules has completed the track implementation.
- User has clicked **"Publish PR"** in the Jules UI.
- A PR exists on GitHub.

## Workflow

### 1. Verification
- Pull the PR branch: `gh pr checkout <ID>`.
- Read the corresponding `conductor-jules/tracks/ID/plan.md`.
- Run tests: `ddev composer test`.
- Verify that every task in the `plan.md` has been implemented correctly in the code.

### 2. Sanitization
- Ensure no emojis or "bot-style" comments were introduced.
- Verify the PR description matches `.github/PULL_REQUEST_TEMPLATE.md`.

### 3. Merging
- Squash and merge the PR: `gh pr merge --squash`.
- Clean up the local and remote branches.

### 4. Record Keeping
- Update `conductor-jules/tracks.md` to mark the track as completed.
- Update `AGENTS.md` if any architecture changes occurred.
- Push the updated context to `master`.
