<template>
  <div class="app-container">
    <el-card  class="app-head head-pb20">
      <div class="section-title-row">
        <div>
          <div class="section-title-row__title">菜单筛选</div>
          <div class="section-title-row__desc">集中处理商家端目录、菜单和按钮权限，先筛选再做树形维护与批量调整。</div>
        </div>
        <div class="section-title-row__meta">总数 {{ count || 0 }}</div>
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
              <el-select
                  v-model="query.menu_type"
                  clearable
                  filterable
                  @change="search()"
              >
                <el-option :value="0" label="目录" />
                <el-option :value="1" label="菜单" />
                <el-option :value="2" label="按钮" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="免登：" prop="is_unlogin">
              <el-select
                  v-model="query.is_unlogin"
                  @change="search()"
                  clearable
              >
                <el-option :value="1" label="是" />
                <el-option :value="0" label="否" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="免权：" prop="is_unauth">
              <el-select
                  v-model="query.is_unauth"
                  @change="search()"
                  clearable
              >
                <el-option :value="1" label="是" />
                <el-option :value="0" label="否" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="免限：" prop="is_unrate">
              <el-select
                  v-model="query.is_unrate"
                  @change="search()"
                  clearable
              >
                <el-option :value="1" label="是" />
                <el-option :value="0" label="否" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="隐藏：" prop="hidden">
              <el-select
                  v-model="query.hidden"
                  @change="search()"
                  clearable
              >
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
            <el-button title="重置" @click="refresh()">
              重置
            </el-button>
          </el-col>
        </el-row>
      </el-form>

      <div class="menu-summary-bar">
        <div class="menu-summary-bar__chips">
          <span class="summary-chip summary-chip--primary">{{ currentSearchLabel }}</span>
          <span class="summary-chip">已选 {{ selection.length }} 项</span>
          <span class="summary-chip">{{ isExpandAll ? '当前收起模式' : '当前展开模式' }}</span>
          <span v-for="item in activeFilterTags" :key="item" class="summary-chip">{{ item }}</span>
          <span v-if="!activeFilterTags.length" class="summary-chip">未设置筛选条件</span>
          <span v-if="recentActionSummary" class="summary-chip summary-chip--muted">{{ recentActionSummary }}</span>
        </div>
        <div class="menu-summary-bar__hint">
          <span class="menu-summary-bar__hint-title">{{ selectionActionHint }}</span>
          <span class="menu-summary-bar__hint-text">{{ riskHintText }}</span>
        </div>
      </div>
      <div class="merchant-menu-guide">
        <div class="merchant-menu-guide__header">
          <div>
            <div class="merchant-menu-guide__title">商家端菜单第一次排查，建议先这样看</div>
            <div class="merchant-menu-guide__desc">这页管的是商家后台的目录、菜单和按钮权限。先确认入口树，再动隐藏、禁用和免权开关。</div>
          </div>
          <div class="merchant-menu-guide__badge">{{ menuGuideFocusLabel }}</div>
        </div>
        <div class="merchant-menu-guide__grid">
          <div v-for="item in menuGuideCards" :key="item.title" class="merchant-menu-guide-card">
            <span class="merchant-menu-guide-card__step">{{ item.step }}</span>
            <div class="merchant-menu-guide-card__title">{{ item.title }}</div>
            <div class="merchant-menu-guide-card__desc">{{ item.desc }}</div>
          </div>
        </div>
      </div>
      <div class="merchant-menu-followup">
        <div class="merchant-menu-followup__main">
          <div class="merchant-menu-followup__title">菜单树改完后，建议继续这样复核</div>
          <div class="merchant-menu-followup__desc">
            菜单配置页只能说明树结构已经改了，不能直接证明商家端入口已经正常承接。批量改完隐藏、禁用或免权后，最好继续去商家管理和操作日志交叉确认。
          </div>
        </div>
        <div class="merchant-menu-followup__actions">
          <el-button text type="primary" @click="goToPage('/merchant/merchant')">去商家管理</el-button>
          <el-button text type="primary" @click="goToPage('/system/user-log')">去操作日志</el-button>
          <el-button text type="primary" @click="goToPage('/dashboard')">回控制台</el-button>
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

    <el-card  class="app-main">
      <div class="section-title section-title--compact">
        <div>
          <h3>菜单列表</h3>
          <p>支持菜单树批量维护、角色解除和字段双击复制。</p>
        </div>
      </div>
      <div class="operation_btn mb5">
        <!-- 操作 -->
        <div>
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
          <el-button title="删除" @click="selectOpen('dele')">删除</el-button>
          <el-button title="是否禁用" @click="selectOpen('disable')">禁用</el-button>
          <el-button title="修改上级" @click="selectOpen('editpid')">上级</el-button>
          <el-button title="是否免登" @click="selectOpen('unlogin')">免登</el-button>
          <el-button title="是否免权" @click="selectOpen('unauth')">免权</el-button>
          <el-button title="是否免限" @click="selectOpen('unrate')">免限</el-button>
          <el-button title="是否隐藏" @click="selectOpen('hidden')">隐藏</el-button>
          <el-button title="修改排序" @click="selectOpen('editsort')">排序</el-button>
          <el-button title="解除角色" @click="selectOpen('remover')">解除角色</el-button>
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
          size="small"
          class="menu-table"
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
                v-model="scope.row.is_unlogin"
                :width="35"
                :active-value="1"
                :inactive-value="0"
                @change="unlogin([scope.row])"
            />
            <span v-else></span>
          </template>
        </el-table-column>
        <el-table-column prop="is_unauth" label="免权" min-width="66">
          <template #default="scope">
            <el-switch
                v-if="scope.row.menu_url"
                v-model="scope.row.is_unauth"
                :width="35"
                :active-value="1"
                :inactive-value="0"
                @change="unauth([scope.row])"
            />
            <span v-else></span>
          </template>
        </el-table-column>
        <el-table-column prop="is_unrate" label="免限" min-width="66">
          <template #default="scope">
            <el-switch
                v-if="scope.row.menu_url"
                v-model="scope.row.is_unrate"
                :width="35"
                :active-value="1"
                :inactive-value="0"
                @change="unrate([scope.row])"
            />
            <span v-else></span>
          </template>
        </el-table-column>
        <el-table-column prop="is_disable" label="禁用" min-width="66">
          <template #default="scope">
            <el-switch
                v-if="scope.row.menu_url"
                v-model="scope.row.is_disable"
                :width="35"
                :active-value="1"
                :inactive-value="0"
                @change="disable([scope.row])"
            />
            <span v-else></span>
          </template>
        </el-table-column>
        <el-table-column prop="hidden" label="隐藏" min-width="66">
          <template #default="scope">
            <el-switch
                v-model="scope.row.hidden"
                :width="35"
                :active-value="1"
                :inactive-value="0"
                @change="ishidden([scope.row])"
            />
          </template>
        </el-table-column>
        <el-table-column :prop="idkey" label="ID" min-width="80" />
        <el-table-column prop="sort" label="排序" min-width="80" />
        <el-table-column label="操作" width="170">
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
            <el-link type="primary" :underline="false" @click="selectOpen('dele', scope.row)">
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
              <el-input v-model="model.menu_name" clearable placeholder="meta.title；侧边栏菜单名称">
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
} from '@/api/merchant/menu'
import {shortcuts} from "@/utils/getDate.js";

