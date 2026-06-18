<template>
  <div :class="layoutClass" class="admin-next-shell">
    <div
      v-if="isMobile && appStore.sidebar.opened"
      class="admin-next-shell__mask"
      @click="appStore.closeSideBar(false)"
    ></div>

    <aside class="admin-next-shell__aside">
      <router-link class="brand" to="/dashboard">
        <div class="brand__badge">TG</div>
        <div v-if="appStore.sidebar.opened" class="brand__text">
          <div class="brand__title">涛冠优选后台</div>
          <div class="brand__desc">运营管理</div>
        </div>
      </router-link>

      <el-scrollbar class="aside__scroll">
        <LeftMenu :menu-list="groupedMenus" base-path="" menu-mode="vertical" />
      </el-scrollbar>

      <button class="aside__toggle" type="button" @click="appStore.toggleSidebar()">
        {{ appStore.sidebar.opened ? '收起菜单' : '展开菜单' }}
      </button>
    </aside>

    <div class="admin-next-shell__workspace">
      <header class="topbar">
        <div class="topbar__left">
          <button class="topbar__menu-btn" type="button" @click="appStore.toggleSidebar()">
            <span></span>
            <span></span>
            <span></span>
          </button>

          <div class="topbar__titles">
            <div class="topbar__headline">
              <div class="topbar__title">{{ currentPageTitle }}</div>
              <span class="topbar__env-badge" :class="`topbar__env-badge--${runtimeEnv.tone}`">
                {{ runtimeEnv.label }}
              </span>
            </div>
            <div class="topbar__meta">
              <div class="topbar__desc">
                {{ currentTopbarDesc }}
              </div>
              <el-breadcrumb v-if="breadcrumbItems.length" class="topbar__breadcrumb" separator="/">
                <el-breadcrumb-item
                  v-for="item in breadcrumbItems"
                  :key="item.path || item.title"
                  :to="item.path || undefined"
                >
                  {{ item.title }}
                </el-breadcrumb-item>
              </el-breadcrumb>
            </div>
            <div class="topbar__env">
              <span class="topbar__env-text">数据：{{ runtimeEnv.dataMode }}</span>
              <span v-if="showWorkbenchAssist" class="topbar__env-host">
                接口：{{ runtimeEnv.baseUrl || '未配置' }}
              </span>
            </div>
          </div>
        </div>

        <div class="topbar__actions">
          <button class="action-link action-link--ghost" type="button" @click="toggleWorkbenchAssist">
            {{ showWorkbenchAssist ? '收起辅助' : '辅助信息' }}
          </button>
          <router-link class="action-link" to="/system/user-center">个人中心</router-link>
          <button class="action-link action-link--quiet" type="button" @click="openLegacyAdmin">旧后台</button>
          <div class="user-pill">{{ displayName }}</div>
          <button class="action-primary" type="button" @click="logout">退出登录</button>
        </div>
      </header>

      <main class="workspace__content" :class="currentSectionClass">
        <section v-if="showWorkbenchAssist" class="runtime-strip" :class="`runtime-strip--${runtimeEnv.tone}`">
          <div class="runtime-strip__title">{{ runtimeEnv.label }} · {{ runtimeEnv.dataMode }}</div>
          <div class="runtime-strip__desc">{{ runtimeHint }}</div>
        </section>
        <section v-if="showWorkbenchAssist && currentSectionPanel" class="module-strip">
          <div class="module-strip__main">
            <div class="module-strip__eyebrow">{{ currentSectionPanel.eyebrow }}</div>
            <div class="module-strip__title">{{ currentSectionPanel.title }}</div>
            <div class="module-strip__desc">{{ currentSectionPanel.desc }}</div>
          </div>
          <div class="module-strip__aside">
            <div class="module-strip__label">栏目内页</div>
            <div class="module-strip__links">
              <router-link
                v-for="item in currentSectionLinks"
                :key="item.path"
                class="module-strip__link"
                :class="{ 'module-strip__link--active': item.path === route.path }"
                :to="item.path"
              >
                {{ translateRouteTitle(item.meta?.title || '') }}
              </router-link>
            </div>
          </div>
        </section>
        <section v-if="showWorkbenchAssist && currentPageGuide" class="page-guide">
          <div class="page-guide__summary">
            <div class="page-guide__label">当前页重点</div>
            <div class="page-guide__title">{{ currentPageGuide.title }}</div>
            <div class="page-guide__desc">{{ currentPageGuide.desc }}</div>
          </div>
          <div class="page-guide__block">
            <div class="page-guide__block-title">高频操作</div>
            <div class="page-guide__chips">
              <span v-for="item in currentPageGuide.actions" :key="item" class="page-guide__chip">
                {{ item }}
              </span>
            </div>
          </div>
          <div class="page-guide__block">
            <div class="page-guide__block-title">使用提示</div>
            <div class="page-guide__tips">
              <div v-for="item in currentPageGuide.tips" :key="item" class="page-guide__tip">
                {{ item }}
              </div>
            </div>
          </div>
        </section>
        <AppMain />
      </main>
    </div>
  </div>
