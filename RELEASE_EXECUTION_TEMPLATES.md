# Release Execution Templates

## Purpose

Use these templates when the project is ready to move from preflight into actual release execution.

They are designed for:

- `admin-next` gray release
- `uni-app` gray release
- `admin-next + uni-app` formal release
- rollback recording

## Gray Release Card

```md
# Gray Release Card

- Release Window:
- Operator:
- Reviewer:
- Target Profile: gray
- Target Scope: admin-next / uni-app / both

## Pre-check

- [ ] `local\build-release-cockpit.cmd gray` is green
- [ ] Real gray base/api URLs are already filled
- [ ] Gray H5 directory is isolated from formal entry
- [ ] Mini-program release target is experience version only
- [ ] Rollback path is confirmed

## Execution

- [ ] Run `local\prepare-uniapp-release.ps1 -Profile gray -OpenProject`
- [ ] Build gray H5 package in HBuilderX
- [ ] Build gray mini-program experience package in HBuilderX
- [ ] Build admin-next online candidate if needed
- [ ] Deploy only to gray path / gray entry

## Verification

- [ ] Login agreement is unchecked by default
- [ ] Login is blocked when agreement is unchecked
- [ ] Environment label is gray
- [ ] Goods flow / order flow / merchant apply flow are available
- [ ] No write request points to prod host

## Result

- Result:
- Issue Summary:
- Next Step:
```

## Formal Release Card

```md
# Formal Release Card

- Release Window:
- Operator:
- Reviewer:
- Target Profile: prod
- Target Scope: admin-next / uni-app / both

## Pre-check

- [ ] Gray release has already passed
- [ ] `local\build-release-cockpit.cmd prod` is green
- [ ] Formal domain and package target are confirmed
- [ ] Rollback package and path are confirmed
- [ ] No pending blocker remains in latest reports

## Execution

- [ ] Run `local\prepare-uniapp-release.ps1 -Profile prod -OpenProject`
- [ ] Build formal H5 package in HBuilderX
- [ ] Build formal mini-program package in HBuilderX
- [ ] Build `admin-next` online artifact
- [ ] Deploy only approved formal artifacts

## Verification

- [ ] Login is available
- [ ] Agreement default state is correct
- [ ] Menu / merchant / analytics / exports are available
- [ ] Goods / cart / settlement / order / merchant apply are available
- [ ] Formal environment label is correct

## Result

- Result:
- Issue Summary:
- Next Step:
```

## Rollback Record

```md
# Rollback Record

- Trigger Time:
- Triggered By:
- Release Scope:
- Trigger Reason:

## Rollback Action

- [ ] Restore admin-next backup
- [ ] Restore previous H5 entry
- [ ] Stop gray/formal exposure if needed
- [ ] Recheck login
- [ ] Recheck menu / goods / order / merchant flow

## Final Status

- Final Status:
- Remaining Issue:
- Follow-up Owner:
```

## Suggested Usage

1. Generate a fresh release note from the root helper script.
2. Fill operator, window, and target.
3. Run preflight commands before each real deployment step.
4. Keep the finished markdown file under `runtime/release-records/`.
