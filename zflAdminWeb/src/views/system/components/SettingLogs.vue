<template>
  <el-row>
    <el-col :span="16">
      <el-form ref="ref" :model="model" :rules="rules" label-width="120px">
        <div class="setting-guide-panel">
          <div class="setting-guide-panel__header">
            <div>
              <div class="setting-guide-panel__title">日志设置先想清楚是保留还是减负</div>
              <div class="setting-guide-panel__desc">
                先判断现在是“日志不够查问题”，还是“日志太多占空间”，再决定开关和保留时间，不要直接清成
                0 天或永久。
              </div>
            </div>
            <span class="setting-guide-panel__badge">{{ logFocusLabel }}</span>
          </div>
          <div class="setting-guide-panel__grid">
            <div v-for="item in logGuideCards" :key="item.title" class="setting-guide-card">
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
            <span class="setting-panel-overview__label">保留时间</span>
            <strong>{{ model.log_save_time ? `${model.log_save_time} 天` : '永久保留' }}</strong>
          </div>
          <div class="setting-panel-overview__card">
            <span class="setting-panel-overview__label">适用范围</span>
            <strong>后台用户日志</strong>
          </div>
        </div>
        <div class="setting-followup-strip">
          <div class="setting-followup-strip__main">
            <div class="setting-followup-strip__title">改完后请触发一次真实后台操作</div>
            <div class="setting-followup-strip__desc">
              日志设置只有在真实菜单访问、保存、审核等操作后才能验证是否生效，不能只看这里提交成功。
            </div>
          </div>
          <div class="setting-followup-strip__actions">
            <el-button text type="primary" @click="goToRoute('/system/user-log')"
              >去用户日志查看</el-button
            >
            <el-button text type="primary" @click="goToRoute('/system/user')"
              >去后台用户联查</el-button
            >
            <el-button text type="primary" @click="goToRoute('/dashboard')"
              >回首页触发操作</el-button
            >
          </div>
        </div>
        <el-form-item label="日志记录开关" prop="log_switch">
          <el-col :span="8">
            <el-switch v-model="model.log_switch" :active-value="1" :inactive-value="0" />
          </el-col>
          <el-col :span="16"> 开启后，会记录后台用户日志。 </el-col>
        </el-form-item>
        <el-form-item label="日志保留时间" prop="log_save_time">
          <el-col :span="8">
            <el-input v-model="model.log_save_time" type="number">
              <template #append>天</template>
            </el-input>
          </el-col>
          <el-col :span="16"> 用户日志保留天数，0永久保留。 </el-col>
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
import { logInfo, logEdit } from '@/api/system/setting'

export default {
  name: 'SystemSettingLog',
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
    logFocusLabel() {
      if (!this.model.log_switch) {
        return '当前不记录后台日志'
      }
      return Number(this.model.log_save_time) === 0 ? '当前永久保留' : '优先核保留周期'
    },
    logGuideCards() {
      return [
        {
          title: '第一步：先看有没有日志可查',
          desc: '如果后台问题经常追不到操作人或菜单来源，先确认日志开关是不是开着。',
          action: this.model.log_switch ? '当前已开启日志记录' : '当前日志记录关闭'
        },
        {
          title: '第二步：再看保留周期',
          desc: '保留太短会丢排查线索，永久保留又会让数据持续膨胀，要按运营排查周期来定。',
          action:
            Number(this.model.log_save_time) === 0
              ? '当前永久保留'
              : `当前保留 ${this.model.log_save_time} 天`
        },
        {
          title: '第三步：最后回日志页验证',
          desc: '改完后不要只看保存成功，要回用户日志页确认新日志还在正常落库和展示。',
          action: '重点回查用户日志列表、筛选、清理。'
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
      this.$router.push({
        path,
        query: {
          from: 'system-setting-log',
          ...query
        }
      })
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
                ElMessage.info('提交后请去用户日志页查看，并从首页或后台用户页触发一次真实操作。')
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
  }
}
</style>
