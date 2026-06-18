<template>
  <el-scrollbar native :height="height">
    <div v-loading="loading" class="app-container dashboard-page">
      <el-card class="hero-card mb-[15px]" shadow="never">
        <div class="hero-card__main">
          <div class="hero-card__eyebrow">控制台</div>
          <div class="hero-card__title">控制台总览</div>
          <div class="hero-card__desc">
            这里聚合最近 7 天的平台关键指标、预警摘要和常用入口，方便你快速判断系统状态，再继续深入分析。
          </div>
          <div v-if="entryContextVisible" class="entry-context-banner">
            <div class="entry-context-banner__main">
              <div class="entry-context-banner__eyebrow">外部入口承接</div>
              <div class="entry-context-banner__title">{{ entryContextTitle }}</div>
              <div class="entry-context-banner__desc">{{ entryContextDesc }}</div>
            </div>
            <div class="entry-context-banner__actions">
              <el-button type="primary" @click="handleEntryContextPrimary">
                {{ entryContextPrimaryLabel }}
              </el-button>
              <el-button @click="goToEntryContextBack">回来源页</el-button>
            </div>
          </div>
          <div class="hero-card__guide">
            <div class="hero-card__guide-title">如果你只是刚进后台，建议先这样看</div>
            <div class="hero-card__guide-grid">
              <div v-for="item in dashboardGuideCards" :key="item.title" class="hero-card__guide-card">
                <div class="hero-card__guide-card-title">{{ item.title }}</div>
                <div class="hero-card__guide-card-desc">{{ item.desc }}</div>
                <div class="hero-card__guide-card-action">{{ item.action }}</div>
              </div>
            </div>
          </div>
        </div>
        <div class="hero-card__meta">
          <div class="hero-card__meta-label">统计周期</div>
          <div class="hero-card__meta-value">{{ summaryRangeLabel }}</div>
          <div class="hero-card__meta-sub">{{ runtimeEnv.label }} / {{ runtimeEnv.dataMode }}</div>
          <div class="hero-card__meta-badge">{{ dashboardFocusLabel }}</div>
        </div>
      </el-card>

      <div class="metric-grid mb-[15px]">
        <el-card
          v-for="item in metricCards"
          :key="item.label"
          class="metric-card"
          shadow="never"
        >
          <div class="metric-card__label">{{ item.label }}</div>
          <div class="metric-card__value">{{ item.value }}</div>
          <div class="metric-card__meta">{{ item.meta }}</div>
        </el-card>
      </div>

      <el-card class="panel-card mb-[15px]" shadow="never">
        <template #header>
          <div class="panel-card__header">
            <div>
              <div class="panel-card__title">看完首页后继续去哪</div>
              <div class="panel-card__desc">把预警、账号、系统设置和数据中心串起来，减少只看概览不落处理的情况。</div>
            </div>
            <el-tag :type="dashboardFollowupTone" effect="plain">{{ dashboardFollowupBadge }}</el-tag>
          </div>
        </template>

        <div class="dashboard-followup">
          <div class="dashboard-followup__risk">{{ dashboardFollowupHint }}</div>
          <div class="dashboard-followup__tags">
            <span v-for="item in dashboardFollowupTags" :key="item">{{ item }}</span>
          </div>
          <div class="dashboard-followup__grid">
            <button
              v-for="item in dashboardFollowupCards"
              :key="item.title"
              type="button"
              class="dashboard-followup-card"
              @click="goTo(item.path, item.query)"
            >
              <span class="dashboard-followup-card__title">{{ item.title }}</span>
              <span class="dashboard-followup-card__desc">{{ item.desc }}</span>
            </button>
          </div>
        </div>
      </el-card>

      <el-row :gutter="12" class="mb-[15px] dashboard-row">
        <el-col :xs="24" :lg="15">
          <el-card class="panel-card panel-card--entries" shadow="never">
            <template #header>
              <div class="panel-card__header">
                <div>
                  <div class="panel-card__title">快捷入口</div>
                  <div class="panel-card__desc">按线上后台习惯收敛高频运营入口，减少来回切菜单。</div>
                </div>
              </div>
            </template>

            <div class="entry-grid">
              <button
                v-for="item in quickEntries"
                :key="item.title"
                type="button"
                class="entry-card"
                @click="goTo(item.path)"
              >
                <span class="entry-card__title">{{ item.title }}</span>
                <span class="entry-card__desc">{{ item.desc }}</span>
                <span class="entry-card__action">立即进入</span>
              </button>
            </div>
          </el-card>
        </el-col>

        <el-col :xs="24" :lg="9">
          <el-card class="panel-card" shadow="never">
            <template #header>
              <div class="panel-card__header">
                <div>
                  <div class="panel-card__title">当前访问信息</div>
                  <div class="panel-card__desc">核对当前账号、入口地址和接口基址，避免误连环境。</div>
                </div>
              </div>
            </template>

            <div class="visit-list">
              <div class="visit-item">
                <span>当前用户</span>
                <strong>{{ operatorLabel }}</strong>
              </div>
              <div class="visit-item">
                <span>权限条目</span>
                <strong>{{ permissionCount }}</strong>
              </div>
              <div class="visit-item">
                <span>当前地址</span>
                <strong>{{ currentUrl }}</strong>
              </div>
              <div class="visit-item">
                <span>接口基址</span>
                <strong>{{ apiBaseUrl }}</strong>
              </div>
            </div>
          </el-card>
        </el-col>
      </el-row>

      <el-row :gutter="12" class="mb-[15px] dashboard-row">
        <el-col :xs="24" :lg="14">
          <el-card class="panel-card" shadow="never">
            <template #header>
              <div class="panel-card__header">
                <div>
                  <div class="panel-card__title">最新预警</div>
                  <div class="panel-card__desc">优先展示商家到期、低活跃等需要当天处理的异常信号。</div>
                </div>
                <el-tag type="warning" effect="plain">{{ alertTotalCount }} 条</el-tag>
              </div>
            </template>

            <el-table :data="alertRows" size="small" empty-text="当前暂无预警">
              <el-table-column label="对象" min-width="180">
                <template #default="{ row }">
                  {{ row.merchant_title || '系统' }}
                </template>
              </el-table-column>
              <el-table-column label="预警内容" min-width="180">
                <template #default="{ row }">
                  {{ row.message }}
                </template>
              </el-table-column>
              <el-table-column label="指标值" min-width="160">
                <template #default="{ row }">
                  {{ row.value || '--' }}
                </template>
              </el-table-column>
              <el-table-column label="处理" width="120" align="right">
                <template #default="{ row }">
                  <el-button link type="primary" @click="openAlertFollowup(row)">
                    {{ getAlertActionLabel(row) }}
                  </el-button>
                </template>
              </el-table-column>
            </el-table>
          </el-card>
        </el-col>

        <el-col :xs="24" :lg="10">
          <el-card class="panel-card" shadow="never">
            <template #header>
              <div class="panel-card__header">
                <div>
                  <div class="panel-card__title">热销商品</div>
                  <div class="panel-card__desc">快速看近 7 天成交额靠前的商品，便于回到商品页继续处理。</div>
                </div>
              </div>
            </template>

            <div class="rank-list">
              <button
                v-for="(item, index) in topGoods"
                :key="item.goods_id"
                type="button"
                class="rank-item rank-item--button"
                @click="openGoodsFollowup(item)"
              >
                <div class="rank-item__index">{{ index + 1 }}</div>
                <div class="rank-item__main">
                  <div class="rank-item__title">{{ item.title }}</div>
                  <div class="rank-item__meta">
                    成交额 {{ formatCurrency(item.sale_amount) }} / 订单 {{ item.order_count }}
                  </div>
                </div>
              </button>
              <div v-if="!topGoods.length" class="rank-empty">当前周期暂无热销商品数据</div>
            </div>
          </el-card>
        </el-col>
      </el-row>

      <index-count v-if="checkPermission(['admin/system.Index/count'])" class="mb-[15px]" />
    </div>
  </el-scrollbar>
