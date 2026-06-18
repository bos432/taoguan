<template>
  <div class="app-container">
    <el-card class="app-head">
      <div class="section-title-row">
        <div>
          <div class="section-title-row__title">会员日志</div>
          <div class="section-title-row__desc">统一处理会员访问日志筛选、异常排查和清理维护。</div>
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
          <el-button type="primary" @click="handleEntryContextPrimary">{{
            entryContextPrimaryLabel
          }}</el-button>
          <el-button @click="goToEntryContextBack">回来源页</el-button>
        </div>
      </div>
      <el-form ref="searchForm" :model="query" label-width="85px">
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
            <el-form-item label="接口：" prop="api_id">
              <el-cascader
                v-model="query.api_id"
                placeholder="接口"
                :options="apiData"
                :props="apiProps"
                clearable
                filterable
                collapse-tags
                style="width: 100%"
                @change="search()"
              />
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="日志类型：" prop="log_type">
              <el-select
                v-model="query.log_type"
                placeholder="日志类型"
                clearable
                filterable
                @change="search()"
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
            <el-form-item label="平台：" prop="platform">
              <el-select v-model="query.platform" clearable @change="search()">
                <el-option
                  v-for="(item, index) in platforms"
                  :key="index"
                  :label="item"
                  :value="index"
                />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="应用：" prop="application">
              <el-select v-model="query.application" clearable @change="search()">
                <el-option
                  v-for="(item, index) in applications"
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
                  <el-option value="member_id" label="会员ID" />
                  <el-option value="request_ip" label="请求IP" />
                  <el-option value="request_region" label="请求地区" />
                  <el-option value="request_isp" label="请求ISP" />
                  <el-option value="response_code" label="返回码" />
                </el-select>
              </template>
            </el-input>
          </el-col>
          <el-col :span="6">
            <el-button type="primary" @click="search()">搜索</el-button>
            <el-button title="重置" @click="refresh()">重置</el-button>
          </el-col>
        </el-row>
      </el-form>
      <div class="member-log-summary-bar">
        <div class="member-log-summary-bar__chips">
          <span class="summary-chip summary-chip--primary">总数 {{ count }}</span>
          <span class="summary-chip">已选 {{ selection.length }} 项</span>
          <span class="summary-chip">
            {{ query.platform === undefined ? '全部平台' : platforms[query.platform] || query.platform }}
          </span>
          <span class="summary-chip">成功 {{ successCount }}</span>
          <span v-if="errorCount > 0" class="summary-chip summary-chip--warning"
            >异常 {{ errorCount }}</span
          >
          <span
            v-for="item in activeFilterTags"
            :key="item"
            class="summary-chip"
          >
            {{ item }}
          </span>
          <span v-if="!activeFilterTags.length" class="summary-chip">默认条件：全部会员日志</span>
          <span v-if="recentActionSummary" class="summary-chip summary-chip--muted">{{
            recentActionSummary
          }}</span>
        </div>
        <div class="member-log-summary-bar__hint" :class="logFollowupBadgeClass">
          <span class="member-log-summary-bar__hint-title">{{ logFollowupBadgeText }}</span>
          <span class="member-log-summary-bar__hint-text">{{ logFollowupHint }}</span>
        </div>
      </div>
      <div class="member-log-guide">
        <div class="member-log-guide__header">
          <div>
            <div class="member-log-guide__title">如果你是第一次排会员日志，建议先按这个顺序</div>
            <div class="member-log-guide__desc">
              这页主要是帮你定位“谁在什么端、调了什么接口、为什么返回异常”。先缩小范围，再决定看详情、查会员还是回接口页。
            </div>
          </div>
          <div class="member-log-guide__badge">{{ memberLogFocusLabel }}</div>
        </div>
        <div class="member-log-guide__grid">
          <div v-for="item in memberLogGuideCards" :key="item.title" class="member-log-guide-card">
            <div class="member-log-guide-card__step">{{ item.step }}</div>
            <div class="member-log-guide-card__title">{{ item.title }}</div>
            <div class="member-log-guide-card__desc">{{ item.desc }}</div>
          </div>
        </div>
      </div>
      <div class="followup-panel">
        <div class="followup-panel__main">
          <div class="followup-panel__title">看完会员日志后继续去哪</div>
          <div class="followup-panel__desc">{{ logFollowupHint }}</div>
          <div class="followup-panel__tags">
            <span v-for="item in memberLogFollowupTags" :key="item">{{ item }}</span>
          </div>
          <div class="followup-panel__risk">{{ memberLogFollowupRiskText }}</div>
        </div>
        <div class="followup-panel__actions">
          <el-button type="primary" @click="goToPage('/member/member')">去会员列表</el-button>
          <el-button @click="goToPage('/member/statistic')">去会员统计</el-button>
          <el-button @click="goToPage('/member/api')">去会员接口</el-button>
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
        <el-form-item v-if="selection.length" label="操作提示">
          <div class="select-review-panel">
            <div class="select-review-panel__tags">
              <span>已选 {{ selection.length }} 项</span>
              <span>目标：{{ selectionPreview }}</span>
            </div>
            <div class="select-review-panel__hint">{{ logSubmitRisk }}</div>
          </div>
        </el-form-item>
        <el-form-item v-if="selectType === 'dele'">
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
          <div class="section-title-row__title">会员日志列表</div>
          <div class="section-title-row__desc">
            支持查看访问轨迹、接口返回结果，并对异常日志做清理维护。
          </div>
        </div>
        <div class="section-title-row__meta">已选 {{ selection.length }} 项</div>
      </div>

      <div class="operation_btn mb5">
        <div>
          <div class="action-cluster">
            <span class="action-cluster__title">批量处理</span>
            <el-button title="删除选择" @click="selectOpen('dele')">删除</el-button>
          </div>
          <div class="action-cluster">
            <span class="action-cluster__title">清理维护</span>
            <el-button title="删除查询结果" @click="clearLogs()">清空</el-button>
          </div>
        </div>
        <div>
          <pagination
            v-show="count > 0"
            v-model:total="count"
            v-model:page="query.page"
            v-model:limit="query.limit"
            @pagination="list"
          />
        </div>
      </div>

      <el-table
        ref="table"
        v-loading="loading"
        :data="data"
        @sort-change="sort"
        @selection-change="select"
      >
        <el-table-column type="selection" width="42" title="全选/反选" />
        <el-table-column
          :prop="idkey"
          label="ID"
          width="80"
          show-overflow-tooltip
          sortable="custom"
        />
        <el-table-column prop="member_id" label="会员ID" min-width="80" show-overflow-tooltip />
        <el-table-column prop="nickname" label="会员昵称" min-width="90" show-overflow-tooltip />
        <el-table-column prop="username" label="会员用户名" min-width="105" show-overflow-tooltip />
        <el-table-column prop="api_id" label="接口ID" min-width="80" show-overflow-tooltip />
        <el-table-column prop="api_name" label="接口名称" min-width="120" show-overflow-tooltip />
        <el-table-column prop="api_url" label="接口链接" min-width="165" show-overflow-tooltip />
        <el-table-column prop="request_ip" label="请求IP" min-width="130" show-overflow-tooltip />
        <el-table-column
          prop="request_region"
          label="请求地区"
          min-width="145"
          show-overflow-tooltip
        />
        <el-table-column prop="request_isp" label="请求ISP" min-width="90" show-overflow-tooltip />
        <el-table-column prop="response_code" label="返回码" min-width="85" show-overflow-tooltip />
        <el-table-column
          prop="response_msg"
          label="返回描述"
          min-width="100"
          show-overflow-tooltip
        />
        <el-table-column
          prop="application_name"
          label="应用"
          min-width="105"
          show-overflow-tooltip
        />
        <el-table-column prop="create_time" label="请求时间" width="165" sortable="custom" />
        <el-table-column label="操作" width="170">
          <template #default="scope">
            <el-link type="primary" class="mr-1" :underline="false" @click="info(scope.row)"
              >详情</el-link
            >
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
              v-if="scope.row.api_id"
              type="primary"
              class="mr-1"
              :underline="false"
              @click="goToMemberApi(scope.row)"
            >
              接口
            </el-link>
            <el-link type="primary" :underline="false" @click="selectOpen('dele', [scope.row])"
              >删除</el-link
            >
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <el-dialog
      v-model="dialog"
      :title="dialogTitle"
      :close-on-click-modal="false"
      :close-on-press-escape="false"
      :before-close="cancel"
      top="5vh"
    >
      <el-scrollbar native :max-height="height - 30">
        <el-form ref="ref" :rules="rules" :model="model" label-width="110px">
          <div class="dialog-review-note dialog-review-note--detail">
            <div class="dialog-review-note__title">日志详情速览</div>
            <div class="dialog-review-note__tags">
              <span class="dialog-review-note__tag">会员：{{ model.member_id || '未知' }}</span>
              <span class="dialog-review-note__tag"
                >返回码：{{ model.response_code || '未知' }}</span
              >
              <span class="dialog-review-note__tag">平台：{{ model.platform_name || '未知' }}</span>
              <span class="dialog-review-note__tag"
                >应用：{{ model.application_name || '未知' }}</span
              >
            </div>
            <div class="dialog-review-note__risk">{{ detailRiskHint }}</div>
          </div>
          <el-form-item label="会员ID" prop="member_id"
            ><el-input v-model="model.member_id"
          /></el-form-item>
          <el-form-item label="会员昵称" prop="nickname"
            ><el-input v-model="model.nickname"
          /></el-form-item>
          <el-form-item label="会员用户名" prop="username"
            ><el-input v-model="model.username"
          /></el-form-item>
          <el-form-item label="接口ID" prop="api_id"
            ><el-input v-model="model.api_id"
          /></el-form-item>
          <el-form-item label="接口名称" prop="api_name"
            ><el-input v-model="model.api_name"
          /></el-form-item>
          <el-form-item label="接口链接" prop="api_url"
            ><el-input v-model="model.api_url"
          /></el-form-item>
          <el-form-item label="请求方式" prop="request_method"
            ><el-input v-model="model.request_method"
          /></el-form-item>
          <el-form-item label="请求IP" prop="request_ip"
            ><el-input v-model="model.request_ip"
          /></el-form-item>
          <el-form-item label="请求地区" prop="request_region"
            ><el-input v-model="model.request_region"
          /></el-form-item>
          <el-form-item label="请求ISP" prop="request_isp"
            ><el-input v-model="model.request_isp"
          /></el-form-item>
          <el-form-item label="请求时间" prop="create_time"
            ><el-input v-model="model.create_time"
          /></el-form-item>
          <el-form-item label="返回码" prop="response_code"
            ><el-input v-model="model.response_code"
          /></el-form-item>
          <el-form-item label="返回描述" prop="response_msg"
            ><el-input v-model="model.response_msg"
          /></el-form-item>
          <el-form-item label="用户代理" prop="user_agent">
            <el-input v-model="model.user_agent" type="textarea" autosize />
          </el-form-item>
          <el-form-item label="平台" prop="platform_name"
            ><el-input v-model="model.platform_name"
          /></el-form-item>
          <el-form-item label="应用" prop="application_name"
            ><el-input v-model="model.application_name"
          /></el-form-item>
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
        <el-button @click="cancel">取消</el-button>
        <el-button type="primary" @click="submit">确定</el-button>
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
import { resolveAdminRuntimeEnv } from '@/utils/runtime-env'
import { list, info, dele, clear } from '@/api/member/log'

