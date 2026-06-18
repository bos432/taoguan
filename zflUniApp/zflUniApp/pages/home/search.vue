<template>
  <view
    class="search-page"
    :class="
      'ui-theme-' +
      (($store.state.setting &&
        $store.state.setting.system &&
        $store.state.setting.system.ui_theme_style) ||
        'origin')
    "
  >
    <view class="hero-card">
      <view class="hero-top">
        <view class="hero-back" @tap="BackPage">
          <text class="cuIcon-back"></text>
          <text>返回</text>
        </view>
        <view class="hero-badge">搜索中心</view>
      </view>
      <view class="hero-title">快速搜索商品</view>
      <view class="hero-subtitle">支持关键字、精确金额和区间金额搜索</view>
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
      <view class="section-tip env-desc">{{ searchEnvDescription }}</view>
      <view class="env-tags">
        <text class="env-tag">{{ searchModeText }}</text>
        <text class="env-tag">{{ searchRouteText }}</text>
        <text class="env-tag">{{ envIsolationText }}</text>
        <text class="env-tag">{{ envIsolationStatusText }}</text>
        <text class="env-tag">{{ envReleaseStageText }}</text>
      </view>
      <view class="env-note">{{ searchActionHint }}</view>
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
              <text class="env-profile-board__item-name">{{ item.label }}</text>
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

    <view class="section-card">
      <view class="section-title">查看前复核</view>
      <view class="recent-action-card__desc">{{ searchReviewHint }}</view>
      <view class="env-tags">
        <text v-for="item in searchReviewTags" :key="item" class="env-tag">{{
          item
        }}</text>
      </view>
      <view class="env-note">{{ searchReviewRisk }}</view>
    </view>

    <view v-if="recentActionSummary" class="section-card recent-action-card">
      <view class="section-title">最近操作</view>
      <view class="recent-action-card__desc">{{ recentActionSummary }}</view>
    </view>

    <view class="section-card helper-card">
      <view class="section-head">
        <view class="section-title">搜索后跟进</view>
        <view class="section-tip">{{ searchFollowupBadgeText }}</view>
      </view>
      <view class="recent-action-card__desc">{{ searchFollowupHint }}</view>
      <view class="env-tags">
        <text class="env-tag" v-for="item in searchFollowupTags" :key="item">{{
          item
        }}</text>
      </view>
      <view class="env-note">{{ searchFollowupRiskText }}</view>
    </view>

    <view class="section-card">
      <view class="section-head">
        <view class="section-title">输入条件</view>
        <view class="section-tip"
          >输入 413 查精确金额，输入 100-500 查金额区间</view
        >
      </view>

      <view class="search-input-row">
        <text class="cuIcon-search search-icon"></text>
        <input
          v-model="searchKey"
          class="search-input"
          :adjust-position="false"
          type="text"
          placeholder="搜索商品，例如 苹果 / 华为 / 413 / 100-500"
          confirm-type="search"
          focus
          @confirm="search"
        />
        <text
          v-if="searchKey"
          class="cuIcon-close close-icon"
          @tap="closeInput"
        ></text>
      </view>

      <view class="search-actions">
        <view class="action-btn primary" @tap="search">立即搜索</view>
        <view class="action-btn" @tap="closeInput">清空内容</view>
      </view>
    </view>

    <view class="section-card">
      <view class="section-head">
        <view class="section-title">推荐搜索</view>
        <view class="section-tip">点击标签可直接带入条件</view>
      </view>

      <view class="tag-row">
        <view
          v-for="item in quickTags"
          :key="item"
          class="quick-tag"
          @tap="tagClick(item)"
        >
          {{ item }}
        </view>
      </view>
    </view>

    <view class="section-card helper-card">
      <view class="section-head">
        <view class="section-title">搜索说明</view>
        <view class="section-tip">帮助你更快找到目标商品</view>
      </view>

      <view class="helper-list">
        <view class="helper-item">
          <text class="helper-dot"></text>
          <text class="helper-text"
            >输入关键字，例如“华为”或“牛肉”，按商品名称和相关信息搜索。</text
          >
        </view>
        <view class="helper-item">
          <text class="helper-dot"></text>
          <text class="helper-text"
            >输入纯数字，例如“413”，会按商品金额精确搜索。</text
          >
        </view>
        <view class="helper-item">
          <text class="helper-dot"></text>
          <text class="helper-text"
            >输入区间，例如“100-500”，会按金额范围搜索。</text
          >
        </view>
      </view>
    </view>
  </view>
</template>

