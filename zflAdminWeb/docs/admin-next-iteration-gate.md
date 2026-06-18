# admin-next 迭代闸门（构建 + 自动 audit）

每迭代在合并或打 tag 前至少执行一次，与 [PLAN.md](../../PLAN.md) Phase2 封板条件一致。

## 命令（在 `zflAdminWeb` 目录）

| 场景 | 命令 |
|------|------|
| 本地正式候选（输出 `public/admin-next`） | `npm run build-and-audit:admin-next-local` |
| 测试目录 `admin-next-dev` | `npm run build:admin-next-dev` 然后 `npm run audit:admin-next-dev` |
| 线上独立包（不覆盖本地候选） | `npm run build:admin-next-online` |

## 说明

- `audit` 使用 Playwright 对接真实后端（见 `tests/admin-next-dev-audit.spec.js`），需本机可访问 `.env` 中配置的后台地址。  
- 最近一次团队记录见仓库根目录 [WEEKLY_STATUS_SYNC.md](../../WEEKLY_STATUS_SYNC.md)。  
