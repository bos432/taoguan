<template>
  <div class="app-container">
    <el-card class="app-head head-pb20">
      <div class="section-title-row">
        <div>
          <div class="section-title-row__title">仓库管理</div>
          <div class="section-title-row__desc">
            统一处理仓库筛选、树形维护、上级调整和大厅归属管理。
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
            <el-form-item label="状态：" prop="is_disable">
              <el-select v-model="query.is_disable" @change="search()" clearable>
                <el-option :value="0" label="启用" />
                <el-option :value="1" label="禁用" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="上级：" prop="pid">
              <el-cascader
                v-model="query.pid"
                :options="trees"
                :props="props"
                @change="search()"
                clearable
                filterable
                style="width: 100%"
              />
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="大厅：" prop="setting_hall_id">
              <el-cascader
                v-model="query.setting_hall_id"
                :options="hall_list"
                :props="props"
                @change="search()"
                clearable
                filterable
                style="width: 100%"
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
                  <el-option value="code" label="编码" />
                  <el-option value="title" label="名称" />
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
      <div class="warehouse-summary-bar">
        <div class="warehouse-summary-bar__chips">
          <span class="summary-chip summary-chip--primary">总数 {{ count }}</span>
          <span class="summary-chip">已选 {{ selection.length }} 项</span>
          <span class="summary-chip">{{ isExpandAll ? '树表展开' : '树表收起' }}</span>
          <span class="summary-chip">大厅 {{ hall_list.length }} 项</span>
          <span class="summary-chip">数据模式 {{ runtimeEnvInfo.dataMode }}</span>
          <span class="summary-chip">状态 {{ statusFilterLabel }}</span>
          <span
            v-if="query.pid !== undefined && query.pid !== '' && query.pid !== 0"
            class="summary-chip"
          >
            上级 {{ query.pid }}
          </span>
          <span v-for="item in activeFilterTags" :key="item" class="summary-chip">{{ item }}</span>
          <span v-if="!activeFilterTags.length" class="summary-chip">默认条件：全部仓库</span>
          <span v-if="recentActionSummary" class="summary-chip summary-chip--muted">{{
            recentActionSummary
          }}</span>
        </div>
        <div class="warehouse-summary-bar__hint" :class="summaryBadgeClass">
          <span class="warehouse-summary-bar__hint-title">{{ summaryBadgeText }}</span>
          <span class="warehouse-summary-bar__hint-text">{{ summaryHint }}</span>
        </div>
      </div>
      <div class="plain-guide">
        <div class="plain-guide__header">
          <div>
            <div class="plain-guide__title">这页建议先这样看</div>
            <div class="plain-guide__desc">
              仓库页本质上在管结构、归属和履约地址。先看仓库层级和大厅归属对不对，再看状态和地区覆盖，最后才做批量改上级、禁用或删除。
            </div>
          </div>
          <span class="plain-guide__badge">{{ warehouseFocusLabel }}</span>
        </div>
        <div class="plain-guide__grid">
          <div v-for="item in warehouseGuideCards" :key="item.title" class="plain-guide-card">
            <div class="plain-guide-card__title">{{ item.title }}</div>
            <div class="plain-guide-card__desc">{{ item.desc }}</div>
            <div class="plain-guide-card__action">{{ item.action }}</div>
          </div>
        </div>
      </div>
      <div class="followup-panel">
        <div class="followup-panel__main">
          <div class="followup-panel__title">看完仓库后继续去哪</div>
          <div class="followup-panel__desc">{{ followupHint }}</div>
          <div class="followup-panel__tags">
            <span v-for="item in followupTags" :key="item">{{ item }}</span>
          </div>
        </div>
        <div class="followup-panel__actions">
          <el-button type="primary" @click="goToPage('/trace/batch')">去批次溯源</el-button>
          <el-button @click="goToPage('/member/member')">去会员管理</el-button>
          <el-button @click="goToPage('/setting/delivery')">去配送设置</el-button>
        </div>
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
        <el-form-item v-if="selectType === 'removec'">
          <span style="">确定要解除选中的{{ name }}的内容吗？</span>
        </el-form-item>
        <el-form-item v-else-if="selectType === 'editpid'" label="上级">
          <el-cascader
            v-model="pid"
            :options="trees"
            :props="props"
            class="w-full"
            placeholder="请选择仓库"
            clearable
            filterable
          />
        </el-form-item>
        <el-form-item v-if="selectType === 'disable'" label="是否禁用">
          <el-switch v-model="is_disable" :active-value="1" :inactive-value="0" />
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
          <div class="section-title-row__title">仓库列表</div>
          <div class="section-title-row__desc">
            支持树形维护、批量禁用、上级调整和大厅归属管理。
          </div>
        </div>
        <div class="section-title-row__meta">已选 {{ selection.length }} 项</div>
      </div>
      <div class="operation_btn mb5">
        <!-- 操作 -->
        <div>
          <div class="action-cluster">
            <span class="action-cluster__title">结构维护</span>
            <el-checkbox
              border
              v-model="isExpandAll"
              class="!mr-[10px] top-[3px]"
              title="收起/展开"
              @change="expandAll"
            >
              收起
            </el-checkbox>
            <el-button type="primary" @click="add()">添加</el-button>
            <el-button title="修改上级" @click="selectOpen('editpid')">上级</el-button>
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
        :row-key="idkey"
        @selection-change="select"
      >
        <el-table-column type="selection" width="42" title="全选/反选" />
        <el-table-column :prop="idkey" label="ID" min-width="80" />
        <el-table-column prop="code" label="编码" min-width="100" show-overflow-tooltip />
        <el-table-column prop="title" label="名称" min-width="250" show-overflow-tooltip />
        <el-table-column prop="hall_title" label="大厅" min-width="100" show-overflow-tooltip />
        <el-table-column prop="address" label="地址" min-width="150" show-overflow-tooltip />
        <el-table-column prop="is_disable" label="禁用" min-width="85">
          <template #default="scope">
            <el-switch
              :model-value="scope.row.is_disable"
              :active-value="1"
              :inactive-value="0"
              @change="handleDisableSwitch(scope.row, $event)"
            />
          </template>
        </el-table-column>
        <el-table-column prop="sort" label="排序" min-width="85" />
        <el-table-column prop="create_time" label="添加时间" width="165" />
        <el-table-column prop="update_time" label="修改时间" width="165" />
        <el-table-column label="操作" width="170">
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
            <el-link
              v-if="scope.row.code != 'ZC'"
              type="primary"
              class="mr-1"
              :underline="false"
              @click="edit(scope.row)"
            >
              修改
            </el-link>
            <el-link
              v-if="scope.row.code != 'ZC'"
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
      <el-scrollbar native :max-height="height - 30">
        <el-form ref="ref" :rules="rules" :model="model" label-width="100px">
          <el-form-item label="上级" prop="pid">
            <el-cascader
              v-model="model.pid"
              :options="trees"
              :props="props"
              class="w-full"
              placeholder="请选择仓库"
              clearable
              filterable
            />
          </el-form-item>
          <el-form-item label="大厅" prop="setting_hall_id">
            <el-cascader
              v-model="model.setting_hall_id"
              :options="hall_list"
              :props="props"
              class="w-full"
              placeholder="请选择大厅"
              clearable
              filterable
            />
          </el-form-item>
          <el-form-item label="编码" prop="code">
            <el-input v-model="model.code" placeholder="请输入仓库编码（唯一）" clearable />
          </el-form-item>
          <el-form-item label="名称" prop="title">
            <el-input v-model="model.title" placeholder="请输入仓库名称" clearable />
          </el-form-item>
          <el-form-item label="地址" prop="address">
            <el-input v-model="model.address" placeholder="请输入仓库地址" clearable />
          </el-form-item>
          <el-form-item label="排序" prop="sort">
            <el-input v-model="model.sort" placeholder="250" clearable />
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
        </el-form>
      </el-scrollbar>
      <template #footer>
        <el-button :loading="loading" @click="cancel">取消</el-button>
        <el-button :loading="loading" type="primary" @click="submit">提交</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script>
