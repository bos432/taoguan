<template>
  <div class="app-container">
    <el-card class="app-head">
      <div class="module-header">
        <div>
          <div class="module-header__title">会员分组</div>
          <div class="module-header__desc">
            集中维护默认分组、接口授权和会员归属，默认直达列表处理。
          </div>
        </div>
        <div class="module-header__meta">
          <span class="module-header__badge">{{ runtimeEnvInfo.label }}</span>
          <span class="module-header__badge module-header__badge--sub">
            {{ runtimeEnvInfo.dataMode }}
          </span>
        </div>
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
      <div class="summary-bar">
        <span class="summary-bar__item">分组 {{ count }}</span>
        <span class="summary-bar__item">已选 {{ selection.length }}</span>
        <span class="summary-bar__item">默认 {{ currentPageDefaultCount }}</span>
        <span class="summary-bar__item">禁用 {{ currentPageDisabledCount }}</span>
        <span class="summary-bar__item">接口节点 {{ apiIds.length }}</span>
        <span v-if="activeFilterSummary" class="summary-bar__item">{{ activeFilterSummary }}</span>
        <span class="summary-bar__risk">{{ runtimeHint }}</span>
      </div>
      <div class="group-guide-panel">
        <div class="group-guide-panel__header">
          <div>
            <div class="group-guide-panel__title">会员分组第一次排查，建议先这样看</div>
            <div class="group-guide-panel__desc">先判断是默认分组问题、会员归属问题，还是接口授权问题，再决定是改默认、改接口还是解绑会员。</div>
          </div>
          <div class="group-guide-panel__badge">{{ groupGuideFocusLabel }}</div>
        </div>
        <div class="group-guide-panel__grid">
          <div v-for="item in groupGuideCards" :key="item.title" class="group-guide-card">
            <span class="group-guide-card__step">{{ item.step }}</span>
            <div class="group-guide-card__title">{{ item.title }}</div>
            <div class="group-guide-card__desc">{{ item.desc }}</div>
          </div>
        </div>
      </div>
      <div class="followup-panel">
        <div class="followup-panel__main">
          <div class="followup-panel__title">看完分组后继续去哪</div>
          <div class="followup-panel__desc">{{ groupFollowupHint }}</div>
          <div class="followup-panel__tags">
            <span v-for="item in groupFollowupTags" :key="item">{{ item }}</span>
          </div>
          <div class="followup-panel__risk">{{ groupFollowupRiskText }}</div>
        </div>
        <div class="followup-panel__actions">
          <el-button type="primary" @click="goToPage('/member/member')">去会员列表</el-button>
          <el-button @click="goToPage('/member/tag')">去会员标签</el-button>
          <el-button @click="goToPage('/member/api')">去会员接口</el-button>
        </div>
      </div>
      <!-- 查询 -->
      <el-form :model="query" ref="searchForm" label-width="85px" class="filter-form">
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
                  <el-option value="group_name" label="名称" />
                  <el-option value="group_desc" label="描述" />
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
          <el-form-item v-if="selectType === 'removem'">
            <span style="">确定要解除选中的{{ name }}的会员吗？</span>
          </el-form-item>
          <el-form-item v-else-if="selectType === 'editapi'" label="会员接口">
            <el-col>
              <el-checkbox
                v-model="apiExpandAll"
                title="展开/收起"
                @change="apiExpandAllChange('selApiRef')"
              >
                展开
              </el-checkbox>
              <el-checkbox
                v-model="apiCheckAll"
                title="全选/反选"
                @change="apiCheckAllChange('selApiRef')"
              >
                全选
              </el-checkbox>
            </el-col>
            <el-tree
              ref="selApiRef"
              :data="apiData"
              :props="apiProps"
              :default-checked-keys="api_ids"
              node-key="api_id"
              show-checkbox
              check-strictly
              :expand-on-click-node="false"
              @check="apiCheck('selApiRef')"
            >
              <template #default="scope">
                <span class="custom-tree-node">
                  <span>{{ scope.node.label }}</span>
                  <span v-if="scope.data.children" style="margin-left: 10px">
                    <el-checkbox
                      title="全选/反选"
                      @change="apiCheckAllChangePid(scope.node, scope.data, 'selApiRef')"
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
          <el-form-item v-else-if="selectType === 'defaults'" label="是否默认">
            <el-switch v-model="is_default" :active-value="1" :inactive-value="0" />
            <span> {{ memberName }} 新增时默认的分组</span>
          </el-form-item>
          <el-form-item v-else-if="selectType === 'disable'" label="是否禁用">
            <el-switch v-model="is_disable" :active-value="1" :inactive-value="0" />
          </el-form-item>
          <el-form-item v-else-if="selectType === 'dele'">
            <span class="c-red">确定要删除选中的{{ name }}吗？</span>
          </el-form-item>
          <el-form-item :label="name + 'ID'">
            <el-input v-model="selectIds" type="textarea" autosize disabled />
          </el-form-item>
        </el-form>
      </el-scrollbar>
      <template #footer>
        <el-button @click="selectCancel">取消</el-button>
        <el-button type="primary" @click="selectSubmit">提交</el-button>
      </template>
    </el-dialog>
    <el-card class="app-main">
      <div class="list-header">
        <div>
          <div class="list-header__title">会员分组列表</div>
          <div class="list-header__desc">
            支持默认分组、接口授权、批量禁用和分组会员解绑。
          </div>
        </div>
        <div class="list-header__meta">
          <span>{{ groupFollowupBadgeText }}</span>
          <span v-if="recentActionSummary">{{ recentActionSummary }}</span>
        </div>
      </div>
      <div class="operation_btn mb5">
        <!-- 操作 -->
        <div>
          <div class="action-cluster">
            <span class="action-cluster__title">分组维护</span>
            <el-button type="primary" @click="add()">添加</el-button>
            <el-button title="是否默认" @click="selectOpen('defaults')">默认</el-button>
            <el-button title="是否禁用" @click="selectOpen('disable')">禁用</el-button>
          </div>
          <div class="action-cluster">
            <span class="action-cluster__title">权限与流转</span>
            <el-button title="修改接口" @click="selectOpen('editapi')">接口</el-button>
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
        <el-table-column prop="group_name" label="名称" min-width="160" show-overflow-tooltip />
        <el-table-column prop="group_desc" label="描述" min-width="220" show-overflow-tooltip />
        <el-table-column prop="remark" label="备注" min-width="150" show-overflow-tooltip />
        <el-table-column prop="is_default" label="默认" min-width="85" sortable="custom">
          <template #default="scope">
            <el-switch
              v-model="scope.row.is_default"
              :active-value="1"
              :inactive-value="0"
              @change="defaults([scope.row])"
            />
          </template>
        </el-table-column>
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
      destroy-on-close
    >
      <el-scrollbar native :max-height="height - 30">
        <el-form ref="ref" :rules="rules" :model="model" label-width="100px">
          <el-form-item label="名称" prop="group_name">
            <el-input v-model="model.group_name" placeholder="请输入名称" clearable />
          </el-form-item>
          <el-form-item label="描述" prop="group_desc">
            <el-input
              v-model="model.group_desc"
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
          <el-form-item label="接口">
            <el-col :span="24">
              <el-checkbox
                v-model="apiExpandAll"
                title="展开/收起"
                @change="apiExpandAllChange('apiRef')"
              >
                展开
              </el-checkbox>
              <el-checkbox
                v-model="apiCheckAll"
                title="全选/反选"
                @change="apiCheckAllChange('apiRef')"
              >
                全选
              </el-checkbox>
            </el-col>
            <el-col :span="24">
              <el-tree
                ref="apiRef"
                :data="apiData"
                :props="apiProps"
                :default-checked-keys="model.api_ids"
                node-key="api_id"
                show-checkbox
                check-strictly
                :expand-on-click-node="false"
                @check="apiCheck('apiRef')"
              >
                <template #default="scope">
                  <span class="custom-tree-node">
                    <span>{{ scope.node.label }}</span>
                    <span v-if="scope.data.children" style="margin-left: 10px">
                      <el-checkbox
                        title="全选/反选"
                        @change="apiCheckAllChangePid(scope.node, scope.data, 'apiRef')"
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
            </el-col>
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
    <!-- 分组会员 -->
    <el-dialog
      v-model="memberDialog"
      :title="memberDialogTitle"
      :close-on-click-modal="false"
      :close-on-press-escape="false"
      top="5vh"
      width="70%"
    >
      <!-- 分组会员操作 -->
      <el-row>
        <el-col>
          <el-button type="primary" title="解除" @click="memberSelectOpen('memberRemove')">
            解除
          </el-button>
          <el-input
            v-model="memberQuery.search_value"
            class="ya-search-value"
            placeholder="昵称"
            clearable
          />
          <el-button type="primary" @click="member()">查询</el-button>
        </el-col>
      </el-row>
      <!-- 分组会员列表 -->
      <el-table
        ref="memberRef"
        v-loading="memberLoad"
        :data="memberData"
        :height="height - 50"
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
        <el-table-column prop="group_names" label="分组" width="170" show-overflow-tooltip />
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
      <!-- 分组会员分页 -->
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
        <el-form-item v-if="memberSelectType === 'memberRemove'" label="分组ID">
          <span>{{ memberQuery[idkey] }}</span>
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
import { getAdminRuntimeHint, resolveAdminRuntimeEnv } from '@/utils/runtime-env'
import {
  list,
  info,
  add,
  edit,
  dele,
  editapi,
  defaults,
  disable,
  member,
  memberRemove
} from '@/api/member/group'

