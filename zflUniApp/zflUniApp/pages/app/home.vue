<template>
  <view
    class="home-page"
    :class="
      'ui-theme-' +
      (($store.state.setting &&
        $store.state.setting.system &&
        $store.state.setting.system.ui_theme_style) ||
        'origin')
    "
    :style="pageThemeCssVars"
  >
    <view v-if="reviewMode" class="review-page">
      <view class="review-hero">
        <view class="review-topbar">
          <view class="brand-badge">平台服务</view>
          <view class="status-badge">服务中心</view>
        </view>

        <view class="review-brand">
          <image
            v-if="reviewInfo.logo_url"
            class="brand-logo"
            :src="resolveImageUrl(reviewInfo.logo_url)"
            mode="aspectFill"
          ></image>
          <view v-else class="brand-logo brand-fallback">{{
            brandInitial
          }}</view>
          <view class="brand-copy">
            <view class="brand-title">{{
              reviewInfo.system_name || "涛冠优选"
            }}</view>
            <view class="brand-subtitle">农产品合作咨询与客户服务支持</view>
          </view>
        </view>

        <view class="review-card hero-copy">
          <view class="hero-heading">{{ reviewInfo.review_hero_title }}</view>
          <view class="hero-desc">{{ reviewInfo.review_hero_desc }}</view>
          <view class="hero-actions">
            <button class="action-btn primary" @tap="toServicePage">
              {{ reviewInfo.review_primary_btn_text }}
            </button>
            <button class="action-btn ghost" @tap="openNewsList">
              {{ reviewInfo.review_secondary_btn_text }}
            </button>
          </view>
        </view>
      </view>

      <view class="review-content">
        <view class="review-card">
          <view class="section-title">服务入口</view>
          <view class="service-grid">
            <view
              v-for="item in reviewServiceCards"
              :key="item.action"
              class="service-item"
              @tap="handleReviewAction(item.action)"
            >
              <view class="service-icon">{{ item.icon }}</view>
              <view class="service-title">{{ item.title }}</view>
              <view class="service-desc">{{ item.desc }}</view>
            </view>
          </view>
        </view>

        <view class="review-card">
          <view class="section-title">服务内容</view>
          <view class="feature-grid">
            <view
              v-for="item in reviewFeatures"
              :key="item.title"
              class="feature-item"
            >
              <view class="feature-icon">{{ item.icon }}</view>
              <view class="feature-title">{{ item.title }}</view>
              <view class="feature-text">{{ item.desc }}</view>
            </view>
          </view>
        </view>

        <view class="review-card">
          <view class="section-title">合作方向</view>
          <view class="category-grid">
            <view
              v-for="item in reviewCategoryList"
              :key="item.title"
              class="category-item"
            >
              <view class="category-tag">{{ item.tag }}</view>
              <view class="category-title">{{ item.title }}</view>
              <view class="category-desc">{{ item.desc }}</view>
            </view>
          </view>
        </view>

        <view class="review-card">
          <view class="section-title">平台支持</view>
          <view class="scene-list">
            <view
              v-for="item in reviewSceneList"
              :key="item.title"
              class="scene-item"
            >
              <view class="scene-title">{{ item.title }}</view>
              <view class="scene-desc">{{ item.desc }}</view>
            </view>
          </view>
        </view>

        <view v-if="reviewArticleList.length" class="review-card">
          <view class="section-head">
            <view class="section-title">平台资讯</view>
            <view class="section-link" @tap="openNewsList">查看全部</view>
          </view>
          <view class="article-list">
            <view
              v-for="item in reviewArticleList"
              :key="item.content_id"
              class="article-item"
              @tap="openArticle(item)"
            >
              <view class="article-main">
                <view class="article-title">{{ item.title }}</view>
                <view class="article-text">{{
                  item.text || "点击查看资讯详情"
                }}</view>
              </view>
              <view class="article-time">{{
                formatArticleTime(item.create_time)
              }}</view>
            </view>
          </view>
        </view>

        <view class="review-card">
          <view class="section-title">{{ reviewInfo.review_intro_title }}</view>
          <image
            v-if="reviewInfo.review_intro_image_url"
            class="intro-image"
            :src="resolveImageUrl(reviewInfo.review_intro_image_url)"
            mode="aspectFill"
          ></image>
          <view class="intro-text">{{ reviewInfo.review_intro_desc }}</view>
        </view>

        <view class="review-card">
          <view class="section-title">服务流程</view>
          <view class="step-list">
            <view
              v-for="(item, index) in reviewProcessList"
              :key="item.title"
              class="step-item"
            >
              <view class="step-index">{{ index + 1 }}</view>
              <view class="step-body">
                <view class="step-title">{{ item.title }}</view>
                <view class="step-desc">{{ item.desc }}</view>
              </view>
            </view>
          </view>
        </view>

        <view class="review-card">
          <view class="section-title">联系方式</view>
          <view class="contact-list">
            <view
              v-if="primaryPhone"
              class="contact-item"
              @tap="callPhone(primaryPhone)"
            >
              <text class="contact-label">联系电话</text>
              <text class="contact-value">{{ primaryPhone }}</text>
            </view>
            <view
              v-if="primaryWechat"
              class="contact-item"
              @tap="copyText(primaryWechat, '客服微信已复制')"
            >
              <text class="contact-label">客服微信</text>
              <text class="contact-value">{{ primaryWechat }}</text>
            </view>
            <view
              v-if="reviewInfo.member_website"
              class="contact-item"
              @tap="copyWebsite"
            >
              <text class="contact-label">平台地址</text>
              <text class="contact-value">{{ reviewInfo.member_website }}</text>
            </view>
            <view
              v-if="reviewInfo.service_wechat_image_url"
              class="wechat-card"
            >
              <image
                class="wechat-image"
                :src="resolveImageUrl(reviewInfo.service_wechat_image_url)"
                mode="aspectFill"
              ></image>
              <view class="wechat-tip">扫码添加客服微信</view>
            </view>
          </view>
        </view>
      </view>
    </view>

    <view v-else class="default-page">
      <view class="home-header">
        <view class="header-copy">
          <view class="header-title">涛冠优选</view>
          <view class="header-subtitle">精选商家与优质好物</view>
        </view>
        <view class="search-box" @tap="searchTap">
          <text class="cuIcon-search"></text>
          <text class="search-placeholder">搜索商品、商家</text>
        </view>
        <view class="tab-row">
          <view
            v-for="item in headTabs"
            :key="item.value"
            class="tab-pill"
            :class="{ active: currentTab === item.value }"
            @tap="tabSelect(item.value)"
          >
            {{ item.label }}
          </view>
        </view>
      </view>

      <view class="env-banner">
        <view class="env-banner__head">
          <view class="env-banner__title">当前环境</view>
          <view
            class="env-banner__badge"
            :class="currentEnvInfo.is_prod ? 'is-prod' : 'is-test'"
          >
            {{ currentEnvInfo.label }}
          </view>
        </view>
        <view class="env-banner__desc">{{ homeEnvDescription }}</view>
        <view class="env-banner__meta">
          <text class="env-banner__tag">{{ homeModeText }}</text>
          <text class="env-banner__tag">{{ homeDataText }}</text>
          <text class="env-banner__tag">{{ envIsolationText }}</text>
          <text class="env-banner__tag">{{ envIsolationStatusText }}</text>
          <text class="env-banner__tag">{{ envReleaseStageText }}</text>
        </view>
        <view class="env-banner__note">{{ homeActionHint }}</view>
        <view class="env-banner__note env-banner__note--strong">{{
          envReleaseHint
        }}</view>
        <view v-if="envRiskList.length" class="env-banner__risk-list">
          <view
            v-for="item in envRiskList"
            :key="item"
            class="env-banner__risk-item"
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
                  :class="'is-' + (item.status_text || '').toLowerCase()"
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
        <view class="env-banner__url">{{ currentEnvInfo.api_root_url }}</view>
      </view>

      <view class="rollout-panel">
        <view class="rollout-panel__head">
          <view class="rollout-panel__title">上线承接提示</view>
          <view
            class="rollout-panel__badge"
            :class="currentEnvInfo.is_prod ? 'is-prod' : 'is-test'"
          >
            {{ currentEnvInfo.is_prod ? "正式入口" : "灰度前检查" }}
          </view>
        </view>
        <view class="rollout-panel__desc">{{ homeRolloutHint }}</view>
        <view class="rollout-panel__list">
          <view
            v-for="item in homeRolloutChecklist"
            :key="item.label"
            class="rollout-panel__item"
          >
            <text class="rollout-panel__item-label">{{ item.label }}</text>
            <text class="rollout-panel__item-value">{{ item.value }}</text>
          </view>
        </view>
        <view class="rollout-panel__risk">{{ homeRollbackHint }}</view>
      </view>

      <view v-if="recentActionSummary" class="recent-action-card">
        <view class="recent-action-card__title">最近操作</view>
        <view class="recent-action-card__desc">{{ recentActionSummary }}</view>
      </view>

      <view class="ops-panel">
        <view class="ops-panel__head">
          <view>
            <view class="ops-panel__title">首页运营速览</view>
            <view class="ops-panel__desc">{{ homeOpsHint }}</view>
          </view>
          <view class="ops-panel__badge" :class="homeOpsBadgeClass">{{
            homeOpsBadgeText
          }}</view>
        </view>
        <view class="ops-panel__meta">
          <text
            v-for="item in homeOpsTags"
            :key="item"
            class="ops-panel__tag"
            >{{ item }}</text
          >
        </view>
        <view class="ops-panel__risk">{{ homeOpsRiskText }}</view>
        <view class="ops-panel__actions">
          <view class="ops-panel__action" @tap="reloadHomeContent"
            >刷新首页内容</view
          >
          <view class="ops-panel__action" @tap="openAppHomeTop"
            >回到首页顶部</view
          >
          <view class="ops-panel__action" @tap="openNewsList">查看资讯</view>
          <view class="ops-panel__action" @tap="retryLoad">重新加载</view>
        </view>
      </view>

      <view class="source-panel">
        <view class="source-panel__head">
          <view>
            <view class="source-panel__title">首页内容状态</view>
            <view class="source-panel__desc">
              后台刚改完轮播、公告、友链后，先看这里能不能真实承接出来。
            </view>
          </view>
          <view class="source-panel__badge" :class="homeSourceHealthClass">
            {{ homeSourceHealthText }}
          </view>
        </view>
        <view class="source-panel__grid">
          <view
            v-for="item in homeSourceCards"
            :key="item.key"
            class="source-card"
            :class="'is-' + item.tone"
            @tap="handleHomeSourceAction(item.key)"
          >
            <view class="source-card__label">{{ item.label }}</view>
            <view class="source-card__value">{{ item.value }}</view>
            <view class="source-card__desc">{{ item.desc }}</view>
            <view class="source-card__action">{{ item.actionText }}</view>
          </view>
        </view>
      </view>

      <view class="entry-panel">
        <view class="entry-panel__head">
          <view class="entry-panel__title">{{ entryPanelTitle }}</view>
          <view
            class="entry-panel__badge"
            :class="currentEnvInfo.is_prod ? 'is-prod' : 'is-test'"
            >{{ entryPanelBadge }}</view
          >
        </view>
        <view class="entry-panel__desc">{{ entryPanelDesc }}</view>
        <view class="entry-panel__list">
          <view
            v-for="item in entryChecklist"
            :key="item.label"
            class="entry-panel__item"
          >
            <text class="entry-panel__item-label">{{ item.label }}</text>
            <text class="entry-panel__item-value">{{ item.value }}</text>
          </view>
        </view>
      </view>

      <view class="banner-wrap">
        <view v-if="!carouselList.length" class="banner-empty-card">
          <view class="banner-empty-card__title">首页轮播暂未加载出来</view>
          <view class="banner-empty-card__desc">
            建议先回后台检查轮播资源是否启用，再重新加载首页确认首屏是否已承接。
          </view>
          <view class="banner-empty-card__actions">
            <button class="empty-action" @tap="reloadHomeContent">
              重新加载首页
            </button>
          </view>
        </view>
        <swiper
          v-else
          class="banner-swiper"
          circular
          autoplay
          indicator-dots
          indicator-active-color="#ffffff"
        >
          <swiper-item v-for="(item, index) in carouselList" :key="index">
            <image
              class="banner-image"
              :src="resolveImageUrl(item.file_url, defaultImage)"
              mode="aspectFill"
            ></image>
          </swiper-item>
        </swiper>
      </view>

      <view class="content-section">
        <view class="section-head">
          <view class="section-title">{{ title || "精选推荐" }}</view>
          <view class="section-tip">持续更新</view>
        </view>

        <view v-if="loadError" class="empty-box">
          <view class="empty-title">首页内容加载失败</view>
          <view class="empty-text">{{ loadError }}</view>
          <button class="empty-action" @tap="retryLoad">重新加载</button>
        </view>

        <view v-else-if="isGoodsMode" class="goods-grid">
          <view
            v-for="item in list"
            :key="item.id"
            class="goods-card"
            @tap="openGoodsDetail(item)"
          >
            <image
              class="goods-image"
              :src="resolveImageUrl(item.image_url, defaultImage)"
              mode="aspectFill"
            ></image>
            <view class="goods-body">
              <view class="goods-title">{{ item.title }}</view>
              <view class="goods-spec">{{ item.spec || "标准规格" }}</view>
              <view class="goods-meta">
                <text class="goods-price">￥{{ item.price }}</text>
                <text class="goods-sales">
                  已售 {{ item.sales_sum || 0 }}{{ item.unit || "件" }}
                </text>
              </view>
              <view class="goods-merchant">{{
                merchantTitleText(item.merchant_title, "平台直营")
              }}</view>
            </view>
          </view>
        </view>

        <view v-else class="merchant-list">
          <view
            v-for="item in list"
            :key="item.id"
            class="merchant-card"
            @tap="listTap(item)"
          >
            <image
              class="merchant-avatar"
              :src="
                resolveImageUrl(
                  item.member && item.member.avatar_url,
                  defaultImage,
                )
              "
              mode="aspectFill"
            ></image>
            <view class="merchant-body">
              <view class="merchant-title">{{
                merchantTitleText(item.title, "商家信息")
              }}</view>
              <view class="merchant-desc">
                {{
                  item.sellGoodsNum > 0
                    ? "在售 " + item.sellGoodsNum + " 件好物"
                    : "商家正在完善商品信息"
                }}
              </view>
            </view>
            <view class="merchant-arrow">查看</view>
          </view>
        </view>

        <view v-if="!list.length && !isLoading && !loadError" class="empty-box">
          <view class="empty-title">暂无内容</view>
          <view class="empty-text">数据加载完成后会显示在这里</view>
        </view>
      </view>

      <view v-if="isLoading" class="loading-box">加载中...</view>
      <view class="cu-tabbar-height"></view>
    </view>

    <view
      v-if="popupVisible && popupNotice.notice_id"
      class="popup-mask"
      @tap="closePopup('popup_close')"
    >
      <view class="popup-card" @tap.stop>
        <view class="popup-close" @tap="closePopup('popup_close')">×</view>
        <image
          v-if="popupNotice.image_url"
          class="popup-image"
          :src="resolveImageUrl(popupNotice.image_url, defaultImage)"
          mode="aspectFill"
        ></image>
        <view class="popup-body">
          <view
            class="popup-title"
            :style="{ color: popupNotice.title_color || '#172333' }"
            >{{ popupNotice.title }}</view
          >
          <view v-if="popupNotice.desc" class="popup-desc">{{
            popupNotice.desc
          }}</view>
          <scroll-view scroll-y class="popup-scroll">
            <view class="popup-html" v-html="popupRenderedContent"></view>
          </scroll-view>
          <view class="popup-actions">
            <button class="popup-btn ghost" @tap="closePopup('popup_close')">
              暂不查看
            </button>
            <button class="popup-btn primary" @tap="openPopupNotice">
              {{ popupButtonText }}
            </button>
          </view>
        </view>
      </view>
    </view>
  </view>