<script>
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
      searchKey: "",
      quickTags: [
        "苹果17",
        "iPhone",
        "413",
        "100-500",
        "荣耀Magic6",
        "苹果16",
        "华为",
      ],
      recentActionSummary: "",
    };
  },
  computed: {
    currentEnvInfo() {
      return getCurrentEnvInfo();
    },
    searchEnvDescription() {
      return this.currentEnvInfo.is_prod
        ? "当前为正式环境，搜索结果、商品详情和后续下单链路都会连接线上真实数据。"
        : `当前为${this.currentEnvInfo.label}，适合联调商品搜索、筛选承接和详情跳转链路。`;
    },
    searchModeText() {
      return this.searchKey ? "当前模式：带条件搜索" : "当前模式：待输入";
    },
    searchRouteText() {
      return this.searchKey
        ? `当前条件：${this.searchKey}`
        : "当前条件：未输入";
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
    searchActionHint() {
      return this.currentEnvInfo.is_prod
        ? `当前搜索后进入商品列表与详情页会使用真实数据。${getEnvIsolationHint(this.currentEnvInfo)}`
        : `建议在当前环境验证搜索、跳转和列表承接链路。${getEnvIsolationHint(this.currentEnvInfo)}`;
    },
    searchReviewHint() {
      return this.currentEnvInfo.is_prod
        ? "当前搜索页已连接正式环境，搜索结果、商品详情和后续下单链路都会命中真实数据。"
        : "当前搜索页适合联调关键字搜索、金额搜索和商品列表承接。";
    },
    searchReviewTags() {
      return [
        this.searchModeText,
        this.searchRouteText,
        this.searchKey ? "搜索状态：可执行" : "搜索状态：待输入",
      ];
    },
    searchReviewRisk() {
      return this.currentEnvInfo.is_prod
        ? "正式环境下搜索后的商品列表和详情都属于真实数据，请避免把测试关键字或演示截图当作正式运营结果。"
        : "当前环境仅用于联调搜索与跳转，不要把测试搜索结果当作正式商品池展示。";
    },
    searchFollowupBadgeText() {
      return this.searchKey ? "可直接检索" : "待输入条件";
    },
    searchFollowupHint() {
      if (!this.searchKey) {
        return "当前还没有输入条件，建议先从推荐词或关键字开始，再逐步用金额做精确筛选。";
      }
      return "当前搜索条件已经就绪，下一步会进入商品列表页继续做结果筛选和详情跳转。";
    },
    searchFollowupTags() {
      return [
        this.searchKey ? `当前条件：${this.searchKey}` : "当前条件：未输入",
        "支持关键字检索",
        "支持精确金额",
        "支持金额区间",
      ];
    },
    searchFollowupRiskText() {
      if (!this.searchKey) {
        return "未输入条件时无法直接进入结果页，搜索动作会被拦截。";
      }
      return this.currentEnvInfo.is_prod
        ? "当前为正式环境，搜索后的商品结果和后续详情/下单入口都连接真实数据，请以实际商品状态为准继续操作。"
        : "当前为非正式环境，建议继续联调搜索页到商品列表、商品详情和下单链路的承接。";
    },
  },
  onLoad(options) {
    if (options.key) {
      this.searchKey = decodeURIComponent(options.key);
      this.updateRecentActionSummary(`已带入初始搜索条件：${this.searchKey}。`);
      return;
    }
    this.updateRecentActionSummary(
      "进入搜索中心，等待输入商品关键字或金额条件。",
    );
  },
  onReady() {
    uni.setNavigationBarColor({
      frontColor: "#ffffff",
      backgroundColor: "#154b72",
    });
    uni.pageScrollTo({
      scrollTop: 0,
      duration: 0,
    });
  },
  methods: {
    updateRecentActionSummary(summary) {
      this.recentActionSummary = summary;
    },
    tagClick(key) {
      this.searchKey = key;
      this.updateRecentActionSummary(`已选择推荐搜索：${key}。`);
      this.search();
    },
    parsePriceKeyword(keyword) {
      const exactMatch = keyword.match(/^(\d+(?:\.\d{1,2})?)$/);
      if (exactMatch) {
        return {
          type: "exact",
          exact_price: exactMatch[1],
        };
      }

      const rangeMatch = keyword.match(
        /^(\d+(?:\.\d{1,2})?)\s*-\s*(\d+(?:\.\d{1,2})?)$/,
      );
      if (rangeMatch) {
        const minPrice = Number(rangeMatch[1]);
        const maxPrice = Number(rangeMatch[2]);
        if (minPrice <= maxPrice) {
          return {
            type: "range",
            min_price: String(minPrice),
            max_price: String(maxPrice),
          };
        }
      }

      return null;
    },
    search() {
      const keyword = String(this.searchKey || "").trim();
      if (!keyword) {
        this.updateRecentActionSummary(
          "搜索未执行：当前没有输入关键字或金额条件。",
        );
        uni.showToast({
          title: "请输入搜索内容",
          icon: "none",
        });
        return;
      }

      const priceQuery = this.parsePriceKeyword(keyword);
      let url = "/pages/goods/list?key=" + encodeURIComponent(keyword);

      if (priceQuery) {
        if (priceQuery.type === "exact") {
          url =
            "/pages/goods/list?price=" +
            encodeURIComponent(priceQuery.exact_price);
          this.updateRecentActionSummary(
            `准备按精确金额搜索：￥${priceQuery.exact_price}。`,
          );
        } else {
          url =
            "/pages/goods/list?min_price=" +
            encodeURIComponent(priceQuery.min_price) +
            "&max_price=" +
            encodeURIComponent(priceQuery.max_price);
          this.updateRecentActionSummary(
            `准备按金额区间搜索：￥${priceQuery.min_price} - ￥${priceQuery.max_price}。`,
          );
        }
      } else {
        this.updateRecentActionSummary(`准备按关键字搜索：${keyword}。`);
      }

      uni.navigateTo({
        url,
      });
    },
    BackPage() {
      this.updateRecentActionSummary("准备返回上一页。");
      uni.navigateBack();
    },
    closeInput() {
      this.searchKey = "";
      this.updateRecentActionSummary("已清空搜索内容，可重新输入条件。");
    },
  },
};
</script>

