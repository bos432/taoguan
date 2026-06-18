# admin-next / uni-app 发布隔离说明

## 后台 admin-next

- 源码目录：`zflAdminWeb`
- 本地构建：`npm run build:admin-next-local`
- 测试构建建议：`admin-next-dev`
- 正式构建建议：`admin-next-online`
- 发布策略：
  - 先发布灰度目录
  - 验证通过后再替换正式 `admin-next`
  - 保留旧目录用于快速回退

## uni-app

- 源码目录：`zflUniApp/zflUniApp`
- 当前环境配置文件：`config/env.js`
- 上线前域名替换说明：`config/ENV_DEPLOY.md`
- 已预置环境：
  - `dev` 开发环境
  - `local` 本地联调
  - `lan` 局域网联调
  - `test` 测试环境
  - `gray` 灰度环境
  - `prod` 正式环境
- 安全规则：
  - `test` 和 `gray` 默认不指向正式环境
  - 上线前必须把 `test` / `gray` 替换成独立测试域名或灰度域名
  - 开发、联调、验收禁止直接使用正式可写数据

## 协议验收

- 登录页协议默认不勾选
- 结算页售后说明默认不勾选
- 商家申请免责声明默认不勾选
- 未勾选时必须拦截提交

## 发布前检查

- 后台核心栏目无死链、无白屏
- uni-app 登录、协议、首页、商品、购物车、结算、订单、商家申请可用
- 测试库验证通过后再切灰度
- 灰度验证通过后再切正式
