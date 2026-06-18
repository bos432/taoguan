import fs from 'node:fs'
import path from 'node:path'
import { fileURLToPath } from 'node:url'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const projectRoot = path.resolve(__dirname, '..')
const pagesJsonFile = path.join(projectRoot, 'pages.json')
const reportDir = path.join(projectRoot, 'runtime', 'runtime-readiness')
const reportJson = path.join(reportDir, 'latest.json')
const reportMd = path.join(reportDir, 'latest.md')
const agreementAuditReportJson = path.join(projectRoot, 'runtime', 'agreement-flow-audit', 'latest.json')

const checks = []

function ensureDir(dir) {
  fs.mkdirSync(dir, { recursive: true })
}

function readFile(file) {
  return fs.readFileSync(file, 'utf8')
}

function readJson(file) {
  return JSON.parse(readFile(file))
}

function normalize(value) {
  return String(value || '').trim()
}

function addCheck(name, status, summary, details = []) {
  checks.push({
    name,
    status,
    summary,
    details: Array.isArray(details) ? details : [details],
  })
}

function readJsonSafe(file) {
  if (!fs.existsSync(file)) {
    return null
  }
  return JSON.parse(readFile(file))
}

function statusRank(status) {
  if (status === 'FAIL') return 2
  if (status === 'WARN') return 1
  return 0
}

function inspectFile(file, rules = []) {
  const content = readFile(file)
  const missing = []

  rules.forEach((rule) => {
    const pattern = typeof rule === 'string' ? rule : rule.pattern
    const label = typeof rule === 'string' ? rule : rule.label || rule.pattern
    const matched = pattern instanceof RegExp ? pattern.test(content) : content.includes(pattern)
    if (!matched) {
      missing.push(label)
    }
  })

  return { content, missing }
}

function checkPageRoute(pages, pagePath, label) {
  const hit = pages.includes(pagePath)
  addCheck(
    `route:${pagePath}`,
    hit ? 'PASS' : 'FAIL',
    hit ? `${label}路由已注册` : `${label}路由缺失`,
    [pagePath]
  )
}

const pagesJson = readJson(pagesJsonFile)
const pagePaths = (pagesJson.pages || []).map((item) => normalize(item.path))

checkPageRoute(pagePaths, 'pages/my/login', '登录页')
checkPageRoute(pagePaths, 'pages/system/accord-center', '协议中心')
checkPageRoute(pagePaths, 'pages/system/accord', '协议详情')
checkPageRoute(pagePaths, 'pages/my/set-up', '设置页')
checkPageRoute(pagePaths, 'pages/goods/settlement', '结算页')
checkPageRoute(pagePaths, 'pages/merchant/apply', '商家申请页')
checkPageRoute(pagePaths, 'pages/app/home', '首页')
checkPageRoute(pagePaths, 'pages/app/my', '我的页')
checkPageRoute(pagePaths, 'pages/app/release', '发布页')

const loginFile = path.join(projectRoot, 'pages', 'my', 'login.vue')
const loginCheck = inspectFile(loginFile, [
  { pattern: 'showAgree: false', label: '协议默认不勾选' },
  { pattern: 'ensureAgreeAccepted("获取验证码")', label: '获取验证码前校验协议' },
  { pattern: 'if (!this.ensureAgreeAccepted())', label: '登录提交前校验协议' },
  { pattern: 'openAccordCenter()', label: '协议中心入口' },
  { pattern: `openAccord('user_agreement')`, label: '用户协议入口' },
  { pattern: `openAccord('privacy_policy')`, label: '隐私政策入口' },
  { pattern: 'consumeLoginRedirect(', label: '登录回跳承接' },
  { pattern: 'switchEnvProfile()', label: '登录页环境切换' },
])
addCheck(
  'login-agreement-guard',
  loginCheck.missing.length ? 'FAIL' : 'PASS',
  loginCheck.missing.length
    ? `登录页关键协议/回跳能力缺失：${loginCheck.missing.join('，')}`
    : '登录页协议默认态、协议入口、环境切换和回跳承接已接入',
  loginCheck.missing.length ? loginCheck.missing : ['pages/my/login.vue']
)

