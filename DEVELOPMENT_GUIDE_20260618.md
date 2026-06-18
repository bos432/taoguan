# 涛冠项目开发升级文档

更新时间：2026-06-18

本文档用于后续开发、升级、上线和排障交接。项目当前已上线新版 `admin-next` 后台，并新增商家采购对账、买卖对比、平台收款码、商家采购限制等能力。

## 1. 项目概览

### 主要线上地址

- 正式站：`https://413.chaimen666.com/`
- 正式后台：`https://413.chaimen666.com/admin-next/`
- 灰度站：`http://gray.413.chaimen666.com/`
- 灰度后台：`http://gray.413.chaimen666.com/admin-next/`
- Git 仓库：`https://github.com/bos432/taoguan.git`

### 服务器目录

- 正式站目录：`/www/wwwroot/413.chaimen666.com`
- 灰度站目录：`/www/wwwroot/gray.413.chaimen666.com`
- 备份目录：`/www/backup`

### 本地项目目录

- 当前工作目录：`E:\2025\重庆分公司\涛冠\2026第二版本`
- 后端：`app/`
- 新后台前端：`zflAdminWeb/`
- 商家端前端：`zflMerchantWeb/`
- 小程序/H5 前端：`zflUniApp/zflUniApp/`
- 数据库迁移：`private/migrations/`
- 静态发布目录：`public/`

## 2. 技术栈和运行方式

### 后端

- ThinkPHP 项目。
- 入口文件：`public/index.php`
- 命令入口：`think`
- 线上推荐 PHP：宝塔 PHP 8.0，命令路径常见为 `/www/server/php/80/bin/php`
- Composer 依赖目录：`vendor/`

常用命令：

```bash
/www/server/php/80/bin/php think clear
composer install --no-dev --optimize-autoloader
```

注意：服务器 Composer 如果提示缺少 `ext-fileinfo`，需要在宝塔 PHP 扩展里启用 `fileinfo`，不要长期使用 `--ignore-platform-req=ext-fileinfo` 绕过。

### admin-next 后台前端

目录：`zflAdminWeb/`

常用脚本：

```bash
npm install
npm run build:admin-next-local
npm run build:admin-next-online
```

关键环境文件：

- 本地构建：`zflAdminWeb/.env.admin-next-local`
- 线上构建：`zflAdminWeb/.env.admin-next-online`

线上构建必须确认：

```env
VITE_APP_BASE_URL = 'https://413.chaimen666.com'
VITE_APP_BASE = '/admin-next/'
VITE_APP_OUT_DIR = 'dist-admin-next-online'
```

如果正式后台登录没反应，第一优先检查正式包里是否残留灰度地址：

```bash
grep -R "gray.413" /www/wwwroot/413.chaimen666.com/public/admin-next/js || echo "OK，正式包没有灰度地址"
```

## 3. 核心业务口径

### 平台和商家商品上传

- 平台可以上传商品。
- 审核成功的商家可以上传商品。
- 商品上传后，默认收款码使用平台收款码。
- 当商家购买平台或其他商家的商品，并完成凭证支付审核后，该商家可进入后续业务链路。

### 平台收款码

后台位置：

`系统管理 -> 系统设置 -> 系统设置 -> 平台收款码`

相关字段：

- 表：`ya_system_setting`
- 字段：`platform_voucher_image_id`

相关后端：

- `app/admin/controller/system/Setting.php`
- `app/api/controller/setting/Setting.php`

小程序/H5 通常不需要因为平台收款码改动重新发布，因为收款码由后端接口返回。但上线后仍需实际走一遍下单和凭证支付页确认展示正常。

## 4. 商家采购对账功能

### 入口

后台路径：

`平台运营 -> 商家采购对账`

路由：

`/report/merchant-purchase-ledger`

### 业务目标

财务需要看懂：

- A 商家买了平台多少。
- A 商家买了其他商家多少。
- A 商家自己被别人买了多少。
- 买入和卖出是否基本抵平。
- 如果有差额，快速定位疑似造成差额的订单。

### 核心表

表名：`ya_merchant_purchase_ledger`

迁移文件：

