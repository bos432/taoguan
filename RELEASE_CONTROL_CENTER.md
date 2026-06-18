# Release Control Center

## Goal

This file is the root entry for the current `admin-next + uni-app` release workflow.

It helps answer three questions quickly:

1. Is `admin-next` locally ready?
2. Is `uni-app` source-side release protection ready?
3. Can we enter `test / gray / prod` packaging now without risking live data?

## Current Facts

- `admin-next` local stack, local candidate build, and online build artifact are available.
- `uni-app` login agreement default remains unchecked.
- `uni-app` runtime readiness checks pass.
- `uni-app prod` preflight passes.
- `uni-app test / gray` are still blocked only because real environment URLs have not been filled in `config/env.profile.local.json`.

## Root Commands

### 1. Linkage readiness

```powershell
E:\2025\重庆分公司\涛冠\2026第二版本\local\check-linkage-readiness.cmd
```

Use this to verify current `admin-next + uni-app` linkage readiness.

### 2. Uni-app env status

```powershell
E:\2025\重庆分公司\涛冠\2026第二版本\local\uni-env-status.cmd
```

Use this to inspect current `test / gray / prod` env mapping.

### 3. Fill env address

```powershell
E:\2025\重庆分公司\涛冠\2026第二版本\local\uni-env-set.cmd test https://test.your-domain.com https://test.your-domain.com/api Test
E:\2025\重庆分公司\涛冠\2026第二版本\local\uni-env-set.cmd gray https://gray.your-domain.com https://gray.your-domain.com/api Gray
```

Use this only after real isolated environment URLs are confirmed.

### 4. Uni-app preflight

```powershell
E:\2025\重庆分公司\涛冠\2026第二版本\local\run-uni-release-preflight.cmd prod
E:\2025\重庆分公司\涛冠\2026第二版本\local\run-uni-release-preflight.cmd test
E:\2025\重庆分公司\涛冠\2026第二版本\local\run-uni-release-preflight.cmd gray
```

### 4.1 Uni-app env isolation audit

```powershell
E:\2025\重庆分公司\涛冠\2026第二版本\local\run-uni-isolation-audit.cmd
```

Use this to confirm `test / gray` do not overlap with `prod`, and to detect whether `test / gray` are still placeholders or accidentally share the same domain.

### 5. Linkage preflight

```powershell
E:\2025\重庆分公司\涛冠\2026第二版本\local\run-linkage-release-preflight.cmd prod
E:\2025\重庆分公司\涛冠\2026第二版本\local\run-linkage-release-preflight.cmd test
E:\2025\重庆分公司\涛冠\2026第二版本\local\run-linkage-release-preflight.cmd gray
```

### 6. One-shot uni-app release prep

```powershell
E:\2025\重庆分公司\涛冠\2026第二版本\local\prepare-uniapp-release.ps1 -Profile prod
E:\2025\重庆分公司\涛冠\2026第二版本\local\prepare-uniapp-release.ps1 -Profile gray -BaseUrl https://gray.your-domain.com -ApiUrl https://gray.your-domain.com/api -Label Gray -OpenProject
```

### 7. Release cockpit

```powershell
E:\2025\重庆分公司\涛冠\2026第二版本\local\build-release-cockpit.cmd prod
E:\2025\重庆分公司\涛冠\2026第二版本\local\build-release-cockpit.cmd gray
E:\2025\重庆分公司\涛冠\2026第二版本\local\build-release-cockpit.cmd test
```

This is the best “can we move forward now?” summary command.

### 8. Open latest reports

```powershell
E:\2025\重庆分公司\涛冠\2026第二版本\local\open-latest-report.cmd cockpit prod
E:\2025\重庆分公司\涛冠\2026第二版本\local\open-latest-report.cmd cockpit gray
E:\2025\重庆分公司\涛冠\2026第二版本\local\open-latest-report.cmd linkage
E:\2025\重庆分公司\涛冠\2026第二版本\local\open-latest-report.cmd brief
```

### 9. Create release record

