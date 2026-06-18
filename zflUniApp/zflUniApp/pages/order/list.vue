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
    <view class="order-page">
      <view class="hero-card">
        <view class="hero-main">
          <view>
            <view class="hero-title">我的订单</view>
            <view class="hero-subtitle">查看购买记录、付款状态和售后进度</view>
          </view>
          <view class="hero-date">共 {{ count || 0 }} 单</view>
        </view>
      </view>

      <view class="env-card">
        <view class="env-card__head">
          <view class="env-card__title">当前环境</view>
          <view
            class="env-card__badge"
            :class="currentEnvInfo.is_prod ? 'is-prod' : 'is-test'"
          >
            {{ currentEnvInfo.label }}
          </view>
        </view>
        <view class="env-card__desc">{{ envDescription }}</view>
        <view class="env-card__meta">
          <text class="env-card__tag">{{ currentTabText }}</text>
          <text class="env-card__tag">{{ orderCountText }}</text>
          <text class="env-card__tag">{{ envIsolationText }}</text>
          <text class="env-card__tag">{{ envIsolationStatusText }}</text>
          <text class="env-card__tag">{{ envReleaseStageText }}</text>
        </view>
        <view class="env-card__note">{{ envActionHint }}</view>
        <view class="env-card__note env-card__note--strong">{{
          envReleaseHint
        }}</view>
        <view v-if="envRiskList.length" class="env-card__risk-list">
          <view
            v-for="item in envRiskList"
            :key="item"
            class="env-card__risk-item"
            >{{ item }}</view
          >
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
        <view class="env-card__url">{{
          currentEnvInfo.api_root_url || "未配置接口地址"
        }}</view>
      </view>

      <view class="review-card">
        <view class="review-card__title">当前操作复核</view>
        <view class="review-card__desc">{{ operationReviewHint }}</view>
        <view class="review-card__tags">
          <text class="review-card__tag">{{ currentTabText }}</text>
          <text class="review-card__tag">{{ orderCountText }}</text>
          <text class="review-card__tag">{{ pendingActionHint }}</text>
        </view>
      </view>

      <view class="ops-card">
        <view class="ops-card__head">
          <view>
            <view class="ops-card__title">订单运营速览</view>
            <view class="ops-card__desc">{{ orderOpsHint }}</view>
          </view>
          <view class="ops-card__badge" :class="orderOpsBadgeClass">{{
            orderOpsBadgeText
          }}</view>
        </view>
        <view class="ops-card__tags">
          <text class="ops-card__tag">{{ orderOpsTags.pendingPay }}</text>
          <text class="ops-card__tag">{{ orderOpsTags.pendingPickup }}</text>
          <text class="ops-card__tag">{{ orderOpsTags.pendingReceipt }}</text>
          <text class="ops-card__tag">{{ orderOpsTags.afterSales }}</text>
        </view>
        <view v-if="orderOpsRiskText" class="ops-card__risk">{{
          orderOpsRiskText
        }}</view>
        <view class="ops-card__actions">
          <view class="ops-card__action" @tap="applyStatusFilter(0, '待支付')"
            >待支付</view
          >
          <view
            class="ops-card__action"
            @tap="applyStatusFilter(1, '待发货/核销')"
            >待发货</view
          >
          <view class="ops-card__action" @tap="applyStatusFilter(5, '售后')"
            >售后</view
          >
          <view class="ops-card__action" @tap="retryLoad">刷新列表</view>
        </view>
      </view>

      <view v-if="recentActionSummary" class="recent-card">
        <view class="recent-card__title">最近操作</view>
        <view class="recent-card__desc">{{ recentActionSummary }}</view>
      </view>

      <view class="ops-card">
        <view class="ops-card__head">
          <view>
            <view class="ops-card__title">尾链路跟进</view>
            <view class="ops-card__desc">{{ orderTailHint }}</view>
          </view>
          <view class="ops-card__badge" :class="orderTailBadgeClass">{{
            orderTailBadgeText
          }}</view>
        </view>
        <view class="ops-card__tags">
          <text
            class="ops-card__tag"
            v-for="item in orderTailTags"
            :key="item"
            >{{ item }}</text
          >
        </view>
        <view class="ops-card__risk">{{ orderTailRiskText }}</view>
      </view>

      <view v-if="recentOrderRuntimeCard" class="result-card">
        <view class="result-card__head">
          <view>
            <view class="result-card__title">{{
              recentOrderRuntimeCard.actionTitle
            }}</view>
            <view class="result-card__time">{{
              recentOrderRuntimeCard.successAt
            }}</view>
          </view>
          <view class="result-card__badge">{{
            recentOrderRuntimeCard.envLabel
          }}</view>
        </view>
        <view class="result-card__desc">{{
          recentOrderRuntimeCard.actionDesc
        }}</view>
        <view class="result-card__meta">
          <text class="result-card__tag">{{
            recentOrderRuntimeCard.payTypeText
          }}</text>
          <text class="result-card__tag">{{
            recentOrderRuntimeCard.deliveryTypeText
          }}</text>
          <text class="result-card__tag">{{
            recentOrderRuntimeCard.targetStatusText
          }}</text>
          <text class="result-card__tag">{{
            recentOrderRuntimeCard.envTag
          }}</text>
        </view>
        <view class="result-card__summary">
          <text>商品 {{ recentOrderRuntimeCard.goodsCount }} 件</text>
          <text>金额 ￥{{ recentOrderRuntimeCard.totalPrice }}</text>
        </view>
        <view
          v-if="recentOrderRuntimeCard.orderNo"
          class="result-card__order-no"
          >订单号：{{ recentOrderRuntimeCard.orderNo }}</view
        >
        <view
          v-if="
            recentOrderRuntimeCard.merchantTitles &&
            recentOrderRuntimeCard.merchantTitles.length
          "
          class="result-card__merchants"
        >
          {{ recentOrderRuntimeCard.merchantTitles.join(" / ") }}
        </view>
        <view class="result-card__followup">
          <view class="result-card__followup-title">建议下一步</view>
          <view class="result-card__followup-desc">{{
            recentOrderFollowupHint
          }}</view>
        </view>
        <view class="result-card__actions">
          <button class="action-btn light" @tap="openRecentOrderNextAction">
            继续处理
          </button>
          <button class="action-btn primary" @tap="focusRecentOrderRuntime">
            查看对应订单
          </button>
          <button class="action-btn light" @tap="clearRecentOrderRuntime">
            清除提示
          </button>
        </view>
      </view>

      <scroll-view
        v-if="params.order_status && params.order_status.length"
        scroll-x
        class="tabs-scroll"
      >
        <view class="tabs-row">
          <view
            v-for="(item, index) in params.order_status"
            :key="index"
            class="tab-chip"
            :class="{ active: item.value == tab_cur }"
            @tap="tabSelect"
            :data-id="item.value"
            :data-index="item.index"
          >
            {{ item.label }}
          </view>
        </view>
      </scroll-view>

      <view v-if="list.length" class="order-list">
        <view v-for="(item, index) in list" :key="index" class="order-card">
          <view class="order-head">
            <view class="merchant-box">
              <view class="merchant-icon">
                <text class="cuIcon-shopfill"></text>
              </view>
              <view class="merchant-info">
                <view class="merchant-title">{{
                  merchantTitleText(item.merchant_title, "平台订单")
                }}</view>
                <view class="merchant-order-no"
                  >订单号：{{ item.order_no }}</view
                >
              </view>
            </view>
            <text class="status-text">{{ resolveOrderStatusText(item) }}</text>
          </view>

          <view
            v-for="(goodsItem, goodsIndex) in item.detaileds"
            :key="goodsIndex"
            class="goods-card"
          >
            <image
              class="goods-image"
              :src="goodsImage(goodsItem)"
              mode="aspectFill"
            ></image>
            <view class="goods-body">
              <view class="goods-title">{{ goodsItem.goods.title }}</view>
              <view class="goods-spec">{{ goodsItem.goods.spec }}</view>
              <view
                v-if="goodsItem.labels && goodsItem.labels.length"
                class="goods-tags"
              >
                <text
                  v-for="(label, labelIndex) in goodsItem.labels"
                  :key="labelIndex"
                  class="goods-tag"
                  >{{ label }}</text
                >
              </view>
              <view class="goods-meta">
                <text class="goods-price"
                  >￥{{ formatMoney(goodsItem.price) }}</text
                >
                <text class="goods-qty"
                  >x{{ goodsItem.quantity
                  }}{{
                    goodsItem.goods.unit ? goodsItem.goods.unit : "件"
                  }}</text
                >
              </view>
            </view>
          </view>

          <view class="order-summary">
            <text class="summary-time">{{ item.create_time }}</text>
            <text class="summary-total"
              >共 {{ item.total_num }} 件 · 合计 ￥{{
                formatMoney(item.total_price)
              }}</text
            >
          </view>

          <view class="action-row">
            <button
              class="action-btn light"
              v-if="item.status == 0"
              @tap="cancelOrder(item.id)"
            >
              取消订单
            </button>
            <button
              class="action-btn primary"
              v-if="item.status == 0 && item.pay_type == 1"
              @tap="payOrder(item.id)"
            >
              立即支付
            </button>
            <button
              class="action-btn light"
              v-if="item.status == 1 && item.delivery_type == 2"
              @tap="getCode(item.id)"
            >
              查看取件码
            </button>
            <button
              class="action-btn light"
              v-if="item.status == 2 && item.delivery_type == 1"
              @tap="jump('/pages/order/logistics?id=' + item.id)"
            >
              查看物流
            </button>
            <button
              class="action-btn primary"
              v-if="item.status == 2 && item.delivery_type == 1"
              @tap="confirmReceipt(item.id)"
            >
              确认收货
            </button>
            <button
              class="action-btn primary"
              v-if="item.status == 3"
              @tap="jump('/pages/order/evaluate?id=' + item.id)"
            >
              订单评价
            </button>
            <button
              class="action-btn light"
              v-if="item.status == 4"
              @tap="jump('/pages/order/service?id=' + item.id)"
            >
              申请售后
            </button>
            <button
              class="action-btn light"
              v-if="item.status == 5"
              @tap="jump('/pages/order/service?id=' + item.id)"
            >
              售后详情
            </button>
          </view>
        </view>
      </view>

      <view v-else-if="loadError" class="empty-state">
        <view class="empty-title">订单数据加载失败</view>
        <view class="empty-text">{{ loadError }}</view>
        <view class="action-row">
          <button class="action-btn primary" @tap="retryLoad">重新加载</button>
        </view>
      </view>
      <view v-else-if="!isLoad" class="empty-state">当前条件下暂无订单</view>
      <view class="loading-box" v-if="isLoad">加载中...</view>
      <view class="page-bottom-space"></view>
    </view>
  </view>
