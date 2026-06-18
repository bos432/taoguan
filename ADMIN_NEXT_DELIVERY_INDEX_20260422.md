# admin-next 交付总入口

## 适用范围

这份索引用于当前项目里 `admin -> admin-next` 的源码迁移、构建、验收、发布和回滚。

相关源码目录：

- 前端源码基座：
  [zflAdminWeb](E:\2025\重庆分公司\涛冠\2026第二版本\zflAdminWeb)
- 本地正式候选目录：
  [public/admin-next](E:\2025\重庆分公司\涛冠\2026第二版本\public\admin-next)
- 本地开发验收目录：
  [public/admin-next-dev](E:\2025\重庆分公司\涛冠\2026第二版本\public\admin-next-dev)

## 先看哪几份文档

1. 迁移背景和源码分析：
   [ADMIN_TO_ADMIN_NEXT_MIGRATION_20260422.md](E:\2025\重庆分公司\涛冠\2026第二版本\ADMIN_TO_ADMIN_NEXT_MIGRATION_20260422.md)
2. 本次源码审计结论：
   [SRC_AUDIT_20260422.md](E:\2025\重庆分公司\涛冠\2026第二版本\SRC_AUDIT_20260422.md)
3. 本地正式候选构建、验收、回滚说明：
   [admin-next-release-runbook.md](E:\2025\重庆分公司\涛冠\2026第二版本\zflAdminWeb\docs\admin-next-release-runbook.md)
4. 线上发布清单：
   [admin-next-online-publish-checklist.md](E:\2025\重庆分公司\涛冠\2026第二版本\zflAdminWeb\docs\admin-next-online-publish-checklist.md)
5. admin-next 与 uni-app 联调回归 / 灰度核对清单：
   [ADMIN_NEXT_UNIAPP_REGRESSION_CHECKLIST.md](E:\2025\重庆分公司\涛冠\2026第二版本\ADMIN_NEXT_UNIAPP_REGRESSION_CHECKLIST.md)
6. 周报与 PLAN 状态同步（模板 + 周记录）：
   [WEEKLY_STATUS_SYNC.md](E:\2025\重庆分公司\涛冠\2026第二版本\WEEKLY_STATUS_SYNC.md)
7. 后台迭代闸门（每迭代 build + audit）：
   [admin-next-iteration-gate.md](E:\2025\重庆分公司\涛冠\2026第二版本\zflAdminWeb\docs\admin-next-iteration-gate.md)

## 当前可直接使用的命令

在目录 [zflAdminWeb](E:\2025\重庆分公司\涛冠\2026第二版本\zflAdminWeb) 下执行：

```bash
npm run build:admin-next-dev
npm run audit:admin-next-dev
```

说明：
构建并验证 `/admin-next-dev/`。

```bash
npm run build-and-audit:admin-next-local
```

说明：
构建并验证 `/admin-next/` 本地正式候选目录。

```bash
npm run build:admin-next-online
```

说明：
生成独立线上发布包到 [dist-admin-next-online](E:\2025\重庆分公司\涛冠\2026第二版本\zflAdminWeb\dist-admin-next-online)。

## 当前状态

- `/admin-next-dev/` 已可本地测试
- `/admin-next/` 已可本地测试
- `/admin-next/` 路径已通过 7 项 Playwright 自动回归
- 线上构建模式已独立拆分，不会覆盖本地候选目录
- 旧 `public/admin-next` 已备份为：
  [admin-next-backup-20260422-122059](E:\2025\重庆分公司\涛冠\2026第二版本\public\admin-next-backup-20260422-122059)

## 当前推荐操作顺序

1. 本地验证候选版：
   `npm run build-and-audit:admin-next-local`
2. 生成线上包：
   `npm run build:admin-next-online`
3. 按发布清单执行上线：
   参考 [admin-next-online-publish-checklist.md](E:\2025\重庆分公司\涛冠\2026第二版本\zflAdminWeb\docs\admin-next-online-publish-checklist.md)
4. 如需回滚：
   参考 [admin-next-release-runbook.md](E:\2025\重庆分公司\涛冠\2026第二版本\zflAdminWeb\docs\admin-next-release-runbook.md)

## 一句话结论

当前这套已经具备：

- 源码基座
- 本地候选构建
- 本地自动回归
- 独立线上构建
- 发布清单
- 回滚方案
