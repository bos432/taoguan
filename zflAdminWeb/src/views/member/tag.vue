<template>
  <div class="app-container">
    <el-card class="app-head">
      <div class="section-title-row">
        <div>
          <div class="section-title-row__title">会员标签管理</div>
          <div class="section-title-row__desc">
            统一处理会员标签筛选、标签维护、禁用状态和标签会员解绑。
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
          <el-button type="primary" @click="handleEntryContextPrimary">
            {{ entryContextPrimaryLabel }}
          </el-button>
          <el-button @click="goToEntryContextBack">回来源页</el-button>
        </div>
      </div>
      <div class="tag-plain-guide">
        <div class="tag-plain-guide__header">
          <div>
            <div class="tag-plain-guide__title">会员标签页第一次进来，先这样判断</div>
            <div class="tag-plain-guide__desc">
              先分清是“找一批会员打标签”，还是“清理失效标签”，再决定继续去会员列表、分组还是统计页。
            </div>
          </div>
          <div class="tag-plain-guide__badge">{{ memberTagGuideFocusLabel }}</div>
        </div>
        <div class="tag-plain-guide__grid">
          <div v-for="item in memberTagGuideCards" :key="item.title" class="tag-plain-guide-card">
            <span class="tag-plain-guide-card__step">{{ item.step }}</span>
            <div class="tag-plain-guide-card__title">{{ item.title }}</div>
            <div class="tag-plain-guide-card__desc">{{ item.desc }}</div>
          </div>
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
                  <el-option value="tag_name" label="名称" />
                  <el-option value="tag_desc" label="描述" />
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
      <div class="member-tag-summary-bar">
        <div class="member-tag-summary-bar__chips">
          <span class="summary-chip summary-chip--primary">总数 {{ count }}</span>
          <span class="summary-chip">已选 {{ selection.length }} 项</span>
          <span class="summary-chip">{{ currentStatusLabel }}</span>
          <span v-for="item in activeFilterTags" :key="item" class="summary-chip">{{ item }}</span>
          <span v-if="!activeFilterTags.length" class="summary-chip">默认条件：全部会员标签</span>
          <span v-if="recentActionSummary" class="summary-chip summary-chip--muted">{{
            recentActionSummary
          }}</span>
        </div>
        <div class="member-tag-summary-bar__hint" :class="tagFollowupBadgeClass">
          <span class="member-tag-summary-bar__hint-title">{{ tagFollowupBadgeText }}</span>
          <span class="member-tag-summary-bar__hint-text">{{ tagFollowupHint }}</span>
        </div>
      </div>
      <div class="followup-panel">
        <div class="followup-panel__main">
          <div class="followup-panel__title">看完标签后继续去哪</div>
          <div class="followup-panel__desc">{{ tagFollowupPanelHint }}</div>
          <div class="followup-panel__tags">
            <span v-for="item in tagFollowupPanelTags" :key="item">{{ item }}</span>
          </div>
          <div class="followup-panel__risk">{{ tagFollowupRiskText }}</div>
        </div>
        <div class="followup-panel__actions">
          <el-button type="primary" @click="goToPage('/member/member')">去会员列表</el-button>
          <el-button @click="goToPage('/member/group')">去会员分组</el-button>
          <el-button @click="goToPage('/member/statistic')">去会员统计</el-button>
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
        <div v-if="selection.length" class="select-review-panel">
          <div class="select-review-panel__tags">
            <span>已选 {{ selection.length }} 项</span>
            <span>目标：{{ selectionPreview }}</span>
          </div>
          <div class="select-review-panel__hint">{{ tagFollowupRiskText }}</div>
        </div>
        <el-form-item v-if="selectType === 'removem'">
          <el-text size="default">确定要解除选中的{{ name }}的会员吗？</el-text>
        </el-form-item>
        <el-form-item v-else-if="selectType === 'disable'" label="是否禁用">
          <el-switch v-model="is_disable" :active-value="1" :inactive-value="0" />
        </el-form-item>
        <el-form-item v-else-if="selectType === 'dele'">
          <el-text size="default" type="danger">确定要删除选中的{{ name }}吗？</el-text>
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
    <el-card class="app-main">
      <div class="section-title-row section-title-row--content">
        <div>
          <div class="section-title-row__title">会员标签列表</div>
          <div class="section-title-row__desc">支持标签维护、批量禁用和标签会员解绑处理。</div>
        </div>
        <div class="section-title-row__meta">已选 {{ selection.length }} 项</div>
      </div>
      <div class="operation_btn mb5">
        <!-- 操作 -->
        <div>
          <div class="action-cluster">
            <span class="action-cluster__title">内容维护</span>
            <el-button type="primary" @click="add()">添加</el-button>
            <el-button title="是否禁用" @click="selectOpen('disable')">禁用</el-button>
          </div>
          <div class="action-cluster">
            <span class="action-cluster__title">会员处理</span>
            <el-button title="解除会员" @click="selectOpen('removem')">解除会员</el-button>
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
        <el-table-column prop="tag_name" label="名称" min-width="160" show-overflow-tooltip />
        <el-table-column prop="tag_desc" label="描述" min-width="220" show-overflow-tooltip />
        <el-table-column prop="remark" label="备注" min-width="150" show-overflow-tooltip />
        <el-table-column prop="is_disable" label="禁用" min-width="85" sortable="custom">
          <template #default="scope">
            <el-switch
              v-model="scope.row.is_disable"
              :active-value="1"
              :inactive-value="0"
              @change="onRowDisableChange(scope.row)"
            />
          </template>
        </el-table-column>
        <el-table-column prop="sort" label="排序" min-width="85" sortable="custom" />
        <el-table-column prop="create_time" label="添加时间" width="165" sortable="custom" />
        <el-table-column prop="update_time" label="修改时间" width="165" sortable="custom" />
        <el-table-column label="操作" width="130">
          <template #default="scope">
            <el-link type="primary" class="mr-1" :underline="false" @click="memberShow(scope.row)">
              会员
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
          <el-form-item label="名称" prop="tag_name">
            <el-input v-model="model.tag_name" placeholder="请输入名称" clearable />
          </el-form-item>
          <el-form-item label="描述" prop="tag_desc">
            <el-input v-model="model.tag_desc" type="textarea" autosize placeholder="请输入描述" />
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
        </el-form>
      </el-scrollbar>
      <template #footer>
        <el-button :loading="loading" @click="cancel">取消</el-button>
        <el-button :loading="loading" type="primary" @click="submit">提交</el-button>
      </template>
    </el-dialog>
    <!-- 标签会员 -->
    <el-dialog
      v-model="memberDialog"
      :title="memberDialogTitle"
      :close-on-click-modal="false"
      :close-on-press-escape="false"
      top="5vh"
      width="70%"
    >
      <!-- 标签会员操作 -->
      <el-row>
        <el-col>
          <el-button type="primary" @click="memberSelectOpen('memberRemove')">解除</el-button>
          <el-input
            v-model="memberQuery.search_value"
            class="ya-search-value"
            placeholder="昵称"
            clearable
          />
          <el-button type="primary" @click="member()">查询</el-button>
        </el-col>
      </el-row>
      <!-- 标签会员列表 -->
      <el-table
        ref="memberRef"
        v-loading="memberLoad"
        :data="memberData"
        @sort-change="memberSort"
        @selection-change="memberSelect"
      >
        <el-table-column type="selection" width="42" title="全选/反选" />
        <el-table-column :prop="memberPk" label="会员ID" min-width="100" sortable="custom" />
        <el-table-column prop="avatar_id" label="头像" min-width="62">
          <template #default="scope">
            <FileImage :file-url="scope.row.avatar_url" avatar lazy />
          </template>
        </el-table-column>
        <el-table-column
          prop="nickname"
          label="昵称"
          min-width="150"
          sortable="custom"
          show-overflow-tooltip
        />
        <el-table-column
          prop="username"
          label="用户名"
          min-width="145"
          sortable="custom"
          show-overflow-tooltip
        />
        <el-table-column
          prop="phone"
          label="手机"
          min-width="120"
          sortable="custom"
          show-overflow-tooltip
        />
        <el-table-column
          prop="email"
          label="邮箱"
          min-width="180"
          sortable="custom"
          show-overflow-tooltip
        />
        <el-table-column prop="tag_names" label="标签" min-width="170" show-overflow-tooltip />
        <el-table-column prop="group_names" label="分组" min-width="170" show-overflow-tooltip />
        <el-table-column label="操作" min-width="70">
          <template #default="scope">
            <el-link
              type="primary"
              :underline="false"
              @click="memberSelectOpen('memberRemove', scope.row)"
            >
              解除
            </el-link>
          </template>
        </el-table-column>
      </el-table>
      <!-- 标签会员分页 -->
      <pagination
        v-show="memberCount > 0"
        v-model:total="memberCount"
        v-model:page="memberQuery.page"
        v-model:limit="memberQuery.limit"
        @pagination="member"
      />
    </el-dialog>
    <el-dialog
      v-model="memberSelectDialog"
      :title="memberSelectTitle"
      :close-on-click-modal="false"
      :close-on-press-escape="false"
      top="20vh"
    >
      <el-form ref="memberSelectRef" label-width="120px">
        <el-form-item v-if="memberSelectType === 'memberRemove'" label="标签ID">
          <el-text size="default">{{ memberQuery[idkey] }}</el-text>
        </el-form-item>
        <el-form-item :label="memberName + 'ID'">
          <el-input v-model="memberSelectIds" type="textarea" autosize disabled />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="memberSelectCancel">取消</el-button>
        <el-button type="primary" @click="memberSelectSubmit">提交</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script>
