<template>
  <div class="app-container notice-page">
    <section class="notice-hero">
      <div>
        <div class="notice-hero__eyebrow">System Notice</div>
        <div class="notice-hero__headline">
          <h1>系统公告</h1>
          <span class="notice-hero__env">{{ runtimeEnvInfo.label }}</span>
        </div>
        <p class="notice-hero__desc">保持筛选、发布和批量维护能力不变，收成更轻的线上管理视图。</p>
      </div>
      <div class="notice-hero__meta">
        <span>当前检索：{{ query.search_field || 'title' }}</span>
        <span>更新时间：{{ lastActionText }}</span>
      </div>
    </section>
    <section v-if="entryContextVisible" class="entry-context-banner">
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
    </section>

    <section class="notice-summary">
      <div class="notice-summary__item">
        <span class="notice-summary__label">公告总量</span>
        <strong class="notice-summary__value">{{ count }}</strong>
      </div>
      <div class="notice-summary__item">
        <span class="notice-summary__label">已选公告</span>
        <strong class="notice-summary__value">{{ selection.length }}</strong>
      </div>
      <div class="notice-summary__item">
        <span class="notice-summary__label">当前状态</span>
        <strong class="notice-summary__value">{{ currentStatusText }}</strong>
      </div>
      <div class="notice-summary__item notice-summary__item--wide">
        <span class="notice-summary__label">最近操作 / 风险</span>
        <strong class="notice-summary__value">{{ lastActionText }}</strong>
        <span class="notice-summary__hint">{{ riskText }}</span>
      </div>
    </section>

    <section class="notice-plain-guide">
      <div class="notice-plain-guide__header">
        <div>
          <div class="notice-plain-guide__title">如果你是第一次进公告管理，建议先这样做</div>
          <div class="notice-plain-guide__desc">先看当前状态，再筛范围，最后决定是发新公告、改时间，还是停用旧公告。</div>
        </div>
        <div class="notice-plain-guide__badge">当前重点：{{ noticeFocusLabel }}</div>
      </div>
      <div class="notice-plain-guide__grid">
        <div v-for="item in noticeGuideCards" :key="item.title" class="notice-plain-guide-card">
          <span class="notice-plain-guide-card__step">{{ item.step }}</span>
          <div class="notice-plain-guide-card__title">{{ item.title }}</div>
          <div class="notice-plain-guide-card__desc">{{ item.desc }}</div>
        </div>
      </div>
    </section>

    <section class="notice-followup">
      <div class="notice-followup__header">
        <div>
          <div class="notice-followup__title">公告发完后继续去哪</div>
          <div class="notice-followup__desc">把发布状态、时间窗口、会员触达和后台日志串起来，避免公告发完就失去跟踪。</div>
        </div>
        <div class="notice-followup__env">{{ runtimeEnvInfo.label }} / {{ runtimeEnvInfo.dataMode }}</div>
      </div>
      <div class="notice-followup__risk">{{ riskText }}</div>
      <div class="notice-followup__tags">
        <span v-for="item in followupTags" :key="item">{{ item }}</span>
      </div>
      <div class="notice-followup__grid">
        <button
          v-for="item in followupCards"
          :key="item.title"
          type="button"
          class="notice-followup-card"
          @click="goToPage(item.path, item.query)"
        >
          <span class="notice-followup-card__title">{{ item.title }}</span>
          <span class="notice-followup-card__desc">{{ item.desc }}</span>
        </button>
      </div>
    </section>

    <el-card class="notice-panel notice-panel--filter">
      <div class="panel-heading">
        <div>
          <div class="panel-heading__title">筛选条件</div>
          <div class="panel-heading__desc">按时间、状态和关键字快速筛选系统公告内容。</div>
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
              <el-select
                  v-model="query.is_disable"
                  @change="search()"
                  clearable
              >
                <el-option :value="0" label="启用" />
                <el-option :value="1" label="禁用" />
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
                  <el-option value="title" label="标题" />
                  <el-option value="desc" label="描述" />
                  <el-option value="remark" label="备注" />
                </el-select>
              </template>
            </el-input>
          </el-col>
          <el-col :span="6">
            <div class="filter-actions">
              <el-button type="primary" @click="search()">搜索</el-button>
              <el-button title="重置" @click="refresh()">重置</el-button>
            </div>
          </el-col>
        </el-row>
      </el-form>
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
        <el-form-item v-else-if="selectType === 'datetime'" label="时间范围">
          <el-date-picker
            v-model="start_time"
            type="datetime"
            value-format="YYYY-MM-DD HH:mm:ss"
            :default-time="new Date(2024, 1, 1, 0, 0, 0)"
            placeholder="开始时间"
          />
          <span>至</span>
          <el-date-picker
            v-model="end_time"
            type="datetime"
            value-format="YYYY-MM-DD HH:mm:ss"
            :default-time="new Date(2024, 1, 1, 23, 59, 59)"
            placeholder="结束时间"
          />
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
    <el-card class="notice-panel notice-panel--main">
      <div class="panel-heading panel-heading--content">
        <div>
          <div class="panel-heading__title">公告列表</div>
          <div class="panel-heading__desc">支持批量设置公告时段、发布状态和内容维护。</div>
        </div>
        <div class="panel-heading__meta">已选 {{ selection.length }} 项</div>
      </div>
      <div class="operation_btn mb5">
        <!-- 操作 -->
        <div>
          <div class="action-cluster">
            <span class="action-cluster__title">内容维护</span>
            <el-button type="primary" @click="add()">添加</el-button>
            <el-button title="时间范围" @click="selectOpen('datetime')">时间</el-button>
          </div>
          <div class="action-cluster">
            <span class="action-cluster__title">发布控制</span>
            <el-button title="是否禁用" @click="selectOpen('disable')">禁用</el-button>
            <el-button title="删除" @click="selectOpen('dele')">删除</el-button>
          </div>
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
      >
        <el-table-column type="selection" width="42" title="全选/反选" />
        <el-table-column :prop="idkey" label="ID" width="80" sortable="custom" />
        <el-table-column prop="image_id" label="图片" min-width="62">
          <template #default="scope">
            <FileImage :file-url="scope.row.image_url" lazy />
          </template>
        </el-table-column>
        <el-table-column prop="title" label="标题" min-width="220" show-overflow-tooltip>
          <template #default="scope">
            <span :style="{ color: scope.row.title_color }">{{ scope.row.title }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="start_time" label="开始时间" width="165" sortable="custom" />
        <el-table-column prop="end_time" label="结束时间" width="165" sortable="custom" />
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
        <el-table-column label="操作" width="170">
          <template #default="scope">
            <el-link type="primary" class="mr-1" :underline="false" @click="edit(scope.row)">
              修改
            </el-link>
            <el-link
              type="primary"
              class="mr-1"
              :underline="false"
              @click="goToPage('/system/user-log', buildEntryRouteQuery({
                notice_id: scope.row[idkey],
                title: scope.row.title || '',
                search_value: scope.row.title || ''
              }, 'system-notice'))"
            >
              日志
            </el-link>
            <el-link
              type="primary"
              class="mr-1"
              :underline="false"
              @click="goToPage('/member/member', buildEntryRouteQuery({
                notice_id: scope.row[idkey]
              }, 'system-notice'))"
            >
              会员
            </el-link>
            <el-link type="primary" :underline="false" @click="selectOpen('dele', [scope.row])">
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
    >
      <el-form ref="ref" :model="model" :rules="rules" label-width="100px">
        <el-tabs>
          <el-tab-pane label="信息">
            <el-scrollbar native :max-height="height - 30">
              <el-form-item label="图片" prop="image_id">
                <FileImage
                  v-model="model.image_id"
                  :file-url="model.image_url"
                  :height="100"
                  upload
                />
              </el-form-item>
              <el-form-item label="标题" prop="title">
                <el-col :span="18">
                  <el-input v-model="model.title" placeholder="请输入标题" clearable />
                </el-col>
                <el-col :span="3" style="text-align: center">标题颜色</el-col>
                <el-col :span="3">
                  <el-color-picker v-model="model.title_color" />
                </el-col>
              </el-form-item>
              <el-form-item label="简介" prop="desc">
                <el-input v-model="model.desc" type="textarea" autosize placeholder="请输入简介" />
              </el-form-item>
              <el-form-item label="开始时间" prop="start_time">
                <el-date-picker
                  v-model="model.start_time"
                  type="datetime"
                  value-format="YYYY-MM-DD HH:mm:ss"
                  :default-time="new Date(2024, 1, 1, 0, 0, 0)"
                  placeholder="开始时间"
                />
              </el-form-item>
              <el-form-item label="结束时间" prop="end_time">
                <el-date-picker
                  v-model="model.end_time"
                  type="datetime"
                  value-format="YYYY-MM-DD HH:mm:ss"
                  :default-time="new Date(2024, 1, 1, 23, 59, 59)"
                  placeholder="结束时间"
                />
              </el-form-item>
              <el-form-item label="备注" prop="remark">
                <el-input v-model="model.remark" placeholder="请输入备注" clearable />
              </el-form-item>
              <el-form-item label="排序" prop="sort">
                <el-input v-model="model.sort" type="number" />
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
            <el-scrollbar native :height="height - 80">
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
  </div>
