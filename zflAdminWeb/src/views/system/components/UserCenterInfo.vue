<template>
  <el-scrollbar native :max-height="height - 30">
    <div class="user-center-panel">
      <div class="section-title-row">
        <div>
          <h3>个人资料</h3>
          <p>直接核对当前登录管理员的资料、联系方式和登录轨迹。</p>
        </div>
        <div class="section-title-row__actions">
          <div class="section-title-row__meta">{{ accountTimeline }}</div>
          <el-button :loading="loading" @click="refresh">刷新</el-button>
        </div>
      </div>
      <div class="summary-bar">
        <div class="summary-bar__item">
          <span class="summary-bar__label">当前账号</span>
          <strong class="summary-bar__value">{{ model.username || '--' }}</strong>
        </div>
        <div class="summary-bar__item">
          <span class="summary-bar__label">联系方式</span>
          <strong class="summary-bar__value">{{ model.phone || '未绑定手机' }}</strong>
        </div>
        <div class="summary-bar__item">
          <span class="summary-bar__label">最近登录</span>
          <strong class="summary-bar__value">{{ model.login_time || '--' }}</strong>
        </div>
        <div class="summary-bar__item summary-bar__item--wide">
          <span class="summary-bar__label">账号摘要</span>
          <strong class="summary-bar__value summary-bar__value--text"
            >{{ model.nickname || '未设置昵称' }} / {{ model.email || '未绑定邮箱' }} /
            {{ model.login_region || '暂无地区记录' }}</strong
          >
        </div>
      </div>

      <div class="follow-up-strip">
        <div>
          <h4>看完资料别停在当前页</h4>
          <p>个人资料核对完成后，建议继续去真实登录入口、操作日志和首页看展示是否一致。</p>
        </div>
        <div class="follow-up-strip__actions">
          <el-button plain @click="openAdminLogin">去后台登录页核对</el-button>
          <el-button plain @click="goToRoute('/system/user-log')">去操作日志看记录</el-button>
          <el-button type="primary" plain @click="goToRoute('/dashboard')"
            >回首页继续验收</el-button
          >
        </div>
      </div>

      <el-row :gutter="16">
        <el-col :span="12">
          <el-form ref="ref" :rules="rules" :model="model" label-width="120px" class="form-card">
            <el-form-item label="头像">
              <FileImage :file-url="model.avatar_url" :height="100" avatar />
            </el-form-item>
            <el-form-item label="昵称" prop="nickname">
              <el-input v-model="model.nickname" />
            </el-form-item>
            <el-form-item label="账号" prop="username">
              <el-input v-model="model.username" />
            </el-form-item>
            <el-form-item label="手机" prop="phone">
              <el-input v-model="model.phone" />
            </el-form-item>
            <el-form-item label="邮箱" prop="email">
              <el-input v-model="model.email" />
            </el-form-item>
            <el-form-item label="添加时间" prop="create_time">
              <el-input v-model="model.create_time" disabled />
            </el-form-item>
            <el-form-item label="修改时间" prop="update_time">
              <el-input v-model="model.update_time" disabled />
            </el-form-item>
            <el-form-item label="登录时间" prop="login_time">
              <el-input v-model="model.login_time" disabled />
            </el-form-item>
            <el-form-item label="登录地区" prop="login_region">
              <el-input v-model="model.login_region" disabled />
            </el-form-item>
            <el-form-item label="退出时间" prop="logout_time">
              <el-input v-model="model.logout_time" disabled />
            </el-form-item>
          </el-form>
        </el-col>
      </el-row>
    </div>
  </el-scrollbar>
</template>

<script>
import screenHeight from '@/utils/screen-height'
import { info } from '@/api/system/user-center'

export default {
  name: 'SystemUserCenterInfo',
  data() {
    return {
      name: '我的信息',
      height: 680,
      loading: false,
      model: {
        avatar_id: 0,
        avatar_url: '',
        username: '',
        nickname: '',
        phone: '',
        email: '',
        create_time: '',
        update_time: '',
        login_time: '',
        login_region: '',
        logout_time: ''
      },
      rules: {}
    }
  },
  computed: {
    accountTimeline() {
      if (this.model.create_time && this.model.update_time) {
        return `${this.model.create_time} 建立`
      }
      return '资料待同步'
    }
  },
  created() {
    this.height = screenHeight(210)
    this.info()
  },
  methods: {
    // 信息
    info(msg = false) {
      this.loading = true
      info()
        .then((res) => {
          this.model = res.data
          this.loading = false
          if (msg) {
            ElMessage.success(res.msg)
          }
        })
        .catch(() => {
          this.loading = false
        })
    },
    // 刷新
    refresh() {
      this.info(true)
    },
    goToRoute(path, query = {}) {
      this.$router.push({
        path,
        query
      })
    },
    openAdminLogin() {
      window.open(`${window.location.origin}/admin-next/#/login`, '_blank')
    }
  }
}
</script>

<style scoped>
.user-center-panel {
  padding: 4px 2px 12px;
}

.form-card {
  border: 1px solid #ebeef5;
  border-radius: 14px;
  background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
  box-shadow: 0 10px 24px rgba(31, 35, 41, 0.06);
}

.section-title-row {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 16px;
}

.section-title-row h3 {
  margin: 0 0 4px;
  color: #1f2329;
  font-size: 18px;
}

.section-title-row p {
  margin: 0;
  color: #7a8599;
  font-size: 13px;
}

.section-title-row__actions {
  display: flex;
  align-items: center;
  gap: 12px;
}

.section-title-row__meta {
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
  white-space: nowrap;
}

.summary-bar {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 12px;
  margin-bottom: 18px;
}

.summary-bar__item {
  min-width: 0;
  padding: 14px 16px;
  border: 1px solid #ebeef5;
  border-radius: 12px;
  background: #f8fafc;
}

.summary-bar__item--wide {
  grid-column: span 2;
}

.summary-bar__label {
  display: block;
  margin-bottom: 6px;
  color: #7a8599;
  font-size: 12px;
}

.summary-bar__value {
  display: block;
  color: #1f2329;
  font-size: 15px;
  line-height: 1.4;
}

.summary-bar__value--text {
  font-size: 14px;
  color: #475569;
}

.follow-up-strip {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 18px;
  padding: 16px 18px;
  border: 1px solid #dbeafe;
  border-radius: 14px;
  background: linear-gradient(90deg, #f8fbff 0%, #eff6ff 100%);
}

.follow-up-strip h4 {
  margin: 0 0 6px;
  color: #1d4ed8;
  font-size: 15px;
}

.follow-up-strip p {
  margin: 0;
  color: #5f6b7a;
  font-size: 13px;
}

.follow-up-strip__actions {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 10px;
}

.form-card {
  padding: 18px 18px 2px;
}

@media (max-width: 992px) {
  :deep(.el-col) {
    max-width: 100%;
    flex: 0 0 100%;
  }

  .section-title-row {
    flex-direction: column;
    align-items: flex-start;
  }

  .section-title-row__meta {
    white-space: normal;
  }

  .follow-up-strip {
    flex-direction: column;
    align-items: flex-start;
  }

  .follow-up-strip__actions {
    justify-content: flex-start;
  }

  .summary-bar__item--wide {
    grid-column: span 1;
  }
}
</style>
