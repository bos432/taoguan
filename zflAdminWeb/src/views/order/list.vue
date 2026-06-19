<template>
  <div class="app-container">
    <div class="page-toolbar">
      <div class="page-toolbar__main">
        <div class="page-toolbar__title">订单列表</div>
        <div class="page-toolbar__meta">
          <span class="toolbar-chip toolbar-chip--emphasis">总数 {{ count }}</span>
          <span class="toolbar-chip">{{ currentTabLabel }}</span>
          <span class="toolbar-chip">{{ currentPayTypeLabel }}</span>
          <span v-if="entrySourceLabel" class="toolbar-chip">{{ entrySourceLabel }}</span>
          <span class="toolbar-chip">已选 {{ selection.length }} 单</span>
        </div>
      </div>
      <div class="page-toolbar__tips">
        <span class="page-toolbar__hint">{{ selectionActionHint }}</span>
        <span class="page-toolbar__hint page-toolbar__hint--warning">{{ riskHintText }}</span>
      </div>
    </div>
    <el-card class="app-head">
      <div v-if="entryContextVisible" class="entry-context-banner">
        <div class="entry-context-banner__main">
          <div class="entry-context-banner__eyebrow">外部入口承接</div>
          <div class="entry-context-banner__title">{{ entryContextTitle }}</div>
          <div class="entry-context-banner__desc">{{ entryContextDesc }}</div>
        </div>
        <div class="entry-context-banner__actions">
          <el-button type="primary" size="small" @click="handleEntryContextPrimary">
            {{ entryContextPrimaryLabel }}
          </el-button>
          <el-button size="small" @click="clearEntryContext">恢复普通视角</el-button>
        </div>
      </div>
      <div class="order-plain-guide">
        <div class="order-plain-guide__header">
          <div>
            <div class="order-plain-guide__title">订单列表第一次进来，先定位你在处理哪一段链路</div>
            <div class="order-plain-guide__desc">
              先分清是支付审核、发货提货、售后，还是商家对账问题；订单页负责过程处理，涉及商家承接和整体复盘要继续跳别的页。
            </div>
          </div>
          <div class="order-plain-guide__badge">{{ orderGuideFocusLabel }}</div>
        </div>
        <div class="order-plain-guide__grid">
          <div v-for="item in orderGuideCards" :key="item.title" class="order-plain-guide-card">
            <span class="order-plain-guide-card__step">{{ item.step }}</span>
            <div class="order-plain-guide-card__title">{{ item.title }}</div>
            <div class="order-plain-guide-card__desc">{{ item.desc }}</div>
          </div>
        </div>
      </div>
      <!-- 查询 -->
      <el-form
        :model="query"
        ref="searchForm"
        label-width="72px"
        size="small"
        class="compact-query-form"
      >
        <el-row :gutter="12">
          <el-col :xl="7" :lg="8" :md="12" :sm="24">
            <el-form-item label="添加时间：">
              <el-date-picker
                v-model="query.date_value"
                ref="datePicker"
                type="daterange"
                class="ya-date-value"
                start-placeholder="开始日期"
                end-placeholder="结束日期"
                value-format="YYYY-MM-DD"
                :shortcuts="shortcuts"
                :default-time="[new Date(2024, 1, 1, 0, 0, 0), new Date(2024, 1, 1, 23, 59, 59)]"
                @change="search()"
              />
            </el-form-item>
          </el-col>
          <el-col :xl="4" :lg="5" :md="6" :sm="12">
            <el-form-item label="启用状态：" prop="is_disable">
              <el-select v-model="query.is_disable" @change="search()" clearable>
                <el-option :value="0" label="启用" />
                <el-option :value="1" label="禁用" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :xl="4" :lg="5" :md="6" :sm="12">
            <el-form-item label="支付方式：" prop="pay_type">
              <el-select v-model="query.pay_type" @change="search()" clearable>
                <el-option
                  v-for="(item, index) in params.pay_types"
                  :key="index"
                  :label="item.label"
                  :value="item.value"
                />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :xl="9" :lg="6" :md="24" :sm="24">
            <div class="query-search-inline">
              <el-input
                v-model="query.search_value"
                :placeholder="searchInputPlaceholder"
                class="input-with-select"
                @keyup.enter="search()"
                clearable
              >
                <template #prepend>
                  <el-select v-model="query.search_field" placeholder="字段" style="width: 92px">
                    <el-option :value="idkey" label="ID" />
                    <el-option value="order_no" label="订单号" />
                    <el-option value="unique" label="标识" />
                    <el-option value="name" label="名称" />
                    <el-option value="desc" label="描述" />
                    <el-option value="remark" label="备注" />
                  </el-select>
                </template>
              </el-input>
              <el-button type="primary" @click="search()">搜索</el-button>
              <el-button title="重置" @click="refresh()">重置</el-button>
            </div>
          </el-col>
        </el-row>
      </el-form>

      <div class="filter-summary-bar">
        <div class="filter-summary-bar__chips">
          <span class="active-filter-strip__label">筛选</span>
          <span v-for="item in activeFilterTags" :key="item" class="active-filter-strip__item">{{
            item
          }}</span>
          <span v-if="!activeFilterTags.length" class="active-filter-strip__item"
            >未设置筛选条件</span
          >
        </div>
        <div v-if="recentActionSummary" class="filter-summary-bar__side">
          <span class="recent-action-strip__text">{{ recentActionSummary }}</span>
        </div>
      </div>
      <div class="quick-filter-bar">
        <el-button
          type="warning"
          plain
          size="small"
          :class="{ 'quick-filter-bar__button--active': isPendingVoucherVerifyFilter }"
          @click="showPendingVoucherVerifyOrders"
        >
          待核销/待支付审核
          <span v-if="pendingVoucherVerifyCount > 0" class="quick-filter-bar__count">
            {{ pendingVoucherVerifyCount }}
          </span>
        </el-button>
        <el-button
          v-if="isPendingVoucherVerifyFilter"
          size="small"
          text
          @click="clearPendingVoucherVerifyFilter"
        >
          取消待核销筛选
        </el-button>
        <span class="quick-filter-bar__hint">
          只看支付凭证且尚未审核通过的订单，方便先处理核销/支付审核。
        </span>
      </div>
    </el-card>

    <el-dialog
      v-model="selectDialog"
      :title="selectTitle"
      :close-on-click-modal="false"
      :close-on-press-escape="false"
      top="20vh"
    >
      <el-form ref="selectRef" label-width="120px">
        <el-form-item v-if="selectType === 'datetime'" label="时间范围">
          <el-date-picker
            v-model="start_time"
            type="datetime"
            value-format="YYYY-MM-DD HH:mm:ss"
            default-time="00:00:00"
            placeholder="开始时间"
          />
          <span>至</span>
          <el-date-picker
            v-model="end_time"
            type="datetime"
            value-format="YYYY-MM-DD HH:mm:ss"
            default-time="23:59:59"
            placeholder="结束时间"
          />
        </el-form-item>
        <el-form-item v-else-if="selectType === 'disable'" label="是否禁用">
          <el-switch v-model="is_disable" :active-value="1" :inactive-value="0" />
        </el-form-item>
        <el-form-item v-else-if="selectType === 'dele'">
          <span class="c-red">确定要删除选中的{{ name }}吗？</span>
        </el-form-item>
        <template v-else-if="selectType === 'delivery'">
          <el-form-item label="快递公司">
            <el-select v-model="delivery_query.setting_delivery_id" clearable>
              <el-option
                v-for="item in params.delivery_list"
                :key="item.value"
                :label="item.label"
                :value="item.value"
                :disabled="item.disabled"
              />
            </el-select>
          </el-form-item>
          <el-form-item label="运单号">
            <el-input
              v-model="delivery_query.kuaidi_order_no"
              placeholder="请输入运单号"
              clearable
            />
          </el-form-item>
        </template>
        <template v-else-if="selectType === 'take_delivery'">
          <el-form-item label="提货人">
            <el-input v-model="take_delivery.self_name" placeholder="提货人" clearable disabled />
          </el-form-item>
          <el-form-item label="联系电话">
            <el-input
              v-model="take_delivery.self_phone"
              placeholder="联系电话"
              clearable
              disabled
            />
          </el-form-item>
          <el-form-item label="取件码">
            <el-input v-model="take_delivery.pick_up_code" placeholder="请输入取件码" clearable />
          </el-form-item>
        </template>
        <template v-else-if="selectType === 'pay_auth'">
          <el-form-item label="支付凭证">
            <FileUploads
              v-model="payAuthInfo.pay_voucher_imgs"
              upload-btn="上传图片"
              file-type="image"
              file-tip="图片文件"
              fileSource="list"
            />
          </el-form-item>
          <el-form-item label="支付状态">
            <el-radio-group v-model="payAuthInfo.pay_status">
              <el-radio :value="1">已支付</el-radio>
              <el-radio :value="0">未支付</el-radio>
            </el-radio-group>
          </el-form-item>
          <el-form-item label="实际支付金额">
            <el-input-number
              v-model="payAuthInfo.pay_price"
              :precision="2"
              :step="0.1"
              :min="0.01"
            />
            <div v-if="selection.length > 1" class="form-help">
              批量审核会按每张订单金额分别入账，避免把同一个凭证金额重复写到多张订单。
            </div>
          </el-form-item>
          <el-form-item label="支付失败原因" v-if="payAuthInfo.pay_status != 1">
            <el-input
              v-model="payAuthInfo.pay_auth_msg"
              placeholder="请输入支付失败原因"
              clearable
            />
          </el-form-item>
        </template>
        <el-form-item :label="name + 'ID'">
          <el-input v-model="selectIds" type="textarea" autosize disabled />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button :loading="loading" @click="selectCancel">取消</el-button>
        <el-button :loading="loading" type="primary" @click="selectSubmit">提交</el-button>
      </template>
    </el-dialog>
    <el-card class="app-main">
      <el-tabs v-model="activeName" class="compact-tabs" @tab-click="handleClick">
        <el-tab-pane label="全部" :name="-1">
          <template #label>
            <span>
              全部
              <el-badge v-if="status_nums.all > 0" :value="status_nums.all" :max="99999999" />
            </span>
          </template>
        </el-tab-pane>
        <template v-for="(item, index) in params.order_status">
          <el-tab-pane :label="item.label" :name="item.value">
            <template #label>
              <span>
                {{ item.label }}
                <el-badge
                  v-if="status_nums[item.code] > 0"
                  :value="status_nums[item.code]"
                  :max="99999999"
                />
              </span>
            </template>
          </el-tab-pane>
        </template>
      </el-tabs>
      <div class="order-followup-panel">
        <div class="order-followup-panel__main">
          <div class="order-followup-panel__header">
            <div>
              <div class="order-followup-panel__title">订单处理后续</div>
              <div class="order-followup-panel__desc">{{ orderFollowupHint }}</div>
            </div>
            <span class="order-followup-panel__badge" :class="orderFollowupBadgeClass">
              {{ orderFollowupBadgeText }}
            </span>
          </div>
          <div class="order-followup-panel__tags">
            <span v-for="item in orderFollowupTags" :key="item">{{ item }}</span>
          </div>
          <div class="order-followup-panel__risk">{{ orderFollowupRiskText }}</div>
        </div>
        <div class="order-followup-panel__actions">
          <el-button :disabled="!focusedMerchantId" type="primary" @click="goToFocusedMerchantPage">
            去商家页继续处理
          </el-button>
          <el-button @click="goToAnalyticsReview">回平台分析复盘</el-button>
          <el-button :disabled="!focusedMerchantId" @click="goToInternalTakeoverReview"
            >去内部接盘对账</el-button
          >
        </div>
      </div>
      <div class="operation_btn mb5">
        <pagination
          v-show="count > 0"
          v-model:total="count"
          v-model:page="query.page"
          v-model:limit="query.limit"
          @pagination="list"
        />
      </div>
      <!-- 列表 -->
      <el-table
        ref="table"
        v-loading="loading"
        :data="data"
        size="small"
        class="compact-order-table"
        @sort-change="sort"
        @selection-change="select"
      >
        <el-table-column type="selection" width="42" title="全选/反选" />
        <el-table-column :prop="idkey" label="ID" width="80" sortable="custom" />
        <el-table-column prop="merchant_title" label="商家" min-width="100" show-overflow-tooltip />
        <el-table-column prop="order_no" label="订单编号" min-width="155" show-overflow-tooltip />
        <el-table-column prop="member_id" label="用户信息" min-width="140" show-overflow-tooltip>
          <template #default="scope">
            {{ scope.row.member_title }}/{{ scope.row.member_id }}
          </template>
        </el-table-column>
        <el-table-column
          prop="delivery_type"
          label="发货类型"
          min-width="100"
          show-overflow-tooltip
        >
          <template #default="scope">
            <el-tag type="primary" v-if="scope.row.delivery_type == 1">物流/快递</el-tag>
            <el-tag type="success" v-if="scope.row.delivery_type == 2">用户自提</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="收货人/自提人" min-width="120" show-overflow-tooltip>
          <template #default="scope">
            <span v-if="scope.row.delivery_type == 1">{{ scope.row.take_name }}</span>
            <span v-if="scope.row.delivery_type == 2">{{ scope.row.self_name }}</span>
          </template>
        </el-table-column>
        <el-table-column label="商品信息" min-width="180">
          <template #default="scope">
            <template v-for="(item, index) in scope.row.detaileds">
              <div class="avatar-text-container" v-if="index == 0">
                <FileImage fileSource="list" :file-url="item.goods.image.file_url" lazy />
                <span>{{ item.goods.title }}</span>
                <span>¥{{ item.price }}x{{ item.quantity }}</span>
              </div>
            </template>
          </template>
        </el-table-column>
        <el-table-column prop="pay_price" label="实际支付" min-width="80" show-overflow-tooltip />
        <el-table-column
          prop="pay_type_title"
          label="支付方式"
          min-width="80"
          show-overflow-tooltip
        />
        <el-table-column prop="pay_status" label="支付状态" min-width="80">
          <template #default="scope">
            <span v-if="scope.row.pay_status == 1">已支付</span>
            <span v-else>未支付</span>
          </template>
        </el-table-column>
        <el-table-column
          prop="status_title"
          label="订单状态"
          min-width="80"
          show-overflow-tooltip
        />
        <el-table-column prop="create_time" label="下单时间" min-width="165" sortable="custom" />
        <el-table-column label="操作" width="150" fixed="right">
          <template #default="scope">
            <el-link
              type="primary"
              class="mr-1"
              :underline="false"
              @click="onDetails(scope.row.id)"
            >
              详情
            </el-link>
            <el-link
              v-if="scope.row.status == 1 && scope.row.delivery_type == 1"
              type="primary"
              class="mr-1"
              :underline="false"
              @click="selectOpen('delivery', [scope.row])"
            >
              发货
            </el-link>
            <el-link
              v-if="scope.row.status == 1 && scope.row.delivery_type == 2"
              type="primary"
              class="mr-1"
              :underline="false"
              @click="selectOpen('take_delivery', [scope.row])"
            >
              提货
            </el-link>
            <el-link
              v-if="scope.row.status == 5"
              type="primary"
              class="mr-1"
              :underline="false"
              @click="orderService(scope.row)"
            >
              售后
            </el-link>
            <el-link
              v-if="scope.row.pay_type == 2 && scope.row.pay_status != 1"
              type="primary"
              class="mr-1"
              :underline="false"
              @click="selectOpen('pay_auth', [scope.row])"
            >
              支付审核
            </el-link>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <!--详情-->
    <detail
      ref="detail"
      :merchant_list="[]"
      :store_platform_list="[]"
      :store_type_list="[]"
      :store_flag_list="[]"
      :regiong_list="[]"
      @closeDrawer="closeDrawer"
      @changeDrawer="changeDrawer"
      @getList="list"
      :drawer="drawer"
    ></detail>
    <!-- 售后 -->
    <el-dialog
      v-model="serviceDialog"
      title="售后详情"
      :close-on-click-modal="false"
      :close-on-press-escape="false"
      :before-close="serviceCancel"
      top="10vh"
    >
      <el-scrollbar class="mt5" native :max-height="height - 30">
        <el-form ref="serviceRef" :rules="serviceRules" :model="serviceInfo" label-width="100px">
          <el-form-item label="订单号" prop="order_no">
            <el-input
              v-model="serviceInfo.order_no"
              placeholder="请输入订单号"
              clearable
              disabled
            />
          </el-form-item>
          <el-form-item label="商品数量" prop="total_num">
            <el-input v-model="serviceInfo.total_num" placeholder="商品数量" clearable disabled />
          </el-form-item>
          <el-form-item label="订单总额" prop="total_price">
            <el-input-number
              v-model="serviceInfo.total_price"
              :precision="2"
              :step="1"
              disabled
              style="width: 100%"
            />
          </el-form-item>
          <div class="service-runtime-card">
            <div class="service-runtime-card__title">退款处理提示</div>
            <div class="service-runtime-card__desc">{{ serviceRefundModeText }}</div>
            <div class="service-runtime-card__meta">
              <span class="service-runtime-card__tag">支付方式：{{ servicePayTypeText }}</span>
              <span class="service-runtime-card__tag">实付金额：￥{{ servicePaidAmountText }}</span>
              <span class="service-runtime-card__tag"
                >本次最多可退：￥{{ serviceRefundCapText }}</span
              >
            </div>
            <div class="service-runtime-card__risk">{{ serviceRefundModeHint }}</div>
          </div>
          <el-form-item label="申请类型" prop="refund_type">
            <el-radio-group v-model="serviceInfo.refund_type">
              <el-radio :value="1">退款</el-radio>
              <el-radio :value="2">退货退款</el-radio>
            </el-radio-group>
          </el-form-item>
          <el-form-item label="退款金额" prop="refund_price">
            <el-input-number
              v-model="serviceInfo.refund_price"
              :precision="2"
              :step="1"
              :min="0.01"
              :max="serviceRefundCap"
              style="width: 100%"
            />
          </el-form-item>
          <el-form-item label="退款原因" prop="refund_reason_wap_explain">
            <el-input
              v-model="serviceInfo.refund_reason_wap_explain"
              placeholder="请输入退款原因"
              clearable
              disabled
            />
          </el-form-item>
          <el-form-item
            label="图片说明"
            prop="refund_reason_wap_imgs"
            v-if="serviceInfo.refund_reason_wap_imgs"
          >
            <FileUploads
              v-model="serviceInfo.refund_reason_wap_imgs"
              upload-btn="上传图片"
              file-type="image"
              file-tip="图片文件"
              fileSource="list"
            />
          </el-form-item>
          <el-form-item label="售后状态" prop="refund_status">
            <el-radio-group v-model="serviceInfo.refund_status">
              <el-radio :value="1" disabled>待审核</el-radio>
              <el-radio :value="2"
                >同意{{ serviceInfo.refund_type == 1 ? '退款' : '退货' }}</el-radio
              >
              <el-radio :value="3">拒绝售后</el-radio>
              <el-radio :value="4" v-if="serviceInfo.refund_type == 2 && serviceInfo.refund_express"
                >同意退款</el-radio
              >
            </el-radio-group>
          </el-form-item>
          <el-form-item
            label="收货人"
            prop="refund_consignee"
            v-if="
              serviceInfo.refund_status == 2 &&
              serviceInfo.refund_type == 2 &&
              !serviceInfo.refund_express
            "
          >
            <el-input v-model="serviceInfo.refund_consignee" placeholder="请输入收货人" clearable />
          </el-form-item>
          <el-form-item
            label="联系电话"
            prop="refund_phone"
            v-if="
              serviceInfo.refund_status == 2 &&
              serviceInfo.refund_type == 2 &&
              !serviceInfo.refund_express
            "
          >
            <el-input v-model="serviceInfo.refund_phone" placeholder="请输入联系电话" clearable />
          </el-form-item>
          <el-form-item
            label="收货地址"
            prop="refund_address"
            v-if="
              serviceInfo.refund_status == 2 &&
              serviceInfo.refund_type == 2 &&
              !serviceInfo.refund_express
            "
          >
            <el-input v-model="serviceInfo.refund_address" placeholder="请输入收货地址" clearable />
          </el-form-item>
          <el-form-item
            label="快递公司"
            prop="refund_express_name"
            v-if="
              serviceInfo.refund_status == 2 &&
              serviceInfo.refund_type == 2 &&
              serviceInfo.refund_express
            "
          >
            <el-input
              v-model="serviceInfo.refund_express_name"
              placeholder="请输入快递公司"
              clearable
              disabled
            />
          </el-form-item>
          <el-form-item
            label="快递单号"
            prop="refund_express"
            v-if="
              serviceInfo.refund_status == 2 &&
              serviceInfo.refund_type == 2 &&
              serviceInfo.refund_express
            "
          >
            <el-input
              v-model="serviceInfo.refund_express"
              placeholder="请输入快递公司"
              clearable
              disabled
            />
          </el-form-item>
          <el-form-item label="拒绝原因" prop="refund_reason" v-if="serviceInfo.refund_status == 3">
            <el-input v-model="serviceInfo.refund_reason" placeholder="请输入拒绝原因" clearable />
          </el-form-item>
        </el-form>
      </el-scrollbar>
      <template #footer>
        <el-button :loading="loading" @click="serviceCancel">取消</el-button>
        <el-button :loading="loading" type="primary" @click="serviceSubmit">提交</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script>
