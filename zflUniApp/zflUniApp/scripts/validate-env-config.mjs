import { createRequire } from 'node:module'
import path from 'node:path'
import { fileURLToPath } from 'node:url'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const projectRoot = path.resolve(__dirname, '..')
const require = createRequire(import.meta.url)

const profile = String(process.argv[2] || '').trim().toLowerCase()
const strict = process.argv.includes('--strict')

if (!profile) {
  console.error('[validate-env-config] missing profile, usage: node ./scripts/validate-env-config.mjs <profile> [--strict]')
  process.exit(1)
}

process.env.UNI_APP_ENV_PROFILE = profile

const envConfig = require(path.join(projectRoot, 'config', 'env.js'))
const profiles = envConfig.profiles || {}
const current = profiles[profile]

if (!current) {
  console.error(`[validate-env-config] unknown profile=${profile}`)
  process.exit(1)
}

function hostnameOf(url = '') {
  const value = String(url || '').trim()
  if (!value) return ''
  try {
    return new URL(value).hostname.toLowerCase()
  } catch (error) {
    const match = value.match(/^https?:\/\/([^/]+)/i)
    return match?.[1] ? String(match[1]).toLowerCase() : ''
  }
}

function isLocalHost(hostname = '') {
  return ['127.0.0.1', 'localhost', '::1'].includes(hostname)
}

function isPrivateLanHost(hostname = '') {
  return /^(10\.|192\.168\.|172\.(1[6-9]|2\d|3[01])\.)/.test(hostname)
}

function isExampleHost(hostname = '') {
  return hostname.endsWith('.example.com') || hostname === 'example.com'
}

const prodHosts = [profiles.prod?.base_root_url, profiles.prod?.api_root_url].map(hostnameOf).filter(Boolean)
const localHosts = [profiles.local?.base_root_url, profiles.local?.api_root_url, profiles.dev?.base_root_url, profiles.dev?.api_root_url]
  .map(hostnameOf)
  .filter(Boolean)

const baseHost = hostnameOf(current.base_root_url)
const apiHost = hostnameOf(current.api_root_url)
const errors = []
const warnings = []

if (!baseHost) errors.push('base_root_url 未配置')
if (!apiHost) errors.push('api_root_url 未配置')
if (isExampleHost(baseHost)) errors.push('base_root_url 仍为示例域名')
if (isExampleHost(apiHost)) errors.push('api_root_url 仍为示例域名')

if (profile === 'dev' || profile === 'local') {
  if (!isLocalHost(baseHost)) errors.push(`${profile} base_root_url 应指向本地地址`)
  if (!isLocalHost(apiHost)) errors.push(`${profile} api_root_url 应指向本地地址`)
}

if (profile === 'lan') {
  if (!isPrivateLanHost(baseHost)) errors.push('lan base_root_url 应指向局域网地址')
  if (!isPrivateLanHost(apiHost)) errors.push('lan api_root_url 应指向局域网地址')
}

if (profile === 'test' || profile === 'gray') {
  if (prodHosts.includes(baseHost) || prodHosts.includes(apiHost)) {
    errors.push(`${profile} 仍指向正式域名`)
  }
  if (strict) {
    if (isLocalHost(baseHost) || isLocalHost(apiHost)) {
      errors.push(`${profile} 严格校验失败：仍指向本地地址`)
    }
    if (localHosts.includes(baseHost) || localHosts.includes(apiHost)) {
      errors.push(`${profile} 严格校验失败：仍复用 dev/local 地址`)
    }
  } else if (isLocalHost(baseHost) || isLocalHost(apiHost)) {
    warnings.push(`${profile} 当前仍为本地占位地址，仅适合防误连，不适合出测试/灰度包`)
  }
}

if (profile === 'prod') {
  if (isLocalHost(baseHost) || isLocalHost(apiHost)) {
    errors.push('prod 不得指向本地地址')
  }
  if (!prodHosts.includes(baseHost) || !prodHosts.includes(apiHost)) {
    errors.push('prod 未完全指向正式域名')
  }
}

if (errors.length) {
  console.error(`[validate-env-config] fail profile=${profile} strict=${strict}`)
  errors.forEach((item) => console.error(`- ${item}`))
  process.exit(1)
}

console.log(
  `[validate-env-config] ok profile=${profile} strict=${strict} base=${current.base_root_url} api=${current.api_root_url}`
)
warnings.forEach((item) => console.warn(`[validate-env-config] warning ${item}`))
