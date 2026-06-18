<template>
  <div class="app-container">
    <el-card  class="app-head head-pb20">
      <div class="section-title-row">
        <div>
          <div class="section-title-row__title">内容分类管理</div>
          <div class="section-title-row__desc">保留树形分类、批量维护和内容承接能力，默认首屏更简洁直达。</div>
        </div>
        <div class="section-title-row__meta">
          <span class="section-title-row__meta-text">{{ runtimeHint }}</span>
          <el-tag :type="runtimeEnvInfo.tone" effect="light">{{ runtimeEnvInfo.label }}</el-tag>
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
<!--          <el-col :span="6">-->
<!--            <el-form-item label="上级：" prop="category_pid">-->
<!--            <el-cascader-->
<!--                v-model="query.category_pid"-->
<!--                :options="trees"-->
<!--                :props="props"-->
<!--                clearable-->
<!--                filterable-->
<!--                style="width: 100%;"-->
<!--            />-->
<!--            </el-form-item>-->
<!--          </el-col>-->
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
                  <el-option value="category_unique" label="编码" />
                  <el-option value="category_name" label="名称" />
<!--                  <el-option value="category_pid" label="上级" />-->
<!--                  <el-option value="title" label="标题" />-->
<!--                  <el-option value="keywords" label="关键词" />-->
<!--                  <el-option value="description" label="描述" />-->
<!--                  <el-option value="remark" label="备注" />-->
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
      <div class="summary-bar">
        <div class="summary-bar__item">
          <span class="summary-bar__label">分类总量</span>
          <strong class="summary-bar__value">{{ count || 0 }}</strong>
        </div>
        <div class="summary-bar__item">
          <span class="summary-bar__label">当前检索</span>
          <strong class="summary-bar__value">{{ currentSearchLabel }}</strong>
        </div>
        <div class="summary-bar__item">
          <span class="summary-bar__label">关键词</span>
          <strong class="summary-bar__value">{{ query.search_value || '未设置' }}</strong>
        </div>
        <div class="summary-bar__item">
          <span class="summary-bar__label">已选</span>
          <strong class="summary-bar__value">{{ selection.length }} 项</strong>
        </div>
        <div class="summary-bar__item">
          <span class="summary-bar__label">当前操作</span>
          <strong class="summary-bar__value">{{ categoryActionSummary }}</strong>
        </div>
      </div>
      <div class="category-guide-panel">
        <div class="category-guide-panel__header">
          <div>
            <div class="category-guide-panel__title">内容分类第一次排查，建议先这样看</div>
            <div class="category-guide-panel__desc">先确认这是“栏目结构问题”还是“内容归属问题”。分类页主要管结构，具体内容是否正确要回内容管理继续看。</div>
          </div>
          <div class="category-guide-panel__badge">{{ categoryGuideFocusLabel }}</div>
        </div>
        <div class="category-guide-panel__grid">
          <div v-for="item in categoryGuideCards" :key="item.title" class="category-guide-card">
            <span class="category-guide-card__step">{{ item.step }}</span>
            <div class="category-guide-card__title">{{ item.title }}</div>
            <div class="category-guide-card__desc">{{ item.desc }}</div>
          </div>
        </div>
      </div>
      <div class="followup-panel">
        <div class="followup-panel__main">
          <div class="followup-panel__title">看完分类后继续去哪</div>
          <div class="followup-panel__desc">{{ followupHint }}</div>
          <div class="followup-panel__tags">
            <span v-for="item in followupTags" :key="item">{{ item }}</span>
          </div>
        </div>
        <div class="followup-panel__actions">
          <el-button type="primary" @click="goToPage('/content/content')">去内容管理</el-button>
          <el-button @click="goToPage('/content/tag')">去内容标签</el-button>
          <el-button @click="goToPage('/content/setting')">去内容设置</el-button>
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
        <div v-if="selection.length" class="dialog-review-note">
          <div class="dialog-review-note__title">提交前复核</div>
          <div class="dialog-review-note__tags">
            <span class="dialog-review-note__tag">运行环境：{{ runtimeEnvInfo.label }}</span>
            <span class="dialog-review-note__tag">操作：{{ categoryActionSummary }}</span>
            <span class="dialog-review-note__tag">对象：{{ selectIds || '未选择' }}</span>
          </div>
          <div class="dialog-review-note__risk">{{ categorySubmitRisk }}</div>
        </div>
        <el-form-item v-if="selectType === 'removec'">
          <span style="">确定要解除选中的{{ name }}的内容吗？</span>
        </el-form-item>
        <el-form-item v-else-if="selectType === 'editpid'" label="上级">
          <el-cascader
            v-model="category_pid"
            :options="trees"
            :props="props"
            class="w-full"
            placeholder="一级分类"
            clearable
            filterable
          />
        </el-form-item>
        <el-form-item v-if="selectType === 'disable'" label="是否禁用">
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
    <el-card  class="app-main">
      <div class="section-title-row section-title-row--content">
        <div>
          <div class="section-title-row__title">分类列表</div>
          <div class="section-title-row__desc">保留树形结构、禁用开关和分类内容承接能力。</div>
        </div>
        <div class="section-title-row__meta">{{ recentActionSummary || `已选 ${selection.length} 项` }}</div>
      </div>
      <div class="operation_btn mb5">
        <!-- 操作 -->
        <div>
