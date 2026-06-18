<template>
  <div class="app-container">
    <div class="page-hero">
      <div>
        <div class="page-hero__title">职位管理</div>
        <div class="page-hero__desc">{{ runtimeHint }}</div>
      </div>
      <div class="page-hero__meta">
        <el-tag :type="runtimeEnvInfo.tone" effect="plain">{{ runtimeEnvInfo.label }}</el-tag>
        <span class="page-hero__meta-text">数据模式：{{ runtimeEnvInfo.dataMode }}</span>
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
    <el-card class="app-head head-pb20">
      <div class="section-title-row section-title-row--compact">
        <div>
          <div class="section-title-row__title">筛选</div>
          <div class="section-title-row__desc">按时间、状态、层级和关键字快速定位职位节点。</div>
        </div>
        <div class="section-title-row__meta">当前检索：{{ query.search_field || 'post_name' }}</div>
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
            <el-form-item label="上级：" prop="post_pid">
              <el-cascader
                v-model="query.post_pid"
                :options="trees"
                :props="props"
                class="ya-search-value"
                @change="search()"
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
                  <el-option value="post_name" label="名称" />
                  <el-option value="post_abbr" label="简称" />
                  <el-option value="post_desc" label="描述" />
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
      <div class="inline-filter-strip">
        <div class="inline-filter-strip__label">当前筛选</div>
        <div class="inline-filter-strip__tags">
          <el-tag v-for="item in activeFilterTags" :key="item" effect="plain" size="small">
            {{ item }}
          </el-tag>
          <el-tag v-if="!activeFilterTags.length" effect="plain" type="info" size="small">
            默认条件：全部职位节点
          </el-tag>
        </div>
      </div>
      <div class="plain-guide">
        <div class="plain-guide__header">
          <div>
            <div class="plain-guide__title">这页建议先这样看</div>
            <div class="plain-guide__desc">
              职位页主要在管“组织里有哪些岗位、岗位下面挂了谁、岗位是否还在用”。先看树结构和禁用状态，再看岗位下用户归属，最后才去做上级调整、成员解绑和批量禁用。
            </div>
          </div>
          <span class="plain-guide__badge">{{ postFocusLabel }}</span>
        </div>
        <div class="plain-guide__grid">
          <div v-for="item in postGuideCards" :key="item.title" class="plain-guide-card">
            <div class="plain-guide-card__title">{{ item.title }}</div>
            <div class="plain-guide-card__desc">{{ item.desc }}</div>
            <div class="plain-guide-card__action">{{ item.action }}</div>
          </div>
        </div>
      </div>
      <div class="followup-panel">
        <div class="followup-panel__header">
          <div>
            <div class="followup-panel__title">职位调整后继续去哪</div>
            <div class="followup-panel__desc">
              职位节点改完后，继续看后台账号归属、角色覆盖和日志回显，才能确认岗位链路真正生效。
            </div>
          </div>
          <div class="followup-panel__risk">{{ postFollowupRiskText }}</div>
        </div>
        <div class="followup-panel__tags">
          <span v-for="item in postFollowupTags" :key="item">{{ item }}</span>
        </div>
        <div class="followup-card-grid">
          <button
            v-for="item in postFollowupActionCards"
            :key="item.title"
            type="button"
            class="followup-card"
            @click="goToSystemPage(item.path, item.query)"
          >
            <span class="followup-card__title">{{ item.title }}</span>
            <span class="followup-card__desc">{{ item.desc }}</span>
          </button>
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
          <div class="select-review-panel__title">提交前复核</div>
          <div class="select-review-panel__tags">
            <span v-for="item in selectReviewItems" :key="item">{{ item }}</span>
          </div>
          <div class="select-review-panel__hint">{{ submitRiskHint }}</div>
        </div>
        <el-form-item v-if="selectType === 'removeu'">
          <span style="">确定要解除选中的{{ name }}的用户吗？</span>
        </el-form-item>
        <el-form-item v-else-if="selectType === 'editpid'" label="上级">
          <el-cascader
            v-model="post_pid"
            :options="trees"
            :props="props"
            class="w-full"
            placeholder="一级职位"
            clearable
            filterable
          />
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
        <el-button @click="selectCancel">取消</el-button>
        <el-button type="primary" @click="selectSubmit">提交</el-button>
      </template>
    </el-dialog>
    <el-card class="app-main">
      <div class="summary-bar">
        <div class="summary-bar__item">
          <span class="summary-bar__label">职位总量</span>
          <strong class="summary-bar__value">{{ count }}</strong>
        </div>
        <div class="summary-bar__item">
          <span class="summary-bar__label">已选</span>
          <strong class="summary-bar__value">{{ selection.length }}</strong>
        </div>
        <div class="summary-bar__item">
          <span class="summary-bar__label">当前上级筛选</span>
          <strong class="summary-bar__value">{{ currentParentFilterText }}</strong>
        </div>
        <div class="summary-bar__item">
          <span class="summary-bar__label">禁用数</span>
          <strong class="summary-bar__value">{{ disabledCount }}</strong>
        </div>
        <div class="summary-bar__item">
          <span class="summary-bar__label">叶子节点</span>
          <strong class="summary-bar__value">{{ leafCount }}</strong>
        </div>
        <div class="summary-bar__item summary-bar__item--wide">
          <span class="summary-bar__label">当前操作</span>
          <strong class="summary-bar__value summary-bar__value--text">{{ currentActionText }}</strong>
        </div>
      </div>
      <div class="section-title-row section-title-row--content">
        <div>
          <div class="section-title-row__title">职位结构列表</div>
          <div class="section-title-row__desc">
            支持树形层级维护、用户解绑、批量状态处理和确认提交流程。
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
            <span class="action-cluster__title">成员与状态</span>
            <el-button title="解除用户" @click="selectOpen('removeu')">用户</el-button>
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
        default-expand-all
        @selection-change="select"
      >
        <el-table-column type="selection" width="42" title="全选/反选" />
        <el-table-column prop="post_name" label="名称" min-width="200" show-overflow-tooltip />
        <el-table-column prop="post_abbr" label="简称" min-width="100" />
        <el-table-column prop="post_desc" label="描述" min-width="150" show-overflow-tooltip />
        <el-table-column :prop="idkey" label="ID" width="80" sortable="custom" />
        <el-table-column prop="is_disable" label="禁用" min-width="85" sortable="custom">
          <template #default="scope">
            <el-switch
              v-model="scope.row.is_disable"
              class="ml-2"
              style="--el-switch-on-color: #ff4949; --el-switch-off-color: #4073fa"
              inline-prompt
              active-text="禁用"
              inactive-text="启用"
              :active-value="1"
              :inactive-value="0"
              @change="disable([scope.row])"
            />
          </template>
        </el-table-column>
        <el-table-column prop="sort" label="排序" min-width="85" />
        <el-table-column prop="create_time" label="添加时间" width="165" sortable="custom" />
        <el-table-column prop="update_time" label="修改时间" width="165" sortable="custom" />
        <el-table-column label="操作" width="250">
          <template #default="scope">
            <el-link type="primary" class="mr-1" :underline="false" @click="userShow(scope.row)">
              用户
            </el-link>
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
            <el-link
              type="primary"
              class="mr-1"
              :underline="false"
              @click="goToSystemPage('/system/user', {
                from: 'system-post',
                post_id: scope.row[idkey],
                post_name: scope.row.post_name || ''
              })"
            >
              账号
            </el-link>
            <el-link
              type="primary"
              class="mr-1"
              :underline="false"
              @click="goToSystemPage('/system/user-log', {
                from: 'system-post',
                post_id: scope.row[idkey],
                post_name: scope.row.post_name || '',
                search_value: scope.row.post_name || scope.row.post_abbr || ''
              })"
            >
              日志
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
            <div class="dialog-plain-guide">
              <div class="dialog-plain-guide__header">
                <div>
                  <div class="dialog-plain-guide__title">职位编辑先看职责承接，再补层级资料</div>
                  <div class="dialog-plain-guide__desc">
                    先想清楚这个岗位要给谁用、挂在哪一层，再去填名称、简称和描述。岗位层级和职责边界对了，后续账号归属、角色配合和日志排查都会顺很多。
                  </div>
                </div>
                <span class="dialog-plain-guide__badge">{{ postDialogFocusLabel }}</span>
              </div>
              <div class="dialog-plain-guide__grid">
                <div
                  v-for="item in postDialogGuideCards"
                  :key="item.title"
                  class="dialog-plain-guide-card"
                >
                  <div class="dialog-plain-guide-card__title">{{ item.title }}</div>
                  <div class="dialog-plain-guide-card__desc">{{ item.desc }}</div>
                  <div class="dialog-plain-guide-card__action">{{ item.action }}</div>
                </div>
              </div>
            </div>
            <el-form-item label="上级" prop="post_pid">
              <el-cascader
                v-model="model.post_pid"
                :options="trees"
                :props="props"
                class="w-full"
                placeholder="一级职位"
                clearable
                filterable
              />
            </el-form-item>
            <el-form-item label="名称" prop="post_name">
              <el-input v-model="model.post_name" placeholder="请输入名称" clearable />
            </el-form-item>
            <el-form-item label="简称" prop="post_abbr">
              <el-input v-model="model.post_abbr" placeholder="请输入简称" clearable />
            </el-form-item>
            <el-form-item label="描述" prop="post_desc">
              <el-input
                v-model="model.post_desc"
                type="textarea"
                autosize
                placeholder="请输入描述"
              />
            </el-form-item>
            <el-form-item label="备注" prop="remark">
              <el-input v-model="model.remark" placeholder="" clearable />
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
      <!-- 职位用户 -->
      <el-dialog
        v-model="userDialog"
        :title="userDialogTitle"
        :close-on-click-modal="false"
        :close-on-press-escape="false"
        top="10vh"
        width="70%"
      >
        <!-- 职位用户操作 -->
        <el-row>
          <el-col>
            <el-button type="primary" title="解除" @click="userSelectOpen('userRemove')">
              解除
            </el-button>
            <el-input
              v-model="userQuery.search_value"
              class="ya-search-value ya-margin-left"
              placeholder="昵称"
              clearable
            />
            <el-button type="primary" @click="user()">查询</el-button>
          </el-col>
        </el-row>
        <!-- 职位用户列表 -->
        <el-table
          ref="userRef"
          v-loading="userLoad"
          :data="userData"
          :height="height - 50"
          @sort-change="userSort"
          @selection-change="userSelect"
        >
          <el-table-column type="selection" width="42" title="全选/反选" />
          <el-table-column prop="user_id" label="用户ID" min-width="100" sortable="custom" />
          <el-table-column prop="nickname" label="昵称" min-width="120" show-overflow-tooltip />
          <el-table-column prop="username" label="账号" min-width="120" show-overflow-tooltip />
          <el-table-column prop="post_names" label="职位" min-width="120" show-overflow-tooltip />
          <el-table-column prop="is_super" label="超管" width="85" sortable="custom">
            <template #default="scope">
              <el-switch
                v-model="scope.row.is_super"
                :active-value="1"
                :inactive-value="0"
                disabled
              />
            </template>
          </el-table-column>
          <el-table-column prop="is_disable" label="禁用" width="85" sortable="custom">
            <template #default="scope">
              <el-switch
                v-model="scope.row.is_disable"
                :active-value="1"
                :inactive-value="0"
                disabled
              />
            </template>
          </el-table-column>
          <el-table-column prop="remark" label="备注" width="100" show-overflow-tooltip />
          <el-table-column label="操作" width="70">
            <template #default="scope">
              <el-link
                type="primary"
                :underline="false"
                @click="userSelectOpen('userRemove', [scope.row])"
              >
                解除
              </el-link>
            </template>
          </el-table-column>
        </el-table>
        <!-- 职位用户分页 -->
        <pagination
          v-show="userCount > 0"
          v-model:total="userCount"
          v-model:page="userQuery.page"
          v-model:limit="userQuery.limit"
          @pagination="user"
        />
      </el-dialog>
      <el-dialog
        v-model="userSelectDialog"
        :title="userSelectTitle"
        :close-on-click-modal="false"
        :close-on-press-escape="false"
        top="20vh"
      >
        <el-form ref="userSelectRef" label-width="120px">
          <el-form-item v-if="userSelectType === 'userRemove'" label="标签ID">
            <span>{{ userQuery[idkey] }}</span>
          </el-form-item>
          <el-form-item :label="userName + 'ID'">
            <el-input v-model="userSelectIds" type="textarea" autosize disabled />
          </el-form-item>
        </el-form>
        <template #footer>
          <el-button @click="userSelectCancel">取消</el-button>
          <el-button type="primary" @click="userSelectSubmit">提交</el-button>
        </template>
      </el-dialog>
    </el-card>
  </div>
