<template>
  <div class="app-container">
    <el-card class="app-head">
      <div class="section-title-row">
        <div>
          <div class="section-title-row__title">会员接口</div>
          <div class="section-title-row__desc">
            统一处理接口树、鉴权例外、分组解除和结构批量维护，默认先给运营一个更直接的首屏。
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
            <el-form-item label="是否免登：" prop="is_unlogin">
              <el-select v-model="query.is_unlogin" @change="search()" clearable>
                <el-option :value="1" label="是" />
                <el-option :value="0" label="否" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="是否免权：" prop="is_unauth">
              <el-select v-model="query.is_unauth" @change="search()" clearable>
                <el-option :value="1" label="是" />
                <el-option :value="0" label="否" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="是否免限：" prop="is_unrate">
              <el-select v-model="query.is_unrate" @change="search()" clearable>
                <el-option :value="1" label="是" />
                <el-option :value="0" label="否" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="上级：" prop="api_pid">
              <el-cascader
                v-model="query.api_pid"
                :options="trees"
                :props="props"
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
                  <el-option value="api_pid" label="上级" />
                  <el-option value="api_name" label="名称" />
                  <el-option value="api_url" label="链接" />
                  <el-option value="is_unlogin" label="免登" />
                  <el-option value="is_unauth" label="免权" />
                  <el-option value="is_unrate" label="免限" />
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
      <div class="member-api-summary-bar">
        <div class="member-api-summary-bar__chips">
          <span class="summary-chip summary-chip--primary">总数 {{ count }}</span>
          <span class="summary-chip">已选 {{ selection.length }} 项</span>
          <span class="summary-chip">免登 {{ unloginCount }}</span>
          <span class="summary-chip" :class="{ 'summary-chip--warning': disabledCount > 0 }">
            禁用 {{ disabledCount }}
          </span>
          <span class="summary-chip">{{ runtimeEnvInfo.dataMode }}</span>
          <span
            v-for="item in activeFilterTags"
            :key="item"
            class="summary-chip"
          >
            {{ item }}
          </span>
          <span v-if="!activeFilterTags.length" class="summary-chip">默认条件：全部会员接口</span>
          <span v-if="recentActionSummary" class="summary-chip summary-chip--muted">
            {{ recentActionSummary }}
          </span>
        </div>
        <div class="member-api-summary-bar__hint" :class="followupBadgeClass">
          <span class="member-api-summary-bar__hint-title">{{ followupBadge }}</span>
          <span class="member-api-summary-bar__hint-text">{{ followupHint }}</span>
        </div>
      </div>
      <div class="member-api-guide">
        <div class="member-api-guide__header">
          <div>
            <div class="member-api-guide__title">如果你是第一次看会员接口，建议先按这个顺序</div>
            <div class="member-api-guide__desc">这页主要管接口树和鉴权例外，不是直接查会员资料。先分清结构，再动免登、免权和免限。</div>
          </div>
          <div class="member-api-guide__badge">{{ apiGuideFocusLabel }}</div>
        </div>
        <div class="member-api-guide__grid">
          <div v-for="item in apiGuideCards" :key="item.title" class="member-api-guide-card">
            <span class="member-api-guide-card__step">{{ item.step }}</span>
            <div class="member-api-guide-card__title">{{ item.title }}</div>
            <div class="member-api-guide-card__desc">{{ item.desc }}</div>
          </div>
        </div>
      </div>
      <div class="followup-panel">
        <div class="followup-panel__header">
          <div>
            <div class="followup-panel__title">改完接口后继续去哪</div>
            <div class="followup-panel__desc">
              把接口树维护、鉴权调整和会员承接页串起来，避免停在当前列表页。
            </div>
          </div>
          <div class="followup-panel__risk">{{ followupRisk }}</div>
        </div>
        <div class="followup-panel__tags">
          <span v-for="item in followupItems" :key="item">{{ item }}</span>
        </div>
        <div class="followup-card-grid">
          <button
            v-for="item in followupActionCards"
            :key="item.title"
            type="button"
            class="followup-card"
            @click="goToPage(item.path, item.query)"
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
        <el-form-item v-if="selectType === 'removeg'">
          <span style="">确定要解除选中的{{ name }}的分组吗？</span>
        </el-form-item>
        <el-form-item v-else-if="selectType === 'editsort'" label="排序">
          <el-input v-model="sort" type="number" placeholder="排序" />
          <el-input v-model="sort_incdec" type="text" placeholder="0">
            <template #append>按{{ name }}ID顺序递增或递减排序</template>
          </el-input>
        </el-form-item>
        <el-form-item v-else-if="selectType === 'editpid'" label="上级">
          <el-cascader
            v-model="api_pid"
            :options="trees"
            :props="props"
            class="w-full"
            placeholder="一级接口"
            clearable
            filterable
          />
        </el-form-item>
        <el-form-item v-else-if="selectType === 'unlogin'" label="是否免登">
          <el-switch v-model="is_unlogin" :active-value="1" :inactive-value="0" />
        </el-form-item>
        <el-form-item v-else-if="selectType === 'unauth'" label="是否免权">
          <el-switch v-model="is_unauth" :active-value="1" :inactive-value="0" />
        </el-form-item>
        <el-form-item v-else-if="selectType === 'unrate'" label="是否免限">
          <el-switch v-model="is_unrate" :active-value="1" :inactive-value="0" />
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
      <template #footer>
        <el-button :loading="loading" @click="selectCancel">取消</el-button>
        <el-button :loading="loading" type="primary" @click="selectSubmit">提交</el-button>
      </template>
    </el-dialog>
    <el-card class="app-main">
      <div class="section-title-row section-title-row--content">
        <div>
          <div class="section-title-row__title">接口列表</div>
          <div class="section-title-row__desc">
            支持接口树维护、鉴权例外控制、分组解除和结构级批量调整。
          </div>
        </div>
        <div class="section-title-row__meta">已选 {{ selection.length }} 项</div>
      </div>
      <div class="operation_btn mb5">
        <!-- 操作 -->
        <div>
          <div class="action-cluster">
            <span class="action-cluster__title">结构视图</span>
            <el-checkbox
              border
              v-model="isExpandAll"
              class="top-[3px]"
              title="收起/展开"
              @change="expandAll"
            >
              收起
            </el-checkbox>
          </div>
          <div class="action-cluster">
            <span class="action-cluster__title">接口维护</span>
            <el-button type="primary" @click="add()">添加</el-button>
            <el-button title="修改排序" @click="selectOpen('editsort')">排序</el-button>
            <el-button title="修改上级" @click="selectOpen('editpid')">上级</el-button>
          </div>
          <div class="action-cluster">
            <span class="action-cluster__title">鉴权控制</span>
            <el-button title="是否禁用" @click="selectOpen('disable')">禁用</el-button>
            <el-button title="是否免登" @click="selectOpen('unlogin')">免登</el-button>
            <el-button title="是否免权" @click="selectOpen('unauth')">免权</el-button>
            <el-button title="是否免限" @click="selectOpen('unrate')">免限</el-button>
          </div>
          <div class="action-cluster">
            <span class="action-cluster__title">分组处理</span>
            <el-button title="解除分组" @click="selectOpen('removeg')">解除分组</el-button>
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
        @select-all="selectAll"
        @cell-dblclick="cellDbclick"
      >
        <el-table-column type="selection" width="42" title="全选/反选" />
        <el-table-column prop="api_name" label="接口名称" min-width="210" show-overflow-tooltip />
        <el-table-column prop="api_url" label="接口链接" min-width="300" show-overflow-tooltip />
        <el-table-column :prop="idkey" label="ID" width="80" />
        <el-table-column prop="is_unlogin" label="免登" min-width="85">
          <template #default="scope">
            <el-switch
              v-if="scope.row.api_url"
              v-model="scope.row.is_unlogin"
              :active-value="1"
              :inactive-value="0"
              @change="confirmRowToggle('unlogin', scope.row, 'is_unlogin', '设为免登', '取消免登')"
            />
            <span v-else></span>
          </template>
        </el-table-column>
        <el-table-column prop="is_unauth" label="免权" min-width="85">
          <template #default="scope">
            <el-switch
              v-if="scope.row.api_url"
              v-model="scope.row.is_unauth"
              :active-value="1"
              :inactive-value="0"
              @change="confirmRowToggle('unauth', scope.row, 'is_unauth', '设为免权', '取消免权')"
            />
            <span v-else></span>
          </template>
        </el-table-column>
        <el-table-column prop="is_unrate" label="免限" min-width="85">
          <template #default="scope">
            <el-switch
              v-if="scope.row.api_url"
              v-model="scope.row.is_unrate"
              :active-value="1"
              :inactive-value="0"
              @change="confirmRowToggle('unrate', scope.row, 'is_unrate', '设为免限', '取消免限')"
            />
            <span v-else></span>
          </template>
        </el-table-column>
        <el-table-column prop="is_disable" label="禁用" min-width="85">
          <template #default="scope">
            <el-switch
              v-if="scope.row.api_url"
              v-model="scope.row.is_disable"
              :active-value="1"
              :inactive-value="0"
              @change="confirmRowToggle('disable', scope.row, 'is_disable', '禁用接口', '启用接口')"
            />
            <span v-else></span>
          </template>
        </el-table-column>
        <el-table-column prop="sort" label="排序" min-width="85" />
        <el-table-column label="操作" width="250">
          <template #default="scope">
            <el-link type="primary" class="mr-1" :underline="false" @click="groupShow(scope.row)">
              分组
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
              @click="goToApiLog(scope.row)"
            >
              日志
            </el-link>
            <el-link
              type="primary"
              class="mr-1"
              :underline="false"
              @click="goToPage('/member/setting', {
                from: 'member-api',
                api_id: scope.row[idkey],
                api_name: scope.row.api_name || ''
              })"
            >
              设置
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
          <el-form-item label="接口上级" prop="api_pid">
            <el-cascader
              v-model="model.api_pid"
              :options="trees"
              :props="props"
              class="w-full"
              placeholder="一级接口"
              clearable
              filterable
            />
          </el-form-item>
          <el-form-item label="接口名称" prop="api_name">
            <el-input v-model="model.api_name" placeholder="请输入接口名称" clearable>
              <template #append>
                <el-link :underline="false" title="复制" @click="copy(model.api_name)">
                  <svg-icon icon-class="copy-document" />
                </el-link>
              </template>
            </el-input>
          </el-form-item>
          <el-form-item label="接口链接" prop="api_url">
            <el-input v-model="model.api_url" placeholder="应用/控制器/操作，区分大小写" clearable>
              <template #append>
                <el-link :underline="false" title="复制" @click="copy(model.api_url)">
                  <svg-icon icon-class="copy-document" />
                </el-link>
              </template>
            </el-input>
          </el-form-item>
          <el-form-item label="排序" prop="sort">
            <el-input v-model="model.sort" type="number" placeholder="250" />
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
    <!-- 接口分组 -->
    <el-dialog
      v-model="groupDialog"
      :title="groupDialogTitle"
      :close-on-click-modal="false"
      :close-on-press-escape="false"
      top="5vh"
      width="70%"
    >
      <!-- 接口分组操作 -->
      <el-row>
        <el-col>
          <el-button type="primary" title="解除" @click="groupSelectOpen('groupRemove')">
            解除
          </el-button>
          <el-input
            v-model="groupQuery.search_value"
            class="ya-search-value"
            placeholder="分组名称"
            clearable
          />
          <el-button type="primary" @click="groupList()">查询</el-button>
        </el-col>
      </el-row>
      <!-- 接口分组列表 -->
      <el-table
        ref="groupRef"
        v-loading="groupLoad"
        :data="groupData"
        :height="height - 70"
        @sort-change="groupSort"
        @selection-change="groupSelect"
      >
        <el-table-column type="selection" width="42" title="全选/反选" />
        <el-table-column :prop="groupPk" label="分组ID" min-width="100" sortable="custom" />
        <el-table-column
          prop="group_name"
          label="分组名称"
          min-width="130"
          sortable="custom"
          show-overflow-tooltip
        />
        <el-table-column prop="group_desc" label="分组描述" min-width="130" show-overflow-tooltip />
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
        <el-table-column label="操作" width="70">
          <template #default="scope">
            <el-link
              type="primary"
              :underline="false"
              @click="groupSelectOpen('groupRemove', scope.row)"
            >
              解除
            </el-link>
          </template>
        </el-table-column>
      </el-table>
      <pagination
        v-show="groupCount > 0"
        v-model:total="groupCount"
        v-model:page="groupQuery.page"
        v-model:limit="groupQuery.limit"
        @pagination="groupList"
      />
    </el-dialog>
    <el-dialog
      v-model="groupSelectDialog"
      :title="groupSelectTitle"
      :close-on-click-modal="false"
      :close-on-press-escape="false"
      top="20vh"
    >
      <el-form ref="groupSelectRef" label-width="120px">
        <el-form-item v-if="groupSelectType === 'groupRemove'" :label="name + 'ID'">
          <span>{{ groupQuery[idkey] }}</span>
        </el-form-item>
        <el-form-item :label="groupName + 'ID'">
          <el-input v-model="groupSelectIds" type="textarea" autosize disabled />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="groupSelectCancel">取消</el-button>
        <el-button type="primary" @click="groupSelectSubmit">提交</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script>
