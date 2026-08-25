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

## 2026-07-17 后台商家超管设置入口

- 阶段名称：后台商家超管设置入口
- 本阶段完成内容：在 `admin-next -> 商家管理` 列表新增“商家超管”状态列和开关，支持对单个商家开启或取消超管权限；开启前校验商家已绑定小程序会员、审核通过且未禁用；操作前明确提示超管将获得平台商家审核和跨商家订单核销权限。商家详情抽屉同步展示超管状态。前端接入后端已有的 `merchant.Merchant/memberSuper` 接口。
- 修改/新增的主要文件：`zflAdminWeb/src/api/merchant/merchant.js`、`zflAdminWeb/src/views/merchant/merchant.vue`、`DEVELOPMENT_LOG.md`
- 运行或测试结果：`npx eslint src/views/merchant/merchant.vue src/api/merchant/merchant.js` 通过；`npm run build:admin-next-online` 通过，正式产物已生成到 `zflAdminWeb/dist-admin-next-online`；构建产物检查确认包含 `memberSuper` 接口和“跨商家订单核销”确认文案。
- 遗留问题：本地未直接对正式数据库执行超管开启/取消写操作；部署正式包后应选择一个已绑定会员的测试商家验证开关、列表回显、小程序重新登录后的超管入口和取消权限回收。
- 下一阶段应继续处理的事项：将最新 `dist-admin-next-online` 部署到正式站 `public/admin-next`，在商家管理页完成一次实际开启/取消回归；确认无误后再为目标商家正式开启。

## 2026-07-17 商家会员绑定防误清空修复

- 阶段名称：商家会员绑定防误清空修复
- 本阶段完成内容：排查正式后台“商家超管”提示未绑定会员的问题。确认商家列表以 `ya_merchant.member_id` 作为唯一绑定依据，后台账号或联系电话相同不会自动建立绑定；同时发现普通商家新增/编辑参数包含 `member_id` 和 `member_is_super` 的缺省值，后台保存未提交这两个字段时仍可能把既有绑定和超管状态覆盖为空/0。现已将两个权限关联字段移出普通编辑字段集，后续只能通过专门的绑定/超管操作修改，避免编辑商家名称、电话、收款码等资料时误清空权限关系。历史数据库快照显示商家 `顺源家电`（商家 ID `4`）原绑定会员 ID 为 `13`，正式库需核对后恢复该关联。
- 修改/新增的主要文件：`app/common/service/merchant/MerchantService.php`、`DEVELOPMENT_LOG.md`
- 运行或测试结果：`php -l app/common/service/merchant/MerchantService.php` 通过；通过 Composer 自动加载读取 `MerchantService::$edit_field`，确认 `member_id`、`member_is_super` 已不在普通编辑字段中；保留专用 `setMemberSuper()` 方法不变。
- 遗留问题：本地不能直接写正式数据库；正式服务器需先查询商家 ID `4` 和会员 ID `13` 当前状态，确认无冲突后恢复 `ya_merchant.member_id=13`，再清缓存并在后台验证商家超管开关。若目标商家实际需要绑定其他小程序会员，不应直接使用历史 ID，应以正式库查询和运营确认结果为准。
- 下一阶段应继续处理的事项：提交并推送本次后端修复；服务器同步本次目标文件 `app/common/service/merchant/MerchantService.php`，执行正式库只读核对、恢复绑定、清缓存，然后验证商家列表显示“已绑定会员”并实际开启商家超管。

## 2026-08-25 渐进式重构阶段一：事实基线冻结

- 阶段名称：渐进式重构阶段一：事实基线冻结
- 本阶段完成内容：把 22 至 24 周渐进式重构路线纳入现有开发计划；完成平台后台、商家端、uni-app、巡检端及 ThinkPHP 后端的第一轮静态盘点；固化核心状态兼容原则、目标角色矩阵、写操作上下文规范、高风险模块和历史副本隔离清单。确认订单、商家和采购台账 Service 均为高耦合大文件，拆分前必须先建立特征测试和结果基准。
- 修改/新增的主要文件：`REFACTOR_BASELINE.md`、`PLAN.md`、`DEVELOPMENT_LOG.md`
- 运行或测试结果：完成 PHP、Vue 页面和测试资产数量盘点；完成核心状态、写控制器、权限判断及 legacy 路由静态检索；本阶段只建立事实基线，未修改业务逻辑、数据库或运行态配置。
- 遗留问题：完整写接口机器清单、数据库字段字典、只读诊断命令、脱敏快照基准和 PHP 特征测试尚未完成；当前仓库存在大量既有未提交修改，后续阶段必须继续保持提交范围隔离。
- 下一阶段应继续处理的事项：阶段一第二小阶段新增只读基线审计命令，输出写接口、关键表字段/索引、历史副本引用和核心文件规模报告；随后以脱敏数据库快照建立订单统计与采购台账黄金基准。

## 2026-08-25 渐进式重构阶段一：机器基线与订单取消状态修复

- 阶段名称：渐进式重构阶段一：机器基线与订单取消状态修复
- 本阶段完成内容：新增 `refactor:baseline` 只读命令，机器可读输出核心状态、核心文件规模、写动作候选、历史副本候选和硬性兼容检查；支持可选 `--database` 只读输出关键表字段与索引。审计首次运行发现订单模型缺少数据库和业务注释中已有的 `7=取消` 状态，导致凭证支付驳回调用 `getStatus('cancel')` 时得到 `NULL`；已补齐兼容状态并增加无数据库特征测试。
- 修改/新增的主要文件：`app/command/RefactorBaselineAudit.php`、`config/console.php`、`app/common/model/member/MemberOrderModel.php`、`tests/refactor/core-state-characterization.php`、`REFACTOR_BASELINE.md`、`DEVELOPMENT_LOG.md`
- 运行或测试结果：全部 PHP 文件语法检查通过；`php tests/refactor/core-state-characterization.php` 通过 34 项断言；`php think refactor:baseline` 识别 6 个核心文件、358 个写动作候选和 14 个历史副本候选，3 项硬检查全部通过、退出码为 0；`git diff --check` 通过，仅有既有行尾转换提示。
- 遗留问题：本机数据库当前未启动，`--database` 模式尚未连接真实测试库验证；写动作清单目前是按命名规则生成的候选集，阶段一后续需要结合路由和调用方收敛；权限特征测试只覆盖商家绑定保护，完整角色矩阵仍待补齐。
- 下一阶段应继续处理的事项：重新读取计划和日志后，进入关键表结构与索引报告、订单/采购台账只读黄金基准小阶段；优先使用现有数据库快照，不连接或修改正式库。

## 2026-08-25 渐进式重构阶段一：SQL 快照黄金基准

