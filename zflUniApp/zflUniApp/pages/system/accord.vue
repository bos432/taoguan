<template>
  <view class="accord-page">
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
        <text>{{ accordModeText }}</text>
        <text>{{ accordIdText }}</text>
        <text>{{ envIsolationText }}</text>
        <text>{{ envReleaseStageText }}</text>
        <text>{{ currentEnvInfo.api_root_url || "未配置接口地址" }}</text>
      </view>
      <view class="env-card__desc env-card__desc--strong">{{
        envReleaseHint
      }}</view>
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
    </view>

    <view class="review-card">
      <view class="review-card__title">查看前复核</view>
      <view class="review-card__desc">{{ accordReviewHint }}</view>
      <view class="review-card__tags">
        <text class="review-card__tag">{{ accordModeText }}</text>
        <text class="review-card__tag">{{ accordIdText }}</text>
        <text class="review-card__tag">{{ accordContentStatusText }}</text>
      </view>
      <view class="review-card__risk">{{ accordRiskHint }}</view>
    </view>

    <view v-if="recentActionSummary" class="action-card">
      <view class="action-card__title">最近操作</view>
      <view class="action-card__desc">{{ recentActionSummary }}</view>
    </view>

    <view class="action-card">
      <view class="action-card__title">上线承接提示</view>
      <view class="action-card__desc">{{ accordRolloutHint }}</view>
      <view class="action-card__checklist">
        <view
          v-for="item in accordRolloutChecklist"
          :key="item.label"
          class="action-card__check-item"
        >
          <text class="action-card__check-label">{{ item.label }}</text>
          <text class="action-card__check-value">{{ item.value }}</text>
        </view>
      </view>
      <view class="action-card__desc action-card__desc--strong">{{
        accordRollbackHint
      }}</view>
    </view>

    <view v-if="!hasRequiredParam" class="action-card" style="border: 1rpx solid rgba(245, 158, 11, 0.22); background: linear-gradient(180deg, rgba(255, 247, 237, 0.98) 0%, #ffffff 100%);">
      <view class="action-card__title">协议入口参数缺失</view>
      <view class="action-card__desc">
        这个页面需要带上协议标识才能打开正文；现在先做可见承接，不再直接空白返回。
      </view>
      <view class="action-card__desc action-card__desc--strong">
        建议从协议中心重新进入，或回到首页继续联调。
      </view>
      <view style="display: flex; gap: 20rpx; margin-top: 24rpx;">
        <button style="flex: 1; height: 76rpx; line-height: 76rpx; border-radius: 999rpx; background: linear-gradient(135deg, #1f7ae0, #2356d3); color: #ffffff; font-size: 24rpx;" @tap="goAccordCenter">
          去协议中心
        </button>
        <button style="flex: 1; height: 76rpx; line-height: 76rpx; border-radius: 999rpx; background: #eef3f7; color: #35506b; font-size: 24rpx;" @tap="goBackOrHome">
          返回上一页
        </button>
      </view>
    </view>

    <view v-if="info.name" class="accord-head">
      <view class="accord-title">{{ info.name }}</view>
      <view v-if="info.desc" class="accord-desc">{{ info.desc }}</view>
    </view>

    <view class="accord-content">
      <view class="font-view" v-html="info.content || ''"></view>
    </view>

    <view
      class="cu-load"
      :class="isLoad ? 'loading' : info.content ? '' : 'over'"
    ></view>
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
      accordId: "",
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
    envIsolationHealth() {
      return getEnvIsolationHealth();
    },
    envDescription() {
      return this.currentEnvInfo.is_prod
        ? "当前为正式环境，协议正文与展示内容来自线上真实配置。"
        : "当前为非正式环境，适合联调协议正文展示、图片适配和跳转链路。";
    },
    accordModeText() {
      return this.currentEnvInfo.is_prod
        ? "当前模式：正式协议查看"
        : "当前模式：联调协议查看";
    },
    accordIdText() {
      return `协议标识：${this.accordId || "未提供"}`;
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
    accordContentStatusText() {
      return this.info.content ? "正文状态：已加载" : "正文状态：待加载";
    },
    accordReviewHint() {
      return this.currentEnvInfo.is_prod
        ? `当前为正式环境，协议正文和展示内容都来自真实配置。查看、截图和外发前请先确认协议标识与版本。${getEnvIsolationHint(this.currentEnvInfo)}`
        : `当前环境适合联调协议正文展示、图片适配和跳转链路。${getEnvIsolationHint(this.currentEnvInfo)}`;
    },
    accordRiskHint() {
      if (!this.info.content) {
        return "当前协议正文尚未加载成功，建议先确认协议标识、接口环境和后台协议配置。";
      }
      return this.currentEnvInfo.is_prod
        ? "正式环境下协议内容会直接影响登录前告知、协议中心和隐私展示，请避免把测试截图当作正式版本。"
        : "当前环境仅用于联调协议展示和跳转，不要把测试内容当作正式运营文案。";
    },
    accordRolloutHint() {
      if (this.currentEnvInfo.is_prod) {
        return "当前协议页已经连接正式环境，查看到的正文和图片就是线上真实配置，适合做正式上线前终验。";
      }
      if (this.envIsolationHealth.has_example_host) {
        return "当前环境仍是占位域名，只适合核对前端结构和样式，不适合进入真实提测或灰度验收。";
      }
      return "当前协议页适合做测试或灰度验收，建议重点核对协议正文、图片适配、标题回显和返回链路。";
    },
    accordRolloutChecklist() {
      return [
        { label: "当前环境", value: this.currentEnvInfo.label || "未配置" },
        { label: "协议标识", value: this.accordId || "未提供" },
        { label: "正文状态", value: this.info.content ? "已加载" : "待加载" },
        { label: "发布阶段", value: this.envReleaseStageText },
      ];
    },
    accordRollbackHint() {
      return this.currentEnvInfo.is_prod
        ? "如发现协议正文异常，建议立即回后台核对协议配置，并暂停把当前页面作为正式入口外发。"
        : "灰度或测试期间如出现正文回显异常，可先回到旧协议入口继续验收，再排查当前配置。";
    },
  },
  onLoad(options) {
    this.accordId = options.accord_id || "";
    if (!this.accordId) {
      this.hasRequiredParam = false;
      this.recentActionSummary = "未提供协议标识，已切换到参数缺失承接态。";
      uni.setNavigationBarTitle({
        title: "协议详情",
      });
      return;
    }
    this.recentActionSummary = `准备加载协议正文：${this.accordId}。`;
    this.getInfo();
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
    goAccordCenter() {
      uni.navigateTo({
        url: "/pages/system/accord-center",
      });
    },
    setRecentActionSummary(summary) {
      this.recentActionSummary = summary;
    },
    getInfo() {
      this.isLoad = true;
      this.setRecentActionSummary(`正在加载协议正文：${this.accordId}。`);
      api
        .getAccordInfo({ accord_id: this.accordId })
        .then((res) => {
          this.info = res.data || {};
          if (this.info.name) {
            uni.setNavigationBarTitle({
              title: this.info.name,
            });
          }
          this.setRecentActionSummary(
            `协议正文加载成功：${this.info.name || this.accordId}。`,
          );
          if (this.info.content) {
            this.info.content = this.info.content.replace(
              /<img(.*?)style="(.*?)"/g,
              '<img$1style="width: 100%;height: auto;"',
            );
          }
        })
        .catch(() => {
          this.setRecentActionSummary(
            `协议正文加载失败，请确认当前环境和协议配置。${getEnvIsolationHint(this.currentEnvInfo)}`,
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

.accord-page {
  min-height: 100vh;
  background:
    radial-gradient(
      circle at top right,
      rgba(233, 150, 85, 0.16),
      transparent 30%
    ),
    linear-gradient(180deg, #f8f3ec 0%, #f2f6fa 100%);
}

.env-card,
.action-card,
.accord-head,
.accord-content {
  margin: 24rpx;
  padding: 28rpx;
  border-radius: 28rpx;
  background: rgba(255, 255, 255, 0.96);
  box-shadow: 0 18rpx 42rpx rgba(17, 34, 51, 0.08);
}

.env-card__head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 20rpx;
}

.env-card__title,
.action-card__title {
  font-size: 30rpx;
  font-weight: 700;
  color: #10283d;
}

.env-card__desc,
.action-card__desc {
  margin-top: 12rpx;
  font-size: 24rpx;
  line-height: 1.7;
  color: #607181;
}

.env-card__desc--strong {
  padding: 14rpx 16rpx;
  border-radius: 18rpx;
  background: rgba(21, 75, 114, 0.06);
  color: #35506b;
}

.env-card__badge {
  padding: 8rpx 18rpx;
  border-radius: 999rpx;
  font-size: 22rpx;
  color: #ffffff;
  background: linear-gradient(45deg, #1cbbb4, #0081ff);
}

.env-card__badge.is-prod {
  background: linear-gradient(45deg, #ef4444, #f97316);
}

.env-card__meta {
  display: flex;
  flex-wrap: wrap;
  gap: 12rpx;
  margin-top: 16rpx;
  font-size: 22rpx;
  color: #315f86;
}

.env-risk-list {
  margin-top: 16rpx;
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

.env-profile-board {
  margin-top: 16rpx;
  padding: 18rpx 20rpx;
  border-radius: 20rpx;
  background: rgba(21, 75, 114, 0.05);
}

.env-profile-board__title {
  font-size: 24rpx;
  font-weight: 700;
  color: #10283d;
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
  color: #10283d;
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
  color: #607181;
}

.review-card {
  margin: 0 24rpx 24rpx;
  padding: 28rpx;
  border-radius: 28rpx;
  background: rgba(255, 255, 255, 0.96);
  box-shadow: 0 18rpx 42rpx rgba(17, 34, 51, 0.08);
}

.review-card__title {
  font-size: 30rpx;
  font-weight: 700;
  color: #10283d;
}

.review-card__desc,
.review-card__risk {
  margin-top: 12rpx;
  font-size: 24rpx;
  line-height: 1.7;
  color: #607181;
}

.review-card__tags {
  display: flex;
  flex-wrap: wrap;
  gap: 12rpx;
  margin-top: 16rpx;
}

.review-card__tag {
  display: inline-flex;
  align-items: center;
  min-height: 38rpx;
  padding: 0 16rpx;
  border-radius: 999rpx;
  font-size: 22rpx;
  color: #315f86;
  background: rgba(28, 92, 136, 0.08);
}

.accord-title {
  font-size: 36rpx;
  font-weight: 700;
  color: #10283d;
}

.accord-desc {
  margin-top: 14rpx;
  font-size: 24rpx;
  line-height: 1.7;
  color: #607181;
}

.font-view {
  font-size: 28rpx;
  line-height: 1.85;
  color: #1f2d3d;
  word-break: break-all;
}

.font-view image,
.font-view img {
  max-width: 100% !important;
  height: auto !important;
}

.cu-load.over::after {
  content: "\u6682\u65e0\u5185\u5bb9";
}

.action-card__checklist {
  margin-top: 16rpx;
  display: flex;
  flex-direction: column;
  gap: 10rpx;
}

.action-card__check-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16rpx;
  padding: 16rpx 18rpx;
  border-radius: 18rpx;
  background: rgba(21, 75, 114, 0.05);
}

.action-card__check-label {
  font-size: 22rpx;
  color: #607181;
}

.action-card__check-value {
  font-size: 22rpx;
  font-weight: 700;
  color: #10283d;
}

.action-card__desc--strong {
  padding: 14rpx 16rpx;
  border-radius: 18rpx;
  background: rgba(21, 75, 114, 0.06);
  color: #35506b;
}
</style>

