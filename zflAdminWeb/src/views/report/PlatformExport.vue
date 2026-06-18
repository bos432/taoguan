<template>
  <div v-loading="loading" class="report-page">
    <el-card class="panel panel--hero" shadow="never">
      <div class="panel__header">
        <div>
          <div class="panel__title">导出中心</div>
          <div class="panel__desc">
            往 `admin-next` 的成品交互靠拢，先设置筛选条件，再按模块导出 CSV；所有导出均为只读操作。
          </div>
        </div>
        <div class="panel__actions">
          <el-tag effect="plain">{{ operatorLabel }}</el-tag>
          <el-tag effect="plain" type="warning">CSV 实时导出</el-tag>
          <el-button :loading="loading" type="primary" @click="reloadPreview">刷新预览</el-button>
        </div>
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
        <el-form-item label="时间区间">
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
            placeholder="选择月份"
            value-format="YYYY-MM"
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
        <div class="current-params__title">当前导出条件</div>
        <div class="current-params__tags">
          <el-tag
            v-for="item in activeFilterTags"
            :key="item"
            effect="plain"
          >
            {{ item }}
          </el-tag>
          <el-tag v-if="!activeFilterTags.length" effect="plain" type="info">
            默认参数：近 7 天 / 全部商家
          </el-tag>
        </div>
      </div>
      <div class="plain-guide">
        <div class="plain-guide__header">
          <div>
            <div class="plain-guide__title">这页建议先这样导</div>
            <div class="plain-guide__desc">
              先判断当前范围是不是已经够小，再决定导统计、导订单、导商家还是先跳去别的页面复核，不要一上来全点一遍。
            </div>
          </div>
          <el-tag effect="plain" type="primary">{{ exportFocusLabel }}</el-tag>
        </div>
        <div class="plain-guide__grid">
          <div v-for="item in exportGuideCards" :key="item.title" class="plain-guide-card">
            <div class="plain-guide-card__title">{{ item.title }}</div>
            <div class="plain-guide-card__desc">{{ item.desc }}</div>
            <div class="plain-guide-card__action">{{ item.action }}</div>
          </div>
        </div>
      </div>
    </el-card>

    <div class="summary-banner">
      <div class="summary-banner__item">
        <span class="summary-banner__label">预览范围</span>
        <strong>{{ previewRangeLabel }}</strong>
      </div>
      <div class="summary-banner__item">
        <span class="summary-banner__label">预警数量</span>
        <strong>{{ formatCount((alertsData.list || []).length) }}</strong>
      </div>
      <div class="summary-banner__item">
        <span class="summary-banner__label">最近导出</span>
        <strong>{{ lastExportLabel }}</strong>
      </div>
      <div class="summary-banner__item">
        <span class="summary-banner__label">当前操作人</span>
        <strong>{{ operatorLabel }}</strong>
      </div>
    </div>

    <div class="metrics-grid">
      <el-card
        v-for="item in previewCards"
        :key="item.label"
        class="metric-card"
        shadow="never"
      >
        <div class="metric-card__label">{{ item.label }}</div>
        <div class="metric-card__value">{{ item.value }}</div>
        <div class="metric-card__meta">{{ item.meta }}</div>
      </el-card>
    </div>

    <div class="workflow-grid">
      <el-card class="panel workflow-card" shadow="never">
        <template #header>
          <div class="panel__header-bar">
            <div>
              <div class="panel__sub-title">今天先导什么</div>
              <div class="panel__sub-desc">先判断优先级，再决定导出顺序，避免运营一上来全量下载。</div>
            </div>
          </div>
        </template>

        <div class="workflow-list">
          <div
            v-for="item in workflowRecommendations"
            :key="item.title"
            class="workflow-item"
            :class="`workflow-item--${item.tone}`"
          >
            <div class="workflow-item__head">
              <div class="workflow-item__title">{{ item.title }}</div>
              <el-tag :type="item.tagType" effect="plain">{{ item.badge }}</el-tag>
            </div>
            <div class="workflow-item__desc">{{ item.desc }}</div>
            <div class="workflow-item__tips">
              <span v-for="tip in item.tips" :key="tip">{{ tip }}</span>
            </div>
          </div>
        </div>
      </el-card>

      <el-card class="panel workflow-card" shadow="never">
        <template #header>
          <div class="panel__header-bar">
            <div>
              <div class="panel__sub-title">报表联动入口</div>
              <div class="panel__sub-desc">当前条件不合适继续导出时，可以直接跳到更适合处理的页面。</div>
            </div>
          </div>
        </template>

        <div class="jump-actions">
          <button type="button" class="jump-action" @click="goToAnalytics">
            <strong>先看平台数据中心</strong>
            <span>把当前筛选条件带过去，先看趋势、排行和异常预警。</span>
          </button>
          <button type="button" class="jump-action" @click="goToInternalTakeover">
            <strong>追内部接盘异常</strong>
            <span>当前有异常预警或商家筛选时，转去内部接盘对账继续核查。</span>
          </button>
          <button type="button" class="jump-action" @click="goToInternalMerchant">
            <strong>看内部号承接能力</strong>
            <span>需要判断内部号是不是还能接货，就去内部商家配置页。</span>
          </button>
        </div>

        <div class="jump-hint">
          <div class="jump-hint__label">当前联动提示</div>
          <div class="jump-hint__value">{{ routeHint }}</div>
        </div>
      </el-card>
    </div>

    <el-card class="panel followup-panel" shadow="never">
      <div class="panel__header-bar">
        <div>
          <div class="panel__sub-title">导完后去哪验</div>
          <div class="panel__sub-desc">导出只是拿到表，真正复核还要回业务页看趋势、订单、商家或内部号承接。</div>
        </div>
        <el-tag effect="plain" type="warning">{{ exportFocusLabel }}</el-tag>
      </div>

      <div class="followup-grid">
        <div v-for="item in exportFollowupCards" :key="item.title" class="followup-card">
          <div class="followup-card__title">{{ item.title }}</div>
          <div class="followup-card__desc">{{ item.desc }}</div>
          <div class="followup-card__action">
            <el-button link type="primary" @click="item.action()">{{ item.actionText }}</el-button>
          </div>
        </div>
      </div>
    </el-card>

    <div class="export-grid">
      <el-card
        v-for="item in exportActions"
        :key="item.key"
        class="export-card"
        :class="{ 'export-card--disabled': !item.enabled }"
        shadow="never"
      >
        <div class="export-card__head">
          <div>
            <div class="export-card__title">{{ item.title }}</div>
            <div class="export-card__desc">{{ item.desc }}</div>
          </div>
          <el-tag :type="item.enabled ? item.tone : 'info'" effect="plain">
            {{ item.enabled ? item.badge : '无权限' }}
          </el-tag>
        </div>

        <div class="export-card__tips">
          <span v-for="tip in item.tips" :key="tip">{{ tip }}</span>
        </div>

        <div class="export-card__status">
          <span>{{ item.enabled ? '当前账号可直接导出' : '当前账号未开放此导出模块' }}</span>
          <strong>{{ item.permissionLabel }}</strong>
        </div>

        <div class="export-card__footer">
          <el-button
            :loading="exportingKey === item.key"
            :disabled="!item.enabled"
            type="primary"
            @click="runExport(item)"
          >
            {{ exportingKey === item.key ? '导出中...' : (item.enabled ? '立即导出' : '暂无权限') }}
          </el-button>
        </div>
      </el-card>
    </div>

    <div class="table-grid">
      <el-card class="panel" shadow="never">
        <template #header>
          <div class="panel__header-bar">
            <div>
              <div class="panel__sub-title">热销商品预览</div>
              <div class="panel__sub-desc">导出前快速确认当前筛选是否正确</div>
            </div>
          </div>
        </template>
        <el-table :data="rankingData.top_goods || []" empty-text="当前条件下暂无预览数据" row-key="goods_id">
          <el-table-column type="index" label="#" width="60" />
          <el-table-column prop="goods_id" label="商品ID" width="96" />
          <el-table-column prop="title" label="商品名称" min-width="220" />
          <el-table-column label="销量" width="120">
            <template #default="{ row }">{{ formatCount(row.sale_num) }}</template>
          </el-table-column>
          <el-table-column label="成交额" width="130">
            <template #default="{ row }">{{ formatCurrency(row.sale_amount) }}</template>
          </el-table-column>
          <el-table-column label="操作" min-width="180" fixed="right">
            <template #default="{ row }">
              <div class="inline-actions">
                <el-button link type="primary" @click="goToGoods(row)">去商品页</el-button>
                <el-button v-if="row.merchant_id" link @click="goToMerchant(row.merchant_id)">去商家页</el-button>
              </div>
            </template>
          </el-table-column>
        </el-table>
      </el-card>

      <el-card class="panel" shadow="never">
        <template #header>
          <div class="panel__header-bar">
            <div>
              <div class="panel__sub-title">异常预警预览</div>
              <div class="panel__sub-desc">导出前先看一眼风险商家，能少走一轮无效导出。</div>
            </div>
            <el-button link type="primary" @click="goToAnalytics">去数据中心看全量</el-button>
          </div>
        </template>
        <div v-if="topAlertRows.length" class="alert-list">
          <div
            v-for="item in topAlertRows"
            :key="`${item.merchant_id}-${item.message}`"
            class="alert-item"
          >
            <div class="alert-item__head">
              <strong>{{ item.merchant_title || `商家 #${item.merchant_id}` }}</strong>
              <el-tag :type="resolveAlertTagType(item.level)" effect="plain">{{ item.level || '提醒' }}</el-tag>
            </div>
            <div class="alert-item__desc">{{ item.message || '暂无异常说明' }}</div>
            <div class="alert-item__meta">指标值：{{ item.value || '--' }}</div>
            <div class="inline-actions inline-actions--mt">
              <el-button v-if="item.merchant_id" link type="primary" @click="goToMerchant(item.merchant_id)">
                去商家页
              </el-button>
              <el-button v-if="item.merchant_id" link @click="goToMerchantAnalytics(item.merchant_id)">
                去数据中心复盘
              </el-button>
              <el-button v-if="item.merchant_id" link @click="goToAlertTakeover(item)">
                去内部接盘对账
              </el-button>
            </div>
          </div>
        </div>
        <el-empty v-else description="当前筛选范围内没有异常预警" />
      </el-card>

      <el-card class="panel" shadow="never">
        <template #header>
          <div class="panel__header-bar">
            <div>
              <div class="panel__sub-title">最近导出记录</div>
              <div class="panel__sub-desc">当前浏览器会话内记录最近操作</div>
            </div>
            <el-button v-if="exportHistory.length" link type="primary" @click="clearHistory">清空记录</el-button>
          </div>
        </template>
        <div v-if="exportHistory.length" class="history-list">
          <div
            v-for="item in exportHistory"
            :key="`${item.type}-${item.time}`"
            class="history-item"
          >
            <div class="history-item__content">
              <div class="history-item__title">{{ item.title }}</div>
              <div class="history-item__meta">{{ item.filename }}</div>
              <div v-if="item.filter_summary?.length" class="history-item__filters">
                {{ item.filter_summary.join(' / ') }}
              </div>
            </div>
            <div class="history-item__side">
              <div class="history-item__time">{{ item.time }}</div>
              <div class="inline-actions inline-actions--compact">
                <el-button link type="primary" @click="reuseHistoryFilters(item)">按原条件复盘</el-button>
                <el-button link @click="rerunHistoryExport(item)">再导一次</el-button>
              </div>
            </div>
          </div>
        </div>
        <el-empty v-else description="还没有导出记录" />
      </el-card>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { alerts, filters, ranking, summary } from '@/api/report/platform-analytics'
