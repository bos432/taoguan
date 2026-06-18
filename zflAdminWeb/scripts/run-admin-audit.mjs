import { spawnSync } from 'node:child_process'

const basePath = process.argv[2] || '/admin-next-dev/'

const result = spawnSync(
  process.platform === 'win32' ? 'npx' : 'npx',
  ['playwright', 'test', 'tests/admin-next-dev-audit.spec.js'],
  {
    stdio: 'inherit',
    shell: process.platform === 'win32',
    env: {
      ...process.env,
      ADMIN_AUDIT_BASE_PATH: basePath
    }
  }
)

if (result.error) {
  throw result.error
}

process.exit(result.status ?? 1)
