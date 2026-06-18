<template>
  <view
    class="mall-page"
    :class="'ui-theme-' + (($store.state.setting && $store.state.setting.system && $store.state.setting.system.ui_theme_style) || 'origin')"
    :style="pageThemeCssVars"
  >
    <view class="hero-card">
      <view class="hero-main">
        <view>
          <view class="hero-title">涛冠优选商城</view>
          <view class="hero-subtitle">统一商城展示、筛选与交易动态</view>
        </view>
        <view class="hero-date">实时更新</view>
      </view>

      <view class="hero-search" @tap="toggleFilterPanel">
        <text class="cuIcon-search"></text>
        <text class="hero-search-text">{{ showFilterPanel ? "收起首页筛选栏" : "展开首页筛选栏" }}</text>
      </view>

      <view class="hero-metrics">
        <view class="hero-metric-cell">
          <view class="hero-metric">
            <text class="metric-label">商品数量</text>
            <text class="metric-value">{{ count || 0 }}</text>
          </view>
        </view>
        <view class="hero-metric-cell">
          <view class="hero-metric">
            <text class="metric-label">筛选结果</text>
            <text class="metric-value">{{ displayList.length }}</text>
          </view>
        </view>
        <view class="hero-metric-cell">
          <view class="hero-metric">
            <text class="metric-label">商品总额</text>
            <text class="metric-value">￥{{ formatMoney(statistics.total || 0) }}</text>
          </view>
        </view>
      </view>
      <view class="hero-runtime-note">
        <view class="hero-runtime-note__title">{{ heroRuntimeTitle }}</view>
        <view class="hero-runtime-note__desc">{{ heroRuntimeDesc }}</view>
      </view>
    </view>

    <view class="section-card env-card">
      <view class="section-head">
        <view class="section-title">当前环境</view>
        <view class="env-badge" :class="currentEnvInfo.is_prod ? 'is-prod' : 'is-test'">{{ currentEnvInfo.label }}</view>
      </view>
      <view class="section-tip env-desc">{{ sellEnvDescription }}</view>
      <view class="env-tags">
        <text class="env-tag">{{ sellModeText }}</text>
        <text class="env-tag">{{ goodsCountTag }}</text>
        <text class="env-tag">{{ envIsolationText }}</text>
        <text class="env-tag">{{ envIsolationStatusText }}</text>
        <text class="env-tag">{{ envReleaseStageText }}</text>
      </view>
      <view class="env-note">{{ sellActionHint }}</view>
      <view class="env-note env-note--strong">{{ envReleaseHint }}</view>
      <view v-if="envRiskList.length" class="env-risk-list">
        <view v-for="item in envRiskList" :key="item" class="env-risk-item">{{ item }}</view>
      </view>
      <view class="env-url">{{ currentEnvInfo.api_root_url || "未配置接口地址" }}</view>
    </view>

    <view v-if="recentActionSummary" class="section-card recent-action-card">
      <view class="section-title">最近操作</view>
      <view class="recent-action-card__desc">{{ recentActionSummary }}</view>
    </view>

    <view v-if="carousel_list.length" class="section-card banner-card">
      <view class="section-head">
        <view class="section-title">精选推荐</view>
        <view class="section-tip">平台轮播内容</view>
      </view>
      <swiper class="banner-swiper" circular autoplay indicator-dots indicator-active-color="#ffffff">
        <swiper-item v-for="(item, index) in carousel_list" :key="index">
          <image class="banner-image" :src="resolveImageUrl(item.file_url, defaultImage)" mode="aspectFill"></image>
        </swiper-item>
      </swiper>
    </view>

    <view class="section-card">
      <view class="section-head">
        <view class="section-title">交易播报</view>
        <view class="section-tip">最新成交动态</view>
      </view>
      <u-notice-bar
        v-if="notce_list.length"
        class="notice-bar"
        direction="column"
        :text="notce_list"
        speed="250"
        fontSize="16"
        bgColor="#f7fafc"
        color="#4d5c6d"
      ></u-notice-bar>
      <view v-else class="empty-inline">暂无交易播报</view>
    </view>

    <view v-show="showFilterPanel" class="section-card">
      <view class="section-head">
        <view class="section-title">首页筛选</view>
        <view class="section-tip">支持关键字、单金额、区间金额</view>
      </view>
      <view class="keyword-row">
        <input
          v-model="keywordInput"
          class="keyword-input"
          type="text"
          placeholder="输入商品关键字，例如：苹果 / 华为 / 牛肉"
          confirm-type="search"
          @confirm="applyAllFilters"
        />
      </view>
      <view class="filter-row">
        <input
          v-model="minPriceInput"
          class="filter-input"
          type="digit"
          placeholder="最小金额"
          confirm-type="search"
          @confirm="applyAllFilters"
        />
        <text class="filter-separator">-</text>
        <input
          v-model="maxPriceInput"
          class="filter-input"
          type="digit"
          placeholder="最大金额"
          confirm-type="search"
          @confirm="applyAllFilters"
        />
      </view>
      <view class="filter-actions">
        <view class="filter-btn primary" @tap="applyAllFilters">立即筛选</view>
        <view class="filter-btn" @tap="clearAllFilters">清空条件</view>
      </view>
      <view v-if="filterSummary" class="filter-summary">{{ filterSummary }}</view>
    </view>

    <view v-if="activeFilterTags.length" class="section-card active-filter-card">
      <view class="section-head">
        <view class="section-title">当前筛选</view>
        <view class="section-tip">点击标签可单独取消</view>
      </view>
      <view class="chip-row wrap active-filter-row">
        <view
          v-for="item in activeFilterTags"
          :key="item.key"
          class="active-filter-chip"
          @tap="removeFilterTag(item)"
        >
          <text class="active-filter-text">{{ item.label }}</text>
          <text class="active-filter-close">x</text>
        </view>
      </view>
    </view>

    <view v-if="recentReleasedGoods" class="section-card recent-release-card">
      <view class="recent-release-card__head">
        <view>
          <view class="recent-release-card__badge">刚刚发布</view>
          <view class="recent-release-card__title">{{ recentReleasedGoods.title || "新商品" }}</view>
          <view class="recent-release-card__desc">已自动帮你定位到这条新商品，并在列表中高亮显示。</view>
        </view>
        <view class="recent-release-card__action" @tap="clearRecentReleasedGoods">恢复列表</view>
      </view>
    </view>

    <view v-if="goodsTypes.length" class="section-card goods-type-section">
      <view class="section-head">
        <view class="section-title">商品分类</view>
        <view class="section-tip">点击快速筛选</view>
      </view>
      <scroll-view scroll-x class="chip-scroll">
        <view class="chip-row">
          <view
            class="filter-chip"
            :class="{ active: !query.goods_type_id }"
            @tap="selectGoodsType('')"
          >
            全部
          </view>
          <view
            v-for="item in goodsTypes"
            :key="item.id"
            class="filter-chip"
            :class="{ active: Number(query.goods_type_id) === Number(item.id) }"
            @tap="selectGoodsType(item.id)"
          >
            {{ item.title }}
          </view>
        </view>
      </scroll-view>
    </view>

    <view v-if="goodsLabels.length" class="section-card">
      <view class="section-head">
        <view class="section-title">标签筛选</view>
        <view class="section-tip">支持多选</view>
      </view>
      <view class="chip-row wrap">
        <view
          v-for="item in goodsLabels"
          :key="item.id"
          class="filter-chip"
          :class="{ active: isLabelActive(item.id) }"
          @tap="toggleGoodsLabel(item.id)"
        >
          {{ item.title }}
        </view>
      </view>
    </view>

    <view class="section-card goods-section">
      <view class="section-head">
        <view class="section-title">商城商品</view>
        <view class="section-tip">共 {{ displayList.length }} 件</view>
      </view>

      <view v-if="loadError" class="empty-state">
        <view class="empty-title">商品页数据加载失败</view>
        <view class="empty-text">{{ loadError }}</view>
        <view class="filter-actions">
          <view class="filter-btn primary" @tap="retryLoad">重新加载</view>
        </view>
      </view>


      <view v-if="displayList.length" class="goods-grid">
        <view
          v-for="item in renderList"
          :key="item.id"
          class="goods-grid-cell"
        >
          <view
            class="goods-card"
            :class="{ 'is-recent-release': item.isRecentRelease, 'is-qr-like': item.isQrLikeGoods }"
            @tap="openGoodsDetail(item)"
          >
            <view class="goods-image-box" :class="{ 'is-qr-like': item.isQrLikeGoods }">
              <view v-if="item.isQrLikeGoods" class="goods-qr-badge">{{ item.qrBadgeText }}</view>
              <view class="goods-image-frame" :class="{ 'is-qr-like': item.isQrLikeGoods }">
                <image class="goods-image" :src="resolveImageUrl(item.image_url, defaultImage)" mode="aspectFit"></image>
              </view>
              <view v-if="item.isQrLikeGoods" class="goods-qr-tip">{{ item.qrLikeHint }}</view>
            </view>
            <view class="goods-body">
              <view v-if="item.isRecentRelease" class="goods-recent-badge">新发布</view>
              <view class="goods-title">{{ item.title }}</view>
              <view class="goods-spec">{{ item.displaySpec }}</view>
              <view class="goods-meta">
                <text class="goods-price">￥{{ formatMoney(item.price) }}</text>
                <text class="goods-sales">已售 {{ item.sales_sum || 0 }}{{ item.unit || "件" }}</text>
              </view>
              <view class="goods-tags">
                <text v-if="item.label_title" class="goods-tag">{{ item.label_title }}</text>
              </view>
              <view class="goods-merchant">{{ merchantTitleText(item.merchant_title, "平台直营") }}</view>
            </view>
          </view>
        </view>
      </view>

      <view v-else-if="!isLoad" class="empty-state">当前条件下暂无商品</view>
    </view>

    <view class="loading-box" v-if="isLoad">加载中...</view>
    <view class="cu-tabbar-height"></view>
  </view>