</template>

<script setup>
import path from 'path-browserify'
import { useWindowSize } from '@vueuse/core'
import { useRoute, useRouter } from 'vue-router'
import { useAppStore } from '@/store/modules/app'
import { usePermissionStore } from '@/store/modules/permission'
import { useTagsViewStore } from '@/store/modules/tagsView'
import { useUserStore } from '@/store/modules/user'
import { translateRouteTitle } from '@/utils/i18n'
import { getAdminRuntimeHint, resolveAdminRuntimeEnv } from '@/utils/runtime-env'
import AppMain from './components/AppMain.vue'
import LeftMenu from './components/Sidebar/LeftMenu.vue'

const WIDTH = 992

const route = useRoute()
const router = useRouter()
const { width } = useWindowSize()
const appStore = useAppStore()
const permissionStore = usePermissionStore()
const userStore = useUserStore()
const tagsViewStore = useTagsViewStore()

const groupConfigs = [
  { key: 'dashboard', title: '控制台总览', path: '/dashboard', matcher: (fullPath) => fullPath === '/dashboard' },
  {
    key: 'goods',
    title: '商品管理',
    path: '/goods',
    matcher: (fullPath) => fullPath.startsWith('/goods/')
  },
  {
    key: 'order',
    title: '订单管理',
    path: '/order',
    matcher: (fullPath) => fullPath.startsWith('/order/')
  },
  {
    key: 'merchant',
    title: '商家管理',
    path: '/merchant',
    matcher: (fullPath) => fullPath.startsWith('/merchant/')
  },
  {
    key: 'report',
    title: '运营报表',
    path: '/report',
    alwaysShow: true,
    matcher: (fullPath) => fullPath.startsWith('/report/')
  },
  {
    key: 'member',
    title: '会员管理',
    path: '/member',
    matcher: (fullPath) => fullPath.startsWith('/member/') || fullPath.startsWith('/legacy/member/')
  },
  {
    key: 'content',
    title: '内容资讯',
    path: '/content',
    matcher: (fullPath) => fullPath.startsWith('/content/')
  },
  {
    key: 'file',
    title: '文件管理',
    path: '/file',
    matcher: (fullPath) => fullPath.startsWith('/file/')
  },
  {
    key: 'setting',
    title: '业务设置',
    path: '/setting',
    matcher: (fullPath) => fullPath.startsWith('/setting/') || fullPath.startsWith('/legacy/setting/')
  },
  {
    key: 'system',
    title: '系统管理',
    path: '/system',
    matcher: (fullPath) => fullPath.startsWith('/system/')
  },
  {
    key: 'platform',
    title: '平台运营',
    path: '/platform',
    alwaysShow: true,
    matcher: (fullPath) => fullPath.startsWith('/analytics') || fullPath.startsWith('/exports')
  },
  {
    key: 'legacy',
    title: '巡检溯源',
    path: '/legacy',
    matcher: (fullPath) => fullPath.startsWith('/trace/') || fullPath.startsWith('/inspection/')
  }
]

const layoutClass = computed(() => ({
  mobile: isMobile.value,
  'sidebar-collapsed': !appStore.sidebar.opened
}))

const isMobile = computed(() => appStore.device === 'mobile')

const displayName = computed(() => {
  return userStore.user.nickname || userStore.user.username || '管理员'
})

const runtimeEnv = resolveAdminRuntimeEnv()
const showWorkbenchAssist = ref(false)

const runtimeHint = computed(() => getAdminRuntimeHint(runtimeEnv))

const groupedMenus = computed(() => {
  const groupOrderMap = new Map(groupConfigs.map((item, index) => [item.key, index]))
  const leaves = collectMenuLeaves(permissionStore.routes)
  const groups = groupConfigs.map((item) => ({
    key: item.key,
    path: item.path,
    meta: { title: item.title, alwaysShow: Boolean(item.alwaysShow) },
    children: []
  }))
  const fallbackGroup = {
    path: '/more',
    meta: { title: '更多功能' },
    children: []
  }
  const seenPaths = new Set()

  leaves.forEach((item) => {
    if (seenPaths.has(item.path)) {
      return
    }
    seenPaths.add(item.path)

    const targetGroup = groups.find((group, index) => {
      return groupConfigs[index].matcher(item.path)
    })

    if (targetGroup) {
      if (targetGroup.path === '/dashboard') {
        targetGroup.meta = item.meta
        targetGroup.path = item.path
      } else {
        targetGroup.children.push(item)
      }
      return
    }

    fallbackGroup.children.push(item)
  })

  return groups
    .filter((group) => group.path === '/dashboard' || group.children.length)
    .sort((left, right) => {
      return (groupOrderMap.get(left.key) ?? Number.MAX_SAFE_INTEGER) - (groupOrderMap.get(right.key) ?? Number.MAX_SAFE_INTEGER)
    })
    .concat(fallbackGroup.children.length ? [fallbackGroup] : [])
})