- 阶段名称：渐进式重构阶段一：SQL 快照黄金基准
- 本阶段完成内容：新增 `refactor:snapshot` 离线只读命令，可解析 mysqldump 而不执行 SQL、不连接数据库；输出商品、会员、订单、订单明细、订单日志、商家和采购流水七张核心表的字段字典、索引、脱敏业务统计与引用完整性。以 2026-06-19 的 67MB 快照生成首份稳定 JSON 黄金基准。识别出订单关联索引缺口，并确认历史数据存在 3 笔有效订单状态为空；仅记录事实，没有修改历史订单。
- 修改/新增的主要文件：`app/common/support/refactor/SqlSnapshotAudit.php`、`app/command/RefactorSnapshotAudit.php`、`config/console.php`、`tests/refactor/sql-snapshot-audit.php`、`refactor-baselines/20260619-snapshot.json`、`REFACTOR_BASELINE.md`、`DEVELOPMENT_LOG.md`
- 运行或测试结果：`php tests/refactor/sql-snapshot-audit.php` 通过 6 项断言；`php think refactor:snapshot --snapshot 413_zlck666_com_2026-06-19_11-32-59_mysql_data_FhRY1.sql --output refactor-baselines/20260619-snapshot.json` 成功。基准显示有效订单 1724 笔、已支付 1720 笔、已支付实付 3,784,243.05 元；采购流水 1477 条、总额 4,376,552.64 元，订单及明细引用缺失均为 0。
- 遗留问题：订单主表缺少订单号、商家/支付时间、会员/创建时间索引，明细和日志缺少订单关联索引；索引必须在测试库通过查询计划和写入影响验证后再新增。快照聚合尚未直接运行 `MerchantPurchaseLedgerReportService` 的完整页面算法，阶段二需在隔离数据库导入脱敏快照后做新旧输出对照。
- 下一阶段应继续处理的事项：重新读取计划和日志后，进入阶段一角色权限矩阵与现状特征测试小阶段，覆盖平台、商家、商家超管、巡检和会员的授权判定；完成后再进入阶段二订单服务拆分前的状态机与写入口收敛。

## 2026-08-25 渐进式重构阶段一：权限矩阵特征化

- 阶段名称：渐进式重构阶段一：权限矩阵特征化
- 本阶段完成内容：梳理平台用户、商家用户、商家超管、会员和巡检员五套当前授权来源及拒绝路径；固化移动端商家审核、支付审核、订单核销的查询/写接口权限映射；新增无数据库权限特征测试，并让 `refactor:baseline` 输出移动管理 URL 与商家身份权限码。保持商家审核状态和超管授权相互独立，不改变线上授权语义。
- 修改/新增的主要文件：`REFACTOR_PERMISSION_MATRIX.md`、`app/command/RefactorBaselineAudit.php`、`tests/refactor/permission-characterization.php`、`REFACTOR_BASELINE.md`、`DEVELOPMENT_LOG.md`
- 运行或测试结果：`php tests/refactor/permission-characterization.php` 通过 19 项断言；`php tests/refactor/core-state-characterization.php` 通过 34 项断言；`php tests/refactor/sql-snapshot-audit.php` 通过 6 项断言；`php think refactor:baseline` 输出权限基线，5 项硬检查全部通过、0 项失败；相关 PHP 语法检查通过。
- 遗留问题：平台、商家、会员和巡检目前仍使用不同权限码/菜单 URL；API 中间件的超级会员旁路、商家超管在审核失败/过期后的实际行权规则，需要阶段三统一权限上下文时显式决策并增加授权日志。
- 下一阶段应继续处理的事项：完成本阶段测试并提交；重新读取计划和日志后，进入阶段二第一小阶段，先建立订单状态转换特征表和单一写入口清单，再开始拆分查询服务。

## 2026-08-25 渐进式重构阶段二：订单转换与写入口基线

- 阶段名称：渐进式重构阶段二：订单转换与写入口基线
- 本阶段完成内容：将创建、微信支付、凭证审核、发货、自提核销、收货、评价、售后、退款和取消共 13 类现有业务转换固化为纯状态策略；记录各控制器到订单 Service 的共享写入口。明确保留“凭证审核通过直接完成”和“买家取消仅软删除”两项兼容差异，不修改历史订单。
- 修改/新增的主要文件：`app/common/domain/order/OrderStateTransitionPolicy.php`、`tests/refactor/order-transition-characterization.php`、`ORDER_STATE_BASELINE.md`、`REFACTOR_BASELINE.md`、`DEVELOPMENT_LOG.md`
- 运行或测试结果：`php tests/refactor/order-transition-characterization.php` 通过 13 项断言；核心状态 34 项、权限 19 项、SQL 快照 6 项回归断言继续通过；新增 PHP 文件语法检查和目标文件 `git diff --check` 通过。
- 遗留问题：现有写方法尚未统一调用状态策略；微信支付回调仍直接在控制器写订单且共享支付单的循环存在提前返回风险。需要事件流水和幂等基础设施落地后优先迁移该入口。
- 下一阶段应继续处理的事项：新增版本化订单业务事件表、幂等请求表及对应模型/记录服务，先完成无业务接入的迁移检查和单元测试。

## 2026-08-25 渐进式重构阶段二：事件流水与幂等基础设施

- 阶段名称：渐进式重构阶段二：事件流水与幂等基础设施
- 本阶段完成内容：新增业务操作请求表和订单事件流水表的版本化迁移，分别提供批量操作级幂等和逐订单事件唯一性；字段覆盖订单号、前后订单/支付/售后状态、金额、数量、会员、商家、操作人、来源端、请求编号、时间、原因和扩展数据。新增操作上下文、幂等请求服务、事件记录服务及模型，迁移附存在性验证和空表回滚说明。
- 修改/新增的主要文件：`private/migrations/20260825_add_business_operation_and_order_event.sql`、`app/common/domain/operation/BusinessOperationContext.php`、`app/common/model/order/BusinessOperationRequestModel.php`、`app/common/model/order/OrderBusinessEventModel.php`、`app/common/service/operation/BusinessOperationRequestService.php`、`app/common/service/order/OrderBusinessEventService.php`、`tests/refactor/order-event-infrastructure.php`、`DEVELOPMENT_LOG.md`
- 运行或测试结果：`php tests/refactor/order-event-infrastructure.php` 通过 25 项断言；订单转换 13 项、核心状态 34 项、权限 19 项、SQL 快照 6 项回归断言全部通过；新增 PHP 文件语法检查通过。
- 遗留问题：迁移尚未在隔离测试库执行；幂等和事件服务尚未接入现有业务写入口，因此当前线上行为不变。业务接入前必须先部署迁移，不允许通过捕获“表不存在”静默跳过事件。
- 下一阶段应继续处理的事项：在隔离测试库执行迁移并验证索引；改造凭证支付审核和自提核销，要求状态变化、账单/库存和事件在同一事务内提交，并为旧接口生成兼容请求上下文。

## 2026-08-25 渐进式重构阶段二：凭证审核与核销可信化

