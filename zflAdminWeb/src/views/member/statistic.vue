<template>
  <el-scrollbar native :height="height">
    <div class="app-container">
      <div class="summary-panel">
        <div class="summary-panel__head">
          <div>
            <div class="summary-panel__eyebrow">会员统计</div>
            <div class="summary-panel__title">会员趋势总览</div>
            <div class="summary-panel__desc">
              按日或按月查看会员增长与趋势变化，支持快速切换时间维度。
            </div>
          </div>
          <div class="summary-panel__status">
            <span class="summary-panel__status-badge" :class="runtimeModeBadgeClass">
              {{ runtimeModeLabel }}
            </span>
            <span class="summary-panel__status-badge" :class="followupBadgeClass">
              {{ followupBadgeText }}
            </span>
            <span class="summary-panel__status-text">
              当前维度：{{ date_type === 'day' ? '按日统计' : '按月统计' }}
            </span>
          </div>
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
        <div class="summary-panel__metrics">
          <div class="summary-panel__metric">
            <span class="summary-panel__label">统计卡片</span>
            <strong>{{ count.length }}</strong>
          </div>
          <div class="summary-panel__metric">
            <span class="summary-panel__label">图表数量</span>
            <strong>{{ echart_num }}</strong>
          </div>
          <div class="summary-panel__metric">
            <span class="summary-panel__label">统计维度</span>
            <strong>{{ date_type === 'day' ? '按日' : '按月' }}</strong>
          </div>
        </div>
        <div class="summary-panel__filters">
          <div class="summary-panel__filters-head">
            <div>
              <div class="summary-panel__filters-title">统计筛选</div>
              <div class="summary-panel__filters-desc">
                当前页仅做统计分析展示，不直接写入业务数据。
              </div>
            </div>
            <div class="summary-panel__filters-controls">
              <el-select v-model="date_type" class="!w-[100px]" @change="typeChange">
                <el-option :label="$t('common.day')" value="day" />
                <el-option :label="$t('common.month')" value="month" />
              </el-select>
              <el-date-picker
                v-model="date_range"
                :type="date_ptype"
                :value-format="date_format"
                :picker-options="date_options"
                :start-placeholder="$t('common.Start date')"
                :end-placeholder="$t('common.End date')"
                @change="dateChange"
              />
            </div>
          </div>
          <div class="summary-panel__tags">
            <el-tag v-for="item in activeFilterTags" :key="item" effect="plain">{{ item }}</el-tag>
            <el-tag v-if="!activeFilterTags.length" type="info" effect="plain">
              默认条件：按月查看全部趋势
            </el-tag>
          </div>
        <div class="summary-panel__notice-row">
          <div class="summary-panel__notice">
            <span class="summary-panel__notice-label">分析建议</span>
            <span>{{ followupHint }}</span>
          </div>
            <div v-if="recentActionSummary" class="summary-panel__notice summary-panel__notice--muted">
              <span class="summary-panel__notice-label">最近操作</span>
              <span>{{ recentActionSummary }}</span>
            </div>
            <div class="summary-panel__risk">{{ followupRiskText }}</div>
          </div>
        </div>
        <div class="statistic-guide-panel">
          <div class="statistic-guide-panel__header">
            <div>
              <div class="statistic-guide-panel__title">第一次看会员统计，建议先按这个顺序</div>
              <div class="statistic-guide-panel__desc">
                这页不是直接处理会员，而是帮你先判断“现在是短期异常、长期趋势，还是某类人群变化”。先看窗口，再决定去日志、分组还是会员列表。
              </div>
            </div>
            <div class="statistic-guide-panel__badge">{{ statisticFocusLabel }}</div>
          </div>
          <div class="statistic-guide-panel__grid">
            <div
              v-for="item in statisticGuideCards"
              :key="item.title"
              class="statistic-guide-card"
            >
              <div class="statistic-guide-card__step">{{ item.step }}</div>
              <div class="statistic-guide-card__title">{{ item.title }}</div>
              <div class="statistic-guide-card__desc">{{ item.desc }}</div>
            </div>
          </div>
        </div>
        <div class="summary-panel__actions">
          <button
            v-for="item in nextActionCards"
            :key="item.title"
            type="button"
            class="summary-action-card"
            @click="goToMemberPage(item.path, item.extraQuery)"
          >
            <span class="summary-action-card__title">{{ item.title }}</span>
            <span class="summary-action-card__desc">{{ item.desc }}</span>
          </button>
        </div>
        <div class="followup-panel">
          <div class="followup-panel__main">
            <div class="followup-panel__title">看完统计后继续去哪</div>
            <div class="followup-panel__desc">{{ followupPanelHint }}</div>
            <div class="followup-panel__tags">
              <span v-for="item in followupPanelTags" :key="item">{{ item }}</span>
            </div>
          </div>
          <div class="followup-panel__actions">
            <el-button type="primary" @click="goToMemberPage('/member/member')">去会员列表</el-button>
            <el-button @click="goToMemberPage('/member/group')">去会员分组</el-button>
            <el-button @click="goToMemberPage('/member/log')">去会员日志</el-button>
          </div>
        </div>
      </div>
      <div class="section-title-row section-title-row--content">
        <div>
          <div class="section-title-row__title">统计结果</div>
          <div class="section-title-row__desc">
            上方展示核心会员指标，下方图表展示时间趋势，便于运营快速判断变化。
          </div>
        </div>
        <div class="section-title-row__meta">
          图表 {{ echart_num }} 张，指标 {{ count.length }} 项
        </div>
      </div>
      <el-row v-loading="loading" :gutter="10">
          <el-col v-for="(item, index) in count" :key="index" :xs="24" :span="3">
            <el-card :body-style="{ padding: '10px 0px' }" shadow="never">
              <template #header>
                <div class="metric-card-head">
                  <span>{{ item.name }}</span>
                  <el-button link type="primary" @click="goToMetricAction(item)">去处理</el-button>
                </div>
              </template>
              <div class="text-center" :title="item.title">
                <el-statistic :value="item.count" />
              </div>
              <div class="metric-card-foot">
                {{ resolveMetricAction(item).desc }}
              </div>
            </el-card>
          </el-col>
        </el-row>
      <el-card
        v-for="(item, index) in echart_num"
        :key="index"
        v-loading="loading"
        class="mt-[10px]"
        shadow="never"
      >
          <div class="chart-card-head">
            <div class="chart-card-head__meta">
              <span>{{ resolveChartAction(echart_data[index]).title }}</span>
              <small>{{ resolveChartAction(echart_data[index]).desc }}</small>
            </div>
            <div class="chart-card-head__actions">
              <el-button link type="primary" @click="goToChartAction(echart_data[index])">去处理</el-button>
              <el-button link @click="goToMemberPage('/member/log', { source_chart: echart_data[index]?.title || '' })">
                查日志
              </el-button>
            </div>
          </div>
          <el-row class="text-center">
            <el-col>
              <div :id="echart_id + index" :style="{ height: height - 300 + 'px' }"></div>
            </el-col>
        </el-row>
      </el-card>
    </div>
  </el-scrollbar>