const breadcrumbItems = computed(() => {
  const trail = findMenuTrail(groupedMenus.value, route.path)
  const items = trail.map((item) => ({
    title: translateRouteTitle(item.meta?.title || item.title || ''),
    path: item.path === route.path ? '' : item.path
  }))
  const currentTitle = translateRouteTitle(route.meta?.title || '')

  if (currentTitle && (!items.length || items[items.length - 1].title !== currentTitle)) {
    items.push({ title: currentTitle, path: '' })
  }

  return items.filter((item) => item.title)
})

const currentPageTitle = computed(() => {
  if (breadcrumbItems.value.length) {
    return breadcrumbItems.value[breadcrumbItems.value.length - 1].title
  }
  return '统一后台工作台'
})

const currentTopbarDesc = computed(() => {
  if (route.path === '/dashboard') {
    return '聚合关键指标与常用入口，便于快速判断系统状态。'
  }
  if (currentSectionKey.value === 'platform') {
    return '集中查看平台成交、退款、商家增长与导出能力。'
  }
  return '保持线上后台节奏，兼顾已迁移模块与原有操作路径。'
})

const currentSectionKey = computed(() => {
  const matched = groupConfigs.find((item) => item.matcher(route.path))
  return matched?.key || ''
})

const currentSectionClass = computed(() => {
  return currentSectionKey.value ? `section-${currentSectionKey.value}` : ''
})

const currentSectionMenu = computed(() => {
  return groupedMenus.value.find((item) => item.key === currentSectionKey.value) || null
})

const currentSectionLinks = computed(() => {
  return Array.isArray(currentSectionMenu.value?.children) ? currentSectionMenu.value.children : []
})

const currentSectionPanel = computed(() => {
  const panelMap = {
    member: {
      eyebrow: '会员运营',
      title: '会员管理工作区',
      desc: '聚焦会员筛选、标签分组、接口能力和日志回查，按线上运营习惯集中处理。'
    },
    report: {
      eyebrow: '经营报表',
      title: '运营报表工作区',
      desc: '集中承接内部接盘、专项对账和阶段性报表页，避免再落到未分类栏目里。'
    },
    setting: {
      eyebrow: '业务配置',
      title: '业务设置工作区',
      desc: '把公告、协议、轮播图、地区与配送等常用配置收敛到统一入口，减少来回切页。'
    },
    platform: {
      eyebrow: '平台运营',
      title: '平台运营工作区',
      desc: '对齐线上后台的数据中心与导出中心入口，统一查看指标、预警与报表导出。'
    },
    system: {
      eyebrow: '权限与组织',
      title: '系统管理工作区',
      desc: '围绕菜单、角色、部门、岗位和用户管理，强化后台日常维护与权限核对场景。'
    }
  }
  return panelMap[currentSectionKey.value] || null
})