</template>

<script>
import UNoticeBar from "@/uni_modules/uview-ui/components/u-notice-bar/u-notice-bar.vue";
import api from "@/api";
import cache from "@/utils/cache.js";
import { maskMerchantTitle } from "@/utils/desensitize.js";
import { resolveImageUrl as resolveSafeImageUrl } from "@/utils/resource.js";
import { resolveUiThemeStyle } from "@/utils/ui-theme.js";
import { getCurrentEnvInfo, getEnvIsolationHealth } from "@/utils/env-runtime.js";
import { getEnvIsolationHint, getEnvIsolationTag, getEnvReleaseHint, getEnvReleaseStageText } from "@/utils/env-risk.js";

function buildCssVars(styleMap = {}) {
  return Object.keys(styleMap)
    .filter((key) => styleMap[key] !== undefined && styleMap[key] !== null && styleMap[key] !== "")
    .map((key) => `${key}:${styleMap[key]}`)
    .join(";");
}

const RECENT_RELEASED_GOODS_KEY = "recent_released_goods";
const SELL_PAGE_THEME_PRESETS = {
  origin: {
    pageBg:
      "radial-gradient(circle at top right, rgba(243, 143, 90, 0.22), transparent 32%), linear-gradient(180deg, #f9f3eb 0%, #f3f6fa 42%, #edf2f6 100%)",
    heroBg:
      "radial-gradient(circle at top right, rgba(255, 255, 255, 0.16), transparent 28%), linear-gradient(135deg, #154b72 0%, #2b6a8d 48%, #ec8a57 100%)",
    heroText: "#ffffff",
    heroMuted: "rgba(255, 255, 255, 0.84)",
    heroSoftBg: "rgba(255, 255, 255, 0.14)",
    heroSoftBorder: "transparent",
    surfaceBg: "rgba(255, 255, 255, 0.96)",
    surfaceBorder: "transparent",
    surfaceShadow: "0 18rpx 42rpx rgba(21, 33, 52, 0.08)",
    softBg: "linear-gradient(180deg, #f7fafc 0%, #eef3f7 100%)",
    softBorder: "transparent",
    softText: "#192636",
    primaryBg: "linear-gradient(90deg, #1c5c88 0%, #ec8a57 100%)",
    primaryText: "#ffffff",
    secondaryBg: "#edf1f5",
    secondaryText: "#4e5d70",
    summaryBg: "#fff4ee",
    summaryText: "#b14b25",
    title: "#162233",
    text: "#5f6e81",
    muted: "#7b8797",
    itemBg: "linear-gradient(180deg, #f9fbfc 0%, #f0f4f7 100%)",
    itemBorder: "rgba(229, 233, 239, 0.88)",
    itemShadow: "inset 0 1rpx 0 rgba(255, 255, 255, 0.8)",
    itemTitle: "#152131",
    itemText: "#718093",
    itemMuted: "#667487",
    price: "#cf5a48",
    badgeBg: "rgba(227, 123, 67, 0.12)",
    badgeText: "#d86b32",
    accent: "#1c5c88",
  },
  red_energy: {
    pageBg:
      "radial-gradient(circle at top right, rgba(255, 220, 198, 0.22), transparent 24%), linear-gradient(180deg, #a30b15 0%, #b9111d 8%, #fff1ea 9%, #fff8f4 100%)",
    heroBg:
      "radial-gradient(circle at top right, rgba(255, 255, 255, 0.14), transparent 24%), linear-gradient(135deg, #8f0912 0%, #c41825 58%, #ef8339 100%)",
    heroText: "#fff8f4",
    heroMuted: "rgba(255, 248, 244, 0.84)",
    heroSoftBg: "rgba(255, 247, 242, 0.14)",
    heroSoftBorder: "rgba(255, 241, 234, 0.14)",
    surfaceBg: "linear-gradient(180deg, rgba(255, 252, 249, 0.99) 0%, rgba(255, 244, 238, 0.96) 100%)",
    surfaceBorder: "rgba(201, 29, 36, 0.12)",
    surfaceShadow: "0 18rpx 30rpx rgba(116, 8, 12, 0.1)",
    softBg: "linear-gradient(180deg, rgba(255, 245, 240, 0.98) 0%, rgba(255, 234, 225, 0.95) 100%)",
    softBorder: "rgba(201, 29, 36, 0.08)",
    softText: "#9f1d15",
    primaryBg: "linear-gradient(135deg, #8f0912 0%, #c91d24 62%, #f08d3f 100%)",
    primaryText: "#fff8f4",
    secondaryBg: "linear-gradient(180deg, rgba(255, 245, 240, 0.98) 0%, rgba(255, 234, 225, 0.95) 100%)",
    secondaryText: "#9f1d15",
    summaryBg: "linear-gradient(180deg, rgba(255, 245, 240, 0.98) 0%, rgba(255, 234, 225, 0.95) 100%)",
    summaryText: "#9f1d15",
    title: "#8f140f",
    text: "#a9613e",
    muted: "#a9613e",
    itemBg: "linear-gradient(180deg, rgba(255, 251, 248, 0.99) 0%, rgba(255, 240, 233, 0.96) 100%)",
    itemBorder: "rgba(201, 29, 36, 0.1)",
    itemShadow: "0 12rpx 22rpx rgba(116, 8, 12, 0.08)",
    itemTitle: "#8f140f",
    itemText: "#a9613e",
    itemMuted: "#a9613e",
    price: "#8f140f",
    badgeBg: "rgba(201, 29, 36, 0.08)",
    badgeText: "#a21d15",
    accent: "#a21d15",
  },
  yellow_energy: {
    pageBg:
      "radial-gradient(circle at top right, rgba(255, 226, 140, 0.28), transparent 26%), linear-gradient(180deg, #f4c63f 0%, #f7d869 21%, #fbf1ca 44%, #fff9ea 100%)",
    heroBg:
      "radial-gradient(circle at top right, rgba(255, 255, 255, 0.16), transparent 24%), linear-gradient(135deg, #f2c23f 0%, #f8da74 48%, #fff7df 100%)",
    heroText: "#9b210f",
    heroMuted: "rgba(155, 33, 15, 0.84)",
    heroSoftBg: "rgba(183, 32, 18, 0.08)",
    heroSoftBorder: "rgba(183, 32, 18, 0.1)",
    surfaceBg: "linear-gradient(180deg, rgba(255, 255, 255, 0.99) 0%, rgba(255, 250, 238, 0.98) 100%)",
    surfaceBorder: "rgba(183, 32, 18, 0.1)",
    surfaceShadow: "0 18rpx 32rpx rgba(177, 100, 20, 0.12)",
    softBg: "linear-gradient(180deg, rgba(255, 252, 242, 0.98) 0%, rgba(255, 246, 220, 0.94) 100%)",
    softBorder: "rgba(183, 32, 18, 0.1)",
    softText: "#9b210f",
    primaryBg: "linear-gradient(135deg, #b82013 0%, #dc531e 55%, #f3b132 100%)",
    primaryText: "#fff8ef",
    secondaryBg: "linear-gradient(180deg, rgba(255, 252, 242, 0.98) 0%, rgba(255, 246, 220, 0.94) 100%)",
    secondaryText: "#a72614",
    summaryBg: "linear-gradient(180deg, rgba(255, 252, 242, 0.98) 0%, rgba(255, 246, 220, 0.94) 100%)",
    summaryText: "#b16825",
    title: "#8f1f0f",
    text: "#b16825",
    muted: "#a76b27",
    itemBg: "linear-gradient(180deg, rgba(255, 252, 242, 0.98) 0%, rgba(255, 246, 220, 0.94) 100%)",
    itemBorder: "rgba(183, 32, 18, 0.1)",
    itemShadow: "0 12rpx 26rpx rgba(177, 100, 20, 0.1)",
    itemTitle: "#8f1f0f",
    itemText: "#b16825",
    itemMuted: "#a76b27",
    price: "#d7772e",
    badgeBg: "rgba(227, 123, 67, 0.12)",
    badgeText: "#d86b32",
    accent: "#ad2814",
  },
  jade_modern: {
    pageBg:
      "radial-gradient(circle at top right, rgba(216, 164, 58, 0.18), transparent 24%), linear-gradient(180deg, #dfe7e0 0%, #eef3ee 22%, #f7f1e7 46%, #fbf8f2 100%)",
    heroBg:
      "radial-gradient(circle at top right, rgba(255, 255, 255, 0.14), transparent 24%), linear-gradient(135deg, #184b42 0%, #206c60 54%, #d7a13e 100%)",
    heroText: "#f8f4ea",
    heroMuted: "rgba(248, 244, 234, 0.84)",
    heroSoftBg: "rgba(255, 255, 255, 0.12)",
    heroSoftBorder: "rgba(248, 244, 234, 0.18)",
    surfaceBg: "linear-gradient(180deg, rgba(255, 253, 250, 0.99) 0%, rgba(245, 240, 231, 0.97) 100%)",
    surfaceBorder: "rgba(24, 76, 67, 0.12)",
    surfaceShadow: "0 18rpx 34rpx rgba(19, 52, 47, 0.12)",
    softBg: "linear-gradient(180deg, rgba(247, 244, 236, 0.98) 0%, rgba(239, 233, 220, 0.96) 100%)",
    softBorder: "rgba(24, 76, 67, 0.1)",
    softText: "#21463f",
    primaryBg: "linear-gradient(135deg, #184b42 0%, #1d665b 54%, #d5a13b 100%)",
    primaryText: "#f8f4ea",
    secondaryBg: "linear-gradient(180deg, rgba(247, 244, 236, 0.98) 0%, rgba(239, 233, 220, 0.96) 100%)",
    secondaryText: "#1d665b",
    summaryBg: "linear-gradient(180deg, rgba(247, 244, 236, 0.98) 0%, rgba(239, 233, 220, 0.96) 100%)",
    summaryText: "#607068",
    title: "#21463f",
    text: "#607068",
    muted: "#7b867f",
    itemBg: "linear-gradient(180deg, rgba(250, 248, 242, 0.99) 0%, rgba(240, 235, 224, 0.96) 100%)",
    itemBorder: "rgba(24, 76, 67, 0.1)",
    itemShadow: "0 12rpx 24rpx rgba(19, 52, 47, 0.08)",
    itemTitle: "#21463f",
    itemText: "#607068",
    itemMuted: "#7b867f",
    price: "#c79036",
    badgeBg: "rgba(24, 76, 67, 0.08)",
    badgeText: "#1d665b",
    accent: "#1d665b",
  },
};

