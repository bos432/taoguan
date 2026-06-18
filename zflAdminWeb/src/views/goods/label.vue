<template>
  <div class="app-container">
    <el-card  class="app-head">
      <div class="section-title-row">
        <div>
          <div class="section-title-row__title">商品标签</div>
          <div class="section-title-row__desc">保留筛选、批量维护和启停能力，首屏信息收敛为轻量概览。</div>
        </div>
        <div class="section-title-row__meta">当前检索：{{ currentSearchLabel }} / {{ query.search_value || '未设置关键词' }}</div>
      </div>
      <div class="goods-plain-guide">
        <div class="goods-plain-guide__header">
          <div>
            <div class="goods-plain-guide__title">商品标签第一次进来，先分清它和分类的区别</div>
            <div class="goods-plain-guide__desc">
              标签更偏活动、人群、专题和运营聚合，不是商品主归属；如果你是在整理商品树，通常应该先去商品分类。
            </div>
          </div>
          <div class="goods-plain-guide__badge">{{ goodsLabelGuideFocusLabel }}</div>
        </div>
        <div class="goods-plain-guide__grid">
          <div
            v-for="item in goodsLabelGuideCards"
            :key="item.title"
            class="goods-plain-guide-card"
          >
            <span class="goods-plain-guide-card__step">{{ item.step }}</span>
            <div class="goods-plain-guide-card__title">{{ item.title }}</div>
            <div class="goods-plain-guide-card__desc">{{ item.desc }}</div>
          </div>
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
                  <el-option value="title" label="名称" />
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
      <div class="summary-bar">
        <div class="summary-bar__item">
          <span class="summary-bar__label">标签总量</span>
          <strong class="summary-bar__value">{{ count || 0 }}</strong>
        </div>
        <div class="summary-bar__item">
          <span class="summary-bar__label">已选</span>
          <strong class="summary-bar__value">{{ selection.length }} 项</strong>
        </div>
        <div class="summary-bar__item">
          <span class="summary-bar__label">状态</span>
          <strong class="summary-bar__value">{{ currentStatusText }}</strong>
        </div>
        <div class="summary-bar__item">
          <span class="summary-bar__label">关键词</span>
          <strong class="summary-bar__value">{{ query.search_value || '未设置' }}</strong>
        </div>
        <div class="summary-bar__item summary-bar__item--wide">
          <span class="summary-bar__label">当前操作</span>
          <strong class="summary-bar__value summary-bar__value--text">{{ currentActionText }}</strong>
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
          <div class="dialog-review-note__title">提交前确认</div>
          <div class="dialog-review-note__tags">
            <span class="dialog-review-note__tag">操作：{{ currentActionText }}</span>
            <span class="dialog-review-note__tag">状态：{{ currentStatusText }}</span>
            <span class="dialog-review-note__tag">对象：{{ selectIds || '未选择' }}</span>
          </div>
        </div>
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
      <div class="section-title-row section-title-row--compact">
        <div>
          <div class="section-title-row__title">标签列表</div>
          <div class="section-title-row__desc">支持标签维护、排序、批量禁用和删除。</div>
        </div>
        <div class="section-title-row__meta">已选 {{ selection.length }} 项 / 共 {{ count || 0 }} 项</div>
      </div>
      <div class="operation_btn mb5">
        <!-- 操作 -->
        <div>
          <el-button type="primary" @click="add()">添加</el-button>
          <el-button title="删除" @click="selectOpen('dele')">删除</el-button>
          <el-button title="是否禁用" @click="selectOpen('disable')">禁用</el-button>
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
        @cell-dblclick="cellDbclick"
      >
        <el-table-column type="selection" width="42" title="全选/反选" />
        <el-table-column :prop="idkey" label="ID" width="80" sortable="custom" />
        <el-table-column
          prop="title"
          label="名称"
          min-width="120"
          sortable="custom"
          show-overflow-tooltip
        />
        <el-table-column prop="remark" label="备注" min-width="150" show-overflow-tooltip />
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
      destroy-on-close
    >
      <el-form ref="ref" :rules="rules" :model="model" label-width="100px">
        <el-scrollbar native :max-height="height - 30">
          <el-form-item label="名称" prop="title">
            <el-input v-model="model.title" placeholder="请输入名称" clearable>
              <template #append>
                <el-button title="复制" @click="copy(model.title)">
                  <svg-icon icon-class="copy-document" />
                </el-button>
              </template>
            </el-input>
          </el-form-item>
          <el-form-item label="备注" prop="remark">
            <el-input v-model="model.remark" placeholder="请输入备注" clearable />
          </el-form-item>
          <el-form-item label="排序" prop="sort">
            <el-input v-model="model.sort" type="number" />
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
import clip from '@/utils/clipboard'
import { arrayColumn } from '@/utils/index'
import { getPageLimit } from '@/utils/settings'
import { shortcuts } from '@/utils/getDate.js'
import { list, info, add, edit, dele, disable } from '@/api/goods/label'

