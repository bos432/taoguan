import fs from 'node:fs/promises'
import path from 'node:path'
import { spawn } from 'node:child_process'

const projectRoot = path.resolve(process.cwd())
const workspaceRoot = path.resolve(projectRoot, '..', '..')
const cliPath = 'D:\\HBuilderX\\cli.exe'
const buildOutputDir = path.join(projectRoot, 'unpackage', 'dist', 'build', 'web')
const localPublishDir = path.join(workspaceRoot, 'public', 'app')

async function ensureCli() {
  try {
    await fs.access(cliPath)
  } catch (error) {
    throw new Error(`未找到 HBuilderX CLI：${cliPath}`)
  }
}

async function removeDirContents(targetDir) {
  await fs.mkdir(targetDir, { recursive: true })
  const entries = await fs.readdir(targetDir, { withFileTypes: true })
  await Promise.all(
    entries.map((entry) =>
      fs.rm(path.join(targetDir, entry.name), {
        recursive: true,
        force: true
      })
    )
  )
}

async function copyDir(sourceDir, targetDir) {
  await fs.mkdir(targetDir, { recursive: true })
  const entries = await fs.readdir(sourceDir, { withFileTypes: true })

  await Promise.all(
    entries.map(async (entry) => {
      const sourcePath = path.join(sourceDir, entry.name)
      const targetPath = path.join(targetDir, entry.name)

      if (entry.isDirectory()) {
        await copyDir(sourcePath, targetPath)
        return
      }

      await fs.copyFile(sourcePath, targetPath)
    })
  )
}

async function runPublish() {
  await ensureCli()

  await new Promise((resolve, reject) => {
    const child = spawn(
      cliPath,
      ['publish', 'web', '--project', projectRoot],
      {
        cwd: projectRoot,
        stdio: 'inherit'
      }
    )

    child.on('error', reject)
    child.on('exit', (code) => {
      if (code === 0) {
        resolve()
        return
      }

      reject(new Error(`H5 发布失败，退出码：${code}`))
    })
  })
}

async function main() {
  console.log(`[build:h5:local] 项目目录：${projectRoot}`)
  await runPublish()
  await removeDirContents(localPublishDir)
  await copyDir(buildOutputDir, localPublishDir)
  console.log(`[build:h5:local] H5 产物已同步到：${localPublishDir}`)
}

main().catch((error) => {
  console.error(`[build:h5:local] ${error.message}`)
  process.exit(1)
})
