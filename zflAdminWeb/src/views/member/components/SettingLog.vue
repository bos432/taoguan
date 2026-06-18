<template>
  <el-row>
    <el-col :span="14">
      <el-form ref="ref" :model="model" :rules="rules" label-width="120px">
        <div class="setting-guide-panel">
          <div class="setting-guide-panel__header">
            <div>
              <div class="setting-guide-panel__title">
                会员日志设置先想清楚是保留线索还是减轻压力
              </div>
              <div class="setting-guide-panel__desc">
                会员日志主要用于排查登录、注册、下单等问题。先判断现在是“查不到线索”，还是“数据积累太多”，再调开关和保留时间。
              </div>
            </div>
            <span class="setting-guide-panel__badge">{{ memberLogFocusLabel }}</span>
          </div>
          <div class="setting-guide-panel__grid">
            <div v-for="item in memberLogGuideCards" :key="item.title" class="setting-guide-card">
              <div class="setting-guide-card__title">{{ item.title }}</div>
              <div class="setting-guide-card__desc">{{ item.desc }}</div>
              <div class="setting-guide-card__action">{{ item.action }}</div>
            </div>
          </div>
        </div>
        <div class="setting-panel-overview">
          <div class="setting-panel-overview__card">
            <span class="setting-panel-overview__label">日志开关</span>
            <strong>{{ model.log_switch ? '开启' : '关闭' }}</strong>
          </div>
          <div class="setting-panel-overview__card">
            <span class="setting-panel-overview__label">保留策略</span>
            <strong>{{ model.log_save_time ? `${model.log_save_time} 天` : '永久保留' }}</strong>
          </div>
          <div class="setting-panel-overview__card">
            <span class="setting-panel-overview__label">适用对象</span>
            <strong>会员端日志</strong>
          </div>
        </div>
        <div class="setting-followup-strip">
          <div class="setting-followup-strip__copy">
            <div class="setting-followup-strip__title">改完要去触发一次真实会员操作</div>
            <div class="setting-followup-strip__desc">
              会员日志设置只有在真实登录、注册、下单后才能验证有没有生效，不能只看这里提交成功。
            </div>
          </div>
          <div class="setting-followup-strip__actions">
            <el-button plain @click="openMemberLogin">去 H5 登录页触发</el-button>
            <el-button plain @click="goToRoute('/member/log')">去会员日志查看</el-button>
            <el-button type="primary" plain @click="goToRoute('/member/member')"
              >去会员列表联查</el-button
            >
          </div>
        </div>
        <el-form-item label="日志记录开关" prop="log_switch">
          <el-col :span="8">
            <el-switch v-model="model.log_switch" :active-value="1" :inactive-value="0" />
          </el-col>
          <el-col :span="16"> 开启后，会记录会员日志 </el-col>
        </el-form-item>
        <el-form-item label="日志保留时间" prop="log_save_time">
          <el-col :span="8">
            <el-input v-model="model.log_save_time" type="number">
              <template #append>天</template>
            </el-input>
          </el-col>
          <el-col :span="16"> 会员日志保留天数，0永久保留 </el-col>
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
import { logInfo, logEdit } from '@/api/member/setting'

export default {
  name: 'MemberSettingLog',
  data() {
    return {
      name: '日志设置',
      height: 680,
      loading: false,
      model: {
        log_switch: 0,
        log_save_time: 0
      },
      rules: {}
    }
  },
  computed: {
    memberLogFocusLabel() {
      if (!this.model.log_switch) {
        return '当前不记录会员日志'
      }
      return Number(this.model.log_save_time) === 0 ? '当前永久保留' : '优先核保留周期'
    },
    memberLogGuideCards() {
      return [
        {
          title: '第一步：先看有没有日志落下来',
          desc: '如果会员端异常经常没法复盘，先确认日志开关是不是开着，而不是直接去清库。',
          action: this.model.log_switch ? '当前已开启会员日志' : '当前会员日志关闭'
        },
        {
          title: '第二步：再看保留时间',
          desc: '保留太短会丢掉排查证据，永久保留又会持续涨数据量，要按复盘周期来定。',
          action:
            Number(this.model.log_save_time) === 0
              ? '当前永久保留'
              : `当前保留 ${this.model.log_save_time} 天`
        },
        {
          title: '第三步：最后回前端链路验证',
          desc: '改完后要从会员登录、注册、下单这些关键链路触发一次，看日志是否正常产生。',
          action: '重点回查登录、注册、订单操作日志。'
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
      this.$router.push({ path, query: { from: 'member-setting-log', ...query } })
    },
    openMemberLogin() {
      window.open(`${window.location.origin}/app/pages/my/login`, '_blank', 'noopener')
    },
    // 信息
    info() {
      logInfo().then((res) => {
        this.model = res.data
      })
    },
    // 刷新
    refresh() {
      this.loading = true
      logInfo()
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
          logEdit(this.model)
            .then((res) => {
              this.loading = false
              ElMessage.success(res.msg)
              this.$nextTick(() => {
                ElMessage.info('提交后请去 H5 登录页触发一次真实操作，再回会员日志查看是否落记录。')
              })
            })
            .catch(() => {
              this.loading = false
            })
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
