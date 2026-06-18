<template>
  <div class="app-container">
    <el-card class="app-head">
      <div class="section-title-row">
        <div>
          <div class="section-title-row__title">会员管理</div>
          <div class="section-title-row__desc">统一处理会员筛选、分组、标签、禁用、重置密码和导出。</div>
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
            <el-form-item label="是否超会：" prop="is_super">
              <el-select v-model="query.is_super" @change="search()" clearable>
                <el-option :value="1" label="是" />
                <el-option :value="0" label="否" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="标签：" prop="tag_ids">
              <el-select v-model="query.tag_ids" clearable filterable multiple collapse-tags>
                <el-option
                  v-for="item in tagData"
                  :key="item.tag_id"
                  :label="item.tag_name"
                  :value="item.tag_id"
                />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="分组：" prop="group_ids">
              <el-select v-model="query.group_ids" clearable filterable multiple collapse-tags>
                <el-option
                  v-for="item in groupData"
                  :key="item.group_id"
                  :label="item.group_name"
                  :value="item.group_id"
                />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="性别：" prop="gender">
              <el-select v-model="query.gender" clearable>
                <el-option
                  v-for="(item, index) in genders"
                  :key="index"
                  :label="item"
                  :value="index"
                />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="所在地：" prop="region_id">
              <el-cascader
                v-model="query.region_id"
                :options="regionData"
                :props="regionQueryProps"
                clearable
                filterable
                collapse-tags
                style="width: 100%"
              />
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="注册平台：" prop="platform">
              <el-select v-model="query.platform" clearable>
                <el-option
                  v-for="(item, index) in platforms"
                  :key="index"
                  :label="item"
                  :value="index"
                />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="注册应用：" prop="application">
              <el-select v-model="query.application" clearable>
                <el-option
                  v-for="(item, index) in applications"
                  :key="index"
                  :label="item"
                  :value="index"
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
                  <el-option value="nickname" label="昵称" />
                  <el-option value="username" label="用户名" />
                  <el-option value="phone" label="手机" />
                  <el-option value="email" label="邮箱" />
                  <el-option value="remark" label="备注" />
                  <el-option value="name" label="姓名" />
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
      <div class="member-summary-bar">
        <div class="member-summary-bar__chips">
          <span class="summary-chip summary-chip--primary">总数 {{ count }}</span>
          <span class="summary-chip">已选 {{ selection.length }} 人</span>
          <span class="summary-chip">当前页禁用 {{ currentPageDisabledCount }} 人</span>
          <span class="summary-chip">当前页超会 {{ currentPageSuperCount }} 人</span>
          <span v-for="item in activeFilterTags" :key="item" class="summary-chip">{{ item }}</span>
          <span v-if="!activeFilterTags.length" class="summary-chip">默认条件：全部会员</span>
          <span v-if="recentActionSummary" class="summary-chip summary-chip--muted">{{ recentActionSummary }}</span>
        </div>
        <div class="member-summary-bar__hint" :class="memberFollowupBadgeClass">
          <span class="member-summary-bar__hint-title">{{ memberFollowupBadgeText }}</span>
          <span class="member-summary-bar__hint-text">{{ memberFollowupHint }}</span>
        </div>
      </div>
      <div class="member-guide-panel">
        <div class="member-guide-panel__header">
          <div>
            <div class="member-guide-panel__title">会员管理第一次进来，建议先这样看</div>
            <div class="member-guide-panel__desc">
              先判断你是在找某个人、找某类人，还是准备批量治理。先缩小范围，再决定去改分组、打标签、禁用账号，还是回统计和日志继续查。
            </div>
          </div>
          <div class="member-guide-panel__badge">{{ memberGuideFocusLabel }}</div>
        </div>
        <div class="member-guide-panel__grid">
          <div v-for="item in memberGuideCards" :key="item.title" class="member-guide-card">
            <span class="member-guide-card__step">{{ item.step }}</span>
            <div class="member-guide-card__title">{{ item.title }}</div>
            <div class="member-guide-card__desc">{{ item.desc }}</div>
          </div>
        </div>
      </div>
      <div class="followup-panel">
        <div class="followup-panel__main">
          <div class="followup-panel__title">看完会员后继续去哪</div>
          <div class="followup-panel__desc">{{ memberFollowupHint }}</div>
          <div class="followup-panel__tags">
            <span v-for="item in memberFollowupTags" :key="item">{{ item }}</span>
          </div>
          <div class="followup-panel__risk">{{ memberFollowupRiskText }}</div>
        </div>
        <div class="followup-panel__actions">
          <el-button type="primary" @click="goToPage('/member/group')">去会员分组</el-button>
          <el-button @click="goToPage('/member/tag')">去会员标签</el-button>
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
      <el-scrollbar native :height="height - 200">
        <el-form label-width="120px">
          <div v-if="selection.length" class="select-review-panel">
            <div class="select-review-panel__title">提交前复核</div>
            <div class="select-review-panel__tags">
              <span v-for="item in selectReviewItems" :key="item">{{ item }}</span>
            </div>
            <div class="select-review-panel__hint">{{ selectRiskHint }}</div>
          </div>
          <el-form-item v-if="selectType === 'region'" label="所在地">
            <el-cascader
              v-model="region_id"
              :options="regionData"
              :props="regionProps"
              class="w-full"
              clearable
              filterable
            />
          </el-form-item>
          <el-form-item v-else-if="selectType === 'edittag'" label="标签">
            <el-select v-model="tag_ids" class="w-full" clearable filterable multiple>
              <el-option
                v-for="item in tagData"
                :key="item.tag_id"
                :label="item.tag_name"
                :value="item.tag_id"
              />
            </el-select>
          </el-form-item>
          <el-form-item v-else-if="selectType === 'editgroup'" label="分组">
            <el-select v-model="group_ids" class="w-full" clearable filterable multiple>
              <el-option
                v-for="item in groupData"
                :key="item.group_id"
                :label="item.group_name"
                :value="item.group_id"
              />
            </el-select>
          </el-form-item>
          <el-form-item v-else-if="selectType === 'super'" label="是否超会">
            <!--            <el-switch v-model="is_super" :active-value="1" :inactive-value="0" />-->
            <el-switch
              v-model="is_super"
              class="ml-2"
              inline-prompt
              active-text="是"
              inactive-text="否"
              :active-value="1"
              :inactive-value="0"
            />
            <el-text v-if="is_super" size="default"> 超级会员，拥有所有权限。</el-text>
          </el-form-item>
          <el-form-item v-else-if="selectType === 'disable'" label="是否禁用">
            <!--            <el-switch v-model="is_disable" :active-value="1" :inactive-value="0" />-->
            <el-switch
              v-model="is_disable"
              class="ml-2"
              inline-prompt
              active-text="是"
              inactive-text="否"
              :active-value="1"
              :inactive-value="0"
            />
            <el-text size="default" v-if="is_disable" type="danger"> 禁用后无法登录！</el-text>
          </el-form-item>
          <el-form-item v-else-if="selectType === 'repwd'" label="新密码">
            <el-input v-model="password" placeholder="请输入新密码" clearable />
          </el-form-item>
          <el-form-item v-else-if="selectType === 'dele'" label="删除？">
            <el-text size="default" type="danger">确定要删除选中的{{ name }}吗？</el-text>
          </el-form-item>
          <div v-else-if="selectType === 'export'">
            <el-form-item label="文件名称">
              <el-input v-model="exportFileName" placeholder="请输入文件名称" clearable />
            </el-form-item>
            <el-form-item label="文件类型">
              <el-select v-model="exportBookType">
                <el-option
                  v-for="item in ['xlsx', 'csv', 'txt']"
                  :key="item"
                  :label="item"
                  :value="item"
                />
              </el-select>
            </el-form-item>
            <el-form-item label="自动宽度">
              <el-switch v-model="exportAutoWidth" :active-value="true" :inactive-value="false" />
              <span> 宽度是否自适应</span>
            </el-form-item>
          </div>
          <el-form-item :label="name + 'ID'">
            <el-input v-model="selectIds" type="textarea" autosize disable />
          </el-form-item>
        </el-form>
      </el-scrollbar>
      <template #footer>
        <el-button :loading="loading" @click="selectCancel">取消</el-button>
        <el-button :loading="loading" type="primary" @click="selectSubmit">提交</el-button>
      </template>
    </el-dialog>
    <el-card class="app-main">
      <div class="section-title-row section-title-row--content">
        <div>
          <div class="section-title-row__title">会员列表</div>
          <div class="section-title-row__desc">支持批量分组、标签、禁用、密码重置和导出导入。</div>
        </div>
        <div class="section-title-row__meta">已选 {{ selection.length }} 项</div>
      </div>
      <div class="operation_btn mb5">
        <!-- 操作 -->
        <div>
          <div class="action-cluster">
            <span class="action-cluster__title">新增维护</span>
            <el-button type="primary" @click="add()">添加</el-button>
            <el-button title="修改标签" @click="selectOpen('edittag')">标签</el-button>
            <el-button title="修改分组" @click="selectOpen('editgroup')">分组</el-button>
            <el-button title="修改所在地" @click="selectOpen('region')">所在地</el-button>
          </div>
          <div class="action-cluster">
            <span class="action-cluster__title">批量处理</span>
            <el-button title="是否禁用" @click="selectOpen('disable')">禁用</el-button>
            <el-button title="删除" @click="selectOpen('dele')">删除</el-button>
            <el-button title="重置密码" @click="selectOpen('repwd')">密码</el-button>
            <el-button title="是否超会" @click="selectOpen('super')">超会</el-button>
          </div>
          <div class="action-cluster">
            <span class="action-cluster__title">数据流转</span>
            <el-button title="导出" @click="selectOpen('export')">导出</el-button>
            <el-tooltip
              content="表头：昵称，用户名，手机，邮箱，密码"
              effect="dark"
              placement="left"
            >
              <excel-import
                v-if="checkPermission(['admin/member.Member/export'])"
                title="导入"
                @on-import="imports"
              />
            </el-tooltip>
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
        size="small"
        class="member-table"
        @sort-change="sort"
        @selection-change="select"
        @cell-dblclick="cellDbclick"
      >
        <el-table-column type="selection" width="42" title="全选/反选" />
        <el-table-column :prop="idkey" label="ID" width="80" sortable="custom" />
        <el-table-column prop="avatar_id" label="头像" min-width="62">
          <template #default="scope">
            <FileImage :file-url="scope.row.avatar_url" avatar lazy />
          </template>
        </el-table-column>
        <el-table-column
          prop="nickname"
          label="昵称"
          min-width="170"
          sortable="custom"
          show-overflow-tooltip
        />
        <el-table-column
          prop="username"
          label="用户名"
          min-width="170"
          sortable="custom"
          show-overflow-tooltip
        />
        <el-table-column
          prop="phone"
          label="手机"
          min-width="112"
          sortable="custom"
          show-overflow-tooltip
        />
        <el-table-column
          prop="email"
          label="邮箱"
          min-width="200"
          sortable="custom"
          show-overflow-tooltip
        />
        <el-table-column prop="tag_names" label="标签" min-width="130" show-overflow-tooltip />
        <el-table-column prop="group_names" label="分组" min-width="135" show-overflow-tooltip />
        <el-table-column prop="is_super" label="超会" min-width="85" sortable="custom">
          <template #default="scope">
            <el-switch
              :model-value="scope.row.is_super"
              class="ml-2"
              inline-prompt
              active-text="是"
              inactive-text="否"
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
              inline-prompt
              active-text="是"
              inactive-text="否"
              :active-value="1"
              :inactive-value="0"
              @change="handleDisableSwitch(scope.row, $event)"
            />
          </template>
        </el-table-column>
        <el-table-column prop="sort" label="排序" width="85" sortable="custom" />
        <el-table-column prop="create_time" label="注册时间" width="165" sortable="custom" />
        <el-table-column label="操作" width="95">
          <template #default="scope">
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
      <el-form ref="ref" :model="model" :rules="rules" label-width="100px">
        <el-tabs>
          <el-tab-pane label="基础信息">
            <el-scrollbar native :max-height="height - 30">
              <el-form-item label="头像" prop="avatar_id">
                <FileImage
                  v-model="model.avatar_id"
                  :file-url="model.avatar_url"
                  file-title="上传头像"
                  file-tip="图片小于200KB，jpg、png格式，宽高 1:1。"
                  :height="100"
                  avatar
                  upload
                />
              </el-form-item>
              <el-form-item label="昵称" prop="nickname">
                <el-input
                  key="nickname"
                  v-model="model.nickname"
                  placeholder="请输入昵称"
                  clearable
                />
              </el-form-item>
              <el-form-item label="用户名" prop="username">
                <el-input
                  key="username"
                  v-model="model.username"
                  placeholder="请输入用户名"
                  clearable
                />
              </el-form-item>
              <el-form-item v-if="model.member_id == ''" label="密码" prop="password">
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
              <el-form-item label="姓名" prop="name">
                <el-input v-model="model.name" clearable />
              </el-form-item>
              <el-form-item label="性别" prop="gender">
                <el-select v-model="model.gender">
                  <el-option
                    v-for="(item, index) in genders"
                    :key="index"
                    :label="item"
                    :value="index"
                  />
                </el-select>
              </el-form-item>
              <el-form-item label="所在地" prop="region_id">
                <el-cascader
                  v-model="model.region_id"
                  class="w-full"
                  :options="regionData"
                  :props="regionProps"
                  clearable
                />
              </el-form-item>
              <el-form-item label="排序" prop="sort">
                <el-input v-model="model.sort" type="number" />
              </el-form-item>
              <el-form-item label="备注" prop="remark">
                <el-input v-model="model.remark" clearable />
              </el-form-item>
            </el-scrollbar>
          </el-tab-pane>
          <el-tab-pane label="权限信息">
            <el-scrollbar native :max-height="height - 30">
              <el-form-item label="超会" prop="is_super">
                <el-switch
                  v-model="model.is_super"
                  class="ml-2"
                  inline-prompt
                  active-text="是"
                  inactive-text="否"
                  :active-value="1"
                  :inactive-value="0"
                  disabled
                />
              </el-form-item>
              <el-form-item label="禁用" prop="is_disable">
                <el-switch
                  v-model="model.is_disable"
                  class="ml-2"
                  inline-prompt
                  active-text="是"
                  inactive-text="否"
                  :active-value="1"
                  :inactive-value="0"
                  disabled
                />
              </el-form-item>
              <el-form-item label="标签" prop="tag_ids">
                <el-select v-model="model.tag_ids" class="w-full" multiple clearable filterable>
                  <el-option
                    v-for="item in tagData"
                    :key="item.tag_id"
                    :label="item.tag_name"
                    :value="item.tag_id"
                  />
                </el-select>
              </el-form-item>
              <el-form-item label="分组(角色)" prop="group_ids">
                <el-select v-model="model.group_ids" class="w-full" clearable filterable multiple>
                  <el-option
                    v-for="item in groupData"
                    :key="item.group_id"
                    :label="item.group_name"
                    :value="item.group_id"
                  />
                </el-select>
              </el-form-item>
              <el-form-item v-if="model[idkey]" label="接口(权限)" prop="api_ids">
                <el-col :span="24">
                  <el-checkbox v-model="apiExpandAll" @change="apiExpandAllChange">
                    展开
                  </el-checkbox>
                </el-col>
                <el-tree
                  ref="apiRef"
                  :data="model.api_tree"
                  :props="apiProps"
                  :default-checked-keys="model.api_ids"
                  :expand-on-click-node="true"
                  :default-expand-all="false"
                  :check-strictly="true"
                  node-key="api_id"
                  highlight-current
                >
                  <template #default="scope">
                    <span class="custom-tree-node">
                      <span>
                        {{ scope.node.label }}
                        <i v-if="scope.data.is_check" style="color: #1890ff" title="已分配">
                          <svg-icon icon-class="check" />
                        </i>
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
                        <i v-if="scope.data.is_group" style="margin-left: 10px" title="分组">
                          <svg-icon icon-class="s-custom" />
                        </i>
                        <i v-else style="margin-left: 10px; color: #fff">
                          <svg-icon icon-class="s-custom" />
                        </i>
                        <i v-if="scope.data.is_unauth" style="margin-left: 10px" title="免权">
                          <svg-icon icon-class="unlock" />
                        </i>
                        <i v-else style="margin-left: 10px; color: #fff">
                          <svg-icon icon-class="unlock" />
                        </i>
                        <i v-if="scope.data.is_unlogin" style="margin-left: 10px" title="免登">
                          <svg-icon icon-class="user" />
                        </i>
                        <i v-else style="margin-left: 10px; color: #fff">
                          <svg-icon icon-class="user" />
                        </i>
                      </span>
                    </span>
                  </template>
                </el-tree>
              </el-form-item>
            </el-scrollbar>
          </el-tab-pane>
          <el-tab-pane label="登录注册">
            <el-scrollbar native :max-height="height - 30">
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
              <el-form-item v-if="model[idkey]" label="注册平台" prop="platform_name">
                <el-input v-model="model.platform_name" disabled />
              </el-form-item>
              <el-form-item v-if="model[idkey]" label="注册应用" prop="application_name">
                <el-input v-model="model.application_name" disabled />
              </el-form-item>
              <el-form-item v-if="model[idkey]" label="注册时间" prop="create_time">
                <el-input v-model="model.create_time" disabled />
              </el-form-item>
              <el-form-item v-if="model[idkey]" label="修改时间" prop="update_time">
                <el-input v-model="model.update_time" disabled />
              </el-form-item>
              <el-form-item v-if="model[idkey]" label="退出时间" prop="logout_time">
                <el-input v-model="model.logout_time" disabled />
              </el-form-item>
              <el-form-item v-if="model.delete_time" label="删除时间" prop="delete_time">
                <el-input v-model="model.delete_time" disabled />
              </el-form-item>
            </el-scrollbar>
          </el-tab-pane>
          <el-tab-pane label="第三方账号">
            <el-scrollbar native :max-height="height - 30">
              <el-table v-if="model[idkey]" :data="model.thirds">
                <el-table-column prop="third_id" label="ID" width="80" />
                <el-table-column prop="headimgurl" label="头像" min-width="62">
                  <template #default="scope">
                    <FileImage :file-url="scope.row.headimgurl" avatar lazy />
                  </template>
                </el-table-column>
                <el-table-column
                  prop="nickname"
                  label="昵称"
                  min-width="100"
                  show-overflow-tooltip
                />
                <el-table-column
                  prop="platform_name"
                  label="平台"
                  min-width="80"
                  show-overflow-tooltip
                />
                <el-table-column
                  prop="application_name"
                  label="应用"
                  min-width="110"
                  show-overflow-tooltip
                />
                <el-table-column prop="is_disable" label="禁用" min-width="85">
                  <template #default="scope">
                    <el-switch
                      :model-value="scope.row.is_disable"
                      :loading="thirdUnbindLoad"
                      :active-value="1"
                      :inactive-value="0"
                      @change="handleThirdDisable(scope.row, $event)"
                    />
                  </template>
                </el-table-column>
                <el-table-column prop="create_time" label="添加/绑定时间" min-width="165" />
                <el-table-column prop="login_time" label="登录时间" width="165" />
                <el-table-column label="操作" width="70">
                  <template #default="scope">
                    <el-link type="primary" :underline="false" @click="thirdUnbindBtn(scope.row)">
                      解绑
                    </el-link>
                  </template>
                </el-table-column>
              </el-table>
            </el-scrollbar>
          </el-tab-pane>
        </el-tabs>
      </el-form>
      <template #footer>
        <el-button :loading="loading" @click="cancel">取消</el-button>
        <el-button :loading="loading" type="primary" @click="submit">提交</el-button>
      </template>
    </el-dialog>
    <!-- 第三方账号解绑 -->
    <el-dialog v-model="thirdUnbindDialog" title="会员第三方账号解绑" width="25%" top="20vh">
      <el-text size="default" type="danger">
        解绑后该会员无法再通过该第三方账号登录，确定要解绑吗？
      </el-text>
      <template #footer>
        <el-button :loading="thirdUnbindLoad" @click="thirdUnbindDialog = false">取消</el-button>
        <el-button :loading="thirdUnbindLoad" type="primary" @click="thirdUnbindSubmit">
          确定
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script>
import checkPermission from '@/utils/permission'
import screenHeight from '@/utils/screen-height'
import Pagination from '@/components/Pagination/index.vue'
import ExcelImport from '@/components/ExcelImport/index.vue'
import clip from '@/utils/clipboard'
import { arrayColumn } from '@/utils/index'
import { getPageLimit } from '@/utils/settings'
import {
  list,
  info,
  add,
  edit,
  dele,
  region,
  edittag,
  editgroup,
  repwd,
  issuper,
  disable,
  imports,
  isbalance as edit_balance,
  iswarehouse as edit_warehouse
} from '@/api/member/member'
import { select as callSelect } from '@/api/setting/call.js'
import { select as warehouseSelect } from '@/api/setting/warehouse.js'
import { getAdminRuntimeHint, resolveAdminRuntimeEnv } from '@/utils/runtime-env'
export default {
  name: 'Member',
  components: { Pagination, ExcelImport },
  data() {
    return {
      name: '会员',
      height: 680,
      loading: false,
      idkey: 'member_id',
      exps: [{ exp: 'like', name: '包含' }],
      query: {
        page: 1,
        limit: getPageLimit(),
        search_field: 'nickname',
        search_exp: 'like',
        search_value: '',
        date_field: 'create_time',
        is_disable: undefined,
        is_super: undefined,
        tag_ids: undefined,
        group_ids: undefined,
        gender: undefined,
        region_id: undefined,
        platform: undefined,
        application: undefined
      },
      data: [],
      count: 0,
      dialog: false,
      dialogTitle: '',
      model: {
        member_id: '',
        avatar_id: 0,
        avatar_url: '',
        tag_ids: [],
        group_ids: [],
        nickname: '',
        username: '',
        password: '',
        phone: '',
        email: '',
        name: '',
        gender: 0,
        region_id: '',
        remark: '',
        sort: 250,
        api_ids: [],
        thirds: [],
        is_balance: 0,
        setting_call_ids: [],
        is_warehouse: 0,
        setting_warehouse_ids: []
      },
      rules: {
        nickname: [{ required: true, message: '请输入昵称', trigger: 'blur' }],
        username: [{ required: true, message: '请输入用户名', trigger: 'blur' }],
        password: [{ required: true, message: '请输入密码', trigger: 'blur' }]
      },
      genders: [],
      platforms: [],
      applications: [],
      regionData: [],
      regionProps: {
        checkStrictly: true,
        value: 'region_id',
        label: 'region_name',
        emitPath: false
      },
      regionQueryProps: {
        checkStrictly: true,
        value: 'region_id',
        label: 'region_name',
        multiple: true,
        emitPath: false
      },
      tagData: [],
      groupData: [],
      selection: [],
      selectIds: '',
      selectTitle: '操作',
      selectDialog: false,
      selectType: '',
      region_id: 0,
      tag_ids: [],
      group_ids: [],
      password: '',
      is_super: 0,
      is_disable: 0,
      exportFileName: '',
      exportBookType: 'xlsx',
      exportAutoWidth: false,
      apiProps: { label: 'api_name', children: 'children' },
      apiCheckAll: false,
      apiExpandAll: false,
      thirdUnbindDialog: false,
      thirdUnbindModel: {},
      thirdUnbindLoad: false,
      call_list: [],
      warehouse_list: [],
      props: { checkStrictly: false, value: 'id', label: 'title', emitPath: false, multiple: true },
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
      if (source === 'member-statistic') return '来自会员统计'
      if (source === 'member-tag') return '来自会员标签'
      if (source === 'member-group') return '来自会员分组'
      if (source === 'member-log') return '来自会员日志'
      if (source === 'member-third') return '来自第三方账号'
      if (source === 'setting-feedback') return '来自意见反馈'
      if (source === 'system-notice') return '来自系统公告'
      if (source === 'internal-takeover') return '来自内部接盘对账'
      if (source === 'member-setting-member') return '来自会员设置'
      if (source === 'member-setting-log') return '来自会员设置'
      if (source === 'member-setting-third') return '来自会员设置'
      if (source === 'member-setting-api') return '来自会员设置'
      if (source === 'member-setting-logreg') return '来自会员设置'
      if (source === 'member-setting') return '来自会员设置'
      return ''
    },
    entryContextVisible() {
      return Boolean(this.entrySourceLabel)
    },
    entryContextTitle() {
      if (this.entrySourceLabel === '来自会员统计') return '当前从会员统计进入会员管理'
      if (this.entrySourceLabel === '来自会员标签') return '当前从会员标签进入会员管理'
      if (this.entrySourceLabel === '来自会员分组') return '当前从会员分组进入会员管理'
      if (this.entrySourceLabel === '来自会员日志') return '当前从会员日志进入会员管理'
      if (this.entrySourceLabel === '来自第三方账号') return '当前从第三方账号进入会员管理'
      if (this.entrySourceLabel === '来自意见反馈') return '当前从意见反馈进入会员管理'
      if (this.entrySourceLabel === '来自系统公告') return '当前从系统公告进入会员管理'
      if (this.entrySourceLabel === '来自内部接盘对账') return '当前从内部接盘对账进入会员管理'
      if (this.entrySourceLabel === '来自会员设置') return '当前从会员设置进入会员管理'
      return '当前为外部入口承接视角'
    },
    entryContextDesc() {
      if (this.entrySourceLabel === '来自会员统计') {
        return '这类进入通常是为了把人群趋势落到真人列表。建议先抽查样本，再决定回统计页继续下钻，还是直接做分组、标签和禁用治理。'
      }
      if (this.entrySourceLabel === '来自会员标签') {
        return '这类进入通常是为了看某个标签下到底有哪些人。建议先核当前人群，再回标签页整理规则或继续批量打标。'
      }
      if (this.entrySourceLabel === '来自会员分组') {
        return '这类进入通常是为了看某个分组的真实成员。建议先确认这批会员是否仍符合当前分组，再决定是否批量迁移。'
      }
      if (this.entrySourceLabel === '来自会员日志') {
        return '这类进入通常是为了排登录、访问或行为异常。建议先确认会员当前状态与身份，再回日志页核行为链。'
      }
      if (this.entrySourceLabel === '来自第三方账号') {
        return '这类进入通常是为了确认某个第三方账号到底绑到了谁。建议先核会员身份，再回第三方账号页继续处理绑定或禁用。'
      }
      if (this.entrySourceLabel === '来自意见反馈') {
        return '这类进入通常是为了把反馈问题落到具体会员。建议先核对会员身份和状态，再回反馈页继续排查。'
      }
      if (this.entrySourceLabel === '来自系统公告') {
        return '这类进入通常是为了核对公告触达的人群是否准确。建议先确认筛选条件和名单，再回公告页继续处理。'
      }
      if (this.entrySourceLabel === '来自内部接盘对账') {
        return '这类进入通常是为了顺着订单找到具体买家。建议先核对手机号、昵称和会员状态，再回内部接盘页继续看订单流转。'
      }
      if (this.entrySourceLabel === '来自会员设置') {
        return '这类进入通常是为了核配置落到具体会员后的实际效果。建议先看会员状态和命中结果，再回设置页继续调整规则。'
      }
      return ''
    },
    entryContextPrimaryLabel() {
      if (this.entrySourceLabel === '来自会员标签') return '回会员标签'
      if (this.entrySourceLabel === '来自会员分组') return '回会员分组'
      if (this.entrySourceLabel === '来自会员日志') return '回会员日志'
      if (this.entrySourceLabel === '来自第三方账号') return '回第三方账号'
      if (this.entrySourceLabel === '来自意见反馈') return '回意见反馈'
      if (this.entrySourceLabel === '来自系统公告') return '回系统公告'
      if (this.entrySourceLabel === '来自内部接盘对账') return '回内部接盘对账'
      if (this.entrySourceLabel === '来自会员设置') return '回会员设置'
      return '去会员统计复核'
    },
    currentPageDisabledCount() {
      return Array.isArray(this.data)
        ? this.data.filter((item) => Number(item.is_disable) === 1).length
        : 0
    },
    currentPageSuperCount() {
      return Array.isArray(this.data)
        ? this.data.filter((item) => Number(item.is_super) === 1).length
        : 0
    },
    activeFilterTags() {
      const tags = []
      if (this.query.date_value?.length === 2) {
        tags.push(`注册时间：${this.query.date_value[0]} 至 ${this.query.date_value[1]}`)
      }
      if (this.query.is_disable !== undefined) {
        tags.push(`状态：${this.query.is_disable === 1 ? '禁用' : '启用'}`)
      }
      if (this.query.is_super !== undefined) {
        tags.push(`超会：${this.query.is_super === 1 ? '是' : '否'}`)
      }
      if (Array.isArray(this.query.tag_ids) && this.query.tag_ids.length) {
        tags.push(
          `标签：${this.resolveMemberNames(this.query.tag_ids, this.tagData, 'tag_id', 'tag_name')}`
        )
      }
      if (Array.isArray(this.query.group_ids) && this.query.group_ids.length) {
        tags.push(
          `分组：${this.resolveMemberNames(
            this.query.group_ids,
            this.groupData,
            'group_id',
            'group_name'
          )}`
        )
      }
      if (this.query.gender !== undefined && this.genders[this.query.gender]) {
        tags.push(`性别：${this.genders[this.query.gender]}`)
      }
      if (this.query.platform !== undefined && this.platforms[this.query.platform]) {
        tags.push(`平台：${this.platforms[this.query.platform]}`)
      }
      if (this.query.application !== undefined && this.applications[this.query.application]) {
        tags.push(`应用：${this.applications[this.query.application]}`)
      }
      if (this.query.search_value) {
        tags.push(`检索：${this.query.search_value}`)
      }
      return tags
    },
    memberFollowupBadgeText() {
      if (this.selection.length > 0) {
        return '可批量处理'
      }
      if (this.activeFilterTags.length > 0) {
        return '筛选已生效'
      }
      return '默认巡检'
    },
    memberFollowupBadgeClass() {
      if (this.selection.length > 0) {
        return 'is-active'
      }
      if (this.currentPageDisabledCount > 0 || this.currentPageSuperCount > 0) {
        return 'is-warning'
      }
      return 'is-safe'
    },
    memberGuideFocusLabel() {
      if (this.selection.length > 0) {
        return '先复核已选会员'
      }
      if (this.query.search_value) {
        return '先核对目标会员'
      }
      if (this.activeFilterTags.length > 0) {
        return '先看这类人群'
      }
      return '先缩小会员范围'
    },
    memberGuideCards() {
      return [
        {
          step: '第一步',
          title: this.query.search_value ? '先确认是不是在找具体会员' : '先确认你要查的是哪一类会员',
          desc: this.query.search_value
            ? `当前正在按 ${this.query.search_field || 'nickname'} 查找 ${this.query.search_value}，先确认目标会员和筛选条件是否一致。`
            : '如果目标明确，优先用手机号、用户名、会员 ID 直达；如果目标不明确，先用状态、平台、标签和分组把范围缩小。'
        },
        {
          step: '第二步',
          title: this.activeFilterTags.length > 0 ? '再判断这批会员该去分组、标签还是日志' : '再判断问题是资料、身份还是行为异常',
          desc: this.activeFilterTags.length > 0
            ? `当前已有 ${this.activeFilterTags.length} 个筛选标签，更适合先抽查结果，再决定回分组、标签或统计页继续处理。`
            : '资料和身份问题优先留在会员页，登录或访问异常优先回日志页，人群变化优先回统计和分组页。'
        },
        {
          step: '第三步',
          title: '最后再做批量动作，不要一上来就改',
          desc: this.memberFollowupRiskText
        }
      ]
    },
    memberFollowupHint() {
      if (this.selection.length > 0) {
        return '当前已经选中会员，可继续做分组、标签、禁用、密码重置和导出等批量处理。'
      }
      if (this.activeFilterTags.length > 0) {
        return '当前列表已经按筛选条件收敛，建议先抽查结果，再执行批量处理动作。'
      }
      return '当前为默认会员巡检视角，建议先通过状态、平台、标签和分组收敛范围，再继续运营处理。'
    },
    memberFollowupTags() {
      return [
        `当前页禁用：${this.currentPageDisabledCount} 人`,
        `当前页超会：${this.currentPageSuperCount} 人`,
        `已选会员：${this.selection.length} 人`,
        `筛选标签：${this.activeFilterTags.length} 项`
      ]
    },
    memberFollowupRiskText() {
      if (this.selection.length > 0) {
        return '批量动作会直接作用到当前勾选会员，请在提交前再次确认筛选条件和勾选对象一致。'
      }
      if (this.currentPageDisabledCount > 0) {
        return '当前页存在禁用会员，建议优先核对是否属于预期封禁对象，避免误伤正常会员。'
      }
      return '导入、导出、密码重置和删除都属于高影响动作，建议先通过筛选条件缩小范围后再执行。'
    },
    selectReviewItems() {
      const items = [
        `操作：${this.selectTitle || '批量处理'}`,
        `数量：${this.selection.length} 项`,
        `对象：${this.buildSelectionPreview()}`
      ]
      if (this.selectType === 'region' && this.region_id) {
        items.push(`目标地区：${this.region_id}`)
      } else if (this.selectType === 'edittag' && this.tag_ids.length) {
        items.push(
          `目标标签：${this.resolveMemberNames(this.tag_ids, this.tagData, 'tag_id', 'tag_name')}`
        )
      } else if (this.selectType === 'editgroup' && this.group_ids.length) {
        items.push(
          `目标分组：${this.resolveMemberNames(
            this.group_ids,
            this.groupData,
            'group_id',
            'group_name'
          )}`
        )
      } else if (this.selectType === 'super') {
        items.push(`超会调整：${this.is_super === 1 ? '设为超会' : '取消超会'}`)
      } else if (this.selectType === 'disable') {
        items.push(`状态调整：${this.is_disable === 1 ? '批量禁用' : '批量启用'}`)
      } else if (this.selectType === 'repwd' && this.password) {
        items.push('密码处理：将覆盖所选会员登录密码')
      } else if (this.selectType === 'export') {
        items.push(`导出文件：${this.exportFileName || '未命名导出'}.${this.exportBookType}`)
      }
      return items
    },
    selectRiskHint() {
      if (this.selectType === 'dele') {
        return '删除属于高风险动作，提交后将直接影响当前会员数据，请再次确认筛选范围和勾选对象。'
      }
      if (this.selectType === 'repwd') {
        return '重置密码后，所选会员需要使用新密码重新登录，建议仅在确认身份后执行。'
      }
      if (this.selectType === 'disable') {
        return '禁用后所选会员将无法继续登录，请先确认是否只覆盖本次筛选范围。'
      }
      return '提交前请确认筛选条件、勾选对象和目标值一致，避免把批量操作扩散到非预期会员。'
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
    this.getCall()
    this.getWarehouse()
    this.applyRouteQuery()
    this.list()
  },
  methods: {
    checkPermission,
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
    handleEntryContextPrimary() {
      if (this.entrySourceLabel === '来自会员标签') {
        this.goToPage('/member/tag')
        return
      }
      if (this.entrySourceLabel === '来自会员分组') {
        this.goToPage('/member/group')
        return
      }
      if (this.entrySourceLabel === '来自会员日志') {
        this.goToPage('/member/log')
        return
      }
      if (this.entrySourceLabel === '来自第三方账号') {
        this.goToPage('/member/third')
        return
      }
      if (this.entrySourceLabel === '来自意见反馈') {
        this.goToPage('/setting/feedback')
        return
      }
      if (this.entrySourceLabel === '来自系统公告') {
        this.goToPage('/system/notice')
        return
      }
      if (this.entrySourceLabel === '来自内部接盘对账') {
        this.goToPage('/report/internal-takeover')
        return
      }
      if (this.entrySourceLabel === '来自会员设置') {
        this.goToPage('/member/setting')
        return
      }
      this.goToPage('/member/statistic')
    },
    goToEntryContextBack() {
      if (this.entrySourceLabel === '来自会员统计') {
        this.$router.push({
          path: '/member/statistic',
          query: this.buildEntryRouteQuery({ from: 'member-member' })
        })
        return
      }
      if (this.entrySourceLabel === '来自会员标签') {
        this.$router.push({
          path: '/member/tag',
          query: this.buildEntryRouteQuery({ from: 'member-member' })
        })
        return
      }
      if (this.entrySourceLabel === '来自会员分组') {
        this.$router.push({
          path: '/member/group',
          query: this.buildEntryRouteQuery({ from: 'member-member' })
        })
        return
      }
      if (this.entrySourceLabel === '来自会员日志') {
        this.$router.push({
          path: '/member/log',
          query: this.buildEntryRouteQuery({ from: 'member-member' })
        })
        return
      }
      if (this.entrySourceLabel === '来自第三方账号') {
        this.$router.push({
          path: '/member/third',
          query: this.buildEntryRouteQuery({ from: 'member-member' })
        })
        return
      }
      if (this.entrySourceLabel === '来自意见反馈') {
        this.$router.push({
          path: '/setting/feedback',
          query: this.buildEntryRouteQuery({ from: 'member-member' })
        })
        return
      }
      if (this.entrySourceLabel === '来自系统公告') {
        this.$router.push({
          path: '/system/notice',
          query: this.buildEntryRouteQuery({ from: 'member-member' })
        })
        return
      }
      if (this.entrySourceLabel === '来自内部接盘对账') {
        this.$router.push({
          path: '/report/internal-takeover',
          query: this.buildEntryRouteQuery({ from: 'member-member' })
        })
        return
      }
      if (this.entrySourceLabel === '来自会员设置') {
        this.$router.push({
          path: '/member/setting',
          query: this.buildEntryRouteQuery({ from: 'member-member' })
        })
      }
    },
    parseRouteNumber(value) {
      if (value === undefined || value === null || value === '') {
        return undefined
      }
      const parsed = Number(value)
      return Number.isFinite(parsed) ? parsed : undefined
    },
    parseRouteArray(value) {
      if (Array.isArray(value)) {
        return value.filter((item) => item !== undefined && item !== null && item !== '')
      }
      if (typeof value === 'string' && value.trim()) {
        return value
          .split(',')
          .map((item) => item.trim())
          .filter(Boolean)
      }
      return undefined
    },
    applyRouteQuery() {
      const nextQuery = {
        ...this.$options.data().query,
        limit: this.query?.limit || getPageLimit()
      }
      const routeQuery = this.$route?.query || {}
      const searchValue = routeQuery.search_value ?? routeQuery.keyword ?? ''
      const searchField = routeQuery.search_field || routeQuery.member_id ? routeQuery.search_field || this.idkey : ''
      if (searchField) {
        nextQuery.search_field = String(searchField)
      }
      if (routeQuery.search_exp) {
        nextQuery.search_exp = String(routeQuery.search_exp)
      }
      if (searchValue !== '') {
        nextQuery.search_value = String(searchValue)
      } else if (routeQuery.member_id) {
        nextQuery.search_value = String(routeQuery.member_id)
        nextQuery.search_exp = '='
        nextQuery.search_field = this.idkey
      }
      const isDisable = this.parseRouteNumber(routeQuery.is_disable)
      if (isDisable !== undefined) {
        nextQuery.is_disable = isDisable
      }
      const isSuper = this.parseRouteNumber(routeQuery.is_super)
      if (isSuper !== undefined) {
        nextQuery.is_super = isSuper
      }
      const gender = this.parseRouteNumber(routeQuery.gender)
      if (gender !== undefined) {
        nextQuery.gender = gender
      }
      const platform = this.parseRouteNumber(routeQuery.platform)
      if (platform !== undefined) {
        nextQuery.platform = platform
      }
      const application = this.parseRouteNumber(routeQuery.application)
      if (application !== undefined) {
        nextQuery.application = application
      }
      const tagIds = this.parseRouteArray(routeQuery.tag_ids)
      if (tagIds?.length) {
        nextQuery.tag_ids = tagIds
      }
      const singleTagId = this.parseRouteNumber(routeQuery.tag_id)
      if (singleTagId !== undefined) {
        nextQuery.tag_ids = [singleTagId]
      }
      const groupIds = this.parseRouteArray(routeQuery.group_ids)
      if (groupIds?.length) {
        nextQuery.group_ids = groupIds
      }
      this.query = nextQuery
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
      this.recentActionSummary = `已执行${action}，影响 ${this.selection.length || 0} 项会员${
        extra ? `，${extra}` : ''
      }。`
    },
    goToPage(path) {
      this.$router.push({
        path,
        query: this.buildEntryRouteQuery({ from: 'member-member' })
      })
    },
    resolveMemberNames(ids = [], source = [], valueKey = 'id', labelKey = 'title') {
      return ids
        .map((id) => source.find((item) => String(item[valueKey]) === String(id))?.[labelKey] || id)
        .join('、')
    },
    //查询秤重设备
    getCall() {
      this.loading = true
      callSelect({})
        .then((res) => {
          this.call_list = res.data
          this.loading = false
        })
        .catch(() => {
          this.loading = false
        })
    },
    //查询仓库
    getWarehouse() {
      this.loading = true
      warehouseSelect({})
        .then((res) => {
          this.warehouse_list = res.data
          this.loading = false
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
          this.count = res.data.count
          this.genders = res.data.genders
          this.platforms = res.data.platforms
          this.applications = res.data.applications
          this.regionData = res.data.region
          this.tagData = res.data.tag
          this.groupData = res.data.group
          this.exps = res.data.exps
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
          this.model.api_tree = []
          this.model.thirds = []
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
      this.apiExpandAll = false
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
    getMemberLabel(row) {
      return row.nickname || row.username || row.phone || `会员#${row[this.idkey]}`
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
        if (selectType === 'region') {
          this.selectTitle = this.name + '修改所在地'
        } else if (selectType === 'edittag') {
          this.selectTitle = this.name + '修改标签'
        } else if (selectType === 'editgroup') {
          this.selectTitle = this.name + '修改分组'
        } else if (selectType === 'repwd') {
          this.selectTitle = this.name + '重置密码'
        } else if (selectType === 'super') {
          this.selectTitle = this.name + '是否超会'
        } else if (selectType === 'disable') {
          this.selectTitle = this.name + '是否禁用'
        } else if (selectType === 'dele') {
          this.selectTitle = this.name + '删除'
        } else if (selectType === 'export') {
          var date = new Date()
          var month = date.getMonth() + 1
          month = month < 10 ? '0' + month : month
          this.exportFileName = this.name + date.getFullYear() + '-' + month + '-' + date.getDate()
          this.selectTitle = this.name + '导出'
        } else if (selectType === 'import') {
          this.selectTitle = this.name + '导入'
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
        if (selectType === 'region') {
          this.region(this.selection)
        } else if (selectType === 'edittag') {
          this.edittag(this.selection)
        } else if (selectType === 'editgroup') {
          this.editgroup(this.selection)
        } else if (selectType === 'repwd') {
          this.repwd(this.selection)
        } else if (selectType === 'super') {
          this.issuper(this.selection, true)
        } else if (selectType === 'disable') {
          this.disable(this.selection, true)
        } else if (selectType === 'dele') {
          this.dele(this.selection)
        } else if (selectType === 'export') {
          this.export(this.selection)
        } else if (selectType === 'import') {
          this.import(this.selection)
        }
        this.selectDialog = false
      }
    },
    // 修改所在地
    region(row) {
      if (!row.length) {
        this.selectAlert()
      } else {
        this.loading = true
        region({
          ids: this.selectGetIds(row),
          region_id: this.region_id
        })
          .then((res) => {
            this.list()
            this.setRecentActionSummary('批量修改所在地', `目标地区：${this.region_id || '未设置'}`)
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.loading = false
          })
      }
    },
    // 修改标签
    edittag(row) {
      if (!row.length) {
        this.selectAlert()
      } else {
        this.loading = true
        edittag({
          ids: this.selectGetIds(row),
          tag_ids: this.tag_ids
        })
          .then((res) => {
            this.list()
            this.setRecentActionSummary(
              '批量修改标签',
              `目标标签：${this.resolveMemberNames(
                this.tag_ids,
                this.tagData,
                'tag_id',
                'tag_name'
              )}`
            )
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.loading = false
          })
      }
    },
    // 修改分组
    editgroup(row) {
      if (!row.length) {
        this.selectAlert()
      } else {
        this.loading = true
        editgroup({
          ids: this.selectGetIds(row),
          group_ids: this.group_ids
        })
          .then((res) => {
            this.list()
            this.setRecentActionSummary(
              '批量修改分组',
              `目标分组：${this.resolveMemberNames(
                this.group_ids,
                this.groupData,
                'group_id',
                'group_name'
              )}`
            )
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.loading = false
          })
      }
    },
    // 重置密码
    repwd(row) {
      if (!row.length) {
        this.selectAlert()
      } else {
        if (!this.password) {
          ElMessage.error('请输入新密码')
        } else {
          this.loading = true
          repwd({
            ids: this.selectGetIds(row),
            password: this.password
          })
            .then((res) => {
              this.list()
              this.setRecentActionSummary('批量重置密码', '已覆盖所选会员登录密码')
              ElMessage.success(res.msg)
            })
            .catch(() => {
              this.loading = false
            })
        }
      }
    },
    // 是否超会
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
              '批量调整超会状态',
              `目标状态：${is_super === 1 ? '设为超会' : '取消超会'}`
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
        `确认要将「${this.getMemberLabel(row)}」${value === 1 ? '设为超会' : '取消超会'}吗？`,
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
              '批量调整禁用状态',
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
        `确认要${value === 1 ? '禁用' : '启用'}「${this.getMemberLabel(row)}」吗？`,
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
        this.loading = true
        dele({
          ids: this.selectGetIds(row)
        })
          .then((res) => {
            this.list()
            this.setRecentActionSummary('批量删除会员')
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.loading = false
          })
      }
    },
    // 导入，results数据，header表头
    imports({ results, header }) {
      this.loading = true
      imports({
        import: results
      })
        .then((res) => {
          this.list()
          this.recentActionSummary = `已执行会员导入，本次导入 ${
            results.length || 0
          } 条记录，建议立即抽查昵称、手机和注册时间字段回显。`
          ElMessage.success(res.msg)
        })
        .catch(() => {
          this.loading = false
        })
    },
    // 导出
    export(row) {
      this.loading = true
      import('@/components/ExcelExport/index').then((excel) => {
        const header = [
          { member_id: 'ID' },
          { nickname: '昵称' },
          { username: '用户名' },
          { phone: '手机' },
          { email: '邮箱' },
          { is_super: '超会' },
          { is_disable: '禁用' },
          { remark: '备注' },
          { create_time: '注册时间' }
        ]
        excel.excelExport(
          row,
          header,
          this.exportFileName,
          this.exportBookType,
          this.exportAutoWidth
        )
        this.recentActionSummary = `已导出 ${row.length || 0} 条会员记录，文件：${
          this.exportFileName
        }.${this.exportBookType}。`
        this.loading = false
      })
    },
    // 权限展开
    apiExpandAllChange() {
      const expanded = this.apiExpandAll
      const length = this.$refs.apiRef.store._getAllNodes().length
      for (let i = 0; i < length; i++) {
        this.$refs.apiRef.store._getAllNodes()[i].expanded = expanded
      }
    },
    // 第三方账号解绑
    thirdUnbindBtn(row) {
      this.thirdUnbindDialog = true
      this.thirdUnbindModel = row
    },
    thirdUnbindSubmit() {
      const row = this.thirdUnbindModel
      if (!row) {
        ElMessageBox.alert('请选择需要操作的' + this.name + '第三方账号', '提示', {
          type: 'warning',
          callback: () => {}
        })
      } else {
        this.thirdUnbindLoad = true
        dele({
          ids: [row.third_id],
          type: 'third'
        })
          .then((res) => {
            var id = {}
            id[this.idkey] = row[this.idkey]
            info(id)
              .then((res) => {
                this.model.thirds = res.data.thirds
              })
              .catch(() => {})
            this.thirdUnbindLoad = false
            this.thirdUnbindDialog = false
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.thirdUnbindLoad = false
          })
      }
    },
    // 第三方账号是否禁用
    thirdDisable(row, value = row.is_disable) {
      var id = {}
      id[this.idkey] = row[this.idkey]
      disable({
        ids: [row.third_id],
        is_disable: value,
        type: 'third'
      })
        .then((res) => {
          info(id)
            .then((res) => {
              this.model.thirds = res.data.thirds
            })
            .catch(() => {})
          ElMessage.success(res.msg)
        })
        .catch(() => {
          info(id)
            .then((res) => {
              this.model.thirds = res.data.thirds
            })
            .catch(() => {})
        })
    },
    handleThirdDisable(row, value) {
      ElMessageBox.confirm(
        `确认要${value === 1 ? '禁用' : '启用'}第三方账号「${row.nickname || row.platform_name || row.third_id}」吗？`,
        '操作确认',
        {
          type: 'warning',
          confirmButtonText: '继续',
          cancelButtonText: '取消'
        }
      )
        .then(() => {
          this.thirdDisable(row, value)
        })
        .catch(() => {})
    },
    // 复制
    copy(text) {
      clip(text)
    },
    // 单元格双击复制
    cellDbclick(row, column) {
      this.copy(row[column.property])
    },
    // 是否司称员
    isbalance(row, select = false) {
      if (!row.length) {
        this.selectAlert()
      } else {
        this.loading = true
        var is_balance = row[0].is_balance
        if (select) {
          is_balance = this.is_balance
        }
        edit_balance({
          ids: this.selectGetIds(row),
          is_balance: is_balance
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
    // 是否仓库员
    iswarehouse(row, select = false) {
      if (!row.length) {
        this.selectAlert()
      } else {
        this.loading = true
        var is_warehouse = row[0].is_warehouse
        if (select) {
          is_warehouse = this.is_warehouse
        }
        edit_warehouse({
          ids: this.selectGetIds(row),
          is_warehouse: is_warehouse
        })
          .then((res) => {
            this.list()
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.list()
          })
      }
    }
  }
}
</script>
<style lang="scss" scoped>
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

.member-summary-bar {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  margin-top: 16px;
  padding: 14px 16px;
  border: 1px solid #e6ecf5;
  border-radius: 14px;
  background: #f8fbff;
}

.member-summary-bar__chips {
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

.member-summary-bar__hint {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 240px;
  padding: 12px 14px;
  border-radius: 12px;
  background: #eef4ff;
  color: #1d4ed8;
}

.member-summary-bar__hint.is-safe {
  background: #eaf8ef;
  color: #15803d;
}

.member-summary-bar__hint.is-warning {
  background: #fff5e8;
  color: #b45309;
}

.member-summary-bar__hint.is-active {
  background: #e8f0ff;
  color: #1d4ed8;
}

.member-summary-bar__hint-title {
  font-size: 12px;
  font-weight: 700;
}

.member-summary-bar__hint-text {
  font-size: 12px;
  line-height: 1.7;
}

.member-guide-panel {
  margin-top: 10px;
  padding: 14px 16px;
  border: 1px solid #dbe7f5;
  border-radius: 14px;
  background: linear-gradient(135deg, #f9fbff 0%, #f5f8ff 100%);
}

.member-guide-panel__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.member-guide-panel__title {
  font-size: 14px;
  font-weight: 700;
  color: #111827;
}

.member-guide-panel__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.member-guide-panel__badge {
  display: inline-flex;
  align-items: center;
  min-height: 30px;
  padding: 0 12px;
  border-radius: 999px;
  background: #e8f0ff;
  color: #1d4ed8;
  font-size: 12px;
  font-weight: 700;
  white-space: nowrap;
}

.member-guide-panel__grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-top: 14px;
}

.member-guide-card {
  padding: 12px 14px;
  border-radius: 12px;
  border: 1px solid rgba(191, 219, 254, 0.95);
  background: rgba(255, 255, 255, 0.92);
}

.member-guide-card__step {
  display: inline-flex;
  align-items: center;
  min-height: 22px;
  padding: 0 8px;
  border-radius: 999px;
  background: #eff6ff;
  color: #2563eb;
  font-size: 11px;
  font-weight: 700;
}

.member-guide-card__title {
  margin-top: 10px;
  font-size: 13px;
  font-weight: 700;
  color: #1f2937;
}

.member-guide-card__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #475569;
}

.followup-panel {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-top: 10px;
  padding: 14px 16px;
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
  border-radius: 999px;
  background: #f3f4f6;
  color: #475569;
  font-size: 12px;
  border: 1px solid rgba(148, 163, 184, 0.16);
}

.followup-panel__risk {
  padding: 10px 12px;
  border-radius: 12px;
  border: 1px solid #fed7aa;
  background: #fff7ed;
  color: #c2410c;
}

.followup-panel__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  justify-content: flex-end;
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
  flex-wrap: wrap;
  gap: 10px;
  align-items: center;
  padding: 10px 12px;
  background: rgba(248, 250, 252, 0.9);
  border: 1px solid rgba(148, 163, 184, 0.14);
  border-radius: 14px;
}

.action-cluster__title {
  font-size: 12px;
  font-weight: 700;
  color: #64748b;
}

.member-table :deep(.el-table__cell) {
  padding-top: 8px;
  padding-bottom: 8px;
}

@media (max-width: 900px) {
  .entry-context-banner,
  .followup-panel,
  .member-summary-bar,
  .member-guide-panel__header,
  .section-title-row {
    flex-direction: column;
  }

  .section-title-row__meta {
    white-space: normal;
  }

  .member-guide-panel__grid {
    grid-template-columns: 1fr;
  }
}
</style>
