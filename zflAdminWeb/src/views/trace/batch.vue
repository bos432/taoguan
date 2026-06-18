<template>
  <div class="app-container">
    <div class="page-overview">
      <div class="overview-card">
        <span class="overview-label">批次数量</span>
        <strong>{{ count || 0 }}</strong>
        <p>覆盖待审核、已通过、已拒绝等全部批次。</p>
      </div>
      <div class="overview-card">
        <span class="overview-label">当前分组</span>
        <strong>{{ currentTabLabel }}</strong>
        <p>{{ currentFilterSummary }}</p>
      </div>
      <div class="overview-card">
        <span class="overview-label">批量状态</span>
        <strong>{{ selection.length }} 项已选</strong>
        <p>{{ traceFollowupHint }}</p>
      </div>
      <div class="overview-card">
        <span class="overview-label">称重入库</span>
        <strong>{{ weighingSummary }}</strong>
        <p>{{ traceFollowupRiskText }}</p>
      </div>
    </div>
    <el-card class="app-head head-pb20">
      <div class="section-title section-title--inline">
        <div class="section-title__main">
          <div class="section-title__eyebrow">筛选</div>
          <p>保留审核、称重、入库和溯源联动能力，首屏信息收敛为更轻量的线上摘要。</p>
        </div>
        <div class="summary-badge" :class="runtimeModeBadgeClass">{{ runtimeModeLabel }}</div>
      </div>
      <!-- 查询 -->
      <el-form :model="query" ref="searchForm" label-width="85px">
        <el-row :gutter="20">
          <el-col :span="6">
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
          <el-col :span="6">
            <el-form-item label="状态：" prop="is_disable">
              <el-select v-model="query.is_disable" @change="search()" clearable>
                <el-option :value="0" label="启用" />
                <el-option :value="1" label="禁用" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="称重入库：" prop="is_weighing_warehousing">
              <el-select v-model="query.is_weighing_warehousing" @change="search()" clearable>
                <el-option :value="0" label="否" />
                <el-option :value="1" label="是" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="称重状态：" prop="is_weighing">
              <el-select v-model="query.is_weighing" @change="search()" clearable>
                <el-option :value="0" label="待称重" />
                <el-option :value="1" label="已称重" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="入库状态：" prop="is_warehousing">
              <el-select v-model="query.is_warehousing" @change="search()" clearable>
                <el-option :value="0" label="待入库" />
                <el-option :value="1" label="已入库" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="称重设备：" prop="setting_call_id">
              <el-cascader
                v-model="query.setting_call_id"
                :options="params.call_list"
                :props="props"
                class="w-full"
                placeholder="请选择称重设备"
                clearable
                filterable
                @change="search()"
              />
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="存储仓库：" prop="setting_warehouse_id">
              <el-cascader
                v-model="query.setting_warehouse_id"
                :options="params.warehouse_list"
                :props="props"
                class="w-full"
                placeholder="请选择存储仓库"
                clearable
                filterable
                @change="search()"
              />
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="商品：" prop="goods_id">
              <el-select v-model="query.goods_id" @change="search()" clearable filterable>
                <el-option
                  v-for="item in params.goods_list"
                  :key="item.value"
                  :label="item.label"
                  :value="item.value"
                />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-input
              v-model="query.search_value"
              placeholder="请输入内容"
              class="input-with-select"
              @keyup.enter="search()"
              clearable
            >
              <template #prepend>
                <el-select v-model="query.search_field" placeholder="Select" style="width: 100px">
                  <el-option :value="idkey" label="ID" />
                  <el-option value="title" label="批次号" />
                  <el-option value="remark" label="备注" />
                </el-select>
              </template>
            </el-input>
          </el-col>
          <el-col :span="6">
            <el-button type="primary" @click="search()">搜索</el-button>
            <el-button title="重置" @click="refresh()"> 重置 </el-button>
          </el-col>
        </el-row>
      </el-form>
      <div class="summary-panel">
        <div class="summary-panel__head">
          <div class="summary-panel__title">批次摘要</div>
          <div class="summary-panel__status" :class="traceFollowupBadgeClass">
            {{ traceFollowupBadgeText }}
          </div>
        </div>
        <div class="summary-panel__meta">
          <span v-for="item in summaryMetaTags" :key="item" class="summary-chip">{{ item }}</span>
        </div>
        <div v-if="recentActionSummary" class="summary-panel__tip">
          <span class="summary-panel__tip-label">最近操作</span>
          <span class="summary-panel__tip-text">{{ recentActionSummary }}</span>
        </div>
        <p class="summary-panel__desc">{{ traceFollowupHint }}</p>
        <p class="summary-panel__risk">{{ traceFollowupRiskText }}</p>
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
        <el-form-item v-if="selectType === 'disable'" label="是否禁用">
          <el-switch v-model="is_disable" :active-value="1" :inactive-value="0" />
        </el-form-item>
        <el-form-item v-else-if="selectType === 'dele'">
          <span class="c-red">确定要删除选中的{{ name }}吗？</span>
        </el-form-item>
        <template v-else-if="selectType === 'auth'">
          <el-form-item label="审核状态">
            <el-radio-group v-model="auth_status">
              <el-radio :value="1">审核通过</el-radio>
              <el-radio :value="2">审核拒绝</el-radio>
            </el-radio-group>
          </el-form-item>
          <el-form-item label="拒绝原因" v-if="auth_status == 2">
            <el-input v-model="auth_msg" placeholder="请填写拒绝原因" clearable />
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
      <div class="section-title section-title--compact">
        <div>
          <h3>批次列表</h3>
          <p>支持分状态审核、称重入库筛选和溯源信息查看。</p>
        </div>
      </div>
      <el-tabs v-model="activeName" @tab-click="handleClick">
        <el-tab-pane label="全部" :name="-1">
          <template #label>
            <span>
              全部
              <el-badge v-if="status_nums.all > 0" :value="status_nums.all" :max="99999999" />
            </span>
          </template>
        </el-tab-pane>
        <template v-for="(item, index) in params.auth_status">
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
      <div class="operation_btn mb5">
        <!-- 操作 -->
        <div>
          <!--          <el-button type="primary" @click="add()">添加</el-button>-->
          <el-button title="删除" @click="selectOpen('dele')">删除</el-button>
          <el-button title="是否禁用" @click="selectOpen('disable')">禁用</el-button>
          <el-button title="批量审核" @click="selectOpen('auth')">批量审核</el-button>
        </div>
        <div>
          <!-- 分页 -->
          <pagination
            v-show="count > 0"
            v-model:total="count"
            v-model:page="query.page"
            v-model:limit="query.limit"
            @pagination="list"
          />
        </div>
      </div>
      <!-- 列表 -->
      <el-table
        ref="table"
        v-loading="loading"
        :data="data"
        @sort-change="sort"
        @selection-change="select"
        @cell-dblclick="cellDbclick"
      >
        <el-table-column type="selection" width="42" title="全选/反选" />
        <el-table-column :prop="idkey" label="ID" width="80" sortable="custom" />
        <el-table-column
          prop="merchant_title"
          label="商家名称"
          min-width="100"
          show-overflow-tooltip
        />
        <el-table-column
          prop="title"
          label="批次号"
          min-width="120"
          sortable="custom"
          show-overflow-tooltip
        />
        <el-table-column prop="goods_title" label="商品" min-width="150" show-overflow-tooltip />
        <el-table-column prop="goods_num" label="申请数量" min-width="80" show-overflow-tooltip />
        <el-table-column prop="weighing_num" label="称重数量" min-width="80" show-overflow-tooltip>
          <template #default="scope">
            <template v-if="scope.row.is_weighing_warehousing == 1 && scope.row.is_weighing == 1">
              {{ scope.row.weighing_num }}
            </template>
            <template v-else> </template>
          </template>
        </el-table-column>
        <el-table-column
          prop="warehousing_num"
          label="入库数量"
          min-width="80"
          show-overflow-tooltip
        >
          <template #default="scope">
            <template
              v-if="scope.row.is_weighing_warehousing == 1 && scope.row.is_warehousing == 1"
            >
              {{ scope.row.warehousing_num }}
            </template>
            <template v-else> </template>
          </template>
        </el-table-column>
        <el-table-column prop="auth_status" label="审核状态" min-width="80" show-overflow-tooltip>
          <template #default="scope">
            <el-tag type="info" v-if="scope.row.auth_status == 0">待审核</el-tag>
            <el-tag type="success" v-else-if="scope.row.auth_status == 1">审核通过</el-tag>
            <el-tag type="danger" v-else-if="scope.row.auth_status == 2">审核失败</el-tag>
            <div v-if="scope.row.auth_status == 2 && scope.row.auth_msg">
              <el-text type="danger">{{ scope.row.auth_msg }}</el-text>
            </div>
          </template>
        </el-table-column>
        <el-table-column
          prop="is_weighing_warehousing"
          label="称重入库"
          min-width="80"
          show-overflow-tooltip
        >
          <template #default="scope">
            <el-tag type="success" v-if="scope.row.is_weighing_warehousing == 1">是</el-tag>
            <el-tag type="info" v-else>否</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="call_title" label="称重设备" min-width="100" show-overflow-tooltip />
        <el-table-column prop="is_weighing" label="称重状态" min-width="80" show-overflow-tooltip>
          <template #default="scope">
            <template v-if="scope.row.is_weighing_warehousing == 1">
              <el-tag type="success" v-if="scope.row.is_weighing == 1">已称重</el-tag>
              <el-tag type="info" v-else>待称重</el-tag>
            </template>
            <template v-else> </template>
          </template>
        </el-table-column>
        <el-table-column
          prop="warehouse_title"
          label="存储仓库"
          min-width="100"
          show-overflow-tooltip
        />
        <el-table-column
          prop="is_warehousing"
          label="入库状态"
          min-width="80"
          show-overflow-tooltip
        >
          <template #default="scope">
            <template v-if="scope.row.is_weighing_warehousing == 1">
              <el-tag type="success" v-if="scope.row.is_warehousing == 1">已入库</el-tag>
              <el-tag type="info" v-else>待入库</el-tag>
            </template>
            <template v-else> </template>
          </template>
        </el-table-column>
        <el-table-column prop="remark" label="备注" min-width="150" show-overflow-tooltip />
        <el-table-column prop="is_disable" label="禁用" min-width="85" sortable="custom">
          <template #default="scope">
            <el-switch
              v-model="scope.row.is_disable"
              :active-value="1"
              :inactive-value="0"
              @change="disable([scope.row])"
            />
          </template>
        </el-table-column>
        <el-table-column prop="sort" label="排序" min-width="85" sortable="custom" />
        <el-table-column prop="create_time" label="添加时间" width="165" sortable="custom" />
        <el-table-column prop="update_time" label="修改时间" width="165" sortable="custom" />
        <el-table-column label="操作" width="125" fixed="right">
          <template #default="scope">
            <el-link
              type="primary"
              class="mr-1"
              :underline="false"
              @click="get_batch_tache(scope.row.id)"
            >
              溯源
            </el-link>
            <el-link type="primary" class="mr-1" :underline="false" @click="edit(scope.row)">
              修改
            </el-link>
            <el-link
              :disabled="scope.row.auth_status == 1"
              type="primary"
              :underline="false"
              @click="selectOpen('dele', [scope.row])"
            >
              删除
            </el-link>
          </template>
        </el-table-column>
      </el-table>
    </el-card>
    <!-- 添加修改 -->
    <el-dialog
      v-model="dialog"
      :title="dialogTitle"
      :close-on-click-modal="false"
      :close-on-press-escape="false"
      :before-close="cancel"
      top="5vh"
      destroy-on-close
    >
      <el-form ref="ref" :rules="rules" :model="model" label-width="100px">
        <el-scrollbar native :max-height="height - 30">
          <el-form-item label="批次号" prop="title">
            <el-input
              v-model="model.title"
              placeholder="请输入批次号"
              clearable
              :disabled="model.auth_status == 1"
            >
              <template #append>
                <el-button title="复制" @click="copy(model.title)">
                  <svg-icon icon-class="copy-document" />
                </el-button>
              </template>
            </el-input>
          </el-form-item>
          <el-form-item label="商品" prop="goods_id">
            <el-select
              v-model="model.goods_id"
              @change="goodsChange"
              clearable
              filterable
              :disabled="model.auth_status == 1"
            >
              <el-option
                v-for="item in params.goods_list"
                :key="item.value"
                :label="item.label"
                :value="item.value"
                :disabled="item.disabled == 1"
              />
            </el-select>
          </el-form-item>
          <el-form-item label="数量" prop="goods_num">
            <el-input-number
              :controls="true"
              style="width: 100%"
              v-model="model.goods_num"
              placeholder="请输入数量"
              :min="1"
              :precision="0"
              :step="1"
              :disabled="model.auth_status == 1"
            />
          </el-form-item>
          <el-form-item label="备注" prop="remark">
            <el-input v-model="model.remark" placeholder="请输入备注" clearable />
          </el-form-item>
          <el-form-item label="排序" prop="sort">
            <el-input-number
              :controls="true"
              style="width: 100%"
              v-model="model.sort"
              placeholder="请输入排序"
              :min="0"
              :precision="0"
              :step="1"
            />
          </el-form-item>
          <el-form-item label="称重入库" prop="is_weighing_warehousing">
            <el-switch
              v-model="model.is_weighing_warehousing"
              class="ml-2"
              inline-prompt
              active-text="是"
              inactive-text="否"
              :active-value="1"
              :inactive-value="0"
              :disabled="model.auth_status == 1"
            />
          </el-form-item>
          <el-form-item
            label="称重设备"
            prop="setting_call_id"
            v-if="model.is_weighing_warehousing == 1"
          >
            <el-cascader
              v-model="model.setting_call_id"
              :options="call_list"
              :props="props"
              class="w-full"
              placeholder="请选择称重设备"
              clearable
              filterable
              :disabled="model.auth_status == 1"
            />
            <el-text type="info" class="tip" v-if="selectedDeviceAddress">
              <el-icon><InfoFilled /></el-icon>
              {{ selectedDeviceAddress }}
            </el-text>
          </el-form-item>
          <el-form-item
            label="存放仓库"
            prop="setting_warehouse_id"
            v-if="model.is_weighing_warehousing == 1"
          >
            <el-cascader
              v-model="model.setting_warehouse_id"
              :options="warehouse_list"
              :props="props"
              class="w-full"
              placeholder="请选择存放仓库"
              clearable
              filterable
              :disabled="model.auth_status == 1"
            />
            <el-text type="info" class="tip" v-if="selectedWarehouseAddress">
              <el-icon><InfoFilled /></el-icon>
              {{ selectedWarehouseAddress }}
            </el-text>
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
          <el-form-item v-if="model.auth_time" label="审核时间" prop="auth_time">
            <el-input v-model="model.auth_time" disabled />
          </el-form-item>
          <el-form-item
            v-if="model[idkey] > 0 && model.is_weighing_warehousing == 1 && model.weighing_time"
            label="称重时间"
            prop="weighing_time"
          >
            <el-input v-model="model.weighing_time" disabled />
          </el-form-item>
          <el-form-item
            v-if="model[idkey] > 0 && model.is_weighing_warehousing == 1 && model.warehousing_time"
            label="入库时间"
            prop="warehousing_time"
          >
            <el-input v-model="model.warehousing_time" disabled />
          </el-form-item>
        </el-scrollbar>
      </el-form>
      <template #footer>
        <el-button :loading="loading" @click="cancel">取消</el-button>
        <el-button
          :loading="loading"
          type="primary"
          @click="submit"
          :disabled="model.auth_status == 1"
          >提交</el-button
        >
      </template>
    </el-dialog>
    <!-- 查看溯源信息 -->
    <el-dialog
      v-model="traceDialog"
      title="溯源信息"
      :close-on-click-modal="false"
      :close-on-press-escape="false"
      :before-close="traceCancel"
      top="5vh"
      destroy-on-close
    >
      <el-descriptions border>
        <el-descriptions-item :rowspan="2" :width="140" label="商品" align="center">
          <el-image
            style="width: 100px; height: 100px"
            :src="trace_info.batch.goods.image.file_url"
          />
        </el-descriptions-item>
        <el-descriptions-item label="商品编码">{{
          trace_info.batch.goods.code
        }}</el-descriptions-item>
        <el-descriptions-item label="商品名称">{{
          trace_info.batch.goods.title
        }}</el-descriptions-item>
        <el-descriptions-item label="商品规格">{{
          trace_info.batch.goods.spec
        }}</el-descriptions-item>
        <el-descriptions-item label="计量单位">{{
          trace_info.batch.goods.unit
        }}</el-descriptions-item>
      </el-descriptions>
      <template v-for="(item, index) in trace_info.list">
        <el-descriptions column="1" border>
          <el-descriptions-item
            :label="item.tache_title"
            label-width="50%"
            label-class-name="my_label"
            class-name="my_label"
          ></el-descriptions-item>
          <template v-for="(obj, k) in item.tacheValue">
            <el-descriptions-item :label="obj.label">
              <template v-if="obj.is_inspection_type == 1 && obj.inspection_id">
                <el-tag type="info" v-if="obj.inspection_state == 0">待检测</el-tag>
                <el-tag type="success" v-else-if="obj.inspection_state == 1">已检测</el-tag>
                <el-tag type="danger" v-if="obj.inspection_state == 2">
                  检测失败
                  <template v-if="obj.inspection_remark"> ({{ obj.inspection_remark }}) </template>
                </el-tag>
                {{ obj.inspection_title }}
                {{ obj.inspection_result || obj.inspection_time ? '(' : '' }}
                {{ obj.inspection_result ? '检测结果:' + obj.inspection_result : '' }}
                {{ obj.inspection_time ? ',检测时间:' + obj.inspection_time : '' }}
                {{ obj.inspection_result || obj.inspection_time ? ')' : '' }}
              </template>
              <template v-else>
                {{ obj.value }}
              </template>
              <template
                v-if="
                  (obj.inspection_reports && obj.inspection_reports.length > 0) ||
                  (obj.reports && obj.reports.length > 0)
                "
              >
                【
                <template v-for="(item, index) in obj.inspection_reports">
                  <el-link type="primary" :href="item.file_url" target="_blank">{{
                    item.file_name + '.' + item.file_ext
                  }}</el-link>
                </template>
                <template v-for="(item, index) in obj.reports">
                  <el-link type="primary" :href="item.file_url" target="_blank">{{
                    item.file_name + '.' + item.file_ext
                  }}</el-link>
                </template>
                】
              </template>
            </el-descriptions-item>
          </template>
        </el-descriptions>
      </template>
    </el-dialog>
  </div>