```powershell
E:\2025\重庆分公司\涛冠\2026第二版本\local\create-release-record.cmd gray
E:\2025\重庆分公司\涛冠\2026第二版本\local\create-release-record.cmd prod
E:\2025\重庆分公司\涛冠\2026第二版本\local\create-release-record.cmd rollback
```

Use this to create an execution note before gray release, formal release, or rollback.

### 10. Build release brief

```powershell
E:\2025\重庆分公司\涛冠\2026第二版本\local\build-release-brief.cmd
E:\2025\重庆分公司\涛冠\2026第二版本\local\refresh-release-brief.cmd
```

Use this when you want a one-page summary of current blockers and next actions.
`refresh-release-brief.cmd` will rebuild the summary and open it immediately.

### 11. Refresh release dashboard

```powershell
E:\2025\重庆分公司\涛冠\2026第二版本\local\refresh-release-dashboard.cmd
E:\2025\重庆分公司\涛冠\2026第二版本\local\open-release-dashboard.cmd
```

Use this when you want one page that indexes the latest brief, cockpit, and preflight reports.

### 12. Run release flow

```powershell
E:\2025\重庆分公司\涛冠\2026第二版本\local\run-release-flow.ps1 -Profile gray -BaseUrl https://gray.your-domain.com -ApiUrl https://gray.your-domain.com/api -Label Gray -OpenProject
E:\2025\重庆分公司\涛冠\2026第二版本\local\run-release-flow.ps1 -Profile prod -OpenProject
```

Use this when you want one command to chain env setup, preflight, release record, and dashboard refresh.

### 13. Export release handoff

```powershell
E:\2025\重庆分公司\涛冠\2026第二版本\local\export-release-handoff.cmd
E:\2025\重庆分公司\涛冠\2026第二版本\local\export-release-handoff.cmd gray-ready
E:\2025\重庆分公司\涛冠\2026第二版本\local\archive-release-handoff.cmd
E:\2025\重庆分公司\涛冠\2026第二版本\local\open-release-handoff.cmd dir
E:\2025\重庆分公司\涛冠\2026第二版本\local\open-release-handoff.cmd zip
E:\2025\重庆分公司\涛冠\2026第二版本\local\verify-release-handoff.cmd
E:\2025\重庆分公司\涛冠\2026第二版本\local\prune-release-handoffs.cmd 5
```

Use this when you want a timestamped bundle of the latest reports, templates, env snapshot, and release records.

### 14. Refresh and export snapshot

```powershell
E:\2025\重庆分公司\涛冠\2026第二版本\local\refresh-and-export-release.cmd
E:\2025\重庆分公司\涛冠\2026第二版本\local\refresh-and-export-release.cmd daily-sync
```

Use this when you want one command to rebuild the latest summary/dashboard and export a fresh handoff bundle.

### 15. Suggest next step

```powershell
E:\2025\重庆分公司\涛冠\2026第二版本\local\suggest-next-release-step.cmd
```

Use this when you want the system to tell you the single most useful next command to run.

### 16. Run release doctor

```powershell
E:\2025\重庆分公司\涛冠\2026第二版本\local\release-doctor.cmd
```

Use this when you want a quick diagnosis plus the current next command.

### 17. Check release pulse

```powershell
E:\2025\重庆分公司\涛冠\2026第二版本\local\release-pulse.cmd
```

Use this when you want a fast terminal summary without rebuilding every report.

### 18. Check release freshness

```powershell
E:\2025\重庆分公司\涛冠\2026第二版本\local\release-freshness.cmd
E:\2025\重庆分公司\涛冠\2026第二版本\local\release-freshness.cmd 60
```

Use this when you want to know whether the current release reports and handoff bundle are fresh enough.

### 19. Build frontend delivery manifest

```powershell
E:\2025\重庆分公司\涛冠\2026第二版本\local\build-frontend-delivery-manifest.cmd
```

Use this to snapshot the current `admin-next` candidate, online build, and `uni-app` release reports with file hashes before gray or formal delivery.