</template>

<script>
import screenHeight from '@/utils/screen-height'
import Pagination from '@/components/Pagination/index.vue'
import RichEditor from '@/components/RichEditor/index.vue'
import { arrayColumn } from '@/utils/index'
import { getPageLimit } from '@/utils/settings'
import { list, info, add, edit, dele, disable, datetime } from '@/api/system/notice'
import { resolveAdminRuntimeEnv } from '@/utils/runtime-env'

export default {
  name: 'SystemNotice',
  components: { Pagination, RichEditor },
  data() {
    return {
      name: '公告',
      height: 680,
      loading: false,
      idkey: 'notice_id',
      exps: [{ exp: 'like', name: '包含' }],
      query: {
        page: 1,
        limit: getPageLimit(),
        search_field: 'title',
        search_exp: 'like',
        search_value: '',
        date_field: 'create_time',
        is_disable:undefined,
      },
      data: [],
      count: 0,
      dialog: false,
      dialogTitle: '',
      model: {
        notice_id: '',
        image_id: 0,
        image_url: '',
        type: 1,
        title: '',
        title_color: '',
        start_time: '',
        end_time: '',
        desc: '',
        content: '',
        remark: '',
        sort: 250
      },
      rules: {
        title: [{ required: true, message: '请输入标题', trigger: 'blur' }],
        start_time: [{ required: true, message: '请输入开始时间', trigger: 'blur' }],
        end_time: [{ required: true, message: '请输入结束时间', trigger: 'blur' }]
      },
      selection: [],
      selectIds: '',
      selectTitle: '操作',
      selectDialog: false,
      selectType: '',
      lastActionText: '列表已加载',
      is_disable: 0,
      start_time: '',
      end_time: '',
      runtimeEnvInfo: resolveAdminRuntimeEnv()
    }
  },
  computed: {
    entrySourceLabel() {
      const source = String(this.$route?.query?.from || '')
      if (source === 'dashboard') return '来自后台首页'
      if (source === 'system-setting') return '来自系统设置'
      if (source === 'system-user-log') return '来自用户日志'
      if (source === 'member-member') return '来自会员列表'
      if (source === 'member-statistic') return '来自会员统计'
      return ''
    },
    entryContextVisible() {
      return Boolean(this.entrySourceLabel)
    },
    entryContextTitle() {
      if (this.entrySourceLabel === '来自后台首页') return '当前从后台首页进入系统公告'
      if (this.entrySourceLabel === '来自系统设置') return '当前从系统设置进入系统公告'
      if (this.entrySourceLabel === '来自用户日志') return '当前从用户日志进入系统公告'
      if (this.entrySourceLabel === '来自会员列表') return '当前从会员列表进入系统公告'
      if (this.entrySourceLabel === '来自会员统计') return '当前从会员统计进入系统公告'
      return '当前为外部入口承接视角'
    },
    entryContextDesc() {
      if (this.entrySourceLabel === '来自后台首页') {
        return '这类进入通常是为了从首页继续排查最近公告有没有影响运营展示。建议先分清启用中和停用中的公告，再继续改时间或内容。'
      }
      if (this.entrySourceLabel === '来自系统设置') {
        return '这类进入通常是为了核公告配置和实际公告内容有没有脱节。建议先看当前启用公告，再回系统设置页复核富文本或上传配置。'
      }
      if (this.entrySourceLabel === '来自用户日志') {
        return '这类进入通常是为了追某次公告操作有没有真正落地。建议先锁定公告，再回日志页继续确认修改、停用或删除记录。'
      }
      if (this.entrySourceLabel === '来自会员列表') {
        return '这类进入通常是为了把会员触达问题落到具体公告。建议先看当前生效公告，再回会员页确认目标人群承接。'
      }
      return '这类进入通常是为了把会员统计波动落到具体公告投放。建议先确认时间窗和启禁状态，再回统计页复盘效果。'
    },
    entryContextPrimaryLabel() {
      if (this.entrySourceLabel === '来自系统设置') return '回系统设置'
      if (this.entrySourceLabel === '来自用户日志') return '回用户日志'
      if (this.entrySourceLabel === '来自会员列表') return '回会员列表'
      if (this.entrySourceLabel === '来自会员统计') return '回会员统计'
      return '回后台首页'
    },
    currentStatusText() {
      return this.query.is_disable === undefined ? '全部' : this.query.is_disable === 1 ? '禁用' : '启用'
    },
    noticeFocusLabel() {
      if (this.selection.length) {
        return `先确认这 ${this.selection.length} 条批量操作会不会影响线上展示`
      }
      if (this.query.is_disable === 1) {
        return '先看哪些公告已经停用，避免误恢复'
      }
      if (this.query.is_disable === 0) {
        return '先看当前启用中的公告是否还在正确时间窗'
      }
      return '先分清哪些公告在用、哪些可以停'
    },
    noticeGuideCards() {
      return [
        {
          step: '第一步',
          title: '先看现在生效的是哪批公告',
          desc: '优先用状态和时间范围筛选，先把“正在展示”和“已经过期”的公告分开。'
        },
        {
          step: '第二步',
          title: '再决定是改内容还是改投放时间',
          desc: '文案、图片、富文本走编辑；需要统一调整上线周期时，直接走批量时间。'
        },
        {
          step: '第三步',
          title: '改完后去日志或会员页看承接',
          desc: '公告不是发完就结束，最好继续确认后台日志和会员触达有没有跟上。'
        }
      ]
    },
    riskText() {
      if (this.selectType === 'dele' && this.selection.length) {
        return '高风险：删除后不可恢复，请确认选中项。'
      }
      if (this.selectType === 'disable' && this.selection.length) {
        return '中风险：批量修改发布状态会立即影响展示。'
      }
      if (this.selectType === 'datetime' && this.selection.length) {
        return '注意时间范围是否覆盖当前线上投放周期。'
      }
      if (this.selection.length) {
        return '已进入批量操作上下文，请核对选中公告。'
      }
      return '当前无高风险操作，可继续筛选或维护内容。'
    },
    followupTags() {
      return [
        `公告总量：${this.count}`,
        `已选：${this.selection.length} 项`,
        `状态：${this.currentStatusText}`,
        `检索字段：${this.query.search_field || 'title'}`
      ]
    },
    followupCards() {
      if (this.selection.length) {
        return [
          {
            title: '去操作日志复核',
            desc: '批量改完公告状态或时间后，先去日志页确认后台操作有没有异常回显。',
            path: '/system/user-log',
            query: this.buildEntryRouteQuery({
              notice_ids: this.selectIds
            }, 'system-notice')
          },
          {
            title: '去会员页看承接',
            desc: '如果公告关联的是用户触达或运营通知，继续到会员页更容易落到具体人。',
            path: '/member/member',
            query: this.buildEntryRouteQuery({
              notice_ids: this.selectIds
            }, 'system-notice')
          },
          {
            title: '去系统设置核入口',
            desc: '需要继续确认公告相关配置、富文本或基础参数时，直接去系统设置页。',
            path: '/system/setting',
            query: this.buildEntryRouteQuery({}, 'system-notice')
          }
        ]
      }
      return [
        {
          title: '去操作日志巡检',
          desc: '先从日志页看最近公告操作记录，再决定是否回公告页继续改。',
          path: '/system/user-log',
          query: this.buildEntryRouteQuery({}, 'system-notice')
        },
        {
          title: '去会员页看触达对象',
          desc: '当公告面向具体人群时，会员页是继续做承接和核对的自然下一站。',
          path: '/member/member',
          query: this.buildEntryRouteQuery({}, 'system-notice')
        },
        {
          title: '去系统设置页复核配置',
          desc: '需要继续确认富文本、上传和系统配置时，直接去系统设置页会更顺。',
          path: '/system/setting',
          query: this.buildEntryRouteQuery({}, 'system-notice')
        }
      ]
    }
  },
  watch: {
    '$route.fullPath'() {
      this.applyRouteQuery()
      this.list()
    }
  },
  created() {
    this.height = screenHeight()
    this.applyRouteQuery()
    this.list()
  },
  methods: {
    buildEntryRouteQuery(extraQuery = {}, nextFrom = '') {
      const query = {
        ...this.$route.query,
        ...extraQuery
      }
      if (nextFrom) {
        query.from = nextFrom
      }
      return query
    },
    handleEntryContextPrimary() {
      if (this.entrySourceLabel === '来自系统设置') {
        this.goToPage('/system/setting', this.buildEntryRouteQuery({}, 'system-notice'))
        return
      }
      if (this.entrySourceLabel === '来自用户日志') {
        this.goToPage('/system/user-log', this.buildEntryRouteQuery({}, 'system-notice'))
        return
      }
      if (this.entrySourceLabel === '来自会员列表') {
        this.goToPage('/member/member', this.buildEntryRouteQuery({}, 'system-notice'))
        return
      }
      if (this.entrySourceLabel === '来自会员统计') {
        this.goToPage('/member/statistic', this.buildEntryRouteQuery({}, 'system-notice'))
        return
      }
      this.goToPage('/dashboard', this.buildEntryRouteQuery({}, 'system-notice'))
    },
    goToEntryContextBack() {
      this.handleEntryContextPrimary()
    },
    parseRouteNumber(value) {
      if (value === undefined || value === null || value === '') {
        return undefined
      }
      const parsed = Number(value)
      return Number.isFinite(parsed) ? parsed : undefined
    },
    applyRouteQuery() {
      const nextQuery = {
        ...this.$options.data().query,
        limit: this.query?.limit || getPageLimit()
      }
      const routeQuery = this.$route?.query || {}
      if (routeQuery.search_field) {
        nextQuery.search_field = String(routeQuery.search_field)
      }
      if (routeQuery.search_exp) {
        nextQuery.search_exp = String(routeQuery.search_exp)
      }
      if (routeQuery.search_value !== undefined || routeQuery.keyword !== undefined) {
        nextQuery.search_value = String(routeQuery.search_value ?? routeQuery.keyword ?? '')
      }
      const isDisable = this.parseRouteNumber(routeQuery.is_disable)
      if (isDisable !== undefined) {
        nextQuery.is_disable = isDisable
      }
      this.query = nextQuery
    },
    goToPage(path, query = {}) {
      this.$router.push({
        path,
        query
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
          this.lastActionText = '列表刷新于当前检索结果'
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
      this.lastActionText = '打开新增公告弹窗'
      this.reset()
    },
    edit(row) {
      this.dialog = true
      this.dialogTitle = this.name + '修改：' + row[this.idkey]
      this.lastActionText = '打开公告修改：' + row[this.idkey]
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
      this.lastActionText = '关闭公告编辑弹窗'
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
                this.lastActionText = '提交公告修改：' + this.model[this.idkey]
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
                this.lastActionText = '提交新增公告'
                ElMessage.success(res.msg)
              })
              .catch(() => {
                this.loading = false
              })
          }
        } else {
          ElMessage.error('请完善必填项（带红色星号*）')
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
      this.lastActionText = '执行筛选检索'
      this.list()
    },
    // 刷新
    refresh() {
      const limit = this.query.limit
      this.query = this.$options.data().query
      this.$refs['table'].clearSort()
      this.query.limit = limit
      this.lastActionText = '重置筛选条件'
      this.list()
    },
    // 排序
    sort(sort) {
      this.query.sort_field = sort.prop
      this.query.sort_value = ''
      if (sort.order === 'ascending') {
        this.query.sort_value = 'asc'
        this.lastActionText = '按' + sort.prop + '升序排序'
        this.list()
      }
      if (sort.order === 'descending') {
        this.query.sort_value = 'desc'
        this.lastActionText = '按' + sort.prop + '降序排序'
        this.list()
      }
    },
    // 操作
    select(selection) {
      this.selection = selection
      this.selectIds = this.selectGetIds(selection).toString()
      this.lastActionText = selection.length ? '更新批量选择：' + selection.length + ' 项' : '清空批量选择'
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
        } else if (selectType === 'datetime') {
          this.selectTitle = this.name + '时间范围'
        } else if (selectType === 'dele') {
          this.selectTitle = this.name + '删除'
        }
        this.selectDialog = true
        this.selectType = selectType
        this.lastActionText = '打开批量操作：' + this.selectTitle
      }
    },
    selectCancel() {
      this.selectDialog = false
      this.lastActionText = '取消批量操作'
    },
    selectSubmit() {
      if (!this.selection.length) {
        this.selectAlert()
      } else {
        const selectType = this.selectType
        if (selectType === 'disable') {
          this.disable(this.selection, true)
        } else if (selectType === 'datetime') {
          this.datetime(this.selection)
        } else if (selectType === 'dele') {
          this.dele(this.selection)
        }
        this.lastActionText = '提交批量操作：' + this.selectTitle
        this.selectDialog = false
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
            this.lastActionText = (select ? '批量' : '单条') + '更新禁用状态'
            this.list()
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.list()
          })
      }
    },
    // 时间范围
    datetime(row) {
      if (!row.length) {
        this.selectAlert()
      } else {
        this.loading = true
        datetime({
          ids: this.selectGetIds(row),
          start_time: this.start_time,
          end_time: this.end_time
        })
          .then((res) => {
            this.lastActionText = '批量更新时间范围'
            this.list()
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.loading = false
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
            this.lastActionText = '执行删除操作'
            this.list()
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.loading = false
          })
      }
    }
  }
}
</script>

