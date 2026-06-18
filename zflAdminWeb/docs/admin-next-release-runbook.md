# admin-next 正式切换说明

## 当前状态

- 本地正式候选目录：
  [public/admin-next](E:\2025\重庆分公司\涛冠\2026第二版本\public\admin-next)
- 本地测试地址：
  `http://127.0.0.1:807/admin-next/`
- 当前本地构建模式：
  [`.env.admin-next-local`](E:\2025\重庆分公司\涛冠\2026第二版本\zflAdminWeb\.env.admin-next-local)
- 正式目录备份：
  [admin-next-backup-20260422-122059](E:\2025\重庆分公司\涛冠\2026第二版本\public\admin-next-backup-20260422-122059)
- 当前自动回归脚本：
  [admin-next-dev-audit.spec.js](E:\2025\重庆分公司\涛冠\2026第二版本\zflAdminWeb\tests\admin-next-dev-audit.spec.js)

## 已确认通过

- 真实后端 `UserCenter/info` 已直接返回 `/platform` 菜单和报表权限
- 真实登录可用
- 商家管理页可用
- `/analytics` 可用，支持前进后退、筛选记忆、重新登录恢复
- `/exports` 可用，支持下载、导出历史、筛选记忆
- 异常接口提示和移动端布局可用
- 菜单收起展开和退出登录可用

## 常用命令

在目录 [zflAdminWeb](E:\2025\重庆分公司\涛冠\2026第二版本\zflAdminWeb) 下执行：

```bash
npm run start:local-stack
```

说明：
调用项目根目录 [start-local.ps1](E:\2025\重庆分公司\涛冠\2026第二版本\local\start-local.ps1)，按项目自带 `php-local.ini` 拉起本地 MySQL `3310` 和 PHP `807`。
如果 `807` 已被未加载 `php-local.ini` 的旧 PHP 进程占用，脚本会先重启它，避免再次出现 `could not find driver`。

```bash
npm run check:local-stack
```

说明：
对 `127.0.0.1:3310` 和 `127.0.0.1:807/admin/system.Login/login` 做联调前探活。
如果 PHP 未加载 `pdo_mysql`、MySQL 未监听，或登录探活失败，会直接阻断后续自动回归。

```bash
npm run build:admin-next-local
```

说明：
把源码构建到 [public/admin-next](E:\2025\重庆分公司\涛冠\2026第二版本\public\admin-next)，本地接口指向 `http://127.0.0.1:807`。
构建前会自动校验本地模式输出目录和接口地址。

```bash
npm run audit:admin-next
```

说明：
对 `/admin-next/` 路径执行 7 项自动回归。

```bash
npm run audit:admin-next-dev
```

说明：
对 `/admin-next-dev/` 路径执行同一套自动回归。

```bash
npm run build-and-audit:admin-next-local
```

说明：
先执行本地栈探活，再构建并自动回归。

```bash
npm run prepare-build-and-audit:admin-next-local
```

说明：
一键拉起本地 MySQL + PHP，再做探活、构建和自动回归。
适合当前这套 `admin-next` 本地候选版的完整验收。

```bash
npm run build:admin-next-online
```

说明：
生成独立的线上发布包到 `dist-admin-next-online`，不会覆盖 [public/admin-next](E:\2025\重庆分公司\涛冠\2026第二版本\public\admin-next)。
构建前会自动校验：
- `VITE_APP_BASE_URL` 不是本地地址
- `VITE_APP_OUT_DIR` 不是 `public/admin-next` 或 `public/admin-next-dev`
- 线上构建目录固定为 `dist-admin-next-online`

```bash
npm run release:preflight:admin-next-local
```

说明：
按本地候选口径执行后台预检，自动串行校验：
- `admin-next-local` 环境变量
- 后台源码路由覆盖报告
- 本地 MySQL / PHP 联调探活
- `/admin-next/` 自动回归

```bash
npm run release:preflight:admin-next-dev
```

说明：
按灰度目录口径执行后台预检，自动校验：
- `admin-next-dev` 环境变量
- 后台源码路由覆盖报告
- 本地 MySQL / PHP 联调探活
- `/admin-next-dev/` 自动回归

```bash
npm run release:preflight:admin-next-online
```

说明：
按线上发布包口径执行后台预检，自动校验：
- `admin-next-online` 环境变量
- 后台源码路由覆盖报告

不会触碰本地 `public/admin-next` 目录，也不会把线上包误构建到本地候选目录。

## 正式切换前检查

1. 先执行 `npm run start:local-stack`，确认本地 `3310` 与 `807` 已拉起。
2. 确认本地访问 `http://127.0.0.1:807/admin-next/` 正常。
3. 执行 `npm run build-and-audit:admin-next-local`，确保 7 项全部通过。
3. 确认后端缓存没有旧菜单残留。
   如有需要，可清理 `runtime/cache/yylAdmin` 后再测试一次。
4. 确认要部署的环境不是线上正式域名配置。
   当前这份候选包是本地模式，不应直接替换线上域名版构建。

## 回滚步骤

如果切换后需要回滚，在项目根目录执行：

```powershell
Remove-Item -LiteralPath 'E:\2025\重庆分公司\涛冠\2026第二版本\public\admin-next' -Recurse -Force
Copy-Item -LiteralPath 'E:\2025\重庆分公司\涛冠\2026第二版本\public\admin-next-backup-20260422-122059' -Destination 'E:\2025\重庆分公司\涛冠\2026第二版本\public\admin-next' -Recurse
```

回滚后建议再访问：

```text
http://127.0.0.1:807/admin-next/
```

并至少确认：

- 登录正常
- 菜单正常
- 商家管理正常
- `/analytics` 正常
- `/exports` 正常

## 注意事项

- [`.env.production`](E:\2025\重庆分公司\涛冠\2026第二版本\zflAdminWeb\.env.production) 仍指向线上域名 `https://413.chaimen666.com`
- 当前 [public/admin-next](E:\2025\重庆分公司\涛冠\2026第二版本\public\admin-next) 是“本地候选版”，不是线上正式域名版
- 线上发布包请使用 [`.env.admin-next-online`](E:\2025\重庆分公司\涛冠\2026第二版本\zflAdminWeb\.env.admin-next-online) 和 `npm run build:admin-next-online`
- `build:admin-next-online` 的输出目录是 `dist-admin-next-online`，目的是把线上包和本地候选包彻底隔离
- 当前已增加 `validate-admin-next-env.mjs`，用于阻止把本地接口或本地输出目录错误带入线上构建

## 最近一次验证

- 构建命令：
  `npm run build:admin-next-local`
- 验收命令：
  `npm run audit:admin-next`
- 结果：
  `7 passed`
