<template>
  <div class="app-container">
    <el-card  class="app-head">
      <div class="setting-overview">
        <div class="setting-overview__card">
          <span class="setting-overview__label">模块数量</span>
          <strong>{{ visibleTabCount }}</strong>
        </div>
        <div class="setting-overview__card">
          <span class="setting-overview__label">当前栏目</span>
          <strong>会员设置中心</strong>
        </div>
        <div class="setting-overview__card">
          <span class="setting-overview__label">操作提示</span>
          <strong>按标签分项维护</strong>
        </div>
      </div>
      <div class="section-title-row">
        <div>
          <div class="section-title-row__title">会员业务配置</div>
          <div class="section-title-row__desc">集中维护会员体系、登录注册、验证码、接口和日志配置。</div>
        </div>
        <div class="section-title-row__meta">{{ currentTabText }} / 共 {{ visibleTabCount }} 个模块</div>
      </div>
      <div v-if="entryContextVisible" class="entry-context-banner">
        <div class="entry-context-banner__main">
          <div class="entry-context-banner__eyebrow">外部入口承接</div>
          <div class="entry-context-banner__title">{{ entryContextTitle }}</div>
          <div class="entry-context-banner__desc">{{ entryContextDesc }}</div>
        </div>
        <div class="entry-context-banner__actions">
          <el-button type="primary" @click="handleEntryContextPrimary">
            {{ entryContextPrimaryLabel }}
          </el-button>
          <el-button @click="goToEntryContextBack">回来源页</el-button>
        </div>
      </div>
      <div class="setting-status-row">
        <div class="setting-status-row__chips">
          <span class="setting-status-chip">{{ activeTabName ? '模块已定位' : '待选择模块' }}</span>
          <span class="setting-status-chip">登录注册 {{ loginTabCount }}</span>
          <span class="setting-status-chip">安全鉴权 {{ securityTabCount }}</span>
          <span class="setting-status-chip">接口日志 {{ operationTabCount }}</span>
        </div>
        <div class="setting-status-row__actions">
          <el-button text type="primary" @click="goTo(primaryAction.path)">{{ primaryAction.label }}</el-button>
          <el-button text type="primary" @click="goTo(secondaryAction.path)">{{ secondaryAction.label }}</el-button>
          <el-button text type="primary" @click="goTo(tertiaryAction.path)">{{ tertiaryAction.label }}</el-button>
        </div>
      </div>
      <div class="setting-plain-guide">
        <div class="setting-plain-guide__header">
          <div>
            <div class="setting-plain-guide__title">会员设置中心第一次进来，建议先这样看</div>
            <div class="setting-plain-guide__desc">先分清是会员规则、登录注册、第三方绑定，还是安全鉴权问题，再切到对应标签处理。</div>
          </div>
          <div class="setting-plain-guide__badge">{{ settingGuideFocusLabel }}</div>
        </div>
        <div class="setting-plain-guide__grid">
          <div v-for="item in settingGuideCards" :key="item.title" class="setting-plain-guide-card">
            <span class="setting-plain-guide-card__step">{{ item.step }}</span>
            <div class="setting-plain-guide-card__title">{{ item.title }}</div>
            <div class="setting-plain-guide-card__desc">{{ item.desc }}</div>
          </div>
        </div>
      </div>
      <div class="setting-plain-guide setting-plain-guide--radar">
        <div class="setting-plain-guide__header">
          <div>
            <div class="setting-plain-guide__title">会员配置总控视角</div>
            <div class="setting-plain-guide__desc">
              先看当前标签会影响哪条链路、改完第一站去哪验，再进入具体表单，会更接近真实运营排查顺序。
            </div>
          </div>
          <div class="setting-plain-guide__badge">{{ currentVerificationLabel }}</div>
        </div>
        <div class="setting-overview setting-overview--radar">
          <div
            v-for="item in memberImpactCards"
            :key="item.label"
            class="setting-overview__card setting-overview__card--radar"
          >
            <span class="setting-overview__label">{{ item.label }}</span>
            <strong>{{ item.value }}</strong>
            <em>{{ item.desc }}</em>
          </div>
        </div>
        <div class="setting-status-row__actions setting-status-row__actions--radar">
          <el-button text type="primary" @click="openMemberLogin">打开 H5 登录页</el-button>
          <el-button text type="primary" @click="goTo('/member/log')">去会员日志</el-button>
          <el-button text type="primary" @click="goTo('/member/member')">去会员列表</el-button>
        </div>
      </div>
      <div class="setting-followup-grid">
        <button
          v-for="item in followupCards"
          :key="item.title"
          type="button"
          class="setting-followup-card"
          @click="goTo(item.path, item.extraQuery)"
        >
          <span class="setting-followup-card__title">{{ item.title }}</span>
          <span class="setting-followup-card__desc">{{ item.desc }}</span>
        </button>
      </div>
      <el-tabs v-model="activeTabName" @tab-change="handleTabChange">
      <el-tab-pane
        v-if="checkPermission(['admin/member.Setting/memberInfo'])"
        label="会员设置"
        name="memberInfo"
        lazy
      >
        <SettingMember />
      </el-tab-pane>
      <el-tab-pane
        v-if="checkPermission(['admin/member.Setting/logregInfo'])"
        label="登录注册设置"
        name="logregInfo"
        lazy
      >
        <SettingLogreg />
      </el-tab-pane>
      <el-tab-pane
        v-if="checkPermission(['admin/member.Setting/thirdInfo'])"
        label="第三方账号设置"
        name="thirdInfo"
        lazy
      >
        <SettingThird />
      </el-tab-pane>
      <el-tab-pane
        v-if="checkPermission(['admin/member.Setting/captchaInfo'])"
        label="验证码设置"
        name="captchaInfo"
        lazy
      >
        <SettingCaptcha />
      </el-tab-pane>
      <el-tab-pane
        v-if="checkPermission(['admin/member.Setting/tokenInfo'])"
        label="Token设置"
        name="tokenInfo"
        lazy
      >
        <SettingToken />
      </el-tab-pane>
      <el-tab-pane
        v-if="checkPermission(['admin/member.Setting/logInfo'])"
        label="日志设置"
        name="logInfo"
        lazy
      >
        <SettingLog />
      </el-tab-pane>
      <el-tab-pane
        v-if="checkPermission(['admin/member.Setting/apiInfo'])"
        label="接口设置"
        name="apiInfo"
        lazy
      >
        <SettingApi />
      </el-tab-pane>
    </el-tabs>
    </el-card>
  </div>
