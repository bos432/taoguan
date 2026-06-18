<template>
  <div class="app-container">
    <el-card class="app-head">
      <div class="section-title-row">
        <div>
          <div class="section-title-row__title">地区管理</div>
          <div class="section-title-row__desc">
            统一处理行政区划筛选、树形维护、区号邮编调整和状态管理。
          </div>
        </div>
        <div class="section-title-row__meta">{{ runtimeEnvInfo.label }}</div>
      </div>
      <div v-if="entryContextVisible" class="entry-context-banner">
        <div class="entry-context-banner__main">
          <div class="entry-context-banner__eyebrow">外部入口承接</div>
          <div class="entry-context-banner__title">{{ entryContextTitle }}</div>
          <div class="entry-context-banner__desc">{{ entryContextDesc }}</div>
        </div>
        <div class="entry-context-banner__actions">
          <el-button type="primary" @click="handleEntryContextPrimary">{{
            entryContextPrimaryLabel
          }}</el-button>
          <el-button @click="goToEntryContextBack">回来源页</el-button>
        </div>
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
            <el-form-item label="上级：" prop="region_pid">
              <el-cascader
                v-model="query.region_pid"
                :options="trees"
                :props="props"
                clearable
                filterable
              />
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
                  <el-option value="region_name" label="名称" />
                  <el-option value="region_pinyin" label="拼音" />
                  <el-option value="region_jianpin" label="简拼" />
                  <el-option value="region_initials" label="首字母" />
                  <el-option value="region_citycode" label="区号" />
                  <el-option value="region_zipcode" label="邮编" />
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
      <!-- 查询 -->
      <!--      <el-row>-->
      <!--        <el-col class="mb-2">-->
      <!--          <el-select v-model="query.search_field" class="ya-search-field" placeholder="查询字段">-->
      <!--            <el-option :value="idkey" label="ID" />-->
      <!--            <el-option value="region_pid" label="上级" />-->
      <!--            <el-option value="region_name" label="名称" />-->
      <!--            <el-option value="region_pinyin" label="拼音" />-->
      <!--            <el-option value="region_jianpin" label="简拼" />-->
      <!--            <el-option value="region_initials" label="首字母" />-->
      <!--            <el-option value="region_citycode" label="区号" />-->
      <!--            <el-option value="region_zipcode" label="邮编" />-->
      <!--          </el-select>-->
      <!--          <el-select v-model="query.search_exp" class="ya-search-exp">-->
      <!--            <el-option v-for="exp in exps" :key="exp.exp" :value="exp.exp" :label="exp.name" />-->
      <!--          </el-select>-->
      <!--          <el-cascader-->
      <!--            v-if="query.search_field === 'region_pid'"-->
      <!--            v-model="query.region_pid"-->
      <!--            :options="trees"-->
      <!--            :props="props"-->
      <!--            class="ya-search-value"-->
      <!--            clearable-->
      <!--            filterable-->
      <!--          />-->
      <!--          <el-input-->
      <!--            v-else-->
      <!--            v-model="query.search_value"-->
      <!--            class="ya-search-value"-->
      <!--            placeholder="查询内容"-->
      <!--            clearable-->
      <!--          />-->
      <!--          <el-select v-model="query.date_field" class="ya-date-field" placeholder="时间类型">-->
      <!--            <el-option value="create_time" label="添加时间" />-->
      <!--            <el-option value="update_time" label="修改时间" />-->
      <!--          </el-select>-->
      <!--          <el-date-picker-->
      <!--            v-model="query.date_value"-->
      <!--            type="datetimerange"-->
      <!--            class="ya-date-value"-->
      <!--            start-placeholder="开始日期"-->
      <!--            end-placeholder="结束日期"-->
      <!--            value-format="YYYY-MM-DD HH:mm:ss"-->
      <!--            :default-time="[new Date(2024, 1, 1, 0, 0, 0), new Date(2024, 1, 1, 23, 59, 59)]"-->
      <!--          />-->
      <!--          <el-button type="primary" @click="search()">查询</el-button>-->
      <!--          <el-button title="重置" @click="refresh()">-->
      <!--            <svg-icon icon-class="refresh" />-->
      <!--          </el-button>-->
      <!--          <el-button type="primary" @click="add()">添加</el-button>-->
      <!--        </el-col>-->
      <!--      </el-row>-->
    </el-card>
    <el-dialog
      v-model="selectDialog"
      :title="selectTitle"
      :close-on-click-modal="false"
      :close-on-press-escape="false"
      top="20vh"
      destroy-on-close
    >
      <el-form ref="selectRef" label-width="120px">
        <el-form-item v-if="selectType === 'editpid'" label="上级">
          <el-cascader
            v-model="region_pid"
            :options="trees"
            :props="props"
            class="w-full"
            placeholder="一级地区"
            clearable
            filterable
          />
        </el-form-item>
        <el-form-item v-else-if="selectType === 'citycode'" label="区号">
          <el-input v-model="region_citycode" placeholder="请输入区号，eg：010" clearable />
        </el-form-item>
        <el-form-item v-else-if="selectType === 'zipcode'" label="邮编">
          <el-input v-model="region_zipcode" placeholder="请输入邮编，eg：1000" clearable />
        </el-form-item>
        <el-form-item v-else-if="selectType === 'disable'" label="是否禁用">
          <!--          <el-switch v-model="is_disable" :active-value="1" :inactive-value="0" />-->
          <el-radio-group v-model="is_disable">
            <el-radio :value="0">启用</el-radio>
            <el-radio :value="1">禁用</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item v-else-if="selectType === 'dele'">
          <span class="c-red">确定要删除选中的{{ name }}吗？</span>
        </el-form-item>
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
      <div class="section-title-row section-title-row--content">
        <div>
          <div class="section-title-row__title">地区列表</div>
          <div class="section-title-row__desc">
            支持树形结构维护、区号邮编调整以及地区状态管理。
          </div>
        </div>
        <div class="section-title-row__meta">已选 {{ selection.length }} 项</div>
      </div>
      <div class="page-summary-strip">
        <div class="page-summary-strip__head">
          <div class="page-summary-strip__title">操作摘要</div>
          <div class="page-summary-strip__env">
            <span class="page-summary-strip__tag">{{ runtimeEnvInfo.label }}</span>
            <span class="page-summary-strip__tag">{{ runtimeEnvInfo.dataMode }}</span>
          </div>
        </div>
        <div class="page-summary-strip__metrics">
          <span class="page-summary-strip__metric">地区总量：{{ count || 0 }}</span>
          <span class="page-summary-strip__metric">已选地区：{{ selection.length }}</span>
          <span class="page-summary-strip__metric">当前上级：{{ currentRegionParentLabel }}</span>
        </div>
        <div class="page-summary-strip__filters">
          <el-tag
            v-for="item in summaryFilterTags"
            :key="item"
            effect="plain"
            class="page-summary-strip__filter-tag"
          >
            {{ item }}
          </el-tag>
        </div>
        <div class="page-summary-strip__hint">
          <span>{{ summaryHint }}</span>
          <span v-if="recentActionSummary">{{ recentActionSummary }}</span>
        </div>
      </div>
      <div class="plain-guide">
        <div class="plain-guide__header">
          <div>
            <div class="plain-guide__title">这页建议先这样看</div>
            <div class="plain-guide__desc">
              地区页本质上是地址和履约基础台账。先看层级关系是不是对的，再看区号、邮编和状态，最后才去动批量调整，避免把下游仓库、配送范围一起带乱。
            </div>
          </div>
          <span class="plain-guide__badge">{{ regionFocusLabel }}</span>
        </div>
        <div class="plain-guide__grid">
          <div v-for="item in regionGuideCards" :key="item.title" class="plain-guide-card">
            <div class="plain-guide-card__title">{{ item.title }}</div>
            <div class="plain-guide-card__desc">{{ item.desc }}</div>
            <div class="plain-guide-card__action">{{ item.action }}</div>
          </div>
        </div>
      </div>
      <div class="followup-panel">
        <div class="followup-panel__main">
          <div class="followup-panel__title">看完地区后继续去哪</div>
          <div class="followup-panel__desc">{{ followupHint }}</div>
          <div class="followup-panel__tags">
            <span v-for="item in followupTags" :key="item">{{ item }}</span>
          </div>
        </div>
        <div class="followup-panel__actions">
          <el-button type="primary" @click="goToPage('/setting/warehouse')">去仓库管理</el-button>
          <el-button @click="goToPage('/setting/delivery')">去配送设置</el-button>
          <el-button @click="goToPage('/order/order')">去订单管理</el-button>
        </div>
      </div>
      <div class="operation_btn mb5">
        <!-- 操作 -->
        <div>
          <div class="action-cluster">
            <span class="action-cluster__title">结构维护</span>
            <el-button type="primary" @click="add()">添加</el-button>
            <el-button title="修改上级" @click="selectOpen('editpid')">上级</el-button>
            <el-button title="修改区号" @click="selectOpen('citycode')">区号</el-button>
            <el-button title="修改邮编" @click="selectOpen('zipcode')">邮编</el-button>
          </div>
          <div class="action-cluster">
            <span class="action-cluster__title">状态处理</span>
            <el-button title="是否禁用" @click="selectOpen('disable')">禁用</el-button>
            <el-button title="删除" @click="selectOpen('dele')">删除</el-button>
          </div>
        </div>
        <div>
          <!-- 分页 -->
          <el-descriptions title="" :column="12" :colon="false">
            <el-descriptions-item>共 {{ count }} 条</el-descriptions-item>
          </el-descriptions>
        </div>
      </div>
      <!-- 列表 -->
      <el-table
        ref="table"
        v-loading="loading"
        :data="data"
        :height="height"
        :row-key="idkey"
        :lazy="true"
        :load="load"
        @sort-change="sort"
        @selection-change="select"
        @cell-dblclick="cellDbclick"
      >
        <el-table-column type="selection" width="42" title="全选/反选" />
        <el-table-column prop="region_name" label="名称" min-width="250" show-overflow-tooltip />
        <el-table-column prop="region_pinyin" label="拼音" min-width="250" show-overflow-tooltip />
        <el-table-column prop="region_citycode" label="区号" min-width="80" />
        <el-table-column prop="region_zipcode" label="邮编" min-width="80" />
        <el-table-column prop="region_longitude" label="经度" min-width="110" />
        <el-table-column prop="region_latitude" label="纬度" min-width="110" />
        <el-table-column prop="is_disable" label="禁用" width="85">
          <template #default="scope">
            <el-switch
              :model-value="scope.row.is_disable"
              class="ml-2"
              style="--el-switch-on-color: #ff4949; --el-switch-off-color: #4073fa"
              inline-prompt
              active-text="禁用"
              inactive-text="启用"
              :active-value="1"
              :inactive-value="0"
              @change="handleDisableSwitch(scope.row, $event)"
            />
          </template>
        </el-table-column>
        <el-table-column :prop="idkey" label="ID" min-width="95" />
        <el-table-column prop="sort" label="排序" min-width="80" />
        <el-table-column label="操作" width="130">
          <template #default="scope">
            <el-link
              type="primary"
              class="mr-1"
              :underline="false"
              title="添加下级"
              @click="add(scope.row)"
            >
              添加
            </el-link>
            <el-link type="primary" class="mr-1" :underline="false" @click="edit(scope.row)">
              修改
            </el-link>
            <el-link type="primary" :underline="false" @click="selectOpen('dele', [scope.row])">
              删除
            </el-link>
          </template>
        </el-table-column>
      </el-table>
      <!-- 添加修改 -->
      <el-dialog
        v-model="dialog"
        :title="dialogTitle"
        :close-on-click-modal="false"
        :close-on-press-escape="false"
        :before-close="cancel"
        top="10vh"
      >
        <el-scrollbar native :height="height - 50">
          <el-form ref="ref" :rules="rules" :model="model" label-width="100px">
            <el-form-item label="上级" prop="region_pid">
              <el-cascader
                v-model="model.region_pid"
                :options="trees"
                :props="props"
                class="w-full"
                placeholder="一级地区"
                clearable
                filterable
              />
            </el-form-item>
            <el-form-item label="名称" prop="region_name">
              <el-input v-model="model.region_name" placeholder="请输入名称，eg：北京市" clearable>
                <template #append>
                  <el-button title="复制" @click="copy(model.region_name)">
                    <svg-icon icon-class="copy-document" />
                  </el-button>
                </template>
              </el-input>
            </el-form-item>
            <el-form-item label="拼音" prop="region_pinyin">
              <el-input
                v-model="model.region_pinyin"
                placeholder="请输入拼音，eg：Beijing"
                clearable
              >
                <template #append>
                  <el-button title="复制" @click="copy(model.region_pinyin)">
                    <svg-icon icon-class="copy-document" />
                  </el-button>
                </template>
              </el-input>
            </el-form-item>
            <el-form-item label="简拼" prop="region_jianpin">
              <el-input v-model="model.region_jianpin" placeholder="请输入简拼，eg：BJ" clearable>
                <template #append>
                  <el-button title="复制" @click="copy(model.region_jianpin)">
                    <svg-icon icon-class="copy-document" />
                  </el-button>
                </template>
              </el-input>
            </el-form-item>
            <el-form-item label="首字母" prop="region_initials">
              <el-input v-model="model.region_initials" placeholder="请输入首字母，eg：B" clearable>
                <template #append>
                  <el-button title="复制" @click="copy(model.region_initials)">
                    <svg-icon icon-class="copy-document" />
                  </el-button>
                </template>
              </el-input>
            </el-form-item>
            <el-form-item label="区号" prop="region_citycode">
              <el-input v-model="model.region_citycode" placeholder="请输入区号，eg：010" clearable>
                <template #append>
                  <el-button title="复制" @click="copy(model.region_citycode)">
                    <svg-icon icon-class="copy-document" />
                  </el-button>
                </template>
              </el-input>
            </el-form-item>
            <el-form-item label="邮编" prop="region_zipcode">
              <el-input v-model="model.region_zipcode" placeholder="请输入邮编，eg：1000" clearable>
                <template #append>
                  <el-button title="复制" @click="copy(model.region_zipcode)">
                    <svg-icon icon-class="copy-document" />
                  </el-button>
                </template>
              </el-input>
            </el-form-item>
            <el-form-item label="经度" prop="region_longitude">
              <el-input
                v-model="model.region_longitude"
                placeholder="请输入经度（高德），eg：116.403263"
                clearable
              >
                <template #append>
                  <el-button title="复制" @click="copy(model.region_longitude)">
                    <svg-icon icon-class="copy-document" />
                  </el-button>
                </template>
              </el-input>
            </el-form-item>
            <el-form-item label="纬度" prop="region_latitude">
              <el-input
                v-model="model.region_latitude"
                placeholder="请输入纬度（高德），eg：39.915156"
                clearable
              >
                <template #append>
                  <el-button title="复制" @click="copy(model.region_latitude)">
                    <svg-icon icon-class="copy-document" />
                  </el-button>
                </template>
              </el-input>
            </el-form-item>
            <el-form-item label="排序" prop="sort">
              <el-input
                v-model="model.sort"
                type="number"
                placeholder="请输入排序，eg：2250"
                clearable
              >
                <template #append>
                  <el-button title="复制" @click="copy(model.sort)">
                    <svg-icon icon-class="copy-document" />
                  </el-button>
                </template>
              </el-input>
            </el-form-item>
            <el-form-item v-if="model[idkey]" label="完整名称" prop="region_fullname">
              <el-input v-model="model.region_fullname" placeholder="" disabled>
                <template #append>
                  <el-button title="复制" @click="copy(model.region_fullname)">
                    <svg-icon icon-class="copy-document" />
                  </el-button>
                </template>
              </el-input>
            </el-form-item>
            <el-form-item v-if="model[idkey]" label="完整拼音" prop="region_fullname_py">
              <el-input v-model="model.region_fullname_py" placeholder="" disabled>
                <template #append>
                  <el-button title="复制" @click="copy(model.region_fullname_py)">
                    <svg-icon icon-class="copy-document" />
                  </el-button>
                </template>
              </el-input>
            </el-form-item>
            <el-form-item v-if="model[idkey]" label="添加时间" prop="create_time">
              <el-input v-model="model.create_time" placeholder="" disabled />
            </el-form-item>
            <el-form-item v-if="model[idkey]" label="修改时间" prop="update_time">
              <el-input v-model="model.update_time" placeholder="" disabled />
            </el-form-item>
            <el-form-item v-if="model.delete_time" label="删除时间" prop="delete_time">
              <el-input v-model="model.delete_time" disabled />
            </el-form-item>
          </el-form>
        </el-scrollbar>
        <template #footer>
          <el-button :loading="loading" @click="cancel">取消</el-button>
          <el-button :loading="loading" type="primary" @click="submit">提交</el-button>
        </template>
      </el-dialog>
    </el-card>
  </div>
