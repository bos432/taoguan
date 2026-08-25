# 渐进式重构权限事实矩阵

更新日期：2026-08-25

## 1. 当前授权来源

| 身份 | 当前后端授权源 | 当前拒绝机制 | 重构目标 |
|---|---|---|---|
| 平台管理员/财务/审核员/客服 | `system_user`、角色菜单 URL | admin 中间件和控制器 | 统一权限码并保留角色数据范围 |
| 商家负责人/员工 | `merchant_user`、商家角色菜单 | merchant 中间件 | 当前商家范围内授权 |
| 商家超管 | `merchant.member_id + member_is_super` | `MobileAdminAccessService` 写接口复核 | 独立授权命令、授权记录和权限版本 |
| 普通会员 | member API 列表及会员组 | `ApiVerifyMiddleware` | 本人数据范围和统一权限响应 |
| 巡检员 | inspection 用户、角色和菜单 URL | inspection token/API 中间件 | 保留入口，接入统一权限上下文 |

## 2. 移动管理接口边界

| 权限 | 查询入口 | 写入口 | 当前后端校验 |
|---|---|---|---|
| `merchant_view` | 商家参数、列表、详情 | 无 | `assertAnyPermission` |
| `merchant_auth` | 商家参数、列表、详情 | 商家审核 | `assertPermission('merchant_auth')` |
| `order_view` | 订单参数、列表 | 无 | `assertAnyPermission` |
| `order_pay_auth` | 订单参数、列表 | 支付审核 | `assertPermission('order_pay_auth')` |
| `order_writeoff` | 订单参数、列表 | 自提核销 | `assertPermission('order_writeoff')` |

前端入口不是授权依据。上述三个写接口必须继续在控制器或统一应用服务中做后端复核，未授权请求在统一改造后返回 HTTP 403 和稳定业务错误码。

## 3. 商家身份权限事实

| 商家状态 | 当前权限码 |
|---|---|
| 未审核普通商家 | 无 |
| 审核通过普通商家 | `edit_profile`、`admin_manage_merchant`、`verify_order`、`view_stats`、`publish_product` |
| 审核通过商家超管 | 普通商家权限加 `verify_cross_merchant_order` |

`auth_state` 与 `member_is_super` 当前是独立字段。重新审核不得直接修改超管字段；阶段三必须明确审核失败、禁用、过期时权限上下文是否暂停行权，同时保留原授权记录，不得靠前端隐藏入口代替后端拒绝。

## 4. 已锁定安全特征

- 未审核普通商家不返回商家操作权限码。
- 普通商家不返回跨商家核销权限码。
- 核销、支付审核和商家审核写接口均执行后端权限检查。
- 某一移动权限只派生对应 API，不横向授予其他审核能力。
- 巡检非超管必须通过菜单角色校验，无权限时返回明确拒绝错误。

阶段三统一权限响应前，这些特征不得回归；兼容接口仍保留原 URL 和原业务结构。

## 5. 统一权限上下文契约

阶段三首个兼容接口已固定返回以下字段：

- `identity`：当前身份类型、ID 和显示名称。
- `merchant`：当前商家 ID、名称和审核状态；非商家身份为空。
- `role_codes`：稳定角色码，例如 `platform_super`、`merchant_owner`、`merchant_employee`、`merchant_super`、`member`。
- `permission_codes` / `permission_map`：稳定权限码数组及映射。
- `data_scope`：`all`、`role`、`merchant`、`member` 或 `member_and_merchant`，并明确商家 ID 与会员 ID。
- `permission_version`：由当前身份、角色、权限与数据范围生成的版本摘要；登录、刷新或切换商家身份后应替换前端旧版本。

兼容查询入口：

| 来源端 | 接口 |
|---|---|
| `admin-next` | `admin/system.UserCenter/permissionContext` |
| 商家桌面端 | `merchant/system.UserCenter/permissionContext` |
| uni-app 会员/移动商家端 | `api/merchant.Identity/context`，且 `current`、`switch` 附带 `permission_context` |

统一拒绝契约为 HTTP `403`、业务码 `40301`、错误码 `AUTH_FORBIDDEN`，并在 `data.permission` 返回被拒绝的权限码。旧控制器尚未全部迁移到统一断言，迁移期间原后端校验继续保留，不能提前删除。

首批稳定权限码：

- 平台：`platform.merchant.view`、`platform.merchant.review`、`platform.order.view`、`platform.order.payment_review`、`platform.order.writeoff`、`platform.order.refund`、`platform.finance.ledger`、`platform.finance.gateway_attempt`。
- 商家：`merchant.profile.edit`、`merchant.order.view`、`merchant.order.verify`、`merchant.order.cross_verify`、`merchant.stats.view`、`merchant.product.publish`。
- 会员：`member.profile.view`、`member.order.view_own`、`member.order.create`、`member.order.refund_own`。
