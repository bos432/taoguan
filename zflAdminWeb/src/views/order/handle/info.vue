<template>
  <div>
    <div class="detail-guide">
      <div class="detail-guide__header">
        <div>
          <div class="detail-guide__title">订单详情页怎么用更顺手</div>
          <div class="detail-guide__desc">基本信息里先判断钱、货、人是不是对得上；再切商品信息和订单记录，确认这单是正常推进还是需要人工介入。</div>
        </div>
        <span class="detail-guide__badge">{{ detailFocusLabel }}</span>
      </div>
      <div class="detail-guide__grid">
        <div v-for="item in detailGuideCards" :key="item.title" class="detail-guide-card">
          <div class="detail-guide-card__title">{{ item.title }}</div>
          <div class="detail-guide-card__desc">{{ item.desc }}</div>
        </div>
      </div>
    </div>
    <el-tabs type="border-card" v-model="activeName" class="order-detail-tabs">
      <el-tab-pane label="基本信息" name="detail">
        <div class="section">
          <div class="overview-strip">
            <span class="overview-strip__item">订单状态：{{ merData.status_title || '--' }}</span>
            <span class="overview-strip__item">支付方式：{{ merData.pay_type_title || '--' }}</span>
            <span class="overview-strip__item">发货类型：{{ deliveryTypeText }}</span>
            <span class="overview-strip__item">商品数量：{{ merData.total_num || 0 }}</span>
          </div>
          <div class="risk-panel">
            <div class="risk-panel__title">处理提示</div>
            <div class="risk-panel__desc">{{ riskHintText }}</div>
          </div>
          <div class="section-caption">用户信息</div>
          <ul class="list list--compact">
            <li class="item">
              <div>用户昵称：</div>
              <div class="value">
                {{merData.member.nickname}}
              </div>
            </li>
            <li class="item">
              <div>用户ID：</div>
              <div class="value">
                {{merData.member.member_id}}
              </div>
            </li>
            <li class="item">
              <div>绑定电话：</div>
              <div class="value">
                {{merData.member.phone}}
              </div>
            </li>
          </ul>
          <div class="section-caption">收货信息</div>
          <ul class="list list--compact">
            <li class="item">
              <div>发货类型：</div>
              <div class="value">
                <el-tag type="primary" v-if="merData.delivery_type==1">物流/快递</el-tag>
                <el-tag type="success" v-if="merData.delivery_type==2">用户自提</el-tag>
              </div>
            </li>
            <li class="item" v-if="merData.delivery_type==1">
              <div>收货人：</div>
              <div class="value">
                {{merData.take_name}}
              </div>
            </li>
            <li class="item" v-if="merData.delivery_type==1">
              <div>联系电话：</div>
              <div class="value">
                {{merData.take_phone}}
              </div>
            </li>
            <li class="item" v-if="merData.delivery_type==1">
              <div>收货地区：</div>
              <div class="value">
                {{merData.take_region}}
              </div>
            </li>
            <li class="item" v-if="merData.delivery_type==1">
              <div>详细地址：</div>
              <div class="value">
                {{merData.take_address}}
              </div>
            </li>
            <li class="item" v-if="merData.delivery_type==2">
              <div>自提联系人：</div>
              <div class="value">
                {{merData.self_name}}
              </div>
            </li>
            <li class="item" v-if="merData.delivery_type==2">
              <div>自提预留手机号：</div>
              <div class="value">
                {{merData.self_phone}}
              </div>
            </li>
          </ul>
          <div class="section-caption">订单信息</div>
          <ul class="list list--compact">
            <li class="item">
              <div>创建时间：</div>
              <div class="value">
                {{merData.create_time}}
              </div>
            </li>
            <li class="item">
              <div>商品总数：</div>
              <div class="value">
                {{merData.total_num}}
              </div>
            </li>
            <li class="item">
              <div>支付方式：</div>
              <div class="value">
                {{merData.pay_type_title}}
              </div>
            </li>
            <li class="item">
              <div>订单金额：</div>
              <div class="value">
                ¥{{merData.total_price}}
              </div>
            </li>
            <li class="item">
              <div>支付凭证：</div>
              <div class="value">
                <FileUploads
                    v-model="merData.pay_voucher_imgs"
                    upload-btn="上传图片"
                    file-type="image"
                    file-tip="图片文件"
                    fileSource="list"
                />
              </div>
            </li>

          </ul>
          <template v-if="merData.status>1 && merData.delivery_type==1">
            <div class="section-caption">物流信息</div>
            <ul class="list list--compact">
              <li class="item">
                <div>发货时间：</div>
                <div class="value">
                  {{merData.delivery_time}}
                </div>
              </li>
              <li class="item">
                <div>快递公司：</div>
                <div class="value">
                  {{merData.delivery_name}}
                </div>
              </li>
              <li class="item">
                <div>快递单号：</div>
                <div class="value">
                  {{merData.kuaidi_order_no}}
                </div>
              </li>
              <li class="item">
                <el-button type="primary" @click="getLogistics()">查看物流</el-button>
              </li>
            </ul>
          </template>
        </div>
      </el-tab-pane>
      <el-tab-pane label="商品信息" name="account">
        <el-table :data="merData.detaileds" size="small" class="detail-table" style="width: 100%">
          <el-table-column label="缩略图" min-width="70">
            <template #default="scope">
              <div class="avatar-text-container">
                <FileImage fileSource="list" :file-url="scope.row.goods.image.file_url" />
              </div>
            </template>
          </el-table-column>
          <el-table-column label="商品名称" min-width="180"  show-overflow-tooltip>
            <template #default="scope">
              {{scope.row.goods.title}}
            </template>
          </el-table-column>
          <el-table-column label="商品规格" min-width="80">
            <template #default="scope">
              {{scope.row.goods.spec}}
            </template>
          </el-table-column>
          <el-table-column label="商品单价" min-width="80">
            <template #default="scope">
              {{scope.row.price}}
            </template>
          </el-table-column>
          <el-table-column label="购买数量" min-width="80">
            <template #default="scope">
              {{scope.row.quantity}}
            </template>
          </el-table-column>
          <el-table-column label="商品总价" min-width="80">
            <template #default="scope">
              {{scope.row.total}}
            </template>
          </el-table-column>
        </el-table>
      </el-tab-pane>
      <el-tab-pane label="订单记录" name="operate">
        <el-table :data="merData.logs" size="small" class="detail-table" style="width: 100%">
          <el-table-column label="订单编号" min-width="150"  show-overflow-tooltip>
            <template #default="scope">
              {{merData.order_no}}
            </template>
          </el-table-column>
          <el-table-column label="操作记录" min-width="180">
            <template #default="scope">
              {{scope.row.title}}
            </template>
          </el-table-column>
          <el-table-column label="操作人ID" prop="create_uid" min-width="80"/>
          <el-table-column label="操作角色" prop="role_type_title" min-width="100"/>
          <el-table-column label="操作时间" prop="create_time" min-width="160"/>
        </el-table>
      </el-tab-pane>
    </el-tabs>
    <el-dialog
        v-model="logisticsDialog"
        title="物流信息"
        :close-on-click-modal="false"
        :close-on-press-escape="false"
        top="20vh"
        width="500px"
    >
      <div class="logistics acea-row row-top lrflex">
        <div class="logistics_img">
          <img :src="expressiImg" />
        </div>
        <div class="logistics_cent">
          <span>物流公司：{{ merData.delivery_name }}</span>
          <span>物流单号：{{ merData.kuaidi_order_no }}</span>
        </div>
      </div>
      <div class="acea-row row-column-around trees-coadd">
        <div class="scollhide">
          <el-timeline>
            <el-timeline-item v-for="(item, i) in logistics_list" :key="i">
              <p class="time" v-text="item.time"></p>
              <p class="content" v-text="item.context"></p>
            </el-timeline-item>
          </el-timeline>
        </div>
      </div>
    </el-dialog>
  </div>   