const setupFile = path.join(projectRoot, 'pages', 'my', 'set-up.vue')
const setupCheck = inspectFile(setupFile, [
  { pattern: 'switchEnvProfile()', label: '设置页环境切换' },
  { pattern: 'copyApiRoot()', label: '接口地址复制' },
  { pattern: 'copyBaseRoot()', label: '站点地址复制' },
  { pattern: 'openAccordCenter()', label: '协议中心入口' },
  { pattern: 'getAccordRuntimeSummary', label: '协议同步状态摘要' },
  { pattern: 'retryPendingAccordRuntime()', label: '设置页待补记重试' },
  { pattern: 'envIsolationStatusText', label: '隔离状态展示' },
  { pattern: 'submitReviewHint', label: '资料修改前复核提示' },
])
addCheck(
  'settings-runtime-controls',
  setupCheck.missing.length ? 'WARN' : 'PASS',
  setupCheck.missing.length
    ? `设置页运行环境/复核能力仍有缺口：${setupCheck.missing.join('，')}`
    : '设置页已具备环境切换、地址复制、协议中心和资料修改前复核能力',
  setupCheck.missing.length ? setupCheck.missing : ['pages/my/set-up.vue']
)

const accordCenterFile = path.join(projectRoot, 'pages', 'system', 'accord-center.vue')
const accordCenterCheck = inspectFile(accordCenterFile, [
  { pattern: `accord_id: "user_agreement"`, label: '用户协议卡片' },
  { pattern: `accord_id: "privacy_policy"`, label: '隐私政策卡片' },
  { pattern: `accord_id: "disclaimer"`, label: '免责声明卡片' },
  { pattern: `accord_id: "after_sales_policy"`, label: '售后协议卡片' },
  { pattern: 'retryPendingAccords()', label: '协议补记重试' },
  { pattern: 'getAccordRuntimeSummary', label: '协议运行态摘要' },
  { pattern: 'retryAllPending()', label: '协议中心一键重试' },
  { pattern: 'sync-card', label: '协议同步状态面板' },
  { pattern: 'buildLoginRedirectUrl("/pages/system/accord-center")', label: '协议中心登录回跳' },
  { pattern: 'getAccordStatus(', label: '协议状态刷新' },
])
addCheck(
  'accord-center-flows',
  accordCenterCheck.missing.length ? 'WARN' : 'PASS',
  accordCenterCheck.missing.length
    ? `协议中心仍缺少部分承接能力：${accordCenterCheck.missing.join('，')}`
    : '协议中心已具备协议列表、状态刷新、补记重试和登录回跳能力',
  accordCenterCheck.missing.length ? accordCenterCheck.missing : ['pages/system/accord-center.vue']
)

