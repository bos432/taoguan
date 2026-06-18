<template>
  <div class="app-container">
    <el-card class="app-head">
      <div class="page-head">
        <div>
          <div class="page-head__title">协议管理</div>
          <div class="page-head__desc">
            维护协议文案、展示状态与移动端协议中心配置，默认按线上后台节奏直达列表。
          </div>
        </div>
        <div class="page-head__meta">
          <span class="page-head__tag">{{ runtimeEnvInfo.label }}</span>
          <span class="page-head__tag">{{ runtimeEnvInfo.dataMode }}</span>
        </div>
      </div>
      <div v-if="entryContextVisible" class="entry-context-banner">
        <div class="entry-context-banner__main">
          <div class="entry-context-banner__eyebrow">外部入口承接</div>
          <div class="entry-context-banner__title">{{ entryContextTitle }}</div>
          <div class="entry-context-banner__desc">{{ entryContextDesc }}</div>
        </div>
        <div class="entry-context-banner__actions">
          <el-button type="primary" @click="handleEntryContextPrimary">{{ entryContextPrimaryLabel }}</el-button>
          <el-button @click="goToEntryContextBack">回来源页</el-button>
        </div>
      </div>
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
              <el-select
                  v-model="query.is_disable"
                  @change="search()"
                  clearable
              >
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
                  <el-option value="unique" label="标识" />
                  <el-option value="name" label="名称" />
                  <el-option value="desc" label="描述" />
                  <el-option value="remark" label="备注" />
                </el-select>
              </template>
            </el-input>
          </el-col>
          <el-col :span="6">
            <el-button type="primary" @click="search()">搜索</el-button>
            <el-button title="重置" @click="refresh()">
              重置
            </el-button>
          </el-col>
        </el-row>
      </el-form>
      <div class="accord-brief">
        <div class="accord-brief__main">
          <div class="accord-brief__title">这页主要在做什么</div>
          <div class="accord-brief__desc">{{ plainSummary }}</div>
          <div class="accord-brief__points">
            <div v-for="item in plainSummaryPoints" :key="item.label" class="accord-brief__point">
              <span class="accord-brief__point-label">{{ item.label }}</span>
              <span class="accord-brief__point-value">{{ item.value }}</span>
            </div>
          </div>
        </div>
        <div class="accord-brief__aside">
          <div class="accord-brief__aside-title">现在更适合做</div>
          <div class="accord-brief__aside-text">{{ plainNextStep }}</div>
        </div>
      </div>
      <div class="followup-panel">
        <div class="followup-panel__main">
          <div class="followup-panel__title">看完协议后继续去哪</div>
          <div class="followup-panel__desc">{{ followupHint }}</div>
          <div class="followup-panel__tags">
            <span v-for="item in followupTags" :key="item">{{ item }}</span>
          </div>
        </div>
        <div class="followup-panel__actions">
          <el-button type="primary" @click="goToPage('/member/setting')">去会员设置</el-button>
          <el-button @click="goToPage('/setting/notice')">去通知公告</el-button>
          <el-button @click="goToPage('/content/setting')">去内容设置</el-button>
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
          <el-switch v-model="is_disable" :active-value="1" :inactive-value="0" />
        </el-form-item>
        <el-form-item v-else-if="selectType === 'dele'">
          <span class="c-red">确定要删除选中的{{ name }}吗？</span>
        </el-form-item>
        <el-form-item :label="name + 'ID'">
          <el-input v-model="selectIds" type="textarea" autosize disabled />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button :loading="loading" @click="selectCancel">取消</el-button>
        <el-button :loading="loading" type="primary" @click="selectSubmit">提交</el-button>
      </template>
    </el-dialog>
    <el-card class="app-main">
      <div class="section-title-row section-title-row--content">
        <div>
          <div class="section-title-row__title">协议列表</div>
          <div class="section-title-row__desc">支持协议文案维护、状态切换与移动端协议中心展示配置。</div>
        </div>
        <div class="section-title-row__meta">已选 {{ selection.length }} 项</div>
      </div>
      <div class="summary-bar">
        <div class="summary-bar__metrics">
          <span class="summary-bar__metric">协议总量：{{ count }}</span>
          <span class="summary-bar__metric">已选协议：{{ selection.length }}</span>
          <span class="summary-bar__metric">当前状态：{{ currentStatusLabel }}</span>
        </div>
        <div class="summary-bar__filters">
          <el-tag v-for="item in summaryTags" :key="item" effect="plain">{{ item }}</el-tag>
        </div>
        <div class="summary-bar__hint">
          <span>{{ summaryHint }}</span>
          <span v-if="recentActionSummary">{{ recentActionSummary }}</span>
        </div>
      </div>
      <div class="operation_btn mb5">
        <!-- 操作 -->
        <div>
          <div class="action-cluster">
            <span class="action-cluster__title">内容维护</span>
            <el-button type="primary" @click="add()">添加</el-button>
          </div>
          <div class="action-cluster">
            <span class="action-cluster__title">发布控制</span>
            <el-button title="是否禁用" @click="selectOpen('disable')">禁用</el-button>
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
        @cell-dblclick="cellDbclick"
      >
        <el-table-column type="selection" width="42" title="全选/反选" />
        <el-table-column :prop="idkey" label="ID" width="80" sortable="custom" />
        <el-table-column
          prop="unique"
          label="标识"
          min-width="120"
          sortable="custom"
          show-overflow-tooltip
        />
        <el-table-column
          prop="name"
          label="名称"
          min-width="120"
          sortable="custom"
          show-overflow-tooltip
        />
        <el-table-column prop="desc" label="描述" min-width="160" show-overflow-tooltip />
        <el-table-column prop="remark" label="备注" min-width="150" show-overflow-tooltip />
        <el-table-column prop="is_disable" label="禁用" min-width="85" sortable="custom">
          <template #default="scope">
            <el-switch
              v-model="scope.row.is_disable"
              :active-value="1"
              :inactive-value="0"
              @change="handleDisableChange(scope.row)"
            />
          </template>
        </el-table-column>
        <el-table-column prop="sort" label="排序" min-width="85" sortable="custom" />
        <el-table-column prop="create_time" label="添加时间" width="165" sortable="custom" />
        <el-table-column prop="update_time" label="修改时间" width="165" sortable="custom" />
        <el-table-column label="操作" width="95">
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
    </el-card>
    <!-- 添加修改 -->
    <el-dialog
      v-model="dialog"
      :title="dialogTitle"
      :close-on-click-modal="false"
      :close-on-press-escape="false"
      :before-close="cancel"
      top="5vh"
      destroy-on-close
    >
      <el-form ref="ref" :rules="rules" :model="model" label-width="100px">
        <el-tabs>
          <el-tab-pane label="信息">
            <el-scrollbar native :max-height="height - 30">
              <el-form-item label="标识" prop="unique">
                <el-input v-model="model.unique" placeholder="请输入标识（唯一）" clearable>
                  <template #append>
                    <el-button title="复制" @click="copy(model.unique)">
                      <svg-icon icon-class="copy-document" />
                    </el-button>
                  </template>
                </el-input>
              </el-form-item>
              <el-form-item label="名称" prop="name">
                <el-input v-model="model.name" placeholder="请输入名称" clearable>
                  <template #append>
                    <el-button title="复制" @click="copy(model.name)">
                      <svg-icon icon-class="copy-document" />
                    </el-button>
                  </template>
                </el-input>
              </el-form-item>
              <el-form-item label="描述" prop="desc">
                <el-input v-model="model.desc" type="textarea" autosize placeholder="请输入描述" />
              </el-form-item>
              <el-form-item label="备注" prop="remark">
                <el-input v-model="model.remark" placeholder="请输入备注" clearable />
              </el-form-item>
              <el-form-item label="排序" prop="sort">
                <el-input v-model="model.sort" type="number" />
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
            </el-scrollbar>
          </el-tab-pane>
          <el-tab-pane label="内容">
            <el-scrollbar native :max-height="height - 30">
              <el-form-item label="内容" prop="content">
                <RichEditor v-model="model.content" />
              </el-form-item>
            </el-scrollbar>
          </el-tab-pane>
        </el-tabs>
      </el-form>
      <template #footer>
        <el-button :loading="loading" @click="cancel">取消</el-button>
        <el-button :loading="loading" type="primary" @click="submit">提交</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script>
