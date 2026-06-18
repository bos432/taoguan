<template>
  <view
    :class="
      'ui-theme-' +
      (($store.state.setting &&
        $store.state.setting.system &&
        $store.state.setting.system.ui_theme_style) ||
        'origin')
    "
  >
    <view class="renew-page">
      <view class="hero-card">
        <view class="hero-badge">{{ text.badge }}</view>
        <view class="hero-title">{{ pageTitle }}</view>
        <view class="hero-desc">{{ pageDesc }}</view>
      </view>

      <view class="env-card">
        <view class="card-head">
          <view class="card-title">{{ text.envTitle }}</view>
          <view
            class="env-badge"
            :class="currentEnvInfo.is_prod ? 'is-prod' : 'is-test'"
            >{{ currentEnvInfo.label }}</view
          >
        </view>
        <view class="env-desc">{{ envDescription }}</view>
        <view class="env-tags">
          <text class="env-tag">{{ envModeText }}</text>
          <text class="env-tag">{{ statusTagText }}</text>
          <text class="env-tag">{{ envIsolationText }}</text>
          <text class="env-tag">{{ envIsolationStatusText }}</text>
          <text class="env-tag">{{ envReleaseStageText }}</text>
        </view>
        <view class="env-desc">{{ envActionHint }}</view>
        <view class="env-desc env-desc--strong">{{ envReleaseHint }}</view>
        <view v-if="envRiskList.length" class="env-risk-list">
          <view v-for="item in envRiskList" :key="item" class="env-risk-item">{{
            item
          }}</view>
        </view>
        <view class="env-profile-board">
          <view class="env-profile-board__title">环境就绪看板</view>
          <view class="env-profile-board__list">
            <view
              v-for="item in profileReadinessList"
              :key="item.key"
              class="env-profile-board__item"
              :class="{ 'is-current': item.key === currentEnvInfo.key }"
            >
              <view class="env-profile-board__item-head">
                <text class="env-profile-board__item-name">{{
                  item.label
                }}</text>
                <text
                  class="env-profile-board__item-status"
                  :class="'is-' + ((item.status_text || '').toLowerCase())"
                >
                  {{ item.status_text }}
                </text>
              </view>
              <view class="env-profile-board__item-desc">{{
                item.short_hint
              }}</view>
            </view>
          </view>
        </view>
        <view class="env-url">{{
          currentEnvInfo.api_root_url || text.unset
        }}</view>
      </view>

      <view class="action-card rollout-card">
        <view class="card-head rollout-card__head">
          <view class="card-title rollout-card__title">上线承接提示</view>
          <view
            class="rollout-card__badge"
            :class="currentEnvInfo.is_prod ? 'is-prod' : 'is-test'"
          >
            {{ currentEnvInfo.is_prod ? "正式续费" : "灰度续费" }}
          </view>
        </view>
        <view class="env-desc rollout-card__desc">{{ renewRolloutHint }}</view>
        <view class="rollout-card__list">
          <view
            v-for="item in renewRolloutChecklist"
            :key="item.label"
            class="rollout-card__item"
          >
            <text class="rollout-card__item-label">{{ item.label }}</text>
            <text class="rollout-card__item-value">{{ item.value }}</text>
          </view>
        </view>
        <view class="rollout-card__risk">{{ renewRollbackHint }}</view>
      </view>

      <view class="status-card">
        <view class="card-title">{{ text.currentStatus }}</view>
        <view class="status-grid">
          <view class="status-item">
            <text class="status-label">{{ text.merchantTitle }}</text>
            <text class="status-value">{{ merchantDisplayTitle }}</text>
          </view>
          <view class="status-item">
            <text class="status-label">{{ text.expireTime }}</text>
            <text class="status-value">{{
              merchantInfo.expire_time || text.unset
            }}</text>
          </view>
          <view class="status-item">
            <text class="status-label">{{ text.remindDays }}</text>
            <text class="status-value"
              >{{ merchantInfo.renew_remind_days || 7 }} {{ text.day }}</text
            >
          </view>
          <view class="status-item">
            <text class="status-label">{{ text.currentState }}</text>
            <text class="status-value" :class="statusClass">{{
              displayExpireStatusTitle
            }}</text>
          </view>
        </view>
      </view>

      <view v-if="recentActionSummary" class="recent-card">
        <view class="card-title">{{ text.recentTitle }}</view>
        <view class="recent-desc">{{ recentActionSummary }}</view>
      </view>

      <view class="action-card">
        <view class="card-head">
          <view class="card-title">处理建议</view>
          <view class="status-hint-badge" :class="renewAdviceBadgeClass">{{
            renewAdviceBadgeText
          }}</view>
        </view>
        <view class="env-desc">{{ renewAdviceHint }}</view>
        <view class="renew-advice-tags">
          <text v-for="item in renewAdviceTags" :key="item" class="env-tag">{{
            item
          }}</text>
        </view>
        <view class="renew-advice-risk">{{ renewAdviceRiskText }}</view>
        <view class="renew-advice-actions">
          <button class="action-btn primary" @tap="refreshRecords">
            {{ text.refresh }}
          </button>
          <button class="action-btn ghost" @tap="goService">
            {{ text.contactService }}
          </button>
        </view>
      </view>

      <view class="action-card">
        <view class="card-title">{{ text.processTitle }}</view>
        <view class="step-item" v-for="(item, index) in steps" :key="index">
          <view class="step-index">{{ index + 1 }}</view>
          <view class="step-content">
            <view class="step-title">{{ item.title }}</view>
            <view class="step-desc">{{ item.desc }}</view>
          </view>
        </view>
        <view class="action-row">
          <button class="action-btn primary" @tap="goService">
            {{ text.contactService }}
          </button>
          <button class="action-btn ghost" @tap="refreshPage">
            {{ text.refresh }}
          </button>
        </view>
      </view>

      <view class="record-card">
        <view class="card-head">
          <view class="card-title">{{ text.recordTitle }}</view>
          <view class="card-link" @tap="refreshRecords">{{
            text.refresh
          }}</view>
        </view>
        <view v-if="records.length" class="record-list">
          <view class="record-item" v-for="item in records" :key="item.id">
            <view class="record-top">
              <text class="record-source">{{
                item.renew_source_title || text.adminRenew
              }}</text>
              <text class="record-time">{{ item.create_time || "--" }}</text>
            </view>
            <view class="record-row">
              <text>{{ text.renewResult }}</text>
              <text>{{
                formatExpireRange(
                  item.before_expire_time,
                  item.after_expire_time,
                )
              }}</text>
            </view>
            <view class="record-row">
              <text>{{ text.renewContent }}</text>
              <text>{{ formatDuration(item) }}</text>
            </view>
            <view class="record-row">
              <text>{{ text.renewAmount }}</text>
              <text>{{ text.currency }}{{ formatAmount(item.amount) }}</text>
            </view>
            <view class="record-row" v-if="item.remark">
              <text>{{ text.remark }}</text>
              <text>{{ item.remark }}</text>
            </view>
          </view>
        </view>
        <view v-else class="empty-box">
          <text>{{ text.emptyRecord }}</text>
        </view>
      </view>
    </view>
  </view>
