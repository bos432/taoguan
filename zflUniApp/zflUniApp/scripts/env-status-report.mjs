import fs from 'node:fs'
import path from 'node:path'
import { fileURLToPath } from 'node:url'
import { createRequire } from 'node:module'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const projectRoot = path.resolve(__dirname, '..')
const require = createRequire(import.meta.url)

const localProfileFile = path.join(projectRoot, 'config', 'env.profile.local.json')
const envConfig = require(path.join(projectRoot, 'config', 'env.js'))
const profiles = envConfig.profiles || {}

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

function isExampleHost(hostname = '') {
  return hostname.endsWith('.example.com') || hostname === 'example.com'
}

function describeProfile(key, profile) {
  const baseHost = hostnameOf(profile?.base_root_url)
  const apiHost = hostnameOf(profile?.api_root_url)
  const flags = []

  if (key === 'prod') flags.push('formal')
  if (['dev', 'local', 'lan'].includes(key)) flags.push('dev-only')
  if (key === 'test') flags.push('test')
  if (key === 'gray') flags.push('gray')
  if (isLocalHost(baseHost) || isLocalHost(apiHost)) flags.push('local-host')
  if (isExampleHost(baseHost) || isExampleHost(apiHost)) flags.push('example-host')

  return {
    key,
    label: profile?.label || key,
    base: profile?.base_root_url || '',
    api: profile?.api_root_url || '',
    flags
  }
}

const rows = Object.keys(profiles).map((key) => describeProfile(key, profiles[key]))

console.log('[env-status-report] uni-app environment summary')
console.log(`- profile_overrides_enabled: ${envConfig.profile_overrides_enabled ? 'yes' : 'no'}`)
console.log(`- local_profile_file: ${fs.existsSync(localProfileFile) ? localProfileFile : 'missing'}`)
console.log('')

rows.forEach((row) => {
  console.log(`${row.key} (${row.label})`)
  console.log(`  base: ${row.base}`)
  console.log(`  api : ${row.api}`)
  console.log(`  tags: ${row.flags.length ? row.flags.join(', ') : 'none'}`)
})