const currentPageGuide = computed(() => {
  const guideMap = {
    '/member/member': {
      title: '会员列表',
      desc: '适合先筛选会员范围，再执行标签、分组、禁用、导入导出等批量动作。',
      actions: ['批量标签', '批量分组', '重置密码', '导入导出'],
      tips: ['先用筛选区缩小范围，再执行批量处理。', '导入前先确认表头与当前字段一致。']
    },
    '/member/setting': {
      title: '会员设置中心',
      desc: '适合先判断问题属于会员资料、登录注册、第三方绑定，还是安全鉴权，再只改一个标签模块。',
      actions: ['定位问题链路', '按标签单独保存', '去会员页复核'],
      tips: ['不要一次连改多个标签。', '提交后优先回会员列表、登录页和日志页看是否真实生效。']
    },
    '/member/tag': {
      title: '会员标签',
      desc: '集中维护会员标签体系，适合运营活动前统一整理标签口径。',
      actions: ['新增标签', '批量禁用', '成员挂载'],
      tips: ['标签名称尽量保持统一命名。', '批量禁用前先确认是否仍被活动使用。']
    },
    '/member/group': {
      title: '会员分组',
      desc: '用于分组归档会员与接口关联，便于做精细化权限与运营管理。',
      actions: ['新增分组', '默认分组', '接口绑定'],
      tips: ['默认分组建议保持唯一。', '变更分组前先确认会员归属策略。']
    },
    '/setting/notice': {
      title: '通知公告',
      desc: '适合统一维护公告展示周期、类型和正文内容，按时间段精细投放。',
      actions: ['新增公告', '批量禁用', '类型调整', '时间范围'],
      tips: ['公告上线前建议先核对开始与结束时间。', '正文较长时优先确认移动端显示效果。']
    },
    '/setting/setting': {
      title: '站点配置',
      desc: '适合先补站点名称、Logo、联系方式和二维码，再处理 SEO、备案和说明类字段。',
      actions: ['补品牌信息', '补联系信息', '回前端首页复核'],
      tips: ['先填高优先级展示字段。', '保存后要回 H5 首页和协议页核对前端显示。']
    },
    '/setting/accord': {
      title: '协议管理',
      desc: '适合先判断协议是否仍要展示，再维护启停、文案和移动端协议中心承接。',
      actions: ['新增协议', '调整启停', '回登录与结算页复核'],
      tips: ['协议默认态是固定验收项，别误改成默认勾选。', '改完要回 H5 登录页和协议中心验证跳转。']
    },
    '/setting/carousel': {
      title: '轮播图管理',
      desc: '用于集中维护首页或栏目轮播位，适合按位置和时间窗口统一调度。',
      actions: ['新增轮播', '位置调整', '批量禁用'],
      tips: ['同一位置避免重复投放相同素材。', '图片上线前先核对尺寸比例。']
    },
    '/setting/link': {
      title: '友链管理',
      desc: '适合先确认外部入口是否还要继续展示，再处理时间窗口、禁用和删除。',
      actions: ['新增友链', '调整展示时间', '回 H5 首页复核'],
      tips: ['先看链接是否有效。', '批量禁用或删除前先确认前端还有没有依赖入口。']
    },
    '/setting/delivery': {
      title: '快递公司',
      desc: '适合先区分是在处理可用快递清单，还是在做禁用、删除和编码治理。',
      actions: ['新增快递', '批量禁用', '回订单页验证'],
      tips: ['先确认当前状态筛选。', '改完建议回订单页看物流选择是否受影响。']
    },
    '/setting/warehouse': {
      title: '仓库管理',
      desc: '适合先看仓库层级和大厅归属，再处理状态、上级和树形结构调整。',
      actions: ['调整上级', '批量禁用', '回溯源或配送页复核'],
      tips: ['先确认父子关系和大厅归属。', '批量改上级前先缩小筛选范围。']
    },
    '/setting/region': {
      title: '地区管理',
      desc: '用于维护地区层级和配送基础信息，是业务配置里的底层数据页。',
      actions: ['新增地区', '修改上级', '城市编码'],
      tips: ['层级调整后建议回查下游联动筛选。', '批量操作前先确认父子级关系。']
    },
    '/system/setting': {
      title: '系统设置中心',
      desc: '适合先判断这次变更属于系统基础、安全校验，还是接口和运维配置，再按标签独立保存。',
      actions: ['定位配置类型', '单标签保存', '回日志和用户页复核'],
      tips: ['正式环境优先单项变更。', '改安全和接口配置后要立即复核真实登录与调用表现。']
    },
    '/order/order': {
      title: '订单管理',
      desc: '适合先筛选订单状态、支付审核和异常单，再继续做核验、发货和售后处理。',
      actions: ['缩小订单范围', '看支付与审核状态', '继续处理异常单'],
      tips: ['订单数据量大时先用筛选减少等待。', '从报表或预警跳进来时优先看当前带入的条件。']
    },
    '/report/internal-takeover': {
      title: '内部接盘对账',
      desc: '适合先筛出待审核、待转商品和真正异常，再决定今天先盯哪个内部号处理。',
      actions: ['查看待审核', '查看待转商品', '追踪真正异常'],
      tips: ['先确认当前环境，再决定是否做正式数据核对。', '选择某个内部号后，可以直接看健康面板和下一步建议。']
    },
    '/report/merchant-purchase-ledger': {
      title: '商家采购对账',
      desc: '按付款确认时的来源快照，拆清买方商家采购平台商品和采购其他商家商品的金额。',
      actions: ['看买方拆账', '看来源排行', '导出采购流水'],
      tips: ['财务核对时优先看支付时间范围。', '历史未写入快照的数据需要先执行补账脚本或按订单明细回溯。']
    },
    '/analytics': {
      title: '平台数据中心',
      desc: '适合先看成交、退款、商家增长和异常预警，再决定要不要继续下钻到订单或导出中心。',
      actions: ['刷新趋势', '查看排行', '追踪异常预警'],
      tips: ['大范围筛选时先看概览，不要一上来就全量导出。', '发现异常预警后，可转去导出中心或内部接盘对账继续处理。']
    },
    '/exports': {
      title: '导出中心',
      desc: '适合在确认筛选口径之后导出订单、商家、续费和统计报表，也可以先看导出建议再下手。',
      actions: ['确认筛选条件', '查看导出建议', '执行 CSV 导出'],
      tips: ['有异常预警时建议先去平台数据中心确认重点。', '单商家排查时优先导商家、续费和订单三类明细。']
    },
    '/system/menu': {
      title: '菜单管理',
      desc: '适合集中调整后台菜单、按钮权限和可见性，是权限体系的核心入口。',
      actions: ['新增菜单', '修改上级', '免登免权', '角色解除'],
      tips: ['改菜单前先确认路由地址与组件路径。', '按钮类权限建议和页面功能保持一致命名。']
    },
    '/system/role': {
      title: '角色管理',
      desc: '围绕角色与菜单授权展开，适合做运营、财务、客服等岗位分权。',
      actions: ['新增角色', '菜单授权', '批量禁用'],
      tips: ['角色调整后建议立即回查目标账号权限。', '删除前先确认是否仍有用户绑定。']
    },
    '/system/user': {
      title: '用户管理',
      desc: '集中处理后台账号、部门岗位和角色归属，是日常运维的高频页面。',
      actions: ['新增用户', '分配角色', '部门岗位调整', '重置密码'],
      tips: ['新增账号后建议同步检查岗位与部门归属。', '高权限账号调整后建议立即复核登录权限。']
    },
    '/system/user-log': {
      title: '操作日志',
      desc: '适合从首页预警、系统配置或权限调整一路追到具体操作记录，确认到底是谁、何时、做了什么。',
      actions: ['按时间和账号筛选', '核对来源页面', '继续定位操作者'],
      tips: ['从首页跳进来时先看是否带了来源上下文。', '定位到账号后，再回用户管理或目标业务页继续处理。']
    },
    '/system/internal-merchant': {
      title: '内部商家配置',
      desc: '用于集中查看内部商家承接状态和日购买限制，是特殊商家治理的承接入口。',
      actions: ['调整购买限制', '查看到期商家', '跳转商家列表'],
      tips: ['保存限制前先确认是否会影响当前商家采购链路。', '如需逐商家差异化规则，先在商家列表核对目标账号。']
    },
    '/merchant/merchant': {
      title: '商家列表',
      desc: '适合先筛选商家状态、审核和到期情况，再进入编辑、续费或收款码等具体处理。',
      actions: ['筛选商家状态', '查看到期与审核', '进入编辑页处理'],
      tips: ['批量处理前先缩小范围。', '涉及收款码、续费和内部号时，保存后要回真实链路复核。']
    },
    '/goods/goods': {
      title: '商品列表',
      desc: '适合先看待审、热销和异常商品，再继续做审核、上下架和迁移处理。',
      actions: ['筛选商品状态', '查看热销商品', '继续审核与迁移'],
      tips: ['商品页数据量大时先用筛选减少加载压力。', '从首页热销入口跳进来时优先看当前搜索条件。']
    }
  }

  const normalizedPath = String(route.path || '').toLowerCase()

  if (guideMap[normalizedPath]) {
    const guide = guideMap[normalizedPath]
    const focus = String(route.query.focus || '').toLowerCase()
    const fromDashboard = String(route.query.from || '').toLowerCase() === 'dashboard'

    if (!fromDashboard || !focus) {
      return guide
    }

    const focusAddonMap = {
      alerts: {
        desc: '你是从控制台预警区跳进来的，建议先看异常和风险，再决定是否继续下钻到订单、商家或内部接盘链路。',
        actions: ['先看预警相关数据', '缩小异常范围', '继续下钻问题链路'],
        tips: ['优先处理当天最急的异常。', '处理完再回控制台确认预警是否收敛。']
      },
      takeover: {
        desc: '你是从控制台接盘入口跳进来的，建议优先看内部号健康面板、待审核和待转商品。',
        actions: ['先看健康面板', '核待审核', '核待转商品'],
        tips: ['先分清正常积压和真正异常。', '一键转入前先确认筛选范围。']
      },
      ops: {
        desc: '你是从控制台异常链路跳进来的，建议优先按时间、账号和来源页缩小日志范围，再定位具体操作者。',
        actions: ['先按时间筛选', '再按账号缩小', '定位来源页面'],
        tips: ['先确认是不是刚刚的配置或审核动作触发。', '定位到人后再回用户页或业务页继续处理。']
      }
    }

    const addon = focusAddonMap[focus]
    if (!addon) {
      return guide
    }

    return {
      ...guide,
      desc: addon.desc,
      actions: addon.actions,
      tips: addon.tips
    }
  }

  if (!currentSectionPanel.value) {
    return null
  }

  return {
    title: currentPageTitle.value,
    desc: '当前页已纳入统一后台工作区，可配合顶部栏目页签快速切换相关功能。',
    actions: ['先筛选', '再批量处理', '最后复核结果'],
    tips: ['优先在当前栏目内连续处理相关操作。', '执行批量动作前建议先确认筛选条件与勾选范围。']
  }
})

