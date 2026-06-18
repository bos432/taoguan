<template>
  <div class="app-container">
    <div class="page-header">
      <div class="page-header__main">
        <div class="page-header__title">轮播资源</div>
        <div class="page-header__desc">
          集中查看轮播素材、投放位置和启用状态，便于按当前范围直接处理。
        </div>
      </div>
      <div class="page-header__meta">
        <span class="summary-chip summary-chip--env">{{ runtimeEnvInfo.label }}</span>
        <span class="summary-chip">{{ runtimeEnvInfo.dataMode }}</span>
      </div>
    </div>
    <el-card class="app-head">
      <div class="filter-head">
        <div class="filter-head__title">资源检索</div>
        <div class="filter-head__meta">按时间、状态和关键词快速定位轮播资源</div>
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
                    <el-option value="unique" label="标识" />
                    <el-option value="title" label="标题" />
                    <el-option value="position" label="位置" />
                    <el-option value="desc" label="描述" />
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
          <span class="summary-chip"
            >状态
            {{
              query.is_disable === undefined ? '全部' : query.is_disable === 1 ? '禁用' : '启用'
            }}</span
          >
          <span class="summary-chip">已启用 {{ enabledCount }} 项</span>
          <span class="summary-chip">位置 {{ uniquePositionCount }} 处</span>
          <span v-for="item in activeFilterTags" :key="item" class="summary-chip">{{ item }}</span>
        </div>
        <div class="summary-bar__side">
          <span class="summary-chip summary-chip--risk">{{ carouselSubmitRisk }}</span>
          <span v-if="recentActionSummary" class="summary-chip">{{ recentActionSummary }}</span>
        </div>
      </div>
      <div class="plain-guide">
        <div class="plain-guide__header">
          <div>
            <div class="plain-guide__title">这页建议先这样看</div>
            <div class="plain-guide__desc">
              轮播页主要在管首页和活动页的曝光位。先判断素材现在还要不要投，再确认它放在哪个位置、要不要下线，最后再去核对公告和友链的跳转口径。
            </div>
          </div>
          <span class="plain-guide__badge">{{ carouselFocusLabel }}</span>
        </div>
        <div class="plain-guide__grid">
          <div v-for="item in carouselGuideCards" :key="item.title" class="plain-guide-card">
            <div class="plain-guide-card__title">{{ item.title }}</div>
            <div class="plain-guide-card__desc">{{ item.desc }}</div>
            <div class="plain-guide-card__action">{{ item.action }}</div>
          </div>
        </div>
      </div>
      <div class="followup-panel">
        <div class="followup-panel__main">
          <div class="followup-panel__title">看完轮播后继续去哪</div>
          <div class="followup-panel__desc">{{ followupHint }}</div>
          <div class="followup-panel__tags">
            <span v-for="item in followupTags" :key="item">{{ item }}</span>
          </div>
        </div>
        <div class="followup-panel__actions">
          <el-button type="primary" @click="openH5Home">去 H5 首页看轮播</el-button>
          <el-button @click="goToPage('/setting/notice')">去公告管理</el-button>
          <el-button @click="goToPage('/setting/hall')">回内容大厅</el-button>
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
        <el-form-item v-if="selectType === 'editpos'" label="位置">
          <el-input v-model="positions" placeholder="请输入位置" clearable />
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
        <el-form-item label="批量复核">
          <div class="dialog-review-note">
            <div class="dialog-review-note__tags">
              <span class="dialog-review-note__tag">{{ carouselActionSummary }}</span>
              <span class="dialog-review-note__tag">影响资源：{{ selection.length || 0 }} 项</span>
              <span class="dialog-review-note__tag">运行环境：{{ runtimeEnvInfo.label }}</span>
            </div>
            <div class="dialog-review-note__risk">{{ carouselSubmitRisk }}</div>
          </div>
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
            <el-button type="primary" @click="add()">添加</el-button>
            <el-button title="修改位置" @click="selectOpen('editpos')">位置</el-button>
          </div>
          <div class="action-cluster">
            <el-button title="是否禁用" @click="selectOpen('disable')">禁用</el-button>
            <el-button title="删除" @click="selectOpen('dele')">删除</el-button>
          </div>
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
        class="compact-carousel-table"
        @sort-change="sort"
        @selection-change="select"
        @cell-dblclick="cellDbclick"
      >
        <el-table-column type="selection" width="42" title="全选/反选" />
        <el-table-column :prop="idkey" label="ID" width="80" sortable="custom" />
        <el-table-column
          prop="unique"
          label="标识"
          min-width="100"
          sortable="custom"
          show-overflow-tooltip
        />
        <el-table-column prop="file_url" label="文件" min-width="130">
          <template #default="scope">
            <FilePreview :file="scope.row" lazy />
          </template>
        </el-table-column>
        <el-table-column prop="file_type_name" label="类型" min-width="80" />
        <el-table-column
          prop="title"
          label="标题"
          min-width="150"
          sortable="custom"
          show-overflow-tooltip
        />
        <el-table-column
          prop="position"
          label="位置"
          min-width="120"
          sortable="custom"
          show-overflow-tooltip
        />
        <el-table-column prop="desc" label="描述" min-width="120" show-overflow-tooltip />
        <el-table-column
          prop="remark"
          label="备注"
          min-width="150"
          sortable="custom"
          show-overflow-tooltip
        />
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
      <el-scrollbar native :max-height="height - 30">
        <el-form ref="ref" :rules="rules" :model="model" label-width="100px">
          <div class="dialog-review-note dialog-review-note--form">
            <div class="dialog-review-note__tags">
              <span class="dialog-review-note__tag">{{
                model[idkey] ? '当前模式：编辑轮播' : '当前模式：新增轮播'
              }}</span>
              <span class="dialog-review-note__tag">运行环境：{{ runtimeEnvInfo.label }}</span>
              <span class="dialog-review-note__tag">标题：{{ model.title || '未填写' }}</span>
            </div>
            <div class="dialog-review-note__risk">{{ carouselFormRisk }}</div>
          </div>
          <el-form-item label="标识" prop="unique">
            <el-input v-model="model.unique" placeholder="请输入标识（唯一）" clearable>
              <template #append>
                <el-button title="复制" @click="copy(model.unique)">
                  <svg-icon icon-class="copy-document" />
                </el-button>
              </template>
            </el-input>
          </el-form-item>
          <el-form-item label="文件" prop="file_id">
            <FileUpload v-model="model.file_id" :preview="model" />
          </el-form-item>
          <el-form-item label="标题" prop="title">
            <el-input v-model="model.title" :model="model" placeholder="请输入标题" clearable>
              <template #append>
                <el-button title="复制" @click="copy(model.title)">
                  <svg-icon icon-class="copy-document" />
                </el-button>
              </template>
            </el-input>
          </el-form-item>
          <el-form-item label="描述" prop="desc">
            <el-input v-model="model.desc" type="textarea" autosize placeholder="请输入描述" />
          </el-form-item>
          <el-form-item label="链接" prop="url">
            <el-input v-model="model.url" placeholder="请输入链接" clearable>
              <template #append>
                <el-button title="复制" @click="copy(model.url)">
                  <svg-icon icon-class="copy-document" />
                </el-button>
              </template>
            </el-input>
          </el-form-item>
          <el-form-item label="备注" prop="remark">
            <el-input v-model="model.remark" placeholder="请输入备注" clearable />
          </el-form-item>
          <el-form-item label="排序" prop="sort">
            <el-input v-model="model.sort" type="number" />
          </el-form-item>
          <el-form-item label="文件列表" prop="file_list">
            <FileUploads
              v-model="model.file_list"
              upload-btn="上传文件"
              file-type="all"
              file-tip="文件列表"
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
  </div>