export default {
  name: 'MerchantMenu',
  components: { Pagination },
  computed: {
    currentSearchLabel() {
      const fieldMap = {
        menu_id: 'ID',
        menu_name: '名称',
        menu_url: '链接'
      }
      return fieldMap[this.query.search_field] || '综合筛选'
    },
    activeFilterTags() {
      const tags = []
      if (this.query.search_value) tags.push(`检索：${this.query.search_value}`)
      if (this.query.menu_type !== undefined && this.query.menu_type !== '') {
        const typeMap = { 0: '目录', 1: '菜单', 2: '按钮' }
        tags.push(`类型：${typeMap[this.query.menu_type] || this.query.menu_type}`)
      }
      if (this.query.is_disable !== undefined && this.query.is_disable !== '') tags.push(`状态：${Number(this.query.is_disable) === 1 ? '禁用' : '启用'}`)
      if (this.query.is_unlogin !== undefined && this.query.is_unlogin !== '') tags.push(`免登：${Number(this.query.is_unlogin) === 1 ? '是' : '否'}`)
      if (this.query.is_unauth !== undefined && this.query.is_unauth !== '') tags.push(`免权：${Number(this.query.is_unauth) === 1 ? '是' : '否'}`)
      if (this.query.is_unrate !== undefined && this.query.is_unrate !== '') tags.push(`免限：${Number(this.query.is_unrate) === 1 ? '是' : '否'}`)
      if (this.query.hidden !== undefined && this.query.hidden !== '') tags.push(`隐藏：${Number(this.query.hidden) === 1 ? '是' : '否'}`)
      if (this.query.menu_pid) tags.push(`上级：菜单 #${this.query.menu_pid}`)
      if (Array.isArray(this.query.date_value) && this.query.date_value.length === 2) tags.push(`时间：${this.query.date_value[0]} 至 ${this.query.date_value[1]}`)
      return tags
    },
    selectionReviewTags() {
      const tags = [`已选菜单：${this.selection.length} 项`]
      if (this.selection.length) {
        const menuCount = this.selection.filter((item) => Number(item.menu_type) === 1).length
        const buttonCount = this.selection.filter((item) => Number(item.menu_type) === 2).length
        const disabledCount = this.selection.filter((item) => Number(item.is_disable) === 1).length
        const hiddenCount = this.selection.filter((item) => Number(item.hidden) === 1).length
        if (menuCount) tags.push(`菜单：${menuCount} 项`)
        if (buttonCount) tags.push(`按钮：${buttonCount} 项`)
        if (disabledCount) tags.push(`已禁用：${disabledCount} 项`)
        if (hiddenCount) tags.push(`已隐藏：${hiddenCount} 项`)
      }
      return tags
    },
    selectionActionHint() {
      if (!this.selection.length) {
        return '未勾选菜单时仅支持筛选浏览；如需批量调整免登、免权、免限、隐藏、排序或上级，请先勾选目标菜单。'
      }
      return `已勾选 ${this.selection.length} 项菜单，可继续执行批量开关、排序调整、上级迁移或角色解除。`
    },
    menuGuideFocusLabel() {
      if (this.selection.length) {
        return `先确认这 ${this.selection.length} 项是否属于同一层级或同一角色范围`
      }
      if (this.query.hidden === 1) {
        return '先核对被隐藏的商家端入口是否还需要恢复显示'
      }
      if (this.query.is_disable === 1) {
        return '先看禁用菜单是不是历史入口，避免误启用'
      }
      return '先分清目录、菜单、按钮，再调权限开关'
    },
    menuGuideCards() {
      return [
        {
          step: '第一步',
          title: '先筛类型和上级，定位是哪一层入口',
          desc: '目录、菜单、按钮的处理方式不一样，先把范围缩小到一层再改最安全。'
        },
        {
          step: '第二步',
          title: '再决定改显示还是改权限',
          desc: '隐藏、禁用更多是入口展示问题；免登、免权、免限则会影响真实访问控制。'
        },
        {
          step: '第三步',
          title: '最后回角色和商家端页面复核',
          desc: '菜单树改完不代表商家端就一定正常，最好再去角色承接和真实页面入口核一遍。'
        }
      ]
    },
    riskHintText() {
      if (this.selection.some((item) => Number(item.menu_type) === 2)) {
        return '当前勾选中包含按钮权限，批量解除角色或调整免权前，建议先核对角色菜单承接，避免商家端入口缺失。'
      }
      if (this.selection.some((item) => Number(item.hidden) === 1)) {
        return '当前勾选中包含隐藏菜单，恢复显示前请确认路由、组件地址和上级目录已对齐。'
      }
      if (this.selection.some((item) => Number(item.is_disable) === 1)) {
        return '当前勾选中包含已禁用菜单，如需重新启用，建议先核对是否需要同步恢复免登、免权和免限状态。'
      }
      return '建议先缩小到同类型节点后再做批量排序、上级调整和权限开关，避免整棵树误操作。'
    },
    recentActionSummary() {
      if (!this.recentAction) return ''
      const time = this.recentAction.time || ''
      return `${this.recentAction.label}${time ? ` · ${time}` : ''}`
    }
  },
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
        is_disable:undefined,
        menu_pid:undefined,
        menu_type:undefined,
        is_unlogin:undefined,
        is_unauth:undefined,
        is_unrate:undefined,
        hidden:undefined,
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
      shortcuts:shortcuts(),
      recentAction: null,
    }
  },
  created() {
    this.height = screenHeight(290)
    this.list()
  },
  methods: {
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
        this.model.menu_pid = row[this.idkey]
      }
    },
    edit(row) {
      this.dialog = true
      this.dialogTitle = this.name + '修改：' + row[this.idkey]
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
                  this.setRecentAction(`菜单修改已提交：${this.model.menu_name || this.model[this.idkey]}`)
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
                  this.setRecentAction(`菜单新增已提交：${this.model.menu_name || '新菜单'}`)
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
    goToPage(path, extraQuery = {}) {
      this.$router.push({
        path,
        query: {
          from: 'merchant-menu',
          ...extraQuery
        }
      })
    },
    setRecentAction(label) {
      this.recentAction = {
        label,
        time: new Date().toLocaleString('zh-CN', { hour12: false })
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
              this.setRecentAction(`菜单角色已解除：${row.length} 项`)
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
            this.setRecentAction(`菜单排序已调整：${row.length} 项`)
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
            this.setRecentAction(`菜单上级已调整：${row.length} 项`)
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
              this.setRecentAction(`菜单免登已更新：${row.length} 项`)
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
              this.setRecentAction(`菜单免权已更新：${row.length} 项`)
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
              this.setRecentAction(`菜单免限已更新：${row.length} 项`)
              ElMessage.success(res.msg)
            })
            .catch(() => {
              this.list()
            })
      }
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
              this.setRecentAction(`菜单隐藏状态已更新：${row.length} 项`)
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
              this.setRecentAction(`菜单禁用状态已更新：${row.length} 项`)
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
              this.setRecentAction(`菜单删除已提交：${row.length} 项`)
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
              this.setRecentAction(`角色解除已提交：${row.length} 项`)
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

