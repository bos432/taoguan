<template>
  <div v-loading="loading" class="report-page">
    <el-card class="panel panel--hero" shadow="never">
      <div class="panel__header">
        <div>
          <div class="panel__title">超级管理员数据中心</div>
          <div class="panel__desc">
            对齐 `admin-next` 的筛选表达与统计层级，统一查看成交、退款、商家增长、续费、排行和异常预警。
          </div>
        </div>
        <div class="panel__actions">
          <el-tag effect="plain">{{ operatorLabel }}</el-tag>
          <el-tag effect="plain" type="success">实时后端数据</el-tag>
          <el-button :loading="loading" type="primary" @click="reloadAll">刷新数据</el-button>
        </div>
      </div>

      <div class="quick-range">
        <button
          v-for="item in quickRanges"
          :key="item.value"
          class="quick-range__item"
          :class="{ 'quick-range__item--active': query.quick_date === item.value }"
          type="button"
          @click="applyQuickRange(item.value)"
        >
          {{ item.label }}
        </button>
      </div>

      <el-form class="filter-form" inline>
        <el-form-item label="日期范围">
          <el-date-picker
            v-model="query.daterange"
            type="daterange"
            value-format="YYYY-MM-DD"
            start-placeholder="开始日期"
            end-placeholder="结束日期"
            style="width: 260px"
          />
        </el-form-item>
        <el-form-item label="月份">
          <el-date-picker
            v-model="query.month"
            type="month"
            value-format="YYYY-MM"
            placeholder="选择月份"
            style="width: 160px"
          />
        </el-form-item>
        <el-form-item label="统计粒度">
          <el-select v-model="query.granularity" clearable style="width: 120px">
            <el-option label="自动" value="" />
            <el-option
              v-for="item in granularityOptions"
              :key="item.value"
              :label="item.label"
              :value="item.value"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="商家">
          <el-select v-model="query.merchant_id" clearable filterable style="width: 220px">
            <el-option label="全部商家" :value="-1" />
            <el-option
              v-for="item in merchantOptions"
              :key="item.id"
              :label="item.title"
              :value="item.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="商家状态">
          <el-select v-model="query.auth_state" clearable style="width: 140px">
            <el-option label="全部状态" :value="-1" />
            <el-option
              v-for="item in merchantStatuses"
              :key="item.value"
              :label="item.label"
              :value="Number(item.value)"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="到期状态">
          <el-select v-model="query.expire_status" clearable style="width: 140px">
            <el-option label="全部状态" value="" />
            <el-option
              v-for="item in expireStatuses"
              :key="item.value"
              :label="item.label"
              :value="item.value"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="商品分类">
          <el-select v-model="query.goods_type_id" clearable filterable style="width: 180px">
            <el-option label="全部分类" :value="-1" />
            <el-option
              v-for="item in goodsTypeOptions"
              :key="item.value"
              :label="item.label"
              :value="item.value"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="订单状态">
          <el-select v-model="query.order_status" clearable style="width: 150px">
            <el-option label="全部订单" :value="-1" />
            <el-option
              v-for="item in orderStatuses"
              :key="item.value"
              :label="item.label"
              :value="Number(item.value)"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="支付状态">
          <el-select v-model="query.pay_status" clearable style="width: 140px">
            <el-option label="全部支付" :value="-1" />
            <el-option
              v-for="item in payStatuses"
              :key="item.value"
              :label="item.label"
              :value="Number(item.value)"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="商品来源">
          <el-select v-model="query.source" clearable style="width: 140px">
            <el-option label="全部来源" :value="-1" />
            <el-option
              v-for="item in sourceOptions"
              :key="item.value"
              :label="item.label"
              :value="Number(item.value)"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="金额区间">
          <div class="amount-range">
            <el-input v-model="query.amount_min" clearable placeholder="最低金额" />
            <span>至</span>
            <el-input v-model="query.amount_max" clearable placeholder="最高金额" />
          </div>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="applyFilters">应用筛选</el-button>
          <el-button @click="resetQuery">重置</el-button>
        </el-form-item>
      </el-form>

      <div class="current-params">
        <div class="current-params__title">当前分析条件</div>
        <div class="current-params__tags">
          <el-tag
            v-for="item in activeFilterTags"
            :key="item"
            effect="plain"
          >
            {{ item }}
          </el-tag>
          <el-tag v-if="!activeFilterTags.length" effect="plain" type="info">
            默认条件：近 7 天 / 全部商家
          </el-tag>
        </div>
      </div>
      <div v-if="dashboardContextVisible" class="dashboard-context-banner">
        <div class="dashboard-context-banner__main">
          <div class="dashboard-context-banner__title">{{ dashboardContextTitle }}</div>
          <div class="dashboard-context-banner__desc">{{ dashboardContextDesc }}</div>
        </div>
        <div class="dashboard-context-banner__actions">
          <el-button text type="primary" @click="handleDashboardContextPrimary">
            {{ dashboardContextPrimaryLabel }}
          </el-button>
          <el-button text type="primary" @click="clearDashboardContext">按普通分析页继续</el-button>
        </div>
      </div>
      <div class="plain-guide">
        <div class="plain-guide__header">
          <div>
            <div class="plain-guide__title">这页建议先这样看</div>
            <div class="plain-guide__desc">先看范围和预警，再判断是会员波动、商家治理、内部接盘，还是订单退款问题，不要一上来就在大表里翻。</div>
          </div>
          <el-tag effect="plain" type="primary">{{ analysisFocusLabel }}</el-tag>
        </div>
        <div class="plain-guide__grid">
          <div v-for="item in analysisGuideCards" :key="item.title" class="plain-guide-card">
            <div class="plain-guide-card__title">{{ item.title }}</div>
            <div class="plain-guide-card__desc">{{ item.desc }}</div>
            <div class="plain-guide-card__action">{{ item.action }}</div>
          </div>
        </div>
      </div>
    </el-card>

    <div class="summary-banner">
      <div class="summary-banner__item">
        <span class="summary-banner__label">统计范围</span>
        <strong>{{ summaryRangeLabel }}</strong>
      </div>
      <div class="summary-banner__item">
        <span class="summary-banner__label">统计粒度</span>
        <strong>{{ summaryGranularityLabel }}</strong>
      </div>
      <div class="summary-banner__item">
        <span class="summary-banner__label">预警数量</span>
        <strong>{{ formatCount(alertList.length) }}</strong>
      </div>
      <div class="summary-banner__item">
        <span class="summary-banner__label">当前操作人</span>
        <strong>{{ operatorLabel }}</strong>
      </div>
    </div>

    <div class="action-strip">
      <button type="button" class="action-strip__card" @click="goToMemberStatistic">
        <span class="action-strip__title">去会员统计复盘</span>
        <span class="action-strip__desc">把平台波动继续拆到会员增长、活跃和变化趋势。</span>
      </button>
      <button type="button" class="action-strip__card" @click="goToMerchantList">
        <span class="action-strip__title">去商家页继续处理</span>
        <span class="action-strip__desc">把当前商家条件直接带去商家管理，继续做审核、续费和状态处理。</span>
      </button>
      <button type="button" class="action-strip__card" @click="goToExportCenter">
        <span class="action-strip__title">去导出中心</span>
        <span class="action-strip__desc">当前分析条件可直接带去导出中心，沉淀对账和运营报表。</span>
      </button>
      <button type="button" class="action-strip__card" @click="goToInternalTakeover">
        <span class="action-strip__title">去内部接盘对账</span>
        <span class="action-strip__desc">当异常来自内部号承接或商品流转时，继续下钻到对账页。</span>
      </button>
      <button type="button" class="action-strip__card" @click="goToOrderList">
        <span class="action-strip__title">去订单页复核</span>
        <span class="action-strip__desc">当波动更像支付、退款、发货或订单状态问题时，直接回订单侧继续排查。</span>
      </button>
    </div>

    <div class="metrics-grid">
      <el-card
        v-for="item in summaryCards"
        :key="item.key"
        class="metric-card"
        :class="`metric-card--${item.tone}`"
        shadow="never"
      >
        <div class="metric-card__label">{{ item.label }}</div>
        <div class="metric-card__value">{{ item.value }}</div>
        <div class="metric-card__meta">{{ item.meta }}</div>
      </el-card>
    </div>

    <div class="chart-grid">
      <el-card class="panel" shadow="never">
        <template #header>
          <div class="panel__header-bar">
            <div>
              <div class="panel__sub-title">成交趋势</div>
              <div class="panel__sub-desc">成交额与支付订单数联动观察</div>
            </div>
          </div>
        </template>
        <div ref="tradeChartRef" class="chart"></div>
      </el-card>
      <el-card class="panel" shadow="never">
        <template #header>
          <div class="panel__header-bar">
            <div>
              <div class="panel__sub-title">退款趋势</div>
              <div class="panel__sub-desc">退款金额与退款订单数同步查看</div>
            </div>
          </div>
        </template>
        <div ref="refundChartRef" class="chart"></div>
      </el-card>
      <el-card class="panel" shadow="never">
        <template #header>
          <div class="panel__header-bar">
            <div>
              <div class="panel__sub-title">商家增长趋势</div>
              <div class="panel__sub-desc">新增商家与累计商家变化</div>
            </div>
          </div>
        </template>
        <div ref="merchantChartRef" class="chart"></div>
      </el-card>
      <el-card class="panel" shadow="never">
        <template #header>
          <div class="panel__header-bar">
            <div>
              <div class="panel__sub-title">续费趋势</div>
              <div class="panel__sub-desc">续费金额和续费次数汇总</div>
            </div>
          </div>
        </template>
        <div ref="renewChartRef" class="chart"></div>
      </el-card>
    </div>

    <div class="table-grid">
      <el-card class="panel" shadow="never">
        <template #header>
          <div class="panel__header-bar">
            <div>
              <div class="panel__sub-title">热销商品 Top10</div>
              <div class="panel__sub-desc">按成交额和销量排序</div>
            </div>
          </div>
        </template>
        <div v-if="topGoodsHighlights.length" class="highlight-grid">
          <button
            v-for="item in topGoodsHighlights"
            :key="item.goods_id"
            class="highlight-card highlight-card--goods"
            type="button"
            @click="goToGoodsList(item)"
          >
            <div class="highlight-card__rank">TOP {{ item.rank }}</div>
            <div class="highlight-card__title">{{ item.title || `商品 #${item.goods_id}` }}</div>
            <div class="highlight-card__meta">销量 {{ formatCount(item.sale_num) }} / 订单 {{ formatCount(item.order_count) }}</div>
            <div class="highlight-card__value">{{ formatCurrency(item.sale_amount) }}</div>
          </button>
        </div>
        <el-table :data="rankingData.top_goods || []" empty-text="当前条件下暂无热销商品数据" row-key="goods_id">
          <el-table-column type="index" label="#" width="60" />
          <el-table-column prop="goods_id" label="商品ID" width="96" />
          <el-table-column prop="title" label="商品名称" min-width="220" />
          <el-table-column label="继续处理" width="120">
            <template #default="{ row }">
              <el-button link type="primary" @click="goToGoodsList(row)">去商品页</el-button>
            </template>
          </el-table-column>
          <el-table-column label="销量" width="110">
            <template #default="{ row }">{{ formatCount(row.sale_num) }}</template>
          </el-table-column>
          <el-table-column label="成交额" width="130">
            <template #default="{ row }">{{ formatCurrency(row.sale_amount) }}</template>
          </el-table-column>
          <el-table-column label="订单数" width="110">
            <template #default="{ row }">{{ formatCount(row.order_count) }}</template>
          </el-table-column>
        </el-table>
      </el-card>

      <el-card class="panel" shadow="never">
        <template #header>
          <div class="panel__header-bar">
            <div>
              <div class="panel__sub-title">商家成交 Top10</div>
              <div class="panel__sub-desc">点击可查看商家分析详情</div>
            </div>
          </div>
        </template>
        <div v-if="topMerchantHighlights.length" class="highlight-grid">
          <button
            v-for="item in topMerchantHighlights"
            :key="item.merchant_id"
            class="highlight-card highlight-card--merchant"
            type="button"
            @click="openMerchantDetail(item)"
          >
            <div class="highlight-card__rank">TOP {{ item.rank }}</div>
            <div class="highlight-card__title">{{ item.title || `商家 #${item.merchant_id}` }}</div>
            <div class="highlight-card__meta">{{ item.auth_state_title || '审核待定' }} / {{ item.expire_status_title || '到期状态待定' }}</div>
            <div class="highlight-card__value">{{ formatCurrency(item.paid_amount) }}</div>
          </button>
        </div>
        <el-table :data="rankingData.top_merchants || []" empty-text="当前条件下暂无商家成交排行" row-key="merchant_id">
          <el-table-column type="index" label="#" width="60" />
          <el-table-column prop="merchant_id" label="商家ID" width="96" />
          <el-table-column label="商家名称" min-width="200">
            <template #default="{ row }">
              <el-button link type="primary" @click="openMerchantDetail(row)">
                {{ row.title }}
              </el-button>
            </template>
          </el-table-column>
          <el-table-column prop="auth_state_title" label="审核" width="100" />
          <el-table-column prop="expire_status_title" label="到期" width="110" />
          <el-table-column label="成交额" width="130">
            <template #default="{ row }">{{ formatCurrency(row.paid_amount) }}</template>
          </el-table-column>
          <el-table-column label="支付订单" width="110">
            <template #default="{ row }">{{ formatCount(row.paid_order_count) }}</template>
          </el-table-column>
        </el-table>
      </el-card>
    </div>

    <el-card class="panel" shadow="never">
      <template #header>
        <div class="panel__header-bar">
          <div>
            <div class="panel__sub-title">异常预警</div>
            <div class="panel__sub-desc">优先识别到期、异常经营和风险商家</div>
          </div>
          <el-tag type="warning" effect="plain">当前 {{ formatCount(alertList.length) }} 条</el-tag>
        </div>
      </template>
      <div v-if="alertHighlights.length" class="alert-overview">
        <div
          v-for="item in alertHighlights"
          :key="`${item.level}-${item.merchant_id}`"
          class="alert-card"
          :class="`alert-card--${item.level || 'info'}`"
        >
          <div class="alert-card__level">{{ alertLevelLabel(item.level) }}</div>
          <div class="alert-card__title">{{ item.merchant_title || `商家 #${item.merchant_id}` }}</div>
          <div class="alert-card__desc">{{ item.message }}</div>
          <div class="alert-card__footer">
            <span>{{ item.value || '待补充' }}</span>
            <div>
              <el-button link type="primary" @click="openMerchantDetail(item)">查看商家</el-button>
              <el-button link @click="goToOrderList(item)">看订单</el-button>
            </div>
          </div>
        </div>
      </div>
      <el-table :data="alertList" empty-text="当前条件下暂无异常预警" row-key="type">
        <el-table-column prop="merchant_id" label="商家ID" width="100" />
        <el-table-column prop="merchant_title" label="商家名称" min-width="180" />
        <el-table-column prop="message" label="预警内容" min-width="220" />
        <el-table-column label="级别" width="100">
          <template #default="{ row }">
            <el-tag :type="alertTagType(row.level)">{{ row.level || 'info' }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="value" label="指标值" width="140" />
        <el-table-column label="商家分析" width="120">
          <template #default="{ row }">
            <el-button link type="primary" @click="openMerchantDetail(row)">查看</el-button>
          </template>
        </el-table-column>
        <el-table-column label="订单侧" width="100">
          <template #default="{ row }">
            <el-button link @click="goToOrderList(row)">去复核</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <el-drawer v-model="detailVisible" size="760px" title="商家分析详情">
      <div v-loading="detailLoading" class="detail-wrap">
        <template v-if="merchantDetailData">
          <el-descriptions :column="2" border>
            <el-descriptions-item label="商家">
              {{ merchantDetailData.merchant?.title || '--' }}
            </el-descriptions-item>
            <el-descriptions-item label="审核状态">
              {{ merchantDetailData.merchant?.auth_state_title || '--' }}
            </el-descriptions-item>
            <el-descriptions-item label="到期状态">
              {{ merchantDetailData.merchant?.expire_status_title || '--' }}
            </el-descriptions-item>
            <el-descriptions-item label="到期时间">
              {{ merchantDetailData.merchant?.expire_time || '--' }}
            </el-descriptions-item>
          </el-descriptions>

          <div class="detail-followup">
            <el-button type="primary" @click="goToFocusedMerchant">去商家页处理</el-button>
            <el-button @click="goToOrderList({ merchant_id: currentDetailMerchantId })">去订单页复核</el-button>
            <el-button @click="goToInternalTakeoverForMerchant">去内部接盘对账</el-button>
            <el-button @click="goToExportCenterForMerchant">导出当前商家分析</el-button>
          </div>

          <div class="detail-metrics">
            <div
              v-for="item in merchantOverviewCards"
              :key="item.label"
              class="detail-metric"
            >
              <div class="detail-metric__label">{{ item.label }}</div>
              <div class="detail-metric__value">{{ item.value }}</div>
            </div>
          </div>
        </template>
      </div>
    </el-drawer>
  </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import * as echarts from 'echarts/core'
import { BarChart, LineChart } from 'echarts/charts'
import { GridComponent, LegendComponent, TooltipComponent } from 'echarts/components'
import { CanvasRenderer } from 'echarts/renderers'
import {
  alerts,
  filters,
  merchantDetail,
  ranking,
  summary,
  trend
} from '@/api/report/platform-analytics'
import { useUserStoreHook } from '@/store/modules/user'

echarts.use([LineChart, BarChart, GridComponent, LegendComponent, TooltipComponent, CanvasRenderer])

const route = useRoute()
const router = useRouter()
const userStore = useUserStoreHook()
const loading = ref(false)
const detailLoading = ref(false)
const filterData = ref({})
const summaryData = ref({})
const trendData = ref({})
const rankingData = ref({})
const alertsData = ref({})
const merchantDetailData = ref(null)
const detailVisible = ref(false)
const currentDetailMerchantId = ref(0)
const ignoreRouteWatch = ref(false)
const queryStorageKey = 'platform-analytics-query'

const tradeChartRef = ref()
const refundChartRef = ref()
const merchantChartRef = ref()
const renewChartRef = ref()

let tradeChart
let refundChart
let merchantChart
let renewChart

const defaultQuery = () => ({
  quick_date: 'last7',
  daterange: [],
  month: '',
  granularity: '',
  merchant_id: -1,
  auth_state: -1,
  expire_status: '',
  goods_type_id: -1,
  order_status: -1,
  pay_status: -1,
  source: -1,
  amount_min: '',
  amount_max: ''
})

const query = reactive(defaultQuery())

const quickRanges = computed(() => filterData.value.quick_ranges || [])
const merchantOptions = computed(() => filterData.value.merchants || [])
const merchantStatuses = computed(() => filterData.value.merchant_statuses || [])
const expireStatuses = computed(() => filterData.value.expire_statuses || [])
const sourceOptions = computed(() => filterData.value.sources || [])
const orderStatuses = computed(() => filterData.value.order_statuses || [])
const payStatuses = computed(() => filterData.value.pay_statuses || [])
const granularityOptions = computed(() => filterData.value.granularity_options || [])
const alertList = computed(() => {
  const priorityMap = { danger: 0, warning: 1, info: 2 }
  return [...(alertsData.value.list || [])].sort((left, right) => {
    const leftPriority = priorityMap[left.level] ?? 9
    const rightPriority = priorityMap[right.level] ?? 9
    if (leftPriority !== rightPriority) return leftPriority - rightPriority
    return Number(right.merchant_id || 0) - Number(left.merchant_id || 0)
  })
})
const goodsTypeOptions = computed(() => flattenGoodsTypes(filterData.value.goods_types || []))
const operatorLabel = computed(() => {
  return userStore.user.nickname || userStore.user.username || '当前管理员'
})
const topGoodsHighlights = computed(() => {
  return (rankingData.value.top_goods || []).slice(0, 3).map((item, index) => ({
    ...item,
    rank: index + 1
  }))
})
const topMerchantHighlights = computed(() => {
  return (rankingData.value.top_merchants || []).slice(0, 3).map((item, index) => ({
    ...item,
    rank: index + 1
  }))
})
const alertHighlights = computed(() => alertList.value.slice(0, 3))

const summaryRangeLabel = computed(() => summaryData.value.range?.label || '近 7 天')
const summaryGranularityLabel = computed(() => {
  return summaryData.value.range?.granularity === 'month' ? '按月' : '按天'
})
const routeFrom = computed(() => String(route.query.from || '').toLowerCase())
const routeFocus = computed(() => String(route.query.focus || '').toLowerCase())
const dashboardContextVisible = computed(() => routeFrom.value === 'dashboard' && ['alerts', 'takeover', 'ops'].includes(routeFocus.value))
const dashboardContextTitle = computed(() => {
  const map = {
    alerts: '当前是从控制台预警入口进来的',
    takeover: '当前是从控制台接盘入口进来的',
    ops: '当前是从控制台异常操作入口进来的'
  }
  return map[routeFocus.value] || '当前带有控制台来源上下文'
})
const dashboardContextDesc = computed(() => {
  const map = {
    alerts: '建议先看预警卡片、异常商家和退款波动，再决定是继续去商家页、订单页还是内部接盘对账。',
    takeover: '建议先看异常预警和商家排行，再重点下钻内部号承接、待转商品和账单异常。',
    ops: '建议先锁定最近异常时间段和相关商家，再联动去操作日志、商家页或订单页做交叉核对。'
  }
  return map[routeFocus.value] || '当前更适合先按来源问题链路处理，再看全量趋势。'
})
const dashboardContextPrimaryLabel = computed(() => {
  const map = {
    alerts: '先看预警卡片',
    takeover: '去内部接盘对账',
    ops: '去操作日志'
  }
  return map[routeFocus.value] || '继续处理'
})

const summaryCards = computed(() => {
  const cards = summaryData.value.cards || {}
  return [
    { key: 'gmv', label: 'GMV', value: formatCurrency(cards.gmv), meta: '核心成交额', tone: 'blue' },
    { key: 'paid_order_count', label: '支付订单数', value: formatCount(cards.paid_order_count), meta: '已支付并完成统计', tone: 'cyan' },
    { key: 'paid_buyer_count', label: '支付买家数', value: formatCount(cards.paid_buyer_count), meta: '去重买家规模', tone: 'sky' },
    { key: 'average_order_amount', label: '客单价', value: formatCurrency(cards.average_order_amount), meta: '平均每单成交', tone: 'indigo' },
    { key: 'refund_amount', label: '退款金额', value: formatCurrency(cards.refund_amount), meta: '退款总金额', tone: 'orange' },
    { key: 'refund_rate', label: '退款率', value: `${Number(cards.refund_rate || 0).toFixed(2)}%`, meta: '退款金额 / GMV', tone: 'amber' },
    { key: 'new_merchant_count', label: '新增商家', value: formatCount(cards.new_merchant_count), meta: '统计期新增', tone: 'green' },
    { key: 'active_merchant_count', label: '活跃商家', value: formatCount(cards.active_merchant_count), meta: '有成交商家数', tone: 'emerald' },
    { key: 'expiring_merchant_count', label: '即将到期', value: formatCount(cards.expiring_merchant_count), meta: '需优先跟进', tone: 'yellow' },
    { key: 'expired_merchant_count', label: '已到期', value: formatCount(cards.expired_merchant_count), meta: '重点预警对象', tone: 'red' },
    { key: 'on_sale_goods_count', label: '在售商品', value: formatCount(cards.on_sale_goods_count), meta: '可售商品规模', tone: 'teal' },
    { key: 'sold_out_goods_count', label: '售罄商品', value: formatCount(cards.sold_out_goods_count), meta: '库存需关注', tone: 'slate' }
  ]
})

const merchantOverviewCards = computed(() => {
  const overview = merchantDetailData.value?.overview || {}
  return [
    { label: '成交额', value: formatCurrency(overview.paid_amount) },
    { label: '支付订单', value: formatCount(overview.paid_order_count) },
    { label: '支付买家', value: formatCount(overview.paid_buyer_count) },
    { label: '退款金额', value: formatCurrency(overview.refund_amount) },
    { label: '商品数', value: formatCount(overview.goods_count) },
    { label: '续费次数', value: formatCount(overview.renew_count) }
  ]
})

const activeFilterTags = computed(() => {
  const tags = []

  if (dashboardContextVisible.value) {
    const labelMap = {
      alerts: '来源：控制台预警',
      takeover: '来源：控制台接盘',
      ops: '来源：控制台异常操作'
    }
    tags.push(labelMap[routeFocus.value] || '来源：控制台')
  }

  if (query.quick_date) {
    const quick = quickRanges.value.find((item) => item.value === query.quick_date)
    if (quick) tags.push(`快捷时间：${quick.label}`)
  }
  if (query.daterange.length === 2) {
    tags.push(`时间区间：${query.daterange[0]} 至 ${query.daterange[1]}`)
  }
  if (query.month) tags.push(`月份：${query.month}`)
  if (query.granularity) tags.push(`统计粒度：${query.granularity === 'month' ? '按月' : '按天'}`)
  pushOptionTag(tags, '商家', query.merchant_id, merchantOptions.value, 'id', 'title')
  pushOptionTag(tags, '商家状态', query.auth_state, merchantStatuses.value)
  pushOptionTag(tags, '到期状态', query.expire_status, expireStatuses.value)
  pushOptionTag(tags, '商品分类', query.goods_type_id, goodsTypeOptions.value)
  pushOptionTag(tags, '订单状态', query.order_status, orderStatuses.value)
  pushOptionTag(tags, '支付状态', query.pay_status, payStatuses.value)
  pushOptionTag(tags, '商品来源', query.source, sourceOptions.value)
  if (query.amount_min || query.amount_max) {
    tags.push(`金额区间：${query.amount_min || '不限'} 至 ${query.amount_max || '不限'}`)
  }

  return tags
})

const analysisFocusLabel = computed(() => {
  if (dashboardContextVisible.value) {
    const map = {
      alerts: '先处理首页预警',
      takeover: '先看接盘链路',
      ops: '先追异常操作'
    }
    return map[routeFocus.value] || '先接住首页来源'
  }
  if (alertList.value.length) {
    return '先处理预警'
  }
  if (Number(query.merchant_id) > 0) {
    return '先看单商家'
  }
  if (Number(query.pay_status) !== -1 || Number(query.order_status) !== -1) {
    return '先看订单支付'
  }
  if (Number(query.auth_state) !== -1 || query.expire_status) {
    return '先看商家治理'
  }
  return '先看整体波动'
})

const analysisGuideCards = computed(() => {
  const cards = [
    {
      title: '第一步先看顶部摘要',
      desc: `先确认你现在看的时间范围是“${summaryRangeLabel.value}”，再看成交、退款、商家到期这些总量有没有明显波动。`,
      action: '如果范围不对，先改筛选，不要直接解读下面的大表。'
    },
    {
      title: '第二步看预警和排行',
      desc: alertList.value.length
        ? `当前有 ${formatCount(alertList.value.length)} 条预警，优先看红色和黄色，再结合商家排行判断是不是集中在少数商家。`
        : '当前没有明显预警，就先看商家排行、退款趋势和续费趋势，找出波动最大的点。',
      action: alertList.value.length ? '有预警时先查原因，再决定要不要下钻。' : '没预警时重点看趋势变化。'
    },
    {
      title: '第三步带条件下钻',
      desc:
        Number(query.merchant_id) > 0
          ? '你已经锁定到单个商家了，接下来更适合去商家管理或订单页继续做处理。'
          : '这页更适合先找问题，不适合直接做所有处理。找到异常后，再带着条件跳去商家、订单或会员页。',
      action: '处理动作尽量在对应业务页完成，分析页主要负责发现问题。'
    },
    {
      title: '第四步分清异常归属',
      desc:
        Number(query.pay_status) !== -1 || Number(query.order_status) !== -1
          ? '当前更像订单或支付问题，优先去订单列表复核支付、退款和状态流转。'
          : '如果异常像内部号接手、账单缺失或商品流转问题，就不要在这里硬查，直接去内部接盘对账。',
      action:
        Number(query.pay_status) !== -1 || Number(query.order_status) !== -1
          ? '订单类异常优先去订单页。'
          : '内部承接类异常优先去内部接盘对账。'
    }
  ]
  if (dashboardContextVisible.value) {
    cards.unshift({
      title: '先接住控制台来源',
      desc: dashboardContextDesc.value,
      action: `当前来源重点：${dashboardContextTitle.value}`
    })
  }
  return cards
})

onMounted(async () => {
  await loadFilters()
  if (!hasRouteFilters()) {
    const persistedParams = readPersistedParams()
    if (persistedParams) {
      applyQueryParams(persistedParams)
      ignoreRouteWatch.value = true
      await router.replace({
        name: route.name,
        query: stringifyQuery(persistedParams)
      })
    } else {
      restoreQueryFromRoute()
    }
  } else {
    restoreQueryFromRoute()
  }
  await reloadAll()
  window.addEventListener('resize', resizeCharts)
})

watch(
  () => route.fullPath,
  async () => {
    if (ignoreRouteWatch.value) {
      ignoreRouteWatch.value = false
      return
    }
    restoreQueryFromRoute()
    await reloadAll()
  }
)

onBeforeUnmount(() => {
  window.removeEventListener('resize', resizeCharts)
  disposeCharts()
})

async function loadFilters() {
  const res = await filters()
  filterData.value = res.data || {}
}

function flattenGoodsTypes(list = [], bucket = []) {
  list.forEach((item) => {
    bucket.push({
      value: Number(item.value ?? item.id),
      label: String(item.label ?? item.title ?? '')
    })
    if (Array.isArray(item.children) && item.children.length) {
      flattenGoodsTypes(item.children, bucket)
    }
  })
  return bucket
}

function pushOptionTag(tags, label, value, options = [], valueKey = 'value', labelKey = 'label') {
  if (value === '' || value === -1 || value === null || value === undefined) return
  const selected = options.find((item) => item && String(item[valueKey]) === String(value))
  if (selected) {
    tags.push(`${label}：${selected[labelKey]}`)
  }
}

function parseNumber(value, fallback = -1) {
  if (value === '' || value === null || value === undefined) return fallback
  const next = Number(value)
  return Number.isNaN(next) ? fallback : next
}

function hasRouteFilters() {
  return Object.keys(route.query || {}).length > 0
}

function readPersistedParams() {
  try {
    const raw = localStorage.getItem(queryStorageKey)
    if (!raw) return null
    const parsed = JSON.parse(raw)
    return parsed && typeof parsed === 'object' ? parsed : null
  } catch {
    return null
  }
}

function writePersistedParams(params) {
  localStorage.setItem(queryStorageKey, JSON.stringify(params))
}

function stringifyQuery(params) {
  return Object.fromEntries(Object.entries(params).map(([key, value]) => [key, String(value)]))
}

function applyQueryParams(nextQuery = {}) {
  query.quick_date = typeof nextQuery.quick_date === 'string' && nextQuery.quick_date ? nextQuery.quick_date : 'last7'
  query.month = typeof nextQuery.month === 'string' ? nextQuery.month : ''
  query.granularity = typeof nextQuery.granularity === 'string' ? nextQuery.granularity : ''
  query.expire_status = typeof nextQuery.expire_status === 'string' ? nextQuery.expire_status : ''
  query.amount_min = typeof nextQuery.amount_min === 'string' ? nextQuery.amount_min : ''
  query.amount_max = typeof nextQuery.amount_max === 'string' ? nextQuery.amount_max : ''
  query.merchant_id = parseNumber(nextQuery.merchant_id, -1)
  query.auth_state = parseNumber(nextQuery.auth_state, -1)
  query.goods_type_id = parseNumber(nextQuery.goods_type_id, -1)
  query.order_status = parseNumber(nextQuery.order_status, -1)
  query.pay_status = parseNumber(nextQuery.pay_status, -1)
  query.source = parseNumber(nextQuery.source, -1)

  const startDate = typeof nextQuery.start_date === 'string' ? nextQuery.start_date : ''
  const endDate = typeof nextQuery.end_date === 'string' ? nextQuery.end_date : ''
  query.daterange = startDate && endDate ? [startDate, endDate] : []

  if (query.daterange.length === 2 || query.month) {
    query.quick_date = ''
  }
}

function restoreQueryFromRoute() {
  applyQueryParams(route.query)
  writePersistedParams(buildParams())
}

function buildRouteContextQuery() {
  const extra = {}
  if (routeFrom.value === 'dashboard') {
    extra.from = 'dashboard'
  }
  if (['alerts', 'takeover', 'ops'].includes(routeFocus.value)) {
    extra.focus = routeFocus.value
  }
  return extra
}

function buildParams() {
  const params = {
    quick_date: query.quick_date,
    granularity: query.granularity,
    merchant_id: query.merchant_id,
    auth_state: query.auth_state,
    expire_status: query.expire_status,
    goods_type_id: query.goods_type_id,
    order_status: query.order_status,
    pay_status: query.pay_status,
    source: query.source,
    amount_min: query.amount_min,
    amount_max: query.amount_max
  }

  if (query.daterange.length === 2) {
    params.start_date = query.daterange[0]
    params.end_date = query.daterange[1]
    params.quick_date = ''
  } else if (query.month) {
    params.month = query.month
    params.quick_date = ''
  }

  return Object.fromEntries(
    Object.entries(params).filter(([, value]) => value !== '' && value !== -1 && value !== null && value !== undefined)
  )
}

async function syncRoute(params) {
  const nextQuery = stringifyQuery({
    ...params,
    ...buildRouteContextQuery()
  })
  const currentQuery = Object.fromEntries(
    Object.entries(route.query).map(([key, value]) => [key, Array.isArray(value) ? String(value[0] ?? '') : String(value)])
  )
  const unchanged =
    JSON.stringify(Object.entries(currentQuery).sort(([a], [b]) => a.localeCompare(b))) ===
    JSON.stringify(Object.entries(nextQuery).sort(([a], [b]) => a.localeCompare(b)))

  if (unchanged) {
    ignoreRouteWatch.value = false
    return
  }

  ignoreRouteWatch.value = true
  writePersistedParams(params)
  await router.push({
    name: route.name,
    query: nextQuery
  })
}

async function reloadAll() {
  loading.value = true
  try {
    const params = buildParams()
    const [summaryRes, trendRes, rankingRes, alertsRes] = await Promise.all([
      summary(params),
      trend(params),
      ranking(params),
      alerts(params)
    ])
    summaryData.value = summaryRes.data || {}
    trendData.value = trendRes.data || {}
    rankingData.value = rankingRes.data || {}
    alertsData.value = alertsRes.data || {}
    await nextTick()
    renderCharts()
  } catch (error) {
    ElMessage.error(error?.message || '加载数据中心失败')
  } finally {
    loading.value = false
  }
}

async function applyFilters() {
  if (query.daterange.length === 2) {
    query.quick_date = ''
    query.month = ''
  } else if (query.month) {
    query.quick_date = ''
    query.daterange = []
  }
  const params = buildParams()
  await syncRoute(params)
  await reloadAll()
}

async function resetQuery() {
  Object.assign(query, defaultQuery())
  await syncRoute(buildParams())
  await reloadAll()
}

async function applyQuickRange(value) {
  query.quick_date = value
  query.daterange = []
  query.month = ''
  await applyFilters()
}

async function clearDashboardContext() {
  ignoreRouteWatch.value = true
  await router.replace({
    name: route.name,
    query: stringifyQuery(buildParams())
  })
}

function handleDashboardContextPrimary() {
  if (routeFocus.value === 'takeover') {
    goToInternalTakeover()
    return
  }
  if (routeFocus.value === 'ops') {
    router.push({
      path: '/system/user-log',
      query: {
        from: 'platform-analytics',
        focus: 'ops'
      }
    })
    return
  }
  const target = document.querySelector('.alert-overview')
  if (target) {
    target.scrollIntoView({ behavior: 'smooth', block: 'start' })
    return
  }
  ElMessage.info('继续往下看预警卡片和异常列表。')
}

async function openMerchantDetail(row) {
  if (!row?.merchant_id) {
    ElMessage.warning('当前项没有可查看的商家')
    return
  }

  detailVisible.value = true
  detailLoading.value = true
  currentDetailMerchantId.value = Number(row.merchant_id || 0)
  try {
    const res = await merchantDetail({
      ...buildParams(),
      merchant_id: row.merchant_id
    })
    merchantDetailData.value = res.data || null
  } catch (error) {
    ElMessage.error(error?.message || '加载商家分析失败')
  } finally {
    detailLoading.value = false
  }
}

function goToGoodsList(row) {
  const nextQuery = { from: 'platform-analytics' }
  if (row?.goods_id) {
    nextQuery.search_field = 'goods_id'
    nextQuery.search_exp = '='
    nextQuery.search_value = String(row.goods_id)
  } else if (row?.title) {
    nextQuery.search_field = 'title'
    nextQuery.search_exp = 'like'
    nextQuery.search_value = String(row.title)
  }
  router.push({
    path: '/goods/goods',
    query: nextQuery
  })
}

function goToMemberStatistic() {
  const nextQuery = {
    from: 'platform-analytics',
    type: summaryGranularityLabel.value === '按月' ? 'month' : 'day'
  }
  const range = summaryData.value.range || {}
  if (range.start && range.end) {
    nextQuery.start = range.start
    nextQuery.end = range.end
  }
  router.push({
    path: '/member/statistic',
    query: nextQuery
  })
}

function goToMerchantList(row) {
  const nextQuery = { from: 'platform-analytics' }
  const merchantId = Number(row?.merchant_id || query.merchant_id || 0)
  if (merchantId > 0) {
    nextQuery.id = String(merchantId)
    nextQuery.focus_mode = 'detail'
  }
  router.push({
    path: '/merchant/merchant',
    query: nextQuery
  })
}

function goToOrderList(row) {
  const nextQuery = { from: 'platform-analytics' }
  const merchantId = Number(row?.merchant_id || query.merchant_id || 0)
  if (merchantId > 0) {
    nextQuery.merchant_id = String(merchantId)
  }
  router.push({
    path: '/order/order',
    query: nextQuery
  })
}

function goToExportCenter() {
  const params = buildParams()
  router.push({
    path: '/exports',
    query: {
      ...Object.fromEntries(
        Object.entries(params).map(([key, value]) => [key, Array.isArray(value) ? value.join(',') : String(value)])
      ),
      from: 'platform-analytics'
    }
  })
}

function buildInternalTakeoverQuery(merchantId = 0) {
  const params = buildParams()
  const nextQuery = {
    from: 'platform-analytics'
  }

  if (params.quick_date) {
    nextQuery.quick_date = String(params.quick_date)
  }
  if (params.start_date && params.end_date) {
    nextQuery.start_date = String(params.start_date)
    nextQuery.end_date = String(params.end_date)
  }

  const sourceMerchantId = Number(merchantId || query.merchant_id || 0)
  if (sourceMerchantId > 0) {
    nextQuery.source_merchant_id = String(sourceMerchantId)
  }

  return nextQuery
}

function goToInternalTakeover(merchantId = 0) {
  router.push({
    path: '/report/internal-takeover',
    query: buildInternalTakeoverQuery(merchantId)
  })
}

function goToFocusedMerchant() {
  if (!currentDetailMerchantId.value) {
    ElMessage.warning('当前没有可继续处理的商家')
    return
  }
  goToMerchantList({ merchant_id: currentDetailMerchantId.value })
}

function goToInternalTakeoverForMerchant() {
  if (!currentDetailMerchantId.value) {
    ElMessage.warning('当前没有可下钻的商家')
    return
  }
  goToInternalTakeover(currentDetailMerchantId.value)
}

function goToExportCenterForMerchant() {
  if (currentDetailMerchantId.value > 0) {
    query.merchant_id = currentDetailMerchantId.value
  }
  goToExportCenter()
}

function ensureChart(target, chart) {
  if (!target?.value) return null
  if (chart && !chart.isDisposed()) return chart
  return echarts.init(target.value)
}

function renderCharts() {
  const tradeList = trendData.value.trade_trend || []
  const refundList = trendData.value.refund_trend || []
  const merchantList = trendData.value.merchant_growth_trend || []
  const renewList = trendData.value.renew_trend || []

  tradeChart = ensureChart(tradeChartRef, tradeChart)
  refundChart = ensureChart(refundChartRef, refundChart)
  merchantChart = ensureChart(merchantChartRef, merchantChart)
  renewChart = ensureChart(renewChartRef, renewChart)

  tradeChart?.setOption(buildChartOption({
    labels: tradeList.map((item) => item.label),
    legends: ['成交额', '支付订单数'],
    series: [
      { name: '成交额', type: 'line', smooth: true, data: tradeList.map((item) => Number(item.amount || 0)) },
      { name: '支付订单数', type: 'bar', barMaxWidth: 18, data: tradeList.map((item) => Number(item.order_count || 0)) }
    ]
  }))

  refundChart?.setOption(buildChartOption({
    labels: refundList.map((item) => item.label),
    legends: ['退款金额', '退款订单数'],
    series: [
      { name: '退款金额', type: 'line', smooth: true, data: refundList.map((item) => Number(item.refund_amount || 0)) },
      { name: '退款订单数', type: 'bar', barMaxWidth: 18, data: refundList.map((item) => Number(item.refund_count || 0)) }
    ]
  }))

  merchantChart?.setOption(buildChartOption({
    labels: merchantList.map((item) => item.label),
    legends: ['新增商家', '累计商家'],
    series: [
      { name: '新增商家', type: 'bar', barMaxWidth: 18, data: merchantList.map((item) => Number(item.new_merchant_count || 0)) },
      { name: '累计商家', type: 'line', smooth: true, data: merchantList.map((item) => Number(item.cumulative_merchant_count || 0)) }
    ]
  }))

  renewChart?.setOption(buildChartOption({
    labels: renewList.map((item) => item.label),
    legends: ['续费金额', '续费次数'],
    series: [
      { name: '续费金额', type: 'line', smooth: true, data: renewList.map((item) => Number(item.renew_amount || 0)) },
      { name: '续费次数', type: 'bar', barMaxWidth: 18, data: renewList.map((item) => Number(item.renew_count || 0)) }
    ]
  }))
}

function buildChartOption({ labels = [], legends = [], series = [] }) {
  if (!labels.length) {
    return {
      graphic: {
        type: 'text',
        left: 'center',
        top: 'middle',
        style: {
          text: '暂无趋势数据',
          fill: '#94a3b8',
          fontSize: 14
        }
      }
    }
  }

  return {
    color: ['#2563eb', '#f97316'],
    tooltip: {
      trigger: 'axis',
      backgroundColor: 'rgba(15, 23, 42, 0.92)',
      borderWidth: 0,
      textStyle: { color: '#f8fafc' }
    },
    legend: { data: legends, top: 0 },
    grid: { left: 36, right: 18, top: 52, bottom: 28, containLabel: true },
    xAxis: {
      type: 'category',
      data: labels,
      axisLine: { lineStyle: { color: '#cbd5e1' } },
      axisLabel: { color: '#64748b' }
    },
    yAxis: {
      type: 'value',
      axisLabel: { color: '#64748b' },
      splitLine: { lineStyle: { color: 'rgba(148, 163, 184, 0.18)' } }
    },
    series: series.map((item) => {
      if (item.type === 'line') {
        return {
          ...item,
          symbol: 'circle',
          symbolSize: 6,
          lineStyle: { width: 3 },
          areaStyle: { opacity: 0.08 }
        }
      }
      return {
        ...item,
        itemStyle: {
          borderRadius: [8, 8, 0, 0]
        }
      }
    })
  }
}

function resizeCharts() {
  tradeChart?.resize()
  refundChart?.resize()
  merchantChart?.resize()
  renewChart?.resize()
}

function disposeCharts() {
  tradeChart?.dispose()
  refundChart?.dispose()
  merchantChart?.dispose()
  renewChart?.dispose()
}

function formatCurrency(value) {
  return `￥${Number(value || 0).toFixed(2)}`
}

function formatCount(value) {
  return Number(value || 0).toLocaleString('zh-CN')
}

function alertTagType(level) {
  if (level === 'danger') return 'danger'
  if (level === 'warning') return 'warning'
  return 'info'
}

function alertLevelLabel(level) {
  if (level === 'danger') return '高风险'
  if (level === 'warning') return '需跟进'
  return '提示'
}
</script>

<style lang="scss" scoped>
.report-page {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.panel {
  border: none;
  border-radius: 22px;
  box-shadow: 0 18px 45px rgba(15, 23, 42, 0.06);
}

.panel--hero {
  background:
    radial-gradient(circle at right top, rgba(37, 99, 235, 0.12), transparent 22%),
    linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
}

.panel__header {
  display: flex;
  justify-content: space-between;
  gap: 16px;
}

.panel__header-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.panel__title {
  font-size: 22px;
  font-weight: 700;
  color: #0f172a;
}

.panel__desc,
.panel__sub-desc {
  margin-top: 6px;
  font-size: 13px;
  color: #64748b;
}

.panel__actions {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 10px;
}

.panel__sub-title {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
}

.quick-range {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 18px;
}

.quick-range__item {
  padding: 10px 14px;
  color: #334155;
  cursor: pointer;
  background: #f8fafc;
  border: 1px solid #dbe4f0;
  border-radius: 999px;
  transition: all 0.2s ease;
}

.quick-range__item--active,
.quick-range__item:hover {
  color: #1d4ed8;
  background: #eff6ff;
  border-color: rgba(37, 99, 235, 0.32);
}

.filter-form {
  margin-top: 18px;
}

.amount-range {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 280px;
}

.current-params {
  margin-top: 18px;
  padding: 16px 18px;
  background: rgba(248, 250, 252, 0.88);
  border: 1px solid rgba(226, 232, 240, 0.9);
  border-radius: 18px;
}

.current-params__title {
  font-size: 13px;
  font-weight: 600;
  color: #475569;
}

.current-params__tags {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 12px;
}

.plain-guide {
  margin-top: 16px;
  padding: 18px;
  background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
  border: 1px solid rgba(191, 219, 254, 0.9);
  border-radius: 18px;
}

.plain-guide__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.plain-guide__title {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
}

.plain-guide__desc {
  margin-top: 6px;
  font-size: 13px;
  line-height: 1.7;
  color: #64748b;
}

.plain-guide__grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 12px;
  margin-top: 14px;
}

.plain-guide-card {
  padding: 14px 16px;
  background: #fff;
  border: 1px solid rgba(226, 232, 240, 0.92);
  border-radius: 16px;
}

.plain-guide-card__title {
  font-size: 14px;
  font-weight: 700;
  color: #0f172a;
}

.plain-guide-card__desc {
  margin-top: 8px;
  font-size: 13px;
  line-height: 1.7;
  color: #475569;
}

.plain-guide-card__action {
  margin-top: 10px;
  font-size: 12px;
  color: #1d4ed8;
}

.summary-banner {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 14px;
}

.action-strip {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 14px;
}

.action-strip__card {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 6px;
  min-height: 108px;
  padding: 14px 16px;
  border-radius: 18px;
  border: 1px solid rgba(226, 232, 240, 0.9);
  background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%);
  box-shadow: 0 10px 25px rgba(15, 23, 42, 0.04);
  text-align: left;
  transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}