<style scoped>
.notice-page {
  padding-top: 8px;
}

.entry-context-banner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 14px;
  padding: 14px 16px;
  border-radius: 16px;
  background: linear-gradient(135deg, #f5f7ff 0%, #fffaf0 100%);
  border: 1px solid #e5e7eb;
}

.entry-context-banner__main {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.entry-context-banner__eyebrow {
  font-size: 12px;
  font-weight: 700;
  color: #909399;
}

.entry-context-banner__title {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
}

.entry-context-banner__desc {
  font-size: 13px;
  line-height: 1.6;
  color: #475569;
}

.entry-context-banner__actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  justify-content: flex-end;
}

.notice-hero {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 14px;
}

.notice-hero__eyebrow {
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.16em;
  text-transform: uppercase;
  color: #8b9bb3;
}

.notice-hero__headline {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-top: 6px;
}

.notice-hero__headline h1 {
  margin: 0;
  font-size: 28px;
  line-height: 1.1;
  color: #10233f;
}

.notice-hero__env {
  display: inline-flex;
  align-items: center;
  height: 24px;
  padding: 0 10px;
  border-radius: 999px;
  background: rgba(12, 74, 110, 0.08);
  color: #0c4a6e;
  font-size: 12px;
  font-weight: 700;
}

.notice-hero__desc {
  margin: 10px 0 0;
  font-size: 13px;
  color: #5b6b82;
}

