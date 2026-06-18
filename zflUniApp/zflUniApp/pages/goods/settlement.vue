<template>
  <view>
    <view v-if="loadError" class="margin-tb-sm zaiui-view-box">
      <view class="bg-white zaiui-card load-error-card">
        <view class="load-error-title">{{ text.loadErrorTitle }}</view>
        <view class="load-error-desc">{{ loadError }}</view>
        <view class="load-error-actions">
          <button class="cu-btn line-red radius" @tap="backToPrevious">
            {{ text.backToPrevious }}
          </button>
          <button class="cu-btn bg-red radius" @tap="retryLoad">
            {{ text.retryLoad }}
          </button>
        </view>
      </view>
    </view>

    <view v-if="!loadError" class="margin-tb-sm zaiui-view-box">
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
        <view class="env-tags">
          <text class="env-tag">{{ settlementModeText }}</text>
          <text class="env-tag">{{ afterSalesAgreementStatus }}</text>
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

      <view class="bg-white zaiui-card review-card">
        <view class="review-card__title">提交前复核</view>
        <view class="review-card__desc">{{ submitReviewHint }}</view>
        <view class="review-card__tags">
          <text class="review-card__tag">{{ submitReviewTags.delivery }}</text>
          <text class="review-card__tag">{{ submitReviewTags.pay }}</text>
          <text class="review-card__tag">{{ submitReviewTags.goods }}</text>
          <text class="review-card__tag">{{ submitReviewTags.agreement }}</text>
        </view>
        <view class="review-card__risk">{{ submitRiskHint }}</view>
      </view>

      <view v-if="recentActionSummary" class="bg-white zaiui-card review-card">
        <view class="review-card__title">最近操作</view>
        <view class="review-card__desc">{{ recentActionSummary }}</view>
      </view>

      <view class="bg-white zaiui-card review-card">
        <view class="review-card__title">下单后跟进</view>
        <view class="review-card__desc">{{ settlementFollowupHint }}</view>
        <view class="review-card__tags">
          <text
            class="review-card__tag"
            v-for="item in settlementFollowupTags"
            :key="item"
            >{{ item }}</text
          >
        </view>
        <view class="review-card__risk">{{ settlementFollowupRiskText }}</view>
      </view>

      <scroll-view scroll-x class="bg-white nav">
        <view class="flex text-center">
          <view
            class="cu-item flex-sub"
            :class="info.delivery_type == 2 ? 'text-red cur' : ''"
            @tap="tabSelect(2)"
            >{{ text.pickup }}</view
          >
          <view
            class="cu-item flex-sub"
            :class="info.delivery_type == 1 ? 'text-red cur' : ''"
            @tap="tabSelect(1)"
            >{{ text.express }}</view
          >
        </view>
      </scroll-view>

      <view v-if="info.delivery_type == 1">
        <view class="cu-form-group">
          <view class="title">{{ text.receiverName }}</view>
          <input
            :placeholder="text.receiverPlaceholder"
            v-model="info.take_name"
          />
          <text
            class="cuIcon-roundclosefill text-gray"
            v-if="info.take_name"
            @click="clearField('take_name')"
          />
        </view>
        <view class="cu-form-group">
          <view class="title">{{ text.phone }}</view>
          <input
            :placeholder="text.phonePlaceholder"
            v-model="info.take_phone"
          />
          <text
            class="cuIcon-roundclosefill text-gray"
            v-if="info.take_phone"
            @click="clearField('take_phone')"
          />
        </view>
        <view class="cu-form-group">
          <view class="title">{{ text.region }}</view>
          <picker
            mode="region"
            @change="bindRegionChange"
            :value="info.take_region"
            :custom-item="customItem"
          >
            <view class="picker">
              {{ !info.take_region[0] ? text.pleaseSelect : "" }}
              {{ info.take_region[0]
              }}{{ info.take_region[1] ? "-" + info.take_region[1] : ""
              }}{{ info.take_region[2] ? "-" + info.take_region[2] : "" }}
            </view>
          </picker>
        </view>
        <view class="cu-form-group">
          <view class="title">{{ text.address }}</view>
          <input
            :placeholder="text.addressPlaceholder"
            v-model="info.take_address"
          />
          <text
            class="cuIcon-roundclosefill text-gray"
            v-if="info.take_address"
            @click="clearField('take_address')"
          />
        </view>
      </view>

      <view v-else-if="info.delivery_type == 2" class="address-view">
        <view class="cu-list menu-avatar">
          <view
            class="cu-item"
            v-for="(item, index) in info.merchant_list"
            :key="index"
          >
            <view class="bg-grey icon-view">
              <text class="cuIcon-shopfill" />
            </view>
            <view class="content">
              <view class="text-black"
                ><text>{{
                  merchantTitleText(item.title, text.noMerchantAddress)
                }}</text></view
              >
              <view class="text-gray text-sm flex">
                <view class="text-cut">{{
                  item.address ? item.address : text.noMerchantAddress
                }}</view>
              </view>
            </view>
          </view>
        </view>
        <view class="cu-form-group">
          <view class="title">{{ text.contactName }}</view>
          <input
            :placeholder="text.contactPlaceholder"
            v-model="info.self_name"
          />
          <text
            class="cuIcon-roundclosefill text-gray"
            v-if="info.self_name"
            @click="clearField('self_name')"
          />
        </view>
        <view class="cu-form-group">
          <view class="title">{{ text.reservePhone }}</view>
          <input
            :placeholder="text.reservePhonePlaceholder"
            v-model="info.self_phone"
          />
          <text
            class="cuIcon-roundclosefill text-gray"
            v-if="info.self_phone"
            @click="clearField('self_phone')"
          />
        </view>
      </view>
    </view>

    <view v-if="!loadError" class="margin-tb-sm zaiui-view-box">
      <view
        class="bg-white zaiui-card goods-view"
        v-for="(shop, shopIndex) in info.merchant_list"
        :key="shopIndex"
      >
        <view class="margin-bottom-sm title-view">
          <view class="cu-avatar sm round" style="background-color: #f0f0f0">
            <text class="cuIcon-shopfill text-orange"></text>
          </view>
          <view class="title-box"
            ><text class="text-black margin-right-xs">{{
              merchantTitleText(shop.title, text.noMerchantAddress)
            }}</text></view
          >
        </view>

        <view
          class="goods-info-view-box solid-bottom"
          v-for="goods in shop.goods"
          :key="goods.id"
        >
          <view
            class="cu-avatar radius lg"
            :style="[{ backgroundImage: 'url(' + goods.image_url + ')' }]"
          />
          <view class="goods-info-view">
            <view class="text-cut text-black">{{ goods.title }}</view>
            <view class="text-sm text-gray">{{ goods.spec }}</view>
            <view
              class="text-sm text-gray"
              v-if="!goods.spec && goods.stock > 0"
              >{{ text.stockLeft }} {{ goods.stock
              }}{{ goods.unit ? goods.unit : text.unit }}</view
            >
            <view class="zaiui-tag-view">
              <text
                class="cu-tag line-red sm radius"
                v-for="(label, labelIndex) in goods.labels"
                :key="labelIndex"
                >{{ label }}</text
              >
            </view>
            <view class="goods-price-view lrflex">
              <text class="text-price text-red text-lg">{{ goods.price }}</text>
              <text class="text-gray"
                >*{{ goods.cart_num
                }}{{ goods.unit ? goods.unit : text.unit }}</text
              >
            </view>
          </view>
        </view>
      </view>
    </view>

    <view
      v-if="!loadError"
      class="cu-load"
      :class="isLoad ? 'loading' : info.merchant_list.length > 0 ? '' : 'over'"
    ></view>

    <view v-if="!loadError" class="margin-tb-sm zaiui-view-box">
      <view class="bg-white zaiui-card zaiui-price-view">
        <view v-if="showMerchantPurchaseLimit" class="merchant-limit-card">
          <view class="merchant-limit-title">{{
            text.merchantLimitTitle
          }}</view>
          <view class="merchant-limit-desc">{{
            merchantPurchaseLimit.message
          }}</view>
          <view class="merchant-limit-grid">
            <view class="merchant-limit-item">
              <text class="merchant-limit-label">{{
                text.todayPurchasedQty
              }}</text>
              <text class="merchant-limit-value">{{
                merchantPurchaseLimit.today_quantity
              }}</text>
            </view>
            <view class="merchant-limit-item">
              <text class="merchant-limit-label">{{
                text.todayPurchasedAmount
              }}</text>
              <text class="merchant-limit-value"
                >￥{{ merchantPurchaseLimit.today_amount }}</text
              >
            </view>
            <view class="merchant-limit-item">
              <text class="merchant-limit-label">{{ text.dailyQtyLimit }}</text>
              <text class="merchant-limit-value">{{
                merchantPurchaseLimit.daily_quantity_limit
              }}</text>
            </view>
            <view class="merchant-limit-item">
              <text class="merchant-limit-label">{{
                text.dailyAmountLimit
              }}</text>
              <text class="merchant-limit-value"
                >￥{{ merchantPurchaseLimit.daily_amount_limit }}</text
              >
            </view>
          </view>
        </view>
        <view class="cu-form-group" style="padding: 0">
          <view class="title">{{ text.goodsTotal }}</view>
          <text class="text-red text-price text-right">{{
            info.total_price
          }}</text>
        </view>
        <view class="cu-form-group" style="padding: 0">
          <view class="title">{{ text.goodsCount }}</view>
          <text class="text-right">{{ info.total_num }}{{ text.unit }}</text>
        </view>
        <view class="cu-form-group" style="padding: 0">
          <view class="title">{{ text.orderRemark }}</view>
          <input
            :placeholder="text.orderRemarkPlaceholder"
            v-model="info.mark"
          />
        </view>
        <view class="cu-form-group" style="padding: 0">
          <view class="title">{{ text.payType }}</view>
          <MyPicker
            v-model="info.pay_type"
            :options="info.pay_types"
            :placeholder="text.selectPayType"
          ></MyPicker>
        </view>
        <view class="bg-white form-view" v-if="info.pay_type == 2">
          <view class="text-black title">{{ text.receiptInfo }}</view>
          <view
            v-if="receiptImageList.length > 0"
            class="grid col-4 grid-square flex-sub"
          >
            <view
              class="bg-img"
              v-for="(item, index) in receiptImageList"
              :key="index"
              @tap="viewReceiptImage"
              :data-url="item"
            >
              <image :src="item" mode="aspectFill"></image>
            </view>
          </view>
          <view v-else class="receipt-empty-tip">{{
            text.receiptInfoEmpty
          }}</view>
        </view>
        <view class="bg-white form-view" v-if="info.pay_type == 2">
          <view class="text-black title">{{ text.payVoucher }}</view>
          <view class="grid col-4 grid-square flex-sub">
            <view
              class="bg-img"
              v-for="(item, index) in info.pay_voucher_imgs"
              :key="index"
              @tap="viewVoucherImage"
              :data-url="item.file_url"
            >
              <image :src="item.file_url" mode="aspectFill"></image>
              <view
                class="cu-tag bg-red"
                @tap.stop="delImg"
                :data-id="item.file_id"
                ><text class="cuIcon-close"></text
              ></view>
            </view>
            <view
              class="solids"
              @tap="chooseImage"
              v-if="info.pay_voucher_imgs.length < 4"
              ><text class="cuIcon-cameraadd"></text
            ></view>
          </view>
        </view>
      </view>
    </view>

    <view v-if="!loadError" class="margin-tb-sm zaiui-view-box agreement-card">
      <view class="agreement-row" @tap="toggleAfterSalesAgree">
        <text
          class="cuIcon"
          :class="
            agreeAfterSales
              ? 'cuIcon-radiobox text-red'
              : 'cuIcon-round text-gray'
          "
        ></text>
        <text class="agreement-text">{{ text.readAgree }}</text>
        <text
          class="agreement-link"
          @tap.stop="openAccord('after_sales_policy')"
          >{{ text.afterSalesLink }}</text
        >
      </view>
      <view class="agreement-note">{{ agreementReminderText }}</view>
    </view>

    <view v-if="!loadError" class="cu-tabbar-height" />

    <view
      v-if="!loadError"
      class="bg-white zaiui-footer-fixed zaiui-foot-padding-bottom"
    >
      <view class="cu-bar padding-lr">
        <view class="text-black text-bold price-view"
          ><text>{{ text.totalPrefix }}￥{{ info.total_price }}</text></view
        >
        <view class="btn-view"
          ><button
            class="cu-btn radius bg-red"
            @tap="payTap"
            :loading="btn_loading"
          >
            {{ text.submitOrder }}
          </button></view
        >
      </view>
    </view>
  </view>