</template>

<script>
import screenHeight from '@/utils/screen-height'
import checkPermission from '@/utils/permission'
import { resolveAdminRuntimeEnv } from '@/utils/runtime-env'
import { alerts, ranking, summary } from '@/api/report/platform-analytics'
import IndexCount from './components/IndexCount.vue'
import { useUserStoreHook } from '@/store/modules/user'

export default {
  name: 'Dashboard',
  components: { IndexCount },
  data() {
    return {
      height: 680,
      loading: false,
      runtimeEnv: resolveAdminRuntimeEnv(),
      summaryData: {},
      rankingData: {},
      alertsData: {}
    }
  },
  computed: {
    entrySourceLabel() {
      const source = this.$route?.query?.from
      if (source === 'system-user-center') return '来自个人中心'
      if (source === 'system-setting') return '来自系统设置'
      if (source === 'system-user-log') return '来自用户日志'
      if (source === 'member-setting') return '来自会员设置'
      return ''
    },
    entryContextVisible() {
      return Boolean(this.entrySourceLabel)
    },
    entryContextTitle() {
      if (this.entrySourceLabel === '来自个人中心') return '当前从个人中心回到控制台'
      if (this.entrySourceLabel === '来自系统设置') return '当前从系统设置回到控制台'
      if (this.entrySourceLabel === '来自用户日志') return '当前从用户日志回到控制台'
      if (this.entrySourceLabel === '来自会员设置') return '当前从会员设置回到控制台'
      return '当前为外部入口承接视角'
    },
    entryContextDesc() {
      if (this.entrySourceLabel === '来自个人中心') {
        return '这类返回通常是为了从当前账号问题切回全局巡检。建议先看告警和快捷入口，再继续去日志或设置页。'
      }
      if (this.entrySourceLabel === '来自系统设置') {
        return '这类返回通常是为了确认改完配置后，首页关键指标和常用入口是否仍正常。建议先看预警和环境信息，再继续深入。'
      }
      if (this.entrySourceLabel === '来自用户日志') {
        return '这类返回通常是为了从操作记录切回全局判断。建议先看预警和热销商品，再决定回日志还是继续去业务页。'
      }
      return '这类返回通常是为了把会员配置调整回落到全站视角。建议先看预警和快捷入口，再决定去会员、日志还是系统设置。'
    },
    entryContextPrimaryLabel() {
      if (this.entrySourceLabel === '来自个人中心') return '回个人中心'
      if (this.entrySourceLabel === '来自系统设置') return '回系统设置'
      if (this.entrySourceLabel === '来自用户日志') return '回用户日志'
      return '回会员设置'
    },
    summaryRangeLabel() {
      return this.summaryData.range?.label || '近7天'
    },
    summaryCards() {
      return this.summaryData.cards || {}
    },
    metricCards() {
      const cards = this.summaryCards
      return [
        {
          label: '近 7 天 GMV',
          value: this.formatCurrency(cards.gmv),
          meta: `客单价 ${this.formatCurrency(cards.average_order_amount)}`
        },
        {
          label: '支付订单数',
          value: this.formatCount(cards.paid_order_count),
          meta: `支付买家 ${this.formatCount(cards.paid_buyer_count)}`
        },
        {
          label: '活跃商家',
          value: this.formatCount(cards.active_merchant_count),
          meta: `新增商家 ${this.formatCount(cards.new_merchant_count)}`
        },
        {
          label: '异常预警数',
          value: this.formatCount(this.alertTotalCount),
          meta: `到期商家 ${this.formatCount(cards.expired_merchant_count)}`
        }
      ]
    },
    dashboardFocusLabel() {
      if (this.alertTotalCount > 0) {
        return '先看预警'
      }
      if (this.topGoods.length > 0) {
        return '先看热销与趋势'
      }
      return '先看关键指标'
    },
    dashboardGuideCards() {
      return [
        {
          title: '第一步：先看有没有预警',
          desc: '首页最重要的不是数字漂不漂亮，而是今天有没有必须先处理的异常、到期和积压。',
          action:
            this.alertTotalCount > 0
              ? `当前有 ${this.alertTotalCount} 条预警，建议先去平台数据中心或内部接盘对账。`
              : '当前没有明显预警，可以继续看趋势和高频入口。'
        },
        {
          title: '第二步：再看关键趋势是不是正常',
          desc: 'GMV、支付订单、活跃商家这些指标更适合拿来判断今天平台整体有没有跑偏。',
          action: `统计周期：${this.summaryRangeLabel}。`
        },
        {
          title: '第三步：最后再进具体栏目处理',
          desc: '确认异常和趋势后，再进会员、商家、商品、订单，不然很容易一上来就在菜单里来回找。',
          action: `当前快捷入口 ${this.quickEntries.length} 个。`
        }
      ]
    },
    alertTotalCount() {
      return Number(this.alertsData.count || 0)
    },
    quickEntries() {
      return [
        { title: '会员管理', desc: '查看会员趋势、会员详情、重置密码和启停会员。', path: '/member/member' },
        { title: '商家管理', desc: '查看商家状态、到期信息和续费记录，直接处理审核与续期。', path: '/merchant/merchant' },
        { title: '商品管理', desc: '查看商品状态、销量、库存和审核信息，直接处理待审商品。', path: '/goods/goods' },
        { title: '订单管理', desc: '查看订单状态、支付凭证和订单详情，支持直接做核心审核。', path: '/order/order' },
        { title: '平台数据中心', desc: '查看平台成交、退款、商家增长和异常预警。', path: '/analytics' },
        { title: '导出中心', desc: '按筛选条件导出订单、商家、续费和平台统计报表。', path: '/exports' },
        { title: '旧后台入口', desc: '尚未迁移的模块仍可直接进入旧后台处理，避免影响现有业务。', path: '__legacy__' }
      ]
    },
    alertRows() {
      return (this.alertsData.list || []).slice(0, 4)
    },
    topGoods() {
      return (this.rankingData.top_goods || []).slice(0, 4)
    },
    canUseReportSummary() {
      return this.checkPermission(['admin/report.PlatformAnalytics/summary'])
    },
    canUseReportRanking() {
      return this.checkPermission(['admin/report.PlatformAnalytics/ranking'])
    },
    canUseReportAlerts() {
      return this.checkPermission(['admin/report.PlatformAnalytics/alerts'])
    },
    operatorLabel() {
      const user = useUserStoreHook().user
      return user.nickname || user.username || '管理员'
    },
    permissionCount() {
      const user = useUserStoreHook().user
      return this.formatCount((user.roles || []).length)
    },
    currentUrl() {
      return window.location.href
    },
    apiBaseUrl() {
      return import.meta.env.VITE_APP_BASE_URL || window.location.origin
    },
    dashboardFollowupBadge() {
      if (this.alertTotalCount > 0) {
        return '优先处理预警'
      }
      if (this.topGoods.length > 0) {
        return '可继续复盘'
      }
      return '入口稳定'
    },
    dashboardFollowupTone() {
      if (this.alertTotalCount > 0) {
        return 'warning'
      }
      if (this.topGoods.length > 0) {
        return 'primary'
      }
      return 'success'
    },
    dashboardFollowupHint() {
      if (this.alertTotalCount > 0) {
        return `当前有 ${this.alertTotalCount} 条预警，建议优先从数据中心、商家页或内部接盘对账继续处理。`
      }
      if (this.topGoods.length > 0) {
        return '热销商品和关键指标都已回显，下一步更适合去商品页、会员页或导出中心做细分处理。'
      }
      return '当前首页更适合作为巡检入口，建议按账号、系统设置和数据页继续抽查关键链路。'
    },
    dashboardFollowupTags() {
      return [
        `预警：${this.formatCount(this.alertTotalCount)} 条`,
        `热销商品：${this.topGoods.length} 项`,
        `快捷入口：${this.quickEntries.length} 个`,
        `权限条目：${this.permissionCount}`
      ]
    },
    dashboardFollowupCards() {
      if (this.alertTotalCount > 0) {
        return [
          {
            title: '去平台数据中心',
            desc: '先看整体异常和趋势，再判断是商家问题、商品问题还是内部接盘链路问题。',
            path: '/analytics',
            query: { from: 'dashboard', focus: 'alerts' }
          },
          {
            title: '去内部接盘对账',
            desc: '如果预警涉及转单、接盘或待处理积压，直接继续到对账页。',
            path: '/report/internal-takeover',
            query: { from: 'dashboard', focus: 'takeover' }
          },
          {
            title: '去后台用户与日志',
            desc: '怀疑是后台操作、审核或配置导致的异常时，直接去账号和日志链路。',
            path: '/system/user-log',
            query: { from: 'dashboard', focus: 'ops' }
          }
        ]
      }
      return [
        {
          title: '去会员管理',
          desc: '首页看完指标后，落到会员页最适合继续核对具体用户和状态变化。',
          path: '/member/member',
          query: { from: 'dashboard' }
        },
        {
          title: '去系统设置',
          desc: '如果你在首页主要想确认环境和配置，继续到系统设置页更直接。',
          path: '/system/setting',
          query: { from: 'dashboard' }
        },
        {
          title: '去导出中心',
          desc: '需要把当前概览落成报表或带筛选复盘，导出中心最顺手。',
          path: '/exports',
          query: { from: 'dashboard' }
        }
      ]
    }
  },
  created() {
    this.height = screenHeight(130)
    this.fetchDashboard()
  },
  methods: {
    checkPermission,
    buildEntryRouteQuery(extraQuery = {}, nextFrom = '') {
      const query = {
        ...this.$route.query,
        ...extraQuery
      }
      if (nextFrom) {
        query.from = nextFrom
      }
      return query
    },
    handleEntryContextPrimary() {
      if (this.entrySourceLabel === '来自个人中心') {
        this.$router.push({ path: '/system/user-center', query: this.buildEntryRouteQuery({}, 'dashboard') })
        return
      }
      if (this.entrySourceLabel === '来自系统设置') {
        this.$router.push({ path: '/system/setting', query: this.buildEntryRouteQuery({}, 'dashboard') })
        return
      }
      if (this.entrySourceLabel === '来自用户日志') {
        this.$router.push({ path: '/system/user-log', query: this.buildEntryRouteQuery({}, 'dashboard') })
        return
      }
      this.$router.push({ path: '/member/setting', query: this.buildEntryRouteQuery({}, 'dashboard') })
    },
    goToEntryContextBack() {
      this.handleEntryContextPrimary()
    },
    async fetchDashboard() {
      if (!this.canUseReportSummary && !this.canUseReportRanking && !this.canUseReportAlerts) {
        return
      }
      this.loading = true
      try {
        const params = { quick_date: 'last7' }
        const tasks = [
          this.canUseReportSummary ? summary(params) : Promise.resolve({ data: {} }),
          this.canUseReportRanking ? ranking(params) : Promise.resolve({ data: {} }),
          this.canUseReportAlerts ? alerts(params) : Promise.resolve({ data: {} })
        ]
        const [summaryRes, rankingRes, alertsRes] = await Promise.all(tasks)
        this.summaryData = summaryRes.data || {}
        this.rankingData = rankingRes.data || {}
        this.alertsData = alertsRes.data || {}
      } finally {
        this.loading = false
      }
    },
    goTo(path, query = undefined) {
      if (path === '__legacy__') {
        const baseUrl = String(import.meta.env.VITE_APP_BASE_URL || window.location.origin).replace(/\/+$/, '')
        window.open(`${baseUrl}/admin/`, '_blank')
        return
      }
      if (query && typeof query === 'object') {
        this.$router.push({ path, query: this.buildEntryRouteQuery(query, query.from || 'dashboard') })
        return
      }
      this.$router.push(path)
    },
    openGoodsFollowup(item) {
      if (!item?.goods_id) {
        this.$router.push('/goods/goods')
        return
      }
      this.$router.push({
        path: '/goods/goods',
        query: this.buildEntryRouteQuery({
          search_field: 'goods_id',
          search_exp: '=',
          search_value: String(item.goods_id)
        }, 'dashboard')
      })
    },
    openAlertFollowup(row) {
      const type = String(row?.type || '').toLowerCase()
      if (type.includes('takeover') || type.includes('transfer')) {
        this.$router.push({
          path: '/report/internal-takeover',
          query: this.buildEntryRouteQuery({
            focus: 'takeover'
          }, 'dashboard')
        })
        return
      }
      if (row?.merchant_id) {
        this.$router.push({
          path: '/merchant/merchant',
          query: this.buildEntryRouteQuery({
            id: String(row.merchant_id),
            focus_mode: 'detail'
          }, 'dashboard-alert')
        })
        return
      }
      if (type.includes('renew') || type.includes('export')) {
        this.$router.push({
          path: '/exports',
          query: this.buildEntryRouteQuery({
            focus: 'alerts'
          }, 'dashboard')
        })
        return
      }
      this.$router.push({
        path: '/analytics',
        query: this.buildEntryRouteQuery({
          focus: 'alerts'
        }, 'dashboard')
      })
    },
    getAlertActionLabel(row) {
      const type = String(row?.type || '').toLowerCase()
      if (type.includes('takeover') || type.includes('transfer')) {
        return '去对账'
      }
      if (row?.merchant_id) {
        return '去商家页'
      }
      if (type.includes('renew') || type.includes('export')) {
        return '去导出'
      }
      return '去数据中心'
    },
    formatCurrency(value) {
      const amount = Number(value || 0)
      return `￥${amount.toFixed(2)}`
    },
    formatCount(value) {
      return Number(value || 0).toLocaleString('zh-CN')
    }
  }
}
</script>

