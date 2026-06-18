<template>
  <el-row>
    <el-col :span="14">
      <el-form ref="ref" :model="model" :rules="rules" label-width="120px">
        <div class="setting-guide-panel">
          <div class="setting-guide-panel__header">
            <div>
              <div class="setting-guide-panel__title">会员基础设置主要先管默认头像</div>
              <div class="setting-guide-panel__desc">
                这页改动虽然不多，但会直接影响新会员默认展示。先确认头像素材是否统一，再上传、预览、提交。
              </div>
            </div>
            <span class="setting-guide-panel__badge">{{ memberSettingFocusLabel }}</span>
          </div>
          <div class="setting-guide-panel__grid">
            <div
              v-for="item in memberSettingGuideCards"
              :key="item.title"
              class="setting-guide-card"
            >
              <div class="setting-guide-card__title">{{ item.title }}</div>
              <div class="setting-guide-card__desc">{{ item.desc }}</div>
              <div class="setting-guide-card__action">{{ item.action }}</div>
            </div>
          </div>
        </div>
        <div class="setting-panel-overview">
          <div class="setting-panel-overview__card">
            <span class="setting-panel-overview__label">配置主题</span>
            <strong>会员基础资料</strong>
          </div>
          <div class="setting-panel-overview__card">
            <span class="setting-panel-overview__label">默认头像</span>
            <strong>{{ model.default_avatar_id ? '已设置' : '未设置' }}</strong>
          </div>
          <div class="setting-panel-overview__card">
            <span class="setting-panel-overview__label">维护建议</span>
            <strong>先上传再提交</strong>
          </div>
        </div>
        <div class="setting-followup-strip">
          <div class="setting-followup-strip__copy">
            <div class="setting-followup-strip__title">提交后请直接回真实会员场景验证</div>
            <div class="setting-followup-strip__desc">
              默认头像改完后，要确认后台会员列表和前端“我的”页的新会员展示一致，别停在素材预览层。
            </div>
          </div>
          <div class="setting-followup-strip__actions">
            <el-button plain @click="goToRoute('/member/member')">去会员列表核对</el-button>
            <el-button plain @click="goToRoute('/member/statistic')">去会员统计复核</el-button>
            <el-button type="primary" plain @click="openMemberLogin">去 H5 登录页验证</el-button>
          </div>
        </div>
        <el-form-item label="默认头像" prop="default_avatar_id">
          <FileImage
            v-model="model.default_avatar_id"
            :file-url="model.default_avatar_url"
            file-title="上传头像"
            file-tip="图片小于 50 KB，jpg、png格式，128 x 128。"
            :height="100"
            avatar
            upload
          />
        </el-form-item>
        <el-form-item>
          <el-button :loading="loading" @click="refresh()">刷新</el-button>
          <el-button :loading="loading" type="primary" @click="submit()">提交</el-button>
        </el-form-item>
      </el-form>
    </el-col>
  </el-row>
</template>

<script>
import screenHeight from '@/utils/screen-height'
import { memberInfo, memberEdit } from '@/api/member/setting'

