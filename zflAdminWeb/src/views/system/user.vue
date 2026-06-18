<template>
  <div class="app-container">
    <el-card class="app-head">
      <div class="section-title-row">
        <div>
          <div class="section-title-row__title">后台用户管理</div>
          <div class="section-title-row__desc">
            按状态、超管、组织架构和账号信息快速定位后台用户，并继续做组织、角色和账号治理。
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
            <el-form-item label="超管：" prop="is_super">
              <el-select v-model="query.is_super" @change="search()" clearable>
                <el-option :value="1" label="是" />
                <el-option :value="0" label="否" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="部门：" prop="dept_ids">
              <el-cascader
                v-model="query.dept_ids"
                :options="deptData"
                :props="deptProps"
                @change="search()"
                clearable
                filterable
                collapse-tags
              />
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="职位：" prop="post_ids">
              <el-cascader
                v-model="query.post_ids"
                :options="postData"
                :props="postProps"
                @change="search()"
                clearable
                filterable
                collapse-tags
              />
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="角色：" prop="role_ids">
              <el-select
                v-model="query.role_ids"
                @change="search()"
                clearable
                filterable
                multiple
                collapse-tags
              >
                <el-option
                  v-for="item in roleData"
                  :key="item.role_id"
                  :label="item.role_name"
                  :value="item.role_id"
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
                  <el-option value="number" label="编号" />
                  <el-option value="nickname" label="昵称" />
                  <el-option value="username" label="账号" />
                  <el-option value="phone" label="手机" />
                  <el-option value="email" label="邮箱" />
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
      <div class="user-summary-bar">
        <div class="user-summary-bar__chips">
          <span class="summary-chip summary-chip--primary">总数 {{ count }}</span>
          <span class="summary-chip">已选 {{ selection.length }} 项</span>
          <span class="summary-chip">
            {{
              query.is_disable === undefined
                ? '全部用户'
                : query.is_disable === 1
                ? '禁用用户'
                : '启用用户'
            }}
          </span>
          <span v-for="item in activeFilterTags" :key="item" class="summary-chip">{{ item }}</span>
          <span v-if="!activeFilterTags.length" class="summary-chip">默认条件：全部后台用户</span>
          <span v-if="recentActionSummary" class="summary-chip summary-chip--muted">{{
            recentActionSummary
          }}</span>
        </div>
        <div class="user-summary-bar__hint" :class="userFollowupBadgeClass">
          <span class="user-summary-bar__hint-title">{{ userFollowupBadgeText }}</span>
          <span class="user-summary-bar__hint-text">{{ userFollowupHint }}</span>
        </div>
      </div>
      <div class="plain-guide">
        <div class="plain-guide__header">
          <div>
            <div class="plain-guide__title">这页建议先这样看</div>
            <div class="plain-guide__desc">
              后台用户页主要在管“谁能进后台、进来后能看什么、属于哪个组织”。先看账号状态和角色归属，再看是否是超管，最后才去做批量改角色、改部门或重置密码。
            </div>
          </div>
          <span class="plain-guide__badge">{{ userFocusLabel }}</span>
        </div>
        <div class="plain-guide__grid">
          <div v-for="item in userGuideCards" :key="item.title" class="plain-guide-card">
            <div class="plain-guide-card__title">{{ item.title }}</div>
            <div class="plain-guide-card__desc">{{ item.desc }}</div>
            <div class="plain-guide-card__action">{{ item.action }}</div>
          </div>
        </div>
      </div>
      <div class="followup-panel">
        <div class="followup-panel__header">
          <div>
            <div class="followup-panel__title">账号治理后继续去哪</div>
            <div class="followup-panel__desc">
              组织、角色、超管和密码处理完成后，继续到权限、组织和日志页做闭环复核。
            </div>
          </div>
          <div class="followup-panel__risk">{{ userFollowupRiskText }}</div>
        </div>
        <div class="followup-panel__tags">
          <span v-for="item in userFollowupTags" :key="item">{{ item }}</span>
        </div>
        <div class="followup-card-grid">
          <button
            v-for="item in userFollowupActionCards"
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
          <div class="select-review-panel__hint">{{ selectRiskHint }}</div>
        </div>
        <el-form-item v-if="selectType === 'editdept'" label="部门" prop="dept_ids">
          <el-cascader
            v-model="dept_ids"
            :options="deptData"
            :props="deptProps"
            class="w-full"
            clearable
            filterable
          />
        </el-form-item>
        <el-form-item v-else-if="selectType === 'editpost'" label="职位" prop="post_ids">
          <el-cascader
            v-model="post_ids"
            :options="postData"
            :props="postProps"
            class="w-full"
            clearable
            filterable
          />
        </el-form-item>
        <el-form-item v-else-if="selectType === 'editrole'" label="角色" prop="role_ids">
          <el-select v-model="role_ids" class="w-full" clearable filterable multiple>
            <el-option
              v-for="item in roleData"
              :key="item.role_id"
              :label="item.role_name"
              :value="item.role_id"
            />
          </el-select>
        </el-form-item>
        <el-form-item v-else-if="selectType === 'password'" label="新密码" prop="password">
          <el-input v-model="password" placeholder="请输入新密码" clearable show-password />
        </el-form-item>
        <el-form-item v-else-if="selectType === 'super'" label="是否超管">
          <!--          <el-switch v-model="is_super" :active-value="1" :inactive-value="0" />-->
          <el-radio-group v-model="is_super">
            <el-radio :value="1">是</el-radio>
            <el-radio :value="0">否</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item v-else-if="selectType === 'disable'" label="状态">
          <!--          <el-switch v-model="is_disable" :active-value="1" :inactive-value="0" />-->
          <el-radio-group v-model="is_disable">
            <el-radio :value="0">启用</el-radio>
            <el-radio :value="1">禁用</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item v-else-if="selectType === 'dele'">
          <span class="c-red">确定要删除选中的{{ name }}吗？</span>
        </el-form-item>
        <el-form-item :label="name + 'ID'" :prop="idkey">
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
          <div class="section-title-row__title">后台用户列表</div>
          <div class="section-title-row__desc">
            支持批量组织调整、角色分配、超管控制和密码重置。
          </div>
        </div>
        <div class="section-title-row__meta">已选 {{ selection.length }} 项</div>
      </div>
      <div class="operation_btn mb5">
        <!-- 操作 -->
        <div>
          <div class="action-cluster">
            <span class="action-cluster__title">用户维护</span>
            <el-button type="primary" @click="add()">添加</el-button>
            <el-button title="是否禁用" @click="selectOpen('disable')">禁用</el-button>
            <el-button title="是否超管" @click="selectOpen('super')">超管</el-button>
          </div>
          <div class="action-cluster">
            <span class="action-cluster__title">组织配置</span>
            <el-button title="修改部门" @click="selectOpen('editdept')">部门</el-button>
            <el-button title="修改职位" @click="selectOpen('editpost')">职位</el-button>
            <el-button title="修改角色" @click="selectOpen('editrole')">角色</el-button>
          </div>
          <div class="action-cluster">
            <span class="action-cluster__title">安全处理</span>
            <el-button title="重置密码" @click="selectOpen('password')">密码</el-button>
            <el-button :title="'删除' + name" @click="selectOpen('dele')">删除</el-button>
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
        <el-table-column prop="avatar_id" label="头像" min-width="62">
          <template #default="scope">
            <FileImage :file-url="scope.row.avatar_url" avatar lazy />
          </template>
        </el-table-column>
        <el-table-column
          prop="number"
          label="编号"
          min-width="85"
          sortable="custom"
          show-overflow-tooltip
        />
        <el-table-column
          prop="nickname"
          label="昵称"
          min-width="100"
          sortable="custom"
          show-overflow-tooltip
        />
        <el-table-column
          prop="username"
          label="账号"
          min-width="100"
          sortable="custom"
          show-overflow-tooltip
        />
        <el-table-column prop="dept_names" label="部门" min-width="120" show-overflow-tooltip />
        <el-table-column prop="post_names" label="职位" min-width="120" show-overflow-tooltip />
        <el-table-column prop="role_names" label="角色" min-width="120" show-overflow-tooltip />
        <el-table-column prop="is_super" label="超管" min-width="85" sortable="custom">
          <template #default="scope">
            <el-switch
              :model-value="scope.row.is_super"
              :active-value="1"
              :inactive-value="0"
              @change="handleSuperSwitch(scope.row, $event)"
            />
          </template>
        </el-table-column>
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
        <el-table-column prop="login_time" label="登录时间" width="165" sortable="custom" />
        <el-table-column prop="create_time" label="添加时间" width="165" sortable="custom" />
        <el-table-column prop="update_time" label="修改时间" width="165" sortable="custom" />
        <el-table-column label="操作" width="185">
          <template #default="scope">
            <el-link type="primary" class="mr-1" :underline="false" @click="edit(scope.row)">
              修改
            </el-link>
            <el-link type="primary" class="mr-1" :underline="false" @click="goToUserLog(scope.row)">
              日志
            </el-link>
            <el-link
              type="primary"
              class="mr-1"
              :underline="false"
              @click="goToSystemPage('/system/menu', {
                from: 'system-user',
                user_id: scope.row[idkey],
                username: scope.row.username || '',
                nickname: scope.row.nickname || ''
              })"
            >
              菜单
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
        <el-scrollbar class="mt5" native :max-height="height - 30">
          <el-form ref="ref" :model="model" :rules="rules" label-width="100px">
            <div class="dialog-plain-guide">
              <div class="dialog-plain-guide__header">
                <div>
                  <div class="dialog-plain-guide__title">账号编辑先看归属和权限，再补基础资料</div>
                  <div class="dialog-plain-guide__desc">
                    先确认这个后台账号给谁用、挂在哪个组织、承担哪些角色，再去补昵称、手机、邮箱和排序。账号资料看起来是基础字段，实际上会直接影响后续排查和交接效率。
                  </div>
                </div>
                <span class="dialog-plain-guide__badge">{{ userDialogFocusLabel }}</span>
              </div>
              <div class="dialog-plain-guide__grid">
                <div
                  v-for="item in userDialogGuideCards"
                  :key="item.title"
                  class="dialog-plain-guide-card"
                >
                  <div class="dialog-plain-guide-card__title">{{ item.title }}</div>
                  <div class="dialog-plain-guide-card__desc">{{ item.desc }}</div>
                  <div class="dialog-plain-guide-card__action">{{ item.action }}</div>
                </div>
              </div>
            </div>
            <el-form-item label="头像" prop="avatar_id">
              <FileImage
                v-model="model.avatar_id"
                :file-url="model.avatar_url"
                file-title="上传头像"
                file-tip="图片小于 100 KB，jpg、png格式，100 x 100。"
                :height="100"
                avatar
                upload
              />
            </el-form-item>
            <el-form-item label="编号" prop="number">
              <el-input
                key="number"
                v-model="model.number"
                placeholder="请输入编号（工号）"
                clearable
              />
            </el-form-item>
            <el-form-item label="昵称" prop="nickname">
              <el-input
                key="nickname"
                v-model="model.nickname"
                placeholder="请输入昵称（姓名）"
                clearable
              />
            </el-form-item>
            <el-form-item label="账号" prop="username">
              <el-input
                key="username"
                v-model="model.username"
                placeholder="请输入账号（用户名）"
                clearable
              />
            </el-form-item>
            <el-form-item v-if="model[idkey] == ''" label="密码" prop="password">
              <el-input
                key="password"
                v-model="model.password"
                placeholder="请输入密码"
                clearable
                show-password
              />
            </el-form-item>
            <el-form-item label="手机" prop="phone">
              <el-input v-model="model.phone" clearable />
            </el-form-item>
            <el-form-item label="邮箱" prop="email">
              <el-input v-model="model.email" clearable />
            </el-form-item>
            <el-form-item label="备注" prop="remark">
              <el-input v-model="model.remark" clearable />
            </el-form-item>
            <el-form-item label="排序" prop="sort">
              <el-input v-model="model.sort" type="number" />
            </el-form-item>
            <el-form-item v-if="model[idkey]" label="登录次数" prop="login_ip">
              <el-input v-model="model.login_num" disabled />
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
            <el-form-item v-if="model[idkey]" label="退出时间" prop="logout_time">
              <el-input v-model="model.logout_time" disabled />
            </el-form-item>
            <el-form-item v-if="model[idkey]" label="添加时间" prop="create_time">
              <el-input v-model="model.create_time" disabled />
            </el-form-item>
            <el-form-item v-if="model.delete_time" label="删除时间" prop="delete_time">
              <el-input v-model="model.delete_time" disabled />
            </el-form-item>
            <el-form-item v-if="model[idkey]" label="部门" prop="dept_ids">
              <el-cascader
                v-model="model.dept_ids"
                :options="deptData"
                :props="deptProps"
                class="w-full"
                disabled
                placeholder=""
              />
            </el-form-item>
            <el-form-item v-if="model[idkey]" label="职位" prop="post_ids">
              <el-cascader
                v-model="model.post_ids"
                :options="postData"
                :props="postProps"
                class="w-full"
                disabled
                placeholder=""
              />
            </el-form-item>
            <el-form-item v-if="model[idkey]" label="角色" prop="role_ids">
              <el-select v-model="model.role_ids" class="w-full" disabled multiple placeholder="">
                <el-option
                  v-for="item in roleData"
                  :key="item.role_id"
                  :label="item.role_name"
                  :value="item.role_id"
                />
              </el-select>
            </el-form-item>
            <el-form-item v-if="model[idkey]" label="菜单(权限)" prop="menu_tree">
              <el-col>
                <el-checkbox
                  v-model="menuExpandAll"
                  title="展开/收起"
                  @change="menuExpandAllChange"
                >
                  展开
                </el-checkbox>
              </el-col>
              <el-tree
                ref="menuRef"
                :data="model.menu_tree"
                :props="menuProps"
                node-key="menu_id"
                :default-checked-keys="model.menu_ids"
                :expand-on-click-node="false"
                highlight-current
              >
                <template #default="scope">
                  <span class="custom-tree-node">
                    <span>
                      {{ scope.node.label }}
                      <i
                        v-if="scope.data.is_check"
                        class="el-icon-check"
                        style="color: #1890ff"
                        title="已分配"
                      >
                        <svg-icon icon-class="check" />
                      </i>
                    </span>
                    <span>
                      <i v-if="scope.data.is_role" style="margin-left: 10px" title="角色">
                        <svg-icon icon-class="custom" />
                      </i>
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
import Pagination from '@/components/Pagination/index.vue'
import { arrayColumn } from '@/utils/index'
import { getPageLimit } from '@/utils/settings'
import {
  list,
  info,
  add,
  edit,
  dele,
  editdept,
  editpost,
  editrole,
  repwd,
  issuper,
  disable
} from '@/api/system/user'
import { shortcuts } from '@/utils/getDate.js'
import { getAdminRuntimeHint, resolveAdminRuntimeEnv } from '@/utils/runtime-env'

