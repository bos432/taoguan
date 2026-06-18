<template>
  <view class="news-list-page">
    <view class="section-card env-card">
      <view class="section-head">
        <view class="section-title">当前环境</view>
        <view
          class="env-badge"
          :class="currentEnvInfo.is_prod ? 'is-prod' : 'is-test'"
          >{{ currentEnvInfo.label }}</view
        >
      </view>
      <view class="env-desc">{{ newsEnvDescription }}</view>
      <view class="env-tags">
        <text class="env-tag">{{ newsCategoryText }}</text>
        <text class="env-tag">{{ newsCountText }}</text>
        <text class="env-tag">{{ envIsolationText }}</text>
        <text class="env-tag">{{ envIsolationStatusText }}</text>
        <text class="env-tag">{{ envReleaseStageText }}</text>
      </view>
      <view class="env-note">{{ newsActionHint }}</view>
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
      <view class="recent-action-card__desc">{{ newsReviewHint }}</view>
      <view class="env-tags">
        <text v-for="item in newsReviewTags" :key="item" class="env-tag">{{
          item
        }}</text>
      </view>
      <view class="env-note">{{ newsRiskHint }}</view>
    </view>

    <view v-if="recentActionSummary" class="section-card recent-action-card">
      <view class="section-title">最近操作</view>
      <view class="recent-action-card__desc">{{ recentActionSummary }}</view>
    </view>

    <view class="zaiui-topic-card-list-box">
      <view class="cu-list menu-avatar margin-tb-sm">
        <block v-for="(item, index) in list" :key="item.content_id">
          <view class="cu-item" @tap="openNewsDetail(item)">
            <image :src="item.image_url" class="cu-avatar lg"></image>
            <view
              class="corner-mark text-sm text-bold one"
              v-if="index === 0"
              >{{ index + 1 }}</view
            >
            <view
              class="corner-mark text-sm text-bold two"
              v-else-if="index === 1"
              >{{ index + 1 }}</view
            >
            <view
              class="corner-mark text-sm text-bold three"
              v-else-if="index === 2"
              >{{ index + 1 }}</view
            >
            <view class="corner-mark text-sm text-bold" v-else>{{
              index + 1
            }}</view>
            <view class="content">
              <view class="text-black">
                <view class="text-cut-2">{{ item.title }}</view>
              </view>
              <view class="text-gray text-sm flex lrflex">
                <view class="text-cut">{{ item.text }}</view>
                <view class="text-cut">{{ item.create_time }}</view>
              </view>
            </view>
          </view>
        </block>
      </view>
    </view>
    <view
      class="cu-load"
      :class="isLoad ? 'loading' : list.length <= 0 ? 'over' : ''"
    ></view>
  </view>
</template>

