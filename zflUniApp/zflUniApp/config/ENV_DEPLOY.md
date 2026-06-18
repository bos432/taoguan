# uni-app 环境配置（test / gray / prod）

## 目的

满足 [PLAN.md](../../../PLAN.md) 与 [frontend-env-release-checklist.md](../md-doc/frontend-env-release-checklist.md)：**测试与灰度包不得默认指向正式可写库**；上线前由运维/负责人填写真实域名。

## 配置文件

- 主文件：[env.js](env.js)
- 样板文件：[env.profile.example.json](env.profile.example.json)
- 私有覆盖文件：`env.profile.local.json`（本地生成，不提交）

## 推荐做法

优先不要直接改 [env.js](E:\2025\重庆分公司\涛冠\2026第二版本\zflUniApp\zflUniApp\config\env.js) 里的默认占位地址。
建议在 [zflUniApp](E:\2025\重庆分公司\涛冠\2026第二版本\zflUniApp\zflUniApp) 下执行：

```bash
npm run env:local:init
```

执行后会生成：

- `config/env.profile.local.json`

然后只在这个本地私有文件里填写真实 `test` / `gray` 地址。
当前 `.gitignore` 已忽略该文件，避免把测试、灰度、正式前置地址误提交到源码里。

如果已经拿到真实地址，推荐直接用命令写入，而不是手改 JSON：

```bash
npm run env:set -- test https://test.your-domain.com https://test.your-domain.com/api 测试环境
npm run env:set -- gray https://gray.your-domain.com https://gray.your-domain.com/api 灰度环境
```

说明：
- 第一个参数是 profile，只支持 `test` / `gray` / `prod`
- 第二个参数是 `base_root_url`
- 第三个参数是 `api_root_url`
- 第四个参数可选，用于显示名称

写入后建议立刻执行：

```bash
npm run env:local:check
npm run release:check:test
npm run release:check:gray
npm run release:preflight:test
npm run release:preflight:gray
```

初始化后，建议先执行：

```bash
npm run env:local:check
npm run env:status
```

说明：
- `env:local:check` 会先校验 `config/env.profile.local.json` 是否存在、是否是合法 JSON、以及 `test / gray / prod` 三个 profile 是否都包含 `label / base_root_url / api_root_url`
- `env:status` 会输出当前所有 profile 的站点地址、接口地址、以及是否仍是 `local-host` / `example-host` 这类占位状态

如果 `env:local:check` 通过，但 `release:check:test` / `release:check:gray` 仍失败，通常说明结构没问题，但真实测试/灰度地址还没有填进去

```bash
npm run env:status
```

## 上线前必改

1. **`test`**：将 `base_root_url`、`api_root_url` 改为**独立测试环境**（与正式库隔离）。
2. **`gray`**：将上述字段改为**灰度入口**（仅小流量验证）。
3. **`prod`**：保持正式运营域名；发布前再次核对与小程序/H5 后台配置的域名一致。
4. **显式 profile**：构建测试包、灰度包、正式包时，优先显式传入 `UNI_APP_ENV_PROFILE=test|gray|prod`，不要依赖默认推断。

## 安全约定

- 勿将生产密钥、内网未授权地址提交到公开仓库；必要时使用 CI 注入或私有配置仓。
- 本地开发可继续使用 `dev` / `local` / `127.0.0.1`。
- 当前仓库中 `test` / `gray` 若仍为本地占位，属于**防误连**默认态；**出测试包/灰度包前必须替换**。
- 当前 `env.js` 已支持 `UNI_APP_ENV_PROFILE` / `VUE_APP_ENV_PROFILE` / `ENV_PROFILE` 显式指定环境。
- H5 非本地、非正式域名场景下，默认将回退到 `test`，不再自动落到 `prod`。
- 当前源码已增加正式环境切换锁；`prod` 覆盖切换需先显式解锁，不能再当作普通环境一键切换。
- 当前源码已增加请求侧环境守卫；非正式环境若配置到正式域名，请求会被直接拦截。
- 项目实际运行和发行以 `HBuilderX` 为主，执行步骤见 [uniapp-runtime-verification.md](../md-doc/uniapp-runtime-verification.md)。

## 体验版 / 正式版出包检查

## 环境校验命令

在目录 [zflUniApp](E:\2025\重庆分公司\涛冠\2026第二版本\zflUniApp\zflUniApp) 下执行：

```bash
npm run validate:env:local
```

说明：
校验本地联调配置是否仍指向 `127.0.0.1/localhost`。

```bash
npm run validate:env:test
npm run validate:env:gray
```

说明：
校验 `test` / `gray` 没有误指向正式域名；如果仍是本地占位地址，会给出 warning。

```bash
npm run release:check:test
npm run release:check:gray
npm run release:check:prod
npm run release:preflight:test
npm run release:preflight:gray
npm run release:preflight:prod
```

说明：
用于出测试包、灰度包、正式包前的严格校验。
- `release:check:test` / `release:check:gray` 会直接拦截仍回退本地地址的配置
- `release:check:prod` 会直接拦截仍指向本地地址或未完全指向正式域名的配置
- `release:preflight:*` 会额外串联源码级运行准备度、私有环境文件结构、环境状态总览，并输出报告到 `runtime/release-preflight/`

当前这套源码如果 `test` / `gray` 还没换成独立环境，严格校验会失败，这是预期行为。

- [ ] `test`、`gray` 已替换且自测可访问  
- [ ] 已显式指定 `UNI_APP_ENV_PROFILE`，并确认运行页环境标签一致  
- [ ] 在对应 profile 下走一遍登录、协议、结算、商家入驻  
- [ ] 与 [PLAN.md](../../../PLAN.md) 中 uni-app 验收盘点表 P0 项对照签字  
