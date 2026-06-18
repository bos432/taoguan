<template>
  <view class="release-page">
    <bar-title bgColor="bg-white">
      <block slot="content">商品发布</block>
    </bar-title>

    <view class="hero-card">
      <view class="hero-badge">发布中心</view>
      <view class="hero-title">{{ accessCardTitle }}</view>
      <view class="hero-desc">{{ accessCardDesc }}</view>
      <view class="hero-actions">
        <button
          v-for="item in accessActions"
          :key="item.code"
          class="hero-action-btn"
          @tap="handleAccessAction(item)"
        >
          {{ item.label }}
        </button>
      </view>
    </view>

    <view class="env-card">
      <view class="env-head">
        <text class="env-title">当前环境</text>
        <text
          class="env-badge"
          :class="currentEnvInfo.is_prod ? 'is-prod' : 'is-test'"
          >{{ currentEnvInfo.label }}</text
        >
      </view>
      <view class="env-desc">{{ releaseEnvDescription }}</view>
      <view class="env-tags">
        <text class="env-tag">{{ releaseModeText }}</text>
        <text class="env-tag">{{ releaseStateText }}</text>
        <text class="env-tag">{{ envIsolationText }}</text>
        <text class="env-tag">{{ envIsolationStatusText }}</text>
        <text class="env-tag">{{ envReleaseStageText }}</text>
      </view>
      <view class="env-note">{{ releaseActionHint }}</view>
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
      <view class="env-url">{{ currentEnvInfo.api_root_url }}</view>
    </view>

    <view class="section-card rollout-card">
      <view class="section-title">上线承接提示</view>
      <view class="rollout-card__desc">{{ releaseRolloutHint }}</view>
      <view class="rollout-card__list">
        <view
          v-for="item in releaseRolloutChecklist"
          :key="item.label"
          class="rollout-card__item"
        >
          <text class="rollout-card__item-label">{{ item.label }}</text>
          <text class="rollout-card__item-value">{{ item.value }}</text>
        </view>
      </view>
      <view class="rollout-card__risk">{{ releaseRollbackHint }}</view>
    </view>

    <view v-if="releaseAllowed" class="section-card review-card">
      <view class="section-title">提交前复核</view>
      <view class="review-card__desc">{{ submitReviewHint }}</view>
      <view class="review-card__tags">
        <text class="review-card__tag">{{ submitReviewTags.mode }}</text>
        <text class="review-card__tag">{{ submitReviewTags.category }}</text>
        <text class="review-card__tag">{{ submitReviewTags.price }}</text>
        <text class="review-card__tag">{{ submitReviewTags.images }}</text>
        <text class="review-card__tag">{{ submitReviewTags.merchant }}</text>
      </view>
      <view class="review-card__risk">{{ submitRiskHint }}</view>
    </view>

    <view v-if="recentActionSummary" class="section-card recent-card">
      <view class="section-title">最近操作</view>
      <view class="recent-card__desc">{{ recentActionSummary }}</view>
    </view>

    <view v-if="recentReleasedGoodsCard" class="section-card result-card">
      <view class="result-card__head">
        <view>
          <view class="section-title">最近发布结果</view>
          <view class="result-card__time">{{
            recentReleasedGoodsCard.savedAt
          }}</view>
        </view>
        <view class="result-card__badge">{{
          recentReleasedGoodsCard.envLabel
        }}</view>
      </view>
      <view class="result-card__desc">{{
        recentReleasedGoodsCard.title || "已完成商品发布"
      }}</view>
      <view class="result-card__tags">
        <text class="result-card__tag">{{
          recentReleasedGoodsCard.goodsTypeTitle || "未记录分类"
        }}</text>
        <text class="result-card__tag"
          >售价 ￥{{ recentReleasedGoodsCard.price }}</text
        >
        <text class="result-card__tag"
          >{{ recentReleasedGoodsCard.imageCount }} 张图</text
        >
        <text class="result-card__tag">{{
          recentReleasedGoodsCard.envTag
        }}</text>
      </view>
      <view class="result-card__actions">
        <button class="footer-btn ghost" @tap="clearRecentReleasedGoods">
          清除提示
        </button>
        <button
          v-if="merchantApproved"
          class="footer-btn ghost"
          @tap="goMerchantAnalytics"
        >
          看分析
        </button>
        <button class="footer-btn primary" @tap="goRecentReleasedGoods">
          查看商品池
        </button>
      </view>
    </view>

    <view v-if="!releaseAllowed" class="release-lock-tip">
      商家审核通过后即可发布商品，发布成功后直接归入平台直营池。
    </view>

    <view v-if="releaseAllowed" class="section-card">
      <view class="section-head">
        <view class="section-title">基础信息</view>
        <view class="section-tip"
          >草稿自动保存在本机，发布后进入平台直营池</view
        >
      </view>

      <view class="publish-runtime-note">
        <view class="publish-runtime-note__title">发布说明</view>
        <view class="publish-runtime-note__desc"
          >商品发布后归属平台直营，库存固定为
          1，买家下单时统一展示平台收款码。</view
        >
      </view>

      <view class="form-list">
        <view class="form-item">
          <text class="form-label">商品分类</text>
          <xm-cascader
            v-model="info.goods_type_id"
            :options="params.goods_types || []"
            :props="props"
            :showAllLevels="true"
            :clearable="true"
            :border="false"
            placeholder="请选择商品分类"
            style="width: 100%"
            @input="saveDraft"
          ></xm-cascader>
        </view>

        <view class="form-item">
          <text class="form-label">商品名称</text>
          <input
            v-model="info.title"
            class="form-input"
            placeholder="请输入商品名称"
            @input="saveDraft"
          />
        </view>

        <view class="form-item two-col">
          <view class="split-item">
            <text class="form-label">商品规格</text>
            <input
              v-model="info.spec"
              class="form-input"
              placeholder="如：10kg/箱"
              @input="saveDraft"
            />
          </view>
          <view class="split-item">
            <text class="form-label">计量单位</text>
            <input
              v-model="info.unit"
              class="form-input"
              placeholder="如：kg / 箱 / 件"
              @input="saveDraft"
            />
          </view>
        </view>

        <view class="form-item two-col">
          <view class="split-item">
            <text class="form-label">商品原价</text>
            <u-number-box
              bgColor="transparent"
              v-model="info.original_price"
              :min="0.01"
              :max="999999"
              :decimalLength="2"
              :showMinus="true"
              :showPlus="true"
              :inputWidth="140"
              @change="saveDraft"
            ></u-number-box>
          </view>
          <view class="split-item">
            <text class="form-label">商品售价</text>
            <u-number-box
              bgColor="transparent"
              v-model="info.price"
              :min="0.01"
              :max="999999"
              :decimalLength="2"
              :showMinus="true"
              :showPlus="true"
              :inputWidth="140"
              @change="saveDraft"
            ></u-number-box>
          </view>
        </view>

        <view class="form-item">
          <text class="form-label">库存</text>
          <view class="form-static-value">固定 1 件（平台直营）</view>
        </view>

        <view class="form-item">
          <text class="form-label">商品描述</text>
          <textarea
            v-model="info.content"
            class="form-textarea"
            placeholder="请输入商品描述、卖点或交付说明"
            @input="saveDraft"
          ></textarea>
        </view>
      </view>
    </view>

    <view v-if="releaseAllowed" class="section-card">
      <view class="section-head">
        <view class="section-title">商品图片</view>
        <view class="section-tip">最多上传 4 张</view>
      </view>

      <view class="upload-grid">
        <view
          v-for="(item, index) in info.images"
          :key="index"
          class="upload-item"
          :data-url="item.file_url"
          @tap="viewImage"
        >
          <image
            :src="item.file_url"
            mode="aspectFill"
            class="upload-image"
          ></image>
          <view
            class="upload-remove"
            :data-id="item.file_id"
            @tap.stop="delImg"
          >
            <text class="cuIcon-close"></text>
          </view>
        </view>
        <view
          v-if="info.images.length < 4"
          class="upload-placeholder"
          @tap="chooseImage"
        >
          <text class="cuIcon-cameraadd"></text>
          <text>继续上传</text>
        </view>
      </view>
    </view>

    <view class="cu-tabbar-height"></view>

    <view v-if="releaseAllowed" class="footer-bar">
      <button class="footer-btn secondary" @tap="clearDraftAndReset">
        清空草稿
      </button>
      <button class="footer-btn ghost" @tap="saveDraft(true)">保存草稿</button>
      <button class="footer-btn primary" :loading="btn_loading" @tap="submit">
        立即发布
      </button>
    </view>
  </view>