### 20. Build admin-next coverage report

```powershell
E:\2025\重庆分公司\涛冠\2026第二版本\local\build-admin-next-coverage.cmd
```

Use this to snapshot current `admin-next` source coverage by route section, key operating modules, and legacy-carried pages.

### 21. Build admin-next focus report

```powershell
E:\2025\重庆分公司\涛冠\2026第二版本\local\build-admin-next-focus.cmd
```

Use this to snapshot current `member / setting / system` detail, including largest pages and remaining legacy-carried routes.

## Report Files

### Linkage readiness

- [latest.md](E:/2025/重庆分公司/涛冠/2026第二版本/runtime/linkage-readiness/latest.md)

### Uni-app preflight

- [prod.latest.md](E:/2025/重庆分公司/涛冠/2026第二版本/zflUniApp/zflUniApp/runtime/release-preflight/prod.latest.md)
- [test.latest.md](E:/2025/重庆分公司/涛冠/2026第二版本/zflUniApp/zflUniApp/runtime/release-preflight/test.latest.md)
- [gray.latest.md](E:/2025/重庆分公司/涛冠/2026第二版本/zflUniApp/zflUniApp/runtime/release-preflight/gray.latest.md)

### Uni-app env isolation

- [latest.md](E:/2025/重庆分公司/涛冠/2026第二版本/zflUniApp/zflUniApp/runtime/env-isolation/latest.md)

### Linkage preflight

- [prod.latest.md](E:/2025/重庆分公司/涛冠/2026第二版本/runtime/linkage-release-preflight/prod.latest.md)
- [test.latest.md](E:/2025/重庆分公司/涛冠/2026第二版本/runtime/linkage-release-preflight/test.latest.md)
- [gray.latest.md](E:/2025/重庆分公司/涛冠/2026第二版本/runtime/linkage-release-preflight/gray.latest.md)

### Release cockpit

- [prod.latest.md](E:/2025/重庆分公司/涛冠/2026第二版本/runtime/release-cockpit/prod.latest.md)
- [gray.latest.md](E:/2025/重庆分公司/涛冠/2026第二版本/runtime/release-cockpit/gray.latest.md)

### Release brief

- [latest.md](E:/2025/重庆分公司/涛冠/2026第二版本/runtime/release-brief/latest.md)

### Release dashboard

- [latest.md](E:/2025/重庆分公司/涛冠/2026第二版本/runtime/release-dashboard/latest.md)

### Frontend delivery

- [latest.md](E:/2025/重庆分公司/涛冠/2026第二版本/runtime/frontend-delivery/latest.md)

### Admin-next coverage

- [latest.md](E:/2025/重庆分公司/涛冠/2026第二版本/runtime/admin-next-coverage/latest.md)

### Admin-next focus

- [latest.md](E:/2025/重庆分公司/涛冠/2026第二版本/runtime/admin-next-focus/latest.md)

## Decision Guide

### If you want to continue local development

- Run `local\check-linkage-readiness.cmd`
- Keep `test / gray` on placeholders until real isolated envs are ready

### If you want to prepare gray release

1. Fill real `gray` base/api URLs
2. Run `local\run-uni-release-preflight.cmd gray`
3. Run `local\run-linkage-release-preflight.cmd gray`
4. Run `local\build-release-cockpit.cmd gray`
5. Open `HBuilderX` and package gray H5 / mini-program experience build

### If you want to prepare formal release

1. Confirm gray validation is already done
2. Run `local\run-uni-release-preflight.cmd prod`
3. Run `local\run-linkage-release-preflight.cmd prod`
4. Run `local\build-release-cockpit.cmd prod`
5. Open `HBuilderX` and move to final package verification

## Current Blockers

- `test` still uses example host
- `gray` still uses example host

These are expected blockers, not code regressions.

## Execution Templates

- [RELEASE_EXECUTION_TEMPLATES.md](E:/2025/重庆分公司/涛冠/2026第二版本/RELEASE_EXECUTION_TEMPLATES.md)
