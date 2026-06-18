<template>
  <div class="app-container">
    <el-card class="app-head">
      <div class="section-title-row">
        <div>
          <div class="section-title-row__title">标签管理</div>
          <div class="section-title-row__desc">
            统一处理内容标签筛选、内容承接、状态控制和批量运营维护。
          </div>
        </div>
        <div class="section-title-row__meta">{{ runtimeEnvInfo.label }}</div>
      </div>
      <div class="tag-plain-guide">
        <div class="tag-plain-guide__header">
          <div>
            <div class="tag-plain-guide__title">内容标签页第一次进来，先看这个页面管什么</div>
            <div class="tag-plain-guide__desc">
              标签更偏专题、推荐位和内容聚合，不是内容正文本身；先判断你是在改内容归类，还是在改前台聚合入口。
            </div>
          </div>
          <div class="tag-plain-guide__badge">{{ contentTagGuideFocusLabel }}</div>
        </div>
        <div class="tag-plain-guide__grid">
          <div
            v-for="item in contentTagGuideCards"
            :key="item.title"
            class="tag-plain-guide-card"
          >
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
                  <el-option value="tag_unique" label="标识" />
                  <el-option value="tag_name" label="名称" />
                  <el-option value="title" label="标题" />
                  <el-option value="keywords" label="关键词" />
                  <el-option value="description" label="描述" />
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
      <div class="tag-summary-bar">
        <div class="tag-summary-bar__chips">
          <span class="summary-chip summary-chip--primary">总数 {{ count }}</span>
          <span class="summary-chip">已选 {{ selection.length }} 项</span>
          <span class="summary-chip">筛选 {{ currentSearchLabel }}</span>
          <span class="summary-chip">状态 {{ statusFilterLabel }}</span>
          <span class="summary-chip">当前操作 {{ tagActionSummary }}</span>
          <span class="summary-chip">数据模式 {{ runtimeEnvInfo.dataMode }}</span>
          <span v-for="item in activeFilterTags" :key="item" class="summary-chip">{{ item }}</span>
          <span v-if="!activeFilterTags.length" class="summary-chip">默认条件：全部标签</span>
          <span v-if="recentActionSummary" class="summary-chip summary-chip--muted">{{
            recentActionSummary
          }}</span>
        </div>
        <div class="tag-summary-bar__hint" :class="summaryBadgeClass">
          <span class="tag-summary-bar__hint-title">{{ summaryBadgeText }}</span>
          <span class="tag-summary-bar__hint-text">{{ summaryHint }}</span>
        </div>
      </div>
      <div class="followup-panel">
        <div class="followup-panel__main">
          <div class="followup-panel__title">看完标签后继续去哪</div>
          <div class="followup-panel__desc">{{ followupHint }}</div>
          <div class="followup-panel__tags">
            <span v-for="item in followupTags" :key="item">{{ item }}</span>
          </div>
          <div class="followup-panel__risk">{{ followupRiskText }}</div>
        </div>
        <div class="followup-panel__actions">
          <el-button type="primary" @click="goToPage('/content/content')">去内容管理</el-button>
          <el-button @click="goToPage('/content/category')">去内容分类</el-button>
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
        <el-form-item v-if="selectType === 'removec'">
          <span style="">确定要解除选中的{{ name }}的内容吗？</span>
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
        <el-button @click="selectCancel">取消</el-button>
        <el-button type="primary" @click="selectSubmit">提交</el-button>
      </template>
    </el-dialog>
    <el-card class="app-main">
      <div class="section-title-row section-title-row--content">
        <div>
          <div class="section-title-row__title">标签列表</div>
          <div class="section-title-row__desc">
            支持标签内容承接、禁用切换、详情编辑和批量处理。
          </div>
        </div>
        <div class="section-title-row__meta">已选 {{ selection.length }} 项</div>
      </div>
      <div class="operation_btn mb5">
        <!-- 操作 -->
        <div>
          <el-button type="primary" @click="add()">添加</el-button>
          <el-button title="删除" @click="selectOpen('dele')">删除</el-button>
          <el-button title="是否禁用" @click="selectOpen('disable')">禁用</el-button>
          <el-button title="解除内容" @click="selectOpen('removec')">解除内容</el-button>
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
        <el-table-column prop="tag_unique" label="标识" min-width="80" show-overflow-tooltip />
        <el-table-column prop="image_url" label="图片" min-width="62">
          <template #default="scope">
            <FileImage :file-url="scope.row.image_url" lazy />
          </template>
        </el-table-column>
        <el-table-column prop="tag_name" label="名称" min-width="130" show-overflow-tooltip />
        <el-table-column prop="is_disable" label="禁用" min-width="85" sortable="custom">
          <template #default="scope">
            <el-switch
              :model-value="scope.row.is_disable"
              :active-value="1"
              :inactive-value="0"
              @change="handleDisableSwitch(scope.row, $event)"
            />
          </template>
        </el-table-column>
        <el-table-column prop="sort" label="排序" min-width="85" sortable="custom" />
        <el-table-column prop="create_time" label="添加时间" width="165" sortable="custom" />
        <el-table-column prop="update_time" label="修改时间" width="165" sortable="custom" />
        <el-table-column label="操作" width="130">
          <template #default="scope">
            <el-link type="primary" class="mr-1" :underline="false" @click="contentShow(scope.row)">
              内容
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
    >
      <el-scrollbar native :max-height="height - 30">
        <el-form ref="ref" :rules="rules" :model="model" label-width="100px">
          <el-form-item label="标识" prop="tag_unique">
            <el-input v-model="model.tag_unique" placeholder="请输入标识（唯一）" clearable />
          </el-form-item>
          <el-form-item label="名称" prop="tag_name">
            <el-input v-model="model.tag_name" placeholder="请输入名称" clearable />
          </el-form-item>
          <el-form-item label="图片" prop="image_id">
            <FileImage v-model="model.image_id" :file-url="model.image_url" :height="100" upload />
          </el-form-item>
          <el-form-item label="标题" prop="title">
            <el-input v-model="model.title" placeholder="title" clearable />
          </el-form-item>
          <el-form-item label="关键词" prop="keywords">
            <el-input v-model="model.keywords" placeholder="keywords" clearable />
          </el-form-item>
          <el-form-item label="描述" prop="description">
            <el-input
              v-model="model.description"
              type="textarea"
              autosize
              placeholder="description"
            />
          </el-form-item>
          <el-form-item label="备注" prop="remark">
            <el-input v-model="model.remark" placeholder="remark" clearable />
          </el-form-item>
          <el-form-item label="排序" prop="sort">
            <el-input v-model="model.sort" type="number" />
          </el-form-item>
          <el-form-item label="图片列表" prop="images">
            <FileUploads
              v-model="model.images"
              upload-btn="上传图片"
              file-type="image"
              file-tip="图片文件"
            />
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
    <!-- 标签内容 -->
    <el-dialog
      v-model="contentDialog"
      :title="contentDialogTitle"
      :close-on-click-modal="false"
      :close-on-press-escape="false"
      top="5vh"
      width="70%"
    >
      <!-- 标签内容操作 -->
      <el-row>
        <el-col>
          <el-button type="primary" title="解除" @click="contentSelectOpen('contentRemove')">
            解除
          </el-button>
          <el-input
            v-model="contentQuery.search_value"
            class="ya-search-value"
            placeholder="名称"
            clearable
          />
          <el-button type="primary" @click="content()">查询</el-button>
        </el-col>
      </el-row>
      <!-- 标签内容列表 -->
      <el-table
        ref="contentRef"
        v-loading="contentLoad"
        :data="contentData"
        :height="height - 50"
        @sort-change="contentSort"
        @selection-change="contentSelect"
      >
        <el-table-column type="selection" width="42" title="全选/反选" />
        <el-table-column :prop="contentPk" label="内容ID" min-width="100" sortable="custom" />
        <el-table-column prop="image_url" label="图片" min-width="62">
          <template #default="scope">
            <FileImage :file-url="scope.row.image_url" lazy />
          </template>
        </el-table-column>
        <el-table-column prop="name" label="名称" min-width="230" show-overflow-tooltip />
        <el-table-column prop="unique" label="标识" min-width="80" show-overflow-tooltip />
        <el-table-column prop="category_names" label="分类" min-width="120" show-overflow-tooltip />
        <el-table-column prop="tag_names" label="标签" min-width="120" show-overflow-tooltip />
        <el-table-column prop="is_top" label="置顶" min-width="85" sortable="custom">
          <template #default="scope">
            <el-switch v-model="scope.row.is_top" :active-value="1" :inactive-value="0" disabled />
          </template>
        </el-table-column>
        <el-table-column prop="is_hot" label="热门" min-width="85" sortable="custom">
          <template #default="scope">
            <el-switch v-model="scope.row.is_hot" :active-value="1" :inactive-value="0" disabled />
          </template>
        </el-table-column>
        <el-table-column prop="is_rec" label="推荐" min-width="85" sortable="custom">
          <template #default="scope">
            <el-switch v-model="scope.row.is_rec" :active-value="1" :inactive-value="0" disabled />
          </template>
        </el-table-column>
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
        <el-table-column label="操作" min-width="70">
          <template #default="scope">
            <el-link
              type="primary"
              :underline="false"
              @click="contentSelectOpen('contentRemove', scope.row)"
            >
              解除
            </el-link>
          </template>
        </el-table-column>
      </el-table>
      <pagination
        v-show="contentCount > 0"
        v-model:total="contentCount"
        v-model:page="contentQuery.page"
        v-model:limit="contentQuery.limit"
        @pagination="content"
      />
    </el-dialog>
    <el-dialog
      v-model="contentSelectDialog"
      :title="contentSelectTitle"
      :close-on-click-modal="false"
      :close-on-press-escape="false"
      top="20vh"
    >
      <el-form ref="contentSelectRef" label-width="120px">
        <el-form-item v-if="contentSelectType === 'contentRemove'" label="标签ID">
          <span>{{ contentQuery[idkey] }}</span>
        </el-form-item>
        <el-form-item :label="contentName + 'ID'">
          <el-input v-model="contentSelectIds" type="textarea" autosize disabled />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="contentSelectCancel">取消</el-button>
        <el-button type="primary" @click="contentSelectSubmit">提交</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script>
