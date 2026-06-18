<template>
  <div v-loading="loading" class="takeover-page">
    <el-card class="panel panel--hero" shadow="never">
      <div class="panel__header">
        <div>
          <div class="panel__title">内部接盘对账</div>
          <div class="panel__desc">
            这页专门给运营看“内部号接手商品后是不是走顺了”。重点不是技术状态码，而是快速分清：待审核、待转商品、已完成、真正异常。
          </div>
        </div>
        <div class="panel__actions">
          <el-tag effect="plain">{{ queryLabel }}</el-tag>
          <el-tag effect="plain" type="success">实时后端数据</el-tag>
          <el-button :loading="loading" type="primary" @click="reloadAll">刷新数据</el-button>
        </div>
      </div>
      <div v-if="entryContextVisible" class="entry-context-banner">
        <div class="entry-context-banner__main">
          <div class="entry-context-banner__eyebrow">外部入口承接</div>
          <div class="entry-context-banner__title">{{ entryContextTitle }}</div>
          <div class="entry-context-banner__desc">{{ entryContextDesc }}</div>
        </div>
        <div class="entry-context-banner__actions">
          <el-button type="primary" @click="handleEntryContextPrimary">
            {{ entryContextPrimaryLabel }}
          </el-button>
          <el-button @click="goToEntryContextBack">回来源页</el-button>
        </div>
      </div>

      <div class="quick-range">
        <button
          v-for="item in quickDateOptions"
          :key="item.value"
          class="quick-range__item"
          :class="{ 'quick-range__item--active': query.quick_date === item.value && !query.daterange.length }"
          type="button"
          @click="applyQuickRange(item.value)"
        >
          {{ item.label }}
        </button>
      </div>

      <el-form class="filter-form" inline>
        <el-form-item label="下单日期">
          <el-date-picker
            v-model="query.daterange"
            type="daterange"
            value-format="YYYY-MM-DD"
            start-placeholder="开始日期"
            end-placeholder="结束日期"
            style="width: 260px"
          />
        </el-form-item>
        <el-form-item label="接手内部号">
          <el-select v-model="query.internal_merchant_id" clearable filterable style="width: 220px">
            <el-option label="全部内部号" :value="0" />
            <el-option
              v-for="item in filterOptions.internal_merchants"
              :key="item.value"
              :label="item.label"
              :value="item.value"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="原商品归属商家">
          <el-select v-model="query.source_merchant_id" clearable filterable style="width: 220px">
            <el-option label="全部归属商家" :value="0" />
            <el-option
              v-for="item in filterOptions.source_merchants"
              :key="item.value"
              :label="item.label"
              :value="item.value"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="付款审核状态">
          <el-select v-model="query.pay_status" style="width: 150px">
            <el-option
              v-for="item in filterOptions.pay_status_options"
              :key="item.value"
              :label="item.label"
              :value="item.value"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="处理阶段">
          <el-select v-model="query.stage_code" style="width: 150px">
            <el-option
              v-for="item in filterOptions.stage_options"
              :key="item.value"
              :label="item.label"
              :value="item.value"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="关键字">
          <el-input
            v-model="query.keyword"
            clearable
            placeholder="订单号 / 买家手机号 / 昵称"
            style="width: 240px"
            @keyup.enter="applyFilters"
          />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="applyFilters">应用筛选</el-button>
          <el-button @click="resetQuery">重置</el-button>
        </el-form-item>
      </el-form>

      <div class="current-params">
        <div class="current-params__title">当前筛选条件</div>
        <div class="current-params__tags">
          <el-tag v-for="item in activeFilterTags" :key="item" effect="plain">
            {{ item }}
          </el-tag>
          <el-tag v-if="!activeFilterTags.length" type="info" effect="plain">默认条件：近 7 天 / 全部内部号</el-tag>
        </div>
      </div>

      <div class="plain-guide">
        <div class="plain-guide__header">
          <div>
            <div class="plain-guide__title">先把这四种状态看明白</div>
            <div class="plain-guide__desc">先分清它是正常积压，还是已经需要人工处理，再决定要不要继续往详情、内部号或商家页下钻。</div>
          </div>
          <el-tag effect="plain" :type="currentStageGuide.type">{{ currentStageGuide.label }}</el-tag>
        </div>
        <div class="plain-guide__grid">
          <div v-for="item in stageMeaningCards" :key="item.title" class="plain-guide-card">
            <div class="plain-guide-card__title">{{ item.title }}</div>
            <div class="plain-guide-card__desc">{{ item.desc }}</div>
            <div class="plain-guide-card__action">{{ item.action }}</div>
          </div>
        </div>
      </div>

      <div class="followup-strip">
        <button type="button" class="followup-card" @click="goToCurrentInternalMerchant">
          <span class="followup-card__title">去内部商家页核对</span>
          <span class="followup-card__desc">{{ currentInternalMerchantActionText }}</span>
        </button>
        <button type="button" class="followup-card" @click="goToCurrentSourceMerchant">
          <span class="followup-card__title">去原商家页继续处理</span>
          <span class="followup-card__desc">{{ currentSourceMerchantActionText }}</span>
        </button>
        <button type="button" class="followup-card" @click="goToAnalyticsReview">
          <span class="followup-card__title">回平台分析复核</span>
          <span class="followup-card__desc">看完接盘异常后，带着当前时间范围和商家条件回平台分析页复盘整体波动。</span>
        </button>
      </div>

      <div class="quick-start">
        <div class="quick-start__header">
          <div>
            <div class="quick-start__title">如果你只想先看懂一遍，就按这个顺序</div>
            <div class="quick-start__desc">不用一上来就钻详情。先看顶部统计，再缩到某一类，再选内部号，最后看列表和详情。</div>
          </div>
          <el-tag effect="dark" type="warning">当前重点：{{ currentPriorityShort }}</el-tag>
        </div>
        <div class="quick-start__steps">
          <div v-for="item in quickStartSteps" :key="item.title" class="quick-start-step">
            <span>{{ item.step }}</span>
            <strong>{{ item.title }}</strong>
            <em>{{ item.desc }}</em>
          </div>
        </div>
      </div>

      <div class="operator-guide">
        <div class="operator-guide__main">
          <div class="operator-guide__header">
            <div>
              <div class="operator-guide__title">这页怎么用</div>
              <div class="operator-guide__desc">先分清是正常积压还是异常，再顺着详情去找内部号、原商家、商品和会员。</div>
            </div>
            <el-tag effect="plain" :type="currentStageGuide.type">{{ currentStageGuide.label }}</el-tag>
          </div>
          <div class="operator-guide__steps">
            <div v-for="item in operatorGuideSteps" :key="item.title" class="operator-guide-step">
              <span>{{ item.step }}</span>
              <strong>{{ item.title }}</strong>
              <em>{{ item.desc }}</em>
            </div>
          </div>
        </div>
        <div class="operator-guide__aside">
          <div class="operator-guide__aside-title">快速只看某一类</div>
          <div class="operator-guide__aside-actions">
            <el-button :type="query.stage_code === '' ? 'primary' : 'default'" plain @click="clearStageFilter">查看全部</el-button>
            <el-button
              :type="query.stage_code === 'pending_review' ? 'warning' : 'default'"
              :disabled="!summaryCards.pending_review_count"
              plain
              @click="useStage('pending_review')"
            >
              只看待审核
            </el-button>
            <el-button
              :type="query.stage_code === 'pending_transfer' ? 'primary' : 'default'"
              :disabled="!summaryCards.pending_transfer_count"
              plain
              @click="useStage('pending_transfer')"
            >
              只看待转商品
            </el-button>
            <el-button
              :type="query.stage_code === 'exception' ? 'danger' : 'default'"
              :disabled="!summaryCards.exception_count"
              plain
              @click="useStage('exception')"
            >
              只看真正异常
            </el-button>
          </div>
          <div class="operator-guide__aside-note">{{ currentPriorityHint }}</div>
        </div>
      </div>
    </el-card>

    <div class="summary-banner">
      <div class="summary-banner__item">
        <span class="summary-banner__label">总接手单量</span>
        <strong>{{ formatCount(summaryCards.total_count) }}</strong>
      </div>
      <div class="summary-banner__item">
        <span class="summary-banner__label">最优先处理</span>
        <strong>{{ summaryCards.priority_text || '整体正常' }}</strong>
      </div>
      <div class="summary-banner__item">
        <span class="summary-banner__label">正常完成</span>
        <strong>{{ formatCount(summaryCards.completed_count) }}</strong>
      </div>
      <div class="summary-banner__item">
        <span class="summary-banner__label">涉及总金额</span>
        <strong>{{ formatCurrency(summaryCards.total_amount) }}</strong>
      </div>
    </div>

    <div class="metrics-grid">
      <el-card class="metric-card metric-card--amber" shadow="never">
        <div class="metric-card__label">待审核</div>
        <div class="metric-card__value">{{ formatCount(summaryCards.pending_review_count) }}</div>
        <div class="metric-card__meta">买家已传凭证，等平台确认</div>
      </el-card>
      <el-card class="metric-card metric-card--blue" shadow="never">
        <div class="metric-card__label">待转商品</div>
        <div class="metric-card__value">{{ formatCount(summaryCards.pending_transfer_count) }}</div>
        <div class="metric-card__meta">平台已确认收款，等商品转入内部号</div>
      </el-card>
      <el-card class="metric-card metric-card--green" shadow="never">
        <div class="metric-card__label">已完成</div>
        <div class="metric-card__value">{{ formatCount(summaryCards.completed_count) }}</div>
        <div class="metric-card__meta">收款、账单、转入都已闭环</div>
      </el-card>
      <el-card class="metric-card metric-card--red" shadow="never">
        <div class="metric-card__label">真正异常</div>
        <div class="metric-card__value">{{ formatCount(summaryCards.exception_count) }}</div>
        <div class="metric-card__meta">不是正常积压，需要人工核查</div>
      </el-card>
    </div>

    <el-row :gutter="12" class="content-row">
      <el-col :xs="24" :lg="15">
        <el-card class="panel" shadow="never">
          <template #header>
            <div class="panel__header-bar">
              <div>
                <div class="panel__sub-title">内部号健康面板</div>
                <div class="panel__sub-desc">选中某个内部号后，直接告诉运营当前这个号是正常、处理中还是存在风险。</div>
              </div>
              <el-tag :type="healthStatusType">{{ healthPanel.status_tag || '未选择内部号' }}</el-tag>
            </div>
          </template>

          <div v-if="healthPanel.merchant_id" class="health-panel">
            <div class="health-panel__hero">
              <div>
                <div class="health-panel__title">{{ healthPanel.merchant_title }}</div>
                <div class="health-panel__summary">{{ healthPanel.summary_text }}</div>
              </div>
              <div class="health-panel__next">
                <span>现在最该处理</span>
                <strong>{{ healthPanel.next_action }}</strong>
              </div>
            </div>

            <div class="health-reminders">
              <div class="health-reminders__item">
                <span>正常完成</span>
                <strong>{{ formatCount(healthPanel.completed_count) }}</strong>
              </div>
              <div class="health-reminders__item">
                <span>待审核</span>
                <strong>{{ formatCount(healthPanel.pending_review_count) }}</strong>
              </div>
              <div class="health-reminders__item">
                <span>待转商品</span>
                <strong>{{ formatCount(healthPanel.pending_transfer_count) }}</strong>
              </div>
              <div class="health-reminders__item">
                <span>真正异常</span>
                <strong>{{ formatCount(healthPanel.exception_count) }}</strong>
              </div>
            </div>

            <div class="status-grid">
              <div class="status-grid__item">
                <span>资金状态</span>
                <strong>{{ healthPanel.fund_status }}</strong>
              </div>
              <div class="status-grid__item">
                <span>账单状态</span>
                <strong>{{ healthPanel.bill_status }}</strong>
              </div>
              <div class="status-grid__item">
                <span>流转状态</span>
                <strong>{{ healthPanel.transfer_status }}</strong>
              </div>
              <div class="status-grid__item">
                <span>异常状态</span>
                <strong>{{ healthPanel.exception_status }}</strong>
              </div>
            </div>

            <div class="stats-grid">
              <div class="stats-grid__item">
                <span>接手订单数</span>
                <strong>{{ formatCount(healthPanel.order_count) }}</strong>
              </div>
              <div class="stats-grid__item">
                <span>已确认收款订单数</span>
                <strong>{{ formatCount(healthPanel.paid_count) }}</strong>
              </div>
              <div class="stats-grid__item">
                <span>已生成账单订单数</span>
                <strong>{{ formatCount(healthPanel.bill_count) }}</strong>
              </div>
              <div class="stats-grid__item">
                <span>已转到内部号订单数</span>
                <strong>{{ formatCount(healthPanel.transfer_count) }}</strong>
              </div>
              <div class="stats-grid__item">
                <span>订单总金额</span>
                <strong>{{ formatCurrency(healthPanel.total_amount) }}</strong>
              </div>
              <div class="stats-grid__item">
                <span>已确认收款金额</span>
                <strong>{{ formatCurrency(healthPanel.paid_amount) }}</strong>
              </div>
              <div class="stats-grid__item">
                <span>已生成账单金额</span>
                <strong>{{ formatCurrency(healthPanel.bill_amount) }}</strong>
              </div>
              <div class="stats-grid__item">
                <span>资金差额</span>
                <strong :class="{ danger: Number(healthPanel.fund_gap_amount || 0) > 0.01 }">
                  {{ formatCurrency(healthPanel.fund_gap_amount) }}
                </strong>
              </div>
            </div>
          </div>

          <el-empty v-else description="当前筛选范围内暂无内部号健康数据" />
        </el-card>
      </el-col>

      <el-col :xs="24" :lg="9">
        <el-card class="panel" shadow="never">
          <template #header>
            <div class="panel__header-bar">
              <div>
                <div class="panel__sub-title">异常来源追踪</div>
                <div class="panel__sub-desc">把异常再拆成运营更容易消化的三类来源。</div>
              </div>
            </div>
          </template>

          <div class="exception-source-list">
            <button
              v-for="item in exceptionSourceCards"
              :key="item.key"
              type="button"
              class="exception-source"
              :class="{ 'exception-source--active': sourceFilter === item.key }"
              @click="sourceFilter = item.key"
            >
              <span class="exception-source__title">{{ item.title }}</span>
              <strong>{{ formatCount(item.count) }}</strong>
              <em>{{ item.desc }}</em>
            </button>
          </div>

          <div class="helper-box">
            <div class="helper-box__title">快捷查看</div>
            <div class="helper-box__actions">
              <el-button :disabled="!summaryCards.pending_review_count" @click="useStage('pending_review')">查看待审核</el-button>
              <el-button :disabled="!summaryCards.pending_transfer_count" @click="useStage('pending_transfer')">查看待转商品</el-button>
              <el-button :disabled="!summaryCards.exception_count" @click="useStage('exception')">查看真正异常</el-button>
              <el-button :disabled="tableRows.length === 0" @click="useStage('')">查看全部</el-button>
            </div>
          </div>

          <div class="helper-box">
            <div class="helper-box__title">批量动作</div>
            <div class="helper-box__actions helper-box__actions--stacked">
              <el-button :disabled="tableRows.length === 0" @click="goToExportCenter">带条件去导出中心</el-button>
              <el-button :disabled="!query.internal_merchant_id" type="primary" @click="goToInternalMerchant">
                去内部商家页核对承接限制
              </el-button>
            </div>
            <div class="helper-box__note">当前先保留只读核对链路，不在这页直接做批量写操作，避免测试阶段误写正式数据。</div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <el-card class="panel table-panel" shadow="never">
      <template #header>
        <div class="panel__header-bar">
          <div>
            <div class="panel__sub-title">接盘订单列表</div>
            <div class="panel__sub-desc">按“待审核 / 待转商品 / 已完成 / 真正异常”直接看订单处理状态。</div>
          </div>
          <el-tag effect="plain">当前 {{ filteredRows.length }} 条</el-tag>
        </div>
      </template>

      <el-table :data="filteredRows" stripe empty-text="当前筛选范围内暂无接盘订单">
        <el-table-column label="ID" prop="id" min-width="80" />
        <el-table-column label="订单号" prop="order_no" min-width="180" show-overflow-tooltip />
        <el-table-column label="下单时间" prop="create_time" min-width="170" />
        <el-table-column label="接手内部号" prop="internal_merchant_title" min-width="130" show-overflow-tooltip />
        <el-table-column label="买家手机号" prop="buyer_phone" min-width="120" />
        <el-table-column label="原商品归属商家" prop="source_merchant_title" min-width="140" show-overflow-tooltip />
        <el-table-column label="商品信息" prop="goods_summary" min-width="220" show-overflow-tooltip />
        <el-table-column label="订单金额" min-width="110">
          <template #default="{ row }">{{ formatCurrency(row.total_price) }}</template>
        </el-table-column>
        <el-table-column label="支付金额" min-width="110">
          <template #default="{ row }">{{ formatCurrency(row.pay_price) }}</template>
        </el-table-column>
        <el-table-column label="付款审核状态" min-width="120">
          <template #default="{ row }">
            <el-tag :type="payStatusType(row.pay_status)" effect="plain">{{ row.pay_status_title }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="商品转入状态" min-width="120">
          <template #default="{ row }">
            <el-tag :type="row.transfer_status ? 'success' : 'warning'" effect="plain">
              {{ row.transfer_status_title }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="当前步骤" prop="current_step_text" min-width="190" show-overflow-tooltip />
        <el-table-column label="是否影响资金" min-width="110">
          <template #default="{ row }">
            <el-tag :type="row.affects_fund ? 'danger' : 'success'" effect="plain">{{ row.affects_fund_title }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="当前处理情况" prop="processing_text" min-width="220" show-overflow-tooltip />
        <el-table-column label="操作" fixed="right" min-width="100">
            <template #default="{ row }">
              <div class="table-actions">
                <el-button link type="primary" @click="openDetail(row)">详情</el-button>
                <el-button v-if="row.internal_merchant_id" link @click="goToRowInternalMerchant(row)">内部号</el-button>
                <el-button v-if="row.source_merchant_id" link @click="goToRowSourceMerchant(row)">原商家</el-button>
              </div>
            </template>
        </el-table-column>
      </el-table>

      <Pagination
        v-show="pagination.count > 0"
        v-model:page="query.page"
        v-model:limit="query.limit"
        v-model:total="pagination.count"
        align="right"
        @pagination="handlePagination"
      />
    </el-card>

    <el-dialog v-model="detailVisible" title="接盘订单详情" width="980px">
      <div v-loading="detailLoading" class="detail-dialog">
        <template v-if="detailData">
          <div class="detail-followup">
            <el-button v-if="detailData.internal_merchant_id" type="primary" @click="goToCurrentInternalMerchant(true)">
              去内部商家页
            </el-button>
            <el-button v-if="detailData.source_merchant_id" @click="goToCurrentSourceMerchant(true)">
              去原商家页
            </el-button>
            <el-button v-if="detailData.detail_items?.length" @click="goToCurrentGoods(true)">
              去商品页
            </el-button>
              <el-button v-if="detailData.buyer_phone || detailData.buyer_nickname" @click="goToCurrentMember(true)">
                去会员页
              </el-button>
            <el-button @click="goToExportCenterForCurrent(true)">导出当前条件</el-button>
          </div>

          <div class="detail-section">
            <div class="detail-section__title">基础信息</div>
            <div class="detail-grid">
              <div class="detail-grid__item"><span>订单号</span><strong>{{ detailData.order_no || '--' }}</strong></div>
              <div class="detail-grid__item"><span>接手内部号</span><strong>{{ detailData.internal_merchant_title || '--' }}</strong></div>
              <div class="detail-grid__item"><span>原商品归属商家</span><strong>{{ detailData.source_merchant_title || '--' }}</strong></div>
              <div class="detail-grid__item"><span>买家昵称</span><strong>{{ detailData.buyer_nickname || '--' }}</strong></div>
              <div class="detail-grid__item"><span>买家手机号</span><strong>{{ detailData.buyer_phone || '--' }}</strong></div>
              <div class="detail-grid__item"><span>支付方式</span><strong>付款凭证上传</strong></div>
              <div class="detail-grid__item"><span>订单金额</span><strong>{{ formatCurrency(detailData.total_price) }}</strong></div>
              <div class="detail-grid__item"><span>支付金额</span><strong>{{ formatCurrency(detailData.pay_price) }}</strong></div>
              <div class="detail-grid__item"><span>付款审核状态</span><strong>{{ detailData.pay_status_title || '--' }}</strong></div>
              <div class="detail-grid__item"><span>订单状态</span><strong>{{ detailData.stage_title || '--' }}</strong></div>
              <div class="detail-grid__item"><span>下单时间</span><strong>{{ detailData.create_time || '--' }}</strong></div>
              <div class="detail-grid__item"><span>支付时间</span><strong>{{ detailData.pay_time || '--' }}</strong></div>
            </div>
          </div>

          <div class="detail-section">
            <div class="detail-section__title">处理进度</div>
            <div class="detail-grid">
              <div class="detail-grid__item"><span>买家账单是否生成</span><strong>{{ detailData.bill_count ? '已生成' : '未生成' }}</strong></div>
              <div class="detail-grid__item"><span>账单 ID</span><strong>{{ billIdsLabel }}</strong></div>
              <div class="detail-grid__item"><span>账单金额</span><strong>{{ formatCurrency(detailData.bill_amount) }}</strong></div>
              <div class="detail-grid__item"><span>商品是否已转入</span><strong>{{ detailData.transfer_status ? '已转入' : '未转入' }}</strong></div>
              <div class="detail-grid__item"><span>转入商家 ID</span><strong>{{ detailData.internal_merchant_id || '--' }}</strong></div>
              <div class="detail-grid__item"><span>转入目标商家名称</span><strong>{{ detailData.internal_merchant_title || '--' }}</strong></div>
            </div>
          </div>

          <div class="detail-section">
            <div class="detail-section__title">异常提醒</div>
            <div class="detail-alert" :class="`detail-alert--${stageTone(detailData.stage_code)}`">
              <strong>{{ detailData.stage_title || '当前状态' }}</strong>
              <span>{{ detailData.exception_reason || detailData.processing_text || '当前未发现明显异常' }}</span>
            </div>
          </div>

          <div class="detail-section">
            <div class="detail-section__title">商品明细</div>
            <div class="goods-list">
              <div v-for="item in detailData.detail_items || []" :key="item.id" class="goods-item">
                <img v-if="item.goods_image_url" :src="item.goods_image_url" alt="商品图" class="goods-item__image" />
                <div class="goods-item__body">
                  <div class="goods-item__title">{{ item.goods_title || '商品' }}</div>
                  <div class="goods-item__meta">
                    数量 {{ item.quantity || 0 }} / 单价 {{ formatCurrency(item.price) }} / 小计 {{ formatCurrency(item.total) }}
                  </div>
                  <div class="goods-item__meta">当前归属商家 ID：{{ item.source_merchant_id || '--' }}</div>
                </div>
              </div>
              <el-empty v-if="!(detailData.detail_items || []).length" description="暂无商品明细" />
            </div>
          </div>

          <div class="detail-section">
            <div class="detail-section__title">支付凭证</div>
            <div class="voucher-list">
              <a
                v-for="item in detailData.pay_voucher_imgs || []"
                :key="item.file_id"
                :href="item.file_url"
                class="voucher-item"
                target="_blank"
                rel="noreferrer"
              >
                <img :src="item.file_url" :alt="item.file_name || '支付凭证'" />
              </a>
              <el-empty v-if="!(detailData.pay_voucher_imgs || []).length" description="暂无支付凭证" />
            </div>
          </div>

          <div class="detail-section">
            <div class="detail-section__title">订单日志</div>
            <el-timeline>
              <el-timeline-item
                v-for="item in detailData.logs || []"
                :key="item.id"
                :timestamp="item.create_time"
                placement="top"
              >
                {{ item.title || '订单日志' }}
              </el-timeline-item>
            </el-timeline>
            <el-empty v-if="!(detailData.logs || []).length" description="暂无订单日志" />
          </div>
        </template>
      </div>
    </el-dialog>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import Pagination from '@/components/Pagination/index.vue'
import { detail, filters, list, summary } from '@/api/report/internal-takeover'

const loading = ref(false)
const detailLoading = ref(false)
const detailVisible = ref(false)
const detailData = ref(null)
const route = useRoute()
const router = useRouter()
const ignoreRouteWatch = ref(false)
const queryStorageKey = 'internal-takeover-query'

const filterOptions = reactive({
  quick_dates: [],
  pay_status_options: [],
  stage_options: [],
  internal_merchants: [],
  source_merchants: []
})

const defaultQuery = () => ({
  quick_date: 'last7',
  daterange: [],
  internal_merchant_id: 0,
  source_merchant_id: 0,
  pay_status: -1,
  stage_code: '',
  keyword: '',
  page: 1,
  limit: 20
})
const query = reactive(defaultQuery())

const summaryCards = reactive({
  total_count: 0,
  priority_text: '',
  completed_count: 0,
  total_amount: 0,
  pending_review_count: 0,
  pending_transfer_count: 0,
  exception_count: 0
})

const healthPanel = ref({})
const queryLabel = ref('当前筛选范围')
const tableRows = ref([])
const pagination = reactive({
  count: 0,
  pages: 0
})
const sourceFilter = ref('all')
const currentDetailRow = ref(null)

const quickDateOptions = computed(() => {
  return filterOptions.quick_dates.length
    ? filterOptions.quick_dates
    : [
        { value: 'today', label: '今天' },
        { value: 'yesterday', label: '昨天' },
        { value: 'last7', label: '近7天' },
        { value: 'last30', label: '近30天' }
      ]
})

const activeFilterTags = computed(() => {
  const tags = []
  const internalMerchant = filterOptions.internal_merchants.find((item) => item.value === query.internal_merchant_id)
  const sourceMerchant = filterOptions.source_merchants.find((item) => item.value === query.source_merchant_id)
  const payStatus = filterOptions.pay_status_options.find((item) => item.value === query.pay_status)
  const stage = filterOptions.stage_options.find((item) => item.value === query.stage_code)

  if (query.daterange.length === 2) {
    tags.push(`下单日期：${query.daterange[0]} 至 ${query.daterange[1]}`)
  } else if (query.quick_date) {
    const quick = quickDateOptions.value.find((item) => item.value === query.quick_date)
    if (quick) {
      tags.push(`快捷日期：${quick.label}`)
    }
  }
  if (internalMerchant?.label) {
    tags.push(`接手内部号：${internalMerchant.label}`)
  }
  if (sourceMerchant?.label) {
    tags.push(`原商品归属商家：${sourceMerchant.label}`)
  }
  if (payStatus && query.pay_status !== -1) {
    tags.push(`付款审核状态：${payStatus.label}`)
  }
  if (stage?.label && query.stage_code) {
    tags.push(`处理阶段：${stage.label}`)
  }
  if (query.keyword) {
    tags.push(`关键字：${query.keyword}`)
  }

  return tags
})

const healthStatusType = computed(() => {
  const tag = healthPanel.value?.status_tag || ''
  if (tag === '存在风险') {
    return 'danger'
  }
  if (tag === '流程处理中') {
    return 'warning'
  }
  return 'success'
})

const exceptionSourceCards = computed(() => {
  const allRows = tableRows.value || []
  const unchanged = allRows.filter((item) => item.pay_status === 1 && !item.transfer_status).length
  const bulk = allRows.filter((item) => Number(item.pay_price || 0) >= 10000 || Number(item.total_num || 0) >= 10 || (item.exception_reason || '').includes('偏大')).length
  const duplicate = allRows.filter((item) => (item.exception_reason || '').includes('同一内部号')).length

  return [
    { key: 'all', title: '全部异常来源', count: allRows.length, desc: '先看全量，再决定切哪一类。' },
    { key: 'unchanged', title: '未变化', count: unchanged, desc: '已收款，但还没识别到商品真正转过去。' },
    { key: 'bulk', title: '大批量', count: bulk, desc: '金额或件数偏大，适合优先复核。' },
    { key: 'duplicate', title: '重复目标', count: duplicate, desc: '同一内部号在当前条件下接手订单偏多。' }
  ]
})

const filteredRows = computed(() => {
  if (sourceFilter.value === 'unchanged') {
    return tableRows.value.filter((item) => item.pay_status === 1 && !item.transfer_status)
  }
  if (sourceFilter.value === 'bulk') {
    return tableRows.value.filter(
      (item) => Number(item.pay_price || 0) >= 10000 || Number(item.total_num || 0) >= 10 || (item.exception_reason || '').includes('偏大')
    )
  }
  if (sourceFilter.value === 'duplicate') {
    return tableRows.value.filter((item) => (item.exception_reason || '').includes('同一内部号'))
  }
  return tableRows.value
})

const billIdsLabel = computed(() => {
  const bills = detailData.value?.bills || []
  if (!bills.length) {
    return '--'
  }
  return bills.map((item) => item.id).join('、')
})

const currentFocusRow = computed(() => {
  if (detailData.value?.id) {
    return detailData.value
  }
  return currentDetailRow.value || filteredRows.value[0] || {}
})

const entrySourceLabel = computed(() => {
  const source = route.query.from
  if (source === 'platform-analytics') return '来自平台分析'
  if (source === 'platform-export') return '来自平台导出'
  if (source === 'merchant-list') return '来自商家列表'
  if (source === 'order-list') return '来自订单列表'
  if (source === 'dashboard') return '来自后台首页'
  if (source === 'internal-merchant') return '来自内部商家'
  return ''
})

const entryContextVisible = computed(() => Boolean(entrySourceLabel.value))

const entryContextTitle = computed(() => {
  if (entrySourceLabel.value === '来自平台分析') return '当前从平台分析进入内部接盘对账'
  if (entrySourceLabel.value === '来自平台导出') return '当前从平台导出进入内部接盘对账'
  if (entrySourceLabel.value === '来自商家列表') return '当前从商家列表进入内部接盘对账'
  if (entrySourceLabel.value === '来自订单列表') return '当前从订单列表进入内部接盘对账'
  if (entrySourceLabel.value === '来自后台首页') return '当前从后台首页进入内部接盘对账'
  if (entrySourceLabel.value === '来自内部商家') return '当前从内部商家进入内部接盘对账'
  return '当前为外部入口承接视角'
})

const entryContextDesc = computed(() => {
  if (entrySourceLabel.value === '来自平台分析') {
    return '这类进入通常是为了把波动趋势落到真实接盘单。建议先看异常和待转商品，再回平台分析页复盘整体影响。'
  }
  if (entrySourceLabel.value === '来自平台导出') {
    return '这类进入通常是为了把导出报表里的异常商家继续下钻。建议先核当前商家和时间范围，再回导出中心处理批量结果。'
  }
  if (entrySourceLabel.value === '来自商家列表') {
    return '这类进入通常是为了查某个商家为什么会触发内部接盘。建议先看原商家和待转商品，再回商家页继续处理到期、归属或接盘策略。'
  }
  if (entrySourceLabel.value === '来自订单列表') {
    return '这类进入通常是为了查订单为什么卡在接盘链路。建议先看待审核和真正异常，再回订单页确认订单状态和凭证审核。'
  }
  if (entrySourceLabel.value === '来自内部商家') {
    return '这类进入通常是为了看某个内部号接手后是否健康。建议先看健康面板，再回内部商家页处理承接限制或到期提醒。'
  }
  return '这类进入通常是首页巡检后的继续下钻。建议先看顶部统计和健康面板，再决定回分析、商家或订单页继续处理。'
})

const entryContextPrimaryLabel = computed(() => {
  if (entrySourceLabel.value === '来自平台分析') return '回平台分析'
  if (entrySourceLabel.value === '来自平台导出') return '回导出中心'
  if (entrySourceLabel.value === '来自商家列表') return '去原商家页'
  if (entrySourceLabel.value === '来自订单列表') return '回订单列表'
  if (entrySourceLabel.value === '来自内部商家') return '回内部商家页'
  return '回后台首页'
})

const currentInternalMerchantActionText = computed(() => {
  const internalMerchantId = Number(currentFocusRow.value?.internal_merchant_id || query.internal_merchant_id || 0)
  if (internalMerchantId > 0) {
    return `把当前接盘问题直接定位到内部号 ${internalMerchantId}，继续核对承接规则、限制和接盘状态。`
  }
  return '当前还没锁定具体内部号，先选内部号或打开一条详情后再继续去内部商家页。'
})

const currentSourceMerchantActionText = computed(() => {
  const merchantId = Number(currentFocusRow.value?.source_merchant_id || query.source_merchant_id || 0)
  if (merchantId > 0) {
    return `把当前接盘问题定位到原商家 ${merchantId}，继续核对商品归属、到期状态和商家处理动作。`
  }
  return '当前还没锁定具体原商家，先筛选原商家或打开一条详情后再继续去商家页。'
})

const currentStageGuide = computed(() => {
  if (query.stage_code === 'exception') {
    return { label: '当前只看真正异常', type: 'danger' }
  }
  if (query.stage_code === 'pending_transfer') {
    return { label: '当前只看待转商品', type: 'primary' }
  }
  if (query.stage_code === 'pending_review') {
    return { label: '当前只看待审核', type: 'warning' }
  }
  return { label: '当前看全部接盘单', type: 'success' }
})

const stageMeaningCards = computed(() => {
  return [
    {
      title: '待审核',
      desc: '买家已经传了付款凭证，但平台还没确认收款。这类先看凭证和金额，不算真正异常。',
      action: '适合先点“只看待审核”，集中确认付款。'
    },
    {
      title: '待转商品',
      desc: '平台已经确认收款，但商品还没真正转到内部号名下。这类多数是流程积压，不一定异常。',
      action: '适合去内部商家页或订单详情看流转卡在哪一步。'
    },
    {
      title: '已完成',
      desc: '收款、账单、商品转入都已经闭环，这类通常不用追，除非你要做复盘抽查。',
      action: '适合留在列表里抽样确认，不用优先处理。'
    },
    {
      title: '真正异常',
      desc: '账单缺失、金额对不上、商品归属不对，或者别的明显不正常情况。这类才是优先级最高的。',
      action: '适合先看详情，再继续跳内部号、原商家和订单页处理。'
    }
  ]
})

const operatorGuideSteps = computed(() => {
  return [
    {
      step: '01',
      title: '先看顶部四张卡',
      desc: '先判断今天主要是待审核、待转商品，还是已经出现真正异常。'
    },
    {
      step: '02',
      title: '再用阶段按钮聚焦',
      desc: '不要一上来翻全表，先把问题压缩到某一类。'
    },
    {
      step: '03',
      title: '最后顺着详情继续处理',
      desc: '详情里可继续跳内部号、原商家、商品和会员，不用重新搜。'
    }
  ]
})

const currentPriorityHint = computed(() => {
  if (summaryCards.exception_count > 0) {
    return `现在最该优先看“真正异常”，当前有 ${formatCount(summaryCards.exception_count)} 单不是正常积压。`
  }
  if (summaryCards.pending_transfer_count > 0) {
    return `当前主要是待转商品，共 ${formatCount(summaryCards.pending_transfer_count)} 单，说明收款后流转还有积压。`
  }
  if (summaryCards.pending_review_count > 0) {
    return `当前主要是待审核，共 ${formatCount(summaryCards.pending_review_count)} 单，建议先确认付款凭证。`
  }
  if (summaryCards.total_count > 0) {
    return '当前筛选范围内基本顺畅，没有明显需要优先处理的积压。'
  }
  return '当前筛选范围内暂无接盘单，可以切时间范围或切内部号继续看。'
})

const currentPriorityShort = computed(() => {
  if (summaryCards.exception_count > 0) {
    return '先看真正异常'
  }
  if (summaryCards.pending_transfer_count > 0) {
    return '先看待转商品'
  }
  if (summaryCards.pending_review_count > 0) {
    return '先看待审核'
  }
  if (summaryCards.total_count > 0) {
    return '先看健康面板'
  }
  return '先切筛选条件'
})

const quickStartSteps = computed(() => [
  {
    step: '01',
    title: '先看顶部 4 张统计卡',
    desc: '先判断今天主要是待审核、待转商品，还是已经出现真正异常。'
  },
  {
    step: '02',
    title: '再点“只看某一类”缩小范围',
    desc: '如果异常不多，就先只看异常；如果异常为 0，就优先看待转商品或待审核。'
  },
  {
    step: '03',
    title: '再选一个内部号看健康面板',
    desc: '健康面板会直接告诉你这个内部号现在是正常、处理中，还是已经有风险。'
  },
  {
    step: '04',
    title: '最后看列表和详情弹窗',
    desc: '列表负责定位具体单子，详情弹窗负责看账单、商品转入、支付凭证和日志。'
  }
])

function formatCount(value) {
  return Number(value || 0).toLocaleString('zh-CN')
}

function formatCurrency(value) {
  return `¥${Number(value || 0).toFixed(2)}`
}

function buildExportQuery() {
  const next = buildContinuityQuery({ from: 'internal-takeover' })
  if (Number(query.source_merchant_id || 0) > 0) {
    next.merchant_id = query.source_merchant_id
  }
  return next
}

function payStatusType(status) {
  if (Number(status) === 1) {
    return 'success'
  }
  if (Number(status) === 2) {
    return 'danger'
  }
  return 'warning'
}

function stageTone(stageCode) {
  if (stageCode === 'completed') {
    return 'green'
  }
  if (stageCode === 'exception') {
    return 'red'
  }
  if (stageCode === 'pending_transfer') {
    return 'blue'
  }
  return 'amber'
}

function buildParams() {
  const params = {
    quick_date: query.daterange.length ? '' : query.quick_date,
    start_date: query.daterange[0] || '',
    end_date: query.daterange[1] || '',
    internal_merchant_id: query.internal_merchant_id || 0,
    source_merchant_id: query.source_merchant_id || 0,
    pay_status: query.pay_status,
    stage_code: query.stage_code,
    keyword: query.keyword,
    page: query.page,
    limit: query.limit
  }
  return params
}

function parseNumber(value, fallback = 0) {
  if (value === '' || value === null || value === undefined) return fallback
  const next = Number(value)
  return Number.isNaN(next) ? fallback : next
}

function hasRouteFilters() {
  return Object.keys(route.query || {}).length > 0
}

function readPersistedParams() {
  try {
    const raw = localStorage.getItem(queryStorageKey)
    if (!raw) return null
    const parsed = JSON.parse(raw)
    return parsed && typeof parsed === 'object' ? parsed : null
  } catch {
    return null
  }
}

function writePersistedParams(params) {
  localStorage.setItem(queryStorageKey, JSON.stringify(params))
}

function stringifyQuery(params) {
  return Object.fromEntries(Object.entries(params).map(([key, value]) => [key, String(value)]))
}

function buildContinuityQuery(extraQuery = {}) {
  return {
    ...buildParams(),
    ...extraQuery
  }
}

function applyQueryParams(nextQuery = {}) {
  const sourceMerchantId =
    nextQuery.source_merchant_id !== undefined ? nextQuery.source_merchant_id : nextQuery.merchant_id
  query.quick_date = typeof nextQuery.quick_date === 'string' && nextQuery.quick_date ? nextQuery.quick_date : 'last7'
  query.internal_merchant_id = parseNumber(nextQuery.internal_merchant_id, 0)
  query.source_merchant_id = parseNumber(sourceMerchantId, 0)
  query.pay_status = parseNumber(nextQuery.pay_status, -1)
  query.stage_code = typeof nextQuery.stage_code === 'string' ? nextQuery.stage_code : ''
  query.keyword = typeof nextQuery.keyword === 'string' ? nextQuery.keyword : ''
  query.page = parseNumber(nextQuery.page, 1)
  query.limit = parseNumber(nextQuery.limit, 20)

  const startDate = typeof nextQuery.start_date === 'string' ? nextQuery.start_date : ''
  const endDate = typeof nextQuery.end_date === 'string' ? nextQuery.end_date : ''
  query.daterange = startDate && endDate ? [startDate, endDate] : []

  if (query.daterange.length === 2) {
    query.quick_date = ''
  }
}

function restoreQueryFromRoute() {
  applyQueryParams(route.query)
  writePersistedParams(buildParams())
}

async function syncRoute(params) {
  const nextQuery = stringifyQuery(params)
  const currentQuery = Object.fromEntries(
    Object.entries(route.query).map(([key, value]) => [key, Array.isArray(value) ? String(value[0] ?? '') : String(value)])
  )
  const unchanged =
    JSON.stringify(Object.entries(currentQuery).sort(([a], [b]) => a.localeCompare(b))) ===
    JSON.stringify(Object.entries(nextQuery).sort(([a], [b]) => a.localeCompare(b)))

  if (unchanged) {
    ignoreRouteWatch.value = false
    return
  }

  ignoreRouteWatch.value = true
  writePersistedParams(params)
  await router.push({
    name: route.name,
    query: nextQuery
  })
}

async function loadFilterOptions() {
  const res = await filters()
  Object.assign(filterOptions, res.data || {})
}

async function loadSummaryAndList() {
  const params = buildParams()
  const [summaryRes, listRes] = await Promise.all([summary(params), list(params)])
  const summaryData = summaryRes.data || {}
  const listData = listRes.data || {}

  Object.assign(summaryCards, summaryData.cards || {})
  healthPanel.value = summaryData.health_panel || {}
  queryLabel.value = summaryData.query_label || '当前筛选范围'
  tableRows.value = listData.list || []
  pagination.count = Number(listData.count || 0)
  pagination.pages = Number(listData.pages || 0)
}

async function fetchData() {
  loading.value = true
  try {
    await loadSummaryAndList()
  } catch (error) {
    ElMessage.error(error?.msg || error?.message || '内部接盘对账加载失败')
  } finally {
    loading.value = false
  }
}

async function applyQuickRange(value) {
  query.quick_date = value
  query.daterange = []
  query.page = 1
  await syncRoute(buildParams())
  fetchData()
}

async function applyFilters() {
  query.page = 1
  if (query.daterange.length) {
    query.quick_date = ''
  } else if (!query.quick_date) {
    query.quick_date = 'last7'
  }
  await syncRoute(buildParams())
  fetchData()
}

async function resetQuery() {
  Object.assign(query, defaultQuery())
  sourceFilter.value = 'all'
  await syncRoute(buildParams())
  fetchData()
}

async function useStage(stageCode) {
  query.stage_code = stageCode
  query.page = 1
  await syncRoute(buildParams())
  fetchData()
}

async function clearStageFilter() {
  query.stage_code = ''
  query.page = 1
  await syncRoute(buildParams())
  fetchData()
}

async function handlePagination() {
  await syncRoute(buildParams())
  fetchData()
}

async function reloadAll() {
  await syncRoute(buildParams())
  fetchData()
}

async function openDetail(row) {
  currentDetailRow.value = row
  detailVisible.value = true
  detailLoading.value = true
  detailData.value = null
  try {
    const res = await detail({ id: row.id })
    detailData.value = res.data || {}
  } catch (error) {
    ElMessage.error(error?.msg || error?.message || '订单详情加载失败')
  } finally {
    detailLoading.value = false
  }
}

function goToExportCenter() {
  router.push({
    path: '/exports',
    query: stringifyQuery(buildExportQuery())
  })
}

function goToExportCenterForCurrent(closeDialog = false) {
  if (closeDialog) {
    detailVisible.value = false
  }
  goToExportCenter()
}

function goToInternalMerchant() {
  if (!query.internal_merchant_id) return
  router.push({
    path: '/system/internal-merchant',
    query: stringifyQuery(buildContinuityQuery({
      internal_merchant_id: String(query.internal_merchant_id),
      from: 'internal-takeover'
    }))
  })
}

function goToCurrentInternalMerchant(closeDialog = false) {
  const internalMerchantId = Number(currentFocusRow.value?.internal_merchant_id || query.internal_merchant_id || 0)
  if (!internalMerchantId) {
    ElMessage.warning('当前没有可定位的内部号')
    return
  }
  if (closeDialog) {
    detailVisible.value = false
  }
  router.push({
    path: '/system/internal-merchant',
    query: stringifyQuery(buildContinuityQuery({
      internal_merchant_id: String(internalMerchantId),
      from: 'internal-takeover'
    }))
  })
}

function goToCurrentSourceMerchant(closeDialog = false) {
  const merchantId = Number(currentFocusRow.value?.source_merchant_id || query.source_merchant_id || 0)
  if (!merchantId) {
    ElMessage.warning('当前没有可定位的原商家')
    return
  }
  if (closeDialog) {
    detailVisible.value = false
  }
  router.push({
    path: '/merchant/merchant',
    query: stringifyQuery(buildContinuityQuery({
      id: String(merchantId),
      focus_mode: 'detail',
      from: 'internal-takeover'
    }))
  })
}

function goToRowInternalMerchant(row = {}) {
  const internalMerchantId = Number(row.internal_merchant_id || 0)
  if (!internalMerchantId) {
    ElMessage.warning('当前没有可定位的内部号')
    return
  }
  router.push({
    path: '/system/internal-merchant',
    query: stringifyQuery(buildContinuityQuery({
      internal_merchant_id: String(internalMerchantId),
      from: 'internal-takeover'
    }))
  })
}

function goToRowSourceMerchant(row = {}) {
  const merchantId = Number(row.source_merchant_id || 0)
  if (!merchantId) {
    ElMessage.warning('当前没有可定位的原商家')
    return
  }
  router.push({
    path: '/merchant/merchant',
    query: stringifyQuery(buildContinuityQuery({
      id: String(merchantId),
      focus_mode: 'detail',
      from: 'internal-takeover'
    }))
  })
}

function goToCurrentGoods(closeDialog = false) {
  const firstGoods = currentFocusRow.value?.detail_items?.[0] || {}
  const goodsId = firstGoods.goods_id || currentFocusRow.value?.goods_id
  const goodsTitle = firstGoods.goods_title || currentFocusRow.value?.goods_summary
  if (!goodsId && !goodsTitle) {
    ElMessage.warning('当前没有可定位的商品')
    return
  }
  if (closeDialog) {
    detailVisible.value = false
  }
  router.push({
    path: '/goods/Goods',
    query: stringifyQuery(
      goodsId
      ? buildContinuityQuery({
          search_field: 'goods_id',
          search_exp: '=',
          search_value: String(goodsId),
          from: 'internal-takeover'
        })
      : buildContinuityQuery({
          search_field: 'title',
          search_exp: 'like',
          search_value: String(goodsTitle),
          from: 'internal-takeover'
        })
    )
  })
}

function goToCurrentMember(closeDialog = false) {
  const row = currentFocusRow.value || {}
  const keyword = row.buyer_phone || row.buyer_nickname
  if (!keyword) {
    ElMessage.warning('当前没有可追踪的买家线索')
    return
  }
  if (closeDialog) {
    detailVisible.value = false
  }
  router.push({
    path: '/member/member',
    query: stringifyQuery(
      row.buyer_phone
        ? buildContinuityQuery({
            search_field: 'phone',
            search_exp: '=',
            search_value: String(row.buyer_phone),
            from: 'internal-takeover'
          })
        : buildContinuityQuery({
            search_field: 'nickname',
            search_exp: 'like',
            search_value: String(row.buyer_nickname),
            from: 'internal-takeover'
          })
    )
  })
}

function goToAnalyticsReview() {
  const nextQuery = {}
  if (query.quick_date && !query.daterange.length) {
    nextQuery.quick_date = query.quick_date
  }
  if (query.daterange.length === 2) {
    nextQuery.start_date = query.daterange[0]
    nextQuery.end_date = query.daterange[1]
  }
  if (Number(query.source_merchant_id || 0) > 0) {
    nextQuery.merchant_id = String(query.source_merchant_id)
  }
  router.push({
    path: '/analytics',
    query: {
      ...nextQuery,
      from: 'internal-takeover'
    }
  })
}

function goToOrderReview() {
  const nextQuery = buildContinuityQuery({ from: 'internal-takeover' })
  if (Number(query.source_merchant_id || 0) > 0) {
    nextQuery.merchant_id = String(query.source_merchant_id)
  }
  if (query.keyword) {
    nextQuery.search_field = 'unique'
    nextQuery.search_exp = 'like'
    nextQuery.search_value = query.keyword
  }
  delete nextQuery.keyword
  delete nextQuery.stage_code
  delete nextQuery.source_merchant_id
  nextQuery.order_status = ''
  router.push({
    path: '/order/order',
    query: stringifyQuery(nextQuery)
  })
}

function goToMerchantListReview() {
  const merchantId = Number(currentFocusRow.value?.source_merchant_id || query.source_merchant_id || 0)
  router.push({
    path: '/merchant/merchant',
    query: stringifyQuery(
      merchantId
        ? buildContinuityQuery({
            id: String(merchantId),
            focus_mode: 'detail',
            from: 'internal-takeover'
          })
        : buildContinuityQuery({
            from: 'internal-takeover'
          })
    )
  })
}

function goToInternalMerchantReview() {
  const internalMerchantId = Number(currentFocusRow.value?.internal_merchant_id || query.internal_merchant_id || 0)
  router.push({
    path: '/system/internal-merchant',
    query: internalMerchantId
      ? stringifyQuery(buildContinuityQuery({
          internal_merchant_id: String(internalMerchantId),
          from: 'internal-takeover'
        }))
      : stringifyQuery(buildContinuityQuery({
          from: 'internal-takeover'
        }))
  })
}

function handleEntryContextPrimary() {
  if (entrySourceLabel.value === '来自平台分析') {
    goToAnalyticsReview()
    return
  }
  if (entrySourceLabel.value === '来自平台导出') {
    goToExportCenter()
    return
  }
  if (entrySourceLabel.value === '来自商家列表') {
    goToMerchantListReview()
    return
  }
  if (entrySourceLabel.value === '来自订单列表') {
    goToOrderReview()
    return
  }
  if (entrySourceLabel.value === '来自内部商家') {
    goToInternalMerchantReview()
    return
  }
  router.push({
    path: '/dashboard',
    query: stringifyQuery(buildContinuityQuery({ from: 'internal-takeover' }))
  })
}

function goToEntryContextBack() {
  handleEntryContextPrimary()
}

watch(
  () => route.fullPath,
  async () => {
    if (ignoreRouteWatch.value) {
      ignoreRouteWatch.value = false
      return
    }
    restoreQueryFromRoute()
    await fetchData()
  }
)

onMounted(async () => {
  if (!hasRouteFilters()) {
    const persistedParams = readPersistedParams()
    if (persistedParams) {
      applyQueryParams(persistedParams)
      ignoreRouteWatch.value = true
      await router.replace({
        name: route.name,
        query: stringifyQuery(persistedParams)
      })
    } else {
      restoreQueryFromRoute()
    }
  } else {
    restoreQueryFromRoute()
  }
  try {
    await loadFilterOptions()
  } catch (error) {
    ElMessage.error(error?.msg || error?.message || '筛选项加载失败')
  }
  await fetchData()
})
</script>

<style scoped>
.takeover-page {
  display: grid;
  gap: 12px;
}

.panel {
  border-radius: 16px;
  border: 1px solid #e6ebf2;
}

.panel--hero {
  background: linear-gradient(135deg, #f8fbff 0%, #fffdf6 100%);
}

.panel__header,
.panel__header-bar {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;
}

.panel__title {
  font-size: 20px;
  font-weight: 700;
  color: #1f2937;
}

.panel__desc,
.panel__sub-desc {
  margin-top: 6px;
  line-height: 1.7;
  color: #5b6472;
}

.panel__sub-title {
  font-size: 16px;
  font-weight: 700;
  color: #1f2937;
}

.panel__actions {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 8px;
}

.entry-context-banner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 16px;
  padding: 14px 16px;
  border-radius: 12px;
  background: linear-gradient(135deg, #f5f7ff 0%, #fffaf0 100%);
  border: 1px solid #e4e7ed;
}

.entry-context-banner__main {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.entry-context-banner__eyebrow {
  font-size: 12px;
  font-weight: 600;
  color: #909399;
}

.entry-context-banner__title {
  font-size: 16px;
  font-weight: 600;
  color: #303133;
}

.entry-context-banner__desc {
  font-size: 13px;
  line-height: 1.6;
  color: #606266;
}

.entry-context-banner__actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  justify-content: flex-end;
}

.quick-range {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 18px;
}

.quick-range__item {
  border: 1px solid #d8e3f0;
  background: #fff;
  color: #425466;
  border-radius: 999px;
  padding: 8px 16px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.quick-range__item--active {
  border-color: #1d4ed8;
  background: #1d4ed8;
  color: #fff;
}

.filter-form {
  margin-top: 18px;
}

.current-params {
  margin-top: 8px;
  padding-top: 10px;
  border-top: 1px dashed #d7dfeb;
}

.current-params__title {
  font-size: 13px;
  color: #6b7280;
}

.current-params__tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 10px;
}

.plain-guide {
  margin-top: 12px;
  padding: 14px 16px;
  border: 1px solid #e6ebf2;
  border-radius: 16px;
  background: linear-gradient(180deg, #fffdf7 0%, #ffffff 100%);
}

.plain-guide__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 14px;
}

.plain-guide__title {
  font-size: 14px;
  font-weight: 700;
  color: #1f2937;
}

.plain-guide__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.plain-guide__grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
  margin-top: 12px;
}

.plain-guide-card {
  padding: 14px;
  border-radius: 14px;
  border: 1px solid rgba(226, 232, 240, 0.95);
  background: #fff;
}

.plain-guide-card__title {
  font-size: 13px;
  font-weight: 700;
  color: #0f172a;
}

.plain-guide-card__desc {
  margin-top: 8px;
  font-size: 12px;
  line-height: 1.7;
  color: #5b6472;
}

.plain-guide-card__action {
  margin-top: 10px;
  padding: 8px 10px;
  border-radius: 10px;
  background: #f8fafc;
  color: #334155;
  font-size: 12px;
  line-height: 1.7;
}

.followup-strip {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-top: 14px;
}

.followup-card {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 6px;
  min-height: 108px;
  padding: 14px 16px;
  text-align: left;
  border: 1px solid #d8e3f0;
  background: #fff;
  border-radius: 16px;
}

.followup-card__title {
  font-size: 13px;
  font-weight: 700;
  color: #1f2937;
}

.followup-card__desc {
  font-size: 12px;
  line-height: 1.7;
  color: #5b6472;
}

.operator-guide {
  display: grid;
  grid-template-columns: minmax(0, 1.8fr) minmax(300px, 1fr);
  gap: 12px;
  margin-top: 14px;
}

.quick-start {
  margin-top: 14px;
  padding: 16px 18px;
  border: 1px solid #e6ebf2;
  border-radius: 16px;
  background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
}

.quick-start__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.quick-start__title {
  font-size: 14px;
  font-weight: 700;
  color: #1f2937;
}

.quick-start__desc {
  margin-top: 8px;
  color: #5b6472;
  line-height: 1.7;
}

.quick-start__steps {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 10px;
  margin-top: 14px;
}

.quick-start-step {
  display: grid;
  gap: 6px;
  padding: 14px 16px;
  border-radius: 14px;
  border: 1px solid #e7edf5;
  background: #fff;
}

.quick-start-step span {
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.08em;
  color: #d97706;
}

.quick-start-step strong {
  color: #0f172a;
}

.quick-start-step em {
  font-style: normal;
  color: #64748b;
  line-height: 1.6;
}

.operator-guide__main,
.operator-guide__aside {
  padding: 16px 18px;
  border: 1px solid #e6ebf2;
  border-radius: 16px;
  background: #fff;
}

.operator-guide__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.operator-guide__title,
.operator-guide__aside-title {
  font-size: 14px;
  font-weight: 700;
  color: #1f2937;
}

.operator-guide__desc,
.operator-guide__aside-note {
  margin-top: 8px;
  color: #5b6472;
  line-height: 1.7;
}

.operator-guide__steps {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 10px;
  margin-top: 14px;
}

.operator-guide-step {
  display: grid;
  gap: 6px;
  padding: 14px 16px;
  border-radius: 14px;
  border: 1px solid #e7edf5;
  background: linear-gradient(180deg, #f8fbff 0%, #fff 100%);
}

.operator-guide-step span {
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.08em;
  color: #1d4ed8;
}

.operator-guide-step strong {
  color: #0f172a;
}

.operator-guide-step em {
  font-style: normal;
  color: #64748b;
  line-height: 1.6;
}

.operator-guide__aside-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 14px;
}

.summary-banner {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
}

.summary-banner__item {
  padding: 16px 18px;
  background: #fff;
  border: 1px solid #e6ebf2;
  border-radius: 16px;
}

.summary-banner__label {
  display: block;
  color: #64748b;
  font-size: 13px;
}

.summary-banner__item strong {
  display: block;
  margin-top: 10px;
  font-size: 22px;
  color: #0f172a;
}

.metrics-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
}

.metric-card {
  border-radius: 16px;
}

.metric-card__label {
  color: #64748b;
  font-size: 13px;
}

.metric-card__value {
  margin-top: 10px;
  font-size: 28px;
  font-weight: 700;
}

.metric-card__meta {
  margin-top: 8px;
  color: #5b6472;
  line-height: 1.6;
}

.metric-card--amber {
  background: linear-gradient(180deg, #fff9eb 0%, #fff 100%);
}

.metric-card--blue {
  background: linear-gradient(180deg, #eef6ff 0%, #fff 100%);
}

.metric-card--green {
  background: linear-gradient(180deg, #edf9f1 0%, #fff 100%);
}

.metric-card--red {
  background: linear-gradient(180deg, #fff0f0 0%, #fff 100%);
}

.content-row {
  margin: 0;
}

.health-panel {
  display: grid;
  gap: 16px;
}

.health-panel__hero {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  padding: 16px;
  border-radius: 14px;
  background: #f8fafc;
}

.health-panel__title {
  font-size: 18px;
  font-weight: 700;
  color: #111827;
}

.health-panel__summary {
  margin-top: 8px;
  line-height: 1.7;
  color: #5b6472;
}

.health-panel__next {
  min-width: 240px;
  display: grid;
  gap: 6px;
}

.health-panel__next span {
  font-size: 12px;
  color: #64748b;
}

.health-panel__next strong {
  color: #0f172a;
  line-height: 1.7;
}

.health-reminders,
.status-grid,
.stats-grid {
  display: grid;
  gap: 12px;
}

.health-reminders {
  grid-template-columns: repeat(4, minmax(0, 1fr));
}

.status-grid {
  grid-template-columns: repeat(4, minmax(0, 1fr));
}

.stats-grid {
  grid-template-columns: repeat(4, minmax(0, 1fr));
}

.health-reminders__item,
.status-grid__item,
.stats-grid__item {
  padding: 14px 16px;
  background: #fff;
  border: 1px solid #e7edf5;
  border-radius: 14px;
}

.health-reminders__item span,
.status-grid__item span,
.stats-grid__item span,
.detail-grid__item span {
  display: block;
  color: #64748b;
  font-size: 12px;
}

.health-reminders__item strong,
.status-grid__item strong,
.stats-grid__item strong,
.detail-grid__item strong {
  display: block;
  margin-top: 8px;
  color: #0f172a;
  line-height: 1.6;
}

.danger {
  color: #dc2626 !important;
}

.exception-source-list {
  display: grid;
  gap: 10px;
}

.exception-source {
  text-align: left;
  border: 1px solid #e5eaf3;
  background: #fff;
  border-radius: 14px;
  padding: 14px 16px;
  cursor: pointer;
  display: grid;
  gap: 6px;
  transition: all 0.2s ease;
}

.exception-source strong {
  font-size: 24px;
  color: #111827;
}

.exception-source em {
  font-style: normal;
  color: #64748b;
  line-height: 1.6;
}

.exception-source--active {
  border-color: #1d4ed8;
  box-shadow: 0 0 0 1px rgba(29, 78, 216, 0.08);
  background: #f7fbff;
}

.helper-box {
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px dashed #d7dfeb;
}

.helper-box__title {
  font-weight: 700;
  color: #1f2937;
}

.helper-box__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 12px;
}

.helper-box__actions--stacked {
  flex-direction: column;
  align-items: stretch;
}

.helper-box__note {
  margin-top: 12px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.table-panel {
  overflow: hidden;
}

.detail-dialog {
  min-height: 240px;
}

.detail-followup {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-bottom: 18px;
}

.detail-section {
  margin-bottom: 18px;
}

.detail-section__title {
  font-size: 15px;
  font-weight: 700;
  color: #1f2937;
  margin-bottom: 12px;
}

.detail-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
}

.detail-grid__item {
  padding: 14px 16px;
  border-radius: 12px;
  border: 1px solid #e7edf5;
  background: #fff;
}

.detail-alert {
  padding: 14px 16px;
  border-radius: 14px;
  display: grid;
  gap: 8px;
}

.detail-alert--amber {
  background: #fff8e9;
  color: #8a5a00;
}

.detail-alert--blue {
  background: #eff6ff;
  color: #1d4ed8;
}

.detail-alert--green {
  background: #edf9f1;
  color: #0f8a4b;
}

.detail-alert--red {
  background: #fff1f1;
  color: #c53030;
}

.goods-list,
.voucher-list {
  display: grid;
  gap: 12px;
}

.goods-item {
  display: flex;
  gap: 14px;
  padding: 14px 16px;
  border: 1px solid #e7edf5;
  border-radius: 14px;
}

.goods-item__image {
  width: 72px;
  height: 72px;
  object-fit: cover;
  border-radius: 10px;
  background: #f8fafc;
}

.goods-item__body {
  flex: 1;
}

.goods-item__title {
  font-weight: 700;
  color: #111827;
}

.goods-item__meta {
  margin-top: 6px;
  color: #64748b;
  line-height: 1.6;
}

.voucher-list {
  grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
}

.voucher-item {
  display: block;
  padding: 8px;
  border: 1px solid #e7edf5;
  border-radius: 12px;
  background: #fff;
}

.voucher-item img {
  width: 100%;
  height: 120px;
  object-fit: cover;
  border-radius: 8px;
}

@media (max-width: 1200px) {
  .quick-start__steps,
  .plain-guide__grid,
  .summary-banner,
  .metrics-grid,
  .followup-strip,
  .operator-guide,
  .operator-guide__steps {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .health-reminders,
  .status-grid,
  .stats-grid,
  .detail-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 768px) {
  .entry-context-banner,
  .panel__header,
  .panel__header-bar,
  .quick-start__header,
  .plain-guide__header,
  .health-panel__hero {
    flex-direction: column;
  }

  .quick-start__steps,
  .plain-guide__grid,
  .summary-banner,
  .metrics-grid,
  .followup-strip,
  .operator-guide,
  .operator-guide__steps,
  .health-reminders,
  .status-grid,
  .stats-grid,
  .detail-grid {
    grid-template-columns: 1fr;
  }
}
</style>
