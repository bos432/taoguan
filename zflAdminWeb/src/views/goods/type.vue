<template>
  <div class="app-container">
    <el-card  class="app-head head-pb20">
      <div class="section-title-row">
        <div>
          <div class="section-title-row__title">商品分类</div>
          <div class="section-title-row__desc">保留分类树、筛选和批量调整能力，首屏概览收敛为线上轻量样式。</div>
        </div>
        <div class="section-title-row__meta">当前检索：{{ currentSearchLabel }} / {{ query.search_value || '未设置关键词' }}</div>
      </div>
      <div class="goods-plain-guide">
        <div class="goods-plain-guide__header">
          <div>
            <div class="goods-plain-guide__title">商品分类第一次进来，先把树形结构想清楚</div>
            <div class="goods-plain-guide__desc">
              分类是商品主归属，影响上架路径和后台运营查看习惯；如果只是做活动聚合，优先回商品标签，不要在这里硬拆树。
            </div>
          </div>
          <div class="goods-plain-guide__badge">{{ goodsTypeGuideFocusLabel }}</div>
        </div>
        <div class="goods-plain-guide__grid">
          <div
            v-for="item in goodsTypeGuideCards"
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
            <el-form-item label="上级：" prop="pid">
              <el-cascader
                  v-model="query.pid"
                  :options="trees"
                  :props="props"
                  clearable
                  filterable
                  style="width: 100%;"
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
                  <el-option value="code" label="编码" />
                  <el-option value="title" label="名称" />
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
          <span class="summary-bar__label">已选</span>
          <strong class="summary-bar__value">{{ selection.length }} 项</strong>
        </div>
        <div class="summary-bar__item">
          <span class="summary-bar__label">状态</span>
          <strong class="summary-bar__value">{{ currentStatusText }}</strong>
        </div>
        <div class="summary-bar__item">
          <span class="summary-bar__label">树形视图</span>
          <strong class="summary-bar__value">{{ treeModeText }}</strong>
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
        <el-form-item v-if="selectType === 'removec'">
          <span style="">确定要解除选中的{{ name }}的内容吗？</span>
        </el-form-item>
        <el-form-item v-else-if="selectType === 'editpid'" label="上级">
          <el-cascader
              v-model="pid"
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
      <div class="section-title-row section-title-row--compact">
        <div>
          <div class="section-title-row__title">分类列表</div>
          <div class="section-title-row__desc">支持分类层级、排序、禁用和图片维护。</div>
        </div>
        <div class="section-title-row__meta">已选 {{ selection.length }} 项 / 共 {{ count || 0 }} 条</div>
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
      >
        <el-table-column type="selection" width="42" title="全选/反选" />
        <el-table-column :prop="idkey" label="ID" min-width="80" />
        <el-table-column prop="code" label="编码" min-width="100" show-overflow-tooltip />
        <el-table-column prop="title" label="名称" min-width="250" show-overflow-tooltip />
        <el-table-column prop="image_url" label="图片" min-width="62">
          <template #default="scope">
            <FileImage :file-url="scope.row.image_url" lazy fileSource="list"/>
          </template>
        </el-table-column>
        <el-table-column prop="is_disable" label="禁用" min-width="85">
          <template #default="scope">
            <el-switch
                v-model="scope.row.is_disable"
                :active-value="1"
                :inactive-value="0"
                @change="disable([scope.row])"
            />
          </template>
        </el-table-column>
        <el-table-column prop="sort" label="排序" min-width="85" />
        <el-table-column prop="create_time" label="添加时间" width="165" />
        <el-table-column prop="update_time" label="修改时间" width="165" />
        <el-table-column label="操作" width="170">
          <template #default="scope">
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
          <el-form-item label="上级" prop="pid">
            <el-cascader
                v-model="model.pid"
                :options="trees"
                :props="props"
                class="w-full"
                placeholder="一级分类"
                clearable
                filterable
            />
          </el-form-item>
          <el-form-item label="编码" prop="code">
            <el-input
                v-model="model.code"
                placeholder="请输入分类编码（唯一）"
                clearable
            />
          </el-form-item>
          <el-form-item label="名称" prop="title">
            <el-input v-model="model.title" placeholder="请输入分类名称" clearable />
          </el-form-item>
          <el-form-item label="图标" prop="image_id">
            <FileImage v-model="model.image_id" :file-url="model.image_url" :height="100" upload />
          </el-form-item>
          <el-form-item label="排序" prop="sort">
            <el-input v-model="model.sort" placeholder="250" clearable />
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
  </div>