<style lang="scss" scoped>
.dashboard-page {
  padding-bottom: 8px;
}

.entry-context-banner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin: 16px 0 0;
  padding: 14px 16px;
  border-radius: 14px;
  background: linear-gradient(135deg, rgba(245, 247, 255, 0.96) 0%, rgba(255, 250, 240, 0.96) 100%);
  border: 1px solid #e5e7eb;
}

.entry-context-banner__main {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.entry-context-banner__eyebrow {
  font-size: 12px;
  font-weight: 700;
  color: #64748b;
}

.entry-context-banner__title {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
}

.entry-context-banner__desc {
  font-size: 13px;
  line-height: 1.6;
  color: #64748b;
}

.entry-context-banner__actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  justify-content: flex-end;
}

.dashboard-row {
  margin-bottom: 0;
}

.hero-card,
.panel-card,
.metric-card {
  border: none;
  border-radius: 18px;
  box-shadow: 0 12px 28px rgba(15, 23, 42, 0.05);
}

.hero-card :deep(.el-card__body),
.metric-card :deep(.el-card__body),
.panel-card :deep(.el-card__body) {
  padding: 16px 18px;
}

.panel-card :deep(.el-card__header) {
  padding: 14px 18px 0;
  border-bottom: none;
}

.hero-card {
  background:
    radial-gradient(circle at top right, rgba(96, 165, 250, 0.2), transparent 28%),
    linear-gradient(135deg, rgba(248, 250, 252, 0.98), rgba(241, 245, 249, 0.96));
}