.action-strip__card:hover {
  transform: translateY(-1px);
  border-color: rgba(37, 99, 235, 0.25);
  box-shadow: 0 16px 32px rgba(37, 99, 235, 0.08);
}

.action-strip__title {
  font-size: 14px;
  font-weight: 700;
  color: #0f172a;
}

.action-strip__desc {
  font-size: 12px;
  line-height: 1.65;
  color: #64748b;
}

.summary-banner__item {
  padding: 16px 18px;
  background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
  border: 1px solid rgba(226, 232, 240, 0.9);
  border-radius: 18px;
  box-shadow: 0 10px 25px rgba(15, 23, 42, 0.04);
}

.summary-banner__label {
  display: block;
  margin-bottom: 8px;
  font-size: 13px;
  color: #64748b;
}

.summary-banner strong {
  font-size: 18px;
  color: #0f172a;
}

.metrics-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 14px;
}

.metric-card {
  border: none;
  border-radius: 18px;
  box-shadow: 0 14px 36px rgba(15, 23, 42, 0.05);
}

.metric-card__label {
  font-size: 13px;
  color: #64748b;
}

.metric-card__value {
  margin-top: 10px;
  font-size: 26px;
  font-weight: 700;
  color: #0f172a;
}

.metric-card__meta {
  margin-top: 8px;
  font-size: 12px;
  color: #94a3b8;
}