</template>

<script>
import api from "@/api";
import cache from "@/utils/cache";
import store from "@/store/common";
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
import { resolveImageUrl as resolveSafeImageUrl } from "@/utils/resource.js";
import {
  applyUiThemeFromSetting,
  resolveUiThemeStyle,
} from "@/utils/ui-theme.js";

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

const REVIEW_FEATURES = [
  {
    icon: "询",
    title: "合作咨询",
    desc: "提供农产品合作、平台服务与业务沟通入口。",
  },
  {
    icon: "讯",
    title: "官方资讯",
    desc: "持续更新平台动态、服务说明与合作信息。",
  },
  {
    icon: "联",
    title: "联系支持",
    desc: "联系电话、客服微信与服务渠道公开可见。",
  },
  { icon: "服", title: "服务流程", desc: "从咨询到资料对接的流程一目了然。" },
];

const REVIEW_CATEGORY_LIST = [
  {
    tag: "农",
    title: "农副合作",
    desc: "围绕粮油、调味、日用等方向提供合作咨询与对接。",
  },
  {
    tag: "商",
    title: "商家服务",
    desc: "支持合作咨询、资料提交与平台服务沟通。",
  },
  {
    tag: "客",
    title: "客户支持",
    desc: "提供客服联系、电话沟通与后续服务答疑入口。",
  },
  {
    tag: "资",
    title: "资讯查看",
    desc: "可浏览平台公告、服务说明和合作动态内容。",
  },
];

const REVIEW_SCENE_LIST = [
  {
    title: "平台介绍与合作咨询",
    desc: "查看平台介绍、服务说明与合作方向，联系官方进行业务沟通。",
  },
  {
    title: "官方客服与电话支持",
    desc: "通过客服中心、电话、微信等方式获取咨询与后续服务支持。",
  },
  {
    title: "资讯查看与资料了解",
    desc: "浏览平台资讯、品牌内容和服务流程，完整了解平台服务能力。",
  },
];

const REVIEW_SERVICE_CARDS = [
  {
    icon: "服",
    title: "联系客服",
    desc: "咨询平台服务与合作事项",
    action: "service",
  },
  { icon: "电", title: "电话咨询", desc: "快速拨打官方电话", action: "phone" },
  {
    icon: "讯",
    title: "平台资讯",
    desc: "查看平台动态与服务说明",
    action: "news",
  },
  {
    icon: "微",
    title: "客服微信",
    desc: "复制客服微信获取支持",
    action: "wechat",
  },
];

const REVIEW_PROCESS_LIST = [
  {
    title: "了解服务内容",
    desc: "先查看平台介绍、服务内容和资讯信息，确认合作方向。",
  },
  {
    title: "联系官方客服",
    desc: "通过电话、微信或客服中心联系平台，了解合作与服务细节。",
  },
  {
    title: "提交资料对接",
    desc: "确认合作意向后，由平台进一步对接合作信息与展示资料。",
  },
];

