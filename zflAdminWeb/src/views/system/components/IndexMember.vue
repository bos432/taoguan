<template>
  <el-card v-loading="loading" shadow="never" class="index-chart-card">
    <template #header>
      <div class="index-chart-card__header">
        <div>
          <div class="index-chart-card__title">会员趋势看板</div>
          <div class="index-chart-card__desc">
            这块主要看注册、活跃走势有没有异常，再决定回会员列表、标签还是登录链路继续查。
          </div>
        </div>
        <el-tag type="primary" effect="plain">{{ memberFocusLabel }}</el-tag>
      </div>
    </template>

    <div class="index-chart-card__summary">
      <div class="index-chart-card__summary-item">
        <span>统计口径</span>
        <strong>{{ dateTypeLabel }}</strong>
      </div>
      <div class="index-chart-card__summary-item">
        <span>当前周期</span>
        <strong>{{ rangeLabel }}</strong>
      </div>
      <div class="index-chart-card__summary-item">
        <span>图例数量</span>
        <strong>{{ legendCount }}</strong>
      </div>
    </div>

    <div class="index-chart-card__actions">
      <el-button text type="primary" @click="goTo('/member/member')">去会员列表</el-button>
      <el-button text type="primary" @click="goTo('/member/statistic')">去会员统计</el-button>
      <el-button text type="primary" @click="openMemberLogin">去 H5 登录页</el-button>
    </div>

    <el-row>
      <el-col class="text-center">
        <el-select v-model="date_type" class="!w-[100px]" @change="typeChange">
          <el-option :label="$t('common.day')" value="day" />
          <el-option :label="$t('common.month')" value="month" />
        </el-select>
        <el-date-picker
          v-model="date_range"
          :type="date_ptype"
          :value-format="date_format"
          :start-placeholder="$t('common.Start date')"
          :end-placeholder="$t('common.End date')"
          @change="dateChange"
        />
      </el-col>
      <el-col>
        <div id="numberEchart" :style="{ height: height + 'px' }"></div>
      </el-col>
    </el-row>
  </el-card>
</template>

<script>
import * as echarts from 'echarts/core'
import { LineChart, BarChart } from 'echarts/charts'
import {
  TitleComponent,
  LegendComponent,
  GridComponent,
  TooltipComponent,
  ToolboxComponent
} from 'echarts/components'
import { CanvasRenderer } from 'echarts/renderers'
echarts.use([
  LineChart,
  BarChart,
  TitleComponent,
  LegendComponent,
  GridComponent,
  TooltipComponent,
  ToolboxComponent,
  CanvasRenderer
])

import { member as stat } from '@/api/system/index'

export default {
  name: 'SystemIndexMember',
  data() {
    return {
      height: 500,
      loading: false,
      date_type: 'day',
      date_range: [],
      date_ptype: 'monthrange',
      date_format: 'YYYY-MM',
      chartMeta: {
        legend: [],
        date: []
      }
    }
  },
  computed: {
    name() {
      return this.$t('member.Member statistic')
    },
    legendCount() {
      return (this.chartMeta.legend || []).length
    },
    rangeLabel() {
      const [start, end] = this.chartMeta.date || []
      return start && end ? `${start} 至 ${end}` : '未选择'
    },
    dateTypeLabel() {
      return this.date_type === 'day' ? '按天' : '按月'
    },
    memberFocusLabel() {
      return this.legendCount ? '优先看异常波动日期' : '等待会员趋势加载'
    }
  },
  watch: {
    name() {
      this.stat()
    }
  },
  created() {
    this.stat()
  },
  methods: {
    stat() {
      this.loading = true
      stat({
        type: this.date_type,
        date: this.date_range
      })
        .then((res) => {
          const number = res.data.number || {}
          this.chartMeta = number
          this.date_type = number.type
          this.date_range = number.date
          this.dateEchart(number, 'numberEchart')
          this.dateOptions()
          this.loading = false
        })
        .catch(() => {
          this.loading = false
        })
    },
    typeChange() {
      this.dateOptions()
      this.date_range = []
    },
    dateOptions() {
      const type = this.date_type
      if (type === 'day') {
        this.date_ptype = 'daterange'
        this.date_format = 'YYYY-MM-DD'
      } else if (type === 'month') {
        this.date_ptype = 'monthrange'
        this.date_format = 'YYYY-MM'
      }
    },
    dateChange() {
      this.stat()
    },
    dateEchart(data, id) {
      var echart = echarts.init(document.getElementById(id))
      var option = {
        title: {
          left: 'center'
        },
        legend: {
          top: '20px',
          data: data.legend,
          selected: data.selected
        },
        grid: {
          top: '80px',
          left: '1%',
          right: '3%',
          bottom: '3%',
          containLabel: true
        },
        xAxis: {
          type: 'category',
          boundaryGap: false,
          data: data.xAxis
        },
        yAxis: {
          type: 'value'
        },
        tooltip: {
          trigger: 'axis',
          textStyle: {
            align: 'left'
          }
        },
        toolbox: {
          feature: {
            magicType: { show: true, type: ['line', 'bar'] },
            dataView: { show: true, readOnly: true },
            saveAsImage: {
              show: true,
              name: this.name + data.date[0] + '-' + data.date[1]
            }
          }
        },
        series: data.series
      }
      echart.setOption(option)
    },
    goTo(path, query = {}) {
      this.$router.push({ path, query: { from: 'dashboard-member', ...query } })
    },
    openMemberLogin() {
      window.open(`${window.location.origin}/app/pages/my/login`, '_blank', 'noopener')
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
