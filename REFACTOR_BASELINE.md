# 涛冠系统渐进式重构基线

状态：阶段一进行中  
基线日期：2026-08-25

## 1. 重构边界

- `zflAdminWeb` 是平台后台唯一目标源码，旧后台只保留兼容和回退。
- `zflMerchantWeb`、`zflUniApp/zflUniApp`、巡检端保留独立入口，共用后端业务规则和权限结果。
- 保留 ThinkPHP 8、现有接口 URL、现有数据表和历史业务数据。
- 数据库只允许兼容性新增；迁移期禁止删除字段、重算历史订单或改变既有状态值语义。
- 新功能不得继续落入旧后台或 `*-1.php`、`*-2.php`、backup 文件。

## 2. 当前高风险模块

| 模块 | 当前主要实现 | 风险 | 第一目标 |
|---|---|---|---|
| 订单 | `MemberOrderService.php`，约 1942 行 | 下单、支付、履约、核销、退款集中；事务和状态写入分散 | 先建立状态基线和特征测试，再拆命令服务 |
| 采购台账 | `MerchantPurchaseLedgerReportService.php`，约 2229 行 | 匹配算法复杂，历史异常依赖推断 | 建立新旧结果对照和订单事件时间线 |
| 商家 | `MerchantService.php`，约 1768 行 | 资料、审核、绑定、超管、续费等职责集中 | 保持独立写入口，禁止资料编辑覆盖权限关系 |
| 权限 | `common.php`、`MobileAdminAccessService`、`MerchantIdentityService` | 平台、商家、会员、巡检身份判断并存 | 形成统一权限上下文，后端作为唯一授权源 |

## 3. 核心状态事实

### 3.1 订单履约状态

现有 `MemberOrderModel::STATUS` 是迁移期兼容标准。任何新服务必须继续返回原数值和原文案；拆分前不得修改数据库状态值。

已确认的业务流包括：

- 创建后进入待付款。
- 支付或凭证审核通过后进入后续履约状态。
- 收货后进入待评价，评价后完成。
- 核销路径当前会同时修改支付状态并将订单推进到完成。
- 售后申请进入售后状态，退款完成进入已退款。
- 支付审核拒绝或取消进入取消状态。

阶段二必须把支付状态、履约状态、退款状态、核销事实分别建模，兼容层继续映射到现有字段。

### 3.2 商家状态

- `auth_state`：商家审核状态。
- `is_disable`：商家可用状态。
- `member_id`：小程序会员绑定关系，不能通过普通资料编辑修改。
- `member_is_super`：绑定会员的商家超管授权，和审核状态相互独立。
- 重新审核不得自动授予或取消超管权限。

### 3.3 商品状态

现有 `GoodsModel::STATUS` 是迁移期兼容标准。阶段二先记录商品发布、审核、交易和库存变化事件，再拆分商品命令服务。

## 4. 目标角色矩阵

| 角色 | 数据范围 | 核心权限 |
|---|---|---|
| 平台超级管理员 | 全平台 | 全部平台操作和授权治理 |
| 平台财务 | 全平台财务数据 | 支付审核、对账、差额诊断、导出；默认无系统权限管理 |
| 平台审核员 | 被授权业务范围 | 商家审核、商品审核；默认无财务写权限 |
| 平台客服 | 被授权查询范围 | 会员、商家、订单只读和服务记录 |
| 商家负责人 | 当前商家 | 商家资料、员工、商品、订单、统计 |
| 商家员工 | 当前身份授权范围 | 按商家角色菜单和权限码操作 |
| 商家超管 | 绑定商家及明确授予的平台范围 | 平台商家审核、跨商家核销等高风险权限 |
| 巡检员 | 被分配巡检范围 | 巡检业务和关联订单查询 |
| 普通会员 | 本人数据 | 浏览、购买、订单、售后及商家申请 |

所有前端只消费权限结果，不自行推导授权。直接请求未授权接口必须返回 403。

## 5. 写操作规范

阶段二起，订单、商家、商品和权限写接口必须携带统一操作上下文：

