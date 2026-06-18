<template>
  <div class="app-container">
    <div class="page-light-header">
      <div class="page-light-header__main">
        <div>
          <div class="page-light-header__title">巡检菜单</div>
          <div class="page-light-header__desc">
            保留树形菜单维护、角色承接、批量调整与确认式提交能力，首屏改为更轻量的后台操作模式。
          </div>
        </div>
        <div class="page-light-header__meta">
          <el-tag :type="runtimeEnvInfo.tone">{{ runtimeEnvInfo.label }}</el-tag>
          <span class="page-light-header__hint">{{ runtimeHint }}</span>
        </div>
      </div>
      <div class="summary-bar">
        <div v-for="item in summaryBarItems" :key="item.label" class="summary-bar__item">
          <span class="summary-bar__label">{{ item.label }}</span>
          <span class="summary-bar__value">{{ item.value }}</span>
        </div>
      </div>
      <div class="summary-bar summary-bar--risk">
        <div class="summary-bar__item summary-bar__item--risk">
          <span class="summary-bar__label">风险提示</span>
          <span class="summary-bar__value">{{ menuSubmitRisk }}</span>
        </div>
      </div>
    </div>
    <el-card class="app-head head-pb20">
      <div class="section-title-row">
        <div>
          <div class="section-title-row__title">菜单筛选</div>
          <div class="section-title-row__desc">
            保留原有树形菜单维护、角色承接和批量开关能力，补齐统一后台壳子。
          </div>
        </div>
        <div class="section-title-row__meta">当前检索：{{ currentSearchLabel }}</div>
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
      <div v-if="activeFilterTags.length" class="active-filter-strip">
        <span class="active-filter-strip__label">当前筛选</span>
        <span v-for="item in activeFilterTags" :key="item" class="active-filter-strip__item">{{
          item
        }}</span>
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

    <el-card class="app-main">
      <div class="section-title section-title--compact">
        <div>
          <h3>菜单列表</h3>
          <p>支持菜单树批量维护、角色解除和双击复制字段值。</p>
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
} from '@/api/inspection/menu'
import { shortcuts } from '@/utils/getDate.js'
import { getAdminRuntimeHint, resolveAdminRuntimeEnv } from '@/utils/runtime-env'

