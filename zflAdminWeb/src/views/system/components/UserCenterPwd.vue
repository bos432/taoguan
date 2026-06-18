<template>
  <el-scrollbar native :max-height="height - 30">
    <div class="pwd-panel">
      <div class="overview-grid">
        <div class="overview-card">
          <span class="overview-label">安全动作</span>
          <strong>密码更新</strong>
          <p>提交后立即影响当前账号登录凭证。</p>
        </div>
        <div class="overview-card">
          <span class="overview-label">校验要求</span>
          <strong>{{ passwordStrength }}</strong>
          <p>确认密码需与新密码完全一致。</p>
        </div>
        <div class="overview-card">
          <span class="overview-label">操作建议</span>
          <strong>{{ actionHint }}</strong>
          <p>建议修改后重新验证登录状态。</p>
        </div>
      </div>

      <div class="section-title">
        <div>
          <h3>修改密码</h3>
          <p>延续原有提交接口，增强页面说明，降低误操作概率。</p>
        </div>
        <div class="section-actions">
          <el-button :loading="loading" @click="reset">重置</el-button>
          <el-button :loading="loading" type="primary" @click="submit">提交</el-button>
        </div>
      </div>
      <div class="plain-guide-panel">
        <div class="plain-guide-panel__header">
          <div>
            <div class="plain-guide-panel__title">改密码前先确认会影响当前账号立即重登</div>
            <div class="plain-guide-panel__desc">
              这不是普通资料修改。密码一旦提交成功，最应该做的是重新登录和核对日志，而不是继续在当前页面停留。
            </div>
          </div>
          <span class="plain-guide-panel__badge">{{ passwordFocusLabel }}</span>
        </div>
      </div>

      <div class="follow-up-strip">
        <div>
          <h4>密码改完要走一轮真登录验证</h4>
          <p>
            提交成功后，请直接退出并到后台登录页重新登录，再回操作日志确认本次安全动作已落记录。
          </p>
        </div>
        <div class="follow-up-strip__actions">
          <el-button plain @click="goToRoute('/system/user-log')">去操作日志核对</el-button>
          <el-button plain @click="goToRoute('/system/user-center', { tab: 'info' })"
            >回个人资料</el-button
          >
          <el-button type="primary" plain @click="openAdminLogin">去后台登录页重登</el-button>
        </div>
      </div>

      <el-row :gutter="16">
        <el-col :span="12">
          <el-form ref="ref" :rules="rules" :model="model" label-width="120px" class="form-card">
            <el-form-item label="旧密码" prop="password_old">
              <el-input
                v-model="model.password_old"
                type="password"
                placeholder="请输入旧密码"
                autocomplete="off"
                clearable
                show-password
              />
            </el-form-item>
            <el-form-item label="新密码" prop="password_new">
              <el-input
                v-model="model.password_new"
                type="password"
                placeholder="请输入新密码"
                autocomplete="off"
                clearable
                show-password
              />
            </el-form-item>
            <el-form-item label="确认新密码" prop="password_confirm">
              <el-input
                v-model="model.password_confirm"
                type="password"
                placeholder="请再次输入新密码"
                autocomplete="off"
                clearable
                show-password
              />
            </el-form-item>
          </el-form>
        </el-col>
      </el-row>
    </div>
  </el-scrollbar>
</template>

<script>
import screenHeight from '@/utils/screen-height'
import { pwd } from '@/api/system/user-center'

export default {
  name: 'SystemUserCenterPwd',
  data() {
    var validatePasswordConfirm = (rule, value, callback) => {
      if (value !== this.model.password_new) {
        callback(new Error('两次输入的新密码不一致'))
      } else {
        callback()
      }
    }
    return {
      name: '修改密码',
      height: 680,
      loading: false,
      model: {
        password_old: '',
        password_new: '',
        password_confirm: ''
      },
      rules: {
        password_old: [{ required: true, message: '请输入旧密码', trigger: 'blur' }],
        password_new: [{ required: true, message: '请输入新密码', trigger: 'blur' }],
        password_confirm: [
          { required: true, message: '请再次输入新密码', trigger: 'blur' },
          { validator: validatePasswordConfirm, trigger: 'blur' }
        ]
      }
    }
  },
  computed: {
    passwordFocusLabel() {
      if (this.model.password_new && this.model.password_new === this.model.password_confirm) {
        return '提交后先退出重登'
      }
      if (this.model.password_new || this.model.password_confirm) {
        return '先确认两次新密码一致'
      }
      return '先填写旧密码与新密码'
    },
    passwordStrength() {
      const len = this.model.password_new.length
      if (len >= 12) {
        return '高强度'
      }
      if (len >= 6) {
        return '建议再加强'
      }
      return '待输入新密码'
    },
    actionHint() {
      if (this.model.password_old || this.model.password_new || this.model.password_confirm) {
        return '请确认后再提交'
      }
      return '先填写旧密码与新密码'
    }
  },
  created() {
    this.height = screenHeight(210)
  },
  methods: {
    // 重置
    reset() {
      this.$refs['ref'].resetFields()
      this.$refs['ref'].clearValidate()
    },
    // 提交
    submit() {
      this.$refs['ref'].validate((valid) => {
        if (valid) {
          this.loading = true
          pwd(this.model)
            .then((res) => {
              this.loading = false
              ElMessage.success(res.msg)
              this.$nextTick(() => {
                ElMessage.info('密码已提交，请退出当前账号并到后台登录页重新登录，再核对操作日志。')
              })
            })
            .catch(() => {
              this.loading = false
            })
        }
      })
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
.pwd-panel {
  padding: 4px 2px 12px;
}

.overview-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 14px;
  margin-bottom: 18px;
}

.overview-card,
.form-card {
  border: 1px solid #ebeef5;
  border-radius: 14px;
  background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
  box-shadow: 0 10px 24px rgba(31, 35, 41, 0.06);
}

.overview-card {
  padding: 16px 18px;
}

.overview-label {
  display: inline-block;
  margin-bottom: 10px;
  color: #7a8599;
  font-size: 12px;
}

.overview-card strong {
  display: block;
  color: #1f2329;
  font-size: 18px;
}

.overview-card p {
  margin: 8px 0 0;
  color: #5f6b7a;
  font-size: 13px;
}

.section-title {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 16px;
}

.section-title h3 {
  margin: 0 0 4px;
  color: #1f2329;
  font-size: 18px;
}

.section-title p {
  margin: 0;
  color: #7a8599;
  font-size: 13px;
}

.section-actions {
  display: flex;
  gap: 10px;
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

.plain-guide-panel {
  margin-bottom: 16px;
  padding: 16px;
  border: 1px solid #dbe7f5;
  border-radius: 14px;
  background: linear-gradient(135deg, #f7fbff 0%, #ffffff 100%);
}

.plain-guide-panel__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
}

.plain-guide-panel__title {
  font-size: 15px;
  font-weight: 700;
  color: #1f2329;
}

.plain-guide-panel__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.plain-guide-panel__badge {
  min-width: 180px;
  padding: 10px 12px;
  border-radius: 12px;
  background: rgba(37, 99, 235, 0.08);
  color: #1d4ed8;
  font-size: 12px;
  font-weight: 700;
  line-height: 1.6;
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

  .section-title {
    flex-direction: column;
    align-items: flex-start;
  }

  .follow-up-strip {
    flex-direction: column;
    align-items: flex-start;
  }

  .plain-guide-panel__header {
    flex-direction: column;
  }

  .plain-guide-panel__badge {
    min-width: auto;
    width: 100%;
  }

  .follow-up-strip__actions {
    justify-content: flex-start;
  }
}
</style>