.hero-card :deep(.el-card__body) {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 18px;
}

.hero-card__eyebrow {
  font-size: 11px;
  font-weight: 700;
  color: #2563eb;
  letter-spacing: 0.08em;
}

.hero-card__title {
  margin-top: 4px;
  font-size: 24px;
  font-weight: 700;
  color: #0f172a;
}

.hero-card__desc {
  max-width: 720px;
  margin-top: 8px;
  font-size: 12px;
  line-height: 1.6;
  color: #64748b;
}

.hero-card__guide {
  margin-top: 14px;
  padding: 14px 16px;
  border-radius: 16px;
  border: 1px solid rgba(148, 163, 184, 0.16);
  background: rgba(255, 255, 255, 0.82);
}

.hero-card__guide-title {
  font-size: 13px;
  font-weight: 700;
  color: #0f172a;
}

.hero-card__guide-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 10px;
  margin-top: 12px;
}

.hero-card__guide-card {
  padding: 12px;
  border-radius: 14px;
  border: 1px solid rgba(219, 234, 254, 0.9);
  background: #fff;
}

.hero-card__guide-card-title {
  font-size: 13px;
  font-weight: 700;
  color: #111827;
}

.hero-card__guide-card-desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.hero-card__guide-card-action {
  margin-top: 8px;
  font-size: 12px;
  color: #2563eb;
}

