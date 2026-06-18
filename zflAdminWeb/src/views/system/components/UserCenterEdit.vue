<template>
  <el-scrollbar native :max-height="height - 30">
    <div class="user-center-panel">
      <div class="overview-grid">
        <div class="overview-card">
          <span class="overview-label">编辑主体</span>
          <strong>{{ model.nickname || '未设置昵称' }}</strong>
          <p>账号：{{ model.username || '--' }}</p>
        </div>
        <div class="overview-card">
          <span class="overview-label">头像状态</span>
          <strong>{{ model.avatar_id ? '已上传头像' : '使用默认头像' }}</strong>
          <p>建议使用 100 x 100 JPG/PNG</p>
        </div>
        <div class="overview-card">
          <span class="overview-label">联系方式</span>
          <strong>{{ model.phone || '未绑定手机' }}</strong>
          <p>{{ model.email || '未绑定邮箱' }}</p>
        </div>
      </div>

      <div class="section-title">
        <div>
          <h3>修改资料</h3>
          <p>提交后会同步更新顶部管理员信息显示，保留原有提交逻辑不变。</p>
        </div>
        <div class="section-actions">
          <el-button :loading="loading" @click="refresh">刷新</el-button>
          <el-button :loading="loading" type="primary" @click="submit">提交</el-button>
        </div>
      </div>
      <div class="plain-guide-panel">
        <div class="plain-guide-panel__header">
          <div>
            <div class="plain-guide-panel__title">改资料前先确认这是当前账号本人资料</div>
            <div class="plain-guide-panel__desc">
              这页改的是当前管理员本人的昵称、头像和联系方式。先确认不是在替别人改资料，再提交，避免把顶部显示和登录信息一起改乱。
            </div>
          </div>
          <span class="plain-guide-panel__badge">{{ editFocusLabel }}</span>
        </div>
      </div>

      <div class="follow-up-strip">
        <div>
          <h4>改完资料要去真实页面看结果</h4>
          <p>提交后别只看成功提示，建议回个人资料、首页头像昵称和后台登录页各核对一次。</p>
        </div>
        <div class="follow-up-strip__actions">
          <el-button plain @click="goToRoute('/system/user-center', { tab: 'info' })"
            >回个人资料</el-button
          >
          <el-button plain @click="goToRoute('/dashboard')">去首页核对显示</el-button>
          <el-button type="primary" plain @click="openAdminLogin">去后台登录页复看</el-button>
        </div>
      </div>

      <el-row :gutter="16">
        <el-col :span="12">
          <el-form ref="ref" :rules="rules" :model="model" label-width="120px" class="form-card">
            <el-form-item label="头像" prop="avatar_id">
              <FileImage
                v-model="model.avatar_id"
                :file-url="model.avatar_url"
                file-title="上传头像"
                file-tip="图片小于 100 KB，jpg、png格式，100 x 100"
                :height="100"
                avatar
                upload
              />
            </el-form-item>
            <el-form-item label="昵称" prop="nickname">
              <el-input v-model="model.nickname" placeholder="请输入昵称" clearable />
            </el-form-item>
            <el-form-item label="账号" prop="username">
              <el-input v-model="model.username" placeholder="请输入账号" clearable />
            </el-form-item>
            <el-form-item label="手机" prop="phone">
              <el-input v-model="model.phone" placeholder="请输入手机" clearable />
            </el-form-item>
            <el-form-item label="邮箱" prop="email">
              <el-input v-model="model.email" placeholder="请输入邮箱" clearable />
            </el-form-item>
          </el-form>
        </el-col>
      </el-row>
    </div>
  </el-scrollbar>
</template>

<script>
import screenHeight from '@/utils/screen-height'
import { useUserStoreHook } from '@/store/modules/user'
import { info, edit } from '@/api/system/user-center'

