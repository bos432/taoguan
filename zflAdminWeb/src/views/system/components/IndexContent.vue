<template>
  <el-card v-loading="loading" shadow="never" class="index-chart-card">
    <template #header>
      <div class="index-chart-card__header">
        <div>
          <div class="index-chart-card__title">内容发布走势</div>
          <div class="index-chart-card__desc">
            用来判断最近内容更新有没有停滞，以及是否需要回栏目和分类页继续补内容。
          </div>
        </div>
        <el-tag type="success" effect="plain">{{ contentFocusLabel }}</el-tag>
      </div>
    </template>

    <div class="index-chart-card__summary">
      <div class="index-chart-card__summary-item">
        <span>分类总数</span>
        <strong>{{ chartMeta.category }}</strong>
      </div>
      <div class="index-chart-card__summary-item">
        <span>内容总数</span>
        <strong>{{ chartMeta.content }}</strong>
      </div>
      <div class="index-chart-card__summary-item">
        <span>峰值日期</span>
        <strong>{{ peakLabel }}</strong>
      </div>
    </div>

    <div class="index-chart-card__actions">
      <el-button text type="primary" @click="goTo('/content/content')">去内容管理</el-button>
      <el-button text type="primary" @click="goTo('/content/category')">去内容分类</el-button>
    </div>

    <div id="echartIndexContent" class="h-[500px] w-[100%]"></div>
  </el-card>
</template>

<script>
import * as echarts from 'echarts/core'
import { BarChart } from 'echarts/charts'
import { TitleComponent, TooltipComponent, GridComponent } from 'echarts/components'
import { CanvasRenderer } from 'echarts/renderers'
echarts.use([BarChart, TitleComponent, TooltipComponent, GridComponent, CanvasRenderer])

import { content } from '@/api/system/index'

export default {
  name: 'SystemIndexContent',
  data() {
    return {
      loading: false,
      chartMeta: {
        category: 0,
        content: 0,
        x_data: [],
        s_data: []
      }
    }
  },
  computed: {
    name() {
      return this.$t('content.Content statistic')
    },
    contents() {
      return this.$t('content.content')
    },
    category() {
      return this.$t('content.category')
    },
    peakIndex() {
      const values = this.chartMeta.s_data || []
      if (!values.length) {
        return -1
      }
      return values.reduce((best, value, index, list) => (value > list[best] ? index : best), 0)
    },
    peakLabel() {
      if (this.peakIndex < 0) {
        return '暂无数据'
      }
      return `${this.chartMeta.x_data[this.peakIndex]} / ${this.chartMeta.s_data[this.peakIndex]}`
    },
    contentFocusLabel() {
      return Number(this.chartMeta.content || 0) > 0 ? '优先看峰值和空档期' : '当前暂无内容统计'
    }
  },
  watch: {
    name() {
      this.content()
    }
  },
  created() {
    this.content()
  },
  methods: {
    content() {
      this.loading = true
      content()
        .then((res) => {
          this.chartMeta = res.data || {}
          this.echartIndexContent(this.chartMeta)
          this.loading = false
        })
        .catch(() => {
          this.loading = false
        })
    },
    echartIndexContent(data) {
      var echart = echarts.init(document.getElementById('echartIndexContent'))
      var option = {
        title: {
          subtext:
            this.category + '：' + (data.category || 0) + '，' + this.contents + '：' + (data.content || 0),
          left: 'center'
        },
        tooltip: {
          trigger: 'axis'
        },
        grid: {
          left: '3%',
          right: '3%',
          bottom: '3%',
          containLabel: true
        },
        xAxis: {
          type: 'category',
          data: data.x_data || []
        },
        yAxis: {
          type: 'value'
        },
        series: [
          {
            data: data.s_data || [],
            type: 'bar',
            label: {
              show: true,
              position: 'top'
            }
          }
        ]
      }
      echart.setOption(option)
    },
    goTo(path, query = {}) {
      this.$router.push({ path, query: { from: 'dashboard-content', ...query } })
    }
  }
}
</script>

<style scoped>
.index-chart-card__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
}

.index-chart-card__title {
  font-size: 15px;
  font-weight: 700;
  color: #0f172a;
}

.index-chart-card__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.index-chart-card__summary {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-bottom: 14px;
}

.index-chart-card__summary-item {
  padding: 12px 14px;
  border: 1px solid #e6ecf5;
  border-radius: 14px;
  background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
}

.index-chart-card__summary-item span {
  display: block;
  font-size: 11px;
  color: #7c8aa5;
}

.index-chart-card__summary-item strong {
  display: block;
  margin-top: 6px;
  font-size: 14px;
  color: #0f172a;
}

.index-chart-card__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 12px;
}

@media (max-width: 900px) {
  .index-chart-card__summary {
    grid-template-columns: 1fr;
  }
}
</style>
