<template>
  <div>
    <el-drawer
      v-model="localDrawer"
      :with-header="false"
      size="50%"
      :visible.sync="localDrawer"
      :direction="direction"
      :before-close="handleClose"
    >
      <div v-loading="loading" v-if="localDrawer">
        <div v-if="!isAdd" class="head">
          <div class="full">
            <el-image class="order_icon" :src="orderImg" />
            <div class="text">
              <div class="title">
                <span class="bold">订单编号</span>
              </div>
              <div>
                <span class="mr20 bold">{{merData.order_no}}</span>
              </div>
            </div>
            <div>
<!--              <el-button-->
<!--                v-if="isEdit"-->
<!--                @click="cancelEdit"-->
<!--                >取消</el-button-->
<!--              >-->
            </div>
          </div>
          <ul class="list">
            <li class="item">
              <div class="title">订单状态</div>
              <div>
                {{ merData.status_title }}
                </div>
            </li>
            <li class="item">
              <div class="title">订单金额</div>
              <div>¥{{ merData.total_price}}</div>
            </li>
            <li class="item">
              <div class="title">支付状态</div>
              <div>
                <span v-if="merData.pay_status==1">已支付</span>
                <span v-else>未支付</span>
              </div>
            </li>
            <li class="item">
              <div class="title">支付时间</div>
              <div>{{ merData.pay_time }}</div>
            </li>
          </ul>
        </div>
        <div v-else class="head">

        </div>
        <!--详情-->
        <mer-info ref="merInfo" :merData="merData" v-if="!isEdit && !isAdd"></mer-info>
      </div>
      <div v-if="isAdd" class="footer">
        <el-button size="small" @click="handleClose">取消</el-button>
        <el-button type="primary" size="small" @click="submitInfo">提交</el-button>
      </div>
    </el-drawer>

  </div>
</template>

<script >
import { info } from '@/api/order/list.js';
import merInfo from './info.vue';
import merImg from '@/assets/images/order.png';
import {More} from '@element-plus/icons-vue';
export default {
  name: 'merchantDetails',
  setup() {
    return {
      More
    }
  },
  props: {
    drawer: {
      type: Boolean,
      default: false,
    },
    merchant_list: {
      type: Array,
      default: [],
    },
    store_platform_list: {
      type: Array,
      default: [],
    },
    store_type_list: {
      type: Array,
      default: [],
    },
    store_flag_list: {
      type: Array,
      default: [],
    },
    regiong_list: {
      type: Array,
      default: [],
    }
  },
  components: {merInfo },
  data() {
    return {
      loading: true,
      merId: '',
      isEdit: false,
      isAdd: false,
      direction: 'rtl',
      activeName: 'detail',
      merData: {},
      orderImg: merImg,
      localDrawer:this.drawer
    };
  },
  filters: {
  },
  methods: {
    handleClose() {
      if(this.isEdit || this.isAdd) {
        this.$refs.editForm.resetData();
        this.$refs.editForm.activeName = 'detail';
      }else{
        this.$refs.merInfo.activeName = 'detail';
      }
      this.$emit('closeDrawer');
      this.localDrawer=false;
    },
    getInfo(id) {
      this.isAdd = false;
      info({id:id}).then((res) => {
          this.loading = false;
          this.merData = res.data;
          this.localDrawer = true;
        })
        .catch((res) => {
          ElMessage.error(res.message)
        });
    },
    initData(){
      this.merData = {};
      this.isEdit = false;
      this.isAdd = true;
      this.loading = false;
      this.localDrawer = true;
    },
    merEdit(){
      this.isEdit = true;
      this.$nextTick(()=>{
        this.getInfo(this.merId);
      })
    },
    cancelEdit() {
      this.isEdit = false
    },
    // 编辑成功回调
    editSuccess(){
      if(this.isAdd){
        this.handleClose();
      }else{
        this.isEdit = false;
      }
      this.$emit('getList')
    },
    // 详情
    onDetails(id) {
      this.merId = id;
      this.$refs.detail.isEdit = false;
      this.$refs.detail.getInfo(id);
      this.drawer = true;
    },

    saveInfo(){
      this.$refs.editForm.onSubmit(this.merId).then(res => {
        if (res) {
          this.getInfo(this.merId);
        }
      }).catch(err => {
        console.log(err);
      });
    },
    submitInfo(){
      this.$refs.editForm.handleCreate();
    }
  },
};
</script>
<style lang="scss" scoped>
.head {
  padding: 20px 35px;
  .full {
    display: flex;
    align-items: center;
    .order_icon {
      width: 60px;
      height: 60px;
    }
    .text {
      align-self: center;
      flex: 1;
      min-width: 0;
      padding-left: 12px;
      font-size: 13px;
      color: #606266;
      .title {
        margin-bottom: 10px;
        font-weight: 500;
        font-size: 16px;
        line-height: 16px;
        color: #282828;
      }
    }
  }
  .bold{
    font-weight: bold;
  }
  .list {
    display: flex;
    margin-top: 20px;
    overflow: hidden;
    list-style: none;
    padding: 0;
    .item {
      flex: none;
      width: 150px;
      font-size: 14px;
      line-height: 14px;
      color: rgba(0, 0, 0, 0.85);
      .title {
        margin-bottom: 12px;
        font-size: 13px;
        line-height: 13px;
        color: #666666;
      }
    }
  }
}
.el-tabs--border-card {
  box-shadow: none;
  border-bottom: none;
}
.section {
  padding: 20px 0 8px;
  border-bottom: 1px dashed #eeeeee;
  .title {
    padding-left: 10px;
    border-left: 3px solid var(--prev-color-primary);
    font-size: 15px;
    line-height: 15px;
    color: #303133;
  }
  .list {
    display: flex;
    flex-wrap: wrap;
    list-style: none;
    padding: 0;
  }
  .item {
    flex: 0 0 calc(100% / 2);
    display: flex;
    margin-top: 16px;
    font-size: 13px;
    color: #606266;

    &:nth-child(2n + 1) {
      padding-right: 20px;
      padding-left: 20px;
    }
    &:nth-child(2n) {
     padding-right: 20px;
    }
  }
  .value {
    flex: 1;
    image {
      display: inline-block;
      width: 40px;
      height: 40px;
      margin: 0 12px 12px 0;
      vertical-align: middle;
    }
  }
}
.tab {
  display: flex;
  align-items: center;
  .el-image {
    width: 36px;
    height: 36px;
    margin-right: 10px;
  }
}
.el-drawer__body {
  overflow: auto;
}
.gary {
  color: #aaa;
}
.footer{
  width: 100%;
  text-align: center;
  position: absolute;
  bottom: 17px;
  padding-top: 17px;
  border-top: 1px dashed #eeeeee;
}
.ml10{
  margin-left: 10px;
}
</style>
