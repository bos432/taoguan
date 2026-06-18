import fs from 'node:fs'
import path from 'node:path'
import { fileURLToPath } from 'node:url'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const projectRoot = path.resolve(__dirname, '..')
const exampleFile = path.join(projectRoot, 'config', 'env.profile.example.json')
const localFile = path.join(projectRoot, 'config', 'env.profile.local.json')

if (fs.existsSync(localFile)) {
  console.log(`[create-env-profile-local] exists ${localFile}`)
  process.exit(0)
}

fs.copyFileSync(exampleFile, localFile)
console.log(`[create-env-profile-local] created ${localFile}`)