</template>
<script>
import expressi from '@/assets/images/expressi.jpg';
import {logistics} from '@/api/order/list.js';
export default {
  props: {
    merData: {
      type: Object,
      default: {},
    },
  },
  data() {
    return {
      loading: true,
      merId: '',
      direction: 'rtl',
      activeName: 'detail',
      timeVal: [],
      tableDataLog: {
        data: [],
        total: 0
      },
      tableFromLog: {
        user_type: '',
        date: [],
        page: 1,
        limit: 10
      },
      expressiImg: expressi,
      logisticsDialog: false,
      logistics_list:[]
    };
  },
  computed: {
    deliveryTypeText() {
      if (Number(this.merData.delivery_type) === 2) return '用户自提'
      if (Number(this.merData.delivery_type) === 1) return '物流/快递'
      return '--'
    },
    riskHintText() {
      if (Number(this.merData.pay_type) === 2 && Number(this.merData.pay_status) !== 1) {
        return '当前订单为凭证支付且仍未支付，建议先核对支付凭证图片、实际支付金额和审核状态。'
      }
      if (Number(this.merData.delivery_type) === 2) {
        return '当前订单为用户自提，请重点核对自提联系人、预留手机号和取件码承接状态。'
      }
      if (Number(this.merData.status) > 1 && Number(this.merData.delivery_type) === 1) {
        return '当前订单已进入物流流程，建议结合物流信息、商品明细和订单记录同步核对。'
      }
      return '建议结合用户信息、收货信息、商品明细和订单记录一并复核当前订单。'
    },
    detailFocusLabel() {
      if (Number(this.merData.pay_status) !== 1) {
        return '先看支付凭证'
      }
      if (Number(this.merData.delivery_type) === 2) {
        return '先看自提承接'
      }
      if ((this.merData.logs || []).length > 0) {
        return '先看订单记录'
      }
      return '先看基本信息'
    },
    detailGuideCards() {
      return [
        {
          title: '先在基本信息里判断这单是不是成立',
          desc: `${this.merData.status_title || '状态未识别'} / ${this.merData.pay_type_title || '支付方式未识别'} / ${this.deliveryTypeText}`
        },
        {
          title: '再看商品信息，确认商品和金额有没有对上',
          desc: `当前共 ${this.merData.total_num || 0} 件商品，订单金额 ¥${this.merData.total_price || 0}`
        },
        {
          title: '最后看订单记录，确认有没有卡在某一步',
          desc: (this.merData.logs || []).length ? `当前已有 ${(this.merData.logs || []).length} 条订单记录，可顺着时间线复核。` : '当前暂无订单记录，若状态异常建议回列表或业务链路继续核查。'
        }
      ]
    }
  },
  filters: {
  },
  methods: {
    //查看物流
    getLogistics(){
      logistics({id:this.merData.id}).then((res) => {
        this.logistics_list = res.data;
        this.logisticsDialog=true;
      })
      .catch((res) => {
        ElMessage.error(res.message)
      });
    },
  },
};
</script>
<style lang="scss" scoped>
.el-tabs--border-card {
  box-shadow: none;
  border-bottom: none;
}
.order-detail-tabs :deep(.el-tabs__header) {
  margin-bottom: 8px;
}
.order-detail-tabs :deep(.el-tabs__item) {
  height: 34px;
  line-height: 34px;
  font-size: 13px;
}

