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
            <view class="hero-title">订单核销</view>
            <view class="hero-subtitle"
              >处理线下收款订单，查看支付凭证并确认到账</view
            >
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
          <text class="env-card__tag">{{ currentStatusText }}</text>
          <text class="env-card__tag">{{ orderCountText }}</text>
          <text class="env-card__tag">{{ merchantAccessText }}</text>
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
        <view class="review-card__title">核销前复核</view>
        <view class="review-card__desc">{{ reviewHint }}</view>
        <view class="review-card__meta">
          <text class="review-card__tag">{{ currentStatusText }}</text>
          <text class="review-card__tag">{{ orderCountText }}</text>
          <text class="review-card__tag">{{ envModeText }}</text>
        </view>
        <view class="review-card__risk">{{ reviewRiskText }}</view>
      </view>

      <view v-if="recentActionSummary" class="recent-card">
        <view class="recent-card__title">最近操作</view>
        <view class="recent-card__desc">{{ recentActionSummary }}</view>
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
                <text class="cuIcon-peoplefill"></text>
              </view>
              <view class="merchant-info">
                <view class="merchant-title">{{
                  memberTitleText(item.member_title, "匿名用户")
                }}</view>
                <view class="merchant-order-no"
                  >订单号：{{ item.order_no }}</view
                >
              </view>
            </view>
            <text class="status-text">{{ item.status_title }}</text>
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
              v-if="item.pay_type == 2"
              @tap="showImages(item.pay_voucher_imgs)"
            >
              查看支付凭证
            </button>
            <button
              class="action-btn primary"
              v-if="item.status == 0 && item.pay_type == 2"
              @tap="confirmReceipt(item.id, item.total_price)"
            >
              确认已收款
            </button>
          </view>
        </view>
      </view>

      <view v-else-if="!isLoad" class="empty-state"
        >当前条件下暂无待核销订单</view
      >
      <view class="loading-box" v-if="isLoad">加载中...</view>
      <view class="page-bottom-space"></view>
    </view>
  </view>
</template>

<script>
import api from "@/api";
import { maskMemberNickname } from "@/utils/desensitize.js";
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

