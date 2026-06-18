<template>
  <div class="legacy-module">
    <el-card class="legacy-module__tip" shadow="never">
      <div v-if="entryContextVisible" class="legacy-module__entry-banner">
        <div class="legacy-module__entry-banner-main">
          <div class="legacy-module__entry-banner-eyebrow">外部入口承接</div>
          <div class="legacy-module__entry-banner-title">{{ entryContextTitle }}</div>
          <div class="legacy-module__entry-banner-desc">{{ entryContextDesc }}</div>
        </div>
        <div class="legacy-module__entry-banner-actions">
          <el-button v-if="nativeRouteTarget" type="primary" @click="goToNativeRoute">直接去新页</el-button>
          <el-button v-else type="primary" plain @click="openLegacyStandalone">独立打开旧后台</el-button>
          <el-button @click="goToContextHome">{{ entryContextHomeLabel }}</el-button>
        </div>
      </div>
      <div class="legacy-module__overview">
        <div class="legacy-module__overview-card">
          <span>承接状态</span>
          <strong>{{ nativeRouteTarget ? '可分流到新页' : '旧后台承接中' }}</strong>
        </div>
        <div class="legacy-module__overview-card">
          <span>当前来源</span>
          <strong>{{ legacyPathLabel }}</strong>
        </div>
        <div class="legacy-module__overview-card">
          <span>访问方式</span>
          <strong>{{ nativeRouteTarget ? '优先新页 + 旧页兜底' : '内嵌 + 独立打开' }}</strong>
        </div>
      </div>
      <div class="legacy-module__header">
        <div>
          <div class="legacy-module__title">统一工作台承接页</div>
          <div class="legacy-module__desc">
            {{ nativeRouteTarget ? '当前栏目已经存在新后台承接，建议优先走新页；旧后台作为兜底入口保留。' : '当前栏目仍由旧后台渲染，但已经纳入当前工作台入口，便于按线上栏目结构继续使用。' }}
          </div>
          <div class="legacy-module__path">{{ legacyPathLabel }}</div>
        </div>
        <div class="legacy-module__actions">
          <el-button v-if="nativeRouteTarget" type="primary" @click="goToNativeRoute">优先去新页</el-button>
          <el-button plain @click="refreshFrame">刷新</el-button>
          <el-button type="primary" plain @click="openLegacyStandalone">在旧后台独立打开</el-button>
        </div>
      </div>
      <div class="legacy-module__plain-guide">
        <div class="legacy-module__plain-guide-header">
          <div>
            <div class="legacy-module__plain-guide-title">如果你会走到这里，通常是这三种情况</div>
            <div class="legacy-module__plain-guide-desc">
              不是所有旧页都代表有问题。更常见的是新页还在承接中、你点进了历史入口，或者这个栏目本来就需要旧后台做兜底细节操作。
            </div>
          </div>
          <span class="legacy-module__plain-guide-badge">{{ legacyFocusLabel }}</span>
        </div>
        <div class="legacy-module__plain-guide-grid">
          <div v-for="item in legacyGuideCards" :key="item.title" class="legacy-module__plain-guide-card">
            <div class="legacy-module__plain-guide-card-title">{{ item.title }}</div>
            <div class="legacy-module__plain-guide-card-text">{{ item.desc }}</div>
            <div class="legacy-module__plain-guide-card-action">{{ item.action }}</div>
          </div>
        </div>
      </div>
      <div v-if="nativeRouteTarget || fallbackEntries.length || frameStatusText" class="legacy-module__guide">
        <div v-if="nativeRouteTarget" class="legacy-module__guide-card legacy-module__guide-card--primary">
          <div class="legacy-module__guide-title">推荐操作</div>
          <div class="legacy-module__guide-text">
            这个栏目已经有新后台页可接住，优先去 <strong>{{ nativeRouteLabel }}</strong>，避免在旧后台里重复找入口。
          </div>
        </div>
        <div v-if="businessFocusGuide" class="legacy-module__guide-card">
          <div class="legacy-module__guide-title">{{ businessFocusGuide.title }}</div>
          <div class="legacy-module__guide-text">{{ businessFocusGuide.text }}</div>
        </div>
        <div v-if="frameStatusText" class="legacy-module__guide-card" :class="{ 'legacy-module__guide-card--warning': frameLoadFailed }">
          <div class="legacy-module__guide-title">当前提示</div>
          <div class="legacy-module__guide-text">{{ frameStatusText }}</div>
        </div>
        <div v-if="fallbackEntries.length" class="legacy-module__guide-card">
          <div class="legacy-module__guide-title">如果还是不顺</div>
          <div class="legacy-module__guide-links">
            <el-button
              v-for="item in fallbackEntries"
              :key="item.path"
              text
              type="primary"
              @click="goToFallback(item.path)"
            >
              {{ item.label }}
            </el-button>
          </div>
        </div>
      </div>
      <div v-if="smartActionCards.length" class="legacy-module__smart-grid">
        <button
          v-for="item in smartActionCards"
          :key="item.title"
          type="button"
          class="legacy-module__smart-card"
          @click="handleSmartAction(item)"
        >
          <span class="legacy-module__smart-card-title">{{ item.title }}</span>
          <span class="legacy-module__smart-card-desc">{{ item.desc }}</span>
          <span class="legacy-module__smart-card-action">{{ item.action }}</span>
        </button>
      </div>
    </el-card>

    <div
      class="legacy-module__frame-wrap"
      :class="{
        'legacy-module__frame-wrap--loading': frameLoading,
        'legacy-module__frame-wrap--failed': frameLoadFailed
      }"
    >
      <iframe
        :key="frameKey"
        ref="iframeRef"
        class="legacy-module__frame"
        :src="frameSrc"
        title="旧后台模块"
        @load="handleFrameLoad"
      ></iframe>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useSettingsStore } from '@/store/modules/settings'
