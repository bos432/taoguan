<template>
  <el-scrollbar native :max-height="height - 30">
    <el-row>
      <el-col :span="16">
        <el-form ref="ref" :model="model" label-width="120px">
          <div class="setting-guide-panel">
            <div class="setting-guide-panel__header">
              <div>
                <div class="setting-guide-panel__title">系统基础配置建议先这样改</div>
                <div class="setting-guide-panel__desc">
                  先补系统简称、页面标题和展示素材，再核移动端域名与客服信息，最后再处理登录背景和过审开关。
                </div>
              </div>
              <span class="setting-guide-panel__badge">{{ systemFocusLabel }}</span>
            </div>
            <div class="setting-guide-panel__grid">
              <div v-for="item in systemGuideCards" :key="item.title" class="setting-guide-card">
                <div class="setting-guide-card__title">{{ item.title }}</div>
                <div class="setting-guide-card__desc">{{ item.desc }}</div>
                <div class="setting-guide-card__action">{{ item.action }}</div>
              </div>
            </div>
          </div>
          <div class="setting-panel-overview">
            <div class="setting-panel-overview__card">
              <span class="setting-panel-overview__label">系统简称</span>
              <strong>{{ model.system_name || '未设置' }}</strong>
            </div>
            <div class="setting-panel-overview__card">
              <span class="setting-panel-overview__label">登录背景</span>
              <strong>{{
                model.login_bg_id ? '图片模式' : model.login_bg_color || '纯色模式'
              }}</strong>
            </div>
            <div class="setting-panel-overview__card">
              <span class="setting-panel-overview__label">客服信息</span>
              <strong>{{ serviceSummary }}</strong>
            </div>
          </div>
          <div class="setting-followup-strip">
            <div class="setting-followup-strip__copy">
              <div class="setting-followup-strip__title">改完记得回真实入口复核</div>
              <div class="setting-followup-strip__desc">
                系统简称、标题、登录背景、移动端域名和客服资料都会直接影响后台首屏与前端展示，提交后别只看成功提示。
              </div>
            </div>
            <div class="setting-followup-strip__actions">
              <el-button plain @click="openAdminLogin">去后台登录页看首屏</el-button>
              <el-button plain @click="openMemberSite">去前端入口复核</el-button>
              <el-button type="primary" plain @click="goToRoute('/dashboard')"
                >去首页核品牌回显</el-button
              >
            </div>
          </div>
          <el-form-item label="* 系统简称" prop="system_name">
            <el-col :span="8">
              <el-input v-model="model.system_name" type="text" clearable />
            </el-col>
            <el-col :span="16"> 侧边栏、登录页显示。</el-col>
          </el-form-item>
          <el-form-item label="* 页面标题" prop="page_title">
            <el-col :span="8">
              <el-input v-model="model.page_title" type="text" clearable />
            </el-col>
            <el-col :span="16"> 浏览器页面标题后缀。 </el-col>
          </el-form-item>
          <el-form-item label="favicon" prop="favicon_id">
            <FileImage
              v-model="model.favicon_id"
              :file-url="model.favicon_url"
              file-title="上传favicon"
              file-tip="图片小于 50 KB，jpg、png、ico格式，128 x 128。"
              :height="50"
              upload
            />
          </el-form-item>
          <el-form-item label="logo" prop="logo_id">
            <FileImage
              v-model="model.logo_id"
              :file-url="model.logo_url"
              file-title="上传logo"
              file-tip="图片小于 200 KB，jpg、png格式，150 x 150"
              :height="100"
              upload
            />
          </el-form-item>
          <el-form-item label="登录背景图" prop="login_bg_id">
            <FileImage
              v-model="model.login_bg_id"
              :file-url="model.login_bg_url"
              file-title="上传登录背景图"
              file-tip="图片小于 200 KB，jpg、png格式，1920 x 1080。"
              :height="100"
              upload
            />
          </el-form-item>
          <el-form-item label="登录背景色">
            <el-col :span="8">
              <el-color-picker
                v-model="model.login_bg_color"
                :predefine="[
                  '#2d3a4b',
                  '#2C8AFF',
                  '#1C2D56',
                  '#121E56',
                  '#079583',
                  '#09AEC2',
                  '#5F45CD',
                  '#E10E2D'
                ]"
                @change="loginBgColorChange"
              />
            </el-col>
            <el-col :span="16">
              <el-text size="default">登录页面背景颜色。</el-text>
            </el-col>
          </el-form-item>
          <el-form-item label="分页每页数量" prop="page_limit">
            <el-col :span="8">
              <el-input v-model="model.page_limit" type="number" />
            </el-col>
            <el-col :span="16">
              <el-text size="default">分页每页显示数量。</el-text>
            </el-col>
          </el-form-item>
          <el-form-item label="移动端域名" prop="member_website">
            <el-col :span="8">
              <el-input v-model="model.member_website" />
            </el-col>
            <el-col :span="16">
              <el-text size="default">不要以/结尾</el-text>
            </el-col>
          </el-form-item>
          <el-form-item label="小程序过审开关" prop="wx_approved">
            <el-col :span="8">
              <el-switch v-model="model.wx_approved" :active-value="1" :inactive-value="0" />
            </el-col>
            <el-col :span="16"> 开启后，前端会隐藏一些功能。 </el-col>
          </el-form-item>
          <el-form-item label="客服电话" prop="service_phone">
            <el-col :span="8">
              <el-input
                v-model="model.service_phone"
                placeholder="请输入客服联系电话，多个逗号隔开"
                clearable
              />
            </el-col>
          </el-form-item>
          <el-form-item label="客服QQ号" prop="service_qq">
            <el-col :span="8">
              <el-input
                v-model="model.service_qq"
                placeholder="请输入客服QQ号，多个逗号隔开"
                clearable
              />
            </el-col>
          </el-form-item>
          <el-form-item label="客服微信号" prop="service_wechat">
            <el-col :span="8">
              <el-input
                v-model="model.service_wechat"
                placeholder="请输入客服微信号，多个逗号隔开"
                clearable
              />
            </el-col>
          </el-form-item>
          <el-form-item label="客服微信二维码" prop="service_wechat_image_id">
            <FileImage
              v-model="model.service_wechat_image_id"
              :file-url="model.service_wechat_image_url"
              file-title="上传客服微信二维码"
              file-tip="图片小于 200 KB，jpg、png格式，1920 x 1080。"
              :height="100"
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
  </el-scrollbar>
