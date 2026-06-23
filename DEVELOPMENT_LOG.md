# DEVELOPMENT_LOG

## 2026-06-20 启动记录

- 阶段名称：启动与计划读取
- 本阶段完成内容：已读取 `PLAN.md`，确认开发范围只能来自计划内 P0/P1 与灰度上线核对项；当前优先从 `admin-next 登录/退出` P0 小阶段开始。
- 修改/新增的主要文件：新增 `DEVELOPMENT_LOG.md`
- 运行或测试结果：尚未运行测试。
- 遗留问题：计划内多数 P0 项仍为“进行中”，需要按小阶段逐步验证和收口。
- 下一阶段应继续处理的事项：检查并推进 `admin-next 登录/退出`：登录成功、退出清理 token、刷新不丢登录态。

## 2026-06-20 admin-next 登录/退出 P0 收口

- 阶段名称：admin-next 登录/退出 P0 收口
- 本阶段完成内容：将后台用户 store 的退出清理改为前端必定执行；无论后端 logout 接口成功或失败，都会清空 token 与用户角色/菜单信息。`resetToken` 也复用同一清理逻辑，减少登录态失效后残留用户信息的风险。
- 修改/新增的主要文件：`zflAdminWeb/src/store/modules/user.js`
- 运行或测试结果：`npm run build:admin-next-local` 通过；`npx eslint src/store/modules/user.js --fix` 后，`npx eslint src/store/modules/user.js` 通过。
- 遗留问题：本阶段未连接独立测试库做真实账号登录/退出浏览器全流程；后续主流程验收时需覆盖登录、刷新保持登录态、退出回登录页。
- 下一阶段应继续处理的事项：继续计划内 P0，进入 `admin-next 菜单/权限` 小阶段，检查菜单可达、权限路由与 401/404 异常跳转风险。

## 2026-06-20 admin-next 菜单/权限 P0 收口

- 阶段名称：admin-next 菜单/权限 P0 收口
- 本阶段完成内容：补齐 `/order/order` 的 admin-next 路由组件映射，避免菜单进入订单管理时因标题映射存在但实际组件路径不一致导致白屏或 404 风险。
- 修改/新增的主要文件：`zflAdminWeb/src/store/modules/permission.js`
- 运行或测试结果：路由覆盖脚本检查 52 个标题映射入口，缺失组件映射为 `[]`；`npx eslint src/store/modules/permission.js --fix` 后，`npx eslint src/store/modules/permission.js` 通过；`npm run build:admin-next-local` 通过。
- 遗留问题：本阶段未使用不同权限账号做浏览器全量菜单矩阵走查；后续主流程验收需覆盖菜单点击、刷新、回退/前进与无权限跳转。
- 下一阶段应继续处理的事项：继续计划内 P0，进入 `admin-next 控制台总览` 小阶段，检查 `/dashboard` 首屏无白屏、关键卡片正常、路由回退/前进正常。

## 2026-06-20 admin-next 控制台总览 P0 收口

- 阶段名称：admin-next 控制台总览 P0 收口
- 本阶段完成内容：将 `/dashboard` 首页的关键指标、热销商品、预警摘要三个接口改为独立容错请求；任一统计接口失败时不再拖垮整个控制台，页面会保留基础入口与默认卡片，并显示“部分控制台数据加载失败”的明确提示。
- 修改/新增的主要文件：`zflAdminWeb/src/views/system/index.vue`
- 运行或测试结果：`npx eslint src/views/system/index.vue --fix` 通过；`npm run build:admin-next-local` 通过，构建仅出现 outDir 位于项目外的既有提示。
- 遗留问题：本阶段未接独立测试库做真实接口失败/成功的浏览器验证；后续主流程验收需覆盖 `/dashboard` 打开、刷新、快捷入口跳转、回退/前进。
- 下一阶段应继续处理的事项：继续计划内 P0，进入 `admin-next 商品分类` 小阶段，检查 `/goods/type` 列表、搜索、添加/编辑/禁用、回显与提示完整性。

## 2026-06-20 admin-next 商品分类 P0 收口

- 阶段名称：admin-next 商品分类 P0 收口
- 本阶段完成内容：为 `/goods/type` 列表加载补齐页面内失败提示和动态空态；列表接口返回缺失字段时改为安全兜底，避免 `list/tree/count` 异常导致页面白屏；编辑详情加载失败时关闭弹窗并给出明确错误提示；同时清理商品分类页未使用的 `Pagination` 组件注册，保证单文件 ESLint 可通过。
- 修改/新增的主要文件：`zflAdminWeb/src/views/goods/type.vue`
- 运行或测试结果：`npm run build:admin-next-local` 通过，构建仅出现 outDir 位于项目外的既有提示；`npx eslint src/views/goods/type.vue --fix` 后，`npx eslint src/views/goods/type.vue` 通过。
- 遗留问题：本阶段未接独立测试库执行真实添加/编辑/禁用写操作；后续主流程验收需覆盖分类搜索、添加、编辑、禁用、失败提示与刷新状态。
- 下一阶段应继续处理的事项：继续计划内 P0，进入 `admin-next 商品管理` 小阶段，检查 `/goods/goods` 列表筛选分页、上下架、审核、批量操作与异常提示。

## 2026-06-20 admin-next 商品管理 P0 收口

- 阶段名称：admin-next 商品管理 P0 收口
- 本阶段完成内容：为 `/goods/goods` 商品列表补齐参数加载与列表加载的页面内异常提示；表格空态会显示具体失败原因；列表接口返回缺失字段时安全兜底，避免 `list/count/statistics` 异常导致页面白屏；编辑商品详情加载失败时关闭弹窗并给出明确错误提示；同时避免参数加载抢占列表全局 loading。
- 修改/新增的主要文件：`zflAdminWeb/src/views/goods/goods.vue`
- 运行或测试结果：`npx eslint src/views/goods/goods.vue --fix` 后，`npx eslint src/views/goods/goods.vue` 通过；`npm run build:admin-next-local` 通过，构建仅出现 outDir 位于项目外的既有提示。
- 遗留问题：本阶段未接独立测试库执行真实上下架、审核、批量迁移、批量打标/换图等写操作；后续主流程验收需覆盖筛选分页、详情、上下架、审核和批量操作。
- 下一阶段应继续处理的事项：继续计划内 P0，进入 `admin-next 订单管理` 小阶段，检查 `/order/order` 列表筛选分页、详情、关键状态流转与异常提示。

## 2026-06-20 admin-next 订单管理 P0 收口

- 阶段名称：admin-next 订单管理 P0 收口
- 本阶段完成内容：为 `/order/order` 补齐订单参数加载与列表加载的页面内异常提示；表格空态会显示具体失败原因；列表接口返回缺失字段时安全兜底，避免 `list/count/exps/status_nums` 异常导致页面白屏；商品明细改为安全读取，缺少 `detaileds/goods/image` 时显示兜底文案；订单详情加载失败时关闭弹窗并给出明确错误提示。
- 修改/新增的主要文件：`zflAdminWeb/src/views/order/list.vue`
- 运行或测试结果：`npx eslint src/views/order/list.vue --fix` 后，`npx eslint src/views/order/list.vue` 通过；`npm run build:admin-next-local` 通过，构建仅出现 outDir 位于项目外的既有提示。
- 遗留问题：本阶段未接独立测试库执行真实订单详情、支付审核、发货/自提/售后等写操作；后续主流程验收需覆盖订单筛选分页、详情打开、凭证待审核筛选、关键状态流转和刷新状态。
- 下一阶段应继续处理的事项：继续计划内 P0，进入 `admin-next 会员管理` 小阶段，检查 `/member/member` 列表、标签/分组联动、禁用/解禁、回显提示。

## 2026-06-20 admin-next 会员管理 P0 收口