</template>

<script>
import screenHeight from '@/utils/screen-height'
import { statistic } from '@/api/member/member'
// ECharts
// 引入 echarts 核心模块，核心模块提供了 echarts 使用必须要的接口
import * as echarts from 'echarts/core'
// 引入图表，图表后缀都为 Chart
import { LineChart, BarChart } from 'echarts/charts'
// 引入组件，组件后缀都为 Component
import {
  TitleComponent,
  LegendComponent,
  GridComponent,
  TooltipComponent,
  ToolboxComponent
} from 'echarts/components'
// 引入 Canvas 渲染器，注意引入 CanvasRenderer 或者 SVGRenderer 是必须的一步
import { CanvasRenderer } from 'echarts/renderers'
// 注册必须的组件
echarts.use([
  LineChart,
  BarChart,
  TitleComponent,
  LegendComponent,
  GridComponent,
  TooltipComponent,
  ToolboxComponent,
  CanvasRenderer
])

export default {
  name: 'MemberStatistic',
  data() {
    return {
      name: '会员统计',
      height: 680,
      loading: false,
      count: [],
      echart_id: 'echartid',
      echart_num: 0,
      echart_data: [],
      date_type: 'month',
      date_range: [],
      date_options: {},
      recentActionSummary: '',
      date_ptype: 'monthrange',
      date_format: 'YYYY-MM',
      picker_options_day: {
        shortcuts: [
          {
            text: '最近7天',
            onClick(picker) {
              const end = new Date()
              const start = new Date()
              start.setTime(start.getTime() - 3600 * 1000 * 24 * 6)
              picker.$emit('pick', [start, end])
            }
          },
          {
            text: '最近30天',
            onClick(picker) {
              const end = new Date()
              const start = new Date()
              start.setTime(start.getTime() - 3600 * 1000 * 24 * 29)
              picker.$emit('pick', [start, end])
            }
          },
          {
            text: '最近90天',
            onClick(picker) {
              const end = new Date()
              const start = new Date()
              start.setTime(start.getTime() - 3600 * 1000 * 24 * 89)
              picker.$emit('pick', [start, end])
            }
          },
          {
            text: '最近120天',
            onClick(picker) {
              const end = new Date()
              const start = new Date()
              start.setTime(start.getTime() - 3600 * 1000 * 24 * 119)
              picker.$emit('pick', [start, end])
            }
          }
        ]
      },
      picker_options_month: {
        shortcuts: [
          {
            text: '最近3个月',
            onClick(picker) {
              const end = new Date()
              const start = new Date()
              start.setMonth(start.getMonth() - 2)
              picker.$emit('pick', [start, end])
            }
          },
          {
            text: '最近6个月',
            onClick(picker) {
              const end = new Date()
              const start = new Date()
              start.setMonth(start.getMonth() - 5)
              picker.$emit('pick', [start, end])
            }
          },
          {
            text: '最近9个月',
            onClick(picker) {
              const end = new Date()
              const start = new Date()
              start.setMonth(start.getMonth() - 8)
              picker.$emit('pick', [start, end])
            }
          },
          {
            text: '最近12个月',
            onClick(picker) {
              const end = new Date()
              const start = new Date()
              start.setMonth(start.getMonth() - 11)
              picker.$emit('pick', [start, end])
            }
          }
        ]
      }
    }
  },
  computed: {
    entrySourceLabel() {
      const source = String(this.$route?.query?.from || '')
      if (source === 'member-member') return '来自会员列表'
      if (source === 'member-group') return '来自会员分组'
      if (source === 'member-setting') return '来自会员设置'
      if (source === 'member-setting-api') return '来自会员设置'
      if (source === 'member-setting-log') return '来自会员设置'
      if (source === 'member-setting-logreg') return '来自会员设置'
      if (source === 'member-setting-member') return '来自会员设置'
      if (source === 'member-setting-third') return '来自会员设置'
      if (source === 'member-api') return '来自会员接口'
      if (source === 'member-log') return '来自会员日志'
      if (source === 'dashboard') return '来自后台首页'
      return ''
    },
    entryContextVisible() {
      return Boolean(this.entrySourceLabel)
    },
    entryContextTitle() {
      if (this.entrySourceLabel === '来自会员列表') return '当前从会员列表进入会员统计'
      if (this.entrySourceLabel === '来自会员设置') return '当前从会员设置进入会员统计'
      if (this.entrySourceLabel === '来自会员分组') return '当前从会员分组进入会员统计'
      if (this.entrySourceLabel === '来自会员接口') return '当前从会员接口进入会员统计'
      if (this.entrySourceLabel === '来自会员日志') return '当前从会员日志进入会员统计'
      if (this.entrySourceLabel === '来自后台首页') return '当前从后台首页进入会员统计'
      return '当前为外部入口承接视角'
    },
    entryContextDesc() {
      if (this.entrySourceLabel === '来自会员列表') {
        return '这类进入通常是为了把某批会员的问题先放到趋势上看。建议先判断是短期波动还是长期变化，再回会员列表继续筛人群。'
      }
      if (this.entrySourceLabel === '来自会员设置') {
        return '这类进入通常是为了看设置调整后会员趋势有没有变化。建议先看当前窗口，再回设置页复核全局规则。'
      }
      if (this.entrySourceLabel === '来自会员分组') {
        return '这类进入通常是为了先看某个分组到底是短期波动还是长期变化。建议先看窗口，再回分组页继续缩小人群。'
      }
      if (this.entrySourceLabel === '来自会员接口') {
        return '这类进入通常是为了判断接口或登录链路有没有带来趋势波动。建议先看按日图，再回接口页核具体规则。'
      }
      if (this.entrySourceLabel === '来自会员日志') {
        return '这类进入通常是为了把异常日志放到整体趋势里看。建议先判断波动窗口，再回日志页继续查行为明细。'
      }
      return '这类进入通常是首页巡检后的继续下钻。建议先判断当前是短期异常还是长期趋势，再决定回会员列表、分组或日志。'
    },
    entryContextPrimaryLabel() {
      if (this.entrySourceLabel === '来自会员列表') return '回会员列表'
      if (this.entrySourceLabel === '来自会员设置') return '回会员设置'
      if (this.entrySourceLabel === '来自会员分组') return '回会员分组'
      if (this.entrySourceLabel === '来自会员接口') return '回会员接口'
      if (this.entrySourceLabel === '来自会员日志') return '回会员日志'
      return '回后台首页'
    },
    runtimeModeLabel() {
      return process.env.NODE_ENV === 'production' ? '生产构建' : '开发构建'
    },
    runtimeModeBadgeClass() {
      return process.env.NODE_ENV === 'production' ? 'is-production' : 'is-development'
    },
    activeFilterTags() {
      const tags = [`统计维度：${this.date_type === 'day' ? '按日' : '按月'}`]
      if (this.date_range?.length === 2) {
        tags.push(`时间范围：${this.date_range[0]} 至 ${this.date_range[1]}`)
      }
      if (this.count.length) {
        tags.push(`指标卡：${this.count.length} 项`)
      }
      if (this.echart_num) {
        tags.push(`趋势图：${this.echart_num} 张`)
      }
      return tags
    },
    followupBadgeText() {
      if (this.loading) {
        return '统计加载中'
      }
      if (this.date_type === 'day') {
        return '适合短期复盘'
      }
      if (this.echart_num > 1) {
        return '适合月度汇总'
      }
      return '已生成基础趋势'
    },
    followupBadgeClass() {
      if (this.loading) {
        return 'is-warning'
      }
      if (this.date_type === 'day') {
        return 'is-active'
      }
      return 'is-safe'
    },
    followupHint() {
      if (this.date_range?.length === 2) {
        return `当前统计窗口为 ${this.date_range[0]} 至 ${this.date_range[1]}，建议结合增长、活跃和沉默趋势做复盘。`
      }
      return '可以先选择时间范围，再结合图表切换折线/柱状图观察会员增长与变化。'
    },
    followupRiskText() {
      if (this.date_type === 'day') {
        return '按日统计更容易放大短周期波动，建议与最近7天、30天两组窗口交叉对比后再下结论。'
      }
      return '按月统计适合看大盘走势，但会弱化日级异常峰值，若发现异常建议回切到按日继续排查。'
    },
    statisticFocusLabel() {
      if (this.loading) {
        return '先等统计结果'
      }
      if (this.date_type === 'day') {
        return '先看短期波动'
      }
      if (this.echart_num > 1) {
        return '先看长期趋势'
      }
      return '先看核心指标'
    },
    statisticGuideCards() {
      const rangeLabel = this.date_range?.length === 2
        ? `${this.date_range[0]} 至 ${this.date_range[1]}`
        : '当前默认统计窗口'
      return [
        {
          step: '第一步',
          title: this.date_type === 'day' ? '先判断是不是短周期异常波动' : '先判断是不是持续趋势变化',
          desc: `当前正在看 ${this.date_type === 'day' ? '按日' : '按月'} 统计，窗口为 ${rangeLabel}。`
        },
        {
          step: '第二步',
          title: this.date_type === 'day' ? '短期异常优先回日志查行为' : '长期变化优先回分组和会员列表查人群',
          desc: this.date_type === 'day'
            ? '如果某几天突然放大或回落，优先去会员日志核对登录、访问和接口行为。'
            : '如果连续多月增长或沉默变化明显，优先去分组页和会员列表继续定位是哪类会员在变。'
        },
        {
          step: '第三步',
          title: '最后再决定落到哪个承接页继续处理',
          desc: this.followupPanelHint
        }
      ]
    },
    followupPanelHint() {
      if (this.date_type === 'day') {
        return '短周期统计更适合先去会员日志确认行为波动，再回会员列表锁定具体人群，必要时落到分组继续承接。'
      }
      return '月度趋势更适合先去会员列表核对人群范围，再到分组页承接留存和沉默人群，最后用日志页补异常排查。'
    },
    followupPanelTags() {
      return [
        `当前维度：${this.date_type === 'day' ? '按日统计' : '按月统计'}`,
        this.date_range?.length === 2
          ? `统计窗口：${this.date_range[0]} 至 ${this.date_range[1]}`
          : '统计窗口：默认时间范围',
        `结果规模：${this.count.length || 0} 项指标 / ${this.echart_num || 0} 张图表`
      ]
    },
    nextActionCards() {
      const rangeLabel = this.date_range?.length === 2 ? `${this.date_range[0]} 至 ${this.date_range[1]}` : '当前统计窗口'
      return [
        {
          title: '去会员管理',
          path: '/member/member',
          desc: `把 ${rangeLabel} 的趋势判断带去会员列表，继续筛选、禁用、分组和会员核对。`
        },
        {
          title: this.date_type === 'day' ? '去会员日志' : '去会员分组',
          path: this.date_type === 'day' ? '/member/log' : '/member/group',
          desc:
            this.date_type === 'day'
              ? '短周期波动更适合直接看日志，排查注册、登录和访问异常。'
              : '月度变化更适合拆分组，方便把沉默、活跃和增长人群继续下钻。'
        },
        {
          title: '去会员标签',
          path: '/member/tag',
          desc: '把增长或沉默人群继续拆到标签侧，便于后续精细化承接。'
        },
        {
          title: '去会员接口',
          path: '/member/api',
          desc: '若趋势异常怀疑是登录或接口链路导致，可继续去会员接口页排查权限与例外配置。'
        }
      ]
    }
  },
  watch: {
    echart_data() {
      this.$nextTick(() => {
        this.setEchart()
      })
    }
  },
  created() {
    this.height = screenHeight(140)
    this.restoreRouteQuery()
    this.dateOptions()
    this.statistic()
  },
  methods: {
    handleEntryContextPrimary() {
      if (this.entrySourceLabel === '来自会员列表') {
        this.goToMemberPage('/member/member', { from: 'member-statistic' })
        return
      }
      if (this.entrySourceLabel === '来自会员分组') {
        this.goToMemberPage('/member/group', { from: 'member-statistic' })
        return
      }
      if (this.entrySourceLabel === '来自会员设置') {
        this.goToMemberPage('/member/setting', { from: 'member-statistic' })
        return
      }
      if (this.entrySourceLabel === '来自会员接口') {
        this.goToMemberPage('/member/api', { from: 'member-statistic' })
        return
      }
      if (this.entrySourceLabel === '来自会员日志') {
        this.goToMemberPage('/member/log', { from: 'member-statistic' })
        return
      }
      this.$router.push({
        path: '/dashboard',
        query: this.buildStatisticContext({ from: 'member-statistic' })
      })
    },
    goToEntryContextBack() {
      this.handleEntryContextPrimary()
    },
    statistic() {
      this.loading = true
      statistic({
        type: this.date_type,
        date: this.date_range
      })
        .then((res) => {
          const nextCount = Array.isArray(res.data?.count) ? res.data.count : []
          const nextCharts = Array.isArray(res.data?.echart) ? res.data.echart : []
          this.count = nextCount
          this.echart_data = nextCharts
          this.echart_num = nextCharts.length
          this.dateOptions()
          this.syncRouteQuery()
          this.setRecentActionSummary('刷新会员趋势')
          this.loading = false
        })
        .catch(() => {
          this.loading = false
        })
    },
    typeChange() {
      this.dateOptions()
      this.date_range = []
      this.syncRouteQuery()
      this.statistic()
    },
    dateOptions() {
      const type = this.date_type
      if (type === 'day') {
        this.date_ptype = 'daterange'
        this.date_format = 'YYYY-MM-DD'
        this.date_options = this.picker_options_day
      } else if (type === 'month') {
        this.date_ptype = 'monthrange'
        this.date_format = 'YYYY-MM'
        this.date_options = this.picker_options_month
      }
    },
    dateChange() {
      this.syncRouteQuery()
      this.statistic()
    },
    restoreRouteQuery() {
      const query = this.$route.query || {}
      const type = String(query.type || '').trim()
      if (type === 'day' || type === 'month') {
        this.date_type = type
      }
      const start = String(query.start || '').trim()
      const end = String(query.end || '').trim()
      if (start && end) {
        this.date_range = [start, end]
      }
    },
    syncRouteQuery() {
      const nextQuery = { ...this.$route.query, type: this.date_type }
      if (this.date_range?.length === 2) {
        nextQuery.start = this.date_range[0]
        nextQuery.end = this.date_range[1]
      } else {
        delete nextQuery.start
        delete nextQuery.end
      }
      this.$router.replace({
        name: this.$route.name,
        query: nextQuery
      })
    },
    buildStatisticContext(extraQuery = {}) {
      const query = {
        ...this.$route.query,
        from: 'member-statistic',
        statistic_type: this.date_type
      }
      if (this.date_range?.length === 2) {
        query.statistic_start = this.date_range[0]
        query.statistic_end = this.date_range[1]
      }
      return {
        ...query,
        ...extraQuery
      }
    },
    goToMemberPage(path, extraQuery = {}) {
      this.$router.push({ path, query: this.buildStatisticContext(extraQuery) })
    },
    resolveMetricAction(item = {}) {
      const name = String(item.name || item.title || '')
      if (/活跃|登录|访问|在线/.test(name)) {
        return {
          path: '/member/log',
          title: '去会员日志',
          desc: '这个指标更适合继续查日志，看看短周期行为变化是否异常。',
          extraQuery: { focus_metric: name }
        }
      }
      if (/标签/.test(name)) {
        return {
          path: '/member/tag',
          title: '去会员标签',
          desc: '这个指标更适合继续看标签，把趋势拆成可运营的人群。',
          extraQuery: { focus_metric: name }
        }
      }
      if (/分组|沉默|留存/.test(name)) {
        return {
          path: '/member/group',
          title: '去会员分组',
          desc: '这个指标更适合继续看分组，方便承接留存和沉默人群处理。',
          extraQuery: { focus_metric: name }
        }
      }
      return {
        path: '/member/member',
        title: '去会员管理',
        desc: '这个指标适合回到会员列表，继续做筛选、核对和状态处理。',
        extraQuery: { focus_metric: name }
      }
    },
    goToMetricAction(item = {}) {
      const action = this.resolveMetricAction(item)
      this.goToMemberPage(action.path, action.extraQuery)
    },
    resolveChartAction(item = {}) {
      const title = String(item?.title || '')
      if (/登录|访问|活跃/.test(title)) {
        return {
          path: '/member/log',
          title: '当前图更适合去日志排查',
          desc: '看完趋势后直接对照日志，能更快确认是不是登录、访问或接口波动。 ',
          extraQuery: { source_chart: title }
        }
      }
      if (/沉默|留存|分组/.test(title)) {
        return {
          path: '/member/group',
          title: '当前图更适合去分组承接',
          desc: '这类趋势更适合转成分组运营动作，方便后续沉默和留存处理。',
          extraQuery: { source_chart: title }
        }
      }
      return {
        path: '/member/member',
        title: '当前图更适合去会员列表复核',
        desc: '看完走势后先回会员列表，把时间窗口下的问题人群筛出来继续处理。',
        extraQuery: { source_chart: title }
      }
    },
    goToChartAction(item = {}) {
      const action = this.resolveChartAction(item)
      this.goToMemberPage(action.path, action.extraQuery)
    },
    setRecentActionSummary(action) {
      const rangeText =
        this.date_range?.length === 2
          ? `${this.date_range[0]} 至 ${this.date_range[1]}`
          : '默认时间范围'
      this.recentActionSummary = `${action}完成，当前为${
        this.date_type === 'day' ? '按日' : '按月'
      }统计，范围：${rangeText}，已生成 ${this.count.length || 0} 项指标和 ${
        this.echart_num || 0
      } 张图表。`
    },
    setEchart() {
      const data = this.echart_data
      const num = this.echart_num
      const id = this.echart_id
      for (let i = 0; i < num; i++) {
        this.initEchart(data[i], id + i)
      }
    },
    initEchart(data, id) {
      const target = document.getElementById(id)
      if (!target) return
      const existingChart = echarts.getInstanceByDom(target)
      if (existingChart) {
        existingChart.dispose()
      }
      const myChart = echarts.init(target)
      const option = {
        title: {
          text: data.title,
          textStyle: { fontSize: 12 }
        },
        legend: {
          top: '20px',
          data: data.legend,
          selected: { 总数: false }
        },
        grid: {
          top: '80px',
          left: '1%',
          right: '3%',
          bottom: '3%',
          containLabel: true
        },
        xAxis: {
          type: 'category',
          boundaryGap: false,
          data: data.xAxis
        },
        yAxis: {
          type: 'value'
        },
        tooltip: {
          trigger: 'axis',
          textStyle: {
            align: 'left'
          }
        },
        toolbox: {
          feature: {
            magicType: { show: true, type: ['line', 'bar'] },
            dataView: { show: true, readOnly: true },
            saveAsImage: { show: true, name: this.name + data.date[0] + '-' + data.date[1] }
          }
        },
        series: data.series
      }
      myChart.setOption(option)
    }
  }
}
</script>