import screenHeight from '@/utils/screen-height'
import Pagination from '@/components/Pagination/index.vue'
import { arrayColumn } from '@/utils/index'
import { getPageLimit } from '@/utils/settings'
import { resolveAdminRuntimeEnv } from '@/utils/runtime-env'
import { list, info, add, edit, dele, disable, member, memberRemove } from '@/api/member/tag'

export default {
  name: 'MemberTag',
  components: { Pagination },
  data() {
    return {
      name: '会员标签',
      height: 680,
      loading: false,
      idkey: 'tag_id',
      exps: [{ exp: 'like', name: '包含' }],
      query: {
        page: 1,
        limit: getPageLimit(),
        search_field: 'tag_name',
        search_exp: 'like',
        date_field: 'create_time',
        is_disable: undefined
      },
      data: [],
      count: 0,
      dialog: false,
      dialogTitle: '',
      model: {
        tag_id: '',
        tag_name: '',
        tag_desc: '',
        remark: '',
        sort: 250
      },
      rules: {
        tag_name: [{ required: true, message: '请输入名称', trigger: 'blur' }]
      },
      selection: [],
      selectIds: '',
      selectTitle: '操作',
      selectDialog: false,
      selectType: '',
      is_disable: 0,
      memberPk: 'member_id',
      memberName: '会员',
      memberDialog: false,
      memberDialogTitle: '',
      memberLoad: false,
      memberData: [],
      memberCount: 0,
      memberQuery: {
        page: 1,
        limit: getPageLimit(),
        search_field: 'nickname',
        search_exp: 'like',
        search_value: ''
      },
      memberSelection: [],
      memberSelectIds: '',
      memberSelectTitle: '操作',
      memberSelectDialog: false,
      memberSelectType: '',
      recentActionSummary: ''
    }
  },
  computed: {
    entrySourceLabel() {
      const source = this.$route?.query?.from
      if (source === 'member-member') return '来自会员列表'
      if (source === 'member-group') return '来自会员分组'
      if (source === 'member-statistic') return '来自会员统计'
      if (source === 'member-log') return '来自会员日志'
      if (source === 'dashboard') return '来自后台首页'
      return ''
    },
    entryContextVisible() {
      return Boolean(this.entrySourceLabel)
    },
    entryContextTitle() {
      if (this.entrySourceLabel === '来自会员列表') return '当前从会员列表进入会员标签'
      if (this.entrySourceLabel === '来自会员分组') return '当前从会员分组进入会员标签'
      if (this.entrySourceLabel === '来自会员统计') return '当前从会员统计进入会员标签'
      if (this.entrySourceLabel === '来自会员日志') return '当前从会员日志进入会员标签'
      if (this.entrySourceLabel === '来自后台首页') return '当前从后台首页进入会员标签'
      return '当前为外部入口承接视角'
    },
    entryContextDesc() {
      if (this.entrySourceLabel === '来自会员列表') {
        return '这类进入通常是为了看某批会员为什么被打上当前标签。建议先锁定目标标签，再回会员列表确认命中对象和触达范围。'
      }
      if (this.entrySourceLabel === '来自会员分组') {
        return '这类进入通常是为了把分组策略继续落到标签维度。建议先确认标签边界，再回分组页复核分组与标签是不是重复承接。'
      }
      if (this.entrySourceLabel === '来自会员统计') {
        return '这类进入通常是为了把统计波动落到具体标签。建议先看启禁状态和命中标签，再回统计页确认趋势是否能解释得通。'
      }
      if (this.entrySourceLabel === '来自会员日志') {
        return '这类进入通常是为了排查会员标签相关操作。建议先锁定标签，再回日志页继续确认是哪次解绑、禁用或删除触发了变化。'
      }
      return '这类进入通常是首页巡检后的继续下钻。建议先按状态或关键词缩小标签范围，再继续去会员列表或统计页核对真实承接。'
    },
    entryContextPrimaryLabel() {
      if (this.entrySourceLabel === '来自会员列表') return '回会员列表'
      if (this.entrySourceLabel === '来自会员分组') return '回会员分组'
      if (this.entrySourceLabel === '来自会员统计') return '回会员统计'
      if (this.entrySourceLabel === '来自会员日志') return '回会员日志'
      return '回后台首页'
    },
    runtimeEnvInfo() {
      return resolveAdminRuntimeEnv()
    },
    currentPageDisabledCount() {
      return Array.isArray(this.data)
        ? this.data.filter((item) => Number(item.is_disable) === 1).length
        : 0
    },
    currentStatusLabel() {
      if (this.query.is_disable === 1) {
        return '禁用标签'
      }
      if (this.query.is_disable === 0) {
        return '启用标签'
      }
      return '全部状态'
    },
    activeFilterTags() {
      const tags = []
      if (Array.isArray(this.query.date_value) && this.query.date_value.length === 2) {
        tags.push(`时间：${this.query.date_value[0]} 至 ${this.query.date_value[1]}`)
      }
      if (this.query.is_disable !== undefined && this.query.is_disable !== '') {
        tags.push(`状态：${this.query.is_disable === 1 ? '禁用' : '启用'}`)
      }
      if (this.query.search_value) {
        tags.push(`检索：${this.query.search_field || 'tag_name'} = ${this.query.search_value}`)
      }
      return tags
    },
    selectionPreview() {
      if (!this.selection.length) {
        return '未选择'
      }
      const ids = this.selection
        .slice(0, 4)
        .map((item) => item[this.idkey])
        .join('、')
      return `${ids}${this.selection.length > 4 ? ' 等' : ''}`
    },
    tagFollowupBadgeText() {
      if (this.selection.length > 0) {
        return '可批量处理'
      }
      if (this.currentPageDisabledCount > 0) {
        return '需复核'
      }
      return '状态平稳'
    },
    tagFollowupBadgeClass() {
      if (this.selection.length > 0) {
        return 'is-active'
      }
      if (this.currentPageDisabledCount > 0) {
        return 'is-warning'
      }
      return 'is-safe'
    },
    tagFollowupHint() {
      if (this.selection.length > 0) {
        return '当前已经选中标签，可继续做禁用、解绑会员和删除等批量处理。'
      }
      return '标签页更适合先筛选出目标标签，再继续查看会员绑定关系和禁用状态。'
    },
    tagFollowupRiskText() {
      if (this.selection.length > 0) {
        return '标签删除和会员解绑都会直接影响标签运营结果，请先确认已选对象与筛选范围一致。'
      }
      if (this.currentPageDisabledCount > 0) {
        return '当前页存在禁用标签，建议优先核对是否属于预期停用标签，避免误伤仍在使用中的标签。'
      }
      return '会员解绑、禁用和删除都属于高影响动作，建议先通过筛选缩小范围后再批量处理。'
    },
    currentFocusTag() {
      if (this.selection.length > 0) {
        return this.selection[0]
      }
      return this.data[0] || null
    },
    tagFollowupPanelHint() {
      if (this.selection.length > 0) {
        return '当前已经圈定标签，下一步更适合去会员列表看命中会员，或去分组、统计页确认这批标签的运营归属和效果。'
      }
      return '标签页适合先收敛目标标签，再继续去会员列表、分组和统计页把绑定对象、归属关系和标签效果串起来看。'
    },
    tagFollowupPanelTags() {
      const focusTag = this.currentFocusTag
      return [
        `当前状态：${this.currentStatusLabel}`,
        `重点标签：${focusTag ? `${focusTag.tag_name || focusTag[this.idkey]}（ID ${focusTag[this.idkey]}）` : '未圈定'}`,
        `当前页禁用：${this.currentPageDisabledCount} 个`
      ]
    },
    memberTagGuideFocusLabel() {
      if (this.selection.length > 0) {
        return `当前重点：已圈定 ${this.selection.length} 个标签，先确认会不会误解绑会员`
      }
      if (this.currentPageDisabledCount > 0) {
        return '当前重点：先核对禁用标签是不是历史停用，不要误伤仍在运营的人群标签'
      }
      if (this.query.search_value) {
        return `当前重点：先确认关键词命中的标签，再继续看会员绑定范围`
      }
      return '当前重点：先按状态或关键词缩小标签范围，再继续去会员列表看真实命中人群'
    },
    memberTagGuideCards() {
      return [
        {
          step: '第一步',
          title: '先判断你是在找人群，还是在清理标签',
          desc: '如果目的是圈会员，先按名称、备注和状态把目标标签找出来；如果是清理历史标签，优先看禁用和空标签。'
        },
        {
          step: '第二步',
          title: '再决定是改标签，还是动会员关系',
          desc: '标签名称、描述、排序属于标签本身；解除会员、批量禁用和删除会影响真实会员归属，动作要分开看。'
        },
        {
          step: '第三步',
          title: '改完后回会员列表和统计页复核',
          desc: '标签页只负责人群标记，最终还是要回会员列表确认命中对象，再去统计页看运营结果有没有跟着变化。'
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
    buildEntryRouteQuery(extraQuery = {}, options = {}) {
      const routeQuery = { ...(this.$route?.query || {}) }
      if (!options.preserveFrom) {
        delete routeQuery.from
      }
      return {
        ...routeQuery,
        ...extraQuery
      }
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
      const tagId = this.parseRouteNumber(routeQuery.tag_id)
      if (tagId !== undefined && !nextQuery.search_value) {
        nextQuery.search_field = this.idkey
        nextQuery.search_exp = '='
        nextQuery.search_value = String(tagId)
      }
      this.query = nextQuery
    },
    handleEntryContextPrimary() {
      if (this.entrySourceLabel === '来自会员列表') {
        this.goToMemberPage('/member/member')
        return
      }
      if (this.entrySourceLabel === '来自会员分组') {
        this.goToMemberPage('/member/group')
        return
      }
      if (this.entrySourceLabel === '来自会员统计') {
        this.goToMemberPage('/member/statistic')
        return
      }
      if (this.entrySourceLabel === '来自会员日志') {
        this.goToMemberPage('/member/log')
        return
      }
      this.goToMemberPage('/dashboard')
    },
    goToEntryContextBack() {
      this.handleEntryContextPrimary()
    },
    goToMemberPage(path, query = {}) {
      this.$router.push({
        path,
        query: this.buildEntryRouteQuery(query)
      })
    },
    setRecentActionSummary(summary) {
      this.recentActionSummary = summary
    },
    // 列表
    list() {
      this.loading = true
      list(this.query)
        .then((res) => {
          this.data = res.data.list
          this.count = res.data.count
          this.exps = res.data.exps
          this.setRecentActionSummary(`标签列表已刷新，共 ${res.data.count || 0} 项。`)
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
      if (selection.length) {
        this.setRecentActionSummary(`已选 ${selection.length} 个标签，待处理 ID：${this.selectIds}。`)
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
        if (selectType === 'removem') {
          this.selectTitle = this.name + '解除会员'
        } else if (selectType === 'disable') {
          this.selectTitle = this.name + '是否禁用'
        } else if (selectType === 'dele') {
          this.selectTitle = this.name + '删除'
        }
        this.selectDialog = true
        this.selectType = selectType
      }
    },
    onRowDisableChange(row) {
      const isDisable = Number(row.is_disable) === 1
      const nextLabel = isDisable ? '禁用' : '启用'
      ElMessageBox.confirm(`确定要${nextLabel}标签【${row.tag_name || row[this.idkey]}】吗？`, '提示', {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning'
      })
        .then(() => {
          this.disable([row])
        })
        .catch(() => {
          row.is_disable = isDisable ? 0 : 1
        })
    },
    selectCancel() {
      this.selectDialog = false
    },
    selectSubmit() {
      if (!this.selection.length) {
        this.selectAlert()
      } else {
        const selectType = this.selectType
        if (selectType === 'removem') {
          this.removem(this.selection)
        } else if (selectType === 'disable') {
          this.disable(this.selection, true)
        } else if (selectType === 'dele') {
          this.dele(this.selection, true)
        }
        this.selectDialog = false
      }
    },
    // 解除会员
    removem(row) {
      if (!row.length) {
        this.selectAlert()
      } else {
        this.loading = true
        memberRemove({
          tag_id: this.selectGetIds(row),
          member_ids: []
        })
          .then((res) => {
            this.list()
            this.setRecentActionSummary(`标签会员解绑已提交，共 ${row.length} 项。`)
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
            this.setRecentActionSummary(
              `${is_disable === 1 ? '禁用' : '启用'}标签已提交，共 ${row.length} 项。`
            )
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
            this.setRecentActionSummary(`标签删除已提交，共 ${row.length} 项。`)
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.loading = false
          })
      }
    },
    // 标签会员显示
    memberShow(row) {
      this.memberDialog = true
      this.memberDialogTitle = '标签会员：' + row.tag_name
      this.memberQuery.tag_id = row.tag_id
      this.memberQuery.search_value = ''
      this.member()
    },
    // 标签会员列表
    member() {
      this.memberLoad = true
      member(this.memberQuery)
        .then((res) => {
          this.memberData = res.data.list
          this.memberCount = res.data.count
          this.memberLoad = false
        })
        .catch(() => {
          this.memberLoad = false
        })
    },
    // 标签会员排序
    memberSort(sort) {
      this.memberQuery.sort_field = sort.prop
      this.memberQuery.sort_value = ''
      if (sort.order === 'ascending') {
        this.memberQuery.sort_value = 'asc'
        this.member()
      }
      if (sort.order === 'descending') {
        this.memberQuery.sort_value = 'desc'
        this.member()
      }
    },
    // 标签会员操作
    memberSelect(selection) {
      this.memberSelection = selection
      this.memberSelectIds = this.memberSelectGetIds(selection).toString()
    },
    memberSelectGetIds(selection) {
      return arrayColumn(selection, this.memberPk)
    },
    memberSelectAlert() {
      ElMessageBox.alert('请选择需要操作的' + this.memberName, '提示', {
        type: 'warning',
        callback: () => {}
      })
    },
    memberSelectOpen(selectType, selectRow = '') {
      if (selectRow) {
        this.$refs['memberRef'].clearSelection()
        this.$refs['memberRef'].toggleRowSelection(selectRow)
      }
      if (!this.memberSelection.length) {
        this.memberSelectAlert()
      } else {
        this.memberSelectTitle = '操作'
        if (selectType === 'memberRemove') {
          this.memberSelectTitle = '解除标签'
        }
        this.memberSelectDialog = true
        this.memberSelectType = selectType
      }
    },
    memberSelectCancel() {
      this.memberSelectDialog = false
    },
    memberSelectSubmit() {
      if (!this.memberSelection.length) {
        this.memberSelectAlert()
      } else {
        const selectType = this.memberSelectType
        if (selectType === 'memberRemove') {
          this.memberRemove(this.memberSelection)
        }
        this.memberSelectDialog = false
      }
    },
    // 标签会员解除
    memberRemove(row) {
      if (!row.length) {
        this.memberSelectAlert()
      } else {
        this.memberLoad = true
        memberRemove({
          tag_id: this.memberQuery.tag_id,
          member_ids: this.memberSelectGetIds(row)
        })
          .then((res) => {
            this.member()
            this.setRecentActionSummary(`标签会员解绑已提交，共 ${row.length} 项。`)
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.memberLoad = false
          })
      }
    },
    goToPage(path) {
      const focusTag = this.currentFocusTag || {}
      this.$router.push({
        path,
        query: this.buildEntryRouteQuery({
          from: 'member-tag',
          tag_id: focusTag[this.idkey] ? String(focusTag[this.idkey]) : undefined,
          tag_name: focusTag.tag_name || undefined
        })
      })
    }
  }
}
</script>

<style scoped>
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

.entry-context-banner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin: 0 0 16px;
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

.member-tag-summary-bar {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  margin-top: 16px;
  padding: 14px 16px;
  border: 1px solid #dbe7f5;
  border-radius: 14px;
  background: #f8fbff;
}

.member-tag-summary-bar__chips {
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
  border: 1px solid #dbe7f5;
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

.summary-chip--warning {
  color: #b45309;
  background: #fff5e8;
  border-color: #f7d8a8;
}

.member-tag-summary-bar__hint {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 240px;
  padding: 12px 14px;
  border-radius: 12px;
  background: #eef4ff;
  color: #1d4ed8;
}

.member-tag-summary-bar__hint.is-safe {
  background: #eaf8ef;
  color: #15803d;
}

.member-tag-summary-bar__hint.is-warning {
  background: #fff5e8;
  color: #b45309;
}

.member-tag-summary-bar__hint.is-active {
  background: #e8f0ff;
  color: #1d4ed8;
}

.member-tag-summary-bar__hint-title {
  font-size: 12px;
  font-weight: 700;
}

.member-tag-summary-bar__hint-text {
  font-size: 12px;
  line-height: 1.7;
}

.tag-plain-guide {
  margin-bottom: 16px;
  padding: 16px;
  border: 1px solid #dbe7f5;
  border-radius: 16px;
  background: linear-gradient(135deg, #f7fbff 0%, #ffffff 100%);
}

.tag-plain-guide__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.tag-plain-guide__title {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
}

.tag-plain-guide__desc {
  margin-top: 6px;
  font-size: 12px;
  color: #64748b;
  line-height: 1.7;
}

.tag-plain-guide__badge {
  min-width: 220px;
  padding: 10px 12px;
  border-radius: 12px;
  background: rgba(37, 99, 235, 0.08);
  color: #1d4ed8;
  font-size: 12px;
  font-weight: 700;
  line-height: 1.6;
}

.tag-plain-guide__grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-top: 14px;
}

.tag-plain-guide-card {
  padding: 14px 16px;
  border: 1px solid #e7edf5;
  border-radius: 14px;
  background: #fff;
}

.tag-plain-guide-card__step {
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

.tag-plain-guide-card__title {
  margin-top: 10px;
  font-size: 14px;
  font-weight: 700;
  color: #0f172a;
}

.tag-plain-guide-card__desc {
  margin-top: 8px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.followup-panel {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-top: 16px;
  padding: 16px;
  border: 1px solid #dbe7f5;
  border-radius: 14px;
  background: linear-gradient(135deg, #ffffff 0%, #f7fbff 100%);
}

.followup-panel__main {
  flex: 1;
  min-width: 0;
}

.followup-panel__title {
  font-size: 14px;
  font-weight: 700;
  color: #0f172a;
}

.followup-panel__desc,
.followup-panel__risk {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
}

.followup-panel__desc {
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
  font-size: 12px;
  color: #334155;
  background: #fff;
  border: 1px solid #dbe7f5;
  border-radius: 999px;
}

.followup-panel__risk {
  margin-top: 10px;
  padding: 10px 12px;
  color: #9a3412;
  background: #fff7ed;
  border: 1px solid #fed7aa;
  border-radius: 12px;
}

.followup-panel__actions {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 8px;
  min-width: 240px;
}

.select-review-panel {
  margin-bottom: 16px;
  padding: 14px 16px;
  background: #f8fbff;
  border: 1px solid #dbe7f5;
  border-radius: 14px;
}

.select-review-panel__tags {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.select-review-panel__tags span {
  display: inline-flex;
  align-items: center;
  min-height: 30px;
  padding: 0 10px;
  font-size: 12px;
  color: #334155;
  background: #fff;
  border: 1px solid #dbe7f5;
  border-radius: 999px;
}

.select-review-panel__hint {
  margin-top: 10px;
  font-size: 12px;
  line-height: 1.7;
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
  .entry-context-banner,
  .section-title-row,
  .tag-plain-guide__header,
  .member-tag-summary-bar,
  .followup-panel {
    flex-direction: column;
  }

  .section-title-row__meta {
    white-space: normal;
  }

  .followup-panel__actions {
    min-width: 0;
    justify-content: flex-start;
  }

  .tag-plain-guide__badge {
    min-width: 0;
  }

  .tag-plain-guide__grid {
    grid-template-columns: 1fr;
  }
}
</style>