import {
  downloadAnalytics,
  downloadMerchants,
  downloadOrders,
  downloadRenewRecords
} from '@/api/report/platform-export'
import { useUserStoreHook } from '@/store/modules/user'

const route = useRoute()
const router = useRouter()
const userStore = useUserStoreHook()
const loading = ref(false)
const exportingKey = ref('')
const filterData = ref({})
const summaryData = ref({})
const rankingData = ref({})
const alertsData = ref({})
const exportHistory = ref([])
const exportHistoryKey = 'platform-export-history'
const queryStorageKey = 'platform-export-query'
const ignoreRouteWatch = ref(false)

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
const goodsTypeOptions = computed(() => flattenGoodsTypes(filterData.value.goods_types || []))
const orderStatuses = computed(() => filterData.value.order_statuses || [])
const payStatuses = computed(() => filterData.value.pay_statuses || [])
const sourceOptions = computed(() => filterData.value.sources || [])
const granularityOptions = computed(() => filterData.value.granularity_options || [])
const operatorLabel = computed(() => {
  return userStore.user.nickname || userStore.user.username || '当前管理员'
})
const previewRangeLabel = computed(() => summaryData.value.range?.label || '近 7 天')
const lastExportLabel = computed(() => exportHistory.value[0]?.title || '暂无记录')
const permissionSignatures = computed(() => collectPermissionSignatures(userStore.user.menus || []))
const topAlertRows = computed(() => (alertsData.value.list || []).slice(0, 5))
const entrySourceLabel = computed(() => {
  const source = route.query.from
  if (source === 'platform-analytics') return '来自平台分析'
  if (source === 'internal-takeover') return '来自内部接盘对账'
  if (source === 'merchant-list') return '来自商家列表'
  if (source === 'order-list') return '来自订单列表'
  if (source === 'dashboard') return '来自后台首页'
  return ''
})
const entryContextVisible = computed(() => Boolean(entrySourceLabel.value))
const entryContextTitle = computed(() => {
  if (entrySourceLabel.value === '来自平台分析') return '当前从平台分析进入导出中心'
  if (entrySourceLabel.value === '来自内部接盘对账') return '当前从内部接盘对账进入导出中心'
  if (entrySourceLabel.value === '来自商家列表') return '当前从商家列表进入导出中心'
  if (entrySourceLabel.value === '来自订单列表') return '当前从订单列表进入导出中心'
  if (entrySourceLabel.value === '来自后台首页') return '当前从后台首页进入导出中心'
  return '当前为外部入口承接视角'
})
const entryContextDesc = computed(() => {
  if (entrySourceLabel.value === '来自平台分析') {
    return '这类进入通常是为了把趋势和预警导成可分发报表。建议先确认当前时间和商家范围，再决定导统计还是导明细。'
  }
  if (entrySourceLabel.value === '来自内部接盘对账') {
    return '这类进入通常是为了把接盘异常落成表格。建议先导订单或统计，再回内部接盘对账继续追异常单。'
  }
  if (entrySourceLabel.value === '来自商家列表') {
    return '这类进入通常是为了把某批商家的状态、续费或订单导出来。建议先确认是否已锁定单商家，再选择细表。'
  }
  if (entrySourceLabel.value === '来自订单列表') {
    return '这类进入通常是为了把订单筛选结果导走复核。建议先确认订单和支付状态，再决定导订单还是回订单页继续缩范围。'
  }
  return '这类进入通常是首页巡检后的继续下钻。建议先看预警和当前范围，再决定导统计、导商家还是先跳业务页。'
})
const entryContextPrimaryLabel = computed(() => {
  if (entrySourceLabel.value === '来自平台分析') return '回平台分析'
  if (entrySourceLabel.value === '来自内部接盘对账') return '回内部接盘对账'
  if (entrySourceLabel.value === '来自商家列表') return '回商家列表'
  if (entrySourceLabel.value === '来自订单列表') return '回订单列表'
  return '回后台首页'
})
const routeHint = computed(() => {
  if ((alertsData.value.list || []).length > 0) {
    return '当前范围内已有异常预警，建议先去平台数据中心确认风险，再决定是否导出明细。'
  }
  if (Number(query.merchant_id || -1) > 0) {
    return '你已经缩小到单商家范围，适合先看趋势与续费，再按需导出订单或商家数据。'
  }
  return '当前更像通用巡检范围，适合先看概览，再按订单、商家、续费三类分开导出。'
})