</template>

<script>
import screenHeight from '@/utils/screen-height'
import Pagination from '@/components/Pagination/index.vue'
import { arrayColumn } from '@/utils/index'
import { shortcuts } from '@/utils/getDate.js'
import {
  list,
  info,
  add,
  edit,
  dele,
  disable,
  editpid,
} from '@/api/goods/type'

export default {
  name: 'GoodsType',
  components: { Pagination },
  computed: {
    currentSearchLabel() {
      const fieldMap = {
        id: 'ID',
        code: '编码',
        title: '名称'
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
    treeModeText() {
      return this.isExpandAll ? '当前收起模式' : '当前展开模式'
    },
    currentActionText() {
      if (this.selectType === 'editpid') {
        return `批量修改上级 ${this.selection.length || 0} 项`
      }
      if (this.selectType === 'disable') {
        return `批量${this.is_disable === 1 ? '禁用' : '启用'} ${this.selection.length || 0} 项`
      }
      if (this.selectType === 'dele') {
        return `批量删除 ${this.selection.length || 0} 项`
      }
      if (this.selection.length) {
        return `待处理 ${this.selection.length} 项`
      }
      return '可执行新增、上级调整、批量禁用和删除'
    },
    goodsTypeGuideFocusLabel() {
      if (this.selection.length) {
        return `当前重点：已圈定 ${this.selection.length} 个分类，先确认是不是同一树层再批量改上级`
      }
      if (this.query.pid) {
        return '当前重点：正在按上级分类查看，先确认这一层是不是你真正想调整的主目录'
      }
      if (this.query.is_disable === 1) {
        return '当前重点：先核对禁用分类下面还有没有商品，避免目录停用后商品无处承接'
      }
      return '当前重点：先判断是要改分类树主结构，还是只是做运营聚合，不要把标签活放到分类树里'
    },
    goodsTypeGuideCards() {
      return [
        {
          step: '第一步',
          title: '先决定你改的是树结构，还是某个节点信息',
          desc: '改上级、补下级属于树结构调整；改名称、编码、图标和排序属于节点信息维护，两类动作风险不一样。'
        },
        {
          step: '第二步',
          title: '批量改上级前先确认是否同层',
          desc: '如果把不同层级、不同业务线的分类混着迁上级，后面商品列表和筛选习惯会一起跑偏，建议先按上级筛一层再动。'
        },
        {
          step: '第三步',
          title: '改完回商品列表和标签页复核',
          desc: '分类树只决定主归属，真正商品是否还在正确目录、活动是否还能聚合出来，还是要回商品列表和标签页再看一遍。'
        }
      ]
    }
  },
  data() {
    return {
      name: '商品分类',
      height: 680,
      loading: false,
      idkey: 'id',
      query: {
        search_field: 'title',
        search_exp: 'like',
        date_field: 'create_time',
        pid:undefined,
        is_disable:undefined
      },
      exps: [{ exp: 'like', name: '包含' }],
      data: [],
      count: '',
      dialog: false,
      dialogTitle: '',
      model: {
        id: '',
        code: '',
        pid: 0,
        title: '',
        image_id: 0,
        image_url: '',
        sort: 250,
      },
      rules: {
        code: [{ required: true, message: '请输入分类编码', trigger: 'blur' }],
        title: [{ required: true, message: '请输入分类名称', trigger: 'blur' }]
      },
      trees: [],
      props: { checkStrictly: true, value: 'id', label: 'title', emitPath: false },
      isExpandAll: false,
      selection: [],
      selectIds: '',
      selectTitle: '操作',
      selectDialog: false,
      selectType: '',
      pid: '',
      is_disable: 0,
      shortcuts: shortcuts(),
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
        this.model.pid = row[this.idkey]
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
        if (selectType == 'editpid') {
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
        if (selectType == 'editpid') {
          this.editpid(this.selection)
        } else if (selectType === 'disable') {
          this.disable(this.selection, true)
        } else if (selectType === 'dele') {
          this.dele(this.selection)
        }
        this.selectDialog = false
      }
    },
    // 修改上级
    editpid(row) {
      editpid({
        ids: this.selectGetIds(row),
        pid: this.pid
      })
          .then((res) => {
            this.list()
            this.selectDialog = false
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