import screenHeight from '@/utils/screen-height'
import Pagination from '@/components/Pagination/index.vue'
import { arrayColumn } from '@/utils/index'
import { getPageLimit } from '@/utils/settings'
import { list, info, add, edit, dele, disable, content, contentRemove } from '@/api/content/tag'
import { getAdminRuntimeHint, resolveAdminRuntimeEnv } from '@/utils/runtime-env'

export default {
  name: 'ContentTag',
  components: { Pagination },
  computed: {
    currentSearchLabel() {
      const fieldMap = {
        tag_id: 'ID',
        tag_unique: '标识',
        tag_name: '名称',
        title: '标题',
        keywords: '关键词',
        description: '描述',
        remark: '备注'
      }
      return fieldMap[this.query.search_field] || '综合筛选'
    },
    runtimeHint() {
      return getAdminRuntimeHint(this.runtimeEnvInfo)
    },
    statusFilterLabel() {
      return this.query.is_disable === 1
        ? '仅看禁用'
        : this.query.is_disable === 0
          ? '仅看启用'
          : '全部状态'
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
    tagActionSummary() {
      if (this.selection.length) {
        return `已勾选 ${this.selection.length} 项`
      }
      return this.recentActionSummary || '列表巡检中'
    },
    tagSubmitRisk() {
      if (this.runtimeEnvInfo.isProd) {
        return '正式环境下的标签禁用、删除和内容解绑会直接影响推荐位、专题页和检索聚合结果，提交前请先复核筛选范围。'
      }
      return '当前环境适合验证标签新增、禁用和内容解绑链路，确认无误后再切正式环境操作。'
    },
    followupHint() {
      if (this.selection.length) {
        return '当前已选择标签，可继续做批量禁用、删除或解除内容，建议优先确认标签是否仍被专题页或内容位引用。'
      }
      if (this.query.is_disable === 1) {
        return '当前正在查看禁用标签，适合核对历史标签是否仍需恢复或彻底清理。'
      }
      return '标签页更适合按专题、推荐位和内容属性维护，先筛选再批量处理会更稳。'
    },
    followupTags() {
      return [
        `环境：${this.runtimeEnvInfo.label}`,
        `数据模式：${this.runtimeEnvInfo.dataMode}`,
        `已选：${this.selection.length} 项`,
        `总量：${this.count || 0} 条`
      ]
    },
    followupBadgeText() {
      if (this.selection.length) {
        return '待复核'
      }
      return this.runtimeEnvInfo.isProd ? '正式运营' : '联调验收'
    },
    followupBadgeClass() {
      if (this.selection.length) {
        return 'is-warning'
      }
      return this.runtimeEnvInfo.isProd ? 'is-danger' : 'is-safe'
    },
    followupRiskText() {
      return this.selection.length
        ? '若批量解除内容，内容详情页和标签聚合页可能同步失去关联展示，建议先确认本次选择的标签是否仍在前台使用。'
        : '建议优先清理重复、过期或失效标签，再做禁用和删除，避免误伤仍在展示中的内容入口。'
    },
    summaryBadgeClass() {
      if (this.selection.length) {
        return 'is-warning'
      }
      return this.runtimeEnvInfo.isProd ? 'is-danger' : 'is-safe'
    },
    summaryBadgeText() {
      if (this.selection.length) {
        return '待复核'
      }
      return this.runtimeEnvInfo.isProd ? '正式运营' : '联调验收'
    },
    summaryHint() {
      if (this.selection.length) {
        return this.tagSubmitRisk
      }
      if (this.query.is_disable === 1) {
        return '当前正在查看禁用标签，适合核对历史标签是否仍需恢复或彻底清理。'
      }
      return `${this.runtimeHint} 当前共 ${this.count || 0} 条标签，可继续从下方直接维护。`
    },
    contentTagGuideFocusLabel() {
      if (this.contentDialog) {
        return '当前重点：正在看标签下挂的真实内容，先确认是标签关系问题还是内容本身状态问题'
      }
      if (this.selection.length > 0) {
        return `当前重点：已圈定 ${this.selection.length} 个标签，先确认会不会影响专题页或推荐位聚合`
      }
      if (this.query.is_disable === 1) {
        return '当前重点：先核对禁用标签是否已经退出前台展示，再决定恢复还是清理'
      }
      return '当前重点：先区分“分类决定归属”与“标签决定聚合”，再决定去分类页还是标签页处理'
    },
    contentTagGuideCards() {
      return [
        {
          step: '第一步',
          title: '先判断是改归属，还是改聚合入口',
          desc: '内容分类更像主归属，标签更像专题和推荐位的聚合条件；如果只是归档内容，优先去分类页。'
        },
        {
          step: '第二步',
          title: '再确认是改标签本身，还是改标签下的内容',
          desc: '标签名称、标识、图片和描述属于标签本身；解除内容会直接影响前台专题、推荐位和检索聚合结果。'
        },
        {
          step: '第三步',
          title: '改完回内容管理和分类页复核',
          desc: '标签页只负责聚合关系，最终还是要回内容管理看真实内容，再去分类页确认主归属是否一致。'
        }
      ]
    }
  },
  data() {
    return {
      name: '内容标签',
      height: 680,
      loading: false,
      idkey: 'tag_id',
      exps: [{ exp: 'like', name: '包含' }],
      query: {
        page: 1,
        limit: getPageLimit(),
        search_field: 'tag_name',
        search_exp: 'like',
        date_field: 'create_time'
      },
      data: [],
      count: 0,
      dialog: false,
      dialogTitle: '',
      model: {
        tag_id: '',
        tag_name: '',
        tag_unique: '',
        image_id: 0,
        image_url: '',
        title: '',
        keywords: '',
        description: '',
        remark: '',
        sort: 250,
        images: []
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
      contentPk: 'content_id',
      contentName: '内容',
      contentDialog: false,
      contentDialogTitle: '',
      contentLoad: false,
      contentData: [],
      contentCount: 0,
      contentQuery: {
        page: 1,
        limit: getPageLimit(),
        search_field: 'name',
        search_exp: 'like',
        search_value: ''
      },
      contentSelection: [],
      contentSelectIds: '',
      contentSelectTitle: '操作',
      contentSelectDialog: false,
      contentSelectType: '',
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
          this.recentActionSummary = `已刷新标签列表，共 ${res.data.count || 0} 条记录`
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
      this.recentActionSummary = '已打开内容标签新增弹窗'
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
                this.recentActionSummary = '已提交新增内容标签'
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
      this.recentActionSummary = '已重置标签筛选条件'
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
        ? `已勾选 ${selection.length} 个标签待处理`
        : '已清空标签勾选'
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
        if (selectType === 'removec') {
          this.selectTitle = this.name + '解除内容'
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
        if (selectType === 'removec') {
          this.removec(this.selection)
        } else if (selectType === 'disable') {
          this.disable(this.selection, true)
        } else if (selectType === 'dele') {
          this.dele(this.selection, true)
        }
        this.selectDialog = false
      }
    },
    // 解除内容
    removec(row) {
      if (!row.length) {
        this.selectAlert()
      } else {
        this.loading = true
        contentRemove({
          tag_id: this.selectGetIds(row),
          content_ids: []
        })
          .then((res) => {
            this.list()
            this.recentActionSummary = `已批量解除 ${row.length} 个标签的内容关联`
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
    handleDisableSwitch(row, value) {
      ElMessageBox.confirm(
        `确认要${value === 1 ? '禁用' : '启用'}标签「${row.tag_name || row.tag_unique || row[this.idkey]}」吗？`,
        '操作确认',
        {
          type: 'warning',
          confirmButtonText: '继续',
          cancelButtonText: '取消'
        }
      )
        .then(() => {
          this.disable([{ ...row, is_disable: value }])
        })
        .catch(() => {})
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
            this.recentActionSummary = `已删除 ${row.length} 个标签`
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.loading = false
          })
      }
    },
    // 标签内容显示
    contentShow(row) {
      this.contentDialog = true
      this.contentDialogTitle = this.name + '内容：' + row.tag_name
      this.contentQuery.tag_id = row.tag_id
      this.contentQuery.search_value = ''
      this.recentActionSummary = `已打开标签 ${row.tag_name} 的内容关联列表`
      this.content()
    },
    // 标签内容列表
    content() {
      this.contentLoad = true
      content(this.contentQuery)
        .then((res) => {
          this.contentData = res.data.list
          this.contentCount = res.data.count
          this.recentActionSummary = `已刷新标签内容列表，共 ${res.data.count || 0} 条关联`
          this.contentLoad = false
        })
        .catch(() => {
          this.contentLoad = false
        })
    },
    // 标签内容排序
    contentSort(sort) {
      this.contentQuery.sort_field = sort.prop
      this.contentQuery.sort_value = ''
      if (sort.order === 'ascending') {
        this.contentQuery.sort_value = 'asc'
        this.content()
      }
      if (sort.order === 'descending') {
        this.contentQuery.sort_value = 'desc'
        this.content()
      }
    },
    // 标签内容操作
    contentSelect(selection) {
      this.contentSelection = selection
      this.contentSelectIds = this.contentSelectGetIds(selection).toString()
      this.recentActionSummary = selection.length
        ? `已勾选 ${selection.length} 条内容关联待解除`
        : '已清空内容关联勾选'
    },
    contentSelectGetIds(selection) {
      return arrayColumn(selection, this.contentPk)
    },
    contentSelectAlert() {
      ElMessageBox.alert('请选择需要操作的' + this.contentName, '提示', {
        type: 'warning',
        callback: () => {}
      })
    },
    contentSelectOpen(selectType, selectRow = '') {
      if (selectRow) {
        this.$refs['contentRef'].clearSelection()
        this.$refs['contentRef'].toggleRowSelection(selectRow)
      }
      if (!this.contentSelection.length) {
        this.contentSelectAlert()
      } else {
        this.contentSelectTitle = '操作'
        if (selectType === 'contentRemove') {
          this.contentSelectTitle = '解除标签'
        }
        this.contentSelectDialog = true
        this.contentSelectType = selectType
      }
    },
    contentSelectCancel() {
      this.contentSelectDialog = false
    },
    contentSelectSubmit() {
      if (!this.contentSelection.length) {
        this.contentSelectAlert()
      } else {
        const selectType = this.contentSelectType
        if (selectType === 'contentRemove') {
          this.contentRemove(this.contentSelection)
        }
        this.contentSelectDialog = false
      }
    },
    // 标签内容解除
    contentRemove(row) {
      if (!row.length) {
        this.contentSelectAlert()
      } else {
        this.contentLoad = true
        contentRemove({
          tag_id: this.contentQuery.tag_id,
          content_ids: this.contentSelectGetIds(row)
        })
          .then((res) => {
            this.content()
            this.recentActionSummary = `已解除 ${row.length} 条标签内容关联`
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.contentLoad = false
          })
      }
    },
    goToPage(path) {
      this.$router.push({
        path,
        query: {
          from: 'content-tag'
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
  border: 1px solid #dbe7f5;
  border-radius: 14px;
  background: #fbfdff;
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

.followup-panel__desc,
.followup-panel__risk {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.followup-panel__risk {
  padding: 10px 12px;
  border-radius: 12px;
  background: #fff7ed;
  border: 1px solid #fed7aa;
  color: #9a3412;
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
  background: #fff;
  border: 1px solid #e6ecf5;
  color: #334155;
  font-size: 12px;
}

.followup-panel__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  justify-content: flex-end;
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

.tag-summary-bar {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  margin-top: 16px;
  padding: 14px 16px;
  border: 1px solid #dbe7f5;
  border-radius: 14px;
  background: #f8fbff;
}

.tag-summary-bar__chips {
  display: flex;
  flex: 1;
  flex-wrap: wrap;
  gap: 10px;
}

.summary-chip {
  display: inline-flex;
  align-items: center;
  min-height: 30px;
  padding: 0 10px;
  border: 1px solid #e6ecf5;
  border-radius: 999px;
  background: #fff;
  color: #334155;
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

.tag-summary-bar__hint {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 240px;
  padding: 12px 14px;
  border-radius: 12px;
  background: #eef3ff;
  color: #315efb;
}

.tag-summary-bar__hint.is-safe {
  background: #eaf8ef;
  color: #15803d;
}

.tag-summary-bar__hint.is-warning {
  background: #fff5e8;
  color: #b45309;
}

.tag-summary-bar__hint.is-danger {
  background: #ffeceb;
  color: #c2410c;
}

.tag-summary-bar__hint-title {
  font-size: 12px;
  font-weight: 700;
}

.tag-summary-bar__hint-text {
  margin-top: 0;
  color: inherit;
  line-height: 1.7;
  font-size: 12px;
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

.action-cluster {
  display: inline-flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
  margin-right: 10px;
}

@media (max-width: 900px) {
  .section-title-row,
  .tag-plain-guide__header,
  .followup-panel,
  .tag-summary-bar {
    flex-direction: column;
  }

  .section-title-row__meta {
    white-space: normal;
  }

  .tag-plain-guide__badge {
    min-width: 0;
  }

  .tag-plain-guide__grid {
    grid-template-columns: 1fr;
  }
}
</style>
