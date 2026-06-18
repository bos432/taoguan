<template>
  <el-row>
    <el-col :span="16">
      <el-form ref="ref" :model="model" :rules="rules" label-width="120px">
        <div class="setting-guide-panel">
          <div class="setting-guide-panel__header">
            <div>
              <div class="setting-guide-panel__title">缓存清理先确认是不是该清，而不是顺手就点</div>
              <div class="setting-guide-panel__desc">
                这页核心不是看缓存类型，而是判断“配置改完需不需要清缓存”。先确认问题是缓存残留，再执行清理，避免把它当通用刷新按钮。
              </div>
            </div>
            <span class="setting-guide-panel__badge">{{ cacheFocusLabel }}</span>
          </div>
          <div class="setting-guide-panel__grid">
            <div v-for="item in cacheGuideCards" :key="item.title" class="setting-guide-card">
              <div class="setting-guide-card__title">{{ item.title }}</div>
              <div class="setting-guide-card__desc">{{ item.desc }}</div>
              <div class="setting-guide-card__action">{{ item.action }}</div>
            </div>
          </div>
        </div>
        <div class="setting-panel-overview">
          <div class="setting-panel-overview__card">
            <span class="setting-panel-overview__label">缓存类型</span>
            <strong>{{ model.cache_type || '未识别' }}</strong>
          </div>
          <div class="setting-panel-overview__card">
            <span class="setting-panel-overview__label">清理影响</span>
            <strong>不影响登录状态</strong>
          </div>
          <div class="setting-panel-overview__card">
            <span class="setting-panel-overview__label">推荐场景</span>
            <strong>配置更新后执行</strong>
          </div>
        </div>
        <div class="setting-followup-strip">
          <div class="setting-followup-strip__copy">
            <div class="setting-followup-strip__title">清完缓存马上回问题页复查</div>
            <div class="setting-followup-strip__desc">
              这一步不是日常刷新按钮。清理后要回首页、商品高频页或系统设置页确认旧回显有没有消失，才能判断是否真是缓存问题。
            </div>
          </div>
          <div class="setting-followup-strip__actions">
            <el-button plain @click="goToRoute('/dashboard')">去首页复查</el-button>
            <el-button plain @click="goToRoute('/system/setting')">回系统设置复查</el-button>
            <el-button type="primary" plain @click="goToRoute('/goods/Goods', { limit: 1000 })">
              去商品高频页复查
            </el-button>
          </div>
        </div>
        <el-form-item label="缓存类型" prop="cache_type">
          <el-col :span="8">
            <el-text>{{ model.cache_type }}</el-text>
          </el-col>
        </el-form-item>
        <el-form-item> <el-text>用户和会员登录状态不会清除。</el-text></el-form-item>
        <el-form-item>
          <el-button :loading="loading" @click="refresh()">刷新</el-button>
          <el-button :loading="loading" type="primary" @click="clear()">清除</el-button>
        </el-form-item>
      </el-form>
    </el-col>
  </el-row>
</template>

<script>
import screenHeight from '@/utils/screen-height'
import { cacheInfo, cacheClear } from '@/api/system/setting'

export default {
  name: 'SystemSettingCache',
  data() {
    return {
      name: '缓存设置',
      height: 680,
      loading: false,
      model: {
        cache_type: ''
      },
      rules: {}
    }
  },
  computed: {
    cacheFocusLabel() {
      return this.model.cache_type ? `当前使用 ${this.model.cache_type}` : '先识别缓存类型'
    },
    cacheGuideCards() {
      return [
        {
          title: '第一步：先确认是不是缓存残留',
          desc: '不是所有页面异常都靠清缓存解决，先确认刚改过配置、路由或系统参数没有即时生效。',
          action: '适合处理配置修改后未刷新、旧值仍回显。'
        },
        {
          title: '第二步：再看当前缓存类型',
          desc: '不同缓存类型会影响排查思路，但这里的清理动作本身不应该随便频繁执行。',
          action: this.model.cache_type || '当前还没识别缓存类型'
        },
        {
          title: '第三步：清完后回问题页复查',
          desc: '执行清理后要回实际异常页面复测，而不是只看到提示成功就结束。',
          action: '重点回查配置页、前端首页、接口回显。'
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
      this.$router.push({ path, query: { from: 'system-setting-caches', ...query } })
    },
    // 信息
    info() {
      cacheInfo().then((res) => {
        this.model = res.data
      })
    },
    // 刷新
    refresh() {
      this.loading = true
      cacheInfo()
        .then((res) => {
          this.model = res.data
          this.loading = false
          ElMessage.success(res.msg)
        })
        .catch(() => {
          this.loading = false
        })
    },
    // 清除
    clear() {
      this.$refs['ref'].validate((valid) => {
        if (valid) {
          this.loading = true
          cacheClear()
            .then((res) => {
              this.loading = false
              ElMessage.success(res.msg)
              this.$nextTick(() => {
                ElMessage.info('缓存清理后请回首页、系统设置页和商品高频页复查真实回显。')
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