import screenHeight from '@/utils/screen-height'
import Pagination from '@/components/Pagination/index.vue'
import RichEditor from '@/components/RichEditor/index.vue'
import clip from '@/utils/clipboard'
import { arrayColumn } from '@/utils/index'
import { getPageLimit } from '@/utils/settings'
import { list, info, add, edit, dele, disable } from '@/api/setting/accord'
import { getAdminRuntimeHint, resolveAdminRuntimeEnv } from '@/utils/runtime-env'

export default {
  name: 'SettingAccord',
  components: { Pagination, RichEditor },
  data() {
    return {
      name: '协议',
      height: 680,
      loading: false,
      idkey: 'accord_id',
      exps: [{ exp: 'like', name: '包含' }],
      query: {
        page: 1,
        limit: getPageLimit(),
        search_field: 'name',
        search_exp: 'like',
        date_field: 'create_time',
        is_disable:undefined,
      },
      data: [],
      count: 0,
      dialog: false,
      dialogTitle: '',
      model: {
        accord_id: '',
        unique: '',
        name: '',
        desc: '',
        content: '',
        remark: '',
        sort: 250
      },
      rules: {
        unique: [{ required: true, message: '请输入标识', trigger: 'blur' }],
        name: [{ required: true, message: '请输入名称', trigger: 'blur' }]
      },
      selection: [],
      selectIds: '',
      selectTitle: '操作',
      selectDialog: false,
      selectType: '',
      is_disable: 0,
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
      if (source === 'member-setting') return '来自会员设置中心'
      if (source === 'system-setting') return '来自系统设置中心'
      return ''
    },
    entryContextVisible() {
      return Boolean(this.entrySourceLabel)
    },
    entryContextTitle() {
      if (this.entrySourceLabel === '来自控制台总览') return '当前从控制台进入协议管理'
      if (this.entrySourceLabel === '来自会员设置中心') return '当前从会员设置中心进入协议管理'
      if (this.entrySourceLabel === '来自系统设置中心') return '当前从系统设置中心进入协议管理'
      return '当前为外部入口承接视角'
    },
    entryContextDesc() {
      if (this.entrySourceLabel === '来自控制台总览') {
        return '这类进入通常是为了核登录、注册或协议中心链路。建议优先确认启用状态和当前文案，再去会员设置与 H5 登录页复核。'
      }
      if (this.entrySourceLabel === '来自会员设置中心') {
        return '这类进入通常是为了补登录前协议勾选和展示入口，建议先确认协议是否启用，再回会员设置继续核对前台链路。'
      }
      if (this.entrySourceLabel === '来自系统设置中心') {
        return '这类进入通常是为了继续核对全局展示文案，建议先看是否存在禁用协议或旧版本协议残留。'
      }
      return ''
    },
    entryContextPrimaryLabel() {
      if (this.entrySourceLabel === '来自控制台总览') return '去会员设置复核'
      if (this.entrySourceLabel === '来自会员设置中心') return '回会员设置'
      return '去通知公告'
    },
    currentStatusLabel() {
      return this.query.is_disable === undefined ? '全部' : this.query.is_disable === 1 ? '禁用' : '启用'
    },
    summaryTags() {
      const tags = [`当前检索：${this.query.search_field || 'name'}`]
      if (this.query.date_value?.length) {
        tags.push(`添加时间：${this.query.date_value.join(' 至 ')}`)
      }
      if (this.query.search_value) {
        tags.push(`关键词：${this.query.search_value}`)
      }
      if (this.selection.length) {
        tags.push(`待处理ID：${this.selectIds}`)
      }
      return tags
    },
    summaryHint() {
      if (this.selection.length) {
        return `当前已选 ${this.selection.length} 项协议，可继续做禁用或删除等发布控制操作。`
      }
      return this.selectRiskHint
    },
    selectRiskHint() {
      return this.runtimeEnvInfo.isProd
        ? '正式环境会直接影响登录、协议中心和隐私政策展示，请先复核禁用、删除和文案内容。'
        : '当前环境适合验证协议文案、启禁用和回显展示，不要把测试内容当作正式运营结果。'
    },
    plainSummary() {
      if (this.selection.length) {
        return `你当前选中了 ${this.selection.length} 项协议，这一步更像是在确认哪些协议要继续启用、禁用，或者准备删除。`
      }
      if (this.query.search_value || this.query.date_value?.length || this.query.is_disable !== undefined) {
        return '你现在是在按条件筛协议，通常是为了快速找到某份用户协议、隐私政策或授权说明，再决定是否修改文案和展示状态。'
      }
      return '这页不是单纯改列表，而是统一管协议内容、启用状态和前台展示入口，确保用户在登录、注册或协议中心里看到的是正确版本。'
    },
    plainSummaryPoints() {
      return [
        {
          label: '现在看到的范围',
          value: this.query.search_value ? `关键词“${this.query.search_value}”的检索结果` : '当前协议全量列表'
        },
        {
          label: '当前状态重点',
          value: this.currentStatusLabel === '全部' ? '同时关注启用与禁用协议' : `重点查看${this.currentStatusLabel}协议`
        },
        {
          label: '处理后影响',
          value: this.runtimeEnvInfo.isProd ? '会直接影响线上协议展示与用户确认链路' : '适合先验证展示效果与回显逻辑'
        }
      ]
    },
    plainNextStep() {
      if (this.selection.length) {
        return '先完成本页的启用、禁用或删除，再去相关设置页确认前台入口有没有承接到最新协议。'
      }
      if (this.query.is_disable === 1) {
        return '这批禁用协议更适合回头核对会员设置和公告页，确认前台不会继续引导用户看到旧入口。'
      }
      return '如果协议文案已经确认，下一步通常是去会员设置、公告或内容设置页，把入口说明和展示文案一并核对掉。'
    },
    followupHint() {
      if (this.selection.length) {
        return `当前已选 ${this.selection.length} 项协议，处理完后建议先去会员设置确认登录注册链路，再去公告或内容设置页补齐前台承接说明。`
      }
      if (this.query.is_disable === 1) {
        return '你现在重点看的是禁用协议，下一步更适合去会员设置和内容设置页核对前台是否还保留旧协议入口或旧说明。'
      }
      return '协议文案改完后，通常还要顺手检查会员设置里的登录注册承接、公告里的用户提醒，以及内容设置里的站点展示信息，避免用户入口和协议内容脱节。'
    },
    followupTags() {
      return [
        `协议总量：${this.count || 0}`,
        `已选：${this.selection.length} 项`,
        `状态：${this.currentStatusLabel}`,
        `环境：${this.runtimeEnvInfo.label}`
      ]
    }
  },
  created() {
    this.height = screenHeight()
    this.list()
  },
  methods: {
    handleEntryContextPrimary() {
      if (this.entrySourceLabel === '来自控制台总览' || this.entrySourceLabel === '来自会员设置中心') {
        this.goToPage('/member/setting')
        return
      }
      this.goToPage('/setting/notice')
    },
    goToEntryContextBack() {
      if (this.entrySourceLabel === '来自控制台总览') {
        this.$router.push({ path: '/dashboard', query: { from: 'setting-accord' } })
        return
      }
      if (this.entrySourceLabel === '来自会员设置中心') {
        this.$router.push({ path: '/member/setting', query: { from: 'setting-accord' } })
        return
      }
      if (this.entrySourceLabel === '来自系统设置中心') {
        this.$router.push({ path: '/system/setting', query: { from: 'setting-accord' } })
      }
    },
    handleDisableChange(row) {
      const nextValue = row.is_disable
      const previousValue = nextValue === 1 ? 0 : 1
      ElMessageBox.confirm(
        `确认要${nextValue === 1 ? '禁用' : '启用'}协议「${row.name || row[this.idkey]}」吗？`,
        '提示',
        { type: 'warning' }
      )
        .then(() => {
          this.disable([row])
        })
        .catch(() => {
          row.is_disable = previousValue
        })
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
          this.recentActionSummary = `已加载协议列表，共 ${res.data.count || 0} 项。`
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
      this.recentActionSummary = '准备新增协议内容。'
    },
    edit(row) {
      this.dialog = true
      this.dialogTitle = this.name + '修改：' + row[this.idkey]
      this.recentActionSummary = `准备修改协议 ${row[this.idkey]}。`
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
                this.recentActionSummary = `已修改协议：${this.model.name || '未命名协议'}。`
                ElMessage.success(res.msg)
              })
              .catch(() => {
                this.loading = false
              })
          } else {
            add(this.model)
              .then((res) => {
                this.list()
                this.dialog = false
                this.recentActionSummary = `已新增协议：${this.model.name || '未命名协议'}。`
                ElMessage.success(res.msg)
              })
              .catch(() => {
                this.loading = false
              })
          }
        } else {
          ElMessage.error('请完善必填项（带红色星号*）')
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
      this.recentActionSummary = `已按 ${this.query.search_field || 'name'} 发起协议检索。`
      this.list()
    },
    // 刷新
    refresh() {
      const limit = this.query.limit
      this.query = this.$options.data().query
      this.$refs['table'].clearSort()
      this.query.limit = limit
      this.recentActionSummary = '已重置协议筛选条件。'
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
        this.recentActionSummary = `已勾选 ${selection.length} 项协议，待处理 ID：${this.selectIds}。`
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
        if (selectType === 'disable') {
          this.selectTitle = this.name + '是否禁用'
        } else if (selectType === 'dele') {
          this.selectTitle = this.name + '删除'
        }
        this.selectDialog = true
        this.selectType = selectType
        this.recentActionSummary = `准备执行${this.selectTitle}，当前已选 ${this.selection.length} 项协议。`
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
          this.dele(this.selection)
        }
        this.selectDialog = false
      }
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
            this.recentActionSummary = `已${is_disable === 1 ? '禁用' : '启用'} ${row.length} 项协议。`
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
            this.recentActionSummary = `已删除 ${row.length} 项协议。`
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.loading = false
          })
      }
    },
    // 复制
    copy(text) {
      clip(text)
    },
    // 单元格双击复制
    cellDbclick(row, column) {
      this.copy(row[column.property])
    },
    goToPage(path) {
      this.$router.push({
        path,
        query: {
          from: 'setting-accord'
        }
      })
    }
  }
}
</script>

