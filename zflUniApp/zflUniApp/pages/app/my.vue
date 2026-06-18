<template>
  <view
    class="zaiui-my-box"
    :class="[show ? 'show' : '', 'ui-theme-' + uiThemeStyle]"
    :style="myThemeCssVars"
  >
    <view class="bg-gradual-red zaiui-head-box">
      <view class="zaiui-head-glow zaiui-head-glow--one"></view>
      <view class="zaiui-head-glow zaiui-head-glow--two"></view>
      <!--标题栏-->
      <!--小程序端不显示-->
      <!-- #ifndef MP -->
      <bar-title :isBack="false" :fixed="false">
        <block slot="right">
          <!--					<text class="cuIcon-camera"/>-->
          <text class="cuIcon-settings" @tap="setupTap('/pages/my/set-up')" />
        </block>
      </bar-title>
      <!-- #endif -->

      <!--用户信息-->
      <view class="zaiui-user-info-box">
        <view v-if="reviewMode" class="review-user-view">
          <view class="review-mode-badge">{{ reviewBadgeText }}</view>
          <view class="review-title">{{ reviewDisplayTitle }}</view>
          <view class="review-desc">{{ reviewDisplayDesc }}</view>
          <button
            class="cu-btn sm round review-service-btn"
            @tap="jump('/pages/help/service')"
          >
            联系客服
          </button>
        </view>
        <!--未登陆-->
        <view class="login-user-view user-shell-card" v-else-if="login">
          <view class="login-user-avatar-view">
            <view class="cu-avatar round lg" :style="guestAvatarStyle" />
          </view>
          <!-- #ifndef MP-WEIXIN -->
          <view
            class="text-black text-lg text-bold padding-sm my-head-login-text"
            @tap="loginUrlTap"
            >点击登录
          </view>
          <!-- #endif -->
          <!-- 授权登录按钮 -->
          <!-- #ifdef MP-WEIXIN -->
          <button class="cu-btn sm radius" @tap="loginUrlTap">立即登录</button>
          <!-- #endif -->
        </view>

        <!--已登陆-->
        <view class="cu-list menu-avatar user-shell-card" v-else>
          <view class="cu-item">
            <view class="cu-avatar round lg" :style="displayAvatarStyle" />
            <view class="content text-xl">
              <view class="text-white">
                <text class="margin-right">{{ displayNickname }}</text>
              </view>
              <view class="text-white-bg text-sm">
                <text class="text-border-x" v-if="userInfo.is_balance"
                  >司秤员</text
                >
                <text class="text-border-x" v-if="userInfo.is_warehouse"
                  >仓库员</text
                >
                <text class="text-border-x" v-if="userInfo.group_names">{{
                  userInfo.group_names
                }}</text>
                <text class="text-border-x" v-if="userInfo.tag_names">{{
                  userInfo.tag_names
                }}</text>
              </view>
            </view>
          </view>
        </view>
      </view>

      <!--用户数据-->
      <view class="zaiui-user-info-num-box" v-if="!reviewMode">
        <view class="cu-list grid col-4 no-border">
          <view class="cu-item" @tap="setupTap('/pages/goods/my_cart')">
            <view class="text-xl" v-if="login">-</view>
            <view class="text-xl" v-else>{{ safeCartCount }}</view>
            <text class="text-sm">购物车</text>
          </view>
          <view class="cu-item" @tap="setupTap('/pages/order/list?status=1')">
            <view class="text-xl" v-if="login">-</view>
            <view class="text-xl" v-else>{{ safeStatusNums.p_shipment }}</view>
            <text class="text-sm">待发货</text>
          </view>
          <view class="cu-item" @tap="setupTap('/pages/order/list?status=4')">
            <view class="text-xl" v-if="login">-</view>
            <view class="text-xl" v-else>{{ safeStatusNums.success }}</view>
            <text class="text-sm">完成</text>
          </view>
          <view class="cu-item" @tap="setupTap('/pages/order/list?status=5')">
            <view class="text-xl" v-if="login">-</view>
            <view class="text-xl" v-else>{{ safeStatusNums.service }}</view>
            <text class="text-sm">售后</text>
          </view>
        </view>
      </view>

      <!--用户提示-->
      <view
        class="text-sm zaiui-user-info-tip-box"
        v-if="!login && !reviewMode"
        @tap="setupTap('/pages/merchant/apply')"
      >
        <view class="text-cut">涛冠优选诚邀您加入我们</view>
        <text class="cuIcon-right icon" />
      </view>
    </view>

    <view class="zaiui-view-content">
      <view v-if="!reviewMode" class="env-card">
        <view class="env-card__head">
          <view class="env-card__title">当前环境</view>
          <view
            class="env-card__badge"
            :class="currentEnvInfo.is_prod ? 'is-prod' : 'is-test'"
          >
            {{ currentEnvInfo.label }}
          </view>
        </view>
        <view class="env-card__desc">{{ myEnvDescription }}</view>
        <view class="env-card__meta">
          <view class="env-card__tag">{{ myWorkbenchModeText }}</view>
          <view class="env-card__tag">{{ myRuntimeStatusText }}</view>
          <view class="env-card__tag">{{ myEntryUsageText }}</view>
          <view class="env-card__tag">{{ myIsolationStatusText }}</view>
          <view class="env-card__tag">{{ myReleaseStageText }}</view>
        </view>
        <view class="env-card__note">{{ myRuntimeHint }}</view>
        <view class="env-card__note env-card__note--strong">{{
          myReleaseHint
        }}</view>
        <view v-if="myEnvRiskList.length" class="env-card__risk-list">
          <view
            v-for="item in myEnvRiskList"
            :key="item"
            class="env-card__risk-item"
            >{{ item }}</view
          >
        </view>
        <view class="env-card__board">
          <view class="env-card__board-title">环境就绪看板</view>
          <view class="env-card__board-list">
            <view
              v-for="item in profileReadinessList"
              :key="item.key"
              class="env-card__board-item"
              :class="{ 'is-current': item.key === currentEnvInfo.key }"
            >
              <view class="env-card__board-item-head">
                <text class="env-card__board-item-name">{{ item.label }}</text>
                <text
                  class="env-card__board-item-status"
                  :class="'is-' + ((item.status_text || '').toLowerCase())"
                >
                  {{ item.status_text }}
                </text>
              </view>
              <view class="env-card__board-item-desc">{{
                item.short_hint
              }}</view>
            </view>
          </view>
        </view>
        <view class="env-card__url">{{ currentEnvInfo.api_root_url }}</view>
      </view>
      <view v-if="!reviewMode" class="runtime-rollout-card">
        <view class="runtime-rollout-card__head">
          <view class="runtime-rollout-card__title">上线承接提示</view>
          <view
            class="runtime-rollout-card__badge"
            :class="currentEnvInfo.is_prod ? 'is-prod' : 'is-test'"
          >
            {{ currentEnvInfo.is_prod ? "正式工作台" : "灰度前检查" }}
          </view>
        </view>
        <view class="runtime-rollout-card__desc">{{
          workbenchRolloutHint
        }}</view>
        <view class="runtime-rollout-card__list">
          <view
            v-for="item in workbenchRolloutChecklist"
            :key="item.label"
            class="runtime-rollout-card__item"
          >
            <text class="runtime-rollout-card__item-label">{{
              item.label
            }}</text>
            <text class="runtime-rollout-card__item-value">{{
              item.value
            }}</text>
          </view>
        </view>
        <view class="runtime-rollout-card__risk">{{
          workbenchRollbackHint
        }}</view>
      </view>
      <view v-if="!reviewMode" class="runtime-review-card">
        <view class="runtime-review-card__title">进入前复核</view>
        <view class="runtime-review-card__desc">{{ workbenchReviewHint }}</view>
        <view class="runtime-review-card__tags">
          <view class="runtime-review-card__tag">{{
            workbenchReviewTags.entry
          }}</view>
          <view class="runtime-review-card__tag">{{
            workbenchReviewTags.identity
          }}</view>
          <view class="runtime-review-card__tag">{{
            workbenchReviewTags.merchant
          }}</view>
          <view class="runtime-review-card__tag">{{
            workbenchReviewTags.release
          }}</view>
        </view>
        <view class="runtime-review-card__risk">{{ workbenchRiskHint }}</view>
      </view>
      <view v-if="!reviewMode" class="runtime-hub-card">
        <view class="runtime-hub-card__head">
          <view>
            <view class="runtime-hub-card__title">联调与上线总控</view>
            <view class="runtime-hub-card__desc">{{ workbenchHubHint }}</view>
          </view>
          <view
            class="runtime-hub-card__badge"
            :class="workbenchHubBadgeClass"
            >{{ workbenchHubBadgeText }}</view
          >
        </view>
        <view class="runtime-hub-card__tags">
          <view class="runtime-hub-card__tag">{{
            workbenchHubTags.protocol
          }}</view>
          <view class="runtime-hub-card__tag">{{
            workbenchHubTags.pending
          }}</view>
          <view class="runtime-hub-card__tag">{{
            workbenchHubTags.scene
          }}</view>
          <view class="runtime-hub-card__tag">{{
            workbenchHubTags.success
          }}</view>
        </view>
        <view v-if="workbenchHubRiskHint" class="runtime-hub-card__risk">{{
          workbenchHubRiskHint
        }}</view>
        <view class="runtime-hub-card__actions">
          <view
            class="runtime-hub-card__action"
            @tap="openAccordCenterFromWorkbench"
            >协议中心</view
          >
          <view
            class="runtime-hub-card__action"
            @tap="openSettingsFromWorkbench"
            >账号设置</view
          >
          <view
            class="runtime-hub-card__action"
            :class="{ 'is-disabled': !canRetryWorkbenchAccords }"
            @tap="retryWorkbenchAccords"
          >
            重试待补记
          </view>
        </view>
      </view>
      <view
        v-if="!reviewMode && recentActionSummary"
        class="runtime-action-card"
      >
        <view class="runtime-action-card__title">最近操作</view>
        <view class="runtime-action-card__desc">{{ recentActionSummary }}</view>
      </view>
      <view
        v-if="!reviewMode && recentReleasedGoodsCard"
        class="recent-release-panel"
        @tap="openRecentReleasedGoods"
      >
        <view class="recent-release-panel__badge">最近发布</view>
        <view class="recent-release-panel__title">{{
          recentReleasedGoodsCard.title || "新商品"
        }}</view>
        <view class="recent-release-panel__desc"
          >已保留发布后回看入口，可直接返回商城继续核对商品详情、价格与图片。</view
        >
        <view class="recent-release-panel__action">
          <text>前往查看</text>
          <text class="cuIcon-right"></text>
        </view>
      </view>
      <view
        v-if="!reviewMode && primaryWorkbenchEntry"
        class="workbench-entry-panel"
        @tap="openPrimaryWorkbenchEntry"
      >
        <view class="workbench-entry-panel__badge">工作台入口</view>
        <view class="workbench-entry-panel__title">{{
          workbenchEntryTitle
        }}</view>
        <view class="workbench-entry-panel__desc">{{
          workbenchEntryDesc
        }}</view>
        <view class="workbench-entry-panel__action">
          <text>{{ workbenchEntryActionText }}</text>
          <text class="cuIcon-right"></text>
        </view>
      </view>
      <!--用户数据-->
      <view
        class="padding-xs bg-white zaiui-user-info-order-box"
        v-if="!reviewMode"
      >
        <view class="order-box-head">
          <view class="order-box-head__title">我的订单</view>
          <view class="order-box-head__desc">订单状态一目了然</view>
        </view>
        <view class="order-grid">
          <view
            v-for="item in myOrderEntries"
            :key="item.code"
            class="order-grid-card"
            :class="item.tone"
            @tap="setupTap(item.url)"
          >
            <view class="order-grid-card__top">
              <view class="order-grid-icon">
                <view class="text-xxl text-red" v-if="login">
                  <text :class="item.iconClass"></text>
                </view>
                <view class="text-xxl text-black" v-else>{{ item.count }}</view>
              </view>
              <view class="order-grid-card__meta">
                <view class="order-grid-label">{{ item.label }}</view>
                <view class="order-grid-hint">{{ item.hint }}</view>
              </view>
            </view>
          </view>
        </view>
      </view>

      <view
        v-if="showPendingWriteoffCard"
        class="pending-reminder-card"
        @tap="openPendingWriteoff"
      >
        <view class="pending-badge">订单提醒</view>
        <view class="pending-title"
          >您有 {{ pendingWriteoffCountText }} 笔待核销订单</view
        >
        <view class="pending-desc">
          待核销金额 ¥{{ pendingWriteoffAmountText
          }}<text v-if="pendingWriteoffOldestTime"
            >，最早一笔：{{ pendingWriteoffOldestTime }}</text
          >
        </view>
        <view class="pending-action">
          <text>立即处理</text>
          <text class="cuIcon-right" />
        </view>
      </view>

      <!--推荐工具-->
      <view class="padding-xs bg-white margin-top zaiui-user-info-tools-box">
        <view class="padding-sm tools-view">
          <view class="text-black text-bold text-lg tools-title">{{
            toolsHeaderTitle
          }}</view>
          <view class="text-gray text-sm tools-right">
            <text>{{ toolsHeaderRightText }}</text>
            <text v-if="toolsChevronVisible" class="cuIcon-right" />
          </view>
        </view>

        <picker
          v-if="merchantIdentityOptions.length > 1"
          class="merchant-identity-picker tools-identity-picker"
          :value="currentMerchantIdentityIndex"
          :range="merchantIdentityOptions"
          range-key="label"
          @change="onMerchantIdentityChange"
        >
          <view class="merchant-identity-picker__inner">
            <text class="merchant-identity-picker__label">当前商家身份</text>
            <text class="merchant-identity-picker__value">{{
              (currentMerchantIdentity &&
                currentMerchantIdentity.identity_name) ||
              "请选择商家身份"
            }}</text>
          </view>
        </picker>

        <view
          v-if="showMerchantWorkbenchSummary"
          class="merchant-tools-summary"
        >
          <view class="merchant-tools-summary__head">
            <view>
              <view class="merchant-tools-summary__badge">商家工作台</view>
              <view class="merchant-tools-summary__title">{{
                merchantWorkbenchSummaryTitle
              }}</view>
            </view>
            <view
              class="merchant-tools-summary__status"
              :class="merchantStatusTone"
              >{{ merchantStatusText }}</view
            >
          </view>
          <view class="merchant-tools-summary__desc">{{
            merchantWorkbenchSummaryDesc
          }}</view>
          <picker
            v-if="false"
            class="merchant-identity-picker"
            :value="currentMerchantIdentityIndex"
            :range="merchantIdentityOptions"
            range-key="label"
            @change="onMerchantIdentityChange"
          >
            <view class="merchant-identity-picker__inner">
              <text class="merchant-identity-picker__label">当前商家身份</text>
              <text class="merchant-identity-picker__value">{{
                (currentMerchantIdentity &&
                  currentMerchantIdentity.identity_name) ||
                "请选择商家身份"
              }}</text>
            </view>
          </picker>
        </view>

        <view class="zaiui-tools-list-box">
          <view class="recommend-tools-grid" :class="recommendedToolsGridClass">
            <view
              v-for="item in recommendedTools"
              :key="item.code"
              class="tool-grid-cell"
            >
              <view
                class="cu-item tool-grid-card"
                :class="item.cardClass"
                @tap="gridTap(item)"
              >
                <view class="tool-card-icon">
                  <view class="text-black" :class="item.iconClass" />
                </view>
                <view class="tool-card-title">
                  <text>{{ item.displayName }}</text>
                </view>
                <text
                  v-if="item.badge"
                  class="cu-tag badge bg-red tool-badge"
                  >{{ item.badge }}</text
                >
              </view>
            </view>
          </view>
        </view>

        <view v-if="showFrontendSuperWorkbench" class="merchant-super-tools">
          <view class="merchant-super-tools__hero">
            <view class="merchant-super-tools__badge">超管工作台</view>
            <view class="merchant-super-tools__hero-title"
              >两个高权限入口已就位</view
            >
            <view class="merchant-super-tools__hero-desc"
              >平台商家审核、商家订单核销仅对商家超管开放，商品发布已下沉到普通商家入口。</view
            >
          </view>
          <view class="merchant-super-tools__grid">
            <view
              v-for="item in superManageTools"
              :key="item.code"
              class="merchant-super-tool-card"
              :class="item.cardClass"
              @tap="gridTap(item)"
            >
              <view v-if="item.badge" class="merchant-super-tool-card__badge">
                {{ item.badge }}
              </view>
              <view class="merchant-super-tool-card__icon">
                <text :class="item.iconClass"></text>
              </view>
              <view class="merchant-super-tool-card__title">{{
                item.displayName
              }}</view>
              <view class="merchant-super-tool-card__desc">{{
                item.caption
              }}</view>
              <view class="merchant-super-tool-card__action">
                <text>进入</text>
                <text class="cuIcon-right"></text>
              </view>
            </view>
          </view>
        </view>
      </view>
    </view>

    <!--占位底部距离-->
    <view class="cu-tabbar-height"></view>
  </view>
</template>

<script>
import barTitle from "@/components/zaiui-common/basics/bar-title";
import cache from "@/utils/cache";
import store from "@/store/common";
import api from "@/api";
import {
  resolveAdminNextUrl,
  resolveImageUrl as resolveSafeImageUrl,
} from "@/utils/resource.js";
import {
  bestEffortAcceptAccords,
  getAccordRuntimeSummary,
  retryPendingAccords,
} from "@/utils/accord-accept.js";
import {
  buildLoginRedirectUrl,
  setLoginRedirect,
} from "@/utils/login-redirect.js";
import { maskMerchantTitle } from "@/utils/desensitize.js";
import {
  getCurrentEnvInfo,
  getEnvIsolationHealth,
  getProfileReadinessList,
} from "@/utils/env-runtime.js";
import {
  buildEnvConfirmText,
  getEnvIsolationTag,
  getEnvReleaseHint,
  getEnvReleaseStageText,
} from "@/utils/env-risk.js";
import {
  getCurrentMerchantIdentityId,
  setCurrentMerchantIdentityId,
  setMerchantIdentityList,
  clearCurrentMerchantIdentity,
} from "@/utils/merchant-identity.js";
import {
  applyUiThemeFromSetting,
  resolveUiThemeStyle,
} from "@/utils/ui-theme.js";
function getCachedSetting() {
  return cache.get("setting") || {};
}

function buildCssVars(styleMap = {}) {
  return Object.keys(styleMap)
    .filter(
      (key) =>
        styleMap[key] !== undefined &&
        styleMap[key] !== null &&
        styleMap[key] !== "",
    )
    .map((key) => `${key}:${styleMap[key]}`)
    .join(";");
}

function resolveReviewMode(setting = {}) {
  const system = setting.system || {};
  return Number(system.review_mode || system.wx_approved || 0) === 1;
}

function resolveReviewInfo(setting = {}) {
  const system = setting.system || {};
  return {
    system_name: system.system_name || "涛冠优选",
    review_hero_title: system.review_hero_title || "平台服务中心",
    review_hero_desc:
      system.review_hero_desc ||
      "当前页面展示平台服务入口、官方资讯与联系渠道，可继续查看平台服务内容。",
  };
}

