<template>
  <div class="app-container">
    <el-card class="user-center-hero" shadow="never">
      <div class="user-center-hero__header">
        <div>
          <div class="user-center-hero__eyebrow">Profile Center</div>
          <div class="user-center-hero__title">个人中心</div>
          <div class="user-center-hero__desc">
            先核对当前账号资料，再决定是改资料、改密码，还是继续回日志和系统设置做后续处理。
          </div>
        </div>
        <div class="user-center-hero__meta">
          <el-tag effect="plain" :type="runtimeEnvInfo.tone">{{ runtimeEnvInfo.label }}</el-tag>
          <el-tag effect="plain">{{ runtimeEnvInfo.dataMode }}</el-tag>
        </div>
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

      <div class="setting-overview">
        <div class="setting-overview__card">
          <span class="setting-overview__label">功能区数量</span>
          <strong>{{ visibleTabCount }}</strong>
        </div>
        <div class="setting-overview__card">
          <span class="setting-overview__label">当前栏目</span>
          <strong>个人中心</strong>
        </div>
        <div class="setting-overview__card">
          <span class="setting-overview__label">使用建议</span>
          <strong>{{ recommendationText }}</strong>
        </div>
      </div>

      <div class="user-center-guide">
        <div class="user-center-guide__header">
          <div>
            <div class="user-center-guide__title">个人中心最常见的处理顺序</div>
            <div class="user-center-guide__desc">这个页面主要是处理“当前登录人”的资料和密码，不是改全员权限。</div>
          </div>
          <div class="user-center-guide__badge">当前重点：{{ recommendationText }}</div>
        </div>
        <div class="user-center-guide__grid">
          <div v-for="item in guideCards" :key="item.title" class="user-center-guide-card">
            <span class="user-center-guide-card__step">{{ item.step }}</span>
            <div class="user-center-guide-card__title">{{ item.title }}</div>
            <div class="user-center-guide-card__desc">{{ item.desc }}</div>
          </div>
        </div>
      </div>

      <div class="user-center-followup">
        <button
          v-for="item in followupCards"
          :key="item.title"
          type="button"
          class="user-center-followup__card"
          @click="goToPage(item.path, item.query)"
        >
          <span class="user-center-followup__title">{{ item.title }}</span>
          <span class="user-center-followup__desc">{{ item.desc }}</span>
        </button>
      </div>
    </el-card>

    <el-card class="app-main">
      <div class="user-center-panel-head">
        <div class="user-center-panel-head__title">账号资料维护</div>
        <div class="user-center-panel-head__desc">保持原有信息修改逻辑不变，只把首屏承接和后续入口补齐。</div>
      </div>

      <el-tabs v-model="activeTabName" @tab-change="handleTabChange">
        <el-tab-pane v-if="checkPermission(['admin/system.UserCenter/info'])" label="我的信息" name="info" lazy>
          <UserCenterInfo />
        </el-tab-pane>
        <el-tab-pane v-if="checkPermission(['admin/system.UserCenter/edit'])" label="修改信息" name="edit" lazy>
          <UserCenterEdit />
        </el-tab-pane>
        <el-tab-pane v-if="checkPermission(['admin/system.UserCenter/pwd'])" label="修改密码" name="pwd" lazy>
          <UserCenterPwd />
        </el-tab-pane>
      </el-tabs>
    </el-card>
  </div>
</template>

<script>
import checkPermission from '@/utils/permission'
import { resolveAdminRuntimeEnv } from '@/utils/runtime-env'
import UserCenterInfo from './components/UserCenterInfo.vue'
import UserCenterEdit from './components/UserCenterEdit.vue'
import UserCenterPwd from './components/UserCenterPwd.vue'