<style scoped>
.entry-context-banner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
  padding: 14px 16px;
  margin-bottom: 14px;
  border: 1px solid #dbeafe;
  border-radius: 14px;
  background: linear-gradient(135deg, #f5f9ff 0%, #ffffff 100%);
}

.entry-context-banner__main {
  display: grid;
  gap: 6px;
}

.entry-context-banner__eyebrow {
  font-size: 12px;
  font-weight: 700;
  color: #2563eb;
}

.entry-context-banner__title {
  font-size: 15px;
  font-weight: 700;
  color: #0f172a;
}

.entry-context-banner__desc {
  font-size: 13px;
  line-height: 1.7;
  color: #64748b;
}

.entry-context-banner__actions {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.accord-brief,
.followup-panel {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-top: 12px;
  padding: 12px 14px;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  background: #fbfdff;
}

.accord-brief__main,
.followup-panel__main {
  flex: 1;
  min-width: 0;
}

.accord-brief__title,
.followup-panel__title {
  font-size: 13px;
  font-weight: 700;
  color: #111827;
}

.accord-brief__desc,
.followup-panel__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #6b7280;
}

.accord-brief__points,
.followup-panel__tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 10px;
}

.accord-brief__point,
.followup-panel__tags span {
  display: inline-flex;
  align-items: center;
  min-height: 24px;
  padding: 0 8px;
  border-radius: 999px;
  background: #f3f4f6;
  color: #4b5563;
  font-size: 12px;
}