import { useUserStore } from '@/store/modules/user'

const route = useRoute()
const router = useRouter()
const settingsStore = useSettingsStore()
const userStore = useUserStore()

const iframeRef = ref(null)
const frameKey = ref(0)
const frameLoading = ref(true)
const frameLoadFailed = ref(false)
let frameLoadTimer = null

const legacyNativeRouteMap = [
  { match: /^\/member\/member$/, path: '/member/member', label: '会员管理' },
  { match: /^\/member\/tag$/, path: '/member/tag', label: '会员标签' },
  { match: /^\/member\/group$/, path: '/member/group', label: '会员分组' },
  { match: /^\/member\/api$/, path: '/member/api', label: '会员接口' },
  { match: /^\/member\/log$/, path: '/member/log', label: '会员日志' },
  { match: /^\/member\/third$/, path: '/member/third', label: '第三方账号' },
  { match: /^\/member\/statistic$/, path: '/member/statistic', label: '会员统计' },
  { match: /^\/content\/setting$/, path: '/content/setting', label: '内容设置' },
  { match: /^\/file\/setting$/, path: '/file/setting', label: '文件设置' },
  { match: /^\/order\/order$/, path: '/order/order', label: '订单管理' },
  { match: /^\/setting\/feedback$/, path: '/setting/feedback', label: '意见反馈' },
  { match: /^\/setting\/notice$/, path: '/setting/notice', label: '通知公告' },
  { match: /^\/setting\/accord$/, path: '/setting/accord', label: '协议管理' },
  { match: /^\/setting\/link$/, path: '/setting/link', label: '友情链接' },
  { match: /^\/setting\/region$/, path: '/setting/region', label: '地区管理' },
  { match: /^\/setting\/membersetting$/, path: '/member/setting', label: '会员设置中心' },
  { match: /^\/setting\/contentsetting$/, path: '/content/setting', label: '内容设置中心' },
  { match: /^\/setting\/filesetting$/, path: '/file/setting', label: '文件设置中心' },
  { match: /^\/system\/menu$/, path: '/system/menu', label: '菜单管理' },
  { match: /^\/system\/role$/, path: '/system/role', label: '角色管理' },
  { match: /^\/system\/dept$/, path: '/system/dept', label: '部门管理' },
  { match: /^\/system\/post$/, path: '/system/post', label: '职位管理' },
  { match: /^\/system\/user$/, path: '/system/user', label: '用户管理' },
  { match: /^\/system\/user-log$/, path: '/system/user-log', label: '操作日志' },
  { match: /^\/system\/apidoc$/, path: '/system/apidoc', label: '接口文档' },
  { match: /^\/system\/internal-merchant$/, path: '/system/internal-merchant', label: '内部商家配置' },
  { match: /^\/report\/internal-takeover$/, path: '/report/internal-takeover', label: '内部接盘对账' },
  { match: /^\/platform\/analytics$/, path: '/analytics', label: '平台数据中心' },
  { match: /^\/platform\/exports$/, path: '/exports', label: '导出中心' },
  { match: /^\/goods\/(Goods|goods)$/, path: '/goods/goods', label: '商品管理' },
  { match: /^\/merchant\/merchant$/, path: '/merchant/merchant', label: '商家管理' }
]