</template>

<script>
import barTitle from "@/components/zaiui-common/basics/bar-title";
import util from "@/utils/util.js";
import api from "@/api";
import cache from "@/utils/cache.js";
import { maskMerchantTitle } from "@/utils/desensitize.js";
import { extractUrlList } from "@/utils/resource.js";
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

const RELEASE_DRAFT_KEY = "merchant_release_draft";
const RECENT_RELEASED_GOODS_KEY = "recent_released_goods";

function getPrimaryImageId(images = []) {
  if (!Array.isArray(images)) {
    return undefined;
  }
  const firstImage = images.find(
    (item) => item && Number(item.file_id || 0) > 0,
  );
  return firstImage ? Number(firstImage.file_id || 0) : undefined;
}

function findGoodsTypeTitle(list = [], targetId) {
  const normalizedId = Number(targetId || 0);
  if (!normalizedId || !Array.isArray(list)) {
    return "";
  }
  for (const item of list) {
    if (Number(item?.id || 0) === normalizedId) {
      return String(item?.title || "").trim();
    }
    const childTitle = findGoodsTypeTitle(item?.children || [], normalizedId);
    if (childTitle) {
      return childTitle;
    }
  }
  return "";
}

function createDefaultInfo() {
  return {
    title: "",
    goods_type_id: undefined,
    image_id: undefined,
    original_price: 1,
    price: 1,
    stock: 1,
    content: "",
    spec: "",
    unit: "",
    images: [],
  };
}

