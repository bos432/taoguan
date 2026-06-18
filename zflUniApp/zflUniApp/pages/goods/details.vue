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
    <view class="detail-page">
      <template v-if="info.id">
        <view class="gallery-card">
          <swiper
            class="gallery-swiper"
            circular
            autoplay
            @change="bannerSwiper"
          >
            <swiper-item v-for="(item, index) in bannerList" :key="index">
              <image
                class="gallery-image"
                :src="
                  resolveImageUrl(
                    item.url || item.file_url || item.image_url,
                    defaultImage,
                  )
                "
                mode="aspectFill"
              ></image>
            </swiper-item>
          </swiper>
          <view class="gallery-badge"
            >{{ bannerCur + 1 }} / {{ bannerList.length || 1 }}</view
          >
        </view>

        <view class="hero-card">
          <view class="hero-top">
            <view>
              <view class="price-row">
                <text class="price-symbol">￥</text>
                <text class="price-value">{{ formatMoney(info.price) }}</text>
              </view>
              <view class="price-meta"
                >原价 ￥{{ formatMoney(info.original_price) }} · 库存
                {{ info.stock || 0 }}{{ unitText }}</view
              >
            </view>
            <view class="hero-side">
              <text class="hero-side-label">已售</text>
              <text class="hero-side-value"
                >{{ info.sales_sum || 0 }}{{ unitText }}</text
              >
              <text class="hero-side-tip"
                >{{ info.click_count || 0 }} 人浏览</text
              >
            </view>
          </view>

          <view v-if="labelList.length" class="label-row">
            <text
              v-for="(item, index) in labelList"
              :key="index"
              class="label-chip"
              >{{ item }}</text
            >
          </view>

          <view class="hero-title">{{ info.title }}</view>
          <view class="hero-subtitle">{{ info.spec || "标准规格" }}</view>
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
          <view class="section-tip env-desc">{{ envDescription }}</view>
          <view class="env-tags">
            <text class="env-tag">{{ goodsModeText }}</text>
            <text class="env-tag">{{ goodsStateText }}</text>
            <text class="env-tag">{{ envIsolationText }}</text>
            <text class="env-tag">{{ envIsolationStatusText }}</text>
            <text class="env-tag">{{ envReleaseStageText }}</text>
          </view>
          <view class="env-note">{{ envActionHint }}</view>
          <view class="env-note env-note--strong">{{ envReleaseHint }}</view>
          <view v-if="envRiskList.length" class="env-risk-list">
            <view
              v-for="item in envRiskList"
              :key="item"
              class="env-risk-item"
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
          <view class="env-url">{{
            currentEnvInfo.api_root_url || "未配置接口地址"
          }}</view>
        </view>

        <view class="section-card review-card">
          <view class="section-head">
            <view class="section-title">操作前复核</view>
            <view class="section-tip">下单与线下处理前先核对</view>
          </view>
          <view class="review-desc">{{ submitReviewHint }}</view>
          <view class="review-tags">
            <text class="review-tag">{{ submitReviewTags.mode }}</text>
            <text class="review-tag">{{ submitReviewTags.stock }}</text>
            <text class="review-tag">{{ submitReviewTags.cart }}</text>
            <text class="review-tag">{{ submitReviewTags.owner }}</text>
          </view>
          <view class="review-risk">{{ submitRiskHint }}</view>
        </view>

        <view class="section-card review-card">
          <view class="section-head">
            <view class="section-title">操作后跟进</view>
            <view class="section-tip">{{ detailFollowupBadgeText }}</view>
          </view>
          <view class="review-desc">{{ detailFollowupHint }}</view>
          <view class="review-tags">
            <text
              class="review-tag"
              v-for="item in detailFollowupTags"
              :key="item"
              >{{ item }}</text
            >
          </view>
          <view class="review-risk">{{ detailFollowupRiskText }}</view>
        </view>

        <view v-if="recentActionSummary" class="section-card review-card">
          <view class="section-title">最近操作</view>
          <view class="review-desc">{{ recentActionSummary }}</view>
        </view>

        <view v-if="info.spec || info.unit" class="section-card">
          <view class="section-head">
            <view class="section-title">商品信息</view>
            <view class="section-tip">核心参数一目了然</view>
          </view>
          <view class="info-grid">
            <view v-if="info.spec" class="info-item">
              <text class="info-label">规格</text>
              <text class="info-value">{{ info.spec }}</text>
            </view>
            <view v-if="info.unit" class="info-item">
              <text class="info-label">单位</text>
              <text class="info-value">{{ info.unit }}</text>
            </view>
            <view class="info-item">
              <text class="info-label">商品来源</text>
              <text class="info-value">{{
                info.source === 1 ? "线下商品" : "商城商品"
              }}</text>
            </view>
            <view class="info-item">
              <text class="info-label">购物车数量</text>
              <text class="info-value">{{ info.shop_cart_num || 0 }}</text>
            </view>
          </view>
        </view>

        <view v-if="merchantInfo.id" class="section-card">
          <view class="section-head">
            <view class="section-title">商家信息</view>
            <view class="section-tip">当前商品所属商家</view>
          </view>
          <view class="entity-card">
            <view class="entity-avatar">
              <text class="cuIcon-shopfill"></text>
            </view>
            <view class="entity-body">
              <view class="entity-title">{{ merchantDisplayTitle }}</view>
              <view class="entity-desc">{{
                merchantInfo.address || "正品保障，售后无忧"
              }}</view>
            </view>
          </view>
        </view>

        <view v-if="memberInfo.member_id" class="section-card">
          <view class="section-head">
            <view class="section-title">发布信息</view>
            <view class="section-tip">商品发布会员</view>
          </view>
          <view class="entity-card">
            <image
              v-if="memberAvatar"
              class="member-avatar"
              :src="memberAvatar"
              mode="aspectFill"
            ></image>
            <view v-else class="entity-avatar">
              <text class="cuIcon-peoplefill"></text>
            </view>
            <view class="entity-body">
              <view class="entity-title">{{ memberDisplayTitle }}</view>
              <view class="entity-desc">正品保障，售后无忧</view>
            </view>
          </view>
        </view>

        <view class="section-card detail-card">
          <view class="section-head">
            <view class="section-title">图文详情</view>
            <view class="section-tip">查看商品完整介绍</view>
          </view>
          <rich-text class="detail-richtext" :nodes="detailContent"></rich-text>
        </view>
      </template>

      <view v-else-if="isLoad" class="loading-box">加载中...</view>
      <view v-else-if="loadError" class="empty-state">
        <view>{{ loadError }}</view>
        <view style="display: flex; gap: 20rpx; margin-top: 24rpx; width: 100%;">
          <button class="action-btn empty-action" style="background: #edf2f7; color: #37506c;" @tap="goBackOrHome">
            返回上一页
          </button>
          <button class="action-btn primary empty-action" @tap="retryLoad">
            重新加载
          </button>
        </view>
      </view>
      <view v-else class="empty-state">商品信息不存在或已下架</view>

      <view class="page-bottom-space"></view>

      <view v-if="info.id" class="action-bar">
        <view class="action-shortcuts">
          <button class="shortcut-btn" open-type="contact">
            <text class="cuIcon-service"></text>
            <text>客服</text>
          </button>
          <view class="shortcut-btn" @tap="myCartTap">
            <view class="shortcut-icon-box">
              <text class="cuIcon-cart"></text>
              <view v-if="info.shop_cart_num" class="shortcut-badge">{{
                info.shop_cart_num
              }}</view>
            </view>
            <text>购物车</text>
          </view>
        </view>

        <view class="action-group">
          <button
            v-if="info.source != 1"
            class="action-btn secondary"
            @tap="shopCartAdd"
            :loading="isBtn"
            :disabled="!canPurchase"
          >
            加入购物车
          </button>
          <button
            v-if="info.source != 1"
            class="action-btn primary"
            @tap="buy"
            :loading="isBuyLoading"
            :disabled="!canPurchase"
          >
            {{ buyButtonText }}
          </button>
          <button
            v-if="info.source == 1 && info.is_del == 1"
            class="action-btn danger"
            @tap="del"
            :loading="btn_loading"
          >
            删除
          </button>
          <button
            v-if="info.source == 1 && info.is_del == 0"
            class="action-btn primary"
            @tap="transaction"
            :loading="btn_loading"
            :disabled="info.is_transaction == 1"
          >
            {{ info.is_transaction == 1 ? "已完成交易" : "线下交易" }}
          </button>
        </view>
      </view>
    </view>
  </view>
