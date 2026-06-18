<template>
  <div class="app-container">
    <el-card class="app-head head-pb20">
      <div class="section-title-row">
        <div>
          <div class="section-title-row__title">部门管理</div>
          <div class="section-title-row__desc">统一处理部门筛选、树形维护、上级调整和成员解绑。</div>
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
            <el-form-item label="上级：" prop="dept_pid">
              <el-cascader
                v-model="query.dept_pid"
                :options="trees"
                :props="props"
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
                  <el-option value="dept_name" label="名称" />
                  <el-option value="dept_abbr" label="简称" />
                  <el-option value="dept_desc" label="描述" />
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
      <div class="dept-summary-bar">
        <div class="dept-summary-bar__chips">
          <span class="summary-chip summary-chip--primary">总数 {{ count }}</span>
          <span class="summary-chip">已选 {{ selection.length }} 项</span>
          <span class="summary-chip">{{ isExpandAll ? '树表展开' : '树表收起' }}</span>
          <span class="summary-chip">禁用 {{ disabledCount }} 项</span>
          <span class="summary-chip">叶子 {{ leafCount }} 项</span>
          <span class="summary-chip">数据模式 {{ runtimeEnvInfo.dataMode }}</span>
          <span v-for="item in activeFilterTags" :key="item" class="summary-chip">{{ item }}</span>
          <span v-if="!activeFilterTags.length" class="summary-chip">默认条件：全部部门节点</span>
          <span v-if="recentActionSummary" class="summary-chip summary-chip--muted">{{
            recentActionSummary
          }}</span>
        </div>
        <div class="dept-summary-bar__hint" :class="deptSummaryBadgeClass">
          <span class="dept-summary-bar__hint-title">{{ deptSummaryBadgeText }}</span>
          <span class="dept-summary-bar__hint-text">{{ deptSummaryHint }}</span>
        </div>
      </div>
      <div class="plain-guide">
        <div class="plain-guide__header">
          <div>
            <div class="plain-guide__title">这页建议先这样看</div>
            <div class="plain-guide__desc">
              部门页本质上在管组织树。先看层级和禁用节点，再看叶子节点下面有没有成员，最后才做改上级、解绑成员或删除，避免只改结构不看账号承接。
            </div>
          </div>
          <span class="plain-guide__badge">{{ deptFocusLabel }}</span>
        </div>
        <div class="plain-guide__grid">
          <div v-for="item in deptGuideCards" :key="item.title" class="plain-guide-card">
            <div class="plain-guide-card__title">{{ item.title }}</div>
            <div class="plain-guide-card__desc">{{ item.desc }}</div>
            <div class="plain-guide-card__action">{{ item.action }}</div>
          </div>
        </div>
      </div>
      <div class="followup-panel">
        <div class="followup-panel__header">
          <div>
            <div class="followup-panel__title">部门调整后继续去哪</div>
            <div class="followup-panel__desc">
              把组织节点、后台账号归属、角色权限和操作日志串起来，避免只改结构不复核结果。
            </div>
          </div>
          <div class="followup-panel__risk">{{ deptFollowupRiskText }}</div>
        </div>
        <div class="followup-panel__tags">
          <span v-for="item in deptFollowupTags" :key="item">{{ item }}</span>
        </div>
        <div class="followup-card-grid">
          <button
            v-for="item in deptFollowupActionCards"
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
            v-model="dept_pid"
            :options="trees"
            :props="props"
            class="w-full"
            placeholder="一级部门"
            clearable
            filterable
          />
        </el-form-item>
        <el-form-item v-if="selectType === 'disable'" label="是否禁用">
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
          <div class="section-title-row__title">部门列表</div>
          <div class="section-title-row__desc">支持树形维护、上级调整、成员解绑和状态控制。</div>
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
        <el-table-column prop="dept_name" label="名称" min-width="200" show-overflow-tooltip />
        <el-table-column prop="dept_abbr" label="简称" min-width="100" />
        <el-table-column prop="dept_desc" label="描述" min-width="200" show-overflow-tooltip />
        <el-table-column :prop="idkey" label="ID" min-width="80" />
        <el-table-column prop="is_disable" label="禁用" min-width="85">
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
        <el-table-column prop="sort" label="排序" min-width="85" />
        <el-table-column prop="create_time" label="添加时间" width="165" />
        <el-table-column prop="update_time" label="修改时间" width="165" />
        <el-table-column label="操作" width="210">
          <template #default="scope">
            <el-link type="primary" class="mr-1" :underline="false" @click="userShow(scope.row)">
              用户
            </el-link>
            <el-link type="primary" class="mr-1" :underline="false" @click="edit(scope.row)">
              修改
            </el-link>
            <el-link
              type="primary"
              class="mr-1"
              :underline="false"
              @click="goToSystemPage('/system/user-log', {
                from: 'system-dept',
                dept_id: scope.row[idkey],
                dept_name: scope.row.dept_name || '',
                search_value: scope.row.dept_name || scope.row.dept_abbr || ''
              })"
            >
              日志
            </el-link>
            <el-link
              type="primary"
              class="mr-1"
              :underline="false"
              @click="goToSystemPage('/system/user', {
                from: 'system-dept',
                dept_id: scope.row[idkey],
                dept_name: scope.row.dept_name || ''
              })"
            >
              账号
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
        destroy-on-close
      >
        <el-scrollbar native :height="height - 50">
          <el-form ref="ref" :rules="rules" :model="model" label-width="100px">
            <div class="dialog-plain-guide">
              <div class="dialog-plain-guide__header">
                <div>
                  <div class="dialog-plain-guide__title">部门编辑先看组织承接，再补基础资料</div>
                  <div class="dialog-plain-guide__desc">
                    先确认这次是在新增顶级部门、补子部门，还是调整已有组织节点；层级和承接对象想清楚后，再填写简称、电话、地址和排序，后面复核会轻松很多。
                  </div>
                </div>
                <span class="dialog-plain-guide__badge">{{ deptDialogFocusLabel }}</span>
              </div>
              <div class="dialog-plain-guide__grid">
                <div
                  v-for="item in deptDialogGuideCards"
                  :key="item.title"
                  class="dialog-plain-guide-card"
                >
                  <div class="dialog-plain-guide-card__title">{{ item.title }}</div>
                  <div class="dialog-plain-guide-card__desc">{{ item.desc }}</div>
                  <div class="dialog-plain-guide-card__action">{{ item.action }}</div>
                </div>
              </div>
            </div>
            <el-form-item label="上级" prop="dept_pid">
              <el-cascader
                v-model="model.dept_pid"
                :options="trees"
                :props="props"
                class="w-full"
                placeholder="一级部门"
                clearable
                filterable
              />
            </el-form-item>
            <el-form-item label="名称" prop="dept_name">
              <el-input v-model="model.dept_name" placeholder="请输入部门名称" clearable />
            </el-form-item>
            <el-form-item label="简称" prop="dept_abbr">
              <el-input v-model="model.dept_abbr" placeholder="请输入部门简称" clearable />
            </el-form-item>
            <el-form-item label="描述" prop="dept_desc">
              <el-input
                v-model="model.dept_desc"
                type="textarea"
                autosize
                placeholder="请输入部门描述"
              />
            </el-form-item>
            <el-form-item label="电话" prop="dept_tel">
              <el-input v-model="model.dept_tel" placeholder="" clearable />
            </el-form-item>
            <el-form-item label="传真" prop="dept_fax">
              <el-input v-model="model.dept_fax" placeholder="" clearable />
            </el-form-item>
            <el-form-item label="邮箱" prop="dept_email">
              <el-input v-model="model.dept_email" placeholder="" clearable />
            </el-form-item>
            <el-form-item label="地址" prop="dept_addr">
              <el-input v-model="model.dept_addr" placeholder="" clearable />
            </el-form-item>
            <el-form-item label="备注" prop="remark">
              <el-input v-model="model.remark" placeholder="" clearable />
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
      <!-- 部门用户 -->
      <el-dialog
        v-model="userDialog"
        :title="userDialogTitle"
        :close-on-click-modal="false"
        :close-on-press-escape="false"
        align-center
        width="70%"
      >
        <!-- 部门用户操作 -->
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
        <!-- 部门用户列表 -->
        <el-table
          ref="userRef"
          v-loading="userLoad"
          :data="userData"
          :height="height - 50"
          @sort-change="userSort"
          @selection-change="userSelect"
        >
          <el-table-column type="selection" width="42" title="全选/反选" />
          <el-table-column :prop="userPk" label="用户ID" min-width="100" sortable="custom" />
          <el-table-column prop="nickname" label="昵称" min-width="120" show-overflow-tooltip />
          <el-table-column prop="username" label="账号" min-width="120" show-overflow-tooltip />
          <el-table-column prop="dept_names" label="部门" min-width="120" show-overflow-tooltip />
          <el-table-column prop="is_super" label="超管" min-width="85" sortable="custom">
            <template #default="scope">
              <el-switch
                v-model="scope.row.is_super"
                :active-value="1"
                :inactive-value="0"
                disabled
              />
            </template>
          </el-table-column>
          <el-table-column prop="is_disable" label="禁用" min-width="85" sortable="custom">
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
          <el-table-column label="操作" min-width="70">
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
        <!-- 部门用户分页 -->
        <pagination
          v-show="userCount > 0"
          :total="userCount"
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
          <el-form-item v-if="userSelectType === 'userRemove'" label="部门ID">
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
import { list, info, add, edit, dele, editpid, disable, user, userRemove } from '@/api/system/dept'
import { shortcuts } from '@/utils/getDate.js'
import { resolveAdminRuntimeEnv } from '@/utils/runtime-env'