.detail-guide {
  margin-bottom: 12px;
  padding: 14px 16px;
  border: 1px solid #dbe7ff;
  border-radius: 14px;
  background: linear-gradient(135deg, #f9fbff 0%, #f7f9ff 100%);
}

.detail-guide__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.detail-guide__title {
  font-size: 15px;
  font-weight: 600;
  color: #1f2937;
}

.detail-guide__desc {
  margin-top: 4px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.detail-guide__badge {
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

.detail-guide__grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-top: 14px;
}

.detail-guide-card {
  padding: 12px 14px;
  border-radius: 12px;
  border: 1px solid rgba(191, 219, 254, 0.95);
  background: rgba(255, 255, 255, 0.92);
}

.detail-guide-card__title {
  font-size: 13px;
  font-weight: 600;
  color: #1f2937;
}

.detail-guide-card__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #475569;
}
.overview-strip {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 12px;
}
.overview-strip__item {
  display: inline-flex;
  align-items: center;
  min-height: 24px;
  padding: 0 8px;
  border-radius: 999px;
  background: #f5f7fb;
  color: #4a5670;
  font-size: 12px;
}
.risk-panel {
  margin-bottom: 14px;
  padding: 12px 14px;
  border-radius: 12px;
  background: #fff9f0;
  border: 1px solid #f3ddab;
}
.risk-panel__title {
  font-size: 13px;
  font-weight: 600;
  color: #1f2329;
}
.risk-panel__desc {
  margin-top: 6px;
  font-size: 12px;
  color: #596273;
  line-height: 1.7;
}
.section-caption {
  margin: 14px 0 2px;
  padding-left: 10px;
  border-left: 3px solid var(--prev-color-primary);
  font-size: 13px;
  font-weight: 600;
  line-height: 1.2;
  color: #303133;
}
.section {
  padding: 14px 0 4px;
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
    margin-top: 0;
  }
  .list--compact {
    margin-bottom: 0;
  }
  .item {
    flex: 0 0 calc(100% / 3);
    display: flex;
    margin-top: 12px;
    font-size: 12px;
    line-height: 1.6;
    color: #606266;
    padding-right: 16px;
    padding-left: 16px;
    &.item100{
     flex: 0 0 calc(100% / 1);
     padding-right: 20px;
     padding-left: 20px;
    }
    &:nth-child(2n + 1) {
      padding-right: 20px;
      padding-left: 20px;
    }
    // &:nth-child(2n) {
    //  padding-right: 20px;
    // }
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
.detail-table :deep(.el-table__cell) {
  padding: 8px 0;
}
.detail-table :deep(.el-table__body .cell) {
  line-height: 1.4;
}
.info-red{
  color: red;
  font-size: 12px;
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
.logistics {
  align-items: center;
  padding: 10px 20px;

  .logistics_img {
    width: 45px;
    height: 45px;
    margin-right: 12px;

    img {
      width: 100%;
      height: 100%;
    }
  }

  .logistics_cent {
    span {
      display: block;
      font-size: 12px;
    }
  }
}
.trees-coadd {
  width: 100%;
  height: 400px;
  border-radius: 4px;
  overflow: hidden;

  .scollhide {
    width: 100%;
    height: 100%;
    overflow: auto;
    margin-left: 18px;
    padding: 10px 0 10px 0;
    box-sizing: border-box;

    .content {
      font-size: 12px;
    }

    .time {
      font-size: 12px;
      color: #2d8cf0;
    }
  }
}
.lrflex{
  display: flex;
  align-items: center;
  justify-content: left;
}

@media (max-width: 960px) {
  .detail-guide__header {
    flex-direction: column;
    align-items: flex-start;
  }

  .detail-guide__grid {
    grid-template-columns: 1fr;
  }
}
</style>
