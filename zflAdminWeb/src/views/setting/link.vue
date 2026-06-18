<template>
  <div class="app-container">
    <el-card class="app-head">
      <div class="section-title-row">
        <div>
          <div class="section-title-row__title">友链管理</div>
          <div class="section-title-row__desc">
            统一处理友链筛选、展示周期调整、状态控制和前台入口维护。
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
            <el-button title="重置" @click="refresh()"> 重置 </el-button>
          </el-col>
        </el-row>
      </el-form>
      <div class="link-summary-bar">
        <div class="link-summary-bar__chips">
          <span class="summary-chip summary-chip--primary">总数 {{ count }}</span>
          <span class="summary-chip">已选 {{ selection.length }} 项</span>
          <span class="summary-chip">状态 {{ statusFilterLabel }}</span>
          <span class="summary-chip">当前操作 {{ linkActionSummary }}</span>
          <span class="summary-chip">数据模式 {{ runtimeEnvInfo.dataMode }}</span>
          <span v-for="item in activeFilterTags" :key="item" class="summary-chip">{{ item }}</span>
          <span v-if="!activeFilterTags.length" class="summary-chip">默认条件：全部友链</span>
          <span v-if="recentActionSummary" class="summary-chip summary-chip--muted">{{
            recentActionSummary
          }}</span>
        </div>
        <div class="link-summary-bar__hint" :class="summaryBadgeClass">
          <span class="link-summary-bar__hint-title">{{ summaryBadgeText }}</span>
          <span class="link-summary-bar__hint-text">{{ summaryHint }}</span>
        </div>
      </div>
      <div class="plain-guide">
        <div class="plain-guide__header">
          <div>
            <div class="plain-guide__title">这页建议先这样看</div>
            <div class="plain-guide__desc">
              友链页主要在管外部跳转入口和展示周期。先判断它是不是还要继续展示，再决定改时间、禁用还是删除，不要把它当普通文案页。
            </div>
          </div>
          <span class="plain-guide__badge">{{ linkFocusLabel }}</span>
        </div>
        <div class="plain-guide__grid">
          <div v-for="item in linkGuideCards" :key="item.title" class="plain-guide-card">
            <div class="plain-guide-card__title">{{ item.title }}</div>
            <div class="plain-guide-card__desc">{{ item.desc }}</div>
            <div class="plain-guide-card__action">{{ item.action }}</div>
          </div>
        </div>
      </div>
      <div class="followup-panel">
        <div class="followup-panel__main">
          <div class="followup-panel__title">看完友链后继续去哪</div>
          <div class="followup-panel__desc">{{ followupHint }}</div>
          <div class="followup-panel__tags">
            <span v-for="item in followupTags" :key="item">{{ item }}</span>
          </div>
        </div>
        <div class="followup-panel__actions">
          <el-button type="primary" @click="openH5Home">去 H5 首页看入口</el-button>
          <el-button @click="goToPage('/setting/carousel')">去轮播管理</el-button>
          <el-button @click="goToPage('/setting/notice')">去公告管理</el-button>
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
        <div v-if="selection.length" class="dialog-review-note">
          <div class="dialog-review-note__title">提交前复核</div>
          <div class="dialog-review-note__tags">
            <span class="dialog-review-note__tag">运行环境：{{ runtimeEnvInfo.label }}</span>
            <span class="dialog-review-note__tag">操作：{{ linkActionSummary }}</span>
            <span class="dialog-review-note__tag">对象：{{ selectIds || '未选择' }}</span>
          </div>
          <div class="dialog-review-note__risk">{{ linkSubmitRisk }}</div>
        </div>
        <el-form-item v-if="selectType === 'datetime'" label="时间范围">
          <el-date-picker
            v-model="start_time"
            type="datetime"
            value-format="YYYY-MM-DD HH:mm:ss"
            default-time="00:00:00"
            placeholder="开始时间"
          />
          <span>至</span>
          <el-date-picker
            v-model="end_time"
            type="datetime"
            value-format="YYYY-MM-DD HH:mm:ss"
            default-time="23:59:59"
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
      <div class="section-title-row section-title-row--content">
        <div>
          <div class="section-title-row__title">友链列表</div>
          <div class="section-title-row__desc">支持批量时间调整、禁用控制和前台展示内容维护。</div>
        </div>
        <div class="section-title-row__meta">已选 {{ selection.length }} 项</div>
      </div>
      <div class="operation_btn mb5">
        <!-- 操作 -->
        <div>
          <div class="action-cluster">
            <span class="action-cluster__title">内容维护</span>
            <el-button type="primary" @click="add()">添加</el-button>
            <el-button title="修改时间" @click="selectOpen('datetime')">时间</el-button>
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
        <el-table-column prop="unique" label="标识" min-width="80" show-overflow-tooltip />
        <el-table-column prop="image_id" label="图片" min-width="62">
          <template #default="scope">
            <FileImage :file-url="scope.row.image_url" lazy />
          </template>
        </el-table-column>
        <el-table-column prop="name" label="名称" min-width="120" show-overflow-tooltip>
          <template #default="scope">
            <span :style="{ color: scope.row.name_color }">{{ scope.row.name }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="url" label="链接" min-width="175" show-overflow-tooltip />
        <el-table-column prop="desc" label="描述" width="175" show-overflow-tooltip />
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
      <el-scrollbar native :max-height="height - 30">
        <el-form ref="ref" :model="model" :rules="rules" label-width="100px">
          <div class="dialog-review-note">
            <div class="dialog-review-note__title">提交前复核</div>
            <div class="dialog-review-note__tags">
              <span class="dialog-review-note__tag">运行环境：{{ runtimeEnvInfo.label }}</span>
              <span class="dialog-review-note__tag">标识：{{ model.unique || '未填写' }}</span>
              <span class="dialog-review-note__tag">名称：{{ model.name || '未填写' }}</span>
            </div>
            <div class="dialog-review-note__risk">{{ linkFormRisk }}</div>
          </div>
          <el-form-item label="标识" prop="unique">
            <el-input v-model="model.unique" placeholder="请输入标识（唯一）" clearable />
          </el-form-item>
          <el-form-item label="图片" prop="image_url">
            <FileImage v-model="model.image_id" :file-url="model.image_url" :height="100" upload />
          </el-form-item>
          <el-form-item label="名称" prop="name">
            <el-col :span="18">
              <el-input v-model="model.name" placeholder="请输入名称" clearable />
            </el-col>
            <el-col :span="3" style="text-align: center">名称颜色</el-col>
            <el-col :span="3">
              <el-color-picker v-model="model.name_color" />
            </el-col>
          </el-form-item>
          <el-form-item label="链接" prop="url">
            <el-col :span="18">
              <el-input v-model="model.url" placeholder="请输入链接" clearable />
            </el-col>
            <el-col :span="3" style="text-align: center">下划线</el-col>
            <el-col :span="3">
              <el-switch v-model="model.underline" :active-value="1" :inactive-value="0" />
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
              :default-time="new Date(2024, 1, 1, 0, 0, 0)"
              placeholder="开始时间"
            />
          </el-form-item>
          <el-form-item label="结束时间" prop="end_time">
            <el-date-picker
              v-model="model.end_time"
              type="datetime"
              value-format="YYYY-MM-DD HH:mm:ss"
              :default-time="new Date(2024, 1, 1, 23, 59, 59)"
              placeholder="结束时间"
            />
          </el-form-item>
          <el-form-item label="备注" prop="remark">
            <el-input v-model="model.remark" placeholder="" />
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
import { getAdminRuntimeHint, resolveAdminRuntimeEnv } from '@/utils/runtime-env'
import { list, info, add, edit, dele, datetime, disable } from '@/api/setting/link'

export default {
  name: 'SettingLink',
  components: { Pagination },
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
      if (source === 'setting-notice') return '来自公告管理'
      if (source === 'system-setting') return '来自系统设置中心'
      return ''
    },
    entryContextVisible() {
      return Boolean(this.entrySourceLabel)
    },
    entryContextTitle() {
      if (this.entrySourceLabel === '来自控制台总览') return '当前从控制台进入友链管理'
      if (this.entrySourceLabel === '来自公告管理') return '当前从公告管理进入友链管理'
      if (this.entrySourceLabel === '来自系统设置中心') return '当前从系统设置中心进入友链管理'
      return '当前为外部入口承接视角'
    },
    entryContextDesc() {
      if (this.entrySourceLabel === '来自控制台总览') {
        return '这类进入通常是为了核首页外链入口。建议先确认启用状态和展示周期，再去 H5 首页点一次真实入口。'
      }
      if (this.entrySourceLabel === '来自公告管理') {
        return '这类进入通常是为了核首页文案和外链入口是否一致，建议先处理友链状态，再回公告页复核首页口径。'
      }
      if (this.entrySourceLabel === '来自系统设置中心') {
        return '这类进入通常是为了继续核对前台展示链路，建议先确认友链入口是否仍需保留。'
      }
      return ''
    },
    entryContextPrimaryLabel() {
      if (this.entrySourceLabel === '来自控制台总览') return '去 H5 首页复核'
      if (this.entrySourceLabel === '来自公告管理') return '回公告管理'
      return '去轮播管理'
    },
    statusFilterLabel() {
      return this.query.is_disable === undefined
        ? '全部'
        : this.query.is_disable === 1
        ? '禁用'
        : '启用'
    },
    linkActionSummary() {
      const map = {
        datetime: '批量改时间',
        disable: '批量改状态',
        dele: '批量删除'
      }
      return map[this.selectType] || '友链维护'
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
        tags.push(`检索：${this.query.search_field || 'name'} = ${this.query.search_value}`)
      }
      return tags
    },
    linkSubmitRisk() {
      if (this.selectType === 'dele') {
        return this.runtimeEnvInfo.isProd
          ? '正式环境下删除友链会直接影响前台入口，请先确认当前选中链接和展示周期。'
          : '当前环境适合验证友链删除和结果回显，不要把测试删除结果当作正式运营结果。'
      }
      if (!this.selection.length) {
        return this.runtimeEnvInfo.isProd
          ? '正式环境下批量操作会直接影响线上友链，请先选择记录后再提交。'
          : '当前环境建议先选择要验证的友链，再执行批量操作。'
      }
      return this.runtimeEnvInfo.isProd
        ? `正式环境下本次会直接影响 ${this.selection.length} 项友链，请先复核名称、链接和状态。`
        : `当前环境可用于验证 ${this.selection.length} 项友链的批量操作与结果回显。`
    },
    linkFormRisk() {
      if (!this.model.unique) {
        return '当前标识未填写，建议先补齐唯一标识后再提交。'
      }
      if (!this.model.name) {
        return '当前名称未填写，提交会被表单校验拦截。'
      }
      if (!this.model.url) {
        return '当前链接未填写，提交前需要确认目标地址。'
      }
      if (
        this.model.start_time &&
        this.model.end_time &&
        this.model.start_time > this.model.end_time
      ) {
        return '结束时间早于开始时间，提交前需要调整展示周期。'
      }
      return this.runtimeEnvInfo.isProd
        ? '正式环境下提交会直接影响前台友链入口，请复核名称、链接与时间范围。'
        : '当前环境适合验证友链新增和修改流程，可继续核对回显结果。'
    },
    summaryBadgeClass() {
      if (this.selection.length) {
        return 'is-active'
      }
      return this.query.is_disable === 1 ? 'is-warning' : 'is-safe'
    },
    summaryBadgeText() {
      if (this.selection.length) {
        return `待复核 ${this.selection.length} 项`
      }
      return this.query.is_disable === 1 ? '重点检查禁用友链' : '发布概览'
    },
    summaryHint() {
      if (this.selection.length) {
        return this.linkSubmitRisk
      }
      return `${this.runtimeHint} 当前共 ${this.count || 0} 条友链，可直接从下方继续维护。`
    },
    followupHint() {
      if (this.selection.length) {
        return `当前已选 ${this.selection.length} 条友链，处理完成后建议先去 H5 首页看入口和跳转，再去轮播、公告页核对曝光位与文案是否仍然一致。`
      }
      if (this.query.is_disable === 1) {
        return '当前聚焦的是禁用友链，下一步更适合先去 H5 首页看是否还有残留入口，再去轮播和公告页检查同类内容是否仍在承接。'
      }
      return '友链页主要解决外链入口和展示周期；通常处理完这里后，要先去 H5 首页看入口和跳转，再去轮播、公告页确认首页口径一致。'
    },
    followupTags() {
      return [
        `环境：${this.runtimeEnvInfo.label}`,
        `状态：${this.statusFilterLabel}`,
        `已选：${this.selection.length} 项`,
        `总量：${this.count || 0} 条`
      ]
    },
    linkFocusLabel() {
      if (this.selection.length) {
        return '先复核批量修改'
      }
      if (this.query.is_disable === 1) {
        return '先看下线入口'
      }
      if (this.query.search_value) {
        return '先看单条跳转'
      }
      return '先看整体曝光位'
    },
    linkGuideCards() {
      return [
        {
          title: '第一步先看这条友链还该不该展示',
          desc:
            this.query.is_disable === 1
              ? '当前聚焦禁用友链，重点是核对这些入口是不是已经不需要在前台露出。'
              : '友链最核心的判断不是文案，而是这条外链当前还要不要继续给用户看。',
          action: this.query.is_disable === 1 ? '下线入口优先核展示必要性。' : '先看是否还该曝光。'
        },
        {
          title: '第二步再决定改时间还是改状态',
          desc: this.selection.length
            ? `当前已选 ${this.selection.length} 条友链，提交前先确认这是临时调整展示周期，还是彻底下线。`
            : '展示周期适合做活动排期，禁用更像临时下线，删除通常放在最后。',
          action: this.selection.length ? '先分清排期还是下线。' : '删除动作尽量最后做。'
        },
        {
          title: '第三步回首页入口继续核承接',
          desc: '友链处理完后，最好继续去轮播、公告和内容大厅页看首页入口和落地页口径是不是一致。',
          action: '入口页负责曝光，友链页负责跳转。'
        }
      ]
    }
  },
  data() {
    return {
      name: '友链',
      height: 680,
      loading: false,
      idkey: 'link_id',
      exps: [{ exp: 'like', name: '包含' }],
      query: {
        page: 1,
        limit: getPageLimit(),
        search_field: 'name',
        search_exp: 'like',
        date_field: 'create_time',
        is_disable: undefined
      },
      data: [],
      count: 0,
      dialog: false,
      dialogTitle: '',
      model: {
        link_id: '',
        unique: '',
        image_id: 0,
        image_url: '',
        name: '',
        name_color: '',
        url: '',
        desc: '',
        start_time: '',
        end_time: '2099-12-31 23:59:59',
        underline: 0,
        remark: '',
        sort: 250
      },
      rules: {
        name: [{ required: true, message: '请输入名称', trigger: 'blur' }],
        start_time: [{ required: true, message: '请输入开始时间', trigger: 'blur' }],
        end_time: [{ required: true, message: '请输入结束时间', trigger: 'blur' }]
      },
      types: [],
      selection: [],
      selectIds: '',
      selectTitle: '操作',
      selectDialog: false,
      selectType: '',
      start_time: '',
      end_time: '',
      is_disable: 0,
      recentActionSummary: ''
    }
  },
  created() {
    this.height = screenHeight()
    this.list()
  },
  methods: {
    handleEntryContextPrimary() {
      if (this.entrySourceLabel === '来自控制台总览') {
        this.openH5Home()
        return
      }
      if (this.entrySourceLabel === '来自公告管理') {
        this.goToPage('/setting/notice')
        return
      }
      this.goToPage('/setting/carousel')
    },
    goToEntryContextBack() {
      if (this.entrySourceLabel === '来自控制台总览') {
        this.$router.push({ path: '/dashboard', query: { from: 'setting-link' } })
        return
      }
      if (this.entrySourceLabel === '来自公告管理') {
        this.$router.push({ path: '/setting/notice', query: { from: 'setting-link' } })
        return
      }
      if (this.entrySourceLabel === '来自系统设置中心') {
        this.$router.push({ path: '/system/setting', query: { from: 'setting-link' } })
      }
    },
    setRecentActionSummary(action, extra = '') {
      this.recentActionSummary = `已执行${action}，影响 ${this.selection.length || 0} 条友链${
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
                this.setRecentActionSummary('修改友链', `名称：${this.model.name || '未填写'}`)
                ElMessage.success(res.msg)
                this.$nextTick(() => {
                  ElMessage.info('友链已提交，请继续去 H5 首页、轮播管理和公告管理各核对一次。')
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
                this.setRecentActionSummary('新增友链', `名称：${this.model.name || '未填写'}`)
                ElMessage.success(res.msg)
                this.$nextTick(() => {
                  ElMessage.info('友链已提交，请继续去 H5 首页、轮播管理和公告管理各核对一次。')
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
        if (selectType === 'dele') {
          this.selectTitle = this.name + '删除'
        } else if (selectType === 'disable') {
          this.selectTitle = this.name + '是否禁用'
        } else if (selectType === 'datetime') {
          this.selectTitle = this.name + '时间范围'
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
        if (selectType === 'dele') {
          this.dele(this.selection)
        } else if (selectType === 'disable') {
          this.disable(this.selection, true)
        } else if (selectType === 'datetime') {
          this.datetime(this.selection)
        }
        this.selectDialog = false
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
            this.setRecentActionSummary('批量删除友链')
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
        this.loading = true
        datetime({
          ids: this.selectGetIds(row),
          start_time: this.start_time,
          end_time: this.end_time
        })
          .then((res) => {
            this.list()
            this.setRecentActionSummary(
              '批量调整友链时间',
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
              '批量调整友链状态',
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
        `确认要${value === 1 ? '禁用' : '启用'}友链「${
          row.name || row.unique || row[this.idkey]
        }」吗？`,
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
    goToPage(path) {
      this.$router.push({
        path,
        query: {
          from: 'setting-link'
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

.link-summary-bar {
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

.link-summary-bar__chips {
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

.link-summary-bar__hint {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 240px;
  padding: 12px 14px;
  border-radius: 12px;
  background: #eef4ff;
  color: #1d4ed8;
}

.link-summary-bar__hint.is-safe {
  background: #eaf8ef;
  color: #15803d;
}

.link-summary-bar__hint.is-warning {
  background: #fff5e8;
  color: #b45309;
}

.link-summary-bar__hint.is-active {
  background: #e8f0ff;
  color: #1d4ed8;
}

.link-summary-bar__hint-title {
  font-size: 12px;
  font-weight: 700;
}

.link-summary-bar__hint-text {
  font-size: 12px;
  line-height: 1.7;
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
  .section-title-row,
  .plain-guide__header,
  .followup-panel,
  .link-summary-bar {
    flex-direction: column;
  }

  .section-title-row__meta {
    white-space: normal;
  }
}
</style>
