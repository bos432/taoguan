<template>
  <div class="app-container">
    <div class="page-header">
      <div class="page-header__main">
        <div class="page-header__title">巡检机构</div>
        <div class="page-header__desc">集中查看机构账号、联系电话和禁用状态，便于按当前范围直接处理。</div>
      </div>
      <div class="page-header__meta">
        <span class="summary-chip summary-chip--env" :class="runtimeModeBadgeClass">{{ runtimeModeLabel }}</span>
      </div>
    </div>
    <el-card class="app-head head-pb20">
      <div class="filter-head">
        <div class="filter-head__title">机构检索</div>
        <div class="filter-head__meta">按时间、状态和关键词快速定位机构账号</div>
      </div>
      <!-- 查询 -->
      <el-form :model="query" ref="searchForm" label-width="72px" size="small" class="compact-query-form">
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
          <el-col :xl="13" :lg="11" :md="18" :sm="24">
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
                    <el-option value="title" label="机构名称" />
                    <el-option value="phone" label="手机" />
                    <el-option value="username" label="机构账号" />
                    <el-option value="remark" label="备注" />
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
          <span class="summary-chip">字段 {{ currentSearchLabel }}</span>
          <span class="summary-chip">
            状态 {{
              query.is_disable === 1 ? '仅看禁用' : query.is_disable === 0 ? '仅看启用' : '全部状态'
            }}
          </span>
          <span v-for="item in activeFilterTags" :key="item" class="summary-chip">{{ item }}</span>
        </div>
        <div class="summary-bar__side">
          <span class="summary-chip summary-chip--risk">{{ inspectionFollowupRiskText }}</span>
          <span v-if="recentActionSummary" class="summary-chip">{{ recentActionSummary }}</span>
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
      <el-form label-width="120px">
        <el-form-item v-if="selectType === 'status'" label="状态">
          <el-select v-model="status" class="ya-search-value">
            <el-option v-for="(item, index) in statuss" :key="index" :label="item" :value="index" />
          </el-select>
        </el-form-item>
        <el-form-item v-else-if="selectType === 'disable'" label="是否禁用">
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
          <el-button type="primary" @click="add()">添加</el-button>
          <el-button title="删除" @click="selectOpen('dele')">删除</el-button>
          <el-button title="禁用" @click="selectOpen('disable')">禁用</el-button>
        </div>
        <div class="operation_btn__right">
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
        size="small"
        class="compact-inspection-table"
        @sort-change="sort"
        @selection-change="select"
      >
        <el-table-column type="selection" width="42" title="全选/反选" />
        <el-table-column :prop="idkey" label="ID" width="80" sortable="custom" />
        <el-table-column prop="title" label="机构名称" min-width="200" show-overflow-tooltip />
        <el-table-column prop="phone" label="联系电话" min-width="120" show-overflow-tooltip />
        <el-table-column prop="address" label="详细地址" min-width="120" show-overflow-tooltip />
        <el-table-column prop="username" label="机构账号" min-width="120" show-overflow-tooltip />
        <el-table-column prop="remark" label="备注" min-width="120" show-overflow-tooltip />
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
        <el-table-column prop="sort" label="排序" min-width="120" show-overflow-tooltip />
        <el-table-column prop="create_time" label="添加时间" width="165" sortable="custom" />
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
    >
      <el-scrollbar native :max-height="height - 30">
        <el-form ref="ref" :rules="rules" :model="model" label-width="100px">
          <el-form-item label="机构名称" prop="title">
            <el-input v-model="model.title" placeholder="请输入机构名称" clearable />
          </el-form-item>
          <el-form-item label="联系电话" prop="phone">
            <el-input v-model="model.phone" autosize placeholder="请输入联系电话" />
          </el-form-item>
          <el-form-item label="详细地址" prop="address">
            <el-input v-model="model.address" autosize placeholder="请输入详细地址" />
          </el-form-item>
          <el-form-item label="机构账号" prop="username">
            <el-input v-model="model.username" autosize placeholder="请输入机构账号" />
          </el-form-item>
          <el-form-item label="登录密码" prop="password">
            <el-input
              v-model="model.password"
              show-password
              type="password"
              autosize
              placeholder="请输入登录密码"
            />
          </el-form-item>
          <el-form-item label="备注" prop="remark">
            <el-input v-model="model.remark" placeholder="" clearable />
          </el-form-item>
          <el-form-item label="排序" prop="sort">
            <el-input v-model="model.sort" type="number" placeholder="排序" clearable />
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
import Pagination from '@/components/Pagination/index.vue'
import { arrayColumn } from '@/utils/index'
import { getPageLimit } from '@/utils/settings'
import {
  list,
  info,
  add,
  edit,
  dele,
  status as editsta,
  disable
} from '@/api/inspection/inspection'
import { resolveAdminRuntimeEnv } from '@/utils/runtime-env'