import screenHeight from '@/utils/screen-height'
import Pagination from '@/components/Pagination/index.vue'
import { arrayColumn } from '@/utils/index'
import { getPageLimit } from '@/utils/settings'
import {
  list,
  info,
  add,
  edit,
  dele,
  disable,
  getParams,
  sendDelivery,
  takeDelivery,
  serviceOrder,
  orderPayAuth
} from '@/api/order/list'
import detail from '@/views/order/handle/details.vue'
export default {
  name: 'orderList',
  components: { Pagination, detail },
  computed: {
    runtimeModeLabel() {
      return process.env.NODE_ENV === 'production' ? '生产构建' : '开发构建'
    },
    runtimeModeBadgeClass() {
      return process.env.NODE_ENV === 'production' ? 'is-production' : 'is-development'
    },
    currentTabLabel() {
      if (this.activeName === -1 || this.activeName === '-1') {
        return '全部订单'
      }
      const current = this.params.order_status?.find(
        (item) => item.value === this.activeName || item.value === Number(this.activeName)
      )
      return current ? current.label : '订单筛选'
    },
    currentPayTypeLabel() {
      const current = this.params.pay_types?.find((item) => item.value === this.query.pay_type)
      return current ? current.label : '全部支付方式'
    },
    currentPayStatusLabel() {
      if (this.query.pay_status === undefined || this.query.pay_status === '') {
        return ''
      }
      return Number(this.query.pay_status) === 1 ? '已核销/已支付' : '待核销/待支付审核'
    },
    pendingVoucherVerifyCount() {
      return Number(this.status_nums?.pending_voucher_verify || 0)
    },
    isPendingVoucherVerifyFilter() {
      return (
        Number(this.query.status) === 0 &&
        Number(this.query.pay_type) === 2 &&
        Number(this.query.pay_status) === 0
      )
    },
    servicePayTypeText() {
      return this.serviceInfo?.pay_type_title || '未识别支付方式'
    },
    serviceRefundCap() {
      const payPrice = Number(this.serviceInfo?.pay_price || 0)
      const totalPrice = Number(this.serviceInfo?.total_price || 0)
      const maxAmount = payPrice > 0 ? payPrice : totalPrice
      return maxAmount > 0 ? Number(maxAmount.toFixed(2)) : 0.01
    },
    serviceRefundCapText() {
      return this.serviceRefundCap.toFixed(2)
    },
    servicePaidAmountText() {
      return this.serviceRefundCap.toFixed(2)
    },
    serviceRefundModeText() {
      if (Number(this.serviceInfo?.pay_type) === 2) {
        return '当前订单为支付凭证单，后台提交后会按人工退款闭环处理，不走微信原路退。'
      }
      return '当前订单为微信支付单，后台提交后会尝试走微信原路退款，请先确认退款金额和阶段。'
    },
    serviceRefundModeHint() {
      if (Number(this.serviceInfo?.pay_type) === 2) {
        return '建议先核对支付凭证、实际支付金额和退款备注；退款金额不能超过实际支付金额。'
      }
      return '建议先核对微信支付金额、退货回寄信息和退款阶段；退款金额不能超过实际支付金额。'
    },
    currentFilterSummary() {
      return this.query.search_value ? `关键词：${this.query.search_value}` : '未设置关键词'
    },
    entrySourceLabel() {
      const source = String(this.$route?.query?.from || '')
      if (source === 'dashboard') {
        return '来自控制台总览'
      }
      if (source === 'platform-analytics') {
        return '来自平台分析'
      }
      if (source === 'legacy-module') {
        return '来自旧栏目承接'
      }
      if (source === 'internal-takeover') {
        return '来自内部接盘对账'
      }
      return ''
    },
    entryContextFocus() {
      return String(this.$route?.query?.focus || '')
    },
    entryContextVisible() {
      return Boolean(this.entrySourceLabel || this.entryContextFocus)
    },
    entryContextTitle() {
      if (this.entryContextFocus === 'alerts') {
        return '当前从待处理提醒进入订单列表'
      }
      if (this.entryContextFocus === 'offline-pay') {
        return '当前从线下支付审核提醒进入订单列表'
      }
      if (this.entryContextFocus === 'delivery') {
        return '当前从履约处理提醒进入订单列表'
      }
      if (this.entrySourceLabel) {
        return `${this.entrySourceLabel}，当前页已自动承接筛选条件`
      }
      return '当前为外部入口承接视角'
    },
    entryContextDesc() {
      if (this.entryContextFocus === 'alerts') {
        return '建议先盯待支付审核、待发货、待提货三类订单，不要混着做售后或删除动作。处理完当前一批，再回分析页复盘整体分布。'
      }
      if (this.entryContextFocus === 'offline-pay') {
        return '当前更适合先核对支付凭证图片、实际支付金额和审核备注，确认无误后再提交支付审核。'
      }
      if (this.entryContextFocus === 'delivery') {
        return '当前更适合先处理待发货和待提货订单，提交前重点核对快递单号、取件码和收货信息。'
      }
      if (this.entrySourceLabel === '来自内部接盘对账') {
        return '当前页负责补订单过程动作；如果要判断内部号接盘是否正常、账单是否一致，处理完订单后继续回内部接盘对账页。'
      }
      return '当前筛选可能来自外部页面，建议先看顶部筛选条确认范围，再决定在订单页直接处理，还是跳商家页/报表页继续承接。'
    },
    entryContextPrimaryLabel() {
      if (this.entryContextFocus === 'alerts' || this.entryContextFocus === 'offline-pay') {
        return '只看待支付审核'
      }
      if (this.entryContextFocus === 'delivery') {
        return '只看待发货/提货'
      }
      if (this.entrySourceLabel === '来自内部接盘对账') {
        return '回内部接盘对账'
      }
      return '回平台分析'
    },
    activeFilterTags() {
      const tags = []
      if (this.entrySourceLabel) {
        tags.push(this.entrySourceLabel)
      }
      if (this.entryContextFocus === 'alerts') {
        tags.push('聚焦：待处理提醒')
      } else if (this.entryContextFocus === 'offline-pay') {
        tags.push('聚焦：线下支付审核')
      } else if (this.entryContextFocus === 'delivery') {
        tags.push('聚焦：履约处理')
      }
      if (this.currentTabLabel) {
        tags.push(`状态：${this.currentTabLabel}`)
      }
      if (
        this.currentPayTypeLabel &&
        this.query.pay_type !== undefined &&
        this.query.pay_type !== ''
      ) {
        tags.push(`支付方式：${this.currentPayTypeLabel}`)
      }
      if (this.currentPayStatusLabel) {
        tags.push(`支付状态：${this.currentPayStatusLabel}`)
      }
      if (this.query.is_disable !== undefined && this.query.is_disable !== '') {
        tags.push(`禁用状态：${Number(this.query.is_disable) === 1 ? '禁用' : '启用'}`)
      }
      if (Array.isArray(this.query.date_value) && this.query.date_value.length === 2) {
        tags.push(`时间：${this.query.date_value[0]} 至 ${this.query.date_value[1]}`)
      }
      if (Number(this.query.merchant_id || 0) > 0) {
        tags.push(`商家ID：${this.query.merchant_id}`)
      }
      if (this.query.search_value) {
        tags.push(`检索：${this.query.search_value}`)
      }
      return tags
    },
    selectReviewItems() {
      const tags = [`已选订单：${this.selection.length} 单`]
      if (this.selection.length) {
        const deliveryCount = this.selection.filter(
          (item) => Number(item.status) === 1 && Number(item.delivery_type) === 1
        ).length
        const pickupCount = this.selection.filter(
          (item) => Number(item.status) === 1 && Number(item.delivery_type) === 2
        ).length
        const payAuthCount = this.selection.filter(
          (item) => Number(item.pay_type) === 2 && Number(item.pay_status) !== 1
        ).length
        if (deliveryCount) {
          tags.push(`待发货：${deliveryCount} 单`)
        }
        if (pickupCount) {
          tags.push(`待提货：${pickupCount} 单`)
        }
        if (payAuthCount) {
          tags.push(`待支付审核：${payAuthCount} 单`)
        }
      }
      return tags
    },
    selectionActionHint() {
      if (!this.selection.length) {
        return '未勾选订单时仅支持筛选浏览；如需批量处理，请先在列表勾选目标订单。'
      }
      return `已勾选 ${this.selection.length} 单订单，可继续执行发货、自提、支付审核或删除操作。`
    },
    riskHintText() {
      if (
        this.selection.some((item) => Number(item.pay_type) === 2 && Number(item.pay_status) !== 1)
      ) {
        return '当前勾选中包含凭证支付待审核订单，建议先核对支付金额、凭证图片和审核备注。'
      }
      if (
        this.selection.some((item) => Number(item.status) === 1 && Number(item.delivery_type) === 2)
      ) {
        return '当前勾选中包含自提订单，提交前请再次核对取件码，避免现场提货失败。'
      }
      if (
        this.selection.some((item) => Number(item.status) === 1 && Number(item.delivery_type) === 1)
      ) {
        return '当前勾选中包含待发货订单，提交前请核对快递公司和运单号，避免误发。'
      }
      return '建议先按状态、支付方式和日期缩小范围，再处理批量发货、支付审核和售后订单。'
    },
    recentActionSummary() {
      if (!this.recentAction) {
        return ''
      }
      const time = this.recentAction.time || ''
      return `${this.recentAction.label}${time ? ` · ${time}` : ''}`
    },
    orderFollowupBadgeText() {
      if (this.selection.length > 0) {
        return '待提交复核'
      }
      if (this.activeFilterTags.length > 0) {
        return '筛选已收敛'
      }
      return '默认巡检'
    },
    orderFollowupBadgeClass() {
      if (this.selection.length > 0) {
        return 'is-active'
      }
      if (
        this.selection.some(
          (item) => Number(item.pay_type) === 2 && Number(item.pay_status) !== 1
        ) ||
        this.selection.some((item) => Number(item.status) === 1)
      ) {
        return 'is-warning'
      }
      return 'is-safe'
    },
    orderFollowupHint() {
      if (this.selection.length > 0) {
        return `当前已勾选 ${this.selection.length} 单订单，可继续处理发货、自提、支付审核和删除，但建议先核对订单状态与支付状态。`
      }
      if (this.activeFilterTags.length > 0) {
        return '当前订单列表已经按筛选条件收敛，建议先抽查订单详情和支付状态，再继续批量处理。'
      }
      return '当前为订单巡检视角，建议优先按订单状态、支付方式和日期缩小范围，再继续做运营处理。'
    },
    orderFollowupTags() {
      const payAuthCount = this.data.filter(
        (item) => Number(item.pay_type) === 2 && Number(item.pay_status) !== 1
      ).length
      const deliveryCount = this.data.filter(
        (item) => Number(item.status) === 1 && Number(item.delivery_type) === 1
      ).length
      const pickupCount = this.data.filter(
        (item) => Number(item.status) === 1 && Number(item.delivery_type) === 2
      ).length
      return [
        `已选订单：${this.selection.length} 单`,
        `聚焦商家：${this.focusedMerchantId || '未锁定'}`,
        `待支付审核：${payAuthCount} 单`,
        `待发货：${deliveryCount} 单`,
        `待自提：${pickupCount} 单`
      ]
    },
    orderGuideFocusLabel() {
      if (this.selection.length > 0) {
        return `当前重点：已圈定 ${this.selection.length} 单，先确认它们是不是同一处理阶段再批量操作`
      }
      if (this.entryContextFocus === 'alerts') {
        return '当前重点：这是待处理提醒承接视角，优先看支付审核、发货、自提三类积压订单'
      }
      if (this.entryContextFocus === 'offline-pay') {
        return '当前重点：先核对凭证支付单，不要和售后、普通已支付订单混着处理'
      }
      if (this.entryContextFocus === 'delivery') {
        return '当前重点：先把履约单处理干净，再回报表页看整体异常分布'
      }
      if (this.activeNameId === 1) {
        return '当前重点：待发货/待提货订单更适合在这里直接处理，处理后再回商家页复核承接'
      }
      if (Number(this.query.pay_type) === 2) {
        return '当前重点：线下凭证支付单优先看支付审核，不要直接跳过去做售后或对账'
      }
      if (this.focusedMerchantId) {
        return '当前重点：当前筛选已聚焦到商家，可先处理订单，再去商家页和内部接盘对账页继续复核'
      }
      return '当前重点：先按状态、支付方式和商家缩小范围，再决定是在订单页处理，还是去商家页/报表页继续承接'
    },
    orderGuideCards() {
      if (this.entryContextFocus === 'alerts') {
        return [
          {
            step: '第一步',
            title: '先把待支付审核和待履约拆开看',
            desc: '待支付审核更适合核对凭证和金额；待发货、待提货更适合直接做履约动作。不要把两类单混在一个批次里提交。'
          },
          {
            step: '第二步',
            title: '批量前先看同批订单是不是同一阶段',
            desc: '如果同一批里既有待支付审核又有待发货单，建议先用状态页签和支付方式继续缩小范围，再做批量动作。'
          },
          {
            step: '第三步',
            title: '过程处理完，再回分析页或商家页复盘',
            desc: '订单页解决的是“这单现在怎么处理”；商家页、平台分析和内部接盘对账页解决的是“这条链路为什么积压、后续有没有异常”。'
          }
        ]
      }
      return [
        {
          step: '第一步',
          title: '先定位是支付、履约还是售后问题',
          desc: '支付凭证异常先看支付审核；物流和自提问题先看发货/提货；已经进入退款退货阶段，再走售后，不要一上来就混着查。'
        },
        {
          step: '第二步',
          title: '批量操作前先确保订单处于同一阶段',
          desc: '同一批勾选里如果既有待支付、待发货又有售后单，批量处理很容易误伤，建议先按状态页签和筛选条件收窄。'
        },
        {
          step: '第三步',
          title: '订单页处理完，再去商家页和报表页复核',
          desc: '订单页更像过程处理台；如果要看商家承接结果、内部号接盘和整体异常分布，继续去商家页、平台分析和内部接盘对账页。'
        }
      ]
    },
    orderFollowupRiskText() {
      if (
        this.selection.some((item) => Number(item.pay_type) === 2 && Number(item.pay_status) !== 1)
      ) {
        return '当前勾选中包含凭证支付待审核订单，建议先核对支付金额、凭证图片和审核备注，再提交支付结果。'
      }
      if (
        this.selection.some((item) => Number(item.status) === 1 && Number(item.delivery_type) === 2)
      ) {
        return '当前勾选中包含自提订单，提交核销前请再次核对取件码和提货人信息，避免现场提货失败。'
      }
      if (
        this.selection.some((item) => Number(item.status) === 1 && Number(item.delivery_type) === 1)
      ) {
        return '当前勾选中包含待发货订单，提交前请再次核对快递公司、运单号和收件信息，避免误发。'
      }
      return '订单处理会直接影响履约状态和支付状态，建议先通过筛选缩小范围，再执行发货、审核和删除动作。'
    },
    focusedMerchantId() {
      if (this.selection.length && this.selection[0]?.merchant_id) {
        return Number(this.selection[0].merchant_id)
      }
      if (Number(this.query.merchant_id || 0) > 0) {
        return Number(this.query.merchant_id)
      }
      if (this.data.length && this.data[0]?.merchant_id) {
        return Number(this.data[0].merchant_id)
      }
      return 0
    }
  },
  data() {
    return {
      name: '订单',
      height: 680,
      loading: false,
      idkey: 'id',
      exps: [{ exp: 'like', name: '包含' }],
      query: {
        page: 1,
        limit: getPageLimit(),
        search_field: 'order_no',
        search_exp: 'like',
        date_field: 'create_time',
        is_disable: undefined,
        status: undefined,
        pay_type: undefined,
        pay_status: undefined,
        merchant_id: undefined
      },
      data: [],
      count: 0,
      dialog: false,
      dialogTitle: '',
      model: {
        link_id: '',
        unique: '',
        image_id: 0,
        image_url: '',
        name: '',
        name_color: '',
        url: '',
        desc: '',
        start_time: '',
        end_time: '2099-12-31 23:59:59',
        underline: 0,
        remark: '',
        sort: 250
      },
      rules: {
        name: [{ required: true, message: '请输入名称', trigger: 'blur' }],
        start_time: [{ required: true, message: '请输入开始时间', trigger: 'blur' }],
        end_time: [{ required: true, message: '请输入结束时间', trigger: 'blur' }]
      },
      types: [],
      selection: [],
      selectIds: '',
      selectTitle: '操作',
      selectDialog: false,
      selectType: '',
      start_time: '',
      end_time: '',
      is_disable: 0,
      drawer: false,
      activeName: -1,
      activeNameId: -1,
      status_nums: {},
      params: {},
      //发货
      delivery_query: {
        setting_delivery_id: undefined,
        kuaidi_order_no: undefined
      },
      //自提
      take_delivery: {
        pick_up_code: '',
        self_name: '',
        self_phone: ''
      },
      //售后
      serviceDialog: false,
      serviceInfo: {},
      serviceRules: {
        refund_consignee: [{ required: true, message: '请输入收货人', trigger: 'blur' }],
        refund_address: [{ required: true, message: '请输入收货地址', trigger: 'blur' }],
        refund_phone: [{ required: true, message: '请输入联系电话', trigger: 'blur' }],
        refund_reason: [{ required: true, message: '请输入拒绝原因', trigger: 'blur' }]
      },
      //支付审核
      payAuthInfo: {
        pay_price: 0, //实际支付金额
        pay_voucher_imgs: [], //支付凭证图片
        pay_status: 1, //支付状态：1、已支付 0、未支付
        pay_auth_msg: '' //支付审核备注
      },
      recentAction: null
    }
  },
  created() {
    this.height = screenHeight()
    this.applyExternalEntryQuery()
    this.getParams()
    this.list()
  },
  watch: {
    '$route.fullPath'(nextPath, prevPath) {
      if (nextPath === prevPath) {
        return
      }
      this.applyExternalEntryQuery()
      this.list()
    }
  },
  methods: {
    applyExternalEntryQuery() {
      const routeQuery = this.$route?.query || {}
      if (routeQuery.merchant_id !== undefined) {
        const merchantId = Number(routeQuery.merchant_id)
        this.query.merchant_id =
          Number.isNaN(merchantId) || merchantId <= 0 ? undefined : merchantId
      }
      if (routeQuery.pay_type !== undefined) {
        const payType = Number(routeQuery.pay_type)
        this.query.pay_type = Number.isNaN(payType) ? undefined : payType
      }
      if (routeQuery.pay_status !== undefined) {
        const payStatus = Number(routeQuery.pay_status)
        this.query.pay_status = Number.isNaN(payStatus) ? undefined : payStatus
      }
      if (routeQuery.order_status !== undefined) {
        const status = Number(routeQuery.order_status)
        this.query.status = Number.isNaN(status) ? undefined : status
        this.activeName = Number.isNaN(status) ? -1 : status
        this.activeNameId = Number.isNaN(status) ? -1 : status
      }
      if (routeQuery.search_value && !this.query.search_value) {
        this.query.search_value = String(routeQuery.search_value)
      }
      if (routeQuery.search_field && !this.query.search_field) {
        this.query.search_field = String(routeQuery.search_field)
      }
      if (routeQuery.search_exp && !this.query.search_exp) {
        this.query.search_exp = String(routeQuery.search_exp)
      }
      if (Array.isArray(routeQuery.date_value) && routeQuery.date_value.length === 2) {
        this.query.date_value = routeQuery.date_value.map((item) => String(item))
      } else if (routeQuery.start_date && routeQuery.end_date) {
        this.query.date_value = [String(routeQuery.start_date), String(routeQuery.end_date)]
      }
      if (routeQuery.focus === 'offline-pay') {
        this.query.pay_type = 2
        this.query.pay_status = 0
        this.query.status = 0
        this.activeName = 0
        this.activeNameId = 0
      }
      if (routeQuery.focus === 'delivery' && this.query.status === undefined) {
        this.query.status = 1
        this.activeName = 1
        this.activeNameId = 1
      }
      this.query.page = 1
      if (this.entrySourceLabel) {
        this.setRecentAction(`外部入口已接入：${this.entrySourceLabel}`)
      }
    },
    buildContinuityQuery(extraQuery = {}) {
      const nextQuery = {
        ...this.$route.query,
        from: 'order-list'
      }
      if (Number(this.query.merchant_id || 0) > 0) {
        nextQuery.merchant_id = String(this.query.merchant_id)
      } else {
        delete nextQuery.merchant_id
      }
      if (this.query.pay_type !== undefined && this.query.pay_type !== '') {
        nextQuery.pay_type = String(this.query.pay_type)
      } else {
        delete nextQuery.pay_type
      }
      if (this.query.pay_status !== undefined && this.query.pay_status !== '') {
        nextQuery.pay_status = String(this.query.pay_status)
      } else {
        delete nextQuery.pay_status
      }
      if (this.query.status !== undefined && this.query.status !== '') {
        nextQuery.order_status = String(this.query.status)
      } else {
        delete nextQuery.order_status
      }
      if (this.query.search_value) {
        nextQuery.search_field = this.query.search_field
        nextQuery.search_exp = this.query.search_exp
        nextQuery.search_value = String(this.query.search_value)
      } else {
        delete nextQuery.search_field
        delete nextQuery.search_exp
        delete nextQuery.search_value
      }
      if (Array.isArray(this.query.date_value) && this.query.date_value.length === 2) {
        nextQuery.date_value = this.query.date_value
        nextQuery.start_date = this.query.date_value[0]
        nextQuery.end_date = this.query.date_value[1]
      } else {
        delete nextQuery.date_value
        delete nextQuery.start_date
        delete nextQuery.end_date
      }
      return {
        ...nextQuery,
        ...extraQuery
      }
    },
    clearEntryContext() {
      const query = { ...this.$route.query }
      delete query.from
      delete query.focus
      this.$router.replace({
        path: this.$route.path,
        query
      })
    },
    handleEntryContextPrimary() {
      if (this.entryContextFocus === 'alerts' || this.entryContextFocus === 'offline-pay') {
        this.showPendingVoucherVerifyOrders()
        return
      }
      if (this.entryContextFocus === 'delivery') {
        this.query.status = 1
        this.activeName = 1
        this.activeNameId = 1
        this.query.page = 1
        this.setRecentAction('已切换为待履约视角')
        this.list()
        return
      }
      if (this.entrySourceLabel === '来自内部接盘对账') {
        this.goToInternalTakeoverReview()
        return
      }
      this.goToAnalyticsReview()
    },
    //处理售后
    serviceSubmit() {
      this.$refs['serviceRef'].validate((valid) => {
        if (valid) {
          this.loading = true
          if (this.serviceInfo[this.idkey]) {
            serviceOrder(this.serviceInfo)
              .then((res) => {
                this.list()
                this.dialog = false
                this.serviceDialog = false
                this.loading = false
                ElMessage.success(res.msg)
              })
              .catch(() => {
                this.loading = false
              })
          }
        }
      })
    },
    buildServiceInfo(row = {}) {
      return {
        ...row,
        refund_reason_wap_imgs: Array.isArray(row.refund_reason_wap_imgs)
          ? row.refund_reason_wap_imgs
          : []
      }
    },
    orderService(row) {
      const id = row?.[this.idkey]
      if (!id) {
        this.serviceInfo = this.buildServiceInfo(row)
        this.serviceDialog = true
        return
      }
      this.loading = true
      info({ [this.idkey]: id })
        .then((res) => {
          this.serviceInfo = this.buildServiceInfo(res.data || row)
          this.serviceDialog = true
        })
        .catch(() => {
          this.serviceInfo = this.buildServiceInfo(row)
          this.serviceDialog = true
        })
        .finally(() => {
          this.loading = false
        })
    },
    serviceCancel() {
      this.serviceDialog = false
      this.serviceInfo = {}
      if (this.$refs['serviceRef']) {
        this.$refs['serviceRef'].clearValidate()
      }
    },
    //查询参数
    getParams() {
      getParams({})
        .then((res) => {
          this.params = res.data
        })
        .catch(() => {})
    },
    //选项卡点击
    handleClick(e) {
      this.query.page = 1
      this.query.status = parseInt(e.paneName)
      this.activeNameId = parseInt(e.paneName)
      this.list()
    },
    showPendingVoucherVerifyOrders() {
      this.query.status = 0
      this.query.pay_type = 2
      this.query.pay_status = 0
      this.query.page = 1
      this.activeName = 0
      this.activeNameId = 0
      this.setRecentAction('已切换为待核销/待支付审核视角')
      this.list()
    },
    clearPendingVoucherVerifyFilter() {
      this.query.pay_status = undefined
      this.query.page = 1
      this.setRecentAction('已取消待核销筛选')
      this.list()
    },
    // 详情
    onDetails(id) {
      this.$refs.detail.isEdit = false
      this.$refs.detail.getInfo(id)
      this.drawer = true
    },
    closeDrawer() {
      this.drawer = false
    },
    changeDrawer() {
      this.drawer = !this.drawer
    },
    // 列表
    list() {
      this.loading = true
      list(this.query)
        .then((res) => {
          this.data = res.data.list
          this.count = res.data.count
          this.exps = res.data.exps
          this.status_nums = res.data.status_nums
          this.setRecentAction(
            `订单列表已刷新：${res.data.count || 0} 单，当前状态 ${this.currentTabLabel}`
          )
          this.loading = false
        })
        .catch(() => {
          this.loading = false
        })
    },
    // 添加修改
    add() {
      this.dialog = true
      this.dialogTitle = this.name + '添加'
      this.reset()
    },
    edit(row) {
      this.dialog = true
      this.dialogTitle = this.name + '修改：' + row[this.idkey]
      var id = {}
      id[this.idkey] = row[this.idkey]
      info(id)
        .then((res) => {
          this.reset(res.data)
        })
        .catch(() => {})
    },
    cancel() {
      this.dialog = false
      this.reset()
    },
    submit() {
      this.$refs['ref'].validate((valid) => {
        if (valid) {
          this.loading = true
          if (this.model[this.idkey]) {
            edit(this.model)
              .then((res) => {
                this.list()
                this.dialog = false
                ElMessage.success(res.msg)
              })
              .catch(() => {
                this.loading = false
              })
          } else {
            add(this.model)
              .then((res) => {
                this.list()
                this.dialog = false
                ElMessage.success(res.msg)
              })
              .catch(() => {
                this.loading = false
              })
          }
        }
      })
    },
    // 重置
    reset(row) {
      if (row) {
        this.model = row
      } else {
        this.model = this.$options.data().model
      }
      if (this.$refs['ref'] !== undefined) {
        try {
          this.$refs['ref'].resetFields()
          this.$refs['ref'].clearValidate()
        } catch (error) {}
      }
    },
    // 查询
    search() {
      this.query.page = 1
      this.list()
    },
    // 刷新
    refresh() {
      const limit = this.query.limit
      this.query = this.$options.data().query
      this.$refs['table'].clearSort()
      this.query.limit = limit
      this.applyExternalEntryQuery()
      this.list()
    },
    // 排序
    sort(sort) {
      this.query.sort_field = sort.prop
      this.query.sort_value = ''
      if (sort.order === 'ascending') {
        this.query.sort_value = 'asc'
        this.list()
      }
      if (sort.order === 'descending') {
        this.query.sort_value = 'desc'
        this.list()
      }
    },
    // 操作
    select(selection) {
      this.selection = selection
      this.selectIds = this.selectGetIds(selection).toString()
    },
    setRecentAction(label) {
      this.recentAction = {
        label,
        time: new Date().toLocaleString('zh-CN', { hour12: false })
      }
    },
    selectGetIds(selection) {
      return arrayColumn(selection, this.idkey)
    },
    selectAlert() {
      ElMessageBox.alert('请选择需要操作的' + this.name, '提示', {
        type: 'warning',
        callback: () => {}
      })
    },
    selectOpen(selectType, selectRow = '') {
      if (selectRow) {
        this.$refs['table'].clearSelection()
        const selectRowLen = selectRow.length
        for (let i = 0; i < selectRowLen; i++) {
          this.$refs['table'].toggleRowSelection(selectRow[i], true)
        }
      }
      if (!this.selection.length) {
        this.selectAlert()
      } else {
        this.selectTitle = '操作'
        if (selectType === 'dele') {
          this.selectTitle = this.name + '删除'
        } else if (selectType === 'disable') {
          this.selectTitle = this.name + '是否禁用'
        } else if (selectType === 'delivery') {
          this.selectTitle = this.name + '发货'
        } else if (selectType === 'take_delivery') {
          let obj = selectRow[0]
          this.take_delivery.pick_up_code = ''
          this.take_delivery.self_name = obj.self_name
          this.take_delivery.self_phone = obj.self_phone
          this.selectTitle = this.name + '提货'
        } else if (selectType === 'pay_auth') {
          let obj = selectRow[0]
          this.payAuthInfo.pay_price = obj.total_price
          this.payAuthInfo.pay_voucher_imgs = obj.pay_voucher_imgs
          this.payAuthInfo.pay_status = obj.pay_status
          this.payAuthInfo.pay_auth_msg = obj.pay_auth_msg
          this.selectTitle = this.name + '凭证支付审核'
        }

        this.selectDialog = true
        this.selectType = selectType
      }
    },
    selectCancel() {
      this.selectDialog = false
    },
    selectSubmit() {
      if (!this.selection.length) {
        this.selectAlert()
      } else {
        const selectType = this.selectType
        if (selectType === 'dele') {
          this.dele(this.selection)
          this.selectDialog = false
        } else if (selectType === 'disable') {
          this.disable(this.selection, true)
          this.selectDialog = false
        } else if (selectType === 'delivery') {
          if (!this.delivery_query.setting_delivery_id) {
            ElMessage.error('请选择快递公司')
            return
          }
          if (!this.delivery_query.kuaidi_order_no) {
            ElMessage.error('请输入运单号')
            return
          }
          this.delivery(this.selection, true)
          this.selectDialog = false
        } else if (selectType === 'take_delivery') {
          if (!this.take_delivery.pick_up_code) {
            ElMessage.error('请输入用户的取件码')
            return
          }
          this.take_deliverys(this.selection, true)
        } else if (selectType === 'pay_auth') {
          if (this.payAuthInfo.pay_status == 1 && this.payAuthInfo.pay_price <= 0) {
            ElMessage.error('请输入实际支付金额')
            return
          }
          if (this.payAuthInfo.pay_status == 0 && !this.payAuthInfo.pay_auth_msg) {
            ElMessage.error('请输入支付失败原因')
            return
          }
          this.payAuth(this.selection, true)
        }
      }
    },
    //支付审核
    payAuth(row, select = false) {
      if (!row.length) {
        this.selectAlert()
      } else {
        this.payAuthInfo.ids = this.selectGetIds(row)
        orderPayAuth(this.payAuthInfo)
          .then((res) => {
            this.selectDialog = false
            this.list()
            this.setRecentAction(`支付审核已提交：${row.length} 单`)
            ElMessage.success(res.msg)
          })
          .catch(() => {})
      }
    },
    //提货
    take_deliverys(row, select = false) {
      if (!row.length) {
        this.selectAlert()
      } else {
        takeDelivery({
          ids: this.selectGetIds(row),
          pick_up_code: this.take_delivery.pick_up_code
        })
          .then((res) => {
            this.selectDialog = false
            this.list()
            this.setRecentAction(`自提核销已提交：${row.length} 单`)
            ElMessage.success(res.msg)
          })
          .catch(() => {})
      }
    },
    //发货
    delivery(row, select = false) {
      if (!row.length) {
        this.selectAlert()
      } else {
        this.loading = true
        sendDelivery({
          ids: this.selectGetIds(row),
          setting_delivery_id: this.delivery_query.setting_delivery_id,
          kuaidi_order_no: this.delivery_query.kuaidi_order_no
        })
          .then((res) => {
            this.list()
            this.setRecentAction(`发货已提交：${row.length} 单`)
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.list()
          })
      }
    },
    // 删除
    dele(row) {
      if (!row.length) {
        this.selectAlert()
      } else {
        dele({
          ids: this.selectGetIds(row)
        })
          .then((res) => {
            this.list()
            this.setRecentAction(`删除已提交：${row.length} 单`)
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.loading = false
          })
      }
    },
    // 是否禁用
    disable(row, select = false) {
      if (!row.length) {
        this.selectAlert()
      } else {
        this.loading = true
        var is_disable = row[0].is_disable
        if (select) {
          is_disable = this.is_disable
        }
        disable({
          ids: this.selectGetIds(row),
          is_disable: is_disable
        })
          .then((res) => {
            this.list()
            this.setRecentAction(
              `${is_disable === 1 ? '禁用' : '启用'}状态已更新：${row.length} 单`
            )
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.list()
          })
      }
    },
    goToFocusedMerchantPage() {
      if (!this.focusedMerchantId) {
        ElMessage.warning('当前没有可继续处理的商家')
        return
      }
      this.$router.push({
        path: '/merchant/merchant',
        query: this.buildContinuityQuery({
          id: String(this.focusedMerchantId),
          focus_mode: 'detail',
          from: 'order-list'
        })
      })
    },
    goToAnalyticsReview() {
      this.$router.push({
        path: '/analytics',
        query: this.buildContinuityQuery()
      })
    },
    goToInternalTakeoverReview() {
      if (!this.focusedMerchantId) {
        ElMessage.warning('当前没有可继续定位的商家')
        return
      }
      this.$router.push({
        path: '/report/internal-takeover',
        query: this.buildContinuityQuery({
          from: 'order-list',
          source_merchant_id: String(this.focusedMerchantId)
        })
      })
    }
  }
}
</script>
<style scoped>
.page-toolbar {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 10px;
  padding: 10px 12px;
  border: 1px solid #e4eaf3;
  border-radius: 8px;
  background: #f8fafc;
}