- 阶段名称：admin-next 会员管理 P0 收口
- 本阶段完成内容：为 `/member/member` 补齐会员列表与关联选项加载异常提示；表格空态会显示具体失败原因；列表接口返回缺失字段时安全兜底，避免 `list/count/genders/platforms/applications/region/tag/group/exps` 异常导致页面白屏；辅助设备/仓库选项不再抢占会员列表全局 loading；会员详情加载失败时关闭弹窗并给出明确错误提示；批量操作弹窗中的会员 ID 文本框改为真正禁用，避免误编辑。
- 修改/新增的主要文件：`zflAdminWeb/src/views/member/member.vue`
- 运行或测试结果：`npx eslint src/views/member/member.vue --fix` 后，`npx eslint src/views/member/member.vue` 通过；`npm run build:admin-next-local` 通过，构建仅出现 outDir 位于项目外的既有提示。
- 遗留问题：本阶段未接独立测试库执行真实标签/分组联动、禁用/解禁、重置密码、导入导出等写操作；后续主流程验收需覆盖会员筛选分页、详情打开、标签/分组批量修改、禁用/解禁和刷新状态。
- 下一阶段应继续处理的事项：继续计划内 P0，进入 `admin-next 商家管理` 小阶段，检查 `/merchant/merchant` 列表、审核、启停、续期、续费记录、回显提示。

## 2026-06-20 admin-next 商家管理 P0 收口

- 阶段名称：admin-next 商家管理 P0 收口
- 本阶段完成内容：为 `/merchant/merchant` 补齐参数、列表和续费记录的页面内异常提示；商家列表和续费记录表格增加动态空态；列表接口返回缺失字段时安全兜底，避免 `list/count/status_nums` 异常导致页面白屏；状态统计改为对象安全读取；商家详情打开时先以当前行兜底，详情接口失败时不会残留上一家商家的旧数据；启停确认文案统一使用商家展示名，减少误操作风险。
- 修改/新增的主要文件：`zflAdminWeb/src/views/merchant/merchant.vue`
- 运行或测试结果：`npx eslint src/views/merchant/merchant.vue --fix` 后，`npx eslint src/views/merchant/merchant.vue` 通过；`npm run build:admin-next-local` 通过，构建仅出现 outDir 位于项目外的既有提示。
- 遗留问题：本阶段未接独立测试库执行真实商家审核、启停、续费、新建/编辑、续费记录查询等写操作；后续主流程验收需覆盖商家筛选分页、详情打开、审核、启停、续费和刷新状态。
- 下一阶段应继续处理的事项：继续计划内 P0，进入 `admin-next 协议管理` 小阶段，检查 `/setting/accord` 协议中心可配置、内容可打开、链接正确。

## 2026-06-20 admin-next 协议管理 P0 收口

- 阶段名称：admin-next 协议管理 P0 收口
- 本阶段完成内容：为 `/setting/accord` 补齐协议列表加载异常提示和表格动态空态；列表接口返回缺失字段时安全兜底，避免 `list/count/exps` 异常导致页面白屏；协议详情加载失败时关闭弹窗并给出明确错误提示；协议内容仍通过编辑弹窗的富文本页签打开和维护，避免内容加载失败时误以为空白协议。
- 修改/新增的主要文件：`zflAdminWeb/src/views/setting/accord.vue`
- 运行或测试结果：`npx eslint src/views/setting/accord.vue --fix` 后，`npx eslint src/views/setting/accord.vue` 通过；`npm run build:admin-next-local` 通过，构建仅出现 outDir 位于项目外的既有提示。
- 遗留问题：本阶段未接独立测试库执行真实协议新增/编辑、启用/禁用、删除和前台协议中心联动验证；后续主流程验收需覆盖协议筛选分页、内容页签打开、保存回显、禁用/启用和刷新状态。
- 下一阶段应继续处理的事项：重新读取计划与日志后，继续计划内下一小阶段；后台 P0 单页已完成初步收口，下一步应进入全局稳定性或 uni-app P0 链路。

## 2026-06-20 admin-next 全局稳定性 P0 收口

- 阶段名称：admin-next 全局稳定性 P0 收口
- 本阶段完成内容：修复后台 Playwright 自动巡检的本地账号密码探测逻辑，使 audit 与 `check:local-stack` 一致，按 `ADMIN_AUDIT_PASSWORD`、`ADMIN_LOCAL_PASSWORD`、本地常用密码和默认密码依次尝试；成功创建 token 后复用同一密码进行真实登录表单测试，避免本地密码非默认值时全局巡检误失败。
- 修改/新增的主要文件：`zflAdminWeb/tests/admin-next-dev-audit.spec.js`
- 运行或测试结果：`npx eslint tests/admin-next-dev-audit.spec.js --fix` 通过；`npm run build-and-audit:admin-next-local` 通过，其中 `check:local-stack` 成功、`build:admin-next-local` 成功、`audit:admin-next` Playwright 8/8 通过；构建仍仅出现 outDir 位于项目外的既有提示。
- 遗留问题：本阶段为本地自动巡检与静态资源稳定性验证；正式灰度目录、H5 灰度入口、小程序体验版仍需按计划后续验收。
- 下一阶段应继续处理的事项：重新读取计划与日志后，进入 uni-app P0 链路，优先核查登录验证码、账号密码、微信授权的协议默认态与未勾选拦截。

## 2026-06-20 uni-app 登录协议 P0 收口

- 阶段名称：uni-app 登录协议 P0 收口
- 本阶段完成内容：核查 `pages/my/login`，确认验证码登录、账号密码登录、微信手机号授权入口均保持协议默认未勾选，且获取验证码、登录提交、微信授权前都会先执行协议勾选拦截；协议中心、用户协议、隐私政策入口仍可从登录页打开；登录成功后继续补记用户协议和隐私政策接受记录。同时修复 H5 本地发布脚本的“假成功”风险：当 HBuilderX CLI 未真正执行发布或产物未刷新时，命令会失败并阻止同步旧包。
- 修改/新增的主要文件：`zflUniApp/zflUniApp/scripts/build-h5-local.mjs`
- 运行或测试结果：`npm run runtime:agreement-audit` 通过，结果为 PASS 4 / WARN 0 / FAIL 0；`npm run env:local:check` 通过；`node --check scripts/build-h5-local.mjs` 通过；首次 `npm run build:h5:local` 正确拦截未打开 HBuilderX 的假成功；执行 `D:\HBuilderX\cli.exe open --project ...` 后再次运行 `npm run build:h5:local` 成功，H5 产物已同步到 `public/app`。构建仅提示 Browserslist 数据过期和部分资源体积较大。
- 遗留问题：本阶段为源码审计与 H5 本地构建验证，尚未在浏览器里真实点击登录页协议勾选/未勾选拦截；小程序微信授权仍需体验版或开发者工具专项验证。
- 下一阶段应继续处理的事项：重新读取计划与日志后，进入 `uni-app 协议中心 / 用户协议 / 隐私政策` P0 小阶段，检查协议列表、协议详情、返回路径和待补记重试状态。

## 2026-06-20 uni-app 协议中心与协议详情 P0 收口

- 阶段名称：uni-app 协议中心与协议详情 P0 收口
- 本阶段完成内容：核查 `pages/system/accord-center` 与 `pages/system/accord`，确认协议中心具备协议状态刷新、待补记读取、一键重试、未登录跳转登录并返回协议中心、用户协议/隐私政策等详情入口；协议详情页具备协议标识缺失承接、返回协议中心、返回上一页或首页兜底、正文加载、标题回显和加载失败提示。将协议中心/协议详情纳入 `runtime:agreement-audit` 自动审计，避免后续改动破坏 P0 协议入口。
- 修改/新增的主要文件：`zflUniApp/zflUniApp/scripts/agreement-flow-audit.mjs`
- 运行或测试结果：`node --check scripts/agreement-flow-audit.mjs` 通过；`npm run runtime:agreement-audit` 通过，结果为 PASS 5 / WARN 0 / FAIL 0，新增 `accord-pages-flow` 检查通过。
- 遗留问题：本阶段为源码和自动审计收口，尚未在浏览器真实打开协议中心、用户协议、隐私政策并检查接口内容；该项需在最终 H5 主流程浏览器验收中覆盖。
- 下一阶段应继续处理的事项：重新读取计划与日志后，进入 `uni-app 结算提交协议 P0` 小阶段，检查售后/退货说明默认未勾选、未勾选拦截提交、协议入口和补记逻辑。

## 2026-06-20 uni-app 结算提交协议 P0 收口

