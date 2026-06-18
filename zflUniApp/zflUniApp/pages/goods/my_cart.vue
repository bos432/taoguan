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
    <view class="cart-page">
      <view class="hero-card">
        <view class="hero-main">
          <view>
            <view class="hero-title">购物车</view>
            <view class="hero-subtitle"
              >集中管理待购买商品，支持编辑、删除和结算</view
            >
          </view>
          <view class="hero-badge">{{
            goods_checked ? "编辑模式" : "结算模式"
          }}</view>
        </view>

        <view class="env-inline-card">
          <view class="env-inline-card__head">
            <text class="env-inline-card__title">当前环境</text>
            <text
              class="env-inline-card__badge"
              :class="currentEnvInfo.is_prod ? 'is-prod' : 'is-test'"
            >
              {{ currentEnvInfo.label }}
            </text>
          </view>
          <view class="env-inline-card__desc">{{ envDescription }}</view>
          <view class="env-inline-card__meta">
            <text class="env-inline-card__tag">{{ cartModeText }}</text>
            <text class="env-inline-card__tag">{{ selectionStatusText }}</text>
            <text class="env-inline-card__tag">{{ envIsolationText }}</text>
            <text class="env-inline-card__tag">{{
              envIsolationStatusText
            }}</text>
            <text class="env-inline-card__tag">{{ envReleaseStageText }}</text>
          </view>
          <view class="env-inline-card__note">{{ envActionHint }}</view>
          <view class="env-inline-card__note env-inline-card__note--strong">{{
            envReleaseHint
          }}</view>
          <view v-if="envRiskList.length" class="env-inline-card__risk-list">
            <view
              v-for="item in envRiskList"
              :key="item"
              class="env-inline-card__risk-item"
              >{{ item }}</view
            >
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
          <view class="env-inline-card__url">{{
            currentEnvInfo.api_root_url || "未配置接口地址"
          }}</view>
        </view>

        <view class="hero-metrics">
          <view class="hero-metric">
            <text class="metric-label">商家数</text>
            <text class="metric-value">{{ goodsList.length }}</text>
          </view>
          <view class="hero-metric">
            <text class="metric-label">已选商品</text>
            <text class="metric-value">{{ totalQuantity }}</text>
          </view>
          <view class="hero-metric">
            <text class="metric-label">合计金额</text>
            <text class="metric-value">￥{{ totalPrice.toFixed(2) }}</text>
          </view>
        </view>
      </view>

      <view class="review-card">
        <view class="review-card__title">操作前复核</view>
        <view class="review-card__desc">{{ actionReviewHint }}</view>
        <view class="review-card__tags">
          <text class="review-card__tag">{{ actionReviewTags.mode }}</text>
          <text class="review-card__tag">{{ actionReviewTags.selection }}</text>
          <text class="review-card__tag">{{ actionReviewTags.merchant }}</text>
          <text class="review-card__tag">{{ actionReviewTags.amount }}</text>
        </view>
        <view class="review-card__risk">{{ actionRiskHint }}</view>
      </view>

      <view v-if="recentActionSummary" class="recent-action-card">
        <view class="recent-action-card__title">最近操作</view>
        <view class="recent-action-card__desc">{{ recentActionSummary }}</view>
      </view>

      <view class="review-card">
        <view class="review-card__title">结算跟进</view>
        <view class="review-card__desc">{{ cartFollowupHint }}</view>
        <view class="review-card__tags">
          <text
            class="review-card__tag"
            v-for="item in cartFollowupTags"
            :key="item"
            >{{ item }}</text
          >
        </view>
        <view class="review-card__risk">{{ cartFollowupRiskText }}</view>
      </view>

      <view class="cart-group">
        <view
          v-for="(shop, shopIndex) in goodsList"
          :key="shop.id"
          class="shop-card"
        >
          <view class="shop-head">
            <view class="shop-left">
              <checkbox
                class="round red sm"
                :class="shop.checked ? 'checked' : ''"
                :checked="shop.checked ? true : false"
                @tap.stop="toggleShop(shopIndex)"
              />
              <view class="shop-name">{{
                merchantTitleText(shop.name, "平台直营")
              }}</view>
            </view>
            <view class="shop-count">{{ shop.goods.length }} 件商品</view>
          </view>

          <view
            v-for="(goods, goodsIndex) in shop.goods"
            :key="goods.id"
            class="goods-card"
          >
            <checkbox
              class="round red sm"
              :class="goods.checked ? 'checked' : ''"
              :checked="goods.checked ? true : false"
              @tap.stop="toggleGoods(shopIndex, goodsIndex)"
            />
            <image
              class="goods-image"
              :src="goods.img"
              mode="aspectFill"
            ></image>
            <view class="goods-body">
              <view class="goods-title">{{ goods.name }}</view>
              <view class="goods-spec">{{
                goods.spec ||
                "剩余" + goods.stock + (goods.unit ? goods.unit : "件")
              }}</view>
              <view
                v-if="goods.labels && goods.labels.length"
                class="goods-tags"
              >
                <text
                  v-for="(label, labelIndex) in goods.labels"
                  :key="labelIndex"
                  class="goods-tag"
                  >{{ label }}</text
                >
              </view>
              <view class="goods-meta">
                <text class="goods-price"
                  >￥{{ Number(goods.price || 0).toFixed(2) }}</text
                >
                <u-number-box
                  v-model="goods.num"
                  @change="(e) => numChange(e, shopIndex, goodsIndex)"
                  :min="1"
                  :max="goods.stock"
                >
                  <view slot="minus" class="step-btn minus">
                    <u-icon name="minus" size="12"></u-icon>
                  </view>
                  <text slot="input" class="step-input">{{ goods.num }}</text>
                  <view slot="plus" class="step-btn plus">
                    <u-icon name="plus" color="#FFFFFF" size="12"></u-icon>
                  </view>
                </u-number-box>
              </view>
            </view>
          </view>
        </view>
      </view>

      <view v-if="!isLoad && !goodsList.length" class="empty-state"
        >购物车还是空的，先去挑选喜欢的商品吧</view
      >
      <view class="loading-box" v-if="isLoad">加载中...</view>

      <view class="page-bottom-space"></view>

      <view class="action-bar">
        <view class="action-left" @tap="toggleCheckAll">
          <checkbox
            class="round red sm"
            :class="checkAll ? 'checked' : ''"
            :checked="checkAll"
          ></checkbox>
          <text class="action-all">全选</text>
        </view>
        <view class="action-summary">
          <view class="summary-price">￥{{ totalPrice.toFixed(2) }}</view>
          <view class="summary-count">已选 {{ totalQuantity }} 件</view>
        </view>
        <view class="action-buttons">
          <button
            v-if="goods_checked"
            class="action-btn warning"
            @tap="shopCartDel"
            :loading="btn_loading"
          >
            删除
          </button>
          <button v-else class="action-btn primary" @tap="tapBtn">
            去结算
          </button>
        </view>
      </view>

      <view class="edit-fab" @tap="barEditTap">
        <button class="edit-btn">{{ goods_checked ? "完成" : "编辑" }}</button>
      </view>
    </view>
  </view>
