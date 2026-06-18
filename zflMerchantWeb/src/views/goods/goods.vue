<template>
  <div class="app-container">
    <el-card  class="app-head head-pb20">
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
            <el-form-item label="上架状态：" prop="is_disable">
              <el-select
                  v-model="query.is_disable"
                  @change="search()"
                  clearable
              >
                <el-option :value="0" label="上架" />
                <el-option :value="1" label="下架" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="商品状态：" prop="status">
              <el-select
                  v-model="query.status"
                  @change="search()"
                  clearable
              >
                <el-option :value="0" label="待审核" />
                <el-option :value="1" label="审核成功" />
                <el-option :value="2" label="审核失败" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="分类：" prop="goods_type_id">
              <el-cascader
                  v-model="query.goods_type_id"
                  :options="params.goods_types"
                  :props="typeProps"
                  clearable
                  filterable
                  collapse-tags
                  style="width: 100%;"
                  @change="search()"
              />
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="标签：" prop="goods_label_id">
              <el-select
                  v-model="query.goods_label_id"
                  clearable
                  filterable
                  collapse-tags
                  @change="search()"
              >
                <el-option
                    v-for="item in params.goods_labels"
                    :key="item.id"
                    :label="item.title"
                    :value="item.id"
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
                  <el-option value="code" label="编码" />
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
            v-model="goods_type_id"
            :options="params.goods_types"
            :props="typeProps"
            class="w-full"
            clearable
            filterable
          />
        </el-form-item>
        <el-form-item v-else-if="selectType === 'edittag'" label="标签">
          <el-select v-model="goods_label_id" multiple clearable filterable class="w-full">
            <el-option
              v-for="item in params.goods_labels"
              :key="item.id"
              :label="item.title"
              :value="item.id"
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
        <el-form-item v-else-if="selectType === 'disable'" label="是否上架">
          <el-switch
              v-model="is_disable"
              class="ml-2"
              style="--el-switch-on-color: #4073fa; --el-switch-off-color: #ff4949"
              inline-prompt
              active-text="上架"
              inactive-text="下架"
              :active-value="0"
              :inactive-value="1"
          />
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
      </el-form>
      <template #footer>
        <el-button :loading="loading" @click="selectCancel">取消</el-button>
        <el-button :loading="loading" type="primary" @click="selectSubmit">提交</el-button>
      </template>
    </el-dialog>
    <el-card  class="app-main">
      <div class="operation_btn mb5">
        <!-- 操作 -->
        <div>
          <el-button type="primary" @click="add()">添加</el-button>
          <el-button title="删除" @click="selectOpen('dele')">删除</el-button>
          <el-button title="是否上架" @click="selectOpen('disable')">上架</el-button>
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
        <el-table-column prop="code" label="编码" min-width="75" show-overflow-tooltip />
        <el-table-column prop="image_url" label="缩略图" min-width="62" show-overflow-tooltip>
          <template #default="scope">
            <FileImage :file-url="scope.row.image_url" lazy />
          </template>
        </el-table-column>
        <el-table-column prop="title" label="商品名称" min-width="175" show-overflow-tooltip />
        <el-table-column prop="type_title" label="商品分类" min-width="105" show-overflow-tooltip />
        <el-table-column prop="label_title" label="商品标签" min-width="105" show-overflow-tooltip />
        <el-table-column prop="is_disable" label="上架状态" min-width="95" sortable="custom">
          <template #default="scope">
            <el-switch
                v-model="scope.row.is_disable"
                class="ml-2"
                style="--el-switch-on-color: #4073fa; --el-switch-off-color: #ff4949"
                inline-prompt
                active-text="上架"
                inactive-text="下架"
                :active-value="0"
                :inactive-value="1"
                @change="disable([scope.row])"
            />
          </template>
        </el-table-column>
        <el-table-column
          prop="stock"
          label="库存"
          min-width="85"
          show-overflow-tooltip
          sortable="custom"
        />
        <el-table-column prop="status_title" label="审核状态" min-width="105" show-overflow-tooltip >
          <template #default="scope">
            <el-tag type="info" v-if="scope.row.status == 0">待审核</el-tag>
            <el-tag type="success"  v-else-if="scope.row.status == 1">审核成功</el-tag>
            <el-tag type="danger"  v-else-if="scope.row.status == 2">审核失败</el-tag>
            <div v-if="scope.row.status == 2 && scope.row.auth_msg">
              <el-text type="danger">{{scope.row.auth_msg}}</el-text>
            </div>
          </template>
        </el-table-column>
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
        <el-tabs>
          <el-tab-pane label="信息">
            <el-scrollbar native :max-height="height - 30">
              <el-form-item label="缩略图" prop="image_id">
                <FileImage
                  v-model="model.image_id"
                  :file-url="model.image_url"
                  :height="100"
                  upload
                />
              </el-form-item>
              <el-form-item label="轮播图" prop="images">
                <FileUploads
                    v-model="model.images"
                    upload-btn="上传图片"
                    file-type="image"
                    file-tip="图片文件"
                />
              </el-form-item>
              <el-form-item label="商品分类" prop="goods_type_id">
                <el-cascader
                  v-model="model.goods_type_id"
                  :options="params.goods_types"
                  :props="typeProps"
                  class="w-full"
                  clearable
                  filterable
                  @change="getCode"
                />
              </el-form-item>
              <el-form-item label="商品编码" prop="code">
                <el-input v-model="model.code" placeholder="请输入编码（唯一）" clearable />
              </el-form-item>
              <el-form-item label="商品标签" prop="goods_label_id">
                <el-select v-model="model.goods_label_id" class="w-full" clearable filterable multiple disabled>
                  <el-option
                    v-for="item in params.goods_labels"
                    :key="item.id"
                    :label="item.title"
                    :value="item.id"
                  />
                </el-select>
              </el-form-item>
              <el-form-item label="商品名称" prop="title">
                <el-input v-model="model.title" placeholder="请输入商品名称" clearable />
              </el-form-item>
              <el-form-item label="商品规格" prop="spec">
                <el-input v-model="model.spec" placeholder="请输入商品规格" clearable />
              </el-form-item>
              <el-form-item label="计量单位" prop="unit">
                <el-input v-model="model.unit" placeholder="请输入计量单位" clearable />
              </el-form-item>
              <el-form-item label="商品原价" prop="original_price">
                <el-input-number :controls="false"  style="width: 100%;text-align: left;" v-model="model.original_price" placeholder="请输入商品原价" :min="0.01" :precision="2" :step="1"/>
              </el-form-item>
              <el-form-item label="商品价格" prop="price">
                <el-input-number :controls="false"  style="width: 100%;" v-model="model.price" placeholder="请输入商品价格" :min="0.01" :precision="2" :step="1"/>
              </el-form-item>
              <el-form-item label="商品库存" prop="stock">
                <el-input-number disabled :controls="false"  style="width: 100%;" v-model="model.stock" placeholder="请输入商品库存" :min="0" :precision="0" :step="1"/>
              </el-form-item>
              <el-form-item label="备注" prop="remark">
                <el-input v-model="model.remark" placeholder="请输入备注" clearable />
              </el-form-item>
              <el-form-item label="排序" prop="sort">
                <el-input v-model="model.sort" type="number" placeholder="请输入排序" clearable />
              </el-form-item>
              <el-form-item v-if="model[idkey]" label="商品销量" prop="sales_sum">
                <el-input-number :controls="false" disabled style="width: 100%;" v-model="model.sales_sum" placeholder="请输入商品销量" :min="0" :precision="0" :step="1"/>
              </el-form-item>
              <el-form-item v-if="model[idkey]" label="浏览量" prop="click_count">
                <el-input-number :controls="false" disabled style="width: 100%;" v-model="model.click_count" placeholder="请输入浏览量" :min="0" :precision="0" :step="1"/>
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
import {
  list,
  info,
  add,
  edit,
  dele,
  disable,
  params,
  code
} from '@/api/goods/goods'

