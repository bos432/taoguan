<template>
  <div class="app-container">
    <el-card  class="app-head head-pb20">
      <div class="section-title-row">
        <div>
          <div class="section-title-row__title">内容管理</div>
          <div class="section-title-row__desc">统一处理内容筛选、内容维护和运营状态调整，保持原有业务能力不变。</div>
        </div>
        <div class="section-title-row__meta">{{ runtimeEnvInfo.label }}</div>
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
            <el-form-item label="是否置顶：" prop="is_top">
              <el-select
                  v-model="query.is_top"
                  @change="search()"
                  clearable
              >
                <el-option :value="1" label="是" />
                <el-option :value="0" label="否" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="是否热门：" prop="is_hot">
              <el-select
                  v-model="query.is_hot"
                  @change="search()"
                  clearable
              >
                <el-option :value="1" label="是" />
                <el-option :value="0" label="否" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="是否推荐：" prop="is_rec">
              <el-select
                  v-model="query.is_rec"
                  @change="search()"
                  clearable
              >
                <el-option :value="1" label="是" />
                <el-option :value="0" label="否" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="分类：" prop="category_ids">
              <el-cascader
                  v-model="query.category_ids"
                  :options="categoryData"
                  :props="categoryProps"
                  clearable
                  filterable
                  collapse-tags
                  style="width: 100%;"
              />
            </el-form-item>
          </el-col>