function normalizeReleaseInfo(info = {}) {
  const next = Object.assign(createDefaultInfo(), info || {});
  next.images = Array.isArray(next.images)
    ? next.images.filter((item) => item && Number(item.file_id || 0) > 0)
    : [];
  next.image_id =
    getPrimaryImageId(next.images) ||
    (Number(next.image_id || 0) > 0 ? Number(next.image_id) : undefined);
  delete next.setting_hall_id;
  delete next.setting_call_id;
  return next;
}

export default {
  name: "release",
  components: {
    barTitle,
  },
  data() {
    return {
      info: createDefaultInfo(),
      params: {},
      props: {
        value: "id",
        label: "title",
        children: "children",
      },
      btn_loading: false,
      merchantInfo: {},
      recentActionSummary: "",
      recentReleasedGoodsCard: null,
    };
  },
  computed: {
    goodsReleaseEnabled() {
      return Number(this.params.goods_release_enabled ?? 1) === 1;
    },
    hasMerchantRecord() {
      return Number(this.merchantInfo.id || 0) > 0;
    },
    merchantApproved() {
      return Number(this.merchantInfo.auth_state || 0) === 1;
    },
    merchantExpired() {
      return Number(this.merchantInfo.is_expired || 0) === 1;
    },
    currentEnvInfo() {
      return getCurrentEnvInfo();
    },
    releaseEnvDescription() {
      return this.currentEnvInfo.is_prod
        ? "当前为正式环境，发布成功会写入真实商品数据，请仅在确认运营发布时提交。"
        : "当前为非正式环境，可用于商品发布联调、图片上传和提交流程验收。";
    },
    releaseModeText() {
      return this.releaseAllowed
        ? "当前模式：可发布商品"
        : "当前模式：发布受限";
    },
    releaseStateText() {
      return this.info.images.length > 0
        ? `已上传图片：${this.info.images.length} 张`
        : "已上传图片：0 张";
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
    releaseAllowed() {
      return (
        this.hasMerchantRecord &&
        this.merchantApproved &&
        !this.merchantExpired &&
        this.goodsReleaseEnabled &&
        Number(this.params.goods_release_allowed || 0) === 1
      );
    },
    releaseActionHint() {
      return this.currentEnvInfo.is_prod
        ? `正式环境下发布会直接进入真实商品池，请先确认分类、价格、图片和商家状态。${getEnvIsolationHint(this.currentEnvInfo)}`
        : `建议先在当前环境验证草稿保存、图片上传、发布成功回跳和商家权限限制。${getEnvIsolationHint(this.currentEnvInfo)}`;
    },
    releaseRolloutHint() {
      if (this.currentEnvInfo.is_prod) {
        return "当前发布页已经连接正式环境，提交后会进入真实商品池，应按正式运营发布流程执行。";
      }
      if (this.envIsolationHealth.has_example_host) {
        return "当前环境仍是占位域名，只适合核对表单结构和交互，不适合真实商品发布联调。";
      }
      return "当前发布页适合做测试或灰度验收，建议先核对草稿、图片上传、提交流程和发布后回看入口。";
    },
    releaseRolloutChecklist() {
      return [
        { label: "当前环境", value: this.currentEnvInfo.label || "未配置" },
        {
          label: "发布权限",
          value: this.releaseAllowed ? "允许提交" : "当前受限",
        },
        { label: "图片状态", value: `${this.info.images.length || 0} / 4 张` },
        { label: "发布阶段", value: this.envReleaseStageText },
      ];
    },
    releaseRollbackHint() {
      if (this.currentEnvInfo.is_prod) {
        return "正式发布如发现异常，建议立即停止继续上新，并切回旧发布入口或后台下架处理。";
      }
      return "灰度期间建议保留旧发布路径，若新发布页出现异常，可先回切旧入口继续上新。";
    },
    selectedGoodsTypeTitle() {
      return (
        findGoodsTypeTitle(
          this.params.goods_types || [],
          this.info.goods_type_id,
        ) || "未选择分类"
      );
    },
    submitReviewTags() {
      const originalPrice = Number(this.info.original_price || 0);
      const price = Number(this.info.price || 0);
      return {
        mode: this.currentEnvInfo.is_prod
          ? "当前模式：正式发布"
          : "当前模式：测试发布",
        category: `分类：${this.selectedGoodsTypeTitle}`,
        price: `价格：￥${price || 0}${originalPrice > 0 ? " / 原价￥" + originalPrice : ""}`,
        images: `图片：${this.info.images.length}/4`,
        merchant: this.merchantApproved
          ? `商家：${maskMerchantTitle(this.merchantInfo.title, "已审核通过")}`
          : "商家：待完成审核",
      };
    },
    submitReviewHint() {
      return this.currentEnvInfo.is_prod
        ? "当前为正式环境，提交后会写入真实商品池。请确认分类、价格、首图和商家状态后再发布。"
        : "当前为非正式环境，建议先完整验证草稿保存、图片删除、提交发布和发布回跳流程。";
    },
    submitRiskHint() {
      if (!this.info.goods_type_id) {
        return "当前还没有选择商品分类，提交会被拦截。";
      }
      if (!String(this.info.title || "").trim()) {
        return "当前还没有填写商品名称，提交会被拦截。";
      }
      if (!String(this.info.spec || "").trim()) {
        return "当前还没有填写商品规格，提交会被拦截。";
      }
      if (!String(this.info.unit || "").trim()) {
        return "当前还没有填写计量单位，提交会被拦截。";
      }
      if (this.info.images.length <= 0) {
        return "当前还没有上传商品图片，提交会被拦截。";
      }
      if (Number(this.info.images.length || 0) >= 4) {
        return "已达到图片上限，建议确认首图是否准确，避免误把测试图发布到真实商品池。";
      }
      if (
        Number(this.info.price || 0) > Number(this.info.original_price || 0) &&
        Number(this.info.original_price || 0) > 0
      ) {
        return "当前售价高于原价，建议发布前再次核对价格配置。";
      }
      if (Number(this.merchantInfo.should_remind || 0) === 1) {
        return `商家服务剩余 ${this.merchantInfo.days_left || 0} 天，建议确认续费计划，避免发布后经营期中断。`;
      }
      return this.currentEnvInfo.is_prod
        ? "当前复核项已通过，正式发布后将直接进入真实商品池。"
        : "当前复核项已通过，可继续在测试环境验证商品发布与回显。";
    },
    accessCardTitle() {
      if (!this.goodsReleaseEnabled) {
        return "商品发布功能已关闭";
      }
      if (!this.hasMerchantRecord) {
        return "先完成商家入驻，再发布商品";
      }
      if (!this.merchantApproved) {
        return Number(this.merchantInfo.auth_state || 0) === 2
          ? "商家审核未通过"
          : "商家审核中";
      }
      if (this.merchantExpired) {
        return "商家服务已到期";
      }
      return maskMerchantTitle(this.merchantInfo.title, "当前商家可发布商品");
    },
    accessCardDesc() {
      if (!this.goodsReleaseEnabled) {
        return "后台当前已关闭商品发布入口，重新开启后商家账号才可继续发布商品。";
      }
      if (!this.hasMerchantRecord) {
        return "完成商家资料提交和审核后，才能继续使用商品发布能力。";
      }
      if (!this.merchantApproved) {
        if (Number(this.merchantInfo.auth_state || 0) === 2) {
          return (
            this.merchantInfo.auth_msg || "请根据驳回原因完善资料后重新提交。"
          );
        }
        return "审核通过后即可继续发布商品。";
      }
      if (this.merchantExpired) {
        return "续期完成后可恢复商品发布、商家分析和订单核销能力。";
      }
      if (Number(this.merchantInfo.should_remind || 0) === 1) {
        return (
          "商家服务将在 " +
          (this.merchantInfo.days_left || 0) +
          " 天后到期，建议尽快续期。"
        );
      }
      return "表单内容支持自动草稿保存，完善后可直接提交发布。";
    },
    accessActions() {
      const actions = [];
      if (!this.hasMerchantRecord || !this.merchantApproved) {
        actions.push({
          code: "apply",
          label: this.hasMerchantRecord ? "完善资料" : "去入驻",
          url: "/pages/merchant/apply",
        });
      }
      if (this.hasMerchantRecord) {
        actions.push({
          code: "merchant_info",
          label: "商家资料",
          url: "/pages/merchant/apply",
        });
      }
      if (this.merchantApproved) {
        actions.push({
          code: "analytics",
          label: "经营分析",
          url: "/pages/merchant/analytics",
        });
      }
      if (this.merchantApproved || this.merchantExpired) {
        actions.push({
          code: "renew",
          label: "续费中心",
          url: "/pages/merchant/renew",
        });
      }
      return actions;
    },
  },
  created() {
    this.restoreDraft();
  },
  onShow() {
    this.loadRecentReleasedGoods();
    this.getParams();
    this.getMerchantInfo();
  },
  onHide() {
    if (this.btn_loading) {
      return;
    }
    this.saveDraft();
  },
  onUnload() {
    if (this.btn_loading) {
      return;
    }
    this.saveDraft();
  },
  methods: {
    updateRecentActionSummary(summary) {
      this.recentActionSummary = summary;
    },
    loadRecentReleasedGoods() {
      this.recentReleasedGoodsCard =
        cache.get(RECENT_RELEASED_GOODS_KEY, null) || null;
    },
    clearRecentReleasedGoods() {
      cache.remove(RECENT_RELEASED_GOODS_KEY);
      this.recentReleasedGoodsCard = null;
      this.updateRecentActionSummary("已清除最近一次商品发布结果提示。");
    },
    goRecentReleasedGoods() {
      this.updateRecentActionSummary("正在跳转商品池，核对最近发布商品。");
      uni.switchTab({
        url: "/pages/app/sell",
      });
    },
    goMerchantAnalytics() {
      this.updateRecentActionSummary(
        "正在跳转经营分析，核对发布后的经营数据承接。",
      );
      uni.navigateTo({
        url: "/pages/merchant/analytics",
      });
    },
    syncPrimaryImage() {
      this.info.image_id = getPrimaryImageId(this.info.images);
    },
    handleAccessAction(item) {
      if (!item || !item.url) {
        return;
      }
      this.updateRecentActionSummary(`正在进入${item.label}。`);
      uni.navigateTo({
        url: item.url,
      });
    },
    getMerchantInfo() {
      api
        .merchantInfo({})
        .then((res) => {
          this.merchantInfo = res.data || {};
          this.updateRecentActionSummary("商家发布权限状态已刷新。");
        })
        .catch(() => {
          this.merchantInfo = {};
          this.updateRecentActionSummary("商家发布权限状态加载失败。");
        });
    },
    restoreDraft() {
      const draft = cache.get(RELEASE_DRAFT_KEY, null);
      if (!draft || typeof draft !== "object") {
        return;
      }
      this.info = normalizeReleaseInfo(draft);
      this.syncPrimaryImage();
      this.updateRecentActionSummary("已恢复本机草稿，可继续编辑后发布。");
    },
    saveDraft(showToast = false) {
      this.syncPrimaryImage();
      const draft = Object.assign({}, normalizeReleaseInfo(this.info), {
        images: Array.isArray(this.info.images) ? this.info.images : [],
      });
      cache.set(RELEASE_DRAFT_KEY, draft, 7 * 24 * 60 * 60);
      this.updateRecentActionSummary(
        `草稿已更新：${String(this.info.title || "").trim() || "未填写商品名称"}。`,
      );
      if (showToast) {
        uni.showToast({
          icon: "none",
          title: "草稿已保存",
        });
      }
    },
    clearDraftAndReset() {
      cache.remove(RELEASE_DRAFT_KEY);
      this.info = createDefaultInfo();
      this.updateRecentActionSummary("已清空商品草稿，表单已重置。");
      uni.showToast({
        icon: "none",
        title: "草稿已清空",
      });
    },
    ensureMerchantReady() {
      if (!this.goodsReleaseEnabled) {
        this.updateRecentActionSummary("发布入口已关闭，当前不能提交商品。");
        uni.showToast({
          icon: "none",
          title: "商品发布功能暂未开放",
        });
        return false;
      }
      if (!this.hasMerchantRecord) {
        this.updateRecentActionSummary("未完成商家入驻，发布已被拦截。");
        uni.showModal({
          title: "提示",
          content: "请先完成商家入驻后再发布商品。",
          confirmText: "去入驻",
          success: (res) => {
            if (res.confirm) {
              uni.navigateTo({
                url: "/pages/merchant/apply",
              });
            }
          },
        });
        return false;
      }
      if (!this.merchantApproved) {
        this.updateRecentActionSummary(
          "商家审核未通过或仍在审核中，发布已被拦截。",
        );
        uni.showToast({
          icon: "none",
          title:
            Number(this.merchantInfo.auth_state || 0) === 2
              ? "商家审核未通过"
              : "商家审核中",
        });
        return false;
      }
      if (this.merchantExpired) {
        this.updateRecentActionSummary(
          "商家服务已到期，需先续费后再发布商品。",
        );
        uni.showModal({
          title: "提示",
          content: "商家服务已到期，请先续期后再发布商品。",
          confirmText: "去续费",
          success: (res) => {
            if (res.confirm) {
              uni.navigateTo({
                url: "/pages/merchant/renew",
              });
            }
          },
        });
        return false;
      }
      return true;
    },
    submit() {
      if (this.btn_loading) {
        return false;
      }
      if (!this.ensureMerchantReady()) {
        return false;
      }
      if (!this.info.goods_type_id) {
        this.updateRecentActionSummary("提交被拦截：未选择商品分类。");
        uni.showToast({ icon: "none", title: "请选择商品分类" });
        return false;
      }
      if (!this.info.title) {
        this.updateRecentActionSummary("提交被拦截：未填写商品名称。");
        uni.showToast({ icon: "none", title: "请输入商品名称" });
        return false;
      }
      if (!this.info.spec) {
        this.updateRecentActionSummary("提交被拦截：未填写商品规格。");
        uni.showToast({ icon: "none", title: "请输入商品规格" });
        return false;
      }
      if (!this.info.unit) {
        this.updateRecentActionSummary("提交被拦截：未填写计量单位。");
        uni.showToast({ icon: "none", title: "请输入计量单位" });
        return false;
      }
      if (this.info.images.length <= 0) {
        this.updateRecentActionSummary("提交被拦截：未上传商品图片。");
        uni.showToast({ icon: "none", title: "请上传商品图片" });
        return false;
      }

      this.updateRecentActionSummary(
        `准备提交商品：${String(this.info.title || "").trim() || "未命名商品"}。`,
      );

      const confirmContent = buildEnvConfirmText(this.currentEnvInfo, {
        prod: "当前为正式环境，发布后会写入真实商品数据并进入平台直营池，确认继续发布吗？",
        test: "确认继续提交测试商品吗？",
      });

      uni.showModal({
        title: "发布确认",
        content: confirmContent,
        success: (modalRes) => {
          if (!modalRes.confirm) {
            return;
          }
          this.btn_loading = true;
          const submitPayload = Object.assign(
            {},
            normalizeReleaseInfo(this.info),
            {
              image_id: getPrimaryImageId(this.info.images),
              stock: 1,
              source: 0,
              status: 1,
            },
          );
          api
            .saveRelease(submitPayload)
            .then((res) => {
              const savedGoods = res.data || {};
              cache.set(
                RECENT_RELEASED_GOODS_KEY,
                {
                  id: Number(savedGoods.id || 0),
                  title: String(
                    savedGoods.title || submitPayload.title || "",
                  ).trim(),
                  goodsTypeTitle: this.selectedGoodsTypeTitle,
                  price: Number(submitPayload.price || 0).toFixed(2),
                  imageCount: Array.isArray(this.info.images)
                    ? this.info.images.length
                    : 0,
                  envLabel: this.currentEnvInfo.label,
                  envTag: this.envIsolationText,
                  savedAt: new Date().toLocaleString("zh-CN", {
                    hour12: false,
                  }),
                },
                10 * 60,
              );
              this.loadRecentReleasedGoods();
              cache.remove(RELEASE_DRAFT_KEY);
              this.info = createDefaultInfo();
              this.updateRecentActionSummary(
                `商品发布成功：${String(savedGoods.title || submitPayload.title || "").trim() || "未命名商品"}。`,
              );
              uni.showToast({
                icon: "none",
                title: this.currentEnvInfo.is_prod
                  ? "发布成功"
                  : "测试发布成功",
              });
              setTimeout(() => {
                uni.switchTab({
                  url: "/pages/app/sell",
                });
              }, 350);
            })
            .catch(() => {
              this.updateRecentActionSummary(
                "商品发布失败，请确认当前环境、图片上传状态和接口返回。",
              );
            })
            .finally(() => {
              this.btn_loading = false;
            });
        },
      });
    },
    getParams() {
      api
        .getReleaseParams({})
        .then((res) => {
          this.params = res.data || {};
          this.updateRecentActionSummary("商品发布参数已刷新。");
        })
        .catch(() => {
          this.updateRecentActionSummary("商品发布参数加载失败。");
        });
    },
    chooseImage() {
      if (!this.ensureMerchantReady()) {
        return;
      }
      if (this.info.images.length >= 4) {
        return;
      }
      util.uploadImage().then((res) => {
        this.info.images.push({
          file_id: res.file_id,
          file_url: res.file_url,
        });
        this.syncPrimaryImage();
        this.updateRecentActionSummary(
          `已上传商品图片，当前共 ${this.info.images.length} 张。`,
        );
        this.saveDraft();
      });
    },
    viewImage(e) {
      const urls = extractUrlList(this.info.images);
      this.updateRecentActionSummary(
        `正在预览商品图片，当前共 ${urls.length} 张。`,
      );
      uni.previewImage({
        urls,
        current: e.currentTarget.dataset.url,
      });
    },
    delImg(e) {
      uni.showModal({
        title: "温馨提示",
        content: "确定要删除这张图片吗？",
        success: (res) => {
          if (res.confirm) {
            this.info.images = this.info.images.filter(
              (item) => item.file_id != e.currentTarget.dataset.id,
            );
            this.syncPrimaryImage();
            this.updateRecentActionSummary(
              `已删除商品图片，当前剩余 ${this.info.images.length} 张。`,
            );
            this.saveDraft();
          }
        },
      });
    },
  },
};
</script>

<style scoped lang="scss">
.release-page {
  min-height: 100vh;
  padding: 0 24rpx 36rpx;
  background:
    radial-gradient(
      circle at top right,
      rgba(243, 143, 90, 0.22),
      transparent 32%
    ),
    radial-gradient(
      circle at top left,
      rgba(255, 107, 136, 0.08),
      transparent 28%
    ),
    linear-gradient(180deg, #f9f3eb 0%, #f3f6fa 42%, #edf2f6 100%);
}

.hero-card,
.env-card,
.section-card {
  background: rgba(255, 255, 255, 0.96);
  border-radius: 30rpx;
  box-shadow: 0 18rpx 42rpx rgba(21, 33, 52, 0.08);
  border: 1rpx solid rgba(228, 233, 240, 0.85);
}

.hero-card {
  position: relative;
  margin-top: 24rpx;
  padding: 32rpx;
  color: #ffffff;
  background:
    radial-gradient(
      circle at top right,
      rgba(255, 255, 255, 0.16),
      transparent 28%
    ),
    linear-gradient(135deg, #154b72 0%, #2b6a8d 48%, #ec8a57 100%);
  overflow: hidden;
}

.hero-card::after {
  content: "";
  position: absolute;
  right: -36rpx;
  top: -30rpx;
  width: 220rpx;
  height: 220rpx;
  border-radius: 999rpx;
  background: radial-gradient(
    circle,
    rgba(255, 255, 255, 0.24) 0%,
    rgba(255, 255, 255, 0) 72%
  );
}

.env-card {
  margin-top: 20rpx;
  padding: 24rpx 28rpx;
}

.review-card {
  margin-top: 20rpx;
}

.recent-card,
.result-card {
  margin-top: 20rpx;
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
  margin-top: 10rpx;
  color: #94a3b8;
  font-size: 20rpx;
  line-height: 1.6;
  word-break: break-all;
}

.env-profile-board,
.rollout-card {
  margin-top: 20rpx;
  padding: 24rpx;
  border-radius: 26rpx;
  background: rgba(255, 255, 255, 0.92);
  box-shadow: 0 12rpx 26rpx rgba(17, 34, 51, 0.06);
}

.env-profile-board {
  margin-top: 14rpx;
  padding: 16rpx 18rpx;
  border-radius: 20rpx;
  background: rgba(21, 75, 114, 0.04);
  box-shadow: none;
}

.env-profile-board__title,
.rollout-card__title {
  font-size: 24rpx;
  font-weight: 700;
  color: #10283d;
}

.env-profile-board__list,
.rollout-card__list {
  display: flex;
  flex-direction: column;
  gap: 12rpx;
  margin-top: 14rpx;
}

.env-profile-board__item,
.rollout-card__item {
  padding: 16rpx 18rpx;
  border-radius: 18rpx;
  background: rgba(255, 255, 255, 0.92);
}

.env-profile-board__item.is-current {
  border: 1rpx solid rgba(0, 129, 255, 0.22);
}

.env-profile-board__item-head,
.rollout-card__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12rpx;
}

.env-profile-board__item-name,
.rollout-card__item-label {
  font-size: 22rpx;
  color: #23405d;
  font-weight: 600;
}

.env-profile-board__item-status,
.rollout-card__badge {
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

.env-profile-board__item-status.is-ready,
.rollout-card__badge.is-prod {
  background: linear-gradient(45deg, #ef4444, #f97316);
}

.env-profile-board__item-desc,
.rollout-card__desc,
.rollout-card__risk,
.rollout-card__item-value {
  margin-top: 8rpx;
  font-size: 21rpx;
  line-height: 1.7;
  color: #6b7b8c;
}

.rollout-card__item-value {
  display: block;
  color: #1c425f;
  font-weight: 600;
}

.rollout-card__risk {
  margin-top: 16rpx;
  padding: 18rpx 20rpx;
  border-radius: 20rpx;
  background: rgba(255, 247, 237, 0.96);
  color: #9a3412;
}

.review-card__desc {
  margin-top: 14rpx;
  color: #5b6b7b;
  font-size: 24rpx;
  line-height: 1.7;
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
  min-height: 42rpx;
  padding: 0 18rpx;
  border-radius: 999rpx;
  background: rgba(21, 75, 114, 0.08);
  color: #35506b;
  font-size: 22rpx;
}

.review-card__risk {
  margin-top: 14rpx;
  padding: 18rpx 20rpx;
  border-radius: 20rpx;
  background: linear-gradient(
    180deg,
    rgba(255, 247, 237, 0.96) 0%,
    rgba(255, 252, 247, 0.98) 100%
  );
  border: 1rpx solid rgba(234, 120, 72, 0.18);
  color: #8c5145;
  font-size: 23rpx;
  line-height: 1.7;
}

.recent-card__desc {
  margin-top: 14rpx;
  color: #5b6b7b;
  font-size: 24rpx;
  line-height: 1.7;
}

.result-card__head,
.result-card__actions {
  display: flex;
  justify-content: space-between;
  gap: 16rpx;
  align-items: flex-start;
}

.result-card__time {
  margin-top: 8rpx;
  color: #7b8797;
  font-size: 22rpx;
}

.result-card__badge {
  display: inline-flex;
  min-height: 40rpx;
  align-items: center;
  padding: 0 16rpx;
  border-radius: 999rpx;
  background: rgba(21, 75, 114, 0.08);
  color: #35506b;
  font-size: 20rpx;
}

.result-card__desc {
  margin-top: 14rpx;
  color: #162233;
  font-size: 25rpx;
  line-height: 1.7;
}

.result-card__tags {
  display: flex;
  flex-wrap: wrap;
  gap: 12rpx;
  margin-top: 16rpx;
}

.result-card__tag {
  display: inline-flex;
  align-items: center;
  min-height: 40rpx;
  padding: 0 16rpx;
  border-radius: 999rpx;
  background: rgba(21, 75, 114, 0.08);
  color: #35506b;
  font-size: 20rpx;
}

.result-card__actions {
  margin-top: 18rpx;
}

.hero-badge {
  display: inline-flex;
  padding: 10rpx 18rpx;
  border-radius: 999rpx;
  background: rgba(255, 255, 255, 0.16);
  font-size: 22rpx;
  font-weight: 700;
}

.hero-title {
  margin-top: 24rpx;
  font-size: 38rpx;
  font-weight: 700;
  line-height: 1.4;
}

.hero-desc {
  margin-top: 14rpx;
  font-size: 25rpx;
  line-height: 1.7;
  color: rgba(255, 255, 255, 0.92);
}

.hero-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 16rpx;
  margin-top: 22rpx;
}

.hero-action-btn {
  min-width: 170rpx;
  height: 68rpx;
  padding: 0 24rpx;
  border: 1rpx solid rgba(255, 255, 255, 0.28);
  border-radius: 999rpx;
  background: rgba(255, 255, 255, 0.14);
  color: #ffffff;
  font-size: 24rpx;
  line-height: 68rpx;
  font-weight: 700;
}

.section-card {
  margin-top: 20rpx;
  padding: 28rpx;
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

.section-tip {
  font-size: 22rpx;
  color: #7b8797;
}

.publish-runtime-note {
  margin-top: 20rpx;
  padding: 24rpx 28rpx;
  border-radius: 24rpx;
  background: linear-gradient(
    180deg,
    rgba(255, 246, 239, 0.98) 0%,
    rgba(255, 250, 246, 0.96) 100%
  );
  border: 1px solid rgba(222, 109, 76, 0.14);
}

.publish-runtime-note__title {
  font-size: 28rpx;
  font-weight: 700;
  color: #7c2518;
}

.publish-runtime-note__desc {
  margin-top: 12rpx;
  font-size: 24rpx;
  line-height: 1.7;
  color: #8c5145;
}

.form-list {
  margin-top: 22rpx;
}

.form-item + .form-item {
  margin-top: 18rpx;
}

.form-item.two-col {
  display: flex;
  gap: 18rpx;
}

.split-item {
  flex: 1;
}

.form-label {
  display: block;
  margin-bottom: 12rpx;
  font-size: 24rpx;
  color: #5d6c7e;
}

.form-input,
.form-textarea {
  width: 100%;
  box-sizing: border-box;
  padding: 0 24rpx;
  border-radius: 20rpx;
  background: linear-gradient(180deg, #f7fafc 0%, #eef3f7 100%);
  border: 1rpx solid rgba(228, 233, 240, 0.9);
  font-size: 28rpx;
  color: #172333;
}

.form-input {
  height: 84rpx;
  line-height: 84rpx;
}

.form-textarea {
  min-height: 180rpx;
  padding-top: 20rpx;
  padding-bottom: 20rpx;
}

.form-static-value {
  width: 100%;
  box-sizing: border-box;
  min-height: 84rpx;
  display: flex;
  align-items: center;
  padding: 0 24rpx;
  border-radius: 20rpx;
  background: linear-gradient(180deg, #f7fafc 0%, #eef3f7 100%);
  border: 1rpx solid rgba(228, 233, 240, 0.9);
  font-size: 28rpx;
  color: #6d4c41;
}

.upload-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 18rpx;
  margin-top: 22rpx;
}

.upload-item,
.upload-placeholder {
  position: relative;
  height: 260rpx;
  overflow: hidden;
  border-radius: 24rpx;
}

.upload-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12rpx;
  background: linear-gradient(180deg, #f7fafc 0%, #eef3f7 100%);
  color: #667487;
  font-size: 24rpx;
}

.upload-image {
  width: 100%;
  height: 100%;
}

.upload-remove {
  position: absolute;
  top: 16rpx;
  right: 16rpx;
  width: 48rpx;
  height: 48rpx;
  border-radius: 50%;
  background: rgba(12, 18, 28, 0.45);
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
}

.footer-bar {
  position: sticky;
  bottom: 0;
  z-index: 9;
  display: flex;
  gap: 16rpx;
  padding: 18rpx 0 calc(18rpx + env(safe-area-inset-bottom));
  background: linear-gradient(
    180deg,
    rgba(249, 243, 235, 0) 0%,
    rgba(249, 243, 235, 0.82) 28%,
    rgba(249, 243, 235, 1) 100%
  );
}

.footer-btn {
  flex: 1;
  height: 84rpx;
  border-radius: 24rpx;
  font-size: 26rpx;
  line-height: 84rpx;
  border: none;
  font-weight: 700;
}

.footer-btn::after {
  border: none;
}

.footer-btn.primary {
  background: linear-gradient(90deg, #1c5c88 0%, #ec8a57 100%);
  color: #ffffff;
}

.footer-btn.secondary {
  background: #edf1f5;
  color: #4e5d70;
}

.footer-btn.ghost {
  background: rgba(21, 75, 114, 0.08);
  color: #1c5c88;
}

.release-lock-tip {
  margin-top: 20rpx;
  padding: 24rpx;
  border-radius: 24rpx;
  background: rgba(255, 248, 244, 0.9);
  color: #8d1611;
  line-height: 1.7;
  box-shadow: 0 12rpx 28rpx rgba(80, 0, 0, 0.12);
}

@media screen and (max-width: 375px) {
  .release-page {
    padding-left: 20rpx;
    padding-right: 20rpx;
  }

  .hero-card,
  .section-card {
    border-radius: 26rpx;
  }

  .hero-card {
    padding: 28rpx;
  }

  .hero-title {
    font-size: 34rpx;
  }

  .hero-desc {
    font-size: 23rpx;
  }

  .hero-action-btn {
    min-width: 148rpx;
    font-size: 22rpx;
  }

  .form-item.two-col {
    flex-direction: column;
  }

  .footer-btn {
    font-size: 24rpx;
  }
}
</style>

