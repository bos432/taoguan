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
                  <el-option value="name" label="名称" />
                  <el-option value="desc" label="描述" />
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
        <el-form-item v-if="selectType === 'datetime'" label="时间范围">
          <el-date-picker
            v-model="start_time"
            type="datetime"
            value-format="YYYY-MM-DD HH:mm:ss"
            default-time="00:00:00"
            placeholder="开始时间"
          />
          <span>至</span>
          <el-date-picker
            v-model="end_time"
            type="datetime"
            value-format="YYYY-MM-DD HH:mm:ss"
            default-time="23:59:59"
            placeholder="结束时间"
          />
        </el-form-item>
        <el-form-item v-else-if="selectType === 'disable'" label="是否禁用">
          <el-switch v-model="is_disable" :active-value="1" :inactive-value="0" />
        </el-form-item>
        <el-form-item v-else-if="selectType === 'dele'">
          <span class="c-red">确定要删除选中的{{ name }}吗？</span>
        </el-form-item>
        <template v-else-if="selectType === 'pay_auth'">
          <el-form-item label="支付凭证">
            <FileUploads
                v-model="payAuthInfo.pay_voucher_imgs"
                upload-btn="上传图片"
                file-type="image"
                file-tip="图片文件"
                fileSource="list"
            />
          </el-form-item>
          <el-form-item label="支付状态">
            <el-radio-group v-model="payAuthInfo.pay_status">
              <el-radio :value="1">已支付</el-radio>
              <el-radio :value="0">未支付</el-radio>
            </el-radio-group>
          </el-form-item>
          <el-form-item label="实际支付金额">
            <el-input-number v-model="payAuthInfo.pay_price" :precision="2" :step="0.1" :min="0.01" />
          </el-form-item>
          <el-form-item label="支付失败原因" v-if="payAuthInfo.pay_status!=1">
            <el-input v-model="payAuthInfo.pay_auth_msg" placeholder="请输入支付失败原因" clearable />
          </el-form-item>
        </template>
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
<!--          <el-button title="是否禁用" @click="selectOpen('disable')">禁用</el-button>-->
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
          <el-table-column prop="order_no" label="订单编号" min-width="155" show-overflow-tooltip />
<!--          <el-table-column prop="member_id" label="用户信息" min-width="140" show-overflow-tooltip>-->
<!--            <template #default="scope">-->
<!--              {{scope.row.member_title}}/{{scope.row.member_id}}-->
<!--            </template>-->
<!--          </el-table-column>-->
          <el-table-column prop="delivery_type" label="发货类型" min-width="100" show-overflow-tooltip>
            <template #default="scope">
              <el-tag type="primary" v-if="scope.row.delivery_type==1">物流/快递</el-tag>
              <el-tag type="success" v-if="scope.row.delivery_type==2">用户自提</el-tag>
            </template>
          </el-table-column>
          <el-table-column label="收货人/自提人" min-width="120" show-overflow-tooltip>
            <template #default="scope">
              <span v-if="scope.row.delivery_type==1">{{scope.row.take_name}}</span>
              <span v-if="scope.row.delivery_type==2">{{scope.row.self_name}}</span>
            </template>
          </el-table-column>
          <el-table-column label="商品信息" min-width="180">
            <template #default="scope">
              <template v-for="(item,index) in scope.row.detaileds">
                <div class="avatar-text-container" v-if="index==0">
                  <FileImage fileSource="list" :file-url="item.goods.image.file_url" lazy />
                  <span>{{item.goods.title }}</span>
                  <span>¥{{item.price}}x{{item.quantity}}</span>
                </div>
              </template>
            </template>
          </el-table-column>
          <el-table-column prop="pay_price" label="实际支付" min-width="80" show-overflow-tooltip />
          <el-table-column prop="pay_type_title" label="支付方式" min-width="80" show-overflow-tooltip />
          <el-table-column prop="pay_status" label="支付状态" min-width="80">
            <template #default="scope">
              <span v-if="scope.row.pay_status==1">已支付</span>
              <span v-else>未支付</span>
            </template>
          </el-table-column>
          <el-table-column prop="status_title" label="订单状态" min-width="80" show-overflow-tooltip />
          <el-table-column prop="create_time" label="下单时间" min-width="165" sortable="custom" />
          <el-table-column label="操作" width="130" fixed="right">
            <template #default="scope">
              <el-link type="primary" class="mr-1" :underline="false" @click="onDetails(scope.row.id)">
                详情
              </el-link>
              <el-link v-if="scope.row.pay_type==2 && scope.row.pay_status!=1" type="primary" class="mr-1" :underline="false" @click="selectOpen('pay_auth', [scope.row])">
                支付审核
              </el-link>
