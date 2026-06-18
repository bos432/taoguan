import fs from 'node:fs/promises'
import path from 'node:path'
import { fileURLToPath } from 'node:url'
import { chromium, request } from '@playwright/test'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const adminRoot = path.resolve(__dirname, '..')
const workspaceRoot = path.resolve(adminRoot, '..')
const uniRoot = path.join(workspaceRoot, 'zflUniApp', 'zflUniApp')
const localDir = path.join(workspaceRoot, 'local')

const origin = process.env.FULL_STACK_SMOKE_ORIGIN || 'http://127.0.0.1:807'
const adminBase = `${origin}/admin-next/`
const h5Base = `${origin}/app/`
const adminUser = process.env.FULL_STACK_SMOKE_USER || 'admin'
const passwordCandidates = [
  process.env.FULL_STACK_SMOKE_PASSWORD,
  '123456',
  'star1229',
].filter(Boolean)

const adminSlowThreshold = Number(process.env.FULL_STACK_SMOKE_ADMIN_SLOW_MS || 3200)
const h5SlowThreshold = Number(process.env.FULL_STACK_SMOKE_H5_SLOW_MS || 2600)

const adminReportFile = path.join(localDir, 'admin-menu-report.json')
const smokeReportFile = path.join(localDir, 'smoke-report.json')
const smokeMarkdownFile = path.join(localDir, 'smoke-report.md')
const adminRouteAliasMap = {
  '/internal-takeover': '/report/internal-takeover',
  '/platform/analytics': '/analytics',
  '/platform/exports': '/exports',
  '/tag': '/member/tag',
  '/group': '/member/group',
  '/api': '/member/api',
  '/statistic': '/member/statistic',
  '/log': '/member/log',
  '/third': '/member/third',
  '/membersetting': '/setting/membersetting',
  '/contentsetting': '/setting/contentsetting',
  '/filesetting': '/setting/filesetting',
  '/carousel': '/setting/carousel',
  '/notice': '/setting/notice',
  '/accord': '/setting/accord',
  '/feedback': '/setting/feedback',
  '/link': '/setting/link',
  '/region': '/setting/region',
  '/menu': '/system/menu',
  '/role': '/system/role',
  '/dept': '/system/dept',
  '/post': '/system/post',
  '/user': '/system/user',
  '/user-log': '/system/user-log',
  '/user-center': '/system/user-center',
  '/internal-merchant': '/system/internal-merchant',
  '/apidoc': '/system/apidoc',
}
const acceptableH5LoginPages = new Set([
  'pages/app/my',
  'pages/app/release',
  'pages/goods/my_cart',
  'pages/my/login',
  'pages/system/accord-center',
  'pages/my/set-up',
  'pages/order/list',
  'pages/order/mer_list',
  'pages/merchant/analytics',
  'pages/merchant/renew',
  'pages/merchant/apply',
  'pages/admin/merchant-audit',
  'pages/admin/order-audit',
])
const acceptableH5ParamPages = new Set([
  'pages/goods/settlement',
  'pages/goods/trace',
  'pages/goods/details',
  'pages/system/accord',
  'pages/news/details',
  'pages/notice/detail',
  'pages/order/service',
  'pages/order/evaluate',
  'pages/order/logistics',
])

function sleep(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms))
}

function textSample(value, max = 1200) {
  return String(value || '')
    .replace(/\s+/g, '\n')
    .trim()
    .slice(0, max)
}

function classifyParamRequired(sample = '') {
  return [
    '参数异常',
    '参数不完整',
    '请返回商品列表重新进入',
    '请返回购物车重新下单',
    '查询编号：未提供',
  ].some((keyword) => sample.includes(keyword))
}

function classifyLoginGate(sample = '', finalUrl = '') {
  return finalUrl.includes('/login') || ['点击登录', '请登录', '登录后'].some((keyword) => sample.includes(keyword))
}