.accord-brief__point {
  gap: 6px;
}

.accord-brief__point-label {
  color: #64748b;
}

.accord-brief__point-value {
  color: #334155;
}

.accord-brief__aside {
  min-width: 220px;
  padding: 10px 12px;
  border-radius: 12px;
  background: #eef4ff;
}

.accord-brief__aside-title {
  font-size: 13px;
  font-weight: 700;
  color: #1e3a8a;
}

.accord-brief__aside-text {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #334155;
}

.followup-panel__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  justify-content: flex-end;
}

.page-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 16px;
}

.page-head__title {
  font-size: 18px;
  font-weight: 700;
  color: #0f172a;
}

.page-head__desc {
  margin-top: 4px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.page-head__meta,
.summary-bar__metrics,
.summary-bar__filters {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.page-head__tag,
.summary-bar__metric {
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  padding: 0 10px;
  border-radius: 999px;
  font-size: 12px;
}

.page-head__tag {
  font-weight: 700;
  color: #1d4ed8;
  background: #eef4ff;
}

.summary-bar {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-bottom: 12px;
  padding: 12px 16px;
  border: 1px solid rgba(148, 163, 184, 0.16);
  border-radius: 12px;
  background: linear-gradient(180deg, rgba(248, 250, 252, 0.96), #ffffff 100%);
}

.summary-bar__metric {
  color: #334155;
  background: #f8fafc;
  border: 1px solid rgba(148, 163, 184, 0.16);
}

.summary-bar__hint {
  display: flex;
  flex-wrap: wrap;
  gap: 10px 16px;
  font-size: 12px;
  color: #64748b;
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
  .page-head,
  .accord-brief,
  .followup-panel {
    flex-direction: column;
  }

  .accord-brief__aside {
    min-width: 100%;
  }
}
</style>
