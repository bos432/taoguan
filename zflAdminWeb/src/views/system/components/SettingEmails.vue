<template>
  <el-row>
    <el-col :span="16">
      <el-form ref="ref" :model="model" :rules="rules" label-width="120px">
        <div class="setting-guide-panel">
          <div class="setting-guide-panel__header">
            <div>
              <div class="setting-guide-panel__title">邮件设置先补通道，再发测试</div>
              <div class="setting-guide-panel__desc">
                这页最容易出现“直接点测试邮件但配置没补全”。先把主机、端口、账号、授权码补齐，再填测试邮箱验证真实发送结果。
              </div>
            </div>
            <span class="setting-guide-panel__badge">{{ emailFocusLabel }}</span>
          </div>
          <div class="setting-guide-panel__grid">
            <div v-for="item in emailGuideCards" :key="item.title" class="setting-guide-card">
              <div class="setting-guide-card__title">{{ item.title }}</div>
              <div class="setting-guide-card__desc">{{ item.desc }}</div>
              <div class="setting-guide-card__action">{{ item.action }}</div>
            </div>
          </div>
        </div>
        <div class="setting-panel-overview">
          <div class="setting-panel-overview__card">
            <span class="setting-panel-overview__label">SMTP 主机</span>
            <strong>{{ model.email_host || '未设置' }}</strong>
          </div>
          <div class="setting-panel-overview__card">
            <span class="setting-panel-overview__label">发件账号</span>
            <strong>{{ model.email_username || '未设置' }}</strong>
          </div>
          <div class="setting-panel-overview__card">
            <span class="setting-panel-overview__label">测试邮箱</span>
            <strong>{{ model.email_test || '未填写' }}</strong>
          </div>
        </div>
        <div class="setting-followup-strip">
          <div class="setting-followup-strip__copy">
            <div class="setting-followup-strip__title">配置完邮件不要只看“发送成功”</div>
            <div class="setting-followup-strip__desc">
              邮件设置真正要确认的是收件箱是否收到、展示名称是否正确、失败时日志里有没有线索，而不是只停在这个表单页。
            </div>
          </div>
          <div class="setting-followup-strip__actions">
            <el-button plain @click="goToRoute('/system/user-log')">去用户日志核对</el-button>
            <el-button plain @click="goToRoute('/system/notice')">去通知页复核</el-button>
            <el-button type="primary" plain @click="goToRoute('/dashboard')"
              >回首页看全局提示</el-button
            >
          </div>
        </div>
        <el-form-item label="* SMTP服务器" prop="email_host">
          <el-col :span="8">
            <el-input v-model="model.email_host" type="text" clearable />
          </el-col>
          <el-col :span="16"> 发送邮件服务器，如：smtp.qq.com </el-col>
        </el-form-item>
        <el-form-item label="SMTP协议" prop="email_secure">
          <el-col :span="8">
            <el-select v-model="model.email_secure" placeholder="">
              <el-option
                v-for="item in secure"
                :key="item.value"
                :label="item.label"
                :value="item.value"
              />
            </el-select>
          </el-col>
          <el-col :span="16"> 发送邮件服务器加密方式，如：SSL </el-col>
        </el-form-item>
        <el-form-item label="* SMTP端口" prop="email_port">
          <el-col :span="8">
            <el-input v-model="model.email_port" type="number" clearable />
          </el-col>
          <el-col :span="16"> 发送邮件服务器端口号，如：465 </el-col>
        </el-form-item>
        <el-form-item label="邮箱名称" prop="email_setfrom">
          <el-col :span="8">
            <el-input v-model="model.email_setfrom" type="text" clearable />
          </el-col>
          <el-col :span="16"> 发件邮箱名称即发件人，如：yylAdmin </el-col>
        </el-form-item>
        <el-form-item label="* 邮箱账号" prop="email_username">
          <el-col :span="8">
            <el-input v-model="model.email_username" type="text" clearable />
          </el-col>
          <el-col :span="16"> 发件邮箱账号，如：123456789@qq.com </el-col>
        </el-form-item>
        <el-form-item label="* 授权码/密码" prop="email_password">
          <el-col :span="8">
            <el-input v-model="model.email_password" type="password" clearable show-password />
          </el-col>
          <el-col :span="16"> 发件邮箱的授权码或密码，如：y1y2l3a4d5m6i7n </el-col>
        </el-form-item>
        <el-form-item label="测试邮箱" prop="email_test">
          <el-col :span="8">
            <el-input v-model="model.email_test" type="text" clearable />
          </el-col>
          <el-col :span="16">
            <el-button :loading="loading" text title="先提交再发送" @click="test()">
              发送测试邮件
            </el-button>
          </el-col>
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
import { emailInfo, emailEdit, emailTest } from '@/api/system/setting'

