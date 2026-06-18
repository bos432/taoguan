<template>
  <div v-loading="loading" class="goods-ops-page">
    <el-card class="panel panel--hero panel--hero-lite" shadow="never">
      <div class="panel__header">
        <div>
          <div class="panel__title">商品管理</div>
          <div class="panel__desc">
            先按线上那种清爽列表节奏处理，迁移复核和批量承接需要时再展开。
          </div>
        </div>
        <div class="panel__actions">
          <el-tag effect="plain" type="success">可运营源码版</el-tag>
          <el-tag effect="plain">{{ heroSelectionLabel }}</el-tag>
          <el-button @click="refresh">刷新列表</el-button>
          <el-button type="primary" @click="add">新增商品</el-button>
        </div>
      </div>

      <div class="hero-shortcuts">
        <button
          v-for="item in shortcutActions"
          :key="item.key"
          class="hero-shortcut"
          type="button"
          :disabled="item.disabled"
          @click="item.action"
        >
          <span class="hero-shortcut__title">{{ item.title }}</span>
          <span class="hero-shortcut__desc">{{ item.desc }}</span>
        </button>
      </div>

      <div class="hero-decision-strip" :class="`hero-decision-strip--${heroDecisionStrip.tone}`">
        <div class="hero-decision-strip__main">
          <div class="hero-decision-strip__label">当前建议</div>
          <div class="hero-decision-strip__title">{{ heroDecisionStrip.title }}</div>
          <div class="hero-decision-strip__desc">{{ heroDecisionStrip.desc }}</div>
        </div>
        <div class="hero-decision-strip__tags">
          <span v-for="item in heroDecisionStrip.tags" :key="item">{{ item }}</span>
        </div>
      </div>

      <div class="hero-guide-grid">
        <div v-for="item in heroGuideCards" :key="item.title" class="hero-guide-card">
          <div class="hero-guide-card__title">{{ item.title }}</div>
          <div class="hero-guide-card__desc">{{ item.desc }}</div>
          <div class="hero-guide-card__action">{{ item.action }}</div>
        </div>
      </div>

    </el-card>

    <el-card class="panel panel--workbench" :class="{ 'panel--workbench-compact': !operationWorkbenchExpanded }" shadow="never">
      <div class="panel__header-bar" :class="{ 'panel__header-bar--compact': !operationWorkbenchExpanded }">
        <div>
          <div class="panel__sub-title">{{ operationWorkbenchExpanded ? '增强运营工作台' : '高级操作台' }}</div>
          <div class="panel__sub-desc">
            {{ operationWorkbenchExpanded ? '当前已展开迁移、复核和批量处理能力。' : '默认按线上习惯保留清爽列表，批量迁移和复核按需展开。' }}
          </div>
        </div>
        <div class="panel__toolbar" :class="{ 'panel__toolbar--compact': !operationWorkbenchExpanded }">
          <el-button :disabled="!selection.length" @click="selectOpen('dele')">删除</el-button>
          <el-button :disabled="!data.length" @click="quickDisableSelected(0)">批量上架</el-button>
          <el-button :disabled="!data.length" @click="quickDisableSelected(1)">批量下架</el-button>
          <el-button :disabled="!data.length" @click="selectOpen('disable')">上架/下架</el-button>
          <el-button :disabled="!selection.length" type="warning" @click="selectOpen('auth')">批量审核</el-button>
          <el-button :disabled="!data.length" @click="selectOpen('batch_label')">批量打标</el-button>
          <el-button :disabled="!data.length" @click="selectOpen('batch_thumbnail')">批量换图</el-button>
          <el-button
            :disabled="!data.length || !checkPermission(['admin/goods.Goods/transferToPlatform'])"
            type="primary"
            @click="selectOpen('transfer_platform')"
          >
            批量迁移到平台
          </el-button>
          <el-button
            :disabled="!data.length || !checkPermission(['admin/goods.Goods/transferToMerchant'])"
            type="success"
            @click="openTransferMerchantDialog()"
          >
            批量迁移到商家
          </el-button>
        </div>
      </div>

      <div class="compact-workbench" :class="{ 'compact-workbench--active': operationWorkbenchExpanded }">
        <div class="compact-workbench__main">
          <div class="compact-workbench__title">{{ compactWorkbenchSummary.title }}</div>
          <div class="compact-workbench__desc">{{ compactWorkbenchSummary.desc }}</div>
          <div v-if="compactWorkbenchSummary.tags.length" class="compact-workbench__tags">
            <span v-for="item in compactWorkbenchSummary.tags" :key="item">{{ item }}</span>
          </div>
        </div>
        <div class="compact-workbench__actions">
          <el-button size="small" type="primary" @click="toggleOperationWorkbench">
            {{ operationWorkbenchExpanded ? '收起高级操作' : '展开高级操作' }}
          </el-button>
          <el-button
            v-if="stagedBridgeSummary.active"
            size="small"
            plain
            @click="submitBridgeAction"
          >
            继续迁移
          </el-button>
          <el-button
            v-if="stagedBridgeSnapshotAvailable"
            size="small"
            plain
            @click="restoreStagedBridgeSnapshot"
          >
            恢复最近承接
          </el-button>
        </div>
      </div>

      <div v-if="operationWorkbenchExpanded" class="action-groups">
        <div v-for="group in actionGroups" :key="group.key" class="action-group">
          <div class="action-group__title">{{ group.title }}</div>
          <div class="action-group__desc">{{ group.desc }}</div>
          <div class="action-group__items">
            <button
              v-for="item in group.items"
              :key="item.key"
              class="action-group__item"
              type="button"
              :disabled="item.disabled"
              @click="item.action"
            >
              {{ item.label }}
            </button>
          </div>
        </div>
      </div>

      <div v-if="operationWorkbenchExpanded" class="batch-bridge">
        <div class="batch-bridge__intro">
          <div class="batch-bridge__title">批量迁移工具</div>
          <div class="batch-bridge__desc">
            这两个迁移入口优先处理当前筛选页里的商品。你可以先缩小筛选范围，再在弹窗里确认整页迁移或仅处理已勾选商品。
          </div>
        </div>

        <div class="batch-bridge__controls">
          <div class="bridge-control">
            <label>迁移模式</label>
            <el-radio-group v-model="bridgeMode">
              <el-radio-button label="platform">迁移到平台</el-radio-button>
              <el-radio-button label="merchant">迁移到商家</el-radio-button>
            </el-radio-group>
          </div>
          <div class="bridge-control bridge-control--merchant" v-if="bridgeMode === 'merchant'">
            <label>目标商家</label>
            <el-select
              v-model="target_merchant_id"
              class="bridge-merchant-select"
              clearable
              filterable
              popper-class="bridge-merchant-dropdown"
              placeholder="请选择目标商家"
            >
              <el-option
                v-for="item in merchantOptions"
                :key="item.id"
                :label="item.title"
                :value="item.id"
              />
            </el-select>
            <div v-if="transferMerchantQuickTargets.length" class="bridge-target-quick">
              <span class="bridge-target-quick__label">常用目标</span>
              <button
                v-for="item in transferMerchantQuickTargets"
                :key="`bridge-${item.id}`"
                class="transfer-target-chip"
                type="button"
                :class="{ 'transfer-target-chip--active': Number(target_merchant_id) === Number(item.id) }"
                @click="applyTransferTargetMerchant(item.id)"
              >
                {{ item.title }}
              </button>
            </div>
          </div>
          <div class="bridge-control bridge-control--actions">
            <label>执行动作</label>
            <div class="bridge-action-row">
              <el-button :disabled="!selection.length" @click="clearSelection">清空勾选</el-button>
              <el-button plain @click="resetBridgePreferences">清除迁移偏好</el-button>
              <el-button
                :disabled="!selection.length"
                type="primary"
                @click="submitBridgeAction"
              >
                {{ bridgeActionLabel }}
              </el-button>
            </div>
          </div>
        </div>

        <div v-if="operationWorkbenchExpanded && bridgeQuickTargets.length" class="bridge-target-switch">
          <div class="bridge-target-switch__label">快捷切换常用迁移目标</div>
          <div class="bridge-target-switch__items">
            <button
              v-for="item in bridgeQuickTargets"
              :key="`switch-${item.key}`"
              class="transfer-target-chip"
              type="button"
              :class="{
                'transfer-target-chip--active':
                  item.type === 'platform'
                    ? bridgeMode === 'platform'
                    : bridgeMode === 'merchant' && Number(target_merchant_id) === Number(item.id)
              }"
              @click="activateBridgeTarget(item)"
            >
              {{ item.title }}
            </button>
          </div>
        </div>

        <div v-if="operationWorkbenchExpanded" class="bridge-steps">
          <div v-for="item in bridgeSteps" :key="item.step" class="bridge-step">
            <div class="bridge-step__index">{{ item.step }}</div>
            <div class="bridge-step__title">{{ item.title }}</div>
            <div class="bridge-step__desc">{{ item.desc }}</div>
          </div>
        </div>

        <div v-if="operationWorkbenchExpanded" class="online-actions online-actions--selected">
          <button
            class="online-action"
            type="button"
            :disabled="!selection.length"
            @click="selectOpen('dele')"
          >
            删除
          </button>
          <button
            class="online-action"
            type="button"
            :disabled="!selection.length || !checkPermission(['admin/goods.Goods/transferToPlatform'])"
            @click="selectOpen('transfer_platform')"
          >
            仅迁移已勾选到平台自营
          </button>
          <button
            class="online-action"
            type="button"
            :disabled="!selection.length || !checkPermission(['admin/goods.Goods/transferToMerchant'])"
            @click="openTransferMerchantDialog()"
          >
            仅迁移已勾选到指定商家
          </button>
          <button class="online-action" type="button" :disabled="!selection.length" @click="selectOpen('batch_thumbnail')">
            仅更换已勾选缩略图
          </button>
          <button class="online-action" type="button" :disabled="!selection.length" @click="clearSelection">清空已勾选</button>
        </div>
        <div v-if="operationWorkbenchExpanded" class="selection-stage-bar">
          <div class="selection-stage-bar__summary">
            <div class="selection-stage-bar__title">勾选承接区</div>
            <div class="selection-stage-bar__desc">{{ selectionStageSummary }}</div>
          </div>
          <div class="selection-stage-bar__actions">
            <button
              class="online-action"
              type="button"
              :disabled="!canStageSelectionForCurrentBridge"
              @click="stageSelectionForCurrentBridge"
            >
              {{ selectionStageActionLabel }}
            </button>
            <button
              v-if="targetMerchantLabel"
              class="online-action"
              type="button"
              :disabled="!selection.length || !checkPermission(['admin/goods.Goods/transferToMerchant'])"
              @click="stageSelectionForRememberedMerchant"
            >
              带入已记忆目标
            </button>
          </div>
        </div>

        <div v-if="operationWorkbenchExpanded" class="selection-review-panel" :class="{ 'selection-review-panel--active': selectionReviewPanel.active }">
          <div class="selection-review-panel__header">
            <div>
              <div class="selection-review-panel__eyebrow">勾选批次复核台</div>
              <div class="selection-review-panel__title">{{ selectionReviewPanel.title }}</div>
            </div>
            <el-tag size="small" effect="plain" :type="selectionReviewPanel.tagType">{{ selectionReviewPanel.badge }}</el-tag>
          </div>
          <div class="selection-review-panel__desc">{{ selectionReviewPanel.desc }}</div>
          <div class="selection-review-panel__metrics">
            <div v-for="item in selectionReviewMetrics" :key="item.label" class="selection-review-metric">
              <div class="selection-review-metric__label">{{ item.label }}</div>
              <div class="selection-review-metric__value">{{ item.value }}</div>
              <div class="selection-review-metric__desc">{{ item.desc }}</div>
            </div>
          </div>
          <div v-if="selectionLatestOperationLink.active" class="selection-review-link">
            <div class="selection-review-link__main">
              <div class="selection-review-link__title">{{ selectionLatestOperationLink.title }}</div>
              <div class="selection-review-link__desc">{{ selectionLatestOperationLink.desc }}</div>
            </div>
            <div class="selection-review-link__tags">
              <span v-for="item in selectionLatestOperationLink.tags" :key="item">{{ item }}</span>
            </div>
            <div class="selection-review-link__actions">
              <button
                class="online-action"
                type="button"
                :disabled="!selectionLatestOperationLink.canStageOverlap"
                @click="stageSelectionLatestOperationOverlap"
              >
                承接重叠到工具
              </button>
              <button
                class="online-action"
                type="button"
                :disabled="!selectionLatestOperationLink.pendingOverlapIds.length"
                @click="keepSelectionLatestPendingOverlapIds"
              >
                仅保留重叠待复核
              </button>
              <button
                class="online-action"
                type="button"
                :disabled="!selectionLatestOperationLink.overlapIds.length"
                @click="copySelectionLatestOperationOverlapIds"
              >
                复制重叠商品 ID
              </button>
              <button
                class="online-action"
                type="button"
                :disabled="!latestOperation"
                @click="focusOperationFirstGoods(latestOperation)"
              >
                定位最近首个商品
              </button>
              <button
                v-if="latestOperation?.querySnapshot"
                class="online-action"
                type="button"
                @click="restoreOperationFilters(latestOperation)"
              >
                恢复最近筛选
              </button>
              <button
                v-if="latestOperation?.filterable"
                class="online-action"
                type="button"
                @click="applyOperationMerchantFilter(latestOperation)"
              >
                {{ latestOperation.filterLabel }}
              </button>
            </div>
          </div>
          <div v-if="selectionReviewWarnings.length" class="selection-review-panel__warnings">
            <div v-for="item in selectionReviewWarnings" :key="item" class="selection-review-warning">{{ item }}</div>
          </div>
          <div v-if="selectionReviewFocus.active" class="selection-review-focus">
            <div class="selection-review-focus__header">
              <div>
                <div class="selection-review-focus__title">{{ selectionReviewFocus.title }}</div>
                <div class="selection-review-focus__desc">{{ selectionReviewFocus.desc }}</div>
              </div>
              <el-tag size="small" effect="plain" :type="selectionReviewFocus.tagType">{{ selectionReviewFocus.badge }}</el-tag>
            </div>
            <div class="selection-review-focus__items">
              <div
                v-for="item in selectionReviewFocus.items"
                :key="item.label"
                class="selection-review-focus__item"
                :class="`selection-review-focus__item--${item.tone}`"
              >
                <div class="selection-review-focus__item-label">{{ item.label }}</div>
                <div class="selection-review-focus__item-value">{{ item.value }}</div>
                <div class="selection-review-focus__item-desc">{{ item.desc }}</div>
              </div>
            </div>
          </div>
          <div class="selection-review-panel__actions">
            <button
              class="online-action"
              type="button"
              :disabled="!selectionPendingReviewCount"
              @click="reviewSelectedPendingGoods"
            >
              复核待审核勾选
            </button>
            <button
              class="online-action"
              type="button"
              :disabled="!selectionOfflineCount"
              @click="bringSelectedOfflineGoodsOnline"
            >
              仅上架勾选下架商品
            </button>
            <button
              class="online-action"
              type="button"
              :disabled="!canStageSelectionForCurrentBridge"
              @click="stageSelectionForCurrentBridge"
            >
              带入当前工具继续处理
            </button>
            <button
              v-if="stagedBridgeSummary.active"
              class="online-action"
              type="button"
              :disabled="!bridgeStageMergePanel.canMerge"
              @click="mergeSelectionIntoStagedBridge()"
            >
              合并勾选进承接
            </button>
            <button
              v-if="stagedBridgeSummary.active"
              class="online-action"
              type="button"
              :disabled="!bridgeStageMergePanel.canAppendDelta"
              @click="mergeSelectionIntoStagedBridge({ onlyDelta: true })"
            >
              仅追加新增勾选
            </button>
            <button
              v-if="stagedBridgeSummary.active"
              class="online-action"
              type="button"
              @click="submitBridgeAction"
            >
              继续迁移
            </button>
            <button
              v-if="stagedBridgeSummary.active"
              class="online-action"
              type="button"
              @click="copyCurrentTransferTargetIds"
            >
              复制当前复核ID
            </button>
            <button
              v-if="stagedBridgeSummary.active"
              class="online-action"
              type="button"
              @click="clearBridgeStaging"
            >
              清空承接
            </button>
            <button
              v-if="stagedBridgeSnapshotAvailable"
              class="online-action"
              type="button"
              @click="restoreStagedBridgeSnapshot"
            >
              恢复最近承接
            </button>
            <button
              v-if="stagedBridgeSummary.active"
              class="online-action"
              type="button"
              :disabled="!canApplyCurrentBridgeTargetFilter"
              @click="applyCurrentBridgeTargetFilter"
            >
              按当前目标复核
            </button>
          </div>
        </div>

        <div v-if="operationWorkbenchExpanded" class="bridge-summary">
          <div class="bridge-summary__title">当前批量处理对象</div>
          <div class="bridge-summary__tags">
            <el-tag effect="plain">已勾选 {{ selection.length }} 件</el-tag>
            <el-tag effect="plain" type="info">当前页 {{ data.length }} 件</el-tag>
            <el-tag effect="plain" type="success">{{ bridgeSummaryLabel }}</el-tag>
            <el-tag v-if="bridgePreferenceLabel" effect="plain" type="info">
              {{ bridgePreferenceLabel }}
            </el-tag>
            <el-tag v-if="targetMerchantLabel" effect="plain" type="warning">
              目标商家：{{ targetMerchantLabel }}
            </el-tag>
          </div>
          <div class="bridge-summary__items">
            <span v-for="item in selectedTitlesPreview" :key="item">{{ item }}</span>
            <span v-if="!selectedTitlesPreview.length">暂未勾选商品，请先在表格中选择需要操作的商品。</span>
          </div>
        </div>

        <div v-if="operationWorkbenchExpanded" class="bridge-command-center">
          <div class="bridge-command-card" :class="`bridge-command-card--${bridgeNextStep.tone}`">
            <div class="bridge-command-card__header">
              <div>
                <div class="bridge-command-card__eyebrow">运营下一步</div>
                <div class="bridge-command-card__title">{{ bridgeNextStep.title }}</div>
              </div>
              <el-tag size="small" effect="plain" :type="bridgeNextStep.tagType">{{ bridgeNextStep.badge }}</el-tag>
            </div>
            <div class="bridge-command-card__desc">{{ bridgeNextStep.desc }}</div>
            <div class="bridge-command-card__checklist">
              <div
                v-for="item in bridgeScopeChecklist"
                :key="item.label"
                class="bridge-command-check"
                :class="{ 'bridge-command-check--done': item.done }"
              >
                <span class="bridge-command-check__status">{{ item.done ? '已就绪' : '待处理' }}</span>
                <span class="bridge-command-check__label">{{ item.label }}</span>
              </div>
            </div>
            <div class="bridge-command-card__actions">
              <el-button
                v-if="bridgeNextStep.action === 'stage'"
                size="small"
                type="primary"
                :disabled="!canStageSelectionForCurrentBridge"
                @click="stageSelectionForCurrentBridge"
              >
                带入当前勾选
              </el-button>
              <el-button
                v-else-if="bridgeNextStep.action === 'submit'"
                size="small"
                type="primary"
                :disabled="!stagedBridgeSummary.active"
                @click="submitBridgeAction"
              >
                继续迁移
              </el-button>
              <el-button
                v-else-if="bridgeNextStep.action === 'rollback'"
                size="small"
                type="primary"
                :disabled="!canNavigateFilterBack"
                @click="navigateFilterStep(-1)"
              >
                回退当前筛选
              </el-button>
              <el-button
                v-if="latestRecentFilter"
                size="small"
                plain
                @click="restoreRecentFilter(latestRecentFilter)"
              >
                恢复最近筛选
              </el-button>
              <el-button
                v-if="bridgeMode === 'merchant' && targetMerchantLabel"
                size="small"
                plain
                @click="applyCurrentBridgeTargetFilter"
              >
                过滤当前目标
              </el-button>
            </div>
          </div>

          <div class="bridge-command-card bridge-command-card--timeline">
            <div class="bridge-command-card__header">
              <div>
                <div class="bridge-command-card__eyebrow">筛选变更轨迹</div>
                <div class="bridge-command-card__title">{{ filterTimelineStatusLabel }}</div>
              </div>
              <el-tag size="small" effect="plain" type="info">可逐步回退</el-tag>
            </div>
            <div class="bridge-command-card__desc">
              当前迁移区会直接沿用商品列表的筛选时间线，适合先缩小范围、迁移、再按步骤回退复核。
            </div>
            <div class="bridge-timeline-strip">
              <div class="bridge-timeline-strip__item">
                <span class="bridge-timeline-strip__label">上一步</span>
                <strong>{{ canNavigateFilterBack ? '可回退' : '已到起点' }}</strong>
              </div>
              <div class="bridge-timeline-strip__item">
                <span class="bridge-timeline-strip__label">下一步</span>
                <strong>{{ canNavigateFilterForward ? '可前进' : '暂无前进' }}</strong>
              </div>
              <div class="bridge-timeline-strip__item">
                <span class="bridge-timeline-strip__label">最近条件</span>
                <strong>{{ latestRecentFilter ? latestRecentFilter.label : '默认筛选条件' }}</strong>
              </div>
            </div>
            <div class="bridge-command-card__actions">
              <el-button size="small" plain :disabled="!canNavigateFilterBack" @click="navigateFilterStep(-1)">回退当前筛选</el-button>
              <el-button size="small" plain :disabled="!canNavigateFilterForward" @click="navigateFilterStep(1)">前进到下一步</el-button>
              <el-button size="small" plain :disabled="!latestRecentFilter" @click="restoreRecentFilter(latestRecentFilter)">恢复最近筛选</el-button>
            </div>
          </div>
        </div>

        <div v-if="operationWorkbenchExpanded" class="bridge-insight-grid">
          <div class="bridge-insight-card">
            <div class="bridge-insight-card__title">迁移前归属分布</div>
            <div class="bridge-insight-card__value">{{ selectionOwnershipSummary.label }}</div>
            <div class="bridge-insight-card__desc">{{ selectionOwnershipSummary.desc }}</div>
          </div>
          <div class="bridge-insight-card">
            <div class="bridge-insight-card__title">迁移后归属预判</div>
            <div class="bridge-insight-card__value">{{ bridgeTargetPreview.title }}</div>
            <div class="bridge-insight-card__desc">{{ bridgeTargetPreview.desc }}</div>
          </div>
          <div class="bridge-insight-card" :class="{ 'bridge-insight-card--active': stagedBridgeSummary.active }">
            <div class="bridge-insight-card__title">当前承接批次</div>
            <div class="bridge-insight-card__value">{{ stagedBridgeSummary.title }}</div>
            <div class="bridge-insight-card__desc">{{ stagedBridgeSummary.desc }}</div>
            <div v-if="stagedBridgeSummary.tags.length" class="bridge-insight-card__tags">
              <el-tag
                v-for="item in stagedBridgeSummary.tags"
                :key="item"
                size="small"
                effect="plain"
                type="warning"
              >
                {{ item }}
              </el-tag>
            </div>
            <div class="bridge-insight-card__actions">
              <el-button size="small" type="primary" :disabled="!stagedBridgeSummary.active" @click="submitBridgeAction">
                继续迁移
              </el-button>
              <el-button size="small" plain :disabled="!stagedBridgeSnapshotAvailable" @click="restoreStagedBridgeSnapshot">
                恢复最近承接
              </el-button>
              <el-button size="small" plain :disabled="!stagedBridgeSummary.active" @click="clearBridgeStaging">
                清空承接
              </el-button>
              <el-button size="small" plain :disabled="!canApplyCurrentBridgeTargetFilter" @click="applyCurrentBridgeTargetFilter">
                按目标归属筛选
              </el-button>
            </div>
          </div>
          <div class="bridge-insight-card" :class="{ 'bridge-insight-card--active': bridgeStageMergePanel.active }">
            <div class="bridge-insight-card__title">承接合流台</div>
            <div class="bridge-insight-card__value">{{ bridgeStageMergePanel.title }}</div>
            <div class="bridge-insight-card__desc">{{ bridgeStageMergePanel.desc }}</div>
            <div v-if="bridgeStageMergePanel.tags.length" class="bridge-insight-card__tags">
              <el-tag
                v-for="item in bridgeStageMergePanel.tags"
                :key="item"
                size="small"
                effect="plain"
                type="primary"
              >
                {{ item }}
              </el-tag>
            </div>
            <div class="bridge-insight-card__actions">
              <el-button size="small" type="primary" :disabled="!bridgeStageMergePanel.canMerge" @click="mergeSelectionIntoStagedBridge()">
                合并勾选进承接
              </el-button>
              <el-button size="small" plain :disabled="!bridgeStageMergePanel.canAppendDelta" @click="mergeSelectionIntoStagedBridge({ onlyDelta: true })">
                仅追加新增勾选
              </el-button>
              <el-button size="small" plain :disabled="!bridgeStageMergePanel.selectionOnlyIds.length" @click="copyBridgeSelectionDeltaIds">
                复制新增ID
              </el-button>
            </div>
          </div>
          <div class="bridge-insight-card" :class="{ 'bridge-insight-card--active': bridgeBattlePanel.active }">
            <div class="bridge-insight-card__title">批次作战面板</div>
            <div class="bridge-insight-card__value">{{ bridgeBattlePanel.title }}</div>
            <div class="bridge-insight-card__desc">{{ bridgeBattlePanel.desc }}</div>
            <div v-if="bridgeBattlePanel.tags.length" class="bridge-insight-card__tags">
              <el-tag
                v-for="item in bridgeBattlePanel.tags"
                :key="item"
                size="small"
                effect="plain"
                type="info"
              >
                {{ item }}
              </el-tag>
            </div>
            <div class="bridge-insight-card__actions">
              <el-button size="small" type="primary" :disabled="!canStageSelectionForCurrentBridge" @click="stageSelectionForCurrentBridge">
                带入当前勾选
              </el-button>
              <el-button size="small" plain :disabled="!canApplyCurrentBridgeTargetFilter" @click="applyCurrentBridgeTargetFilter">
                过滤当前目标
              </el-button>
              <el-button size="small" plain :disabled="!latestOperation?.querySnapshot" @click="restoreOperationFilters(latestOperation)">
                恢复最近筛选
              </el-button>
            </div>
          </div>
          <div class="bridge-insight-card" :class="{ 'bridge-insight-card--active': latestTransferReviewPanel.active }">
            <div class="bridge-insight-card__title">最近迁移复核</div>
            <div class="bridge-insight-card__value">{{ latestTransferReviewPanel.title }}</div>
            <div class="bridge-insight-card__desc">{{ latestTransferReviewPanel.desc }}</div>
          <div v-if="latestTransferReviewPanel.tags.length" class="bridge-insight-card__tags">
            <el-tag
              v-for="item in latestTransferReviewPanel.tags"
              :key="item"
              size="small"
              effect="plain"
              type="success"
            >
              {{ item }}
            </el-tag>
          </div>
          <div v-if="latestTransferExceptionTracker.active" class="bridge-exception-tracker">
            <div class="bridge-exception-tracker__header">
              <div>
                <div class="bridge-exception-tracker__title">{{ latestTransferExceptionTracker.title }}</div>
                <div class="bridge-exception-tracker__desc">{{ latestTransferExceptionTracker.desc }}</div>
              </div>
              <el-tag size="small" effect="plain" :type="latestTransferExceptionTracker.tagType">{{ latestTransferExceptionTracker.badge }}</el-tag>
            </div>
            <div class="bridge-exception-tracker__grid">
              <div
                v-for="item in latestTransferExceptionTracker.items"
                :key="item.label"
                class="bridge-exception-tracker__item"
                :class="`bridge-exception-tracker__item--${item.tone}`"
              >
                <div class="bridge-exception-tracker__label">{{ item.label }}</div>
                <div class="bridge-exception-tracker__value">{{ item.value }}</div>
                <div class="bridge-exception-tracker__desc">{{ item.desc }}</div>
              </div>
            </div>
            <div v-if="latestTransferExceptionTracker.alerts.length" class="bridge-exception-tracker__alerts">
              <span v-for="item in latestTransferExceptionTracker.alerts" :key="item">{{ item }}</span>
            </div>
            <div v-if="latestTransferExceptionTracker.sources.length" class="bridge-exception-tracker__filters">
              <button
                v-for="item in latestTransferExceptionTracker.sources"
                :key="item.key"
                class="bridge-exception-tracker__filter"
                :class="{ 'bridge-exception-tracker__filter--active': latestTransferExceptionActiveSource.key === item.key }"
                type="button"
                @click="setLatestTransferExceptionFilter(item.key)"
              >
                <span>{{ item.label }}</span>
                <strong>{{ item.count }}</strong>
              </button>
            </div>
            <div v-if="latestTransferExceptionActiveSource.key" class="bridge-exception-tracker__focus">
              <div class="bridge-exception-tracker__focus-main">
                <div class="bridge-exception-tracker__focus-title">{{ latestTransferExceptionActiveSource.title }}</div>
                <div class="bridge-exception-tracker__focus-desc">{{ latestTransferExceptionActiveSource.desc }}</div>
              </div>
              <div class="bridge-exception-tracker__focus-actions">
                <el-button
                  size="small"
                  plain
                  :disabled="!latestTransferExceptionActiveSource.count"
                  @click="trackLatestTransferExceptionSource(latestTransferExceptionActiveSource.key)"
                >
                  承接当前来源
                </el-button>
                <el-button
                  size="small"
                  plain
                  :disabled="!latestTransferExceptionActiveSource.count"
                  @click="copyLatestTransferExceptionSourceIds(latestTransferExceptionActiveSource.key)"
                >
                  复制当前来源ID
                </el-button>
              </div>
            </div>
          </div>
          <div class="bridge-insight-card__actions">
            <el-button size="small" plain :disabled="!latestTransferOperation?.merchant_id && latestTransferOperation?.merchant_id !== 0" @click="applyOperationMerchantFilter(latestTransferOperation)">
              筛选最近目标
            </el-button>
            <el-button size="small" plain :disabled="!latestTransferOperation?.querySnapshot" @click="restoreOperationFilters(latestTransferOperation)">
              恢复迁移前筛选
            </el-button>
            <el-button size="small" plain :disabled="!latestTransferExceptionTracker.canTrackUnchanged" @click="trackLatestTransferExceptionBatch('unchanged')">
              追踪未变化批次
            </el-button>
            <el-button size="small" plain :disabled="!latestTransferExceptionTracker.canTrackChanged" @click="trackLatestTransferExceptionBatch('changed')">
              追踪已变化批次
            </el-button>
            <el-button size="small" type="primary" plain :disabled="!canReuseOperationTransfer(latestTransferOperation)" @click="reuseOperationTransfer(latestTransferOperation)">
              再次发起该批次
            </el-button>
          </div>
        </div>
          <div class="bridge-insight-card" :class="{ 'bridge-insight-card--active': bridgeTargetLibrary.some((item) => item.active) }">
            <div class="bridge-insight-card__title">常用目标库</div>
            <div class="bridge-insight-card__value">{{ bridgeTargetLibraryTitle }}</div>
            <div class="bridge-insight-card__desc">{{ bridgeTargetLibraryDesc }}</div>
            <div class="bridge-target-library">
              <button
                v-for="item in bridgeTargetLibrary"
                :key="item.key"
                class="bridge-target-library__item"
                :class="{ 'bridge-target-library__item--active': item.active }"
                type="button"
                @click="activateBridgeTarget(item)"
              >
                <span class="bridge-target-library__title">{{ item.title }}</span>
                <span class="bridge-target-library__meta">{{ item.meta }}</span>
              </button>
            </div>
            <div class="bridge-insight-card__actions">
              <el-button size="small" type="primary" :disabled="!bridgeTargetLibrary.length" @click="submitBridgeAction">
                用当前目标继续迁移
              </el-button>
              <el-button size="small" plain :disabled="!canApplyCurrentBridgeTargetFilter" @click="applyCurrentBridgeTargetFilter">
                按当前目标复核
              </el-button>
            </div>
          </div>
        </div>

        <div v-if="operationWorkbenchExpanded" class="bridge-risk-panel">
          <div class="bridge-risk-panel__title">操作风险提示</div>
          <div class="bridge-risk-panel__items">
            <span v-for="item in bridgeRiskNotes" :key="item">{{ item }}</span>
          </div>
        </div>

        <div v-if="operationWorkbenchExpanded" class="bridge-permissions">
          <div class="bridge-permissions__title">迁移权限状态</div>
          <div class="bridge-permissions__items">
            <div
              v-for="item in permissionCards"
              :key="item.key"
              class="bridge-permissions__item"
              :class="{ 'bridge-permissions__item--disabled': !item.enabled }"
            >
              <div class="bridge-permissions__label">{{ item.label }}</div>
              <div class="bridge-permissions__desc">{{ item.desc }}</div>
            </div>
          </div>
        </div>

        <div v-if="latestOperation && operationWorkbenchExpanded" class="bridge-feedback">
          <div class="bridge-feedback__header">
            <div class="bridge-feedback__title">最近一次操作回显</div>
            <el-tag :type="latestOperation.tone" effect="plain">{{ latestOperation.label }}</el-tag>
          </div>
          <div class="bridge-feedback__meta">
            <span>处理数量：{{ latestOperation.count }} 件</span>
            <span v-if="latestOperation.target">目标：{{ latestOperation.target }}</span>
            <span>完成时间：{{ latestOperation.time }}</span>
          </div>
          <div class="bridge-feedback__message">{{ latestOperation.message }}</div>
          <div class="bridge-feedback__actions">
            <el-button size="small" @click="focusOperationFirstGoods(latestOperation)">定位首个商品</el-button>
            <el-button
              v-if="latestOperation.ids && latestOperation.ids.length"
              size="small"
              plain
              @click="copyOperationIds(latestOperation)"
            >
              复制商品 ID
            </el-button>
            <el-button
              v-if="latestOperation.filterable"
              size="small"
              type="primary"
              plain
              @click="applyOperationMerchantFilter(latestOperation)"
            >
              {{ latestOperation.filterLabel }}
            </el-button>
            <el-button
              v-if="latestOperation.querySnapshot"
              size="small"
              plain
              @click="restoreOperationFilters(latestOperation)"
            >
              恢复操作前筛选
            </el-button>
            <el-button
              v-if="latestOperation.revertable"
              size="small"
              type="warning"
              plain
              @click="revertOperation(latestOperation)"
            >
              {{ latestOperation.revertLabel }}
            </el-button>
            <el-button
              v-if="canReuseOperationTransfer(latestOperation)"
              size="small"
              type="success"
              plain
              @click="reuseOperationTransfer(latestOperation)"
            >
              再次发起迁移
            </el-button>
          </div>
          <div v-if="latestOperation.querySnapshotLabel" class="bridge-feedback__snapshot">
            操作前筛选：{{ latestOperation.querySnapshotLabel }}
          </div>
          <div v-if="latestOperation.diff" class="operation-diff">
            <div class="operation-diff__header">
              <div class="operation-diff__title">{{ latestOperation.diffTitle || '处理结果摘要' }}</div>
              <el-tag
                :type="latestOperation.diff.unchangedCount ? 'warning' : 'success'"
                effect="plain"
              >
                变化 {{ latestOperation.diff.changedCount }} 件
              </el-tag>
            </div>
            <div class="operation-diff__grid">
              <div class="operation-diff__card">
                <div class="operation-diff__label">迁移前</div>
                <div class="operation-diff__value">{{ latestOperation.diff.beforeLabel }}</div>
                <div class="operation-diff__desc">{{ latestOperation.diff.beforeDesc }}</div>
              </div>
              <div class="operation-diff__card operation-diff__card--accent">
                <div class="operation-diff__label">迁移后</div>
                <div class="operation-diff__value">{{ latestOperation.diff.afterLabel }}</div>
                <div class="operation-diff__desc">{{ latestOperation.diff.afterDesc }}</div>
              </div>
              <div class="operation-diff__card">
                <div class="operation-diff__label">{{ latestOperation.diff.summaryLabel || '变化摘要' }}</div>
                <div class="operation-diff__value">{{ latestOperation.diff.summaryValue || `${latestOperation.diff.changedCount} 件发生变化` }}</div>
                <div class="operation-diff__desc">{{ latestOperation.diff.summary }}</div>
              </div>
            </div>
          </div>
          <div v-if="latestTransferExceptionTracker.active" class="bridge-feedback__tracker">
            <div class="bridge-feedback__tracker-header">
              <div>
                <div class="bridge-feedback__tracker-title">{{ latestTransferExceptionTracker.title }}</div>
                <div class="bridge-feedback__tracker-desc">{{ latestTransferExceptionTracker.desc }}</div>
              </div>
              <el-tag size="small" effect="plain" :type="latestTransferExceptionTracker.tagType">{{ latestTransferExceptionTracker.badge }}</el-tag>
            </div>
            <div class="bridge-feedback__tracker-actions">
              <el-button size="small" plain :disabled="!latestTransferExceptionTracker.canTrackUnchanged" @click="trackLatestTransferExceptionBatch('unchanged')">
                承接未变化批次
              </el-button>
              <el-button size="small" plain :disabled="!latestTransferExceptionTracker.canTrackChanged" @click="trackLatestTransferExceptionBatch('changed')">
                承接已变化批次
              </el-button>
              <el-button size="small" plain :disabled="!latestTransferExceptionTracker.canCopyUnchanged" @click="copyLatestTransferUnchangedIds">
                复制未变化ID
              </el-button>
              <el-button size="small" plain :disabled="!latestTransferExceptionTracker.canCopyChanged" @click="copyLatestTransferChangedIds">
                复制已变化ID
              </el-button>
              <el-button size="small" plain :disabled="!latestTransferOperation?.querySnapshot" @click="restoreOperationFilters(latestTransferOperation)">
                回到迁移前筛选
              </el-button>
            </div>
          </div>
        </div>

        <div v-if="operationHistory.length && operationWorkbenchExpanded" class="bridge-history">
          <div class="bridge-history__header">
            <div class="bridge-history__heading">
              <div class="bridge-history__title">最近操作记录</div>
              <div class="bridge-history__stats">
                <el-tag effect="plain" type="info">保留 {{ operationHistory.length }} 条</el-tag>
                <el-tag effect="plain" type="success">命中 {{ filteredOperationHistory.length }} 条</el-tag>
                <el-tag effect="plain" type="warning">累计处理 {{ operationHistorySummary.totalCount }} 件</el-tag>
              </div>
            </div>
            <div class="bridge-history__toolbar">
              <el-select
                v-model="operationHistoryType"
                size="small"
                clearable
                class="bridge-history__filter"
                placeholder="操作类型"
              >
                <el-option
                  v-for="item in operationHistoryTypeOptions"
                  :key="item.value"
                  :label="item.label"
                  :value="item.value"
                />
              </el-select>
              <el-input
                v-model="operationHistoryKeyword"
                size="small"
                clearable
                class="bridge-history__search"
                placeholder="搜索目标/商品ID/结果说明"
              />
              <el-button size="small" plain @click="clearOperationHistory">
                清空记录
              </el-button>
              <el-button size="small" type="primary" plain @click="exportOperationSummary">
                导出操作摘要
              </el-button>
            </div>
          </div>
          <div class="bridge-history-overview">
            <div
              v-for="item in operationHistoryOverviewCards"
              :key="item.key"
              class="bridge-history-overview__card"
              :class="`bridge-history-overview__card--${item.tone}`"
            >
              <div class="bridge-history-overview__label">{{ item.label }}</div>
              <div class="bridge-history-overview__value">{{ item.value }}</div>
              <div class="bridge-history-overview__desc">{{ item.desc }}</div>
            </div>
          </div>
          <div v-if="priorityOperationCards.length" class="bridge-history-priority">
            <div
              v-for="item in priorityOperationCards"
              :key="item.key"
              class="bridge-history-priority__card"
              :class="`bridge-history-priority__card--${item.tone}`"
            >
              <div class="bridge-history-priority__header">
                <div>
                  <div class="bridge-history-priority__title">{{ item.title }}</div>
                  <div class="bridge-history-priority__meta">{{ item.meta }}</div>
                </div>
                <el-tag size="small" effect="plain" :type="item.tone">{{ item.badge }}</el-tag>
              </div>
              <div class="bridge-history-priority__desc">{{ item.desc }}</div>
              <div class="bridge-history-priority__tips">
                <span v-for="tip in item.tips" :key="tip">{{ tip }}</span>
              </div>
              <div v-if="item.alerts.length" class="bridge-history-priority__alerts">
                <el-tag
                  v-for="alert in item.alerts"
                  :key="alert"
                  size="small"
                  effect="plain"
                  type="danger"
                >
                  {{ alert }}
                </el-tag>
              </div>
              <div class="bridge-history-priority__actions">
                <el-button size="small" text @click="focusOperationFirstGoods(item.operation)">定位</el-button>
                <el-button
                  v-if="item.operation.ids && item.operation.ids.length"
                  size="small"
                  text
                  @click="copyOperationIds(item.operation)"
                >
                  复制ID
                </el-button>
                <el-button
                  v-if="item.operation.filterable"
                  size="small"
                  text
                  type="primary"
                  @click="applyOperationMerchantFilter(item.operation)"
                >
                  筛选归属
                </el-button>
                <el-button
                  v-if="item.operation.querySnapshot"
                  size="small"
                  text
                  @click="restoreOperationFilters(item.operation)"
                >
                  恢复筛选
                </el-button>
                <el-button
                  v-if="item.operation.revertable"
                  size="small"
                  text
                  type="warning"
                  @click="revertOperation(item.operation)"
                >
                  回退
                </el-button>
                <el-button
                  v-if="canStageOperationTransfer(item.operation)"
                  size="small"
                  text
                  type="primary"
                  @click="stageOperationTransfer(item.operation)"
                >
                  带入工具
                </el-button>
                <el-button
                  v-if="canReuseOperationTransfer(item.operation)"
                  size="small"
                  text
                  type="success"
                  @click="reuseOperationTransfer(item.operation)"
                >
                  再次迁移
                </el-button>
              </div>
            </div>
          </div>
          <div class="bridge-history__items">
            <div
              v-for="group in groupedOperationHistory"
              :key="group.key"
              class="bridge-history-group"
            >
              <div class="bridge-history-group__header">
                <div class="bridge-history-group__title">{{ group.label }}</div>
                <div class="bridge-history-group__meta">
                  <el-tag size="small" effect="plain" :type="group.tone">{{ group.count }} 条</el-tag>
                  <span>{{ group.desc }}</span>
                </div>
              </div>
              <div class="bridge-history-group__items">
                <div v-for="item in group.items" :key="item.key" class="bridge-history__item">
                  <div class="bridge-history__main">
                    <div class="bridge-history__label">{{ item.label }}</div>
                    <div class="bridge-history__desc">
                      {{ item.time }} · {{ item.count }} 件
                      <span v-if="item.target">· {{ item.target }}</span>
                    </div>
                    <div v-if="item.diff" class="bridge-history__diff">
                      {{ item.diff.summary }}
                    </div>
                    <div v-if="item.diff" class="bridge-history__stats-inline">
                      <el-tag size="small" effect="plain" type="success">变化 {{ item.diff.changedCount }} 件</el-tag>
                      <el-tag
                        v-if="item.diff.unchangedCount"
                        size="small"
                        effect="plain"
                        type="warning"
                      >
                        未变化 {{ item.diff.unchangedCount }} 件
                      </el-tag>
                    </div>
                  </div>
                  <div class="bridge-history__actions">
                    <el-button size="small" text @click="focusOperationFirstGoods(item)">定位</el-button>
                    <el-button
                      v-if="item.ids && item.ids.length"
                      size="small"
                      text
                      @click="copyOperationIds(item)"
                    >
                      复制ID
                    </el-button>
                    <el-button
                      v-if="item.filterable"
                      size="small"
                      text
                      type="primary"
                      @click="applyOperationMerchantFilter(item)"
                    >
                      筛选归属
                    </el-button>
                    <el-button
                      v-if="item.querySnapshot"
                      size="small"
                      text
                      @click="restoreOperationFilters(item)"
                    >
                      恢复筛选
                    </el-button>
                    <el-button
                      v-if="item.revertable"
                      size="small"
                      text
                      type="warning"
                      @click="revertOperation(item)"
                    >
                      回退
                    </el-button>
                    <el-button
                      v-if="canReuseOperationTransfer(item)"
                      size="small"
                      text
                      type="success"
                      @click="reuseOperationTransfer(item)"
                    >
                      再次迁移
                    </el-button>
                  </div>
                </div>
              </div>
            </div>
            <div v-if="!filteredOperationHistory.length" class="bridge-history__empty">
              当前筛选条件下没有匹配的操作记录，换个关键词或操作类型试试。
            </div>
          </div>
        </div>
      </div>
    </el-card>

    <div class="metrics-grid">
      <el-card
        v-for="item in summaryCards"
        :key="item.key"
        class="metric-card"
        :class="`metric-card--${item.tone}`"
        shadow="never"
      >
        <div class="metric-card__label">{{ item.label }}</div>
        <div class="metric-card__value">{{ item.value }}</div>
        <div class="metric-card__meta">{{ item.meta }}</div>
      </el-card>
    </div>

    <el-card v-if="false && legacyEnhancementPanelEnabled" class="panel" shadow="never">
      <div class="panel__header-bar">
        <div>
          <div class="panel__sub-title">商品管理增强区</div>
          <div class="panel__sub-desc">把商品流转、复核和高频批量操作固定在筛选区上方，方便持续运营。</div>
        </div>
      </div>

      <div class="action-groups">
        <div v-for="group in actionGroups" :key="`visible-${group.key}`" class="action-group">
          <div class="action-group__title">{{ group.title }}</div>
          <div class="action-group__desc">{{ group.desc }}</div>
          <div class="action-group__items">
            <button
              v-for="item in group.items"
              :key="`visible-${group.key}-${item.key}`"
              class="action-group__item"
              type="button"
              :disabled="item.disabled"
              @click="item.action"
            >
              {{ item.label }}
            </button>
          </div>
        </div>
      </div>

      <div class="batch-bridge">
        <div class="batch-bridge__intro">
          <div class="batch-bridge__title">批量迁移工具</div>
          <div class="batch-bridge__desc">
            先缩小筛选范围，再决定按勾选商品迁移到平台或商家，提交前可直接在这里完成归属复核。
          </div>
        </div>

        <div class="batch-bridge__controls">
          <div class="bridge-control">
            <label>迁移模式</label>
            <el-radio-group v-model="bridgeMode">
              <el-radio-button label="platform">迁移到平台</el-radio-button>
              <el-radio-button label="merchant">迁移到商家</el-radio-button>
            </el-radio-group>
          </div>
          <div v-if="false && bridgeMode === 'merchant'" class="bridge-control bridge-control--merchant">
            <label>目标商家</label>
            <el-select
              v-model="target_merchant_id"
              class="bridge-merchant-select"
              clearable
              filterable
              popper-class="bridge-merchant-dropdown"
              placeholder="请选择目标商家"
            >
              <el-option
                v-for="item in merchantOptions"
                :key="`visible-merchant-${item.id}`"
                :label="item.title"
                :value="item.id"
              />
            </el-select>
          </div>
          <div class="bridge-control bridge-control--actions">
            <label>执行动作</label>
            <div class="bridge-action-row">
              <el-button :disabled="!selection.length" @click="clearSelection">清空勾选</el-button>
              <el-button plain @click="resetBridgePreferences">清除迁移偏好</el-button>
              <el-button
                :disabled="bridgeMode === 'merchant' ? !targetMerchantLabel || !selection.length : !selection.length"
                type="primary"
                @click="submitBridgeAction"
              >
                {{ bridgeActionLabel }}
              </el-button>
            </div>
          </div>
        </div>

        <div class="online-actions online-actions--selected">
          <button
            class="online-action"
            type="button"
            :disabled="!selection.length"
            @click="selectOpen('dele')"
          >
            删除
          </button>
          <button
            class="online-action"
            type="button"
            :disabled="!selection.length || !checkPermission(['admin/goods.Goods/transferToPlatform'])"
            @click="selectOpen('transfer_platform')"
          >
            仅迁移已勾选到平台自营
          </button>
          <button
            class="online-action"
            type="button"
            :disabled="!selection.length || !checkPermission(['admin/goods.Goods/transferToMerchant'])"
            @click="openTransferMerchantDialog()"
          >
            仅迁移已勾选到指定商家
          </button>
          <button class="online-action" type="button" :disabled="!canStageSelectionForCurrentBridge" @click="stageSelectionForCurrentBridge">
            {{ selectionStageActionLabel }}
          </button>
        </div>

        <div class="bridge-summary">
          <div class="bridge-summary__title">当前批量处理对象</div>
          <div class="bridge-summary__tags">
            <el-tag effect="plain">已勾选 {{ selection.length }} 件</el-tag>
            <el-tag effect="plain" type="info">当前页 {{ data.length }} 件</el-tag>
            <el-tag effect="plain" type="success">{{ bridgeSummaryLabel }}</el-tag>
            <el-tag v-if="bridgePreferenceLabel" effect="plain" type="info">
              {{ bridgePreferenceLabel }}
            </el-tag>
            <el-tag v-if="targetMerchantLabel" effect="plain" type="warning">目标商家：{{ targetMerchantLabel }}</el-tag>
            <el-tag v-if="stagedBridgeSummary.active" effect="plain" type="success">
              承接 {{ transferManualIds.length }} 件
            </el-tag>
            <el-tag v-if="transferManualPendingIds.length" effect="plain" type="warning">
              待补查 {{ transferManualPendingIds.length }} 件
            </el-tag>
          </div>
          <div class="bridge-summary__items">
            <span v-for="item in selectedTitlesPreview" :key="`visible-selected-${item}`">{{ item }}</span>
            <span v-if="!selectedTitlesPreview.length">暂未勾选商品，请先在表格中选择需要操作的商品。</span>
          </div>
          <div class="bridge-summary__grid">
            <div class="bridge-summary-card" :class="{ 'bridge-summary-card--active': stagedBridgeSummary.active }">
              <div class="bridge-summary-card__label">当前承接批次</div>
              <div class="bridge-summary-card__value">{{ stagedBridgeSummary.title }}</div>
              <div class="bridge-summary-card__desc">{{ stagedBridgeSummary.desc }}</div>
              <div v-if="stagedBridgeSummary.tags.length" class="bridge-summary-card__tags">
                <span v-for="item in stagedBridgeSummary.tags" :key="`visible-staged-${item}`">{{ item }}</span>
              </div>
            </div>
            <div class="bridge-summary-card">
              <div class="bridge-summary-card__label">筛选变更轨迹</div>
              <div class="bridge-summary-card__value">{{ filterTimelineStatusLabel }}</div>
              <div class="bridge-summary-card__desc">
                {{ canNavigateFilterBack ? '支持逐步回退' : '当前已在筛选起点' }}
                <span v-if="canNavigateFilterForward">，并且还能前进到下一步。</span>
                <span v-else>，当前没有可前进的历史步骤。</span>
              </div>
            </div>
            <div class="bridge-summary-card" :class="{ 'bridge-summary-card--active': Boolean(latestOperation) }">
              <div class="bridge-summary-card__label">最近一次运营结果</div>
              <div class="bridge-summary-card__value">
                {{ latestOperation ? latestOperation.label : '暂无最近操作' }}
              </div>
              <div class="bridge-summary-card__desc">
                {{
                  latestOperation
                    ? `${latestOperation.time} · ${latestOperation.message}`
                    : '完成一次迁移、审核或批量维护后，这里会回显最近结果。'
                }}
              </div>
            </div>
          </div>
          <div
            v-if="transferManualPendingIds.length || transferManualMeta.invalidTokens.length || transferDraftAvailable || stagedBridgeSnapshotAvailable"
            class="bridge-summary__alerts"
          >
            <div v-if="transferManualPendingIds.length" class="bridge-summary-alert bridge-summary-alert--warning">
              <div class="bridge-summary-alert__title">承接批次里还有待补查商品</div>
              <div class="bridge-summary-alert__desc">
                当前待补查 ID：{{ transferManualPendingIds.slice(0, 8).join('，') }}
                <span v-if="transferManualPendingIds.length > 8"> 等 {{ transferManualPendingIds.length }} 个</span>
              </div>
            </div>
            <div v-if="transferManualMeta.invalidTokens.length" class="bridge-summary-alert bridge-summary-alert--danger">
              <div class="bridge-summary-alert__title">检测到无效商品 ID 输入</div>
              <div class="bridge-summary-alert__desc">
                已忽略：{{ transferManualMeta.invalidTokens.slice(0, 6).join('，') }}
                <span v-if="transferManualMeta.invalidTokens.length > 6"> 等 {{ transferManualMeta.invalidTokens.length }} 项</span>
              </div>
            </div>
            <div v-if="transferDraftAvailable" class="bridge-summary-alert">
              <div class="bridge-summary-alert__title">存在可恢复迁移草稿</div>
              <div class="bridge-summary-alert__desc">{{ transferDraftHint }}</div>
            </div>
            <div v-if="stagedBridgeSnapshotAvailable" class="bridge-summary-alert bridge-summary-alert--info">
              <div class="bridge-summary-alert__title">{{ stagedBridgeSnapshotSummary.title }}</div>
              <div class="bridge-summary-alert__desc">{{ stagedBridgeSnapshotSummary.desc }}</div>
            </div>
          </div>
          <div class="bridge-summary__actions">
            <el-button
              v-if="canStageSelectionForCurrentBridge"
              size="small"
              type="primary"
              @click="stageSelectionForCurrentBridge"
            >
              {{ selectionStageActionLabel }}
            </el-button>
            <el-button
              v-if="stagedBridgeSummary.active"
              size="small"
              type="success"
              plain
              @click="submitBridgeAction"
            >
              继续迁移
            </el-button>
            <el-button
              v-if="transferManualCleanupNeeded"
              size="small"
              plain
              @click="keepResolvedTransferManualIds"
            >
              仅保留已识别
            </el-button>
            <el-button
              v-if="transferDraftAvailable"
              size="small"
              plain
              @click="restoreTransferDraft"
            >
              恢复草稿
            </el-button>
            <el-button
              v-if="stagedBridgeSnapshotAvailable"
              size="small"
              plain
              @click="restoreStagedBridgeSnapshot"
            >
              恢复最近承接
            </el-button>
            <el-button
              v-if="stagedBridgeSummary.active"
              size="small"
              plain
              @click="clearBridgeStaging"
            >
              清空承接
            </el-button>
            <el-button
              v-if="canNavigateFilterBack"
              size="small"
              plain
              @click="navigateFilterStep(-1)"
            >
              回退当前筛选
            </el-button>
            <el-button
              v-if="latestRecentFilter"
              size="small"
              plain
              @click="restoreRecentFilter(latestRecentFilter)"
            >
              恢复最近筛选
            </el-button>
          </div>
        </div>

        <div class="bridge-insight-grid">
          <div class="bridge-insight-card">
            <div class="bridge-insight-card__title">迁移前归属分布</div>
            <div class="bridge-insight-card__value">{{ selectionOwnershipSummary.label }}</div>
            <div class="bridge-insight-card__desc">{{ selectionOwnershipSummary.desc }}</div>
          </div>
          <div class="bridge-insight-card">
            <div class="bridge-insight-card__title">迁移后归属预判</div>
            <div class="bridge-insight-card__value">{{ bridgeTargetPreview.title }}</div>
            <div class="bridge-insight-card__desc">{{ bridgeTargetPreview.desc }}</div>
          </div>
        </div>

        <div class="bridge-risk-panel">
          <div class="bridge-risk-panel__title">操作风险提示</div>
          <div class="bridge-risk-panel__items">
            <span v-for="item in bridgeRiskNotes" :key="`visible-risk-${item}`">{{ item }}</span>
          </div>
        </div>

        <div class="bridge-permissions">
          <div class="bridge-permissions__title">迁移权限状态</div>
          <div class="bridge-permissions__items">
            <div
              v-for="item in permissionCards"
              :key="`visible-perm-${item.key}`"
              class="bridge-permissions__item"
              :class="{ 'bridge-permissions__item--disabled': !item.enabled }"
            >
              <div class="bridge-permissions__label">{{ item.label }}</div>
              <div class="bridge-permissions__desc">{{ item.desc }}</div>
            </div>
          </div>
        </div>

        <div v-if="latestOperation && operationWorkbenchExpanded" class="bridge-feedback">
          <div class="bridge-feedback__header">
            <div class="bridge-feedback__title">最近一次操作回显</div>
            <el-tag :type="latestOperation.tone" effect="plain">{{ latestOperation.label }}</el-tag>
          </div>
          <div class="bridge-feedback__meta">
            <span>处理数量：{{ latestOperation.count }} 件</span>
            <span v-if="latestOperation.target">目标：{{ latestOperation.target }}</span>
            <span>完成时间：{{ latestOperation.time }}</span>
          </div>
          <div class="bridge-feedback__message">{{ latestOperation.message }}</div>
          <div class="bridge-feedback__actions">
            <el-button size="small" @click="focusOperationFirstGoods(latestOperation)">
              定位首个商品
            </el-button>
            <el-button
              v-if="latestOperation.ids && latestOperation.ids.length"
              size="small"
              plain
              @click="copyOperationIds(latestOperation)"
            >
              复制商品 ID
            </el-button>
            <el-button
              v-if="latestOperation.filterable"
              size="small"
              type="primary"
              plain
              @click="applyOperationMerchantFilter(latestOperation)"
            >
              {{ latestOperation.filterLabel }}
            </el-button>
            <el-button
              v-if="latestOperation.querySnapshot"
              size="small"
              plain
              @click="restoreOperationFilters(latestOperation)"
            >
              恢复操作前筛选
            </el-button>
            <el-button
              v-if="latestOperation.revertable"
              size="small"
              type="warning"
              plain
              @click="revertOperation(latestOperation)"
            >
              {{ latestOperation.revertLabel }}
            </el-button>
            <el-button
              v-if="canReuseOperationTransfer(latestOperation)"
              size="small"
              type="success"
              plain
              @click="reuseOperationTransfer(latestOperation)"
            >
              再次发起迁移
            </el-button>
          </div>
          <div v-if="latestOperation.querySnapshotLabel" class="bridge-feedback__snapshot">
            操作前筛选：{{ latestOperation.querySnapshotLabel }}
          </div>
          <div v-if="latestOperation.diff" class="operation-diff">
            <div class="operation-diff__header">
              <div class="operation-diff__title">{{ latestOperation.diffTitle || '处理结果摘要' }}</div>
              <el-tag
                :type="latestOperation.diff.unchangedCount ? 'warning' : 'success'"
                effect="plain"
              >
                变化 {{ latestOperation.diff.changedCount }} 件
              </el-tag>
            </div>
            <div class="operation-diff__grid">
              <div class="operation-diff__card">
                <div class="operation-diff__label">迁移前</div>
                <div class="operation-diff__value">{{ latestOperation.diff.beforeLabel }}</div>
                <div class="operation-diff__desc">{{ latestOperation.diff.beforeDesc }}</div>
              </div>
              <div class="operation-diff__card operation-diff__card--accent">
                <div class="operation-diff__label">迁移后</div>
                <div class="operation-diff__value">{{ latestOperation.diff.afterLabel }}</div>
                <div class="operation-diff__desc">{{ latestOperation.diff.afterDesc }}</div>
              </div>
              <div class="operation-diff__card">
                <div class="operation-diff__label">{{ latestOperation.diff.summaryLabel || '变化摘要' }}</div>
                <div class="operation-diff__value">
                  {{ latestOperation.diff.summaryValue || `${latestOperation.diff.changedCount} 件发生变化` }}
                </div>
                <div class="operation-diff__desc">{{ latestOperation.diff.summary }}</div>
              </div>
            </div>
          </div>
        </div>

        <div v-if="operationHistory.length && operationWorkbenchExpanded" class="bridge-history">
          <div class="bridge-history__header">
            <div class="bridge-history__heading">
              <div class="bridge-history__title">最近操作记录</div>
              <div class="bridge-history__stats">
                <el-tag effect="plain" type="info">保留 {{ operationHistory.length }} 条</el-tag>
                <el-tag effect="plain" type="success">命中 {{ filteredOperationHistory.length }} 条</el-tag>
                <el-tag effect="plain" type="warning">累计处理 {{ operationHistorySummary.totalCount }} 件</el-tag>
              </div>
            </div>
            <div class="bridge-history__toolbar">
              <el-select
                v-model="operationHistoryType"
                size="small"
                clearable
                class="bridge-history__filter"
                placeholder="操作类型"
              >
                <el-option
                  v-for="item in operationHistoryTypeOptions"
                  :key="`visible-history-type-${item.value}`"
                  :label="item.label"
                  :value="item.value"
                />
              </el-select>
              <el-input
                v-model="operationHistoryKeyword"
                size="small"
                clearable
                class="bridge-history__search"
                placeholder="搜索目标/商品ID/结果说明"
              />
              <el-button size="small" plain @click="clearOperationHistory">
                清空记录
              </el-button>
              <el-button size="small" type="primary" plain @click="exportOperationSummary">
                导出操作摘要
              </el-button>
            </div>
          </div>
          <div v-if="quickBatchLibrary.length" class="quick-batch-library">
            <div class="quick-batch-library__header">
              <div>
                <div class="quick-batch-library__title">快捷批次库</div>
                <div class="quick-batch-library__desc">把最近值得复用的运营批次固定出来，方便一键承接、复核和继续处理。</div>
              </div>
              <el-tag effect="plain" type="info">精选 {{ quickBatchLibrary.length }} 条</el-tag>
            </div>
            <div class="quick-batch-library__grid">
              <div
                v-for="item in quickBatchLibrary"
                :key="`quick-batch-${item.key}`"
                class="quick-batch-card"
                :class="`quick-batch-card--${item.tone}`"
              >
                <div class="quick-batch-card__header">
                  <div>
                    <div class="quick-batch-card__title">{{ item.title }}</div>
                    <div class="quick-batch-card__meta">{{ item.meta }}</div>
                  </div>
                  <el-tag size="small" effect="plain" :type="item.tone">{{ item.badge }}</el-tag>
                </div>
                <div class="quick-batch-card__desc">{{ item.desc }}</div>
                <div class="quick-batch-card__stats">
                  <span v-for="stat in item.stats" :key="`quick-batch-${item.key}-${stat}`">{{ stat }}</span>
                </div>
                <div
                  v-if="item.overlap.active"
                  class="quick-batch-card__notice"
                  :class="{
                    'quick-batch-card__notice--match': item.overlap.count,
                    'quick-batch-card__notice--idle': !item.overlap.count
                  }"
                >
                  <div class="quick-batch-card__notice-title">{{ item.overlap.title }}</div>
                  <div class="quick-batch-card__notice-desc">{{ item.overlap.desc }}</div>
                </div>
                <div class="quick-batch-card__actions">
                  <el-button
                    v-if="canStageOperationTransfer(item.operation)"
                    size="small"
                    type="primary"
                    plain
                    @click="stageQuickBatch(item.operation)"
                  >
                    承接到工具
                  </el-button>
                  <el-button
                    v-if="canReuseOperationTransfer(item.operation)"
                    size="small"
                    type="success"
                    plain
                    @click="reuseOperationTransfer(item.operation)"
                  >
                    直接继续迁移
                  </el-button>
                  <el-button
                    v-if="item.overlap.count && canStageOperationTransfer(item.operation)"
                    size="small"
                    type="warning"
                    plain
                    @click="stageOperationOverlap(item.operation)"
                  >
                    承接重叠部分
                  </el-button>
                  <el-button
                    v-if="item.operation.querySnapshot"
                    size="small"
                    plain
                    @click="restoreOperationFilters(item.operation)"
                  >
                    恢复筛选
                  </el-button>
                  <el-button
                    v-if="item.operation.ids && item.operation.ids.length"
                    size="small"
                    plain
                    @click="copyOperationIds(item.operation)"
                  >
                    复制ID
                  </el-button>
                  <el-button
                    v-if="item.overlap.count"
                    size="small"
                    plain
                    @click="copyOperationOverlapIds(item.operation)"
                  >
                    复制重叠ID
                  </el-button>
                </div>
              </div>
            </div>
          </div>
          <div class="bridge-history-overview">
            <div
              v-for="item in operationHistoryOverviewCards"
              :key="`visible-history-overview-${item.key}`"
              class="bridge-history-overview__card"
              :class="`bridge-history-overview__card--${item.tone}`"
            >
              <div class="bridge-history-overview__label">{{ item.label }}</div>
              <div class="bridge-history-overview__value">{{ item.value }}</div>
              <div class="bridge-history-overview__desc">{{ item.desc }}</div>
            </div>
          </div>
          <div v-if="priorityOperationCards.length" class="bridge-history-priority">
            <div
              v-for="item in priorityOperationCards"
              :key="`visible-history-priority-${item.key}`"
              class="bridge-history-priority__card"
              :class="`bridge-history-priority__card--${item.tone}`"
            >
              <div class="bridge-history-priority__header">
                <div>
                  <div class="bridge-history-priority__title">{{ item.title }}</div>
                  <div class="bridge-history-priority__meta">{{ item.meta }}</div>
                </div>
                <el-tag size="small" effect="plain" :type="item.tone">{{ item.badge }}</el-tag>
              </div>
              <div class="bridge-history-priority__desc">{{ item.desc }}</div>
              <div class="bridge-history-priority__tips">
                <span v-for="tip in item.tips" :key="`visible-history-tip-${item.key}-${tip}`">{{ tip }}</span>
              </div>
              <div v-if="item.alerts.length" class="bridge-history-priority__alerts">
                <el-tag
                  v-for="alert in item.alerts"
                  :key="`visible-history-alert-${item.key}-${alert}`"
                  size="small"
                  effect="plain"
                  type="danger"
                >
                  {{ alert }}
                </el-tag>
              </div>
              <div class="bridge-history-priority__actions">
                <el-button size="small" text @click="focusOperationFirstGoods(item.operation)">定位</el-button>
                <el-button
                  v-if="item.operation.ids && item.operation.ids.length"
                  size="small"
                  text
                  @click="copyOperationIds(item.operation)"
                >
                  复制ID
                </el-button>
                <el-button
                  v-if="item.operation.filterable"
                  size="small"
                  text
                  type="primary"
                  @click="applyOperationMerchantFilter(item.operation)"
                >
                  筛选归属
                </el-button>
                <el-button
                  v-if="item.operation.querySnapshot"
                  size="small"
                  text
                  @click="restoreOperationFilters(item.operation)"
                >
                  恢复筛选
                </el-button>
                <el-button
                  v-if="item.operation.revertable"
                  size="small"
                  text
                  type="warning"
                  @click="revertOperation(item.operation)"
                >
                  回退
                </el-button>
                <el-button
                  v-if="canStageOperationTransfer(item.operation)"
                  size="small"
                  text
                  type="primary"
                  @click="stageOperationTransfer(item.operation)"
                >
                  带入工具
                </el-button>
                <el-button
                  v-if="canReuseOperationTransfer(item.operation)"
                  size="small"
                  text
                  type="success"
                  @click="reuseOperationTransfer(item.operation)"
                >
                  再次迁移
                </el-button>
              </div>
            </div>
          </div>
          <div class="bridge-history__items">
            <div
              v-for="group in groupedOperationHistory"
              :key="`visible-history-group-${group.key}`"
              class="bridge-history-group"
            >
              <div class="bridge-history-group__header">
                <div class="bridge-history-group__title">{{ group.label }}</div>
                <div class="bridge-history-group__meta">
                  <el-tag size="small" effect="plain" :type="group.tone">{{ group.count }} 条</el-tag>
                  <span>{{ group.desc }}</span>
                </div>
              </div>
              <div class="bridge-history-group__items">
                <div
                  v-for="item in group.items"
                  :key="`visible-history-item-${item.key}`"
                  class="bridge-history__item"
                >
                  <div class="bridge-history__main">
                    <div class="bridge-history__label">{{ item.label }}</div>
                    <div class="bridge-history__desc">
                      {{ item.time }} · {{ item.count }} 件
                      <span v-if="item.target">· {{ item.target }}</span>
                    </div>
                    <div v-if="item.diff" class="bridge-history__diff">
                      {{ item.diff.summary }}
                    </div>
                    <div v-if="item.diff" class="bridge-history__stats-inline">
                      <el-tag size="small" effect="plain" type="success">变化 {{ item.diff.changedCount }} 件</el-tag>
                      <el-tag
                        v-if="item.diff.unchangedCount"
                        size="small"
                        effect="plain"
                        type="warning"
                      >
                        未变化 {{ item.diff.unchangedCount }} 件
                      </el-tag>
                    </div>
                  </div>
                  <div class="bridge-history__actions">
                    <el-button size="small" text @click="focusOperationFirstGoods(item)">定位</el-button>
                    <el-button
                      v-if="item.ids && item.ids.length"
                      size="small"
                      text
                      @click="copyOperationIds(item)"
                    >
                      复制ID
                    </el-button>
                    <el-button
                      v-if="item.filterable"
                      size="small"
                      text
                      type="primary"
                      @click="applyOperationMerchantFilter(item)"
                    >
                      筛选归属
                    </el-button>
                    <el-button
                      v-if="item.querySnapshot"
                      size="small"
                      text
                      @click="restoreOperationFilters(item)"
                    >
                      恢复筛选
                    </el-button>
                    <el-button
                      v-if="item.revertable"
                      size="small"
                      text
                      type="warning"
                      @click="revertOperation(item)"
                    >
                      回退
                    </el-button>
                    <el-button
                      v-if="canReuseOperationTransfer(item)"
                      size="small"
                      text
                      type="success"
                      @click="reuseOperationTransfer(item)"
                    >
                      再次迁移
                    </el-button>
                  </div>
                </div>
              </div>
            </div>
            <div v-if="!filteredOperationHistory.length" class="bridge-history__empty">
              当前筛选条件下没有匹配的操作记录，换个关键词或操作类型试试。
            </div>
          </div>
        </div>
      </div>
    </el-card>

    <el-card class="panel" shadow="never">
      <div class="panel__header-bar">
        <div>
          <div class="panel__sub-title">运营筛选</div>
          <div class="panel__sub-desc">用快捷筛选和组合条件快速定位待处理商品。</div>
        </div>
      </div>

      <div class="quick-filters">
        <button
          v-for="item in quickFilters"
          :key="item.key"
          class="quick-filter"
          :class="{ 'quick-filter--active': activeQuickFilter === item.key }"
          type="button"
          @click="applyQuickFilter(item)"
        >
          {{ item.label }}
        </button>
      </div>

      <div class="combined-filters">
        <div class="combined-filters__title">联动筛选</div>
        <div class="combined-filters__items">
          <button
            v-for="item in combinedFilterPresets"
            :key="item.key"
            class="combined-filter"
            type="button"
            @click="applyCombinedFilter(item)"
          >
            {{ item.label }}
          </button>
        </div>
      </div>

      <el-form class="filter-form" inline>
        <el-form-item label="添加时间">
          <el-date-picker
            v-model="query.date_value"
            type="daterange"
            value-format="YYYY-MM-DD"
            start-placeholder="开始日期"
            end-placeholder="结束日期"
            style="width: 260px"
          />
        </el-form-item>
        <el-form-item label="商品状态">
          <el-select v-model="query.status" clearable style="width: 150px">
            <el-option label="全部状态" :value="undefined" />
            <el-option
              v-for="item in goodsStatusOptions"
              :key="item.value"
              :label="item.label"
              :value="item.value"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="上架状态">
          <el-select v-model="query.is_disable" clearable style="width: 140px">
            <el-option label="全部" :value="undefined" />
            <el-option :value="0" label="上架" />
            <el-option :value="1" label="下架" />
          </el-select>
        </el-form-item>
        <el-form-item label="商品分类">
          <el-cascader
            v-model="query.goods_type_id"
            :options="params.goods_types"
            :props="typeProps"
            clearable
            filterable
            collapse-tags
            style="width: 220px"
          />
        </el-form-item>
        <el-form-item label="商品标签">
          <el-select
            v-model="query.goods_label_id"
            clearable
            filterable
            collapse-tags
            style="width: 180px"
          >
            <el-option
              v-for="item in params.goods_labels || []"
              :key="item.id"
              :label="item.title"
              :value="item.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="归属商家">
          <el-select
            v-model="query.merchant_id"
            clearable
            filterable
            style="width: 220px"
          >
            <el-option label="全部商品" :value="-1" />
            <el-option label="平台自营" :value="0" />
            <el-option
              v-for="item in merchantOptions"
              :key="item.id"
              :label="item.title"
              :value="item.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="关键词">
          <el-input
            v-model="query.search_value"
            placeholder="请输入内容"
            class="input-with-select"
            clearable
            style="width: 280px"
            @keyup.enter="search"
          >
            <template #prepend>
              <el-select v-model="query.search_field" style="width: 96px">
                <el-option :value="idkey" label="ID" />
                <el-option value="code" label="编码" />
                <el-option value="title" label="标题" />
                <el-option value="remark" label="备注" />
              </el-select>
            </template>
          </el-input>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="search">查询</el-button>
          <el-button @click="refresh">重置</el-button>
          <el-button plain @click="clearFilters">清空筛选</el-button>
          <el-button plain :disabled="!data.length" @click="selectCurrentPage">当前页全选</el-button>
        </el-form-item>
      </el-form>

      <div class="current-params">
        <div class="current-params__title">当前筛选条件</div>
        <div class="current-params__tags">
          <el-tag v-for="item in activeFilterTags" :key="item" effect="plain">{{ item }}</el-tag>
          <el-tag v-if="!activeFilterTags.length" effect="plain" type="info">当前为默认筛选条件</el-tag>
        </div>
      </div>

      <div class="filter-history">
        <div class="filter-history__header">
          <div>
            <div class="filter-history__title">筛选轨迹</div>
            <div class="filter-history__desc">保留最近几次商品筛选，支持一键恢复和页内回退。</div>
            <div class="filter-history__status">{{ filterTimelineStatusLabel }}</div>
          </div>
          <div class="filter-history__actions">
            <el-button size="small" plain :disabled="!canNavigateFilterBack" @click="navigateFilterStep(-1)">回退一步</el-button>
            <el-button size="small" plain :disabled="!canNavigateFilterForward" @click="navigateFilterStep(1)">前进一步</el-button>
          </div>
        </div>
        <div class="filter-history__items">
          <button
            v-for="item in recentFilterHistory"
            :key="item.key"
            class="filter-history__item"
            type="button"
            @click="restoreRecentFilter(item)"
          >
            <span class="filter-history__item-label">{{ item.label }}</span>
            <span class="filter-history__item-meta">{{ item.meta }}</span>
          </button>
          <div v-if="!recentFilterHistory.length" class="filter-history__empty">
            还没有保存筛选轨迹，执行一次查询后这里会自动沉淀最近条件。
          </div>
        </div>
      </div>

      <div class="merchant-picks">
        <div class="merchant-picks__title">快捷筛选商家</div>
        <div class="merchant-picks__items">
          <button
            class="merchant-pick"
            :class="{ 'merchant-pick--active': Number(query.merchant_id) === -1 }"
            type="button"
            @click="applyMerchantPick(-1)"
          >
            全部商家
          </button>
          <button
            v-for="item in merchantQuickPicks"
            :key="item.id"
            class="merchant-pick"
            :class="{ 'merchant-pick--active': Number(query.merchant_id) === Number(item.id) }"
            type="button"
            @click="applyMerchantPick(item.id)"
          >
            {{ item.title }}
          </button>
        </div>
      </div>

    </el-card>

    <el-card class="panel" shadow="never">
      <div class="panel__header-bar">
        <div>
          <div class="panel__sub-title">商品列表</div>
          <div class="panel__sub-desc">保留原有商品编辑、审核、上下架能力，并在行内补齐迁移入口。</div>
        </div>
        <pagination
          v-show="count > 0"
          v-model:total="count"
          v-model:page="query.page"
          v-model:limit="query.limit"
          @pagination="handlePagination"
        />
      </div>

      <div ref="goodsTopActions" class="online-actions online-actions--table">
        <button class="online-action online-action--primary" type="button" @click="add">新建商品</button>
        <button
          class="online-action"
          type="button"
          :disabled="!selection.length || !checkPermission(['admin/goods.Goods/transferToPlatform'])"
          @click="selectOpen('transfer_platform')"
        >
          批量迁移到平台自营
        </button>
        <button
          class="online-action"
          type="button"
          :disabled="!selection.length || !checkPermission(['admin/goods.Goods/transferToMerchant'])"
          @click="openTransferMerchantDialog()"
        >
          批量迁移到指定商家
        </button>
        <button class="online-action" type="button" :disabled="!data.length" @click="selectOpen('batch_thumbnail')">
          批量更换缩略图
        </button>
        <button class="online-action" type="button" :disabled="!data.length" @click="selectOpen('batch_label')">
          批量更新标签
        </button>
        <button
          class="online-action"
          type="button"
          :disabled="!canStageSelectionForCurrentBridge"
          @click="stageSelectionForCurrentBridge"
        >
          {{ selectionStageActionLabel }}
        </button>
        <button class="online-action" type="button" @click="search">筛选</button>
        <button class="online-action" type="button" @click="clearFilters">清空筛选</button>
        <button class="online-action" type="button" :disabled="!data.length" @click="selectCurrentPage">当前页全选</button>
      </div>

      <el-table
        ref="table"
        :data="data"
        class="goods-table"
        @sort-change="sort"
        @selection-change="select"
      >
        <el-table-column type="selection" width="48" />
        <el-table-column :prop="idkey" label="ID" width="88" sortable="custom" />
        <el-table-column prop="merchant_title" label="归属" min-width="140" show-overflow-tooltip>
          <template #default="scope">
            <div class="cell-main">{{ scope.row.merchant_title || resolveMerchantTitle(scope.row.merchant_id) }}</div>
            <div class="cell-meta">销量 {{ scope.row.sales_sum || 0 }} / 库存 {{ scope.row.stock || 0 }}</div>
          </template>
        </el-table-column>
        <el-table-column prop="code" label="编码" min-width="120" show-overflow-tooltip />
        <el-table-column prop="image_url" label="缩略图" min-width="72">
          <template #default="scope">
            <FileImage :file-url="scope.row.image_url" lazy fileSource="list" />
          </template>
        </el-table-column>
        <el-table-column prop="title" label="商品名称" min-width="220" show-overflow-tooltip>
          <template #default="scope">
            <div class="goods-title-cell">
              <el-tag size="small" effect="plain" class="goods-title-cell__tag">勾选迁移</el-tag>
              <div class="goods-title-cell__text">
                <div class="cell-main">{{ scope.row.title }}</div>
                <div class="cell-meta">编码：{{ scope.row.code || '--' }}</div>
                <div class="cell-meta">分类：{{ scope.row.type_title || '--' }}</div>
              </div>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="type_title" label="商品分类" min-width="120" show-overflow-tooltip />
        <el-table-column prop="label_title" label="商品标签" min-width="140" show-overflow-tooltip />
        <el-table-column label="价格" width="150">
          <template #default="scope">
            <div class="price-cell">
              <div class="price-cell__current">¥{{ formatMoney(scope.row.price) }}</div>
              <div class="price-cell__origin">原价 ¥{{ formatMoney(scope.row.original_price) }}</div>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="is_disable" label="上架状态" width="110" sortable="custom">
          <template #default="scope">
            <el-switch
              v-model="scope.row.is_disable"
              inline-prompt
              active-text="上架"
              inactive-text="下架"
              :active-value="0"
              :inactive-value="1"
              @change="disable([scope.row])"
            />
          </template>
        </el-table-column>
        <el-table-column prop="stock" label="库存" width="90" sortable="custom" />
        <el-table-column prop="status_title" label="审核状态" min-width="110">
          <template #default="scope">
            <div class="status-stack">
              <el-tag type="info" v-if="scope.row.status === 0">待审核</el-tag>
              <el-tag type="success" v-else-if="scope.row.status === 1">审核通过</el-tag>
              <el-tag type="danger" v-else>审核失败</el-tag>
              <div class="status-reason">{{ scope.row.auth_msg || '无审核备注' }}</div>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="create_time" label="添加时间" width="170" sortable="custom" />
        <el-table-column prop="update_time" label="修改时间" width="170" sortable="custom" />
        <el-table-column label="操作" min-width="320">
          <template #default="scope">
            <div class="row-actions">
              <div v-if="buildRowActionHints(scope.row).length" class="row-actions__hints">
                <el-tag
                  v-for="item in buildRowActionHints(scope.row)"
                  :key="item.label"
                  size="small"
                  effect="plain"
                  :type="item.type"
                >
                  {{ item.label }}
                </el-tag>
              </div>
              <div class="row-actions__group">
                <el-link type="primary" :underline="false" @click="edit(scope.row)">编辑</el-link>
                <el-link type="info" :underline="false" @click="openDetail(scope.row)">详情</el-link>
                <el-link type="primary" :underline="false" @click="copyRowGoodsId(scope.row)">复制ID</el-link>
                <el-link type="info" :underline="false" @click="filterRowOwnership(scope.row)">按归属筛选</el-link>
                <el-link
                  :disabled="!scope.row.code"
                  type="info"
                  :underline="false"
                  @click="searchRowByCode(scope.row)"
                >
                  查同编码
                </el-link>
              </div>
              <div class="row-actions__group">
                <el-link
                  :type="Number(scope.row.is_disable) === 0 ? 'warning' : 'success'"
                  :underline="false"
                  @click="toggleRowDisable(scope.row)"
                >
                  {{ Number(scope.row.is_disable) === 0 ? '停用' : '上架' }}
                </el-link>
                <el-link
                  v-if="scope.row.status === 0"
                  type="warning"
                  :underline="false"
                  @click="selectOpen('auth', [scope.row])"
                >
                  审核
                </el-link>
                <el-link type="danger" :underline="false" @click="selectOpen('dele', [scope.row])">删除</el-link>
                <el-link
                  v-if="Number(scope.row.merchant_id) !== 0"
                  type="primary"
                  :disabled="!checkPermission(['admin/goods.Goods/transferToPlatform'])"
                  :underline="false"
                  @click="selectOpen('transfer_platform', [scope.row])"
                >
                  转平台
                </el-link>
                <el-link
                  type="success"
                  :disabled="!checkPermission(['admin/goods.Goods/transferToMerchant'])"
                  :underline="false"
                  @click="openTransferMerchantDialog([scope.row])"
                >
                  转商家
                </el-link>
                <el-link
                  v-if="targetMerchantLabel"
                  type="success"
                      :disabled="!checkPermission(['admin/goods.Goods/transferToMerchant'])"
                  :underline="false"
                  @click="quickTransferRowToRememberedMerchant(scope.row)"
                >
                  转到已记忆目标
                </el-link>
                <el-link
                  type="primary"
                  :underline="false"
                  @click="stageRowForCurrentBridge(scope.row)"
                >
                  带入当前工具
                </el-link>
              </div>
            </div>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <el-dialog
      v-model="selectDialog"
      :title="selectTitle"
      :close-on-click-modal="false"
      :close-on-press-escape="false"
      :before-close="handleSelectDialogBeforeClose"
      top="18vh"
    >
      <el-form label-width="120px">
        <template v-if="selectType === 'disable'">
          <el-form-item label="上架状态">
            <el-switch
              v-model="is_disable"
              inline-prompt
              active-text="上架"
              inactive-text="下架"
              :active-value="0"
              :inactive-value="1"
            />
          </el-form-item>
          <el-form-item label="处理范围">
            <div class="transfer-scope transfer-scope--inline">
              <el-radio-group v-model="batchScope">
                <el-radio-button label="selected" :disabled="!selection.length">仅处理已勾选</el-radio-button>
                <el-radio-button label="current_page" :disabled="!data.length">处理当前页</el-radio-button>
              </el-radio-group>
              <div class="transfer-scope__desc">{{ batchScopeDescription }}</div>
            </div>
          </el-form-item>
          <el-form-item label="操作预判">
            <div class="transfer-preview transfer-preview--inline">
              <div class="transfer-preview__grid">
                <div class="transfer-preview__card">
                  <div class="transfer-preview__label">即将处理</div>
                  <div class="transfer-preview__value">{{ batchSelectionSummary.label }}</div>
                  <div class="transfer-preview__desc">{{ batchSelectionSummary.desc }}</div>
                </div>
                <div class="transfer-preview__card transfer-preview__card--accent">
                  <div class="transfer-preview__label">提交后状态</div>
                  <div class="transfer-preview__value">{{ Number(is_disable) === 0 ? '统一上架' : '统一下架' }}</div>
                  <div class="transfer-preview__desc">{{ batchActionPreview.disable }}</div>
                </div>
              </div>
            </div>
          </el-form-item>
        </template>
        <el-form-item v-else-if="selectType === 'dele'">
          <span class="dialog-warn">确定要删除选中的{{ name }}吗？</span>
        </el-form-item>
        <template v-else-if="selectType === 'batch_thumbnail'">
          <el-form-item label="操作说明">
            <div class="dialog-help">
              先确认处理范围，再统一替换这些商品的缩略图。
            </div>
          </el-form-item>
          <el-form-item label="处理范围">
            <div class="transfer-scope transfer-scope--inline">
              <el-radio-group v-model="batchScope">
                <el-radio-button label="selected" :disabled="!selection.length">仅处理已勾选</el-radio-button>
                <el-radio-button label="current_page" :disabled="!data.length">处理当前页</el-radio-button>
              </el-radio-group>
              <div class="transfer-scope__desc">{{ batchScopeDescription }}</div>
            </div>
          </el-form-item>
          <el-form-item label="处理预判">
            <div class="transfer-preview transfer-preview--inline">
              <div class="transfer-preview__grid">
                <div class="transfer-preview__card">
                  <div class="transfer-preview__label">即将处理</div>
                  <div class="transfer-preview__value">{{ batchSelectionSummary.label }}</div>
                  <div class="transfer-preview__desc">{{ batchSelectionSummary.desc }}</div>
                </div>
                <div class="transfer-preview__card transfer-preview__card--accent">
                  <div class="transfer-preview__label">提交后结果</div>
                  <div class="transfer-preview__value">统一更新缩略图</div>
                  <div class="transfer-preview__desc">{{ batchActionPreview.thumbnail }}</div>
                </div>
              </div>
            </div>
          </el-form-item>
          <el-form-item label="快捷操作">
            <el-button size="small" plain @click="useFirstSelectionThumbnail">使用首个商品当前缩略图</el-button>
          </el-form-item>
          <el-form-item label="目标缩略图" required>
            <FileImage
              v-model="batch_image_id"
              :file-url="batch_image_url"
              :height="96"
              upload
            />
          </el-form-item>
        </template>
        <template v-else-if="selectType === 'batch_label'">
          <el-form-item label="操作说明">
            <div class="dialog-help">
              先确认处理范围，再统一更新这些商品的标签。
            </div>
          </el-form-item>
          <el-form-item label="处理范围">
            <div class="transfer-scope transfer-scope--inline">
              <el-radio-group v-model="batchScope">
                <el-radio-button label="selected" :disabled="!selection.length">仅处理已勾选</el-radio-button>
                <el-radio-button label="current_page" :disabled="!data.length">处理当前页</el-radio-button>
              </el-radio-group>
              <div class="transfer-scope__desc">{{ batchScopeDescription }}</div>
            </div>
          </el-form-item>
          <el-form-item label="处理预判">
            <div class="transfer-preview transfer-preview--inline">
              <div class="transfer-preview__grid">
                <div class="transfer-preview__card">
                  <div class="transfer-preview__label">即将处理</div>
                  <div class="transfer-preview__value">{{ batchSelectionSummary.label }}</div>
                  <div class="transfer-preview__desc">{{ batchSelectionSummary.desc }}</div>
                </div>
                <div class="transfer-preview__card transfer-preview__card--accent">
                  <div class="transfer-preview__label">提交后结果</div>
                  <div class="transfer-preview__value">{{ batch_goods_label_id.length ? `保留 ${batch_goods_label_id.length} 个标签` : '清空全部标签' }}</div>
                  <div class="transfer-preview__desc">{{ batchActionPreview.label }}</div>
                </div>
              </div>
            </div>
          </el-form-item>
          <el-form-item label="商品标签">
            <el-select
              v-model="batch_goods_label_id"
              class="w-full"
              clearable
              filterable
              multiple
              placeholder="请选择需要保留的标签"
            >
              <el-option
                v-for="item in params.goods_labels || []"
                :key="item.id"
                :label="item.title"
                :value="item.id"
              />
            </el-select>
          </el-form-item>
        </template>
        <template v-else-if="selectType === 'transfer_platform'">
          <el-form-item>
            <div class="transfer-preview">
              <div class="transfer-preview__lead">确认本次迁移范围后，再执行平台自营归属切换。</div>
              <div class="transfer-scope">
                <div class="transfer-scope__label">处理范围</div>
                <el-radio-group v-model="transferScope">
                  <el-radio-button label="selected" :disabled="!selection.length">仅处理已勾选</el-radio-button>
                  <el-radio-button label="current_page" :disabled="!data.length">处理当前页</el-radio-button>
                  <el-radio-button label="manual_ids">指定商品 ID</el-radio-button>
                </el-radio-group>
                <div class="transfer-scope__desc">{{ transferScopeDescription }}</div>
              </div>
              <div class="transfer-preview__grid">
                <div class="transfer-preview__card">
                  <div class="transfer-preview__label">迁移前归属</div>
                  <div class="transfer-preview__value">{{ transferSelectionSummary.label }}</div>
                  <div class="transfer-preview__desc">{{ transferSelectionSummary.desc }}</div>
                </div>
                <div class="transfer-preview__card transfer-preview__card--accent">
                  <div class="transfer-preview__label">迁移后预判</div>
                  <div class="transfer-preview__value">平台自营 {{ transferTargetIds.length }} 件</div>
                  <div class="transfer-preview__desc">{{ transferPreviewSummary.platform }}</div>
                </div>
              </div>
            </div>
          </el-form-item>
          <el-form-item v-if="transferScope === 'manual_ids'" label="指定商品 ID">
            <div class="transfer-manual-input">
              <div class="dialog-help">支持输入部分商品 ID，多个 ID 可用逗号、空格或换行分隔。</div>
              <div class="transfer-manual-actions">
                <el-button size="small" plain :disabled="!selection.length" @click="fillTransferManualIds('selected')">填入当前勾选</el-button>
                <el-button size="small" plain :disabled="!data.length" @click="fillTransferManualIds('current_page')">填入当前页</el-button>
                <el-button size="small" plain :disabled="!transferManualCleanupNeeded" @click="keepResolvedTransferManualIds">仅保留已识别</el-button>
                <el-button size="small" plain :disabled="!stagedBridgeSnapshotAvailable" @click="restoreStagedBridgeSnapshot">恢复最近承接</el-button>
                <el-button size="small" plain :disabled="!transferDraftAvailable" @click="restoreTransferDraft">恢复草稿</el-button>
                <el-button size="small" plain :disabled="!transferDraftAvailable" @click="clearTransferDraft">清空草稿</el-button>
                <el-button size="small" plain :disabled="!transferManualIdsText" @click="clearTransferManualIds">清空</el-button>
              </div>
              <el-input
                v-model="transferManualIdsText"
                type="textarea"
                :rows="4"
                placeholder="例如：1001,1002,1003"
              />
              <div class="transfer-manual-stats">
                <el-tag effect="plain" type="info">已输入 {{ transferManualIds.length }} 个 ID</el-tag>
                <el-tag effect="plain" type="success">已识别 {{ transferManualMatchedIds.length }} 个</el-tag>
                <el-tag v-if="transferManualMeta.duplicateCount" effect="plain" type="warning">
                  已去重 {{ transferManualMeta.duplicateCount }} 个
                </el-tag>
                <el-tag v-if="transferManualMeta.invalidTokens.length" effect="plain" type="danger">
                  已忽略 {{ transferManualMeta.invalidTokens.length }} 项无效内容
                </el-tag>
                <el-tag v-if="transferManualPendingIds.length" effect="plain" type="warning">
                  待补查 {{ transferManualPendingIds.length }} 个
                </el-tag>
              </div>
              <div v-if="transferManualMeta.invalidTokens.length" class="dialog-warn">
                以下内容不是有效商品 ID，已自动忽略：{{ transferManualMeta.invalidTokens.join('，') }}
              </div>
              <div v-if="transferManualPendingIds.length" class="dialog-warn">
                以下 ID 当前页未识别：{{ transferManualPendingIds.join('，') }}。提交前会再次核对，若仍未找到将阻止提交。
              </div>
              <div v-if="transferManualPreviewTitles.length" class="transfer-manual-preview">
                <div class="transfer-manual-preview__header">
                  <span>已识别商品预览</span>
                  <span>提交前可先核对命中商品</span>
                </div>
                <div class="transfer-manual-preview__metrics">
                  <div class="transfer-manual-preview__metric">
                    <div class="transfer-manual-preview__metric-label">已识别</div>
                    <div class="transfer-manual-preview__metric-value">{{ transferManualMatchedIds.length }} 件</div>
                  </div>
                  <div class="transfer-manual-preview__metric transfer-manual-preview__metric--accent">
                    <div class="transfer-manual-preview__metric-label">预计迁移</div>
                    <div class="transfer-manual-preview__metric-value">{{ transferManualChangeSummary.changed }} 件</div>
                  </div>
                  <div class="transfer-manual-preview__metric">
                    <div class="transfer-manual-preview__metric-label">保持原归属</div>
                    <div class="transfer-manual-preview__metric-value">{{ transferManualChangeSummary.unchanged }} 件</div>
                  </div>
                  <div class="transfer-manual-preview__metric" v-if="transferManualPendingIds.length">
                    <div class="transfer-manual-preview__metric-label">待补查</div>
                    <div class="transfer-manual-preview__metric-value">{{ transferManualPendingIds.length }} 件</div>
                  </div>
                </div>
                <div class="transfer-manual-preview__summary transfer-manual-preview__summary--accent">
                  本次将迁移至{{ transferManualPreviewTargetLabel }}。已识别商品中，预计发生归属变化 {{ transferManualChangeSummary.changed }} 件，保持原归属 {{ transferManualChangeSummary.unchanged }} 件。
                </div>
                <div class="transfer-manual-preview__summary">
                  归属汇总：平台自营 {{ transferManualOwnershipSummary.platform }} 件，商家归属 {{ transferManualOwnershipSummary.merchant }} 件
                </div>
                <div class="transfer-manual-preview__items">
                  <span v-for="item in transferManualPreviewItems" :key="item.id">
                    {{ item.title }} · {{ item.ownership || '当前归属待识别' }}
                  </span>
                </div>
                <div v-if="transferManualPreviewOverflowCount" class="transfer-manual-preview__more">
                  还有 {{ transferManualPreviewOverflowCount }} 件已识别商品未展开显示，提交时会一并处理。
                </div>
              </div>
            </div>
          </el-form-item>
        </template>
        <template v-else-if="selectType === 'transfer_merchant'">
          <el-form-item label="迁移说明">
            <div class="dialog-help">
              先确认处理范围，再选择目标商家，提交后会按当前弹窗里展示的对象执行迁移。
            </div>
          </el-form-item>
          <el-form-item label="处理范围">
            <div class="transfer-scope transfer-scope--inline">
              <el-radio-group v-model="transferScope">
                <el-radio-button label="selected" :disabled="!selection.length">仅处理已勾选</el-radio-button>
                <el-radio-button label="current_page" :disabled="!data.length">处理当前页</el-radio-button>
                <el-radio-button label="manual_ids">指定商品 ID</el-radio-button>
              </el-radio-group>
              <div class="transfer-scope__desc">{{ transferScopeDescription }}</div>
            </div>
          </el-form-item>
          <el-form-item v-if="transferScope === 'manual_ids'" label="指定商品 ID">
            <div class="transfer-manual-input">
              <div class="dialog-help">支持输入部分商品 ID，多个 ID 可用逗号、空格或换行分隔。</div>
              <div class="transfer-manual-actions">
                <el-button size="small" plain :disabled="!selection.length" @click="fillTransferManualIds('selected')">填入当前勾选</el-button>
                <el-button size="small" plain :disabled="!data.length" @click="fillTransferManualIds('current_page')">填入当前页</el-button>
                <el-button size="small" plain :disabled="!transferManualCleanupNeeded" @click="keepResolvedTransferManualIds">仅保留已识别</el-button>
                <el-button size="small" plain :disabled="!stagedBridgeSnapshotAvailable" @click="restoreStagedBridgeSnapshot">恢复最近承接</el-button>
                <el-button size="small" plain :disabled="!transferDraftAvailable" @click="restoreTransferDraft">恢复草稿</el-button>
                <el-button size="small" plain :disabled="!transferDraftAvailable" @click="clearTransferDraft">清空草稿</el-button>
                <el-button size="small" plain :disabled="!transferManualIdsText" @click="clearTransferManualIds">清空</el-button>
              </div>
              <el-input
                v-model="transferManualIdsText"
                type="textarea"
                :rows="4"
                placeholder="例如：1001,1002,1003"
              />
              <div class="transfer-manual-stats">
                <el-tag effect="plain" type="info">已输入 {{ transferManualIds.length }} 个 ID</el-tag>
                <el-tag effect="plain" type="success">已识别 {{ transferManualMatchedIds.length }} 个</el-tag>
                <el-tag v-if="transferManualMeta.duplicateCount" effect="plain" type="warning">
                  已去重 {{ transferManualMeta.duplicateCount }} 个
                </el-tag>
                <el-tag v-if="transferManualMeta.invalidTokens.length" effect="plain" type="danger">
                  已忽略 {{ transferManualMeta.invalidTokens.length }} 项无效内容
                </el-tag>
                <el-tag v-if="transferManualPendingIds.length" effect="plain" type="warning">
                  待补查 {{ transferManualPendingIds.length }} 个
                </el-tag>
              </div>
              <div v-if="transferManualMeta.invalidTokens.length" class="dialog-warn">
                以下内容不是有效商品 ID，已自动忽略：{{ transferManualMeta.invalidTokens.join('，') }}
              </div>
              <div v-if="transferManualPendingIds.length" class="dialog-warn">
                以下 ID 当前页未识别：{{ transferManualPendingIds.join('，') }}。提交前会再次核对，若仍未找到将阻止提交。
              </div>
              <div v-if="transferManualPreviewTitles.length" class="transfer-manual-preview">
                <div class="transfer-manual-preview__header">
                  <span>已识别商品预览</span>
                  <span>提交前可先核对命中商品</span>
                </div>
                <div class="transfer-manual-preview__metrics">
                  <div class="transfer-manual-preview__metric">
                    <div class="transfer-manual-preview__metric-label">已识别</div>
                    <div class="transfer-manual-preview__metric-value">{{ transferManualMatchedIds.length }} 件</div>
                  </div>
                  <div class="transfer-manual-preview__metric transfer-manual-preview__metric--accent">
                    <div class="transfer-manual-preview__metric-label">预计迁移</div>
                    <div class="transfer-manual-preview__metric-value">{{ transferManualChangeSummary.changed }} 件</div>
                  </div>
                  <div class="transfer-manual-preview__metric">
                    <div class="transfer-manual-preview__metric-label">保持原归属</div>
                    <div class="transfer-manual-preview__metric-value">{{ transferManualChangeSummary.unchanged }} 件</div>
                  </div>
                  <div class="transfer-manual-preview__metric" v-if="transferManualPendingIds.length">
                    <div class="transfer-manual-preview__metric-label">待补查</div>
                    <div class="transfer-manual-preview__metric-value">{{ transferManualPendingIds.length }} 件</div>
                  </div>
                </div>
                <div class="transfer-manual-preview__summary transfer-manual-preview__summary--accent">
                  本次将迁移至{{ transferManualPreviewTargetLabel }}。已识别商品中，预计发生归属变化 {{ transferManualChangeSummary.changed }} 件，保持原归属 {{ transferManualChangeSummary.unchanged }} 件。
                </div>
                <div class="transfer-manual-preview__summary">
                  归属汇总：平台自营 {{ transferManualOwnershipSummary.platform }} 件，商家归属 {{ transferManualOwnershipSummary.merchant }} 件
                </div>
                <div class="transfer-manual-preview__items">
                  <span v-for="item in transferManualPreviewItems" :key="item.id">
                    {{ item.title }} · {{ item.ownership || '当前归属待识别' }}
                  </span>
                </div>
                <div v-if="transferManualPreviewOverflowCount" class="transfer-manual-preview__more">
                  还有 {{ transferManualPreviewOverflowCount }} 件已识别商品未展开显示，提交时会一并处理。
                </div>
              </div>
            </div>
          </el-form-item>
          <el-form-item label="当前目标">
            <div class="transfer-target-panel">
              <el-tag v-if="targetMerchantLabel" type="success" effect="plain">
                {{ targetMerchantLabel }}
              </el-tag>
              <span v-else class="dialog-warn">尚未选择目标商家</span>
              <div v-if="transferMerchantQuickTargets.length" class="transfer-target-panel__quick">
                <span class="transfer-target-panel__quick-label">快捷目标</span>
                <button
                  v-for="item in transferMerchantQuickTargets"
                  :key="item.id"
                  class="transfer-target-chip"
                  type="button"
                  :class="{ 'transfer-target-chip--active': Number(target_merchant_id) === Number(item.id) }"
                  @click="applyTransferTargetMerchant(item.id)"
                >
                  {{ item.title }}
                </button>
              </div>
              <div class="transfer-target-panel__draft">
                <el-button size="small" plain :disabled="!transferDraftAvailable" @click="restoreTransferDraft">
                  恢复上次迁移草稿
                </el-button>
                <el-button size="small" plain :disabled="!transferDraftAvailable" @click="clearTransferDraft">
                  清空迁移草稿
                </el-button>
                <span v-if="transferDraftHint" class="transfer-target-panel__draft-text">{{ transferDraftHint }}</span>
              </div>
            </div>
          </el-form-item>
          <el-form-item label="迁移预判">
            <div class="transfer-preview transfer-preview--inline">
              <div class="transfer-preview__grid">
                <div class="transfer-preview__card">
                  <div class="transfer-preview__label">迁移前归属</div>
                  <div class="transfer-preview__value">{{ transferSelectionSummary.label }}</div>
                  <div class="transfer-preview__desc">{{ transferSelectionSummary.desc }}</div>
                </div>
                <div class="transfer-preview__card transfer-preview__card--accent">
                  <div class="transfer-preview__label">迁移后预判</div>
                  <div class="transfer-preview__value">{{ targetMerchantLabel || '待选择目标商家' }}</div>
                  <div class="transfer-preview__desc">{{ transferPreviewSummary.merchant }}</div>
                </div>
              </div>
            </div>
          </el-form-item>
          <el-form-item label="目标商家" required>
            <el-select
              v-model="target_merchant_id"
              class="transfer-merchant-dialog-select w-full"
              clearable
              filterable
              popper-class="transfer-merchant-dialog-dropdown"
              placeholder="请选择目标商家"
            >
              <el-option
                v-for="item in merchantOptions"
                :key="item.id"
                :label="item.title"
                :value="item.id"
              />
            </el-select>
          </el-form-item>
        </template>
        <template v-else-if="selectType === 'auth'">
          <el-form-item label="审核状态">
            <el-radio-group v-model="goods_status">
              <el-radio :value="1">审核通过</el-radio>
              <el-radio :value="2">审核拒绝</el-radio>
            </el-radio-group>
          </el-form-item>
          <el-form-item v-if="goods_status === 2" label="拒绝原因">
            <el-input v-model="auth_msg" placeholder="请填写拒绝原因" clearable />
          </el-form-item>
          <el-form-item label="商品库存">
            <el-input-number v-model="stock" :controls="false" :min="0" :precision="0" :step="1" style="width: 100%" />
          </el-form-item>
          <el-form-item label="商品标签">
            <el-select v-model="goods_label_id" multiple clearable filterable class="w-full">
              <el-option
                v-for="item in params.goods_labels || []"
                :key="item.id"
                :label="item.title"
                :value="item.id"
              />
            </el-select>
          </el-form-item>
        </template>
        <el-form-item :label="name + 'ID'">
          <el-input :model-value="dialogIdsLabel" type="textarea" autosize disabled />
        </el-form-item>
      </el-form>
      <div v-if="isTransferDialog" class="transfer-submit-panel" :class="`transfer-submit-panel--${transferSubmitReadiness.tone}`">
        <div class="transfer-submit-panel__header">
          <div>
            <div class="transfer-submit-panel__eyebrow">提交前确认</div>
            <div class="transfer-submit-panel__title">{{ transferSubmitReadiness.title }}</div>
          </div>
          <el-tag size="small" effect="plain" :type="transferSubmitReadiness.tagType">{{ transferSubmitReadiness.badge }}</el-tag>
        </div>
        <div class="transfer-submit-panel__desc">{{ transferSubmitReadiness.desc }}</div>
        <div class="transfer-submit-panel__checks">
          <div
            v-for="item in transferSubmitChecklist"
            :key="item.label"
            class="transfer-submit-check"
            :class="{ 'transfer-submit-check--done': item.done }"
          >
            <span class="transfer-submit-check__status">{{ item.done ? '已确认' : '待确认' }}</span>
            <span class="transfer-submit-check__label">{{ item.label }}</span>
          </div>
        </div>
        <div v-if="transferSubmitWarnings.length" class="transfer-submit-panel__warnings">
          <span v-for="item in transferSubmitWarnings" :key="item">{{ item }}</span>
        </div>
        <div v-if="transferRiskBoard.active" class="transfer-submit-panel__risk-board">
          <div class="transfer-submit-panel__compare-header">
            <div>
              <div class="transfer-submit-panel__compare-title">{{ transferRiskBoard.title }}</div>
              <div class="transfer-submit-panel__compare-desc">{{ transferRiskBoard.desc }}</div>
            </div>
            <el-tag size="small" effect="plain" :type="transferRiskBoard.tagType">{{ transferRiskBoard.badge }}</el-tag>
          </div>
          <div class="transfer-submit-panel__risk-grid">
            <div
              v-for="item in transferRiskBoard.items"
              :key="item.label"
              class="transfer-submit-panel__risk-item"
              :class="`transfer-submit-panel__risk-item--${item.tone}`"
            >
              <div class="transfer-submit-panel__risk-label">{{ item.label }}</div>
              <div class="transfer-submit-panel__risk-value">{{ item.value }}</div>
              <div class="transfer-submit-panel__risk-desc">{{ item.desc }}</div>
            </div>
          </div>
          <div v-if="transferRiskBoard.alerts.length" class="transfer-submit-panel__compare-alerts">
            <el-tag
              v-for="item in transferRiskBoard.alerts"
              :key="item"
              size="small"
              effect="plain"
              :type="transferRiskBoard.alertType"
            >
              {{ item }}
            </el-tag>
          </div>
          <div class="transfer-submit-panel__actions">
            <el-button
              v-if="transferManualCleanupNeeded"
              size="small"
              type="warning"
              plain
              @click="keepResolvedTransferManualIds"
            >
              仅保留已识别后再提
            </el-button>
            <el-button
              v-if="transferRiskBoard.canCopyCurrentTarget"
              size="small"
              plain
              @click="copyCurrentTransferTargetIds"
            >
              复制本批复核ID
            </el-button>
            <el-button
              v-if="transferRiskBoard.canFilterCurrentTarget"
              size="small"
              plain
              @click="applyCurrentBridgeTargetFilter"
            >
              按当前目标复核
            </el-button>
            <el-button
              v-if="transferRiskBoard.canRestoreLatestFilter"
              size="small"
              plain
              @click="restoreOperationFilters(latestTransferOperation)"
            >
              恢复最近迁移前筛选
            </el-button>
            <el-button
              v-if="transferRiskBoard.canRestoreSnapshot"
              size="small"
              plain
              @click="restoreStagedBridgeSnapshot"
            >
              恢复最近承接
            </el-button>
          </div>
        </div>
        <div
          v-if="transferRecentComparison.active"
          class="transfer-submit-panel__compare"
          :class="{ 'transfer-submit-panel__compare--warning': transferRecentComparison.warning }"
        >
          <div class="transfer-submit-panel__compare-header">
            <div>
              <div class="transfer-submit-panel__compare-title">{{ transferRecentComparison.title }}</div>
              <div class="transfer-submit-panel__compare-desc">{{ transferRecentComparison.desc }}</div>
            </div>
            <el-tag size="small" effect="plain" :type="transferRecentComparison.tagType">{{ transferRecentComparison.badge }}</el-tag>
          </div>
          <div class="transfer-submit-panel__compare-tags">
            <span v-for="item in transferRecentComparison.tags" :key="item">{{ item }}</span>
          </div>
          <div class="transfer-submit-panel__actions">
            <el-button
              size="small"
              plain
              :disabled="!transferRecentComparison.overlapIds.length"
              @click="copyTransferRecentOverlapIds"
            >
              复制重叠ID
            </el-button>
            <el-button
              size="small"
              plain
              :disabled="!transferRecentComparison.overlapIds.length"
              @click="keepTransferRecentOverlapIds"
            >
              仅复核重叠商品
            </el-button>
            <el-button
              size="small"
              plain
              :disabled="!transferRecentComparison.deltaIds.length"
              @click="copyTransferRecentDeltaIds"
            >
              复制新增ID
            </el-button>
            <el-button
              size="small"
              plain
              :disabled="!transferRecentComparison.deltaIds.length"
              @click="keepTransferRecentDeltaIds"
            >
              仅保留新增商品
            </el-button>
            <el-button
              size="small"
              plain
              :disabled="!latestTransferOperation?.querySnapshot"
              @click="restoreOperationFilters(latestTransferOperation)"
            >
              恢复最近迁移前筛选
            </el-button>
          </div>
        </div>
        <div
          v-if="transferRecentFollowUp.active"
          class="transfer-submit-panel__compare transfer-submit-panel__compare--follow-up"
        >
          <div class="transfer-submit-panel__compare-header">
            <div>
              <div class="transfer-submit-panel__compare-title">{{ transferRecentFollowUp.title }}</div>
              <div class="transfer-submit-panel__compare-desc">{{ transferRecentFollowUp.desc }}</div>
            </div>
            <el-tag size="small" effect="plain" :type="transferRecentFollowUp.tagType">{{ transferRecentFollowUp.badge }}</el-tag>
          </div>
          <div class="transfer-submit-panel__compare-tags">
            <span v-for="item in transferRecentFollowUp.tags" :key="item">{{ item }}</span>
          </div>
          <div v-if="transferRecentFollowUp.alerts.length" class="transfer-submit-panel__compare-alerts">
            <el-tag
              v-for="item in transferRecentFollowUp.alerts"
              :key="item"
              size="small"
              effect="plain"
              :type="transferRecentFollowUp.alertType"
            >
              {{ item }}
            </el-tag>
          </div>
          <div class="transfer-submit-panel__metrics">
            <div
              v-for="item in transferRecentFollowUp.metrics"
              :key="item.label"
              class="transfer-submit-panel__metric"
              :class="`transfer-submit-panel__metric--${item.tone}`"
            >
              <div class="transfer-submit-panel__metric-label">{{ item.label }}</div>
              <div class="transfer-submit-panel__metric-value">{{ item.value }}</div>
              <div class="transfer-submit-panel__metric-desc">{{ item.desc }}</div>
            </div>
          </div>
          <div class="transfer-submit-panel__actions">
            <el-button
              size="small"
              plain
              :disabled="!transferRecentFollowUp.canFilterTarget"
              @click="applyOperationMerchantFilter(latestTransferOperation)"
            >
              筛选最近目标
            </el-button>
            <el-button
              size="small"
              plain
              :disabled="!latestTransferOperation?.querySnapshot"
              @click="restoreOperationFilters(latestTransferOperation)"
            >
              恢复最近迁移前筛选
            </el-button>
            <el-button
              size="small"
              type="warning"
              plain
              :disabled="!transferRecentFollowUp.canLoadCurrentTarget"
              @click="loadLatestTransferIntoCurrentTarget"
            >
              装入当前目标复核
            </el-button>
            <el-button
              size="small"
              type="danger"
              plain
              :disabled="!transferRecentFollowUp.canLoadUnchanged"
              @click="loadLatestTransferUnchangedIds"
            >
              仅装入待复核商品
            </el-button>
            <el-button
              size="small"
              type="success"
              plain
              :disabled="!transferRecentFollowUp.canLoadChanged"
              @click="loadLatestTransferChangedIds"
            >
              仅装入已变化商品
            </el-button>
            <el-button
              size="small"
              plain
              :disabled="!transferRecentFollowUp.canLoadUnchanged"
              @click="copyLatestTransferUnchangedIds"
            >
              复制待复核ID
            </el-button>
            <el-button
              size="small"
              plain
              :disabled="!transferRecentFollowUp.canLoadChanged"
              @click="copyLatestTransferChangedIds"
            >
              复制已变化ID
            </el-button>
            <el-button
              size="small"
              type="warning"
              plain
              :disabled="!transferRecentFollowUp.canLoadPendingOverlap"
              @click="loadLatestTransferPendingOverlapIds"
            >
              仅保留重叠待复核
            </el-button>
            <el-button
              size="small"
              plain
              :disabled="!transferRecentFollowUp.canCopyCurrentTarget"
              @click="copyCurrentTransferTargetIds"
            >
              复制当前复核ID
            </el-button>
            <el-button
              size="small"
              type="primary"
              plain
              :disabled="!canStageOperationTransfer(latestTransferOperation)"
              @click="stageOperationTransfer(latestTransferOperation)"
            >
              承接最近整批
            </el-button>
            <el-button
              size="small"
              type="success"
              plain
              :disabled="!canReuseOperationTransfer(latestTransferOperation)"
              @click="reuseOperationTransfer(latestTransferOperation)"
            >
              继续最近整批
            </el-button>
          </div>
        </div>
        <div class="transfer-submit-panel__actions">
          <el-button
            v-if="transferScope === 'manual_ids' && transferManualCleanupNeeded"
            size="small"
            plain
            @click="keepResolvedTransferManualIds"
          >
            仅保留已识别
          </el-button>
          <el-button
            v-if="transferScope === 'manual_ids' && transferDraftAvailable"
            size="small"
            plain
            @click="restoreTransferDraft"
          >
            恢复草稿
          </el-button>
          <el-button
            v-if="selectType === 'transfer_merchant' && !targetMerchantLabel && transferMerchantQuickTargets.length"
            size="small"
            plain
            @click="applyTransferTargetMerchant(transferMerchantQuickTargets[0].id)"
          >
            设为常用目标
          </el-button>
          <el-button
            v-if="canNavigateFilterBack"
            size="small"
            plain
            @click="navigateFilterStep(-1)"
          >
            返回上一条筛选
          </el-button>
        </div>
      </div>
      <template #footer>
        <div v-if="selectDialogSummary.label" class="dialog-submit-summary">
          <div class="dialog-submit-summary__main">
            <span class="dialog-submit-summary__label">{{ selectDialogSummary.label }}</span>
            <span class="dialog-submit-summary__desc">{{ selectDialogSummary.desc }}</span>
          </div>
          <div v-if="selectDialogSummary.tip" class="dialog-submit-summary__tip">
            {{ selectDialogSummary.tip }}
          </div>
        </div>
        <el-button :loading="loading" :disabled="loading" @click="selectCancel">取消</el-button>
        <el-button :loading="loading" :disabled="isSelectSubmitDisabled" type="primary" @click="selectSubmit">提交</el-button>
      </template>
    </el-dialog>

    <el-dialog
      v-model="dialog"
      :title="dialogTitle"
      :close-on-click-modal="false"
      :close-on-press-escape="false"
      :before-close="cancel"
      top="5vh"
    >
      <el-form ref="ref" :rules="rules" :model="model" label-width="100px">
        <el-tabs>
          <el-tab-pane label="信息">
            <el-scrollbar native :max-height="height - 30">
              <el-form-item label="缩略图" prop="image_id">
                <FileImage v-model="model.image_id" :file-url="model.image_url" :height="100" upload />
              </el-form-item>
              <el-form-item label="轮播图" prop="images">
                <FileUploads
                  v-model="model.images"
                  upload-btn="上传图片"
                  file-type="image"
                  file-tip="图片文件"
                />
              </el-form-item>
              <el-form-item label="商品分类" prop="goods_type_id">
                <el-cascader
                  v-model="model.goods_type_id"
                  :options="params.goods_types"
                  :props="typeProps"
                  class="w-full"
                  clearable
                  filterable
                  @change="getCode"
                />
              </el-form-item>
              <el-form-item label="编码" prop="code">
                <el-input v-model="model.code" placeholder="请输入编码（唯一）" clearable />
              </el-form-item>
              <el-form-item label="商品标签" prop="goods_label_id">
                <el-select v-model="model.goods_label_id" class="w-full" clearable filterable multiple>
                  <el-option
                    v-for="item in params.goods_labels || []"
                    :key="item.id"
                    :label="item.title"
                    :value="item.id"
                  />
                </el-select>
              </el-form-item>
              <el-form-item label="商品名称" prop="title">
                <el-input v-model="model.title" placeholder="请输入商品名称" clearable />
              </el-form-item>
              <el-form-item label="商品规格" prop="spec">
                <el-input v-model="model.spec" placeholder="请输入商品规格" clearable />
              </el-form-item>
              <el-form-item label="计量单位" prop="unit">
                <el-input v-model="model.unit" placeholder="请输入计量单位" clearable />
              </el-form-item>
              <el-form-item label="商品原价" prop="original_price">
                <el-input-number v-model="model.original_price" :controls="false" :min="0.01" :precision="2" :step="1" style="width: 100%" />
              </el-form-item>
              <el-form-item label="商品价格" prop="price">
                <el-input-number v-model="model.price" :controls="false" :min="0.01" :precision="2" :step="1" style="width: 100%" />
              </el-form-item>
              <el-form-item label="商品库存" prop="stock">
                <el-input-number v-model="model.stock" :controls="false" :min="0" :precision="0" :step="1" style="width: 100%" />
              </el-form-item>
              <el-form-item label="备注" prop="remark">
                <el-input v-model="model.remark" placeholder="请输入备注" clearable />
              </el-form-item>
              <el-form-item label="排序" prop="sort">
                <el-input v-model="model.sort" type="number" placeholder="请输入排序" clearable />
              </el-form-item>
              <el-form-item v-if="model[idkey]" label="商品销量" prop="sales_sum">
                <el-input-number v-model="model.sales_sum" :controls="false" disabled :min="0" :precision="0" :step="1" style="width: 100%" />
              </el-form-item>
              <el-form-item v-if="model[idkey]" label="浏览量" prop="click_count">
                <el-input-number v-model="model.click_count" :controls="false" disabled :min="0" :precision="0" :step="1" style="width: 100%" />
              </el-form-item>
              <el-form-item v-if="model[idkey]" label="添加时间" prop="create_time">
                <el-input v-model="model.create_time" disabled />
              </el-form-item>
              <el-form-item v-if="model[idkey]" label="修改时间" prop="update_time">
                <el-input v-model="model.update_time" disabled />
              </el-form-item>
              <el-form-item v-if="model.delete_time" label="删除时间" prop="delete_time">
                <el-input v-model="model.delete_time" disabled />
              </el-form-item>
            </el-scrollbar>
          </el-tab-pane>
          <el-tab-pane label="内容">
            <el-scrollbar native :max-height="height - 30">
              <el-form-item label="内容" prop="content">
                <RichEditor v-model="model.content" />
              </el-form-item>
            </el-scrollbar>
          </el-tab-pane>
        </el-tabs>
      </el-form>
      <template #footer>
        <el-button :loading="loading" @click="cancel">取消</el-button>
        <el-button :loading="loading" type="primary" @click="submit">提交</el-button>
      </template>
    </el-dialog>

    <el-drawer
      v-model="detailDrawer"
      title="商品详情"
      size="560px"
      :destroy-on-close="false"
    >
      <div v-loading="detailLoading" class="goods-detail">
        <template v-if="detailModel">
          <div class="goods-detail__hero">
            <FileImage :file-url="detailModel.image_url" :height="108" fileSource="list" />
            <div class="goods-detail__hero-meta">
              <div class="goods-detail__title">{{ detailModel.title || `商品#${detailModel.id}` }}</div>
              <div class="goods-detail__code">编码：{{ detailModel.code || '--' }}</div>
              <div class="goods-detail__tags">
                <el-tag effect="plain">{{ resolveMerchantTitle(detailModel.merchant_id) }}</el-tag>
                <el-tag effect="plain" :type="Number(detailModel.is_disable) === 0 ? 'success' : 'warning'">
                  {{ Number(detailModel.is_disable) === 0 ? '已上架' : '已下架' }}
                </el-tag>
                <el-tag effect="plain" :type="detailModel.status === 1 ? 'success' : detailModel.status === 2 ? 'danger' : 'info'">
                  {{ detailModel.status === 1 ? '审核通过' : detailModel.status === 2 ? '审核失败' : '待审核' }}
                </el-tag>
              </div>
            </div>
          </div>

          <div class="goods-detail__grid">
            <div class="goods-detail__card">
              <div class="goods-detail__label">商品分类</div>
              <div class="goods-detail__value">{{ detailModel.type_title || '--' }}</div>
            </div>
            <div class="goods-detail__card">
              <div class="goods-detail__label">商品标签</div>
              <div class="goods-detail__value">{{ detailModel.label_title || '--' }}</div>
            </div>
            <div class="goods-detail__card">
              <div class="goods-detail__label">销售价</div>
              <div class="goods-detail__value">¥{{ formatMoney(detailModel.price) }}</div>
            </div>
            <div class="goods-detail__card">
              <div class="goods-detail__label">原价</div>
              <div class="goods-detail__value">¥{{ formatMoney(detailModel.original_price) }}</div>
            </div>
            <div class="goods-detail__card">
              <div class="goods-detail__label">库存</div>
              <div class="goods-detail__value">{{ detailModel.stock ?? '--' }}</div>
            </div>
            <div class="goods-detail__card">
              <div class="goods-detail__label">销量</div>
              <div class="goods-detail__value">{{ detailModel.sales_sum ?? '--' }}</div>
            </div>
          </div>

          <div class="goods-detail__section">
            <div class="goods-detail__section-title">运营信息</div>
            <div class="goods-detail__rows">
              <div class="goods-detail__row"><span>规格</span><strong>{{ detailModel.spec || '--' }}</strong></div>
              <div class="goods-detail__row"><span>单位</span><strong>{{ detailModel.unit || '--' }}</strong></div>
              <div class="goods-detail__row"><span>点击量</span><strong>{{ detailModel.click_count ?? '--' }}</strong></div>
              <div class="goods-detail__row"><span>排序</span><strong>{{ detailModel.sort ?? '--' }}</strong></div>
              <div class="goods-detail__row"><span>添加时间</span><strong>{{ detailModel.create_time || '--' }}</strong></div>
              <div class="goods-detail__row"><span>修改时间</span><strong>{{ detailModel.update_time || '--' }}</strong></div>
            </div>
          </div>

          <div class="goods-detail__section">
            <div class="goods-detail__section-title">备注与审核</div>
            <div class="goods-detail__text">{{ detailModel.remark || '暂无备注' }}</div>
            <div v-if="detailModel.auth_msg" class="goods-detail__alert">{{ detailModel.auth_msg }}</div>
          </div>
        </template>
      </div>
    </el-drawer>
  </div>
</template>

<script>
import screenHeight from '@/utils/screen-height'
import Pagination from '@/components/Pagination/index.vue'
import RichEditor from '@/components/RichEditor/index.vue'
import checkPermission from '@/utils/permission'
import { arrayColumn } from '@/utils/index'
import { getPageLimit } from '@/utils/settings'
import { list, info, add, edit, dele, disable, params, code, auth as is_auth, transferToPlatform, transferToMerchant, batchUpdateThumbnail, batchUpdateLabels } from '@/api/goods/goods'

export default {
  name: 'goods',
  components: { Pagination, RichEditor },
  data() {
    return {
      name: '商品',
      height: 680,
      loading: false,
      idkey: 'id',
      query: {
        page: 1,
        limit: getPageLimit(),
        search_field: 'title',
        search_exp: 'like',
        date_field: 'create_time',
        date_value: [],
        is_disable: undefined,
        goods_type_id: undefined,
        goods_label_id: undefined,
        merchant_id: -1,
        status: undefined
      },
      data: [],
      count: 0,
      dialog: false,
      dialogTitle: '',
      model: {
        id: '',
        code: '',
        goods_type_id: null,
        goods_label_id: [],
        image_id: 0,
        image_url: '',
        title: '',
        content: '',
        remark: '',
        sort: 250,
        images: [],
        original_price: undefined,
        price: undefined,
        sales_sum: undefined,
        click_count: undefined,
        spec: undefined,
        unit: undefined,
        stock: undefined,
        video_id: undefined
      },
      rules: {
        title: [{ required: true, message: '请输入商品名称', trigger: 'blur' }],
        code: [{ required: true, message: '请输入商品编码', trigger: 'blur' }],
        image_id: [{ required: true, message: '请上传缩略图', trigger: 'blur' }],
        goods_type_id: [{ required: true, message: '请选择商品分类', trigger: 'blur' }],
        price: [{ required: true, message: '请输入商品价格', trigger: 'blur' }]
      },
      typeProps: {
        checkStrictly: false,
        value: 'id',
        label: 'title',
        multiple: false,
        emitPath: false
      },
      params: {},
      status_nums: {},
      selection: [],
      selectIds: '',
      selectTitle: '操作',
      selectDialog: false,
      selectType: '',
      is_disable: 0,
      goods_label_id: [],
      goods_status: 1,
      auth_msg: '',
      stock: 1,
      batch_goods_label_id: [],
      batch_image_id: 0,
      batch_image_url: '',
      target_merchant_id: undefined,
      batchScope: 'selected',
      transferScope: 'selected',
      transferManualIdsText: '',
      bridgeMode: 'platform',
      activeQuickFilter: 'all',
      latestOperation: null,
      operationHistory: [],
      operationHistoryKeyword: '',
      operationHistoryType: '',
      transferDraftAvailable: false,
      transferDraftHint: '',
      stagedBridgeSnapshotAvailable: false,
      stagedBridgeSnapshotHint: '',
      latestTransferExceptionFilter: 'unchanged',
      operationWorkbenchExpanded: false,
      recentFilterHistory: [],
      filterTimeline: [],
      filterTimelineIndex: -1,
      currentFilterStepKey: '',
      legacyEnhancementPanelEnabled: false,
      lastRouteQuery: {},
      detailDrawer: false,
      detailLoading: false,
      detailModel: null,
      ignoreRouteWatch: false,
      pendingEntryAnchorAlign: false
    }
  },
  computed: {
    merchantOptions() {
      return Array.isArray(this.params?.merchant_list) ? this.params.merchant_list : []
    },
    merchantQuickPicks() {
      return this.merchantOptions.slice(0, 10)
    },
    goodsStatusOptions() {
      return Array.isArray(this.params?.goods_status) ? this.params.goods_status : []
    },
    heroSelectionLabel() {
      return this.selection.length ? `已勾选 ${this.selection.length} 件` : '未勾选商品'
    },
    heroDecisionStrip() {
      const tags = [
        this.heroSelectionLabel,
        this.activeFilterTags.length ? `${this.activeFilterTags.length} 项筛选条件` : '默认筛选',
        this.operationWorkbenchExpanded ? '高级工作台已展开' : '首屏清爽模式'
      ]
      if (this.stagedBridgeSummary.active) {
        return {
          tone: 'warning',
          title: '先处理当前待继续迁移批次',
          desc: '当前已经有一批商品带入了承接区。比起重新筛选，更适合先继续迁移、恢复快照或复核目标归属，避免批次断开。',
          tags: [...tags, this.stagedBridgeSummary.title]
        }
      }
      if (this.selection.length) {
        return {
          tone: 'info',
          title: '已选中商品，可以直接进入批量处理',
          desc: this.bridgeMode === 'merchant'
            ? (this.targetMerchantLabel
              ? `当前更适合把这批商品带入迁移工具，直接承接到 ${this.targetMerchantLabel}，或先做审核、打标等批量动作。`
              : '当前可以先做审核、打标、换图等批量动作；如果要转商家，先选定目标商家再承接。')
            : '当前可以直接做审核、上下架、打标，也可以带入迁移工具承接到平台自营。',
          tags
        }
      }
      if (this.activeFilterTags.length) {
        return {
          tone: 'success',
          title: '筛选范围已经缩小，适合按当前页整批处理',
          desc: '你已经把商品池缩到了更具体的范围。现在更适合先看当前页结果，再决定整页上架、整页打标，还是勾选后精确迁移。',
          tags: [...tags, ...this.activeFilterTags.slice(0, 2)]
        }
      }
      return {
        tone: 'default',
        title: '先用筛选把范围缩小，再决定批量动作',
        desc: '默认不要急着展开全部高级能力。先按状态、归属、关键词把范围缩小，等列表更干净以后，再做审核、换图或归属迁移会更稳。',
        tags
      }
    },
    targetMerchantLabel() {
      const target = this.merchantOptions.find((item) => Number(item.id) === Number(this.target_merchant_id))
      return target ? target.title : ''
    },
    bridgePreferenceLabel() {
      if (this.bridgeMode === 'merchant' && this.targetMerchantLabel) {
        return `已记忆偏好：迁移到商家 / ${this.targetMerchantLabel}`
      }
      if (this.bridgeMode === 'merchant') {
        return '已记忆偏好：迁移到商家'
      }
      return '已记忆偏好：迁移到平台'
    },
    currentFilterMerchantTarget() {
      if (Number(this.query.merchant_id) <= 0) return null
      const merchant = this.merchantOptions.find((item) => Number(item.id) === Number(this.query.merchant_id))
      return merchant ? { id: Number(merchant.id), title: merchant.title } : null
    },
    transferMerchantQuickTargets() {
      const bucket = []
      const seen = new Set()
      const pushMerchant = (merchantId) => {
        const id = Number(merchantId)
        if (id <= 0 || seen.has(id)) return
        const merchant = this.merchantOptions.find((item) => Number(item.id) === id)
        if (!merchant) return
        seen.add(id)
        bucket.push({ id, title: merchant.title })
      }
      if (Number(this.target_merchant_id) > 0) {
        pushMerchant(this.target_merchant_id)
      }
      if (this.currentFilterMerchantTarget) {
        pushMerchant(this.currentFilterMerchantTarget.id)
      }
      this.operationHistory.forEach((item) => {
        if (item?.label === '迁移到商家') {
          pushMerchant(item.merchant_id)
        }
      })
      if (bucket.length < 4) {
        this.merchantOptions.forEach((item) => {
          if (bucket.length >= 4) return
          pushMerchant(item.id)
        })
      }
      return bucket.slice(0, 6)
    },
    bridgeQuickTargets() {
      return [
        { key: 'platform', type: 'platform', title: '平台自营' },
        ...this.transferMerchantQuickTargets.map((item) => ({
          key: `merchant-${item.id}`,
          type: 'merchant',
          id: item.id,
          title: item.title
        }))
      ]
    },
    batchScopedSelectTypes() {
      return ['disable', 'batch_label', 'batch_thumbnail']
    },
    batchTargetRows() {
      if (this.batchScope === 'current_page') return this.data
      return this.selection
    },
    batchTargetIds() {
      return this.selectGetIds(this.batchTargetRows)
    },
    transferTargetRows() {
      if (this.transferScope === 'manual_ids') {
        return this.collectGoodsRowsByIds(this.transferManualIds)
      }
      if (this.transferScope === 'current_page') return this.data
      return this.selection
    },
    transferManualMeta() {
      return this.parseGoodsIdMeta(this.transferManualIdsText)
    },
    transferManualIds() {
      return this.transferManualMeta.ids
    },
    transferManualMatchedIds() {
      return this.transferTargetRows.map((item) => Number(item[this.idkey]))
    },
    transferManualPendingIds() {
      const matched = new Set(this.transferManualMatchedIds)
      return this.transferManualIds.filter((id) => !matched.has(Number(id)))
    },
    transferManualPreviewItems() {
      return this.transferTargetRows.slice(0, 5).map((item) => ({
        id: Number(item.id),
        title: `${item.title || '未命名商品'} (#${item.id})`,
        ownership: Number(item.merchant_id) > 0 ? `当前归属：${this.resolveMerchantTitle(item.merchant_id)}` : '当前归属：平台自营'
      }))
    },
    transferManualPreviewTitles() {
      return this.transferManualPreviewItems.map((item) => item.title)
    },
    transferManualOwnershipSummary() {
      return this.transferTargetRows.reduce((summary, item) => {
        if (Number(item.merchant_id) > 0) {
          summary.merchant += 1
        } else {
          summary.platform += 1
        }
        return summary
      }, { platform: 0, merchant: 0 })
    },
    transferManualChangeSummary() {
      return this.transferTargetRows.reduce((summary, item) => {
        const currentMerchantId = Number(item.merchant_id)
        const targetMerchantId = this.selectType === 'transfer_merchant'
          ? Number(this.target_merchant_id || 0)
          : 0
        if (currentMerchantId === targetMerchantId) {
          summary.unchanged += 1
        } else {
          summary.changed += 1
        }
        return summary
      }, { changed: 0, unchanged: 0 })
    },
    transferManualPreviewTargetLabel() {
      if (this.selectType === 'transfer_merchant') {
        return this.targetMerchantLabel || '目标商家'
      }
      return '平台自营'
    },
    transferManualPreviewOverflowCount() {
      return Math.max(this.transferTargetRows.length - this.transferManualPreviewTitles.length, 0)
    },
    transferManualCleanupNeeded() {
      return !!(this.transferManualMeta.invalidTokens.length || this.transferManualPendingIds.length)
    },
    transferTargetIds() {
      if (this.transferScope === 'manual_ids') return this.transferManualIds
      return this.selectGetIds(this.transferTargetRows)
    },
    dialogIdsLabel() {
      if (this.batchScopedSelectTypes.includes(this.selectType)) {
        return this.batchTargetIds.toString()
      }
      if (this.selectType === 'transfer_platform' || this.selectType === 'transfer_merchant') {
        return this.transferTargetIds.toString()
      }
      return this.selectIds
    },
    batchScopeDescription() {
      if (this.batchScope === 'current_page') {
        return `当前会处理本页 ${this.data.length} 件商品，适合按筛选结果整页执行。`
      }
      return this.selection.length
        ? `当前会处理已勾选的 ${this.selection.length} 件商品，适合精确处理。`
        : '请先勾选商品，或改为处理当前页。'
    },
    transferScopeDescription() {
      if (this.transferScope === 'manual_ids') {
        return this.transferManualIds.length
          ? `当前会按输入的 ${this.transferManualIds.length} 个商品 ID 精确迁移。已识别 ${this.transferManualMatchedIds.length} 个。`
          : '支持输入部分商品 ID，适合精确迁移指定商品。'
      }
      if (this.transferScope === 'current_page') {
        return `当前会处理本页 ${this.data.length} 件商品，适合按筛选结果整页迁移。`
      }
      return this.selection.length
        ? `当前会处理已勾选的 ${this.selection.length} 件商品，适合精确迁移。`
        : '请先勾选商品，或改为处理当前页。'
    },
    summaryCards() {
      return [
        {
          key: 'all',
          label: '全部商品',
          value: this.formatCount(this.status_nums.all),
          meta: '当前全量商品池',
          tone: 'all'
        },
        {
          key: 'auth',
          label: '待审核',
          value: this.formatCount(this.status_nums.auth),
          meta: '需要优先处理',
          tone: 'pending'
        },
        {
          key: 'auth_success',
          label: '审核通过',
          value: this.formatCount(this.status_nums.auth_success),
          meta: '已可正常运营',
          tone: 'success'
        },
        {
          key: 'auth_error',
          label: '审核失败',
          value: this.formatCount(this.status_nums.auth_error),
          meta: '需复核整改',
          tone: 'danger'
        },
        {
          key: 'current_page',
          label: '当前页商品',
          value: this.formatCount(this.data.length),
          meta: `第 ${this.query.page} 页结果`,
          tone: 'info'
        },
        {
          key: 'selected',
          label: '当前勾选',
          value: this.formatCount(this.selection.length),
          meta: '批量操作对象',
          tone: 'warning'
        }
      ]
    },
    quickFilters() {
      return [
        { key: 'all', label: '全部商品', query: { status: undefined, merchant_id: -1, is_disable: undefined } },
        { key: 'pending', label: '待审核', query: { status: 0 } },
        { key: 'approved', label: '审核通过', query: { status: 1 } },
        { key: 'rejected', label: '审核失败', query: { status: 2 } },
        { key: 'platform', label: '平台自营', query: { merchant_id: 0 } },
        { key: 'shelf', label: '已上架', query: { is_disable: 0 } }
      ]
    },
    combinedFilterPresets() {
      return [
        { key: 'pending-offline', label: '待审核且下架', query: { status: 0, is_disable: 1, merchant_id: -1 } },
        { key: 'platform-live', label: '平台自营且上架', query: { merchant_id: 0, is_disable: 0, status: undefined } },
        { key: 'merchant-pending', label: '商家商品待审核', query: { merchant_id: '__merchant__', status: 0, is_disable: undefined } },
        { key: 'rejected-offline', label: '审核失败且下架', query: { status: 2, is_disable: 1, merchant_id: -1 } }
      ]
    },
    activeFilterTags() {
      const tags = []
      if (this.query.status !== undefined) {
        const status = this.goodsStatusOptions.find((item) => Number(item.value) === Number(this.query.status))
        if (status) tags.push(`状态：${status.label}`)
      }
      if (this.query.is_disable !== undefined) {
        tags.push(`上架：${Number(this.query.is_disable) === 0 ? '上架' : '下架'}`)
      }
      if (this.query.goods_type_id) {
        tags.push('已筛分类')
      }
      if (this.query.goods_label_id) {
        tags.push('已筛标签')
      }
      if (Number(this.query.merchant_id) === 0) {
        tags.push('平台自营')
      } else if (Number(this.query.merchant_id) > 0) {
        const merchant = this.merchantOptions.find((item) => Number(item.id) === Number(this.query.merchant_id))
        if (merchant) tags.push(`商家：${merchant.title}`)
      }
      if (this.query.search_value) {
        tags.push(`关键词：${this.query.search_value}`)
      }
      if (Array.isArray(this.query.date_value) && this.query.date_value.length === 2) {
        tags.push(`日期：${this.query.date_value[0]} 至 ${this.query.date_value[1]}`)
      }
      return tags
    },
    shortcutActions() {
      return [
        {
          key: 'create',
          title: '新增商品',
          desc: '快速录入新商品并进入编辑状态',
          disabled: false,
          action: this.add
        },
        {
          key: 'audit',
          title: '批量审核',
          desc: this.selection.length ? `审核 ${this.selection.length} 件已选商品` : '先勾选商品后批量审核',
          disabled: !this.selection.length,
          action: () => this.selectOpen('auth')
        },
        {
          key: 'batch-up',
          title: '批量上架',
          desc: this.selection.length ? '快速把已选商品统一上架' : '可按当前筛选页统一上架',
          disabled: !this.data.length,
          action: () => this.quickDisableSelected(0)
        },
        {
          key: 'batch-label',
          title: '批量打标',
          desc: this.selection.length ? '统一调整已选商品标签' : '可按当前筛选页批量更新标签',
          disabled: !this.data.length,
          action: () => this.selectOpen('batch_label')
        },
        {
          key: 'batch-thumbnail',
          title: '批量换图',
          desc: this.selection.length ? '统一更新已选商品缩略图' : '可按当前筛选页批量调整缩略图',
          disabled: !this.data.length,
          action: () => this.selectOpen('batch_thumbnail')
        },
        {
          key: 'bridge-platform',
          title: '迁移到平台',
          desc: this.selection.length ? '把已选商品转为平台自营' : '勾选后可执行平台迁移',
          disabled: !this.data.length || !this.checkPermission(['admin/goods.Goods/transferToPlatform']),
          action: () => this.selectOpen('transfer_platform')
        },
        {
          key: 'bridge-merchant',
          title: '迁移到商家',
          desc: this.selection.length ? '把已选商品转给指定商家' : '勾选后可执行商家迁移',
          disabled: !this.data.length || !this.checkPermission(['admin/goods.Goods/transferToMerchant']),
          action: () => this.openTransferMerchantDialog()
        }
      ]
    },
    heroGuideCards() {
      return [
        {
          title: '第一步：先判断你现在是在看池子还是在处理批次',
          desc: this.stagedBridgeSummary.active
            ? '当前已经不是纯看列表，而是在处理一个待继续的迁移批次。'
            : '当前更像在线上首屏看商品池，先别急着展开所有高级功能。',
          action: this.stagedBridgeSummary.active ? this.stagedBridgeSummary.desc : this.compactWorkbenchSummary.desc
        },
        {
          title: '第二步：再决定按筛选整页处理，还是按勾选精确处理',
          desc: '筛选更适合清一类商品，勾选更适合精确改一批商品，两种节奏不要混着走。',
          action: this.selection.length
            ? `当前已勾选 ${this.selection.length} 件，适合精确处理。`
            : `当前页有 ${this.data.length} 件商品，适合先按筛选结果整页观察。`
        },
        {
          title: '第三步：涉及迁移时，先核目标归属再提交',
          desc: '迁移类动作风险最高，最好先看目标商家、当前归属分布和变化摘要，再点提交。',
          action: this.bridgeMode === 'merchant'
            ? `当前迁移模式：转商家${this.targetMerchantLabel ? ` / ${this.targetMerchantLabel}` : ' / 待选择目标' }`
            : '当前迁移模式：转平台自营'
        }
      ]
    },
    actionGroups() {
      return [
        {
          key: 'status',
          title: '状态处理',
          desc: '把上架、下架、审核这类高频状态动作集中处理。',
          items: [
            { key: 'up', label: '批量上架', disabled: !this.data.length, action: () => this.quickDisableSelected(0) },
            { key: 'down', label: '批量下架', disabled: !this.data.length, action: () => this.quickDisableSelected(1) },
            { key: 'auth', label: '批量审核', disabled: !this.selection.length, action: () => this.selectOpen('auth') }
          ]
        },
        {
          key: 'content',
          title: '图文维护',
          desc: '适合集中处理标签和缩略图这类运营素材。',
          items: [
            { key: 'label', label: '批量打标', disabled: !this.data.length, action: () => this.selectOpen('batch_label') },
            { key: 'thumb', label: '批量换图', disabled: !this.data.length, action: () => this.selectOpen('batch_thumbnail') }
          ]
        },
        {
          key: 'ownership',
          title: '归属迁移',
          desc: '处理平台与商家之间的商品归属切换。',
          items: [
            {
              key: 'platform',
              label: '迁移到平台',
              disabled: !this.data.length || !this.checkPermission(['admin/goods.Goods/transferToPlatform']),
              action: () => this.selectOpen('transfer_platform')
            },
            {
              key: 'merchant',
              label: '迁移到商家',
              disabled: !this.data.length || !this.checkPermission(['admin/goods.Goods/transferToMerchant']),
              action: () => this.openTransferMerchantDialog()
            }
          ]
        }
      ]
    },
    bridgeSteps() {
      return [
        { step: 1, title: '先用筛选缩小范围', desc: '先用上方筛选条件把要迁移的商品范围缩小。' },
        { step: 2, title: '确认处理范围', desc: '打开迁移弹窗后，可选整页处理或只处理已勾选商品。' },
        { step: 3, title: '核对目标商家', desc: '若迁移到指定商家，可先搜索商家名称或 ID 再选择。' },
        { step: 4, title: '确认摘要再执行', desc: '底部会实时显示迁移摘要，确认无误后再提交。' }
      ]
    },
    bridgeActionLabel() {
      return this.bridgeMode === 'merchant' ? '执行当前商家迁移' : '执行当前平台迁移'
    },
    compactWorkbenchSummary() {
      const tags = [
        this.selection.length ? `已勾选 ${this.selection.length} 件` : '未勾选商品',
        this.stagedBridgeSummary.active ? `承接 ${this.transferManualIds.length} 件` : '无承接批次',
        this.latestTransferExceptionTracker.active
          ? `异常追踪 ${this.latestTransferExceptionTracker.sources.reduce((sum, item) => sum + Number(item.count || 0), 0)} 项`
          : '暂无异常追踪'
      ]
      if (this.latestOperation?.target) {
        tags.push(`最近目标 ${this.latestOperation.target}`)
      }
      if (!this.operationWorkbenchExpanded) {
        return {
          title: '线上优先模式',
          desc: this.stagedBridgeSummary.active
            ? '当前有可继续处理的迁移批次，先保持列表清爽，需要时再展开复核台继续处理。'
            : '首屏只保留线上更常用的列表与快捷入口，复核、追踪、历史等增强能力按需展开。',
          tags
        }
      }
      return {
        title: '高级操作工作台已展开',
        desc: '当前已显示迁移复核、异常追踪、批次历史和高级承接工具，适合连续运营处理。',
        tags
      }
    },
    bridgeSummaryLabel() {
      return this.bridgeMode === 'merchant' ? '当前模式：迁移到商家' : '当前模式：迁移到平台'
    },
    canStageSelectionForCurrentBridge() {
      if (!this.selection.length) return false
      if (this.bridgeMode === 'platform') {
        return this.checkPermission(['admin/goods.Goods/transferToPlatform'])
      }
      return !!(this.targetMerchantLabel && this.checkPermission(['admin/goods.Goods/transferToMerchant']))
    },
    selectionStageActionLabel() {
      if (this.bridgeMode === 'merchant') {
        return this.targetMerchantLabel ? `带入当前工具（${this.targetMerchantLabel}）` : '带入当前工具'
      }
      return '带入当前工具（平台）'
    },
    selectionStageSummary() {
      if (!this.selection.length) {
        return '先勾选商品后，这里可以把当前批次直接承接到迁移工具里。'
      }
      if (this.bridgeMode === 'merchant') {
        if (!this.targetMerchantLabel) {
          return `已勾选 ${this.selection.length} 件商品，当前是转商家模式，请先选定目标商家。`
        }
        return `已勾选 ${this.selection.length} 件商品，可直接带入迁移工具并承接到 ${this.targetMerchantLabel}。`
      }
      return `已勾选 ${this.selection.length} 件商品，可直接带入迁移工具并承接到平台自营。`
    },
    stagedBridgeSnapshotSummary() {
      if (!this.stagedBridgeSnapshotAvailable) {
        return {
          active: false,
          title: '暂无最近承接快照',
          desc: '把一批商品带入迁移工具后，这里会保留最近一次承接快照，方便清空或改筛选后快速恢复。'
        }
      }
      return {
        active: true,
        title: '存在可恢复承接快照',
        desc: this.stagedBridgeSnapshotHint
      }
    },
    stagedBridgeSummary() {
      if (this.transferScope !== 'manual_ids' || !this.transferManualIds.length) {
        return {
          active: false,
          title: '未承接历史批次',
          desc: '从重点批次点击“带入工具”后，这里会显示当前待迁移对象和快捷动作。',
          tags: []
        }
      }
      const matchedCount = this.transferTargetRows.length
      const pendingCount = this.transferManualPendingIds.length
      const invalidCount = this.transferManualMeta.invalidTokens.length
      const targetLabel = this.bridgeMode === 'merchant'
        ? (this.targetMerchantLabel || '待确认商家')
        : '平台自营'
      const tags = [
        `已带入 ${this.transferManualIds.length} 件`,
        `已识别 ${matchedCount} 件`
      ]
      if (pendingCount) {
        tags.push(`待补查 ${pendingCount} 件`)
      }
      if (invalidCount) {
        tags.push(`异常输入 ${invalidCount} 项`)
      }
      return {
        active: true,
        title: `目标：${targetLabel}`,
        desc: pendingCount
          ? `当前已承接一批手动指定商品，仍有 ${pendingCount} 个 ID 未在当前列表中识别，提交前建议先核对。`
          : '当前已承接一批手动指定商品，可以直接继续迁移或切换筛选复核归属。',
        tags
      }
    },
    bridgeStageMergePanel() {
      const stagedIds = this.transferScope === 'manual_ids' ? this.transferManualIds : []
      const selectionIds = this.selectGetIds(this.selection).map((item) => Number(item)).filter((item) => item > 0)
      const stagedSet = new Set(stagedIds.map((item) => Number(item)).filter((item) => item > 0))
      const overlapIds = selectionIds.filter((id) => stagedSet.has(id))
      const selectionOnlyIds = selectionIds.filter((id) => !stagedSet.has(id))
      const tags = []
      if (stagedIds.length) tags.push(`承接 ${stagedIds.length} 件`)
      if (selectionIds.length) tags.push(`勾选 ${selectionIds.length} 件`)
      if (overlapIds.length) tags.push(`重叠 ${overlapIds.length} 件`)
      if (selectionOnlyIds.length) tags.push(`新增 ${selectionOnlyIds.length} 件`)
      if (!stagedIds.length && !selectionIds.length) {
        return {
          active: false,
          title: '等待形成承接批次或勾选批次',
          desc: '先承接一批商品，或先在表格里勾选商品后，这里会帮你判断是合并、续接还是保持现状。',
          tags: [],
          canMerge: false,
          canAppendDelta: false,
          selectionOnlyIds: []
        }
      }
      if (!stagedIds.length) {
        return {
          active: true,
          title: '当前只有勾选批次，尚未形成承接批次',
          desc: selectionIds.length
            ? '可以先把勾选商品带入迁移工具，后续再继续增量补单。'
            : '先勾选商品后，这里会提示是否值得合并进当前承接批次。',
          tags,
          canMerge: false,
          canAppendDelta: false,
          selectionOnlyIds
        }
      }
      if (!selectionIds.length) {
        return {
          active: true,
          title: '当前承接批次已就绪，等待新的表格勾选',
          desc: '如果你又在列表里找到一批要补迁移的商品，直接勾选后就能在这里做合并去重。',
          tags,
          canMerge: false,
          canAppendDelta: false,
          selectionOnlyIds: []
        }
      }
      if (!selectionOnlyIds.length) {
        return {
          active: true,
          title: '当前勾选都已经包含在承接批次里',
          desc: overlapIds.length
            ? '这批勾选无需重复并入，适合直接继续迁移或改做复核。'
            : '当前没有新增商品需要并入承接批次。',
          tags,
          canMerge: true,
          canAppendDelta: false,
          selectionOnlyIds: []
        }
      }
      return {
        active: true,
        title: `当前还有 ${selectionOnlyIds.length} 件新增勾选可并入承接批次`,
        desc: overlapIds.length
          ? `你补勾了一批新商品，其中 ${overlapIds.length} 件已在承接批次里，建议直接只追加新增部分，避免重复迁移。`
          : '这批勾选都是新增商品，可以直接合并进当前承接批次，形成更完整的连续迁移对象。',
        tags,
        canMerge: true,
        canAppendDelta: true,
        selectionOnlyIds
      }
    },
    bridgeBattlePanel() {
      const active = this.selection.length > 0 || this.stagedBridgeSummary.active
      const tags = [
        `勾选 ${this.selection.length} 件`,
        `当前页 ${this.data.length} 件`
      ]
      if (this.stagedBridgeSummary.active) {
        tags.push(`承接 ${this.transferManualIds.length} 件`)
      }
      if (this.transferManualPendingIds.length) {
        tags.push(`待补查 ${this.transferManualPendingIds.length} 件`)
      }
      if (this.latestOperation?.querySnapshotLabel) {
        tags.push(`最近筛选：${this.latestOperation.querySnapshotLabel}`)
      }
      if (!active) {
        return {
          active: false,
          title: '等待开始批次处理',
          desc: '先勾选商品，或从最近操作里带入一批商品后，这里会给出下一步最顺手的动作。',
          tags: []
        }
      }
      if (this.stagedBridgeSummary.active) {
        return {
          active: true,
          title: this.bridgeMode === 'merchant'
            ? `优先复核后迁移到 ${this.targetMerchantLabel || '目标商家'}`
            : '优先复核后迁移到平台自营',
          desc: this.transferManualPendingIds.length
            ? '当前承接批次里还有待补查商品，建议先过滤当前目标或恢复最近筛选，再继续提交。'
            : '当前承接批次已经就绪，可以直接继续迁移，或先按目标归属过滤做最后复核。',
          tags
        }
      }
      return {
        active: true,
        title: this.bridgeMode === 'merchant'
          ? `先把勾选商品承接到 ${this.targetMerchantLabel || '当前商家目标'}`
          : '先把勾选商品承接到平台工具',
        desc: this.bridgeMode === 'merchant' && !this.targetMerchantLabel
          ? '当前是转商家模式，但还没明确目标商家。建议先选目标，再把勾选商品带入工具。'
          : '当前勾选已经形成一个临时批次，建议先带入迁移工具，再决定是否立即提交。',
        tags
      }
    },
    latestTransferOperation() {
      return this.operationHistory.find((item) => this.resolveOperationHistoryType(item) === 'transfer') || null
    },
    latestTransferReviewPanel() {
      const operation = this.latestTransferOperation
      if (!operation) {
        return {
          active: false,
          title: '暂无可复核迁移',
          desc: '完成一次商品迁移后，这里会展示目标、变化数量和快速复核入口。',
          tags: []
        }
      }
      const changedCount = Number(operation.diff?.changedCount || 0)
      const unchangedCount = Number(operation.diff?.unchangedCount || 0)
      const tags = [
        `目标 ${operation.target || '未识别'}`
      ]
      if (changedCount || unchangedCount) {
        tags.push(`变化 ${changedCount} 件`)
        if (unchangedCount) {
          tags.push(`保持原归属 ${unchangedCount} 件`)
        }
      }
      if (operation.querySnapshotLabel) {
        tags.push(`操作前 ${operation.querySnapshotLabel}`)
      }
      return {
        active: true,
        title: operation.target ? `最近迁移到 ${operation.target}` : '最近迁移批次待复核',
        desc: unchangedCount
          ? `这次迁移里有 ${unchangedCount} 件商品保持原归属，建议先按目标归属筛选，再对照迁移前筛选做一轮复核。`
          : '这次迁移已经产生实际归属变化，适合直接按目标筛选确认结果，或复用原批次继续处理。',
        tags
      }
    },
    latestTransferExceptionTracker() {
      const operation = this.latestTransferOperation
      if (!operation || this.resolveOperationHistoryType(operation) !== 'transfer') {
        return {
          active: false,
          title: '',
          desc: '',
          badge: '',
          tagType: 'info',
          items: [],
          alerts: [],
          sources: [],
          canTrackUnchanged: false,
          canTrackChanged: false,
          canCopyUnchanged: false,
          canCopyChanged: false
        }
      }
      const ids = Array.isArray(operation.ids) ? operation.ids.map((item) => Number(item)).filter((item) => item > 0) : []
      const unchangedIds = Array.isArray(operation.diff?.unchangedIds)
        ? operation.diff.unchangedIds.map((item) => Number(item)).filter((item) => item > 0)
        : []
      const changedIds = Array.isArray(operation.diff?.changedIds)
        ? operation.diff.changedIds.map((item) => Number(item)).filter((item) => item > 0)
        : []
      if (!ids.length) {
        return {
          active: false,
          title: '',
          desc: '',
          badge: '',
          tagType: 'info',
          items: [],
          alerts: [],
          sources: [],
          canTrackUnchanged: false,
          canTrackChanged: false,
          canCopyUnchanged: false,
          canCopyChanged: false
        }
      }
      const unchangedCount = unchangedIds.length
      const changedCount = changedIds.length
      const totalCount = ids.length
      const unchangedRate = totalCount ? Math.round((unchangedCount / totalCount) * 100) : 0
      const bulkCount = totalCount >= 20 ? totalCount : 0
      const alerts = []
      if (unchangedCount) alerts.push(`有 ${unchangedCount} 件商品迁移后未发生归属变化，建议优先追踪`)
      if (changedCount && unchangedCount) alerts.push('这批迁移同时包含有效变化和异常未变化对象，适合拆批复核')
      if (totalCount >= 20) alerts.push(`这是一批 ${totalCount} 件的大批量迁移，建议保留异常批次继续跟踪`)
      const sources = [
        {
          key: 'unchanged',
          label: '未变化',
          count: unchangedCount,
          title: '未变化异常来源',
          desc: unchangedCount ? '这些商品迁移后仍保持原归属，适合优先回查。' : '当前没有未变化异常来源。',
          ids: unchangedIds
        },
        {
          key: 'bulk',
          label: '大批量',
          count: bulkCount,
          title: '大批量来源',
          desc: bulkCount ? '整批迁移体量较大，适合整体承接后继续分批复核。' : '当前批次体量未达到大批量追踪阈值。',
          ids
        },
        {
          key: 'duplicate',
          label: '重复目标',
          count: unchangedCount,
          title: '重复目标来源',
          desc: unchangedCount ? '这些商品本来就处在当前目标方向，更像重复迁移，建议单独确认。' : '当前没有重复目标来源。',
          ids: unchangedIds
        }
      ]
      return {
        active: true,
        title: unchangedCount ? '迁移异常批次追踪入口' : '迁移结果追踪入口',
        desc: unchangedCount
          ? '最近迁移里已经识别到未变化对象，可以直接把异常批次承接回工具继续追踪。'
          : '最近迁移整体结果较干净，如需复盘也可以按已变化批次继续追踪。',
        badge: unchangedCount ? '异常可追踪' : '结果可复盘',
        tagType: unchangedCount ? 'warning' : 'success',
        items: [
          {
            label: '异常未变化',
            value: `${unchangedCount} 件`,
            desc: unchangedCount ? `约占整批 ${unchangedRate}% ，建议优先回查这些商品。` : '当前没有未变化异常对象。',
            tone: unchangedCount ? 'warning' : 'neutral'
          },
          {
            label: '已产生变化',
            value: `${changedCount} 件`,
            desc: changedCount ? '这些商品已经完成归属变化，可按目标继续做结果复核。' : '当前没有已变化商品可追踪。',
            tone: changedCount ? 'success' : 'neutral'
          },
          {
            label: '整批体量',
            value: `${totalCount} 件`,
            desc: totalCount >= 20 ? '批次较大，建议拆成异常批次和已变化批次分别跟踪。' : '批次体量适中，可直接在当前页完成复盘。',
            tone: totalCount >= 20 ? 'primary' : 'neutral'
          }
        ],
        alerts,
        sources,
        canTrackUnchanged: unchangedCount > 0,
        canTrackChanged: changedCount > 0,
        canCopyUnchanged: unchangedCount > 0,
        canCopyChanged: changedCount > 0
      }
    },
    latestTransferExceptionActiveSource() {
      const tracker = this.latestTransferExceptionTracker
      if (!tracker.active || !tracker.sources.length) {
        return {
          key: '',
          count: 0,
          title: '',
          desc: '',
          ids: []
        }
      }
      const preferred = tracker.sources.find((item) => item.key === this.latestTransferExceptionFilter && item.count > 0)
      const fallback = tracker.sources.find((item) => item.count > 0) || tracker.sources[0]
      return fallback && preferred ? preferred : fallback
    },
    transferRecentComparison() {
      if (!this.isTransferDialog || !this.latestTransferOperation || !this.transferTargetIds.length) {
        return {
          active: false,
          title: '',
          desc: '',
          badge: '',
          tagType: 'info',
          tags: [],
          overlapIds: [],
          deltaIds: [],
          warning: false
        }
      }
      const currentIds = [...new Set(this.transferTargetIds.map((item) => Number(item)).filter((item) => item > 0))]
      const latestIds = Array.isArray(this.latestTransferOperation.ids)
        ? [...new Set(this.latestTransferOperation.ids.map((item) => Number(item)).filter((item) => item > 0))]
        : []
      if (!latestIds.length) {
        return {
          active: false,
          title: '',
          desc: '',
          badge: '',
          tagType: 'info',
          tags: [],
          overlapIds: [],
          deltaIds: [],
          warning: false
        }
      }
      const latestSet = new Set(latestIds)
      const overlapIds = currentIds.filter((id) => latestSet.has(id))
      const deltaIds = currentIds.filter((id) => !latestSet.has(id))
      const sameTarget = this.selectType === 'transfer_platform'
        ? Number(this.latestTransferOperation.merchant_id) === 0
        : Number(this.latestTransferOperation.merchant_id) === Number(this.target_merchant_id || 0)
      const tags = [
        `当前批次 ${currentIds.length} 件`,
        `最近迁移 ${latestIds.length} 件`
      ]
      if (overlapIds.length) tags.push(`重叠 ${overlapIds.length} 件`)
      if (deltaIds.length) tags.push(`新增 ${deltaIds.length} 件`)
      tags.push(sameTarget ? '迁移目标一致' : '迁移目标不同')
      if (this.latestTransferOperation.target) {
        tags.push(`最近目标：${this.latestTransferOperation.target}`)
      }
      if (overlapIds.length === currentIds.length) {
        return {
          active: true,
          title: sameTarget ? '当前批次与最近迁移完全重叠' : '当前批次与最近迁移完全重叠，但目标已变化',
          desc: sameTarget
            ? '这次提交基本是在重复处理上一批商品，建议先确认是否真的需要再次迁移。'
            : '商品还是上一批，但这次迁移目标不同，提交前建议重点复核目标商家和归属变化。',
          badge: sameTarget ? '高度重复' : '目标变化',
          tagType: sameTarget ? 'warning' : 'danger',
          tags,
          overlapIds,
          deltaIds,
          warning: true
        }
      }
      if (overlapIds.length > 0) {
        return {
          active: true,
          title: `当前批次与最近迁移重叠 ${overlapIds.length} 件`,
          desc: sameTarget
            ? '这次是在上一批基础上补迁一部分新商品，建议优先关注新增商品是否完整。'
            : '这次有部分旧商品会被再次处理，而且迁移目标与最近批次不同，提交前建议重点复核重叠商品。',
          badge: sameTarget ? '续接迁移' : '交叉迁移',
          tagType: sameTarget ? 'primary' : 'warning',
          tags,
          overlapIds,
          deltaIds,
          warning: !sameTarget
        }
      }
      return {
        active: true,
        title: '当前批次与最近迁移没有重叠',
        desc: sameTarget
          ? '这是一批全新的同目标迁移对象，可以继续提交。'
          : '这是一批全新的迁移对象，但目标与最近批次不同，建议再确认目标选择是否正确。',
        badge: sameTarget ? '全新批次' : '新目标批次',
        tagType: sameTarget ? 'success' : 'primary',
        tags,
        overlapIds: [],
        deltaIds,
        warning: false
      }
    },
    transferRecentFollowUp() {
      const operation = this.latestTransferOperation
      if (!this.isTransferDialog || !operation) {
        return {
          active: false,
          title: '',
          desc: '',
          badge: '',
          tagType: 'info',
          tags: [],
          metrics: [],
          alerts: [],
          alertType: 'info',
          canFilterTarget: false,
          canLoadCurrentTarget: false,
          canLoadUnchanged: false,
          canLoadChanged: false,
          canLoadPendingOverlap: false,
          canCopyCurrentTarget: false
        }
      }
      const recentIds = Array.isArray(operation.ids)
        ? [...new Set(operation.ids.map((item) => Number(item)).filter((item) => item > 0))]
        : []
      const changedCount = Number(operation.diff?.changedCount || 0)
      const unchangedCount = Number(operation.diff?.unchangedCount || 0)
      const currentTargetIds = [...new Set(this.transferTargetIds.map((item) => Number(item)).filter((item) => item > 0))]
      const recentIdSet = new Set(recentIds)
      const unchangedIdSet = new Set(
        Array.isArray(operation.diff?.unchangedIds)
          ? operation.diff.unchangedIds.map((item) => Number(item)).filter((item) => item > 0)
          : []
      )
      const overlapWithCurrentIds = currentTargetIds.filter((id) => recentIdSet.has(id))
      const pendingOverlapIds = overlapWithCurrentIds.filter((id) => unchangedIdSet.has(id))
      const sameTarget = this.selectType === 'transfer_platform'
        ? Number(operation.merchant_id) === 0
        : Number(operation.merchant_id) === Number(this.target_merchant_id || 0)
      const canFilterTarget = operation.merchant_id === 0 || Number(operation.merchant_id) > 0
      const canLoadCurrentTarget = recentIds.length > 0
      const canLoadUnchanged = Array.isArray(operation.diff?.unchangedIds) && operation.diff.unchangedIds.some((item) => Number(item) > 0)
      const canLoadChanged = Array.isArray(operation.diff?.changedIds) && operation.diff.changedIds.some((item) => Number(item) > 0)
      const canLoadPendingOverlap = pendingOverlapIds.length > 0
      const canCopyCurrentTarget = currentTargetIds.length > 0
      const alerts = []
      const tags = [
        `最近批次 ${recentIds.length} 件`,
        sameTarget ? '当前目标与最近迁移一致' : '当前目标与最近迁移不同'
      ]
      const metrics = [
        {
          label: '最近整批',
          value: `${recentIds.length} 件`,
          desc: operation.target ? `最近迁移目标：${operation.target}` : '最近一次迁移批次',
          tone: 'info'
        },
        {
          label: '已变化',
          value: `${changedCount} 件`,
          desc: changedCount ? '这些商品已产生实际归属变化，可继续增量处理' : '当前没有已变化商品',
          tone: changedCount ? 'success' : 'neutral'
        },
        {
          label: '待复核',
          value: `${unchangedCount} 件`,
          desc: unchangedCount ? '这些商品仍停留在原归属，建议优先复核' : '最近批次没有待复核商品',
          tone: unchangedCount ? 'warning' : 'neutral'
        },
        {
          label: '当前重叠',
          value: `${overlapWithCurrentIds.length} 件`,
          desc: pendingOverlapIds.length
            ? `其中 ${pendingOverlapIds.length} 件仍待复核，可一键保留`
            : (overlapWithCurrentIds.length ? '当前批次与最近迁移有重叠，可继续核对' : '当前批次与最近迁移暂无重叠'),
          tone: pendingOverlapIds.length ? 'danger' : (overlapWithCurrentIds.length ? 'primary' : 'neutral')
        }
      ]
      if (operation.target) tags.push(`最近目标：${operation.target}`)
      if (changedCount) tags.push(`已变化 ${changedCount} 件`)
      if (unchangedCount) tags.push(`待复核 ${unchangedCount} 件`)
      if (overlapWithCurrentIds.length) tags.push(`当前重叠 ${overlapWithCurrentIds.length} 件`)
      if (operation.querySnapshotLabel) tags.push(`迁移前筛选：${operation.querySnapshotLabel}`)
      if (unchangedCount) alerts.push(`有 ${unchangedCount} 件商品未发生归属变化，建议优先复核`)
      if (!sameTarget) alerts.push('当前目标与最近迁移不同，提交前建议再次确认目标')
      if (!unchangedCount && changedCount) alerts.push('最近批次已形成实际归属变化，可优先继续增量处理')
      if (pendingOverlapIds.length) alerts.push(`当前批次有 ${pendingOverlapIds.length} 件和最近迁移重叠且仍待复核，建议先缩小范围逐步处理`)
      return {
        active: true,
        title: sameTarget ? '最近迁移后续动作已就绪' : '最近迁移目标不同，建议先复核',
        desc: sameTarget
          ? '如果你正在续接同一目标的迁移，可以直接承接最近整批，或先按最近目标筛选确认迁移结果。'
          : '当前准备迁移的目标和最近批次不同，建议先按最近目标回看结果，再决定是否复用上一批继续处理。',
        badge: sameTarget ? '续接建议' : '复核建议',
        tagType: sameTarget ? 'success' : 'warning',
        tags,
        metrics,
        alerts,
        alertType: unchangedCount || !sameTarget ? 'warning' : 'success',
        canFilterTarget,
        canLoadCurrentTarget,
        canLoadUnchanged,
        canLoadChanged,
        canLoadPendingOverlap,
        canCopyCurrentTarget
      }
    },
    bridgeTargetLibrary() {
      const items = []
      const scoreMap = new Map()
      const addTarget = (target) => {
        if (!target?.key) return
        const current = scoreMap.get(target.key)
        if (current) {
          current.score += Number(target.score || 0)
          current.meta = target.meta || current.meta
          current.active = current.active || target.active
          return
        }
        const entry = {
          ...target,
          score: Number(target.score || 0)
        }
        scoreMap.set(target.key, entry)
        items.push(entry)
      }
      addTarget({
        key: 'platform',
        type: 'platform',
        title: '平台自营',
        meta: this.bridgeMode === 'platform' ? '当前正在使用' : '适合统一归口平台',
        active: this.bridgeMode === 'platform',
        score: this.bridgeMode === 'platform' ? 10 : 1
      })
      if (this.currentFilterMerchantTarget) {
        addTarget({
          key: `merchant-${this.currentFilterMerchantTarget.id}`,
          type: 'merchant',
          id: this.currentFilterMerchantTarget.id,
          title: this.currentFilterMerchantTarget.title,
          meta: '当前筛选商家',
          active: Number(this.target_merchant_id) === Number(this.currentFilterMerchantTarget.id),
          score: 12
        })
      }
      this.transferMerchantQuickTargets.forEach((item, index) => {
        addTarget({
          key: `merchant-${item.id}`,
          type: 'merchant',
          id: item.id,
          title: item.title,
          meta: index === 0 ? '常用迁移目标' : '历史常用目标',
          active: Number(this.target_merchant_id) === Number(item.id),
          score: 8 - index
        })
      })
      this.operationHistory.forEach((item, index) => {
        if (this.resolveOperationHistoryType(item) !== 'transfer') return
        const isPlatform = Number(item.merchant_id) === 0
        addTarget({
          key: isPlatform ? 'platform' : `merchant-${item.merchant_id}`,
          type: isPlatform ? 'platform' : 'merchant',
          id: isPlatform ? undefined : Number(item.merchant_id),
          title: isPlatform ? '平台自营' : (item.target || this.resolveMerchantTitle(item.merchant_id)),
          meta: index === 0 ? '最近迁移目标' : '历史迁移目标',
          active: isPlatform ? this.bridgeMode === 'platform' : Number(this.target_merchant_id) === Number(item.merchant_id),
          score: 6 - index
        })
      })
      return items
        .sort((a, b) => b.score - a.score)
        .slice(0, 5)
    },
    bridgeTargetLibraryTitle() {
      if (this.bridgeMode === 'merchant' && this.targetMerchantLabel) {
        return `当前主目标：${this.targetMerchantLabel}`
      }
      if (this.bridgeMode === 'platform') {
        return '当前主目标：平台自营'
      }
      return '选择一个高频迁移目标'
    },
    bridgeTargetLibraryDesc() {
      if (!this.bridgeTargetLibrary.length) {
        return '常用目标会根据当前筛选、迁移偏好和最近操作自动沉淀。'
      }
      return '这里会把当前筛选商家、已记忆目标和最近迁移目标汇总成快捷入口，方便快速切换。'
    },
    canApplyCurrentBridgeTargetFilter() {
      if (!this.stagedBridgeSummary.active) return false
      if (this.bridgeMode === 'platform') return true
      return Number(this.target_merchant_id) > 0
    },
    selectionOwnershipSummary() {
      const platformCount = this.selection.filter((item) => Number(item.merchant_id) === 0).length
      const merchantCount = this.selection.filter((item) => Number(item.merchant_id) > 0).length
      if (!this.selection.length) {
        return {
          label: '未勾选商品',
          desc: '勾选后可查看当前批量对象来自平台还是商家。'
        }
      }
      return {
        label: `平台 ${platformCount} 件 / 商家 ${merchantCount} 件`,
        desc: merchantCount ? '包含商家归属商品，迁移前请确认目标是否正确。' : '当前勾选商品均为平台自营。'
      }
    },
    selectionPendingReviewCount() {
      return this.selection.filter((item) => Number(item.status) === 0).length
    },
    selectionOfflineCount() {
      return this.selection.filter((item) => Number(item.is_disable) === 1).length
    },
    selectionTargetAlignedCount() {
      if (!this.selection.length) return 0
      if (this.bridgeMode === 'merchant' && Number(this.target_merchant_id) > 0) {
        return this.selection.filter((item) => Number(item.merchant_id) === Number(this.target_merchant_id)).length
      }
      if (this.bridgeMode === 'platform') {
        return this.selection.filter((item) => Number(item.merchant_id) === 0).length
      }
      return 0
    },
    selectionTargetChangedCount() {
      return Math.max(this.selection.length - this.selectionTargetAlignedCount, 0)
    },
    selectionReviewPanel() {
      if (!this.selection.length) {
        return {
          active: false,
          badge: '等待勾选',
          tagType: 'info',
          title: '先勾选一批商品，再集中复核',
          desc: '勾选后这里会汇总归属、审核、上下架和与当前迁移目标的差异，方便提交前最后确认。'
        }
      }
      if (this.bridgeMode === 'merchant' && !this.targetMerchantLabel) {
        return {
          active: true,
          badge: '待选目标',
          tagType: 'warning',
          title: `当前已勾选 ${this.selection.length} 件商品，先确认目标商家`,
          desc: '你已经有一批待处理商品，但转商家模式还没有锁定目标，建议先选商家再继续迁移或复核。'
        }
      }
      if (this.selectionTargetChangedCount > 0) {
        return {
          active: true,
          badge: '建议复核',
          tagType: 'warning',
          title: `这批勾选里有 ${this.selectionTargetChangedCount} 件商品会发生归属变化`,
          desc: this.bridgeMode === 'merchant'
            ? `提交后会迁移到 ${this.targetMerchantLabel}，建议先看待审核和下架商品，再决定是否整批提交。`
            : '提交后会统一归口到平台自营，建议优先处理待审核或下架商品，减少迁移后再返工。'
        }
      }
      return {
        active: true,
        badge: '已对齐',
        tagType: 'success',
        title: '当前勾选已基本对齐到目标方向',
        desc: '这批商品大多已经处于当前迁移目标，若只是做复核或补充调整，可以继续用快捷动作收尾。'
      }
    },
    selectionReviewMetrics() {
      const selectedCount = this.selection.length
      const platformCount = this.selection.filter((item) => Number(item.merchant_id) === 0).length
      const merchantCount = selectedCount - platformCount
      const approvedCount = this.selection.filter((item) => Number(item.status) === 1).length
      const rejectedCount = this.selection.filter((item) => Number(item.status) === 2).length
      return [
        {
          label: '归属结构',
          value: selectedCount ? `平台 ${platformCount} / 商家 ${merchantCount}` : '未勾选商品',
          desc: selectedCount ? '帮助判断这批商品当前主要来自哪一侧。' : '勾选后可查看归属分布。'
        },
        {
          label: '审核状态',
          value: selectedCount ? `待审 ${this.selectionPendingReviewCount} / 已审 ${approvedCount + rejectedCount}` : '暂无数据',
          desc: this.selectionPendingReviewCount ? '建议先处理待审核商品，减少迁移后重复回查。' : '当前勾选商品都已经过审核。'
        },
        {
          label: '上下架状态',
          value: selectedCount ? `上架 ${selectedCount - this.selectionOfflineCount} / 下架 ${this.selectionOfflineCount}` : '暂无数据',
          desc: this.selectionOfflineCount ? '下架商品较多时，建议先确认是否需要统一上架。' : '当前勾选商品都处于上架状态。'
        },
        {
          label: '目标对齐',
          value: selectedCount
            ? (this.bridgeMode === 'merchant' && !this.targetMerchantLabel
              ? '待选择商家目标'
              : `待迁移 ${this.selectionTargetChangedCount} / 已对齐 ${this.selectionTargetAlignedCount}`)
            : '暂无数据',
          desc: this.bridgeMode === 'merchant' && !this.targetMerchantLabel
            ? '先选目标商家后，才能准确判断这批商品的迁移差异。'
            : '这里会显示当前勾选与迁移目标之间的差异量。'
        }
      ]
    },
    selectionReviewWarnings() {
      const warnings = []
      if (!this.selection.length) return warnings
      if (this.selectionPendingReviewCount) {
        warnings.push(`当前勾选里还有 ${this.selectionPendingReviewCount} 件待审核商品，建议优先审核后再迁移。`)
      }
      if (this.selectionOfflineCount) {
        warnings.push(`当前勾选里有 ${this.selectionOfflineCount} 件下架商品，运营前请确认是否需要统一上架。`)
      }
      if (this.bridgeMode === 'merchant' && !this.targetMerchantLabel) {
        warnings.push('当前是转商家模式，但还没有选定目标商家。')
      } else if (this.selectionTargetAlignedCount && this.selectionTargetChangedCount) {
        warnings.push(`这批商品里有 ${this.selectionTargetAlignedCount} 件已经处于当前目标，提交前建议确认是否需要重复迁移。`)
      }
      return warnings
    },
    selectionReviewFocus() {
      if (!this.selection.length) {
        return {
          active: false,
          title: '',
          desc: '',
          badge: '',
          tagType: 'info',
          items: []
        }
      }
      const items = [
        {
          label: '优先审核',
          value: `${this.selectionPendingReviewCount} 件`,
          desc: this.selectionPendingReviewCount
            ? '待审核商品建议先处理，减少迁移后还要回头补审。'
            : '当前勾选里没有待审核商品，可直接做下一步。',
          tone: this.selectionPendingReviewCount ? 'warning' : 'neutral'
        },
        {
          label: '优先上架',
          value: `${this.selectionOfflineCount} 件`,
          desc: this.selectionOfflineCount
            ? '下架商品较多，建议确认是否需要统一上架后再运营。'
            : '当前勾选里没有下架商品。',
          tone: this.selectionOfflineCount ? 'primary' : 'neutral'
        },
        {
          label: '实际迁移',
          value: `${this.selectionTargetChangedCount} 件`,
          desc: this.selectionTargetChangedCount
            ? '这些商品提交后会发生真实归属变化，建议重点抽查。'
            : '当前勾选基本已对齐到目标，更适合做复核收尾。',
          tone: this.selectionTargetChangedCount ? 'success' : 'neutral'
        },
        {
          label: '重复目标',
          value: `${this.selectionTargetAlignedCount} 件`,
          desc: this.selectionTargetAlignedCount
            ? '这些商品已处于当前目标，提交前建议确认是否真的要重复迁移。'
            : '当前没有已对齐商品，不容易出现重复迁移。',
          tone: this.selectionTargetAlignedCount ? 'danger' : 'neutral'
        }
      ]
      if (this.bridgeMode === 'merchant' && !this.targetMerchantLabel) {
        return {
          active: true,
          title: '这批勾选还差一个目标商家确认动作',
          desc: '先把目标商家锁定，再决定是直接承接整批，还是先审核/上架后再提交流转。',
          badge: '先锁目标',
          tagType: 'warning',
          items
        }
      }
      if (this.selectionPendingReviewCount || this.selectionOfflineCount) {
        return {
          active: true,
          title: '这批商品建议先处理状态类风险',
          desc: '审核和上下架问题会影响后续运营判断，建议先把明显风险清掉，再进入迁移提交。',
          badge: '先复核状态',
          tagType: 'warning',
          items
        }
      }
      if (this.selectionTargetAlignedCount && this.selectionTargetChangedCount) {
        return {
          active: true,
          title: '这批商品同时包含新增迁移和重复目标对象',
          desc: '建议重点看“重复目标”那部分，必要时先缩小到真正会变化的商品再提交。',
          badge: '混合批次',
          tagType: 'primary',
          items
        }
      }
      return {
        active: true,
        title: '这批勾选已经接近可直接运营提交',
        desc: '状态风险较低，可以直接承接到工具，或按当前目标快速复核后提交。',
        badge: '可继续',
        tagType: 'success',
        items
      }
    },
    selectionLatestOperationLink() {
      const operation = this.latestOperation
      const selectionIds = this.selectGetIds(this.selection).map((item) => Number(item)).filter((item) => item > 0)
      const operationIds = Array.isArray(operation?.ids) ? operation.ids.map((item) => Number(item)).filter((item) => item > 0) : []
      if (!selectionIds.length || !operation || !operationIds.length) {
        return {
          active: false,
          title: '',
          desc: '',
          tags: [],
          overlapIds: [],
          pendingOverlapIds: [],
          canStageOverlap: false
        }
      }
      const overlapIds = selectionIds.filter((id) => operationIds.includes(id))
      const overlapCount = overlapIds.length
      const pendingSet = new Set(
        Array.isArray(operation?.diff?.unchangedIds)
          ? operation.diff.unchangedIds.map((item) => Number(item)).filter((item) => item > 0)
          : []
      )
      const pendingOverlapIds = overlapIds.filter((id) => pendingSet.has(id))
      const tags = [
        `当前勾选 ${selectionIds.length} 件`,
        `最近操作 ${operationIds.length} 件`
      ]
      if (operation.target) {
        tags.push(`最近目标：${operation.target}`)
      }
      if (overlapCount) {
        tags.push(`重叠 ${overlapCount} 件`)
      }
      if (pendingOverlapIds.length) {
        tags.push(`待复核重叠 ${pendingOverlapIds.length} 件`)
      }
      if (operation.time) {
        tags.push(operation.time)
      }
      if (overlapCount === selectionIds.length) {
        return {
          active: true,
          title: '当前勾选与最近操作完全重叠',
          desc: pendingOverlapIds.length
            ? '这批商品就是你刚刚处理过的对象，其中还有待复核重叠项，适合直接承接到迁移工具继续处理。'
            : '这批商品就是你刚刚处理过的对象，适合直接复用最近筛选或继续围绕该批次做复核。',
          tags,
          overlapIds,
          pendingOverlapIds,
          canStageOverlap: this.canStageOperationTransfer(operation)
        }
      }
      if (overlapCount > 0) {
        return {
          active: true,
          title: `当前勾选与最近操作有 ${overlapCount} 件重叠`,
          desc: pendingOverlapIds.length
            ? '说明你正在继续处理上一批里的部分商品，其中还有待复核重叠项，可直接缩小范围后继续迁移。'
            : '说明你正在继续处理上一批里的部分商品，可以直接复制重叠 ID、承接到工具或恢复最近筛选做对照复核。',
          tags,
          overlapIds,
          pendingOverlapIds,
          canStageOverlap: this.canStageOperationTransfer(operation)
        }
      }
      return {
        active: true,
        title: '当前勾选与最近操作不是同一批商品',
        desc: '这批商品与最近一次运营对象没有重叠，适合按当前目标重新承接，避免误以为还在处理上一批。',
        tags,
        overlapIds: [],
        pendingOverlapIds: [],
        canStageOverlap: false
      }
    },
    transferSelectionSummary() {
      const list = this.transferTargetRows
      if (this.transferScope === 'manual_ids') {
        if (!this.transferManualIds.length) {
          return {
            label: '暂无待处理商品',
            desc: '请输入商品 ID，多个 ID 可用逗号、空格或换行分隔。'
          }
        }
        const knownCount = list.length
        const pendingCount = Math.max(this.transferManualIds.length - knownCount, 0)
        return {
          label: `手动指定 ${this.transferManualIds.length} 件`,
          desc: pendingCount
            ? `已匹配当前页 ${knownCount} 件，另有 ${pendingCount} 个 ID 需要提交前补查。`
            : '当前输入的商品 ID 已全部完成预匹配。'
        }
      }
      const platformCount = list.filter((item) => Number(item.merchant_id) === 0).length
      const merchantCount = list.filter((item) => Number(item.merchant_id) > 0).length
      if (!list.length) {
        return {
          label: '暂无待处理商品',
          desc: '请先勾选商品，或使用当前页处理模式。'
        }
      }
      return {
        label: `平台 ${platformCount} 件 / 商家 ${merchantCount} 件`,
        desc: this.transferScope === 'current_page'
          ? '将按当前筛选页的结果整体执行。'
          : '将按当前勾选结果执行精确迁移。'
      }
    },
    batchSelectionSummary() {
      const list = this.batchTargetRows
      const onlineCount = list.filter((item) => Number(item.is_disable) === 0).length
      const offlineCount = Math.max(list.length - onlineCount, 0)
      if (!list.length) {
        return {
          label: '暂无待处理商品',
          desc: '请先勾选商品，或使用当前页处理模式。'
        }
      }
      return {
        label: `上架 ${onlineCount} 件 / 下架 ${offlineCount} 件`,
        desc: this.batchScope === 'current_page'
          ? '将按当前筛选页的结果整体执行。'
          : '将按当前勾选结果执行精确处理。'
      }
    },
    bridgeTargetPreview() {
      if (!this.selection.length) {
        return {
          title: '等待选择商品',
          desc: '先勾选商品后，再确认迁移后的归属结果。'
        }
      }
      if (this.bridgeMode === 'merchant') {
        if (!this.targetMerchantLabel) {
          return {
            title: '待选择目标商家',
            desc: '选择商家后，会把已勾选商品统一迁移到该商家。'
          }
        }
        return {
          title: `统一归属到 ${this.targetMerchantLabel}`,
          desc: `提交后预计 ${this.selection.length} 件商品归属将切换到目标商家。`
        }
      }
      return {
        title: '统一归属到平台自营',
        desc: `提交后预计 ${this.selection.length} 件商品归属将切换为平台自营。`
      }
    },
    bridgeRiskNotes() {
      const notes = []
      if (!this.selection.length) {
        notes.push('当前未勾选商品，不会触发批量迁移。')
        notes.push('批量迁移只变更商品归属，不会修改商品标题、价格和库存。')
        return notes
      }
      if (this.bridgeMode === 'merchant' && !this.target_merchant_id) {
        notes.push('当前是转商家模式，请先明确目标商家后再提交。')
      }
      const platformCount = this.selection.filter((item) => Number(item.merchant_id) === 0).length
      if (platformCount && this.bridgeMode === 'platform') {
        notes.push(`当前勾选里已有 ${platformCount} 件平台商品，提交后会统一再次归口到平台。`)
      }
      const sameTargetCount =
        this.bridgeMode === 'merchant' && this.target_merchant_id
          ? this.selection.filter((item) => Number(item.merchant_id) === Number(this.target_merchant_id)).length
          : 0
      if (sameTargetCount) {
        notes.push(`已有 ${sameTargetCount} 件商品本来就属于该商家，提交前建议再核对一次。`)
      }
      if (this.transferScope === 'manual_ids' && this.transferManualIds.length) {
        if (this.transferManualPendingIds.length) {
          notes.push(`当前承接批次还有 ${this.transferManualPendingIds.length} 个商品 ID 未在列表中识别，建议先复核后再提交。`)
        } else {
          notes.push('当前承接批次已全部识别完成，可以直接继续迁移。')
        }
      }
      notes.push('提交后列表会自动刷新，建议根据回显结果继续复核归属是否准确。')
      return notes
    },
    permissionCards() {
      return [
        {
          key: 'transfer-platform',
          label: '迁移到平台',
          enabled: this.checkPermission(['admin/goods.Goods/transferToPlatform']),
          desc: this.checkPermission(['admin/goods.Goods/transferToPlatform']) ? '当前账号可执行平台归属迁移。' : '当前账号没有平台迁移权限。'
        },
        {
          key: 'transfer-merchant',
          label: '迁移到商家',
          enabled: this.checkPermission(['admin/goods.Goods/transferToMerchant']),
          desc: this.checkPermission(['admin/goods.Goods/transferToMerchant']) ? '当前账号可执行商家归属迁移。' : '当前账号没有商家迁移权限。'
        }
      ]
    },
    transferPreviewSummary() {
      const rows = this.transferTargetRows
      const platformCount = rows.filter((item) => Number(item.merchant_id) === 0).length
      const merchantCount = rows.length - platformCount
      if (this.transferScope === 'manual_ids') {
        return {
          platform: merchantCount
            ? `已识别 ${merchantCount} 件商家商品会切到平台，其余 ID 会在提交时自动核对。`
            : `将按输入的 ${this.transferTargetIds.length} 个商品 ID 执行平台归属迁移。`,
          merchant: this.targetMerchantLabel
            ? `将按输入的 ${this.transferTargetIds.length} 个商品 ID 统一迁移到 ${this.targetMerchantLabel}。`
          : '选择目标商家后，会按输入的商品 ID 生成迁移预判。'
        }
      }
      const sameTargetCount =
        this.target_merchant_id
          ? rows.filter((item) => Number(item.merchant_id) === Number(this.target_merchant_id)).length
          : 0
      return {
        platform: merchantCount ? `预计有 ${merchantCount} 件商品从商家归属切到平台。` : '当前处理对象本身已全部属于平台。',
        merchant: this.targetMerchantLabel
          ? sameTargetCount
            ? `预计 ${Math.max(this.transferTargetRows.length - sameTargetCount, 0)} 件发生迁移，${sameTargetCount} 件保持原归属。`
            : `预计 ${this.transferTargetRows.length} 件商品统一迁移到 ${this.targetMerchantLabel}。`
          : '选择目标商家后，会自动给出本次迁移变化预判。'
      }
    },
    batchActionPreview() {
      return {
        disable: Number(this.is_disable) === 0
          ? `预计把 ${this.batchTargetRows.length} 件商品统一切到上架状态。`
          : `预计把 ${this.batchTargetRows.length} 件商品统一切到下架状态。`,
        thumbnail: this.batch_image_id
          ? `预计为 ${this.batchTargetRows.length} 件商品替换为同一张缩略图。`
          : '选择目标缩略图后，会统一更新本次处理对象。',
        label: this.batch_goods_label_id.length
          ? `预计为 ${this.batchTargetRows.length} 件商品统一保留所选标签。`
          : `预计清空 ${this.batchTargetRows.length} 件商品的标签。`
      }
    },
    selectDialogSummary() {
      const targetCount = (() => {
        if (this.batchScopedSelectTypes.includes(this.selectType)) return this.batchTargetIds.length
        if (this.selectType === 'transfer_platform' || this.selectType === 'transfer_merchant') return this.transferTargetIds.length
        return this.selection.length
      })()
      const scopeLabel = (() => {
        if (this.batchScopedSelectTypes.includes(this.selectType)) {
          return this.batchScope === 'current_page' ? '当前页' : '已勾选'
        }
        if (this.selectType === 'transfer_platform' || this.selectType === 'transfer_merchant') {
          if (this.transferScope === 'manual_ids') return '手动指定'
          return this.transferScope === 'current_page' ? '当前页' : '已勾选'
        }
        return '已勾选'
      })()
      if (!this.selectType) {
        return { label: '', desc: '', tip: '' }
      }
      const summaryMap = {
        disable: {
          label: `将处理 ${targetCount} 件商品`,
          desc: `范围：${scopeLabel} · ${Number(this.is_disable) === 0 ? '统一上架' : '统一下架'}`,
          tip: targetCount ? '提交后会立即刷新列表并记录本次操作回显。' : ''
        },
        auth: {
          label: `将处理 ${this.selection.length} 件商品`,
          desc: `范围：已勾选 · ${this.goods_status === 2 ? '审核拒绝' : '审核通过'}`,
          tip: this.selection.length ? '审核结果会同步写入最近操作记录。' : ''
        },
        dele: {
          label: `将删除 ${this.selection.length} 件商品`,
          desc: '范围：已勾选 · 删除后不可在当前页直接恢复',
          tip: this.selection.length ? '建议再次确认当前筛选条件和勾选结果。' : ''
        },
        batch_label: {
          label: `将处理 ${targetCount} 件商品`,
          desc: `范围：${scopeLabel} · ${this.batch_goods_label_id.length ? `更新为 ${this.batch_goods_label_id.length} 个标签` : '清空标签'}`,
          tip: targetCount ? '提交后会按当前弹窗展示的范围整体更新。' : ''
        },
        batch_thumbnail: {
          label: `将处理 ${targetCount} 件商品`,
          desc: `范围：${scopeLabel} · ${this.batch_image_id ? '统一替换缩略图' : '待选择目标缩略图'}`,
          tip: targetCount ? '缩略图更新后会同步写入最近操作记录。' : ''
        },
        transfer_platform: {
          label: `将迁移 ${targetCount} 件商品`,
          desc: `范围：${scopeLabel} · 目标：平台自营`,
          tip: targetCount ? '若商品当前已属于平台自营，会在预判中显示为保持原归属。' : ''
        },
        transfer_merchant: {
          label: `将迁移 ${targetCount} 件商品`,
          desc: `范围：${scopeLabel} · 目标：${this.targetMerchantLabel || '待选择目标商家'}`,
          tip: targetCount ? '提交后会记录迁移差异和可回退信息。' : ''
        }
      }
      return summaryMap[this.selectType] || { label: '', desc: '', tip: '' }
    },
    isTransferDialog() {
      return this.selectType === 'transfer_platform' || this.selectType === 'transfer_merchant'
    },
    transferSubmitChecklist() {
      if (!this.isTransferDialog) return []
      return [
        {
          label: this.transferTargetIds.length
            ? `已锁定 ${this.transferTargetIds.length} 件待迁移商品`
            : '请先选择迁移对象或输入商品 ID',
          done: this.transferTargetIds.length > 0
        },
        {
          label: this.transferScope === 'manual_ids'
            ? `手动 ID 已识别 ${this.transferManualMatchedIds.length} 件`
            : `当前处理范围：${this.transferScopeDescription}`,
          done: this.transferScope === 'manual_ids' ? this.transferManualMatchedIds.length > 0 : this.transferTargetIds.length > 0
        },
        {
          label: this.selectType === 'transfer_merchant'
            ? (this.targetMerchantLabel ? `目标商家已选定为 ${this.targetMerchantLabel}` : '当前尚未选择目标商家')
            : '当前目标为平台自营',
          done: this.selectType === 'transfer_platform' || Boolean(this.targetMerchantLabel)
        },
        {
          label: this.transferManualPendingIds.length
            ? `还有 ${this.transferManualPendingIds.length} 个 ID 需提交时二次补查`
            : '当前没有待补查的商品 ID',
          done: this.transferManualPendingIds.length === 0
        }
      ]
    },
    transferSubmitWarnings() {
      if (!this.isTransferDialog) return []
      const warnings = []
      if (!this.transferTargetIds.length) {
        warnings.push('当前还没有形成可提交的迁移对象，提交按钮会保持禁用。')
      }
      if (this.selectType === 'transfer_merchant' && !this.targetMerchantLabel) {
        warnings.push('转商家前必须先选择目标商家。')
      }
      if (this.transferManualPendingIds.length) {
        warnings.push(`有 ${this.transferManualPendingIds.length} 个商品 ID 当前页未识别，提交时会再次校验，若仍未找到会阻止提交。`)
      }
      if (this.transferManualMeta.invalidTokens.length) {
        warnings.push(`已自动忽略 ${this.transferManualMeta.invalidTokens.length} 项无效输入，建议确认商品 ID 是否完整。`)
      }
      if (this.transferManualChangeSummary.unchanged > 0) {
        warnings.push(`预计有 ${this.transferManualChangeSummary.unchanged} 件商品会保持原归属，提交前建议确认是否属于重复迁移。`)
      }
      if (this.transferManualChangeSummary.changed >= 50) {
        warnings.push(`本次预计迁移 ${this.transferManualChangeSummary.changed} 件商品，建议先回退筛选再做一轮复核。`)
      }
      return warnings
    },
    transferRiskBoard() {
      if (!this.isTransferDialog) {
        return {
          active: false,
          title: '',
          desc: '',
          badge: '',
          tagType: 'info',
          items: [],
          alerts: [],
          alertType: 'info',
          canCopyCurrentTarget: false,
          canFilterCurrentTarget: false,
          canRestoreLatestFilter: false,
          canRestoreSnapshot: false
        }
      }
      const pendingCount = this.transferManualPendingIds.length
      const invalidCount = this.transferManualMeta.invalidTokens.length
      const unchangedCount = this.transferManualChangeSummary.unchanged
      const changedCount = this.transferManualChangeSummary.changed
      const totalCount = this.transferTargetIds.length
      const sameTargetRate = totalCount ? Math.round((unchangedCount / totalCount) * 100) : 0
      const items = [
        {
          label: '待补查',
          value: `${pendingCount} 件`,
          desc: pendingCount ? '这些 ID 需要提交时二次补查，越多越建议先缩范围。' : '当前没有待补查商品 ID。',
          tone: pendingCount ? 'warning' : 'neutral'
        },
        {
          label: '预计变化',
          value: `${changedCount} 件`,
          desc: changedCount ? '这些商品预计会发生真实归属变化，是本轮迁移的核心对象。' : '当前还没有形成会变化的迁移对象。',
          tone: changedCount ? 'success' : 'neutral'
        },
        {
          label: '重复目标',
          value: `${unchangedCount} 件`,
          desc: unchangedCount ? `约占当前批次 ${sameTargetRate}% ，建议确认是否属于重复迁移。` : '当前没有预计保持原归属的商品。',
          tone: unchangedCount ? 'danger' : 'neutral'
        },
        {
          label: '批次体量',
          value: `${totalCount} 件`,
          desc: totalCount >= 50 ? '批次较大，建议先回退筛选或复制复核 ID 再提交。' : '批次规模适中，适合当前窗口内完成复核。',
          tone: totalCount >= 50 ? 'primary' : 'neutral'
        }
      ]
      const alerts = []
      if (pendingCount) alerts.push(`存在 ${pendingCount} 件待补查商品，建议优先只保留已识别对象`)
      if (unchangedCount) alerts.push(`存在 ${unchangedCount} 件重复目标商品，提交前建议再确认是否需要重复迁移`)
      if (totalCount >= 50) alerts.push(`当前批次体量较大，建议先复制复核 ID 或回退筛选做最后抽查`)
      if (this.selectType === 'transfer_merchant' && !this.targetMerchantLabel) alerts.push('当前还未锁定目标商家，不能直接提交')
      let badge = '低风险'
      let tagType = 'success'
      let desc = '当前提交面板已经聚合了这批迁移最容易出错的几个风险点，可在提交前最后压一次范围。'
      if (this.selectType === 'transfer_merchant' && !this.targetMerchantLabel) {
        badge = '待选商家'
        tagType = 'warning'
        desc = '目标商家还未确认，建议先锁定目标，再决定是否继续提交流转。'
      } else if (pendingCount || unchangedCount || totalCount >= 50) {
        badge = '提交前再看一眼'
        tagType = pendingCount || unchangedCount ? 'warning' : 'primary'
        desc = pendingCount
          ? '这批对象里既有可提的商品，也有待补查项，建议先清掉明显风险再提交。'
          : unchangedCount
            ? '当前已经可以提交，但有一部分商品大概率不会发生变化，更适合先做一次针对性复核。'
            : '批次偏大，建议先做一轮抽样复核后再正式提交。'
      }
      return {
        active: true,
        title: '运营风险总览',
        desc,
        badge,
        tagType,
        items,
        alerts,
        alertType: pendingCount || unchangedCount ? 'warning' : 'info',
        canCopyCurrentTarget: totalCount > 0,
        canFilterCurrentTarget: this.canApplyCurrentBridgeTargetFilter,
        canRestoreLatestFilter: Boolean(this.latestTransferOperation?.querySnapshot),
        canRestoreSnapshot: this.stagedBridgeSnapshotAvailable
      }
    },
    transferSubmitReadiness() {
      if (!this.isTransferDialog) {
        return {
          tone: 'muted',
          tagType: 'info',
          badge: '未启用',
          title: '',
          desc: ''
        }
      }
      if (!this.transferTargetIds.length) {
        return {
          tone: 'warning',
          tagType: 'warning',
          badge: '待选择对象',
          title: '当前还不能提交迁移',
          desc: '先确定处理范围、勾选商品或输入指定商品 ID，形成待迁移对象后才能继续。'
        }
      }
      if (this.selectType === 'transfer_merchant' && !this.targetMerchantLabel) {
        return {
          tone: 'warning',
          tagType: 'warning',
          badge: '待选商家',
          title: '还差目标商家没有确认',
          desc: '当前迁移对象已经准备好，但转商家必须先选定目标商家，建议优先从常用目标或下拉列表里确认。'
        }
      }
      if (this.transferManualPendingIds.length) {
        return {
          tone: 'warning',
          tagType: 'warning',
          badge: '提交前复核',
          title: '这批迁移对象里还有待补查商品',
          desc: '可以继续提交让系统二次补查，也可以先只保留已识别商品，减少提交时被拦截的概率。'
        }
      }
      if (this.transferManualChangeSummary.unchanged > 0) {
        return {
          tone: 'primary',
          tagType: 'primary',
          badge: '建议复核',
          title: '迁移对象已就绪，但存在重复归属商品',
          desc: '这批商品可以提交，不过其中一部分预计保持原归属，建议先确认是不是本轮真的需要重复迁移。'
        }
      }
      return {
        tone: 'success',
        tagType: 'success',
        badge: '可提交',
        title: '当前迁移批次已经满足提交条件',
        desc: this.selectType === 'transfer_merchant'
          ? `目标商家和处理范围都已明确，可以继续把这批商品迁移到 ${this.targetMerchantLabel}。`
          : '处理范围和迁移对象都已明确，可以继续把这批商品统一归口到平台自营。'
      }
    },
    isSelectSubmitDisabled() {
      return (
        this.loading ||
        (this.batchScopedSelectTypes.includes(this.selectType) && !this.batchTargetIds.length) ||
        ((this.selectType === 'transfer_merchant' || this.selectType === 'transfer_platform') && !this.transferTargetIds.length) ||
        (this.selectType === 'transfer_merchant' && !this.target_merchant_id) ||
        (this.selectType === 'batch_thumbnail' && !this.batch_image_id)
      )
    },
    operationHistoryTypeOptions() {
      return [
        { label: '迁移操作', value: 'transfer' },
        { label: '批量处理', value: 'batch' },
        { label: '审核处理', value: 'audit' },
        { label: '上下架', value: 'disable' },
        { label: '删除处理', value: 'delete' }
      ]
    },
    filteredOperationHistory() {
      const keyword = String(this.operationHistoryKeyword || '').trim().toLowerCase()
      return this.operationHistory.filter((item) => {
        const typeMatched = !this.operationHistoryType || this.resolveOperationHistoryType(item) === this.operationHistoryType
        if (!typeMatched) return false
        if (!keyword) return true
        const haystack = [
          item.label,
          item.target,
          item.message,
          item.search_id,
          item.time,
          item.diff?.summary,
          item.querySnapshotLabel
        ]
          .filter(Boolean)
          .join(' ')
          .toLowerCase()
        return haystack.includes(keyword)
      })
    },
    operationHistorySummary() {
      return {
        totalCount: this.filteredOperationHistory.reduce((sum, item) => sum + Number(item.count || 0), 0)
      }
    },
    operationHistoryOverviewCards() {
      const entries = this.filteredOperationHistory
      const transferEntries = entries.filter((item) => this.resolveOperationHistoryType(item) === 'transfer')
      const changedCount = entries.reduce((sum, item) => sum + Number(item.diff?.changedCount || 0), 0)
      const unchangedCount = entries.reduce((sum, item) => sum + Number(item.diff?.unchangedCount || 0), 0)
      const latestTransfer = transferEntries[0]
      const latestTarget = latestTransfer?.target || '暂无迁移目标'
      return [
        {
          key: 'ops',
          label: '操作批次',
          value: `${entries.length} 条`,
          desc: entries.length ? '当前筛选下沉淀的最近操作批次。' : '暂无可展示的操作批次。',
          tone: 'primary'
        },
        {
          key: 'changed',
          label: '真实变化',
          value: `${changedCount} 件`,
          desc: unchangedCount ? `另有 ${unchangedCount} 件保持原状态或原归属。` : '当前筛选下的操作都产生了真实变化。',
          tone: 'success'
        },
        {
          key: 'transfer',
          label: '迁移批次',
          value: `${transferEntries.length} 条`,
          desc: transferEntries.length ? `最近一次迁移目标：${latestTarget}` : '当前筛选下暂无迁移记录。',
          tone: 'warning'
        },
        {
          key: 'target',
          label: '最近目标',
          value: latestTarget,
          desc: latestTransfer?.time ? `${latestTransfer.time} 最近一次执行迁移。` : '可通过迁移操作回显继续复盘目标去向。',
          tone: 'info'
        }
      ]
    },
    quickBatchLibrary() {
      const selectionIds = this.selectGetIds(this.selection).map((item) => Number(item)).filter((item) => item > 0)
      return this.filteredOperationHistory
        .map((operation, index) => {
          const type = this.resolveOperationHistoryType(operation)
          const ids = Array.isArray(operation.ids) ? operation.ids.filter((item) => Number(item) > 0) : []
          const totalCount = Number(operation.count || ids.length || 0)
          const changedCount = Number(operation.diff?.changedCount || 0)
          const unchangedCount = Number(operation.diff?.unchangedCount || 0)
          const overlapIds = selectionIds.length ? selectionIds.filter((id) => ids.includes(id)) : []
          const overlapCount = overlapIds.length
          let score = changedCount + totalCount - index
          let tone = 'info'
          let badge = '批次'
          let desc = operation.message || '可继续围绕这批商品处理。'
          const stats = [`批次 ${totalCount} 件`]
          if (changedCount || unchangedCount) {
            stats.push(`变化 ${changedCount} / 未变 ${unchangedCount}`)
          }
          if (operation.target) {
            stats.push(`目标 ${operation.target}`)
          }
          if (selectionIds.length) {
            stats.push(overlapCount ? `重叠 ${overlapCount} 件` : '与当前勾选无重叠')
          }
          if (type === 'transfer') {
            tone = operation.revertable ? 'warning' : 'success'
            badge = operation.revertable ? '迁移批次' : '已完成迁移'
            desc = unchangedCount
              ? `这批迁移里有 ${unchangedCount} 件保持原归属，适合先承接再做二次复核。`
              : '这批迁移已形成明确结果，适合直接承接到当前工具继续处理。'
            score += 80
          } else if (type === 'audit') {
            tone = 'warning'
            badge = '审核批次'
            desc = changedCount
              ? `审核结果已更新 ${changedCount} 件，适合配合当前筛选继续复核。`
              : '这批审核没有产生明显变化，适合先恢复筛选再确认。'
            score += 45
          } else if (type === 'disable') {
            tone = 'info'
            badge = '状态批次'
            desc = changedCount
              ? `上下架状态已调整 ${changedCount} 件，可继续围绕这批商品运营。`
              : '这批状态操作未产生变化，可按需恢复筛选复核。'
            score += 30
          } else if (type === 'batch') {
            tone = 'primary'
            badge = '素材批次'
            desc = '这批素材维护操作可以作为后续运营复核对象继续查看。'
            score += 20
          } else {
            return null
          }
          if (overlapCount) {
            score += 18 + overlapCount
          }
          const overlap = !selectionIds.length
            ? {
              active: false,
              title: '',
              desc: '',
              ids: []
            }
            : overlapCount === selectionIds.length
              ? {
                active: true,
                title: '当前勾选与该批次完全重叠',
                desc: '这批就是你正在看的对象，可以直接承接重叠部分继续处理。',
                ids: overlapIds
              }
              : overlapCount > 0
                ? {
                  active: true,
                  title: `当前勾选与该批次重叠 ${overlapCount} 件`,
                  desc: `这批里有 ${overlapCount} 件商品正在继续处理，适合先复制或承接重叠部分。`,
                  ids: overlapIds
                }
                : {
                  active: true,
                  title: '当前勾选与该批次没有重叠',
                  desc: '说明你正在处理另一批商品，可先恢复该批筛选后再决定是否切换。',
                  ids: []
                }
          return {
            key: operation.key,
            score,
            tone,
            badge,
            title: operation.label || '最近批次',
            meta: `${operation.time}${operation.querySnapshotLabel ? ` · ${operation.querySnapshotLabel}` : ''}`,
            desc,
            stats,
            overlap,
            operation
          }
        })
        .filter(Boolean)
        .sort((a, b) => b.score - a.score)
        .slice(0, 4)
    },
    priorityOperationCards() {
      return this.filteredOperationHistory
        .map((operation) => {
          const type = this.resolveOperationHistoryType(operation)
          const totalCount = Number(operation.count || 0)
          const changedCount = Number(operation.diff?.changedCount || 0)
          const unchangedCount = Number(operation.diff?.unchangedCount || 0)
          let score = changedCount
          let tone = 'info'
          let badge = '重点关注'
          let title = operation.label || '最近操作'
          let desc = operation.diff?.summary || operation.message || '请继续复核本次处理结果。'
          let tips = [`涉及 ${totalCount} 件商品`]
          const alerts = []
          if (type === 'delete') {
            score += 100 + totalCount
            tone = 'danger'
            badge = '高风险'
            desc = `删除 ${totalCount} 件商品，建议优先确认筛选范围和后续影响。`
            tips = ['优先确认是否误删', '建议核对筛选条件和备份可恢复性']
            if (totalCount >= 10) {
              alerts.push('删除批次较大')
            }
          } else if (type === 'transfer') {
            score += 80 + changedCount
            tone = operation.revertable ? 'warning' : 'success'
            badge = operation.revertable ? '可回退迁移' : '迁移批次'
            desc = unchangedCount
              ? `迁移目标 ${operation.target || '未识别'}，其中 ${changedCount} 件发生归属变化，${unchangedCount} 件保持原归属。`
              : `迁移目标 ${operation.target || '未识别'}，本批次 ${changedCount} 件商品全部发生归属变化。`
            tips = [
              operation.target ? `目标：${operation.target}` : '建议补核目标归属',
              unchangedCount ? `有 ${unchangedCount} 件未变化，建议确认是否重复迁移` : '本批次全部发生归属变化'
            ]
            if (unchangedCount && changedCount === 0) {
              alerts.push('全部未变化，疑似重复迁移')
            } else if (unchangedCount >= Math.max(2, Math.ceil(totalCount / 2))) {
              alerts.push('未变化占比较高')
            }
            if (totalCount >= 20) {
              alerts.push('大批量迁移')
            }
          } else if (type === 'audit') {
            score += 60 + changedCount
            tone = 'warning'
            badge = '审核批次'
            tips = [
              changedCount ? `${changedCount} 件状态已更新` : '本批次未产生状态变化',
              '建议结合筛选轨迹继续复核审核结果'
            ]
          } else if (type === 'disable') {
            score += 40 + changedCount
            tone = 'info'
            badge = '状态调整'
            tips = [
              changedCount ? `${changedCount} 件状态已切换` : '状态未发生变化',
              '建议继续核对前台可售状态'
            ]
          } else if (type === 'batch') {
            score += 30 + changedCount
            tone = 'primary'
            badge = '素材维护'
            tips = [
              changedCount ? `${changedCount} 件素材已更新` : '素材结果与原值一致',
              '建议抽查首个商品确认展示效果'
            ]
          }
          return {
            key: operation.key,
            score,
            tone,
            badge,
            title,
            meta: `${operation.time} · ${totalCount} 件${operation.target ? ` · ${operation.target}` : ''}`,
            desc,
            tips,
            alerts,
            operation
          }
        })
        .sort((a, b) => b.score - a.score)
        .slice(0, 3)
    },
    groupedOperationHistory() {
      const metaMap = {
        transfer: { label: '迁移批次', tone: 'success', desc: '平台与商家归属切换相关操作。' },
        audit: { label: '审核批次', tone: 'warning', desc: '审核通过、拒绝和原因回写。' },
        batch: { label: '素材维护', tone: 'primary', desc: '标签、缩略图等素材类批量处理。' },
        disable: { label: '上下架批次', tone: 'info', desc: '商品上下架状态调整。' },
        delete: { label: '删除批次', tone: 'danger', desc: '删除类高风险处理，请重点复核。' },
        other: { label: '其他操作', tone: 'info', desc: '未归类的操作记录。' }
      }
      const buckets = this.filteredOperationHistory.reduce((result, item) => {
        const type = this.resolveOperationHistoryType(item)
        if (!result[type]) {
          result[type] = []
        }
        result[type].push(item)
        return result
      }, {})
      return Object.keys(buckets).map((key) => ({
        key,
        label: metaMap[key]?.label || metaMap.other.label,
        tone: metaMap[key]?.tone || metaMap.other.tone,
        desc: metaMap[key]?.desc || metaMap.other.desc,
        count: buckets[key].length,
        items: buckets[key]
      }))
    },
    selectedTitlesPreview() {
      return this.selection.slice(0, 6).map((item) => `${item.title} (#${item.id})`)
    },
    filterStepEntries() {
      const timeline = this.filterTimeline.filter((item) => item?.query)
      if (timeline.length) {
        return timeline
      }
      return [this.createFilterTimelineEntry(this.buildRouteQuery())]
    },
    currentFilterStepIndex() {
      if (this.filterTimelineIndex >= 0 && this.filterTimelineIndex < this.filterStepEntries.length) {
        return this.filterTimelineIndex
      }
      if (this.currentFilterStepKey) {
        const cursorIndex = this.filterStepEntries.findIndex((item) => item.key === this.currentFilterStepKey)
        if (cursorIndex >= 0) return cursorIndex
      }
      const current = this.createFilterTimelineEntry(this.buildRouteQuery())
      return this.filterStepEntries.findIndex((item) => item.key === current.key)
    },
    canNavigateFilterBack() {
      return this.currentFilterStepIndex > 0
    },
    canNavigateFilterForward() {
      return this.currentFilterStepIndex >= 0 && this.currentFilterStepIndex < this.filterStepEntries.length - 1
    },
    filterTimelineStatusLabel() {
      if (!this.filterStepEntries.length || this.currentFilterStepIndex < 0) {
        return '当前为默认筛选起点'
      }
      const current = this.filterStepEntries[this.currentFilterStepIndex]
      return `当前第 ${this.currentFilterStepIndex + 1} 步，共 ${this.filterStepEntries.length} 步 · ${current?.label || '默认筛选条件'}`
    },
    latestRecentFilter() {
      return this.recentFilterHistory[0] || null
    },
    bridgeScopeChecklist() {
      return [
        {
          label: this.selection.length ? `已勾选 ${this.selection.length} 件商品` : '先勾选待处理商品',
          done: this.selection.length > 0
        },
        {
          label: this.bridgeMode === 'merchant'
            ? (this.targetMerchantLabel ? `目标商家已锁定为 ${this.targetMerchantLabel}` : '当前为转商家模式，需先选择目标商家')
            : '当前为转平台模式，可直接承接到平台自营',
          done: this.bridgeMode === 'platform' || Boolean(this.targetMerchantLabel)
        },
        {
          label: this.stagedBridgeSummary.active
            ? `承接批次已就绪，共 ${this.transferManualIds.length} 件`
            : '还未形成承接批次，可先把勾选商品带入工具',
          done: this.stagedBridgeSummary.active
        },
        {
          label: this.transferManualPendingIds.length
            ? `还有 ${this.transferManualPendingIds.length} 件待补查，提交前建议先复核`
            : '当前没有待补查商品 ID',
          done: this.transferManualPendingIds.length === 0
        }
      ]
    },
    bridgeNextStep() {
      if (this.bridgeMode === 'merchant' && !this.targetMerchantLabel) {
        return {
          tone: 'warning',
          tagType: 'warning',
          badge: '先选目标',
          title: '当前迁移目标还没锁定',
          desc: this.selection.length
            ? '你已经勾选了待处理商品，但当前是转商家模式，建议先选定目标商家，再把这批商品承接到迁移工具。'
            : '先选定目标商家，再去勾选或承接商品，后续整页迁移和批次复核会更顺手。',
          action: 'rollback'
        }
      }
      if (this.stagedBridgeSummary.active) {
        return {
          tone: this.transferManualPendingIds.length ? 'warning' : 'success',
          tagType: this.transferManualPendingIds.length ? 'warning' : 'success',
          badge: this.transferManualPendingIds.length ? '待复核' : '可提交',
          title: this.transferManualPendingIds.length ? '承接批次里还有待补查商品' : '承接批次已经可以继续迁移',
          desc: this.transferManualPendingIds.length
            ? '建议优先按目标归属筛选或恢复最近筛选做最后一轮核对，确认无误后再继续提交迁移。'
            : '这批商品已经形成稳定承接状态，可以直接继续迁移；如果你想更稳一点，也可以先按目标归属过滤复核。',
          action: 'submit'
        }
      }
      if (this.selection.length) {
        return {
          tone: 'primary',
          tagType: 'primary',
          badge: '先承接',
          title: '当前勾选已经形成临时作战批次',
          desc: this.bridgeMode === 'platform'
            ? '建议先把当前勾选商品带入平台迁移工具，随后就能复用承接批次、筛选回退和结果回显。'
            : `建议先把当前勾选商品带入 ${this.targetMerchantLabel} 迁移工具，再做提交或回退复核。`,
          action: 'stage'
        }
      }
      return {
        tone: 'muted',
        tagType: 'info',
        badge: '等待开始',
        title: '先从筛选或勾选开始这一轮处理',
        desc: '可以先通过筛选缩小范围，再勾选商品；当前迁移工具会自动保留最近条件，方便逐步回退。',
        action: this.canNavigateFilterBack ? 'rollback' : ''
      }
    }
  },
  async created() {
    this.height = screenHeight()
    this.getParams()
    await this.initRouteQueryState()
    if (!this.recentFilterHistory.length) {
      this.rememberRecentFilter(this.buildRouteQuery())
    }
    this.restoreBridgePreferences()
    this.syncTransferDraftState()
    this.syncStagedBridgeSnapshotState()
    this.restorePersistedOperationHistory()
    this.restoreOperationWorkbenchExpanded()
    this.pendingEntryAnchorAlign = true
    this.scheduleEnterPageScrollReset()
    this.list()
  },
  mounted() {
    this.pendingEntryAnchorAlign = true
    this.scheduleEnterPageScrollReset()
  },
  activated() {
    this.pendingEntryAnchorAlign = true
    this.scheduleEnterPageScrollReset()
  },
  watch: {
    async '$route.fullPath'() {
      if (this.ignoreRouteWatch) {
        this.ignoreRouteWatch = false
        return
      }
      this.applyRouteQuery(this.$route.query)
      this.lastRouteQuery = this.buildRouteQuery()
      this.recentFilterHistory = this.readRecentFilterHistory()
      this.syncFilterTimeline(this.buildRouteQuery(), 'sync')
      this.clearSelection()
      this.pendingEntryAnchorAlign = true
      this.list()
    },
    bridgeMode() {
      this.writeBridgePreferences()
      this.persistTransferDraft()
    },
    target_merchant_id() {
      this.writeBridgePreferences()
      this.persistTransferDraft()
    },
    transferScope() {
      this.persistTransferDraft()
    },
    transferManualIdsText() {
      this.persistTransferDraft()
    }
  },
  methods: {
    checkPermission,
    getGoodsPageScrollContainers() {
      if (typeof document === 'undefined') return []
      const containers = []
      const appMain = document.querySelector('.app-main')
      if (appMain) containers.push(appMain)
      const scrollingElement = document.scrollingElement || document.documentElement || document.body
      if (scrollingElement && !containers.includes(scrollingElement)) containers.push(scrollingElement)
      if (document.body && !containers.includes(document.body)) containers.push(document.body)
      return containers
    },
    getGoodsTopActionsAnchor() {
      return this.$refs.goodsTopActions?.$el || this.$refs.goodsTopActions || null
    },
    resetGoodsPageScrollTop() {
      this.getGoodsPageScrollContainers().forEach((node) => {
        try {
          node.scrollTop = 0
        } catch (error) {}
      })
      if (typeof window !== 'undefined') {
        try {
          window.scrollTo(0, 0)
        } catch (error) {}
      }
    },
    alignGoodsTopActionsIntoView() {
      const anchor = this.getGoodsTopActionsAnchor()
      if (!anchor) return
      try {
        anchor.scrollIntoView({ block: 'start', inline: 'nearest' })
      } catch (error) {
        try {
          anchor.scrollIntoView(true)
        } catch (innerError) {}
      }
    },
    scheduleEnterPageScrollReset() {
      this.$nextTick(() => {
        this.resetGoodsPageScrollTop()
        this.alignGoodsTopActionsIntoView()
        ;[24, 80, 180, 320].forEach((delay) => {
          window.setTimeout(() => {
            this.resetGoodsPageScrollTop()
            this.alignGoodsTopActionsIntoView()
          }, delay)
        })
      })
    },
    getRouteQueryStorageKey() {
      return 'admin_next_goods_query_v1'
    },
    getRouteHistoryStorageKey() {
      return 'admin_next_goods_history_v1'
    },
    getFilterTimelineStorageKey() {
      return 'admin_next_goods_timeline_v1'
    },
    getFilterCursorStorageKey() {
      return 'admin_next_goods_timeline_cursor_v1'
    },
    getBridgePreferencesStorageKey() {
      return 'admin_next_goods_bridge_preferences_v1'
    },
    getOperationHistoryStorageKey() {
      return 'admin_next_goods_operation_history_v1'
    },
    getOperationWorkbenchExpandedStorageKey() {
      return 'admin_next_goods_workbench_expanded_v2'
    },
    getTransferDraftStorageKey() {
      return 'admin_next_goods_transfer_draft_v1'
    },
    getStagedBridgeSnapshotStorageKey() {
      return 'admin_next_goods_staged_bridge_snapshot_v1'
    },
    normalizeRouteValue(value) {
      return Array.isArray(value) ? value[0] : value
    },
    parseRouteNumber(value, fallback = undefined) {
      if (value === '' || value === null || value === undefined) return fallback
      const next = Number(this.normalizeRouteValue(value))
      return Number.isNaN(next) ? fallback : next
    },
    parsePositiveRouteNumber(value, fallback) {
      const next = this.parseRouteNumber(value, fallback)
      return Number.isInteger(next) && next > 0 ? next : fallback
    },
    hasRouteFilters() {
      return Object.keys(this.$route?.query || {}).length > 0
    },
    readPersistedQuery() {
      try {
        const raw = localStorage.getItem(this.getRouteQueryStorageKey())
        if (!raw) return null
        const parsed = JSON.parse(raw)
        return parsed && typeof parsed === 'object' ? parsed : null
      } catch (error) {
        return null
      }
    },
    writePersistedQuery(params) {
      try {
        localStorage.setItem(this.getRouteQueryStorageKey(), JSON.stringify(params))
      } catch (error) {}
    },
    readRecentFilterHistory() {
      try {
        const raw = localStorage.getItem(this.getRouteHistoryStorageKey())
        if (!raw) return []
        const parsed = JSON.parse(raw)
        return Array.isArray(parsed)
          ? parsed
            .filter((item) => item && item.query)
            .map((item) => ({
              ...item,
              key: this.serializeRouteQuery(item.query)
            }))
          : []
      } catch (error) {
        return []
      }
    },
    writeRecentFilterHistory(entries) {
      this.recentFilterHistory = entries
      try {
        localStorage.setItem(this.getRouteHistoryStorageKey(), JSON.stringify(entries))
      } catch (error) {}
    },
    readPersistedFilterTimeline() {
      try {
        const raw = sessionStorage.getItem(this.getFilterTimelineStorageKey())
        if (!raw) return null
        const parsed = JSON.parse(raw)
        const entries = Array.isArray(parsed?.entries)
          ? parsed.entries
            .filter((item) => item && item.query)
            .map((item) => this.createFilterTimelineEntry(item.query))
          : []
        if (!entries.length) return null
        const index = Number.isInteger(parsed?.index) ? parsed.index : entries.length - 1
        return {
          entries,
          index: Math.min(Math.max(index, 0), entries.length - 1)
        }
      } catch (error) {
        return null
      }
    },
    writePersistedFilterTimeline(entries = this.filterTimeline, index = this.filterTimelineIndex) {
      try {
        if (!Array.isArray(entries) || !entries.length) {
          sessionStorage.removeItem(this.getFilterTimelineStorageKey())
          return
        }
        sessionStorage.setItem(this.getFilterTimelineStorageKey(), JSON.stringify({
          entries: entries.map((item) => this.createFilterTimelineEntry(item.query)),
          index
        }))
      } catch (error) {}
    },
    readPersistedFilterCursor() {
      try {
        return sessionStorage.getItem(this.getFilterCursorStorageKey()) || ''
      } catch (error) {
        return ''
      }
    },
    writePersistedFilterCursor(key = this.currentFilterStepKey) {
      try {
        if (!key) {
          sessionStorage.removeItem(this.getFilterCursorStorageKey())
          return
        }
        sessionStorage.setItem(this.getFilterCursorStorageKey(), key)
      } catch (error) {}
    },
    readPersistedOperationHistory() {
      try {
        const raw = localStorage.getItem(this.getOperationHistoryStorageKey())
        if (!raw) return []
        const parsed = JSON.parse(raw)
        return Array.isArray(parsed) ? parsed.filter((item) => item && typeof item === 'object') : []
      } catch (error) {
        return []
      }
    },
    writePersistedOperationHistory(entries) {
      const nextEntries = Array.isArray(entries) ? entries.slice(0, 6) : []
      this.operationHistory = nextEntries
      this.latestOperation = nextEntries[0] || null
      try {
        if (!nextEntries.length) {
          localStorage.removeItem(this.getOperationHistoryStorageKey())
          return
        }
        localStorage.setItem(this.getOperationHistoryStorageKey(), JSON.stringify(nextEntries))
      } catch (error) {}
    },
    readPersistedOperationWorkbenchExpanded() {
      try {
        const value = localStorage.getItem(this.getOperationWorkbenchExpandedStorageKey())
        if (value === null) {
          return false
        }
        return value === '1'
      } catch (error) {
        return false
      }
    },
    writePersistedOperationWorkbenchExpanded(expanded = this.operationWorkbenchExpanded) {
      try {
        if (expanded) {
          localStorage.setItem(this.getOperationWorkbenchExpandedStorageKey(), '1')
          return
        }
        localStorage.removeItem(this.getOperationWorkbenchExpandedStorageKey())
      } catch (error) {}
    },
    restoreOperationWorkbenchExpanded() {
      this.operationWorkbenchExpanded = this.readPersistedOperationWorkbenchExpanded()
    },
    restorePersistedOperationHistory() {
      this.writePersistedOperationHistory(this.readPersistedOperationHistory())
    },
    readTransferDraft() {
      try {
        const raw = localStorage.getItem(this.getTransferDraftStorageKey())
        if (!raw) return null
        const parsed = JSON.parse(raw)
        return parsed && typeof parsed === 'object' ? parsed : null
      } catch (error) {
        return null
      }
    },
    syncTransferDraftState() {
      const draft = this.readTransferDraft()
      const ids = this.parseGoodsIds(draft?.transferManualIdsText)
      this.transferDraftAvailable = Boolean(draft && (ids.length || Number(draft.target_merchant_id) > 0))
      if (!this.transferDraftAvailable) {
        this.transferDraftHint = ''
        return
      }
      const targetLabel = Number(draft.target_merchant_id) > 0 ? this.resolveMerchantTitle(draft.target_merchant_id) : '平台自营'
      this.transferDraftHint = `已记忆 ${ids.length} 个商品 ID / 目标 ${targetLabel}`
    },
    persistTransferDraft() {
      try {
        const payload = {
          bridgeMode: this.bridgeMode === 'merchant' ? 'merchant' : 'platform',
          transferScope: this.transferScope || 'selected',
          transferManualIdsText: String(this.transferManualIdsText || '').trim(),
          target_merchant_id: Number(this.target_merchant_id) > 0 ? Number(this.target_merchant_id) : 0
        }
        const ids = this.parseGoodsIds(payload.transferManualIdsText)
        if (!ids.length && payload.target_merchant_id <= 0) {
          localStorage.removeItem(this.getTransferDraftStorageKey())
          this.syncTransferDraftState()
          return
        }
        localStorage.setItem(this.getTransferDraftStorageKey(), JSON.stringify(payload))
      } catch (error) {}
      this.syncTransferDraftState()
    },
    restoreTransferDraft({ silent = false } = {}) {
      const draft = this.readTransferDraft()
      if (!draft) {
        this.syncTransferDraftState()
        if (!silent) ElMessage.warning('暂无可恢复的迁移草稿')
        return false
      }
      this.bridgeMode = draft.bridgeMode === 'merchant' ? 'merchant' : 'platform'
      this.transferScope = draft.transferScope || 'manual_ids'
      this.transferManualIdsText = String(draft.transferManualIdsText || '')
      this.target_merchant_id = Number(draft.target_merchant_id) > 0 ? Number(draft.target_merchant_id) : undefined
      this.syncTransferDraftState()
      if (!silent) ElMessage.success('已恢复上次迁移草稿')
      return true
    },
    clearTransferDraft() {
      try {
        localStorage.removeItem(this.getTransferDraftStorageKey())
      } catch (error) {}
      this.syncTransferDraftState()
      ElMessage.success('迁移草稿已清空')
    },
    readStagedBridgeSnapshot() {
      try {
        const raw = localStorage.getItem(this.getStagedBridgeSnapshotStorageKey())
        if (!raw) return null
        const parsed = JSON.parse(raw)
        return parsed && typeof parsed === 'object' ? parsed : null
      } catch (error) {
        return null
      }
    },
    syncStagedBridgeSnapshotState() {
      const snapshot = this.readStagedBridgeSnapshot()
      const ids = this.parseGoodsIds(snapshot?.transferManualIdsText)
      this.stagedBridgeSnapshotAvailable = Boolean(snapshot && ids.length)
      if (!this.stagedBridgeSnapshotAvailable) {
        this.stagedBridgeSnapshotHint = ''
        return
      }
      const targetLabel = Number(snapshot.target_merchant_id) > 0 ? this.resolveMerchantTitle(snapshot.target_merchant_id) : '平台自营'
      const sourceLabel = snapshot.sourceLabel || '最近承接批次'
      this.stagedBridgeSnapshotHint = `${sourceLabel} · ${ids.length} 件 / 目标 ${targetLabel}`
    },
    persistStagedBridgeSnapshot(payload = {}, { silent = true } = {}) {
      try {
        const normalized = {
          bridgeMode: payload.bridgeMode === 'merchant' ? 'merchant' : 'platform',
          transferScope: 'manual_ids',
          transferManualIdsText: String(payload.transferManualIdsText || '').trim(),
          target_merchant_id: Number(payload.target_merchant_id) > 0 ? Number(payload.target_merchant_id) : 0,
          sourceLabel: String(payload.sourceLabel || '').trim()
        }
        const ids = this.parseGoodsIds(normalized.transferManualIdsText)
        if (!ids.length) {
          localStorage.removeItem(this.getStagedBridgeSnapshotStorageKey())
          this.syncStagedBridgeSnapshotState()
          return false
        }
        localStorage.setItem(this.getStagedBridgeSnapshotStorageKey(), JSON.stringify(normalized))
      } catch (error) {
        return false
      }
      this.syncStagedBridgeSnapshotState()
      if (!silent) {
        ElMessage.success('已更新最近承接快照')
      }
      return true
    },
    rememberCurrentBridgeSnapshot(sourceLabel = '最近承接批次') {
      if (this.transferScope !== 'manual_ids') return false
      if (!this.transferManualIds.length) return false
      return this.persistStagedBridgeSnapshot({
        bridgeMode: this.bridgeMode,
        transferManualIdsText: this.transferManualIds.join(','),
        target_merchant_id: this.target_merchant_id,
        sourceLabel
      })
    },
    restoreStagedBridgeSnapshot({ silent = false } = {}) {
      const snapshot = this.readStagedBridgeSnapshot()
      if (!snapshot) {
        this.syncStagedBridgeSnapshotState()
        if (!silent) ElMessage.warning('暂无可恢复的最近承接批次')
        return false
      }
      const ids = this.parseGoodsIds(snapshot.transferManualIdsText)
      if (!ids.length) {
        this.syncStagedBridgeSnapshotState()
        if (!silent) ElMessage.warning('最近承接快照已失效')
        return false
      }
      this.bridgeMode = snapshot.bridgeMode === 'merchant' ? 'merchant' : 'platform'
      this.transferScope = 'manual_ids'
      this.transferManualIdsText = ids.join(',')
      this.target_merchant_id = Number(snapshot.target_merchant_id) > 0 ? Number(snapshot.target_merchant_id) : undefined
      this.syncStagedBridgeSnapshotState()
      if (!silent) ElMessage.success(`已恢复最近承接批次，共 ${ids.length} 件商品`)
      return true
    },
    readBridgePreferences() {
      try {
        const raw = localStorage.getItem(this.getBridgePreferencesStorageKey())
        if (!raw) return null
        const parsed = JSON.parse(raw)
        return parsed && typeof parsed === 'object' ? parsed : null
      } catch (error) {
        return null
      }
    },
    writeBridgePreferences() {
      try {
        localStorage.setItem(this.getBridgePreferencesStorageKey(), JSON.stringify({
          bridgeMode: this.bridgeMode === 'merchant' ? 'merchant' : 'platform',
          target_merchant_id: Number(this.target_merchant_id) > 0 ? Number(this.target_merchant_id) : undefined
        }))
      } catch (error) {}
    },
    restoreBridgePreferences() {
      const persisted = this.readBridgePreferences()
      if (!persisted) return
      this.bridgeMode = persisted.bridgeMode === 'merchant' ? 'merchant' : 'platform'
      this.target_merchant_id = Number(persisted.target_merchant_id) > 0 ? Number(persisted.target_merchant_id) : undefined
    },
    resetBridgePreferences() {
      this.bridgeMode = 'platform'
      this.target_merchant_id = undefined
      try {
        localStorage.removeItem(this.getBridgePreferencesStorageKey())
      } catch (error) {}
      ElMessage.success('迁移偏好已清除')
    },
    describeRouteQuerySummary(params = {}) {
      const tags = []
      if (params.quick_filter) {
        const quick = this.quickFilters.find((item) => item.key === params.quick_filter)
        if (quick) tags.push(quick.label)
      }
      if (params.search_value) tags.push(`关键词 ${params.search_value}`)
      if (params.merchant_id === '0') tags.push('平台自营')
      if (params.merchant_id && params.merchant_id !== '0') {
        const merchant = this.merchantOptions.find((item) => String(item.id) === String(params.merchant_id))
        if (merchant) tags.push(`商家 ${merchant.title}`)
      }
      if (params.status !== undefined) {
        const status = this.goodsStatusOptions.find((item) => String(item.value) === String(params.status))
        if (status) tags.push(status.label)
      }
      if (params.is_disable !== undefined) {
        tags.push(String(params.is_disable) === '0' ? '上架' : '下架')
      }
      if (params.start_date && params.end_date) {
        tags.push(`${params.start_date} 至 ${params.end_date}`)
      }
      return tags.slice(0, 3).join(' / ') || '默认筛选条件'
    },
    serializeRouteQuery(params = {}) {
      return JSON.stringify(Object.entries(params).sort(([a], [b]) => a.localeCompare(b)))
    },
    rememberRecentFilter(params = this.buildRouteQuery()) {
      const serialized = this.serializeRouteQuery(params)
      const nextEntry = {
        key: serialized,
        label: this.describeRouteQuerySummary(params),
        meta: Object.keys(params).length ? `${Object.keys(params).length} 项条件` : '默认条件',
        query: params
      }
      const history = this.readRecentFilterHistory()
        .filter((item) => item && item.query)
        .filter((item) => this.serializeRouteQuery(item.query) !== serialized)
      this.writeRecentFilterHistory([nextEntry, ...history].slice(0, 6))
    },
    createFilterTimelineEntry(params = this.buildRouteQuery()) {
      return {
        key: this.serializeRouteQuery(params),
        label: this.describeRouteQuerySummary(params),
        query: { ...params }
      }
    },
    syncFilterTimeline(params = this.buildRouteQuery(), mode = 'push') {
      const entry = this.createFilterTimelineEntry(params)
      const existingIndex = this.filterTimeline.findIndex((item) => item.key === entry.key)
      if (mode === 'sync') {
        if (existingIndex >= 0) {
          this.filterTimeline.splice(existingIndex, 1, entry)
          this.filterTimelineIndex = existingIndex
          this.currentFilterStepKey = entry.key
          this.writePersistedFilterCursor()
          this.writePersistedFilterTimeline()
          return
        }
      }
      if (mode === 'replace' && this.filterTimelineIndex >= 0) {
        this.filterTimeline.splice(this.filterTimelineIndex, 1, entry)
        this.currentFilterStepKey = entry.key
        this.writePersistedFilterCursor()
        this.writePersistedFilterTimeline()
        return
      }
      const base = this.filterTimeline.slice(0, this.filterTimelineIndex + 1)
      const last = base[base.length - 1]
      if (last?.key === entry.key) {
        this.filterTimeline = base
        this.filterTimelineIndex = base.length - 1
        this.currentFilterStepKey = entry.key
        this.writePersistedFilterCursor()
        this.writePersistedFilterTimeline()
        return
      }
      const nextTimeline = [...base, entry].slice(-12)
      this.filterTimeline = nextTimeline
      this.filterTimelineIndex = nextTimeline.length - 1
      this.currentFilterStepKey = entry.key
      this.writePersistedFilterCursor()
      this.writePersistedFilterTimeline()
    },
    resetFilterTimeline(params = this.buildRouteQuery()) {
      const entry = this.createFilterTimelineEntry(params)
      this.filterTimeline = [entry]
      this.filterTimelineIndex = 0
      this.currentFilterStepKey = entry.key
      this.writePersistedFilterCursor()
      this.writePersistedFilterTimeline()
    },
    restoreFilterTimeline(params = this.buildRouteQuery()) {
      const entry = this.createFilterTimelineEntry(params)
      const persisted = this.readPersistedFilterTimeline()
      if (!persisted?.entries?.length) {
        return false
      }
      const nextIndex = persisted.entries.findIndex((item) => item.key === entry.key)
      if (nextIndex < 0) {
        return false
      }
      this.filterTimeline = persisted.entries.map((item, index) => (index === nextIndex ? entry : item))
      this.filterTimelineIndex = nextIndex
      this.currentFilterStepKey = entry.key
      this.writePersistedFilterCursor()
      this.writePersistedFilterTimeline()
      return true
    },
    applyRouteQuery(routeQuery = {}) {
      const defaultQuery = this.$options.data().query
      const startDate = this.normalizeRouteValue(routeQuery.start_date)
      const endDate = this.normalizeRouteValue(routeQuery.end_date)
      const nextSearchField = this.normalizeRouteValue(routeQuery.search_field)
      const nextSearchExp = this.normalizeRouteValue(routeQuery.search_exp)
      const nextDateField = this.normalizeRouteValue(routeQuery.date_field)
      const allowedSearchFields = [this.idkey, 'code', 'title', 'remark']
      this.query.page = this.parsePositiveRouteNumber(routeQuery.page, 1)
      this.query.limit = this.parsePositiveRouteNumber(routeQuery.limit, defaultQuery.limit)
      this.query.search_field = allowedSearchFields.includes(nextSearchField) ? nextSearchField : defaultQuery.search_field
      this.query.search_exp = typeof nextSearchExp === 'string' && nextSearchExp ? nextSearchExp : defaultQuery.search_exp
      this.query.date_field = typeof nextDateField === 'string' && nextDateField ? nextDateField : defaultQuery.date_field
      this.query.status = this.parseRouteNumber(routeQuery.status, undefined)
      this.query.is_disable = this.parseRouteNumber(routeQuery.is_disable, undefined)
      this.query.goods_type_id = this.parseRouteNumber(routeQuery.goods_type_id, undefined)
      this.query.goods_label_id = this.parseRouteNumber(routeQuery.goods_label_id, undefined)
      this.query.merchant_id = routeQuery.merchant_id === undefined ? -1 : this.parseRouteNumber(routeQuery.merchant_id, -1)
      this.query.search_value = typeof this.normalizeRouteValue(routeQuery.search_value) === 'string'
        ? this.normalizeRouteValue(routeQuery.search_value)
        : ''
      this.query.date_value = startDate && endDate ? [String(startDate), String(endDate)] : []
      this.query.sort_field = typeof this.normalizeRouteValue(routeQuery.sort_field) === 'string'
        ? this.normalizeRouteValue(routeQuery.sort_field)
        : ''
      this.query.sort_value = typeof this.normalizeRouteValue(routeQuery.sort_value) === 'string'
        ? this.normalizeRouteValue(routeQuery.sort_value)
        : ''
      this.activeQuickFilter = typeof this.normalizeRouteValue(routeQuery.quick_filter) === 'string' && this.normalizeRouteValue(routeQuery.quick_filter)
        ? this.normalizeRouteValue(routeQuery.quick_filter)
        : 'all'
    },
    buildRouteQuery() {
      const defaultQuery = this.$options.data().query
      const params = {}
      if (Number(this.query.page) > 1) params.page = String(this.query.page)
      if (Number(this.query.limit) !== Number(defaultQuery.limit)) params.limit = String(this.query.limit)
      if (this.query.search_value) {
        params.search_value = this.query.search_value
        params.search_field = this.query.search_field || defaultQuery.search_field
      } else if (this.query.search_field !== defaultQuery.search_field) {
        params.search_field = this.query.search_field
      }
      if (this.query.search_exp && this.query.search_exp !== defaultQuery.search_exp) params.search_exp = this.query.search_exp
      if (this.query.date_field && this.query.date_field !== defaultQuery.date_field) params.date_field = this.query.date_field
      if (this.query.status !== undefined) params.status = String(this.query.status)
      if (this.query.is_disable !== undefined) params.is_disable = String(this.query.is_disable)
      if (this.query.goods_type_id !== undefined && this.query.goods_type_id !== null) params.goods_type_id = String(this.query.goods_type_id)
      if (this.query.goods_label_id !== undefined && this.query.goods_label_id !== null) params.goods_label_id = String(this.query.goods_label_id)
      if (Number(this.query.merchant_id) !== -1) params.merchant_id = String(this.query.merchant_id)
      if (Array.isArray(this.query.date_value) && this.query.date_value.length === 2) {
        params.start_date = this.query.date_value[0]
        params.end_date = this.query.date_value[1]
      }
      if (this.query.sort_field) params.sort_field = this.query.sort_field
      if (this.query.sort_value) params.sort_value = this.query.sort_value
      if (this.activeQuickFilter && this.activeQuickFilter !== 'all') params.quick_filter = this.activeQuickFilter
      return params
    },
    async syncRouteQuery(mode = 'push') {
      const nextQuery = this.buildRouteQuery()
      const currentQuery = { ...this.lastRouteQuery }
      const normalizeEntries = (value) => JSON.stringify(Object.entries(value).sort(([a], [b]) => a.localeCompare(b)))
      const nextEntry = this.createFilterTimelineEntry(nextQuery)
      if (normalizeEntries(currentQuery) === normalizeEntries(nextQuery)) {
        this.lastRouteQuery = { ...nextQuery }
        this.writePersistedQuery(nextQuery)
        this.rememberRecentFilter(nextQuery)
        this.syncFilterTimeline(nextQuery, 'sync')
        return
      }
      this.ignoreRouteWatch = true
      this.lastRouteQuery = { ...nextQuery }
      this.writePersistedQuery(nextQuery)
      this.rememberRecentFilter(nextQuery)
      this.syncFilterTimeline(nextQuery, mode === 'replace' ? 'replace' : 'push')
      await this.$router[mode]({
        path: this.$route.path,
        query: nextQuery
      })
    },
    async initRouteQueryState() {
      this.recentFilterHistory = this.readRecentFilterHistory()
      if (this.hasRouteFilters()) {
        this.applyRouteQuery(this.$route.query)
        this.lastRouteQuery = this.buildRouteQuery()
        this.writePersistedQuery(this.buildRouteQuery())
        if (!this.restoreFilterTimeline(this.buildRouteQuery())) {
          this.resetFilterTimeline(this.buildRouteQuery())
        }
        return
      }
      const persisted = this.readPersistedQuery()
      if (persisted) {
        this.applyRouteQuery(persisted)
        this.lastRouteQuery = this.buildRouteQuery()
        this.ignoreRouteWatch = true
        await this.$router.replace({
          path: this.$route.path,
          query: this.buildRouteQuery()
        })
        this.writePersistedQuery(this.buildRouteQuery())
        if (!this.restoreFilterTimeline(this.buildRouteQuery())) {
          this.resetFilterTimeline(this.buildRouteQuery())
        }
        return
      }
      this.lastRouteQuery = this.buildRouteQuery()
      this.writePersistedQuery(this.buildRouteQuery())
      if (!this.restoreFilterTimeline(this.buildRouteQuery())) {
        this.resetFilterTimeline(this.buildRouteQuery())
      }
    },
    async navigateFilterStep(step) {
      const nextIndex = this.currentFilterStepIndex + step
      if (nextIndex < 0 || nextIndex >= this.filterStepEntries.length) return
      const target = this.filterStepEntries[nextIndex]
      if (!target?.query) return
      this.applyRouteQuery(target.query)
      this.lastRouteQuery = { ...target.query }
      this.filterTimelineIndex = nextIndex
      this.currentFilterStepKey = target.key
      this.writePersistedFilterCursor()
      this.writePersistedFilterTimeline()
      this.writePersistedQuery(target.query)
      this.ignoreRouteWatch = true
      await this.$router.push({
        path: this.$route.path,
        query: target.query
      })
      this.clearSelection()
      this.list()
    },
    async restoreRecentFilter(item) {
      if (!item?.query) return
      this.applyRouteQuery(item.query)
      this.query.page = 1
      await this.syncRouteQuery('push')
      this.clearSelection()
      this.list()
    },
    formatCount(value) {
      return Number(value || 0).toLocaleString('zh-CN')
    },
    formatMoney(value) {
      const amount = Number(value || 0)
      return amount.toFixed(2)
    },
    formatOperationTime() {
      return new Date().toLocaleString('zh-CN', { hour12: false })
    },
    buildDetailModel(payload = {}) {
      return {
        ...payload,
        merchant_title: payload.merchant_title || this.resolveMerchantTitle(payload.merchant_id),
        type_title: payload.type_title || payload.goods_type_title || '',
        label_title: payload.label_title || this.normalizeLabelTitle(payload.goods_label_id)
      }
    },
    normalizeLabelTitle(labelIds) {
      if (!Array.isArray(labelIds) || !labelIds.length) return ''
      const map = new Map((this.params.goods_labels || []).map((item) => [Number(item.id), item.title]))
      return labelIds.map((id) => map.get(Number(id))).filter(Boolean).join('、')
    },
    captureQuerySnapshot() {
      return {
        status: this.query.status,
        is_disable: this.query.is_disable,
        goods_type_id: this.query.goods_type_id,
        goods_label_id: this.query.goods_label_id,
        merchant_id: this.query.merchant_id,
        search_field: this.query.search_field,
        search_exp: this.query.search_exp,
        search_value: this.query.search_value,
        date_value: Array.isArray(this.query.date_value) ? [...this.query.date_value] : [],
        activeQuickFilter: this.activeQuickFilter
      }
    },
    describeQuerySnapshot(snapshot = {}) {
      const tags = []
      if (snapshot.activeQuickFilter && snapshot.activeQuickFilter !== 'all') {
        const quick = this.quickFilters.find((item) => item.key === snapshot.activeQuickFilter)
        if (quick) tags.push(`快捷筛选 ${quick.label}`)
      }
      if (snapshot.status !== undefined) {
        const status = this.goodsStatusOptions.find((item) => Number(item.value) === Number(snapshot.status))
        if (status) tags.push(`状态 ${status.label}`)
      }
      if (snapshot.is_disable !== undefined) {
        tags.push(`上架 ${Number(snapshot.is_disable) === 0 ? '上架' : '下架'}`)
      }
      if (Number(snapshot.merchant_id) === 0) {
        tags.push('归属 平台自营')
      } else if (Number(snapshot.merchant_id) > 0) {
        tags.push(`归属 ${this.resolveMerchantTitle(snapshot.merchant_id)}`)
      }
      if (snapshot.search_value) {
        tags.push(`关键词 ${snapshot.search_value}`)
      }
      if (Array.isArray(snapshot.date_value) && snapshot.date_value.length === 2) {
        tags.push(`日期 ${snapshot.date_value[0]} 至 ${snapshot.date_value[1]}`)
      }
      return tags.length ? tags.join(' / ') : '默认筛选条件'
    },
    resolveMerchantTitle(merchantId) {
      if (Number(merchantId) === 0) return '平台自营'
      const merchant = this.merchantOptions.find((item) => Number(item.id) === Number(merchantId))
      return merchant ? merchant.title : `商家#${merchantId}`
    },
    buildOperationDiff(row, targetMerchantId) {
      const list = Array.isArray(row) ? row : []
      const targetId = Number(targetMerchantId)
      const changedRows = list.filter((item) => Number(item.merchant_id) !== targetId)
      const unchangedRows = list.filter((item) => Number(item.merchant_id) === targetId)
      const changedCount = changedRows.length
      const unchangedCount = unchangedRows.length
      const beforePlatformCount = list.filter((item) => Number(item.merchant_id) === 0).length
      const beforeMerchantCount = Math.max(list.length - beforePlatformCount, 0)
      const beforeMap = list.reduce((result, item) => {
        const title = this.resolveMerchantTitle(item.merchant_id)
        result[title] = (result[title] || 0) + 1
        return result
      }, {})
      const beforeDesc = Object.keys(beforeMap)
        .slice(0, 3)
        .map((title) => `${title} ${beforeMap[title]} 件`)
        .join('，')

      return {
        changedCount,
        unchangedCount,
        changedIds: changedRows.map((item) => Number(item[this.idkey])).filter((item) => item > 0),
        unchangedIds: unchangedRows.map((item) => Number(item[this.idkey])).filter((item) => item > 0),
        beforeLabel: `平台 ${beforePlatformCount} 件 / 商家 ${beforeMerchantCount} 件`,
        beforeDesc: beforeDesc || '本次操作前未识别到归属信息。',
        afterLabel: targetId === 0 ? `平台 ${list.length} 件 / 商家 0 件` : `平台 0 件 / 商家 ${list.length} 件`,
        afterDesc: targetId === 0 ? '提交后统一归口到平台自营。' : `提交后统一归属到 ${this.resolveMerchantTitle(targetId)}。`,
        summaryLabel: '变化摘要',
        summaryValue: `${changedCount} 件切换归属`,
        summary: unchangedCount
          ? `其中 ${changedCount} 件会真正迁移，另有 ${unchangedCount} 件已在目标归属。`
          : `本次 ${changedCount} 件商品都会发生归属变化。`
      }
    },
    buildBatchOperationDiff(type, row, payload = {}) {
      const list = Array.isArray(row) ? row : []
      if (!list.length) return null
      if (type === 'auth') {
        const targetStatus = Number(payload.goods_status)
        const changedCount = list.filter((item) => Number(item.status) !== targetStatus).length
        const unchangedCount = Math.max(list.length - changedCount, 0)
        const pendingCount = list.filter((item) => Number(item.status) === 0).length
        const approvedCount = list.filter((item) => Number(item.status) === 1).length
        const rejectedCount = list.filter((item) => Number(item.status) === 2).length
        const statusLabel = targetStatus === 2 ? '审核拒绝' : '审核通过'
        const statusDesc = targetStatus === 2
          ? (payload.auth_msg ? `提交后统一标记为审核拒绝，并写入拒绝原因：${payload.auth_msg}` : '提交后统一标记为审核拒绝。')
          : '提交后统一标记为审核通过，并同步写入库存和标签结果。'
        return {
          changedCount,
          unchangedCount,
          beforeLabel: `待审核 ${pendingCount} 件 / 通过 ${approvedCount} 件 / 拒绝 ${rejectedCount} 件`,
          beforeDesc: '提交前会保留商品归属、缩略图和价格信息，仅更新审核相关字段。',
          afterLabel: statusLabel,
          afterDesc: statusDesc,
          summaryLabel: '审核变化',
          summaryValue: `${changedCount} 件更新审核状态`,
          summary: unchangedCount
            ? `其中 ${changedCount} 件会真正更新审核状态，另有 ${unchangedCount} 件已处于目标状态。`
            : `本次 ${changedCount} 件商品都会更新审核状态。`
        }
      }
      if (type === 'delete') {
        const platformCount = list.filter((item) => Number(item.merchant_id) === 0).length
        const merchantCount = Math.max(list.length - platformCount, 0)
        const onlineCount = list.filter((item) => Number(item.is_disable) === 0).length
        const offlineCount = Math.max(list.length - onlineCount, 0)
        return {
          changedCount: list.length,
          unchangedCount: 0,
          beforeLabel: `平台 ${platformCount} 件 / 商家 ${merchantCount} 件`,
          beforeDesc: `删除前商品状态分布：上架 ${onlineCount} 件 / 下架 ${offlineCount} 件。`,
          afterLabel: '已移出当前商品池',
          afterDesc: '提交后这些商品会从当前列表中删除，需通过备份或回收机制恢复。',
          summaryLabel: '删除摘要',
          summaryValue: `${list.length} 件已删除`,
          summary: `本次共删除 ${list.length} 件商品，请继续核对筛选结果和后续运营影响。`
        }
      }
      if (type === 'disable') {
        const targetDisable = Number(payload.is_disable)
        const changedCount = list.filter((item) => Number(item.is_disable) !== targetDisable).length
        const unchangedCount = Math.max(list.length - changedCount, 0)
        const onlineCount = list.filter((item) => Number(item.is_disable) === 0).length
        const offlineCount = Math.max(list.length - onlineCount, 0)
        return {
          changedCount,
          unchangedCount,
          beforeLabel: `上架 ${onlineCount} 件 / 下架 ${offlineCount} 件`,
          beforeDesc: '提交前会按当前所选范围统一调整商品上架状态。',
          afterLabel: targetDisable === 0 ? `上架 ${list.length} 件 / 下架 0 件` : `上架 0 件 / 下架 ${list.length} 件`,
          afterDesc: targetDisable === 0 ? '提交后统一切换为上架状态。' : '提交后统一切换为下架状态。',
          summaryLabel: '状态变化',
          summaryValue: `${changedCount} 件切换状态`,
          summary: unchangedCount
            ? `其中 ${changedCount} 件会真正变更状态，另有 ${unchangedCount} 件已处于目标状态。`
            : `本次 ${changedCount} 件商品都会发生状态变化。`
        }
      }
      if (type === 'thumbnail') {
        const targetImageId = Number(payload.image_id || 0)
        const changedCount = list.filter((item) => Number(item.image_id || 0) !== targetImageId).length
        const unchangedCount = Math.max(list.length - changedCount, 0)
        const withImageCount = list.filter((item) => Number(item.image_id || 0) > 0).length
        return {
          changedCount,
          unchangedCount,
          beforeLabel: `已有缩略图 ${withImageCount} 件 / 无图 ${Math.max(list.length - withImageCount, 0)} 件`,
          beforeDesc: '提交前保留商品原有标题、价格和归属，仅替换缩略图。',
          afterLabel: `统一缩略图 ${list.length} 件`,
          afterDesc: '提交后会按当前选择的目标缩略图批量覆盖。',
          summaryLabel: '图片变化',
          summaryValue: `${changedCount} 件更新缩略图`,
          summary: unchangedCount
            ? `其中 ${changedCount} 件会更新缩略图，另有 ${unchangedCount} 件原本已使用该图片。`
            : `本次 ${changedCount} 件商品都会更新缩略图。`
        }
      }
      if (type === 'label') {
        const targetLabelIds = Array.isArray(payload.goods_label_id)
          ? [...payload.goods_label_id].map((item) => Number(item)).sort((a, b) => a - b)
          : []
        const currentLabelKey = (labelIds) =>
          Array.isArray(labelIds) ? [...labelIds].map((item) => Number(item)).sort((a, b) => a - b).join(',') : ''
        const targetLabelKey = targetLabelIds.join(',')
        const changedCount = list.filter((item) => currentLabelKey(item.goods_label_id) !== targetLabelKey).length
        const unchangedCount = Math.max(list.length - changedCount, 0)
        const labeledCount = list.filter((item) => Array.isArray(item.goods_label_id) && item.goods_label_id.length).length
        const targetLabelText = targetLabelIds.length
          ? this.normalizeLabelTitle(targetLabelIds) || `保留 ${targetLabelIds.length} 个标签`
          : '清空全部标签'
        return {
          changedCount,
          unchangedCount,
          beforeLabel: `已有标签 ${labeledCount} 件 / 无标签 ${Math.max(list.length - labeledCount, 0)} 件`,
          beforeDesc: '提交前不会影响商品上下架状态和归属，只更新标签集合。',
          afterLabel: targetLabelText,
          afterDesc: targetLabelIds.length ? '提交后按当前所选标签统一覆盖。' : '提交后统一清空这些商品的标签。',
          summaryLabel: '标签变化',
          summaryValue: `${changedCount} 件更新标签`,
          summary: unchangedCount
            ? `其中 ${changedCount} 件会更新标签，另有 ${unchangedCount} 件标签结果本就一致。`
            : `本次 ${changedCount} 件商品都会更新标签结果。`
        }
      }
      return null
    },
    buildOperationRevert(row, targetMerchantId) {
      const list = Array.isArray(row) ? row : []
      if (!list.length) return { revertable: false }
      const sourceIds = [...new Set(list.map((item) => Number(item.merchant_id)))]
      if (sourceIds.length !== 1) return { revertable: false }
      const sourceMerchantId = sourceIds[0]
      if (sourceMerchantId === Number(targetMerchantId)) return { revertable: false }
      return {
        revertable: true,
        revertTargetMerchantId: sourceMerchantId,
        revertTargetLabel: this.resolveMerchantTitle(sourceMerchantId),
        revertLabel: `回退到${this.resolveMerchantTitle(sourceMerchantId)}`
      }
    },
    pushOperationFeedback(config = {}) {
      const operation = {
        key: `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
        label: config.label || '操作完成',
        tone: config.tone || 'success',
        count: Number(config.count || 0),
        target: config.target || '',
        time: this.formatOperationTime(),
        message: config.message || '已完成处理',
        ids: Array.isArray(config.ids) ? config.ids : [],
        filterable: Boolean(config.filterable),
        filterLabel: config.filterLabel || '',
        merchant_id: config.merchant_id,
        search_id: config.search_id || '',
        diff: config.diff || null,
        diffTitle: config.diffTitle || '',
        querySnapshot: config.querySnapshot || null,
        querySnapshotLabel: config.querySnapshot ? this.describeQuerySnapshot(config.querySnapshot) : '',
        revertable: Boolean(config.revertable),
        revertTargetMerchantId: config.revertTargetMerchantId,
        revertTargetLabel: config.revertTargetLabel || '',
        revertLabel: config.revertLabel || ''
      }
      this.writePersistedOperationWorkbenchExpanded(true)
      this.operationWorkbenchExpanded = true
      this.writePersistedOperationHistory([operation, ...this.operationHistory])
    },
    clearOperationHistory() {
      this.writePersistedOperationHistory([])
      ElMessage.success('最近操作记录已清空')
    },
    exportOperationSummary() {
      if (!this.operationHistory.length) {
        ElMessage.warning('暂无可导出的操作记录')
        return
      }
      const rows = this.operationHistory.map((item) => ({
        time: item.time,
        type: this.resolveOperationHistoryType(item),
        label: item.label,
        count: item.count,
        target: item.target || '-',
        firstId: item.search_id || '-',
        ids: Array.isArray(item.ids) && item.ids.length ? item.ids.join('|') : '-',
        changedCount: item.diff?.changedCount ?? '-',
        unchangedCount: item.diff?.unchangedCount ?? '-',
        diff: item.diff ? item.diff.summary : '-',
        message: item.message || '-'
      }))
      const header = ['操作时间', '分类', '操作类型', '处理数量', '目标归属', '首个商品ID', '商品ID列表', '变化数量', '未变化数量', '差异摘要', '结果说明']
      const csv = [
        header.join(','),
        ...rows.map((item) =>
          [
            item.time,
            item.type,
            item.label,
            item.count,
            item.target,
            item.firstId,
            item.ids,
            item.changedCount,
            item.unchangedCount,
            item.diff,
            item.message
          ]
            .map((value) => `"${String(value).replace(/"/g, '""')}"`)
            .join(',')
        )
      ].join('\n')

      const blob = new Blob([`\uFEFF${csv}`], { type: 'text/csv;charset=utf-8;' })
      const downloadUrl = URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = downloadUrl
      link.download = `goods_operation_summary_${Date.now()}.csv`
      link.target = '_self'
      link.rel = 'noopener'
      link.style.display = 'none'
      document.body.appendChild(link)
      link.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true, view: window }))
      window.setTimeout(() => {
        document.body.removeChild(link)
        URL.revokeObjectURL(downloadUrl)
      }, 1500)
      ElMessage.success('操作摘要已开始导出')
    },
    resolveOperationHistoryType(operation = {}) {
      const safeOperation = operation && typeof operation === 'object' ? operation : {}
      const label = String(safeOperation.label || '')
      if (label.includes('迁移') || label.includes('回退')) return 'transfer'
      if (label.includes('打标') || label.includes('换图')) return 'batch'
      if (label.includes('审核')) return 'audit'
      if (label.includes('上架') || label.includes('下架')) return 'disable'
      if (label.includes('删除')) return 'delete'
      return 'other'
    },
    async copyText(value, successMessage = '内容已复制') {
      const text = String(value || '').trim()
      if (!text) {
        ElMessage.warning('没有可复制的内容')
        return false
      }
      try {
        if (navigator?.clipboard?.writeText) {
          await navigator.clipboard.writeText(text)
        } else {
          const input = document.createElement('textarea')
          input.value = text
          input.setAttribute('readonly', 'readonly')
          input.style.position = 'fixed'
          input.style.top = '-9999px'
          document.body.appendChild(input)
          input.select()
          document.execCommand('copy')
          document.body.removeChild(input)
        }
        ElMessage.success(successMessage)
        return true
      } catch (error) {
        ElMessage.warning('复制失败，请重试')
        return false
      }
    },
    async copyOperationIds(operation) {
      const ids = Array.isArray(operation?.ids) ? operation.ids.filter((item) => Number(item) > 0) : []
      if (!ids.length) {
        ElMessage.warning('当前记录没有可复制的商品 ID')
        return
      }
      await this.copyText(ids.join(','), `已复制 ${ids.length} 个商品 ID`)
    },
    getOperationOverlapIds(operation = {}) {
      const selectionIds = this.selectGetIds(this.selection).map((item) => Number(item)).filter((item) => item > 0)
      const operationIds = Array.isArray(operation?.ids) ? operation.ids.map((item) => Number(item)).filter((item) => item > 0) : []
      if (!selectionIds.length || !operationIds.length) return []
      return selectionIds.filter((id) => operationIds.includes(id))
    },
    async copyOperationOverlapIds(operation = {}) {
      const ids = this.getOperationOverlapIds(operation)
      if (!ids.length) {
        ElMessage.warning('当前勾选与该批次没有重叠商品')
        return
      }
      await this.copyText(ids.join(','), `已复制 ${ids.length} 个重叠商品 ID`)
    },
    canReuseOperationTransfer(operation = {}) {
      const ids = Array.isArray(operation?.ids) ? operation.ids.filter((item) => Number(item) > 0) : []
      return this.resolveOperationHistoryType(operation) === 'transfer' && ids.length > 0
    },
    canStageOperationTransfer(operation = {}) {
      return this.canReuseOperationTransfer(operation)
    },
    stageTransferPayload(ids = [], { mode = 'platform', merchantId = undefined, successMessage = '已带入迁移工具' } = {}) {
      const normalizedIds = [...new Set(ids.map((item) => Number(item)).filter((item) => item > 0))]
      if (!normalizedIds.length) {
        ElMessage.warning('当前没有可带入迁移工具的商品')
        return false
      }
      this.transferScope = 'manual_ids'
      this.transferManualIdsText = normalizedIds.join(',')
      if (mode === 'merchant') {
        if (!Number(merchantId) || Number(merchantId) <= 0) {
          ElMessage.warning('请先明确目标商家后再带入迁移工具')
          return false
        }
        this.bridgeMode = 'merchant'
        this.target_merchant_id = Number(merchantId)
      } else {
        this.bridgeMode = 'platform'
        this.target_merchant_id = undefined
      }
      this.persistStagedBridgeSnapshot({
        bridgeMode: this.bridgeMode,
        transferManualIdsText: normalizedIds.join(','),
        target_merchant_id: this.target_merchant_id,
        sourceLabel: successMessage
      })
      ElMessage.success(successMessage)
      return true
    },
    stageOperationTransfer(operation = {}) {
      if (!this.canStageOperationTransfer(operation)) {
        ElMessage.warning('该条记录暂不支持带入迁移工具')
        return false
      }
      return this.stageTransferPayload(operation.ids, {
        mode: Number(operation.merchant_id) > 0 ? 'merchant' : 'platform',
        merchantId: operation.merchant_id,
        successMessage: `已带入 ${operation.ids.length} 件商品到迁移工具，可继续调整后再提交`
      })
    },
    stageQuickBatch(operation = {}) {
      if (this.canStageOperationTransfer(operation)) {
        this.stageOperationTransfer(operation)
        return
      }
      if (operation?.querySnapshot) {
        this.restoreOperationFilters(operation)
        return
      }
      if (Array.isArray(operation?.ids) && operation.ids.length) {
        this.copyOperationIds(operation)
      }
    },
    stageOperationOverlap(operation = {}) {
      const overlapIds = this.getOperationOverlapIds(operation)
      if (!overlapIds.length) {
        ElMessage.warning('当前勾选与该批次没有重叠商品')
        return false
      }
      if (!this.canStageOperationTransfer(operation)) {
        ElMessage.warning('该批次暂不支持承接到迁移工具')
        return false
      }
      return this.stageTransferPayload(overlapIds, {
        mode: Number(operation.merchant_id) > 0 ? 'merchant' : 'platform',
        merchantId: operation.merchant_id,
        successMessage: `已承接 ${overlapIds.length} 件重叠商品到迁移工具`
      })
    },
    reuseOperationTransfer(operation = {}) {
      if (!this.stageOperationTransfer(operation)) {
        return
      }
      if (Number(operation.merchant_id) > 0) {
        this.selectOpen('transfer_merchant')
        return
      }
      this.selectOpen('transfer_platform')
    },
    useFirstSelectionThumbnail() {
      if (!this.batchTargetRows.length) return
      this.batch_image_id = Number(this.batchTargetRows[0]?.image_id || 0)
      this.batch_image_url = this.batchTargetRows[0]?.image_url || ''
      if (!this.batch_image_id) {
        ElMessage.warning('首个商品当前没有可复用的缩略图')
        return
      }
      ElMessage.success('已带入首个商品当前缩略图')
    },
    quickDisableSelected(nextValue) {
      if (!this.data.length) {
        return
      }
      this.is_disable = nextValue
      this.selectOpen('disable')
    },
    async clearFilters() {
      await this.refresh()
    },
    async applyMerchantPick(merchantId) {
      this.query.page = 1
      this.query.merchant_id = merchantId
      this.activeQuickFilter = 'all'
      await this.syncRouteQuery()
      this.list()
    },
    async applyCurrentBridgeTargetFilter() {
      if (!this.canApplyCurrentBridgeTargetFilter) {
        ElMessage.warning('当前承接状态还不能直接按目标归属筛选')
        return
      }
      if (this.bridgeMode === 'platform') {
        await this.applyOperationMerchantFilter({ merchant_id: 0 })
        return
      }
      await this.applyOperationMerchantFilter({ merchant_id: Number(this.target_merchant_id) })
    },
    activateMerchantBridgeTarget(merchantId) {
      this.bridgeMode = 'merchant'
      this.applyTransferTargetMerchant(merchantId)
    },
    activateBridgeTarget(target) {
      if (target?.type === 'platform') {
        this.bridgeMode = 'platform'
        this.target_merchant_id = ''
        return
      }
      if (target?.type === 'merchant') {
        this.activateMerchantBridgeTarget(target.id)
      }
    },
    applyTransferTargetMerchant(merchantId) {
      this.target_merchant_id = Number(merchantId)
    },
    async handlePagination() {
      await this.syncRouteQuery()
      this.list()
    },
    selectCurrentPage() {
      if (!this.data.length || !this.$refs.table) return
      this.$refs.table.clearSelection()
      this.data.forEach((item) => {
        this.$refs.table.toggleRowSelection(item, true)
      })
    },
    openDetail(row) {
      this.detailDrawer = true
      this.detailLoading = true
      this.detailModel = this.buildDetailModel(row)
      info({ id: row.id })
        .then((res) => {
          this.detailModel = this.buildDetailModel({
            ...row,
            ...(res.data || {})
          })
        })
        .catch(() => {
          ElMessage.warning('商品详情拉取失败，已展示列表快照')
        })
        .finally(() => {
          this.detailLoading = false
        })
    },
    toggleRowDisable(row) {
      const nextValue = Number(row.is_disable) === 0 ? 1 : 0
      const nextLabel = nextValue === 0 ? '上架' : '停用'
      ElMessageBox.confirm(`确认${nextLabel}商品「${row.title}」吗？`, '提示', {
        type: 'warning'
      })
        .then(() => {
          this.disable([{ ...row, is_disable: nextValue }])
        })
        .catch(() => {})
    },
    async applyCombinedFilter(item) {
      const nextQuery = { ...item.query }
      if (nextQuery.merchant_id === '__merchant__') {
        const merchantCandidate = this.merchantOptions[0]
        nextQuery.merchant_id = merchantCandidate ? merchantCandidate.id : -1
      }
      this.activeQuickFilter = ''
      this.query.page = 1
      Object.keys(nextQuery).forEach((key) => {
        this.query[key] = nextQuery[key]
      })
      await this.syncRouteQuery()
      this.list()
    },
    async applyOperationMerchantFilter(operation) {
      if (operation.merchant_id === undefined || operation.merchant_id === null) return
      this.query.page = 1
      this.query.merchant_id = operation.merchant_id
      this.activeQuickFilter = ''
      await this.syncRouteQuery()
      this.list()
    },
    async restoreOperationFilters(operation) {
      if (!operation.querySnapshot) return
      const snapshot = operation.querySnapshot
      this.query.page = 1
      this.query.status = snapshot.status
      this.query.is_disable = snapshot.is_disable
      this.query.goods_type_id = snapshot.goods_type_id
      this.query.goods_label_id = snapshot.goods_label_id
      this.query.merchant_id = snapshot.merchant_id
      this.query.search_field = snapshot.search_field || 'title'
      this.query.search_exp = snapshot.search_exp || 'like'
      this.query.search_value = snapshot.search_value || ''
      this.query.date_value = Array.isArray(snapshot.date_value) ? [...snapshot.date_value] : []
      this.activeQuickFilter = snapshot.activeQuickFilter || 'all'
      await this.syncRouteQuery()
      this.list()
      ElMessage.success('已恢复到该次操作前的筛选条件')
    },
    revertOperation(operation) {
      if (!operation.revertable || !Array.isArray(operation.ids) || !operation.ids.length) return
      ElMessageBox.confirm(`确定将这次操作涉及的商品回退到 ${operation.revertTargetLabel} 吗？`, '回退确认', {
        type: 'warning'
      }).then(() => {
        this.loading = true
        const querySnapshot = this.captureQuerySnapshot()
        const action = Number(operation.revertTargetMerchantId) === 0
          ? transferToPlatform({ ids: operation.ids })
          : transferToMerchant({ ids: operation.ids, target_merchant_id: operation.revertTargetMerchantId })
        action.then((res) => {
          this.list()
          this.pushOperationFeedback({
            label: '回退迁移',
            tone: 'warning',
            count: operation.ids.length,
            target: operation.revertTargetLabel,
            message: res.msg,
            ids: operation.ids,
            filterable: true,
            filterLabel: Number(operation.revertTargetMerchantId) === 0 ? '筛选平台自营' : '筛选回退商家',
            merchant_id: operation.revertTargetMerchantId,
            search_id: operation.search_id || operation.ids[0] || '',
            querySnapshot
          })
          ElMessage.success(res.msg)
        }).catch(() => {
          this.loading = false
        })
      }).catch(() => {})
    },
    async focusOperationFirstGoods(operation) {
      if (!operation.search_id) return
      this.query.page = 1
      this.query.search_field = this.idkey
      this.query.search_exp = '='
      this.query.search_value = String(operation.search_id)
      await this.syncRouteQuery()
      this.list()
    },
    getCode() {
      if (!this.model.id) {
        code({ goods_type_id: this.model.goods_type_id }).then((res) => {
          this.model.code = res.data
        })
      }
    },
    getParams() {
      this.loading = true
      params({})
        .then((res) => {
          this.params = res.data
          this.syncTransferDraftState()
          this.loading = false
        })
        .catch(() => {
          this.loading = false
        })
    },
    list() {
      this.loading = true
      list(this.query)
        .then((res) => {
          this.data = res.data.list
          this.count = res.data.count
          this.status_nums = res.data.statistics || {}
          this.loading = false
          if (this.pendingEntryAnchorAlign) {
            this.pendingEntryAnchorAlign = false
            this.scheduleEnterPageScrollReset()
          }
        })
        .catch(() => {
          this.loading = false
          if (this.pendingEntryAnchorAlign) {
            this.pendingEntryAnchorAlign = false
            this.scheduleEnterPageScrollReset()
          }
        })
    },
    add() {
      this.dialog = true
      this.dialogTitle = this.name + '添加'
      this.reset()
    },
    edit(row) {
      this.dialog = true
      this.dialogTitle = this.name + '修改：' + row[this.idkey]
      const id = {}
      id[this.idkey] = row[this.idkey]
      info(id).then((res) => {
        this.reset(res.data)
      })
    },
    cancel() {
      this.dialog = false
      this.reset()
    },
    submit() {
      this.$refs.ref.validate((valid) => {
        if (!valid) {
          ElMessage.error('请完善必填项（带红色星号*）')
          return
        }
        const action = this.model[this.idkey] ? edit(this.model) : add(this.model)
        action
          .then((res) => {
            this.list()
            this.dialog = false
            ElMessage.success(res.msg)
          })
      })
    },
    reset(row) {
      this.model = row ? row : this.$options.data().model
      if (this.$refs.ref !== undefined) {
        try {
          this.$refs.ref.resetFields()
          this.$refs.ref.clearValidate()
        } catch (error) {}
      }
    },
    async search() {
      this.query.page = 1
      await this.syncRouteQuery()
      this.list()
    },
    async refresh() {
      const limit = this.query.limit
      this.query = this.$options.data().query
      this.query.limit = limit
      this.activeQuickFilter = 'all'
      this.selection = []
      this.selectIds = ''
      if (this.$refs.table) {
        this.$refs.table.clearSelection()
        this.$refs.table.clearSort()
      }
      await this.syncRouteQuery()
      this.list()
    },
    async sort(sort) {
      this.query.sort_field = sort.prop
      this.query.sort_value = sort.order === 'ascending' ? 'asc' : sort.order === 'descending' ? 'desc' : ''
      await this.syncRouteQuery()
      this.list()
    },
    select(selection) {
      this.selection = selection
      this.selectIds = this.selectGetIds(selection).toString()
    },
    selectGetIds(selection) {
      return arrayColumn(selection, this.idkey)
    },
    clearSelection() {
      this.selection = []
      this.selectIds = ''
      if (this.$refs.table) {
        this.$refs.table.clearSelection()
      }
    },
    selectAlert() {
      ElMessageBox.alert('请选择需要操作的' + this.name, '提示', { type: 'warning' })
    },
    async applyQuickFilter(item) {
      this.activeQuickFilter = item.key
      this.query.page = 1
      Object.keys(item.query).forEach((key) => {
        this.query[key] = item.query[key]
      })
      await this.syncRouteQuery()
      this.list()
    },
    toggleOperationWorkbench() {
      this.operationWorkbenchExpanded = !this.operationWorkbenchExpanded
      this.writePersistedOperationWorkbenchExpanded()
    },
    openTransferMerchantDialog(selectRow = '') {
      this.bridgeMode = 'merchant'
      this.selectOpen('transfer_merchant', selectRow)
    },
    async copyRowGoodsId(row = {}) {
      const id = Number(row?.[this.idkey] || row?.id || 0)
      if (!id) {
        ElMessage.warning('当前商品没有可复制的 ID')
        return
      }
      await this.copyText(String(id), `已复制商品 ID：${id}`)
    },
    async filterRowOwnership(row = {}) {
      const merchantId = Number(row?.merchant_id)
      if (!Number.isInteger(merchantId) || merchantId < 0) {
        ElMessage.warning('当前商品缺少归属信息')
        return
      }
      await this.applyOperationMerchantFilter({ merchant_id: merchantId })
      ElMessage.success(`已按 ${this.resolveMerchantTitle(merchantId)} 过滤商品`)
    },
    async searchRowByCode(row = {}) {
      const code = String(row?.code || '').trim()
      if (!code) {
        ElMessage.warning('当前商品没有可检索的编码')
        return
      }
      this.query.page = 1
      this.query.search_field = 'code'
      this.query.search_exp = '='
      this.query.search_value = code
      await this.syncRouteQuery()
      this.list()
      ElMessage.success(`已按商品编码 ${code} 回查`)
    },
    reviewSelectedPendingGoods() {
      const rows = this.selection.filter((item) => Number(item.status) === 0)
      if (!rows.length) {
        ElMessage.warning('当前勾选里没有待审核商品')
        return
      }
      this.selectOpen('auth', rows)
    },
    bringSelectedOfflineGoodsOnline() {
      const rows = this.selection.filter((item) => Number(item.is_disable) === 1)
      if (!rows.length) {
        ElMessage.warning('当前勾选里没有下架商品')
        return
      }
      ElMessageBox.confirm(
        `确认将当前勾选中的 ${rows.length} 件下架商品统一上架吗？`,
        '批量上架确认',
        { type: 'warning' }
      )
        .then(() => {
          this.is_disable = 0
          this.disable(rows, true)
        })
        .catch(() => {})
    },
    async copySelectionLatestOperationOverlapIds() {
      const ids = this.selectionLatestOperationLink.overlapIds || []
      await this.copyText(ids.join(','), `已复制 ${ids.length} 个重叠商品 ID`)
    },
    stageSelectionLatestOperationOverlap() {
      if (!this.latestOperation) {
        ElMessage.warning('当前没有可承接的最近操作')
        return
      }
      this.stageOperationOverlap(this.latestOperation)
    },
    keepSelectionLatestPendingOverlapIds() {
      const ids = this.selectionLatestOperationLink.pendingOverlapIds || []
      if (!ids.length) {
        ElMessage.warning('当前勾选里没有与最近操作重叠且待复核的商品')
        return
      }
      if (this.canStageOperationTransfer(this.latestOperation)) {
        this.stageTransferPayload(ids, {
          mode: Number(this.latestOperation?.merchant_id) > 0 ? 'merchant' : 'platform',
          merchantId: this.latestOperation?.merchant_id,
          successMessage: `已将 ${ids.length} 个重叠待复核商品承接到迁移工具`
        })
        return
      }
      this.transferScope = 'manual_ids'
      this.transferManualIdsText = ids.join(',')
      ElMessage.success(`已仅保留 ${ids.length} 个重叠待复核商品，可继续精确复核`)
    },
    canQuickTransferRowToRememberedMerchant(row = {}) {
      if (!this.checkPermission(['admin/goods.Goods/transferToMerchant'])) return false
      if (!Number(this.target_merchant_id) || !this.targetMerchantLabel) return false
      return Number(row.merchant_id) !== Number(this.target_merchant_id)
    },
    quickTransferRowToRememberedMerchant(row) {
      if (!this.target_merchant_id || !this.targetMerchantLabel) {
        ElMessage.warning('请先在迁移工具里选择目标商家')
        return
      }
      ElMessageBox.confirm(
        `确认将商品「${row.title}」直接迁移到已记忆目标 ${this.targetMerchantLabel} 吗？`,
        '快捷迁移确认',
        { type: 'warning' }
      )
        .then(() => {
          this.bridgeMode = 'merchant'
          this.handleTransferToMerchant([row], [Number(row[this.idkey])])
        })
        .catch(() => {})
    },
    stageRowForCurrentBridge(row) {
      const id = Number(row?.[this.idkey] || row?.id || 0)
      if (!id) {
        ElMessage.warning('当前商品无法带入迁移工具')
        return
      }
      if (this.bridgeMode === 'merchant') {
        if (!this.target_merchant_id || !this.targetMerchantLabel) {
          ElMessage.warning('请先在迁移工具里选择目标商家')
          return
        }
        this.stageTransferPayload([id], {
          mode: 'merchant',
          merchantId: this.target_merchant_id,
          successMessage: `已将商品「${row.title || id}」带入当前商家迁移工具`
        })
        return
      }
      this.stageTransferPayload([id], {
        mode: 'platform',
        successMessage: `已将商品「${row.title || id}」带入当前平台迁移工具`
      })
    },
    buildRowActionHints(row = {}) {
      const hints = []
      if (Number(row.status) === 0) {
        hints.push({ label: '待审核', type: 'warning' })
      }
      if (Number(row.is_disable) === 1) {
        hints.push({ label: '当前下架', type: 'info' })
      }
      if (Number(row.merchant_id) > 0) {
        hints.push({ label: `商家货：${row.merchant_title || this.resolveMerchantTitle(row.merchant_id)}`, type: 'success' })
      } else {
        hints.push({ label: '平台自营', type: 'primary' })
      }
      if (this.bridgeMode === 'merchant' && Number(this.target_merchant_id) > 0) {
        if (Number(row.merchant_id) === Number(this.target_merchant_id)) {
          hints.push({ label: '已在当前目标商家', type: 'danger' })
        } else {
          hints.push({ label: `当前工具目标：${this.targetMerchantLabel || this.resolveMerchantTitle(this.target_merchant_id)}`, type: 'warning' })
        }
      } else if (this.bridgeMode === 'platform' && Number(row.merchant_id) === 0) {
        hints.push({ label: '已在平台目标', type: 'danger' })
      }
      return hints.slice(0, 4)
    },
    parseGoodsIds(value) {
      if (!value) return []
      return [...new Set(String(value)
        .split(/[\s,，]+/)
        .map((item) => Number(item.trim()))
        .filter((item) => Number.isInteger(item) && item > 0))]
    },
    parseGoodsIdMeta(value) {
      if (!value) {
        return { ids: [], duplicateCount: 0, invalidTokens: [] }
      }
      const rawTokens = String(value)
        .split(/[\s,，]+/)
        .map((item) => item.trim())
        .filter(Boolean)
      const ids = []
      const seen = new Set()
      let duplicateCount = 0
      const invalidTokens = []
      rawTokens.forEach((token) => {
        const num = Number(token)
        if (!Number.isInteger(num) || num <= 0) {
          invalidTokens.push(token)
          return
        }
        if (seen.has(num)) {
          duplicateCount += 1
          return
        }
        seen.add(num)
        ids.push(num)
      })
      return { ids, duplicateCount, invalidTokens }
    },
    collectGoodsRowsByIds(ids) {
      if (!Array.isArray(ids) || !ids.length) return []
      const source = [...this.selection, ...this.data]
      const rowMap = new Map(source.map((item) => [Number(item[this.idkey]), item]))
      return ids.map((id) => rowMap.get(Number(id))).filter(Boolean)
    },
    fillTransferManualIds(source) {
      const ids = source === 'current_page' ? this.selectGetIds(this.data) : this.selectGetIds(this.selection)
      this.transferManualIdsText = ids.join(',')
    },
    keepResolvedTransferManualIds() {
      this.transferManualIdsText = this.transferManualMatchedIds.join(',')
    },
    clearTransferManualIds() {
      this.transferManualIdsText = ''
    },
    clearBridgeStaging() {
      this.rememberCurrentBridgeSnapshot('清空前承接批次')
      this.transferScope = this.selection.length ? 'selected' : 'current_page'
      this.clearTransferManualIds()
      ElMessage.success('已清空当前承接批次，批量迁移工具已恢复默认处理范围')
    },
    applyIdsToCurrentBridge(ids = [], successMessage = '已更新当前承接批次') {
      const normalizedIds = [...new Set(ids.map((item) => Number(item)).filter((item) => item > 0))]
      if (!normalizedIds.length) {
        ElMessage.warning('当前没有可承接的商品 ID')
        return false
      }
      if (this.bridgeMode === 'merchant') {
        if (!this.target_merchant_id || !this.targetMerchantLabel) {
          ElMessage.warning('请先在迁移工具里选择目标商家')
          return false
        }
        return this.stageTransferPayload(normalizedIds, {
          mode: 'merchant',
          merchantId: this.target_merchant_id,
          successMessage
        })
      }
      return this.stageTransferPayload(normalizedIds, {
        mode: 'platform',
        successMessage
      })
    },
    mergeSelectionIntoStagedBridge({ onlyDelta = false } = {}) {
      if (!this.selection.length) {
        ElMessage.warning('请先勾选需要并入的商品')
        return false
      }
      const selectionIds = this.selectGetIds(this.selection)
      const stagedIds = this.transferScope === 'manual_ids' ? this.transferManualIds : []
      const stagedSet = new Set(stagedIds.map((item) => Number(item)).filter((item) => item > 0))
      const selectionOnlyIds = selectionIds.filter((id) => !stagedSet.has(Number(id)))
      const nextIds = onlyDelta
        ? [...stagedIds, ...selectionOnlyIds]
        : [...stagedIds, ...selectionIds]
      if (onlyDelta && !selectionOnlyIds.length) {
        ElMessage.warning('当前勾选没有新增商品可追加')
        return false
      }
      const targetCount = [...new Set(nextIds.map((item) => Number(item)).filter((item) => item > 0))].length
      return this.applyIdsToCurrentBridge(nextIds, onlyDelta
        ? `已把 ${selectionOnlyIds.length} 件新增勾选并入承接批次，当前合计 ${targetCount} 件`
        : `已将勾选商品与承接批次合并，当前合计 ${targetCount} 件`)
    },
    async copyBridgeSelectionDeltaIds() {
      const ids = this.bridgeStageMergePanel.selectionOnlyIds || []
      if (!ids.length) {
        ElMessage.warning('当前没有新增勾选商品可复制')
        return
      }
      await this.copyText(ids.join(','), `已复制 ${ids.length} 个新增商品 ID`)
    },
    async copyTransferRecentOverlapIds() {
      const ids = this.transferRecentComparison.overlapIds || []
      if (!ids.length) {
        ElMessage.warning('当前批次与最近迁移没有重叠商品')
        return
      }
      await this.copyText(ids.join(','), `已复制 ${ids.length} 个与最近迁移重叠的商品 ID`)
    },
    async copyTransferRecentDeltaIds() {
      const ids = this.transferRecentComparison.deltaIds || []
      if (!ids.length) {
        ElMessage.warning('当前批次没有相对最近迁移的新增商品')
        return
      }
      await this.copyText(ids.join(','), `已复制 ${ids.length} 个相对最近迁移新增的商品 ID`)
    },
    keepTransferRecentDeltaIds() {
      const ids = this.transferRecentComparison.deltaIds || []
      if (!ids.length) {
        ElMessage.warning('当前批次没有可保留的新增商品')
        return
      }
      this.transferScope = 'manual_ids'
      this.transferManualIdsText = ids.join(',')
      ElMessage.success(`已仅保留 ${ids.length} 个相对最近迁移的新增商品`)
    },
    keepTransferRecentOverlapIds() {
      const ids = this.transferRecentComparison.overlapIds || []
      if (!ids.length) {
        ElMessage.warning('当前批次没有可复核的重叠商品')
        return
      }
      this.transferScope = 'manual_ids'
      this.transferManualIdsText = ids.join(',')
      ElMessage.success(`已切换为复核 ${ids.length} 个与最近迁移重叠的商品`)
    },
    loadLatestTransferIntoCurrentTarget() {
      const ids = Array.isArray(this.latestTransferOperation?.ids)
        ? [...new Set(this.latestTransferOperation.ids.map((item) => Number(item)).filter((item) => item > 0))]
        : []
      if (!ids.length) {
        ElMessage.warning('最近迁移批次没有可装入的商品')
        return
      }
      this.transferScope = 'manual_ids'
      this.transferManualIdsText = ids.join(',')
      const targetLabel = this.selectType === 'transfer_platform'
        ? '当前平台目标'
        : (this.targetMerchantLabel || '当前商家目标')
      ElMessage.success(`已把最近迁移的 ${ids.length} 件商品装入 ${targetLabel}，可直接复核或改目标后继续提交`)
    },
    loadLatestTransferUnchangedIds() {
      const ids = Array.isArray(this.latestTransferOperation?.diff?.unchangedIds)
        ? [...new Set(this.latestTransferOperation.diff.unchangedIds.map((item) => Number(item)).filter((item) => item > 0))]
        : []
      if (!ids.length) {
        ElMessage.warning('最近迁移没有可单独复核的待复核商品')
        return
      }
      this.transferScope = 'manual_ids'
      this.transferManualIdsText = ids.join(',')
      ElMessage.success(`已装入 ${ids.length} 个最近迁移中未发生归属变化的商品，可直接重点复核`)
    },
    loadLatestTransferChangedIds() {
      const ids = Array.isArray(this.latestTransferOperation?.diff?.changedIds)
        ? [...new Set(this.latestTransferOperation.diff.changedIds.map((item) => Number(item)).filter((item) => item > 0))]
        : []
      if (!ids.length) {
        ElMessage.warning('最近迁移没有可单独承接的已变化商品')
        return
      }
      this.transferScope = 'manual_ids'
      this.transferManualIdsText = ids.join(',')
      ElMessage.success(`已装入 ${ids.length} 个最近迁移中已发生归属变化的商品，可继续做增量处理`)
    },
    setLatestTransferExceptionFilter(key = 'unchanged') {
      this.latestTransferExceptionFilter = key
    },
    resolveLatestTransferExceptionSource(key = 'unchanged') {
      return this.latestTransferExceptionTracker.sources.find((item) => item.key === key) || null
    },
    async copyLatestTransferExceptionSourceIds(key = 'unchanged') {
      const source = this.resolveLatestTransferExceptionSource(key)
      const ids = Array.isArray(source?.ids) ? [...new Set(source.ids.map((item) => Number(item)).filter((item) => item > 0))] : []
      if (!ids.length) {
        ElMessage.warning('当前来源没有可复制的商品 ID')
        return false
      }
      await this.copyText(ids.join(','), `已复制${source.label}来源的 ${ids.length} 个商品 ID`)
      return true
    },
    trackLatestTransferExceptionSource(key = 'unchanged') {
      if (key === 'bulk') {
        return this.trackLatestTransferExceptionBatch('bulk')
      }
      if (key === 'duplicate') {
        return this.trackLatestTransferExceptionBatch('duplicate')
      }
      return this.trackLatestTransferExceptionBatch(key)
    },
    trackLatestTransferExceptionBatch(type = 'unchanged') {
      const operation = this.latestTransferOperation
      if (!operation) {
        ElMessage.warning('当前没有可追踪的最近迁移批次')
        return false
      }
      let ids = []
      let label = ''
      if (type === 'changed') {
        ids = Array.isArray(operation.diff?.changedIds)
          ? operation.diff.changedIds.map((item) => Number(item)).filter((item) => item > 0)
          : []
        label = '已变化批次'
      } else if (type === 'bulk') {
        ids = Array.isArray(operation.ids)
          ? operation.ids.map((item) => Number(item)).filter((item) => item > 0)
          : []
        label = '大批量批次'
      } else if (type === 'duplicate') {
        ids = Array.isArray(operation.diff?.unchangedIds)
          ? operation.diff.unchangedIds.map((item) => Number(item)).filter((item) => item > 0)
          : []
        label = '重复目标批次'
      } else {
        ids = Array.isArray(operation.diff?.unchangedIds)
          ? operation.diff.unchangedIds.map((item) => Number(item)).filter((item) => item > 0)
          : []
        label = '未变化批次'
      }
      const normalizedIds = [...new Set(ids)]
      if (!normalizedIds.length) {
        ElMessage.warning(`最近迁移没有可追踪的${label}`)
        return false
      }
      return this.stageTransferPayload(normalizedIds, {
        mode: Number(operation.merchant_id) > 0 ? 'merchant' : 'platform',
        merchantId: operation.merchant_id,
        successMessage: `已承接最近迁移的${label}，共 ${normalizedIds.length} 件，可继续追踪处理`
      })
    },
    async copyLatestTransferUnchangedIds() {
      const ids = Array.isArray(this.latestTransferOperation?.diff?.unchangedIds)
        ? [...new Set(this.latestTransferOperation.diff.unchangedIds.map((item) => Number(item)).filter((item) => item > 0))]
        : []
      if (!ids.length) {
        ElMessage.warning('最近迁移没有可复制的待复核商品 ID')
        return
      }
      await this.copyText(ids.join(','), `已复制 ${ids.length} 个待复核商品 ID`)
    },
    async copyLatestTransferChangedIds() {
      const ids = Array.isArray(this.latestTransferOperation?.diff?.changedIds)
        ? [...new Set(this.latestTransferOperation.diff.changedIds.map((item) => Number(item)).filter((item) => item > 0))]
        : []
      if (!ids.length) {
        ElMessage.warning('最近迁移没有可复制的已变化商品 ID')
        return
      }
      await this.copyText(ids.join(','), `已复制 ${ids.length} 个已变化商品 ID`)
    },
    loadLatestTransferPendingOverlapIds() {
      const currentIds = [...new Set(this.transferTargetIds.map((item) => Number(item)).filter((item) => item > 0))]
      const pendingSet = new Set(
        Array.isArray(this.latestTransferOperation?.diff?.unchangedIds)
          ? this.latestTransferOperation.diff.unchangedIds.map((item) => Number(item)).filter((item) => item > 0)
          : []
      )
      const ids = currentIds.filter((id) => pendingSet.has(id))
      if (!ids.length) {
        ElMessage.warning('当前批次没有与最近迁移重叠且待复核的商品')
        return
      }
      this.transferScope = 'manual_ids'
      this.transferManualIdsText = ids.join(',')
      ElMessage.success(`已仅保留 ${ids.length} 个重叠待复核商品，便于逐步回退后继续处理`)
    },
    async copyCurrentTransferTargetIds() {
      const ids = [...new Set(this.transferTargetIds.map((item) => Number(item)).filter((item) => item > 0))]
      if (!ids.length) {
        ElMessage.warning('当前没有可复制的复核商品 ID')
        return
      }
      await this.copyText(ids.join(','), `已复制 ${ids.length} 个当前复核商品 ID`)
    },
    stageSelectionForCurrentBridge() {
      if (!this.selection.length) {
        ElMessage.warning('请先勾选需要承接的商品')
        return
      }
      if (this.bridgeMode === 'merchant') {
        if (!this.target_merchant_id || !this.targetMerchantLabel) {
          ElMessage.warning('请先在迁移工具里选择目标商家')
          return
        }
        this.stageTransferPayload(this.selectGetIds(this.selection), {
          mode: 'merchant',
          merchantId: this.target_merchant_id,
          successMessage: `已将 ${this.selection.length} 件勾选商品带入 ${this.targetMerchantLabel} 迁移工具`
        })
        return
      }
      this.stageTransferPayload(this.selectGetIds(this.selection), {
        mode: 'platform',
        successMessage: `已将 ${this.selection.length} 件勾选商品带入平台迁移工具`
      })
    },
    stageSelectionForRememberedMerchant() {
      if (!this.selection.length) {
        ElMessage.warning('请先勾选需要承接的商品')
        return
      }
      if (!this.target_merchant_id || !this.targetMerchantLabel) {
        ElMessage.warning('当前没有可用的已记忆目标商家')
        return
      }
      this.stageTransferPayload(this.selectGetIds(this.selection), {
        mode: 'merchant',
        merchantId: this.target_merchant_id,
        successMessage: `已将 ${this.selection.length} 件勾选商品带入已记忆目标 ${this.targetMerchantLabel}`
      })
    },
    async resolveTransferRows(ids) {
      const rowMap = new Map(this.collectGoodsRowsByIds(ids).map((item) => [Number(item[this.idkey]), item]))
      const missingIds = ids.filter((id) => !rowMap.has(Number(id)))
      if (missingIds.length) {
        const results = await Promise.all(missingIds.map((id) => info({ [this.idkey]: id }).catch(() => null)))
        results.forEach((res) => {
          const row = res?.data
          if (row && Number(row[this.idkey]) > 0) {
            rowMap.set(Number(row[this.idkey]), row)
          }
        })
      }
      return ids.map((id) => rowMap.get(Number(id))).filter(Boolean)
    },
    submitBridgeAction() {
      if (this.bridgeMode === 'merchant') {
        this.openTransferMerchantDialog()
      } else {
        this.selectOpen('transfer_platform')
      }
    },
    selectOpen(selectType, selectRow = '') {
      if (selectRow) {
        this.$refs.table.clearSelection()
        selectRow.forEach((item) => {
          this.$refs.table.toggleRowSelection(item, true)
        })
      }
      const allowCurrentPage = [...this.batchScopedSelectTypes, 'transfer_platform', 'transfer_merchant'].includes(selectType)
      if (!this.selection.length && (!allowCurrentPage || !this.data.length)) {
        this.selectAlert()
        return
      }
      this.selectTitle = '操作'
      if (selectType === 'disable') this.selectTitle = this.name + '上架/下架'
      if (selectType === 'dele') this.selectTitle = this.name + '删除'
      if (selectType === 'auth') this.selectTitle = this.name + '审核'
      if (selectType === 'batch_label') this.selectTitle = this.name + '批量打标'
      if (selectType === 'batch_thumbnail') this.selectTitle = this.name + '批量换图'
      if (selectType === 'transfer_platform') this.selectTitle = this.name + '转平台'
      if (selectType === 'transfer_merchant') this.selectTitle = this.name + '转商家'
      if (this.batchScopedSelectTypes.includes(selectType)) {
        this.batchScope = Array.isArray(selectRow) && selectRow.length ? 'selected' : (this.selection.length ? 'selected' : 'current_page')
      }
      if (selectType === 'transfer_platform' || selectType === 'transfer_merchant') {
        this.transferScope = Array.isArray(selectRow) && selectRow.length ? 'selected' : (this.selection.length ? 'selected' : 'current_page')
        this.transferManualIdsText = ''
      }
      if (selectType === 'transfer_merchant' && !this.target_merchant_id) {
        this.target_merchant_id = undefined
      }
      if (selectType === 'batch_thumbnail') {
        this.batch_image_id = 0
        this.batch_image_url = ''
      }
      if (selectType === 'batch_label') {
        this.batch_goods_label_id = Array.isArray(selectRow) && selectRow.length === 1 && Array.isArray(selectRow[0]?.goods_label_id)
          ? [...selectRow[0].goods_label_id]
          : []
      }
      this.selectType = selectType
      this.selectDialog = true
    },
    selectCancel() {
      if (this.loading) return
      this.selectDialog = false
    },
    handleSelectDialogBeforeClose(done) {
      if (this.loading) return
      done()
    },
    async selectSubmit() {
      if (this.loading) return
      if (!this.selection.length) {
        if (!this.batchScopedSelectTypes.includes(this.selectType) && this.selectType !== 'transfer_platform' && this.selectType !== 'transfer_merchant') {
          this.selectAlert()
          return
        }
      }
      if (this.selectType === 'transfer_merchant' && !this.target_merchant_id) {
        ElMessage.warning('请选择目标商家')
        return
      }
      if (this.selectType === 'batch_thumbnail' && !this.batch_image_id) {
        ElMessage.warning('请选择目标缩略图')
        return
      }
      let transferRows = null
      let transferIds = null
      if (this.selectType === 'transfer_platform' || this.selectType === 'transfer_merchant') {
        transferIds = [...this.transferTargetIds]
        if (!transferIds.length) {
          ElMessage.warning('请先选择迁移对象或输入商品 ID')
          return
        }
        transferRows = await this.resolveTransferRows(transferIds)
        if (!transferRows.length) {
          ElMessage.warning('未能匹配到可迁移的商品，请核对输入的商品 ID')
          return
        }
        if (transferRows.length !== transferIds.length) {
          const resolvedIds = new Set(transferRows.map((item) => Number(item[this.idkey])))
          const missingIds = transferIds.filter((id) => !resolvedIds.has(Number(id)))
          ElMessage.warning(`以下商品 ID 未找到：${missingIds.join('，')}`)
          return
        }
      }
      this.selectDialog = false
      if (this.selectType === 'disable') this.disable(this.batchTargetRows, true)
      if (this.selectType === 'dele') this.handleDelete(this.selection)
      if (this.selectType === 'auth') this.handleAuth(this.selection)
      if (this.selectType === 'batch_label') this.handleBatchLabels(this.batchTargetRows)
      if (this.selectType === 'batch_thumbnail') this.handleBatchThumbnail(this.batchTargetRows)
      if (this.selectType === 'transfer_platform') this.handleTransferToPlatform(transferRows, transferIds)
      if (this.selectType === 'transfer_merchant') this.handleTransferToMerchant(transferRows, transferIds)
    },
    handleAuth(row) {
      this.loading = true
      const querySnapshot = this.captureQuerySnapshot()
      is_auth({
        ids: this.selectGetIds(row),
        goods_status: this.goods_status,
        auth_msg: this.auth_msg,
        goods_label_id: this.goods_label_id,
        stock: this.stock
      })
        .then((res) => {
          const diff = this.buildBatchOperationDiff('auth', row, {
            goods_status: this.goods_status,
            auth_msg: this.auth_msg
          })
          this.list()
          this.clearSelection()
          this.pushOperationFeedback({
            label: '批量审核',
            tone: this.goods_status === 2 ? 'warning' : 'success',
            count: row.length,
            target: this.goods_status === 2 ? '审核拒绝' : '审核通过',
            message: res.msg,
            ids: this.selectGetIds(row),
            search_id: row[0]?.[this.idkey] || '',
            diff,
            diffTitle: '审核结果摘要',
            querySnapshot
          })
          ElMessage.success(res.msg)
        })
        .catch(() => {
          this.list()
        })
    },
    disable(row, select = false) {
      if (!row.length) {
        this.selectAlert()
        return
      }
      this.loading = true
      const nextValue = select ? this.is_disable : row[0].is_disable
      const querySnapshot = this.captureQuerySnapshot()
      disable({
        ids: this.selectGetIds(row),
        is_disable: nextValue
      })
        .then((res) => {
          const diff = this.buildBatchOperationDiff('disable', row, { is_disable: nextValue })
          this.list()
          if (select) this.clearSelection()
          this.pushOperationFeedback({
            label: '上架/下架',
            tone: nextValue === 0 ? 'success' : 'warning',
            count: row.length,
            target: nextValue === 0 ? '已上架' : '已下架',
            message: res.msg,
            ids: this.selectGetIds(row),
            search_id: row[0]?.[this.idkey] || '',
            diff,
            diffTitle: '状态变更摘要',
            querySnapshot
          })
          ElMessage.success(res.msg)
        })
        .catch(() => {
          this.list()
        })
    },
    handleDelete(row) {
      this.loading = true
      const querySnapshot = this.captureQuerySnapshot()
      dele({ ids: this.selectGetIds(row) })
        .then((res) => {
          const diff = this.buildBatchOperationDiff('delete', row)
          this.list()
          this.clearSelection()
          this.pushOperationFeedback({
            label: '批量删除',
            tone: 'danger',
            count: row.length,
            message: res.msg,
            ids: this.selectGetIds(row),
            search_id: row[0]?.[this.idkey] || '',
            diff,
            diffTitle: '删除结果摘要',
            querySnapshot
          })
          ElMessage.success(res.msg)
        })
        .catch(() => {
          this.loading = false
        })
    },
    handleBatchThumbnail(row) {
      this.loading = true
      const querySnapshot = this.captureQuerySnapshot()
      batchUpdateThumbnail({
        ids: this.selectGetIds(row),
        image_id: this.batch_image_id
      })
        .then((res) => {
          const diff = this.buildBatchOperationDiff('thumbnail', row, { image_id: this.batch_image_id })
          this.list()
          this.clearSelection()
          this.pushOperationFeedback({
            label: '批量换图',
            tone: 'warning',
            count: row.length,
            target: '缩略图已更新',
            message: res.msg,
            ids: this.selectGetIds(row),
            search_id: row[0]?.[this.idkey] || '',
            diff,
            diffTitle: '缩略图更新摘要',
            querySnapshot
          })
          ElMessage.success(res.msg)
        })
        .catch(() => {
          this.loading = false
        })
    },
    handleBatchLabels(row) {
      this.loading = true
      const querySnapshot = this.captureQuerySnapshot()
      batchUpdateLabels({
        ids: this.selectGetIds(row),
        goods_label_id: this.batch_goods_label_id
      })
        .then((res) => {
          const diff = this.buildBatchOperationDiff('label', row, { goods_label_id: this.batch_goods_label_id })
          this.list()
          this.clearSelection()
          this.pushOperationFeedback({
            label: '批量打标',
            tone: 'primary',
            count: row.length,
            target: this.batch_goods_label_id.length ? '标签已更新' : '标签已清空',
            message: res.msg,
            ids: this.selectGetIds(row),
            search_id: row[0]?.[this.idkey] || '',
            diff,
            diffTitle: '标签更新摘要',
            querySnapshot
          })
          ElMessage.success(res.msg)
        })
        .catch(() => {
          this.loading = false
        })
    },
    handleTransferToPlatform(row, ids = null) {
      this.loading = true
      const targetIds = Array.isArray(ids) && ids.length ? ids : this.selectGetIds(row)
      const querySnapshot = this.captureQuerySnapshot()
      transferToPlatform({ ids: targetIds })
        .then((res) => {
          const diff = this.buildOperationDiff(row, 0)
          const revertPlan = this.buildOperationRevert(row, 0)
          this.list()
          this.clearSelection()
          this.pushOperationFeedback({
            label: '迁移到平台',
            tone: 'primary',
            count: targetIds.length,
            target: '平台自营',
            message: res.msg,
            ids: targetIds,
            filterable: true,
            filterLabel: '筛选平台自营',
            merchant_id: 0,
            search_id: row[0]?.[this.idkey] || targetIds[0] || '',
            diff,
            diffTitle: '迁移后差异高亮',
            querySnapshot,
            ...revertPlan
          })
          ElMessage.success(res.msg)
        })
        .catch(() => {
          this.loading = false
        })
    },
    handleTransferToMerchant(row, ids = null) {
      this.loading = true
      const targetIds = Array.isArray(ids) && ids.length ? ids : this.selectGetIds(row)
      const querySnapshot = this.captureQuerySnapshot()
      transferToMerchant({
        ids: targetIds,
        target_merchant_id: this.target_merchant_id
      })
        .then((res) => {
          const diff = this.buildOperationDiff(row, this.target_merchant_id)
          const revertPlan = this.buildOperationRevert(row, this.target_merchant_id)
          this.list()
          this.clearSelection()
          this.pushOperationFeedback({
            label: '迁移到商家',
            tone: 'success',
            count: targetIds.length,
            target: this.targetMerchantLabel,
            message: res.msg,
            ids: targetIds,
            filterable: true,
            filterLabel: '筛选目标商家',
            merchant_id: this.target_merchant_id,
            search_id: row[0]?.[this.idkey] || targetIds[0] || '',
            diff,
            diffTitle: '迁移后差异高亮',
            querySnapshot,
            ...revertPlan
          })
          ElMessage.success(res.msg)
        })
        .catch(() => {
          this.loading = false
        })
    }
  }
}
</script>

<style scoped>
.goods-ops-page {
  display: grid;
  gap: 18px;
}

.panel {
  border: 0;
  border-radius: 20px;
  box-shadow: 0 18px 48px rgba(15, 23, 42, 0.08);
}

.panel--hero {
  background: linear-gradient(135deg, #0f3c64, #1d6f9d 58%, #f0a15d);
  color: #fff;
}

.panel--hero-lite {
  box-shadow: 0 12px 28px rgba(15, 60, 100, 0.1);
}

.panel__header,
.panel__header-bar {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  align-items: flex-start;
}

.panel__title {
  font-size: 26px;
  font-weight: 700;
}

.panel__desc,
.panel__sub-desc {
  margin-top: 8px;
  font-size: 13px;
  line-height: 1.7;
  color: rgba(255, 255, 255, 0.86);
}

.panel__sub-title {
  font-size: 24px;
  font-weight: 700;
  color: #16324f;
}

.panel__header-bar .panel__sub-desc {
  color: #5d7085;
}

.panel__actions,
.panel__toolbar {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 10px;
}

.panel--workbench {
  transition: border-color 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
}

.panel--workbench-compact {
  border-color: #e8eef5;
  background: linear-gradient(180deg, #ffffff, #fbfcfe);
  box-shadow: inset 0 0 0 1px rgba(228, 236, 245, 0.7);
}

.panel__header-bar--compact {
  align-items: center;
}

.panel__header-bar--compact .panel__sub-title {
  font-size: 18px;
}

.panel__toolbar--compact {
  gap: 8px;
}

.panel__toolbar--compact :deep(.el-button) {
  min-height: 32px;
  padding: 7px 12px;
  border-radius: 999px;
  border-color: #dbe6f2;
  color: #35526e;
  background: #f8fbfe;
}

.panel__toolbar--compact :deep(.el-button--primary),
.panel__toolbar--compact :deep(.el-button--success),
.panel__toolbar--compact :deep(.el-button--warning) {
  background: #eef5fb;
  color: #1f4e79;
  border-color: #cfe0f0;
}

.compact-workbench {
  margin-top: 16px;
  padding: 16px 18px;
  border-radius: 18px;
  border: 1px solid #dce7f3;
  background: linear-gradient(135deg, #f8fbff, #eef5fb);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
}

.compact-workbench--active {
  border-color: #c9dcf3;
  box-shadow: 0 14px 32px rgba(90, 120, 160, 0.1);
}

.compact-workbench__main {
  min-width: 240px;
  flex: 1;
}

.compact-workbench__title {
  font-size: 16px;
  font-weight: 700;
  color: #173858;
}

.compact-workbench__desc {
  margin-top: 6px;
  font-size: 13px;
  line-height: 1.7;
  color: #5f7387;
}

.compact-workbench__tags {
  margin-top: 10px;
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.compact-workbench__tags span {
  padding: 6px 10px;
  border-radius: 999px;
  background: #fff;
  color: #3b5673;
  font-size: 12px;
  line-height: 1.4;
  border: 1px solid #dde8f4;
}

.compact-workbench__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.action-groups {
  margin-top: 16px;
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
}

.action-group {
  padding: 16px;
  border-radius: 18px;
  background: linear-gradient(135deg, #f9fbff, #eef4fb);
  border: 1px solid #dfeafb;
}

.action-group__title {
  font-size: 15px;
  font-weight: 700;
  color: #173858;
}

.action-group__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.6;
  color: #607388;
}

.action-group__items {
  margin-top: 12px;
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.action-group__item,
.combined-filter {
  border: 0;
  border-radius: 999px;
  padding: 8px 12px;
  background: #fff;
  color: #21405f;
  cursor: pointer;
}

.action-group__item:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

.hero-shortcuts {
  margin-top: 18px;
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
}

.hero-decision-strip {
  margin-top: 16px;
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  padding: 14px 16px;
  border-radius: 16px;
  background: rgba(255, 255, 255, 0.1);
  box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.12);
}

.hero-decision-strip--warning {
  background: rgba(255, 244, 214, 0.12);
}

.hero-decision-strip--info,
.hero-decision-strip--success,
.hero-decision-strip--default {
  background: rgba(255, 255, 255, 0.1);
}

.hero-decision-strip__main {
  min-width: 0;
  flex: 1;
}

.hero-decision-strip__label {
  font-size: 12px;
  color: rgba(255, 255, 255, 0.72);
}

.hero-decision-strip__title {
  margin-top: 6px;
  font-size: 16px;
  font-weight: 700;
  color: #fff;
}

.hero-decision-strip__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: rgba(255, 255, 255, 0.8);
}

.hero-decision-strip__tags {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 8px;
}

.hero-decision-strip__tags span {
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  padding: 0 10px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.14);
  color: #fff;
  font-size: 12px;
  white-space: nowrap;
}

.hero-guide-grid {
  margin-top: 16px;
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
}

.hero-guide-card {
  padding: 14px;
  border-radius: 16px;
  background: rgba(255, 255, 255, 0.08);
  box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.1);
}

.hero-guide-card__title {
  font-size: 13px;
  font-weight: 700;
  color: #fff;
}

.hero-guide-card__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: rgba(255, 255, 255, 0.78);
}

.hero-guide-card__action {
  margin-top: 8px;
  font-size: 12px;
  color: #dbeafe;
}

.hero-shortcut {
  border: 0;
  border-radius: 14px;
  padding: 12px 14px;
  text-align: left;
  background: rgba(255, 255, 255, 0.1);
  color: #fff;
  cursor: pointer;
  transition: transform 0.18s ease, background 0.18s ease, box-shadow 0.18s ease;
  box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.14);
}

.hero-shortcut:disabled {
  cursor: not-allowed;
  opacity: 0.6;
}

.hero-shortcut:not(:disabled):hover {
  transform: translateY(-1px);
  background: rgba(255, 255, 255, 0.16);
  box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.2);
}

.hero-shortcut__title {
  display: block;
  font-size: 14px;
  font-weight: 700;
}

.hero-shortcut__desc {
  display: block;
  margin-top: 4px;
  font-size: 11px;
  line-height: 1.5;
  color: rgba(255, 255, 255, 0.74);
}

.batch-bridge {
  display: grid;
  gap: 16px;
}

.batch-bridge__title {
  font-size: 22px;
  font-weight: 700;
  color: #173858;
}

.batch-bridge__desc {
  margin-top: 8px;
  color: #597087;
  line-height: 1.7;
}

.batch-bridge__controls {
  display: grid;
  grid-template-columns: 1.1fr 1.2fr 1fr;
  gap: 14px;
}

.bridge-control {
  padding: 16px;
  border-radius: 18px;
  background: linear-gradient(135deg, #f9fbff, #eef4fb);
}

.bridge-control label {
  display: block;
  margin-bottom: 10px;
  font-size: 13px;
  color: #567089;
}

.bridge-control--merchant :deep(.el-select) {
  width: 100%;
}

.bridge-target-quick {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
  margin-top: 10px;
}

.bridge-target-quick__label {
  font-size: 12px;
  color: #607388;
}

.bridge-action-row {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.bridge-target-switch {
  margin-top: 14px;
  padding: 14px 16px;
  border-radius: 18px;
  border: 1px solid #d9e8f6;
  background: linear-gradient(135deg, #f8fbff, #edf5fc);
}

.bridge-target-switch__label {
  font-size: 13px;
  color: #567089;
  margin-bottom: 10px;
}

.bridge-target-switch__items {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.bridge-steps {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
}

.bridge-step {
  padding: 14px 16px;
  border-radius: 18px;
  border: 1px solid #e5edf7;
  background: #fff;
}

.bridge-step__index {
  width: 26px;
  height: 26px;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: #eef4ff;
  color: #2454d3;
  font-weight: 700;
  font-size: 12px;
}

.bridge-step__title {
  margin-top: 10px;
  font-size: 14px;
  font-weight: 700;
  color: #173858;
}

.bridge-step__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.6;
  color: #607388;
}

.bridge-summary {
  padding: 16px 18px;
  border-radius: 18px;
  background: linear-gradient(180deg, #f8fbff, #f2f7fc);
}

.bridge-summary__title {
  font-weight: 700;
  color: #173858;
}

.bridge-summary__tags {
  margin-top: 10px;
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.bridge-summary__items {
  margin-top: 12px;
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  color: #5f7387;
  font-size: 12px;
}

.bridge-summary__items span {
  padding: 6px 10px;
  border-radius: 999px;
  background: #fff;
}

.bridge-summary__grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-top: 14px;
}

.bridge-summary-card {
  padding: 14px 16px;
  border-radius: 16px;
  border: 1px solid #d7e5f3;
  background: linear-gradient(180deg, #fff, #f7fbff);
}

.bridge-summary-card--active {
  border-color: #8fb7e0;
  box-shadow: 0 10px 24px rgba(33, 64, 95, 0.08);
}

.bridge-summary-card__label {
  font-size: 12px;
  color: #607388;
}

.bridge-summary-card__value {
  margin-top: 8px;
  font-size: 16px;
  font-weight: 700;
  line-height: 1.5;
  color: #173858;
}

.bridge-summary-card__desc {
  margin-top: 8px;
  font-size: 13px;
  line-height: 1.6;
  color: #4a6078;
}

.bridge-summary-card__tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 10px;
}

.bridge-summary-card__tags span {
  padding: 6px 10px;
  border-radius: 999px;
  background: #edf5fd;
  font-size: 12px;
  color: #2b5a88;
}

.bridge-summary__alerts {
  display: grid;
  gap: 10px;
  margin-top: 14px;
}

.bridge-summary-alert {
  padding: 12px 14px;
  border-radius: 14px;
  border: 1px solid #d8e4f1;
  background: #f8fbff;
}

.bridge-summary-alert--warning {
  border-color: #f6d89b;
  background: #fff9ec;
}

.bridge-summary-alert--danger {
  border-color: #f2b4b4;
  background: #fff4f4;
}

.bridge-summary-alert__title {
  font-size: 13px;
  font-weight: 700;
  color: #173858;
}

.bridge-summary-alert__desc {
  margin-top: 6px;
  font-size: 13px;
  line-height: 1.6;
  color: #4a6078;
}

.bridge-summary__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 14px;
}

.bridge-command-center {
  display: grid;
  grid-template-columns: 1.15fr 1fr;
  gap: 14px;
}

.bridge-command-card {
  padding: 18px;
  border-radius: 20px;
  border: 1px solid #dce7f4;
  background: linear-gradient(135deg, #f8fbff, #eef5fb);
  display: grid;
  gap: 12px;
}

.bridge-command-card--primary {
  border-color: #cfe0fb;
  background: linear-gradient(135deg, #f7fbff, #edf5ff);
}

.bridge-command-card--success {
  border-color: #cfe8d7;
  background: linear-gradient(135deg, #f6fcf7, #edf8f0);
}

.bridge-command-card--warning {
  border-color: #efd8b3;
  background: linear-gradient(135deg, #fff9ef, #fff1de);
}

.bridge-command-card--muted,
.bridge-command-card--timeline {
  border-color: #dce7f4;
  background: linear-gradient(135deg, #f8fbff, #eff5fb);
}

.bridge-command-card__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.bridge-command-card__eyebrow {
  font-size: 12px;
  font-weight: 700;
  color: #607388;
}

.bridge-command-card__title {
  margin-top: 6px;
  font-size: 18px;
  line-height: 1.45;
  font-weight: 700;
  color: #173858;
}

.bridge-command-card__desc {
  font-size: 13px;
  line-height: 1.75;
  color: #5f7387;
}

.bridge-command-card__checklist {
  display: grid;
  gap: 8px;
}

.bridge-command-check {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.82);
  border: 1px solid rgba(148, 163, 184, 0.18);
}

.bridge-command-check--done {
  border-color: rgba(34, 197, 94, 0.18);
  background: rgba(255, 255, 255, 0.92);
}

.bridge-command-check__status {
  flex-shrink: 0;
  padding: 4px 8px;
  border-radius: 999px;
  background: #eef2ff;
  color: #3557a5;
  font-size: 12px;
  font-weight: 700;
}

.bridge-command-check--done .bridge-command-check__status {
  background: #ecfdf3;
  color: #15803d;
}

.bridge-command-check__label {
  min-width: 0;
  font-size: 13px;
  line-height: 1.6;
  color: #244463;
}

.bridge-command-card__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.bridge-timeline-strip {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 10px;
}

.bridge-timeline-strip__item {
  min-width: 0;
  padding: 12px;
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.86);
  border: 1px solid rgba(148, 163, 184, 0.16);
  display: grid;
  gap: 6px;
}

.bridge-timeline-strip__label {
  font-size: 12px;
  color: #607388;
}

.bridge-timeline-strip__item strong {
  font-size: 13px;
  line-height: 1.6;
  color: #173858;
}

.bridge-feedback {
  padding: 16px 18px;
  border-radius: 18px;
  border: 1px solid #d7e7fb;
  background: linear-gradient(135deg, #f8fbff, #eef6ff);
}

.bridge-feedback__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.bridge-feedback__title {
  font-weight: 700;
  color: #173858;
}

.bridge-feedback__meta {
  margin-top: 10px;
  display: flex;
  flex-wrap: wrap;
  gap: 14px;
  color: #567089;
  font-size: 13px;
}

.bridge-feedback__message {
  margin-top: 10px;
  color: #21405f;
  font-size: 14px;
  line-height: 1.7;
}

.bridge-feedback__actions {
  margin-top: 12px;
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.bridge-feedback__snapshot {
  margin-top: 10px;
  font-size: 12px;
  color: #607388;
}

.operation-diff {
  margin-top: 16px;
  padding-top: 14px;
  border-top: 1px solid rgba(36, 84, 211, 0.12);
}

.operation-diff__header,
.bridge-history__heading {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.operation-diff__title {
  font-weight: 700;
  color: #173858;
}

.operation-diff__grid {
  margin-top: 12px;
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
}

.operation-diff__card {
  padding: 14px 16px;
  border-radius: 16px;
  background: rgba(255, 255, 255, 0.76);
  border: 1px solid #dfeafb;
}

.operation-diff__card--accent {
  background: linear-gradient(135deg, #eef7ff, #dff0ff);
  border-color: #bddcff;
}

.operation-diff__label {
  font-size: 12px;
  color: #607388;
}

.operation-diff__value {
  margin-top: 8px;
  font-size: 16px;
  font-weight: 700;
  color: #173858;
}

.operation-diff__desc,
.bridge-history__diff {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.6;
  color: #607388;
}

.bridge-insight-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
}

.bridge-insight-card {
  padding: 16px 18px;
  border-radius: 18px;
  background: linear-gradient(135deg, #f9fbff, #f1f6fd);
  border: 1px solid #e0ebf8;
}

.bridge-insight-card--active {
  background: linear-gradient(135deg, #fff9ee, #ffefd8);
  border-color: #eed7aa;
}

.bridge-insight-card__title {
  font-size: 13px;
  color: #607388;
}

.bridge-insight-card__value {
  margin-top: 10px;
  font-size: 18px;
  font-weight: 700;
  color: #173858;
}

.bridge-insight-card__desc {
  margin-top: 8px;
  color: #5f7387;
  line-height: 1.7;
  font-size: 13px;
}

.bridge-insight-card__tags {
  margin-top: 10px;
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.bridge-insight-card__actions {
  margin-top: 12px;
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.bridge-target-library {
  margin-top: 12px;
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
}

.bridge-target-library__item {
  padding: 12px 14px;
  border-radius: 14px;
  border: 1px solid #d8e6f5;
  background: rgba(255, 255, 255, 0.82);
  text-align: left;
  cursor: pointer;
  transition: border-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
}

.bridge-target-library__item:hover {
  border-color: #94b9eb;
  box-shadow: 0 8px 18px rgba(74, 117, 181, 0.12);
  transform: translateY(-1px);
}

.bridge-target-library__item--active {
  border-color: #5b8def;
  background: linear-gradient(135deg, #eef5ff, #e2edff);
}

.bridge-target-library__title {
  display: block;
  font-weight: 700;
  color: #173858;
}

.bridge-target-library__meta {
  display: block;
  margin-top: 6px;
  font-size: 12px;
  color: #607388;
}

.bridge-risk-panel {
  padding: 16px 18px;
  border-radius: 18px;
  background: linear-gradient(135deg, #fff8ee, #fff2df);
  border: 1px solid #f3ddbb;
}

.bridge-risk-panel__title {
  font-weight: 700;
  color: #7a4c0f;
}

.bridge-risk-panel__items {
  margin-top: 10px;
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.bridge-risk-panel__items span {
  padding: 7px 10px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.78);
  color: #86551a;
  font-size: 12px;
}

.bridge-permissions {
  padding: 16px 18px;
  border-radius: 18px;
  background: linear-gradient(135deg, #f7fafc, #eef4f9);
  border: 1px solid #d9e5f2;
}

.bridge-permissions__title {
  font-weight: 700;
  color: #173858;
}

.bridge-permissions__items,
.transfer-preview__grid {
  margin-top: 12px;
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.bridge-permissions__item,
.transfer-preview__card {
  padding: 14px 16px;
  border-radius: 16px;
  background: #fff;
  border: 1px solid #dce8f5;
}

.bridge-permissions__item--disabled {
  background: #f8fafc;
  opacity: 0.78;
}

.bridge-permissions__label,
.transfer-preview__label {
  font-size: 12px;
  color: #607388;
}

.bridge-permissions__desc,
.transfer-preview__desc {
  margin-top: 6px;
  font-size: 13px;
  line-height: 1.6;
  color: #21405f;
}

.transfer-preview__lead {
  color: #dc2626;
}

.transfer-preview__value {
  margin-top: 8px;
  font-size: 16px;
  font-weight: 700;
  color: #173858;
}

.transfer-target-panel {
  width: 100%;
  display: grid;
  gap: 10px;
}

.transfer-target-panel__quick {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
}

.transfer-target-panel__draft {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;
}

.transfer-target-panel__draft-text {
  font-size: 12px;
  color: #607388;
}

.transfer-target-panel__quick-label {
  font-size: 12px;
  color: #607388;
}

.transfer-target-chip {
  border: 0;
  border-radius: 999px;
  padding: 7px 12px;
  background: #eef4fb;
  color: #244463;
  cursor: pointer;
  transition: all 0.18s ease;
}

.transfer-target-chip--active,
.transfer-target-chip:hover {
  background: linear-gradient(135deg, #16a34a, #22c55e);
  color: #fff;
}

.transfer-preview__card--accent {
  background: linear-gradient(135deg, #eef7ff, #dff0ff);
  border-color: #bddcff;
}

.transfer-preview--inline {
  width: 100%;
}

.bridge-history {
  padding: 16px 18px;
  border-radius: 18px;
  border: 1px solid #e2ebf6;
  background: #fff;
}

.bridge-history__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
}

.bridge-history__toolbar {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.bridge-history__heading {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.bridge-history__stats {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.bridge-history__filter {
  width: 132px;
}

.bridge-history__search {
  width: 240px;
}

.bridge-history__title {
  font-weight: 700;
  color: #173858;
}

.bridge-history-overview {
  margin-top: 14px;
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 10px;
}

.quick-batch-library {
  margin-top: 14px;
  padding: 16px;
  border-radius: 20px;
  border: 1px solid #dce7f3;
  background: linear-gradient(135deg, #fbfdff, #f3f8fc);
  display: grid;
  gap: 14px;
}

.quick-batch-library__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.quick-batch-library__title {
  font-size: 16px;
  font-weight: 700;
  color: #1f3c58;
}

.quick-batch-library__desc {
  margin-top: 4px;
  font-size: 12px;
  line-height: 1.7;
  color: #60758a;
}

.quick-batch-library__grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.quick-batch-card {
  padding: 14px;
  border-radius: 16px;
  border: 1px solid #e3ebf5;
  background: rgba(255, 255, 255, 0.92);
  display: grid;
  gap: 10px;
}

.quick-batch-card--success {
  border-color: #cfe9d8;
}

.quick-batch-card--warning {
  border-color: #f0deb3;
}

.quick-batch-card--primary {
  border-color: #d7e3f6;
}

.quick-batch-card__header {
  display: flex;
  justify-content: space-between;
  gap: 12px;
}

.quick-batch-card__title {
  font-size: 14px;
  font-weight: 700;
  color: #203b57;
}

.quick-batch-card__meta {
  margin-top: 4px;
  font-size: 12px;
  color: #6a7f94;
}

.quick-batch-card__desc {
  font-size: 12px;
  line-height: 1.7;
  color: #5c7186;
}

.quick-batch-card__stats {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.quick-batch-card__stats span {
  padding: 6px 10px;
  border-radius: 999px;
  background: #f3f7fb;
  color: #52687d;
  font-size: 12px;
}

.quick-batch-card__notice {
  padding: 10px 12px;
  border-radius: 14px;
  border: 1px solid #d8e6f4;
  background: #f8fbff;
  display: grid;
  gap: 4px;
}

.quick-batch-card__notice--match {
  border-color: #cfe1f3;
  background: linear-gradient(135deg, #f5faff, #edf5fd);
}

.quick-batch-card__notice--idle {
  border-color: #e2eaf4;
  background: #f8fafc;
}

.quick-batch-card__notice-title {
  font-size: 12px;
  font-weight: 700;
  color: #24405b;
}

.quick-batch-card__notice-desc {
  font-size: 12px;
  line-height: 1.7;
  color: #607489;
}

.quick-batch-card__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.bridge-history-overview__card {
  padding: 14px 16px;
  border-radius: 16px;
  border: 1px solid #dce8f5;
  background: linear-gradient(135deg, #f9fbff, #f2f7fd);
}

.bridge-history-overview__card--primary {
  background: linear-gradient(135deg, #eef6ff, #e2f0ff);
}

.bridge-history-overview__card--success {
  background: linear-gradient(135deg, #eefbf3, #dcf6e6);
}

.bridge-history-overview__card--warning {
  background: linear-gradient(135deg, #fff8ee, #ffefd9);
}

.bridge-history-overview__card--info {
  background: linear-gradient(135deg, #f5f8fb, #ebf1f6);
}

.bridge-history-overview__label {
  font-size: 12px;
  color: #607388;
}

.bridge-history-overview__value {
  margin-top: 8px;
  font-size: 18px;
  font-weight: 700;
  color: #173858;
  word-break: break-all;
}

.bridge-history-overview__desc {
  margin-top: 8px;
  font-size: 12px;
  line-height: 1.6;
  color: #5f7387;
}

.bridge-history-priority {
  margin-top: 14px;
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 10px;
}

.bridge-history-priority__card {
  padding: 16px;
  border-radius: 18px;
  border: 1px solid #d9e5f2;
  background: linear-gradient(135deg, #f8fbff, #eff5fb);
}

.bridge-history-priority__card--danger {
  background: linear-gradient(135deg, #fff4f3, #ffe4e2);
  border-color: #f0c4be;
}

.bridge-history-priority__card--warning {
  background: linear-gradient(135deg, #fff9ee, #ffefd8);
  border-color: #eed7aa;
}

.bridge-history-priority__card--success {
  background: linear-gradient(135deg, #eefbf3, #dbf5e5);
  border-color: #bfe3cb;
}

.bridge-history-priority__card--primary {
  background: linear-gradient(135deg, #eef6ff, #e0eeff);
  border-color: #c4daf5;
}

.bridge-history-priority__card--info {
  background: linear-gradient(135deg, #f5f8fb, #ebf1f6);
  border-color: #d5e1eb;
}

.bridge-history-priority__header {
  display: flex;
  justify-content: space-between;
  gap: 10px;
  align-items: flex-start;
}

.bridge-history-priority__title {
  font-size: 15px;
  font-weight: 700;
  color: #173858;
}

.bridge-history-priority__meta {
  margin-top: 4px;
  font-size: 12px;
  color: #607388;
  line-height: 1.6;
}

.bridge-history-priority__desc {
  margin-top: 10px;
  min-height: 58px;
  font-size: 13px;
  line-height: 1.7;
  color: #21405f;
}

.bridge-history-priority__tips {
  margin-top: 10px;
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.bridge-history-priority__tips span {
  padding: 6px 10px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.75);
  color: #52677d;
  font-size: 12px;
}

.bridge-history-priority__alerts {
  margin-top: 10px;
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.bridge-history-priority__actions {
  margin-top: 10px;
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.filter-history__status {
  margin-top: 6px;
  font-size: 12px;
  color: #607388;
}

.bridge-history__items {
  margin-top: 12px;
  display: grid;
  gap: 10px;
}

.bridge-history-group {
  padding: 12px;
  border-radius: 16px;
  border: 1px solid #e3ecf7;
  background: linear-gradient(135deg, #fbfdff, #f5f9fe);
}

.bridge-history-group__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  flex-wrap: wrap;
  padding-bottom: 10px;
  border-bottom: 1px solid rgba(36, 84, 211, 0.08);
}

.bridge-history-group__title {
  font-size: 14px;
  font-weight: 700;
  color: #173858;
}

.bridge-history-group__meta {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
  font-size: 12px;
  color: #607388;
}

.bridge-history-group__items {
  margin-top: 10px;
  display: grid;
  gap: 10px;
}

.bridge-history__item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 12px 14px;
  border-radius: 14px;
  background: linear-gradient(135deg, #f8fbff, #f1f6fd);
}

.bridge-history__label {
  font-weight: 700;
  color: #173858;
}

.bridge-history__desc {
  margin-top: 4px;
  color: #607388;
  font-size: 12px;
}

.bridge-history__stats-inline {
  margin-top: 8px;
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.bridge-history__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.bridge-history__empty {
  padding: 18px 16px;
  border-radius: 14px;
  border: 1px dashed rgba(148, 163, 184, 0.55);
  background: rgba(248, 250, 252, 0.88);
  color: #64748b;
  font-size: 13px;
}

.metrics-grid {
  display: grid;
  grid-template-columns: repeat(6, minmax(0, 1fr));
  gap: 14px;
}

.metric-card {
  border-radius: 18px;
  border: 0;
}

.metric-card__label {
  color: #5f7387;
  font-size: 13px;
}

.metric-card__value {
  margin-top: 10px;
  font-size: 30px;
  font-weight: 700;
  color: #162f4f;
}

.metric-card__meta {
  margin-top: 8px;
  font-size: 12px;
  color: #6d7f90;
}

.metric-card--all {
  background: linear-gradient(135deg, #f5f8ff, #dbe8ff);
}

.metric-card--pending {
  background: linear-gradient(135deg, #fff8ec, #ffe1b0);
}

.metric-card--success {
  background: linear-gradient(135deg, #eefbf2, #c8f0d5);
}

.metric-card--danger {
  background: linear-gradient(135deg, #fff1f1, #ffd1d1);
}

.metric-card--info {
  background: linear-gradient(135deg, #f1f8ff, #cfe9ff);
}

.metric-card--warning {
  background: linear-gradient(135deg, #fff4ef, #ffd8c2);
}

.quick-filters {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.combined-filters {
  margin-top: 14px;
}

.combined-filters__title {
  font-size: 13px;
  color: #5f7387;
}

.combined-filters__items {
  margin-top: 10px;
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.quick-filter {
  border: 0;
  border-radius: 999px;
  padding: 10px 16px;
  background: #eef4fb;
  color: #2b5071;
  cursor: pointer;
  transition: all 0.18s ease;
}

.quick-filter:hover,
.quick-filter--active {
  background: linear-gradient(135deg, #2f6bff, #3bb0ff);
  color: #fff;
}

.filter-form {
  margin-top: 16px;
}

.current-params {
  margin-top: 8px;
}

.current-params__title {
  font-size: 13px;
  color: #5f7387;
}

.current-params__tags {
  margin-top: 10px;
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.filter-history {
  margin-top: 16px;
  padding: 14px 16px;
  border-radius: 16px;
  border: 1px solid #dce8f5;
  background: linear-gradient(135deg, #f8fbff, #f3f8ff);
}

.filter-history__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
}

.filter-history__title {
  font-size: 13px;
  font-weight: 700;
  color: #173858;
}

.filter-history__desc {
  margin-top: 4px;
  font-size: 12px;
  line-height: 1.5;
  color: #5f7387;
}

.filter-history__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.filter-history__items {
  margin-top: 12px;
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.filter-history__item {
  border: 0;
  border-radius: 14px;
  padding: 10px 12px;
  min-width: 180px;
  background: #fff;
  box-shadow: 0 8px 24px rgba(21, 62, 109, 0.06);
  display: grid;
  gap: 4px;
  text-align: left;
  cursor: pointer;
  transition: transform 0.18s ease, box-shadow 0.18s ease;
}

.filter-history__item:hover {
  transform: translateY(-1px);
  box-shadow: 0 10px 26px rgba(21, 62, 109, 0.1);
}

.filter-history__item-label {
  font-size: 13px;
  font-weight: 700;
  color: #173858;
}

.filter-history__item-meta,
.filter-history__empty {
  font-size: 12px;
  line-height: 1.5;
  color: #607388;
}

.filter-history__empty {
  padding: 10px 12px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.76);
}

.online-actions {
  margin-top: 16px;
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.selection-stage-bar {
  margin-top: 12px;
  padding: 14px 16px;
  border-radius: 18px;
  border: 1px solid #dce7f3;
  background: linear-gradient(135deg, #f7fbff, #eef5fb);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
}

.selection-stage-bar__summary {
  min-width: 220px;
  flex: 1;
}

.selection-stage-bar__title {
  font-size: 13px;
  font-weight: 700;
  color: #21405f;
}

.selection-stage-bar__desc {
  margin-top: 4px;
  font-size: 13px;
  line-height: 1.7;
  color: #5f7387;
}

.selection-stage-bar__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.selection-review-panel {
  margin-top: 14px;
  padding: 16px 18px;
  border-radius: 20px;
  border: 1px solid #dde8f3;
  background: linear-gradient(135deg, #fbfdff, #f2f7fb);
  display: grid;
  gap: 14px;
}

.selection-review-panel--active {
  border-color: #cfe0f3;
  box-shadow: 0 14px 30px rgba(121, 144, 173, 0.12);
}

.selection-review-panel__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.selection-review-panel__eyebrow {
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.08em;
  color: #6c8095;
}

.selection-review-panel__title {
  margin-top: 4px;
  font-size: 18px;
  font-weight: 700;
  color: #223a54;
}

.selection-review-panel__desc {
  font-size: 13px;
  line-height: 1.8;
  color: #5f7387;
}

.selection-review-panel__metrics {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
}

.selection-review-metric {
  padding: 14px;
  border-radius: 16px;
  border: 1px solid #e4edf6;
  background: rgba(255, 255, 255, 0.92);
  display: grid;
  gap: 6px;
}

.selection-review-metric__label {
  font-size: 12px;
  font-weight: 700;
  color: #6e8298;
}

.selection-review-metric__value {
  font-size: 18px;
  font-weight: 700;
  color: #1f3c58;
}

.selection-review-metric__desc {
  font-size: 12px;
  line-height: 1.7;
  color: #62778c;
}

.selection-review-panel__warnings {
  display: grid;
  gap: 8px;
}

.selection-review-link {
  padding: 14px;
  border-radius: 16px;
  border: 1px solid #d8e5f2;
  background: rgba(255, 255, 255, 0.9);
  display: grid;
  gap: 10px;
}

.selection-review-link__main {
  display: grid;
  gap: 4px;
}

.selection-review-link__title {
  font-size: 14px;
  font-weight: 700;
  color: #203c58;
}

.selection-review-link__desc {
  font-size: 12px;
  line-height: 1.7;
  color: #62768b;
}

.selection-review-link__tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.selection-review-link__tags span {
  padding: 6px 10px;
  border-radius: 999px;
  background: #f3f7fb;
  color: #4e647a;
  font-size: 12px;
  line-height: 1.4;
}

.selection-review-link__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.selection-review-warning {
  padding: 10px 12px;
  border-radius: 14px;
  border: 1px solid #f4d7ae;
  background: #fff9ef;
  font-size: 12px;
  line-height: 1.7;
  color: #926331;
}

.selection-review-panel__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.selection-review-focus {
  padding: 14px;
  border-radius: 18px;
  border: 1px solid #d9e5f1;
  background: linear-gradient(135deg, rgba(245, 249, 255, 0.96), rgba(255, 255, 255, 0.94));
  display: grid;
  gap: 12px;
}

.selection-review-focus__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.selection-review-focus__title {
  font-size: 14px;
  font-weight: 700;
  color: #1f3b57;
}

.selection-review-focus__desc {
  margin-top: 4px;
  font-size: 12px;
  line-height: 1.7;
  color: #607489;
}

.selection-review-focus__items {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 10px;
}

.selection-review-focus__item {
  padding: 12px;
  border-radius: 14px;
  border: 1px solid #e2ebf5;
  background: rgba(255, 255, 255, 0.94);
  display: grid;
  gap: 5px;
}

.selection-review-focus__item--warning {
  border-color: #f1d39d;
  background: #fff9ef;
}

.selection-review-focus__item--primary {
  border-color: #c8dcff;
  background: #f4f8ff;
}

.selection-review-focus__item--success {
  border-color: #ccead8;
  background: #f3fbf6;
}

.selection-review-focus__item--danger {
  border-color: #f0c8c8;
  background: #fff6f5;
}

.selection-review-focus__item-label {
  font-size: 12px;
  font-weight: 700;
  color: #698096;
}

.selection-review-focus__item-value {
  font-size: 18px;
  font-weight: 700;
  color: #1f3b57;
}

.selection-review-focus__item-desc {
  font-size: 12px;
  line-height: 1.7;
  color: #62778c;
}

.bridge-exception-tracker {
  padding: 12px;
  border-radius: 16px;
  border: 1px solid #dce7f3;
  background: rgba(255, 255, 255, 0.92);
  display: grid;
  gap: 10px;
}

.bridge-exception-tracker__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 10px;
}

.bridge-exception-tracker__title {
  font-size: 13px;
  font-weight: 700;
  color: #1f3b57;
}

.bridge-exception-tracker__desc {
  margin-top: 4px;
  font-size: 12px;
  line-height: 1.7;
  color: #62778c;
}

.bridge-exception-tracker__grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 10px;
}

.bridge-exception-tracker__item {
  padding: 10px 12px;
  border-radius: 14px;
  border: 1px solid #e2ebf5;
  background: #fff;
  display: grid;
  gap: 4px;
}

.bridge-exception-tracker__item--warning {
  border-color: #f1d39d;
  background: #fff9ef;
}

.bridge-exception-tracker__item--success {
  border-color: #ccead8;
  background: #f3fbf6;
}

.bridge-exception-tracker__item--primary {
  border-color: #c8dcff;
  background: #f4f8ff;
}

.bridge-exception-tracker__label {
  font-size: 12px;
  font-weight: 700;
  color: #698096;
}

.bridge-exception-tracker__value {
  font-size: 18px;
  font-weight: 700;
  color: #1f3b57;
}

.bridge-exception-tracker__desc {
  font-size: 12px;
  line-height: 1.6;
  color: #62778c;
}

.bridge-exception-tracker__alerts {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.bridge-exception-tracker__alerts span {
  padding: 6px 10px;
  border-radius: 999px;
  background: #fff4e6;
  color: #916334;
  font-size: 12px;
  line-height: 1.4;
}

.bridge-exception-tracker__filters {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.bridge-exception-tracker__filter {
  border: 1px solid #d8e4f2;
  border-radius: 999px;
  padding: 8px 12px;
  background: #fff;
  color: #274566;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  transition: all 0.18s ease;
}

.bridge-exception-tracker__filter strong {
  font-size: 12px;
}

.bridge-exception-tracker__filter:hover,
.bridge-exception-tracker__filter--active {
  border-color: #8cb7ff;
  background: #f5f9ff;
}

.bridge-exception-tracker__focus {
  padding: 12px;
  border-radius: 14px;
  border: 1px solid #dce7f3;
  background: linear-gradient(135deg, rgba(245, 249, 255, 0.98), rgba(255, 255, 255, 0.94));
  display: grid;
  gap: 10px;
}

.bridge-exception-tracker__focus-main {
  display: grid;
  gap: 4px;
}

.bridge-exception-tracker__focus-title {
  font-size: 13px;
  font-weight: 700;
  color: #1f3b57;
}

.bridge-exception-tracker__focus-desc {
  font-size: 12px;
  line-height: 1.7;
  color: #62778c;
}

.bridge-exception-tracker__focus-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.merchant-picks {
  margin-top: 16px;
}

.merchant-picks__title {
  font-size: 13px;
  color: #5f7387;
}

.merchant-picks__items {
  margin-top: 10px;
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.merchant-pick {
  border: 0;
  border-radius: 999px;
  padding: 8px 12px;
  background: #eef4fb;
  color: #244463;
  cursor: pointer;
  transition: all 0.18s ease;
}

.merchant-pick--active,
.merchant-pick:hover {
  background: linear-gradient(135deg, #2f6bff, #3bb0ff);
  color: #fff;
}

.online-action {
  border: 1px solid #d6e3f3;
  border-radius: 999px;
  padding: 10px 16px;
  background: #fff;
  color: #21405f;
  cursor: pointer;
  transition: all 0.18s ease;
}

.online-action:hover:not(:disabled) {
  border-color: #8ab7ff;
  background: #f5f9ff;
}

.online-action:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

.online-action--primary {
  border-color: #2f6bff;
  background: linear-gradient(135deg, #2f6bff, #3bb0ff);
  color: #fff;
}

.goods-table :deep(.el-table__row) {
  --el-table-tr-bg-color: #fff;
}

.cell-main {
  font-weight: 700;
  color: #173858;
}

.goods-title-cell {
  display: flex;
  align-items: flex-start;
  gap: 10px;
}

.goods-title-cell__tag {
  flex-shrink: 0;
}

.goods-title-cell__text {
  min-width: 0;
}

.cell-meta {
  margin-top: 4px;
  font-size: 12px;
  color: #607388;
}

.price-cell__current {
  font-weight: 700;
  color: #173858;
}

.price-cell__origin {
  margin-top: 4px;
  font-size: 12px;
  color: #607388;
}

.row-actions {
  display: grid;
  gap: 8px;
  grid-template-columns: minmax(0, 1fr);
  line-height: 1.8;
}

.row-actions__hints {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  grid-column: 1 / -1;
}

.row-actions__group {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 10px;
  padding: 8px 10px;
  border-radius: 12px;
  background: #f8fbff;
  border: 1px solid #e5eef8;
}

.row-actions__group--migration {
  background: linear-gradient(135deg, #f7fbff, #eef5fb);
}

.row-actions__group-label {
  font-size: 12px;
  font-weight: 700;
  color: #607388;
}

.status-stack {
  display: grid;
  gap: 6px;
}

.status-reason {
  margin-top: 6px;
  font-size: 12px;
  color: #dc2626;
}

.dialog-warn {
  color: #dc2626;
}

.dialog-help {
  line-height: 1.7;
  color: #5f7387;
}

.transfer-submit-panel {
  margin-top: 14px;
  padding: 14px 16px;
  border-radius: 16px;
  border: 1px solid #dce7f4;
  background: linear-gradient(135deg, #f8fbff, #eef5fb);
  display: grid;
  gap: 10px;
}

.transfer-submit-panel--success {
  border-color: #cfe8d7;
  background: linear-gradient(135deg, #f6fcf7, #edf8f0);
}

.transfer-submit-panel--warning {
  border-color: #efd8b3;
  background: linear-gradient(135deg, #fff9ef, #fff1de);
}

.transfer-submit-panel--primary {
  border-color: #cfe0fb;
  background: linear-gradient(135deg, #f7fbff, #edf5ff);
}

.transfer-submit-panel__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.transfer-submit-panel__eyebrow {
  font-size: 12px;
  font-weight: 700;
  color: #607388;
}

.transfer-submit-panel__title {
  margin-top: 4px;
  font-size: 16px;
  line-height: 1.5;
  font-weight: 700;
  color: #173858;
}

.transfer-submit-panel__desc {
  font-size: 13px;
  line-height: 1.7;
  color: #5f7387;
}

.transfer-submit-panel__checks {
  display: grid;
  gap: 8px;
}

.transfer-submit-check {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 9px 10px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.84);
  border: 1px solid rgba(148, 163, 184, 0.16);
}

.transfer-submit-check--done {
  border-color: rgba(34, 197, 94, 0.18);
}

.transfer-submit-check__status {
  flex-shrink: 0;
  padding: 3px 8px;
  border-radius: 999px;
  background: #eef2ff;
  color: #3557a5;
  font-size: 12px;
  font-weight: 700;
}

.transfer-submit-check--done .transfer-submit-check__status {
  background: #ecfdf3;
  color: #15803d;
}

.transfer-submit-check__label {
  font-size: 13px;
  line-height: 1.6;
  color: #244463;
}

.transfer-submit-panel__warnings {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.transfer-submit-panel__warnings span {
  padding: 7px 10px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.78);
  color: #86551a;
  font-size: 12px;
  line-height: 1.45;
}

.transfer-submit-panel__compare {
  padding: 12px 14px;
  border-radius: 14px;
  border: 1px solid #dce7f4;
  background: rgba(255, 255, 255, 0.84);
  display: grid;
  gap: 10px;
}

.transfer-submit-panel__compare--warning {
  border-color: #efd8b3;
  background: linear-gradient(135deg, #fffaf2, #fff2e1);
}

.transfer-submit-panel__compare--follow-up {
  border-color: #cfe0f6;
  background: linear-gradient(135deg, #f7fbff, #eef5ff);
}

.transfer-submit-panel__compare-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.transfer-submit-panel__compare-title {
  font-size: 13px;
  font-weight: 700;
  color: #21405f;
}

.transfer-submit-panel__compare-desc {
  margin-top: 4px;
  font-size: 12px;
  line-height: 1.7;
  color: #607489;
}

.transfer-submit-panel__compare-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.transfer-submit-panel__compare-tags span {
  padding: 6px 10px;
  border-radius: 999px;
  background: #f3f7fb;
  color: #52687d;
  font-size: 12px;
  line-height: 1.4;
}

.transfer-submit-panel__compare-alerts {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.transfer-submit-panel__metrics {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 10px;
}

.transfer-submit-panel__metric {
  padding: 12px;
  border-radius: 14px;
  border: 1px solid rgba(148, 163, 184, 0.18);
  background: rgba(255, 255, 255, 0.88);
  display: grid;
  gap: 4px;
}

.transfer-submit-panel__metric--info {
  background: linear-gradient(135deg, rgba(239, 246, 255, 0.96), rgba(219, 234, 254, 0.9));
}

.transfer-submit-panel__metric--success {
  background: linear-gradient(135deg, rgba(236, 253, 245, 0.98), rgba(209, 250, 229, 0.92));
}

.transfer-submit-panel__metric--warning {
  background: linear-gradient(135deg, rgba(255, 251, 235, 0.98), rgba(254, 240, 138, 0.28));
}

.transfer-submit-panel__metric--danger {
  background: linear-gradient(135deg, rgba(254, 242, 242, 0.98), rgba(254, 202, 202, 0.72));
}

.transfer-submit-panel__metric--primary {
  background: linear-gradient(135deg, rgba(239, 246, 255, 0.98), rgba(191, 219, 254, 0.9));
}

.transfer-submit-panel__metric--neutral {
  background: linear-gradient(135deg, rgba(248, 250, 252, 0.98), rgba(241, 245, 249, 0.94));
}

.transfer-submit-panel__metric-label {
  font-size: 12px;
  font-weight: 700;
  color: #63778b;
}

.transfer-submit-panel__metric-value {
  font-size: 18px;
  font-weight: 700;
  color: #173858;
}

.transfer-submit-panel__metric-desc {
  font-size: 12px;
  line-height: 1.6;
  color: #5f7387;
}

.transfer-submit-panel__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.transfer-submit-panel__risk-board {
  padding: 16px;
  border-radius: 18px;
  border: 1px solid #dae5f2;
  background: linear-gradient(135deg, rgba(246, 249, 255, 0.98), rgba(255, 255, 255, 0.94));
  display: grid;
  gap: 14px;
}

.transfer-submit-panel__risk-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 10px;
}

.transfer-submit-panel__risk-item {
  padding: 12px;
  border-radius: 14px;
  border: 1px solid #e1eaf4;
  background: rgba(255, 255, 255, 0.96);
  display: grid;
  gap: 5px;
}

.transfer-submit-panel__risk-item--warning {
  border-color: #f1d39d;
  background: #fff9ef;
}

.transfer-submit-panel__risk-item--success {
  border-color: #cde9d8;
  background: #f3fbf6;
}

.transfer-submit-panel__risk-item--danger {
  border-color: #f1c9c9;
  background: #fff5f4;
}

.transfer-submit-panel__risk-item--primary {
  border-color: #cadcff;
  background: #f4f8ff;
}

.transfer-submit-panel__risk-label {
  font-size: 12px;
  font-weight: 700;
  color: #688096;
}

.transfer-submit-panel__risk-value {
  font-size: 18px;
  font-weight: 700;
  color: #1d3d59;
}

.transfer-submit-panel__risk-desc {
  font-size: 12px;
  line-height: 1.7;
  color: #62788e;
}

.bridge-feedback__tracker {
  margin-top: 14px;
  padding: 14px;
  border-radius: 16px;
  border: 1px solid #dbe6f2;
  background: linear-gradient(135deg, rgba(247, 250, 255, 0.96), rgba(255, 255, 255, 0.94));
  display: grid;
  gap: 12px;
}

.bridge-feedback__tracker-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.bridge-feedback__tracker-title {
  font-size: 14px;
  font-weight: 700;
  color: #1f3b57;
}

.bridge-feedback__tracker-desc {
  margin-top: 4px;
  font-size: 12px;
  line-height: 1.7;
  color: #62778c;
}

.bridge-feedback__tracker-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.dialog-submit-summary {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin: 0 0 12px;
  padding: 12px 14px;
  border-radius: 14px;
  background: linear-gradient(135deg, rgba(15, 23, 42, 0.92), rgba(30, 41, 59, 0.88));
  color: #e2e8f0;
}

.dialog-submit-summary__main {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.dialog-submit-summary__label {
  font-size: 14px;
  font-weight: 700;
}

.dialog-submit-summary__desc,
.dialog-submit-summary__tip {
  font-size: 12px;
  line-height: 1.6;
  color: rgba(226, 232, 240, 0.82);
}

.dialog-submit-summary__tip {
  max-width: 320px;
  text-align: right;
}

.transfer-manual-input {
  width: 100%;
  display: grid;
  gap: 8px;
}

.transfer-manual-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.transfer-manual-stats {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.transfer-manual-preview {
  border: 1px solid rgba(15, 23, 42, 0.08);
  border-radius: 14px;
  background: linear-gradient(135deg, #f8fbff 0%, #eef6ff 100%);
  padding: 12px 14px;
  display: grid;
  gap: 10px;
}

.transfer-manual-preview__header {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  font-size: 12px;
  color: #5b6475;
}

.transfer-manual-preview__metrics {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
  gap: 10px;
}

.transfer-manual-preview__metric {
  border-radius: 12px;
  padding: 10px 12px;
  background: rgba(255, 255, 255, 0.9);
  border: 1px solid rgba(53, 87, 165, 0.12);
  display: grid;
  gap: 4px;
}

.transfer-manual-preview__metric--accent {
  background: rgba(255, 247, 237, 0.95);
  border-color: rgba(194, 65, 12, 0.16);
}

.transfer-manual-preview__metric-label {
  font-size: 12px;
  line-height: 1.4;
  color: #5b6475;
}

.transfer-manual-preview__metric-value {
  font-size: 16px;
  line-height: 1.2;
  font-weight: 600;
  color: #1f2937;
}

.transfer-manual-preview__items {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.transfer-manual-preview__summary {
  font-size: 12px;
  line-height: 1.5;
  color: #3557a5;
  background: rgba(255, 255, 255, 0.72);
  border-radius: 10px;
  padding: 8px 10px;
}

.transfer-manual-preview__summary--accent {
  color: #7c2d12;
  background: rgba(255, 247, 237, 0.9);
}

.transfer-manual-preview__items span,
.transfer-manual-preview__more {
  padding: 6px 10px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.86);
  color: #1f2937;
  font-size: 12px;
  line-height: 1.4;
}

.transfer-manual-preview__more {
  justify-self: start;
  color: #3557a5;
}

.goods-detail {
  display: grid;
  gap: 18px;
}

.goods-detail__hero {
  display: flex;
  gap: 16px;
  align-items: flex-start;
  padding: 16px;
  border-radius: 18px;
  background: linear-gradient(135deg, #f7fbff, #eef4fb);
}

.goods-detail__hero-meta {
  display: grid;
  gap: 8px;
  flex: 1;
}

.goods-detail__title {
  font-size: 20px;
  font-weight: 700;
  color: #16324f;
}

.goods-detail__code {
  color: #607388;
  font-size: 13px;
}

.goods-detail__tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.goods-detail__grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.goods-detail__card,
.goods-detail__section {
  padding: 14px 16px;
  border-radius: 16px;
  background: #f9fbff;
  border: 1px solid #e4edf7;
}

.goods-detail__label {
  font-size: 12px;
  color: #607388;
}

.goods-detail__value {
  margin-top: 6px;
  font-size: 18px;
  font-weight: 700;
  color: #16324f;
}

.goods-detail__section-title {
  font-size: 14px;
  font-weight: 700;
  color: #173858;
}

.goods-detail__rows {
  margin-top: 12px;
  display: grid;
  gap: 10px;
}

.goods-detail__row {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  color: #5f7387;
}

.goods-detail__row strong {
  color: #16324f;
  text-align: right;
}

.goods-detail__text {
  margin-top: 12px;
  line-height: 1.8;
  color: #42576b;
}

.goods-detail__alert {
  margin-top: 12px;
  padding: 12px 14px;
  border-radius: 14px;
  background: #fff4f4;
  color: #c2410c;
}

@media (max-width: 1600px) {
  .action-groups,
  .hero-shortcuts,
  .hero-guide-grid,
  .bridge-steps {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .bridge-insight-grid,
  .metrics-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }

  .bridge-command-center {
    grid-template-columns: 1fr;
  }

  .batch-bridge__controls {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .bridge-permissions__items,
  .operation-diff__grid,
  .selection-review-panel__metrics,
  .selection-review-focus__items,
  .bridge-exception-tracker__grid,
  .transfer-submit-panel__risk-grid,
  .transfer-submit-panel__metrics {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 992px) {
  .dialog-submit-summary {
    flex-direction: column;
    align-items: flex-start;
  }

  .compact-workbench {
    flex-direction: column;
    align-items: flex-start;
  }

  .dialog-submit-summary__tip {
    max-width: none;
    text-align: left;
  }

  .panel__header,
  .panel__header-bar {
    flex-direction: column;
  }

  .hero-decision-strip {
    flex-direction: column;
  }

  .hero-decision-strip__tags {
    justify-content: flex-start;
  }

  .panel__actions,
  .panel__toolbar {
    justify-content: flex-start;
  }

  .hero-shortcuts,
  .hero-guide-grid,
  .action-groups,
  .bridge-steps,
  .quick-batch-library__grid,
  .bridge-summary__grid,
  .bridge-insight-grid,
  .bridge-command-center,
  .bridge-history-overview,
  .bridge-history-priority,
  .bridge-target-library,
  .metrics-grid,
  .batch-bridge__controls,
  .selection-review-panel__metrics,
  .selection-review-focus__items,
  .bridge-exception-tracker__grid,
  .transfer-submit-panel__risk-grid {
    grid-template-columns: 1fr;
  }

  .bridge-timeline-strip {
    grid-template-columns: 1fr;
  }

  .bridge-history__item {
    flex-direction: column;
    align-items: flex-start;
  }

  .bridge-history-group__header {
    align-items: flex-start;
  }

  .selection-review-panel__header {
    flex-direction: column;
    align-items: flex-start;
  }

  .bridge-history__search,
  .bridge-history__filter {
    width: 100%;
  }

  .bridge-history__stats-inline {
    gap: 6px;
  }

  .goods-detail__hero,
  .goods-detail__grid {
    grid-template-columns: 1fr;
  }

  .goods-detail__hero {
    flex-direction: column;
  }
}
</style>
