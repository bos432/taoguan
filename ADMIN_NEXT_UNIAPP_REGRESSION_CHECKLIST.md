# admin-next 与 uni-app 联调回归 / 灰度核对清单

本清单落实 [PLAN.md](PLAN.md) 第四阶段与「联调回归 / 灰度上线」章节，供测试与发布负责人在**测试库**与**灰度环境**勾选执行。与 [PLAN.md](PLAN.md) 第三节「灰度上线核对」表同步更新状态。

## 一、前置条件

- [ ] 后台：`zflAdminWeb` 下 `npm run build-and-audit:admin-next-local` 或当前迭代约定的 `build` + `audit` 已通过  
- [ ] uni-app：[config/env.js](zflUniApp/zflUniApp/config/env.js) 的 `test` / `gray` 已按 [ENV_DEPLOY.md](zflUniApp/zflUniApp/config/ENV_DEPLOY.md) 配置为独立环境（非正式可写误用）  
- [ ] 测试账号、权限、订单/商品等测试数据已在**独立测试库**准备  

## 二、测试库全链路（写操作仅在此环境）

### 后台 admin-next

- [ ] 登录 / 退出 / 刷新保持登录态  
- [ ] 菜单与权限：P0 路由可达，无异常 401/404  
- [ ] 商品分类、商品管理、订单、会员、商家、协议管理：列表 + 典型写操作 + 提示与回显  
- [ ] （P1）数据中心 `#/analytics`、导出中心 `#/exports`：与 [admin-next-dev-phase3-qa.md](zflAdminWeb/docs/admin-next-dev-phase3-qa.md) 一致  

### uni-app（H5 与小程序各跑一遍）

- [ ] 登录：验证码 / 账号密码 / 微信（小程序）— 协议未勾选时**全部拦截**；勾选后可登录  
- [ ] 协议中心、用户协议、隐私政策：可打开、可返回  
- [ ] 结算页：售后说明默认不勾选；未勾选**不可提交**  
- [ ] 商家入驻：免责声明默认不勾选；未勾选**不可提交**  
- [ ] 首页 / 商品 / 购物车 / 订单（P1）：主路径无白屏  

## 三、灰度环境（只读正式库以外的验证）

- [ ] 后台灰度目录：登录、菜单、关键模块无白屏、静态资源无 404  
- [ ] H5 灰度入口：登录、浏览、下单流程、协议拦截符合 [PLAN.md](PLAN.md)  
- [ ] 小程序体验版：与 H5 同一账号下**拦截与提示一致**  
- [ ] 确认请求域名与当前环境 profile 一致，**无测试包误连正式可写**  

## 四、正式切换前

- [ ] 现网仅做**只读**或明确允许的冒烟（不写业务数据）  
- [ ] 回退路径已确认：旧后台目录、旧 H5 入口、小程序回退版本策略  
- [ ] 将 [PLAN.md](PLAN.md) 第三节「灰度上线核对」四行状态更新为「通过」并归档责任人  

## 五、参考命令（后台）

在 `zflAdminWeb` 目录：

```bash
npm run build-and-audit:admin-next-local
npm run build:admin-next-dev && npm run audit:admin-next-dev
npm run build:admin-next-online
```
