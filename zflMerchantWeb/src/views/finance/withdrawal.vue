<template>
  <div class="app-container">
    <el-card  class="app-main">
      <div class="withdrawal">
        <div class="left-sanjiao-title">
          提现中心
        </div>
        <div  class="mt-20">
          <div  class="pl-20 pt-16 pb-16 inline-block" style="background: rgb(242, 244, 247); border-radius: 4px; width: 200px;">
            <div  class="fz-14 color-666">我的余额</div>
            <div  class="mt-14 fz-20 color-333">{{params.balance}}</div>
          </div>
        </div>
        <div class="mt-20">
          <el-form ref="withdrawal_ref" :model="withdrawal_model" :rules="withdrawal_rules" label-width="80px">
            <el-form-item label="提现方式" prop="title" class="way">
              <div  class="flex f-aic" style="line-height: 1;">
                <div >
                  <div  class="unionpay-icon-container">
                    <el-image class="svg-icon" style="width: 21px;" :src="unionpay"/>
                    <span >银联转账</span>
                  </div>
                </div>
                <div  class="ml-20 mr-20">或</div>
                <div >
                  <div  class="unionpay-icon-container">
                    <el-image class="svg-icon" style="width: 21px;" :src="alipay"/>
                    <span >支付宝</span>
                  </div>
                </div>
              </div>
            </el-form-item>
            <el-form-item label="提现账号" class="account">
              <!----------------银行卡------------------------->
              <div  class="flex f-aic" v-if="params.bank">
                <div  class="mr-10">
                  <el-text  type="primary" size="large">
                  {{params.bank.card_no}}
                  </el-text>
                </div>
                <el-button type="primary" round size="small" @click="edit(1,params.bank)">修改</el-button>
              </div>
              <el-button type="primary" round size="small" @click="add(1)" v-else>绑定银行卡</el-button>
              <!----------------支付宝------------------------->
              <div  class="flex f-aic ml-50" v-if="params.alipay">
                <div  class="mr-10">
                  <el-text  type="primary" size="large">
                    {{params.alipay.alipay_account}}
                  </el-text>
                </div>
                <el-button type="primary" round size="small" @click="edit(2,params.alipay)">修改</el-button>
              </div>
              <el-button type="primary" round size="small" style="margin-left: 50px" @click="add(2)" v-else>绑定支付宝</el-button>
            </el-form-item>
            <el-form-item label="提现金额" prop="amount">
              <el-input-number :controls="false" style="width: 100px;" @change="amountChange()" v-model="withdrawal_model.amount" placeholder="金额" :min="params.mer_min_withdrawal_amount" :max="params.mer_max_withdrawal_amount" :precision="2" :step="1"/>
              <el-text type="info" class="tip" style="margin-left: 10px;">
                <el-icon><InfoFilled /></el-icon>
                {{commission_title}}预计到账时间2-3天
              </el-text>
            </el-form-item>
            <el-form-item>

              <el-button :loading="loading" type="primary" @click="withdrawal_submit" round>提交</el-button>
            </el-form-item>
          </el-form>
        </div>
      </div>
      <!-- 列表 -->
      <div class="left-sanjiao-title mt-20">
        提现记录
      </div>
      <el-table
        ref="table"
        v-loading="loading"
        :data="data"
        @sort-change="sort"
        @selection-change="select"
        class="mt-20"
      >
