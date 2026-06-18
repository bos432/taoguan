<template>
  <div class="app-container">
    <el-card class="app-head head-pb20 third-page-shell">
      <div class="third-page-header">
        <div>
          <div class="third-page-header__title">第三方登录 / 会员第三方</div>
          <div class="third-page-header__desc">按平台、应用、状态和第三方标识检索会员已绑定的外部账号。</div>
        </div>
        <el-tag class="third-page-header__env" size="large" effect="plain" round>
          {{ environmentTag }}
        </el-tag>
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
      <div class="third-summary-bar">
        <div class="third-summary-bar__item">
          <span class="third-summary-bar__label">账号总量</span>
          <strong class="third-summary-bar__value">{{ count }}</strong>
        </div>
        <div class="third-summary-bar__item">
          <span class="third-summary-bar__label">已选</span>
          <strong class="third-summary-bar__value">{{ selection.length }}</strong>
        </div>
        <div class="third-summary-bar__item">
          <span class="third-summary-bar__label">平台</span>
          <strong class="third-summary-bar__value">{{ currentPlatformLabel }}</strong>
        </div>
        <div class="third-summary-bar__item">
          <span class="third-summary-bar__label">检索字段</span>
          <strong class="third-summary-bar__value">{{ currentSearchFieldLabel }}</strong>
        </div>
      </div>
      <div class="third-guide-panel">
        <div class="third-guide-panel__header">
          <div>
            <div class="third-guide-panel__title">第一次查第三方绑定，先这样看</div>
            <div class="third-guide-panel__desc">这页主要回答“这个会员绑了哪个外部账号、现在还能不能用”，不是直接改登录配置。</div>
          </div>
          <div class="third-guide-panel__badge">{{ thirdGuideFocusLabel }}</div>
        </div>
        <div class="third-guide-panel__grid">
          <div v-for="item in thirdGuideCards" :key="item.title" class="third-guide-card">
            <span class="third-guide-card__step">{{ item.step }}</span>
            <div class="third-guide-card__title">{{ item.title }}</div>
            <div class="third-guide-card__desc">{{ item.desc }}</div>
          </div>
        </div>
      </div>
      <div class="third-followup-strip">
        <button type="button" class="third-followup-card" @click="goToCurrentMember">
          <span class="third-followup-card__title">去会员页核对</span>
          <span class="third-followup-card__desc">{{ currentMemberActionText }}</span>
        </button>
        <button type="button" class="third-followup-card" @click="goToCurrentLog">
          <span class="third-followup-card__title">去会员日志排查</span>
          <span class="third-followup-card__desc">{{ currentLogActionText }}</span>
        </button>
        <button type="button" class="third-followup-card" @click="goToMemberSetting">
          <span class="third-followup-card__title">回第三方设置</span>
          <span class="third-followup-card__desc">如果要改登录绑定策略、平台接入或默认配置，继续去会员设置里的第三方账号设置。</span>
        </button>
      </div>
      <!-- 查询 -->
      <el-form :model="query" ref="searchForm" label-width="85px" class="third-filter-form">
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
            <el-form-item label="平台：" prop="platform">
              <el-select
                  v-model="query.platform"
                  clearable
              >
                <el-option
                    v-for="platform in platforms"
                    :key="platform.value"
                    :value="platform.value"
                    :label="platform.label"
                />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="应用：" prop="application">
              <el-select
                  v-model="query.application"
                  clearable
              >
                <el-option
                    v-for="application in applications"
                    :key="application.value"
                    :value="application.value"
                    :label="application.label"
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
                  <el-option value="member_id" label="会员ID" />
                  <el-option value="nickname" label="昵称" />
                  <el-option value="remark" label="备注" />
                  <el-option value="unionid" label="unionid" />
                  <el-option value="openid" label="openid" />
                </el-select>
              </template>
            </el-input>
          </el-col>
          <el-col :span="6">
            <el-button type="primary" @click="search()">搜索</el-button>
            <el-button title="重置" @click="refresh()">
              重置
            </el-button>
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
        <el-form-item v-else-if="selectType === 'dele'">
          <span class="c-red">确定要删除选中的{{ name }}吗？</span>
        </el-form-item>
        <el-form-item :label="name + 'ID'">
          <el-input v-model="selectIds" type="textarea" autosize disabled />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="selectCancel">取消</el-button>
        <el-button type="primary" @click="selectSubmit">提交</el-button>
      </template>
    </el-dialog>
    <el-card class="app-main third-page-main">
      <div class="operation_btn mb5">
        <!-- 操作 -->
        <div>
          <div class="action-cluster">
            <span class="action-cluster__title">账号维护</span>
            <el-button type="primary" @click="add()">添加</el-button>
            <el-button title="是否禁用" @click="selectOpen('disable')">禁用</el-button>
          </div>
          <div class="action-cluster">
            <span class="action-cluster__title">解绑处理</span>
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
        <el-table-column prop="member_id" label="会员ID" width="100" sortable="custom" />
        <el-table-column
          prop="member_nickname"
          label="会员昵称"
          min-width="135"
          show-overflow-tooltip
        />
        <el-table-column
          prop="member_username"
          label="会员用户名"
          min-width="135"
          show-overflow-tooltip
        />
        <el-table-column prop="headimgurl" label="头像" min-width="62">
          <template #default="scope">
            <FileImage :file-url="scope.row.headimgurl" avatar lazy />
          </template>
        </el-table-column>
        <el-table-column prop="nickname" label="昵称" min-width="135" show-overflow-tooltip />
        <el-table-column prop="platform_name" label="平台" min-width="80" show-overflow-tooltip />
        <el-table-column prop="application_name" label="应用" min-width="120" show-overflow-tooltip />
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
        <el-table-column prop="login_time" label="登录时间" width="165" />
        <el-table-column prop="create_time" label="添加/绑定时间" min-width="165" />
        <el-table-column prop="update_time" label="修改时间" min-width="165" />
        <el-table-column label="操作" width="150">
          <template #default="scope">
            <el-link type="primary" class="mr-1" :underline="false" @click="edit(scope.row)">
              修改
            </el-link>
            <el-link
              v-if="scope.row.member_id"
              type="primary"
              class="mr-1"
              :underline="false"
              @click="goToMember(scope.row)"
            >
              会员
            </el-link>
            <el-link
              v-if="scope.row.member_id"
              type="primary"
              class="mr-1"
              :underline="false"
              @click="goToMemberLog(scope.row)"
            >
              日志
            </el-link>
            <el-link
              type="primary"
              title="解绑"
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
    >
      <el-scrollbar native :max-height="height - 30">
        <el-form ref="ref" :rules="rules" :model="model" label-width="100px">
          <el-form-item label="会员ID" prop="member_id">
            <el-input
              v-model="model.member_id"
              type="number"
              placeholder="请输入会员ID"
              clearable
            />
          </el-form-item>
          <el-form-item label="平台" prop="platform">
            <el-select v-model="model.platform">
              <el-option
                v-for="platform in platforms"
                :key="platform.value"
                :value="platform.value"
                :label="platform.label"
              />
            </el-select>
          </el-form-item>
          <el-form-item label="应用" prop="application">
            <el-select v-model="model.application">
              <el-option
                v-for="application in applications"
                :key="application.value"
                :value="application.value"
                :label="application.label"
              />
            </el-select>
          </el-form-item>
          <el-form-item label="unionid" prop="unionid">
            <el-input v-model="model.unionid" placeholder="请输入unionid" clearable />
          </el-form-item>
          <el-form-item label="openid" prop="openid">
            <el-input v-model="model.openid" placeholder="请输入openid" clearable />
          </el-form-item>
          <el-form-item label="头像" prop="headimgurl">
            <el-input v-model="model.headimgurl" placeholder="请输入头像链接或上传头像" clearable />
          </el-form-item>
          <el-form-item prop="headimgurl">
            <FileImage
              v-model:file-url="model.headimgurl"
              file-title="上传头像"
              :height="100"
              avatar
              upload
            />
          </el-form-item>
          <el-form-item label="昵称" prop="nickname">
            <el-input v-model="model.nickname" placeholder="请输入昵称" clearable />
          </el-form-item>
          <el-form-item label="备注" prop="remark">
            <el-input v-model="model.remark" placeholder="请输入备注" clearable />
          </el-form-item>
          <el-form-item v-if="model[idkey]" label="登录IP" prop="login_ip">
            <el-input v-model="model.login_ip" disabled />
          </el-form-item>
          <el-form-item v-if="model[idkey]" label="登录地区" prop="login_region">
            <el-input v-model="model.login_region" disabled />
          </el-form-item>
          <el-form-item v-if="model[idkey]" label="登录时间" prop="login_time">
            <el-input v-model="model.login_time" disabled />
          </el-form-item>
          <el-form-item v-if="model[idkey]" label="登录次数" prop="login_num">
            <el-input v-model="model.login_num" disabled />
          </el-form-item>
          <el-form-item v-if="model[idkey]" label="添加/绑定时间" prop="create_time">
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
        <div class="dialog-footer-actions">
          <el-button v-if="model.member_id" @click="goToMember(model)">去会员页</el-button>
          <el-button v-if="model.member_id" @click="goToMemberLog(model)">去会员日志</el-button>
          <el-button @click="goToMemberSetting">第三方设置</el-button>
        </div>
        <el-button :loading="loading" @click="cancel">取消</el-button>
        <el-button :loading="loading" type="primary" @click="submit">提交</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script>
