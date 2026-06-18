<template>
  <div class="app-container">
    <el-card class="app-head">
      <div class="section-title-row">
        <div>
          <div class="section-title-row__title">快递公司</div>
          <div class="section-title-row__desc">
            统一处理快递资料、启停维护和批量删除，默认首屏直接进入筛选与列表。
          </div>
        </div>
        <div class="section-title-row__meta">{{ runtimeEnvInfo.label }}</div>
      </div>
      <div v-if="entryContextVisible" class="entry-context-banner">
        <div class="entry-context-banner__main">
          <div class="entry-context-banner__eyebrow">外部入口承接</div>
          <div class="entry-context-banner__title">{{ entryContextTitle }}</div>
          <div class="entry-context-banner__desc">{{ entryContextDesc }}</div>
        </div>
        <div class="entry-context-banner__actions">
          <el-button type="primary" @click="handleEntryContextPrimary">{{
            entryContextPrimaryLabel
          }}</el-button>
          <el-button @click="goToEntryContextBack">回来源页</el-button>
        </div>
      </div>
      <!-- 查询 -->
      <el-form :model="query" ref="searchForm" label-width="85px">
        <el-row :gutter="20">
          <el-col :span="6">
            <el-form-item label="添加时间：">
              <el-date-picker
                v-model="query.date_value"
                ref="datePicker"
                type="daterange"
                class="ya-date-value"
                start-placeholder="开始日期"
                end-placeholder="结束日期"
                value-format="YYYY-MM-DD"
                :shortcuts="shortcuts"
                :default-time="[new Date(2024, 1, 1, 0, 0, 0), new Date(2024, 1, 1, 23, 59, 59)]"
                @change="search()"
              />
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="状态：" prop="is_disable">
              <el-select v-model="query.is_disable" @change="search()" clearable>
                <el-option :value="0" label="启用" />
                <el-option :value="1" label="禁用" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-input
              v-model="query.search_value"
              placeholder="请输入内容"
              class="input-with-select"
              @keyup.enter="search()"
              clearable
            >
              <template #prepend>
                <el-select v-model="query.search_field" placeholder="Select" style="width: 100px">
                  <el-option :value="idkey" label="ID" />
                  <el-option value="title" label="名称" />
                  <el-option value="code" label="编码" />
                </el-select>
              </template>
            </el-input>
          </el-col>
          <el-col :span="6">
            <el-button type="primary" @click="search()">搜索</el-button>
            <el-button title="重置" @click="refresh()"> 重置 </el-button>
          </el-col>
        </el-row>
      </el-form>
      <div class="delivery-summary-bar">
        <div class="delivery-summary-bar__chips">
          <span class="summary-chip summary-chip--primary">总数 {{ count }}</span>
          <span class="summary-chip">已选 {{ selection.length }} 项</span>
          <span class="summary-chip">
            {{
              query.is_disable === undefined
                ? '全部状态'
                : query.is_disable === 1
                ? '禁用中'
                : '启用中'
            }}
          </span>
          <span class="summary-chip">{{ runtimeEnvInfo.dataMode }}</span>
          <span v-for="item in activeFilterTags" :key="item" class="summary-chip">{{ item }}</span>
          <span v-if="!activeFilterTags.length" class="summary-chip">默认条件：全部快递公司</span>
          <span v-if="recentActionSummary" class="summary-chip summary-chip--muted">{{
            recentActionSummary
          }}</span>
        </div>
        <div class="delivery-summary-bar__hint" :class="summaryHintClass">
          <span class="delivery-summary-bar__hint-title">{{ summaryHintTitle }}</span>
          <span class="delivery-summary-bar__hint-text">{{ selectRiskHint }}</span>
        </div>
      </div>
      <div class="plain-guide">
        <div class="plain-guide__header">
          <div>
            <div class="plain-guide__title">这页建议先这样看</div>
            <div class="plain-guide__desc">
              先判断你是在查“还能不能用的快递公司”，还是在做“禁用、删除、编码治理”，不要一上来直接批量处理。
            </div>
          </div>
          <span class="plain-guide__badge">{{ deliveryFocusLabel }}</span>
        </div>
        <div class="plain-guide__grid">
          <div v-for="item in deliveryGuideCards" :key="item.title" class="plain-guide-card">
            <div class="plain-guide-card__title">{{ item.title }}</div>
            <div class="plain-guide-card__desc">{{ item.desc }}</div>
            <div class="plain-guide-card__action">{{ item.action }}</div>
          </div>
        </div>
      </div>
      <div class="followup-panel">
        <div class="followup-panel__main">
          <div class="followup-panel__title">看完快递后继续去哪</div>
          <div class="followup-panel__desc">{{ followupHint }}</div>
          <div class="followup-panel__tags">
            <span v-for="item in followupTags" :key="item">{{ item }}</span>
          </div>
        </div>
        <div class="followup-panel__actions">
          <el-button type="primary" @click="goToPage('/order/order')">去订单管理</el-button>
          <el-button @click="goToPage('/setting/warehouse')">去仓库管理</el-button>
          <el-button @click="goToPage('/setting/region')">去地区管理</el-button>
        </div>
      </div>
    </el-card>
    <el-dialog
      v-model="selectDialog"
      :title="selectTitle"
      :close-on-click-modal="false"
      :close-on-press-escape="false"
      top="20vh"
    >
      <el-form ref="selectRef" label-width="120px">
        <el-form-item v-if="selectType === 'disable'" label="是否禁用">
          <el-radio-group v-model="is_disable">
            <el-radio :value="0">启用</el-radio>
            <el-radio :value="1">禁用</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item v-else-if="selectType === 'dele'">
          <span class="c-red">确定要删除选中的{{ name }}吗？</span>
        </el-form-item>
        <el-form-item :label="name + 'ID'">
          <el-input v-model="selectIds" type="textarea" autosize disabled />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="selectCancel">取消</el-button>
        <el-button type="primary" @click="selectSubmit">提交</el-button>
      </template>
    </el-dialog>
    <el-card class="app-main">
      <div class="section-title-row section-title-row--content">
        <div>
          <div class="section-title-row__title">快递公司列表</div>
          <div class="section-title-row__desc">支持基础资料维护、批量禁用和删除处理。</div>
        </div>
        <div class="section-title-row__meta">已选 {{ selection.length }} 项</div>
      </div>
      <div class="operation_btn mb5">
        <!-- 操作 -->
        <div>
          <div class="action-cluster">
            <span class="action-cluster__title">基础维护</span>
            <el-button type="primary" @click="add()">添加</el-button>
            <el-button title="是否禁用" @click="selectOpen('disable')">禁用</el-button>
          </div>
          <div class="action-cluster">
            <span class="action-cluster__title">批量处理</span>
            <el-button title="删除" @click="selectOpen('dele')">删除</el-button>
          </div>
        </div>
        <div>
          <!-- 分页 -->
          <pagination
            v-show="count > 0"
            v-model:total="count"
            v-model:page="query.page"
            v-model:limit="query.limit"
            @pagination="list"
          />
        </div>
      </div>
      <!-- 列表 -->
      <el-table
        ref="table"
        v-loading="loading"
        :data="data"
        @sort-change="sort"
        @selection-change="select"
      >
        <el-table-column type="selection" width="42" title="全选/反选" />
        <el-table-column :prop="idkey" label="ID" width="80" sortable="custom" />
        <el-table-column prop="code" label="编码" min-width="130" show-overflow-tooltip />
        <el-table-column prop="title" label="名称" min-width="130" show-overflow-tooltip />
        <el-table-column prop="is_disable" label="禁用" min-width="85" sortable="custom">
          <template #default="scope">
            <el-switch
              v-model="scope.row.is_disable"
              :active-value="1"
              :inactive-value="0"
              @change="confirmRowDisable(scope.row)"
            />
          </template>
        </el-table-column>
        <el-table-column prop="sort" label="排序" min-width="85" sortable="custom" />
        <el-table-column prop="create_time" label="添加时间" width="165" sortable="custom" />
        <el-table-column prop="update_time" label="修改时间" width="165" sortable="custom" />
        <el-table-column label="操作" width="130">
          <template #default="scope">
            <el-link type="primary" class="mr-1" :underline="false" @click="edit(scope.row)">
              修改
            </el-link>
            <el-link type="primary" :underline="false" @click="selectOpen('dele', [scope.row])">
              删除
            </el-link>
          </template>
        </el-table-column>
      </el-table>

      <!-- 添加修改 -->
      <el-dialog
        v-model="dialog"
        :title="dialogTitle"
        :close-on-click-modal="false"
        :close-on-press-escape="false"
        :before-close="cancel"
        top="10vh"
      >
        <el-scrollbar class="mt5" native :max-height="height - 30">
          <el-form ref="ref" :rules="rules" :model="model" label-width="100px">
            <el-form-item label="编码" prop="code">
              <el-input v-model="model.code" placeholder="请输入名称" clearable />
            </el-form-item>
            <el-form-item label="名称" prop="title">
              <el-input v-model="model.title" placeholder="请输入名称" clearable />
            </el-form-item>
            <el-form-item label="排序" prop="sort">
              <el-input v-model="model.sort" type="number" placeholder="" />
            </el-form-item>
            <el-form-item v-if="model[idkey]" label="添加时间" prop="create_time">
              <el-input v-model="model.create_time" disabled />
            </el-form-item>
            <el-form-item v-if="model[idkey]" label="修改时间" prop="update_time">
              <el-input v-model="model.update_time" disabled />
            </el-form-item>
            <el-form-item v-if="model.delete_time" label="删除时间" prop="delete_time">
              <el-input v-model="model.delete_time" disabled />
            </el-form-item>
          </el-form>
        </el-scrollbar>
        <template #footer>
          <el-button :loading="loading" @click="cancel">取消</el-button>
          <el-button :loading="loading" type="primary" @click="submit">提交</el-button>
        </template>
      </el-dialog>
    </el-card>
  </div>
