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
          <div class="drawer-summary">
            <div class="drawer-summary__identity">
              <el-image class="order_icon" :src="orderImg" />
              <div class="text">
                <div class="title">订单编号</div>
                <div class="drawer-summary__no">{{ merData.order_no || '--' }}</div>
              </div>
            </div>
            <div class="drawer-summary__chips">
              <span class="summary-chip summary-chip--primary">{{ merData.status_title || '--' }}</span>
              <span class="summary-chip">订单金额 ¥{{ merData.total_price || 0 }}</span>
              <span class="summary-chip">{{ Number(merData.pay_status) === 1 ? '已支付' : '未支付' }}</span>
              <span class="summary-chip">支付时间 {{ merData.pay_time || '--' }}</span>
            </div>
          </div>
          <div class="summary-inline">
            <div class="summary-inline__item">
              <span class="summary-inline__label">订单承接</span>
              <span class="summary-inline__value">{{ deliverySummaryText }}</span>
            </div>
            <div class="summary-inline__item">
              <span class="summary-inline__label">支付复核</span>
              <span class="summary-inline__value">{{ paySummaryText }}</span>
            </div>
          </div>
          <div class="risk-inline">
            <span class="risk-inline__label">处理提示</span>
            <span class="risk-inline__text">{{ riskSummaryText }}</span>
          </div>
          <div class="order-guide">
            <div class="order-guide__header">
              <div>
                <div class="order-guide__title">这笔订单建议先这样看</div>
                <div class="order-guide__desc">先看支付是否成立，再看收货或自提承接，最后结合订单记录判断是不是需要继续处理。</div>
              </div>
              <span class="order-guide__badge">{{ orderFocusLabel }}</span>
            </div>
            <div class="order-guide__grid">
              <div v-for="item in orderGuideCards" :key="item.title" class="order-guide-card">
                <div class="order-guide-card__step">{{ item.step }}</div>
                <div class="order-guide-card__title">{{ item.title }}</div>
                <div class="order-guide-card__desc">{{ item.desc }}</div>
              </div>
            </div>
          </div>
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
  computed: {
    deliverySummaryText() {
      if (Number(this.merData.delivery_type) === 2) {
        return `用户自提，联系人 ${this.merData.self_name || '--'} / ${this.merData.self_phone || '--'}`
      }
      return `物流配送，收货人 ${this.merData.take_name || '--'} / ${this.merData.take_phone || '--'}`
    },
    paySummaryText() {
      const payStatus = Number(this.merData.pay_status) === 1 ? '已支付' : '未支付'
      return `${this.merData.pay_type_title || '未识别支付方式'}，${payStatus}，金额 ¥${this.merData.total_price || 0}`
    },
    riskSummaryText() {
      if (Number(this.merData.pay_type) === 2 && Number(this.merData.pay_status) !== 1) {
        return '当前订单为凭证支付且仍未支付，处理前请先核对支付凭证图片和审核状态。'
      }
      if (Number(this.merData.delivery_type) === 1 && Number(this.merData.status) > 1) {
        return '当前订单已进入物流流程，建议结合物流轨迹和订单记录一起核对。'
      }
      if (Number(this.merData.delivery_type) === 2) {
        return '当前订单为自提单，处理前请重点核对取件码、联系人和核销状态。'
      }
      return '建议结合商品信息、订单记录和当前状态一并复核后再处理。'
    },
    orderFocusLabel() {
      if (Number(this.merData.pay_status) !== 1) {
        return '先核对支付'
      }
      if (Number(this.merData.delivery_type) === 2) {
        return '先核对自提承接'
      }
      if (Number(this.merData.status) > 1) {
        return '先核对物流进度'
      }
      return '先核对基础信息'
    },
    orderGuideCards() {
      return [
        {
          step: '先看支付',
          title: '先确认这笔钱是不是已经收到了',
          desc: this.paySummaryText
        },
        {
          step: '再看承接',
          title: Number(this.merData.delivery_type) === 2 ? '自提联系人和核销状态要对上' : '收货信息和物流流转要对上',
          desc: this.deliverySummaryText
        },
        {
          step: '最后判断',
          title: '再结合订单记录决定要不要继续处理',
          desc: this.riskSummaryText
        }
      ]
    }
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
  padding: 18px 26px 14px;
}

.drawer-summary {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 14px;
}

.drawer-summary__identity {
  display: flex;
  align-items: center;
  min-width: 0;
}

.order_icon {
  width: 44px;
  height: 44px;
}

.text {
  min-width: 0;
  padding-left: 12px;
  font-size: 12px;
  color: #606266;
}

.title {
  font-size: 13px;
  font-weight: 600;
  color: #282828;
}

.drawer-summary__no {
  margin-top: 6px;
  font-size: 18px;
  font-weight: 700;
  color: #111827;
  line-height: 1.3;
  word-break: break-all;
}

.drawer-summary__chips {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 8px;
}

.summary-chip {
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  padding: 0 10px;
  border-radius: 999px;
  font-size: 12px;
  color: #475569;
  background: #f1f5f9;
}

.summary-chip--primary {
  color: #1d4ed8;
  background: #e8f0ff;
  font-weight: 600;
}

.summary-inline {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
  margin-top: 14px;
}

.summary-inline__item {
  padding: 12px 14px;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  background: #f8fafc;
}

.summary-inline__label {
  display: block;
  font-size: 12px;
  font-weight: 600;
  color: #475569;
}

.summary-inline__value {
  display: block;
  margin-top: 6px;
  font-size: 13px;
  line-height: 1.6;
  color: #1f2937;
}

.risk-inline {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 12px;
  padding: 12px 14px;
  border-radius: 12px;
  border: 1px solid #f3ddab;
  background: #fff9f0;
}

.risk-inline__label {
  font-size: 12px;
  font-weight: 600;
  color: #b45309;
}

.risk-inline__text {
  flex: 1;
  min-width: 0;
  font-size: 12px;
  line-height: 1.7;
  color: #7c5a10;
}

.order-guide {
  margin-top: 14px;
  padding: 14px 16px;
  border-radius: 14px;
  background: linear-gradient(135deg, #f8fbff 0%, #f5f7ff 100%);
  border: 1px solid #dbe7ff;
}

.order-guide__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.order-guide__title {
  font-size: 15px;
  font-weight: 600;
  color: #1f2937;
}

.order-guide__desc {
  margin-top: 4px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.order-guide__badge {
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  padding: 0 12px;
  border-radius: 999px;
  background: #e8f0ff;
  color: #1d4ed8;
  font-size: 12px;
  font-weight: 600;
  white-space: nowrap;
}

.order-guide__grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-top: 14px;
}

.order-guide-card {
  padding: 12px 14px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.9);
  border: 1px solid rgba(191, 219, 254, 0.95);
}

.order-guide-card__step {
  display: inline-flex;
  align-items: center;
  min-height: 22px;
  padding: 0 8px;
  border-radius: 999px;
  background: #eff6ff;
  color: #2563eb;
  font-size: 11px;
  font-weight: 600;
}

.order-guide-card__title {
  margin-top: 10px;
  font-size: 14px;
  font-weight: 600;
  color: #1f2937;
}

.order-guide-card__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #475569;
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
@media (max-width: 1200px) {
  .drawer-summary,
  .order-guide__header {
    flex-direction: column;
  }

  .drawer-summary__chips {
    justify-content: flex-start;
  }

  .summary-inline,
  .order-guide__grid {
    grid-template-columns: 1fr;
  }
}
</style>
