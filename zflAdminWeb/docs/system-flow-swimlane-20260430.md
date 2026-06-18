# 系统角色泳道图（前后端协同版）

更新时间：2026-04-30

适用场景：

- 测试拆用例
- 研发对齐前后端职责
- 运营理解各环节由谁处理

## 1. 买家下单到审核完成泳道图

```mermaid
sequenceDiagram
    participant U as 买家 / C端
    participant FE as uni-app 前端
    participant BE as ThinkPHP 后端
    participant OP as 后台运营
    participant DB as 数据库 / 文件

    U->>FE: 打开登录页
    FE-->>U: 协议默认未勾选
    U->>FE: 勾选协议并登录
    FE->>BE: 提交登录请求
    BE->>DB: 校验账号 / 记录登录态
    DB-->>BE: 返回结果
    BE-->>FE: 登录成功
    U->>FE: 浏览商品并提交订单
    FE->>BE: 创建订单
    BE->>DB: 保存订单
    BE-->>FE: 返回付款信息
    FE-->>U: 展示商家收款码
    U->>FE: 上传付款凭证
    FE->>BE: 提交凭证
    BE->>DB: 保存图片和订单状态
    BE-->>FE: 返回待审核
    OP->>BE: 后台审核付款
    BE->>DB: 更新审核状态 / 写日志
    BE-->>OP: 返回审核结果
    BE-->>FE: 后续查询时返回最新订单状态
```

## 2. 商家维护收款码泳道图

```mermaid
sequenceDiagram
    participant OP as 后台运营
    participant AFE as admin-next
    participant BE as ThinkPHP 后端
    participant FS as 文件存储
    participant DB as 商家数据表
    participant UFE as C端付款页

    OP->>AFE: 进入商家管理并点击编辑
    AFE->>BE: 请求商家详情
    BE->>DB: 读取商家资料
    DB-->>BE: 返回商家资料
    BE-->>AFE: 返回详情和收款码
    OP->>AFE: 上传或更换收款码
    AFE->>BE: 提交图片 / 文件选择结果
    BE->>FS: 保存或关联文件
    FS-->>BE: 返回文件地址或标识
    BE->>DB: 更新商家收款码
    DB-->>BE: 保存成功
    BE-->>AFE: 编辑成功并回显
    UFE->>BE: 付款页读取订单付款信息
    BE->>DB: 查所属商家收款码
    DB-->>BE: 返回最新收款码
    BE-->>UFE: 返回付款展示数据
```

## 3. 内部接盘对账泳道图

```mermaid
sequenceDiagram
    participant OP as 运营人员
    participant AFE as admin-next 报表页
    participant BE as 报表服务
    participant DB as 订单/商品/账单/日志

    OP->>AFE: 打开内部接盘对账页面
    AFE->>BE: 请求筛选项和汇总
    BE->>DB: 聚合内部号、订单、账单、归属数据
    DB-->>BE: 返回聚合结果
    BE-->>AFE: 返回统计卡片和健康面板

    OP->>AFE: 按内部号/日期/状态筛选
    AFE->>BE: 请求列表
    BE->>DB: 查询接盘订单
    DB-->>BE: 返回原始数据
    BE-->>AFE: 返回待审核/待转/已完成/异常分类结果

    OP->>AFE: 查看某笔订单详情
    AFE->>BE: 请求详情
    BE->>DB: 读取订单、账单、商品、凭证、日志
    DB-->>BE: 返回详情数据
    BE-->>AFE: 返回详情弹窗数据

    OP->>AFE: 点击一键转当前待转商品
    AFE->>BE: 发起批量转入请求
    BE->>DB: 更新商品归属并写日志
    DB-->>BE: 返回处理结果
    BE-->>AFE: 返回成功并刷新统计
```

## 4. 后台栏目通用交互泳道图

```mermaid
sequenceDiagram
    participant OP as 运营人员
    participant AFE as admin-next 页面
    participant BE as 后端接口
    participant DB as 数据库

    OP->>AFE: 输入筛选条件
    AFE->>BE: 请求列表数据
    BE->>DB: 查询数据
    DB-->>BE: 返回结果
    BE-->>AFE: 列表回显
    OP->>AFE: 点击新增 / 编辑 / 审核 / 批量操作
    AFE->>BE: 提交表单或操作请求
    BE->>DB: 写入数据并记录日志
    DB-->>BE: 返回成功或失败
    BE-->>AFE: 返回提示信息
    AFE-->>OP: 刷新列表 / 弹窗回显
```

## 5. 前后端职责拆分

### 前端负责

- 页面承接和路由可达
- 表单录入、预校验、按钮禁用态
- 列表筛选、详情弹窗、结果回显
- 上传、预览、删除、二次确认

### 后端负责

- 登录态与权限校验
- 业务状态识别
- 订单、商品、账单、商家数据一致性
- 文件保存和实际读写
- 日志、异常、批量处理规则

## 6. 测试拆用例建议

1. 买家登录流程
2. 协议未勾选拦截流程
3. 商品浏览与下单流程
4. 付款凭证提交与后台审核流程
5. 商家收款码维护与前端付款展示联动
6. 内部接盘对账筛选、详情、导出、一键转入
7. 后台各栏目列表、弹窗、批量操作、结果回显

## 7. 导出建议

- Mermaid 支持的 Markdown 编辑器可直接导出 PDF
- Mermaid Live Editor 可导出单张 SVG / PNG
- 也可以把每个泳道图分别导出后放进 PPT 或 Word

