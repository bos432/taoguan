<template>
  <el-drawer v-model="visible" title="订单流转" size="min(720px, 92vw)" destroy-on-close>
    <div v-loading="loading" class="order-timeline-drawer">
      <el-alert v-if="error" :title="error" type="error" :closable="false" show-icon />
      <template v-else-if="timeline.order">
        <div class="order-timeline-summary">
          <div>
            <div class="order-timeline-summary__label">订单号</div>
            <div class="order-timeline-summary__value">{{ timeline.order.order_no }}</div>
          </div>
          <div>
            <div class="order-timeline-summary__label">当前状态</div>
            <div class="order-timeline-summary__value">{{ timeline.order.status_title }}</div>
          </div>
          <div>
            <div class="order-timeline-summary__label">实付 / 数量</div>
            <div class="order-timeline-summary__value">
              ￥{{ timeline.order.pay_price || 0 }} / {{ timeline.order.total_num || 0 }}
            </div>
          </div>
          <el-tag :type="coverageType" effect="plain">{{ coverageLabel }}</el-tag>
        </div>

        <el-tabs v-model="activeTab" class="order-timeline-tabs">
          <el-tab-pane :label="`业务事件 (${timeline.events.length})`" name="events">
            <el-empty
              v-if="!timeline.events.length"
              description="该历史订单尚无新版业务事件，可切换到旧操作日志查看"
            />
            <el-timeline v-else>
              <el-timeline-item
                v-for="event in timeline.events"
                :key="event.id"
                :timestamp="event.occurred_at"
                placement="top"
                type="primary"
              >
                <div class="order-timeline-item">
                  <div class="order-timeline-item__header">
                    <strong>{{ eventLabel(event.event_type) }}</strong>
                    <span v-if="event.before_status_title || event.after_status_title">
                      {{ event.before_status_title || '未知' }} →
                      {{ event.after_status_title || '未知' }}
                    </span>
                  </div>
                  <div class="order-timeline-item__meta">
                    <span>金额：￥{{ event.amount || 0 }}</span>
                    <span>数量：{{ event.quantity || 0 }}</span>
                    <span>来源：{{ event.source || '-' }}</span>
                    <span>
                      操作人：{{ event.operator_type || '-' }} #{{ event.operator_id || 0 }}
                    </span>
                  </div>
                  <div v-if="event.reason" class="order-timeline-item__reason">
                    原因：{{ event.reason }}
                  </div>
                  <div class="order-timeline-item__request">请求编号：{{ event.request_id }}</div>
                </div>
              </el-timeline-item>
            </el-timeline>
          </el-tab-pane>
          <el-tab-pane :label="`旧操作日志 (${timeline.legacy_logs.length})`" name="legacy">
            <el-empty v-if="!timeline.legacy_logs.length" description="暂无旧操作日志" />
            <el-timeline v-else>
              <el-timeline-item
                v-for="log in timeline.legacy_logs"
                :key="log.id"
                :timestamp="log.create_time"
                placement="top"
                type="info"
              >
                <div class="order-timeline-item">
                  <div class="order-timeline-item__header">
                    <strong>{{ log.title || '订单操作' }}</strong>
                    <span>角色 {{ log.role_type || '-' }} / 操作人 {{ log.create_uid || 0 }}</span>
                  </div>
                  <div v-if="log.content" class="order-timeline-item__reason">
                    {{ log.content }}
                  </div>
                </div>
              </el-timeline-item>
            </el-timeline>
          </el-tab-pane>
        </el-tabs>
      </template>
    </div>
  </el-drawer>
</template>

<script>
import { timeline } from '@/api/order/list'

const emptyTimeline = () => ({
  order: null,
  coverage: 'legacy_only',
  events: [],
  legacy_logs: []
})

export default {
  name: 'OrderTimelineDrawer',
  data() {
    return {
      visible: false,
      loading: false,
      error: '',
      activeTab: 'events',
      timeline: emptyTimeline()
    }
  },
  computed: {
    coverageLabel() {
      return (
        {
          legacy_only: '仅旧日志',
          hybrid: '新旧记录并行',
          event: '新版事件'
        }[this.timeline.coverage] || '记录来源未知'
      )
    },
    coverageType() {
      if (this.timeline.coverage === 'event') return 'success'
      if (this.timeline.coverage === 'hybrid') return 'warning'
      return 'info'
    }
  },
  methods: {
    open(orderId) {
      this.visible = true
      this.loading = true
      this.error = ''
      this.activeTab = 'events'
      this.timeline = emptyTimeline()
      timeline({ id: orderId })
        .then((res) => {
          const payload = res.data || {}
          this.timeline = {
            order: payload.order || null,
            coverage: payload.coverage || 'legacy_only',
            events: Array.isArray(payload.events) ? payload.events : [],
            legacy_logs: Array.isArray(payload.legacy_logs) ? payload.legacy_logs : []
          }
          if (!this.timeline.events.length && this.timeline.legacy_logs.length) {
            this.activeTab = 'legacy'
          }
        })
        .catch((error) => {
          this.error = error?.message || '订单流转加载失败，请稍后重试'
        })
        .finally(() => {
          this.loading = false
        })
    },
    eventLabel(eventType) {
      return (
        {
          'order.canceled': '买家取消订单',
          'order.received': '买家确认收货',
          'order.evaluated': '买家完成评价',
          'payment.voucher_approved': '凭证支付审核通过',
          'payment.voucher_rejected': '凭证支付审核拒绝',
          'payment.wechat_succeeded': '微信支付成功',
          'order.picked_up': '订单核销',
          'order.delivered': '订单发货',
          'refund.requested': '申请售后',
          'refund.service_approved': '售后审核通过',
          'refund.service_rejected': '售后审核拒绝',
          'refund.completed': '退款完成'
        }[eventType] ||
        eventType ||
        '订单事件'
      )
    }
  }
}
</script>

<style scoped>
.order-timeline-drawer {
  min-height: 240px;
  padding-right: 12px;
}

.order-timeline-summary {
  display: grid;
  grid-template-columns: minmax(180px, 1.6fr) minmax(100px, 0.8fr) minmax(120px, 1fr) auto;
  gap: 16px;
  align-items: center;
  padding: 14px 16px;
  border: 1px solid #e4e7ed;
  border-radius: 8px;
  background: #f8fafc;
}

.order-timeline-summary__label {
  margin-bottom: 4px;
  color: #909399;
  font-size: 12px;
}

.order-timeline-summary__value {
  overflow-wrap: anywhere;
  color: #303133;
  font-size: 14px;
  font-weight: 600;
}

.order-timeline-tabs {
  margin-top: 18px;
}

.order-timeline-item {
  padding: 12px 14px;
  border: 1px solid #e4e7ed;
  border-radius: 8px;
  background: #fff;
}

.order-timeline-item__header,
.order-timeline-item__meta {
  display: flex;
  flex-wrap: wrap;
  gap: 8px 16px;
  align-items: center;
  justify-content: space-between;
}

.order-timeline-item__header span,
.order-timeline-item__meta,
.order-timeline-item__request {
  color: #606266;
  font-size: 12px;
}

.order-timeline-item__meta,
.order-timeline-item__reason,
.order-timeline-item__request {
  margin-top: 8px;
  line-height: 1.6;
}

.order-timeline-item__reason {
  color: #303133;
}

.order-timeline-item__request {
  overflow-wrap: anywhere;
}

@media (max-width: 768px) {
  .order-timeline-summary {
    grid-template-columns: 1fr;
  }
}
</style>
