<template>
  <div class="app-container">
    <el-card class="app-head head-pb20">
      <div class="page-head">
        <div>
          <div class="page-head__title">反馈管理</div>
          <div class="page-head__desc">
            处理用户反馈、状态流转和回执追踪，默认按线上后台节奏直接进入列表处理。
          </div>
        </div>
        <div class="page-head__meta">
          <span class="page-head__tag">{{ runtimeEnvInfo.label }}</span>
          <span class="page-head__tag">{{ runtimeEnvInfo.dataMode }}</span>
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
              <el-select v-model="query.is_disable" @change="search()" clearable>
                <el-option :value="0" label="启用" />
                <el-option :value="1" label="禁用" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="类型：" prop="type">
              <el-select v-model="query.type" @change="search()" clearable>
                <el-option
                  v-for="(item, index) in types"
                  :key="index"
                  :label="item"
                  :value="index"
                />
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
                  <el-option value="title" label="标题" />
                  <el-option value="phone" label="手机" />
                  <el-option value="email" label="邮箱" />
                  <el-option value="receipt_no" label="回执" />
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
      <div class="section-title-row section-title-row--content">
        <div>
          <div class="section-title-row__title">反馈列表</div>
          <div class="section-title-row__desc">
            支持反馈录入、状态流转、禁用控制与问题回执跟踪。
          </div>
        </div>
        <div class="section-title-row__meta">已选 {{ selection.length }} 项</div>
      </div>
      <div class="summary-bar">
        <div class="summary-bar__metrics">
          <span class="summary-bar__metric">反馈总量：{{ count }}</span>
          <span class="summary-bar__metric">已选反馈：{{ selection.length }}</span>
          <span class="summary-bar__metric">当前类型：{{ currentTypeLabel }}</span>
          <span class="summary-bar__metric">当前状态：{{ currentDisableLabel }}</span>
        </div>
        <div class="summary-bar__filters">
          <el-tag v-for="item in summaryTags" :key="item" effect="plain">{{ item }}</el-tag>
        </div>
        <div class="summary-bar__hint">
          <span>{{ summaryHint }}</span>
          <span v-if="recentActionSummary">{{ recentActionSummary }}</span>
        </div>
      </div>
      <div class="plain-guide">
        <div class="plain-guide__header">
          <div>
            <div class="plain-guide__title">这页建议先这样看</div>
            <div class="plain-guide__desc">
              反馈页不是只看留言内容，而是把用户问题分流到对应链路。先判断是不是同类问题集中出现，再决定去会员页、会员日志，还是按当前类型继续批量处理。
            </div>
          </div>
          <span class="plain-guide__badge">{{ feedbackFocusLabel }}</span>
        </div>
        <div class="plain-guide__grid">
          <div v-for="item in feedbackGuideCards" :key="item.title" class="plain-guide-card">
            <div class="plain-guide-card__title">{{ item.title }}</div>
            <div class="plain-guide-card__desc">{{ item.desc }}</div>
            <div class="plain-guide-card__action">{{ item.action }}</div>
          </div>
        </div>
      </div>
      <div class="followup-strip">
        <button type="button" class="followup-card" @click="goToCurrentMember">
          <span class="followup-card__title">去会员页核对</span>
          <span class="followup-card__desc">{{ currentMemberActionText }}</span>
        </button>
        <button type="button" class="followup-card" @click="goToCurrentMemberLog">
          <span class="followup-card__title">去会员日志排查</span>
          <span class="followup-card__desc">{{ currentLogActionText }}</span>
        </button>
        <button type="button" class="followup-card" @click="goToFeedbackTypeView">
          <span class="followup-card__title">按当前类型继续处理</span>
          <span class="followup-card__desc"
            >保留当前反馈类型，继续做同类问题的批量排查和状态流转。</span
          >
        </button>
        <button type="button" class="followup-card" @click="goToSuggestedBoard">
          <span class="followup-card__title">{{ suggestedBoard.title }}</span>
          <span class="followup-card__desc">{{ suggestedBoard.desc }}</span>
        </button>
      </div>
      <div class="operation_btn mb5">
        <!-- 操作 -->
        <div>
          <div class="action-cluster">
            <span class="action-cluster__title">内容处理</span>
            <el-button type="primary" @click="add()">添加</el-button>
            <el-button title="状态" @click="selectOpen('status')">状态</el-button>
          </div>
          <div class="action-cluster">
            <span class="action-cluster__title">清理控制</span>
            <el-button title="禁用" @click="selectOpen('disable')">禁用</el-button>
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
        <el-table-column label="会员" min-width="130" show-overflow-tooltip>
          <template #default="scope">
            <el-button
              v-if="scope.row.member_id"
              link
              type="primary"
              @click="goToMember(scope.row)"
            >
              {{ scope.row.member_username || `会员 #${scope.row.member_id}` }}
            </el-button>
            <span v-else>{{ scope.row.member_username || '--' }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="type_name" label="类型" min-width="80" show-overflow-tooltip />
        <el-table-column prop="title" label="标题" min-width="200" show-overflow-tooltip />
        <el-table-column prop="phone" label="手机" min-width="120" show-overflow-tooltip />
        <el-table-column prop="email" label="邮箱" min-width="130" show-overflow-tooltip />
        <el-table-column prop="remark" label="备注" min-width="100" show-overflow-tooltip />
        <el-table-column prop="status" label="状态" min-width="85" sortable="custom">
          <template #default="scope">
            {{ statuss[scope.row.status] }}
          </template>
        </el-table-column>
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
        <el-table-column prop="receipt_no" label="回执编号" min-width="100" show-overflow-tooltip />
        <el-table-column prop="create_time" label="添加时间" width="165" sortable="custom" />
        <el-table-column prop="update_time" label="修改时间" width="165" sortable="custom" />
        <el-table-column label="操作" width="155">
          <template #default="scope">
            <el-link type="primary" class="mr-1" :underline="false" @click="edit(scope.row)">
              修改
            </el-link>
            <el-link
              v-if="scope.row.member_id"
              type="primary"
              class="mr-1"
              :underline="false"
              @click="goToMember(scope.row)"
            >
              会员
            </el-link>
            <el-link
              v-if="scope.row.member_id"
              type="primary"
              class="mr-1"
              :underline="false"
              @click="goToMemberLog(scope.row)"
            >
              日志
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
          <el-form-item label="会员ID" prop="member_id">
            <el-col :span="8">
              <el-input v-model="model.member_id" placeholder="请输入会员ID" clearable />
            </el-col>
            <el-col class="text-center" :span="4">会员用户名</el-col>
            <el-col :span="12">
              <el-input v-model="model.member_username" placeholder="" disabled />
            </el-col>
          </el-form-item>
          <el-form-item label="类型" prop="type">
            <el-select v-model="model.type">
              <el-option v-for="(item, index) in types" :key="index" :label="item" :value="index" />
            </el-select>
          </el-form-item>
          <el-form-item label="标题" prop="title">
            <el-input v-model="model.title" placeholder="请输入标题" clearable />
          </el-form-item>
          <el-form-item label="内容" prop="content">
            <el-input v-model="model.content" type="textarea" autosize placeholder="请输入内容" />
          </el-form-item>
          <el-form-item label="图片" prop="images">
            <FileUploads
              v-model="model.images"
              upload-btn="上传图片"
              file-type="image"
              file-tip="图片文件"
            />
          </el-form-item>
          <el-form-item label="手机" prop="phone">
            <el-input v-model="model.phone" placeholder="" clearable />
          </el-form-item>
          <el-form-item label="邮箱" prop="email">
            <el-input v-model="model.email" placeholder="" clearable />
          </el-form-item>
          <el-form-item label="回复" prop="reply">
            <el-input v-model="model.reply" type="textarea" autosize placeholder="请输入回复" />
          </el-form-item>
          <el-form-item label="状态" prop="status">
            <el-select v-model="model.status">
              <el-option
                v-for="(item, index) in statuss"
                :key="index"
                :label="item"
                :value="index"
              />
            </el-select>
          </el-form-item>
          <el-form-item label="备注" prop="remark">
            <el-input v-model="model.remark" placeholder="" clearable />
          </el-form-item>
          <el-form-item label="回执" prop="receipt_no">
            <el-input v-model="model.receipt_no" placeholder="回执编号（唯一）" clearable />
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
        <div class="dialog-footer-actions">
          <el-button v-if="model.member_id" @click="goToMember(model)">去会员页</el-button>
          <el-button v-if="model.member_id" @click="goToMemberLog(model)">去会员日志</el-button>
          <el-button @click="goToFeedbackTypeView">看同类反馈</el-button>
          <el-button @click="goToSuggestedBoard">{{ suggestedBoard.title }}</el-button>
        </div>
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
import { shortcuts } from '@/utils/getDate.js'
import { list, info, add, edit, dele, status as editsta, disable } from '@/api/setting/feedback'
import { getAdminRuntimeHint, resolveAdminRuntimeEnv } from '@/utils/runtime-env'

export default {
  name: 'SettingFeedback',
  components: { Pagination },
  data() {
    return {
      name: '反馈',
      height: 680,
      loading: false,
      idkey: 'feedback_id',
      exps: [{ exp: 'like', name: '包含' }],
      query: {
        page: 1,
        limit: getPageLimit(),
        search_field: 'title',
        search_exp: 'like',
        search_value: '',
        date_field: 'create_time',
        is_disable: undefined,
        type: undefined
      },
      data: [],
      count: 0,
      dialog: false,
      dialogTitle: '',
      model: {
        feedback_id: '',
        member_id: 0,
        member_username: '',
        receipt_no: '',
        type: 0,
        title: '',
        content: '',
        images: [],
        phone: '',
        email: '',
        reply: '',
        remark: '',
        status: 0
      },
      rules: {
        title: [{ required: true, message: '请输入标题', trigger: 'blur' }],
        content: [{ required: true, message: '请输入内容', trigger: 'blur' }]
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
      runtimeEnvInfo: resolveAdminRuntimeEnv(),
      shortcuts: shortcuts()
    }
  },
  computed: {
    runtimeHint() {
      return getAdminRuntimeHint(this.runtimeEnvInfo)
    },
    activeFilterTags() {
      const tags = []
      if (this.query.date_value?.length) {
        tags.push(`反馈时间：${this.query.date_value.join(' 至 ')}`)
      }
      if (this.query.type !== undefined) {
        tags.push(`反馈类型：${this.types[this.query.type] || this.query.type}`)
      }
      if (this.query.is_disable !== undefined) {
        tags.push(`启禁状态：${this.query.is_disable === 1 ? '禁用' : '启用'}`)
      }
      if (this.query.search_value) {
        tags.push(`关键词：${this.query.search_field || this.idkey}=${this.query.search_value}`)
      }
      return tags
    },
    currentTypeLabel() {
      return this.query.type === undefined
        ? '全部类型'
        : this.types[this.query.type] || this.query.type
    },
    currentDisableLabel() {
      return this.query.is_disable === undefined
        ? '全部状态'
        : this.query.is_disable === 1
        ? '禁用'
        : '启用'
    },
    summaryTags() {
      const tags = [...this.activeFilterTags]
      tags.unshift(`当前检索：${this.query.search_field || this.idkey}`)
      if (!this.activeFilterTags.length) {
        tags.push('默认条件：全部反馈')
      }
      if (this.selection.length) {
        tags.push(`样本预览：${this.buildSelectionPreview()}`)
      }
      return tags
    },
    summaryHint() {
      if (this.selection.length) {
        return `当前已选 ${this.selection.length} 条反馈，可继续做状态流转、禁用或删除处理。`
      }
      return this.selectRiskHint
    },
    feedbackFocusLabel() {
      if (this.selection.length) {
        return '先复核待处理反馈'
      }
      if (this.query.type !== undefined && this.query.type !== null && this.query.type !== '') {
        return '先看当前问题类型'
      }
      if (this.query.is_disable === 1) {
        return '先看已停用反馈'
      }
      return '先看高频问题'
    },
    feedbackGuideCards() {
      return [
        {
          title: '第一步先看是不是同类问题集中出现',
          desc:
            this.query.type !== undefined && this.query.type !== null && this.query.type !== ''
              ? `当前已经按 ${this.currentTypeLabel} 聚焦，可以先判断这类问题是个案还是批量爆发。`
              : '先按反馈类型和时间范围判断问题是否集中爆发，别把重复问题一条条分散处理。',
          action: '优先筛出同类型、同时间段的反馈，判断是否需要集中回访或统一修复。'
        },
        {
          title: '第二步再看要不要落到会员排查',
          desc: this.currentFocusRow.member_id
            ? '当前焦点反馈已经能定位到具体会员，可以直接联动会员页和会员日志页继续核查。'
            : '有些反馈只是表面现象，真正原因要去会员资料、日志或订单链路里看上下文。',
          action: '能定位会员就继续去会员页和日志页，不能定位就先补充回执、手机号或邮箱信息。'
        },
        {
          title: '第三步决定是结案、流转还是保留观察',
          desc: this.selection.length
            ? `当前已选 ${this.selection.length} 条反馈，处理时要区分哪些能立即闭环，哪些只是先改状态。`
            : '反馈页的重点不是“删掉看起来干净”，而是让状态和回执能真实反映处理进度。',
          action: '处理后同步更新状态、回执和备注，避免下次回看时只剩一条空记录。'
        }
      ]
    },
    currentFocusRow() {
      if (this.model && this.model[this.idkey]) {
        return this.model
      }
      return this.selection[0] || {}
    },
    currentMemberActionText() {
      if (this.currentFocusRow.member_id) {
        return `把当前反馈直接定位到会员 ${this.currentFocusRow.member_id}，继续核对账号信息、历史反馈和资料状态。`
      }
      return '当前还没锁定具体会员，先勾选一条反馈或打开详情后再继续去会员页。'
    },
    currentLogActionText() {
      if (this.currentFocusRow.member_id) {
        return `继续排查会员 ${this.currentFocusRow.member_id} 的登录日志、接口日志和相关行为轨迹。`
      }
      return '当前还没锁定具体会员，先勾选一条反馈或打开详情后再继续去会员日志。'
    },
    suggestedBoard() {
      const route = this.resolveSuggestedBoard(this.currentFocusRow)
      return route
    },
    selectRiskHint() {
      if (this.selectType === 'dele') {
        return '删除反馈会影响问题回溯和回执追踪，正式环境请先确认该记录不再用于客服或售后复核。'
      }
      if (this.selectType === 'disable') {
        return '禁用后该反馈将退出正常运营处理视图，提交前请确认是否已经完成登记或归档。'
      }
      if (this.selectType === 'status') {
        return '状态批量流转会影响客服处理节奏，建议结合回执编号和备注再次复核目标反馈。'
      }
      return this.runtimeEnvInfo.isProd
        ? '正式环境会直接影响反馈流转、回执跟踪和客服处理，请先确认筛选条件与勾选范围。'
        : '当前环境适合核对筛选、状态流转和回执回显，不要把测试结果当作正式运营数据。'
    }
  },
  watch: {
    '$route.fullPath'() {
      this.applyRouteQuery()
      this.list()
    }
  },
  created() {
    this.height = screenHeight()
    this.applyRouteQuery()
    this.list()
  },
  methods: {
    parseRouteNumber(value) {
      if (value === undefined || value === null || value === '') {
        return undefined
      }
      const parsed = Number(value)
      return Number.isFinite(parsed) ? parsed : undefined
    },
    applyRouteQuery() {
      const nextQuery = {
        ...this.$options.data().query,
        limit: this.query?.limit || getPageLimit()
      }
      const routeQuery = this.$route?.query || {}
      if (routeQuery.search_field) {
        nextQuery.search_field = String(routeQuery.search_field)
      }
      if (routeQuery.search_exp) {
        nextQuery.search_exp = String(routeQuery.search_exp)
      }
      if (routeQuery.search_value !== undefined || routeQuery.keyword !== undefined) {
        nextQuery.search_value = String(routeQuery.search_value ?? routeQuery.keyword ?? '')
      }
      const isDisable = this.parseRouteNumber(routeQuery.is_disable)
      if (isDisable !== undefined) {
        nextQuery.is_disable = isDisable
      }
      const type = this.parseRouteNumber(routeQuery.type)
      if (type !== undefined) {
        nextQuery.type = type
      }
      this.query = nextQuery
    },
    handleDisableChange(row) {
      const nextValue = row.is_disable
      const previousValue = nextValue === 1 ? 0 : 1
      ElMessageBox.confirm(
        `确认要${nextValue === 1 ? '禁用' : '启用'}反馈「${row.title || row[this.idkey]}」吗？`,
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
    buildSelectionPreview(limit = 5) {
      return (
        this.selection
          .slice(0, limit)
          .map((item) => item[this.idkey])
          .join('、') + (this.selection.length > limit ? ' 等' : '')
      )
    },
    setRecentActionSummary(action, extra = '') {
      this.recentActionSummary = `已执行${action}，影响 ${this.selection.length || 0} 条反馈${
        extra ? `，${extra}` : ''
      }。`
    },
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
          this.recentActionSummary = `已加载反馈列表，共 ${res.data.count || 0} 条，当前已选 ${
            this.selection.length || 0
          } 条。`
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
      this.recentActionSummary = '准备新增反馈记录。'
    },
    edit(row) {
      this.dialog = true
      this.dialogTitle = this.name + '修改：' + row[this.idkey]
      this.recentActionSummary = `准备修改反馈 ${row[this.idkey]}。`
      var id = {}
      id[this.idkey] = row[this.idkey]
      info(id)
        .then((res) => {
          this.reset(res.data)
        })
        .catch(() => {})
    },
    goToMember(row = {}) {
      if (!row.member_id) {
        return
      }
      this.$router.push({
        path: '/member/member',
        query: {
          search_field: 'member_id',
          search_exp: '=',
          search_value: String(row.member_id),
          from: 'setting-feedback'
        }
      })
    },
    goToMemberLog(row = {}) {
      if (!row.member_id) {
        return
      }
      this.$router.push({
        path: '/member/log',
        query: {
          search_field: 'member_id',
          search_exp: '=',
          search_value: String(row.member_id),
          from: 'setting-feedback'
        }
      })
    },
    goToCurrentMember() {
      const row = this.currentFocusRow
      if (!row.member_id) {
        ElMessage.warning('当前没有可定位的会员')
        return
      }
      this.goToMember(row)
    },
    goToCurrentMemberLog() {
      const row = this.currentFocusRow
      if (!row.member_id) {
        ElMessage.warning('当前没有可排查的会员日志')
        return
      }
      this.goToMemberLog(row)
    },
    goToFeedbackTypeView() {
      if (this.model && this.model[this.idkey]) {
        this.dialog = false
      }
      this.query.page = 1
      this.list()
    },
    resolveSuggestedBoard(row = {}) {
      const typeName = String(row.type_name || this.currentTypeLabel || '').toLowerCase()
      const title = String(row.title || '').toLowerCase()
      const remark = String(row.remark || '').toLowerCase()
      const combined = `${typeName} ${title} ${remark}`

      if (/商家|入驻|收款|菜单|门店/.test(combined)) {
        return {
          path: '/merchant/merchant',
          title: '去商家页排查',
          desc: '这类反馈更像商家资料、入驻、收款码或菜单问题，继续去商家管理页核对主体信息和运营状态。'
        }
      }
      if (/商品|上架|详情|库存|价格|购买/.test(combined)) {
        return {
          path: '/goods/Goods',
          query: { limit: 1000 },
          title: '去商品页排查',
          desc: '这类反馈更像商品展示、库存、价格或购买链路问题，继续去商品管理页核对可售状态和商品资料。'
        }
      }
      if (/内容|文章|公告|协议|帮助|轮播/.test(combined)) {
        return {
          path: '/content/content',
          title: '去内容页排查',
          desc: '这类反馈更像文章、公告、协议或内容素材问题，继续去内容管理页核对发布状态和展示内容。'
        }
      }
      if (row.member_id) {
        return {
          path: '/member/member',
          query: {
            search_field: 'member_id',
            search_exp: '=',
            search_value: String(row.member_id)
          },
          title: '去会员页排查',
          desc: `当前反馈已关联会员 ${row.member_id}，继续去会员页核对账号状态、资料和历史处理记录。`
        }
      }
      return {
        path: '/dashboard',
        title: '去首页看全局',
        desc: '当前还不适合直接跳某条业务链路，先回首页看告警、快捷入口和全局数据，再决定分流方向。'
      }
    },
    goToSuggestedBoard() {
      const route = this.suggestedBoard
      this.$router.push({
        path: route.path,
        query: {
          from: 'setting-feedback',
          ...(route.query || {})
        }
      })
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
                this.recentActionSummary = `已修改反馈：${
                  this.model.title || this.model[this.idkey]
                }。`
                ElMessage.success(res.msg)
                this.$nextTick(() => {
                  ElMessage.info(`建议下一步：${this.suggestedBoard.title}。`)
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
                this.recentActionSummary = `已新增反馈：${this.model.title || '未命名反馈'}。`
                ElMessage.success(res.msg)
                this.$nextTick(() => {
                  ElMessage.info(`建议下一步：${this.suggestedBoard.title}。`)
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
            this.setRecentActionSummary(
              '批量调整反馈状态',
              `目标状态：${this.statuss[status] || status}`
            )
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
            this.setRecentActionSummary(
              '批量调整反馈禁用状态',
              `目标状态：${is_disable === 1 ? '禁用' : '启用'}`
            )
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
            this.setRecentActionSummary('批量删除反馈')
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
  background: #e8f0ff;
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

.plain-guide {
  margin-bottom: 12px;
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

.followup-strip {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 12px;
  margin-bottom: 12px;
}

.followup-card {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 6px;
  min-height: 104px;
  padding: 14px 16px;
  text-align: left;
  border: 1px solid rgba(148, 163, 184, 0.16);
  border-radius: 12px;
  background: linear-gradient(180deg, rgba(248, 250, 252, 0.96), #ffffff 100%);
}

.followup-card__title {
  font-size: 13px;
  font-weight: 700;
  color: #334155;
}

.followup-card__desc {
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

.action-cluster__title {
  font-size: 12px;
  color: #7c8aa5;
}

.dialog-footer-actions {
  display: inline-flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-right: auto;
}

@media (max-width: 900px) {
  .page-head,
  .plain-guide__header,
  .section-title-row {
    flex-direction: column;
  }

  .section-title-row__meta {
    white-space: normal;
  }
}
</style>