export default {
  name: 'InspectionMenu',
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
    runtimeHint() {
      return getAdminRuntimeHint(this.runtimeEnvInfo)
    },
    activeFilterTags() {
      const tags = []
      if (this.query.search_value)
        tags.push(`${this.currentSearchLabel}：${this.query.search_value}`)
      if (this.query.is_disable === 0) tags.push('状态：启用')
      else if (this.query.is_disable === 1) tags.push('状态：禁用')
      if (this.query.menu_type === 0) tags.push('类型：目录')
      else if (this.query.menu_type === 1) tags.push('类型：菜单')
      else if (this.query.menu_type === 2) tags.push('类型：按钮')
      if (this.query.is_unlogin === 1) tags.push('免登：是')
      else if (this.query.is_unlogin === 0) tags.push('免登：否')
      if (this.query.is_unauth === 1) tags.push('免权：是')
      else if (this.query.is_unauth === 0) tags.push('免权：否')
      if (this.query.is_unrate === 1) tags.push('免限：是')
      else if (this.query.is_unrate === 0) tags.push('免限：否')
      if (this.query.hidden === 1) tags.push('隐藏：是')
      else if (this.query.hidden === 0) tags.push('隐藏：否')
      if (this.query.date_value && this.query.date_value.length === 2) {
        tags.push(`添加时间：${this.query.date_value[0]} 至 ${this.query.date_value[1]}`)
      }
      return tags
    },
    summaryFilterText() {
      if (this.activeFilterTags.length) {
        return this.activeFilterTags.join(' / ')
      }
      return '未设置筛选条件'
    },
    treeStateText() {
      return this.isExpandAll ? '当前收起模式' : '当前展开模式'
    },
    menuActionSummary() {
      if (this.selection.length) {
        return `已勾选 ${this.selection.length} 个菜单节点`
      }
      return this.recentActionSummary || '菜单巡检中'
    },
    summaryBarItems() {
      return [
        { label: '总量', value: `${this.count || 0} 项` },
        { label: '筛选', value: this.summaryFilterText },
        { label: '已选', value: `${this.selection.length} 项` },
        { label: '树形状态', value: this.treeStateText },
        { label: '当前操作', value: this.menuActionSummary },
        { label: '数据模式', value: this.runtimeEnvInfo.dataMode }
      ]
    },
    menuSubmitRisk() {
      return this.runtimeEnvInfo.isProd
        ? '正式环境下修改菜单树、权限豁免、隐藏和禁用会直接影响巡检端导航与权限链路，提交前请务必复核。'
        : '当前环境适合验证菜单树结构、权限豁免和角色承接链路，确认无误后再切正式环境。'
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
      runtimeEnvInfo: resolveAdminRuntimeEnv(),
      recentActionSummary: ''
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
          this.recentActionSummary = `已刷新巡检菜单树，共 ${res.data.count || 0} 个节点`
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
      this.recentActionSummary = row
        ? `已打开菜单 ${row[this.idkey]} 的下级新增弹窗`
        : '已打开巡检菜单新增弹窗'
      this.reset()
      if (row) {
        this.model.menu_pid = row[this.idkey]
      }
    },
    edit(row) {
      this.dialog = true
      this.dialogTitle = this.name + '修改：' + row[this.idkey]
      this.recentActionSummary = `已打开菜单 ${row[this.idkey]} 的编辑弹窗`
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
                this.recentActionSummary = `已提交菜单 ${this.model[this.idkey]} 的修改`
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
                this.recentActionSummary = '已提交新增巡检菜单'
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
      this.recentActionSummary = '已重置巡检菜单筛选条件'
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
      this.recentActionSummary = selection.length
        ? `已勾选 ${selection.length} 个菜单节点待处理`
        : '已清空菜单节点勾选'
    },
    selectAll(selection) {
      if (selection) {
        this.selection = []
        this.selectAllKeys(selection)
        this.selectIds = this.selectGetIds(this.selection).toString()
        this.recentActionSummary = `已全选 ${this.selection.length} 个菜单节点`
      } else {
        this.selection = []
        this.selectIds = ''
        this.recentActionSummary = '已取消菜单节点全选'
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
          this.recentActionSummary = `已批量修改 ${row.length} 个菜单节点的排序`
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
          this.recentActionSummary = `已批量调整 ${row.length} 个菜单节点的上级关系`
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
            this.recentActionSummary = select
              ? `已批量更新 ${row.length} 个菜单节点的免登状态`
              : `已更新菜单 ${this.selectGetIds(row).join(',')} 的免登状态`
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
            this.recentActionSummary = select
              ? `已批量更新 ${row.length} 个菜单节点的免权状态`
              : `已更新菜单 ${this.selectGetIds(row).join(',')} 的免权状态`
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
            this.recentActionSummary = select
              ? `已批量更新 ${row.length} 个菜单节点的免限状态`
              : `已更新菜单 ${this.selectGetIds(row).join(',')} 的免限状态`
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
            this.recentActionSummary = select
              ? `已批量更新 ${row.length} 个菜单节点的隐藏状态`
              : `已更新菜单 ${this.selectGetIds(row).join(',')} 的隐藏状态`
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
            this.recentActionSummary = select
              ? `已批量更新 ${row.length} 个菜单节点的禁用状态`
              : `已更新菜单 ${this.selectGetIds(row).join(',')} 的禁用状态`
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
            this.recentActionSummary = `已删除 ${row.length} 个菜单节点`
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
      this.recentActionSummary = `已打开菜单 ${row.menu_name} 的角色承接列表`
      this.roleList()
    },
    // 角色列表
    roleList() {
      this.roleLoad = true
      role(this.roleQuery)
        .then((res) => {
          this.roleData = res.data.list
          this.roleCount = res.data.count
          this.recentActionSummary = `已刷新菜单角色列表，共 ${res.data.count || 0} 条关联`
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
      this.recentActionSummary = selection.length
        ? `已勾选 ${selection.length} 个角色待解除`
        : '已清空角色勾选'
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
            this.recentActionSummary = `已解除 ${row.length} 个角色的菜单承接关系`
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
.page-light-header {
  margin-bottom: 12px;
}

.page-light-header__main {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 10px;
}

.page-light-header__title {
  font-size: 20px;
  font-weight: 700;
  color: #1f2329;
}

.page-light-header__desc,
.page-light-header__hint {
  margin-top: 4px;
  color: #5f6b7a;
  line-height: 1.6;
  font-size: 13px;
}

.page-light-header__meta {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: flex-end;
  gap: 10px;
}

.summary-bar {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.summary-bar--risk {
  margin-top: 8px;
}

.summary-bar__item {
  display: flex;
  align-items: center;
  gap: 8px;
  min-height: 32px;
  padding: 6px 12px;
  border-radius: 10px;
  background: #f7f9fc;
  border: 1px solid #e8edf5;
  color: #445066;
  font-size: 12px;
  line-height: 1.5;
}

.summary-bar__item--risk {
  width: 100%;
  align-items: flex-start;
  background: #fff9f2;
  border-color: #f4dfbf;
}

.summary-bar__label,
.active-filter-strip__label {
  font-size: 12px;
  font-weight: 700;
  color: #66758c;
}

.summary-bar__value {
  color: #1f2329;
}

.active-filter-strip__item {
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  padding: 0 10px;
  border-radius: 999px;
  background: #f5f7fb;
  color: #4a5670;
  font-size: 12px;
}

.active-filter-strip {
  margin-top: 14px;
  border-radius: 12px;
  padding: 14px 16px;
  background: #f8fbff;
  border: 1px solid #e8eef8;
}

@media (max-width: 900px) {
  .page-light-header__main {
    flex-direction: column;
    align-items: flex-start;
  }

  .page-light-header__meta {
    justify-content: flex-start;
  }
}
</style>