function classifyParamFallback(sample = '') {
  return [
    '参数缺失承接态',
    '入口参数缺失',
    '查询编码：未提供',
    '资讯编号：未提供',
    '公告编号：未提供',
  ].some((keyword) => sample.includes(keyword))
}

function classifyWeakAcceptance(sample = '', finalUrl = '') {
  return classifyParamRequired(sample) || classifyParamFallback(sample) || classifyLoginGate(sample, finalUrl)
}

function isAcceptableH5WeakState(label = '', sample = '', finalUrl = '') {
  if (!label) return false
  if (acceptableH5LoginPages.has(label)) {
    if (label === 'pages/admin/merchant-audit' || label === 'pages/admin/order-audit') {
      return sample.includes('权限：只读查看') && classifyLoginGate(sample, finalUrl)
    }
    const hasWorkbench = ['当前环境', '环境就绪看板', '发布状态', '隔离状态'].every((keyword) => sample.includes(keyword))
    if (label === 'pages/my/login') {
      return sample.includes('协议默认不勾选') && sample.includes('《协议中心》')
    }
    return hasWorkbench && classifyLoginGate(sample, finalUrl)
  }
  if (acceptableH5ParamPages.has(label)) {
    return classifyParamRequired(sample) || classifyParamFallback(sample)
  }
  return false
}

function classifyNotFound(sample = '', finalUrl = '') {
  return finalUrl.includes('/404') || ['404', '页面不存在', 'Not Found'].some((keyword) => sample.includes(keyword))
}

function classifyBlank(sample = '') {
  return !sample || sample.length < 8
}

function hasTransientBufferError(logs = []) {
  return logs.some((item) => String(item || '').includes('ERR_NO_BUFFER_SPACE'))
}

function cleanPath(path = '') {
  return String(path || '').replace(/\/+/g, '/').replace(/\/$/, '') || '/'
}

function resolveRoutePath(parentPath = '/', routePath = '') {
  const current = String(routePath || '').trim()
  if (!current) return cleanPath(parentPath)
  if (current.startsWith('/')) return cleanPath(current)
  if (!parentPath || parentPath === '/') return cleanPath(`/${current}`)
  return cleanPath(`${parentPath}/${current}`)
}

function normalizeAdminRoutePath(routePath = '') {
  const fullPath = cleanPath(routePath)
  return adminRouteAliasMap[fullPath] || fullPath
}

function flattenMenus(menus = [], parentTitles = [], parentPath = '/') {
  const rows = []
  for (const item of menus) {
    const title = String(item.title || item.meta?.title || item.name || item.label || item.path || '').trim()
    const nextParents = title ? [...parentTitles, title] : parentTitles
    const children = Array.isArray(item.children) ? item.children : []
    const sourceFullPath = resolveRoutePath(parentPath, item.path || '')
    if (children.length) {
      rows.push(...flattenMenus(children, nextParents, sourceFullPath))
      continue
    }
    if (!sourceFullPath || sourceFullPath.startsWith('http')) {
      continue
    }
    rows.push({
      menu: title || sourceFullPath,
      routePath: normalizeAdminRoutePath(sourceFullPath),
      lineage: nextParents,
    })
  }
  return rows
}

function dedupeMenus(rows = []) {
  const seen = new Set()
  return rows.filter((item) => {
    const key = item.url
    if (!key || seen.has(key)) {
      return false
    }
    seen.add(key)
    return true
  })
}

async function readJson(file) {
  return JSON.parse(await fs.readFile(file, 'utf8'))
}

async function createAdminToken() {
  for (const password of passwordCandidates) {
    const api = await request.newContext()
    try {
      const response = await api.post(`${origin}/admin/system.Login/login?lang=zh-cn`, {
        data: {
          username: adminUser,
          password,
          captcha_id: '',
          captcha_code: '',
          ajcaptcha: {},
        },
      })
      const json = await response.json()
      if (Number(json.code) === 200 && json.data?.AdminToken) {
        return {
          token: json.data.AdminToken,
          passwordUsed: password,
        }
      }
    } catch (error) {
      // Continue trying the next candidate.
    } finally {
      await api.dispose()
    }
  }
  throw new Error('无法获取本地后台 AdminToken，请检查本地账号密码或登录接口状态。')
}