export default {
  name: 'SystemSettingEmail',
  data() {
    return {
      name: '邮件设置',
      height: 680,
      loading: false,
      model: {
        email_host: '',
        email_port: '',
        email_secure: '',
        email_username: '',
        email_password: '',
        email_setfrom: '',
        email_test: ''
      },
      rules: {
        email_secure: [{ required: true, message: '请选择SMTP协议', trigger: 'blur' }]
      },
      secure: [
        { value: 'ssl', label: 'SSL' },
        { value: 'tls', label: 'TLS' }
      ]
    }
  },
  computed: {
    emailFocusLabel() {
      if (!this.model.email_host || !this.model.email_username || !this.model.email_password) {
        return '先补 SMTP 通道'
      }
      return this.model.email_test ? '可以发测试邮件' : '先补测试邮箱'
    },
    emailGuideCards() {
      return [
        {
          title: '第一步：先补发信通道',
          desc: 'SMTP 主机、端口、协议、账号和授权码缺一个都容易导致发送失败，先把通道配完整。',
          action: this.model.email_host || '当前还没有 SMTP 主机'
        },
        {
          title: '第二步：再看发件身份',
          desc: '邮箱名称和发件账号会直接影响收件人看到的展示信息，也容易被误认为配置成功但展示不对。',
          action: this.model.email_username || '当前未配置发件账号'
        },
        {
          title: '第三步：最后发测试邮件',
          desc: '测试邮箱不是配置项本身，而是验证结果的入口。先提交再发测试，才更接近真实发送链路。',
          action: this.model.email_test || '当前未填写测试邮箱'
        }
      ]
    }
  },
  created() {
    this.height = screenHeight(210)
    this.info()
  },
  methods: {
    goToRoute(path, query = {}) {
      this.$router.push({ path, query: { from: 'system-setting-emails', ...query } })
    },
    // 信息
    info() {
      emailInfo().then((res) => {
        this.model = res.data
      })
    },
    // 刷新
    refresh() {
      this.loading = true
      emailInfo()
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
          emailEdit(this.model)
            .then((res) => {
              this.loading = false
              ElMessage.success(res.msg)
              this.$nextTick(() => {
                ElMessage.info('提交后请去用户日志、通知页和真实收件箱一起复核邮件链路。')
              })
            })
            .catch(() => {
              this.loading = false
            })
        }
      })
    },
    // 测试
    test() {
      this.$refs['ref'].validate((valid) => {
        if (valid) {
          if (!this.model.email_test) {
            ElMessage.error('请输入测试邮箱')
          } else {
            this.loading = true
            emailTest(this.model)
              .then((res) => {
                this.loading = false
                ElMessage.success(res.msg)
                this.$nextTick(() => {
                  ElMessage.info('测试后请检查真实收件箱，并回用户日志核对失败或成功记录。')
                })
              })
              .catch(() => {
                this.loading = false
              })
          }
        }
      })
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
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 16px;
  padding: 14px 16px;
  border: 1px solid #dbe7f5;
  border-radius: 14px;
  background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
}

.setting-followup-strip__title {
  font-size: 13px;
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
  flex-wrap: wrap;
  gap: 8px;
  justify-content: flex-end;
}

@media (max-width: 960px) {
  .setting-guide-panel__header {
    flex-direction: column;
  }

  .setting-guide-panel__badge {
    min-width: auto;
    width: 100%;
  }

  .setting-guide-panel__grid {
    grid-template-columns: 1fr;
  }

  .setting-followup-strip {
    flex-direction: column;
    align-items: flex-start;
  }

  .setting-followup-strip__actions {
    width: 100%;
    justify-content: flex-start;
  }
}
</style>
