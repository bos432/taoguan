<template>
  <div class="app-container">
    <el-card class="app-head">
      <div class="setting-page-head">
        <div>
          <div class="setting-page-head__title">系统设置</div>
          <div class="setting-page-head__desc">{{ runtimeHint }}</div>
        </div>
        <span class="setting-page-head__tag">{{ runtimeEnvInfo.label }}</span>
      </div>

      <div class="setting-summary-bar">
        <div class="setting-summary-bar__item">
          <span class="setting-summary-bar__label">模块数量</span>
          <strong>{{ visibleTabCount }}</strong>
        </div>
        <div class="setting-summary-bar__item">
          <span class="setting-summary-bar__label">当前栏目</span>
          <strong>系统设置中心</strong>
        </div>
        <div class="setting-summary-bar__item">
          <span class="setting-summary-bar__label">当前标签</span>
          <strong>{{ currentTabText }}</strong>
        </div>
        <div class="setting-summary-bar__item">
          <span class="setting-summary-bar__label">数据模式</span>
          <strong>{{ runtimeEnvInfo.dataMode }}</strong>
        </div>
        <div class="setting-summary-bar__item">
          <span class="setting-summary-bar__label">最近操作</span>
          <strong>{{ recentActionText }}</strong>
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

      <div class="setting-status-row">
        <div class="setting-status-row__chips">
          <span class="setting-status-chip">{{ systemFollowupBadgeText }}</span>
          <span class="setting-status-chip">安全类 {{ securityTabCount }}</span>
          <span class="setting-status-chip">运维类 {{ runtimeTabCount }}</span>
          <span class="setting-status-chip">按标签独立保存</span>
        </div>
        <div class="setting-status-row__tools">
          <div class="setting-status-row__hint">{{ compactFollowupHint }}</div>
          <div class="setting-status-row__actions">
            <el-button text type="primary" @click="goTo(primaryAction.path)">{{ primaryAction.label }}</el-button>
            <el-button text type="primary" @click="goTo(secondaryAction.path)">{{ secondaryAction.label }}</el-button>
            <el-button text type="primary" @click="goTo(tertiaryAction.path)">{{ tertiaryAction.label }}</el-button>
          </div>
        </div>
      </div>
      <div class="plain-guide">
        <div class="plain-guide__header">
          <div>
            <div class="plain-guide__title">这页建议先这样看</div>
            <div class="plain-guide__desc">
              系统设置不要把它当成一个大表单，而要当成“按风险分开的配置中心”。先判断这次改的是基础系统、安全校验，还是接口与运行配置，再切到对应标签逐项保存。
            </div>
          </div>
          <span class="plain-guide__badge">{{ settingFocusLabel }}</span>
        </div>
        <div class="plain-guide__grid">
          <div v-for="item in settingGuideCards" :key="item.title" class="plain-guide-card">
            <div class="plain-guide-card__title">{{ item.title }}</div>
            <div class="plain-guide-card__desc">{{ item.desc }}</div>
            <div class="plain-guide-card__action">{{ item.action }}</div>
          </div>
        </div>
      </div>
      <div class="current-tab-guide">
        <div class="current-tab-guide__main">
          <div class="current-tab-guide__label">当前标签建议</div>
          <div class="current-tab-guide__title">{{ currentTabGuide.title }}</div>
          <div class="current-tab-guide__desc">{{ currentTabGuide.desc }}</div>
        </div>
        <div class="current-tab-guide__tags">
          <span v-for="item in currentTabGuide.tags" :key="item">{{ item }}</span>
        </div>
      </div>
      <div class="current-tab-guide__grid">
        <div v-for="item in currentTabGuide.steps" :key="item.title" class="current-tab-guide-card">
          <div class="current-tab-guide-card__title">{{ item.title }}</div>
          <div class="current-tab-guide-card__desc">{{ item.desc }}</div>
          <div class="current-tab-guide-card__action">{{ item.action }}</div>
        </div>
      </div>
      <div class="plain-guide plain-guide--radar">
        <div class="plain-guide__header">
          <div>
            <div class="plain-guide__title">系统配置总控视角</div>
            <div class="plain-guide__desc">
              先看这次会影响登录、安全、接口还是运行环境，再决定复核顺序，避免只在标签里切换。
            </div>
          </div>
          <span class="plain-guide__badge">{{ systemVerificationLabel }}</span>
        </div>
        <div class="setting-summary-bar setting-summary-bar--radar">
          <div
            v-for="item in systemImpactCards"
            :key="item.label"
            class="setting-summary-bar__item setting-summary-bar__item--radar"
          >
            <span class="setting-summary-bar__label">{{ item.label }}</span>
            <strong>{{ item.value }}</strong>
            <em>{{ item.desc }}</em>
          </div>
        </div>
        <div class="setting-status-row__actions setting-status-row__actions--radar">
          <el-button text type="primary" @click="openAdminLogin">打开后台登录页</el-button>
          <el-button text type="primary" @click="goTo('/system/user-log')">去操作日志</el-button>
          <el-button text type="primary" @click="goTo('/system/apidoc')">去接口文档</el-button>
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
          v-if="checkPermission(['admin/system.Setting/systemInfo'])"
          label="系统设置"
          name="systemInfo"
          lazy
        >
          <SettingSystems />
        </el-tab-pane>
        <el-tab-pane
          v-if="checkPermission(['admin/system.Setting/captchaInfo'])"
          label="验证码设置"
          name="captchaInfo"
          lazy
        >
          <SettingCaptchas />
        </el-tab-pane>
        <el-tab-pane
          v-if="checkPermission(['admin/system.Setting/cacheInfo'])"
          label="缓存设置"
          name="cacheInfo"
          lazy
        >
          <SettingCaches />
        </el-tab-pane>
        <el-tab-pane
          v-if="checkPermission(['admin/system.Setting/tokenInfo'])"
          label="Token设置"
          name="tokenInfo"
          lazy
        >
          <SettingTokens />
        </el-tab-pane>
        <el-tab-pane
          v-if="checkPermission(['admin/system.Setting/logInfo'])"
          label="日志设置"
          name="logInfo"
          lazy
        >
          <SettingLogs />
        </el-tab-pane>
        <el-tab-pane
          v-if="checkPermission(['admin/system.Setting/apiInfo'])"
          label="接口设置"
          name="apiInfo"
          lazy
        >
          <SettingApis />
        </el-tab-pane>
        <el-tab-pane
          v-if="checkPermission(['admin/system.Setting/emailInfo'])"
          label="邮件设置"
          name="emailInfo"
          lazy
        >
          <SettingEmails />
        </el-tab-pane>
        <el-tab-pane
          v-if="checkPermission(['admin/system.Setting/serverInfo'])"
          label="服务器信息"
          name="serverInfo"
          lazy
        >
          <SettingServers />
        </el-tab-pane>
      </el-tabs>
    </el-card>
  </div>
