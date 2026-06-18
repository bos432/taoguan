import fs from 'node:fs'
import path from 'node:path'
import { fileURLToPath } from 'node:url'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const projectRoot = path.resolve(__dirname, '..')
const reportDir = path.join(projectRoot, 'runtime', 'agreement-flow-audit')
const reportJson = path.join(reportDir, 'latest.json')
const reportMd = path.join(reportDir, 'latest.md')

const checks = []

function ensureDir(dir) {
  fs.mkdirSync(dir, { recursive: true })
}

function readFile(file) {
  return fs.readFileSync(file, 'utf8')
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

function addCheck(name, status, summary, details = []) {
  checks.push({
    name,
    status,
    summary,
    details: Array.isArray(details) ? details : [details],
  })
}

function statusRank(status) {
  if (status === 'FAIL') return 2
  if (status === 'WARN') return 1
  return 0
}

const loginFile = path.join(projectRoot, 'pages', 'my', 'login.vue')
const loginAudit = inspectFile(loginFile, [
  { pattern: 'showAgree: false', label: '协议默认不勾选' },
  { pattern: 'ensureAgreeAccepted("获取验证码")', label: '获取验证码前拦截' },
  { pattern: 'if (!this.ensureAgreeAccepted())', label: '登录提交前拦截' },
  { pattern: 'onGetPhoneNumber(e)', label: '微信授权入口存在' },
  { pattern: 'if (!this.ensureAgreeAccepted()) {', label: '微信授权前协议拦截' },
  { pattern: 'openAccordCenter()', label: '协议中心入口' },
  { pattern: `openAccord('user_agreement')`, label: '用户协议入口' },
  { pattern: `openAccord('privacy_policy')`, label: '隐私政策入口' },
  { pattern: /(bestEffortAcceptAccords|ensureAcceptAccords)\(/, label: '登录后补记协议' },
  { pattern: 'accord_uniques: ["user_agreement", "privacy_policy"]', label: '登录补记协议标识' },
])
addCheck(
  'login-flow',
  loginAudit.missing.length ? 'FAIL' : 'PASS',
  loginAudit.missing.length
    ? `登录页协议链路存在缺口：${loginAudit.missing.join('，')}`
    : '登录页已覆盖默认未勾选、三类登录拦截、协议入口和登录后补记。',
  loginAudit.missing.length ? loginAudit.missing : ['pages/my/login.vue']
)

const settlementFile = path.join(projectRoot, 'pages', 'goods', 'settlement.vue')
const settlementAudit = inspectFile(settlementFile, [
  { pattern: 'agreeAfterSales: false', label: '售后协议默认未勾选' },
  { pattern: 'if (!this.agreeAfterSales)', label: '提交前售后协议拦截' },
  { pattern: `openAccord('after_sales_policy')`, label: '售后协议入口' },
  { pattern: /(bestEffortAcceptAccords|ensureAcceptAccords)\(\s*\{ scene: "order_confirm"/, label: '结算补记协议' },
  { pattern: 'accord_uniques: ["after_sales_policy"]', label: '结算补记协议标识' },
  { pattern: 'buildEnvConfirmText(this.currentEnvInfo', label: '结算正式环境确认弹窗' },
  { pattern: 'agreementReminderText()', label: '结算协议提醒文案' },
])
addCheck(
  'settlement-flow',
  settlementAudit.missing.length ? 'FAIL' : 'PASS',
  settlementAudit.missing.length
    ? `结算页协议链路存在缺口：${settlementAudit.missing.join('，')}`
    : '结算页已覆盖默认未勾选、提交拦截、协议入口、协议补记和环境确认提示。',
  settlementAudit.missing.length ? settlementAudit.missing : ['pages/goods/settlement.vue']
)

const merchantApplyFile = path.join(projectRoot, 'pages', 'merchant', 'apply.vue')
const merchantApplyAudit = inspectFile(merchantApplyFile, [
  { pattern: 'agreeDisclaimer: false', label: '免责声明默认未勾选' },
  { pattern: 'if (!this.agreeDisclaimer)', label: '提交前免责声明拦截' },
  { pattern: `openAccord('disclaimer')`, label: '免责声明入口' },
  { pattern: 'bestEffortAcceptAccords(', label: '入驻补记协议' },
  { pattern: 'accord_uniques: ["disclaimer"]', label: '入驻补记协议标识' },
  { pattern: 'buildEnvConfirmText(this.currentEnvInfo', label: '入驻正式环境确认弹窗' },
  { pattern: 'agreementReminderText()', label: '入驻协议提醒文案' },
])
addCheck(
  'merchant-apply-flow',
  merchantApplyAudit.missing.length ? 'FAIL' : 'PASS',
  merchantApplyAudit.missing.length
    ? `商家申请页协议链路存在缺口：${merchantApplyAudit.missing.join('，')}`
    : '商家申请页已覆盖默认未勾选、提交拦截、协议入口、协议补记和环境确认提示。',
  merchantApplyAudit.missing.length ? merchantApplyAudit.missing : ['pages/merchant/apply.vue']
)

const accordRequestFile = path.join(projectRoot, 'api', 'request.js')
const accordRequestAudit = inspectFile(accordRequestFile, [
  { pattern: 'const PENDING_ACCORD_KEY = "pending_accord_accept_map"', label: '待补记缓存键' },
  { pattern: 'function flushPendingAccords()', label: '待补记冲刷函数' },
  { pattern: '"/setting.Accord/accept"', label: '协议补记接口' },
  { pattern: 'setPendingAccordMap(', label: '待补记写回' },
])
const accordRuntimeFile = path.join(projectRoot, 'utils', 'accord-accept.js')
const accordRuntimeAudit = inspectFile(accordRuntimeFile, [
  { pattern: 'const ACCORD_RUNTIME_KEY = "accord_accept_runtime_summary"', label: '协议运行态缓存键' },
  { pattern: 'function updateRuntimeSummary(', label: '协议运行态写回' },
  { pattern: 'export function getAccordRuntimeSummary()', label: '协议运行态摘要导出' },
])
const accordRuntimeMissing = [...accordRequestAudit.missing, ...accordRuntimeAudit.missing]
addCheck(
  'accord-retry-runtime',
  accordRuntimeMissing.length ? 'WARN' : 'PASS',
  accordRuntimeMissing.length
    ? `协议补记运行时能力仍有缺口：${accordRuntimeMissing.join('，')}`
    : '协议补记运行时已具备缓存、重试与接口回放能力。',
  accordRuntimeMissing.length ? accordRuntimeMissing : ['api/request.js', 'utils/accord-accept.js']
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
  checks,
}

fs.writeFileSync(reportJson, `${JSON.stringify(report, null, 2)}\n`, 'utf8')

const mdLines = [
  '# Uni-app Agreement Flow Audit',
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

console.log(`[agreement-flow-audit] ${finalStatus} / PASS ${passCount} / WARN ${warnCount} / FAIL ${failCount}`)
checks.forEach((item) => {
  console.log(`[${item.status}] ${item.name} - ${item.summary}`)
})
console.log(`[agreement-flow-audit] json=${reportJson}`)
console.log(`[agreement-flow-audit] md=${reportMd}`)

if (failCount > 0) {
  process.exit(1)
}