.page-toolbar__main,
.page-toolbar__tips {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;
}

.entry-context-banner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
  padding: 14px 16px;
  margin-bottom: 14px;
  border: 1px solid #cfe0ff;
  border-radius: 12px;
  background: linear-gradient(135deg, #f3f8ff 0%, #ffffff 100%);
}

.entry-context-banner__main {
  display: grid;
  gap: 6px;
}

.entry-context-banner__eyebrow {
  font-size: 12px;
  font-weight: 600;
  color: #3b82f6;
}

.entry-context-banner__title {
  font-size: 15px;
  font-weight: 600;
  color: #1e293b;
}

.entry-context-banner__desc {
  font-size: 13px;
  line-height: 1.6;
  color: #475569;
}

.entry-context-banner__actions {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.page-toolbar__main {
  flex: 1;
  min-width: 0;
}

.page-toolbar__title {
  font-size: 14px;
  font-weight: 600;
  color: #1f2937;
}

.page-toolbar__meta,
.filter-summary-bar__chips,
.filter-summary-bar__side {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 6px;
}

.toolbar-chip,
.page-toolbar__hint,
.active-filter-strip__label,
.active-filter-strip__item,
.recent-action-strip__text {
  display: inline-flex;
  align-items: center;
  min-height: 24px;
  padding: 0 8px;
  border-radius: 999px;
  font-size: 12px;
  line-height: 1;
  white-space: nowrap;
}

.toolbar-chip,
.active-filter-strip__item,
.recent-action-strip__text {
  color: #475569;
  background: #f1f5f9;
}

.toolbar-chip--emphasis {
  color: #1d4ed8;
  background: #e8f0ff;
}

.toolbar-chip.is-production {
  color: #b45309;
  background: #fff5e8;
}

.toolbar-chip.is-development {
  color: #1d4ed8;
  background: #e8f0ff;
}

.page-toolbar__hint {
  color: #475569;
  background: #ffffff;
  border: 1px solid #e2e8f0;
}

.page-toolbar__hint--warning {
  color: #b45309;
  background: #fff7ed;
  border-color: #fed7aa;
}

.app-head,
.app-main {
  border-radius: 8px;
}

.app-head {
  border: 1px solid #eef2f7;
  box-shadow: none;
}

.app-head :deep(.el-card__body) {
  padding-top: 12px;
  padding-bottom: 12px;
}

.compact-query-form :deep(.el-form-item) {
  margin-bottom: 8px;
}

.compact-query-form :deep(.el-form-item__label) {
  color: #475569;
}

.compact-query-form :deep(.el-input__wrapper),
.compact-query-form :deep(.el-textarea__inner),
.compact-query-form :deep(.el-select__wrapper) {
  box-shadow: 0 0 0 1px #d7dee8 inset;
}

.compact-query-form :deep(.el-date-editor.el-input__wrapper),
.compact-query-form :deep(.el-date-editor--daterange) {
  width: 100%;
}

.query-search-inline {
  display: flex;
  align-items: center;
  gap: 8px;
}

.query-search-inline .input-with-select {
  flex: 1;
  min-width: 0;
}

.filter-summary-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  margin-top: 4px;
  padding-top: 6px;
  border-top: 1px dashed #e2e8f0;
}