- 阶段名称：uni-app 结算提交协议 P0 收口
- 本阶段完成内容：核查 `pages/goods/settlement`，确认售后/退货说明 `agreeAfterSales` 默认未勾选；页面显示协议入口与未勾选提醒；提交订单前先完成收货/自提、支付方式、凭证图片等校验，再校验售后协议，未勾选时直接提示并返回，不进入确认弹窗、不调用下单接口；勾选后才进入环境二次确认，并在真正提交订单前调用 `ensureAcceptAccords` 补记 `after_sales_policy`。
- 修改/新增的主要文件：无业务代码修改
- 运行或测试结果：`npm run runtime:agreement-audit` 通过，结果为 PASS 5 / WARN 0 / FAIL 0，其中 `settlement-flow` 检查通过。
- 遗留问题：本阶段未连接测试库创建真实结算订单；最终 H5 主流程验收需实际进入结算页，验证未勾选拦截、勾选后弹出确认、凭证支付/在线支付入口表现。
- 下一阶段应继续处理的事项：重新读取计划与日志后，进入 `uni-app 商家入驻协议 P0` 小阶段，检查免责声明默认未勾选、未勾选拦截提交、协议入口和补记逻辑。

## 2026-06-20 uni-app 商家入驻协议 P0 收口

- 阶段名称：uni-app 商家入驻协议 P0 收口
- 本阶段完成内容：核查 `pages/merchant/apply`，确认免责声明 `agreeDisclaimer` 默认未勾选；页面提供《免责声明》协议详情入口；提交商家入驻或修改重提前，先校验商户名称、用户姓名、手机号、收款信息和资质图片，再校验免责声明，未勾选时直接提示并返回，不进入确认弹窗、不调用入驻接口；勾选后才进入环境二次确认，并在提交前通过 `bestEffortAcceptAccords` 补记 `disclaimer`。
- 修改/新增的主要文件：无业务代码修改
- 运行或测试结果：`npm run runtime:agreement-audit` 通过，结果为 PASS 5 / WARN 0 / FAIL 0，其中 `merchant-apply-flow` 检查通过。
- 遗留问题：本阶段未连接测试库真实提交商家入驻资料；最终 H5 主流程验收需覆盖未勾选拦截、勾选后二次确认、资料提交成功/失败回显。
- 下一阶段应继续处理的事项：重新读取计划与日志后，进入 `uni-app 首页/商品/购物车 P1` 或 `订单列表/详情 P1` 等计划内核心浏览链路，优先检查 H5 主流程可达性，为最终浏览器验收做准备。

## 2026-06-20 uni-app 数据隔离与环境预检收口

- 阶段名称：uni-app 数据隔离与环境预检收口
- 本阶段完成内容：将本机私有环境配置中的 `gray` 从示例域名更新为 `http://gray.413.chaimen666.com` 与 `http://gray.413.chaimen666.com/api`；将 `test` 更新为本地联调地址，明确仅用于本机独立测试库联调，避免继续使用 `example.com` 造成误判。确认 `prod` 保持 `https://413.chaimen666.com` 与 `https://413.chaimen666.com/api`，且 `gray` 与 `prod` 域名隔离。
- 修改/新增的主要文件：`zflUniApp/zflUniApp/config/env.profile.local.json`（Git 忽略的本机私有配置，不进入提交）
- 运行或测试结果：`npm run env:local:check` 通过；`npm run env:isolation` 通过但有 1 个 WARN，提示 test 仍是本地地址；`npm run validate:env:test`、`npm run validate:env:gray`、`npm run validate:env:prod` 均通过非严格校验；`npm run release:preflight:gray` 通过，PASS 6 / FAIL 0；`npm run release:preflight:prod` 通过，PASS 6 / FAIL 0；`npm run release:preflight:test` 失败，原因是严格校验不允许 test 继续指向本地地址或复用 dev/local 地址。
- 遗留问题：缺少独立真实测试域名/测试后端地址，导致 test 发布预检不能通过；这不是代码问题，需提供真实 test base/api 后更新本机私有配置并重跑 `npm run release:preflight:test`。灰度与正式预检已具备继续验收条件。
- 下一阶段应继续处理的事项：重新读取计划与日志后，进入 `uni-app 首页/商品/购物车 P1` 核心浏览链路，检查 H5 页面可达、列表/详情/购物车入口和环境提示覆盖。

## 2026-06-20 uni-app 首页/商品/购物车 P1 可达性收口

- 阶段名称：uni-app 首页/商品/购物车 P1 可达性收口
- 本阶段完成内容：通过运行态检查确认首页、我的页、发布页、首页商品池、商品列表、商品详情、购物车和订单列表均已注册或纳入关键流环境覆盖；确认这些核心浏览链路已接入环境提示与发布阶段提示，为后续 H5 浏览器主流程验收做准备。
- 修改/新增的主要文件：无业务代码修改
- 运行或测试结果：`npm run runtime:readiness` 通过，结果为 PASS 17 / WARN 0 / FAIL 0；`npm run build:h5:local` 成功，H5 产物已同步到 `public/app`，构建仅提示 Browserslist 数据过期和部分静态资源体积较大。
- 遗留问题：本阶段仍是静态与构建验收，尚未用浏览器真实点击首页、商品详情、购物车和结算入口；最终 H5 主流程验收需覆盖这些交互。
- 下一阶段应继续处理的事项：重新读取计划与日志后，进入 `uni-app 订单列表/详情 P1` 小阶段，检查订单列表/状态展示/详情尾链路的路由和环境提示覆盖。

## 2026-06-20 uni-app 订单列表/详情 P1 收口

- 阶段名称：uni-app 订单列表/详情 P1 收口
- 本阶段完成内容：确认项目没有独立 `pages/order/details` 页面，订单详情能力由订单列表商品明细与物流、评价、售后尾链路页面承接；为订单列表补齐商品明细和接口返回缺字段时的安全兜底，避免 `detaileds/goods` 偶发缺失导致页面卡住；将订单列表、订单核销、物流详情、订单评价、售后详情路由和订单列表状态/筛选/支付/取消/收货/尾链路入口纳入 `runtime:readiness` 自动审计。
- 修改/新增的主要文件：`zflUniApp/zflUniApp/pages/order/list.vue`、`zflUniApp/zflUniApp/scripts/runtime-readiness-report.mjs`
- 运行或测试结果：`node --check scripts/runtime-readiness-report.mjs` 通过；`npm run runtime:readiness` 通过，结果为 PASS 24 / WARN 0 / FAIL 0；`npm run build:h5:local` 成功，H5 产物已同步到 `public/app`，构建仅提示 Browserslist 数据过期和部分静态资源体积较大。
- 遗留问题：本阶段仍未接真实测试库创建订单后在浏览器里检查列表、状态和尾链路页面；最终 H5 主流程验收需覆盖下单后回到订单列表、筛选状态、查看物流/评价/售后入口。
- 下一阶段应继续处理的事项：重新读取计划与日志后，进入 `uni-app H5 与小程序一致性 P0` 或灰度上线核对项；若需要小程序体验版，需要可用的小程序构建/体验版环境配合。

## 2026-06-20 uni-app H5 与小程序一致性 P0 收口

- 阶段名称：uni-app H5 与小程序一致性 P0 收口
- 本阶段完成内容：核查业务源码中的 H5/小程序条件编译，确认登录页的小程序微信手机号授权入口在授权前仍先执行协议勾选拦截，H5/非小程序端会显示微信授权兜底并切回验证码登录；结算页、商家入驻页、协议中心、协议详情和订单列表未发现 H5/小程序端特有分支绕过协议或核心展示逻辑。将跨端登录一致性、结算/入驻无端特有协议分支、核心页无端特有分支纳入 `runtime:agreement-audit` 自动审计。
- 修改/新增的主要文件：`zflUniApp/zflUniApp/scripts/agreement-flow-audit.mjs`
- 运行或测试结果：`node --check scripts/agreement-flow-audit.mjs` 通过；`npm run runtime:agreement-audit` 通过，结果为 PASS 7 / WARN 0 / FAIL 0；`npm run runtime:readiness` 通过，结果为 PASS 24 / WARN 0 / FAIL 0；`D:\HBuilderX\cli.exe launch mp-weixin --project ... --compile true --continue-on-error false` 微信小程序本地编译成功且未上传；`npm run build:h5:local` 成功，H5 产物已同步到 `public/app`。构建仍仅提示 Browserslist 数据过期、运行模式包体积/性能提醒和部分静态资源体积较大。
- 遗留问题：本阶段完成了源码一致性审计与 H5/微信小程序本地编译验证，但没有在微信开发者工具体验版中用同一账号实际点击登录、协议、结算和入驻全流程；体验版实机验证仍需小程序测试账号/开发者工具环境配合。
- 下一阶段应继续处理的事项：重新读取计划与日志后，进入灰度上线核对项，优先验证后台灰度目录可用与 H5 灰度入口可用；小程序体验版可用项需在具备体验版环境后继续补验。

