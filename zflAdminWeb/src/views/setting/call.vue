<template>
  <div class="app-container">
    <el-card class="app-head head-pb20">
      <div class="module-header">
        <div>
          <div class="module-header__title">秤设备</div>
          <div class="module-header__desc">
            统一处理秤设备树、大厅归属和启停维护，默认首屏先给到直接可操作的列表入口。
          </div>
        </div>
        <div class="module-header__meta">
          <span class="module-header__badge">{{ runtimeEnvInfo.label }}</span>
          <span class="module-header__badge module-header__badge--sub">{{
            runtimeEnvInfo.dataMode
          }}</span>
        </div>
      </div>
      <!-- 查询 -->
      <el-form :model="query" ref="searchForm" label-width="85px" class="filter-form">
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
          <el-col :span="6">
            <el-form-item label="大厅：" prop="setting_hall_id">
              <el-cascader
                v-model="query.setting_hall_id"
                :options="hall_list"
                :props="props"
                @change="search()"
                clearable
                filterable
                style="width: 100%"
              />
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
                  <el-option value="code" label="编码" />
                  <el-option value="title" label="名称" />
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
      <div class="call-summary-bar">
        <div class="call-summary-bar__chips">
          <span class="summary-chip summary-chip--primary">总数 {{ count }}</span>
          <span class="summary-chip">已选 {{ selection.length }} 项</span>
          <span class="summary-chip">大厅 {{ hall_list.length }}</span>
          <span class="summary-chip">
            {{
              query.is_disable === undefined
                ? '全部状态'
                : query.is_disable === 0
                ? '启用中'
                : '已禁用'
            }}
          </span>
          <span class="summary-chip">{{ runtimeEnvInfo.dataMode }}</span>
          <span v-for="item in activeFilterTags" :key="item" class="summary-chip">{{ item }}</span>
          <span v-if="!activeFilterTags.length" class="summary-chip">默认条件：全部秤设备</span>
          <span v-if="recentActionSummary" class="summary-chip summary-chip--muted">{{
            recentActionSummary
          }}</span>
        </div>
        <div class="call-summary-bar__hint" :class="summaryHintClass">
          <span class="call-summary-bar__hint-title">{{ summaryHintTitle }}</span>
          <span class="call-summary-bar__hint-text">{{ selectRiskHint }}</span>
        </div>
      </div>
      <div class="plain-guide">
        <div class="plain-guide__header">
          <div>
            <div class="plain-guide__title">这页建议先这样看</div>
            <div class="plain-guide__desc">
              先分清你是在查设备归属，还是在做启停治理。秤设备页的重点不是文案，而是设备属于哪个大厅、还能不能正常承接业务。
            </div>
          </div>
          <span class="plain-guide__badge">{{ callFocusLabel }}</span>
        </div>
        <div class="plain-guide__grid">
          <div v-for="item in callGuideCards" :key="item.title" class="plain-guide-card">
            <div class="plain-guide-card__title">{{ item.title }}</div>
            <div class="plain-guide-card__desc">{{ item.desc }}</div>
            <div class="plain-guide-card__action">{{ item.action }}</div>
          </div>
        </div>
      </div>
      <div class="followup-panel">
        <div class="followup-panel__main">
          <div class="followup-panel__title">看完秤设备后继续去哪</div>
          <div class="followup-panel__desc">{{ followupHint }}</div>
          <div class="followup-panel__tags">
            <span v-for="item in followupTags" :key="item">{{ item }}</span>
          </div>
        </div>
        <div class="followup-panel__actions">
          <el-button type="primary" @click="goToPage('/setting/warehouse')">去仓库管理</el-button>
          <el-button @click="goToPage('/setting/hall')">去大厅管理</el-button>
          <el-button @click="goToPage('/analytics')">去数据分析</el-button>
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
        <el-form-item v-if="selectType === 'removec'">
          <span style="">确定要解除选中的{{ name }}的内容吗？</span>
        </el-form-item>
        <el-form-item v-else-if="selectType === 'editpid'" label="上级">
          <el-cascader
            v-model="pid"
            :options="trees"
            :props="props"
            class="w-full"
            placeholder="一级秤"
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
      <div class="list-header">
        <div>
          <div class="list-header__title">秤设备列表</div>
          <div class="list-header__desc">支持树形结构维护、批量禁用和大厅归属管理。</div>
        </div>
        <div class="list-header__meta">已选 {{ selection.length }} 项</div>
      </div>
      <div class="operation_btn mb5">
        <!-- 操作 -->
        <div>
          <div class="action-cluster">
            <span class="action-cluster__title">结构维护</span>
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
            <span class="action-cluster__title">状态处理</span>
            <el-button title="是否禁用" @click="selectOpen('disable')">禁用</el-button>
            <el-button title="删除" @click="selectOpen('dele')">删除</el-button>
          </div>
        </div>
        <div>
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
        :row-key="idkey"
        @selection-change="select"
      >
        <el-table-column type="selection" width="42" title="全选/反选" />
        <el-table-column :prop="idkey" label="ID" min-width="80" />
        <el-table-column prop="code" label="编码" min-width="100" show-overflow-tooltip />
        <el-table-column prop="title" label="名称" min-width="250" show-overflow-tooltip />
        <el-table-column prop="hall_title" label="大厅" min-width="100" show-overflow-tooltip />
        <el-table-column prop="address" label="地址" min-width="150" show-overflow-tooltip />
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
              placeholder="一级秤"
              clearable
              filterable
            />
          </el-form-item>
          <el-form-item label="大厅" prop="setting_hall_id">
            <el-cascader
              v-model="model.setting_hall_id"
              :options="hall_list"
              :props="props"
              class="w-full"
              placeholder="请选择大厅"
              clearable
              filterable
            />
          </el-form-item>
          <el-form-item label="编码" prop="code">
            <el-input v-model="model.code" placeholder="请输入秤编码（唯一）" clearable />
          </el-form-item>
          <el-form-item label="名称" prop="title">
            <el-input v-model="model.title" placeholder="请输入秤名称" clearable />
          </el-form-item>
          <el-form-item label="地址" prop="address">
            <el-input v-model="model.address" placeholder="请输入仓库地址" clearable />
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
import { list, info, add, edit, dele, disable, editpid } from '@/api/setting/call.js'
import { shortcuts } from '@/utils/getDate.js'
import { select as hallSelect } from '@/api/setting/hall.js'
import { getAdminRuntimeHint, resolveAdminRuntimeEnv } from '@/utils/runtime-env'
export default {
  name: 'SettingCall',
  data() {
    return {
      name: '秤',
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
        sort: 250,
        setting_hall_id: undefined,
        address: ''
      },
      rules: {
        code: [{ required: true, message: '请输入秤编码', trigger: 'blur' }],
        title: [{ required: true, message: '请输入秤名称', trigger: 'blur' }],
        setting_hall_id: [{ required: true, message: '请选择大厅', trigger: 'blur' }]
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
      hall_list: [],
      recentActionSummary: '',
      runtimeEnvInfo: resolveAdminRuntimeEnv()
    }
  },
  computed: {
    runtimeHint() {
      return getAdminRuntimeHint(this.runtimeEnvInfo)
    },
    activeFilterTags() {
      const tags = []
      if (this.query.date_value?.length === 2) {
        tags.push(`添加时间：${this.query.date_value[0]} 至 ${this.query.date_value[1]}`)
      }
      if (this.query.is_disable !== undefined) {
        tags.push(`状态：${this.query.is_disable === 1 ? '禁用' : '启用'}`)
      }
      if (this.query.pid) {
        tags.push(`上级：${this.query.pid}`)
      }
      if (this.query.setting_hall_id) {
        tags.push(`大厅：${this.query.setting_hall_id}`)
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
        `大厅数量：${this.hall_list.length}`,
        `操作类型：${this.selectTitle || '秤设备维护'}`
      ]
    },
    selectRiskHint() {
      return this.runtimeEnvInfo.isProd
        ? '正式环境会直接影响交易称重入口和大厅归属，请先复核上级调整、禁用和删除。'
        : '当前环境适合验证秤设备结构维护、筛选与树形回显，不要把测试操作当作正式运营结果。'
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
      if (this.selection.length) {
        return `当前已选 ${this.selection.length} 项秤设备，处理完后建议先去仓库页和大厅页核对归属结构，再到数据分析页确认设备侧回传是否正常。`
      }
      if (this.query.setting_hall_id) {
        return '当前已经收窄到某个大厅，下一步更适合先去仓库页复核归属，再回大厅页看层级，最后到数据分析页观察这个范围的设备回传。'
      }
      return '秤设备页更像设备归属与启停台账，处理完后通常要先去仓库页核对承接，再回大厅页看结构，最后到数据分析页检查下游是否还一致。'
    },
    followupTags() {
      return [
        `设备总量：${this.count || 0}`,
        `已选：${this.selection.length} 项`,
        `大厅：${this.query.setting_hall_id || '全部大厅'}`,
        `环境：${this.runtimeEnvInfo.label}`
      ]
    },
    callFocusLabel() {
      if (this.selection.length) {
        return '先复核批量处理'
      }
      if (this.query.setting_hall_id) {
        return '先看单大厅归属'
      }
      if (this.query.is_disable === 1) {
        return '先看禁用影响'
      }
      return '先看整体设备分布'
    },
    callGuideCards() {
      return [
        {
          title: '第一步先看设备归属',
          desc: this.query.setting_hall_id
            ? `当前已经收窄到大厅 ${this.query.setting_hall_id}，优先确认这个范围内的秤设备是不是都归到正确入口。`
            : '秤设备首先是归属台账，先看大厅、上级和地址，不要直接从启停开关开始处理。',
          action: this.query.setting_hall_id ? '单大厅先核归属。' : '全局先看归属结构。'
        },
        {
          title: '第二步再决定做启停还是结构调整',
          desc: this.selection.length
            ? `当前已选 ${this.selection.length} 项，提交前要想清楚这是临时停用设备，还是设备本身挂错了上级或大厅。`
            : '启停只解决可用性，上级和大厅调整才解决归属错误；两类动作不要混着做。',
          action: this.selection.length ? '先分清停用还是迁移。' : '归属错就改结构，不要只禁用。'
        },
        {
          title: '第三步回仓库和分析页承接',
          desc: '设备调整完后，最好继续回仓库管理和数据分析页，看仓储承接和设备回传有没有一起跟上。',
          action: '设备页负责配置，下游页负责验证。'
        }
      ]
    }
  },
  created() {
    this.height = screenHeight(290)
    this.getHall()
    this.list()
  },
  methods: {
    //查询大厅
    getHall() {
      this.loading = true
      hallSelect({})
        .then((res) => {
          this.hall_list = res.data
          this.loading = false
          this.recentActionSummary = `已加载大厅选项，共 ${res.data.length || 0} 项。`
        })
        .catch(() => {
          this.loading = false
        })
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
          this.recentActionSummary = `已加载秤设备列表，共 ${res.data.count || 0} 项。`
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
        this.recentActionSummary = `准备在秤设备 ${row[this.idkey]} 下添加子节点。`
      } else {
        this.recentActionSummary = '准备添加顶级秤设备节点。'
      }
    },
    edit(row) {
      this.dialog = true
      this.dialogTitle = this.name + '修改：' + row[this.idkey]
      this.recentActionSummary = `准备修改秤设备 ${row[this.idkey]}。`
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
                this.recentActionSummary = `已修改秤设备 ${this.model[this.idkey]}，名称：${
                  this.model.title || '未命名'
                }。`
                ElMessage.success(res.msg)
                this.$nextTick(() => {
                  ElMessage.info('秤设备已提交，请继续去仓库管理、大厅管理和数据分析页各核对一次。')
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
                this.recentActionSummary = `已添加秤设备，名称：${this.model.title || '未命名'}。`
                ElMessage.success(res.msg)
                this.$nextTick(() => {
                  ElMessage.info('秤设备已提交，请继续去仓库管理、大厅管理和数据分析页各核对一次。')
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
      this.recentActionSummary = `已按 ${this.query.search_field || 'title'} 发起秤设备检索。`
      this.list()
    },
    // 刷新
    refresh() {
      const limit = this.query.limit
      this.query = this.$options.data().query
      this.$refs['table'].clearSort()
      this.query.limit = limit
      this.recentActionSummary = '已重置秤设备筛选条件。'
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
        this.recentActionSummary = `已勾选 ${selection.length} 项秤设备，待处理 ID：${this.selectIds}。`
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
        this.recentActionSummary = `准备执行${this.selectTitle}，当前已选 ${this.selection.length} 项秤设备。`
      }
    },
    selectCancel() {
      this.selectDialog = false
    },
    confirmRowDisable(row) {
      const nextValue = Number(row.is_disable || 0)
      const previousValue = nextValue === 1 ? 0 : 1
      const actionLabel = nextValue === 1 ? '禁用' : '启用'
      ElMessageBox.confirm(
        `确定要${actionLabel}秤设备“${row.title || row.code || row[this.idkey]}”吗？`,
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
            } 项秤设备。`
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
            this.recentActionSummary = `已删除 ${row.length} 项秤设备。`
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
          from: 'setting-call'
        }
      })
    }
  }
}
</script>

<style scoped>
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

.call-summary-bar,
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

.call-summary-bar {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.call-summary-bar__chips,
.select-review-panel__tags {
  display: flex;
  flex: 1;
  flex-wrap: wrap;
  align-items: center;
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

.call-summary-bar__hint {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 240px;
  padding: 10px 12px;
  border-radius: 12px;
}

.call-summary-bar__hint.is-safe {
  background: #eaf8ef;
  color: #15803d;
}

.call-summary-bar__hint.is-warning {
  background: #fff5e8;
  color: #b45309;
}

.call-summary-bar__hint.is-active {
  background: #e8f0ff;
  color: #1d4ed8;
}

.call-summary-bar__hint-title,
.select-review-panel__title {
  font-size: 13px;
  font-weight: 700;
  color: #0f172a;
}

.call-summary-bar__hint-text,
.select-review-panel__hint {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: inherit;
}

.plain-guide {
  margin-top: 12px;
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
  .plain-guide__header,
  .followup-panel,
  .call-summary-bar,
  .section-title-row {
    flex-direction: column;
  }

  .section-title-row__meta {
    white-space: normal;
  }
}
</style>
