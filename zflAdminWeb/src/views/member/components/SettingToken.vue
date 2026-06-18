<template>
  <el-row>
    <el-col :span="14">
      <el-form ref="ref" :model="model" :rules="rules" label-width="120px">
        <div class="setting-guide-panel">
          <div class="setting-guide-panel__header">
            <div>
              <div class="setting-guide-panel__title">会员 Token 设置先评估登录链路影响</div>
              <div class="setting-guide-panel__desc">
                这页直接影响 H5、小程序和 APP 的登录态。先确认是调时长、多端，还是准备让所有会员重新登录。
              </div>
            </div>
            <span class="setting-guide-panel__badge">{{ memberTokenFocusLabel }}</span>
          </div>
          <div class="setting-guide-panel__grid">
            <div v-for="item in memberTokenGuideCards" :key="item.title" class="setting-guide-card">
              <div class="setting-guide-card__title">{{ item.title }}</div>
              <div class="setting-guide-card__desc">{{ item.desc }}</div>
              <div class="setting-guide-card__action">{{ item.action }}</div>
            </div>
          </div>
        </div>
        <div class="setting-panel-overview">
          <div class="setting-panel-overview__card">
            <span class="setting-panel-overview__label">会话时长</span>
            <strong>{{ model.token_exp || 0 }} 小时</strong>
          </div>
          <div class="setting-panel-overview__card">
            <span class="setting-panel-overview__label">多端登录</span>
            <strong>{{ model.is_multi_login ? '允许' : '单端' }}</strong>
          </div>
          <div class="setting-panel-overview__card">
            <span class="setting-panel-overview__label">风险提示</span>
            <strong>改密钥会强制下线</strong>
          </div>
        </div>
        <div class="followup-panel">
          <div class="followup-panel__header">
            <div>
              <div class="followup-panel__title">提交后先去哪里验</div>
              <div class="followup-panel__desc">
                Token 设置改完后，最容易出问题的是登录态失效、多端互挤和接口鉴权，不建议只看提交成功提示就结束。
              </div>
            </div>
            <span class="followup-panel__badge">{{ memberTokenFocusLabel }}</span>
          </div>
          <div class="followup-panel__actions">
            <el-button @click="goToMemberLog()">去会员日志看掉线情况</el-button>
            <el-button @click="goToMemberApi()">去会员接口页看鉴权</el-button>
            <el-button type="primary" @click="openH5Login()">打开 H5 登录页实测</el-button>
          </div>
        </div>
        <el-form-item label="Token密钥" prop="token_key">
          <el-col :span="8">
            <el-input v-model="model.token_key" type="text" clearable />
          </el-col>
          <el-col :span="16"> 修改后会员登录状态失效，需重新登录 </el-col>
        </el-form-item>
        <el-form-item label="Token有效时间" prop="token_exp">
          <el-col :span="8">
            <el-input v-model="model.token_exp" type="number" />
          </el-col>
          <el-col :span="16"> 小时，登录后超过此时间，需重新登录 </el-col>
        </el-form-item>
        <el-form-item label="多端登录" prop="is_multi_login">
          <el-col :span="8">
            <el-switch v-model="model.is_multi_login" :active-value="1" :inactive-value="0" />
          </el-col>
          <el-col :span="16"> 开启后可以在多个设备同时登录 </el-col>
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
import { tokenInfo, tokenEdit } from '@/api/member/setting'

export default {
  name: 'MemberSettingToken',
  data() {
    return {
      name: 'Token设置',
      height: 680,
      loading: false,
      model: {
        token_key: '',
        token_exp: 720,
        is_multi_login: 0
      },
      rules: {
        token_key: [{ required: true, message: '请输入Token密钥', trigger: 'blur' }]
      }
    }
  },
  computed: {
    memberTokenFocusLabel() {
      if (!this.model.token_key) {
        return '先补会员 Token 密钥'
      }
      if (Number(this.model.token_exp || 0) < 24) {
        return '优先核会话时长'
      }
      return this.model.is_multi_login ? '优先核多端并发' : '优先核单端挤下线'
    },
    memberTokenGuideCards() {
      return [
        {
          title: '第一步：先确认会不会让会员重新登录',
          desc: '改 Token 密钥会让现有会员登录态失效，所以要先评估公告和用户影响。',
          action: this.model.token_key ? '当前密钥已配置' : '当前密钥未配置'
        },
        {
          title: '第二步：再看有效时间',
          desc: '会员端会话太短会频繁掉登录，太长又会提高账号安全风险，通常先调这里。',
          action: `当前有效时间：${this.model.token_exp || 0} 小时`
        },
        {
          title: '第三步：最后看多端策略',
          desc: '是否允许同一会员多端共存，会直接影响手机、小程序、H5 的并发登录体验。',
          action: this.model.is_multi_login ? '当前允许多端登录' : '当前限制单端登录'
        }
      ]
    }
  },
  created() {
    this.height = screenHeight(210)
    this.info()
  },
  methods: {
    buildEntryRouteQuery(extraQuery = {}, nextFrom = 'member-setting') {
      return {
        ...this.$route.query,
        ...extraQuery,
        from: nextFrom
      }
    },
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
              ElMessage.info('建议下一步去会员日志看登录态变化，再打开 H5 登录页做一次真实登录回归。')
            })
            .catch(() => {
              this.loading = false
            })
        }
      })
    },
    goToMemberLog() {
      this.$router.push({
        path: '/member/log',
        query: this.buildEntryRouteQuery({}, 'member-setting-log')
      })
    },
    goToMemberApi() {
      this.$router.push({
        path: '/member/api',
        query: this.buildEntryRouteQuery({}, 'member-setting-api')
      })
    },
    openH5Login() {
      window.open(`${window.location.origin}/app/pages/my/login`, '_blank')
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

.followup-panel {
  margin-bottom: 16px;
  padding: 16px;
  border: 1px solid #fde68a;
  border-radius: 16px;
  background: linear-gradient(135deg, #fffdf5 0%, #ffffff 100%);
}

.followup-panel__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
}

.followup-panel__title {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
}

.followup-panel__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.followup-panel__badge {
  min-width: 180px;
  padding: 10px 12px;
  border-radius: 12px;
  background: rgba(245, 158, 11, 0.12);
  color: #b45309;
  font-size: 12px;
  font-weight: 700;
  line-height: 1.6;
}

.followup-panel__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 14px;
}

.setting-panel-overview__label {
  display: block;
  margin-bottom: 8px;
  font-size: 12px;
  color: #7c8aa5;
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

  .followup-panel__header {
    flex-direction: column;
  }

  .followup-panel__badge {
    min-width: auto;
    width: 100%;
  }
}
</style>