export default {
  name: 'SettingMember',
  data() {
    return {
      name: '会员设置',
      height: 680,
      loading: false,
      model: {
        default_avatar_id: 0,
        default_avatar_url: ''
      },
      rules: {}
    }
  },
  computed: {
    memberSettingFocusLabel() {
      return this.model.default_avatar_id ? '先回查前端默认展示' : '先补默认头像'
    },
    memberSettingGuideCards() {
      return [
        {
          title: '第一步：先确认是不是默认头像问题',
          desc: '这页只处理新会员默认头像，不会改已经上传过头像的老会员资料。',
          action: '适合处理注册后空头像、素材统一、品牌替换。'
        },
        {
          title: '第二步：再上传并看预览',
          desc: '先确认图片尺寸和清晰度，再上传预览，避免前端出现糊图或裁切异常。',
          action: this.model.default_avatar_id ? '当前已配置默认头像' : '当前还没有默认头像'
        },
        {
          title: '第三步：最后去前端页面回查',
          desc: '提交后最好回会员列表或 H5 端看一下默认头像呈现，确保新用户展示一致。',
          action: '重点回查注册页、我的页面、会员列表头像列。'
        }
      ]
    }
  },
  created() {
    this.height = screenHeight(210)
    this.info()
  },
  methods: {
    goToRoute(path, query = {}) {
      this.$router.push({ path, query: { from: 'member-setting-member', ...query } })
    },
    openMemberLogin() {
      window.open(`${window.location.origin}/app/pages/my/login`, '_blank', 'noopener')
    },
    // 信息
    info() {
      memberInfo().then((res) => {
        this.model = res.data
      })
    },
    // 刷新
    refresh() {
      this.loading = true
      memberInfo()
        .then((res) => {
          this.model = res.data
          this.loading = false
          ElMessage.success(res.msg)
        })
        .catch(() => {
          this.loading = false
        })
    },
    // 提交
    submit() {
      this.$refs['ref'].validate((valid) => {
        if (valid) {
          this.loading = true
          memberEdit(this.model)
            .then((res) => {
              this.loading = false
              ElMessage.success(res.msg)
              this.$nextTick(() => {
                ElMessage.info('提交后请回会员列表、会员统计和 H5 登录页复核默认头像展示。')
              })
            })
            .catch(() => {
              this.loading = false
            })
        }
      })
    }
  }
}
</script>

<style scoped>
.setting-guide-panel {
  margin-bottom: 16px;
  padding: 16px;
  border: 1px solid #dbe7f5;
  border-radius: 16px;
  background: linear-gradient(135deg, #f7fbff 0%, #ffffff 100%);
}

.setting-guide-panel__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
}

.setting-guide-panel__title {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
}

.setting-guide-panel__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.setting-guide-panel__badge {
  min-width: 180px;
  padding: 10px 12px;
  border-radius: 12px;
  background: rgba(37, 99, 235, 0.08);
  color: #1d4ed8;
  font-size: 12px;
  font-weight: 700;
  line-height: 1.6;
}

.setting-guide-panel__grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-top: 14px;
}

.setting-guide-card {
  padding: 14px 16px;
  border: 1px solid #e7edf5;
  border-radius: 14px;
  background: #fff;
}

.setting-guide-card__title {
  font-weight: 700;
  color: #0f172a;
}

.setting-guide-card__desc {
  margin-top: 8px;
  color: #64748b;
  line-height: 1.7;
}

.setting-guide-card__action {
  margin-top: 10px;
  color: #1d4ed8;
  font-size: 12px;
  line-height: 1.6;
}

.setting-panel-overview {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 12px;
  margin-bottom: 16px;
}

.setting-panel-overview__card {
  border: 1px solid #e6ecf5;
  border-radius: 12px;
  padding: 14px 16px;
  background: linear-gradient(180deg, #f9fbff 0%, #ffffff 100%);
  box-shadow: 0 6px 18px rgba(15, 35, 95, 0.05);
}

.setting-panel-overview__label {
  display: block;
  margin-bottom: 8px;
  font-size: 12px;
  color: #7c8aa5;
}

.setting-followup-strip {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 16px;
  padding: 14px 16px;
  border: 1px solid #dbe7f5;
  border-radius: 14px;
  background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
}

.setting-followup-strip__title {
  font-size: 13px;
  font-weight: 700;
  color: #0f172a;
}

.setting-followup-strip__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.setting-followup-strip__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  justify-content: flex-end;
}

@media (max-width: 960px) {
  .setting-guide-panel__header {
    flex-direction: column;
  }

  .setting-guide-panel__badge {
    min-width: auto;
    width: 100%;
  }

  .setting-guide-panel__grid {
    grid-template-columns: 1fr;
  }

  .setting-followup-strip {
    flex-direction: column;
    align-items: flex-start;
  }

  .setting-followup-strip__actions {
    width: 100%;
    justify-content: flex-start;
  }
}
</style>