</template>

<script>
import checkPermission from '@/utils/permission'
import { getAdminRuntimeHint, resolveAdminRuntimeEnv } from '@/utils/runtime-env'
import SettingApis from './components/SettingApis.vue'
import SettingCaches from './components/SettingCaches.vue'
import SettingCaptchas from './components/SettingCaptchas.vue'
import SettingEmails from './components/SettingEmails.vue'
import SettingLogs from './components/SettingLogs.vue'
import SettingServers from './components/SettingServers.vue'
import SettingSystems from './components/SettingSystems.vue'
import SettingTokens from './components/SettingTokens.vue'

export default {
  name: 'SystemSetting',
  components: {
    SettingApis,
    SettingCaches,
    SettingCaptchas,
    SettingEmails,
    SettingLogs,
    SettingServers,
    SettingSystems,
    SettingTokens
  },
  data() {
    return {
      name: '系统设置',
      runtimeEnvInfo: resolveAdminRuntimeEnv(),
      activeTabName: '',
      recentActionSummary: '请先选择一个系统设置模块，再进行参数核对与保存。'
    }
  },
  computed: {
    securityTabCount() {
      return this.visibleTabs.filter((item) =>
        ['systemInfo', 'captchaInfo', 'tokenInfo'].includes(item.name)
      ).length
    },
    runtimeTabCount() {
      return this.visibleTabs.filter((item) =>
        ['cacheInfo', 'logInfo', 'apiInfo', 'emailInfo', 'serverInfo'].includes(item.name)
      ).length
    },
    visibleTabCount() {
      const permissionGroups = [
        ['admin/system.Setting/systemInfo'],
        ['admin/system.Setting/captchaInfo'],
        ['admin/system.Setting/cacheInfo'],
        ['admin/system.Setting/tokenInfo'],
        ['admin/system.Setting/logInfo'],
        ['admin/system.Setting/apiInfo'],
        ['admin/system.Setting/emailInfo'],
        ['admin/system.Setting/serverInfo']
      ]
      return permissionGroups.filter((permissions) => this.checkPermission(permissions)).length
    },
    runtimeHint() {
      return getAdminRuntimeHint(this.runtimeEnvInfo)
    },
    entrySourceLabel() {
      const source = String(this.$route.query.from || '')
      if (source === 'dashboard') {
        return '来自控制台总览'
      }
      if (source === 'legacy-module') {
        return '来自旧页承接'
      }
      if (source === 'system-apidoc') {
        return '来自接口文档'
      }
      if (source === 'system-user-center') {
        return '来自个人中心'
      }
      if (source === 'system-user-log') {
        return '来自用户日志'
      }
      return ''
    },
    entryContextVisible() {
      return Boolean(this.entrySourceLabel)
    },
    entryContextTitle() {
      if (this.entrySourceLabel === '来自控制台总览') {
        return '当前从控制台进入系统设置中心'
      }
      if (this.entrySourceLabel === '来自旧页承接') {
        return '当前从旧页桥接回系统设置中心'
      }
      if (this.entrySourceLabel === '来自接口文档') {
        return '当前从接口文档回到系统设置中心'
      }
      if (this.entrySourceLabel === '来自个人中心') {
        return '当前从个人中心回到系统设置中心'
      }
      if (this.entrySourceLabel === '来自用户日志') {
        return '当前从日志排查入口进入系统设置中心'
      }
      return '当前为外部入口承接视角'
    },
    entryContextDesc() {
      if (this.entrySourceLabel === '来自控制台总览') {
        return '更适合先按影响面选标签：登录与安全问题看系统/验证码/Token，接口和运行问题看日志/接口/服务器。'
      }
      if (this.entrySourceLabel === '来自旧页承接') {
        return '这说明旧入口已经把你带回新后台。现在更适合直接在当前标签完成配置复核，不要再回旧后台反复找入口。'
      }
      if (this.entrySourceLabel === '来自接口文档') {
        return '这类返回通常是为了确认文档地址、访问口令和系统配置是不是同一套环境。建议先处理当前标签，再回文档页继续联调。'
      }
      if (this.entrySourceLabel === '来自个人中心') {
        return '这类返回通常是为了把当前登录人的体验问题，重新落到全局配置上判断。建议先核系统或安全相关标签，再决定是否回个人中心继续复测。'
      }
      if (this.entrySourceLabel === '来自用户日志') {
        return '这类进入通常是为了继续核配置是否影响了日志、鉴权或接口。建议先处理相关标签，再回日志页复核结果。'
      }
      return '当前页负责按模块复核系统配置。'
    },
    entryContextPrimaryLabel() {
      if (this.entrySourceLabel === '来自控制台总览') {
        return '去高影响标签'
      }
      if (this.entrySourceLabel === '来自接口文档') {
        return '回接口文档'
      }
      if (this.entrySourceLabel === '来自个人中心') {
        return '回个人中心'
      }
      if (this.entrySourceLabel === '来自用户日志') {
        return '回日志继续排查'
      }
      return '去操作日志'
    },
    visibleTabs() {
      return [
        { name: 'systemInfo', label: '系统设置', permissions: ['admin/system.Setting/systemInfo'] },
        {
          name: 'captchaInfo',
          label: '验证码设置',
          permissions: ['admin/system.Setting/captchaInfo']
        },
        { name: 'cacheInfo', label: '缓存设置', permissions: ['admin/system.Setting/cacheInfo'] },
        { name: 'tokenInfo', label: 'Token设置', permissions: ['admin/system.Setting/tokenInfo'] },
        { name: 'logInfo', label: '日志设置', permissions: ['admin/system.Setting/logInfo'] },
        { name: 'apiInfo', label: '接口设置', permissions: ['admin/system.Setting/apiInfo'] },
        { name: 'emailInfo', label: '邮件设置', permissions: ['admin/system.Setting/emailInfo'] },
        {
          name: 'serverInfo',
          label: '服务器信息',
          permissions: ['admin/system.Setting/serverInfo']
        }
      ].filter((item) => this.checkPermission(item.permissions))
    },
    currentTabText() {
      const current = this.visibleTabs.find((item) => item.name === this.activeTabName)
      return current ? current.label : '未选择'
    },
    recentActionText() {
      return this.recentActionSummary.replace(/^已/, '')
    },
    currentImpactType() {
      if (['systemInfo', 'captchaInfo', 'tokenInfo'].includes(this.activeTabName)) {
        return '登录 / 鉴权 / 安全校验'
      }
      if (['cacheInfo', 'logInfo', 'apiInfo', 'emailInfo', 'serverInfo'].includes(this.activeTabName)) {
        return '接口调用 / 运行配置 / 运维联调'
      }
      return '待确认影响面'
    },
    systemVerificationLabel() {
      const map = {
        systemInfo: '先回控制台和日志看全局表现',
        captchaInfo: '先回后台登录页试验证码',
        cacheInfo: '先回控制台和高频页看缓存表现',
        tokenInfo: '先回登录态和接口鉴权链路',
        logInfo: '先回操作日志确认记录与筛选',
        apiInfo: '先回高频接口链路看是否误限流',
        emailInfo: '先确认发信配置和通知链路',
        serverInfo: '先核环境信息，不直接改业务行为'
      }
      return map[this.activeTabName] || '先选一个标签再开始复核'
    },
    systemImpactCards() {
      return [
        {
          label: '当前标签',
          value: this.currentTabText,
          desc: '本轮建议只处理这一组参数。'
        },
        {
          label: '影响范围',
          value: this.currentImpactType,
          desc: '用来决定先验登录、日志还是接口。'
        },
        {
          label: '首要验收',
          value: this.systemVerificationLabel,
          desc: '改完后优先走这一条真实链路。'
        },
        {
          label: '当前环境',
          value: `${this.runtimeEnvInfo.label} / ${this.runtimeEnvInfo.dataMode}`,
          desc: '先确认环境，再做高影响配置保存。'
        }
      ]
    },
    systemFollowupBadgeText() {
      return this.activeTabName ? '模块已就绪' : '待选择模块'
    },
    compactFollowupHint() {
      if (this.entrySourceLabel === '来自控制台总览') {
        return '当前从控制台进入，建议按影响面选一个标签后立即保存并跨页复核。'
      }
      return this.runtimeEnvInfo.isProd
        ? '正式环境请按模块保存并逐项回查高影响配置。'
        : '当前环境建议逐个模块核对回显与保存效果。'
    },
    settingFocusLabel() {
      if (this.entrySourceLabel === '来自用户日志') {
        return '先看日志关联配置'
      }
      if (this.entrySourceLabel === '来自控制台总览') {
        return '先按影响面选模块'
      }
      if (!this.activeTabName) {
        return '先选一个模块'
      }
      if (['systemInfo', 'captchaInfo', 'tokenInfo'].includes(this.activeTabName)) {
        return '先看高影响配置'
      }
      return '先看运行与联调配置'
    },
    settingGuideCards() {
      if (this.entrySourceLabel === '来自控制台总览') {
        return [
          {
            title: '第一步：先判断问题落在安全还是运行链路',
            desc: '控制台异常过来时，不要先随便点标签。登录、验证码、Token 算高影响；日志、接口、服务器更偏运行与联调。',
            action: `安全类 ${this.securityTabCount} 个，运维类 ${this.runtimeTabCount} 个。`
          },
          {
            title: '第二步：只处理一个标签并立刻保存',
            desc: '这类页最怕“顺手多改几项”。一次只收口一组参数，后面跨页复核时更容易判断影响。',
            action: `当前标签：${this.currentTabText}。`
          },
          {
            title: '第三步：立刻回日志、文档或控制台复核',
            desc: '系统设置页本身不能证明配置已经生效，必须回真实链路看登录、日志和接口表现。',
            action: this.compactFollowupHint
          }
        ]
      }
      return [
        {
          title: '第一步：先判断改哪一类配置',
          desc: '系统、验证码、Token 这类更偏高影响；缓存、日志、接口、邮件、服务器更偏运行与联调。',
          action: `安全类 ${this.securityTabCount} 个，运维类 ${this.runtimeTabCount} 个。`
        },
        {
          title: '第二步：一次只改一个标签',
          desc: '这里是按标签独立保存的，最好不要跨几个标签来回改，不然容易忘记回查哪一组参数。',
          action: `当前标签：${this.currentTabText}。`
        },
        {
          title: '第三步：改完就去日志或文档复核',
          desc: '系统设置页本身只能说明“你改了”，不能证明“已经生效”，所以改完要继续去日志、接口文档或控制台复核。',
          action: this.compactFollowupHint
        }
      ]
    },
    tabActionMap() {
      return {
        systemInfo: [
          { label: '回控制台', path: '/dashboard' },
          { label: '去操作日志', path: '/system/user-log' },
          { label: '去接口文档', path: '/system/apidoc' }
        ],
        captchaInfo: [
          { label: '去操作日志', path: '/system/user-log' },
          { label: '去接口文档', path: '/system/apidoc' },
          { label: '回控制台', path: '/dashboard' }
        ],
        cacheInfo: [
          { label: '去操作日志', path: '/system/user-log' },
          { label: '回控制台', path: '/dashboard' },
          { label: '去接口文档', path: '/system/apidoc' }
        ],
        tokenInfo: [
          { label: '去操作日志', path: '/system/user-log' },
          { label: '去接口文档', path: '/system/apidoc' },
          { label: '回控制台', path: '/dashboard' }
        ],
        logInfo: [
          { label: '去操作日志', path: '/system/user-log' },
          { label: '去后台用户', path: '/system/user' },
          { label: '去接口文档', path: '/system/apidoc' }
        ],
        apiInfo: [
          { label: '去接口文档', path: '/system/apidoc' },
          { label: '去操作日志', path: '/system/user-log' },
          { label: '回控制台', path: '/dashboard' }
        ],
        emailInfo: [
          { label: '去后台用户', path: '/system/user' },
          { label: '去操作日志', path: '/system/user-log' },
          { label: '回控制台', path: '/dashboard' }
        ],
        serverInfo: [
          { label: '回控制台', path: '/dashboard' },
          { label: '去操作日志', path: '/system/user-log' },
          { label: '去接口文档', path: '/system/apidoc' }
        ]
      }
    },
    currentTabActions() {
      return (
        this.tabActionMap[this.activeTabName] || [
          { label: '去操作日志', path: '/system/user-log' },
          { label: '去接口文档', path: '/system/apidoc' },
          { label: '回控制台', path: '/dashboard' }
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
        systemInfo: [
          { title: '去操作日志复核', path: '/system/user-log', desc: '系统级参数改完后，优先去日志页确认关键操作有没有异常回显。' },
          { title: '去接口文档核对', path: '/system/apidoc', desc: '需要确认对外调用环境、凭据或文档地址时，直接去接口文档页。' },
          { title: '回控制台总览', path: '/dashboard', desc: '改完基础系统参数后，回首屏继续看整体运行状态。' }
        ],
        captchaInfo: [
          { title: '去操作日志排查', path: '/system/user-log', desc: '验证码配置改完后，最应该先看日志，确认没有拦截异常或频率异常。' },
          { title: '去接口文档核对', path: '/system/apidoc', desc: '如果牵涉接口接入方，继续看文档和凭据会更顺。' },
          { title: '去后台用户核人', path: '/system/user', desc: '需要落到具体后台操作人时，直接去后台用户页继续定位。' }
        ],
        cacheInfo: [
          { title: '去操作日志观察', path: '/system/user-log', desc: '缓存策略调整后，先看日志观察命中、清理和相关操作是否正常。' },
          { title: '回控制台看状态', path: '/dashboard', desc: '如果改的是全局缓存，更适合回控制台继续看整体页面和数据表现。' },
          { title: '去服务器信息页', path: '/system/setting', extraQuery: { tab: 'serverInfo' }, desc: '怀疑环境资源影响缓存时，继续切到服务器信息页核对。' }
        ],
        tokenInfo: [
          { title: '去接口文档核凭据', path: '/system/apidoc', desc: 'Token 相关配置改完后，先回文档页核对当前凭据和调试入口。' },
          { title: '去操作日志排查', path: '/system/user-log', desc: '如果出现登录态或鉴权异常，继续到日志页最直接。' },
          { title: '去后台用户核人', path: '/system/user', desc: '需要定位具体操作者或账号影响时，继续去后台用户页。' }
        ],
        logInfo: [
          { title: '去操作日志', path: '/system/user-log', desc: '日志设置改完后，先回日志页看回显和筛选链路是否正常。' },
          { title: '去后台用户核人', path: '/system/user', desc: '当日志已经定位到人，再回后台用户页继续处理。' },
          { title: '去接口文档核接口', path: '/system/apidoc', desc: '如果问题跟接口访问有关，直接去接口文档页继续核查。' }
        ],
        apiInfo: [
          { title: '去接口文档', path: '/system/apidoc', desc: '接口设置改完后，先去文档页核对地址、口令、Token 和调试入口。' },
          { title: '去操作日志', path: '/system/user-log', desc: '需要确认接口调用结果时，继续看日志页更稳。' },
          { title: '去后台用户页', path: '/system/user', desc: '若问题已经落到具体后台账号，直接去后台用户页继续处理。' }
        ],
        emailInfo: [
          { title: '去后台用户核接收方', path: '/system/user', desc: '邮件相关配置改完后，优先核对后台用户和接收对象。' },
          { title: '去操作日志排查', path: '/system/user-log', desc: '如果发信异常或动作异常，继续看日志更直接。' },
          { title: '去接口文档核环境', path: '/system/apidoc', desc: '若邮件回调或通知和接口有关，继续去文档页复核。' }
        ],
        serverInfo: [
          { title: '回控制台看整体', path: '/dashboard', desc: '服务器信息核完后，更适合回首屏继续看整体运行状态。' },
          { title: '去操作日志排查', path: '/system/user-log', desc: '如果怀疑资源抖动影响了操作，继续去日志页交叉核对。' },
          { title: '去系统设置中心', path: '/system/setting', extraQuery: { tab: 'systemInfo' }, desc: '需要回到系统基础参数时，直接切回系统设置页。' }
        ]
      }
      return map[this.activeTabName] || [
        { title: '去操作日志', path: '/system/user-log', desc: '先回操作日志继续复核。' },
        { title: '去接口文档', path: '/system/apidoc', desc: '需要核接口环境时，从这里继续。' },
        { title: '回控制台', path: '/dashboard', desc: '需要回总览页时，从这里继续。' }
      ]
    },
    currentTabGuide() {
      const label = this.currentTabText
      const isSecurityTab = ['systemInfo', 'captchaInfo', 'tokenInfo'].includes(this.activeTabName)
      const isRuntimeTab = ['cacheInfo', 'logInfo', 'apiInfo', 'emailInfo', 'serverInfo'].includes(this.activeTabName)
      const title = this.activeTabName ? `现在优先收口「${label}」` : '先选一个标签开始处理'
      const desc = !this.activeTabName
        ? '系统设置是分标签保存的，先进入一个具体模块，再按“改参数 -> 保存 -> 去别页复核”这条链路走。'
        : isSecurityTab
          ? '这类配置更容易直接影响登录、校验和权限体验，建议一次只动一组参数，保存后立刻去日志或文档页复核。'
          : isRuntimeTab
            ? '这类配置更偏运行与联调，改完不要停在当前页，最好继续去日志、接口文档或控制台确认效果。'
            : '当前标签已经选中，接下来按单标签保存和跨页复核的节奏处理最稳。'
      const tags = [
        this.runtimeEnvInfo.label,
        this.activeTabName ? label : '待选标签',
        isSecurityTab ? '高影响配置' : isRuntimeTab ? '运行 / 联调配置' : '按标签独立保存'
      ]
      const steps = [
        {
          title: '先聚焦这一组参数',
          desc: '不要跨几个标签来回改，这样更容易记住本轮到底改了哪一组。',
          action: this.activeTabName ? `当前正在处理：${label}` : '先从任一可见标签进入。'
        },
        {
          title: '保存后立刻跨页复核',
          desc: '设置页只能说明“已提交”，不能自动证明“已生效”，所以改完要继续验证。',
          action: this.primaryAction ? `下一步优先：${this.primaryAction.label}` : '下一步优先去日志或接口文档。'
        },
        {
          title: '用白话判断本轮风险',
          desc: isSecurityTab
            ? '如果你改的是安全类，重点看有没有把登录、验证码、Token 链路一起带动。'
            : '如果你改的是运行类，重点看接口、日志、页面表现是否同步变化。',
          action: this.compactFollowupHint
        }
      ]
      return { title, desc, tags, steps }
    }
  },
  created() {
    this.syncRouteTab()
  },
  watch: {
    '$route.fullPath'(nextPath, prevPath) {
      if (nextPath === prevPath) {
        return
      }
      this.syncRouteTab()
    }
  },
  methods: {
    checkPermission,
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
    syncRouteTab() {
      const routeTab = String(this.$route.query.tab || this.$route.query.setting_tab || '').trim()
      const matchedTab = this.visibleTabs.find((item) => item.name === routeTab)
      if (matchedTab) {
        this.activeTabName = matchedTab.name
        this.recentActionSummary = `已进入${matchedTab.label}，请按标签独立复核配置后再保存。`
        return
      }
      if (this.visibleTabs.length) {
        this.activeTabName = this.visibleTabs[0].name
        this.recentActionSummary = `已进入${this.visibleTabs[0].label}，请按标签独立复核配置后再保存。`
      }
    },
    handleEntryContextPrimary() {
      if (this.entrySourceLabel === '来自控制台总览') {
        const targetTab = ['systemInfo', 'captchaInfo', 'tokenInfo'].includes(this.activeTabName)
          ? this.activeTabName
          : 'systemInfo'
        this.handleTabChange(targetTab)
        return
      }
      if (this.entrySourceLabel === '来自接口文档') {
        this.goTo('/system/apidoc', { setting_tab: this.activeTabName, tab: this.activeTabName })
        return
      }
      if (this.entrySourceLabel === '来自个人中心') {
        this.goTo('/system/user-center')
        return
      }
      if (this.entrySourceLabel === '来自用户日志') {
        this.goTo('/system/user-log')
        return
      }
      this.goTo('/system/user-log')
    },
    goToEntryContextBack() {
      if (this.entrySourceLabel === '来自控制台总览') {
        this.goTo('/dashboard')
        return
      }
      if (this.entrySourceLabel === '来自接口文档') {
        this.goTo('/system/apidoc', { setting_tab: this.activeTabName, tab: this.activeTabName })
        return
      }
      if (this.entrySourceLabel === '来自个人中心') {
        this.goTo('/system/user-center')
        return
      }
      if (this.entrySourceLabel === '来自用户日志') {
        this.goTo('/system/user-log')
        return
      }
      this.goTo('/dashboard')
    },
    handleTabChange(name) {
      const current = this.visibleTabs.find((item) => item.name === name)
      if (!current) {
        return
      }
      this.recentActionSummary = `已切换到${current.label}，当前环境：${this.runtimeEnvInfo.label}。`
      if (current.name !== this.activeTabName) {
        this.activeTabName = current.name
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
        }, 'system-setting')
      })
    },
    openAdminLogin() {
      window.open(`${window.location.origin}/admin-next/#/login`, '_blank', 'noopener')
    }
  }
}
</script>