<!--          <el-col :span="6">-->
<!--            <el-form-item label="标签：" prop="tag_ids">-->
<!--              <el-select-->
<!--                  v-model="query.tag_ids"-->
<!--                  clearable-->
<!--                  filterable-->
<!--                  multiple-->
<!--                  collapse-tags-->
<!--              >-->
<!--                <el-option-->
<!--                    v-for="item in tagData"-->
<!--                    :key="item.tag_id"-->
<!--                    :label="item.tag_name"-->
<!--                    :value="item.tag_id"-->
<!--                />-->
<!--              </el-select>-->
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
                  <el-option value="unique" label="标识" />
                  <el-option value="title" label="标题" />
                  <el-option value="remark" label="备注" />
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
      <div class="content-summary-bar">
        <div class="content-summary-bar__chips">
          <span class="summary-chip summary-chip--primary">总数 {{ count }}</span>
          <span class="summary-chip">已选 {{ selection.length }} 条</span>
          <span class="summary-chip">{{ currentCategorySummary }}</span>
          <span class="summary-chip">{{ currentStatusSummary }}</span>
          <span v-for="item in activeFilterTags" :key="item" class="summary-chip">{{ item }}</span>
          <span v-if="!activeFilterTags.length" class="summary-chip">默认条件：全部内容</span>
          <span v-if="recentActionSummary" class="summary-chip summary-chip--muted">{{
            recentActionSummary
          }}</span>
        </div>
        <div class="content-summary-bar__hint" :class="contentFollowupBadgeClass">
          <span class="content-summary-bar__hint-title">{{ contentFollowupBadgeText }}</span>
          <span class="content-summary-bar__hint-text">{{ contentFollowupHint }}</span>
        </div>
      </div>
      <div class="content-guide-panel">
        <div class="content-guide-panel__header">
          <div>
            <div class="content-guide-panel__title">内容管理第一次排查，建议先这样看</div>
            <div class="content-guide-panel__desc">先看内容现在处于什么运营状态，再决定是改分类、改推荐位，还是去内容设置和文件库联动处理。</div>
          </div>
          <div class="content-guide-panel__badge">{{ contentGuideFocusLabel }}</div>
        </div>
        <div class="content-guide-panel__grid">
          <div v-for="item in contentGuideCards" :key="item.title" class="content-guide-card">
            <span class="content-guide-card__step">{{ item.step }}</span>
            <div class="content-guide-card__title">{{ item.title }}</div>
            <div class="content-guide-card__desc">{{ item.desc }}</div>
          </div>
        </div>
      </div>
      <div class="followup-panel">
        <div class="followup-panel__main">
          <div class="followup-panel__title">看完内容后继续去哪</div>
          <div class="followup-panel__desc">{{ followupHint }}</div>
          <div class="followup-panel__tags">
            <span v-for="item in followupTags" :key="item">{{ item }}</span>
          </div>
        </div>
        <div class="followup-panel__actions">
          <el-button type="primary" @click="goToPage('/content/category')">去内容分类</el-button>
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
        <el-form-item v-if="selectType === 'editcate'" label="分类">
          <el-cascader
            v-model="category_ids"
            :options="categoryData"
            :props="categoryProps"
            class="w-full"
            clearable
            filterable
          />
        </el-form-item>
        <el-form-item v-else-if="selectType === 'edittag'" label="标签">
          <el-select v-model="tag_ids" multiple clearable filterable class="w-full">
            <el-option
              v-for="item in tagData"
              :key="item.tag_id"
              :label="item.tag_name"
              :value="item.tag_id"
            />
          </el-select>
        </el-form-item>
        <el-form-item v-else-if="selectType === 'istop'" label="是否置顶">
          <el-switch v-model="is_top" :active-value="1" :inactive-value="0" />
        </el-form-item>
        <el-form-item v-else-if="selectType === 'ishot'" label="是否热门">
          <el-switch v-model="is_hot" :active-value="1" :inactive-value="0" />
        </el-form-item>
        <el-form-item v-else-if="selectType === 'isrec'" label="是否推荐">
          <el-switch v-model="is_rec" :active-value="1" :inactive-value="0" />
        </el-form-item>
        <el-form-item v-else-if="selectType === 'disable'" label="是否禁用">
          <el-switch v-model="is_disable" :active-value="1" :inactive-value="0" />
        </el-form-item>
        <el-form-item v-else-if="selectType === 'release'" label="发布时间">
          <el-date-picker
            v-model="release_time"
            type="datetime"
            value-format="YYYY-MM-DD HH:mm:ss"
          />
        </el-form-item>
        <el-form-item v-else-if="selectType === 'dele'">
          <span class="c-red">确定要删除选中的{{ name }}吗？</span>
        </el-form-item>
        <el-form-item :label="name + 'ID'">
          <el-input v-model="selectIds" type="textarea" autosize disabled />
        </el-form-item>
        <el-form-item v-if="selection.length" label="批量复核">
          <div class="select-review-panel">
            <div class="select-review-panel__tags">
              <span>{{ contentActionSummary }}</span>
              <span>影响记录：{{ selection.length || 0 }} 条</span>
              <span>目标：{{ selectionPreview }}</span>
            </div>
            <div class="select-review-panel__hint">{{ contentSubmitRisk }}</div>
          </div>
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
          <div class="section-title-row__title">内容列表</div>
          <div class="section-title-row__desc">支持批量维护内容状态，并保留原有新增、编辑、分类调整流程。</div>
        </div>
        <div class="section-title-row__meta">已选 {{ selection.length }} 条</div>
      </div>
      <div class="operation_btn mb5">
        <!-- 操作 -->
        <div>
          <el-button type="primary" @click="add()">添加</el-button>
          <el-button title="删除" @click="selectOpen('dele')">删除</el-button>
          <el-button title="是否禁用" @click="selectOpen('disable')">禁用</el-button>
<!--          <el-button title="发布时间" @click="selectOpen('release')">发布时间</el-button>-->
          <el-button title="修改分类" @click="selectOpen('editcate')">分类</el-button>