<!--          <el-checkbox-->
<!--              border-->
<!--              v-model="isExpandAll"-->
<!--              class="!mr-[10px] top-[3px]"-->
<!--              title="收起/展开"-->
<!--              @change="expandAll"-->
<!--          >-->
<!--            收起-->
<!--          </el-checkbox>-->
          <el-button type="primary" @click="add()">添加</el-button>
          <el-button title="删除" @click="selectOpen('dele')">删除</el-button>
          <el-button title="是否禁用" @click="selectOpen('disable')">禁用</el-button>
<!--          <el-button title="修改上级" @click="selectOpen('editpid')">上级</el-button>-->
          <el-button title="解除内容" @click="selectOpen('removec')">解除内容</el-button>
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
        <el-table-column :prop="idkey" label="ID" min-width="80" />
        <el-table-column prop="category_unique" label="编码" min-width="100" show-overflow-tooltip />
        <el-table-column prop="category_name" label="名称" min-width="250" show-overflow-tooltip />
<!--        <el-table-column prop="image_url" label="图片" min-width="62">-->
<!--          <template #default="scope">-->
<!--            <FileImage :file-url="scope.row.image_url" lazy />-->
<!--          </template>-->
<!--        </el-table-column>-->
        <el-table-column prop="is_disable" label="禁用" min-width="85">
          <template #default="scope">
            <el-switch
              :model-value="scope.row.is_disable"
              :active-value="1"
              :inactive-value="0"
              @change="handleDisableSwitch(scope.row, $event)"
            />
          </template>
        </el-table-column>
        <el-table-column prop="sort" label="排序" min-width="85" />
        <el-table-column prop="create_time" label="添加时间" width="165" />
        <el-table-column prop="update_time" label="修改时间" width="165" />
        <el-table-column label="操作" width="90">
          <template #default="scope">
<!--            <el-link type="primary" class="mr-1" :underline="false" @click="contentShow(scope.row)">-->
<!--              内容-->
<!--            </el-link>-->
<!--            <el-link-->
<!--              type="primary"-->
<!--              class="mr-1"-->
<!--              :underline="false"-->
<!--              title="添加下级"-->
<!--              @click="add(scope.row)"-->
<!--            >-->
<!--              添加-->
<!--            </el-link>-->
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
          <div class="dialog-review-note">
            <div class="dialog-review-note__title">提交前复核</div>
            <div class="dialog-review-note__tags">
              <span class="dialog-review-note__tag">运行环境：{{ runtimeEnvInfo.label }}</span>
              <span class="dialog-review-note__tag">编码：{{ model.category_unique || '未填写' }}</span>
              <span class="dialog-review-note__tag">名称：{{ model.category_name || '未填写' }}</span>
            </div>
            <div class="dialog-review-note__risk">{{ categoryFormRisk }}</div>
          </div>
          <el-form-item label="编码" prop="category_unique">
            <el-input
              v-model="model.category_unique"
              placeholder="请输入分类编码（唯一）"
              clearable
            />
          </el-form-item>
