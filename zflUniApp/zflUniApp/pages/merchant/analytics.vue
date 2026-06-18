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
    <view class="analytics-page">
      <view class="hero-card">
        <view class="hero-main">
          <view>
            <view class="hero-title">{{ merchantDisplayTitle }}</view>
            <view class="hero-subtitle">{{
              rangeSummary.label || currentRangeHeroLabel
            }}</view>
          </view>
          <view class="hero-date">
            {{ rangeSummary.start_date || "--" }} 至
            {{ rangeSummary.end_date || "--" }}
          </view>
        </view>

        <view class="hero-metrics">
          <view class="hero-metric">
            <text class="metric-label">成交金额</text>
            <text class="metric-value"
              >￥{{ formatMoney(rangeSummary.paid_amount) }}</text
            >
          </view>
          <view class="hero-metric">
            <text class="metric-label">成交订单</text>
            <text class="metric-value">{{
              rangeSummary.paid_order_count || 0
            }}</text>
          </view>
          <view class="hero-metric">
            <text class="metric-label">成交买家</text>
            <text class="metric-value">{{
              rangeSummary.buyer_count || 0
            }}</text>
          </view>
        </view>
      </view>

      <view class="section-card env-card">
        <view class="section-head">
          <view class="section-title">当前环境</view>
          <view
            class="env-badge"
            :class="currentEnvInfo.is_prod ? 'is-prod' : 'is-test'"
            >{{ currentEnvInfo.label }}</view
          >
        </view>
        <view class="section-tip">{{ envDescription }}</view>
        <view class="env-meta">
          <text class="env-tag">{{ currentRangeHeroLabel }}</text>
          <text class="env-tag">{{ merchantAccessTag }}</text>
          <text class="env-tag">{{ envIsolationStatusText }}</text>
          <text class="env-tag">{{ envReleaseStageText }}</text>
        </view>
        <view class="section-tip env-tip">{{ envActionHint }}</view>
        <view class="section-tip env-tip env-tip--strong">{{
          envReleaseHint
        }}</view>
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
        <view class="env-url">{{ currentEnvInfo.api_root_url }}</view>
      </view>

      <view class="section-card rollout-card">
        <view class="section-head rollout-card__head">
          <view class="section-title rollout-card__title">上线承接提示</view>
          <view
            class="rollout-card__badge"
            :class="currentEnvInfo.is_prod ? 'is-prod' : 'is-test'"
          >
            {{ currentEnvInfo.is_prod ? "正式分析" : "灰度分析" }}
          </view>
        </view>
        <view class="section-tip rollout-card__desc">{{
          analyticsRolloutHint
        }}</view>
        <view class="rollout-card__list">
          <view
            v-for="item in analyticsRolloutChecklist"
            :key="item.label"
            class="rollout-card__item"
          >
            <text class="rollout-card__item-label">{{ item.label }}</text>
            <text class="rollout-card__item-value">{{ item.value }}</text>
          </view>
        </view>
        <view class="rollout-card__risk">{{ analyticsRollbackHint }}</view>
      </view>

      <view v-if="recentActionSummary" class="section-card recent-action-card">
        <view class="section-title">最近操作</view>
        <view class="section-tip recent-action-card__desc">{{
          recentActionSummary
        }}</view>
      </view>

      <view class="section-card operation-card">
        <view class="section-head">
          <view class="section-title">运营承接</view>
          <view class="section-tip">{{ operationCardDesc }}</view>
        </view>
        <view class="operation-grid">
          <view
            v-for="item in operationActions"
            :key="item.code"
            class="operation-item"
            :class="item.tone"
            @tap="openOperationAction(item)"
          >
            <view class="operation-item__title">{{ item.label }}</view>
            <view class="operation-item__desc">{{ item.desc }}</view>
          </view>
        </view>
      </view>

      <view class="range-tabs">
        <view
          v-for="item in rangeOptions"
          :key="item.key"
          class="range-tab"
          :class="{ active: isActiveRange(item) }"
          @tap="changeRange(item)"
        >
          {{ item.label }}
        </view>
      </view>

      <view v-if="accessWarning" class="section-card warning-card">
        <view class="warning-copy">
          <view class="section-title">访问校验提醒</view>
          <view class="section-tip">{{ accessWarning }}</view>
        </view>
        <view class="warning-action" @tap="retryAccessCheck">重新校验</view>
      </view>

      <view v-if="isCustomRange" class="custom-range-panel">
        <view class="custom-range-head">
          <view class="custom-range-title">自定义时间段</view>
          <view class="custom-range-tip">支持按开始和结束日期筛选经营数据</view>
        </view>
        <view class="custom-range-row">
          <picker
            mode="date"
            :value="customRange.start_date"
            :end="customRange.end_date || todayString"
            @change="onCustomStartDateChange"
          >
            <view class="custom-date-field">
              <text class="custom-date-label">开始日期</text>
              <text class="custom-date-value">{{
                customRange.start_date || "请选择"
              }}</text>
            </view>
          </picker>
          <picker
            mode="date"
            :value="customRange.end_date"
            :start="customRange.start_date || ''"
            :end="todayString"
            @change="onCustomEndDateChange"
          >
            <view class="custom-date-field">
              <text class="custom-date-label">结束日期</text>
              <text class="custom-date-value">{{
                customRange.end_date || "请选择"
              }}</text>
            </view>
          </picker>
        </view>
        <view class="custom-range-actions">
          <view class="custom-range-ghost" @tap="resetCustomRange"
            >重置为近 7 天</view
          >
          <view class="custom-range-submit" @tap="applyCustomRange"
            >应用筛选</view
          >
        </view>
      </view>

      <view class="section-card">
        <view class="section-title">核心指标</view>
        <view class="overview-grid">
          <view
            v-for="item in overviewCards"
            :key="item.label"
            class="overview-item"
          >
            <text class="overview-label">{{ item.label }}</text>
            <text class="overview-value">{{
              item.isMoney ? "￥" + formatMoney(item.value) : item.value
            }}</text>
          </view>
        </view>
      </view>

      <view class="section-card">
        <view class="section-head">
          <view class="section-title">买卖对比</view>
          <view class="section-tip">看你买别人和别人买你是否持平</view>
        </view>
        <view class="today-comparison-grid">
          <view class="today-comparison-item">
            <text class="summary-key">今日我买别人</text>
            <text class="summary-value is-buy"
              >￥{{ formatMoney(tradeComparison.today_buy_amount) }}</text
            >
            <text class="today-comparison-meta"
              >成交 {{ tradeComparison.today_buy_order_count || 0 }} 单</text
            >
          </view>
          <view class="today-comparison-item">
            <text class="summary-key">今日别人买我</text>
            <text class="summary-value is-sale"
              >￥{{ formatMoney(tradeComparison.today_sale_amount) }}</text
            >
            <text class="today-comparison-meta"
              >成交 {{ tradeComparison.today_sale_order_count || 0 }} 单</text
            >
          </view>
        </view>
        <view class="comparison-grid">
          <view class="comparison-item buy">
            <text class="comparison-label"
              >{{ comparisonPeriodText }} 我买别人</text
            >
            <text class="comparison-value"
              >￥{{ formatMoney(tradeComparison.range_buy_amount) }}</text
            >
            <text class="comparison-meta"
              >成交 {{ tradeComparison.range_buy_order_count || 0 }} 单</text
            >
          </view>
          <view class="comparison-item sale">
            <text class="comparison-label"
              >{{ comparisonPeriodText }} 别人买我</text
            >
            <text class="comparison-value"
              >￥{{ formatMoney(tradeComparison.range_sale_amount) }}</text
            >
            <text class="comparison-meta"
              >成交 {{ tradeComparison.range_sale_order_count || 0 }} 单</text
            >
          </view>
        </view>
        <view class="comparison-summary">
          <view class="comparison-summary-item">
            <text class="summary-key">{{ comparisonPeriodText }} 差额</text>
            <text class="summary-value" :class="comparisonTone"
              >￥{{
                formatMoney(Math.abs(tradeComparison.range_diff_amount || 0))
              }}</text
            >
          </view>
          <view class="comparison-summary-item">
            <text class="summary-key">{{ comparisonPeriodText }} 买卖比</text>
            <text class="summary-value">{{ comparisonRatioText }}</text>
          </view>
          <view class="comparison-summary-item">
            <text class="summary-key">今日判断</text>
            <text class="summary-value" :class="todayComparisonTone">{{
              todayComparisonText
            }}</text>
          </view>
          <view class="comparison-summary-item">
            <text class="summary-key">累计买 / 卖</text>
            <text class="summary-value"
              >￥{{ formatMoney(tradeComparison.total_buy_amount) }} / ￥{{
                formatMoney(tradeComparison.total_sale_amount)
              }}</text
            >
          </view>
          <view class="comparison-summary-item full">
            <text class="summary-key">当前判断</text>
            <text class="summary-value" :class="comparisonTone">{{
              comparisonText
            }}</text>
          </view>
        </view>
      </view>

      <view class="section-card">
        <view class="section-head">
          <view class="section-title">买卖趋势</view>
          <view class="section-tip">按日统计买入金额与卖出金额</view>
        </view>
        <view v-if="trend.length" class="trend-list">
          <view v-for="item in trend" :key="item.label" class="trend-row">
            <view class="trend-label">{{ item.label }}</view>
            <view class="trend-main">
              <view class="trend-block">
                <view class="trend-track buy-track">
                  <view
                    class="trend-bar buy-bar"
                    :style="{ width: getAmountWidth(item.buy_amount) }"
                  ></view>
                </view>
                <view class="trend-meta">
                  <text>买 ￥{{ formatMoney(item.buy_amount) }}</text>
                  <text>订单 {{ item.buy_order_count || 0 }}</text>
                </view>
              </view>
              <view class="trend-block">
                <view class="trend-track sale-track">
                  <view
                    class="trend-bar sale-overlay"
                    :style="{ width: getAmountWidth(item.paid_amount) }"
                  ></view>
                </view>
                <view class="trend-meta">
                  <text>卖 ￥{{ formatMoney(item.paid_amount) }}</text>
                  <text>成交 {{ item.paid_order_count || 0 }}</text>
                </view>
              </view>
            </view>
          </view>
        </view>
        <view v-else class="empty-state">当前时间范围内暂无趋势数据</view>
      </view>

      <view class="section-card">
        <view class="section-head">
          <view class="section-title">订单状态分布</view>
          <view class="section-tip">基于全部订单统计</view>
        </view>
        <view v-if="statusBreakdown.length" class="status-list">
          <view
            v-for="item in statusBreakdown"
            :key="item.code"
            class="status-row"
          >
            <view class="status-top">
              <text class="status-name">{{ item.label }}</text>
              <text class="status-value"
                >{{ item.count }} 单 / {{ item.rate }}%</text
              >
            </view>
            <view class="status-track">
              <view
                class="status-bar"
                :style="{ width: item.rate + '%' }"
              ></view>
            </view>
          </view>
        </view>
        <view v-else class="empty-state">暂无订单状态数据</view>
      </view>

      <view class="section-card">
        <view class="section-head">
          <view class="section-title">热销商品 Top 5</view>
          <view class="section-tip">{{ comparisonPeriodText }} 成交额排序</view>
        </view>
        <view v-if="topGoods.length" class="goods-list">
          <view
            v-for="(item, index) in topGoods"
            :key="item.goods_id"
            class="goods-item"
          >
            <view class="goods-rank">{{ index + 1 }}</view>
            <image
              class="goods-image"
              :src="item.image_url || defaultImage"
              mode="aspectFill"
            ></image>
            <view class="goods-info">
              <view class="goods-title">{{ item.title }}</view>
              <view class="goods-meta"
                >销量 {{ item.sale_num || 0 }}{{ item.unit || "件" }}</view
              >
              <view class="goods-meta">订单 {{ item.order_count || 0 }}</view>
            </view>
            <view class="goods-amount"
              >￥{{ formatMoney(item.sale_amount) }}</view
            >
          </view>
        </view>
        <view v-else class="empty-state">当前时间范围内暂无热销商品</view>
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
  getEnvReleaseHint,
  getEnvReleaseStageText,
} from "@/utils/env-risk.js";

