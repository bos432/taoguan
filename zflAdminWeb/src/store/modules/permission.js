import { defineStore } from 'pinia'
import { constantRoutes } from '@/router'
import { store } from '@/store'

const modules = import.meta.glob('../../views/**/**.vue')
const Layout = () => import('@/layout/index.vue')
const LegacyView = () => import('@/views/system/legacy.vue')
const adminNextPathMap = {
  '/system/index': '/dashboard',
  '/order/list': '/order/order',
  '/platform/analytics': '/analytics',
  '/platform/exports': '/exports',
  '/platform/merchant-purchase-ledger': '/report/merchant-purchase-ledger'
}
const adminNextTitleMap = {
  '/dashboard': '控制台总览',
  '/report/internal-takeover': '内部接盘对账',
  '/report/merchant-purchase-ledger': '商家采购对账',
  '/analytics': '超级管理员数据中心',
  '/exports': '导出中心',
  '/order/order': '订单管理',
  '/goods/type': '商品分类',
  '/goods/label': '商品标签',
  '/goods/goods': '商品管理',
  '/content/category': '内容分类',
  '/content/content': '内容管理',
  '/content/setting': '内容设置',
  '/content/tag': '内容标签',
  '/file/file': '文件管理',
  '/file/group': '文件分组',
  '/file/setting': '文件设置',
  '/file/tag': '文件标签',
  '/inspection/inspection': '巡检管理',
  '/inspection/menu': '巡检菜单',
  '/member/member': '会员管理',
  '/member/tag': '会员标签',
  '/member/group': '会员分组',
  '/member/log': '会员日志',
  '/member/third': '第三方账号',
  '/member/api': '会员接口',
  '/member/setting': '会员设置',
  '/member/statistic': '会员统计',
  '/merchant/merchant': '商家管理',
  '/merchant/menu': '商家菜单',
  '/setting/call': '秤管理',
  '/setting/carousel': '轮播图管理',
  '/setting/notice': '通知公告',
  '/setting/accord': '协议管理',
  '/setting/feedback': '意见反馈',
  '/setting/hall': '大厅管理',
  '/setting/link': '友情链接',
  '/setting/region': '地区管理',
  '/setting/delivery': '配送设置',
  '/setting/setting': '基础设置',
  '/setting/warehouse': '仓库管理',
  '/system/notice': '系统通知',
  '/system/user-log': '操作日志',
  '/system/menu': '菜单管理',
  '/system/role': '角色管理',
  '/system/dept': '部门管理',
  '/system/post': '职位管理',
  '/system/user': '用户管理',
  '/system/user-center': '个人中心',
  '/system/internal-merchant': '内部商家配置',
  '/system/apidoc': '接口文档',
  '/system/setting': '系统设置',
  '/trace/batch': '批次溯源'
}

function cleanPath(path = '') {
  return String(path || '')
    .replace(/\/+/g, '/')
    .replace(/\/$/, '') || '/'
}

function resolveRoutePath(parentPath = '/', routePath = '') {
  const current = String(routePath || '').trim()
  if (!current) return cleanPath(parentPath)
  if (current.startsWith('/')) return cleanPath(current)
  if (!parentPath || parentPath === '/') return cleanPath(`/${current}`)
  return cleanPath(`${parentPath}/${current}`)
}

function relativeRoutePath(targetPath = '/', parentPath = '/') {
  if (!parentPath || parentPath === '/') {
    return cleanPath(targetPath)
  }
  const prefix = `${parentPath}/`
  if (targetPath === parentPath) {
    return targetPath.split('/').pop() || ''
  }
  if (targetPath.startsWith(prefix)) {
    return targetPath.slice(prefix.length)
  }
  return targetPath
}

function normalizeMeta(meta = {}, targetPath = '/') {
  const nextMeta = { ...meta }
  if (adminNextTitleMap[targetPath]) {
    nextMeta.title = adminNextTitleMap[targetPath]
  }
  return nextMeta
}

/**
 * 递归生成异步(动态)路由
 * @param routes 接口返回的异步(动态)路由
 * @returns 异步(动态)路由
 */
const filterAsyncRoutes = (routes, parentSourcePath = '/', parentTargetPath = '/') => {
  const asyncRoutes = []
  routes.forEach((route) => {
    const tmpRoute = { ...route } // ES6扩展运算符复制新对象
    const sourceFullPath = resolveRoutePath(parentSourcePath, tmpRoute.path || '')
    const targetFullPath = adminNextPathMap[sourceFullPath] || sourceFullPath
    if (!route.name) {
      tmpRoute.name = targetFullPath
    }
    tmpRoute.meta = normalizeMeta(tmpRoute.meta, targetFullPath)
    tmpRoute.path = relativeRoutePath(targetFullPath, parentTargetPath)
    if (tmpRoute.component?.toString() == 'Layout') {
      tmpRoute.component = Layout
    } else if (targetFullPath === '/report/internal-takeover') {
      tmpRoute.component = modules['../../views/report/internal-takeover.vue']
    } else if (targetFullPath === '/report/merchant-purchase-ledger') {
      tmpRoute.component = modules['../../views/report/merchant-purchase-ledger.vue']
    } else if (targetFullPath === '/analytics') {
      tmpRoute.component = modules['../../views/report/PlatformAnalytics.vue']
    } else if (targetFullPath === '/exports') {
      tmpRoute.component = modules['../../views/report/PlatformExport.vue']
    } else if (targetFullPath.startsWith('/legacy/')) {
      tmpRoute.component = LegacyView
      tmpRoute.meta = {
        ...tmpRoute.meta,
        legacySourcePath: sourceFullPath,
        legacyTargetPath: targetFullPath
      }
    } else {
      const component = modules[`../../views/${tmpRoute.component}.vue`]
      if (component) {
        tmpRoute.component = component
      } else {
        tmpRoute.component = modules[`../../views/error-page/404.vue`]
      }
    }

    if (tmpRoute.children) {
      tmpRoute.children = filterAsyncRoutes(tmpRoute.children, sourceFullPath, targetFullPath)
    }

    asyncRoutes.push(tmpRoute)
  })

  return asyncRoutes
}

// setup
export const usePermissionStore = defineStore('permission', () => {
  // state
  const routes = ref([])

  // actions
  function setRoutes(newRoutes) {
    routes.value = constantRoutes.concat(newRoutes)
  }
  /**
   * 生成动态路由
   */
  function generateRoutes(menus) {
    return new Promise((resolve, reject) => {
      const asyncRoutes = menus
      const accessedRoutes = filterAsyncRoutes(asyncRoutes)
      setRoutes(accessedRoutes)
      resolve(accessedRoutes)
    })
  }
  /**
   * 混合模式左侧菜单
   */
  const mixLeftMenu = ref([])
  function getMixLeftMenu(activeTop) {
    routes.value.forEach((item) => {
      if (item.path === activeTop) {
        mixLeftMenu.value = item.children || []
      }
    })
  }
  return { routes, setRoutes, generateRoutes, getMixLeftMenu, mixLeftMenu }
})

// 非setup
export function usePermissionStoreHook() {
  return usePermissionStore(store)
}
