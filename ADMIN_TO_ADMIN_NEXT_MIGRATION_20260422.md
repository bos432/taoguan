# `admin` -> `admin-next` 迁移清单

日期：2026-04-22

## 一、目标

以 `zflAdminWeb` 作为可维护源码基座，参考 `public/admin-next` 的菜单结构、布局和模块划分，逐步把旧后台源码升级为“统一后台”形态。

本方案遵循两个原则：

- 不直接覆盖现有线上可运行的 `public/admin-next`
- 先完成“壳子统一 + 路由统一 + 商家模块对齐”，再逐页补齐差异功能

---

## 二、当前事实基线

### 1. 可维护源码基座

当前可维护后台源码工程：

- `zflAdminWeb`

当前 `zflAdminWeb` 已有模块目录：

- `content`
- `file`
- `goods`
- `inspection`
- `member`
- `merchant`
- `order`
- `setting`
- `system`
- `trace`

当前 `zflAdminWeb` 已有主要页面：

- `content/category.vue`
- `content/content.vue`
- `content/setting.vue`
- `content/tag.vue`
- `file/file.vue`
- `file/group.vue`
- `file/setting.vue`
- `file/tag.vue`
- `goods/goods.vue`
- `goods/label.vue`
- `goods/type.vue`
- `inspection/inspection.vue`
- `inspection/menu.vue`
- `member/api.vue`
- `member/group.vue`
- `member/log.vue`
- `member/member.vue`
- `member/setting.vue`
- `member/statistic.vue`
- `member/tag.vue`
- `member/third.vue`
- `merchant/menu.vue`
- `merchant/merchant.vue`
- `order/list.vue`
- `setting/accord.vue`
- `setting/call.vue`
- `setting/carousel.vue`
- `setting/delivery.vue`
- `setting/feedback.vue`
- `setting/hall.vue`
- `setting/link.vue`
- `setting/notice.vue`
- `setting/region.vue`
- `setting/setting.vue`
- `setting/warehouse.vue`
- `system/apidoc.vue`
- `system/dept.vue`
- `system/index.vue`
- `system/menu.vue`
- `system/notice.vue`
- `system/post.vue`
- `system/role.vue`
- `system/setting.vue`
- `system/user-center.vue`
- `system/user-log.vue`
- `system/user.vue`
- `trace/batch.vue`

### 2. `admin-next` 已核实的目标模块

从 `public/admin-next/assets/index-Dl7FiZWY.js` 已确认的目标模块：

- `/dashboard`
- `/analytics`
- `/analytics/merchant/:merchantId`
- `/exports`
- `/goods/type`
- `/goods/label`
- `/goods/goods`
- `/content/category`
- `/content/content`
- `/file/file`
- `/file/group`
- `/file/tag`
- `/member/member`
- `/member/tag`
- `/member/group`
- `/member/log`
- `/member/third`
- `/member/api`
- `/setting/carousel`
- `/setting/notice`
- `/setting/accord`
- `/setting/feedback`
- `/setting/link`
- `/setting/region`
- `/setting/delivery`
- `/system/notice`
- `/system/user-log`
- `/system/menu`
- `/system/role`
- `/system/dept`
- `/system/post`
- `/system/user`
- `/system/user-center`
- `/system/apidoc`
- `/system/setting`
- `/order/order`
- `/merchant/merchant`
- `/merchant/menu`
- `/legacy/:legacyPath`

---

## 三、总体判断

### 1. 结论

`zflAdminWeb` 与 `admin-next` 的主业务模块重叠度很高。

这意味着：

- 不是从零重建
- 也不是先补功能再补结构
- 更合理的是先统一“壳子、路由、菜单层级、命名方式”，再补 `admin-next` 独有页面

### 2. 迁移难度判断

最容易复用的是：

- 商品、内容、文件、会员、业务设置、系统管理、订单、商家菜单

中等工作量的是：

- 首页控制台改造成统一后台风格
- 商家管理页与 `admin-next` 行为、样式、字段结构对齐

新增工作量较明显的是：

- 平台数据中心 `/analytics`
- 商家详情钻取 `/analytics/merchant/:merchantId`
- 导出中心 `/exports`
- 旧模块承接 `/legacy/:legacyPath`
- 兜底占位页 `placeholder`

---

## 四、模块迁移分类

### A. 可直接复用并仅需路由/菜单重组

