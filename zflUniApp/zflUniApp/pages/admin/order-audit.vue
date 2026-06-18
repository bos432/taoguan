<template>
  <view
    :class="'ui-theme-' + (($store.state.setting && $store.state.setting.system && $store.state.setting.system.ui_theme_style) || 'origin')"
  >
    <view class="order-page">
    <view class="hero-card">
      <view class="hero-badge">移动审核</view>
      <view class="hero-title">商家订单核销</view>
      <view class="hero-subtitle">查看全平台商家订单，处理付款审核和自提核销。</view>
    </view>

    <view class="env-card">
      <view class="env-card__head">
        <view class="env-card__title">当前环境</view>
        <view class="env-card__badge" :class="currentEnvInfo.is_prod ? 'is-prod' : 'is-test'">
          {{ currentEnvInfo.label }}
        </view>
      </view>
      <view class="env-card__desc">{{ envDescription }}</view>
      <view class="env-card__meta">
        <text class="env-card__tag">{{ envIsolationStatusText }}</text>
        <text class="env-card__tag">{{ envReleaseStageText }}</text>
        <text class="env-card__tag">{{ envIsolationText }}</text>
      </view>
      <view class="env-card__summary">{{ envReleaseHint }}</view>
      <view v-if="envRiskList.length" class="env-card__risk-list">
        <view v-for="item in envRiskList" :key="item" class="env-card__risk-item">{{ item }}</view>
      </view>
      <view class="env-card__url">{{ currentEnvInfo.api_root_url || "未配置接口地址" }}</view>
    </view>

    <view class="review-card">
      <view class="review-card__title">操作前复核</view>
      <view class="review-card__desc">{{ submitReviewHint }}</view>
      <view class="review-card__tags">
        <text class="review-card__tag">{{ submitReviewTags.role }}</text>
        <text class="review-card__tag">{{ submitReviewTags.scene }}</text>
        <text class="review-card__tag">{{ submitReviewTags.pay }}</text>
        <text class="review-card__tag">{{ submitReviewTags.writeoff }}</text>
      </view>
      <view class="review-card__risk">{{ submitRiskHint }}</view>
    </view>

    <view class="recent-card">
      <view class="recent-card__title">最近操作</view>
      <view class="recent-card__desc">{{ recentActionSummary }}</view>
    </view>

    <scroll-view scroll-x class="tabs-scroll">
      <view class="tabs-row">
        <view
          v-for="tab in sceneTabs"
          :key="tab.value"
          class="tab-chip"
          :class="{ active: query.review_scene === tab.value }"
          @tap="switchScene(tab.value)"
        >
          {{ tab.label }}
        </view>
      </view>
    </scroll-view>

    <view v-if="list.length" class="order-list">
      <view v-for="item in list" :key="item.id" class="order-card">
        <view class="order-head">
          <view class="head-main">
            <view class="head-title">{{ merchantTitleText(item.merchant_title, '平台订单') }}</view>
            <view class="head-subtitle">订单号：{{ item.order_no }}</view>
          </view>
          <view class="status-pill" :class="item.status_class">{{ item.status_title || '-' }}</view>
        </view>

        <view class="summary-grid">
          <view class="summary-item">
            <text class="summary-label">买家</text>
            <text class="summary-value">{{ memberTitleText(item.member_title, '-') }}</text>
          </view>
          <view class="summary-item">
            <text class="summary-label">订单金额</text>
            <text class="summary-value">¥{{ formatMoney(item.total_price) }}</text>
          </view>
          <view class="summary-item">
            <text class="summary-label">支付方式</text>
            <text class="summary-value">{{ item.pay_type_title || '-' }}</text>
          </view>
          <view class="summary-item">
            <text class="summary-label">提交时间</text>
            <text class="summary-value">{{ item.create_time || '-' }}</text>
          </view>
          <view v-if="Number(item.delivery_type || 0) === 2" class="summary-item summary-item--full">
            <text class="summary-label">提货码</text>
            <text class="summary-value">{{ item.pick_up_code || '-' }}</text>
          </view>
          <view v-if="item.pay_auth_msg" class="summary-item summary-item--full">
            <text class="summary-label">审核说明</text>
            <text class="summary-value summary-danger">{{ item.pay_auth_msg }}</text>
          </view>
        </view>

        <view v-for="goodsItem in item.detaileds || []" :key="goodsItem.id" class="goods-card">
          <image class="goods-image" :src="goodsImage(goodsItem)" mode="aspectFill"></image>
          <view class="goods-body">
            <view class="goods-title">{{ goodsItem.goods ? goodsItem.goods.title : '商品信息缺失' }}</view>
            <view class="goods-spec">{{ goodsItem.goods && goodsItem.goods.spec ? goodsItem.goods.spec : '默认规格' }}</view>
            <view class="goods-meta">
              <text class="goods-price">¥{{ formatMoney(goodsItem.price) }}</text>
              <text class="goods-qty">x{{ goodsItem.quantity }}</text>
            </view>
          </view>
        </view>

        <view class="action-row">
          <button
            v-if="voucherUrls(item).length"
            class="action-btn light"
            @tap="previewImages(voucherUrls(item), voucherUrls(item)[0])"
          >
            查看凭证
          </button>
          <button
            v-if="canPayAuth && canApprovePayment(item)"
            class="action-btn success"
            @tap="submitPayAuth(item, 1)"
          >
            通过付款
          </button>
          <button
            v-if="canPayAuth && canApprovePayment(item)"
            class="action-btn danger"
            @tap="openReject(item)"
          >
            驳回付款
          </button>
          <button
            v-if="canWriteoff && canWriteoffOrder(item)"
            class="action-btn primary"
            @tap="confirmWriteoff(item)"
          >
            确认核销
          </button>
        </view>
      </view>
    </view>

    <view v-else-if="!loading" class="empty-state">当前条件下暂无订单</view>
    <view v-if="loading" class="loading-box">加载中...</view>
    <view class="page-bottom-space"></view>

    <view v-if="rejectVisible" class="mask-layer" @tap="closeReject">
      <view class="dialog-card" @tap.stop>
        <view class="dialog-title">驳回付款</view>
        <textarea v-model="rejectForm.pay_auth_msg" class="dialog-textarea" placeholder="请输入驳回原因"></textarea>
        <view class="dialog-actions">
          <button class="action-btn light" @tap="closeReject">取消</button>
          <button class="action-btn danger" :loading="submitting" @tap="confirmReject">确认驳回</button>
        </view>
      </view>
    </view>
    </view>
  </view>
