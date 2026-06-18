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
    <view class="accord-center-page">
      <view class="hero-card">
        <view class="hero-badge">协议中心</view>
        <view class="hero-title">{{ text.title }}</view>
        <view class="hero-subtitle">{{ text.subtitle }}</view>
      </view>

      <view class="env-card">
        <view class="env-card__head">
          <view>
            <view class="env-card__title">当前环境</view>
            <view class="env-card__desc">{{ envDescription }}</view>
          </view>
          <view
            class="env-card__badge"
            :class="currentEnvInfo.is_prod ? 'is-prod' : 'is-test'"
            >{{ currentEnvInfo.label }}</view
          >
        </view>
        <view class="env-card__meta">
          <text>{{ loginStatusText }}</text>
          <text>{{ pendingSummaryText }}</text>
          <text>{{ envUsageText }}</text>
          <text>{{ envIsolationStatusText }}</text>
          <text>{{ envReleaseStageText }}</text>
          <text>{{ currentEnvInfo.api_root_url || "未配置接口地址" }}</text>
        </view>
        <view class="env-card__summary">{{ envReleaseHint }}</view>
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
        <view class="env-card__actions">
          <view class="env-card__action" @tap="openLoginOrRefresh">{{
            envPrimaryActionText
          }}</view>
        </view>
      </view>

      <view class="sync-card">
        <view class="sync-card__head">
          <view>
            <view class="sync-card__title">补记同步状态</view>
            <view class="sync-card__desc">{{ syncSummaryText }}</view>
          </view>
          <view class="sync-card__badge" :class="syncBadgeClass">{{
            syncBadgeText
          }}</view>
        </view>
        <view class="sync-card__tags">
          <text class="sync-card__tag">{{ syncStatusTags.pending }}</text>
          <text class="sync-card__tag">{{ syncStatusTags.lastScene }}</text>
          <text class="sync-card__tag">{{ syncStatusTags.lastSuccess }}</text>
          <text class="sync-card__tag">{{ syncStatusTags.lastFailure }}</text>
        </view>
        <view v-if="syncRiskText" class="sync-card__risk">{{
          syncRiskText
        }}</view>
        <view class="sync-card__actions">
          <view
            class="sync-card__action"
            :class="{ 'is-disabled': !canRetryPending }"
            @tap="retryAllPending"
          >
            一键重试待补记
          </view>
        </view>
      </view>

      <view class="review-card">
        <view class="review-card__title">操作前复核</view>
        <view class="review-card__desc">{{ accordReviewHint }}</view>
        <view class="review-card__tags">
          <text class="review-card__tag">{{ accordReviewTags.login }}</text>
          <text class="review-card__tag">{{ accordReviewTags.pending }}</text>
          <text class="review-card__tag">{{ accordReviewTags.accepted }}</text>
          <text class="review-card__tag">{{ accordReviewTags.env }}</text>
        </view>
        <view class="review-card__risk">{{ accordRiskHint }}</view>
      </view>

      <view v-if="recentActionSummary" class="action-card">
        <view class="action-card__title">最近操作</view>
        <view class="action-card__desc">{{ recentActionSummary }}</view>
      </view>

      <view class="list-card">
        <view
          v-for="item in accords"
          :key="item.accord_id"
          class="accord-item"
          @tap="openAccord(item.accord_id)"
        >
          <view class="accord-main">
            <view class="accord-title-row">
              <view class="accord-title">{{ item.title }}</view>
              <view
                class="accord-status"
                :class="{ accepted: item.accepted, pending: item.pending }"
              >
                {{
                  item.accepted
                    ? text.accepted
                    : item.pending
                      ? text.pending
                      : statusText
                }}
              </view>
            </view>
            <view class="accord-desc">{{ item.desc }}</view>
            <view
              v-if="item.accepted && item.latest_accept_time"
              class="accord-time"
            >
              {{ text.latestTime }}{{ item.latest_accept_time }}
            </view>
            <view
              v-if="item.accepted && item.latest_scene_title"
              class="accord-time"
            >
              {{ text.latestScene }}{{ item.latest_scene_title }}
            </view>
            <view v-if="item.pending" class="accord-time accord-time--pending">
              {{ text.pendingHint }}
            </view>
            <view
              v-if="item.pending && item.pending_scene_title"
              class="accord-time accord-time--pending"
            >
              最近待补记场景：{{ item.pending_scene_title }}
            </view>
            <view
              v-if="item.pending && item.pending_updated_at"
              class="accord-time accord-time--pending"
            >
              最近待补记时间：{{ item.pending_updated_at }}
            </view>
            <view v-if="getActionLabel(item)" class="accord-actions">
              <view class="accord-action-btn" @tap.stop="handleAction(item)">{{
                getActionLabel(item)
              }}</view>
            </view>
          </view>
          <view class="accord-arrow">{{ text.view }}</view>
        </view>
      </view>
    </view>
  </view>