- 阶段名称：渐进式重构阶段二：凭证审核与核销可信化
- 本阶段完成内容：启动独立 MySQL 8.4 容器并导入 2026-06-19 快照，订单和采购流水数量与黄金基准一致；真实执行事件/幂等迁移并验证所有索引。凭证支付通过、凭证驳回和自提核销已接入服务端操作上下文、状态策略、幂等请求和订单事件，原有订单、账单、采购流水、商品转移、库存出库和日志仍与事件处于同一事务。平台后台、商家后台、移动商家和移动管理入口由服务端确定操作人及来源，兼容旧客户端在缺少请求编号时生成唯一兼容编号。
- 修改/新增的主要文件：`app/common/domain/operation/BusinessOperationContextFactory.php`、`app/common/service/member/MemberOrderService.php`、`app/common/service/operation/BusinessOperationRequestService.php`、`app/admin/controller/order/Order.php`、`app/merchant/controller/order/Order.php`、`app/api/controller/merchant/Merchant.php`、`app/api/controller/admin/MobileAdmin.php`、`app/command/RefactorBaselineAudit.php`、`tests/refactor/order-event-database-integration.php`、`tests/refactor/voucher-review-database-integration.php`、`tests/refactor/voucher-approval-database-integration.php`、`tests/refactor/order-writeoff-database-integration.php`、`DEVELOPMENT_LOG.md`
- 运行或测试结果：9 组测试共 140 项断言通过。凭证通过测试覆盖订单、账单、采购流水、事件和重复请求；驳回测试覆盖取消状态和重复请求；核销测试覆盖订单、库存出库和重复请求。测试全部使用外层事务回滚，结束后本地库仍为订单 1732、采购流水 1477、操作请求 0、事件 0。`refactor:baseline --database` 成功读取 8 张核心表，5 项硬检查全绿。
- 遗留问题：迁移尚未部署灰度环境；微信支付回调仍在控制器直接写订单，且共享支付单遇到已支付订单会提前返回；退款、发货、收货、评价和取消尚未接入事件/幂等。兼容客户端自动生成请求编号只能保证单次调用内部一致，新版前端必须主动发送稳定 `X-Request-Id` 才能抵御网络重试。
- 下一阶段应继续处理的事项：将微信支付回调迁移到支付应用服务，修复共享支付单提前返回并以微信交易号作为幂等请求编号；增加回调重复与多订单集成测试，然后接入退款事件。

## 2026-08-25 渐进式重构阶段二：微信支付回调可信化

- 阶段名称：渐进式重构阶段二：微信支付回调可信化
- 本阶段完成内容：将微信支付成功后的订单、账单、日志和状态写入从 API 控制器迁移到独立 `WechatPaymentCallbackService`；控制器只保留微信 SDK 验签和响应。服务以微信交易号作为幂等请求编号，锁定共享支付单全部订单，已支付订单只跳过而不结束循环，未支付订单按状态策略写入订单、账单和事件并在同一事务提交。订单日志操作角色改为系统，准确反映回调来源。
- 修改/新增的主要文件：`app/common/service/order/WechatPaymentCallbackService.php`、`app/api/controller/member/MemberOrder.php`、`tests/refactor/wechat-payment-callback-database-integration.php`、`DEVELOPMENT_LOG.md`
- 运行或测试结果：`php tests/refactor/wechat-payment-callback-database-integration.php` 通过 12 项断言；测试构造共享支付单第一笔已支付、第二笔待支付场景，确认结果为 processed=1/skipped=1，第二笔订单正常支付，账单和事件各新增一次；相同交易号重复回调不产生重复账单或事件；外层事务回滚后订单、操作请求和事件全部恢复。
- 遗留问题：微信支付失败通知当前仍保持原有“不改变订单”行为且未记录失败事件；真实微信 SDK 签名回调只能在灰度配置可用后做端到端验证。退款、发货、收货、评价和取消仍待接入事件与幂等。
- 下一阶段应继续处理的事项：拆出退款应用服务并接入申请、审核和完成事件；优先覆盖凭证人工退款以避免测试调用微信退款外部接口，再为微信退款增加外部调用结果记录和补偿策略。

## 2026-08-25 渐进式重构阶段二：售后申请服务拆分

- 阶段名称：渐进式重构阶段二：售后申请服务拆分
- 本阶段完成内容：从 `MemberOrderService` 中完整拆出售后申请写逻辑到 `OrderRefundService::request`，旧接口保持原方法和返回值并转调新服务。售后申请现在锁定订单、校验完成态、使用服务端会员上下文和请求编号，并将订单状态、售后参数、传统订单日志、幂等请求和 `refund.requested` 事件在同一事务提交。
- 修改/新增的主要文件：`app/common/service/order/OrderRefundService.php`、`app/common/service/member/MemberOrderService.php`、`app/api/controller/member/MemberOrder.php`、`tests/refactor/refund-request-database-integration.php`、`DEVELOPMENT_LOG.md`
- 运行或测试结果：`php tests/refactor/refund-request-database-integration.php` 通过 10 项断言；完整 11 组测试共 162 项断言通过。回滚后隔离库订单仍为 1732，操作请求和事件均为 0。
- 遗留问题：管理员售后审核与退款完成仍留在旧订单 Service；拒绝售后当前保持订单状态 5 的历史语义；微信退款包含外部调用，不能只靠数据库事务实现原子性，需要记录外部调用结果并设计补偿。
- 下一阶段应继续处理的事项：将凭证支付的售后同意、拒绝和人工退款迁入 `OrderRefundService`，接入幂等与事件并覆盖商家余额扣减、会员账单和重复请求；随后抽象微信退款网关。

## 2026-08-25 渐进式重构阶段二：凭证退款可信化

- 阶段名称：渐进式重构阶段二：凭证退款可信化
- 本阶段完成内容：在 `OrderRefundService` 新增凭证支付售后审核与人工退款路径，旧 `serviceOrder` 根据订单支付方式兼容转调；支持同意退款、同意退货、拒绝售后和退货退款四种现有语义。凭证退款在单一事务内写订单退款状态、退款单号、会员退款账单、按订单明细比例扣减商家余额、传统日志、业务事件和幂等结果；管理员操作上下文由服务端生成。
- 修改/新增的主要文件：`app/common/service/order/OrderRefundService.php`、`app/common/service/member/MemberOrderService.php`、`app/admin/controller/order/Order.php`、`tests/refactor/voucher-refund-database-integration.php`、`DEVELOPMENT_LOG.md`
- 运行或测试结果：`php tests/refactor/voucher-refund-database-integration.php` 通过 13 项断言，覆盖退款状态、金额、退款单号、会员账单、事件、幂等重复和事务回滚；完整 12 组测试共 175 项断言通过。回滚后订单 1732、采购流水 1477、操作请求 0、事件 0。
- 遗留问题：微信支付退款仍在旧 Service 中直接调用外部网关；数据库事务不能回滚已成功的微信退款，需要先记录外部请求状态、响应和补偿标记。售后拒绝继续保留状态 5 的历史兼容语义。
- 下一阶段应继续处理的事项：新增退款网关调用记录表和接口，拆出微信退款服务，按“准备记录、调用网关、落库完成/待补偿”处理外部一致性；增加网关假实现测试，禁止自动测试调用真实微信退款。

## 2026-08-25 渐进式重构阶段二：微信退款 Saga 与补偿记录