</template>

<script>
import api from "@/api";
import { maskMerchantTitle } from "@/utils/desensitize.js";
import {
  getCurrentEnvInfo,
  getEnvIsolationHealth,
  getProfileReadinessList,
} from "@/utils/env-runtime.js";
import {
  getEnvIsolationHint,
  getEnvIsolationTag,
  getEnvReleaseHint,
  getEnvReleaseStageText,
} from "@/utils/env-risk.js";

const TEXT = {
  badge: "续费中心",
  currentStatus: "当前状态",
  merchantTitle: "商家名称",
  expireTime: "到期时间",
  remindDays: "提醒天数",
  currentState: "当前状态",
  processTitle: "续费流程",
  contactService: "联系客服",
  refresh: "刷新",
  recordTitle: "续费记录",
  adminRenew: "后台续费",
  renewResult: "续费结果",
  renewContent: "续费内容",
  renewAmount: "续费金额",
  currency: "￥",
  remark: "备注",
  emptyRecord: "暂无续费记录",
  unset: "未设置",
  unsetExpire: "未设置期限",
  day: "天",
  expiredTitle: "商家服务已到期",
  activeTitle: "商家服务管理",
  expiredDesc: "当前商家功能已暂停，续费完成后会自动恢复。",
  remindDescPrefix: "距离到期还有 ",
  remindDescSuffix: " 天，建议尽快续费。",
  normalDesc: "这里可以查看续费状态和历史记录。",
  step1Title: "查看当前服务状态",
  step1Desc: "这里会展示商家的到期时间、提醒天数和当前是否已到期。",
  step2Title: "联系平台处理续费",
  step2Desc:
    "目前续费由平台客服或后台统一处理，处理成功后会自动更新商家到期时间。",
  step3Title: "刷新后恢复使用",
  step3Desc: "续费完成后刷新本页或重新进入小程序，商家相关功能会自动恢复。",
  loadMerchantFail: "商家信息加载失败",
  envTitle: "当前环境",
  recentTitle: "最近操作",
  monthUnit: "个月",
  joinWord: " + ",
  arrow: " -> ",
  backendProcess: "后台处理",
};