</template>

<script>
import api from "@/api";
import cache from "@/utils/cache";
import {
  bestEffortAcceptAccords,
  getAccordRuntimeSummary,
  getPendingAccords,
  retryPendingAccords,
} from "@/utils/accord-accept.js";
import {
  getCurrentEnvInfo,
  getEnvIsolationHealth,
  getProfileReadinessList,
} from "@/utils/env-runtime.js";
import {
  getEnvIsolationTag,
  getEnvReleaseHint,
  getEnvReleaseStageText,
} from "@/utils/env-risk.js";
import {
  buildLoginRedirectUrl,
  setLoginRedirect,
} from "@/utils/login-redirect.js";

const TEXT = {
  title: "\u534f\u8bae\u4e2d\u5fc3",
  subtitle:
    "\u8fd9\u91cc\u53ef\u4ee5\u67e5\u770b\u514d\u8d23\u58f0\u660e\u3001\u7528\u6237\u534f\u8bae\u3001\u9690\u79c1\u653f\u7b56\u548c\u552e\u540e\u8bf4\u660e\u3002",
  accepted: "\u5df2\u540c\u610f",
  pending: "\u5f85\u8865\u8bb0",
  notAccepted: "\u672a\u540c\u610f",
  needLogin: "\u767b\u5f55\u540e\u67e5\u770b",
  loadFailed:
    "\u72b6\u6001\u52a0\u8f7d\u5931\u8d25\uff0c\u8bf7\u68c0\u67e5\u63a5\u53e3\u5730\u5740\u6216\u7f51\u7edc",
  latestTime: "\u6700\u8fd1\u4e00\u6b21\u540c\u610f\u65f6\u95f4\uff1a",
  latestScene: "\u540c\u610f\u6765\u6e90\u573a\u666f\uff1a",
  pendingHint:
    "\u534f\u8bae\u8bb0\u5f55\u5f85\u8865\u8bb0\uff0c\u7cfb\u7edf\u4f1a\u5728\u540e\u7eed\u8bf7\u6c42\u4e2d\u81ea\u52a8\u91cd\u8bd5\u3002",
  view: "\u67e5\u770b",
  goComplete: "\u53bb\u5b8c\u6210",
  retrySync: "\u91cd\u8bd5\u540c\u6b65",
  relogin: "\u91cd\u65b0\u767b\u5f55",
  patchAgree: "\u8865\u8bb0\u540c\u610f",
  goApply: "\u53bb\u5165\u9a7b",
  goOrder: "\u53bb\u4e0b\u5355",
  syncDone: "\u534f\u8bae\u72b6\u6001\u5df2\u5237\u65b0",
  patchDone: "\u534f\u8bae\u8bb0\u5f55\u5df2\u8865\u8bb0",
};

