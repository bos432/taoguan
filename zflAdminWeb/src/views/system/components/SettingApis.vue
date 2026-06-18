<template>
  <el-row>
    <el-col :span="16">
      <el-form ref="ref" :model="model" :rules="rules" label-width="120px">
        <div class="setting-guide-panel">
          <div class="setting-guide-panel__header">
            <div>
              <div class="setting-guide-panel__title">接口限流先看是不是异常流量问题</div>
              <div class="setting-guide-panel__desc">
                这页不是越严格越好，先判断是否真有高频异常请求，再去调次数和时间窗口，避免误伤正常操作。
              </div>
            </div>
            <span class="setting-guide-panel__badge">{{ apiFocusLabel }}</span>
          </div>
          <div class="setting-guide-panel__grid">
            <div v-for="item in apiGuideCards" :key="item.title" class="setting-guide-card">
              <div class="setting-guide-card__title">{{ item.title }}</div>
              <div class="setting-guide-card__desc">{{ item.desc }}</div>
              <div class="setting-guide-card__action">{{ item.action }}</div>
            </div>
          </div>
        </div>
        <div class="setting-panel-overview">
          <div class="setting-panel-overview__card">
            <span class="setting-panel-overview__label">后台接口速率</span>
            <strong>{{ model.api_rate_num }}/{{ model.api_rate_time }}</strong>
          </div>
          <div class="setting-panel-overview__card">
            <span class="setting-panel-overview__label">限流状态</span>
            <strong>{{ Number(model.api_rate_num) === 0 ? '不限流' : '已限流' }}</strong>
          </div>
          <div class="setting-panel-overview__card">
            <span class="setting-panel-overview__label">运维建议</span>
            <strong>异常高频请求时优先调整</strong>
          </div>
        </div>
        <div class="setting-followup-strip">
          <div class="setting-followup-strip__copy">
            <div class="setting-followup-strip__title">改完别停在当前页</div>
            <div class="setting-followup-strip__desc">
              限流值保存后，要立刻回真实高频页面、接口说明和用户日志做复核，避免把正常列表、登录或批量操作一起误拦。
            </div>
          </div>
          <div class="setting-followup-strip__actions">
            <el-button plain @click="goToRoute('/system/user-log')">去用户日志核对</el-button>
            <el-button plain @click="goToRoute('/system/apidoc')">去接口文档复核</el-button>
            <el-button type="primary" plain @click="goToRoute('/goods/Goods', { limit: 1000 })">
              去高频商品页验证
            </el-button>
          </div>
        </div>
        <el-form-item label="接口速率">
          <el-col :span="6">
            <el-input v-model="model.api_rate_num" type="number" placeholder="次数">
              <template #append>次 /</template>
            </el-input>
          </el-col>
          <el-col :span="6">
            <el-input v-model="model.api_rate_time" type="number" placeholder="时间">
              <template #append>秒</template>
            </el-input>
          </el-col>
        </el-form-item>
        <el-form-item> 次数/时间；3/1：3次1秒；次数为 0 则不限制。 </el-form-item>
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
import { apiInfo, apiEdit } from '@/api/system/setting'

export default {
  name: 'SystemSettingApi',
  data() {
    return {
      name: '接口设置',
      height: 680,
      loading: false,
      model: {
        api_rate_num: 3,
        api_rate_time: 1
      },
      rules: {
        api_rate_num: [{ required: true, message: '请输入次数', trigger: 'blur' }],
        api_rate_time: [{ required: true, message: '请输入时间', trigger: 'blur' }]
      }
    }
  },
  computed: {
    apiFocusLabel() {
      return Number(this.model.api_rate_num) === 0 ? '当前未限流' : '优先核高频接口影响'
    },
    apiGuideCards() {
      return [
        {
          title: '第一步：先确认是不是要拦高频请求',
          desc: '接口限流主要是为了处理异常刷接口，不是日常配置越大越稳。',
          action:
            Number(this.model.api_rate_num) === 0
              ? '当前不限流'
              : `当前限流：${this.model.api_rate_num} 次 / ${this.model.api_rate_time} 秒`
        },
        {
          title: '第二步：再调次数和时间窗口',
          desc: '次数决定容忍度，时间窗口决定压制强度，两者要一起看，不要只改一个值。',
          action: '示例：3/1 代表 1 秒内最多 3 次'
        },
        {
          title: '第三步：最后回日志和接口文档复核',
          desc: '调整后要看真实接口有没有被误拦，尤其是高频列表页、登录和批量操作。',
          action: '重点回查用户日志、接口文档、慢页高频请求。'
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
      this.$router.push({ path, query: { from: 'system-setting-api', ...query } })
    },
    // 信息
    info() {
      apiInfo().then((res) => {
        this.model = res.data
      })
    },
    // 刷新
    refresh() {
      this.loading = true
      apiInfo()
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
          apiEdit(this.model)
            .then((res) => {
              this.loading = false
              ElMessage.success(res.msg)
              this.$nextTick(() => {
                ElMessage.info('提交后请直接去用户日志、接口文档和商品高频页复核是否有误拦。')
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