</template>

<script>
import api from "@/api";
import { maskMemberNickname, maskMerchantTitle } from "@/utils/desensitize.js";
import { getCurrentEnvInfo, getEnvIsolationHealth } from "@/utils/env-runtime.js";
import { getEnvIsolationTag, getEnvReleaseHint, getEnvReleaseStageText } from "@/utils/env-risk.js";

export default {
  data() {
    return {
      sceneTabs: [
        { value: "all", label: "全部订单" },
        { value: "pending_pay_auth", label: "待审核付款" },
        { value: "pending_writeoff", label: "待核销订单" },
      ],
      query: {
        page: 1,
        limit: 10,
        review_scene: "all",
      },
      list: [],
      pages: 0,
      loading: false,
      submitting: false,
      merchantInfo: {},
      rejectVisible: false,
      rejectTarget: null,
      rejectForm: {
        pay_auth_msg: "",
      },
      recentActionSummary: "待开始：请先确认环境、审核场景和商家超管权限。",
    };
  },
  computed: {
    currentEnvInfo() {
      return getCurrentEnvInfo();
    },
    envDescription() {
      return this.currentEnvInfo.is_prod
        ? "当前为正式环境，付款审核和核销会直接写入线上订单状态。"
        : "当前为非正式环境，适合做付款审核、自提核销和异常订单流程验收。";
    },
    envIsolationHealth() {
      return getEnvIsolationHealth();
    },
    envIsolationStatusText() {
      return this.envIsolationHealth.is_isolated_ready ? "隔离状态：已就绪" : "隔离状态：待处理";
    },
    envReleaseStageText() {
      return getEnvReleaseStageText(this.envIsolationHealth);
    },
    envReleaseHint() {
      return getEnvReleaseHint(this.envIsolationHealth);
    },
    envIsolationText() {
      return getEnvIsolationTag(this.currentEnvInfo);
    },
    envRiskList() {
      return this.envIsolationHealth.warnings || [];
    },
    isMerchantSuper() {
      return Number(this.merchantInfo.merchant_system_super || 0) === 1;
    },
    canPayAuth() {
      return this.isMerchantSuper;
    },
    canWriteoff() {
      return this.isMerchantSuper;
    },
    pendingPayCount() {
      return this.list.filter((item) => this.canApprovePayment(item)).length;
    },
    pendingWriteoffCount() {
      return this.list.filter((item) => this.canWriteoffOrder(item)).length;
    },
    submitReviewTags() {
      return {
        role: this.isMerchantSuper ? "权限：可审核可核销" : "权限：只读查看",
        scene: `场景：${(this.sceneTabs.find((item) => item.value === this.query.review_scene) || {}).label || "全部订单"}`,
        pay: `待审核付款：${this.pendingPayCount} 单`,
        writeoff: `待核销：${this.pendingWriteoffCount} 单`,
      };
    },
    submitReviewHint() {
      return this.currentEnvInfo.is_prod
        ? "当前为正式环境，付款审核和核销会直接改变真实订单状态，请先核对金额、凭证和提货码。"
        : "当前为非正式环境，建议先完整验证付款通过、付款驳回、核销完成和列表刷新回显。";
    },
    submitRiskHint() {
      if (!this.isMerchantSuper) {
        return "当前账号没有审核或核销权限，只能查看订单信息。";
      }
      if (!this.list.length) {
        return "当前筛选条件下没有订单数据，建议切换到待审核付款或待核销列表继续联调。";
      }
      if (this.query.review_scene === "pending_pay_auth" && this.pendingPayCount <= 0) {
        return "待审核付款列表为空，当前无法继续验证付款审核动作。";
      }
      if (this.query.review_scene === "pending_writeoff" && this.pendingWriteoffCount <= 0) {
        return "待核销列表为空，当前无法继续验证核销动作。";
      }
      return this.currentEnvInfo.is_prod
        ? "正式环境下建议先查看付款凭证与订单金额一致性，再执行审核或核销。"
        : "当前复核项已就绪，可继续验证付款审核、驳回原因和核销完成回显。";
    },
  },
  onLoad(options = {}) {
    const scene = String(options.review_scene || "").trim();
    if (scene && this.sceneTabs.some(item => item.value === scene)) {
      this.query.review_scene = scene;
    }
    this.updateRecentActionSummary(`页面已进入，当前审核场景：${(this.sceneTabs.find((item) => item.value === this.query.review_scene) || {}).label || "全部订单"}。`);
    this.bootstrap();
  },
  onPullDownRefresh() {
    this.reloadList().finally(() => {
      uni.stopPullDownRefresh();
    });
  },
  onReachBottom() {
    if (this.query.page < this.pages && !this.loading) {
      this.query.page += 1;
      this.getList();
    }
  },
  methods: {
    merchantTitleText(value, fallback = "平台订单") {
      return maskMerchantTitle(value, fallback);
    },
    memberTitleText(value, fallback = "-") {
      return maskMemberNickname(value, fallback);
    },
    updateRecentActionSummary(message) {
      this.recentActionSummary = message;
    },
    async bootstrap() {
      try {
        await this.loadMerchantInfo();
      } catch (error) {
        this.merchantInfo = {};
        this.updateRecentActionSummary("商家信息加载失败，当前无法确认订单审核权限。");
        uni.showToast({
          icon: "none",
          title: "商家信息加载失败",
        });
        return;
      }
      if (!this.ensureMerchantSuperAccess()) {
        return;
      }
      this.updateRecentActionSummary("商家超管权限校验通过，开始加载订单审核列表。");
      await this.reloadList();
    },
    async loadMerchantInfo() {
      const res = await api.merchantInfo({});
      this.merchantInfo = res.data || {};
    },
    ensureMerchantSuperAccess() {
      if (this.isMerchantSuper) {
        return true;
      }
      this.updateRecentActionSummary("权限校验未通过，当前账号无法执行付款审核或核销。");
      uni.showModal({
        title: "无权访问",
        content: "当前页面仅商家超管可使用。",
        showCancel: false,
        success: () => {
          uni.navigateBack({
            fail: () => {
              uni.reLaunch({
                url: "/pages/app/my",
              });
            },
          });
        },
      });
      return false;
    },
    async reloadList() {
      if (!this.ensureMerchantSuperAccess()) {
        return;
      }
      this.query.page = 1;
      this.list = [];
      this.updateRecentActionSummary(`已刷新${(this.sceneTabs.find((item) => item.value === this.query.review_scene) || {}).label || "全部订单"}，准备重新拉取数据。`);
      await this.getList();
    },
    async getList() {
      if (!this.ensureMerchantSuperAccess()) {
        return;
      }
      this.loading = true;
      try {
        const res = await api.adminOrderList(this.query);
        const data = res.data || {};
        const rows = Array.isArray(data.list) ? data.list.map((item) => this.normalizeOrderRow(item)) : [];
        this.list = this.query.page === 1 ? rows : this.list.concat(rows);
        this.pages = Number(data.pages || 0);
        const currentScene = (this.sceneTabs.find((item) => item.value === this.query.review_scene) || {}).label || "全部订单";
        this.updateRecentActionSummary(`已加载${currentScene} ${rows.length} 单，当前第 ${this.query.page} 页，共 ${this.pages || 1} 页。`);
      } catch (error) {
        if (this.query.page === 1) {
          this.list = [];
        }
        this.pages = 0;
        this.updateRecentActionSummary("订单审核列表加载失败，请检查接口或稍后重试。");
        uni.showToast({
          icon: "none",
          title: "订单列表加载失败，请稍后重试",
        });
      } finally {
        this.loading = false;
      }
    },
    switchScene(scene) {
      this.query.review_scene = scene;
      this.updateRecentActionSummary(`已切换到${(this.sceneTabs.find((item) => item.value === scene) || {}).label || "全部订单"}，列表即将刷新。`);
      this.reloadList();
    },
    resolveStatusClass(item) {
      if (this.canApprovePayment(item)) {
        return "is-warning";
      }
      if (this.canWriteoffOrder(item)) {
        return "is-primary";
      }
      return Number(item.status || 0) === 4 ? "is-success" : "is-neutral";
    },
    normalizeOrderRow(item) {
      return {
        ...item,
        status_class: this.resolveStatusClass(item),
      };
    },
    voucherUrls(item) {
      return (Array.isArray(item.pay_voucher_imgs) ? item.pay_voucher_imgs : [])
        .map((file) => file.file_url)
        .filter(Boolean);
    },
    goodsImage(goodsItem) {
      if (goodsItem && goodsItem.goods && goodsItem.goods.image && goodsItem.goods.image.file_url) {
        return goodsItem.goods.image.file_url;
      }
      return "";
    },
    formatMoney(value) {
      return Number(value || 0).toFixed(2);
    },
    previewImages(urls, current) {
      const list = (urls || []).filter(Boolean);
      if (!list.length) {
        this.updateRecentActionSummary("当前订单未上传付款凭证，无法预览。");
        return;
      }
      this.updateRecentActionSummary(`已预览付款凭证，共 ${list.length} 张。`);
      uni.previewImage({
        urls: list,
        current: current || list[0],
      });
    },
    canApprovePayment(item) {
      return Number(item.pay_type || 0) === 2
        && Number(item.pay_status || 0) !== 1
        && Number(item.status || 0) === 0;
    },
    canWriteoffOrder(item) {
      return Number(item.delivery_type || 0) === 2
        && Number(item.status || 0) === 1;
    },
    async submitPayAuth(item, payStatus, payAuthMsg = "") {
      if (!this.ensureMerchantSuperAccess() || this.submitting) {
        return;
      }
      const actionText = payStatus === 1 ? "通过付款" : "驳回付款";
      this.updateRecentActionSummary(`已发起${actionText}确认，目标订单：${item.order_no || item.id}。`);
      uni.showModal({
        title: actionText,
        content: this.currentEnvInfo.is_prod
          ? `当前为正式环境，确认要执行${actionText}吗？`
          : `当前为${this.currentEnvInfo.label}，确认继续执行${actionText}吗？`,
        success: async (modalRes) => {
          if (!modalRes.confirm) {
            this.updateRecentActionSummary(`${actionText}操作已取消，未提交订单状态变更。`);
            return;
          }
          this.submitting = true;
          try {
            await api.adminOrderPayAuth({
              ids: [item.id],
              pay_price: Number(item.pay_price || item.total_price || 0),
              pay_status: payStatus,
              pay_auth_msg: payAuthMsg,
            });
            uni.showToast({
              icon: "none",
              title: payStatus === 1 ? "审核通过" : "已驳回",
            });
            this.closeReject();
            this.updateRecentActionSummary(`订单 ${item.order_no || item.id}${actionText}成功，列表已准备刷新。`);
            await this.reloadList();
          } catch (error) {
            this.updateRecentActionSummary(`订单 ${item.order_no || item.id}${actionText}失败，请稍后重试。`);
            uni.showToast({
              icon: "none",
              title: `${actionText}失败`,
            });
          } finally {
            this.submitting = false;
          }
        },
      });
    },
    openReject(item) {
      if (!this.ensureMerchantSuperAccess()) {
        return;
      }
      this.rejectTarget = item;
      this.rejectForm.pay_auth_msg = item.pay_auth_msg || "";
      this.rejectVisible = true;
      this.updateRecentActionSummary(`已打开付款驳回弹窗，目标订单：${item.order_no || item.id}。`);
    },
    closeReject() {
      this.rejectVisible = false;
      this.rejectTarget = null;
      this.rejectForm.pay_auth_msg = "";
      this.updateRecentActionSummary("已关闭付款驳回弹窗，未提交驳回说明。");
    },
    confirmReject() {
      if (!this.rejectTarget) {
        return;
      }
      if (!this.rejectForm.pay_auth_msg) {
        this.updateRecentActionSummary("付款驳回提交被拦截：缺少驳回原因。");
        uni.showToast({
          icon: "none",
          title: "请输入驳回原因",
        });
        return;
      }
      this.updateRecentActionSummary(`已填写驳回原因，准备驳回订单 ${this.rejectTarget.order_no || this.rejectTarget.id} 的付款审核。`);
      this.submitPayAuth(this.rejectTarget, 0, this.rejectForm.pay_auth_msg);
    },
    confirmWriteoff(item) {
      if (!this.ensureMerchantSuperAccess()) {
        return;
      }
      this.updateRecentActionSummary(`已发起核销确认，目标订单：${item.order_no || item.id}。`);
      uni.showModal({
        title: "确认核销",
        content: this.currentEnvInfo.is_prod
          ? "当前为正式环境，确认将这笔订单核销完成吗？"
          : `当前为${this.currentEnvInfo.label}，确认继续执行核销吗？`,
        success: async (res) => {
          if (!res.confirm) {
            this.updateRecentActionSummary("核销操作已取消，订单状态保持不变。");
            return;
          }
          this.submitting = true;
          try {
            await api.adminOrderWriteoff({ id: item.id });
            uni.showToast({
              icon: "none",
              title: "核销成功",
            });
            this.updateRecentActionSummary(`订单 ${item.order_no || item.id} 核销成功，列表已准备刷新。`);
            await this.reloadList();
          } catch (error) {
            this.updateRecentActionSummary(`订单 ${item.order_no || item.id} 核销失败，请稍后重试。`);
            uni.showToast({
              icon: "none",
              title: "核销失败",
            });
          } finally {
            this.submitting = false;
          }
        },
      });
    },
  },
};
</script>