## 2026-06-20 后台灰度目录可用核对与商品分类路由收口

- 阶段名称：后台灰度目录可用核对与商品分类路由收口
- 本阶段完成内容：使用右侧浏览器访问 `http://gray.413.chaimen666.com/admin-next/`，确认灰度后台登录页可打开且静态资源无前端 error；使用 `admin` 测试账号完成真实表单登录后进入控制台，抽查控制台、商品管理、订单管理、会员管理、商家管理、协议管理、商家采购对账等计划内关键页面，均无白屏和控制台 error。核对时发现计划入口 `/goods/type` 在当前灰度包中无法直接进入商品分类，而实际旧菜单路径 `/goods/GoodsType` 可进入；已在权限路由映射中补齐 `/goods/GoodsType` 到 `/goods/type` 的兼容，保证后端旧菜单和计划验收路径统一。
- 修改/新增的主要文件：`zflAdminWeb/src/store/modules/permission.js`
- 运行或测试结果：`npx eslint src/store/modules/permission.js --fix` 通过；`npm run build:admin-next-local` 通过，仅保留既有 outDir 提示；本地浏览器验证 `http://127.0.0.1:807/admin-next/#/goods/type` 可打开商品分类、无白屏、无前端 error。灰度浏览器验证结果：登录成功，`#/dashboard`、`#/goods/goods`、`#/order/order`、`#/member/member`、`#/merchant/merchant`、`#/setting/accord`、`#/report/merchant-purchase-ledger` 可达；当前灰度包中的 `/goods/type` 仍需重新发布后复验。
- 遗留问题：本机已修复并验证商品分类计划路径，但服务器灰度目录尚未重新发布最新后台包，因此灰度站点当前仍没有体现 `/goods/type` 修复；需要将最新 `admin-next` 包发布到灰度后再复验该路径。订单页文字中已能检索到“待支付审核”，但本阶段未做写操作和支付审核流转，只做灰度只读冒烟。
- 下一阶段应继续处理的事项：重新读取计划与日志后，继续灰度上线核对项；优先验证 H5 灰度入口可用，同时在具备灰度发布条件后复验后台 `/goods/type`。

## 2026-06-20 H5 灰度入口可用核对与灰度构建收口

- 阶段名称：H5 灰度入口可用核对与灰度构建收口
- 本阶段完成内容：使用右侧浏览器访问 `http://gray.413.chaimen666.com/app/`，确认当前服务器 H5 灰度入口可打开但不是可通过状态：页面显示“测试环境/占位域名”，接口地址仍出现 `https://test.example.com/api`，并触发多条 `[wxapp.request.fail]`。为避免再次把测试占位包发到灰度，已将 H5 运行时增加 `gray.413.chaimen666.com` 主机自动识别为灰度环境，并新增 `build:h5:gray` 灰度构建命令；该命令会先做严格 gray 环境校验，再用 HBuilderX CLI 发布 H5，复制到独立 `dist-h5-gray` 目录，并检查发布包不能包含真实 `http(s)://*.example.com` 占位地址。
- 修改/新增的主要文件：`zflUniApp/zflUniApp/config/env.js`、`zflUniApp/zflUniApp/package.json`、`zflUniApp/zflUniApp/scripts/build-h5-release.mjs`
- 运行或测试结果：`node --check scripts/build-h5-release.mjs` 通过；`npm run release:preflight:gray` 通过，PASS 6 / FAIL 0；首次 `npm run build:h5:gray` 成功构建但被安全检查误拦截，原因是代码中保留了用于识别示例域名的 `.example.com` 字符串；已修正为只拦截真实 URL 形式后，第二次 `npm run build:h5:gray` 通过并生成 `zflUniApp/zflUniApp/dist-h5-gray`。本地模拟 `/app/` 目录预览通过：`http://127.0.0.1:18090/app/` 首页可打开、构建包包含 `gray.413.chaimen666.com`、不包含真实 `example.com` URL、无控制台 error；通过真实交互进入“我的”页和登录页，确认登录页协议状态默认为未勾选，点击“验证码登录”会显示“请先同意用户协议和隐私政策”并被前端拦截。已生成可上传压缩包：`runtime/release-artifacts/h5-gray-dist-20260620-085811.zip`。
- 遗留问题：服务器 `http://gray.413.chaimen666.com/app/` 当前仍是旧 H5 包，尚未部署本次生成的 `dist-h5-gray`，因此真实灰度入口仍显示测试占位环境并有接口请求失败；需要把 `dist-h5-gray` 内容覆盖到灰度站点 `/public/app/` 后，再用浏览器复验灰度 H5 首页、登录协议、商品浏览和结算入口。小程序体验版真机验证仍未执行。
- 下一阶段应继续处理的事项：先发布后台与 H5 的最新灰度包并复验：后台需复验 `/admin-next/#/goods/type`，H5 需复验 `/app/` 是否显示灰度环境并消除 `test.example.com` 请求；复验通过后再继续小程序体验版与数据不误写正式库核对。

## 2026-06-20 后台与 H5 最新灰度包发布准备（服务器权限阻塞）

- 阶段名称：后台与 H5 最新灰度包发布准备（服务器权限阻塞）
- 本阶段完成内容：为后台新增独立 `admin-next-gray` 构建模式，灰度后台包明确指向 `http://gray.413.chaimen666.com`、输出到 `dist-admin-next-gray`，避免继续混用正式 `online` 包；修复后台覆盖率脚本对已承接路由的别名识别，使商品分类 `/goods/type` 与商家采购对账 `/report/merchant-purchase-ledger` 按真实页面承接验收；生成后台灰度发布压缩包，H5 灰度包沿用上一阶段已生成的通过包。
- 修改/新增的主要文件：`zflAdminWeb/.env.admin-next-gray`、`zflAdminWeb/package.json`、`zflAdminWeb/scripts/validate-admin-next-env.mjs`、`zflAdminWeb/scripts/release-preflight-admin-next.mjs`、`zflAdminWeb/scripts/build-admin-next-coverage.mjs`；新增发布产物 `runtime/release-artifacts/admin-next-gray-dist-20260620-090847.zip`。
- 运行或测试结果：`node --check scripts/validate-admin-next-env.mjs` 通过；`node --check scripts/release-preflight-admin-next.mjs` 通过；`npm run validate:admin-next-gray` 通过；`npm run build:admin-next-gray` 通过并生成 `dist-admin-next-gray`；修复覆盖脚本后 `node scripts/build-admin-next-coverage.mjs` 结果为 `total=52 ready=52 missing=0 legacy=0`；`npm run release:preflight:admin-next-gray` 通过，PASS 3 / FAIL 0；运行时配置文件确认 `baseUrl=http://gray.413.chaimen666.com`、环境标识为“灰度环境/灰度隔离”；压缩包生成成功，大小约 1.94 MB。
- 遗留问题：本机无法直接发布到服务器。`gray.413.chaimen666.com:22` 端口可达，域名扫描到的 SSH 指纹与本机已信任的 `47.239.156.27` 指纹一致；使用已信任 IP 指纹别名后，SSH 失败从 `Host key verification failed` 变为 `Permission denied (publickey,gssapi-keyex,gssapi-with-mic,password)`，说明当前本机没有可用的服务器登录凭据/免密密钥。因无法上传并替换服务器 `/public/admin-next` 与 `/public/app`，真实灰度浏览器复验暂不能完成。
- 下一阶段应继续处理的事项：需要先恢复可用的服务器部署通道（例如在宝塔终端手动上传解压这两个 zip，或给本机配置可用 SSH 密钥/账号）。部署完成后继续本计划内灰度核对：复验 `http://gray.413.chaimen666.com/admin-next/#/goods/type`，复验 `http://gray.413.chaimen666.com/app/` 不再出现 `test.example.com`，并继续小程序体验版与数据不误写正式库核对。

