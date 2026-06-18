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
    <view class="service-page">
      <view class="hero-card">
        <view class="hero-badge">客服中心</view>
        <view class="hero-title">需要帮助时，随时联系平台客服</view>
        <view class="hero-subtitle">
          工作时间、联系电话、客服 QQ 和微信信息都集中展示在这里。
        </view>
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
        <view class="section-tip env-desc">{{ serviceEnvDescription }}</view>
        <view class="env-tags">
          <text class="env-tag">{{ serviceModeText }}</text>
          <text class="env-tag">{{ serviceCountText }}</text>
          <text class="env-tag">{{ envIsolationText }}</text>
          <text class="env-tag">{{ envIsolationStatusText }}</text>
          <text class="env-tag">{{ envReleaseStageText }}</text>
        </view>
        <view class="env-note">{{ serviceActionHint }}</view>
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
        <view class="recent-action-card__desc">{{ serviceReviewHint }}</view>
        <view class="env-tags">
          <text v-for="item in serviceReviewTags" :key="item" class="env-tag">{{
            item
          }}</text>
        </view>
        <view class="env-note">{{ serviceRiskHint }}</view>
      </view>

      <view v-if="recentActionSummary" class="section-card recent-action-card">
        <view class="section-title">最近操作</view>
        <view class="recent-action-card__desc">{{ recentActionSummary }}</view>
      </view>

      <view class="section-card">
        <view class="section-title">上线承接提示</view>
        <view class="recent-action-card__desc">{{ serviceRolloutHint }}</view>
        <view class="rollout-card__list">
          <view
            v-for="item in serviceRolloutChecklist"
            :key="item.label"
            class="rollout-card__item"
          >
            <text class="rollout-card__item-label">{{ item.label }}</text>
            <text class="rollout-card__item-value">{{ item.value }}</text>
          </view>
        </view>
        <view class="env-note env-note--strong">{{ serviceRollbackHint }}</view>
      </view>

      <view class="section-card">
        <view class="section-head">
          <view class="section-title">服务时间</view>
          <view class="section-tip">建议优先在工作时段内联系</view>
        </view>
        <view class="single-line-card">
          <text class="single-line-label">工作时段</text>
          <text class="single-line-value">09:00 - 18:00</text>
        </view>
      </view>

      <view v-if="(info.service_phone || []).length" class="section-card">
        <view class="section-head">
          <view class="section-title">联系电话</view>
          <view class="section-tip">点击号码可直接拨打或复制</view>
        </view>
        <view class="contact-list">
          <view
            v-for="(item, index) in info.service_phone"
            :key="index"
            class="contact-item"
            :data-phone="item"
            @tap="toPhone"
          >
            <view class="contact-left">
              <text class="cuIcon-phone"></text>
              <text>联系电话</text>
            </view>
            <text class="contact-value">{{ item }}</text>
          </view>
        </view>
      </view>

      <view v-if="(info.service_qq || []).length" class="section-card">
        <view class="section-head">
          <view class="section-title">客服 QQ</view>
          <view class="section-tip">点击即可复制</view>
        </view>
        <view class="contact-list">
          <view
            v-for="(item, index) in info.service_qq"
            :key="index"
            class="contact-item"
            :data-content="item"
            @tap="strCopy"
          >
            <view class="contact-left">
              <text class="cuIcon-service"></text>
              <text>客服 QQ</text>
            </view>
            <text class="contact-value">{{ item }}</text>
          </view>
        </view>
      </view>

      <view v-if="(info.service_wechat || []).length" class="section-card">
        <view class="section-head">
          <view class="section-title">客服微信</view>
          <view class="section-tip">点击微信号即可复制</view>
        </view>
        <view class="contact-list">
          <view
            v-for="(item, index) in info.service_wechat"
            :key="index"
            class="contact-item"
            :data-content="item"
            @tap="strCopy"
          >
            <view class="contact-left">
              <text class="cuIcon-weixin"></text>
              <text>客服微信号</text>
            </view>
            <text class="contact-value">{{ item }}</text>
          </view>
        </view>
      </view>

      <view v-if="info.service_wechat_image_url" class="section-card qr-card">
        <view class="section-head">
          <view class="section-title">客服二维码</view>
          <view class="section-tip">点击图片可放大查看</view>
        </view>
        <image
          :src="info.service_wechat_image_url"
          class="qr-image"
          mode="aspectFill"
          :data-url="info.service_wechat_image_url"
          @tap="viewImage"
        ></image>
      </view>

      <view v-if="!isLoad && isEmpty" class="empty-state"
        >当前暂无客服信息</view
      >
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
  name: "service",
  data() {
    return {
      info: {},
      isLoad: false,
      recentActionSummary: "",
    };
  },
  computed: {
    currentEnvInfo() {
      return getCurrentEnvInfo();
    },
    isEmpty() {
      const phones = this.info.service_phone || [];
      const qqs = this.info.service_qq || [];
      const wechats = this.info.service_wechat || [];
      return (
        !phones.length &&
        !qqs.length &&
        !wechats.length &&
        !this.info.service_wechat_image_url
      );
    },
    serviceEnvDescription() {
      return this.currentEnvInfo.is_prod
        ? "当前为正式环境，拨号、复制客服信息和扫码联系会直接作用于真实客服渠道。"
        : `当前为${this.currentEnvInfo.label}，适合联调客服信息展示、拨号复制和二维码预览链路。`;
    },
    serviceModeText() {
      return this.isLoad ? "当前状态：加载中" : "当前状态：客服信息查看";
    },
    serviceCountText() {
      const total =
        (this.info.service_phone || []).length +
        (this.info.service_qq || []).length +
        (this.info.service_wechat || []).length +
        (this.info.service_wechat_image_url ? 1 : 0);
      return `可用客服项：${total}`;
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
    serviceActionHint() {
      return this.currentEnvInfo.is_prod
        ? `正式环境下请确认拨打和复制的是当前正在使用的客服渠道。${getEnvIsolationHint(this.currentEnvInfo)}`
        : `建议在当前环境验证客服信息加载、复制、拨号和二维码预览链路。${getEnvIsolationHint(this.currentEnvInfo)}`;
    },
    serviceReviewHint() {
      return this.currentEnvInfo.is_prod
        ? "当前客服页已连接正式环境，拨号、复制和扫码前请先确认当前展示的是线上真实客服渠道。"
        : "当前客服页适合验证电话拨号、QQ/微信复制和二维码预览链路。";
    },
    serviceReviewTags() {
      return [
        this.serviceModeText,
        this.serviceCountText,
        this.isEmpty ? "客服状态：待配置" : "客服状态：已可联系",
      ];
    },
    serviceRiskHint() {
      return this.currentEnvInfo.is_prod
        ? "正式环境下拨号和复制都会触达真实客服渠道，建议避免把测试号码或演示二维码暴露给用户。"
        : "当前环境仅用于联调客服信息展示和联系入口，不要把测试客服信息当作正式运营配置。";
    },
    serviceRolloutHint() {
      if (this.currentEnvInfo.is_prod) {
        return "当前客服页已经连接正式环境，适合做正式上线前的终验和客服渠道核对。";
      }
      if (this.envIsolationHealth.has_example_host) {
        return "当前环境仍是占位域名，只适合核对页面结构和交互，不适合进入真实提测或灰度验收。";
      }
      return "当前客服页适合做测试或灰度验收，建议重点核对电话、QQ、微信和二维码入口是否都可用。";
    },
    serviceRolloutChecklist() {
      return [
        { label: "当前环境", value: this.currentEnvInfo.label || "未配置" },
        {
          label: "客服项",
          value: this.serviceCountText.replace("可用客服项：", ""),
        },
        { label: "配置状态", value: this.isEmpty ? "待补齐" : "已可验收" },
        { label: "发布阶段", value: this.envReleaseStageText },
      ];
    },
    serviceRollbackHint() {
      return this.currentEnvInfo.is_prod
        ? "如客服渠道异常，建议立即回后台核对客服配置，并暂停把当前页面作为正式联系入口外发。"
        : "灰度或测试期间如出现客服信息异常，可先回切旧客服入口继续验收，再排查当前配置。";
    },
  },
  onLoad() {
    uni.setNavigationBarTitle({
      title: "客服中心",
    });
    this.updateRecentActionSummary(
      "进入客服中心，准备加载联系电话、QQ、微信和二维码信息。",
    );
    this.getInfo();
  },
  methods: {
    updateRecentActionSummary(summary) {
      this.recentActionSummary = summary;
    },
    toPhone(e) {
      const phone = e && e.currentTarget ? e.currentTarget.dataset.phone : "";
      if (!phone) {
        return;
      }
      this.updateRecentActionSummary(`准备联系客服电话：${phone}。`);
      if (uni.getSystemInfoSync().platform !== "devtools") {
        uni.makePhoneCall({
          phoneNumber: phone,
          fail: () => {
            this.updateRecentActionSummary(
              `拨号失败，已改为复制客服电话：${phone}。`,
            );
            this.copyValue(phone);
          },
        });
        return;
      }
      this.copyValue(phone);
    },
    viewImage(e) {
      const url = e && e.currentTarget ? e.currentTarget.dataset.url : "";
      if (!url) {
        return;
      }
      this.updateRecentActionSummary("准备预览客服二维码。");
      uni.previewImage({
        urls: [url],
        current: url,
      });
    },
    strCopy(e) {
      const content =
        e && e.currentTarget ? e.currentTarget.dataset.content : "";
      this.updateRecentActionSummary(
        `准备复制客服信息：${content || "未命名内容"}。`,
      );
      this.copyValue(content);
    },
    copyValue(content) {
      if (!content) {
        return;
      }
      uni.setClipboardData({
        data: content,
        showToast: false,
        success() {
          uni.showToast({
            icon: "none",
            title: "已复制",
          });
        },
      });
      this.updateRecentActionSummary(`已复制客服信息：${content}。`);
    },
    getInfo() {
      this.isLoad = true;
      this.updateRecentActionSummary("正在加载客服中心信息。");
      api
        .getServiceInfo({})
        .then((res) => {
          this.info = res.data || {};
          this.updateRecentActionSummary(
            this.isEmpty
              ? "客服中心加载完成，但当前没有可用的电话、QQ、微信或二维码信息。"
              : `客服中心加载完成，可查看 ${(this.info.service_phone || []).length} 个电话、${(this.info.service_qq || []).length} 个 QQ、${(this.info.service_wechat || []).length} 个微信。`,
          );
        })
        .catch(() => {
          this.updateRecentActionSummary(
            "客服中心信息加载失败，请确认当前环境接口和客服配置。",
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
.service-page {
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

.hero-badge {
  display: inline-flex;
  padding: 12rpx 18rpx;
  border-radius: 999rpx;
  background: rgba(255, 255, 255, 0.14);
  font-size: 22rpx;
}

.hero-title {
  margin-top: 28rpx;
  font-size: 38rpx;
  font-weight: 700;
  line-height: 1.4;
}

.hero-subtitle {
  margin-top: 16rpx;
  font-size: 26rpx;
  line-height: 1.7;
  opacity: 0.94;
}

.section-card {
  margin-top: 24rpx;
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
  color: #12283b;
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
  color: #12283b;
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

.rollout-card__list {
  display: flex;
  flex-wrap: wrap;
  gap: 12rpx;
  margin-top: 16rpx;
}

.rollout-card__item {
  display: inline-flex;
  align-items: center;
  gap: 10rpx;
  padding: 10rpx 18rpx;
  border-radius: 999rpx;
  background: rgba(21, 75, 114, 0.08);
}

.rollout-card__item-label {
  color: #7d8b98;
  font-size: 22rpx;
}

.rollout-card__item-value {
  color: #12283b;
  font-size: 22rpx;
  font-weight: 700;
}

.section-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 20rpx;
}

.section-title {
  font-size: 34rpx;
  font-weight: 700;
  color: #12283b;
}

.section-tip {
  font-size: 22rpx;
  color: #7d8b98;
}

.single-line-card {
  margin-top: 22rpx;
  padding: 24rpx 26rpx;
  border-radius: 24rpx;
  background: #f4f7fb;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.single-line-label,
.contact-left {
  display: flex;
  align-items: center;
  gap: 12rpx;
  font-size: 28rpx;
  color: #526577;
}

.single-line-value,
.contact-value {
  font-size: 28rpx;
  font-weight: 600;
  color: #12283b;
}

.contact-list {
  margin-top: 18rpx;
}

.contact-item {
  padding: 26rpx 4rpx;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.contact-item + .contact-item {
  border-top: 1rpx solid #edf1f5;
}

.qr-image {
  width: 100%;
  height: 420rpx;
  margin-top: 24rpx;
  border-radius: 24rpx;
  background: #edf2f7;
}

.empty-state {
  margin-top: 120rpx;
  text-align: center;
  font-size: 28rpx;
  color: #7d8b98;
}
</style>