</template>

<script>
import screenHeight from '@/utils/screen-height'
import Pagination from '@/components/Pagination/index.vue'
import RichEditor from '@/components/RichEditor/index.vue'
import clip from '@/utils/clipboard'
import { arrayColumn } from '@/utils/index'
import { getPageLimit } from '@/utils/settings'
import {
  list,
  info,
  add,
  edit,
  dele,
  disable,
  params,
  auth as is_auth,
  getBatchTache
} from '@/api/trace/batch'
import { InfoFilled } from '@element-plus/icons-vue'
import { shortcuts } from '@/utils/getDate.js'
import { resolveAdminRuntimeEnv } from '@/utils/runtime-env'

export default {
  name: 'batch',
  components: { InfoFilled, Pagination, RichEditor },
  data() {
    return {
      name: '批次',
      height: 680,
      loading: false,
      idkey: 'id',
      exps: [{ exp: 'like', name: '包含' }],
      query: {
        page: 1,
        limit: getPageLimit(),
        search_field: 'title',
        search_exp: 'like',
        date_field: 'create_time',
        is_disable: undefined,
        goods_id: undefined,
        auth_status: undefined,
        is_weighing_warehousing: undefined,
        is_weighing: undefined,
        is_warehousing: undefined,
        setting_call_id: undefined,
        setting_warehouse_id: undefined
      },
      data: [],
      count: 0,
      dialog: false,
      dialogTitle: '',
      model: {
        id: '',
        title: '',
        goods_id: undefined,
        goods_num: 0, //数量
        remark: '',
        sort: 250,
        is_weighing_warehousing: undefined,
        setting_call_id: undefined,
        setting_warehouse_id: undefined,
        auth_status: undefined
      },
      rules: {
        title: [{ required: true, message: '请输入批次号', trigger: 'blur' }],
        goods_id: [{ required: true, message: '请选择商品', trigger: 'blur' }],
        goods_num: [{ required: true, message: '请填写商品数量', trigger: 'blur' }]
      },
      selection: [],
      selectIds: '',
      selectTitle: '操作',
      selectDialog: false,
      selectType: '',
      is_disable: 0,
      max_no: '',
      params: {},
      props: { checkStrictly: false, value: 'id', label: 'title', emitPath: false },
      call_list: [],
      warehouse_list: [],
      //选项卡
      activeName: -1,
      status_nums: {},
      //商品审核
      auth_status: 1,
      auth_msg: '',
      //溯源信息
      traceDialog: false,
      traceLoading: false,
      trace_info: {},
      shortcuts: shortcuts(),
      recentActionSummary: '',
      runtimeEnvInfo: resolveAdminRuntimeEnv()
    }
  },
  created() {
    this.height = screenHeight()
    this.getParams()
    this.list()
  },
  computed: {
    runtimeModeLabel() {
      return this.runtimeEnvInfo.label
    },
    runtimeModeBadgeClass() {
      return this.runtimeEnvInfo.isProd ? 'is-production' : 'is-development'
    },
    currentTabLabel() {
      if (Number(this.activeName) === -1) {
        return '全部批次'
      }
      const authStatus = Array.isArray(this.params.auth_status) ? this.params.auth_status : []
      const current = authStatus.find((item) => Number(item.value) === Number(this.activeName))
      return current ? current.label : '状态筛选'
    },
    currentFilterSummary() {
      const parts = []
      if (this.query.goods_id) {
        const goodsList = Array.isArray(this.params.goods_list) ? this.params.goods_list : []
        const goods = goodsList.find((item) => Number(item.value) === Number(this.query.goods_id))
        if (goods) {
          parts.push(`商品：${goods.label}`)
        }
      }
      if (this.query.search_value) {
        parts.push(`关键词：${this.query.search_value}`)
      }
      if (this.query.is_disable === 1) {
        parts.push('仅看禁用')
      } else if (this.query.is_disable === 0) {
        parts.push('仅看启用')
      }
      return parts.length ? parts.join(' / ') : '未设置额外筛选条件'
    },
    weighingSummary() {
      const parts = []
      if (this.query.is_weighing === 1) {
        parts.push('已称重')
      } else if (this.query.is_weighing === 0) {
        parts.push('待称重')
      }
      if (this.query.is_warehousing === 1) {
        parts.push('已入库')
      } else if (this.query.is_warehousing === 0) {
        parts.push('待入库')
      }
      if (this.query.is_weighing_warehousing === 1) {
        parts.push('称重入库')
      }
      return parts.length ? parts.join(' / ') : '全部状态'
    },
    activeFilterTags() {
      const tags = [`分组：${this.currentTabLabel}`]
      if (Array.isArray(this.query.date_value) && this.query.date_value.length === 2) {
        tags.push(`时间：${this.query.date_value[0]} 至 ${this.query.date_value[1]}`)
      }
      if (this.query.goods_id) {
        const goodsList = Array.isArray(this.params.goods_list) ? this.params.goods_list : []
        const goods = goodsList.find((item) => Number(item.value) === Number(this.query.goods_id))
        if (goods) {
          tags.push(`商品：${goods.label}`)
        }
      }
      if (this.query.search_value) {
        tags.push(`检索：${this.query.search_value}`)
      }
      if (
        this.query.is_weighing_warehousing !== undefined &&
        this.query.is_weighing_warehousing !== ''
      ) {
        tags.push(`称重入库：${Number(this.query.is_weighing_warehousing) === 1 ? '是' : '否'}`)
      }
      return tags
    },
    traceFollowupBadgeText() {
      if (this.selection.length > 0) return '待提交复核'
      if (this.activeFilterTags.length > 1) return '筛选已收敛'
      return '默认巡检'
    },
    traceFollowupBadgeClass() {
      if (this.selection.length > 0) return 'is-active'
      if (
        this.query.is_weighing === 0 ||
        this.query.is_warehousing === 0 ||
        this.query.auth_status === 0
      ) {
        return 'is-warning'
      }
      return 'is-safe'
    },
    traceFollowupHint() {
      if (this.selection.length > 0) {
        return `当前已勾选 ${this.selection.length} 个批次，可继续做审核、禁用和删除处理，但建议先复核称重入库状态。`
      }
      if (this.activeFilterTags.length > 1) {
        return '当前批次列表已经按筛选条件收敛，建议先抽查溯源信息与审核状态，再继续批量处理。'
      }
      return '当前为批次巡检视角，建议优先按审核状态、称重状态、入库状态和商品筛选收敛范围。'
    },
    traceFollowupTags() {
      const pendingAuth = Array.isArray(this.data)
        ? this.data.filter((item) => Number(item.auth_status) === 0).length
        : 0
      const pendingWeighing = Array.isArray(this.data)
        ? this.data.filter(
            (item) => Number(item.is_weighing_warehousing) === 1 && Number(item.is_weighing) === 0
          ).length
        : 0
      const pendingWarehousing = Array.isArray(this.data)
        ? this.data.filter(
            (item) =>
              Number(item.is_weighing_warehousing) === 1 && Number(item.is_warehousing) === 0
          ).length
        : 0
      return [
        `已选批次：${this.selection.length} 项`,
        `待审核：${pendingAuth} 项`,
        `待称重：${pendingWeighing} 项`,
        `待入库：${pendingWarehousing} 项`
      ]
    },
    summaryMetaTags() {
      return [...this.activeFilterTags, ...this.traceFollowupTags]
    },
    traceFollowupRiskText() {
      if (this.selection.some((item) => Number(item.auth_status) === 0)) {
        return '当前勾选中包含待审核批次，提交审核前请再次核对批次号、商品、申请数量和审核意见。'
      }
      if (
        this.selection.some(
          (item) => Number(item.is_weighing_warehousing) === 1 && Number(item.is_weighing) === 0
        )
      ) {
        return '当前勾选中包含待称重批次，建议先完成称重数据回填，再继续做后续审核或删除。'
      }
      return this.runtimeEnvInfo.isProd
        ? '正式环境中的批次审核、禁用和删除会影响真实溯源链路，请先通过筛选缩小范围，再执行批量处理。'
        : '联调环境适合验证批次审核、称重、入库和溯源信息展示，不建议把测试批次当成正式经营数据。'
    },
    selectedDeviceAddress() {
      // 如果没有选择设备，返回 null
      if (!this.model.setting_call_id) {
        return null
      }

      // 定义一个递归函数来查找选中的设备
      const findDevice = (list, id) => {
        for (const item of list) {
          // 如果当前项的 id 匹配，返回地址
          if (item.id === id) {
            return item.address
          }
          // 如果当前项有子集，递归查找子集
          if (item.children) {
            const found = findDevice(item.children, id)
            if (found) {
              return found
            }
          }
        }
        return null // 没有找到匹配项
      }

      // 调用递归查找函数
      return findDevice(this.call_list, this.model.setting_call_id)
    },
    selectedWarehouseAddress() {
      // 如果没有选择设备，返回 null
      if (!this.model.setting_warehouse_id) {
        return null
      }

      // 定义一个递归函数来查找选中的设备
      const findDevice = (list, id) => {
        for (const item of list) {
          // 如果当前项的 id 匹配，返回地址
          if (item.id === id) {
            return item.address
          }
          // 如果当前项有子集，递归查找子集
          if (item.children) {
            const found = findDevice(item.children, id)
            if (found) {
              return found
            }
          }
        }
        return null // 没有找到匹配项
      }

      // 调用递归查找函数
      return findDevice(this.warehouse_list, this.model.setting_warehouse_id)
    }
  },
  methods: {
    //查询溯源信息
    get_batch_tache(id) {
      this.traceLoading = true
      getBatchTache({ id: id })
        .then((res) => {
          this.trace_info = res.data
          this.traceDialog = true
          this.traceLoading = false
        })
        .catch(() => {
          this.traceLoading = false
        })
    },
    //关闭溯源信息
    traceCancel() {
      this.traceDialog = false
    },
    //选项卡选择
    handleClick(e) {
      this.query.page = 1
      this.query.auth_status = parseInt(e.paneName)
      this.list()
    },
    //商品选择
    goodsChange(is_caler = true) {
      if (is_caler) {
        this.model.setting_call_id = undefined
        this.model.setting_warehouse_id = undefined
      }
      let goods = this.params.goods_list.find((item) => item.value === Number(this.model.goods_id))
      if (goods) {
        this.call_list = goods.call_list
        this.warehouse_list = goods.warehouse_list
      } else {
        this.call_list = this.params.call_list
        this.warehouse_list = this.params.warehouse_list
      }
    },
    // 参数
    getParams() {
      this.loading = true
      params({})
        .then((res) => {
          this.params = res.data
          this.call_list = this.params.call_list
          this.warehouse_list = this.params.warehouse_list
          this.recentActionSummary = `批次参数已加载，当前环境：${this.runtimeModeLabel}。`
          this.loading = false
        })
        .catch(() => {
          this.loading = false
        })
    },
    // 列表
    list() {
      this.loading = true
      list(this.query)
        .then((res) => {
          this.data = res.data.list
          this.count = res.data.count
          this.exps = res.data.exps
          this.max_no = res.data.max_no
          this.status_nums = res.data.statistics
          this.recentActionSummary = `批次列表已刷新，共 ${res.data.count || 0} 条，当前分组：${
            this.currentTabLabel
          }。`
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
      this.model.title = this.max_no
    },
    edit(row) {
      this.dialog = true
      this.dialogTitle = this.name + '修改：' + row[this.idkey]
      var id = {}
      id[this.idkey] = row[this.idkey]
      info(id)
        .then((res) => {
          this.reset(res.data)
          this.goodsChange(false)
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
                this.recentActionSummary = `批次已更新：${this.model.title || '未命名批次'}。`
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
                this.recentActionSummary = `批次已新增：${this.model.title || '未命名批次'}。`
                ElMessage.success(res.msg)
              })
              .catch(() => {
                this.loading = false
              })
          }
        } else {
          // ElMessage.error('请完善必填项（带红色星号*）')
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
        if (selectType === 'disable') {
          this.selectTitle = this.name + '是否禁用'
        } else if (selectType === 'dele') {
          this.selectTitle = this.name + '删除'
        } else if (selectType === 'auth') {
          this.selectTitle = this.name + '审核'
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
        if (selectType === 'disable') {
          this.disable(this.selection, true)
        } else if (selectType === 'dele') {
          this.dele(this.selection)
        } else if (selectType === 'auth') {
          this.auth(this.selection, true)
        }
        this.selectDialog = false
      }
    },
    // 批次审核
    auth(row, select = false) {
      if (!row.length) {
        this.selectAlert()
      } else {
        this.loading = true
        is_auth({
          ids: this.selectGetIds(row),
          auth_status: this.auth_status,
          auth_msg: this.auth_msg
        })
          .then((res) => {
            this.list()
            this.recentActionSummary = `批次审核已提交：${row.length} 项。`
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.list()
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
            this.recentActionSummary = `${is_disable === 1 ? '批次禁用' : '批次启用'}已提交：${
              row.length
            } 项。`
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
        this.loading = true
        dele({
          ids: this.selectGetIds(row)
        })
          .then((res) => {
            this.list()
            this.recentActionSummary = `批次删除已提交：${row.length} 项。`
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.loading = false
          })
      }
    },
    // 复制
    copy(text) {
      clip(text)
    },
    // 单元格双击复制
    cellDbclick(row, column) {
      this.copy(row[column.property])
    }
  }
}
</script>
<style scoped>
.summary-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 28px;
  padding: 0 12px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 700;
  white-space: nowrap;
  color: #1d4ed8;
  background: #e8f0ff;
}

.summary-badge.is-production {
  color: #b45309;
  background: #fff5e8;
}

.summary-badge.is-development {
  color: #1d4ed8;
  background: #e8f0ff;
}

.summary-panel {
  margin-top: 14px;
  padding-top: 14px;
  border-top: 1px solid #edf1f7;
}

.summary-panel__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.summary-panel__title {
  font-size: 14px;
  font-weight: 600;
  color: #1f2329;
}

.summary-panel__status {
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  padding: 0 10px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 700;
  color: #315efb;
  background: #eef3ff;
}

.summary-panel__status.is-safe {
  color: #15803d;
  background: #eaf8ef;
}

.summary-panel__status.is-warning {
  color: #b45309;
  background: #fff5e8;
}

.summary-panel__status.is-active {
  color: #1d4ed8;
  background: #e8f0ff;
}

.summary-panel__meta {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 12px;
}

.summary-chip,
.summary-panel__tip-label,
.summary-panel__tip-text {
  display: inline-flex;
  align-items: center;
  min-height: 26px;
  padding: 0 10px;
  border-radius: 999px;
  font-size: 12px;
}

.summary-chip,
.summary-panel__tip-text {
  color: #4a5670;
  background: #f5f7fb;
}

.summary-panel__tip {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 10px;
}

.summary-panel__tip-label {
  color: #315efb;
  font-weight: 600;
  background: #eef3ff;
}

.summary-panel__desc,
.summary-panel__risk {
  margin-top: 12px;
  color: #596273;
  font-size: 13px;
  line-height: 1.6;
}

.page-overview {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 12px;
  margin-bottom: 14px;
}

.overview-card {
  padding: 14px 16px;
  border: 1px solid #edf1f7;
  border-radius: 12px;
  background: #ffffff;
  box-shadow: 0 6px 18px rgba(31, 35, 41, 0.04);
}

.overview-label {
  display: inline-block;
  margin-bottom: 8px;
  color: #7a8599;
  font-size: 12px;
}

.overview-card strong {
  display: block;
  color: #1f2329;
  font-size: 18px;
}

.overview-card p {
  margin: 6px 0 0;
  color: #5f6b7a;
  font-size: 13px;
  line-height: 1.5;
}

.section-title {
  margin-bottom: 14px;
}

.section-title--inline {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.section-title__main {
  min-width: 0;
}

.section-title h3 {
  margin: 0 0 4px;
  font-size: 18px;
  color: #1f2329;
}

.section-title__eyebrow {
  margin: 0 0 4px;
  font-size: 13px;
  font-weight: 600;
  color: #475569;
}

.section-title p {
  margin: 0;
  color: #7a8599;
  font-size: 13px;
}

.section-title--compact {
  margin-bottom: 10px;
}

.my_label {
  background: #009d0e !important;
  color: #ffffff !important;
}

@media (max-width: 1200px) {
  .section-title--inline,
  .summary-panel__head {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>