<style scoped lang="scss">
.order-page {
  min-height: 100vh;
  padding: 24rpx;
  background:
    radial-gradient(circle at top right, rgba(243, 143, 90, 0.22), transparent 32%),
    radial-gradient(circle at top left, rgba(255, 107, 136, 0.08), transparent 28%),
    linear-gradient(180deg, #f9f3eb 0%, #f3f6fa 42%, #edf2f6 100%);
}

.hero-card,
.order-card,
.dialog-card {
  background: rgba(255, 255, 255, 0.96);
  border-radius: 30rpx;
  box-shadow: 0 18rpx 42rpx rgba(21, 33, 52, 0.08);
  border: 1rpx solid rgba(228, 233, 240, 0.85);
}

.hero-card {
  position: relative;
  padding: 32rpx;
  color: #ffffff;
  background:
    radial-gradient(circle at top right, rgba(255, 255, 255, 0.16), transparent 28%),
    linear-gradient(135deg, #154b72 0%, #2b6a8d 48%, #ec8a57 100%);
  overflow: hidden;
}

.hero-card::after {
  content: "";
  position: absolute;
  right: -36rpx;
  top: -30rpx;
  width: 220rpx;
  height: 220rpx;
  border-radius: 999rpx;
  background: radial-gradient(circle, rgba(255, 255, 255, 0.24) 0%, rgba(255, 255, 255, 0) 72%);
}

.hero-badge {
  display: inline-flex;
  padding: 10rpx 18rpx;
  border-radius: 999rpx;
  background: rgba(255, 255, 255, 0.14);
  font-size: 22rpx;
  font-weight: 700;
}

.hero-title {
  margin-top: 26rpx;
  font-size: 38rpx;
  font-weight: 700;
}

.hero-subtitle {
  margin-top: 12rpx;
  font-size: 24rpx;
  color: rgba(255, 255, 255, 0.82);
}

.tabs-scroll {
  margin-top: 22rpx;
  white-space: nowrap;
}

.env-card {
  margin-top: 20rpx;
  padding: 24rpx 28rpx;
  border-radius: 28rpx;
  background: rgba(255, 255, 255, 0.96);
  border: 1rpx solid rgba(228, 233, 240, 0.85);
  box-shadow: 0 18rpx 42rpx rgba(21, 33, 52, 0.08);
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
  gap: 12rpx;
  margin-top: 12rpx;
}

.env-card__tag {
  display: inline-flex;
  align-items: center;
  min-height: 40rpx;
  padding: 0 16rpx;
  border-radius: 999rpx;
  background: rgba(21, 75, 114, 0.08);
  color: #35506b;
  font-size: 20rpx;
}

.env-card__summary {
  margin-top: 14rpx;
  padding: 16rpx 18rpx;
  border-radius: 18rpx;
  background: rgba(21, 75, 114, 0.06);
  color: #35506b;
  font-size: 22rpx;
  line-height: 1.7;
}

.env-card__risk-list {
  margin-top: 14rpx;
  padding: 16rpx 18rpx;
  border-radius: 18rpx;
  background: rgba(255, 244, 232, 0.98);
}

.env-card__risk-item {
  color: #8c5145;
  font-size: 22rpx;
  line-height: 1.7;
}

.env-card__risk-item + .env-card__risk-item {
  margin-top: 8rpx;
}

.env-card__url {
  margin-top: 10rpx;
  color: #94a3b8;
  font-size: 20rpx;
  line-height: 1.6;
  word-break: break-all;
}

.review-card {
  margin-top: 20rpx;
  padding: 24rpx 28rpx;
  background: rgba(255, 255, 255, 0.96);
  border-radius: 30rpx;
  box-shadow: 0 18rpx 42rpx rgba(21, 33, 52, 0.08);
  border: 1rpx solid rgba(228, 233, 240, 0.85);
}

.review-card__title {
  color: #162233;
  font-size: 28rpx;
  font-weight: 700;
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
  gap: 12rpx;
  margin-top: 14rpx;
}

.review-card__tag {
  display: inline-flex;
  align-items: center;
  min-height: 40rpx;
  padding: 0 16rpx;
  border-radius: 999rpx;
  background: rgba(21, 75, 114, 0.08);
  color: #35506b;
  font-size: 20rpx;
}

.review-card__risk {
  margin-top: 14rpx;
  padding: 16rpx 18rpx;
  border-radius: 20rpx;
  background: linear-gradient(180deg, rgba(255, 247, 237, 0.96) 0%, rgba(255, 252, 247, 0.98) 100%);
  border: 1rpx solid rgba(234, 120, 72, 0.18);
  color: #8c5145;
  font-size: 22rpx;
  line-height: 1.7;
}

.recent-card {
  margin-top: 20rpx;
  padding: 24rpx 28rpx;
  background: linear-gradient(180deg, rgba(21, 75, 114, 0.06) 0%, rgba(255, 255, 255, 0.96) 100%);
  border-radius: 30rpx;
  box-shadow: 0 18rpx 42rpx rgba(21, 33, 52, 0.08);
  border: 1rpx solid rgba(174, 199, 219, 0.45);
}

.recent-card__title {
  color: #162233;
  font-size: 28rpx;
  font-weight: 700;
}

.recent-card__desc {
  margin-top: 12rpx;
  color: #4b5d70;
  font-size: 22rpx;
  line-height: 1.7;
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
  font-weight: 600;
}

.tab-chip.active {
  background: #154b72;
  color: #ffffff;
}

.order-list {
  margin-top: 20rpx;
}

.order-card + .order-card {
  margin-top: 18rpx;
}

.order-card {
  padding: 28rpx;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.99) 0%, rgba(249, 250, 252, 0.98) 100%);
}