const legacyFallbackMap = [
  {
    match: /^\/member\//,
    entries: [
      { path: '/member/member', label: '去会员管理' },
      { path: '/member/statistic', label: '去会员统计' },
      { path: '/member/log', label: '去会员日志' }
    ]
  },
  {
    match: /^\/setting\//,
    entries: [
      { path: '/setting/setting', label: '去站点配置' },
      { path: '/setting/feedback', label: '去意见反馈' },
      { path: '/dashboard', label: '回控制台' }
    ]
  },
  {
    match: /^\/goods\//,
    entries: [
      { path: '/goods/goods', label: '去商品管理' },
      { path: '/order/order', label: '去订单管理' },
      { path: '/merchant/merchant', label: '去商家管理' },
      { path: '/analytics', label: '去平台分析' }
    ]
  },
  {
    match: /^\/merchant\//,
    entries: [
      { path: '/merchant/merchant', label: '去商家管理' },
      { path: '/system/internal-merchant', label: '去内部商家配置' },
      { path: '/report/internal-takeover', label: '去内部接盘对账' }
    ]
  },
  {
    match: /^\/platform\//,
    entries: [
      { path: '/analytics', label: '去平台分析' },
      { path: '/exports', label: '去导出中心' },
      { path: '/report/internal-takeover', label: '去内部接盘对账' }
    ]
  },
  {
    match: /^\/system\//,
    entries: [
      { path: '/system/setting', label: '去系统设置' },
      { path: '/system/user-log', label: '去操作日志' },
      { path: '/dashboard', label: '回控制台' }
    ]
  },
  {
    match: /^\/content\//,
    entries: [
      { path: '/content/setting', label: '去内容设置' },
      { path: '/dashboard', label: '回控制台' },
      { path: '/analytics', label: '去平台分析' }
    ]
  },
  {
    match: /^\/file\//,
    entries: [
      { path: '/file/setting', label: '去文件设置' },
      { path: '/setting/feedback', label: '去意见反馈' },
      { path: '/dashboard', label: '回控制台' }
    ]
  },
  {
    match: /^\/order\//,
    entries: [
      { path: '/order/order', label: '去订单管理' },
      { path: '/merchant/merchant', label: '去商家管理' },
      { path: '/analytics', label: '去平台分析' }
    ]
  },
  {
    match: /^\/inspection\//,
    entries: [
      { path: '/report/internal-takeover', label: '去内部接盘对账' },
      { path: '/analytics', label: '去平台分析' },
      { path: '/dashboard', label: '回控制台' }
    ]
  }
]

const legacyInnerPath = computed(() => {
  const sourcePath = String(route.meta?.legacySourcePath || '').trim()
  if (sourcePath) {
    return sourcePath.startsWith('/') ? sourcePath : `/${sourcePath}`
  }
  const paramPath = route.params.legacyPath
  const normalized = Array.isArray(paramPath) ? paramPath.join('/') : String(paramPath || '')
  return normalized ? `/${normalized.replace(/^\/+/, '')}` : '/'
})