const previewCards = computed(() => {
  const cards = summaryData.value.cards || {}
  return [
    { label: '支付订单数', value: formatCount(cards.paid_order_count), meta: '订单明细导出参考' },
    { label: '支付买家数', value: formatCount(cards.paid_buyer_count), meta: '用户规模参考' },
    { label: '活跃商家', value: formatCount(cards.active_merchant_count), meta: '商家导出范围参考' },
    { label: 'GMV', value: formatCurrency(cards.gmv), meta: '统计导出核心指标' }
  ]
})

const workflowRecommendations = computed(() => {
  const alertCount = (alertsData.value.list || []).length
  const goodsCount = (rankingData.value.top_goods || []).length
  const merchantSelected = Number(query.merchant_id || -1) > 0
  const hasHistory = exportHistory.value.length > 0

  return [
    {
      title: alertCount > 0 ? '先看风险，再导出订单明细' : '先导平台统计，再拆明细',
      badge: alertCount > 0 ? `${formatCount(alertCount)} 条预警` : '推荐第一步',
      tone: alertCount > 0 ? 'danger' : 'primary',
      tagType: alertCount > 0 ? 'danger' : 'primary',
      desc: alertCount > 0
        ? '当前范围内已经出现风险商家，直接全量导订单容易淹没重点，建议先去数据中心确认问题点。'
        : '当前没有明显异常，适合先导平台统计总表，再按订单、商家、续费补明细。',
      tips: alertCount > 0
        ? ['先看异常预警名单', '再定位问题商家', '最后导出订单或商家表']
        : ['先导统计 CSV', '再按需要导细表', '减少重复下载']
    },
    {
      title: merchantSelected ? '当前是单商家核查模式' : '当前是全局巡检模式',
      badge: merchantSelected ? '商家已锁定' : '范围较大',
      tone: merchantSelected ? 'success' : 'warning',
      tagType: merchantSelected ? 'success' : 'warning',
      desc: merchantSelected
        ? '更适合导商家、续费和该商家的订单明细，方便一次性把单商家问题看全。'
        : '更适合先看概览和热销榜，再决定是否继续细分商家、时间或金额范围。',
      tips: merchantSelected
        ? ['可直接带条件跳数据中心', '续费记录更适合一起导出', '单商家问题更容易复核']
        : ['先缩小时间范围', '必要时再锁定商家', '避免大范围导出导致页面等待']
    },
    {
      title: goodsCount > 0 ? '热销商品已可预览' : '当前预览商品较少',
      badge: hasHistory ? `最近已导出 ${formatCount(exportHistory.value.length)} 次` : '本次会话未导出',
      tone: goodsCount > 0 ? 'primary' : 'info',
      tagType: goodsCount > 0 ? 'primary' : 'info',
      desc: goodsCount > 0
        ? '热销商品预览已经能验证筛选口径，适合在确认无误后直接执行导出。'
        : '当前商品预览数据不多，建议先回查筛选条件或改到更合适的时间范围。',
      tips: goodsCount > 0
        ? ['先看预览是否符合预期', '确认后再点导出', '导完回看导出记录']
        : ['检查日期条件', '检查商家或分类条件', '必要时切回近 7 天']
    }
  ]
})