<script>
import barTitle from "@/components/zaiui-common/basics/bar-title";
import api from "@/api";
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
  name: "news-list",
  components: {
    barTitle,
  },
  data() {
    return {
      query: {
        page: 1,
        limit: 20,
        category_id: 1,
      },
      list: [],
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
    newsEnvDescription() {
      return this.currentEnvInfo.is_prod
        ? "当前为正式环境，资讯列表和详情展示都来自线上真实内容。"
        : `当前为${this.currentEnvInfo.label}，适合联调资讯列表加载、翻页和详情跳转链路。`;
    },
    newsCategoryText() {
      return this.query.category_id === 1
        ? "当前栏目：政策解读"
        : "当前栏目：平台资讯";
    },
    newsCountText() {
      return `当前数量：${this.list.length}`;
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
    newsActionHint() {
      return this.currentEnvInfo.is_prod
        ? `正式环境下请确认当前资讯列表和详情内容为线上真实数据。${getEnvIsolationHint(this.currentEnvInfo)}`
        : `建议在当前环境验证资讯列表加载、上拉翻页和详情跳转链路。${getEnvIsolationHint(this.currentEnvInfo)}`;
    },
    newsReviewHint() {
      return this.currentEnvInfo.is_prod
        ? "当前资讯列表已连接正式环境，查看、截图和外发前请先确认栏目与内容均为线上真实信息。"
        : "当前资讯列表适合联调列表加载、翻页和详情跳转。";
    },
    newsReviewTags() {
      return [
        this.newsCategoryText,
        this.newsCountText,
        this.list.length ? "内容状态：已加载" : "内容状态：待加载",
      ];
    },
    newsRiskHint() {
      return this.currentEnvInfo.is_prod
        ? "正式环境下资讯标题、摘要和详情链接都属于真实内容，请避免把测试栏目或草稿数据外发给用户。"
        : "当前环境仅用于联调资讯列表和详情跳转，不要把测试内容当作正式运营资讯。";
    },
  },
  onLoad(options) {
    if (options.category_id) {
      this.query.category_id = Number(options.category_id);
    }

    uni.setNavigationBarTitle({
      title: this.query.category_id === 1 ? "政策解读" : "平台资讯",
    });
    this.recentActionSummary = `进入${this.query.category_id === 1 ? "政策解读" : "平台资讯"}列表，准备加载内容。`;
    this.getList();
  },
  onReachBottom() {
    if (this.query.page < this.pages) {
      this.query.page += 1;
      this.recentActionSummary = `准备加载第 ${this.query.page} 页资讯内容。`;
      this.getList();
    }
  },
  methods: {
    updateRecentActionSummary(summary) {
      this.recentActionSummary = summary;
    },
    jump(url) {
      uni.navigateTo({
        url,
      });
    },
    openNewsDetail(item = {}) {
      const contentId = Number(item.content_id || 0);
      if (!contentId) {
        this.updateRecentActionSummary("资讯内容异常，无法进入详情页。");
        return;
      }
      this.updateRecentActionSummary(
        `准备查看资讯详情：${item.title || contentId}。`,
      );
      this.jump("/pages/news/details?id=" + contentId);
    },
    getList() {
      this.isLoad = true;
      this.updateRecentActionSummary(
        this.query.page > 1
          ? `正在加载第 ${this.query.page} 页资讯内容。`
          : `正在加载${this.query.category_id === 1 ? "政策解读" : "平台资讯"}列表。`,
      );
      api
        .getContentList(this.query)
        .then((res) => {
          const data = res.data || {};
          const currentList = data.list || [];
          if (this.query.page === 1) {
            this.list = currentList;
          } else {
            this.list = this.list.concat(currentList);
          }
          this.count = data.count || 0;
          this.pages = data.pages || 0;
          this.updateRecentActionSummary(
            this.query.page > 1
              ? `第 ${this.query.page} 页加载完成，当前累计 ${this.list.length} 条资讯。`
              : this.list.length
                ? `资讯列表加载完成，当前共有 ${this.list.length} 条内容。`
                : "资讯列表加载完成，但当前没有可展示内容。",
          );
        })
        .catch(() => {
          this.updateRecentActionSummary(
            "资讯列表加载失败，请确认当前环境接口和内容配置。",
          );
        })
        .finally(() => {
          this.isLoad = false;
        });
    },
  },
};
</script>

<style scoped lang="scss">
page {
  background: #ffffff;
}

.news-list-page {
  min-height: 100vh;
  padding: 24rpx 0 0;
  background:
    radial-gradient(
      circle at top right,
      rgba(243, 143, 90, 0.12),
      transparent 28%
    ),
    linear-gradient(180deg, #f8fbfd 0%, #ffffff 20%, #ffffff 100%);
}

.section-card {
  margin: 0 24rpx 20rpx;
  padding: 28rpx;
  background: #ffffff;
  border-radius: 24rpx;
  box-shadow: 0 18rpx 42rpx rgba(21, 33, 52, 0.08);
}

.section-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 20rpx;
}

.section-title {
  font-size: 30rpx;
  font-weight: 700;
  color: #162233;
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

.env-desc,
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

.zaiui-topic-card-list-box {
  margin-top: 4rpx;

  .cu-list.menu-avatar > .cu-item > .cu-avatar {
    left: 14px;
    width: 58px;
    height: 58px;
    border-radius: 9.09rpx;
  }

  .cu-list.menu-avatar > .cu-item {
    height: 75px;

    &:after {
      width: 0;
      height: 0;
      border-bottom: 0;
    }

    .corner-mark {
      position: absolute;
      width: 29.09rpx;
      height: 29.09rpx;
      text-align: center;
      top: 14.54rpx;
      left: 27.27rpx;
      border-radius: 7.27rpx 0 9.09rpx;
      background: rgba(0, 0, 0, 0.55);
      color: #ffffff;
      line-height: 29.09rpx;
    }

    .corner-mark.one {
      background: #ef5350;
    }

    .corner-mark.two {
      background: #fb8c00;
    }

    .corner-mark.three {
      background: #42a5f5;
    }
  }
}

.lrflex {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.cu-load.over::after {
  content: "暂无数据";
}
</style>

