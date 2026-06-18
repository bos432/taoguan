<template>
  <div class="captcha-panel">
    <div class="setting-guide-panel">
      <div class="setting-guide-panel__header">
        <div>
          <div class="setting-guide-panel__title">会员验证码设置先看体验还是拦截</div>
          <div class="setting-guide-panel__desc">
            这页主要影响会员端登录、注册、找回密码。先判断当前问题更偏“太容易刷”还是“太难过”，再选模式和类型。
          </div>
        </div>
        <span class="setting-guide-panel__badge">{{ memberCaptchaFocusLabel }}</span>
      </div>
      <div class="setting-guide-panel__grid">
        <div v-for="item in memberCaptchaGuideCards" :key="item.title" class="setting-guide-card">
          <div class="setting-guide-card__title">{{ item.title }}</div>
          <div class="setting-guide-card__desc">{{ item.desc }}</div>
          <div class="setting-guide-card__action">{{ item.action }}</div>
        </div>
      </div>
    </div>
    <div class="overview-grid">
      <div class="overview-card">
        <span class="overview-label">当前模式</span>
        <strong>{{ currentModeLabel }}</strong>
        <p>字符校验适合轻量场景，行为校验更偏防刷。</p>
      </div>
      <div class="overview-card">
        <span class="overview-label">当前类型</span>
        <strong>{{ currentTypeLabel }}</strong>
        <p>切换模式时会自动同步默认类型。</p>
      </div>
      <div class="overview-card">
        <span class="overview-label">运营提示</span>
        <strong>{{ modeTip }}</strong>
        <p>建议根据登录、注册、找回密码等环节分别核对体验。</p>
      </div>
    </div>
    <div class="followup-panel">
      <div class="followup-panel__header">
        <div>
          <div class="followup-panel__title">改完后建议这样验</div>
          <div class="followup-panel__desc">
            提交成功后，不要只停在设置页。先去会员日志看验证码链路，再去 H5 登录页回查真实体验。
          </div>
        </div>
        <span class="followup-panel__badge">先看日志，再看入口</span>
      </div>
      <div class="followup-panel__actions">
        <el-button @click="goToMemberLog()">去会员日志看失败/拦截</el-button>
        <el-button @click="goToMemberList()">去会员列表抽样复核</el-button>
        <el-button type="primary" @click="openH5Login()">打开 H5 登录页实测</el-button>
      </div>
    </div>

    <div class="section-title">
      <div>
        <h3>验证码设置</h3>
        <p>保持原有配置接口不变，补一层更直观的状态说明和操作区。</p>
      </div>
      <div class="section-actions">
        <el-button :loading="loading" @click="refresh()">刷新</el-button>
        <el-button :loading="loading" type="primary" @click="submit()">提交</el-button>
      </div>
    </div>

    <el-row :gutter="16">
      <el-col :span="14">
        <el-form ref="ref" :model="model" :rules="rules" label-width="120px" class="form-card">
        <el-form-item label="验证码方式" prop="captcha_mode">
          <el-col :span="8">
            <el-select v-model="model.captcha_mode" placeholder="" @change="modeChange">
              <el-option
                v-for="item in modes"
                :key="item.value"
                :label="item.label"
                :value="item.value"
              />
            </el-select>
          </el-col>
          <el-col :span="16"> 字符：输入字符；行为：滑动或点选 </el-col>
        </el-form-item>
        <el-form-item label="验证码类型" prop="captcha_type">
          <el-col :span="8">
            <el-select v-if="model.captcha_mode == 1" v-model="model.captcha_type" placeholder="">
              <el-option
                v-for="item in typestr"
                :key="item.value"
                :label="item.label"
                :value="item.value"
              />
            </el-select>
            <el-select v-else v-model="model.captcha_type" placeholder="">
              <el-option
                v-for="item in typeaj"
                :key="item.value"
                :label="item.label"
                :value="item.value"
              />
            </el-select>
          </el-col>
        </el-form-item>
        </el-form>
      </el-col>
    </el-row>
  </div>
</template>

<script>
import screenHeight from '@/utils/screen-height'
import { captchaInfo, captchaEdit } from '@/api/member/setting'

export default {
  name: 'MemberSettingCaptcha',
  computed: {
    memberCaptchaFocusLabel() {
      return this.model.captcha_mode === 1 ? '优先核输入体验' : '优先核防刷强度'
    },
    currentModeLabel() {
      const current = this.modes.find((item) => item.value === this.model.captcha_mode)
      return current ? current.label : '未设置'
    },
    currentTypeLabel() {
      const options = this.model.captcha_mode === 1 ? this.typestr : this.typeaj
      const current = options.find((item) => item.value === this.model.captcha_type)
      return current ? current.label : '未设置'
    },
    modeTip() {
      return this.model.captcha_mode === 1 ? '优先兼顾输入便捷性' : '优先提升拦截与校验强度'
    },
    memberCaptchaGuideCards() {
      return [
        {
          title: '第一步：先看会员端是卡体验还是卡风控',
          desc: '会员验证码主要影响登录、注册和找回密码，改前先明确是要提升通过率还是提高拦截。',
          action: this.modeTip
        },
        {
          title: '第二步：再选模式和类型',
          desc: '字符模式更轻，行为模式更强；类型决定具体输入、滑块或点选动作。',
          action: `${this.currentModeLabel} / ${this.currentTypeLabel}`
        },
        {
          title: '第三步：最后去 H5 和小程序回查',
          desc: '提交后要分别看 H5 和小程序登录链路，避免某一端能过、另一端异常。',
          action: '重点回查登录、注册、找回密码。'
        }
      ]
    }
  },
  data() {
    return {
      name: '验证码设置',
      height: 680,
      loading: false,
      model: {
        captcha_mode: 1,
        captcha_type: 1
      },
      rules: {},
      modes: [
        { value: 1, label: '字符' },
        { value: 2, label: '行为' }
      ],
      typestr: [
        { value: 1, label: '数字' },
        { value: 2, label: '字母' },
        { value: 3, label: '数字字母' },
        { value: 4, label: '算术' },
        { value: 5, label: '中文' }
      ],
      typeaj: [
        { value: 1, label: '滑动拼图' },
        { value: 2, label: '点选文字' }
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
      captchaInfo().then((res) => {
        this.model = res.data
      })
    },
    // 刷新
    refresh() {
      this.loading = true
      captchaInfo()
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
          captchaEdit(this.model)
            .then((res) => {
              this.loading = false
              ElMessage.success(res.msg)
              ElMessage.info('下一步建议先去会员日志看验证码拦截结果，再打开 H5 登录页实测。')
            })
            .catch(() => {
              this.loading = false
            })
        }
      })
    },
    modeChange(value) {
      this.model.captcha_type = value
    },
    goToMemberLog() {
      this.$router.push({
        path: '/member/log',
        query: this.buildEntryRouteQuery({}, 'member-setting-log')
      })
    },
    goToMemberList() {
      this.$router.push({
        path: '/member/member',
        query: this.buildEntryRouteQuery({}, 'member-setting-member')
      })
    },
    openH5Login() {
      window.open(`${window.location.origin}/app/pages/my/login`, '_blank')
    }
  }
}
</script>

<style scoped>
.captcha-panel {
  padding: 4px 2px 12px;
}

.setting-guide-panel {
  margin-bottom: 18px;
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

.overview-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 14px;
  margin-bottom: 18px;
}

.followup-panel {
  margin-bottom: 18px;
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