async function fetchAdminMenus(token) {
  const api = await request.newContext({
    extraHTTPHeaders: {
      AdminToken: token,
      'think-lang': 'zh-cn',
    },
  })
  try {
    const response = await api.get(`${origin}/admin/system.UserCenter/info`)
    const json = await response.json()
    if (Number(json.code) !== 200 || !Array.isArray(json.data?.menus)) {
      throw new Error(`后台菜单读取失败：${json.msg || 'unknown error'}`)
    }
    return dedupeMenus(
      flattenMenus(json.data.menus).map((item) => ({
        ...item,
        url: `${adminBase}#${item.routePath}`,
      }))
    )
  } finally {
    await api.dispose()
  }
}

async function seedAdminToken(page, token) {
  await page.addInitScript((tokenValue) => {
    window.localStorage.setItem('admin_AdminToken', tokenValue)
    window.localStorage.setItem('admin_tokenType', 'header')
    window.localStorage.setItem('admin_tokenName', 'AdminToken')
    window.localStorage.setItem('admin_layout', 'left')
    window.localStorage.setItem('admin_language', 'zh-cn')
  }, token)
  await page.goto(adminBase, { waitUntil: 'domcontentloaded' })
  await sleep(400)
  await page.reload({ waitUntil: 'domcontentloaded' })
  await sleep(900)
}

function normalizeHashPath(url = '') {
  const hashIndex = url.indexOf('#/')
  if (hashIndex === -1) {
    return ''
  }
  return url.slice(hashIndex + 2).split('?')[0].replace(/^\/+/, '')
}

async function collectPageState(page, url, type, slowThreshold, label) {
  const logs = []
  const onConsole = (msg) => {
    if (['error', 'warning'].includes(msg.type())) {
      logs.push(`[${msg.type()}] ${msg.text()}`)
    }
  }
  const onPageError = (error) => {
    logs.push(`[pageerror] ${error.message}`)
  }
  const onRequestFailed = (request) => {
    const failure = request.failure()
    if (!failure?.errorText) return
    logs.push(`[requestfailed] ${request.url()} :: ${failure.errorText}`)
  }

  page.on('console', onConsole)
  page.on('pageerror', onPageError)
  page.on('requestfailed', onRequestFailed)

  const startedAt = Date.now()
  let error = null
  let sample = ''

  async function visitOnce() {
    let visitError = null
    try {
      await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 30000 })
      await page.waitForLoadState('networkidle', { timeout: 15000 }).catch(() => {})
      await sleep(type === 'admin' ? 900 : 1200)
    } catch (navigationError) {
      visitError = navigationError.message
    }

    let visitSample = ''
    try {
      visitSample = textSample(
        await page.locator('body').innerText({ timeout: 5000 }).catch(() => '')
      )
    } catch (readError) {
      if (!visitError) {
        visitError = readError.message
      }
    }

    return {
      sample: visitSample,
      error: visitError,
    }
  }

  let visitResult = await visitOnce()
  sample = visitResult.sample
  error = visitResult.error

  if (type === 'h5' && classifyBlank(sample) && hasTransientBufferError(logs)) {
    logs.length = 0
    await sleep(600)
    visitResult = await visitOnce()
    sample = visitResult.sample
    error = visitResult.error
  }

  const finalUrl = page.url()

  page.off('console', onConsole)
  page.off('pageerror', onPageError)
  page.off('requestfailed', onRequestFailed)

  const weakAcceptance = classifyWeakAcceptance(sample, finalUrl)
  const acceptedWeakState =
    type === 'h5' && weakAcceptance
      ? isAcceptableH5WeakState(label, sample, finalUrl)
      : false

  return {
    type,
    label,
    url,
    finalUrl,
    routeMismatch: type === 'admin' ? normalizeHashPath(finalUrl) !== normalizeHashPath(url) : false,
    ms: Date.now() - startedAt,
    slow: Date.now() - startedAt > slowThreshold,
    blank: classifyBlank(sample),
    notFound: classifyNotFound(sample, finalUrl),
    bouncedToLogin: finalUrl.includes('#/login'),
    maybeParamRequired: classifyParamRequired(sample),
    paramFallback: classifyParamFallback(sample),
    weakAcceptance: weakAcceptance && !acceptedWeakState,
    acceptedWeakState,
    loginGate: classifyLoginGate(sample, finalUrl),
    sample,
    error,
    logs: logs.slice(0, 12),
  }
}

