<template>
  <div class="app-container">
    <el-card class="app-head head-pb20">
      <div class="section-title-row">
        <div>
          <div class="section-title-row__title">公告管理</div>
          <div class="section-title-row__desc">
            统一维护公告内容、类型、投放时间与启用状态，默认首屏直达筛选和列表。
          </div>
        </div>
        <div class="section-title-row__meta">
          <el-tag :type="runtimeEnvInfo.tone">{{ runtimeEnvInfo.label }}</el-tag>
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
            <el-form-item label="类型：" prop="type">
              <el-select v-model="query.type" @change="search()" clearable>
                <el-option
                  v-for="(item, index) in types"
                  :key="index"
                  :value="index"
                  :label="item"
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
                  <el-option value="type" label="类型" />
                  <el-option value="title" label="标题" />
                  <el-option value="desc" label="描述" />
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
      <div class="summary-bar">
        <span class="summary-bar__item">总量：{{ count }}</span>
        <span class="summary-bar__item">已启用：{{ enabledNoticeCount }}</span>
        <span class="summary-bar__item">已选：{{ selection.length }} 项</span>
        <span class="summary-bar__item">当前操作：{{ noticeActionSummary }}</span>
      </div>
      <div class="plain-guide">
        <div class="plain-guide__header">
          <div>
            <div class="plain-guide__title">这页建议先这样看</div>
            <div class="plain-guide__desc">
              公告页主要在管“什么时候展示、展示给谁、现在还要不要挂着”。先看公告是否还有效，再确认类型和时间，最后才做批量启用、禁用或删除。
            </div>
          </div>
          <span class="plain-guide__badge">{{ noticeFocusLabel }}</span>
        </div>
        <div class="plain-guide__grid">
          <div v-for="item in noticeGuideCards" :key="item.title" class="plain-guide-card">
            <div class="plain-guide-card__title">{{ item.title }}</div>
            <div class="plain-guide-card__desc">{{ item.desc }}</div>
            <div class="plain-guide-card__action">{{ item.action }}</div>
          </div>
        </div>
      </div>
      <div class="followup-panel">
        <div class="followup-panel__main">
          <div class="followup-panel__title">看完公告后继续去哪</div>
          <div class="followup-panel__desc">{{ followupHint }}</div>
          <div class="followup-panel__tags">
            <span v-for="item in followupTags" :key="item">{{ item }}</span>
          </div>
        </div>
        <div class="followup-panel__actions">
          <el-button type="primary" @click="openH5Home">去 H5 首页看公告</el-button>
          <el-button @click="goToPage('/setting/carousel')">去轮播管理</el-button>
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
        <div v-if="selection.length" class="select-review-panel">
          <div class="select-review-panel__title">提交前复核</div>
          <div class="select-review-panel__tags">
            <span v-for="item in selectReviewItems" :key="item">{{ item }}</span>
          </div>
          <div class="select-review-panel__hint">{{ selectRiskHint }}</div>
        </div>
        <el-form-item v-if="selectType === 'edittype'" label="类型">
          <el-select v-model="type">
            <el-option v-for="(item, index) in types" :key="index" :value="index" :label="item" />
          </el-select>
        </el-form-item>
        <el-form-item v-else-if="selectType === 'datetime'" label="时间范围">
          <el-date-picker
            v-model="start_time"
            type="datetime"
            value-format="YYYY-MM-DD HH:mm:ss"
            :default-time="new Date(2024, 1, 1, 0, 0, 0)"
            placeholder="开始时间"
          />
          <span>至</span>
          <el-date-picker
            v-model="end_time"
            type="datetime"
            value-format="YYYY-MM-DD HH:mm:ss"
            :default-time="new Date(2024, 1, 1, 23, 59, 59)"
            placeholder="结束时间"
          />
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
      <div class="active-filter-strip">
        <div class="active-filter-strip__title">当前筛选</div>
        <div class="active-filter-strip__tags">
          <el-tag v-for="item in activeFilterTags" :key="item" effect="plain">{{ item }}</el-tag>
          <el-tag v-if="!activeFilterTags.length" effect="plain" type="info"
            >默认条件：全部公告</el-tag
          >
        </div>
      </div>
      <div class="section-title-row section-title-row--content">
        <div>
          <div class="section-title-row__title">公告列表</div>
          <div class="section-title-row__desc">
            支持批量类型调整、发布时间维护和公告内容发布控制。
          </div>
        </div>
        <div class="section-title-row__meta">已选 {{ selection.length }} 项</div>
      </div>
      <div class="operation_btn mb5">
        <!-- 操作 -->
        <div>
          <div class="action-cluster">
            <span class="action-cluster__title">内容维护</span>
            <el-button type="primary" @click="add()">添加</el-button>
            <el-button title="修改类型" @click="selectOpen('edittype')">类型</el-button>
            <el-button title="时间范围" @click="selectOpen('datetime')">时间</el-button>
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
      >
        <el-table-column type="selection" width="42" title="全选/反选" />
        <el-table-column :prop="idkey" label="ID" width="80" sortable="custom" />
        <el-table-column prop="image_id" label="图片" min-width="62">
          <template #default="scope">
            <FileImage :file-url="scope.row.image_url" lazy />
          </template>
        </el-table-column>
        <el-table-column prop="type_name" label="类型" min-width="85" />
        <el-table-column prop="title" label="标题" min-width="220" show-overflow-tooltip>
          <template #default="scope">
            <span :style="{ color: scope.row.title_color }">{{ scope.row.title }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="start_time" label="开始时间" width="165" sortable="custom" />
        <el-table-column prop="end_time" label="结束时间" width="165" sortable="custom" />
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
      <el-form ref="ref" :model="model" :rules="rules" label-width="100px">
        <div class="dialog-review-note">
          <div class="dialog-review-note__title">提交前复核</div>
          <div class="dialog-review-note__tags">
            <span class="dialog-review-note__tag">运行环境：{{ runtimeEnvInfo.label }}</span>
            <span class="dialog-review-note__tag"
              >类型：{{ types[model.type] || model.type || '未选择' }}</span
            >
            <span class="dialog-review-note__tag">标题：{{ model.title || '未填写' }}</span>
          </div>
          <div class="dialog-review-note__risk">{{ noticeFormRisk }}</div>
        </div>
        <el-tabs>
          <el-tab-pane label="信息">
            <el-scrollbar native :max-height="height - 30">
              <el-form-item label="图片" prop="image_id">
                <FileImage
                  v-model="model.image_id"
                  :file-url="model.image_url"
                  :height="100"
                  upload
                />
              </el-form-item>
              <el-form-item label="类型" prop="type">
                <el-select v-model="model.type">
                  <el-option
                    v-for="(item, index) in types"
                    :key="index"
                    :value="index"
                    :label="item"
                  />
                </el-select>
              </el-form-item>
              <el-form-item label="标题" prop="title">
                <el-col :span="18">
                  <el-input v-model="model.title" placeholder="请输入标题" clearable />
                </el-col>
                <el-col :span="3" class="text-center">标题颜色</el-col>
                <el-col :span="3">
                  <el-color-picker v-model="model.title_color" />
                </el-col>
              </el-form-item>
              <el-form-item label="描述" prop="desc">
                <el-input v-model="model.desc" type="textarea" autosize placeholder="请输入描述" />
              </el-form-item>
              <el-form-item label="开始时间" prop="start_time">
                <el-date-picker
                  v-model="model.start_time"
                  type="datetime"
                  value-format="YYYY-MM-DD HH:mm:ss"
                  placeholder="开始时间"
                  :default-time="new Date(2024, 1, 1, 0, 0, 0)"
                />
              </el-form-item>
              <el-form-item label="结束时间" prop="end_time">
                <el-date-picker
                  v-model="model.end_time"
                  type="datetime"
                  value-format="YYYY-MM-DD HH:mm:ss"
                  placeholder="结束时间"
                  :default-time="new Date(2024, 1, 1, 23, 59, 59)"
                />
              </el-form-item>
              <el-form-item label="备注" prop="remark">
                <el-input v-model="model.remark" placeholder="请输入备注" clearable />
              </el-form-item>
              <el-form-item label="排序" prop="sort">
                <el-input v-model="model.sort" type="number" placeholder="请输入排序" />
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
import { arrayColumn } from '@/utils/index'
import { getPageLimit } from '@/utils/settings'
import { getAdminRuntimeHint, resolveAdminRuntimeEnv } from '@/utils/runtime-env'
import { list, info, add, edit, dele, edittype, datetime, disable } from '@/api/setting/notice'

export default {
  name: 'SettingNotice',
  components: { Pagination, RichEditor },
  data() {
    return {
      name: '通告',
      height: 680,
      loading: false,
      idkey: 'notice_id',
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
        notice_id: '',
        image_id: 0,
        image_url: '',
        type: 0,
        title: '',
        title_color: '',
        start_time: '',
        end_time: '',
        desc: '',
        content: '',
        remark: '',
        sort: 250
      },
      rules: {
        title: [{ required: true, message: '请输入标题', trigger: 'blur' }],
        start_time: [{ required: true, message: '请输入开始时间', trigger: 'blur' }],
        end_time: [{ required: true, message: '请输入结束时间', trigger: 'blur' }]
      },
      types: [],
      selection: [],
      selectIds: '',
      selectTitle: '操作',
      selectDialog: false,
      selectType: '',
      type: 0,
      start_time: '',
      end_time: '',
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
    entrySourceLabel() {
      const source = String(this.$route?.query?.from || '')
      if (source === 'dashboard') return '来自控制台总览'
      if (source === 'system-setting') return '来自系统设置中心'
      if (source === 'setting-accord') return '来自协议管理'
      return ''
    },
    entryContextVisible() {
      return Boolean(this.entrySourceLabel)
    },
    entryContextTitle() {
      if (this.entrySourceLabel === '来自控制台总览') return '当前从控制台进入公告管理'
      if (this.entrySourceLabel === '来自系统设置中心') return '当前从系统设置中心进入公告管理'
      if (this.entrySourceLabel === '来自协议管理') return '当前从协议管理进入公告管理'
      return '当前为外部入口承接视角'
    },
    entryContextDesc() {
      if (this.entrySourceLabel === '来自控制台总览') {
        return '这类进入通常是为了核首页露出或异常提醒。建议先看启用状态和投放时间，再去 H5 首页复核是不是已经按预期展示。'
      }
      if (this.entrySourceLabel === '来自系统设置中心') {
        return '这类进入通常是为了继续核对前台展示链路。建议先确认公告是否启用，再去轮播和内容大厅看首页口径是否一致。'
      }
      if (this.entrySourceLabel === '来自协议管理') {
        return '这类进入通常是为了补用户提醒或首页说明，建议先看是否需要同步公告文案和投放时间。'
      }
      return ''
    },
    entryContextPrimaryLabel() {
      if (this.entrySourceLabel === '来自控制台总览') return '去 H5 首页复核'
      if (this.entrySourceLabel === '来自协议管理') return '回协议管理'
      return '去轮播管理'
    },
    noticeActionSummary() {
      const map = {
        edittype: '批量改类型',
        datetime: '批量改时间',
        disable: '批量改状态',
        dele: '批量删除'
      }
      return map[this.selectType] || '公告维护'
    },
    noticeSubmitRisk() {
      if (this.selectType === 'dele') {
        return this.runtimeEnvInfo.isProd
          ? '正式环境下删除公告会直接影响前台展示，请先确认当前选中公告和投放周期。'
          : '当前环境适合验证公告删除和回显，不要把测试删除结果当作正式运营结果。'
      }
      if (!this.selection.length) {
        return this.runtimeEnvInfo.isProd
          ? '正式环境下批量操作会直接影响线上公告，请先选择记录后再提交。'
          : '当前环境建议先选择要验证的公告，再执行批量操作。'
      }
      return this.runtimeEnvInfo.isProd
        ? `正式环境下本次会直接影响 ${this.selection.length} 条公告，请先复核类型、时间和状态。`
        : `当前环境可用于验证 ${this.selection.length} 条公告的批量操作和结果回显。`
    },
    noticeFormRisk() {
      if (!this.model.title) {
        return '当前标题未填写，提交会被表单校验拦截。'
      }
      if (this.model.type === '' || this.model.type === undefined || this.model.type === null) {
        return '当前类型未选择，建议先确认公告归属类型再提交。'
      }
      if (!this.model.start_time || !this.model.end_time) {
        return '当前发布时间区间未填写完整，建议补齐后再提交。'
      }
      if (this.model.start_time > this.model.end_time) {
        return '结束时间早于开始时间，提交前需要调整时间范围。'
      }
      return this.runtimeEnvInfo.isProd
        ? '正式环境下提交会直接影响前台公告内容，请复核标题、类型和发布时间。'
        : '当前环境适合验证公告新增和修改流程，可继续核对内容与回显。'
    },
    activeFilterTags() {
      const tags = []
      if (this.query.date_value?.length === 2) {
        tags.push(`添加时间：${this.query.date_value[0]} 至 ${this.query.date_value[1]}`)
      }
      if (this.query.is_disable !== undefined) {
        tags.push(`状态：${this.query.is_disable === 1 ? '禁用' : '启用'}`)
      }
      if (this.query.type !== undefined) {
        tags.push(`类型：${this.types[this.query.type] || this.query.type}`)
      }
      if (this.query.search_value) {
        tags.push(`检索：${this.query.search_value}`)
      }
      return tags
    },
    selectReviewItems() {
      const items = [
        `操作：${this.selectTitle || '批量处理'}`,
        `数量：${this.selection.length} 项`,
        `对象：${this.buildSelectionPreview()}`
      ]
      if (this.selectType === 'edittype') {
        items.push(`目标类型：${this.types[this.type] || this.type}`)
      } else if (this.selectType === 'datetime' && (this.start_time || this.end_time)) {
        items.push(`时间范围：${this.start_time || '未设置'} 至 ${this.end_time || '未设置'}`)
      } else if (this.selectType === 'disable') {
        items.push(`状态调整：${this.is_disable === 1 ? '批量禁用' : '批量启用'}`)
      }
      return items
    },
    selectRiskHint() {
      if (this.selectType === 'dele') {
        return '删除公告后会直接影响前台可见内容，请先确认所选公告不在当前投放周期内。'
      }
      if (this.selectType === 'datetime') {
        return `修改发布时间会影响前台展示窗口。${
          this.datetimeConflictHint
            ? ` 当前提示：${this.datetimeConflictHint}`
            : '建议先复核开始时间、结束时间和当前启用状态。'
        }`
      }
      return '提交前请确认所选公告范围与目标值一致，避免把发布时间或类型批量改错。'
    },
    datetimeConflictHint() {
      if (this.selectType !== 'datetime') {
        return ''
      }
      if (this.start_time && this.end_time && this.start_time > this.end_time) {
        return '结束时间早于开始时间，提交前需要调整。'
      }
      const enabledRows = this.selection.filter((item) => Number(item.is_disable || 0) === 0).length
      if (enabledRows && (this.start_time || this.end_time)) {
        return `当前有 ${enabledRows} 条启用公告会直接受发布时间变更影响。`
      }
      return ''
    },
    enabledNoticeCount() {
      return this.data.filter((item) => Number(item.is_disable || 0) === 0).length
    },
    timedNoticeCount() {
      return this.data.filter((item) => item.start_time || item.end_time).length
    },
    noticeFocusLabel() {
      if (this.selection.length) {
        return '先复核批量处理'
      }
      if (this.query.is_disable === 1) {
        return '先看下线公告'
      }
      if (this.query.type !== undefined) {
        return '先看当前公告类型'
      }
      return '先看投放时效'
    },
    noticeGuideCards() {
      return [
        {
          title: '第一步先看这条公告还该不该继续挂',
          desc:
            this.query.is_disable === 1
              ? '当前筛出来的是已停用公告，先判断哪些只是暂时下线，哪些已经可以彻底清理。'
              : '很多公告问题不是文案本身，而是活动早结束了还挂着。先判断它现在是否还该继续展示。',
          action: '先按是否仍有效分组，再决定保留、禁用还是删除。'
        },
        {
          title: '第二步再看类型和投放时间',
          desc:
            this.query.type !== undefined
              ? `当前已经聚焦到 ${
                  this.types[this.query.type] || this.query.type
                }，适合继续复核这类公告的开始和结束时间。`
              : `当前列表里有 ${this.timedNoticeCount} 条带时间控制的公告，时间配错比内容写错更容易出运营事故。`,
          action: '优先确认类型归属、开始时间、结束时间和当前启用状态是否一致。'
        },
        {
          title: '第三步联动检查首页入口',
          desc: this.selection.length
            ? `当前已选 ${this.selection.length} 条公告，处理完后最好继续去轮播和友链页核对入口口径。`
            : '公告处理不是孤立动作，通常还要继续确认轮播、友链和内容大厅有没有同步更新。',
          action: '处理完成后继续去轮播管理、友链管理和内容大厅做联动复核。'
        }
      ]
    },
    noticeFollowupHint() {
      if (!this.count) {
        return '当前暂无公告内容，建议先补齐首页通知和活动公告。'
      }
      return this.runtimeEnvInfo.isProd
        ? '正式环境建议先核对启用状态和投放时间，再执行批量类型与时间调整。'
        : '当前环境适合验证公告发布时间、批量禁用和内容回显。'
    },
    noticeFollowupBadge() {
      return this.enabledNoticeCount > 0 ? '可投放' : '待启用'
    },
    noticeFollowupItems() {
      const typePreview = Array.from(
        new Set(this.data.map((item) => item.type_name).filter(Boolean))
      ).slice(0, 3)
      return [
        `已启用：${this.enabledNoticeCount} 条`,
        `含时间窗口：${this.timedNoticeCount} 条`,
        `公告类型：${typePreview.join('、') || '未设置'}`,
        `当前选中：${this.buildSelectionPreview() || '未选择'}`
      ]
    },
    noticeFollowupRisk() {
      if (this.selection.length) {
        return `当前已选 ${this.selection.length} 条公告，请重点复核类型、时间范围和启用状态。`
      }
      return this.runtimeEnvInfo.isProd
        ? '正式环境下批量调整会直接影响前台公告可见性，建议按类型或时间窗口分批处理。'
        : '测试环境建议优先验证开始时间、结束时间和禁用状态的联动回显。'
    },
    followupHint() {
      if (this.selection.length) {
        return `当前已选 ${this.selection.length} 条公告，处理完成后建议先去 H5 首页看露出内容，再去轮播页核对首页口径和投放时间是否仍然一致。`
      }
      if (this.query.type !== undefined) {
        return '当前已经聚焦到某个公告类型，下一步更适合去 H5 首页和轮播页核对这一类内容在首页入口上的承接关系。'
      }
      return '公告页主要解决内容发布时间和启用状态；通常处理完这里后，要先去 H5 首页看露出，再去轮播页和内容大厅确认首页口径一致。'
    },
    followupTags() {
      return [
        `环境：${this.runtimeEnvInfo.label}`,
        `已启用：${this.enabledNoticeCount} 条`,
        `含时间窗：${this.timedNoticeCount} 条`,
        `已选：${this.selection.length} 项`
      ]
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
    handleEntryContextPrimary() {
      if (this.entrySourceLabel === '来自控制台总览') {
        this.openH5Home()
        return
      }
      if (this.entrySourceLabel === '来自协议管理') {
        this.goToPage('/setting/accord')
        return
      }
      this.goToPage('/setting/carousel')
    },
    goToEntryContextBack() {
      if (this.entrySourceLabel === '来自控制台总览') {
        this.$router.push({ path: '/dashboard', query: { from: 'setting-notice' } })
        return
      }
      if (this.entrySourceLabel === '来自系统设置中心') {
        this.$router.push({ path: '/system/setting', query: { from: 'setting-notice' } })
        return
      }
      if (this.entrySourceLabel === '来自协议管理') {
        this.$router.push({ path: '/setting/accord', query: { from: 'setting-notice' } })
      }
    },
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
    buildSelectionPreview(limit = 5) {
      if (!this.selection.length) {
        return ''
      }
      return (
        this.selection
          .slice(0, limit)
          .map((item) => item[this.idkey])
          .join('、') + (this.selection.length > limit ? ' 等' : '')
      )
    },
    setRecentActionSummary(action, extra = '') {
      this.recentActionSummary = `已执行${action}，影响 ${this.selection.length || 0} 条公告${
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
                this.recentActionSummary = `已更新公告 ${this.model[this.idkey]}，类型：${
                  this.types[this.model.type] || this.model.type || '未设置'
                }，标题：${this.model.title || '未填写'}。`
                ElMessage.success(res.msg)
                this.$nextTick(() => {
                  ElMessage.info('公告已提交，请继续去 H5 首页、轮播管理和内容大厅各核对一次。')
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
                this.recentActionSummary = `已新增公告，类型：${
                  this.types[this.model.type] || this.model.type || '未设置'
                }，标题：${this.model.title || '未填写'}。`
                ElMessage.success(res.msg)
                this.$nextTick(() => {
                  ElMessage.info('公告已提交，请继续去 H5 首页、轮播管理和内容大厅各核对一次。')
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
        if (selectType === 'edittype') {
          this.selectTitle = this.name + '修改类型'
        } else if (selectType === 'datetime') {
          this.selectTitle = this.name + '时间范围'
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
        if (selectType === 'edittype') {
          this.edittype(this.selection)
        } else if (selectType === 'datetime') {
          this.datetime(this.selection)
        } else if (selectType === 'disable') {
          this.disable(this.selection, true)
        } else if (selectType === 'dele') {
          this.dele(this.selection)
        }
        this.selectDialog = false
      }
    },
    // 修改类型
    edittype(row) {
      if (!row.length) {
        this.selectAlert()
      } else {
        this.loading = true
        edittype({
          ids: this.selectGetIds(row),
          type: this.type
        })
          .then((res) => {
            this.list()
            this.setRecentActionSummary(
              '批量修改公告类型',
              `目标类型：${this.types[this.type] || this.type}`
            )
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.loading = false
          })
      }
    },
    // 时间范围
    datetime(row) {
      if (!row.length) {
        this.selectAlert()
      } else {
        if (this.start_time && this.end_time && this.start_time > this.end_time) {
          ElMessage.error('结束时间不能早于开始时间')
          return
        }
        this.loading = true
        datetime({
          ids: this.selectGetIds(row),
          start_time: this.start_time,
          end_time: this.end_time
        })
          .then((res) => {
            this.list()
            this.setRecentActionSummary(
              '批量调整发布时间',
              `时间范围：${this.start_time || '未设置'} 至 ${this.end_time || '未设置'}`
            )
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
            this.setRecentActionSummary(
              '批量调整公告状态',
              `目标状态：${is_disable === 1 ? '禁用' : '启用'}`
            )
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.list()
          })
      }
    },
    handleDisableSwitch(row, value) {
      ElMessageBox.confirm(
        `确认要${value === 1 ? '禁用' : '启用'}公告「${row.title || row[this.idkey]}」吗？`,
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
        dele({
          ids: this.selectGetIds(row)
        })
          .then((res) => {
            this.list()
            this.setRecentActionSummary('批量删除公告')
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
          from: 'setting-notice'
        }
      })
    },
    openH5Home() {
      window.open(`${window.location.origin}/app/`, '_blank')
    }
  }
}
</script>
<style lang="scss" scoped>
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

.dialog-review-note__tags,
.summary-bar {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 12px;
}

.summary-bar {
  padding-top: 14px;
  border-top: 1px solid #eef2f7;
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

.summary-bar__item,
.dialog-review-note__tags {
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  padding: 0 10px;
  border-radius: 999px;
  background: #eff6ff;
  color: #1d4ed8;
  font-size: 12px;
}

.dialog-review-note__tag {
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  padding: 0 10px;
  border-radius: 999px;
  background: #eff6ff;
  color: #1d4ed8;
  font-size: 12px;
}

.dialog-review-note__risk {
  margin-top: 12px;
  padding: 12px 14px;
  border-radius: 12px;
  background: #fff7ed;
  border: 1px solid #fed7aa;
  color: #9a3412;
  font-size: 12px;
  line-height: 1.7;
}

.dialog-review-note {
  margin-bottom: 16px;
  padding: 14px 16px;
  border: 1px solid #dbe7f5;
  border-radius: 14px;
  background: #f8fbff;
}

.dialog-review-note__title {
  font-size: 12px;
  font-weight: 700;
  color: #475569;
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
  display: inline-flex;
  align-items: center;
  gap: 8px;
  white-space: nowrap;
}

.active-filter-strip {
  margin: 14px 0;
  padding: 14px 16px;
  border: 1px solid #e6ecf5;
  border-radius: 14px;
  background: #f8fbff;
}

.active-filter-strip__title {
  margin-bottom: 10px;
  font-size: 12px;
  font-weight: 700;
  color: #64748b;
}

.active-filter-strip__tags {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.select-review-panel {
  margin-bottom: 16px;
  padding: 14px 16px;
  background: #f8fbff;
  border: 1px solid #dbe7f5;
  border-radius: 14px;
}

.select-review-panel__title {
  margin-bottom: 10px;
  font-size: 12px;
  font-weight: 700;
  color: #475569;
}

.select-review-panel__tags {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.select-review-panel__tags span {
  display: inline-flex;
  align-items: center;
  min-height: 30px;
  padding: 0 10px;
  font-size: 12px;
  color: #334155;
  background: #fff;
  border: 1px solid #dbe7f5;
  border-radius: 999px;
}

.select-review-panel__hint {
  margin-top: 10px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.action-cluster {
  display: inline-flex;
  flex-wrap: wrap;
  gap: 10px;
  align-items: center;
  padding: 10px 12px;
  background: rgba(248, 250, 252, 0.9);
  border: 1px solid rgba(148, 163, 184, 0.14);
  border-radius: 14px;
}

.action-cluster__title {
  font-size: 12px;
  font-weight: 700;
  color: #64748b;
}

@media (max-width: 900px) {
  .section-title-row,
  .plain-guide__header,
  .followup-panel {
    flex-direction: column;
  }

  .section-title-row__meta {
    white-space: normal;
  }
}
</style>