const MAX_CUSTOM_RANGE_DAYS = 93;

export default {
  data() {
    return {
      merchantAccessInfo: {},
      currentDays: 7,
      activeRangeType: "days",
      rangeOptions: [
        { key: "days-7", label: "近 7 天", value: 7, type: "days" },
        { key: "days-15", label: "近 15 天", value: 15, type: "days" },
        { key: "days-30", label: "近 30 天", value: 30, type: "days" },
        { key: "all", label: "全部", value: "all", type: "all" },
        { key: "custom", label: "自定义", value: "custom", type: "custom" },
      ],
      customRange: {
        start_date: "",
        end_date: "",
      },
      merchant: {},
      overview: {},
      rangeSummary: {},
      tradeComparison: {},
      trend: [],
      statusBreakdown: [],
      topGoods: [],
      defaultImage: "/static/images/avatar/1.jpg",
      accessWarning: "",
      recentActionSummary: "",
    };
  },
  computed: {
    merchantDisplayTitle() {
      return maskMerchantTitle(this.merchant.title, "商家数据分析");
    },
    overviewCards() {
      return [
        { label: "累计订单", value: this.overview.order_count || 0 },
        {
          label: "累计成交",
          value: this.overview.paid_amount || 0,
          isMoney: true,
        },
        {
          label: "累计已购",
          value: this.tradeComparison.total_buy_amount || 0,
          isMoney: true,
        },
        { label: "今日订单", value: this.overview.today_order_count || 0 },
        {
          label: "今日成交",
          value: this.overview.today_paid_amount || 0,
          isMoney: true,
        },
        {
          label: "今日已购",
          value: this.tradeComparison.today_buy_amount || 0,
          isMoney: true,
        },
        {
          label: "客单价",
          value: this.overview.average_order_amount || 0,
          isMoney: true,
        },
        { label: "今日买家", value: this.overview.today_buyer_count || 0 },
        { label: "商品总数", value: this.overview.goods_count || 0 },
        { label: "在售商品", value: this.overview.on_sale_goods_count || 0 },
        { label: "售罄商品", value: this.overview.sold_out_goods_count || 0 },
        { label: "待审核商品", value: this.overview.pending_goods_count || 0 },
      ];
    },
    comparisonText() {
      const diff = Number(this.tradeComparison.range_diff_amount || 0);
      if (Math.abs(diff) < 0.01) {
        return this.comparisonPeriodText + "买卖基本持平";
      }
      return diff > 0
        ? this.comparisonPeriodText + "卖出高于买入"
        : this.comparisonPeriodText + "买入高于卖出";
    },
    comparisonTone() {
      const diff = Number(this.tradeComparison.range_diff_amount || 0);
      if (Math.abs(diff) < 0.01) {
        return "is-neutral";
      }
      return diff > 0 ? "is-sale" : "is-buy";
    },
    comparisonRatioText() {
      const ratio = this.tradeComparison.range_buy_to_sale_ratio;
      if (ratio === null || ratio === undefined) {
        return "暂无可比数据";
      }
      return ratio + "%";
    },
    todayComparisonText() {
      const diff = Number(this.tradeComparison.today_diff_amount || 0);
      if (Math.abs(diff) < 0.01) {
        return "今日买卖基本持平";
      }
      return diff > 0 ? "今日卖出更高" : "今日买入更高";
    },
    todayComparisonTone() {
      const diff = Number(this.tradeComparison.today_diff_amount || 0);
      if (Math.abs(diff) < 0.01) {
        return "is-neutral";
      }
      return diff > 0 ? "is-sale" : "is-buy";
    },
    isCustomRange() {
      return this.activeRangeType === "custom";
    },
    isAllRange() {
      return this.activeRangeType === "all";
    },
    currentRangeHeroLabel() {
      if (this.isAllRange) {
        return "全部数据经营概览";
      }
      return this.isCustomRange
        ? this.customRangeDisplay + " 经营概览"
        : "近 " + this.currentDays + " 天经营概览";
    },
    comparisonPeriodText() {
      if (this.isAllRange) {
        return "全部数据";
      }
      return this.isCustomRange
        ? this.customRangeDisplay
        : "近 " + this.currentDays + " 天";
    },
    customRangeDisplay() {
      if (this.customRange.start_date && this.customRange.end_date) {
        return this.customRange.start_date + " 至 " + this.customRange.end_date;
      }
      return "自定义时间段";
    },
    todayString() {
      return this.formatDate(new Date());
    },
    currentEnvInfo() {
      return getCurrentEnvInfo();
    },
    envIsolationHealth() {
      return getEnvIsolationHealth();
    },
    profileReadinessList() {
      return getProfileReadinessList();
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
    envDescription() {
      return this.currentEnvInfo.is_prod
        ? "当前为正式环境，分析数据来自线上真实商家经营结果，请按正式业务口径核对。"
        : "当前为非正式环境，适合联调经营分析、筛选条件和回显展示。";
    },
    envActionHint() {
      return this.currentEnvInfo.is_prod
        ? `当前经营分析展示真实商家成交、买卖对比与热销数据，请按正式口径核对。${getEnvIsolationHint(this.currentEnvInfo)}`
        : `建议在当前环境重点验证筛选切换、自定义时间段和分析回显。${getEnvIsolationHint(this.currentEnvInfo)}`;
    },
    merchantAccessTag() {
      return Number(this.merchantAccessInfo.is_expired || 0) === 1
        ? "商家状态：已到期"
        : "商家状态：可分析";
    },
    operationCardDesc() {
      return Number(this.merchantAccessInfo.is_expired || 0) === 1
        ? "当前商家已到期，先续费再继续发布和经营。"
        : "分析完成后可直接回到商品池、发布页或商家工作台继续处理。";
    },
    analyticsRolloutHint() {
      return this.currentEnvInfo.is_prod
        ? "当前经营分析页已进入正式承接模式，建议按时间范围、经营结果和商家状态三层顺序复核。"
        : "当前环境适合做灰度联调，重点确认时间筛选、自定义时间段和经营分析回显是否稳定。";
    },
    analyticsRolloutChecklist() {
      return [
        {
          label: "当前分析范围",
          value: this.rangeSummary.label || this.currentRangeHeroLabel,
        },
        {
          label: "商家状态",
          value: this.merchantAccessTag,
        },
        {
          label: "隔离阶段",
          value: this.envReleaseStageText,
        },
      ];
    },
    analyticsRollbackHint() {
      return this.currentEnvInfo.is_prod
        ? "如发现口径异常，先记录当前筛选条件和商家状态，再回到工作台或商品池复核，不要直接按测试数据判断。"
        : "灰度联调如出现异常，优先保留当前筛选范围并回退到近 7 天默认视图，确认是否为联调数据问题。";
    },
    operationActions() {
      const actions = [
        {
          code: "sell",
          label: "查看商品池",
          desc: "回到商城商品页核对在售和热销商品。",
          url: "/pages/app/sell",
          openType: "switchTab",
          tone: "is-sell",
        },
        {
          code: "my",
          label: "返回工作台",
          desc: "回到我的页面继续处理商家工具和订单。",
          url: "/pages/app/my",
          openType: "switchTab",
          tone: "is-workbench",
        },
      ];

      if (Number(this.merchantAccessInfo.is_expired || 0) === 1) {
        actions.unshift({
          code: "renew",
          label: "前往续费",
          desc: "恢复商家服务后再继续使用经营分析和发布能力。",
          url: "/pages/merchant/renew",
          openType: "navigateTo",
          tone: "is-renew",
        });
        return actions;
      }

      if (Number(this.merchantAccessInfo.auth_state || 0) === 1) {
        actions.unshift({
          code: "release",
          label: "发布商品",
          desc: "直接进入商品发布页，补货或上新后再回看数据。",
          url: "/pages/app/release",
          openType: "navigateTo",
          tone: "is-release",
        });
      }

      return actions;
    },
  },
  onLoad() {
    this.resetCustomRange();
    this.checkMerchantAccess();
  },
  onPullDownRefresh() {
    this.checkMerchantAccess(true);
  },
  methods: {
    updateRecentActionSummary(summary) {
      this.recentActionSummary = summary;
    },
    async checkMerchantAccess(stopRefresh = false) {
      try {
        const res = await api.merchantInfo({});
        this.merchantAccessInfo = res.data || {};
        this.accessWarning = "";
        this.updateRecentActionSummary("商家分析访问状态已刷新。");
        if (Number(this.merchantAccessInfo.is_expired || 0) === 1) {
          if (stopRefresh) {
            uni.stopPullDownRefresh();
          }
          this.showExpireDialog();
          return;
        }
      } catch (error) {
        this.accessWarning = "商家信息校验失败，已尝试直接加载统计数据";
        this.updateRecentActionSummary(
          "商家信息校验失败，已转为直接加载分析数据。",
        );
        uni.showToast({
          title: "商家信息校验失败，已继续加载统计数据",
          icon: "none",
        });
      }

      await this.getAnalytics(stopRefresh);
    },
    retryAccessCheck() {
      this.updateRecentActionSummary("正在重新校验商家分析访问权限。");
      this.checkMerchantAccess();
    },
    openOperationAction(item) {
      if (!item || !item.url) {
        return;
      }
      this.updateRecentActionSummary(`正在进入${item.label}。`);
      if (item.openType === "switchTab") {
        uni.switchTab({
          url: item.url,
        });
        return;
      }
      uni.navigateTo({
        url: item.url,
      });
    },
    showExpireDialog() {
      const expireTime = this.merchantAccessInfo.expire_time || "未设置";
      uni.showModal({
        title: "商家服务已到期",
        content: `您的商家服务已于 ${expireTime} 到期，请先续费后再继续使用数据分析功能。`,
        confirmText: "去续费",
        cancelText: "返回",
        success: (res) => {
          if (res.confirm) {
            this.updateRecentActionSummary(
              "准备跳转续费中心，恢复经营分析能力。",
            );
            uni.navigateTo({
              url: "/pages/merchant/renew",
            });
            return;
          }
          this.updateRecentActionSummary("已取消续费，准备离开经营分析页。");
          this.leavePage();
        },
      });
    },
    leavePage() {
      const pages = getCurrentPages();
      if (pages.length > 1) {
        uni.navigateBack();
        return;
      }
      uni.switchTab({
        url: "/pages/app/my",
      });
    },
    isActiveRange(item) {
      if (item.type === "custom") {
        return this.isCustomRange;
      }
      if (item.type === "all") {
        return this.isAllRange;
      }
      return this.activeRangeType === "days" && this.currentDays === item.value;
    },
    changeRange(item) {
      if (item.type === "custom") {
        this.activeRangeType = "custom";
        this.updateRecentActionSummary("已切换到自定义时间段。");
        return;
      }
      if (item.type === "all") {
        if (this.isAllRange) {
          return;
        }
        this.activeRangeType = "all";
        this.updateRecentActionSummary("已切换到全部数据分析。");
        this.getAnalytics();
        return;
      }
      if (this.activeRangeType === "days" && this.currentDays === item.value) {
        return;
      }
      this.activeRangeType = "days";
      this.currentDays = item.value;
      this.updateRecentActionSummary(`已切换到近 ${item.value} 天分析。`);
      this.getAnalytics();
    },
    onCustomStartDateChange(event) {
      this.customRange.start_date = event.detail.value;
      if (
        this.customRange.end_date &&
        this.customRange.start_date > this.customRange.end_date
      ) {
        this.customRange.end_date = this.customRange.start_date;
      }
      this.updateRecentActionSummary(
        `已更新开始日期：${this.customRange.start_date}。`,
      );
    },
    onCustomEndDateChange(event) {
      this.customRange.end_date = event.detail.value;
      this.updateRecentActionSummary(
        `已更新结束日期：${this.customRange.end_date}。`,
      );
    },
    resetCustomRange() {
      const endDate = new Date();
      const startDate = new Date(endDate.getTime());
      startDate.setDate(startDate.getDate() - 6);
      this.customRange = {
        start_date: this.formatDate(startDate),
        end_date: this.formatDate(endDate),
      };
      this.updateRecentActionSummary(
        `已重置自定义时间段：${this.customRange.start_date} 至 ${this.customRange.end_date}。`,
      );
    },
    applyCustomRange() {
      if (!this.customRange.start_date || !this.customRange.end_date) {
        this.updateRecentActionSummary("自定义时间段应用失败：日期不完整。");
        uni.showToast({
          title: "请选择完整时间段",
          icon: "none",
        });
        return;
      }
      if (this.customRange.start_date > this.customRange.end_date) {
        this.updateRecentActionSummary(
          "自定义时间段应用失败：开始日期晚于结束日期。",
        );
        uni.showToast({
          title: "开始日期不能晚于结束日期",
          icon: "none",
        });
        return;
      }
      const rangeDays = this.calcRangeDays(
        this.customRange.start_date,
        this.customRange.end_date,
      );
      if (rangeDays > MAX_CUSTOM_RANGE_DAYS) {
        this.updateRecentActionSummary(
          `自定义时间段应用失败：超过 ${MAX_CUSTOM_RANGE_DAYS} 天。`,
        );
        uni.showToast({
          title: `最多可筛选 ${MAX_CUSTOM_RANGE_DAYS} 天`,
          icon: "none",
        });
        return;
      }
      this.activeRangeType = "custom";
      this.updateRecentActionSummary(
        `已应用自定义时间段：${this.customRange.start_date} 至 ${this.customRange.end_date}。`,
      );
      this.getAnalytics();
    },
    getAnalytics(stopRefresh = false) {
      uni.showLoading({
        title: "加载中",
        mask: true,
      });
      const params = this.isCustomRange
        ? {
            filter_type: "custom",
            start_date: this.customRange.start_date,
            end_date: this.customRange.end_date,
          }
        : this.isAllRange
          ? {
              filter_type: "all",
            }
          : {
              filter_type: "days",
              days: this.currentDays,
            };
      return api
        .getMerAnalytics(params)
        .then((res) => {
          const data = res.data || {};
          this.merchant = data.merchant || {};
          this.overview = data.overview || {};
          this.rangeSummary = data.range_summary || {};
          this.tradeComparison = data.trade_comparison || {};
          this.trend = data.trend || [];
          this.statusBreakdown = data.status_breakdown || [];
          this.topGoods = data.top_goods || [];
          if (this.rangeSummary.filter_type === "custom") {
            this.activeRangeType = "custom";
            this.customRange = {
              start_date:
                this.rangeSummary.start_date || this.customRange.start_date,
              end_date: this.rangeSummary.end_date || this.customRange.end_date,
            };
          } else if (this.rangeSummary.filter_type === "all") {
            this.activeRangeType = "all";
          } else if (Number(this.rangeSummary.days || 0) > 0) {
            this.activeRangeType = "days";
            this.currentDays = Number(
              this.rangeSummary.days || this.currentDays,
            );
          }
          this.updateRecentActionSummary(
            `经营分析已刷新：${this.comparisonPeriodText}，成交金额 ￥${this.formatMoney(this.rangeSummary.paid_amount)}。`,
          );
        })
        .catch(() => {
          this.updateRecentActionSummary("经营分析数据加载失败。");
          uni.showToast({
            title: "数据加载失败",
            icon: "none",
          });
        })
        .finally(() => {
          uni.hideLoading();
          if (stopRefresh) {
            uni.stopPullDownRefresh();
          }
        });
    },
    formatMoney(value) {
      return Number(value || 0).toFixed(2);
    },
    formatDate(date) {
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, "0");
      const day = String(date.getDate()).padStart(2, "0");
      return `${year}-${month}-${day}`;
    },
    calcRangeDays(startDate, endDate) {
      const start = new Date(startDate + "T00:00:00");
      const end = new Date(endDate + "T00:00:00");
      const diff = end.getTime() - start.getTime();
      if (diff < 0) {
        return 0;
      }
      return Math.floor(diff / 86400000) + 1;
    },
    getAmountWidth(amount) {
      const max = this.getMaxAmount();
      if (!max) {
        return "12%";
      }
      return Math.max((Number(amount || 0) / max) * 100, 12) + "%";
    },
    getMaxAmount() {
      let max = 0;
      for (let i = 0; i < this.trend.length; i++) {
        max = Math.max(
          max,
          Number(this.trend[i].paid_amount || 0),
          Number(this.trend[i].buy_amount || 0),
        );
      }
      return max;
    },
  },
};
</script>