<style scoped>
.summary-panel,
.section-title-row {
  padding: 14px 16px;
  margin-bottom: 14px;
  border: 1px solid #e6ecf5;
  border-radius: 12px;
  background: linear-gradient(180deg, #f7faff 0%, #ffffff 100%);
}

.summary-panel {
  display: grid;
  gap: 14px;
  border-color: #dbe7f5;
}

.section-title-row--content {
  margin-top: 4px;
}

.section-title-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
}

.summary-panel__eyebrow {
  margin-bottom: 4px;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.08em;
  color: #7c8aa5;
}

.summary-panel__title,
.section-title-row__title {
  font-size: 15px;
  font-weight: 600;
  color: #243b53;
}

.summary-panel__desc,
.section-title-row__desc,
.section-title-row__meta {
  font-size: 12px;
  color: #7c8aa5;
}

.summary-panel__head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.summary-panel__status {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.summary-panel__status-badge {
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

.summary-panel__status-badge.is-production,
.summary-panel__status-badge.is-safe {
  color: #15803d;
  background: #eaf8ef;
}

.summary-panel__status-badge.is-development,
.summary-panel__status-badge.is-warning {
  color: #b45309;
  background: #fff5e8;
}

.summary-panel__status-badge.is-active {
  color: #1d4ed8;
  background: #e8f0ff;
}

.summary-panel__status-text {
  font-size: 12px;
  color: #64748b;
}

.summary-panel__metrics {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 12px;
}

.entry-context-banner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
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

.summary-panel__metric {
  border: 1px solid #e6ecf5;
  border-radius: 12px;
  padding: 12px 14px;
  background: linear-gradient(180deg, #f9fbff 0%, #ffffff 100%);
}

.summary-panel__label {
  display: block;
  margin-bottom: 6px;
  font-size: 12px;
  color: #7c8aa5;
}

.summary-panel__filters {
  padding: 14px;
  border: 1px solid #e6ecf5;
  border-radius: 12px;
  background: #ffffff;
}

.summary-panel__filters-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 12px;
}

.summary-panel__filters-title {
  margin-bottom: 4px;
  font-size: 13px;
  font-weight: 600;
  color: #334155;
}

.summary-panel__filters-desc {
  font-size: 12px;
  color: #7c8aa5;
}

.summary-panel__filters-controls,
.summary-panel__tags {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 10px;
}

.summary-panel__notice-row {
  display: grid;
  gap: 8px;
  margin-top: 12px;
}

.summary-panel__actions {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 10px;
}

.statistic-guide-panel {
  padding: 14px 16px;
  border: 1px solid #dbe7f5;
  border-radius: 14px;
  background: linear-gradient(135deg, #f9fbff 0%, #f5f8ff 100%);
}

.statistic-guide-panel__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.statistic-guide-panel__title {
  font-size: 14px;
  font-weight: 700;
  color: #0f172a;
}

.statistic-guide-panel__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.statistic-guide-panel__badge {
  display: inline-flex;
  align-items: center;
  min-height: 30px;
  padding: 0 12px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 700;
  color: #1d4ed8;
  background: #e8f0ff;
  white-space: nowrap;
}

.statistic-guide-panel__grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-top: 14px;
}

.statistic-guide-card {
  padding: 12px 14px;
  border-radius: 12px;
  border: 1px solid rgba(191, 219, 254, 0.95);
  background: rgba(255, 255, 255, 0.92);
}

.statistic-guide-card__step {
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

.statistic-guide-card__title {
  margin-top: 10px;
  font-size: 13px;
  font-weight: 700;
  color: #1f2937;
}

.statistic-guide-card__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #475569;
}

.summary-action-card {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 6px;
  min-height: 108px;
  padding: 12px 14px;
  text-align: left;
  border: 1px solid #dbe5f1;
  border-radius: 12px;
  background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
  transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
}

.summary-action-card:hover {
  transform: translateY(-1px);
  border-color: rgba(37, 99, 235, 0.28);
  box-shadow: 0 12px 28px rgba(37, 99, 235, 0.08);
}

.summary-action-card__title {
  font-size: 14px;
  font-weight: 700;
  color: #0f172a;
}

.summary-action-card__desc {
  font-size: 12px;
  line-height: 1.6;
  color: #64748b;
}

.followup-panel {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  padding: 14px 16px;
  border-radius: 14px;
  border: 1px solid #e6ecf5;
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

.metric-card-head,
.chart-card-head,
.chart-card-head__actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.metric-card-foot {
  padding: 8px 12px 0;
  font-size: 12px;
  line-height: 1.6;
  color: #64748b;
  text-align: left;
}

.chart-card-head {
  margin-bottom: 12px;
  padding-bottom: 12px;
  border-bottom: 1px solid #eef2f7;
}

.chart-card-head__meta {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 4px;
  text-align: left;
}

.chart-card-head__meta span {
  font-size: 13px;
  font-weight: 600;
  color: #334155;
}

.chart-card-head__meta small {
  font-size: 12px;
  line-height: 1.6;
  color: #64748b;
}

.summary-panel__notice,
.summary-panel__risk {
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
  padding: 10px 12px;
  border-radius: 10px;
  background: #f8fbff;
}

.summary-panel__notice--muted {
  background: #fbfdff;
}

.summary-panel__notice-label {
  display: inline-block;
  margin-right: 8px;
  font-weight: 600;
  color: #475569;
}

@media (max-width: 900px) {
  .entry-context-banner,
  .summary-panel__head,
  .summary-panel__filters-head,
  .statistic-guide-panel__header,
  .section-title-row,
  .summary-panel__status,
  .chart-card-head,
  .followup-panel {
    align-items: flex-start;
    flex-direction: column;
  }

  .statistic-guide-panel__grid {
    grid-template-columns: 1fr;
  }
}
</style>