watchEffect(() => {
  if (width.value < WIDTH) {
    appStore.toggleDevice('mobile')
    appStore.closeSideBar(true)
  } else {
    appStore.toggleDevice('desktop')

    if (width.value >= 1200) {
      appStore.openSideBar(true)
    } else {
      appStore.closeSideBar(true)
    }
  }
})

function normalizePath(basePath, routePath) {
  if (!routePath) {
    return basePath || '/'
  }
  if (routePath.startsWith('/')) {
    return routePath
  }
  return path.resolve(basePath || '/', routePath)
}

function normalizeMenuTitle(fullPath, title) {
  const titleMap = {
    '/member/member': '会员列表',
    '/file/file': '文件列表',
    '/system/post': '岗位管理'
  }
  return titleMap[fullPath] || title
}

function collectMenuLeaves(routes = [], basePath = '/') {
  const result = []

  routes.forEach((item) => {
    if (!item || item.meta?.hidden) {
      return
    }

    const fullPath = normalizePath(basePath, item.path || '')
    const visibleChildren = Array.isArray(item.children)
      ? item.children.filter((child) => !child.meta?.hidden)
      : []

    if (visibleChildren.length) {
      result.push(...collectMenuLeaves(visibleChildren, fullPath))
      return
    }

    if (!item.meta?.title) {
      return
    }

    if (['/login', '/404', '/401', '/setting', '/redirect'].includes(fullPath)) {
      return
    }

    result.push({
      ...item,
      path: fullPath,
      meta: {
        ...item.meta,
        title: normalizeMenuTitle(fullPath, item.meta?.title || '')
      },
      children: []
    })
  })

  return result
}

