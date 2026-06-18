import fs from 'node:fs'
import path from 'node:path'
import { fileURLToPath } from 'node:url'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const projectRoot = path.resolve(__dirname, '..')
const localFile = path.join(projectRoot, 'config', 'env.profile.local.json')

const requiredProfiles = ['test', 'gray', 'prod']
const requiredFields = ['label', 'base_root_url', 'api_root_url']

function fail(lines) {
  console.error('[validate-env-profile-local] fail')
  lines.forEach((line) => console.error(`- ${line}`))
  process.exit(1)
}

if (!fs.existsSync(localFile)) {
  fail(['缺少 config/env.profile.local.json，可先执行 npm run env:local:init'])
}

let json
try {
  json = JSON.parse(fs.readFileSync(localFile, 'utf8'))
} catch (error) {
  fail([`config/env.profile.local.json 不是合法 JSON: ${error.message}`])
}

const issues = []

requiredProfiles.forEach((profile) => {
  const entry = json?.[profile]
  if (!entry || typeof entry !== 'object') {
    issues.push(`缺少 profile: ${profile}`)
    return
  }

  requiredFields.forEach((field) => {
    const value = String(entry[field] || '').trim()
    if (!value) {
      issues.push(`${profile}.${field} 未填写`)
    }
  })
})

if (issues.length) {
  fail(issues)
}

console.log(`[validate-env-profile-local] ok file=${localFile}`)
