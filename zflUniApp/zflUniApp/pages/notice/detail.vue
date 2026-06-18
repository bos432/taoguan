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
    <view class="notice-detail-page">
      <view class="section-card env-card">
        <view class="section-head">
          <view class="section-title">当前环境</view>
          <view
            class="env-badge"
            :class="currentEnvInfo.is_prod ? 'is-prod' : 'is-test'"
            >{{ currentEnvInfo.label }}</view
          >
        </view>
        <view class="env-desc">{{ noticeEnvDescription }}</view>
        <view class="env-tags">
          <text class="env-tag">{{ noticeModeText }}</text>
          <text class="env-tag">{{ noticeScopeText }}</text>
          <text class="env-tag">{{ envIsolationText }}</text>
          <text class="env-tag">{{ envReleaseStageText }}</text>
        </view>
        <view class="env-note">{{ noticeActionHint }}</view>
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

      <view class="section-card">
        <view class="section-title">查看前复核</view>
        <view class="recent-action-card__desc">{{ noticeReviewHint }}</view>
        <view class="env-tags">
          <text v-for="item in noticeReviewTags" :key="item" class="env-tag">{{
            item
          }}</text>
        </view>
        <view class="env-note">{{ noticeRiskHint }}</view>
      </view>

      <view v-if="recentActionSummary" class="section-card recent-action-card">
        <view class="section-title">最近操作</view>
        <view class="recent-action-card__desc">{{ recentActionSummary }}</view>
      </view>

      <view v-if="!hasRequiredParam" class="section-card" style="margin-top: 20rpx; border: 1rpx solid rgba(245, 158, 11, 0.18);">
        <view class="section-title">公告入口参数缺失</view>
        <view class="recent-action-card__desc">
          当前链接没有带公告编号，所以详情正文无法加载；现在会先显示承接提示。
        </view>
        <view class="env-note">
          建议从公告列表或消息入口重新进入，避免直接点到空链路。
        </view>
        <view style="display: flex; gap: 20rpx; margin-top: 20rpx;">
          <button style="flex: 1; height: 76rpx; line-height: 76rpx; border-radius: 999rpx; background: linear-gradient(135deg, #1f7ae0, #2356d3); color: #ffffff; font-size: 24rpx;" @tap="goHome">
            回到首页
          </button>
          <button style="flex: 1; height: 76rpx; line-height: 76rpx; border-radius: 999rpx; background: #edf2f7; color: #37506c; font-size: 24rpx;" @tap="goBackOrHome">
            返回上一页
          </button>
        </view>
      </view>

      <view class="meta-bar">
        <text class="meta-text">{{ info.create_time || "--" }}</text>
        <text class="meta-text">{{
          info.popup_scope_name || info.type_name || "公告"
        }}</text>
      </view>

      <view class="hero-card">
        <view class="title" :style="{ color: info.title_color || '#172333' }">{{
          info.title || "公告详情"
        }}</view>
        <view v-if="info.desc" class="desc">{{ info.desc }}</view>
        <image
          v-if="info.image_url"
          class="cover-image"
          :src="info.image_url"
          mode="aspectFill"
          @tap="previewCover"
        ></image>
      </view>

      <view class="content-card">
        <view class="html-content" v-html="renderedContent"></view>
      </view>
    </view>
  </view>
</template>