export default {
  name: "sell",
  components: {
    UNoticeBar,
  },
  data() {
    return {
      notce_list: [],
      query: {
        page: 1,
        limit: 10,
        sort_field: null,
        sort_value: null,
        goods_type_id: undefined,
        goods_label_id: [],
        source: 0,
        exact_price: 0,
        min_price: 0,
        max_price: 0,
      },
      params: {},
      isLoad: false,
      loadError: "",
      list: [],
      statistics: {},
      count: 0,
      pages: 0,
      carousel_list: [],
      showFilterPanel: true,
      keywordInput: "",
      minPriceInput: "",
      maxPriceInput: "",
      defaultImage: "/static/images/avatar/1.jpg",
      recentReleasedGoods: null,
      recentActionSummary: "",
    };
  },
  computed: {
    currentEnvInfo() {
      return getCurrentEnvInfo();
    },
    sellEnvDescription() {
      return this.currentEnvInfo.is_prod
        ? "当前为正式环境，商品筛选与最近发布定位都对应真实商城数据。"
        : "当前为非正式环境，适合联调首页筛选、商品展示和发布后定位回显。";
    },
    sellModeText() {
      return this.showFilterPanel ? "当前模式：筛选展开" : "当前模式：筛选收起";
    },
    goodsCountTag() {
      return `当前商品：${this.count || 0} 件`;
    },
    envIsolationText() {
      return getEnvIsolationTag(this.currentEnvInfo);
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
    envRiskList() {
      return this.envIsolationHealth.warnings || [];
    },
    sellActionHint() {
      return this.currentEnvInfo.is_prod
        ? `正式环境下请重点核对最近发布商品、价格和筛选结果是否与真实商品池一致。${getEnvIsolationHint(this.currentEnvInfo)}`
        : `建议在当前环境验证最近发布高亮、分类筛选、标签筛选和价格筛选回显。${getEnvIsolationHint(this.currentEnvInfo)}`;
    },
    pageThemeCssVars() {
      const themeStyle = resolveUiThemeStyle(this.$store.state.setting || cache.get("setting") || {});
      const palette = SELL_PAGE_THEME_PRESETS[themeStyle] || SELL_PAGE_THEME_PRESETS.origin;
      return buildCssVars({
        "--sell-page-bg": palette.pageBg,
        "--sell-hero-bg": palette.heroBg,
        "--sell-hero-text": palette.heroText,
        "--sell-hero-muted": palette.heroMuted,
        "--sell-hero-soft-bg": palette.heroSoftBg,
        "--sell-hero-soft-border": palette.heroSoftBorder,
        "--sell-surface-bg": palette.surfaceBg,
        "--sell-surface-border": palette.surfaceBorder,
        "--sell-surface-shadow": palette.surfaceShadow,
        "--sell-soft-bg": palette.softBg,
        "--sell-soft-border": palette.softBorder,
        "--sell-soft-text": palette.softText,
        "--sell-primary-bg": palette.primaryBg,
        "--sell-primary-text": palette.primaryText,
        "--sell-secondary-bg": palette.secondaryBg,
        "--sell-secondary-text": palette.secondaryText,
        "--sell-summary-bg": palette.summaryBg,
        "--sell-summary-text": palette.summaryText,
        "--sell-title": palette.title,
        "--sell-text": palette.text,
        "--sell-muted": palette.muted,
        "--sell-item-bg": palette.itemBg,
        "--sell-item-border": palette.itemBorder,
        "--sell-item-shadow": palette.itemShadow,
        "--sell-item-title": palette.itemTitle,
        "--sell-item-text": palette.itemText,
        "--sell-item-muted": palette.itemMuted,
        "--sell-price": palette.price,
        "--sell-badge-bg": palette.badgeBg,
        "--sell-badge-text": palette.badgeText,
        "--sell-accent": palette.accent,
      });
    },
    goodsTypes() {
      return this.params.goods_types || [];
    },
    goodsLabels() {
      return this.params.goods_labels || [];
    },
    displayList() {
      return this.list.filter((item) => {
        const keyword = String(this.query.keyword || "").trim().toLowerCase();
        if (keyword) {
          const haystack = [item.title, item.spec, item.merchant_title, item.label_title]
            .join(" ")
            .toLowerCase();
          if (!haystack.includes(keyword)) {
            return false;
          }
        }
        const price = Number(item.price || 0);
        if (this.query.exact_price > 0 && price !== Number(this.query.exact_price)) {
          return false;
        }
        if (this.query.min_price > 0 && price < Number(this.query.min_price)) {
          return false;
        }
        if (this.query.max_price > 0 && price > Number(this.query.max_price)) {
          return false;
        }
        return true;
      });
    },
    renderList() {
      const recentId = Number(this.recentReleasedGoods && this.recentReleasedGoods.id || 0);
      const imageUsageMap = this.displayList.reduce((result, item) => {
        const signature = this.normalizeGoodsImageSignature(item.image_url);
        if (signature) {
          result[signature] = (result[signature] || 0) + 1;
        }
        return result;
      }, {});
      return this.displayList.map((item) => ({
        ...item,
        isRecentRelease: recentId > 0 && Number(item.id || 0) === recentId,
        ...this.getGoodsCardMeta(item, imageUsageMap),
      }));
    },
    heroRuntimeTitle() {
      if (this.recentReleasedGoods) {
        return `最近发布：${this.recentReleasedGoods.title || "新商品"}`;
      }
      return this.activeFilterTags.length ? "当前列表已应用筛选" : "当前列表为默认商城流";
    },
    heroRuntimeDesc() {
      if (this.recentReleasedGoods) {
        return "系统已自动按新发布商品回跳并高亮，便于继续核对详情、价格与图片。";
      }
      if (this.activeFilterTags.length) {
        return `当前共启用 ${this.activeFilterTags.length} 项筛选条件，可继续缩小范围后查看商品详情。`;
      }
      return "适合从这里继续查看最新商品、回看发布结果，或按分类和标签做运营筛选。";
    },
    filterSummary() {
      const parts = [];
      if (this.query.keyword) {
        parts.push("关键字：" + this.query.keyword);
      }
      if (this.query.exact_price > 0) {
        parts.push("金额：￥" + this.formatMoney(this.query.exact_price));
      }
      if (this.query.min_price > 0 || this.query.max_price > 0) {
        parts.push(
          "区间：￥" +
            this.formatMoney(this.query.min_price || 0) +
            " - ￥" +
            this.formatMoney(this.query.max_price || 0),
        );
      }
      return parts.join(" / ");
    },
    activeFilterTags() {
      const tags = [];
      const keyword = String(this.query.keyword || "").trim();
      const typeId = Number(this.query.goods_type_id || 0);
      const activeLabels = (this.query.goods_label_id || []).map((item) => String(item));

      if (keyword) {
        tags.push({
          key: "keyword",
          type: "keyword",
          label: "关键字：" + keyword,
        });
      }

      if (this.query.exact_price > 0) {
        tags.push({
          key: "exact_price",
          type: "price",
          label: "金额：￥" + this.formatMoney(this.query.exact_price),
        });
      } else if (this.query.min_price > 0 || this.query.max_price > 0) {
        tags.push({
          key: "price_range",
          type: "price",
          label:
            "区间：￥" +
            this.formatMoney(this.query.min_price || 0) +
            " - ￥" +
            this.formatMoney(this.query.max_price || 0),
        });
      }

      if (typeId > 0) {
        const matchedType = this.goodsTypes.find((item) => Number(item.id) === typeId);
        tags.push({
          key: "goods_type_id",
          type: "goods_type",
          label: "分类：" + (matchedType ? matchedType.title : "已选分类"),
        });
      }

      activeLabels.forEach((labelId) => {
        const matchedLabel = this.goodsLabels.find((item) => String(item.id) === labelId);
        tags.push({
          key: "label_" + labelId,
          type: "label",
          value: labelId,
          label: "标签：" + (matchedLabel ? matchedLabel.title : "已选标签"),
        });
      });

      return tags;
    },
  },
  created() {
    this.getCarouselList();
    this.getParams();
    this.getOrderTransaction();
    this.getList();
  },
  onShow() {
    this.applyRecentReleasedGoods();
  },
  onReachBottom() {
    if (this.query.page < this.pages) {
      this.query.page += 1;
      this.getList();
    }
  },
  methods: {
    updateRecentActionSummary(summary) {
      this.recentActionSummary = summary;
    },
    resolveImageUrl(url, fallback = this.defaultImage) {
      return resolveSafeImageUrl(url, fallback);
    },
    normalizeGoodsImageSignature(imageUrl) {
      return String(imageUrl || "")
        .trim()
        .toLowerCase()
        .split("?")[0]
        .split("#")[0];
    },
    getGoodsCardMeta(item, imageUsageMap = {}) {
      const title = String(item.title || "").trim();
      const spec = String(item.spec || "").trim();
      const titleCompact = title.replace(/\s+/g, "");
      const keywordText = [title, spec].join(" ").toLowerCase();
      const imageSignature = this.normalizeGoodsImageSignature(item.image_url);
      const sharedImageCount = imageSignature ? Number(imageUsageMap[imageSignature] || 0) : 0;
      const hasQrKeyword = /(补差价|差价|收款|支付|扫码|二维码|补款|尾款)/i.test(keywordText);
      const isNumericTitle = /^\d+(\.\d+)?$/.test(titleCompact);
      const isShortTitle = titleCompact.length > 0 && titleCompact.length <= 8;
      const isQrLikeGoods =
        hasQrKeyword ||
        (isNumericTitle && sharedImageCount >= 2) ||
        (sharedImageCount >= 3 && isShortTitle);

      if (!isQrLikeGoods) {
        return {
          isQrLikeGoods: false,
          qrBadgeText: "",
          qrLikeHint: "",
          displaySpec: spec || "标准规格",
        };
      }

      const isPaymentStyle = /(收款|支付|扫码|二维码|尾款)/i.test(keywordText) || isNumericTitle;
      return {
        isQrLikeGoods: true,
        qrBadgeText: isPaymentStyle ? "扫码类商品" : "凭证类商品",
        qrLikeHint: isPaymentStyle
          ? "进入详情确认金额、收款说明后再下单"
          : "进入详情确认用途、补差价说明后再购买",
        displaySpec: spec || (isPaymentStyle ? "按详情说明完成支付" : "按详情说明核对用途"),
      };
    },
    merchantTitleText(value, fallback = "平台直营") {
      return maskMerchantTitle(value, fallback);
    },
    applyRecentReleasedGoods() {
      const recent = cache.get(RECENT_RELEASED_GOODS_KEY, null);
      if (!recent || typeof recent !== "object") {
        return;
      }

      const recentId = Number(recent.id || 0);
      const recentTitle = String(recent.title || "").trim();
      if (!recentId && !recentTitle) {
        cache.remove(RECENT_RELEASED_GOODS_KEY);
        return;
      }

      this.recentReleasedGoods = {
        id: recentId,
        title: recentTitle,
      };
      cache.remove(RECENT_RELEASED_GOODS_KEY);

      this.showFilterPanel = true;
      this.keywordInput = recentTitle;
      this.minPriceInput = "";
      this.maxPriceInput = "";
      this.query.keyword = recentTitle;
      this.query.exact_price = 0;
      this.query.min_price = 0;
      this.query.max_price = 0;
      this.query.goods_type_id = undefined;
      this.query.goods_label_id = [];
      this.query.page = 1;
      this.list = [];
      this.getList();

      uni.pageScrollTo({
        scrollTop: 0,
        duration: 0,
      });
    },
    clearRecentReleasedGoods() {
      this.recentReleasedGoods = null;
      this.updateRecentActionSummary("已清除最近发布商品定位，恢复默认商品列表。");
      this.clearAllFilters();
    },
    toggleFilterPanel() {
      this.showFilterPanel = !this.showFilterPanel;
      this.updateRecentActionSummary(this.showFilterPanel ? "已展开首页筛选栏。" : "已收起首页筛选栏。");
    },
    formatMoney(value) {
      return Number(value || 0).toFixed(2);
    },
    normalizeGoodsId(value) {
      const text = String(value === undefined || value === null ? "" : value).trim();
      if (!/^\d+$/.test(text)) {
        return 0;
      }
      return Number(text);
    },
    resolveGoodsId(item = {}) {
      const nestedGoods = item.goods && typeof item.goods === "object" ? item.goods : {};
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
        uni.showToast({
          icon: "none",
          title: "商品信息异常",
        });
        return;
      }
      this.jump(`/pages/goods/details?goods_id=${goodsId}`);
    },
    normalizeNoticeText(item) {
      if (item === undefined || item === null) {
        return "";
      }
      if (typeof item === "string" || typeof item === "number") {
        return String(item).trim();
      }
      if (Array.isArray(item)) {
        return item
          .map((child) => this.normalizeNoticeText(child))
          .filter(Boolean)
          .join("，");
      }
      if (typeof item !== "object") {
        return "";
      }

      const preferredKeys = [
        "text",
        "title",
        "content",
        "message",
        "notice",
        "notice_text",
        "desc",
        "remark",
      ];
      for (let i = 0; i < preferredKeys.length; i += 1) {
        const text = String(item[preferredKeys[i]] || "").trim();
        if (text) {
          return text;
        }
      }

      return Object.keys(item)
        .map((key) => item[key])
        .filter((value) => typeof value === "string" || typeof value === "number")
        .map((value) => String(value).trim())
        .filter(Boolean)
        .join("，");
    },
    normalizeNoticeList(list = []) {
      if (!Array.isArray(list)) {
        return [];
      }
      return list.map((item) => this.normalizeNoticeText(item)).filter(Boolean);
    },
    normalizeNumber(value) {
      const text = String(value || "").trim();
      if (!/^\d+(\.\d{1,2})?$/.test(text)) {
        return 0;
      }
      return Number(Number(text).toFixed(2));
    },
    parsePriceInput() {
      const minText = String(this.minPriceInput || "").trim();
      const maxText = String(this.maxPriceInput || "").trim();

      if (minText && !maxText) {
        const exactPrice = this.normalizeNumber(minText);
        if (exactPrice > 0) {
          return {
            type: "exact",
            exact_price: exactPrice,
          };
        }
      }

      if (minText || maxText) {
        const minPrice = minText ? this.normalizeNumber(minText) : 0;
        const maxPrice = maxText ? this.normalizeNumber(maxText) : 0;
        if (minPrice > 0 || maxPrice > 0) {
          if (minPrice > 0 && maxPrice > 0 && minPrice > maxPrice) {
            return null;
          }
          return {
            type: "range",
            min_price: minPrice,
            max_price: maxPrice,
          };
        }
      }

      return null;
    },
    applyAllFilters() {
      const parsed = this.parsePriceInput();
      const keyword = String(this.keywordInput || "").trim();
      if (!parsed && !keyword) {
        uni.showToast({
          title: "请输入关键字或金额",
          icon: "none",
        });
        return;
      }

      this.query.keyword = keyword;
      this.query.exact_price = 0;
      this.query.min_price = 0;
      this.query.max_price = 0;

      if (parsed) {
        if (parsed.type === "exact") {
          this.query.exact_price = parsed.exact_price;
          this.minPriceInput = this.formatMoney(parsed.exact_price);
          this.maxPriceInput = this.formatMoney(parsed.exact_price);
        } else {
          this.query.min_price = parsed.min_price;
          this.query.max_price = parsed.max_price;
          this.minPriceInput = parsed.min_price ? this.formatMoney(parsed.min_price) : "";
          this.maxPriceInput = parsed.max_price ? this.formatMoney(parsed.max_price) : "";
        }
      }

      this.updateRecentActionSummary(`已应用首页筛选：${keyword ? "关键字已设置" : "无关键字"}${parsed ? "，金额条件已生效" : ""}。`);
      this.resetAndFetch();
    },
    clearAllFilters() {
      this.keywordInput = "";
      this.minPriceInput = "";
      this.maxPriceInput = "";
      this.query.keyword = "";
      this.query.exact_price = 0;
      this.query.min_price = 0;
      this.query.max_price = 0;
      this.updateRecentActionSummary("已清空首页筛选条件。");
      this.resetAndFetch();
    },
    removeFilterTag(item) {
      if (!item || !item.type) {
        return;
      }

      if (item.type === "keyword") {
        this.keywordInput = "";
        this.query.keyword = "";
      }

      if (item.type === "price") {
        this.minPriceInput = "";
        this.maxPriceInput = "";
        this.query.exact_price = 0;
        this.query.min_price = 0;
        this.query.max_price = 0;
      }

      if (item.type === "goods_type") {
        this.query.goods_type_id = undefined;
      }

      if (item.type === "label") {
        const labelId = String(item.value || "");
        this.query.goods_label_id = (this.query.goods_label_id || [])
          .map((value) => String(value))
          .filter((value) => value !== labelId);
      }

      this.updateRecentActionSummary(`已移除筛选项：${item.label || item.key || "当前条件"}。`);
      this.resetAndFetch();
    },
    isLabelActive(id) {
      return this.query.goods_label_id.includes(String(id)) || this.query.goods_label_id.includes(id);
    },
    selectGoodsType(id) {
      this.query.goods_type_id = id || undefined;
      const currentType = (this.goodsTypes || []).find((item) => Number(item.id) === Number(id));
      this.updateRecentActionSummary(`已切换商品分类：${currentType ? currentType.title : "全部"}。`);
      this.resetAndFetch();
    },
    toggleGoodsLabel(id) {
      const labelId = String(id);
      const next = (this.query.goods_label_id || []).map((item) => String(item));
      const index = next.indexOf(labelId);
      if (index >= 0) {
        next.splice(index, 1);
      } else {
        next.push(labelId);
      }
      this.query.goods_label_id = next;
      const currentLabel = (this.goodsLabels || []).find((item) => Number(item.id) === Number(id));
      this.updateRecentActionSummary(`${index >= 0 ? "已取消" : "已添加"}标签筛选：${currentLabel ? currentLabel.title : id}。`);
      this.resetAndFetch();
    },
    resetAndFetch() {
      this.query.page = 1;
      this.list = [];
      this.getList();
      uni.pageScrollTo({
        scrollTop: 0,
        duration: 0,
      });
    },
    getCarouselList() {
      api
        .getCarouselList({})
        .then((res) => {
          this.carousel_list = (res.data && res.data.list) || [];
          this.updateRecentActionSummary(`首页轮播已刷新，当前 ${this.carousel_list.length} 项。`);
        })
        .catch(() => {
          this.updateRecentActionSummary("首页轮播加载失败。");
        });
    },
    getOrderTransaction() {
      api
        .getOrderTransaction({ type: 2 })
        .then((res) => {
          this.notce_list = this.normalizeNoticeList(res.data || []);
          this.updateRecentActionSummary(`交易播报已刷新，当前 ${this.notce_list.length} 条。`);
        })
        .catch(() => {
          this.updateRecentActionSummary("交易播报加载失败。");
        });
    },
    getList() {
      this.isLoad = true;
      api
        .getGoodsList(this.query)
        .then((res) => {
          const dataList = (res.data && res.data.list) || [];
          this.list = this.query.page === 1 ? dataList : this.list.concat(dataList);
          this.count = (res.data && res.data.count) || 0;
          this.pages = (res.data && res.data.pages) || 0;
          this.statistics = (res.data && res.data.statistics) || {};
          this.loadError = "";
          this.updateRecentActionSummary(`商品列表已刷新，共 ${this.count || 0} 件，当前展示 ${this.displayList.length} 件。`);
        })
        .catch(() => {
          if (this.query.page === 1) {
            this.list = [];
            this.count = 0;
            this.pages = 0;
            this.statistics = {};
            this.loadError = "商品列表暂时无法加载，请稍后重试";
            this.updateRecentActionSummary("商品列表加载失败，请确认当前环境和接口状态。");
          }
        })
        .finally(() => {
          this.isLoad = false;
        });
    },
    getParams() {
      api
        .getGoodsParams({})
        .then((res) => {
          this.params = res.data || {};
          this.updateRecentActionSummary("商品筛选参数已同步。");
        })
        .catch(() => {
          if (!this.list.length) {
            this.loadError = "筛选参数暂时无法加载，请稍后重试";
          }
          this.updateRecentActionSummary("商品筛选参数加载失败。");
        });
    },
    retryLoad() {
      this.loadError = "";
      this.query.page = 1;
      this.list = [];
      this.updateRecentActionSummary("正在重新加载商城首页数据。");
      this.getCarouselList();
      this.getParams();
      this.getOrderTransaction();
      this.getList();
    },
    jump(url) {
      this.updateRecentActionSummary("正在进入商品详情。");
      uni.navigateTo({
        url,
      });
    },
  },
};
</script>