function findMenuTrail(menuList, targetPath, parents = []) {
  for (const item of menuList) {
    const nextParents =
      item.path === '/dashboard' || item.path === '/more' ? parents : parents.concat(item)

    if (item.path === targetPath) {
      return item.path === '/dashboard' ? [item] : nextParents.concat(item.children?.length ? [] : [])
    }

    if (Array.isArray(item.children)) {
      const child = item.children.find((subItem) => subItem.path === targetPath)
      if (child) {
        return nextParents.concat(child)
      }
    }
  }

  return []
}

function getLegacyAdminUrl() {
  const baseUrl = String(import.meta.env.VITE_APP_BASE_URL || window.location.origin).replace(
    /\/+$/,
    ''
  )
  return `${baseUrl}/admin/`
}

function openLegacyAdmin() {
  window.open(getLegacyAdminUrl(), '_blank')
}

function toggleWorkbenchAssist() {
  showWorkbenchAssist.value = !showWorkbenchAssist.value
}

function logout() {
  ElMessageBox.confirm('确认退出当前后台登录吗？', '提示', {
    type: 'warning'
  })
    .then(() => {
      return userStore.logout().then(() => {
        tagsViewStore.delAllViews()
        router.push(`/login?redirect=${route.fullPath}`)
      })
    })
    .catch(() => {})
}
</script>

