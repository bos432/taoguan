<template>
  <view>
    <view v-if="!hasRequiredParam" class="margin-tb-sm zaiui-view-box">
      <view class="bg-white zaiui-card review-card">
        <view class="review-card__title">评价入口参数缺失</view>
        <view class="review-card__desc">
          当前链接没有带订单编号，所以无法直接提交评价；现在改为可见承接，不再直接返回。
        </view>
        <view class="review-card__risk">
          建议从订单列表的已完成订单重新进入评价页，确保评价对象正确。
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
          <text class="env-tag">{{ evaluateModeText }}</text>
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
        <view class="env-url">{{ currentEnvInfo.api_root_url }}</view>
      </view>
    </view>
    <view class="margin-tb-sm zaiui-view-box">
      <view class="bg-white zaiui-card review-card">
        <view class="review-card__title">提交前复核</view>
        <view class="review-card__desc">{{ reviewHint }}</view>
        <view class="review-card__meta">
          <text class="review-card__tag">{{ evaluateModeText }}</text>
          <text class="review-card__tag">{{ scorePreviewText }}</text>
          <text class="review-card__tag">{{ contentStatusText }}</text>
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
    <view>
      <view class="cu-form-group margin-top">
        <view class="title">商品评分</view>
        <view>
          <u-rate count="5" v-model="evaluate.evaluate_num"></u-rate>
        </view>
      </view>
      <view class="cu-form-group">
        <view class="title">评价内容</view>
        <input
          placeholder="随手评价，帮助更多用户了解商品"
          v-model="evaluate.evaluate_content"
        />
      </view>
    </view>
    <view class="bg-white zaiui-btn-view zaiui-foot-padding-bottom margin-top">
      <view class="flex flex-direction">
        <button class="cu-btn bg-red" @click="submitEvaluation">
          提交评价
        </button>
      </view>
    </view>
    </view>
  </view>
</template>

<script>
import URate from "@/uni_modules/uview-ui/components/u-rate/u-rate.vue";
import api from "@/api";
import {
  getCurrentEnvInfo,
  getEnvIsolationHealth,
  getProfileReadinessList,
} from "@/utils/env-runtime.js";
import {
  buildEnvConfirmText,
  getEnvIsolationHint,
  getEnvIsolationTag,
  getEnvReleaseHint,
  getEnvReleaseStageText,
} from "@/utils/env-risk.js";
import cache from "@/utils/cache.js";

const RECENT_ORDER_RUNTIME_KEY = "recent_order_runtime_card";

export default {
  components: {
    URate,
  },
  data() {
    return {
      isLoad: false,
      hasRequiredParam: true,
      evaluate: {
        id: 0,
        evaluate_content: "",
        evaluate_num: 5,
      },
      recentActionSummary: "",
    };
  },
  computed: {
    currentEnvInfo() {
      return getCurrentEnvInfo();
    },
    envDescription() {
      return this.currentEnvInfo.is_prod
        ? "当前为正式环境，提交评价会写入真实订单评价数据，请确认内容后提交。"
        : "当前为非正式环境，可用于评价流程联调和回显验证。";
    },
    evaluateModeText() {
      return Number(this.evaluate.id || 0) > 0
        ? `评价订单：${this.evaluate.id}`
        : "评价订单：待确认";
    },
    scorePreviewText() {
      return `当前评分：${Number(this.evaluate.evaluate_num || 5)} 星`;
    },
    contentStatusText() {
      return this.evaluate.evaluate_content
        ? "评价内容：已填写"
        : "评价内容：未填写";
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
        ? `评价提交后会直接作用于真实订单评价内容。${getEnvIsolationHint(this.currentEnvInfo)}`
        : `建议先在当前环境验证评价提交、回跳和列表回显。${getEnvIsolationHint(this.currentEnvInfo)}`;
    },
    reviewHint() {
      return this.currentEnvInfo.is_prod
        ? "当前为正式环境，评价内容提交后会真实展示给后续用户查看。"
        : "当前为非正式环境，建议重点验证评分、提交成功回跳和订单列表结果卡回显。";
    },
    reviewRiskText() {
      if (!this.evaluate.evaluate_content) {
        return "当前还没有填写评价内容，提交会被拦截。";
      }
      if (Number(this.evaluate.evaluate_num || 0) <= 2) {
        return "当前评分较低，正式环境提交前请再次确认评价内容是否准确。";
      }
      return "当前评价信息已基本齐备，提交后会按当前环境写入订单评价。";
    },
  },
  onLoad(options) {
    if (options.id) {
      this.evaluate.id = Number(options.id);
    } else {
      this.hasRequiredParam = false;
      this.updateRecentActionSummary("未提供订单标识，已切换到参数缺失承接态。");
      uni.setNavigationBarTitle({
        title: "订单评价",
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
    saveRecentEvaluationRuntime() {
      cache.set(
        RECENT_ORDER_RUNTIME_KEY,
        {
          envLabel: this.currentEnvInfo.label,
          envTag: this.envIsolationText,
          successAt: new Date().toLocaleString("zh-CN", { hour12: false }),
          targetStatus: 4,
          targetStatusText: "已评价",
          actionType: "evaluate_submit",
          actionTitle: "订单评价已提交",
          actionDesc:
            "评价内容已提交，可回到订单列表继续核对完成订单与评价回显。",
          totalPrice: "0.00",
          goodsCount: 0,
          merchantTitles: [],
          payTypeText: `评分 ${Number(this.evaluate.evaluate_num || 5)} 星`,
          deliveryTypeText: "订单评价",
          orderId: this.evaluate.id || "",
          orderNo: "",
        },
        7200,
      );
    },
    submitEvaluation() {
      if (this.isLoad) {
        return false;
      }
      if (!this.evaluate.evaluate_content) {
        this.updateRecentActionSummary("评价提交被拦截：未填写评价内容。");
        uni.showToast({
          icon: "none",
          position: "bottom",
          title: "请输入评价内容",
        });
        return false;
      }
      const confirmContent = buildEnvConfirmText(this.currentEnvInfo, {
        prod: "当前为正式环境，提交后会写入真实评价内容，确认继续吗？",
        test: "确认继续提交测试评价吗？",
      });
      uni.showModal({
        title: "提交确认",
        content: confirmContent,
        success: (modalRes) => {
          if (!modalRes.confirm) {
            return;
          }
          this.updateRecentActionSummary(
            `准备提交订单评价：${Number(this.evaluate.evaluate_num || 5)} 星。`,
          );
          this.isLoad = true;
          api
            .submitEvaluation(this.evaluate)
            .then(() => {
              this.updateRecentActionSummary(
                "订单评价已提交，准备返回订单列表。",
              );
              this.saveRecentEvaluationRuntime();
              uni.redirectTo({
                url: "/pages/order/list?status=4",
              });
            })
            .catch(() => {
              this.updateRecentActionSummary("订单评价提交失败。");
            })
            .finally(() => {
              this.isLoad = false;
            });
        },
      });
    },
  },
};
</script>

<style lang="scss">
.zaiui-btn-view {
  position: fixed;
  width: 100%;
  bottom: 0;
  .flex {
    padding: 50rpx;
  }
}

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
  background: linear-gradient(180deg, #fff7ed 0%, #ffedd5 100%);
  color: #9a3412;
  font-size: 22rpx;
  line-height: 1.65;
}
</style>