<style lang="scss" scoped>
.mall-page {
  min-height: 100vh;
  padding: 24rpx;
  background: var(--sell-page-bg);
}

.hero-card,
.section-card {
  background: var(--sell-surface-bg);
  border-radius: 30rpx;
  box-shadow: var(--sell-surface-shadow);
  border: 1rpx solid var(--sell-surface-border);
}

.hero-card {
  padding: 32rpx;
  color: var(--sell-hero-text);
  background: var(--sell-hero-bg);
}

.hero-main,
.section-head,
.goods-meta {
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
  color: var(--sell-hero-muted);
}

.hero-date {
  padding: 12rpx 18rpx;
  border-radius: 999rpx;
  background: var(--sell-hero-soft-bg);
  border: 1rpx solid var(--sell-hero-soft-border);
  font-size: 22rpx;
  white-space: nowrap;
}

.hero-search {
  display: flex;
  align-items: center;
  gap: 14rpx;
  margin-top: 28rpx;
  padding: 20rpx 22rpx;
  border-radius: 24rpx;
  background: var(--sell-hero-soft-bg);
  border: 1rpx solid var(--sell-hero-soft-border);
}

.hero-search-text {
  font-size: 26rpx;
  color: var(--sell-hero-text);
}

.hero-metrics {
  display: flex;
  flex-wrap: wrap;
  margin: 28rpx -9rpx 0;
}

