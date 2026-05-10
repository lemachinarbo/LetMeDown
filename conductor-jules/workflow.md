# Workflow & Contribution Standards

## Git Strategy
- **Linear History**: No merge commits. Always squash or rebase.
- **Commit Format**: Conventional Commits (strictly lowercase subject, imperative mood).
- **No Emojis**: Strictly forbidden in commits, PRs, or code.
- **No Auto-Commit**: Agents must propose commits for user approval.

## PR Standards
- **Template**: Use `.github/PULL_REQUEST_TEMPLATE.md`.
- **Title**: `type: brief description` (lowercase, no trailing period).

## Release Workflow
- Automated by **Release Please**.
- Versioning is driven by Conventional Commits.
- PRs are created on push to `main` for manual approval.

## Quality Control
- **TDD Preferred**: Update tests in the same change as logic.
- **Validation**: Run focused tests before the full suite on high-risk edits.