const HOME_PAGE_THEME_PRESETS = {
  origin: {
    pageBg: "#f4f7fb",
    reviewPageBg:
      "radial-gradient(circle at top right, rgba(236, 138, 87, 0.24), transparent 26%), linear-gradient(180deg, #f7fbff 0%, #f1f5fa 100%)",
    reviewHeroBg:
      "radial-gradient(circle at top right, rgba(255, 255, 255, 0.12), transparent 22%), linear-gradient(135deg, #184f78 0%, #2a6f93 48%, #eb8b59 100%)",
    reviewCardBg: "rgba(255, 255, 255, 0.96)",
    reviewItemBg: "#f5f8fc",
    reviewAccent: "#184f78",
    reviewTitle: "#10283d",
    reviewText: "#607181",
    reviewMuted: "#6d7a87",
    headerBg:
      "radial-gradient(circle at top right, rgba(255, 255, 255, 0.12), transparent 24%), linear-gradient(180deg, #f2076a 0%, #ff216a 58%, #ff4c7e 100%)",
    headerShadow: "none",
    headerText: "#ffffff",
    headerSubtle: "rgba(255, 255, 255, 0.88)",
    searchBg: "rgba(255, 255, 255, 0.16)",
    searchBorder: "transparent",
    tabBg: "rgba(255, 255, 255, 0.14)",
    tabBorder: "transparent",
    tabText: "rgba(255, 255, 255, 0.82)",
    tabActiveBg: "#ffffff",
    tabActiveText: "#d93761",
    tabActiveShadow: "none",
    bannerBg: "transparent",
    contentBg: "#ffffff",
    contentBorder: "transparent",
    contentShadow: "0 16rpx 40rpx rgba(17, 34, 51, 0.05)",
    sectionTitle: "#10283d",
    sectionTip: "#6d7a87",
    itemBg: "transparent",
    itemBorder: "transparent",
    itemShadow: "none",
    itemTitle: "#10283d",
    itemText: "#6d7a87",
    itemMuted: "#6d7a87",
    price: "#e9653a",
    accent: "#2a6f93",
  },
  red_energy: {
    pageBg:
      "radial-gradient(circle at top right, rgba(255, 220, 198, 0.22), transparent 24%), linear-gradient(180deg, #a30b15 0%, #b9111d 8%, #fff1ea 9%, #fff8f4 100%)",
    reviewPageBg:
      "radial-gradient(circle at top right, rgba(255, 220, 198, 0.22), transparent 24%), linear-gradient(180deg, #8f0814 0%, #b70d1b 22%, #f2eee8 23%, #f6eee7 100%)",
    reviewHeroBg:
      "radial-gradient(circle at top right, rgba(255, 255, 255, 0.14), transparent 24%), linear-gradient(135deg, #8e0812 0%, #c51424 54%, #ef7d35 100%)",
    reviewCardBg:
      "linear-gradient(180deg, rgba(255, 252, 249, 0.99) 0%, rgba(255, 244, 238, 0.96) 100%)",
    reviewItemBg:
      "linear-gradient(180deg, rgba(255, 245, 240, 0.98) 0%, rgba(255, 234, 225, 0.95) 100%)",
    reviewAccent: "#a30c15",
    reviewTitle: "#8f140f",
    reviewText: "#a9613e",
    reviewMuted: "#a9613e",
    headerBg:
      "radial-gradient(circle at top right, rgba(255, 255, 255, 0.12), transparent 24%), linear-gradient(180deg, #8f0912 0%, #c91d24 62%, #f08d3f 100%)",
    headerShadow: "0 20rpx 42rpx rgba(116, 8, 12, 0.18)",
    headerText: "#fff8f4",
    headerSubtle: "rgba(255, 248, 244, 0.88)",
    searchBg: "rgba(255, 247, 242, 0.14)",
    searchBorder: "rgba(255, 241, 234, 0.14)",
    tabBg: "rgba(255, 247, 242, 0.14)",
    tabBorder: "rgba(255, 244, 233, 0.18)",
    tabText: "rgba(255, 248, 244, 0.82)",
    tabActiveBg:
      "linear-gradient(135deg, #8f0912 0%, #c91d24 62%, #f08d3f 100%)",
    tabActiveText: "#fff8f4",
    tabActiveShadow: "0 12rpx 26rpx rgba(111, 8, 12, 0.22)",
    bannerBg:
      "linear-gradient(135deg, rgba(117, 9, 12, 0.92) 0%, rgba(196, 18, 29, 0.92) 56%, rgba(241, 140, 60, 0.92) 100%)",
    contentBg:
      "linear-gradient(180deg, rgba(255, 252, 249, 0.99) 0%, rgba(255, 244, 238, 0.96) 100%)",
    contentBorder: "rgba(201, 29, 36, 0.12)",
    contentShadow: "0 18rpx 30rpx rgba(116, 8, 12, 0.1)",
    sectionTitle: "#8f140f",
    sectionTip: "#a9613e",
    itemBg:
      "linear-gradient(180deg, rgba(255, 251, 248, 0.99) 0%, rgba(255, 240, 233, 0.96) 100%)",
    itemBorder: "rgba(201, 29, 36, 0.1)",
    itemShadow: "0 12rpx 22rpx rgba(116, 8, 12, 0.08)",
    itemTitle: "#8f140f",
    itemText: "#a9613e",
    itemMuted: "#a9613e",
    price: "#8f140f",
    accent: "#a21d15",
  },
  yellow_energy: {
    pageBg:
      "radial-gradient(circle at top right, rgba(255, 226, 140, 0.28), transparent 26%), linear-gradient(180deg, #f4c63f 0%, #f7d869 21%, #fbf1ca 44%, #fff9ea 100%)",
    reviewPageBg:
      "radial-gradient(circle at top right, rgba(255, 226, 140, 0.24), transparent 26%), linear-gradient(180deg, #f8e9a8 0%, #fff8e8 36%, #fffaf0 100%)",
    reviewHeroBg:
      "radial-gradient(circle at top right, rgba(255, 255, 255, 0.16), transparent 24%), linear-gradient(135deg, #f2c23f 0%, #f8da74 48%, #fff7df 100%)",
    reviewCardBg:
      "linear-gradient(180deg, rgba(255, 255, 255, 0.99) 0%, rgba(255, 250, 238, 0.98) 100%)",
    reviewItemBg:
      "linear-gradient(180deg, rgba(255, 252, 242, 0.98) 0%, rgba(255, 246, 220, 0.94) 100%)",
    reviewAccent: "#b82013",
    reviewTitle: "#8f1f0f",
    reviewText: "#b16825",
    reviewMuted: "#b16825",
    headerBg:
      "radial-gradient(circle at top right, rgba(255, 247, 214, 0.42), transparent 28%), linear-gradient(135deg, #f2c339 0%, #f8da70 46%, #fff0ba 100%)",
    headerShadow: "0 20rpx 42rpx rgba(173, 92, 18, 0.18)",
    headerText: "#a32513",
    headerSubtle: "rgba(163, 37, 19, 0.88)",
    searchBg: "rgba(255, 255, 255, 0.34)",
    searchBorder: "rgba(183, 32, 18, 0.1)",
    tabBg: "rgba(255, 255, 255, 0.3)",
    tabBorder: "rgba(183, 32, 18, 0.08)",
    tabText: "#a32513",
    tabActiveBg:
      "linear-gradient(135deg, #b82013 0%, #dc531e 55%, #f3b132 100%)",
    tabActiveText: "#fff8ef",
    tabActiveShadow: "0 12rpx 24rpx rgba(183, 61, 16, 0.16)",
    bannerBg:
      "linear-gradient(135deg, rgba(247, 208, 86, 0.98) 0%, rgba(255, 231, 151, 0.96) 62%, rgba(255, 246, 220, 0.98) 100%)",
    contentBg:
      "linear-gradient(180deg, rgba(255, 255, 255, 0.99) 0%, rgba(255, 250, 238, 0.98) 100%)",
    contentBorder: "rgba(183, 32, 18, 0.1)",
    contentShadow: "0 18rpx 32rpx rgba(177, 100, 20, 0.12)",
    sectionTitle: "#8f1f0f",
    sectionTip: "#b42b15",
    itemBg:
      "linear-gradient(180deg, rgba(255, 252, 242, 0.98) 0%, rgba(255, 246, 220, 0.94) 100%)",
    itemBorder: "rgba(183, 32, 18, 0.1)",
    itemShadow: "0 12rpx 26rpx rgba(177, 100, 20, 0.1)",
    itemTitle: "#8f1f0f",
    itemText: "#b16825",
    itemMuted: "#a76b27",
    price: "#d7772e",
    accent: "#ad2814",
  },
  jade_modern: {
    pageBg:
      "radial-gradient(circle at top right, rgba(216, 164, 58, 0.18), transparent 24%), linear-gradient(180deg, #dfe7e0 0%, #eef3ee 22%, #f7f1e7 46%, #fbf8f2 100%)",
    reviewPageBg:
      "radial-gradient(circle at top right, rgba(216, 164, 58, 0.16), transparent 24%), linear-gradient(180deg, #dfe7e0 0%, #eef3ee 22%, #f7f1e7 46%, #fbf8f2 100%)",
    reviewHeroBg:
      "radial-gradient(circle at top right, rgba(255, 255, 255, 0.14), transparent 24%), linear-gradient(135deg, #184b42 0%, #206c60 54%, #d7a13e 100%)",
    reviewCardBg:
      "linear-gradient(180deg, rgba(255, 253, 250, 0.99) 0%, rgba(245, 240, 231, 0.97) 100%)",
    reviewItemBg:
      "linear-gradient(180deg, rgba(247, 244, 236, 0.98) 0%, rgba(239, 233, 220, 0.96) 100%)",
    reviewAccent: "#184b42",
    reviewTitle: "#21463f",
    reviewText: "#607068",
    reviewMuted: "#7b867f",
    headerBg:
      "radial-gradient(circle at top right, rgba(229, 204, 143, 0.34), transparent 28%), linear-gradient(135deg, #184b42 0%, #206c60 54%, #d7a13e 100%)",
    headerShadow: "0 20rpx 38rpx rgba(19, 52, 47, 0.14)",
    headerText: "#f8f4ea",
    headerSubtle: "rgba(248, 244, 234, 0.86)",
    searchBg: "rgba(255, 255, 255, 0.16)",
    searchBorder: "rgba(248, 244, 234, 0.28)",
    tabBg: "rgba(255, 255, 255, 0.12)",
    tabBorder: "rgba(248, 244, 234, 0.16)",
    tabText: "rgba(248, 244, 234, 0.86)",
    tabActiveBg:
      "linear-gradient(135deg, #184b42 0%, #1d665b 54%, #d5a13b 100%)",
    tabActiveText: "#f8f4ea",
    tabActiveShadow: "0 12rpx 24rpx rgba(21, 58, 52, 0.18)",
    bannerBg:
      "linear-gradient(135deg, rgba(24, 76, 67, 0.92) 0%, rgba(36, 101, 91, 0.9) 56%, rgba(245, 237, 220, 0.96) 100%)",
    contentBg:
      "linear-gradient(180deg, rgba(255, 253, 250, 0.99) 0%, rgba(245, 240, 231, 0.97) 100%)",
    contentBorder: "rgba(24, 76, 67, 0.12)",
    contentShadow: "0 18rpx 34rpx rgba(19, 52, 47, 0.12)",
    sectionTitle: "#21463f",
    sectionTip: "#7b867f",
    itemBg:
      "linear-gradient(180deg, rgba(250, 248, 242, 0.99) 0%, rgba(240, 235, 224, 0.96) 100%)",
    itemBorder: "rgba(24, 76, 67, 0.1)",
    itemShadow: "0 12rpx 24rpx rgba(19, 52, 47, 0.08)",
    itemTitle: "#21463f",
    itemText: "#607068",
    itemMuted: "#7b867f",
    price: "#c79036",
    accent: "#1d665b",
  },
};

