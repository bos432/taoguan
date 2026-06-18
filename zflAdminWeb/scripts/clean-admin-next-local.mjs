import { existsSync, rmSync } from 'node:fs'
import { resolve } from 'node:path'

const outputDir = resolve(process.cwd(), '../public/admin-next')

if (existsSync(outputDir)) {
  rmSync(outputDir, { recursive: true, force: true })
  console.log(`[clean-admin-next-local] removed ${outputDir}`)
} else {
  console.log(`[clean-admin-next-local] skip missing ${outputDir}`)
}
