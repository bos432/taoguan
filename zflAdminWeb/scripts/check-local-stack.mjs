import http from 'node:http'
import { spawnSync } from 'node:child_process'

const origin = process.env.ADMIN_AUDIT_ORIGIN || 'http://127.0.0.1:807'
const loginPath = '/admin/system.Login/login?lang=zh-cn'
const loginProbePasswords = [
  process.env.ADMIN_AUDIT_PASSWORD,
  process.env.ADMIN_LOCAL_PASSWORD,
  'star1229',
  '123456'
].filter(Boolean)

function fail(message) {
  console.error(`[check-local-stack] ${message}`)
  process.exit(1)
}

function runPowerShell(command) {
  return spawnSync('powershell', ['-NoProfile', '-Command', command], {
    encoding: 'utf8'
  })
}

function request(method, url, payload) {
  return new Promise((resolve, reject) => {
    const req = http.request(
      url,
      {
        method,
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

function parseProbe(response) {
  let json = null

  try {
    json = JSON.parse(response.body)
  } catch (error) {
    return {
      healthy: false,
      loginOk: false,
      fatal: `登录接口未返回 JSON，当前状态码 ${response.statusCode}。`
    }
  }

  const message = String(json.msg || '')
  if (message.includes('could not find driver')) {
    return {
      healthy: false,
      loginOk: false,
      fatal: '当前 PHP 服务未加载项目 php-local.ini，pdo_mysql 仍不可用。请重新执行 ..\\local\\start-local.ps1。'
    }
  }

  if (
    message.includes('SQLSTATE') ||
    message.includes('Connection refused') ||
    message.includes('Access denied')
  ) {
    return {
      healthy: false,
      loginOk: false,
      fatal: `数据库连接仍不可用：${message}`
    }
  }

  return {
    healthy: true,
    loginOk: json.code === 200,
    json,
    statusCode: response.statusCode
  }
}

async function main() {
  const pingMysql = runPowerShell(
    "if (Get-NetTCPConnection -LocalPort 3310 -State Listen -ErrorAction SilentlyContinue) { 'mysql:up' } else { 'mysql:down' }"
  )

  if (!pingMysql.stdout.includes('mysql:up')) {
    fail('本地 MySQL 3310 未监听。先执行 ..\\local\\start-local.ps1 拉起本地栈。')
  }

  const mysqlReady = runPowerShell(
    "$mysqlAdmin='D:\\phpstudy_pro\\Extensions\\MySQL5.7.26\\bin\\mysqladmin.exe'; if (Test-Path $mysqlAdmin) { try { & $mysqlAdmin -h 127.0.0.1 -P 3310 -uroot ping | Out-Null; 'mysql:ready' } catch { 'mysql:not-ready' } } else { 'mysql:skip' }"
  )

  if (mysqlReady.stdout.includes('mysql:not-ready')) {
    fail('本地 MySQL 3310 已监听，但尚未通过 mysqladmin ping。请稍后重试或重新执行 ..\\local\\start-local.ps1。')
  }

  let lastProbe = null

  for (const password of loginProbePasswords) {
    const payload = JSON.stringify({
      username: 'admin',
      password,
      captcha_id: '',
      captcha_code: '',
      ajcaptcha: {}
    })

    let response
    try {
      response = await request('POST', `${origin}${loginPath}`, payload)
    } catch (error) {
      fail(`无法访问 ${origin}。请先执行 ..\\local\\start-local.ps1。原始错误：${error.message}`)
    }

    const probe = parseProbe(response)
    lastProbe = probe

    if (probe.fatal) {
      fail(probe.fatal)
    }

    if (probe.loginOk) {
      console.log(`[check-local-stack] ok origin=${origin} login=pass`)
      return
    }
  }

  if (lastProbe?.healthy) {
    console.log(`[check-local-stack] ok origin=${origin} login=healthy auth=failed`)
    return
  }

  fail('登录探活未通过，且后端未返回健康响应。')
}

main().catch((error) => {
  fail(error.message)
})