## 2026-06-20 本地部署主流程浏览器验收与购物车缺图兜底

- 阶段名称：本地部署主流程浏览器验收与购物车缺图兜底
- 本阶段完成内容：按本地部署优先原则完成 `http://127.0.0.1:807` 右侧浏览器主流程验收。后台自动巡检通过后，H5 真实登录 `codex0411test / 123456`，验证协议未勾选拦截、勾选后登录、刷新保持登录态；浏览商品列表、商品详情、加入购物车、购物车勾选、进入结算、线下凭证提交前校验、订单列表回显；使用本地接口补测带凭证下单，生成测试订单 `2606201055417701`，并在后台订单管理中验证平台角色可看到该订单、`待核销/待支付审核` 按钮可筛出待审核凭证订单。商家端本地入口 `/merchant/` 完成登录冒烟，商家订单与文件页可达。
- 修改/新增的主要文件：`app/common/service/member/MemberShopCartService.php`、`DEVELOPMENT_LOG.md`
- 运行或测试结果：`npm run check:local-stack` 通过；`npm run runtime:agreement-audit` 通过，PASS 7 / FAIL 0；`npm run runtime:readiness` 通过，PASS 24 / FAIL 0；`npm run audit:admin-next` 通过，8/8；`npm run report:admin-next-coverage` 通过，`total=52 ready=52 missing=0 legacy=0`；`php -l app/common/service/member/MemberShopCartService.php` 通过；`php think clear` 通过。浏览器验证无前端 console error：H5 登录页、商品列表、商品详情、购物车、结算页、订单列表、后台订单页、后台待支付审核筛选、商家端登录/订单/文件页均可打开。
- 浏览器验收结果：验证时间为 2026-06-20 10:30-11:05；验证环境为本地 `127.0.0.1:807` + 本地 MySQL `3310` + 数据库 `taoguan_online_base`；主要步骤包括后台登录/订单筛选、H5 登录协议拦截、C 端账号登录、商品 `1144` 加购、购物车勾选、结算页凭证/协议拦截、接口提交线下凭证订单、H5 订单列表回显、后台订单待审核筛选、商家端登录冒烟；输入测试数据包括会员账号 `codex0411test`、后台账号 `admin`、商家账号 `15696934319`、测试联系人 `本地验收用户`、手机号 `13800138000`、凭证文件 ID `1110`、订单号 `2606201055417701`。
- 本阶段发现并修复的问题：购物车列表接口遇到缺失商家文件 ID `1107` 时直接抛出 `文件不存在`，导致购物车页面显示为空；已改为 `MerchantFileService::info($image_id, false)`，缺图时返回空图片地址但不阻断购物车列表。
- 遗留问题：浏览器自动化当前不能直接操作系统文件选择框，因此“上传支付凭证”通过接口补测完成，非纯浏览器上传；本地商家端首页首次进入曾出现一次 `only array cache can be push` 提示，但订单/文件关键页可达且无 console error；小程序体验版真机/开发者工具点击链路仍未验收；服务器灰度部署仍受 SSH/上传通道限制。
- 是否达到可上线运营标准：本地部署范围已达到继续灰度部署前的主流程验收条件；但完整上线运营标准仍需完成灰度后台/H5 真实部署复验、小程序体验版验证、正式切换回滚演练。
- 下一阶段应继续处理的事项：重新读取计划与日志后，继续计划内灰度上线核对；优先恢复服务器部署通道并发布最新后台/H5 灰度包，再复验灰度 `/admin-next/`、`/app/`、小程序体验版和数据不误写正式库。

## 2026-06-20 admin-next 数据中心 P1 收口

- 阶段名称：admin-next 数据中心 P1 收口
- 本阶段完成内容：按计划内 `/analytics` 数据中心小阶段核查页面承接、筛选记忆、前进/后退、接口异常兜底和移动端布局；发现页面业务逻辑可用但单文件格式校验失败，已使用项目 ESLint/Prettier 规则自动格式化，保证后续维护和构建一致。
- 修改/新增的主要文件：`zflAdminWeb/src/views/report/PlatformAnalytics.vue`、`DEVELOPMENT_LOG.md`
- 运行或测试结果：`npm run check:local-stack` 通过；`npx eslint src/views/report/PlatformAnalytics.vue --fix` 通过；`npx eslint src/views/report/PlatformAnalytics.vue` 通过；`npx playwright test tests/admin-next-dev-audit.spec.js -g "analytics"` 通过，2/2；`npm run build:admin-next-local` 通过，仍仅有 outDir 位于项目外的既有提示。
- 遗留问题：本阶段以本地自动巡检为主，未对数据中心执行正式/灰度环境真实浏览器复验；服务器灰度部署通道仍未恢复，小程序体验版仍未真机验收。
- 下一阶段应继续处理的事项：重新读取计划与日志后，继续计划内 P1，进入 `admin-next 导出中心` 小阶段，检查 `/exports` 导出创建/下载、历史记录、异常提示、权限态和移动端布局。

## 2026-06-20 admin-next 导出中心 P1 收口

- 阶段名称：admin-next 导出中心 P1 收口
- 本阶段完成内容：按计划内 `/exports` 导出中心小阶段核查导出创建/下载、历史记录、筛选记忆、权限态和移动端布局；自动巡检已验证可触发 CSV 下载并保留导出历史。发现页面业务巡检通过但单文件格式校验失败，已使用项目 ESLint/Prettier 规则自动格式化，未改业务逻辑。
- 修改/新增的主要文件：`zflAdminWeb/src/views/report/PlatformExport.vue`、`DEVELOPMENT_LOG.md`
- 运行或测试结果：`npx eslint src/views/report/PlatformExport.vue --fix` 通过；`npx eslint src/views/report/PlatformExport.vue` 通过；`npx playwright test tests/admin-next-dev-audit.spec.js -g "exports downloads|mobile layout"` 通过，2/2；`npm run build:admin-next-local` 通过，仍仅有 outDir 位于项目外的既有提示。
- 遗留问题：本阶段以本地自动巡检为主，未在灰度/正式环境执行真实 CSV 下载复验；导出属于敏感运营动作，正式环境只应做只读或明确授权后的低风险验证。
- 下一阶段应继续处理的事项：重新读取计划与日志后，继续计划内 P1，进入 `admin-next 系统用户/角色/菜单` 小阶段，检查 `/system/user`、`/system/role`、`/system/menu` 增删改查、授权关系、回显提示和路由权限稳定性。

## 2026-06-20 admin-next 系统用户/角色/菜单 P1 收口

- 阶段名称：admin-next 系统用户/角色/菜单 P1 收口
- 本阶段完成内容：按计划内 `/system/user`、`/system/role`、`/system/menu` 小阶段完成本地权限链路自动验收。新增后台巡检用例会在本地库创建临时菜单、角色、用户，依次验证菜单新增/编辑/禁用/删除、角色新增/编辑/菜单授权/禁用/删除、用户新增/编辑/绑定角色/禁用/删除，并交叉验证“角色下用户”和“菜单下角色”关系；用例结束会自动清理临时数据。同步修正数据中心异常兜底用例的等待方式，避免 Vue 异步渲染稍慢时误报白屏。
- 修改/新增的主要文件：`zflAdminWeb/tests/admin-next-dev-audit.spec.js`、`zflAdminWeb/src/views/system/user.vue`、`zflAdminWeb/src/views/system/role.vue`、`zflAdminWeb/src/views/system/menu.vue`、`DEVELOPMENT_LOG.md`
- 运行或测试结果：`npm run check:local-stack` 通过；`npx eslint tests/admin-next-dev-audit.spec.js src/views/system/user.vue src/views/system/role.vue src/views/system/menu.vue` 通过；`npx playwright test tests/admin-next-dev-audit.spec.js -g "system user role menu"` 通过，1/1；`npm run build:admin-next-local` 通过，仍仅有 outDir 位于项目外的既有提示；首次完整 `npm run audit:admin-next` 中新增系统权限用例通过，但既有数据中心兜底用例因等待标题不够稳定失败，已改为等待明确故障提示和标题后复跑；最终 `npm run audit:admin-next` 通过，9/9。
- 遗留问题：本阶段通过本地自动化覆盖了系统权限模块的安全 CRUD 和授权关系，但未在正式/灰度环境执行写操作；正式/灰度仍只应做只读或经明确授权的低风险验证。新增用例会清理临时用户/角色/菜单，不保留权限测试账号。
- 下一阶段应继续处理的事项：重新读取计划与日志后，继续计划内 P1，进入 `admin-next legacy 承接` 小阶段，检查 `/legacy/*` 旧模块 iframe 可打开、独立打开可用、登录态桥接不影响新后台。

