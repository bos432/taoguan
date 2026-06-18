<template>
  <div v-loading="loading" class="ledger-page">
    <section class="hero">
      <div>
        <p class="eyebrow">财务来源快照</p>
        <h1>商家采购对账</h1>
        <p class="hero__desc">
          按支付审核成功时的商品来源统计，区分商家买平台多少、买其他商家多少。
        </p>
      </div>
      <el-button type="primary" @click="handleExport">导出明细 CSV</el-button>
    </section>

    <section class="filters">
      <el-select
        v-model="query.quick_date"
        class="filter-item"
        placeholder="快捷日期"
        @change="reload"
      >
        <el-option
          v-for="item in filterOptions.quick_dates"
          :key="item.value"
          :label="item.label"
          :value="item.value"
        />
      </el-select>
      <el-date-picker
        v-model="dateRange"
        class="filter-date"
        type="daterange"
        value-format="YYYY-MM-DD"
        start-placeholder="开始日期"
        end-placeholder="结束日期"
        @change="handleDateChange"
      />
      <el-select
        v-model="query.buyer_merchant_id"
        clearable
        filterable
        class="filter-item"
        placeholder="买方商家"
        @change="reload"
      >
        <el-option
          v-for="item in filterOptions.buyer_merchants"
          :key="item.value"
          :label="item.label"
          :value="item.value"
        />
      </el-select>
      <el-select
        v-model="query.source_type"
        clearable
        class="filter-item"
        placeholder="来源类型"
        @change="reload"
      >
        <el-option
          v-for="item in filterOptions.source_types"
          :key="item.value"
          :label="item.label"
          :value="item.value"
        />
      </el-select>
      <el-select
        v-model="query.source_merchant_id"
        clearable
        filterable
        class="filter-item"
        placeholder="来源商家/平台"
        @change="reload"
      >
        <el-option
          v-for="item in filterOptions.source_merchants"
          :key="item.value"
          :label="item.label"
          :value="item.value"
        />
      </el-select>
      <el-input
        v-model="query.keyword"
        clearable
        class="filter-keyword"
        placeholder="订单号/商品/商家"
        @keyup.enter="reload"
      />
      <el-button @click="reload">查询</el-button>
    </section>

    <section class="cards">
      <article class="card card--dark">
        <span>采购总额</span>
        <strong>¥{{ money(summaryData.cards.total_amount) }}</strong>
        <small>{{ summaryData.query_label || '当前范围' }}</small>
      </article>
      <article class="card">
        <span>买平台商品</span>
        <strong>¥{{ money(summaryData.cards.platform_amount) }}</strong>
        <small>平台收款码来源</small>
      </article>
      <article class="card">
        <span>买其他商家</span>
        <strong>¥{{ money(summaryData.cards.merchant_amount) }}</strong>
        <small>商家供货来源</small>
      </article>
      <article class="card">
        <span>订单 / 件数</span>
        <strong>{{ summaryData.cards.order_count }} / {{ summaryData.cards.quantity }}</strong>
        <small>按流水明细汇总</small>
      </article>
    </section>

    <section class="cards cards--reconcile">
      <article class="card">
        <span>核算订单</span>
        <strong>{{ summaryData.reconciliation.cards.order_count }}</strong>
        <small>按订单自动比对</small>
      </article>
      <article class="card card--ok">
        <span>核算正常</span>
        <strong>{{ summaryData.reconciliation.cards.normal_count }}</strong>
        <small>流水、订单、账单一致</small>
      </article>
      <article class="card card--warn">
        <span>异常订单</span>
        <strong>{{ summaryData.reconciliation.cards.exception_count }}</strong>
        <small>需要财务复核</small>
      </article>
      <article class="card card--danger">
        <span>异常差额</span>
        <strong>¥{{ money(summaryData.reconciliation.cards.exception_amount) }}</strong>
        <small>按差异绝对值汇总</small>
      </article>
    </section>

    <section class="split">
      <div class="panel">
        <div class="panel__title">买方商家拆账</div>
        <el-table :data="summaryData.buyer_rank" border>
          <el-table-column prop="buyer_merchant_title" label="买方商家" min-width="140" />
          <el-table-column label="买平台" width="120">
            <template #default="{ row }">¥{{ money(row.platform_amount) }}</template>
          </el-table-column>
          <el-table-column label="买商家" width="120">
            <template #default="{ row }">¥{{ money(row.merchant_amount) }}</template>
          </el-table-column>
          <el-table-column label="合计" width="120">
            <template #default="{ row }">¥{{ money(row.total_amount) }}</template>
          </el-table-column>
        </el-table>
      </div>
      <div class="panel">
        <div class="panel__title">供货来源排行</div>
        <el-table :data="summaryData.source_rank" border>
          <el-table-column prop="source_merchant_title" label="来源" min-width="140" />
          <el-table-column prop="source_type_title" label="类型" width="100" />
          <el-table-column label="金额" width="120">
            <template #default="{ row }">¥{{ money(row.amount) }}</template>
          </el-table-column>
          <el-table-column prop="order_count" label="订单" width="90" />
        </el-table>
      </div>
    </section>

    <section class="panel">
      <div class="panel__heading">
        <div>
          <div class="panel__title">核算异常订单</div>
          <p class="panel__hint">
            系统自动检查：采购流水金额、订单实付金额、购买商品账单金额是否一致。
          </p>
        </div>
        <el-tag
          :type="summaryData.reconciliation.cards.exception_count > 0 ? 'danger' : 'success'"
          effect="dark"
        >
          {{ summaryData.reconciliation.cards.exception_count > 0 ? '有异常' : '全部正常' }}
        </el-tag>
      </div>
      <el-table
        :data="summaryData.reconciliation.exception_list"
        border
        empty-text="当前筛选范围内没有核算异常"
      >
        <el-table-column prop="order_no" label="订单号" width="170" />
        <el-table-column prop="buyer_merchant_title" label="买方商家" min-width="140" />
        <el-table-column label="核算结果" width="110">
          <template #default="{ row }">
            <el-tag :type="statusTag(row.reconcile_status)">{{
              row.reconcile_status_title
            }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column
          prop="reconcile_message"
          label="系统说明"
          min-width="220"
          show-overflow-tooltip
        />
        <el-table-column label="采购流水" width="120">
          <template #default="{ row }">¥{{ money(row.ledger_amount) }}</template>
        </el-table-column>
        <el-table-column label="订单实付" width="120">
          <template #default="{ row }">¥{{ money(row.order_pay_price) }}</template>
        </el-table-column>
        <el-table-column label="账单金额" width="120">
          <template #default="{ row }">¥{{ money(row.bill_amount) }}</template>
        </el-table-column>
        <el-table-column label="差额" width="120">
          <template #default="{ row }">¥{{ money(row.reconcile_diff_amount) }}</template>
        </el-table-column>
      </el-table>
    </section>

    <section class="panel">
      <div class="panel__title">采购明细流水</div>
      <el-table :data="rows" border>
        <el-table-column prop="pay_time" label="支付时间" width="170" />
        <el-table-column prop="order_no" label="订单号" width="170" />
        <el-table-column label="核算" width="110">
          <template #default="{ row }">
            <el-tag :type="statusTag(row.reconcile_status)">{{
              row.reconcile_status_title
            }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column
          prop="reconcile_message"
          label="核算说明"
          min-width="180"
          show-overflow-tooltip
        />
        <el-table-column prop="buyer_merchant_title" label="买方商家" min-width="130" />
        <el-table-column prop="source_type_title" label="来源类型" width="110" />
        <el-table-column prop="source_merchant_title" label="来源名称" min-width="130" />
        <el-table-column prop="goods_title" label="商品" min-width="220" show-overflow-tooltip />
        <el-table-column prop="quantity" label="数量" width="80" />
        <el-table-column label="单价" width="100">
          <template #default="{ row }">¥{{ money(row.price) }}</template>
        </el-table-column>
        <el-table-column label="明细金额" width="120">
          <template #default="{ row }">¥{{ money(row.total) }}</template>
        </el-table-column>
        <el-table-column label="订单实付" width="120">
          <template #default="{ row }">¥{{ money(row.order_current_pay_price) }}</template>
        </el-table-column>
        <el-table-column label="账单金额" width="120">
          <template #default="{ row }">¥{{ money(row.bill_amount) }}</template>
        </el-table-column>
        <el-table-column label="差额" width="120">
          <template #default="{ row }">¥{{ money(row.reconcile_diff_amount) }}</template>
        </el-table-column>
      </el-table>
      <div class="pager">
        <el-pagination
          v-model:current-page="page"
          v-model:page-size="limit"
          background
          layout="total, sizes, prev, pager, next"
          :total="total"
          @current-change="loadList"
          @size-change="loadList"
        />
      </div>
    </section>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { downloadLedger, filters, list, summary } from '@/api/report/merchant-purchase-ledger'

const loading = ref(false)
const dateRange = ref([])
const page = ref(1)
const limit = ref(20)
const total = ref(0)
const rows = ref([])

const query = reactive({
  quick_date: 'all',
  start_date: '',
  end_date: '',
  buyer_merchant_id: undefined,
  source_type: '',
  source_merchant_id: undefined,
  keyword: ''
})

const filterOptions = reactive({
  quick_dates: [],
  source_types: [],
  buyer_merchants: [],
  source_merchants: []
})

const summaryData = reactive({
  query_label: '',
  cards: {
    total_amount: 0,
    platform_amount: 0,
    merchant_amount: 0,
    order_count: 0,
    quantity: 0
  },
  reconciliation: {
    cards: {
      order_count: 0,
      normal_count: 0,
      exception_count: 0,
      missing_bill_count: 0,
      amount_mismatch_count: 0,
      exception_amount: 0
    },
    exception_list: []
  },
  buyer_rank: [],
  source_rank: []
})

function buildParams() {
  return {
    ...query,
    buyer_merchant_id: query.buyer_merchant_id || 0,
    source_merchant_id: query.source_merchant_id ?? -1
  }
}

function money(value) {
  return Number(value || 0).toFixed(2)
}

function statusTag(status) {
  if (status === 'normal') return 'success'
  if (status === 'missing_bill') return 'warning'
  return 'danger'
}

function handleDateChange(value) {
  query.quick_date = ''
  query.start_date = value?.[0] || ''
  query.end_date = value?.[1] || ''
  reload()
}

async function loadFilters() {
  const res = await filters()
  Object.assign(filterOptions, res.data || {})
}

async function loadSummary() {
  const res = await summary(buildParams())
  Object.assign(summaryData, res.data || {})
}

async function loadList() {
  const res = await list({
    ...buildParams(),
    page: page.value,
    limit: limit.value
  })
  rows.value = res.data?.list || []
  total.value = res.data?.count || 0
}

async function reload() {
  loading.value = true
  try {
    page.value = 1
    await Promise.all([loadSummary(), loadList()])
  } finally {
    loading.value = false
  }
}

async function handleExport() {
  await downloadLedger(buildParams())
}

onMounted(async () => {
  loading.value = true
  try {
    await loadFilters()
    await Promise.all([loadSummary(), loadList()])
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.ledger-page {
  padding: 24px;
  background: linear-gradient(135deg, #f7f3e8 0%, #edf5ef 46%, #f7faf9 100%);
  min-height: 100%;
}

.hero,
.filters,
.panel,
.card {
  border: 1px solid rgba(39, 55, 44, 0.12);
  box-shadow: 0 18px 45px rgba(36, 52, 42, 0.08);
}

.hero {
  display: flex;
  justify-content: space-between;
  gap: 20px;
  align-items: center;
  padding: 28px;
  border-radius: 28px;
  color: #f8f3e6;
  background: radial-gradient(circle at 20% 20%, rgba(241, 195, 89, 0.35), transparent 30%),
    linear-gradient(135deg, #21392f, #0e1f1b);
}

.eyebrow {
  margin: 0 0 8px;
  letter-spacing: 0.18em;
  font-size: 12px;
  color: #f1c359;
}

.hero h1 {
  margin: 0;
  font-size: 32px;
}

.hero__desc {
  margin: 10px 0 0;
  opacity: 0.82;
}

.filters {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  padding: 16px;
  margin: 18px 0;
  border-radius: 20px;
  background: rgba(255, 255, 255, 0.78);
}

.filter-item {
  width: 170px;
}

.filter-date {
  width: 260px;
}

.filter-keyword {
  width: 220px;
}

.cards {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 16px;
}

.cards--reconcile {
  margin-top: 16px;
}

.card {
  padding: 20px;
  border-radius: 22px;
  background: rgba(255, 255, 255, 0.86);
}

.card--dark {
  color: #f8f3e6;
  background: linear-gradient(145deg, #8a5b26, #1f372d);
}

.card--ok {
  background: linear-gradient(145deg, rgba(224, 244, 230, 0.94), rgba(255, 255, 255, 0.9));
}

.card--warn {
  background: linear-gradient(145deg, rgba(255, 241, 205, 0.94), rgba(255, 255, 255, 0.9));
}

.card--danger {
  background: linear-gradient(145deg, rgba(255, 224, 216, 0.94), rgba(255, 255, 255, 0.9));
}

.card span,
.card small {
  display: block;
  opacity: 0.72;
}

.card strong {
  display: block;
  margin: 10px 0;
  font-size: 26px;
}

.split {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
  margin-top: 18px;
}

.panel {
  padding: 18px;
  border-radius: 22px;
  margin-top: 18px;
  background: rgba(255, 255, 255, 0.86);
}

.panel__heading {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  align-items: flex-start;
  margin-bottom: 14px;
}

.split .panel {
  margin-top: 0;
}

.panel__title {
  margin-bottom: 14px;
  font-weight: 700;
  color: #20372e;
}

.panel__hint {
  margin: -6px 0 0;
  color: #6c766f;
}

.pager {
  display: flex;
  justify-content: flex-end;
  margin-top: 16px;
}

@media (max-width: 1180px) {
  .cards,
  .split {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 760px) {
  .ledger-page {
    padding: 14px;
  }

  .hero,
  .filters {
    display: block;
  }

  .cards,
  .split {
    grid-template-columns: 1fr;
  }

  .filter-item,
  .filter-date,
  .filter-keyword {
    width: 100%;
    margin-bottom: 10px;
  }
}
</style>
