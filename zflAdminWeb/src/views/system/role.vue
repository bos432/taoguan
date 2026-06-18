<template>
  <div class="app-container">
    <el-card class="app-head">
      <div class="section-title-row">
        <div>
          <div class="section-title-row__title">角色管理</div>
          <div class="section-title-row__desc">
            统一处理角色筛选、菜单授权、成员解绑和启停维护。
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
                  <el-option value="role_name" label="名称" />
                  <el-option value="role_desc" label="描述" />
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
      <div class="role-summary-bar">
        <div class="role-summary-bar__chips">
          <span class="summary-chip summary-chip--primary">总数 {{ count }}</span>
          <span class="summary-chip">已选 {{ selection.length }} 项</span>
          <span class="summary-chip">菜单节点 {{ menuIds.length }}</span>
          <span v-for="item in activeFilterTags" :key="item" class="summary-chip">{{ item }}</span>
          <span v-if="!activeFilterTags.length" class="summary-chip">默认条件：全部角色</span>
          <span v-if="recentActionSummary" class="summary-chip summary-chip--muted">{{
            recentActionSummary
          }}</span>
        </div>
        <div class="role-summary-bar__hint" :class="roleFollowupBadgeClass">
          <span class="role-summary-bar__hint-title">{{ roleFollowupBadgeText }}</span>
          <span class="role-summary-bar__hint-text">{{ roleFollowupHint }}</span>
        </div>
      </div>
      <div class="plain-guide">
        <div class="plain-guide__header">
          <div>
            <div class="plain-guide__title">这页建议先这样看</div>
            <div class="plain-guide__desc">
              角色页主要在管“这类人能看到哪些菜单、能做哪些事”。先看角色是否还在用、菜单授权范围是否合理，再看挂了哪些账号，最后才去批量授权、禁用或删角色。
            </div>
          </div>
          <span class="plain-guide__badge">{{ roleFocusLabel }}</span>
        </div>
        <div class="plain-guide__grid">
          <div v-for="item in roleGuideCards" :key="item.title" class="plain-guide-card">
            <div class="plain-guide-card__title">{{ item.title }}</div>
            <div class="plain-guide-card__desc">{{ item.desc }}</div>
            <div class="plain-guide-card__action">{{ item.action }}</div>
          </div>
        </div>
      </div>
      <div class="followup-panel">
        <div class="followup-panel__header">
          <div>
            <div class="followup-panel__title">角色改完后继续去哪</div>
            <div class="followup-panel__desc">
              角色授权不是终点，下一步通常还要回菜单、账号和操作日志去核对真实影响面。
            </div>
          </div>
          <div class="followup-panel__risk">{{ roleFollowupRiskText }}</div>
        </div>
        <div class="followup-panel__tags">
          <span v-for="item in roleFollowupTags" :key="item">{{ item }}</span>
        </div>
        <div class="followup-card-grid">
          <button
            v-for="item in roleFollowupActionCards"
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
      <el-scrollbar native :height="height - 200">
        <el-form ref="selectRef" label-width="120px">
          <div v-if="selection.length" class="select-review-panel">
            <div class="select-review-panel__title">提交前复核</div>
            <div class="select-review-panel__tags">
              <span v-for="item in selectReviewItems" :key="item">{{ item }}</span>
            </div>
            <div class="select-review-panel__hint">{{ selectRiskHint }}</div>
          </div>
          <el-form-item :label="name + 'ID'">
            <el-input v-model="selectIds" type="textarea" autosize disabled />
          </el-form-item>
          <el-form-item v-if="selectType === 'removeu'">
            <span style="">确定要解除选中的{{ name }}的用户吗？</span>
          </el-form-item>
          <el-form-item v-else-if="selectType === 'editmenu'" label="菜单">
            <el-col>
              <el-checkbox
                v-model="menuExpandAll"
                title="展开/收起"
                @change="menuExpandAllChange('selMenuRef')"
              >
                展开
              </el-checkbox>
              <el-checkbox
                v-model="menuCheckAll"
                title="全选/反选"
                @change="menuCheckAllChange('selMenuRef')"
              >
                全选
              </el-checkbox>
            </el-col>
            <el-tree
              ref="selMenuRef"
              :data="menuData"
              :props="menuProps"
              :default-checked-keys="menu_ids"
              node-key="menu_id"
              show-checkbox
              check-strictly
              :expand-on-click-node="false"
              @check="apiCheck('selMenuRef')"
            >
              <template #default="scope">
                <span class="custom-tree-node">
                  <span>{{ scope.node.label }}</span>
                  <span v-if="scope.data.children" style="margin-left: 10px">
                    <el-checkbox
                      title="全选/反选"
                      @change="menuCheckAllChangePid(scope.node, scope.data, 'selMenuRef')"
                    >
                      全选
                    </el-checkbox>
                  </span>
                  <span>
                    <i
                      v-if="scope.data.api_url"
                      style="margin-left: 10px"
                      :title="scope.data.api_url"
                    >
                      <svg-icon icon-class="link" />
                    </i>
                    <i v-else style="margin-left: 10px; color: #fff">
                      <svg-icon icon-class="link" />
                    </i>
                    <i v-if="scope.data.is_unlogin" style="margin-left: 10px" title="免登">
                      <svg-icon icon-class="user" />
                    </i>
                    <i v-else style="margin-left: 10px; color: #fff">
                      <svg-icon icon-class="user" />
                    </i>
                    <i v-if="scope.data.is_unauth" style="margin-left: 10px" title="免权">
                      <svg-icon icon-class="unlock" />
                    </i>
                    <i v-else style="margin-left: 10px; color: #fff">
                      <svg-icon icon-class="unlock" />
                    </i>
                  </span>
                </span>
              </template>
            </el-tree>
          </el-form-item>
          <el-form-item v-else-if="selectType === 'disable'" label="是否禁用">
            <!--            <el-switch v-model="is_disable" :active-value="1" :inactive-value="0" />-->
            <el-radio-group v-model="is_disable">
              <el-radio :value="0">启用</el-radio>
              <el-radio :value="1">禁用</el-radio>
            </el-radio-group>
          </el-form-item>
          <el-form-item v-else-if="selectType === 'dele'">
            <span class="c-red">确定要删除选中的{{ name }}吗？</span>
          </el-form-item>
        </el-form>
      </el-scrollbar>
      <template #footer>
        <el-button @click="selectCancel">取消</el-button>
        <el-button type="primary" @click="selectSubmit">提交</el-button>
      </template>
    </el-dialog>
    <el-card class="app-main">
      <div class="section-title-row section-title-row--content">
        <div class="section-title-row__title">角色列表</div>
        <div class="section-title-row__meta">共 {{ count }} 项</div>
      </div>
      <div class="operation_btn mb5">
        <!-- 操作 -->
        <div>
          <div class="action-cluster">
            <span class="action-cluster__title">角色维护</span>
            <el-button type="primary" @click="add()">添加</el-button>
            <el-button title="是否禁用" @click="selectOpen('disable')">禁用</el-button>
          </div>
          <div class="action-cluster">
            <span class="action-cluster__title">权限与成员</span>
            <el-button title="修改菜单" @click="selectOpen('editmenu')">菜单授权</el-button>
            <el-button title="解除用户" @click="selectOpen('removeu')">解绑用户</el-button>
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
        <el-table-column prop="role_name" label="名称" min-width="120" show-overflow-tooltip />
        <el-table-column prop="role_desc" label="描述" min-width="160" show-overflow-tooltip />
        <el-table-column prop="remark" label="备注" min-width="150" show-overflow-tooltip />
        <el-table-column prop="is_disable" label="禁用" min-width="85" sortable="custom">
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
        <el-table-column prop="sort" label="排序" min-width="85" sortable="custom" />
        <el-table-column prop="create_time" label="添加时间" width="165" sortable="custom" />
        <el-table-column prop="update_time" label="修改时间" width="165" sortable="custom" />
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
              @click="goToSystemPage('/system/menu', {
                from: 'system-role',
                role_id: scope.row[idkey],
                role_name: scope.row.role_name || ''
              })"
            >
              菜单
            </el-link>
            <el-link
              type="primary"
              class="mr-1"
              :underline="false"
              @click="goToSystemPage('/system/user-log', {
                from: 'system-role',
                role_id: scope.row[idkey],
                role_name: scope.row.role_name || '',
                search_value: scope.row.role_name || scope.row.role_desc || ''
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
        destroy-on-close
      >
        <el-scrollbar class="mt5" native :max-height="height - 30">
          <el-form ref="ref" :rules="rules" :model="model" label-width="100px">
            <div class="dialog-plain-guide">
              <div class="dialog-plain-guide__header">
                <div>
                  <div class="dialog-plain-guide__title">角色编辑先看承接范围，再改名称和授权</div>
                  <div class="dialog-plain-guide__desc">
                    先确认这个角色要承接哪些后台人群、关键菜单和风险边界，再处理名称、描述、排序等基础资料，避免名字改了但权限思路没对齐。
                  </div>
                </div>
                <span class="dialog-plain-guide__badge">{{ roleDialogFocusLabel }}</span>
              </div>
              <div class="dialog-plain-guide__grid">
                <div
                  v-for="item in roleDialogGuideCards"
                  :key="item.title"
                  class="dialog-plain-guide-card"
                >
                  <div class="dialog-plain-guide-card__title">{{ item.title }}</div>
                  <div class="dialog-plain-guide-card__desc">{{ item.desc }}</div>
                  <div class="dialog-plain-guide-card__action">{{ item.action }}</div>
                </div>
              </div>
            </div>
            <el-form-item label="名称" prop="role_name">
              <el-input v-model="model.role_name" placeholder="请输入名称" clearable />
            </el-form-item>
            <el-form-item label="描述" prop="role_desc">
              <el-input
                v-model="model.role_desc"
                type="textarea"
                autosize
                placeholder="请输入描述"
              />
            </el-form-item>
            <el-form-item label="备注" prop="remark">
              <el-input v-model="model.remark" placeholder="请输入备注" clearable />
            </el-form-item>
            <el-form-item label="排序" prop="sort" placeholder="250">
              <el-input v-model="model.sort" type="number" />
            </el-form-item>
            <el-form-item label="菜单">
              <el-col>
                <el-checkbox
                  v-model="menuExpandAll"
                  title="展开/收起"
                  @change="menuExpandAllChange('menuRef')"
                >
                  展开
                </el-checkbox>
                <el-checkbox
                  v-model="menuCheckAll"
                  title="全选/反选"
                  @change="menuCheckAllChange('menuRef')"
                >
                  全选
                </el-checkbox>
              </el-col>
              <el-tree
                ref="menuRef"
                :data="menuData"
                :props="menuProps"
                :default-checked-keys="model.menu_ids"
                :node-key="menuIdkey"
                :expand-on-click-node="false"
                show-checkbox
                check-strictly
                @check="menuCheck('menuRef')"
              >
                <template #default="scope">
                  <span class="custom-tree-node">
                    <span>{{ scope.node.label }}</span>
                    <span v-if="scope.data.children" style="margin-left: 10px">
                      <el-checkbox
                        title="全选/反选"
                        @change="menuCheckAllChangePid(scope.node, scope.data, 'menuRef')"
                      >
                        全选
                      </el-checkbox>
                    </span>
                    <span>
                      <i
                        v-if="scope.data.menu_url"
                        style="margin-left: 10px"
                        :title="scope.data.menu_url"
                      >
                        <svg-icon icon-class="link" />
                      </i>
                      <i v-else style="margin-left: 10px; color: #fff">
                        <svg-icon icon-class="link" />
                      </i>
                      <i v-if="scope.data.is_unlogin" style="margin-left: 10px" title="免登">
                        <svg-icon icon-class="user" />
                      </i>
                      <i v-else class="el-icon-user" style="margin-left: 10px; color: #fff">
                        <svg-icon icon-class="user" />
                      </i>
                      <i v-if="scope.data.is_unauth" style="margin-left: 10px" title="免权">
                        <svg-icon icon-class="unlock" />
                      </i>
                      <i v-else style="margin-left: 10px; color: #fff">
                        <svg-icon icon-class="unlock" />
                      </i>
                    </span>
                  </span>
                </template>
              </el-tree>
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
      <!-- 用户 -->
      <el-dialog
        v-model="userDialog"
        :title="userDialogTitle"
        :close-on-click-modal="false"
        :close-on-press-escape="false"
        top="10vh"
        width="65%"
      >
        <!-- 用户操作 -->
        <el-row>
          <el-col>
            <el-button type="primary" title="解除" @click="userSelectOpen('userRemove')">
              解除
            </el-button>
            <el-input
              v-model="userQuery.search_value"
              class="ya-search-value"
              placeholder="昵称"
              clearable
            />
            <el-button type="primary" @click="userList()">查询</el-button>
          </el-col>
        </el-row>
        <!-- 用户列表 -->
        <el-table
          ref="userRef"
          v-loading="userLoad"
          :data="userData"
          :height="height - 50"
          @sort-change="userSort"
          @selection-change="userSelect"
        >
          <el-table-column type="selection" width="42" title="全选/反选" />
          <el-table-column prop="user_id" label="用户ID" width="100" sortable="custom" />
          <el-table-column prop="nickname" label="昵称" min-width="130" show-overflow-tooltip />
          <el-table-column prop="username" label="账号" min-width="130" show-overflow-tooltip />
          <el-table-column prop="role_names" label="角色" min-width="130" show-overflow-tooltip />
          <el-table-column prop="remark" label="备注" min-width="100" show-overflow-tooltip />
          <el-table-column prop="is_super" label="超管" min-width="80" sortable="custom">
            <template #default="scope">
              <el-switch
                v-model="scope.row.is_super"
                :active-value="1"
                :inactive-value="0"
                disabled
              />
            </template>
          </el-table-column>
          <el-table-column prop="is_disable" label="禁用" min-width="80" sortable="custom">
            <template #default="scope">
              <el-switch
                v-model="scope.row.is_disable"
                :active-value="1"
                :inactive-value="0"
                disabled
              />
            </template>
          </el-table-column>
          <el-table-column label="操作" width="70">
            <template #default="scope">
              <el-link
                type="primary"
                :underline="false"
                @click="userSelectOpen('userRemove', scope.row)"
              >
                解除
              </el-link>
            </template>
          </el-table-column>
        </el-table>
        <!-- 角色分页 -->
        <pagination
          v-show="userCount > 0"
          v-model:total="userCount"
          v-model:page="userQuery.page"
          v-model:limit="userQuery.limit"
          @pagination="userList"
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
          <el-form-item v-if="userSelectType === 'userRemove'" :label="name + 'ID'">
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
import { list, info, add, edit, dele, editmenu, disable, user, userRemove } from '@/api/system/role'
import { shortcuts } from '@/utils/getDate.js'
import { resolveAdminRuntimeEnv } from '@/utils/runtime-env'