</template>

<script>
import screenHeight from '@/utils/screen-height'
import clip from '@/utils/clipboard'
import { arrayColumn } from '@/utils/index'
import {
  list,
  info,
  add,
  edit,
  dele,
  editpid,
  citycode,
  zipcode,
  disable as disableApi
} from '@/api/setting/region'
import { shortcuts } from '@/utils/getDate.js'
import { getAdminRuntimeHint, resolveAdminRuntimeEnv } from '@/utils/runtime-env'

export default {
  name: 'SettingRegion',
  data() {
    return {
      name: '地区',
      height: 680,
      loading: false,
      idkey: 'region_id',
      tbKey: 1,
      exps: [{ exp: 'like', name: '包含' }],
      query: { search_field: 'region_name', search_exp: 'like', date_field: 'create_time' },
      data: [],
      dialog: false,
      dialogTitle: '',
      model: {
        region_id: '',
        region_pid: 0,
        region_name: '',
        region_pinyin: '',
        region_jianpin: '',
        region_initials: '',
        region_citycode: '',
        region_zipcode: '',
        region_longitude: '',
        region_latitude: '',
        sort: 2250
      },
      rules: {
        region_name: [{ required: true, message: '请输入地区名称', trigger: 'blur' }]
      },
      trees: [],
      props: {
        expandTrigger: 'click',
        checkStrictly: true,
        value: 'region_id',
        label: 'region_name',
        emitPath: false
      },
      selection: [],
      selectIds: '',
      selectTitle: '操作',
      selectDialog: false,
      selectType: '',
      region_pid: 0,
      region_citycode: '',
      region_zipcode: '',
      is_disable: 0,
      count: '',
      shortcuts: shortcuts(),
      recentActionSummary: '',
      runtimeEnvInfo: resolveAdminRuntimeEnv()
    }
  },
  computed: {
    runtimeHint() {
      return getAdminRuntimeHint(this.runtimeEnvInfo)
    },
    entrySourceLabel() {
      const source = String(this.$route?.query?.from || '')
      if (source === 'dashboard') return '来自控制台总览'
      if (source === 'system-setting') return '来自系统设置中心'
      if (source === 'setting-delivery') return '来自配送设置'
      if (source === 'setting-warehouse') return '来自仓库管理'
      return ''
    },
    entryContextVisible() {
      return Boolean(this.entrySourceLabel)
    },
    entryContextTitle() {
      if (this.entrySourceLabel === '来自控制台总览') return '当前从控制台进入地区管理'
      if (this.entrySourceLabel === '来自系统设置中心') return '当前从系统设置中心进入地区管理'
      if (this.entrySourceLabel === '来自配送设置') return '当前从配送设置进入地区管理'
      if (this.entrySourceLabel === '来自仓库管理') return '当前从仓库管理进入地区管理'
      return '当前为外部入口承接视角'
    },
    entryContextDesc() {
      if (this.entrySourceLabel === '来自控制台总览') {
        return '这类进入通常是为了排查地址、仓配覆盖或下单履约问题。建议先看层级树是否挂对，再去仓库和配送页确认承接。'
      }
      if (this.entrySourceLabel === '来自系统设置中心') {
        return '这类进入通常是为了继续补基础台账。建议先处理地区树与状态，再继续核仓库、配送和订单地址链路。'
      }
      if (this.entrySourceLabel === '来自配送设置') {
        return '这类进入通常是因为配送范围或地区联动有问题。建议先核上级层级和禁用状态，再回配送设置看模板是否承接正确。'
      }
      if (this.entrySourceLabel === '来自仓库管理') {
        return '这类进入通常是为了修仓库覆盖区域。建议先核地区归属和命名，再回仓库页看区域绑定是否正确。'
      }
      return ''
    },
    entryContextPrimaryLabel() {
      if (this.entrySourceLabel === '来自配送设置') return '回配送设置'
      if (this.entrySourceLabel === '来自仓库管理') return '回仓库管理'
      return '去订单管理核地址'
    },
    activeFilterTags() {
      const tags = []
      if (this.query.date_value?.length) {
        tags.push(`添加时间：${this.query.date_value.join(' 至 ')}`)
      }
      if (this.query.region_pid) {
        tags.push(`上级地区：${this.query.region_pid}`)
      }
      if (this.query.search_value) {
        tags.push(`关键词：${this.query.search_field || this.idkey}=${this.query.search_value}`)
      }
      return tags
    },
    currentRegionParentLabel() {
      return this.query.region_pid || '全部层级'
    },
    summaryFilterTags() {
      const tags = [...this.activeFilterTags]
      if (!tags.length) {
        tags.push('默认条件：全部地区')
      }
      if (this.selection.length) {
        tags.push(`样本预览：${this.buildSelectionPreview()}`)
      }
      return tags
    },
    summaryHint() {
      if (this.selectType) {
        return this.selectRiskHint
      }
      if (this.selection.length) {
        return `当前已选 ${this.selection.length} 个地区节点，可继续调整上级、区号、邮编或状态。`
      }
      return this.runtimeHint
    },
    regionFocusLabel() {
      if (this.selection.length) {
        return '先复核批量调整'
      }
      if (this.query.region_pid) {
        return '先看当前地区分支'
      }
      if (!this.count) {
        return '先补基础地区'
      }
      return '先看树形层级'
    },
    regionGuideCards() {
      return [
        {
          title: '第一步先看层级关系有没有错',
          desc: this.query.region_pid
            ? `当前已经聚焦到 ${this.currentRegionParentLabel}，先确认这条分支下的省市区层级是不是挂对了。`
            : '地区页最怕层级挂错。上级一旦错了，前台地址选择、仓库覆盖和配送范围都会跟着偏。',
          action: '先核对父级归属，再去改区号、邮编或状态，不要倒着处理。'
        },
        {
          title: '第二步再看基础字段是不是完整',
          desc: this.selection.length
            ? `当前已选 ${this.selection.length} 个地区节点，适合顺手复核区号、邮编和名称回显。`
            : '区号、邮编和拼音这些字段平时不起眼，但一旦缺失，会影响表单回显、筛选和联动搜索。',
          action: '优先检查核心地区节点的名称、区号和邮编，避免关键地址链路缺字段。'
        },
        {
          title: '第三步联动检查仓库和配送承接',
          desc: this.query.region_pid
            ? '当前已经缩到一个地区分支，处理完后最应该去仓库和配送页核对该区域有没有正常承接。'
            : '地区维护不是孤立页面，改完以后通常要继续确认仓库覆盖、配送模板和内容大厅的下游承接。',
          action: '处理完成后继续去仓库管理、配送设置和内容大厅做联动复核。'
        }
      ]
    },
    selectRiskHint() {
      if (this.selectType === 'dele') {
        return '删除地区节点会影响级联选择、地址归属和运费模板等依赖关系，正式环境请先确认没有下游引用。'
      }
      if (this.selectType === 'editpid') {
        return '批量调整上级会改变行政区划树结构，提交前请复核层级关系和已有业务绑定。'
      }
      if (this.selectType === 'disable') {
        return '禁用地区后相关前端选择器和后台表单可能不再可选，建议先核对是否仍有在用业务。'
      }
      return this.runtimeEnvInfo.isProd
        ? '正式环境会直接影响地区树、收货地址和联动组件，请先确认筛选条件、勾选范围与目标值。'
        : '当前环境适合验证地区树维护、区号邮编回显和批量调整，不要把测试结果当作正式运营数据。'
    },
    followupHint() {
      if (this.selection.length) {
        return `当前已选 ${this.selection.length} 个地区节点，处理完后建议继续去仓库、配送和订单页核对这些地区是否仍被正常引用。`
      }
      if (this.query.region_pid) {
        return '当前已经聚焦到某个上级地区，下一步更适合去仓库、配送和订单页检查该区域的履约范围与收件链路。'
      }
      return '地区页是地址与履约基础台账，处理完后通常要继续去仓库、配送和订单页检查下游承接。'
    },
    followupTags() {
      return [
        `地区总量：${this.count || 0}`,
        `已选：${this.selection.length} 项`,
        `上级：${this.currentRegionParentLabel}`,
        `环境：${this.runtimeEnvInfo.label}`
      ]
    }
  },
  created() {
    this.height = screenHeight(290)
    this.list()
  },
  methods: {
    handleEntryContextPrimary() {
      if (this.entrySourceLabel === '来自配送设置') {
        this.goToPage('/setting/delivery')
        return
      }
      if (this.entrySourceLabel === '来自仓库管理') {
        this.goToPage('/setting/warehouse')
        return
      }
      this.goToPage('/order/order')
    },
    goToEntryContextBack() {
      if (this.entrySourceLabel === '来自控制台总览') {
        this.$router.push({ path: '/dashboard', query: { from: 'setting-region' } })
        return
      }
      if (this.entrySourceLabel === '来自系统设置中心') {
        this.$router.push({ path: '/system/setting', query: { from: 'setting-region' } })
        return
      }
      if (this.entrySourceLabel === '来自配送设置') {
        this.$router.push({ path: '/setting/delivery', query: { from: 'setting-region' } })
        return
      }
      if (this.entrySourceLabel === '来自仓库管理') {
        this.$router.push({ path: '/setting/warehouse', query: { from: 'setting-region' } })
      }
    },
    buildSelectionPreview(limit = 5) {
      return (
        this.selection
          .slice(0, limit)
          .map((item) => item[this.idkey])
          .join('、') + (this.selection.length > limit ? ' 等' : '')
      )
    },
    getRegionLabel(row) {
      return row.region_name || row.region_pinyin || `地区#${row[this.idkey]}`
    },
    setRecentActionSummary(action, extra = '') {
      this.recentActionSummary = `已执行${action}，影响 ${this.selection.length || 0} 个地区节点${
        extra ? `，${extra}` : ''
      }。`
    },
    // 列表
    list() {
      this.loading = true
      list(this.query)
        .then((res) => {
          this.data = res.data.list
          this.trees = res.data.tree
          this.exps = res.data.exps
          this.count = res.data.count
          this.recentActionSummary = `已加载地区列表，共 ${res.data.count || 0} 个节点，当前已选 ${
            this.selection.length || 0
          } 个。`
          this.loading = false
        })
        .catch(() => {
          this.loading = false
        })
    },
    // 懒加载
    load(row, treeNode, resolve) {
      list({
        region_pid: row[this.idkey]
      }).then((res) => {
        resolve(res.data.list)
      })
    },
    // 添加修改
    add(row) {
      this.dialog = true
      this.dialogTitle = this.name + '添加'
      this.reset()
      this.recentActionSummary = row
        ? `准备在地区 ${row[this.idkey]} 下新增子节点。`
        : '准备新增顶级地区节点。'
      if (row) {
        this.model.region_pid = row[this.idkey]
      }
    },
    edit(row) {
      this.dialog = true
      this.dialogTitle = this.name + '修改：' + row[this.idkey]
      this.recentActionSummary = `准备修改地区 ${row[this.idkey]}。`
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
                this.recentActionSummary = `已修改地区：${
                  this.model.region_name || this.model[this.idkey]
                }。`
                ElMessage.success(res.msg)
                this.$nextTick(() => {
                  ElMessage.info('地区已提交，请继续去仓库管理、配送设置和订单管理页各核对一次。')
                })
              })
              .catch(() => {
                this.loading = false
              })
          } else {
            add(this.model)
              .then((res) => {
                this.list()
                this.dialog = false
                this.recentActionSummary = `已新增地区：${this.model.region_name || '未命名地区'}。`
                ElMessage.success(res.msg)
                this.$nextTick(() => {
                  ElMessage.info('地区已提交，请继续去仓库管理、配送设置和订单管理页各核对一次。')
                })
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
      this.list()
    },
    // 刷新
    refresh() {
      const limit = this.query.limit
      this.query = this.$options.data().query
      this.$refs['table'].clearSort()
      this.query.limit = limit
      this.list()
      ++this.tbKey
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
        if (selectType === 'editpid') {
          this.selectTitle = this.name + '修改上级'
        } else if (selectType === 'citycode') {
          this.selectTitle = this.name + '修改区号'
        } else if (selectType === 'zipcode') {
          this.selectTitle = this.name + '修改邮编'
        } else if (selectType === 'disable') {
          this.selectTitle = this.name + '是否禁用'
        } else if (selectType === 'dele') {
          this.selectTitle = this.name + '删除'
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
        if (selectType === 'editpid') {
          this.editpid(this.selection)
        } else if (selectType === 'citycode') {
          this.citycode(this.selection)
        } else if (selectType === 'zipcode') {
          this.zipcode(this.selection)
        } else if (selectType === 'disable') {
          this.disable(this.selection, true)
        } else if (selectType === 'dele') {
          this.dele(this.selection)
        }
        this.selectDialog = false
      }
    },
    // 修改上级
    editpid(row) {
      editpid({
        ids: this.selectGetIds(row),
        region_pid: this.region_pid
      })
        .then((res) => {
          this.list()
          this.reset()
          this.selectDialog = false
          this.setRecentActionSummary('批量修改地区上级', `目标上级：${this.region_pid || '顶级'}`)
          ElMessage.success(res.msg)
        })
        .catch(() => {
          this.loading = false
        })
    },
    // 修改区号
    citycode(row) {
      citycode({
        ids: this.selectGetIds(row),
        region_citycode: this.region_citycode
      })
        .then((res) => {
          this.list()
          this.reset()
          this.selectDialog = false
          this.setRecentActionSummary(
            '批量修改地区区号',
            `目标区号：${this.region_citycode || '清空'}`
          )
          ElMessage.success(res.msg)
        })
        .catch(() => {
          this.loading = false
        })
    },
    // 修改邮编
    zipcode(row) {
      zipcode({
        ids: this.selectGetIds(row),
        region_zipcode: this.region_zipcode
      })
        .then((res) => {
          this.list()
          this.reset()
          this.selectDialog = false
          this.setRecentActionSummary(
            '批量修改地区邮编',
            `目标邮编：${this.region_zipcode || '清空'}`
          )
          ElMessage.success(res.msg)
        })
        .catch(() => {
          this.loading = false
        })
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
        disableApi({
          ids: this.selectGetIds(row),
          is_disable: is_disable
        })
          .then((res) => {
            this.list()
            this.reset()
            this.setRecentActionSummary(
              '批量调整地区状态',
              `目标状态：${is_disable === 1 ? '禁用' : '启用'}`
            )
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.list()
          })
      }
    },
    handleDisableSwitch(row, value) {
      ElMessageBox.confirm(
        `确认要${value === 1 ? '禁用' : '启用'}地区「${this.getRegionLabel(row)}」吗？`,
        '操作确认',
        {
          type: 'warning',
          confirmButtonText: '继续',
          cancelButtonText: '取消'
        }
      )
        .then(() => {
          this.disable([{ ...row, is_disable: value }])
        })
        .catch(() => {})
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
            this.reset()
            this.setRecentActionSummary('批量删除地区')
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
    },
    goToPage(path) {
      this.$router.push({
        path,
        query: {
          from: 'setting-region'
        }
      })
    }
  }
}
</script>