export default {
  name: 'MemberLog',
  components: { Pagination },
  data() {
    return {
      name: '会员日志',
      height: 680,
      loading: false,
      idkey: 'log_id',
      exps: [{ exp: 'like', name: '包含' }],
      query: {
        page: 1,
        limit: getPageLimit(),
        search_field: 'member_id',
        search_exp: 'like',
        search_value: '',
        date_field: 'create_time',
        is_disable: undefined,
        api_id: undefined,
        log_type: undefined,
        platform: undefined,
        application: undefined
      },
      data: [],
      count: 0,
      dialog: false,
      dialogTitle: '',
      model: {},
      rules: {},
      logTypes: [],
      platforms: [],
      applications: [],
      apiData: [],
      apiProps: {
        checkStrictly: true,
        value: 'api_id',
        label: 'api_name',
        multiple: true,
        emitPath: false
      },
      selection: [],
      selectIds: '',
      selectTitle: '操作',
      selectDialog: false,
      selectType: '',
      recentActionSummary: ''
    }
  },
  computed: {
    runtimeEnvInfo() {
      return resolveAdminRuntimeEnv()
    },
    entrySourceLabel() {
      const source = String(this.$route?.query?.from || '')
      if (source === 'member-api') return '来自会员接口'
      if (source === 'member-member') return '来自会员列表'
      if (source === 'member-statistic') return '来自会员统计'
      if (source === 'member-group') return '来自会员分组'
      if (source === 'member-third') return '来自第三方账号'
      if (source === 'setting-feedback') return '来自意见反馈'
      if (source === 'member-setting') return '来自会员设置'
      if (source === 'member-setting-log') return '来自会员设置'
      if (source === 'member-setting-logreg') return '来自会员设置'
      if (source === 'member-setting-api') return '来自会员设置'
      if (source === 'member-setting-member') return '来自会员设置'
      if (source === 'member-setting-third') return '来自会员设置'
      if (source === 'dashboard') return '来自后台首页'
      return ''
    },
    entryContextVisible() {
      return Boolean(this.entrySourceLabel)
    },
    entryContextTitle() {
      if (this.entrySourceLabel === '来自会员接口') return '当前从会员接口进入会员日志'
      if (this.entrySourceLabel === '来自会员列表') return '当前从会员列表进入会员日志'
      if (this.entrySourceLabel === '来自会员统计') return '当前从会员统计进入会员日志'
      if (this.entrySourceLabel === '来自会员分组') return '当前从会员分组进入会员日志'
      if (this.entrySourceLabel === '来自第三方账号') return '当前从第三方账号进入会员日志'
      if (this.entrySourceLabel === '来自意见反馈') return '当前从意见反馈进入会员日志'
      if (this.entrySourceLabel === '来自会员设置') return '当前从会员设置进入会员日志'
      if (this.entrySourceLabel === '来自后台首页') return '当前从后台首页进入会员日志'
      return '当前为外部入口承接视角'
    },
    entryContextDesc() {
      if (this.entrySourceLabel === '来自会员接口') {
        return '这类进入通常是为了看某个接口规则在真实请求里有没有生效。建议先看异常返回，再回接口页核鉴权例外。'
      }
      if (this.entrySourceLabel === '来自会员列表') {
        return '这类进入通常是为了追某个会员最近的访问和报错。建议先缩到该会员，再回会员列表继续处理账号本身。'
      }
      if (this.entrySourceLabel === '来自会员统计') {
        return '这类进入通常是为了把统计波动落到真实请求。建议先看端侧来源和异常返回，再回统计页复盘趋势。'
      }
      if (this.entrySourceLabel === '来自会员分组') {
        return '这类进入通常是为了核某个分组的会员请求是否正常。建议先看异常接口，再回分组页确认归属与权限。'
      }
      if (this.entrySourceLabel === '来自第三方账号') {
        return '这类进入通常是为了追第三方登录、解绑或绑定异常。建议先锁定会员和平台，再回第三方账号页继续处理绑定关系。'
      }
      if (this.entrySourceLabel === '来自意见反馈') {
        return '这类进入通常是为了把反馈问题落到真实访问链路。建议先确认会员和异常时间，再回意见反馈页继续收口。'
      }
      if (this.entrySourceLabel === '来自会员设置') {
        return '这类进入通常是为了验证会员设置改动有没有落到真实请求。建议先看接口、平台和返回码，再回设置页继续调整规则。'
      }
      return '这类进入通常是首页巡检后的继续下钻。建议先看异常返回和平台来源，再回业务页继续处理。'
    },
    entryContextPrimaryLabel() {
      if (this.entrySourceLabel === '来自会员接口') return '回会员接口'
      if (this.entrySourceLabel === '来自会员列表') return '回会员列表'
      if (this.entrySourceLabel === '来自会员统计') return '回会员统计'
      if (this.entrySourceLabel === '来自会员分组') return '回会员分组'
      if (this.entrySourceLabel === '来自第三方账号') return '回第三方账号'
      if (this.entrySourceLabel === '来自意见反馈') return '回意见反馈'
      if (this.entrySourceLabel === '来自会员设置') return '回会员设置'
      return '回后台首页'
    },
    successCount() {
      return this.data.filter((item) => Number(item.response_code) === 200).length
    },
    errorCount() {
      return this.data.filter((item) => Number(item.response_code) !== 200).length
    },
    activeFilterTags() {
      const tags = []
      if (this.query.date_value?.length === 2) {
        tags.push(`请求时间：${this.query.date_value[0]} 至 ${this.query.date_value[1]}`)
      }
      if (this.query.api_id !== undefined && this.query.api_id !== '') {
        tags.push(`接口：${this.query.api_id}`)
      }
      if (this.query.log_type !== undefined) {
        tags.push(`日志类型：${this.logTypes[this.query.log_type] || this.query.log_type}`)
      }
      if (this.query.platform !== undefined) {
        tags.push(`平台：${this.platforms[this.query.platform] || this.query.platform}`)
      }
      if (this.query.application !== undefined) {
        tags.push(`应用：${this.applications[this.query.application] || this.query.application}`)
      }
      if (this.query.search_value) {
        tags.push(`检索：${this.query.search_field || 'member_id'} = ${this.query.search_value}`)
      }
      return tags
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
    logSubmitRisk() {
      if (!this.selection.length) {
        return this.runtimeEnvInfo.isProd
          ? '正式环境下日志删除不可回退，建议先按筛选条件缩小范围再处理。'
          : '当前环境建议先选择样本日志，再验证删除与清空流程。'
      }
      return this.runtimeEnvInfo.isProd
        ? `正式环境下本次会直接删除 ${this.selection.length} 条会员日志，请确认不影响排障追踪。`
        : `当前环境可用于验证 ${this.selection.length} 条会员日志的删除和回显流程。`
    },
    logFollowupBadgeText() {
      if (this.selection.length > 0) {
        return '待确认'
      }
      return this.errorCount > 0 ? '优先排查' : '正常巡检'
    },
    logFollowupBadgeClass() {
      if (this.selection.length > 0) {
        return 'is-active'
      }
      return this.errorCount > 0 ? 'is-warning' : 'is-safe'
    },
    logFollowupHint() {
      if (!this.count) {
        return '当前没有会员访问日志，建议先核对前端链路是否已接入埋点和接口请求。'
      }
      if (this.selection.length > 0) {
        return `当前已选 ${this.selection.length} 条日志，删除前请确认不会影响异常定位与排障追踪。`
      }
      if (this.errorCount > 0) {
        return `当前列表含 ${this.errorCount} 条异常返回日志，建议优先查看详情和请求参数，再决定是否清理。`
      }
      return this.runtimeEnvInfo.isProd
        ? '正式环境建议优先按返回码、平台和接口维度排查异常日志，再决定是否清理。'
        : '当前环境适合先验证日志筛选、详情回显和异常清理流程。'
    },
    memberLogFocusLabel() {
      if (this.errorCount > 0) {
        return '先看异常返回'
      }
      if (this.selection.length > 0) {
        return '先复核已选日志'
      }
      if (this.query.platform !== undefined || this.query.application !== undefined) {
        return '先看端侧来源'
      }
      return '先缩小日志范围'
    },
    memberLogGuideCards() {
      return [
        {
          step: '第一步',
          title: '先把范围缩小到一类会员或一个端',
          desc: this.activeFilterTags.length
            ? `当前已带 ${this.activeFilterTags.length} 个筛选条件，可以先按这些结果往下查。`
            : '建议优先按时间、平台、应用或会员 ID 缩小范围，不要直接在全量日志里找。'
        },
        {
          step: '第二步',
          title: this.errorCount > 0 ? '优先看异常返回，不要先急着清理' : '先抽查几条正常日志，确认链路有回显',
          desc: this.errorCount > 0
            ? `当前有 ${this.errorCount} 条非 200 返回，建议先点详情看接口、参数和来源。`
            : `当前成功返回 ${this.successCount} 条，可先抽查详情确认平台、应用和会员来源是否符合预期。`
        },
        {
          step: '第三步',
          title: '看完日志后尽快回业务页继续处理',
          desc: this.memberLogFollowupRiskText
        }
      ]
    },
    memberLogFollowupTags() {
      return [
        `成功：${this.successCount} 条`,
        `异常：${this.errorCount} 条`,
        `已选：${this.selection.length} 项`,
        `筛选标签：${this.activeFilterTags.length} 项`
      ]
    },
    memberLogFollowupRiskText() {
      if (this.errorCount > 0) {
        return `当前页还有 ${this.errorCount} 条异常返回，建议先回会员接口或会员列表核对异常会员、请求参数和返回码。`
      }
      if (this.selection.length > 0) {
        return '当前已勾选日志，执行删除前请确认这些记录不再需要用于问题追踪或复盘。'
      }
      return '日志页更适合定位问题，不适合长期停留；看清异常后，尽快回会员列表、统计页或接口页继续处理。'
    },
    detailRiskHint() {
      if (Number(this.model.response_code) !== 200) {
        return '当前日志返回码异常，建议优先核对接口链接、请求参数和端侧来源。'
      }
      return '当前日志返回正常，可结合用户代理、平台和应用字段继续核对访问来源。'
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
    buildEntryRouteQuery(extraQuery = {}, options = {}) {
      const routeQuery = { ...(this.$route?.query || {}) }
      if (!options.preserveFrom) {
        delete routeQuery.from
      }
      return {
        ...routeQuery,
        ...extraQuery
      }
    },
    handleEntryContextPrimary() {
      if (this.entrySourceLabel === '来自会员接口') {
        this.goToPage('/member/api')
        return
      }
      if (this.entrySourceLabel === '来自会员列表') {
        this.goToPage('/member/member')
        return
      }
      if (this.entrySourceLabel === '来自会员统计') {
        this.goToPage('/member/statistic')
        return
      }
      if (this.entrySourceLabel === '来自会员分组') {
        this.goToPage('/member/group')
        return
      }
      if (this.entrySourceLabel === '来自第三方账号') {
        this.goToPage('/member/third')
        return
      }
      if (this.entrySourceLabel === '来自意见反馈') {
        this.goToPage('/setting/feedback')
        return
      }
      if (this.entrySourceLabel === '来自会员设置') {
        this.goToPage('/member/setting', { tab: 'logInfo', setting_tab: 'logInfo' })
        return
      }
      this.goToPage('/dashboard')
    },
    goToEntryContextBack() {
      this.handleEntryContextPrimary()
    },
    parseRouteNumber(value) {
      if (value === undefined || value === null || value === '') {
        return undefined
      }
      const parsed = Number(value)
      return Number.isFinite(parsed) ? parsed : undefined
    },
    parseRouteArray(value) {
      if (Array.isArray(value)) {
        return value
          .map((item) => this.parseRouteNumber(item))
          .filter((item) => item !== undefined)
      }
      if (typeof value === 'string' && value.trim()) {
        return value
          .split(',')
          .map((item) => this.parseRouteNumber(item.trim()))
          .filter((item) => item !== undefined)
      }
      const parsed = this.parseRouteNumber(value)
      return parsed !== undefined ? [parsed] : undefined
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
      const memberId = this.parseRouteNumber(routeQuery.member_id)
      if (memberId !== undefined && !nextQuery.search_value) {
        nextQuery.search_field = 'member_id'
        nextQuery.search_exp = '='
        nextQuery.search_value = String(memberId)
      }
      const isDisable = this.parseRouteNumber(routeQuery.is_disable)
      if (isDisable !== undefined) {
        nextQuery.is_disable = isDisable
      }
      const logType = this.parseRouteNumber(routeQuery.log_type)
      if (logType !== undefined) {
        nextQuery.log_type = logType
      }
      const platform = this.parseRouteNumber(routeQuery.platform)
      if (platform !== undefined) {
        nextQuery.platform = platform
      }
      const application = this.parseRouteNumber(routeQuery.application)
      if (application !== undefined) {
        nextQuery.application = application
      }
      const apiIds = this.parseRouteArray(routeQuery.api_id ?? routeQuery.api_ids)
      if (apiIds?.length) {
        nextQuery.api_id = apiIds
      }
      this.query = nextQuery
    },
    goToPage(path, extraQuery = {}) {
      this.$router.push({
        path,
        query: this.buildEntryRouteQuery({
          from: 'member-log',
          ...extraQuery
        })
      })
    },
    list() {
      this.loading = true
      list(this.query)
        .then((res) => {
          this.data = res.data.list
          this.count = res.data.count
          this.apiData = res.data.api
          this.platforms = res.data.platforms
          this.applications = res.data.applications
          this.logTypes = res.data.log_types
          this.exps = res.data.exps
          this.recentActionSummary = `已加载会员日志 ${res.data.count || 0} 条，异常返回 ${
            this.data.filter((item) => Number(item.response_code) !== 200).length
          } 条。`
          this.loading = false
        })
        .catch(() => {
          this.loading = false
        })
    },
    info(row) {
      this.dialog = true
      this.dialogTitle = this.name + '详情：' + row[this.idkey]
      this.recentActionSummary = `正在查看日志 ${row[this.idkey]} 详情，会员 ${
        row.member_id || '未知'
      }。`
      const id = {}
      id[this.idkey] = row[this.idkey]
      info(id).then((res) => {
        this.model = res.data
      })
    },
    goToMember(row = {}) {
      if (!row.member_id) {
        return
      }
      this.$router.push({
        path: '/member/member',
        query: this.buildEntryRouteQuery({
          from: 'member-log',
          search_field: 'member_id',
          search_exp: '=',
          search_value: String(row.member_id)
        })
      })
    },
    goToMemberApi(row = {}) {
      this.$router.push({
        path: '/member/api',
        query: this.buildEntryRouteQuery({
          from: 'member-log',
          api_id: row.api_id ? String(row.api_id) : ''
        })
      })
    },
    cancel() {
      this.dialog = false
      this.reset()
    },
    submit() {
      this.dialog = false
      this.reset()
    },
    reset(row) {
      if (row) {
        this.model = row
      } else {
        this.model = this.$options.data().model
      }
    },
    search() {
      this.query.page = 1
      this.recentActionSummary = `已按 ${this.query.search_field || 'member_id'} 发起日志检索。`
      this.list()
    },
    refresh() {
      const limit = this.query.limit
      this.query = this.$options.data().query
      this.$refs.table.clearSort()
      this.query.limit = limit
      this.recentActionSummary = '已重置会员日志筛选条件。'
      this.list()
    },
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
    select(selection) {
      this.selection = selection
      this.selectIds = this.selectGetIds(selection).toString()
      if (selection.length) {
        this.recentActionSummary = `已勾选 ${selection.length} 条会员日志，待处理 ID：${this.selectIds}。`
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
        this.$refs.table.clearSelection()
        const selectRowLen = selectRow.length
        for (let i = 0; i < selectRowLen; i += 1) {
          this.$refs.table.toggleRowSelection(selectRow[i], true)
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
      } else if (this.selectType === 'dele') {
        this.dele(this.selection)
        this.selectDialog = false
      }
    },
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
            this.recentActionSummary = `已删除 ${row.length} 条会员日志。`
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.loading = false
          })
      }
    },
    clearLogs() {
      ElMessageBox.confirm('确定要清空' + this.name + '(查询结果或所有)吗？', '提示', {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning'
      })
        .then(() => {
          clear(this.query)
            .then((res) => {
              this.list()
              this.recentActionSummary = `已清空 ${res.data.count || 0} 条会员日志。`
              ElMessage.success('清空' + this.name + '记录 ' + res.data.count + ' 条')
            })
            .catch(() => {})
        })
        .catch(() => {})
    },
    copy(text) {
      clip(text)
    },
    requestParamCopy() {
      const text = this.$refs.requestParamRef
      this.copy(text.textContent)
    }
  }
}
</script>

