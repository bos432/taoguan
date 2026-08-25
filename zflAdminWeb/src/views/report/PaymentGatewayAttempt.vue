<template>
  <div class="app-container gateway-attempt-page">
    <header class="page-header">
      <div>
        <h1>支付网关异常</h1>
        <p>微信预下单与退款的外部调用记录，只读查看，不在此页执行补偿。</p>
      </div>
      <el-button :icon="Refresh" :loading="loading" @click="loadAll">刷新</el-button>
    </header>

    <section class="summary-band">
      <div>
        <span>全部记录</span><strong>{{ summaryData.total || 0 }}</strong>
      </div>
      <div class="is-warning">
        <span>需要关注</span><strong>{{ summaryData.attention || 0 }}</strong>
      </div>
      <div>
        <span>预下单</span><strong>{{ summaryData.business_counts?.prepayment || 0 }}</strong>
      </div>
      <div>
        <span>退款</span><strong>{{ summaryData.business_counts?.refund || 0 }}</strong>
      </div>
    </section>

    <el-form :inline="true" :model="query" class="filter-bar" @submit.prevent>
      <el-form-item label="状态">
        <el-select v-model="query.status" clearable placeholder="全部状态" style="width: 150px">
          <el-option label="请求中" :value="1" />
          <el-option label="成功" :value="2" />
          <el-option label="失败" :value="3" />
          <el-option label="结果未知" :value="4" />
        </el-select>
      </el-form-item>
      <el-form-item label="业务">
        <el-select
          v-model="query.business_type"
          clearable
          placeholder="全部业务"
          style="width: 160px"
        >
          <el-option label="微信预下单" value="prepayment" />
          <el-option label="微信退款" value="refund" />
        </el-select>
      </el-form-item>
      <el-form-item label="检索">
        <el-input
          v-model="query.keyword"
          clearable
          placeholder="订单号 / 请求号 / 微信流水"
          style="width: 280px"
          @keyup.enter="search"
        />
      </el-form-item>
      <el-form-item>
        <el-button type="primary" :icon="Search" @click="search">查询</el-button>
        <el-button @click="reset">重置</el-button>
      </el-form-item>
    </el-form>

    <el-alert v-if="error" :title="error" type="error" show-icon :closable="false" />
    <el-table v-loading="loading" :data="rows" stripe class="gateway-table">
      <el-table-column prop="order_no" label="订单号" min-width="180" />
      <el-table-column prop="business_type_title" label="业务" width="120" />
      <el-table-column prop="status_title" label="状态" width="110">
        <template #default="scope">
          <el-tag :type="statusType(scope.row.status)" effect="plain">{{
            scope.row.status_title
          }}</el-tag>
        </template>
      </el-table-column>
      <el-table-column
        prop="merchant_request_no"
        label="商户请求号"
        min-width="190"
        show-overflow-tooltip
      />
      <el-table-column prop="amount" label="金额" width="100">
        <template #default="scope">￥{{ scope.row.amount }}</template>
      </el-table-column>
      <el-table-column prop="attempt_count" label="调用次数" width="90" />
      <el-table-column
        prop="error_message"
        label="错误摘要"
        min-width="220"
        show-overflow-tooltip
      />
      <el-table-column prop="update_time" label="更新时间" width="170" />
      <el-table-column label="操作" width="140" fixed="right">
        <template #default="scope">
          <el-link type="primary" :underline="false" @click="openDetail(scope.row.id)"
            >详情</el-link
          >
          <el-link
            v-if="scope.row.member_order_id"
            type="primary"
            :underline="false"
            @click="goOrder(scope.row.order_no)"
            >订单</el-link
          >
        </template>
      </el-table-column>
    </el-table>
    <Pagination
      v-model:page="query.page"
      v-model:limit="query.limit"
      :total="total"
      @pagination="loadList"
    />

    <el-drawer v-model="drawer" title="网关调用详情" size="min(720px, 92vw)" destroy-on-close>
      <div v-loading="detailLoading" class="detail-body">
        <el-descriptions v-if="detail.id" :column="2" border>
          <el-descriptions-item label="业务">{{ detail.business_type_title }}</el-descriptions-item>
          <el-descriptions-item label="状态">{{ detail.status_title }}</el-descriptions-item>
          <el-descriptions-item label="订单号">{{ detail.order_no }}</el-descriptions-item>
          <el-descriptions-item label="调用次数">{{ detail.attempt_count }}</el-descriptions-item>
          <el-descriptions-item label="请求号" :span="2">{{
            detail.merchant_request_no
          }}</el-descriptions-item>
          <el-descriptions-item label="微信流水" :span="2">{{
            detail.provider_transaction_id || '-'
          }}</el-descriptions-item>
          <el-descriptions-item label="错误" :span="2">{{
            detail.error_message || '-'
          }}</el-descriptions-item>
        </el-descriptions>
        <h2>请求参数</h2>
        <pre>{{ formatJson(detail.request) }}</pre>
        <h2>网关响应</h2>
        <pre>{{ formatJson(detail.response) }}</pre>
      </div>
    </el-drawer>
  </div>
