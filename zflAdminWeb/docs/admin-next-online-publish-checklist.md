# admin-next 线上发布清单

## 目标

把源码版后台发布到线上 `/admin-next/` 路径，并保留可回滚能力。

当前线上构建包目录：
[dist-admin-next-online](E:\2025\重庆分公司\涛冠\2026第二版本\zflAdminWeb\dist-admin-next-online)

当前线上构建配置：
[`.env.admin-next-online`](E:\2025\重庆分公司\涛冠\2026第二版本\zflAdminWeb\.env.admin-next-online)

## 发布前准备

1. 在 [zflAdminWeb](E:\2025\重庆分公司\涛冠\2026第二版本\zflAdminWeb) 执行：

```bash
npm run build:admin-next-online
```

说明：
- 构建前会先执行环境守卫脚本 `validate-admin-next-env.mjs`
- 若 `VITE_APP_BASE_URL` 仍为本地地址，或 `VITE_APP_OUT_DIR` 错误指向 `public/admin-next` / `public/admin-next-dev`，构建会直接失败

2. 确认产物目录存在：

- [dist-admin-next-online](E:\2025\重庆分公司\涛冠\2026第二版本\zflAdminWeb\dist-admin-next-online)

3. 确认目录结构只有这些核心文件：

- `index.html`
- `css/`
- `js/`
- `img/`
- `favicon.ico`
- `robots.txt`

4. 确认线上环境的后端接口地址与权限已经准备好。

## 需要上传的内容

把 [dist-admin-next-online](E:\2025\重庆分公司\涛冠\2026第二版本\zflAdminWeb\dist-admin-next-online) 目录中的全部内容上传到服务器站点的：

- `public/admin-next/`

上传的是“目录内文件”，不是外层文件夹本身。

## 推荐发布步骤

1. 先在服务器上备份现有 `public/admin-next/`
2. 清空服务器当前 `public/admin-next/`
3. 上传 [dist-admin-next-online](E:\2025\重庆分公司\涛冠\2026第二版本\zflAdminWeb\dist-admin-next-online) 内全部文件
4. 刷新缓存或 CDN
5. 打开线上 `/admin-next/` 进行冒烟验证

## 服务器回滚建议

如果线上目录是 Linux 服务器，建议回滚时保留一份时间戳备份：

```bash
mv public/admin-next public/admin-next-backup-$(date +%Y%m%d-%H%M%S)
cp -r public/admin-next-backup-上一个稳定版本 public/admin-next
```

如果线上目录是 Windows 服务器，可参考：

```powershell
Rename-Item -LiteralPath 'public\\admin-next' -NewName 'admin-next-backup-20260422'
Copy-Item -LiteralPath 'public\\admin-next-backup-上一个稳定版本' -Destination 'public\\admin-next' -Recurse
```

## 发布后验证

上线后至少验证这些路径：

1. 登录页是否正常打开
2. 登录后是否进入控制台
3. 商家管理页是否能正常加载列表
4. `/analytics` 是否正常显示图表、榜单、预警
5. `/exports` 是否能正常下载 CSV
6. 退出登录是否有效

## 发布后重点观察

- 浏览器控制台是否有静态资源 404
- `index.html` 中的 `/admin-next/js/...` 与 `/admin-next/css/...` 是否能正确加载
- 菜单是否出现“平台运营”
- `/platform` 下的 `analytics` 和 `exports` 是否有权限
- 导出接口是否被线上网关或鉴权拦截

## 不要做的事

- 不要把 [public/admin-next](E:\2025\重庆分公司\涛冠\2026第二版本\public\admin-next) 本地候选目录直接打包上传
- 不要用 [`.env.admin-next-local`](E:\2025\重庆分公司\涛冠\2026第二版本\zflAdminWeb\.env.admin-next-local) 产物替代线上包
- 不要绕过 `validate:admin-next-online` 直接手动拼接 `vite build --mode admin-next-online`
- 不要跳过服务器目录备份

## 当前建议

本地候选验证继续使用：

- `npm run build-and-audit:admin-next-local`

线上发布只使用：

- `npm run build:admin-next-online`