<!--          <el-button title="修改标签" @click="selectOpen('edittag')">标签</el-button>-->
          <el-button title="是否置顶" @click="selectOpen('istop')">置顶</el-button>
          <el-button title="是否热门" @click="selectOpen('ishot')">热门</el-button>
          <el-button title="是否推荐" @click="selectOpen('isrec')">推荐</el-button>
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
        <el-table-column prop="unique" label="标识" min-width="75" show-overflow-tooltip />
        <el-table-column prop="image_url" label="图片" min-width="62" show-overflow-tooltip>
          <template #default="scope">
            <FileImage fileSource="list" :file-url="scope.row.image_url" lazy />
          </template>
        </el-table-column>
        <el-table-column prop="title" label="标题" min-width="175" show-overflow-tooltip />
        <el-table-column prop="category_names" label="分类" min-width="105" show-overflow-tooltip />
        <el-table-column prop="is_top" label="置顶" min-width="85" sortable="custom">
          <template #default="scope">
            <el-switch
              v-model="scope.row.is_top"
              :active-value="1"
              :inactive-value="0"
              @change="onRowSwitchChange(scope.row, 'is_top', '置顶', 'istop')"
            />
          </template>
        </el-table-column>
        <el-table-column prop="is_hot" label="热门" min-width="85" sortable="custom">
          <template #default="scope">
            <el-switch
              v-model="scope.row.is_hot"
              :active-value="1"
              :inactive-value="0"
              @change="onRowSwitchChange(scope.row, 'is_hot', '热门', 'ishot')"
            />
          </template>
        </el-table-column>
        <el-table-column prop="is_rec" label="推荐" min-width="85" sortable="custom">
          <template #default="scope">
            <el-switch
              v-model="scope.row.is_rec"
              :active-value="1"
              :inactive-value="0"
              @change="onRowSwitchChange(scope.row, 'is_rec', '推荐', 'isrec')"
            />
          </template>
        </el-table-column>
        <el-table-column prop="is_disable" label="禁用" min-width="85" sortable="custom">
          <template #default="scope">
            <el-switch
              v-model="scope.row.is_disable"
              :active-value="1"
              :inactive-value="0"
              @change="onRowSwitchChange(scope.row, 'is_disable', '禁用', 'disable')"
            />
          </template>
        </el-table-column>
        <el-table-column
          prop="hits"
          label="点击"
          min-width="85"
          show-overflow-tooltip
          sortable="custom"
        />
        <el-table-column prop="create_time" label="添加时间" width="165" sortable="custom" />
        <el-table-column prop="update_time" label="修改时间" width="165" sortable="custom" />
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
      <el-form ref="ref" :rules="rules" :model="model" label-width="100px">
        <div class="dialog-review-note dialog-review-note--form">
          <div class="dialog-review-note__tags">
            <span class="dialog-review-note__tag">{{ model[this.idkey] ? '当前模式：编辑内容' : '当前模式：新增内容' }}</span>
            <span class="dialog-review-note__tag">运行环境：{{ runtimeEnvInfo.label }}</span>
            <span class="dialog-review-note__tag">标题：{{ model.title || '未填写' }}</span>
          </div>
          <div class="dialog-review-note__risk">{{ contentFormRisk }}</div>
        </div>
        <el-tabs>
          <el-tab-pane label="信息">
            <el-scrollbar native :max-height="height - 30">
              <el-form-item label="图片" prop="image_id">
                <FileImage
                  v-model="model.image_id"
                  :file-url="model.image_url"
                  :height="100"
                  upload
                />
              </el-form-item>
              <el-form-item label="分类" prop="category_ids">
                <el-cascader
                  v-model="model.category_ids"
                  :options="categoryData"
                  :props="categoryProps"
                  class="w-full"
                  clearable
                  filterable
                />
              </el-form-item>
              <el-form-item label="标题" prop="title">
                <el-input v-model="model.title" placeholder="请输入标题" clearable />
              </el-form-item>
              <el-form-item label="来源" prop="source">
                <el-input v-model="model.source" placeholder="请输入来源" clearable />
              </el-form-item>
              <el-form-item label="作者" prop="author">
                <el-input v-model="model.author" placeholder="请输入作者" clearable />
              </el-form-item>
              <el-form-item label="备注" prop="remark">
                <el-input v-model="model.remark" placeholder="请输入备注" clearable />
              </el-form-item>
              <el-form-item label="排序" prop="sort">
                <el-input v-model="model.sort" type="number" placeholder="sort" clearable />
              </el-form-item>
              <el-form-item label="初始点击" prop="hits_initial">
                <el-col :span="6">
                  <el-input v-model="model.hits_initial" type="number" placeholder="初始点击量" />
                </el-col>
                <el-col :span="3" class="text-center"> 真实点击 </el-col>
                <el-col :span="6">
                  <el-input v-model="model.hits" type="number" placeholder="真实点击量" disabled />
                </el-col>
                <el-col :span="3" class="text-center"> 展示点击 </el-col>
                <el-col :span="6">
                  <el-input
                    :value="parseFloat(model.hits_initial) + parseFloat(model.hits)"
                    type="number"
                    placeholder="初始点击量"
                    disabled
                  />
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
            </el-scrollbar>
          </el-tab-pane>
          <el-tab-pane label="内容">
            <el-scrollbar native :max-height="height - 30">
              <el-form-item label="内容" prop="content">
                <RichEditor v-model="model.content" />
              </el-form-item>
            </el-scrollbar>
          </el-tab-pane>