function resolveGoodsReleaseEnabled(setting = {}) {
  const system = setting.system || {};
  return Number(system.goods_release_enabled ?? 1) === 1;
}

const MY_HEAD_THEME_PRESETS = {
  origin: {
    headBg: "linear-gradient(160deg, #ef5b6c 0%, #ff7a59 45%, #ff9863 100%)",
    headText: "#ffffff",
    headSubtle: "rgba(255, 255, 255, 0.88)",
    headGlowTwo:
      "radial-gradient(circle, rgba(255,204,188,0.55) 0%, rgba(255,255,255,0.04) 72%, rgba(255,255,255,0) 100%)",
    glassBg:
      "linear-gradient(180deg, rgba(255, 255, 255, 0.2) 0%, rgba(255, 255, 255, 0.12) 100%)",
    glassBorder: "rgba(255, 255, 255, 0.18)",
    glassShadow: "0 18rpx 36rpx rgba(140, 38, 26, 0.14)",
    avatarShadow: "0 16rpx 28rpx rgba(140, 38, 26, 0.14)",
    titleShadow: "0 4rpx 10rpx rgba(140, 38, 26, 0.14)",
    statDivider: "rgba(255, 255, 255, 0.14)",
    tipBg:
      "linear-gradient(135deg, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0.12) 100%)",
    tipBorder: "rgba(255,255,255,0.16)",
    tipShadow: "0 14rpx 28rpx rgba(140, 38, 26, 0.1)",
    tipText: "#ffffff",
  },
  red_energy: {
    headBg: "linear-gradient(135deg, #8e0812 0%, #c51424 54%, #ef7d35 100%)",
    headText: "#fff8f4",
    headSubtle: "rgba(255, 248, 244, 0.88)",
    headGlowTwo:
      "radial-gradient(circle, rgba(255,223,190,0.42) 0%, rgba(255,255,255,0.04) 72%, rgba(255,255,255,0) 100%)",
    glassBg:
      "linear-gradient(180deg, rgba(255, 247, 242, 0.18) 0%, rgba(255, 236, 229, 0.12) 100%)",
    glassBorder: "rgba(255, 231, 206, 0.24)",
    glassShadow: "0 18rpx 36rpx rgba(111, 8, 12, 0.16)",
    avatarShadow: "0 16rpx 28rpx rgba(111, 8, 12, 0.16)",
    titleShadow: "0 4rpx 10rpx rgba(111, 8, 12, 0.16)",
    statDivider: "rgba(255, 231, 206, 0.18)",
    tipBg:
      "linear-gradient(135deg, rgba(255, 247, 242, 0.18) 0%, rgba(255, 236, 229, 0.12) 100%)",
    tipBorder: "rgba(255, 231, 206, 0.22)",
    tipShadow: "0 14rpx 28rpx rgba(111, 8, 12, 0.12)",
    tipText: "#fff8f4",
  },
  yellow_energy: {
    headBg: "linear-gradient(135deg, #f2c339 0%, #f8da70 46%, #fff0ba 100%)",
    headText: "#a32513",
    headSubtle: "rgba(163, 37, 19, 0.88)",
    headGlowTwo:
      "radial-gradient(circle, rgba(255,236,170,0.58) 0%, rgba(255,255,255,0.04) 72%, rgba(255,255,255,0) 100%)",
    glassBg:
      "linear-gradient(180deg, rgba(255, 255, 255, 0.34) 0%, rgba(255, 247, 221, 0.26) 100%)",
    glassBorder: "rgba(183, 32, 18, 0.12)",
    glassShadow: "0 18rpx 34rpx rgba(177, 100, 20, 0.14)",
    avatarShadow: "0 16rpx 28rpx rgba(177, 100, 20, 0.14)",
    titleShadow: "0 4rpx 10rpx rgba(255, 248, 233, 0.42)",
    statDivider: "rgba(183, 32, 18, 0.12)",
    tipBg:
      "linear-gradient(135deg, rgba(247, 208, 86, 0.96) 0%, rgba(255, 234, 155, 0.96) 58%, rgba(255, 248, 226, 0.96) 100%)",
    tipBorder: "rgba(183, 32, 18, 0.1)",
    tipShadow: "0 14rpx 28rpx rgba(177, 100, 20, 0.12)",
    tipText: "#ad2614",
  },
  jade_modern: {
    headBg: "linear-gradient(135deg, #184b42 0%, #206c60 54%, #d7a13e 100%)",
    headText: "#f8f4ea",
    headSubtle: "rgba(248, 244, 234, 0.86)",
    headGlowTwo:
      "radial-gradient(circle, rgba(229,204,143,0.42) 0%, rgba(255,255,255,0.04) 72%, rgba(255,255,255,0) 100%)",
    glassBg:
      "linear-gradient(180deg, rgba(255, 255, 255, 0.16) 0%, rgba(247, 244, 236, 0.1) 100%)",
    glassBorder: "rgba(248, 244, 234, 0.22)",
    glassShadow: "0 18rpx 36rpx rgba(19, 52, 47, 0.16)",
    avatarShadow: "0 16rpx 28rpx rgba(19, 52, 47, 0.16)",
    titleShadow: "0 4rpx 10rpx rgba(19, 52, 47, 0.14)",
    statDivider: "rgba(248, 244, 234, 0.16)",
    tipBg:
      "linear-gradient(135deg, rgba(255, 255, 255, 0.16) 0%, rgba(247, 244, 236, 0.12) 100%)",
    tipBorder: "rgba(248, 244, 234, 0.18)",
    tipShadow: "0 14rpx 28rpx rgba(19, 52, 47, 0.12)",
    tipText: "#f8f4ea",
  },
};