以下模块在 `zflAdminWeb` 已存在，对齐到 `admin-next` 时可优先复用：

- 商品分类：`goods/type.vue`
- 商品标签：`goods/label.vue`
- 商品管理：`goods/goods.vue`
- 内容分类：`content/category.vue`
- 内容管理：`content/content.vue`
- 文件管理：`file/file.vue`
- 文件分组：`file/group.vue`
- 文件标签：`file/tag.vue`
- 会员管理：`member/member.vue`
- 会员标签：`member/tag.vue`
- 会员分组：`member/group.vue`
- 会员日志：`member/log.vue`
- 第三方账号：`member/third.vue`
- 会员接口：`member/api.vue`
- 轮播图管理：`setting/carousel.vue`
- 通知公告：`setting/notice.vue`
- 协议管理：`setting/accord.vue`
- 意见反馈：`setting/feedback.vue`
- 友情链接：`setting/link.vue`
- 地区管理：`setting/region.vue`
- 配送设置：`setting/delivery.vue`
- 系统通知：`system/notice.vue`
- 操作日志：`system/user-log.vue`
- 菜单管理：`system/menu.vue`
- 角色管理：`system/role.vue`
- 部门管理：`system/dept.vue`
- 职位管理：`system/post.vue`
- 用户管理：`system/user.vue`
- 个人中心：`system/user-center.vue`
- 接口文档：`system/apidoc.vue`
- 系统设置：`system/setting.vue`
- 商家菜单：`merchant/menu.vue`

判断：

- 这些页面大多已经有现成业务逻辑
- 第一阶段不需要大改功能
- 优先做路径命名、菜单归组、页面标题与面包屑统一

### B. 已有基础，但要重点对齐到 `admin-next`

#### 1. 首页控制台

现有页面：

- `system/index.vue`

目标对齐：

- `admin-next` 的 `/dashboard`
- 标题改为“控制台总览”
- 顶部信息、模块总览、快捷入口风格统一

判断：

- 可复用已有统计组件
- 需要明显调整布局与视觉层次

#### 2. 订单管理

现有页面：

- `order/list.vue`

目标对齐：

- `admin-next` 的 `/order/order`

判断：

- 功能主体可复用
- 路由名称与菜单结构需调整

#### 3. 商家管理

现有页面：

- `merchant/merchant.vue`

目标对齐：

- `admin-next` 的 `/merchant/merchant`

当前已知已补能力：

- 审核状态筛选
- 期限状态筛选
- 新建商家
- 编辑商家
- 商家详情
- 停用 / 启用商家
- 设为 / 取消商家超管
- 删除商家
- 商家审核
- 商家续期
- 续费记录

判断：

- 这是第一阶段最值得优先对齐的页面
- 因为后端接口已具备，且业务价值高
- 后续应继续把页面结构、交互文案、状态卡片、详情抽屉向 `admin-next` 靠拢

### C. `admin-next` 独有，需新增页面或承接机制

以下是当前 `zflAdminWeb` 没有直接对应页面，但 `admin-next` 已存在的能力：

- 平台数据中心：`/analytics`
- 商家数据钻取详情：`/analytics/merchant/:merchantId`
- 导出中心：`/exports`
- 旧模块承接页：`/legacy/:legacyPath`
- 兜底占位页：`placeholder`

判断：

- 这些属于统一后台的“新能力”
- 不建议和第一阶段混在一起同时做
- 先把壳子和主业务对齐，再单独补

### D. `zflAdminWeb` 现有但不在 `admin-next` 主菜单中的页面

以下页面在 `zflAdminWeb` 中存在，但未在当前 `admin-next` 主路由中直接体现：

- `content/setting.vue`
- `content/tag.vue`
- `file/setting.vue`
- `inspection/inspection.vue`
- `inspection/menu.vue`
- `member/setting.vue`
- `member/statistic.vue`
- `setting/call.vue`
- `setting/hall.vue`
- `setting/setting.vue`
- `setting/warehouse.vue`
- `trace/batch.vue`

建议处理方式：

- 不删除
- 第二阶段前先保留
- 后续通过两种方式接入：
  - 并入新菜单
  - 放入 `/legacy/:legacyPath` 承接体系

---

## 五、推荐实施顺序

### 第一阶段：壳子统一

目标：

- 先把 `zflAdminWeb` 的后台框架升级成 `admin-next` 的统一后台感

