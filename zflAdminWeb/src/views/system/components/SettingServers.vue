<template>
  <el-scrollbar native :max-height="height - 30">
    <div class="setting-panel">
      <div class="section-title-row">
        <div>
          <h3>服务器信息</h3>
          <p>保留原有字段，直接核对环境、入口、缓存和资源状态。</p>
        </div>
        <div class="section-title-row__actions">
          <div class="section-title-row__meta">
            {{ model.server_protocol || '--' }} / {{ cacheTypeLabel }}
          </div>
          <el-button :loading="loading" @click="refresh()">刷新</el-button>
        </div>
      </div>
      <div class="summary-bar">
        <div class="summary-bar__item">
          <span class="summary-bar__label">运行环境</span>
          <strong class="summary-bar__value">{{ model.system_info || '待检测' }}</strong>
        </div>
        <div class="summary-bar__item">
          <span class="summary-bar__label">服务入口</span>
          <strong class="summary-bar__value">{{ model.domain || model.ip || '待检测' }}</strong>
        </div>
        <div class="summary-bar__item">
          <span class="summary-bar__label">缓存状态</span>
          <strong class="summary-bar__value">{{ cacheTypeLabel }}</strong>
        </div>
        <div class="summary-bar__item summary-bar__item--wide">
          <span class="summary-bar__label">资源摘要</span>
          <strong class="summary-bar__value summary-bar__value--text"
            >{{ memorySummary }} / {{ connectionSummary }}</strong
          >
        </div>
      </div>
      <div class="plain-guide-panel">
        <div class="plain-guide-panel__header">
          <div>
            <div class="plain-guide-panel__title">服务器信息建议先这样看</div>
            <div class="plain-guide-panel__desc">
              这页最适合拿来判断“慢页是不是环境问题、缓存是不是异常、当前入口是不是跑错环境”，不是用来逐项抄参数。
            </div>
          </div>
          <span class="plain-guide-panel__badge">{{ serverFocusLabel }}</span>
        </div>
        <div class="plain-guide-panel__grid">
          <div v-for="item in serverGuideCards" :key="item.title" class="plain-guide-card">
            <div class="plain-guide-card__title">{{ item.title }}</div>
            <div class="plain-guide-card__desc">{{ item.desc }}</div>
            <div class="plain-guide-card__action">{{ item.action }}</div>
          </div>
        </div>
      </div>
      <div class="followup-strip">
        <div class="followup-strip__main">
          <div class="followup-strip__title">看完服务器信息别停在信息页</div>
          <div class="followup-strip__desc">
            这里的价值不是把参数抄走，而是快速判断当前后台慢页、缓存残留和环境异常有没有线索。看完建议立刻回真实页面复核。
          </div>
        </div>
        <div class="followup-strip__actions">
          <el-button text type="primary" @click="goToRoute('/dashboard')">去首页复核</el-button>
          <el-button text type="primary" @click="goToRoute('/system/setting')"
            >去系统设置复核</el-button
          >
          <el-button text type="primary" @click="goToRoute('/goods/Goods', { limit: 1000 })"
            >去商品高频页复核</el-button
          >
        </div>
      </div>

      <el-row :gutter="16">
        <el-col :span="12">
          <el-form :model="model" label-width="150px" class="form-card">
            <el-form-item label="ThinkPHP">
              <el-input v-model="model.thinkphp" />
            </el-form-item>
            <el-form-item label="OS">
              <el-input v-model="model.system_info" />
            </el-form-item>
            <el-form-item label="Web">
              <el-input v-model="model.server_software" />
            </el-form-item>
            <el-form-item label="MySQL">
              <el-input v-model="model.mysql" />
            </el-form-item>
            <el-form-item label="PHP">
              <el-input v-model="model.php_version" />
            </el-form-item>
            <el-form-item label="Protocol">
              <el-input v-model="model.server_protocol" />
            </el-form-item>
            <el-form-item label="IP">
              <el-input v-model="model.ip" />
            </el-form-item>
            <el-form-item label="Domain">
              <el-input v-model="model.domain" />
            </el-form-item>
            <el-form-item label="Port">
              <el-input v-model="model.port" />
            </el-form-item>
            <el-form-item label="php_sapi_name">
              <el-input v-model="model.php_sapi_name" />
            </el-form-item>
            <el-form-item label="max_execution_time">
              <el-input v-model="model.max_execution_time" />
            </el-form-item>
            <el-form-item label="upload_max_filesize">
              <el-input v-model="model.upload_max_filesize" />
            </el-form-item>
            <el-form-item label="post_max_size" class="ya-margin-bottom">
              <el-input v-model="model.post_max_size" />
            </el-form-item>
          </el-form>
        </el-col>
        <el-col :span="12">
          <el-form :model="model" label-width="150px" class="form-card">
            <el-form-item label="缓存类型" prop="type">
              <el-input v-model="model.cache_type" />
            </el-form-item>
            <div v-if="model.cache_type === 'redis'">
              <el-form-item label="Redis">
                <el-input v-model="model.redis_version" />
              </el-form-item>
              <el-form-item label="运行时长">
                <el-input v-model="model.uptime_in_days" />
              </el-form-item>
              <el-form-item label="已用内存">
                <el-input v-model="model.used_memory_human" />
              </el-form-item>
              <el-form-item label="峰值内存">
                <el-input v-model="model.used_memory_peak_human" />
              </el-form-item>
              <el-form-item label="Lua内存">
                <el-input v-model="model.used_memory_lua_human" />
              </el-form-item>
              <el-form-item label="客户端数">
                <el-input v-model="model.connected_clients" />
              </el-form-item>
              <el-form-item label="总连接数">
                <el-input v-model="model.total_connections_received" />
              </el-form-item>
              <el-form-item label="总命令数">
                <el-input v-model="model.total_commands_processed" />
              </el-form-item>
              <el-form-item label="内存碎片比率">
                <el-input v-model="model.mem_fragmentation_ratio" />
              </el-form-item>
              <el-form-item label="DB0" class="ya-margin-bottom">
                <el-input v-model="model.db0" />
              </el-form-item>
              <template v-for="n in 15">
                <el-form-item
                  v-if="model['db' + n]"
                  :key="n"
                  :label="'DB' + n"
                  class="ya-margin-bottom"
                >
                  <el-input v-model="model['db' + n]" />
                </el-form-item>
              </template>
            </div>
            <div v-else-if="model.cache_type === 'memcache'">
              <el-form-item label="memcache">
                <el-input v-model="model.version" />
              </el-form-item>
              <el-form-item label="运行秒数">
                <el-input v-model="model.uptime" />
              </el-form-item>
              <el-form-item label="读取字节总数">
                <el-input v-model="model.bytes_read" />
              </el-form-item>
              <el-form-item label="写入字节总数">
                <el-input v-model="model.bytes_written" />
              </el-form-item>
              <el-form-item label="分配的内存数">
                <el-input v-model="model.limit_maxbytes" />
              </el-form-item>
              <el-form-item label="当前打开链接数">
                <el-input v-model="model.curr_connections" />
              </el-form-item>
              <el-form-item label="曾打开连接总数">
                <el-input v-model="model.total_connections" />
              </el-form-item>
              <el-form-item label="执行get命令总数">
                <el-input v-model="model.cmd_get" />
              </el-form-item>
              <el-form-item label="执行set命令总数">
                <el-input v-model="model.cmd_set" />
              </el-form-item>
              <el-form-item label="flush_all命令总数">
                <el-input v-model="model.cmd_flush" />
              </el-form-item>
              <el-form-item label="当前服务器时间" class="ya-margin-bottom">
                <el-input v-model="model.time" />
              </el-form-item>
            </div>
            <div v-else-if="model.cache_type === 'wincache'">
              <el-form-item label="缓存信息" prop="wincache_info">
                <pre>{{ model.wincache_info }}</pre>
              </el-form-item>
            </div>
          </el-form>
        </el-col>
      </el-row>
    </div>
  </el-scrollbar>