import screenHeight from '@/utils/screen-height'
import Pagination from '@/components/Pagination/index.vue'
import { arrayColumn } from '@/utils/index'
import { getPageLimit } from '@/utils/settings'
import { list, info, add, edit, dele, disable } from '@/api/member/third'

export default {
  name: 'MemberThird',
  components: { Pagination },
  computed: {
    entrySourceLabel() {
      const source = String(this.$route?.query?.from || '')
      if (source === 'member-member') return '来自会员列表'
      if (source === 'member-log') return '来自会员日志'
      if (source === 'member-setting') return '来自会员设置'
      if (source === 'member-setting-third') return '来自会员设置'
      if (source === 'member-setting-api') return '来自会员设置'
      if (source === 'member-setting-log') return '来自会员设置'
      if (source === 'member-setting-logreg') return '来自会员设置'
      if (source === 'member-setting-member') return '来自会员设置'
      if (source === 'dashboard') return '来自后台首页'
      return ''
    },
    entryContextVisible() {
      return Boolean(this.entrySourceLabel)
    },
    entryContextTitle() {
      if (this.entrySourceLabel === '来自会员列表') return '当前从会员列表进入第三方登录'
      if (this.entrySourceLabel === '来自会员日志') return '当前从会员日志进入第三方登录'
      if (this.entrySourceLabel === '来自会员设置') return '当前从会员设置进入第三方登录'
      if (this.entrySourceLabel === '来自后台首页') return '当前从后台首页进入第三方登录'
      return '当前为外部入口承接视角'
    },
    entryContextDesc() {
      if (this.entrySourceLabel === '来自会员列表') {
        return '这类进入通常是为了确认某个会员绑了哪个第三方账号。建议先锁定会员和平台，再回会员页复核账号资料与绑定状态。'
      }
      if (this.entrySourceLabel === '来自会员日志') {
        return '这类进入通常是为了追第三方登录或解绑行为。建议先确认平台、应用和第三方标识，再回会员日志继续排查操作链路。'
      }
      if (this.entrySourceLabel === '来自会员设置') {
        return '这类进入通常是为了把第三方登录配置继续落到真实绑定账号。建议先看当前账号绑定情况，再回会员设置页调整平台接入策略。'
      }
      return '这类进入通常是首页巡检后的继续下钻。建议先按平台和应用缩小范围，再继续去会员页或会员日志确认真实影响面。'
    },
    entryContextPrimaryLabel() {
      if (this.entrySourceLabel === '来自会员列表') return '回会员列表'
      if (this.entrySourceLabel === '来自会员日志') return '回会员日志'
      if (this.entrySourceLabel === '来自会员设置') return '回会员设置'
      return '回后台首页'
    },
    environmentTag() {
      const host = window.location.hostname || ''
      if (!host) {
        return '本地环境'
      }
      if (host === 'localhost' || host === '127.0.0.1') {
        return '本地环境'
      }
      if (host.includes('test') || host.includes('uat')) {
        return '测试环境'
      }
      if (host.includes('pre') || host.includes('staging')) {
        return '预发环境'
      }
      return '生产环境'
    },
    currentPlatformLabel() {
      if (this.query.platform === undefined) {
        return '全部平台'
      }
      const current = this.platforms.find((item) => item.value === this.query.platform)
      return current ? current.label : this.query.platform
    },
    currentSearchFieldLabel() {
      const fieldMap = {
        [this.idkey]: 'ID',
        member_id: '会员ID',
        nickname: '昵称',
        remark: '备注',
        unionid: 'unionid',
        openid: 'openid'
      }
      return fieldMap[this.query.search_field] || '会员ID'
    },
    currentFocusRow() {
      if (this.model && this.model[this.idkey]) {
        return this.model
      }
      return this.selection[0] || {}
    },
    currentMemberActionText() {
      const memberId = this.currentFocusRow.member_id || this.query.search_value
      if (memberId && this.query.search_field === 'member_id') {
        return `把当前第三方绑定直接定位到会员 ${memberId}，继续看会员资料、状态和绑定结果。`
      }
      if (this.currentFocusRow.member_id) {
        return `把当前第三方绑定直接定位到会员 ${this.currentFocusRow.member_id}，继续看会员资料、状态和绑定结果。`
      }
      return '当前还没锁定具体会员，先勾选一条或打开详情后再继续去会员页。'
    },
    currentLogActionText() {
      if (this.currentFocusRow.member_id) {
        return `继续排查会员 ${this.currentFocusRow.member_id} 的登录日志、接口日志和第三方登录行为。`
      }
      return '当前还没锁定具体会员，先勾选一条或打开详情后再继续去会员日志。'
    },
    thirdGuideFocusLabel() {
      if (this.selection.length) {
        return `先核对这 ${this.selection.length} 条绑定记录是不是同一平台、同一问题`
      }
      if (this.query.platform !== undefined) {
        return `先把 ${this.currentPlatformLabel} 平台的绑定状态看清楚`
      }
      return '先定位平台和会员，再判断是解绑问题还是配置问题'
    },
    thirdGuideCards() {
      return [
        {
          step: '第一步',
          title: '先按平台和会员缩小范围',
          desc: '优先先选平台、应用，再搜会员 ID 或昵称，别在全量绑定里盲找。'
        },
        {
          step: '第二步',
          title: '再判断是记录异常还是登录配置异常',
          desc: '如果只是单条绑定不对，先看会员和日志；如果整个平台都异常，再回第三方设置核配置。'
        },
        {
          step: '第三步',
          title: '最后再做禁用或删除',
          desc: '删除和禁用会直接影响会员后续登录绑定，最好在确认归因后再执行。'
        }
      ]
    }
  },
  data() {
    return {
      name: '会员第三方账号',
      height: 680,
      loading: false,
      idkey: 'third_id',
      exps: [{ exp: 'like', name: '包含' }],
      query: {
        page: 1,
        limit: getPageLimit(),
        search_field: 'nickname',
        search_exp: 'like',
        date_field: 'create_time',
        is_disable:undefined,
        platform:undefined,
        application:undefined,
      },
      data: [],
      count: 0,
      platforms: [],
      applications: [],
      dialog: false,
      dialogTitle: '',
      model: {
        third_id: '',
        member_id: 0,
        platform: 20,
        application: 21,
        unionid: '',
        openid: '',
        headimgurl: '',
        nickname: '',
        remark: ''
      },
      rules: {
        member_id: [{ required: true, message: '请输入openid', trigger: 'blur' }],
        openid: [{ required: true, message: '请输入openid', trigger: 'blur' }]
      },
      selection: [],
      selectIds: '',
      selectTitle: '操作',
      selectDialog: false,
      selectType: '',
      is_disable: 0
    }
  },
  created() {
    this.height = screenHeight()
    this.list()
  },
  methods: {
    buildEntryRouteQuery(extraQuery = {}, nextFrom = 'member-third') {
      return {
        ...this.$route.query,
        ...extraQuery,
        from: nextFrom
      }
    },
    handleEntryContextPrimary() {
      if (this.entrySourceLabel === '来自会员列表') {
        this.$router.push({
          path: '/member/member',
          query: this.buildEntryRouteQuery({}, 'member-third')
        })
        return
      }
      if (this.entrySourceLabel === '来自会员日志') {
        this.$router.push({
          path: '/member/log',
          query: this.buildEntryRouteQuery({}, 'member-third')
        })
        return
      }
      if (this.entrySourceLabel === '来自会员设置') {
        this.goToMemberSetting()
        return
      }
      this.$router.push({
        path: '/dashboard',
        query: this.buildEntryRouteQuery({}, 'member-third')
      })
    },
    goToEntryContextBack() {
      this.handleEntryContextPrimary()
    },
    // 列表
    list() {
      this.loading = true
      list(this.query)
        .then((res) => {
          this.data = res.data.list
          this.count = res.data.count
          this.exps = res.data.exps
          this.platforms = res.data.platforms
          this.applications = res.data.applications
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
    goToMember(row = {}) {
      if (!row.member_id) {
        return
      }
      this.$router.push({
        path: '/member/member',
        query: this.buildEntryRouteQuery({
          search_field: 'member_id',
          search_exp: '=',
          search_value: String(row.member_id)
        }, 'member-third')
      })
    },
    goToMemberLog(row = {}) {
      if (!row.member_id) {
        return
      }
      this.$router.push({
        path: '/member/log',
        query: this.buildEntryRouteQuery({
          search_field: 'member_id',
          search_exp: '=',
          search_value: String(row.member_id)
        }, 'member-third')
      })
    },
    goToCurrentMember() {
      const row = this.currentFocusRow
      if (!row.member_id) {
        ElMessage.warning('当前没有可定位的会员')
        return
      }
      this.goToMember(row)
    },
    goToCurrentLog() {
      const row = this.currentFocusRow
      if (!row.member_id) {
        ElMessage.warning('当前没有可排查的会员日志')
        return
      }
      this.goToMemberLog(row)
    },
    goToMemberSetting() {
      this.$router.push({
        path: '/member/setting',
        query: this.buildEntryRouteQuery({
          tab: 'thirdInfo',
          setting_tab: 'thirdInfo'
        }, 'member-third')
      })
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
          this.dele(this.selection, true)
        }
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
            this.list()
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
.third-page-shell {
  padding-bottom: 18px;
}

.entry-context-banner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 14px;
  padding: 14px 16px;
  border-radius: 12px;
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

.third-page-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 14px;
}

.third-page-header__title {
  font-size: 20px;
  font-weight: 600;
  color: #1f2d3d;
  line-height: 1.3;
}

.third-page-header__desc {
  margin-top: 4px;
  font-size: 13px;
  color: #7a8499;
}

.third-page-header__env {
  flex-shrink: 0;
}

.third-summary-bar {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 10px 18px;
  padding: 10px 14px;
  margin-bottom: 16px;
  border: 1px solid #e8edf5;
  border-radius: 10px;
  background: #f8fafc;
}

.third-summary-bar__item {
  display: inline-flex;
  align-items: baseline;
  gap: 6px;
  min-height: 24px;
}

.third-summary-bar__label {
  font-size: 12px;
  color: #7a8499;
}

.third-summary-bar__value {
  font-size: 13px;
  font-weight: 600;
  color: #243247;
}

.third-filter-form :deep(.el-form-item) {
  margin-bottom: 16px;
}

.third-guide-panel {
  margin-bottom: 14px;
  padding: 16px;
  border: 1px solid #dbe7f5;
  border-radius: 16px;
  background: linear-gradient(135deg, #f7fbff 0%, #ffffff 100%);
}

.third-guide-panel__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.third-guide-panel__title {
  font-size: 16px;
  font-weight: 700;
  color: #10233f;
}

.third-guide-panel__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.third-guide-panel__badge {
  min-width: 220px;
  padding: 10px 12px;
  border-radius: 12px;
  background: rgba(37, 99, 235, 0.08);
  color: #1d4ed8;
  font-size: 12px;
  font-weight: 700;
  line-height: 1.6;
}

.third-guide-panel__grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-top: 14px;
}

.third-guide-card {
  padding: 14px 16px;
  border: 1px solid #e7edf5;
  border-radius: 14px;
  background: #fff;
}

.third-guide-card__step {
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

.third-guide-card__title {
  margin-top: 10px;
  font-size: 14px;
  font-weight: 700;
  color: #10233f;
}

.third-guide-card__desc {
  margin-top: 8px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.third-followup-strip {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 12px;
  margin-bottom: 16px;
}

.third-followup-card {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 6px;
  min-height: 108px;
  padding: 14px 16px;
  text-align: left;
  border: 1px solid #e8edf5;
  border-radius: 12px;
  background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
}

.third-followup-card__title {
  font-size: 13px;
  font-weight: 700;
  color: #243247;
}

.third-followup-card__desc {
  font-size: 12px;
  line-height: 1.7;
  color: #7a8499;
}

.third-page-main {
  padding-top: 16px;
}

.dialog-footer-actions {
  display: inline-flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-right: auto;
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

@media (max-width: 768px) {
  .entry-context-banner,
  .third-page-header,
  .third-guide-panel__header {
    flex-direction: column;
    align-items: flex-start;
  }

  .third-summary-bar {
    padding: 10px 12px;
  }

  .third-guide-panel__badge {
    min-width: 0;
  }

  .third-guide-panel__grid,
  .third-followup-strip {
    grid-template-columns: 1fr;
  }
}
</style>