.order-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 18rpx;
}

.head-title {
  font-size: 30rpx;
  font-weight: 700;
  color: #172333;
}

.head-subtitle {
  margin-top: 10rpx;
  font-size: 22rpx;
  color: #718093;
}

.status-pill {
  padding: 10rpx 18rpx;
  border-radius: 999rpx;
  font-size: 22rpx;
  white-space: nowrap;
}

.status-pill.is-warning {
  background: rgba(236, 138, 87, 0.12);
  color: #cf6b45;
}

.status-pill.is-primary {
  background: rgba(21, 92, 136, 0.12);
  color: #1c5c88;
}

.status-pill.is-success {
  background: rgba(29, 143, 95, 0.12);
  color: #1d8f5f;
}

.status-pill.is-neutral {
  background: rgba(123, 135, 151, 0.12);
  color: #667487;
}

.summary-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 18rpx;
  margin-top: 22rpx;
}

.summary-item {
  padding: 18rpx 20rpx;
  border-radius: 20rpx;
  background: linear-gradient(180deg, #f7fafc 0%, #eef3f7 100%);
  border: 1rpx solid rgba(228, 233, 240, 0.9);
}

.summary-item--full {
  grid-column: 1 / -1;
}

.summary-label {
  display: block;
  font-size: 22rpx;
  color: #7b8797;
}

.summary-value {
  display: block;
  margin-top: 10rpx;
  font-size: 25rpx;
  color: #243142;
  line-height: 1.6;
}

.summary-danger {
  color: #d64545;
}

.goods-card {
  display: flex;
  gap: 18rpx;
  margin-top: 20rpx;
  padding: 20rpx;
  border-radius: 24rpx;
  background: linear-gradient(180deg, #f9fbfc 0%, #f0f4f7 100%);
  border: 1rpx solid rgba(228, 233, 240, 0.9);
}

.goods-image {
  width: 152rpx;
  height: 152rpx;
  border-radius: 18rpx;
  flex-shrink: 0;
  background: #edf1f5;
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

.goods-meta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 16rpx;
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

.action-row,
.dialog-actions {
  display: flex;
  justify-content: flex-end;
  flex-wrap: wrap;
  gap: 14rpx;
  margin-top: 22rpx;
}

.action-btn {
  min-width: 156rpx;
  height: 68rpx;
  padding: 0 28rpx;
  border: none;
  border-radius: 999rpx;
  font-size: 24rpx;
  line-height: 68rpx;
  font-weight: 700;
}

.action-btn::after {
  border: none;
}

.action-btn.light {
  background: #edf1f5;
  color: #4e5d70;
}

.action-btn.success {
  background: linear-gradient(90deg, #1d8f5f 0%, #35b77a 100%);
  color: #ffffff;
}

.action-btn.danger {
  background: linear-gradient(90deg, #cf5a48 0%, #ec8a57 100%);
  color: #ffffff;
}

.action-btn.primary {
  background: linear-gradient(90deg, #154b72 0%, #2b6a8d 100%);
  color: #ffffff;
}

.loading-box,
.empty-state {
  padding: 90rpx 0 20rpx;
  text-align: center;
  font-size: 24rpx;
  color: #7f8a99;
}

.mask-layer {
  position: fixed;
  inset: 0;
  display: flex;
  align-items: flex-end;
  justify-content: center;
  padding: 24rpx;
  background: rgba(7, 17, 27, 0.45);
  z-index: 99;
}

.dialog-card {
  width: 100%;
  padding: 28rpx;
}

.dialog-title {
  font-size: 30rpx;
  font-weight: 700;
  color: #172333;
}

.dialog-textarea {
  width: 100%;
  height: 220rpx;
  margin-top: 18rpx;
  padding: 22rpx;
  border-radius: 20rpx;
  background: linear-gradient(180deg, #f7fafc 0%, #eef3f7 100%);
  border: 1rpx solid rgba(228, 233, 240, 0.9);
  font-size: 26rpx;
  color: #243142;
}

.page-bottom-space {
  height: calc(32rpx + env(safe-area-inset-bottom));
}

@media screen and (max-width: 375px) {
  .order-page {
    padding: 20rpx;
  }

  .hero-card,
  .order-card,
  .dialog-card {
    border-radius: 26rpx;
  }

  .hero-card {
    padding: 28rpx;
  }

  .hero-title {
    font-size: 34rpx;
  }

  .hero-subtitle {
    font-size: 22rpx;
  }

  .order-head {
    flex-direction: column;
    align-items: flex-start;
  }

  .summary-grid {
    grid-template-columns: repeat(1, minmax(0, 1fr));
  }

  .goods-image {
    width: 132rpx;
    height: 132rpx;
  }
}
</style>