<style scoped>
.section-title-row {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.section-title-row__title {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
}

.section-title-row--content {
  margin-bottom: 14px;
}

.section-title-row__desc,
.section-title-row__meta {
  margin-top: 4px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.section-title-row__meta {
  font-weight: 600;
  white-space: nowrap;
}

.entry-context-banner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-top: 16px;
  padding: 14px 16px;
  border-radius: 12px;
  background: linear-gradient(135deg, #f5f7ff 0%, #fffaf0 100%);
  border: 1px solid #e4e7ed;
}

.entry-context-banner__main {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.entry-context-banner__eyebrow {
  font-size: 12px;
  font-weight: 600;
  color: #909399;
}

.entry-context-banner__title {
  font-size: 16px;
  font-weight: 600;
  color: #303133;
}

.entry-context-banner__desc {
  font-size: 13px;
  line-height: 1.6;
  color: #606266;
}

.entry-context-banner__actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  justify-content: flex-end;
}

.member-log-summary-bar {
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

.member-log-summary-bar__chips {
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
  border: 1px solid #dbe7f5;
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

.summary-chip--warning {
  color: #b45309;
  background: #fff5e8;
  border-color: #f7d8a8;
}

.member-log-summary-bar__hint {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 240px;
  padding: 12px 14px;
  border-radius: 12px;
  background: #eef4ff;
  color: #1d4ed8;
}

.member-log-summary-bar__hint.is-safe {
  background: #eaf8ef;
  color: #15803d;
}

.member-log-summary-bar__hint.is-warning {
  background: #fff5e8;
  color: #b45309;
}

.member-log-summary-bar__hint.is-active {
  background: #e8f0ff;
  color: #1d4ed8;
}

.member-log-summary-bar__hint-title {
  font-size: 12px;
  font-weight: 700;
}

.member-log-summary-bar__hint-text {
  font-size: 12px;
  line-height: 1.7;
}

.member-log-guide {
  margin-top: 10px;
  padding: 14px 16px;
  border: 1px solid #dbe7f5;
  border-radius: 14px;
  background: linear-gradient(135deg, #f9fbff 0%, #f5f8ff 100%);
}

.member-log-guide__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.member-log-guide__title {
  font-size: 14px;
  font-weight: 700;
  color: #111827;
}

.member-log-guide__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.member-log-guide__badge {
  display: inline-flex;
  align-items: center;
  min-height: 30px;
  padding: 0 12px;
  border-radius: 999px;
  background: #e8f0ff;
  color: #1d4ed8;
  font-size: 12px;
  font-weight: 700;
  white-space: nowrap;
}

.member-log-guide__grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-top: 14px;
}

.member-log-guide-card {
  padding: 12px 14px;
  border-radius: 12px;
  border: 1px solid rgba(191, 219, 254, 0.95);
  background: rgba(255, 255, 255, 0.92);
}

.member-log-guide-card__step {
  display: inline-flex;
  align-items: center;
  min-height: 22px;
  padding: 0 8px;
  border-radius: 999px;
  background: #eff6ff;
  color: #2563eb;
  font-size: 11px;
  font-weight: 700;
}

.member-log-guide-card__title {
  margin-top: 10px;
  font-size: 13px;
  font-weight: 700;
  color: #1f2937;
}

.member-log-guide-card__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #475569;
}

.followup-panel {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-top: 10px;
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
  font-size: 13px;
  font-weight: 700;
  color: #111827;
}

.followup-panel__desc,
.followup-panel__risk {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
}

.followup-panel__desc {
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

.followup-panel__risk {
  padding: 10px 12px;
  border-radius: 12px;
  border: 1px solid #fed7aa;
  background: #fff7ed;
  color: #c2410c;
}

.followup-panel__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  justify-content: flex-end;
}

.select-review-panel {
  width: 100%;
  padding: 12px 14px;
  border: 1px solid #dbe7f5;
  border-radius: 12px;
  background: #f8fbff;
}

.select-review-panel__tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.select-review-panel__tags span {
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  padding: 0 10px;
  border-radius: 999px;
  background: #fff;
  border: 1px solid #dbe7f5;
  color: #334155;
  font-size: 12px;
}

.select-review-panel__hint {
  margin-top: 10px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.dialog-review-note {
  margin-bottom: 14px;
  padding: 14px 16px;
  border: 1px solid #e6ecf5;
  border-radius: 14px;
  background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
  box-shadow: 0 6px 18px rgba(15, 35, 95, 0.05);
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
  margin-top: 10px;
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
  margin-top: 10px;
  padding: 12px 14px;
  border-radius: 12px;
  background: #fff7ed;
  border: 1px solid #fed7aa;
  color: #9a3412;
  font-size: 12px;
  line-height: 1.7;
}

.dialog-review-note--detail {
  margin-bottom: 16px;
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
  .entry-context-banner,
  .section-title-row,
  .followup-panel,
  .member-log-summary-bar,
  .member-log-guide__header {
    flex-direction: column;
  }

  .section-title-row__meta {
    white-space: normal;
  }

  .member-log-guide__grid {
    grid-template-columns: 1fr;
  }
}
</style>
