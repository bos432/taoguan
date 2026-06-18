<template>
  <view
    :class="'ui-theme-' + (($store.state.setting && $store.state.setting.system && $store.state.setting.system.ui_theme_style) || 'origin')"
  >
    <view class="audit-page">
    <view class="hero-card">
      <view class="hero-badge">移动审核</view>
      <view class="hero-title">平台商家审核</view>
      <view class="hero-subtitle">查看商家入驻资料，快速完成通过或驳回。</view>
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
      <view class="review-card__title">审核前复核</view>
      <view class="review-card__desc">{{ submitReviewHint }}</view>
      <view class="review-card__tags">
        <text class="review-card__tag">{{ submitReviewTags.role }}</text>
        <text class="review-card__tag">{{ submitReviewTags.scope }}</text>
        <text class="review-card__tag">{{ submitReviewTags.pending }}</text>
        <text class="review-card__tag">{{ submitReviewTags.rejected }}</text>
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
          v-for="tab in tabs"
          :key="tab.value"
          class="tab-chip"
          :class="{ active: query.auth_state === tab.value }"
          @tap="switchTab(tab.value)"
        >
          {{ tab.label }}
        </view>
      </view>
    </scroll-view>

    <view v-if="list.length" class="card-list">
      <view v-for="item in list" :key="item.id" class="audit-card">
        <view class="card-head">
          <view>
            <view class="merchant-title">{{ merchantTitleText(item.title, '未命名商家') }}</view>
            <view class="merchant-subtitle">账号：{{ item.username || '-' }}</view>
          </view>
          <view class="status-pill" :class="item.status_class">{{ item.auth_state_title || statusText(item.auth_state) }}</view>
        </view>

        <view class="meta-grid">
          <view class="meta-item">
            <text class="meta-label">联系人</text>
            <text class="meta-value">{{ item.name || '-' }}</text>
          </view>
          <view class="meta-item">
            <text class="meta-label">联系电话</text>
            <text class="meta-value">{{ item.phone || '-' }}</text>
          </view>
          <view class="meta-item meta-item--full">
            <text class="meta-label">提交时间</text>
            <text class="meta-value">{{ item.create_time || '-' }}</text>
          </view>
          <view v-if="item.auth_msg" class="meta-item meta-item--full">
            <text class="meta-label">审核说明</text>
            <text class="meta-value meta-value--danger">{{ item.auth_msg }}</text>
          </view>
        </view>

        <view class="action-row">
          <button class="action-btn light" @tap="openDetail(item)">查看资料</button>
          <button
            v-if="canAuth && Number(item.auth_state || 0) !== 1"
            class="action-btn success"
            @tap="submitAuth(item, 1)"
          >
            审核通过
          </button>
          <button
            v-if="canAuth && Number(item.auth_state || 0) !== 1"
            class="action-btn danger"
            @tap="openReject(item)"
          >
            驳回
          </button>
        </view>
      </view>
    </view>

    <view v-else-if="!loading" class="empty-state">当前条件下暂无商家申请</view>
    <view v-if="loading" class="loading-box">加载中...</view>
    <view class="page-bottom-space"></view>

    <view v-if="detailVisible" class="mask-layer" @tap="closeDetail">
      <view class="sheet-card" @tap.stop>
        <view class="sheet-head">
          <view class="sheet-title">入驻资料</view>
          <text class="cuIcon-close close-icon" @tap="closeDetail"></text>
        </view>

        <scroll-view scroll-y class="sheet-scroll">
          <view class="sheet-group">
            <view class="sheet-row">
              <text class="sheet-label">商家名称</text>
              <text class="sheet-value">{{ merchantTitleText(detailInfo.title, '-') }}</text>
            </view>
            <view class="sheet-row">
              <text class="sheet-label">联系人</text>
              <text class="sheet-value">{{ detailInfo.name || '-' }}</text>
            </view>
            <view class="sheet-row">
              <text class="sheet-label">联系电话</text>
              <text class="sheet-value">{{ detailInfo.phone || '-' }}</text>
            </view>
            <view class="sheet-row">
              <text class="sheet-label">地址</text>
              <text class="sheet-value">{{ detailInfo.address || '-' }}</text>
            </view>
            <view class="sheet-row" v-if="detailInfo.auth_msg">
              <text class="sheet-label">审核说明</text>
              <text class="sheet-value danger-text">{{ detailInfo.auth_msg }}</text>
            </view>
          </view>

          <view class="sheet-group">
            <view class="group-title">收款码</view>
            <view v-if="detailInfo.image_url" class="image-grid single">
              <image class="preview-image" :src="detailInfo.image_url" mode="aspectFill" @tap="previewImageList([detailInfo.image_url], detailInfo.image_url)"></image>
            </view>
            <view v-else class="empty-inline">未上传</view>
          </view>

          <view class="sheet-group">
            <view class="group-title">资质图片</view>
            <view v-if="detailImages.length" class="image-grid">
              <image
                v-for="(image, index) in detailImages"
                :key="index"
                class="preview-image"
                :src="image.file_url"
                mode="aspectFill"
                @tap="previewImageList(detailImageUrls, image.file_url)"
              ></image>
            </view>
            <view v-else class="empty-inline">未上传</view>
          </view>
        </scroll-view>
      </view>
    </view>

    <view v-if="rejectVisible" class="mask-layer" @tap="closeReject">
      <view class="dialog-card" @tap.stop>
        <view class="dialog-title">驳回原因</view>
        <textarea v-model="rejectForm.auth_msg" class="dialog-textarea" placeholder="请输入驳回说明"></textarea>
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
import { maskMerchantTitle } from "@/utils/desensitize.js";
import { getCurrentEnvInfo, getEnvIsolationHealth } from "@/utils/env-runtime.js";
import { getEnvIsolationTag, getEnvReleaseHint, getEnvReleaseStageText } from "@/utils/env-risk.js";