- 阶段名称：渐进式重构阶段二：微信退款 Saga 与补偿记录
- 本阶段完成内容：新增外部支付网关调用记录表、模型和状态服务，记录准备、请求中、成功、失败及结果未知；新增退款网关接口和微信实现，自动测试使用可注入假网关。微信退款从旧订单 Service 迁入 `OrderRefundService::reviewWechat`，采用“幂等操作与网关准备、外部调用、响应持久化、订单事务收口”的 Saga。成功后写订单、商家扣减、日志和事件；外部成功但本地失败时标记待补偿；网络超时标记结果未知并阻止相同请求再次调用网关。
- 修改/新增的主要文件：`private/migrations/20260825_add_payment_gateway_attempt.sql`、`app/common/model/finance/PaymentGatewayAttemptModel.php`、`app/common/gateway/payment/RefundGatewayInterface.php`、`app/common/gateway/payment/WechatRefundGateway.php`、`app/common/service/payment/PaymentGatewayAttemptService.php`、`app/common/service/order/OrderRefundService.php`、`app/common/service/member/MemberOrderService.php`、`app/command/RefactorBaselineAudit.php`、`tests/refactor/payment-gateway-infrastructure.php`、`tests/refactor/wechat-refund-saga-database-integration.php`、`DEVELOPMENT_LOG.md`
- 运行或测试结果：本地迁移执行成功，唯一键和 4 组查询索引正确；支付网关基础设施 9 项、微信退款 Saga 17 项断言通过。完整 14 组测试共 201 项断言通过；`refactor:baseline --database` 读取 9 张核心表且硬检查 0 失败；回滚后订单 1732、采购流水 1477、操作请求/事件/网关记录均为 0。测试未读取微信证书、未调用真实微信接口。
- 遗留问题：尚未实现结果未知记录的后台人工核对/补偿页面和定时告警；真实微信退款响应字段需要灰度环境契约验证。发货、收货、评价和取消仍未接入事件/幂等。
- 下一阶段应继续处理的事项：接入发货、收货、评价和买家取消事件，修复取消订单只软删除但无显式取消状态的诊断展示；随后新增订单时间线查询接口和后台页面。

## 2026-08-25 渐进式重构阶段二：取消、履约与订单时间线

- 阶段名称：渐进式重构阶段二：取消、履约与订单时间线
- 本阶段完成内容：将买家取消抽取到 `OrderCancellationService`，保留 `status=0 + is_delete=1` 的历史兼容语义并以 `order.canceled` 事件明确记录取消，库存恢复改为数据库原子递增且重复请求不重复返还；将确认收货和评价抽取到 `OrderFulfillmentService`，订单、评价、非凭证商家入账、旧日志、幂等请求和业务事件在同一事务内提交。新增订单时间线查询服务和管理员只读接口，统一返回订单摘要、新事件、旧日志及 `legacy_only/hybrid/event` 覆盖标记；`admin-next` 订单列表新增“流转”抽屉，可查看状态前后变化、金额、数量、来源、操作人、请求编号、原因和历史日志。
- 修改/新增的主要文件：`app/common/service/order/OrderCancellationService.php`、`app/common/service/order/OrderFulfillmentService.php`、`app/common/service/order/OrderTimelineQueryService.php`、`app/common/service/member/MemberOrderService.php`、`app/api/controller/member/MemberOrder.php`、`app/admin/controller/order/Order.php`、`zflAdminWeb/src/api/order/list.js`、`zflAdminWeb/src/views/order/list.vue`、`zflAdminWeb/src/views/order/components/OrderTimelineDrawer.vue`、`tests/refactor/order-cancellation-database-integration.php`、`tests/refactor/order-fulfillment-database-integration.php`、`tests/refactor/order-timeline-database-integration.php`、`zflAdminWeb/tests/order-timeline-audit.spec.js`、`DEVELOPMENT_LOG.md`
- 运行或测试结果：新增取消 11 项、履约 14 项、时间线 6 项数据库断言通过；完整 `tests/refactor` 17 个脚本共 232 项断言通过。相关 PHP 文件语法检查、目标前端 ESLint 和 `npm run build:admin-next-local` 通过；独立 Playwright 时间线交互用例 1/1 通过；真实管理员调用 `/admin/order.Order/timeline` 返回 200，并正确返回快照订单的 `legacy_only` 摘要和旧日志。全部数据库测试均回滚，结束后订单 1732、采购流水 1477、操作请求/事件/网关记录均为 0。本地 PHP 服务已恢复在 `http://127.0.0.1:807/`。
- 遗留问题：发货仍保留在旧 `MemberOrderService`，尚未接入统一幂等和业务事件；退货寄回物流也尚无独立事件。当前快照没有持久化新版事件，因此真实接口只验证了历史日志分支，新旧混合分支由 Playwright 契约数据验证。
- 下一阶段应继续处理的事项：重新读取计划和日志后，将平台/商家发货入口抽取到 `OrderFulfillmentService`，统一操作上下文、状态策略、物流字段、旧日志、幂等请求和 `order.delivered` 事件，并增加重复发货数据库集成测试。

## 2026-08-25 渐进式重构阶段二：发货履约可信化

- 阶段名称：渐进式重构阶段二：发货履约可信化
- 本阶段完成内容：将平台订单发货从 `MemberOrderService` 抽取到 `OrderFulfillmentService::deliver`，旧接口保持 URL、参数和返回值不变并转调新服务。服务锁定待发货订单和商品库存记录，在同一事务内写库存出库、物流公司、运单号、发货时间、订单状态、旧订单日志、幂等结果及 `order.delivered` 业务事件；快递公司不存在或禁用时返回明确错误。平台控制器统一注入管理员身份、`admin-next` 来源和请求编号；前端发货 API 同时发送 `X-Request-Id` 与兼容参数编号。
- 修改/新增的主要文件：`app/common/service/order/OrderFulfillmentService.php`、`app/common/service/member/MemberOrderService.php`、`app/admin/controller/order/Order.php`、`zflAdminWeb/src/api/order/list.js`、`tests/refactor/order-delivery-database-integration.php`、`DEVELOPMENT_LOG.md`
- 运行或测试结果：发货数据库集成测试通过 14 项断言，覆盖物流字段、状态转换、库存出库、旧日志、业务事件和重复请求；完整 `tests/refactor` 18 个脚本共 246 项断言通过。相关 PHP 语法、前端 API ESLint、`npm run build:admin-next-local` 和目标差异检查通过。测试全部回滚，结束后订单 1732、采购流水 1477、操作请求/事件/网关记录均为 0。
- 遗留问题：当前兼容语义仍允许库存不足的商品不记出库但订单继续发货，本阶段没有改变线上规则；阶段四统一库存边界时需要决定是否改为强制拦截。买家退货寄回仍在旧订单 Service，尚未接入业务事件和幂等请求。
- 下一阶段应继续处理的事项：重新读取计划和日志后，将买家退货寄回抽入退款/履约应用服务，记录退货物流事件和幂等请求；随后进入订单创建事件及下单幂等小阶段。

## 2026-08-25 渐进式重构阶段二：退货寄回物流可信化