</template>

<script>
import screenHeight from '@/utils/screen-height'
import Pagination from '@/components/Pagination/index.vue'
import clip from '@/utils/clipboard'
import { arrayColumn } from '@/utils/index'
import { getPageLimit } from '@/utils/settings'
import { getAdminRuntimeHint, resolveAdminRuntimeEnv } from '@/utils/runtime-env'
import { list, info, add, edit, dele, position, disable } from '@/api/setting/carousel'

export default {
  name: 'SettingCarousel',
  components: { Pagination },
  data() {
    return {
      name: '轮播',
      height: 680,
      loading: false,
      idkey: 'carousel_id',
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
        carousel_id: '',
        unique: '',
        file_id: 0,
        file_url: '',
        file_type: '',
        file_name: '',
        file_ext: '',
        title: '',
        position: '',
        url: '',
        desc: '',
        remark: '',
        sort: 250,
        file_list: []
      },
      rules: {
        title: [{ required: true, message: '请输入标题', trigger: 'blur' }]
      },
      selection: [],
      selectIds: '',
      selectTitle: '操作',
      selectDialog: false,
      selectType: '',
      positions: '',
      is_disable: 0,
      recentActionSummary: ''
    }
  },
  computed: {
    runtimeEnvInfo() {
      return resolveAdminRuntimeEnv()
    },
    runtimeHint() {
      return getAdminRuntimeHint(this.runtimeEnvInfo)
    },
    carouselActionSummary() {
      const map = {
        editpos: '批量改位置',
        disable: '批量改禁用',
        dele: '批量删除'
      }
      return map[this.selectType] || '轮播维护'
    },
    carouselSubmitRisk() {
      if (this.selectType === 'dele') {
        return this.runtimeEnvInfo.isProd
          ? '正式环境下删除轮播会直接影响前台展示，请确认当前选中资源和位置。'
          : '当前环境适合验证轮播删除和列表回显，不要把测试删除结果当作正式运营结果。'
      }
      if (!this.selection.length) {
        return this.runtimeEnvInfo.isProd
          ? '正式环境下建议先明确选中资源，再执行位置或状态调整。'
          : '当前环境建议先选中样本资源，再验证批量位置和状态操作。'
      }
      return this.runtimeEnvInfo.isProd
        ? `正式环境下本次会直接影响 ${this.selection.length} 项轮播资源，请先复核位置、标题和启用状态。`
        : `当前环境可用于验证 ${this.selection.length} 项轮播资源的批量操作与结果回显。`
    },
    carouselFormRisk() {
      if (!this.model.title) {
        return '当前标题未填写，提交会被表单校验拦截。'
      }
      if (!this.model.file_id) {
        return '当前文件未上传，建议提交前补齐素材文件。'
      }
      return this.runtimeEnvInfo.isProd
        ? '正式环境下新增或修改会直接影响前台轮播展示，请确认素材、位置和链接后再提交。'
        : '当前环境适合验证轮播新增、编辑、文件预览和位置回显。'
    },
    enabledCount() {
      return this.data.filter((item) => Number(item.is_disable || 0) === 0).length
    },
    uniquePositionCount() {
      return new Set(this.data.map((item) => item.position).filter(Boolean)).size
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
    carouselFocusLabel() {
      if (this.selection.length) {
        return '先复核批量处理'
      }
      if (this.query.is_disable === 1) {
        return '先看下线素材'
      }
      if (!this.uniquePositionCount) {
        return '先补投放位置'
      }
      return '先看整体曝光位'
    },
    carouselGuideCards() {
      return [
        {
          title: '第一步先看素材还要不要继续投放',
          desc:
            this.query.is_disable === 1
              ? '当前筛出来的是已下线素材，先判断哪些只是临时停用，哪些已经可以彻底清理。'
              : '先确认当前素材是不是还要继续对外展示，避免把过期活动图继续挂在首页。 ',
          action: '先按活动是否仍有效分组，再决定保留、禁用还是删除。'
        },
        {
          title: '第二步再看它放在哪个位置',
          desc: this.uniquePositionCount
            ? `当前列表覆盖 ${this.uniquePositionCount} 个位置，位置越多越要先分位置处理，别一把梭批量改。`
            : '当前还没有清晰的位置分布，说明素材投放规则还没收口，容易导致首页展示混乱。',
          action: '优先确认首页主轮播、活动位、专题位这些关键曝光口是否匹配。'
        },
        {
          title: '第三步联动检查跳转和内容口径',
          desc: this.selection.length
            ? `当前已选 ${this.selection.length} 项素材，处理完后最好回公告和友链页复核对应入口。`
            : '轮播改完不代表首页就闭环了，通常还要继续确认公告标题、友链入口和落地页有没有一起同步。',
          action: '处理完成后继续去公告管理、友链管理和内容大厅做联动复核。'
        }
      ]
    },
    carouselFollowupHint() {
      if (!this.count) {
        return '当前列表暂无轮播资源，建议先补齐首页和活动页的基础素材。'
      }
      return this.runtimeEnvInfo.isProd
        ? '正式环境下建议先核对位置、启用状态和链接，再执行批量位置与状态调整。'
        : '当前环境适合先验证各位置素材回显，再检查批量改位置和禁用流程。'
    },
    carouselFollowupBadge() {
      return this.enabledCount >= Math.max(1, Math.ceil(this.data.length / 2)) ? '投放中' : '待排期'
    },
    carouselFollowupItems() {
      const topPositions = Array.from(
        new Set(this.data.map((item) => item.position).filter(Boolean))
      ).slice(0, 3)
      return [
        `已启用：${this.enabledCount} 项`,
        `已禁用：${this.data.length - this.enabledCount} 项`,
        `重点位置：${topPositions.join('、') || '未设置'}`,
        `当前选中：${this.selectionPreview}`
      ]
    },
    carouselFollowupRisk() {
      if (this.selection.length) {
        return `当前已选 ${this.selection.length} 项资源，提交前建议复核位置、链接和素材类型是否一致。`
      }
      if (!this.uniquePositionCount) {
        return '当前列表没有明确的位置分布，建议先补齐位置字段，避免素材投放混乱。'
      }
      return this.runtimeEnvInfo.isProd
        ? '正式环境下位置调整会直接影响首页和专题页展示顺序，建议按位置逐组处理。'
        : '测试环境建议优先验证首页主轮播、活动位和公告位的回显差异。'
    },
    selectionPreview() {
      if (!this.selection.length) {
        return '未选择'
      }
      const ids = this.selection
        .slice(0, 4)
        .map((item) => item[this.idkey])
        .join('、')
      return `${ids}${this.selection.length > 4 ? ' 等' : ''}`
    },
    followupHint() {
      if (this.selection.length) {
        return `当前已选 ${this.selection.length} 项轮播资源，处理完成后建议先去 H5 首页看首屏展示，再去公告页核对首页内容口径是否仍然一致。`
      }
      if (!this.uniquePositionCount) {
        return '当前轮播资源还没有形成清晰的位置分布，下一步建议先补齐位置，再去 H5 首页和公告页核对对应入口内容。'
      }
      return '轮播页主要解决素材、位置和投放顺序；通常处理完这里后，要先去 H5 首页看首屏轮播，再回公告页和内容大厅确认口径没有脱节。'
    },
    followupTags() {
      return [
        `环境：${this.runtimeEnvInfo.label}`,
        `位置：${this.uniquePositionCount} 处`,
        `已启用：${this.enabledCount} 项`,
        `已选：${this.selection.length} 项`
      ]
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
                this.recentActionSummary = `已更新轮播 ${this.model[this.idkey]}，位置：${
                  this.model.position || '未设置'
                }，标题：${this.model.title || '未填写'}。`
                ElMessage.success(res.msg)
                this.$nextTick(() => {
                  ElMessage.info('轮播资源已提交，请继续去 H5 首页、公告管理和内容大厅各核对一次。')
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
                this.recentActionSummary = `已新增轮播素材，位置：${
                  this.model.position || '未设置'
                }，标题：${this.model.title || '未填写'}。`
                ElMessage.success(res.msg)
                this.$nextTick(() => {
                  ElMessage.info('轮播资源已提交，请继续去 H5 首页、公告管理和内容大厅各核对一次。')
                })
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
        if (selectType === 'editpos') {
          this.selectTitle = this.name + '修改位置'
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
        if (selectType === 'editpos') {
          this.editpos(this.selection, true)
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
        `确定要${actionText}轮播“${row.title || row[this.idkey]}”吗？`,
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
    // 修改位置
    editpos(row, select = false) {
      if (!row.length) {
        this.selectAlert()
      } else {
        this.loading = true
        var positions = row[0].position
        if (select) {
          positions = this.positions
        }
        position({
          ids: this.selectGetIds(row),
          position: positions
        })
          .then((res) => {
            this.list()
            this.recentActionSummary = `已批量修改轮播位置，影响 ${row.length} 项资源，目标位置：${
              positions || '未设置'
            }。`
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
            this.recentActionSummary = `已批量调整轮播状态，影响 ${row.length} 项资源，目标状态：${
              is_disable === 1 ? '禁用' : '启用'
            }。`
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
            this.recentActionSummary = `已批量删除 ${row.length} 项轮播资源，请继续核对同位置剩余素材是否满足投放。`
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
          from: 'setting-carousel'
        }
      })
    },
    openH5Home() {
      window.open(`${window.location.origin}/app/`, '_blank')
    }
  }
}
</script>

<style scoped>
.dialog-review-note__tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 12px;
}

.dialog-review-note__tag {
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  padding: 0 10px;
  border-radius: 999px;
  background: #eef4ff;
  color: #375078;
  font-size: 12px;
}

.dialog-review-note__risk {
  margin-top: 12px;
  padding: 12px 14px;
  border-radius: 12px;
  background: #fff7ed;
  border: 1px solid #fed7aa;
  color: #9a3412;
  font-size: 13px;
  line-height: 1.6;
}

.dialog-review-note {
  width: 100%;
}

.dialog-review-note--form {
  margin-bottom: 14px;
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
  font-weight: 700;
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

.followup-panel {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-top: 10px;
  padding: 12px 14px;
  border: 1px solid #dbe7f5;
  border-radius: 14px;
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
  color: #64748b;
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
  background: #f3f4f6;
  color: #475569;
  font-size: 12px;
  border: 1px solid rgba(148, 163, 184, 0.16);
}

.followup-panel__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  justify-content: flex-end;
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

.compact-carousel-table :deep(.el-table__cell) {
  padding: 8px 0;
}

.compact-carousel-table :deep(.el-table__body .cell) {
  line-height: 1.4;
}

@media (max-width: 900px) {
  .page-header,
  .filter-head,
  .plain-guide__header,
  .followup-panel,
  .summary-bar,
  .operation_btn {
    flex-direction: column;
    align-items: stretch;
  }
}
</style>