export default {
  name: 'inspection',
  components: { Pagination },
  computed: {
    runtimeModeLabel() {
      return this.runtimeEnvInfo.label
    },
    runtimeModeBadgeClass() {
      return this.runtimeEnvInfo.isProd ? 'is-production' : 'is-development'
    },
    currentSearchLabel() {
      const fieldMap = {
        id: 'ID',
        title: '机构名称',
        phone: '手机',
        username: '机构账号',
        remark: '备注'
      }
      return fieldMap[this.query.search_field] || '综合筛选'
    },
    activeFilterTags() {
      const tags = []
      if (Array.isArray(this.query.date_value) && this.query.date_value.length === 2) {
        tags.push(`时间：${this.query.date_value[0]} 至 ${this.query.date_value[1]}`)
      }
      if (this.query.is_disable !== undefined && this.query.is_disable !== '') {
        tags.push(`状态：${Number(this.query.is_disable) === 1 ? '禁用' : '启用'}`)
      }
      if (this.query.search_value) {
        tags.push(`检索：${this.currentSearchLabel}=${this.query.search_value}`)
      }
      return tags
    },
    inspectionFollowupBadgeText() {
      if (this.selection.length > 0) return '待提交复核'
      if (this.activeFilterTags.length > 0) return '筛选已收敛'
      return '默认巡检'
    },
    inspectionFollowupBadgeClass() {
      if (this.selection.length > 0) return 'is-active'
      if (this.query.is_disable !== undefined && this.query.is_disable !== '') return 'is-warning'
      return 'is-safe'
    },
    inspectionFollowupHint() {
      if (this.selection.length > 0) {
        return `当前已勾选 ${this.selection.length} 个机构账号，可继续做禁用和删除处理，但建议先复核联系电话和账号归属。`
      }
      if (this.activeFilterTags.length > 0) {
        return '当前机构列表已经按筛选条件收敛，建议先抽查账号、电话和地址，再继续批量处理。'
      }
      return '当前为机构巡检视角，建议优先按状态和关键词收敛范围，再做账号治理。'
    },
    inspectionFollowupTags() {
      const disabledCount = Array.isArray(this.data)
        ? this.data.filter((item) => Number(item.is_disable) === 1).length
        : 0
      return [
        `已选机构：${this.selection.length} 项`,
        `当前页禁用：${disabledCount} 项`,
        `筛选标签：${this.activeFilterTags.length} 项`,
        `环境：${this.runtimeModeLabel}`
      ]
    },
    inspectionFollowupRiskText() {
      if (this.selection.length > 0) {
        return '机构账号禁用和删除会直接影响巡检端登录与使用，请先确认该机构是否仍在服务中。'
      }
      return this.runtimeEnvInfo.isProd
        ? '正式环境中的机构账号会影响真实巡检业务，请先通过筛选缩小范围，再执行禁用或删除。'
        : '联调环境适合验证机构账号新增、编辑和禁用流程，不建议把测试账号当成正式巡检机构。'
    }
  },
  data() {
    return {
      name: '机构',
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
        is_disable: undefined,
        type: undefined
      },
      data: [],
      count: 0,
      dialog: false,
      dialogTitle: '',
      model: {
        id: undefined, //主键ID
        title: undefined, //机构名称
        username: undefined, //机构账号
        password: undefined, //登陆密码
        region_id: undefined, //机构地区
        address: undefined, //详细地址
        phone: undefined, //联系电话
        auth_state: 0, //审核状态：0、待审核 1、审核通过 2、审核失败
        auth_msg: undefined, //审核原因
        sort: 250, //排序
        remark: undefined //备注
      },
      rules: {
        title: [{ required: true, message: '请输入机构名称', trigger: 'blur' }],
        username: [{ required: true, message: '请输入机构账号', trigger: 'blur' }],
        password: [{ required: true, message: '请输入登录密码', trigger: 'blur' }]
      },
      types: [],
      statuss: [],
      selection: [],
      selectIds: '',
      selectTitle: '操作',
      selectDialog: false,
      selectType: '',
      status: 0,
      is_disable: 0,
      recentActionSummary: '',
      runtimeEnvInfo: resolveAdminRuntimeEnv()
    }
  },
  created() {
    this.height = screenHeight()
    this.list()
  },
  methods: {
    // 列表
    list() {
      this.loading = true
      list(this.query)
        .then((res) => {
          this.data = res.data.list
          this.count = res.data.count
          this.types = res.data.types
          this.statuss = res.data.statuss
          this.exps = res.data.exps
          this.recentActionSummary = `机构列表已刷新，共 ${res.data.count || 0} 条，当前环境：${
            this.runtimeModeLabel
          }。`
          this.loading = false
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
    },
    edit(row) {
      this.dialog = true
      this.dialogTitle = this.name + '修改：' + row[this.idkey]
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
                this.recentActionSummary = `机构信息已保存：${this.model.title || '未命名机构'}。`
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
                this.recentActionSummary = `机构信息已新增：${this.model.title || '未命名机构'}。`
                ElMessage.success(res.msg)
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
      this.list()
    },
    // 刷新
    refresh() {
      const limit = this.query.limit
      this.query = this.$options.data().query
      this.$refs['table'].clearSort()
      this.query.limit = limit
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
        if (selectType === 'status') {
          this.selectTitle = this.name + '状态'
        } else if (selectType === 'disable') {
          this.selectTitle = this.name + '是否禁用'
        } else if (selectType === 'dele') {
          this.selectTitle = this.name + '删除'
        }
        this.selectDialog = true
        this.selectType = selectType
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
        if (selectType === 'status') {
          this.editsta(this.selection, true)
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
        `确定要${actionText}机构“${row.title || row[this.idkey]}”吗？`,
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
    // 状态
    editsta(row, select) {
      if (!row.length) {
        this.selectAlert()
      } else {
        this.loading = true
        var status = row[0].status
        if (select) {
          status = this.status
        }
        editsta({
          ids: this.selectGetIds(row),
          status: status
        })
          .then((res) => {
            this.list()
            this.recentActionSummary = `机构状态已更新：${row.length} 项。`
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.list()
          })
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
            this.recentActionSummary = `${is_disable === 1 ? '机构禁用' : '机构启用'}已提交：${
              row.length
            } 项。`
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
            this.recentActionSummary = `机构删除已提交：${row.length} 项。`
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.loading = false
          })
      }
    }
  }
}
</script>

<style scoped>
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
  line-height: 1.6;
  color: #6b7280;
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
.summary-chip--strong,
.summary-chip.is-development {
  background: #e8f0ff;
  color: #1d4ed8;
}

.summary-chip.is-production {
  background: #fff5e8;
  color: #b45309;
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

.compact-inspection-table :deep(.el-table__cell) {
  padding: 8px 0;
}

.compact-inspection-table :deep(.el-table__body .cell) {
  line-height: 1.4;
}

@media (max-width: 900px) {
  .page-header,
  .filter-head,
  .summary-bar,
  .operation_btn {
    flex-direction: column;
    align-items: stretch;
  }
}
</style>
