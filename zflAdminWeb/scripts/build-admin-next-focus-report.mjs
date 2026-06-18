import fs from 'node:fs'
import path from 'node:path'
import { fileURLToPath } from 'node:url'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const projectRoot = path.resolve(__dirname, '..')
const rootProject = path.resolve(projectRoot, '..')
const coveragePath = path.join(rootProject, 'runtime', 'admin-next-coverage', 'latest.json')
const viewsRoot = path.join(projectRoot, 'src', 'views')
const reportDir = path.join(rootProject, 'runtime', 'admin-next-focus')

const focusSections = ['member', 'setting', 'system']

function ensureDir(dir) {
  fs.mkdirSync(dir, { recursive: true })
}

function readJson(file) {
  return JSON.parse(fs.readFileSync(file, 'utf8'))
}

function fileStats(relativePath) {
  if (!relativePath) {
    return { exists: false, size: 0, lines: 0 }
  }
  const fullPath = path.join(viewsRoot, relativePath)
  if (!fs.existsSync(fullPath)) {
    return { exists: false, size: 0, lines: 0 }
  }
  const content = fs.readFileSync(fullPath, 'utf8')
  return {
    exists: true,
    size: fs.statSync(fullPath).size,
    lines: content.split(/\r?\n/).length,
  }
}

function summarizeScale(lines = 0) {
  if (lines >= 1200) return 'heavy'
  if (lines >= 500) return 'medium'
  return 'light'
}

const coverage = readJson(coveragePath)
const routes = coverage.routes || []
const legacyRoutes = coverage.legacy_routes || []

const focusReport = focusSections.map((section) => {
  const sectionRoutes = routes
    .filter((item) => item.section === section)
    .map((item) => {
      const stats = fileStats(item.view_path)
      return {
        route: item.route,
        title: item.title,
        mode: item.mode,
        view_path: item.view_path,
        size: stats.size,
        lines: stats.lines,
        scale: summarizeScale(stats.lines),
      }
    })
    .sort((a, b) => b.lines - a.lines)

  const sectionLegacy = legacyRoutes
    .filter((item) => item.source_route.startsWith(`/${section}/`))
    .sort((a, b) => a.source_route.localeCompare(b.source_route))

  return {
    section,
    summary: {
      source_routes: sectionRoutes.length,
      legacy_routes: sectionLegacy.length,
      heavy_pages: sectionRoutes.filter((item) => item.scale === 'heavy').length,
      medium_pages: sectionRoutes.filter((item) => item.scale === 'medium').length,
      light_pages: sectionRoutes.filter((item) => item.scale === 'light').length,
    },
    largest_pages: sectionRoutes.slice(0, 5),
    routes: sectionRoutes,
    legacy_routes: sectionLegacy,
  }
})

const report = {
  generated_at: new Date().toISOString(),
  focus_sections: focusReport,
}

ensureDir(reportDir)

const jsonPath = path.join(reportDir, 'latest.json')
const mdPath = path.join(reportDir, 'latest.md')
fs.writeFileSync(jsonPath, `${JSON.stringify(report, null, 2)}\n`, 'utf8')

const mdLines = ['# Admin Next Focus Report', '', `- Generated At: ${report.generated_at}`, '']

focusReport.forEach((sectionReport) => {
  mdLines.push(`## ${sectionReport.section}`)
  mdLines.push('')
  mdLines.push(`- Source Routes: ${sectionReport.summary.source_routes}`)
  mdLines.push(`- Legacy Routes: ${sectionReport.summary.legacy_routes}`)
  mdLines.push(`- Heavy Pages: ${sectionReport.summary.heavy_pages}`)
  mdLines.push(`- Medium Pages: ${sectionReport.summary.medium_pages}`)
  mdLines.push(`- Light Pages: ${sectionReport.summary.light_pages}`)
  mdLines.push('')
  mdLines.push('### Largest Pages')
  mdLines.push('')
  sectionReport.largest_pages.forEach((page) => {
    mdLines.push(`- ${page.route} (${page.title}) -> ${page.view_path} / ${page.lines} lines / ${page.scale}`)
  })
  if (!sectionReport.largest_pages.length) {
    mdLines.push('- none')
  }
  mdLines.push('')
  mdLines.push('### Legacy Routes')
  mdLines.push('')
  sectionReport.legacy_routes.forEach((legacy) => {
    mdLines.push(`- ${legacy.source_route} -> ${legacy.target_route}`)
  })
  if (!sectionReport.legacy_routes.length) {
    mdLines.push('- none')
  }
  mdLines.push('')
})

fs.writeFileSync(mdPath, `${mdLines.join('\n')}\n`, 'utf8')

console.log(`[admin-next-focus] json=${jsonPath}`)
console.log(`[admin-next-focus] md=${mdPath}`)