.hero-card__meta {
  min-width: 198px;
  padding: 12px 14px;
  background: rgba(255, 255, 255, 0.85);
  border: 1px solid rgba(148, 163, 184, 0.16);
  border-radius: 14px;
}

.hero-card__meta-label {
  font-size: 11px;
  color: #64748b;
}

.hero-card__meta-value {
  margin-top: 6px;
  font-size: 18px;
  font-weight: 700;
  color: #0f172a;
}

.hero-card__meta-sub {
  margin-top: 4px;
  font-size: 11px;
  color: #64748b;
}

.hero-card__meta-badge {
  display: inline-flex;
  align-items: center;
  min-height: 26px;
  margin-top: 10px;
  padding: 0 10px;
  border-radius: 999px;
  background: #dbeafe;
  color: #1d4ed8;
  font-size: 12px;
  font-weight: 700;
}

.metric-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 10px;
}

.metric-card {
  background: rgba(255, 255, 255, 0.94);
}

.metric-card__label {
  font-size: 12px;
  color: #64748b;
}

.metric-card__value {
  margin-top: 6px;
  font-size: 24px;
  font-weight: 700;
  color: #0f172a;
  line-height: 1.2;
}

.metric-card__meta {
  margin-top: 6px;
  font-size: 11px;
  color: #64748b;
}