<style scoped>
.setting-page-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 16px;
}

.setting-page-head__title {
  font-size: 18px;
  font-weight: 700;
  color: #0f172a;
}

.setting-page-head__desc {
  margin-top: 4px;
  font-size: 12px;
  line-height: 1.6;
  color: #64748b;
}

.setting-page-head__tag {
  display: inline-flex;
  align-items: center;
  padding: 5px 10px;
  border-radius: 999px;
  border: 1px solid #dbe5f1;
  background: #f8fafc;
  color: #334155;
  font-size: 12px;
  white-space: nowrap;
}

.setting-summary-bar {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 0;
  margin-bottom: 16px;
  border: 1px solid #e6ecf5;
  border-radius: 12px;
  background: #f8fafc;
  overflow: hidden;
}

.entry-context-banner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
  margin-bottom: 16px;
  padding: 14px 16px;
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

.setting-summary-bar__item {
  min-width: 0;
  padding: 14px 16px;
  border-right: 1px solid #e6ecf5;
}

.setting-summary-bar__item:last-child {
  border-right: 0;
}

.setting-summary-bar__label {
  display: block;
  margin-bottom: 6px;
  font-size: 12px;
  color: #7c8aa5;
}

.setting-summary-bar__item strong {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: #0f172a;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.setting-status-row {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 16px;
}