</template>

<script>
import checkPermission from '@/utils/permission'
import SettingApi from './components/SettingApi.vue'
import SettingCaptcha from './components/SettingCaptcha.vue'
import SettingLog from './components/SettingLog.vue'
import SettingLogreg from './components/SettingLogreg.vue'
import SettingMember from './components/SettingMember.vue'
import SettingThird from './components/SettingThird.vue'
import SettingToken from './components/SettingToken.vue'

export default {
  name: 'MemberSetting',
  components: {
    SettingApi,
    SettingCaptcha,
    SettingLog,
    SettingLogreg,
    SettingMember,
    SettingThird,
    SettingToken
  },
  data() {
    return {
      name: '会员设置',
      activeTabName: ''
    }
  },
  computed: {
    entrySourceLabel() {
      const source = this.$route?.query?.from
      if (source === 'member-api') return '来自会员接口'
      if (source === 'member-log') return '来自会员日志'
      if (source === 'member-third') return '来自第三方账号'
      if (source === 'dashboard') return '来自后台首页'
      return ''
    },
    entryContextVisible() {
      return Boolean(this.entrySourceLabel)
    },
    entryContextTitle() {
      if (this.entrySourceLabel === '来自会员接口') return '当前从会员接口进入会员设置'
      if (this.entrySourceLabel === '来自会员日志') return '当前从会员日志进入会员设置'
      if (this.entrySourceLabel === '来自第三方账号') return '当前从第三方账号进入会员设置'
      if (this.entrySourceLabel === '来自后台首页') return '当前从后台首页进入会员设置'
      return '当前为外部入口承接视角'
    },
    entryContextDesc() {
      if (this.entrySourceLabel === '来自会员接口') {
        return '这类进入通常是为了把接口、Token 或验证码问题落到具体配置项。建议先只处理当前标签，再回会员接口页确认真实回显。'
      }
      if (this.entrySourceLabel === '来自会员日志') {
        return '这类进入通常是为了追某次会员行为异常背后的配置原因。建议先看日志对应链路，再回日志页确认异常是否收敛。'
      }
      if (this.entrySourceLabel === '来自第三方账号') {
        return '这类进入通常是为了把第三方绑定问题落到配置页。建议先锁定第三方账号设置，再回第三方账号页看绑定状态是否恢复正常。'
      }
      return '这类进入通常是首页巡检后的继续下钻。建议先分清当前问题属于登录、鉴权、第三方还是会员规则，再只改一类配置。'
    },
    entryContextPrimaryLabel() {
      if (this.entrySourceLabel === '来自会员接口') return '回会员接口'
      if (this.entrySourceLabel === '来自会员日志') return '回会员日志'
      if (this.entrySourceLabel === '来自第三方账号') return '回第三方账号'
      return '回后台首页'
    },
    visibleTabs() {
      return [
        { name: 'memberInfo', label: '会员设置', permissions: ['admin/member.Setting/memberInfo'] },
        { name: 'logregInfo', label: '登录注册设置', permissions: ['admin/member.Setting/logregInfo'] },
        { name: 'thirdInfo', label: '第三方账号设置', permissions: ['admin/member.Setting/thirdInfo'] },
        { name: 'captchaInfo', label: '验证码设置', permissions: ['admin/member.Setting/captchaInfo'] },
        { name: 'tokenInfo', label: 'Token设置', permissions: ['admin/member.Setting/tokenInfo'] },
        { name: 'logInfo', label: '日志设置', permissions: ['admin/member.Setting/logInfo'] },
        { name: 'apiInfo', label: '接口设置', permissions: ['admin/member.Setting/apiInfo'] }
      ].filter((item) => this.checkPermission(item.permissions))
    },
    visibleTabCount() {
      return this.visibleTabs.length
    },
    currentTabText() {
      const current = this.visibleTabs.find((item) => item.name === this.activeTabName)
      return current ? current.label : '未选择模块'
    },
    loginTabCount() {
      return this.visibleTabs.filter((item) =>
        ['memberInfo', 'logregInfo', 'thirdInfo'].includes(item.name)
      ).length
    },
    securityTabCount() {
      return this.visibleTabs.filter((item) =>
        ['captchaInfo', 'tokenInfo'].includes(item.name)
      ).length
    },
    operationTabCount() {
      return this.visibleTabs.filter((item) =>
        ['logInfo', 'apiInfo'].includes(item.name)
      ).length
    },
    currentFlowType() {
      if (['memberInfo', 'logregInfo', 'thirdInfo'].includes(this.activeTabName)) {
        return '会员资料 / 登录注册'
      }
      if (['captchaInfo', 'tokenInfo'].includes(this.activeTabName)) {
        return '安全鉴权 / 风控拦截'
      }
      if (['logInfo', 'apiInfo'].includes(this.activeTabName)) {
        return '接口调用 / 行为日志'
      }
      return '待确认链路'
    },
    currentVerificationLabel() {
      const map = {
        memberInfo: '先回会员列表看展示和默认值',
        logregInfo: '先回 H5 登录页试注册登录',
        thirdInfo: '先回第三方账号页和授权链路',
        captchaInfo: '先回 H5 登录页和日志看是否误拦',
        tokenInfo: '先回登录态和接口鉴权链路',
        logInfo: '先回会员日志确认记录是否完整',
        apiInfo: '先回高频页面确认是否误限流'
      }
      return map[this.activeTabName] || '先选一个标签再开始验证'
    },
    memberImpactCards() {
      return [
        {
          label: '当前标签',
          value: this.currentTabText,
          desc: '本轮建议只改这一类设置。'
        },
        {
          label: '影响链路',
          value: this.currentFlowType,
          desc: '用来判断接下来优先去哪复核。'
        },
        {
          label: '首要验收',
          value: this.currentVerificationLabel,
          desc: '改完第一站就先走这条真实链路。'
        },
        {
          label: '环境定位',
          value: '本地联调后台',
          desc: '当前更适合先做行为和日志回归。'
        }
      ]
    },
    settingGuideFocusLabel() {
      if (this.activeTabName === 'thirdInfo') {
        return '当前重点：先看第三方绑定配置，再去第三方账号页核对真实绑定'
      }
      if (this.activeTabName === 'apiInfo' || this.activeTabName === 'tokenInfo' || this.activeTabName === 'captchaInfo') {
        return '当前重点：先确认安全策略，再去日志和接口页验证实际生效'
      }
      if (this.activeTabName) {
        return `当前重点：先处理 ${this.currentTabText}，再去会员页或日志页回看结果`
      }
      return '当前重点：先定位问题属于哪一类，再切对应设置标签'
    },
    settingGuideCards() {
      return [
        {
          step: '第一步',
          title: '先判断问题属于哪条链路',
          desc: '会员资料问题看会员设置，登录问题看登录注册，微信/外部账号问题看第三方设置，安全拦截问题看验证码和 Token。'
        },
        {
          step: '第二步',
          title: '再只改一个模块，不要混着动',
          desc: '会员设置中心标签多，但每次最好只改一块，避免改完后不知道是哪一项引发结果变化。'
        },
        {
          step: '第三步',
          title: '改完立即去会员页、日志页或第三方页复核',
          desc: '设置页本身只负责规则，最终还是要回到真实会员数据和日志看是否已经生效。'
        }
      ]
    },
    tabActionMap() {
      return {
        memberInfo: [
          { label: '去会员管理', path: '/member/member' },
          { label: '去会员统计', path: '/member/statistic' },
          { label: '去会员日志', path: '/member/log' }
        ],
        logregInfo: [
          { label: '去会员管理', path: '/member/member' },
          { label: '去会员统计', path: '/member/statistic' },
          { label: '去第三方账号', path: '/member/third' }
        ],
        thirdInfo: [
          { label: '去第三方账号', path: '/member/third' },
          { label: '去会员管理', path: '/member/member' },
          { label: '去会员日志', path: '/member/log' }
        ],
        captchaInfo: [
          { label: '去会员日志', path: '/member/log' },
          { label: '去会员接口', path: '/member/api' },
          { label: '去会员管理', path: '/member/member' }
        ],
        tokenInfo: [
          { label: '去会员接口', path: '/member/api' },
          { label: '去会员日志', path: '/member/log' },
          { label: '去会员管理', path: '/member/member' }
        ],
        logInfo: [
          { label: '去会员日志', path: '/member/log' },
          { label: '去会员管理', path: '/member/member' },
          { label: '去会员统计', path: '/member/statistic' }
        ],
        apiInfo: [
          { label: '去会员接口', path: '/member/api' },
          { label: '去会员日志', path: '/member/log' },
          { label: '去会员管理', path: '/member/member' }
        ]
      }
    },
    currentTabActions() {
      return (
        this.tabActionMap[this.activeTabName] || [
          { label: '去会员管理', path: '/member/member' },
          { label: '去会员统计', path: '/member/statistic' },
          { label: '去会员日志', path: '/member/log' }
        ]
      )
    },
    primaryAction() {
      return this.currentTabActions[0]
    },
    secondaryAction() {
      return this.currentTabActions[1]
    },
    tertiaryAction() {
      return this.currentTabActions[2]
    },
    followupCards() {
      const map = {
        memberInfo: [
          { title: '去会员管理核对', path: '/member/member', desc: '改完会员规则后，直接去会员列表看状态、分组和标签是否符合预期。' },
          { title: '去会员统计复盘', path: '/member/statistic', desc: '适合回看整体趋势，确认配置调整后没有放大异常波动。' },
          { title: '去会员标签承接', path: '/member/tag', desc: '把当前人群规则继续落到标签侧，方便运营后续承接。' }
        ],
        logregInfo: [
          { title: '去会员日志排查', path: '/member/log', desc: '登录注册配置改完后，优先去日志页核对注册、登录和访问变化。' },
          { title: '去第三方账号核对', path: '/member/third', desc: '如果涉及授权登录，继续看第三方账号绑定情况是否正常。' },
          { title: '去会员统计复盘', path: '/member/statistic', desc: '观察短期趋势，确认注册与活跃走势没有异常。 ' }
        ],
        thirdInfo: [
          { title: '去第三方账号页', path: '/member/third', desc: '改完第三方账号配置后，先看绑定、禁用和平台分布是否正常。' },
          { title: '去会员日志排查', path: '/member/log', desc: '若授权登录异常，继续去日志页对照行为记录。' },
          { title: '去会员管理核人', path: '/member/member', desc: '需要落到具体会员时，直接去会员列表继续核对。' }
        ],
        captchaInfo: [
          { title: '去会员日志排查', path: '/member/log', desc: '验证码配置改完后，最先去日志页确认有没有拦截异常。' },
          { title: '去会员接口核对', path: '/member/api', desc: '怀疑接口链路或权限例外时，继续看会员接口页。' },
          { title: '去会员统计复盘', path: '/member/statistic', desc: '回看短期趋势，确认验证码调整没有压坏转化。' }
        ],
        tokenInfo: [
          { title: '去会员接口核对', path: '/member/api', desc: 'Token 配置最适合继续核接口页，确认鉴权规则和例外是否一致。' },
          { title: '去会员日志排查', path: '/member/log', desc: '如果登录态异常，继续看日志定位是鉴权还是行为问题。' },
          { title: '去会员管理核人', path: '/member/member', desc: '需要落到具体用户时，直接回会员列表继续核查。' }
        ],
        logInfo: [
          { title: '去会员日志', path: '/member/log', desc: '日志相关配置改完后，直接回日志页看回显和筛选结果最直接。' },
          { title: '去会员统计复盘', path: '/member/statistic', desc: '从趋势页回看近期变化，方便判断是不是批量异常。' },
          { title: '去会员管理核人', path: '/member/member', desc: '遇到具体用户问题时，回会员列表继续处理。' }
        ],
        apiInfo: [
          { title: '去会员接口页', path: '/member/api', desc: '接口设置改完后，优先回接口页核对结构、禁用和鉴权例外。' },
          { title: '去会员日志排查', path: '/member/log', desc: '需要确认调用结果时，继续看日志最稳妥。' },
          { title: '去会员管理核人', path: '/member/member', desc: '需要把异常落到具体用户时，直接去会员列表。' }
        ]
      }
      return map[this.activeTabName] || [
        { title: '去会员管理', path: '/member/member', desc: '先回会员列表继续处理。' },
        { title: '去会员统计', path: '/member/statistic', desc: '需要回看整体趋势时，从这里继续。' },
        { title: '去会员日志', path: '/member/log', desc: '需要排查行为记录时，直接去日志页。' }
      ]
    }
  },
  created() {
    const routeTab = String(this.$route.query.tab || '').trim()
    const matchedTab = this.visibleTabs.find((item) => item.name === routeTab)
    this.activeTabName = matchedTab?.name || this.visibleTabs[0]?.name || ''
  },
  methods: {
    checkPermission,
    buildEntryRouteQuery(extraQuery = {}, nextFrom = 'member-setting') {
      return {
        ...this.$route.query,
        ...extraQuery,
        from: nextFrom
      }
    },
    handleEntryContextPrimary() {
      if (this.entrySourceLabel === '来自会员接口') {
        this.goTo('/member/api')
        return
      }
      if (this.entrySourceLabel === '来自会员管理') {
        this.goTo('/member/member')
        return
      }
      if (this.entrySourceLabel === '来自会员日志') {
        this.goTo('/member/log')
        return
      }
      if (this.entrySourceLabel === '来自会员分组') {
        this.goTo('/member/group')
        return
      }
      if (this.entrySourceLabel === '来自会员标签') {
        this.goTo('/member/tag')
        return
      }
      if (this.entrySourceLabel === '来自会员统计') {
        this.goTo('/member/statistic')
        return
      }
      if (this.entrySourceLabel === '来自第三方账号') {
        this.goTo('/member/third')
        return
      }
      this.$router.push({
        path: '/dashboard',
        query: this.buildEntryRouteQuery({}, 'member-setting')
      })
    },
    goToEntryContextBack() {
      this.handleEntryContextPrimary()
    },
    handleTabChange(name) {
      const current = this.visibleTabs.find((item) => item.name === name)
      if (!current) {
        return
      }
      this.$router.replace({
        name: this.$route.name,
        query: {
          ...this.$route.query,
          tab: current.name
        }
      })
    },
    goTo(path, extraQuery = {}) {
      this.$router.push({
        path,
        query: this.buildEntryRouteQuery({
          setting_tab: this.activeTabName,
          ...extraQuery
        }, 'member-setting')
      })
    },
    openMemberLogin() {
      window.open(`${window.location.origin}/app/pages/my/login`, '_blank', 'noopener')
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
  margin-bottom: 16px;
}

.entry-context-banner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 14px;
  padding: 14px 16px;
  border-radius: 12px;
  background: linear-gradient(135deg, #f5f7ff 0%, #fffaf0 100%);
  border: 1px solid #e5e7eb;
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
  font-weight: 700;
  color: #0f172a;
}

.entry-context-banner__desc {
  font-size: 13px;
  line-height: 1.6;
  color: #64748b;
}

.entry-context-banner__actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  justify-content: flex-end;
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

.setting-overview {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 12px;
  margin-bottom: 16px;
}

.setting-overview__card {
  border: 1px solid #e6ecf5;
  border-radius: 12px;
  padding: 14px 16px;
  background: linear-gradient(180deg, #f9fbff 0%, #ffffff 100%);
  box-shadow: 0 6px 18px rgba(15, 35, 95, 0.05);
}

.setting-overview__label {
  display: block;
  margin-bottom: 8px;
  font-size: 12px;
  color: #7c8aa5;
}

.setting-status-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 16px;
}

.setting-status-row__chips,
.setting-status-row__actions {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
}

.setting-status-chip {
  display: inline-flex;
  align-items: center;
  padding: 4px 10px;
  border-radius: 999px;
  background: #f8fafc;
  border: 1px solid #dbe5f1;
  color: #475569;
  font-size: 12px;
}

.setting-plain-guide {
  margin-bottom: 16px;
  padding: 16px;
  border: 1px solid #dbe7f5;
  border-radius: 16px;
  background: linear-gradient(135deg, #f7fbff 0%, #ffffff 100%);
}

.setting-plain-guide__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.setting-plain-guide__title {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
}

.setting-plain-guide__desc {
  margin-top: 6px;
  font-size: 12px;
  color: #64748b;
  line-height: 1.7;
}

.setting-plain-guide__badge {
  min-width: 220px;
  padding: 10px 12px;
  border-radius: 12px;
  background: rgba(37, 99, 235, 0.08);
  color: #1d4ed8;
  font-size: 12px;
  font-weight: 700;
  line-height: 1.6;
}

.setting-plain-guide__grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-top: 14px;
}

.setting-plain-guide--radar {
  margin-top: -4px;
}

.setting-plain-guide-card {
  padding: 14px 16px;
  border: 1px solid #e7edf5;
  border-radius: 14px;
  background: #fff;
}

.setting-plain-guide-card__step {
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

.setting-plain-guide-card__title {
  margin-top: 10px;
  font-size: 14px;
  font-weight: 700;
  color: #0f172a;
}

.setting-plain-guide-card__desc {
  margin-top: 8px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.setting-followup-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 10px;
  margin-bottom: 16px;
}

.setting-followup-card {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 6px;
  min-height: 104px;
  padding: 12px 14px;
  text-align: left;
  border: 1px solid #dbe5f1;
  border-radius: 12px;
  background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
  transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
}

.setting-followup-card:hover {
  transform: translateY(-1px);
  border-color: rgba(37, 99, 235, 0.28);
  box-shadow: 0 12px 28px rgba(37, 99, 235, 0.08);
}

.setting-followup-card__title {
  font-size: 14px;
  font-weight: 700;
  color: #0f172a;
}

.setting-followup-card__desc {
  font-size: 12px;
  line-height: 1.6;
  color: #64748b;
}

.setting-overview--radar {
  margin-top: 14px;
  margin-bottom: 0;
}

.setting-overview__card--radar {
  gap: 6px;
}

.setting-overview__card--radar em {
  display: block;
  margin-top: 4px;
  font-style: normal;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.setting-status-row__actions--radar {
  margin-top: 14px;
}

@media (max-width: 900px) {
  .entry-context-banner,
  .section-title-row,
  .setting-plain-guide__header {
    flex-direction: column;
  }

  .section-title-row__meta {
    white-space: normal;
  }

  .setting-status-row {
    flex-direction: column;
    align-items: flex-start;
  }

  .setting-plain-guide__badge {
    min-width: 0;
  }

  .setting-plain-guide__grid,
  .setting-followup-grid {
    grid-template-columns: 1fr;
  }
}
</style>
