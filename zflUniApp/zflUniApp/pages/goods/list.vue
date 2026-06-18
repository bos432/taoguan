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
    <view class="goods-page">
      <view class="hero-card">
        <view class="hero-main">
          <view>
            <view class="hero-title">{{ searchTitle }}</view>
            <view class="hero-subtitle">{{
              filterSummary || "支持关键字、单金额、区间金额筛选"
            }}</view>
          </view>
          <view class="hero-date">共 {{ displayList.length }} 件</view>
        </view>

        <view class="hero-search" @tap="searchTap">
          <text class="cuIcon-search"></text>
          <text class="hero-search-text">返回搜索页重新输入条件</text>
        </view>
      </view>

      <view class="section-card env-card">
        <view class="section-head env-card__head">
          <view class="section-title">当前环境</view>
          <view
            class="env-badge"
            :class="currentEnvInfo.is_prod ? 'is-prod' : 'is-test'"
          >
            {{ currentEnvInfo.label }}
          </view>
        </view>
        <view class="env-desc">{{ envDescription }}</view>
        <view class="env-meta">
          <text class="env-tag">{{ searchModeText }}</text>
          <text class="env-tag">{{ resultCountText }}</text>
          <text class="env-tag">{{ envIsolationText }}</text>
          <text class="env-tag">{{ envIsolationStatusText }}</text>
          <text class="env-tag">{{ envReleaseStageText }}</text>
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
        <view class="env-url">{{
          currentEnvInfo.api_root_url || "未配置接口地址"
        }}</view>
      </view>

      <view v-if="recentActionSummary" class="section-card recent-action-card">
        <view class="section-title">最近操作</view>
        <view class="recent-action-card__desc">{{ recentActionSummary }}</view>
      </view>

      <view class="section-card active-filter-card">
        <view class="section-head">
          <view class="section-title">结果跟进</view>
          <view class="section-tip">{{ resultFollowupBadgeText }}</view>
        </view>
        <view class="recent-action-card__desc">{{ resultFollowupHint }}</view>
        <view class="chip-row wrap active-filter-row">
          <view
            v-for="item in resultFollowupTags"
            :key="item"
            class="active-filter-chip"
          >
            <text class="active-filter-text">{{ item }}</text>
          </view>
        </view>
        <view class="filter-summary">{{ resultFollowupRiskText }}</view>
      </view>

      <view class="section-card">
        <view class="section-head">
          <view class="section-title">结果筛选</view>
          <view class="section-tip">支持关键字与金额组合筛选</view>
        </view>

        <view class="keyword-row">
          <input
            v-model="keywordInput"
            class="keyword-input"
            type="text"
            placeholder="输入商品关键字，例如 苹果 / 华为 / 牛肉"
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
          <view class="filter-btn primary" @tap="applyAllFilters"
            >立即筛选</view
          >
          <view class="filter-btn" @tap="clearAllFilters">清空条件</view>
        </view>

        <view v-if="filterSummary" class="filter-summary">{{
          filterSummary
        }}</view>
      </view>

      <view
        v-if="activeFilterTags.length"
        class="section-card active-filter-card"
      >
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

      <view class="section-card goods-section">
        <view class="section-head">
          <view class="section-title">商品结果</view>
          <view class="section-tip">当前显示 {{ displayList.length }} 件</view>
        </view>

        <view v-if="displayList.length" class="goods-grid">
          <view
            v-for="item in displayList"
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
                <text class="goods-price">￥{{ formatMoney(item.price) }}</text>
                <text class="goods-sales"
                  >已售 {{ item.sales_sum || 0 }}{{ item.unit || "件" }}</text
                >
              </view>
              <view class="goods-tags">
                <text v-if="item.label_title" class="goods-tag">{{
                  item.label_title
                }}</text>
              </view>
              <view class="goods-merchant">{{
                merchantTitleText(item.merchant_title, "平台直营")
              }}</view>
            </view>
          </view>
        </view>

        <view v-else-if="!isLoad" class="empty-state">当前条件下暂无商品</view>
      </view>

      <view class="loading-box" v-if="isLoad">加载中...</view>
      <view class="page-bottom-space"></view>
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
  getEnvIsolationTag,
  getEnvReleaseHint,
  getEnvReleaseStageText,
} from "@/utils/env-risk.js";
import { resolveImageUrl as resolveSafeImageUrl } from "@/utils/resource.js";