export default {
  data() {
    return {
      tabs: [
        { value: "", label: "全部" },
        { value: 0, label: "待审核" },
        { value: 1, label: "已通过" },
        { value: 2, label: "已驳回" },
      ],
      query: {
        page: 1,
        limit: 10,
        auth_state: 0,
      },
      list: [],
      pages: 0,
      loading: false,
      submitting: false,
      merchantInfo: {},
      detailVisible: false,
      detailInfo: {},
      rejectVisible: false,
      rejectTarget: null,
      rejectForm: {
        auth_msg: "",
      },
      recentActionSummary: "待开始：请先确认当前环境、筛选状态和商家超管权限。",
    };
  },
  computed: {
    currentEnvInfo() {
      return getCurrentEnvInfo();
    },
    envDescription() {
      return this.currentEnvInfo.is_prod
        ? "当前为正式环境，审核通过或驳回会直接影响线上商家入驻结果。"
        : "当前为非正式环境，适合做商家审核流程联调和灰度验收。";
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
    canAuth() {
      return this.isMerchantSuper;
    },
    detailImages() {
      return Array.isArray(this.detailInfo.images) ? this.detailInfo.images : [];
    },
    detailImageUrls() {
      return this.detailImages.map((item) => item.file_url).filter(Boolean);
    },
    pendingCount() {
      return this.list.filter((item) => Number(item.auth_state || 0) === 0).length;
    },
    rejectedCount() {
      return this.list.filter((item) => Number(item.auth_state || 0) === 2).length;
    },
    submitReviewTags() {
      return {
        role: this.canAuth ? "权限：可执行审核" : "权限：只读查看",
        scope: `筛选：${this.query.auth_state === "" ? "全部" : this.statusText(this.query.auth_state)}`,
        pending: `待审核：${this.pendingCount} 条`,
        rejected: `已驳回：${this.rejectedCount} 条`,
      };
    },
    submitReviewHint() {
      return this.currentEnvInfo.is_prod
        ? "当前为正式环境，审核通过或驳回会直接影响真实入驻结果，请逐条核对资料和审核说明。"
        : "当前为非正式环境，建议先完整验证待审核、通过、驳回、详情查看和重提回显流程。";
    },
    submitRiskHint() {
      if (!this.canAuth) {
        return "当前账号没有审核权限，只能查看商家资料。";
      }
      if (!this.list.length) {
        return "当前筛选条件下没有待处理数据，建议切换到待审核列表再继续验收。";
      }
      if (this.pendingCount <= 0 && this.query.auth_state === 0) {
        return "待审核列表为空，当前无法继续验证审核动作。";
      }
      return this.currentEnvInfo.is_prod
        ? "正式环境下建议先查看详情页中的收款码和资质图，再执行通过或驳回。"
        : "当前复核项已就绪，可继续验证审核通过、驳回原因填写和列表刷新回显。";
    },
  },
  onLoad() {
    this.updateRecentActionSummary("页面已进入，开始拉取商家超管信息。");
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
    merchantTitleText(value, fallback = "未命名商家") {
      return maskMerchantTitle(value, fallback);
    },
    updateRecentActionSummary(message) {
      this.recentActionSummary = message;
    },
    async bootstrap() {
      try {
        await this.loadMerchantInfo();
      } catch (error) {
        this.merchantInfo = {};
        this.updateRecentActionSummary("商家信息加载失败，当前无法确认审核权限。");
        uni.showToast({
          icon: "none",
          title: "商家信息加载失败",
        });
        return;
      }
      if (!this.ensureMerchantSuperAccess()) {
        return;
      }
      this.updateRecentActionSummary("商家超管权限校验通过，开始加载审核列表。");
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
      this.updateRecentActionSummary("权限校验未通过，当前账号仅可返回上一页。");
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
      this.updateRecentActionSummary(`已刷新${this.query.auth_state === "" ? "全部" : this.statusText(this.query.auth_state)}列表，准备重新拉取数据。`);
      await this.getList();
    },
    async getList() {
      if (!this.ensureMerchantSuperAccess()) {
        return;
      }
      this.loading = true;
      try {
        const res = await api.adminMerchantList(this.query);
        const data = res.data || {};
        const rows = Array.isArray(data.list) ? data.list.map((item) => this.normalizeMerchantRow(item)) : [];
        this.list = this.query.page === 1 ? rows : this.list.concat(rows);
        this.pages = Number(data.pages || 0);
        const currentLabel = this.query.auth_state === "" ? "全部" : this.statusText(this.query.auth_state);
        this.updateRecentActionSummary(`已加载${currentLabel}商家 ${rows.length} 条，当前第 ${this.query.page} 页，共 ${this.pages || 1} 页。`);
      } catch (error) {
        if (this.query.page === 1) {
          this.list = [];
        }
        this.pages = 0;
        this.updateRecentActionSummary("商家审核列表加载失败，请检查接口或稍后重试。");
        uni.showToast({
          icon: "none",
          title: "列表加载失败，请稍后重试",
        });
      } finally {
        this.loading = false;
      }
    },
    switchTab(value) {
      this.query.auth_state = value;
      this.updateRecentActionSummary(`已切换到${value === "" ? "全部" : this.statusText(value)}筛选，列表即将刷新。`);
      this.reloadList();
    },
    statusText(status) {
      switch (Number(status || 0)) {
        case 1:
          return "已通过";
        case 2:
          return "已驳回";
        default:
          return "待审核";
      }
    },
    resolveStatusClass(status) {
      switch (Number(status || 0)) {
        case 1:
          return "is-success";
        case 2:
          return "is-danger";
        default:
          return "is-warning";
      }
    },
    normalizeMerchantRow(item) {
      return {
        ...item,
        status_class: this.resolveStatusClass(item.auth_state),
      };
    },
    async openDetail(item) {
      if (!this.ensureMerchantSuperAccess()) {
        return;
      }
      try {
        const res = await api.adminMerchantInfo({ id: item.id });
        this.detailInfo = res.data || {};
        this.detailVisible = true;
        this.updateRecentActionSummary(`已打开 ${this.merchantTitleText(item.title, "未命名商家")} 的入驻资料。`);
      } catch (error) {
        this.updateRecentActionSummary("商家详情加载失败，未能打开资料面板。");
        uni.showToast({
          icon: "none",
          title: "资料加载失败",
        });
      }
    },
    closeDetail() {
      this.detailVisible = false;
      this.detailInfo = {};
      this.updateRecentActionSummary("已关闭入驻资料面板，返回审核列表。");
    },
    previewImageList(urls, current) {
      const list = (urls || []).filter(Boolean);
      if (!list.length) {
        this.updateRecentActionSummary("当前资料中没有可预览的图片。");
        return;
      }
      this.updateRecentActionSummary(`已预览资料图片，共 ${list.length} 张。`);
      uni.previewImage({
        urls: list,
        current: current || list[0],
      });
    },
    async submitAuth(item, authState, authMsg = "") {
      if (!this.ensureMerchantSuperAccess() || this.submitting) {
        return;
      }
      const actionText = authState === 1 ? "审核通过" : "驳回";
      this.updateRecentActionSummary(`已发起${actionText}确认，目标商家：${this.merchantTitleText(item.title, "未命名商家")}。`);
      uni.showModal({
        title: actionText,
        content: this.currentEnvInfo.is_prod
          ? `当前为正式环境，确认要对该商家执行${actionText}吗？`
          : `当前为${this.currentEnvInfo.label}，确认继续执行${actionText}吗？`,
        success: async (modalRes) => {
          if (!modalRes.confirm) {
            this.updateRecentActionSummary(`${actionText}操作已取消，未提交审核变更。`);
            return;
          }
          this.submitting = true;
          try {
            await api.adminMerchantAuth({
              ids: [item.id],
              auth_state: authState,
              auth_msg: authMsg,
            });
            uni.showToast({
              icon: "none",
              title: authState === 1 ? "审核通过" : "已驳回",
            });
            this.closeReject();
            this.updateRecentActionSummary(`${this.merchantTitleText(item.title, "未命名商家")}${actionText}成功，列表已准备刷新。`);
            await this.reloadList();
          } catch (error) {
            this.updateRecentActionSummary(`${this.merchantTitleText(item.title, "未命名商家")}${actionText}失败，请稍后重试。`);
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
      this.rejectTarget = item;
      this.rejectForm.auth_msg = item.auth_msg || "";
      this.rejectVisible = true;
      this.updateRecentActionSummary(`已打开驳回弹窗，目标商家：${this.merchantTitleText(item.title, "未命名商家")}。`);
    },
    closeReject() {
      this.rejectVisible = false;
      this.rejectTarget = null;
      this.rejectForm.auth_msg = "";
      this.updateRecentActionSummary("已关闭驳回弹窗，未提交驳回说明。");
    },
    confirmReject() {
      if (!this.rejectTarget) {
        return;
      }
      if (!this.rejectForm.auth_msg) {
        this.updateRecentActionSummary("驳回提交被拦截：缺少驳回原因。");
        uni.showToast({
          icon: "none",
          title: "请输入驳回原因",
        });
        return;
      }
      this.updateRecentActionSummary(`已填写驳回原因，准备提交 ${this.merchantTitleText(this.rejectTarget.title, "未命名商家")} 的驳回审核。`);
      this.submitAuth(this.rejectTarget, 2, this.rejectForm.auth_msg);
    },
  },
};
</script>

<style scoped lang="scss">
.audit-page {
  min-height: 100vh;
  padding: 24rpx;
  background:
    radial-gradient(circle at top right, rgba(243, 143, 90, 0.22), transparent 32%),
    radial-gradient(circle at top left, rgba(255, 107, 136, 0.08), transparent 28%),
    linear-gradient(180deg, #f9f3eb 0%, #f3f6fa 42%, #edf2f6 100%);
}

.hero-card,
.audit-card,
.sheet-card,
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

.card-list {
  margin-top: 20rpx;
}

.audit-card + .audit-card {
  margin-top: 18rpx;
}

.audit-card {
  padding: 28rpx;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.99) 0%, rgba(249, 250, 252, 0.98) 100%);
}

.card-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 20rpx;
}

.merchant-title {
  font-size: 30rpx;
  font-weight: 700;
  color: #172333;
}

.merchant-subtitle {
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

.status-pill.is-success {
  background: rgba(29, 143, 95, 0.12);
  color: #1d8f5f;
}

.status-pill.is-warning {
  background: rgba(236, 138, 87, 0.12);
  color: #cf6b45;
}

.status-pill.is-danger {
  background: rgba(225, 87, 89, 0.12);
  color: #d64545;
}

.meta-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 18rpx;
  margin-top: 22rpx;
}

.meta-item {
  padding: 18rpx 20rpx;
  border-radius: 20rpx;
  background: linear-gradient(180deg, #f7fafc 0%, #eef3f7 100%);
  border: 1rpx solid rgba(228, 233, 240, 0.9);
}

.meta-item--full {
  grid-column: 1 / -1;
}

.meta-label {
  display: block;
  font-size: 22rpx;
  color: #7b8797;
}

.meta-value {
  display: block;
  margin-top: 10rpx;
  font-size: 25rpx;
  color: #243142;
  line-height: 1.6;
}

.meta-value--danger,
.danger-text {
  color: #d64545;
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

.sheet-card,
.dialog-card {
  width: 100%;
}

.sheet-card {
  max-height: 78vh;
  padding: 28rpx;
}

.sheet-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.sheet-title,
.dialog-title,
.group-title {
  font-size: 30rpx;
  font-weight: 700;
  color: #172333;
}

.close-icon {
  font-size: 34rpx;
  color: #7b8797;
}

.sheet-scroll {
  max-height: 66vh;
  margin-top: 18rpx;
}

.sheet-group + .sheet-group {
  margin-top: 20rpx;
}

.sheet-row {
  display: flex;
  justify-content: space-between;
  gap: 16rpx;
  padding: 18rpx 0;
  border-bottom: 1rpx solid rgba(123, 135, 151, 0.16);
}

.sheet-label {
  font-size: 24rpx;
  color: #718093;
}

.sheet-value {
  flex: 1;
  text-align: right;
  font-size: 24rpx;
  color: #243142;
  line-height: 1.6;
}

.image-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16rpx;
  margin-top: 18rpx;
}

.image-grid.single {
  grid-template-columns: repeat(1, minmax(0, 1fr));
}

.preview-image {
  width: 100%;
  height: 220rpx;
  border-radius: 20rpx;
}

.empty-inline {
  margin-top: 18rpx;
  font-size: 24rpx;
  color: #7f8a99;
}

.dialog-card {
  padding: 28rpx;
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
  .audit-page {
    padding: 20rpx;
  }

  .hero-card,
  .audit-card,
  .sheet-card,
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

  .card-head {
    flex-direction: column;
    align-items: flex-start;
  }

  .meta-grid {
    grid-template-columns: repeat(1, minmax(0, 1fr));
  }
}
</style>
