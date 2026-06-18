import fs from 'node:fs'
import path from 'node:path'
import { fileURLToPath } from 'node:url'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const projectRoot = path.resolve(__dirname, '..')
const rootProject = path.resolve(projectRoot, '..')
const permissionFile = path.join(projectRoot, 'src', 'store', 'modules', 'permission.js')
const viewsRoot = path.join(projectRoot, 'src', 'views')
const reportDir = path.join(rootProject, 'runtime', 'admin-next-coverage')

function ensureDir(dir) {
  fs.mkdirSync(dir, { recursive: true })
}

function extractObjectLiteral(source, constName) {
  const marker = `const ${constName} =`
  const start = source.indexOf(marker)
  if (start === -1) {
    throw new Error(`missing ${constName} in permission.js`)
  }

  const braceStart = source.indexOf('{', start)
  if (braceStart === -1) {
    throw new Error(`missing object start for ${constName}`)
  }

  let depth = 0
  let end = braceStart
  for (; end < source.length; end += 1) {
    const char = source[end]
    if (char === '{') depth += 1
    if (char === '}') {
      depth -= 1
      if (depth === 0) {
        end += 1
        break
      }
    }
  }

  return source.slice(braceStart, end)
}

function loadObject(source, constName) {
  const literal = extractObjectLiteral(source, constName)
  return Function(`"use strict"; return (${literal});`)()
}

function walkFiles(dir) {
  const result = []
  const items = fs.readdirSync(dir, { withFileTypes: true })
  items.forEach((item) => {
    const fullPath = path.join(dir, item.name)
    if (item.isDirectory()) {
      result.push(...walkFiles(fullPath))
      return
    }
    result.push(fullPath)
  })
  return result
}

function normalizeRoute(route = '') {
  return String(route || '').replace(/\\/g, '/').replace(/\/+/g, '/').replace(/\/$/, '') || '/'
}

function routeToViewPath(route = '') {
  return `${route.replace(/^\//, '')}.vue`
}

const permissionSource = fs.readFileSync(permissionFile, 'utf8')
const titleMap = loadObject(permissionSource, 'adminNextTitleMap')
const pathMap = loadObject(permissionSource, 'adminNextPathMap')
const reversePathMap = Object.fromEntries(Object.entries(pathMap).map(([source, target]) => [target, source]))
const allViewFiles = walkFiles(viewsRoot)
const relativeViews = new Set(
  allViewFiles
    .filter((item) => item.endsWith('.vue'))
    .map((item) => path.relative(viewsRoot, item).replace(/\\/g, '/'))
)

function resolveViewForRoute(route) {
  if (route === '/analytics') return 'report/PlatformAnalytics.vue'
  if (route === '/exports') return 'report/PlatformExport.vue'

  const sourceRoute = reversePathMap[route]
  if (sourceRoute) {
    return routeToViewPath(sourceRoute)
  }

  return routeToViewPath(route)
}

const routeEntries = Object.entries(titleMap)
  .map(([route, title]) => {
    const normalizedRoute = normalizeRoute(route)
    const viewPath = resolveViewForRoute(normalizedRoute)
    const exists = relativeViews.has(viewPath)
    const section = normalizedRoute.split('/')[1] || 'root'

    return {
      route: normalizedRoute,
      title,
      section,
      source_route: reversePathMap[normalizedRoute] || normalizedRoute,
      view_path: viewPath,
      exists,
      mode: normalizedRoute.startsWith('/legacy/') ? 'legacy' : 'source',
    }
  })
  .sort((a, b) => a.route.localeCompare(b.route))

const legacyEntries = Object.entries(pathMap)
  .filter(([, target]) => String(target).startsWith('/legacy/'))
  .map(([source, target]) => ({
    source_route: normalizeRoute(source),
    target_route: normalizeRoute(target),
    title: titleMap[target] || '',
    carrier_view: 'system/legacy.vue',
  }))
  .sort((a, b) => a.target_route.localeCompare(b.target_route))

const sections = Array.from(
  routeEntries.reduce((map, entry) => {
    if (!map.has(entry.section)) {
      map.set(entry.section, { section: entry.section, total: 0, exists: 0 })
    }
    const current = map.get(entry.section)
    current.total += 1
    if (entry.exists) current.exists += 1
    return map
  }, new Map()).values()
).sort((a, b) => a.section.localeCompare(b.section))

const missingRoutes = routeEntries.filter((item) => !item.exists)
const keyModules = [
  '/dashboard',
  '/goods/goods',
  '/order/order',
  '/member/member',
  '/merchant/merchant',
  '/analytics',
  '/exports',
  '/system/user',
  '/system/role',
  '/system/menu',
  '/setting/accord',
]

const keyCoverage = keyModules.map((route) => {
  const match = routeEntries.find((item) => item.route === route)
  return {
    route,
    title: match?.title || '',
    exists: Boolean(match?.exists),
    view_path: match?.view_path || '',
  }
})

const report = {
  generated_at: new Date().toISOString(),
  summary: {
    total_routes: routeEntries.length,
    source_ready: routeEntries.filter((item) => item.exists).length,
    source_missing: missingRoutes.length,
    legacy_routes: legacyEntries.length,
  },
  sections,
  key_coverage: keyCoverage,
  routes: routeEntries,
  legacy_routes: legacyEntries,
}

ensureDir(reportDir)

const jsonPath = path.join(reportDir, 'latest.json')
const mdPath = path.join(reportDir, 'latest.md')
fs.writeFileSync(jsonPath, `${JSON.stringify(report, null, 2)}\n`, 'utf8')

const mdLines = [
  '# Admin Next Coverage',
  '',
  `- Generated At: ${report.generated_at}`,
  `- Total Routes: ${report.summary.total_routes}`,
  `- Source Ready: ${report.summary.source_ready}`,
  `- Source Missing: ${report.summary.source_missing}`,
  `- Legacy Routes: ${report.summary.legacy_routes}`,
  '',
  '## Section Coverage',
  '',
  ...sections.map((item) => `- ${item.section}: ${item.exists}/${item.total}`),
  '',
  '## Key Coverage',
  '',
  ...keyCoverage.map((item) => `- [${item.exists ? 'OK' : 'MISS'}] ${item.route} ${item.title ? `(${item.title})` : ''} -> ${item.view_path || 'n/a'}`),
  '',
  '## Legacy Routes',
  '',
]

if (legacyEntries.length) {
  legacyEntries.forEach((item) => {
    mdLines.push(`- ${item.source_route} -> ${item.target_route} via ${item.carrier_view}`)
  })
} else {
  mdLines.push('- none')
}

mdLines.push('', '## Missing Source Routes', '')

if (missingRoutes.length) {
  missingRoutes.forEach((item) => {
    mdLines.push(`- ${item.route} -> ${item.view_path}`)
  })
} else {
  mdLines.push('- none')
}

fs.writeFileSync(mdPath, `${mdLines.join('\n')}\n`, 'utf8')

console.log(`[admin-next-coverage] total=${report.summary.total_routes} ready=${report.summary.source_ready} missing=${report.summary.source_missing} legacy=${report.summary.legacy_routes}`)
console.log(`[admin-next-coverage] json=${jsonPath}`)
console.log(`[admin-next-coverage] md=${mdPath}`)
