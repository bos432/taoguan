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
    <view class="apply-page">
      <view class="hero-card">
        <view class="hero-badge">{{ info.id ? "商家信息" : "商家入驻" }}</view>
        <view class="hero-title">{{
          info.id ? info.title || "商家信息" : "提交入驻资料，等待平台审核"
        }}</view>
        <view v-if="info.id" class="hero-title hero-title--mask">{{
          heroTitleText
        }}</view>
        <view class="hero-subtitle">{{ statusText }}</view>
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
          <text class="env-tag">{{ applyModeText }}</text>
          <text class="env-tag">{{ disclaimerStatusText }}</text>
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

      <view v-if="loadError" class="section-card">
        <view class="section-title">商家信息加载失败</view>
        <view class="section-tip load-error-text">{{ loadError }}</view>
        <view class="action-area">
          <button class="action-btn primary" @tap="retryLoad">重新加载</button>
        </view>
      </view>

      <view v-if="!loadError" class="section-card">
        <view class="section-head">
          <view class="section-title">基础资料</view>
          <view class="section-tip">请填写真实有效的信息</view>
        </view>

        <view class="form-list">
          <view class="form-item">
            <text class="form-label">商户名称</text>
            <input
              v-model="info.title"
              class="form-input"
              placeholder="请输入商户名称"
            />
          </view>
          <view class="form-item">
            <text class="form-label">用户姓名</text>
            <input
              v-model="info.name"
              class="form-input"
              placeholder="请输入用户姓名"
            />
          </view>
          <view class="form-item">
            <text class="form-label">联系电话</text>
            <input
              v-model="info.phone"
              class="form-input"
              placeholder="请输入联系电话"
            />
          </view>
        </view>
      </view>

      <view v-if="!loadError" class="section-card">
        <view class="section-head">
          <view class="section-title">收款信息</view>
          <view class="section-tip">{{
            info.id
              ? "收款码仅支持网页超级管理员修改"
              : "请上传清晰可识别的收款码"
          }}</view>
        </view>
        <view class="upload-grid single-upload">
          <view v-if="info.image_id" class="upload-item" @tap="viewImage1">
            <image
              :src="info.image_url"
              class="upload-image"
              mode="aspectFill"
            ></image>
            <view v-if="!info.id" class="upload-remove" @tap.stop="delImg1">
              <text class="cuIcon-close"></text>
            </view>
          </view>
          <view
            v-else
            class="upload-placeholder"
            :class="{ 'is-disabled': info.id }"
            @tap="chooseImage1"
          >
            <text class="cuIcon-cameraadd"></text>
            <text>{{
              info.id ? "仅网页超级管理员可修改" : "上传收款信息"
            }}</text>
          </view>
        </view>
      </view>

      <view v-if="!loadError" class="section-card">
        <view class="section-head">
          <view class="section-title">资质图片</view>
          <view class="section-tip">营业执照及相关证明，最多 4 张</view>
        </view>
        <view class="upload-grid">
          <view
            v-for="(item, index) in info.images"
            :key="index"
            class="upload-item"
            @tap="viewImage(item.file_url)"
          >
            <image
              :src="item.file_url"
              class="upload-image"
              mode="aspectFill"
            ></image>
            <view class="upload-remove" @tap.stop="delImg(item.file_id)">
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

      <view v-if="!loadError" class="section-card agreement-block">
        <view class="agreement-row" @tap="toggleDisclaimerAgree">
          <text
            class="cuIcon"
            :class="
              agreeDisclaimer
                ? 'cuIcon-radiobox text-orange'
                : 'cuIcon-round text-gray'
            "
          ></text>
          <text class="agreement-text">我已阅读并同意</text>
          <text class="agreement-link" @tap.stop="openAccord('disclaimer')"
            >《免责声明》</text
          >
        </view>
        <view class="agreement-note">{{ agreementReminderText }}</view>
      </view>

      <view v-if="!loadError" class="section-card review-card">
        <view class="section-title">提交前复核</view>
        <view class="review-card__desc">{{ submitReviewHint }}</view>
        <view class="review-card__tags">
          <text class="review-card__tag">{{ submitReviewTags.mode }}</text>
          <text class="review-card__tag">{{ submitReviewTags.receipt }}</text>
          <text class="review-card__tag">{{ submitReviewTags.license }}</text>
          <text class="review-card__tag">{{ submitReviewTags.agreement }}</text>
        </view>
        <view class="review-card__risk">{{ submitRiskHint }}</view>
      </view>

      <view v-if="!loadError && recentActionSummary" class="section-card">
        <view class="section-title">最近操作</view>
        <view class="review-card__desc">{{ recentActionSummary }}</view>
      </view>

      <view v-if="!loadError" class="section-card review-card">
        <view class="section-head">
          <view class="section-title">审核跟进</view>
          <view class="status-hint-badge" :class="auditFollowupBadgeClass">{{
            auditFollowupBadgeText
          }}</view>
        </view>
        <view class="review-card__desc">{{ auditFollowupHint }}</view>
        <view class="review-card__tags">
          <text
            class="review-card__tag"
            v-for="item in auditFollowupTags"
            :key="item"
            >{{ item }}</text
          >
        </view>
        <view class="review-card__risk">{{ auditFollowupRiskText }}</view>
      </view>

      <view
        v-if="
          !loadError &&
          (info.auth_state_title || info.auth_msg || info.auth_state == 1)
        "
        class="section-card"
      >
        <view class="section-head">
          <view class="section-title">审核信息</view>
          <view class="section-tip">审核通过后可进入商家后台</view>
        </view>
        <view class="info-block">
          <view v-if="info.auth_state_title" class="info-row">
            <text class="info-key">审核状态</text>
            <text class="info-value">{{ info.auth_state_title }}</text>
          </view>
          <view v-if="info.auth_state == 2 && info.auth_msg" class="info-row">
            <text class="info-key">失败原因</text>
            <text class="info-value danger-text">{{ info.auth_msg }}</text>
          </view>
          <view v-if="info.auth_state == 1" class="info-row">
            <text class="info-key">登录地址</text>
            <text class="info-value">{{ info.mer_host }}</text>
          </view>
          <view v-if="info.auth_state == 1" class="info-row">
            <text class="info-key">登录账号</text>
            <text class="info-value">{{ info.username }}</text>
          </view>
          <view v-if="info.auth_state == 1" class="info-row">
            <text class="info-key">初始密码</text>
            <text class="info-value">{{ info.pwd }}</text>
          </view>
        </view>
      </view>

      <view v-if="!loadError" class="action-area">
        <button
          v-if="!info.id"
          class="action-btn primary"
          :loading="btn_loading"
          @tap="submit"
        >
          提交
        </button>
        <button
          v-else-if="info.id && info.auth_state != 1"
          class="action-btn primary"
          :loading="btn_loading"
          @tap="submit"
        >
          修改并重新提交
        </button>
        <button
          v-else
          class="action-btn secondary"
          :loading="btn_loading"
          @tap="back"
        >
          返回
        </button>
      </view>
    </view>
  </view>