import screenHeight from '@/utils/screen-height'
import Pagination from '@/components/Pagination/index.vue'
import clip from '@/utils/clipboard'
import { arrayColumn } from '@/utils/index'
import { getPageLimit } from '@/utils/settings'
import { getAdminRuntimeHint, resolveAdminRuntimeEnv } from '@/utils/runtime-env'
import {
  list,
  info,
  add,
  edit,
  dele,
  editsort,
  editpid,
  unlogin,
  unauth,
  unrate,
  disable,
  group,
  groupRemove
} from '@/api/member/api'

export default {
  name: 'MemberApi',
  components: { Pagination },
  data() {
    return {
      name: '会员接口',
      height: 680,
      loading: false,
      idkey: 'api_id',
      exps: [{ exp: 'like', name: '包含' }],
      query: {
        search_field: 'api_name',
        search_exp: 'like',
        search_value: '',
        date_field: 'create_time',
        is_disable: undefined,
        is_unlogin: undefined,
        is_unauth: undefined,
        is_unrate: undefined,
        api_pid: undefined
      },
      data: [],
      dialog: false,
      dialogTitle: '',
      model: {
        api_id: '',
        api_pid: 0,
        api_name: '',
        api_url: '',
        sort: 250
      },
      rules: {
        api_name: [{ required: true, message: '请输入接口名称', trigger: 'blur' }]
      },
      trees: [],
      props: { checkStrictly: true, value: 'api_id', label: 'api_name', emitPath: false },
      isExpandAll: false,
      selection: [],
      selectIds: '',
      selectTitle: '操作',
      selectDialog: false,
      selectType: '',
      sort: 250,
      sort_incdec: '0',
      api_pid: 0,
      is_unlogin: 0,
      is_unauth: 0,
      is_unrate: 0,
      is_disable: 0,
      groupPk: 'group_id',
      groupName: '分组',
      groupDialog: false,
      groupDialogTitle: '',
      groupLoad: false,
      groupData: [],
      groupCount: 0,
      groupQuery: {
        page: 1,
        limit: getPageLimit(),
        search_field: 'group_name',
        search_exp: 'like',
        search_value: ''
      },
      groupSelection: [],
      groupSelectIds: '',
      groupSelectTitle: '操作',
      groupSelectDialog: false,
      groupSelectType: '',
      count: 0,
      recentActionSummary: ''
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
      if (source === 'member-setting') return '来自会员设置'
      if (source === 'member-setting-api') return '来自会员设置'
      if (source === 'member-setting-log') return '来自会员设置'
      if (source === 'member-setting-logreg') return '来自会员设置'
      if (source === 'member-setting-member') return '来自会员设置'
      if (source === 'member-setting-third') return '来自会员设置'
      if (source === 'member-log') return '来自会员日志'
      if (source === 'member-group') return '来自会员分组'
      if (source === 'member-statistic') return '来自会员统计'
      return ''
    },
    entryContextVisible() {
      return Boolean(this.entrySourceLabel)
    },
    entryContextTitle() {
      if (this.entrySourceLabel === '来自会员设置') return '当前从会员设置进入会员接口'
      if (this.entrySourceLabel === '来自会员日志') return '当前从会员日志进入会员接口'
      if (this.entrySourceLabel === '来自会员分组') return '当前从会员分组进入会员接口'
      if (this.entrySourceLabel === '来自会员统计') return '当前从会员统计进入会员接口'
      return '当前为外部入口承接视角'
    },
    entryContextDesc() {
      if (this.entrySourceLabel === '来自会员设置') {
        return '这类进入通常是为了把登录、鉴权或接口规则落到具体接口树。建议先看结构和例外配置，再回设置页核全局规则。'
      }
      if (this.entrySourceLabel === '来自会员日志') {
        return '这类进入通常是为了排某个真实请求为什么被放行、被拒绝或没记权。建议先锁定接口，再回日志页看请求链。'
      }
      if (this.entrySourceLabel === '来自会员分组') {
        return '这类进入通常是为了核分组与接口面的承接关系。建议先看接口分组解除和鉴权例外，再回分组页确认运营规则。'
      }
      if (this.entrySourceLabel === '来自会员统计') {
        return '这类进入通常是为了把某类人群问题落到接口面。建议先核结构和免登免权限制，再回统计页看样本范围。'
      }
      return ''
    },
    entryContextPrimaryLabel() {
      if (this.entrySourceLabel === '来自会员设置') return '回会员设置'
      if (this.entrySourceLabel === '来自会员日志') return '回会员日志'
      return '去会员分组复核'
    },
    disabledCount() {
      return this.flattenApiData(this.data).filter((item) => Number(item.is_disable || 0) === 1)
        .length
    },
    unloginCount() {
      return this.flattenApiData(this.data).filter((item) => Number(item.is_unlogin || 0) === 1)
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
      if (this.query.is_unlogin !== undefined) {
        tags.push(`免登：${this.query.is_unlogin === 1 ? '是' : '否'}`)
      }
      if (this.query.is_unauth !== undefined) {
        tags.push(`免权：${this.query.is_unauth === 1 ? '是' : '否'}`)
      }
      if (this.query.is_unrate !== undefined) {
        tags.push(`免限：${this.query.is_unrate === 1 ? '是' : '否'}`)
      }
      if (
        this.query.api_pid !== undefined &&
        this.query.api_pid !== '' &&
        this.query.api_pid !== 0
      ) {
        tags.push(`上级：${this.query.api_pid}`)
      }
      if (this.query.search_value) {
        tags.push(`检索：${this.query.search_field || 'api_name'} = ${this.query.search_value}`)
      }
      return tags
    },
    selectReviewItems() {
      const items = [
        `操作：${this.selectTitle || '批量处理'}`,
        `数量：${this.selection.length} 项`,
        `对象：${this.selectionPreview}`
      ]
      if (this.selectType === 'editpid') {
        items.push(`目标上级：${this.api_pid || '顶级接口'}`)
      } else if (this.selectType === 'editsort') {
        items.push(`排序：${this.sort || 0}`)
      } else if (this.selectType === 'unlogin') {
        items.push(`免登调整：${this.is_unlogin === 1 ? '设为免登' : '取消免登'}`)
      } else if (this.selectType === 'unauth') {
        items.push(`免权调整：${this.is_unauth === 1 ? '设为免权' : '取消免权'}`)
      } else if (this.selectType === 'unrate') {
        items.push(`免限调整：${this.is_unrate === 1 ? '设为免限' : '取消免限'}`)
      } else if (this.selectType === 'disable') {
        items.push(`状态调整：${this.is_disable === 1 ? '批量禁用' : '批量启用'}`)
      }
      return items
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
    submitRiskHint() {
      if (!this.selection.length) {
        return this.runtimeEnvInfo.isProd
          ? '正式环境下接口权限调整会直接影响会员端访问与鉴权，请先勾选需要处理的接口。'
          : '当前环境建议先选择样本接口，再验证树结构与鉴权例外流程。'
      }
      if (this.selectType === 'dele') {
        return '删除接口属于高风险动作，提交前请确认没有被前端、小程序或运营工具依赖。'
      }
      if (['unlogin', 'unauth', 'unrate'].includes(this.selectType)) {
        return '鉴权例外调整会直接影响会员访问控制，请提交前再次核对接口范围和目标状态。'
      }
      return this.runtimeEnvInfo.isProd
        ? `正式环境下本次会直接影响 ${this.selection.length} 项会员接口配置，请先复核结构、分组和鉴权状态。`
        : `当前环境可用于验证 ${this.selection.length} 项会员接口的结构调整和结果回显。`
    },
    followupHint() {
      if (!this.count) {
        return '当前没有会员接口数据，建议先核对接口字典和权限初始化流程。'
      }
      return this.runtimeEnvInfo.isProd
        ? '正式环境建议优先按禁用、免登和免权维度排查，再做批量鉴权调整。'
        : '当前环境适合先验证接口树、分组解除和鉴权例外操作的回显。'
    },
    followupBadge() {
      return this.disabledCount > 0 ? '需巡检' : '状态稳定'
    },
    followupBadgeClass() {
      if (this.selection.length > 0) {
        return 'is-active'
      }
      return this.disabledCount > 0 ? 'is-warning' : 'is-safe'
    },
    apiGuideFocusLabel() {
      if (this.selection.length) {
        return `先确认勾选的 ${this.selection.length} 项是不是同一类接口，再做批量调整`
      }
      if (this.disabledCount > 0) {
        return '先看禁用接口是否还被页面或小程序调用'
      }
      if (this.unloginCount > 0) {
        return '先看免登接口范围是不是只覆盖该放开的节点'
      }
      return '先分清接口树、再动鉴权例外'
    },
    apiGuideCards() {
      return [
        {
          step: '第一步',
          title: '先按状态和上级把接口范围缩小',
          desc: '不要一上来全量批量改，先用禁用、免登、免权和上级筛到一小段接口树。'
        },
        {
          step: '第二步',
          title: '再决定改结构还是改权限例外',
          desc: '上级、排序、分组属于结构问题；免登、免权、免限属于访问策略问题，最好分开处理。'
        },
        {
          step: '第三步',
          title: '改完立刻去日志和会员承接页复核',
          desc: '接口开关本身不说明问题，最终还是要看真实请求日志和会员端承接有没有受影响。'
        }
      ]
    },
    followupItems() {
      return [
        `免登接口：${this.unloginCount} 项`,
        `禁用接口：${this.disabledCount} 项`,
        `当前筛选：${
          this.query.is_disable === undefined
            ? '全部接口'
            : this.query.is_disable === 1
            ? '禁用接口'
            : '启用接口'
        }`,
        `当前选择：${this.selection.length} 项`
      ]
    },
    followupRisk() {
      if (this.disabledCount > 0) {
        return `当前列表中有 ${this.disabledCount} 项禁用接口，建议同步核对是否仍被前端菜单或页面依赖。`
      }
      return this.runtimeEnvInfo.isProd
        ? '正式环境下免登、免权和免限配置都会影响会员请求安全性，建议按分组逐步调整。'
        : '测试环境建议先以单层接口树为样本验证，再扩大到批量结构操作。'
    },
    followupActionCards() {
      const baseQuery = {
        from: 'member-api',
        source_count: this.count || 0
      }
      if (this.selection.length > 0) {
        return [
          {
            title: '去会员日志复核',
            desc: '本页改完后，直接看会员日志最容易确认接口开关和鉴权有没有影响真实请求。',
            path: '/member/log',
            query: {
              ...baseQuery,
              api_ids: this.selectIds,
              search_value: this.selection[0]?.api_url || this.selection[0]?.api_name || ''
            }
          },
          {
            title: '去会员分组承接',
            desc: '涉及分组解除或分组权限时，继续到会员分组页核对承接配置。',
            path: '/member/group',
            query: {
              ...baseQuery,
              api_ids: this.selectIds
            }
          },
          {
            title: '去会员设置收口',
            desc: '需要从规则层补充校验时，直接去会员设置页继续看登录、授权和日志链路。',
            path: '/member/setting',
            query: {
              ...baseQuery,
              api_ids: this.selectIds
            }
          }
        ]
      }
      return [
        {
          title: '去会员日志巡检',
          desc: '先从会员日志看真实请求和异常行为，再回到接口页做更细调整。',
          path: '/member/log',
          query: baseQuery
        },
        {
          title: '去会员分组整理',
          desc: '如果接口树和会员分组有耦合，去分组页继续做运营承接会更顺。',
          path: '/member/group',
          query: baseQuery
        },
        {
          title: '去会员设置核规则',
          desc: '登录、授权或验证码问题通常要联动会员设置页一起看。',
          path: '/member/setting',
          query: baseQuery
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
    this.height = screenHeight(290)
    this.applyRouteQuery()
    this.list()
  },
  methods: {
    buildEntryRouteQuery(extraQuery = {}, nextFrom = 'member-api') {
      return {
        ...this.$route.query,
        ...extraQuery,
        from: nextFrom
      }
    },
    handleEntryContextPrimary() {
      if (this.entrySourceLabel === '来自会员设置') {
        this.goToPage('/member/setting', this.buildEntryRouteQuery({}, 'member-api'))
        return
      }
      if (this.entrySourceLabel === '来自会员日志') {
        this.goToPage('/member/log', this.buildEntryRouteQuery({}, 'member-api'))
        return
      }
      this.goToPage('/member/group', this.buildEntryRouteQuery({}, 'member-api'))
    },
    goToEntryContextBack() {
      if (this.entrySourceLabel === '来自会员设置') {
        this.goToPage('/member/setting', this.buildEntryRouteQuery({}, 'member-api'))
        return
      }
      if (this.entrySourceLabel === '来自会员日志') {
        this.goToPage('/member/log', this.buildEntryRouteQuery({}, 'member-api'))
        return
      }
      if (this.entrySourceLabel === '来自会员分组') {
        this.goToPage('/member/group', this.buildEntryRouteQuery({}, 'member-api'))
        return
      }
      if (this.entrySourceLabel === '来自会员统计') {
        this.goToPage('/member/statistic', this.buildEntryRouteQuery({}, 'member-api'))
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
        ...this.$options.data().query
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
      const isUnlogin = this.parseRouteNumber(routeQuery.is_unlogin)
      if (isUnlogin !== undefined) {
        nextQuery.is_unlogin = isUnlogin
      }
      const isUnauth = this.parseRouteNumber(routeQuery.is_unauth)
      if (isUnauth !== undefined) {
        nextQuery.is_unauth = isUnauth
      }
      const isUnrate = this.parseRouteNumber(routeQuery.is_unrate)
      if (isUnrate !== undefined) {
        nextQuery.is_unrate = isUnrate
      }
      const apiPid = this.parseRouteNumber(routeQuery.api_pid)
      if (apiPid !== undefined) {
        nextQuery.api_pid = apiPid
      }
      const apiId = this.parseRouteNumber(routeQuery.api_id)
      if (apiId !== undefined) {
        nextQuery.search_field = this.idkey
        nextQuery.search_exp = '='
        nextQuery.search_value = String(apiId)
      }
      this.query = nextQuery
    },
    flattenApiData(tree = []) {
      return tree.reduce((acc, item) => {
        acc.push(item)
        if (Array.isArray(item.children) && item.children.length) {
          acc.push(...this.flattenApiData(item.children))
        }
        return acc
      }, [])
    },
    setRecentActionSummary(action, extra = '') {
      this.recentActionSummary = `已执行${action}，影响 ${this.selection.length || 0} 项会员接口${
        extra ? `，${extra}` : ''
      }。`
    },
    goToPage(path, query = {}) {
      this.$router.push({
        path,
        query
      })
    },
    goToApiLog(row) {
      this.goToPage('/member/log', {
        ...this.buildEntryRouteQuery({}, 'member-api'),
        api_id: row[this.idkey],
        api_name: row.api_name || '',
        search_value: row.api_url || row.api_name || ''
      })
    },
    confirmRowToggle(action, row, field, enableLabel, disableLabel) {
      const nextValue = Number(row[field] || 0)
      const previousValue = nextValue === 1 ? 0 : 1
      const actionLabel = nextValue === 1 ? enableLabel : disableLabel
      const itemLabel = row.api_name || row.api_url || row[this.idkey]

      ElMessageBox.confirm(
        `确定要对接口“${itemLabel}”执行“${actionLabel}”吗？`,
        '操作确认',
        {
          type: nextValue === 1 ? 'warning' : 'info',
          confirmButtonText: '确定',
          cancelButtonText: '取消'
        }
      )
        .then(() => {
          if (action === 'unlogin') {
            this.unlogin([row])
          } else if (action === 'unauth') {
            this.unauth([row])
          } else if (action === 'unrate') {
            this.unrate([row])
          } else if (action === 'disable') {
            this.disable([row])
          }
        })
        .catch(() => {
          row[field] = previousValue
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
          this.recentActionSummary = `已加载会员接口 ${res.data.count || 0} 项，免登接口 ${
            this.flattenApiData(res.data.list || []).filter(
              (item) => Number(item.is_unlogin || 0) === 1
            ).length
          } 项。`
          this.loading = false
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
        this.model.api_pid = row[this.idkey]
        this.recentActionSummary = `准备在接口 ${row[this.idkey]} 下添加子接口。`
      } else {
        this.recentActionSummary = '准备新增顶级会员接口。'
      }
    },
    edit(row) {
      this.dialog = true
      this.dialogTitle = this.name + '修改：' + row[this.idkey]
      this.recentActionSummary = `准备修改会员接口 ${row[this.idkey]}。`
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
                this.recentActionSummary = `已修改会员接口 ${this.model[this.idkey]}，名称：${
                  this.model.api_name || '未命名'
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
                this.recentActionSummary = `已新增会员接口，名称：${
                  this.model.api_name || '未命名'
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
      this.query.page = 1
      this.recentActionSummary = `已按 ${this.query.search_field || 'api_name'} 发起接口检索。`
      this.list()
    },
    // 刷新
    refresh() {
      const limit = this.query.limit
      this.query = this.$options.data().query
      this.$refs['table'].clearSort()
      this.query.limit = limit
      this.recentActionSummary = '已重置会员接口筛选条件。'
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
        this.recentActionSummary = `已勾选 ${selection.length} 项会员接口，待处理 ID：${this.selectIds}。`
      }
    },
    selectAll(selection) {
      if (selection) {
        this.selectAllKeys(selection)
        this.selectIds = this.selectGetIds(this.selection).toString()
      } else {
        this.selectIds = ''
      }
    },
    selectAllKeys(tree) {
      for (const i in tree) {
        this.selection.push(tree[i])
        if (tree[i].children) {
          this.selectAllKeys(tree[i].children)
        }
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
        if (selectType === 'removeg') {
          this.selectTitle = this.name + '解除分组'
        } else if (selectType === 'editsort') {
          this.selectTitle = this.name + '修改排序'
        } else if (selectType === 'editpid') {
          this.selectTitle = this.name + '修改上级'
        } else if (selectType === 'unlogin') {
          this.selectTitle = this.name + '是否免登'
        } else if (selectType === 'unauth') {
          this.selectTitle = this.name + '是否免权'
        } else if (selectType === 'unrate') {
          this.selectTitle = this.name + '是否免限'
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
        if (selectType === 'removeg') {
          this.removeg(this.selection)
        } else if (selectType === 'editsort') {
          this.editsort(this.selection)
        } else if (selectType === 'editpid') {
          this.editpid(this.selection)
        } else if (selectType === 'unlogin') {
          this.unlogin(this.selection, true)
        } else if (selectType === 'unauth') {
          this.unauth(this.selection, true)
        } else if (selectType === 'unrate') {
          this.unrate(this.selection, true)
        } else if (selectType === 'disable') {
          this.disable(this.selection, true)
        } else if (selectType === 'dele') {
          this.dele(this.selection)
        }
        this.selectDialog = false
      }
    },
    // 解除分组
    removeg(row) {
      if (!row.length) {
        this.selectAlert()
      } else {
        this.loading = true
        groupRemove({
          api_id: this.selectGetIds(row),
          group_ids: []
        })
          .then((res) => {
            this.list()
            this.setRecentActionSummary('批量解除接口分组')
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.loading = false
          })
      }
    },
    // 修改排序
    editsort(row) {
      this.loading = true
      editsort({
        ids: this.selectGetIds(row),
        sort: this.sort,
        sort_incdec: this.sort_incdec
      })
        .then((res) => {
          this.list()
          this.sort_incdec = '0'
          this.setRecentActionSummary('批量修改接口排序', `起始排序：${this.sort}`)
          ElMessage.success(res.msg)
        })
        .catch(() => {
          this.loading = false
        })
    },
    // 修改上级
    editpid(row) {
      this.loading = true
      editpid({
        ids: this.selectGetIds(row),
        api_pid: this.api_pid
      })
        .then((res) => {
          this.list()
          this.setRecentActionSummary('批量修改接口上级', `目标上级：${this.api_pid || '顶级接口'}`)
          ElMessage.success(res.msg)
        })
        .catch(() => {
          this.loading = false
        })
    },
    // 是否免登
    unlogin(row, select = false) {
      if (!row.length) {
        this.selectAlert()
      } else {
        this.loading = true
        var is_unlogin = row[0].is_unlogin
        if (select) {
          is_unlogin = this.is_unlogin
        }
        unlogin({
          ids: this.selectGetIds(row),
          is_unlogin: is_unlogin
        })
          .then((res) => {
            this.list()
            this.setRecentActionSummary(
              '批量调整免登状态',
              `目标状态：${is_unlogin === 1 ? '设为免登' : '取消免登'}`
            )
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.list()
          })
      }
    },
    // 是否免限
    unrate(row, select = false) {
      if (!row.length) {
        this.selectAlert()
      } else {
        this.loading = true
        var is_unrate = row[0].is_unrate
        if (select) {
          is_unrate = this.is_unrate
        }
        unrate({
          ids: this.selectGetIds(row),
          is_unrate: is_unrate
        })
          .then((res) => {
            this.list()
            this.setRecentActionSummary(
              '批量调整免限状态',
              `目标状态：${is_unrate === 1 ? '设为免限' : '取消免限'}`
            )
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.list()
          })
      }
    },
    // 是否免权
    unauth(row, select = false) {
      if (!row.length) {
        this.selectAlert()
      } else {
        this.loading = true
        var is_unauth = row[0].is_unauth
        if (select) {
          is_unauth = this.is_unauth
        }
        unauth({
          ids: this.selectGetIds(row),
          is_unauth: is_unauth
        })
          .then((res) => {
            this.list()
            this.setRecentActionSummary(
              '批量调整免权状态',
              `目标状态：${is_unauth === 1 ? '设为免权' : '取消免权'}`
            )
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.list()
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
              '批量调整接口状态',
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
            this.setRecentActionSummary('批量删除会员接口')
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.loading = false
          })
      }
    },
    // 分组显示
    groupShow(row) {
      this.groupDialog = true
      this.groupDialogTitle = this.name + this.groupName + '：' + row.api_name
      this.groupQuery.api_id = row.api_id
      this.groupQuery.search_value = ''
      this.groupList()
    },
    // 分组列表
    groupList() {
      this.groupLoad = true
      group(this.groupQuery)
        .then((res) => {
          this.groupData = res.data.list
          this.groupCount = res.data.count
          this.groupLoad = false
        })
        .catch(() => {
          this.groupLoad = false
        })
    },
    // 分组排序
    groupSort(sort) {
      this.groupQuery.sort_field = sort.prop
      this.groupQuery.sort_value = ''
      if (sort.order === 'ascending') {
        this.groupQuery.sort_value = 'asc'
        this.groupList()
      }
      if (sort.order === 'descending') {
        this.groupQuery.sort_value = 'desc'
        this.groupList()
      }
    },
    // 分组操作
    groupSelect(selection) {
      this.groupSelection = selection
      this.groupSelectIds = this.groupSelectGetIds(selection).toString()
    },
    groupSelectGetIds(selection) {
      return arrayColumn(selection, this.groupPk)
    },
    groupSelectAlert() {
      ElMessageBox.alert('请选择需要操作的' + this.groupName, '提示', {
        type: 'warning',
        callback: () => {}
      })
    },
    groupSelectOpen(selectType, selectRow = '') {
      if (selectRow) {
        this.$refs['groupRef'].clearSelection()
        this.$refs['groupRef'].toggleRowSelection(selectRow)
      }
      if (!this.groupSelection.length) {
        this.groupSelectAlert()
      } else {
        this.groupSelectTitle = '操作'
        if (selectType === 'groupRemove') {
          this.groupSelectTitle = this.name + '解除' + this.groupName
        }
        this.groupSelectDialog = true
        this.groupSelectType = selectType
      }
    },
    groupSelectCancel() {
      this.groupSelectDialog = false
    },
    groupSelectSubmit() {
      if (!this.groupSelection.length) {
        this.groupSelectAlert()
      } else {
        const selectType = this.groupSelectType
        if (selectType === 'groupRemove') {
          this.groupRemove(this.groupSelection)
        }
        this.groupSelectDialog = false
      }
    },
    // 分组解除
    groupRemove(row) {
      if (!row.length) {
        this.groupSelectAlert()
      } else {
        this.groupLoad = true
        groupRemove({
          api_id: this.groupQuery.api_id,
          group_ids: this.groupSelectGetIds(row)
        })
          .then((res) => {
            this.groupList()
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.groupLoad = false
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
    }
  }
}
</script>

<style scoped>
.entry-context-banner {
  margin-bottom: 14px;
  padding: 14px 16px;
  border: 1px solid #dbe7f5;
  border-radius: 14px;
  background: linear-gradient(135deg, #f8fbff 0%, #ffffff 100%);
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
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

.member-api-summary-bar,
.member-api-guide,
.followup-panel,
.select-review-panel {
  margin-bottom: 14px;
  padding: 14px 16px;
  border: 1px solid #e6ecf5;
  border-radius: 14px;
  background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
  box-shadow: 0 6px 18px rgba(15, 35, 95, 0.05);
}

.member-api-guide__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.member-api-guide__title {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
}

.member-api-guide__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.member-api-guide__badge {
  min-width: 220px;
  padding: 10px 12px;
  border-radius: 12px;
  background: rgba(59, 130, 246, 0.08);
  color: #1d4ed8;
  font-size: 12px;
  font-weight: 700;
  line-height: 1.6;
}

.member-api-guide__grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-top: 14px;
}

.member-api-guide-card {
  padding: 14px 16px;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  background: #fff;
}

.member-api-guide-card__step {
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

.member-api-guide-card__title {
  margin-top: 10px;
  font-size: 14px;
  font-weight: 700;
  color: #0f172a;
}

.member-api-guide-card__desc {
  margin-top: 8px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.member-api-summary-bar,
.followup-panel__header,
.section-title-row {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.select-review-panel__title,
.section-title-row__title {
  font-size: 12px;
  font-weight: 700;
  color: #475569;
}

.select-review-panel__hint,
.section-title-row__desc,
.section-title-row__meta {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.section-title-row__meta {
  font-weight: 600;
  white-space: nowrap;
}

.member-api-summary-bar__chips,
.followup-panel__tags,
.select-review-panel__tags {
  display: flex;
  flex: 1;
  flex-wrap: wrap;
  gap: 10px;
}

.member-api-summary-bar__chips {
  align-items: center;
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
  margin-top: 12px;
  gap: 10px;
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
  border: 1px solid #dbe7f6;
  border-radius: 14px;
  background: #ffffff;
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

.summary-chip {
  display: inline-flex;
  align-items: center;
  min-height: 30px;
  padding: 0 10px;
  border-radius: 999px;
  border: 1px solid #dbe7f6;
  background: #f8fbff;
  color: #375078;
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

.member-api-summary-bar__hint {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 240px;
  padding: 10px 12px;
  border-radius: 12px;
  border: 1px solid transparent;
}

.member-api-summary-bar__hint.is-safe {
  background: #eaf8ef;
  color: #15803d;
}

.member-api-summary-bar__hint.is-warning {
  background: #fff5e8;
  color: #b45309;
}

.member-api-summary-bar__hint.is-active {
  background: #e8f0ff;
  color: #1d4ed8;
}

.member-api-summary-bar__hint-title {
  font-size: 12px;
  font-weight: 700;
}

.member-api-summary-bar__hint-text {
  font-size: 12px;
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
  .member-api-summary-bar,
  .member-api-guide__header,
  .followup-panel__header,
  .section-title-row {
    flex-direction: column;
  }

  .section-title-row__meta {
    white-space: normal;
  }

  .member-api-guide__badge {
    min-width: 0;
  }

  .member-api-guide__grid,
  .followup-card-grid {
    grid-template-columns: 1fr;
  }
}
</style>
