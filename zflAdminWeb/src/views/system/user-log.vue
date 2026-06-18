<template>
  <div class="app-container">
    <el-card class="app-head head-pb20">
      <div class="page-hero">
        <div class="page-hero__content">
          <div class="page-hero__title">用户日志</div>
          <div class="page-hero__desc">
            用于检索后台操作轨迹、核对请求上下文，并在确认范围后执行清理。
          </div>
        </div>
        <div class="page-hero__badge" :class="`is-${runtimeEnvInfo.key}`">
          {{ runtimeEnvInfo.label }}
        </div>
      </div>
      <div class="section-title-row section-title-row--compact">
        <div>
          <div class="section-title-row__title">筛选</div>
          <div class="section-title-row__desc">
            按用户、菜单、日志类型和请求信息快速定位后台操作日志。
          </div>
        </div>
        <div class="section-title-row__meta">
          当前检索：{{ query.search_field || 'request_ip' }}
        </div>
      </div>
      <div v-if="entryContextVisible" class="entry-context-banner">
        <div class="entry-context-banner__main">
          <div class="entry-context-banner__eyebrow">外部入口承接</div>
          <div class="entry-context-banner__title">{{ entryContextTitle }}</div>
          <div class="entry-context-banner__desc">{{ entryContextDesc }}</div>
        </div>
        <div class="entry-context-banner__actions">
          <el-button type="primary" size="small" @click="handleEntryContextPrimary">
            {{ entryContextPrimaryLabel }}
          </el-button>
          <el-button size="small" @click="clearEntryContext">恢复普通视角</el-button>
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
            <el-form-item label="用户：" prop="user_id">
              <el-select
                v-model="query.user_id"
                placeholder="用户"
                @change="search()"
                clearable
                filterable
                collapse-tags
                multiple
                allow-create
              >
                <el-option
                  v-for="item in userData"
                  :key="item.user_id"
                  :value="item.user_id"
                  :label="item.nickname"
                >
                  {{ item.nickname }} ({{ item.username }})
                </el-option>
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="菜单：" prop="menu_id">
              <el-cascader
                v-model="query.menu_id"
                placeholder="菜单"
                :options="menuData"
                :props="menuProps"
                @change="search()"
                clearable
                filterable
                collapse-tags
              />
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="日志类型：" prop="log_type">
              <el-select
                v-model="query.log_type"
                placeholder="日志类型"
                @change="search()"
                clearable
                filterable
              >
                <el-option
                  v-for="(item, index) in logTypes"
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
                  <el-option value="request_ip" label="请求IP" />
                  <el-option value="request_region" label="请求地区" />
                  <el-option value="request_isp" label="请求ISP" />
                  <el-option value="response_code" label="返回码" />
                  <el-option value="response_msg" label="返回描述" />
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
      <el-form ref="selectRef" label-width="120px">
        <div v-if="selection.length" class="select-review-panel">
          <div class="select-review-panel__title">提交前复核</div>
          <div class="select-review-panel__tags">
            <span v-for="item in selectReviewItems" :key="item">{{ item }}</span>
          </div>
          <div class="select-review-panel__hint">{{ selectRiskHint }}</div>
        </div>
        <el-form-item v-if="selectType === 'dele'">
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
      <div class="summary-bar">
        <div class="summary-bar__main">
          <div class="summary-bar__item">
            <span class="summary-bar__label">日志总量</span>
            <strong>{{ count }}</strong>
          </div>
          <div class="summary-bar__item">
            <span class="summary-bar__label">已选</span>
            <strong>{{ selection.length }}</strong>
          </div>
          <div class="summary-bar__item">
            <span class="summary-bar__label">当前日志类型/筛选</span>
            <strong>{{ query.log_type === undefined ? '全部日志' : logTypes[query.log_type] || query.log_type }}</strong>
            <span class="summary-bar__text">
              {{ activeFilterTags.length ? activeFilterTags.join(' / ') : '默认条件：全部日志' }}
            </span>
          </div>
          <div class="summary-bar__item">
            <span class="summary-bar__label">数据模式</span>
            <strong>{{ runtimeEnvInfo.dataMode }}</strong>
            <span class="summary-bar__text">{{ runtimeHint }}</span>
          </div>
          <div class="summary-bar__item summary-bar__item--risk">
            <span class="summary-bar__label">风险提示</span>
            <strong>{{ logFollowupBadgeText }}</strong>
            <span class="summary-bar__text">{{ selectRiskHint }}</span>
          </div>
        </div>
        <div v-if="recentActionSummary" class="summary-bar__foot">
          最近操作：{{ recentActionSummary }}
        </div>
      </div>
      <div class="plain-guide">
        <div class="plain-guide__header">
          <div>
            <div class="plain-guide__title">这页建议先这样看</div>
            <div class="plain-guide__desc">
              用户日志页主要不是让你直接删记录，而是先看“谁做了什么、从哪个菜单进来、结果是成功还是异常”。先盯异常返回，再锁定用户和菜单，确认清楚后再做清理。
            </div>
          </div>
          <span class="plain-guide__badge">{{ logFocusLabel }}</span>
        </div>
        <div class="plain-guide__grid">
          <div v-for="item in logGuideCards" :key="item.title" class="plain-guide-card">
            <div class="plain-guide-card__title">{{ item.title }}</div>
            <div class="plain-guide-card__desc">{{ item.desc }}</div>
            <div class="plain-guide-card__action">{{ item.action }}</div>
          </div>
        </div>
      </div>
      <div class="section-title-row section-title-row--content">
        <div>
          <div class="section-title-row__title">后台操作日志</div>
          <div class="section-title-row__desc">
            支持查看后台菜单访问轨迹、请求上下文和异常返回结果，并做清理维护。
          </div>
        </div>
        <div class="section-title-row__meta">已选 {{ selection.length }} 项</div>
      </div>
      <div class="followup-strip">
        <button type="button" class="followup-card" @click="goToCurrentUser">
          <span class="followup-card__title">去后台用户处理</span>
          <span class="followup-card__desc">
            {{ currentUserActionText }}
          </span>
        </button>
        <button type="button" class="followup-card" @click="goToCurrentMenu">
          <span class="followup-card__title">去菜单管理定位</span>
          <span class="followup-card__desc">
            {{ currentMenuActionText }}
          </span>
        </button>
        <button type="button" class="followup-card" @click="goToSystemApiDoc">
          <span class="followup-card__title">去接口文档核对</span>
          <span class="followup-card__desc">
            针对返回码异常、请求参数异常或菜单权限疑问，继续去接口文档确认接口约定。
          </span>
        </button>
      </div>
      <div class="operation_btn mb5">
        <!-- 操作 -->
        <div>
          <div class="action-cluster">
            <span class="action-cluster__title">批量处理</span>
            <el-button title="删除选择" @click="selectOpen('dele')">删除</el-button>
          </div>
          <div class="action-cluster">
            <span class="action-cluster__title">清理维护</span>
            <el-button title="删除查询结果" @click="clear()">清空</el-button>
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
        <el-table-column prop="user_id" label="用户ID" min-width="100" show-overflow-tooltip />
        <el-table-column prop="nickname" label="用户昵称" min-width="100" show-overflow-tooltip />
        <el-table-column prop="username" label="用户账号" min-width="100" show-overflow-tooltip />
        <el-table-column prop="menu_id" label="菜单ID" min-width="100" show-overflow-tooltip />
        <el-table-column prop="menu_name" label="菜单名称" min-width="130" show-overflow-tooltip />
        <el-table-column prop="menu_url" label="菜单链接" min-width="180" show-overflow-tooltip />
        <el-table-column prop="request_ip" label="请求IP" min-width="130" show-overflow-tooltip />
        <el-table-column
          prop="request_region"
          label="请求地区"
          min-width="150"
          show-overflow-tooltip
        />
        <el-table-column prop="request_isp" label="请求ISP" min-width="105" show-overflow-tooltip />
        <el-table-column prop="response_code" label="返回码" min-width="80" show-overflow-tooltip />
        <el-table-column
          prop="response_msg"
          label="返回描述"
          min-width="120"
          show-overflow-tooltip
        />
        <el-table-column prop="create_time" label="请求时间" width="165" sortable="custom" />
        <el-table-column label="操作" width="95">
          <template #default="scope">
            <el-link type="primary" class="mr-1" :underline="false" @click="info(scope.row)">
              详情
            </el-link>
            <el-link type="primary" :underline="false" @click="selectOpen('dele', [scope.row])">
              删除
            </el-link>
          </template>
        </el-table-column>
      </el-table>

      <!-- 详情 -->
      <el-dialog
        v-model="dialog"
        :title="dialogTitle"
        :close-on-click-modal="false"
        :close-on-press-escape="false"
        :before-close="cancel"
        top="10vh"
      >
        <el-scrollbar class="mt5" native :max-height="height - 30">
          <el-form ref="ref" :rules="rules" :model="model" label-width="110px">
            <div class="dialog-plain-guide">
              <div class="dialog-plain-guide__header">
                <div>
                  <div class="dialog-plain-guide__title">日志详情先看结果，再回到人和菜单排查</div>
                  <div class="dialog-plain-guide__desc">
                    先确认这条日志是成功还是报错，再看是谁、从哪个菜单进来、带了什么参数。日志详情最有价值的是帮助你决定下一步该去账号页、菜单页还是接口文档，而不是把字段从上到下机械读完。
                  </div>
                </div>
                <span class="dialog-plain-guide__badge">{{ logDialogFocusLabel }}</span>
              </div>
              <div class="dialog-plain-guide__grid">
                <div
                  v-for="item in logDialogGuideCards"
                  :key="item.title"
                  class="dialog-plain-guide-card"
                >
                  <div class="dialog-plain-guide-card__title">{{ item.title }}</div>
                  <div class="dialog-plain-guide-card__desc">{{ item.desc }}</div>
                  <div class="dialog-plain-guide-card__action">{{ item.action }}</div>
                </div>
              </div>
            </div>
            <el-form-item label="用户ID" prop="user_id">
              <el-input v-model="model.user_id" />
            </el-form-item>
            <el-form-item label="用户昵称" prop="nickname">
              <el-input v-model="model.nickname" />
            </el-form-item>
            <el-form-item label="用户账号" prop="username">
              <el-input v-model="model.username" />
            </el-form-item>
            <el-form-item label="菜单ID" prop="menu_id">
              <el-input v-model="model.menu_id" />
            </el-form-item>
            <el-form-item label="菜单名称" prop="menu_name">
              <el-input v-model="model.menu_name" />
            </el-form-item>
            <el-form-item label="菜单链接" prop="menu_url">
              <el-input v-model="model.menu_url" />
            </el-form-item>
            <el-form-item label="请求方式" prop="request_method">
              <el-input v-model="model.request_method" />
            </el-form-item>
            <el-form-item label="请求IP" prop="request_ip">
              <el-input v-model="model.request_ip" />
            </el-form-item>
            <el-form-item label="请求地区" prop="request_region">
              <el-input v-model="model.request_region" />
            </el-form-item>
            <el-form-item label="请求ISP" prop="request_isp">
              <el-input v-model="model.request_isp" />
            </el-form-item>
            <el-form-item label="请求时间" prop="create_time">
              <el-input v-model="model.create_time" />
            </el-form-item>
            <el-form-item label="返回码" prop="response_code">
              <el-input v-model="model.response_code" />
            </el-form-item>
            <el-form-item label="返回描述" prop="response_msg">
              <el-input v-model="model.response_msg" type="textarea" />
            </el-form-item>
            <el-form-item label="用户代理" prop="user_agent">
              <el-input v-model="model.user_agent" type="textarea" autosize />
            </el-form-item>
            <el-form-item label="请求参数" prop="request_param">
              <el-col>
                <el-button text type="primary" title="复制参数" @click="requestParamCopy()">
                  <svg-icon icon-class="copy-document" />
                </el-button>
              </el-col>
              <pre ref="requestParamRef">{{ model.request_param }}</pre>
            </el-form-item>
          </el-form>
        </el-scrollbar>
        <template #footer>
          <div class="dialog-followup-actions">
            <el-button v-if="model.user_id" @click="goToCurrentUser(true)">去后台用户</el-button>
            <el-button v-if="model.menu_id || model.menu_url" @click="goToCurrentMenu(true)">
              去菜单管理
            </el-button>
            <el-button @click="goToSystemApiDoc(true)">接口文档</el-button>
          </div>
          <el-button :loading="loading" @click="cancel">取消</el-button>
          <el-button :loading="loading" type="primary" @click="submit">确定</el-button>
        </template>
      </el-dialog>
    </el-card>
  </div>