</template>

<script>
import util from "@/utils/util.js";
import api from "@/api";
import { bestEffortAcceptAccords } from "@/utils/accord-accept.js";
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

export default {
  name: "merchant-apply",
  data() {
    return {
      info: {
        id: undefined,
        title: undefined,
        name: undefined,
        phone: undefined,
        images: [],
        image_url: undefined,
        image_id: undefined,
      },
      btn_loading: false,
      agreeDisclaimer: false,
      loadError: "",
      recentActionSummary: "",
    };
  },
  computed: {
    currentEnvInfo() {
      return getCurrentEnvInfo();
    },
    envDescription() {
      return this.currentEnvInfo.is_prod
        ? "当前为正式环境，请确认资料提交前已完成测试环境验证。"
        : "当前为非正式环境，适合商家入驻联调和审核流程验证。";
    },
    applyModeText() {
      return this.info.id ? "当前模式：资料修改" : "当前模式：新商家入驻";
    },
    disclaimerStatusText() {
      return this.agreeDisclaimer ? "免责声明：已勾选" : "免责声明：未勾选";
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
    profileReadinessList() {
      return getProfileReadinessList();
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
    envActionHint() {
      return this.currentEnvInfo.is_prod
        ? `提交后会进入真实审核流程，请先确认图片、手机号和商户名称准确无误。${getEnvIsolationHint(this.currentEnvInfo)}`
        : `建议先在当前环境验证入驻、驳回、修改重提和审核回显，再切正式环境。${getEnvIsolationHint(this.currentEnvInfo)}`;
    },
    agreementReminderText() {
      return this.agreeDisclaimer
        ? "已确认免责声明，提交时会同步尝试补记协议记录。"
        : "免责声明默认不勾选，未勾选时无法提交入驻资料。";
    },
    submitReviewTags() {
      return {
        mode: this.info.id ? "当前模式：资料修改" : "当前模式：新商家入驻",
        receipt: this.info.image_id ? "收款码：已上传" : "收款码：未上传",
        license: `资质图片：${Array.isArray(this.info.images) ? this.info.images.length : 0} / 4`,
        agreement: this.agreeDisclaimer
          ? "免责声明：已勾选"
          : "免责声明：未勾选",
      };
    },
    submitReviewHint() {
      return this.currentEnvInfo.is_prod
        ? "当前为正式环境，提交后会进入真实审核流程，请在提交前再次核对商户资料和上传图片。"
        : "当前为非正式环境，建议先完整验证入驻、驳回、修改重提和审核回显。";
    },
    submitRiskHint() {
      if (!this.info.image_id) {
        return "当前还没有上传收款信息，提交会被拦截。";
      }
      if (!Array.isArray(this.info.images) || this.info.images.length <= 0) {
        return "当前还没有上传营业执照及行业相关资质证明图片，提交会被拦截。";
      }
      if (!this.agreeDisclaimer) {
        return "免责声明默认不勾选，未勾选时无法提交入驻资料。";
      }
      if (!this.info.phone) {
        return "请继续核对联系电话，确保审核人员能联系到商家。";
      }
      return "当前资料已基本齐备，提交后会按当前环境进入入驻审核流程。";
    },
    auditFollowupBadgeText() {
      if (!this.info.id) {
        return "待提交";
      }
      if (Number(this.info.auth_state || 0) === 1) {
        return "已通过";
      }
      if (Number(this.info.auth_state || 0) === 2) {
        return "待修正";
      }
      return "审核中";
    },
    auditFollowupBadgeClass() {
      if (!this.info.id) {
        return "is-warning";
      }
      if (Number(this.info.auth_state || 0) === 1) {
        return "is-safe";
      }
      if (Number(this.info.auth_state || 0) === 2) {
        return "is-danger";
      }
      return "is-warning";
    },
    auditFollowupHint() {
      if (!this.info.id) {
        return "当前还没有正式提交入驻资料，建议先补齐商户名称、联系电话、收款码和资质图片后再发起审核。";
      }
      if (Number(this.info.auth_state || 0) === 1) {
        return "当前商家资料已审核通过，可直接使用审核结果中的账号信息进入商家后台。";
      }
      if (Number(this.info.auth_state || 0) === 2) {
        return "当前商家资料已被驳回，建议根据失败原因修正资料后重新提交。";
      }
      return "当前商家资料已进入审核流程，建议持续关注审核状态和失败原因回显。";
    },
    auditFollowupTags() {
      return [
        this.info.id ? "资料状态：已建档" : "资料状态：未建档",
        this.info.auth_state_title
          ? `审核状态：${this.info.auth_state_title}`
          : "审核状态：待提交/待审核",
        this.info.image_id ? "收款码：已上传" : "收款码：未上传",
        `资质图片：${Array.isArray(this.info.images) ? this.info.images.length : 0} 张`,
      ];
    },
    auditFollowupRiskText() {
      if (Number(this.info.auth_state || 0) === 2 && this.info.auth_msg) {
        return `当前审核未通过，优先处理失败原因：${this.info.auth_msg}`;
      }
      if (!this.info.id) {
        return "当前还没有形成正式入驻记录，离开前请确认是否已经完成收款码、资质图片和免责声明勾选。";
      }
      return this.currentEnvInfo.is_prod
        ? "当前为正式环境，资料修改和重新提交会直接影响真实商家入驻审核，请以审核结果回显为准继续跟进。"
        : "当前为非正式环境，建议继续联调入驻提交、驳回重提、审核通过和商家后台账号回显。";
    },
    heroTitleText() {
      if (!this.info.id) {
        return "";
      }
      return maskMerchantTitle(this.info.title, "鍟嗗淇℃伅");
    },
    statusText() {
      if (!this.info.id) {
        return "填写资料后即可提交审核";
      }
      if (this.info.auth_state_title) {
        return "当前状态：" + this.info.auth_state_title;
      }
      return "资料已保存，可继续完善";
    },
  },
  created() {
    this.getInfo();
  },
  methods: {
    updateRecentActionSummary(summary) {
      this.recentActionSummary = summary;
    },
    back() {
      this.updateRecentActionSummary("准备返回上一页。");
      uni.navigateBack();
    },
    openAccord(accordId) {
      this.updateRecentActionSummary("正在查看免责声明。");
      uni.navigateTo({
        url: "/pages/system/accord?accord_id=" + accordId,
      });
    },
    toggleDisclaimerAgree() {
      this.agreeDisclaimer = !this.agreeDisclaimer;
      this.updateRecentActionSummary(
        this.agreeDisclaimer ? "已勾选免责声明。" : "已取消免责声明勾选。",
      );
    },
    getInfo() {
      api
        .merchantInfo({})
        .then((res) => {
          if (res.data) {
            this.info = Object.assign(
              {
                images: [],
              },
              res.data,
            );
            if (!this.info.images) {
              this.info.images = [];
            }
          }
          this.loadError = "";
          this.updateRecentActionSummary(
            `商家资料已刷新：${this.info.id ? "已有入驻记录" : "可新建入驻资料"}。`,
          );
        })
        .catch(() => {
          this.btn_loading = false;
          this.loadError = "商家资料暂时无法加载，请稍后重试";
          this.updateRecentActionSummary("商家资料加载失败。");
        });
    },
    retryLoad() {
      this.loadError = "";
      this.updateRecentActionSummary("正在重新加载商家资料。");
      this.getInfo();
    },
    submit() {
      const phoneRegEx = /^1[3-9]\d{9}$/;
      if (this.btn_loading) {
        return;
      }
      if (!this.info.title) {
        uni.showToast({ icon: "none", title: "请输入商户名称" });
        return;
      }
      if (!this.info.name) {
        uni.showToast({ icon: "none", title: "请输入用户姓名" });
        return;
      }
      if (!this.info.phone) {
        uni.showToast({ icon: "none", title: "请输入联系电话" });
        return;
      }
      if (!phoneRegEx.test(this.info.phone)) {
        uni.showToast({ icon: "none", title: "联系电话格式不正确" });
        return;
      }
      if (!this.info.image_id) {
        uni.showToast({ icon: "none", title: "请上传收款信息" });
        return;
      }
      if (this.info.images.length <= 0) {
        uni.showToast({
          icon: "none",
          title: "请上传营业执照及行业相关资质证明图片",
        });
        return;
      }
      if (!this.agreeDisclaimer) {
        uni.showToast({ icon: "none", title: "请先同意免责声明" });
        return;
      }
      this.updateRecentActionSummary(
        `准备提交商家资料：${this.info.id ? "修改重提" : "新商家入驻"}。`,
      );
      const confirmContent = buildEnvConfirmText(this.currentEnvInfo, {
        prod: "当前为正式环境，提交后会写入真实商家入驻资料并进入审核流程，确认继续吗？",
        test: "确认继续提交测试入驻资料吗？",
      });
      uni.showModal({
        title: "提交确认",
        content: confirmContent,
        success: (modalRes) => {
          if (!modalRes.confirm) {
            return;
          }
          this.btn_loading = true;
          bestEffortAcceptAccords(
            {
              scene: "merchant_apply",
              accord_uniques: ["disclaimer"],
            },
            {
              toast: true,
              message: "协议记录暂未同步，将继续提交入驻资料",
            },
          )
            .then(() => api.merchantApply(this.info))
            .then(() => {
              this.getInfo();
              this.updateRecentActionSummary(
                "商家资料提交成功，等待审核结果回显。",
              );
              uni.showToast({
                icon: "none",
                title: this.currentEnvInfo.is_prod
                  ? "提交成功"
                  : "测试提交成功",
              });
            })
            .catch((error) => {
              if (error && error.msg) {
                uni.showToast({
                  icon: "none",
                  title: error.msg,
                });
              }
              this.updateRecentActionSummary(
                "商家资料提交失败，请确认当前资料和接口返回。",
              );
            })
            .finally(() => {
              this.btn_loading = false;
            });
        },
      });
    },
    chooseImage() {
      if (this.info.images.length >= 4) {
        return;
      }
      util.uploadImage().then((res) => {
        this.info.images.push({
          file_id: res.file_id,
          file_url: res.file_url,
        });
        this.updateRecentActionSummary(
          `已上传资质图片，当前 ${this.info.images.length} 张。`,
        );
      });
    },
    viewImage(currentUrl) {
      const urls = extractUrlList(this.info.images);
      this.updateRecentActionSummary(
        `正在预览资质图片，当前 ${urls.length} 张。`,
      );
      uni.previewImage({
        urls,
        current: currentUrl,
      });
    },
    delImg(fileId) {
      uni.showModal({
        title: "温馨提示",
        content: "确定要删除这张图片吗？",
        cancelText: "取消",
        confirmText: "确定",
        success: (res) => {
          if (res.confirm) {
            this.info.images = this.info.images.filter(
              (item) => item.file_id != fileId,
            );
            this.updateRecentActionSummary(
              `已删除资质图片，当前剩余 ${this.info.images.length} 张。`,
            );
          }
        },
      });
    },
    chooseImage1() {
      if (this.info.id) {
        uni.showToast({
          icon: "none",
          title: "收款码仅支持网页超级管理员修改",
        });
        return;
      }
      util.uploadImage().then((res) => {
        this.info.image_id = res.file_id;
        this.info.image_url = res.file_url;
        this.updateRecentActionSummary("已上传收款信息图片。");
      });
    },
    viewImage1() {
      this.updateRecentActionSummary("正在预览收款信息图片。");
      uni.previewImage({
        urls: [this.info.image_url],
        current: this.info.image_url,
      });
    },
    delImg1() {
      if (this.info.id) {
        uni.showToast({
          icon: "none",
          title: "收款码仅支持网页超级管理员修改",
        });
        return;
      }
      uni.showModal({
        title: "温馨提示",
        content: "确定要删除这张图片吗？",
        cancelText: "取消",
        confirmText: "确定",
        success: (res) => {
          if (res.confirm) {
            this.info.image_id = null;
            this.info.image_url = null;
            this.updateRecentActionSummary("已删除收款信息图片。");
          }
        },
      });
    },
  },
};
</script>

<style scoped lang="scss">
.apply-page {
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
  line-height: 1.45;
}

.hero-title--mask {
  margin-top: -56rpx;
  position: relative;
  z-index: 1;
  background:
    radial-gradient(
      circle at top right,
      rgba(255, 255, 255, 0.16),
      transparent 28%
    ),
    linear-gradient(135deg, #154b72 0%, #2b6a8d 48%, #ec8a57 100%);
}

.hero-subtitle {
  margin-top: 12rpx;
  font-size: 24rpx;
  color: rgba(255, 255, 255, 0.82);
}

.env-card {
  margin-top: 20rpx;
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
  border-radius: 20rpx;
  background: rgba(15, 23, 42, 0.04);
  color: #35506b;
}

.env-risk-list {
  margin-top: 14rpx;
  padding: 16rpx 18rpx;
  border-radius: 20rpx;
  background: linear-gradient(
    180deg,
    rgba(255, 247, 237, 0.96) 0%,
    rgba(255, 252, 247, 0.98) 100%
  );
  border: 1rpx solid rgba(234, 120, 72, 0.18);
}

.env-risk-item {
  color: #8c5145;
  font-size: 22rpx;
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
  text-align: right;
}

.form-list {
  margin-top: 22rpx;
}

.form-item + .form-item {
  margin-top: 18rpx;
}

.form-label {
  display: block;
  margin-bottom: 12rpx;
  font-size: 24rpx;
  color: #5d6c7e;
}

.form-input {
  height: 84rpx;
  padding: 0 24rpx;
  border-radius: 20rpx;
  background: linear-gradient(180deg, #f7fafc 0%, #eef3f7 100%);
  font-size: 28rpx;
  color: #172333;
}

.upload-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 18rpx;
  margin-top: 22rpx;
}

.single-upload {
  grid-template-columns: repeat(1, minmax(0, 1fr));
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

.upload-placeholder.is-disabled {
  opacity: 0.68;
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

.agreement-block {
  .agreement-row {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 10rpx;
  }

  .agreement-text {
    color: #5f6f82;
    font-size: 24rpx;
  }

  .agreement-link {
    color: #cf6b45;
    font-size: 24rpx;
  }

  .agreement-note {
    margin-top: 12rpx;
    color: #7a8797;
    font-size: 22rpx;
    line-height: 1.7;
  }
}

.review-card__desc {
  margin-top: 14rpx;
  color: #5b6b7b;
  font-size: 22rpx;
  line-height: 1.7;
}

.review-card__tags {
  display: flex;
  flex-wrap: wrap;
  gap: 12rpx;
  margin-top: 14rpx;
}

.review-card__tag {
  display: inline-flex;
  align-items: center;
  min-height: 40rpx;
  padding: 0 16rpx;
  border-radius: 999rpx;
  background: rgba(15, 23, 42, 0.06);
  color: #526274;
  font-size: 20rpx;
}

.review-card__risk {
  margin-top: 14rpx;
  padding: 16rpx 18rpx;
  border-radius: 18rpx;
  background: rgba(236, 138, 87, 0.1);
  color: #8a4a2d;
  font-size: 22rpx;
  line-height: 1.7;
}

.status-hint-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 42rpx;
  padding: 0 18rpx;
  border-radius: 999rpx;
  font-size: 22rpx;
  font-weight: 600;
  color: #9a3412;
  background: rgba(255, 237, 213, 0.96);
}

.status-hint-badge.is-safe {
  color: #166534;
  background: rgba(220, 252, 231, 0.96);
}

.status-hint-badge.is-danger {
  color: #b91c1c;
  background: rgba(254, 226, 226, 0.96);
}

.info-block {
  margin-top: 22rpx;
}

.info-row {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 20rpx;
  padding: 20rpx 0;
  border-bottom: 1rpx solid rgba(123, 135, 151, 0.15);
}

.info-row:last-child {
  border-bottom: none;
}

.info-key {
  font-size: 24rpx;
  color: #718093;
}

.info-value {
  flex: 1;
  text-align: right;
  font-size: 24rpx;
  font-weight: 600;
  color: #243142;
  line-height: 1.6;
}

.danger-text {
  color: #cf5a48;
}

.action-area {
  margin-top: 28rpx;
  padding-bottom: calc(32rpx + env(safe-area-inset-bottom));
}

.action-btn {
  height: 88rpx;
  border: none;
  border-radius: 24rpx;
  font-size: 28rpx;
  font-weight: 600;
  line-height: 88rpx;
}

.action-btn::after {
  border: none;
}

.action-btn.primary {
  background: linear-gradient(90deg, #1c5c88 0%, #ec8a57 100%);
  color: #ffffff;
}

.action-btn.secondary {
  background: #edf1f5;
  color: #4e5d70;
}
</style>

