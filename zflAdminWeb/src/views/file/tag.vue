<template>
  <div class="app-container">
    <div class="page-header-simple">
      <div class="page-header-simple__main">
        <div>
          <div class="page-header-simple__title">文件标签</div>
          <div class="page-header-simple__desc">
            简化页面层级，保留筛选、批量处理和文件承接的操作语义。
          </div>
        </div>
        <el-tag :type="runtimeEnvInfo.tone">{{ runtimeEnvInfo.label }}</el-tag>
      </div>
      <div class="summary-bar">
        <span class="summary-bar__item">环境：{{ runtimeEnvInfo.label }}</span>
        <span class="summary-bar__item">数据模式：{{ runtimeEnvInfo.dataMode }}</span>
        <span class="summary-bar__item">总量：{{ count || 0 }} 条</span>
        <span class="summary-bar__item">已选：{{ selection.length }} 项</span>
        <span class="summary-bar__item">检索字段：{{ currentSearchLabel }}</span>
        <span class="summary-bar__item">状态：{{ statusSummaryText }}</span>
      </div>
    </div>
    <el-card class="app-head">
      <div class="section-title-row">
        <div>
          <div class="section-title-row__title">标签筛选</div>
          <div class="section-title-row__desc">
            支持按时间、状态和关键词快速筛选，不改变原有处理链路。
          </div>
        </div>
        <div class="section-title-row__meta">当前检索：{{ currentSearchLabel }}</div>
      </div>
      <div class="tag-plain-guide">
        <div class="tag-plain-guide__header">
          <div>
            <div class="tag-plain-guide__title">文件标签页第一次进来，建议先看关系</div>
            <div class="tag-plain-guide__desc">
              先判断你是在整理文件归类，还是在处理前台专题素材；标签本身只是聚合入口，真正文件还在文件库和分组里。
            </div>
          </div>
          <div class="tag-plain-guide__badge">{{ fileTagGuideFocusLabel }}</div>
        </div>
        <div class="tag-plain-guide__grid">
          <div v-for="item in fileTagGuideCards" :key="item.title" class="tag-plain-guide-card">
            <span class="tag-plain-guide-card__step">{{ item.step }}</span>
            <div class="tag-plain-guide-card__title">{{ item.title }}</div>
            <div class="tag-plain-guide-card__desc">{{ item.desc }}</div>
          </div>
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
                  <el-option value="tag_name" label="名称" />
                  <el-option value="tag_desc" label="描述" />
                  <el-option value="remark" label="备注" />
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
      <div v-if="activeFilterTags.length" class="active-filter-strip">
        <span class="active-filter-strip__label">当前筛选</span>
        <span v-for="item in activeFilterTags" :key="item" class="active-filter-strip__item">{{
          item
        }}</span>
      </div>
      <div v-if="recentActionSummary" class="active-filter-strip active-filter-strip--muted">
        <span class="active-filter-strip__label">最近操作</span>
        <span class="active-filter-strip__text">{{ recentActionSummary }}</span>
      </div>
      <div class="followup-panel">
        <div class="followup-panel__main">
          <div class="followup-panel__title">看完标签后继续去哪</div>
          <div class="followup-panel__desc">{{ followupHint }}</div>
          <div class="followup-panel__tags">
            <span v-for="item in followupTags" :key="item">{{ item }}</span>
          </div>
        </div>
        <div class="followup-panel__actions">
          <el-button type="primary" @click="goToPage('/file/file')">去文件库</el-button>
          <el-button @click="goToPage('/file/group')">去文件分组</el-button>
          <el-button @click="goToPage('/file/setting')">去文件设置</el-button>
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
        <el-form-item v-if="selectType === 'removef'">
          <span style="">确定要解除选中的{{ name }}的文件吗？</span>
        </el-form-item>
        <el-form-item v-else-if="selectType === 'disable'" label="是否禁用">
          <!--          <el-switch v-model="is_disable" :active-value="1" :inactive-value="0" />-->
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
          <div class="section-title-row__title">标签列表</div>
          <div class="section-title-row__desc">支持文件承接、快速切换状态和标签信息维护。</div>
        </div>
        <div class="section-title-row__meta">已选 {{ selection.length }} 项</div>
      </div>
      <div class="operation_btn mb5">
        <!-- 操作 -->
        <div>
          <el-button type="primary" @click="add()">添加</el-button>
          <el-button title="删除" @click="selectOpen('dele')">删除</el-button>
          <el-button title="是否禁用" @click="selectOpen('disable')">禁用</el-button>
          <el-button title="解除文件" @click="selectOpen('removef')">解除文件</el-button>
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
        <el-table-column prop="tag_name" label="名称" min-width="130" show-overflow-tooltip />
        <el-table-column prop="tag_desc" label="描述" min-width="160" show-overflow-tooltip />
        <el-table-column prop="remark" label="备注" min-width="150" show-overflow-tooltip />
        <el-table-column prop="is_disable" label="禁用" min-width="85" sortable="custom">
          <template #default="scope">
            <el-switch
              v-model="scope.row.is_disable"
              class="ml-2"
              style="--el-switch-on-color: #ff4949; --el-switch-off-color: #4073fa"
              inline-prompt
              active-text="禁用"
              inactive-text="启用"
              :active-value="1"
              :inactive-value="0"
              @change="disable([scope.row])"
            />
          </template>
        </el-table-column>
        <el-table-column prop="sort" label="排序" min-width="85" sortable="custom" />
        <el-table-column prop="create_time" label="添加时间" width="165" sortable="custom" />
        <el-table-column prop="update_time" label="修改时间" width="165" sortable="custom" />
        <el-table-column label="操作" width="130">
          <template #default="scope">
            <el-link type="primary" class="mr-1" :underline="false" @click="fileShow(scope.row)">
              文件
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
            <el-form-item label="名称" prop="tag_name">
              <el-input v-model="model.tag_name" placeholder="请输入名称" clearable />
            </el-form-item>
            <el-form-item label="描述" prop="tag_desc">
              <el-input
                v-model="model.tag_desc"
                type="textarea"
                autosize
                placeholder="请输入描述"
              />
            </el-form-item>
            <el-form-item label="备注" prop="remark">
              <el-input v-model="model.remark" placeholder="请输入备注" clearable />
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
      <!-- 标签文件 -->
      <el-dialog
        v-model="fileDialog"
        :title="fileDialogTitle"
        :close-on-click-modal="false"
        :close-on-press-escape="false"
        top="10vh"
        width="70%"
      >
        <!-- 标签文件操作 -->
        <el-row>
          <el-col>
            <el-button type="primary" title="解除" @click="fileSelectOpen('fileRemove')">
              解除
            </el-button>
            <el-input
              v-model="fileQuery.search_value"
              class="ya-search-value"
              placeholder="名称"
              clearable
            />
            <el-button type="primary" @click="file()">查询</el-button>
          </el-col>
        </el-row>
        <!-- 标签文件列表 -->
        <el-table
          ref="fileRef"
          v-loading="fileLoad"
          :data="fileData"
          :height="height - 50"
          @sort-change="fileSort"
          @selection-change="fileSelect"
        >
          <el-table-column type="selection" width="42" title="全选/反选" />
          <el-table-column :prop="filePk" label="ID" width="80" sortable="custom" />
          <el-table-column prop="file_url" label="文件" min-width="90">
            <template #default="scope">
              <FilePreview :file="scope.row" lazy />
            </template>
          </el-table-column>
          <el-table-column prop="file_type_name" label="类型" min-width="85" sortable="custom" />
          <el-table-column
            prop="file_name"
            label="名称"
            min-width="120"
            sortable="custom"
            show-overflow-tooltip
          />
          <el-table-column prop="file_ext" label="后缀" min-width="85" sortable="custom" />
          <el-table-column
            prop="file_size"
            label="大小"
            min-width="85"
            sortable="custom"
            show-overflow-tooltip
          />
          <el-table-column prop="group_name" label="分组" min-width="120" show-overflow-tooltip />
          <el-table-column prop="tag_names" label="标签" min-width="130" show-overflow-tooltip />
          <el-table-column prop="is_disable" label="禁用" min-width="85" sortable="custom">
            <template #default="scope">
              <el-switch
                v-model="scope.row.is_disable"
                :active-value="1"
                :inactive-value="0"
                disabled
              />
            </template>
          </el-table-column>
          <el-table-column prop="sort" label="排序" min-width="85" sortable="custom" />
          <el-table-column label="操作" min-width="70">
            <template #default="scope">
              <el-link
                type="primary"
                :underline="false"
                @click="fileSelectOpen('fileRemove', scope.row)"
              >
                解除
              </el-link>
            </template>
          </el-table-column>
        </el-table>
        <!-- 标签文件分页 -->
        <pagination
          v-show="fileCount > 0"
          v-model:total="fileCount"
          v-model:page="fileQuery.page"
          v-model:limit="fileQuery.limit"
          @pagination="file"
        />
      </el-dialog>
      <el-dialog
        v-model="fileSelectDialog"
        :title="fileSelectTitle"
        :close-on-click-modal="false"
        :close-on-press-escape="false"
        top="20vh"
      >
        <el-form ref="fileSelectRef" label-width="120px">
          <el-form-item v-if="fileSelectType === 'fileRemove'" label="标签ID">
            <span>{{ fileQuery[idkey] }}</span>
          </el-form-item>
          <el-form-item :label="fileName + 'ID'">
            <el-input v-model="fileSelectIds" type="textarea" autosize disabled />
          </el-form-item>
        </el-form>
        <template #footer>
          <el-button @click="fileSelectCancel">取消</el-button>
          <el-button type="primary" @click="fileSelectSubmit">提交</el-button>
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
import { list, info, add, edit, dele, disable, file, fileRemove } from '@/api/file/tag'
import { shortcuts } from '@/utils/getDate.js'
import { getAdminRuntimeHint, resolveAdminRuntimeEnv } from '@/utils/runtime-env'