const legacyPathLabel = computed(() => legacyInnerPath.value)
const nativeRouteRecord = computed(() => {
  return legacyNativeRouteMap.find((item) => item.match.test(legacyInnerPath.value)) || null
})
const nativeRouteTarget = computed(() => nativeRouteRecord.value?.path || '')
const nativeRouteLabel = computed(() => nativeRouteRecord.value?.label || nativeRouteTarget.value)
const fallbackEntries = computed(() => {
  return legacyFallbackMap.find((item) => item.match.test(legacyInnerPath.value))?.entries || []
})
const businessFocusGuide = computed(() => {
  if (/^\/order\//.test(legacyInnerPath.value)) {
    return {
      title: '订单类栏目建议',
      text: '这类页面通常要和商家、商品、支付状态一起看。优先回订单管理复核，再去商家或平台分析继续定位。'
    }
  }
  if (/^\/goods\//.test(legacyInnerPath.value)) {
    return {
      title: '商品类栏目建议',
      text: '商品问题最好连着订单和商家一起排，不要只停在旧后台详情里。先去商品管理，再决定是否继续看订单或商家。'
    }
  }
  if (/^\/content\//.test(legacyInnerPath.value)) {
    return {
      title: '内容类栏目建议',
      text: '内容栏目已经有新页承接，优先用新页处理配置和内容结构，旧后台只作为历史入口兜底。'
    }
  }
  if (/^\/file\//.test(legacyInnerPath.value)) {
    return {
      title: '文件类栏目建议',
      text: '文件相关问题优先回文件设置或文件管理处理，避免在旧后台里反复切层级。'
    }
  }
  return null
})
const entrySource = computed(() => String(route.query?.from || ''))
const entryFocus = computed(() => String(route.query?.focus || ''))
const entryContextVisible = computed(() => Boolean(entrySource.value || entryFocus.value))
const entryContextTitle = computed(() => {
  if (entrySource.value === 'dashboard') {
    return '当前从控制台或提醒链路进入旧页承接'
  }
  if (entrySource.value === 'platform-analytics') {
    return '当前从平台分析下钻进入旧页承接'
  }
  if (entrySource.value === 'system-setting') {
    return '当前从系统设置中心跳入旧页承接'
  }
  if (entrySource.value === 'system-user-log') {
    return '当前从操作日志继续追到旧页承接'
  }
  if (entrySource.value === 'system-user-center') {
    return '当前从个人中心跳入旧页承接'
  }
  if (entrySource.value === 'member-setting') {
    return '当前从会员设置中心跳入旧页承接'
  }
  if (entrySource.value === 'legacy-module') {
    return '当前正在旧页承接链路里继续切换'
  }
  return '当前为外部入口承接视角'
})
const entryContextDesc = computed(() => {
  if (entrySource.value === 'dashboard') {
    return nativeRouteTarget.value
      ? '这说明控制台链路已经把你带到了旧入口附近。优先直接跳新页，只有细节操作还没迁完时再用旧后台兜底。'
      : '这说明控制台链路落到了旧入口。当前更适合直接独立打开旧后台处理，不要在 iframe 里反复等待。'
  }
  if (entrySource.value === 'platform-analytics') {
    return '这类入口通常是从复盘或异常下钻过来的。建议先保留当前路径线索，优先去对应新页处理，再回分析页看整体分布。'
  }
  if (entrySource.value === 'system-setting') {
    return '这类入口通常是为了继续查日志、菜单、接口或旧后台细节，不建议在这里停太久。'
  }
  if (entrySource.value === 'system-user-log') {
    return '这通常说明你正在顺着日志记录追操作路径。建议优先记住当前旧路径，再回操作日志或对应新页复核整条链路。'
  }
  if (entrySource.value === 'system-user-center') {
    return '这类入口通常和账号资料、权限生效、登录会话有关。处理完旧页细节后，最好回个人中心或系统首页再确认。'
  }
  if (entrySource.value === 'member-setting') {
    return '这类入口通常是为了继续补会员配置相关旧字段或历史细节，完成后建议回会员设置中心继续核对。'
  }
  if (entrySource.value === 'legacy-module') {
    return '当前已经在旧页承接链路中，优先回最接近的新页，不建议在多个旧页 iframe 之间来回切换。'
  }
  return '这页的职责是先接住旧入口，再把你带去更合适的新页或独立旧后台。'
})
const entryContextHomeLabel = computed(() => {
  if (entrySource.value === 'platform-analytics') {
    return '回平台分析'
  }
  if (entrySource.value === 'system-setting') {
    return '回系统设置中心'
  }
  if (entrySource.value === 'system-user-log') {
    return '回操作日志'
  }
  if (entrySource.value === 'system-user-center') {
    return '回个人中心'
  }
  if (entrySource.value === 'member-setting') {
    return '回会员设置'
  }
  return '回控制台'
})
const legacyFocusLabel = computed(() => {
  if (frameLoadFailed.value) {
    return '先别在 iframe 里硬等'
  }
  if (nativeRouteTarget.value) {
    return '先走新页更顺'
  }
  return '这是旧页兜底入口'
})
const legacyGuideCards = computed(() => {
  return [
    {
      title: '第一种：这个栏目已经有新页',
      desc: '如果系统已经识别到新后台承接，优先去新页最省事，旧页更像备用入口。',
      action: nativeRouteTarget.value ? `当前可直接去「${nativeRouteLabel.value}」。` : '当前还没有识别到对应新页。'
    },
    {
      title: '第二种：你是从历史菜单点进来的',
      desc: '很多栏目线上本来就沿用了旧入口，所以这里更像“先接住你，再告诉你下一步去哪”。',
      action: `当前来源：${legacyPathLabel.value}`
    },
    {
      title: '第三种：这个栏目还需要旧页兜底',
      desc: '有些细节操作短期内还没完全迁到新页，这时旧后台 iframe 或独立打开就是过渡方案。',
      action: frameLoadFailed.value
        ? '当前 iframe 加载不顺，建议直接点“在旧后台独立打开”。'
        : '如果新页还不够用，可以继续用旧页处理细节。'
    }
  ]
})
const frameStatusText = computed(() => {
  if (frameLoadFailed.value) {
    return '旧模块加载超时了。可以先走新页，或者点“在旧后台独立打开”继续处理。'
  }
  if (nativeRouteTarget.value) {
    return '当前已经识别到对应新页，旧后台 iframe 主要保留给尚未完全迁移的细节操作。'
  }
  return ''
})
const smartActionCards = computed(() => {
  const cards = []
  if (nativeRouteTarget.value) {
    cards.push({
      title: '直接走新页处理',
      desc: `当前旧路径 ${legacyPathLabel.value} 已识别到新页承接，继续在新页处理会更顺。`,
      action: `立即进入 ${nativeRouteLabel.value}`,
      type: 'native'
    })
  }
  if (fallbackEntries.value.length) {
    const firstFallback = fallbackEntries.value[0]
    cards.push({
      title: '去最接近的新后台栏目',
      desc: '如果这一页只是历史入口，最稳的做法通常是回到对应的新后台栏目，而不是在旧页里层层找菜单。',
      action: `继续去 ${firstFallback.label}`,
      type: 'fallback',
      path: firstFallback.path
    })
  }
  cards.push({
    title: frameLoadFailed.value ? '当前 iframe 已超时' : '旧页只做兜底',
    desc: frameLoadFailed.value
      ? 'iframe 已经超时，不建议继续在当前嵌入页等待。'
      : '如果只是需要补细节操作，可以独立打开旧后台；如果只是正常业务处理，优先用新页。',
    action: frameLoadFailed.value ? '独立打开旧后台继续处理' : '需要旧细节时独立打开',
    type: 'standalone'
  })
  return cards
})