## 2026-06-20 admin-next legacy 承接 P1 收口

- 阶段名称：admin-next legacy 承接 P1 收口
- 本阶段完成内容：按计划内 `/legacy/*` 小阶段完成本地旧模块承接验收。新增后台巡检用例覆盖两个典型旧路径：`/legacy/member/member?from=dashboard` 会识别到新页承接并可点击“优先去新页”，`/legacy/trace/batch?from=dashboard` 会保持旧后台 iframe 兜底；同时验证 iframe 地址指向本地旧后台 `/admin/#/...`、点击“在旧后台独立打开”能打开独立旧后台页、回到新后台后 `AdminToken` 未丢失且控制台仍可访问。
- 修改/新增的主要文件：`zflAdminWeb/tests/admin-next-dev-audit.spec.js`、`DEVELOPMENT_LOG.md`
- 运行或测试结果：`npx eslint tests/admin-next-dev-audit.spec.js` 通过；`npx playwright test tests/admin-next-dev-audit.spec.js -g "legacy carrier"` 通过，1/1；`npm run audit:admin-next` 通过，10/10；`npm run report:admin-next-coverage` 通过，`total=52 ready=52 missing=0 legacy=0`，说明当前后端菜单路由均已有源码页承接，静态 legacy 兜底入口仍可用。
- 遗留问题：本阶段验证的是本地 `/admin-next/` 与本地旧后台 `/admin/`；灰度/正式目录仍需在服务器部署最新包后只读复验。当前覆盖报告 `legacy=0` 并不代表没有静态 legacy 承接页，而是后端菜单映射已全部承接到源码页。
- 下一阶段应继续处理的事项：重新读取计划与日志后，继续计划内灰度上线核对或数据隔离核对；优先处理“数据不误写正式库”和灰度部署通道阻塞。

## 2026-06-20 数据不误写正式库 / 环境隔离核对

- 阶段名称：数据不误写正式库 / 环境隔离核对
- 本阶段完成内容：按计划内“数据不误写正式库”核对后台与 uni-app 的环境配置、发布预检和构建产物占位域名残留；确认后台 local/gray/online 三类构建预检均通过，uni-app gray/prod 发布预检均通过，gray 与 prod 域名隔离，prod 地址保持正式域名，test 当前仍是本地地址且严格发布检查会主动失败，避免把本地测试包误当作可发布测试包。
- 修改/新增的主要文件：`DEVELOPMENT_LOG.md`；运行命令刷新了 `runtime/admin-next-release-preflight/*.latest.*` 与 `zflUniApp/zflUniApp/runtime/*/*.latest.*` 报告文件。
- 运行或测试结果：`npm run release:preflight:admin-next-local` 通过，PASS 6 / FAIL 0，包含本地栈检查、构建和 `audit:admin-next`；`npm run release:preflight:admin-next-gray` 通过，PASS 3 / FAIL 0；`npm run release:preflight:admin-next-online` 通过，PASS 3 / FAIL 0；`npm run release:preflight:gray` 通过，PASS 6 / FAIL 0；`npm run release:preflight:prod` 通过，PASS 6 / FAIL 0；`npm run release:check:test` 按预期失败，原因是 test 仍指向 `127.0.0.1` 且复用 dev/local 地址，仅适合本机联调，不可发布。静态扫描 `public/admin-next`、`public/app`、`zflAdminWeb/dist-admin-next-gray`、`zflAdminWeb/dist-admin-next-online`、`zflUniApp/zflUniApp/dist-h5-gray` 中 `test.example.com` / `gray.example.com` / 真实 `example.com` URL 命中数均为 0；当前 `runtime/release-artifacts` 目录没有可扫描 zip 包。
- 遗留问题：缺少独立真实 test 域名/测试后端，因此 test 严格发布预检不能通过；服务器灰度部署通道仍未恢复，无法在本机直接替换灰度站点后复验；小程序体验版真实点击链路仍未验收。
- 下一阶段应继续处理的事项：重新读取计划与日志后，继续计划内灰度上线核对；若服务器部署通道仍不可用，则先推进本地可验证的小程序构建/体验版准备项，并把真实体验版发布阻塞原因记录清楚。

## 2026-06-20 小程序体验版灰度候选包收口

- 阶段名称：小程序体验版灰度候选包收口
- 本阶段完成内容：检查微信小程序 AppID 与项目配置，确认 `manifest.json` 与 `project.config.json` 均使用 `wx092092834bc690a8`；发现直接通过 HBuilderX `publish mp-weixin` 时自定义环境变量不会固化进包，小程序发行包会默认落到 `test`，而当前 `test` 是本地地址，不适合作为体验版。已为 uni-app 增加临时构建 profile 机制和 `build:mp-weixin:gray` 命令，生成小程序灰度候选包时会先严格校验 gray 环境，再临时写入 `config/env.build-profile.json`，发行成功后复制到 `dist-mp-weixin-gray` 并自动清理临时文件，确保体验版候选包默认走灰度。
- 修改/新增的主要文件：`zflUniApp/zflUniApp/config/env.js`、`zflUniApp/zflUniApp/package.json`、`zflUniApp/zflUniApp/.gitignore`、`zflUniApp/zflUniApp/scripts/build-mp-weixin-release.mjs`、`DEVELOPMENT_LOG.md`；新增本地构建产物目录 `zflUniApp/zflUniApp/dist-mp-weixin-gray`。
- 运行或测试结果：`node --check scripts/build-mp-weixin-release.mjs` 通过；`node --check config/env.js` 通过；`npm run build:mp-weixin:gray` 通过，严格校验 `gray` 为 `http://gray.413.chaimen666.com` / `http://gray.413.chaimen666.com/api`，HBuilderX 成功导出微信小程序到 `unpackage/dist/build/mp-weixin`，并复制为 `dist-mp-weixin-gray`；`npm run runtime:agreement-audit` 通过，PASS 7 / WARN 0 / FAIL 0；`npm run runtime:readiness` 通过，PASS 24 / WARN 0 / FAIL 0；扫描 `dist-mp-weixin-gray`：`test.example.com` 0、`gray.example.com` 0、真实 `example.com` URL 0、`"profile":"gray"` 1、`http://gray.413.chaimen666.com` 2；临时 `config/env.build-profile.json` 已自动清理。候选包文件数 224，总大小约 2.86 MB，最新时间 2026-06-20 12:14。
- 遗留问题：HBuilderX 已打开微信开发者工具并提示“请在微信小程序开发者工具中点击上传”，但当前本机没有微信上传密钥/机器人配置，也不能代替管理员确认上传体验版，因此尚未完成真实体验版上传与手机真机点击验收；这一步需要微信开发者工具登录且有该小程序上传权限，或提供微信 CI 上传密钥后再自动化。
- 下一阶段应继续处理的事项：重新读取计划与日志后，继续灰度上线核对；若仍无法获得服务器部署通道和小程序上传权限，则应记录真实阻塞，避免把本地候选包误判为完整上线验收通过。

## 2026-06-20 灰度发布包刷新与上传阻塞收口

