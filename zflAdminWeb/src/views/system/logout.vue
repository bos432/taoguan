<template>
  <div class="logout-shell" v-loading.fullscreen.lock="loading" element-loading-text="正在退出">
    <el-card class="logout-shell__card" shadow="never">
      <div class="logout-shell__eyebrow">Admin Next</div>
      <div class="logout-shell__title">正在安全退出后台</div>
      <div class="logout-shell__desc">
        当前会话正在清理登录状态与权限缓存，完成后将自动返回登录页。
      </div>
      <div v-if="logoutContextVisible" class="logout-shell__entry">
        <div class="logout-shell__entry-badge">{{ logoutContextBadge }}</div>
        <div class="logout-shell__entry-title">{{ logoutContextTitle }}</div>
        <div class="logout-shell__entry-desc">{{ logoutContextDesc }}</div>
      </div>
      <div class="logout-shell__tip">
        如果你刚刚改过账号、角色或菜单权限，这一步就是把旧缓存清掉，避免你以为“改了没生效”。
      </div>
      <div class="logout-shell__steps">
        <div class="logout-shell__step">
          <span>步骤 1</span>
          <strong>清理本地会话</strong>
        </div>
        <div class="logout-shell__step">
          <span>步骤 2</span>
          <strong>回收菜单权限</strong>
        </div>
        <div class="logout-shell__step">
          <span>步骤 3</span>
          <strong>跳转登录入口</strong>
        </div>
      </div>
      <div class="logout-shell__footer">
        退出后建议先重新登录，再回到系统首页或用户日志确认新权限是否正常。
      </div>
    </el-card>
  </div>
</template>

<script>
export default {
  name: 'SystemLogout',
  data() {
    return {
      name: '退出',
      loading: true
    }
  },
  computed: {
    logoutSource() {
      return String(this.$route.query?.from || '')
    },
    logoutContextVisible() {
      return Boolean(this.logoutSource)
    },
    logoutContextBadge() {
      if (this.logoutSource === 'system-setting') {
        return '配置变更后退出'
      }
      if (this.logoutSource === 'system-user-center') {
        return '个人中心发起'
      }
      if (this.logoutSource === 'system-user-log') {
        return '日志复核后退出'
      }
      return '会话回收'
    },
    logoutContextTitle() {
      if (this.logoutSource === 'system-setting') {
        return '刚才的系统配置修改，适合配合重新登录一起验证'
      }
      if (this.logoutSource === 'system-user-center') {
        return '你是从个人中心发起退出，账号信息会同步刷新'
      }
      if (this.logoutSource === 'system-user-log') {
        return '你是从操作日志链路退出，重新登录后可继续复核权限和行为记录'
      }
      return '当前正在清理会话并回到登录页'
    },
    logoutContextDesc() {
      if (this.logoutSource === 'system-setting') {
        return '如果刚改了登录、安全、菜单或接口相关配置，重新登录后更容易确认是否已经按新配置生效。'
      }
      if (this.logoutSource === 'system-user-center') {
        return '这适合切换账号、确认头像昵称资料，或验证个人权限是否按当前身份刷新。'
      }
      if (this.logoutSource === 'system-user-log') {
        return '重新登录后建议优先回控制台或操作日志，看关键行为记录是否符合预期。'
      }
      return '退出不会影响业务数据，只会清理当前浏览器里的后台会话和菜单缓存。'
    }
  },
  created() {
    this.logout()
  },
  methods: {
    async logout() {
      await this.$store.dispatch('user/logout')
      this.$router.push({
        path: '/login',
        query: {
          from: 'system-logout'
        }
      })
      // this.$router.push(`/login?redirect=${this.$route.fullPath}`)
    }
  }
}
</script>

<style scoped>
.logout-shell {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: calc(100vh - 84px);
  padding: 24px;
  background:
    radial-gradient(circle at top, rgba(59, 130, 246, 0.12), transparent 30%),
    linear-gradient(180deg, #f8fafc 0%, #eef2ff 100%);
}

.logout-shell__card {
  width: min(560px, 100%);
  border: none;
  border-radius: 24px;
  background: rgba(255, 255, 255, 0.94);
  box-shadow: 0 22px 50px rgba(15, 23, 42, 0.08);
}

.logout-shell__eyebrow {
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: #2563eb;
}

.logout-shell__title {
  margin-top: 10px;
  font-size: 28px;
  font-weight: 700;
  color: #0f172a;
}

.logout-shell__desc {
  margin-top: 12px;
  font-size: 14px;
  line-height: 1.8;
  color: #64748b;
}

.logout-shell__entry {
  margin-top: 16px;
  padding: 14px 16px;
  border: 1px solid rgba(59, 130, 246, 0.14);
  border-radius: 16px;
  background: rgba(239, 246, 255, 0.9);
}

.logout-shell__entry-badge {
  font-size: 12px;
  font-weight: 700;
  color: #2563eb;
}

.logout-shell__entry-title {
  margin-top: 8px;
  font-size: 18px;
  font-weight: 700;
  color: #0f172a;
}

.logout-shell__entry-desc {
  margin-top: 8px;
  line-height: 1.7;
  color: #475569;
}

.logout-shell__steps {
  display: grid;
  gap: 12px;
  margin-top: 22px;
}

.logout-shell__tip,
.logout-shell__footer {
  margin-top: 14px;
  padding: 12px 14px;
  border-radius: 14px;
  background: rgba(37, 99, 235, 0.06);
  color: #475569;
  line-height: 1.7;
}

.logout-shell__step {
  padding: 14px 16px;
  border: 1px solid rgba(148, 163, 184, 0.16);
  border-radius: 16px;
  background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
}

.logout-shell__step span {
  display: block;
  margin-bottom: 6px;
  font-size: 12px;
  color: #64748b;
}

.logout-shell__step strong {
  font-size: 16px;
  color: #0f172a;
}
</style>