const frameSrc = computed(() => `${getLegacyAdminBase()}#${legacyInnerPath.value}`)

watch(
  () => route.fullPath,
  () => {
    frameLoading.value = true
    frameLoadFailed.value = false
    frameKey.value += 1
    startFrameLoadTimer()
  },
  { immediate: true }
)

function getLegacyAdminBase() {
  const baseUrl = String(import.meta.env.VITE_APP_BASE_URL || window.location.origin).replace(/\/+$/, '')
  return `${baseUrl}/admin/`
}

function bridgeLegacySession() {
  try {
    const tokenName = settingsStore.tokenName || 'AdminToken'
    const tokenValue = userStore.token || ''
    const targetWindow = iframeRef.value?.contentWindow
    if (!targetWindow || !tokenValue) return
    targetWindow.localStorage.setItem(`admin_${tokenName}`, tokenValue)
    targetWindow.localStorage.setItem('admin_AdminToken', tokenValue)
  } catch (error) {
    // Cross-origin iframe in dev mode may block localStorage bridge. The standalone button remains available.
  }
}

function handleFrameLoad() {
  clearFrameLoadTimer()
  frameLoading.value = false
  frameLoadFailed.value = false
  bridgeLegacySession()
}

function refreshFrame() {
  frameLoading.value = true
  frameLoadFailed.value = false
  frameKey.value += 1
  startFrameLoadTimer()
}

function openLegacyStandalone() {
  window.open(frameSrc.value, '_blank')
}