.plain-guide {
  margin-bottom: 16px;
  padding: 14px 16px;
  border: 1px solid #dbe5f1;
  border-radius: 14px;
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
  color: #64748b;
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

.setting-status-row__chips {
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
  border: 1px solid #dbe5f1;
  background: #ffffff;
  color: #475569;
  font-size: 12px;
  font-weight: 600;
}

.setting-status-row__hint {
  flex: 1;
  min-width: 220px;
  font-size: 12px;
  line-height: 1.6;
  color: #64748b;
  text-align: right;
}

.setting-followup-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 10px;
  margin-bottom: 16px;
}

.current-tab-guide {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 12px;
  padding: 14px 16px;
  border: 1px solid #dbe5f1;
  border-radius: 14px;
  background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
}

.current-tab-guide__main {
  min-width: 0;
  flex: 1;
}

.current-tab-guide__label {
  font-size: 12px;
  color: #7c8aa5;
}

.current-tab-guide__title {
  margin-top: 6px;
  font-size: 14px;
  font-weight: 700;
  color: #0f172a;
}

.current-tab-guide__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.current-tab-guide__tags {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 8px;
}

.current-tab-guide__tags span {
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  padding: 0 10px;
  border-radius: 999px;
  background: #eef2ff;
  color: #4338ca;
  font-size: 12px;
  white-space: nowrap;
}