<style scoped>
.entry-context-banner {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin: 14px 0 16px;
  padding: 14px 16px;
  border-radius: 14px;
  border: 1px solid #dbe7f5;
  background: linear-gradient(135deg, #f8fbff 0%, #ffffff 100%);
}

.entry-context-banner__main {
  flex: 1;
  min-width: 0;
}

.entry-context-banner__eyebrow {
  font-size: 12px;
  font-weight: 700;
  color: #2563eb;
}

.entry-context-banner__title {
  margin-top: 4px;
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
}

.entry-context-banner__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.entry-context-banner__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  justify-content: flex-end;
}

.plain-guide {
  margin-bottom: 14px;
  padding: 12px 14px;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
}

.plain-guide__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.plain-guide__title {
  font-size: 13px;
  font-weight: 700;
  color: #111827;
}

.plain-guide__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #6b7280;
}

.plain-guide__badge {
  display: inline-flex;
  align-items: center;
  min-height: 24px;
  padding: 0 8px;
  border-radius: 999px;
  background: #eef2ff;
  color: #4338ca;
  font-size: 12px;
  font-weight: 700;
}

.plain-guide__grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 10px;
  margin-top: 12px;
}

.plain-guide-card {
  padding: 12px;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  background: #fff;
}

.plain-guide-card__title {
  font-size: 13px;
  font-weight: 700;
  color: #111827;
}