function goToNativeRoute() {
  if (!nativeRouteTarget.value) return
  router.push({
    path: nativeRouteTarget.value,
    query: {
      from: 'legacy-module',
      legacy_path: legacyInnerPath.value
    }
  })
}

function goToFallback(path) {
  router.push({
    path,
    query: {
      from: 'legacy-module',
      legacy_path: legacyInnerPath.value
    }
  })
}

function handleSmartAction(item) {
  if (item.type === 'native') {
    goToNativeRoute()
    return
  }
  if (item.type === 'fallback' && item.path) {
    goToFallback(item.path)
    return
  }
  openLegacyStandalone()
}

function goToContextHome() {
  if (entrySource.value === 'platform-analytics') {
    router.push({
      path: '/analytics',
      query: {
        from: 'legacy-module',
        legacy_path: legacyInnerPath.value
      }
    })
    return
  }
  if (entrySource.value === 'system-setting') {
    router.push({
      path: '/system/setting',
      query: {
        from: 'legacy-module',
        legacy_path: legacyInnerPath.value
      }
    })
    return
  }
  if (entrySource.value === 'system-user-log') {
    router.push({
      path: '/system/user-log',
      query: {
        from: 'legacy-module',
        legacy_path: legacyInnerPath.value
      }
    })
    return
  }
  if (entrySource.value === 'system-user-center') {
    router.push({
      path: '/system/user-center',
      query: {
        from: 'legacy-module',
        legacy_path: legacyInnerPath.value
      }
    })
    return
  }
  if (entrySource.value === 'member-setting') {
    router.push({
      path: '/member/setting',
      query: {
        from: 'legacy-module',
        legacy_path: legacyInnerPath.value
      }
    })
    return
  }
  router.push({
    path: '/dashboard',
    query: {
      from: 'legacy-module',
      legacy_path: legacyInnerPath.value
    }
  })
}

function startFrameLoadTimer() {
  clearFrameLoadTimer()
  frameLoadTimer = window.setTimeout(() => {
    frameLoading.value = false
    frameLoadFailed.value = true
  }, 8000)
}

function clearFrameLoadTimer() {
  if (frameLoadTimer) {
    window.clearTimeout(frameLoadTimer)
    frameLoadTimer = null
  }
}
</script>

<style lang="scss" scoped>
.legacy-module {
  display: flex;
  flex-direction: column;
  gap: 16px;
  min-height: calc(100vh - 150px);
}

.legacy-module__tip {
  border: none;
  border-radius: 18px;
  box-shadow: 0 18px 40px rgba(15, 23, 42, 0.06);
}

.legacy-module__entry-banner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
  margin-bottom: 16px;
  padding: 14px 16px;
  border: 1px solid rgba(191, 219, 254, 0.9);
  border-radius: 16px;
  background: linear-gradient(135deg, rgba(239, 246, 255, 0.92), rgba(255, 255, 255, 0.98));
}

.legacy-module__entry-banner-main {
  display: grid;
  gap: 6px;
}

.legacy-module__entry-banner-eyebrow {
  font-size: 12px;
  font-weight: 700;
  color: #2563eb;
}

.legacy-module__entry-banner-title {
  font-size: 15px;
  font-weight: 700;
  color: #0f172a;
}

.legacy-module__entry-banner-desc {
  font-size: 13px;
  line-height: 1.7;
  color: #64748b;
}

.legacy-module__entry-banner-actions {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.legacy-module__overview {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-bottom: 16px;
}

.legacy-module__overview-card {
  padding: 14px 16px;
  border: 1px solid rgba(148, 163, 184, 0.14);
  border-radius: 16px;
  background: linear-gradient(180deg, rgba(248, 250, 252, 0.96), rgba(241, 245, 249, 0.92));

  span {
    display: block;
    margin-bottom: 8px;
    font-size: 12px;
    color: #64748b;
  }

  strong {
    display: block;
    font-size: 16px;
    color: #0f172a;
    word-break: break-all;
  }
}

.legacy-module__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
}

.legacy-module__title {
  font-size: 20px;
  font-weight: 700;
  color: #0f172a;
}

.legacy-module__desc {
  margin-top: 6px;
  font-size: 13px;
  color: #64748b;
}

.legacy-module__path {
  margin-top: 10px;
  font-family: Consolas, Monaco, monospace;
  font-size: 13px;
  color: #1d4ed8;
}