<!--          <el-tab-pane label="附件">-->
<!--            <el-scrollbar native :max-height="height - 30">-->
<!--              <el-form-item label="图片列表" prop="images">-->
<!--                <FileUploads-->
<!--                  v-model="model.images"-->
<!--                  upload-btn="上传图片"-->
<!--                  file-type="image"-->
<!--                  file-tip="图片文件"-->
<!--                />-->
<!--              </el-form-item>-->
<!--              <el-form-item label="视频列表" prop="videos">-->
<!--                <FileUploads-->
<!--                  v-model="model.videos"-->
<!--                  upload-btn="上传视频"-->
<!--                  file-type="video"-->
<!--                  file-tip="视频文件"-->
<!--                />-->
<!--              </el-form-item>-->
<!--              <el-form-item label="音频列表" prop="audios">-->
<!--                <FileUploads-->
<!--                  v-model="model.audios"-->
<!--                  upload-btn="上传音频"-->
<!--                  file-type="audio"-->
<!--                  file-tip="音频文件"-->
<!--                />-->
<!--              </el-form-item>-->
<!--              <el-form-item label="文档列表" prop="words">-->
<!--                <FileUploads-->
<!--                  v-model="model.words"-->
<!--                  upload-btn="上传文档"-->
<!--                  file-type="word"-->
<!--                  file-tip="文档文件"-->
<!--                />-->
<!--              </el-form-item>-->
<!--              <el-form-item label="其它列表" prop="others">-->
<!--                <FileUploads-->
<!--                  v-model="model.others"-->
<!--                  upload-btn="上传其它"-->
<!--                  file-type="other"-->
<!--                  file-tip="其它文件"-->
<!--                />-->
<!--              </el-form-item>-->
<!--            </el-scrollbar>-->
<!--          </el-tab-pane>-->
        </el-tabs>
      </el-form>
      <template #footer>
        <el-button :loading="loading" @click="cancel">取消</el-button>
        <el-button :loading="loading" type="primary" @click="submit">提交</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script>
import screenHeight from '@/utils/screen-height'
import Pagination from '@/components/Pagination/index.vue'
import RichEditor from '@/components/RichEditor/index.vue'
import { arrayColumn } from '@/utils/index'
import { getPageLimit } from '@/utils/settings'
import { resolveAdminRuntimeEnv } from '@/utils/runtime-env'
import {
  list,
  info,
  add,
  edit,
  dele,
  editcate,
  edittag,
  istop,
  ishot,
  isrec,
  disable,
  release
} from '@/api/content/content'