</template>

<script>
import UNumberBox from "@/uni_modules/uview-ui/components/u-number-box/u-number-box.vue";
import api from "@/api";
import { maskMerchantTitle } from "@/utils/desensitize.js";
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

export default {
  components: {
    UNumberBox,
  },
  data() {
    return {
      goodsList: [],
      checkAll: false,
      goods_checked: false,
      isLoad: false,
      btn_loading: false,
      totalPrice: 0,
      totalQuantity: 0,
      recentActionSummary: "",
    };
  },
  computed: {
    currentEnvInfo() {
      return getCurrentEnvInfo();
    },
    envDescription() {
      return this.currentEnvInfo.is_prod
        ? "当前为正式环境，删除购物车商品和去结算都会直接进入真实业务流程。"
        : "当前为非正式环境，适合验证购物车编辑、删除和结算联调。";
    },
    cartModeText() {
      return this.goods_checked ? "当前模式：编辑购物车" : "当前模式：准备结算";
    },
    selectionStatusText() {
      return this.totalQuantity > 0
        ? `已选商品：${this.totalQuantity} 件`
        : "已选商品：0 件";
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
      if (this.goods_checked) {
        return this.currentEnvInfo.is_prod
          ? `编辑模式下删除会直接影响真实购物车，请先确认勾选范围。${getEnvIsolationHint(this.currentEnvInfo)}`
          : `编辑模式下适合验证删除、数量修改和全选联动。${getEnvIsolationHint(this.currentEnvInfo)}`;
      }
      return this.currentEnvInfo.is_prod
        ? `结算前请确认只勾选了同一商家的商品，避免进入真实订单流程后再回退。${getEnvIsolationHint(this.currentEnvInfo)}`
        : `建议先在当前环境确认勾选、数量和商家范围，再进入结算联调。${getEnvIsolationHint(this.currentEnvInfo)}`;
    },
    selectedMerchantCount() {
      return this.goodsList.filter((shop) =>
        shop.goods.some((goods) => goods.checked),
      ).length;
    },
    actionReviewHint() {
      return this.goods_checked
        ? "编辑模式下可执行删除与数量调整，提交前请先确认勾选范围。"
        : "结算模式下仅支持同一商家商品一起去结算，请先核对勾选范围和数量。";
    },
    actionReviewTags() {
      return {
        mode: this.cartModeText,
        selection: this.selectionStatusText,
        merchant:
          this.selectedMerchantCount > 0
            ? `已选商家：${this.selectedMerchantCount} 个`
            : "已选商家：0 个",
        amount: `合计金额：￥${this.totalPrice.toFixed(2)}`,
      };
    },
    actionRiskHint() {
      if (this.totalQuantity <= 0) {
        return this.goods_checked
          ? "当前没有勾选商品，删除操作会被拦截。"
          : "当前没有勾选商品，无法进入结算。";
      }
      if (!this.goods_checked && this.selectedMerchantCount > 1) {
        return "当前跨多个商家勾选，结算前需要收敛到单个商家。";
      }
      return this.currentEnvInfo.is_prod
        ? "当前为正式环境，删除和去结算都会影响真实购物车与订单流程，请再次确认。"
        : "当前为非正式环境，建议先验证勾选、数量变更、删除和结算跳转链路。";
    },
    cartFollowupHint() {
      if (this.goods_checked) {
        return "编辑模式下更适合做数量调整和删除收口，完成后建议切回结算模式再核对商家范围。";
      }
      if (this.totalQuantity <= 0) {
        return "当前还没有选中商品，建议先选中同一商家的商品，再继续进入结算链路。";
      }
      return "当前已进入结算前状态，建议继续核对商家范围、数量和金额，再跳转结算页。";
    },
    cartFollowupTags() {
      return [
        this.goods_checked ? "下一步：删除或调整数量" : "下一步：进入结算",
        `勾选商家：${this.selectedMerchantCount} 个`,
        `勾选件数：${this.totalQuantity} 件`,
        this.checkAll ? "全选状态：是" : "全选状态：否",
      ];
    },
    cartFollowupRiskText() {
      if (this.goods_checked && this.totalQuantity <= 0) {
        return "当前处于编辑模式，但没有勾选商品，删除动作不会生效。";
      }
      if (!this.goods_checked && this.selectedMerchantCount > 1) {
        return "当前已勾选多个商家的商品，无法直接结算，建议先收敛到单个商家。";
      }
      return this.currentEnvInfo.is_prod
        ? "当前为正式环境，购物车删除和去结算都会承接真实订单链路，请以勾选结果和金额为准继续操作。"
        : "当前为非正式环境，建议继续联调勾选、删除、结算跳转以及返回购物车后的状态回显。";
    },
  },
  onLoad() {
    this.getList();
  },
  methods: {
    setRecentActionSummary(action, extra = "") {
      this.recentActionSummary = `已执行${action}${extra ? `，${extra}` : ""}。`;
    },
    merchantTitleText(value, fallback = "平台直营") {
      return maskMerchantTitle(value, fallback);
    },
    getGoodsIds() {
      const ids = [];
      this.goodsList.forEach((shop) => {
        shop.goods.forEach((goods) => {
          if (goods.checked) {
            ids.push(goods.id);
          }
        });
      });
      return ids;
    },
    getCartIds() {
      const ids = [];
      this.goodsList.forEach((shop) => {
        shop.goods.forEach((goods) => {
          if (goods.checked) {
            ids.push(goods.cart_id);
          }
        });
      });
      return ids;
    },
    shopCartDel() {
      const goods_ids = this.getCartIds();
      if (goods_ids.length <= 0) {
        uni.showToast({
          icon: "none",
          title: "请选择需要删除的商品",
        });
        return false;
      }
      uni.showModal({
        title: "温馨提示",
        content: buildEnvConfirmText(this.currentEnvInfo, {
          prod: "当前为正式环境，确定删除购物车中的已选商品吗？删除后会直接影响真实购物车数据。",
          test: "确定继续测试删除购物车商品吗？",
        }),
        cancelText: "取消",
        confirmText: "确定",
        success: (res) => {
          if (!res.confirm) {
            return;
          }
          this.btn_loading = true;
          api
            .shopCartDel({ ids: goods_ids })
            .then((resp) => {
              this.getList(2);
              this.setRecentActionSummary(
                "删除购物车商品",
                `影响 ${goods_ids.length} 条记录`,
              );
              uni.showToast({
                icon: "none",
                title: resp.msg,
              });
            })
            .catch(() => {})
            .finally(() => {
              this.btn_loading = false;
            });
        },
      });
    },
    numChange(e, shop_index, goods_index) {
      this.goodsList[shop_index].goods[goods_index].num = e.value;
      this.setRecentActionSummary(
        "调整商品数量",
        `${this.goodsList[shop_index].goods[goods_index].name || "商品"} -> ${e.value}`,
      );
      api
        .shopCartEdit({
          id: this.goodsList[shop_index].goods[goods_index].cart_id,
          cart_num: this.goodsList[shop_index].goods[goods_index].num,
        })
        .catch(() => {});
      this.updateSummary();
    },
    toggleShop(shopIndex) {
      const shop = this.goodsList[shopIndex];
      if (!shop) {
        return;
      }
      const nextChecked = !shop.checked;
      shop.checked = nextChecked;
      shop.goods.forEach((goods) => {
        goods.checked = nextChecked;
      });
      this.updateSummary();
    },
    toggleGoods(shopIndex, goodsIndex) {
      const shop = this.goodsList[shopIndex];
      if (!shop || !shop.goods[goodsIndex]) {
        return;
      }
      shop.goods[goodsIndex].checked = !shop.goods[goodsIndex].checked;
      shop.checked =
        shop.goods.length > 0 && shop.goods.every((goods) => goods.checked);
      this.updateSummary();
    },
    updateSummary() {
      let total = 0;
      let quantity = 0;
      this.goodsList.forEach((shop) => {
        shop.goods.forEach((goods) => {
          if (goods.checked) {
            total += Number(goods.price || 0) * Number(goods.num || 0);
            quantity += Number(goods.num || 0);
          }
        });
      });
      this.totalPrice = total;
      this.totalQuantity = quantity;
      this.checkAll =
        this.goodsList.length > 0 &&
        this.goodsList.every((shop) =>
          shop.goods.every((goods) => goods.checked),
        );
    },
    getList(type = 1) {
      this.isLoad = true;
      api
        .shopCartList({})
        .then((res) => {
          this.goodsList = res.data.list || [];
          if (type == 2) {
            this.updateSummary();
          } else {
            this.totalPrice = 0;
            this.totalQuantity = 0;
            this.checkAll = false;
          }
        })
        .catch(() => {})
        .finally(() => {
          this.isLoad = false;
        });
    },
    toggleCheckAll() {
      this.checkAll = !this.checkAll;
      this.goodsList.forEach((shop) => {
        shop.checked = this.checkAll;
        shop.goods.forEach((goods) => {
          goods.checked = this.checkAll;
        });
      });
      this.updateSummary();
    },
    barEditTap() {
      this.goods_checked = !this.goods_checked;
      this.setRecentActionSummary(
        this.goods_checked ? "切换到编辑模式" : "切换到结算模式",
      );
    },
    tapBtn() {
      const goods_ids = this.getGoodsIds();
      if (goods_ids.length <= 0) {
        uni.showToast({
          icon: "none",
          title: "请选择需要结算的商品",
        });
        return false;
      }
      const mer_ids = [];
      this.goodsList.forEach((shop) => {
        let is_mer = false;
        shop.goods.forEach((goods) => {
          if (goods.checked) {
            is_mer = true;
          }
        });
        if (is_mer) {
          mer_ids.push(shop.id);
        }
      });
      if (mer_ids.length > 1) {
        uni.showToast({
          icon: "none",
          title: "请选择单个商家的商品进行结算",
        });
        return false;
      }
      uni.showModal({
        title: "去结算",
        content: buildEnvConfirmText(this.currentEnvInfo, {
          prod: "当前为正式环境，继续后将进入真实结算流程，确认继续吗？",
          test: "继续后将进入结算联调流程，确认继续吗？",
        }),
        success: (modalRes) => {
          if (!modalRes.confirm) {
            return;
          }
          this.setRecentActionSummary(
            "进入结算",
            `商品 ${goods_ids.length} 件，商家 ${mer_ids.length} 个`,
          );
          uni.navigateTo({
            url:
              "/pages/goods/settlement?source=shop_cart&goods_ids=" + goods_ids,
          });
        },
      });
    },
  },
};
</script>

