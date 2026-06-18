<template>
  <el-row>
    <el-col :span="14">
      <el-form ref="ref" :model="model" :rules="rules" label-width="120px">
        <div class="setting-guide-panel">
          <div class="setting-guide-panel__header">
            <div>
              <div class="setting-guide-panel__title">登录注册设置先按入口梳理，不要零散开关</div>
              <div class="setting-guide-panel__desc">
                这页影响账号密码、手机验证码、邮箱验证码三条会员登录注册链路。先看要保留哪些入口，再决定验证码和登录方式组合。
              </div>
            </div>
            <span class="setting-guide-panel__badge">{{ logregFocusLabel }}</span>
          </div>
          <div class="setting-guide-panel__grid">
            <div v-for="item in logregGuideCards" :key="item.title" class="setting-guide-card">
              <div class="setting-guide-card__title">{{ item.title }}</div>
              <div class="setting-guide-card__desc">{{ item.desc }}</div>
              <div class="setting-guide-card__action">{{ item.action }}</div>
            </div>
          </div>
        </div>
        <div class="setting-panel-overview">
          <div class="setting-panel-overview__card">
            <span class="setting-panel-overview__label">账号密码注册</span>
            <strong>{{ model.is_register ? '开启' : '关闭' }}</strong>
          </div>
          <div class="setting-panel-overview__card">
            <span class="setting-panel-overview__label">账号密码登录</span>
            <strong>{{ model.is_login ? '开启' : '关闭' }}</strong>
          </div>
          <div class="setting-panel-overview__card">
            <span class="setting-panel-overview__label">快捷登录入口</span>
            <strong>{{ quickEntrySummary }}</strong>
          </div>
        </div>
        <div class="setting-followup-strip">
          <div class="setting-followup-strip__main">
            <div class="setting-followup-strip__title">提交后请直接回真实前端入口验证</div>
            <div class="setting-followup-strip__desc">
              登录注册设置最容易出现“后台已保存、前端按钮却不对”的情况。提交后建议立刻打开 H5 登录页，再去会员日志和第三方账号页看承接结果。
            </div>
          </div>
          <div class="setting-followup-strip__actions">
            <el-button text type="primary" @click="openH5Login">打开 H5 登录页</el-button>
            <el-button text type="primary" @click="goToRoute('/member/log')">去会员日志</el-button>
            <el-button text type="primary" @click="goToRoute('/member/third')">去第三方账号</el-button>
            <el-button text type="primary" @click="goToRoute('/member/statistic')">去会员统计</el-button>
          </div>
        </div>
        <el-form-item label="注册" prop="is_register">
          <el-col :span="8">
            <el-switch v-model="model.is_register" :active-value="1" :inactive-value="0" />
          </el-col>
          <el-col :span="16"> 关闭后，不能使用账号密码注册 </el-col>
        </el-form-item>
        <el-form-item label="注册验证码" prop="is_captcha_register">
          <el-col :span="8">
            <el-switch v-model="model.is_captcha_register" :active-value="1" :inactive-value="0" />
          </el-col>
          <el-col :span="16"> 开启后，会员账号密码注册需要输入验证码 </el-col>
        </el-form-item>
        <el-form-item label="登录" prop="is_login">
          <el-col :span="8">
            <el-switch v-model="model.is_login" :active-value="1" :inactive-value="0" />
          </el-col>
          <el-col :span="16"> 关闭后，不能使用账号密码登录 </el-col>
        </el-form-item>
        <el-form-item label="登录验证码" prop="is_captcha_login">
          <el-col :span="8">
            <el-switch v-model="model.is_captcha_login" :active-value="1" :inactive-value="0" />
          </el-col>
          <el-col :span="16"> 开启后，会员账号密码登录需要输入验证码 </el-col>
        </el-form-item>
        <el-form-item label="手机验证码注册" prop="is_phone_register">
          <el-col :span="8">
            <el-switch v-model="model.is_phone_register" :active-value="1" :inactive-value="0" />
          </el-col>
          <el-col :span="16"> 关闭后，不能使用手机+验证码注册 </el-col>
        </el-form-item>
        <el-form-item label="手机验证码登录" prop="is_phone_login">
          <el-col :span="8">
            <el-switch v-model="model.is_phone_login" :active-value="1" :inactive-value="0" />
          </el-col>
          <el-col :span="16"> 关闭后，不能使用手机+验证码登录 </el-col>
        </el-form-item>
        <el-form-item label="邮箱验证码注册" prop="is_email_register">
          <el-col :span="8">
            <el-switch v-model="model.is_email_register" :active-value="1" :inactive-value="0" />
          </el-col>
          <el-col :span="16"> 关闭后，不能使用邮箱+验证码注册 </el-col>
        </el-form-item>
        <el-form-item label="邮箱验证码登录" prop="is_email_login">
          <el-col :span="8">
            <el-switch v-model="model.is_email_login" :active-value="1" :inactive-value="0" />
          </el-col>
          <el-col :span="16"> 关闭后，不能使用邮箱+验证码登录 </el-col>
        </el-form-item>
        <el-form-item>
          <el-button :loading="loading" @click="refresh()">刷新</el-button>
          <el-button :loading="loading" type="primary" @click="submit()">提交</el-button>
        </el-form-item>
      </el-form>
    </el-col>
  </el-row>
</template>

<script>
import screenHeight from '@/utils/screen-height'
import { logregInfo, logregEdit } from '@/api/member/setting'