export default {
  name: 'SystemUser',
  components: { Pagination },
  data() {
    return {
      name: '用户',
      height: 680,
      loading: false,
      idkey: 'user_id',
      exps: [{ exp: 'like', name: '包含' }],
      query: {
        page: 1,
        limit: getPageLimit(),
        search_field: 'username',
        search_exp: 'like',
        search_value: '',
        date_field: 'create_time',
        dept_ids: undefined,
        post_ids: undefined,
        role_ids: undefined,
        is_super: undefined,
        is_disable: undefined
      },
      data: [],
      count: 0,
      dialog: false,
      dialogTitle: '',
      model: {
        user_id: '',
        number: '',
        avatar_id: 0,
        avatar_url: '',
        nickname: '',
        username: '',
        password: '',
        phone: '',
        email: '',
        remark: '',
        sort: 250,
        login_ip: '',
        login_region: '',
        login_time: '',
        logout_time: '',
        create_time: '',
        update_time: '',
        dept_ids: [],
        post_ids: [],
        role_ids: []
      },
      rules: {
        nickname: [{ required: true, message: '请输入昵称', trigger: 'blur' }],
        username: [{ required: true, message: '请输入账号', trigger: 'blur' }],
        password: [{ required: true, message: '请输入密码', trigger: 'blur' }]
      },
      selection: [],
      selectIds: '',
      selectTitle: '操作',
      selectDialog: false,
      selectType: '',
      dept_ids: [],
      post_ids: [],
      role_ids: [],
      password: '',
      is_super: 0,
      is_disable: 0,
      deptData: [],
      deptProps: {
        checkStrictly: true,
        value: 'dept_id',
        label: 'dept_name',
        multiple: true,
        emitPath: false
      },
      postData: [],
      postProps: {
        checkStrictly: true,
        value: 'post_id',
        label: 'post_name',
        multiple: true,
        emitPath: false
      },
      roleData: [],
      menuProps: { label: 'menu_name', children: 'children' },
      menuExpandAll: false,
      shortcuts: shortcuts(),
      recentActionSummary: ''
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
  computed: {
    runtimeEnvInfo() {
      return resolveAdminRuntimeEnv()
    },
    runtimeHint() {
      return getAdminRuntimeHint(this.runtimeEnvInfo)
    },
    entrySourceLabel() {
      const source = String(this.$route?.query?.from || '')
      if (source === 'system-dept') return '来自部门管理'
      if (source === 'system-post') return '来自职位管理'
      if (source === 'system-role') return '来自角色管理'
      if (source === 'system-menu') return '来自菜单管理'
      if (source === 'system-user-log') return '来自用户日志'
      if (source === 'dashboard') return '来自控制台总览'
      return ''
    },
    entryContextVisible() {
      return Boolean(this.entrySourceLabel)
    },
    entryContextTitle() {
      if (this.entrySourceLabel === '来自部门管理') return '当前从部门管理进入后台用户'
      if (this.entrySourceLabel === '来自职位管理') return '当前从职位管理进入后台用户'
      if (this.entrySourceLabel === '来自角色管理') return '当前从角色管理进入后台用户'
      if (this.entrySourceLabel === '来自菜单管理') return '当前从菜单管理进入后台用户'
      if (this.entrySourceLabel === '来自用户日志') return '当前从用户日志进入后台用户'
      if (this.entrySourceLabel === '来自控制台总览') return '当前从控制台进入后台用户'
      return '当前为外部入口承接视角'
    },
    entryContextDesc() {
      if (this.entrySourceLabel === '来自部门管理') {
        return '这类进入通常是为了看某个部门下到底有哪些后台账号。建议先核部门归属，再继续看职位、角色和超管能力。'
      }
      if (this.entrySourceLabel === '来自职位管理') {
        return '这类进入通常是为了排某个职位当前挂了哪些后台账号。建议先按职位与状态缩小范围，再继续核组织和角色承接。'
      }
      if (this.entrySourceLabel === '来自角色管理') {
        return '这类进入通常是为了看某个角色到底落到了哪些后台账号。建议先按角色和状态缩小范围，再继续核组织归属和超管能力。'
      }
      if (this.entrySourceLabel === '来自菜单管理') {
        return '这类进入通常是为了排“谁看到了这个菜单”或“谁不该看到却看到了”。建议先锁定账号，再回菜单页看权限面。'
      }
      if (this.entrySourceLabel === '来自用户日志') {
        return '这类进入通常是为了排异常登录、误操作或责任归属。建议先确认账号当前状态和角色，再回日志页核行为链。'
      }
      if (this.entrySourceLabel === '来自控制台总览') {
        return '这类进入通常是为了做后台账号巡检。建议先看启用状态、角色和组织归属，再继续做批量治理。'
      }
      return ''
    },
    entryContextPrimaryLabel() {
      if (this.entrySourceLabel === '来自部门管理') return '回部门管理'
      if (this.entrySourceLabel === '来自职位管理') return '回职位管理'
      if (this.entrySourceLabel === '来自角色管理') return '回角色管理'
      if (this.entrySourceLabel === '来自菜单管理') return '回菜单管理'
      return '去用户日志复核'
    },
    activeFilterTags() {
      const tags = []
      if (this.query.date_value?.length === 2) {
        tags.push(`添加时间：${this.query.date_value[0]} 至 ${this.query.date_value[1]}`)
      }
      if (this.query.is_disable !== undefined) {
        tags.push(`状态：${this.query.is_disable === 1 ? '禁用' : '启用'}`)
      }
      if (this.query.is_super !== undefined) {
        tags.push(`超管：${this.query.is_super === 1 ? '是' : '否'}`)
      }
      if (Array.isArray(this.query.dept_ids) && this.query.dept_ids.length) {
        tags.push(
          `部门：${this.resolveUserNames(
            this.query.dept_ids,
            this.deptData,
            'dept_id',
            'dept_name'
          )}`
        )
      }
      if (Array.isArray(this.query.post_ids) && this.query.post_ids.length) {
        tags.push(
          `职位：${this.resolveUserNames(
            this.query.post_ids,
            this.postData,
            'post_id',
            'post_name'
          )}`
        )
      }
      if (Array.isArray(this.query.role_ids) && this.query.role_ids.length) {
        tags.push(
          `角色：${this.resolveUserNames(
            this.query.role_ids,
            this.roleData,
            'role_id',
            'role_name'
          )}`
        )
      }
      if (this.query.search_value) {
        tags.push(`检索：${this.query.search_value}`)
      }
      return tags
    },
    userFocusLabel() {
      if (this.selection.length > 0) {
        return '先复核批量账号'
      }
      if (this.query.is_super !== undefined) {
        return '先看超管账号'
      }
      if (Array.isArray(this.query.role_ids) && this.query.role_ids.length) {
        return '先看当前角色人群'
      }
      return '先看账号归属'
    },
    userGuideCards() {
      return [
        {
          title: '第一步先看这个账号现在归谁管',
          desc:
            Array.isArray(this.query.dept_ids) && this.query.dept_ids.length
              ? `当前已经按组织缩小范围，先确认这些账号的部门归属和职位承接是不是正确。`
              : '后台用户页最容易漏看的是组织归属。账号挂错部门，后面角色和菜单再对也会出治理偏差。',
          action: '优先核对组织、职位和账号状态，再决定是否继续做批量调整。'
        },
        {
          title: '第二步再看角色和超管能力',
          desc:
            this.query.is_super !== undefined
              ? '当前正在看超管相关账号，这类账号影响面最大，任何调整都要先确认是否仍承担全局职责。'
              : '账号能看到什么、能操作什么，本质上由角色和超管能力决定，不要只看用户名和状态。',
          action: '先抽查角色、超管状态和菜单可见范围，再去改角色、超管或密码。'
        },
        {
          title: '第三步最后再做批量治理',
          desc:
            this.selection.length > 0
              ? `当前已选 ${this.selection.length} 个账号，适合继续做改角色、改部门、改职位或重置密码。`
              : '批量治理不是越快越好，最好先把筛选范围缩小，再一次性提交，避免误改无关账号。',
          action: '处理完成后继续去角色页、菜单页和操作日志页做交叉复核。'
        }
      ]
    },
    userFollowupBadgeText() {
      if (this.selection.length > 0) {
        return '待提交复核'
      }
      if (this.activeFilterTags.length > 0) {
        return '筛选已收敛'
      }
      return '默认巡检'
    },
    userFollowupBadgeClass() {
      if (this.selection.length > 0) {
        return 'is-active'
      }
      if (
        this.query.is_super !== undefined ||
        (Array.isArray(this.query.role_ids) && this.query.role_ids.length)
      ) {
        return 'is-warning'
      }
      return 'is-safe'
    },
    userFollowupHint() {
      if (this.selection.length > 0) {
        return '当前已选中后台账号，可继续做角色、组织、超管和密码等批量治理，但建议先复核账号归属。'
      }
      if (this.activeFilterTags.length > 0) {
        return '当前列表已经按筛选条件收敛，建议先抽查目标账号的当前角色与组织，再继续批量调整。'
      }
      return '当前为后台账号巡检视角，建议优先按状态、组织和角色缩小范围，再做权限或组织调整。'
    },
    userFollowupTags() {
      return [
        `已选账号：${this.selection.length} 个`,
        `筛选标签：${this.activeFilterTags.length} 项`,
        `角色筛选：${Array.isArray(this.query.role_ids) ? this.query.role_ids.length : 0} 项`,
        `组织筛选：${Array.isArray(this.query.dept_ids) ? this.query.dept_ids.length : 0} 项`
      ]
    },
    userFollowupRiskText() {
      if (this.selection.length > 0) {
        return '批量角色、超管和密码调整都会直接影响后台登录与可见菜单，请确保勾选账号和目标配置完全一致。'
      }
      if (this.query.is_super !== undefined) {
        return '当前正在筛选超管相关账号，提交任何权限调整前都建议再次确认该账号是否仍承担全局管理职责。'
      }
      return '后台账号属于高影响对象，建议先通过筛选缩小范围，再执行组织、角色、密码和删除动作。'
    },
    userFollowupActionCards() {
      const baseQuery = {
        from: 'system-user',
        source_count: this.count || 0
      }
      if (this.selection.length > 0) {
        return [
          {
            title: '去操作日志复核',
            desc: '账号权限改完后，先去操作日志看有没有异常登录、批量变更或报错。',
            path: '/system/user-log',
            query: {
              ...baseQuery,
              user_ids: this.selectIds,
              search_value: this.selection[0]?.username || this.selection[0]?.nickname || ''
            }
          },
          {
            title: '去角色页继续治理',
            desc: '如果本次调整涉及统一角色或超管能力，继续到角色页核对权限归属。',
            path: '/system/role',
            query: {
              ...baseQuery,
              user_ids: this.selectIds
            }
          },
          {
            title: '去菜单页看权限面',
            desc: '想确认账号看到什么菜单、能走到哪，直接去菜单页继续排查最稳。',
            path: '/system/menu',
            query: {
              ...baseQuery,
              user_ids: this.selectIds
            }
          }
        ]
      }
      return [
        {
          title: '去操作日志巡检',
          desc: '先通过日志看后台账号有没有异常行为，再决定是否回这里做更细治理。',
          path: '/system/user-log',
          query: baseQuery
        },
        {
          title: '去角色页整理权限',
          desc: '后台账号治理通常和角色一起看更顺，尤其是线上操作习惯承接时。',
          path: '/system/role',
          query: baseQuery
        },
        {
          title: '去部门职位页承接',
          desc: '需要把账号问题落到组织结构时，继续到部门页做更清楚的归属核对。',
          path: '/system/dept',
          query: baseQuery
        }
      ]
    },
    selectReviewItems() {
      const items = [
        `操作：${this.selectTitle || '批量处理'}`,
        `数量：${this.selection.length} 项`,
        `对象：${this.buildSelectionPreview()}`
      ]
      if (this.selectType === 'editdept' && this.dept_ids.length) {
        items.push(
          `目标部门：${this.resolveUserNames(this.dept_ids, this.deptData, 'dept_id', 'dept_name')}`
        )
      } else if (this.selectType === 'editpost' && this.post_ids.length) {
        items.push(
          `目标职位：${this.resolveUserNames(this.post_ids, this.postData, 'post_id', 'post_name')}`
        )
      } else if (this.selectType === 'editrole' && this.role_ids.length) {
        items.push(
          `目标角色：${this.resolveUserNames(this.role_ids, this.roleData, 'role_id', 'role_name')}`
        )
      } else if (this.selectType === 'super') {
        items.push(`超管调整：${this.is_super === 1 ? '设为超管' : '取消超管'}`)
      } else if (this.selectType === 'disable') {
        items.push(`状态调整：${this.is_disable === 1 ? '批量禁用' : '批量启用'}`)
      } else if (this.selectType === 'password' && this.password) {
        items.push('密码处理：将覆盖所选后台账号登录密码')
      }
      return items
    },
    selectRiskHint() {
      if (this.selectType === 'dele') {
        return '删除后台账号属于高风险动作，提交前请确认该账号不再承担运营、客服或财务职责。'
      }
      if (this.selectType === 'password') {
        return '重置密码会直接影响后台登录，建议先通知对应人员并确认账号归属。'
      }
      if (this.selectType === 'super' || this.selectType === 'editrole') {
        return `权限和角色调整会影响后台可见菜单及操作范围。${
          this.roleImpactSummary
            ? ` 当前影响：${this.roleImpactSummary}`
            : '提交前请再次复核目标角色与勾选账号。'
        }`
      }
      return '提交前请确认勾选账号、组织归属和目标值一致，避免批量调整到非预期后台用户。'
    },
    roleImpactSummary() {
      if (this.selectType === 'super') {
        return this.is_super === 1 ? '所选账号将获得超管能力。' : '所选账号将失去超管能力。'
      }
      if (this.selectType === 'editrole' && this.role_ids.length) {
        return `所选账号将统一改为角色：${this.resolveUserNames(
          this.role_ids,
          this.roleData,
          'role_id',
          'role_name'
        )}。`
      }
      return ''
    },
    userDialogFocusLabel() {
      if (!this.model[this.idkey]) {
        return '先定义账号归属'
      }
      if (this.model.role_ids?.length) {
        return '优先核角色权限承接'
      }
      return '先看组织与登录状态'
    },
    userDialogGuideCards() {
      return [
        {
          title: '第一步：先看这个账号给谁用',
          desc:
            '后台账号不是单纯用户名。先确认它对应的是运营、客服、财务还是系统管理员，再决定后面的角色和组织归属。',
          action: `账号：${this.model.username || '未填写'}；昵称：${this.model.nickname || '未填写'}`
        },
        {
          title: '第二步：再看组织和角色是不是匹配',
          desc:
            '账号挂错部门或角色配错，会直接造成菜单看到不对、日志排查对不上、甚至线上误操作。',
          action:
            this.model[this.idkey]
              ? `部门 ${Array.isArray(this.model.dept_ids) ? this.model.dept_ids.length : 0} 项，角色 ${Array.isArray(this.model.role_ids) ? this.model.role_ids.length : 0} 项`
              : '新增账号后建议继续用批量治理弹窗统一补组织和角色'
        },
        {
          title: '第三步：最后补联系信息和登录资料',
          desc:
            '手机、邮箱、备注、最后登录信息这些字段决定后面能不能快速找到责任人，也方便做权限变更后的回访。',
          action:
            this.model[this.idkey]
              ? `最近登录：${this.model.login_time || '暂无记录'}`
              : '新增账号保存后，建议立刻去日志页确认首次登录链路'
        }
      ]
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

      const deptIds = this.parseRouteArray(routeQuery.dept_ids) || this.parseRouteArray(routeQuery.dept_id)
      if (deptIds?.length) {
        nextQuery.dept_ids = deptIds
      }

      const postIds = this.parseRouteArray(routeQuery.post_ids) || this.parseRouteArray(routeQuery.post_id)
      if (postIds?.length) {
        nextQuery.post_ids = postIds
      }

      const roleIds = this.parseRouteArray(routeQuery.role_ids) || this.parseRouteArray(routeQuery.role_id)
      if (roleIds?.length) {
        nextQuery.role_ids = roleIds
      }

      const isSuper = this.parseRouteNumber(routeQuery.is_super)
      if (isSuper !== undefined) {
        nextQuery.is_super = isSuper
      }

      const isDisable = this.parseRouteNumber(routeQuery.is_disable)
      if (isDisable !== undefined) {
        nextQuery.is_disable = isDisable
      }

      if (
        !nextQuery.search_value &&
        (routeQuery.menu_name || routeQuery.menu_url || routeQuery.username || routeQuery.nickname)
      ) {
        nextQuery.search_value = String(
          routeQuery.menu_name ||
            routeQuery.menu_url ||
            routeQuery.username ||
            routeQuery.nickname ||
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
    handleEntryContextPrimary() {
      if (this.entrySourceLabel === '来自部门管理') {
        this.goToSystemPage('/system/dept', this.buildEntryRouteQuery({}, 'system-user'))
        return
      }
      if (this.entrySourceLabel === '来自职位管理') {
        this.goToSystemPage('/system/post', this.buildEntryRouteQuery({}, 'system-user'))
        return
      }
      if (this.entrySourceLabel === '来自角色管理') {
        this.goToSystemPage('/system/role', this.buildEntryRouteQuery({}, 'system-user'))
        return
      }
      if (this.entrySourceLabel === '来自菜单管理') {
        this.goToSystemPage('/system/menu', this.buildEntryRouteQuery({}, 'system-user'))
        return
      }
      this.goToSystemPage('/system/user-log', this.buildEntryRouteQuery({}, 'system-user'))
    },
    goToEntryContextBack() {
      if (this.entrySourceLabel === '来自部门管理') {
        this.goToSystemPage('/system/dept', this.buildEntryRouteQuery({}, 'system-user'))
        return
      }
      if (this.entrySourceLabel === '来自职位管理') {
        this.goToSystemPage('/system/post', this.buildEntryRouteQuery({}, 'system-user'))
        return
      }
      if (this.entrySourceLabel === '来自角色管理') {
        this.goToSystemPage('/system/role', this.buildEntryRouteQuery({}, 'system-user'))
        return
      }
      if (this.entrySourceLabel === '来自菜单管理') {
        this.goToSystemPage('/system/menu', this.buildEntryRouteQuery({}, 'system-user'))
        return
      }
      if (this.entrySourceLabel === '来自用户日志') {
        this.goToSystemPage('/system/user-log', this.buildEntryRouteQuery({}, 'system-user'))
        return
      }
      if (this.entrySourceLabel === '来自控制台总览') {
        this.goToSystemPage('/dashboard', this.buildEntryRouteQuery({}, 'system-user'))
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
    setRecentActionSummary(action, extra = '') {
      this.recentActionSummary = `已执行${action}，影响 ${this.selection.length || 0} 个后台账号${
        extra ? `，${extra}` : ''
      }。`
    },
    goToSystemPage(path, query = {}) {
      this.$router.push({
        path,
        query
      })
    },
    goToUserLog(row) {
      this.goToSystemPage('/system/user-log', {
        from: 'system-user',
        user_id: row[this.idkey],
        username: row.username || '',
        nickname: row.nickname || '',
        search_value: row.username || row.nickname || row.phone || ''
      })
    },
    resolveUserNames(ids = [], source = [], valueKey = 'id', labelKey = 'title') {
      return ids
        .map((id) => source.find((item) => String(item[valueKey]) === String(id))?.[labelKey] || id)
        .join('、')
    },
    // 列表
    list() {
      this.loading = true
      list(this.query)
        .then((res) => {
          this.data = res.data.list
          this.count = res.data.count
          this.deptData = res.data.dept
          this.postData = res.data.post
          this.roleData = res.data.role
          this.exps = res.data.exps
          this.recentActionSummary = `后台用户列表已刷新，共 ${
            res.data.count || 0
          } 个账号，当前页已选 ${this.selection.length || 0} 项。`
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
      this.menuExpandAll = false
      this.fileDialog = false
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
    getUserLabel(row) {
      return row.nickname || row.username || row.number || row.phone || `账号#${row[this.idkey]}`
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
        if (selectType === 'editdept') {
          this.selectTitle = this.name + '修改部门'
        } else if (selectType === 'editpost') {
          this.selectTitle = this.name + '修改职位'
        } else if (selectType === 'editrole') {
          this.selectTitle = this.name + '修改角色'
        } else if (selectType === 'super') {
          this.selectTitle = this.name + '是否超管'
        } else if (selectType === 'password') {
          this.selectTitle = this.name + '重置密码'
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
        if (selectType === 'editdept') {
          this.editdept(this.selection, true)
        } else if (selectType === 'editpost') {
          this.editpost(this.selection, true)
        } else if (selectType === 'editrole') {
          this.editrole(this.selection, true)
        } else if (selectType === 'super') {
          this.issuper(this.selection, true)
        } else if (selectType === 'password') {
          this.repwd(this.selection, true)
        } else if (selectType === 'disable') {
          this.disable(this.selection, true)
        } else if (selectType === 'dele') {
          this.dele(this.selection)
        }
        this.selectDialog = false
      }
    },
    // 重置密码
    repwd(row, select = false) {
      if (!row.length) {
        this.selectAlert()
      } else {
        if (!this.password) {
          ElMessage.error('请输入新密码')
          return false
        }
        this.loading = true
        var password = row[0].password
        if (select) {
          password = this.password
        }
        repwd({
          ids: this.selectGetIds(row),
          password: password
        })
          .then((res) => {
            this.list()
            this.setRecentActionSummary('批量重置密码', '已覆盖所选账号登录密码')
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.loading = false
          })
      }
    },
    // 修改部门
    editdept(row) {
      if (!row.length) {
        this.selectAlert()
      } else {
        this.loading = true
        editdept({
          ids: this.selectGetIds(row),
          dept_ids: this.dept_ids
        })
          .then((res) => {
            this.list()
            this.setRecentActionSummary(
              '批量修改部门',
              `目标部门：${this.resolveUserNames(
                this.dept_ids,
                this.deptData,
                'dept_id',
                'dept_name'
              )}`
            )
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.loading = false
          })
      }
    },
    // 修改职位
    editpost(row) {
      if (!row.length) {
        this.selectAlert()
      } else {
        this.loading = true
        editpost({
          ids: this.selectGetIds(row),
          post_ids: this.post_ids
        })
          .then((res) => {
            this.list()
            this.setRecentActionSummary(
              '批量修改职位',
              `目标职位：${this.resolveUserNames(
                this.post_ids,
                this.postData,
                'post_id',
                'post_name'
              )}`
            )
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.loading = false
          })
      }
    },
    // 修改角色
    editrole(row) {
      if (!row.length) {
        this.selectAlert()
      } else {
        this.loading = true
        editrole({
          ids: this.selectGetIds(row),
          role_ids: this.role_ids
        })
          .then((res) => {
            this.list()
            this.setRecentActionSummary(
              '批量修改角色',
              `目标角色：${this.resolveUserNames(
                this.role_ids,
                this.roleData,
                'role_id',
                'role_name'
              )}`
            )
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.loading = false
          })
      }
    },
    // 是否超管
    issuper(row, select = false) {
      if (!row.length) {
        this.selectAlert()
      } else {
        this.loading = true
        var is_super = row[0].is_super
        if (select) {
          is_super = this.is_super
        }
        issuper({
          ids: this.selectGetIds(row),
          is_super: is_super
        })
          .then((res) => {
            this.list()
            this.setRecentActionSummary(
              '批量调整超管状态',
              `目标状态：${is_super === 1 ? '设为超管' : '取消超管'}`
            )
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.list()
          })
      }
    },
    handleSuperSwitch(row, value) {
      ElMessageBox.confirm(
        `确认要将「${this.getUserLabel(row)}」${value === 1 ? '设为超管' : '取消超管'}吗？`,
        '操作确认',
        {
          type: 'warning',
          confirmButtonText: '继续',
          cancelButtonText: '取消'
        }
      )
        .then(() => {
          this.issuper([{ ...row, is_super: value }])
        })
        .catch(() => {})
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
              '批量调整账号状态',
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
        `确认要${value === 1 ? '禁用' : '启用'}账号「${this.getUserLabel(row)}」吗？`,
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
            this.setRecentActionSummary('批量删除后台账号')
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.loading = false
          })
      }
    },
    // 菜单（权限）
    menuExpandAllChange() {
      const expanded = this.menuExpandAll
      const length = this.$refs.menuRef.store._getAllNodes().length
      for (let i = 0; i < length; i++) {
        this.$refs.menuRef.store._getAllNodes()[i].expanded = expanded
      }
    },
    // 上传头像
    fileUpload() {
      this.fileDialog = true
    },
    fileCancel() {
      this.fileDialog = false
    },
    fileSubmit(fileList) {
      this.fileDialog = false
      const fileLength = fileList.length
      if (fileLength) {
        const i = fileLength - 1
        this.model.avatar_id = fileList[i]['file_id']
        this.model.avatar_url = fileList[i]['file_url']
      }
    },
    fileDelete() {
      this.model.avatar_id = 0
      this.model.avatar_url = ''
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
  font-size: 12px;
  color: #334155;
  background: #fff;
  border: 1px solid #dbe7f5;
  border-radius: 999px;
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

.user-summary-bar {
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

.user-summary-bar__chips {
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

.user-summary-bar__hint {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 240px;
  padding: 12px 14px;
  border-radius: 12px;
  background: #eef4ff;
  color: #1d4ed8;
}

.user-summary-bar__hint.is-safe {
  background: #eaf8ef;
  color: #15803d;
}

.user-summary-bar__hint.is-warning {
  background: #fff5e8;
  color: #b45309;
}

.user-summary-bar__hint.is-active {
  background: #e8f0ff;
  color: #1d4ed8;
}

.user-summary-bar__hint-title {
  font-size: 12px;
  font-weight: 700;
}

.user-summary-bar__hint-text {
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

.select-review-panel {
  margin-bottom: 16px;
  padding: 14px 16px;
  background: #f8fbff;
  border: 1px solid #dbe7f5;
  border-radius: 14px;
}

.select-review-panel__title {
  margin-bottom: 10px;
  font-size: 12px;
  font-weight: 700;
  color: #475569;
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
  .plain-guide__header,
  .dialog-plain-guide__header,
  .followup-panel__header,
  .user-summary-bar {
    flex-direction: column;
  }

  .section-title-row__meta {
    white-space: normal;
  }
}
</style>