</template>

<script>
import screenHeight from '@/utils/screen-height'
import Pagination from '@/components/Pagination/index.vue'
import { arrayColumn } from '@/utils/index'
import { getPageLimit } from '@/utils/settings'
import { list, info, add, edit, dele, disable } from '@/api/setting/delivery.js'
import { shortcuts } from '@/utils/getDate.js'
import { getAdminRuntimeHint, resolveAdminRuntimeEnv } from '@/utils/runtime-env'

export default {
  name: 'SettingDelivery',
  components: { Pagination },
  data() {
    return {
      name: '快递公司',
      height: 680,
      loading: false,
      idkey: 'id',
      exps: [{ exp: 'like', name: '包含' }],
      query: {
        page: 1,
        limit: getPageLimit(),
        search_field: 'title',
        search_exp: 'like',
        date_field: 'create_time',
        is_disable: undefined
      },
      data: [],
      count: 0,
      dialog: false,
      dialogTitle: '',
      model: {
        id: '',
        title: '',
        code: '',
        remark: '',
        sort: 250
      },
      rules: {
        title: [{ required: true, message: '请输入名称', trigger: 'blur' }],
        code: [{ required: true, message: '请输入编码', trigger: 'blur' }]
      },
      selection: [],
      selectIds: '',
      selectTitle: '操作',
      selectDialog: false,
      selectType: '',
      is_disable: 0,
      shortcuts: shortcuts(),
      recentActionSummary: '',
      runtimeEnvInfo: resolveAdminRuntimeEnv()
    }
  },
  computed: {
    runtimeHint() {
      return getAdminRuntimeHint(this.runtimeEnvInfo)
    },
    entrySourceLabel() {
      const source = String(this.$route?.query?.from || '')
      if (source === 'dashboard') return '来自控制台总览'
      if (source === 'setting-region') return '来自地区管理'
      if (source === 'setting-warehouse') return '来自仓库管理'
      return ''
    },
    entryContextVisible() {
      return Boolean(this.entrySourceLabel)
    },
    entryContextTitle() {
      if (this.entrySourceLabel === '来自控制台总览') return '当前从控制台进入快递公司'
      if (this.entrySourceLabel === '来自地区管理') return '当前从地区管理进入快递公司'
      if (this.entrySourceLabel === '来自仓库管理') return '当前从仓库管理进入快递公司'
      return '当前为外部入口承接视角'
    },
    entryContextDesc() {
      if (this.entrySourceLabel === '来自控制台总览') {
        return '这类进入通常是为了排发货失败、物流不可选或履约异常。建议先看启用状态和编码，再回订单页核发货弹窗。'
      }
      if (this.entrySourceLabel === '来自地区管理') {
        return '这类进入通常是因为履约区域调整后，需要继续核物流承接。建议先看禁用状态和资料完整度，再回地区页复核下游影响。'
      }
      if (this.entrySourceLabel === '来自仓库管理') {
        return '这类进入通常是因为仓库履约链路里出现物流问题。建议先核快递可用性，再回仓库页看地址和归属。'
      }
      return ''
    },
    entryContextPrimaryLabel() {
      if (this.entrySourceLabel === '来自地区管理') return '回地区管理'
      if (this.entrySourceLabel === '来自仓库管理') return '回仓库管理'
      return '去订单管理复核'
    },
    activeFilterTags() {
      const tags = []
      if (this.query.date_value?.length === 2) {
        tags.push(`添加时间：${this.query.date_value[0]} 至 ${this.query.date_value[1]}`)
      }
      if (this.query.is_disable !== undefined) {
        tags.push(`状态：${this.query.is_disable === 1 ? '禁用' : '启用'}`)
      }
      if (this.query.search_value) {
        tags.push(`检索：${this.query.search_field || 'title'} = ${this.query.search_value}`)
      }
      return tags
    },
    selectReviewItems() {
      return [
        `运行环境：${this.runtimeEnvInfo.label}`,
        `已选数量：${this.selection.length}`,
        `当前状态：${
          this.query.is_disable === undefined
            ? '全部'
            : this.query.is_disable === 1
            ? '禁用'
            : '启用'
        }`,
        `操作类型：${this.selectTitle || '快递维护'}`
      ]
    },
    selectRiskHint() {
      return this.runtimeEnvInfo.isProd
        ? '正式环境会直接影响后台发货和物流选择，请先复核禁用、删除和编码修改。'
        : '当前环境适合验证快递公司筛选、状态切换和列表回显，不要把测试操作当作正式运营结果。'
    },
    summaryHintTitle() {
      if (this.selection.length > 0) {
        return '待确认'
      }
      return this.query.is_disable === 1 ? '重点巡检' : '状态稳定'
    },
    summaryHintClass() {
      if (this.selection.length > 0) {
        return 'is-active'
      }
      return this.query.is_disable === 1 ? 'is-warning' : 'is-safe'
    },
    followupHint() {
      if (this.selection.length > 0) {
        return '当前已经锁定了一批快递公司，处理完启停或删除后，通常要先去订单页点发货弹窗看实际可选，再回仓库和地区页确认履约链路有没有被带偏。'
      }
      if (this.query.is_disable === 1) {
        return '当前重点在看禁用中的快递公司，下一步更适合先去订单页看发货承接，再回仓库和地区页核对履约基础配置。'
      }
      return '快递公司页更像物流基础资料台账，处理完编码、状态和排序后，通常要先去订单页看发货弹窗，再回仓库和地区页检查下游承接。'
    },
    followupTags() {
      return [
        `当前状态：${
          this.query.is_disable === undefined
            ? '全部快递公司'
            : this.query.is_disable === 1
            ? '禁用中'
            : '启用中'
        }`,
        `已选数量：${this.selection.length}`,
        this.query.search_value ? `当前检索：${this.query.search_value}` : '默认检索：全部快递公司'
      ]
    },
    deliveryFocusLabel() {
      if (this.selection.length > 0) {
        return '先复核批量处理'
      }
      if (this.query.is_disable === 1) {
        return '先看禁用影响'
      }
      if (this.query.search_value) {
        return '先核对单条资料'
      }
      return '先看整体可用性'
    },
    deliveryGuideCards() {
      return [
        {
          title: '第一步先分清是在巡检还是治理',
          desc:
            this.query.is_disable === 1
              ? '当前看到的是禁用中的快递公司，重点不是新增，而是判断哪些还能恢复、哪些该彻底废弃。'
              : '当前更像快递基础资料巡检页，先看状态、编码和名称有没有明显脏数据。',
          action:
            this.query.is_disable === 1 ? '禁用数据优先看影响面。' : '正常巡检优先看编码和状态。'
        },
        {
          title: '第二步再决定要不要批量处理',
          desc:
            this.selection.length > 0
              ? `当前已选 ${this.selection.length} 项，提交前先确认这些公司是不是还被仓库、订单或发货规则引用。`
              : '没有先勾选时，不建议直接做删除或禁用；先缩小范围再批量处理更稳。',
          action: this.selection.length > 0 ? '批量处理前先想下游影响。' : '先筛选，再勾选。'
        },
        {
          title: '第三步把物流链路串起来看',
          desc: '快递公司只是物流基础台账，单看这页不能判断履约是否正常，还要回仓库、订单和地区页看实际承接。',
          action: '资料改完后继续去下游页复核。'
        }
      ]
    }
  },
  created() {
    this.height = screenHeight()
    this.list()
  },
  methods: {
    handleEntryContextPrimary() {
      if (this.entrySourceLabel === '来自地区管理') {
        this.goToPage('/setting/region')
        return
      }
      if (this.entrySourceLabel === '来自仓库管理') {
        this.goToPage('/setting/warehouse')
        return
      }
      this.goToPage('/order/order')
    },
    goToEntryContextBack() {
      if (this.entrySourceLabel === '来自控制台总览') {
        this.$router.push({ path: '/dashboard', query: { from: 'setting-delivery' } })
        return
      }
      if (this.entrySourceLabel === '来自地区管理') {
        this.$router.push({ path: '/setting/region', query: { from: 'setting-delivery' } })
        return
      }
      if (this.entrySourceLabel === '来自仓库管理') {
        this.$router.push({ path: '/setting/warehouse', query: { from: 'setting-delivery' } })
      }
    },
    // 列表
    list() {
      this.loading = true
      list(this.query)
        .then((res) => {
          this.data = res.data.list
          this.count = res.data.count
          this.exps = res.data.exps
          this.loading = false
          this.recentActionSummary = `已加载快递公司列表，共 ${res.data.count || 0} 项。`
        })
        .catch(() => {
          this.loading = false
        })
    },
    // 添加修改
    add() {
      this.dialog = true
      this.dialogTitle = this.name + '添加'
      this.reset()
      this.recentActionSummary = '准备新增快递公司。'
    },
    edit(row) {
      this.dialog = true
      this.dialogTitle = this.name + '修改：' + row[this.idkey]
      this.recentActionSummary = `准备修改快递公司 ${row[this.idkey]}。`
      var id = {}
      id[this.idkey] = row[this.idkey]
      info(id)
        .then((res) => {
          this.reset(res.data)
        })
        .catch(() => {})
    },
    cancel() {
      this.dialog = false
      this.reset()
    },
    submit() {
      this.$refs['ref'].validate((valid) => {
        if (valid) {
          this.loading = true
          if (this.model[this.idkey]) {
            edit(this.model)
              .then((res) => {
                this.list()
                this.dialog = false
                this.recentActionSummary = `已修改快递公司：${this.model.title || '未命名'}。`
                ElMessage.success(res.msg)
                this.$nextTick(() => {
                  ElMessage.info(
                    '快递公司已提交，请继续去订单管理的发货流程、仓库管理和地区管理页各核对一次。'
                  )
                })
              })
              .catch(() => {
                this.loading = false
              })
          } else {
            add(this.model)
              .then((res) => {
                this.list()
                this.dialog = false
                this.recentActionSummary = `已新增快递公司：${this.model.title || '未命名'}。`
                ElMessage.success(res.msg)
                this.$nextTick(() => {
                  ElMessage.info(
                    '快递公司已提交，请继续去订单管理的发货流程、仓库管理和地区管理页各核对一次。'
                  )
                })
              })
              .catch(() => {
                this.loading = false
              })
          }
        }
      })
    },
    // 重置
    reset(row) {
      if (row) {
        this.model = row
      } else {
        this.model = this.$options.data().model
      }
      if (this.$refs['ref'] !== undefined) {
        try {
          this.$refs['ref'].resetFields()
          this.$refs['ref'].clearValidate()
        } catch (error) {}
      }
    },
    // 查询
    search() {
      this.query.page = 1
      this.recentActionSummary = `已按 ${this.query.search_field || 'title'} 发起快递公司检索。`
      this.list()
    },
    // 刷新
    refresh() {
      const limit = this.query.limit
      this.query = this.$options.data().query
      this.$refs['table'].clearSort()
      this.query.limit = limit
      this.recentActionSummary = '已重置快递公司筛选条件。'
      this.list()
    },
    // 排序
    sort(sort) {
      this.query.sort_field = sort.prop
      this.query.sort_value = ''
      if (sort.order === 'ascending') {
        this.query.sort_value = 'asc'
        this.list()
      }
      if (sort.order === 'descending') {
        this.query.sort_value = 'desc'
        this.list()
      }
    },
    // 操作
    select(selection) {
      this.selection = selection
      this.selectIds = this.selectGetIds(selection).toString()
      if (selection.length) {
        this.recentActionSummary = `已勾选 ${selection.length} 项快递公司，待处理 ID：${this.selectIds}。`
      }
    },
    selectGetIds(selection) {
      return arrayColumn(selection, this.idkey)
    },
    selectAlert() {
      ElMessageBox.alert('请选择需要操作的' + this.name, '提示', {
        type: 'warning',
        callback: () => {}
      })
    },
    selectOpen(selectType, selectRow = '') {
      if (selectRow) {
        this.$refs['table'].clearSelection()
        const selectRowLen = selectRow.length
        for (let i = 0; i < selectRowLen; i++) {
          this.$refs['table'].toggleRowSelection(selectRow[i], true)
        }
      }
      if (!this.selection.length) {
        this.selectAlert()
      } else {
        this.selectTitle = '操作'
        if (selectType === 'removef') {
          this.selectTitle = this.name + '解除文件'
        } else if (selectType === 'disable') {
          this.selectTitle = this.name + '是否禁用'
        } else if (selectType === 'dele') {
          this.selectTitle = this.name + '删除'
        }
        this.selectDialog = true
        this.selectType = selectType
        this.recentActionSummary = `准备执行${this.selectTitle}，当前已选 ${this.selection.length} 项快递公司。`
      }
    },
    selectCancel() {
      this.selectDialog = false
    },
    selectSubmit() {
      if (!this.selection.length) {
        this.selectAlert()
      } else {
        const selectType = this.selectType
        if (selectType === 'disable') {
          this.disable(this.selection, true)
        } else if (selectType === 'dele') {
          this.dele(this.selection, true)
        }
        this.selectDialog = false
      }
    },
    confirmRowDisable(row) {
      const nextValue = Number(row.is_disable || 0)
      const previousValue = nextValue === 1 ? 0 : 1
      const actionLabel = nextValue === 1 ? '禁用' : '启用'
      ElMessageBox.confirm(
        `确定要${actionLabel}快递公司“${row.title || row.code || row[this.idkey]}”吗？`,
        '操作确认',
        {
          type: nextValue === 1 ? 'warning' : 'info',
          confirmButtonText: '确定',
          cancelButtonText: '取消'
        }
      )
        .then(() => {
          this.disable([row])
        })
        .catch(() => {
          row.is_disable = previousValue
        })
    },
    // 是否禁用
    disable(row, select = false) {
      if (!row.length) {
        this.selectAlert()
      } else {
        this.loading = true
        var is_disable = row[0].is_disable
        if (select) {
          is_disable = this.is_disable
        }
        disable({
          ids: this.selectGetIds(row),
          is_disable: is_disable
        })
          .then((res) => {
            this.list()
            this.recentActionSummary = `已${is_disable === 1 ? '禁用' : '启用'} ${
              row.length
            } 项快递公司。`
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.list()
          })
      }
    },
    // 删除
    dele(row) {
      if (!row.length) {
        this.selectAlert()
      } else {
        this.loading = true
        dele({
          ids: this.selectGetIds(row)
        })
          .then((res) => {
            this.list()
            this.recentActionSummary = `已删除 ${row.length} 项快递公司。`
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.loading = false
          })
      }
    },
    goToPage(path) {
      this.$router.push({
        path,
        query: {
          from: 'setting-delivery'
        }
      })
    }
  }
}
</script>