const activeFilterTags = computed(() => {
  const tags = []

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

const exportFocusLabel = computed(() => {
  if ((alertsData.value.list || []).length > 0) {
    return '先处理异常'
  }
  if (Number(query.merchant_id || -1) > 0) {
    return '先导单商家'
  }
  if (Number(query.pay_status || -1) !== -1 || Number(query.order_status || -1) !== -1) {
    return '先导订单明细'
  }
  if (query.amount_min || query.amount_max) {
    return '先核金额范围'
  }
  return '先导平台概览'
})

const exportGuideCards = computed(() => {
  const alertCount = (alertsData.value.list || []).length
  const merchantSelected = Number(query.merchant_id || -1) > 0
  const narrowedByStatus =
    Number(query.pay_status || -1) !== -1 || Number(query.order_status || -1) !== -1

  return [
    {
      title: '第一步先确认范围',
      desc: `当前预览范围是“${previewRangeLabel.value}”，先确认时间、商家和金额条件是不是你真正想导的那一批。`,
      action: activeFilterTags.value.length ? '范围已经收窄，可以继续挑导出类型。' : '如果还太大，先缩范围再导。'
    },
    {
      title: '第二步先看预警再下载',
      desc:
        alertCount > 0
          ? `当前有 ${formatCount(alertCount)} 条异常预警，直接全量导出通常会把重点冲散。`
          : '当前没有明显预警，适合先导平台统计，再按需要补订单、商家和续费明细。',
      action: alertCount > 0 ? '先去数据中心或异常预览确认问题商家。' : '没有预警时优先导总表。'
    },
    {
      title: '第三步按问题选报表',
      desc: merchantSelected
        ? '你已经锁定到单商家了，更适合导该商家的订单、商家资料和续费记录。'
        : narrowedByStatus
        ? '你已经带了订单或支付状态，当前更适合直接导订单明细，不用先导全量商家表。'
        : '如果还在全局巡检，建议先导统计或商家表，再决定是否补订单明细。',
      action: merchantSelected ? '单商家模式优先导细表。' : narrowedByStatus ? '订单状态筛选优先导订单。' : '全局巡检优先导总览。'
    },
    {
      title: '第四步导不动就先跳转',
      desc:
        alertCount > 0
          ? '当异常已经很明确时，继续下载不一定最快，直接跳去平台数据中心或内部接盘对账通常更高效。'
          : '如果你只是想判断趋势、续费或内部号承接能力，跳到对应页面会比盲目导表更快。',
      action: '导出中心负责产出表，定位问题优先去业务页。'
    }
  ]
})

const exportActions = computed(() => {
  return [
    {
      key: 'orders',
      title: '订单导出',
      desc: '导出当前筛选条件下的订单明细、支付状态和退款数据。',
      badge: '订单 CSV',
      tone: 'primary',
      permissionPrefix: 'admin/report.PlatformExport/orders',
      tips: ['适合财务对账', '保留支付与退款字段', '按当前筛选条件导出']
    },
    {
      key: 'merchants',
      title: '商家导出',
      desc: '导出商家状态、到期情况、商品数量和筛选后的交易汇总。',
      badge: '商家 CSV',
      tone: 'success',
      permissionPrefix: 'admin/report.PlatformExport/merchants',
      tips: ['适合运营巡检', '包含到期状态', '包含成交汇总']
    },
    {
      key: 'renewRecords',
      title: '续费记录导出',
      desc: '导出商家续费来源、时长、金额和到期变化。',
      badge: '续费 CSV',
      tone: 'warning',
      permissionPrefix: 'admin/report.PlatformExport/renewRecords',
      tips: ['适合续费核算', '包含前后到期时间', '包含备注与来源']
    },
    {
      key: 'analytics',
      title: '平台统计导出',
      desc: '导出当前筛选条件下的概览、趋势、排行和预警数据。',
      badge: '统计 CSV',
      tone: 'danger',
      permissionPrefix: 'admin/report.PlatformExport/analytics',
      tips: ['适合周报月报', '汇总趋势与榜单', '可直接发给管理层']
    }
  ].map((item) => {
    const enabled = hasExportPermission(item.permissionPrefix, item.key)
    return {
      ...item,
      enabled,
      permissionLabel: enabled ? '权限已开放' : '需后台授权'
    }
  })
})

const exportFollowupCards = computed(() => {
  const merchantSelected = Number(query.merchant_id || -1) > 0
  const alertCount = (alertsData.value.list || []).length

  return [
    {
      title: '统计导出后先回平台数据中心',
      desc: '适合看趋势、榜单和异常是否跟导出表一致，避免只看 CSV 不看页面口径。',
      actionText: '去平台数据中心',
      action: goToAnalytics
    },
    {
      title: merchantSelected ? '单商家导出后回商家页复核' : '商家导出后回商家列表抽样',
      desc: merchantSelected
        ? '当前已经锁定商家，导完后最适合回商家页核详情、状态和商品数量。'
        : '当你导的是商家表，建议回商家列表抽几家重点样本复核页面展示。',
      actionText: '去商家页',
      action: goToMerchantList
    },
    {
      title: '订单导出后回订单页看业务链路',
      desc: '适合把筛选条件带回订单列表，看支付、退款和状态回显是否和导出结果一致。',
      actionText: '去订单页',
      action: goToOrderList
    },
    {
      title: alertCount > 0 ? '异常导出后直接追内部接盘' : '内部号问题先去接盘对账页',
      desc: alertCount > 0
        ? '当前已经有异常预警，导完后直接转内部接盘对账更容易把问题单拎出来。'
        : '当你怀疑是内部号承接、待转商品或账单异常，不要只留在导出中心。' ,
      actionText: '去内部接盘对账',
      action: goToInternalTakeover
    }
  ]
})

onMounted(async () => {
  restoreHistory()
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
  await reloadPreview()
})

watch(
  () => route.fullPath,
  async () => {
    if (ignoreRouteWatch.value) {
      ignoreRouteWatch.value = false
      return
    }
    restoreQueryFromRoute()
    await reloadPreview()
  }
)

watch(
  exportHistory,
  (value) => {
    sessionStorage.setItem(exportHistoryKey, JSON.stringify(value))
  },
  { deep: true }
)

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
  const nextQuery = stringifyQuery(params)
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

async function reloadPreview() {
  loading.value = true
  try {
    const params = buildParams()
    const [summaryRes, rankingRes, alertsRes] = await Promise.all([
      summary(params),
      ranking(params),
      alerts(params)
    ])
    summaryData.value = summaryRes.data || {}
    rankingData.value = rankingRes.data || {}
    alertsData.value = alertsRes.data || {}
  } catch (error) {
    ElMessage.error(error?.message || '加载导出中心预览失败')
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
  await reloadPreview()
}

async function applyQuickRange(value) {
  query.quick_date = value
  query.daterange = []
  query.month = ''
  await applyFilters()
}

async function resetQuery() {
  Object.assign(query, defaultQuery())
  await syncRoute(buildParams())
  await reloadPreview()
}

async function runExport(item) {
  if (!item.enabled) {
    ElMessage.warning('当前账号没有这个导出模块的权限')
    return
  }
  exportingKey.value = item.key
  try {
    const params = buildParams()
    await syncRoute(params)

    let filename = ''
    if (item.key === 'orders') {
      filename = await downloadOrders(params)
    } else if (item.key === 'merchants') {
      filename = await downloadMerchants(params)
    } else if (item.key === 'renewRecords') {
      filename = await downloadRenewRecords(params)
    } else if (item.key === 'analytics') {
      filename = await downloadAnalytics(params)
    }

    exportHistory.value = [
      {
        type: item.key,
        title: item.title,
        filename,
        time: formatTime(new Date()),
        params,
        filter_summary: activeFilterTags.value.slice(0, 6)
      },
      ...exportHistory.value
    ].slice(0, 6)

    ElMessage.success(filename ? `导出已开始：${filename}` : '导出已开始')
    ElMessage.info(resolveExportFollowupMessage(item))
  } catch (error) {
    ElMessage.error(error?.message || '导出失败')
  } finally {
    exportingKey.value = ''
  }
}

function buildTakeoverQuery() {
  const params = buildParams()
  const next = {
    from: 'platform-export'
  }

  if (params.quick_date) next.quick_date = params.quick_date
  if (params.start_date && params.end_date) {
    next.start_date = params.start_date
    next.end_date = params.end_date
  }
  if (Number(params.merchant_id || -1) > 0) {
    next.source_merchant_id = params.merchant_id
  }

  return next
}

function buildRouteQuery(extraQuery = {}, nextFrom = 'platform-export') {
  return stringifyQuery({
    ...route.query,
    ...extraQuery,
    from: nextFrom
  })
}

function goToAnalytics() {
  router.push({
    path: '/analytics',
    query: buildRouteQuery({
      ...stringifyQuery(buildParams()),
    })
  })
}

function goToMerchant(merchantId) {
  const id = Number(merchantId || 0)
  if (!id) {
    ElMessage.warning('当前没有可定位的商家')
    return
  }
  router.push({
    path: '/merchant/merchant',
    query: buildRouteQuery({
      id: String(id),
      focus_mode: 'detail'
    })
  })
}

function goToGoods(row = {}) {
  const goodsId = Number(row.goods_id || 0)
  if (!goodsId) {
    ElMessage.warning('当前没有可定位的商品')
    return
  }
  router.push({
    path: '/goods/Goods',
    query: buildRouteQuery({
      search_field: 'goods_id',
      search_exp: '=',
      search_value: String(goodsId),
      merchant_id: row.merchant_id ? String(row.merchant_id) : undefined
    })
  })
}

function goToMerchantAnalytics(merchantId) {
  const id = Number(merchantId || 0)
  if (!id) {
    ElMessage.warning('当前没有可复盘的商家')
    return
  }
  router.push({
    path: '/analytics',
    query: buildRouteQuery({
      ...stringifyQuery(buildParams()),
      merchant_id: String(id)
    })
  })
}

function goToMerchantList() {
  const merchantId = Number(query.merchant_id || -1)
  router.push({
    path: '/merchant/merchant',
    query:
      merchantId > 0
        ? buildRouteQuery({
            id: String(merchantId),
            focus_mode: 'detail'
          })
        : buildRouteQuery({
            auth_state: Number(query.auth_state || -1) > -1 ? String(query.auth_state) : undefined
          })
  })
}

function goToOrderList() {
  router.push({
    path: '/order/order',
    query: buildRouteQuery({
      order_status: Number(query.order_status || -1) > -1 ? String(query.order_status) : undefined,
      pay_status: Number(query.pay_status || -1) > -1 ? String(query.pay_status) : undefined,
      merchant_id: Number(query.merchant_id || -1) > 0 ? String(query.merchant_id) : undefined
    })
  })
}

function goToInternalTakeover() {
  router.push({
    path: '/report/internal-takeover',
    query: stringifyQuery(buildTakeoverQuery())
  })
}

function goToAlertTakeover(item = {}) {
  const next = buildTakeoverQuery()
  const merchantId = Number(item.merchant_id || 0)
  if (merchantId > 0) {
    next.source_merchant_id = merchantId
  }
  router.push({
    path: '/report/internal-takeover',
    query: stringifyQuery(next)
  })
}

function goToInternalMerchant() {
  router.push({
    path: '/system/internal-merchant',
    query: buildRouteQuery()
  })
}

function handleEntryContextPrimary() {
  if (entrySourceLabel.value === '来自平台分析') {
    goToAnalytics()
    return
  }
  if (entrySourceLabel.value === '来自内部接盘对账') {
    goToInternalTakeover()
    return
  }
  if (entrySourceLabel.value === '来自商家列表') {
    goToMerchantList()
    return
  }
  if (entrySourceLabel.value === '来自订单列表') {
    goToOrderList()
    return
  }
  router.push({
    path: '/dashboard',
    query: buildRouteQuery()
  })
}

function goToEntryContextBack() {
  handleEntryContextPrimary()
}

function restoreHistory() {
  const stored = sessionStorage.getItem(exportHistoryKey)
  if (!stored) return
  try {
    const parsed = JSON.parse(stored)
    exportHistory.value = Array.isArray(parsed) ? parsed.slice(0, 6) : []
  } catch (error) {
    exportHistory.value = []
  }
}

function clearHistory() {
  exportHistory.value = []
}

async function reuseHistoryFilters(item = {}) {
  if (!item.params || typeof item.params !== 'object') {
    ElMessage.warning('这条历史记录没有保存原始筛选条件')
    return
  }
  applyQueryParams(item.params)
  await syncRoute(item.params)
  await reloadPreview()
  ElMessage.success('已恢复这条导出记录的筛选条件')
}

async function rerunHistoryExport(item = {}) {
  const targetAction = exportActions.value.find((action) => action.key === item.type)
  if (!targetAction) {
    ElMessage.warning('这条历史记录对应的导出模块已不可用')
    return
  }
  if (item.params && typeof item.params === 'object') {
    applyQueryParams(item.params)
    await syncRoute(item.params)
  }
  await runExport(targetAction)
}

function collectPermissionSignatures(list = [], bucket = []) {
  list.forEach((item) => {
    if (!item || typeof item !== 'object') return
    const values = [
      item.path,
      item.name,
      item.component,
      item.permission,
      item.perms,
      item.api,
      item.auth,
      item.meta?.title,
      item.meta?.permission,
      item.meta?.perms,
      item.meta?.auth
    ]
    values.forEach((value) => {
      if (typeof value === 'string' && value.trim()) {
        bucket.push(value.trim().toLowerCase())
      }
      if (Array.isArray(value)) {
        value.forEach((child) => {
          if (typeof child === 'string' && child.trim()) {
            bucket.push(child.trim().toLowerCase())
          }
        })
      }
    })
    if (Array.isArray(item.children) && item.children.length) {
      collectPermissionSignatures(item.children, bucket)
    }
  })
  return Array.from(new Set(bucket))
}

function hasExportPermission(prefix, key) {
  const signatures = permissionSignatures.value
  if (!signatures.length) return true
  const candidates = [
    prefix,
    `/${key}`,
    key,
    'report.platformexport',
    `platformexport/${key}`.toLowerCase(),
    `platformexport${key}`.toLowerCase()
  ].map((item) => String(item).toLowerCase())

  return signatures.some((signature) => candidates.some((candidate) => signature.includes(candidate)))
}

function resolveAlertTagType(level) {
  if (String(level || '').includes('高')) return 'danger'
  if (String(level || '').includes('中')) return 'warning'
  return 'info'
}

function resolveExportFollowupMessage(item = {}) {
  if (item.key === 'analytics') {
    return '建议下一步回平台数据中心核趋势和异常预警，再决定要不要继续导细表。'
  }
  if (item.key === 'orders') {
    return '建议下一步回订单页带条件复核，确认页面状态和导出结果一致。'
  }
  if (item.key === 'merchants') {
    return '建议下一步回商家页抽样复核，确认状态、商品数和到期情况都对得上。'
  }
  if (item.key === 'renewRecords') {
    return '建议下一步回商家页或数据中心复盘续费变化，必要时继续查内部接盘。'
  }
  return '建议下一步回业务页继续复核，不要只停在导出中心。'
}

function formatCurrency(value) {
  return `￥${Number(value || 0).toFixed(2)}`
}

function formatCount(value) {
  return Number(value || 0).toLocaleString('zh-CN')
}

function formatTime(date) {
  const yyyy = date.getFullYear()
  const mm = String(date.getMonth() + 1).padStart(2, '0')
  const dd = String(date.getDate()).padStart(2, '0')
  const hh = String(date.getHours()).padStart(2, '0')
  const mi = String(date.getMinutes()).padStart(2, '0')
  return `${yyyy}-${mm}-${dd} ${hh}:${mi}`
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
    radial-gradient(circle at right top, rgba(249, 115, 22, 0.12), transparent 24%),
    linear-gradient(180deg, #ffffff 0%, #fffaf5 100%);
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

.entry-context-banner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 16px;
  padding: 14px 16px;
  border-radius: 12px;
  background: linear-gradient(135deg, #f5f7ff 0%, #fffaf0 100%);
  border: 1px solid #e4e7ed;
}

.entry-context-banner__main {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.entry-context-banner__eyebrow {
  font-size: 12px;
  font-weight: 600;
  color: #909399;
}

.entry-context-banner__title {
  font-size: 16px;
  font-weight: 600;
  color: #303133;
}

.entry-context-banner__desc {
  font-size: 13px;
  line-height: 1.6;
  color: #606266;
}

.entry-context-banner__actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  justify-content: flex-end;
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
  background: #fff7ed;
  border: 1px solid #fed7aa;
  border-radius: 999px;
  transition: all 0.2s ease;
}

.quick-range__item--active,
.quick-range__item:hover {
  color: #c2410c;
  background: #ffedd5;
  border-color: rgba(234, 88, 12, 0.32);
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
  background: rgba(255, 247, 237, 0.66);
  border: 1px solid rgba(253, 186, 116, 0.42);
  border-radius: 18px;
}

.current-params__title {
  font-size: 13px;
  font-weight: 600;
  color: #9a3412;
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

.summary-banner__item {
  padding: 16px 18px;
  background: linear-gradient(135deg, #ffffff 0%, #fff7ed 100%);
  border: 1px solid rgba(253, 186, 116, 0.42);
  border-radius: 18px;
  box-shadow: 0 10px 25px rgba(15, 23, 42, 0.04);
}

.summary-banner__label {
  display: block;
  margin-bottom: 8px;
  font-size: 13px;
  color: #9a3412;
}

.summary-banner strong {
  font-size: 18px;
  color: #0f172a;
}

.metrics-grid,
.workflow-grid,
.export-grid,
.table-grid {
  display: grid;
  gap: 18px;
}

.metrics-grid {
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
}

.export-grid,
.workflow-grid,
.table-grid {
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
}

.metric-card,
.export-card {
  border: none;
  border-radius: 18px;
  box-shadow: 0 14px 36px rgba(15, 23, 42, 0.05);
}

.export-card--disabled {
  opacity: 0.78;
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

.workflow-card {
  min-height: 100%;
}

.workflow-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.workflow-item {
  padding: 16px 18px;
  border-radius: 18px;
  border: 1px solid #e2e8f0;
  background: #fff;
}

.workflow-item--danger {
  background: linear-gradient(180deg, #fff7f7 0%, #fff 100%);
  border-color: rgba(239, 68, 68, 0.2);
}

.workflow-item--warning {
  background: linear-gradient(180deg, #fffaf0 0%, #fff 100%);
  border-color: rgba(245, 158, 11, 0.22);
}

.workflow-item--success {
  background: linear-gradient(180deg, #f0fdf4 0%, #fff 100%);
  border-color: rgba(34, 197, 94, 0.18);
}

.workflow-item--primary {
  background: linear-gradient(180deg, #eff6ff 0%, #fff 100%);
  border-color: rgba(59, 130, 246, 0.18);
}

.workflow-item--info {
  background: linear-gradient(180deg, #f8fafc 0%, #fff 100%);
}

.workflow-item__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.workflow-item__title {
  font-size: 15px;
  font-weight: 700;
  color: #0f172a;
}

.workflow-item__desc {
  margin-top: 8px;
  font-size: 13px;
  line-height: 1.7;
  color: #475569;
}

.workflow-item__tips {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 14px;
}

.workflow-item__tips span {
  padding: 6px 10px;
  font-size: 12px;
  color: #475569;
  background: rgba(255, 255, 255, 0.78);
  border: 1px solid rgba(148, 163, 184, 0.22);
  border-radius: 999px;
}

.jump-actions {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.jump-action {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 6px;
  padding: 16px 18px;
  text-align: left;
  cursor: pointer;
  background: linear-gradient(180deg, #fff 0%, #fff7ed 100%);
  border: 1px solid rgba(253, 186, 116, 0.32);
  border-radius: 18px;
  transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}

.jump-action:hover {
  border-color: rgba(249, 115, 22, 0.36);
  box-shadow: 0 14px 30px rgba(249, 115, 22, 0.1);
  transform: translateY(-1px);
}

.jump-action strong {
  font-size: 15px;
  color: #0f172a;
}

.jump-action span {
  font-size: 13px;
  line-height: 1.7;
  color: #64748b;
}

.jump-hint {
  margin-top: 14px;
  padding: 14px 16px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
}

.jump-hint__label {
  font-size: 12px;
  font-weight: 700;
  color: #64748b;
}

.jump-hint__value {
  margin-top: 6px;
  font-size: 13px;
  line-height: 1.7;
  color: #334155;
}

.followup-panel {
  padding: 22px 24px;
}

.followup-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 12px;
  margin-top: 16px;
}

.followup-card {
  padding: 16px 18px;
  background: linear-gradient(180deg, #fffdf7 0%, #ffffff 100%);
  border: 1px solid rgba(253, 186, 116, 0.32);
  border-radius: 18px;
}

.followup-card__title {
  font-size: 14px;
  font-weight: 700;
  color: #0f172a;
}

.followup-card__desc {
  margin-top: 8px;
  font-size: 13px;
  line-height: 1.7;
  color: #64748b;
}

.followup-card__action {
  margin-top: 10px;
}

.export-card__head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.export-card__title {
  font-size: 18px;
  font-weight: 700;
  color: #0f172a;
}

.export-card__desc {
  margin-top: 8px;
  min-height: 44px;
  font-size: 13px;
  line-height: 1.7;
  color: #64748b;
}

.export-card__tips {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 16px;

  span {
    padding: 8px 12px;
    font-size: 12px;
    color: #475569;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 999px;
  }
}

.export-card__status {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-top: 16px;
  padding: 12px 14px;
  font-size: 12px;
  color: #64748b;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
}

.export-card__status strong {
  color: #0f172a;
}

.export-card__footer {
  margin-top: 18px;
}

.history-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.inline-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.inline-actions--mt {
  margin-top: 10px;
}

.inline-actions--compact {
  justify-content: flex-end;
}

.alert-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.alert-item {
  padding: 14px 16px;
  background: linear-gradient(180deg, #fff7f7 0%, #fff 100%);
  border: 1px solid rgba(239, 68, 68, 0.14);
  border-radius: 16px;
}

.alert-item__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.alert-item__head strong {
  font-size: 14px;
  color: #0f172a;
}

.alert-item__desc {
  margin-top: 8px;
  font-size: 13px;
  line-height: 1.7;
  color: #475569;
}

.alert-item__meta {
  margin-top: 6px;
  font-size: 12px;
  color: #94a3b8;
}

.history-item {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
  padding: 14px 16px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
}

.history-item__content,
.history-item__side {
  display: flex;
  flex-direction: column;
}

.history-item__title {
  font-size: 14px;
  font-weight: 700;
  color: #0f172a;
}

.history-item__filters,
.history-item__meta,
.history-item__time {
  margin-top: 4px;
  font-size: 12px;
  color: #64748b;
}

.history-item__side {
  align-items: flex-end;
}

@media (max-width: 1100px) {
  .plain-guide__grid,
  .export-grid,
  .table-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 900px) {
  .entry-context-banner,
  .panel__header,
  .plain-guide__header,
  .workflow-item__head,
  .alert-item__head,
  .export-card__head,
  .history-item,
  .export-card__status {
    flex-direction: column;
  }
}
</style>