.current-tab-guide__grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 10px;
  margin-bottom: 16px;
}

.current-tab-guide-card {
  padding: 12px;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  background: #fff;
}

.current-tab-guide-card__title {
  font-size: 13px;
  font-weight: 700;
  color: #111827;
}

.current-tab-guide-card__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #6b7280;
}

.current-tab-guide-card__action {
  margin-top: 8px;
  font-size: 12px;
  color: #4f46e5;
}

.plain-guide--radar {
  margin-top: -4px;
}

.setting-summary-bar--radar {
  margin-top: 14px;
  margin-bottom: 0;
}

.setting-summary-bar__item--radar {
  gap: 6px;
}

.setting-summary-bar__item--radar em {
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

.setting-status-row__tools {
  display: flex;
  align-items: center;
  gap: 12px;
}

.setting-status-row__actions {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 2px;
}

@media (max-width: 900px) {
  .setting-page-head,
  .setting-status-row,
  .plain-guide__header,
  .current-tab-guide {
    flex-direction: column;
  }

  .setting-status-row__hint {
    min-width: 0;
    text-align: left;
  }

  .setting-status-row__tools {
    flex-direction: column;
    align-items: flex-start;
  }

  .current-tab-guide__tags {
    justify-content: flex-start;
  }
}
</style>