.quick-filter-bar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;
  margin-top: 10px;
  padding: 10px 12px;
  border: 1px solid #fed7aa;
  border-radius: 12px;
  background: linear-gradient(135deg, #fff7ed 0%, #ffffff 72%);
}

.quick-filter-bar__button--active {
  box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.18);
}

.quick-filter-bar__count {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 18px;
  height: 18px;
  margin-left: 6px;
  padding: 0 5px;
  border-radius: 999px;
  color: #ffffff;
  background: #f97316;
  font-size: 12px;
  line-height: 1;
}

.quick-filter-bar__hint {
  color: #92400e;
  font-size: 12px;
}

.order-plain-guide {
  margin-bottom: 12px;
  padding: 14px 16px;
  border: 1px solid #dbe7f5;
  border-radius: 14px;
  background: linear-gradient(135deg, #f7fbff 0%, #ffffff 100%);
}

.order-plain-guide__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.order-plain-guide__title {
  font-size: 15px;
  font-weight: 700;
  color: #0f172a;
}

.order-plain-guide__desc {
  margin-top: 6px;
  font-size: 12px;
  color: #64748b;
  line-height: 1.7;
}

.order-plain-guide__badge {
  min-width: 220px;
  padding: 10px 12px;
  border-radius: 12px;
  background: rgba(37, 99, 235, 0.08);
  color: #1d4ed8;
  font-size: 12px;
  font-weight: 700;
  line-height: 1.6;
}

.order-plain-guide__grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-top: 12px;
}