<!--        <el-table-column type="selection" width="42" title="全选/反选" />-->
        <el-table-column :prop="idkey" label="ID" width="80" sortable="custom" />
        <el-table-column  label="银行卡号" min-width="180">
          <template #default="scope">
            <el-popover placement="top" width="200" trigger="hover">
              <template #reference>
                <span style="cursor: pointer">{{scope.row.card_no}}</span>
              </template>
              <div><el-text>提现姓名：{{scope.row.name}}</el-text></div>
              <div><el-text>开户银行：{{scope.row.bank}}</el-text></div>
              <div><el-text>开户支行：{{scope.row.bank_branch}}</el-text></div>
            </el-popover>
          </template>
        </el-table-column>
        <el-table-column prop="alipay_account" label="支付宝账号" min-width="180" show-overflow-tooltip />
        <el-table-column prop="total_amount" label="提现金额" min-width="120" />
        <el-table-column prop="amount" label="到账金额" min-width="120" />
        <el-table-column prop="commission" label="手续费" min-width="120" />
        <el-table-column prop="auth_status" label="状态" min-width="80" show-overflow-tooltip >
          <template #default="scope">
            <span v-if="scope.row.auth_status == 0">待审核</span>
            <span v-else-if="scope.row.auth_status == 1">提现成功</span>
            <span v-else-if="scope.row.auth_status == 2">提现失败</span>
            <span v-else-if="scope.row.auth_status == 3">提现中</span>
            <div v-if="scope.row.auth_status == 2 && scope.row.auth_msg">
              <el-text type="danger">{{scope.row.auth_msg}}</el-text>
            </div>
            <div v-if="scope.row.auth_status == 1">
              <el-text type="success" v-if="scope.row.code=='Card'">银行卡</el-text>
              <el-text type="success" v-if="scope.row.code=='Alipay'">支付宝</el-text>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="create_time" label="添加时间" min-width="165" />
        <el-table-column prop="auth_time" label="审核时间" min-width="165" />
      </el-table>
      <pagination
          v-show="count > 0"
          v-model:total="count"
          v-model:page="query.page"
          v-model:limit="query.limit"
          @pagination="list"
      />
    </el-card>
    <!-- 添加修改 -->
    <el-dialog
        v-model="dialog"
        :title="dialogTitle"
        :close-on-click-modal="false"
        :close-on-press-escape="false"
        :before-close="cancel"
        top="10vh"
        width="40%"
    >
      <el-scrollbar class="mt5" native :max-height="height - 30">
        <el-form ref="ref" :model="model" :rules="rules" label-width="100px">
          <el-form-item label="姓名" prop="name" v-if="model.type==1">
            <el-input v-model="model.name" placeholder="请输入姓名" clearable />
          </el-form-item>
          <el-form-item label="银行卡号" prop="card_no" v-if="model.type==1">
            <el-input v-model="model.card_no" placeholder="请输入银行卡号" clearable />
          </el-form-item>
          <el-form-item label="开户银行" prop="bank" v-if="model.type==1">
            <el-input v-model="model.bank" placeholder="请输入开户银行" clearable />
          </el-form-item>
          <el-form-item label="开户支行" prop="bank_branch" v-if="model.type==1">
            <el-input v-model="model.bank_branch" placeholder="请输入开户支行" clearable />
          </el-form-item>
          <el-form-item label="支付宝账号" prop="alipay_account" v-if="model.type==2">
            <el-input v-model="model.alipay_account" placeholder="请输入支付宝账号" clearable />
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
import { getPageLimit } from '@/utils/settings'
import { list, add,getParams as selectParams } from '@/api/finance/withdrawal.js'
import {add as acadd, edit as acedit} from '@/api/finance/account.js'
import {shortcuts} from "@/utils/getDate.js";
import alipay from '@/assets/images/alipay_icon.png';
import unionpay from '@/assets/images/unionpay.png';
import {InfoFilled} from "@element-plus/icons-vue";
export default {
  name: 'withdrawal',
  components: {InfoFilled, Pagination },
  data() {
    return {
      name: '提现申请',
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
        is_disable:undefined
      },
      data: [],
      count: 0,
      dialog: false,
      dialogTitle: '',
      model: {
        promotion_user_id:undefined,//推广员id
        source:2,
        type:1,//类型：1、银行卡 2、支付宝账户
        name:undefined,//姓名
        bank:undefined,//开户银行
        bank_branch:undefined,//开户支行
        card_no:undefined,//银行卡号
        alipay_account:undefined,//支付宝账号
      },
      rules: {
        name: [{ required: true, message: '请输入姓名', trigger: 'blur' }],
        bank: [{ required: true, message: '请输入开户银行', trigger: 'blur' }],
        bank_branch: [{ required: true, message: '请输入开户支行', trigger: 'blur' }],
        card_no: [{ required: true, message: '请输入银行卡号', trigger: 'blur' }],
        alipay_account: [{ required: true, message: '请输入支付宝账号', trigger: 'blur' }],
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
      shortcuts:shortcuts(),
      withdrawal_model: {
        amount:undefined,
      },
      withdrawal_rules: {
        amount: [{ required: true, message: '请输入提现金额', trigger: 'blur' }],
      },
      alipay:alipay,
      unionpay:unionpay,
      params:{},
      commission_title:'',
    }
  },
  created() {
    this.height = screenHeight()
    this.list()
    this.getParams()
  },
  methods: {
    //提现金额
    amountChange(){
      if(this.params.mer_withdrawal_commission>0 && this.withdrawal_model.amount>0){
        let commission = parseFloat(this.withdrawal_model.amount*this.params.mer_withdrawal_commission);
        commission = commission.toFixed(2);
        if(commission>0){
          this.commission_title = "手续费："+commission+"元，";
        }
      }else{
        this.commission_title ="";
      }
    },
    //提现
    withdrawal_submit(){
      let that = this;
      if(!that.params.bank){
        ElMessage.error("请添加银行卡");
        return false;
      }
      if(!that.params.alipay){
        ElMessage.error("请添加支付宝");
        return false;
      }
      that.$refs['withdrawal_ref'].validate((valid) => {
        if (valid) {
          if(that.withdrawal_model.amount>parseFloat(that.params.balance)){
            ElMessage.error("余额不足："+that.withdrawal_model.amount);
          }else{
            ElMessageBox.confirm('确定提现吗？', '温馨提示', {
              confirmButtonText: '确定',
              cancelButtonText: '取消',
              type: 'warning'
            })
            .then(() => {
              that.loading = true;
              add(that.withdrawal_model)
                  .then((res) => {
                    that.list();
                    that.loading = false;
                    that.getParams();
                    that.withdrawal_model.amount = undefined;
                    that.commission_title = "";
                    ElMessage.success(res.msg);
                  })
                  .catch(() => {
                    that.loading = false
                  })
            })
            .catch(() => {})
          }
        }
      });
    },
    //查询相应参数
    getParams(){
      selectParams({})
          .then((res) => {
            this.params = res.data;
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
    add(type) {
      this.dialog = true
      this.dialogTitle = type==1?'收款银行卡':'收款支付宝';
      this.reset();
      this.model.type = type;
    },
    edit(type,row) {
      this.dialog = true
      this.dialogTitle = type==1?'收款银行卡':'收款支付宝';
      this.reset(Object.assign({}, row))
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
            acedit(this.model)
              .then((res) => {
                this.dialog = false
                this.loading = false
                this.getParams()
                ElMessage.success(res.msg)
              })
              .catch(() => {
                this.loading = false
              })
          } else {
            acadd(this.model)
              .then((res) => {
                this.dialog = false
                this.loading = false
                this.getParams()
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
  }
}
</script>
<style>
.app-main .left-sanjiao-title {
  position: relative;
  padding-left: 24px;
  height: 16px;
  line-height: 16px;
  color: #000;
  font-size: 15px;
}
.app-main .left-sanjiao-title:after {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  width: 0;
  height: 0;
  border-bottom: 16px solid #1678ff;
  border-left: 16px solid transparent;
}
.app-main .mt-20 {
  margin-top: 20px !important;
}
.withdrawal .pl-20 {
  padding-left: 20px !important;
}
.withdrawal .pb-16 {
  padding-bottom: 16px !important;
}

.withdrawal .pt-16 {
  padding-top: 16px !important;
}
.withdrawal .inline-block {
  display: inline-block;
}
.withdrawal .color-666 {
  color: #666;
}
.withdrawal .fz-14 {
  font-size: 14px !important;
}
.withdrawal .fz-20 {
  font-size: 20px !important;
}

.withdrawal .mt-14 {
  margin-top: 14px !important;
}
.way .f-aic {
  align-items: center;
}
.way .flex {
  display: flex;
}
.way .unionpay-icon-container{
  position: relative;
  display: flex;
  align-items: center;
  padding: 0 14px 0 10px;
  height: 36px;
  border: 1px solid #1678ff;
  border-radius: 6px;
  font-size: 14px;
}
.way .unionpay-icon-container:after {
  position: absolute;
  right: 0;
  bottom: 0;
  content: "";
  display: block;
  width: 0;
  height: 0;
  border-bottom: 14px solid #1678ff;
  border-left: 14px solid transparent;
}
.way .unionpay-icon-container .svg-icon{
  font-size: 21px;
  margin-right: 6px;
}
.way .svg-icon{
  width: 1em;
  height: 1em;
  vertical-align: middle;
  fill: currentColor;
  overflow: hidden;
}
.way .ml-20 {
  margin-left: 20px !important;
}
.way .mr-20 {
  margin-right: 20px !important;
}
.account .color-link{
  color: #1678ff;
}
.account .fz-16{
  font-size: 16px !important;
}
.account .flex{
  display: flex;
}
.account .f-aic{
  align-items: center;
}
.account .mr-10 {
  margin-right: 10px !important;
}
.account .ml-50 {
  margin-left: 50px !important;
}
</style>