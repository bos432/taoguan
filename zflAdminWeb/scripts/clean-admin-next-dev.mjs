import { existsSync, rmSync } from 'node:fs'
import { resolve } from 'node:path'

const outputDir = resolve(process.cwd(), '../public/admin-next-dev')

if (existsSync(outputDir)) {
  rmSync(outputDir, { recursive: true, force: true })
  console.log(`[clean-admin-next-dev] removed ${outputDir}`)
} else {
  console.log(`[clean-admin-next-dev] skip missing ${outputDir}`)
}