- 阶段名称：渐进式重构阶段二：退货寄回物流可信化
- 本阶段完成内容：新增 `refund.return_shipped` 状态基线，明确退货寄回只允许“售后中 + 退货退款 + 已同意退货”，且提交物流后主状态和售后状态保持不变。将旧 `returnGoods` 写逻辑迁入 `OrderRefundService::shipReturn`，订单锁定、快递校验、退货物流、旧日志、幂等结果和业务事件在同一事务提交；事件扩展数据包含快递公司、编码和运单号。移动会员控制器注入会员身份与 `uniapp-weixin` 来源；uni-app 对订单、快递公司和运单号计算确定性请求编号，同一物流内容重试不会重复记事件。
- 修改/新增的主要文件：`app/common/domain/order/OrderStateTransitionPolicy.php`、`app/common/service/order/OrderRefundService.php`、`app/common/service/member/MemberOrderService.php`、`app/api/controller/member/MemberOrder.php`、`tests/refactor/order-transition-characterization.php`、`tests/refactor/return-shipment-database-integration.php`、`zflAdminWeb/src/views/order/components/OrderTimelineDrawer.vue`、`zflUniApp/zflUniApp/api/member.js`、`DEVELOPMENT_LOG.md`
- 运行或测试结果：订单转换特征测试扩展为 16 项断言，退货寄回数据库集成测试通过 14 项断言；完整 `tests/refactor` 19 个脚本共 263 项断言通过。相关 PHP 语法、移动 API `node --check`、后台 `npm run build:admin-next-local` 和目标差异检查通过；测试全部回滚，结束后订单 1732、采购流水 1477、操作请求/事件/网关记录均为 0。H5 构建首次因 HBuilderX 未打开失败，自动启动后再次执行明确返回“此功能需要先登录”，构建脚本正确拒绝同步旧产物，因此本阶段未把 H5 发布标记为通过。
- 遗留问题：本机 HBuilderX 未登录，无法完成 H5/小程序真实编译；需要具备 DCloud 登录态后补跑 `npm run build:h5:local` 和小程序构建。退货运单在业务上允许用户修改，内容变化会形成新的请求编号和新事件，这是可追溯修改而非重复提交。
- 下一阶段应继续处理的事项：重新读取计划和日志后，进入订单创建事件与下单幂等小阶段，先梳理 `confirmOrder` 的购物车、库存和订单创建事务边界，再接入 `order.created` 事件和稳定请求编号。

## 2026-08-25 渐进式重构阶段二：订单创建事件与下单幂等

- 阶段名称：渐进式重构阶段二：订单创建事件与下单幂等
- 本阶段完成内容：在保持 `confirmOrder` 原 URL、参数、返回结构和事务范围的前提下接入 `order.create` 幂等请求；每个商家子订单在订单、明细、库存、购物车和旧日志同一事务内写 `order.created` 事件，事件包含共同支付单号、支付方式、配送方式、金额和数量。凭证支付完成后持久化 `true` 结果，微信统一下单成功后持久化原 bridgeConfig，重复请求按原结构重放。会员控制器注入会员身份和 `uniapp-weixin` 来源；uni-app API 首次提交时写入请求对象级 `request_id`，网络失败后复用同一结算对象可保持编号稳定。
- 修改/新增的主要文件：`app/common/service/member/MemberOrderService.php`、`app/api/controller/member/MemberOrder.php`、`zflUniApp/zflUniApp/api/member.js`、`tests/refactor/order-creation-database-integration.php`、`ORDER_STATE_BASELINE.md`、`DEVELOPMENT_LOG.md`
- 运行或测试结果：凭证支付订单创建数据库集成测试通过 16 项断言，覆盖订单、明细、库存扣减、旧日志、事件、操作结果及重复请求；完整 `tests/refactor` 20 个脚本共 279 项断言通过。相关 PHP 语法、移动 API `node --check` 和目标差异检查通过；测试全部回滚，结束后订单 1732、订单明细 1733、采购流水 1477、操作请求/事件/网关记录均为 0。自动测试没有调用真实微信统一下单。
- 遗留问题：微信统一下单外部调用仍位于数据库事务中，可能延长订单和库存锁持有时间；真实微信结果契约尚未在灰度验证。HBuilderX 未登录阻塞仍存在，因此本阶段未重跑 H5 发布。
- 下一阶段应继续处理的事项：重新读取计划和日志后，抽象微信统一下单网关并把外部调用移出数据库事务，采用可恢复 Saga 记录预下单结果；自动测试必须使用假网关，不调用真实微信。

## 2026-08-25 渐进式重构阶段二：微信预下单网关边界

- 阶段名称：渐进式重构阶段二：微信预下单网关边界
- 本阶段完成内容：新增 `PrepaymentGatewayInterface` 和 `WechatPrepaymentGateway`，将微信配置读取、openid 查询、统一下单和 bridgeConfig 生成从订单服务中隔离。`confirmOrder` 支持注入预下单网关，生产默认使用微信实现，旧接口成功/失败提示和 bridgeConfig 返回结构保持不变；凭证支付不再因为无关的微信配置预检查而被阻断。相同下单请求命中已完成幂等记录时直接重放 bridgeConfig，不再次调用网关。
- 修改/新增的主要文件：`app/common/gateway/payment/PrepaymentGatewayInterface.php`、`app/common/gateway/payment/WechatPrepaymentGateway.php`、`app/common/service/member/MemberOrderService.php`、`tests/refactor/wechat-prepayment-gateway-database-integration.php`、`DEVELOPMENT_LOG.md`
- 运行或测试结果：假预下单网关数据库集成测试通过 14 项断言，覆盖微信订单、创建事件、bridgeConfig 持久化、重复请求重放和网关仅调用一次；完整 `tests/refactor` 21 个脚本共 293 项断言通过。相关 PHP 语法和目标差异检查通过；测试全部回滚，结束后订单 1732、订单明细 1733、采购流水 1477、操作请求/事件/网关记录均为 0。测试未调用真实微信。
- 遗留问题：预下单网关调用目前仍位于订单数据库事务内，本阶段只建立了可测试边界；外部失败仍采用原事务回滚行为，尚未形成独立网关尝试记录和可恢复补偿。
- 下一阶段应继续处理的事项：重新读取计划和日志后，将微信订单数据库准备先提交并写 `business_type=prepayment` 网关尝试记录，再在事务外调用网关；失败时补偿订单、明细关联状态、商品库存/销量和购物车，结果未知时禁止自动重试并进入人工核对。

## 2026-08-25 渐进式重构阶段二：微信预下单可恢复 Saga

