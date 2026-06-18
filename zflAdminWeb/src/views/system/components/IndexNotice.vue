<template>
  <div>
    <el-card shadow="never" class="notice-brief-card">
      <div class="notice-brief-card__header">
        <div>
          <div class="notice-brief-card__title">公告提醒</div>
          <div class="notice-brief-card__desc">
            这里负责把后台公告先拦一遍，避免运营漏看上线提醒、制度通知或紧急处理消息。
          </div>
        </div>
        <el-tag :type="noticeTagType" effect="plain">{{ noticeFocusLabel }}</el-tag>
      </div>
      <div class="notice-brief-card__summary">
        <div class="notice-brief-card__item">
          <span>未读公告</span>
          <strong>{{ count }}</strong>
        </div>
        <div class="notice-brief-card__item">
          <span>提示状态</span>
          <strong>{{ getNotice() ? '已关闭本轮提示' : '会自动弹出' }}</strong>
        </div>
        <div class="notice-brief-card__item">
          <span>建议动作</span>
          <strong>看完后去公告管理</strong>
        </div>
      </div>
      <div class="notice-brief-card__actions">
        <el-button text type="primary" @click="list()">重新检查</el-button>
        <el-button text type="primary" @click="goToNoticePage">去公告管理</el-button>
        <el-button text type="primary" @click="resetNoticeHint">恢复自动提示</el-button>
      </div>
    </el-card>

    <el-dialog
      v-model="dialog"
      :title="dialogTitle"
      :close-on-click-modal="false"
      :close-on-press-escape="false"
      :before-close="cancel"
      top="10vh"
      center
    >
      <el-table
        ref="table"
        v-loading="loading"
        :data="data"
        :height="height - 200"
        :show-header="false"
      >
        <el-table-column prop="image_id" min-width="90">
          <template #default="scope">
            <FileImage :file-url="scope.row.image_url" lazy />
          </template>
        </el-table-column>
        <el-table-column prop="title" min-width="250" show-overflow-tooltip>
          <template #default="scope">
            <span :style="{ color: scope.row.title_color }">{{ scope.row.title }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="start_time" width="165" />
        <el-table-column width="100">
          <template #default="scope">
            <el-button text type="primary" @click="info(scope.row)">
              {{ $t('common.view') }}
            </el-button>
          </template>
        </el-table-column>
      </el-table>
      <pagination
        v-show="count > 0"
        v-model:total="count"
        v-model:page="query.page"
        v-model:limit="query.limit"
        :background="false"
        layout="prev, pager, next"
        @pagination="list"
      />
      <template #footer>
        <el-button text @click="nohint(count)">{{ $t('common.Don not prompt again') }}</el-button>
        <el-button text @click="submit">{{ $t('common.close') }}</el-button>
      </template>
    </el-dialog>

    <el-dialog
      v-model="oneDialog"
      :close-on-click-modal="false"
      :close-on-press-escape="false"
      top="18vh"
      width="33%"
      center
    >
      <template #header>
        <span :style="{ color: oneModel.title_color }">{{ oneModel.title }}</span>
      </template>
      <el-scrollbar native :height="430">
        <el-form ref="ref" :model="oneModel" label-width="0" class="text-center">
          <el-form-item prop="start_time">
            <el-col class="text-center">{{ oneModel.start_time }}</el-col>
          </el-form-item>
          <el-form-item v-if="oneModel.image_url" prop="image_url">
            <FileImage :file-url="oneModel.image_url" :height="150" />
          </el-form-item>
          <el-form-item prop="desc">
            <el-col class="text-center">{{ oneModel.desc }}</el-col>
          </el-form-item>
        </el-form>
      </el-scrollbar>
      <template #footer>
        <el-button @click="oneNohint(count)">{{ $t('common.Don not prompt again') }}</el-button>
        <el-button @click="oneSubmit">{{ $t('common.close') }}</el-button>
        <el-button type="primary" @click="info(oneModel)">{{ $t('common.view') }}</el-button>
      </template>
    </el-dialog>

    <el-dialog
      v-model="infoDialog"
      :close-on-click-modal="false"
      :close-on-press-escape="false"
      top="9vh"
      center
    >
      <template #header>
        <span :style="{ color: infoModel.title_color }">{{ infoModel.title }}</span>
      </template>
      <el-scrollbar native :height="height">
        <el-form ref="ref" :model="infoModel" label-width="0" class="text-center">
          <el-form-item prop="start_time">
            <el-col class="text-center">{{ infoModel.start_time }}</el-col>
          </el-form-item>
          <el-form-item v-if="infoModel.image_url" prop="image_url">
            <FileImage :file-url="infoModel.image_url" :height="150" />
          </el-form-item>
          <el-form-item prop="desc">
            <el-col class="text-center">{{ infoModel.desc }}</el-col>
          </el-form-item>
          <el-form-item prop="content">
            <el-col class="text-center"><div v-html="infoModel.content"></div></el-col>
          </el-form-item>
        </el-form>
      </el-scrollbar>
    </el-dialog>
  </div>
</template>
<script>
import screenHeight from '@/utils/screen-height'
import Pagination from '@/components/Pagination/index.vue'
import { setNotice, getNotice, getPageLimit } from '@/utils/settings'
import { notice } from '@/api/system/index'
import { info } from '@/api/system/notice'

export default {
  name: 'SystemIndexNotice',
  components: { Pagination },
  data() {
    return {
      name: '公告',
      height: 680,
      loading: false,
      idkey: 'notice_id',
      query: { page: 1, limit: getPageLimit() },
      data: [],
      exps: [{ exp: 'like', name: '包含' }],
      count: 0,
      dialog: false,
      dialogTitle: this.$t('common.Notice'),
      infoDialog: false,
      infoModel: {},
      oneDialog: false,
      oneModel: {}
    }
  },
  computed: {
    noticeFocusLabel() {
      if (!this.count) {
        return '当前无待看公告'
      }
      return this.count === 1 ? '有 1 条公告待看' : `有 ${this.count} 条公告待看`
    },
    noticeTagType() {
      return this.count ? 'warning' : 'success'
    }
  },
  created() {
    this.height = screenHeight()
    this.list()
  },
  methods: {
    getNotice,
    list() {
      if (!getNotice()) {
        this.loading = true
        notice(this.query)
          .then((res) => {
            this.data = res.data.list
            this.count = res.data.count
            this.loading = false
            if (this.count > 0) {
              if (this.count === 1) {
                const row = this.data[0]
                this.oneInfo(row)
              } else {
                this.dialog = true
              }
            }
          })
          .catch(() => {
            this.loading = false
          })
      }
    },
    info(row) {
      this.infoDialog = true
      var id = {}
      id[this.idkey] = row[this.idkey]
      info(id).then((res) => {
        this.infoModel = res.data
      })
    },
    cancel() {
      this.dialog = false
    },
    submit() {
      this.dialog = false
    },
    nohint(count) {
      this.dialog = false
      setNotice(count)
    },
    oneInfo(row) {
      this.oneDialog = true
      var id = {}
      id[this.idkey] = row[this.idkey]
      info(id).then((res) => {
        this.oneModel = res.data
      })
    },
    oneCancel() {
      this.oneDialog = false
    },
    oneSubmit() {
      this.oneDialog = false
    },
    oneNohint(count) {
      this.oneDialog = false
      setNotice(count)
    },
    goToNoticePage() {
      this.$router.push({
        path: '/system/notice',
        query: {
          from: 'dashboard-notice'
        }
      })
    },
    resetNoticeHint() {
      setNotice(0)
      ElMessage.success('已恢复公告自动提示，下次进入后台会再次检查。')
      this.list()
    }
  }
}
</script>

<style scoped>
.notice-brief-card {
  margin-bottom: 14px;
}

.notice-brief-card__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
}

.notice-brief-card__title {
  font-size: 15px;
  font-weight: 700;
  color: #0f172a;
}

.notice-brief-card__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.notice-brief-card__summary {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-top: 14px;
}

.notice-brief-card__item {
  padding: 12px 14px;
  border: 1px solid #e6ecf5;
  border-radius: 14px;
  background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
}

.notice-brief-card__item span {
  display: block;
  font-size: 11px;
  color: #7c8aa5;
}

.notice-brief-card__item strong {
  display: block;
  margin-top: 6px;
  font-size: 14px;
  color: #0f172a;
}

.notice-brief-card__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin-top: 14px;
}

@media (max-width: 900px) {
  .notice-brief-card__summary {
    grid-template-columns: 1fr;
  }
}
</style>
