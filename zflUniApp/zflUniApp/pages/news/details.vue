<template>
  <view class="news-detail-page">
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
        <text class="env-tag">{{ newsIdText }}</text>
        <text class="env-tag">{{ newsSourceText }}</text>
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

    <view v-if="!hasRequiredParam" class="section-card" style="margin-top: 18rpx; border: 1rpx solid rgba(245, 158, 11, 0.18);">
      <view class="section-title">资讯入口参数缺失</view>
      <view class="recent-action-card__desc">
        当前链接没有带资讯编号，所以正文无法加载；现在会直接给出承接提示，不再卡住。
      </view>
      <view class="env-note">
        建议从资讯列表重新进入，或回到首页继续查看其它链路。
      </view>
      <view style="display: flex; gap: 20rpx; margin-top: 20rpx;">
        <button style="flex: 1; height: 76rpx; line-height: 76rpx; border-radius: 999rpx; background: linear-gradient(135deg, #1f7ae0, #2356d3); color: #ffffff; font-size: 24rpx;" @tap="goNewsList">
          去资讯列表
        </button>
        <button style="flex: 1; height: 76rpx; line-height: 76rpx; border-radius: 999rpx; background: #edf2f7; color: #37506c; font-size: 24rpx;" @tap="goBackOrHome">
          返回上一页
        </button>
      </view>
    </view>

    <view
      class="lrflex text-gray text-sm text-right margin-lr"
      v-if="info.content_id"
    >
      <text v-if="info.source">{{ info.source }}</text>
      <text v-else>{{ info.hits_initial }} 人阅读</text>
      <text>{{ info.create_time }}</text>
    </view>

    <view class="section-card content-view-box">
      <view class="font-view" v-html="info.content"></view>
    </view>
    <view
      class="cu-load"
      :class="isLoad ? 'loading' : info.content_id ? '' : 'over'"
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
  components: {
    barTitle,
  },
  data() {
    return {
      id: 0,
      hasRequiredParam: true,
      isLoad: false,
      info: {},
      recentActionSummary: "",
    };
  },
  computed: {
    currentEnvInfo() {
      return getCurrentEnvInfo();
    },
    newsEnvDescription() {
      return this.currentEnvInfo.is_prod
        ? "当前为正式环境，资讯详情正文和阅读数据都来自线上真实内容。"
        : `当前为${this.currentEnvInfo.label}，适合联调资讯详情加载、正文展示和回退链路。`;
    },
    newsIdText() {
      return this.id ? `资讯编号：${this.id}` : "资讯编号：未提供";
    },
    newsSourceText() {
      return `来源：${this.info.source || "未加载"}`;
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
        ? `正式环境下请确认当前资讯正文为线上真实数据。${getEnvIsolationHint(this.currentEnvInfo)}`
        : `建议在当前环境验证资讯详情加载、正文展示和回退链路。${getEnvIsolationHint(this.currentEnvInfo)}`;
    },
    newsReviewHint() {
      return this.currentEnvInfo.is_prod
        ? "当前资讯详情已连接正式环境，正文、来源和阅读信息都应按线上真实内容对待。"
        : "当前资讯详情适合联调正文展示、图片适配和返回链路。";
    },
    newsReviewTags() {
      return [
        this.newsIdText,
        this.newsSourceText,
        this.info.content_id ? "正文状态：已加载" : "正文状态：待加载",
      ];
    },
    newsRiskHint() {
      return this.currentEnvInfo.is_prod
        ? "正式环境下资讯正文、图片和发布时间都属于真实内容，请避免把测试稿或演示截图当作正式资讯。"
        : "当前环境仅用于联调资讯详情页和图片适配，不要把测试内容当作正式运营素材。";
    },
  },
  onLoad(options) {
    if (options.id) {
      this.id = Number(options.id);
      this.recentActionSummary = `准备加载资讯详情：${this.id}。`;
      this.getInfo();
    } else {
      this.hasRequiredParam = false;
      this.recentActionSummary = "未提供资讯标识，已切换到参数缺失承接态。";
      uni.setNavigationBarTitle({
        title: "资讯详情",
      });
    }
  },
  methods: {
    goBackOrHome() {
      if (getCurrentPages().length > 1) {
        uni.navigateBack();
        return;
      }
      uni.switchTab({
        url: "/pages/app/home",
      });
    },
    goNewsList() {
      uni.navigateTo({
        url: "/pages/news/list",
      });
    },
    updateRecentActionSummary(summary) {
      this.recentActionSummary = summary;
    },
    getInfo() {
      this.isLoad = true;
      this.updateRecentActionSummary(`正在加载资讯详情：${this.id}。`);
      api
        .getContentInfo({ content_id: this.id })
        .then((res) => {
          this.info = res.data || {};
          uni.setNavigationBarTitle({
            title: this.info.title || "资讯详情",
          });
          if (this.info.content) {
            this.info.content = this.info.content.replace(
              /<img(.*?)style="(.*?)"/g,
              '<img$1style="width: 100%;"',
            );
          }
          this.updateRecentActionSummary(
            `资讯详情加载完成：${this.info.title || this.id}。`,
          );
        })
        .catch(() => {
          this.updateRecentActionSummary(
            "资讯详情加载失败，请确认当前环境接口和内容配置。",
          );
        })
        .finally(() => {
          this.isLoad = false;
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

page {
  background: #ffffff;
}

.news-detail-page {
  min-height: 100vh;
  padding: 24rpx;
  background:
    radial-gradient(
      circle at top right,
      rgba(243, 143, 90, 0.12),
      transparent 28%
    ),
    linear-gradient(180deg, #f8fbfd 0%, #ffffff 20%, #ffffff 100%);
}

.section-card {
  background: #ffffff;
  border-radius: 24rpx;
  padding: 28rpx;
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

.margin-lr {
  margin: 18rpx 8rpx 0;
}

.content-view-box {
  position: relative;
  margin-top: 18rpx;

  .font-view {
    line-height: 1.7;
  }

  image {
    width: 100%;
  }
}

.cu-load.over::after {
  content: "暂无数据";
}

.lrflex {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.font-view rich-text img {
  width: 100% !important;
  height: auto;
}
</style>