<!--          <el-form-item label="上级" prop="category_pid">-->
<!--            <el-cascader-->
<!--              v-model="model.category_pid"-->
<!--              :options="trees"-->
<!--              :props="props"-->
<!--              class="w-full"-->
<!--              placeholder="一级分类"-->
<!--              clearable-->
<!--              filterable-->
<!--            />-->
<!--          </el-form-item>-->
          <el-form-item label="名称" prop="category_name">
            <el-input v-model="model.category_name" placeholder="请输入分类名称" clearable />
          </el-form-item>
<!--          <el-form-item label="图片" prop="image_id">-->
<!--            <FileImage v-model="model.image_id" :file-url="model.image_url" :height="100" upload />-->
<!--          </el-form-item>-->
<!--          <el-form-item label="标题" prop="title">-->
<!--            <el-input v-model="model.title" placeholder="title" clearable />-->
<!--          </el-form-item>-->
<!--          <el-form-item label="关键词" prop="keywords">-->
<!--            <el-input v-model="model.keywords" placeholder="keywords" clearable />-->
<!--          </el-form-item>-->
<!--          <el-form-item label="描述" prop="description">-->
<!--            <el-input-->
<!--              v-model="model.description"-->
<!--              type="textarea"-->
<!--              autosize-->
<!--              placeholder="description"-->
<!--            />-->
<!--          </el-form-item>-->
<!--          <el-form-item label="备注" prop="remark">-->
<!--            <el-input v-model="model.remark" placeholder="remark" clearable />-->
<!--          </el-form-item>-->
          <el-form-item label="排序" prop="sort">
            <el-input v-model="model.sort" placeholder="250" clearable />
          </el-form-item>
