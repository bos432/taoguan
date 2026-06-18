import http from 'node:http'
import { spawnSync } from 'node:child_process'
import path from 'node:path'
import { fileURLToPath } from 'node:url'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const projectRoot = path.resolve(__dirname, '..', '..')
const origin = process.env.ADMIN_AUDIT_ORIGIN || 'http://127.0.0.1:807'
const loginPath = '/admin/system.Login/login?lang=zh-cn'
const rootStartScript = path.join(projectRoot, 'local', 'start-local.ps1')
const phpExe =
  'C:\\Users\\Administrator\\AppData\\Local\\Microsoft\\WinGet\\Packages\\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe\\php.exe'
const phpIni = path.join(projectRoot, 'runtime', 'php-local.ini')
const loginProbePasswords = [
  process.env.ADMIN_AUDIT_PASSWORD,
  process.env.ADMIN_LOCAL_PASSWORD,
  'star1229',
  '123456'
].filter(Boolean)

function runPowerShell(command) {
  return spawnSync('powershell', ['-NoProfile', '-Command', command], {
    encoding: 'utf8'
  })
}

function sleep(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms))
}

function requestLoginProbe(password) {
  const payload = JSON.stringify({
    username: 'admin',
    password,
    captcha_id: '',
    captcha_code: '',
    ajcaptcha: {}
  })

  return new Promise((resolve, reject) => {
    const req = http.request(
      `${origin}${loginPath}`,
      {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Content-Length': Buffer.byteLength(payload)
        }
      },
      (res) => {
        let raw = ''
        res.setEncoding('utf8')
        res.on('data', (chunk) => {
          raw += chunk
        })
        res.on('end', () => {
          resolve({
            statusCode: res.statusCode || 0,
            body: raw
          })
        })
      }
    )

    req.on('error', reject)
    req.write(payload)
    req.end()
  })
}

function parseProbeResult(response) {
  let json = null

  try {
    json = JSON.parse(response.body)
  } catch (error) {
    return {
      ok: false,
      healthy: false,
      loginOk: false,
      fatal: `登录接口未返回 JSON，状态码 ${response.statusCode}`
    }
  }

  const message = String(json.msg || '')
  if (message.includes('could not find driver')) {
    return {
      ok: true,
      healthy: false,
      loginOk: false,
      fatal: '当前 PHP 服务未加载项目 php-local.ini，pdo_mysql 仍不可用。'
    }
  }

  if (
    message.includes('SQLSTATE') ||
    message.includes('Connection refused') ||
    message.includes('Access denied')
  ) {
    return {
      ok: true,
      healthy: false,
      loginOk: false,
      fatal: `数据库连接仍不可用：${message}`
    }
  }

  return {
    ok: true,
    healthy: true,
    loginOk: json.code === 200,
    statusCode: response.statusCode,
    json
  }
}

async function inspectLoginHealth() {
  let lastProbe = null

  for (const password of loginProbePasswords) {
    try {
      const response = await requestLoginProbe(password)
      const probe = parseProbeResult(response)
      lastProbe = probe
      if (probe.fatal) {
        return probe
      }
      if (probe.loginOk) {
        return probe
      }
    } catch (error) {
      lastProbe = {
        ok: false,
        healthy: false,
        loginOk: false,
        fatal: error.message
      }
    }
  }

  return (
    lastProbe || {
      ok: false,
      healthy: false,
      loginOk: false,
      fatal: '未配置可用的登录探活密码'
    }
  )
}

function isPortListening(port) {
  const result = runPowerShell(
    `if (Get-NetTCPConnection -LocalPort ${port} -State Listen -ErrorAction SilentlyContinue) { 'up' } else { 'down' }`
  )
  return result.stdout.includes('up')
}

function startPhpDirect() {
  const result = runPowerShell(
    [
      `$php = '${phpExe.replace(/\\/g, '\\\\')}'`,
      `$projectRoot = '${projectRoot.replace(/\\/g, '\\\\')}'`,
      `$args = @('-c','${phpIni.replace(/\\/g, '\\\\')}','-S','127.0.0.1:807','-t','.\\public','.\\public\\router.php')`,
      'Start-Process -FilePath $php -WorkingDirectory $projectRoot -ArgumentList $args -PassThru | Out-Null'
    ].join('; ')
  )

  if (result.status !== 0) {
    throw new Error(result.stderr || 'PHP start failed')
  }
}

function assertBootstrapSucceeded(bootstrap) {
  if (bootstrap.error) {
    throw bootstrap.error
  }

  if (bootstrap.signal) {
    throw new Error(`local/start-local.ps1 执行被中断：${bootstrap.signal}`)
  }

  if (bootstrap.status !== 0) {
    const detail = [bootstrap.stderr, bootstrap.stdout].filter(Boolean).join('\n').trim()
    throw new Error(detail || `local/start-local.ps1 执行失败，退出码 ${bootstrap.status}`)
  }
}

async function waitForHealthyLogin(maxAttempts = 30) {
  for (let index = 0; index < maxAttempts; index += 1) {
    const probe = await inspectLoginHealth()
    if (probe.healthy) {
      return probe
    }
    if (probe.fatal && !probe.fatal.includes('ECONNREFUSED')) {
      return probe
    }
    await sleep(1000)
  }
  return {
    healthy: false,
    loginOk: false,
    fatal: '等待登录接口就绪超时'
  }
}

async function main() {
  const initialProbe = await inspectLoginHealth()
  if (initialProbe.healthy) {
    console.log('[start-local-stack] already healthy')
    return
  }

  const bootstrap = spawnSync(
    'powershell',
    ['-ExecutionPolicy', 'Bypass', '-File', rootStartScript],
    {
      cwd: projectRoot,
      encoding: 'utf8',
      timeout: 45000
    }
  )

  if (bootstrap.stdout) {
    process.stdout.write(bootstrap.stdout)
  }
  if (bootstrap.stderr) {
    process.stderr.write(bootstrap.stderr)
  }
  assertBootstrapSucceeded(bootstrap)

  const afterBootstrapProbe = await inspectLoginHealth()
  if (afterBootstrapProbe.healthy) {
    console.log('[start-local-stack] healthy after bootstrap')
    return
  }

  if (!isPortListening(807)) {
    startPhpDirect()
  }

  const finalProbe = await waitForHealthyLogin()
  if (!finalProbe.healthy) {
    throw new Error(finalProbe.fatal || 'local stack started but login probe is still failing')
  }

  console.log('[start-local-stack] ok')
}

main().catch((error) => {
  console.error(`[start-local-stack] ${error.message}`)
  process.exit(1)
})