.notice-hero__meta {
  display: grid;
  gap: 6px;
  min-width: 220px;
  padding-top: 2px;
  font-size: 12px;
  color: #6b7b92;
  text-align: right;
}

.notice-summary {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
  margin-bottom: 14px;
}

.notice-followup {
  margin-bottom: 14px;
  padding: 16px;
  border: 1px solid #e7edf5;
  border-radius: 16px;
  background: linear-gradient(135deg, #f8fbff 0%, #ffffff 100%);
}

.notice-plain-guide {
  margin-bottom: 14px;
  padding: 16px;
  border: 1px solid #dbe7f5;
  border-radius: 16px;
  background: linear-gradient(135deg, #f7fbff 0%, #ffffff 100%);
}

.notice-plain-guide__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.notice-plain-guide__title {
  font-size: 16px;
  font-weight: 700;
  color: #10233f;
}

.notice-plain-guide__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #6b7b92;
}

.notice-plain-guide__badge {
  min-width: 220px;
  padding: 10px 12px;
  border-radius: 12px;
  background: rgba(37, 99, 235, 0.08);
  color: #1d4ed8;
  font-size: 12px;
  font-weight: 700;
  line-height: 1.6;
}

.notice-plain-guide__grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-top: 14px;
}

.notice-plain-guide-card {
  padding: 14px 16px;
  border: 1px solid #e7edf5;
  border-radius: 14px;
  background: #fff;
}