function buildAdminUrl(routePath) {
  const normalized = routePath.startsWith('/') ? routePath : `/${routePath}`
  return `${adminBase}#${normalized}`
}

function buildH5Url(pagePath) {
  return `${h5Base}${pagePath}`
}

function summarizeIssues(rows = []) {
  const slow = rows.filter((item) => item.slow)
  const broken = rows.filter(
    (item) =>
      item.blank ||
      item.notFound ||
      item.error ||
      item.routeMismatch ||
      (item.type === 'admin' && item.bouncedToLogin)
  )
  const weak = rows.filter(
    (item) => !broken.includes(item) && item.weakAcceptance
  )
  return { slow, broken, weak }
}

function toMarkdown(report) {
  const lines = [
    '# 全站关键页面冒烟清单',
    '',
    `- 生成时间：${report.generatedAt}`,
    `- 后台菜单页：${report.adminMenuCount}`,
    `- H5 页面：${report.h5Count}`,
    `- 后台慢页阈值：${adminSlowThreshold}ms`,
    `- H5 慢页阈值：${h5SlowThreshold}ms`,
    '',
    '## 总览',
    '',
    `- 后台异常：${report.summary.adminBroken}`,
    `- 后台慢页：${report.summary.adminSlow}`,
    `- 后台弱承接：${report.summary.adminWeak}`,
    `- H5 异常：${report.summary.h5Broken}`,
    `- H5 慢页：${report.summary.h5Slow}`,
    `- H5 弱承接：${report.summary.h5Weak}`,
    '',
  ]

  const sections = [
    ['后台 P0 异常', report.p0.adminBroken, 'admin'],
    ['H5 P0 异常', report.p0.h5Broken, 'h5'],
    ['后台慢页', report.p0.adminSlow, 'admin'],
    ['H5 慢页', report.p0.h5Slow, 'h5'],
    ['后台弱承接', report.p1.adminWeak, 'admin'],
    ['H5 弱承接', report.p1.h5Weak, 'h5'],
  ]

  for (const [title, rows] of sections) {
    lines.push(`## ${title}`)
    lines.push('')
    if (!rows.length) {
      lines.push('- 无')
      lines.push('')
      continue
    }
    for (const row of rows) {
      const name = row.menu || row.page || row.label
      const flags = []
      if (row.error) flags.push('error')
      if (row.blank) flags.push('blank')
      if (row.notFound) flags.push('404')
      if (row.routeMismatch) flags.push('route-mismatch')
      if (row.bouncedToLogin) flags.push('login')
      if (row.maybeParamRequired) flags.push('param')
      if (row.paramFallback) flags.push('fallback')
      if (row.weakAcceptance) flags.push('weak')
      if (row.slow) flags.push(`slow:${row.ms}ms`)
      lines.push(`- ${name} | ${row.finalUrl} | ${flags.join(', ') || 'ok'}`)
    }
    lines.push('')
  }

  lines.push('## 后台菜单结果')
  lines.push('')
  for (const row of report.admin) {
    lines.push(
      `- ${row.menu} | ${row.finalUrl} | ${row.ms}ms | ${row.routeMismatch ? 'route-mismatch' : row.blank ? 'blank' : row.notFound ? '404' : row.weakAcceptance ? 'weak' : 'ok'}`
    )
  }
  lines.push('')
  lines.push('## H5 页面结果')
  lines.push('')
  for (const row of report.h5) {
    lines.push(
      `- ${row.page} | ${row.finalUrl} | ${row.ms}ms | ${row.blank ? 'blank' : row.notFound ? '404' : row.weakAcceptance ? 'weak' : 'ok'}`
    )
  }
  lines.push('')

  return `${lines.join('\n')}\n`
}

