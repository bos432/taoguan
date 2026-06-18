涛冠 3.0 发布说明
日期：2026-04-18

一、上传方式
1. 先在宝塔备份网站目录和数据库。
2. 将发布压缩包上传到网站根目录。
3. 在网站根目录直接解压并覆盖。
4. 本压缩包不包含 .env / runtime / public/storage，不会主动覆盖线上环境配置、缓存、附件。

二、SQL 执行顺序
以下 SQL 文件位于压缩包内的 _deploy_sql 目录：

1. 必执行：
   20260409_phase1_safe_schema.sql
   20260418_add_platform_voucher_image_id_safe.sql
   20260418_goods_transfer_and_merchant_disable_strategy.sql

2. 验证用：
   20260409_phase1_verify.sql

3. 可选：
   20260409_merchant_super_cleanup.sql
   仅当你线上仍然存在历史遗留的商家后台超级管理员脏数据时再执行。

三、Linux / 宝塔命令示例
假设网站目录：
/www/wwwroot/your_site

假设数据库：
<db_name>

假设数据库账号：
<db_user>

1. 进入网站目录
cd /www/wwwroot/your_site

2. 解压覆盖
unzip -o taoguan_online_base_deploy_20260418.zip -d /www/wwwroot/your_site

3. 执行 SQL
mysql -h 127.0.0.1 -P 3306 -u <db_user> -p <db_name> < /www/wwwroot/your_site/_deploy_sql/20260409_phase1_safe_schema.sql
mysql -h 127.0.0.1 -P 3306 -u <db_user> -p <db_name> < /www/wwwroot/your_site/_deploy_sql/20260418_add_platform_voucher_image_id_safe.sql
mysql -h 127.0.0.1 -P 3306 -u <db_user> -p <db_name> < /www/wwwroot/your_site/_deploy_sql/20260418_goods_transfer_and_merchant_disable_strategy.sql

4. 验证 SQL
mysql -h 127.0.0.1 -P 3306 -u <db_user> -p <db_name> < /www/wwwroot/your_site/_deploy_sql/20260409_phase1_verify.sql

5. 清缓存
rm -rf /www/wwwroot/your_site/runtime/cache/*
rm -rf /www/wwwroot/your_site/runtime/config/*

6. 重载服务
/etc/init.d/nginx reload
/etc/init.d/php-fpm-80 reload

如果你宝塔上的 PHP 不是 8.0，请把 php-fpm-80 改成你实际的 PHP 服务名，例如：
/etc/init.d/php-fpm-74 reload
/etc/init.d/php-fpm-81 reload

四、上线后重点检查
1. 后台系统设置里是否能看到“平台收款码”
2. 商品管理里是否能看到“批量迁移到平台自营 / 指定商家”
3. 商家停用时是否可选“自动下架商品”策略
4. 后台 analytics 是否正常显示
5. 商品发布、下单确认、平台收款码、图片显示是否正常
6. H5 商城商品列表是否按两列展示；若同步上传小程序，也确认商品列表为双列布局

五、注意事项
1. 不要覆盖线上 .env
2. 不要覆盖线上 public/storage
3. 不要上传本地 runtime
4. 不要导入本地整库备份 SQL
5. 微信小程序上传若报 errCode -10008 invalid ip，属于微信平台上传 IP 限制问题，需要在微信平台侧处理白名单/安全配置，不是当前代码编译错误