export default {
  name: 'SystemRole',
  components: { Pagination },
  data() {
    return {
      name: '角色',
      height: 680,
      loading: false,
      idkey: 'role_id',
      exps: [{ exp: 'like', name: '包含' }],
      query: {
        page: 1,
        limit: getPageLimit(),
        search_field: 'role_name',
        search_exp: 'like',
        search_value: '',
        date_field: 'create_time',
        is_disable: undefined
      },
      data: [],
      count: 0,
      dialog: false,
      dialogTitle: '',
      dialogLoad: false,
      model: {
        role_id: '',
        menu_ids: [],
        role_name: '',
        role_desc: '',
        remark: '',
        sort: 250
      },
      rules: {
        role_name: [{ required: true, message: '请输入名称', trigger: 'blur' }]
      },
      menuIdkey: 'menu_id',
      menuData: [],
      menuIds: [],
      menuProps: { children: 'children', label: 'menu_name' },
      menuCheckAll: false,
      menuExpandAll: false,
      menuCheckAllPid: {},
      selMenuRefs: 'menuRef',
      selection: [],
      selectIds: '',
      selectTitle: '操作',
      selectDialog: false,
      selectType: '',
      is_disable: 0,
      menu_ids: [],
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
      shortcuts: shortcuts(),
      recentActionSummary: '',
      runtimeEnvInfo: resolveAdminRuntimeEnv()
    }
  },
  computed: {
    entrySourceLabel() {
      const source = this.$route?.query?.from
      if (source === 'system-user') return '来自后台用户'
      if (source === 'system-dept') return '来自部门管理'
      if (source === 'system-post') return '来自职位管理'
      if (source === 'system-menu') return '来自菜单管理'
      if (source === 'system-user-log') return '来自用户日志'
      if (source === 'dashboard') return '来自后台首页'
      return ''
    },
    entryContextVisible() {
      return Boolean(this.entrySourceLabel)
    },
    entryContextTitle() {
      if (this.entrySourceLabel === '来自后台用户') return '当前从后台用户进入角色管理'
      if (this.entrySourceLabel === '来自部门管理') return '当前从部门管理进入角色管理'
      if (this.entrySourceLabel === '来自职位管理') return '当前从职位管理进入角色管理'
      if (this.entrySourceLabel === '来自菜单管理') return '当前从菜单管理进入角色管理'
      if (this.entrySourceLabel === '来自用户日志') return '当前从用户日志进入角色管理'
      if (this.entrySourceLabel === '来自后台首页') return '当前从后台首页进入角色管理'
      return '当前为外部入口承接视角'
    },
    entryContextDesc() {
      if (this.entrySourceLabel === '来自后台用户') {
        return '这类进入通常是为了排某个账号到底该挂什么角色。建议先核角色状态和菜单覆盖，再回账号页确认真实承接。'
      }
      if (this.entrySourceLabel === '来自部门管理') {
        return '这类进入通常是为了把组织归属继续落到权限层。建议先看角色状态和菜单覆盖，再回部门页确认组织承接。'
      }
      if (this.entrySourceLabel === '来自职位管理') {
        return '这类进入通常是为了把岗位职责继续落到角色权限。建议先锁角色，再回职位页确认岗位和权限是否匹配。'
      }
      if (this.entrySourceLabel === '来自菜单管理') {
        return '这类进入通常是为了反查某组菜单权限应该挂在哪些角色上。建议先看角色范围，再回菜单页复核入口影响面。'
      }
      if (this.entrySourceLabel === '来自用户日志') {
        return '这类进入通常是为了排权限异常到底落在哪个角色。建议先锁角色，再回日志页看异常是否消失。'
      }
      return '这类进入通常是首页巡检后的继续下钻。建议先看禁用角色和菜单覆盖，再继续去菜单页或后台用户页。'
    },
    entryContextPrimaryLabel() {
      if (this.entrySourceLabel === '来自后台用户') return '回后台用户'
      if (this.entrySourceLabel === '来自部门管理') return '回部门管理'
      if (this.entrySourceLabel === '来自职位管理') return '回职位管理'
      if (this.entrySourceLabel === '来自菜单管理') return '回菜单管理'
      if (this.entrySourceLabel === '来自用户日志') return '回用户日志'
      return '回后台首页'
    },
    activeFilterTags() {
      const tags = []
      if (this.query.date_value?.length) {
        tags.push(`添加时间：${this.query.date_value.join(' 至 ')}`)
      }
      if (this.query.is_disable !== undefined) {
        tags.push(`启禁状态：${this.query.is_disable === 1 ? '禁用' : '启用'}`)
      }
      if (this.query.search_value) {
        tags.push(`关键词：${this.query.search_field || this.idkey}=${this.query.search_value}`)
      }
      return tags
    },
    currentPageDisabledCount() {
      return Array.isArray(this.data)
        ? this.data.filter((item) => Number(item.is_disable) === 1).length
        : 0
    },
    roleFocusLabel() {
      if (this.selection.length > 0) {
        return '先复核批量授权'
      }
      if (this.currentPageDisabledCount > 0) {
        return '先看禁用角色'
      }
      if (this.activeFilterTags.length > 0) {
        return '先看当前角色范围'
      }
      return '先看授权结构'
    },
    roleGuideCards() {
      return [
        {
          title: '第一步先看这个角色还在不在用',
          desc:
            this.currentPageDisabledCount > 0
              ? `当前页有 ${this.currentPageDisabledCount} 个禁用角色，先确认这些角色下是否还挂着有效账号。`
              : '不是所有角色都适合继续留着。先分清哪些是正在使用的角色，哪些只是历史残留或备用角色。',
          action: '优先核对角色状态和成员承接，再决定启用、禁用或删除。'
        },
        {
          title: '第二步再看菜单授权是不是过宽或过窄',
          desc:
            this.menuIds.length > 0
              ? `当前角色体系共挂了 ${this.menuIds.length} 个菜单节点，最怕的是授权超范围或关键入口缺失。`
              : '角色的核心不是名称，而是菜单权限。菜单配错，后台账号看到的页面和操作范围都会偏。',
          action: '先抽查关键菜单入口，再继续做批量授权或菜单调整。'
        },
        {
          title: '第三步最后回账号侧确认真实影响',
          desc:
            this.selection.length > 0
              ? `当前已选 ${this.selection.length} 个角色，处理完后一定要回后台用户页确认真实落到了哪些账号。`
              : '角色改完不代表真的生效，还要回用户页和日志页核对有没有实际承接和异常反馈。',
          action: '处理完成后继续去菜单页、后台用户页和操作日志页做交叉复核。'
        }
      ]
    },
    selectionPreview() {
      if (!this.selection.length) return '未选择'
      const ids = this.selection.slice(0, 5).map((item) => item[this.idkey]).join('、')
      return `${ids}${this.selection.length > 5 ? ' 等' : ''}`
    },
    selectReviewItems() {
      const items = [
        `操作：${this.selectTitle || '批量处理'}`,
        `数量：${this.selection.length} 项`,
        `对象：${this.selectionPreview}`
      ]
      if (this.selectType === 'disable') {
        items.push(`状态调整：${this.is_disable === 1 ? '批量禁用' : '批量启用'}`)
      } else if (this.selectType === 'editmenu') {
        items.push(`菜单节点：${this.menu_ids?.length || 0} 个`)
      }
      return items
    },
    selectRiskHint() {
      if (!this.selection.length) {
        return '请先勾选目标角色，再执行授权、禁用或解绑操作。'
      }
      if (this.selectType === 'dele') {
        return '删除角色属于高风险动作，提交前请先确认没有后台账号仍依赖这组权限。'
      }
      if (this.selectType === 'editmenu') {
        return '角色菜单授权会直接影响后台账号可见范围，建议先抽查商品、订单、系统这类关键入口。'
      }
      return '角色状态和用户解绑会直接影响后台账号权限承接，提交前请先确认目标成员范围。'
    },
    roleDialogFocusLabel() {
      if (!this.model[this.idkey]) {
        return '先定义角色承接范围'
      }
      return this.model.menu_ids?.length ? '优先核关键菜单覆盖' : '当前还未挂菜单'
    },
    roleDialogGuideCards() {
      return [
        {
          title: '第一步：先看这个角色给谁用',
          desc: '角色不是纯文案字段，先想清楚是给运营、财务、商家审核还是系统管理员使用。',
          action: this.model.role_name || '当前还没填角色名称'
        },
        {
          title: '第二步：再看菜单覆盖范围',
          desc: '真正决定角色能力的是菜单节点，不是名称。基础资料对了之后要尽快回菜单授权确认范围。',
          action: `当前菜单节点：${this.model.menu_ids?.length || 0} 个`
        },
        {
          title: '第三步：最后补描述和排序',
          desc: '描述用于团队识别，排序用于列表呈现，这些都应该排在角色承接和权限范围之后。',
          action: `排序：${this.model.sort || 0}`
        }
      ]
    },
    roleFollowupBadgeText() {
      if (this.selection.length > 0) {
        return '可批量授权'
      }
      if (this.currentPageDisabledCount > 0) {
        return '需复核'
      }
      return '角色稳定'
    },
    roleFollowupBadgeClass() {
      if (this.selection.length > 0) {
        return 'is-active'
      }
      if (this.currentPageDisabledCount > 0) {
        return 'is-warning'
      }
      return 'is-safe'
    },
    roleFollowupHint() {
      if (this.selection.length > 0) {
        return '当前已选中角色，可继续调整菜单授权、用户解绑和禁用状态。'
      }
      return '角色页更适合先核对角色禁用状态和菜单节点覆盖，再继续做授权或成员流转。'
    },
    roleFollowupTags() {
      return [
        `已选角色：${this.selection.length} 项`,
        `当前页禁用：${this.currentPageDisabledCount} 项`,
        `菜单节点：${this.menuIds.length} 个`,
        `筛选标签：${this.activeFilterTags.length} 项`
      ]
    },
    roleFollowupRiskText() {
      if (this.selection.length > 0) {
        return '角色的菜单授权和禁用状态会直接影响后台账号可见范围，批量提交前请先确认目标角色归属。'
      }
      if (this.currentPageDisabledCount > 0) {
        return '当前页有禁用角色，建议顺手核对这些角色下是否仍挂着有效账号或仍被菜单引用。'
      }
      return '角色页最怕“授权改了但账号没看出来”，所以每次改完都建议继续去菜单、用户和日志页交叉核对。'
    },
    roleFollowupActionCards() {
      const baseQuery = {
        from: 'system-role',
        source_count: this.count || 0
      }
      if (this.selection.length > 0) {
        return [
          {
            title: '去菜单页复核权限面',
            desc: '菜单授权改完后，直接回菜单页看结构和入口是否还挂在正确位置。',
            path: '/system/menu',
            query: {
              ...baseQuery,
              role_ids: this.selectIds
            }
          },
          {
            title: '去后台用户页看实际账号',
            desc: '角色最终要落到具体账号上，所以继续看用户页最能确认真实影响。',
            path: '/system/user',
            query: {
              ...baseQuery,
              role_ids: this.selectIds
            }
          },
          {
            title: '去操作日志排查',
            desc: '改完角色后如果出现权限不符、菜单缺失或误开放，日志页最适合追。',
            path: '/system/user-log',
            query: {
              ...baseQuery,
              role_ids: this.selectIds
            }
          }
        ]
      }
      return [
        {
          title: '去菜单页看授权结构',
          desc: '角色和菜单一定要一起看，先确认菜单树和角色授权是否对应。',
          path: '/system/menu',
          query: baseQuery
        },
        {
          title: '去后台用户页看成员承接',
          desc: '角色有没有真正挂到账号上，要在后台用户页里看才算落地。',
          path: '/system/user',
          query: baseQuery
        },
        {
          title: '去操作日志看回显',
          desc: '如果权限改了但行为不对，日志能最快反馈是否出现鉴权或菜单异常。',
          path: '/system/user-log',
          query: baseQuery
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
    parseRouteArray(value) {
      if (Array.isArray(value)) {
        return value
          .map((item) => this.parseRouteNumber(item))
          .filter((item) => item !== undefined)
      }
      if (value === undefined || value === null || value === '') {
        return undefined
      }
      return String(value)
        .split(',')
        .map((item) => this.parseRouteNumber(item.trim()))
        .filter((item) => item !== undefined)
    },
    applyRouteQuery() {
      const defaultQuery = this.$options.data().query
      const routeQuery = this.$route?.query || {}
      const nextQuery = {
        ...defaultQuery,
        limit: this.query?.limit || defaultQuery.limit
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

      const isDisable = this.parseRouteNumber(routeQuery.is_disable)
      if (isDisable !== undefined) {
        nextQuery.is_disable = isDisable
      }

      const roleId = this.parseRouteNumber(routeQuery.role_id)
      if (roleId !== undefined) {
        nextQuery.search_field = this.idkey
        nextQuery.search_exp = '='
        nextQuery.search_value = String(roleId)
      } else if (routeQuery.role_ids && !nextQuery.search_value) {
        nextQuery.search_field = this.idkey
        nextQuery.search_exp = 'in'
        nextQuery.search_value = String(routeQuery.role_ids)
      }

      if (
        !nextQuery.search_value &&
        (
          routeQuery.role_name ||
          routeQuery.menu_name ||
          routeQuery.username ||
          routeQuery.nickname ||
          routeQuery.dept_name ||
          routeQuery.post_name
        )
      ) {
        nextQuery.search_value = String(
          routeQuery.role_name ||
            routeQuery.menu_name ||
            routeQuery.username ||
            routeQuery.nickname ||
            routeQuery.dept_name ||
            routeQuery.post_name ||
            ''
        )
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
    setRecentActionSummary(summary) {
      this.recentActionSummary = summary
    },
    handleEntryContextPrimary() {
      if (this.entrySourceLabel === '来自后台用户') {
        this.goToSystemPage('/system/user', this.buildEntryRouteQuery({}, 'system-role'))
        return
      }
      if (this.entrySourceLabel === '来自部门管理') {
        this.goToSystemPage('/system/dept', this.buildEntryRouteQuery({}, 'system-role'))
        return
      }
      if (this.entrySourceLabel === '来自职位管理') {
        this.goToSystemPage('/system/post', this.buildEntryRouteQuery({}, 'system-role'))
        return
      }
      if (this.entrySourceLabel === '来自菜单管理') {
        this.goToSystemPage('/system/menu', this.buildEntryRouteQuery({}, 'system-role'))
        return
      }
      if (this.entrySourceLabel === '来自用户日志') {
        this.goToSystemPage('/system/user-log', this.buildEntryRouteQuery({}, 'system-role'))
        return
      }
      this.goToSystemPage('/dashboard', this.buildEntryRouteQuery({}, 'system-role'))
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
          this.count = res.data.count
          this.menuData = res.data.menu
          this.menuIds = res.data.menu_ids
          this.exps = res.data.exps
          this.setRecentActionSummary(
            `角色列表已刷新，共 ${res.data.count || 0} 项，菜单节点 ${
              res.data.menu_ids?.length || 0
            } 个。`
          )
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
      this.setRecentActionSummary('准备新增角色。')
    },
    edit(row) {
      this.dialog = true
      this.dialogTitle = this.name + '修改：' + row[this.idkey]
      this.setRecentActionSummary(`准备修改角色：${row.role_name || row[this.idkey]}。`)
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
                this.setRecentActionSummary(
                  `已修改角色：${this.model.role_name || this.model[this.idkey]}。`
                )
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
                this.setRecentActionSummary(`已新增角色：${this.model.role_name || '未命名角色'}。`)
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
      this.menuCheckAll = false
      this.menuExpandAll = false
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
    getRoleLabel(row) {
      return row.role_name || row.role_desc || `角色#${row[this.idkey]}`
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
        } else if (selectType === 'editmenu') {
          this.selectTitle = this.name + '修改菜单'
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
        } else if (selectType === 'editmenu') {
          this.editmenu(this.selection)
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
          role_id: this.selectGetIds(row),
          user_ids: []
        })
          .then((res) => {
            this.list()
            this.setRecentActionSummary('已批量解除角色下的用户绑定。')
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.loading = false
          })
      }
    },
    // 修改菜单
    editmenu(row) {
      if (!row.length) {
        this.selectAlert()
      } else {
        this.loading = true
        editmenu({
          ids: this.selectGetIds(row),
          menu_ids: this.menu_ids
        })
          .then((res) => {
            this.list()
            this.setRecentActionSummary(
              `已批量更新角色菜单授权，授权节点 ${this.menu_ids.length || 0} 个。`
            )
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
              `已批量调整角色状态：${is_disable === 1 ? '禁用' : '启用'}。`
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
        `确认要${value === 1 ? '禁用' : '启用'}角色「${this.getRoleLabel(row)}」吗？`,
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
            this.setRecentActionSummary('已批量删除角色。')
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.loading = false
          })
      }
    },
    // 菜单选择
    menuCheck(refs = 'menuRef') {
      this.selMenuRefs = refs
      this.menuCheckSetKeys()
    },
    menuCheckAllChange(refs = 'menuRef') {
      this.selMenuRefs = refs
      if (this.menuCheckAll) {
        this.menuIds.forEach((item) => {
          this.$refs[this.selMenuRefs].setChecked(item, true, true)
        })
      } else {
        this.$refs[this.selMenuRefs].setCheckedKeys([])
      }
      this.menuCheckSetKeys()
    },
    menuExpandAllChange(refs = 'menuRef') {
      this.selMenuRefs = refs
      const expanded = this.menuExpandAll
      const length = this.$refs[this.selMenuRefs].store._getAllNodes().length
      for (let i = 0; i < length; i++) {
        this.$refs[this.selMenuRefs].store._getAllNodes()[i].expanded = expanded
      }
    },
    menuCheckAllChangePid(node, data, refs = 'menuRef') {
      this.selMenuRefs = refs
      this.menuCheckAllPid[data.menu_id] = node.checked
      this.$refs[this.selMenuRefs].setChecked(
        data.menu_id,
        !this.menuCheckAllPid[data.menu_id],
        true
      )
      data.children.forEach((item) => {
        this.$refs[this.selMenuRefs].setChecked(
          item.menu_id,
          !this.menuCheckAllPid[data.menu_id],
          true
        )
      })
      this.menuCheckSetKeys()
    },
    menuCheckSetKeys() {
      if (this.selMenuRefs === 'menuRef') {
        this.model.menu_ids = this.$refs[this.selMenuRefs].getCheckedKeys()
      } else {
        this.menu_ids = this.$refs[this.selMenuRefs].getCheckedKeys()
      }
    },
    // 用户显示
    userShow(row) {
      this.userDialog = true
      this.userDialogTitle = this.name + this.userName + '：' + row.role_name
      this.setRecentActionSummary(`正在查看角色用户：${row.role_name || row[this.idkey]}。`)
      this.userQuery.role_id = row.role_id
      this.userQuery.search_value = ''
      this.userList()
    },
    // 用户列表
    userList() {
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
    // 用户排序
    userSort(sort) {
      this.userQuery.sort_field = sort.prop
      this.userQuery.sort_value = ''
      if (sort.order === 'ascending') {
        this.userQuery.sort_value = 'asc'
        this.userList()
      }
      if (sort.order === 'descending') {
        this.userQuery.sort_value = 'desc'
        this.userList()
      }
    },
    // 用户操作
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
          this.userSelectTitle = this.name + '解除用户'
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
    // 用户解除
    userRemove(row) {
      if (!row.length) {
        this.userSelectAlert()
      } else {
        this.userLoad = true
        userRemove({
          role_id: this.userQuery.role_id,
          user_ids: this.userSelectGetIds(row)
        })
          .then((res) => {
            this.userList()
            this.setRecentActionSummary(`已解除角色用户绑定，影响 ${row.length || 0} 个成员。`)
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

.role-summary-bar {
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

.role-summary-bar__chips {
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

.role-summary-bar__hint {
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
  gap: 10px;
  margin-top: 12px;
}

.followup-panel__tags span {
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

.role-summary-bar__hint.is-safe {
  background: #eaf8ef;
  color: #15803d;
}

.role-summary-bar__hint.is-warning {
  background: #fff5e8;
  color: #b45309;
}

.role-summary-bar__hint.is-active {
  background: #e8f0ff;
  color: #1d4ed8;
}

.role-summary-bar__hint-title {
  font-size: 12px;
  font-weight: 700;
}

.role-summary-bar__hint-text {
  font-size: 12px;
  line-height: 1.7;
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
  color: #9a3412;
  font-size: 12px;
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
  .role-summary-bar,
  .followup-panel__header {
    flex-direction: column;
  }

  .section-title-row__meta {
    white-space: normal;
  }
}
</style>