</template>

<script setup>
import { Refresh, Search } from '@element-plus/icons-vue'
import Pagination from '@/components/Pagination/index.vue'
import { info, list, summary } from '@/api/report/payment-gateway-attempt'

const router = useRouter()
const query = reactive({ page: 1, limit: 20, status: '', business_type: '', keyword: '' })
const rows = ref([])
const total = ref(0)
const loading = ref(false)
const error = ref('')
const summaryData = ref({ total: 0, attention: 0, business_counts: {} })
const drawer = ref(false)
const detailLoading = ref(false)
const detail = ref({})

async function loadList() {
  loading.value = true
  error.value = ''
  try {
    const response = await list(query)
    rows.value = Array.isArray(response.data?.list) ? response.data.list : []
    total.value = Number(response.data?.count || 0)
  } catch (exception) {
    rows.value = []
    total.value = 0
    error.value = exception?.message || '网关记录加载失败'
  } finally {
    loading.value = false
  }
}

async function loadSummary() {
  const response = await summary()
  summaryData.value = response.data || summaryData.value
}

function loadAll() {
  return Promise.all([loadList(), loadSummary()])
}

function search() {
  query.page = 1
  loadList()
}

function reset() {
  Object.assign(query, { page: 1, status: '', business_type: '', keyword: '' })
  loadList()
}

async function openDetail(id) {
  drawer.value = true
  detailLoading.value = true
  detail.value = {}
  try {
    const response = await info({ id })
    detail.value = response.data || {}
  } finally {
    detailLoading.value = false
  }
}

function goOrder(orderNo) {
  router.push({
    path: '/order/order',
    query: { search_field: 'order_no', search_exp: 'like', search_value: orderNo }
  })
}

function statusType(status) {
  if (Number(status) === 2) return 'success'
  if (Number(status) === 4) return 'warning'
  if (Number(status) === 3) return 'danger'
  return 'info'
}

function formatJson(value) {
  return JSON.stringify(value || {}, null, 2)
}

onMounted(loadAll)
</script>

<style scoped>
.gateway-attempt-page {
  display: flex;
  flex-direction: column;
  gap: 16px;
}
.page-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
}
.page-header h1 {
  margin: 0;
  font-size: 22px;
  color: #1f2937;
}
.page-header p {
  margin: 6px 0 0;
  color: #64748b;
}
.summary-band {
  display: grid;
  grid-template-columns: repeat(4, minmax(120px, 1fr));
  border-block: 1px solid #e5e7eb;
}
.summary-band div {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  padding: 14px 18px;
  border-right: 1px solid #e5e7eb;
}
.summary-band div:last-child {
  border-right: 0;
}
.summary-band span {
  color: #64748b;
}
.summary-band strong {
  font-size: 22px;
  color: #111827;
}
.summary-band .is-warning strong {
  color: #b45309;
}
.filter-bar {
  padding-top: 4px;
}
.gateway-table :deep(.el-link + .el-link) {
  margin-left: 12px;
}
.detail-body h2 {
  margin: 20px 0 8px;
  font-size: 14px;
}
.detail-body pre {
  max-height: 280px;
  overflow: auto;
  margin: 0;
  padding: 14px;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  background: #f8fafc;
  color: #334155;
  white-space: pre-wrap;
  overflow-wrap: anywhere;
}
@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
  }
  .summary-band {
    grid-template-columns: repeat(2, 1fr);
  }
  .summary-band div:nth-child(2) {
    border-right: 0;
  }
}
</style>