.hero-runtime-note {
  margin-top: 10rpx;
  padding: 22rpx 24rpx;
  border-radius: 24rpx;
  background: var(--sell-hero-soft-bg);
  border: 1rpx solid var(--sell-hero-soft-border);
}

.hero-runtime-note__title {
  font-size: 24rpx;
  font-weight: 700;
  color: var(--sell-hero-text);
}

.hero-runtime-note__desc {
  margin-top: 10rpx;
  font-size: 22rpx;
  line-height: 1.7;
  color: var(--sell-hero-muted);
}

.hero-metric-cell {
  width: 33.333333%;
  padding: 0 9rpx 18rpx;
  box-sizing: border-box;
  display: flex;
}

.hero-metric {
  width: 100%;
  min-width: 0;
  padding: 20rpx;
  border-radius: 22rpx;
  background: var(--sell-hero-soft-bg);
  border: 1rpx solid var(--sell-hero-soft-border);
}

.metric-label {
  display: block;
  font-size: 22rpx;
  color: var(--sell-hero-muted);
}

.metric-value {
  display: block;
  margin-top: 12rpx;
  font-size: 32rpx;
  font-weight: 700;
}

.section-card {
  margin-top: 20rpx;
  padding: 28rpx;
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
  background: var(--sell-soft-bg);
  color: var(--sell-soft-text);
  border: 1rpx solid var(--sell-soft-border);
  font-size: 20rpx;
}