async function main() {
  await fs.mkdir(localDir, { recursive: true })

  const { token, passwordUsed } = await createAdminToken()
  const browser = await chromium.launch({ headless: true })
  const context = await browser.newContext({ viewport: { width: 1440, height: 960 } })
  const adminPage = await context.newPage()
  await seedAdminToken(adminPage, token)
  const adminMenus = await fetchAdminMenus(token)
  const pagesJson = await readJson(path.join(uniRoot, 'pages.json'))
  const h5Pages = (pagesJson.pages || []).map((item) => String(item.path || '').trim()).filter(Boolean)

  const adminResults = []
  for (const item of adminMenus) {
    adminResults.push(
      {
        menu: item.menu,
        ...(await collectPageState(
          adminPage,
          item.url,
          'admin',
          adminSlowThreshold,
          item.menu
        )),
      }
    )
  }

  const h5Page = await context.newPage()
  const h5Results = []
  for (const pagePath of h5Pages) {
    h5Results.push(
      {
        page: pagePath,
        ...(await collectPageState(
          h5Page,
          buildH5Url(pagePath),
          'h5',
          h5SlowThreshold,
          pagePath
        )),
      }
    )
  }

  await context.close()
  await browser.close()

  const adminIssues = summarizeIssues(adminResults)
  const h5Issues = summarizeIssues(h5Results)

  const report = {
    generatedAt: new Date().toISOString(),
    origin,
    adminBase,
    h5Base,
    adminAuth: {
      username: adminUser,
      passwordUsed,
    },
    adminMenuCount: adminResults.length,
    h5Count: h5Results.length,
    summary: {
      adminBroken: adminIssues.broken.length,
      adminSlow: adminIssues.slow.length,
      adminWeak: adminIssues.weak.length,
      h5Broken: h5Issues.broken.length,
      h5Slow: h5Issues.slow.length,
      h5Weak: h5Issues.weak.length,
    },
    p0: {
      adminBroken: adminIssues.broken,
      h5Broken: h5Issues.broken,
      adminSlow: adminIssues.slow,
      h5Slow: h5Issues.slow,
    },
    p1: {
      adminWeak: adminIssues.weak,
      h5Weak: h5Issues.weak,
    },
    admin: adminResults,
    h5: h5Results,
  }

  await fs.writeFile(adminReportFile, `${JSON.stringify(adminResults, null, 2)}\n`, 'utf8')
  await fs.writeFile(smokeReportFile, `${JSON.stringify(report, null, 2)}\n`, 'utf8')
  await fs.writeFile(smokeMarkdownFile, toMarkdown(report), 'utf8')

  console.log(`[full-stack-smoke] admin=${adminResults.length} h5=${h5Results.length}`)
  console.log(`[full-stack-smoke] admin-broken=${adminIssues.broken.length} admin-slow=${adminIssues.slow.length} admin-weak=${adminIssues.weak.length}`)
  console.log(`[full-stack-smoke] h5-broken=${h5Issues.broken.length} h5-slow=${h5Issues.slow.length} h5-weak=${h5Issues.weak.length}`)
  console.log(`[full-stack-smoke] admin-report=${adminReportFile}`)
  console.log(`[full-stack-smoke] smoke-report=${smokeReportFile}`)
  console.log(`[full-stack-smoke] smoke-markdown=${smokeMarkdownFile}`)
}

main().catch((error) => {
  console.error('[full-stack-smoke] failed', error)
  process.exit(1)
})
