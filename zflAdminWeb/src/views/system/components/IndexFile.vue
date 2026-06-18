<template>
  <el-card v-loading="loading" shadow="never" class="index-chart-card">
    <template #header>
      <div class="index-chart-card__header">
        <div>
          <div class="index-chart-card__title">文件库分布</div>
          <div class="index-chart-card__desc">
            适合快速判断素材主要压在哪类文件上，再回文件库、分组和商家收款码之类的场景核查。
          </div>
        </div>
        <el-tag type="warning" effect="plain">{{ fileFocusLabel }}</el-tag>
      </div>
    </template>

    <div class="index-chart-card__summary">
      <div class="index-chart-card__summary-item">
        <span>文件总数</span>
        <strong>{{ fileCount }}</strong>
      </div>
      <div class="index-chart-card__summary-item">
        <span>主类型</span>
        <strong>{{ topFileTypeLabel }}</strong>
      </div>
      <div class="index-chart-card__summary-item">
        <span>建议动作</span>
        <strong>回文件库复核素材</strong>
      </div>
    </div>

    <div class="index-chart-card__actions">
      <el-button text type="primary" @click="goTo('/file/file')">去文件管理</el-button>
      <el-button text type="primary" @click="goTo('/file/group')">去文件分组</el-button>
      <el-button text type="primary" @click="goTo('/merchant/Merchant')">去商家列表</el-button>
    </div>

    <div id="echartIndexFile" class="h-[500px] w-[100%]"></div>
  </el-card>
</template>

<script>
import * as echarts from 'echarts/core'
import { PieChart } from 'echarts/charts'
import { TitleComponent, LegendComponent, TooltipComponent } from 'echarts/components'
import { CanvasRenderer } from 'echarts/renderers'
echarts.use([PieChart, TitleComponent, LegendComponent, TooltipComponent, CanvasRenderer])

import { file } from '@/api/system/index'

export default {
  name: 'SystemIndexFile',
  data() {
    return {
      loading: false,
      chartMeta: {
        count: 0,
        data: []
      }
    }
  },
  computed: {
    name() {
      return this.$t('file.File statistic')
    },
    files() {
      return this.$t('file.file')
    },
    file_type() {
      return this.$t('file.file type')
    },
    fileCount() {
      return this.chartMeta.count || 0
    },
    topFileType() {
      return (this.chartMeta.data || []).reduce(
        (max, item) => (Number(item.value || 0) > Number(max.value || 0) ? item : max),
        { name: '暂无数据', value: 0 }
      )
    },
    topFileTypeLabel() {
      return `${this.topFileType.name} ${this.topFileType.value || 0}`
    },
    fileFocusLabel() {
      return this.fileCount ? `优先核 ${this.topFileType.name}` : '当前暂无文件统计'
    }
  },
  watch: {
    name() {
      this.file()
    }
  },
  created() {
    this.file()
  },
  methods: {
    file() {
      this.loading = true
      file()
        .then((res) => {
          this.chartMeta = res.data || {}
          this.echartIndexFile(this.chartMeta)
          this.loading = false
        })
        .catch(() => {
          this.loading = false
        })
    },
    echartIndexFile(data) {
      var echart = echarts.init(document.getElementById('echartIndexFile'))
      var option = {
        title: {
          subtext: this.files + '：' + (data.count || 0),
          left: 'center'
        },
        legend: {
          left: 'center',
          top: 'bottom'
        },
        tooltip: {
          trigger: 'item',
          formatter: '{a} <br/>{b} : {c} ({d}%)'
        },
        series: [
          {
            name: this.file_type,
            type: 'pie',
            radius: ['40%', '70%'],
            avoidLabelOverlap: false,
            itemStyle: {
              borderRadius: 10,
              borderColor: '#fff',
              borderWidth: 2,
              label: {
                show: true,
                formatter: '{b} : {c} ({d}%)'
              }
            },
            data: data.data || []
          }
        ]
      }
      echart.setOption(option)
    },
    goTo(path, query = {}) {
      this.$router.push({ path, query: { from: 'dashboard-file', ...query } })
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