.dashboard-context-banner {
  display: flex;
  justify-content: space-between;
  gap: 14px;
  margin-bottom: 16px;
  padding: 14px 16px;
  border: 1px solid rgba(251, 191, 36, 0.35);
  border-radius: 16px;
  background: linear-gradient(180deg, #fffdf5 0%, #ffffff 100%);
}

.dashboard-context-banner__title {
  font-size: 14px;
  font-weight: 700;
  color: #92400e;
}

.dashboard-context-banner__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #6b7280;
}

.dashboard-context-banner__actions {
  display: flex;
  align-items: flex-start;
  flex-wrap: wrap;
  gap: 4px;
}

.metric-card--blue { background: linear-gradient(180deg, #ffffff 0%, #eff6ff 100%); }
.metric-card--cyan { background: linear-gradient(180deg, #ffffff 0%, #ecfeff 100%); }
.metric-card--sky { background: linear-gradient(180deg, #ffffff 0%, #f0f9ff 100%); }
.metric-card--indigo { background: linear-gradient(180deg, #ffffff 0%, #eef2ff 100%); }
.metric-card--orange { background: linear-gradient(180deg, #ffffff 0%, #fff7ed 100%); }
.metric-card--amber { background: linear-gradient(180deg, #ffffff 0%, #fffbeb 100%); }
.metric-card--green { background: linear-gradient(180deg, #ffffff 0%, #f0fdf4 100%); }
.metric-card--emerald { background: linear-gradient(180deg, #ffffff 0%, #ecfdf5 100%); }
.metric-card--yellow { background: linear-gradient(180deg, #ffffff 0%, #fefce8 100%); }
.metric-card--red { background: linear-gradient(180deg, #ffffff 0%, #fef2f2 100%); }
.metric-card--teal { background: linear-gradient(180deg, #ffffff 0%, #f0fdfa 100%); }
.metric-card--slate { background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%); }

.chart-grid,
.table-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 18px;
}

.highlight-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-bottom: 16px;
}

.highlight-card {
  padding: 16px;
  text-align: left;
  cursor: pointer;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 18px;
  transition: all 0.2s ease;
}

.highlight-card:hover {
  transform: translateY(-1px);
  box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
}

.highlight-card--goods {
  background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
}

.highlight-card--merchant {
  background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
}

.highlight-card__rank {
  font-size: 12px;
  font-weight: 700;
  color: #2563eb;
}

.highlight-card__title {
  margin-top: 8px;
  font-size: 15px;
  font-weight: 700;
  color: #0f172a;
}

.highlight-card__meta {
  margin-top: 8px;
  font-size: 12px;
  color: #64748b;
}

.highlight-card__value {
  margin-top: 14px;
  font-size: 22px;
  font-weight: 700;
  color: #0f172a;
}

.alert-overview {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-bottom: 16px;
}

.alert-card {
  padding: 16px;
  border-radius: 18px;
  border: 1px solid #e2e8f0;
}

.alert-card--danger {
  background: linear-gradient(180deg, #ffffff 0%, #fef2f2 100%);
  border-color: rgba(248, 113, 113, 0.35);
}

.alert-card--warning {
  background: linear-gradient(180deg, #ffffff 0%, #fffbeb 100%);
  border-color: rgba(251, 191, 36, 0.35);
}

.alert-card--info {
  background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
}

.alert-card__level {
  font-size: 12px;
  font-weight: 700;
  color: #b45309;
}

.alert-card__title {
  margin-top: 8px;
  font-size: 15px;
  font-weight: 700;
  color: #0f172a;
}

.alert-card__desc {
  margin-top: 8px;
  min-height: 40px;
  font-size: 13px;
  line-height: 1.6;
  color: #475569;
}

.alert-card__footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  margin-top: 14px;
  font-size: 12px;
  color: #64748b;
}

.chart {
  width: 100%;
  height: 320px;
}

.detail-wrap {
  min-height: 240px;
}

.detail-metrics {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 14px;
  margin-top: 18px;
}

.detail-followup {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 16px;
}

.detail-metric {
  padding: 16px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
}

.detail-metric__label {
  font-size: 13px;
  color: #64748b;
}

.detail-metric__value {
  margin-top: 8px;
  font-size: 24px;
  font-weight: 700;
  color: #0f172a;
}

@media (max-width: 1100px) {
  .plain-guide__grid,
  .summary-banner,
  .action-strip,
  .chart-grid,
  .table-grid,
  .highlight-grid,
  .alert-overview,
  .detail-metrics {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 900px) {
  .dashboard-context-banner,
  .panel__header {
    flex-direction: column;
  }

  .plain-guide__header {
    flex-direction: column;
  }
}
</style>
