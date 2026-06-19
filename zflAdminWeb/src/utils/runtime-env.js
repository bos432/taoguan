function normalizeBaseUrl(url = '') {
  return String(url || '').trim().replace(/\/+$/, '')
}

function resolveAdminRuntimeEnv() {
  const baseUrl = normalizeBaseUrl(import.meta.env.VITE_APP_BASE_URL || window.location.origin)
  const appBase = String(import.meta.env.VITE_APP_BASE || '/').trim()
  const outDir = String(import.meta.env.VITE_APP_OUT_DIR || '').trim()

  let key = 'prod'
  let label = '正式环境'
  let dataMode = '真实写入'
  let tone = 'danger'

  if (appBase.includes('admin-next-dev') || baseUrl.includes('gray.')) {
    key = 'gray'
    label = '灰度环境'
    dataMode = '灰度隔离'
    tone = 'warning'
  } else if (
    baseUrl.includes('127.0.0.1') ||
    baseUrl.includes('localhost') ||
    outDir.includes('public/admin-next')
  ) {
    key = 'local'
    label = '本地联调'
    dataMode = '本地测试'
    tone = 'primary'
  }

  return {
    key,
    label,
    tone,
    dataMode,
    baseUrl,
    appBase,
    outDir,
    isProd: key === 'prod',
    isGray: key === 'gray',
    isLocal: key === 'local'
  }
}

function getAdminRuntimeHint(envInfo = resolveAdminRuntimeEnv()) {
  if (envInfo.isProd) {
    return '当前后台会直接影响线上真实数据，执行禁用、删除、审核和迁移前请先复核。'
  }
  if (envInfo.isGray) {
    return '当前后台适合做灰度回归，请优先验证关键流程和权限链路，再切正式目录。'
  }
  return '当前后台适合本地联调和功能验收，不要把测试操作当作正式运营结果。'
}

export { getAdminRuntimeHint, resolveAdminRuntimeEnv }