.env-note {
  margin-top: 12rpx;
  color: var(--sell-text);
  font-size: 22rpx;
  line-height: 1.7;
}

.env-note--strong {
  padding: 16rpx 18rpx;
  border-radius: 18rpx;
  background: var(--sell-soft-bg);
  color: var(--sell-soft-text);
  border: 1rpx solid var(--sell-soft-border);
}

.env-risk-list {
  margin-top: 12rpx;
  padding: 16rpx 18rpx;
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

.env-url {
  margin-top: 10rpx;
  color: var(--sell-muted);
  font-size: 20rpx;
  line-height: 1.6;
  word-break: break-all;
}

.recent-action-card__desc {
  margin-top: 12rpx;
  color: var(--sell-text);
  font-size: 22rpx;
  line-height: 1.7;
}

.recent-release-card {
  border: 1rpx solid var(--sell-surface-border);
  background: var(--sell-soft-bg);
  box-shadow: var(--sell-surface-shadow);
}

.recent-release-card__head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 20rpx;
}

.recent-release-card__badge {
  display: inline-flex;
  padding: 8rpx 18rpx;
  border-radius: 999rpx;
  background: var(--sell-badge-bg);
  color: var(--sell-badge-text);
  font-size: 22rpx;
  font-weight: 700;
}