建议任务：

- 重做 `layout`
- 重做左侧菜单与顶部栏
- 顶部增加“打开旧后台”
- 统一标题、面包屑、品牌区
- 调整为 `admin-next` 风格的菜单归组：
  - 控制台总览
  - 平台运营
  - 商品管理
  - 内容资讯
  - 文件管理
  - 商家管理
  - 会员管理
  - 业务设置
  - 系统管理
  - 订单管理

验证标准：

- 不改业务接口时，现有主要页面可以继续进入
- 菜单结构与 `admin-next` 的视觉和层级明显接近

### 第二阶段：路由统一

目标：

- 让 `zflAdminWeb` 的路径体系向 `admin-next` 靠拢

建议任务：

- 路由命名改为：
  - `/dashboard`
  - `/goods/type`
  - `/goods/label`
  - `/goods/goods`
  - `/content/category`
  - `/content/content`
  - `/file/file`
  - `/file/group`
  - `/file/tag`
  - `/member/member`
  - `/member/tag`
  - `/member/group`
  - `/member/log`
  - `/member/third`
  - `/member/api`
  - `/setting/carousel`
  - `/setting/notice`
  - `/setting/accord`
  - `/setting/feedback`
  - `/setting/link`
  - `/setting/region`
  - `/setting/delivery`
  - `/system/notice`
  - `/system/user-log`
  - `/system/menu`
  - `/system/role`
  - `/system/dept`
  - `/system/post`
  - `/system/user`
  - `/system/user-center`
  - `/system/apidoc`
  - `/system/setting`
  - `/order/order`
  - `/merchant/merchant`
  - `/merchant/menu`

验证标准：

- 主要业务页面路径与 `admin-next` 对齐
- 页面标题、菜单选中、面包屑正确

### 第三阶段：商家模块对齐

目标：

- 先把最关键、你最关心的商家模块做成 `admin-next` 同风格、同能力

建议任务：

- 对齐 `merchant/merchant.vue`
- 保持已补功能可用
- 统一状态卡片、筛选区、操作区、详情抽屉、续期弹窗、续费记录抽屉
- 如有必要，增加商家详情页独立路由

验证标准：

- 商家管理的功能、字段和交互达到 `admin-next` 当前可见水平

### 第四阶段：补 `admin-next` 独有模块

目标：

- 逐步补齐真正缺失的新后台能力

建议任务：

- 新增平台数据中心 `/analytics`
- 新增商家钻取详情 `/analytics/merchant/:merchantId`
- 新增导出中心 `/exports`
- 新增旧模块承接页 `/legacy/:legacyPath`
- 新增兜底占位页

验证标准：

- `zflAdminWeb` 已不仅是旧后台，而是可替代 `admin-next` 的源码版统一后台

### 第五阶段：单独目录测试，再决定替换

当前 `zflAdminWeb` 生产环境配置：

- `VITE_APP_BASE = '/admin'`

建议测试方式：

- 先把构建输出改到单独测试目录，例如 `public/admin-next-dev`
- 或者新增一个独立构建模式，输出到 `public/admin-next`

建议顺序：

1. 先保留现有 `public/admin-next`
2. 新源码先部署到独立测试目录
3. 验证通过后再替换正式目录

这样做的好处：

- 不影响现有项目功能
- 新旧后台可并行对照测试
- 回退简单

---

## 六、建议的首批开发清单

如果按“先做最值钱的部分”排序，建议第一批就做下面 4 项：

1. 重做 `layout`，让 `zflAdminWeb` 具备 `admin-next` 的统一后台外壳
2. 统一路由命名与菜单分组
3. 把商家管理页完全对齐到 `admin-next`
4. 新增旧模块承接页 `/legacy/:legacyPath`

原因：

- 这 4 项一完成，后台的“新旧感受差异”会立刻缩小
- 同时不会过早陷入数据中心和导出中心这类重页面开发

---

## 七、最终判断

这条路线是当前最稳、最可控的路线。

因为：

- `zflAdminWeb` 有源码，可持续维护
- `admin-next` 有明确可参考的成品结构
- 后端接口已经支撑大部分业务
- 可以先做结构升级，再做功能补齐

一句话总结：

不是“去找不到源码的 `admin-next`”，而是“把现有可维护的 `zflAdminWeb` 演进成源码版 `admin-next`”。