export default {
  name: 'SystemDept',
  components: { Pagination },
  data() {
    return {
      name: '部门',
      height: 680,
      loading: false,
      idkey: 'dept_id',
      exps: [{ exp: 'like', name: '包含' }],
      query: {
        search_field: 'dept_name',
        search_exp: 'like',
        search_value: '',
        date_field: 'create_time',
        dept_pid: undefined,
        is_disable: undefined
      },
      data: [],
      dialog: false,
      dialogTitle: '',
      model: {
        dept_id: '',
        dept_pid: 0,
        dept_name: '',
        dept_abbr: '',
        dept_desc: '',
        dept_tel: '',
        dept_fax: '',
        dept_email: '',
        dept_addr: '',
        remark: '',
        sort: 250
      },
      rules: {
        dept_name: [{ required: true, message: '请输入部门名称', trigger: 'blur' }]
      },
      trees: [],
      props: { checkStrictly: true, value: 'dept_id', label: 'dept_name', emitPath: false },
      isExpandAll: false,
      selection: [],
      selectIds: '',
      selectTitle: '操作',
      selectDialog: false,
      selectType: '',
      dept_pid: '',
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
    runtimeEnvInfo() {
      return resolveAdminRuntimeEnv()
    },
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
      if (this.entrySourceLabel === '来自后台用户') return '当前从后台用户进入部门管理'
      if (this.entrySourceLabel === '来自角色管理') return '当前从角色管理进入部门管理'
      if (this.entrySourceLabel === '来自用户日志') return '当前从用户日志进入部门管理'
      if (this.entrySourceLabel === '来自后台首页') return '当前从后台首页进入部门管理'
      return '当前为外部入口承接视角'
    },
    entryContextDesc() {
      if (this.entrySourceLabel === '来自后台用户') {
        return '这类进入通常是为了排某个账号挂在哪个组织节点。建议先核部门层级和禁用状态，再回账号页确认真实归属。'
      }
      if (this.entrySourceLabel === '来自角色管理') {
        return '这类进入通常是为了把权限变化和组织归属一起看。建议先看组织树，再回角色页确认权限承接是否同步。'
      }
      if (this.entrySourceLabel === '来自用户日志') {
        return '这类进入通常是为了查异常账号属于哪个组织。建议先锁组织分支，再回日志页继续追异常行为。'
      }
      return '这类进入通常是首页巡检后的继续下钻。建议先看禁用节点和叶子节点，再继续到账号页或角色页复核。'
    },
    entryContextPrimaryLabel() {
      if (this.entrySourceLabel === '来自后台用户') return '回后台用户'
      if (this.entrySourceLabel === '来自角色管理') return '回角色管理'
      if (this.entrySourceLabel === '来自用户日志') return '回用户日志'
      return '回后台首页'
    },
    flatDepts() {
      return this.flattenDeptData(this.data)
    },
    disabledCount() {
      return this.flatDepts.filter((item) => Number(item.is_disable || 0) === 1).length
    },
    leafCount() {
      return this.flatDepts.filter((item) => !Array.isArray(item.children) || !item.children.length)
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
        this.query.dept_pid !== undefined &&
        this.query.dept_pid !== '' &&
        this.query.dept_pid !== 0
      ) {
        tags.push(`上级：${this.query.dept_pid}`)
      }
      if (this.query.search_value) {
        tags.push(`检索：${this.query.search_field || 'dept_name'} = ${this.query.search_value}`)
      }
      return tags
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
        items.push(`目标上级：${this.dept_pid || '顶级部门'}`)
      } else if (this.selectType === 'disable') {
        items.push(`状态调整：${this.is_disable === 1 ? '批量禁用' : '批量启用'}`)
      }
      return items
    },
    submitRiskHint() {
      if (!this.selection.length) {
        return this.runtimeEnvInfo.isProd
          ? '正式环境下部门结构调整会直接影响后台账号归属和组织展示，请先勾选目标节点。'
          : '当前环境建议先选择样本部门，再验证树结构和成员解绑流程。'
      }
      if (this.selectType === 'removeu') {
        return '用户解绑会直接影响后台账号的部门归属，请提交前确认组织关系调整已同步。'
      }
      if (this.selectType === 'dele') {
        return '删除部门属于高风险动作，提交前请确认该节点没有被后台用户、审批流程或统计报表依赖。'
      }
      return this.runtimeEnvInfo.isProd
        ? `正式环境下本次会直接影响 ${this.selection.length} 个部门节点，请先复核上级、状态和成员归属。`
        : `当前环境可用于验证 ${this.selection.length} 个部门节点的结构调整和结果回显。`
    },
    followupHint() {
      if (!this.count) {
        return '当前没有部门节点，建议先补齐基础组织结构。'
      }
      return this.runtimeEnvInfo.isProd
        ? '正式环境建议优先按禁用节点、叶子节点和成员分布做巡检，再执行结构调整。'
        : '当前环境适合先验证部门树、成员解绑和上级调整回显。'
    },
    deptFocusLabel() {
      if (this.selection.length) {
        return '先复核结构调整'
      }
      if (this.disabledCount > 0) {
        return '先看禁用节点'
      }
      if (
        this.query.dept_pid !== undefined &&
        this.query.dept_pid !== '' &&
        this.query.dept_pid !== 0
      ) {
        return '先看当前组织分支'
      }
      return '先看组织层级'
    },
    deptGuideCards() {
      return [
        {
          title: '第一步先看层级关系有没有挂错',
          desc:
            this.query.dept_pid !== undefined &&
            this.query.dept_pid !== '' &&
            this.query.dept_pid !== 0
              ? `当前已经聚焦到上级 ${this.query.dept_pid}，先确认这条组织分支下的层级是不是合理。`
              : '部门页最怕的是上级挂错。层级一错，后台账号归属、审批链和统计口径都会一起偏掉。',
          action: '优先核对父子层级，再去改状态、解绑成员或删除。'
        },
        {
          title: '第二步再看禁用节点和叶子节点',
          desc:
            this.disabledCount > 0
              ? `当前有 ${this.disabledCount} 个禁用节点，顺手看看这些节点下是否还留着成员或历史引用。`
              : `当前共有 ${this.leafCount} 个叶子节点，叶子节点更适合重点看成员承接是否完整。`,
          action: '先判断节点是否仍在用，再决定禁用、启用或继续保留。'
        },
        {
          title: '第三步最后回账号和角色侧确认承接',
          desc:
            this.selection.length > 0
              ? `当前已选 ${this.selection.length} 个部门节点，处理完后最好回后台用户页和角色页看真实影响。`
              : '部门改完通常不算完事，还要继续确认后台用户、角色和日志页有没有同步反映这次组织调整。',
          action: '处理完成后继续去后台用户页、角色页和操作日志页做交叉复核。'
        }
      ]
    },
    deptSummaryBadgeClass() {
      if (this.selection.length) {
        return 'is-active'
      }
      return this.disabledCount > 0 ? 'is-warning' : 'is-safe'
    },
    deptSummaryBadgeText() {
      if (this.selection.length) {
        return `待复核 ${this.selection.length} 项`
      }
      return this.disabledCount > 0 ? '需巡检' : '结构稳定'
    },
    deptSummaryHint() {
      if (this.selection.length) {
        return this.submitRiskHint
      }
      return `${this.runtimeHint} ${this.followupHint}`
    },
    deptFollowupTags() {
      return [
        `已选部门：${this.selection.length} 项`,
        `禁用节点：${this.disabledCount} 项`,
        `叶子节点：${this.leafCount} 项`,
        `筛选标签：${this.activeFilterTags.length} 项`
      ]
    },
    deptFollowupRiskText() {
      if (this.selection.length) {
        return '部门节点的上级、禁用和成员解绑会直接影响后台账号归属，请提交前确认组织调整范围。'
      }
      if (this.disabledCount > 0) {
        return '当前有禁用部门，建议同步核对这些节点下是否仍有后台账号、审批链或统计口径在使用。'
      }
      return '部门管理不是孤立页面，通常还要继续去后台用户、角色和日志页确认组织调整是否真正落地。'
    },
    deptFollowupActionCards() {
      const baseQuery = {
        from: 'system-dept',
        source_count: this.count || 0
      }
      if (this.selection.length) {
        return [
          {
            title: '去后台用户页看归属',
            desc: '部门改完后先看后台用户页，最容易确认账号是否已经挂到正确组织下。',
            path: '/system/user',
            query: {
              ...baseQuery,
              dept_ids: this.selectIds
            }
          },
          {
            title: '去角色页交叉核权限',
            desc: '如果组织调整伴随权限变化，继续到角色页核对会更稳。',
            path: '/system/role',
            query: {
              ...baseQuery,
              dept_ids: this.selectIds
            }
          },
          {
            title: '去操作日志排查',
            desc: '遇到组织改了但结果没回显、账号没同步时，日志页最适合继续追。',
            path: '/system/user-log',
            query: {
              ...baseQuery,
              dept_ids: this.selectIds
            }
          }
        ]
      }
      return [
        {
          title: '去后台用户页巡检归属',
          desc: '先看后台用户页，确认部门结构是否已经被真实账号使用。',
          path: '/system/user',
          query: baseQuery
        },
        {
          title: '去角色页核组织权限',
          desc: '组织架构和角色权限通常一起看，避免组织改了权限没跟上。',
          path: '/system/role',
          query: baseQuery
        },
        {
          title: '去操作日志看回显',
          desc: '如果部门调整后出现异常回显，直接去日志页最省时间。',
          path: '/system/user-log',
          query: baseQuery
        }
      ]
    },
    deptDialogFocusLabel() {
      if (!this.model[this.idkey]) {
        return this.model.dept_pid ? '先看子部门挂载位置' : '先补顶级部门'
      }
      if (Number(this.model.is_disable || 0) === 1) {
        return '当前节点已禁用'
      }
      return '优先核组织层级和承接'
    },
    deptDialogGuideCards() {
      return [
        {
          title: '第一步：先看这次要把节点挂到哪',
          desc:
            '部门层级一旦挂错，后台账号归属、审批链和统计口径都会一起偏。先确认上级，再去补资料。',
          action: `当前上级：${this.model.dept_pid || '顶级部门'}`
        },
        {
          title: '第二步：再补运营真正会看的基础信息',
          desc:
            '名称、简称、电话、邮箱、地址这些字段决定运营能不能快速识别这个组织节点，不建议只填一个名字就结束。',
          action: `名称：${this.model.dept_name || '未填写'}；简称：${this.model.dept_abbr || '未填写'}`
        },
        {
          title: '第三步：保存后要回账号侧确认承接',
          desc:
            '部门结构改完不代表账号已经理解这次调整，最好继续去后台用户页和角色页复核真实影响。',
          action:
            this.model[this.idkey]
              ? `当前修改部门 ID：${this.model[this.idkey]}`
              : '新增后建议立刻去后台用户页确认账号归属'
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

      const deptPid = this.parseRouteNumber(routeQuery.dept_pid)
      if (deptPid !== undefined) {
        nextQuery.dept_pid = deptPid
      }
      const isDisable = this.parseRouteNumber(routeQuery.is_disable)
      if (isDisable !== undefined) {
        nextQuery.is_disable = isDisable
      }

      const deptId = this.parseRouteNumber(routeQuery.dept_id)
      if (deptId !== undefined) {
        nextQuery.search_field = this.idkey
        nextQuery.search_exp = '='
        nextQuery.search_value = String(deptId)
      } else if (routeQuery.dept_ids && !nextQuery.search_value) {
        nextQuery.search_field = this.idkey
        nextQuery.search_exp = 'in'
        nextQuery.search_value = String(routeQuery.dept_ids)
      }

      if (!nextQuery.search_value && routeQuery.dept_name) {
        nextQuery.search_value = String(routeQuery.dept_name)
      }

      this.query = nextQuery
    },
    flattenDeptData(tree = []) {
      return tree.reduce((acc, item) => {
        acc.push(item)
        if (Array.isArray(item.children) && item.children.length) {
          acc.push(...this.flattenDeptData(item.children))
        }
        return acc
      }, [])
    },
    setRecentActionSummary(action, extra = '') {
      this.recentActionSummary = `已执行${action}，影响 ${this.selection.length || 0} 个部门节点${
        extra ? `，${extra}` : ''
      }。`
    },
    getDeptLabel(row) {
      return row.dept_name || row.dept_abbr || `部门#${row[this.idkey]}`
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
        this.goToSystemPage('/system/user', this.buildEntryRouteQuery({}, 'system-dept'))
        return
      }
      if (this.entrySourceLabel === '来自角色管理') {
        this.goToSystemPage('/system/role', this.buildEntryRouteQuery({}, 'system-dept'))
        return
      }
      if (this.entrySourceLabel === '来自用户日志') {
        this.goToSystemPage('/system/user-log', this.buildEntryRouteQuery({}, 'system-dept'))
        return
      }
      this.goToSystemPage('/dashboard', this.buildEntryRouteQuery({}, 'system-dept'))
    },
    goToEntryContextBack() {
      this.handleEntryContextPrimary()
    },
    goToSystemPage(path, query = {}) {
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
          this.trees = res.data.tree
          this.exps = res.data.exps
          this.count = res.data.count
          this.isExpandAll = false
          this.loading = false
          this.recentActionSummary = `已加载部门节点 ${res.data.count || 0} 项，禁用部门 ${
            this.flattenDeptData(res.data.list || []).filter(
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
        this.model.dept_pid = row[this.idkey]
        this.recentActionSummary = `准备在部门 ${row[this.idkey]} 下添加子节点。`
      } else {
        this.recentActionSummary = '准备新增顶级部门节点。'
      }
    },
    edit(row) {
      this.dialog = true
      this.dialogTitle = this.name + '修改：' + row[this.idkey]
      this.recentActionSummary = `准备修改部门 ${row[this.idkey]}。`
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
                this.recentActionSummary = `已修改部门 ${this.model[this.idkey]}，名称：${
                  this.model.dept_name || '未命名'
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
                this.recentActionSummary = `已新增部门节点，名称：${
                  this.model.dept_name || '未命名'
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
      this.recentActionSummary = `已按 ${this.query.search_field || 'dept_name'} 发起部门检索。`
      this.list()
    },
    // 刷新
    refresh() {
      const limit = this.query.limit
      this.query = this.$options.data().query
      this.$refs['table'].clearSort()
      this.query.limit = limit
      this.recentActionSummary = '已重置部门筛选条件。'
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
        this.recentActionSummary = `已勾选 ${selection.length} 个部门节点，待处理 ID：${this.selectIds}。`
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
          dept_id: this.selectGetIds(row),
          user_ids: []
        })
          .then((res) => {
            this.list()
            this.setRecentActionSummary('批量解除部门成员')
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
        dept_pid: this.dept_pid
      })
        .then((res) => {
          this.list()
          this.selectDialog = false
          this.setRecentActionSummary(
            '批量修改部门上级',
            `目标上级：${this.dept_pid || '顶级部门'}`
          )
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
            this.setRecentActionSummary(
              '批量调整部门状态',
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
        `确认要${value === 1 ? '禁用' : '启用'}部门「${this.getDeptLabel(row)}」吗？`,
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
            this.setRecentActionSummary('批量删除部门节点')
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.loading = false
          })
      }
    },
    // 部门用户显示
    userShow(row) {
      this.userDialog = true
      this.userDialogTitle = this.name + '用户：' + row.dept_name
      this.recentActionSummary = `正在查看部门 ${row.dept_name || row[this.idkey]} 下的成员列表。`
      this.userQuery.dept_id = row.dept_id
      this.userQuery.search_value = ''
      this.user()
    },
    // 部门用户列表
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
    // 部门用户排序
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
    // 部门用户操作
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
          this.userSelectTitle = '解除' + this.name
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
    // 部门用户解除
    userRemove(row) {
      if (!row.length) {
        this.userSelectAlert()
      } else {
        this.userLoad = true
        userRemove({
          dept_id: this.userQuery.dept_id,
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
.select-review-panel {
  margin-bottom: 12px;
  padding: 12px 14px;
  border: 1px solid #dbe7f5;
  border-radius: 12px;
  background: #f8fbff;
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

.select-review-panel__title {
  font-size: 12px;
  font-weight: 700;
  color: #475569;
}

.select-review-panel__hint,
.section-title-row__desc {
  margin-top: 4px;
  font-size: 12px;
  color: #64748b;
  line-height: 1.7;
}

.dialog-plain-guide {
  margin-bottom: 14px;
  padding: 12px 14px;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
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
  font-weight: 700;
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

.section-title-row__meta {
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
  white-space: nowrap;
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

.dept-summary-bar {
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

.dept-summary-bar__chips {
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

.dept-summary-bar__hint {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 240px;
  padding: 12px 14px;
  border-radius: 12px;
  background: #eef4ff;
  color: #1d4ed8;
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
  margin-top: 16px;
  padding: 14px 16px;
  border: 1px solid #dbe7f5;
  border-radius: 14px;
  background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
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
  line-height: 1.7;
  color: #64748b;
}

.followup-panel__risk {
  max-width: 360px;
  padding: 10px 12px;
  border-radius: 12px;
  border: 1px solid #fde68a;
  background: #fff8e8;
  color: #92400e;
}

.followup-panel__tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 12px;
}

.followup-panel__tags span {
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  padding: 0 10px;
  border: 1px solid #dbe7f5;
  border-radius: 999px;
  background: #fff;
  color: #334155;
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
  border: 1px solid #dbe7f5;
  border-radius: 14px;
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
  line-height: 1.7;
  color: #64748b;
}

.dept-summary-bar__hint.is-safe {
  background: #eaf8ef;
  color: #15803d;
}

.dept-summary-bar__hint.is-warning {
  background: #fff5e8;
  color: #b45309;
}

.dept-summary-bar__hint.is-active {
  background: #e8f0ff;
  color: #1d4ed8;
}

.dept-summary-bar__hint-title {
  font-size: 12px;
  font-weight: 700;
}

.dept-summary-bar__hint-text {
  font-size: 12px;
  line-height: 1.7;
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
  color: #9a3412;
  padding: 0;
  border: none;
  border-radius: 0;
  background: transparent;
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
  .dialog-plain-guide__header,
  .dept-summary-bar,
  .followup-panel__header {
    flex-direction: column;
  }

  .section-title-row__meta {
    white-space: normal;
  }
}
</style>