`private/migrations/20260618_add_merchant_purchase_ledger.sql`

这张表是商家采购流水快照，主要记录支付审核成功时的商品来源、买方商家、来源商家/平台、订单金额等信息。

### 数据回填

正式库已经执行过：

```bash
mysql -u413_zlck666_com -p 413_zlck666_com < private/migrations/20260618_add_merchant_purchase_ledger.sql
```

曾验证：

```sql
SHOW TABLES LIKE 'ya_merchant_purchase_ledger';
SELECT COUNT(*) AS ledger_count FROM ya_merchant_purchase_ledger;
```

当时回填结果为 `1476` 条。

### 后端文件

- 控制器：`app/admin/controller/report/MerchantPurchaseLedger.php`
- 报表服务：`app/common/service/report/MerchantPurchaseLedgerReportService.php`
- 写入服务：`app/common/service/finance/MerchantPurchaseLedgerService.php`
- 模型：`app/common/model/finance/MerchantPurchaseLedgerModel.php`
- 下单/审核触发点：`app/common/service/member/MemberOrderService.php`

### 前端文件

- 页面：`zflAdminWeb/src/views/report/merchant-purchase-ledger.vue`
- API：`zflAdminWeb/src/api/report/merchant-purchase-ledger.js`
- 权限/路由补充：`zflAdminWeb/src/store/modules/permission.js`

### 接口

接口前缀：

`/admin/report.MerchantPurchaseLedger/`

主要接口：

- `filters`：筛选项。
- `summary`：汇总、买方排行、来源排行、买卖对比、异常汇总。
- `list`：采购明细流水。
- `tradeDiffOrders`：查看差额订单，按差额金额定位疑似订单。
- `export`：导出 CSV。

权限补充位置：

`app/common/service/system/UserService.php`

新增接口后要确认加入内置报表权限，否则非超级管理员可能看不到或请求失败。

## 5. 财务对账逻辑

### 买方拆账

按买方商家汇总：

- 买平台：`source_type = platform`
- 买其他商家：`source_type = merchant`
- 合计：买平台 + 买商家

### 商家买卖对比

每个对象按两个方向汇总：

- 我买别人：该商家作为买方的采购金额。
- 别人买我：该商家作为供货来源的销售金额。
- 差额：`我买别人 - 别人买我`

普通财务理解：

- 差额为正：这个商家买入比卖出多。
- 差额为负：这个商家卖出比买入多。
- 差额不是直接等于“未核销”，只是说明买卖两边没有抵平，需要继续看订单和凭证。

### 查看差额订单

按钮名称：`查看差额订单`

当前逻辑：

- 差额为正，从“我买别人”的订单里找。
- 差额为负，从“别人买我”的订单里找。
- 优先找单笔金额刚好等于差额的订单。
- 如果没有单笔命中，尝试找 `2-4` 笔订单合计刚好等于差额。
- 如果仍没有完全命中，列出最接近差额的订单。

注意：这是辅助定位，不是最终会计核销结论。最终仍以订单详情、支付凭证、财务实际审核为准。

### 凭证支付核算口径

凭证支付订单的特点：

- A 买 B/平台商品。
- B/平台审核凭证。
- 审核通过后视为支付成功。

因此核算时不能简单用 `pay_price = 0` 判定异常。当前逻辑已调整为：

- 凭证支付且已支付时，优先使用订单总额或凭证审核金额作为核算口径。
- 避免把凭证订单全部误判为“采购流水金额与订单实付不一致”。

相关方法：

- `resolveOrderPayPrice`
- `resolveBillAmount`
- `appendReconciliationState`

## 6. 商家列表和采购限制

### 商家列表优化

页面：

`zflAdminWeb/src/views/merchant/merchant.vue`

已优化内容：

- 显示商家名称。
- 显示商家 ID、账号、联系人、手机号。
- 显示收款码状态。
- 增加“设置平台收款码”快捷入口。

### 商家采购限制字段

表：`ya_merchant`

字段：

- `merchant_purchase_daily_quantity_limit`
- `merchant_purchase_daily_amount_limit`

用途：

- 限制商家每日采购件数。
- 限制商家每日采购金额。