<style scoped lang="scss">
.cart-page {
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
.shop-card,
.action-bar {
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
.hero-metrics,
.goods-meta,
.action-bar {
  display: flex;
  justify-content: space-between;
}

.env-inline-card {
  margin-top: 24rpx;
  padding: 18rpx 22rpx;
  border-radius: 22rpx;
  background: rgba(255, 255, 255, 0.12);
}

.env-inline-card__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16rpx;
}

.env-inline-card__title {
  font-size: 22rpx;
  color: rgba(255, 255, 255, 0.82);
}

.env-inline-card__badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 40rpx;
  padding: 0 16rpx;
  border-radius: 999rpx;
  font-size: 20rpx;
  color: #ffffff;
  background: rgba(28, 187, 180, 0.9);
}

.env-inline-card__badge.is-prod {
  background: rgba(239, 68, 68, 0.9);
}

.env-inline-card__desc {
  margin-top: 10rpx;
  font-size: 22rpx;
  line-height: 1.7;
  color: rgba(255, 255, 255, 0.84);
}

.env-inline-card__meta {
  display: flex;
  flex-wrap: wrap;
  gap: 10rpx;
  margin-top: 10rpx;
}

.env-inline-card__tag {
  display: inline-flex;
  align-items: center;
  min-height: 38rpx;
  padding: 0 16rpx;
  border-radius: 999rpx;
  background: rgba(255, 255, 255, 0.14);
  color: rgba(255, 255, 255, 0.88);
  font-size: 20rpx;
}