<style scoped lang="scss">
.search-page {
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

.hero-top,
.section-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 20rpx;
}

.hero-back,
.hero-badge {
  display: inline-flex;
  align-items: center;
  gap: 10rpx;
  padding: 12rpx 18rpx;
  border-radius: 999rpx;
  background: rgba(255, 255, 255, 0.14);
  font-size: 22rpx;
}

.hero-title {
  margin-top: 28rpx;
  font-size: 40rpx;
  font-weight: 700;
}

.hero-subtitle {
  margin-top: 12rpx;
  font-size: 24rpx;
  color: rgba(255, 255, 255, 0.82);
}

.section-card {
  margin-top: 20rpx;
  padding: 28rpx;
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
  color: #18543b;
  background: rgba(223, 245, 232, 0.96);
}

.env-desc {
  margin-top: 10rpx;
  text-align: left;
}

.env-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 14rpx;
  margin-top: 22rpx;
}

.env-tag {
  padding: 10rpx 18rpx;
  border-radius: 999rpx;
  background: rgba(21, 75, 114, 0.08);
  color: #1d5279;
  font-size: 22rpx;
}

.env-note,
.env-url,
.recent-action-card__desc {
  margin-top: 14rpx;
  color: #5b6b7b;
  font-size: 22rpx;
  line-height: 1.7;
}

.env-note--strong {
  padding: 14rpx 16rpx;
  border-radius: 18rpx;
  background: rgba(21, 75, 114, 0.06);
  color: #35506b;
}

.env-risk-list {
  margin-top: 12rpx;
  padding: 14rpx 16rpx;
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
  word-break: break-all;
}

.env-profile-board {
  margin-top: 16rpx;
  padding: 18rpx 20rpx;
  border-radius: 20rpx;
  background: rgba(21, 75, 114, 0.05);
}

.env-profile-board__title {
  font-size: 24rpx;
  font-weight: 700;
  color: #162233;
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
  color: #162233;
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
  background: rgba(28, 92, 136, 0.12);
  color: #315f86;
}

.env-profile-board__item-status.is-hold {
  background: rgba(255, 244, 232, 0.98);
  color: #8c5145;
}

.env-profile-board__item-desc {
  margin-top: 10rpx;
  font-size: 21rpx;
  line-height: 1.7;
  color: #5b6b7b;
}

.section-title {
  font-size: 30rpx;
  font-weight: 700;
  color: #162233;
}

.section-tip {
  font-size: 22rpx;
  color: #7b8797;
  text-align: right;
}

.search-input-row {
  display: flex;
  align-items: center;
  gap: 16rpx;
  margin-top: 22rpx;
  padding: 0 24rpx;
  height: 92rpx;
  border-radius: 22rpx;
  background: linear-gradient(180deg, #f7fafc 0%, #eef3f7 100%);
}

.search-icon,
.close-icon {
  font-size: 30rpx;
  color: #6a7788;
}

.search-input {
  flex: 1;
  font-size: 28rpx;
  color: #192636;
}

.search-actions {
  display: flex;
  gap: 16rpx;
  margin-top: 20rpx;
}

.action-btn {
  min-width: 180rpx;
  height: 80rpx;
  padding: 0 28rpx;
  border-radius: 20rpx;
  background: #edf1f5;
  color: #4e5d70;
  font-size: 26rpx;
  line-height: 80rpx;
  text-align: center;
}

.action-btn.primary {
  background: linear-gradient(90deg, #1c5c88 0%, #ec8a57 100%);
  color: #ffffff;
}

.tag-row {
  display: flex;
  flex-wrap: wrap;
  gap: 16rpx;
  margin-top: 22rpx;
}

.quick-tag {
  padding: 14rpx 24rpx;
  border-radius: 999rpx;
  background: rgba(21, 75, 114, 0.08);
  color: #1c4f76;
  font-size: 24rpx;
}

.helper-card {
  background: linear-gradient(
    180deg,
    rgba(255, 244, 238, 0.96) 0%,
    rgba(255, 255, 255, 0.98) 100%
  );
}

.helper-list {
  margin-top: 20rpx;
}

.helper-item {
  display: flex;
  align-items: flex-start;
  gap: 14rpx;
}

.helper-item + .helper-item {
  margin-top: 18rpx;
}

.helper-dot {
  width: 14rpx;
  height: 14rpx;
  margin-top: 10rpx;
  border-radius: 50%;
  background: linear-gradient(135deg, #1c5c88 0%, #ec8a57 100%);
  flex-shrink: 0;
}

.helper-text {
  flex: 1;
  font-size: 24rpx;
  line-height: 1.7;
  color: #566577;
}
</style>

