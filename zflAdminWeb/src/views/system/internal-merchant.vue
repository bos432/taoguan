<template>
  <div v-loading="loading" class="internal-merchant-page">
    <el-card class="panel panel--hero" shadow="never">
      <div class="panel__header">
        <div>
          <div class="panel__title">内部商家配置</div>
          <div class="panel__desc">
            这页专门看内部号是否还能稳定承接商品。把全局限制、内部号状态、接盘健康、续期记录和后续动作收在一起，不用再来回切页。
          </div>
        </div>
        <div class="panel__actions">
          <el-tag effect="plain">{{ runtimeLabel }}</el-tag>
          <el-tag effect="plain" type="success">内部号治理页</el-tag>
          <el-button :loading="loading" type="primary" @click="reload">刷新</el-button>
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

      <div class="metric-grid">
        <el-card v-for="item in summaryCards" :key="item.label" class="metric-card" shadow="never">
          <div class="metric-card__label">{{ item.label }}</div>
          <div class="metric-card__value">{{ item.value }}</div>
          <div class="metric-card__meta">{{ item.meta }}</div>
        </el-card>
      </div>
      <div class="plain-guide">
        <div class="plain-guide__header">
          <div>
            <div class="plain-guide__title">第一次看内部号，建议先按这个顺序</div>
            <div class="plain-guide__desc">
              这页不是直接查订单，而是先判断“内部号现在还能不能接、要不要限流、哪个号已经有风险”。先看全局限制，再选内部号看健康，最后再去对账或商家页继续处理。
            </div>
          </div>
          <span class="plain-guide__badge">{{ internalMerchantFocusLabel }}</span>
        </div>
        <div class="plain-guide__grid">
          <div v-for="item in internalMerchantGuideCards" :key="item.title" class="plain-guide-card">
            <div class="plain-guide-card__title">{{ item.title }}</div>
            <div class="plain-guide-card__desc">{{ item.desc }}</div>
            <div class="plain-guide-card__action">{{ item.action }}</div>
          </div>
        </div>
      </div>
      <div class="followup-panel">
        <div class="followup-panel__main">
          <div class="followup-panel__title">看完内部号后继续去哪</div>
          <div class="followup-panel__desc">{{ currentMerchantFollowupHint }}</div>
          <div class="followup-panel__tags">
            <span v-for="item in currentMerchantFollowupTags" :key="item">{{ item }}</span>
          </div>
        </div>
        <div class="followup-panel__actions">
          <el-button type="primary" @click="goToInternalTakeover()">去内部接盘对账</el-button>
          <el-button @click="goToMerchantDetail()" :disabled="!selectedMerchantId">去商家编辑页</el-button>
          <el-button @click="goToAnalyticsReview">回平台分析</el-button>
        </div>
      </div>
    </el-card>

    <el-row :gutter="12">
      <el-col :xs="24" :lg="9">
        <el-card class="panel" shadow="never">
          <template #header>
            <div class="panel__subhead">
              <div>
                <div class="panel__sub-title">承接限制</div>
                <div class="panel__sub-desc">内部号当天最多能接多少件、多少金额，先在这里做全局兜底。</div>
              </div>
            </div>
          </template>

          <el-form label-width="110px">
            <el-form-item label="是否启用">
              <el-switch v-model="limitForm.enabled" :active-value="1" :inactive-value="0" />
            </el-form-item>
            <el-form-item label="单日件数上限">
              <el-input-number v-model="limitForm.daily_quantity_limit" :min="0" :step="10" />
            </el-form-item>
            <el-form-item label="单日金额上限">
              <el-input-number v-model="limitForm.daily_amount_limit" :min="0" :step="1000" :precision="2" />
            </el-form-item>
            <el-form-item>
              <el-button type="primary" :loading="saving" @click="saveLimit">保存限制</el-button>
            </el-form-item>
          </el-form>

          <div class="hint-box">
            <div class="hint-box__title">运营提醒</div>
            <div class="hint-box__desc">
              这组限制会影响所有内部号当日承接能力。正式环境调整前，建议先结合“内部接盘对账”确认当前是否真的积压。
            </div>
          </div>
        </el-card>

        <el-card class="panel panel--minor" shadow="never">
          <template #header>
            <div class="panel__subhead">
              <div>
                <div class="panel__sub-title">快捷动作</div>
                <div class="panel__sub-desc">先看健康，再决定去哪个页面继续处理。</div>
              </div>
            </div>
          </template>

          <div class="action-stack">
            <el-button class="w-full" type="primary" @click="goToInternalTakeover()">查看内部接盘对账</el-button>
            <el-button class="w-full" @click="goTo('/merchant/merchant', { from: 'internal-merchant' })">进入商家列表</el-button>
            <el-button class="w-full" :disabled="!selectedMerchantId" @click="goToMerchantDetail()">去商家页继续处理</el-button>
          </div>
        </el-card>
      </el-col>

      <el-col :xs="24" :lg="15">
        <el-card class="panel" shadow="never">
          <template #header>
            <div class="panel__subhead">
              <div>
                <div class="panel__sub-title">内部号状态总览</div>
                <div class="panel__sub-desc">只看内部号，不把普通商家混进来。</div>
              </div>
              <el-select v-model="selectedMerchantId" filterable style="width: 220px" @change="handleMerchantChange">
                <el-option label="请选择内部号" :value="0" />
                <el-option
                  v-for="item in internalMerchantRows"
                  :key="item.id"
                  :label="item.title || `内部号 #${item.id}`"
                  :value="item.id"
                />
              </el-select>
            </div>
          </template>

          <el-table :data="internalMerchantRows" empty-text="当前未识别到内部号">
            <el-table-column label="内部号" min-width="240">
              <template #default="{ row }">
                <div class="stack">
                  <strong>{{ row.title || `内部号 #${row.id}` }}</strong>
                  <span>{{ row.username || '--' }}</span>
                </div>
              </template>
            </el-table-column>
            <el-table-column label="审核状态" width="110">
              <template #default="{ row }">
                <el-tag :type="row.auth_state === 1 ? 'success' : row.auth_state === 2 ? 'danger' : 'warning'" effect="plain">
                  {{ row.auth_state_title || '--' }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column label="期限状态" width="120">
              <template #default="{ row }">
                <el-tag :type="expireTagType(row.expire_status)" effect="plain">
                  {{ row.expire_status_title || '--' }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column label="承接限制" min-width="180">
              <template #default="{ row }">
                <div class="stack">
                  <span>件数：{{ row.merchant_purchase_daily_quantity_limit || '跟随全局' }}</span>
                  <span>金额：{{ row.merchant_purchase_daily_amount_limit || '跟随全局' }}</span>
                </div>
              </template>
            </el-table-column>
            <el-table-column label="内部号" width="100">
              <template #default="{ row }">
                <el-tag :type="row.member_is_super ? 'success' : 'info'" effect="plain">
                  {{ row.member_is_super ? '已启用' : '否' }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column label="操作" width="140" fixed="right">
              <template #default="{ row }">
                <el-button link type="primary" @click="pickMerchant(row.id)">查看健康</el-button>
              </template>
            </el-table-column>
          </el-table>
        </el-card>
      </el-col>
    </el-row>

    <el-row :gutter="12">
      <el-col :xs="24" :lg="15">
        <el-card class="panel" shadow="never">
          <template #header>
            <div class="panel__subhead">
              <div>
                <div class="panel__sub-title">接盘健康面板</div>
                <div class="panel__sub-desc">直接复用“内部接盘对账”的实时判断，告诉运营当前这个内部号是不是该立刻处理。</div>
              </div>
              <el-tag :type="healthTagType">{{ healthPanel.status_tag || '未选择内部号' }}</el-tag>
            </div>
          </template>

          <div v-if="healthPanel.merchant_id" class="health-panel">
            <div class="health-panel__hero">
              <div>
                <div class="health-panel__title">{{ healthPanel.merchant_title }}</div>
                <div class="health-panel__summary">{{ healthPanel.summary_text }}</div>
              </div>
              <div class="health-panel__next">
                <span>现在最该处理</span>
                <strong>{{ healthPanel.next_action }}</strong>
              </div>
            </div>

            <div class="health-grid">
              <div class="health-grid__item">
                <span>正常完成</span>
                <strong>{{ formatCount(healthPanel.completed_count) }}</strong>
              </div>
              <div class="health-grid__item">
                <span>待审核</span>
                <strong>{{ formatCount(healthPanel.pending_review_count) }}</strong>
              </div>
              <div class="health-grid__item">
                <span>待转商品</span>
                <strong>{{ formatCount(healthPanel.pending_transfer_count) }}</strong>
              </div>
              <div class="health-grid__item">
                <span>真正异常</span>
                <strong>{{ formatCount(healthPanel.exception_count) }}</strong>
              </div>
              <div class="health-grid__item">
                <span>资金状态</span>
                <strong>{{ healthPanel.fund_status || '--' }}</strong>
              </div>
              <div class="health-grid__item">
                <span>账单状态</span>
                <strong>{{ healthPanel.bill_status || '--' }}</strong>
              </div>
              <div class="health-grid__item">
                <span>流转状态</span>
                <strong>{{ healthPanel.transfer_status || '--' }}</strong>
              </div>
              <div class="health-grid__item">
                <span>资金差额</span>
                <strong :class="{ danger: Number(healthPanel.fund_gap_amount || 0) > 0.01 }">
                  {{ formatCurrency(healthPanel.fund_gap_amount) }}
                </strong>
              </div>
            </div>
          </div>

          <el-empty v-else description="选择一个内部号后，这里会直接显示它的接盘健康状态" />
        </el-card>
      </el-col>

      <el-col :xs="24" :lg="9">
        <el-card class="panel" shadow="never">
          <template #header>
            <div class="panel__subhead">
              <div>
                <div class="panel__sub-title">最近续期记录</div>
                <div class="panel__sub-desc">快速确认这个内部号最近有没有被延长或缩短期限。</div>
              </div>
            </div>
          </template>

          <div v-if="renewRows.length" class="renew-list">
            <div v-for="item in renewRows" :key="item.id" class="renew-item">
              <div class="renew-item__head">
                <strong>{{ item.merchant_title || '内部号' }}</strong>
                <el-tag :type="item.adjust_type === 'reduce' ? 'danger' : 'success'" effect="plain">
                  {{ item.adjust_title || '--' }}
                </el-tag>
              </div>
              <div class="renew-item__meta">续期来源：{{ item.renew_source_title || '--' }}</div>
              <div class="renew-item__meta">变更后到期：{{ item.after_expire_time || '--' }}</div>
              <div class="renew-item__meta">记录时间：{{ item.create_time || '--' }}</div>
            </div>
          </div>

          <el-empty v-else description="当前没有续期记录" />
        </el-card>
      </el-col>
    </el-row>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { list as merchantList, renewRecordList } from '@/api/merchant/merchant'
import { edit as editMerchantPurchaseLimit, info as merchantPurchaseLimitInfo } from '@/api/system/merchant-purchase-limit'
import { summary as internalTakeoverSummary } from '@/api/report/internal-takeover'

const route = useRoute()
const router = useRouter()
const loading = ref(false)
const saving = ref(false)
const merchantRows = ref([])
const healthPanel = ref({})
const renewRows = ref([])
const selectedMerchantId = ref(0)

const runtimeLabel = '本地联调'

const limitForm = reactive({
  enabled: 1,
  daily_quantity_limit: 100,
  daily_amount_limit: 50000
})

const internalMerchantRows = computed(() => {
  return merchantRows.value.filter((item) => Number(item.member_is_super || 0) === 1)
})

const summaryCards = computed(() => {
  const total = internalMerchantRows.value.length
  const expired = internalMerchantRows.value.filter((item) => item.expire_status === 'expired').length
  const remind = internalMerchantRows.value.filter((item) => item.expire_status === 'remind').length
  const disabled = internalMerchantRows.value.filter((item) => Number(item.is_disable || 0) === 1).length

  return [
    {
      label: '内部号总数',
      value: formatCount(total),
      meta: `当前页已识别 ${formatCount(total)} 个内部号`
    },
    {
      label: '已到期',
      value: formatCount(expired),
      meta: `即将到期 ${formatCount(remind)} 个`
    },
    {
      label: '已禁用',
      value: formatCount(disabled),
      meta: '这些内部号不适合继续承接新商品'
    },
    {
      label: '全局承接限制',
      value: limitForm.enabled ? '已启用' : '已关闭',
      meta: `${formatCount(limitForm.daily_quantity_limit)} 件 / ${formatCurrency(limitForm.daily_amount_limit)}`
    }
  ]
})

const entrySourceLabel = computed(() => {
  const source = route.query.from
  if (source === 'internal-takeover') return '来自内部接盘对账'
  if (source === 'platform-export') return '来自平台导出中心'
  if (source === 'merchant-list') return '来自商家列表'
  if (source === 'platform-analytics') return '来自平台分析'
  if (source === 'dashboard') return '来自后台首页'
  return ''
})

const entryContextVisible = computed(() => Boolean(entrySourceLabel.value))

const entryContextTitle = computed(() => {
  if (entrySourceLabel.value === '来自内部接盘对账') return '当前从内部接盘对账进入内部商家配置'
  if (entrySourceLabel.value === '来自平台导出中心') return '当前从平台导出中心进入内部商家配置'
  if (entrySourceLabel.value === '来自商家列表') return '当前从商家列表进入内部商家配置'
  if (entrySourceLabel.value === '来自平台分析') return '当前从平台分析进入内部商家配置'
  if (entrySourceLabel.value === '来自后台首页') return '当前从后台首页进入内部商家配置'
  return '当前为外部入口承接视角'
})

const entryContextDesc = computed(() => {
  if (entrySourceLabel.value === '来自内部接盘对账') {
    return '这类进入通常是为了确认某个内部号为什么会积压、异常或承接不稳。建议先看健康面板，再决定回对账页追订单还是回商家页改配置。'
  }
  if (entrySourceLabel.value === '来自平台导出中心') {
    return '这类进入通常是为了把导出里看到的内部号异常落回业务页。建议先锁定内部号，再回导出中心确认报表口径是否一致。'
  }
  if (entrySourceLabel.value === '来自商家列表') {
    return '这类进入通常是为了把普通商家视角切到内部号治理视角。建议先确认内部号标记、期限和限制，再决定是否继续去对账页。'
  }
  if (entrySourceLabel.value === '来自平台分析') {
    return '这类进入通常是为了追某个趋势波动背后的内部号承接问题。建议先看全局限制和异常号，再回平台分析复盘走势。'
  }
  return '这类进入通常是首页巡检后的继续下钻。建议先判断内部号是否还能稳定承接，再去对账页或商家页继续处理。'
})

const entryContextPrimaryLabel = computed(() => {
  if (entrySourceLabel.value === '来自内部接盘对账') return '回内部接盘对账'
  if (entrySourceLabel.value === '来自平台导出中心') return '回平台导出中心'
  if (entrySourceLabel.value === '来自商家列表') return '回商家列表'
  if (entrySourceLabel.value === '来自平台分析') return '回平台分析'
  return '回后台首页'
})

const internalMerchantFocusLabel = computed(() => {
  if (!selectedMerchantId.value) {
    return '先选内部号'
  }
  if (Number(healthPanel.value?.exception_count || 0) > 0) {
    return '先处理异常号'
  }
  if (Number(healthPanel.value?.pending_transfer_count || 0) > 0) {
    return '先处理待转商品'
  }
  if (Number(healthPanel.value?.pending_review_count || 0) > 0) {
    return '先处理待审核'
  }
  return '先看承接限制'
})

const internalMerchantGuideCards = computed(() => {
  const selectedTitle = healthPanel.value?.merchant_title || '当前未锁定内部号'
  return [
    {
      title: '第一步：先看全局限制是不是卡住了承接',
      desc: '如果全局件数或金额上限过低，内部号就算状态正常也接不住单，所以先看这里最省时间。',
      action: `当前限制：件数 ${limitForm.daily_quantity_limit || 0} / 金额 ${formatCurrency(limitForm.daily_amount_limit)}`
    },
    {
      title: '第二步：再选一个内部号看健康面板',
      desc: '健康面板会直接告诉你这个内部号是正常、处理中还是有风险，比单看商家状态更接近实际运营节奏。',
      action: selectedMerchantId.value ? `当前已锁定：${selectedTitle}` : '先从右侧列表选择一个内部号。'
    },
    {
      title: '第三步：最后再决定去对账页还是商家页',
      desc: '如果是待审核、待转商品、异常订单，就去内部接盘对账；如果是期限、限制、收款码等商家资料问题，就回商家页处理。',
      action: currentMerchantFollowupHint.value
    }
  ]
})

const currentMerchantRow = computed(() => {
  return internalMerchantRows.value.find((item) => Number(item.id) === Number(selectedMerchantId.value)) || {}
})

const currentMerchantFollowupHint = computed(() => {
  if (!selectedMerchantId.value) {
    return '还没有锁定具体内部号，建议先从列表里选一个内部号，再继续去对账页或商家编辑页处理。'
  }
  const row = currentMerchantRow.value
  const merchantTitle = row.title || `内部号 #${selectedMerchantId.value}`
  if (healthPanel.value?.status_tag === '存在风险') {
    return `${merchantTitle} 当前已经有风险提示，建议先去内部接盘对账页看异常单，再决定是否回商家页改限制或状态。`
  }
  if (healthPanel.value?.pending_transfer_count > 0 || healthPanel.value?.pending_review_count > 0) {
    return `${merchantTitle} 当前更多是流程积压，适合先去对账页看待审核、待转商品，再回这里核对承接限制。`
  }
  return `${merchantTitle} 当前整体比较平稳，适合回平台分析做复盘，或回商家页抽查配置是否仍和运营策略一致。`
})

const currentMerchantFollowupTags = computed(() => {
  const row = currentMerchantRow.value
  return [
    `当前内部号：${row.title || (selectedMerchantId.value ? `#${selectedMerchantId.value}` : '未选择')}`,
    `健康状态：${healthPanel.value?.status_tag || '未判断'}`,
    `待审核：${formatCount(healthPanel.value?.pending_review_count)}`,
    `待转商品：${formatCount(healthPanel.value?.pending_transfer_count)}`
  ]
})

const healthTagType = computed(() => {
  if (healthPanel.value.status_tag === '存在风险') return 'danger'
  if (healthPanel.value.status_tag === '流程处理中') return 'warning'
  return 'success'
})

async function reload() {
  loading.value = true
  try {
    const [merchantRes, limitRes] = await Promise.all([
      merchantList({ page: 1, limit: 100 }),
      merchantPurchaseLimitInfo()
    ])
    merchantRows.value = (merchantRes.data?.list || []).map((item) => ({
      ...item,
      expire_status_title: item.expire_status_title || getExpireTitle(item.expire_status)
    }))
    Object.assign(limitForm, limitRes.data || {})

    if (!selectedMerchantId.value && internalMerchantRows.value.length) {
      selectedMerchantId.value = Number(internalMerchantRows.value[0].id)
    }
    await loadMerchantLinkedData()
  } catch (error) {
    ElMessage.error(error?.msg || error?.message || '内部商家配置加载失败')
  } finally {
    loading.value = false
  }
}

async function loadMerchantLinkedData() {
  if (!selectedMerchantId.value) {
    healthPanel.value = {}
    renewRows.value = []
    return
  }

  const [summaryRes, renewRes] = await Promise.all([
    internalTakeoverSummary({ internal_merchant_id: selectedMerchantId.value, quick_date: 'last30' }),
    renewRecordList({ merchant_id: selectedMerchantId.value, page: 1, limit: 5 })
  ])

  healthPanel.value = summaryRes.data?.health_panel || {}
  renewRows.value = renewRes.data?.list || []
}

async function saveLimit() {
  saving.value = true
  try {
    await editMerchantPurchaseLimit({ ...limitForm })
    ElMessage.success('内部号承接限制已保存')
    await reload()
  } catch (error) {
    ElMessage.error(error?.msg || error?.message || '保存失败')
  } finally {
    saving.value = false
  }
}

function pickMerchant(id) {
  selectedMerchantId.value = Number(id || 0)
  handleMerchantChange()
}

async function handleMerchantChange() {
  try {
    await loadMerchantLinkedData()
    if (selectedMerchantId.value) {
      router.replace({
        path: route.path,
        query: {
          ...route.query,
          internal_merchant_id: selectedMerchantId.value
        }
      })
    }
  } catch (error) {
    ElMessage.error(error?.msg || error?.message || '内部号健康数据加载失败')
  }
}

function syncSelectedMerchantFromRoute() {
  const routeMerchantId = Number(route.query.internal_merchant_id || 0)
  if (routeMerchantId > 0 && routeMerchantId !== selectedMerchantId.value) {
    selectedMerchantId.value = routeMerchantId
    return true
  }
  return false
}

function buildEntryRouteQuery(extraQuery = {}, nextFrom = '') {
  const query = {
    ...route.query,
    ...extraQuery
  }
  if (nextFrom) {
    query.from = nextFrom
  }
  return query
}

function goTo(path, query = {}) {
  router.push({
    path,
    query: buildEntryRouteQuery(query, query.from || 'internal-merchant')
  })
}

function handleEntryContextPrimary() {
  if (entrySourceLabel.value === '来自内部接盘对账') {
    goToInternalTakeover()
    return
  }
  if (entrySourceLabel.value === '来自平台导出中心') {
    goToExportCenter()
    return
  }
  if (entrySourceLabel.value === '来自商家列表') {
    goTo('/merchant/merchant')
    return
  }
  if (entrySourceLabel.value === '来自平台分析') {
    goToAnalyticsReview()
    return
  }
  goTo('/dashboard')
}

function goToEntryContextBack() {
  handleEntryContextPrimary()
}

function goToInternalTakeover() {
  if (!selectedMerchantId.value) {
    goTo('/report/internal-takeover')
    return
  }
  goTo('/report/internal-takeover', {
    internal_merchant_id: selectedMerchantId.value
  })
}

function goToAnalyticsReview() {
  goTo('/analytics', {
    ...(selectedMerchantId.value ? { merchant_id: selectedMerchantId.value } : {})
  })
}

function goToExportCenter() {
  goTo('/exports', {
    ...(selectedMerchantId.value ? { merchant_id: selectedMerchantId.value } : {})
  })
}

function goToMerchantDetail() {
  if (!selectedMerchantId.value) return
  goTo('/merchant/merchant', {
    id: selectedMerchantId.value,
    focus_mode: 'edit',
    internal_merchant_id: selectedMerchantId.value
  })
}

function expireTagType(status) {
  if (status === 'expired') return 'danger'
  if (status === 'remind') return 'warning'
  if (status === 'active') return 'success'
  return 'info'
}

function getExpireTitle(status) {
  if (status === 'expired') return '已到期'
  if (status === 'remind') return '即将到期'
  if (status === 'active') return '有效期内'
  return '未设置'
}

function formatCount(value) {
  return Number(value || 0).toLocaleString('zh-CN')
}

function formatCurrency(value) {
  return `￥${Number(value || 0).toFixed(2)}`
}

onMounted(() => {
  syncSelectedMerchantFromRoute()
  reload()
})

watch(
  () => route.query.internal_merchant_id,
  async () => {
    if (syncSelectedMerchantFromRoute()) {
      await handleMerchantChange()
    }
  }
)
</script>

<style lang="scss" scoped>
.internal-merchant-page {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.entry-context-banner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin: 0 0 16px;
  padding: 14px 16px;
  border-radius: 12px;
  background: linear-gradient(135deg, #f5f7ff 0%, #fffaf0 100%);
  border: 1px solid #e5e7eb;
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

.panel,
.metric-card {
  border: none;
  border-radius: 22px;
  box-shadow: 0 18px 40px rgba(15, 23, 42, 0.06);
}

.panel--hero {
  background: linear-gradient(135deg, rgba(248, 250, 252, 0.98), rgba(239, 246, 255, 0.95));
}

.panel--minor {
  margin-top: 12px;
}

.panel__header,
.panel__subhead {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
}

.panel__title,
.panel__sub-title {
  font-size: 20px;
  font-weight: 700;
  color: #0f172a;
}

.panel__desc,
.panel__sub-desc {
  margin-top: 6px;
  font-size: 13px;
  line-height: 1.7;
  color: #64748b;
}

.panel__actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.metric-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
  margin-top: 18px;
}

.plain-guide {
  margin-top: 14px;
  padding: 14px 16px;
  border-radius: 16px;
  border: 1px solid #dbe7ff;
  background: linear-gradient(135deg, #f9fbff 0%, #f5f8ff 100%);
}

.plain-guide__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.plain-guide__title {
  font-size: 15px;
  font-weight: 700;
  color: #111827;
}

.plain-guide__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.plain-guide__badge {
  display: inline-flex;
  align-items: center;
  min-height: 30px;
  padding: 0 12px;
  border-radius: 999px;
  background: #e8f0ff;
  color: #1d4ed8;
  font-size: 12px;
  font-weight: 700;
  white-space: nowrap;
}

.plain-guide__grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-top: 14px;
}

.plain-guide-card {
  padding: 12px 14px;
  border-radius: 12px;
  border: 1px solid rgba(191, 219, 254, 0.95);
  background: rgba(255, 255, 255, 0.92);
}

.plain-guide-card__title {
  font-size: 13px;
  font-weight: 700;
  color: #1f2937;
}

.plain-guide-card__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #475569;
}

.plain-guide-card__action {
  margin-top: 8px;
  font-size: 12px;
  line-height: 1.7;
  color: #1d4ed8;
}

.followup-panel {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-top: 14px;
  padding: 14px 16px;
  border-radius: 16px;
  border: 1px solid rgba(226, 232, 240, 0.95);
  background: rgba(255, 255, 255, 0.9);
}

.followup-panel__main {
  flex: 1;
  min-width: 0;
}

.followup-panel__title {
  font-size: 14px;
  font-weight: 700;
  color: #0f172a;
}

.followup-panel__desc {
  margin-top: 8px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.followup-panel__tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 10px;
}

.followup-panel__tags span {
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  padding: 0 10px;
  border-radius: 999px;
  background: #f8fafc;
  border: 1px solid rgba(148, 163, 184, 0.16);
  color: #475569;
  font-size: 12px;
}

.followup-panel__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  justify-content: flex-end;
}

.metric-card__label {
  font-size: 13px;
  color: #64748b;
}

.metric-card__value {
  margin-top: 10px;
  font-size: 28px;
  font-weight: 700;
  color: #0f172a;
}

.metric-card__meta {
  margin-top: 10px;
  font-size: 12px;
  color: #64748b;
  line-height: 1.7;
}

.hint-box {
  padding: 14px 16px;
  margin-top: 12px;
  background: rgba(248, 250, 252, 0.92);
  border: 1px solid rgba(148, 163, 184, 0.16);
  border-radius: 16px;
}

.hint-box__title {
  font-size: 14px;
  font-weight: 700;
  color: #0f172a;
}

.hint-box__desc {
  margin-top: 8px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.action-stack {
  display: grid;
  gap: 10px;
}

.stack {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.stack strong,
.stack span {
  line-height: 1.5;
}

.health-panel {
  display: grid;
  gap: 16px;
}

.health-panel__hero {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  padding: 16px;
  border-radius: 14px;
  background: #f8fafc;
}

.health-panel__title {
  font-size: 18px;
  font-weight: 700;
  color: #111827;
}

.health-panel__summary {
  margin-top: 8px;
  line-height: 1.7;
  color: #5b6472;
}

.health-panel__next {
  min-width: 220px;
  display: grid;
  gap: 6px;
}

.health-panel__next span {
  font-size: 12px;
  color: #64748b;
}

.health-panel__next strong {
  color: #0f172a;
  line-height: 1.7;
}

.health-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
}

.health-grid__item {
  padding: 14px 16px;
  background: #fff;
  border: 1px solid #e7edf5;
  border-radius: 14px;
}

.health-grid__item span {
  display: block;
  font-size: 12px;
  color: #64748b;
}

.health-grid__item strong {
  display: block;
  margin-top: 8px;
  color: #0f172a;
  line-height: 1.6;
}

.renew-list {
  display: grid;
  gap: 12px;
}

.renew-item {
  padding: 14px 16px;
  border-radius: 14px;
  border: 1px solid #e7edf5;
  background: #fff;
}

.renew-item__head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
}

.renew-item__meta {
  margin-top: 8px;
  color: #64748b;
  font-size: 12px;
  line-height: 1.6;
}

.danger {
  color: #dc2626 !important;
}

@media (max-width: 1200px) {
  .metric-grid,
  .health-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 768px) {
  .entry-context-banner,
  .plain-guide__header,
  .panel__header,
  .panel__subhead,
  .followup-panel,
  .health-panel__hero {
    flex-direction: column;
  }

  .plain-guide__grid,
  .metric-grid,
  .health-grid {
    grid-template-columns: 1fr;
  }
}
</style>
