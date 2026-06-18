<template>
  <view>
    <view v-if="!hasRequiredParam" class="margin-tb-sm zaiui-view-box">
      <view class="bg-white zaiui-card review-card">
        <view class="review-card__title">售后入口参数缺失</view>
        <view class="review-card__desc">
          当前链接没有带订单编号，所以售后信息无法加载；现在会先给出承接提示，不再空白停住。
        </view>
        <view class="review-card__risk">
          建议从订单列表重新进入售后页，或先回到订单页继续联调。
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
    <view v-else-if="info.id">
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
            <text class="env-tag">{{ serviceModeText }}</text>
            <text class="env-tag">{{ refundStatusText }}</text>
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
          <view class="env-url">{{ currentEnvInfo.api_root_url }}</view>
        </view>
      </view>
      <view class="margin-tb-sm zaiui-view-box">
        <view class="bg-white zaiui-card review-card">
          <view class="review-card__title">提交前复核</view>
          <view class="review-card__desc">{{ reviewHint }}</view>
          <view class="review-card__meta">
            <text class="review-card__tag">{{ serviceModeText }}</text>
            <text class="review-card__tag">{{ refundStatusText }}</text>
            <text class="review-card__tag">{{ refundAmountText }}</text>
            <text class="review-card__tag">{{ evidenceStatusText }}</text>
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
          <view class="review-card__title">售后结果追踪</view>
          <view class="review-card__desc">{{ serviceFollowupHint }}</view>
          <view class="review-card__meta">
            <text
              v-for="item in serviceFollowupTags"
              :key="item"
              class="review-card__tag"
              >{{ item }}</text
            >
          </view>
          <view class="review-card__risk">{{ serviceFollowupRiskText }}</view>
        </view>
      </view>
      <view class="cu-form-group">
        <view class="title">订单号</view>
        <view>{{ info.order_no }}</view>
      </view>
      <view class="cu-form-group">
        <view class="title">商品数量</view>
        <view>{{ info.total_num }}</view>
      </view>
      <view class="cu-form-group">
        <view class="title">订单总额</view>
        <view>{{ info.total_price }}</view>
      </view>
      <view class="cu-form-group">
        <view class="title">申请类型</view>
        <u-radio-group
          v-model="info_data.refund_type"
          placement="row"
          :disabled="info.refund_status >= 1"
        >
          <u-radio label="退款" :name="1"></u-radio>
          <u-radio
            :customStyle="{ marginLeft: '20px' }"
            label="退货退款"
            :name="2"
          ></u-radio>
        </u-radio-group>
      </view>
      <view class="cu-form-group form-view">
        <view class="title">退款金额</view>
        <u-number-box
          :disabled="info.refund_status >= 1"
          bgColor="transparent"
          v-model="info_data.refund_price"
          :min="0.01"
          :max="info.total_price"
          :decimalLength="2"
          :showMinus="true"
          :showPlus="true"
          :inputWidth="150"
        ></u-number-box>
      </view>
      <view class="cu-form-group">
        <view class="title">申请原因</view>
        <input
          :disabled="info.refund_status >= 1"
          placeholder="请填写售后原因"
          v-model="info_data.refund_reason_wap_explain"
        />
      </view>
      <view class="bg-white padding form-view">
        <view class="text-black title">图片说明</view>
        <view class="grid col-4 grid-square flex-sub margin-top">
          <view
            class="bg-img"
            v-for="(item, index) in info_data.refund_reason_wap_imgs"
            :key="index"
            @tap="viewImage"
            :data-url="item.file_url"
          >
            <image :src="item.file_url" mode="aspectFill"></image>
            <view
              class="cu-tag bg-red"
              @tap.stop="delImg"
              :data-id="item.file_id"
            >
              <text class="cuIcon-close"></text>
            </view>
          </view>
          <view
            class="solids"
            @tap="chooseImage"
            v-if="
              info_data.refund_reason_wap_imgs.length < 4 &&
              info.refund_status <= 0
            "
          >
            <text class="cuIcon-cameraadd"></text>
          </view>
        </view>
      </view>

      <view class="cu-form-group" v-if="info.refund_status >= 1">
        <view class="title">售后状态</view>
        <view v-if="info.refund_status == 1">待审核</view>
        <view v-else-if="info.refund_status == 2">
          {{ info_data.refund_type == 1 ? "已退款" : "待退货" }}
        </view>
        <view v-else-if="info.refund_status == 3">拒绝申请</view>
        <view v-else-if="info.refund_status == 4">已退款</view>
      </view>

      <view class="cu-form-group" v-if="info.refund_status == 3">
        <view class="title">拒绝原因</view>
        <view class="title">{{ info.refund_reason }}</view>
      </view>

      <view
        class="cu-form-group"
        v-if="info.refund_status == 2 && info_data.refund_type == 2"
      >
        <view class="title">收货人：{{ info.refund_consignee }}</view>
        <button
          class="cu-btn sm line-black"
          style="width: 60px"
          @click="str_copy(info.refund_consignee)"
        >
          复制
        </button>
      </view>
      <view
        class="cu-form-group"
        v-if="info.refund_status == 2 && info_data.refund_type == 2"
      >
        <view class="title">联系电话：{{ info.refund_phone }}</view>
        <button
          class="cu-btn sm line-black"
          style="width: 60px"
          @click="str_copy(info.refund_phone)"
        >
          复制
        </button>
      </view>
      <view
        class="cu-form-group"
        v-if="info.refund_status == 2 && info_data.refund_type == 2"
      >
        <view class="title">收货地址：{{ info.refund_address }}</view>
        <button
          class="cu-btn sm line-black"
          style="width: 60px"
          @click="str_copy(info.refund_address)"
        >
          复制
        </button>
      </view>
      <view
        class="cu-form-group"
        v-if="info.refund_status == 2 && info_data.refund_type == 2"
      >
        <view class="title">快递公司：</view>
        <view v-if="hasReturnDeliverySubmitted" class="service-readonly-value">
          {{ info.refund_express_name || "已提交，等待平台回显" }}
        </view>
        <MyPicker
          v-else
          v-model="info.refund_delivery_id"
          :options="info.delivery_list"
          placeholder="请选择快递公司"
        ></MyPicker>
      </view>
      <view
        class="cu-form-group"
        v-if="info.refund_status == 2 && info_data.refund_type == 2"
      >
        <view class="title">快递单号：</view>
        <view v-if="hasReturnDeliverySubmitted" class="service-readonly-field">
          <view class="service-readonly-value">
            {{ info.refund_express || "已提交，等待平台回显" }}
          </view>
          <button
            v-if="info.refund_express"
            class="cu-btn sm line-black"
            style="width: 60px"
            @click="str_copy(info.refund_express)"
          >
            复制
          </button>
        </view>
        <input
          v-else
          placeholder="请输入快递单号"
          v-model="info.refund_express"
        />
      </view>

      <view class="bg-white zaiui-foot-padding-bottom margin-top">
        <view class="flex flex-direction">
          <button
            class="cu-btn bg-red"
            @click="submitBtn"
            v-if="info.refund_status <= 0"
          >
            申请售后
          </button>
          <button
            class="cu-btn bg-red"
            @click="submitDeliveryBtn"
            v-else-if="
              info.refund_status == 2 &&
              info_data.refund_type == 2 &&
              !hasReturnDeliverySubmitted
            "
          >
            提交发货
          </button>
          <button class="cu-btn bg-red" @click="backBtn" v-else>返回</button>
        </view>
      </view>
    </view>
    <view
      class="cu-load"
      :class="isLoad ? 'loading' : info.id || !hasRequiredParam ? '' : 'over'"
    ></view>
  </view>