.recent-release-card__title {
  margin-top: 14rpx;
  font-size: 30rpx;
  font-weight: 700;
  color: var(--sell-title);
}

.recent-release-card__desc {
  margin-top: 10rpx;
  font-size: 22rpx;
  line-height: 1.7;
  color: var(--sell-text);
}

.recent-release-card__action {
  flex-shrink: 0;
  padding: 14rpx 20rpx;
  border-radius: 999rpx;
  background: rgba(255, 255, 255, 0.82);
  color: var(--sell-accent);
  font-size: 22rpx;
  font-weight: 700;
}

.section-title {
  font-size: 30rpx;
  font-weight: 700;
  color: var(--sell-title);
}

.section-tip {
  font-size: 22rpx;
  color: var(--sell-muted);
}

.banner-card {
  overflow: hidden;
}

.banner-swiper {
  height: 280rpx;
  margin-top: 22rpx;
  border-radius: 26rpx;
}

.banner-image {
  width: 100%;
  height: 100%;
  border-radius: 26rpx;
}

.notice-bar {
  margin-top: 22rpx;
  border-radius: 24rpx;
  overflow: hidden;
}

.keyword-row {
  margin-top: 22rpx;
}

.keyword-input {
  width: 100%;
  height: 82rpx;
  padding: 0 24rpx;
  border-radius: 20rpx;
  background: var(--sell-soft-bg);
  font-size: 28rpx;
  color: var(--sell-soft-text);
  border: 1rpx solid var(--sell-soft-border);
  box-sizing: border-box;
}

.filter-row {
  display: flex;
  align-items: center;
  gap: 16rpx;
  margin-top: 22rpx;
}

.filter-input {
  flex: 1;
  height: 82rpx;
  padding: 0 24rpx;
  border-radius: 20rpx;
  background: var(--sell-soft-bg);
  font-size: 28rpx;
  color: var(--sell-soft-text);
  border: 1rpx solid var(--sell-soft-border);
}

.filter-separator {
  color: var(--sell-text);
  font-size: 30rpx;
  font-weight: 700;
}

.filter-actions {
  display: flex;
  gap: 16rpx;
  margin-top: 18rpx;
}

.filter-btn {
  min-width: 160rpx;
  height: 78rpx;
  padding: 0 28rpx;
  border-radius: 20rpx;
  background: var(--sell-secondary-bg);
  color: var(--sell-secondary-text);
  font-size: 26rpx;
  line-height: 78rpx;
  text-align: center;
}

.filter-btn.primary {
  background: var(--sell-primary-bg);
  color: var(--sell-primary-text);
}

.filter-summary {
  margin-top: 18rpx;
  padding: 16rpx 20rpx;
  border-radius: 18rpx;
  background: var(--sell-summary-bg);
  color: var(--sell-summary-text);
  font-size: 24rpx;
}

.active-filter-card {
  background: var(--sell-soft-bg);
}

.active-filter-row {
  margin-top: 18rpx;
}