export default {
  name: 'MemberSettingLogreg',
  data() {
    return {
      name: '登录注册设置',
      height: 680,
      loading: false,
      model: {
        is_register: 1,
        is_captcha_register: 0,
        is_login: 1,
        is_captcha_login: 0,
        is_phone_register: 1,
        is_phone_login: 1,
        is_email_register: 1,
        is_email_login: 1
      },
      rules: {}
    }
  },
  computed: {
    logregFocusLabel() {
      if (!this.model.is_login && !this.model.is_phone_login && !this.model.is_email_login) {
        return '当前无可用登录入口'
      }
      if (!this.model.is_register && !this.model.is_phone_register && !this.model.is_email_register) {
        return '当前无可用注册入口'
      }
      return '优先核前端登录入口展示'
    },
    quickEntrySummary() {
      const channels = []
      if (this.model.is_phone_login) channels.push('手机')
      if (this.model.is_email_login) channels.push('邮箱')
      return channels.length ? channels.join(' + ') : '全部关闭'
    },
    logregGuideCards() {
      return [
        {
          title: '第一步：先确认还有哪些登录入口',
          desc: '先看账号密码、手机、邮箱三条登录链路是不是都需要保留，不要只关其中一个就以为前端会同步合理。',
          action: `快捷登录：${this.quickEntrySummary}`
        },
        {
          title: '第二步：再看注册和验证码组合',
          desc: '登录开着不代表注册也该开着，验证码开关也要分注册和登录分别看。',
          action: `账号注册 ${this.model.is_register ? '开' : '关'} / 账号登录 ${this.model.is_login ? '开' : '关'}`
        },
        {
          title: '第三步：最后去 H5 和小程序逐入口回查',
          desc: '提交后要看前端实际展示哪些按钮、是否默认拦截、是否还能正常进入下一步。',
          action: '重点回查登录页、注册页、验证码登录、账号登录。'
        }
      ]
    }
  },
  created() {
    this.height = screenHeight(210)
    this.info()
  },
  methods: {
    // 信息
    info() {
      logregInfo().then((res) => {
        this.model = res.data
      })
    },
    // 刷新
    refresh() {
      this.loading = true
      logregInfo()
        .then((res) => {
          this.model = res.data
          this.loading = false
          ElMessage.success(res.msg)
        })
        .catch(() => {
          this.loading = false
        })
    },
    // 提交
    submit() {
      this.$refs['ref'].validate((valid) => {
        if (valid) {
          this.loading = true
          logregEdit(this.model)
            .then((res) => {
              this.loading = false
              ElMessage.success(res.msg)
              this.$nextTick(() => {
                ElMessage.info('建议立刻打开 H5 登录页验证入口展示，再去会员日志页看实际登录注册回显')
              })
            })
            .catch(() => {
              this.loading = false
            })
        }
      })
    },
    goToRoute(path) {
      this.$router.push({
        path,
        query: {
          from: 'member-setting-logreg'
        }
      })
    },
    openH5Login() {
      const url = `${window.location.origin}/app/pages/my/login`
      window.open(url, '_blank', 'noopener')
    }
  }
}
</script>

<style scoped>
.setting-guide-panel {
  margin-bottom: 16px;
  padding: 16px;
  border: 1px solid #dbe7f5;
  border-radius: 16px;
  background: linear-gradient(135deg, #f7fbff 0%, #ffffff 100%);
}

.setting-guide-panel__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
}

.setting-guide-panel__title {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
}

.setting-guide-panel__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.setting-guide-panel__badge {
  min-width: 180px;
  padding: 10px 12px;
  border-radius: 12px;
  background: rgba(37, 99, 235, 0.08);
  color: #1d4ed8;
  font-size: 12px;
  font-weight: 700;
  line-height: 1.6;
}

.setting-guide-panel__grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-top: 14px;
}

.setting-guide-card {
  padding: 14px 16px;
  border: 1px solid #e7edf5;
  border-radius: 14px;
  background: #fff;
}

.setting-guide-card__title {
  font-weight: 700;
  color: #0f172a;
}

.setting-guide-card__desc {
  margin-top: 8px;
  color: #64748b;
  line-height: 1.7;
}

.setting-guide-card__action {
  margin-top: 10px;
  color: #1d4ed8;
  font-size: 12px;
  line-height: 1.6;
}

.setting-panel-overview {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 12px;
  margin-bottom: 16px;
}

.setting-panel-overview__card {
  border: 1px solid #e6ecf5;
  border-radius: 12px;
  padding: 14px 16px;
  background: linear-gradient(180deg, #f9fbff 0%, #ffffff 100%);
  box-shadow: 0 6px 18px rgba(15, 35, 95, 0.05);
}

.setting-panel-overview__label {
  display: block;
  margin-bottom: 8px;
  font-size: 12px;
  color: #7c8aa5;
}

.setting-followup-strip {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 16px;
  padding: 14px 16px;
  border: 1px solid #dbe7f5;
  border-radius: 14px;
  background: #fff;
}

.setting-followup-strip__title {
  font-size: 14px;
  font-weight: 700;
  color: #0f172a;
}

.setting-followup-strip__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.setting-followup-strip__actions {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 2px;
}

@media (max-width: 960px) {
  .setting-guide-panel__header,
  .setting-followup-strip {
    flex-direction: column;
  }

  .setting-guide-panel__badge {
    min-width: auto;
    width: 100%;
  }

  .setting-guide-panel__grid {
    grid-template-columns: 1fr;
  }
}
</style>