相关服务：

`app/common/service/system/MerchantPurchaseLimitService.php`

## 7. 推荐开发流程

### 本地开发

1. 修改后端或前端代码。
2. 后端先跑 PHP 语法检查。
3. 前端跑本地构建。
4. 本地或灰度验证。
5. 提交 Git。
6. 灰度服务器 `git pull`。
7. 灰度验证无误后同步正式站。

常用验证：

```bash
php -l app/admin/controller/report/MerchantPurchaseLedger.php
php -l app/common/service/report/MerchantPurchaseLedgerReportService.php

cd zflAdminWeb
npm run build:admin-next-local
```

### Git 提交

```bash
git status --short
git add 需要提交的文件
git commit -m "fix: 描述本次修复"
git push origin main
```

当前近期关键提交：

- `f6498ea fix: add trade diff order drilldown`
- `4916bc5 fix: drill trade difference into ledger orders`
- `81b2d75 fix: improve merchant list and platform voucher setting`
- `1413533 feat: drill into trade comparison details`
- `ab6bedb fix: reconcile voucher orders by order totals`
- `19025cc fix: use voucher approval amount in purchase reconciliation`
- `9046105 feat: compare merchant buy sell totals`

## 8. 灰度和正式发布流程

### 灰度目录拉最新代码

```bash
cd /www/wwwroot/gray.413.chaimen666.com
git pull
```

### 正式后台前端包构建

正式后台包必须使用正式域名：

```bash
cd /www/wwwroot/gray.413.chaimen666.com/zflAdminWeb

grep VITE_APP_BASE_URL .env.admin-next-online
sed -i 's#http://gray.413.chaimen666.com#https://413.chaimen666.com#g' .env.admin-next-online
sed -i 's#https://gray.413.chaimen666.com#https://413.chaimen666.com#g' .env.admin-next-online
grep VITE_APP_BASE_URL .env.admin-next-online

npm run build:admin-next-online
```

构建后检查正式包不能残留灰度地址：

```bash
grep -R "gray.413" dist-admin-next-online/js || echo "OK，线上包没有灰度地址"
```

### 打包后端和前端产物

```bash
cd /www/wwwroot/gray.413.chaimen666.com

tar \
  --exclude='public/storage' \
  --exclude='public/uploads' \
  -czf /www/backup/gray_release_to_413_$(date +%F_%H%M%S).tar.gz \
  404.html app bootstrap config extend private public route _deploy_sql sql-release composer.json composer.lock think
```

确认包内没有危险文件：

```bash
tar -tzf /www/backup/gray_release_to_413_*.tar.gz | grep -E '(^|/)\.env$|public/storage|public/uploads' || echo "OK，没有危险文件"
```

### 覆盖正式站

```bash
cd /www/wwwroot/413.chaimen666.com

rm -rf public/admin-next
tar -xzf $(ls -t /www/backup/gray_release_to_413_*.tar.gz | head -1)

mkdir -p runtime public/storage
chown -R www:www app bootstrap config extend private public route _deploy_sql sql-release composer.json composer.lock think runtime public/storage
chmod -R 755 runtime public/storage

/www/server/php/80/bin/php think clear
```

### 单独覆盖正式后台前端包

如果只是前端包域名错了，或者只改后台页面，可以只复制后台包：

```bash
rm -rf /www/wwwroot/413.chaimen666.com/public/admin-next
cp -r /www/wwwroot/gray.413.chaimen666.com/zflAdminWeb/dist-admin-next-online /www/wwwroot/413.chaimen666.com/public/admin-next
chown -R www:www /www/wwwroot/413.chaimen666.com/public/admin-next
```

## 9. 上线前备份和回滚

### 正式站文件备份

```bash
mkdir -p /www/backup
cd /www/wwwroot

tar --exclude='413.chaimen666.com/runtime' \
    --exclude='413.chaimen666.com/public/admin-next' \
    -czf /www/backup/413_site_before_release_$(date +%F_%H%M%S).tar.gz 413.chaimen666.com
```

### 正式数据库备份

```bash
mysqldump --single-transaction --no-tablespaces -u413_zlck666_com -p 413_zlck666_com > /www/backup/413_db_before_release_$(date +%F_%H%M%S).sql
```