export default {
  name: "home",
  data() {
    return {
      reviewMode: false,
      reviewInfo: {},
      reviewFeatures: REVIEW_FEATURES,
      reviewCategoryList: REVIEW_CATEGORY_LIST,
      reviewSceneList: REVIEW_SCENE_LIST,
      reviewServiceCards: REVIEW_SERVICE_CARDS,
      reviewProcessList: REVIEW_PROCESS_LIST,
      reviewArticleList: [],
      headTabs: [
        { label: "首页", value: "home" },
        { label: "发现好物", value: "discover" },
      ],
      currentTab: "home",
      popupVisible: false,
      popupNotice: {},
      popupClientKey: "",
      query: {
        page: 1,
        limit: 6,
      },
      count: 0,
      pages: 0,
      title: "",
      list: [],
      carouselList: [],
      isLoading: false,
      loadError: "",
      defaultImage: "/static/images/avatar/1.jpg",
      recentActionSummary: "",
      skipNextShowRefresh: true,
      popupNoticeTimer: null,
    };
  },
  computed: {
    currentEnvInfo() {
      return getCurrentEnvInfo();
    },
    homeEnvDescription() {
      return this.currentEnvInfo.is_prod
        ? "当前为正式环境，首页浏览与跳转会连接线上真实数据。"
        : `当前为${this.currentEnvInfo.label}，适合做首页、商品与商家链路联调。`;
    },
    homeModeText() {
      return this.isGoodsMode ? "当前首页：商品流" : "当前首页：商家流";
    },
    homeDataText() {
      return `当前内容：${this.list.length || 0} 条`;
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
    homeActionHint() {
      return this.currentEnvInfo.is_prod
        ? `从首页进入商品详情、商家页和资讯页都会使用真实线上数据，建议先确认环境再继续浏览。${getEnvIsolationHint(this.currentEnvInfo)}`
        : `建议先在当前环境检查首页切换、列表加载、详情跳转和弹窗公告链路。${getEnvIsolationHint(this.currentEnvInfo)}`;
    },
    homeOpsBadgeText() {
      if (this.loadError) {
        return "待处理";
      }
      if (!this.list.length) {
        return "待补数";
      }
      return this.isGoodsMode ? "商品运营中" : "商家运营中";
    },
    homeOpsBadgeClass() {
      if (this.loadError) {
        return "is-danger";
      }
      if (!this.list.length) {
        return "is-warning";
      }
      return "is-safe";
    },
    homeOpsHint() {
      if (this.loadError) {
        return "首页内容刚刚加载失败，建议先重新拉取，再继续核对商品、商家和资讯入口。";
      }
      if (!this.list.length) {
        return "当前首页暂无内容，建议先检查分类切换、接口返回和首页运营位是否已配置。";
      }
      return this.isGoodsMode
        ? "当前首页以商品流为主，适合核对商品卡、详情跳转、资讯承接与环境隔离提示。"
        : "当前首页以商家流为主，适合核对商家列表、商家主页和资讯承接是否顺手。";
    },
    homeOpsTags() {
      return [
        `主Tab：${this.isGoodsMode ? "商品" : "商家"}`,
        `列表条数：${this.list.length || 0}`,
        `轮播图：${this.carouselList.length || 0}`,
        `资讯数：${this.reviewArticleList.length || 0}`,
        `弹窗公告：${this.popupNotice && this.popupNotice.notice_id ? "已配置" : "未配置"}`,
      ];
    },
    homeSourceHealthText() {
      if (this.loadError) {
        return "承接异常";
      }
      if (!this.carouselList.length || !this.reviewArticleList.length) {
        return "待补核";
      }
      return "承接正常";
    },
    homeSourceHealthClass() {
      if (this.loadError) {
        return "is-danger";
      }
      if (!this.carouselList.length || !this.reviewArticleList.length) {
        return "is-warning";
      }
      return "is-safe";
    },
    homeSourceCards() {
      return [
        {
          key: "carousel",
          label: "轮播资源",
          value: `${this.carouselList.length || 0} 项`,
          desc: this.carouselList.length
            ? "首屏轮播已拿到数据，点我可直接回首页顶部核对展示。"
            : "当前还没拿到轮播数据，优先检查后台轮播启用状态。",
          actionText: this.carouselList.length
            ? "点我回顶部看轮播"
            : "点我重新加载轮播",
          tone: this.carouselList.length ? "safe" : "warning",
        },
        {
          key: "notice",
          label: "弹窗公告",
          value:
            (this.popupNotice &&
              (this.popupNotice.title || this.popupNotice.notice_id)) ||
            "未配置",
          desc:
            this.popupNotice && this.popupNotice.notice_id
              ? "首页弹窗公告已配置，点我可直接查看公告详情。"
              : "当前没有拿到弹窗公告，优先检查后台公告配置和弹窗开关。",
          actionText:
            this.popupNotice && this.popupNotice.notice_id
              ? "点我查看公告"
              : "点我重新检查公告",
          tone:
            this.popupNotice && this.popupNotice.notice_id ? "safe" : "warning",
        },
        {
          key: "news",
          label: "首页资讯",
          value: `${this.reviewArticleList.length || 0} 条`,
          desc: this.reviewArticleList.length
            ? "首页资讯已加载，点我可直接进入资讯列表继续核对。"
            : "当前资讯为空，优先检查后台内容分类和首页资讯来源。",
          actionText: this.reviewArticleList.length
            ? "点我查看资讯列表"
            : "点我重新拉资讯",
          tone: this.reviewArticleList.length ? "safe" : "warning",
        },
        {
          key: "content",
          label: this.isGoodsMode ? "首页商品流" : "首页商家流",
          value: `${this.list.length || 0} 条`,
          desc: this.list.length
            ? "首页主内容已加载，点我可直接重新拉取首页主列表。"
            : "首页主内容为空，建议重新加载并核对后台首页承接接口。",
          actionText: "点我刷新主内容",
          tone: this.list.length ? "safe" : "warning",
        },
      ];
    },
    homeRolloutHint() {
      if (this.currentEnvInfo.is_prod) {
        return "当前首页已经是正式入口，进入商品、商家和资讯都应按真实运营路径验证。";
      }
      if (this.envIsolationHealth.has_example_host) {
        return "当前环境仍是占位域名，只适合看页面结构和跳转形态，不能作为真实灰度入口。";
      }
      return "当前首页可作为测试或灰度入口继续验收，建议先核对首页列表、公告弹窗和详情跳转。";
    },
    homeRolloutChecklist() {
      return [
        { label: "当前环境", value: this.currentEnvInfo.label || "未配置" },
        { label: "首页主流", value: this.isGoodsMode ? "商品流" : "商家流" },
        { label: "内容数量", value: `${this.list.length || 0} 条` },
        { label: "发布阶段", value: this.envReleaseStageText },
      ];
    },
    homeRollbackHint() {
      if (this.currentEnvInfo.is_prod) {
        return "正式入口如发现异常，建议立即回退到旧入口或灰度入口，并暂停继续放量。";
      }
      return "灰度前建议保留旧 H5 入口，首页如发现异常可直接回切旧入口继续运营。";
    },
    homeOpsRiskText() {
      if (this.loadError) {
        return `当前首页存在加载异常：${this.loadError}`;
      }
      if (!this.list.length) {
        return "当前首页虽然可以打开，但运营内容为空，继续上线前需要先确认首页配置与接口数据。";
      }
      return this.currentEnvInfo.is_prod
        ? "当前首页已连接正式环境，商品、商家和资讯入口都应按真实运营内容核对。"
        : "当前首页更适合做联调验收，建议顺手检查商品流、商家流、资讯入口和公告弹窗是否都能回到正确页面。";
    },
    entryPanelTitle() {
      return this.currentEnvInfo.is_prod
        ? "当前是正式运营入口"
        : "当前是联调/灰度入口";
    },
    entryPanelBadge() {
      return this.currentEnvInfo.is_prod ? "真实写入" : "安全核对";
    },
    entryPanelDesc() {
      if (this.currentEnvInfo.key === "gray") {
        return "当前页面更适合做灰度验收，重点核对新页面、新链路和发布前回退能力。";
      }
      if (this.currentEnvInfo.is_prod) {
        return "浏览、下单、支付和售后都会连接正式数据，适合正式运营使用。";
      }
      return "当前入口更适合做页面联调、流程验收和发布前核对，默认应避免把测试行为带入正式环境。";
    },
    entryChecklist() {
      if (this.currentEnvInfo.is_prod) {
        return [
          { label: "浏览数据", value: "正式内容" },
          { label: "下单支付", value: "真实写入" },
          { label: "推荐用途", value: "正式运营" },
        ];
      }
      return [
        {
          label: "浏览数据",
          value:
            this.currentEnvInfo.key === "gray"
              ? "灰度内容核对"
              : "测试内容联调",
        },
        { label: "下单支付", value: "先做隔离验证" },
        {
          label: "推荐用途",
          value:
            this.currentEnvInfo.key === "gray"
              ? "灰度放量前检查"
              : "开发测试/提测",
        },
      ];
    },
    pageThemeCssVars() {
      const themeStyle = resolveUiThemeStyle(
        this.$store.state.setting || cache.get("setting") || {},
      );
      const palette =
        HOME_PAGE_THEME_PRESETS[themeStyle] || HOME_PAGE_THEME_PRESETS.origin;
      return buildCssVars({
        "--home-page-bg": palette.pageBg,
        "--home-review-bg": palette.reviewPageBg,
        "--home-review-hero-bg": palette.reviewHeroBg,
        "--home-review-card-bg": palette.reviewCardBg,
        "--home-review-item-bg": palette.reviewItemBg,
        "--home-review-accent": palette.reviewAccent,
        "--home-review-title": palette.reviewTitle,
        "--home-review-text": palette.reviewText,
        "--home-review-muted": palette.reviewMuted,
        "--home-header-bg": palette.headerBg,
        "--home-header-shadow": palette.headerShadow,
        "--home-header-text": palette.headerText,
        "--home-header-subtle": palette.headerSubtle,
        "--home-search-bg": palette.searchBg,
        "--home-search-border": palette.searchBorder,
        "--home-tab-bg": palette.tabBg,
        "--home-tab-border": palette.tabBorder,
        "--home-tab-text": palette.tabText,
        "--home-tab-active-bg": palette.tabActiveBg,
        "--home-tab-active-text": palette.tabActiveText,
        "--home-tab-active-shadow": palette.tabActiveShadow,
        "--home-banner-bg": palette.bannerBg,
        "--home-content-bg": palette.contentBg,
        "--home-content-border": palette.contentBorder,
        "--home-content-shadow": palette.contentShadow,
        "--home-section-title": palette.sectionTitle,
        "--home-section-tip": palette.sectionTip,
        "--home-item-bg": palette.itemBg,
        "--home-item-border": palette.itemBorder,
        "--home-item-shadow": palette.itemShadow,
        "--home-item-title": palette.itemTitle,
        "--home-item-text": palette.itemText,
        "--home-item-muted": palette.itemMuted,
        "--home-price": palette.price,
        "--home-accent": palette.accent,
      });
    },
    isGoodsMode() {
      return String(this.title || "").indexOf("商品") !== -1;
    },
    brandInitial() {
      const title = this.reviewInfo.system_name || "涛冠优选";
      return title ? title.slice(0, 1) : "涛";
    },
    primaryPhone() {
      const list = this.reviewInfo.service_phone_list || [];
      return list.length ? list[0] : "";
    },
    primaryWechat() {
      const list = this.reviewInfo.service_wechat_list || [];
      return list.length ? list[0] : "";
    },
    popupRenderedContent() {
      const content = this.popupNotice.content || "";
      return content.replace(
        /<img(.*?)style=\"(.*?)\"/g,
        '<img$1style="width: 100%;"',
      );
    },
    popupButtonText() {
      return this.popupNotice.popup_button_text || "查看详情";
    },
  },
  created() {
    this.initPage();
  },
  onShow() {
    if (this.skipNextShowRefresh) {
      this.skipNextShowRefresh = false;
      return;
    }
    this.updateRecentActionSummary(
      this.reviewMode
        ? "已返回首页服务模式，准备刷新平台设置、资讯入口和客服入口。"
        : "已返回首页商品模式，准备刷新首页内容与弹窗公告。",
    );
    this.loadRemoteSetting();
  },
  onHide() {
    this.clearPopupNoticeTimer();
  },
  onUnload() {
    this.clearPopupNoticeTimer();
  },
  onReachBottom() {
    if (this.reviewMode || this.isLoading || this.query.page >= this.pages) {
      return;
    }
    this.query.page += 1;
    this.updateRecentActionSummary(
      `准备加载首页第 ${this.query.page} 页内容。`,
    );
    this.getList();
  },
  methods: {
    clearPopupNoticeTimer() {
      if (!this.popupNoticeTimer) {
        return;
      }
      clearTimeout(this.popupNoticeTimer);
      this.popupNoticeTimer = null;
    },
    updateRecentActionSummary(summary) {
      this.recentActionSummary = summary;
    },
    resolveImageUrl(url, fallback = this.defaultImage) {
      return resolveSafeImageUrl(url, fallback);
    },
    merchantTitleText(value, fallback = "平台直营") {
      return maskMerchantTitle(value, fallback);
    },
    normalizeGoodsId(value) {
      const text = String(
        value === undefined || value === null ? "" : value,
      ).trim();
      if (!/^\d+$/.test(text)) {
        return 0;
      }
      return Number(text);
    },
    resolveGoodsId(item = {}) {
      const nestedGoods =
        item.goods && typeof item.goods === "object" ? item.goods : {};
      return this.normalizeGoodsId(
        item.id ||
          item.goods_id ||
          item.goodsId ||
          item.product_id ||
          nestedGoods.id ||
          nestedGoods.goods_id ||
          nestedGoods.goodsId,
      );
    },
    openGoodsDetail(item = {}) {
      const goodsId = this.resolveGoodsId(item);
      if (!goodsId) {
        this.updateRecentActionSummary("首页商品信息异常，无法进入详情页。");
        uni.showToast({
          icon: "none",
          title: "商品信息异常",
        });
        return;
      }
      this.updateRecentActionSummary(
        `准备查看商品详情：${item.title || goodsId}。`,
      );
      this.jump(`/pages/goods/details?goods_id=${goodsId}`);
    },
    initPage() {
      this.updateRecentActionSummary(
        this.reviewMode
          ? "正在初始化首页服务模式。"
          : "正在初始化首页商品模式。",
      );
      this.applySetting(
        this.$store.state.setting || cache.get("setting") || {},
      );
      if (this.reviewMode) {
        this.loadReviewContent();
      } else {
        this.loadHomeNewsDigest();
        this.getCarouselList();
        this.getList();
      }
    },
    loadRemoteSetting() {
      api
        .getSetting()
        .then((res) => {
          const data = res.data || {};
          cache.set("setting", data);
          store.commit("setSetting", data);
          applyUiThemeFromSetting(data);
          const previousReviewMode = this.reviewMode;
          this.applySetting(data);
          if (this.reviewMode) {
            this.updateRecentActionSummary(
              "首页设置刷新完成，当前为服务模式。",
            );
            this.loadReviewContent();
            return;
          }

          this.getCarouselList();
          this.loadHomeNewsDigest();
          if (previousReviewMode) {
            this.query.page = 1;
            this.list = [];
          }
          this.updateRecentActionSummary("首页设置刷新完成，当前为商品模式。");
          this.getList();
        })
        .catch(() => {
          this.updateRecentActionSummary(
            "首页设置刷新失败，继续保留当前本地展示配置。",
          );
        });
    },
    applySetting(setting) {
      const system = setting.system || {};
      const reviewFlag =
        Number(
          system.audit_mode ?? system.review_mode ?? system.wx_approved ?? 0,
        ) === 1;
      this.reviewMode = !!(reviewFlag && !this.currentEnvInfo.is_prod);
      this.reviewInfo = {
        system_name: system.system_name || "涛冠优选",
        logo_url: system.logo_url || "",
        member_website: system.member_website || "",
        service_phone_list: system.service_phone_list || [],
        service_qq_list: system.service_qq_list || [],
        service_wechat_list: system.service_wechat_list || [],
        service_wechat_image_url: system.service_wechat_image_url || "",
        review_hero_title:
          system.review_hero_title || "平台服务清晰可见，合作沟通便捷在线",
        review_hero_desc:
          system.review_hero_desc ||
          "平台聚焦农产品合作咨询、客户联系支持与官方资讯展示，提供稳定的服务入口和持续更新的官方内容。",
        review_intro_title: system.review_intro_title || "品牌介绍",
        review_intro_desc:
          system.review_intro_desc ||
          this.buildDefaultIntro(system.system_name || "涛冠优选"),
        review_primary_btn_text: system.review_primary_btn_text || "联系客服",
        review_secondary_btn_text:
          system.review_secondary_btn_text || "查看资讯",
        review_intro_image_url: system.review_intro_image_url || "",
      };
      this.syncTabBar();
    },
    ensurePopupClientKey() {
      if (this.popupClientKey) {
        return this.popupClientKey;
      }
      let clientKey = cache.get("popup_notice_client_key");
      if (!clientKey) {
        clientKey = `popup_${Date.now()}_${Math.random().toString(36).slice(2, 10)}`;
        cache.set("popup_notice_client_key", clientKey, 31536000);
      }
      this.popupClientKey = clientKey;
      return clientKey;
    },
    schedulePopupNoticeLoad(delay = 1400) {
      this.clearPopupNoticeTimer();
      this.popupNoticeTimer = setTimeout(() => {
        this.popupNoticeTimer = null;
        this.loadPopupNotice();
      }, delay);
    },
    loadPopupNotice() {
      const clientKey = this.ensurePopupClientKey();
      api
        .getPopupNotice({ client_key: clientKey })
        .then((res) => {
          const notice = res.data || {};
          this.popupNotice = notice;
          this.popupVisible = !!notice.notice_id;
          if (notice.notice_id) {
            this.updateRecentActionSummary(
              `首页弹窗公告已加载：${notice.title || notice.notice_id}。`,
            );
          }
        })
        .catch(() => {
          this.updateRecentActionSummary(
            "首页弹窗公告加载失败，已跳过弹窗承接。",
          );
        });
    },
    markPopupNotice(readType) {
      if (!this.popupNotice.notice_id) {
        return Promise.resolve();
      }
      return api
        .readPopupNotice({
          notice_id: this.popupNotice.notice_id,
          client_key: this.ensurePopupClientKey(),
          read_type: readType || "popup_close",
        })
        .catch(() => {});
    },
    closePopup(readType) {
      if (!this.popupVisible) {
        return;
      }
      this.popupVisible = false;
      this.updateRecentActionSummary("已关闭首页弹窗公告。");
      this.markPopupNotice(readType || "popup_close");
    },
    openPopupNotice() {
      const jumpType = this.popupNotice.popup_jump_type || "detail";
      const jumpValue = this.popupNotice.popup_jump_value || "";
      this.popupVisible = false;
      this.updateRecentActionSummary(
        `准备查看首页弹窗公告：${this.popupNotice.title || this.popupNotice.notice_id}。`,
      );
      this.markPopupNotice("popup_view");
      if (jumpType === "none") {
        return;
      }
      if (jumpType === "url" && jumpValue) {
        /* #ifdef H5 */
        window.location.href = jumpValue;
        return;
        /* #endif */
        uni.setClipboardData({
          data: jumpValue,
          success: () => {
            uni.showToast({
              icon: "none",
              title: "链接已复制",
            });
          },
        });
        return;
      }
      if (jumpType === "mini_page" && jumpValue) {
        uni.navigateTo({
          url: jumpValue,
        });
        return;
      }
      uni.navigateTo({
        url: `/pages/notice/detail?notice_id=${this.popupNotice.notice_id}`,
      });
    },
    buildDefaultIntro(systemName) {
      return `${systemName} 以平台服务展示、合作咨询、客户联系与资讯发布为核心，围绕农产品合作与官方支持建立统一、清晰、可联系的线上服务入口。`;
    },
    syncTabBar() {
      /* #ifdef MP-WEIXIN */
      if (this.reviewMode) {
        uni.hideTabBar();
      } else {
        uni.showTabBar();
      }
      /* #endif */
    },
    getCarouselList() {
      api
        .getCarouselList({})
        .then((res) => {
          this.carouselList = (res.data && res.data.list) || [];
        })
        .catch(() => {
          this.carouselList = [];
        });
    },
    getList() {
      this.isLoading = true;
      this.updateRecentActionSummary(
        this.query.page > 1
          ? `正在加载首页第 ${this.query.page} 页内容。`
          : "正在加载首页内容列表。",
      );
      api
        .merchantList(this.query)
        .then((res) => {
          const data = res.data || {};
          const list = data.list || [];
          this.list = this.query.page === 1 ? list : this.list.concat(list);
          this.count = data.count || 0;
          this.pages = data.pages || 0;
          this.title = data.title || "";
          this.loadError = "";
          this.updateRecentActionSummary(
            this.query.page > 1
              ? `首页第 ${this.query.page} 页内容加载完成，当前累计 ${this.list.length} 条。`
              : `首页内容加载完成，当前共有 ${this.list.length} 条。`,
          );
          if (this.query.page === 1) {
            this.schedulePopupNoticeLoad();
          }
        })
        .catch(() => {
          if (this.query.page === 1) {
            this.list = [];
            this.count = 0;
            this.pages = 0;
            this.title = "";
            this.loadError = "首页内容暂时无法加载，请稍后重试";
          }
          this.updateRecentActionSummary(
            "首页内容加载失败，请确认当前环境接口和首页配置。",
          );
          if (this.query.page === 1) {
            this.schedulePopupNoticeLoad();
          }
        })
        .finally(() => {
          this.isLoading = false;
        });
    },
    retryLoad() {
      this.updateRecentActionSummary("准备重新加载首页内容。");
      this.loadError = "";
      this.query.page = 1;
      this.list = [];
      this.loadHomeNewsDigest();
      this.getCarouselList();
      this.getList();
    },
    reloadHomeContent() {
      this.updateRecentActionSummary("准备刷新首页轮播、资讯和主内容。");
      this.query.page = 1;
      this.list = [];
      this.loadError = "";
      this.loadHomeNewsDigest();
      this.getCarouselList();
      this.getList();
    },
    loadHomeNewsDigest() {
      api
        .getContentList({ page: 1, limit: 3, category_id: 2 })
        .then((res) => {
          this.reviewArticleList = (res.data && res.data.list) || [];
        })
        .catch(() => {
          this.reviewArticleList = [];
        });
    },
    loadReviewContent() {
      api
        .getContentList({ page: 1, limit: 3, category_id: 2 })
        .then((articleRes) => {
          this.reviewArticleList =
            (articleRes.data && articleRes.data.list) || [];
          this.updateRecentActionSummary(
            this.reviewArticleList.length
              ? `首页服务资讯加载完成，当前展示 ${this.reviewArticleList.length} 条。`
              : "首页服务资讯加载完成，但当前没有可展示内容。",
          );
          this.schedulePopupNoticeLoad();
        })
        .catch(() => {
          this.reviewArticleList = [];
          this.updateRecentActionSummary(
            "首页服务资讯加载失败，请确认当前环境接口和内容配置。",
          );
          this.schedulePopupNoticeLoad();
        });
    },
    tabSelect(value) {
      this.currentTab = value;
      this.updateRecentActionSummary(
        `已切换首页标签：${value === "discover" ? "发现好物" : "首页"}。`,
      );
      if (value === "discover") {
        uni.reLaunch({
          url: "/pages/app/sell",
        });
      }
    },
    searchTap() {
      this.updateRecentActionSummary("准备进入首页搜索中心。");
      uni.navigateTo({
        url: "/pages/home/search",
      });
    },
    listTap(item) {
      this.updateRecentActionSummary(
        `准备查看商家商品：${item.title || item.id || "未命名商家"}。`,
      );
      uni.navigateTo({
        url: "/pages/goods/list?merchant_id=" + item.id,
      });
    },
    jump(url) {
      this.updateRecentActionSummary(`准备打开页面：${url}。`);
      uni.navigateTo({
        url,
      });
    },
    openAppHomeTop() {
      this.updateRecentActionSummary("准备回到首页顶部查看首屏轮播。");
      uni.pageScrollTo({
        scrollTop: 0,
        duration: 200,
      });
    },
    handleHomeSourceAction(key) {
      if (key === "carousel") {
        if (!this.carouselList.length) {
          this.reloadHomeContent();
          return;
        }
        this.openAppHomeTop();
        return;
      }
      if (key === "notice") {
        if (this.popupNotice && this.popupNotice.notice_id) {
          this.openPopupNotice();
          return;
        }
        this.loadPopupNotice();
        return;
      }
      if (key === "news") {
        if (this.reviewArticleList.length) {
          this.openNewsList();
          return;
        }
        this.loadHomeNewsDigest();
        return;
      }
      this.reloadHomeContent();
    },
    toServicePage() {
      this.updateRecentActionSummary("准备进入客服中心。");
      uni.navigateTo({
        url: "/pages/help/service",
      });
    },
    openNewsList() {
      this.updateRecentActionSummary("准备查看平台资讯列表。");
      uni.navigateTo({
        url: "/pages/news/list?category_id=2",
      });
    },
    openArticle(item) {
      if (!item || !item.content_id) {
        this.updateRecentActionSummary("资讯内容异常，无法进入详情页。");
        return;
      }
      this.updateRecentActionSummary(
        `准备查看首页资讯详情：${item.title || item.content_id}。`,
      );
      uni.navigateTo({
        url: "/pages/news/details?id=" + item.content_id,
      });
    },
    handleReviewAction(action) {
      switch (action) {
        case "service":
          this.toServicePage();
          break;
        case "phone":
          if (this.primaryPhone) {
            this.callPhone(this.primaryPhone);
          } else {
            this.toServicePage();
          }
          break;
        case "news":
          this.openNewsList();
          break;
        case "wechat":
          if (this.primaryWechat) {
            this.copyText(this.primaryWechat, "客服微信已复制");
          } else {
            this.toServicePage();
          }
          break;
        default:
          this.toServicePage();
      }
    },
    copyWebsite() {
      if (!this.reviewInfo.member_website) {
        this.updateRecentActionSummary("官网地址未配置，无法复制。");
        uni.showToast({
          title: "后台还未配置官网地址",
          icon: "none",
        });
        return;
      }
      this.updateRecentActionSummary("准备复制平台官网地址。");
      this.copyText(this.reviewInfo.member_website, "官网地址已复制");
    },
    copyText(text, successText) {
      if (!text) {
        return;
      }
      this.updateRecentActionSummary(`准备复制内容：${text}。`);
      uni.setClipboardData({
        data: text,
        success: () => {
          uni.showToast({
            title: successText || "复制成功",
            icon: "none",
          });
        },
      });
    },
    callPhone(phone) {
      if (!phone) {
        return;
      }
      this.updateRecentActionSummary(`准备联系平台电话：${phone}。`);
      uni.makePhoneCall({
        phoneNumber: phone,
        fail: () => {
          this.updateRecentActionSummary(
            `拨号失败，已改为复制平台电话：${phone}。`,
          );
          this.copyText(phone, "联系电话已复制");
        },
      });
    },
    formatArticleTime(value) {
      return value ? String(value).slice(0, 10) : "--";
    },
  },
};
</script>

<style scoped lang="scss">
.home-page {
  min-height: 100vh;
  background: var(--home-page-bg, #f4f7fb);
}

.popup-mask {
  position: fixed;
  inset: 0;
  z-index: 999;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 32rpx;
  background: rgba(12, 18, 28, 0.55);
}

.popup-card {
  width: 100%;
  max-height: 82vh;
  overflow: hidden;
  border-radius: 30rpx;
  background: #ffffff;
  box-shadow: 0 24rpx 60rpx rgba(10, 21, 32, 0.18);
  position: relative;
}

.popup-close {
  position: absolute;
  right: 22rpx;
  top: 18rpx;
  z-index: 2;
  width: 56rpx;
  height: 56rpx;
  border-radius: 50%;
  background: rgba(12, 18, 28, 0.48);
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 36rpx;
}

.popup-image {
  width: 100%;
  height: 300rpx;
}

.popup-body {
  padding: 28rpx;
}

.popup-title {
  font-size: 38rpx;
  font-weight: 700;
  line-height: 1.4;
}

.popup-desc {
  margin-top: 14rpx;
  color: #5f6d7a;
  font-size: 26rpx;
  line-height: 1.7;
}

.popup-scroll {
  max-height: 420rpx;
  margin-top: 18rpx;
}

.popup-html {
  color: #243142;
  font-size: 28rpx;
  line-height: 1.8;
}

.popup-html img {
  width: 100% !important;
  height: auto !important;
}

.popup-html video {
  width: 100% !important;
}

.popup-html pre {
  padding: 20rpx;
  border-radius: 18rpx;
  background: #0f1720;
  color: #f8fafc;
  overflow-x: auto;
  white-space: pre-wrap;
  word-break: break-all;
}

.popup-actions {
  display: flex;
  gap: 18rpx;
  margin-top: 24rpx;
}

.popup-btn {
  flex: 1;
  height: 84rpx;
  line-height: 84rpx;
  border-radius: 999rpx;
  font-size: 28rpx;
  font-weight: 600;
}

.popup-btn.ghost {
  color: #4b5b6d;
  background: #edf2f6;
}

.popup-btn.primary {
  color: #ffffff;
  background: linear-gradient(90deg, #184f78 0%, #eb8b59 100%);
}

.review-page {
  min-height: 100vh;
  padding-bottom: 40rpx;
  background: var(
    --home-review-bg,
    linear-gradient(180deg, #f7fbff 0%, #f1f5fa 100%)
  );
}

.review-hero {
  padding: calc(var(--status-bar-height) + 26rpx) 24rpx 0;
  background: var(
    --home-review-hero-bg,
    linear-gradient(135deg, #184f78 0%, #2a6f93 48%, #eb8b59 100%)
  );
  border-bottom-left-radius: 40rpx;
  border-bottom-right-radius: 40rpx;
}

.review-topbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.brand-badge,
.status-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 10rpx 18rpx;
  border-radius: 999rpx;
  font-size: 22rpx;
  color: #fff;
  background: rgba(255, 255, 255, 0.16);
}

.review-brand {
  display: flex;
  align-items: center;
  gap: 22rpx;
  margin-top: 34rpx;
}

.brand-logo {
  width: 124rpx;
  height: 124rpx;
  border-radius: 30rpx;
  background: rgba(255, 255, 255, 0.18);
}

.brand-fallback {
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 46rpx;
  font-weight: 700;
  color: #fff;
}

.brand-copy {
  flex: 1;
  color: #fff;
}

.brand-title {
  font-size: 54rpx;
  font-weight: 700;
}

.brand-subtitle {
  margin-top: 10rpx;
  font-size: 28rpx;
  opacity: 0.92;
}

.review-card {
  background: var(--home-review-card-bg, rgba(255, 255, 255, 0.96));
  border-radius: 30rpx;
  box-shadow: var(--home-content-shadow, 0 18rpx 42rpx rgba(17, 34, 51, 0.08));
  border: 1rpx solid var(--home-content-border, transparent);
}

.hero-copy {
  margin-top: 28rpx;
  padding: 32rpx;
  color: #fff;
  background:
    linear-gradient(
      135deg,
      rgba(255, 255, 255, 0.12),
      rgba(255, 255, 255, 0.08)
    ),
    rgba(255, 255, 255, 0.1);
  box-shadow: none;
}

.hero-heading {
  font-size: 54rpx;
  font-weight: 700;
  line-height: 1.24;
}

.hero-desc {
  margin-top: 18rpx;
  font-size: 30rpx;
  line-height: 1.7;
  opacity: 0.96;
}

.hero-actions {
  display: flex;
  gap: 20rpx;
  margin-top: 28rpx;
}

.action-btn {
  height: 84rpx;
  padding: 0 34rpx;
  line-height: 84rpx;
  border-radius: 999rpx;
  font-size: 30rpx;
  font-weight: 600;
}

.action-btn.primary {
  color: #184f78;
  background: #ffffff;
}

.action-btn.ghost {
  color: #ffffff;
  background: transparent;
  border: 2rpx solid rgba(255, 255, 255, 0.36);
}

.review-content {
  padding: 24rpx;
}

.review-content .review-card + .review-card {
  margin-top: 24rpx;
}

.section-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20rpx;
}

.section-title {
  font-size: 42rpx;
  font-weight: 700;
  color: var(--home-review-title, #0f2538);
}

.section-tip,
.section-link {
  font-size: 24rpx;
  color: var(--home-review-muted, #6d7a87);
}

.section-link {
  color: var(--home-review-accent, #2a6f93);
}

.service-grid,
.feature-grid,
.category-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 20rpx;
}

.review-card {
  padding: 30rpx;
}

.service-item,
.feature-item,
.category-item,
.status-item {
  padding: 24rpx;
  border-radius: 24rpx;
  background: var(--home-review-item-bg, #f5f8fc);
}

.service-icon,
.feature-icon {
  width: 68rpx;
  height: 68rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 20rpx;
  background: var(--home-review-accent, #184f78);
  color: #fff;
  font-size: 34rpx;
  font-weight: 700;
}

.service-title,
.feature-title,
.category-title,
.scene-title,
.step-title,
.article-title,
.contact-title {
  margin-top: 18rpx;
  font-size: 32rpx;
  font-weight: 700;
  color: var(--home-review-title, #10283d);
}

.service-desc,
.feature-text,
.category-desc,
.scene-desc,
.step-desc,
.article-text,
.intro-text,
.wechat-tip {
  margin-top: 12rpx;
  font-size: 26rpx;
  line-height: 1.7;
  color: var(--home-review-text, #607181);
}

.category-grid,
.scene-list,
.article-list,
.step-list,
.contact-list {
  margin-top: 22rpx;
}

.scene-item,
.article-item,
.contact-item,
.step-item {
  display: flex;
  gap: 18rpx;
  padding: 24rpx 0;
}

.scene-item + .scene-item,
.article-item + .article-item,
.contact-item + .contact-item,
.step-item + .step-item {
  border-top: 1rpx solid #edf1f5;
}

.category-tag {
  width: 68rpx;
  height: 68rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 20rpx;
  background: rgba(24, 79, 120, 0.12);
  color: #184f78;
  font-size: 34rpx;
  font-weight: 700;
}

.article-main,
.step-body {
  flex: 1;
  min-width: 0;
}

.category-title,
.scene-title {
  margin-top: 16rpx;
}

.scene-item {
  align-items: flex-start;
}

.article-item {
  align-items: center;
}

.article-title {
  margin-top: 0;
  font-size: 30rpx;
}

.article-text {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.article-time {
  font-size: 22rpx;
  color: var(--home-review-muted, #97a3af);
}

.step-index {
  width: 56rpx;
  height: 56rpx;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 999rpx;
  background: var(--home-review-accent, #184f78);
  color: #fff;
  font-size: 28rpx;
  font-weight: 700;
}

.contact-item {
  justify-content: space-between;
  align-items: center;
}

.contact-label {
  font-size: 28rpx;
  color: var(--home-review-text, #607181);
}

.contact-value {
  font-size: 28rpx;
  color: var(--home-review-title, #10283d);
  font-weight: 600;
  text-align: right;
}

.intro-image,
.wechat-image {
  width: 100%;
  border-radius: 24rpx;
  margin-top: 22rpx;
}

.intro-image {
  height: 320rpx;
}

.wechat-card {
  margin-top: 16rpx;
  padding-top: 10rpx;
}

.wechat-image {
  height: 340rpx;
  background: var(--home-review-item-bg, #eef3f8);
}

.wechat-tip {
  text-align: center;
}

.default-page {
  padding-bottom: 24rpx;
}

.home-header {
  padding: calc(var(--status-bar-height) + 24rpx) 24rpx 28rpx;
  background: var(
    --home-header-bg,
    linear-gradient(180deg, #f2076a 0%, #ff216a 58%, #ff4c7e 100%)
  );
  border-bottom-left-radius: 34rpx;
  border-bottom-right-radius: 34rpx;
  box-shadow: var(--home-header-shadow, none);
}

.header-copy {
  color: var(--home-header-text, #fff);
}

.header-title {
  font-size: 44rpx;
  font-weight: 700;
}

.header-subtitle {
  margin-top: 10rpx;
  font-size: 26rpx;
  color: var(--home-header-subtle, rgba(255, 255, 255, 0.9));
}

.search-box {
  margin-top: 24rpx;
  padding: 0 24rpx;
  height: 82rpx;
  display: flex;
  align-items: center;
  border-radius: 999rpx;
  background: var(--home-search-bg, rgba(255, 255, 255, 0.16));
  color: var(--home-header-text, #fff);
  border: 1rpx solid var(--home-search-border, transparent);
}

.search-placeholder {
  margin-left: 12rpx;
  font-size: 28rpx;
  color: var(--home-header-text, #fff);
}

.tab-row {
  display: flex;
  gap: 18rpx;
  margin-top: 22rpx;
}

.tab-pill {
  min-width: 150rpx;
  height: 66rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 999rpx;
  font-size: 26rpx;
  color: var(--home-tab-text, rgba(255, 255, 255, 0.82));
  background: var(--home-tab-bg, rgba(255, 255, 255, 0.14));
  border: 1rpx solid var(--home-tab-border, transparent);
}

.tab-pill.active {
  color: var(--home-tab-active-text, #d93761);
  background: var(--home-tab-active-bg, #fff);
  box-shadow: var(--home-tab-active-shadow, none);
}

.env-banner {
  margin: 24rpx 24rpx 0;
  padding: 22rpx 24rpx;
  border-radius: 26rpx;
  background: rgba(255, 255, 255, 0.92);
  box-shadow: 0 12rpx 26rpx rgba(17, 34, 51, 0.06);
}

.env-banner__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16rpx;
}

.env-banner__title {
  font-size: 28rpx;
  font-weight: 600;
  color: #10283d;
}

.env-banner__badge {
  min-width: 124rpx;
  padding: 8rpx 18rpx;
  border-radius: 999rpx;
  text-align: center;
  font-size: 22rpx;
  font-weight: 600;
}

.env-banner__badge.is-prod {
  color: #a53b12;
  background: rgba(255, 227, 214, 0.92);
}

.env-banner__badge.is-test {
  color: #155e75;
  background: rgba(220, 246, 255, 0.92);
}

.env-banner__desc {
  margin-top: 12rpx;
  font-size: 24rpx;
  line-height: 1.6;
  color: #5d6b78;
}

.env-banner__meta {
  display: flex;
  flex-wrap: wrap;
  gap: 10rpx;
  margin-top: 10rpx;
}

.env-banner__tag {
  display: inline-flex;
  align-items: center;
  min-height: 38rpx;
  padding: 0 16rpx;
  border-radius: 999rpx;
  background: rgba(21, 75, 114, 0.08);
  color: #4f6275;
  font-size: 20rpx;
}

.env-banner__note {
  margin-top: 10rpx;
  font-size: 22rpx;
  line-height: 1.7;
  color: #6b7b8c;
}

.env-banner__note--strong {
  padding: 14rpx 16rpx;
  border-radius: 18rpx;
  background: rgba(21, 75, 114, 0.06);
  color: #35506b;
}

.env-banner__risk-list {
  margin-top: 12rpx;
  padding: 14rpx 16rpx;
  border-radius: 18rpx;
  background: rgba(255, 244, 232, 0.98);
}

.env-banner__risk-item {
  color: #8c5145;
  font-size: 21rpx;
  line-height: 1.7;
}

.env-banner__risk-item + .env-banner__risk-item {
  margin-top: 8rpx;
}

.env-banner__url {
  margin-top: 10rpx;
  font-size: 20rpx;
  line-height: 1.6;
  color: #8694a2;
  word-break: break-all;
}

.env-profile-board,
.rollout-panel {
  margin: 20rpx 24rpx 0;
  padding: 24rpx;
  border-radius: 26rpx;
  background: rgba(255, 255, 255, 0.92);
  box-shadow: 0 12rpx 26rpx rgba(17, 34, 51, 0.06);
}

.env-profile-board {
  margin: 14rpx 0 0;
  padding: 16rpx 18rpx;
  border-radius: 20rpx;
  background: rgba(21, 75, 114, 0.04);
  box-shadow: none;
}

.env-profile-board__title,
.rollout-panel__title {
  font-size: 24rpx;
  font-weight: 700;
  color: #10283d;
}

.env-profile-board__list,
.rollout-panel__list {
  display: flex;
  flex-direction: column;
  gap: 12rpx;
  margin-top: 14rpx;
}

.env-profile-board__item,
.rollout-panel__item {
  padding: 16rpx 18rpx;
  border-radius: 18rpx;
  background: rgba(255, 255, 255, 0.92);
}

.env-profile-board__item.is-current {
  border: 1rpx solid rgba(0, 129, 255, 0.22);
}

.env-profile-board__item-head,
.rollout-panel__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12rpx;
}

.env-profile-board__item-name,
.rollout-panel__item-label {
  font-size: 22rpx;
  color: #23405d;
  font-weight: 600;
}

.env-profile-board__item-status,
.rollout-panel__badge {
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
.rollout-panel__badge.is-prod {
  background: linear-gradient(45deg, #ef4444, #f97316);
}

.env-profile-board__item-desc,
.rollout-panel__desc,
.rollout-panel__risk,
.rollout-panel__item-value {
  margin-top: 8rpx;
  font-size: 21rpx;
  line-height: 1.7;
  color: #6b7b8c;
}

.rollout-panel__item-value {
  display: block;
  color: #1c425f;
  font-weight: 600;
}

.rollout-panel__risk {
  margin-top: 16rpx;
  padding: 18rpx 20rpx;
  border-radius: 20rpx;
  background: rgba(255, 247, 237, 0.96);
  color: #9a3412;
}

.recent-action-card {
  margin: 20rpx 24rpx 0;
  padding: 24rpx;
  border-radius: 26rpx;
  background: rgba(255, 255, 255, 0.92);
  box-shadow: 0 12rpx 26rpx rgba(17, 34, 51, 0.06);
}

.recent-action-card__title {
  font-size: 28rpx;
  font-weight: 600;
  color: #10283d;
}

.recent-action-card__desc {
  margin-top: 12rpx;
  font-size: 22rpx;
  line-height: 1.7;
  color: #6b7b8c;
}

.ops-panel {
  margin: 20rpx 24rpx 0;
  padding: 24rpx;
  border-radius: 26rpx;
  background: rgba(255, 255, 255, 0.94);
  box-shadow: 0 12rpx 26rpx rgba(17, 34, 51, 0.06);
}

.ops-panel__head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16rpx;
}

.ops-panel__title {
  font-size: 28rpx;
  font-weight: 600;
  color: #10283d;
}

.ops-panel__desc {
  margin-top: 10rpx;
  font-size: 22rpx;
  line-height: 1.7;
  color: #6b7b8c;
}

.ops-panel__badge {
  min-width: 128rpx;
  padding: 8rpx 18rpx;
  border-radius: 999rpx;
  text-align: center;
  font-size: 22rpx;
  font-weight: 600;
  color: #155e75;
  background: rgba(220, 246, 255, 0.92);
}

.ops-panel__badge.is-safe {
  color: #166534;
  background: rgba(220, 252, 231, 0.92);
}

.ops-panel__badge.is-warning {
  color: #9a3412;
  background: rgba(255, 237, 213, 0.96);
}

.ops-panel__badge.is-danger {
  color: #b91c1c;
  background: rgba(254, 226, 226, 0.96);
}

.ops-panel__meta {
  display: flex;
  flex-wrap: wrap;
  gap: 12rpx;
  margin-top: 14rpx;
}

.ops-panel__tag {
  display: inline-flex;
  align-items: center;
  min-height: 40rpx;
  padding: 0 16rpx;
  border-radius: 999rpx;
  background: rgba(21, 75, 114, 0.08);
  color: #1c425f;
  font-size: 21rpx;
}

.ops-panel__risk {
  margin-top: 14rpx;
  padding: 18rpx 20rpx;
  border-radius: 20rpx;
  background: rgba(255, 247, 237, 0.96);
  color: #9a3412;
  font-size: 22rpx;
  line-height: 1.65;
}

.ops-panel__actions {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12rpx;
  margin-top: 16rpx;
}

.ops-panel__action {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 76rpx;
  border-radius: 20rpx;
  background: rgba(21, 75, 114, 0.08);
  color: #1c425f;
  font-size: 23rpx;
  font-weight: 600;
}

.source-panel {
  margin: 24rpx;
  padding: 28rpx;
  border-radius: 30rpx;
  background: var(--home-content-bg, #fff);
  box-shadow: var(--home-content-shadow, 0 16rpx 40rpx rgba(17, 34, 51, 0.05));
  border: 1rpx solid var(--home-content-border, transparent);
}

.source-panel__head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 20rpx;
}

.source-panel__title {
  font-size: 30rpx;
  font-weight: 700;
  color: var(--home-section-title, #10283d);
}

.source-panel__desc {
  margin-top: 10rpx;
  font-size: 24rpx;
  line-height: 1.7;
  color: var(--home-section-tip, #8a97a4);
}

.source-panel__badge {
  flex-shrink: 0;
  padding: 10rpx 18rpx;
  border-radius: 999rpx;
  font-size: 22rpx;
  font-weight: 700;
}

.source-panel__badge.is-safe {
  background: rgba(20, 126, 92, 0.12);
  color: #147e5c;
}

.source-panel__badge.is-warning {
  background: rgba(217, 119, 6, 0.12);
  color: #b45309;
}

.source-panel__badge.is-danger {
  background: rgba(220, 38, 38, 0.12);
  color: #b91c1c;
}

.source-panel__grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 18rpx;
  margin-top: 24rpx;
}

.source-card {
  padding: 22rpx;
  border-radius: 24rpx;
  background: var(--home-item-bg, #f8fafc);
  border: 1rpx solid var(--home-item-border, transparent);
}

.source-card.is-safe {
  background: rgba(20, 126, 92, 0.08);
}

.source-card.is-warning {
  background: rgba(245, 158, 11, 0.08);
}

.source-card__label {
  font-size: 24rpx;
  color: var(--home-item-muted, #6d7a87);
}

.source-card__value {
  margin-top: 10rpx;
  font-size: 30rpx;
  font-weight: 700;
  color: var(--home-item-title, #10283d);
  word-break: break-all;
}

.source-card__desc {
  margin-top: 12rpx;
  font-size: 22rpx;
  line-height: 1.7;
  color: var(--home-item-text, #6d7a87);
}

.source-card__action {
  margin-top: 16rpx;
  font-size: 22rpx;
  font-weight: 700;
  color: var(--home-accent, #2a6f93);
}

.banner-empty-card {
  padding: 40rpx 32rpx;
  text-align: center;
  background: var(--home-content-bg, #fff);
  border-radius: 30rpx;
  border: 1rpx dashed var(--home-content-border, rgba(42, 111, 147, 0.2));
}

.banner-empty-card__title {
  font-size: 30rpx;
  font-weight: 700;
  color: var(--home-section-title, #10283d);
}

.banner-empty-card__desc {
  margin-top: 14rpx;
  font-size: 24rpx;
  line-height: 1.7;
  color: var(--home-section-tip, #8a97a4);
}

.banner-empty-card__actions {
  margin-top: 22rpx;
  display: flex;
  justify-content: center;
}

.entry-panel {
  margin: 20rpx 24rpx 0;
  padding: 24rpx;
  border-radius: 26rpx;
  background: rgba(255, 255, 255, 0.92);
  box-shadow: 0 12rpx 26rpx rgba(17, 34, 51, 0.06);
}

.entry-panel__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16rpx;
}

.entry-panel__title {
  font-size: 28rpx;
  font-weight: 600;
  color: #10283d;
}

.entry-panel__badge {
  min-width: 124rpx;
  padding: 8rpx 18rpx;
  border-radius: 999rpx;
  text-align: center;
  font-size: 22rpx;
  font-weight: 600;
  color: #155e75;
  background: rgba(220, 246, 255, 0.92);
}

.entry-panel__badge.is-prod {
  color: #a53b12;
  background: rgba(255, 227, 214, 0.92);
}

.entry-panel__desc {
  margin-top: 12rpx;
  font-size: 22rpx;
  line-height: 1.7;
  color: #6b7b8c;
}

.entry-panel__list {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12rpx;
  margin-top: 14rpx;
}

.entry-panel__item {
  padding: 18rpx 16rpx;
  border-radius: 20rpx;
  background: rgba(21, 75, 114, 0.08);
}

.entry-panel__item-label {
  display: block;
  font-size: 20rpx;
  color: #7b8794;
}

.entry-panel__item-value {
  display: block;
  margin-top: 10rpx;
  font-size: 23rpx;
  font-weight: 600;
  line-height: 1.5;
  color: #1c425f;
}

.banner-wrap {
  margin: 24rpx 24rpx 0;
  background: var(--home-banner-bg, transparent);
  border-radius: 30rpx;
}

.banner-swiper,
.banner-image {
  width: 100%;
  height: 280rpx;
  border-radius: 30rpx;
}

.content-section {
  margin: 24rpx;
  padding: 28rpx;
  border-radius: 30rpx;
  background: var(--home-content-bg, #fff);
  box-shadow: var(--home-content-shadow, 0 16rpx 40rpx rgba(17, 34, 51, 0.05));
  border: 1rpx solid var(--home-content-border, transparent);
}

.goods-grid,
.merchant-list {
  margin-top: 24rpx;
}

.goods-card,
.merchant-card {
  display: flex;
  gap: 20rpx;
  padding: 22rpx 0;
  background: var(--home-item-bg, transparent);
  border: 1rpx solid var(--home-item-border, transparent);
  box-shadow: var(--home-item-shadow, none);
  border-radius: 26rpx;
}

.goods-card + .goods-card,
.merchant-card + .merchant-card {
  border-top: none;
  margin-top: 18rpx;
}

.goods-image,
.merchant-avatar {
  width: 156rpx;
  height: 156rpx;
  border-radius: 24rpx;
  background: var(--home-content-bg, #f1f5f9);
}

.goods-body,
.merchant-body {
  flex: 1;
  min-width: 0;
}

.goods-title,
.merchant-title {
  font-size: 30rpx;
  font-weight: 700;
  color: var(--home-item-title, #10283d);
}

.goods-spec,
.merchant-desc {
  margin-top: 12rpx;
  font-size: 24rpx;
  color: var(--home-item-text, #6d7a87);
  line-height: 1.7;
}

.goods-meta {
  display: flex;
  flex-direction: column;
  gap: 8rpx;
  margin-top: 14rpx;
}

.goods-price {
  font-size: 32rpx;
  font-weight: 700;
  color: var(--home-price, #e9653a);
}

.goods-sales,
.goods-merchant {
  font-size: 24rpx;
  color: var(--home-item-muted, #6d7a87);
}

.merchant-arrow {
  align-self: center;
  color: var(--home-accent, #2a6f93);
  font-size: 26rpx;
}

.empty-box,
.loading-box {
  padding: 40rpx 0 16rpx;
  text-align: center;
}

.empty-title {
  font-size: 30rpx;
  font-weight: 700;
  color: var(--home-section-title, #10283d);
}

.empty-text,
.loading-box {
  font-size: 24rpx;
  color: var(--home-section-tip, #8a97a4);
}

.empty-action {
  margin-top: 20rpx;
  min-width: 220rpx;
  height: 76rpx;
  padding: 0 32rpx;
  border: none;
  border-radius: 999rpx;
  background: var(
    --home-tab-active-bg,
    linear-gradient(90deg, #1f5d85 0%, #f08a4f 100%)
  );
  color: #ffffff;
  font-size: 26rpx;
  line-height: 76rpx;
}

.empty-action::after {
  border: none;
}

@media screen and (max-width: 767px) {
  .entry-panel__list {
    grid-template-columns: 1fr;
  }

  .source-panel__grid {
    grid-template-columns: 1fr;
  }
}

@media screen and (min-width: 768px) {
  .review-content,
  .source-panel,
  .banner-wrap,
  .content-section {
    max-width: 960rpx;
    margin-left: auto;
    margin-right: auto;
  }
}

.protocol-fab {
  position: fixed;
  right: 24rpx;
  bottom: calc(120rpx + env(safe-area-inset-bottom));
  z-index: 99;
  height: 72rpx;
  padding: 0 26rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 999rpx;
  background: linear-gradient(135deg, #184f78 0%, #eb8b59 100%);
  color: #fff;
  font-size: 24rpx;
  box-shadow: 0 18rpx 36rpx rgba(17, 34, 51, 0.16);
}

/* #ifdef MP-WEIXIN */
.tab-pill + .tab-pill {
  margin-left: 18rpx;
}

.goods-card,
.merchant-card {
  padding-left: 20rpx;
  padding-right: 20rpx;
}

.goods-image,
.merchant-avatar {
  flex-shrink: 0;
  margin-right: 20rpx;
}

.goods-body,
.merchant-body {
  width: 0;
}

.goods-sales,
.goods-merchant {
  display: block;
}

.goods-sales + .goods-merchant {
  margin-top: 8rpx;
}
/* #endif */
</style>