- 阶段名称：渐进式重构阶段二：微信预下单可恢复 Saga
- 本阶段完成内容：微信下单先在数据库事务内创建订单、明细、扣减库存/增加销量、处理购物车、写旧日志和 `order.created` 事件，同时准备 `business_type=prepayment` 网关尝试记录；事务提交并释放商品购买锁后，才在事务外调用预下单网关。成功时保存网关响应、prepay_id 和 bridgeConfig 并完成幂等操作；确定失败时在独立补偿事务恢复库存/销量及购物车，删除本次未成立订单、明细、旧日志和创建事件，同时保留失败操作与网关记录；网络异常标记“结果未知”，保留待付款订单和库存占用供人工核对，并阻止同请求再次调用网关。修复数据库准备提交后旧 catch 仍无条件 rollback 的事务边界问题。
- 修改/新增的主要文件：`app/common/service/order/WechatPrepaymentSagaService.php`、`app/common/service/member/MemberOrderService.php`、`tests/refactor/wechat-prepayment-gateway-database-integration.php`、`DEVELOPMENT_LOG.md`
- 运行或测试结果：微信预下单 Saga 集成测试扩展为 31 项断言，覆盖成功、幂等重放、确定失败补偿、结果未知保留和重复调用冻结；完整 `tests/refactor` 21 个脚本共 310 项断言通过。相关 PHP 语法和目标差异检查通过；测试全部回滚，结束后订单 1732、订单明细 1733、采购流水 1477、操作请求/事件/网关记录均为 0。全部网关测试使用假实现，未调用真实微信。
- 遗留问题：后台尚无“预下单结果未知”人工核对列表和补偿入口；结果未知订单会保持待付款并占用库存，这是避免微信可能已成功时错误释放库存的保守策略。真实微信预下单响应仍需灰度契约验证。
- 下一阶段应继续处理的事项：重新读取计划和日志后，新增支付网关异常查询/人工核对后台接口和 `admin-next` 页面，至少支持查看业务类型、订单、请求响应、错误、调用次数和状态；人工补偿写操作需单独设计权限与幂等，不在只读页面阶段直接开放。

## 2026-08-25 渐进式重构阶段二：支付网关异常只读监控

- 阶段名称：渐进式重构阶段二：支付网关异常只读监控
- 本阶段完成内容：新增支付网关尝试只读查询服务及管理员接口，支持按状态、业务类型、支付提供方、关键字和时间范围筛选，提供总量、待关注数量及状态/业务类型汇总，并在详情中解码请求与响应证据。`admin-next` 新增隐藏静态路由 `#/finance/gateway-attempts`，实现汇总、筛选、分页、异常列表和详情抽屉，可从网关记录返回订单搜索。页面只提供核对能力，不开放补偿、重试或状态修改，避免在权限、幂等和审计设计完成前引入高风险写入口。
- 修改/新增的主要文件：`app/common/service/payment/PaymentGatewayAttemptQueryService.php`、`app/admin/controller/report/PaymentGatewayAttempt.php`、`tests/refactor/payment-gateway-query-database-integration.php`、`zflAdminWeb/src/api/report/payment-gateway-attempt.js`、`zflAdminWeb/src/views/report/PaymentGatewayAttempt.vue`、`zflAdminWeb/src/router/index.js`、`zflAdminWeb/tests/payment-gateway-attempt-audit.spec.js`、`DEVELOPMENT_LOG.md`
- 运行或测试结果：支付网关查询数据库集成测试通过 7 项断言；完整 `tests/refactor` 22 个脚本共 317 项断言通过；目标 PHP 语法、前端 ESLint、Prettier、`git diff --check` 和 `npm run build:admin-next-local` 通过；Playwright 异常核对页面交互 1/1 通过；真实本地管理员接口 `/admin/report.PaymentGatewayAttempt/summary` 返回 200。测试结束后订单 1732、订单明细 1733、采购流水 1477，操作请求、事件和网关记录均为 0。
- 遗留问题：当前异常页是隐藏静态路由，尚未接入菜单权限；真实微信预下单/退款响应契约仍需灰度验证。人工补偿写操作尚未实现，必须先明确权限码、二次确认、幂等请求、补偿前状态复核和完整操作日志。
- 下一阶段应继续处理的事项：重新读取计划和日志后，进入每日自动对账小阶段，先建立只读对账服务和命令，输出订单金额、订单数量、核销数量、事件覆盖及未解释异常；对账不得修改历史订单或财务数据。

## 2026-08-25 渐进式重构阶段二：每日订单与财务只读对账

- 阶段名称：渐进式重构阶段二：每日订单与财务只读对账
- 本阶段完成内容：新增每日只读对账服务和 `php think refactor:reconcile` 命令，默认核对昨天并将 JSON 报告写入 `runtime/refactor-reconciliation/YYYY-MM-DD.json`，支持 `--date` 和 `--output` 供服务器 cron 调用。报告汇总订单创建/支付数量与金额、采购流水、会员账单、核销事件及历史核销候选，并列出缺少账单、采购台账核算异常、事件覆盖期缺少支付事件和支付网关待关注订单。凭证支付同时输出数据库 `pay_price` 原始金额和按 `total_price` 计算的台账兼容金额；核销明确区分 `order.picked_up` 新事件和仅按自提 `delivery_time` 推断的历史候选，不把历史推断伪装成精确事件。
- 修改/新增的主要文件：`app/common/service/report/DailyOrderReconciliationService.php`、`app/command/RefactorDailyReconciliation.php`、`config/console.php`、`tests/refactor/daily-order-reconciliation-database-integration.php`、`DEVELOPMENT_LOG.md`
- 运行或测试结果：每日对账数据库集成测试通过 14 项断言，覆盖凭证金额双口径、采购流水、会员账单、核销事件、事件覆盖、缺账单告警、无效日期和事务回滚；完整 `tests/refactor` 23 个脚本共 331 项断言通过。真实命令对本地快照 `2026-06-13` 生成报告成功：支付订单 23 笔，原始实付/账单 6,624.00 元，台账兼容金额/采购流水 55,639.00 元，历史调整差额 49,015.00 元，报告明确保留两个口径。`refactor:baseline --database` 5 项检查通过、0 失败；PHP 语法和 `git diff --check` 通过；测试结束后订单 1732、明细 1733、采购流水 1477，操作请求/事件/网关记录均为 0。
- 遗留问题：代码已具备每日自动执行入口，但灰度/正式服务器尚未配置系统 cron；部署时需在每日业务低峰执行 `php think refactor:reconcile` 并监控命令退出状态。历史快照没有 `order.picked_up` 事件且没有可识别的自提 `delivery_time` 候选，因此历史核销数显示 0，不代表历史从未核销；只能在新事件上线后精确统计。
- 下一阶段应继续处理的事项：重新读取计划和日志后，完善差额订单诊断，将采购台账差额订单与统一订单时间线/业务事件关联，返回明确的匹配关系、未配平原因和完整流转证据，并保持旧算法并行输出。

## 2026-08-25 渐进式重构阶段二：差额订单流转证据