export default {
  name: 'goods',
  components: { Pagination, RichEditor },

  data() {
    return {
      name: '商品',
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
        is_top:undefined,
        is_hot:undefined,
        is_rec:undefined,
        goods_type_id:undefined,
        goods_label_id:undefined,
        setting_hall_id:undefined,
      },
      data: [],
      count: 0,
      dialog: false,
      dialogTitle: '',
      model: {
        id: '',
        code: '',
        goods_type_id: null,
        goods_label_id: [],
        image_id: 0,
        image_url: '',
        title: '',
        content: '',
        remark: '',
        sort: 250,
        images: [],
        original_price:undefined,//原价（只做显示用）
        price:undefined,//售价
        sales_sum:undefined,//销量
        click_count:undefined,//商品点击量
        spec:undefined,//商品规格
        unit:undefined,//单位
        stock:undefined,//总库存
        video_id:undefined,//商品视频
        setting_hall_id:undefined,//所在大厅
      },
      rules: {
        title: [{ required: true, message: '请输入商品名称', trigger: 'blur' }],
        code: [{ required: true, message: '请输入商品编码', trigger: 'blur' }],
        image_id: [{ required: true, message: '请上传缩略图', trigger: 'blur' }],
        goods_type_id: [{ required: true, message: '请选择商品分类', trigger: 'blur' }],
        price: [{ required: true, message: '请输入商品价格', trigger: 'blur' }],
      },
      typeProps: {
        checkStrictly: false,
        value: 'id',
        label: 'title',
        multiple: false,
        emitPath: false
      },
      selection: [],
      selectIds: '',
      selectTitle: '操作',
      selectDialog: false,
      selectType: '',
      goods_type_id: [],
      goods_label_id: [],
      is_top: 0,
      is_hot: 0,
      is_rec: 0,
      is_disable: 0,
      release_time: '',
      params:{},
    }
  },
  created() {
    this.height = screenHeight()
    this.getParams()
    this.list()
  },
  methods: {
    //获取商品编码
    getCode(){
      if(!this.model.id){
        code({goods_type_id:this.model.goods_type_id})
            .then((res) => {
              this.model.code = res.data;
            })
            .catch(() => {
            })
      }
    },
    // 参数
    getParams() {
      this.loading = true
      params({})
          .then((res) => {
            this.params = res.data
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
          if (this.model[this.idkey]) {
            edit(this.model)
              .then((res) => {
                this.list()
                this.dialog = false
                ElMessage.success(res.msg)
              })
              .catch(() => {})
          } else {
            add(this.model)
              .then((res) => {
                this.list()
                this.dialog = false
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
        goods_type_id: this.goods_type_id
      })
        .then((res) => {
          this.list()
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
          goods_label_id: this.goods_label_id
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
    }
  }
}
</script>