<style scoped>
.menu-summary-bar {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 14px;
  margin-top: 16px;
}

.menu-summary-bar__chips {
  display: flex;
  flex: 1;
  flex-wrap: wrap;
  gap: 8px;
}

.summary-chip {
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  padding: 0 10px;
  border-radius: 999px;
  font-size: 12px;
  background: #f5f7fb;
  color: #4a5670;
}

.summary-chip--primary {
  background: #e8f0ff;
  color: #1d4ed8;
  font-weight: 600;
}

.summary-chip--muted {
  color: #64748b;
}

.menu-summary-bar__hint {
  width: 320px;
  padding: 12px 14px;
  border-radius: 12px;
  border: 1px solid #f3ddab;
  background: #fff9f0;
}

.menu-summary-bar__hint-title {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: #1f2329;
}

.menu-summary-bar__hint-text {
  display: block;
  margin-top: 6px;
  font-size: 12px;
  color: #5f6b7a;
  line-height: 1.6;
}

.merchant-menu-guide {
  margin-top: 16px;
  padding: 16px;
  border: 1px solid #dbe7f5;
  border-radius: 16px;
  background: linear-gradient(135deg, #f7fbff 0%, #ffffff 100%);
}

.merchant-menu-followup {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  margin-top: 16px;
  padding: 14px 16px;
  border: 1px solid #dbe7f5;
  border-radius: 14px;
  background: #fff;
}

.merchant-menu-followup__title {
  font-size: 14px;
  font-weight: 700;
  color: #10233f;
}

.merchant-menu-followup__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.merchant-menu-followup__actions {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 2px;
}

.merchant-menu-guide__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.merchant-menu-guide__title {
  font-size: 16px;
  font-weight: 700;
  color: #10233f;
}

.merchant-menu-guide__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.merchant-menu-guide__badge {
  min-width: 220px;
  padding: 10px 12px;
  border-radius: 12px;
  background: rgba(37, 99, 235, 0.08);
  color: #1d4ed8;
  font-size: 12px;
  font-weight: 700;
  line-height: 1.6;
}

.merchant-menu-guide__grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-top: 14px;
}

.merchant-menu-guide-card {
  padding: 14px 16px;
  border: 1px solid #e7edf5;
  border-radius: 14px;
  background: #fff;
}

.merchant-menu-guide-card__step {
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

.merchant-menu-guide-card__title {
  margin-top: 10px;
  font-size: 14px;
  font-weight: 700;
  color: #10233f;
}

.merchant-menu-guide-card__desc {
  margin-top: 8px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.menu-table :deep(.el-table__cell) {
  padding: 8px 0;
}

.menu-table :deep(.el-table__body .cell) {
  line-height: 1.45;
}

@media (max-width: 1200px) {
  .menu-summary-bar,
  .merchant-menu-guide__header,
  .merchant-menu-followup {
    flex-direction: column;
    align-items: stretch;
  }

  .menu-summary-bar__hint,
  .merchant-menu-guide__badge {
    width: auto;
    min-width: 0;
  }

  .merchant-menu-guide__grid {
    grid-template-columns: 1fr;
  }
}
</style>
