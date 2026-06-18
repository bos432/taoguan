<template>
  <div class="app-container">
    <div class="page-header">
      <div class="page-header__main">
        <div class="page-header__title">内容大厅</div>
        <div class="page-header__desc">
          集中查看大厅层级、状态和上级关系，便于按当前范围直接处理。
        </div>
      </div>
      <div class="page-header__meta">
        <span class="summary-chip summary-chip--env">{{ runtimeEnvInfo.label }}</span>
        <span class="summary-chip">{{ runtimeEnvInfo.dataMode }}</span>
      </div>
    </div>
    <el-card class="app-head head-pb20">
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
      <div class="filter-head">
        <div class="filter-head__title">大厅检索</div>
        <div class="filter-head__meta">按时间、状态、上级和关键词快速定位大厅内容</div>
      </div>
      <!-- 查询 -->
      <el-form
        :model="query"
        ref="searchForm"
        label-width="72px"
        size="small"
        class="compact-query-form"
      >
        <el-row :gutter="12">
          <el-col :xl="7" :lg="8" :md="12" :sm="24">
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
          <el-col :xl="4" :lg="5" :md="6" :sm="12">
            <el-form-item label="状态：" prop="is_disable">
              <el-select v-model="query.is_disable" @change="search()" clearable>
                <el-option :value="0" label="启用" />
                <el-option :value="1" label="禁用" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :xl="4" :lg="5" :md="6" :sm="12">
            <el-form-item label="上级：" prop="pid">
              <el-cascader
                v-model="query.pid"
                :options="trees"
                :props="props"
                @change="search()"
                clearable
                filterable
                style="width: 100%"
              />
            </el-form-item>
          </el-col>
          <el-col :xl="9" :lg="6" :md="24" :sm="24">
            <div class="query-search-inline">
              <el-input
                v-model="query.search_value"
                placeholder="请输入内容"
                class="input-with-select"
                @keyup.enter="search()"
                clearable
              >
                <template #prepend>
                  <el-select v-model="query.search_field" placeholder="Select" style="width: 92px">
                    <el-option :value="idkey" label="ID" />
                    <el-option value="code" label="编码" />
                    <el-option value="title" label="名称" />
                  </el-select>
                </template>
              </el-input>
              <el-button type="primary" @click="search()">搜索</el-button>
              <el-button title="重置" @click="refresh()">重置</el-button>
            </div>
          </el-col>
        </el-row>
      </el-form>

      <div class="summary-bar">
        <div class="summary-bar__main">
          <span class="summary-chip summary-chip--strong">总数 {{ count }}</span>
          <span class="summary-chip">已选 {{ selection.length }} 项</span>
          <span class="summary-chip">层级 {{ query.pid || '全部层级' }}</span>
          <span class="summary-chip"
            >状态
            {{
              query.is_disable === undefined ? '全部' : query.is_disable === 0 ? '启用' : '禁用'
            }}</span
          >
          <span v-for="item in activeFilterTags" :key="item" class="summary-chip">{{ item }}</span>
        </div>
        <div class="summary-bar__side">
          <span class="summary-chip summary-chip--risk">{{ selectRiskHint }}</span>
          <span v-if="recentActionSummary" class="summary-chip">{{ recentActionSummary }}</span>
        </div>
      </div>
      <div class="plain-guide">
        <div class="plain-guide__header">
          <div>
            <div class="plain-guide__title">这页建议先这样看</div>
            <div class="plain-guide__desc">
              大厅页本质上是在管前端入口结构，不只是普通内容列表。先看层级，再看状态，最后再决定改上级、禁用还是删除。
            </div>
          </div>
          <span class="plain-guide__badge">{{ hallFocusLabel }}</span>
        </div>
        <div class="plain-guide__grid">
          <div v-for="item in hallGuideCards" :key="item.title" class="plain-guide-card">
            <div class="plain-guide-card__title">{{ item.title }}</div>
            <div class="plain-guide-card__desc">{{ item.desc }}</div>
            <div class="plain-guide-card__action">{{ item.action }}</div>
          </div>
        </div>
      </div>
      <div class="followup-panel">
        <div class="followup-panel__main">
          <div class="followup-panel__title">看完大厅后继续去哪</div>
          <div class="followup-panel__desc">{{ followupHint }}</div>
          <div class="followup-panel__tags">
            <span v-for="item in followupTags" :key="item">{{ item }}</span>
          </div>
        </div>
        <div class="followup-panel__actions">
          <el-button type="primary" @click="goToPage('/setting/warehouse')">去仓库管理</el-button>
          <el-button @click="goToPage('/setting/call')">去秤设备</el-button>
          <el-button @click="goToPage('/setting/carousel')">去轮播管理</el-button>
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
        <div class="select-review-panel select-review-panel--dialog">
          <div class="select-review-panel__title">提交前复核</div>
          <div class="select-review-panel__tags">
            <span v-for="item in selectReviewItems" :key="item">{{ item }}</span>
          </div>
          <div class="select-review-panel__hint">{{ selectRiskHint }}</div>
        </div>
        <el-form-item v-if="selectType === 'removec'">
          <span style="">确定要解除选中的{{ name }}的内容吗？</span>
        </el-form-item>
        <el-form-item v-else-if="selectType === 'editpid'" label="上级">
          <el-cascader
            v-model="pid"
            :options="trees"
            :props="props"
            class="w-full"
            placeholder="一级大厅"
            clearable
            filterable
          />
        </el-form-item>
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
      <div class="operation_btn mb5">
        <!-- 操作 -->
        <div class="operation_btn__left">
          <div class="action-cluster">
            <el-checkbox
              border
              v-model="isExpandAll"
              class="!mr-[10px] top-[3px]"
              title="收起/展开"
              @change="expandAll"
            >
              收起
            </el-checkbox>
            <el-button type="primary" @click="add()">添加</el-button>
            <el-button title="修改上级" @click="selectOpen('editpid')">上级</el-button>
          </div>
          <div class="action-cluster">
            <el-button title="是否禁用" @click="selectOpen('disable')">禁用</el-button>
            <el-button title="删除" @click="selectOpen('dele')">删除</el-button>
          </div>
        </div>
        <div class="operation_btn__right">
          <!-- 分页 -->
          <el-descriptions title="" :column="12" :colon="false">
            <el-descriptions-item>共 {{ count }} 条</el-descriptions-item>
          </el-descriptions>
        </div>
      </div>
      <!-- 列表 -->
      <el-table
        ref="table"
        v-loading="loading"
        :data="data"
        size="small"
        class="compact-hall-table"
        :row-key="idkey"
        @selection-change="select"
      >
        <el-table-column type="selection" width="42" title="全选/反选" />
        <el-table-column :prop="idkey" label="ID" min-width="80" />
        <el-table-column prop="code" label="编码" min-width="100" show-overflow-tooltip />
        <el-table-column prop="title" label="名称" min-width="250" show-overflow-tooltip />
        <el-table-column prop="is_disable" label="禁用" min-width="85">
          <template #default="scope">
            <el-switch
              v-model="scope.row.is_disable"
              :active-value="1"
              :inactive-value="0"
              @change="confirmRowDisable(scope.row)"
            />
          </template>
        </el-table-column>
        <el-table-column prop="sort" label="排序" min-width="85" />
        <el-table-column prop="create_time" label="添加时间" width="165" />
        <el-table-column prop="update_time" label="修改时间" width="165" />
        <el-table-column label="操作" width="170">
          <template #default="scope">
            <el-link
              type="primary"
              class="mr-1"
              :underline="false"
              title="添加下级"
              @click="add(scope.row)"
            >
              添加
            </el-link>
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
      <el-scrollbar native :max-height="height - 30">
        <el-form ref="ref" :rules="rules" :model="model" label-width="100px">
          <el-form-item label="上级" prop="pid">
            <el-cascader
              v-model="model.pid"
              :options="trees"
              :props="props"
              class="w-full"
              placeholder="一级大厅"
              clearable
              filterable
            />
          </el-form-item>
          <el-form-item label="编码" prop="code">
            <el-input v-model="model.code" placeholder="请输入大厅编码（唯一）" clearable />
          </el-form-item>
          <el-form-item label="名称" prop="title">
            <el-input v-model="model.title" placeholder="请输入大厅名称" clearable />
          </el-form-item>
          <el-form-item label="排序" prop="sort">
            <el-input v-model="model.sort" placeholder="250" clearable />
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
  </div>