</template>

<script>
import MyPicker from "@/components/zaiui-common/basics/MyPicker";
import api from "@/api";
import util from "@/utils/util.js";
import { ensureAcceptAccords } from "@/utils/accord-accept.js";
import { maskMerchantTitle } from "@/utils/desensitize.js";
import { extractUrlList, normalizeUrl } from "@/utils/resource.js";
import { getPaymentErrorMessage, requestOrderPayment } from "@/utils/payment.js";
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

const TEXT = {
  pickup: "\u95e8\u5e97\u81ea\u63d0",
  express: "\u5feb\u9012\u914d\u9001",
  receiverName: "\u6536\u8d27\u4eba\u59d3\u540d",
  receiverPlaceholder: "\u8bf7\u8f93\u5165\u6536\u8d27\u4eba\u59d3\u540d",
  phone: "\u8054\u7cfb\u7535\u8bdd",
  phonePlaceholder:
    "\u7528\u4e8e\u5546\u5bb6\u548c\u5feb\u9012\u5458\u8054\u7cfb\u60a8",
  region: "\u6240\u5728\u5730\u533a",
  pleaseSelect: "\u8bf7\u9009\u62e9",
  address: "\u8be6\u7ec6\u5730\u5740",
  addressPlaceholder: "\u8bf7\u8f93\u5165\u8be6\u7ec6\u5730\u5740\u4fe1\u606f",
  noMerchantAddress: "\u6682\u65e0\u5546\u5bb6\u5730\u5740",
  contactName: "\u8054\u7cfb\u4eba",
  contactPlaceholder: "\u8bf7\u8f93\u5165\u8054\u7cfb\u4eba\u59d3\u540d",
  reservePhone: "\u9884\u7559\u624b\u673a\u53f7",
  reservePhonePlaceholder:
    "\u7528\u4e8e\u5546\u5bb6\u548c\u95e8\u5e97\u8054\u7cfb\u60a8",
  stockLeft: "\u5269\u4f59",
  unit: "\u4ef6",
  goodsTotal: "\u5546\u54c1\u603b\u989d",
  goodsCount: "\u5546\u54c1\u6570\u91cf",
  orderRemark: "\u8ba2\u5355\u5907\u6ce8",
  orderRemarkPlaceholder: "\u8bf7\u8f93\u5165\u8ba2\u5355\u5907\u6ce8",
  payType: "\u652f\u4ed8\u65b9\u5f0f",
  selectPayType: "\u8bf7\u9009\u62e9\u652f\u4ed8\u65b9\u5f0f",
  receiptInfo: "\u5546\u5bb6\u6536\u6b3e\u7801",
  receiptInfoEmpty:
    "\u6682\u672a\u914d\u7f6e\u5546\u5bb6\u6536\u6b3e\u7801\uff0c\u8bf7\u8054\u7cfb\u7ba1\u7406\u5458",
  payVoucher: "\u652f\u4ed8\u51ed\u8bc1",
  readAgree: "\u6211\u5df2\u9605\u8bfb\u5e76\u540c\u610f",
  afterSalesLink: "\u300a\u552e\u540e/\u9000\u8d27\u8bf4\u660e\u300b",
  totalPrefix: "\u5408\u8ba1\uff1a",
  submitOrder: "\u786e\u8ba4\u4e0b\u5355",
  needReceiverName: "\u8bf7\u8f93\u5165\u6536\u8d27\u4eba\u59d3\u540d",
  needPhone: "\u8bf7\u8f93\u5165\u8054\u7cfb\u7535\u8bdd",
  invalidPhone: "\u8054\u7cfb\u7535\u8bdd\u683c\u5f0f\u4e0d\u6b63\u786e",
  needRegion: "\u8bf7\u9009\u62e9\u6240\u5728\u5730\u533a",
  needAddress: "\u8bf7\u8f93\u5165\u8be6\u7ec6\u5730\u5740",
  needContact: "\u8bf7\u8f93\u5165\u8054\u7cfb\u4eba",
  needReservePhone: "\u8bf7\u8f93\u5165\u9884\u7559\u624b\u673a\u53f7",
  invalidReservePhone:
    "\u9884\u7559\u624b\u673a\u53f7\u683c\u5f0f\u4e0d\u6b63\u786e",
  needPayType: "\u8bf7\u9009\u62e9\u652f\u4ed8\u65b9\u5f0f",
  needVoucher: "\u8bf7\u4e0a\u4f20\u652f\u4ed8\u51ed\u8bc1",
  needAfterSales:
    "\u8bf7\u5148\u540c\u610f\u552e\u540e/\u9000\u8d27\u8bf4\u660e",
  retryHint:
    "\u534f\u8bae\u8bb0\u5f55\u6682\u672a\u540c\u6b65\uff0c\u5c06\u7ee7\u7eed\u63d0\u4ea4\u5f53\u524d\u8ba2\u5355",
  agreementSyncRequired:
    "\u534f\u8bae\u8bb0\u5f55\u540c\u6b65\u5931\u8d25\uff0c\u8bf7\u91cd\u8bd5\u540e\u518d\u63d0\u4ea4\u8ba2\u5355",
  modalTitle: "\u6e29\u99a8\u63d0\u793a",
  paySuccess: "\u652f\u4ed8\u6210\u529f",
  submitSuccess:
    "\u8ba2\u5355\u63d0\u4ea4\u6210\u529f\uff0c\u8bf7\u7b49\u5f85\u5ba1\u6838",
  loadErrorTitle: "\u8ba2\u5355\u4fe1\u606f\u52a0\u8f7d\u5931\u8d25",
  loadErrorDesc:
    "\u8ba2\u5355\u4fe1\u606f\u6682\u65f6\u65e0\u6cd5\u52a0\u8f7d\uff0c\u8bf7\u91cd\u8bd5\u6216\u8fd4\u56de\u4e0a\u4e00\u9875\u91cd\u65b0\u63d0\u4ea4\u3002",
  invalidParams:
    "\u8ba2\u5355\u53c2\u6570\u4e0d\u5b8c\u6574\uff0c\u8bf7\u8fd4\u56de\u8d2d\u7269\u8f66\u91cd\u65b0\u4e0b\u5355",
  retryLoad: "\u91cd\u8bd5\u52a0\u8f7d",
  backToPrevious: "\u8fd4\u56de\u4e0a\u4e00\u9875",
  paymentCancelled:
    "\u5df2\u53d6\u6d88\u652f\u4ed8\uff0c\u8ba2\u5355\u5df2\u4fdd\u7559\uff0c\u53ef\u5728\u8ba2\u5355\u5217\u8868\u7ee7\u7eed\u652f\u4ed8",
  paymentFailed:
    "\u652f\u4ed8\u672a\u6210\u529f\uff0c\u8ba2\u5355\u5df2\u4fdd\u7559\uff0c\u53ef\u7a0d\u540e\u5728\u8ba2\u5355\u5217\u8868\u7ee7\u7eed\u5904\u7406",
  stayHere: "\u7559\u5728\u5f53\u524d",
  viewOrders: "\u67e5\u770b\u8ba2\u5355",
  confirmDeleteImage:
    "\u786e\u5b9a\u8981\u5220\u9664\u8fd9\u5f20\u56fe\u7247\u5417\uff1f",
  cancel: "\u53d6\u6d88",
  confirm: "\u786e\u5b9a",
  merchantLimitTitle: "\u5546\u5bb6\u8d2d\u4e70\u9650\u5236",
  todayPurchasedQty: "\u4eca\u65e5\u5df2\u8d2d\u4ef6\u6570",
  todayPurchasedAmount: "\u4eca\u65e5\u5df2\u8d2d\u91d1\u989d",
  dailyQtyLimit: "\u5355\u65e5\u4ef6\u6570\u4e0a\u9650",
  dailyAmountLimit: "\u5355\u65e5\u91d1\u989d\u4e0a\u9650",
};