<style scoped>
.entry-context-banner {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin: 0 0 16px;
  padding: 14px 16px;
  border-radius: 14px;
  border: 1px solid #dbe7f5;
  background: linear-gradient(135deg, #f8fbff 0%, #ffffff 100%);
}

.entry-context-banner__main {
  flex: 1;
  min-width: 0;
}

.entry-context-banner__eyebrow {
  font-size: 12px;
  font-weight: 700;
  color: #2563eb;
}

.entry-context-banner__title {
  margin-top: 4px;
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
}

.entry-context-banner__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.entry-context-banner__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  justify-content: flex-end;
}

.delivery-summary-bar,
.followup-panel,
.select-review-panel {
  border: 1px solid #e6ecf5;
  border-radius: 12px;
  padding: 14px 16px;
  background: linear-gradient(180deg, #f9fbff 0%, #ffffff 100%);
  box-shadow: 0 6px 18px rgba(15, 35, 95, 0.05);
}

.section-title-row {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 16px;
}

.section-title-row--content {
  margin-bottom: 14px;
}

.section-title-row__title {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
}

.section-title-row__desc {
  margin-top: 4px;
  font-size: 12px;
  color: #64748b;
}

.section-title-row__meta {
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
  white-space: nowrap;
}

.delivery-summary-bar {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 14px;
}

.delivery-summary-bar__chips,
.select-review-panel__tags {
  display: flex;
  flex: 1;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
}

.summary-chip {
  display: inline-flex;
  align-items: center;
  min-height: 30px;
  padding: 0 10px;
  border-radius: 999px;
  background: #eef4ff;
  border: 1px solid #dbe7f6;
  color: #375078;
  font-size: 12px;
}

.summary-chip--primary {
  color: #1d4ed8;
  background: #e8f0ff;
  border-color: #cfe0ff;
}

.summary-chip--muted {
  color: #64748b;
}

.delivery-summary-bar__hint {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 240px;
  padding: 10px 12px;
  border-radius: 12px;
}

.delivery-summary-bar__hint.is-safe {
  background: #eaf8ef;
  color: #15803d;
}

.delivery-summary-bar__hint.is-warning {
  background: #fff5e8;
  color: #b45309;
}

.delivery-summary-bar__hint.is-active {
  background: #e8f0ff;
  color: #1d4ed8;
}

.delivery-summary-bar__hint-title,
.select-review-panel__title {
  font-size: 13px;
  font-weight: 700;
  color: #0f172a;
}

.delivery-summary-bar__hint-text,
.select-review-panel__hint {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: inherit;
}

.plain-guide {
  margin-top: 14px;
  padding: 14px 16px;
  border: 1px solid #dbe7f6;
  border-radius: 12px;
  background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
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
  color: #0f172a;
}

.plain-guide__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.8;
  color: #64748b;
}