const settlementFile = path.join(projectRoot, 'pages', 'goods', 'settlement.vue')
const settlementCheck = inspectFile(settlementFile, [
  { pattern: 'agreeAfterSales', label: '售后协议勾选状态' },
  { pattern: `openAccord('after_sales_policy')`, label: '售后协议入口' },
  { pattern: /(bestEffortAcceptAccords|ensureAcceptAccords)\(\s*\{ scene: "order_confirm"/, label: '下单协议补记' },
  { pattern: 'buildEnvConfirmText(', label: '提交前环境风险提示' },
  { pattern: 'agreementReminderText', label: '协议提醒文案' },
  { pattern: 'envIsolationText', label: '环境隔离提示' },
])
addCheck(
  'settlement-agreement-flow',
  settlementCheck.missing.length ? 'WARN' : 'PASS',
  settlementCheck.missing.length
    ? `结算页协议/环境保护仍有缺口：${settlementCheck.missing.join('，')}`
    : '结算页已具备售后协议勾选、协议入口、补记和提交前环境确认能力',
  settlementCheck.missing.length ? settlementCheck.missing : ['pages/goods/settlement.vue']
)

const merchantApplyFile = path.join(projectRoot, 'pages', 'merchant', 'apply.vue')
const merchantApplyCheck = inspectFile(merchantApplyFile, [
  { pattern: 'agreeDisclaimer', label: '免责声明勾选状态' },
  { pattern: `openAccord('disclaimer')`, label: '免责声明入口' },
  { pattern: 'bestEffortAcceptAccords(', label: '入驻协议补记' },
  { pattern: 'buildEnvConfirmText(', label: '提交前环境风险提示' },
  { pattern: 'agreementReminderText', label: '协议提醒文案' },
  { pattern: 'envIsolationText', label: '环境隔离提示' },
])
addCheck(
  'merchant-apply-agreement-flow',
  merchantApplyCheck.missing.length ? 'WARN' : 'PASS',
  merchantApplyCheck.missing.length
    ? `商家申请页协议/环境保护仍有缺口：${merchantApplyCheck.missing.join('，')}`
    : '商家申请页已具备免责声明勾选、协议入口、补记和提交前环境确认能力',
  merchantApplyCheck.missing.length ? merchantApplyCheck.missing : ['pages/merchant/apply.vue']
)

const agreementAudit = readJsonSafe(agreementAuditReportJson)
if (agreementAudit && agreementAudit.summary) {
  addCheck(
    'agreement-flow-audit',
    agreementAudit.summary.status === 'FAIL'
      ? 'FAIL'
      : agreementAudit.summary.status === 'WARN'
        ? 'WARN'
        : 'PASS',
    agreementAudit.summary.status === 'FAIL'
      ? '协议专项审计未通过，请先修复高风险协议链路。'
      : agreementAudit.summary.status === 'WARN'
        ? '协议专项审计存在提醒项，请复核协议补记运行时能力。'
        : '协议专项审计已通过，登录、结算、商家申请关键协议链路正常。',
    ['runtime/agreement-flow-audit/latest.md']
  )
}

const criticalFiles = [
  ['pages/app/home.vue', '首页入口', ['envIsolationText', 'envReleaseStageText', 'envRiskList']],
  ['pages/app/my.vue', '我的页工作台', ['myIsolationStatusText', 'myReleaseStageText', 'myEnvRiskList']],
  ['pages/app/release.vue', '发布页', ['envIsolationText', 'envReleaseStageText', 'envRiskList']],
  ['pages/app/sell.vue', '首页商品池', ['envIsolationText', 'envReleaseStageText', 'envRiskList']],
  ['pages/goods/list.vue', '商品列表', ['envIsolationText']],
  ['pages/goods/details.vue', '商品详情', ['envIsolationText', 'envReleaseStageText', 'envRiskList']],
  ['pages/goods/my_cart.vue', '购物车', ['envIsolationText', 'envReleaseStageText', 'envRiskList']],
  ['pages/order/list.vue', '订单列表', ['envIsolationText', 'envReleaseStageText', 'envRiskList']],
]

const coverageMissing = []
criticalFiles.forEach(([relativeFile, label, patterns]) => {
  const fullFile = path.join(projectRoot, relativeFile)
  const content = readFile(fullFile)
  const missing = patterns.filter((pattern) => !content.includes(pattern))
  if (missing.length) {
    coverageMissing.push(`${label}缺少${missing.join(' / ')}`)
  }
})

addCheck(
  'critical-flow-env-coverage',
  coverageMissing.length ? 'WARN' : 'PASS',
  coverageMissing.length
    ? `关键浏览/交易页面环境提示不完整：${coverageMissing.join('，')}`
    : '首页入口、我的页工作台、发布页、首页商品池、商品列表、商品详情、购物车和订单列表均已接入环境提示与发布阶段提示',
  coverageMissing.length ? coverageMissing : criticalFiles.map(([relativeFile]) => relativeFile)
)

const operationalFiles = [
  ['pages/home/search.vue', '搜索页', ['envIsolationText', 'envReleaseStageText', 'envRiskList']],
  ['pages/merchant/renew.vue', '商家续费页', ['envIsolationText', 'envReleaseStageText', 'envRiskList']],
  ['pages/merchant/analytics.vue', '商家分析页', ['envReleaseStageText', 'envRiskList']],
  ['pages/admin/order-audit.vue', '订单审核页', ['envIsolationText', 'envReleaseStageText', 'envRiskList']],
  ['pages/admin/merchant-audit.vue', '商家审核页', ['envIsolationText', 'envReleaseStageText', 'envRiskList']],
  ['pages/help/service.vue', '客服中心页', ['envIsolationText', 'envReleaseStageText', 'envRiskList']],
  ['pages/news/list.vue', '资讯列表页', ['envIsolationText', 'envReleaseStageText', 'envRiskList']],
  ['pages/news/details.vue', '资讯详情页', ['envIsolationText', 'envReleaseStageText', 'envRiskList']],
  ['pages/notice/detail.vue', '公告详情页', ['envIsolationText', 'envReleaseStageText', 'envRiskList']],
  ['pages/system/accord.vue', '协议详情页', ['envIsolationText', 'envReleaseStageText', 'envRiskList']],
  ['pages/goods/trace.vue', '商品溯源页', ['envIsolationText', 'envReleaseStageText', 'envRiskList']],
  ['pages/order/logistics.vue', '物流详情页', ['envIsolationText', 'envReleaseStageText', 'envRiskList']],
  ['pages/order/evaluate.vue', '订单评价页', ['envIsolationText', 'envReleaseStageText', 'envRiskList']],
  ['pages/order/service.vue', '售后详情页', ['envIsolationText', 'envReleaseStageText', 'envRiskList']],
]

const operationalMissing = []
operationalFiles.forEach(([relativeFile, label, patterns]) => {
  const fullFile = path.join(projectRoot, relativeFile)
  const content = readFile(fullFile)
  const missing = patterns.filter((pattern) => !content.includes(pattern))
  if (missing.length) {
    operationalMissing.push(`${label}缺少${missing.join(' / ')}`)
  }
})

addCheck(
  'operational-flow-env-coverage',
  operationalMissing.length ? 'WARN' : 'PASS',
  operationalMissing.length
    ? `搜索与商家运营页面环境提示不完整：${operationalMissing.join('，')}`
    : '搜索页、商家运营页、审核页、客服资讯页、公告协议详情页、商品溯源页和订单尾链路页均已接入环境提示与发布阶段提示',
  operationalMissing.length ? operationalMissing : operationalFiles.map(([relativeFile]) => relativeFile)
)

const passCount = checks.filter((item) => item.status === 'PASS').length
const warnCount = checks.filter((item) => item.status === 'WARN').length
const failCount = checks.filter((item) => item.status === 'FAIL').length
const highestRank = Math.max(...checks.map((item) => statusRank(item.status)), 0)
const finalStatus = highestRank === 2 ? 'FAIL' : highestRank === 1 ? 'WARN' : 'PASS'

ensureDir(reportDir)

const report = {
  generated_at: new Date().toISOString(),
  project_root: projectRoot,
  summary: {
    status: finalStatus,
    pass: passCount,
    warn: warnCount,
    fail: failCount,
  },
  related_reports: {
    agreement_flow_audit: fs.existsSync(agreementAuditReportJson) ? agreementAuditReportJson : '',
  },
  checks,
}

fs.writeFileSync(reportJson, `${JSON.stringify(report, null, 2)}\n`, 'utf8')

const mdLines = [
  '# Uni-app Runtime Readiness Report',
  '',
  `- Generated At: ${report.generated_at}`,
  `- Project Root: ${report.project_root}`,
  `- Summary: ${finalStatus} / PASS ${passCount} / WARN ${warnCount} / FAIL ${failCount}`,
  '',
]

checks.forEach((item) => {
  mdLines.push(`## [${item.status}] ${item.name}`)
  mdLines.push('')
  mdLines.push(item.summary)
  mdLines.push('')
  if (item.details.length) {
    mdLines.push('```text')
    mdLines.push(...item.details)
    mdLines.push('```')
    mdLines.push('')
  }
})

fs.writeFileSync(reportMd, `${mdLines.join('\n')}\n`, 'utf8')

console.log(`[runtime-readiness] ${finalStatus} / PASS ${passCount} / WARN ${warnCount} / FAIL ${failCount}`)
checks.forEach((item) => {
  console.log(`[${item.status}] ${item.name} - ${item.summary}`)
})
console.log(`[runtime-readiness] json=${reportJson}`)
console.log(`[runtime-readiness] md=${reportMd}`)

if (failCount > 0) {
  process.exit(1)
}