export default {
  components: { MyPicker },
  data() {
    return {
      text: TEXT,
      isLoad: false,
      btn_loading: false,
      loadError: "",
      info: {
        delivery_type: 2,
        take_name: "",
        take_phone: "",
        take_region: ["", "", ""],
        take_address: "",
        self_name: "",
        self_phone: "",
        total_num: 0,
        total_price: 0,
        merchant_list: [],
        mark: "",
        pay_type: 1,
        pay_voucher_imgs: [],
      },
      customItem: "\u5168\u90e8",
      query: {},
      agreeAfterSales: false,
      recentActionSummary: "",
    };
  },
  computed: {
    currentEnvInfo() {
      return getCurrentEnvInfo();
    },
    envDescription() {
      return this.currentEnvInfo.is_prod
        ? "当前为正式环境，提交订单会写入正式数据，请确认仅用于真实下单。"
        : "当前为非正式环境，可用于结算联调、支付凭证上传和下单流程验收。";
    },
    settlementModeText() {
      return this.info.pay_type == 1
        ? "当前支付：在线支付"
        : "当前支付：线下凭证";
    },
    afterSalesAgreementStatus() {
      return this.agreeAfterSales ? "售后协议：已勾选" : "售后协议：未勾选";
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
        ? `提交前请再次核对商品、收货信息、支付方式和售后协议勾选状态。${getEnvIsolationHint(this.currentEnvInfo)}`
        : `建议先在当前环境完成支付凭证上传、收货信息和协议校验，再切正式环境。${getEnvIsolationHint(this.currentEnvInfo)}`;
    },
    agreementReminderText() {
      return this.agreeAfterSales
        ? "已确认售后/退货说明，提交时会同步尝试补记协议记录。"
        : "协议默认不勾选，未勾选时无法提交订单。";
    },
    submitReviewTags() {
      return {
        delivery:
          this.info.delivery_type == 1
            ? "配送方式：快递配送"
            : "配送方式：门店自提",
        pay:
          this.info.pay_type == 1 ? "支付方式：在线支付" : "支付方式：线下凭证",
        goods: `商品数量：${Number(this.info.total_num || 0)} 件`,
        agreement: this.agreeAfterSales
          ? "售后协议：已勾选"
          : "售后协议：未勾选",
      };
    },
    submitReviewHint() {
      return this.currentEnvInfo.is_prod
        ? "当前为正式环境，提交前请再次核对收货信息、支付方式、凭证图片和协议状态。"
        : "当前为非正式环境，建议先完整走一遍收货信息、支付方式和协议校验，再切灰度或正式环境。";
    },
    submitRiskHint() {
      if (this.info.pay_type == 2 && this.info.pay_voucher_imgs.length <= 0) {
        return "当前为线下凭证支付，但还没有上传凭证图片，提交会被拦截。";
      }
      if (!this.agreeAfterSales) {
        return "售后/退货说明默认不勾选，未勾选时无法提交订单。";
      }
      if (
        this.info.delivery_type == 1 &&
        (!this.info.take_name ||
          !this.info.take_phone ||
          !this.info.take_address)
      ) {
        return "当前为快递配送，请重点核对收货人、联系电话和详细地址。";
      }
      if (
        this.info.delivery_type == 2 &&
        (!this.info.self_name || !this.info.self_phone)
      ) {
        return "当前为门店自提，请重点核对联系人和预留手机号。";
      }
      return "当前信息已基本齐备，提交后会按当前环境进入下单或支付流程。";
    },
    settlementFollowupHint() {
      if (this.info.pay_type == 1) {
        return this.info.delivery_type == 1
          ? "在线支付成功后，订单会进入待发货流程，建议继续关注订单列表中的发货与物流状态。"
          : "在线支付成功后，订单会进入待核销流程，建议继续关注订单列表中的核销状态和门店提货信息。";
      }
      return "线下凭证提交后，订单会先进入后台审核流程，建议继续关注订单列表中的审核结果和后续处理。";
    },
    settlementFollowupTags() {
      return [
        this.info.delivery_type == 1
          ? "后续状态：待发货/待收货"
          : "后续状态：待核销/待提货",
        this.info.pay_type == 1
          ? "提交结果：支付后生效"
          : "提交结果：后台审核后生效",
        this.showMerchantPurchaseLimit
          ? "商家限购：已启用"
          : "商家限购：未启用",
        `商家收款码：${this.receiptImageList.length > 0 ? "已配置" : "未配置"}`,
      ];
    },
    settlementFollowupRiskText() {
      if (this.info.pay_type == 1) {
        return this.currentEnvInfo.is_prod
          ? "当前为正式环境，支付成功后会直接生成真实订单，请以下单后的订单状态回显为准继续跟进。"
          : "当前为非正式环境，建议重点联调支付成功后的订单列表跳转、状态回显和继续支付承接。";
      }
      if (this.receiptImageList.length <= 0) {
        return "当前为线下凭证支付，但页面还没有可核对的商家收款码，继续提交前建议先联系管理员确认。";
      }
      return this.currentEnvInfo.is_prod
        ? "当前为正式环境，线下凭证提交后会进入真实审核流程，请重点核对凭证图片和商家收款码。"
        : "当前为非正式环境，建议重点联调凭证上传、后台审核和订单列表结果回显。";
    },
    merchantPurchaseLimit() {
      return this.info.merchant_purchase_limit || {};
    },
    showMerchantPurchaseLimit() {
      return (
        Number(this.merchantPurchaseLimit.enabled || 0) === 1 &&
        Number(this.merchantPurchaseLimit.is_merchant_member || 0) === 1
      );
    },
    receiptImageList() {
      const normalizeList = (list = []) => {
        if (!Array.isArray(list)) {
          return [];
        }
        return Array.from(
          new Set(
            list
              .map((item) => {
                if (typeof item === "string") {
                  return normalizeUrl(item);
                }
                if (item && typeof item === "object") {
                  return normalizeUrl(
                    item.image_url || item.file_url || item.url || "",
                  );
                }
                return "";
              })
              .filter(Boolean),
          ),
        );
      };

      const receiptImageList = normalizeList(this.info.receipt_image_list);
      if (receiptImageList.length > 0) {
        return receiptImageList;
      }

      // 平台收款码优先显示
      const platformVoucherUrl = normalizeUrl(
        this.info.platform_voucher_image_url || "",
      );
      if (
        Number(this.info.use_platform_voucher || 0) === 1 &&
        platformVoucherUrl
      ) {
        return [platformVoucherUrl];
      }

      const merchantReceiptList = extractUrlList(
        this.info.merchant_list,
        "receipt_image_url",
      );
      if (merchantReceiptList.length > 0) {
        return Array.from(new Set(merchantReceiptList));
      }

      return Array.from(
        new Set(extractUrlList(this.info.merchant_list, "image_url")),
      );
    },
  },
  onLoad(options) {
    if (options.source && options.goods_ids) {
      this.query = { source: options.source, goods_ids: options.goods_ids };
      this.getInfo();
    } else {
      this.loadError = this.text.invalidParams;
      this.updateRecentActionSummary("下单参数缺失，已切换到结算承接提示。");
    }
  },
  methods: {
    updateRecentActionSummary(summary) {
      this.recentActionSummary = summary;
    },
    saveRecentOrderRuntime(payload = {}) {
      const merchantTitles = (this.info.merchant_list || [])
        .map((item) => this.merchantTitleText(item && item.title, "平台订单"))
        .filter(Boolean);
      const goodsCount = Number(this.info.total_num || 0);
      const totalPrice = Number(this.info.total_price || 0).toFixed(2);
      cache.set(
        RECENT_ORDER_RUNTIME_KEY,
        {
          envLabel: this.currentEnvInfo.label,
          envTag: this.envIsolationText,
          successAt: new Date().toLocaleString("zh-CN", { hour12: false }),
          orderId: payload.orderId || payload.id || "",
          orderNo: payload.orderNo || payload.order_no || "",
          targetStatus: payload.targetStatus,
          targetStatusText: payload.targetStatusText,
          actionType: payload.actionType,
          actionTitle: payload.actionTitle,
          actionDesc: payload.actionDesc,
          totalPrice,
          goodsCount,
          merchantTitles: merchantTitles.slice(0, 3),
          payTypeText: this.info.pay_type == 1 ? "在线支付" : "线下凭证",
          deliveryTypeText:
            this.info.delivery_type == 1 ? "快递配送" : "门店自提",
        },
        7200,
      );
    },
    merchantTitleText(value, fallback = "") {
      return maskMerchantTitle(value, fallback);
    },
    retryLoad() {
      this.loadError = "";
      this.updateRecentActionSummary("正在重新加载结算信息。");
      this.getInfo();
    },
    backToPrevious() {
      if (getCurrentPages().length > 1) {
        uni.navigateBack();
        return;
      }
      uni.navigateTo({
        url: "/pages/goods/my_cart",
      });
    },
    getInfo() {
      this.isLoad = true;
      api
        .getConfirmOrder(this.query)
        .then((res) => {
          this.info = res.data;
          this.loadError = "";
          this.isLoad = false;
          this.updateRecentActionSummary(
            `结算信息已刷新：共 ${Number(this.info.total_num || 0)} 件商品，合计 ￥${this.info.total_price || 0}。`,
          );
        })
        .catch((err) => {
          this.isLoad = false;
          if (!(err && err.merchant_expired)) {
            this.loadError = this.text.loadErrorDesc;
          }
          this.updateRecentActionSummary(
            "结算信息加载失败，请返回购物车重新发起下单。",
          );
        });
    },
    bindRegionChange(e) {
      this.info.take_region = e.detail.value;
      this.updateRecentActionSummary(
        `已更新收货地区：${this.info.take_region.join("-")}。`,
      );
    },
    clearField(field) {
      this.info[field] = "";
      this.updateRecentActionSummary("已清空当前输入项。");
    },
    toggleAfterSalesAgree() {
      this.agreeAfterSales = !this.agreeAfterSales;
      this.updateRecentActionSummary(
        this.agreeAfterSales
          ? "已勾选售后/退货说明。"
          : "已取消售后/退货说明勾选。",
      );
    },
    openAccord(accordId) {
      this.updateRecentActionSummary("正在查看售后/退货说明。");
      uni.navigateTo({ url: "/pages/system/accord?accord_id=" + accordId });
    },
    tabSelect(tabCur) {
      this.info.delivery_type = tabCur;
      this.updateRecentActionSummary(
        tabCur == 1 ? "已切换为快递配送。" : "已切换为门店自提。",
      );
    },
    handlePaymentFail(error) {
      const errMsg = String((error && error.errMsg) || "");
      const isCancel = /cancel/i.test(errMsg);
      uni.showModal({
        title: this.text.modalTitle,
        content: isCancel
          ? this.text.paymentCancelled
          : getPaymentErrorMessage(error, this.text.paymentFailed),
        cancelText: this.text.stayHere,
        confirmText: this.text.viewOrders,
        success: (modalRes) => {
          if (modalRes.confirm) {
            this.updateRecentActionSummary(
              "支付未完成，准备跳转订单列表继续处理。",
            );
            uni.redirectTo({ url: "/pages/order/list" });
          }
        },
      });
    },
    continueSubmitOrder() {
      this.btn_loading = true;
      this.updateRecentActionSummary(
        `准备提交订单：${this.info.pay_type == 1 ? "在线支付" : "线下凭证"}，${this.info.delivery_type == 1 ? "快递配送" : "门店自提"}。`,
      );
      // audit marker: ensureAcceptAccords({ scene: "order_confirm", accord_uniques: ["after_sales_policy"] })
      ensureAcceptAccords(
        { scene: "order_confirm", accord_uniques: ["after_sales_policy"] },
        { toast: true, message: this.text.agreementSyncRequired },
      )
        .then(() => {
          this.info.source = this.query.source;
          this.info.goods_ids = this.query.goods_ids;
          return api.confirmOrder(this.info);
        })
        .then((res) => {
          this.btn_loading = false;
          const orderInfo = res.data;
          if (this.info.pay_type == 1) {
            requestOrderPayment(orderInfo)
              .then(() => {
                this.saveRecentOrderRuntime({
                  actionType: "pay_success",
                  actionTitle: "支付成功，订单已进入待发货/待核销流程",
                  actionDesc:
                    this.info.delivery_type == 1
                      ? "可在订单列表继续跟进发货、物流和收货状态。"
                      : "可在订单列表查看核销进度和取件码信息。",
                  targetStatus: 1,
                  targetStatusText:
                    this.info.delivery_type == 1 ? "待发货" : "待核销",
                  orderId: orderInfo.id || orderInfo.order_id,
                  orderNo: orderInfo.order_no,
                });
                this.updateRecentActionSummary(
                  "订单支付成功，准备跳转订单列表。",
                );
                uni.showModal({
                  title: this.text.modalTitle,
                  showCancel: false,
                  content: this.text.paySuccess,
                  success: (modalRes) => {
                    if (modalRes.confirm)
                      uni.redirectTo({ url: "/pages/order/list?status=1" });
                  },
                });
              })
              .catch((e) => {
                this.updateRecentActionSummary(
                  `支付未完成：${getPaymentErrorMessage(e, "未知原因")}。`,
                );
                this.handlePaymentFail(e);
              });
          } else {
            this.saveRecentOrderRuntime({
              actionType: "submit_success",
              actionTitle: "订单已提交，等待后台审核",
              actionDesc:
                "线下凭证已随订单提交，可在订单列表继续跟进审核结果和后续处理。",
              targetStatus: 0,
              targetStatusText: "待审核",
              orderId: orderInfo.id || orderInfo.order_id,
              orderNo: orderInfo.order_no,
            });
            this.updateRecentActionSummary("订单已提交，等待后台审核。");
            uni.showModal({
              title: this.text.modalTitle,
              showCancel: false,
              content: this.text.submitSuccess,
              success: (modalRes) => {
                if (modalRes.confirm)
                  uni.redirectTo({ url: "/pages/order/list" });
              },
            });
          }
        })
        .catch((error) => {
          if (error && error.msg)
            uni.showToast({ icon: "none", title: error.msg });
          this.updateRecentActionSummary(
            "订单提交失败，请确认当前结算信息和接口返回。",
          );
          this.btn_loading = false;
        });
    },
    payTap() {
      if (this.btn_loading) return false;
      const phoneRegEx = /^1[3-9]\d{9}$/;
      if (this.info.delivery_type == 1) {
        if (!this.info.take_name)
          return (
            uni.showToast({ icon: "none", title: this.text.needReceiverName }),
            false
          );
        if (!this.info.take_phone)
          return (
            uni.showToast({ icon: "none", title: this.text.needPhone }),
            false
          );
        if (!phoneRegEx.test(this.info.take_phone))
          return (
            uni.showToast({ icon: "none", title: this.text.invalidPhone }),
            false
          );
        if (!this.info.take_region[0] || !this.info.take_region[1])
          return (
            uni.showToast({ icon: "none", title: this.text.needRegion }),
            false
          );
        if (!this.info.take_address)
          return (
            uni.showToast({ icon: "none", title: this.text.needAddress }),
            false
          );
      } else if (this.info.delivery_type == 2) {
        if (!this.info.self_name)
          return (
            uni.showToast({ icon: "none", title: this.text.needContact }),
            false
          );
        if (!this.info.self_phone)
          return (
            uni.showToast({ icon: "none", title: this.text.needReservePhone }),
            false
          );
        if (!phoneRegEx.test(this.info.self_phone))
          return (
            uni.showToast({
              icon: "none",
              title: this.text.invalidReservePhone,
            }),
            false
          );
      }
      if (!this.info.pay_type)
        return (
          uni.showToast({ icon: "none", title: this.text.needPayType }),
          false
        );
      if (this.info.pay_type == 2 && this.info.pay_voucher_imgs.length <= 0)
        return (
          uni.showToast({ icon: "none", title: this.text.needVoucher }),
          false
        );
      if (!this.agreeAfterSales)
        return (
          uni.showToast({ icon: "none", title: this.text.needAfterSales }),
          false
        );
      this.updateRecentActionSummary("已通过前置校验，等待确认提交订单。");

      const confirmContent = buildEnvConfirmText(this.currentEnvInfo, {
        prod: `当前为正式环境，提交后会写入真实订单数据。确认继续${this.info.pay_type == 1 ? "支付并提交订单" : "提交订单"}吗？`,
        test: `确认继续${this.info.pay_type == 1 ? "支付联调并提交订单" : "提交测试订单"}吗？`,
      });

      uni.showModal({
        title: this.text.modalTitle,
        content: confirmContent,
        cancelText: this.text.cancel,
        confirmText: this.text.confirm,
        success: (res) => {
          if (res.confirm) {
            this.continueSubmitOrder();
          }
        },
      });
    },
    chooseImage() {
      if (this.info.pay_voucher_imgs.length < 4) {
        util.uploadImage().then((res) => {
          this.info.pay_voucher_imgs.push({
            file_id: res.file_id,
            file_url: res.file_url,
          });
          this.updateRecentActionSummary(
            `已上传支付凭证，当前 ${this.info.pay_voucher_imgs.length} 张。`,
          );
        });
      }
    },
    viewVoucherImage(e) {
      const urls = extractUrlList(this.info.pay_voucher_imgs);
      this.updateRecentActionSummary(
        `正在预览支付凭证，当前 ${urls.length} 张。`,
      );
      uni.previewImage({ urls, current: e.currentTarget.dataset.url });
    },
    viewReceiptImage(e) {
      const urls = this.receiptImageList;
      this.updateRecentActionSummary(
        `正在预览收款信息，当前 ${urls.length} 张。`,
      );
      uni.previewImage({ urls, current: e.currentTarget.dataset.url });
    },
    delImg(e) {
      uni.showModal({
        title: this.text.modalTitle,
        content: this.text.confirmDeleteImage,
        cancelText: this.text.cancel,
        confirmText: this.text.confirm,
        success: (res) => {
          if (res.confirm) {
            for (let i = 0; i < this.info.pay_voucher_imgs.length; i++) {
              if (
                this.info.pay_voucher_imgs[i].file_id ==
                e.currentTarget.dataset.id
              ) {
                this.info.pay_voucher_imgs.splice(i, 1);
              }
            }
            this.updateRecentActionSummary(
              `已删除支付凭证，当前剩余 ${this.info.pay_voucher_imgs.length} 张。`,
            );
          }
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
@import "../../static/zaiui/style/settlement.scss";

.agreement-card {
  .agreement-row {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 10rpx;
    padding: 20rpx 24rpx;
    border-radius: 18rpx;
    background: #fff;
    box-shadow: 0 12rpx 28rpx rgba(17, 34, 51, 0.05);
  }
  .agreement-text {
    color: #5f6f82;
    font-size: 24rpx;
  }
  .agreement-link {
    color: #2a6f93;
    font-size: 24rpx;
  }
  .agreement-note {
    margin-top: 12rpx;
    color: #7a8797;
    font-size: 22rpx;
    line-height: 1.7;
  }
}

.env-card {
  margin-bottom: 20rpx;
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
  background: rgba(15, 23, 42, 0.04);
  color: #395078;
}

.env-risk-list {
  margin-top: 12rpx;
  padding: 14rpx 16rpx;
  border-radius: 18rpx;
  background: rgba(255, 244, 232, 0.98);
}

.env-risk-item {
  color: #8a4a2d;
  font-size: 22rpx;
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
  margin-bottom: 20rpx;
  padding: 24rpx 28rpx;
  border-radius: 24rpx;
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

.review-card__tags {
  display: flex;
  flex-wrap: wrap;
  gap: 12rpx;
  margin-top: 12rpx;
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
  margin-top: 12rpx;
  padding: 16rpx 18rpx;
  border-radius: 18rpx;
  background: rgba(236, 138, 87, 0.1);
  color: #8a4a2d;
  font-size: 22rpx;
  line-height: 1.7;
}

.merchant-limit-card {
  margin-bottom: 24rpx;
  padding: 24rpx;
  border-radius: 24rpx;
  background: linear-gradient(
    135deg,
    rgba(21, 75, 114, 0.08),
    rgba(235, 139, 89, 0.12)
  );
}

.merchant-limit-title {
  font-size: 28rpx;
  font-weight: 700;
  color: #16324d;
}

.merchant-limit-desc {
  margin-top: 12rpx;
  font-size: 24rpx;
  line-height: 1.7;
  color: #5d6f83;
}

.merchant-limit-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16rpx;
  margin-top: 20rpx;
}

.merchant-limit-item {
  padding: 18rpx 20rpx;
  border-radius: 20rpx;
  background: rgba(255, 255, 255, 0.8);
}

.merchant-limit-label {
  display: block;
  font-size: 22rpx;
  color: #7a8797;
}

.merchant-limit-value {
  display: block;
  margin-top: 10rpx;
  font-size: 28rpx;
  font-weight: 700;
  color: #162233;
}

.load-error-card {
  padding: 36rpx 28rpx;
}

.load-error-title {
  font-size: 32rpx;
  font-weight: 700;
  color: #10283d;
}

.load-error-desc {
  margin-top: 16rpx;
  font-size: 26rpx;
  line-height: 1.7;
  color: #607181;
}

.load-error-actions {
  margin-top: 24rpx;
  display: flex;
  gap: 18rpx;
}

.receipt-empty-tip {
  padding: 24rpx 0 8rpx;
  font-size: 24rpx;
  line-height: 1.7;
  color: #7a8797;
}
</style>