</template>

<script>
import screenHeight from '@/utils/screen-height'
import Pagination from '@/components/Pagination/index.vue'
import { arrayColumn } from '@/utils/index'
import { getPageLimit } from '@/utils/settings'
import { list, info, add, edit, dele, editpid, disable, user, userRemove } from '@/api/system/post'
import { shortcuts } from '@/utils/getDate.js'
import { getAdminRuntimeHint, resolveAdminRuntimeEnv } from '@/utils/runtime-env'

export default {
  name: 'SystemPost',
  components: { Pagination },
  data() {
    return {
      name: '职位',
      height: 680,
      loading: false,
      idkey: 'post_id',
      exps: [{ exp: 'like', name: '包含' }],
      query: {
        search_field: 'post_name',
        search_exp: 'like',
        search_value: '',
        date_field: 'create_time',
        post_pid: undefined,
        is_disable: undefined
      },
      data: [],
      dialog: false,
      dialogTitle: '',
      model: {
        post_id: '',
        post_pid: '',
        post_name: '',
        post_abbr: '',
        post_desc: '',
        remark: '',
        sort: 250
      },
      rules: {
        post_name: [{ required: true, message: '请输入名称', trigger: 'blur' }]
      },
      trees: [],
      props: { checkStrictly: true, value: 'post_id', label: 'post_name', emitPath: false },
      isExpandAll: false,
      selection: [],
      selectIds: '',
      selectTitle: '操作',
      selectDialog: false,
      selectType: '',
      post_pid: '',
      is_disable: 0,
      userPk: 'user_id',
      userName: '用户',
      userDialog: false,
      userDialogTitle: '',
      userLoad: false,
      userData: [],
      userCount: 0,
      userQuery: {
        page: 1,
        limit: getPageLimit(),
        search_field: 'nickname',
        search_exp: 'like',
        search_value: ''
      },
      userSelection: [],
      userSelectIds: '',
      userSelectTitle: '操作',
      userSelectDialog: false,
      userSelectType: '',
      count: '',
      shortcuts: shortcuts(),
      recentActionSummary: ''
    }
  },
  computed: {
    entrySourceLabel() {
      const source = this.$route?.query?.from
      if (source === 'system-user') return '来自后台用户'
      if (source === 'system-role') return '来自角色管理'
      if (source === 'system-user-log') return '来自用户日志'
      if (source === 'dashboard') return '来自后台首页'
      return ''
    },
    entryContextVisible() {
      return Boolean(this.entrySourceLabel)
    },
    entryContextTitle() {
      if (this.entrySourceLabel === '来自后台用户') return '当前从后台用户进入职位管理'
      if (this.entrySourceLabel === '来自角色管理') return '当前从角色管理进入职位管理'
      if (this.entrySourceLabel === '来自用户日志') return '当前从用户日志进入职位管理'
      if (this.entrySourceLabel === '来自后台首页') return '当前从后台首页进入职位管理'
      return '当前为外部入口承接视角'
    },
    entryContextDesc() {
      if (this.entrySourceLabel === '来自后台用户') {
        return '这类进入通常是为了排某个后台账号到底挂在哪个职位节点。建议先看职位层级和禁用状态，再回账号页确认归属是否已经同步。'
      }
      if (this.entrySourceLabel === '来自角色管理') {
        return '这类进入通常是为了把角色覆盖范围继续落到岗位层级。建议先确认职位结构，再回角色页核对岗位和角色是不是重复承接。'
      }
      if (this.entrySourceLabel === '来自用户日志') {
        return '这类进入通常是为了追岗位调整有没有影响账号操作。建议先锁定职位节点，再回日志页继续看异常是否已经收敛。'
      }
      return '这类进入通常是首页巡检后的继续下钻。建议先看树结构和禁用节点，再继续去后台用户或日志页确认真实影响面。'
    },
    entryContextPrimaryLabel() {
      if (this.entrySourceLabel === '来自后台用户') return '回后台用户'
      if (this.entrySourceLabel === '来自角色管理') return '回角色管理'
      if (this.entrySourceLabel === '来自用户日志') return '回用户日志'
      return '回后台首页'
    },
    runtimeEnvInfo() {
      return resolveAdminRuntimeEnv()
    },
    runtimeHint() {
      return getAdminRuntimeHint(this.runtimeEnvInfo)
    },
    flatPosts() {
      return this.flattenPostData(this.data)
    },
    currentParentFilterText() {
      if (
        this.query.post_pid === undefined ||
        this.query.post_pid === '' ||
        this.query.post_pid === 0
      ) {
        return '全部层级'
      }
      return String(this.query.post_pid)
    },
    currentActionText() {
      return this.recentActionSummary || '待处理'
    },
    disabledCount() {
      return this.flatPosts.filter((item) => Number(item.is_disable || 0) === 1).length
    },
    leafCount() {
      return this.flatPosts.filter((item) => !Array.isArray(item.children) || !item.children.length)
        .length
    },
    activeFilterTags() {
      const tags = []
      if (this.query.date_value?.length === 2) {
        tags.push(`添加时间：${this.query.date_value[0]} 至 ${this.query.date_value[1]}`)
      }
      if (this.query.is_disable !== undefined) {
        tags.push(`状态：${this.query.is_disable === 1 ? '禁用' : '启用'}`)
      }
      if (
        this.query.post_pid !== undefined &&
        this.query.post_pid !== '' &&
        this.query.post_pid !== 0
      ) {
        tags.push(`上级：${this.query.post_pid}`)
      }
      if (this.query.search_value) {
        tags.push(`检索：${this.query.search_field || 'post_name'} = ${this.query.search_value}`)
      }
      return tags
    },
    postFocusLabel() {
      if (this.selection.length) {
        return '先确认当前勾选岗位'
      }
      if (this.disabledCount > 0) {
        return '先看停用岗位'
      }
      return '先看岗位结构'
    },
    postGuideCards() {
      return [
        {
          title: '第一步：先看岗位树结构',
          desc: '先确认岗位上下级是不是对的，避免岗位本来就挂错层，后面再怎么调成员都不顺。',
          action: this.isExpandAll
            ? '当前树表已展开，适合先扫一遍岗位层级。'
            : '可以先展开树表，再核对岗位上下级。'
        },
        {
          title: '第二步：再看岗位是不是还在用',
          desc: '禁用岗位不一定能直接删，很多时候下面还挂着后台账号或仍被组织链路使用。',
          action: `当前禁用岗位 ${this.disabledCount} 个，叶子岗位 ${this.leafCount} 个。`
        },
        {
          title: '第三步：最后再做批量处理',
          desc: '只有确认结构和使用情况没问题后，再去批量改上级、解绑用户或禁用，风险会更低。',
          action:
            this.selection.length > 0
              ? `当前已选 ${this.selection.length} 个岗位，可以继续批量处理。`
              : '先勾选岗位，再继续做批量维护。'
        }
      ]
    },
    selectionPreview() {
      if (!this.selection.length) {
        return '未选择'
      }
      const ids = this.selection
        .slice(0, 5)
        .map((item) => item[this.idkey])
        .join('、')
      return `${ids}${this.selection.length > 5 ? ' 等' : ''}`
    },
    selectReviewItems() {
      const items = [
        `操作：${this.selectTitle || '批量处理'}`,
        `数量：${this.selection.length} 项`,
        `对象：${this.selectionPreview}`
      ]
      if (this.selectType === 'editpid') {
        items.push(`目标上级：${this.post_pid || '顶级职位'}`)
      } else if (this.selectType === 'disable') {
        items.push(`状态调整：${this.is_disable === 1 ? '批量禁用' : '批量启用'}`)
      }
      return items
    },
    submitRiskHint() {
      if (!this.selection.length) {
        return this.runtimeEnvInfo.isProd
          ? '正式环境下职位结构调整会直接影响后台用户归属和岗位展示，请先勾选目标节点。'
          : '当前环境建议先选择样本职位，再验证树结构和成员解绑流程。'
      }
      if (this.selectType === 'removeu') {
        return '用户解绑会直接影响后台账号的职位归属，请提交前确认组织关系变更已通知到位。'
      }
      if (this.selectType === 'dele') {
        return '删除职位属于高风险动作，提交前请确认该节点没有被后台用户、流程审批或权限策略依赖。'
      }
      return this.runtimeEnvInfo.isProd
        ? `正式环境下本次会直接影响 ${this.selection.length} 项职位结构，请先复核上级、状态和成员归属。`
        : `当前环境可用于验证 ${this.selection.length} 项职位节点的结构调整和结果回显。`
    },
    postFollowupTags() {
      return [
        `已选职位：${this.selection.length} 项`,
        `禁用节点：${this.disabledCount} 项`,
        `叶子节点：${this.leafCount} 项`,
        `筛选标签：${this.activeFilterTags.length} 项`
      ]
    },
    postFollowupRiskText() {
      if (this.selection.length) {
        return '职位节点的上级、禁用和成员解绑都会影响后台岗位归属，批量提交前请先确认目标范围。'
      }
      if (this.disabledCount > 0) {
        return '当前有禁用职位，建议同步核对这些岗位下是否仍挂着后台账号或角色职责。'
      }
      return '职位页通常需要和后台用户、角色页一起看，才能判断岗位调整是否真正影响了账号权限和操作链路。'
    },
    postDialogFocusLabel() {
      if (!this.model[this.idkey]) {
        return this.model.post_pid ? '先看子岗位挂载位置' : '先补顶级岗位'
      }
      if (Number(this.model.is_disable || 0) === 1) {
        return '当前岗位已禁用'
      }
      return '优先核岗位层级和职责'
    },
    postDialogGuideCards() {
      return [
        {
          title: '第一步：先看岗位挂载层级',
          desc:
            '岗位上下级一旦挂错，后台用户归属和组织职责就会跟着偏。先确认层级，再去补名称和描述。',
          action: `当前上级：${this.model.post_pid || '顶级岗位'}`
        },
        {
          title: '第二步：再补运营能读懂的岗位信息',
          desc:
            '名称、简称和描述决定运营能不能快速判断这个岗位到底负责什么，不建议只写一个简称就结束。',
          action: `名称：${this.model.post_name || '未填写'}；简称：${this.model.post_abbr || '未填写'}`
        },
        {
          title: '第三步：保存后回账号和角色侧确认',
          desc:
            '岗位结构调整完成后，最好继续去后台用户页和角色页看真实承接，否则容易出现岗位改了但账号职责没跟上的情况。',
          action:
            this.model[this.idkey]
              ? `当前修改岗位 ID：${this.model[this.idkey]}`
              : '新增后建议立刻去后台用户页确认账号挂载'
        }
      ]
    }
  },
  created() {
    this.height = screenHeight()
    this.applyRouteQuery()
    this.list()
  },
  watch: {
    '$route.fullPath'(nextPath, prevPath) {
      if (nextPath === prevPath) {
        return
      }
      this.applyRouteQuery()
      this.list()
    }
  },
  methods: {
    parseRouteNumber(value) {
      if (value === undefined || value === null || value === '') {
        return undefined
      }
      const parsed = Number(value)
      return Number.isNaN(parsed) ? undefined : parsed
    },
    applyRouteQuery() {
      const defaultQuery = this.$options.data().query
      const routeQuery = this.$route?.query || {}
      const nextQuery = {
        ...defaultQuery
      }

      if (routeQuery.search_field) {
        nextQuery.search_field = String(routeQuery.search_field)
      }
      if (routeQuery.search_exp) {
        nextQuery.search_exp = String(routeQuery.search_exp)
      }
      if (routeQuery.search_value !== undefined) {
        nextQuery.search_value = String(routeQuery.search_value || '')
      }

      const postPid = this.parseRouteNumber(routeQuery.post_pid)
      if (postPid !== undefined) {
        nextQuery.post_pid = postPid
      }
      const isDisable = this.parseRouteNumber(routeQuery.is_disable)
      if (isDisable !== undefined) {
        nextQuery.is_disable = isDisable
      }

      const postId = this.parseRouteNumber(routeQuery.post_id)
      if (postId !== undefined) {
        nextQuery.search_field = this.idkey
        nextQuery.search_exp = '='
        nextQuery.search_value = String(postId)
      } else if (routeQuery.post_ids && !nextQuery.search_value) {
        nextQuery.search_field = this.idkey
        nextQuery.search_exp = 'in'
        nextQuery.search_value = String(routeQuery.post_ids)
      }

      if (!nextQuery.search_value && routeQuery.post_name) {
        nextQuery.search_value = String(routeQuery.post_name)
      }

      this.query = nextQuery
    },
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
      if (this.entrySourceLabel === '来自后台用户') {
        this.goToSystemPage('/system/user', this.buildEntryRouteQuery({}, 'system-post'))
        return
      }
      if (this.entrySourceLabel === '来自角色管理') {
        this.goToSystemPage('/system/role', this.buildEntryRouteQuery({}, 'system-post'))
        return
      }
      if (this.entrySourceLabel === '来自用户日志') {
        this.goToSystemPage('/system/user-log', this.buildEntryRouteQuery({}, 'system-post'))
        return
      }
      this.goToSystemPage('/dashboard', this.buildEntryRouteQuery({}, 'system-post'))
    },
    goToEntryContextBack() {
      this.handleEntryContextPrimary()
    },
    flattenPostData(tree = []) {
      return tree.reduce((acc, item) => {
        acc.push(item)
        if (Array.isArray(item.children) && item.children.length) {
          acc.push(...this.flattenPostData(item.children))
        }
        return acc
      }, [])
    },
    setRecentActionSummary(action, extra = '') {
      this.recentActionSummary = `已执行${action}，影响 ${this.selection.length || 0} 个职位节点${
        extra ? `，${extra}` : ''
      }。`
    },
    goToSystemPage(path, query = {}) {
      this.$router.push({
        path,
        query
      })
    },
    postFollowupActionCards() {
      const baseQuery = {
        from: 'system-post',
        source_count: this.count || 0
      }
      if (this.selection.length) {
        return [
          {
            title: '去后台用户页看岗位归属',
            desc: '职位改完后，先看后台用户页最容易确认账号是否已经挂到正确岗位。',
            path: '/system/user',
            query: {
              ...baseQuery,
              post_ids: this.selectIds
            }
          },
          {
            title: '去角色页交叉核职责',
            desc: '岗位调整常常伴随权限职责变化，继续到角色页复核更稳。',
            path: '/system/role',
            query: {
              ...baseQuery,
              post_ids: this.selectIds
            }
          },
          {
            title: '去操作日志排查',
            desc: '如果岗位改了但账号没有跟着变化，日志页最适合继续追踪。',
            path: '/system/user-log',
            query: {
              ...baseQuery,
              post_ids: this.selectIds
            }
          }
        ]
      }
      return [
        {
          title: '去后台用户页巡检岗位',
          desc: '先看后台用户页，确认职位节点是否已经被真实账号使用。',
          path: '/system/user',
          query: baseQuery
        },
        {
          title: '去角色页核岗位职责',
          desc: '岗位和角色职责通常一起看，避免岗位改了权限没同步。',
          path: '/system/role',
          query: baseQuery
        },
        {
          title: '去操作日志看回显',
          desc: '岗位调整后的异常最适合在日志页继续确认。',
          path: '/system/user-log',
          query: baseQuery
        }
      ]
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
          this.loading = false
          this.recentActionSummary = `已加载职位节点 ${res.data.count || 0} 项，禁用职位 ${
            this.flattenPostData(res.data.list || []).filter(
              (item) => Number(item.is_disable || 0) === 1
            ).length
          } 项。`
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
        this.model.post_pid = row[this.idkey]
        this.recentActionSummary = `准备在职位 ${row[this.idkey]} 下添加子节点。`
      } else {
        this.recentActionSummary = '准备新增顶级职位节点。'
      }
    },
    edit(row) {
      this.dialog = true
      this.dialogTitle = this.name + '修改：' + row[this.idkey]
      this.recentActionSummary = `准备修改职位 ${row[this.idkey]}。`
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
                this.recentActionSummary = `已修改职位 ${this.model[this.idkey]}，名称：${
                  this.model.post_name || '未命名'
                }。`
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
                this.recentActionSummary = `已新增职位节点，名称：${
                  this.model.post_name || '未命名'
                }。`
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
      this.recentActionSummary = `已按 ${this.query.search_field || 'post_name'} 发起职位检索。`
      this.list()
    },
    // 刷新
    refresh() {
      this.query = this.$options.data().query
      this.$refs['table'].clearSort()
      this.recentActionSummary = '已重置职位筛选条件。'
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
        this.recentActionSummary = `已勾选 ${selection.length} 个职位节点，待处理 ID：${this.selectIds}。`
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
        if (selectType === 'removeu') {
          this.selectTitle = this.name + '解除用户'
        } else if (selectType === 'editpid') {
          this.selectTitle = this.name + '修改上级'
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
        if (selectType === 'removeu') {
          this.removeu(this.selection)
        } else if (selectType === 'editpid') {
          this.editpid(this.selection)
        } else if (selectType === 'disable') {
          this.disable(this.selection, true)
        } else if (selectType === 'dele') {
          this.dele(this.selection)
        }
        this.selectDialog = false
      }
    },
    // 解除用户
    removeu(row) {
      if (!row.length) {
        this.selectAlert()
      } else {
        this.loading = true
        userRemove({
          post_id: this.selectGetIds(row),
          user_ids: []
        })
          .then((res) => {
            this.list()
            this.setRecentActionSummary('批量解除职位成员')
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.loading = false
          })
      }
    },
    // 修改上级
    editpid(row) {
      editpid({
        ids: this.selectGetIds(row),
        post_pid: this.post_pid
      })
        .then((res) => {
          this.list()
          this.selectDialog = false
          this.setRecentActionSummary(
            '批量修改职位上级',
            `目标上级：${this.post_pid || '顶级职位'}`
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
        disable({
          ids: this.selectGetIds(row),
          is_disable: is_disable
        })
          .then((res) => {
            this.list()
            this.setRecentActionSummary(
              '批量调整职位状态',
              `目标状态：${is_disable === 1 ? '禁用' : '启用'}`
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
            this.setRecentActionSummary('批量删除职位节点')
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.loading = false
          })
      }
    },
    // 职位用户显示
    userShow(row) {
      this.userDialog = true
      this.userDialogTitle = this.name + '用户：' + row.post_name
      this.recentActionSummary = `正在查看职位 ${row.post_name || row[this.idkey]} 下的成员列表。`
      this.userQuery.post_id = row.post_id
      this.userQuery.search_value = ''
      this.user()
    },
    // 职位用户列表
    user() {
      this.userLoad = true
      user(this.userQuery)
        .then((res) => {
          this.userData = res.data.list
          this.userCount = res.data.count
          this.userLoad = false
        })
        .catch(() => {
          this.userLoad = false
        })
    },
    // 职位用户排序
    userSort(sort) {
      this.userQuery.sort_field = sort.prop
      this.userQuery.sort_value = ''
      if (sort.order === 'ascending') {
        this.userQuery.sort_value = 'asc'
        this.user()
      }
      if (sort.order === 'descending') {
        this.userQuery.sort_value = 'desc'
        this.user()
      }
    },
    // 职位用户操作
    userSelect(selection) {
      this.userSelection = selection
      this.userSelectIds = this.userSelectGetIds(selection).toString()
    },
    userSelectGetIds(selection) {
      return arrayColumn(selection, this.userPk)
    },
    userSelectAlert() {
      ElMessageBox.alert('请选择需要操作的' + this.userName, '提示', {
        type: 'warning',
        callback: () => {}
      })
    },
    userSelectOpen(selectType, selectRow = '') {
      if (selectRow) {
        this.$refs['userRef'].clearSelection()
        this.$refs['userRef'].toggleRowSelection(selectRow)
      }
      if (!this.userSelection.length) {
        this.userSelectAlert()
      } else {
        this.userSelectTitle = '操作'
        if (selectType === 'userRemove') {
          this.userSelectTitle = '解除用户'
        }
        this.userSelectDialog = true
        this.userSelectType = selectType
      }
    },
    userSelectCancel() {
      this.userSelectDialog = false
    },
    userSelectSubmit() {
      if (!this.userSelection.length) {
        this.userSelectAlert()
      } else {
        const selectType = this.userSelectType
        if (selectType === 'userRemove') {
          this.userRemove(this.userSelection)
        }
        this.userSelectDialog = false
      }
    },
    // 职位用户解除
    userRemove(row) {
      if (!row.length) {
        this.userSelectAlert()
      } else {
        this.userLoad = true
        userRemove({
          post_id: this.userQuery.post_id,
          user_ids: this.userSelectGetIds(row)
        })
          .then((res) => {
            this.user()
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.userLoad = false
          })
      }
    }
  }
}
</script>

<style scoped>
.entry-context-banner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 14px;
  padding: 14px 16px;
  border: 1px solid #edf2f7;
  border-radius: 12px;
  background: linear-gradient(135deg, #f5f7ff 0%, #fffaf0 100%);
  box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
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

.page-hero,
.summary-bar,
.inline-filter-strip,
.select-review-panel {
  margin-bottom: 14px;
  padding: 14px 16px;
  border: 1px solid #edf2f7;
  border-radius: 12px;
  background: #ffffff;
  box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
}

.plain-guide {
  margin-bottom: 14px;
  padding: 14px 16px;
  border: 1px solid #edf2f7;
  border-radius: 12px;
  background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
  box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
}

.page-hero,
.section-title-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.page-hero {
  padding: 12px 16px;
}

.section-title-row--content {
  margin-bottom: 14px;
}

.section-title-row--compact {
  padding: 10px 14px;
  border-style: dashed;
  background: #fbfdff;
}

.page-hero__title,
.select-review-panel__title,
.section-title-row__title {
  font-size: 15px;
  font-weight: 600;
  color: #0f172a;
}

.section-title-row--compact .section-title-row__title {
  font-size: 13px;
  font-weight: 600;
  color: #475569;
}

.page-hero__desc,
.select-review-panel__hint,
.section-title-row__desc {
  margin-top: 4px;
  font-size: 12px;
  color: #64748b;
  line-height: 1.7;
}

.page-hero__meta {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.page-hero__meta-text {
  font-size: 12px;
  color: #64748b;
}

.section-title-row__meta {
  font-size: 12px;
  font-weight: 500;
  color: #94a3b8;
  white-space: nowrap;
}

.summary-bar {
  display: grid;
  grid-template-columns: repeat(6, minmax(0, 1fr));
  gap: 10px;
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
  white-space: nowrap;
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

.dialog-plain-guide {
  margin-bottom: 14px;
  padding: 14px 16px;
  border: 1px solid #edf2f7;
  border-radius: 12px;
  background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
  box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
}

.dialog-plain-guide__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.dialog-plain-guide__title {
  font-size: 13px;
  font-weight: 700;
  color: #111827;
}

.dialog-plain-guide__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #6b7280;
}

.dialog-plain-guide__badge {
  display: inline-flex;
  align-items: center;
  min-height: 24px;
  padding: 0 8px;
  border-radius: 999px;
  background: #eef2ff;
  color: #4338ca;
  font-size: 12px;
  white-space: nowrap;
}

.dialog-plain-guide__grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 10px;
  margin-top: 12px;
}

.dialog-plain-guide-card {
  padding: 12px;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  background: #fff;
}

.dialog-plain-guide-card__title {
  font-size: 13px;
  font-weight: 700;
  color: #111827;
}

.dialog-plain-guide-card__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #6b7280;
}

.dialog-plain-guide-card__action {
  margin-top: 8px;
  font-size: 12px;
  color: #4f46e5;
}

.summary-bar__item {
  min-width: 0;
  padding: 10px 12px;
  border-radius: 10px;
  background: #f8fafc;
  border: 1px solid #edf2f7;
}

.summary-bar__item--wide {
  grid-column: span 2;
}

.summary-bar__label {
  display: block;
  margin-bottom: 6px;
  font-size: 12px;
  color: #7c8aa5;
}

.summary-bar__value {
  display: block;
  font-size: 18px;
  font-weight: 700;
  color: #0f172a;
  line-height: 1.4;
}

.summary-bar__value--text {
  font-size: 13px;
  font-weight: 600;
  color: #334155;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.inline-filter-strip {
  margin-top: 14px;
  padding: 12px 14px;
}

.inline-filter-strip__label {
  margin-bottom: 8px;
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
}

.inline-filter-strip__tags,
.followup-panel__tags,
.select-review-panel__tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.followup-panel {
  margin-top: 14px;
  padding: 14px 16px;
  border: 1px solid #edf2f7;
  border-radius: 12px;
  background: #ffffff;
  box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
}

.followup-panel__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.followup-panel__title {
  font-size: 13px;
  font-weight: 700;
  color: #0f172a;
}

.followup-panel__desc,
.followup-panel__risk {
  margin-top: 6px;
  font-size: 12px;
  color: #64748b;
  line-height: 1.7;
}

.followup-panel__risk {
  max-width: 360px;
  padding: 10px 12px;
  border-radius: 12px;
  border: 1px solid #fed7aa;
  background: #fff7ed;
  color: #9a3412;
}

.followup-panel__tags span {
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  padding: 0 10px;
  border-radius: 999px;
  background: #eef4ff;
  color: #375078;
  font-size: 12px;
}

.followup-card-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 12px;
  margin-top: 14px;
}