<!--          <el-form-item label="图片列表" prop="images">-->
<!--            <FileUploads-->
<!--              v-model="model.images"-->
<!--              upload-btn="上传图片"-->
<!--              file-type="image"-->
<!--              file-tip="图片文件"-->
<!--            />-->
<!--          </el-form-item>-->
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
    <!-- 分类内容 -->
    <el-dialog
      v-model="contentDialog"
      :title="contentDialogTitle"
      :close-on-click-modal="false"
      :close-on-press-escape="false"
      top="5vh"
      width="70%"
    >
      <!-- 分类内容操作 -->
      <el-row>
        <el-col>
          <el-button type="primary" title="解除" @click="contentSelectOpen('contentRemove')">
            解除
          </el-button>
          <el-input
            v-model="contentQuery.search_value"
            class="ya-search-value"
            placeholder="名称"
            clearable
          />
          <el-button type="primary" @click="content()">查询</el-button>
        </el-col>
      </el-row>
      <!-- 分类内容列表 -->
      <el-table
        ref="contentRef"
        v-loading="contentLoad"
        :data="contentData"
        :height="height - 60"
        @sort-change="contentSort"
        @selection-change="contentSelect"
      >
        <el-table-column type="selection" width="42" title="全选/反选" />
        <el-table-column :prop="contentPk" label="内容ID" min-width="100" sortable="custom" />
        <el-table-column prop="image_url" label="图片" min-width="62">
          <template #default="scope">
            <FileImage :file-url="scope.row.image_url" lazy />
          </template>
        </el-table-column>
        <el-table-column prop="name" label="名称" min-width="230" show-overflow-tooltip />
        <el-table-column prop="unique" label="编码" min-width="80" show-overflow-tooltip />
        <el-table-column prop="category_names" label="分类" min-width="120" show-overflow-tooltip />
        <el-table-column prop="tag_names" label="标签" min-width="120" show-overflow-tooltip />
        <el-table-column prop="is_top" label="置顶" min-width="85" sortable="custom">
          <template #default="scope">
            <el-switch v-model="scope.row.is_top" :active-value="1" :inactive-value="0" disabled />
          </template>
        </el-table-column>
        <el-table-column prop="is_hot" label="热门" min-width="85" sortable="custom">
          <template #default="scope">
            <el-switch v-model="scope.row.is_hot" :active-value="1" :inactive-value="0" disabled />
          </template>
        </el-table-column>
        <el-table-column prop="is_rec" label="推荐" min-width="85" sortable="custom">
          <template #default="scope">
            <el-switch v-model="scope.row.is_rec" :active-value="1" :inactive-value="0" disabled />
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
        <el-table-column label="操作" width="70">
          <template #default="scope">
            <el-link
              type="primary"
              :underline="false"
              @click="contentSelectOpen('contentRemove', scope.row)"
            >
              解除
            </el-link>
          </template>
        </el-table-column>
      </el-table>
      <!-- 分类内容分页 -->
      <pagination
        v-show="contentCount > 0"
        v-model:total="contentCount"
        v-model:page="contentQuery.page"
        v-model:limit="contentQuery.limit"
        @pagination="content"
      />
    </el-dialog>
    <el-dialog
      v-model="contentSelectDialog"
      :title="contentSelectTitle"
      :close-on-click-modal="false"
      :close-on-press-escape="false"
      top="20vh"
    >
      <el-form ref="contentSelectRef" label-width="120px">
        <el-form-item v-if="contentSelectType === 'contentRemove'" label="分类ID">
          <span>{{ contentQuery[idkey] }}</span>
        </el-form-item>
        <el-form-item :label="contentName + 'ID'">
          <el-input v-model="contentSelectIds" type="textarea" autosize disabled />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="contentSelectCancel">取消</el-button>
        <el-button type="primary" @click="contentSelectSubmit">提交</el-button>
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
  editpid,
  disable,
  content,
  contentRemove
} from '@/api/content/category'

