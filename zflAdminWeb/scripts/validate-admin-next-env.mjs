import { existsSync, readFileSync } from 'node:fs'
import { resolve } from 'node:path'

const mode = String(process.argv[2] || '').trim()

if (!mode) {
  console.error('[validate-admin-next-env] missing mode argument')
  process.exit(1)
}

const envFile = resolve(process.cwd(), `.env.${mode}`)

if (!existsSync(envFile)) {
  console.error(`[validate-admin-next-env] missing env file: ${envFile}`)
  process.exit(1)
}

function parseEnvFile(filePath) {
  const raw = readFileSync(filePath, 'utf8')
  return raw
    .split(/\r?\n/)
    .map((line) => line.trim())
    .filter((line) => line && !line.startsWith('#'))
    .reduce((acc, line) => {
      const equalIndex = line.indexOf('=')
      if (equalIndex <= 0) {
        return acc
      }
      const key = line.slice(0, equalIndex).trim()
      let value = line.slice(equalIndex + 1).trim()
      if (
        (value.startsWith("'") && value.endsWith("'")) ||
        (value.startsWith('"') && value.endsWith('"'))
      ) {
        value = value.slice(1, -1)
      }
      acc[key] = value
      return acc
    }, {})
}

function fail(message) {
  console.error(`[validate-admin-next-env] ${message}`)
  process.exit(1)
}

function ensure(condition, message) {
  if (!condition) {
    fail(message)
  }
}

const env = parseEnvFile(envFile)
const baseUrl = String(env.VITE_APP_BASE_URL || '').trim()
const base = String(env.VITE_APP_BASE || '').trim()
const outDir = String(env.VITE_APP_OUT_DIR || '').trim()

ensure(baseUrl, `${mode} missing VITE_APP_BASE_URL`)
ensure(base, `${mode} missing VITE_APP_BASE`)
ensure(outDir, `${mode} missing VITE_APP_OUT_DIR`)

const checks = {
  'admin-next-local': () => {
    ensure(base === '/admin-next/', 'admin-next-local must use VITE_APP_BASE=/admin-next/')
    ensure(
      /127\.0\.0\.1|localhost/i.test(baseUrl),
      'admin-next-local must point to local host base url'
    )
    ensure(
      /public[\\/]+admin-next$/i.test(outDir),
      'admin-next-local must output to ../public/admin-next'
    )
  },
  'admin-next-dev': () => {
    ensure(base === '/admin-next-dev/', 'admin-next-dev must use VITE_APP_BASE=/admin-next-dev/')
    ensure(
      /127\.0\.0\.1|localhost/i.test(baseUrl),
      'admin-next-dev must point to local host base url or replace with explicit test host before use'
    )
    ensure(
      /public[\\/]+admin-next-dev$/i.test(outDir),
      'admin-next-dev must output to ../public/admin-next-dev'
    )
  },
  'admin-next-online': () => {
    ensure(base === '/admin-next/', 'admin-next-online must use VITE_APP_BASE=/admin-next/')
    ensure(
      /^https?:\/\//i.test(baseUrl) && !/127\.0\.0\.1|localhost/i.test(baseUrl),
      'admin-next-online must use a non-local base url'
    )
    ensure(
      !/public[\\/]+admin-next(-dev)?$/i.test(outDir),
      'admin-next-online must not output to local public/admin-next or public/admin-next-dev'
    )
    ensure(
      /dist-admin-next-online$/i.test(outDir),
      'admin-next-online must output to dist-admin-next-online'
    )
  }
}

ensure(checks[mode], `unsupported admin-next mode: ${mode}`)
checks[mode]()

console.log(
  `[validate-admin-next-env] ok mode=${mode} baseUrl=${baseUrl} base=${base} outDir=${outDir}`
)