.legacy-module__actions {
  display: flex;
  gap: 10px;
}

.legacy-module__guide {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 12px;
  margin-top: 16px;
}

.legacy-module__smart-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 12px;
  margin-top: 16px;
}

.legacy-module__smart-card {
  display: grid;
  gap: 8px;
  padding: 14px 16px;
  text-align: left;
  border: 1px solid rgba(219, 234, 254, 0.95);
  border-radius: 16px;
  background: #fff;
  transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}

.legacy-module__smart-card:hover {
  transform: translateY(-1px);
  border-color: rgba(96, 165, 250, 0.9);
  box-shadow: 0 12px 24px rgba(37, 99, 235, 0.08);
}

.legacy-module__smart-card-title {
  font-size: 13px;
  font-weight: 700;
  color: #0f172a;
}

.legacy-module__smart-card-desc {
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.legacy-module__smart-card-action {
  font-size: 12px;
  color: #2563eb;
}

.legacy-module__plain-guide {
  margin-top: 16px;
  padding: 14px 16px;
  border: 1px solid rgba(148, 163, 184, 0.14);
  border-radius: 16px;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.95));
}

.legacy-module__plain-guide-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.legacy-module__plain-guide-title {
  font-size: 13px;
  font-weight: 700;
  color: #0f172a;
}

.legacy-module__plain-guide-desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.legacy-module__plain-guide-badge {
  display: inline-flex;
  align-items: center;
  min-height: 24px;
  padding: 0 8px;
  border-radius: 999px;
  background: #eef2ff;
  color: #4338ca;
  font-size: 12px;
  white-space: nowrap;
}

.legacy-module__plain-guide-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 12px;
  margin-top: 12px;
}

.legacy-module__plain-guide-card {
  padding: 14px 16px;
  border: 1px solid rgba(219, 234, 254, 0.9);
  border-radius: 16px;
  background: #fff;
}

.legacy-module__plain-guide-card-title {
  font-size: 12px;
  font-weight: 700;
  color: #475569;
}

.legacy-module__plain-guide-card-text {
  margin-top: 8px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.legacy-module__plain-guide-card-action {
  margin-top: 8px;
  font-size: 12px;
  color: #2563eb;
}

.legacy-module__guide-card {
  padding: 14px 16px;
  border: 1px solid rgba(148, 163, 184, 0.14);
  border-radius: 16px;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.95));
}

.legacy-module__guide-card--primary {
  border-color: rgba(37, 99, 235, 0.18);
  background: linear-gradient(180deg, rgba(239, 246, 255, 0.96), rgba(255, 255, 255, 0.98));
}

.legacy-module__guide-card--warning {
  border-color: rgba(245, 158, 11, 0.26);
  background: linear-gradient(180deg, rgba(255, 247, 237, 0.96), rgba(255, 255, 255, 0.98));
}

.legacy-module__guide-title {
  font-size: 12px;
  font-weight: 700;
  color: #475569;
}

.legacy-module__guide-text {
  margin-top: 8px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.legacy-module__guide-links {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 4px;
  margin-top: 6px;
}

.legacy-module__frame-wrap {
  position: relative;
  flex: 1;
  min-height: 720px;
  overflow: hidden;
  background: #fff;
  border: 1px solid rgba(148, 163, 184, 0.18);
  border-radius: 20px;
  box-shadow: 0 18px 40px rgba(15, 23, 42, 0.06);
}

.legacy-module__frame-wrap--loading::before {
  position: absolute;
  inset: 0;
  z-index: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  color: #64748b;
  content: '旧模块加载中...';
  background: rgba(248, 250, 252, 0.8);
}

.legacy-module__frame-wrap--failed::before {
  content: '旧模块加载超时，请优先走新页或独立打开旧后台。';
  color: #b45309;
  background: rgba(255, 247, 237, 0.9);
}

.legacy-module__frame {
  width: 100%;
  min-height: 720px;
  border: none;
}

@media (max-width: 900px) {
  .legacy-module__overview {
    grid-template-columns: 1fr;
  }

  .legacy-module__header,
  .legacy-module__plain-guide-header {
    flex-direction: column;
  }

  .legacy-module__actions {
    flex-wrap: wrap;
  }
}
</style>
