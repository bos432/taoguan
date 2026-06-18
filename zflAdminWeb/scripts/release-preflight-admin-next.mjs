import fs from 'node:fs'
import path from 'node:path'
import { fileURLToPath } from 'node:url'
import { spawnSync } from 'node:child_process'

const mode = String(process.argv[2] || '').trim()

if (!mode) {
  console.error('[admin-next-release-preflight] missing mode argument')
  process.exit(1)
}

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const projectRoot = path.resolve(__dirname, '..')
const rootProject = path.resolve(projectRoot, '..')
const reportDir = path.join(rootProject, 'runtime', 'admin-next-release-preflight')
const reportJson = path.join(reportDir, `${mode}.latest.json`)
const reportMd = path.join(reportDir, `${mode}.latest.md`)
const coverageJson = path.join(rootProject, 'runtime', 'admin-next-coverage', 'latest.json')

const checks = []

function ensureDir(dir) {
  fs.mkdirSync(dir, { recursive: true })
}

function addCheck(name, status, summary, details = []) {
  checks.push({
    name,
    status,
    summary,
    details: Array.isArray(details) ? details : [details]
  })
}

function readJson(file) {
  return JSON.parse(fs.readFileSync(file, 'utf8'))
}

function runStep(name, command, args, options = {}) {
  const normalizedArgs = Array.isArray(args) ? args : []
  const retryCount = Math.max(0, Number(options.retryCount || 0))
  const spawnOptions = { ...options }
  delete spawnOptions.retryCount

  const outputs = []

  for (let attempt = 0; attempt <= retryCount; attempt += 1) {
    const result =
      process.platform === 'win32' && (command === 'npm' || command === 'npx')
        ? spawnSync('cmd.exe', ['/d', '/s', '/c', command, ...normalizedArgs], {
            cwd: projectRoot,
            encoding: 'utf8',
            ...spawnOptions
          })
        : spawnSync(command, normalizedArgs, {
            cwd: projectRoot,
            encoding: 'utf8',
            ...spawnOptions
          })

    const output = `${result.stdout || ''}${result.stderr || ''}`.trim()
    const attemptLabel = `attempt=${attempt + 1}`

    if (result.error) {
      outputs.push(`${attemptLabel} error=${result.error.message}`)
      if (attempt === retryCount) {
        addCheck(name, 'FAIL', `${name} 执行异常`, outputs)
        return false
      }
      continue
    }

    outputs.push(`${attemptLabel} exit=${result.status ?? 1}`)
    if (output) {
      outputs.push(output)
    }

    if ((result.status ?? 1) === 0) {
      addCheck(name, 'PASS', `${name} 已通过`, outputs)
      return true
    }
  }

  addCheck(name, 'FAIL', `${name} 未通过`, outputs.length ? outputs : ['unknown failure'])
  return false
}

function addCoverageCheck() {
  if (!fs.existsSync(coverageJson)) {
    addCheck('admin-next-coverage-summary', 'FAIL', '未找到 admin-next 覆盖率报告', [coverageJson])
    return false
  }

  const report = readJson(coverageJson)
  const missing = Number(report.summary?.source_missing || 0)
  const total = Number(report.summary?.total_routes || 0)
  const ready = Number(report.summary?.source_ready || 0)
  const legacy = Number(report.summary?.legacy_routes || 0)

  if (missing > 0) {
    addCheck(
      'admin-next-coverage-summary',
      'FAIL',
      `admin-next 源码路由仍有缺口：${missing} 项`,
      [
        `total_routes=${total}`,
        `source_ready=${ready}`,
        `source_missing=${missing}`,
        `legacy_routes=${legacy}`
      ]
    )
    return false
  }

  addCheck(
    'admin-next-coverage-summary',
    'PASS',
    'admin-next 路由源码覆盖已完整',
    [
      `total_routes=${total}`,
      `source_ready=${ready}`,
      `source_missing=${missing}`,
      `legacy_routes=${legacy}`
    ]
  )
  return true
}

const modeConfig = {
  'admin-next-local': {
    auditPath: '/admin-next/',
    needsLocalStack: true,
    buildScript: 'build:admin-next-local',
    auditRetryCount: 1
  },
  'admin-next-dev': {
    auditPath: '/admin-next-dev/',
    needsLocalStack: true,
    buildScript: 'build:admin-next-dev',
    auditRetryCount: 1
  },
  'admin-next-online': {
    auditPath: '',
    needsLocalStack: false
  }
}

if (!modeConfig[mode]) {
  console.error(`[admin-next-release-preflight] unsupported mode: ${mode}`)
  process.exit(1)
}

const currentConfig = modeConfig[mode]

runStep('validate-admin-next-env', 'node', ['./scripts/validate-admin-next-env.mjs', mode])
runStep('admin-next-coverage', 'node', ['./scripts/build-admin-next-coverage.mjs'])
addCoverageCheck()

if (currentConfig.needsLocalStack) {
  runStep('check-local-stack', 'node', ['./scripts/check-local-stack.mjs'])
  if (currentConfig.buildScript) {
    runStep('admin-next-build', 'npm', ['run', currentConfig.buildScript])
  }
  runStep('admin-next-audit', 'node', ['./scripts/run-admin-audit.mjs', currentConfig.auditPath], {
    retryCount: currentConfig.auditRetryCount || 0
  })
}

const passCount = checks.filter((item) => item.status === 'PASS').length
const failCount = checks.filter((item) => item.status === 'FAIL').length
const finalStatus = failCount > 0 ? 'FAIL' : 'PASS'

ensureDir(reportDir)

const report = {
  generated_at: new Date().toISOString(),
  project_root: projectRoot,
  mode,
  summary: {
    status: finalStatus,
    pass: passCount,
    fail: failCount
  },
  checks
}

fs.writeFileSync(reportJson, `${JSON.stringify(report, null, 2)}\n`, 'utf8')

const mdLines = [
  '# Admin Next Release Preflight',
  '',
  `- Generated At: ${report.generated_at}`,
  `- Project Root: ${report.project_root}`,
  `- Mode: ${mode}`,
  `- Summary: ${finalStatus} / PASS ${passCount} / FAIL ${failCount}`,
  ''
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

console.log(`[admin-next-release-preflight] ${finalStatus} mode=${mode} pass=${passCount} fail=${failCount}`)
console.log(`[admin-next-release-preflight] json=${reportJson}`)
console.log(`[admin-next-release-preflight] md=${reportMd}`)
checks.forEach((item) => {
  console.log(`[${item.status}] ${item.name} - ${item.summary}`)
})

if (failCount > 0) {
  process.exit(1)
}