<style lang="scss" scoped>
.admin-next-shell {
  position: relative;
  display: flex;
  min-height: 100vh;
  background:
    radial-gradient(circle at top left, rgba(59, 130, 246, 0.08), transparent 24%),
    linear-gradient(180deg, #f4f7fb 0%, #f7f9fc 38%, #f3f5f8 100%);
}

.admin-next-shell__mask {
  position: fixed;
  inset: 0;
  z-index: 1000;
  background: rgba(15, 23, 42, 0.36);
}

.admin-next-shell__aside {
  position: fixed;
  inset: 0 auto 0 0;
  z-index: 1001;
  display: flex;
  flex-direction: column;
  width: 220px;
  background: linear-gradient(180deg, #10203d 0%, #16284a 100%);
  box-shadow: 12px 0 28px rgba(15, 23, 42, 0.14);
  transition:
    width 0.28s ease,
    transform 0.28s ease;
}

.brand {
  display: flex;
  align-items: center;
  gap: 10px;
  height: 60px;
  padding: 0 16px;
  color: #fff;
  text-decoration: none;
  border-bottom: 1px solid rgba(148, 163, 184, 0.14);
}

.brand__badge {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 34px;
  font-size: 13px;
  font-weight: 700;
  letter-spacing: 0.08em;
  color: #10203d;
  background: linear-gradient(135deg, #f8fafc 0%, #dbeafe 100%);
  border-radius: 10px;
  box-shadow: 0 6px 14px rgba(15, 23, 42, 0.16);
}

.brand__text {
  min-width: 0;
}

.brand__title {
  font-size: 14px;
  font-weight: 700;
  line-height: 1.2;
}

.brand__desc {
  margin-top: 2px;
  font-size: 11px;
  color: rgba(226, 232, 240, 0.72);
}

.aside__scroll {
  flex: 1;
  padding: 12px 8px;
}

.aside__toggle {
  height: 40px;
  margin: 8px;
  font-size: 12px;
  color: rgba(226, 232, 240, 0.86);
  cursor: pointer;
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(148, 163, 184, 0.18);
  border-radius: 10px;
}

.admin-next-shell__workspace {
  display: flex;
  flex: 1;
  flex-direction: column;
  min-width: 0;
  margin-left: 220px;
  transition: margin-left 0.28s ease;
}

.topbar {
  position: sticky;
  top: 0;
  z-index: 30;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  min-height: 68px;
  padding: 12px 20px;
  background: rgba(255, 255, 255, 0.92);
  border-bottom: 1px solid rgba(148, 163, 184, 0.18);
  backdrop-filter: blur(14px);
}

.topbar__left {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  min-width: 0;
}

.topbar__menu-btn {
  display: inline-flex;
  flex-direction: column;
  justify-content: center;
  gap: 3px;
  width: 36px;
  height: 36px;
  padding: 0 9px;
  cursor: pointer;
  background: #fff;
  border: 1px solid rgba(148, 163, 184, 0.28);
  border-radius: 10px;
  box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);

  span {
    display: block;
    width: 100%;
    height: 2px;
    background: #1e293b;
    border-radius: 999px;
  }
}

.topbar__titles {
  min-width: 0;
}

.topbar__headline {
  display: flex;
  align-items: center;
  gap: 10px;
  min-height: 28px;
}

.topbar__title {
  font-size: 20px;
  font-weight: 700;
  color: #0f172a;
}

.topbar__meta {
  display: flex;
  align-items: center;
  gap: 12px;
  min-height: 24px;
  margin-top: 2px;
}

.topbar__desc {
  font-size: 12px;
  color: #64748b;
}

.topbar__breadcrumb {
  margin-top: 0;
  min-width: 0;
}

.topbar__env {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-top: 8px;
}

.topbar__env-badge,
.topbar__env-text,
.topbar__env-host {
  display: inline-flex;
  align-items: center;
  min-height: 24px;
  padding: 0 8px;
  font-size: 11px;
  border-radius: 999px;
}

.topbar__env-badge {
  font-weight: 700;
  color: #fff;
}

.topbar__env-badge--primary {
  background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
}

.topbar__env-badge--warning {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.topbar__env-badge--danger {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

.topbar__env-text,
.topbar__env-host {
  color: #475569;
  background: rgba(248, 250, 252, 0.96);
  border: 1px solid rgba(148, 163, 184, 0.18);
}

.topbar__actions {
  display: flex;
  align-items: center;
  gap: 8px;
}

.action-link,
.action-primary {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  height: 34px;
  padding: 0 12px;
  font-size: 12px;
  cursor: pointer;
  text-decoration: none;
  border-radius: 10px;
}

.action-link {
  color: #1e293b;
  background: rgba(255, 255, 255, 0.84);
  border: 1px solid rgba(148, 163, 184, 0.2);
}

.action-link--ghost {
  color: #475569;
  background: transparent;
}

.action-link--quiet {
  color: #64748b;
}

.action-primary {
  color: #fff;
  background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
  border: none;
  box-shadow: 0 8px 18px rgba(37, 99, 235, 0.18);
}

.user-pill {
  display: inline-flex;
  align-items: center;
  height: 34px;
  padding: 0 12px;
  font-size: 12px;
  font-weight: 600;
  color: #0f172a;
  background: rgba(219, 234, 254, 0.56);
  border: 1px solid rgba(96, 165, 250, 0.18);
  border-radius: 10px;
}

.workspace__content {
  flex: 1;
  min-width: 0;
  padding: 14px 16px 20px;
}

.runtime-strip {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 14px 18px;
  margin-bottom: 16px;
  border-radius: 18px;
  border: 1px solid rgba(148, 163, 184, 0.18);
  background: rgba(255, 255, 255, 0.88);
  box-shadow: 0 14px 32px rgba(15, 23, 42, 0.05);
}

.runtime-strip--primary {
  border-color: rgba(59, 130, 246, 0.22);
  background: linear-gradient(135deg, rgba(239, 246, 255, 0.96), rgba(255, 255, 255, 0.92));
}

.runtime-strip--warning {
  border-color: rgba(245, 158, 11, 0.24);
  background: linear-gradient(135deg, rgba(255, 247, 237, 0.96), rgba(255, 255, 255, 0.92));
}

.runtime-strip--danger {
  border-color: rgba(239, 68, 68, 0.22);
  background: linear-gradient(135deg, rgba(254, 242, 242, 0.96), rgba(255, 255, 255, 0.92));
}

.runtime-strip__title {
  font-size: 14px;
  font-weight: 700;
  color: #0f172a;
}

.runtime-strip__desc {
  font-size: 13px;
  color: #64748b;
  text-align: right;
}

.module-strip {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 18px;
  padding: 18px 20px;
  margin-bottom: 16px;
  background:
    linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(239, 246, 255, 0.92)),
    linear-gradient(180deg, rgba(37, 99, 235, 0.06), transparent);
  border: 1px solid rgba(96, 165, 250, 0.14);
  border-radius: 22px;
  box-shadow: 0 18px 40px rgba(15, 23, 42, 0.06);
}

.module-strip__main {
  min-width: 0;
}

.module-strip__eyebrow {
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.08em;
  color: #2563eb;
}

.module-strip__title {
  margin-top: 6px;
  font-size: 22px;
  font-weight: 700;
  color: #0f172a;
}

.module-strip__desc {
  margin-top: 6px;
  font-size: 13px;
  line-height: 1.7;
  color: #64748b;
}

.module-strip__aside {
  min-width: min(420px, 48%);
}

.module-strip__label {
  margin-bottom: 10px;
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
}

.module-strip__links {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.module-strip__link {
  display: inline-flex;
  align-items: center;
  height: 36px;
  padding: 0 14px;
  font-size: 13px;
  color: #334155;
  text-decoration: none;
  background: rgba(255, 255, 255, 0.88);
  border: 1px solid rgba(148, 163, 184, 0.18);
  border-radius: 999px;
  transition:
    transform 0.2s ease,
    box-shadow 0.2s ease,
    border-color 0.2s ease;
}

.module-strip__link:hover,
.module-strip__link--active {
  color: #1d4ed8;
  border-color: rgba(59, 130, 246, 0.26);
  box-shadow: 0 10px 24px rgba(59, 130, 246, 0.12);
  transform: translateY(-1px);
}

.page-guide {
  display: grid;
  grid-template-columns: minmax(0, 1.3fr) minmax(0, 1fr) minmax(0, 1fr);
  gap: 14px;
  margin-bottom: 16px;
}

.page-guide__summary,
.page-guide__block {
  padding: 16px 18px;
  background: rgba(255, 255, 255, 0.86);
  border: 1px solid rgba(148, 163, 184, 0.16);
  border-radius: 18px;
  box-shadow: 0 14px 32px rgba(15, 23, 42, 0.05);
}

.page-guide__label,
.page-guide__block-title {
  font-size: 12px;
  font-weight: 700;
  color: #64748b;
}

.page-guide__title {
  margin-top: 8px;
  font-size: 20px;
  font-weight: 700;
  color: #0f172a;
}

.page-guide__desc {
  margin-top: 6px;
  font-size: 13px;
  line-height: 1.7;
  color: #64748b;
}

.page-guide__chips {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 12px;
}

.page-guide__chip {
  display: inline-flex;
  align-items: center;
  height: 32px;
  padding: 0 12px;
  font-size: 12px;
  font-weight: 600;
  color: #1d4ed8;
  background: rgba(219, 234, 254, 0.72);
  border-radius: 999px;
}

.page-guide__tips {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-top: 12px;
}

.page-guide__tip {
  font-size: 13px;
  line-height: 1.7;
  color: #475569;
}

:deep(.el-menu) {
  background: transparent !important;
  border-right: none !important;
}

:deep(.el-menu-item),
:deep(.el-sub-menu__title) {
  height: 40px;
  line-height: 40px;
  margin-bottom: 4px;
  overflow: hidden;
  color: rgba(226, 232, 240, 0.86) !important;
  border-radius: 10px;
}

:deep(.el-menu-item:hover),
:deep(.el-sub-menu__title:hover) {
  background: rgba(255, 255, 255, 0.08) !important;
}

:deep(.el-menu-item.is-active) {
  color: #fff !important;
  background: linear-gradient(135deg, rgba(37, 99, 235, 0.82), rgba(59, 130, 246, 0.82)) !important;
  box-shadow: 0 8px 18px rgba(30, 64, 175, 0.18);
}

:deep(.el-sub-menu.is-active > .el-sub-menu__title) {
  color: #fff !important;
}

:deep(.el-sub-menu .el-menu) {
  background: rgba(15, 23, 42, 0.24) !important;
  border-radius: 14px;
}

:deep(.app-main) {
  min-height: auto;
  margin-top: 0;
}

.sidebar-collapsed {
  .admin-next-shell__aside {
    width: 68px;
  }

  .admin-next-shell__workspace {
    margin-left: 68px;
  }

  .brand {
    justify-content: center;
    padding: 0;
  }

  .aside__toggle {
    margin: 8px 10px;
    font-size: 11px;
  }
}

.mobile {
  .admin-next-shell__aside {
    transform: translateX(0);
  }

  .admin-next-shell__workspace {
    margin-left: 0;
  }

  .topbar,
  .runtime-strip,
  .module-strip {
    flex-direction: column;
    align-items: stretch;
  }

  .topbar__actions,
  .runtime-strip__desc {
    text-align: left;
  }

  &.sidebar-collapsed {
    .admin-next-shell__aside {
      transform: translateX(-100%);
    }
  }

  .topbar {
    align-items: flex-start;
    flex-direction: column;
  }

  .topbar__meta {
    flex-direction: column;
    align-items: flex-start;
    gap: 4px;
  }

  .topbar__actions {
    flex-wrap: wrap;
  }

  .module-strip {
    flex-direction: column;
  }

  .module-strip__aside {
    min-width: 0;
  }

  .page-guide {
    grid-template-columns: 1fr;
  }
}
</style>