</template>

<script>
import api from "@/api";
import { maskMerchantTitle } from "@/utils/desensitize.js";
import { getPaymentErrorMessage, requestOrderPayment } from "@/utils/payment.js";
import {
  getCurrentEnvInfo,
  getEnvIsolationHealth,
  getProfileReadinessList,
} from "@/utils/env-runtime.js";
import {
  buildEnvConfirmText,
  getEnvIsolationHint,
  getEnvIsolationTag,
  getEnvReleaseHint,
  getEnvReleaseStageText,
} from "@/utils/env-risk.js";
import cache from "@/utils/cache.js";

const RECENT_ORDER_RUNTIME_KEY = "recent_order_runtime_card";

export default {
  data() {
    return {
      tab_cur: -1,
      tab_scroll: 0,
      isLoad: false,
      loadError: "",
      params: {},
      query: {
        page: 1,
        limit: 10,
        status: null,
      },
      list: [],
      count: 0,
      pages: 0,
      isBtn: false,
      recentOrderRuntimeCard: null,
      recentActionSummary: "",
    };
  },
  computed: {
    currentEnvInfo() {
      return getCurrentEnvInfo();
    },
    envDescription() {
      return this.currentEnvInfo.is_prod
        ? "当前为正式环境，取消订单、支付、确认收货都会直接影响线上订单数据。"
        : "当前为非正式环境，适合做订单支付、取消、收货和售后流程验收。";
    },
    currentTabText() {
      const current = (this.params.order_status || []).find(
        (item) => item.value == this.tab_cur,
      );
      return current ? `当前筛选：${current.label}` : "当前筛选：全部订单";
    },
    orderCountText() {
      return `当前订单：${this.count || 0} 单`;
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
    profileReadinessList() {
      return getProfileReadinessList();
    },
    envActionHint() {
      return this.currentEnvInfo.is_prod
        ? `支付、取消、确认收货和售后入口都是真实动作，建议逐单核对后再操作。${getEnvIsolationHint(this.currentEnvInfo)}`
        : `建议在当前环境逐项验证支付、取消、收货、物流和售后入口的回显。${getEnvIsolationHint(this.currentEnvInfo)}`;
    },
    pendingActionHint() {
      if (this.tab_cur == 0) return "当前重点：待支付 / 可取消";
      if (this.tab_cur == 1) return "当前重点：待发货 / 待核销";
      if (this.tab_cur == 2) return "当前重点：可查看物流 / 确认收货";
      if (this.tab_cur == 5) return "当前重点：售后处理中";
      return "当前重点：核对订单状态和后续动作";
    },
    operationReviewHint() {
      return this.currentEnvInfo.is_prod
        ? "当前为正式环境，取消订单、支付和确认收货都会直接影响真实订单状态。"
        : "当前为非正式环境，建议在这里重点回归取消、支付、收货和售后入口的回显。";
    },
    pendingPayCount() {
      return this.list.filter((item) => Number(item.status) === 0).length;
    },
    pendingPickupCount() {
      return this.list.filter(
        (item) => Number(item.status) === 1 && Number(item.delivery_type) === 2,
      ).length;
    },
    pendingReceiptCount() {
      return this.list.filter((item) => Number(item.status) === 2).length;
    },
    afterSalesCount() {
      return this.list.filter(
        (item) => Number(item.status) === 4 || Number(item.status) === 5,
      ).length;
    },
    orderOpsBadgeText() {
      if (this.loadError) {
        return "待重载";
      }
      if (
        this.pendingPayCount > 0 ||
        this.pendingPickupCount > 0 ||
        this.afterSalesCount > 0
      ) {
        return "需跟进";
      }
      return "平稳";
    },
    orderOpsBadgeClass() {
      if (this.loadError) {
        return "is-risk";
      }
      if (
        this.pendingPayCount > 0 ||
        this.pendingPickupCount > 0 ||
        this.afterSalesCount > 0
      ) {
        return "is-pending";
      }
      return "is-success";
    },
    orderOpsHint() {
      if (this.loadError) {
        return "当前列表加载失败，建议先刷新列表再继续处理订单。";
      }
      if (this.count <= 0) {
        return "当前筛选下暂无订单，可切换状态标签查看其它订单。";
      }
      return "这里会汇总当前列表里的待支付、待核销、待收货和售后订单，方便你快速切换处理。";
    },
    orderOpsTags() {
      return {
        pendingPay: `待支付：${this.pendingPayCount} 单`,
        pendingPickup: `待核销：${this.pendingPickupCount} 单`,
        pendingReceipt: `待收货：${this.pendingReceiptCount} 单`,
        afterSales: `售后中：${this.afterSalesCount} 单`,
      };
    },
    orderOpsRiskText() {
      if (this.loadError) {
        return "订单列表当前不可用，先刷新确认接口和网络状态。";
      }
      if (this.pendingPayCount > 0) {
        return "存在待支付订单，建议优先确认是否需要继续支付或取消，避免长时间挂单。";
      }
      if (this.pendingPickupCount > 0) {
        return "存在待核销订单，建议尽快查看取件码并跟进核销进度。";
      }
      if (this.afterSalesCount > 0) {
        return "当前有售后订单，建议继续跟进售后处理和结果回显。";
      }
      return "";
    },
    recentOrderFollowupHint() {
      if (!this.recentOrderRuntimeCard) {
        return "";
      }
      if (this.recentOrderRuntimeCard.targetStatus === 0) {
        return "当前订单仍在待审核阶段，建议继续关注审核结果和后续处理状态。";
      }
      if (
        this.recentOrderRuntimeCard.targetStatus === 1 &&
        this.recentOrderRuntimeCard.deliveryTypeText === "门店自提"
      ) {
        return "当前订单已进入待核销阶段，建议优先查看取件码并核对核销进度。";
      }
      if (this.recentOrderRuntimeCard.targetStatus === 1) {
        return "当前订单已进入待发货阶段，建议继续查看物流、发货和确认收货链路。";
      }
      return "建议先定位到对应订单状态，再继续处理后续动作。";
    },
    deliveredCount() {
      return this.list.filter((item) => Number(item.status) === 2).length;
    },
    evaluatedCount() {
      return this.list.filter((item) => Number(item.status) === 3).length;
    },
    finishedCount() {
      return this.list.filter((item) => Number(item.status) === 4).length;
    },
    orderTailBadgeText() {
      if (this.loadError) {
        return "待重载";
      }
      if (this.afterSalesCount > 0 || this.deliveredCount > 0) {
        return "需跟进";
      }
      return "已收口";
    },
    orderTailBadgeClass() {
      if (this.loadError) {
        return "is-risk";
      }
      if (this.afterSalesCount > 0 || this.deliveredCount > 0) {
        return "is-pending";
      }
      return "is-success";
    },
    orderTailHint() {
      if (this.loadError) {
        return "当前订单尾链路数据未成功加载，建议先刷新列表再继续判断。";
      }
      if (!this.count) {
        return "当前筛选下暂无订单，可切换其它标签继续查看订单尾链路状态。";
      }
      return "这里重点跟进待收货、待评价、已完成和售后中的订单，避免订单停在尾链路无人处理。";
    },
    orderTailTags() {
      return [
        `待收货：${this.deliveredCount} 单`,
        `待评价：${this.evaluatedCount} 单`,
        `已完成：${this.finishedCount} 单`,
        `售后中：${this.afterSalesCount} 单`,
      ];
    },
    orderTailRiskText() {
      if (this.loadError) {
        return "订单尾链路当前不可用，先恢复列表加载，再做收货、评价和售后跟进。";
      }
      if (this.deliveredCount > 0) {
        return "当前存在待收货订单，建议优先核对物流、收货和评价承接，避免订单长期停在配送尾部。";
      }
      if (this.afterSalesCount > 0) {
        return "当前存在售后订单，建议继续跟进售后处理结果和回显，避免异常订单长时间挂起。";
      }
      return this.currentEnvInfo.is_prod
        ? "当前为正式环境，尾链路动作会直接影响真实订单状态，请以订单状态回显为准继续处理。"
        : "当前为非正式环境，建议继续联调收货、评价、售后和物流页之间的回跳承接。";
    },
  },
  onLoad(options) {
    if (options.status) {
      this.query.status = options.status;
    }
    this.loadRecentOrderRuntime();
    this.retryLoad();
  },
  onShow() {
    this.loadRecentOrderRuntime();
  },
  onReachBottom() {
    if (this.query.page < this.pages) {
      this.query.page++;
      this.getList();
    }
  },
  methods: {
    saveRecentOrderRuntime(payload = {}) {
      cache.set(
        RECENT_ORDER_RUNTIME_KEY,
        {
          envLabel: this.currentEnvInfo.label,
          envTag: this.envIsolationText,
          successAt: new Date().toLocaleString("zh-CN", { hour12: false }),
          targetStatus: payload.targetStatus,
          targetStatusText: payload.targetStatusText,
          actionType: payload.actionType,
          actionTitle: payload.actionTitle,
          actionDesc: payload.actionDesc,
          totalPrice: this.formatMoney(payload.totalPrice),
          goodsCount: Number(payload.goodsCount || 0),
          merchantTitles: payload.merchantTitles || [],
          payTypeText: payload.payTypeText || "在线支付",
          deliveryTypeText: payload.deliveryTypeText || "待确认",
          orderId: payload.orderId || "",
          orderNo: payload.orderNo || "",
        },
        7200,
      );
      this.loadRecentOrderRuntime();
    },
    updateRecentActionSummary(summary) {
      this.recentActionSummary = summary;
    },
    loadRecentOrderRuntime() {
      this.recentOrderRuntimeCard =
        cache.get(RECENT_ORDER_RUNTIME_KEY, null) || null;
    },
    clearRecentOrderRuntime() {
      cache.remove(RECENT_ORDER_RUNTIME_KEY);
      this.recentOrderRuntimeCard = null;
      this.updateRecentActionSummary(
        "已清除最近一次订单结果提示，可继续按当前筛选查看订单。",
      );
    },
    focusRecentOrderRuntime() {
      if (!this.recentOrderRuntimeCard) return;
      const targetStatus = this.recentOrderRuntimeCard.targetStatus;
      this.updateRecentActionSummary(
        `已定位到最近一次订单结果，准备切换到${this.recentOrderRuntimeCard.targetStatusText || "对应状态"}。`,
      );
      if (
        targetStatus !== undefined &&
        targetStatus !== null &&
        this.query.status != targetStatus
      ) {
        this.query.status = targetStatus;
        this.tab_cur = targetStatus;
        this.query.page = 1;
        this.list = [];
        this.getList();
      }
      uni.pageScrollTo({ scrollTop: 0, duration: 0 });
    },
    openRecentOrderNextAction() {
      if (!this.recentOrderRuntimeCard) {
        return;
      }
      this.updateRecentActionSummary(
        `正在继续处理最近订单：${this.recentOrderRuntimeCard.orderNo || "最近一单"}。`,
      );
      this.focusRecentOrderRuntime();
    },
    goodsImage(goodsItem) {
      if (
        goodsItem &&
        goodsItem.goods &&
        goodsItem.goods.image &&
        goodsItem.goods.image.file_url
      ) {
        return goodsItem.goods.image.file_url;
      }
      return "";
    },
    merchantTitleText(value, fallback = "平台订单") {
      return maskMerchantTitle(value, fallback);
    },
    confirmReceipt(id) {
      if (this.isBtn) return false;
      const currentOrder = this.list.find((item) => item.id == id) || {};
      this.updateRecentActionSummary(
        `准备确认收货：订单 ${currentOrder.order_no || id}。`,
      );
      uni.showModal({
        title: "温馨提示",
        showCancel: true,
        content: buildEnvConfirmText(this.currentEnvInfo, {
          prod: "当前为正式环境，确定已收到货物并确认收货吗？确认后会直接变更真实订单状态。",
          test: "确定继续走确认收货流程吗？",
        }),
        success: (e) => {
          if (!e.confirm) return;
          this.isBtn = true;
          api
            .confirmReceipt({ id })
            .then(() => {
              this.updateRecentActionSummary(
                `已确认收货：订单 ${currentOrder.order_no || id}，准备进入评价页。`,
              );
              uni.showToast({
                icon: "none",
                position: "bottom",
                title: "操作成功",
              });
              this.query.page = 1;
              this.getList();
              this.jump("/pages/order/evaluate?id=" + id);
            })
            .catch((error) => {
              this.showActionError(error, "确认收货失败，请稍后重试");
            })
            .finally(() => {
              this.isBtn = false;
            });
        },
      });
    },
    getCode(id) {
      if (this.isBtn) return false;
      const currentOrder = this.list.find((item) => item.id == id) || {};
      this.updateRecentActionSummary(
        `正在查看取件码：订单 ${currentOrder.order_no || id}。`,
      );
      this.isBtn = true;
      api
        .getOrderCode({ id })
        .then((res) => {
          uni.showModal({
            title: "取件码：" + res.data,
            showCancel: false,
          });
        })
        .catch((error) => {
          this.showActionError(error, "取件码加载失败，请稍后重试");
        })
        .finally(() => {
          this.isBtn = false;
        });
    },
    jump(url) {
      if (url.indexOf("/pages/order/logistics") === 0) {
        this.updateRecentActionSummary("正在进入物流详情，核对配送轨迹。");
      } else if (url.indexOf("/pages/order/evaluate") === 0) {
        this.updateRecentActionSummary("正在进入订单评价页。");
      } else if (url.indexOf("/pages/order/service") === 0) {
        this.updateRecentActionSummary("正在进入售后处理页。");
      }
      uni.navigateTo({ url });
    },
    resolveOrderStatusText(item) {
      if (Number(item?.status) === 5) {
        if (Number(item?.refund_status) === 1) {
          return "售后待审核";
        }
        if (Number(item?.refund_status) === 2) {
          if (Number(item?.refund_type) === 2) {
            return item?.refund_express ? "待平台收货" : "待退货";
          }
          return "待退款";
        }
        if (Number(item?.refund_status) === 3) {
          return "售后已拒绝";
        }
        if (Number(item?.refund_status) === 4) {
          return "售后退款中";
        }
        return "售后处理中";
      }
      return item?.status_title || "订单处理中";
    },
    showActionError(error, fallback = "操作失败，请稍后重试") {
      uni.showToast({
        icon: "none",
        title: getPaymentErrorMessage(error, fallback),
      });
    },
    payOrder(id) {
      if (this.isBtn) return false;
      const currentOrder = this.list.find((item) => item.id == id) || {};
      this.updateRecentActionSummary(
        `准备发起支付：订单 ${currentOrder.order_no || id}。`,
      );
      uni.showModal({
        title: "发起支付",
        content: buildEnvConfirmText(this.currentEnvInfo, {
          prod: "当前为正式环境，将发起真实支付流程，确认继续吗？",
          test: "将发起支付联调流程，确认继续吗？",
        }),
        success: (modalRes) => {
          if (!modalRes.confirm) return;
          this.isBtn = true;
          api
            .payOrder({ id })
            .then((res) => {
              const orderInfo = res.data;
              requestOrderPayment(orderInfo)
                .then(() => {
                  this.saveRecentOrderRuntime({
                    actionType: "pay_success",
                    actionTitle: "支付成功，订单已进入待发货/待核销流程",
                    actionDesc:
                      currentOrder.delivery_type == 2
                        ? "可以继续查看取件码和核销进度。"
                        : "可以继续跟进发货、物流和确认收货流程。",
                    targetStatus: 1,
                    targetStatusText:
                      currentOrder.delivery_type == 2 ? "待核销" : "待发货",
                    totalPrice: currentOrder.total_price,
                    goodsCount: currentOrder.total_num,
                    merchantTitles: currentOrder.merchant_title
                      ? [
                          this.merchantTitleText(
                            currentOrder.merchant_title,
                            "平台订单",
                          ),
                        ]
                      : [],
                    payTypeText: "在线支付",
                    deliveryTypeText:
                      currentOrder.delivery_type == 2 ? "门店自提" : "快递配送",
                    orderId: currentOrder.id || id,
                    orderNo: currentOrder.order_no,
                  });
                  this.updateRecentActionSummary(
                    `支付成功：订单 ${currentOrder.order_no || id} 已进入${currentOrder.delivery_type == 2 ? "待核销" : "待发货"}。`,
                  );
                  uni.showModal({
                    title: "温馨提示",
                    showCancel: false,
                    content: "支付成功",
                    success: (nextRes) => {
                      if (nextRes.confirm) {
                        this.query.status = 1;
                        this.tab_cur = 1;
                        this.query.page = 1;
                        this.getList();
                      }
                    },
                  });
                })
                .catch((e) => {
                  this.updateRecentActionSummary(
                    `支付未完成：订单 ${currentOrder.order_no || id}，原因 ${getPaymentErrorMessage(e, "未知")}。`,
                  );
                  this.showActionError(e, "支付暂时无法完成，请稍后重试");
                });
            })
            .catch((error) => {
              this.showActionError(error, "支付发起失败，请稍后重试");
            })
            .finally(() => {
              this.isBtn = false;
            });
        },
      });
    },
    cancelOrder(id) {
      if (this.isBtn) return false;
      const currentOrder = this.list.find((item) => item.id == id) || {};
      this.updateRecentActionSummary(
        `准备取消订单：${currentOrder.order_no || id}。`,
      );
      uni.showModal({
        title: "温馨提示",
        showCancel: true,
        content: buildEnvConfirmText(this.currentEnvInfo, {
          prod: "当前为正式环境，确定取消该订单吗？取消后会直接影响真实订单数据。",
          test: "确定继续测试取消订单吗？",
        }),
        success: (e) => {
          if (!e.confirm) return;
          this.isBtn = true;
          api
            .cancelOrder({ id })
            .then(() => {
              this.updateRecentActionSummary(
                `已取消订单：${currentOrder.order_no || id}。`,
              );
              uni.showToast({
                icon: "none",
                position: "bottom",
                title: "取消成功",
              });
              this.query.page = 1;
              this.getList();
            })
            .catch((error) => {
              this.showActionError(error, "取消订单失败，请稍后重试");
            })
            .finally(() => {
              this.isBtn = false;
            });
        },
      });
    },
    getList() {
      this.isLoad = true;
      api
        .getOrderList(this.query)
        .then((res) => {
          if (this.query.page == 1) this.list = res.data.list;
          else this.list = this.list.concat(res.data.list);
          this.count = res.data.count;
          this.pages = res.data.pages;
          this.loadError = "";
          this.updateRecentActionSummary(
            `订单列表已刷新：${this.currentTabText.replace("当前筛选：", "")}，共 ${res.data.count || 0} 单。`,
          );
        })
        .catch(() => {
          if (this.query.page === 1) {
            this.list = [];
            this.count = 0;
            this.pages = 0;
            this.loadError = "订单列表暂时无法加载，请稍后重试";
            this.updateRecentActionSummary(
              "订单列表加载失败，请重试并确认当前接口环境。",
            );
          }
        })
        .finally(() => {
          this.isLoad = false;
        });
    },
    getParams() {
      api
        .getOrderParams({})
        .then((res) => {
          this.params = res.data;
          if (this.query.status >= -1) {
            for (let i = 0; i < this.params.order_status.length; i++) {
              if (this.params.order_status[i].value == this.query.status) {
                this.tab_cur = this.params.order_status[i].value;
                this.tab_scroll = (i - 1) * 60;
              }
            }
          }
        })
        .catch(() => {
          if (!this.list.length) {
            this.loadError = "订单筛选参数暂时无法加载，请稍后重试";
          }
        });
    },
    retryLoad() {
      this.loadError = "";
      this.query.page = 1;
      this.list = [];
      this.updateRecentActionSummary("正在重新加载订单筛选和列表数据。");
      this.getParams();
      this.getList();
    },
    applyStatusFilter(status, label = "订单") {
      this.tab_cur = status;
      this.query.status = status;
      this.query.page = 1;
      this.list = [];
      uni.pageScrollTo({ scrollTop: 0, duration: 0 });
      this.updateRecentActionSummary(`已切换到${label}筛选。`);
      this.getList();
    },
    tabSelect(e) {
      const status = e.currentTarget.dataset.id;
      const current = (this.params.order_status || []).find(
        (item) => item.value == status,
      );
      this.tab_cur = status;
      uni.pageScrollTo({ scrollTop: 0, duration: 0 });
      this.query.status = status;
      this.query.page = 1;
      this.list = [];
      this.updateRecentActionSummary(
        `已切换筛选：${current ? current.label : "全部订单"}。`,
      );
      this.getList();
    },
    formatMoney(value) {
      return Number(value || 0).toFixed(2);
    },
  },
};
</script>

<style scoped lang="scss">
.order-page {
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
.order-card,
.env-card {
  background: rgba(255, 255, 255, 0.96);
  border-radius: 30rpx;
  box-shadow: 0 18rpx 42rpx rgba(21, 33, 52, 0.08);
}
.result-card {
  margin-top: 20rpx;
  padding: 24rpx 28rpx;
  background: linear-gradient(
    135deg,
    rgba(21, 75, 114, 0.96) 0%,
    rgba(43, 106, 141, 0.94) 52%,
    rgba(236, 138, 87, 0.94) 100%
  );
  border-radius: 30rpx;
  box-shadow: 0 18rpx 42rpx rgba(21, 33, 52, 0.12);
  color: #ffffff;
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
.review-card {
  margin-top: 20rpx;
  padding: 24rpx 28rpx;
  background: rgba(255, 255, 255, 0.96);
  border-radius: 30rpx;
  box-shadow: 0 18rpx 42rpx rgba(21, 33, 52, 0.08);
}
.ops-card {
  margin-top: 20rpx;
  padding: 24rpx 28rpx;
  background: rgba(255, 255, 255, 0.96);
  border-radius: 30rpx;
  box-shadow: 0 18rpx 42rpx rgba(21, 33, 52, 0.08);
}
.recent-card {
  margin-top: 20rpx;
  padding: 22rpx 26rpx;
  border-radius: 26rpx;
  background: linear-gradient(
    180deg,
    rgba(21, 75, 114, 0.06) 0%,
    rgba(236, 138, 87, 0.08) 100%
  );
  border: 1rpx solid rgba(21, 75, 114, 0.08);
}
.hero-main,
.order-head,
.goods-meta,
.order-summary {
  display: flex;
  justify-content: space-between;
}
.hero-main,
.order-head {
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
.review-card__title {
  font-size: 28rpx;
  font-weight: 700;
  color: #172333;
}
.review-card__desc {
  margin-top: 12rpx;
  color: #5b6b7b;
  font-size: 22rpx;
  line-height: 1.7;
}
.review-card__tags {
  display: flex;
  flex-wrap: wrap;
  gap: 10rpx;
  margin-top: 12rpx;
}
.review-card__tag {
  display: inline-flex;
  align-items: center;
  min-height: 38rpx;
  padding: 0 16rpx;
  border-radius: 999rpx;
  background: rgba(21, 75, 114, 0.08);
  color: #4f6275;
  font-size: 20rpx;
}
.ops-card__head {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 20rpx;
}
.ops-card__title {
  font-size: 28rpx;
  font-weight: 700;
  color: #172333;
}
.ops-card__desc {
  margin-top: 12rpx;
  color: #5b6b7b;
  font-size: 22rpx;
  line-height: 1.7;
}
.ops-card__badge {
  min-height: 42rpx;
  padding: 0 18rpx;
  border-radius: 999rpx;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 22rpx;
  font-weight: 700;
  white-space: nowrap;
}
.ops-card__badge.is-success {
  background: rgba(39, 174, 96, 0.12);
  color: #1d8f5f;
}
.ops-card__badge.is-pending {
  background: rgba(240, 140, 90, 0.14);
  color: #cf6b45;
}
.ops-card__badge.is-risk {
  background: rgba(239, 68, 68, 0.12);
  color: #cf5a48;
}
.ops-card__tags {
  display: flex;
  flex-wrap: wrap;
  gap: 10rpx;
  margin-top: 14rpx;
}
.ops-card__tag {
  display: inline-flex;
  align-items: center;
  min-height: 38rpx;
  padding: 0 16rpx;
  border-radius: 999rpx;
  background: rgba(21, 75, 114, 0.08);
  color: #4f6275;
  font-size: 20rpx;
}
.ops-card__risk {
  margin-top: 14rpx;
  padding: 16rpx 18rpx;
  border-radius: 18rpx;
  background: rgba(255, 244, 232, 0.98);
  color: #8c5145;
  font-size: 21rpx;
  line-height: 1.7;
}
.ops-card__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 12rpx;
  margin-top: 16rpx;
}
.ops-card__action {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 42rpx;
  padding: 0 18rpx;
  border-radius: 999rpx;
  background: linear-gradient(
    135deg,
    rgba(21, 75, 114, 0.12),
    rgba(235, 139, 89, 0.16)
  );
  color: #2c6184;
  font-size: 21rpx;
  font-weight: 700;
}
.recent-card__title {
  font-size: 26rpx;
  font-weight: 700;
  color: #172333;
}
.recent-card__desc {
  margin-top: 10rpx;
  color: #4f6275;
  font-size: 22rpx;
  line-height: 1.7;
}
.result-card__head,
.result-card__summary {
  display: flex;
  justify-content: space-between;
  gap: 20rpx;
}
.result-card__head {
  align-items: flex-start;
}
.result-card__title {
  font-size: 30rpx;
  font-weight: 700;
  line-height: 1.5;
}
.result-card__time {
  margin-top: 10rpx;
  font-size: 20rpx;
  color: rgba(255, 255, 255, 0.78);
}
.result-card__badge {
  min-height: 42rpx;
  padding: 0 18rpx;
  border-radius: 999rpx;
  background: rgba(255, 255, 255, 0.16);
  font-size: 22rpx;
  display: inline-flex;
  align-items: center;
  white-space: nowrap;
}
.result-card__desc {
  margin-top: 14rpx;
  font-size: 22rpx;
  line-height: 1.7;
  color: rgba(255, 255, 255, 0.9);
}
.result-card__meta {
  display: flex;
  flex-wrap: wrap;
  gap: 10rpx;
  margin-top: 12rpx;
}
.result-card__tag {
  display: inline-flex;
  align-items: center;
  min-height: 38rpx;
  padding: 0 16rpx;
  border-radius: 999rpx;
  background: rgba(255, 255, 255, 0.14);
  font-size: 20rpx;
}
.result-card__summary {
  margin-top: 14rpx;
  font-size: 22rpx;
  color: rgba(255, 255, 255, 0.88);
}
.result-card__order-no,
.result-card__merchants {
  margin-top: 10rpx;
  font-size: 21rpx;
  color: rgba(255, 255, 255, 0.8);
  line-height: 1.6;
  word-break: break-all;
}
.result-card__followup {
  margin-top: 14rpx;
  padding: 16rpx 18rpx;
  border-radius: 18rpx;
  background: rgba(255, 255, 255, 0.12);
}
.result-card__followup-title {
  font-size: 22rpx;
  font-weight: 700;
  color: #ffffff;
}
.result-card__followup-desc {
  margin-top: 8rpx;
  font-size: 21rpx;
  line-height: 1.7;
  color: rgba(255, 255, 255, 0.88);
}
.result-card__actions {
  display: flex;
  justify-content: flex-end;
  flex-wrap: wrap;
  gap: 14rpx;
  margin-top: 18rpx;
}
.env-card {
  margin-top: 20rpx;
  padding: 24rpx 28rpx;
  border: 1rpx solid rgba(228, 233, 240, 0.85);
}
.env-card__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16rpx;
}
.env-card__title {
  font-size: 28rpx;
  font-weight: 700;
  color: #172333;
}
.env-card__badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 42rpx;
  padding: 0 18rpx;
  border-radius: 999rpx;
  font-size: 22rpx;
  color: #ffffff;
  background: linear-gradient(45deg, #1cbbb4, #0081ff);
}
.env-card__badge.is-prod {
  background: linear-gradient(45deg, #ef4444, #f97316);
}
.env-card__desc {
  margin-top: 12rpx;
  color: #5b6b7b;
  font-size: 22rpx;
  line-height: 1.7;
}
.env-card__meta {
  display: flex;
  flex-wrap: wrap;
  gap: 10rpx;
  margin-top: 10rpx;
}
.env-card__tag {
  display: inline-flex;
  align-items: center;
  min-height: 38rpx;
  padding: 0 16rpx;
  border-radius: 999rpx;
  background: rgba(21, 75, 114, 0.08);
  color: #4f6275;
  font-size: 20rpx;
}
.env-card__note {
  margin-top: 10rpx;
  color: #6b7b8c;
  font-size: 21rpx;
  line-height: 1.7;
}
.env-card__note--strong {
  padding: 14rpx 16rpx;
  border-radius: 18rpx;
  background: rgba(21, 75, 114, 0.06);
  color: #35506b;
}
.env-card__risk-list {
  margin-top: 12rpx;
  padding: 14rpx 16rpx;
  border-radius: 18rpx;
  background: rgba(255, 244, 232, 0.98);
}
.env-card__risk-item {
  color: #8c5145;
  font-size: 21rpx;
  line-height: 1.7;
}
.env-card__risk-item + .env-card__risk-item {
  margin-top: 8rpx;
}
.env-profile-board {
  margin-top: 14rpx;
  padding: 18rpx 20rpx;
  border-radius: 20rpx;
  background: rgba(21, 75, 114, 0.05);
}
.env-profile-board__title {
  font-size: 24rpx;
  font-weight: 700;
  color: #112233;
}
.env-profile-board__list {
  margin-top: 14rpx;
  display: flex;
  flex-direction: column;
  gap: 12rpx;
}
.env-profile-board__item {
  padding: 18rpx 20rpx;
  border-radius: 18rpx;
  background: rgba(255, 255, 255, 0.92);
  border: 2rpx solid rgba(21, 75, 114, 0.06);
}
.env-profile-board__item.is-current {
  border-color: rgba(21, 75, 114, 0.18);
  box-shadow: 0 10rpx 24rpx rgba(21, 75, 114, 0.08);
}
.env-profile-board__item-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16rpx;
}
.env-profile-board__item-name {
  font-size: 24rpx;
  font-weight: 700;
  color: #112233;
}
.env-profile-board__item-status {
  padding: 6rpx 14rpx;
  border-radius: 999rpx;
  font-size: 20rpx;
  font-weight: 700;
  background: rgba(28, 187, 180, 0.12);
  color: #147d8e;
}
.env-profile-board__item-status.is-ready {
  background: rgba(39, 174, 96, 0.12);
  color: #1d8f5f;
}
.env-profile-board__item-status.is-gray,
.env-profile-board__item-status.is-test {
  background: rgba(42, 111, 147, 0.12);
  color: #2a6f93;
}
.env-profile-board__item-status.is-hold {
  background: rgba(255, 244, 232, 0.98);
  color: #c76836;
}
.env-profile-board__item-desc {
  margin-top: 10rpx;
  font-size: 21rpx;
  line-height: 1.7;
  color: #647487;
}
.env-card__url {
  margin-top: 10rpx;
  color: #94a3b8;
  font-size: 20rpx;
  line-height: 1.6;
  word-break: break-all;
}
.tabs-scroll {
  margin-top: 22rpx;
  white-space: nowrap;
}
.tabs-row {
  display: inline-flex;
  gap: 16rpx;
}
.tab-chip {
  padding: 14rpx 24rpx;
  border-radius: 999rpx;
  background: rgba(21, 75, 114, 0.08);
  color: #536274;
  font-size: 24rpx;
}
.tab-chip.active {
  background: #154b72;
  color: #ffffff;
  box-shadow: 0 10rpx 24rpx rgba(21, 75, 114, 0.2);
}
.order-list {
  margin-top: 20rpx;
}
.order-card + .order-card {
  margin-top: 20rpx;
}
.order-card {
  padding: 28rpx;
}
.merchant-box {
  display: flex;
  align-items: center;
  gap: 16rpx;
  flex: 1;
  min-width: 0;
}
.merchant-icon {
  width: 56rpx;
  height: 56rpx;
  border-radius: 50%;
  background: rgba(21, 75, 114, 0.08);
  color: #1c5c88;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28rpx;
}
.merchant-title {
  font-size: 28rpx;
  font-weight: 600;
  color: #172333;
}
.merchant-order-no {
  margin-top: 8rpx;
  font-size: 22rpx;
  color: #718093;
}
.status-text {
  font-size: 24rpx;
  font-weight: 600;
  color: #cf5a48;
}
.goods-card {
  display: flex;
  gap: 18rpx;
  margin-top: 20rpx;
  padding: 20rpx;
  border-radius: 24rpx;
  background: linear-gradient(180deg, #f9fbfc 0%, #f0f4f7 100%);
}
.goods-image {
  width: 152rpx;
  height: 152rpx;
  border-radius: 18rpx;
  flex-shrink: 0;
}
.goods-body {
  flex: 1;
  min-width: 0;
}
.goods-title {
  font-size: 28rpx;
  font-weight: 600;
  color: #172333;
  line-height: 1.45;
}
.goods-spec {
  margin-top: 8rpx;
  font-size: 22rpx;
  color: #718093;
}
.goods-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 10rpx;
  margin-top: 12rpx;
}
.goods-tag {
  padding: 8rpx 16rpx;
  border-radius: 999rpx;
  background: rgba(236, 138, 87, 0.12);
  color: #cf5a48;
  font-size: 20rpx;
}
.goods-meta {
  align-items: center;
  margin-top: 14rpx;
}
.goods-price {
  font-size: 30rpx;
  font-weight: 700;
  color: #cf5a48;
}
.goods-qty {
  font-size: 22rpx;
  color: #667487;
}
.order-summary {
  align-items: center;
  gap: 20rpx;
  margin-top: 20rpx;
  font-size: 22rpx;
}
.summary-time {
  color: #718093;
}
.summary-total {
  color: #243142;
  text-align: right;
}
.action-row {
  display: flex;
  justify-content: flex-end;
  flex-wrap: wrap;
  gap: 14rpx;
  margin-top: 20rpx;
}
.action-btn {
  height: 68rpx;
  padding: 0 28rpx;
  border: none;
  border-radius: 999rpx;
  font-size: 24rpx;
  line-height: 68rpx;
}
.action-btn::after {
  border: none;
}
.action-btn.primary {
  background: linear-gradient(90deg, #1c5c88 0%, #ec8a57 100%);
  color: #ffffff;
}
.action-btn.light {
  background: #edf1f5;
  color: #4e5d70;
}
.loading-box,
.empty-state {
  padding: 80rpx 0 20rpx;
  text-align: center;
  font-size: 24rpx;
  color: #7f8a99;
}
.page-bottom-space {
  height: calc(32rpx + env(safe-area-inset-bottom));
}
</style>