</template>

<script>
import MyPicker from "@/components/zaiui-common/basics/MyPicker";
import api from "@/api";
import util from "@/utils/util.js";
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
    MyPicker,
  },
  data() {
    return {
      isLoad: false,
      isBtn: false,
      id: 0,
      hasRequiredParam: true,
      info: {},
      info_data: {
        id: 0,
        refund_reason_wap_explain: "",
        refund_reason_wap_imgs: [],
        refund_type: 2,
        refund_price: 0,
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
        ? "当前为正式环境，售后申请和退货发货会写入真实订单数据，请确认后提交。"
        : "当前为非正式环境，可用于售后申请、退货寄回和审核链路联调。";
    },
    serviceModeText() {
      return Number(this.info_data.refund_type || 2) === 1
        ? "当前售后：退款"
        : "当前售后：退货退款";
    },
    refundStatusText() {
      if (Number(this.info.refund_status || 0) <= 0) {
        return "售后状态：待提交";
      }
      if (Number(this.info.refund_status || 0) === 1) {
        return "售后状态：待审核";
      }
      if (Number(this.info.refund_status || 0) === 2) {
        return Number(this.info_data.refund_type || 0) === 1
          ? "售后状态：待退款"
          : "售后状态：待退货";
      }
      if (Number(this.info.refund_status || 0) === 3) {
        return "售后状态：已拒绝";
      }
      return "售后状态：已完成";
    },
    refundAmountText() {
      return `退款金额：￥${Number(this.info_data.refund_price || 0).toFixed(2)}`;
    },
    hasReturnDeliverySubmitted() {
      return (
        Number(this.info.refund_status || 0) === 2 &&
        Number(this.info_data.refund_type || 0) === 2 &&
        !!this.info.refund_express
      );
    },
    evidenceStatusText() {
      return `凭证图片：${Array.isArray(this.info_data.refund_reason_wap_imgs) ? this.info_data.refund_reason_wap_imgs.length : 0} 张`;
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
        ? `售后申请、退货物流填写和回退处理都会直接影响真实订单。${getEnvIsolationHint(this.currentEnvInfo)}`
        : `建议先在当前环境验证售后申请、图片上传、退货寄回和审核状态回显。${getEnvIsolationHint(this.currentEnvInfo)}`;
    },
    reviewHint() {
      return this.currentEnvInfo.is_prod
        ? "当前为正式环境，售后申请或退货物流提交后会直接作用于真实订单。"
        : "当前为非正式环境，建议重点验证售后申请、退货寄回、审核回显和订单列表结果承接。";
    },
    reviewRiskText() {
      if (this.hasReturnDeliverySubmitted) {
        return "退货物流已经提交成功，下一步等待平台收货并继续退款处理。";
      }
      if (
        Number(this.info.refund_status || 0) === 2 &&
        Number(this.info_data.refund_type || 0) === 2
      ) {
        if (!this.info.refund_delivery_id || !this.info.refund_express) {
          return "当前为待退货状态，未填写快递公司或运单号时无法提交退货物流。";
        }
        return "当前为退货寄回阶段，提交前请再次核对快递公司、运单号和收货信息。";
      }
      if (!this.info_data.refund_reason_wap_explain) {
        return "当前还没有填写售后原因，提交会被拦截。";
      }
      if (
        !this.info_data.refund_reason_wap_imgs ||
        this.info_data.refund_reason_wap_imgs.length <= 0
      ) {
        return "当前还没有上传售后凭证图片，提交会被拦截。";
      }
      if (!this.info_data.refund_price || this.info_data.refund_price <= 0) {
        return "当前退款金额无效，提交会被拦截。";
      }
      return "当前售后资料已基本齐备，提交后会按当前环境进入审核或退货处理流程。";
    },
    serviceFollowupHint() {
      if (Number(this.info.refund_status || 0) <= 0) {
        return "当前售后单还未正式提交，建议先补齐原因、金额和凭证后再发起申请。";
      }
      if (Number(this.info.refund_status || 0) === 1) {
        return "当前售后单正在等待平台审核，建议关注订单列表和售后结果回显。";
      }
      if (
        Number(this.info.refund_status || 0) === 2 &&
        Number(this.info_data.refund_type || 0) === 2
      ) {
        if (this.hasReturnDeliverySubmitted) {
          return "当前售后单已提交退货物流，接下来重点看平台收货和退款结果是否回显。";
        }
        return "当前售后单已进入退货阶段，提交寄回物流后可继续跟进平台收货和退款结果。";
      }
      if (Number(this.info.refund_status || 0) === 3) {
        return "当前售后申请已被拒绝，建议结合拒绝原因决定是否重新发起申请。";
      }
      return "当前售后流程已进入完成态，可回到订单列表继续核对结果是否已经完全回显。";
    },
    serviceFollowupTags() {
      return [
        this.serviceModeText,
        this.refundStatusText,
        this.refundAmountText,
        this.evidenceStatusText,
        `订单号：${this.info.order_no || "--"}`,
      ];
    },
    serviceFollowupRiskText() {
      if (Number(this.info.refund_status || 0) <= 0) {
        return "当前售后单尚未提交，离开前请确认售后原因、凭证图片和退款金额是否已补齐。";
      }
      if (this.hasReturnDeliverySubmitted) {
        return this.currentEnvInfo.is_prod
          ? "当前退货物流已经写入正式环境，请持续关注平台收货、退款和订单结果回显。"
          : "当前退货物流已经写入测试环境，建议继续联调订单列表、售后页和后台审核结果是否同步回显。";
      }
      if (
        Number(this.info.refund_status || 0) === 2 &&
        Number(this.info_data.refund_type || 0) === 2 &&
        (!this.info.refund_delivery_id || !this.info.refund_express)
      ) {
        return "当前处于待退货阶段，但寄回物流还未完整填写，平台无法继续处理。";
      }
      return this.currentEnvInfo.is_prod
        ? "当前售后流程运行在正式环境，所有提交和寄回信息都会影响真实订单，请以结果回显为准继续跟进。"
        : "当前售后流程运行在非正式环境，建议继续联调订单列表、售后页和结果回显是否都能闭环承接。";
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
        title: "申请售后",
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
    saveRecentServiceRuntime(payload = {}) {
      cache.set(
        RECENT_ORDER_RUNTIME_KEY,
        {
          envLabel: this.currentEnvInfo.label,
          envTag: this.envIsolationText,
          successAt: new Date().toLocaleString("zh-CN", { hour12: false }),
          targetStatus: 5,
          targetStatusText: payload.targetStatusText || "售后处理中",
          actionType: payload.actionType || "service_update",
          actionTitle: payload.actionTitle || "售后流程已更新",
          actionDesc:
            payload.actionDesc ||
            "可在订单列表继续跟进售后状态和平台处理结果。",
          totalPrice: Number(this.info.total_price || 0).toFixed(2),
          goodsCount: Number(this.info.total_num || 0),
          merchantTitles: this.info.merchant_title
            ? [this.info.merchant_title]
            : [],
          payTypeText:
            Number(this.info_data.refund_type || 2) === 1
              ? "退款申请"
              : "退货退款",
          deliveryTypeText: payload.deliveryTypeText || "售后单",
          orderId: this.info.id || "",
          orderNo: this.info.order_no || "",
        },
        7200,
      );
    },
    backBtn() {
      this.updateRecentActionSummary("准备返回上一页。");
      uni.navigateBack();
    },
    submitDeliveryBtn() {
      if (this.isBtn) {
        return false;
      }
      if (!this.info.refund_delivery_id) {
        this.updateRecentActionSummary("退货物流提交被拦截：未选择快递公司。");
        uni.showToast({
          icon: "none",
          position: "bottom",
          title: "请选择快递公司",
        });
        return false;
      }
      if (!this.info.refund_express) {
        this.updateRecentActionSummary("退货物流提交被拦截：未填写快递单号。");
        uni.showToast({
          icon: "none",
          position: "bottom",
          title: "请输入快递单号",
        });
        return false;
      }
      const confirmContent = buildEnvConfirmText(this.currentEnvInfo, {
        prod: "当前为正式环境，提交后会更新真实退货物流信息，确认继续吗？",
        test: "确认继续提交测试退货物流吗？",
      });
      uni.showModal({
        title: "提交确认",
        content: confirmContent,
        success: (modalRes) => {
          if (!modalRes.confirm) {
            return;
          }
          this.updateRecentActionSummary("准备提交退货物流信息。");
          this.isBtn = true;
          api
            .returnGoods({
              id: this.info.id,
              refund_delivery_id: this.info.refund_delivery_id,
              refund_express: this.info.refund_express,
            })
            .then(() => {
              this.updateRecentActionSummary("退货物流已提交，等待平台收货。");
              this.saveRecentServiceRuntime({
                actionType: "service_return_delivery",
                actionTitle: "退货物流已提交，等待平台收货",
                actionDesc:
                  "退货快递信息已记录，可在订单列表继续查看售后处理进度。",
                targetStatusText: "待平台收货",
                deliveryTypeText: "退货寄回",
              });
              uni.showToast({
                icon: "none",
                position: "bottom",
                title: this.currentEnvInfo.is_prod
                  ? "已发货，请等待平台收货"
                  : "测试物流已提交",
              });
              uni.redirectTo({
                url: "/pages/order/list?status=5",
              });
            })
            .catch(() => {})
            .finally(() => {
              this.isBtn = false;
            });
        },
      });
    },
    str_copy(content) {
      this.updateRecentActionSummary("已复制售后收货信息。");
      uni.setClipboardData({
        data: content,
        showToast: false,
        success: function () {
          uni.showToast({
            icon: "none",
            title: "已复制",
          });
        },
      });
    },
    delImg(e) {
      if (this.info.refund_status >= 1) {
        return false;
      }
      uni.showModal({
        title: "温馨提示",
        content: "确定要删除该图片吗？",
        cancelText: "取消",
        confirmText: "确定",
        success: (res) => {
          if (res.confirm) {
            for (
              let i = 0;
              i < this.info_data.refund_reason_wap_imgs.length;
              i++
            ) {
              if (
                this.info_data.refund_reason_wap_imgs[i].file_id ==
                e.currentTarget.dataset.id
              ) {
                this.info_data.refund_reason_wap_imgs.splice(i, 1);
              }
            }
            this.updateRecentActionSummary(
              `已删除售后凭证图片，当前剩余 ${this.info_data.refund_reason_wap_imgs.length} 张。`,
            );
          }
        },
      });
    },
    chooseImage() {
      if (this.info_data.refund_reason_wap_imgs.length < 4) {
        util.uploadImage().then((res) => {
          this.info_data.refund_reason_wap_imgs.push({
            file_id: res.file_id,
            file_url: res.file_url,
          });
          this.updateRecentActionSummary(
            `已上传售后凭证图片，当前 ${this.info_data.refund_reason_wap_imgs.length} 张。`,
          );
        });
      }
    },
    viewImage(e) {
      const urls = [];
      for (let i = 0; i < this.info_data.refund_reason_wap_imgs.length; i++) {
        urls.push(this.info_data.refund_reason_wap_imgs[i].file_url);
      }
      this.updateRecentActionSummary(
        `正在预览售后凭证图片，当前 ${urls.length} 张。`,
      );
      uni.previewImage({
        urls,
        current: e.currentTarget.dataset.url,
      });
    },
    getInfo() {
      this.isLoad = true;
      api
        .getOrderInfo({ id: this.id })
        .then((res) => {
          const nextInfo = res.data || {};
          const deliveryOptions = Array.isArray(nextInfo.delivery_list)
            ? nextInfo.delivery_list
            : Array.isArray(
                  nextInfo.delivery_list && nextInfo.delivery_list.list,
                )
              ? nextInfo.delivery_list.list
              : [];
          this.info = Object.assign(
            {
              delivery_list: [],
            },
            nextInfo,
            {
              delivery_list: deliveryOptions,
            },
          );
          this.info_data.refund_reason_wap_explain =
            this.info.refund_reason_wap_explain || "";
          this.info_data.refund_reason_wap_imgs = Array.isArray(
            this.info.refund_reason_wap_imgs,
          )
            ? this.info.refund_reason_wap_imgs
            : [];
          this.info_data.refund_type = this.info.refund_type || 2;
          this.info_data.refund_price =
            this.info.refund_price || this.info.total_price || 0;
          this.updateRecentActionSummary(
            this.hasReturnDeliverySubmitted
              ? `退货物流已回显：${this.info.refund_express_name || "快递公司待回显"} ${this.info.refund_express || ""}`.trim()
              : `售后详情已刷新：${this.refundStatusText}。`,
          );
        })
        .catch(() => {
          this.updateRecentActionSummary("售后详情加载失败。");
        })
        .finally(() => {
          this.isLoad = false;
        });
    },
    submitBtn() {
      if (this.isBtn) {
        return false;
      }
      this.info_data.id = this.info.id;
      if (!this.info_data.refund_reason_wap_explain) {
        this.updateRecentActionSummary("售后申请被拦截：未填写申请原因。");
        uni.showToast({
          icon: "none",
          position: "bottom",
          title: "请输入申请原因",
        });
        return false;
      }
      if (
        !this.info_data.refund_reason_wap_imgs ||
        this.info_data.refund_reason_wap_imgs.length <= 0
      ) {
        this.updateRecentActionSummary("售后申请被拦截：未上传凭证图片。");
        uni.showToast({
          icon: "none",
          position: "bottom",
          title: "请上传图片",
        });
        return false;
      }
      if (!this.info_data.refund_price || this.info_data.refund_price <= 0) {
        this.updateRecentActionSummary("售后申请被拦截：退款金额无效。");
        uni.showToast({
          icon: "none",
          position: "bottom",
          title: "请输入退款金额",
        });
        return false;
      }
      const confirmContent = buildEnvConfirmText(this.currentEnvInfo, {
        prod: "当前为正式环境，提交后会生成真实售后申请单，确认继续吗？",
        test: "确认继续提交测试售后申请吗？",
      });
      uni.showModal({
        title: "提交确认",
        content: confirmContent,
        success: (modalRes) => {
          if (!modalRes.confirm) {
            return;
          }
          this.updateRecentActionSummary(
            `准备提交售后申请：${Number(this.info_data.refund_type || 2) === 1 ? "退款" : "退货退款"}。`,
          );
          this.isBtn = true;
          api
            .submitService(this.info_data)
            .then(() => {
              this.updateRecentActionSummary("售后申请已提交，等待平台审核。");
              this.saveRecentServiceRuntime({
                actionType: "service_submit",
                actionTitle: "售后申请已提交，等待平台审核",
                actionDesc:
                  Number(this.info_data.refund_type || 2) === 1
                    ? "退款申请已提交，可在订单列表继续跟进审核与退款进度。"
                    : "退货退款申请已提交，可在订单列表继续跟进审核、退货和退款进度。",
                targetStatusText: "待审核",
                deliveryTypeText:
                  Number(this.info_data.refund_type || 2) === 1
                    ? "退款申请"
                    : "退货退款",
              });
              uni.showToast({
                icon: "none",
                position: "bottom",
                title: this.currentEnvInfo.is_prod
                  ? "申请成功，请等待审核"
                  : "测试申请已提交",
              });
              uni.redirectTo({
                url: "/pages/order/list?status=5",
              });
            })
            .catch(() => {})
            .finally(() => {
              this.isBtn = false;
            });
        },
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

.zaiui-btn-view {
  position: fixed;
  width: 100%;
  bottom: 0;
  .flex {
    padding: 50rpx;
  }
}

.zaiui-foot-padding-bottom {
  width: 80%;
  margin: 20px auto;
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

.service-readonly-field {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20rpx;
}

.service-readonly-value {
  flex: 1;
  color: #2d3748;
  font-size: 26rpx;
  line-height: 1.6;
  word-break: break-all;
  text-align: right;
}
</style>