export default {
  name: 'SystemUserCenterEdit',
  data() {
    return {
      name: '修改信息',
      height: 680,
      loading: false,
      model: {
        avatar_id: 0,
        avatar_url: '',
        username: '',
        nickname: '',
        phone: '',
        email: ''
      },
      rules: {
        nickname: [{ required: true, message: '请输入昵称', trigger: 'blur' }],
        username: [{ required: true, message: '请输入账号', trigger: 'blur' }]
      }
    }
  },
  computed: {
    editFocusLabel() {
      if (!this.model.nickname || !this.model.username) {
        return '先补基础资料'
      }
      if (!this.model.phone && !this.model.email) {
        return '建议补联系方式'
      }
      return '提交后回首页和日志复核'
    }
  },
  created() {
    this.height = screenHeight(210)
    this.info()
  },
  methods: {
    // 信息
    info(msg = false) {
      info()
        .then((res) => {
          this.reset(res.data)
          this.update(res.data)
          if (msg) {
            ElMessage.success(res.msg)
          }
        })
        .catch(() => {})
    },
    // 刷新
    refresh() {
      this.loading = true
      info()
        .then((res) => {
          this.reset(res.data)
          this.update(res.data)
          ElMessage.success(res.msg)
        })
        .finally(() => {
          this.loading = false
        })
    },
    // 提交
    submit() {
      this.$refs['ref'].validate((valid) => {
        if (valid) {
          this.loading = true
          edit(this.model)
            .then((res) => {
              this.update(this.model)
              this.loading = false
              ElMessage.success(res.msg)
              this.$nextTick(() => {
                ElMessage.info('资料已提交，请继续到个人资料、首页头像昵称和后台登录页各核对一次。')
              })
            })
            .catch(() => {
              this.loading = false
            })
        }
      })
    },
    update(data) {
      const userStore = useUserStoreHook()
      userStore.user.avatar_url = data.avatar_url
      userStore.user.nickname = data.nickname
      userStore.user.username = data.username
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
    goToRoute(path, query = {}) {
      this.$router.push({
        path,
        query
      })
    },
    openAdminLogin() {
      window.open(`${window.location.origin}/admin-next/#/login`, '_blank')
    }
  }
}
</script>

<style scoped>
.user-center-panel {
  padding: 4px 2px 12px;
}

.overview-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 14px;
  margin-bottom: 18px;
}

.overview-card,
.form-card {
  border: 1px solid #ebeef5;
  border-radius: 14px;
  background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
  box-shadow: 0 10px 24px rgba(31, 35, 41, 0.06);
}

.overview-card {
  padding: 16px 18px;
}

.overview-label {
  display: inline-block;
  margin-bottom: 10px;
  color: #7a8599;
  font-size: 12px;
}

.overview-card strong {
  display: block;
  color: #1f2329;
  font-size: 18px;
}

.overview-card p {
  margin: 8px 0 0;
  color: #5f6b7a;
  font-size: 13px;
}

.section-title {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 16px;
}

.section-title h3 {
  margin: 0 0 4px;
  color: #1f2329;
  font-size: 18px;
}

.section-title p {
  margin: 0;
  color: #7a8599;
  font-size: 13px;
}

.section-actions {
  display: flex;
  gap: 10px;
}

.follow-up-strip {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 18px;
  padding: 16px 18px;
  border: 1px solid #dbeafe;
  border-radius: 14px;
  background: linear-gradient(90deg, #f8fbff 0%, #eff6ff 100%);
}

.plain-guide-panel {
  margin-bottom: 16px;
  padding: 16px;
  border: 1px solid #dbe7f5;
  border-radius: 14px;
  background: linear-gradient(135deg, #f7fbff 0%, #ffffff 100%);
}

.plain-guide-panel__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
}

.plain-guide-panel__title {
  font-size: 15px;
  font-weight: 700;
  color: #1f2329;
}

.plain-guide-panel__desc {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.7;
  color: #64748b;
}

.plain-guide-panel__badge {
  min-width: 180px;
  padding: 10px 12px;
  border-radius: 12px;
  background: rgba(37, 99, 235, 0.08);
  color: #1d4ed8;
  font-size: 12px;
  font-weight: 700;
  line-height: 1.6;
}

.follow-up-strip h4 {
  margin: 0 0 6px;
  color: #1d4ed8;
  font-size: 15px;
}

.follow-up-strip p {
  margin: 0;
  color: #5f6b7a;
  font-size: 13px;
}

.follow-up-strip__actions {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 10px;
}

.form-card {
  padding: 18px 18px 2px;
}

@media (max-width: 992px) {
  :deep(.el-col) {
    max-width: 100%;
    flex: 0 0 100%;
  }

  .section-title {
    flex-direction: column;
    align-items: flex-start;
  }

  .follow-up-strip {
    flex-direction: column;
    align-items: flex-start;
  }

  .plain-guide-panel__header {
    flex-direction: column;
  }

  .plain-guide-panel__badge {
    min-width: auto;
    width: 100%;
  }

  .follow-up-strip__actions {
    justify-content: flex-start;
  }
}
</style>