.env-inline-card__note {
  margin-top: 10rpx;
  font-size: 21rpx;
  line-height: 1.7;
  color: rgba(255, 255, 255, 0.8);
}

.env-inline-card__note--strong {
  background: rgba(255, 255, 255, 0.14);
  color: rgba(255, 255, 255, 0.92);
  padding: 14rpx 16rpx;
  border-radius: 18rpx;
}

.env-inline-card__risk-list {
  margin-top: 12rpx;
  padding: 14rpx 16rpx;
  border-radius: 18rpx;
  background: rgba(255, 244, 232, 0.18);
}

.env-inline-card__risk-item {
  color: rgba(255, 248, 241, 0.92);
  font-size: 21rpx;
  line-height: 1.7;
}

.env-inline-card__risk-item + .env-inline-card__risk-item {
  margin-top: 8rpx;
}

.env-inline-card__url {
  margin-top: 8rpx;
  font-size: 20rpx;
  line-height: 1.6;
  color: rgba(255, 255, 255, 0.66);
  word-break: break-all;
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

.hero-badge {
  padding: 12rpx 18rpx;
  border-radius: 999rpx;
  background: rgba(255, 255, 255, 0.14);
  font-size: 22rpx;
}

.hero-metrics {
  gap: 18rpx;
  margin-top: 28rpx;
}

.env-profile-board {
  margin-top: 14rpx;
  padding: 16rpx 18rpx;
  border-radius: 20rpx;
  background: rgba(255, 255, 255, 0.08);
}

.env-profile-board__title {
  font-size: 24rpx;
  font-weight: 700;
  color: #ffffff;
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
  background: rgba(255, 255, 255, 0.1);
}

.env-profile-board__item.is-current {
  border: 1rpx solid rgba(255, 255, 255, 0.28);
}

.env-profile-board__item-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12rpx;
}