export default {
  data() {
    return {
      text: TEXT,
      merchantInfo: {},
      records: [],
      recentActionSummary: "",
      steps: [
        { title: TEXT.step1Title, desc: TEXT.step1Desc },
        { title: TEXT.step2Title, desc: TEXT.step2Desc },
        { title: TEXT.step3Title, desc: TEXT.step3Desc },
      ],
    };
  },
  computed: {
    currentEnvInfo() {
      return getCurrentEnvInfo();
    },
    envDescription() {
      return this.currentEnvInfo.is_prod
        ? "当前为正式环境，续费状态展示对应真实商家有效期，请核对后再联系平台处理。"
        : "当前为非正式环境，可用于联调商家到期拦截、续费记录刷新和页面恢复流程。";
    },
    envModeText() {
      return this.currentEnvInfo.is_prod
        ? "当前模式：真实续费状态"
        : "当前模式：测试续费状态";
    },
    profileReadinessList() {
      return getProfileReadinessList();
    },
    statusTagText() {
      return `服务状态：${this.displayExpireStatusTitle}`;
    },
    envIsolationText() {
      return getEnvIsolationTag(this.currentEnvInfo);
    },
    envIsolationHealth() {
      return getEnvIsolationHealth();
    },
    envIsolationStatusText() {
      return this.envIsolationHealth.is_isolated_ready
        ? "隔离状态：已就绪"
        : "隔离状态：待处理";
    },
    envReleaseStageText() {
      return getEnvReleaseStageText(this.envIsolationHealth);
    },
    envReleaseHint() {
      return getEnvReleaseHint(this.envIsolationHealth);
    },
    envRiskList() {
      return this.envIsolationHealth.warnings || [];
    },
    envActionHint() {
      return this.currentEnvInfo.is_prod
        ? `当前页展示真实商家服务有效期与续费记录，请按正式续费流程处理。${getEnvIsolationHint(this.currentEnvInfo)}`
        : `建议在当前环境验证商家到期拦截、续费记录刷新和恢复链路。${getEnvIsolationHint(this.currentEnvInfo)}`;
    },
    merchantDisplayTitle() {
      return maskMerchantTitle(this.merchantInfo.title, this.text.unset);
    },
    pageTitle() {
      return Number(this.merchantInfo.is_expired || 0) === 1
        ? this.text.expiredTitle
        : this.text.activeTitle;
    },
    pageDesc() {
      if (Number(this.merchantInfo.is_expired || 0) === 1) {
        return this.text.expiredDesc;
      }
      if (Number(this.merchantInfo.should_remind || 0) === 1) {
        return `${this.text.remindDescPrefix}${this.merchantInfo.days_left || 0}${this.text.remindDescSuffix}`;
      }
      return this.text.normalDesc;
    },
    displayExpireStatusTitle() {
      return this.normalizeExpireStatusTitle(
        this.merchantInfo.expire_status_title,
        this.merchantInfo.expire_status,
      );
    },
    statusClass() {
      if (Number(this.merchantInfo.is_expired || 0) === 1) {
        return "is-danger";
      }
      if (Number(this.merchantInfo.should_remind || 0) === 1) {
        return "is-warning";
      }
      return "is-safe";
    },
    renewAdviceBadgeText() {
      if (Number(this.merchantInfo.is_expired || 0) === 1) {
        return "优先处理";
      }
      if (Number(this.merchantInfo.should_remind || 0) === 1) {
        return "建议跟进";
      }
      return "状态稳定";
    },
    renewAdviceBadgeClass() {
      if (Number(this.merchantInfo.is_expired || 0) === 1) {
        return "is-danger";
      }
      if (Number(this.merchantInfo.should_remind || 0) === 1) {
        return "is-warning";
      }
      return "is-safe";
    },
    renewAdviceHint() {
      if (Number(this.merchantInfo.is_expired || 0) === 1) {
        return "当前商家服务已经到期，建议优先联系平台处理续费，并在处理完成后立即刷新确认。";
      }
      if (Number(this.merchantInfo.should_remind || 0) === 1) {
        return "当前商家已进入续费提醒阶段，建议提前与平台确认，避免到期后影响经营。";
      }
      return "当前商家服务状态正常，可定期核对有效期和续费记录，避免漏掉后台续费结果。";
    },
    renewAdviceTags() {
      return [
        `当前状态：${this.displayExpireStatusTitle}`,
        `记录数量：${this.records.length || 0}`,
        `提醒天数：${this.merchantInfo.renew_remind_days || 7} 天`,
        `到期时间：${this.merchantInfo.expire_time || this.text.unsetExpire}`,
      ];
    },
    renewAdviceRiskText() {
      if (!this.records.length) {
        return "当前还没有查到续费记录，如近期已做过续费处理，建议先刷新记录再核对结果。";
      }
      if (Number(this.merchantInfo.is_expired || 0) === 1) {
        return "当前商家已处于到期状态，若续费处理已完成但页面仍未恢复，需要继续核对后台生效结果。";
      }
      return this.currentEnvInfo.is_prod
        ? "当前页展示的是正式商家服务状态，联系平台续费前请再次核对商家名称和到期时间。"
        : "当前页更适合做到期提醒、续费记录刷新和恢复链路联调，建议顺手核对状态回显是否一致。";
    },
    renewRolloutHint() {
      return this.currentEnvInfo.is_prod
        ? "当前续费中心处于正式承接模式，建议先核对商家名称、到期时间和最近续费记录，再联系平台处理。"
        : "当前环境适合验证到期提醒、续费记录刷新和续费后恢复链路，不要把联调记录当成正式结果。";
    },
    renewRolloutChecklist() {
      return [
        {
          label: "当前服务状态",
          value: this.displayExpireStatusTitle,
        },
        {
          label: "到期时间",
          value: this.merchantInfo.expire_time || this.text.unsetExpire,
        },
        {
          label: "续费记录",
          value: `${this.records.length || 0} 条`,
        },
      ];
    },
    renewRollbackHint() {
      return this.currentEnvInfo.is_prod
        ? "如果续费处理后页面状态未恢复，先刷新记录并核对最新到期时间，再决定是否继续反馈后台处理。"
        : "灰度联调如状态异常，优先刷新记录并重新进入页面，确认是否为缓存或测试数据未回写导致。";
    },
  },
  onShow() {
    this.refreshPage();
  },
  methods: {
    updateRecentActionSummary(summary) {
      this.recentActionSummary = summary;
    },
    refreshPage() {
      this.updateRecentActionSummary("正在刷新商家续费状态和续费记录。");
      this.getMerchantInfo();
      this.getRenewRecords();
    },
    refreshRecords() {
      this.updateRecentActionSummary("正在刷新续费记录。");
      this.getRenewRecords();
    },
    getMerchantInfo() {
      api
        .merchantInfo({})
        .then((res) => {
          this.merchantInfo = res.data || {};
          this.updateRecentActionSummary(
            `续费状态已更新：${this.displayExpireStatusTitle}。`,
          );
        })
        .catch(() => {
          this.updateRecentActionSummary("商家续费状态加载失败。");
          uni.showToast({
            title: this.text.loadMerchantFail,
            icon: "none",
          });
        });
    },
    getRenewRecords() {
      api
        .getMerchantRenewRecords({ page: 1, limit: 20 })
        .then((res) => {
          this.records = (res.data && res.data.list) || [];
          this.updateRecentActionSummary(
            `续费记录已刷新，当前共 ${this.records.length} 条。`,
          );
        })
        .catch(() => {
          this.records = [];
          this.updateRecentActionSummary("续费记录加载失败。");
        });
    },
    goService() {
      this.updateRecentActionSummary(
        `正在联系客服处理续费。${getEnvIsolationHint(this.currentEnvInfo)}`,
      );
      uni.navigateTo({
        url: "/pages/help/service",
      });
    },
    formatAmount(value) {
      return Number(value || 0).toFixed(2);
    },
    normalizeExpireStatusTitle(title, status) {
      const rawTitle = String(title || "").trim();
      const rawStatus = String(status || "")
        .trim()
        .toLowerCase();
      const titleMap = {
        Active: "正常有效",
        Expired: "已到期",
        "Expiring soon": "即将到期",
        "No expiry set": "未设置期限",
      };
      const statusMap = {
        active: "正常有效",
        expired: "已到期",
        remind: "即将到期",
        unset: "未设置期限",
      };
      if (titleMap[rawTitle]) {
        return titleMap[rawTitle];
      }
      if (rawTitle) {
        return rawTitle;
      }
      return statusMap[rawStatus] || this.text.unsetExpire;
    },
    formatDuration(item = {}) {
      if (item.adjust_title) {
        return item.adjust_title;
      }
      const months = Number(item.renew_months || 0);
      const days = Number(item.renew_days || 0);
      const segments = [];
      if (months > 0) {
        segments.push(`增加${months}${this.text.monthUnit}`);
      } else if (months < 0) {
        segments.push(`减少${Math.abs(months)}${this.text.monthUnit}`);
      }
      if (days > 0) {
        segments.push(`增加${days}${this.text.day}`);
      } else if (days < 0) {
        segments.push(`减少${Math.abs(days)}${this.text.day}`);
      }
      if (segments.length) {
        return segments.join(this.text.joinWord);
      }
      return this.text.backendProcess;
    },
    formatExpireRange(beforeTime, afterTime) {
      return `${beforeTime || this.text.unset}${this.text.arrow}${afterTime || this.text.unset}`;
    },
  },
};
</script>

