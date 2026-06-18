<template>
  <el-row>
    <el-col :span="16">
      <el-form ref="ref" :model="model" :rules="rules" label-width="120px">
        <div class="setting-guide-panel">
          <div class="setting-guide-panel__header">
            <div>
              <div class="setting-guide-panel__title">会员接口设置先看是不是风控或放开问题</div>
              <div class="setting-guide-panel__desc">
                这页同时影响会员接口校验和频率限制。先确认现在的问题是“接口太松”、还是“调用被拦太多”，再去改开关和限流。
              </div>
            </div>
            <span class="setting-guide-panel__badge">{{ memberApiFocusLabel }}</span>
          </div>
          <div class="setting-guide-panel__grid">
            <div v-for="item in memberApiGuideCards" :key="item.title" class="setting-guide-card">
              <div class="setting-guide-card__title">{{ item.title }}</div>
              <div class="setting-guide-card__desc">{{ item.desc }}</div>
              <div class="setting-guide-card__action">{{ item.action }}</div>
            </div>
          </div>
        </div>
        <div class="setting-panel-overview">
          <div class="setting-panel-overview__card">
            <span class="setting-panel-overview__label">接口校验</span>
            <strong>{{ model.is_member_api ? '开启' : '关闭' }}</strong>
          </div>
          <div class="setting-panel-overview__card">
            <span class="setting-panel-overview__label">速率限制</span>
            <strong>{{ model.api_rate_num }}/{{ model.api_rate_time }}</strong>
          </div>
          <div class="setting-panel-overview__card">
            <span class="setting-panel-overview__label">维护建议</span>
            <strong>变更后关注会员端调用</strong>
          </div>
        </div>
        <div class="setting-followup-strip">
          <div class="setting-followup-strip__copy">
            <div class="setting-followup-strip__title">改完请直接回真实会员入口验证</div>
            <div class="setting-followup-strip__desc">
              会员接口开关和限流会直接影响
              H5、小程序的登录、列表、购物车和订单链路，保存后别停在设置页。
            </div>
          </div>
          <div class="setting-followup-strip__actions">
            <el-button plain @click="openMemberLogin">去 H5 登录页验证</el-button>
            <el-button plain @click="goToRoute('/member/log')">去会员日志核对</el-button>
            <el-button type="primary" plain @click="goToRoute('/member/statistic')">
              去会员统计复核
            </el-button>
          </div>
        </div>
        <el-form-item label="接口速率">
          <el-col :span="5">
            <el-input v-model="model.api_rate_num" type="number" placeholder="次数">
              <template #append>次 /</template>
            </el-input>
          </el-col>
          <el-col :span="5">
            <el-input v-model="model.api_rate_time" type="number" placeholder="时间">
              <template #append>秒</template>
            </el-input>
          </el-col>
          <el-col :span="14">eg：3/1：3次1秒；次数为 0 则不限制</el-col>
        </el-form-item>
        <el-form-item label="会员接口" prop="is_member_api">
          <el-col :span="4">
            <el-switch v-model="model.is_member_api" :active-value="1" :inactive-value="0" />
          </el-col>
          <el-col :span="20"> 关闭后，不校验接口和权限，所有接口（免登除外）均需登录访问 </el-col>
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
import { apiInfo, apiEdit } from '@/api/member/setting'

export default {
  name: 'MemberSettingApi',
  data() {
    return {
      name: '接口设置',
      height: 680,
      loading: false,
      model: {
        is_member_api: 0,
        api_rate_num: 3,
        api_rate_time: 1
      },
      rules: {}
    }
  },
  computed: {
    memberApiFocusLabel() {
      if (!this.model.is_member_api) {
        return '当前未启会员接口校验'
      }
      return Number(this.model.api_rate_num) === 0 ? '当前不限流' : '优先核高频调用影响'
    },
    memberApiGuideCards() {
      return [
        {
          title: '第一步：先看接口校验是不是开着',
          desc: '如果会员接口校验关闭，很多接口会变成只看登录不看权限，先确认这是不是你的预期。',
          action: this.model.is_member_api ? '当前已开启会员接口校验' : '当前关闭会员接口校验'
        },
        {
          title: '第二步：再看限流强度',
          desc: '次数和秒数一起决定拦截强度，改前先确认是不是登录、列表或轮询接口被挡住。',
          action:
            Number(this.model.api_rate_num) === 0
              ? '当前不限流'
              : `当前限流：${this.model.api_rate_num} 次 / ${this.model.api_rate_time} 秒`
        },
        {
          title: '第三步：最后回会员端实测',
          desc: '提交后要去 H5、小程序真实点一遍登录、列表、下单等高频路径，确认没有误拦截。',
          action: '重点回查登录、商品列表、购物车、订单。'
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
      this.$router.push({ path, query: { from: 'member-setting-api', ...query } })
    },
    openMemberLogin() {
      window.open(`${window.location.origin}/app/pages/my/login`, '_blank', 'noopener')
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
                ElMessage.info('提交后请回 H5 登录页、会员日志和会员统计页复核真实调用表现。')
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
