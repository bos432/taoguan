<template>
  <el-card v-loading="loading" shadow="never" class="overview-card">
    <template #header>
      <div class="overview-card__header">
        <div>
          <div class="overview-card__title">后台总量速看</div>
          <div class="overview-card__desc">
            这块适合先看平台存量规模，再决定往商品、会员、商家还是内容继续钻。
          </div>
        </div>
        <el-tag type="primary" effect="plain">{{ countFocusLabel }}</el-tag>
      </div>
    </template>

    <div class="overview-summary">
      <div class="overview-summary__item">
        <span>统计项</span>
        <strong>{{ datas.length }} 个</strong>
      </div>
      <div class="overview-summary__item">
        <span>当前最高</span>
        <strong>{{ topCountLabel }}</strong>
      </div>
      <div class="overview-summary__item">
        <span>建议动作</span>
        <strong>先看高量级栏目</strong>
      </div>
    </div>

    <div class="overview-actions">
      <el-button text type="primary" @click="goTo('/goods/Goods', { limit: 1000 })">
        去商品管理
      </el-button>
      <el-button text type="primary" @click="goTo('/member/member')">去会员管理</el-button>
      <el-button text type="primary" @click="goTo('/merchant/Merchant')">去商家列表</el-button>
    </div>

    <el-row :gutter="10">
      <el-col v-for="(item, index) in datas" :key="index" :xs="24" :sm="8" :md="6" :lg="4">
        <el-card :body-style="{ padding: '10px 0px' }" class="text-center count-tile" shadow="never">
          <template #header>
            <span>{{ item.name }}</span>
          </template>
          <el-statistic :value="item.count" />
        </el-card>
      </el-col>
    </el-row>
  </el-card>
</template>

<script>
import { count } from '@/api/system/index'

export default {
  name: 'SystemIndexCount',
  data() {
    return {
      loading: false,
      datas: []
    }
  },
  computed: {
    name() {
      return this.$t('common.Count statistic')
    },
    topCountItem() {
      return this.datas.reduce(
        (max, item) => (Number(item.count || 0) > Number(max.count || 0) ? item : max),
        { name: '暂无数据', count: 0 }
      )
    },
    topCountLabel() {
      return `${this.topCountItem.name} ${this.topCountItem.count || 0}`
    },
    countFocusLabel() {
      return this.datas.length ? `优先关注 ${this.topCountItem.name}` : '等待统计加载'
    }
  },
  watch: {
    name() {
      this.count()
    }
  },
  created() {
    this.count()
  },
  methods: {
    count() {
      this.loading = true
      count()
        .then((res) => {
          this.datas = res.data.count || []
          this.loading = false
        })
        .catch(() => {
          this.loading = false
        })
    },
    goTo(path, query = {}) {
      this.$router.push({ path, query: { from: 'dashboard-count', ...query } })
    }
  }
}
</script>

<style scoped>
.overview-card__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
}

.overview-card__title {
  font-size: 15px;
  font-weight: 700;
  color: #0f172a;
}

.overview-card__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.overview-summary {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-bottom: 14px;
}

.overview-summary__item {
  padding: 12px 14px;
  border: 1px solid #e6ecf5;
  border-radius: 14px;
  background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
}

.overview-summary__item span {
  display: block;
  font-size: 11px;
  color: #7c8aa5;
}

.overview-summary__item strong {
  display: block;
  margin-top: 6px;
  font-size: 14px;
  color: #0f172a;
}

.overview-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 14px;
}

.count-tile {
  border-color: #eef3f8;
}

@media (max-width: 900px) {
  .overview-summary {
    grid-template-columns: 1fr;
  }
}
</style>
