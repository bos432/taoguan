# admin-next 与 uni-app 联动状态总检

## 入口

在项目根目录执行：

```powershell
E:\2025\重庆分公司\涛冠\2026第二版本\local\check-linkage-readiness.cmd
```

或：

```powershell
powershell -ExecutionPolicy Bypass -File E:\2025\重庆分公司\涛冠\2026第二版本\local\check-linkage-readiness.ps1
```

执行后会在以下位置写入最新检查快照：

- [latest.json](E:\2025\重庆分公司\涛冠\2026第二版本\runtime\linkage-readiness\latest.json)
- [latest.md](E:\2025\重庆分公司\涛冠\2026第二版本\runtime\linkage-readiness\latest.md)

适合把当前联调准备度、发布前阻塞项和明细输出留档。

如果要直接跑“联动发布前预检”，可执行：

```powershell
E:\2025\重庆分公司\涛冠\2026第二版本\local\run-linkage-release-preflight.cmd prod
E:\2025\重庆分公司\涛冠\2026第二版本\local\run-linkage-release-preflight.cmd test
E:\2025\重庆分公司\涛冠\2026第二版本\local\run-linkage-release-preflight.cmd gray
```

对应报告会输出到：

- `runtime/linkage-release-preflight/prod.latest.md`
- `runtime/linkage-release-preflight/test.latest.md`
- `runtime/linkage-release-preflight/gray.latest.md`

如果只想在项目根目录操作 `uni-app` 环境和预检，可直接执行：

```powershell
E:\2025\重庆分公司\涛冠\2026第二版本\local\uni-env-status.cmd
E:\2025\重庆分公司\涛冠\2026第二版本\local\run-uni-isolation-audit.cmd
E:\2025\重庆分公司\涛冠\2026第二版本\local\uni-env-set.cmd test https://test.your-domain.com https://test.your-domain.com/api 测试环境
E:\2025\重庆分公司\涛冠\2026第二版本\local\uni-env-set.cmd gray https://gray.your-domain.com https://gray.your-domain.com/api 灰度环境
E:\2025\重庆分公司\涛冠\2026第二版本\local\run-uni-release-preflight.cmd test
E:\2025\重庆分公司\涛冠\2026第二版本\local\run-uni-release-preflight.cmd gray
E:\2025\重庆分公司\涛冠\2026第二版本\local\run-uni-release-preflight.cmd prod
```

如果要把“查看环境 -> 可选写入地址 -> 单体预检 -> 联动预检 -> 打开 HBuilderX”串成一次动作，可执行：

```powershell
E:\2025\重庆分公司\涛冠\2026第二版本\local\prepare-uniapp-release.ps1 -Profile prod
E:\2025\重庆分公司\涛冠\2026第二版本\local\prepare-uniapp-release.ps1 -Profile gray -BaseUrl https://gray.your-domain.com -ApiUrl https://gray.your-domain.com/api -Label 灰度环境
E:\2025\重庆分公司\涛冠\2026第二版本\local\prepare-uniapp-release.ps1 -Profile test -BaseUrl https://test.your-domain.com -ApiUrl https://test.your-domain.com/api -Label 测试环境 -OpenProject
```

说明：
- 不传 `BaseUrl / ApiUrl` 时，只做状态查看与预检
- 传入 `BaseUrl / ApiUrl` 时，会先写入 `env.profile.local.json`
- `-OpenProject` 会在通过前置动作后打开 `HBuilderX`

如果要从根目录生成一份“当前能否进入发布动作”的总览报告，可执行：

```powershell
E:\2025\重庆分公司\涛冠\2026第二版本\local\build-release-cockpit.cmd prod
E:\2025\重庆分公司\涛冠\2026第二版本\local\build-release-cockpit.cmd gray
E:\2025\重庆分公司\涛冠\2026第二版本\local\build-release-cockpit.cmd test
```

对应报告会输出到：

- `runtime/release-cockpit/prod.latest.md`
- `runtime/release-cockpit/gray.latest.md`
- `runtime/release-cockpit/test.latest.md`

如果想直接打开最近的报告文件，可执行：

```powershell
E:\2025\重庆分公司\涛冠\2026第二版本\local\open-latest-report.cmd linkage
E:\2025\重庆分公司\涛冠\2026第二版本\local\open-latest-report.cmd linkage-preflight gray
E:\2025\重庆分公司\涛冠\2026第二版本\local\open-latest-report.cmd uni-preflight prod
E:\2025\重庆分公司\涛冠\2026第二版本\local\open-latest-report.cmd cockpit prod
E:\2025\重庆分公司\涛冠\2026第二版本\local\open-latest-report.cmd brief
```

如果要在根目录直接生成本次发布记录草稿，可执行：

