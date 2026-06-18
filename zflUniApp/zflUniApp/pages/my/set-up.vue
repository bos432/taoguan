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
    <view class="settings-page">
      <view class="hero-card">
        <view class="hero-badge">账号设置</view>
        <view class="hero-profile">
          <image
            class="hero-avatar"
            :src="displayAvatarUrl"
            mode="aspectFill"
          ></image>
          <view class="hero-userinfo">
            <view class="hero-title">{{ displayName }}</view>
            <view class="hero-subtitle">{{ displayPhone }}</view>
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
        <view class="section-tip env-desc">{{ envDescription }}</view>
        <view class="env-tags">
          <text class="env-tag">{{ loginIdentityText }}</text>
          <text class="env-tag">{{ profileEditHint }}</text>
          <text class="env-tag">{{ envUsageText }}</text>
          <text class="env-tag">{{ envIsolationStatusText }}</text>
          <text class="env-tag">{{ envReleaseStageText }}</text>
          <text class="env-tag">{{ envProfileSourceText }}</text>
        </view>
        <view class="env-note">{{ envActionHint }}</view>
        <view class="env-note env-note--strong">{{ envReleaseHint }}</view>
        <view v-if="envRiskList.length" class="env-risk-list">
          <view v-for="item in envRiskList" :key="item" class="env-risk-item">{{
            item
          }}</view>
        </view>
        <view class="env-profile-board">
          <view class="env-profile-board__title">环境就绪看板</view>
          <view class="env-profile-list">
            <view
              v-for="item in profileReadinessList"
              :key="item.key"
              class="env-profile-item"
              :class="item.key === currentEnvInfo.key ? 'is-current' : ''"
            >
              <view class="env-profile-item__head">
                <text class="env-profile-item__name">{{ item.label }}</text>
                <text
                  class="env-profile-item__status"
                  :class="'is-' + ((item.status_text || '').toLowerCase())"
                >
                  {{ item.status_text }}
                </text>
              </view>
              <view class="env-profile-item__desc">{{ item.short_hint }}</view>
            </view>
          </view>
        </view>
        <view class="env-url">{{ displayApiRoot }}</view>
      </view>

      <view class="section-card review-card">
        <view class="section-head">
          <view class="section-title">修改前复核</view>
          <view class="section-tip">资料更新前确认当前账号</view>
        </view>
        <view class="review-desc">{{ submitReviewHint }}</view>
        <view class="review-tags">
          <text class="review-tag">{{ submitReviewTags.account }}</text>
          <text class="review-tag">{{ submitReviewTags.name }}</text>
          <text class="review-tag">{{ submitReviewTags.gender }}</text>
          <text class="review-tag">{{ submitReviewTags.avatar }}</text>
        </view>
        <view class="review-risk">{{ submitRiskHint }}</view>
      </view>

      <view class="section-card sync-card">
        <view class="section-head">
          <view class="section-title">协议同步状态</view>
          <view class="sync-badge" :class="protocolSyncBadgeClass">{{
            protocolSyncBadgeText
          }}</view>
        </view>
        <view class="sync-desc">{{ protocolSyncHint }}</view>
        <view class="sync-tags">
          <text class="sync-tag">{{ protocolSyncTags.pending }}</text>
          <text class="sync-tag">{{ protocolSyncTags.scene }}</text>
          <text class="sync-tag">{{ protocolSyncTags.success }}</text>
          <text class="sync-tag">{{ protocolSyncTags.failure }}</text>
        </view>
        <view v-if="protocolSyncRiskHint" class="sync-risk">{{
          protocolSyncRiskHint
        }}</view>
        <view class="sync-actions">
          <view class="sync-action" @tap="openAccordCenter">查看协议中心</view>
          <view
            class="sync-action"
            :class="{ 'sync-action--disabled': !canRetryPendingAccords }"
            @tap="retryPendingAccordRuntime"
            >重试待补记</view
          >
        </view>
      </view>

      <view class="section-card">
        <view class="section-head">
          <view class="section-title">基础信息</view>
          <view class="section-tip">点击对应项目可修改</view>
        </view>

        <view class="setting-list">
          <view class="setting-item" @tap="editAvatar">
            <view class="setting-main">
              <view class="setting-label">头像</view>
              <view class="setting-desc">上传新的个人头像</view>
            </view>
            <view class="setting-action">
              <image
                class="setting-avatar"
                :src="displayAvatarUrl"
                mode="aspectFill"
              ></image>
            </view>
          </view>

          <view class="setting-item" @tap="editNameTap">
            <view class="setting-main">
              <view class="setting-label">姓名</view>
              <view class="setting-desc">用于订单和资料展示</view>
            </view>
            <view class="setting-action text-action">
              {{ userInfo.name || "点击设置" }}
            </view>
          </view>

          <view class="setting-item">
            <view class="setting-main">
              <view class="setting-label">性别</view>
              <view class="setting-desc">可在这里更新个人资料</view>
            </view>
            <view class="setting-action text-action">
              <picker
                @change="sexPickerChange"
                :value="userInfo.gender || 0"
                :range="sexPicker"
              >
                <view class="picker-value">
                  {{ sexPicker[userInfo.gender || 0] || "保密" }}
                </view>
              </picker>
            </view>
          </view>

          <view class="setting-item static-item">
            <view class="setting-main">
              <view class="setting-label">手机号</view>
              <view class="setting-desc">当前绑定的登录手机号</view>
            </view>
            <view class="setting-action text-action">
              {{ displayPhone }}
            </view>
          </view>
        </view>
      </view>

      <view class="section-card">
        <view class="section-head">
          <view class="section-title">协议与连接</view>
          <view class="section-tip">查看协议与当前接口地址</view>
        </view>

        <view class="setting-list">
          <view class="setting-item" @tap="openAccordCenter">
            <view class="setting-main">
              <view class="setting-label">协议中心</view>
              <view class="setting-desc"
                >查看免责声明、用户协议、隐私政策和售后说明</view
              >
            </view>
            <view class="setting-action text-action">查看</view>
          </view>

          <view class="setting-item" @tap="copyApiRoot">
            <view class="setting-main">
              <view class="setting-label">当前接口地址</view>
              <view class="setting-desc">{{ displayApiRoot }}</view>
            </view>
            <view class="setting-action text-action">复制</view>
          </view>

          <view class="setting-item" @tap="copyBaseRoot">
            <view class="setting-main">
              <view class="setting-label">当前站点地址</view>
              <view class="setting-desc">{{ displayBaseRoot }}</view>
            </view>
            <view class="setting-action text-action">复制</view>
          </view>

          <view
            class="setting-item"
            :class="{ 'setting-item--switchable': allowEnvSwitch }"
            @tap="switchEnvProfile"
          >
            <view class="setting-main">
              <view class="setting-label">运行环境</view>
              <view class="setting-desc">{{ displayEnvDesc }}</view>
            </view>
            <view class="setting-action text-action">
              {{ displayEnvLabel }}
            </view>
          </view>
        </view>
      </view>

      <view class="action-area">
        <button class="logout-btn" @tap="outLogin">退出登录</button>
      </view>
    </view>
  </view>