export default {
  name: "goods-list",
  data() {
    return {
      list: [],
      keywordInput: "",
      minPriceInput: "",
      maxPriceInput: "",
      defaultImage: "/static/images/avatar/1.jpg",
      query: {
        page: 1,
        limit: 10,
        sort_field: null,
        sort_value: null,
        keyword: "",
        exact_price: 0,
        min_price: 0,
        max_price: 0,
        merchant_id: undefined,
      },
      count: 0,
      pages: 0,
      isLoad: false,
      recentActionSummary: "",
    };
  },
  computed: {
    currentEnvInfo() {
      return getCurrentEnvInfo();
    },
    envDescription() {
      return this.currentEnvInfo.is_prod
        ? "当前为正式环境，商品浏览、下单跳转和详情访问都会连接线上真实数据。"
        : `当前为${this.currentEnvInfo.label}，适合做商品检索、筛选、详情和下单链路联调。`;
    },
    searchModeText() {
      return this.query.keyword ? "当前模式：关键词检索" : "当前模式：列表浏览";
    },
    resultCountText() {
      return `结果数量：${this.displayList.length || 0} 件`;
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
        ? `从搜索结果继续进入商品详情和下单链路会连接真实数据。${getEnvIsolationHint(this.currentEnvInfo)}`
        : `建议在当前环境验证搜索、筛选、翻页和详情跳转链路。${getEnvIsolationHint(this.currentEnvInfo)}`;
    },
    displayList() {
      return this.list.filter((item) => {
        const keyword = String(this.query.keyword || "")
          .trim()
          .toLowerCase();
        if (keyword) {
          const haystack = [
            item.title,
            item.spec,
            item.merchant_title,
            item.label_title,
          ]
            .join(" ")
            .toLowerCase();
          if (!haystack.includes(keyword)) {
            return false;
          }
        }

        const price = Number(item.price || 0);
        if (
          this.query.exact_price > 0 &&
          price !== Number(this.query.exact_price)
        ) {
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
    searchTitle() {
      if (this.query.keyword) {
        return this.query.keyword;
      }
      if (this.query.exact_price > 0) {
        return "金额 ￥" + this.formatMoney(this.query.exact_price);
      }
      if (this.query.min_price > 0 || this.query.max_price > 0) {
        return "金额区间筛选";
      }
      return "商品列表";
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

      return tags;
    },
    resultFollowupBadgeText() {
      if (this.isLoad) {
        return "加载中";
      }
      if (!this.displayList.length) {
        return "待调整条件";
      }
      return "可继续查看";
    },
    resultFollowupHint() {
      if (this.isLoad) {
        return "当前正在拉取商品结果，建议等待本页加载完成后再继续细化条件。";
      }
      if (!this.displayList.length) {
        return "当前条件下暂无商品，建议放宽金额区间、删除部分筛选或回搜索页重新输入。";
      }
      return "当前结果已可继续查看，建议优先进入商品详情核对库存、商家归属和后续下单入口。";
    },
    resultFollowupTags() {
      return [
        `当前页码：${this.query.page}`,
        `累计结果：${this.list.length} 件`,
        `命中结果：${this.displayList.length} 件`,
        this.filterSummary || "当前条件：默认列表",
      ];
    },
    resultFollowupRiskText() {
      if (!this.displayList.length) {
        return "当前结果为空时，不建议继续盲目翻页，先调整关键字或金额条件更有效。";
      }
      return this.currentEnvInfo.is_prod
        ? "当前为正式环境，从结果页继续进入详情和下单链路会连接真实数据，请以库存和商品状态回显为准。"
        : "当前为非正式环境，建议继续联调筛选、翻页、详情跳转和返回列表后的状态保持。";
    },
  },
  onLoad(options) {
    if (options.key) {
      this.query.keyword = decodeURIComponent(options.key);
      this.keywordInput = this.query.keyword;
    }
    if (options.price) {
      const price = this.normalizeNumber(options.price);
      this.query.exact_price = price;
      this.minPriceInput = price ? this.formatMoney(price) : "";
      this.maxPriceInput = price ? this.formatMoney(price) : "";
    }
    if (options.min_price || options.max_price) {
      const minPrice = this.normalizeNumber(options.min_price);
      const maxPrice = this.normalizeNumber(options.max_price);
      this.query.min_price = minPrice;
      this.query.max_price = maxPrice;
      this.minPriceInput = minPrice ? this.formatMoney(minPrice) : "";
      this.maxPriceInput = maxPrice ? this.formatMoney(maxPrice) : "";
    }
    if (options.merchant_id) {
      this.query.merchant_id = options.merchant_id;
    }
    this.updateRecentActionSummary(
      this.filterSummary
        ? `准备加载商品列表，初始条件为：${this.filterSummary}。`
        : "准备加载商品列表，可继续使用关键字或金额条件筛选。",
    );
    this.getList();
  },
  onReachBottom() {
    if (this.query.page < this.pages) {
      this.query.page += 1;
      this.updateRecentActionSummary(
        `准备加载第 ${this.query.page} 页商品结果。`,
      );
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
        this.updateRecentActionSummary("商品信息异常，无法进入详情页。");
        uni.showToast({
          icon: "none",
          title: "商品信息异常",
        });
        return;
      }
      this.updateRecentActionSummary(
        `准备进入商品详情：${item.title || "未命名商品"}。`,
      );
      this.jump(`/pages/goods/details?goods_id=${goodsId}`);
    },
    searchTap() {
      this.updateRecentActionSummary("准备返回搜索页，重新输入商品条件。");
      uni.navigateTo({
        url: "/pages/home/search",
      });
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
        this.updateRecentActionSummary(
          "筛选未执行：当前没有输入关键字或金额条件。",
        );
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
          this.minPriceInput = parsed.min_price
            ? this.formatMoney(parsed.min_price)
            : "";
          this.maxPriceInput = parsed.max_price
            ? this.formatMoney(parsed.max_price)
            : "";
        }
      }

      const summaryParts = [];
      if (keyword) {
        summaryParts.push(`关键字“${keyword}”`);
      }
      if (parsed) {
        if (parsed.type === "exact") {
          summaryParts.push(`金额￥${this.formatMoney(parsed.exact_price)}`);
        } else {
          summaryParts.push(
            `金额区间￥${this.formatMoney(parsed.min_price || 0)} - ￥${this.formatMoney(parsed.max_price || 0)}`,
          );
        }
      }
      this.updateRecentActionSummary(
        summaryParts.length
          ? `已提交筛选条件：${summaryParts.join("，")}。`
          : "已提交筛选条件。",
      );
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
      this.updateRecentActionSummary("已清空商品筛选条件，恢复默认列表。");
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

      this.updateRecentActionSummary(
        `已移除筛选项：${item.label || "未命名条件"}。`,
      );
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
    getList() {
      this.isLoad = true;
      this.updateRecentActionSummary(
        this.query.page > 1
          ? `正在加载第 ${this.query.page} 页商品结果。`
          : this.filterSummary
            ? `正在按条件加载商品列表：${this.filterSummary}。`
            : "正在加载默认商品列表。",
      );
      api
        .getGoodsList(this.query)
        .then((res) => {
          const dataList = (res.data && res.data.list) || [];
          this.list =
            this.query.page === 1 ? dataList : this.list.concat(dataList);
          this.count = (res.data && res.data.count) || 0;
          this.pages = (res.data && res.data.pages) || 0;
          this.updateRecentActionSummary(
            this.query.page > 1
              ? `第 ${this.query.page} 页加载完成，当前累计 ${this.list.length} 件商品。`
              : this.filterSummary
                ? `筛选完成，当前命中 ${this.displayList.length} 件商品。`
                : `商品列表加载完成，当前共有 ${this.displayList.length} 件商品。`,
          );
        })
        .catch(() => {
          this.updateRecentActionSummary(
            "商品列表加载失败，请确认当前环境接口和筛选条件。",
          );
        })
        .finally(() => {
          this.isLoad = false;
        });
    },
    jump(url) {
      uni.navigateTo({
        url,
      });
    },
    formatMoney(value) {
      return Number(value || 0).toFixed(2);
    },
  },
};
</script>

<style scoped lang="scss">
.goods-page {
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
.section-card {
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
  color: rgba(255, 255, 255, 0.82);
}

.hero-date {
  padding: 12rpx 18rpx;
  border-radius: 999rpx;
  background: rgba(255, 255, 255, 0.14);
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
  background: rgba(255, 255, 255, 0.14);
}

.hero-search-text {
  font-size: 26rpx;
  color: rgba(255, 255, 255, 0.9);
}

.section-card {
  margin-top: 20rpx;
  padding: 28rpx;
}

.recent-action-card__desc {
  margin-top: 12rpx;
  color: #5b6b7b;
  font-size: 22rpx;
  line-height: 1.7;
}

.env-card__head {
  align-items: center;
}

.env-badge {
  min-width: 124rpx;
  padding: 8rpx 18rpx;
  border-radius: 999rpx;
  text-align: center;
  font-size: 22rpx;
  font-weight: 600;
}

.env-badge.is-prod {
  color: #a53b12;
  background: rgba(255, 227, 214, 0.92);
}

.env-badge.is-test {
  color: #155e75;
  background: rgba(220, 246, 255, 0.92);
}

.env-desc {
  margin-top: 14rpx;
  font-size: 24rpx;
  line-height: 1.6;
  color: #5b6876;
}

.env-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 12rpx;
  margin-top: 14rpx;
}

.env-tag {
  display: inline-flex;
  align-items: center;
  min-height: 42rpx;
  padding: 0 18rpx;
  border-radius: 999rpx;
  background: rgba(30, 92, 136, 0.08);
  color: #1c5c88;
  font-size: 21rpx;
  font-weight: 600;
}

.env-note {
  margin-top: 14rpx;
  padding: 18rpx 20rpx;
  border-radius: 18rpx;
  background: linear-gradient(180deg, #f7fafc 0%, #eef3f7 100%);
  color: #5f6f82;
  font-size: 22rpx;
  line-height: 1.65;
}

.env-note--strong {
  background: rgba(28, 92, 136, 0.08);
  color: #35506b;
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
  margin-top: 12rpx;
  font-size: 22rpx;
  line-height: 1.5;
  word-break: break-all;
  color: #8a97a6;
}

.env-profile-board {
  margin-top: 14rpx;
  padding: 16rpx 18rpx;
  border-radius: 20rpx;
  background: rgba(21, 75, 114, 0.04);
}

.env-profile-board__title {
  font-size: 24rpx;
  font-weight: 700;
  color: #10283d;
}

.env-profile-board__list {
  display: flex;
  flex-direction: column;
  gap: 12rpx;
  margin-top: 14rpx;
}

.env-profile-board__item {
  padding: 16rpx 18rpx;
  border-radius: 18rpx;
  background: rgba(255, 255, 255, 0.92);
}

.env-profile-board__item.is-current {
  border: 1rpx solid rgba(0, 129, 255, 0.22);
}

.env-profile-board__item-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12rpx;
}

.env-profile-board__item-name {
  font-size: 22rpx;
  color: #23405d;
  font-weight: 600;
}

.env-profile-board__item-status {
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

.env-profile-board__item-status.is-ready {
  background: linear-gradient(45deg, #ef4444, #f97316);
}

.env-profile-board__item-desc {
  margin-top: 8rpx;
  font-size: 21rpx;
  line-height: 1.7;
  color: #6b7b8c;
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

.keyword-row {
  margin-top: 22rpx;
}

.keyword-input,
.filter-input {
  width: 100%;
  height: 82rpx;
  padding: 0 24rpx;
  border-radius: 20rpx;
  background: linear-gradient(180deg, #f7fafc 0%, #eef3f7 100%);
  font-size: 28rpx;
  color: #192636;
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
}

.filter-separator {
  color: #5f6e81;
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
  background: #edf1f5;
  color: #4e5d70;
  font-size: 26rpx;
  line-height: 78rpx;
  text-align: center;
}

.filter-btn.primary {
  background: linear-gradient(90deg, #1c5c88 0%, #ec8a57 100%);
  color: #ffffff;
}

.filter-summary {
  margin-top: 18rpx;
  padding: 16rpx 20rpx;
  border-radius: 18rpx;
  background: #fff4ee;
  color: #b14b25;
  font-size: 24rpx;
}

.active-filter-card {
  background: linear-gradient(
    180deg,
    rgba(255, 244, 238, 0.96) 0%,
    rgba(255, 255, 255, 0.98) 100%
  );
}

.chip-row {
  display: flex;
  gap: 16rpx;
  margin-top: 22rpx;
}

.chip-row.wrap {
  flex-wrap: wrap;
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
  background: rgba(236, 138, 87, 0.12);
  color: #b14b25;
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

.goods-section {
  padding-bottom: 12rpx;
}

.goods-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 20rpx;
  margin-top: 22rpx;
}

.goods-card {
  overflow: hidden;
  border-radius: 26rpx;
  background: linear-gradient(180deg, #f9fbfc 0%, #f0f4f7 100%);
  box-shadow: inset 0 1rpx 0 rgba(255, 255, 255, 0.8);
}

.goods-image {
  width: 100%;
  height: 280rpx;
}

.goods-body {
  padding: 22rpx;
}

.goods-title {
  font-size: 28rpx;
  font-weight: 600;
  color: #152131;
  line-height: 1.4;
}

.goods-spec {
  margin-top: 10rpx;
  font-size: 22rpx;
  color: #718093;
}

.goods-meta {
  align-items: center;
  gap: 12rpx;
  margin-top: 18rpx;
}

.goods-price {
  font-size: 30rpx;
  font-weight: 700;
  color: #cf5a48;
}

.goods-sales {
  font-size: 22rpx;
  color: #667487;
}

.goods-tags {
  min-height: 34rpx;
  margin-top: 14rpx;
}

.goods-tag {
  display: inline-block;
  padding: 8rpx 16rpx;
  border-radius: 999rpx;
  background: rgba(28, 92, 136, 0.08);
  color: #1c5c88;
  font-size: 20rpx;
}

.goods-merchant {
  margin-top: 14rpx;
  font-size: 22rpx;
  color: #576679;
}

.loading-box,
.empty-state {
  padding: 36rpx 0 10rpx;
  text-align: center;
  font-size: 24rpx;
  color: #7f8a99;
}

.page-bottom-space {
  height: calc(40rpx + env(safe-area-inset-bottom));
}
</style>