export default {
  name: 'ContentCategory',
  components: { Pagination },
  computed: {
    runtimeEnvInfo() {
      return resolveAdminRuntimeEnv()
    },
    runtimeHint() {
      return getAdminRuntimeHint(this.runtimeEnvInfo)
    },
    categoryActionSummary() {
      const map = {
        removec: '批量解除内容',
        editpid: '批量改上级',
        disable: '批量改状态',
        dele: '批量删除'
      }
      return map[this.selectType] || '分类维护'
    },
    categorySubmitRisk() {
      if (this.selectType === 'dele') {
        return this.runtimeEnvInfo.isProd
          ? '正式环境下删除分类会直接影响栏目结构和内容归属，请先确认分类节点和关联内容。'
          : '当前环境适合验证分类删除和结构回显，不要把测试删除结果当作正式运营结果。'
      }
      if (!this.selection.length) {
        return this.runtimeEnvInfo.isProd
          ? '正式环境下批量操作会直接影响线上分类结构，请先选择记录后再提交。'
          : '当前环境建议先选择要验证的分类，再执行批量操作。'
      }
      return this.runtimeEnvInfo.isProd
        ? `正式环境下本次会直接影响 ${this.selection.length} 个分类节点，请先复核层级、禁用状态和内容承接关系。`
        : `当前环境可用于验证 ${this.selection.length} 个分类节点的批量操作与树形回显。`
    },
    categoryFormRisk() {
      if (!this.model.category_unique) {
        return '当前分类编码未填写，建议先补齐唯一编码后再提交。'
      }
      if (!this.model.category_name) {
        return '当前分类名称未填写，提交会被表单校验拦截。'
      }
      return this.runtimeEnvInfo.isProd
        ? '正式环境下提交会直接影响分类结构和承接关系，请复核编码、名称与排序。'
        : '当前环境适合验证分类新增和修改流程，可继续核对树形回显结果。'
    },
    currentSearchLabel() {
      const fieldMap = {
        category_id: 'ID',
        category_unique: '编码',
        category_name: '名称'
      }
      return fieldMap[this.query.search_field] || '综合筛选'
    },
    categoryGuideFocusLabel() {
      if (this.selection.length) {
        return `当前重点：先确认这 ${this.selection.length} 个分类节点是不是同一层级调整`
      }
      if (this.query.is_disable === 1) {
        return '当前重点：先看禁用分类下的内容是否已经迁移或解绑'
      }
      if (this.query.search_value) {
        return '当前重点：先看目标分类结构，再回内容管理核对实际内容'
      }
      return '当前重点：先分清分类结构问题和内容归属问题'
    },
    categoryGuideCards() {
      return [
        {
          step: '第一步',
          title: '先确认分类结构是不是对的',
          desc: '编码、名称、启用状态和树形层级不对，就先在分类页处理。'
        },
        {
          step: '第二步',
          title: '再判断要不要动内容归属',
          desc: '如果分类本身没问题，但内容显示错位，那通常要回内容管理页看具体内容挂在哪个分类下。'
        },
        {
          step: '第三步',
          title: '最后去内容页和标签页承接',
          desc: '分类只是骨架，真正的运营结果还要看内容归属和标签聚合有没有一起对上。'
        }
      ]
    },
    followupHint() {
      if (this.selection.length) {
        return `当前已选 ${this.selection.length} 个分类，提交前建议先去内容管理核对这些分类下的内容是否还在正常承接。`
      }
      if (this.query.is_disable === 1) {
        return '当前正在看禁用分类，适合继续去内容管理检查这些分类下的内容是否需要迁移、解绑或恢复。'
      }
      if (this.query.search_value) {
        return '当前分类范围已经收窄，下一步更适合去内容管理按分类继续复核具体内容。'
      }
      return '分类结构稳定后，通常下一步就是去内容管理看内容归属，再回标签页补专题和聚合入口。'
    },
    followupTags() {
      return [
        `分类总量：${this.count || 0}`,
        `已选：${this.selection.length} 项`,
        `筛选：${this.currentSearchLabel}`,
        `状态：${this.query.is_disable === 1 ? '禁用' : this.query.is_disable === 0 ? '启用' : '全部'}`
      ]
    }
  },
  data() {
    return {
      name: '内容分类',
      height: 680,
      loading: false,
      idkey: 'category_id',
      query: { search_field: 'category_name', search_exp: 'like', date_field: 'create_time',category_pid:undefined,is_disable:undefined },
      exps: [{ exp: 'like', name: '包含' }],
      data: [],
      count: '',
      dialog: false,
      dialogTitle: '',
      model: {
        category_id: '',
        category_unique: '',
        category_pid: 0,
        category_name: '',
        image_id: 0,
        image_url: '',
        title: '',
        keywords: '',
        description: '',
        remark: '',
        sort: 250,
        images: []
      },
      rules: {
        category_name: [{ required: true, message: '请输入分类名称', trigger: 'blur' }]
      },
      trees: [],
      props: { checkStrictly: true, value: 'category_id', label: 'category_name', emitPath: false },
      isExpandAll: false,
      selection: [],
      selectIds: '',
      selectTitle: '操作',
      selectDialog: false,
      selectType: '',
      category_pid: '',
      is_disable: 0,
      contentPk: 'content_id',
      contentName: '内容',
      contentDialog: false,
      contentDialogTitle: '',
      contentLoad: false,
      contentData: [],
      contentCount: 0,
      contentQuery: {
        page: 1,
        limit: getPageLimit(),
        search_field: 'name',
        search_exp: 'like',
        search_value: ''
      },
      contentSelection: [],
      contentSelectIds: '',
      contentSelectTitle: '操作',
      contentSelectDialog: false,
      contentSelectType: '',
      recentActionSummary: ''
    }
  },
  created() {
    this.height = screenHeight(290)
    this.list()
  },
  methods: {
    setRecentActionSummary(action, extra = '') {
      this.recentActionSummary = `已执行${action}，影响 ${this.selection.length || 0} 个分类${extra ? `，${extra}` : ''}。`
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
        this.model.category_pid = row[this.idkey]
      }
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
                this.setRecentActionSummary('修改分类', `名称：${this.model.category_name || '未填写'}`)
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
                this.setRecentActionSummary('新增分类', `名称：${this.model.category_name || '未填写'}`)
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
    search() {
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
        if (selectType === 'removec') {
          this.selectTitle = this.name + '解除内容'
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
        if (selectType === 'removec') {
          this.removec(this.selection)
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
    // 解除内容
    removec(row) {
      if (!row.length) {
        this.selectAlert()
      } else {
        this.loading = true
        contentRemove({
          category_id: this.selectGetIds(row),
          content_ids: []
        })
          .then((res) => {
            this.list()
            this.setRecentActionSummary('批量解除分类内容')
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
        category_pid: this.category_pid
      })
        .then((res) => {
          this.list()
          this.selectDialog = false
          this.setRecentActionSummary('批量调整分类上级', `目标上级：${this.category_pid || '顶级分类'}`)
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
            this.setRecentActionSummary('批量调整分类状态', `目标状态：${is_disable === 1 ? '禁用' : '启用'}`)
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.list()
          })
      }
    },
    handleDisableSwitch(row, value) {
      const actionText = value === 1 ? '禁用' : '启用'
      ElMessageBox.confirm(
        `确认要${actionText}分类「${row.category_name || row[this.idkey]}」吗？`,
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
        .catch(() => {
          this.list()
        })
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
            this.setRecentActionSummary('批量删除分类')
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.loading = false
          })
      }
    },
    // 分类内容显示
    contentShow(row) {
      this.contentDialog = true
      this.contentDialogTitle = this.name + '内容：' + row.category_name
      this.contentQuery.category_id = row.category_id
      this.contentQuery.search_value = ''
      this.content()
    },
    // 分类内容列表
    content() {
      this.contentLoad = true
      content(this.contentQuery)
        .then((res) => {
          this.contentData = res.data.list
          this.contentCount = res.data.count
          this.contentLoad = false
        })
        .catch(() => {
          this.contentLoad = false
        })
    },
    // 分类内容排序
    contentSort(sort) {
      this.contentQuery.sort_field = sort.prop
      this.contentQuery.sort_value = ''
      if (sort.order === 'ascending') {
        this.contentQuery.sort_value = 'asc'
        this.content()
      }
      if (sort.order === 'descending') {
        this.contentQuery.sort_value = 'desc'
        this.content()
      }
    },
    // 分类内容操作
    contentSelect(selection) {
      this.contentSelection = selection
      this.contentSelectIds = this.contentSelectGetIds(selection).toString()
    },
    contentSelectGetIds(selection) {
      return arrayColumn(selection, this.contentPk)
    },
    contentSelectAlert() {
      ElMessageBox.alert('请选择需要操作的' + this.contentName, '提示', {
        type: 'warning',
        callback: () => {}
      })
    },
    contentSelectOpen(selectType, selectRow = '') {
      if (selectRow) {
        this.$refs['contentRef'].clearSelection()
        this.$refs['contentRef'].toggleRowSelection(selectRow)
      }
      if (!this.contentSelection.length) {
        this.contentSelectAlert()
      } else {
        this.contentSelectTitle = '操作'
        if (selectType === 'contentRemove') {
          this.contentSelectTitle = this.name + '解除' + this.contentName
        }
        this.contentSelectDialog = true
        this.contentSelectType = selectType
      }
    },
    contentSelectCancel() {
      this.contentSelectDialog = false
    },
    contentSelectSubmit() {
      if (!this.contentSelection.length) {
        this.contentSelectAlert()
      } else {
        const selectType = this.contentSelectType
        if (selectType === 'contentRemove') {
          this.contentRemove(this.contentSelection)
        }
        this.contentSelectDialog = false
      }
    },
    // 分类内容解除内容
    contentRemove(row) {
      if (!row.length) {
        this.contentSelectAlert()
      } else {
        this.contentLoad = true
        contentRemove({
          category_id: this.contentQuery.category_id,
          content_ids: this.contentSelectGetIds(row)
        })
          .then((res) => {
            this.content()
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.contentLoad = false
          })
      }
    },
    goToPage(path) {
      this.$router.push({
        path,
        query: {
          from: 'content-category'
        }
      })
    }
  }
}
</script>

<style scoped>
.followup-panel {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-top: 16px;
  padding: 14px 16px;
  border-radius: 14px;
  border: 1px solid #e6eef8;
  background: #fbfdff;
}

.category-guide-panel {
  margin-top: 16px;
  padding: 14px 16px;
  border-radius: 14px;
  border: 1px solid #dbe7f5;
  background: linear-gradient(135deg, #f7fbff 0%, #ffffff 100%);
}

.category-guide-panel__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.category-guide-panel__title {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
}

.category-guide-panel__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.category-guide-panel__badge {
  min-width: 220px;
  padding: 10px 12px;
  border-radius: 12px;
  background: rgba(37, 99, 235, 0.08);
  color: #1d4ed8;
  font-size: 12px;
  font-weight: 700;
  line-height: 1.6;
}

.category-guide-panel__grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-top: 14px;
}

.category-guide-card {
  padding: 14px 16px;
  border: 1px solid #e7edf5;
  border-radius: 14px;
  background: #fff;
}

.category-guide-card__step {
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

.category-guide-card__title {
  margin-top: 10px;
  font-size: 14px;
  font-weight: 700;
  color: #0f172a;
}

.category-guide-card__desc {
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
  color: #1f2937;
}

.followup-panel__desc {
  margin-top: 6px;
  color: #64748b;
  line-height: 1.7;
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
  background: #f8fafc;
  border: 1px solid #e7edf5;
  color: #475569;
  font-size: 12px;
}

.followup-panel__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  justify-content: flex-end;
}

.dialog-review-note__tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 12px;
}