- `request_id`：调用方生成的幂等编号。
- `source`：`admin-next`、`merchant-web`、`uniapp-h5`、`uniapp-weixin`、`inspection` 或 `legacy`。
- 操作人类型和操作人 ID 由服务端登录上下文确定，禁止信任前端传入。
- 高风险操作必须记录原因；授权取消、审核拒绝、退款拒绝不得为空。

迁移期保留现有 URL 和返回结构。旧控制器作为兼容入口转调同一个新命令服务，禁止新旧实现双写。

## 6. 旧代码隔离清单

以下文件仅作为历史参考，未完成调用确认前不得删除或继续开发：

- `app/api/controller/merchant/Merchant-1.php`
- `app/api/controller/goods/Goods-1.php`
- `app/admin/controller/goods/Goods-1.php`
- `app/admin/controller/file/File-1.php`
- `app/admin/middleware/ApiVerifyMiddleware-1.php`
- `app/common/service/member/MemberService-1.php`
- `app/common/service/member/MemberOrderService-22.php`
- `app/common/service/merchant/MerchantService-1.php`
- `app/common/service/merchant/MerchantService-2.php`
- `app/common/service/system/MobileAdminAccessService-1.php`
- `app/common/service/system/MobileAdminAccessService-2.php`
- `zflUniApp/zflUniApp/pages/app/my-1.vue`
- uView 组件目录中的 `backup` 文件。

处理规则：先确认路由、自动加载、静态引用和线上日志均无调用，再进入只读归档；旧后台退役阶段才允许删除。

## 7. 阶段一验收清单

- [x] 明确目标前端及兼容边界。
- [x] 标记高风险大文件和历史副本。
- [x] 固化订单、商家、商品现有状态兼容原则。
- [x] 固化目标角色及写操作上下文。
- [x] 生成完整写接口机器可读清单。
- [x] 生成数据库字段字典和关键索引报告。
- [x] 为订单、商家、商品、会员增加只读诊断命令。
- [x] 使用正式库脱敏快照固化采购台账和订单统计基准。
- [x] 建立 PHP 特征测试，覆盖现有状态转换和权限判断。

## 8. 基线审计命令

静态审计不连接数据库，不读取业务数据：

```bash
php think refactor:baseline
```

需要核对当前数据库结构时使用只读模式：

```bash
php think refactor:baseline --database
```

命令输出 JSON，硬检查失败时返回非零退出码。当前硬检查包括订单取消状态 `7`、商家会员绑定和商家超管不得由普通资料编辑覆盖。

## 9. SQL 快照黄金基准

只读解析 mysqldump 快照，不执行 SQL、不连接数据库：

```bash
php think refactor:snapshot --snapshot 413_zlck666_com_2026-06-19_11-32-59_mysql_data_FhRY1.sql --output refactor-baselines/20260619-snapshot.json
```

报告只保存表结构、索引和脱敏聚合数据，不保存姓名、手机号、地址或订单明细文本。相同快照重复执行应生成相同 JSON，后续采购台账新旧算法均以该文件作为第一份黄金基准。

2026-06-19 快照事实：

- 快照 SHA-256：`d200583b8b84cad91fd8645435b6bf90e028b85cd277d74eaa52e12d1fd5f9b4`。
- 有效订单 1724 笔，已支付 1720 笔，已支付实付总额 3,784,243.05 元。
- 采购流水 1477 条，数量 1477，明细总额 4,376,552.64 元；订单和明细引用缺失均为 0。
- 历史数据中有 3 笔有效订单的 `status` 为 `NULL`，作为历史异常保留，阶段二事件流水不得静默修正。
- 订单主表缺少 `order_no`、商家/支付时间、会员/创建时间索引；订单明细和订单日志缺少订单关联索引。阶段二先用测试库验证查询计划，再通过版本化迁移新增。

权限现状、移动写接口边界和统一权限目标见 `REFACTOR_PERMISSION_MATRIX.md`。

订单转换事实、兼容差异和写入口见 `ORDER_STATE_BASELINE.md`。
