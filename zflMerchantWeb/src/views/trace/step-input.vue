<template>
  <div class="app-container">
    <el-card  class="app-head">
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
            <el-form-item label="环节：" prop="trace_tache_id">
              <el-select
                  v-model="query.trace_tache_id"
                  clearable
                  filterable
                  @change="search()"
              >
                <el-option
                    v-for="item in tache_list"
                    :key="item.value"
                    :label="item.label"
                    :value="item.value"
                />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="6">
            <el-form-item label="商品：" prop="goods_id">
              <el-select
                  v-model="query.goods_id"
                  clearable
                  filterable
                  @change="search()"
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
        <el-table-column prop="goods_title" label="商品" min-width="150" show-overflow-tooltip />
        <el-table-column
            prop="batch_title"
            label="批次号"
            min-width="120"
            show-overflow-tooltip
        />
        <el-table-column prop="tache_title" label="环节" min-width="150" show-overflow-tooltip />
        <el-table-column prop="value" label="属性" min-width="150">
          <template #default="scope">
            <el-popover placement="top-start" :width="500" trigger="hover">
              <template #reference>
                <div class="ellipsis-text">{{scope.row.value_str}}</div>
              </template>
              <el-table :data="scope.row.tacheValue" :show-header="false">
                <el-table-column min-width="100" prop="label" label="名称" />
                <el-table-column min-width="200" prop="value" label="值" >
                  <template #default="sc">
                    <template v-if="sc.row.is_inspection_type ==1 && sc.row.inspection_id">
                      <el-tag type="info" v-if="sc.row.inspection_state==0">待检测</el-tag>
                      <el-tag type="success" v-else-if="sc.row.inspection_state==1">已检测</el-tag>
                      <el-tag type="danger" v-if="sc.row.inspection_state==2">
                        检测失败
                        <template v-if="sc.row.inspection_remark">
                          ({{sc.row.inspection_remark}})
                        </template>
                      </el-tag>
                      {{sc.row.inspection_title}}
                      {{sc.row.inspection_result||sc.row.inspection_time ?'(':''}}
                      {{sc.row.inspection_result?"检测结果:"+sc.row.inspection_result:''}}
                      {{sc.row.inspection_time?",检测时间:"+sc.row.inspection_time:''}}
                      {{sc.row.inspection_result||sc.row.inspection_time ?')':''}}
                    </template>
                    <template v-else>
                      {{sc.row.value}}
                    </template>
                  </template>
                </el-table-column>
              </el-table>
            </el-popover>
          </template>
        </el-table-column>
        <el-table-column prop="remark" label="备注" min-width="150" show-overflow-tooltip >
          <template #default="scope">
            <el-tag type="danger" v-if="scope.row.refuse">{{scope.row.refuse}}</el-tag>
            <template v-else>{{scope.row.remark}}</template>
          </template>
        </el-table-column>
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
      <el-form ref="ref" :rules="rules" :model="model" label-width="150px">
        <el-scrollbar native :max-height="height - 30">
          <el-form-item label="批次" prop="trace_batch_id">
            <el-select
                v-model="model.trace_batch_id"
                clearable
                filterable
                :disabled="model[idkey]>0"
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
          <el-form-item label="环节" prop="trace_tache_id">
            <el-select
                v-model="model.trace_tache_id"
                clearable
                filterable
                :disabled="model[idkey]>0"
                @change="tacheChange"
            >
              <el-option
                  v-for="item in tache_list"
                  :key="item.value"
                  :label="item.label"
                  :value="item.value"
                  :disabled="item.disable ==1"
              />
            </el-select>
          </el-form-item>
          <template v-for="(item,index) in model.tacheValue">
            <!---------------------自检------------------------->
            <template v-if="item.is_inspection_type==2">
              <el-form-item :label="item.label">
                <el-input  v-model="item.value" :placeholder="'请输入'+item.label" clearable/>
              </el-form-item>
              <el-form-item label="">
                <FileUploads
                    v-model="item.reports"
                    upload-btn="上传检测报告"
                    file-type="other"
                />
              </el-form-item>
            </template>
            <!---------------------送检------------------------->
            <template v-else-if="item.is_inspection_type==1">
              <el-form-item :label="item.label">
                <el-select
                    placeholder="请选择检测机构"
                    v-model="item.inspection_id"
                    :disabled="model[idkey]>0 && item.inspection_state!=2"
                    @change="inspectionChange(index)"
                    clearable
                    filterable
                >
                  <el-option
                      v-for="inspection in inspection_list"
                      :key="inspection.value"
                      :label="inspection.label"
                      :value="inspection.value"
                      :disabled="inspection.disable ==1"
                  />
                </el-select>
                <el-text type="info" class="tip" v-if="model[idkey]>0 && item.inspection_state==0">
                  <el-icon><InfoFilled /></el-icon>
                  待检测
                </el-text>
              </el-form-item>
              <el-form-item label="联系电话" v-if="item.inspection_phone">
                <el-input v-model="item.inspection_phone" placeholder="请输入联系电话" disabled></el-input>
              </el-form-item>
              <el-form-item label="机构地址" v-if="item.inspection_address">
                <el-input v-model="item.inspection_address" placeholder="请输入机构地址" disabled></el-input>
              </el-form-item>
              <el-form-item :label="item.label" v-if="model[idkey]>0 && item.inspection_state!=0">
                <el-input :class="item.inspection_state==1?'input_success':'input_red'"  v-model="item.inspection_result" :placeholder="'请输入'+item.label" clearable disabled/>
                <el-text :type="item.inspection_state==1?'success':'danger'" class="tip">
                  <el-icon><InfoFilled /></el-icon>
                  <el-tag type="danger" v-if="item.inspection_state==2">
                    检测失败
                    <template v-if="item.inspection_remark">
                      ({{item.inspection_remark}})
                    </template>
                  </el-tag>
                  <el-text :type="item.inspection_state==1?'success':'danger'">检测时间：{{item.inspection_time}}</el-text>
                </el-text>
              </el-form-item>
              <el-form-item label="" v-if="model[idkey]>0 && item.inspection_state!=0 && item.inspection_reports && item.inspection_reports.length>0">
                <FileUploads
                    v-model="item.inspection_reports"
                    upload-btn="上传检测报告"
                    file-type="other"
                    fileSource="list"
                />
              </el-form-item>
            </template>
            <!---------------------免检------------------------->
            <el-form-item :label="item.label" v-else>
              <el-input  v-model="item.value" :placeholder="'请输入'+item.label" clearable/>
            </el-form-item>
          </template>
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
import { list, info, add, edit, dele, disable } from '@/api/trace/batchTache'
import { select as batchSelect} from '@/api/trace/batch'
import { select as tacheSelect } from '@/api/trace/tache'
import { select as inspectionSelect } from '@/api/inspection/inspection.js'
import {select as goodsSelect} from "@/api/goods/goods";
import { InfoFilled} from '@element-plus/icons-vue';
export default {
  name: 'step-input',
  components: { Pagination, RichEditor,InfoFilled },
  data() {
    return {
      name: '批次环节',
      height: 680,
      loading: false,
      idkey: 'id',
      exps: [{ exp: 'like', name: '包含' }],
      query: {
        page: 1,
        limit: getPageLimit(),
        search_field: 'remark',
        search_exp: 'like',
        date_field: 'create_time',
        is_disable:undefined,
        trace_batch_id:undefined,
        goods_id:undefined,
        trace_tache_id:undefined,
      },
      data: [],
      count: 0,
      dialog: false,
      dialogTitle: '',
      model: {
        id: '',
        title: '',
        remark: '',
        sort: 250,
        trace_batch_id:null,
        trace_tache_id:null,
        tacheValue:[],//环节内容
      },
      rules: {
        trace_batch_id: [{ required: true, message: '请选择批次', trigger: 'blur' }],
        trace_tache_id: [{ required: true, message: '请选择环节', trigger: 'blur' }]
      },
      selection: [],
      selectIds: '',
      selectTitle: '操作',
      selectDialog: false,
      selectType: '',
      is_disable: 0,
      batch_list:[],//批次
      tache_list:[],//环节
      inspection_list:[],//检测机构
      goods_list:[],//商品信息
    }
  },
  created() {
    this.height = screenHeight();
    this.list();
    this.getBatchs();
    this.getTaches();
    this.getInspection();
    this.getGoodsList();
  },
  methods: {
    //选择检测机构
    inspectionChange(index){
      let value = this.model.tacheValue[index];
      let inspection = this.inspection_list.find(item => item.value === Number(value.inspection_id));
      if(inspection){
        this.model.tacheValue[index].inspection_address = inspection.address;
        this.model.tacheValue[index].inspection_phone = inspection.phone;
      }else{

        this.model.tacheValue[index].inspection_address = undefined;
        this.model.tacheValue[index].inspection_phone = undefined;
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
    //选择环节
    tacheChange(){
      let that = this;
      that.tache_list.forEach((item,index)=>{
        if(item.value == that.model.trace_tache_id){
          that.model.tacheValue = item.attributes;
          //添加自检  检测报告字段
          for (var i=0;i<that.model.tacheValue.length;i++){
            that.model.tacheValue[i]['reports']=[];
            that.model.tacheValue[i]['inspection_id']=null;
            that.model.tacheValue[i]['inspection_address']=null;
            that.model.tacheValue[i]['inspection_phone']=null;
          }
        }
      });
    },
    //查询检测机构
    getInspection(){
      inspectionSelect({})
          .then((res) => {
            this.inspection_list = res.data;
          })
          .catch(() => {
          })
    },
    //查询批次
    getBatchs(){
      batchSelect({is_all:1})
          .then((res) => {
            this.batch_list = res.data;
          })
          .catch(() => {
          })
    },
    //查询环节
    getTaches(){
      tacheSelect({})
          .then((res) => {
            this.tache_list = res.data;
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
          //检查属性值
          let is_mark = false;
          let msg = "请完善该环节下的字段";
          for(let i=0;i<this.model.tacheValue.length;i++){
            //送检
            if(this.model.tacheValue[i].is_inspection_type==1 && !this.model.tacheValue[i].inspection_id){
              is_mark = true;
              msg = "请选择【"+this.model.tacheValue[i].label+"】检测机构";
              break;
            }
            if(this.model.tacheValue[i].is_inspection_type==2 && !this.model.tacheValue[i].value){
              is_mark = true;
              msg = "请填写【"+this.model.tacheValue[i].label+"】内容";
              break;
            }
            if(!this.model.tacheValue[i].value && this.model.tacheValue[i].is_inspection_type!=1 && this.model.tacheValue[i].is_inspection_type!=2){
              msg = "请填写【"+this.model.tacheValue[i].label+"】内容";
              is_mark = true;
              break;
            }
          }
          if(is_mark){
            ElMessage.error(msg);
            return false;
          }
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
<style>
.ellipsis-text {
  white-space: nowrap;       /* 不换行 */
  overflow: hidden;          /* 隐藏溢出内容 */
  text-overflow: ellipsis;   /* 超出内容显示省略号 */
  width: 100%;               /* 占据宽度 */
  display: inline-block;     /* 保证样式生效 */
}
.input_red input{
  color: red !important;
  -webkit-text-fill-color: red !important;;
}
.input_success input{
  color: #67c23a !important;;
  -webkit-text-fill-color: #67c23a !important;;
}
</style>
