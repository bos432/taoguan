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
            <el-form-item label="商品：" prop="goods_id">
              <el-select
                  v-model="query.goods_id"
                  @change="search()"
                  clearable
                  filterable
              >
                <el-option
                    v-for="item in goods_list"
                    :key="item.value"
                    :label="item.label"
                    :value="item.value"
                />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="批次：" prop="trace_batch_id">
              <el-select
                  v-model="query.trace_batch_id"
                  clearable
                  filterable
                  @change="search()"
              >
                <el-option
                    v-for="item in batch_list"
                    :key="item.value"
                    :label="item.label"
                    :value="item.value"
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
        <el-form-item v-if="selectType === 'disable'" label="是否禁用">
          <el-switch v-model="is_disable" :active-value="1" :inactive-value="0" />
        </el-form-item>
        <el-form-item v-else-if="selectType === 'dele'">
          <span class="c-red">确定要删除选中的{{ name }}吗？</span>
        </el-form-item>
        <el-form-item v-else-if="selectType === 'download'">
          <span class="c-red">确定要批量下载选中的{{ name }}吗？</span>
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
          <el-button title="批量下载" @click="selectOpen('download')">批量下载</el-button>
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
        <el-table-column prop="image_url" label="二维码" min-width="62" show-overflow-tooltip>
          <template #default="scope">
            <FileImage fileSource="list" :height="50" :file-url="scope.row.image_url" lazy />
          </template>
        </el-table-column>
        <el-table-column prop="goods_title" label="商品" min-width="150" show-overflow-tooltip />
        <el-table-column prop="batch_title" label="批次号" min-width="150" show-overflow-tooltip />
        <el-table-column prop="code" label="编码" min-width="150" show-overflow-tooltip />
        <el-table-column prop="scanning_num" label="扫码次数" min-width="90" show-overflow-tooltip />
        <el-table-column prop="remark" label="备注" min-width="120" show-overflow-tooltip />
        <el-table-column prop="create_time" label="添加时间" width="165" sortable="custom" />
        <el-table-column prop="scanning_time" label="初次扫码时间" width="165" sortable="custom" />
        <el-table-column label="操作" width="70">
          <template #default="scope">
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
      <el-form ref="ref" :rules="rules" :model="model" label-width="130px">
        <el-scrollbar native :max-height="height - 30">
          <el-form-item label="批次" prop="trace_batch_id">
            <el-select
                v-model="model.trace_batch_id"
                clearable
                filterable
                @change="batchChange"
            >
              <el-option
                  v-for="item in batch_list"
                  :key="item.value"
                  :label="item.label"
                  :value="item.value"
                  :disabled="item.disable ==1"
              />
            </el-select>
          </el-form-item>
          <el-form-item label="商品" prop="goods_id">
            <el-select
                v-model="model.goods_id"
                clearable
                filterable
                disabled
            >
              <el-option
                  v-for="item in goods_list"
                  :key="item.value"
                  :label="item.label"
                  :value="item.value"
              />
            </el-select>
          </el-form-item>
          <el-form-item label="数量" prop="apple_num">
            <el-input-number :controls="true" style="width: 100%;" v-model="model.apple_num" placeholder="请输入二维码生成数量" :min="0" :max="model.max_apple_num" :precision="0" :step="1"/>
          </el-form-item>
          <el-form-item label="备注" prop="remark">
            <el-input v-model="model.remark" placeholder="请输入备注" clearable />
          </el-form-item>
          <el-form-item label="是否下载二维码" prop="is_download">
            <el-radio-group v-model="model.is_download">
              <el-radio :value="1">是</el-radio>
              <el-radio :value="0">否</el-radio>
            </el-radio-group>
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
import { list, info, add, edit, dele, disable,getParams,download } from '@/api/trace/qrCode.js'
import {select as goodsSelect} from "@/api/goods/goods";
export default {
  name: 'qrCode',
  components: { Pagination, RichEditor },
  data() {
    return {
      name: '二维码',
      height: 680,
      loading: false,
      idkey: 'id',
      exps: [{ exp: 'like', name: '包含' }],
      query: {
        page: 1,
        limit: getPageLimit(),
        search_field: 'code',
        search_exp: 'like',
        date_field: 'create_time',
        is_disable:undefined,
        goods_id:undefined,
        trace_batch_id:undefined
      },
      data: [],
      count: 0,
      dialog: false,
      dialogTitle: '',
      model: {
        id: '',
        trace_batch_id:undefined,//批次id
        goods_id:undefined,//商品id
        goods_title:"",//商品名称
        remark:undefined,//备注
        apple_num:0,//申请数量
        max_apple_num:0,//最大申请数量
        is_download:1,//是否下载二维码
      },
      rules: {
        trace_batch_id: [{ required: true, message: '请输入批次号', trigger: 'blur' }],
        goods_id: [{ required: true, message: '请选择商品', trigger: 'blur' }],
        apple_num: [{ required: true, message: '请输入二维码生成数量', trigger: 'blur' },
          {
            validator: (rule, value, callback) => {
              if (value <= 0) {
                callback(new Error('生成数量必须大于0'));
              } else {
                callback();
              }
            },
            trigger: 'blur'
          }],
      },
      selection: [],
      selectIds: '',
      selectTitle: '操作',
      selectDialog: false,
      selectType: '',
      is_disable: 0,
      batch_list:[],
      goods_list:[],
    }
  },
  created() {
    this.height = screenHeight();
    this.list();
    this.selectParams();
    this.getGoodsList();
  },
  methods: {
    //批次选择
    batchChange(){
      let batch = this.batch_list.find(item => item.id === Number(this.model.trace_batch_id));
      if(batch){
        this.model.goods_id = batch.goods_id;
        this.model.goods_title = batch.goods_title;
        this.model.max_apple_num = batch.goods_num;
      }else{
        this.model.goods_id = undefined;
        this.model.goods_title = undefined;
        this.model.max_apple_num =0;
      }
    },
    //查询商品
    getGoodsList(){
      goodsSelect(this.query)
          .then((res) => {
            this.goods_list = res.data
          })
          .catch(() => {
          })
    },
    //查询批次
    selectParams(){
      getParams({})
          .then((res) => {
            this.batch_list = res.data.batch_list;
          })
          .catch(() => {
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
      this.dialogTitle = this.name + '创建'
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
          // ElMessage.error('请完善必填项（带红色星号*）')
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
        }else if (selectType === 'download') {
          this.selectTitle = this.name + '批量下载'
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
        }else if (selectType === 'download') {
          this.download(this.selection)
        }
        this.selectDialog = false
      }
    },
    // 批量下载
    download(row) {
      if (!row.length) {
        this.selectAlert()
      } else {
        download({
          ids: this.selectGetIds(row)
        })
            .then((res) => {
              ElMessage.success(res.msg)
            })
            .catch(() => {
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