export default {
  name: 'ContentContent',
  components: { Pagination, RichEditor },
  computed: {
    runtimeEnvInfo() {
      return resolveAdminRuntimeEnv()
    },
    currentCategorySummary() {
      if (Array.isArray(this.query.category_ids) && this.query.category_ids.length) {
        return `已选 ${this.query.category_ids.length} 个分类`
      }
      return '全部分类'
    },
    currentKeywordSummary() {
      if (this.query.search_value) {
        return `关键词：${this.query.search_value}`
      }
      return '未设置关键词检索'
    },
    currentStatusSummary() {
      const flags = []
      if (this.query.is_disable !== undefined) {
        flags.push(this.query.is_disable ? '禁用' : '启用')
      }
      if (this.query.is_top !== undefined) {
        flags.push(this.query.is_top ? '置顶' : '未置顶')
      }
      if (this.query.is_hot !== undefined) {
        flags.push(this.query.is_hot ? '热门' : '非热门')
      }
      if (this.query.is_rec !== undefined) {
        flags.push(this.query.is_rec ? '推荐' : '非推荐')
      }
      return flags.length ? flags.join(' / ') : '未限定状态'
    },
    activeFilterTags() {
      const tags = []
      if (Array.isArray(this.query.date_value) && this.query.date_value.length === 2) {
        tags.push(`时间：${this.query.date_value[0]} 至 ${this.query.date_value[1]}`)
      }
      if (this.query.search_value) {
        tags.push(`检索：${this.query.search_field || 'title'} = ${this.query.search_value}`)
      }
      return tags
    },
    selectionPreview() {
      if (!this.selection.length) {
        return '未选择'
      }
      const ids = this.selection
        .slice(0, 4)
        .map((item) => item[this.idkey])
        .join('、')
      return `${ids}${this.selection.length > 4 ? ' 等' : ''}`
    },
    contentActionSummary() {
      const map = {
        editcate: '批量改分类',
        edittag: '批量改标签',
        istop: '批量改置顶',
        ishot: '批量改热门',
        isrec: '批量改推荐',
        disable: '批量改禁用',
        release: '批量改发布时间',
        dele: '批量删除'
      }
      return map[this.selectType] || '内容维护'
    },
    contentFollowupBadgeText() {
      if (this.selection.length > 0) {
        return '待提交'
      }
      if (this.query.is_disable !== undefined || this.query.is_top !== undefined || this.query.is_hot !== undefined || this.query.is_rec !== undefined) {
        return '筛选已收敛'
      }
      return '默认巡检'
    },
    contentFollowupBadgeClass() {
      if (this.selection.length > 0) {
        return 'is-active'
      }
      if (this.query.is_disable !== undefined || this.query.is_top !== undefined || this.query.is_hot !== undefined || this.query.is_rec !== undefined) {
        return 'is-warning'
      }
      return 'is-safe'
    },
    contentGuideFocusLabel() {
      if (this.selection.length) {
        return `当前重点：先确认这 ${this.selection.length} 条内容是不是同一批运营动作`
      }
      if (this.query.is_top === 1 || this.query.is_hot === 1 || this.query.is_rec === 1) {
        return '当前重点：先核对运营位状态，再决定是否需要批量调整'
      }
      if (this.query.is_disable === 1) {
        return '当前重点：先看禁用内容是不是历史稿件，避免误恢复'
      }
      return '当前重点：先分清内容状态，再做运营动作'
    },
    contentGuideCards() {
      return [
        {
          step: '第一步',
          title: '先按状态和分类缩小范围',
          desc: '先看是启用、置顶、热门还是推荐内容，再落到具体分类，不要全量内容里混着改。'
        },
        {
          step: '第二步',
          title: '再判断是内容本身问题还是运营位问题',
          desc: '标题、正文、封面不对就在这里改；如果是展示顺序或推荐逻辑问题，往往还要联动分类、标签和设置页。'
        },
        {
          step: '第三步',
          title: '最后去分类、标签、设置或文件库承接',
          desc: '内容页只是一层，真正的运营结果通常还要看分类结构、推荐规则和素材引用。'
        }
      ]
    },
    contentFollowupHint() {
      if (this.selection.length > 0) {
        return `当前已选 ${this.selection.length} 条内容，可继续调整分类、状态或删除，但建议先复核标题与分类。`
      }
      if (this.activeFilterTags.length > 0 || this.currentStatusSummary !== '未限定状态') {
        return '当前列表已经按筛选条件收敛，建议抽查内容标题、分类和运营状态后再继续批量处理。'
      }
      return '默认按内容巡检视角承接，建议先用分类、状态和关键词缩小范围。'
    },
    contentSubmitRisk() {
      if (this.selectType === 'dele') {
        return this.runtimeEnvInfo.isProd
          ? '正式环境下删除会直接影响线上内容展示，提交前请再次确认选中 ID 与分类。'
          : '当前环境适合验证批量删除流程，但不要把测试删除结果当作正式运营结果。'
      }
      if (!this.selection.length) {
        return this.runtimeEnvInfo.isProd
          ? '正式环境下建议先明确筛选条件和选中记录，再执行批量状态调整。'
          : '当前环境建议先选中样本数据，再验证批量操作与列表回显。'
      }
      return this.runtimeEnvInfo.isProd
        ? `正式环境下本次会直接影响 ${this.selection.length} 条内容的运营状态，请先复核分类、状态和标题。`
        : `当前环境可用于验证 ${this.selection.length} 条内容的批量操作和结果回显。`
    },
    contentFormRisk() {
      if (!this.model.title) {
        return '当前标题未填写，提交会被表单校验拦截。'
      }
      if (!this.model.category_ids || !this.model.category_ids.length) {
        return '当前还未选择分类，建议提交前补齐分类归属。'
      }
      return this.runtimeEnvInfo.isProd
        ? '正式环境下新增或修改会直接影响线上内容展示，请确认标题、分类和内容后再提交。'
        : '当前环境适合验证新增、编辑、分类归属和富文本回显。'
    },
    followupHint() {
      if (this.selection.length) {
        return `当前已勾选 ${this.selection.length} 条内容，适合继续去分类页确认归属，或去标签页检查专题聚合是否一致。`
      }
      if (this.query.category_ids && this.query.category_ids.length) {
        return '当前已经按分类筛过内容，下一步更适合回分类页核对结构，或去标签页继续补聚合入口。'
      }
      if (this.activeFilterTags.length > 0) {
        return '当前内容范围已经收窄，建议顺手去标签页和分类页做交叉复核，避免只改内容不改承接。'
      }
      return '内容页通常是运营处理主战场，处理完后建议回分类和标签页补结构，再去设置页确认默认展示能力。'
    },
    followupTags() {
      return [
        `内容总量：${this.count || 0}`,
        `已选：${this.selection.length} 条`,
        `分类筛选：${this.query.category_ids?.length || 0} 项`,
        `状态：${this.currentStatusSummary}`
      ]
    }
  },

  data() {
    return {
      name: '内容',
      height: 680,
      loading: false,
      idkey: 'content_id',
      exps: [{ exp: 'like', name: '包含' }],
      query: {
        page: 1,
        limit: getPageLimit(),
        search_field: 'name',
        search_exp: 'like',
        date_field: 'create_time',
        is_disable:undefined,
        is_top:undefined,
        is_hot:undefined,
        is_rec:undefined,
        category_ids:undefined,
        tag_ids:undefined,
      },
      data: [],
      count: 0,
      dialog: false,
      dialogTitle: '',
      model: {
        content_id: '',
        unique: '',
        category_ids: [],
        tag_ids: [],
        image_id: 0,
        image_url: '',
        name: '',
        title: '',
        keywords: '',
        description: '',
        content: '',
        source: '',
        author: '',
        url: '',
        remark: '',
        sort: 250,
        hits: 0,
        hits_initial: 0,
        images: [],
        videos: [],
        audios: [],
        words: [],
        others: []
      },
      rules: {
        title: [{ required: true, message: '请输入标题', trigger: 'blur' }],
        image_id: [{ required: true, message: '请上传图片', trigger: 'blur' }],
        category_ids: [{ required: true, message: '请选择分类', trigger: 'blur' }],
      },
      tagData: [],
      categoryData: [],
      categoryProps: {
        checkStrictly: true,
        value: 'category_id',
        label: 'category_name',
        multiple: true,
        emitPath: false
      },
      selection: [],
      selectIds: '',
      selectTitle: '操作',
      selectDialog: false,
      selectType: '',
      category_ids: [],
      tag_ids: [],
      is_top: 0,
      is_hot: 0,
      is_rec: 0,
      is_disable: 0,
      release_time: '',
      recentActionSummary: ''
    }
  },
  created() {
    this.height = screenHeight()
    this.list()
  },
  methods: {
    setRecentActionSummary(summary) {
      this.recentActionSummary = summary
    },
    // 列表
    list() {
      this.loading = true
      list(this.query)
        .then((res) => {
          this.data = res.data.list
          this.count = res.data.count
          this.categoryData = res.data.category
          this.tagData = res.data.tag
          this.exps = res.data.exps
          this.setRecentActionSummary(`内容列表已刷新，共 ${res.data.count || 0} 条。`)
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
          if (this.model[this.idkey]) {
            edit(this.model)
              .then((res) => {
                this.list()
                this.dialog = false
                this.setRecentActionSummary(`内容修改已提交：${this.model.title || this.model[this.idkey]}`)
                ElMessage.success(res.msg)
              })
              .catch(() => {})
          } else {
            add(this.model)
              .then((res) => {
                this.list()
                this.dialog = false
                this.setRecentActionSummary(`内容新增已提交：${this.model.title || '未命名内容'}`)
                ElMessage.success(res.msg)
              })
              .catch(() => {})
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
    },
    // 查询
    search() {
      this.query.page = 1
      this.setRecentActionSummary(`已按 ${this.query.search_field || 'title'} 发起内容检索。`)
      this.list()
    },
    // 刷新
    refresh() {
      const limit = this.query.limit
      this.query = this.$options.data().query
      this.$refs['table'].clearSort()
      this.query.limit = limit
      this.setRecentActionSummary('已重置内容筛选条件。')
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
      if (selection.length) {
        this.setRecentActionSummary(`已选 ${selection.length} 条内容，待处理 ID：${this.selectIds}。`)
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
        if (selectType === 'editcate') {
          this.selectTitle = this.name + '修改分类'
        } else if (selectType === 'edittag') {
          this.selectTitle = this.name + '修改标签'
        } else if (selectType === 'istop') {
          this.selectTitle = this.name + '是否置顶'
        } else if (selectType === 'ishot') {
          this.selectTitle = this.name + '是否热门'
        } else if (selectType === 'isrec') {
          this.selectTitle = this.name + '是否推荐'
        } else if (selectType === 'disable') {
          this.selectTitle = this.name + '是否禁用'
        } else if (selectType === 'release') {
          this.selectTitle = this.name + '发布时间'
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
    onRowSwitchChange(row, field, label, action) {
      const enabled = Number(row[field]) === 1
      const targetLabel =
        field === 'is_disable'
          ? enabled
            ? '禁用'
            : '启用'
          : enabled
          ? `设为${label}`
          : `取消${label}`
      ElMessageBox.confirm(`确定要${targetLabel}【${row.title || row[this.idkey]}】吗？`, '提示', {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning'
      })
        .then(() => {
          this[action]([row])
        })
        .catch(() => {
          row[field] = enabled ? 0 : 1
        })
    },
    goToPage(path) {
      this.$router.push({
        path,
        query: {
          from: 'content-content'
        }
      })
    },
    selectSubmit() {
      if (!this.selection.length) {
        this.selectAlert()
      } else {
        const selectType = this.selectType
        if (selectType === 'editcate') {
          this.editcate(this.selection)
        } else if (selectType === 'edittag') {
          this.edittag(this.selection)
        } else if (selectType === 'istop') {
          this.istop(this.selection, true)
        } else if (selectType === 'ishot') {
          this.ishot(this.selection, true)
        } else if (selectType === 'isrec') {
          this.isrec(this.selection, true)
        } else if (selectType === 'disable') {
          this.disable(this.selection, true)
        } else if (selectType === 'release') {
          this.release(this.selection, true)
        } else if (selectType === 'dele') {
          this.dele(this.selection)
        }
        this.selectDialog = false
      }
    },
    // 修改分类
    editcate(row) {
      editcate({
        ids: this.selectGetIds(row),
        category_ids: this.category_ids
      })
        .then((res) => {
          this.list()
          this.setRecentActionSummary(`内容分类调整已提交，共 ${row.length} 条。`)
          ElMessage.success(res.msg)
        })
        .catch(() => {
          this.loading = false
        })
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
            this.setRecentActionSummary(`内容标签调整已提交，共 ${row.length} 条。`)
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.loading = false
          })
      }
    },
    // 是否置顶
    istop(row, select = false) {
      if (!row.length) {
        this.selectAlert()
      } else {
        this.loading = true
        var is_top = row[0].is_top
        if (select) {
          is_top = this.is_top
        }
        istop({
          ids: this.selectGetIds(row),
          is_top: is_top
        })
          .then((res) => {
            this.list()
            this.setRecentActionSummary(`${is_top === 1 ? '置顶' : '取消置顶'}已提交，共 ${row.length} 条。`)
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.list()
          })
      }
    },
    // 是否热门
    ishot(row, select = false) {
      if (!row.length) {
        this.selectAlert()
      } else {
        this.loading = true
        var is_hot = row[0].is_hot
        if (select) {
          is_hot = this.is_hot
        }
        ishot({
          ids: this.selectGetIds(row),
          is_hot: is_hot
        })
          .then((res) => {
            this.list()
            this.setRecentActionSummary(`${is_hot === 1 ? '热门' : '取消热门'}已提交，共 ${row.length} 条。`)
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.list()
          })
      }
    },
    // 是否推荐
    isrec(row, select = false) {
      if (!row.length) {
        this.selectAlert()
      } else {
        this.loading = true
        var is_rec = row[0].is_rec
        if (select) {
          is_rec = this.is_rec
        }
        isrec({
          ids: this.selectGetIds(row),
          is_rec: is_rec
        })
          .then((res) => {
            this.list()
            this.setRecentActionSummary(`${is_rec === 1 ? '推荐' : '取消推荐'}已提交，共 ${row.length} 条。`)
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
            this.setRecentActionSummary(`${is_disable === 1 ? '禁用' : '启用'}已提交，共 ${row.length} 条。`)
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.list()
          })
      }
    },
    // 发布时间
    release(row) {
      if (!row.length) {
        this.selectAlert()
      } else {
        this.loading = true
        release({
          ids: this.selectGetIds(row),
          release_time: this.release_time
        })
          .then((res) => {
            this.list()
            this.setRecentActionSummary(`发布时间调整已提交，共 ${row.length} 条。`)
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
            this.setRecentActionSummary(`内容删除已提交，共 ${row.length} 条。`)
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.loading = false
          })
      }
    }
  }
}
</script>

<style scoped>
.dialog-review-note__tags,
.select-review-panel__tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 12px;
}

.dialog-review-note__tag,
.select-review-panel__tags span {
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  padding: 0 10px;
  border-radius: 999px;
  background: #fff;
  border: 1px solid #dbe7f5;
  color: #334155;
  font-size: 12px;
}

.dialog-review-note__risk,
.select-review-panel__hint {
  margin-top: 12px;
  font-size: 12px;
  line-height: 1.6;
  color: #64748b;
}

.dialog-review-note {
  width: 100%;
}

.dialog-review-note--form {
  margin-bottom: 14px;
}

.content-summary-bar {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  margin-bottom: 16px;
  margin-top: 16px;
  padding: 14px 16px;
  border: 1px solid #dbe7f5;
  border-radius: 14px;
  background: #f8fbff;
}

.content-summary-bar__chips {
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

.content-summary-bar__hint {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 240px;
  padding: 12px 14px;
  border-radius: 12px;
  background: #eef4ff;
  color: #1d4ed8;
}

.content-summary-bar__hint.is-safe {
  background: #eaf8ef;
  color: #15803d;
}

.content-summary-bar__hint.is-warning {
  background: #fff5e8;
  color: #b45309;
}

.content-summary-bar__hint.is-active {
  background: #e8f0ff;
  color: #1d4ed8;
}

.content-summary-bar__hint-title {
  font-size: 12px;
  font-weight: 700;
}

.content-summary-bar__hint-text {
  font-size: 12px;
  line-height: 1.7;
}

.content-guide-panel {
  margin-bottom: 14px;
  padding: 14px 16px;
  border: 1px solid #dbe7f5;
  border-radius: 14px;
  background: linear-gradient(135deg, #f7fbff 0%, #ffffff 100%);
}

.content-guide-panel__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.content-guide-panel__title {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
}

.content-guide-panel__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.content-guide-panel__badge {
  min-width: 220px;
  padding: 10px 12px;
  border-radius: 12px;
  background: rgba(37, 99, 235, 0.08);
  color: #1d4ed8;
  font-size: 12px;
  font-weight: 700;
  line-height: 1.6;
}

.content-guide-panel__grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-top: 14px;
}

.content-guide-card {
  padding: 14px 16px;
  border: 1px solid #e7edf5;
  border-radius: 14px;
  background: #fff;
}

.content-guide-card__step {
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

.content-guide-card__title {
  margin-top: 10px;
  font-size: 14px;
  font-weight: 700;
  color: #0f172a;
}

.content-guide-card__desc {
  margin-top: 8px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.followup-panel {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin: 0 0 16px;
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
  font-size: 14px;
  font-weight: 700;
  color: #0f172a;
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
  background: #fff;
  border: 1px solid #dbe7f5;
  color: #334155;
  font-size: 12px;
}

.followup-panel__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  justify-content: flex-end;
}

.select-review-panel {
  width: 100%;
  padding: 12px 14px;
  border: 1px solid #dbe7f5;
  border-radius: 12px;
  background: #f8fbff;
}

.section-title {
  margin-bottom: 14px;
}

.section-title h3 {
  margin: 0 0 4px;
  font-size: 18px;
  color: #1f2329;
}

.section-title p {
  margin: 0;
  color: #7a8599;
  font-size: 13px;
}

.section-title--compact {
  margin-bottom: 10px;
}

@media (max-width: 900px) {
  .followup-panel,
  .content-summary-bar,
  .content-guide-panel__header {
    flex-direction: column;
  }

  .content-guide-panel__badge {
    min-width: 0;
  }

  .content-guide-panel__grid {
    grid-template-columns: 1fr;
  }
}
</style>
