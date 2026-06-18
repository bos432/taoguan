<template>
  <div class="app-container">
    <el-card class="app-head head-pb20">
      <div class="section-title-row">
        <div>
          <div class="section-title-row__title">菜单管理</div>
          <div class="section-title-row__desc">统一处理菜单筛选、结构调整、权限控制和角色解绑。</div>
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
            <el-form-item label="上级：" prop="menu_pid">
              <el-cascader
                v-model="query.menu_pid"
                :options="trees"
                :props="props"
                clearable
                filterable
                @change="search()"
              />
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="类型：" prop="menu_type">
              <el-select v-model="query.menu_type" clearable filterable @change="search()">
                <el-option :value="0" label="目录" />
                <el-option :value="1" label="菜单" />
                <el-option :value="2" label="按钮" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="免登：" prop="is_unlogin">
              <el-select v-model="query.is_unlogin" @change="search()" clearable>
                <el-option :value="1" label="是" />
                <el-option :value="0" label="否" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="免权：" prop="is_unauth">
              <el-select v-model="query.is_unauth" @change="search()" clearable>
                <el-option :value="1" label="是" />
                <el-option :value="0" label="否" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="免限：" prop="is_unrate">
              <el-select v-model="query.is_unrate" @change="search()" clearable>
                <el-option :value="1" label="是" />
                <el-option :value="0" label="否" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="隐藏：" prop="hidden">
              <el-select v-model="query.hidden" @change="search()" clearable>
                <el-option :value="1" label="是" />
                <el-option :value="0" label="否" />
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
                  <el-option value="menu_name" label="名称" />
                  <el-option value="menu_url" label="链接" />
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
      <div class="menu-summary-bar">
        <div class="menu-summary-bar__chips">
          <span class="summary-chip summary-chip--primary">总数 {{ count }}</span>
          <span class="summary-chip">已选 {{ selection.length }} 项</span>
          <span class="summary-chip">{{ isExpandAll ? '树表展开' : '树表收起' }}</span>
          <span class="summary-chip">树节点 {{ Array.isArray(trees) ? trees.length : 0 }}</span>
          <span v-for="item in activeFilterTags" :key="item" class="summary-chip">{{ item }}</span>
          <span v-if="!activeFilterTags.length" class="summary-chip">默认条件：全部菜单</span>
          <span v-if="recentActionSummary" class="summary-chip summary-chip--muted">{{
            recentActionSummary
          }}</span>
        </div>
        <div class="menu-summary-bar__hint" :class="menuFollowupBadgeClass">
          <span class="menu-summary-bar__hint-title">{{ menuFollowupBadgeText }}</span>
          <span class="menu-summary-bar__hint-text">{{ menuFollowupHint }}</span>
        </div>
      </div>
      <div class="plain-guide">
        <div class="plain-guide__header">
          <div>
            <div class="plain-guide__title">这页建议先这样看</div>
            <div class="plain-guide__desc">
              菜单页本质上是在管“后台入口怎么出现、谁能看到、点进去走到哪”。先看树结构和禁用/隐藏状态，再看角色绑定，最后才去批量改排序、上级或免权免登。
            </div>
          </div>
          <span class="plain-guide__badge">{{ menuFocusLabel }}</span>
        </div>
        <div class="plain-guide__grid">
          <div v-for="item in menuGuideCards" :key="item.title" class="plain-guide-card">
            <div class="plain-guide-card__title">{{ item.title }}</div>
            <div class="plain-guide-card__desc">{{ item.desc }}</div>
            <div class="plain-guide-card__action">{{ item.action }}</div>
          </div>
        </div>
      </div>
      <div class="followup-panel">
        <div class="followup-panel__header">
          <div>
            <div class="followup-panel__title">菜单改完后继续去哪</div>
            <div class="followup-panel__desc">
              把菜单结构、角色绑定、账号可见范围和操作日志连成一条复核链。
            </div>
          </div>
          <div class="followup-panel__risk">{{ menuFollowupRiskText }}</div>
        </div>
        <div class="followup-panel__tags">
          <span v-for="item in menuFollowupTags" :key="item">{{ item }}</span>
        </div>
        <div class="followup-card-grid">
          <button
            v-for="item in menuFollowupActionCards"
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
        <el-form-item v-if="selectType === 'remover'">
          <span style="">确定要解除选中的{{ name }}的角色吗？</span>
        </el-form-item>
        <el-form-item v-else-if="selectType === 'editsort'" label="排序">
          <el-input v-model="sort" type="number" placeholder="250" />
          <el-input v-model="sort_incdec" type="text" placeholder="0">
            <template #append>按{{ name }}ID顺序递增或递减排序</template>
          </el-input>
        </el-form-item>
        <el-form-item v-else-if="selectType === 'editpid'" label="上级">
          <el-cascader
            v-model="menu_pid"
            :options="trees"
            :props="props"
            class="w-full"
            placeholder="一级菜单"
            clearable
            filterable
          />
        </el-form-item>
        <el-form-item v-else-if="selectType === 'unlogin'" label="是否免登">
          <!--          <el-switch v-model="is_unlogin" :active-value="1" :inactive-value="0" />-->
          <el-radio-group v-model="is_unlogin">
            <el-radio :value="1">是</el-radio>
            <el-radio :value="0">否</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item v-else-if="selectType === 'unauth'" label="是否免权">
          <!--          <el-switch v-model="is_unauth" :active-value="1" :inactive-value="0" />-->
          <el-radio-group v-model="is_unauth">
            <el-radio :value="1">是</el-radio>
            <el-radio :value="0">否</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item v-else-if="selectType === 'unrate'" label="是否免限">
          <!--          <el-switch v-model="is_unrate" :active-value="1" :inactive-value="0" />-->
          <el-radio-group v-model="is_unrate">
            <el-radio :value="1">是</el-radio>
            <el-radio :value="0">否</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item v-else-if="selectType === 'hidden'" label="是否隐藏">
          <!--          <el-switch v-model="hidden" :active-value="1" :inactive-value="0" />-->
          <el-radio-group v-model="hidden">
            <el-radio :value="1">是</el-radio>
            <el-radio :value="0">否</el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item v-else-if="selectType === 'disable'" label="是否禁用">
          <el-radio-group v-model="is_disable">
            <el-radio :value="0">启用</el-radio>
            <el-radio :value="1">禁用</el-radio>
          </el-radio-group>
          <!--          <el-switch v-model="is_disable" :active-value="1" :inactive-value="0" />-->
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
          <div class="section-title-row__title">菜单列表</div>
          <div class="section-title-row__desc">
            支持结构调整、权限批量控制、角色解绑和树表维护。
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
              class="top-[3px]"
              title="收起/展开"
              @change="expandAll"
            >
              收起
            </el-checkbox>
            <el-button type="primary" @click="add()">添加</el-button>
            <el-button title="修改上级" @click="selectOpen('editpid')">上级</el-button>
            <el-button title="修改排序" @click="selectOpen('editsort')">排序</el-button>
          </div>
          <div class="action-cluster">
            <span class="action-cluster__title">权限控制</span>
            <el-button title="是否免登" @click="selectOpen('unlogin')">免登</el-button>
            <el-button title="是否免权" @click="selectOpen('unauth')">免权</el-button>
            <el-button title="是否免限" @click="selectOpen('unrate')">免限</el-button>
            <el-button title="是否隐藏" @click="selectOpen('hidden')">隐藏</el-button>
            <el-button title="解除角色" @click="selectOpen('remover')">解除角色</el-button>
          </div>
          <div class="action-cluster">
            <span class="action-cluster__title">批量处理</span>
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
        @selection-change="select"
        @select-all="selectAll"
        @cell-dblclick="cellDbclick"
      >
        <el-table-column type="selection" width="42" title="全选/反选" />
        <el-table-column prop="menu_name" label="菜单名称" min-width="210" show-overflow-tooltip />
        <el-table-column prop="meta_icon" label="图标" min-width="60">
          <template #default="scope">
            <svg-icon :icon-class="scope.row.meta_icon" />
          </template>
        </el-table-column>
        <el-table-column prop="menu_url" label="菜单链接" min-width="220" show-overflow-tooltip />
        <el-table-column prop="path" label="路由地址" min-width="150" show-overflow-tooltip />
        <el-table-column prop="name" label="路由名称" min-width="130" show-overflow-tooltip />
        <el-table-column prop="component" label="组件路径" min-width="135" show-overflow-tooltip />
        <el-table-column prop="menu_type" label="类型" min-width="60">
          <template #default="scope">
            <i v-if="scope.row.menu_type == 0" title="目录"><svg-icon icon-class="folder" /></i>
            <i v-else-if="scope.row.menu_type == 1" title="菜单"><svg-icon icon-class="menu" /></i>
            <i v-else-if="scope.row.menu_type == 2" title="按钮"><svg-icon icon-class="open" /></i>
          </template>
        </el-table-column>
        <el-table-column prop="is_unlogin" label="免登" min-width="66">
          <template #default="scope">
            <el-switch
              v-if="scope.row.menu_url"
              :model-value="scope.row.is_unlogin"
              :width="35"
              :active-value="1"
              :inactive-value="0"
              @change="handleUnloginSwitch(scope.row, $event)"
            />
            <span v-else></span>
          </template>
        </el-table-column>
        <el-table-column prop="is_unauth" label="免权" min-width="66">
          <template #default="scope">
            <el-switch
              v-if="scope.row.menu_url"
              :model-value="scope.row.is_unauth"
              :width="35"
              :active-value="1"
              :inactive-value="0"
              @change="handleUnauthSwitch(scope.row, $event)"
            />
            <span v-else></span>
          </template>
        </el-table-column>
        <el-table-column prop="is_unrate" label="免限" min-width="66">
          <template #default="scope">
            <el-switch
              v-if="scope.row.menu_url"
              :model-value="scope.row.is_unrate"
              :width="35"
              :active-value="1"
              :inactive-value="0"
              @change="handleUnrateSwitch(scope.row, $event)"
            />
            <span v-else></span>
          </template>
        </el-table-column>
        <el-table-column prop="is_disable" label="禁用" min-width="66">
          <template #default="scope">
            <el-switch
              v-if="scope.row.menu_url"
              :model-value="scope.row.is_disable"
              :width="35"
              :active-value="1"
              :inactive-value="0"
              @change="handleDisableSwitch(scope.row, $event)"
            />
            <span v-else></span>
          </template>
        </el-table-column>
        <el-table-column prop="hidden" label="隐藏" min-width="66">
          <template #default="scope">
            <el-switch
              :model-value="scope.row.hidden"
              :width="35"
              :active-value="1"
              :inactive-value="0"
              @change="handleHiddenSwitch(scope.row, $event)"
            />
          </template>
        </el-table-column>
        <el-table-column :prop="idkey" label="ID" min-width="80" />
        <el-table-column prop="sort" label="排序" min-width="80" />
        <el-table-column label="操作" width="250">
          <template #default="scope">
            <el-link type="primary" class="mr-1" :underline="false" @click="roleShow(scope.row)">
              角色
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
                from: 'system-menu',
                menu_id: scope.row[idkey],
                menu_name: scope.row.menu_name || ''
              })"
            >
              用户
            </el-link>
            <el-link
              type="primary"
              class="mr-1"
              :underline="false"
              @click="goToSystemPage('/system/user-log', {
                from: 'system-menu',
                menu_id: scope.row[idkey],
                menu_name: scope.row.menu_name || '',
                search_value: scope.row.menu_name || scope.row.path || scope.row.menu_url || ''
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
                  <div class="dialog-plain-guide__title">菜单编辑先看入口承接，再改权限字段</div>
                  <div class="dialog-plain-guide__desc">
                    先确认这条是目录、菜单还是按钮，以及它应该挂到哪一层；结构和去向对了，再处理免登、免权、隐藏这些控制项。
                  </div>
                </div>
                <span class="dialog-plain-guide__badge">{{ menuDialogFocusLabel }}</span>
              </div>
              <div class="dialog-plain-guide__grid">
                <div
                  v-for="item in menuDialogGuideCards"
                  :key="item.title"
                  class="dialog-plain-guide-card"
                >
                  <div class="dialog-plain-guide-card__title">{{ item.title }}</div>
                  <div class="dialog-plain-guide-card__desc">{{ item.desc }}</div>
                  <div class="dialog-plain-guide-card__action">{{ item.action }}</div>
                </div>
              </div>
            </div>
            <el-form-item label="菜单上级" prop="menu_pid">
              <el-cascader
                v-model="model.menu_pid"
                :options="trees"
                :props="props"
                class="w-full"
                placeholder="一级菜单"
                clearable
                filterable
              />
            </el-form-item>
            <el-form-item label="菜单类型" prop="menu_type">
              <el-radio-group v-model="model.menu_type">
                <el-radio :label="0">目录<svg-icon icon-class="folder" /></el-radio>
                <el-radio :label="1">菜单<svg-icon icon-class="menu" /></el-radio>
                <el-radio :label="2">按钮<svg-icon icon-class="open" /></el-radio>
              </el-radio-group>
            </el-form-item>
            <el-form-item v-if="model.menu_type !== 2" label="菜单图标" prop="meta_icon">
              <icon-select v-model="model.meta_icon" />
            </el-form-item>
            <el-form-item label="菜单名称" prop="menu_name">
              <el-input
                v-model="model.menu_name"
                clearable
                placeholder="meta.title；侧边栏菜单名称"
              >
                <template #prepend>
                  <el-button title="meta.title；侧边栏菜单名称">
                    <svg-icon icon-class="question-filled" />
                  </el-button>
                </template>
                <template #append>
                  <el-button title="复制" @click="copy(model.menu_name)">
                    <svg-icon icon-class="copy-document" />
                  </el-button>
                </template>
              </el-input>
            </el-form-item>
            <el-form-item label="菜单链接" prop="menu_url">
              <el-input
                v-model="model.menu_url"
                clearable
                placeholder="roles；权限标识：应用/控制器/操作；区分大小写"
              >
                <template #prepend>
                  <el-button title="roles；权限标识：应用/控制器/操作，区分大小写">
                    <svg-icon icon-class="question-filled" />
                  </el-button>
                </template>
                <template #append>
                  <el-button title="复制" @click="copy(model.menu_url)">
                    <svg-icon icon-class="copy-document" />
                  </el-button>
                </template>
              </el-input>
            </el-form-item>
            <el-form-item v-if="model.menu_type !== 2" label="路由地址" prop="path">
              <el-input
                v-model="model.path"
                clearable
                placeholder="path；路由地址，如：member，一级菜单需在前面加斜杠/；外链为 http 地址"
              >
                <template #prepend>
                  <el-button
                    title="path；路由地址，如：member，一级菜单需在前面加斜杠/；外链为 http 地址"
                  >
                    <svg-icon icon-class="question-filled" />
                  </el-button>
                </template>
                <template #append>
                  <el-button title="复制" @click="copy(model.path)">
                    <svg-icon icon-class="copy-document" />
                  </el-button>
                </template>
              </el-input>
            </el-form-item>
            <el-form-item v-if="model.menu_type === 1" label="路由名称" prop="name">
              <el-input
                v-model="model.name"
                clearable
                placeholder="name；组件name属性，如：Member，<keep-alive> 用到；外链可随意填写"
              >
                <template #prepend>
                  <el-button
                    title="name；组件的name属性，如：Member，<keep-alive> 用到；外链可随意填写"
                  >
                    <svg-icon icon-class="question-filled" />
                  </el-button>
                </template>
                <template #append>
                  <el-button title="复制" @click="copy(model.name)">
                    <svg-icon icon-class="copy-document" />
                  </el-button>
                </template>
              </el-input>
            </el-form-item>
            <el-form-item
              v-if="model.menu_type === 0 || model.menu_type === 1"
              label="组件地址"
              prop="component"
            >
              <el-input
                v-model="model.component"
                clearable
                placeholder="component；组件路径，如：member/member，默认在 views 目录下"
              >
                <template #prepend>
                  <el-button title="component；组件路径，如：member/member，默认在 views 目录下">
                    <svg-icon icon-class="question-filled" />
                  </el-button>
                </template>
                <template #append>
                  <el-button title="复制" @click="copy(model.component)">
                    <svg-icon icon-class="copy-document" />
                  </el-button>
                </template>
              </el-input>
            </el-form-item>
            <el-form-item v-if="model.menu_type === 1" label="路由参数" prop="meta_query">
              <el-input
                v-model="model.meta_query"
                clearable
                placeholder='meta.query；路由的默认传递参数，如：{ "recycle": 0 }'
              >
                <template #prepend>
                  <el-button title='meta.query；路由的默认传递参数，如：{ "recycle": 0 }'>
                    <svg-icon icon-class="question-filled" />
                  </el-button>
                </template>
                <template #append>
                  <el-button title="复制" @click="copy(model.meta_query)">
                    <svg-icon icon-class="copy-document" />
                  </el-button>
                </template>
              </el-input>
            </el-form-item>
            <el-form-item v-if="model.menu_type !== 2" label="是否隐藏" prop="hidden">
              <el-button title="hidden；隐藏后侧边栏不显示，但仍然可以访问">
                <svg-icon icon-class="question-filled" />
              </el-button>
              <el-radio-group v-model="model.hidden" style="margin-left: 10px">
                <el-radio :label="0">否</el-radio>
                <el-radio :label="1">是</el-radio>
              </el-radio-group>
            </el-form-item>
            <el-form-item v-if="model.menu_type !== 2" label="是否缓存" prop="keep_alive">
              <el-button title="keepAlive；是否缓存组件">
                <svg-icon icon-class="question-filled" />
              </el-button>
              <el-radio-group v-model="model.keep_alive" style="margin-left: 10px">
                <el-radio :label="0">否</el-radio>
                <el-radio :label="1">是</el-radio>
              </el-radio-group>
            </el-form-item>
            <el-form-item v-if="model.menu_type == 0" label="始终显示" prop="always_show">
              <el-button title="alwaysShow；是否始终显示（只有一个子路由的时候）">
                <svg-icon icon-class="question-filled" />
              </el-button>
              <el-radio-group v-model="model.always_show" style="margin-left: 10px">
                <el-radio :label="0">否</el-radio>
                <el-radio :label="1">是</el-radio>
              </el-radio-group>
            </el-form-item>
            <el-form-item label="菜单排序" prop="sort">
              <el-input v-model="model.sort" type="number" placeholder="250">
                <template #prepend>
                  <el-button title="降序，数值越大越排在前面">
                    <svg-icon icon-class="question-filled" />
                  </el-button>
                </template>
              </el-input>
            </el-form-item>
            <el-form-item v-if="model.menu_type === 1" label="快速添加" prop="add">
              <el-button
                class="ya-margin-right"
                title="快速添加，需要输入菜单链接：应用/控制器/操作；区分大小写"
              >
                <svg-icon icon-class="question-filled" />
              </el-button>
              <el-checkbox v-model="model.add_info">信息</el-checkbox>
              <el-checkbox v-model="model.add_add">添加</el-checkbox>
              <el-checkbox v-model="model.add_edit">修改</el-checkbox>
              <el-checkbox v-model="model.add_dele">删除</el-checkbox>
              <el-checkbox v-model="model.add_disable">禁用</el-checkbox>
            </el-form-item>
            <el-form-item
              v-if="model.menu_type === 1"
              v-show="model[idkey]"
              label="快速修改"
              prop="edit"
            >
              <el-button
                class="ya-margin-right"
                title="快速修改，需要输入菜单链接：应用/控制器/操作；区分大小写"
              >
                <svg-icon icon-class="question-filled" />
              </el-button>
              <el-checkbox v-model="model.edit_info">信息</el-checkbox>
              <el-checkbox v-model="model.edit_add">添加</el-checkbox>
              <el-checkbox v-model="model.edit_edit">修改</el-checkbox>
              <el-checkbox v-model="model.edit_dele">删除</el-checkbox>
              <el-checkbox v-model="model.edit_disable">禁用</el-checkbox>
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
          <el-button :loading="loading" @click="reset('')">重置</el-button>
          <el-button :loading="loading" @click="cancel">取消</el-button>
          <el-button :loading="loading" type="primary" @click="submit">提交</el-button>
        </template>
      </el-dialog>
      <!-- 角色 -->
      <el-dialog
        v-model="roleDialog"
        :title="roleDialogTitle"
        :close-on-click-modal="false"
        :close-on-press-escape="false"
        top="10vh"
        width="65%"
      >
        <!-- 角色操作 -->
        <el-row>
          <el-col>
            <el-button type="primary" title="解除" @click="roleSelectOpen('roleRemove')">
              解除
            </el-button>
            <el-input
              v-model="roleQuery.search_value"
              class="ya-search-value"
              placeholder="名称"
              clearable
            />
            <el-button type="primary" @click="roleList()">查询</el-button>
          </el-col>
        </el-row>
        <!-- 角色列表 -->
        <el-table
          ref="roleRef"
          v-loading="roleLoad"
          :data="roleData"
          :height="height - 70"
          @sort-change="roleSort"
          @selection-change="roleSelect"
        >
          <el-table-column type="selection" width="42" title="全选/反选" />
          <el-table-column :prop="rolePk" label="角色ID" width="100" sortable="custom" />
          <el-table-column
            prop="role_name"
            label="角色名称"
            min-width="120"
            sortable="custom"
            show-overflow-tooltip
          />
          <el-table-column prop="role_desc" label="描述" min-width="130" show-overflow-tooltip />
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
          <el-table-column prop="sort" label="排序" min-width="85" sortable="custom" />
          <el-table-column label="操作" width="70">
            <template #default="scope">
              <el-link
                type="primary"
                :underline="false"
                @click="roleSelectOpen('roleRemove', scope.row)"
              >
                解除
              </el-link>
            </template>
          </el-table-column>
        </el-table>
        <!-- 角色分页 -->
        <pagination
          v-show="roleCount > 0"
          v-model:total="roleCount"
          v-model:page="roleQuery.page"
          v-model:limit="roleQuery.limit"
          @pagination="roleList"
        />
      </el-dialog>
      <el-dialog
        v-model="roleSelectDialog"
        :title="roleSelectTitle"
        :close-on-click-modal="false"
        :close-on-press-escape="false"
        top="20vh"
      >
        <el-form ref="roleSelectRef" label-width="120px">
          <el-form-item v-if="roleSelectType === 'roleRemove'" :label="name + 'ID'">
            <span>{{ roleQuery[idkey] }}</span>
          </el-form-item>
          <el-form-item :label="roleName + 'ID'">
            <el-input v-model="roleSelectIds" type="textarea" autosize disabled />
          </el-form-item>
        </el-form>
        <template #footer>
          <el-button @click="roleSelectCancel">取消</el-button>
          <el-button type="primary" @click="roleSelectSubmit">提交</el-button>
        </template>
      </el-dialog>
    </el-card>
  </div>