.env-profile-board__item-name {
  font-size: 22rpx;
  color: #ffffff;
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
  color: rgba(255, 255, 255, 0.78);
}

.review-card,
.recent-action-card {
  margin-top: 20rpx;
  padding: 26rpx;
  border-radius: 28rpx;
  background: #ffffff;
  box-shadow: 0 18rpx 40rpx rgba(23, 35, 51, 0.08);
}

.review-card__title,
.recent-action-card__title {
  font-size: 28rpx;
  font-weight: 700;
  color: #172333;
}

.review-card__desc,
.recent-action-card__desc {
  margin-top: 12rpx;
  font-size: 24rpx;
  line-height: 1.6;
  color: #667487;
}

.review-card__tags {
  display: flex;
  flex-wrap: wrap;
  gap: 12rpx;
  margin-top: 18rpx;
}

.review-card__tag {
  padding: 10rpx 18rpx;
  border-radius: 999rpx;
  background: #eef6ff;
  color: #1c5c88;
  font-size: 22rpx;
}

.review-card__risk {
  margin-top: 18rpx;
  padding: 18rpx 20rpx;
  border-radius: 20rpx;
  background: #fff6eb;
  color: #b25b17;
  font-size: 24rpx;
  line-height: 1.6;
}

.hero-metric {
  flex: 1;
  padding: 20rpx;
  border-radius: 22rpx;
  background: rgba(255, 255, 255, 0.12);
}