</template>

<script>
import screenHeight from '@/utils/screen-height'
import Pagination from '@/components/Pagination/index.vue'
import clip from '@/utils/clipboard'
import { arrayColumn } from '@/utils/index'
import { getPageLimit } from '@/utils/settings'
import { list, info, dele, clear } from '@/api/system/user-log'
import { shortcuts } from '@/utils/getDate.js'
import { getAdminRuntimeHint, resolveAdminRuntimeEnv } from '@/utils/runtime-env'

export default {
  name: 'SystemUserLog',
  components: { Pagination },
  data() {
    return {
      name: '用户日志',
      height: 680,
      loading: false,
      idkey: 'log_id',
      exps: [{ exp: 'like', name: '包含' }],
      query: {
        page: 1,
        limit: getPageLimit(),
        search_field: 'request_ip',
        search_exp: 'in',
        search_value: '',
        date_field: 'create_time',
        user_id: undefined,
        menu_id: undefined,
        log_type: undefined
      },
      data: [],
      count: 0,
      dialog: false,
      dialogTitle: '',
      model: {},
      rules: {},
      userData: [],
      menuData: [],
      menuProps: {
        checkStrictly: true,
        value: 'menu_id',
        label: 'menu_name',
        multiple: true,
        emitPath: false
      },
      logTypes: [],
      selection: [],
      selectIds: '',
      selectTitle: '操作',
      selectDialog: false,
      selectType: '',
      shortcuts: shortcuts(),
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
    currentErrorCount() {
      return Array.isArray(this.data)
        ? this.data.filter((item) => Number(item.response_code) >= 400).length
        : 0
    },
    currentSuccessCount() {
      return Array.isArray(this.data)
        ? this.data.filter(
            (item) => Number(item.response_code) > 0 && Number(item.response_code) < 400
          ).length
        : 0
    },
    activeFilterTags() {
      const tags = []
      if (this.query.date_value?.length === 2) {
        tags.push(`时间范围：${this.query.date_value[0]} 至 ${this.query.date_value[1]}`)
      }
      if (this.query.user_id?.length) {
        tags.push(`用户：${this.query.user_id.length} 个`)
      }
      if (this.query.menu_id?.length) {
        tags.push(`菜单：${this.query.menu_id.length} 项`)
      }
      if (
        this.query.log_type !== undefined &&
        this.query.log_type !== null &&
        this.query.log_type !== ''
      ) {
        tags.push(`日志类型：${this.logTypes[this.query.log_type] || this.query.log_type}`)
      }
      if (this.query.search_value) {
        tags.push(`检索：${this.query.search_field || this.idkey}=${this.query.search_value}`)
      }
      return tags
    },
    logFollowupBadgeText() {
      if (this.selection.length > 0) {
        return '已选待处理'
      }
      if (this.currentErrorCount > 0) {
        return '存在异常返回'
      }
      return '可例行巡检'
    },
    entrySourceLabel() {
      const source = String(this.$route?.query?.from || '')
      if (source === 'system-setting') {
        return '来自系统设置'
      }
      if (source === 'system-role') {
        return '来自角色管理'
      }
      if (source === 'system-menu') {
        return '来自菜单管理'
      }
      if (source === 'system-post') {
        return '来自职位管理'
      }
      if (source === 'system-dept') {
        return '来自部门管理'
      }
      if (source === 'system-notice') {
        return '来自系统公告'
      }
      if (source === 'system-apidoc') {
        return '来自接口文档'
      }
      if (source === 'dashboard') {
        return '来自控制台总览'
      }
      if (source === 'platform-analytics') {
        return '来自平台分析'
      }
      return ''
    },
    entryContextFocus() {
      return String(this.$route?.query?.focus || '')
    },
    entryContextVisible() {
      return Boolean(this.entrySourceLabel || this.entryContextFocus)
    },
    entryContextTitle() {
      if (this.entryContextFocus === 'ops') {
        return '当前从运营动作排查入口进入用户日志'
      }
      if (this.entryContextFocus === 'alerts') {
        return '当前从异常提醒入口进入用户日志'
      }
      if (this.entrySourceLabel) {
        return `${this.entrySourceLabel}，当前页已切到日志排查视角`
      }
      return '当前为外部入口承接视角'
    },
    entryContextDesc() {
      if (this.entryContextFocus === 'ops') {
        return '建议先看返回码异常和高频操作用户，再顺着菜单入口往下查，不要一上来就批量删日志。'
      }
      if (this.entryContextFocus === 'alerts') {
        return '建议先锁定异常返回日志，再看是谁、从哪个菜单触发、参数是什么，确认清楚后再决定去用户、菜单还是接口文档继续排查。'
      }
      if (this.entrySourceLabel === '来自系统设置') {
        return '这类进入通常是为了确认某次配置调整后，后台操作和接口返回有没有异常。建议先看异常返回，再回系统设置继续复核对应标签。'
      }
      if (this.entrySourceLabel === '来自接口文档') {
        return '这类进入通常是为了把接口说明落到真实请求日志上复核。建议先看返回码和请求参数，再回接口文档继续联调。'
      }
      return '当前日志页承担的是排查和定位，不是直接清理。建议先确认范围，再做删除或清空。'
    },
    entryContextPrimaryLabel() {
      if (this.entryContextFocus === 'ops' || this.entryContextFocus === 'alerts') {
        return '只看异常返回'
      }
      if (this.entrySourceLabel === '来自系统设置') {
        return '回系统设置'
      }
      if (this.entrySourceLabel === '来自接口文档') {
        return '回接口文档'
      }
      if (this.entrySourceLabel === '来自角色管理') {
        return '回角色管理'
      }
      if (this.entrySourceLabel === '来自菜单管理') {
        return '回菜单管理'
      }
      if (this.entrySourceLabel === '来自职位管理') {
        return '回职位管理'
      }
      if (this.entrySourceLabel === '来自部门管理') {
        return '回部门管理'
      }
      if (this.entrySourceLabel === '来自系统公告') {
        return '回系统公告'
      }
      return '去接口文档'
    },
    logFocusLabel() {
      if (this.entryContextFocus === 'ops' || this.entryContextFocus === 'alerts') {
        return '先看异常返回'
      }
      if (this.selection.length > 0) {
        return '先确认这批日志要不要动'
      }
      if (this.currentErrorCount > 0) {
        return '先看异常返回'
      }
      return '先看操作轨迹'
    },
    logGuideCards() {
      if (this.entryContextFocus === 'ops' || this.entryContextFocus === 'alerts') {
        return [
          {
            title: '第一步：先锁定异常返回日志',
            desc: '当前更适合先看异常返回，不要先删日志。返回码、返回描述和请求参数会决定你接下来去查账号、菜单还是接口。',
            action:
              this.currentErrorCount > 0
                ? `当前页异常返回 ${this.currentErrorCount} 条，建议优先打开详情。`
                : '当前页暂未发现明显异常返回，可继续抽查最近操作轨迹。'
          },
          {
            title: '第二步：再看是谁从哪个入口触发',
            desc: '后台问题常常不是系统整体故障，而是某个账号、某个菜单或某条操作链路承接出了偏差。',
            action:
              this.activeFilterTags.length > 0
                ? `当前已加 ${this.activeFilterTags.length} 个筛选条件。`
                : '可以先按用户、菜单和日志类型继续缩小范围。'
          },
          {
            title: '第三步：最后决定去哪个页面继续处理',
            desc: '日志页负责定位问题，不负责把所有问题处理完。通常下一步会去后台用户、菜单管理或接口文档页继续排查。',
            action: '页内下方已给出三个后续入口，可直接继续。'
          }
        ]
      }
      return [
        {
          title: '第一步：先看有没有异常返回',
          desc: '如果返回码异常，这页最有价值的不是数量，而是先定位哪类菜单、哪类账号在报错。',
          action:
            this.currentErrorCount > 0
              ? `当前页异常返回 ${this.currentErrorCount} 条，建议优先点详情看参数和返回信息。`
              : '当前页没有明显异常返回，可以继续做例行巡检。'
        },
        {
          title: '第二步：再锁定具体用户和菜单',
          desc: '很多问题不是系统整体坏，而是某个账号权限、某个菜单状态或某条操作链路出问题。',
          action:
            this.activeFilterTags.length > 0
              ? `当前已加 ${this.activeFilterTags.length} 个筛选条件。`
              : '可以先按用户、菜单、日志类型加筛选，把范围缩小。'
        },
        {
          title: '第三步：最后再决定删不删',
          desc: '日志删除更像清理动作，不是排查动作。先确认已经拿到需要的用户、菜单和返回信息，再执行删除或清空。',
          action:
            this.selection.length > 0
              ? `当前已选 ${this.selection.length} 条，提交前建议再看一次详情。`
              : '未勾选日志时，先不要急着做删除。'
        }
      ]
    },
    selectReviewItems() {
      return [
        `已选日志：${this.selection.length} 条`,
        `当前异常：${this.currentErrorCount} 条`,
        `当前成功：${this.currentSuccessCount} 条`,
        `当前检索：${this.query.search_field || this.idkey}`,
        `时间范围：${
          this.query.date_value?.length === 2
            ? `${this.query.date_value[0]} 至 ${this.query.date_value[1]}`
            : '未限制'
        }`
      ]
    },
    selectRiskHint() {
      if (this.selectType === 'dele') {
        return '删除后当前日志记录将不可恢复，建议先在详情页复制请求参数，再执行删除。'
      }
      return '请确认当前筛选范围与勾选记录一致，避免误清理跨用户、跨菜单的操作日志。'
    },
    currentUserActionText() {
      const currentUserId = this.model.user_id || this.selection[0]?.user_id || this.query.user_id?.[0]
      if (currentUserId) {
        return `把当前日志定位到后台用户 ${currentUserId}，继续看账号状态、角色和组织归属。`
      }
      return '当前未锁定具体后台用户，可先在日志列表勾选或打开详情，再继续去账号治理页。'
    },
    currentMenuActionText() {
      const currentMenu = this.model.menu_name || this.selection[0]?.menu_name || ''
      if (currentMenu) {
        return `把当前日志定位到菜单「${currentMenu}」，继续核对菜单权限、隐藏状态和路由承接。`
      }
      return '当前未锁定具体菜单，可先在日志列表勾选或打开详情，再继续去菜单治理页。'
    },
    logDialogFocusLabel() {
      if (Number(this.model.response_code) >= 400) {
        return '优先看异常原因'
      }
      if (this.model.menu_name || this.model.menu_url) {
        return '优先看菜单入口承接'
      }
      return '先看用户和参数'
    },
    logDialogGuideCards() {
      return [
        {
          title: '第一步：先看这次请求结果是不是异常',
          desc:
            '返回码和返回描述决定你是要继续排权限、排参数，还是只是做普通巡检。先盯结果，别一开始就埋进原始参数。',
          action: `返回码：${this.model.response_code || '未记录'}；结果：${this.model.response_msg || '暂无描述'}`
        },
        {
          title: '第二步：再锁定是谁从哪个入口触发',
          desc:
            '很多问题不是系统整体坏，而是某个后台用户、某个菜单入口或某段流程承接有偏差。把人和入口锁准，排查会快很多。',
          action: `用户：${this.model.username || this.model.user_id || '未记录'}；菜单：${this.model.menu_name || this.model.menu_url || '未记录'}`
        },
        {
          title: '第三步：最后再看参数并决定去哪处理',
          desc:
            '参数主要用于确认请求上下文。看完后通常要继续去后台用户、菜单管理或接口文档，而不是停留在日志详情里。',
          action:
            this.model.request_param
              ? '当前已有请求参数，可复制后继续到对应页面复核。'
              : '当前没有完整请求参数，建议结合用户和菜单字段继续排查。'
        }
      ]
    }
  },
  created() {
    this.height = screenHeight()
    this.applyExternalEntryQuery()
    this.list()
  },
  watch: {
    '$route.fullPath'(nextPath, prevPath) {
      if (nextPath === prevPath) {
        return
      }
      this.applyExternalEntryQuery()
      this.list()
    }
  },
  methods: {
    parseRouteNumber(value) {
      if (value === undefined || value === null || value === '') {
        return undefined
      }
      const parsed = Number(value)
      return Number.isNaN(parsed) ? undefined : parsed
    },
    parseRouteArray(value) {
      if (Array.isArray(value)) {
        return value
          .map((item) => this.parseRouteNumber(item))
          .filter((item) => item !== undefined)
      }
      if (value === undefined || value === null || value === '') {
        return undefined
      }
      return String(value)
        .split(',')
        .map((item) => this.parseRouteNumber(item.trim()))
        .filter((item) => item !== undefined)
    },
    applyExternalEntryQuery() {
      const defaultQuery = this.$options.data().query
      const routeQuery = this.$route?.query || {}
      const nextQuery = {
        ...defaultQuery,
        limit: this.query?.limit || defaultQuery.limit
      }

      if (routeQuery.search_field) {
        nextQuery.search_field = String(routeQuery.search_field)
      }
      if (routeQuery.search_exp) {
        nextQuery.search_exp = String(routeQuery.search_exp)
      }
      if (routeQuery.search_value !== undefined) {
        nextQuery.search_value = String(routeQuery.search_value || '')
      }

      const userIds = this.parseRouteArray(routeQuery.user_ids) || this.parseRouteArray(routeQuery.user_id)
      if (userIds?.length) {
        nextQuery.user_id = userIds
      }

      const menuIds = this.parseRouteArray(routeQuery.menu_ids) || this.parseRouteArray(routeQuery.menu_id)
      if (menuIds?.length) {
        nextQuery.menu_id = menuIds
      }

      const logType = this.parseRouteNumber(routeQuery.log_type)
      if (logType !== undefined) {
        nextQuery.log_type = logType
      }

      if (this.entryContextFocus === 'ops' || this.entryContextFocus === 'alerts') {
        nextQuery.log_type = nextQuery.log_type ?? 0
      }

      if (!nextQuery.search_value) {
        nextQuery.search_value = String(
          routeQuery.username ||
            routeQuery.nickname ||
            routeQuery.menu_name ||
            routeQuery.dept_name ||
            routeQuery.post_name ||
            routeQuery.role_name ||
            routeQuery.notice_title ||
            ''
        )
      }

      this.query = nextQuery
      this.setRecentActionSummary(`外部入口已接入：${this.entrySourceLabel || '日志排查入口'}`)
    },
    setRecentActionSummary(summary) {
      this.recentActionSummary = summary
    },
    // 列表
    list() {
      this.loading = true
      list(this.query)
        .then((res) => {
          this.data = res.data.list
          this.count = res.data.count
          this.userData = res.data.user
          this.menuData = res.data.menu
          this.logTypes = res.data.log_types
          this.exps = res.data.exps
          this.setRecentActionSummary(
            `日志列表已刷新，共 ${res.data.count || 0} 条，当前页异常返回 ${
              this.currentErrorCount
            } 条。`
          )
          this.loading = false
        })
        .catch(() => {
          this.loading = false
        })
    },
    // 详情
    info(row) {
      this.dialog = true
      this.dialogTitle = this.name + '详情：' + row[this.idkey]
      this.setRecentActionSummary(
        `打开日志详情：${row[this.idkey]}，建议核对请求参数、返回码和菜单名称。`
      )
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
      this.dialog = false
      this.reset()
    },
    // 重置
    reset(row) {
      if (row) {
        this.model = row
      } else {
        this.model = this.$options.data().model
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
        }
        this.selectDialog = false
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
            this.setRecentActionSummary(
              `已删除 ${row.length || 0} 条日志记录，请继续观察当前筛选结果是否已清干净。`
            )
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.loading = false
          })
      }
    },
    // 清空
    clear() {
      ElMessageBox.confirm('确定要清空' + this.name + '(查询结果或所有)吗？', '提示', {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning'
      })
        .then(() => {
          clear(this.query)
            .then((res) => {
              this.list()
              this.setRecentActionSummary(
                `已清空 ${res.data.count || 0} 条日志记录，建议立即复查当前筛选条件下是否仍有残留。`
              )
              ElMessage.success('清空' + this.name + '记录 ' + res.data.count + ' 条')
            })
            .catch(() => {})
        })
        .catch(() => {})
    },
    // 复制
    copy(text) {
      clip(text)
    },
    // 参数复制
    requestParamCopy() {
      const text = this.$refs.requestParamRef
      this.copy(text.textContent)
      this.setRecentActionSummary('已复制请求参数，可用于异常排查或提交给后端定位问题。')
    },
    clearEntryContext() {
      const query = { ...this.$route.query }
      delete query.from
      delete query.focus
      this.$router.replace({
        path: this.$route.path,
        query
      })
    },
    buildEntryRouteQuery(extraQuery = {}, nextFrom = '') {
      const query = {
        ...this.$route.query,
        ...extraQuery
      }
      if (nextFrom) {
        query.from = nextFrom
      }
      return query
    },
    handleEntryContextPrimary() {
      if (this.entryContextFocus === 'ops' || this.entryContextFocus === 'alerts') {
        this.query.page = 1
        this.query.log_type = 0
        this.setRecentActionSummary('已切换为异常返回排查视角')
        this.list()
        return
      }
      if (this.entrySourceLabel === '来自系统设置') {
        this.$router.push({
          path: '/system/setting',
          query: this.buildEntryRouteQuery(
            {
              tab: this.$route.query.setting_tab || this.$route.query.tab,
              setting_tab: this.$route.query.setting_tab || this.$route.query.tab
            },
            'system-user-log'
          )
        })
        return
      }
      if (this.entrySourceLabel === '来自接口文档') {
        this.goToSystemApiDoc()
        return
      }
      if (this.entrySourceLabel === '来自角色管理') {
        this.$router.push({
          path: '/system/role',
          query: this.buildEntryRouteQuery({}, 'system-user-log')
        })
        return
      }
      if (this.entrySourceLabel === '来自菜单管理') {
        this.$router.push({
          path: '/system/menu',
          query: this.buildEntryRouteQuery({}, 'system-user-log')
        })
        return
      }
      if (this.entrySourceLabel === '来自职位管理') {
        this.$router.push({
          path: '/system/post',
          query: this.buildEntryRouteQuery({}, 'system-user-log')
        })
        return
      }
      if (this.entrySourceLabel === '来自部门管理') {
        this.$router.push({
          path: '/system/dept',
          query: this.buildEntryRouteQuery({}, 'system-user-log')
        })
        return
      }
      if (this.entrySourceLabel === '来自系统公告') {
        this.$router.push({
          path: '/system/notice',
          query: this.buildEntryRouteQuery({}, 'system-user-log')
        })
        return
      }
      this.goToSystemApiDoc()
    },
    goToCurrentUser(closeDialog = false) {
      const row = this.model.user_id ? this.model : this.selection[0] || {}
      const userId = row.user_id || this.query.user_id?.[0]
      if (!userId) {
        ElMessage.warning('当前没有可定位的后台用户')
        return
      }
      if (closeDialog) {
        this.dialog = false
      }
      this.$router.push({
        path: '/system/user',
        query: this.buildEntryRouteQuery({
          search_field: 'user_id',
          search_exp: '=',
          search_value: String(userId)
        }, 'system-user-log')
      })
    },
    goToCurrentMenu(closeDialog = false) {
      const row = this.model.menu_id ? this.model : this.selection[0] || {}
      const menuId = row.menu_id
      const menuUrl = row.menu_url
      if (!menuId && !menuUrl) {
        ElMessage.warning('当前没有可定位的菜单')
        return
      }
      if (closeDialog) {
        this.dialog = false
      }
      this.$router.push({
        path: '/system/menu',
        query: menuId
          ? this.buildEntryRouteQuery({
              search_field: 'menu_id',
              search_exp: '=',
              search_value: String(menuId)
            }, 'system-user-log')
          : this.buildEntryRouteQuery({
              search_field: 'menu_url',
              search_exp: 'like',
              search_value: String(menuUrl)
            }, 'system-user-log')
      })
    },
    goToSystemApiDoc(closeDialog = false) {
      if (closeDialog) {
        this.dialog = false
      }
      this.$router.push({
        path: '/system/apidoc',
        query: this.buildEntryRouteQuery(
          {
            setting_tab: this.$route.query.setting_tab || this.$route.query.tab
          },
          'system-user-log'
        )
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
  border: 1px solid #d6e4ff;
  border-radius: 12px;
  background: linear-gradient(135deg, #f5f9ff 0%, #ffffff 100%);
}

.entry-context-banner__main {
  display: grid;
  gap: 6px;
}

.entry-context-banner__eyebrow {
  font-size: 12px;
  font-weight: 600;
  color: #2563eb;
}

.entry-context-banner__title {
  font-size: 15px;
  font-weight: 600;
  color: #1e293b;
}

.entry-context-banner__desc {
  font-size: 13px;
  line-height: 1.6;
  color: #475569;
}

.entry-context-banner__actions {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.page-hero,
.section-title-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
  padding: 14px 16px;
  margin-bottom: 14px;
  border: 1px solid #edf2f7;
  border-radius: 12px;
  background: linear-gradient(180deg, #fbfdff 0%, #ffffff 100%);
}

.page-hero {
  border-color: #e6ecf5;
}

.page-hero__title,
.section-title-row__title {
  font-size: 14px;
  font-weight: 600;
  color: #243b53;
}

.section-title-row--compact {
  padding: 10px 14px;
  border-style: dashed;
}

.section-title-row--compact .section-title-row__title {
  font-size: 13px;
  color: #475569;
}

.page-hero__desc,
.section-title-row__desc,
.section-title-row__meta {
  font-size: 12px;
  color: #7c8aa5;
}

.section-title-row--compact .section-title-row__meta {
  color: #94a3b8;
}

.page-hero__badge {
  display: inline-flex;
  align-items: center;
  min-height: 30px;
  padding: 0 12px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 700;
  color: #1d4ed8;
  background: #e8f0ff;
}

.page-hero__badge.is-prod {
  color: #b45309;
  background: #fff5e8;
}

.page-hero__badge.is-gray {
  color: #15803d;
  background: #eaf8ef;
}

.page-hero__badge.is-local {
  color: #1d4ed8;
  background: #e8f0ff;
}

.section-title-row--content {
  margin-top: 2px;
}

.summary-bar,
.select-review-panel {
  margin-bottom: 12px;
  padding: 14px 16px;
  border: 1px solid #e6ecf5;
  border-radius: 12px;
  background: linear-gradient(180deg, #fbfdff 0%, #ffffff 100%);
}

.summary-bar__main {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 12px;
}

.summary-bar__item {
  display: flex;
  flex-direction: column;
  gap: 6px;
  min-height: 88px;
  padding: 14px 16px;
  border: 1px solid #e6ecf5;
  border-radius: 12px;
  background: #fff;
}

.summary-bar__item strong {
  font-size: 18px;
  line-height: 1.3;
  color: #243b53;
}

.summary-bar__item--risk {
  background: linear-gradient(180deg, #fff8ef 0%, #ffffff 100%);
  border-color: #fed7aa;
}

.summary-bar__label,
.select-review-panel__title {
  font-size: 12px;
  font-weight: 700;
  color: #475569;
}

.summary-bar__text {
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.summary-bar__foot {
  margin-top: 12px;
  font-size: 12px;
  line-height: 1.7;
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
  white-space: nowrap;
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

.select-review-panel__tags {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.select-review-panel__hint {
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
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
  border: 1px solid #dbe7f5;
  border-radius: 14px;
  background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
  transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
}

.followup-card:hover {
  transform: translateY(-1px);
  border-color: #bfdbfe;
  box-shadow: 0 12px 24px rgba(37, 99, 235, 0.08);
}

.followup-card__title {
  font-size: 13px;
  font-weight: 700;
  color: #243b53;
}

.followup-card__desc {
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.dialog-followup-actions {
  display: inline-flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
  margin-right: auto;
}

.dialog-plain-guide {
  margin-bottom: 14px;
  padding: 12px 14px;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
}

.dialog-plain-guide__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.dialog-plain-guide__title {
  font-size: 13px;
  font-weight: 700;
  color: #111827;
}

.dialog-plain-guide__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #6b7280;
}

.dialog-plain-guide__badge {
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

.dialog-plain-guide__grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 10px;
  margin-top: 12px;
}

.dialog-plain-guide-card {
  padding: 12px;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  background: #fff;
}

.dialog-plain-guide-card__title {
  font-size: 13px;
  font-weight: 700;
  color: #111827;
}

.dialog-plain-guide-card__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #6b7280;
}

.dialog-plain-guide-card__action {
  margin-top: 8px;
  font-size: 12px;
  color: #4f46e5;
}

@media (max-width: 900px) {
  .page-hero,
  .section-title-row,
  .summary-bar__main {
    align-items: flex-start;
  }

  .plain-guide__header,
  .dialog-plain-guide__header,
  .page-hero,
  .section-title-row {
    flex-direction: column;
  }
}
</style>