- 阶段名称：灰度发布包刷新与上传阻塞收口
- 本阶段完成内容：重新构建后台灰度包、H5 灰度包和微信小程序灰度候选包，并生成可交付压缩包；修正小程序构建 profile 机制的默认文件处理，新增空的 `config/env.build-profile.json`，避免 H5 构建因找不到该文件产生噪音 warning，小程序构建时临时写入 gray 后自动还原默认空配置。
- 修改/新增的主要文件：`zflUniApp/zflUniApp/config/env.build-profile.json`、`zflUniApp/zflUniApp/scripts/build-mp-weixin-release.mjs`、`DEVELOPMENT_LOG.md`；刷新构建产物 `zflAdminWeb/dist-admin-next-gray`、`zflUniApp/zflUniApp/dist-h5-gray`、`zflUniApp/zflUniApp/dist-mp-weixin-gray`；新增压缩包 `runtime/release-artifacts/admin-next-gray-20260620-122029.zip`、`runtime/release-artifacts/h5-gray-20260620-122029.zip`、`runtime/release-artifacts/mp-weixin-gray-20260620-122029.zip`。
- 运行或测试结果：`npm run build:admin-next-gray` 通过；`npm run build:h5:gray` 通过，已无 `env.build-profile.json` 缺失 warning，仅保留 Browserslist 过期和资源体积提醒；`npm run build:mp-weixin:gray` 通过，并生成 `dist-mp-weixin-gray`；三份 zip 均生成成功，大小分别约 3.54 MB、1.36 MB、1.13 MB；zip 扫描结果：真实 `example.com` URL 命中均为 0，后台灰度包包含灰度域名文件 15 个，H5 灰度包包含灰度域名文件 1 个且含 gray profile 标识 1 个，小程序灰度包包含灰度域名文件 1 个且含 gray profile 标识 1 个；`npm run release:preflight:admin-next-gray` 通过，PASS 3 / FAIL 0；`npm run release:preflight:gray` 通过，PASS 6 / FAIL 0。
- 遗留问题：当前只能完成本地灰度发布包准备，无法直接部署到 `gray.413.chaimen666.com` 服务器目录，也无法在微信小程序平台上传体验版；服务器上传仍需要可用 SSH/宝塔文件管理权限，小程序体验版仍需要微信开发者工具登录且有上传权限或微信 CI 上传密钥。由于灰度后台/H5尚未替换服务器文件，真实灰度浏览器复验仍不能关闭。
- 下一阶段应继续处理的事项：获得服务器上传权限后，将 `admin-next-gray-20260620-122029.zip` 解压覆盖灰度站点 `public/admin-next`，将 `h5-gray-20260620-122029.zip` 解压覆盖灰度站点 `public/app`，再复验 `http://gray.413.chaimen666.com/admin-next/#/goods/type` 与 `http://gray.413.chaimen666.com/app/`；获得小程序上传权限后，用微信开发者工具上传 `mp-weixin-gray-20260620-122029.zip` 对应的 `dist-mp-weixin-gray` 体验版并进行真机登录/协议/结算/入驻验收。

## 2026-06-20 小程序体验版上传瘦身与上传验证

- 阶段名称：小程序体验版上传瘦身与上传验证
- 本阶段完成内容：继续计划内“小程序体验版可用”核对。微信开发者工具 CLI 已登录，但首次上传灰度小程序候选包失败，错误为 `80051 source size 2384KB exceed max limit 2MB`。已将小程序发布脚本改为导出后自动补齐上传忽略规则、关闭上传 sourceMap，并从小程序发布产物中移除运行期不需要的 SCSS 源文件，以及未被当前页面源码引用的 `static/images/home/banner.png`、`static/images/componentBg.png` 两张大图；源码图片本身未删除，H5 不受影响。重新构建后，`dist-mp-weixin-gray` 从约 2929KB 降到约 2125KB，最终微信 CLI 上传成功。
- 修改/新增的主要文件：`zflUniApp/zflUniApp/scripts/build-mp-weixin-release.mjs`、`zflUniApp/zflUniApp/project.config.json`、`DEVELOPMENT_LOG.md`；刷新产物 `zflUniApp/zflUniApp/dist-mp-weixin-gray`；新增发布包 `runtime/release-artifacts/mp-weixin-gray-20260620-124039.zip`；新增上传结果 `runtime/release-artifacts/mp-weixin-upload-20260620-123902.json`。
- 运行或测试结果：`node --check scripts/build-mp-weixin-release.mjs` 通过；`npm run runtime:agreement-audit` 通过，PASS 7 / FAIL 0；`npm run runtime:readiness` 通过，PASS 24 / FAIL 0；`npm run build:mp-weixin:gray` 通过；候选包检查为 `scssCount=0`、`bannerExists=False`、`componentBgExists=False`；微信开发者工具 CLI 上传版本 `0.0.202606201239` 成功，返回总包大小 `2045297` 字节；`runtime/release-artifacts/mp-weixin-gray-20260620-124039.zip` 生成成功，约 745KB，zip 扫描未发现 `.scss`、两张已清理大图或真实 `example.com` URL；`npm run release:preflight:gray` 通过，PASS 6 / FAIL 0。
- 遗留问题：小程序体验版已经上传成功，但尚未在手机微信体验版中完成真实点击验收；该项仍需用有体验权限的微信号打开体验版，验证登录协议、商品浏览、结算、订单、商家入驻等主链路。服务器灰度后台/H5部署通道仍未恢复，灰度浏览器复验还不能关闭。
- 下一阶段应继续处理的事项：重新读取计划与日志后，继续计划内灰度上线核对；优先做“灰度后台目录可用 / H5 灰度入口可用”的浏览器复验，如发现服务器仍未部署最新包或无可用上传通道，则记录真实阻塞并保留本地/小程序已通过结果。

## 2026-06-20 灰度后台与 H5 入口浏览器复验

- 阶段名称：灰度后台与 H5 入口浏览器复验
- 本阶段完成内容：按计划内“后台灰度目录可用 / H5 灰度入口可用”执行右侧浏览器只读冒烟。访问 `http://gray.413.chaimen666.com/admin-next/#/login` 后自动进入控制台，页面显示“灰度环境 / 灰度隔离”，控制台总览、菜单和静态资源可正常加载。继续访问 `http://gray.413.chaimen666.com/app/`，发现 H5 页面仍显示“测试环境”，页面文案包含 `https://test.example.com/api`、`当前环境仍使用示例域名`，且浏览器控制台出现多条 `[wxapp.request.fail] Object`，说明灰度服务器 `/app/` 仍是旧 H5 包或未覆盖为最新灰度包。
- 修改/新增的主要文件：`DEVELOPMENT_LOG.md`
- 运行或测试结果：浏览器复验 `http://gray.413.chaimen666.com/admin-next/#/dashboard` 通过，未采集到 error/warn；浏览器复验 `http://gray.413.chaimen666.com/app/` 未通过，页面仍指向测试占位环境并请求失败。此前本地生成的 `runtime/release-artifacts/h5-gray-20260620-122029.zip` 已通过灰度预检，但尚未部署到服务器灰度 `/app/` 目录。
- 遗留问题：当前本机没有可用 SSH/宝塔上传通道，无法直接把最新 H5 灰度包覆盖到 `gray.413.chaimen666.com`；H5 灰度入口因此不能关闭验收。小程序体验版已上传成功，但仍缺手机端真实体验版点击验收。
- 下一阶段应继续处理的事项：需要先恢复服务器部署通道，或在宝塔文件管理/服务器终端手动将最新 H5 灰度包解压覆盖灰度站 `/app/` 目录；部署完成后重新浏览器复验 H5 灰度入口，确认不再出现 `test.example.com`、页面环境为灰度、登录/商品/结算/订单主链路可用。

## 2026-06-20 灰度发布部署包与回退命令固化