.notice-plain-guide-card__step {
  display: inline-flex;
  align-items: center;
  min-height: 26px;
  padding: 0 10px;
  border-radius: 999px;
  background: #eff6ff;
  color: #1d4ed8;
  font-size: 12px;
  font-weight: 700;
}

.notice-plain-guide-card__title {
  margin-top: 10px;
  font-size: 14px;
  font-weight: 700;
  color: #10233f;
}

.notice-plain-guide-card__desc {
  margin-top: 8px;
  font-size: 12px;
  line-height: 1.7;
  color: #5b6b82;
}

.notice-followup__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.notice-followup__title {
  font-size: 16px;
  font-weight: 700;
  color: #10233f;
}

.notice-followup__desc,
.notice-followup__env {
  margin-top: 6px;
  font-size: 12px;
  color: #6b7b92;
  line-height: 1.7;
}

.notice-followup__env {
  min-width: 220px;
  text-align: right;
}

.notice-followup__risk {
  margin-top: 12px;
  padding: 12px 14px;
  border-radius: 12px;
  border: 1px solid #fed7aa;
  background: #fff7ed;
  color: #9a3412;
  font-size: 12px;
  line-height: 1.7;
}

.notice-followup__tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 12px;
}

.notice-followup__tags span {
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  padding: 0 10px;
  border: 1px solid #e7edf5;
  border-radius: 999px;
  background: #fff;
  color: #4b5d78;
  font-size: 12px;
}

