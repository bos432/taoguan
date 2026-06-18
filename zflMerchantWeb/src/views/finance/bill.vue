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
            <el-form-item label="账单类型：" prop="type">
              <el-select
                  v-model="query.type"
                  placeholder="账单类型"
                  @change="search()"
                  clearable
                  filterable
              >
                <el-option v-for="(item, index) in type_list" :key="index" :value="item.value" :label="item.label" />
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
                  <el-option :value="idkey" label="流水号" />
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
    </el-card>
    <el-card  class="app-main">
      <div class="operation_btn mb5">
        <!-- 操作 -->
        <div>
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
          :height="height"
          @sort-change="sort"
      >
        <el-table-column type="selection" width="42" title="全选/反选" />
        <el-table-column :prop="idkey" label="流水号" width="120" sortable="custom" />
        <el-table-column prop="type_str" label="类型" min-width="60" show-overflow-tooltip />
        <el-table-column prop="title" label="名称" min-width="300" show-overflow-tooltip />
        <el-table-column prop="money" label="收支" min-width="120" show-overflow-tooltip />
        <el-table-column prop="remark" label="备注" min-width="150" show-overflow-tooltip />
        <el-table-column prop="create_time" label="时间" min-width="170" sortable="custom" />
      </el-table>
    </el-card>
  </div>
</template>

<script>
import screenHeight from '@/utils/screen-height'
import Pagination from '@/components/Pagination/index.vue'
import clip from '@/utils/clipboard'
import { arrayColumn } from '@/utils/index'
import { getPageLimit } from '@/utils/settings'
import { list as getList,getParams as selectParams} from '@/api/finance/bill.js'
import {shortcuts} from "@/utils/getDate.js";

export default {
  name: 'bill',
  components: { Pagination },
  data() {
    return {
      name: '资金明细',
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
        type:undefined,
      },
      data: [],
      count: 0,
      dialog: false,
      dialogTitle: '',
      model: {},
      selection: [],
      selectIds: '',
      selectTitle: '操作',
      selectDialog: false,
      selectType: '',
      shortcuts:shortcuts(),
      type_list:[],
    }
  },
  created() {
    this.height = screenHeight()
    this.list();
    this.getParams();
  },
  methods: {
    //查询类型
    getParams(){
      selectParams({})
          .then((res) => {
            this.type_list = res.data.bill_types;
          });
    },
    // 列表
    list() {
      this.loading = true
      getList(this.query)
          .then((res) => {
            this.data = res.data.list
            this.count = res.data.count
            this.loading = false
          })
          .catch(() => {
            this.loading = false
          })
    },
    // 重置
    reset(row) {
      if (row) {
        this.model = row
      } else {
        this.model = this.$options.data().model
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
    // 复制
    copy(text) {
      clip(text)
    },
  }
}
</script>