</template>

<script>
import api from "@/api";
import { maskMemberNickname, maskMerchantTitle } from "@/utils/desensitize.js";
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
import { resolveImageUrl as resolveSafeImageUrl } from "@/utils/resource.js";

export default {
  data() {
    return {
      bannerCur: 0,
      bannerList: [],
      info: {},
      goods_id: 0,
      defaultImage: "/static/images/avatar/1.jpg",
      isLoad: false,
      loadError: "",
      isBtn: false,
      isBuyLoading: false,
      btn_loading: false,
      recentActionSummary: "",
    };
  },
  computed: {
    labelList() {
      return this.info.labels || [];
    },
    merchantInfo() {
      return this.info.merchant || {};
    },
    merchantDisplayTitle() {
      return maskMerchantTitle(this.merchantInfo.title, "平台直营");
    },
    memberInfo() {
      return this.info.member || {};
    },
    memberDisplayTitle() {
      return maskMemberNickname(this.memberInfo.nickname, "匿名用户");
    },
    memberAvatar() {
      return this.resolveImageUrl(
        this.memberInfo.avatar && this.memberInfo.avatar.file_url
          ? this.memberInfo.avatar.file_url
          : "",
        "",
      );
    },
    unitText() {
      return this.info.unit || "件";
    },
    canPurchase() {
      return (
        Number(this.info.stock || 0) > 0 &&
        Number(this.info.buy_lock_by_other || 0) !== 1
      );
    },
    buyButtonText() {
      if (Number(this.info.stock || 0) <= 0) {
        return "已售罄";
      }
      if (Number(this.info.buy_lock_by_other || 0) === 1) {
        return this.info.buy_lock_text || "已被抢购";
      }
      if (Number(this.info.buy_lock_by_self || 0) === 1) {
        return this.info.buy_lock_text || "去下单";
      }
      return "立即购买";
    },
    detailContent() {
      return this.info.content || "";
    },
    currentEnvInfo() {
      return getCurrentEnvInfo();
    },
    envDescription() {
      if (Number(this.info.source || 0) === 1) {
        return this.currentEnvInfo.is_prod
          ? "当前为正式环境，线下交易完成和删除都会直接影响真实商品状态。"
          : "当前为非正式环境，适合验证线下商品交易完成和删除流程。";
      }
      return this.currentEnvInfo.is_prod
        ? "当前为正式环境，加入购物车和立即购买会直接进入线上下单流程。"
        : "当前为非正式环境，适合验证购物车、立即购买和结算联调。";
    },
    goodsModeText() {
      return Number(this.info.source || 0) === 1
        ? "当前类型：线下商品"
        : "当前类型：商城商品";
    },
    goodsStateText() {
      if (Number(this.info.stock || 0) <= 0) {
        return "当前状态：库存不足";
      }
      if (Number(this.info.buy_lock_by_other || 0) === 1) {
        return "当前状态：已被锁定";
      }
      return "当前状态：可继续操作";
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
      if (Number(this.info.source || 0) === 1) {
        return this.currentEnvInfo.is_prod
          ? `正式环境下，线下交易完成和删除都会直接改动真实商品状态，请先确认是本人发布的商品。${getEnvIsolationHint(this.currentEnvInfo)}`
          : `建议在当前环境检查线下商品的交易完成、删除和列表回跳是否正常。${getEnvIsolationHint(this.currentEnvInfo)}`;
      }
      return this.currentEnvInfo.is_prod
        ? `正式环境下，加入购物车和立即购买会进入真实下单流程，请先确认商品状态和归属。${getEnvIsolationHint(this.currentEnvInfo)}`
        : `建议在当前环境先验证加入购物车、立即购买、结算跳转和回显状态。${getEnvIsolationHint(this.currentEnvInfo)}`;
    },
    submitReviewTags() {
      return {
        mode:
          Number(this.info.source || 0) === 1
            ? "操作类型：线下交易/删除"
            : "操作类型：加购/下单",
        stock: `库存：${Number(this.info.stock || 0)}${this.unitText}`,
        cart: `购物车：${Number(this.info.shop_cart_num || 0)}`,
        owner: `归属：${this.merchantDisplayTitle}`,
      };
    },
    submitReviewHint() {
      if (Number(this.info.source || 0) === 1) {
        return this.currentEnvInfo.is_prod
          ? "当前为正式环境，线下交易完成或删除都会直接变更真实商品状态，请确认商品归属和当前状态。"
          : "当前为非正式环境，建议先验证线下交易完成、删除商品和列表回跳流程。";
      }
      return this.currentEnvInfo.is_prod
        ? "当前为正式环境，加入购物车和立即购买都会进入真实交易链路，请先确认库存和锁单状态。"
        : "当前为非正式环境，建议先验证加购、立即购买、结算跳转和返回回显。";
    },
    submitRiskHint() {
      if (!Number(this.info.id || 0)) {
        return "当前商品信息还未完成加载，暂不建议继续操作。";
      }
      if (
        Number(this.info.source || 0) !== 1 &&
        Number(this.info.stock || 0) <= 0
      ) {
        return "当前库存不足，加入购物车和立即购买都会被拦截。";
      }
      if (
        Number(this.info.source || 0) !== 1 &&
        Number(this.info.buy_lock_by_other || 0) === 1
      ) {
        return (
          this.info.buy_lock_text || "当前商品已被其他用户锁定，暂时无法购买。"
        );
      }
      if (
        Number(this.info.source || 0) === 1 &&
        Number(this.info.is_transaction || 0) === 1
      ) {
        return "当前线下商品已完成交易，只建议核对展示回显，不应重复提交。";
      }
      if (
        Number(this.info.source || 0) === 1 &&
        Number(this.info.is_del || 0) === 1
      ) {
        return "当前线下商品已删除，建议返回列表确认状态，不应重复操作。";
      }
      return this.currentEnvInfo.is_prod
        ? "正式环境下请先确认商品归属、库存和状态，再继续执行真实交易动作。"
        : "当前复核项已通过，可继续在测试环境验证交易链路。";
    },
    detailFollowupBadgeText() {
      if (Number(this.info.source || 0) === 1) {
        return Number(this.info.is_transaction || 0) === 1
          ? "已交易"
          : "待线下确认";
      }
      return this.canPurchase ? "可继续下单" : "待观察";
    },
    detailFollowupHint() {
      if (Number(this.info.source || 0) === 1) {
        return Number(this.info.is_transaction || 0) === 1
          ? "当前线下商品已完成交易，建议回到发布列表确认状态是否已同步。"
          : "当前线下商品仍可继续跟进交易，建议在线下确认后再执行完成交易或删除。";
      }
      return Number(this.info.shop_cart_num || 0) > 0
        ? "当前商品已进入购物车，可继续去购物车统一结算，或直接发起立即购买。"
        : "当前商品尚未加入购物车，可根据库存和锁单状态选择先加购或立即购买。";
    },
    detailFollowupTags() {
      return [
        Number(this.info.source || 0) === 1
          ? "后续入口：发布列表"
          : "后续入口：购物车/结算页",
        `购物车数量：${Number(this.info.shop_cart_num || 0)}`,
        Number(this.info.source || 0) === 1
          ? `交易状态：${Number(this.info.is_transaction || 0) === 1 ? "已完成" : "未完成"}`
          : `购买状态：${this.canPurchase ? "可购买" : "不可购买"}`,
        `浏览次数：${Number(this.info.click_count || 0)}`,
      ];
    },
    detailFollowupRiskText() {
      if (Number(this.info.source || 0) !== 1 && !this.canPurchase) {
        return (
          this.buyButtonText ||
          "当前商品暂时无法购买，建议先刷新商品状态再继续操作。"
        );
      }
      if (
        Number(this.info.source || 0) === 1 &&
        Number(this.info.is_del || 0) === 1
      ) {
        return "当前线下商品已删除，建议回到发布列表确认展示状态，不应继续重复操作。";
      }
      return this.currentEnvInfo.is_prod
        ? "当前为正式环境，详情页的加购、下单、删除和线下交易都会承接真实数据，请以下一页结果回显为准继续跟进。"
        : "当前为非正式环境，建议继续联调详情页到购物车、结算页和发布列表的回跳承接。";
    },
  },
  onLoad(options) {
    const goodsId = this.extractGoodsIdFromOptions(options);
    if (goodsId) {
      this.goods_id = goodsId;
      this.getInfo();
    } else {
      this.loadError = "商品参数异常，请返回商品列表重新进入";
      uni.showToast({
        icon: "none",
        title: "商品信息异常",
      });
    }
  },
  methods: {
    updateRecentActionSummary(summary) {
      this.recentActionSummary = summary;
    },
    resolveImageUrl(url, fallback = this.defaultImage) {
      return resolveSafeImageUrl(url, fallback);
    },
    normalizeGoodsId(value) {
      const text = String(
        value === undefined || value === null ? "" : value,
      ).trim();
      if (!/^\d+$/.test(text)) {
        return 0;
      }
      return Number(text);
    },
    extractGoodsIdFromOptions(options = {}) {
      const directGoodsId = this.normalizeGoodsId(
        options.goods_id || options.id || options.goodsId,
      );
      if (directGoodsId) {
        return directGoodsId;
      }

      const scene = String(options.scene || "").trim();
      if (!scene) {
        return 0;
      }

      const decodedScene = decodeURIComponent(scene);
      const matchedQuery = decodedScene.match(
        /(?:^|[?&])(goods_id|goodsId|id)=([0-9]+)/i,
      );
      if (matchedQuery && matchedQuery[2]) {
        return this.normalizeGoodsId(matchedQuery[2]);
      }

      return this.normalizeGoodsId(decodedScene);
    },
    transaction() {
      this.updateRecentActionSummary("准备提交线下交易完成。");
      uni.showModal({
        title: "温馨提示",
        content: buildEnvConfirmText(this.currentEnvInfo, {
          prod: "当前为正式环境，您确定与卖家线下交易成功了吗？此操作会直接变更真实商品状态。",
          test: "确定继续测试线下交易完成流程吗？",
        }),
        cancelText: "取消",
        confirmText: "确定",
        success: (res) => {
          if (!res.confirm) return;
          this.btn_loading = true;
          api
            .transactionRelease({ id: this.info.id })
            .then((resp) => {
              this.updateRecentActionSummary(
                "线下交易已提交完成，准备刷新商品状态。",
              );
              this.getInfo();
              uni.showToast({ icon: "none", title: resp.msg });
            })
            .catch(() => {
              this.updateRecentActionSummary("线下交易提交失败。");
            })
            .finally(() => {
              this.btn_loading = false;
            });
        },
      });
    },
    del() {
      this.updateRecentActionSummary("准备删除当前商品。");
      uni.showModal({
        title: "温馨提示",
        content: buildEnvConfirmText(this.currentEnvInfo, {
          prod: "当前为正式环境，确定删除您发布的该商品吗？删除后会直接影响真实商品展示。",
          test: "确定继续测试删除商品吗？",
        }),
        cancelText: "取消",
        confirmText: "确定",
        success: (res) => {
          if (!res.confirm) return;
          this.btn_loading = true;
          api
            .delRelease({ id: this.info.id })
            .then((resp) => {
              this.updateRecentActionSummary("商品已删除，准备返回发布列表。");
              uni.showToast({ icon: "none", title: resp.msg });
              uni.reLaunch({ url: "/pages/app/sell" });
            })
            .catch(() => {
              this.updateRecentActionSummary("商品删除失败。");
            })
            .finally(() => {
              this.btn_loading = false;
            });
        },
      });
    },
    buy() {
      if (!this.canPurchase) {
        this.updateRecentActionSummary("立即购买被拦截：商品已锁定或售罄。");
        uni.showToast({ icon: "none", title: "商品已被锁定或售罄" });
        return;
      }
      if (this.isBuyLoading || !this.info.id) {
        return;
      }
      this.updateRecentActionSummary("准备立即购买，进入结算前校验。");
      uni.showModal({
        title: "立即购买",
        content: buildEnvConfirmText(this.currentEnvInfo, {
          prod: "当前为正式环境，继续后将进入真实下单流程，确认继续吗？",
          test: "继续后将进入结算联调流程，确认继续吗？",
        }),
        success: (modalRes) => {
          if (!modalRes.confirm) {
            return;
          }
          this.isBuyLoading = true;
          api
            .getConfirmOrder({
              source: "details",
              goods_ids: String(this.info.id),
            })
            .then(() => {
              this.updateRecentActionSummary(
                "立即购买校验通过，准备跳转结算页。",
              );
              uni.navigateTo({
                url:
                  "/pages/goods/settlement?source=details&goods_ids=" +
                  this.info.id,
              });
            })
            .catch((error) => {
              if (error && error.msg) {
                uni.showToast({ icon: "none", title: error.msg });
              }
              this.updateRecentActionSummary(
                "立即购买校验失败，已回刷商品状态。",
              );
              this.getInfo();
            })
            .finally(() => {
              this.isBuyLoading = false;
            });
        },
      });
    },
    shopCartAdd() {
      if (this.isBtn || !this.info.id) return;
      if (!this.canPurchase) {
        this.updateRecentActionSummary("加入购物车被拦截：商品已锁定或售罄。");
        uni.showToast({ icon: "none", title: "商品已被锁定或售罄" });
        return;
      }
      if (this.info.source == 1) {
        this.updateRecentActionSummary("加入购物车被拦截：线下商品不支持。");
        uni.showToast({ icon: "none", title: "线下商品不支持加入购物车" });
        return;
      }
      this.updateRecentActionSummary("准备加入购物车。");
      uni.showModal({
        title: "加入购物车",
        content: buildEnvConfirmText(this.currentEnvInfo, {
          prod: "当前为正式环境，确认将该商品加入线上购物车吗？",
          test: "确认继续测试加入购物车吗？",
        }),
        success: (modalRes) => {
          if (!modalRes.confirm) {
            return;
          }
          this.isBtn = true;
          api
            .shopCartAdd({ goods_id: this.info.id })
            .then((resp) => {
              this.info.shop_cart_num = resp.data;
              this.updateRecentActionSummary(
                `商品已加入购物车，当前共 ${Number(this.info.shop_cart_num || 0)} 件。`,
              );
              uni.showToast({ icon: "none", title: resp.msg });
            })
            .catch(() => {
              this.updateRecentActionSummary("加入购物车失败。");
            })
            .finally(() => {
              this.isBtn = false;
            });
        },
      });
    },
    getInfo() {
      if (!this.goods_id) {
        this.info = {};
        this.bannerList = [];
        this.loadError = "商品参数异常，请返回商品列表重新进入";
        this.isLoad = false;
        return;
      }
      this.isLoad = true;
      this.updateRecentActionSummary("正在加载商品详情。");
      api
        .getGoodsInfo({ id: this.goods_id })
        .then((res) => {
          const data = res.data || {};
          this.info = data;
          this.bannerList =
            data.banner_list && data.banner_list.length
              ? data.banner_list
              : [{ url: data.image_url || this.defaultImage }];
          if (this.info.content) {
            this.info.content = this.formatRichText(this.info.content);
          }
          this.loadError = "";
          this.updateRecentActionSummary(
            `商品详情已刷新：库存 ${Number(this.info.stock || 0)}${this.unitText}。`,
          );
        })
        .catch(() => {
          this.info = {};
          this.bannerList = [];
          this.loadError = "商品信息暂时无法加载，请稍后重试";
          this.updateRecentActionSummary("商品详情加载失败。");
        })
        .finally(() => {
          this.isLoad = false;
        });
    },
    retryLoad() {
      if (!this.goods_id) {
        return;
      }
      this.loadError = "";
      this.getInfo();
    },
    goBackOrHome() {
      if (getCurrentPages().length > 1) {
        uni.navigateBack();
        return;
      }
      uni.switchTab({ url: "/pages/app/home" });
    },
    formatRichText(html) {
      return String(html || "")
        .replace(
          /<img([^>]*?)src=(['"])(.*?)\2([^>]*)>/gi,
          (match, before, quote, src, after) => {
            const nextAttrs =
              `${String(before || "")}${String(after || "")}`.replace(
                /\sstyle=(['"]).*?\1/gi,
                "",
              );
            const safeSrc = this.resolveImageUrl(src, this.defaultImage);
            return `<img${nextAttrs} src="${safeSrc}" style="max-width:100%;height:auto;display:block;" />`;
          },
        )
        .replace(/<table([^>]*)>/gi, (match, attrs) => {
          const nextAttrs = String(attrs || "").replace(
            /\sstyle=(['"]).*?\1/gi,
            "",
          );
          return (
            "<table" + nextAttrs + ' style="width:100%;table-layout:fixed;" />'
          );
        });
    },
    bannerSwiper(e) {
      this.bannerCur = e.detail.current;
      this.updateRecentActionSummary(
        `已切换到第 ${Number(this.bannerCur || 0) + 1} 张商品图。`,
      );
    },
    myCartTap() {
      this.updateRecentActionSummary("准备跳转购物车。");
      uni.navigateTo({ url: "/pages/goods/my_cart" });
    },
    formatMoney(value) {
      return Number(value || 0).toFixed(2);
    },
  },
};
</script>

<style scoped lang="scss">
.detail-page {
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
.gallery-card,
.hero-card,
.section-card,
.action-bar {
  background: rgba(255, 255, 255, 0.96);
  border-radius: 30rpx;
  box-shadow: 0 18rpx 42rpx rgba(21, 33, 52, 0.08);
}
.gallery-card {
  position: relative;
  overflow: hidden;
}
.gallery-swiper {
  height: 620rpx;
}
.gallery-image {
  width: 100%;
  height: 100%;
}
.gallery-badge {
  position: absolute;
  right: 24rpx;
  bottom: 24rpx;
  padding: 10rpx 16rpx;
  border-radius: 999rpx;
  background: rgba(12, 18, 28, 0.45);
  color: #ffffff;
  font-size: 22rpx;
}
.hero-card {
  margin-top: 20rpx;
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
.section-head,
.entity-card,
.action-bar {
  display: flex;
  justify-content: space-between;
}
.hero-top {
  align-items: flex-start;
  gap: 20rpx;
}
.price-row {
  display: flex;
  align-items: baseline;
  gap: 8rpx;
}
.price-symbol {
  font-size: 32rpx;
}
.price-value {
  font-size: 54rpx;
  font-weight: 700;
  line-height: 1;
}
.price-meta {
  margin-top: 12rpx;
  font-size: 24rpx;
  color: rgba(255, 255, 255, 0.82);
}
.hero-side {
  padding: 16rpx 20rpx;
  border-radius: 24rpx;
  background: rgba(255, 255, 255, 0.14);
  text-align: right;
}
.hero-side-label,
.hero-side-tip {
  display: block;
  font-size: 22rpx;
  color: rgba(255, 255, 255, 0.8);
}
.hero-side-value {
  display: block;
  margin: 10rpx 0 6rpx;
  font-size: 32rpx;
  font-weight: 700;
}
.label-row {
  display: flex;
  flex-wrap: wrap;
  gap: 14rpx;
  margin-top: 26rpx;
}
.label-chip {
  padding: 10rpx 18rpx;
  border-radius: 999rpx;
  background: rgba(255, 255, 255, 0.16);
  font-size: 22rpx;
}
.hero-title {
  margin-top: 24rpx;
  font-size: 36rpx;
  font-weight: 700;
  line-height: 1.45;
}
.hero-subtitle {
  margin-top: 12rpx;
  font-size: 24rpx;
  color: rgba(255, 255, 255, 0.82);
}
.env-card {
  border: 1rpx solid rgba(228, 233, 240, 0.85);
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
  text-align: left;
}
.env-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 12rpx;
  margin-top: 12rpx;
}
.env-tag {
  display: inline-flex;
  align-items: center;
  min-height: 40rpx;
  padding: 0 16rpx;
  border-radius: 999rpx;
  background: rgba(15, 23, 42, 0.06);
  color: #526274;
  font-size: 20rpx;
}
.env-note {
  margin-top: 12rpx;
  color: #6b7b8c;
  font-size: 22rpx;
  line-height: 1.7;
}
.env-note--strong {
  padding: 16rpx 18rpx;
  border-radius: 18rpx;
  background: rgba(21, 75, 114, 0.06);
  color: #35506b;
}
.env-risk-list {
  margin-top: 12rpx;
  padding: 16rpx 18rpx;
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
  margin-top: 12rpx;
  color: #94a3b8;
  font-size: 22rpx;
  line-height: 1.7;
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
  border: 1rpx solid rgba(228, 233, 240, 0.85);
}
.review-desc {
  margin-top: 12rpx;
  color: #5b6b7b;
  font-size: 22rpx;
  line-height: 1.7;
}
.review-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 12rpx;
  margin-top: 14rpx;
}
.review-tag {
  display: inline-flex;
  align-items: center;
  min-height: 40rpx;
  padding: 0 16rpx;
  border-radius: 999rpx;
  background: rgba(21, 75, 114, 0.08);
  color: #35506b;
  font-size: 20rpx;
}
.review-risk {
  margin-top: 14rpx;
  padding: 16rpx 18rpx;
  border-radius: 20rpx;
  background: linear-gradient(
    180deg,
    rgba(255, 247, 237, 0.96) 0%,
    rgba(255, 252, 247, 0.98) 100%
  );
  border: 1rpx solid rgba(234, 120, 72, 0.18);
  color: #8c5145;
  font-size: 22rpx;
  line-height: 1.7;
}
.section-card {
  margin-top: 20rpx;
  padding: 28rpx;
}
.section-head {
  align-items: center;
  gap: 20rpx;
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
.info-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 18rpx;
  margin-top: 22rpx;
}
.info-item {
  padding: 22rpx;
  border-radius: 22rpx;
  background: linear-gradient(180deg, #f7fafc 0%, #eef3f7 100%);
}
.info-label {
  display: block;
  font-size: 22rpx;
  color: #758395;
}
.info-value {
  display: block;
  margin-top: 12rpx;
  font-size: 28rpx;
  font-weight: 600;
  color: #172333;
  line-height: 1.5;
}
.entity-card {
  align-items: center;
  gap: 20rpx;
  margin-top: 22rpx;
  padding: 24rpx;
  border-radius: 24rpx;
  background: linear-gradient(180deg, #f9fbfc 0%, #f0f4f7 100%);
}
.entity-avatar,
.member-avatar {
  width: 88rpx;
  height: 88rpx;
  border-radius: 50%;
  flex-shrink: 0;
}
.entity-avatar {
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(21, 75, 114, 0.08);
  color: #1c5c88;
  font-size: 42rpx;
}
.entity-body {
  flex: 1;
  min-width: 0;
}
.entity-title {
  font-size: 28rpx;
  font-weight: 600;
  color: #172333;
}
.entity-desc {
  margin-top: 10rpx;
  font-size: 22rpx;
  line-height: 1.6;
  color: #667487;
}
.detail-card {
  padding-bottom: 34rpx;
}
.detail-richtext {
  display: block;
  margin-top: 22rpx;
  font-size: 26rpx;
  line-height: 1.8;
  color: #28374b;
}
.action-bar {
  position: fixed;
  left: 24rpx;
  right: 24rpx;
  bottom: calc(24rpx + env(safe-area-inset-bottom));
  align-items: center;
  gap: 18rpx;
  padding: 20rpx;
  z-index: 30;
}
.action-shortcuts {
  display: flex;
  gap: 14rpx;
}
.shortcut-btn {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8rpx;
  width: 118rpx;
  height: 102rpx;
  padding: 0;
  border: none;
  border-radius: 24rpx;
  background: linear-gradient(180deg, #f7fafc 0%, #eef3f7 100%);
  color: #405166;
  font-size: 22rpx;
  line-height: 1.2;
}
.shortcut-btn::after {
  border: none;
}
.shortcut-icon-box {
  position: relative;
}
.shortcut-badge {
  position: absolute;
  top: -10rpx;
  right: -18rpx;
  min-width: 30rpx;
  padding: 2rpx 8rpx;
  border-radius: 999rpx;
  background: #cf5a48;
  color: #ffffff;
  font-size: 18rpx;
  text-align: center;
}
.action-group {
  flex: 1;
  display: flex;
  gap: 14rpx;
}
.action-btn {
  flex: 1;
  height: 92rpx;
  padding: 0 20rpx;
  border: none;
  border-radius: 24rpx;
  font-size: 28rpx;
  font-weight: 600;
  line-height: 92rpx;
}
.action-btn::after {
  border: none;
}
.action-btn.primary {
  background: linear-gradient(90deg, #1c5c88 0%, #ec8a57 100%);
  color: #ffffff;
}
.action-btn.secondary {
  background: linear-gradient(90deg, #fff0e7 0%, #ffe4d5 100%);
  color: #b14b25;
}
.action-btn.danger {
  background: linear-gradient(90deg, #f36f63 0%, #d84c45 100%);
  color: #ffffff;
}
.loading-box,
.empty-state {
  padding: 80rpx 0 20rpx;
  text-align: center;
  font-size: 26rpx;
  color: #7f8a99;
}
.empty-action {
  margin-top: 24rpx;
}
.page-bottom-space {
  height: 220rpx;
}
</style>