export default {
  name: 'FileTag',
  components: { Pagination },
  computed: {
    currentSearchLabel() {
      const fieldMap = {
        tag_id: 'ID',
        tag_name: '名称',
        tag_desc: '描述',
        remark: '备注'
      }
      return fieldMap[this.query.search_field] || '综合筛选'
    },
    activeFilterTags() {
      const tags = []
      if (this.query.search_value) {
        tags.push(`${this.currentSearchLabel}：${this.query.search_value}`)
      }
      if (this.query.is_disable === 0) {
        tags.push('状态：启用')
      } else if (this.query.is_disable === 1) {
        tags.push('状态：禁用')
      }
      if (this.query.date_value && this.query.date_value.length === 2) {
        tags.push(`添加时间：${this.query.date_value[0]} 至 ${this.query.date_value[1]}`)
      }
      return tags
    },
    statusSummaryText() {
      if (this.fileDialog) {
        return '关联复核中'
      }
      if (this.selection.length) {
        return `待处理 ${this.selection.length} 项`
      }
      if (this.query.is_disable === 0) {
        return '仅看启用'
      }
      if (this.query.is_disable === 1) {
        return '仅看禁用'
      }
      return this.recentActionSummary || getAdminRuntimeHint(this.runtimeEnvInfo)
    },
    followupHint() {
      if (this.fileDialog) {
        return '当前正在复核标签关联文件，处理完建议回文件库抽查资源，再去分组页确认目录结构是否同步一致。'
      }
      if (this.selection.length) {
        return `当前已选择 ${this.selection.length} 个标签，批量处理前建议先确认这些标签是否仍被文件检索、专题或前台入口使用。`
      }
      return '标签页更适合做文件聚合和专题承接，处理完后通常要回文件库抽查资源，再去分组页核对结构。'
    },
    followupTags() {
      return [
        `总量：${this.count || 0} 条`,
        `已选：${this.selection.length} 项`,
        `状态：${this.query.is_disable === 1 ? '禁用' : this.query.is_disable === 0 ? '启用' : '全部'}`,
        `环境：${this.runtimeEnvInfo.label}`
      ]
    },
    fileTagGuideFocusLabel() {
      if (this.fileDialog) {
        return '当前重点：正在看标签下的真实文件，先确认是不是标签错挂，不要直接删标签'
      }
      if (this.selection.length > 0) {
        return `当前重点：已选 ${this.selection.length} 个文件标签，先确认会不会影响专题素材聚合`
      }
      if (this.query.is_disable === 1) {
        return '当前重点：先核对禁用标签对应的文件是否已经迁走或失效'
      }
      return '当前重点：先区分“标签用于聚合”还是“文件分组用于归档”，再决定去标签页还是分组页处理'
    },
    fileTagGuideCards() {
      return [
        {
          step: '第一步',
          title: '先分清标签和分组不是一回事',
          desc: '分组更像目录结构，标签更像跨目录聚合；如果你只是想整理存放位置，通常应该先去文件分组。'
        },
        {
          step: '第二步',
          title: '再确认要改标签，还是改文件关联',
          desc: '标签名称、描述、禁用状态属于标签本身；解除文件则会直接影响某批素材是否还能被这个标签聚合出来。'
        },
        {
          step: '第三步',
          title: '改完去文件库抽查真实素材',
          desc: '文件标签页只看到关系，最终还是要回文件库抽看图片、附件和分组，确认前台用到的素材没有断链。'
        }
      ]
    }
  },
  data() {
    return {
      name: '文件标签',
      height: 680,
      loading: false,
      idkey: 'tag_id',
      exps: [{ exp: 'like', name: '包含' }],
      query: {
        page: 1,
        limit: getPageLimit(),
        search_field: 'tag_name',
        search_exp: 'like',
        date_field: 'create_time',
        is_disable: undefined
      },
      data: [],
      count: 0,
      dialog: false,
      dialogTitle: '',
      model: {
        tag_id: '',
        tag_name: '',
        tag_desc: '',
        remark: '',
        sort: 250
      },
      rules: {
        tag_name: [{ required: true, message: '请输入名称', trigger: 'blur' }]
      },
      selection: [],
      selectIds: '',
      selectTitle: '操作',
      selectDialog: false,
      selectType: '',
      is_disable: 0,
      filePk: 'file_id',
      fileName: '文件',
      fileDialog: false,
      fileDialogTitle: '',
      fileLoad: false,
      fileData: [],
      fileCount: 0,
      fileQuery: {
        page: 1,
        limit: getPageLimit(),
        search_field: 'file_name',
        search_exp: 'like',
        search_value: ''
      },
      fileSelection: [],
      fileSelectIds: '',
      fileSelectTitle: '操作',
      fileSelectDialog: false,
      fileSelectType: '',
      shortcuts: shortcuts(),
      runtimeEnvInfo: resolveAdminRuntimeEnv(),
      recentActionSummary: ''
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
          this.exps = res.data.exps
          this.recentActionSummary = `已刷新文件标签列表，共 ${res.data.count || 0} 条记录`
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
      this.recentActionSummary = '已打开文件标签新增弹窗'
      this.reset()
    },
    edit(row) {
      this.dialog = true
      this.dialogTitle = this.name + '修改：' + row[this.idkey]
      this.recentActionSummary = `已打开标签 ${row[this.idkey]} 的编辑弹窗`
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
                this.recentActionSummary = `已提交标签 ${this.model[this.idkey]} 的修改`
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
                this.recentActionSummary = '已提交新增文件标签'
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
      this.recentActionSummary = '已重置文件标签筛选条件'
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
      this.recentActionSummary = selection.length
        ? `已勾选 ${selection.length} 个文件标签待处理`
        : '已清空文件标签勾选'
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
        if (selectType === 'removef') {
          this.removef(this.selection)
        } else if (selectType === 'disable') {
          this.disable(this.selection, true)
        } else if (selectType === 'dele') {
          this.dele(this.selection, true)
        }
        this.selectDialog = false
      }
    },
    // 解除文件
    removef(row) {
      if (!row.length) {
        this.selectAlert()
      } else {
        this.loading = true
        fileRemove({
          tag_id: this.selectGetIds(row),
          file_ids: []
        })
          .then((res) => {
            this.list()
            this.recentActionSummary = `已批量解除 ${row.length} 个标签的文件关联`
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.loading = false
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
            this.recentActionSummary = select
              ? `已批量更新 ${row.length} 个标签的禁用状态`
              : `已更新标签 ${this.selectGetIds(row).join(',')} 的禁用状态`
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
            this.recentActionSummary = `已删除 ${row.length} 个文件标签`
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.loading = false
          })
      }
    },
    // 标签文件显示
    fileShow(row) {
      this.fileDialog = true
      this.fileDialogTitle = this.name + '文件：' + row.tag_name
      this.fileQuery.tag_id = row.tag_id
      this.fileQuery.search_value = ''
      this.recentActionSummary = `已打开标签 ${row.tag_name} 的文件关联列表`
      this.file()
    },
    // 标签文件列表
    file() {
      this.fileLoad = true
      file(this.fileQuery)
        .then((res) => {
          this.fileData = res.data.list
          this.fileCount = res.data.count
          this.recentActionSummary = `已刷新标签文件列表，共 ${res.data.count || 0} 条关联`
          this.fileLoad = false
        })
        .catch(() => {
          this.fileLoad = false
        })
    },
    // 标签文件排序
    fileSort(sort) {
      this.fileQuery.sort_field = sort.prop
      this.fileQuery.sort_value = ''
      if (sort.order === 'ascending') {
        this.fileQuery.sort_value = 'asc'
        this.file()
      }
      if (sort.order === 'descending') {
        this.fileQuery.sort_value = 'desc'
        this.file()
      }
    },
    // 标签文件操作
    fileSelect(selection) {
      this.fileSelection = selection
      this.fileSelectIds = this.fileSelectGetIds(selection).toString()
      this.recentActionSummary = selection.length
        ? `已勾选 ${selection.length} 条标签文件关联待解除`
        : '已清空标签文件关联勾选'
    },
    fileSelectGetIds(selection) {
      return arrayColumn(selection, this.filePk)
    },
    fileSelectAlert() {
      ElMessageBox.alert('请选择需要操作的' + this.fileName, '提示', {
        type: 'warning',
        callback: () => {}
      })
    },
    fileSelectOpen(selectType, selectRow = '') {
      if (selectRow) {
        this.$refs['fileRef'].clearSelection()
        this.$refs['fileRef'].toggleRowSelection(selectRow)
      }
      if (!this.fileSelection.length) {
        this.fileSelectAlert()
      } else {
        this.fileSelectTitle = '操作'
        if (selectType === 'fileRemove') {
          this.fileSelectTitle = '解除标签'
        }
        this.fileSelectDialog = true
        this.fileSelectType = selectType
      }
    },
    fileSelectCancel() {
      this.fileSelectDialog = false
    },
    fileSelectSubmit() {
      if (!this.fileSelection.length) {
        this.fileSelectAlert()
      } else {
        const selectType = this.fileSelectType
        if (selectType === 'fileRemove') {
          this.fileRemove(this.fileSelection)
        }
        this.fileSelectDialog = false
      }
    },
    // 标签文件解除
    fileRemove(row) {
      if (!row.length) {
        this.fileSelectAlert()
      } else {
        this.fileLoad = true
        fileRemove({
          tag_id: this.fileQuery.tag_id,
          file_ids: this.fileSelectGetIds(row)
        })
          .then((res) => {
            this.file()
            this.recentActionSummary = `已解除 ${row.length} 条标签文件关联`
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.fileLoad = false
          })
      }
    },
    goToPage(path) {
      this.$router.push({
        path,
        query: {
          from: 'file-tag'
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
  margin-top: 16px;
  padding: 14px 16px;
  border-radius: 12px;
  border: 1px solid #e8edf5;
  background: #fbfcfe;
}

.followup-panel__main {
  flex: 1;
  min-width: 0;
}

.followup-panel__title {
  font-size: 14px;
  font-weight: 700;
  color: #1f2329;
}

.followup-panel__desc {
  margin-top: 6px;
  color: #5f6b7a;
  line-height: 1.7;
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
  background: #f5f7fb;
  color: #4a5670;
  font-size: 12px;
}

.followup-panel__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  justify-content: flex-end;
}

.page-header-simple {
  margin-bottom: 16px;
}

.page-header-simple__main {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.page-header-simple__title {
  font-size: 22px;
  font-weight: 700;
  color: #1f2329;
}

.page-header-simple__desc {
  margin-top: 8px;
  color: #5f6b7a;
  line-height: 1.6;
  font-size: 14px;
}

.summary-bar {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 12px;
}

.summary-bar__item,
.active-filter-strip__item {
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  padding: 0 10px;
  border-radius: 999px;
  background: #f5f7fb;
  color: #4a5670;
  font-size: 12px;
}

.active-filter-strip {
  margin-top: 14px;
  border-radius: 10px;
  padding: 10px 12px;
  background: #f7f9fc;
  border: 1px solid #e8edf5;
}

.active-filter-strip--muted {
  background: #fbfcfe;
}

.active-filter-strip__label,
.active-filter-strip__text {
  margin-right: 10px;
  font-size: 12px;
}

.active-filter-strip__label {
  font-weight: 700;
  color: #315efb;
}

.active-filter-strip__text {
  color: #4a5670;
  font-size: 13px;
}

.tag-plain-guide {
  margin-bottom: 16px;
  padding: 16px;
  border: 1px solid #dbe7f5;
  border-radius: 16px;
  background: linear-gradient(135deg, #f7fbff 0%, #ffffff 100%);
}

.tag-plain-guide__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.tag-plain-guide__title {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
}

.tag-plain-guide__desc {
  margin-top: 6px;
  font-size: 12px;
  color: #64748b;
  line-height: 1.7;
}

.tag-plain-guide__badge {
  min-width: 220px;
  padding: 10px 12px;
  border-radius: 12px;
  background: rgba(37, 99, 235, 0.08);
  color: #1d4ed8;
  font-size: 12px;
  font-weight: 700;
  line-height: 1.6;
}

.tag-plain-guide__grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-top: 14px;
}

.tag-plain-guide-card {
  padding: 14px 16px;
  border: 1px solid #e7edf5;
  border-radius: 14px;
  background: #fff;
}

.tag-plain-guide-card__step {
  display: inline-flex;
  align-items: center;
  min-height: 26px;
  padding: 0 10px;
  border-radius: 999px;
  background: #eff6ff;
  color: #1d4ed8;
  font-size: 12px;
  font-weight: 700;
}

.tag-plain-guide-card__title {
  margin-top: 10px;
  font-size: 14px;
  font-weight: 700;
  color: #0f172a;
}

.tag-plain-guide-card__desc {
  margin-top: 8px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

@media (max-width: 900px) {
  .followup-panel,
  .page-header-simple__main,
  .tag-plain-guide__header {
    flex-direction: column;
  }

  .tag-plain-guide__badge {
    min-width: 0;
  }

  .tag-plain-guide__grid {
    grid-template-columns: 1fr;
  }
}
</style>