</template>

<script>
import cache from "@/utils/cache";
import api from "@/api";
import store from "@/store/common";
import {
  getAccordRuntimeSummary,
  retryPendingAccords,
} from "@/utils/accord-accept.js";
import { buildApiUrl, getApiRootUrl } from "@/utils/resource.js";
import {
  buildProdUnlockText,
  getEnvIsolationTag,
  getEnvReleaseHint,
  getEnvReleaseStageText,
} from "@/utils/env-risk.js";
import {
  clearEnvProfileOverride,
  getAvailableProfileList,
  getCurrentEnvInfo,
  getEnvIsolationHealth,
  getEnvSwitchGuard,
  getProfileReadinessList,
  isProdProfileSwitchUnlocked,
  PROFILE_STORAGE_KEY,
  setEnvProfile,
  unlockProdProfileSwitch,
} from "@/utils/env-runtime.js";

export default {
  data() {
    return {
      avatar_img: "/static/images/avatar/1.jpg",
      default_avatar_url: "/static/images/avatar/1.jpg",
      avatarPool: [
        "/static/images/avatar/default-1.svg",
        "/static/images/avatar/default-2.svg",
        "/static/images/avatar/default-3.svg",
        "/static/images/avatar/default-4.svg",
      ],
      sexPicker: ["保密", "男", "女"],
      userInfo: {},
      accordRuntimeSummary: getAccordRuntimeSummary(),
    };
  },
  computed: {
    hasCustomAvatar() {
      const avatarId = Number(this.userInfo.avatar_id || 0);
      const headimgurl = this.normalizeText(this.userInfo.headimgurl);
      const avatarUrl = this.normalizeText(this.userInfo.avatar_url);
      const defaultAvatar = this.normalizeText(
        this.default_avatar_url || this.avatar_img,
      );
      if (avatarId > 0 || headimgurl) {
        return true;
      }
      return !!avatarUrl && avatarUrl !== defaultAvatar;
    },
    displayAvatarUrl() {
      const avatarUrl = this.normalizeText(this.userInfo.avatar_url);
      if (this.hasCustomAvatar && avatarUrl) {
        return avatarUrl;
      }
      return this.pickAvatarBySeed(this.getUserSeed());
    },
    displayName() {
      return (
        this.normalizeText(this.userInfo.name) ||
        this.normalizeText(this.userInfo.nickname) ||
        "未设置姓名"
      );
    },
    displayPhone() {
      return (
        this.normalizeText(this.userInfo.phone) ||
        this.normalizeText(this.userInfo.mobile) ||
        "未绑定手机号"
      );
    },
    displayApiRoot() {
      return getApiRootUrl() || "未配置";
    },
    currentEnvInfo() {
      return getCurrentEnvInfo();
    },
    displayBaseRoot() {
      return this.currentEnvInfo.base_root_url || "未配置";
    },
    displayEnvLabel() {
      return this.currentEnvInfo.label || "未配置";
    },
    displayEnvDesc() {
      const profile = this.currentEnvInfo.key || "unknown";
      const safeTip = this.currentEnvInfo.is_prod
        ? "正式环境，默认禁止用于开发联调。"
        : "建议开发、联调和灰度都使用非正式环境。";
      return `${profile} · ${safeTip}`;
    },
    hasEnvOverride() {
      return !!cache.get(PROFILE_STORAGE_KEY, "");
    },
    envDescription() {
      return this.currentEnvInfo.is_prod
        ? "当前为正式环境，修改头像、姓名和性别会直接更新真实账号资料。"
        : "当前为非正式环境，适合做个人资料修改和回显联调。";
    },
    loginIdentityText() {
      return this.displayPhone && this.displayPhone !== "未绑定手机号"
        ? `当前账号：${this.displayPhone}`
        : "当前账号：未绑定手机号";
    },
    profileEditHint() {
      return this.currentEnvInfo.is_prod
        ? "资料修改：真实生效"
        : "资料修改：测试联调";
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
    envProfileSourceText() {
      return this.currentEnvInfo.profile_overrides_enabled
        ? "私有配置：已启用"
        : "私有配置：未启用";
    },
    allowEnvSwitch() {
      return getAvailableProfileList().length > 1;
    },
    profileReadinessList() {
      return getProfileReadinessList();
    },
    envActionHint() {
      return this.currentEnvInfo.is_prod
        ? "切正式环境后，头像、姓名、性别和退出登录都会作用于真实账号。"
        : "建议先在当前环境完成资料修改和协议跳转验收，再切灰度或正式环境。";
    },
    submitReviewTags() {
      return {
        account: `账号：${this.displayPhone}`,
        name: `姓名：${this.displayName}`,
        gender: `性别：${this.sexPicker[Number(this.userInfo.gender || 0)] || "保密"}`,
        avatar: this.hasCustomAvatar ? "头像：已自定义" : "头像：默认头像",
      };
    },
    submitReviewHint() {
      return this.currentEnvInfo.is_prod
        ? "当前为正式环境，修改头像、姓名和性别会直接更新真实账号资料，请先确认当前登录账号。"
        : "当前为非正式环境，建议先验证头像上传、姓名修改、性别修改和资料回显。";
    },
    submitRiskHint() {
      if (
        !this.normalizeText(this.displayPhone) ||
        this.displayPhone === "未绑定手机号"
      ) {
        return "当前账号未绑定手机号，建议先确认账号身份，再执行资料修改。";
      }
      if (
        !this.normalizeText(this.displayName) ||
        this.displayName === "未设置姓名"
      ) {
        return "当前姓名未设置，建议优先补齐姓名后再做正式环境验证。";
      }
      return this.currentEnvInfo.is_prod
        ? "正式环境下资料修改会直接影响真实用户资料，请避免在错误账号上操作。"
        : "当前复核项已通过，可继续在测试环境验证资料写入和页面回显。";
    },
    protocolSyncBadgeText() {
      if (Number(this.accordRuntimeSummary.pending_count || 0) > 0) {
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
    protocolSyncBadgeClass() {
      if (Number(this.accordRuntimeSummary.pending_count || 0) > 0) {
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
    protocolSyncHint() {
      if (Number(this.accordRuntimeSummary.pending_count || 0) > 0) {
        return `当前仍有 ${this.accordRuntimeSummary.pending_count} 项协议待补记，建议先补记完成再继续关键提交或资料修改。`;
      }
      if (this.accordRuntimeSummary.last_attempt_status === "success") {
        return "最近一次协议补记已成功完成，当前可继续执行资料修改、下单和商家入驻相关操作。";
      }
      if (this.accordRuntimeSummary.last_attempt_status === "fail") {
        return "最近一次协议补记失败，建议先检查当前环境地址、登录态和接口状态。";
      }
      return "当前还没有协议补记记录，可先通过登录、下单或商家入驻链路触发一次同步。";
    },
    protocolSyncTags() {
      return {
        pending: `待补记：${Number(this.accordRuntimeSummary.pending_count || 0)} 项`,
        scene: `最近场景：${this.formatAccordScene(this.accordRuntimeSummary.last_scene)}`,
        success: `最近成功：${this.formatAccordTime(this.accordRuntimeSummary.last_success_at)}`,
        failure: `最近失败：${this.formatAccordTime(this.accordRuntimeSummary.last_failure_at)}`,
      };
    },
    protocolSyncRiskHint() {
      if (Number(this.accordRuntimeSummary.pending_count || 0) > 0) {
        return "存在待补记协议时，说明部分协议写入仍未完全落库，建议先完成补记重试。";
      }
      if (this.accordRuntimeSummary.last_attempt_status === "fail") {
        return this.accordRuntimeSummary.last_error_message
          ? `最近失败原因：${this.accordRuntimeSummary.last_error_message}`
          : "最近一次协议补记失败，请复核网络、登录态和接口返回。";
      }
      return "";
    },
    canRetryPendingAccords() {
      return Number(this.accordRuntimeSummary.pending_count || 0) > 0;
    },
  },
  onLoad() {
    this.syncDefaultAvatar();
    this.refreshAccordRuntimeSummary();
    this.getUserInfo();
  },
  onShow() {
    this.refreshAccordRuntimeSummary();
  },
  onReady() {
    uni.pageScrollTo({
      scrollTop: 0,
      duration: 0,
    });
  },
  methods: {
    formatAccordTime(value) {
      if (!value) {
        return "暂无";
      }
      const date = new Date(value);
      if (Number.isNaN(date.getTime())) {
        return String(value);
      }
      return date.toLocaleString("zh-CN", { hour12: false });
    },
    formatAccordScene(scene) {
      const sceneText = this.normalizeText(scene);
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
    refreshAccordRuntimeSummary() {
      this.accordRuntimeSummary = getAccordRuntimeSummary();
    },
    syncDefaultAvatar() {
      const cacheSetting = cache.get("setting");
      const setting =
        store.state.setting && store.state.setting.member
          ? store.state.setting.member
          : cacheSetting && cacheSetting.member;
      const defaultAvatar = this.normalizeText(
        setting && setting.default_avatar_url,
      );
      if (defaultAvatar) {
        this.default_avatar_url = defaultAvatar;
      }
    },
    sexPickerChange(e) {
      const gender = Number(e.detail.value || 0);
      const nextGenderLabel = this.sexPicker[gender] || "保密";
      uni.showModal({
        title: "修改确认",
        content: this.currentEnvInfo.is_prod
          ? `当前为正式环境，确认将性别修改为“${nextGenderLabel}”吗？`
          : `当前为${this.currentEnvInfo.label}，确认继续测试修改性别吗？`,
        success: (modalRes) => {
          if (!modalRes.confirm) {
            return;
          }
          this.userInfo.gender = gender;
          api.editUserInfo({ gender, type: 2 }).then((result) => {
            this.userInfo.gender = Number(result.data.gender || 0);
            this.syncCacheUserInfo({
              gender: this.userInfo.gender,
            });
          });
        },
      });
    },
    editNameTap() {
      uni.showModal({
        title: "请输入姓名",
        content: this.userInfo.name || "",
        editable: true,
        success: (res) => {
          if (!res.confirm) {
            return;
          }
          const value = this.normalizeText(res.content);
          if (!value) {
            uni.showToast({
              icon: "none",
              title: "请输入姓名",
            });
            return;
          }
          uni.showModal({
            title: "修改确认",
            content: this.currentEnvInfo.is_prod
              ? `当前为正式环境，确认将姓名修改为“${value}”吗？`
              : `当前为${this.currentEnvInfo.label}，确认继续测试修改姓名吗？`,
            success: (confirmRes) => {
              if (!confirmRes.confirm) {
                return;
              }
              this.userInfo.name = value;
              api.editUserInfo({ name: value, type: 1 }).then((result) => {
                this.userInfo.name = result.data.name || value;
                this.syncCacheUserInfo({
                  name: this.userInfo.name,
                });
              });
            },
          });
        },
      });
    },
    editAvatar() {
      uni.showModal({
        title: "修改确认",
        content: this.currentEnvInfo.is_prod
          ? "当前为正式环境，上传后会直接更新真实账号头像，确认继续吗？"
          : `当前为${this.currentEnvInfo.label}，确认继续测试修改头像吗？`,
        success: (modalRes) => {
          if (!modalRes.confirm) {
            return;
          }
          uni.chooseImage({
            count: 1,
            sizeType: ["original", "compressed"],
            sourceType: ["album"],
            success: (chooseImageRes) => {
              const tempFilePaths = chooseImageRes.tempFilePaths;
              uni.uploadFile({
                url: buildApiUrl("/member.Member/avatar"),
                filePath: tempFilePaths[0],
                name: "file",
                formData: {
                  file_type: "image",
                },
                header: {
                  ApiToken: cache.get("token", ""),
                },
                success: (uploadRes) => {
                  if (!uploadRes.data) {
                    return;
                  }
                  const data = JSON.parse(uploadRes.data);
                  if (data.code == 200) {
                    this.userInfo.avatar_id = data.data.file_id;
                    this.userInfo.avatar_url = data.data.file_url;
                    this.syncCacheUserInfo({
                      avatar_id: data.data.file_id,
                      avatar_url: data.data.file_url,
                    });
                    store.commit("login");
                    uni.showToast({
                      icon: "none",
                      title: "头像已更新",
                    });
                    return;
                  }
                  uni.hideToast();
                  uni.showToast({
                    icon: "none",
                    title: data.msg || "请求失败",
                  });
                },
              });
            },
          });
        },
      });
    },
    getUserInfo() {
      api
        .getMemberInfo({
          field:
            "member_id,avatar_id,headimgurl,nickname,username,phone,name,gender",
        })
        .then((res) => {
          this.userInfo = res.data || {};
          this.syncCacheUserInfo(this.userInfo);
        })
        .catch(() => {
          uni.showToast({
            icon: "none",
            title: "资料加载失败",
          });
        });
    },
    syncCacheUserInfo(patch = {}) {
      const userInfo = Object.assign({}, cache.get("userInfo") || {}, patch);
      cache.set("userInfo", userInfo, 1296000);
    },
    normalizeText(value) {
      if (value === undefined || value === null) {
        return "";
      }
      return String(value).trim();
    },
    getUserSeed() {
      const source =
        this.normalizeText(this.userInfo.member_id) ||
        this.normalizeText(this.userInfo.phone) ||
        this.normalizeText(this.userInfo.mobile) ||
        this.normalizeText(this.userInfo.nickname) ||
        "guest";
      let seed = 0;
      for (let i = 0; i < source.length; i++) {
        seed = (seed * 31 + source.charCodeAt(i)) >>> 0;
      }
      return seed || 1;
    },
    pickAvatarBySeed(seed) {
      const list =
        this.avatarPool && this.avatarPool.length
          ? this.avatarPool
          : [this.default_avatar_url || this.avatar_img];
      return (
        list[seed % list.length] || this.default_avatar_url || this.avatar_img
      );
    },
    openAccordCenter() {
      this.refreshAccordRuntimeSummary();
      uni.navigateTo({
        url: "/pages/system/accord-center",
      });
    },
    retryPendingAccordRuntime() {
      if (!this.canRetryPendingAccords) {
        uni.showToast({
          icon: "none",
          title: "当前没有待补记协议",
        });
        return;
      }
      retryPendingAccords()
        .then(() => {
          this.refreshAccordRuntimeSummary();
          uni.showToast({
            icon: "none",
            title: "协议状态已刷新",
          });
        })
        .catch(() => {
          this.refreshAccordRuntimeSummary();
        });
    },
    copyApiRoot() {
      const value = this.displayApiRoot;
      uni.setClipboardData({
        data: value,
        success: () => {
          uni.showToast({
            icon: "none",
            title: "接口地址已复制",
          });
        },
      });
    },
    copyBaseRoot() {
      const value = this.displayBaseRoot;
      uni.setClipboardData({
        data: value,
        success: () => {
          uni.showToast({
            icon: "none",
            title: "站点地址已复制",
          });
        },
      });
    },
    switchEnvProfile() {
      if (!this.allowEnvSwitch) {
        return;
      }
      const profiles = getProfileReadinessList();
      const labels = profiles.map((item) => {
        const host = this.normalizeText(
          item.api_root_url || item.base_root_url,
        );
        const label = `${item.label || item.key} · ${item.status_text}`;
        return host ? `${label} · ${host}` : label;
      });
      if (this.hasEnvOverride) {
        labels.push("恢复默认环境");
      }

      const applySwitch = (envInfo, isReset = false) => {
        uni.showToast({
          icon: "none",
          title: isReset
            ? `已恢复为${envInfo.label}`
            : `已切换到${envInfo.label}`,
        });
        setTimeout(() => {
          uni.reLaunch({
            url: "/pages/app/home",
          });
        }, 360);
      };

      uni.showActionSheet({
        itemList: labels,
        success: (res) => {
          if (this.hasEnvOverride && res.tapIndex === labels.length - 1) {
            const envInfo = clearEnvProfileOverride();
            applySwitch(envInfo, true);
            return;
          }

          const selected = profiles[res.tapIndex];
          if (!selected || selected.key === this.currentEnvInfo.key) {
            return;
          }
          if (selected.has_example_host) {
            uni.showModal({
              title: `切换到${selected.label}`,
              content: `${selected.label} 当前仍是占位域名。\n目标地址：${selected.api_root_url || selected.base_root_url || "未配置"}\n该环境只能用于页面结构和交互核对，不能做真实联调、灰度提测或写操作验收。`,
              success: (modalRes) => {
                if (modalRes.confirm) {
                  const envInfo = setEnvProfile(selected.key);
                  applySwitch(envInfo);
                }
              },
            });
            return;
          }
          const switchTargetText = selected.label || selected.key;
          const switchHostText = this.normalizeText(
            selected.api_root_url || selected.base_root_url,
          );
          const confirmContent =
            selected.key === "prod"
              ? `正式环境会连接线上真实数据。\n目标地址：${switchHostText || "未配置"}\n确认继续切换到${switchTargetText}吗？`
              : `确认切换到${switchTargetText}吗？\n目标地址：${switchHostText || "未配置"}\n切换后将返回首页刷新数据。`;

          uni.showModal({
            title: selected.key === "prod" ? "切换正式环境" : "切换运行环境",
            content: confirmContent,
            success: (modalRes) => {
              if (!modalRes.confirm) {
                return;
              }
              if (selected.key === "prod") {
                uni.showModal({
                  title: "解锁正式环境切换",
                  content: buildProdUnlockText(this.currentEnvInfo, {
                    host: switchHostText,
                  }),
                  success: (unlockRes) => {
                    if (!unlockRes.confirm) {
                      return;
                    }
                    unlockProdProfileSwitch();
                    const guard = getEnvSwitchGuard(selected.key);
                    if (!guard.allowed && !isProdProfileSwitchUnlocked()) {
                      uni.showToast({
                        icon: "none",
                        title: guard.message,
                      });
                      return;
                    }
                    const envInfo = setEnvProfile(selected.key, {
                      force_prod: true,
                    });
                    applySwitch(envInfo);
                  },
                });
                return;
              }
              const envInfo = setEnvProfile(selected.key);
              applySwitch(envInfo);
            },
          });
        },
      });
    },
    outLogin() {
      this.$store.commit("logout");
    },
  },
};
</script>

<style lang="scss" scoped>
.settings-page {
  min-height: 100vh;
  padding: calc(var(--status-bar-height) + 32rpx) 28rpx 48rpx;
  background:
    radial-gradient(
      circle at top right,
      rgba(247, 129, 96, 0.26),
      transparent 34%
    ),
    linear-gradient(180deg, #fff3ec 0%, #f7f9fc 32%, #ffffff 100%);
}

.hero-card {
  padding: 34rpx 30rpx;
  border-radius: 30rpx;
  background: linear-gradient(135deg, #1f5f8b 0%, #3b82a8 45%, #f08c5a 100%);
  box-shadow: 0 24rpx 48rpx rgba(31, 95, 139, 0.18);
}

.hero-badge {
  display: inline-flex;
  align-items: center;
  padding: 8rpx 18rpx;
  border-radius: 999rpx;
  background: rgba(255, 255, 255, 0.14);
  color: rgba(255, 255, 255, 0.94);
  font-size: 22rpx;
  letter-spacing: 2rpx;
}

.hero-profile {
  display: flex;
  align-items: center;
  margin-top: 28rpx;
}

.hero-avatar {
  width: 112rpx;
  height: 112rpx;
  border-radius: 50%;
  border: 4rpx solid rgba(255, 255, 255, 0.34);
  background: rgba(255, 255, 255, 0.18);
}

.hero-userinfo {
  flex: 1;
  min-width: 0;
  margin-left: 24rpx;
}

.hero-title {
  color: #ffffff;
  font-size: 40rpx;
  font-weight: 700;
  line-height: 1.35;
}

.hero-subtitle {
  margin-top: 10rpx;
  color: rgba(255, 255, 255, 0.82);
  font-size: 24rpx;
}

.section-card {
  margin-top: 24rpx;
  padding: 14rpx 24rpx 6rpx;
  border-radius: 28rpx;
  background: rgba(255, 255, 255, 0.94);
  box-shadow: 0 18rpx 40rpx rgba(19, 37, 66, 0.08);
}

.env-card {
  padding-bottom: 24rpx;
}

.env-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 42rpx;
  padding: 0 18rpx;
  border-radius: 999rpx;
  color: #ffffff;
  font-size: 22rpx;
  background: linear-gradient(45deg, #1cbbb4, #0081ff);
}

.env-badge.is-prod {
  background: linear-gradient(45deg, #ef4444, #f97316);
}

.env-desc {
  margin-top: 12rpx;
  text-align: left;
}

.env-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 12rpx;
  margin-top: 12rpx;
}

.env-tag {
  display: inline-flex;
  align-items: center;
  min-height: 40rpx;
  padding: 0 16rpx;
  border-radius: 999rpx;
  background: rgba(15, 23, 42, 0.06);
  color: #526274;
  font-size: 20rpx;
}

.env-note {
  margin-top: 12rpx;
  color: #6b7b8c;
  font-size: 22rpx;
  line-height: 1.7;
}

.env-note--strong {
  padding: 16rpx 18rpx;
  border-radius: 20rpx;
  background: rgba(15, 23, 42, 0.04);
  color: #35506b;
}

.env-url {
  margin: 12rpx 6rpx 0;
  color: #94a3b8;
  font-size: 22rpx;
  line-height: 1.7;
  word-break: break-all;
}

.env-profile-board {
  margin: 16rpx 6rpx 0;
  padding: 16rpx 18rpx;
  border-radius: 20rpx;
  background: rgba(15, 23, 42, 0.04);
}

.env-profile-board__title {
  color: #35506b;
  font-size: 22rpx;
  font-weight: 700;
}

.env-profile-list {
  display: flex;
  flex-direction: column;
  gap: 12rpx;
  margin-top: 12rpx;
}

.env-profile-item {
  padding: 14rpx 16rpx;
  border-radius: 16rpx;
  background: rgba(255, 255, 255, 0.92);
}

.env-profile-item.is-current {
  border: 1rpx solid rgba(28, 187, 180, 0.24);
}

.env-profile-item__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12rpx;
}

.env-profile-item__name {
  color: #23405d;
  font-size: 22rpx;
  font-weight: 600;
}

.env-profile-item__status {
  min-width: 88rpx;
  text-align: center;
  padding: 4rpx 14rpx;
  border-radius: 999rpx;
  font-size: 20rpx;
  color: #ffffff;
  background: linear-gradient(45deg, #0081ff, #1cbbb4);
}

.env-profile-item__status.is-hold {
  background: linear-gradient(45deg, #f59e0b, #f97316);
}

.env-profile-item__status.is-ready {
  background: linear-gradient(45deg, #ef4444, #f97316);
}

.env-profile-item__desc {
  margin-top: 8rpx;
  color: #6b7b8c;
  font-size: 20rpx;
  line-height: 1.6;
}

.env-risk-list {
  margin: 14rpx 6rpx 0;
  padding: 16rpx 18rpx;
  border-radius: 20rpx;
  background: linear-gradient(
    180deg,
    rgba(255, 247, 237, 0.96) 0%,
    rgba(255, 252, 247, 0.98) 100%
  );
  border: 1rpx solid rgba(234, 120, 72, 0.18);
}

.env-risk-item {
  color: #8c5145;
  font-size: 22rpx;
  line-height: 1.7;
}

.env-risk-item + .env-risk-item {
  margin-top: 8rpx;
}

.review-card {
  padding-bottom: 24rpx;
}

.sync-card {
  padding-bottom: 24rpx;
}

.review-desc {
  margin: 0 6rpx;
  color: #5b6b7b;
  font-size: 22rpx;
  line-height: 1.7;
}

.review-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 12rpx;
  margin: 14rpx 6rpx 0;
}

.review-tag {
  display: inline-flex;
  align-items: center;
  min-height: 40rpx;
  padding: 0 16rpx;
  border-radius: 999rpx;
  background: rgba(21, 75, 114, 0.08);
  color: #35506b;
  font-size: 20rpx;
}

.review-risk {
  margin: 14rpx 6rpx 0;
  padding: 16rpx 18rpx;
  border-radius: 20rpx;
  background: linear-gradient(
    180deg,
    rgba(255, 247, 237, 0.96) 0%,
    rgba(255, 252, 247, 0.98) 100%
  );
  border: 1rpx solid rgba(234, 120, 72, 0.18);
  color: #8c5145;
  font-size: 22rpx;
  line-height: 1.7;
}

.sync-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 42rpx;
  padding: 0 18rpx;
  border-radius: 999rpx;
  font-size: 22rpx;
  font-weight: 700;
}

.sync-badge.is-success {
  background: rgba(39, 174, 96, 0.12);
  color: #1d8f5f;
}

.sync-badge.is-pending,
.sync-badge.is-risk {
  background: rgba(240, 140, 90, 0.14);
  color: #cf6b45;
}

.sync-badge.is-neutral {
  background: rgba(107, 124, 143, 0.12);
  color: #6b7c8f;
}

.sync-desc {
  margin: 0 6rpx;
  color: #5b6b7b;
  font-size: 22rpx;
  line-height: 1.7;
}

.sync-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 12rpx;
  margin: 14rpx 6rpx 0;
}

.sync-tag {
  display: inline-flex;
  align-items: center;
  min-height: 40rpx;
  padding: 0 16rpx;
  border-radius: 999rpx;
  background: rgba(21, 75, 114, 0.08);
  color: #35506b;
  font-size: 20rpx;
}

.sync-risk {
  margin: 14rpx 6rpx 0;
  padding: 16rpx 18rpx;
  border-radius: 20rpx;
  background: linear-gradient(
    180deg,
    rgba(255, 247, 237, 0.96) 0%,
    rgba(255, 252, 247, 0.98) 100%
  );
  border: 1rpx solid rgba(234, 120, 72, 0.18);
  color: #8c5145;
  font-size: 22rpx;
  line-height: 1.7;
}

.sync-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 12rpx;
  margin: 16rpx 6rpx 0;
}

.sync-action {
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
  font-size: 22rpx;
  font-weight: 700;
}

.sync-action--disabled {
  opacity: 0.46;
}

.section-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16rpx 6rpx 18rpx;
}

.section-title {
  color: #1f2937;
  font-size: 34rpx;
  font-weight: 700;
}

.section-tip {
  color: #9ca3af;
  font-size: 22rpx;
}

.setting-list {
  overflow: hidden;
}

.setting-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 26rpx 6rpx;
  border-bottom: 1rpx solid #eef2f7;
}

.setting-item:last-child {
  border-bottom: 0;
}

.setting-item--switchable {
  cursor: pointer;
}

.setting-main {
  flex: 1;
  min-width: 0;
}

.setting-label {
  color: #111827;
  font-size: 30rpx;
  font-weight: 600;
}

.setting-desc {
  margin-top: 8rpx;
  color: #94a3b8;
  font-size: 22rpx;
}

.setting-action {
  margin-left: 20rpx;
}

.text-action {
  color: #475569;
  font-size: 26rpx;
}

.picker-value {
  color: #475569;
  font-size: 26rpx;
}

.setting-avatar {
  width: 72rpx;
  height: 72rpx;
  border-radius: 50%;
  background: #f8fafc;
}

.action-area {
  margin-top: 30rpx;
}

.logout-btn {
  height: 92rpx;
  line-height: 92rpx;
  border-radius: 24rpx;
  border: 0;
  background: linear-gradient(135deg, #f08c5a 0%, #d95945 100%);
  color: #ffffff;
  font-size: 30rpx;
  font-weight: 600;
  box-shadow: 0 18rpx 34rpx rgba(217, 89, 69, 0.22);
}

.logout-btn::after {
  border: 0;
}
</style>