</template>

<script>
import screenHeight from '@/utils/screen-height'
import { systemInfo, systemEdit } from '@/api/system/setting'
import { useSettingsStore } from '@/store/modules/settings'

export default {
  name: 'SystemSettingSystem',
  data() {
    return {
      name: '系统设置',
      loading: false,
      height: 680,
      model: {
        system_name: '',
        page_title: '',
        favicon_id: 0,
        favicon_url: '',
        logo_id: 0,
        logo_url: '',
        login_bg_id: 0,
        login_bg_url: '',
        login_bg_color: '',
        page_limit: 20,
        member_website: '',
        service_type: 1, //客服中心模式：1、展示联系方式 2、在线咨询
        service_phone: '', //客服电话，多个逗号隔开
        service_qq: '', //客服QQ，多个逗号隔开
        service_wechat: '', //客服微信号
        service_wechat_image_id: null, //客服微信二维码
        wx_approved: 0 //小程序过审开关，1开启0关闭
      }
    }
  },
  computed: {
    serviceSummary() {
      if (this.model.service_phone) return '电话已配置'
      if (this.model.service_wechat || this.model.service_wechat_image_id) return '微信已配置'
      if (this.model.service_qq) return 'QQ已配置'
      return '未配置'
    },
    systemFocusLabel() {
      if (!this.model.system_name || !this.model.page_title) {
        return '先补品牌基础展示'
      }
      if (!this.model.member_website) {
        return '先核移动端入口'
      }
      if (this.serviceSummary === '未配置') {
        return '先补客服触点'
      }
      return '优先回查登录与客服展示'
    },
    systemGuideCards() {
      return [
        {
          title: '第一步：先补系统展示名',
          desc: '系统简称、页面标题、logo 和 favicon 会直接影响后台和登录页第一眼展示。',
          action: `当前简称：${this.model.system_name || '未设置'}；标题：${
            this.model.page_title || '未设置'
          }`
        },
        {
          title: '第二步：再核移动端入口',
          desc: '移动端域名和小程序过审开关会影响 H5、小程序的真实访问和页面展示范围。',
          action: this.model.member_website || '移动端域名还没配置'
        },
        {
          title: '第三步：最后补客服触点',
          desc: '客服电话、微信和二维码补齐后，前端用户联系链路才算闭环。',
          action: `当前客服状态：${this.serviceSummary}`
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
      this.$router.push({ path, query: { from: 'system-setting-systems', ...query } })
    },
    openAdminLogin() {
      window.open(`${window.location.origin}/admin-next/#/login`, '_blank', 'noopener')
    },
    openMemberSite() {
      const url = this.model.member_website || `${window.location.origin}/app/`
      window.open(url, '_blank', 'noopener')
    },
    // 信息
    info() {
      systemInfo().then((res) => {
        this.model = res.data
        this.setting(this.model)
      })
    },
    // 刷新
    refresh() {
      this.loading = true
      systemInfo()
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
          systemEdit(this.model)
            .then((res) => {
              this.setting(this.model)
              this.loading = false
              ElMessage.success(res.msg)
              this.$nextTick(() => {
                ElMessage.info('提交后请回后台登录页、前端入口和首页复核品牌展示与客服资料。')
              })
            })
            .catch(() => {
              this.loading = false
            })
        }
      })
    },
    setting(setting) {
      const settingsStore = useSettingsStore()
      settingsStore.changeSetting({ key: 'loginBgUrl', value: setting.login_bg_url })
      settingsStore.changeSetting({ key: 'loginBgColor', value: setting.login_bg_color })
      settingsStore.changeSetting({ key: 'systemName', value: setting.system_name })
      settingsStore.changeSetting({ key: 'pageTitle', value: setting.page_title })
      settingsStore.changeSetting({ key: 'faviconUrl', value: setting.favicon_url })
      settingsStore.changeSetting({ key: 'logoUrl', value: setting.logo_url })
      settingsStore.changeSetting({ key: 'pageLimit', value: setting.page_limit })
    },
    // 上传图片
    fileUpload(field, title = '') {
      this.fileField = field
      this.fileTitle = title
      this.fileDialog = true
    },
    fileCancel() {
      this.fileDialog = false
    },
    fileSubmit(fileList) {
      this.fileDialog = false
      const fileField = this.fileField
      const fileLength = fileList.length
      if (fileLength) {
        const i = fileLength - 1
        if (fileField === 'favicon') {
          this.model.favicon_id = fileList[i]['file_id']
          this.model.favicon_url = fileList[i]['file_url']
        } else if (fileField === 'logo') {
          this.model.logo_id = fileList[i]['file_id']
          this.model.logo_url = fileList[i]['file_url']
        } else if (fileField === 'login_bg') {
          this.model.login_bg_id = fileList[i]['file_id']
          this.model.login_bg_url = fileList[i]['file_url']
        }
      }
    },
    fileDelete(field = '') {
      if (field === 'favicon') {
        this.model.favicon_id = 0
        this.model.favicon_url = ''
      } else if (field === 'logo') {
        this.model.logo_id = 0
        this.model.logo_url = ''
      } else if (field === 'login_bg') {
        this.model.login_bg_id = 0
        this.model.login_bg_url = ''
      }
    },
    // 登录背景色
    loginBgColorChange(val) {
      const settingsStore = useSettingsStore()
      settingsStore.changeSetting({ key: 'loginBgColor', value: val })
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