</template>

<script>
import screenHeight from '@/utils/screen-height'
import { arrayColumn } from '@/utils/index'
import { list, info, add, edit, dele, disable, editpid } from '@/api/setting/hall.js'
import { shortcuts } from '@/utils/getDate.js'
import { getAdminRuntimeHint, resolveAdminRuntimeEnv } from '@/utils/runtime-env'
export default {
  name: 'SettingHall',
  data() {
    return {
      name: '大厅',
      height: 680,
      loading: false,
      idkey: 'id',
      query: {
        search_field: 'title',
        search_exp: 'like',
        date_field: 'create_time',
        pid: undefined,
        is_disable: undefined
      },
      exps: [{ exp: 'like', name: '包含' }],
      data: [],
      count: '',
      dialog: false,
      dialogTitle: '',
      model: {
        id: '',
        code: '',
        pid: 0,
        title: '',
        sort: 250
      },
      rules: {
        code: [{ required: true, message: '请输入大厅编码', trigger: 'blur' }],
        title: [{ required: true, message: '请输入大厅名称', trigger: 'blur' }]
      },
      trees: [],
      props: { checkStrictly: true, value: 'id', label: 'title', emitPath: false },
      isExpandAll: false,
      selection: [],
      selectIds: '',
      selectTitle: '操作',
      selectDialog: false,
      selectType: '',
      pid: '',
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
      if (source === 'setting-warehouse') return '来自仓库管理'
      if (source === 'setting-carousel') return '来自轮播管理'
      return ''
    },
    entryContextVisible() {
      return Boolean(this.entrySourceLabel)
    },
    entryContextTitle() {
      if (this.entrySourceLabel === '来自控制台总览') return '当前从控制台进入内容大厅'
      if (this.entrySourceLabel === '来自仓库管理') return '当前从仓库管理进入内容大厅'
      if (this.entrySourceLabel === '来自轮播管理') return '当前从轮播管理进入内容大厅'
      return '当前为外部入口承接视角'
    },
    entryContextDesc() {
      if (this.entrySourceLabel === '来自控制台总览') {
        return '这类进入通常是为了排首页入口结构、内容聚合或展示顺序问题。建议先看层级树，再去轮播和仓库页复核真实承接。'
      }
      if (this.entrySourceLabel === '来自仓库管理') {
        return '这类进入通常是因为仓库归属需要对回大厅入口。建议先核层级结构，再回仓库页确认归属链。'
      }
      if (this.entrySourceLabel === '来自轮播管理') {
        return '这类进入通常是因为首页展示入口需要对大厅结构。建议先看上级和状态，再回轮播页核投放位。'
      }
      return ''
    },
    entryContextPrimaryLabel() {
      if (this.entrySourceLabel === '来自仓库管理') return '回仓库管理'
      return '去轮播管理复核'
    },
    activeFilterTags() {
      const tags = []
      if (this.query.pid !== undefined && this.query.pid !== '' && this.query.pid !== null) {
        tags.push(`上级：${this.query.pid}`)
      }
      if (this.query.is_disable !== undefined && this.query.is_disable !== '') {
        tags.push(`状态：${this.query.is_disable === 1 ? '禁用' : '启用'}`)
      }
      if (this.query.search_value) {
        tags.push(`检索：${this.query.search_value}`)
      }
      return tags
    },
    selectReviewItems() {
      return [
        `运行环境：${this.runtimeEnvInfo.label}`,
        `已选数量：${this.selection.length}`,
        `当前上级筛选：${this.query.pid || '全部层级'}`,
        `操作类型：${this.selectTitle || '大厅维护'}`
      ]
    },
    selectRiskHint() {
      return this.runtimeEnvInfo.isProd
        ? '正式环境会直接影响大厅树结构和前端内容入口，执行上级调整、禁用、删除前请先复核。'
        : '当前环境适合验证大厅结构维护、筛选与树形回显，不要把测试操作当作正式运营结果。'
    },
    followupHint() {
      if (this.selection.length) {
        return `当前已选 ${this.selection.length} 项大厅，批量处理后建议先去仓库和秤设备页核对归属结构，再去轮播页看前台入口是否仍然一致。`
      }
      if (this.query.pid) {
        return '当前已经收窄到某个上级大厅，下一步更适合去仓库和秤设备页核对这个层级的真实承接，再去轮播页看入口展示。'
      }
      return '大厅页更像内容入口结构台账，处理完后通常要先去仓库页、秤设备页复核承接结构，再去轮播页检查首页入口。'
    },
    followupTags() {
      return [
        `大厅总量：${this.count || 0}`,
        `已选：${this.selection.length} 项`,
        `当前上级：${this.query.pid || '全部层级'}`,
        `环境：${this.runtimeEnvInfo.label}`
      ]
    },
    hallFocusLabel() {
      if (this.selection.length) {
        return '先复核结构调整'
      }
      if (this.query.pid) {
        return '先看单层级入口'
      }
      if (this.query.is_disable === 1) {
        return '先看禁用入口'
      }
      return '先看整体层级'
    },
    hallGuideCards() {
      return [
        {
          title: '第一步先确认你改的是哪一层',
          desc: this.query.pid
            ? `当前已经收窄到上级 ${this.query.pid}，适合检查这个层级下的入口是不是还完整。`
            : '当前看到的是整棵大厅树，先看顶层和二级结构是否顺，再处理单个节点。',
          action: this.query.pid ? '单层级优先核入口完整性。' : '全局优先看结构顺序。'
        },
        {
          title: '第二步再决定改上级还是改状态',
          desc: this.selection.length
            ? `当前已选 ${this.selection.length} 项，提交前先想清楚这是结构迁移，还是仅仅不想让前台展示。`
            : '上级调整会影响入口归属，禁用会影响前台可见，删除则通常是最后一步。',
          action: this.selection.length ? '先分清迁移还是下线。' : '删除动作尽量放最后。'
        },
        {
          title: '第三步回前台入口位承接',
          desc: '大厅结构改完后，最好继续去链接、轮播和公告页复核，不然很容易出现前台入口断链或指向不一致。',
          action: '结构页负责树，承接页负责展示。'
        }
      ]
    }
  },
  created() {
    this.height = screenHeight(290)
    this.list()
  },
  methods: {
    handleEntryContextPrimary() {
      if (this.entrySourceLabel === '来自仓库管理') {
        this.goToPage('/setting/warehouse')
        return
      }
      this.goToPage('/setting/carousel')
    },
    goToEntryContextBack() {
      if (this.entrySourceLabel === '来自控制台总览') {
        this.$router.push({ path: '/dashboard', query: { from: 'setting-hall' } })
        return
      }
      if (this.entrySourceLabel === '来自仓库管理') {
        this.$router.push({ path: '/setting/warehouse', query: { from: 'setting-hall' } })
        return
      }
      if (this.entrySourceLabel === '来自轮播管理') {
        this.$router.push({ path: '/setting/carousel', query: { from: 'setting-hall' } })
      }
    },
    // 列表
    list() {
      this.loading = true
      list(this.query)
        .then((res) => {
          this.data = res.data.list
          this.trees = res.data.tree
          this.exps = res.data.exps
          this.count = res.data.count
          this.isExpandAll = false
          this.loading = false
          this.recentActionSummary = `已加载大厅列表，共 ${res.data.count || 0} 项。`
        })
        .catch(() => {
          this.loading = false
        })
    },
    // 添加修改
    add(row) {
      this.dialog = true
      this.dialogTitle = this.name + '添加'
      this.reset()
      if (row) {
        this.model.pid = row[this.idkey]
        this.recentActionSummary = `准备在大厅 ${row[this.idkey]} 下添加子节点。`
      } else {
        this.recentActionSummary = '准备添加顶级大厅节点。'
      }
    },
    edit(row) {
      this.dialog = true
      this.dialogTitle = this.name + '修改：' + row[this.idkey]
      this.recentActionSummary = `准备修改大厅 ${row[this.idkey]}。`
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
                this.recentActionSummary = `已修改大厅 ${this.model[this.idkey]}，名称：${
                  this.model.title || '未命名'
                }。`
                ElMessage.success(res.msg)
                this.$nextTick(() => {
                  ElMessage.info('大厅已提交，请继续去仓库管理、秤设备和轮播管理页各核对一次。')
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
                this.recentActionSummary = `已添加大厅，名称：${this.model.title || '未命名'}。`
                ElMessage.success(res.msg)
                this.$nextTick(() => {
                  ElMessage.info('大厅已提交，请继续去仓库管理、秤设备和轮播管理页各核对一次。')
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
    search() {
      this.recentActionSummary = `已按 ${this.query.search_field || 'title'} 发起大厅检索。`
      this.list()
    },
    // 刷新
    refresh() {
      const limit = this.query.limit
      this.query = this.$options.data().query
      this.$refs['table'].clearSort()
      this.query.limit = limit
      this.recentActionSummary = '已重置大厅筛选条件。'
      this.list()
    },
    // 收起/展开
    expandAll(e) {
      this.expandFor(this.data, !e)
    },
    expandFor(data, isExpand) {
      data.forEach((i) => {
        this.$refs.table.toggleRowExpansion(i, isExpand)
        if (i.children) {
          this.expandFor(i.children, isExpand)
        }
      })
    },
    // 操作
    select(selection) {
      this.selection = selection
      this.selectIds = this.selectGetIds(selection).toString()
      if (selection.length) {
        this.recentActionSummary = `已勾选 ${selection.length} 项大厅，待处理 ID：${this.selectIds}。`
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
        if (selectType == 'editpid') {
          this.selectTitle = this.name + '修改上级'
        } else if (selectType === 'disable') {
          this.selectTitle = this.name + '是否禁用'
        } else if (selectType === 'dele') {
          this.selectTitle = this.name + '删除'
        }
        this.selectDialog = true
        this.selectType = selectType
        this.recentActionSummary = `准备执行${this.selectTitle}，当前已选 ${this.selection.length} 项大厅。`
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
        if (selectType == 'editpid') {
          this.editpid(this.selection)
        } else if (selectType === 'disable') {
          this.disable(this.selection, true)
        } else if (selectType === 'dele') {
          this.dele(this.selection)
        }
        this.selectDialog = false
      }
    },
    confirmRowDisable(row) {
      const nextState = row.is_disable
      const actionText = nextState === 1 ? '禁用' : '启用'
      ElMessageBox.confirm(
        `确定要${actionText}大厅“${row.title || row[this.idkey]}”吗？`,
        '状态确认',
        {
          type: 'warning',
          closeOnClickModal: false,
          closeOnPressEscape: false
        }
      )
        .then(() => {
          this.disable([row])
        })
        .catch(() => {
          row.is_disable = nextState === 1 ? 0 : 1
        })
    },
    // 修改上级
    editpid(row) {
      editpid({
        ids: this.selectGetIds(row),
        pid: this.pid
      })
        .then((res) => {
          this.list()
          this.selectDialog = false
          this.recentActionSummary = `已批量修改上级，目标上级：${this.pid || '顶级'}。`
          ElMessage.success(res.msg)
        })
        .catch(() => {
          this.loading = false
        })
    },
    // 是否禁用
    disable(row, select = false) {
      if (row.length === 0) {
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
            } 项大厅。`
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
        dele({
          ids: this.selectGetIds(row)
        })
          .then((res) => {
            this.list()
            this.recentActionSummary = `已删除 ${row.length} 项大厅。`
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
          from: 'setting-hall'
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

.page-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 10px;
}

.page-header__title,
.filter-head__title {
  font-size: 18px;
  font-weight: 600;
  color: #111827;
}

.page-header__desc {
  margin-top: 4px;
  font-size: 12px;
  color: #6b7280;
  line-height: 1.6;
}

.page-header__meta,
.summary-bar__main,
.summary-bar__side {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 6px;
}

.summary-chip {
  display: inline-flex;
  align-items: center;
  min-height: 24px;
  padding: 0 8px;
  border-radius: 999px;
  background: #f3f4f6;
  color: #4b5563;
  font-size: 12px;
  line-height: 1;
}

.summary-chip--env,
.summary-chip--strong {
  background: #e8f0ff;
  color: #1d4ed8;
}

.summary-chip--risk {
  background: #fff7ed;
  color: #b45309;
}

.filter-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 10px;
}

.filter-head__meta {
  font-size: 12px;
  color: #6b7280;
}

.compact-query-form :deep(.el-form-item) {
  margin-bottom: 10px;
}

.compact-query-form :deep(.el-date-editor.el-input__wrapper),
.compact-query-form :deep(.el-date-editor--daterange) {
  width: 100%;
}

.query-search-inline {
  display: flex;
  align-items: center;
  gap: 8px;
}

.query-search-inline .input-with-select {
  flex: 1;
  min-width: 0;
}

.summary-bar {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 10px;
  margin-top: 2px;
  padding-top: 2px;
}

.followup-panel {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-top: 10px;
  padding: 12px 14px;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  background: #fbfdff;
}

.plain-guide {
  margin-top: 10px;
  padding: 12px 14px;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
}

.plain-guide__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.plain-guide__title {
  font-size: 13px;
  font-weight: 700;
  color: #111827;
}

.plain-guide__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #6b7280;
}

.plain-guide__badge {
  display: inline-flex;
  align-items: center;
  min-height: 24px;
  padding: 0 8px;
  border-radius: 999px;
  background: #eef2ff;
  color: #4338ca;
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
  padding: 12px;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  background: #fff;
}

.plain-guide-card__title {
  font-size: 13px;
  font-weight: 700;
  color: #111827;
}

.plain-guide-card__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #6b7280;
}

.plain-guide-card__action {
  margin-top: 8px;
  font-size: 12px;
  color: #4f46e5;
}

.followup-panel__main {
  flex: 1;
  min-width: 0;
}

.followup-panel__title {
  font-size: 13px;
  font-weight: 700;
  color: #111827;
}

.followup-panel__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #6b7280;
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
  min-height: 24px;
  padding: 0 8px;
  border-radius: 999px;
  background: #f3f4f6;
  color: #4b5563;
  font-size: 12px;
}

.followup-panel__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  justify-content: flex-end;
}

.select-review-panel {
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 12px;
  background: #fafafa;
}

.select-review-panel__title {
  font-size: 13px;
  font-weight: 600;
  color: #111827;
}

.select-review-panel__tags {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-top: 8px;
}

.select-review-panel__tags span {
  display: inline-flex;
  align-items: center;
  min-height: 24px;
  padding: 0 8px;
  border-radius: 999px;
  background: #eef2ff;
  color: #4338ca;
  font-size: 12px;
}

.select-review-panel__hint {
  margin-top: 8px;
  font-size: 12px;
  line-height: 1.6;
  color: #6b7280;
}

.select-review-panel--dialog {
  margin-bottom: 18px;
}

.operation_btn {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin: 4px 0 8px;
}

.operation_btn__left,
.operation_btn__right {
  display: flex;
  align-items: center;
}

.action-cluster {
  display: inline-flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
  margin-right: 10px;
}

.compact-hall-table :deep(.el-table__cell) {
  padding: 8px 0;
}

.compact-hall-table :deep(.el-table__body .cell) {
  line-height: 1.4;
}

@media (max-width: 900px) {
  .entry-context-banner,
  .plain-guide__header,
  .followup-panel,
  .page-header,
  .filter-head,
  .summary-bar,
  .operation_btn {
    flex-direction: column;
    align-items: stretch;
  }
}
</style>