const MY_PAGE_THEME_PRESETS = {
  origin: {
    toolsPanelBg:
      "linear-gradient(180deg, rgba(255, 252, 246, 0.99) 0%, rgba(250, 245, 237, 0.98) 100%)",
    toolsPanelShadow: "0 18rpx 38rpx rgba(118, 88, 37, 0.08)",
    toolsSubtleText: "#9b8165",
    summaryBg:
      "linear-gradient(135deg, rgba(252, 242, 224, 0.92) 0%, rgba(250, 232, 211, 0.96) 100%)",
    summaryBorder: "rgba(222, 182, 118, 0.26)",
    summaryBadgeBg: "rgba(32, 79, 118, 0.1)",
    summaryBadgeColor: "#204f76",
    summaryTitle: "#1f2d3d",
    summaryDesc: "#5b6472",
    identityBg: "rgba(255, 250, 243, 0.92)",
    identityBorder: "rgba(226, 173, 92, 0.26)",
    identityLabel: "#7a5d57",
    identityValue: "#aa2b38",
    toolCardBg: "linear-gradient(180deg, #fffdf8 0%, #fff4e4 100%)",
    toolCardBorder: "rgba(214, 166, 84, 0.48)",
    toolCardShadow: "0 10rpx 20rpx rgba(174, 128, 46, 0.1)",
    toolIconBg:
      "linear-gradient(135deg, rgba(244, 195, 92, 0.34) 0%, rgba(232, 151, 49, 0.26) 100%)",
    toolIconBorder: "rgba(188, 123, 24, 0.18)",
    toolIconShadow: "0 8rpx 16rpx rgba(207, 145, 55, 0.14)",
    toolIconColor: "#b86d1b",
    toolTitle: "#6c3f12",
    toolTitleShadow: "0 1rpx 0 rgba(255, 255, 255, 0.75)",
    toolAccentBg: "linear-gradient(180deg, #fff0e0 0%, #ffe3c2 100%)",
    toolAccentBorder: "rgba(219, 129, 51, 0.42)",
    toolAccentShadow: "0 12rpx 22rpx rgba(221, 132, 55, 0.16)",
    toolAccentIconBg: "linear-gradient(135deg, #ed9e61 0%, #e67f58 100%)",
    toolAccentIconColor: "#ffffff",
    toolAccentTitle: "#7b3d1f",
    toolMerchantBg: "linear-gradient(180deg, #fffdf8 0%, #fff1dd 100%)",
    toolSystemBg: "linear-gradient(180deg, #fffefb 0%, #fff5e8 100%)",
    toolAdminBg: "linear-gradient(180deg, #fff8ef 0%, #ffedd6 100%)",
    superHeroBg:
      "linear-gradient(180deg, rgba(255, 250, 242, 0.98) 0%, rgba(255, 247, 235, 0.98) 100%)",
    superHeroBorder: "rgba(238, 191, 105, 0.24)",
    superBadgeBg: "rgba(243, 189, 92, 0.18)",
    superBadgeColor: "#c7851e",
    superTitle: "#7a5330",
    superDesc: "#96795b",
    superCardBg:
      "linear-gradient(180deg, rgba(255, 255, 255, 0.99) 0%, rgba(255, 249, 240, 0.98) 100%)",
    superCardBorder: "rgba(238, 204, 145, 0.32)",
    superCardShadow: "0 10rpx 22rpx rgba(214, 170, 90, 0.09)",
    superIconBg: "linear-gradient(135deg, #f6cc74 0%, #f0a95a 100%)",
    superIconColor: "#ffffff",
    superCardBadgeBg: "rgba(227, 123, 67, 0.12)",
    superCardBadgeColor: "#d86b32",
    superAction: "#d18a24",
    superPrimaryBg:
      "linear-gradient(180deg, rgba(255, 238, 221, 0.99) 0%, rgba(255, 227, 199, 0.98) 100%)",
    superPrimaryBorder: "rgba(232, 142, 82, 0.28)",
    superPrimaryShadow: "0 14rpx 28rpx rgba(224, 142, 76, 0.16)",
    superPrimaryIconBg: "linear-gradient(135deg, #ec9a5f 0%, #e57a57 100%)",
    superPrimaryText: "#7a4025",
    superSecondaryBg:
      "linear-gradient(180deg, rgba(255, 251, 244, 0.99) 0%, rgba(255, 246, 233, 0.98) 100%)",
    superSecondaryBorder: "rgba(237, 194, 120, 0.24)",
    superSecondaryShadow: "0 12rpx 24rpx rgba(214, 170, 90, 0.11)",
    superSecondaryIconBg: "linear-gradient(135deg, #f7cf77 0%, #f0af62 100%)",
    superSecondaryAction: "#c7851e",
    superAlertBg:
      "linear-gradient(180deg, rgba(255, 252, 245, 0.99) 0%, rgba(255, 246, 230, 0.98) 100%)",
    superAlertBorder: "rgba(230, 157, 75, 0.26)",
    superAlertShadow: "0 12rpx 24rpx rgba(230, 157, 75, 0.11)",
    superAlertIconBg: "linear-gradient(135deg, #f6bf73 0%, #eb8c57 100%)",
    superAlertAction: "#d86b32",
  },
  red_energy: {
    toolsPanelBg:
      "linear-gradient(180deg, rgba(255, 246, 244, 0.99) 0%, rgba(252, 238, 234, 0.98) 100%)",
    toolsPanelShadow: "0 18rpx 38rpx rgba(156, 46, 42, 0.1)",
    toolsSubtleText: "#a76865",
    summaryBg:
      "linear-gradient(135deg, rgba(255, 236, 232, 0.96) 0%, rgba(255, 224, 219, 0.98) 100%)",
    summaryBorder: "rgba(216, 86, 75, 0.22)",
    summaryBadgeBg: "rgba(163, 12, 21, 0.1)",
    summaryBadgeColor: "#a30c15",
    summaryTitle: "#6f1f22",
    summaryDesc: "#8f5a59",
    identityBg: "rgba(255, 244, 241, 0.95)",
    identityBorder: "rgba(211, 102, 87, 0.22)",
    identityLabel: "#8b5f5c",
    identityValue: "#ba2d2d",
    toolCardBg: "linear-gradient(180deg, #fffaf8 0%, #ffece6 100%)",
    toolCardBorder: "rgba(215, 114, 91, 0.32)",
    toolCardShadow: "0 10rpx 20rpx rgba(185, 86, 67, 0.12)",
    toolIconBg:
      "linear-gradient(135deg, rgba(223, 78, 80, 0.2) 0%, rgba(240, 133, 102, 0.24) 100%)",
    toolIconBorder: "rgba(183, 55, 54, 0.16)",
    toolIconShadow: "0 8rpx 16rpx rgba(185, 86, 67, 0.14)",
    toolIconColor: "#bd3e37",
    toolTitle: "#7f2a28",
    toolTitleShadow: "0 1rpx 0 rgba(255, 255, 255, 0.68)",
    toolAccentBg: "linear-gradient(180deg, #ffe8e2 0%, #ffd6cc 100%)",
    toolAccentBorder: "rgba(210, 91, 75, 0.36)",
    toolAccentShadow: "0 12rpx 22rpx rgba(185, 74, 60, 0.16)",
    toolAccentIconBg: "linear-gradient(135deg, #d64246 0%, #ef7c5f 100%)",
    toolAccentIconColor: "#ffffff",
    toolAccentTitle: "#7a2525",
    toolMerchantBg: "linear-gradient(180deg, #fffaf8 0%, #ffe9e2 100%)",
    toolSystemBg: "linear-gradient(180deg, #fffdfc 0%, #fff2ee 100%)",
    toolAdminBg: "linear-gradient(180deg, #fff7f3 0%, #ffe3db 100%)",
    superHeroBg:
      "linear-gradient(180deg, rgba(255, 245, 242, 0.99) 0%, rgba(255, 236, 232, 0.99) 100%)",
    superHeroBorder: "rgba(214, 101, 87, 0.22)",
    superBadgeBg: "rgba(163, 12, 21, 0.1)",
    superBadgeColor: "#a30c15",
    superTitle: "#7a2525",
    superDesc: "#946060",
    superCardBg:
      "linear-gradient(180deg, rgba(255, 252, 251, 0.99) 0%, rgba(255, 242, 238, 0.98) 100%)",
    superCardBorder: "rgba(216, 119, 99, 0.24)",
    superCardShadow: "0 10rpx 22rpx rgba(184, 83, 62, 0.1)",
    superIconBg: "linear-gradient(135deg, #d94d45 0%, #ef8764 100%)",
    superIconColor: "#ffffff",
    superCardBadgeBg: "rgba(214, 82, 68, 0.12)",
    superCardBadgeColor: "#c53a32",
    superAction: "#c44036",
    superPrimaryBg:
      "linear-gradient(180deg, rgba(255, 234, 228, 0.99) 0%, rgba(255, 218, 209, 0.98) 100%)",
    superPrimaryBorder: "rgba(209, 85, 73, 0.28)",
    superPrimaryShadow: "0 14rpx 28rpx rgba(183, 73, 60, 0.16)",
    superPrimaryIconBg: "linear-gradient(135deg, #d33c44 0%, #ea755a 100%)",
    superPrimaryText: "#7a2525",
    superSecondaryBg:
      "linear-gradient(180deg, rgba(255, 249, 246, 0.99) 0%, rgba(255, 240, 234, 0.98) 100%)",
    superSecondaryBorder: "rgba(223, 150, 124, 0.2)",
    superSecondaryShadow: "0 12rpx 24rpx rgba(184, 83, 62, 0.1)",
    superSecondaryIconBg: "linear-gradient(135deg, #e3785d 0%, #ef9b72 100%)",
    superSecondaryAction: "#b93c34",
    superAlertBg:
      "linear-gradient(180deg, rgba(255, 246, 242, 0.99) 0%, rgba(255, 232, 225, 0.98) 100%)",
    superAlertBorder: "rgba(215, 101, 80, 0.26)",
    superAlertShadow: "0 12rpx 24rpx rgba(188, 75, 60, 0.12)",
    superAlertIconBg: "linear-gradient(135deg, #e0634c 0%, #f08a5d 100%)",
    superAlertAction: "#c53a32",
  },
  yellow_energy: {
    toolsPanelBg:
      "linear-gradient(180deg, rgba(255, 252, 246, 0.99) 0%, rgba(250, 245, 237, 0.98) 100%)",
    toolsPanelShadow: "0 18rpx 38rpx rgba(118, 88, 37, 0.08)",
    toolsSubtleText: "#9b8165",
    summaryBg:
      "linear-gradient(135deg, rgba(252, 242, 224, 0.92) 0%, rgba(250, 232, 211, 0.96) 100%)",
    summaryBorder: "rgba(222, 182, 118, 0.26)",
    summaryBadgeBg: "rgba(181, 91, 18, 0.1)",
    summaryBadgeColor: "#a65c16",
    summaryTitle: "#744a1d",
    summaryDesc: "#7f6650",
    identityBg: "rgba(255, 250, 243, 0.92)",
    identityBorder: "rgba(226, 173, 92, 0.26)",
    identityLabel: "#7a5d57",
    identityValue: "#b14a1d",
    toolCardBg: "linear-gradient(180deg, #fffdf8 0%, #fff4e4 100%)",
    toolCardBorder: "rgba(214, 166, 84, 0.48)",
    toolCardShadow: "0 10rpx 20rpx rgba(174, 128, 46, 0.1)",
    toolIconBg:
      "linear-gradient(135deg, rgba(244, 195, 92, 0.34) 0%, rgba(232, 151, 49, 0.26) 100%)",
    toolIconBorder: "rgba(188, 123, 24, 0.18)",
    toolIconShadow: "0 8rpx 16rpx rgba(207, 145, 55, 0.14)",
    toolIconColor: "#b86d1b",
    toolTitle: "#6c3f12",
    toolTitleShadow: "0 1rpx 0 rgba(255, 255, 255, 0.75)",
    toolAccentBg: "linear-gradient(180deg, #fff0e0 0%, #ffe3c2 100%)",
    toolAccentBorder: "rgba(219, 129, 51, 0.42)",
    toolAccentShadow: "0 12rpx 22rpx rgba(221, 132, 55, 0.16)",
    toolAccentIconBg: "linear-gradient(135deg, #ed9e61 0%, #e67f58 100%)",
    toolAccentIconColor: "#ffffff",
    toolAccentTitle: "#7b3d1f",
    toolMerchantBg: "linear-gradient(180deg, #fffdf8 0%, #fff1dd 100%)",
    toolSystemBg: "linear-gradient(180deg, #fffefb 0%, #fff5e8 100%)",
    toolAdminBg: "linear-gradient(180deg, #fff8ef 0%, #ffedd6 100%)",
    superHeroBg:
      "linear-gradient(180deg, rgba(255, 250, 242, 0.98) 0%, rgba(255, 247, 235, 0.98) 100%)",
    superHeroBorder: "rgba(238, 191, 105, 0.24)",
    superBadgeBg: "rgba(243, 189, 92, 0.18)",
    superBadgeColor: "#c7851e",
    superTitle: "#7a5330",
    superDesc: "#96795b",
    superCardBg:
      "linear-gradient(180deg, rgba(255, 255, 255, 0.99) 0%, rgba(255, 249, 240, 0.98) 100%)",
    superCardBorder: "rgba(238, 204, 145, 0.32)",
    superCardShadow: "0 10rpx 22rpx rgba(214, 170, 90, 0.09)",
    superIconBg: "linear-gradient(135deg, #f6cc74 0%, #f0a95a 100%)",
    superIconColor: "#ffffff",
    superCardBadgeBg: "rgba(227, 123, 67, 0.12)",
    superCardBadgeColor: "#d86b32",
    superAction: "#d18a24",
    superPrimaryBg:
      "linear-gradient(180deg, rgba(255, 238, 221, 0.99) 0%, rgba(255, 227, 199, 0.98) 100%)",
    superPrimaryBorder: "rgba(232, 142, 82, 0.28)",
    superPrimaryShadow: "0 14rpx 28rpx rgba(224, 142, 76, 0.16)",
    superPrimaryIconBg: "linear-gradient(135deg, #ec9a5f 0%, #e57a57 100%)",
    superPrimaryText: "#7a4025",
    superSecondaryBg:
      "linear-gradient(180deg, rgba(255, 251, 244, 0.99) 0%, rgba(255, 246, 233, 0.98) 100%)",
    superSecondaryBorder: "rgba(237, 194, 120, 0.24)",
    superSecondaryShadow: "0 12rpx 24rpx rgba(214, 170, 90, 0.11)",
    superSecondaryIconBg: "linear-gradient(135deg, #f7cf77 0%, #f0af62 100%)",
    superSecondaryAction: "#c7851e",
    superAlertBg:
      "linear-gradient(180deg, rgba(255, 252, 245, 0.99) 0%, rgba(255, 246, 230, 0.98) 100%)",
    superAlertBorder: "rgba(230, 157, 75, 0.26)",
    superAlertShadow: "0 12rpx 24rpx rgba(230, 157, 75, 0.11)",
    superAlertIconBg: "linear-gradient(135deg, #f6bf73 0%, #eb8c57 100%)",
    superAlertAction: "#d86b32",
  },
  jade_modern: {
    toolsPanelBg:
      "linear-gradient(180deg, rgba(246, 243, 236, 0.99) 0%, rgba(240, 236, 226, 0.98) 100%)",
    toolsPanelShadow: "0 18rpx 38rpx rgba(33, 82, 73, 0.09)",
    toolsSubtleText: "#6f7367",
    summaryBg:
      "linear-gradient(135deg, rgba(232, 241, 236, 0.96) 0%, rgba(225, 236, 229, 0.98) 100%)",
    summaryBorder: "rgba(59, 118, 102, 0.2)",
    summaryBadgeBg: "rgba(22, 76, 67, 0.1)",
    summaryBadgeColor: "#164c43",
    summaryTitle: "#22463f",
    summaryDesc: "#607068",
    identityBg: "rgba(244, 242, 236, 0.95)",
    identityBorder: "rgba(89, 126, 110, 0.2)",
    identityLabel: "#6e7068",
    identityValue: "#1d6e63",
    toolCardBg: "linear-gradient(180deg, #fcfbf8 0%, #f2eee7 100%)",
    toolCardBorder: "rgba(98, 128, 112, 0.26)",
    toolCardShadow: "0 10rpx 20rpx rgba(43, 91, 82, 0.1)",
    toolIconBg:
      "linear-gradient(135deg, rgba(47, 123, 110, 0.18) 0%, rgba(109, 155, 126, 0.24) 100%)",
    toolIconBorder: "rgba(45, 97, 88, 0.14)",
    toolIconShadow: "0 8rpx 16rpx rgba(43, 91, 82, 0.12)",
    toolIconColor: "#1d6e63",
    toolTitle: "#27463f",
    toolTitleShadow: "0 1rpx 0 rgba(255, 255, 255, 0.72)",
    toolAccentBg: "linear-gradient(180deg, #e8f4ee 0%, #dcebe2 100%)",
    toolAccentBorder: "rgba(62, 127, 110, 0.28)",
    toolAccentShadow: "0 12rpx 22rpx rgba(36, 105, 91, 0.14)",
    toolAccentIconBg: "linear-gradient(135deg, #2b7d70 0%, #3f9b7f 100%)",
    toolAccentIconColor: "#ffffff",
    toolAccentTitle: "#1f5c52",
    toolMerchantBg: "linear-gradient(180deg, #faf8f2 0%, #eee8dd 100%)",
    toolSystemBg: "linear-gradient(180deg, #fcfaf5 0%, #f0eadf 100%)",
    toolAdminBg: "linear-gradient(180deg, #f4f2eb 0%, #ece6d9 100%)",
    superHeroBg:
      "linear-gradient(180deg, rgba(241, 243, 236, 0.99) 0%, rgba(232, 236, 227, 0.99) 100%)",
    superHeroBorder: "rgba(74, 114, 96, 0.18)",
    superBadgeBg: "rgba(27, 111, 99, 0.1)",
    superBadgeColor: "#1b6f63",
    superTitle: "#22463f",
    superDesc: "#65726a",
    superCardBg:
      "linear-gradient(180deg, rgba(252, 250, 246, 0.99) 0%, rgba(243, 239, 231, 0.98) 100%)",
    superCardBorder: "rgba(104, 128, 116, 0.22)",
    superCardShadow: "0 10rpx 22rpx rgba(43, 91, 82, 0.1)",
    superIconBg: "linear-gradient(135deg, #2e7f73 0%, #4ca387 100%)",
    superIconColor: "#ffffff",
    superCardBadgeBg: "rgba(27, 111, 99, 0.1)",
    superCardBadgeColor: "#1b6f63",
    superAction: "#1b6f63",
    superPrimaryBg:
      "linear-gradient(180deg, rgba(232, 244, 238, 0.99) 0%, rgba(221, 236, 228, 0.98) 100%)",
    superPrimaryBorder: "rgba(55, 119, 103, 0.24)",
    superPrimaryShadow: "0 14rpx 28rpx rgba(38, 100, 88, 0.14)",
    superPrimaryIconBg: "linear-gradient(135deg, #26776b 0%, #3d997d 100%)",
    superPrimaryText: "#1d5a51",
    superSecondaryBg:
      "linear-gradient(180deg, rgba(248, 247, 242, 0.99) 0%, rgba(238, 234, 225, 0.98) 100%)",
    superSecondaryBorder: "rgba(136, 147, 126, 0.2)",
    superSecondaryShadow: "0 12rpx 24rpx rgba(60, 99, 88, 0.08)",
    superSecondaryIconBg: "linear-gradient(135deg, #517f6d 0%, #7da07d 100%)",
    superSecondaryAction: "#355f55",
    superAlertBg:
      "linear-gradient(180deg, rgba(244, 247, 239, 0.99) 0%, rgba(235, 241, 231, 0.98) 100%)",
    superAlertBorder: "rgba(88, 125, 108, 0.24)",
    superAlertShadow: "0 12rpx 24rpx rgba(43, 91, 82, 0.1)",
    superAlertIconBg: "linear-gradient(135deg, #397e72 0%, #5f987c 100%)",
    superAlertAction: "#1b6f63",
  },
};
export default {
  name: "my",
  components: {
    barTitle,
  },
  data() {
    const cachedSetting = getCachedSetting();
    return {
      toolsList: [
        {
          id: 1,
          icon: "service",
          code: "service",
          name: "客服中心",
        },
        {
          id: 2,
          icon: "discover",
          code: "news",
          name: "平台资讯",
        },
        {
          id: 4,
          icon: "read",
          code: "accord_center",
          name: "协议中心",
        },
        {
          id: 3,
          icon: "friend",
          code: "friend",
          name: "商家资料",
        },
        {
          id: 5,
          icon: "write",
          code: "release",
          name: "商品发布",
        },
        {
          id: 6,
          icon: "read",
          code: "read",
          name: "核销订单",
        },
        {
          id: 7,
          icon: "activity",
          code: "analytics",
          name: "数据分析",
        },
        {
          id: 8,
          icon: "service",
          code: "renew",
          name: "服务续期",
        },
        {
          id: 9,
          icon: "friend",
          code: "merchant_audit",
          name: "平台商家审核",
        },
        {
          id: 10,
          icon: "read",
          code: "order_audit",
          name: "商家订单核销",
        },
        {
          id: 12,
          icon: "settings",
          code: "settings",
          name: "设置",
        },
      ],
      login: false,
      userInfo: {
        wx_approved: 1,
      },
      merchantInfo: {},
      default_avatar_url: "/static/images/avatar/1.jpg",
      avatarPool: [
        "/static/images/avatar/default-1.svg",
        "/static/images/avatar/default-2.svg",
        "/static/images/avatar/default-3.svg",
        "/static/images/avatar/default-4.svg",
      ],
      goodsReleaseEnabled: resolveGoodsReleaseEnabled(cachedSetting),
      reviewMode: resolveReviewMode(cachedSetting),
      reviewInfo: resolveReviewInfo(cachedSetting),
      uiThemeStyle: resolveUiThemeStyle(cachedSetting),
      memberInfoLoading: false,
      merchantInfoLoading: false,
      memberInfoLoaded: false,
      merchantIdentityLoading: false,
      merchantIdentityList: [],
      currentMerchantIdentityId: getCurrentMerchantIdentityId(),
      merchantPermissionCodes: [],
      recentActionSummary: "",
      accordRuntimeSummary: getAccordRuntimeSummary(),
    };
  },
  props: {
    show: {
      type: Boolean,
      default: true,
    },
    scrollY: {
      type: Number,
      default: 0,
    },
    scrollBottom: {
      type: Number,
      default: 0,
    },
  },
  computed: {
    currentEnvInfo() {
      return getCurrentEnvInfo();
    },
    myEnvDescription() {
      return this.currentEnvInfo.is_prod
        ? "当前为正式环境，我的订单、商家身份和工作台操作会连接线上真实数据。"
        : `当前为${this.currentEnvInfo.label}，适合做订单、商家身份切换和工作台功能联调。`;
    },
    recentReleasedGoodsCard() {
      const recent = cache.get("recent_released_goods", null);
      if (!recent || typeof recent !== "object") {
        return null;
      }
      const id = Number(recent.id || 0);
      const title = String(recent.title || "").trim();
      if (!id && !title) {
        return null;
      }
      return {
        id,
        title,
      };
    },
    myWorkbenchModeText() {
      if (this.login) {
        return "当前状态：待登录";
      }
      if (this.showMerchantWorkbenchSummary) {
        return "当前状态：商家工作台";
      }
      if (this.showFrontendSuperWorkbench) {
        return "当前状态：超管工作台";
      }
      return "当前状态：普通会员工作台";
    },
    myRuntimeStatusText() {
      return this.recentReleasedGoodsCard
        ? "存在最近发布回看入口"
        : "暂无最近发布记录";
    },
    myEntryUsageText() {
      if (this.currentEnvInfo.is_prod) {
        return "入口用途：正式运营";
      }
      if (this.currentEnvInfo.key === "gray") {
        return "入口用途：灰度验收";
      }
      return `入口用途：${getEnvIsolationTag(this.currentEnvInfo)}`;
    },
    myEnvIsolationHealth() {
      return getEnvIsolationHealth();
    },
    myIsolationStatusText() {
      return this.myEnvIsolationHealth.is_isolated_ready
        ? "隔离状态：已就绪"
        : "隔离状态：待处理";
    },
    myReleaseStageText() {
      return getEnvReleaseStageText(this.myEnvIsolationHealth);
    },
    myReleaseHint() {
      return getEnvReleaseHint(this.myEnvIsolationHealth);
    },
    myEnvRiskList() {
      return this.myEnvIsolationHealth.warnings || [];
    },
    profileReadinessList() {
      return getProfileReadinessList();
    },
    myRuntimeHint() {
      return this.currentEnvInfo.is_prod
        ? "正式环境下，工作台里的发布、审核、订单处理和身份切换都会直接作用于真实数据。"
        : "建议先在当前环境验证工作台入口、商家身份切换和发布后回看链路。";
    },
    workbenchReviewHint() {
      if (this.login) {
        return "当前未登录，进入订单、协议中心和商家工作台前会先走登录回跳。";
      }
      if (this.showFrontendSuperWorkbench) {
        return "当前账号包含超管能力，进入审核与跨商家核销前请先确认环境和商家身份。";
      }
      if (this.showMerchantWorkbenchSummary) {
        return "当前账号可进入商家工作台，建议先核对商家状态、身份和发布权限。";
      }
      return "当前以会员工作台为主，可继续核对订单、协议中心和商家入驻入口。";
    },
    workbenchReviewTags() {
      return {
        entry: `主入口：${this.workbenchEntryTitle}`,
        identity: this.currentMerchantIdentity
          ? `商家身份：${this.currentMerchantIdentity.identity_name || "已选身份"}`
          : "商家身份：未选择",
        merchant: `商家状态：${this.merchantStatusText}`,
        release: this.releaseAllowed ? "商品发布：可用" : "商品发布：受限",
      };
    },
    workbenchRolloutHint() {
      if (this.currentEnvInfo.is_prod) {
        return "当前工作台已经连接正式环境，订单、身份切换、审核与发布都要按真实运营流程处理。";
      }
      if (this.myEnvIsolationHealth.has_example_host) {
        return "当前环境仍是占位域名，只适合做页面结构和入口核对，不能承担真实联调或灰度。";
      }
      return "当前工作台适合做测试或灰度验收，建议优先核对订单入口、协议补记和商家身份切换。";
    },
    workbenchRolloutChecklist() {
      return [
        { label: "当前环境", value: this.currentEnvInfo.label || "未配置" },
        {
          label: "工作台模式",
          value: this.myWorkbenchModeText.replace("当前状态：", ""),
        },
        { label: "协议补记", value: this.workbenchHubBadgeText },
        { label: "发布阶段", value: this.myReleaseStageText },
      ];
    },
    workbenchRollbackHint() {
      if (this.currentEnvInfo.is_prod) {
        return "正式工作台如发现异常，建议立即暂停高风险操作，优先回退旧入口或切回灰度环境继续排查。";
      }
      return "灰度期间建议保留旧工作台入口，若发现审核、订单或身份链路异常，可先回切旧入口继续使用。";
    },
    workbenchRiskHint() {
      if (this.login) {
        return "未登录状态下，业务入口会先跳登录页并回带目标地址。";
      }
      if (this.currentEnvInfo.is_prod) {
        return "当前为正式环境，身份切换、商家审核、订单核销和商品发布都会直接影响真实数据。";
      }
      if (this.showFrontendSuperWorkbench) {
        return "当前为非正式环境，建议优先验证超管入口、审核链路、订单核销和结果回显。";
      }
      if (this.showMerchantWorkbenchSummary) {
        return "当前为非正式环境，建议优先验证商家资料、商品发布、续费和核销入口。";
      }
      return "当前为非正式环境，建议优先验证订单入口、协议中心和商家入驻承接链路。";
    },
    canRetryWorkbenchAccords() {
      return (
        !this.login && Number(this.accordRuntimeSummary.pending_count || 0) > 0
      );
    },
    workbenchHubBadgeText() {
      if (this.login) {
        return "待登录";
      }
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
    workbenchHubBadgeClass() {
      if (this.login) {
        return "is-neutral";
      }
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
    workbenchHubHint() {
      if (this.login) {
        return "登录后可继续查看协议补记状态、设置页环境和业务入口承接情况。";
      }
      if (Number(this.accordRuntimeSummary.pending_count || 0) > 0) {
        return `当前仍有 ${this.accordRuntimeSummary.pending_count} 项协议待补记，建议先补记完成再继续关键写操作。`;
      }
      if (this.accordRuntimeSummary.last_attempt_status === "success") {
        return "最近一次协议补记已成功完成，可继续进入订单、商家工作台和资料修改页面。";
      }
      if (this.accordRuntimeSummary.last_attempt_status === "fail") {
        return "最近一次协议补记失败，建议先去协议中心或设置页确认当前环境与登录态。";
      }
      return "当前还没有补记记录，可先从登录、下单或商家入驻链路触发一次协议同步。";
    },
    workbenchHubTags() {
      return {
        protocol: this.login
          ? "协议状态：待登录查看"
          : `协议状态：${this.workbenchHubBadgeText}`,
        pending: `待补记：${Number(this.accordRuntimeSummary.pending_count || 0)} 项`,
        scene: `最近场景：${this.formatAccordScene(this.accordRuntimeSummary.last_scene)}`,
        success: `最近成功：${this.formatAccordTime(this.accordRuntimeSummary.last_success_at)}`,
      };
    },
    workbenchHubRiskHint() {
      if (this.login) {
        return "未登录时，协议中心与业务入口都会先跳登录页并回带目标地址。";
      }
      if (Number(this.accordRuntimeSummary.pending_count || 0) > 0) {
        return "存在待补记协议时，说明部分协议写入还未完全落库，建议先完成补记重试。";
      }
      if (this.accordRuntimeSummary.last_attempt_status === "fail") {
        return this.accordRuntimeSummary.last_error_message
          ? `最近失败原因：${this.accordRuntimeSummary.last_error_message}`
          : "最近一次协议补记失败，请复核当前接口环境、登录态和网络状态。";
      }
      return "";
    },
    primaryWorkbenchEntry() {
      const superTools = Array.isArray(this.superManageTools)
        ? this.superManageTools
        : [];
      if (superTools.length > 0) {
        return superTools[0];
      }
      const tools = Array.isArray(this.recommendedTools)
        ? this.recommendedTools
        : [];
      return tools.length > 0 ? tools[0] : null;
    },
    workbenchEntryTitle() {
      const entry = this.primaryWorkbenchEntry;
      if (!entry) {
        return "当前暂无可用工作台入口";
      }
      return `${entry.displayName || entry.name || "工作台入口"}已就位`;
    },
    workbenchEntryDesc() {
      if (this.showFrontendSuperWorkbench) {
        return this.currentEnvInfo.is_prod
          ? "当前为正式环境，超管入口会直接使用真实权限上下文处理审核和订单。"
          : "建议先在当前环境验证超管入口、审核链路和订单处理回显。";
      }
      if (this.showMerchantWorkbenchSummary) {
        return this.currentEnvInfo.is_prod
          ? "当前为正式环境，商家工作台里的发布、核销和数据查看都会连接真实业务数据。"
          : "建议先在当前环境验证商家入口、身份切换和发布回跳链路。";
      }
      return this.currentEnvInfo.is_prod
        ? "当前入口将直接连接真实业务页面，请确认身份和环境后再继续。"
        : "建议先从这里继续做工作台入口联调和页面可达验证。";
    },
    workbenchEntryActionText() {
      if (!this.primaryWorkbenchEntry) {
        return "暂不可用";
      }
      return this.showFrontendSuperWorkbench ? "进入超管工作台" : "进入工作台";
    },
    safeCartCount() {
      return this.toSafeNumber(this.userInfo && this.userInfo.cart_num);
    },
    safeStatusNums() {
      const source =
        this.userInfo &&
        this.userInfo.status_nums &&
        typeof this.userInfo.status_nums === "object"
          ? this.userInfo.status_nums
          : {};
      return {
        all: this.toSafeNumber(source.all),
        p_pay: this.toSafeNumber(source.p_pay),
        p_receipt: this.toSafeNumber(source.p_receipt),
        p_evaluate: this.toSafeNumber(source.p_evaluate),
        p_shipment: this.toSafeNumber(source.p_shipment),
        success: this.toSafeNumber(source.success),
        service: this.toSafeNumber(source.service),
      };
    },
    myOrderEntries() {
      return [
        {
          code: "all",
          label: "全部",
          hint: "全部订单",
          count: this.safeStatusNums.all,
          url: "/pages/order/list",
          tone: "is-all",
          iconClass: "cuIcon-presentfill",
        },
        {
          code: "p_pay",
          label: "待付款",
          hint: "待支付订单",
          count: this.safeStatusNums.p_pay,
          url: "/pages/order/list?status=0",
          tone: "is-pay",
          iconClass: "cuIcon-sponsorfill",
        },
        {
          code: "p_receipt",
          label: "待收货",
          hint: "待签收订单",
          count: this.safeStatusNums.p_receipt,
          url: "/pages/order/list?status=2",
          tone: "is-receipt",
          iconClass: "cuIcon-cartfill",
        },
        {
          code: "p_evaluate",
          label: "待评价",
          hint: "待反馈体验",
          count: this.safeStatusNums.p_evaluate,
          url: "/pages/order/list?status=3",
          tone: "is-evaluate",
          iconClass: "cuIcon-favorfill",
        },
      ];
    },
    guestAvatarUrl() {
      return this.resolveSafeAvatarUrl(
        this.default_avatar_url,
        this.avatarPool[0] || "/static/images/avatar/1.jpg",
      );
    },
    guestAvatarStyle() {
      return this.buildAvatarStyle(this.guestAvatarUrl, true);
    },
    displayAvatarUrl() {
      const avatarUrl = this.normalizeText(this.userInfo.avatar_url);
      if (this.hasCustomAvatar && avatarUrl) {
        return this.resolveSafeAvatarUrl(
          avatarUrl,
          this.pickAvatarBySeed(this.getUserSeed()),
        );
      }
      return this.resolveSafeAvatarUrl(
        this.pickAvatarBySeed(this.getUserSeed()),
        "/static/images/avatar/1.jpg",
      );
    },
    displayAvatarStyle() {
      return this.buildAvatarStyle(this.displayAvatarUrl);
    },
    displayNickname() {
      const merchantTitle = this.normalizeText(this.merchantInfo.title);
      if (merchantTitle) {
        return this.merchantDisplayTitle;
      }
      const realName = this.normalizeText(this.userInfo.name);
      if (realName) {
        return realName;
      }
      const nickname = this.normalizeText(this.userInfo.nickname);
      if (nickname) {
        return nickname;
      }
      const mobile =
        this.normalizeText(this.userInfo.mobile) ||
        this.normalizeText(this.userInfo.phone);
      if (mobile) {
        return `优选用户${mobile.slice(-4)}`;
      }
      const seed = this.getUserSeed();
      return `优选用户${String(seed % 1000).padStart(3, "0")}`;
    },
    hasCustomAvatar() {
      const avatarId = Number(this.userInfo.avatar_id || 0);
      const headimgurl = this.normalizeText(this.userInfo.headimgurl);
      const avatarUrl = this.normalizeText(this.userInfo.avatar_url);
      const defaultAvatar = this.normalizeText(this.default_avatar_url);
      if (avatarId > 0 || headimgurl) {
        return true;
      }
      return !!avatarUrl && avatarUrl !== defaultAvatar;
    },
    currentMerchantIdentity() {
      const currentId = Number(this.currentMerchantIdentityId || 0);
      const list = Array.isArray(this.merchantIdentityList)
        ? this.merchantIdentityList
        : [];
      return this.resolveCurrentMerchantIdentity(list, currentId);
    },
    currentMerchantIdentityIndex() {
      const list = Array.isArray(this.merchantIdentityList)
        ? this.merchantIdentityList
        : [];
      const currentId = Number(
        (this.currentMerchantIdentity &&
          this.currentMerchantIdentity.mer_user_id) ||
          0,
      );
      const index = list.findIndex(
        (item) => Number(item.mer_user_id || 0) === currentId,
      );
      return index >= 0 ? index : 0;
    },
    merchantIdentityOptions() {
      return (
        Array.isArray(this.merchantIdentityList)
          ? this.merchantIdentityList
          : []
      ).map((item) => ({
        mer_user_id: Number(item.mer_user_id || 0),
        merchant_id: Number(item.merchant_id || 0),
        label:
          item.identity_name ||
          (item.merchant && item.merchant.title) ||
          "商家身份",
      }));
    },
    merchantPermissionMap() {
      const map = {};
      (Array.isArray(this.merchantPermissionCodes)
        ? this.merchantPermissionCodes
        : []
      ).forEach((code) => {
        if (code) {
          map[String(code)] = 1;
        }
      });
      return map;
    },
    merchantDisplayTitle() {
      return maskMerchantTitle(this.merchantInfo.title, "商家服务中心");
    },
    reviewBadgeText() {
      return "平台服务";
    },
    reviewDisplayTitle() {
      return (
        this.normalizeText(this.reviewInfo.system_name) ||
        this.normalizeText(this.reviewInfo.review_hero_title) ||
        "涛冠优选"
      );
    },
    reviewDisplayDesc() {
      return (
        this.normalizeText(this.reviewInfo.review_hero_desc) ||
        "当前页面展示平台服务入口、官方资讯与联系渠道，可继续查看平台服务内容。"
      );
    },
    hasMerchantRecord() {
      return (
        Number(this.merchantInfo.id || 0) > 0 ||
        Number(this.userInfo.is_merchant || 0) === 1 ||
        !!this.currentMerchantIdentity
      );
    },
    merchantAuthApproved() {
      if (Number(this.merchantInfo.id || 0) > 0) {
        return Number(this.merchantInfo.auth_state || 0) === 1;
      }
      return Number(this.userInfo.is_merchant || 0) === 1;
    },
    isMerchantSuper() {
      return (
        Number(
          (this.currentMerchantIdentity &&
            this.currentMerchantIdentity.merchant_user &&
            this.currentMerchantIdentity.merchant_user.is_super) ||
            0,
        ) === 1
      );
    },
    hasMerchantSuperCapability() {
      return (
        this.isMerchantSuper &&
        this.hasMerchantPermission("verify_cross_merchant_order")
      );
    },
    releaseAllowed() {
      return (
        this.hasMerchantRecord &&
        this.merchantAuthApproved &&
        Number(this.merchantInfo.is_expired || 0) !== 1 &&
        this.goodsReleaseEnabled &&
        this.hasMerchantPermission("publish_product")
      );
    },
    merchantStatusText() {
      if (!this.hasMerchantRecord) {
        return "未入驻";
      }
      if (
        !Number(this.merchantInfo.id || 0) &&
        Number(this.userInfo.is_merchant || 0) === 1
      ) {
        return "审核通过";
      }
      if (Number(this.merchantInfo.auth_state || 0) === 0) {
        return "待审核";
      }
      if (Number(this.merchantInfo.auth_state || 0) === 2) {
        return "审核驳回";
      }
      if (Number(this.merchantInfo.is_expired || 0) === 1) {
        return "已到期";
      }
      return (
        this.normalizeText(this.merchantInfo.expire_status_title) || "正常有效"
      );
    },
    merchantStatusTone() {
      if (!this.hasMerchantRecord) {
        return "is-idle";
      }
      if (
        !Number(this.merchantInfo.id || 0) &&
        Number(this.userInfo.is_merchant || 0) === 1
      ) {
        return "is-safe";
      }
      if (
        Number(this.merchantInfo.auth_state || 0) === 2 ||
        Number(this.merchantInfo.is_expired || 0) === 1
      ) {
        return "is-danger";
      }
      if (
        Number(this.merchantInfo.auth_state || 0) === 0 ||
        Number(this.merchantInfo.should_remind || 0) === 1
      ) {
        return "is-warning";
      }
      return "is-safe";
    },
    merchantServiceBadge() {
      return this.hasMerchantRecord ? "商家服务" : "入驻入口";
    },
    merchantServiceTitle() {
      if (!this.hasMerchantRecord) {
        return "申请商家入驻";
      }
      if (
        !Number(this.merchantInfo.id || 0) &&
        Number(this.userInfo.is_merchant || 0) === 1
      ) {
        return this.displayNickname || "商家服务中心";
      }
      return this.merchantDisplayTitle || "商家服务中心";
    },
    merchantServiceDesc() {
      if (!this.hasMerchantRecord) {
        return "提交商家资料后可开通商家能力、查看数据分析、处理核销和续期。";
      }
      if (
        !Number(this.merchantInfo.id || 0) &&
        Number(this.userInfo.is_merchant || 0) === 1
      ) {
        return "当前账号已开通商家能力，可直接进入商家资料、数据分析和订单核销。";
      }
      if (Number(this.merchantInfo.auth_state || 0) === 0) {
        return "商家资料已提交，当前等待平台审核，可继续查看资料和审核状态。";
      }
      if (Number(this.merchantInfo.auth_state || 0) === 2) {
        return (
          this.normalizeText(this.merchantInfo.auth_msg) ||
          "审核未通过，请根据原因完善资料后重新提交。"
        );
      }
      if (Number(this.merchantInfo.is_expired || 0) === 1) {
        return "商家服务已到期，续期完成后可恢复数据分析、核销等功能。";
      }
      if (Number(this.merchantInfo.should_remind || 0) === 1) {
        return `商家服务将在 ${this.merchantInfo.days_left || 0} 天后到期，请及时续期，避免影响业务使用。`;
      }
      return "商家资料、商品发布、数据分析、服务续期和核销订单都可以从这里快速进入。";
    },
    merchantServiceActions() {
      const actions = [];
      actions.push({
        code: "merchant_info",
        label: this.hasMerchantRecord ? "商家资料" : "申请入驻",
        url: "/pages/merchant/apply",
      });
      if (this.merchantAuthApproved) {
        if (this.releaseAllowed) {
          actions.push({
            code: "merchant_release",
            label: "商品发布",
            url: "/pages/app/release",
          });
        }
        actions.push({
          code: "merchant_analytics",
          label: "数据分析",
          url: "/pages/merchant/analytics",
        });
        if (
          this.pendingWriteoffCount > 0 &&
          Number(this.merchantInfo.is_expired || 0) !== 1
        ) {
          actions.push({
            code: "merchant_writeoff",
            label: "订单核销",
            url: "/pages/order/mer_list",
          });
        }
        actions.push({
          code: "merchant_renew",
          label: "续费中心",
          url: "/pages/merchant/renew",
        });
      }
      return actions;
    },
    showMerchantWorkbenchSummary() {
      return (
        !this.reviewMode &&
        !this.login &&
        this.hasMerchantRecord &&
        !this.showFrontendSuperWorkbench
      );
    },
    merchantWorkbenchSummaryTitle() {
      return this.merchantServiceTitle;
    },
    merchantWorkbenchSummaryDesc() {
      return this.merchantServiceDesc;
    },
    recommendedTools() {
      const order = this.reviewMode
        ? ["service", "news", "accord_center"]
        : [
            "service",
            "news",
            "friend",
            "read",
            "analytics",
            "renew",
            "settings",
            "release",
          ];
      return order
        .map((code) => this.findToolByCode(code))
        .filter((item) => item && this.showTool(item))
        .map((item) => ({
          ...item,
          displayName: this.getToolDisplayName(item),
          badge: this.getToolBadge(item),
          cardClass: this.getToolCardClass(item),
          iconClass: `cuIcon-${item.icon || ""}`,
        }));
    },
    recommendedToolsGridClass() {
      return "is-three-column";
    },
    superManageTools() {
      if (this.reviewMode || this.login || !this.hasMerchantSuperCapability) {
        return [];
      }
      const merchantAuditTool = this.findToolByCode("merchant_audit");
      const orderAuditTool = this.findToolByCode("order_audit");

      return [
        merchantAuditTool
          ? {
              ...merchantAuditTool,
              displayName: "平台商家审核",
            }
          : null,
        orderAuditTool
          ? {
              ...orderAuditTool,
              code: "cross_writeoff",
              displayName: "商家订单核销",
            }
          : null,
      ]
        .filter(Boolean)
        .map((item) => ({
          ...item,
          badge: this.getSuperToolBadge(item),
          caption: this.getSuperToolCaption(item),
          cardClass: this.getSuperToolCardClass(item),
          iconClass: `cuIcon-${item.icon || ""}`,
        }));
    },
    showFrontendSuperWorkbench() {
      return (
        this.hasMerchantSuperCapability && this.superManageTools.length > 0
      );
    },
    toolsHeaderTitle() {
      return this.reviewMode ? "服务入口" : "推荐工具";
    },
    toolsHeaderRightText() {
      return this.reviewMode ? "官方服务" : "更多";
    },
    myThemeCssVars() {
      const palette =
        MY_PAGE_THEME_PRESETS[this.uiThemeStyle] ||
        MY_PAGE_THEME_PRESETS.origin;
      const headPalette =
        MY_HEAD_THEME_PRESETS[this.uiThemeStyle] ||
        MY_HEAD_THEME_PRESETS.origin;
      return buildCssVars({
        "--my-head-bg": headPalette.headBg,
        "--my-head-text": headPalette.headText,
        "--my-head-subtle": headPalette.headSubtle,
        "--my-head-glow-two": headPalette.headGlowTwo,
        "--my-head-glass-bg": headPalette.glassBg,
        "--my-head-glass-border": headPalette.glassBorder,
        "--my-head-glass-shadow": headPalette.glassShadow,
        "--my-head-avatar-shadow": headPalette.avatarShadow,
        "--my-head-title-shadow": headPalette.titleShadow,
        "--my-head-stat-divider": headPalette.statDivider,
        "--my-head-tip-bg": headPalette.tipBg,
        "--my-head-tip-border": headPalette.tipBorder,
        "--my-head-tip-shadow": headPalette.tipShadow,
        "--my-head-tip-text": headPalette.tipText,
        "--my-tools-panel-bg": palette.toolsPanelBg,
        "--my-tools-panel-shadow": palette.toolsPanelShadow,
        "--my-tools-subtle-text": palette.toolsSubtleText,
        "--my-summary-bg": palette.summaryBg,
        "--my-summary-border": palette.summaryBorder,
        "--my-summary-badge-bg": palette.summaryBadgeBg,
        "--my-summary-badge-color": palette.summaryBadgeColor,
        "--my-summary-title": palette.summaryTitle,
        "--my-summary-desc": palette.summaryDesc,
        "--my-identity-bg": palette.identityBg,
        "--my-identity-border": palette.identityBorder,
        "--my-identity-label": palette.identityLabel,
        "--my-identity-value": palette.identityValue,
        "--my-tool-card-bg": palette.toolCardBg,
        "--my-tool-card-border": palette.toolCardBorder,
        "--my-tool-card-shadow": palette.toolCardShadow,
        "--my-tool-icon-bg": palette.toolIconBg,
        "--my-tool-icon-border": palette.toolIconBorder,
        "--my-tool-icon-shadow": palette.toolIconShadow,
        "--my-tool-icon-color": palette.toolIconColor,
        "--my-tool-title": palette.toolTitle,
        "--my-tool-title-shadow": palette.toolTitleShadow,
        "--my-tool-accent-bg": palette.toolAccentBg,
        "--my-tool-accent-border": palette.toolAccentBorder,
        "--my-tool-accent-shadow": palette.toolAccentShadow,
        "--my-tool-accent-icon-bg": palette.toolAccentIconBg,
        "--my-tool-accent-icon-color": palette.toolAccentIconColor,
        "--my-tool-accent-title": palette.toolAccentTitle,
        "--my-tool-merchant-bg": palette.toolMerchantBg,
        "--my-tool-system-bg": palette.toolSystemBg,
        "--my-tool-admin-bg": palette.toolAdminBg,
        "--my-super-hero-bg": palette.superHeroBg,
        "--my-super-hero-border": palette.superHeroBorder,
        "--my-super-badge-bg": palette.superBadgeBg,
        "--my-super-badge-color": palette.superBadgeColor,
        "--my-super-title": palette.superTitle,
        "--my-super-desc": palette.superDesc,
        "--my-super-card-bg": palette.superCardBg,
        "--my-super-card-border": palette.superCardBorder,
        "--my-super-card-shadow": palette.superCardShadow,
        "--my-super-icon-bg": palette.superIconBg,
        "--my-super-icon-color": palette.superIconColor,
        "--my-super-card-badge-bg": palette.superCardBadgeBg,
        "--my-super-card-badge-color": palette.superCardBadgeColor,
        "--my-super-action": palette.superAction,
        "--my-super-primary-bg": palette.superPrimaryBg,
        "--my-super-primary-border": palette.superPrimaryBorder,
        "--my-super-primary-shadow": palette.superPrimaryShadow,
        "--my-super-primary-icon-bg": palette.superPrimaryIconBg,
        "--my-super-primary-text": palette.superPrimaryText,
        "--my-super-secondary-bg": palette.superSecondaryBg,
        "--my-super-secondary-border": palette.superSecondaryBorder,
        "--my-super-secondary-shadow": palette.superSecondaryShadow,
        "--my-super-secondary-icon-bg": palette.superSecondaryIconBg,
        "--my-super-secondary-action": palette.superSecondaryAction,
        "--my-super-alert-bg": palette.superAlertBg,
        "--my-super-alert-border": palette.superAlertBorder,
        "--my-super-alert-shadow": palette.superAlertShadow,
        "--my-super-alert-icon-bg": palette.superAlertIconBg,
        "--my-super-alert-action": palette.superAlertAction,
      });
    },
    toolsChevronVisible() {
      return true;
    },
    toolsSectionTitle() {
      if (this.reviewMode) {
        return "服务入口";
      }
      if (this.showMerchantWorkbenchSummary) {
        return "商家工作台";
      }
      return "推荐工具";
    },
    toolsSectionRightText() {
      if (this.reviewMode) {
        return "官方服务";
      }
      if (this.showMerchantWorkbenchSummary) {
        return this.merchantStatusText;
      }
      return "更多";
    },
    showToolsChevron() {
      return !this.showMerchantWorkbenchSummary;
    },
    pendingWriteoffCount() {
      return Number(this.merchantInfo.pending_writeoff_count || 0);
    },
    pendingWriteoffCountText() {
      return this.pendingWriteoffCount > 99
        ? "99+"
        : String(this.pendingWriteoffCount || 0);
    },
    pendingWriteoffAmountText() {
      return Number(this.merchantInfo.pending_writeoff_amount || 0).toFixed(2);
    },
    pendingWriteoffOldestTime() {
      return this.normalizeText(this.merchantInfo.pending_writeoff_oldest_time);
    },
    showPendingWriteoffCard() {
      return (
        !this.reviewMode &&
        Number(this.userInfo.is_merchant || 0) === 1 &&
        Number(this.merchantInfo.is_expired || 0) !== 1 &&
        this.hasMerchantPermission(
          "verify_order",
          "verify_cross_merchant_order",
        ) &&
        this.pendingWriteoffCount > 0
      );
    },
  },
  created() {},
  onShow() {
    this.refreshAccordRuntimeSummary();
    this.refreshPageState();
  },
  mounted() {
    this.syncDefaultAvatar();
    this.refreshPageState();
    uni.pageScrollTo({
      scrollTop: 0,
      duration: 0,
    });
  },
  methods: {
    setRecentActionSummary(action, extra = "") {
      this.recentActionSummary = `已执行${action}${extra ? `，${extra}` : ""}。`;
    },
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
    refreshAccordRuntimeSummary() {
      this.accordRuntimeSummary = getAccordRuntimeSummary();
    },
    openAccordCenterFromWorkbench() {
      this.setRecentActionSummary("打开协议中心", this.workbenchHubBadgeText);
      this.setupTap("/pages/system/accord-center");
    },
    openSettingsFromWorkbench() {
      this.setRecentActionSummary(
        "打开账号设置",
        this.currentEnvInfo.label || "当前环境",
      );
      this.setupTap("/pages/my/set-up");
    },
    retryWorkbenchAccords() {
      if (this.login) {
        this.setRecentActionSummary("前往登录", "来源：协议补记重试");
        this.loginUrlTap();
        return;
      }
      if (!this.canRetryWorkbenchAccords) {
        uni.showToast({
          icon: "none",
          title: "当前没有待补记协议",
        });
        return;
      }
      this.setRecentActionSummary(
        "重试待补记协议",
        `${this.accordRuntimeSummary.pending_count} 项`,
      );
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
    toSafeNumber(value) {
      const parsed = Number(value);
      return Number.isFinite(parsed) ? parsed : 0;
    },
    openRecentReleasedGoods() {
      this.setRecentActionSummary("打开最近发布回看入口");
      uni.switchTab({
        url: "/pages/app/sell",
      });
    },
    openPrimaryWorkbenchEntry() {
      const entry = this.primaryWorkbenchEntry;
      if (!entry) {
        return;
      }
      this.setRecentActionSummary(
        "打开主工作台入口",
        entry.displayName || entry.name || "工作台",
      );
      this.gridTap(entry);
    },
    hasCachedAuth() {
      return !!this.normalizeText(cache.get("token", ""));
    },
    refreshPageState() {
      this.applySetting(this.$store.state.setting || getCachedSetting());
      this.syncDefaultAvatar();
      this.refreshAccordRuntimeSummary();
      this.getUserInfo();
      if (this.reviewMode) {
        this.merchantInfo = {};
        this.memberInfoLoaded = false;
        this.merchantIdentityList = [];
        this.currentMerchantIdentityId = 0;
        this.merchantPermissionCodes = [];
      } else {
        this.ensureMemberInfoLoaded();
      }
      this.loadRemoteSetting();
    },
    loadRemoteSetting() {
      api
        .getSetting({})
        .then((res) => {
          const setting = res.data || {};
          const previousReviewMode = this.reviewMode;
          cache.set("setting", setting);
          store.commit("setSetting", setting);
          applyUiThemeFromSetting(setting);
          this.applySetting(setting);
          this.syncDefaultAvatar();
          this.refreshAccordRuntimeSummary();
          this.getUserInfo();
          if (this.reviewMode) {
            this.merchantInfo = {};
            this.memberInfoLoaded = false;
            return;
          }
          if (previousReviewMode) {
            this.ensureMemberInfoLoaded(true);
          }
        })
        .catch(() => {});
    },
    applySetting(setting = {}) {
      this.goodsReleaseEnabled = resolveGoodsReleaseEnabled(setting);
      this.reviewMode = resolveReviewMode(setting);
      this.reviewInfo = resolveReviewInfo(setting);
      this.uiThemeStyle = resolveUiThemeStyle(setting);
      this.syncReviewTabBar();
    },
    syncReviewTabBar() {
      /* #ifdef MP-WEIXIN */
      if (this.reviewMode) {
        uni.hideTabBar();
      } else {
        uni.showTabBar();
      }
      /* #endif */
    },
    syncDefaultAvatar() {
      if (this.$store.state.setting && this.$store.state.setting.member) {
        if (this.$store.state.setting.member.default_avatar_url) {
          this.default_avatar_url =
            this.$store.state.setting.member.default_avatar_url;
        }
      } else if (cache.get("setting")) {
        const data = cache.get("setting");
        if (data.member && data.member.default_avatar_url) {
          this.default_avatar_url = data.member.default_avatar_url;
        }
      }
    },
    loginUrlTap() {
      this.setRecentActionSummary("前往登录", "来源：我的页面");
      setLoginRedirect("/pages/app/my");
      uni.navigateTo({
        url: buildLoginRedirectUrl("/pages/app/my"),
      });
    },
    getMiniappLoginCode() {
      return new Promise((resolve, reject) => {
        const onSuccess = (result) => {
          const code = this.normalizeText(result && result.code);
          if (!code) {
            reject(new Error("empty_code"));
            return;
          }
          resolve(code);
        };
        const onFail = (error) => {
          reject(error || new Error("login_failed"));
        };

        // #ifdef MP-WEIXIN
        if (typeof uni !== "undefined" && typeof uni.login === "function") {
          uni.login({
            provider: "weixin",
            success: onSuccess,
            fail: () => {
              if (typeof wx !== "undefined" && typeof wx.login === "function") {
                wx.login({
                  success: onSuccess,
                  fail: onFail,
                });
                return;
              }
              onFail(new Error("login_failed"));
            },
          });
          return;
        }
        if (typeof wx !== "undefined" && typeof wx.login === "function") {
          wx.login({
            success: onSuccess,
            fail: onFail,
          });
          return;
        }
        // #endif
        onFail(new Error("login_failed"));
      });
    },
    // 获取用户手机号
    onGetPhoneNumber(e) {
      if (e.detail.errMsg !== "getPhoneNumber:ok") {
        uni.showToast({
          icon: "none",
          title: "您已拒绝登录",
        });
        return;
      }
      const phoneCode = this.normalizeText(e.detail.code);
      if (!phoneCode) {
        uni.showToast({
          icon: "none",
          title: "手机号授权凭证获取失败",
        });
        return;
      }
      this.getMiniappLoginCode()
        .then((loginCode) =>
          api.miniappLogin({
            code: loginCode,
            phone_code: phoneCode,
            register: 1,
          }),
        )
        .then((res) => {
          cache.set("userInfo", res.data, 1296000);
          cache.set("token", res.data.ApiToken, 1296000);
          if (res.data.phone) {
            cache.set("phone", res.data.phone);
            cache.set("account", res.data.phone);
          }
          return bestEffortAcceptAccords(
            {
              scene: "login",
              accord_uniques: ["user_agreement", "privacy_policy"],
            },
            {
              toast: true,
              message: "协议记录稍后自动补记，已为您完成登录",
            },
          ).then(() => {
            store.commit("login");
            this.login = false;
            this.getMemberInfo();
            uni.showToast({
              icon: "none",
              position: "bottom",
              title: "登录成功",
            });
          });
        })
        .catch((error) => {
          if (error && (error.code || error.msg)) {
            return;
          }
          uni.showToast({
            icon: "none",
            title: "微信登录凭证获取失败，请重试",
          });
        });
    },
    //获取用户信息
    getUserInfo() {
      if (this.reviewMode) {
        this.login = false;
        return;
      }
      if (cache.get("userInfo")) {
        this.login = false;
        this.userInfo = cache.get("userInfo");
        if (!this.$store.state.hasLogin) {
          store.commit("login");
        }
      } else if (this.hasCachedAuth()) {
        this.login = false;
        if (!this.$store.state.hasLogin) {
          store.commit("login");
        }
      } else {
        this.login = true;
        this.userInfo = {};
        this.merchantInfo = {};
      }
    },
    //获取用户信息
    getMemberInfo() {
      return this.ensureMemberInfoLoaded(true);
    },
    ensureMemberInfoLoaded(force = false) {
      if (this.reviewMode || !this.hasCachedAuth()) {
        this.memberInfoLoaded = false;
        this.merchantIdentityList = [];
        this.currentMerchantIdentityId = 0;
        this.merchantPermissionCodes = [];
        return Promise.resolve();
      }
      if (this.memberInfoLoading && !force) {
        return Promise.resolve();
      }
      if (this.memberInfoLoaded && !force) {
        return Promise.resolve();
      }
      this.memberInfoLoading = true;
      return api
        .getMemberInfo({})
        .then((res) => {
          this.userInfo = res.data;
          this.memberInfoLoaded = true;
          return this.loadMerchantIdentityState(force).then((list) => {
            if (
              (Array.isArray(list) && list.length > 0) ||
              Number(res.data && res.data.is_merchant) === 1
            ) {
              return this.getMerchantInfo(force);
            }
            this.merchantInfo = {};
            return Promise.resolve();
          });
        })
        .finally(() => {
          this.memberInfoLoading = false;
        });
    },
    getMerchantInfo(force = false) {
      if (this.merchantInfoLoading && !force) {
        return Promise.resolve();
      }
      this.merchantInfoLoading = true;
      return api
        .merchantInfo({})
        .then((res) => {
          this.merchantInfo = res.data || {};
          this.merchantPermissionCodes = this.resolveMerchantPermissionCodes(
            this.merchantInfo,
          );
          if (Number(this.merchantInfo.current_mer_user_id || 0) > 0) {
            this.currentMerchantIdentityId = Number(
              this.merchantInfo.current_mer_user_id || 0,
            );
            setCurrentMerchantIdentityId(this.currentMerchantIdentityId);
          }
          this.handleMerchantExpireReminder();
          this.handlePendingWriteoffReminder();
        })
        .catch(() => {
          this.merchantInfo = {};
        })
        .finally(() => {
          this.merchantInfoLoading = false;
        });
    },
    resolveMerchantPermissionCodes(merchantInfo = {}) {
      const permissions = [];
      const isApprovedMerchant =
        (Number(merchantInfo.id || 0) > 0 &&
          Number(merchantInfo.auth_state || 0) === 1) ||
        (!Number(merchantInfo.id || 0) &&
          Number(this.userInfo.is_merchant || 0) === 1);
      if (isApprovedMerchant) {
        permissions.push(
          "edit_profile",
          "admin_manage_merchant",
          "verify_order",
          "view_stats",
          "publish_product",
        );
      }
      if (Number(merchantInfo.merchant_system_super || 0) === 1) {
        permissions.push("verify_cross_merchant_order");
      }
      if (
        Array.isArray(merchantInfo.frontend_permission_codes) &&
        merchantInfo.frontend_permission_codes.length > 0
      ) {
        permissions.push.apply(
          permissions,
          merchantInfo.frontend_permission_codes,
        );
      }
      return Array.from(new Set(permissions.filter(Boolean)));
    },
    handleMerchantExpireReminder() {
      const merchant = this.merchantInfo || {};
      if (!merchant.id) {
        return;
      }
      const isExpired = Number(merchant.is_expired || 0) === 1;
      const shouldRemind = Number(merchant.should_remind || 0) === 1;
      if (!isExpired && !shouldRemind) {
        return;
      }
      const reminderKey = this.getMerchantReminderCacheKey(
        merchant,
        isExpired ? "expired" : "remind",
      );
      if (cache.get(reminderKey)) {
        return;
      }
      cache.set(reminderKey, 1, 86400);
      this.showMerchantExpireDialog(merchant, false);
    },
    handlePendingWriteoffReminder() {
      const merchant = this.merchantInfo || {};
      if (
        this.reviewMode ||
        !merchant.id ||
        Number(merchant.is_expired || 0) === 1 ||
        this.pendingWriteoffCount <= 0
      ) {
        return;
      }
      const reminderKey = this.getMerchantReminderCacheKey(
        merchant,
        `writeoff_${this.pendingWriteoffCount}`,
      );
      if (cache.get(reminderKey)) {
        return;
      }
      cache.set(reminderKey, 1, 43200);
      uni.showModal({
        title: "订单核销提醒",
        content: `您当前还有 ${this.pendingWriteoffCountText} 笔待核销订单，待核销金额 ¥${this.pendingWriteoffAmountText}，建议及时处理，避免遗漏。`,
        confirmText: "去处理",
        cancelText: "知道了",
        success: (res) => {
          if (res.confirm) {
            this.openPendingWriteoff();
          }
        },
      });
    },
    getMerchantReminderCacheKey(merchant, scene = "remind") {
      const date = new Date();
      const day = [
        date.getFullYear(),
        String(date.getMonth() + 1).padStart(2, "0"),
        String(date.getDate()).padStart(2, "0"),
      ].join("");
      return `merchant_expire_notice_${scene}_${merchant.id}_${day}`;
    },
    showMerchantExpireDialog(merchant = {}, forceBlock = false) {
      const isExpired = Number(merchant.is_expired || 0) === 1;
      const expireTime = this.normalizeText(merchant.expire_time) || "未设置";
      const daysLeft = Number(merchant.days_left || 0);
      const title = isExpired ? "商家服务已到期" : "商家服务即将到期";
      const content = isExpired
        ? `您的商家服务已于 ${expireTime} 到期，当前商家功能已暂停，请先续费后再继续使用。`
        : `您的商家服务将在 ${daysLeft} 天后到期（到期时间：${expireTime}），请及时续费，避免影响商家功能使用。`;
      uni.showModal({
        title,
        content,
        confirmText: "去续费",
        cancelText: forceBlock ? "返回" : "知道了",
        success: (res) => {
          if (res.confirm) {
            this.jump("/pages/merchant/renew");
            return;
          }
          if (forceBlock) {
            uni.switchTab({
              url: "/pages/app/my",
            });
          }
        },
      });
    },
    ensureMerchantFeatureAvailable() {
      if (Number(this.merchantInfo.is_expired || 0) === 1) {
        this.showMerchantExpireDialog(this.merchantInfo, true);
        return false;
      }
      return true;
    },
    hasMerchantPermission() {
      const keys = Array.prototype.slice.call(arguments);
      if (!keys.length) {
        return false;
      }
      for (let i = 0; i < keys.length; i++) {
        if (Number(this.merchantPermissionMap[keys[i]] || 0) === 1) {
          return true;
        }
      }
      return false;
    },
    resolveDefaultMerchantIdentity(list = []) {
      const identities = Array.isArray(list) ? list : [];
      if (!identities.length) {
        return null;
      }
      const ordinaryIdentity = identities.find(
        (item) =>
          Number(
            (item && item.merchant_user && item.merchant_user.is_super) || 0,
          ) !== 1,
      );
      return ordinaryIdentity || identities[0] || null;
    },
    resolveCurrentMerchantIdentity(list = [], merUserId = 0) {
      const identities = Array.isArray(list) ? list : [];
      const currentId = Number(merUserId || 0);
      if (currentId > 0) {
        const matchedIdentity = identities.find(
          (item) => Number((item && item.mer_user_id) || 0) === currentId,
        );
        if (matchedIdentity) {
          return matchedIdentity;
        }
      }
      return this.resolveDefaultMerchantIdentity(identities);
    },
    loadMerchantIdentityState(force = false) {
      if (this.reviewMode || !this.hasCachedAuth()) {
        this.merchantIdentityList = [];
        this.currentMerchantIdentityId = 0;
        this.merchantPermissionCodes = [];
        clearCurrentMerchantIdentity();
        return Promise.resolve([]);
      }
      if (this.merchantIdentityLoading && !force) {
        return Promise.resolve(this.merchantIdentityList);
      }
      this.merchantIdentityLoading = true;
      return Promise.all([
        api.merchantIdentityList({}).catch(() => ({ data: { list: [] } })),
        api
          .merchantIdentityCurrent({})
          .catch(() => ({ data: { identity: {}, permissions: {} } })),
      ])
        .then(([listRes, currentRes]) => {
          const list = Array.isArray(listRes.data && listRes.data.list)
            ? listRes.data.list
            : [];
          this.merchantIdentityList = list;
          setMerchantIdentityList(list);
          const currentIdentity =
            currentRes.data && currentRes.data.identity
              ? currentRes.data.identity
              : {};
          const permissions =
            currentRes.data && currentRes.data.permissions
              ? currentRes.data.permissions
              : {};
          const selectedIdentity = this.resolveCurrentMerchantIdentity(
            list,
            Number(
              currentIdentity.mer_user_id ||
                getCurrentMerchantIdentityId() ||
                0,
            ),
          );
          const currentId = Number(
            (selectedIdentity && selectedIdentity.mer_user_id) || 0,
          );
          this.currentMerchantIdentityId = currentId;
          setCurrentMerchantIdentityId(currentId);
          this.merchantPermissionCodes = Array.isArray(
            permissions.permission_codes,
          )
            ? permissions.permission_codes
            : [];
          return list;
        })
        .finally(() => {
          this.merchantIdentityLoading = false;
        });
    },
    onMerchantIdentityChange(event) {
      const nextIndex = Number((event.detail && event.detail.value) || 0);
      const nextItem = this.merchantIdentityOptions[nextIndex];
      if (
        !nextItem ||
        !nextItem.mer_user_id ||
        Number(nextItem.mer_user_id) ===
          Number(this.currentMerchantIdentityId || 0)
      ) {
        return;
      }
      const confirmContent = buildEnvConfirmText(this.currentEnvInfo, {
        prod: "当前为正式环境，确认切换到该商家身份并使用真实权限上下文吗？",
        test: "确认继续测试切换商家身份吗？",
      });
      uni.showModal({
        title: "切换商家身份",
        content: confirmContent,
        confirmText: "确认切换",
        success: ({ confirm }) => {
          if (!confirm) {
            return;
          }
          this.setRecentActionSummary(
            "切换商家身份",
            nextItem.label || "已切换",
          );
          api
            .merchantIdentitySwitch({
              mer_user_id: Number(nextItem.mer_user_id),
            })
            .then((res) => {
              const identity =
                res.data && res.data.identity ? res.data.identity : {};
              const permissions =
                res.data && res.data.permissions ? res.data.permissions : {};
              this.currentMerchantIdentityId = Number(
                identity.mer_user_id || nextItem.mer_user_id || 0,
              );
              setCurrentMerchantIdentityId(this.currentMerchantIdentityId);
              this.merchantPermissionCodes = Array.isArray(
                permissions.permission_codes,
              )
                ? permissions.permission_codes
                : [];
              this.getMerchantInfo(true);
            })
            .catch(() => {});
        },
      });
    },
    normalizeText(value) {
      if (value === undefined || value === null) {
        return "";
      }
      return String(value).trim();
    },
    findToolByCode(code) {
      return (
        (Array.isArray(this.toolsList) ? this.toolsList : []).find(
          (item) => item.code === code,
        ) || null
      );
    },
    getToolDisplayName(item) {
      const code = item && item.code ? item.code : "";
      const labels = {
        service: "客服中心",
        news: "平台资讯",
        accord_center: "协议中心",
        friend: "商家信息",
        release: "商品发布",
        read: "订单核销",
        analytics: "数据分析",
        renew: "续费中心",
        settings: "设置",
        merchant_audit: "平台商家审核",
        order_audit: "商家订单核销",
        cross_writeoff: "商家订单核销",
      };
      return (
        (item && item.displayName) || labels[code] || (item && item.name) || ""
      );
    },
    getSuperToolDescription(item) {
      const code = item && item.code ? item.code : "";
      if (code === "release") {
        return "以商家超管身份进入商品发布中心";
      }
      if (code === "merchant_audit") {
        return "查看并处理平台商家入驻申请";
      }
      if (code === "cross_writeoff") {
        return "查看并核销全平台商家订单";
      }
      if (code === "order_audit") {
        return "查看并核销平台商家订单";
      }
      return "按当前超管权限进入对应页面";
    },
    getSuperToolCaption(item) {
      const code = item && item.code ? item.code : "";
      if (code === "release") {
        return "发布平台商品";
      }
      if (code === "merchant_audit") {
        return "审核入驻申请";
      }
      if (code === "cross_writeoff" || code === "order_audit") {
        return "处理待核销单";
      }
      return "进入工作台";
    },
    getSuperToolCardClass(item) {
      const code = item && item.code ? item.code : "";
      return {
        "is-primary": code === "release",
        "is-secondary": code === "merchant_audit",
        "is-alert": code === "cross_writeoff" || code === "order_audit",
      };
    },
    getSuperToolBadge(item) {
      const code = item && item.code ? item.code : "";
      if (code === "release") {
        return "优先";
      }
      if (code === "merchant_audit") {
        return "审核";
      }
      if (
        (code === "cross_writeoff" || code === "order_audit") &&
        this.pendingWriteoffCount > 0
      ) {
        return this.pendingWriteoffCountText;
      }
      return "";
    },
    getToolCardClass(item) {
      const code = item && item.code ? item.code : "";
      return {
        "is-accent": this.isPrimaryAccentTool(item),
        "is-merchant": ["friend", "read", "analytics", "renew"].includes(code),
        "is-system": ["service", "news", "settings", "accord_center"].includes(
          code,
        ),
        "is-admin": [
          "merchant_audit",
          "order_audit",
          "cross_writeoff",
        ].includes(code),
      };
    },
    isPrimaryAccentTool(item) {
      return !!item && item.code === "release";
    },
    hasAdminPermission() {
      const keys = Array.prototype.slice.call(arguments);
      const permissions =
        this.userInfo && this.userInfo.admin_permissions
          ? this.userInfo.admin_permissions
          : {};
      for (let i = 0; i < keys.length; i++) {
        if (Number(permissions[keys[i]] || 0) === 1) {
          return true;
        }
      }
      return false;
    },
    getUserSeed() {
      const source =
        this.normalizeText(this.userInfo.member_id) ||
        this.normalizeText(this.userInfo.user_id) ||
        this.normalizeText(this.userInfo.mobile) ||
        this.normalizeText(this.userInfo.phone) ||
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
          : [this.default_avatar_url];
      return list[seed % list.length] || this.default_avatar_url;
    },
    buildAvatarStyle(url, isGuest = false) {
      const safeUrl = this.resolveSafeAvatarUrl(
        url,
        "/static/images/avatar/1.jpg",
      );
      const style = {
        backgroundImage: `url(${safeUrl})`,
        backgroundSize: "cover",
        backgroundPosition: "center center",
        backgroundRepeat: "no-repeat",
      };
      if (isGuest) {
        style.display = "inherit";
        style.margin = "auto";
      }
      return style;
    },
    resolveSafeAvatarUrl(url, fallback = "/static/images/avatar/1.jpg") {
      const safeFallback =
        this.normalizeText(fallback) || "/static/images/avatar/1.jpg";
      const normalized = this.normalizeText(url);
      return resolveSafeImageUrl(normalized, safeFallback);
    },
    //足迹
    footmarkTap() {
      uni.navigateTo({
        url: "/pages/my/footmark",
      });
    },
    //需要登录后跳转的链接
    setupTap(url) {
      const targetUrl = url || "/pages/my/set-up";
      this.setRecentActionSummary("打开页面入口", targetUrl);
      if (this.reviewMode) {
        this.jump("/pages/help/service");
        return false;
      }
      if (this.login) {
        uni.showModal({
          title: "温馨提示",
          showCancel: false,
          content: "请登录后再操作",
          success: function (e) {
            if (e.confirm) {
              setLoginRedirect(targetUrl);
              uni.navigateTo({
                url: buildLoginRedirectUrl(targetUrl),
              });
            }
          },
        });
        return false;
      }
      uni.navigateTo({
        url: targetUrl,
      });
    },
    showTool(item) {
      if (this.reviewMode) {
        return (
          item.code === "service" ||
          item.code === "news" ||
          item.code === "accord_center"
        );
      }
      if (item.code === "friend") {
        return (
          this.userInfo.wx_approved != 1 &&
          (!this.hasMerchantRecord ||
            this.hasMerchantPermission("edit_profile"))
        );
      }
      if (item.code === "renew") {
        return (
          this.hasMerchantRecord &&
          this.userInfo.wx_approved != 1 &&
          this.hasMerchantPermission("admin_manage_merchant")
        );
      }
      if (item.code === "merchant_audit") {
        return (
          this.hasMerchantRecord &&
          this.hasMerchantSuperCapability &&
          this.userInfo.wx_approved != 1
        );
      }
      if (item.code === "order_audit") {
        return (
          this.hasMerchantRecord &&
          this.hasMerchantSuperCapability &&
          this.userInfo.wx_approved != 1
        );
      }
      if (
        item.code === "release" ||
        item.code === "read" ||
        item.code === "analytics"
      ) {
        return (
          this.hasMerchantRecord &&
          this.userInfo.wx_approved != 1 &&
          this.merchantAuthApproved &&
          ((item.code === "release" &&
            this.hasMerchantPermission("publish_product")) ||
            (item.code === "read" &&
              this.hasMerchantPermission(
                "verify_order",
                "verify_cross_merchant_order",
              )) ||
            (item.code === "analytics" &&
              this.hasMerchantPermission("view_stats")))
        );
      }
      return true;
    },
    getToolBadge(item) {
      if (
        item.code === "read" &&
        !this.reviewMode &&
        this.pendingWriteoffCount > 0
      ) {
        return this.pendingWriteoffCountText;
      }
      if (
        item.code === "renew" &&
        Number(this.merchantInfo.is_expired || 0) === 1
      ) {
        return "到期";
      }
      if (
        item.code === "renew" &&
        Number(this.merchantInfo.should_remind || 0) === 1
      ) {
        return "提醒";
      }
      return "";
    },
    handleMerchantServiceAction(item) {
      if (!item) {
        return;
      }
      if (!item.url) {
        return;
      }
      if (
        item.code === "merchant_release" ||
        item.code === "merchant_analytics" ||
        item.code === "merchant_writeoff"
      ) {
        const requireSuper = false;
        if (!this.ensureMerchantWorkbenchAvailable(requireSuper)) {
          return;
        }
      }
      this.jump(item.url);
    },
    openPendingWriteoff() {
      if (
        !this.hasMerchantPermission(
          "verify_order",
          "verify_cross_merchant_order",
        )
      ) {
        uni.showToast({
          icon: "none",
          title: "暂无核销权限",
        });
        return;
      }
      if (!this.ensureMerchantWorkbenchAvailable()) {
        return;
      }
      this.setRecentActionSummary(
        "打开待核销订单",
        `待处理 ${this.pendingWriteoffCountText} 笔`,
      );
      this.jump("/pages/order/mer_list");
    },
    openAdminNext() {
      const adminUrl = resolveAdminNextUrl(this.userInfo.admin_next_host);
      // #ifdef H5
      window.location.href = adminUrl;
      return;
      // #endif
      uni.setClipboardData({
        data: adminUrl,
        success: () => {
          uni.showModal({
            title: "后台地址已复制",
            content: "当前账号为总后台管理员，请在浏览器打开总后台。",
            showCancel: false,
          });
        },
      });
    },
    ensureMerchantWorkbenchAvailable(requireSuper = false) {
      if (!this.hasMerchantRecord) {
        uni.showModal({
          title: "提示",
          content: "请先完成商家入驻后再继续使用商家功能。",
          confirmText: "去入驻",
          success: (res) => {
            if (res.confirm) {
              this.jump("/pages/merchant/apply");
            }
          },
        });
        return false;
      }
      if (requireSuper && !this.goodsReleaseEnabled) {
        uni.showToast({
          icon: "none",
          title: "商品发布功能暂未开放",
        });
        return false;
      }
      if (requireSuper && !this.hasMerchantSuperCapability) {
        uni.showToast({
          icon: "none",
          title: "仅商家超管可用",
        });
        return false;
      }
      if (Number(this.merchantInfo.auth_state || 0) === 0) {
        uni.showToast({
          icon: "none",
          title: "商家资料审核中",
        });
        return false;
      }
      if (Number(this.merchantInfo.auth_state || 0) === 2) {
        uni.showModal({
          title: "审核未通过",
          content:
            this.normalizeText(this.merchantInfo.auth_msg) ||
            "请完善商家资料后重新提交审核。",
          confirmText: "去完善",
          success: (res) => {
            if (res.confirm) {
              this.jump("/pages/merchant/apply");
            }
          },
        });
        return false;
      }
      if (!this.ensureMerchantFeatureAvailable()) {
        return false;
      }
      return true;
    },
    ensureMerchantSuperAccess(featureName = "当前功能") {
      if (!this.hasMerchantRecord) {
        uni.showModal({
          title: "提示",
          content: "请先完成商家入驻后再继续使用该功能。",
          confirmText: "去入驻",
          success: (res) => {
            if (res.confirm) {
              this.jump("/pages/merchant/apply");
            }
          },
        });
        return false;
      }
      if (!this.hasMerchantSuperCapability) {
        uni.showToast({
          icon: "none",
          title: `${featureName}仅商家超管可用`,
        });
        return false;
      }
      return true;
    },
    gridTap(item) {
      this.setRecentActionSummary(
        "打开工具入口",
        item.displayName || item.name || item.code || "工具",
      );
      if (this.reviewMode) {
        if (item.code === "service") {
          this.jump("/pages/help/service");
          return;
        }
        if (item.code === "news") {
          this.jump("/pages/news/list?category_id=2");
          return;
        }
        if (item.code === "accord_center") {
          this.jump("/pages/system/accord-center");
          return;
        }
        this.jump("/pages/help/service");
        return;
      }
      switch (item.code) {
        case "accord_center":
          this.jump("/pages/system/accord-center");
          break;
        case "settings": //设置
          this.setupTap("/pages/my/set-up");
          break;
        case "friend": //入驻申请
          this.setupTap("/pages/merchant/apply");
          break;
        case "release":
          if (!this.goodsReleaseEnabled) {
            uni.showToast({
              icon: "none",
              title: "商品发布功能暂未开放",
            });
            return;
          }
          if (!this.ensureMerchantWorkbenchAvailable()) {
            return;
          }
          this.jump("/pages/app/release");
          break;
        case "service": //客服中心
          this.jump("/pages/help/service");
          break;
        case "news":
          this.jump("/pages/news/list?category_id=2");
          break;
        case "read": //订单核销
          if (!this.ensureMerchantWorkbenchAvailable()) {
            return;
          }
          this.jump("/pages/order/mer_list");
          break;
        case "analytics":
          if (!this.ensureMerchantWorkbenchAvailable()) {
            return;
          }
          this.jump("/pages/merchant/analytics");
          break;
        case "merchant_audit":
          if (!this.ensureMerchantSuperAccess("平台商家审核")) {
            return;
          }
          this.jump("/pages/admin/merchant-audit");
          break;
        case "cross_writeoff":
          if (!this.ensureMerchantSuperAccess("商家订单核销")) {
            return;
          }
          this.jump("/pages/admin/order-audit?review_scene=pending_writeoff");
          break;
        case "order_audit":
          if (!this.ensureMerchantSuperAccess("商家订单核销")) {
            return;
          }
          this.jump("/pages/admin/order-audit");
          break;
        case "renew":
          this.jump("/pages/merchant/renew");
          break;
      }
    },
    //页面跳转
    jump(url) {
      uni.navigateTo({
        url: url,
      });
    },
  },
};
</script>

<style lang="scss" scoped>
/* #ifdef APP-PLUS */
@import "../../static/colorui/main.css";
@import "../../static/colorui/icon.css";
@import "../../static/zaiui/style/app.scss";
/* #endif */
.zaiui-my-box {
  width: 100%;
  display: none;
  .zaiui-head-box {
    position: relative;
    overflow: hidden;
    padding-top: 0;
    padding-bottom: 72rpx;
    background: var(--my-head-bg);
    .zaiui-head-glow {
      position: absolute;
      border-radius: 9999rpx;
      pointer-events: none;
      filter: blur(8rpx);
      opacity: 0.34;
      &--one {
        width: 280rpx;
        height: 280rpx;
        right: -70rpx;
        top: 64rpx;
        background: radial-gradient(
          circle,
          rgba(255, 255, 255, 0.88) 0%,
          rgba(255, 255, 255, 0.08) 70%,
          rgba(255, 255, 255, 0) 100%
        );
      }
      &--two {
        width: 220rpx;
        height: 220rpx;
        left: -60rpx;
        top: 220rpx;
        background: var(--my-head-glow-two);
      }
    }
    .zaiui-user-info-box {
      position: relative;
      z-index: 2;
      /* #ifdef MP */
      padding-top: calc(var(--status-bar-height) + 50rpx);
      /* #endif */
      .review-user-view {
        padding: 18.18rpx 36.36rpx 9.09rpx;
        color: #ffffff;
        .review-mode-badge {
          display: inline-flex;
          padding: 8rpx 18rpx;
          border-radius: 999rpx;
          background: rgba(255, 255, 255, 0.18);
          font-size: 24rpx;
          margin-bottom: 18rpx;
        }
        .review-title {
          font-size: 43.63rpx;
          font-weight: 700;
          line-height: 1.3;
        }
        .review-desc {
          margin-top: 14rpx;
          font-size: 25.45rpx;
          line-height: 1.7;
          color: rgba(255, 255, 255, 0.92);
        }
        .review-service-btn {
          margin-top: 25.45rpx;
          background: var(--my-head-glass-bg);
          color: var(--my-head-text);
          border: 1rpx solid var(--my-head-glass-border);
        }
      }
      .user-shell-card {
        margin: 14rpx 27.27rpx 0;
        padding: 22rpx 24rpx 20rpx;
        border-radius: 30rpx;
        background: var(--my-head-glass-bg);
        border: 1rpx solid var(--my-head-glass-border);
        box-shadow: var(--my-head-glass-shadow);
        backdrop-filter: blur(10rpx);
      }
      .login-user-view {
        position: relative;
        text-align: center;
        padding-top: 6rpx;
        padding-bottom: 2rpx;
        .my-head-login-text {
          color: var(--my-head-text);
        }
        .login-user-avatar-view {
          position: relative;
          margin-bottom: 14rpx;
        }
        .cu-avatar {
          width: 110rpx !important;
          height: 110rpx !important;
          border: 4rpx solid rgba(255, 255, 255, 0.3);
          box-shadow: var(--my-head-avatar-shadow);
        }
        .cu-btn {
          margin-top: 4rpx;
          min-width: 180rpx;
          height: 68rpx;
          line-height: 68rpx;
          border-radius: 999rpx;
          background: var(--my-head-glass-bg);
          color: var(--my-head-text);
          border: 1rpx solid var(--my-head-glass-border);
          font-weight: 700;
        }
      }
      .cu-list.menu-avatar > .cu-item {
        background-color: inherit;
        padding: 8rpx 0;
        min-height: 0;
        .cu-avatar {
          width: 110rpx !important;
          height: 110rpx !important;
          border: 4rpx solid rgba(255, 255, 255, 0.24);
          box-shadow: var(--my-head-avatar-shadow);
        }
        .content {
          width: calc(100% - 94.54rpx - 30rpx);
          padding-left: 12rpx;
          .text-white-bg {
            color: var(--my-head-subtle);
            margin-top: 8rpx;
            .text-border-x {
              margin-right: 15rpx;
              position: relative;
              &:after {
                position: absolute;
                background: #dddddd;
                top: 5.45rpx;
                width: 1.81rpx;
                right: -12.72rpx;
                height: 16.36rpx;
                content: " ";
              }
            }
          }
        }
        &:after {
          width: 0;
          height: 0;
          border-bottom: 0;
        }
      }
      .cu-list.menu-avatar > .cu-item .content > view:first-child {
        font-size: 36rpx;
        font-weight: 700;
        text-shadow: var(--my-head-title-shadow);
      }
    }
    .zaiui-user-info-num-box {
      position: relative;
      z-index: 2;
      margin: 16rpx 27.27rpx 0;
      padding: 6rpx 10rpx;
      border-radius: 28rpx;
      background: var(--my-head-glass-bg);
      border: 1rpx solid var(--my-head-glass-border);
      box-shadow: var(--my-head-glass-shadow);
      .cu-list.grid.no-border {
        padding: 0;
      }
      .cu-list.grid.no-border > .cu-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 96rpx;
        padding-top: 18rpx;
        padding-bottom: 16rpx;
        border-radius: 22rpx;
      }
      .cu-list.grid {
        background-color: inherit;
      }
      .cu-list.grid > .cu-item + .cu-item {
        position: relative;
      }
      .cu-list.grid > .cu-item + .cu-item:before {
        content: "";
        position: absolute;
        left: 0;
        top: 24rpx;
        bottom: 24rpx;
        width: 1rpx;
        background: var(--my-head-stat-divider);
      }
      .text-xl {
        font-size: 38rpx;
        font-weight: 700;
        line-height: 1;
        color: var(--my-head-text);
      }
      .cu-list.grid > .cu-item text {
        color: var(--my-head-subtle);
        font-size: 22rpx;
        line-height: 1.4;
        margin-top: 8rpx;
      }
    }
    .zaiui-user-info-tip-box {
      position: relative;
      z-index: 2;
      margin: 18.18rpx 27.27rpx 0;
      border-radius: 24rpx;
      padding: 22rpx 27.27rpx;
      background: var(--my-head-tip-bg);
      border: 1rpx solid var(--my-head-tip-border);
      box-shadow: var(--my-head-tip-shadow);
      color: var(--my-head-tip-text);
      .text-cut {
        padding-right: 45.45rpx;
        font-weight: 600;
      }
      .icon {
        position: absolute;
        right: 27.27rpx;
        top: 23.63rpx;
      }
    }
  }
  .zaiui-view-content {
    padding: 0 27.27rpx 54.54rpx;
    margin-top: -36rpx;
    .env-card {
      margin-bottom: 18rpx;
      padding: 24rpx 26rpx;
      border-radius: 24rpx;
      background: rgba(255, 255, 255, 0.96);
      box-shadow: 0 14rpx 30rpx rgba(16, 33, 56, 0.08);
      &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16rpx;
      }
      &__title {
        font-size: 28rpx;
        font-weight: 700;
        color: #13263b;
      }
      &__badge {
        min-width: 124rpx;
        padding: 8rpx 18rpx;
        border-radius: 999rpx;
        text-align: center;
        font-size: 22rpx;
        font-weight: 600;
        color: #ffffff;
        background: linear-gradient(135deg, #1596a5 0%, #2c7be5 100%);
        &.is-prod {
          background: linear-gradient(135deg, #ef4444 0%, #f97316 100%);
        }
      }
      &__desc {
        margin-top: 12rpx;
        font-size: 23rpx;
        line-height: 1.7;
        color: #5b6b7b;
      }
      &__meta {
        display: flex;
        flex-wrap: wrap;
        gap: 12rpx;
        margin-top: 14rpx;
      }
      &__tag {
        display: inline-flex;
        align-items: center;
        min-height: 42rpx;
        padding: 0 18rpx;
        border-radius: 999rpx;
        background: rgba(44, 123, 229, 0.1);
        color: #2c7be5;
        font-size: 21rpx;
        font-weight: 600;
      }
      &__note {
        margin-top: 14rpx;
        padding: 18rpx 20rpx;
        border-radius: 18rpx;
        background: linear-gradient(
          180deg,
          rgba(248, 250, 252, 0.98) 0%,
          rgba(241, 245, 249, 0.98) 100%
        );
        color: #516274;
        font-size: 21rpx;
        line-height: 1.65;
      }
      &__note--strong {
        background: rgba(44, 123, 229, 0.08);
        color: #2d4f73;
      }
      &__risk-list {
        margin-top: 12rpx;
        padding: 16rpx 18rpx;
        border-radius: 18rpx;
        background: rgba(255, 244, 232, 0.98);
      }
      &__risk-item {
        color: #8c5145;
        font-size: 21rpx;
        line-height: 1.65;
      }
      &__risk-item + &__risk-item {
        margin-top: 8rpx;
      }
      &__url {
        margin-top: 10rpx;
        font-size: 20rpx;
        line-height: 1.6;
        color: #94a3b8;
        word-break: break-all;
      }
      &__board {
        margin-top: 14rpx;
        padding: 16rpx 18rpx;
        border-radius: 20rpx;
        background: rgba(21, 75, 114, 0.04);
      }
      &__board-title {
        font-size: 24rpx;
        font-weight: 700;
        color: #10283d;
      }
      &__board-list {
        display: flex;
        flex-direction: column;
        gap: 12rpx;
        margin-top: 14rpx;
      }
      &__board-item {
        padding: 16rpx 18rpx;
        border-radius: 18rpx;
        background: rgba(255, 255, 255, 0.92);
        &.is-current {
          border: 1rpx solid rgba(0, 129, 255, 0.22);
        }
      }
      &__board-item-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12rpx;
      }
      &__board-item-name {
        font-size: 22rpx;
        color: #23405d;
        font-weight: 600;
      }
      &__board-item-status {
        min-width: 96rpx;
        padding: 6rpx 16rpx;
        border-radius: 999rpx;
        text-align: center;
        font-size: 20rpx;
        color: #ffffff;
        background: linear-gradient(45deg, #0081ff, #1cbbb4);
        &.is-hold {
          background: linear-gradient(45deg, #f59e0b, #f97316);
        }
        &.is-ready {
          background: linear-gradient(45deg, #ef4444, #f97316);
        }
      }
      &__board-item-desc {
        margin-top: 8rpx;
        font-size: 21rpx;
        line-height: 1.7;
        color: #6b7b8c;
      }
    }
    .runtime-rollout-card {
      margin-bottom: 18rpx;
      padding: 24rpx 26rpx;
      border-radius: 24rpx;
      background: rgba(255, 255, 255, 0.98);
      box-shadow: 0 14rpx 30rpx rgba(16, 33, 56, 0.08);
      &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12rpx;
      }
      &__title {
        font-size: 28rpx;
        font-weight: 700;
        color: #13263b;
      }
      &__badge {
        min-width: 96rpx;
        padding: 6rpx 16rpx;
        border-radius: 999rpx;
        text-align: center;
        font-size: 20rpx;
        color: #ffffff;
        background: linear-gradient(45deg, #0081ff, #1cbbb4);
        &.is-prod {
          background: linear-gradient(45deg, #ef4444, #f97316);
        }
      }
      &__desc {
        margin-top: 8rpx;
        font-size: 21rpx;
        line-height: 1.7;
        color: #6b7b8c;
      }
      &__list {
        display: flex;
        flex-direction: column;
        gap: 12rpx;
        margin-top: 14rpx;
      }
      &__item {
        padding: 16rpx 18rpx;
        border-radius: 18rpx;
        background: rgba(244, 248, 255, 0.86);
      }
      &__item-label {
        font-size: 22rpx;
        color: #23405d;
        font-weight: 600;
      }
      &__item-value {
        display: block;
        margin-top: 8rpx;
        font-size: 21rpx;
        line-height: 1.7;
        color: #1c425f;
        font-weight: 600;
      }
      &__risk {
        margin-top: 16rpx;
        padding: 18rpx 20rpx;
        border-radius: 20rpx;
        background: rgba(255, 247, 237, 0.96);
        color: #9a3412;
        font-size: 21rpx;
        line-height: 1.7;
      }
    }
    .runtime-review-card,
    .runtime-action-card,
    .runtime-hub-card {
      margin-bottom: 18rpx;
      padding: 24rpx 26rpx;
      border-radius: 24rpx;
      background: rgba(255, 255, 255, 0.98);
      box-shadow: 0 14rpx 30rpx rgba(16, 33, 56, 0.08);
    }
    .runtime-hub-card {
      &__head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16rpx;
      }
      &__title {
        font-size: 28rpx;
        font-weight: 700;
        color: #13263b;
      }
      &__desc {
        margin-top: 12rpx;
        font-size: 23rpx;
        line-height: 1.7;
        color: #5b6b7b;
      }
      &__badge {
        min-width: 112rpx;
        padding: 8rpx 18rpx;
        border-radius: 999rpx;
        text-align: center;
        font-size: 22rpx;
        font-weight: 700;
        &.is-success {
          background: rgba(39, 174, 96, 0.12);
          color: #1d8f5f;
        }
        &.is-pending,
        &.is-risk {
          background: rgba(236, 138, 87, 0.14);
          color: #d86b32;
        }
        &.is-neutral {
          background: rgba(107, 124, 143, 0.12);
          color: #6b7c8f;
        }
      }
      &__tags {
        display: flex;
        flex-wrap: wrap;
        gap: 12rpx;
        margin-top: 14rpx;
      }
      &__tag {
        display: inline-flex;
        align-items: center;
        min-height: 42rpx;
        padding: 0 18rpx;
        border-radius: 999rpx;
        background: rgba(44, 123, 229, 0.1);
        color: #2c7be5;
        font-size: 21rpx;
        font-weight: 600;
      }
      &__risk {
        margin-top: 14rpx;
        padding: 18rpx 20rpx;
        border-radius: 18rpx;
        background: rgba(255, 244, 232, 0.98);
        color: #c26b32;
        font-size: 21rpx;
        line-height: 1.65;
      }
      &__actions {
        display: flex;
        flex-wrap: wrap;
        gap: 12rpx;
        margin-top: 16rpx;
      }
      &__action {
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
        &.is-disabled {
          opacity: 0.46;
        }
      }
    }
    .runtime-review-card {
      &__title {
        font-size: 28rpx;
        font-weight: 700;
        color: #13263b;
      }
      &__desc {
        margin-top: 12rpx;
        font-size: 23rpx;
        line-height: 1.7;
        color: #5b6b7b;
      }
      &__tags {
        display: flex;
        flex-wrap: wrap;
        gap: 12rpx;
        margin-top: 14rpx;
      }
      &__tag {
        display: inline-flex;
        align-items: center;
        min-height: 42rpx;
        padding: 0 18rpx;
        border-radius: 999rpx;
        background: rgba(44, 123, 229, 0.1);
        color: #2c7be5;
        font-size: 21rpx;
        font-weight: 600;
      }
      &__risk {
        margin-top: 14rpx;
        padding: 18rpx 20rpx;
        border-radius: 18rpx;
        background: rgba(255, 244, 232, 0.98);
        color: #c26b32;
        font-size: 21rpx;
        line-height: 1.65;
      }
    }
    .runtime-action-card {
      &__title {
        font-size: 28rpx;
        font-weight: 700;
        color: #13263b;
      }
      &__desc {
        margin-top: 12rpx;
        font-size: 23rpx;
        line-height: 1.7;
        color: #5b6b7b;
      }
    }
    .recent-release-panel {
      margin-bottom: 18rpx;
      padding: 24rpx 26rpx;
      border-radius: 24rpx;
      background: linear-gradient(
        180deg,
        rgba(255, 250, 242, 0.99) 0%,
        rgba(255, 244, 232, 0.98) 100%
      );
      box-shadow: 0 14rpx 30rpx rgba(224, 142, 76, 0.12);
      &__badge {
        display: inline-flex;
        align-items: center;
        min-height: 42rpx;
        padding: 0 18rpx;
        border-radius: 999rpx;
        background: rgba(227, 123, 67, 0.12);
        color: #d86b32;
        font-size: 22rpx;
        font-weight: 700;
      }
      &__title {
        margin-top: 14rpx;
        font-size: 30rpx;
        font-weight: 700;
        color: #243142;
      }
      &__desc {
        margin-top: 10rpx;
        font-size: 22rpx;
        line-height: 1.7;
        color: #5b6b7b;
      }
      &__action {
        margin-top: 16rpx;
        display: inline-flex;
        align-items: center;
        gap: 8rpx;
        color: #d86b32;
        font-size: 22rpx;
        font-weight: 700;
      }
    }
    .workbench-entry-panel {
      margin-bottom: 18rpx;
      padding: 24rpx 26rpx;
      border-radius: 24rpx;
      background: linear-gradient(
        180deg,
        rgba(244, 248, 255, 0.99) 0%,
        rgba(233, 241, 252, 0.98) 100%
      );
      box-shadow: 0 14rpx 30rpx rgba(44, 123, 229, 0.12);
      &__badge {
        display: inline-flex;
        align-items: center;
        min-height: 42rpx;
        padding: 0 18rpx;
        border-radius: 999rpx;
        background: rgba(44, 123, 229, 0.12);
        color: #2c7be5;
        font-size: 22rpx;
        font-weight: 700;
      }
      &__title {
        margin-top: 14rpx;
        font-size: 30rpx;
        font-weight: 700;
        color: #243142;
      }
      &__desc {
        margin-top: 10rpx;
        font-size: 22rpx;
        line-height: 1.7;
        color: #5b6b7b;
      }
      &__action {
        margin-top: 16rpx;
        display: inline-flex;
        align-items: center;
        gap: 8rpx;
        color: #2c7be5;
        font-size: 22rpx;
        font-weight: 700;
      }
    }
    .zaiui-user-info-order-box {
      border-radius: 30rpx;
      padding: 12rpx 12rpx 16rpx;
      background: linear-gradient(
        180deg,
        rgba(255, 255, 255, 0.99) 0%,
        rgba(249, 250, 252, 0.98) 100%
      );
      box-shadow: 0 18rpx 38rpx rgba(31, 45, 61, 0.08);
      .order-box-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16rpx 18rpx 8rpx;
        &__title {
          font-size: 32rpx;
          font-weight: 700;
          color: #243142;
        }
        &__desc {
          font-size: 22rpx;
          color: #94a0ad;
        }
      }
      .order-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12rpx;
        padding: 0 8rpx 8rpx;
      }
      .order-grid-card {
        padding: 20rpx 18rpx;
        border-radius: 24rpx;
        min-height: 140rpx;
        background: linear-gradient(
          180deg,
          rgba(255, 253, 248, 0.99) 0%,
          rgba(252, 248, 240, 0.98) 100%
        );
        border: 1rpx solid rgba(238, 223, 193, 0.75);
        box-shadow: 0 10rpx 20rpx rgba(214, 170, 90, 0.06);
        &__top {
          display: flex;
          align-items: center;
          gap: 16rpx;
        }
        &__meta {
          flex: 1;
          min-width: 0;
        }
        &.is-all .order-grid-icon {
          background: linear-gradient(
            135deg,
            rgba(255, 166, 177, 0.18) 0%,
            rgba(255, 222, 195, 0.22) 100%
          );
          .text-xxl {
            color: #e96d7f !important;
          }
        }
        &.is-pay .order-grid-icon {
          background: linear-gradient(
            135deg,
            rgba(252, 213, 137, 0.24) 0%,
            rgba(255, 239, 208, 0.22) 100%
          );
          .text-xxl {
            color: #d88c26 !important;
          }
        }
        &.is-receipt .order-grid-icon {
          background: linear-gradient(
            135deg,
            rgba(255, 220, 167, 0.2) 0%,
            rgba(255, 242, 214, 0.22) 100%
          );
          .text-xxl {
            color: #dc9142 !important;
          }
        }
        &.is-evaluate .order-grid-icon {
          background: linear-gradient(
            135deg,
            rgba(226, 204, 255, 0.18) 0%,
            rgba(255, 238, 214, 0.2) 100%
          );
          .text-xxl {
            color: #8f6ad6 !important;
          }
        }
      }
      .order-grid-icon {
        width: 70rpx;
        height: 70rpx;
        border-radius: 24rpx;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        .text-xxl {
          font-size: 32rpx !important;
          font-weight: 700;
          line-height: 1;
        }
      }
      .order-grid-label {
        font-size: 24rpx;
        font-weight: 700;
        color: #7a5330;
      }
      .order-grid-hint {
        margin-top: 8rpx;
        font-size: 20rpx;
        color: #a1876f;
      }
    }
    .merchant-service-card {
      margin-top: 20rpx;
      padding: 29.09rpx;
      border-radius: 30rpx;
      background: linear-gradient(
        135deg,
        rgba(32, 79, 118, 0.96) 0%,
        rgba(236, 138, 87, 0.9) 100%
      );
      color: #ffffff;
      box-shadow: 0 18rpx 42rpx rgba(21, 33, 52, 0.08);
      .merchant-service-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 18rpx;
      }
      .merchant-service-badge {
        display: inline-flex;
        padding: 8rpx 18rpx;
        border-radius: 999rpx;
        background: rgba(255, 255, 255, 0.18);
        font-size: 24rpx;
        margin-bottom: 14rpx;
      }
      .merchant-service-title {
        font-size: 34.54rpx;
        font-weight: 700;
        line-height: 1.4;
      }
      .merchant-service-status {
        padding: 10rpx 18rpx;
        border-radius: 999rpx;
        font-size: 23.63rpx;
        background: rgba(255, 255, 255, 0.18);
        &.is-safe {
          background: rgba(29, 143, 95, 0.18);
        }
        &.is-warning {
          background: rgba(212, 134, 28, 0.18);
        }
        &.is-danger {
          background: rgba(229, 83, 83, 0.22);
        }
        &.is-idle {
          background: rgba(255, 255, 255, 0.18);
        }
      }
      .merchant-service-desc {
        margin-top: 18rpx;
        font-size: 23.63rpx;
        line-height: 1.7;
        color: rgba(255, 255, 255, 0.92);
      }
      .merchant-service-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 16rpx;
        margin-top: 22rpx;
      }
      .merchant-action-pill {
        padding: 14rpx 24rpx;
        border-radius: 999rpx;
        background: rgba(255, 255, 255, 0.16);
        font-size: 23.63rpx;
      }
    }
    .pending-reminder-card {
      margin-top: 20rpx;
      padding: 28rpx 28rpx 26rpx;
      border-radius: 30rpx;
      background: linear-gradient(
        135deg,
        rgba(21, 75, 114, 0.96) 0%,
        rgba(236, 138, 87, 0.94) 100%
      );
      color: #ffffff;
      box-shadow: 0 18rpx 42rpx rgba(21, 33, 52, 0.08);
      .pending-badge {
        display: inline-flex;
        padding: 8rpx 18rpx;
        border-radius: 999rpx;
        background: rgba(255, 255, 255, 0.18);
        font-size: 22rpx;
        font-weight: 700;
        margin-bottom: 18rpx;
      }
      .pending-title {
        font-size: 34rpx;
        font-weight: 700;
        line-height: 1.4;
        letter-spacing: 0.5rpx;
      }
      .pending-desc {
        margin-top: 14rpx;
        font-size: 22rpx;
        line-height: 1.75;
        color: rgba(255, 255, 255, 0.92);
      }
      .pending-action {
        margin-top: 20rpx;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 6rpx;
        font-size: 22rpx;
        font-weight: 700;
      }
    }
    .cu-list.grid > .cu-item text {
      color: inherit;
    }
    .zaiui-user-info-money-box {
      border-radius: 18.18rpx;
      .money-col {
        padding: 0 9.09rpx 9.09rpx;
        .money-item {
          position: relative;
          padding: 9.09rpx;
          .money-item-view {
            border: 1.81rpx solid #f3f2f3;
            border-radius: 18.18rpx;
            position: relative;
            padding: 9.09rpx;
            .cu-avatar {
              position: absolute;
              left: 9.09rpx;
            }
            .money-content {
              position: relative;
              margin-left: 109.09rpx;
              margin-bottom: 27.27rpx;
              top: 12.72rpx;
            }
          }
        }
      }
    }
    .zaiui-user-info-tools-box {
      margin-top: 20rpx;
      border-radius: 30rpx;
      padding: 12rpx 12rpx 18rpx;
      background: var(--my-tools-panel-bg);
      box-shadow: var(--my-tools-panel-shadow);
      .tools-view {
        position: relative;
        padding: 16rpx 18rpx 12rpx;
        .tools-title {
          padding-right: 81.81rpx;
          font-size: 32rpx !important;
          line-height: 1.35;
        }
        .tools-right {
          position: absolute;
          right: 18rpx;
          bottom: 22rpx;
          color: var(--my-tools-subtle-text) !important;
        }
      }
      .merchant-tools-summary {
        margin: 0 12rpx 16rpx;
        padding: 25.45rpx;
        border-radius: 24rpx;
        background: var(--my-summary-bg);
        border: 1px solid var(--my-summary-border);
        &__head {
          display: flex;
          align-items: flex-start;
          justify-content: space-between;
          gap: 18rpx;
        }
        &__badge {
          display: inline-flex;
          padding: 8rpx 18rpx;
          border-radius: 999rpx;
          background: var(--my-summary-badge-bg);
          color: var(--my-summary-badge-color);
          font-size: 22rpx;
          margin-bottom: 12rpx;
        }
        &__title {
          font-size: 32rpx;
          font-weight: 700;
          line-height: 1.4;
          color: var(--my-summary-title);
        }
        &__status {
          padding: 10rpx 18rpx;
          border-radius: 999rpx;
          font-size: 22rpx;
          background: rgba(148, 163, 184, 0.16);
          color: #64748b;
          &.is-safe {
            background: rgba(29, 143, 95, 0.14);
            color: #1d8f5f;
          }
          &.is-warning {
            background: rgba(255, 181, 71, 0.16);
            color: #d07b00;
          }
          &.is-danger {
            background: rgba(229, 83, 83, 0.16);
            color: #c43939;
          }
        }
        &__desc {
          margin-top: 16rpx;
          font-size: 23.63rpx;
          line-height: 1.7;
          color: var(--my-summary-desc);
        }
      }
      .merchant-identity-picker {
        margin-top: 20rpx;
        border-radius: 20rpx;
        background: var(--my-identity-bg);
        border: 1rpx solid var(--my-identity-border);
        &__inner {
          display: flex;
          justify-content: space-between;
          align-items: center;
          padding: 18rpx 22rpx;
          gap: 16rpx;
        }
        &__label {
          font-size: 24rpx;
          color: var(--my-identity-label);
        }
        &__value {
          flex: 1;
          text-align: right;
          font-size: 24rpx;
          color: var(--my-identity-value);
          font-weight: 600;
        }
      }
      .tools-identity-picker {
        margin: 0 12rpx 18rpx;
      }
      .zaiui-tools-list-box {
        .recommend-tools-grid {
          display: flex;
          flex-wrap: wrap;
          margin: 0 -7rpx;
          padding: 6rpx 3rpx 0;
          .tool-grid-cell {
            width: 33.333333%;
            padding: 0 7rpx 14rpx;
            box-sizing: border-box;
            display: flex;
          }
          &.is-two-column {
            .tool-grid-cell {
              width: 50%;
            }
          }
          &.is-three-column {
            .tool-grid-cell {
              width: 33.333333%;
            }
          }
        }
        .cu-item.tool-grid-card {
          position: relative;
          overflow: hidden;
          margin: 0;
          width: 100%;
          padding: 22rpx 12rpx 18rpx;
          border-radius: 24rpx;
          background: var(--my-tool-card-bg) !important;
          border: 1rpx solid var(--my-tool-card-border) !important;
          box-shadow: var(--my-tool-card-shadow) !important;
          min-height: 168rpx;
          min-width: 0;
          display: flex;
          flex-direction: column;
          align-items: center;
          justify-content: center;
          text-align: center;
          &::before,
          &::after {
            display: none !important;
            content: none !important;
          }
          .tool-card-icon {
            width: 60rpx;
            height: 60rpx;
            border-radius: 20rpx;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            position: relative;
            z-index: 2;
            background: var(--my-tool-icon-bg) !important;
            border: 1rpx solid var(--my-tool-icon-border);
            box-shadow: var(--my-tool-icon-shadow);
            .text-black {
              font-size: 32rpx;
              color: var(--my-tool-icon-color) !important;
            }
          }
          .tool-card-title {
            margin-top: 16rpx;
            padding: 0 2rpx;
            min-height: 64rpx;
            font-size: 23rpx;
            line-height: 1.5;
            font-weight: 700;
            letter-spacing: 0.4rpx;
            position: relative;
            z-index: 2;
            width: 100%;
            color: var(--my-tool-title) !important;
            text-align: center;
            text-shadow: var(--my-tool-title-shadow);
            display: flex;
            align-items: center;
            justify-content: center;
            text {
              display: block;
              color: var(--my-tool-title) !important;
              font-size: 23rpx !important;
              font-weight: 700 !important;
              line-height: 1.5 !important;
              letter-spacing: 0.4rpx;
              opacity: 1 !important;
            }
          }
          &.is-accent {
            background: var(--my-tool-accent-bg) !important;
            border-color: var(--my-tool-accent-border) !important;
            box-shadow: var(--my-tool-accent-shadow) !important;
            .tool-card-icon {
              background: var(--my-tool-accent-icon-bg);
              box-shadow: var(--my-tool-accent-shadow);
              .text-black {
                color: var(--my-tool-accent-icon-color) !important;
              }
            }
            .tool-card-title {
              color: var(--my-tool-accent-title) !important;
              text {
                color: var(--my-tool-accent-title) !important;
              }
            }
          }
          &.is-merchant {
            background: var(--my-tool-merchant-bg) !important;
            border-color: var(--my-tool-card-border) !important;
            .tool-card-icon {
              background: var(--my-tool-icon-bg);
              border-color: var(--my-tool-icon-border);
              box-shadow: var(--my-tool-icon-shadow);
              .text-black {
                color: var(--my-tool-icon-color) !important;
              }
            }
          }
          &.is-system {
            background: var(--my-tool-system-bg) !important;
            border-color: var(--my-tool-card-border) !important;
          }
          &.is-admin {
            background: var(--my-tool-admin-bg) !important;
            border-color: var(--my-tool-card-border) !important;
            .tool-card-icon {
              background: var(--my-tool-icon-bg);
              border-color: var(--my-tool-icon-border);
              box-shadow: var(--my-tool-icon-shadow);
              .text-black {
                color: var(--my-tool-icon-color) !important;
              }
            }
          }
        }
        .tool-badge {
          position: absolute !important;
          top: 12rpx;
          right: 12rpx;
          left: auto !important;
          margin-left: 0 !important;
          margin-top: 0 !important;
          min-width: 40rpx;
          height: 34rpx;
          padding: 0 10rpx;
          display: inline-flex !important;
          align-items: center;
          justify-content: center;
          line-height: 1 !important;
          font-size: 20rpx;
          font-weight: 700;
          border-radius: 999rpx;
          z-index: 3;
          box-shadow: 0 6rpx 12rpx rgba(229, 77, 109, 0.18);
        }
      }
      .merchant-super-tools {
        margin: 12rpx 18.18rpx 22rpx;
        padding: 0;
        border-top: 0;
        &__hero {
          padding: 26rpx 24rpx;
          border-radius: 26rpx;
          background: var(--my-super-hero-bg);
          border: 1rpx solid var(--my-super-hero-border);
          box-shadow: 0 14rpx 28rpx rgba(214, 170, 90, 0.12);
          animation: superHeroFadeIn 0.6s ease-out both;
        }
        &__badge {
          display: inline-flex;
          padding: 8rpx 18rpx;
          border-radius: 999rpx;
          background: var(--my-super-badge-bg);
          color: var(--my-super-badge-color);
          font-size: 22rpx;
          font-weight: 700;
        }
        &__hero-title {
          margin-top: 14rpx;
          font-size: 34rpx;
          font-weight: 700;
          color: var(--my-super-title);
        }
        &__hero-desc {
          margin-top: 10rpx;
          font-size: 22rpx;
          line-height: 1.6;
          color: var(--my-super-desc);
        }
        &__grid {
          display: grid;
          grid-template-columns: repeat(3, minmax(0, 1fr));
          gap: 14rpx;
          margin-top: 16rpx;
        }
      }
      .merchant-super-tool-card {
        position: relative;
        padding: 24rpx 18rpx 20rpx;
        border-radius: 22rpx;
        background: var(--my-super-card-bg);
        border: 1rpx solid var(--my-super-card-border);
        box-shadow: var(--my-super-card-shadow);
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        min-height: 220rpx;
        animation:
          superToolEnter 0.55s ease-out both,
          superToolFloat 4.6s ease-in-out infinite;
        &:nth-child(1) {
          animation-delay: 0.04s, 0s;
        }
        &:nth-child(2) {
          animation-delay: 0.12s, 0.4s;
        }
        &:nth-child(3) {
          animation-delay: 0.2s, 0.8s;
        }
        &.is-primary {
          background: var(--my-super-primary-bg);
          border-color: var(--my-super-primary-border);
          box-shadow: var(--my-super-primary-shadow);
          animation:
            superToolEnter 0.55s ease-out both,
            superToolFloatStrong 3.8s ease-in-out infinite;
        }
        &.is-secondary {
          background: var(--my-super-secondary-bg);
          border-color: var(--my-super-secondary-border);
          box-shadow: var(--my-super-secondary-shadow);
        }
        &.is-alert {
          background: var(--my-super-alert-bg);
          border-color: var(--my-super-alert-border);
          box-shadow: var(--my-super-alert-shadow);
        }
        &__badge {
          position: absolute;
          top: 14rpx;
          right: 14rpx;
          min-width: 46rpx;
          height: 36rpx;
          padding: 0 12rpx;
          border-radius: 999rpx;
          display: inline-flex;
          align-items: center;
          justify-content: center;
          font-size: 20rpx;
          font-weight: 700;
          line-height: 1;
          background: var(--my-super-card-badge-bg);
          color: var(--my-super-card-badge-color);
        }
        &__icon {
          width: 64rpx;
          height: 64rpx;
          border-radius: 20rpx;
          display: inline-flex;
          align-items: center;
          justify-content: center;
          background: var(--my-super-icon-bg);
          color: var(--my-super-icon-color);
          font-size: 32rpx;
          box-shadow: 0 10rpx 18rpx rgba(234, 176, 82, 0.22);
        }
        &__title {
          margin-top: 18rpx;
          font-size: 26rpx;
          font-weight: 700;
          color: var(--my-super-title);
          line-height: 1.4;
        }
        &__desc {
          margin-top: 10rpx;
          font-size: 21rpx;
          line-height: 1.5;
          color: var(--my-super-desc);
          min-height: 58rpx;
        }
        &__action {
          margin-top: auto;
          padding-top: 14rpx;
          display: inline-flex;
          align-items: center;
          gap: 6rpx;
          font-size: 21rpx;
          font-weight: 700;
          color: var(--my-super-action);
        }
        &.is-primary &__badge {
          background: var(--my-super-card-badge-bg);
          color: var(--my-super-card-badge-color);
        }
        &.is-primary &__icon {
          background: var(--my-super-primary-icon-bg);
          box-shadow: var(--my-super-primary-shadow);
        }
        &.is-primary &__title,
        &.is-primary &__desc,
        &.is-primary &__action {
          color: var(--my-super-primary-text);
        }
        &.is-secondary &__badge {
          background: var(--my-super-badge-bg);
          color: var(--my-super-badge-color);
        }
        &.is-secondary &__icon {
          background: var(--my-super-secondary-icon-bg);
          box-shadow: var(--my-super-secondary-shadow);
        }
        &.is-secondary &__action {
          color: var(--my-super-secondary-action);
        }
        &.is-alert &__badge {
          background: var(--my-super-card-badge-bg);
          color: var(--my-super-card-badge-color);
          animation: superBadgePulse 1.8s ease-in-out infinite;
        }
        &.is-alert &__icon {
          background: var(--my-super-alert-icon-bg);
          box-shadow: var(--my-super-alert-shadow);
        }
        &.is-alert &__action {
          color: var(--my-super-alert-action);
        }
      }
    }
  }
}
.zaiui-my-box.show {
  display: block;
}
@media screen and (max-width: 375px) {
  .zaiui-my-box {
    .zaiui-head-box {
      padding-bottom: 76rpx;
      .zaiui-user-info-box {
        .user-shell-card {
          margin-left: 22rpx;
          margin-right: 22rpx;
          padding: 22rpx 22rpx 20rpx;
        }
      }
      .zaiui-user-info-num-box {
        margin-left: 22rpx;
        margin-right: 22rpx;
      }
      .zaiui-user-info-tip-box {
        margin-left: 22rpx;
        margin-right: 22rpx;
      }
    }
    .zaiui-view-content {
      padding-left: 22rpx;
      padding-right: 22rpx;
      margin-top: -32rpx;
      .env-card {
        padding: 20rpx 22rpx;
        &__title {
          font-size: 24rpx;
        }
        &__desc {
          font-size: 20rpx;
        }
        &__url {
          font-size: 18rpx;
        }
      }
      .zaiui-user-info-order-box {
        .order-box-head {
          flex-direction: column;
          align-items: flex-start;
          gap: 6rpx;
        }
        .order-grid {
          gap: 10rpx;
        }
        .order-grid-card {
          min-height: 132rpx;
          padding: 18rpx 16rpx;
        }
        .order-grid-icon {
          width: 62rpx;
          height: 62rpx;
        }
      }
      .zaiui-user-info-tools-box {
        .tools-view {
          padding: 14rpx 16rpx 8rpx;
          .tools-title {
            font-size: 30rpx !important;
          }
          .tools-right {
            right: 16rpx;
          }
        }
        .merchant-tools-summary {
          padding: 22rpx;
          &__head {
            flex-direction: column;
            align-items: flex-start;
          }
        }
        .zaiui-tools-list-box {
          .recommend-tools-grid {
            margin: 0 -6rpx;
            padding: 6rpx 0 0;
            .tool-grid-cell {
              padding: 0 6rpx 12rpx;
            }
            &.is-two-column {
              .tool-grid-cell {
                width: 50%;
              }
            }
            &.is-three-column {
              .tool-grid-cell {
                width: 33.333333%;
              }
            }
          }
          .cu-item.tool-grid-card {
            min-height: 154rpx;
            padding: 18rpx 10rpx 16rpx;
            .tool-card-icon {
              width: 58rpx;
              height: 58rpx;
              border-radius: 16rpx;
              .text-black {
                font-size: 28rpx;
              }
            }
            .tool-card-title {
              margin-top: 12rpx;
              min-height: 56rpx;
              font-size: 22rpx;
            }
          }
        }
        .merchant-super-tools {
          &__hero {
            padding: 24rpx 22rpx;
          }
          &__hero-title {
            font-size: 32rpx;
          }
          &__hero-desc {
            font-size: 21rpx;
          }
          &__grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12rpx;
          }
        }
        .merchant-super-tool-card {
          min-height: 188rpx;
          padding: 22rpx 16rpx 18rpx;
          &__icon {
            width: 58rpx;
            height: 58rpx;
            border-radius: 18rpx;
            font-size: 28rpx;
          }
          &__title {
            margin-top: 16rpx;
            font-size: 24rpx;
          }
          &__desc {
            margin-top: 8rpx;
            font-size: 20rpx;
            min-height: 48rpx;
          }
        }
      }
    }
  }
}
@media screen and (max-width: 360px) {
  .zaiui-my-box {
    .zaiui-head-box {
      .zaiui-user-info-box {
        .user-shell-card {
          margin-left: 18rpx;
          margin-right: 18rpx;
        }
        .login-user-view {
          .cu-btn {
            min-width: 164rpx;
          }
        }
        .cu-list.menu-avatar > .cu-item {
          .cu-avatar {
            width: 98rpx !important;
            height: 98rpx !important;
          }
          .content {
            width: calc(100% - 98rpx - 54rpx);
          }
        }
        .cu-list.menu-avatar > .cu-item .content > view:first-child {
          font-size: 32rpx;
        }
      }
      .zaiui-user-info-num-box {
        margin-left: 18rpx;
        margin-right: 18rpx;
        .text-xl {
          font-size: 32rpx;
        }
      }
      .zaiui-user-info-tip-box {
        margin-left: 18rpx;
        margin-right: 18rpx;
      }
    }
    .zaiui-view-content {
      padding-left: 18rpx;
      padding-right: 18rpx;
      .zaiui-user-info-order-box {
        padding: 10rpx 10rpx 14rpx;
        .order-box-head {
          padding: 14rpx 16rpx 6rpx;
          &__title {
            font-size: 30rpx;
          }
          &__desc {
            font-size: 20rpx;
          }
        }
        .order-grid {
          gap: 8rpx;
        }
        .order-grid-card {
          min-height: 124rpx;
          padding: 16rpx 14rpx;
        }
        .order-grid-label {
          font-size: 20rpx;
        }
        .order-grid-hint {
          font-size: 18rpx;
        }
      }
      .pending-reminder-card {
        padding: 24rpx 24rpx 22rpx;
        .pending-title {
          font-size: 30rpx;
        }
        .pending-desc,
        .pending-action {
          font-size: 20rpx;
        }
      }
      .zaiui-user-info-tools-box {
        padding: 10rpx 10rpx 14rpx;
        .tools-view {
          .tools-title {
            font-size: 28rpx !important;
          }
        }
        .zaiui-tools-list-box {
          .recommend-tools-grid {
            margin: 0 -4rpx;
            .tool-grid-cell {
              padding: 0 4rpx 8rpx;
            }
            &.is-two-column {
              .tool-grid-cell {
                width: 50%;
              }
            }
            &.is-three-column {
              .tool-grid-cell {
                width: 33.333333%;
              }
            }
          }
          .cu-item.tool-grid-card {
            min-height: 138rpx;
            .tool-card-title {
              font-size: 20rpx;
              text {
                font-size: 20rpx !important;
              }
            }
          }
        }
        .merchant-super-tool-card {
          min-height: 176rpx;
          &__title {
            font-size: 22rpx;
          }
          &__desc,
          &__action {
            font-size: 19rpx;
          }
        }
      }
    }
  }
}
@keyframes superHeroFadeIn {
  0% {
    opacity: 0;
    transform: translateY(16rpx);
  }
  100% {
    opacity: 1;
    transform: translateY(0);
  }
}
@keyframes superToolEnter {
  0% {
    opacity: 0;
    transform: translateY(22rpx) scale(0.96);
  }
  100% {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}
@keyframes superToolFloat {
  0%,
  100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-5rpx);
  }
}
@keyframes superToolFloatStrong {
  0%,
  100% {
    transform: translateY(0);
    box-shadow: 0 14rpx 28rpx rgba(224, 142, 76, 0.16);
  }
  50% {
    transform: translateY(-6rpx);
    box-shadow: 0 18rpx 32rpx rgba(224, 142, 76, 0.2);
  }
}
@keyframes superBadgePulse {
  0%,
  100% {
    transform: scale(1);
    box-shadow: 0 0 0 0 rgba(227, 123, 67, 0.16);
  }
  50% {
    transform: scale(1.08);
    box-shadow: 0 0 0 10rpx rgba(227, 123, 67, 0);
  }
}
/* #ifdef MP-WEIXIN */
.zaiui-my-box {
  .zaiui-head-box {
    .zaiui-head-glow {
      filter: none;
      opacity: 0.22;
    }
    .zaiui-user-info-box {
      .user-shell-card {
        backdrop-filter: none;
      }
    }
  }
  .zaiui-view-content {
    .zaiui-user-info-tools-box {
      border: 1rpx solid var(--my-tools-panel-border);
      .zaiui-tools-list-box {
        .recommend-tools-grid {
          display: flex;
          flex-wrap: wrap;
          margin: 0 -8rpx;
          padding: 8rpx 0 0;
          .tool-grid-cell {
            width: 33.333333%;
            padding: 0 8rpx 14rpx;
            box-sizing: border-box;
            display: flex;
          }
          .cu-item.tool-grid-card {
            width: 100% !important;
            min-height: 146rpx;
            margin: 0;
            padding: 18rpx 10rpx 16rpx;
            box-sizing: border-box;
            .tool-card-icon {
              width: 56rpx;
              height: 56rpx;
              border-radius: 16rpx;
            }
            .tool-card-title {
              min-height: 54rpx;
              margin-top: 12rpx;
              font-size: 21rpx;
              text {
                font-size: 21rpx !important;
              }
            }
          }
          &.is-two-column {
            .tool-grid-cell {
              width: 50%;
            }
          }
          &.is-three-column {
            .tool-grid-cell {
              width: 33.333333%;
            }
          }
        }
      }
      .merchant-super-tools {
        &__grid {
          display: flex;
          flex-wrap: wrap;
          margin: 16rpx -7rpx 0;
        }
      }
      .merchant-super-tool-card {
        width: calc(33.3333% - 14rpx);
        margin: 0 7rpx 14rpx;
        box-sizing: border-box;
      }
    }
  }
}
/* #endif */
</style>