.notice-followup__grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-top: 14px;
}

.notice-followup-card {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 8px;
  padding: 14px 16px;
  border: 1px solid #e7edf5;
  border-radius: 14px;
  background: #fff;
  text-align: left;
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}

.notice-followup-card:hover {
  transform: translateY(-1px);
  border-color: rgba(59, 130, 246, 0.24);
  box-shadow: 0 14px 30px rgba(59, 130, 246, 0.08);
}

.notice-followup-card__title {
  font-size: 14px;
  font-weight: 700;
  color: #10233f;
}

.notice-followup-card__desc {
  font-size: 12px;
  color: #5b6b82;
  line-height: 1.6;
}

.notice-summary__item {
  padding: 14px 16px;
  border: 1px solid #e7edf5;
  border-radius: 14px;
  background: #fff;
}

.notice-summary__item--wide {
  background: linear-gradient(135deg, #f8fbff 0%, #ffffff 100%);
}

.notice-summary__label {
  display: block;
  margin-bottom: 8px;
  margin-top: 4px;
  font-size: 12px;
  color: #7c8aa5;
}

.notice-summary__value {
  display: block;
  font-size: 20px;
  font-weight: 600;
  color: #10233f;
}

.notice-summary__hint {
  display: block;
  margin-top: 8px;
  font-size: 12px;
  color: #6b7b92;
}

.notice-panel {
  border-radius: 16px;
  border: 1px solid #e9eef5;
  box-shadow: none;
}

.notice-panel--filter {
  margin-bottom: 14px;
}

.panel-heading {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 16px;
}

.panel-heading--content {
  margin-bottom: 14px;
}

.panel-heading__title {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
}

.panel-heading__desc {
  margin-top: 4px;
  font-size: 12px;
  color: #64748b;
}

.panel-heading__meta {
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
  white-space: nowrap;
}

.filter-actions {
  display: flex;
  align-items: center;
  gap: 10px;
  min-height: 32px;
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
  .notice-hero,
  .panel-heading,
  .notice-followup__header,
  .notice-plain-guide__header {
    flex-direction: column;
  }

  .notice-hero__meta,
  .panel-heading__meta,
  .notice-followup__env,
  .notice-plain-guide__badge {
    min-width: 0;
    text-align: left;
    white-space: normal;
  }

  .notice-summary,
  .notice-plain-guide__grid,
  .notice-followup__grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 640px) {
  .notice-summary,
  .notice-plain-guide__grid,
  .notice-followup__grid {
    grid-template-columns: 1fr;
  }

  .notice-hero__headline h1 {
    font-size: 24px;
  }
}
</style>