.plain-guide-card__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #6b7280;
}

.plain-guide-card__action {
  margin-top: 8px;
  font-size: 12px;
  color: #4f46e5;
}

.followup-panel {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 14px;
  padding: 12px 16px;
  border: 1px solid rgba(148, 163, 184, 0.16);
  border-radius: 14px;
  background: #fbfdff;
}

.followup-panel__main {
  flex: 1;
  min-width: 0;
}

.followup-panel__title {
  font-size: 13px;
  font-weight: 700;
  color: #111827;
}

.followup-panel__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.followup-panel__tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 10px;
}

.followup-panel__tags span {
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  padding: 0 10px;
  border-radius: 999px;
  font-size: 12px;
  color: #334155;
  background: #f8fafc;
  border: 1px solid rgba(148, 163, 184, 0.16);
}

.followup-panel__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  justify-content: flex-end;
}

.page-summary-strip {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-bottom: 14px;
  padding: 12px 16px;
  border: 1px solid rgba(148, 163, 184, 0.16);
  border-radius: 14px;
  background: linear-gradient(180deg, rgba(248, 250, 252, 0.96), #ffffff 100%);
}

.page-summary-strip__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}

.page-summary-strip__title {
  font-size: 12px;
  font-weight: 700;
  color: #475569;
}

.page-summary-strip__env,
.page-summary-strip__metrics,
.page-summary-strip__filters {
  display: inline-flex;
  flex-wrap: wrap;
  gap: 8px;
}

.page-summary-strip__tag,
.page-summary-strip__metric {
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  padding: 0 10px;
  border-radius: 999px;
  font-size: 12px;
}

.page-summary-strip__tag {
  font-weight: 700;
  color: #1d4ed8;
  background: #e8f0ff;
}

.page-summary-strip__metric {
  color: #334155;
  background: #f8fafc;
  border: 1px solid rgba(148, 163, 184, 0.16);
}

.page-summary-strip__filter-tag {
  margin-right: 0;
}

.page-summary-strip__hint {
  display: flex;
  flex-wrap: wrap;
  gap: 10px 16px;
  font-size: 12px;
  color: #64748b;
}

.action-cluster {
  display: inline-flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
  margin-right: 10px;
}

.action-cluster__title {
  font-size: 12px;
  color: #7c8aa5;
}

@media (max-width: 900px) {
  .plain-guide__header,
  .followup-panel,
  .page-summary-strip__head,
  .section-title-row {
    flex-direction: column;
    align-items: flex-start;
  }

  .section-title-row__meta {
    white-space: normal;
  }
}
</style>