.dialog-review-note__tag {
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  padding: 0 10px;
  border-radius: 999px;
  background: #eff6ff;
  color: #1d4ed8;
  font-size: 12px;
}

.dialog-review-note__risk {
  margin-top: 12px;
  padding: 12px 14px;
  border-radius: 12px;
  background: #fff7ed;
  border: 1px solid #fed7aa;
  color: #9a3412;
  font-size: 12px;
  line-height: 1.7;
}

.dialog-review-note {
  margin-bottom: 16px;
  padding: 14px 16px;
  border: 1px solid #dbe7f5;
  border-radius: 14px;
  background: #f8fbff;
}

.dialog-review-note__title,
.summary-bar__label {
  font-size: 12px;
  font-weight: 700;
  color: #475569;
}

.section-title-row__meta {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.section-title-row__meta-text {
  color: #64748b;
  font-size: 12px;
}

.summary-bar {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 10px;
  margin-top: 16px;
}

.summary-bar__item {
  padding: 10px 12px;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  background: #f8fbff;
}

.summary-bar__value {
  display: block;
  margin-top: 4px;
  color: #0f172a;
  font-size: 14px;
  font-weight: 600;
}

@media (max-width: 900px) {
  .followup-panel,
  .category-guide-panel__header {
    flex-direction: column;
  }

  .category-guide-panel__badge {
    min-width: 0;
  }

  .category-guide-panel__grid {
    grid-template-columns: 1fr;
  }
}
</style>