export default {
  data() {
    return {
      merchantInfo: {},
      tab_cur: -1,
      tab_scroll: 0,
      isLoad: false,
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
      recentActionSummary: "",
    };
  },
  computed: {
    currentEnvInfo() {
      return getCurrentEnvInfo();
    },
    envDescription() {
      return this.currentEnvInfo.is_prod
        ? "当前为正式环境，确认已收款会直接写入真实订单核销结果。"
        : "当前为非正式环境，适合做线下收款核销和支付凭证核验联调。";
    },
    currentStatusText() {
      const current = (this.params.order_status || []).find(
        (item) => item.value == this.tab_cur,
      );
      return current ? `当前筛选：${current.label}` : "当前筛选：全部订单";
    },
    orderCountText() {
      return `当前订单：${this.count || 0} 单`;
    },
    merchantAccessText() {
      return Number(this.merchantInfo.is_expired || 0) === 1
        ? "商家状态：已到期"
        : "商家状态：可核销";
    },
    envIsolationHealth() {
      return getEnvIsolationHealth();
    },
    envIsolationText() {
      return getEnvIsolationTag(this.currentEnvInfo);
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
    envModeText() {
      return this.currentEnvInfo.is_prod
        ? "环境模式：真实核销"
        : "环境模式：测试核销";
    },
    envActionHint() {
      return this.currentEnvInfo.is_prod
        ? `当前核销操作会直接变更真实订单状态，请逐单核对到账凭证与商家状态。${getEnvIsolationHint(this.currentEnvInfo)}`
        : `建议先在当前环境联调支付凭证预览、核销确认、筛选切换和回显刷新。${getEnvIsolationHint(this.currentEnvInfo)}`;
    },
    reviewHint() {
      return this.currentEnvInfo.is_prod
        ? "当前为正式环境，确认已收款后会直接变更真实订单支付状态。"
        : "当前为非正式环境，建议重点验证支付凭证预览、核销确认和列表刷新回显。";
    },
    reviewRiskText() {
      if (Number(this.merchantInfo.is_expired || 0) === 1) {
        return "当前商家服务已到期，续费前不应继续进行真实订单核销。";
      }
      if (
        this.tab_cur == 0 ||
        this.query.status == 0 ||
        this.query.status === null
      ) {
        return `当前列表可能包含待核销订单，确认收款前请先逐单核对支付凭证与实际到账。${getEnvIsolationHint(this.currentEnvInfo)}`;
      }
      return `请结合当前筛选状态和支付凭证逐单核对，避免误核销。${getEnvIsolationHint(this.currentEnvInfo)}`;
    },
  },
  onLoad(options) {
    if (options.status) {
      this.query.status = options.status;
    }
    this.checkMerchantAccess();
  },
  onReachBottom() {
    if (this.query.page < this.pages) {
      this.query.page++;
      this.getList();
    }
  },
  methods: {
    updateRecentActionSummary(summary) {
      this.recentActionSummary = summary;
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
    memberTitleText(value, fallback = "匿名用户") {
      return maskMemberNickname(value, fallback);
    },
    checkMerchantAccess() {
      api
        .merchantInfo({})
        .then((res) => {
          this.merchantInfo = res.data || {};
          if (Number(this.merchantInfo.is_expired || 0) === 1) {
            this.updateRecentActionSummary(
              "检测到商家服务已到期，核销能力已被拦截。",
            );
            this.showExpireDialog();
            return;
          }
          this.updateRecentActionSummary(
            "商家核销权限校验通过，开始加载订单核销列表。",
          );
          this.getParams();
          this.getList();
        })
        .catch(() => {
          this.updateRecentActionSummary("商家状态校验失败，已退出核销页。");
          this.leavePage();
        });
    },
    showExpireDialog() {
      const expireTime = this.merchantInfo.expire_time || "未设置";
      uni.showModal({
        title: "商家服务已到期",
        content: `您的商家服务已于 ${expireTime} 到期，请先续费后再继续使用订单核销功能。`,
        confirmText: "去续费",
        cancelText: "返回",
        success: (res) => {
          if (res.confirm) {
            this.updateRecentActionSummary(
              "准备跳转续费页，恢复商家核销能力。",
            );
            uni.navigateTo({
              url: "/pages/merchant/renew",
            });
            return;
          }
          this.updateRecentActionSummary("已取消续费，准备离开订单核销页。");
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
    showImages(images) {
      const urls = [];
      for (let i = 0; i < images.length; i++) {
        urls.push(images[i].file_url);
      }
      this.updateRecentActionSummary(
        `正在预览支付凭证，共 ${urls.length} 张。`,
      );
      uni.previewImage({
        urls,
        current: urls[0],
      });
    },
    confirmReceipt(id, pay_price) {
      if (this.isBtn) {
        return false;
      }
      const currentOrder = this.list.find((item) => item.id == id) || {};
      this.updateRecentActionSummary(
        `准备确认线下收款：订单 ${currentOrder.order_no || id}。`,
      );
      uni.showModal({
        title: "温馨提示",
        showCancel: true,
        content: this.currentEnvInfo.is_prod
          ? "当前为正式环境，确定已经收到这笔真实款项并完成核销吗？"
          : `当前为${this.currentEnvInfo.label}，确定继续测试订单核销吗？`,
        success: (e) => {
          if (!e.confirm) {
            return;
          }
          this.isBtn = true;
          api
            .merOrderPayAuth({ ids: [id], pay_price })
            .then(() => {
              this.updateRecentActionSummary(
                `已确认线下收款：订单 ${currentOrder.order_no || id}。`,
              );
              uni.showToast({
                icon: "none",
                position: "bottom",
                title: "操作成功",
              });
              this.query.page = 1;
              this.getList();
            })
            .catch(() => {})
            .finally(() => {
              this.isBtn = false;
            });
        },
      });
    },
    getList() {
      this.isLoad = true;
      api
        .getMerOrderList(this.query)
        .then((res) => {
          if (this.query.page == 1) {
            this.list = res.data.list;
          } else {
            this.list = this.list.concat(res.data.list);
          }
          this.count = res.data.count;
          this.pages = res.data.pages;
          this.updateRecentActionSummary(
            `核销订单已刷新：${this.currentStatusText.replace("当前筛选：", "")}，共 ${res.data.count || 0} 单。`,
          );
        })
        .catch(() => {
          this.updateRecentActionSummary(
            "核销订单加载失败，请确认商家状态和当前接口环境。",
          );
        })
        .finally(() => {
          this.isLoad = false;
        });
    },
    getParams() {
      api
        .getMerParams({})
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
          this.updateRecentActionSummary("核销筛选项已同步。");
        })
        .catch(() => {
          this.updateRecentActionSummary(
            "核销筛选项加载失败，当前仅可使用已有默认筛选。",
          );
        });
    },
    tabSelect(e) {
      const status = e.currentTarget.dataset.id;
      const current = (this.params.order_status || []).find(
        (item) => item.value == status,
      );
      this.tab_cur = status;
      uni.pageScrollTo({
        scrollTop: 0,
        duration: 0,
      });
      this.query.status = status;
      this.query.page = 1;
      this.list = [];
      this.updateRecentActionSummary(
        `已切换核销筛选：${current ? current.label : "全部订单"}。`,
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
.env-card,
.review-card,
.recent-card {
  background: rgba(255, 255, 255, 0.96);
  border-radius: 30rpx;
  box-shadow: 0 18rpx 42rpx rgba(21, 33, 52, 0.08);
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

.env-card {
  margin-top: 20rpx;
  padding: 24rpx 28rpx;
  border: 1rpx solid rgba(228, 233, 240, 0.85);
}

.env-card__meta,
.review-card__meta {
  display: flex;
  flex-wrap: wrap;
  gap: 10rpx;
  margin-top: 10rpx;
}

.env-card__tag,
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

.review-card {
  margin-top: 20rpx;
  padding: 24rpx 28rpx;
}

.recent-card {
  margin-top: 20rpx;
  padding: 22rpx 26rpx;
  background: linear-gradient(
    180deg,
    rgba(21, 75, 114, 0.06) 0%,
    rgba(236, 138, 87, 0.08) 100%
  );
  border: 1rpx solid rgba(21, 75, 114, 0.08);
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

.review-card__risk {
  margin-top: 12rpx;
  padding: 18rpx 20rpx;
  border-radius: 18rpx;
  background: linear-gradient(180deg, #fff7ed 0%, #ffedd5 100%);
  color: #9a3412;
  font-size: 22rpx;
  line-height: 1.65;
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

.env-card__url {
  margin-top: 10rpx;
  color: #94a3b8;
  font-size: 20rpx;
  line-height: 1.6;
  word-break: break-all;
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