.order-plain-guide-card {
  padding: 12px 14px;
  border: 1px solid #e7edf5;
  border-radius: 14px;
  background: #fff;
}

.order-plain-guide-card__step {
  display: inline-flex;
  align-items: center;
  min-height: 24px;
  padding: 0 10px;
  border-radius: 999px;
  background: #eff6ff;
  color: #1d4ed8;
  font-size: 12px;
  font-weight: 700;
}

.order-plain-guide-card__title {
  margin-top: 10px;
  font-size: 14px;
  font-weight: 700;
  color: #0f172a;
}

.order-plain-guide-card__desc {
  margin-top: 8px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.active-filter-strip__label {
  min-height: auto;
  padding: 0;
  color: #64748b;
  background: transparent;
  font-weight: 600;
}

.order-followup-panel {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 14px;
  margin-bottom: 10px;
  padding: 12px 14px;
  border: 1px solid #e4eaf3;
  border-radius: 10px;
  background: #fbfdff;
}

.order-followup-panel__main {
  flex: 1;
  min-width: 0;
}

.order-followup-panel__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.order-followup-panel__title {
  font-size: 14px;
  font-weight: 600;
  color: #1f2937;
}

.order-followup-panel__desc,
.order-followup-panel__risk {
  margin-top: 6px;
  color: #64748b;
  line-height: 1.7;
}

.order-followup-panel__tags {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-top: 10px;
}

.order-followup-panel__tags span,
.order-followup-panel__badge {
  display: inline-flex;
  align-items: center;
  min-height: 24px;
  padding: 0 8px;
  border-radius: 999px;
  font-size: 12px;
  line-height: 1;
}

.order-followup-panel__tags span {
  color: #475569;
  background: #f1f5f9;
}

.order-followup-panel__badge.is-active {
  color: #1d4ed8;
  background: #e8f0ff;
}

.order-followup-panel__badge.is-warning {
  color: #b45309;
  background: #fff7ed;
}

.order-followup-panel__badge.is-safe {
  color: #047857;
  background: #ecfdf5;
}

.order-followup-panel__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  justify-content: flex-end;
}