.panel-card__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 10px;
}

.panel-card__title {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
  line-height: 1.3;
}

.panel-card__desc {
  margin-top: 4px;
  font-size: 12px;
  line-height: 1.5;
  color: #64748b;
}

.entry-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 10px;
}

.entry-card {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 6px;
  min-height: 118px;
  padding: 12px 14px;
  text-align: left;
  cursor: pointer;
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.92));
  border: 1px solid rgba(148, 163, 184, 0.18);
  border-radius: 14px;
  transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}

.entry-card:hover {
  transform: translateY(-1px);
  border-color: rgba(59, 130, 246, 0.24);
  box-shadow: 0 14px 30px rgba(59, 130, 246, 0.08);
}

.entry-card__title {
  font-size: 14px;
  font-weight: 700;
  color: #0f172a;
  line-height: 1.35;
}

.entry-card__desc {
  display: -webkit-box;
  overflow: hidden;
  font-size: 12px;
  line-height: 1.5;
  color: #64748b;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 3;
}

.entry-card__action {
  margin-top: auto;
  padding-top: 2px;
  font-size: 11px;
  font-weight: 700;
  color: #2563eb;
}

.visit-list {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
}

.visit-item {
  padding: 12px 14px;
  background: rgba(248, 250, 252, 0.92);
  border: 1px solid rgba(148, 163, 184, 0.16);
  border-radius: 14px;
  min-width: 0;
}