.plain-guide__badge {
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  padding: 0 10px;
  border-radius: 999px;
  background: #eef4ff;
  color: #3152bf;
  font-size: 12px;
  font-weight: 700;
}

.plain-guide__grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 10px;
  margin-top: 12px;
}

.plain-guide-card {
  padding: 12px 14px;
  border-radius: 12px;
  border: 1px solid #e6ecf5;
  background: #fff;
}

.plain-guide-card__title {
  font-size: 13px;
  font-weight: 700;
  color: #0f172a;
}

.plain-guide-card__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.plain-guide-card__action {
  margin-top: 8px;
  font-size: 12px;
  color: #1d4ed8;
}

.select-review-panel__tags span {
  display: inline-flex;
  align-items: center;
  padding: 4px 10px;
  border-radius: 999px;
  background: #eef4ff;
  color: #3b5ccc;
  font-size: 12px;
}

.select-review-panel {
  margin-bottom: 12px;
}

.followup-panel {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}

.followup-panel__main {
  display: flex;
  flex: 1;
  flex-direction: column;
  gap: 10px;
}

.followup-panel__title {
  font-size: 15px;
  font-weight: 700;
  color: #0f172a;
}

.followup-panel__desc {
  font-size: 12px;
  line-height: 1.8;
  color: #64748b;
}

.followup-panel__tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.followup-panel__tags span {
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  padding: 0 10px;
  border-radius: 999px;
  background: #eef4ff;
  border: 1px solid #dbe7f6;
  color: #375078;
  font-size: 12px;
}

.followup-panel__actions {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 8px;
}

.action-cluster {
  display: inline-flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
  margin-right: 10px;
}

.action-cluster__title {
  font-size: 12px;
  color: #7c8aa5;
}

@media (max-width: 900px) {
  .entry-context-banner,
  .delivery-summary-bar,
  .plain-guide__header,
  .followup-panel,
  .section-title-row {
    flex-direction: column;
  }

  .section-title-row__meta {
    white-space: normal;
  }

  .followup-panel__actions {
    justify-content: flex-start;
  }
}
</style>