.operation_btn {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin: 4px 0 8px;
}

.operation_btn__left,
.operation_btn__right {
  display: flex;
  align-items: center;
}

.compact-tabs :deep(.el-tabs__header) {
  margin-bottom: 8px;
}

.compact-tabs :deep(.el-tabs__item) {
  height: 34px;
  line-height: 34px;
  font-size: 13px;
}

.compact-order-table :deep(.el-table__cell) {
  padding: 8px 0;
}

.compact-order-table :deep(.el-table__body .cell) {
  line-height: 1.4;
}

.compact-order-table :deep(.el-link + .el-link) {
  margin-left: 8px;
}

.service-runtime-card {
  margin-bottom: 18px;
  padding: 14px 16px;
  border-radius: 14px;
  background: linear-gradient(180deg, #f8fbff 0%, #eef6ff 100%);
  border: 1px solid rgba(28, 92, 136, 0.12);
}

.service-runtime-card__title {
  color: #16324a;
  font-size: 14px;
  font-weight: 700;
}

.service-runtime-card__desc {
  margin-top: 8px;
  color: #3c5268;
  font-size: 13px;
  line-height: 1.7;
}

.service-runtime-card__meta {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 10px;
}

.service-runtime-card__tag {
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  padding: 0 10px;
  border-radius: 999px;
  background: rgba(28, 92, 136, 0.08);
  color: #1c5c88;
  font-size: 12px;
  font-weight: 600;
}

.service-runtime-card__risk {
  margin-top: 10px;
  color: #9a3412;
  font-size: 12px;
  line-height: 1.7;
}

.form-help {
  margin-top: 6px;
  color: #6b7280;
  font-size: 12px;
  line-height: 1.5;
}

.avatar-text-container {
  display: flex;
  align-items: center;
  gap: 6px;
  overflow: hidden;
  max-width: 100%;
  line-height: 1.4;
}

.avatar-text-container .el-avatar {
  flex: 0 0 auto;
  width: 28px;
  height: 28px;
}

.platform-title {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  flex: 1;
}

@media (max-width: 1200px) {
  .page-toolbar,
  .filter-summary-bar,
  .order-followup-panel,
  .order-plain-guide__header {
    flex-direction: column;
    align-items: stretch;
  }

  .order-followup-panel__actions {
    justify-content: flex-start;
  }

  .order-plain-guide__badge {
    min-width: 0;
  }

  .order-plain-guide__grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .query-search-inline {
    flex-wrap: wrap;
  }

  .query-search-inline .el-button {
    flex: 1;
  }
}
</style>
