import { createRouter, createWebHashHistory } from 'vue-router'
export const Layout = () => import('@/layout/index.vue')

/**
 * 静态路由
 */
export const constantRoutes = [
  {
    path: '/redirect',
    component: Layout,
    meta: { hidden: true },
    children: [
      {
        path: '/redirect/:path(.*)',
        component: () => import('@/views/system/components/SystemRedirect.vue')
      }
    ]
  },

  {
    path: '/login',
    name: 'login',
    component: () => import('@/views/system/login.vue'),
    meta: { title: '登录', hidden: true }
  },

  {
    path: '/',
    name: '/',
    component: Layout,
    redirect: '/dashboard',
    children: [
      {
        path: 'internal-takeover',
        redirect: '/report/internal-takeover',
        meta: { hidden: true }
      },
      {
        path: 'platform/analytics',
        redirect: '/analytics',
        meta: { hidden: true }
      },
      {
        path: 'platform/exports',
        redirect: '/exports',
        meta: { hidden: true }
      },
      {
        path: 'tag',
        redirect: '/member/tag',
        meta: { hidden: true }
      },
      {
        path: 'group',
        redirect: '/member/group',
        meta: { hidden: true }
      },
      {
        path: 'api',
        redirect: '/member/api',
        meta: { hidden: true }
      },
      {
        path: 'statistic',
        redirect: '/member/statistic',
        meta: { hidden: true }
      },
      {
        path: 'log',
        redirect: '/member/log',
        meta: { hidden: true }
      },
      {
        path: 'third',
        redirect: '/member/third',
        meta: { hidden: true }
      },
      {
        path: 'membersetting',
        redirect: '/member/setting',
        meta: { hidden: true }
      },
      {
        path: 'contentsetting',
        redirect: '/content/setting',
        meta: { hidden: true }
      },
      {
        path: 'filesetting',
        redirect: '/file/setting',
        meta: { hidden: true }
      },
      {
        path: 'carousel',
        redirect: '/setting/carousel',
        meta: { hidden: true }
      },
      {
        path: 'notice',
        redirect: '/setting/notice',
        meta: { hidden: true }
      },
      {
        path: 'accord',
        redirect: '/setting/accord',
        meta: { hidden: true }
      },
      {
        path: 'feedback',
        redirect: '/setting/feedback',
        meta: { hidden: true }
      },
      {
        path: 'link',
        redirect: '/setting/link',
        meta: { hidden: true }
      },
      {
        path: 'region',
        redirect: '/setting/region',
        meta: { hidden: true }
      },
      {
        path: 'menu',
        redirect: '/system/menu',
        meta: { hidden: true }
      },
      {
        path: 'role',
        redirect: '/system/role',
        meta: { hidden: true }
      },
      {
        path: 'dept',
        redirect: '/system/dept',
        meta: { hidden: true }
      },
      {
        path: 'post',
        redirect: '/system/post',
        meta: { hidden: true }
      },
      {
        path: 'user',
        redirect: '/system/user',
        meta: { hidden: true }
      },
      {
        path: 'user-log',
        redirect: '/system/user-log',
        meta: { hidden: true }
      },
      {
        path: 'user-center',
        redirect: '/system/user-center',
        meta: { hidden: true }
      },
      {
        path: 'internal-merchant',
        redirect: '/system/internal-merchant',
        meta: { hidden: true }
      },
      {
        path: 'apidoc',
        redirect: '/system/apidoc',
        meta: { hidden: true }
      },
      {
        path: 'dashboard',
        name: 'dashboard',
        component: () => import('@/views/system/index.vue'),
        meta: {
          title: '控制台总览',
          icon: 'odometer',
          affix: true,
          keepAlive: true,
          alwaysShow: false
        }
      },
      {
        path: 'setting',
        component: () => import('@/views/system/components/SystemSetting.vue'),
        name: 'Setting',
        meta: { title: 'System setting', hidden: true }
      },
      {
        path: 'legacy/:legacyPath(.*)*',
        name: 'legacy-module',
        component: () => import('@/views/system/legacy.vue'),
        meta: { title: '旧模块承接', hidden: true }
      },
      {
        path: '401',
        name: '401',
        component: () => import('@/views/system/components/System401.vue'),
        meta: { title: '401', hidden: true }
      },
      {
        path: '404',
        name: '404',
        component: () => import('@/views/system/components/System404.vue'),
        meta: { title: '404', hidden: true }
      }
    ]
  }
]

/**
 * 创建路由
 */
const router = createRouter({
  history: createWebHashHistory(),
  routes: constantRoutes,
  scrollBehavior: () => ({ left: 0, top: 0 })
})

/**
 * 重置路由
 */
export function resetRouter() {
  router.replace({ path: '/login' })
}

export default router
