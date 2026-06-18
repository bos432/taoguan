<template>
  <el-row>
    <el-col :span="16">
      <el-form ref="ref" :model="model" :rules="rules" label-width="120px">
        <div class="setting-guide-panel">
          <div class="setting-guide-panel__header">
            <div>
              <div class="setting-guide-panel__title">后台验证码设置先看拦截强度</div>
              <div class="setting-guide-panel__desc">
                先判断后台登录现在是“太松”还是“太严”，再决定开关、方式和类型，不要只改一种验证码样式就结束。
              </div>
            </div>
            <span class="setting-guide-panel__badge">{{ captchaFocusLabel }}</span>
          </div>
          <div class="setting-guide-panel__grid">
            <div v-for="item in captchaGuideCards" :key="item.title" class="setting-guide-card">
              <div class="setting-guide-card__title">{{ item.title }}</div>
              <div class="setting-guide-card__desc">{{ item.desc }}</div>
              <div class="setting-guide-card__action">{{ item.action }}</div>
            </div>
          </div>
        </div>
        <div class="setting-panel-overview">
          <div class="setting-panel-overview__card">
            <span class="setting-panel-overview__label">验证码状态</span>
            <strong>{{ model.captcha_switch ? '开启' : '关闭' }}</strong>
          </div>
          <div class="setting-panel-overview__card">
            <span class="setting-panel-overview__label">验证方式</span>
            <strong>{{ captchaModeLabel }}</strong>
          </div>
          <div class="setting-panel-overview__card">
            <span class="setting-panel-overview__label">适用范围</span>
            <strong>后台登录入口</strong>
          </div>
        </div>
        <div class="setting-followup-strip">
          <div class="setting-followup-strip__main">
            <div class="setting-followup-strip__title">保存后要马上回查真实登录体验</div>
            <div class="setting-followup-strip__desc">
              验证码配置最容易出现“保存成功，但登录页拦死或加载异常”。提交后建议立刻去后台登录页试一次，再回日志看异常。
            </div>
          </div>
          <div class="setting-followup-strip__actions">
            <el-button text type="primary" @click="openLoginPreview">打开登录页</el-button>
            <el-button text type="primary" @click="goToRoute('/system/user-log')">去操作日志</el-button>
            <el-button text type="primary" @click="goToRoute('/system/user')">去后台用户</el-button>
          </div>
        </div>
        <el-form-item label="验证码开关" prop="captcha_switch">
          <el-col :span="8">
            <el-switch v-model="model.captcha_switch" :active-value="1" :inactive-value="0" />
          </el-col>
          <el-col :span="16"> 开启后，后台登录需要输入验证码。 </el-col>
        </el-form-item>
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
          <el-col :span="16"> 字符：输入字符；行为：滑动或点选。 </el-col>
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
import { captchaInfo, captchaEdit } from '@/api/system/setting'

export default {
  name: 'SystemSettingCaptcha',
  data() {
    return {
      name: '验证码设置',
      height: 680,
      loading: false,
      model: {
        captcha_switch: 0,
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
  computed: {
    captchaFocusLabel() {
      if (!this.model.captcha_switch) {
        return '当前未开启验证码'
      }
      return this.model.captcha_mode === 1 ? '优先核字符体验' : '优先核行为拦截'
    },
    captchaModeLabel() {
      const list = this.model.captcha_mode === 1 ? this.typestr : this.typeaj
      const current = list.find((item) => item.value === this.model.captcha_type)
      return current ? `${this.model.captcha_mode === 1 ? '字符' : '行为'} / ${current.label}` : '未设置'
    },
    captchaGuideCards() {
      return [
        {
          title: '第一步：先判断要不要拦后台登录',
          desc: '如果后台登录风险高，就先开验证码；如果联调环境频繁登录，先确认是否需要临时放宽。',
          action: this.model.captcha_switch ? '当前已开启后台验证码' : '当前后台验证码关闭'
        },
        {
          title: '第二步：再选方式和类型',
          desc: '字符更轻量，行为更偏防刷；具体类型决定操作体验和识别强度。',
          action: this.captchaModeLabel
        },
        {
          title: '第三步：最后回登录页验证',
          desc: '提交后要回后台登录页实测一次，确认不是只保存成功，而是真正可登录。',
          action: '重点回查登录页加载、识别、错误提示。'
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
              this.$nextTick(() => {
                ElMessage.info('建议立刻打开后台登录页实测验证码，再回操作日志页看是否有异常')
              })
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
    goToRoute(path) {
      this.$router.push({
        path,
        query: {
          from: 'system-setting-captcha'
        }
      })
    },
    openLoginPreview() {
      const loginUrl = `${window.location.origin}/admin-next/#/login`
      window.open(loginUrl, '_blank', 'noopener')
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