- 阶段名称：灰度发布部署包与回退命令固化
- 本阶段完成内容：针对当前计划内灰度 H5 部署阻塞，将实际可用的灰度部署步骤固化进发布模板。新增“Current Gray Server Deploy Card”，明确需要上传的 `admin-next` 与 H5 灰度 zip、宝塔终端部署命令、服务器自检命令、浏览器验收标准和回滚命令；同步更新发布控制中心的当前事实与阻塞状态，避免继续显示“gray 还是示例域名”的旧信息。
- 修改/新增的主要文件：`RELEASE_EXECUTION_TEMPLATES.md`、`RELEASE_CONTROL_CENTER.md`、`DEVELOPMENT_LOG.md`
- 运行或测试结果：文档内容检查通过，确认 `RELEASE_EXECUTION_TEMPLATES.md` 已包含 `Current Gray Server Deploy Card`、`h5-gray-20260620-122029.zip`、`gray_release_rollback`、`test.example.com` 自检；确认 `RELEASE_CONTROL_CENTER.md` 已记录 `gray.413.chaimen666.com`、小程序体验版上传版本 `0.0.202606201239` 和 H5 灰度部署阻塞；确认 `runtime/release-artifacts/admin-next-gray-20260620-122029.zip`、`runtime/release-artifacts/h5-gray-20260620-122029.zip`、`runtime/release-artifacts/mp-weixin-upload-20260620-123902.json` 均存在。
- 遗留问题：文档和命令已准备好，但仍需要服务器侧上传/解压权限才能真正覆盖 `gray.413.chaimen666.com/public/app`；小程序体验版仍需手机端真实体验权限验证。
- 下一阶段应继续处理的事项：重新读取计划与日志后，如果服务器部署通道仍不可用，则应记录当前真实阻塞并停止；一旦用户在服务器执行部署卡命令或提供上传通道，继续浏览器复验 H5 灰度入口与全链路。

## 2026-06-20 灰度 H5 入口状态复核与阻塞确认

- 阶段名称：灰度 H5 入口状态复核与阻塞确认
- 本阶段完成内容：按计划内“灰度上线核对 / H5 灰度入口可用”继续复核线上灰度入口是否已部署最新包。通过网络请求确认 `http://gray.413.chaimen666.com/app/` 首页壳子可返回 200，但仍加载旧文件 `static/js/index.5cd614b9.js`；进一步检查该 JS 文件，确认仍包含 `test.example.com` 与 `gray.example.com`，且不包含 `gray.413.chaimen666.com`。本地最新 `dist-h5-gray` 应加载 `static/js/index.7190edee.js`，说明服务器灰度 `/app/` 目录尚未覆盖最新 H5 灰度包。
- 修改/新增的主要文件：`DEVELOPMENT_LOG.md`
- 运行或测试结果：`Invoke-WebRequest http://gray.413.chaimen666.com/app/` 返回 200；远端 `index.5cd614b9.js` 检查结果为 `HasTestExample=True`、`HasGrayExample=True`、`HasGrayHost=False`；本地 `zflUniApp/zflUniApp/dist-h5-gray/index.html` 指向 `index.7190edee.js`；`ssh -o BatchMode=yes root@gray.413.chaimen666.com` 仍返回 `Permission denied (publickey,gssapi-keyex,gssapi-with-mic,password)`。
- 遗留问题：灰度 H5 入口仍未达到可验收状态，原因是服务器文件未更新且本机无可用 SSH/宝塔上传权限；小程序体验版仍需手机端体验权限验证。
- 下一阶段应继续处理的事项：必须先在服务器执行 `RELEASE_EXECUTION_TEMPLATES.md` 中的 `Current Gray Server Deploy Card`，将 `runtime/release-artifacts/h5-gray-20260620-122029.zip` 解压覆盖到 `/www/wwwroot/gray.413.chaimen666.com/public/app`；完成后再继续浏览器复验 H5 灰度入口和主流程。

## 2026-06-20 灰度服务器一包上传部署包生成

- 阶段名称：灰度服务器一包上传部署包生成
- 本阶段完成内容：为计划内“灰度上线核对”继续降低部署阻塞成本，新增本地命令 `local/create-gray-server-deploy-bundle.cmd` 与 `local/create-gray-server-deploy-bundle.ps1`，可把当前后台灰度包、H5 灰度包、服务器部署脚本、回滚脚本和 README 打成一个可上传到宝塔的 zip。同步把该命令加入发布控制中心，方便后续按固定入口生成交付包。
- 修改/新增的主要文件：`local/create-gray-server-deploy-bundle.cmd`、`local/create-gray-server-deploy-bundle.ps1`、`RELEASE_CONTROL_CENTER.md`、`DEVELOPMENT_LOG.md`；新增部署交付包 `runtime/release-artifacts/gray-server-deploy-bundle-20260620-125342.zip` 与同名解压目录。
- 运行或测试结果：首次运行发现 PowerShell README here-string 因 Markdown 三反引号在双引号模板内触发转义，已改为单引号模板并替换时间戳；随后 `local/create-gray-server-deploy-bundle.cmd` 成功生成 `runtime/release-artifacts/gray-server-deploy-bundle-20260620-125342.zip`，大小约 4699.7KB。zip 内容检查通过，包含 `admin-next-gray-20260620-122029.zip`、`h5-gray-20260620-122029.zip`、`deploy-gray-server.sh`、`rollback-gray-server.sh`、`README.md`；脚本内容检查确认包含灰度站路径、备份回滚目录、覆盖 `public/admin-next` 与 `public/app` 的部署步骤。
- 遗留问题：部署包已经可交付，但仍需要用户在服务器/宝塔上传并执行 `README.md` 中的命令；本机 SSH 仍无权限，无法直接替服务器完成部署。小程序体验版仍需手机端体验权限验证。
- 下一阶段应继续处理的事项：重新读取计划与日志后，如果服务器 H5 仍未更新且没有可用上传通道，则停止等待服务器部署；用户上传 `gray-server-deploy-bundle-20260620-125342.zip` 并执行部署后，继续浏览器复验灰度 H5 和全流程。

## 2026-06-20 各角色操作教程交付

- 阶段名称：各角色操作教程交付
- 本阶段完成内容：新增一版面向上线运营的文字教程，覆盖平台管理员、财务人员、商家、普通用户/会员、系统管理员/运维五类角色；教程包含全局主流程、商家审核、商品审核、凭证支付、财务对账、商家入驻、订单核销、用户下单、灰度发布、正式发布/回滚等 Mermaid 流程图，并补充常见问题和每日运营检查清单。README 已增加教程入口。
- 修改/新增的主要文件：`OPERATION_GUIDE.md`、`README.md`、`DEVELOPMENT_LOG.md`
- 运行或测试结果：通过 `rg` 检查确认教程包含角色章节、主要流程图和 README 链接；本阶段为文档交付，未运行前后端构建。
- 遗留问题：教程为通用运营版本，后续如果正式菜单名称、权限角色或小程序页面入口调整，需要同步修订。
- 下一阶段应继续处理的事项：继续按计划推进灰度 H5 入口部署复验和小程序体验版真机验收；若需要，可基于本教程再拆分成培训版、财务版或商家版单独文档。

## 2026-06-23 前端上传凭证权限报错兜底

- 阶段名称：前端上传凭证权限报错兜底
- 本阶段完成内容：针对小程序/前端确认订单上传支付凭证时弹出 `mkdir(): Operation not permitted` 的问题，后端文件服务在创建 `public/storage/file/YYYYMMDD` 失败或目录不可写时改为返回明确中文业务错误；上传接口捕获目录权限类异常，避免 PHP 底层错误透出；uni-app 通用图片上传工具统一解析上传响应和失败信息，将 `mkdir`、`Operation not permitted`、`Permission denied` 等底层错误转换为用户可理解的“上传目录权限异常”提示。该修复覆盖支付凭证、售后凭证、商家入驻收款信息、商品发布图片等复用 `util.uploadImage` 的上传场景。
- 修改/新增的主要文件：`app/common/service/file/FileService.php`、`app/api/controller/setting/Upload.php`、`zflUniApp/zflUniApp/utils/util.js`、`DEVELOPMENT_LOG.md`
- 运行或测试结果：`php -l app/common/service/file/FileService.php` 通过；`php -l app/api/controller/setting/Upload.php` 通过；`node --check zflUniApp/zflUniApp/utils/util.js` 通过；`npm run runtime:agreement-audit` 通过，PASS 7 / WARN 0 / FAIL 0；`npm run runtime:readiness` 通过，PASS 24 / WARN 0 / FAIL 0。
- 遗留问题：代码已避免前端直接显示底层错误，但真正上传成功仍需要服务器 `public/storage` 目录对 PHP-FPM 运行用户可写；正式/灰度服务器需同步修复目录归属和权限后重新测试上传支付凭证。
- 下一阶段应继续处理的事项：将修复同步到灰度/正式对应代码包，并在服务器执行 `mkdir -p public/storage/file && chown -R www:www public/storage && chmod -R 755 public/storage` 后，用小程序或 H5 重新上传支付凭证验证。
