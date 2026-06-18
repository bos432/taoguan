<template>
  <div>
    <el-tabs type="border-card" v-model="activeName">
      <el-tab-pane label="基本信息" name="detail">
        <div class="section">
          <el-divider content-position="left">用户信息</el-divider>
          <ul class="list">
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
          <el-divider content-position="left">收货信息</el-divider>
          <ul class="list">
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
          <el-divider content-position="left">订单信息</el-divider>
          <ul class="list">
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
              <div>订单金额：</div>
              <div class="value">
                ¥{{merData.total_price}}
              </div>
            </li>
          </ul>
        </div>
      </el-tab-pane>
      <el-tab-pane label="商品信息" name="account">
        <el-table :data="merData.detaileds" style="width: 100%">
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
        <el-table :data="merData.logs" style="width: 100%">
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
  </div>   
</template>
<script>

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
    };
  },
  filters: {
  },
  methods: {
  },
};
</script>
<style lang="scss" scoped>
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
    margin-top: 5px;
  }
  .item {
    flex: 0 0 calc(100% / 3);
    display: flex;
    margin-top: 16px;
    font-size: 13px;
    color: #606266;
    padding-right: 20px;
    padding-left: 20px;
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

</style>
