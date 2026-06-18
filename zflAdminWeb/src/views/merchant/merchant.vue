<template>
  <div class="merchant-page">
    <el-card class="panel panel--hero" shadow="never">
      <div class="panel__header">
        <div>
          <div class="panel__title">商家管理</div>
          <div class="panel__desc">统一处理商家审核、到期、停用、续费与收款码维护。</div>
        </div>
        <div class="panel__actions">
          <el-tag type="info" effect="plain">{{ runtimeModeLabel }}</el-tag>
          <el-button type="primary" @click="openCreate">新建商家</el-button>
        </div>
      </div>

      <div class="status-cards">
        <button
          v-for="item in statusCards"
          :key="item.value"
          class="status-card"
          :class="{ 'status-card--active': query.auth_state === item.value }"
          type="button"
          @click="switchStatus(item.value)"
        >
          <span class="status-card__label">{{ item.label }}</span>
          <span class="status-card__count">{{ formatCount(item.count) }}</span>
        </button>
      </div>

      <el-form class="filter-form" inline>
        <el-form-item label="期限状态">
          <el-select v-model="query.expire_status" clearable style="width: 180px">
            <el-option label="全部期限状态" value="" />
            <el-option
              v-for="item in expireOptions"
              :key="item.value"
              :label="item.label"
              :value="item.value"
            />
          </el-select>
        </el-form-item>

        <el-form-item label="搜索字段">
          <el-select v-model="query.search_field" style="width: 150px">
            <el-option label="商家名称" value="title" />
            <el-option label="商家账号" value="username" />
            <el-option label="联系电话" value="phone" />
            <el-option label="联系人" value="name" />
            <el-option label="备注" value="remark" />
          </el-select>
        </el-form-item>

        <el-form-item label="关键词">
          <el-input
            v-model="query.search_value"
            clearable
            placeholder="请输入商家关键词"
            style="width: 220px"
            @keyup.enter="search"
          />
        </el-form-item>

        <el-form-item label="添加时间">
          <el-date-picker
            v-model="query.date_value"
            type="daterange"
            value-format="YYYY-MM-DD"
            start-placeholder="开始日期"
            end-placeholder="结束日期"
            style="width: 260px"
          />
        </el-form-item>

        <el-form-item>
          <el-button type="primary" @click="search">查询</el-button>
          <el-button @click="resetQuery">重置</el-button>
        </el-form-item>
      </el-form>

      <div class="merchant-summary-bar">
        <div class="merchant-summary-bar__chips">
          <span class="summary-chip summary-chip--primary">总数 {{ pagination.count }}</span>
          <span class="summary-chip">待审核 {{ Number(currentStatusNums.pending || 0) }} 家</span>
          <span class="summary-chip">已到期 {{ Number(currentStatusNums.expired || 0) }} 家</span>
          <span class="summary-chip">已选 {{ selectedRows.length }} 家</span>
          <span v-for="item in activeFilterTags" :key="item" class="summary-chip">{{ item }}</span>
          <span v-if="!activeFilterTags.length" class="summary-chip">未设置筛选条件</span>
          <span v-if="recentActionSummary" class="summary-chip summary-chip--muted">{{ recentActionSummary }}</span>
        </div>
        <div class="merchant-summary-bar__hint" :class="merchantSummaryHintClass">
          <span class="merchant-summary-bar__hint-title">{{ merchantSummaryHintTitle }}</span>
          <span class="merchant-summary-bar__hint-text">{{ merchantSummaryHintText }}</span>
        </div>
      </div>

      <div class="followup-panel">
        <div class="followup-panel__header">
          <div>
            <div class="followup-panel__title">看完商家后继续去哪</div>
            <div class="followup-panel__desc">{{ merchantFollowupHint }}</div>
          </div>
          <div class="followup-panel__tags">
            <el-tag v-for="item in merchantFollowupTags" :key="item" effect="plain" type="info">
              {{ item }}
            </el-tag>
          </div>
        </div>
        <div class="followup-panel__actions">
          <el-button type="primary" plain @click="goToPlatformVoucherSetting">设置平台收款码</el-button>
          <el-button @click="goToMerchantAnalytics">去运营总览看整体波动</el-button>
          <el-button @click="goToInternalTakeover">去内部接盘对账</el-button>
          <el-button @click="goToRenewRecordsPage">去续费记录继续排查</el-button>
        </div>
      </div>
    </el-card>

    <el-card class="panel" shadow="never">
      <div class="toolbar">
        <div class="toolbar__left">
          <el-button @click="openAuthDialog()">批量审核</el-button>
          <el-button @click="openRenewDialog()">批量续费</el-button>
          <el-button @click="openRenewRecords()">续费记录</el-button>
          <el-button @click="openDisableDialog()">批量禁用</el-button>
          <el-button type="danger" plain @click="openDeleteDialog()">批量删除</el-button>
        </div>
        <div class="toolbar__right">
          <span>即将到期 {{ Number(currentStatusNums.expiring || 0) }} 家</span>
          <span>已禁用 {{ Number(currentStatusNums.disabled || 0) }} 家</span>
        </div>
      </div>

      <el-table
        ref="tableRef"
        v-loading="loading"
        :data="rows"
        row-key="id"
        size="small"
        class="merchant-table"
        @selection-change="handleSelectionChange"
      >
        <el-table-column type="selection" width="44" />
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column label="商家" min-width="360">
          <template #default="{ row }">
            <div class="merchant-cell">
              <div class="merchant-cell__media">
                <FileImage
                  v-if="merchantReceiptUrl(row)"
                  :file-url="merchantReceiptUrl(row)"
                  :height="54"
                  file-source="list"
                  fit="cover"
                  class="thumb"
                />
                <div v-else class="merchant-cell__placeholder">
                  {{ merchantInitial(row) }}
                </div>
              </div>
              <div class="merchant-cell__body">
                <div class="merchant-cell__title">{{ merchantDisplayTitle(row) }}</div>
                <div class="merchant-cell__meta">
                  <span>ID：{{ row.id || '--' }}</span>
                  <span>账号：{{ row.username || '--' }}</span>
                </div>
                <div class="merchant-cell__meta">
                  <span>联系人：{{ row.name || '--' }}</span>
                  <span>手机：{{ row.phone || '--' }}</span>
                </div>
                <div class="merchant-cell__tags">
                  <el-tag
                    size="small"
                    :type="merchantReceiptUrl(row) ? 'success' : 'warning'"
                    effect="plain"
                  >
                    {{ merchantReceiptUrl(row) ? '商家收款码已配置' : '未配商家收款码' }}
                  </el-tag>
                  <el-tag v-if="row.member?.nickname" size="small" type="info" effect="plain">
                    会员：{{ row.member.nickname }}
                  </el-tag>
                </div>
              </div>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="联系信息" min-width="180">
          <template #default="{ row }">
            <div class="stack">
              <span>{{ row.phone || '--' }}</span>
              <span class="muted">{{ row.address || '--' }}</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="审核状态" width="130">
          <template #default="{ row }">
            <el-tag :type="authTagType(row.auth_state)">
              {{ row.auth_state_title || authStateLabel(row.auth_state) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="期限状态" min-width="180">
          <template #default="{ row }">
            <div class="stack">
              <el-tag :type="expireTagType(row)">
                {{ row.expire_status_title || expireStateLabel(row) }}
              </el-tag>
              <span class="muted">
                {{
                  row.days_left === null || row.days_left === undefined
                    ? '未设置期限'
                    : `剩余 ${row.days_left} 天`
                }}
              </span>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="expire_time" label="到期时间" width="170" />
        <el-table-column label="禁用" width="100">
          <template #default="{ row }">
            <el-switch
              :model-value="row.is_disable"
              :active-value="1"
              :inactive-value="0"
              @change="handleDisableSwitch(row)"
            />
          </template>
        </el-table-column>
        <el-table-column prop="create_time" label="添加时间" width="170" />
        <el-table-column label="操作" width="360" fixed="right">
          <template #default="{ row }">
            <div class="actions">
              <el-button link type="primary" @click="openEdit(row)">编辑</el-button>
              <el-button link type="info" @click="openDetail(row)">详情</el-button>
              <el-button link type="warning" @click="openDisableDialog([row])">
                {{ Number(row.is_disable) === 1 ? '启用商家' : '停用商家' }}
              </el-button>
              <el-button link type="success" @click="openAuthDialog([row])">审核</el-button>
              <el-button link type="success" @click="openRenewDialog([row])">续费</el-button>
              <el-button link type="info" @click="openRenewRecords([row])">记录</el-button>
              <el-button link type="danger" @click="openDeleteDialog([row])">删除</el-button>
            </div>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination">
        <Pagination
          v-show="pagination.count > 0"
          v-model:total="pagination.count"
          v-model:page="query.page"
          v-model:limit="query.limit"
          @pagination="fetchList"
        />
      </div>
    </el-card>

    <el-drawer v-model="detailVisible" title="商家详情" size="760px">
      <div v-loading="detailLoading" class="detail-wrap">
        <el-descriptions v-if="detailRow" :column="2" border>
          <el-descriptions-item label="商家名称">
            {{ detailRow.title || `商家 #${detailRow.id}` }}
          </el-descriptions-item>
          <el-descriptions-item label="商家账号">
            {{ detailRow.username || '--' }}
          </el-descriptions-item>
          <el-descriptions-item label="联系人">
            {{ detailRow.name || '--' }}
          </el-descriptions-item>
          <el-descriptions-item label="联系电话">
            {{ detailRow.phone || '--' }}
          </el-descriptions-item>
          <el-descriptions-item label="审核状态">
            <el-tag :type="authTagType(detailRow.auth_state)">
              {{ detailRow.auth_state_title || authStateLabel(detailRow.auth_state) }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="期限状态">
            <el-tag :type="expireTagType(detailRow)">
              {{ detailRow.expire_status_title || expireStateLabel(detailRow) }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="到期时间">
            {{ detailRow.expire_time || '--' }}
          </el-descriptions-item>
          <el-descriptions-item label="提醒天数">
            {{ detailRow.renew_remind_days || '--' }}
          </el-descriptions-item>
          <el-descriptions-item label="详细地址" :span="2">
            {{ detailRow.address || '--' }}
          </el-descriptions-item>
          <el-descriptions-item label="审核备注" :span="2">
            {{ detailRow.auth_msg || '--' }}
          </el-descriptions-item>
          <el-descriptions-item label="商家备注" :span="2">
            {{ detailRow.remark || '--' }}
          </el-descriptions-item>
          <el-descriptions-item label="商家收款码" :span="2">
            <div v-if="detailRow.receipt_image_url || detailRow.image_url" class="receipt-preview-card">
              <FileImage
                :file-url="detailRow.receipt_image_url || detailRow.image_url"
                file-source="list"
                class="receipt-preview-card__image"
              />
              <span class="muted">前端付款页会优先展示当前这张收款码。</span>
            </div>
            <span v-else>未配置</span>
          </el-descriptions-item>
        </el-descriptions>
      </div>
    </el-drawer>

    <el-dialog v-model="editVisible" :title="editTitle" width="760px" destroy-on-close>
      <el-form ref="editFormRef" :model="editForm" :rules="editRules" label-position="top">
        <div class="edit-grid">
          <el-form-item label="商家名称" prop="title">
            <el-input v-model="editForm.title" />
          </el-form-item>
          <el-form-item label="商家姓名" prop="name">
            <el-input v-model="editForm.name" />
          </el-form-item>
          <el-form-item label="联系电话" prop="phone">
            <el-input v-model="editForm.phone" />
          </el-form-item>
          <el-form-item label="详细地址" prop="address">
            <el-input v-model="editForm.address" />
          </el-form-item>
          <el-form-item label="商家账号" prop="username">
            <el-input v-model="editForm.username" />
          </el-form-item>
          <el-form-item :label="passwordFieldLabel" prop="password">
            <el-input
              v-model="editForm.password"
              show-password
              type="password"
              :placeholder="isEditing ? '留空则不修改当前密码' : '请输入登录密码'"
            />
          </el-form-item>
          <el-form-item label="到期时间" prop="expire_time">
            <el-date-picker
              v-model="editForm.expire_time"
              type="datetime"
              value-format="YYYY-MM-DD HH:mm:ss"
              placeholder="请选择到期时间"
              style="width: 100%"
            />
          </el-form-item>
          <el-form-item label="提醒天数" prop="renew_remind_days">
            <el-input v-model="editForm.renew_remind_days" type="number" />
          </el-form-item>
          <el-form-item label="排序" prop="sort">
            <el-input v-model="editForm.sort" type="number" />
          </el-form-item>
          <el-form-item label="商家收款码" prop="image_id">
            <div class="receipt-editor">
              <div class="receipt-editor__tip">
                运营可直接上传或更换商家收款码，保存后，前端付款页会自动使用最新图片。
              </div>
              <FileImage
                v-model="editForm.image_id"
                :file-url="editForm.receipt_image_url || editForm.image_url"
                :height="120"
                upload
              />
              <div class="receipt-editor__meta">
                <span class="muted">
                  {{ editForm.image_id ? `当前文件 ID：${editForm.image_id}` : '暂未上传收款码' }}
                </span>
                <el-button
                  v-if="editForm.image_id"
                  link
                  type="danger"
                  @click="clearReceiptImage"
                >
                  删除当前收款码
                </el-button>
              </div>
            </div>
          </el-form-item>
        </div>

        <el-form-item label="备注" prop="remark">
          <el-input v-model="editForm.remark" type="textarea" :rows="3" />
        </el-form-item>
        <el-form-item label="资质证明" prop="images">
          <FileUploads
            v-model="editForm.images"
            upload-btn="上传图片"
            file-type="image"
            file-tip="图片文件"
          />
        </el-form-item>
      </el-form>

      <template #footer>
        <el-button @click="editVisible = false">取消</el-button>
        <el-button type="primary" :loading="submitting" @click="submitEdit">保存</el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="actionVisible" :title="actionTitle" width="560px" destroy-on-close>
      <el-form label-position="top">
        <el-form-item label="目标商家">
          <el-input :model-value="selectedSummary" type="textarea" autosize disabled />
        </el-form-item>

        <template v-if="actionType === 'disable'">
          <el-form-item label="是否禁用">
            <el-switch v-model="actionForm.is_disable" :active-value="1" :inactive-value="0" />
          </el-form-item>
          <el-form-item v-if="actionForm.is_disable === 1" label="同步处理商品">
            <div class="inline-switch">
              <el-switch
                v-model="actionForm.sync_goods_disable"
                :active-value="1"
                :inactive-value="0"
              />
              <span class="muted">启用后会自动下架该商家商品</span>
            </div>
          </el-form-item>
        </template>

        <template v-else-if="actionType === 'auth'">
          <el-form-item label="审核状态">
            <el-radio-group v-model="actionForm.auth_state">
              <el-radio :label="1">审核通过</el-radio>
              <el-radio :label="2">审核拒绝</el-radio>
            </el-radio-group>
          </el-form-item>
          <el-form-item v-if="actionForm.auth_state === 2" label="拒绝原因">
            <el-input
              v-model="actionForm.auth_msg"
              type="textarea"
              :rows="3"
              placeholder="请填写拒绝原因"
            />
          </el-form-item>
        </template>

        <template v-else-if="actionType === 'renew'">
          <el-form-item label="续费月数">
            <el-input-number v-model="actionForm.renew_months" :min="0" :step="1" />
          </el-form-item>
          <el-form-item label="额外天数">
            <el-input-number v-model="actionForm.renew_days" :min="0" :step="1" />
          </el-form-item>
          <el-form-item label="续费金额">
            <el-input-number v-model="actionForm.amount" :min="0" :precision="2" :step="0.1" />
          </el-form-item>
          <el-form-item label="提醒天数">
            <el-input-number v-model="actionForm.renew_remind_days" :min="1" :step="1" />
          </el-form-item>
          <el-form-item label="备注">
            <el-input v-model="actionForm.remark" type="textarea" :rows="4" />
          </el-form-item>
        </template>

        <template v-else-if="actionType === 'delete'">
          <div class="danger-tip">确定要删除当前选中的商家吗？</div>
        </template>
      </el-form>

      <template #footer>
        <el-button @click="actionVisible = false">取消</el-button>
        <el-button type="primary" :loading="actionLoading" @click="submitAction">提交</el-button>
      </template>
    </el-dialog>

    <el-drawer v-model="recordVisible" title="续费记录" size="760px">
      <div v-loading="recordLoading" class="detail-wrap">
        <el-table :data="renewRecords" row-key="id">
          <el-table-column prop="merchant_title" label="商家" min-width="180" />
          <el-table-column prop="renew_source_title" label="来源" width="120" />
          <el-table-column prop="adjust_title" label="调整" width="120" />
          <el-table-column label="金额" width="120">
            <template #default="{ row }">¥{{ formatAmount(row.amount) }}</template>
          </el-table-column>
          <el-table-column prop="before_expire_time" label="调整前到期" width="180" />
          <el-table-column prop="after_expire_time" label="调整后到期" width="180" />
          <el-table-column prop="create_time" label="操作时间" width="180" />
        </el-table>
      </div>
    </el-drawer>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Pagination from '@/components/Pagination/index.vue'
import FileImage from '@/components/FileManage/FileImage.vue'
import FileUploads from '@/components/FileManage/FileUploads.vue'
import { getPageLimit } from '@/utils/settings'
import {
  list as listApi,
  info as infoApi,
  add as addApi,
  edit as editApi,
  dele as deleteApi,
  disable as disableApi,
  getParams as getParamsApi,
  auth as authApi,
  renew as renewApi,
  renewRecordList
} from '@/api/merchant/merchant'

const route = useRoute()
const router = useRouter()
const tableRef = ref()
const editFormRef = ref()
const loading = ref(false)
const submitting = ref(false)
const actionLoading = ref(false)
const detailLoading = ref(false)
const recordLoading = ref(false)
const rows = ref([])
const params = ref({})
const selectedRows = ref([])
const detailRow = ref(null)
const renewRecords = ref([])
const recentAction = ref(null)
const lastHandledFocusKey = ref('')

const detailVisible = ref(false)
const editVisible = ref(false)
const actionVisible = ref(false)
const recordVisible = ref(false)

const actionType = ref('')
const actionTitle = ref('批量操作')
const editTitle = ref('新建商家')

const pagination = reactive({
  count: 0
})

const defaultQuery = () => ({
  page: 1,
  limit: getPageLimit(),
  search_field: 'title',
  search_exp: 'like',
  search_value: '',
  date_field: 'create_time',
  date_value: [],
  expire_status: '',
  auth_state: -1
})

const defaultEditForm = () => ({
  id: undefined,
  title: '',
  name: '',
  phone: '',
  address: '',
  username: '',
  password: '',
  remark: '',
  sort: 250,
  expire_time: '',
  renew_remind_days: 7,
  image_id: 0,
  image_url: '',
  receipt_image_url: '',
  images: []
})

const defaultActionForm = () => ({
  is_disable: 1,
  sync_goods_disable: 0,
  auth_state: 1,
  auth_msg: '',
  renew_months: 1,
  renew_days: 0,
  amount: 0,
  renew_remind_days: 7,
  remark: ''
})

const query = reactive(defaultQuery())
const editForm = reactive(defaultEditForm())
const actionForm = reactive(defaultActionForm())

const isEditing = computed(() => Boolean(editForm.id))
const passwordFieldLabel = computed(() => (isEditing.value ? '登录密码（选填）' : '登录密码'))

const editRules = {
  title: [{ required: true, message: '请输入商家名称', trigger: 'blur' }],
  username: [{ required: true, message: '请输入商家账号', trigger: 'blur' }],
  password: [
    {
      validator: (_rule, value, callback) => {
        if (!isEditing.value && !String(value || '').trim()) {
          callback(new Error('请输入登录密码'))
          return
        }
        callback()
      },
      trigger: 'blur'
    }
  ]
}

const statusCards = computed(() => {
  const states = Array.isArray(params.value.auth_states) ? params.value.auth_states : []
  const counts = rows.value.length ? rows.value[0]?.status_nums : null
  const statusNums = currentStatusNums.value

  return [
    { value: -1, label: '全部商家', count: Number(statusNums.all || pagination.count || 0) },
    ...states.map((item) => ({
      value: Number(item.value),
      label: item.label,
      count: Number(statusNums[item.code] || 0)
    }))
  ]
})

const expireOptions = computed(() => {
  if (Array.isArray(params.value.expire_statuses) && params.value.expire_statuses.length) {
    return params.value.expire_statuses
  }
  return [
    { value: 'normal', label: '正常' },
    { value: 'expiring', label: '即将到期' },
    { value: 'expired', label: '已到期' }
  ]
})

const currentStatusNums = computed(() => params.value.status_nums || {})

const runtimeModeLabel = computed(() =>
  process.env.NODE_ENV === 'production' ? '生产构建' : '开发构建'
)

const selectedSummary = computed(() => {
  return selectedRows.value
    .map((item) => `${merchantDisplayTitle(item)} (${item.id})`)
    .join('\n')
})

const activeFilterTags = computed(() => {
  const tags = []
  const statusCard = statusCards.value.find((item) => item.value === Number(query.auth_state))
  if (statusCard) {
    tags.push(`审核状态：${statusCard.label}`)
  }
  if (query.expire_status) {
    const expireOption = expireOptions.value.find(
      (item) => String(item.value) === String(query.expire_status)
    )
    tags.push(`期限状态：${expireOption ? expireOption.label : query.expire_status}`)
  }
  if (query.search_value?.trim()) {
    tags.push(`检索：${query.search_value.trim()}`)
  }
  if (Array.isArray(query.date_value) && query.date_value.length === 2) {
    tags.push(`添加时间：${query.date_value[0]} 至 ${query.date_value[1]}`)
  }
  return tags
})

const merchantSummaryHintTitle = computed(() => {
  if (
    selectedRows.value.some(
      (item) => String(item.expire_status) === 'expired' || Number(item.is_expired || 0) === 1
    )
  ) {
    return '优先处理到期商家'
  }
  if (selectedRows.value.some((item) => Number(item.auth_state) !== 1)) {
    return '优先复核审核状态'
  }
  if (selectedRows.value.some((item) => Number(item.is_disable) === 1)) {
    return '注意禁用商家恢复'
  }
  if (selectedRows.value.length) {
    return '已勾选待处理商家'
  }
  if (activeFilterTags.value.length) {
    return '当前列表已收敛'
  }
  return '当前为默认巡检视角'
})

const merchantSummaryHintText = computed(() => {
  if (
    selectedRows.value.some(
      (item) => String(item.expire_status) === 'expired' || Number(item.is_expired || 0) === 1
    )
  ) {
    return '当前勾选中包含已到期商家，建议优先核对续费月数、续费天数和提醒天数，再执行续费。'
  }
  if (selectedRows.value.some((item) => Number(item.auth_state) !== 1)) {
    return '当前勾选中包含待审核或已驳回商家，提交前请再次核对审核意见和商家联系人信息。'
  }
  if (selectedRows.value.some((item) => Number(item.is_disable) === 1)) {
    return '当前勾选中包含已禁用商家，如需重新启用，请先确认是否需要同步恢复商家商品。'
  }
  if (selectedRows.value.length) {
    return `当前已勾选 ${selectedRows.value.length} 家商家，可继续执行审核、续费、禁用或删除操作。`
  }
  if (activeFilterTags.value.length) {
    return '当前商家列表已经按筛选条件收敛，建议先抽查详情和续费记录，再继续批量治理。'
  }
  return '建议先按审核状态、期限状态和添加时间缩小范围，再处理批量审核、续费和禁用操作。'
})

const recentActionSummary = computed(() => {
  if (!recentAction.value) {
    return ''
  }
  return `${recentAction.value.label} · ${recentAction.value.time}`
})

const merchantSummaryHintClass = computed(() => {
  if (
    selectedRows.value.some(
      (item) => String(item.expire_status) === 'expired' || Number(item.is_expired || 0) === 1
    ) ||
    selectedRows.value.some((item) => Number(item.auth_state) !== 1)
  ) {
    return 'is-warning'
  }
  if (selectedRows.value.length || activeFilterTags.value.length) {
    return 'is-active'
  }
  return 'is-safe'
})

const merchantFollowupHint = computed(() => {
  if (selectedRows.value.some((item) => Number(item.auth_state) !== 1)) {
    return '当前勾选里有待审核或已驳回商家，先处理审核，再决定是否继续续费或禁用。'
  }
  if (
    selectedRows.value.some(
      (item) => String(item.expire_status) === 'expired' || Number(item.is_expired || 0) === 1
    )
  ) {
    return '当前勾选里有已到期商家，建议先去看续费记录和续费动作，再回到列表做治理。'
  }
  if (activeFilterTags.value.length) {
    return '当前列表已经收窄，适合先抽查商家详情；如果发现异常像接盘流转问题，再切去内部对账页。'
  }
  return '这页适合先做商家筛选和批量治理；如果想看整体波动去运营报表，如果想追内部号承接就去内部接盘对账。'
})

const merchantFollowupTags = computed(() => {
  const tags = [
    `已选 ${selectedRows.value.length} 家`,
    `待审核 ${Number(currentStatusNums.value.pending || 0)} 家`,
    `已到期 ${Number(currentStatusNums.value.expired || 0)} 家`
  ]
  if (activeFilterTags.value.length) {
    tags.push(`筛选 ${activeFilterTags.value.length} 项`)
  }
  return tags
})

function parseRouteNumber(value, fallback = undefined) {
  if (value === undefined || value === null || value === '') return fallback
  const next = Number(value)
  return Number.isNaN(next) ? fallback : next
}

function parseRouteDateRange(value, startDate, endDate) {
  if (Array.isArray(value) && value.length === 2) {
    return value.map((item) => String(item))
  }
  if (typeof value === 'string' && value.includes(',')) {
    const parts = value
      .split(',')
      .map((item) => item.trim())
      .filter(Boolean)
    if (parts.length === 2) {
      return parts
    }
  }
  if (startDate && endDate) {
    return [String(startDate), String(endDate)]
  }
  return []
}

onMounted(async () => {
  await loadParams()
  applyRouteQuery()
  await fetchList()
  await handleRouteFocus()
})

watch(
  () => route.fullPath,
  async () => {
    applyRouteQuery()
    await fetchList()
    await handleRouteFocus()
  }
)

async function loadParams() {
  try {
    const res = await getParamsApi({})
    params.value = res.data || {}
  } catch (error) {
    ElMessage.error('加载商家参数失败')
  }
}

function buildQueryParams() {
  const paramsValue = {
    page: query.page,
    limit: query.limit,
    date_field: query.date_field
  }

  if (query.auth_state !== -1) {
    paramsValue.auth_state = query.auth_state
  }
  if (query.expire_status) {
    paramsValue.expire_status = query.expire_status
  }
  if (query.search_value?.trim()) {
    paramsValue.search_field = query.search_field
    paramsValue.search_exp = query.search_exp
    paramsValue.search_value = query.search_value.trim()
  }
  if (Array.isArray(query.date_value) && query.date_value.length === 2) {
    paramsValue.date_value = query.date_value
  }

  return paramsValue
}

function applyRouteQuery() {
  const routeQuery = route.query || {}
  const nextQuery = defaultQuery()
  nextQuery.limit = query.limit
  nextQuery.page = parseRouteNumber(routeQuery.page, 1)
  nextQuery.date_field = routeQuery.date_field ? String(routeQuery.date_field) : nextQuery.date_field
  nextQuery.auth_state = parseRouteNumber(routeQuery.auth_state, -1)
  nextQuery.expire_status = routeQuery.expire_status ? String(routeQuery.expire_status) : ''
  nextQuery.search_field = routeQuery.search_field ? String(routeQuery.search_field) : nextQuery.search_field
  nextQuery.search_exp = routeQuery.search_exp ? String(routeQuery.search_exp) : nextQuery.search_exp
  nextQuery.search_value = routeQuery.search_value ? String(routeQuery.search_value) : ''
  nextQuery.date_value = parseRouteDateRange(
    routeQuery.date_value,
    routeQuery.start_date,
    routeQuery.end_date
  )
  Object.assign(query, nextQuery)
}

function buildContinuityQuery(extraQuery = {}) {
  const nextQuery = {
    ...route.query,
    from: 'merchant-list',
    page: String(query.page),
    limit: String(query.limit),
    date_field: query.date_field
  }

  if (query.auth_state !== -1) {
    nextQuery.auth_state = String(query.auth_state)
  } else {
    delete nextQuery.auth_state
  }
  if (query.expire_status) {
    nextQuery.expire_status = String(query.expire_status)
  } else {
    delete nextQuery.expire_status
  }
  if (query.search_value?.trim()) {
    nextQuery.search_field = query.search_field
    nextQuery.search_exp = query.search_exp
    nextQuery.search_value = query.search_value.trim()
  } else {
    delete nextQuery.search_field
    delete nextQuery.search_exp
    delete nextQuery.search_value
  }
  if (Array.isArray(query.date_value) && query.date_value.length === 2) {
    nextQuery.date_value = query.date_value
    nextQuery.start_date = query.date_value[0]
    nextQuery.end_date = query.date_value[1]
  } else {
    delete nextQuery.date_value
    delete nextQuery.start_date
    delete nextQuery.end_date
  }

  return {
    ...nextQuery,
    ...extraQuery
  }
}

async function fetchList() {
  loading.value = true
  try {
    const res = await listApi(buildQueryParams())
    rows.value = res.data?.list || []
    pagination.count = Number(res.data?.count || 0)
    params.value = {
      ...params.value,
      ...(res.data || {})
    }
  } catch (error) {
    ElMessage.error('加载商家列表失败')
  } finally {
    loading.value = false
  }
}

async function handleRouteFocus() {
  const targetId = Number(route.query.id || 0)
  const focusMode = String(route.query.focus_mode || 'edit')
  if (!targetId) return

  const focusKey = `${targetId}:${focusMode}`
  if (lastHandledFocusKey.value === focusKey) return
  lastHandledFocusKey.value = focusKey

  const targetRow = rows.value.find((item) => Number(item.id) === targetId) || { id: targetId }

  if (focusMode === 'detail') {
    await openDetail(targetRow)
  } else {
    await openEdit(targetRow)
  }

  ElMessage.success(`已定位到商家 #${targetId}，可以继续处理。`)

  const nextQuery = { ...route.query }
  delete nextQuery.id
  delete nextQuery.focus_mode
  await router.replace({
    path: route.path,
    query: nextQuery
  })
}

function search() {
  query.page = 1
  fetchList()
}

function resetQuery() {
  const limit = query.limit
  Object.assign(query, defaultQuery(), { limit })
  fetchList()
}

function switchStatus(value) {
  query.auth_state = value
  query.page = 1
  fetchList()
}

function handleSelectionChange(selection) {
  selectedRows.value = selection
}

function setRecentAction(label) {
  recentAction.value = {
    label,
    time: new Date().toLocaleString('zh-CN', { hour12: false })
  }
}

function firstFilled(...values) {
  return values.map((item) => String(item || '').trim()).find(Boolean) || ''
}

function merchantDisplayTitle(row = {}) {
  return (
    firstFilled(
      row.title,
      row.merchant_title,
      row.shop_name,
      row.company_name,
      row.name,
      row.username,
      row.phone
    ) || `商家 #${row.id || '--'}`
  )
}

function merchantReceiptUrl(row = {}) {
  return firstFilled(row.receipt_image_url, row.image_url, row.file_url)
}

function merchantInitial(row = {}) {
  return merchantDisplayTitle(row).slice(0, 1).toUpperCase()
}

function resetEditForm() {
  Object.assign(editForm, defaultEditForm())
}

function clearReceiptImage() {
  editForm.image_id = 0
  editForm.image_url = ''
  editForm.receipt_image_url = ''
}

function buildEditPayload() {
  const payload = {
    title: editForm.title,
    name: editForm.name,
    phone: editForm.phone,
    address: editForm.address,
    username: editForm.username,
    remark: editForm.remark,
    sort: Number(editForm.sort || 0),
    expire_time: editForm.expire_time || '',
    renew_remind_days: Number(editForm.renew_remind_days || 0),
    image_id: Number(editForm.image_id || 0),
    images: Array.isArray(editForm.images) ? editForm.images : []
  }

  if (editForm.id) {
    payload.id = editForm.id
  }
  if (String(editForm.password || '').trim()) {
    payload.password = editForm.password
  }

  return payload
}

function resetActionForm() {
  Object.assign(actionForm, defaultActionForm())
}

function openCreate() {
  resetEditForm()
  editTitle.value = '新建商家'
  editVisible.value = true
}

async function openEdit(row) {
  try {
    const res = await infoApi({ id: row.id })
    resetEditForm()
    Object.assign(editForm, res.data || {})
    editTitle.value = `编辑商家：${row.id}`
    editVisible.value = true
  } catch (error) {
    ElMessage.error('加载商家信息失败')
  }
}

async function submitEdit() {
  try {
    await editFormRef.value.validate()
  } catch (error) {
    return
  }

  submitting.value = true
  try {
    const api = editForm.id ? editApi : addApi
    const res = await api(buildEditPayload())
    ElMessage.success(res.msg || '保存成功')
    setRecentAction(
      `${editForm.id ? '商家编辑' : '商家新建'}已提交：${editForm.title || '未命名商家'}`
    )
    editVisible.value = false
    await fetchList()
  } catch (error) {
    ElMessage.error('商家保存失败')
  } finally {
    submitting.value = false
  }
}

async function openDetail(row) {
  detailVisible.value = true
  detailLoading.value = true
  try {
    const res = await infoApi({ id: row.id })
    detailRow.value = res.data || row
  } catch (error) {
    ElMessage.error('加载商家详情失败')
  } finally {
    detailLoading.value = false
  }
}

function ensureSelection(singleRows = []) {
  if (singleRows.length) {
    selectedRows.value = singleRows
    return true
  }
  if (!selectedRows.value.length) {
    ElMessage.warning('请先选择需要操作的商家')
    return false
  }
  return true
}

function openDisableDialog(singleRows = []) {
  if (!ensureSelection(singleRows)) return
  resetActionForm()
  actionType.value = 'disable'
  actionTitle.value =
    Number((singleRows[0] || selectedRows.value[0]).is_disable) === 1 ? '启用商家' : '停用商家'
  actionForm.is_disable = Number((singleRows[0] || selectedRows.value[0]).is_disable) === 1 ? 0 : 1
  actionVisible.value = true
}

function openAuthDialog(singleRows = []) {
  if (!ensureSelection(singleRows)) return
  resetActionForm()
  actionType.value = 'auth'
  actionTitle.value = '商家审核'
  actionVisible.value = true
}

function openRenewDialog(singleRows = []) {
  if (!ensureSelection(singleRows)) return
  resetActionForm()
  actionType.value = 'renew'
  actionTitle.value = '商家续费'
  actionForm.renew_remind_days = Number(
    (singleRows[0] || selectedRows.value[0]).renew_remind_days || 7
  )
  actionVisible.value = true
}

function openDeleteDialog(singleRows = []) {
  if (!ensureSelection(singleRows)) return
  resetActionForm()
  actionType.value = 'delete'
  actionTitle.value = '删除商家'
  actionVisible.value = true
}

async function submitAction() {
  const ids = selectedRows.value.map((item) => item.id)
  if (!ids.length) {
    ElMessage.warning('请先选择需要操作的商家')
    return
  }

  if (
    actionType.value === 'renew' &&
    Number(actionForm.renew_months || 0) <= 0 &&
    Number(actionForm.renew_days || 0) <= 0
  ) {
    ElMessage.warning('请至少填写一个大于 0 的续费月数或续费天数')
    return
  }

  actionLoading.value = true
  try {
    if (actionType.value === 'disable') {
      await disableApi({
        ids,
        is_disable: actionForm.is_disable,
        sync_goods_disable: actionForm.is_disable === 1 ? actionForm.sync_goods_disable : 0
      })
    } else if (actionType.value === 'auth') {
      await authApi({
        ids,
        auth_state: actionForm.auth_state,
        auth_msg: actionForm.auth_msg
      })
    } else if (actionType.value === 'renew') {
      await renewApi({
        ids,
        renew_months: Number(actionForm.renew_months || 0),
        renew_days: Number(actionForm.renew_days || 0),
        amount: Number(actionForm.amount || 0),
        renew_remind_days: Number(actionForm.renew_remind_days || 7),
        remark: actionForm.remark || ''
      })
    } else if (actionType.value === 'delete') {
      await deleteApi({ ids })
    }

    ElMessage.success('操作成功')
    if (actionType.value === 'disable') {
      setRecentAction(
        `${Number(actionForm.is_disable) === 1 ? '商家禁用' : '商家启用'}已提交：${ids.length} 家`
      )
    } else if (actionType.value === 'auth') {
      setRecentAction(`商家审核已提交：${ids.length} 家`)
    } else if (actionType.value === 'renew') {
      setRecentAction(`商家续费已提交：${ids.length} 家`)
    } else if (actionType.value === 'delete') {
      setRecentAction(`商家删除已提交：${ids.length} 家`)
    }
    actionVisible.value = false
    await fetchList()
  } catch (error) {
    ElMessage.error('操作失败')
  } finally {
    actionLoading.value = false
  }
}

async function handleDisableSwitch(row) {
  try {
    await ElMessageBox.confirm(
      `确认要${Number(row.is_disable) === 1 ? '启用' : '停用'}商家「${row.title || `#${row.id}` }」吗？`,
      '操作确认',
      {
        type: 'warning',
        confirmButtonText: '继续',
        cancelButtonText: '取消'
      }
    )
    openDisableDialog([row])
  } catch (error) {
    // 用户取消后保持列表原状态，不做提交
  }
}

async function openRenewRecords(singleRows = []) {
  if (!ensureSelection(singleRows)) return
  recordVisible.value = true
  recordLoading.value = true
  try {
    const paramsValue =
      selectedRows.value.length === 1
        ? { merchant_id: selectedRows.value[0].id, page: 1, limit: 20 }
        : { ids: selectedRows.value.map((item) => item.id), page: 1, limit: 20 }
    const res = await renewRecordList(paramsValue)
    renewRecords.value = res.data?.list || []
  } catch (error) {
    ElMessage.error('加载续费记录失败')
  } finally {
    recordLoading.value = false
  }
}

function goToMerchantAnalytics() {
  const selectedMerchantId =
    selectedRows.value.length === 1 ? Number(selectedRows.value[0]?.id || 0) : 0
  router.push({
    path: '/analytics',
    query: buildContinuityQuery(
      selectedMerchantId > 0 ? { merchant_id: String(selectedMerchantId) } : {}
    )
  })
}

function goToInternalTakeover() {
  const selectedMerchantId =
    selectedRows.value.length === 1 ? Number(selectedRows.value[0]?.id || 0) : 0
  router.push({
    path: '/report/internal-takeover',
    query: buildContinuityQuery(
      selectedMerchantId > 0 ? { source_merchant_id: String(selectedMerchantId) } : {}
    )
  })
}

function goToRenewRecordsPage() {
  if (selectedRows.value.length === 1) {
    openRenewRecords([selectedRows.value[0]])
    return
  }
  openRenewRecords()
}

function goToPlatformVoucherSetting() {
  router.push({
    path: '/system/setting',
    query: {
      from: 'merchant-list',
      tab: 'systemInfo',
      focus: 'platform_voucher'
    }
  })
}

function authTagType(value) {
  if (Number(value) === 1) return 'success'
  if (Number(value) === 2) return 'danger'
  return 'warning'
}

function authStateLabel(value) {
  if (Number(value) === 1) return '审核通过'
  if (Number(value) === 2) return '审核拒绝'
  return '待审核'
}

function expireTagType(row) {
  if (String(row.expire_status) === 'expired' || Number(row.is_expired || 0) === 1) return 'danger'
  if (String(row.expire_status) === 'expiring' || Number(row.should_remind || 0) === 1)
    return 'warning'
  return 'success'
}

function expireStateLabel(row) {
  if (String(row.expire_status) === 'expired' || Number(row.is_expired || 0) === 1) return '已到期'
  if (String(row.expire_status) === 'expiring' || Number(row.should_remind || 0) === 1)
    return '即将到期'
  return '正常'
}

function formatCount(value) {
  return Number(value || 0).toLocaleString('zh-CN')
}

function formatAmount(value) {
  return Number(value || 0).toFixed(2)
}
</script>

<style lang="scss" scoped>
.merchant-page {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.panel {
  border: none;
  border-radius: 20px;
  box-shadow: 0 18px 45px rgba(15, 23, 42, 0.06);
}

.panel--hero {
  background: radial-gradient(circle at right top, rgba(59, 130, 246, 0.1), transparent 22%),
    linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
}

.panel__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
}

.panel__title {
  font-size: 22px;
  font-weight: 700;
  color: #0f172a;
}

.panel__desc {
  margin-top: 6px;
  font-size: 13px;
  color: #64748b;
}

.panel__actions {
  display: flex;
  gap: 10px;
  align-items: center;
}

.status-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 12px;
  margin-top: 18px;
}

.status-card {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 10px;
  padding: 16px 18px;
  cursor: pointer;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 18px;
  transition: all 0.2s ease;
}

.status-card:hover,
.status-card--active {
  background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
  border-color: rgba(59, 130, 246, 0.36);
  box-shadow: 0 14px 28px rgba(37, 99, 235, 0.14);
}

.status-card__label {
  font-size: 13px;
  color: #475569;
}

.status-card__count {
  font-size: 28px;
  font-weight: 700;
  color: #0f172a;
}

.filter-form {
  margin-top: 18px;
}

.merchant-summary-bar {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 14px;
  margin-top: 14px;
}

.merchant-summary-bar__chips {
  display: flex;
  flex: 1;
  flex-wrap: wrap;
  gap: 8px;
}

.summary-chip {
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  padding: 0 10px;
  border-radius: 999px;
  font-size: 12px;
  background: #f5f7fb;
  color: #4a5670;
}

.summary-chip--primary {
  background: #e8f0ff;
  color: #1d4ed8;
  font-weight: 600;
}

.summary-chip--muted {
  color: #64748b;
}

.merchant-summary-bar__hint {
  width: 320px;
  padding: 12px 14px;
  border-radius: 12px;
  border: 1px solid #e7ecf5;
  background: #fafcff;
}

.merchant-summary-bar__hint.is-warning {
  border-color: #f3ddab;
  background: #fff9f0;
}

.merchant-summary-bar__hint.is-active {
  border-color: #dbe7f5;
  background: #f8fbff;
}

.merchant-summary-bar__hint-title {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: #1f2329;
}

.merchant-summary-bar__hint-text {
  display: block;
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.6;
  color: #5f6b7a;
}

.followup-panel {
  margin-top: 16px;
  padding: 16px 18px;
  background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
  border: 1px solid rgba(219, 234, 254, 0.95);
  border-radius: 16px;
}

.followup-panel__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.followup-panel__title {
  font-size: 15px;
  font-weight: 700;
  color: #0f172a;
}

.followup-panel__desc {
  margin-top: 6px;
  font-size: 13px;
  line-height: 1.7;
  color: #64748b;
}

.followup-panel__tags {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 8px;
}

.followup-panel__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 14px;
}

.toolbar {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 16px;
}

.toolbar__left {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.toolbar__right {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 14px;
  font-size: 12px;
  color: #64748b;
}

.merchant-cell {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  min-width: 0;
}

.merchant-cell__media {
  flex: 0 0 auto;
}

.merchant-cell__body {
  display: flex;
  min-width: 0;
  flex: 1;
  flex-direction: column;
  gap: 6px;
}

.merchant-cell__placeholder {
  display: flex;
  width: 54px;
  height: 54px;
  align-items: center;
  justify-content: center;
  border: 1px solid #dbeafe;
  border-radius: 14px;
  background: linear-gradient(135deg, #eef6ff 0%, #f8fbff 100%);
  color: #2563eb;
  font-size: 18px;
  font-weight: 800;
}

.receipt-preview-card,
.receipt-editor {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.receipt-preview-card__image {
  width: 160px;
}

.receipt-editor__tip {
  padding: 10px 12px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
  background: rgba(248, 250, 252, 0.92);
  border: 1px solid rgba(148, 163, 184, 0.16);
  border-radius: 12px;
}

.receipt-editor__meta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.merchant-cell__title {
  max-width: 230px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-weight: 700;
  color: #0f172a;
}

.thumb {
  flex: 0 0 auto;
  width: 54px;
  height: 54px;
  overflow: hidden;
  border-radius: 14px;
  border: 1px solid #e2e8f0;
}

.merchant-cell__meta {
  display: flex;
  flex-wrap: wrap;
  gap: 6px 12px;
  color: #64748b;
  font-size: 12px;
  line-height: 1.4;
}

.merchant-cell__tags {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.stack {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.muted {
  font-size: 12px;
  color: #64748b;
}

.actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0 6px;
}

.merchant-table :deep(.el-table__cell) {
  padding: 8px 0;
}

.merchant-table :deep(.el-table__body .cell) {
  line-height: 1.45;
}

.pagination {
  display: flex;
  justify-content: flex-end;
  margin-top: 16px;
}

.detail-wrap {
  min-height: 220px;
}

.edit-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0 16px;
}

.inline-switch {
  display: flex;
  align-items: center;
  gap: 12px;
}

.danger-tip {
  padding: 12px 14px;
  color: #b91c1c;
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 14px;
}

@media (max-width: 900px) {
  .panel__header,
  .toolbar,
  .merchant-summary-bar {
    flex-direction: column;
    align-items: stretch;
  }

  .edit-grid {
    grid-template-columns: 1fr;
  }

  .merchant-summary-bar__hint {
    width: auto;
  }

  .followup-panel__header {
    flex-direction: column;
  }

  .followup-panel__tags {
    justify-content: flex-start;
  }
}
</style>