- 阶段名称：渐进式重构阶段二：差额订单流转证据
- 本阶段完成内容：保持采购台账原 `tradeDiffOrders` URL、配平算法和旧返回字段不变，在管理员控制器出口通过独立证据服务兼容新增 `match_evidence`、`timeline` 和 `evidence_summary`。每笔主差额订单现在同时返回匹配类型、匹配/剩余金额、匹配/剩余数量、原因编码、中文原因、订单当前状态、新版业务事件和旧操作日志；找不到订单证据时只返回明确错误，不中断整个差额查询。证据预加载限制为页面主表最多 20 笔，避免递归装配商品嵌套行造成大量查询。`admin-next` 差额弹窗新增“流转证据”入口并复用订单时间线抽屉，抽屉支持直接加载接口已返回的数据，仍保留跳转订单页复核路径。
- 修改/新增的主要文件：`app/common/service/report/MerchantPurchaseLedgerDiffEvidenceService.php`、`app/admin/controller/report/MerchantPurchaseLedger.php`、`tests/refactor/merchant-purchase-ledger-diff-evidence-database-integration.php`、`zflAdminWeb/src/views/order/components/OrderTimelineDrawer.vue`、`zflAdminWeb/src/views/report/merchant-purchase-ledger.vue`、`zflAdminWeb/tests/merchant-purchase-ledger-diff-evidence.spec.js`、`DEVELOPMENT_LOG.md`
- 运行或测试结果：差额证据数据库集成测试通过 10 项断言，覆盖 2998 元匹配金额、未配平数量、原因、订单时间线、业务事件、汇总和回滚；完整 `tests/refactor` 24 个脚本共 341 项断言通过。`npm run build:admin-next-local` 通过；Playwright 2998 差额弹窗到时间线抽屉交互 1/1 通过；目标 ESLint、Prettier、PHP 语法和 `git diff --check` 通过。真实本地快照按商家 4、买入方向、目标 2998 元运行原配平算法返回 `balance`，主表 20 笔订单均附带证据，合计 40 条旧日志；测试结束后订单 1732、明细 1733、采购流水 1477，操作请求/事件/网关记录均为 0。
- 遗留问题：本地历史快照在新版事件表启用前形成，真实差额订单当前均显示 `legacy_only`，完整新事件分支由数据库集成测试和 Playwright 契约验证；灰度产生新订单后需再验证 `hybrid/event`。旧配平算法仍作为主计算结果，本阶段只补证据，没有切换到事件事实算法，也没有修改任何历史金额或订单状态。
- 下一阶段应继续处理的事项：重新读取计划和日志后，进入阶段三统一权限的首个小阶段，先定义统一权限上下文响应与 403 契约，并为平台管理员、财务、审核员、客服、商家负责人、员工、超管、巡检员和会员建立可自动验证的权限码基线。

## 2026-08-25 渐进式重构阶段三：统一权限上下文与 403 契约

- 阶段名称：渐进式重构阶段三：统一权限上下文与 403 契约
- 本阶段完成内容：新增统一权限上下文服务，将平台后台菜单 URL、商家后台菜单 URL、小程序商家身份权限和移动管理布尔权限适配为稳定权限码。统一响应包含当前身份、商家、角色码、权限码/映射、数据范围和由事实生成的 `permission_version`；分别新增 admin-next、商家桌面端和 uni-app 查询入口，商家身份 `current/switch` 在保留旧字段的同时附带新上下文，使切换身份后可立即替换权限缓存。新增 `PermissionDeniedException` 和异常渲染契约，统一返回 HTTP 403、业务码 `40301`、错误码 `AUTH_FORBIDDEN` 及被拒绝权限码。旧控制器校验继续保留，本阶段没有一次性替换全部写接口。
- 修改/新增的主要文件：`app/common/exception/PermissionDeniedException.php`、`app/common/service/permission/UnifiedPermissionContextService.php`、`app/ExceptionHandle.php`、`app/admin/controller/system/UserCenter.php`、`app/merchant/controller/system/UserCenter.php`、`app/api/controller/merchant/Identity.php`、`app/common/service/merchant/MerchantIdentityService.php`、`tests/refactor/unified-permission-context-database-integration.php`、`REFACTOR_PERMISSION_MATRIX.md`、`DEVELOPMENT_LOG.md`
- 运行或测试结果：统一权限数据库集成测试通过 20 项断言，覆盖平台超管、商家数据隔离、商家桌面超管不得跨商家、会员本人权限、稳定权限版本、允许/拒绝断言及异常处理器最终 JSON；完整 `tests/refactor` 25 个脚本共 361 项断言通过。真实本地管理员登录后调用 `/admin/system.UserCenter/permissionContext` 返回 200，包含 `platform_super`、8 个平台权限码、`all` 数据范围和 20 位权限版本；全部目标 PHP 语法和 `git diff --check` 通过。
- 遗留问题：统一上下文目前覆盖平台后台、商家桌面端和 uni-app 会员/移动商家，巡检端仍需接入；平台普通角色的权限码由现有菜单 URL 映射，尚未逐个角色账号做浏览器验收。高风险写接口仍使用旧 `MobileAdminAccessService` 校验，尚未迁移到统一 `assertAllowed`，因此新 403 契约只对接入新断言的接口生效。
- 下一阶段应继续处理的事项：重新读取计划和日志后，先迁移移动端商家审核、支付审核和订单核销三个高风险写接口；必须保留旧授权事实作为上下文来源，改为统一权限码断言，并用未授权真实 HTTP 请求验证 403 契约。

## 2026-08-25 渐进式重构阶段三：移动高风险接口统一授权

- 阶段名称：渐进式重构阶段三：移动高风险接口统一授权
- 本阶段完成内容：将小程序移动管理的商家审核、支付审核、订单核销及其关联查询从控制器直接读取旧权限布尔值迁移为统一权限上下文和稳定权限码断言；旧 `MobileAdminAccessService` 继续作为统一上下文内部事实来源，避免改变现有授权结果。8 个 MobileAdmin 虚拟 API 标记为“登录后免会员组 URL 授权”，并加入 API 免组授权列表，使请求必须进入控制器执行统一后端复核；`is_unauth` 不等于免登录。未授权请求现在在参数校验和业务写服务前抛出统一 403，原业务 URL、参数、成功返回和操作服务不变。
- 修改/新增的主要文件：`app/api/controller/admin/MobileAdmin.php`、`app/common/service/system/MobileAdminAccessService.php`、`app/common/service/member/ApiService.php`、`tests/refactor/permission-characterization.php`、`tests/refactor/mobile-admin-unified-authorization.php`、`DEVELOPMENT_LOG.md`
- 运行或测试结果：移动统一授权特征测试通过 15 项，原权限特征 19 项、统一上下文 20 项继续通过；完整 `tests/refactor` 26 个脚本共 376 项断言通过。清理本地 API 缓存后，为无 `platform.merchant.review` 权限的普通会员生成测试 token，直接 POST `/api/admin.MobileAdmin/merchantAuth`，真实响应为 HTTP 403、业务码 `40301`、错误码 `AUTH_FORBIDDEN`、权限码 `platform.merchant.review`，请求在参数校验及写服务前终止。测试结束后订单 1732、明细 1733、采购流水 1477，操作请求/事件/网关记录均为 0；目标 PHP 语法和 `git diff --check` 通过。
- 遗留问题：本阶段只迁移 MobileAdmin 控制器；admin-next、商家桌面端和巡检端仍有旧菜单中间件/控制器校验。真实授权用户的商家审核、支付审核和核销成功路径由既有数据库集成测试覆盖，但尚未使用小程序页面做全流程浏览器验收。
- 下一阶段应继续处理的事项：重新读取计划和日志后，将商家资料编辑、会员绑定、商家审核和超管授权拆成独立命令服务；普通资料编辑必须继续禁止修改 `member_id` 与超管状态，授权和取消必须记录授权人、时间、原因及业务事件/操作日志。

## 2026-08-25 渐进式重构阶段三：商家超管授权命令与审计