.visit-item span {
  display: block;
  font-size: 11px;
  color: #64748b;
}

.visit-item strong {
  display: block;
  margin-top: 6px;
  word-break: break-all;
  font-size: 13px;
  line-height: 1.45;
  color: #0f172a;
}

.rank-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.rank-item {
  display: flex;
  gap: 10px;
  align-items: flex-start;
  padding: 12px 14px;
  background: rgba(248, 250, 252, 0.92);
  border: 1px solid rgba(148, 163, 184, 0.16);
  border-radius: 14px;
}

.rank-item--button {
  width: 100%;
  text-align: left;
  transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
}

.rank-item--button:hover {
  transform: translateY(-1px);
  border-color: rgba(59, 130, 246, 0.22);
  box-shadow: 0 12px 24px rgba(59, 130, 246, 0.08);
}

.rank-item__index {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 30px;
  height: 30px;
  font-size: 13px;
  font-weight: 700;
  color: #1d4ed8;
  background: rgba(219, 234, 254, 0.82);
  border-radius: 10px;
}

.rank-item__main {
  min-width: 0;
}

.rank-item__title {
  font-size: 13px;
  font-weight: 700;
  color: #0f172a;
  word-break: break-all;
  line-height: 1.4;
}

.rank-item__meta {
  margin-top: 4px;
  font-size: 11px;
  color: #64748b;
}