</template>

<script>
import screenHeight from '@/utils/screen-height'
import { serverInfo } from '@/api/system/setting'

export default {
  name: 'SystemSettingServer',
  data() {
    return {
      name: '服务器信息',
      height: 680,
      loading: false,
      model: {}
    }
  },
  computed: {
    serverFocusLabel() {
      if (this.model.cache_type === 'redis') {
        return '先看缓存与连接'
      }
      if (this.model.domain || this.model.ip) {
        return '先确认入口环境'
      }
      return '先看基础环境'
    },
    cacheTypeLabel() {
      const map = {
        redis: 'Redis',
        memcache: 'Memcache',
        wincache: 'WinCache'
      }
      return map[this.model.cache_type] || this.model.cache_type || '未配置'
    },
    cacheSummary() {
      if (this.model.cache_type === 'redis') {
        return `运行 ${this.model.uptime_in_days || '--'} 天，命令 ${
          this.model.total_commands_processed || '--'
        }`
      }
      if (this.model.cache_type === 'memcache') {
        return `已读 ${this.model.bytes_read || '--'}，已写 ${this.model.bytes_written || '--'}`
      }
      if (this.model.cache_type === 'wincache') {
        return '当前为 Windows 缓存模式'
      }
      return '暂未识别缓存状态'
    },
    memorySummary() {
      if (this.model.cache_type === 'redis') {
        return `${this.model.used_memory_human || '--'} / 峰值 ${
          this.model.used_memory_peak_human || '--'
        }`
      }
      if (this.model.cache_type === 'memcache') {
        return `已分配 ${this.model.limit_maxbytes || '--'}`
      }
      return `上传 ${this.model.upload_max_filesize || '--'} / 提交 ${
        this.model.post_max_size || '--'
      }`
    },
    connectionSummary() {
      if (this.model.cache_type === 'redis') {
        return `客户端 ${this.model.connected_clients || '--'}，连接 ${
          this.model.total_connections_received || '--'
        }`
      }
      if (this.model.cache_type === 'memcache') {
        return `当前 ${this.model.curr_connections || '--'}，累计 ${
          this.model.total_connections || '--'
        }`
      }
      return `SAPI ${this.model.php_sapi_name || '--'}`
    },
    serverGuideCards() {
      return [
        {
          title: '第一步：先看是不是跑错环境',
          desc: '优先看服务入口、协议、域名和 IP，确认当前后台是不是你以为的那套环境。',
          action: `${this.model.domain || this.model.ip || '当前入口待检测'} / ${
            this.model.server_protocol || '--'
          }`
        },
        {
          title: '第二步：再看缓存和资源',
          desc: '慢页、老数据、刷新不一致，通常先看缓存类型、内存摘要和连接状态，不要直接怀疑页面本身。',
          action: `${this.cacheTypeLabel} / ${this.memorySummary}`
        },
        {
          title: '第三步：最后回真实页面复核',
          desc: '信息页只能给线索，真正要回首页、系统设置页和商品高频页看有没有卡顿、异常或缓存残留。',
          action: '看完这里就回业务页验证。'
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
          from: 'system-setting-server',
          ...query
        }
      })
    },
    // 信息
    info() {
      serverInfo().then((res) => {
        this.model = res.data
      })
    },
    // 刷新
    refresh() {
      this.loading = true
      serverInfo({ force: 1 })
        .then((res) => {
          this.model = res.data
          this.loading = false
          ElMessage.success(res.msg)
          this.$nextTick(() => {
            ElMessage.info('建议继续去首页、系统设置页或商品高频页复核真实表现。')
          })
        })
        .catch(() => {
          this.loading = false
        })
    }
  }
}
</script>