.metric-label {
  display: block;
  font-size: 22rpx;
  color: rgba(255, 255, 255, 0.82);
}

.metric-value {
  display: block;
  margin-top: 12rpx;
  font-size: 30rpx;
  font-weight: 700;
}

.cart-group {
  display: block;
  margin-top: 20rpx;
}

.shop-card + .shop-card {
  margin-top: 20rpx;
}

.shop-card {
  padding: 28rpx;
}

.shop-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 20rpx;
}

.shop-left {
  display: flex;
  align-items: center;
  gap: 16rpx;
  min-width: 0;
}

.shop-name {
  font-size: 28rpx;
  font-weight: 700;
  color: #172333;
}

.shop-count {
  font-size: 22rpx;
  color: #718093;
}

.goods-card {
  display: flex;
  gap: 16rpx;
  margin-top: 20rpx;
  padding: 20rpx;
  border-radius: 24rpx;
  background: linear-gradient(180deg, #f9fbfc 0%, #f0f4f7 100%);
  align-items: center;
}

.goods-image {
  width: 144rpx;
  height: 144rpx;
  border-radius: 18rpx;
  flex-shrink: 0;
}

.goods-body {
  flex: 1;
  min-width: 0;
}

.goods-title {
  font-size: 28rpx;
  font-weight: 600;
  color: #172333;
  line-height: 1.45;
}

.goods-spec {
  margin-top: 8rpx;
  font-size: 22rpx;
  color: #718093;
}

.goods-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 10rpx;
  margin-top: 12rpx;
}

.goods-tag {
  padding: 8rpx 16rpx;
  border-radius: 999rpx;
  background: rgba(236, 138, 87, 0.12);
  color: #cf5a48;
  font-size: 20rpx;
}

.goods-meta {
  align-items: center;
  margin-top: 18rpx;
  gap: 20rpx;
}

.goods-price {
  font-size: 30rpx;
  font-weight: 700;
  color: #cf5a48;
}

.step-btn {
  width: 40rpx;
  height: 40rpx;
  border-radius: 999rpx;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.step-btn.minus {
  background: #edf1f5;
}

.step-btn.plus {
  background: linear-gradient(90deg, #1c5c88 0%, #ec8a57 100%);
}

.step-input {
  min-width: 44rpx;
  text-align: center;
  font-size: 24rpx;
  color: #172333;
}

.empty-state,
.loading-box {
  padding: 80rpx 0 20rpx;
  text-align: center;
  font-size: 24rpx;
  color: #7f8a99;
}

.page-bottom-space {
  height: 220rpx;
}

.action-bar {
  position: fixed;
  left: 24rpx;
  right: 24rpx;
  bottom: calc(24rpx + env(safe-area-inset-bottom));
  align-items: center;
  gap: 20rpx;
  padding: 20rpx 24rpx;
  z-index: 30;
}

.action-left {
  display: flex;
  align-items: center;
  gap: 10rpx;
}

.action-all,
.summary-count {
  font-size: 22rpx;
  color: #667487;
}

.action-summary {
  flex: 1;
  min-width: 0;
}

.summary-price {
  font-size: 30rpx;
  font-weight: 700;
  color: #cf5a48;
}

.action-buttons {
  display: flex;
  gap: 12rpx;
}

.action-btn {
  min-width: 160rpx;
  height: 76rpx;
  padding: 0 24rpx;
  border: none;
  border-radius: 999rpx;
  font-size: 26rpx;
  line-height: 76rpx;
  color: #ffffff;
}

.action-btn::after {
  border: none;
}

.action-btn.primary {
  background: linear-gradient(90deg, #1c5c88 0%, #ec8a57 100%);
}

.action-btn.warning {
  background: linear-gradient(90deg, #f36f63 0%, #d84c45 100%);
}

.edit-fab {
  position: fixed;
  right: 24rpx;
  bottom: calc(128rpx + env(safe-area-inset-bottom));
  z-index: 31;
}

.edit-btn {
  min-width: 120rpx;
  height: 68rpx;
  padding: 0 24rpx;
  border: none;
  border-radius: 999rpx;
  background: rgba(23, 35, 51, 0.82);
  color: #ffffff;
  font-size: 24rpx;
  line-height: 68rpx;
}

.edit-btn::after {
  border: none;
}
</style>