<script>
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
  data() {
    return {
      noticeId: 0,
      hasRequiredParam: true,
      info: {},
      recentActionSummary: "",
    };
  },
  computed: {
    currentEnvInfo() {
      return getCurrentEnvInfo();
    },
    envIsolationHealth() {
      return getEnvIsolationHealth();
    },
    renderedContent() {
      const content = this.info.content || "";
      return content.replace(
        /<img(.*?)style=\"(.*?)\"/g,
        '<img$1style="width: 100%;"',
      );
    },
    noticeEnvDescription() {
      return this.currentEnvInfo.is_prod
        ? "当前为正式环境，公告详情、封面图和正文展示都来自线上真实内容。"
        : `当前为${this.currentEnvInfo.label}，适合联调公告详情加载、正文展示和图片预览链路。`;
    },
    noticeModeText() {
      return this.noticeId ? `公告编号：${this.noticeId}` : "公告编号：未提供";
    },
    noticeScopeText() {
      return `公告范围：${this.info.popup_scope_name || this.info.type_name || "未加载"}`;
    },
    envIsolationText() {
      return getEnvIsolationTag(this.currentEnvInfo);
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
    noticeActionHint() {
      return this.currentEnvInfo.is_prod
        ? `正式环境下请确认当前查看的是线上公告正文。${getEnvIsolationHint(this.currentEnvInfo)}`
        : `建议在当前环境验证公告详情加载、封面预览和正文展示链路。${getEnvIsolationHint(this.currentEnvInfo)}`;
    },
    noticeReviewHint() {
      return this.currentEnvInfo.is_prod
        ? "当前公告详情已连接正式环境，标题、封面和正文都应按线上真实公告内容对待。"
        : "当前公告详情适合联调封面预览、正文展示和返回链路。";
    },
    noticeReviewTags() {
      return [
        this.noticeModeText,
        this.noticeScopeText,
        this.info.title ? "公告状态：已加载" : "公告状态：待加载",
      ];
    },
    noticeRiskHint() {
      return this.currentEnvInfo.is_prod
        ? "正式环境下公告标题、封面图和正文都属于真实运营内容，请避免把测试公告或草稿截图外发给用户。"
        : "当前环境仅用于联调公告详情展示和图片预览，不要把测试公告当作正式内容。";
    },
  },
  onLoad(options) {
    this.noticeId = Number(options.notice_id || 0);
    if (!this.noticeId) {
      this.hasRequiredParam = false;
      this.recentActionSummary = "未提供公告标识，已切换到参数缺失承接态。";
      uni.setNavigationBarTitle({
        title: "公告详情",
      });
      return;
    }
    this.recentActionSummary = `准备加载公告详情：${this.noticeId}。`;
    this.getInfo();
  },
  methods: {
    goBackOrHome() {
      if (getCurrentPages().length > 1) {
        uni.navigateBack();
        return;
      }
      this.goHome();
    },
    goHome() {
      uni.switchTab({
        url: "/pages/app/home",
      });
    },
    updateRecentActionSummary(summary) {
      this.recentActionSummary = summary;
    },
    getInfo() {
      this.updateRecentActionSummary(`正在加载公告详情：${this.noticeId}。`);
      api
        .getNoticeInfo({ notice_id: this.noticeId })
        .then((res) => {
          this.info = res.data || {};
          uni.setNavigationBarTitle({
            title: this.info.title || "公告详情",
          });
          this.updateRecentActionSummary(
            `公告详情加载完成：${this.info.title || this.noticeId}。`,
          );
        })
        .catch(() => {
          this.updateRecentActionSummary(
            "公告详情加载失败，请确认当前环境接口和公告配置。",
          );
        });
    },
    previewCover() {
      if (!this.info.image_url) {
        return;
      }
      this.updateRecentActionSummary("准备预览公告封面。");
      uni.previewImage({
        urls: [this.info.image_url],
        current: this.info.image_url,
      });
    },
  },
};
</script>

<style lang="scss">
page {
  background: #f4f6f8;
}

.notice-detail-page {
  min-height: 100vh;
  padding: 24rpx;
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
  margin-top: 14rpx;
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

.meta-bar {
  display: flex;
  justify-content: space-between;
  margin: 18rpx 0;
  padding: 0 8rpx;
}

.meta-text {
  font-size: 22rpx;
  color: #7b8794;
}

.title {
  font-size: 38rpx;
  font-weight: 700;
  line-height: 1.45;
}

.desc {
  margin-top: 18rpx;
  color: #5b6675;
  font-size: 26rpx;
  line-height: 1.7;
}

.cover-image {
  width: 100%;
  height: 320rpx;
  border-radius: 20rpx;
  margin-top: 24rpx;
}

.content-card {
  margin-top: 20rpx;
}

.html-content {
  line-height: 1.8;
  color: #243142;
  font-size: 28rpx;
}

.html-content img {
  width: 100% !important;
  height: auto !important;
}

.html-content video {
  width: 100% !important;
}

.html-content pre {
  padding: 20rpx;
  border-radius: 18rpx;
  background: #0f1720;
  color: #f8fafc;
  overflow-x: auto;
  white-space: pre-wrap;
  word-break: break-all;
}

.html-content code {
  font-family: Consolas, Monaco, monospace;
}
</style>

