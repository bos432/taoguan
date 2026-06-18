import fs from 'node:fs'
import path from 'node:path'
import { fileURLToPath } from 'node:url'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const projectRoot = path.resolve(__dirname, '..')
const localFile = path.join(projectRoot, 'config', 'env.profile.local.json')
const exampleFile = path.join(projectRoot, 'config', 'env.profile.example.json')

const profile = String(process.argv[2] || '').trim().toLowerCase()
const baseRootUrl = String(process.argv[3] || '').trim()
const apiRootUrl = String(process.argv[4] || '').trim()
const label = String(process.argv[5] || '').trim()

function fail(message) {
  console.error(`[set-env-profile-local] ${message}`)
  process.exit(1)
}

function isValidUrl(value = '') {
  try {
    const url = new URL(value)
    return ['http:', 'https:'].includes(url.protocol)
  } catch (error) {
    return false
  }
}

function ensureLocalFile() {
  if (fs.existsSync(localFile)) {
    return
  }
  fs.copyFileSync(exampleFile, localFile)
}

if (!['test', 'gray', 'prod'].includes(profile)) {
  fail('usage: node ./scripts/set-env-profile-local.mjs <test|gray|prod> <base_root_url> <api_root_url> [label]')
}

if (!isValidUrl(baseRootUrl)) {
  fail(`invalid base_root_url: ${baseRootUrl || '<empty>'}`)
}

if (!isValidUrl(apiRootUrl)) {
  fail(`invalid api_root_url: ${apiRootUrl || '<empty>'}`)
}

ensureLocalFile()

let json = {}
try {
  json = JSON.parse(fs.readFileSync(localFile, 'utf8'))
} catch (error) {
  fail(`config/env.profile.local.json is not valid JSON: ${error.message}`)
}

const current = json[profile] && typeof json[profile] === 'object' ? json[profile] : {}
json[profile] = {
  ...current,
  label: label || current.label || profile,
  base_root_url: baseRootUrl,
  api_root_url: apiRootUrl
}

fs.writeFileSync(localFile, `${JSON.stringify(json, null, 2)}\n`, 'utf8')

console.log(
  `[set-env-profile-local] updated profile=${profile} file=${localFile} base=${baseRootUrl} api=${apiRootUrl}`
)
