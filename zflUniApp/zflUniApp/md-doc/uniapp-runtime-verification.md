# uni-app 运行与验证手册

## 适用目录
- 源码目录：`E:\2025\重庆分公司\涛冠\2026第二版本\zflUniApp\zflUniApp`
- 适用前端：H5、微信小程序体验版

## 当前项目形态
- 当前项目是 `HBuilderX` 管理的 uni-app 工程，不是标准 npm 命令工程。
- `package.json` 当前为空对象，不能依赖 `npm run dev` / `npm run build` 作为主出包入口。
- 实际运行与出包，默认应通过 `HBuilderX` 的“运行”与“发行”菜单执行。

## 环境配置文件
- 主配置文件：`E:\2025\重庆分公司\涛冠\2026第二版本\zflUniApp\zflUniApp\config\env.js`
- 配置样板：`E:\2025\重庆分公司\涛冠\2026第二版本\zflUniApp\zflUniApp\config\env.profile.example.json`
- 说明文档：`E:\2025\重庆分公司\涛冠\2026第二版本\zflUniApp\zflUniApp\config\ENV_DEPLOY.md`

## 出包前先做的配置动作
1. 打开 `config/env.js`。
2. 将 `test` 改成真实测试环境地址。
3. 将 `gray` 改成真实灰度环境地址。
4. 保持 `prod` 为正式域名，不要把测试地址写到 `prod`。
5. 用 `config/env.profile.example.json` 作为人工对照模板，避免漏改字段。
6. 当前源码已启用环境守卫：非正式环境若误指向正式域名，请求会被直接拦截。
7. 当前源码已启用正式环境切换锁：从登录页或设置页切换 `prod` 时，必须先显式解锁确认。

## H5 运行验证
### 本地联调
1. 在 `HBuilderX` 打开 `zflUniApp\zflUniApp` 项目。
2. 确认 `config/env.js` 中 `dev` / `local` 指向本地接口。
3. 点击：
   `运行 -> 运行到浏览器 -> Chrome`
4. 默认验证地址应落在本地 H5 预览域名，并显示“开发环境”或“本地联调”标签。

### 测试环境验证
1. 先把 `config/env.js` 中 `test` 地址替换为真实测试环境。
2. 确认当前 H5 访问域名不是正式域名 `413.chaimen666.com`。
3. 使用 HBuilderX 运行 H5。
4. 打开页面后，优先检查登录页、我的页、结算页、商家申请页、审核页顶部环境标签是否显示“测试环境”。

### 灰度环境验证
1. 先把 `config/env.js` 中 `gray` 地址替换为真实灰度入口。
2. 使用 HBuilderX 执行：
   `发行 -> 网站-H5手机版`
3. 将产物部署到独立灰度目录，不要覆盖正式 `/app/`。
4. 灰度入口访问后，应核对环境标签、接口地址、关键写操作提示是否全部指向灰度环境。

## 微信小程序体验版验证
1. 确认 `manifest.json` 中微信小程序 `appid` 正确。
2. 在 HBuilderX 中执行：
   `发行 -> 小程序-微信`
3. 选择“体验版/预览版”路径，禁止直接提正式版。
4. 在微信开发者工具中打开编译结果，核对：
   - 登录协议默认未勾选
   - 未勾选协议不能登录
   - 我的页、商品页、结算页、商家申请页、审核页环境标签正确
   - 测试包或灰度包没有连到正式域名

## 建议优先验证的页面
- `pages/my/login.vue`
- `pages/app/home.vue`
- `pages/app/my.vue`
- `pages/app/my-1.vue`
- `pages/goods/details.vue`
- `pages/goods/my_cart.vue`
- `pages/goods/settlement.vue`
- `pages/order/list.vue`
- `pages/order/mer_list.vue`
- `pages/merchant/apply.vue`
- `pages/admin/merchant-audit.vue`
- `pages/admin/order-audit.vue`

## 每轮运行后必须核对
- 页面顶部环境标签与当前目标环境一致
- 登录协议默认态仍为未勾选
- 登录回跳仍然保留原目标页
- 写操作确认弹窗文案与环境一致
- 测试 / 灰度入口不出现正式环境提示文案
- 非正式环境若误配正式域名，应直接看到请求被拦截，而不是继续落到线上数据

## 当前未完成的真实验证边界
- 本文档补齐的是“如何运行、如何验”的执行手册。
- 当前线程还没有实际执行 HBuilderX H5 运行。
- 当前线程还没有实际执行微信小程序体验版发行。
- 如果进入下一轮，要继续做的是真实跑一遍测试环境验证，而不是只看源码。