<style lang="scss">
.analytics-page {
  min-height: 100vh;
  padding: 24rpx;
  background:
    radial-gradient(
      circle at top right,
      rgba(243, 143, 90, 0.22),
      transparent 32%
    ),
    linear-gradient(180deg, #f9f3eb 0%, #f3f6fa 42%, #edf2f6 100%);
}

.hero-card,
.section-card,
.custom-range-panel {
  background: rgba(255, 255, 255, 0.96);
  border-radius: 30rpx;
  box-shadow: 0 18rpx 42rpx rgba(21, 33, 52, 0.08);
}

.env-card {
  border: 1rpx solid rgba(228, 233, 240, 0.85);
}

.env-badge {
  padding: 10rpx 18rpx;
  border-radius: 999rpx;
  font-size: 22rpx;
  font-weight: 600;
}

.env-badge.is-prod {
  background: rgba(207, 90, 72, 0.12);
  color: #bf4a36;
}

.env-badge.is-test {
  background: rgba(21, 75, 114, 0.1);
  color: #1f5f85;
}

.env-url {
  margin-top: 12rpx;
  word-break: break-all;
  font-size: 22rpx;
  color: #758393;
}

.env-tip {
  margin-top: 12rpx;
  text-align: left;
}

.env-tip--strong {
  padding: 14rpx 16rpx;
  border-radius: 18rpx;
  background: rgba(21, 75, 114, 0.06);
  color: #35506b;
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
  background: rgba(21, 75, 114, 0.04);
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

.hero-card {
  padding: 32rpx;
  color: #ffffff;
  background:
    radial-gradient(
      circle at top right,
      rgba(255, 255, 255, 0.16),
      transparent 28%
    ),
    linear-gradient(135deg, #154b72 0%, #2b6a8d 48%, #ec8a57 100%);
}

.hero-main,
.section-head,
.status-top,
.goods-item,
.custom-range-head {
  display: flex;
  justify-content: space-between;
}

.hero-main {
  align-items: flex-start;
  gap: 20rpx;
}

.hero-title {
  font-size: 38rpx;
  font-weight: 700;
}

.hero-subtitle {
  margin-top: 12rpx;
  font-size: 24rpx;
  color: rgba(255, 255, 255, 0.82);
}

.hero-date {
  padding: 12rpx 18rpx;
  border-radius: 999rpx;
  background: rgba(255, 255, 255, 0.14);
  font-size: 22rpx;
  white-space: nowrap;
}

.hero-metrics {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 18rpx;
  margin-top: 30rpx;
}

.hero-metric {
  padding: 20rpx;
  border-radius: 22rpx;
  background: rgba(255, 255, 255, 0.12);
}

.metric-label {
  display: block;
  font-size: 22rpx;
  color: rgba(255, 255, 255, 0.82);
}

.metric-value {
  display: block;
  margin-top: 12rpx;
  font-size: 34rpx;
  font-weight: 700;
}

.range-tabs {
  display: flex;
  flex-wrap: wrap;
  gap: 16rpx;
  margin: 24rpx 0;
}

.range-tab {
  padding: 14rpx 24rpx;
  border-radius: 999rpx;
  background: rgba(21, 75, 114, 0.08);
  color: #536274;
  font-size: 24rpx;
}

.range-tab.active {
  background: #154b72;
  color: #ffffff;
  box-shadow: 0 10rpx 24rpx rgba(21, 75, 114, 0.2);
}

.custom-range-panel {
  margin-bottom: 20rpx;
  padding: 28rpx;
}

.custom-range-head {
  align-items: center;
  gap: 18rpx;
}

.custom-range-title {
  font-size: 30rpx;
  font-weight: 700;
  color: #162233;
}

.custom-range-tip {
  font-size: 22rpx;
  color: #7b8797;
}

.custom-range-row {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 18rpx;
  margin-top: 20rpx;
}

.custom-date-field {
  padding: 22rpx;
  border-radius: 24rpx;
  background: linear-gradient(180deg, #f7fafc 0%, #eef3f7 100%);
}

.custom-date-label {
  display: block;
  font-size: 22rpx;
  color: #7a8797;
}

.custom-date-value {
  display: block;
  margin-top: 12rpx;
  font-size: 28rpx;
  font-weight: 700;
  color: #192636;
}

.custom-range-actions {
  display: flex;
  gap: 18rpx;
  margin-top: 20rpx;
}

.custom-range-ghost,
.custom-range-submit {
  flex: 1;
  text-align: center;
  padding: 18rpx 0;
  border-radius: 999rpx;
  font-size: 24rpx;
  font-weight: 600;
}

.custom-range-ghost {
  background: rgba(21, 75, 114, 0.08);
  color: #4d6178;
}

.custom-range-submit {
  background: linear-gradient(135deg, #154b72 0%, #ec8a57 100%);
  color: #ffffff;
  box-shadow: 0 10rpx 24rpx rgba(21, 75, 114, 0.18);
}

.section-card {
  margin-top: 20rpx;
  padding: 28rpx;
}

.recent-action-card__desc {
  margin-top: 12rpx;
  text-align: left;
}

.operation-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 18rpx;
  margin-top: 22rpx;
}

.operation-item {
  padding: 24rpx;
  border-radius: 24rpx;
  background: linear-gradient(180deg, #f7fafc 0%, #eef3f7 100%);
}

.operation-item.is-release {
  background: linear-gradient(135deg, #fff4ec 0%, #ffe3d2 100%);
}

.operation-item.is-renew {
  background: linear-gradient(135deg, #fff3f0 0%, #ffdcd4 100%);
}

.operation-item.is-sell {
  background: linear-gradient(135deg, #eef6fb 0%, #e1ecf5 100%);
}

.operation-item.is-workbench {
  background: linear-gradient(135deg, #f6f8fb 0%, #edf1f5 100%);
}

.operation-item__title {
  font-size: 28rpx;
  font-weight: 700;
  color: #1a2c3f;
}

.operation-item__desc {
  margin-top: 10rpx;
  font-size: 22rpx;
  line-height: 1.6;
  color: #6b7889;
}

.warning-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20rpx;
  border: 1px solid rgba(227, 120, 52, 0.18);
  background: rgba(255, 248, 240, 0.96);
}

.warning-copy {
  flex: 1;
}

.warning-action {
  flex-shrink: 0;
  padding: 16rpx 24rpx;
  border-radius: 999rpx;
  color: #ffffff;
  font-size: 24rpx;
  background: linear-gradient(135deg, #db7d45 0%, #c95a2b 100%);
}

.section-title {
  font-size: 30rpx;
  font-weight: 700;
  color: #162233;
}

.section-tip {
  font-size: 22rpx;
  color: #7b8797;
}

.overview-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 18rpx;
  margin-top: 20rpx;
}

.overview-item {
  padding: 22rpx;
  border-radius: 24rpx;
  background: linear-gradient(180deg, #f7fafc 0%, #eef3f7 100%);
}

.overview-label {
  display: block;
  font-size: 22rpx;
  color: #7a8797;
}

.overview-value {
  display: block;
  margin-top: 14rpx;
  font-size: 30rpx;
  font-weight: 700;
  color: #192636;
}

.comparison-grid,
.today-comparison-grid,
.comparison-summary {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

.comparison-grid {
  gap: 18rpx;
  margin-top: 22rpx;
}

.today-comparison-grid {
  gap: 16rpx;
  margin-top: 22rpx;
}

.comparison-summary {
  gap: 16rpx;
  margin-top: 18rpx;
}

.today-comparison-item,
.comparison-summary-item {
  padding: 20rpx 22rpx;
  border-radius: 20rpx;
  background: linear-gradient(180deg, #f7fafc 0%, #eef3f7 100%);
}

.comparison-summary-item.full {
  grid-column: span 2;
}

.today-comparison-meta {
  display: block;
  margin-top: 10rpx;
  font-size: 22rpx;
  color: #667487;
}

.comparison-item {
  padding: 24rpx;
  border-radius: 24rpx;
  color: #ffffff;
}

.comparison-item.buy {
  background: linear-gradient(135deg, #5b7c99 0%, #43627d 100%);
}

.comparison-item.sale {
  background: linear-gradient(135deg, #ec8a57 0%, #cf5a48 100%);
}

.comparison-label,
.comparison-meta,
.summary-key {
  display: block;
  font-size: 22rpx;
}

.comparison-label,
.comparison-meta {
  color: rgba(255, 255, 255, 0.82);
}

.comparison-value {
  display: block;
  margin-top: 14rpx;
  font-size: 34rpx;
  font-weight: 700;
}

.comparison-meta {
  margin-top: 10rpx;
}

.summary-key {
  color: #718093;
}

.summary-value {
  display: block;
  margin-top: 10rpx;
  font-size: 28rpx;
  font-weight: 700;
  color: #162233;
  line-height: 1.5;
}

.summary-value.is-buy {
  color: #4c647f;
}

.summary-value.is-sale {
  color: #cf5a48;
}

.summary-value.is-neutral {
  color: #1c5c88;
}

.trend-list,
.status-list,
.goods-list {
  margin-top: 22rpx;
}

.trend-row + .trend-row,
.status-row + .status-row,
.goods-item + .goods-item {
  margin-top: 18rpx;
}

.trend-row {
  display: flex;
  gap: 20rpx;
  align-items: center;
}

.trend-label {
  width: 88rpx;
  font-size: 24rpx;
  color: #5a6778;
}

.trend-main {
  flex: 1;
}

.trend-block + .trend-block {
  margin-top: 14rpx;
}

.trend-track {
  overflow: hidden;
  height: 16rpx;
  border-radius: 999rpx;
  background: #e8edf2;
}

.trend-bar {
  height: 100%;
  border-radius: 999rpx;
}

.buy-bar {
  background: linear-gradient(90deg, #6b89a3 0%, #43627d 100%);
}

.sale-overlay {
  background: linear-gradient(90deg, #f1a774 0%, #cf5a48 100%);
}

.trend-meta {
  display: flex;
  justify-content: space-between;
  margin-top: 10rpx;
  font-size: 22rpx;
  color: #647184;
}

.status-row {
  padding: 20rpx 22rpx;
  border-radius: 20rpx;
  background: linear-gradient(180deg, #f7fafc 0%, #eef3f7 100%);
}

.status-name,
.status-value {
  font-size: 24rpx;
  color: #243140;
}

.status-track {
  overflow: hidden;
  height: 16rpx;
  margin-top: 14rpx;
  border-radius: 999rpx;
  background: #e8edf2;
}

.status-bar {
  height: 100%;
  border-radius: 999rpx;
  background: linear-gradient(90deg, #154b72 0%, #ec8a57 100%);
}

.goods-item {
  align-items: center;
  gap: 18rpx;
  padding: 18rpx 0;
}

.goods-rank {
  width: 44rpx;
  text-align: center;
  font-size: 28rpx;
  font-weight: 700;
  color: #1a344d;
}

.goods-image {
  width: 92rpx;
  height: 92rpx;
  border-radius: 20rpx;
  background: #eef3f7;
}

.goods-info {
  flex: 1;
}

.goods-title {
  font-size: 26rpx;
  font-weight: 700;
  color: #1b2d40;
}

.goods-meta {
  margin-top: 8rpx;
  font-size: 22rpx;
  color: #7a8797;
}

.goods-amount {
  font-size: 28rpx;
  font-weight: 700;
  color: #cf5a48;
}

.empty-state {
  margin-top: 22rpx;
  padding: 28rpx;
  border-radius: 24rpx;
  background: linear-gradient(180deg, #f7fafc 0%, #eef3f7 100%);
  font-size: 24rpx;
  color: #7a8797;
  text-align: center;
}
</style>