<!--              <el-link v-if="scope.row.status==1" type="primary" class="mr-1" :underline="false" @click="edit(scope.row)">-->
<!--                发货-->
<!--              </el-link>-->
<!--              <el-link type="primary" :underline="false" @click="selectOpen('dele', [scope.row])">-->
<!--                删除-->
<!--              </el-link>-->
            </template>
          </el-table-column>
        </el-table>
    </el-card>

    <!--详情-->
    <detail
        ref="detail"
        :merchant_list="[]"
        :store_platform_list="[]"
        :store_type_list="[]"
        :store_flag_list="[]"
        :regiong_list="[]"
        @closeDrawer="closeDrawer"
        @changeDrawer="changeDrawer"
        @getList="list"
        :drawer="drawer"
    ></detail>
  </div>
</template>

<script>
import screenHeight from '@/utils/screen-height'
import Pagination from '@/components/Pagination/index.vue'
import { arrayColumn } from '@/utils/index'
import { getPageLimit } from '@/utils/settings'
import { list, info, add, edit, dele, disable,orderPayAuth } from '@/api/order/list'
import detail from '@/views/order/handle/details.vue'
export default {
  name: 'orderList',
  components: { Pagination, detail },
  data() {
    return {
      name: '订单',
      height: 680,
      loading: false,
      idkey: 'id',
      exps: [{ exp: 'like', name: '包含' }],
      query: {
        page: 1,
        limit: getPageLimit(),
        search_field: 'name',
        search_exp: 'like',
        date_field: 'create_time',
        is_disable:undefined,
      },
      data: [],
      count: 0,
      dialog: false,
      dialogTitle: '',
      model: {
        link_id: '',
        unique: '',
        image_id: 0,
        image_url: '',
        name: '',
        name_color: '',
        url: '',
        desc: '',
        start_time: '',
        end_time: '2099-12-31 23:59:59',
        underline: 0,
        remark: '',
        sort: 250
      },
      rules: {
        name: [{ required: true, message: '请输入名称', trigger: 'blur' }],
        start_time: [{ required: true, message: '请输入开始时间', trigger: 'blur' }],
        end_time: [{ required: true, message: '请输入结束时间', trigger: 'blur' }]
      },
      types: [],
      selection: [],
      selectIds: '',
      selectTitle: '操作',
      selectDialog: false,
      selectType: '',
      start_time: '',
      end_time: '',
      is_disable: 0,
      drawer:false,
      //支付审核
      payAuthInfo:{
        pay_price:0,//实际支付金额
        pay_voucher_imgs:[],//支付凭证图片
        pay_status:1,//支付状态：1、已支付 0、未支付
        pay_auth_msg:'',//支付审核备注
      },
    }
  },
  created() {
    this.height = screenHeight()
    this.list()
  },
  methods: {
    // 详情
    onDetails(id) {
      this.$refs.detail.isEdit = false;
      this.$refs.detail.getInfo(id);
      this.drawer = true;
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
        if (selectType === 'dele') {
          this.selectTitle = this.name + '删除'
        } else if (selectType === 'disable') {
          this.selectTitle = this.name + '是否禁用'
        }else if(selectType === 'pay_auth') {
          let obj = selectRow[0];
          this.payAuthInfo.pay_price =obj.total_price;
          this.payAuthInfo.pay_voucher_imgs = obj.pay_voucher_imgs;
          this.payAuthInfo.pay_status = obj.pay_status;
          this.payAuthInfo.pay_auth_msg = obj.pay_auth_msg;
          this.selectTitle = this.name + '凭证支付审核'
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
        if (selectType === 'dele') {
          this.dele(this.selection)
        } else if (selectType === 'disable') {
          this.disable(this.selection, true)
        }else if (selectType === 'pay_auth') {
          if(this.payAuthInfo.pay_status==1 && this.payAuthInfo.pay_price<=0){
            ElMessage.error('请输入实际支付金额');
            return;
          }
          if(this.payAuthInfo.pay_status==2 && !this.payAuthInfo.pay_auth_msg){
            ElMessage.error('请输入支付失败原因');
            return;
          }
          this.payAuth(this.selection, true);
        }
        this.selectDialog = false
      }
    },
    //支付审核
    payAuth(row, select = false) {
      if (!row.length) {
        this.selectAlert()
      } else {
        this.payAuthInfo.ids=this.selectGetIds(row);
        orderPayAuth(this.payAuthInfo)
            .then((res) => {
              this.selectDialog = false
              this.list()
              ElMessage.success(res.msg)
            })
            .catch(() => {
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
    }
  }
}
</script>
<style>
.avatar-text-container {
  display: flex;
  align-items: center; /* 垂直居中对齐 */
  overflow: hidden; /* 隐藏溢出内容 */
  max-width: 100%; /* 防止超出容器 */
}

.avatar-text-container .el-avatar {
  flex: 0 0 auto; /* 固定宽度，不允许缩放 */
  margin-right: 5px; /* 图标与文本之间的间距 */
}

.platform-title {
  white-space: nowrap; /* 防止文字换行 */
  overflow: hidden; /* 隐藏超出范围的文本 */
  text-overflow: ellipsis; /* 显示省略号 */
  flex: 1; /* 文本容器占用剩余空间 */
}
</style>