- 阶段名称：渐进式重构阶段三：商家超管授权命令与审计
- 本阶段完成内容：保留管理员 `memberSuper` 原 URL 和返回字段，通过独立 `MerchantSuperAuthorizationService` 执行授予及取消超管；授权前复核商家已绑定会员、已审核通过且未禁用，商家字段变更、幂等请求完成和授权审计日志在同一数据库事务提交。新增授权审计表，记录商家、会员、授权前后值、操作人、来源端、请求编号、原因和时间；相同请求编号重复提交直接回放原结果，不重复变更或写日志。旧 `MerchantService::setMemberSuper()` 保留为兼容壳，普通资料编辑仍不接受 `member_id` 和 `member_is_super`。
- 修改/新增的主要文件：`private/migrations/20260825_add_merchant_authorization_log.sql`、`app/common/model/merchant/MerchantAuthorizationLogModel.php`、`app/common/service/merchant/MerchantSuperAuthorizationService.php`、`app/common/service/merchant/MerchantService.php`、`app/admin/controller/merchant/Merchant.php`、`tests/refactor/merchant-super-authorization-database-integration.php`、`DEVELOPMENT_LOG.md`
- 运行或测试结果：迁移已在本地隔离库执行；超管授权数据库集成测试通过 8 项断言，覆盖状态变更、授权前后审计、原因记录、重复请求回放、日志唯一性和事务回滚。完整 `tests/refactor` 27 个脚本共 384 项断言通过；`refactor:baseline --database` 5 项检查通过、0 失败；测试结束后订单 1732、明细 1733、采购流水 1477，操作请求、订单事件、网关尝试和商家授权日志均为 0。目标 PHP 语法和 `git diff --check` 通过。
- 遗留问题：灰度及正式环境部署代码前必须先执行新增迁移；管理员页面尚未强制填写授权/取消原因，也尚未提供授权历史查询。会员绑定和商家审核仍使用现有服务入口，尚未拆成独立命令；重新审核不得修改超管状态的约束需在审核命令阶段固化测试。
- 下一阶段应继续处理的事项：重新读取计划和日志后，拆分商家会员绑定命令；绑定和解绑必须独立于普通资料编辑，校验会员与商家冲突，支持幂等请求并写可追溯审计，保持现有接口兼容。

## 2026-08-25 渐进式重构阶段三：商家会员绑定命令与审计

- 阶段名称：渐进式重构阶段三：商家会员绑定命令与审计
- 本阶段完成内容：新增管理员 `memberBind` 独立命令接口，普通商家资料编辑继续不接收 `member_id`；绑定服务锁定商家和目标会员，拒绝不存在会员及已绑定其他未删除商家的会员，支持绑定、换绑和以 `member_id=0` 解绑。换绑或解绑会同步取消原超管标记，避免新会员意外继承高风险权限，并分别记录会员绑定变化和必要的超管取消日志；商家更新、审计日志和幂等请求在同一事务提交，重复请求回放原结果。统一权限新增 `platform.merchant.bind` 和 `platform.merchant.super_authorize`，两个高风险控制器动作均在参数处理前执行后端权限断言。
- 修改/新增的主要文件：`app/common/service/merchant/MerchantMemberBindingService.php`、`app/admin/controller/merchant/Merchant.php`、`app/common/validate/merchant/MerchantValidate.php`、`app/common/service/permission/UnifiedPermissionContextService.php`、`private/migrations/20260825_expand_merchant_authorization_values.sql`、`tests/refactor/merchant-member-binding-database-integration.php`、`tests/refactor/unified-permission-context-database-integration.php`、`REFACTOR_PERMISSION_MATRIX.md`、`DEVELOPMENT_LOG.md`
- 运行或测试结果：授权审计前后值字段扩展迁移已在本地隔离库执行并确认均为 `int unsigned`；会员绑定数据库集成测试通过 11 项断言，覆盖换绑、超管取消、双日志、原因、重复请求、冲突拦截、失败请求回滚和测试数据回滚；统一权限测试通过 22 项断言。完整 `tests/refactor` 28 个脚本共 397 项断言通过；测试结束后订单 1732、明细 1733、采购流水 1477，操作请求、订单事件、网关尝试和商家授权日志均为 0；目标语法和 `git diff --check` 通过。
- 遗留问题：灰度及正式环境必须依次执行授权日志建表迁移和本阶段字段扩展迁移；后台页面尚未提供绑定会员选择、解绑确认和原因输入，普通角色还需在菜单权限数据中分配新增接口 URL。商家审核仍使用 `MerchantService::auth()`，重新审核不改变绑定和超管状态尚未由独立审核命令测试固化。
- 下一阶段应继续处理的事项：重新读取计划和日志后，拆分商家审核命令；保留旧 URL 和返回结构，增加幂等、审核人/来源/原因审计，并用数据库测试证明重复审核以及重新审核均不会新增、取消或转移会员绑定和超管权限。

## 2026-08-25 渐进式重构阶段三：商家审核命令与审计

- 阶段名称：渐进式重构阶段三：商家审核命令与审计
- 本阶段完成内容：新增 `MerchantReviewService` 并保留管理员及移动端原审核 URL、参数和返回结构，`MerchantService::auth()` 作为公开兼容壳统一转调新命令，旧实现降为私有基线方法。审核仅处理待审商家，商家状态、默认桌面端角色/管理员、幂等完成和审核审计日志在同一事务；审核人、来源端、请求编号、审核前后状态和原因均可追溯。审核通过会复用已有默认管理员，避免商家重新进入待审后重复创建账号；admin-next 和移动端继续使用统一 `platform.merchant.review` 后端权限断言。审核命令不写 `member_id` 或 `member_is_super`。
- 修改/新增的主要文件：`app/common/service/merchant/MerchantReviewService.php`、`app/common/service/merchant/MerchantService.php`、`app/admin/controller/merchant/Merchant.php`、`app/api/controller/admin/MobileAdmin.php`、`tests/refactor/merchant-review-database-integration.php`、`DEVELOPMENT_LOG.md`
- 运行或测试结果：商家审核数据库集成测试通过 14 项断言，覆盖审核通过、默认管理员可用、审核日志、重复请求回放、重新审核不重复创建管理员、拒绝原因及审计，并证明首次审核、重复请求、重新审核和拒绝审核都不会新增、取消或转移会员绑定及会员超管权限。完整 `tests/refactor` 29 个脚本共 411 项断言通过；测试结束后订单 1732、明细 1733、采购流水 1477，操作请求、订单事件、网关尝试和商家授权日志均为 0；目标 PHP 语法和 `git diff --check` 通过。
- 遗留问题：旧审核实现仍以私有 `legacyAuth()` 留在超大 `MerchantService` 中，待阶段四拆分时删除；灰度及正式环境仍需先部署授权审计迁移。admin-next 商家页面目前没有独立会员绑定/解绑和授权原因交互，也没有审计历史查看入口；普通审核角色的菜单权限数据仍需在迁移/页面阶段配置。
- 下一阶段应继续处理的事项：重新读取计划和日志后，为 admin-next 商家管理补齐独立会员绑定/解绑、超管授予/取消及授权审计历史交互；高风险操作必须展示目标商家、当前/目标会员、影响权限并要求确认，保留现有资料编辑和审核操作。