如果 `mysqldump` 提示缺少 `PROCESS privilege`，需要加 `--no-tablespaces`。

### 回滚思路

文件回滚：

1. 找到上线前的 `/www/backup/413_site_before_release_*.tar.gz`。
2. 先备份当前异常版本。
3. 解压备份覆盖正式站。
4. 执行 `php think clear`。

数据库回滚：

- 只有在确认数据库结构或数据被错误修改时才回滚。
- 回滚前必须再次备份当前数据库。
- 不要在不确定影响范围时直接恢复整个库。

## 10. 上线后验收清单

### 后台基础

- 打开 `https://413.chaimen666.com/admin-next/`
- 登录后台。
- 打开控制台确认没有接口请求到 `gray.413.chaimen666.com`。
- 检查 `setting` 接口：

```bash
curl -i https://413.chaimen666.com/admin/system.Login/setting
```

### 商家采购对账

- 进入 `平台运营 -> 商家采购对账`。
- 检查总额、买平台、买其他商家、订单/件数。
- 检查商家买卖对比。
- 点击“我买别人”金额，确认下方明细筛选正确。
- 点击“别人买我”金额，确认下方明细筛选正确。
- 点击“查看差额订单”，确认弹窗能列出疑似订单。
- 点击“核对订单”，确认跳转订单列表并按订单号筛选。
- 导出 CSV，确认字段完整。

### 平台收款码

- 进入 `系统管理 -> 系统设置 -> 系统设置 -> 平台收款码`。
- 确认图片能正常显示和保存。
- H5 下单进入凭证支付页，确认显示正确收款码。

### 订单和凭证支付

- 找一笔凭证支付订单。
- 确认订单状态、支付状态、凭证审核状态。
- 审核通过后确认采购流水生成。
- 回到商家采购对账页确认金额进入统计。

## 11. 常见故障排查

### 后台登录没反应

优先检查前端包是否还指向灰度域名：

```bash
grep -R "gray.413" /www/wwwroot/413.chaimen666.com/public/admin-next/js || echo "OK，正式包没有灰度地址"
```

如果有结果，重新用正式域名打包并覆盖 `public/admin-next`。

### 后端提示 `vendor/autoload.php` 不存在

说明后端依赖没安装：

```bash
cd /www/wwwroot/413.chaimen666.com
composer install --no-dev --optimize-autoloader
```

### `php think` 找不到

确认当前目录必须是站点根目录，例如：

```bash
cd /www/wwwroot/413.chaimen666.com
/www/server/php/80/bin/php think clear
```

### `.user.ini` 无法删除或改权限

宝塔会保护 `.user.ini`，同步代码时不要强删它。打包和覆盖时排除 `.env`、`.user.ini`、`runtime/`、`public/storage/`、`public/uploads/`。

### 正式站不是 Git 仓库

`/www/wwwroot/413.chaimen666.com` 当前不是 Git 仓库，`git rev-parse HEAD` 报错正常。

推荐流程：

- 灰度目录作为 Git 工作目录。
- 正式目录通过安全打包方式覆盖。

## 12. 开发注意事项

- 正式站正在运营，任何写操作都要谨慎。
- 能先灰度验证的，不要直接改正式站。
- 不要覆盖正式站 `.env`。
- 不要覆盖 `.user.ini`。
- 不要覆盖 `public/storage/`、`public/uploads/`。
- 不要随意清空 `runtime/` 以外的业务目录。
- 新增后台接口后，要同步补 `UserService` 权限。
- 新增数据库字段后，要写迁移 SQL，并记录是否已在正式库执行。
- 涉及金额核算时，要写清楚“业务口径”，不要只写技术逻辑。

## 13. 后续建议

- 给商家采购对账增加“按商家导出买卖对比”。
- 给差额订单弹窗增加“导出当前疑似订单”。
- 给订单详情增加“从对账进入”的上下文提示。
- 给正式发布流程写成脚本，减少手动复制和域名打包出错。
- 把灰度站升级为独立测试库，避免灰度和正式共享数据带来的误操作风险。