<style lang="scss" scoped>
.renew-page {
  min-height: 100vh;
  padding: 28rpx;
  background: linear-gradient(180deg, #f7fbff 0%, #fff7f2 100%);
}

.hero-card,
.env-card,
.status-card,
.action-card,
.record-card,
.recent-card {
  background: #ffffff;
  border-radius: 28rpx;
  padding: 30rpx;
  box-shadow: 0 16rpx 40rpx rgba(18, 38, 63, 0.08);
  margin-bottom: 24rpx;
}

.env-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 42rpx;
  padding: 0 18rpx;
  border-radius: 999rpx;
  font-size: 22rpx;
  color: #fff;
  background: linear-gradient(45deg, #1cbbb4, #0081ff);
}

.env-badge.is-prod {
  background: linear-gradient(45deg, #ef4444, #f97316);
}

.env-desc {
  color: #6b778c;
  font-size: 24rpx;
  line-height: 1.7;
}

.env-desc--strong {
  margin-top: 12rpx;
  padding: 14rpx 16rpx;
  border-radius: 18rpx;
  background: rgba(39, 95, 143, 0.06);
  color: #35506b;
}

.env-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 12rpx;
  margin-top: 14rpx;
}

.env-tag {
  display: inline-flex;
  align-items: center;
  min-height: 40rpx;
  padding: 0 16rpx;
  border-radius: 999rpx;
  background: rgba(39, 95, 143, 0.08);
  color: #275f8f;
  font-size: 22rpx;
}

.env-url {
  margin-top: 12rpx;
  color: #97a1b3;
  font-size: 22rpx;
  line-height: 1.6;
  word-break: break-all;
}

.env-risk-list {
  margin-top: 12rpx;
  padding: 14rpx 16rpx;
  border-radius: 18rpx;
  background: rgba(255, 244, 232, 0.98);
}

.env-risk-item {
  color: #8c5145;
  font-size: 21rpx;
  line-height: 1.7;
}

.status-hint-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 42rpx;
  padding: 0 18rpx;
  border-radius: 999rpx;
  font-size: 22rpx;
  font-weight: 600;
  color: #166534;
  background: rgba(220, 252, 231, 0.96);
}

