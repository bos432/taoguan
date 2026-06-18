<template>
  <view>
    <view v-if="!hasRequiredParam" class="margin-tb-sm zaiui-view-box">
      <view class="bg-white zaiui-card review-card">
        <view class="review-card__title">物流入口参数缺失</view>
        <view class="review-card__desc">
          当前链接没有带订单编号，所以无法查询运单轨迹；现在先显示可见承接提示。
        </view>
        <view class="review-card__risk">
          建议从订单列表重新进入物流页，避免把缺参数误判成物流异常。
        </view>
        <view style="display: flex; gap: 20rpx; margin-top: 20rpx;">
          <button style="flex: 1; height: 76rpx; line-height: 76rpx; border-radius: 999rpx; background: linear-gradient(135deg, #1f7ae0, #2356d3); color: #ffffff; font-size: 24rpx;" @tap="goOrderList">
            去订单列表
          </button>
          <button style="flex: 1; height: 76rpx; line-height: 76rpx; border-radius: 999rpx; background: #edf2f7; color: #37506c; font-size: 24rpx;" @tap="goBackOrHome">
            返回上一页
          </button>
        </view>
      </view>
    </view>
    <view v-else>
    <view class="margin-tb-sm zaiui-view-box">
      <view class="bg-white zaiui-card env-card">
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
          <text class="env-tag">{{ logisticsModeText }}</text>
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
    </view>
    <view class="margin-tb-sm zaiui-view-box">
      <view class="bg-white zaiui-card review-card">
        <view class="review-card__title">物流复核</view>
        <view class="review-card__desc">{{ reviewHint }}</view>
        <view class="review-card__meta">
          <text class="review-card__tag">{{ logisticsModeText }}</text>
          <text class="review-card__tag">{{ trackingStatusText }}</text>
          <text class="review-card__tag">{{ timelineCountText }}</text>
        </view>
        <view class="review-card__risk">{{ reviewRiskText }}</view>
      </view>
    </view>
    <view v-if="recentActionSummary" class="margin-tb-sm zaiui-view-box">
      <view class="bg-white zaiui-card review-card">
        <view class="review-card__title">最近操作</view>
        <view class="review-card__desc">{{ recentActionSummary }}</view>
      </view>
    </view>
    <view class="margin-tb-sm zaiui-view-box">
      <view class="bg-white zaiui-card review-card">
        <view class="review-card__title">异常追踪</view>
        <view class="review-card__desc">{{ logisticsFollowupHint }}</view>
        <view class="review-card__meta">
          <text
            class="review-card__tag"
            v-for="item in logisticsFollowupTags"
            :key="item"
            >{{ item }}</text
          >
        </view>
        <view class="review-card__risk">{{ logisticsFollowupRiskText }}</view>
      </view>
    </view>
    <view v-if="info.kuaidi_order_no">
      <view class="cu-form-group margin-top">
        <view class="title">快递公司</view>
        <view>{{ info.delivery_name }}</view>
      </view>
      <view class="cu-form-group">
        <view class="title">运单号</view>
        <view>{{ info.kuaidi_order_no }}</view>
      </view>
      <view class="cu-form-group">
        <view class="title">发货时间</view>
        <view>{{ info.delivery_time }}</view>
      </view>
      <view class="cu-timeline" v-for="(item, index) in info.list" :key="index">
        <view class="cu-time">{{ item.status }}</view>
        <view class="cu-item">
          <view class="content">
            <text>{{ item.time }}：</text>{{ item.context }}
          </view>
        </view>
      </view>
    </view>
    <view
      class="cu-load"
      :class="isLoad ? 'loading' : info.kuaidi_order_no ? '' : 'over'"
    ></view>
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
    envDescription() {
      return this.currentEnvInfo.is_prod
        ? "当前为正式环境，物流轨迹和发货状态来自真实订单数据。"
        : "当前为非正式环境，可用于物流轨迹展示与订单回跳联调。";
    },
    logisticsModeText() {
      return this.info.kuaidi_order_no
        ? "物流状态：已发货"
        : "物流状态：待发货";
    },
    trackingStatusText() {
      return this.info.delivery_name
        ? `快递公司：${this.info.delivery_name}`
        : "快递公司：待回传";
    },
    timelineCountText() {
      return `轨迹节点：${Array.isArray(this.info.list) ? this.info.list.length : 0} 条`;
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
        ? `查看到的物流轨迹对应真实订单，请避免把测试结果当作正式异常。${getEnvIsolationHint(this.currentEnvInfo)}`
        : `建议先在当前环境验证物流详情展示、时间线渲染和订单回跳。${getEnvIsolationHint(this.currentEnvInfo)}`;
    },
    reviewHint() {
      return this.currentEnvInfo.is_prod
        ? "当前为正式环境，物流轨迹和发货状态都来自真实订单，请按真实异常标准核对。"
        : "当前为非正式环境，建议重点核对运单号、轨迹节点和页面展示是否完整。";
    },
    reviewRiskText() {
      if (!this.info.kuaidi_order_no) {
        return "当前订单还没有回传运单号，页面会显示待发货状态。";
      }
      if (!Array.isArray(this.info.list) || !this.info.list.length) {
        return "当前已有运单号，但暂无物流轨迹，建议稍后刷新或核对快递公司配置。";
      }
      return "当前物流轨迹已回传，可继续结合发货时间和运单号做异常排查。";
    },
    logisticsFollowupHint() {
      if (!this.info.kuaidi_order_no) {
        return "当前订单还未发货，建议回到订单页继续关注发货状态和平台处理结果。";
      }
      if (!Array.isArray(this.info.list) || !this.info.list.length) {
        return "当前已有运单号但暂无轨迹，建议稍后刷新并核对快递公司回传是否正常。";
      }
      return "当前物流轨迹已开始回传，建议继续关注最新节点、签收状态和异常停滞情况。";
    },
    logisticsFollowupTags() {
      return [
        this.info.kuaidi_order_no ? "下一步：跟进物流节点" : "下一步：等待发货",
        this.info.delivery_time
          ? `发货时间：${this.info.delivery_time}`
          : "发货时间：待回传",
        this.info.kuaidi_order_no
          ? `运单号：${this.info.kuaidi_order_no}`
          : "运单号：待回传",
        this.timelineCountText,
      ];
    },
    logisticsFollowupRiskText() {
      if (!this.info.kuaidi_order_no) {
        return "当前还没有运单号，异常判断应以订单发货状态为准，不应把未发货误判为物流异常。";
      }
      if (Array.isArray(this.info.list) && this.info.list.length === 1) {
        return "当前只有 1 条物流节点，建议继续观察是否存在长时间未更新的情况。";
      }
      return this.currentEnvInfo.is_prod
        ? "当前为正式环境，物流异常跟踪应以真实运单号、时间线和订单状态回显为准。"
        : "当前为非正式环境，建议继续联调物流页展示、刷新回显和订单页回跳承接。";
    },
  },
  onLoad(options) {
    if (options.id) {
      this.id = Number(options.id);
      this.getInfo();
    } else {
      this.hasRequiredParam = false;
      this.updateRecentActionSummary("未提供订单标识，已切换到参数缺失承接态。");
      uni.setNavigationBarTitle({
        title: "物流详情",
      });
    }
  },
  methods: {
    goBackOrHome() {
      if (getCurrentPages().length > 1) {
        uni.navigateBack();
        return;
      }
      this.goOrderList();
    },
    goOrderList() {
      uni.navigateTo({
        url: "/pages/order/list",
      });
    },
    updateRecentActionSummary(summary) {
      this.recentActionSummary = summary;
    },
    getInfo() {
      this.isLoad = true;
      this.updateRecentActionSummary("正在加载物流详情。");
      api
        .getLogistics({ id: this.id })
        .then((res) => {
          this.info = res.data;
          this.updateRecentActionSummary(
            `物流详情已刷新：${this.logisticsModeText}，${this.timelineCountText}。`,
          );
        })
        .catch(() => {
          this.updateRecentActionSummary("物流详情加载失败。");
        })
        .finally(() => {
          this.isLoad = false;
        });
    },
  },
};
</script>

<style lang="scss">
.env-card {
  padding: 24rpx 28rpx;
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
  margin-top: 10rpx;
  color: #94a3b8;
  font-size: 20rpx;
  line-height: 1.6;
  word-break: break-all;
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

.review-card {
  padding: 24rpx 28rpx;
}

.review-card__title {
  color: #162233;
  font-size: 28rpx;
  font-weight: 700;
}

.review-card__desc {
  margin-top: 12rpx;
  color: #5b6b7b;
  font-size: 22rpx;
  line-height: 1.7;
}

.review-card__meta {
  display: flex;
  flex-wrap: wrap;
  gap: 12rpx;
  margin-top: 14rpx;
}

.review-card__tag {
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

.review-card__risk {
  margin-top: 14rpx;
  padding: 18rpx 20rpx;
  border-radius: 18rpx;
  background: linear-gradient(180deg, #f7fafc 0%, #eef3f7 100%);
  color: #5f6f82;
  font-size: 22rpx;
  line-height: 1.65;
}
</style>