.active-filter-chip {
  display: inline-flex;
  align-items: center;
  gap: 12rpx;
  padding: 14rpx 22rpx;
  border-radius: 999rpx;
  background: var(--sell-badge-bg);
  color: var(--sell-badge-text);
  font-size: 24rpx;
  white-space: nowrap;
}

.active-filter-text {
  line-height: 1;
}

.active-filter-close {
  font-size: 28rpx;
  line-height: 1;
}

.chip-scroll {
  margin-top: 22rpx;
  white-space: nowrap;
}

.chip-row {
  display: flex;
  gap: 16rpx;
  margin-top: 22rpx;
  align-items: center;
}

.chip-row.wrap {
  flex-wrap: wrap;
}

.filter-chip {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 52rpx;
  padding: 14rpx 24rpx;
  border-radius: 999rpx;
  background: var(--sell-soft-bg);
  color: var(--sell-soft-text);
  border: 1rpx solid var(--sell-soft-border);
  font-size: 24rpx;
  line-height: 1;
  white-space: nowrap;
}

.goods-type-section {
  padding-top: 24rpx;
  padding-bottom: 20rpx;
}

.goods-type-section .section-head {
  align-items: flex-start;
}

.goods-type-section .section-tip {
  padding-top: 4rpx;
}

.goods-type-section .chip-scroll {
  margin-top: 16rpx;
  padding-bottom: 2rpx;
}

.goods-type-section .chip-row {
  margin-top: 0;
  padding-right: 8rpx;
}

.goods-type-section .filter-chip {
  min-height: 50rpx;
  padding: 12rpx 22rpx;
}

.filter-chip.active {
  background: var(--sell-primary-bg);
  color: var(--sell-primary-text);
  box-shadow: 0 10rpx 24rpx rgba(21, 75, 114, 0.2);
}

.goods-section {
  padding-bottom: 12rpx;
}

.goods-grid {
  display: flex;
  flex-wrap: wrap;
  margin: 22rpx -10rpx 0;
}

.goods-grid-cell {
  width: 50%;
  padding: 0 10rpx 20rpx;
  box-sizing: border-box;
  display: flex;
}

.goods-card {
  width: 100%;
  min-width: 0;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  border-radius: 26rpx;
  background: var(--sell-item-bg);
  border: 1rpx solid var(--sell-item-border);
  box-shadow: var(--sell-item-shadow);
}

.goods-card.is-recent-release {
  border-color: rgba(230, 157, 75, 0.4);
  box-shadow: 0 18rpx 34rpx rgba(230, 157, 75, 0.18);
  transform: translateY(-4rpx);
}

.goods-card.is-qr-like {
  border-color: var(--sell-soft-border);
  box-shadow: 0 14rpx 28rpx rgba(15, 36, 52, 0.08);
}

.goods-image-box {
  position: relative;
  width: 100%;
  height: 280rpx;
  padding: 12rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.92);
  box-sizing: border-box;
}

.goods-image-box.is-qr-like {
  padding: 18rpx;
  align-items: stretch;
  justify-content: flex-start;
  background: var(--sell-soft-bg);
}

.goods-image-frame {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 20rpx;
  overflow: hidden;
  background: rgba(255, 255, 255, 0.92);
}

.goods-image-frame.is-qr-like {
  width: 196rpx;
  height: 196rpx;
  margin: 44rpx auto 0;
  padding: 16rpx;
  border-radius: 24rpx;
  border: 1rpx solid var(--sell-soft-border);
  background: rgba(255, 255, 255, 0.98);
  box-shadow: 0 12rpx 24rpx rgba(15, 36, 52, 0.08);
  box-sizing: border-box;
}

.goods-qr-badge {
  position: absolute;
  top: 18rpx;
  left: 18rpx;
  z-index: 1;
  display: inline-flex;
  align-items: center;
  padding: 8rpx 16rpx;
  border-radius: 999rpx;
  background: var(--sell-badge-bg);
  color: var(--sell-badge-text);
  font-size: 20rpx;
  font-weight: 700;
  line-height: 1;
}

.goods-qr-tip {
  position: absolute;
  left: 18rpx;
  right: 18rpx;
  bottom: 18rpx;
  padding: 12rpx 16rpx;
  border-radius: 16rpx;
  background: var(--sell-surface-bg);
  border: 1rpx solid var(--sell-soft-border);
  color: var(--sell-item-text);
  font-size: 20rpx;
  line-height: 1.45;
  text-align: center;
}

.goods-image {
  width: 100%;
  height: 100%;
}

.goods-body {
  padding: 22rpx;
}

.goods-recent-badge {
  display: inline-flex;
  margin-bottom: 14rpx;
  padding: 8rpx 18rpx;
  border-radius: 999rpx;
  background: var(--sell-badge-bg);
  color: var(--sell-badge-text);
  font-size: 22rpx;
  font-weight: 700;
}

.goods-title {
  font-size: 28rpx;
  font-weight: 600;
  color: var(--sell-item-title);
  line-height: 1.4;
}

.goods-spec {
  margin-top: 10rpx;
  font-size: 22rpx;
  color: var(--sell-item-text);
}

.goods-card.is-qr-like .goods-title {
  color: var(--sell-title);
}

.goods-card.is-qr-like .goods-spec {
  color: var(--sell-muted);
}

.goods-meta {
  align-items: center;
  gap: 12rpx;
  margin-top: 18rpx;
}

.goods-price {
  font-size: 30rpx;
  font-weight: 700;
  color: var(--sell-price);
}

.goods-sales {
  font-size: 22rpx;
  color: var(--sell-item-muted);
}

.goods-tags {
  min-height: 34rpx;
  margin-top: 14rpx;
}

.goods-tag {
  display: inline-block;
  padding: 8rpx 16rpx;
  border-radius: 999rpx;
  background: var(--sell-badge-bg);
  color: var(--sell-badge-text);
  font-size: 20rpx;
}

.goods-merchant {
  margin-top: 14rpx;
  font-size: 22rpx;
  color: var(--sell-item-muted);
}

.loading-box,
.empty-state,
.empty-inline {
  padding: 36rpx 0 10rpx;
  text-align: center;
  font-size: 24rpx;
  color: var(--sell-muted);
}

/* #ifdef MP-WEIXIN */
.hero-metric-cell {
  padding: 0 8rpx 16rpx;
}

.filter-input {
  min-width: 0;
}

.filter-actions {
  margin: 18rpx -8rpx 0;
}

.filter-btn {
  flex: 1;
  min-width: 0;
  margin: 0 8rpx;
}

.goods-type-section {
  padding-top: 22rpx;
  padding-bottom: 18rpx;
}

.goods-type-section .chip-scroll {
  margin-top: 14rpx;
}

.goods-type-section .chip-row {
  margin-top: 0;
  padding: 0 4rpx 2rpx 0;
}

.goods-grid {
  margin: 22rpx -8rpx 0;
}

.goods-grid-cell {
  padding: 0 8rpx 16rpx;
}

.goods-image {
  height: 100%;
}
/* #endif */
</style>