.status-hint-badge.is-warning {
  color: #9a3412;
  background: rgba(255, 237, 213, 0.96);
}

.status-hint-badge.is-danger {
  color: #b91c1c;
  background: rgba(254, 226, 226, 0.96);
}

.renew-advice-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 12rpx;
  margin-top: 14rpx;
}

.renew-advice-risk {
  margin-top: 14rpx;
  padding: 18rpx 20rpx;
  border-radius: 18rpx;
  background: rgba(255, 247, 237, 0.98);
  color: #9a3412;
  font-size: 22rpx;
  line-height: 1.65;
}

.renew-advice-actions {
  display: flex;
  gap: 16rpx;
  margin-top: 18rpx;
}

.env-risk-item + .env-risk-item {
  margin-top: 8rpx;
}

.env-profile-board,
.rollout-card {
  margin-top: 20rpx;
  padding: 24rpx;
  border-radius: 26rpx;
  background: rgba(255, 255, 255, 0.92);
  box-shadow: 0 12rpx 26rpx rgba(17, 34, 51, 0.06);
}

.env-profile-board {
  margin-top: 14rpx;
  padding: 16rpx 18rpx;
  border-radius: 20rpx;
  background: rgba(39, 95, 143, 0.05);
  box-shadow: none;
}

.env-profile-board__title,
.rollout-card__title {
  font-size: 24rpx;
  font-weight: 700;
  color: #10283d;
}