export default {
  name: 'label',
  components: { Pagination, RichEditor },
  computed: {
    currentSearchLabel() {
      const fieldMap = {
        id: 'ID',
        unique: '标识',
        title: '名称',
        remark: '备注'
      }
      return fieldMap[this.query.search_field] || '综合筛选'
    },
    currentStatusText() {
      if (this.query.is_disable === 0) {
        return '仅看启用'
      }
      if (this.query.is_disable === 1) {
        return '仅看禁用'
      }
      return '全部状态'
    },
    currentActionText() {
      if (this.selectType === 'disable') {
        return `批量${this.is_disable === 1 ? '禁用' : '启用'} ${this.selection.length || 0} 项`
      }
      if (this.selectType === 'dele') {
        return `批量删除 ${this.selection.length || 0} 项`
      }
      if (this.selection.length) {
        return `待处理 ${this.selection.length} 项`
      }
      return '可执行新增、批量禁用和删除'
    },
    goodsLabelGuideFocusLabel() {
      if (this.selection.length) {
        return `当前重点：已圈定 ${this.selection.length} 个标签，先确认会不会影响商品专题聚合`
      }
      if (this.query.is_disable === 1) {
        return '当前重点：先核对禁用标签是不是历史活动标签，不要误删仍在复用的运营入口'
      }
      if (this.query.search_value) {
        return '当前重点：先看关键词命中的标签是不是同一批运营主题，再决定保留还是清理'
      }
      return '当前重点：先区分“标签做运营聚合”还是“分类做主归属”，避免把两种结构混着改'
    },
    goodsLabelGuideCards() {
      return [
        {
          step: '第一步',
          title: '先判断是在建运营入口，还是在清理旧标签',
          desc: '活动、专题、人群承接通常走标签；如果是历史标签过多或重复，再按状态和关键词先缩小清理范围。'
        },
        {
          step: '第二步',
          title: '标签本身和商品归属不要混着改',
          desc: '标签更像商品的运营侧聚合条件，商品真正归到哪一类、放在哪个树层，还是要回商品分类和商品列表处理。'
        },
        {
          step: '第三步',
          title: '改完回商品列表和分类页复核',
          desc: '标签页只改名字、启停和聚合入口，最终还是要回商品主列表看命中商品，再去分类页确认主结构没跑偏。'
        }
      ]
    }
  },
  data() {
    return {
      name: '商品标签',
      height: 680,
      loading: false,
      idkey: 'id',
      exps: [{ exp: 'like', name: '包含' }],
      query: {
        page: 1,
        limit: getPageLimit(),
        search_field: 'title',
        search_exp: 'like',
        date_field: 'create_time',
        is_disable:undefined,
      },
      data: [],
      count: 0,
      dialog: false,
      dialogTitle: '',
      model: {
        id: '',
        title: '',
        remark: '',
        sort: 250
      },
      rules: {
        title: [{ required: true, message: '请输入名称', trigger: 'blur' }]
      },
      selection: [],
      selectIds: '',
      selectTitle: '操作',
      selectDialog: false,
      selectType: '',
      is_disable: 0,
      shortcuts: shortcuts(),
    }
  },
  created() {
    this.height = screenHeight()
    this.list()
  },
  methods: {
    // 列表
    list() {
      this.loading = true
      list(this.query)
        .then((res) => {
          this.data = res.data.list
          this.count = res.data.count
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
        if (selectType === 'disable') {
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
        if (selectType === 'disable') {
          this.disable(this.selection, true)
        } else if (selectType === 'dele') {
          this.dele(this.selection)
        }
        this.selectDialog = false
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
            ElMessage.success(res.msg)
          })
          .catch(() => {
            this.loading = false
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
.section-title-row {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 16px;
}

.section-title-row--compact {
  margin-bottom: 14px;
}

.section-title-row__title {
  color: #0f172a;
  font-size: 18px;
  font-weight: 600;
  line-height: 1.4;
}

.section-title-row__desc {
  margin-top: 4px;
  color: #64748b;
  font-size: 13px;
  line-height: 1.6;
}

.section-title-row__meta {
  color: #64748b;
  font-size: 12px;
  line-height: 1.8;
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

.summary-bar__item--wide {
  grid-column: span 2;
}

.summary-bar__label,
.dialog-review-note__title {
  color: #475569;
  font-size: 12px;
  font-weight: 700;
}

.summary-bar__value {
  display: block;
  margin-top: 4px;
  color: #0f172a;
  font-size: 14px;
  font-weight: 600;
}

.summary-bar__value--text {
  font-size: 13px;
  line-height: 1.6;
}

.goods-plain-guide {
  margin-bottom: 16px;
  padding: 16px;
  border: 1px solid #dbe7f5;
  border-radius: 16px;
  background: linear-gradient(135deg, #f7fbff 0%, #ffffff 100%);
}

.goods-plain-guide__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.goods-plain-guide__title {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
}

.goods-plain-guide__desc {
  margin-top: 6px;
  font-size: 12px;
  color: #64748b;
  line-height: 1.7;
}

.goods-plain-guide__badge {
  min-width: 220px;
  padding: 10px 12px;
  border-radius: 12px;
  background: rgba(37, 99, 235, 0.08);
  color: #1d4ed8;
  font-size: 12px;
  font-weight: 700;
  line-height: 1.6;
}

.goods-plain-guide__grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-top: 14px;
}

.goods-plain-guide-card {
  padding: 14px 16px;
  border: 1px solid #e7edf5;
  border-radius: 14px;
  background: #fff;
}

.goods-plain-guide-card__step {
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

.goods-plain-guide-card__title {
  margin-top: 10px;
  font-size: 14px;
  font-weight: 700;
  color: #0f172a;
}

.goods-plain-guide-card__desc {
  margin-top: 8px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.dialog-review-note {
  margin-bottom: 16px;
  padding: 14px 16px;
  border: 1px solid #dbe7f5;
  border-radius: 14px;
  background: #f8fbff;
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

@media (max-width: 768px) {
  .section-title-row,
  .goods-plain-guide__header {
    flex-direction: column;
  }

  .summary-bar__item--wide {
    grid-column: span 1;
  }

  .goods-plain-guide__badge {
    min-width: 0;
  }

  .goods-plain-guide__grid {
    grid-template-columns: 1fr;
  }
}
</style>
