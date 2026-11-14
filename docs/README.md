# BizStock documentation index

New documentation follows the purpose-based placement rules in the repository-root [AGENTS.md](../AGENTS.md), for every format. Existing flat documents remain linked here; this operational upgrade does not reorganize them.

| Purpose | Future location |
|---|---|
| Setup, configuration, startup | `docs/setup/` |
| User and administrator guides | `docs/guides/` |
| Requirements, design, API explanations | `docs/architecture/` |
| Test plans and results | `docs/testing/` |
| Audits and implementation completion reports | `docs/reviews/` |
| Backup, recovery, upgrade, rollback procedures and execution records | `docs/operations/` |
| Superseded checkpoints | `docs/history/` |
| Sanitized retained screenshots and supporting evidence | `docs/evidence/` |

Functional source/configuration, migration SQL, fixtures and tool-consumed inputs keep their required locations. Private backups, configuration secrets, runtime data and temporary output do not belong in documentation folders. Prefer relevant existing documents over duplicates.

## Current operational work

- [Current UI review and page decisions](visual-review.md#live-workspace-ui-refinement---2026-11-14), [before/after viewer](evidence/workspace-ui/index.html), [verification evidence](evidence/workspace-ui/verification.json) and [browser/accessibility results](testing/workspace-ui-results.json).

- [Sanitized installed upgrade evidence](evidence/2026-11-14-phase345-upgrade.json); upgrade completed and application running after owner-confirmed authentication activity resolved the temporary hold.

- [Installed Phase 3–5 upgrade execution record](operations/2026-11-14-installed-phase345-upgrade.md).
- [Phase 3–5 implementation and upgrade procedure](phase345-upgrade.md).
- [Progress](progress.md) and [gap register](gaps.md).

## Existing references retained in place

- [Build blueprint](BizStock-Codex-Build-Blueprint.md), [requirements](requirements.md), [decisions](decisions.md), [architecture](architecture.md), [schema](schema.md), [API contract](openapi.yaml).
- [Database compatibility](database-compatibility.md), [deployment/operations](deployment.md), [database recovery](database-recovery.md).
- [User guide](user-guide.md), [test plan/results](test-plan.md), [participant testing](user-testing.md), [Phase 1–2 review](phase12-improvements.md).
