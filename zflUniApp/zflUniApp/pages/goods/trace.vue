<template>
  <view>
    <view class="env-card">
      <view class="env-head">
        <text class="env-title">当前环境</text>
        <text
          class="env-badge"
          :class="currentEnvInfo.is_prod ? 'is-prod' : 'is-test'"
          >{{ currentEnvInfo.label }}</text
        >
      </view>
      <view class="env-desc">{{ envDescription }}</view>
      <view class="env-meta">
        <text class="env-tag">{{ traceModeText }}</text>
        <text class="env-tag">{{ traceCodeText }}</text>
        <text class="env-tag">{{ envIsolationText }}</text>
        <text class="env-tag">{{ envReleaseStageText }}</text>
      </view>
      <view class="env-desc env-desc--strong">{{ envReleaseHint }}</view>
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
    <view class="rollout-card">
      <view class="rollout-card__title">上线承接提示</view>
      <view class="rollout-card__desc">{{ traceRolloutHint }}</view>
      <view class="rollout-card__list">
        <view
          v-for="item in traceRolloutChecklist"
          :key="item.label"
          class="rollout-card__item"
        >
          <text class="rollout-card__item-label">{{ item.label }}</text>
          <text class="rollout-card__item-value">{{ item.value }}</text>
        </view>
      </view>
      <view class="rollout-card__risk">{{ traceRollbackHint }}</view>
    </view>
    <view class="review-card">
      <view class="review-card__title">查询前复核</view>
      <view class="review-card__desc">{{ traceReviewHint }}</view>
      <view class="review-card__tags">
        <text class="review-card__tag">{{ traceReviewTags.mode }}</text>
        <text class="review-card__tag">{{ traceReviewTags.code }}</text>
        <text class="review-card__tag">{{ traceReviewTags.result }}</text>
      </view>
      <view class="review-card__risk">{{ traceRiskHint }}</view>
    </view>
    <view v-if="recentActionSummary" class="recent-action-card">
      <view class="recent-action-card__title">最近操作</view>
      <view class="recent-action-card__desc">{{ recentActionSummary }}</view>
    </view>
    <view
      v-if="!hasRequiredParam"
      class="review-card"
      style="border: 1rpx solid rgba(245, 158, 11, 0.22); background: linear-gradient(180deg, rgba(255, 247, 237, 0.98) 0%, #ffffff 100%);"
    >
      <view class="review-card__title">溯源入口参数缺失</view>
      <view class="review-card__desc">
        当前链接没有带溯源码，所以无法直接查询批次流转；现在先给出承接提示，不再直接跳首页。
      </view>
      <view class="review-card__risk">
        建议从商品详情或扫码入口重新进入，确保查询到的批次结果是当前商品对应编码。
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
    <view v-if="info.batch" class="result-card">
      <view class="result-card__head">
        <view>
          <view class="result-card__title">最近查询结果</view>
          <view class="result-card__desc">{{ traceResultTitle }}</view>
        </view>
        <view class="result-card__badge">{{ currentEnvInfo.label }}</view>
      </view>
      <view class="result-card__tags">
        <text class="result-card__tag">{{ traceCodeText }}</text>
        <text class="result-card__tag">{{ traceNodeCountText }}</text>
        <text class="result-card__tag">{{ traceImageCountText }}</text>
      </view>
      <view class="result-card__risk">{{ traceResultHint }}</view>
    </view>
    <view v-if="info.batch">
      <view class="zaiui-banner-swiper-box">
        <swiper class="screen-swiper" circular autoplay @change="bannerSwiper">
          <swiper-item v-for="(item, index) in bannerList" :key="index">
            <image :src="item.url" mode="aspectFill" />
          </swiper-item>
        </swiper>
      </view>
      <view class="cu-bar bg-white solid-bottom margin-top">
        <view class="action">
          <text class="cuIcon-title text-blue"></text>商品信息
        </view>
      </view>
      <view class="cu-form-group">
        <view class="title">商品编码</view>
        <view>{{ info.batch.goods.code }}</view>
      </view>
      <view class="cu-form-group">
        <view class="title">商品名称</view>
        <view>{{ info.batch.goods.title }}</view>
      </view>
      <view class="cu-form-group">
        <view class="title">商品规格</view>
        <view>{{ info.batch.goods.spec }}</view>
      </view>
      <view class="cu-form-group">
        <view class="title">计量单位</view>
        <view>{{ info.batch.goods.unit }}</view>
      </view>
    </view>
    <template v-for="(item, index) in info.list">
      <view class="cu-bar bg-white solid-bottom" :key="index">
        <view class="action">
          <text class="cuIcon-title text-blue"></text>{{ item.tache_title }}
        </view>
      </view>
      <view class="cu-form-group" v-for="(obj, k) in item.tacheValue" :key="k">
        <view class="title">{{ obj.label }}</view>
        <view>{{ obj.value }}</view>
      </view>
    </template>

    <view
      class="cu-load"
      :class="isLoad ? 'loading' : info.batch ? '' : 'over'"
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
      content: {},
      code: "u1JpPJMaiRBjb3W5HF",
      hasRequiredParam: true,
      isLoad: false,
      info: {},
      bannerCur: 0,
      bannerList: [],
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
        ? "当前为正式环境，溯源码查询结果来自线上真实批次数据。"
        : `当前为${this.currentEnvInfo.label}，适合做批次溯源链路和展示内容联调。`;
    },
    traceModeText() {
      return this.currentEnvInfo.is_prod
        ? "当前模式：正式溯源"
        : "当前模式：联调溯源";
    },
    traceCodeText() {
      return `查询编码：${this.code || "未提供"}`;
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
    traceReviewHint() {
      return this.currentEnvInfo.is_prod
        ? `当前查询结果会展示线上真实批次信息，请先确认编码与商品来源后再分享或截图。${getEnvIsolationHint(this.currentEnvInfo)}`
        : `当前环境适合联调溯源码展示、图片轮播和节点信息回显。${getEnvIsolationHint(this.currentEnvInfo)}`;
    },
    traceReviewTags() {
      return {
        mode: this.traceModeText,
        code: this.traceCodeText,
        result: this.info.batch ? "查询结果：已命中批次" : "查询结果：待加载",
      };
    },
    traceRiskHint() {
      return this.currentEnvInfo.is_prod
        ? "正式环境下批次信息、商品名称和流转节点均为真实数据，请避免把测试编码带入运营分享。"
        : "当前环境只用于联调溯源展示，不要把测试批次结果当作正式运营凭据。";
    },
    traceRolloutHint() {
      if (this.currentEnvInfo.is_prod) {
        return "当前溯源码页已经连接正式环境，查询到的批次节点、商品图和流转信息都属于线上真实数据。";
      }
      if (this.envIsolationHealth.has_example_host) {
        return "当前环境仍是占位域名，只适合核对页面结构和前端样式，不适合进入真实提测或灰度验收。";
      }
      return "当前溯源码页适合做测试或灰度验收，建议重点核对图片轮播、节点信息、标题回显和空态提示。";
    },
    traceRolloutChecklist() {
      return [
        { label: "当前环境", value: this.currentEnvInfo.label || "未配置" },
        { label: "查询编码", value: this.code || "未提供" },
        { label: "批次结果", value: this.info.batch ? "已命中" : "待加载" },
        { label: "发布阶段", value: this.envReleaseStageText },
      ];
    },
    traceRollbackHint() {
      return this.currentEnvInfo.is_prod
        ? "如查询结果异常，建议先暂停把当前结果用于运营展示，再回后台核对批次数据与流转记录。"
        : "灰度或测试期间如出现溯源结果异常，可先回切旧查询入口继续验收，再排查当前配置。";
    },
    traceNodeCountText() {
      return `流转节点：${this.info.list.length || 0} 个`;
    },
    traceImageCountText() {
      return `轮播图片：${this.bannerList.length || 0} 张`;
    },
    traceResultTitle() {
      return this.info.batch && this.info.batch.goods
        ? `${this.info.batch.goods.title || "未命名商品"} · ${this.traceNodeCountText}`
        : "溯源结果待加载";
    },
    traceResultHint() {
      return this.currentEnvInfo.is_prod
        ? "正式环境下请把当前结果视为真实批次凭据，截图、外发和客服解释前都应先确认编码来源。"
        : "当前结果仅用于联调溯源展示和节点回显，不要把测试编码结果当作正式凭证。";
    },
  },
  onLoad(options) {
    if (options.code) {
      this.code = options.code;
      this.recentActionSummary = `准备查询溯源码：${this.code}。`;
      this.getInfo();
    } else {
      this.code = "";
      this.hasRequiredParam = false;
      this.recentActionSummary = "未提供溯源码，已切换到参数缺失承接态。";
      uni.setNavigationBarTitle({
        title: "溯源信息",
      });
    }
  },
  methods: {
    goHome() {
      uni.switchTab({
        url: "/pages/app/home",
      });
    },
    goBackOrHome() {
      if (getCurrentPages().length > 1) {
        uni.navigateBack();
        return;
      }
      this.goHome();
    },
    updateRecentActionSummary(summary) {
      this.recentActionSummary = summary;
    },
    bannerSwiper(e) {
      this.bannerCur = e.detail.current;
      this.updateRecentActionSummary(
        `已切换溯源图片：第 ${this.bannerCur + 1} 张。`,
      );
    },
    getInfo() {
      this.isLoad = true;
      this.updateRecentActionSummary(`正在查询溯源码：${this.code}。`);
      api
        .getBatchTacheByCode({ code: this.code })
        .then((res) => {
          this.info = res.data;
          this.bannerList = [];
          if (this.info.batch.goods.image.file_url) {
            this.bannerList.push({ url: this.info.batch.goods.image.file_url });
          }
          this.updateRecentActionSummary(
            `溯源查询成功：${this.info.batch.goods.title || "未命名商品"}，共 ${this.info.list.length || 0} 个流转节点。`,
          );
          uni.setNavigationBarTitle({
            title: this.info.batch.goods.title,
          });
        })
        .catch(() => {
          this.updateRecentActionSummary(
            "溯源查询失败，请确认编码和当前环境接口状态。",
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
  background: #f4f7fb;
}

.env-card {
  margin: 24rpx;
  padding: 24rpx 28rpx;
  border-radius: 24rpx;
  background: #ffffff;
  box-shadow: 0 12rpx 28rpx rgba(17, 34, 51, 0.05);
}

.env-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16rpx;
}

.env-title {
  color: #162233;
  font-size: 28rpx;
  font-weight: 700;
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
  color: #5b6b7b;
  font-size: 22rpx;
  line-height: 1.7;
}

.env-desc--strong {
  padding: 14rpx 16rpx;
  border-radius: 18rpx;
  background: rgba(21, 75, 114, 0.06);
  color: #35506b;
}

.env-meta,
.review-card__tags {
  display: flex;
  flex-wrap: wrap;
  gap: 12rpx;
  margin-top: 12rpx;
}

.env-tag,
.review-card__tag {
  padding: 8rpx 18rpx;
  border-radius: 999rpx;
  font-size: 22rpx;
  color: #315f86;
  background: rgba(28, 92, 136, 0.08);
}

.env-url {
  margin-top: 10rpx;
  color: #94a3b8;
  font-size: 20rpx;
  line-height: 1.6;
  word-break: break-all;
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

.env-profile-board {
  margin-top: 12rpx;
  padding: 18rpx 20rpx;
  border-radius: 20rpx;
  background: rgba(21, 75, 114, 0.05);
}

.env-profile-board__title,
.rollout-card__title,
.result-card__title {
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

.env-profile-board__item-head,
.result-card__head {
  display: flex;
  align-items: flex-start;
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

.review-card,
.recent-action-card,
.rollout-card,
.result-card {
  margin: 0 24rpx 24rpx;
  padding: 24rpx 28rpx;
  border-radius: 24rpx;
  background: #ffffff;
  box-shadow: 0 12rpx 28rpx rgba(17, 34, 51, 0.05);
}

.review-card__title,
.recent-action-card__title {
  color: #162233;
  font-size: 28rpx;
  font-weight: 700;
}

.review-card__desc,
.review-card__risk,
.recent-action-card__desc,
.rollout-card__desc,
.rollout-card__risk,
.result-card__desc,
.result-card__risk {
  margin-top: 12rpx;
  color: #5b6b7b;
  font-size: 22rpx;
  line-height: 1.7;
}

.rollout-card__list,
.result-card__tags {
  display: flex;
  flex-wrap: wrap;
  gap: 12rpx;
  margin-top: 12rpx;
}

.rollout-card__item,
.result-card__tag {
  padding: 8rpx 18rpx;
  border-radius: 999rpx;
  font-size: 22rpx;
  color: #315f86;
  background: rgba(28, 92, 136, 0.08);
}

.rollout-card__item {
  display: inline-flex;
  align-items: center;
  gap: 10rpx;
}

.rollout-card__item-label {
  color: #5b6b7b;
}

.rollout-card__item-value {
  font-weight: 700;
  color: #162233;
}

.result-card__badge {
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
</style>

