<template>
  <div v-loading="loading" class="ledger-page">
    <section class="hero">
      <div>
        <p class="eyebrow">财务来源快照</p>
        <h1>商家采购对账</h1>
        <p class="hero__desc">
          按支付审核成功时的商品来源统计，区分商家买平台多少、买其他商家多少。
        </p>
      </div>
      <el-button type="primary" @click="handleExport">导出明细 CSV</el-button>
    </section>

    <section class="filters">
      <el-select
        v-model="query.quick_date"
        class="filter-item"
        placeholder="快捷日期"
        @change="reload"
      >
        <el-option
          v-for="item in filterOptions.quick_dates"
          :key="item.value"
          :label="item.label"
          :value="item.value"
        />
      </el-select>
      <el-date-picker
        v-model="dateRange"
        class="filter-date"
        type="daterange"
        value-format="YYYY-MM-DD"
        start-placeholder="开始日期"
        end-placeholder="结束日期"
        @change="handleDateChange"
      />
      <el-select
        v-model="query.buyer_merchant_id"
        clearable
        filterable
        class="filter-item"
        placeholder="买方商家"
        @change="reload"
      >
        <el-option
          v-for="item in filterOptions.buyer_merchants"
          :key="item.value"
          :label="item.label"
          :value="item.value"
        />
      </el-select>
      <el-select
        v-model="query.source_type"
        clearable
        class="filter-item"
        placeholder="来源类型"
        @change="reload"
      >
        <el-option
          v-for="item in filterOptions.source_types"
          :key="item.value"
          :label="item.label"
          :value="item.value"
        />
      </el-select>
      <el-select
        v-model="query.source_merchant_id"
        clearable
        filterable
        class="filter-item"
        placeholder="来源商家/平台"
        @change="reload"
      >
        <el-option
          v-for="item in filterOptions.source_merchants"
          :key="item.value"
          :label="item.label"
          :value="item.value"
        />
      </el-select>
      <el-input
        v-model="query.order_no"
        clearable
        class="filter-order"
        placeholder="订单编号"
        @keyup.enter="reload"
      />
      <el-input
        v-model="query.keyword"
        clearable
        class="filter-keyword"
        placeholder="商品/商家"
        @keyup.enter="reload"
      />
      <el-button @click="reload">查询</el-button>
    </section>

    <section class="cards">
      <article class="card card--dark">
        <span>采购总额</span>
        <strong>¥{{ money(summaryData.cards.total_amount) }}</strong>
        <small>{{ summaryData.query_label || '当前范围' }}</small>
      </article>
      <article class="card">
        <span>买平台商品</span>
        <strong>¥{{ money(summaryData.cards.platform_amount) }}</strong>
        <small>平台收款码来源</small>
      </article>
      <article class="card">
        <span>买其他商家</span>
        <strong>¥{{ money(summaryData.cards.merchant_amount) }}</strong>
        <small>商家供货来源</small>
      </article>
      <article class="card">
        <span>订单 / 件数</span>
        <strong>{{ summaryData.cards.order_count }} / {{ summaryData.cards.quantity }}</strong>
        <small>按流水明细汇总</small>
      </article>
    </section>

    <section class="cards cards--reconcile">
      <article class="card">
        <span>核算订单</span>
        <strong>{{ summaryData.reconciliation.cards.order_count }}</strong>
        <small>按订单自动比对</small>
      </article>
      <article class="card card--ok">
        <span>核算正常</span>
        <strong>{{ summaryData.reconciliation.cards.normal_count }}</strong>
        <small>流水、订单、账单一致</small>
      </article>
      <article class="card card--warn">
        <span>异常订单</span>
        <strong>{{ summaryData.reconciliation.cards.exception_count }}</strong>
        <small>需要财务复核</small>
      </article>
      <article class="card card--danger">
        <span>异常差额</span>
        <strong>¥{{ money(summaryData.reconciliation.cards.exception_amount) }}</strong>
        <small>按差异绝对值汇总</small>
      </article>
    </section>

    <section class="reconcile-types">
      <article
        v-for="item in reconcileTypeCards"
        :key="item.key"
        class="type-card"
        :class="{ 'type-card--active': query.reconciliation_status === item.key }"
        @click="selectReconcileType(item.key)"
      >
        <div>
          <span>{{ item.title }}</span>
          <strong>{{ item.count }}</strong>
        </div>
        <small>{{ item.desc }}</small>
        <em>差额 ¥{{ money(item.amount) }}，点击查看明细</em>
      </article>
    </section>

    <section class="split">
      <div class="panel">
        <div class="panel__title">买方商家拆账</div>
        <el-table :data="summaryData.buyer_rank" border>
          <el-table-column prop="buyer_merchant_title" label="买方商家" min-width="140" />
          <el-table-column label="买平台" width="120">
            <template #default="{ row }">¥{{ money(row.platform_amount) }}</template>
          </el-table-column>
          <el-table-column label="买商家" width="120">
            <template #default="{ row }">¥{{ money(row.merchant_amount) }}</template>
          </el-table-column>
          <el-table-column label="合计" width="120">
            <template #default="{ row }">¥{{ money(row.total_amount) }}</template>
          </el-table-column>
        </el-table>
      </div>
      <div class="panel">
        <div class="panel__title">供货来源排行</div>
        <el-table :data="summaryData.source_rank" border>
          <el-table-column prop="source_merchant_title" label="来源" min-width="140" />
          <el-table-column prop="source_type_title" label="类型" width="100" />
          <el-table-column label="金额" width="120">
            <template #default="{ row }">¥{{ money(row.amount) }}</template>
          </el-table-column>
          <el-table-column prop="order_count" label="订单" width="90" />
        </el-table>
      </div>
    </section>

    <section class="panel">
      <div class="panel__heading">
        <div>
          <div class="panel__title">商家买卖对比</div>
          <p class="panel__hint">
            按商家/平台汇总：我买别人、别人买我、差额和买卖比；点击金额或差额可直接查看对应明细。
          </p>
        </div>
      </div>
      <el-table
        :data="summaryData.merchant_trade_compare"
        border
        empty-text="当前筛选范围内没有买卖对比数据"
      >
        <el-table-column label="对象" min-width="160">
          <template #default="{ row }">
            <span>{{ row.merchant_title }}</span>
            <el-tag
              v-if="Number(row.merchant_id) === 0"
              class="object-tag"
              size="small"
              type="info"
            >
              平台
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="我买别人" width="130">
          <template #default="{ row }">
            <el-button
              link
              type="primary"
              :disabled="Number(row.buy_order_count) <= 0"
              @click="selectCompareMerchant(row, 'buy')"
            >
              ¥{{ money(row.buy_amount) }}
            </el-button>
          </template>
        </el-table-column>
        <el-table-column label="别人买我" width="130">
          <template #default="{ row }">
            <el-button
              link
              type="primary"
              :disabled="Number(row.sell_order_count) <= 0"
              @click="selectCompareMerchant(row, 'sell')"
            >
              ¥{{ money(row.sell_amount) }}
            </el-button>
          </template>
        </el-table-column>
        <el-table-column label="差额" width="130">
          <template #default="{ row }">
            <button
              class="diff-link"
              :class="Number(row.net_amount) >= 0 ? 'amount-buy' : 'amount-sell'"
              :disabled="Math.abs(Number(row.net_amount || 0)) <= 0"
              @click="openDiffOrders(row)"
            >
              ¥{{ money(row.net_amount) }}
            </button>
          </template>
        </el-table-column>
        <el-table-column label="买卖比" width="110">
          <template #default="{ row }">{{ ratioText(row.trade_ratio) }}</template>
        </el-table-column>
        <el-table-column label="买入订单" width="100">
          <template #default="{ row }">
            <el-button
              link
              type="primary"
              :disabled="Number(row.buy_order_count) <= 0"
              @click="selectCompareMerchant(row, 'buy')"
            >
              {{ row.buy_order_count }}
            </el-button>
          </template>
        </el-table-column>
        <el-table-column label="卖出订单" width="100">
          <template #default="{ row }">
            <el-button
              link
              type="primary"
              :disabled="Number(row.sell_order_count) <= 0"
              @click="selectCompareMerchant(row, 'sell')"
            >
              {{ row.sell_order_count }}
            </el-button>
          </template>
        </el-table-column>
        <el-table-column label="判断" width="130">
          <template #default="{ row }">
            <el-tag :type="tradeTag(row.trade_judgement)">{{ row.trade_judgement }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="130">
          <template #default="{ row }">
            <el-button
              v-if="Math.abs(Number(row.net_amount || 0)) > 0"
              link
              type="primary"
              @click="openDiffOrders(row)"
            >
              查看差额订单
            </el-button>
            <el-button
              v-else
              link
              type="primary"
              @click="selectCompareMerchant(row, Number(row.merchant_id) === 0 ? 'sell' : 'buy')"
            >
              {{ Number(row.merchant_id) === 0 ? '看售出明细' : '看采购明细' }}
            </el-button>
          </template>
        </el-table-column>
      </el-table>
    </section>

    <el-dialog
      v-model="diffDialog.visible"
      :title="diffDialog.title"
      width="1180px"
      destroy-on-close
    >
      <div v-loading="diffDialog.loading" class="diff-dialog">
        <div class="diff-dialog__summary">
          <div>
            <span>差额</span>
            <strong>¥{{ money(diffDialog.target_amount) }}</strong>
          </div>
          <p>{{ diffDialog.message }}</p>
        </div>
        <div class="diff-dialog__block">
          <div class="diff-dialog__block-head">
            <strong>商品差额定位</strong>
            <span>{{ diffDialog.goods_gap_message || '只按采购流水对比买入和卖出，库存/商品销量不参与财务差额判断。' }}</span>
          </div>
          <el-table
            :data="diffDialog.goods_gaps"
            border
            empty-text="没有找到商品维度差额"
          >
            <el-table-column label="商品" min-width="220">
              <template #default="{ row }">
                <div class="goods-cell">
                  <strong>{{ row.goods_title || '--' }}</strong>
                  <small v-if="row.goods_code">编码：{{ row.goods_code }}</small>
                  <small v-if="row.goods_spec || row.goods_unit">
                    {{ row.goods_spec || '无规格' }} / {{ row.goods_unit || '无单位' }}
                  </small>
                </div>
              </template>
            </el-table-column>
            <el-table-column label="流水买入" width="110">
              <template #default="{ row }">¥{{ money(row.buy_amount) }}</template>
            </el-table-column>
            <el-table-column label="流水卖出" width="110">
              <template #default="{ row }">¥{{ money(row.sell_amount) }}</template>
            </el-table-column>
            <el-table-column label="差额" width="110">
              <template #default="{ row }">
                <strong class="amount-buy">¥{{ money(row.diff_amount) }}</strong>
              </template>
            </el-table-column>
            <el-table-column label="流水件数" width="120">
              <template #default="{ row }">
                {{ row.buy_quantity || 0 }} / {{ row.sell_quantity || 0 }}
                <small class="cell-note">差 {{ row.diff_quantity || 0 }}</small>
              </template>
            </el-table-column>
            <el-table-column label="商品表状态" width="130">
              <template #default="{ row }">
                <div>{{ row.goods_status_title || '未知' }}</div>
                <small class="cell-note">{{ row.goods_disable_title || '未知' }}，仅参考</small>
              </template>
            </el-table-column>
            <el-table-column label="疑似差额订单" min-width="175">
              <template #default="{ row }">
                <small class="cell-note">{{ diffOrderButtonText(row) }}</small>
                <div v-if="orderNoList(row.diff_order_nos).length" class="order-no-list">
                  <el-button
                    v-for="orderNo in visibleOrderNos(row.diff_order_nos)"
                    :key="orderNo"
                    link
                    type="primary"
                    @click="goToOrderNo(orderNo)"
                  >
                    {{ orderNo }}
                  </el-button>
                </div>
                <small v-if="extraOrderNoCount(row.diff_order_nos) > 0" class="order-nos">
                  还有 {{ extraOrderNoCount(row.diff_order_nos) }} 单未展开
                </small>
                <small v-if="!orderNoList(row.diff_order_nos).length" class="order-nos">暂无订单号</small>
                <small v-if="row.diff_order_message" class="cell-note">
                  {{ row.diff_order_message }}
                </small>
              </template>
            </el-table-column>
            <el-table-column label="买入订单" min-width="155">
              <template #default="{ row }">
                <el-button
                  link
                  type="primary"
                  :disabled="!firstOrderNo(row.buy_order_nos)"
                  @click="goToOrderNo(firstOrderNo(row.buy_order_nos))"
                >
                  买入 {{ row.buy_order_count || 0 }} 单
                </el-button>
                <small class="order-nos">{{ shortOrderNos(row.buy_order_nos) }}</small>
              </template>
            </el-table-column>
            <el-table-column label="卖出订单" min-width="155">
              <template #default="{ row }">
                <el-button
                  link
                  type="primary"
                  :disabled="!firstOrderNo(row.sell_order_nos)"
                  @click="goToOrderNo(firstOrderNo(row.sell_order_nos))"
                >
                  卖出 {{ row.sell_order_count || 0 }} 单
                </el-button>
                <small class="order-nos">{{ shortOrderNos(row.sell_order_nos) }}</small>
              </template>
            </el-table-column>
          </el-table>
        </div>
        <div class="diff-dialog__block">
          <div class="diff-dialog__block-head">
            <strong>订单金额匹配</strong>
            <span>如果差额刚好等于一笔或几笔订单金额，这里会直接列出来。</span>
          </div>
          <el-table
            :data="diffDisplayOrders"
            border
            empty-text="没有找到疑似差额订单"
          >
            <el-table-column prop="pay_time" label="支付时间" width="165" />
            <el-table-column label="订单号" width="170">
              <template #default="{ row }">
                <el-button link type="primary" @click="goToOrder(row)">
                  {{ row.order_no || '--' }}
                </el-button>
              </template>
            </el-table-column>
            <el-table-column label="匹配金额" width="120">
              <template #default="{ row }">¥{{ money(row.amount) }}</template>
            </el-table-column>
            <el-table-column
              v-if="diffDialog.match_type === 'near'"
              label="距差额"
              width="110"
            >
              <template #default="{ row }">¥{{ money(row.diff_to_target) }}</template>
            </el-table-column>
            <el-table-column prop="buyer_merchant_title" label="买方商家" min-width="140" />
            <el-table-column prop="source_type_title" label="来源类型" width="120" />
            <el-table-column prop="source_merchant_title" label="来源名称" min-width="140" />
            <el-table-column prop="pay_type_title" label="支付方式" width="100" />
            <el-table-column label="支付状态" width="110">
              <template #default="{ row }">
                <el-tag :type="payStatusTag(row.pay_status)">{{ row.pay_status_title }}</el-tag>
              </template>
            </el-table-column>
            <el-table-column prop="order_status_title" label="订单状态" width="100" />
            <el-table-column label="操作" width="110" fixed="right">
              <template #default="{ row }">
                <el-button link type="primary" @click="goToOrder(row)">核对订单</el-button>
              </template>
            </el-table-column>
          </el-table>
        </div>
      </div>
      <template #footer>
        <el-button @click="diffDialog.visible = false">关闭</el-button>
        <el-button type="primary" @click="applyDiffToLedger">在明细流水中查看</el-button>
      </template>
    </el-dialog>

    <section ref="detailPanelRef" class="panel">
      <div class="panel__heading">
        <div>
          <div class="panel__title">商家异常汇总</div>
          <p class="panel__hint">
            {{ selectedReconcileLabel }}：先按商家汇总总差额，点击商家后再看该商家的订单明细。
          </p>
        </div>
        <div class="panel__actions">
          <span class="pay-mix">
            凭证异常 {{ summaryData.reconciliation.cards.voucher_exception_count || 0 }} 单 /
            微信异常 {{ summaryData.reconciliation.cards.wechat_exception_count || 0 }} 单
          </span>
          <el-button
            v-if="query.reconciliation_status"
            size="small"
            @click="selectReconcileType('')"
          >
            查看全部异常
          </el-button>
          <el-tag
            :type="summaryData.reconciliation.cards.exception_count > 0 ? 'danger' : 'success'"
            effect="dark"
          >
            {{ summaryData.reconciliation.cards.exception_count > 0 ? '有异常' : '全部正常' }}
          </el-tag>
        </div>
      </div>
      <el-table
        :data="summaryData.reconciliation.merchant_list"
        border
        empty-text="当前筛选范围内没有商家异常"
        row-class-name="merchant-row"
        @row-click="selectBuyerMerchant"
      >
        <el-table-column prop="buyer_merchant_title" label="买方商家" min-width="160" />
        <el-table-column prop="exception_order_count" label="异常订单" width="100" />
        <el-table-column prop="detail_count" label="明细数" width="90" />
        <el-table-column label="采购流水合计" width="130">
          <template #default="{ row }">¥{{ money(row.ledger_amount) }}</template>
        </el-table-column>
        <el-table-column label="核算实付合计" width="130">
          <template #default="{ row }">¥{{ money(row.order_pay_price) }}</template>
        </el-table-column>
        <el-table-column label="账单金额合计" width="130">
          <template #default="{ row }">¥{{ money(row.bill_amount) }}</template>
        </el-table-column>
        <el-table-column label="总差额" width="130">
          <template #default="{ row }">¥{{ money(row.diff_amount) }}</template>
        </el-table-column>
        <el-table-column label="操作" width="110">
          <template #default>
            <el-button link type="primary">查看订单</el-button>
          </template>
        </el-table-column>
      </el-table>
    </section>

    <section class="panel">
      <div class="panel__heading">
        <div>
          <div class="panel__title">核算异常订单</div>
          <p class="panel__hint">
            {{ selectedReconcileLabel }}：系统自动检查采购流水、核算实付、购买商品账单是否一致。
          </p>
        </div>
      </div>
      <el-table
        :data="summaryData.reconciliation.exception_list"
        border
        empty-text="当前筛选范围内没有核算异常"
      >
        <el-table-column prop="order_no" label="订单号" width="170" />
        <el-table-column prop="buyer_merchant_title" label="买方商家" min-width="140" />
        <el-table-column label="核算结果" width="110">
          <template #default="{ row }">
            <el-tag :type="statusTag(row.reconcile_status)">{{
              row.reconcile_status_title
            }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column
          prop="reconcile_message"
          label="系统说明"
          min-width="220"
          show-overflow-tooltip
        />
        <el-table-column prop="pay_type_title" label="支付方式" width="100" />
        <el-table-column label="支付状态" width="120">
          <template #default="{ row }">
            <el-tag :type="payStatusTag(row.pay_status)">{{ row.pay_status_title }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="order_status_title" label="订单状态" width="100" />
        <el-table-column label="采购流水" width="120">
          <template #default="{ row }">¥{{ money(row.ledger_amount) }}</template>
        </el-table-column>
        <el-table-column label="核算实付" width="140">
          <template #default="{ row }">
            <div>¥{{ money(row.order_pay_price) }}</div>
            <small v-if="row.order_pay_price_source_title" class="cell-note">
              {{ row.order_pay_price_source_title }}
            </small>
          </template>
        </el-table-column>
        <el-table-column label="账单金额" width="120">
          <template #default="{ row }">¥{{ money(row.bill_amount) }}</template>
        </el-table-column>
        <el-table-column label="差额" width="120">
          <template #default="{ row }">¥{{ money(row.reconcile_diff_amount) }}</template>
        </el-table-column>
        <el-table-column label="操作" width="110" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" @click="goToOrder(row)">核对订单</el-button>
          </template>
        </el-table-column>
      </el-table>
    </section>

    <section ref="ledgerPanelRef" class="panel">
      <div class="panel__title">采购明细流水</div>
      <div v-if="activeCompareDrill" class="compare-drill-tip">
        <div>
          <strong>{{ activeCompareDrill.title }}</strong>
          <span>{{ activeCompareDrill.desc }}</span>
        </div>
        <el-button size="small" @click="clearCompareDrill">清除差额定位</el-button>
      </div>
      <el-table :data="rows" border>
        <el-table-column prop="pay_time" label="支付时间" width="170" />
        <el-table-column label="订单号" width="170">
          <template #default="{ row }">
            <el-button link type="primary" @click="goToOrder(row)">
              {{ row.order_no || '--' }}
            </el-button>
          </template>
        </el-table-column>
        <el-table-column label="核算" width="110">
          <template #default="{ row }">
            <el-tag :type="statusTag(row.reconcile_status)">{{
              row.reconcile_status_title
            }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column
          prop="reconcile_message"
          label="核算说明"
          min-width="180"
          show-overflow-tooltip
        />
        <el-table-column prop="buyer_merchant_title" label="买方商家" min-width="130" />
        <el-table-column prop="source_type_title" label="来源类型" width="110" />
        <el-table-column prop="pay_type_title" label="支付方式" width="100" />
        <el-table-column label="支付状态" width="120">
          <template #default="{ row }">
            <el-tag :type="payStatusTag(row.pay_status)">{{ row.pay_status_title }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="source_merchant_title" label="来源名称" min-width="130" />
        <el-table-column prop="goods_title" label="商品" min-width="220" show-overflow-tooltip />
        <el-table-column prop="quantity" label="数量" width="80" />
        <el-table-column label="单价" width="100">
          <template #default="{ row }">¥{{ money(row.price) }}</template>
        </el-table-column>
        <el-table-column label="明细金额" width="120">
          <template #default="{ row }">¥{{ money(row.total) }}</template>
        </el-table-column>
        <el-table-column label="核算实付" width="140">
          <template #default="{ row }">
            <div>¥{{ money(row.order_current_pay_price) }}</div>
            <small v-if="row.order_pay_price_source_title" class="cell-note">
              {{ row.order_pay_price_source_title }}
            </small>
          </template>
        </el-table-column>
        <el-table-column label="账单金额" width="120">
          <template #default="{ row }">¥{{ money(row.bill_amount) }}</template>
        </el-table-column>
        <el-table-column label="差额" width="120">
          <template #default="{ row }">¥{{ money(row.reconcile_diff_amount) }}</template>
        </el-table-column>
        <el-table-column label="操作" width="110" fixed="right">
          <template #default="{ row }">
            <el-button link type="primary" @click="goToOrder(row)">核对订单</el-button>
          </template>
        </el-table-column>
      </el-table>
      <div class="pager">
        <el-pagination
          v-model:current-page="page"
          v-model:page-size="limit"
          background
          layout="total, sizes, prev, pager, next"
          :total="total"
          @current-change="loadList"
          @size-change="loadList"
        />
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import {
  downloadLedger,
  filters,
  list,
  summary,
  tradeDiffOrders
} from '@/api/report/merchant-purchase-ledger'

const router = useRouter()
const loading = ref(false)
const dateRange = ref([])
const page = ref(1)
const limit = ref(20)
const total = ref(0)
const rows = ref([])
const detailPanelRef = ref(null)
const ledgerPanelRef = ref(null)
const activeCompareDrill = ref(null)
const diffDialog = reactive({
  visible: false,
  loading: false,
  title: '查看差额订单',
  message: '',
  match_type: 'none',
  target_amount: 0,
  orders: [],
  candidate_orders: [],
  goods_gaps: [],
  goods_gap_match_type: 'none',
  goods_gap_message: '',
  row: null
})

const query = reactive({
  quick_date: 'all',
  start_date: '',
  end_date: '',
  buyer_merchant_id: undefined,
  source_type: '',
  source_merchant_id: undefined,
  order_no: '',
  reconciliation_status: '',
  keyword: ''
})

const filterOptions = reactive({
  quick_dates: [],
  source_types: [],
  buyer_merchants: [],
  source_merchants: []
})

const summaryData = reactive({
  query_label: '',
  cards: {
    total_amount: 0,
    platform_amount: 0,
    merchant_amount: 0,
    order_count: 0,
    quantity: 0
  },
  reconciliation: {
    cards: {
      order_count: 0,
      normal_count: 0,
      exception_count: 0,
      missing_bill_count: 0,
      missing_bill_amount: 0,
      ledger_mismatch_count: 0,
      ledger_mismatch_amount: 0,
      bill_mismatch_count: 0,
      bill_mismatch_amount: 0,
      amount_mismatch_count: 0,
      amount_mismatch_amount: 0,
      exception_amount: 0,
      voucher_exception_count: 0,
      wechat_exception_count: 0
    },
    merchant_list: [],
    exception_list: []
  },
  buyer_rank: [],
  source_rank: [],
  merchant_trade_compare: []
})

function buildParams() {
  return {
    ...query,
    buyer_merchant_id: query.buyer_merchant_id || 0,
    source_merchant_id: query.source_merchant_id ?? -1
  }
}

const reconcileTypeCards = computed(() => {
  const cards = summaryData.reconciliation.cards
  return [
    {
      key: 'missing_bill',
      title: '缺少账单',
      desc: '订单已付款，但没有找到购买商品账单',
      count: cards.missing_bill_count,
      amount: cards.missing_bill_amount
    },
    {
      key: 'ledger_mismatch',
      title: '流水不一致',
      desc: '采购流水金额和核算实付对不上',
      count: cards.ledger_mismatch_count,
      amount: cards.ledger_mismatch_amount
    },
    {
      key: 'bill_mismatch',
      title: '账单不一致',
      desc: '购买商品账单和核算实付对不上',
      count: cards.bill_mismatch_count,
      amount: cards.bill_mismatch_amount
    },
    {
      key: 'amount_mismatch',
      title: '金额都不一致',
      desc: '采购流水和账单都与核算实付不一致',
      count: cards.amount_mismatch_count,
      amount: cards.amount_mismatch_amount
    }
  ]
})

const selectedReconcileLabel = computed(() => {
  const selected = reconcileTypeCards.value.find((item) => item.key === query.reconciliation_status)
  return selected ? selected.title : '全部异常'
})

const diffDisplayOrders = computed(() => {
  return diffDialog.orders.length ? diffDialog.orders : diffDialog.candidate_orders
})

function firstOrderNo(value) {
  return orderNoList(value)[0] || ''
}

function orderNoList(value) {
  return String(value || '')
    .split(/[、,，\s]+/)
    .map((item) => item.trim())
    .filter(Boolean)
}

function visibleOrderNos(value) {
  return orderNoList(value).slice(0, 3)
}

function extraOrderNoCount(value) {
  return Math.max(orderNoList(value).length - 3, 0)
}

function shortOrderNos(value) {
  const text = String(value || '')
  if (!text) return '暂无订单号'
  return text.length > 34 ? `${text.slice(0, 34)}...` : text
}

function diffOrderButtonText(row) {
  const count = String(row.diff_order_nos || '')
    .split(/[、,，\s]+/)
    .filter(Boolean).length
  if (!count) return '暂无匹配'
  if (row.diff_order_match_type === 'single') return `单笔匹配 ${count} 单`
  if (row.diff_order_match_type === 'combination') return `合计匹配 ${count} 单`
  return `接近订单 ${count} 单`
}

function money(value) {
  return Number(value || 0).toFixed(2)
}

function statusTag(status) {
  if (status === 'normal') return 'success'
  if (status === 'missing_bill') return 'warning'
  return 'danger'
}

function payStatusTag(status) {
  if (Number(status) === 1) return 'success'
  if (Number(status) === 2) return 'danger'
  return 'warning'
}

function ratioText(value) {
  return value === null || value === undefined ? '--' : `${Number(value).toFixed(1)}%`
}

function tradeTag(title) {
  if (title === '买卖基本持平') return 'success'
  if (title === '暂无买卖') return 'info'
  return 'warning'
}

function handleDateChange(value) {
  query.quick_date = ''
  query.start_date = value?.[0] || ''
  query.end_date = value?.[1] || ''
  reload()
}

function selectReconcileType(status) {
  query.reconciliation_status = status
  reload()
}

function selectBuyerMerchant(row) {
  query.buyer_merchant_id = row.buyer_merchant_id || undefined
  activeCompareDrill.value = null
  reload()
}

function scrollToDetails() {
  setTimeout(() => {
    detailPanelRef.value?.scrollIntoView?.({ behavior: 'smooth', block: 'start' })
  }, 80)
}

function scrollToLedgerDetails() {
  setTimeout(() => {
    ledgerPanelRef.value?.scrollIntoView?.({ behavior: 'smooth', block: 'start' })
  }, 80)
}

function clearCompareFilters() {
  query.buyer_merchant_id = undefined
  query.source_merchant_id = undefined
  query.source_type = ''
}

function buildCompareDrillTip(row, type) {
  const merchantTitle = row.merchant_title || `商家 #${row.merchant_id || '--'}`
  const isSell = type === 'sell'
  return {
    title: `正在定位：${merchantTitle} · ${isSell ? '别人买我' : '我买别人'}`,
    desc: isSell
      ? '下方采购明细流水已筛出“该对象作为供货来源”的订单。'
      : '下方采购明细流水已筛出“该对象作为买方商家”的订单。'
  }
}

function selectCompareMerchant(row, type = 'buy') {
  const merchantId = Number(row.merchant_id || 0)
  const nextType = type === 'diff' ? (Number(row.net_amount || 0) >= 0 ? 'buy' : 'sell') : type

  clearCompareFilters()
  if (nextType === 'sell') {
    query.source_merchant_id = merchantId
    if (merchantId === 0) {
      query.source_type = 'platform'
    }
  } else {
    query.buyer_merchant_id = merchantId || undefined
  }
  activeCompareDrill.value = buildCompareDrillTip(row, nextType)
  page.value = 1
  reload()
  scrollToLedgerDetails()
}

async function openDiffOrders(row) {
  const netAmount = Number(row.net_amount || 0)
  if (Math.abs(netAmount) <= 0) return

  const direction = netAmount >= 0 ? 'buy' : 'sell'
  diffDialog.visible = true
  diffDialog.loading = true
  diffDialog.row = row
  diffDialog.title = `${row.merchant_title || '商家'} · 查看差额订单`
  diffDialog.target_amount = Math.abs(netAmount)
  diffDialog.match_type = 'none'
  diffDialog.message = '正在按差额金额匹配订单...'
  diffDialog.orders = []
  diffDialog.candidate_orders = []
  diffDialog.goods_gaps = []
  diffDialog.goods_gap_match_type = 'none'
  diffDialog.goods_gap_message = ''

  try {
    const res = await tradeDiffOrders({
      ...buildParams(),
      merchant_id: Number(row.merchant_id || 0),
      direction,
      target_amount: Math.abs(netAmount)
    })
    const data = res.data || {}
    diffDialog.message = data.message || '系统已按差额金额完成匹配'
    diffDialog.match_type = data.match_type || 'none'
    diffDialog.target_amount = data.target_amount || Math.abs(netAmount)
    diffDialog.orders = data.orders || []
    diffDialog.candidate_orders = data.candidate_orders || []
    diffDialog.goods_gaps = data.goods_gaps || []
    diffDialog.goods_gap_match_type = data.goods_gap_match_type || 'none'
    diffDialog.goods_gap_message = data.goods_gap_message || ''
  } finally {
    diffDialog.loading = false
  }
}

function applyDiffToLedger() {
  if (!diffDialog.row) return
  diffDialog.visible = false
  selectCompareMerchant(diffDialog.row, 'diff')
}

function clearCompareDrill() {
  clearCompareFilters()
  activeCompareDrill.value = null
  page.value = 1
  reload()
  scrollToLedgerDetails()
}

function goToOrder(row) {
  router.push({
    path: '/order/order',
    query: {
      from: 'merchant-purchase-ledger',
      search_field: 'order_no',
      search_exp: 'like',
      search_value: row.order_no || ''
    }
  })
}

function goToOrderNo(orderNo) {
  if (!orderNo) return
  goToOrder({ order_no: orderNo })
}

async function loadFilters() {
  const res = await filters()
  Object.assign(filterOptions, res.data || {})
}

async function loadSummary() {
  const res = await summary(buildParams())
  Object.assign(summaryData, res.data || {})
}

async function loadList() {
  const res = await list({
    ...buildParams(),
    page: page.value,
    limit: limit.value
  })
  rows.value = res.data?.list || []
  total.value = res.data?.count || 0
}

async function reload() {
  loading.value = true
  try {
    page.value = 1
    await Promise.all([loadSummary(), loadList()])
  } finally {
    loading.value = false
  }
}

async function handleExport() {
  await downloadLedger(buildParams())
}

onMounted(async () => {
  loading.value = true
  try {
    await loadFilters()
    await Promise.all([loadSummary(), loadList()])
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.ledger-page {
  padding: 24px;
  background: linear-gradient(135deg, #f7f3e8 0%, #edf5ef 46%, #f7faf9 100%);
  min-height: 100%;
}

.hero,
.filters,
.panel,
.card {
  border: 1px solid rgba(39, 55, 44, 0.12);
  box-shadow: 0 18px 45px rgba(36, 52, 42, 0.08);
}

.hero {
  display: flex;
  justify-content: space-between;
  gap: 20px;
  align-items: center;
  padding: 28px;
  border-radius: 28px;
  color: #f8f3e6;
  background: radial-gradient(circle at 20% 20%, rgba(241, 195, 89, 0.35), transparent 30%),
    linear-gradient(135deg, #21392f, #0e1f1b);
}

.eyebrow {
  margin: 0 0 8px;
  letter-spacing: 0.18em;
  font-size: 12px;
  color: #f1c359;
}

.hero h1 {
  margin: 0;
  font-size: 32px;
}

.hero__desc {
  margin: 10px 0 0;
  opacity: 0.82;
}

.filters {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  padding: 16px;
  margin: 18px 0;
  border-radius: 20px;
  background: rgba(255, 255, 255, 0.78);
}

.filter-item {
  width: 170px;
}

.filter-date {
  width: 260px;
}

.filter-order {
  width: 190px;
}

.filter-keyword {
  width: 220px;
}

.cards {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 16px;
}

.cards--reconcile {
  margin-top: 16px;
}

.reconcile-types {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
  margin-top: 14px;
}

.type-card {
  padding: 16px;
  border: 1px solid rgba(39, 55, 44, 0.1);
  border-radius: 18px;
  background: rgba(255, 255, 255, 0.78);
  box-shadow: 0 12px 32px rgba(36, 52, 42, 0.06);
  cursor: pointer;
  transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
}

.type-card:hover,
.type-card--active {
  border-color: rgba(165, 65, 46, 0.45);
  box-shadow: 0 16px 36px rgba(165, 65, 46, 0.12);
  transform: translateY(-2px);
}

.type-card div {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  align-items: center;
}

.type-card span {
  color: #20372e;
  font-weight: 700;
}

.type-card strong {
  color: #a5412e;
  font-size: 24px;
}

.type-card small,
.type-card em {
  display: block;
  margin-top: 8px;
  color: #6c766f;
  font-style: normal;
}

.card {
  padding: 20px;
  border-radius: 22px;
  background: rgba(255, 255, 255, 0.86);
}

.card--dark {
  color: #f8f3e6;
  background: linear-gradient(145deg, #8a5b26, #1f372d);
}

.card--ok {
  background: linear-gradient(145deg, rgba(224, 244, 230, 0.94), rgba(255, 255, 255, 0.9));
}

.card--warn {
  background: linear-gradient(145deg, rgba(255, 241, 205, 0.94), rgba(255, 255, 255, 0.9));
}

.card--danger {
  background: linear-gradient(145deg, rgba(255, 224, 216, 0.94), rgba(255, 255, 255, 0.9));
}

.card span,
.card small {
  display: block;
  opacity: 0.72;
}

.card strong {
  display: block;
  margin: 10px 0;
  font-size: 26px;
}

.split {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
  margin-top: 18px;
}

.panel {
  padding: 18px;
  border-radius: 22px;
  margin-top: 18px;
  background: rgba(255, 255, 255, 0.86);
}

.panel__heading {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  align-items: flex-start;
  margin-bottom: 14px;
}

.panel__actions {
  display: flex;
  gap: 10px;
  align-items: center;
}

.pay-mix {
  color: #6c766f;
  font-size: 13px;
  white-space: nowrap;
}

.amount-buy {
  color: #a5412e;
  font-weight: 700;
}

.amount-sell {
  color: #1f6f50;
  font-weight: 700;
}

.diff-link:disabled {
  color: #9aa69f;
  cursor: not-allowed;
}

.diff-dialog__summary {
  display: flex;
  align-items: center;
  gap: 18px;
  margin-bottom: 14px;
  padding: 14px 16px;
  border: 1px solid rgba(165, 65, 46, 0.16);
  border-radius: 16px;
  background: linear-gradient(135deg, rgba(255, 247, 232, 0.96), rgba(255, 255, 255, 0.92));
}

.diff-dialog__summary span,
.diff-dialog__summary strong {
  display: block;
}

.diff-dialog__summary span {
  color: #6c766f;
  font-size: 12px;
}

.diff-dialog__summary strong {
  margin-top: 2px;
  color: #a5412e;
  font-size: 22px;
}

.diff-dialog__summary p {
  margin: 0;
  color: #20372e;
}

.diff-dialog__block {
  margin-top: 14px;
}

.diff-dialog__block-head {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  align-items: center;
  margin-bottom: 10px;
}

.diff-dialog__block-head strong {
  color: #20372e;
}

.diff-dialog__block-head span {
  color: #6c766f;
  font-size: 13px;
}

.goods-cell strong,
.goods-cell small,
.order-nos {
  display: block;
}

.goods-cell strong {
  color: #20372e;
}

.goods-cell small,
.order-nos {
  margin-top: 3px;
  color: #84928a;
  font-size: 12px;
  line-height: 1.35;
}

.order-no-list {
  display: flex;
  flex-wrap: wrap;
  gap: 2px 8px;
}

.order-no-list :deep(.el-button) {
  margin-left: 0;
  padding: 0;
  height: auto;
  line-height: 1.35;
}

.split .panel {
  margin-top: 0;
}

.panel__title {
  margin-bottom: 14px;
  font-weight: 700;
  color: #20372e;
}

.panel__hint {
  margin: -6px 0 0;
  color: #6c766f;
}

.cell-note {
  display: block;
  margin-top: 2px;
  color: #84928a;
  font-size: 12px;
}

.compare-drill-tip {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  margin: -4px 0 14px;
  padding: 12px 14px;
  border: 1px solid rgba(31, 111, 80, 0.18);
  border-radius: 14px;
  background: linear-gradient(135deg, rgba(238, 248, 242, 0.95), rgba(255, 255, 255, 0.92));
}

.compare-drill-tip strong,
.compare-drill-tip span {
  display: block;
}

.compare-drill-tip strong {
  color: #1f372d;
}

.compare-drill-tip span {
  margin-top: 4px;
  color: #6c766f;
  font-size: 13px;
}

:deep(.merchant-row) {
  cursor: pointer;
}

.pager {
  display: flex;
  justify-content: flex-end;
  margin-top: 16px;
}

@media (max-width: 1180px) {
  .cards,
  .reconcile-types,
  .split {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 760px) {
  .ledger-page {
    padding: 14px;
  }

  .hero,
  .filters,
  .compare-drill-tip {
    display: block;
  }

  .cards,
  .reconcile-types,
  .split {
    grid-template-columns: 1fr;
  }

  .filter-item,
  .filter-order,
  .filter-date,
  .filter-keyword {
    width: 100%;
    margin-bottom: 10px;
  }
}
</style>