.followup-card {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 8px;
  padding: 14px 16px;
  border: 1px solid #edf2f7;
  border-radius: 12px;
  background: #fff;
  text-align: left;
  cursor: pointer;
  transition: all 0.2s ease;
}

.followup-card:hover {
  border-color: #93c5fd;
  box-shadow: 0 10px 22px rgba(37, 99, 235, 0.08);
  transform: translateY(-1px);
}

.followup-card__title {
  font-size: 13px;
  font-weight: 700;
  color: #0f172a;
}

.followup-card__desc {
  font-size: 12px;
  color: #64748b;
  line-height: 1.7;
}

.select-review-panel__tags span {
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  padding: 0 10px;
  border-radius: 999px;
  background: #eef4ff;
  color: #375078;
  font-size: 12px;
}

.select-review-panel__hint {
  margin-top: 10px;
  padding: 12px 14px;
  border-radius: 12px;
  background: #fff7ed;
  border: 1px solid #fed7aa;
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
  .page-hero,
  .section-title-row,
  .plain-guide__header,
  .dialog-plain-guide__header,
  .followup-panel__header {
    flex-direction: column;
    align-items: flex-start;
  }

  .section-title-row__meta {
    white-space: normal;
  }

  .summary-bar {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .summary-bar__item--wide {
    grid-column: span 2;
  }
}

@media (max-width: 640px) {
  .summary-bar {
    grid-template-columns: 1fr;
  }

  .summary-bar__item--wide {
    grid-column: span 1;
  }

  .summary-bar__value--text {
    white-space: normal;
  }
}
</style>