export default {
  name: 'MemberGroup',
  components: { Pagination },
  data() {
    return {
      name: '会员分组',
      height: 680,
      loading: false,
      idkey: 'group_id',
      exps: [{ exp: 'like', name: '包含' }],
      query: {
        page: 1,
        limit: getPageLimit(),
        search_field: 'group_name',
        search_exp: 'like',
        date_field: 'create_time'
      },
      data: [],
      count: 0,
      dialog: false,
      dialogTitle: '',
      model: {
        group_id: '',
        group_name: '',
        group_desc: '',
        remark: '',
        sort: 250,
        api_ids: []
      },
      rules: {
        group_name: [{ required: true, message: '请输入分组名称', trigger: 'blur' }]
      },
      apiIds: [],
      apiData: [],
      apiProps: { children: 'children', label: 'api_name' },
      apiCheckAll: false,
      apiExpandAll: false,
      apiCheckAllPid: {},
      selApiRefs: 'apiRef',
      selection: [],
      selectIds: '',
      selectTitle: '操作',
      selectDialog: false,
      selectType: '',
      is_default: 0,
      is_disable: 0,
      api_ids: [],
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
      recentActionSummary: '',
      runtimeEnvInfo: resolveAdminRuntimeEnv()
    }
  },
  computed: {
    entrySourceLabel() {
      const source = this.$route?.query?.from
      if (source === 'member-member') return '来自会员列表'
      if (source === 'member-tag') return '来自会员标签'
      if (source === 'member-api') return '来自会员接口'
      if (source === 'member-statistic') return '来自会员统计'
      if (source === 'member-log') return '来自会员日志'
      return ''
    },
    entryContextVisible() {
      return Boolean(this.entrySourceLabel)
    },
    entryContextTitle() {
      if (this.entrySourceLabel === '来自会员列表') return '当前从会员列表进入会员分组'
      if (this.entrySourceLabel === '来自会员标签') return '当前从会员标签进入会员分组'
      if (this.entrySourceLabel === '来自会员接口') return '当前从会员接口进入会员分组'
      if (this.entrySourceLabel === '来自会员统计') return '当前从会员统计进入会员分组'
      if (this.entrySourceLabel === '来自会员日志') return '当前从会员日志进入会员分组'
      return '当前为外部入口承接视角'
    },
    entryContextDesc() {
      if (this.entrySourceLabel === '来自会员列表') {
        return '这类进入通常是为了查某个会员为什么归到当前分组。建议先核默认分组和分组状态，再回会员列表看真实归属。'
      }
      if (this.entrySourceLabel === '来自会员标签') {
        return '这类进入通常是为了把标签策略继续落到分组承接。建议先分清分组目的，再回标签页复核标签与分组边界。'
      }
      if (this.entrySourceLabel === '来自会员接口') {
        return '这类进入通常是为了反查某组会员能力为什么不对。建议先看接口授权，再回接口页核权限树是否同步。'
      }
      if (this.entrySourceLabel === '来自会员统计') {
        return '这类进入通常是为了把统计波动落到具体分组。建议先确认默认分组和禁用分组，再回统计页复盘趋势。'
      }
      return '这类进入通常是为了核异常会员请求来自哪个分组。建议先锁定分组，再回日志页或会员页继续追。'
    },
    entryContextPrimaryLabel() {
      if (this.entrySourceLabel === '来自会员列表') return '回会员列表'
      if (this.entrySourceLabel === '来自会员标签') return '回会员标签'
      if (this.entrySourceLabel === '来自会员接口') return '回会员接口'
      if (this.entrySourceLabel === '来自会员统计') return '回会员统计'
      return '回会员日志'
    },
    runtimeHint() {
      return getAdminRuntimeHint(this.runtimeEnvInfo)
    },
    activeFilterSummary() {
      const items = []
      if (this.query.is_disable !== undefined) {
        items.push(`状态 ${this.query.is_disable === 1 ? '禁用' : '启用'}`)
      }
      if (this.query.search_value) {
        items.push(`检索 ${this.query.search_value}`)
      }
      if (this.query.date_value?.length === 2) {
        items.push(`时间 ${this.query.date_value[0]} 至 ${this.query.date_value[1]}`)
      }
      return items.join(' / ')
    },
    currentPageDisabledCount() {
      return Array.isArray(this.data)
        ? this.data.filter((item) => Number(item.is_disable) === 1).length
        : 0
    },
    currentPageDefaultCount() {
      return Array.isArray(this.data)
        ? this.data.filter((item) => Number(item.is_default) === 1).length
        : 0
    },
    groupFollowupBadgeText() {
      if (this.selection.length > 0) {
        return '可调整授权'
      }
      if (this.currentPageDisabledCount > 0) {
        return '需复核'
      }
      return '分组稳定'
    },
    groupFollowupBadgeClass() {
      if (this.selection.length > 0) {
        return 'is-active'
      }
      if (this.currentPageDisabledCount > 0) {
        return 'is-warning'
      }
      return 'is-safe'
    },
    groupGuideFocusLabel() {
      if (this.selection.length > 0) {
        return `当前重点：先确认这 ${this.selection.length} 个分组是不是要做同一种调整`
      }
      if (this.currentPageDefaultCount > 1) {
        return '当前重点：先核对默认分组是否重复，避免新会员归属异常'
      }
      if (this.currentPageDisabledCount > 0) {
        return '当前重点：先看禁用分组是否还有会员或权限承接'
      }
      return '当前重点：先分清默认分组、会员归属、接口权限'
    },
    groupGuideCards() {
      return [
        {
          step: '第一步',
          title: '先看默认分组是不是正常',
          desc: '新增会员默认会落到这里，所以先确认默认分组数量和目标是否正确。'
        },
        {
          step: '第二步',
          title: '再判断是分组归属问题还是权限问题',
          desc: '会员进错组去处理归属；会员能力不对则优先检查接口授权，不要混着改。'
        },
        {
          step: '第三步',
          title: '最后去会员列表和接口页复核',
          desc: '分组页改完后，最好回会员列表看真实归属，再去接口页确认权限边界有没有同步。'
        }
      ]
    },
    groupFollowupHint() {
      if (this.selection.length > 0) {
        return '当前已选中分组，可继续调整默认分组、接口授权、会员解绑和禁用状态。'
      }
      return '分组页更适合先核对默认分组和接口树权限，再继续处理会员归属和禁用状态。'
    },
    groupFollowupTags() {
      return [
        `默认分组：${this.currentPageDefaultCount} 个`,
        `禁用分组：${this.currentPageDisabledCount} 个`,
        `接口节点：${this.apiIds.length} 个`,
        `已选分组：${this.selection.length} 个`
      ]
    },
    groupFollowupRiskText() {
      if (this.selection.length > 0) {
        return '接口授权、默认分组和会员解绑都属于高影响动作，请先确认当前勾选范围和目标分组一致。'
      }
      if (this.currentPageDefaultCount > 1) {
        return '当前页存在多个默认分组，建议重点核对是否符合预期，避免新增会员归属异常。'
      }
      return '分组接口授权会影响会员能力边界，建议先抽查默认分组和禁用状态，再做批量调整。'
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
      this.query = nextQuery
    },
    setRecentActionSummary(summary) {
      this.recentActionSummary = summary
    },
    handleEntryContextPrimary() {
      if (this.entrySourceLabel === '来自会员列表') {
        this.goToPage('/member/member')
        return
      }
      if (this.entrySourceLabel === '来自会员标签') {
        this.goToPage('/member/tag')
        return
      }
      if (this.entrySourceLabel === '来自会员接口') {
        this.goToPage('/member/api')
        return
      }
      if (this.entrySourceLabel === '来自会员统计') {
        this.goToPage('/member/statistic')
        return
      }
      this.goToPage('/member/log')
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
          this.apiData = res.data.api
          this.apiIds = res.data.api_ids
          this.exps = res.data.exps
          this.setRecentActionSummary(
            `分组列表已刷新，共 ${res.data.count || 0} 项，接口节点 ${
              res.data.api_ids?.length || 0
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
      this.apiCheckAll = false
      this.apiExpandAll = false
      this.apiCheckAllPid = {}
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
        if (selectType === 'removem') {
          this.selectTitle = this.name + '解除会员'
        } else if (selectType === 'editapi') {
          this.selectTitle = this.name + '修改接口'
        } else if (selectType === 'defaults') {
          this.selectTitle = this.name + '是否默认'
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
        if (selectType === 'removem') {
          this.removem(this.selection)
        } else if (selectType === 'editapi') {
          this.editapi(this.selection)
        } else if (selectType === 'defaults') {
          this.defaults(this.selection, true)
        } else if (selectType === 'disable') {
          this.disable(this.selection, true)
        } else if (selectType === 'dele') {
          this.dele(this.selection)
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
          group_id: this.selectGetIds(row),
          member_ids: []
        })
          .then((res) => {
            this.list()
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.loading = false
          })
      }
    },
    // 修改接口
    editapi(row) {
      if (!row.length) {
        this.selectAlert()
      } else {
        this.loading = true
        editapi({
          ids: this.selectGetIds(row),
          api_ids: this.api_ids
        })
          .then((res) => {
            this.list()
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.loading = false
          })
      }
    },
    // 是否默认
    async defaults(row, select = false) {
      if (!row.length) {
        this.selectAlert()
      } else {
        var is_default = row[0].is_default
        if (select) {
          is_default = this.is_default
        }
        if (!select && row.length === 1) {
          const nextLabel = is_default === 1 ? '设为默认分组' : '取消默认分组'
          try {
            await ElMessageBox.confirm(
              `${nextLabel}会直接影响新增会员的默认归属，确认继续吗？`,
              '提示',
              { type: 'warning' }
            )
          } catch (error) {
            row[0].is_default = is_default === 1 ? 0 : 1
            return
          }
        }
        this.loading = true
        defaults({
          ids: this.selectGetIds(row),
          is_default: is_default
        })
          .then((res) => {
            this.list()
            this.setRecentActionSummary(
              `已调整默认分组状态：${is_default === 1 ? '设为默认' : '取消默认'}。`
            )
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.list()
          })
      }
    },
    // 是否禁用
    async disable(row, select = false) {
      if (!row.length) {
        this.selectAlert()
      } else {
        var is_disable = row[0].is_disable
        if (select) {
          is_disable = this.is_disable
        }
        if (!select && row.length === 1) {
          const nextLabel = is_disable === 1 ? '禁用分组' : '启用分组'
          try {
            await ElMessageBox.confirm(
              `${nextLabel}会直接影响会员分组可用性，确认继续吗？`,
              '提示',
              { type: 'warning' }
            )
          } catch (error) {
            row[0].is_disable = is_disable === 1 ? 0 : 1
            return
          }
        }
        this.loading = true
        disable({
          ids: this.selectGetIds(row),
          is_disable: is_disable
        })
          .then((res) => {
            this.list()
            this.setRecentActionSummary(
              `已调整分组状态：${is_disable === 1 ? '禁用' : '启用'}。`
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
            this.setRecentActionSummary('已删除会员分组。')
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.loading = false
          })
      }
    },
    // 接口选择
    apiCheck(refs = 'apiRef') {
      this.selApiRefs = refs
      this.apiCheckSetKeys()
    },
    apiCheckAllChange(refs = 'apiRef') {
      this.selApiRefs = refs
      if (this.apiCheckAll) {
        this.apiIds.forEach((item) => {
          this.$refs[this.selApiRefs].setChecked(item, true, true)
        })
      } else {
        this.$refs[this.selApiRefs].setCheckedKeys([])
      }
      this.apiCheckSetKeys()
    },
    apiExpandAllChange(refs = 'apiRef') {
      this.selApiRefs = refs
      const expanded = this.apiExpandAll
      const length = this.$refs[this.selApiRefs].store._getAllNodes().length
      for (let i = 0; i < length; i++) {
        this.$refs[this.selApiRefs].store._getAllNodes()[i].expanded = expanded
      }
    },
    apiCheckAllChangePid(node, data, refs = 'apiRef') {
      this.selApiRefs = refs
      this.apiCheckAllPid[data.api_id] = node.checked
      this.$refs[this.selApiRefs].setChecked(data.api_id, !this.apiCheckAllPid[data.api_id], true)
      data.children.forEach((item) => {
        this.$refs[this.selApiRefs].setChecked(item.api_id, !this.apiCheckAllPid[data.api_id], true)
      })
      this.apiCheckSetKeys()
    },
    apiCheckSetKeys() {
      if (this.selApiRefs === 'apiRef') {
        this.model.api_ids = this.$refs[this.selApiRefs].getCheckedKeys()
      } else {
        this.api_ids = this.$refs[this.selApiRefs].getCheckedKeys()
      }
    },
    // 分组会员显示
    memberShow(row) {
      this.memberDialog = true
      this.memberDialogTitle = '分组会员：' + row.group_name
      this.memberQuery.group_id = row.group_id
      this.memberQuery.search_value = ''
      this.member()
    },
    // 分组会员列表
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
    // 分组会员排序
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
    // 分组会员操作
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
          this.memberSelectTitle = '解除分组'
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
    // 分组会员解除
    memberRemove(row) {
      if (!row.length) {
        this.memberSelectAlert()
      } else {
        this.memberLoad = true
        memberRemove({
          group_id: this.memberQuery.group_id,
          member_ids: this.memberSelectGetIds(row)
        })
          .then((res) => {
            this.member()
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.memberLoad = false
          })
      }
    },
    goToPage(path, extraQuery = {}) {
      this.$router.push({
        path,
        query: this.buildEntryRouteQuery({
          from: 'member-group',
          ...extraQuery
        })
      })
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
  margin: 16px 0 0;
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

.followup-panel {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 16px;
  padding: 14px 16px;
  border-radius: 14px;
  border: 1px solid #e6ecf5;
  background: #fbfdff;
}

.group-guide-panel {
  margin-bottom: 16px;
  padding: 16px;
  border-radius: 14px;
  border: 1px solid #dbe7f5;
  background: linear-gradient(135deg, #f7fbff 0%, #ffffff 100%);
}

.group-guide-panel__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.group-guide-panel__title {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
}

.group-guide-panel__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.group-guide-panel__badge {
  min-width: 220px;
  padding: 10px 12px;
  border-radius: 12px;
  background: rgba(37, 99, 235, 0.08);
  color: #1d4ed8;
  font-size: 12px;
  font-weight: 700;
  line-height: 1.6;
}

.group-guide-panel__grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-top: 14px;
}

.group-guide-card {
  padding: 14px 16px;
  border: 1px solid #e7edf5;
  border-radius: 14px;
  background: #fff;
}

.group-guide-card__step {
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

.group-guide-card__title {
  margin-top: 10px;
  font-size: 14px;
  font-weight: 700;
  color: #0f172a;
}

.group-guide-card__desc {
  margin-top: 8px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
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

.followup-panel__risk {
  padding: 10px 12px;
  border-radius: 12px;
  border: 1px solid #fed7aa;
  background: #fff7ed;
  color: #9a3412;
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
  background: #f5f7fb;
  color: #4a5670;
  font-size: 12px;
}

.followup-panel__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  justify-content: flex-end;
}

.module-header,
.list-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 16px;
}

.module-header__title,
.list-header__title {
  font-size: 20px;
  font-weight: 700;
  color: #0f172a;
}

.module-header__desc,
.list-header__desc {
  margin-top: 6px;
  font-size: 13px;
  color: #64748b;
  line-height: 1.7;
}

.module-header__meta,
.list-header__meta {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
}

.module-header__badge,
.list-header__meta span,
.summary-bar__item {
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  padding: 0 10px;
  border-radius: 999px;
  background: #eef4ff;
  color: #315efb;
  font-size: 12px;
}

.module-header__badge--sub,
.list-header__meta span {
  background: #f5f7fb;
  color: #4a5670;
}

.summary-bar {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 16px;
  padding: 12px 14px;
  border: 1px solid #e6ecf5;
  border-radius: 14px;
  background: linear-gradient(180deg, #f9fbff 0%, #ffffff 100%);
  box-shadow: 0 6px 18px rgba(15, 35, 95, 0.05);
}

.summary-bar__risk {
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  padding: 0 10px;
  border-radius: 999px;
  background: #fff7ed;
  color: #9a3412;
  font-size: 12px;
}

.filter-form {
  margin-bottom: 0;
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
  .followup-panel,
  .group-guide-panel__header,
  .module-header,
  .list-header {
    flex-direction: column;
  }

  .summary-bar {
    flex-direction: column;
    align-items: stretch;
  }

  .group-guide-panel__badge {
    min-width: 0;
  }

  .group-guide-panel__grid {
    grid-template-columns: 1fr;
  }
}
</style>