import screenHeight from '@/utils/screen-height'
import { arrayColumn } from '@/utils/index'
import { list, info, add, edit, dele, disable, editpid } from '@/api/setting/warehouse.js'
import { shortcuts } from '@/utils/getDate.js'
import { select as hallSelect } from '@/api/setting/hall.js'
import { getAdminRuntimeHint, resolveAdminRuntimeEnv } from '@/utils/runtime-env'
export default {
  name: 'SettingWarehouse',
  data() {
    return {
      name: '仓库',
      height: 680,
      loading: false,
      idkey: 'id',
      query: {
        search_field: 'title',
        search_exp: 'like',
        date_field: 'create_time',
        pid: undefined,
        is_disable: undefined
      },
      exps: [{ exp: 'like', name: '包含' }],
      data: [],
      count: '',
      dialog: false,
      dialogTitle: '',
      model: {
        id: '',
        code: '',
        pid: 0,
        title: '',
        sort: 250,
        setting_hall_id: undefined,
        address: ''
      },
      rules: {
        code: [{ required: true, message: '请输入仓库编码', trigger: 'blur' }],
        title: [{ required: true, message: '请输入仓库名称', trigger: 'blur' }],
        setting_hall_id: [{ required: true, message: '请选择大厅', trigger: 'blur' }]
      },
      trees: [],
      props: { checkStrictly: true, value: 'id', label: 'title', emitPath: false },
      isExpandAll: false,
      selection: [],
      selectIds: '',
      selectTitle: '操作',
      selectDialog: false,
      selectType: '',
      pid: '',
      is_disable: 0,
      shortcuts: shortcuts(),
      hall_list: [],
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
      if (source === 'setting-region') return '来自地区管理'
      if (source === 'setting-hall') return '来自内容大厅'
      return ''
    },
    entryContextVisible() {
      return Boolean(this.entrySourceLabel)
    },
    entryContextTitle() {
      if (this.entrySourceLabel === '来自控制台总览') return '当前从控制台进入仓库管理'
      if (this.entrySourceLabel === '来自地区管理') return '当前从地区管理进入仓库管理'
      if (this.entrySourceLabel === '来自内容大厅') return '当前从内容大厅进入仓库管理'
      return '当前为外部入口承接视角'
    },
    entryContextDesc() {
      if (this.entrySourceLabel === '来自控制台总览') {
        return '这类进入通常是为了排履约结构、会员归属或批次承接问题。建议先看仓库层级和大厅归属，再去溯源与会员页复核真实引用。'
      }
      if (this.entrySourceLabel === '来自地区管理') {
        return '这类进入通常是因为地区树调整后，需要继续核仓库覆盖。建议先看地址和归属，再回地区页确认联动是否正常。'
      }
      if (this.entrySourceLabel === '来自内容大厅') {
        return '这类进入通常是因为前端入口结构要对回仓库归属。建议先核大厅挂载和仓库层级，再回内容大厅看入口是否还顺。'
      }
      return ''
    },
    entryContextPrimaryLabel() {
      if (this.entrySourceLabel === '来自地区管理') return '回地区管理'
      if (this.entrySourceLabel === '来自内容大厅') return '回内容大厅'
      return '去批次溯源复核'
    },
    statusFilterLabel() {
      return this.query.is_disable === undefined
        ? '全部状态'
        : this.query.is_disable === 0
        ? '启用中'
        : '已禁用'
    },
    activeFilterTags() {
      const tags = []
      if (this.query.date_value?.length === 2) {
        tags.push(`添加时间：${this.query.date_value[0]} 至 ${this.query.date_value[1]}`)
      }
      if (this.query.is_disable !== undefined) {
        tags.push(`状态：${this.query.is_disable === 1 ? '禁用' : '启用'}`)
      }
      if (this.query.pid !== undefined && this.query.pid !== '' && this.query.pid !== 0) {
        tags.push(`上级：${this.query.pid}`)
      }
      if (
        this.query.setting_hall_id !== undefined &&
        this.query.setting_hall_id !== '' &&
        this.query.setting_hall_id !== 0
      ) {
        tags.push(`大厅：${this.query.setting_hall_id}`)
      }
      if (this.query.search_value) {
        tags.push(`检索：${this.query.search_field || 'title'} = ${this.query.search_value}`)
      }
      return tags
    },
    selectReviewItems() {
      return [
        `运行环境：${this.runtimeEnvInfo.label}`,
        `已选数量：${this.selection.length}`,
        `大厅数量：${this.hall_list.length}`,
        `操作类型：${this.selectTitle || '仓库维护'}`
      ]
    },
    selectRiskHint() {
      return this.runtimeEnvInfo.isProd
        ? '正式环境会直接影响仓库树、交付地址和大厅归属，请先复核上级调整、禁用和删除。'
        : '当前环境适合验证仓库结构维护、筛选与树形回显，不要把测试操作当作正式运营结果。'
    },
    summaryBadgeClass() {
      if (this.selection.length) {
        return 'is-active'
      }
      return this.query.is_disable === 1 ? 'is-warning' : 'is-safe'
    },
    summaryBadgeText() {
      if (this.selection.length) {
        return `待复核 ${this.selection.length} 项`
      }
      return this.query.is_disable === 1 ? '重点检查禁用仓库' : '结构概览'
    },
    summaryHint() {
      if (this.selection.length) {
        return this.selectRiskHint
      }
      return `${this.runtimeHint} 当前共 ${this.count || 0} 项仓库，可直接从下方继续维护。`
    },
    warehouseFocusLabel() {
      if (this.selection.length) {
        return '先复核批量调整'
      }
      if (
        this.query.setting_hall_id !== undefined &&
        this.query.setting_hall_id !== '' &&
        this.query.setting_hall_id !== 0
      ) {
        return '先看当前大厅归属'
      }
      if (this.query.pid !== undefined && this.query.pid !== '' && this.query.pid !== 0) {
        return '先看当前仓库分支'
      }
      return '先看结构归属'
    },
    warehouseGuideCards() {
      return [
        {
          title: '第一步先看结构和归属有没有挂对',
          desc:
            this.query.setting_hall_id !== undefined &&
            this.query.setting_hall_id !== '' &&
            this.query.setting_hall_id !== 0
              ? `当前已经聚焦到大厅 ${this.query.setting_hall_id}，先确认这个大厅下的仓库归属是否完整、有没有挂错。`
              : '仓库页最容易出问题的不是名字，而是层级和大厅归属。归属一旦错了，后面的履约链路都会跟着乱。',
          action: '优先核对上级仓库和大厅归属，再处理状态或删除。'
        },
        {
          title: '第二步再看是否还能继续对外承接',
          desc:
            this.query.is_disable === 1
              ? '当前筛出来的是禁用仓库，先判断是临时停用还是已经彻底不用，别直接当脏数据删掉。'
              : '仓库启用状态和地区覆盖会直接影响履约范围，不是简单的“开关”字段。',
          action: '先确认状态、地区覆盖和地址信息，再决定启用、禁用或调整上级。'
        },
        {
          title: '第三步联动检查地区和配送链路',
          desc: this.selection.length
            ? `当前已选 ${this.selection.length} 项仓库，处理完后最好继续去地区和配送页复核下游承接。`
            : '仓库改完通常不算完事，还要继续核对地区维护、配送设置和内容大厅入口是不是仍然匹配。',
          action: '处理完成后继续去地区维护、配送设置和内容大厅做联动复核。'
        }
      ]
    },
    followupHint() {
      if (this.selection.length) {
        return `当前已选 ${this.selection.length} 项仓库，处理完成后建议先去批次溯源页和会员页看仓库引用，再去配送页核对履约范围。`
      }
      if (
        this.query.setting_hall_id !== undefined &&
        this.query.setting_hall_id !== '' &&
        this.query.setting_hall_id !== 0
      ) {
        return '当前已经聚焦到某个大厅下的仓库，下一步更适合先去批次溯源页和会员页看仓库引用，再去配送页核对履约承接。'
      }
      if (this.query.pid !== undefined && this.query.pid !== '' && this.query.pid !== 0) {
        return '当前已经收窄到某个上级仓库，下一步建议先去批次溯源页和会员页看真实引用，再去配送页确认履约链路。'
      }
      return '仓库主要解决结构、归属和地址问题；处理完这里后，通常要先去批次溯源页和会员页看真实引用，再去配送页复核履约结构。'
    },
    followupTags() {
      return [
        `环境：${this.runtimeEnvInfo.label}`,
        `大厅：${this.hall_list.length} 项`,
        `状态：${this.statusFilterLabel}`,
        `已选：${this.selection.length} 项`
      ]
    }
  },
  created() {
    this.height = screenHeight(290)
    this.getHall()
    this.list()
  },
  methods: {
    handleEntryContextPrimary() {
      if (this.entrySourceLabel === '来自地区管理') {
        this.goToPage('/setting/region')
        return
      }
      if (this.entrySourceLabel === '来自内容大厅') {
        this.goToPage('/setting/hall')
        return
      }
      this.goToPage('/trace/batch')
    },
    goToEntryContextBack() {
      if (this.entrySourceLabel === '来自控制台总览') {
        this.$router.push({ path: '/dashboard', query: { from: 'setting-warehouse' } })
        return
      }
      if (this.entrySourceLabel === '来自地区管理') {
        this.$router.push({ path: '/setting/region', query: { from: 'setting-warehouse' } })
        return
      }
      if (this.entrySourceLabel === '来自内容大厅') {
        this.$router.push({ path: '/setting/hall', query: { from: 'setting-warehouse' } })
      }
    },
    //查询大厅
    getHall() {
      this.loading = true
      hallSelect({})
        .then((res) => {
          this.hall_list = res.data
          this.loading = false
          this.recentActionSummary = `已加载大厅选项，共 ${res.data.length || 0} 项。`
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
          this.trees = res.data.tree
          this.exps = res.data.exps
          this.count = res.data.count
          this.isExpandAll = false
          this.loading = false
          this.recentActionSummary = `已加载仓库列表，共 ${res.data.count || 0} 项。`
        })
        .catch(() => {
          this.loading = false
        })
    },
    // 添加修改
    add(row) {
      this.dialog = true
      this.dialogTitle = this.name + '添加'
      this.reset()
      if (row) {
        this.model.pid = row[this.idkey]
        this.recentActionSummary = `准备在仓库 ${row[this.idkey]} 下添加子节点。`
      } else {
        this.recentActionSummary = '准备添加顶级仓库节点。'
      }
    },
    edit(row) {
      this.dialog = true
      this.dialogTitle = this.name + '修改：' + row[this.idkey]
      this.recentActionSummary = `准备修改仓库 ${row[this.idkey]}。`
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
                this.recentActionSummary = `已修改仓库 ${this.model[this.idkey]}，名称：${
                  this.model.title || '未命名'
                }。`
                ElMessage.success(res.msg)
                this.$nextTick(() => {
                  ElMessage.info('仓库已提交，请继续去批次溯源、会员管理和配送设置页各核对一次。')
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
                this.recentActionSummary = `已添加仓库，名称：${this.model.title || '未命名'}。`
                ElMessage.success(res.msg)
                this.$nextTick(() => {
                  ElMessage.info('仓库已提交，请继续去批次溯源、会员管理和配送设置页各核对一次。')
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
    search() {
      this.recentActionSummary = `已按 ${this.query.search_field || 'title'} 发起仓库检索。`
      this.list()
    },
    // 刷新
    refresh() {
      const limit = this.query.limit
      this.query = this.$options.data().query
      this.$refs['table'].clearSort()
      this.query.limit = limit
      this.recentActionSummary = '已重置仓库筛选条件。'
      this.list()
    },
    // 收起/展开
    expandAll(e) {
      this.expandFor(this.data, !e)
    },
    expandFor(data, isExpand) {
      data.forEach((i) => {
        this.$refs.table.toggleRowExpansion(i, isExpand)
        if (i.children) {
          this.expandFor(i.children, isExpand)
        }
      })
    },
    // 操作
    select(selection) {
      this.selection = selection
      this.selectIds = this.selectGetIds(selection).toString()
      if (selection.length) {
        this.recentActionSummary = `已勾选 ${selection.length} 项仓库，待处理 ID：${this.selectIds}。`
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
        if (selectType == 'editpid') {
          this.selectTitle = this.name + '修改上级'
        } else if (selectType === 'disable') {
          this.selectTitle = this.name + '是否禁用'
        } else if (selectType === 'dele') {
          this.selectTitle = this.name + '删除'
        }
        this.selectDialog = true
        this.selectType = selectType
        this.recentActionSummary = `准备执行${this.selectTitle}，当前已选 ${this.selection.length} 项仓库。`
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
        if (selectType == 'editpid') {
          this.editpid(this.selection)
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
        pid: this.pid
      })
        .then((res) => {
          this.list()
          this.selectDialog = false
          this.recentActionSummary = `已批量修改上级，目标上级：${this.pid || '顶级'}。`
          ElMessage.success(res.msg)
        })
        .catch(() => {
          this.loading = false
        })
    },
    // 是否禁用
    disable(row, select = false) {
      if (row.length === 0) {
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
            this.recentActionSummary = `已${is_disable === 1 ? '禁用' : '启用'} ${
              row.length
            } 项仓库。`
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.list()
          })
      }
    },
    handleDisableSwitch(row, value) {
      ElMessageBox.confirm(
        `确认要${value === 1 ? '禁用' : '启用'}仓库「${
          row.title || row.code || row[this.idkey]
        }」吗？`,
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
        dele({
          ids: this.selectGetIds(row)
        })
          .then((res) => {
            this.list()
            this.recentActionSummary = `已删除 ${row.length} 项仓库。`
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.loading = false
          })
      }
    },
    goToPage(path) {
      this.$router.push({
        path,
        query: {
          from: 'setting-warehouse'
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
  margin: 0 0 16px;
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

.section-title-row {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 16px;
}

.section-title-row--content {
  margin-bottom: 14px;
}

.section-title-row__title {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
}

.section-title-row__desc {
  margin-top: 4px;
  font-size: 12px;
  color: #64748b;
}

.section-title-row__meta {
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
  white-space: nowrap;
}

.warehouse-summary-bar {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  margin-top: 16px;
  padding: 14px 16px;
  border: 1px solid #dbe7f5;
  border-radius: 14px;
  background: #f8fbff;
}

.warehouse-summary-bar__chips {
  display: flex;
  flex: 1;
  flex-wrap: wrap;
  gap: 10px;
}

.summary-chip {
  display: inline-flex;
  align-items: center;
  min-height: 30px;
  padding: 0 10px;
  border: 1px solid #e6ecf5;
  border-radius: 999px;
  background: #fff;
  color: #334155;
  font-size: 12px;
}

.summary-chip--primary {
  color: #1d4ed8;
  background: #e8f0ff;
  border-color: #cfe0ff;
}

.summary-chip--muted {
  color: #64748b;
}

.warehouse-summary-bar__hint {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 240px;
  padding: 12px 14px;
  border-radius: 12px;
  background: #eef4ff;
  color: #1d4ed8;
}

.warehouse-summary-bar__hint.is-safe {
  background: #eaf8ef;
  color: #15803d;
}

.warehouse-summary-bar__hint.is-warning {
  background: #fff5e8;
  color: #b45309;
}

.warehouse-summary-bar__hint.is-active {
  background: #e8f0ff;
  color: #1d4ed8;
}

.warehouse-summary-bar__hint-title {
  font-size: 12px;
  font-weight: 700;
}

.warehouse-summary-bar__hint-text {
  font-size: 12px;
  line-height: 1.7;
}

.plain-guide {
  margin-top: 12px;
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
  margin-top: 10px;
  padding: 12px 14px;
  border: 1px solid #dbe7f5;
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
  background: #f3f4f6;
  color: #475569;
  font-size: 12px;
  border: 1px solid rgba(148, 163, 184, 0.16);
}

.followup-panel__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  justify-content: flex-end;
}

.select-review-panel {
  margin-bottom: 12px;
  padding: 12px 14px;
  border: 1px solid #dbe7f5;
  border-radius: 12px;
  background: #f8fbff;
}

.select-review-panel__title {
  font-size: 12px;
  font-weight: 700;
  color: #475569;
}

.select-review-panel__tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 10px;
}

.select-review-panel__tags span {
  display: inline-flex;
  align-items: center;
  min-height: 26px;
  padding: 0 10px;
  border: 1px solid #dbe7f5;
  border-radius: 999px;
  background: #fff;
  color: #334155;
  font-size: 12px;
}

.select-review-panel__hint {
  margin-top: 8px;
  font-size: 12px;
  line-height: 1.7;
  color: #9a3412;
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
  .entry-context-banner,
  .section-title-row,
  .plain-guide__header,
  .followup-panel,
  .warehouse-summary-bar {
    flex-direction: column;
  }

  .section-title-row__meta {
    white-space: normal;
  }
}
</style>