```powershell
E:\2025\重庆分公司\涛冠\2026第二版本\local\create-release-record.cmd gray
E:\2025\重庆分公司\涛冠\2026第二版本\local\create-release-record.cmd prod
E:\2025\重庆分公司\涛冠\2026第二版本\local\create-release-record.cmd rollback
```

如果只想快速生成当前发布摘要，可执行：

```powershell
E:\2025\重庆分公司\涛冠\2026第二版本\local\build-release-brief.cmd
E:\2025\重庆分公司\涛冠\2026第二版本\local\refresh-release-brief.cmd
```

如果要刷新并查看一页式发布看板，可执行：

```powershell
E:\2025\重庆分公司\涛冠\2026第二版本\local\refresh-release-dashboard.cmd
E:\2025\重庆分公司\涛冠\2026第二版本\local\open-release-dashboard.cmd
```

如果要按正式流程串行执行发布准备，可执行：

```powershell
E:\2025\重庆分公司\涛冠\2026第二版本\local\run-release-flow.ps1 -Profile gray -BaseUrl https://gray.your-domain.com -ApiUrl https://gray.your-domain.com/api -Label Gray -OpenProject
E:\2025\重庆分公司\涛冠\2026第二版本\local\run-release-flow.ps1 -Profile prod -OpenProject
```

如果要导出当前最新的发布交接包，可执行：

```powershell
E:\2025\重庆分公司\涛冠\2026第二版本\local\export-release-handoff.cmd
E:\2025\重庆分公司\涛冠\2026第二版本\local\export-release-handoff.cmd gray-ready
E:\2025\重庆分公司\涛冠\2026第二版本\local\archive-release-handoff.cmd
E:\2025\重庆分公司\涛冠\2026第二版本\local\open-release-handoff.cmd dir
E:\2025\重庆分公司\涛冠\2026第二版本\local\open-release-handoff.cmd zip
E:\2025\重庆分公司\涛冠\2026第二版本\local\verify-release-handoff.cmd
E:\2025\重庆分公司\涛冠\2026第二版本\local\prune-release-handoffs.cmd 5
```

如果要一键刷新当前摘要/看板并导出交接快照，可执行：

```powershell
E:\2025\重庆分公司\涛冠\2026第二版本\local\refresh-and-export-release.cmd
E:\2025\重庆分公司\涛冠\2026第二版本\local\refresh-and-export-release.cmd daily-sync
```

如果想直接知道当前下一步最该执行什么命令，可执行：

```powershell
E:\2025\重庆分公司\涛冠\2026第二版本\local\suggest-next-release-step.cmd
```

如果想直接做一轮发布诊断，可执行：

```powershell
E:\2025\重庆分公司\涛冠\2026第二版本\local\release-doctor.cmd
```

如果只想快速看当前状态，不重跑整套检查，可执行：

```powershell
E:\2025\重庆分公司\涛冠\2026第二版本\local\release-pulse.cmd
```

如果想看当前报告和交接包是否还是最新的，可执行：

```powershell
E:\2025\重庆分公司\涛冠\2026第二版本\local\release-freshness.cmd
E:\2025\重庆分公司\涛冠\2026第二版本\local\release-freshness.cmd 60
E:\2025\重庆分公司\涛冠\2026第二版本\local\build-frontend-delivery-manifest.cmd
E:\2025\重庆分公司\涛冠\2026第二版本\local\build-admin-next-coverage.cmd
E:\2025\重庆分公司\涛冠\2026第二版本\local\build-admin-next-focus.cmd
```

## 当前会检查的项目

- `admin-next` 本地后端与登录探活
- `admin-next` 本地构建环境校验
- `admin-next` 本地候选包是否存在
- `admin-next` 线上构建包是否存在
- `uni-app` 环境状态总览
- `uni-app` 环境隔离审计
- `uni-app` 正式环境发布前校验
- `uni-app` 测试环境发布前校验
- `uni-app` 灰度环境发布前校验
- `uni-app` 源码级运行准备度检查
- `admin-next + uni-app` 当前交付物指纹归档
- `admin-next` 栏目覆盖与 legacy 承接清单
- `admin-next` 会员管理 / 业务设置 / 系统管理 重点栏目细分报告

## 结果说明

- `PASS`
  说明该项已经满足当前联调或发布前要求
- `WARN`
  说明当前源码可继续开发，但还不满足测试包/灰度包正式交付条件
- `FAIL`
  说明当前存在硬阻塞，应先修复再继续后续联调或发布动作

## 当前预期

在真实 `test` / `gray` 地址尚未填入 `config/env.profile.local.json` 前：

- `admin-next` 相关检查应通过
- `uni-app runtime:readiness` 应通过，说明协议默认态、协议中心、结算页、商家申请页和环境切换等关键源码承接仍在
- `uni-app release:check:prod` 应通过
- `uni-app release:check:test` / `release:check:gray` 应继续显示 `WARN`

这属于正常状态，表示源码侧防误发守卫在生效，而不是代码故障。
