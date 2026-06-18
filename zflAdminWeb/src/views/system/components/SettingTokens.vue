<template>
  <el-row>
    <el-col :span="16">
      <el-form ref="ref" :model="model" :rules="rules" label-width="120px">
        <div class="setting-guide-panel">
          <div class="setting-guide-panel__header">
            <div>
              <div class="setting-guide-panel__title">Token 配置别直接改密钥</div>
              <div class="setting-guide-panel__desc">
                先确认这次是调登录时长还是处理多端登录；只有确定要全量踢下线时，才去改 Token 密钥。
              </div>
            </div>
            <span class="setting-guide-panel__badge">{{ tokenFocusLabel }}</span>
          </div>
          <div class="setting-guide-panel__grid">
            <div v-for="item in tokenGuideCards" :key="item.title" class="setting-guide-card">
              <div class="setting-guide-card__title">{{ item.title }}</div>
              <div class="setting-guide-card__desc">{{ item.desc }}</div>
              <div class="setting-guide-card__action">{{ item.action }}</div>
            </div>
          </div>
        </div>
        <div class="setting-panel-overview">
          <div class="setting-panel-overview__card">
            <span class="setting-panel-overview__label">Token 时长</span>
            <strong>{{ model.token_exp || 0 }} 小时</strong>
          </div>
          <div class="setting-panel-overview__card">
            <span class="setting-panel-overview__label">登录模式</span>
            <strong>{{ model.is_multi_login ? '支持多端' : '单端限制' }}</strong>
          </div>
          <div class="setting-panel-overview__card">
            <span class="setting-panel-overview__label">运维提醒</span>
            <strong>改密钥会清空会话</strong>
          </div>
        </div>
        <div class="setting-followup-strip">
          <div class="setting-followup-strip__main">
            <div class="setting-followup-strip__title">改完别停在当前页</div>
            <div class="setting-followup-strip__desc">
              Token 配置真正的风险在“保存后登录态有没有异常”。提交后建议立刻去日志、后台用户和接口文档页做交叉复核。
            </div>
          </div>
          <div class="setting-followup-strip__actions">
            <el-button text type="primary" @click="goToRoute('/system/user-log')">去操作日志</el-button>
            <el-button text type="primary" @click="goToRoute('/system/user')">去后台用户</el-button>
            <el-button text type="primary" @click="goToRoute('/system/apidoc')">去接口文档</el-button>
          </div>
        </div>
        <el-form-item label="Token密钥" prop="token_key">
          <el-col :span="8">
            <el-input v-model="model.token_key" type="text" clearable />
          </el-col>
          <el-col :span="16"> 修改后用户登录状态失效，需重新登录。 </el-col>
        </el-form-item>
        <el-form-item label="Token有效时间" prop="token_exp">
          <el-col :span="8">
            <el-input v-model="model.token_exp" type="number">
              <template #append>小时</template>
            </el-input>
          </el-col>
          <el-col :span="16"> 登录成功后超过有效时间，需重新登录。 </el-col>
        </el-form-item>
        <el-form-item label="多端登录" prop="is_multi_login">
          <el-col :span="8">
            <el-switch v-model="model.is_multi_login" :active-value="1" :inactive-value="0" />
          </el-col>
          <el-col :span="16"> 开启后可以在多个设备同时登录。 </el-col>
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
import { tokenInfo, tokenEdit } from '@/api/system/setting'

export default {
  name: 'SystemSettingToken',
  data() {
    return {
      name: 'Token设置',
      height: 680,
      loading: false,
      model: {
        token_key: '',
        token_exp: 12,
        is_multi_login: 0
      },
      rules: {
        token_key: [{ required: true, message: '请输入Token密钥', trigger: 'blur' }]
      }
    }
  },
  computed: {
    tokenFocusLabel() {
      if (!this.model.token_key) {
        return '先补 Token 密钥'
      }
      if ((this.model.token_exp || 0) <= 2) {
        return '先确认时长是否过短'
      }
      if (this.model.is_multi_login) {
        return '先核多端并发风险'
      }
      return '优先关注会话失效影响'
    },
    tokenGuideCards() {
      return [
        {
          title: '第一步：先确认是不是要踢用户下线',
          desc: '改 Token 密钥会让现有登录态失效，所以只有确定要全量失效时才动它。',
          action: this.model.token_key ? '当前密钥已配置' : '当前密钥未配置'
        },
        {
          title: '第二步：再看登录时长',
          desc: '登录有效时间过短会增加反复登录，过长又会提高会话风险，通常先在这里调平衡。',
          action: `当前有效时间：${this.model.token_exp || 0} 小时`
        },
        {
          title: '第三步：最后看多端策略',
          desc: '多端登录决定同一账号是否允许并发在线，改前要先和运营、安全策略对齐。',
          action: this.model.is_multi_login ? '当前允许多端并发' : '当前限制单端登录'
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
      tokenInfo().then((res) => {
        this.model = res.data
      })
    },
    // 刷新
    refresh() {
      this.loading = true
      tokenInfo()
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
          tokenEdit(this.model)
            .then((res) => {
              this.loading = false
              ElMessage.success(res.msg)
              this.$nextTick(() => {
                ElMessage.info('建议继续去操作日志或接口文档页复核登录态和鉴权结果')
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
          from: 'system-setting-token'
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