</template>

<script>
import screenHeight from '@/utils/screen-height'
import Pagination from '@/components/Pagination/index.vue'
import clip from '@/utils/clipboard'
import { arrayColumn } from '@/utils/index'
import { getPageLimit } from '@/utils/settings'
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
  ishidden,
  disable,
  role,
  roleRemove
} from '@/api/system/menu'
import { shortcuts } from '@/utils/getDate.js'
import { resolveAdminRuntimeEnv } from '@/utils/runtime-env'

export default {
  name: 'SystemMenu',
  components: { Pagination },
  data() {
    return {
      name: '菜单',
      height: 680,
      loading: false,
      idkey: 'menu_id',
      exps: [{ exp: 'like', name: '包含' }],
      query: {
        search_field: 'menu_name',
        search_exp: 'like',
        date_field: 'create_time',
        is_disable: undefined,
        menu_pid: undefined,
        menu_type: undefined,
        is_unlogin: undefined,
        is_unauth: undefined,
        is_unrate: undefined,
        hidden: undefined
      },
      data: [],
      dialog: false,
      dialogTitle: '',
      model: {
        menu_id: '',
        menu_pid: 0,
        menu_type: 0,
        meta_icon: '',
        menu_name: '',
        menu_url: '',
        path: '',
        name: '',
        component: '',
        meta_query: '',
        hidden: 0,
        keep_alive: 1,
        always_show: 0,
        sort: 250,
        add_info: false,
        add_add: false,
        add_edit: false,
        add_dele: false,
        add_disable: false,
        edit_info: false,
        edit_add: false,
        edit_edit: false,
        edit_dele: false,
        edit_disable: false
      },
      rules: {
        menu_name: [{ required: true, message: '请输入菜单名称', trigger: 'blur' }],
        path: [{ required: true, message: '请输入路由地址', trigger: 'blur' }],
        name: [{ required: true, message: '请输入路由名称', trigger: 'blur' }]
      },
      trees: [],
      props: { checkStrictly: true, value: 'menu_id', label: 'menu_name', emitPath: false },
      isExpandAll: false,
      selection: [],
      selectIds: '',
      selectTitle: '操作',
      selectDialog: false,
      selectType: '',
      sort: 250,
      sort_incdec: '0',
      menu_pid: 0,
      is_unlogin: 0,
      is_unauth: 0,
      is_unrate: 0,
      is_disable: 0,
      hidden: 0,
      rolePk: 'role_id',
      roleName: '角色',
      roleDialog: false,
      roleDialogTitle: '',
      roleLoad: false,
      roleData: [],
      roleCount: 0,
      roleQuery: {
        page: 1,
        limit: getPageLimit(),
        search_field: 'role_name',
        search_exp: 'like',
        search_value: ''
      },
      roleSelection: [],
      roleSelectIds: '',
      roleSelectTitle: '操作',
      roleSelectDialog: false,
      roleSelectType: '',
      count: '',
      shortcuts: shortcuts(),
      recentActionSummary: '',
      runtimeEnvInfo: resolveAdminRuntimeEnv()
    }
  },
  computed: {
    entrySourceLabel() {
      const source = String(this.$route?.query?.from || '')
      if (source === 'system-user') return '来自后台用户'
      if (source === 'system-dept') return '来自部门管理'
      if (source === 'system-post') return '来自职位管理'
      if (source === 'system-role') return '来自角色管理'
      if (source === 'system-user-log') return '来自用户日志'
      return ''
    },
    entryContextVisible() {
      return Boolean(this.entrySourceLabel)
    },
    entryContextTitle() {
      if (this.entrySourceLabel === '来自后台用户') return '当前从后台用户进入菜单管理'
      if (this.entrySourceLabel === '来自部门管理') return '当前从部门管理进入菜单管理'
      if (this.entrySourceLabel === '来自职位管理') return '当前从职位管理进入菜单管理'
      if (this.entrySourceLabel === '来自角色管理') return '当前从角色管理进入菜单管理'
      if (this.entrySourceLabel === '来自用户日志') return '当前从用户日志进入菜单管理'
      return '当前为外部入口承接视角'
    },
    entryContextDesc() {
      if (this.entrySourceLabel === '来自后台用户') {
        return '这类进入通常是为了排某个账号到底能看到哪些入口。建议先核树结构和隐藏、禁用状态，再回账号页看角色归属。'
      }
      if (this.entrySourceLabel === '来自部门管理') {
        return '这类进入通常是为了把组织归属继续落到菜单入口可见面。建议先锁关键菜单，再回部门页看账号挂载是否一致。'
      }
      if (this.entrySourceLabel === '来自职位管理') {
        return '这类进入通常是为了看岗位变化是否会影响入口可见范围。建议先核菜单状态，再回职位页确认岗位承接。'
      }
      if (this.entrySourceLabel === '来自角色管理') {
        return '这类进入通常是为了排某个角色的菜单面。建议先锁定菜单分支，再继续看角色解绑和免权免登配置。'
      }
      if (this.entrySourceLabel === '来自用户日志') {
        return '这类进入通常是为了排某个异常操作是从哪个入口进来的。建议先确认菜单可见性和访问规则，再回日志页追链路。'
      }
      return ''
    },
    entryContextPrimaryLabel() {
      if (this.entrySourceLabel === '来自后台用户') return '回后台用户'
      if (this.entrySourceLabel === '来自部门管理') return '回部门管理'
      if (this.entrySourceLabel === '来自职位管理') return '回职位管理'
      if (this.entrySourceLabel === '来自角色管理') return '回角色管理'
      return '去用户日志复核'
    },
    activeFilterTags() {
      const tags = []
      if (this.query.date_value?.length) {
        tags.push(`添加时间：${this.query.date_value.join(' 至 ')}`)
      }
      if (this.query.menu_pid) {
        tags.push(`上级菜单：${this.query.menu_pid}`)
      }
      if (this.query.menu_type !== undefined) {
        tags.push(
          `菜单类型：${
            Number(this.query.menu_type) === 0
              ? '目录'
              : Number(this.query.menu_type) === 1
              ? '菜单'
              : '按钮'
          }`
        )
      }
      if (this.query.is_disable !== undefined) {
        tags.push(`启禁状态：${this.query.is_disable === 1 ? '禁用' : '启用'}`)
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
      if (this.query.hidden !== undefined) {
        tags.push(`隐藏：${this.query.hidden === 1 ? '是' : '否'}`)
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
    currentPageHiddenCount() {
      return Array.isArray(this.data)
        ? this.data.filter((item) => Number(item.hidden) === 1).length
        : 0
    },
    menuFollowupBadgeText() {
      if (this.selection.length > 0) {
        return '可批量调整'
      }
      return this.isExpandAll ? '树表展开' : '树表收起'
    },
    menuFollowupBadgeClass() {
      if (this.selection.length > 0) {
        return 'is-active'
      }
      if (this.currentPageDisabledCount > 0 || this.currentPageHiddenCount > 0) {
        return 'is-warning'
      }
      return 'is-safe'
    },
    menuFollowupHint() {
      if (this.selection.length > 0) {
        return '当前已经选中菜单，可继续调整上级、排序、权限开关、角色解绑和禁用状态。'
      }
      return '菜单页更适合先核对树表结构，再继续做排序、隐藏、免登/免权等批量控制。'
    },
    menuFocusLabel() {
      if (this.selection.length > 0) {
        return '先确认当前勾选范围'
      }
      if (this.currentPageDisabledCount > 0 || this.currentPageHiddenCount > 0) {
        return '先看异常入口状态'
      }
      return '先看入口树结构'
    },
    menuGuideCards() {
      return [
        {
          title: '第一步：先看树结构是不是对的',
          desc: '先确认目录、菜单、按钮挂载层级是否合理，避免入口本来就挂错位置，后面越改越乱。',
          action: this.isExpandAll
            ? '现在树表处于展开状态，适合先扫层级。'
            : '可以先展开树表，再核对目录和菜单承接。'
        },
        {
          title: '第二步：再看隐藏、禁用、免权这些状态',
          desc: '很多“菜单不见了、点不开、角色看不到”的问题，不一定是路由错，往往是这里的状态被改过。',
          action: `当前页里禁用 ${this.currentPageDisabledCount} 项，隐藏 ${this.currentPageHiddenCount} 项。`
        },
        {
          title: '第三步：最后再做批量调整',
          desc: '只有在确认入口结构和状态都没问题后，再去批量改上级、排序、角色解绑，风险会小很多。',
          action:
            this.selection.length > 0
              ? `当前已选 ${this.selection.length} 项，可以继续做批量处理。`
              : '先勾选菜单，再继续做批量维护。'
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
      if (this.selectType === 'editpid') {
        items.push(`目标上级：${this.menu_pid || '顶级菜单'}`)
      } else if (this.selectType === 'editsort') {
        items.push(`目标排序：${this.sort || '未填写'}`)
      } else if (['unlogin', 'unauth', 'unrate', 'hidden', 'disable'].includes(this.selectType)) {
        items.push(`状态操作：${this.selectType}`)
      }
      return items
    },
    selectRiskHint() {
      if (!this.selection.length) {
        return '请先勾选目标菜单，再执行批量结构或权限调整。'
      }
      if (this.selectType === 'dele') {
        return '删除菜单属于高风险动作，提交前请先确认没有角色、路由承接或页面入口仍依赖它。'
      }
      if (this.selectType === 'editpid' || this.selectType === 'editsort') {
        return '菜单结构和排序会直接影响后台入口显示顺序，建议先抽查目录、菜单、按钮三层承接。'
      }
      return '菜单权限开关会直接影响后台入口是否可见、可点、可免权访问，提交前请先复核角色影响面。'
    },
    menuDialogFocusLabel() {
      if (!this.model[this.idkey]) {
        return this.model.menu_pid ? '先看子节点挂载位置' : '先补顶级入口'
      }
      if (Number(this.model.menu_type) === 2) {
        return '当前是按钮权限'
      }
      return '优先核入口层级和去向'
    },
    menuDialogGuideCards() {
      const typeMap = { 0: '目录', 1: '菜单', 2: '按钮' }
      return [
        {
          title: '第一步：先看菜单类型和上级',
          desc: '目录、菜单、按钮三种节点承接关系不同，先确认类型和挂载层级是不是合理。',
          action: `类型：${typeMap[Number(this.model.menu_type)] || '未设置'}；上级：${this.model.menu_pid || '顶级菜单'}`
        },
        {
          title: '第二步：再看路由和组件去向',
          desc: '很多入口问题不是权限错，而是 path、name、component 或 menu_url 没对上。',
          action: this.model.path || this.model.menu_url || this.model.component || '当前还没填入口去向'
        },
        {
          title: '第三步：最后处理权限开关',
          desc: '免登、免权、免限、隐藏这些都属于高影响控制项，应该放在入口承接正确之后调整。',
          action: `免登 ${this.model.is_unlogin ? '开' : '关'} / 免权 ${this.model.is_unauth ? '开' : '关'} / 隐藏 ${this.model.hidden ? '开' : '关'}`
        }
      ]
    },
    menuFollowupTags() {
      return [
        `已选菜单：${this.selection.length} 项`,
        `禁用节点：${this.currentPageDisabledCount} 项`,
        `隐藏节点：${this.currentPageHiddenCount} 项`,
        `筛选标签：${this.activeFilterTags.length} 项`
      ]
    },
    menuFollowupRiskText() {
      if (this.selection.length > 0) {
        return '菜单的隐藏、禁用、免权和角色解绑都会直接影响后台可见入口，提交前请确认当前勾选范围。'
      }
      if (this.currentPageDisabledCount > 0 || this.currentPageHiddenCount > 0) {
        return '当前列表里已有隐藏或禁用菜单，建议同步核对这些入口是否仍需要被角色或账号看到。'
      }
      return '菜单属于后台入口层，建议每次改完都顺手去角色页、用户页和日志页做一轮闭环确认。'
    },
    menuFollowupActionCards() {
      const baseQuery = {
        from: 'system-menu',
        source_count: this.count || 0
      }
      if (this.selection.length > 0) {
        return [
          {
            title: '去角色页复核授权',
            desc: '批量改完菜单后，先看角色页最容易发现哪些角色还在引用这些入口。',
            path: '/system/role',
            query: {
              ...baseQuery,
              menu_ids: this.selectIds
            }
          },
          {
            title: '去后台用户页核可见范围',
            desc: '菜单是否真的影响账号可见入口，继续去后台用户页最直观。',
            path: '/system/user',
            query: {
              ...baseQuery,
              menu_ids: this.selectIds
            }
          },
          {
            title: '去操作日志排查',
            desc: '如果菜单调整后出现走错页、没权限或白屏，日志页最适合继续看。',
            path: '/system/user-log',
            query: {
              ...baseQuery,
              menu_ids: this.selectIds
            }
          }
        ]
      }
      return [
        {
          title: '去角色页看菜单归属',
          desc: '先核对菜单到底挂在哪些角色上，再决定是解绑还是继续授权。',
          path: '/system/role',
          query: baseQuery
        },
        {
          title: '去后台用户页看实际账号',
          desc: '角色能看到菜单不等于账号都正常，去用户页继续看实际承接会更稳。',
          path: '/system/user',
          query: baseQuery
        },
        {
          title: '去操作日志看异常回显',
          desc: '涉及隐藏、禁用和免权时，日志能最快反馈是否影响了后台操作。',
          path: '/system/user-log',
          query: baseQuery
        }
      ]
    }
  },
  created() {
    this.height = screenHeight(290)
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

      const isDisable = this.parseRouteNumber(routeQuery.is_disable)
      if (isDisable !== undefined) nextQuery.is_disable = isDisable
      const menuPid = this.parseRouteNumber(routeQuery.menu_pid)
      if (menuPid !== undefined) nextQuery.menu_pid = menuPid
      const menuType = this.parseRouteNumber(routeQuery.menu_type)
      if (menuType !== undefined) nextQuery.menu_type = menuType
      const isUnlogin = this.parseRouteNumber(routeQuery.is_unlogin)
      if (isUnlogin !== undefined) nextQuery.is_unlogin = isUnlogin
      const isUnauth = this.parseRouteNumber(routeQuery.is_unauth)
      if (isUnauth !== undefined) nextQuery.is_unauth = isUnauth
      const isUnrate = this.parseRouteNumber(routeQuery.is_unrate)
      if (isUnrate !== undefined) nextQuery.is_unrate = isUnrate
      const hidden = this.parseRouteNumber(routeQuery.hidden)
      if (hidden !== undefined) nextQuery.hidden = hidden

      const menuId = this.parseRouteNumber(routeQuery.menu_id)
      if (menuId !== undefined) {
        nextQuery.search_field = this.idkey
        nextQuery.search_exp = '='
        nextQuery.search_value = String(menuId)
      } else {
        const menuIds = this.parseRouteArray(routeQuery.menu_ids)
        if (menuIds?.length) {
          nextQuery.search_field = this.idkey
          nextQuery.search_exp = 'in'
          nextQuery.search_value = menuIds.join(',')
        }
      }

      if (
        !nextQuery.search_value &&
        (
          routeQuery.menu_name ||
          routeQuery.menu_url ||
          routeQuery.username ||
          routeQuery.nickname ||
          routeQuery.role_name ||
          routeQuery.dept_name ||
          routeQuery.post_name
        )
      ) {
        nextQuery.search_value = String(
          routeQuery.menu_name ||
            routeQuery.menu_url ||
            routeQuery.username ||
            routeQuery.nickname ||
            routeQuery.role_name ||
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
        this.goToSystemPage('/system/user', this.buildEntryRouteQuery({}, 'system-menu'))
        return
      }
      if (this.entrySourceLabel === '来自部门管理') {
        this.goToSystemPage('/system/dept', this.buildEntryRouteQuery({}, 'system-menu'))
        return
      }
      if (this.entrySourceLabel === '来自职位管理') {
        this.goToSystemPage('/system/post', this.buildEntryRouteQuery({}, 'system-menu'))
        return
      }
      if (this.entrySourceLabel === '来自角色管理') {
        this.goToSystemPage('/system/role', this.buildEntryRouteQuery({}, 'system-menu'))
        return
      }
      this.goToSystemPage('/system/user-log', this.buildEntryRouteQuery({}, 'system-menu'))
    },
    goToEntryContextBack() {
      if (this.entrySourceLabel === '来自后台用户') {
        this.goToSystemPage('/system/user', this.buildEntryRouteQuery({}, 'system-menu'))
        return
      }
      if (this.entrySourceLabel === '来自部门管理') {
        this.goToSystemPage('/system/dept', this.buildEntryRouteQuery({}, 'system-menu'))
        return
      }
      if (this.entrySourceLabel === '来自职位管理') {
        this.goToSystemPage('/system/post', this.buildEntryRouteQuery({}, 'system-menu'))
        return
      }
      if (this.entrySourceLabel === '来自角色管理') {
        this.goToSystemPage('/system/role', this.buildEntryRouteQuery({}, 'system-menu'))
        return
      }
      if (this.entrySourceLabel === '来自用户日志') {
        this.goToSystemPage('/system/user-log', this.buildEntryRouteQuery({}, 'system-menu'))
      }
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
          this.setRecentActionSummary(
            `菜单列表已刷新，共 ${res.data.count || 0} 项，树节点 ${
              res.data.tree?.length || 0
            } 个。`
          )
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
      this.setRecentActionSummary(
        row ? `准备在菜单 ${row[this.idkey]} 下新增子节点。` : '准备新增顶级菜单节点。'
      )
      if (row) {
        this.model.menu_pid = row[this.idkey]
      }
    },
    edit(row) {
      this.dialog = true
      this.dialogTitle = this.name + '修改：' + row[this.idkey]
      this.setRecentActionSummary(`准备修改菜单：${row.menu_name || row[this.idkey]}。`)
      const id = {}
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
                  `已修改菜单：${this.model.menu_name || this.model[this.idkey]}。`
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
                this.setRecentActionSummary(`已新增菜单：${this.model.menu_name || '未命名菜单'}。`)
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
      this.model.add_info =
        this.model.add_add =
        this.model.add_edit =
        this.model.add_dele =
        this.model.add_disable =
          false
      this.model.edit_info =
        this.model.edit_add =
        this.model.edit_edit =
        this.model.edit_dele =
        this.model.edit_disable =
          false
    },
    // 查询
    search() {
      this.list()
    },
    // 刷新
    refresh() {
      this.query = this.$options.data().query
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
    getMenuLabel(row) {
      return row.menu_name || row.menu_url || row.path || `菜单#${row[this.idkey]}`
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
        if (selectType === 'remover') {
          this.selectTitle = this.name + '解除角色'
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
        } else if (selectType === 'hidden') {
          this.selectTitle = this.name + '是否隐藏'
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
        if (selectType === 'remover') {
          this.remover(this.selection)
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
        } else if (selectType === 'hidden') {
          this.ishidden(this.selection, true)
        } else if (selectType === 'disable') {
          this.disable(this.selection, true)
        } else if (selectType === 'dele') {
          this.dele(this.selection)
        }
        this.selectDialog = false
      }
    },
    // 解除角色
    remover(row) {
      if (!row.length) {
        this.selectAlert()
      } else {
        this.loading = true
        roleRemove({
          menu_id: this.selectGetIds(row),
          role_ids: []
        })
          .then((res) => {
            this.list()
            this.setRecentActionSummary('已批量解除菜单角色绑定。')
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
          const sortIncdec = this.sort_incdec
          this.list()
          this.sort_incdec = '0'
          this.setRecentActionSummary(
            `已批量修改菜单排序，基准值 ${this.sort}，增减步长 ${sortIncdec || '0'}。`
          )
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
        menu_pid: this.menu_pid
      })
        .then((res) => {
          this.list()
          this.setRecentActionSummary(`已批量修改菜单上级，目标上级：${this.menu_pid || '顶级'}。`)
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
        let is_unlogin = row[0].is_unlogin
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
              `已批量调整免登状态：${is_unlogin === 1 ? '开启' : '关闭'}。`
            )
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.list()
          })
      }
    },
    handleUnloginSwitch(row, value) {
      ElMessageBox.confirm(
        `确认要将「${this.getMenuLabel(row)}」的免登改为${value === 1 ? '开启' : '关闭'}吗？`,
        '操作确认',
        {
          type: 'warning',
          confirmButtonText: '继续',
          cancelButtonText: '取消'
        }
      )
        .then(() => {
          this.unlogin([{ ...row, is_unlogin: value }])
        })
        .catch(() => {})
    },
    // 是否免权
    unauth(row, select = false) {
      if (!row.length) {
        this.selectAlert()
      } else {
        this.loading = true
        let is_unauth = row[0].is_unauth
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
              `已批量调整免权状态：${is_unauth === 1 ? '开启' : '关闭'}。`
            )
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.list()
          })
      }
    },
    handleUnauthSwitch(row, value) {
      ElMessageBox.confirm(
        `确认要将「${this.getMenuLabel(row)}」的免权改为${value === 1 ? '开启' : '关闭'}吗？`,
        '操作确认',
        {
          type: 'warning',
          confirmButtonText: '继续',
          cancelButtonText: '取消'
        }
      )
        .then(() => {
          this.unauth([{ ...row, is_unauth: value }])
        })
        .catch(() => {})
    },
    // 是否免限
    unrate(row, select = false) {
      if (!row.length) {
        this.selectAlert()
      } else {
        this.loading = true
        let is_unrate = row[0].is_unrate
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
              `已批量调整免限状态：${is_unrate === 1 ? '开启' : '关闭'}。`
            )
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.list()
          })
      }
    },
    handleUnrateSwitch(row, value) {
      ElMessageBox.confirm(
        `确认要将「${this.getMenuLabel(row)}」的免限改为${value === 1 ? '开启' : '关闭'}吗？`,
        '操作确认',
        {
          type: 'warning',
          confirmButtonText: '继续',
          cancelButtonText: '取消'
        }
      )
        .then(() => {
          this.unrate([{ ...row, is_unrate: value }])
        })
        .catch(() => {})
    },
    // 是否隐藏
    ishidden(row, select = false) {
      if (!row.length) {
        this.selectAlert()
      } else {
        this.loading = true
        let hidden = row[0].hidden
        if (select) {
          hidden = this.hidden
        }
        ishidden({
          ids: this.selectGetIds(row),
          hidden: hidden
        })
          .then((res) => {
            this.list()
            this.setRecentActionSummary(
              `已批量调整菜单隐藏状态：${hidden === 1 ? '隐藏' : '显示'}。`
            )
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.list()
          })
      }
    },
    handleHiddenSwitch(row, value) {
      ElMessageBox.confirm(
        `确认要将「${this.getMenuLabel(row)}」改为${value === 1 ? '隐藏' : '显示'}吗？`,
        '操作确认',
        {
          type: 'warning',
          confirmButtonText: '继续',
          cancelButtonText: '取消'
        }
      )
        .then(() => {
          this.ishidden([{ ...row, hidden: value }])
        })
        .catch(() => {})
    },
    // 是否禁用
    disable(row, select = false) {
      if (!row.length) {
        this.selectAlert()
      } else {
        this.loading = true
        let is_disable = row[0].is_disable
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
              `已批量调整菜单禁用状态：${is_disable === 1 ? '禁用' : '启用'}。`
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
        `确认要${value === 1 ? '禁用' : '启用'}菜单「${this.getMenuLabel(row)}」吗？`,
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
            this.setRecentActionSummary('已批量删除菜单节点。')
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.loading = false
          })
      }
    },
    // 角色显示
    roleShow(row) {
      this.roleDialog = true
      this.roleDialogTitle = this.name + this.roleName + '：' + row.menu_name
      this.roleQuery.menu_id = row.menu_id
      this.roleQuery.search_value = ''
      this.roleList()
    },
    // 角色列表
    roleList() {
      this.roleLoad = true
      role(this.roleQuery)
        .then((res) => {
          this.roleData = res.data.list
          this.roleCount = res.data.count
          this.roleLoad = false
        })
        .catch(() => {
          this.roleLoad = false
        })
    },
    // 角色排序
    roleSort(sort) {
      this.roleQuery.sort_field = sort.prop
      this.roleQuery.sort_value = ''
      if (sort.order === 'ascending') {
        this.roleQuery.sort_value = 'asc'
        this.roleList()
      }
      if (sort.order === 'descending') {
        this.roleQuery.sort_value = 'desc'
        this.roleList()
      }
    },
    // 角色操作
    roleSelect(selection) {
      this.roleSelection = selection
      this.roleSelectIds = this.roleSelectGetIds(selection).toString()
    },
    roleSelectGetIds(selection) {
      return arrayColumn(selection, this.rolePk)
    },
    roleSelectAlert() {
      ElMessageBox.alert('请选择需要操作的' + this.roleName, '提示', {
        type: 'warning',
        callback: () => {}
      })
    },
    roleSelectOpen(selectType, selectRow = '') {
      if (selectRow) {
        this.$refs['roleRef'].clearSelection()
        this.$refs['roleRef'].toggleRowSelection(selectRow)
      }
      if (!this.roleSelection.length) {
        this.roleSelectAlert()
      } else {
        this.roleSelectTitle = '操作'
        if (selectType === 'roleRemove') {
          this.roleSelectTitle = this.name + '解除' + this.roleName
        }
        this.roleSelectDialog = true
        this.roleSelectType = selectType
      }
    },
    roleSelectCancel() {
      this.roleSelectDialog = false
    },
    roleSelectSubmit() {
      if (!this.roleSelection.length) {
        this.roleSelectAlert()
      } else {
        const selectType = this.roleSelectType
        if (selectType === 'roleRemove') {
          this.roleRemove(this.roleSelection)
        }
        this.roleSelectDialog = false
      }
    },
    // 角色解除
    roleRemove(row) {
      if (!row.length) {
        this.roleSelectAlert()
      } else {
        this.roleLoad = true
        roleRemove({
          menu_id: this.roleQuery.menu_id,
          role_ids: this.roleSelectGetIds(row)
        })
          .then((res) => {
            this.roleList()
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.roleLoad = false
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

.menu-summary-bar {
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

.menu-summary-bar__chips {
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

.menu-summary-bar__hint {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 240px;
  padding: 12px 14px;
  border-radius: 12px;
  background: #eef4ff;
  color: #1d4ed8;
}

.followup-panel {
  margin-top: 16px;
  padding: 14px 16px;
  border: 1px solid #dbe7f5;
  border-radius: 14px;
  background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
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

.menu-summary-bar__hint.is-safe {
  background: #eaf8ef;
  color: #15803d;
}

.menu-summary-bar__hint.is-warning {
  background: #fff5e8;
  color: #b45309;
}

.menu-summary-bar__hint.is-active {
  background: #e8f0ff;
  color: #1d4ed8;
}

.menu-summary-bar__hint-title {
  font-size: 12px;
  font-weight: 700;
}

.menu-summary-bar__hint-text {
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
  white-space: nowrap;
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

@media (max-width: 900px) {
  .entry-context-banner,
  .section-title-row,
  .menu-summary-bar,
  .plain-guide__header,
  .dialog-plain-guide__header,
  .followup-panel__header {
    flex-direction: column;
  }

  .section-title-row__meta {
    white-space: normal;
  }
}
</style>