export default {
  name: 'SystemUserCenter',
  components: { UserCenterInfo, UserCenterEdit, UserCenterPwd },
  data() {
    return {
      name: '个人中心',
      runtimeEnvInfo: resolveAdminRuntimeEnv(),
      activeTabName: ''
    }
  },
  computed: {
    entrySourceLabel() {
      const source = this.$route?.query?.from
      if (source === 'system-user-log') return '来自用户日志'
      if (source === 'system-setting') return '来自系统设置'
      if (source === 'dashboard') return '来自后台首页'
      return ''
    },
    entryContextVisible() {
      return Boolean(this.entrySourceLabel)
    },
    entryContextTitle() {
      if (this.entrySourceLabel === '来自用户日志') return '当前从用户日志进入个人中心'
      if (this.entrySourceLabel === '来自系统设置') return '当前从系统设置进入个人中心'
      if (this.entrySourceLabel === '来自后台首页') return '当前从后台首页进入个人中心'
      return '当前为外部入口承接视角'
    },
    entryContextDesc() {
      if (this.entrySourceLabel === '来自用户日志') {
        return '这类进入通常是为了核对当前账号的资料或密码改动是不是和日志一致。建议先处理当前账号，再回日志页继续核查操作记录。'
      }
      if (this.entrySourceLabel === '来自系统设置') {
        return '这类进入通常是为了把全局账号策略切回当前登录人的真实体验。建议先核对个人资料和密码，再回系统设置继续处理全局配置。'
      }
      return '这类进入通常是首页巡检后的继续下钻。建议先确认是不是当前账号本人的问题，再决定去日志、设置还是用户管理。'
    },
    entryContextPrimaryLabel() {
      if (this.entrySourceLabel === '来自用户日志') return '回用户日志'
      if (this.entrySourceLabel === '来自系统设置') return '回系统设置'
      return '回后台首页'
    },
    visibleTabCount() {
      return this.visibleTabs.length
    },
    visibleTabs() {
      return [
        { name: 'info', label: '我的信息', permissions: ['admin/system.UserCenter/info'] },
        { name: 'edit', label: '修改信息', permissions: ['admin/system.UserCenter/edit'] },
        { name: 'pwd', label: '修改密码', permissions: ['admin/system.UserCenter/pwd'] }
      ].filter((item) => this.checkPermission(item.permissions))
    },
    recommendationText() {
      if (this.activeTabName === 'pwd') return '当前先核安全与登录'
      if (this.activeTabName === 'edit') return '当前先核资料修改'
      return this.visibleTabCount <= 1 ? '当前权限较少，先核对基础资料' : '先核对资料再改密码'
    },
    followupCards() {
      return [
        {
          title: '去后台日志核对',
          desc: '如果刚改过资料、密码或权限，继续去用户日志确认操作有没有落下。',
          path: '/system/user-log',
          query: this.buildEntryRouteQuery({}, 'system-user-center')
        },
        {
          title: '回系统首页',
          desc: '回首页继续看告警、商品迁移和运营总览。',
          path: '/dashboard',
          query: this.buildEntryRouteQuery({}, 'system-user-center')
        },
        {
          title: '去系统配置页',
          desc: '如果是账号策略、上传设置或全局参数问题，继续去系统设置。',
          path: '/system/setting',
          query: this.buildEntryRouteQuery({}, 'system-user-center')
        }
      ]
    },
    guideCards() {
      return [
        {
          step: '第一步',
          title: '先核对是不是当前账号本人的问题',
          desc: '这里只改你当前登录账号的资料、头像和密码，其他后台人员权限要去用户管理。'
        },
        {
          step: '第二步',
          title: '资料问题走修改信息',
          desc: '昵称、头像、基础资料不对，优先在“修改信息”里处理，改完再返回看展示。'
        },
        {
          step: '第三步',
          title: '登录异常再走修改密码或日志',
          desc: '如果是登录失败、密码遗忘或安全问题，先改密码，再去日志页确认有没有异常操作。'
        }
      ]
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
      const routeTab = String(this.$route?.query?.tab || '').trim()
      const matchedTab = this.visibleTabs.find((item) => item.name === routeTab)
      if (matchedTab) {
        this.activeTabName = matchedTab.name
        return
      }
      this.activeTabName = this.visibleTabs[0]?.name || ''
    },
    handleEntryContextPrimary() {
      if (this.entrySourceLabel === '来自用户日志') {
        this.goToPage('/system/user-log', this.buildEntryRouteQuery({}, 'system-user-center'))
        return
      }
      if (this.entrySourceLabel === '来自系统设置') {
        this.goToPage('/system/setting', this.buildEntryRouteQuery({}, 'system-user-center'))
        return
      }
      this.goToPage('/dashboard', this.buildEntryRouteQuery({}, 'system-user-center'))
    },
    handleTabChange(name) {
      const current = this.visibleTabs.find((item) => item.name === name)
      if (!current) {
        return
      }
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
    goToEntryContextBack() {
      this.handleEntryContextPrimary()
    },
    goToPage(path, query = {}) {
      this.$router.push({ path, query })
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
  margin-bottom: 16px;
  padding: 14px 16px;
  border-radius: 14px;
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
  font-weight: 700;
  color: #64748b;
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

.user-center-hero {
  margin-bottom: 16px;
  border: 1px solid #e6ecf5;
  border-radius: 16px;
}

.user-center-hero__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;
  margin-bottom: 16px;
}

.user-center-hero__eyebrow {
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #1d4ed8;
}

.user-center-hero__title {
  margin-top: 6px;
  font-size: 28px;
  font-weight: 700;
  color: #0f172a;
}

.user-center-hero__desc {
  margin-top: 8px;
  max-width: 720px;
  color: #64748b;
  line-height: 1.7;
}

.user-center-hero__meta {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
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

.user-center-followup {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
}

.user-center-guide {
  margin-bottom: 16px;
  padding: 16px;
  border: 1px solid #dbe7f5;
  border-radius: 16px;
  background: linear-gradient(135deg, #f7fbff 0%, #ffffff 100%);
}

.user-center-guide__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
}

.user-center-guide__title {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
}

.user-center-guide__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.user-center-guide__badge {
  min-width: 220px;
  padding: 10px 12px;
  border-radius: 12px;
  background: rgba(37, 99, 235, 0.08);
  color: #1d4ed8;
  font-size: 12px;
  font-weight: 700;
  line-height: 1.6;
}

.user-center-guide__grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-top: 14px;
}

.user-center-guide-card {
  padding: 14px 16px;
  border: 1px solid #e7edf5;
  border-radius: 14px;
  background: #fff;
}

.user-center-guide-card__step {
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

.user-center-guide-card__title {
  margin-top: 10px;
  font-weight: 700;
  color: #0f172a;
}

.user-center-guide-card__desc {
  margin-top: 8px;
  color: #64748b;
  line-height: 1.7;
}

.user-center-followup__card {
  display: grid;
  gap: 8px;
  padding: 16px;
  border: 1px solid #e7edf5;
  border-radius: 14px;
  background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
  text-align: left;
  cursor: pointer;
  transition: all 0.2s ease;
}

.user-center-followup__card:hover {
  border-color: #c7d7f5;
  transform: translateY(-1px);
}

.user-center-followup__title,
.user-center-panel-head__title {
  font-weight: 700;
  color: #0f172a;
}

.user-center-followup__desc,
.user-center-panel-head__desc {
  color: #64748b;
  line-height: 1.7;
}

.user-center-panel-head {
  margin-bottom: 16px;
}

@media (max-width: 768px) {
  .entry-context-banner,
  .user-center-hero__header,
  .user-center-guide__header {
    flex-direction: column;
  }

  .user-center-guide__badge {
    min-width: 0;
  }

  .user-center-guide__grid,
  .user-center-followup {
    grid-template-columns: 1fr;
  }
}
</style>