export default {
  data() {
    return {
      text: TEXT,
      accords: [
        {
          accord_id: "disclaimer",
          title: "\u514d\u8d23\u58f0\u660e",
          desc: "\u67e5\u770b\u5e73\u53f0\u514d\u8d23\u58f0\u660e\u4e0e\u4f7f\u7528\u8fb9\u754c\u8bf4\u660e\u3002",
          accepted: false,
          pending: false,
          latest_accept_time: "",
          latest_scene_title: "",
        },
        {
          accord_id: "user_agreement",
          title: "\u7528\u6237\u534f\u8bae",
          desc: "\u67e5\u770b\u5e73\u53f0\u7528\u6237\u534f\u8bae\u4e0e\u670d\u52a1\u4f7f\u7528\u89c4\u5219\u3002",
          accepted: false,
          pending: false,
          latest_accept_time: "",
          latest_scene_title: "",
        },
        {
          accord_id: "privacy_policy",
          title: "\u9690\u79c1\u653f\u7b56",
          desc: "\u67e5\u770b\u4e2a\u4eba\u4fe1\u606f\u6536\u96c6\u3001\u4f7f\u7528\u548c\u4fdd\u62a4\u8bf4\u660e\u3002",
          accepted: false,
          pending: false,
          latest_accept_time: "",
          latest_scene_title: "",
        },
        {
          accord_id: "after_sales_policy",
          title: "\u552e\u540e/\u9000\u8d27\u8bf4\u660e",
          desc: "\u67e5\u770b\u4e0b\u5355\u3001\u552e\u540e\u3001\u9000\u6b3e\u548c\u9000\u8d27\u89c4\u5219\u3002",
          accepted: false,
          pending: false,
          latest_accept_time: "",
          latest_scene_title: "",
        },
      ],
      statusText: TEXT.notAccepted,
      recentActionSummary: "",
      accordRuntimeSummary: getAccordRuntimeSummary(),
    };
  },
  computed: {
    currentEnvInfo() {
      return getCurrentEnvInfo();
    },
    envDescription() {
      return this.currentEnvInfo.is_prod
        ? "当前为正式环境，协议补记与状态刷新会对接线上真实账号。"
        : "当前为非正式环境，适合核对协议默认态、补记逻辑和跳转链路。";
    },
    loginStatusText() {
      return cache.get("token")
        ? "当前账号已登录，可直接补记和刷新协议状态"
        : "当前未登录，需登录后查看协议同意状态";
    },
    pendingSummaryText() {
      const pendingCount = this.accords.filter((item) => item.pending).length;
      return pendingCount > 0
        ? `待补记协议：${pendingCount} 项`
        : "待补记协议：0 项";
    },
    envUsageText() {
      if (this.currentEnvInfo.is_prod) {
        return "入口用途：正式运营";
      }
      if (this.currentEnvInfo.key === "gray") {
        return "入口用途：灰度验收";
      }
      return `入口用途：${getEnvIsolationTag(this.currentEnvInfo)}`;
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
    envPrimaryActionText() {
      return cache.get("token") ? "刷新协议状态" : "前往登录";
    },
    canRetryPending() {
      return cache.get("token") && this.accordRuntimeSummary.pending_count > 0;
    },
    acceptedCount() {
      return this.accords.filter((item) => item.accepted).length;
    },
    syncBadgeText() {
      if (!cache.get("token")) {
        return "待登录";
      }
      if (this.accordRuntimeSummary.pending_count > 0) {
        return "待补记";
      }
      if (this.accordRuntimeSummary.last_attempt_status === "fail") {
        return "需复核";
      }
      if (this.accordRuntimeSummary.last_attempt_status === "success") {
        return "已同步";
      }
      return "待检查";
    },
    syncBadgeClass() {
      if (!cache.get("token")) {
        return "is-neutral";
      }
      if (this.accordRuntimeSummary.pending_count > 0) {
        return "is-pending";
      }
      if (this.accordRuntimeSummary.last_attempt_status === "fail") {
        return "is-risk";
      }
      if (this.accordRuntimeSummary.last_attempt_status === "success") {
        return "is-success";
      }
      return "is-neutral";
    },
    syncSummaryText() {
      if (!cache.get("token")) {
        return "登录后可查看协议补记状态，并执行待补记重试。";
      }
      if (this.accordRuntimeSummary.pending_count > 0) {
        return `当前仍有 ${this.accordRuntimeSummary.pending_count} 项协议待补记，建议继续重试直至回落为 0。`;
      }
      if (this.accordRuntimeSummary.last_attempt_status === "success") {
        return "最近一次协议补记/刷新已成功完成，可继续进行登录、下单或商家入驻操作。";
      }
      if (this.accordRuntimeSummary.last_attempt_status === "fail") {
        return "最近一次协议补记失败，请检查当前环境地址、登录态和接口状态。";
      }
      return "当前还没有触发协议补记或刷新，可先通过登录、下单或入驻链路触发。";
    },
    syncStatusTags() {
      return {
        pending: `待补记：${this.accordRuntimeSummary.pending_count} 项`,
        lastScene: `最近场景：${this.formatScene(this.accordRuntimeSummary.last_scene)}`,
        lastSuccess: `最近成功：${this.formatTime(this.accordRuntimeSummary.last_success_at)}`,
        lastFailure: `最近失败：${this.formatTime(this.accordRuntimeSummary.last_failure_at)}`,
      };
    },
    syncRiskText() {
      if (!cache.get("token")) {
        return "未登录时只能查看协议正文，无法确认补记状态是否已真正落库。";
      }
      if (this.accordRuntimeSummary.pending_count > 0) {
        return "存在待补记协议时，说明接口写入仍未完全落地，建议先完成重试再继续关键提交动作。";
      }
      if (this.accordRuntimeSummary.last_attempt_status === "fail") {
        return this.accordRuntimeSummary.last_error_message
          ? `最近失败原因：${this.accordRuntimeSummary.last_error_message}`
          : "最近一次协议补记失败，请复核网络、登录态和接口返回。";
      }
      return "";
    },
    accordReviewHint() {
      return cache.get("token")
        ? "当前已支持协议状态刷新、补记同意和业务入口承接，提交前请先确认当前环境。"
        : "当前未登录，补记同意和状态刷新都会先走登录承接。";
    },
    accordReviewTags() {
      return {
        login: cache.get("token") ? "登录状态：已登录" : "登录状态：未登录",
        pending: this.pendingSummaryText,
        accepted: `已同意协议：${this.acceptedCount} 项`,
        env: this.envUsageText,
      };
    },
    accordRiskHint() {
      if (!cache.get("token")) {
        return "未登录状态下只能查看协议正文，补记和状态刷新会先跳登录。";
      }
      return this.currentEnvInfo.is_prod
        ? "当前为正式环境，协议补记与状态刷新会作用于真实账号，请确认后再操作。"
        : "当前为非正式环境，建议优先验证协议默认态、补记逻辑和业务跳转链路。";
    },
  },
  onShow() {
    this.refreshRuntimeSummary();
    this.loadStatus();
  },
  methods: {
    formatTime(value) {
      if (!value) {
        return "暂无";
      }
      const date = new Date(value);
      if (Number.isNaN(date.getTime())) {
        return String(value);
      }
      return date.toLocaleString("zh-CN", { hour12: false });
    },
    formatScene(scene) {
      const sceneText = String(scene || "").trim();
      if (!sceneText) {
        return "暂无";
      }
      const sceneMap = {
        login: "登录",
        merchant_apply: "商家入驻",
        order_confirm: "订单结算",
        default: "默认场景",
      };
      return sceneMap[sceneText] || sceneText;
    },
    refreshRuntimeSummary() {
      this.accordRuntimeSummary = getAccordRuntimeSummary();
    },
    setRecentActionSummary(action, extra = "") {
      this.recentActionSummary = `已执行${action}${extra ? `，${extra}` : ""}。`;
    },
    openAccord(accordId) {
      this.setRecentActionSummary("查看协议正文", accordId);
      uni.navigateTo({
        url: "/pages/system/accord?accord_id=" + accordId,
      });
    },
    openLoginOrRefresh() {
      if (!cache.get("token")) {
        this.setRecentActionSummary("前往登录", "来源：协议中心");
        setLoginRedirect("/pages/system/accord-center");
        uni.navigateTo({
          url: buildLoginRedirectUrl("/pages/system/accord-center"),
        });
        return;
      }
      this.setRecentActionSummary("刷新协议状态");
      this.loadStatus();
    },
    getActionLabel(item) {
      if (item.accepted) {
        return "";
      }
      if (item.pending) {
        return this.text.retrySync;
      }
      if (!cache.get("token")) {
        return this.text.relogin;
      }
      if (
        item.accord_id === "user_agreement" ||
        item.accord_id === "privacy_policy"
      ) {
        return this.text.patchAgree;
      }
      if (item.accord_id === "disclaimer") {
        return this.text.goApply;
      }
      if (item.accord_id === "after_sales_policy") {
        return this.text.goOrder;
      }
      return this.text.goComplete;
    },
    handleAction(item) {
      if (!cache.get("token")) {
        this.setRecentActionSummary("前往登录", `协议：${item.accord_id}`);
        setLoginRedirect("/pages/system/accord-center");
        uni.navigateTo({
          url: buildLoginRedirectUrl("/pages/system/accord-center"),
        });
        return;
      }
      if (item.pending) {
        this.setRecentActionSummary("重试协议补记", item.accord_id);
        retryPendingAccords()
          .then(() => {
            this.refreshRuntimeSummary();
            uni.showToast({
              icon: "none",
              title: this.text.syncDone,
            });
            this.loadStatus();
          })
          .catch(() => {
            this.loadStatus();
          });
        return;
      }
      if (
        item.accord_id === "user_agreement" ||
        item.accord_id === "privacy_policy"
      ) {
        this.setRecentActionSummary("补记协议同意", item.accord_id);
        bestEffortAcceptAccords(
          {
            scene: "login",
            accord_uniques: [item.accord_id],
          },
          {
            toast: true,
            message: this.text.pendingHint,
          },
        ).then(() => {
          this.refreshRuntimeSummary();
          uni.showToast({
            icon: "none",
            title: this.text.patchDone,
          });
          this.loadStatus();
        });
        return;
      }
      if (item.accord_id === "disclaimer") {
        this.setRecentActionSummary("前往商家入驻", "免责声明未完成");
        uni.navigateTo({
          url: "/pages/merchant/apply",
        });
        return;
      }
      if (item.accord_id === "after_sales_policy") {
        this.setRecentActionSummary("前往下单入口", "售后说明未完成");
        uni.switchTab({
          url: "/pages/app/home",
        });
      }
    },
    retryAllPending() {
      if (!this.canRetryPending) {
        const title = cache.get("token")
          ? "当前没有待补记协议"
          : "请先登录后再重试";
        uni.showToast({
          icon: "none",
          title,
        });
        return;
      }
      this.setRecentActionSummary(
        "一键重试待补记协议",
        `${this.accordRuntimeSummary.pending_count} 项`,
      );
      retryPendingAccords()
        .then(() => {
          this.refreshRuntimeSummary();
          uni.showToast({
            icon: "none",
            title: this.text.syncDone,
          });
          this.loadStatus();
        })
        .catch(() => {
          this.refreshRuntimeSummary();
          this.loadStatus();
        });
    },
    async ensureMerchantDisclaimerAccepted(statusMap = {}) {
      const disclaimerStatus = statusMap.disclaimer || {};
      if (Number(disclaimerStatus.accepted || 0) === 1) {
        return false;
      }
      try {
        const merchantRes = await api.merchantInfo({});
        const merchant = (merchantRes && merchantRes.data) || {};
        if (Number(merchant.auth_state || 0) !== 1) {
          return false;
        }
        await bestEffortAcceptAccords(
          {
            scene: "merchant_apply",
            accord_uniques: ["disclaimer"],
          },
          {
            toast: false,
          },
        );
        return true;
      } catch (error) {
        return false;
      }
    },
    applyPendingState() {
      const pendingMap = getPendingAccords();
      this.accords = this.accords.map((item) => ({
        ...item,
        pending: !!pendingMap[item.accord_id],
        pending_scene_title: this.formatScene(
          pendingMap[item.accord_id] && pendingMap[item.accord_id].scene,
        ),
        pending_updated_at: this.formatTime(
          pendingMap[item.accord_id] && pendingMap[item.accord_id].updated_at,
        ),
      }));
      this.refreshRuntimeSummary();
    },
    async loadStatus() {
      this.applyPendingState();

      if (!cache.get("token")) {
        this.statusText = this.text.needLogin;
        this.accords = this.accords.map((item) => ({
          ...item,
          accepted: false,
          latest_accept_time: "",
          latest_scene_title: "",
        }));
        this.refreshRuntimeSummary();
        return;
      }

      try {
        await retryPendingAccords();
      } catch (error) {}

      this.applyPendingState();

      api
        .getAccordStatus({
          accord_uniques: this.accords.map((item) => item.accord_id),
        })
        .then(async (res) => {
          const statusMap = {};
          (res.data || []).forEach((item) => {
            statusMap[item.accord_unique] = item;
          });

          const patched =
            await this.ensureMerchantDisclaimerAccepted(statusMap);
          if (patched) {
            this.loadStatus();
            return;
          }

          const pendingMap = getPendingAccords();
          this.statusText = this.text.notAccepted;
          this.accords = this.accords.map((item) => {
            const status = statusMap[item.accord_id] || {};
            return {
              ...item,
              accepted: Number(status.accepted || 0) === 1,
              pending: !!pendingMap[item.accord_id],
              pending_scene_title: this.formatScene(
                pendingMap[item.accord_id] && pendingMap[item.accord_id].scene,
              ),
              pending_updated_at: this.formatTime(
                pendingMap[item.accord_id] &&
                  pendingMap[item.accord_id].updated_at,
              ),
              latest_accept_time: status.latest_accept_time || "",
              latest_scene_title: status.latest_scene_title || "",
            };
          });
          this.refreshRuntimeSummary();
        })
        .catch(() => {
          this.statusText = this.text.loadFailed;
          this.accords = this.accords.map((item) => ({
            ...item,
            accepted: false,
            latest_accept_time: "",
            latest_scene_title: "",
          }));
          this.refreshRuntimeSummary();
        });
    },
  },
};
</script>

<style lang="scss">
/* #ifdef APP-PLUS */
@import "../../static/colorui/main.css";
@import "../../static/colorui/icon.css";
@import "../../static/zaiui/style/app.scss";
/* #endif */

.accord-center-page {
  min-height: 100vh;
  padding: 24rpx;
  background:
    radial-gradient(
      circle at top right,
      rgba(233, 150, 85, 0.16),
      transparent 30%
    ),
    linear-gradient(180deg, #f8f3ec 0%, #f2f6fa 100%);
}

.hero-card,
.list-card,
.env-card {
  border-radius: 28rpx;
  background: rgba(255, 255, 255, 0.96);
  box-shadow: 0 18rpx 42rpx rgba(17, 34, 51, 0.08);
}

.hero-card {
  padding: 30rpx;
  color: #fff;
  background:
    radial-gradient(
      circle at top right,
      rgba(255, 255, 255, 0.16),
      transparent 24%
    ),
    linear-gradient(135deg, #154b72 0%, #2a6f93 48%, #eb8b59 100%);
}

.hero-badge {
  display: inline-flex;
  padding: 10rpx 18rpx;
  border-radius: 999rpx;
  background: rgba(255, 255, 255, 0.16);
  font-size: 22rpx;
}

.hero-title {
  margin-top: 24rpx;
  font-size: 40rpx;
  font-weight: 700;
}

.hero-subtitle {
  margin-top: 12rpx;
  font-size: 24rpx;
  line-height: 1.7;
  color: rgba(255, 255, 255, 0.84);
}

.list-card {
  margin-top: 20rpx;
  padding: 0 24rpx;
}

.env-card {
  margin-top: 20rpx;
  padding: 24rpx 26rpx;
  border: 1rpx solid rgba(228, 233, 240, 0.85);
}

.env-card__head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 18rpx;
}

.env-card__title {
  font-size: 28rpx;
  font-weight: 700;
  color: #112233;
}

.env-card__desc {
  margin-top: 10rpx;
  font-size: 23rpx;
  line-height: 1.7;
  color: #647487;
}

.env-card__badge {
  padding: 10rpx 18rpx;
  border-radius: 999rpx;
  font-size: 22rpx;
  font-weight: 600;
  white-space: nowrap;
}

.env-card__badge.is-prod {
  background: rgba(207, 90, 72, 0.12);
  color: #bf4a36;
}

.env-card__badge.is-test {
  background: rgba(42, 111, 147, 0.12);
  color: #2a6f93;
}

.env-card__meta {
  display: grid;
  gap: 10rpx;
  margin-top: 14rpx;
  font-size: 22rpx;
  color: #8694a2;
  word-break: break-all;
}

.env-card__actions {
  margin-top: 14rpx;
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
  color: #c76836;
  font-size: 22rpx;
  line-height: 1.7;
}

.env-card__risk-item + .env-card__risk-item {
  margin-top: 8rpx;
}

.env-card__action {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 10rpx 20rpx;
  border-radius: 999rpx;
  background: linear-gradient(
    135deg,
    rgba(21, 75, 114, 0.12),
    rgba(235, 139, 89, 0.16)
  );
  color: #1f5f85;
  font-size: 22rpx;
  font-weight: 600;
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

.review-card,
.action-card,
.sync-card {
  margin-top: 20rpx;
  padding: 24rpx 26rpx;
  border: 1rpx solid rgba(228, 233, 240, 0.85);
  border-radius: 24rpx;
  background: rgba(255, 255, 255, 0.98);
}

.review-card__title,
.action-card__title,
.sync-card__title {
  font-size: 28rpx;
  font-weight: 700;
  color: #112233;
}

.review-card__desc,
.action-card__desc,
.sync-card__desc {
  margin-top: 10rpx;
  font-size: 23rpx;
  line-height: 1.7;
  color: #647487;
}

.sync-card__head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 18rpx;
}

.sync-card__badge {
  padding: 10rpx 18rpx;
  border-radius: 999rpx;
  font-size: 22rpx;
  font-weight: 600;
  white-space: nowrap;
}

.sync-card__badge.is-success {
  background: rgba(42, 111, 147, 0.12);
  color: #2a6f93;
}

.sync-card__badge.is-pending,
.sync-card__badge.is-risk {
  background: rgba(235, 139, 89, 0.14);
  color: #c76836;
}

.sync-card__badge.is-neutral {
  background: rgba(107, 124, 143, 0.12);
  color: #6b7c8f;
}

.sync-card__tags {
  display: flex;
  flex-wrap: wrap;
  gap: 12rpx;
  margin-top: 14rpx;
}

.sync-card__tag {
  display: inline-flex;
  align-items: center;
  min-height: 40rpx;
  padding: 0 16rpx;
  border-radius: 999rpx;
  background: rgba(42, 111, 147, 0.08);
  color: #4c6278;
  font-size: 21rpx;
}

.sync-card__risk {
  margin-top: 14rpx;
  padding: 16rpx 18rpx;
  border-radius: 18rpx;
  background: rgba(255, 244, 232, 0.98);
  color: #c76836;
  font-size: 22rpx;
  line-height: 1.7;
}

.sync-card__actions {
  margin-top: 14rpx;
}

.sync-card__action {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 10rpx 20rpx;
  border-radius: 999rpx;
  background: linear-gradient(
    135deg,
    rgba(21, 75, 114, 0.12),
    rgba(235, 139, 89, 0.16)
  );
  color: #1f5f85;
  font-size: 22rpx;
  font-weight: 600;
}

.sync-card__action.is-disabled {
  opacity: 0.46;
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
  background: rgba(42, 111, 147, 0.12);
  color: #2a6f93;
  font-size: 21rpx;
  font-weight: 600;
}

.review-card__risk {
  margin-top: 14rpx;
  padding: 16rpx 18rpx;
  border-radius: 18rpx;
  background: rgba(255, 244, 232, 0.98);
  color: #c76836;
  font-size: 22rpx;
  line-height: 1.7;
}

.accord-item {
  display: flex;
  align-items: center;
  gap: 18rpx;
  padding: 28rpx 0;
  border-bottom: 1rpx solid #edf2f6;
}

.accord-item:last-child {
  border-bottom: 0;
}

.accord-main {
  flex: 1;
  min-width: 0;
}

.accord-title-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16rpx;
}

.accord-title {
  font-size: 30rpx;
  font-weight: 700;
  color: #10283d;
}

.accord-status {
  padding: 6rpx 14rpx;
  border-radius: 999rpx;
  background: #edf2f7;
  color: #6b7c8f;
  font-size: 22rpx;
}

.accord-status.accepted {
  background: rgba(42, 111, 147, 0.12);
  color: #2a6f93;
}

.accord-status.pending {
  background: rgba(235, 139, 89, 0.12);
  color: #c76836;
}

.accord-desc {
  margin-top: 10rpx;
  font-size: 24rpx;
  line-height: 1.7;
  color: #607181;
}

.accord-time {
  margin-top: 10rpx;
  font-size: 22rpx;
  color: #8a97a4;
}

.accord-time--pending {
  color: #c76836;
}

.accord-actions {
  margin-top: 14rpx;
}

.accord-action-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 10rpx 20rpx;
  border-radius: 999rpx;
  background: linear-gradient(
    135deg,
    rgba(21, 75, 114, 0.12),
    rgba(235, 139, 89, 0.16)
  );
  color: #1f5f85;
  font-size: 22rpx;
  font-weight: 600;
}

.accord-arrow {
  font-size: 24rpx;
  color: #2a6f93;
}
</style>