<style scoped>
.setting-panel {
  padding: 4px 2px 12px;
}

.form-card {
  border: 1px solid #ebeef5;
  border-radius: 14px;
  background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
  box-shadow: 0 10px 24px rgba(31, 35, 41, 0.06);
}

.section-title-row {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 16px;
}

.section-title-row h3 {
  margin: 0 0 4px;
  color: #1f2329;
  font-size: 18px;
}

.section-title-row p {
  margin: 0;
  color: #7a8599;
  font-size: 13px;
}

.section-title-row__actions {
  display: flex;
  align-items: center;
  gap: 12px;
}

.section-title-row__meta {
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
  white-space: nowrap;
}

.summary-bar {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 12px;
  margin-bottom: 18px;
}

.summary-bar__item {
  min-width: 0;
  padding: 14px 16px;
  border: 1px solid #ebeef5;
  border-radius: 12px;
  background: #f8fafc;
}

.summary-bar__item--wide {
  grid-column: span 2;
}

.summary-bar__label {
  display: block;
  margin-bottom: 6px;
  color: #7a8599;
  font-size: 12px;
}

.summary-bar__value {
  display: block;
  color: #1f2329;
  font-size: 15px;
  line-height: 1.4;
}

.summary-bar__value--text {
  font-size: 14px;
  color: #475569;
}

.followup-strip {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 18px;
  padding: 14px 16px;
  border: 1px solid #dbe7f5;
  border-radius: 14px;
  background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
}

.plain-guide-panel {
  margin-bottom: 18px;
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
  min-width: 160px;
  padding: 10px 12px;
  border-radius: 12px;
  background: rgba(37, 99, 235, 0.08);
  color: #1d4ed8;
  font-size: 12px;
  font-weight: 700;
  line-height: 1.6;
}

.plain-guide-panel__grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-top: 14px;
}

.plain-guide-card {
  padding: 14px 16px;
  border: 1px solid #e7edf5;
  border-radius: 14px;
  background: #fff;
}

.plain-guide-card__title {
  font-weight: 700;
  color: #0f172a;
}

.plain-guide-card__desc {
  margin-top: 8px;
  color: #64748b;
  line-height: 1.7;
}

.plain-guide-card__action {
  margin-top: 10px;
  color: #1d4ed8;
  font-size: 12px;
  line-height: 1.6;
}

.followup-strip__title {
  font-size: 14px;
  font-weight: 700;
  color: #1f2329;
}

.followup-strip__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.followup-strip__actions {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 2px;
}

.form-card {
  padding: 18px 18px 2px;
}

@media (max-width: 992px) {
  :deep(.el-col) {
    max-width: 100%;
    flex: 0 0 100%;
  }

  .section-title-row {
    flex-direction: column;
    align-items: flex-start;
  }

  .followup-strip {
    flex-direction: column;
  }

  .plain-guide-panel__header {
    flex-direction: column;
  }

  .plain-guide-panel__badge {
    min-width: auto;
    width: 100%;
  }

  .plain-guide-panel__grid {
    grid-template-columns: 1fr;
  }

  .section-title-row__meta {
    white-space: normal;
  }

  .summary-bar__item--wide {
    grid-column: span 1;
  }
}
</style>