.rank-empty {
  padding: 14px 10px;
  font-size: 12px;
  color: #94a3b8;
  text-align: center;
}

.panel-card :deep(.el-table th.el-table__cell) {
  padding: 8px 0;
}

.panel-card :deep(.el-table td.el-table__cell) {
  padding: 9px 0;
}

.panel-card :deep(.el-table .cell) {
  line-height: 1.45;
}

.dashboard-followup {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.dashboard-followup__risk {
  padding: 12px 14px;
  border-radius: 14px;
  border: 1px solid rgba(251, 191, 36, 0.3);
  background: rgba(255, 247, 237, 0.92);
  color: #9a3412;
  font-size: 12px;
  line-height: 1.7;
}

.dashboard-followup__tags {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.dashboard-followup__tags span {
  display: inline-flex;
  align-items: center;
  min-height: 30px;
  padding: 0 10px;
  border: 1px solid rgba(148, 163, 184, 0.18);
  border-radius: 999px;
  background: rgba(248, 250, 252, 0.92);
  color: #334155;
  font-size: 12px;
}

.dashboard-followup__grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 10px;
}

.dashboard-followup-card {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 8px;
  padding: 14px 16px;
  text-align: left;
  border-radius: 14px;
  border: 1px solid rgba(148, 163, 184, 0.18);
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.92));
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}

.dashboard-followup-card:hover {
  transform: translateY(-1px);
  border-color: rgba(59, 130, 246, 0.24);
  box-shadow: 0 14px 30px rgba(59, 130, 246, 0.08);
}

.dashboard-followup-card__title {
  font-size: 14px;
  font-weight: 700;
  color: #0f172a;
}

.dashboard-followup-card__desc {
  font-size: 12px;
  line-height: 1.6;
  color: #64748b;
}

@media (max-width: 1100px) {
  .metric-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .entry-grid,
  .dashboard-followup__grid,
  .visit-list {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 900px) {
  .entry-context-banner,
  .hero-card :deep(.el-card__body) {
    flex-direction: column;
  }

  .entry-grid,
  .metric-grid,
  .visit-list,
  .dashboard-followup__grid {
    grid-template-columns: 1fr;
  }
}
</style>