.env-profile-board__list,
.rollout-card__list {
  display: flex;
  flex-direction: column;
  gap: 12rpx;
  margin-top: 14rpx;
}

.env-profile-board__item,
.rollout-card__item {
  padding: 16rpx 18rpx;
  border-radius: 18rpx;
  background: rgba(255, 255, 255, 0.92);
}

.env-profile-board__item.is-current {
  border: 1rpx solid rgba(0, 129, 255, 0.22);
}

.env-profile-board__item-head,
.rollout-card__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12rpx;
}

.env-profile-board__item-name,
.rollout-card__item-label {
  font-size: 22rpx;
  color: #23405d;
  font-weight: 600;
}

.env-profile-board__item-status,
.rollout-card__badge {
  min-width: 96rpx;
  padding: 6rpx 16rpx;
  border-radius: 999rpx;
  text-align: center;
  font-size: 20rpx;
  color: #ffffff;
  background: linear-gradient(45deg, #0081ff, #1cbbb4);
}

.env-profile-board__item-status.is-hold {
  background: linear-gradient(45deg, #f59e0b, #f97316);
}

.env-profile-board__item-status.is-ready,
.rollout-card__badge.is-prod {
  background: linear-gradient(45deg, #ef4444, #f97316);
}

.env-profile-board__item-desc,
.rollout-card__desc,
.rollout-card__risk,
.rollout-card__item-value {
  margin-top: 8rpx;
  font-size: 21rpx;
  line-height: 1.7;
  color: #6b7b8c;
}

.rollout-card__item-value {
  display: block;
  color: #1c425f;
  font-weight: 600;
}

.rollout-card__risk {
  margin-top: 16rpx;
  padding: 18rpx 20rpx;
  border-radius: 20rpx;
  background: rgba(255, 247, 237, 0.96);
  color: #9a3412;
}

.recent-desc {
  color: #6b778c;
  font-size: 24rpx;
  line-height: 1.7;
}

.hero-card {
  background: linear-gradient(135deg, #275f8f 0%, #f08a51 100%);
  color: #fff;
}

.hero-badge {
  display: inline-block;
  padding: 8rpx 18rpx;
  border-radius: 999rpx;
  background: rgba(255, 255, 255, 0.18);
  font-size: 24rpx;
  margin-bottom: 16rpx;
}

.hero-title {
  font-size: 40rpx;
  font-weight: 700;
}

.hero-desc {
  margin-top: 14rpx;
  font-size: 26rpx;
  line-height: 1.7;
  opacity: 0.92;
}

.card-title {
  font-size: 30rpx;
  font-weight: 700;
  color: #1f2d3d;
  margin-bottom: 22rpx;
}

.status-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 20rpx;
}

.status-item {
  padding: 22rpx;
  border-radius: 20rpx;
  background: #f7f9fc;
}

.status-label {
  display: block;
  color: #7a8699;
  font-size: 24rpx;
  margin-bottom: 10rpx;
}

.status-value {
  color: #1f2d3d;
  font-size: 28rpx;
  font-weight: 600;
  word-break: break-all;
}

.is-danger {
  color: #e55353;
}

.is-warning {
  color: #d4861c;
}

.is-safe {
  color: #1d8f5f;
}

.step-item {
  display: flex;
  align-items: flex-start;
  padding: 18rpx 0;
}

.step-index {
  width: 44rpx;
  height: 44rpx;
  border-radius: 50%;
  background: #275f8f;
  color: #fff;
  text-align: center;
  line-height: 44rpx;
  font-size: 24rpx;
  font-weight: 700;
  margin-right: 20rpx;
  flex-shrink: 0;
}

.step-title {
  font-size: 28rpx;
  font-weight: 600;
  color: #1f2d3d;
}

.step-desc {
  margin-top: 8rpx;
  color: #6b778c;
  font-size: 24rpx;
  line-height: 1.7;
}

.action-row {
  display: flex;
  gap: 20rpx;
  margin-top: 24rpx;
}

.action-btn {
  flex: 1;
  border-radius: 999rpx;
  font-size: 28rpx;
  border: none;
}

.action-btn.primary {
  background: linear-gradient(135deg, #275f8f 0%, #f08a51 100%);
  color: #fff;
}

.action-btn.ghost {
  background: #f2f5f9;
  color: #275f8f;
}

.card-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 18rpx;
}

.card-link {
  font-size: 24rpx;
  color: #275f8f;
}

.record-item {
  padding: 24rpx 0;
  border-bottom: 1rpx solid #eef2f7;
}

.record-item:last-child {
  border-bottom: none;
  padding-bottom: 0;
}

.record-top,
.record-row {
  display: flex;
  justify-content: space-between;
  gap: 20rpx;
}

.record-top {
  margin-bottom: 14rpx;
}

.record-source {
  font-size: 28rpx;
  font-weight: 600;
  color: #1f2d3d;
}

.record-time,
.record-row {
  color: #6b778c;
  font-size: 24rpx;
  line-height: 1.7;
}

.empty-box {
  padding: 50rpx 0 20rpx;
  text-align: center;
  color: #97a1b3;
  font-size: 26rpx;
}
</style>

