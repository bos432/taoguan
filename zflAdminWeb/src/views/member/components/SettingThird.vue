<template>
  <el-row>
    <el-col :span="24">
      <div class="setting-guide-panel">
        <div class="setting-guide-panel__header">
          <div>
            <div class="setting-guide-panel__title">第三方登录先看平台缺口，再看开关</div>
            <div class="setting-guide-panel__desc">
              这页主要处理微信、QQ、微博等登录接入。先确认哪个端真的要用，再补 AppID / Secret，最后才开注册、登录、绑定。
            </div>
          </div>
          <span class="setting-guide-panel__badge">{{ thirdSettingFocusLabel }}</span>
        </div>
        <div class="setting-guide-panel__grid">
          <div v-for="item in thirdSettingGuideCards" :key="item.title" class="setting-guide-card">
            <div class="setting-guide-card__title">{{ item.title }}</div>
            <div class="setting-guide-card__desc">{{ item.desc }}</div>
            <div class="setting-guide-card__action">{{ item.action }}</div>
          </div>
        </div>
      </div>
      <div class="setting-panel-overview">
        <div class="setting-panel-overview__card">
          <span class="setting-panel-overview__label">接入平台数</span>
          <strong>{{ platformCount }}</strong>
        </div>
        <div class="setting-panel-overview__card">
          <span class="setting-panel-overview__label">已配置 AppID</span>
          <strong>{{ configuredAppCount }}</strong>
        </div>
        <div class="setting-panel-overview__card">
          <span class="setting-panel-overview__label">已启用登录</span>
          <strong>{{ enabledLoginCount }}</strong>
        </div>
      </div>
      <div class="setting-followup-strip">
        <div class="setting-followup-strip__main">
          <div class="setting-followup-strip__title">第三方配置改完后，优先验证真实授权链路</div>
          <div class="setting-followup-strip__desc">
            这里只能确认凭据和开关已保存，不能证明微信、QQ、微博入口真的能用。建议提交后立刻回 H5 登录页，再去第三方账号和会员日志页交叉验证。
          </div>
        </div>
        <div class="setting-followup-strip__actions">
          <el-button text type="primary" @click="openH5Login">打开 H5 登录页</el-button>
          <el-button text type="primary" @click="goToRoute('/member/third')">去第三方账号</el-button>
          <el-button text type="primary" @click="goToRoute('/member/log')">去会员日志</el-button>
          <el-button text type="primary" @click="goToRoute('/member/member')">去会员列表</el-button>
        </div>
      </div>
      <el-scrollbar native :height="height">
        <el-form ref="ref" label-width="120px">
          <el-form-item>
            <el-col :span="8"> AppID </el-col>
            <el-col :span="9"> AppSecret </el-col>
            <el-col :span="2"> 注册 </el-col>
            <el-col :span="2"> 登录 </el-col>
            <el-col :span="2"> 绑定 </el-col>
          </el-form-item>
        </el-form>
        <el-form ref="ref" :model="model" :rules="rules" label-width="120px">
          <el-form-item v-for="(item, index) in apps" :key="index" :label="apps[index]">
            <el-col :span="8">
              <el-input v-model="model[index + '_appid']" />
            </el-col>
            <el-col :span="9">
              <el-input v-model="model[index + '_appsecret']" />
            </el-col>
            <el-col :span="2">
              <el-switch
                v-model="model[index + '_register']"
                :active-value="1"
                :inactive-value="0"
              />
            </el-col>
            <el-col :span="2">
              <el-switch v-model="model[index + '_login']" :active-value="1" :inactive-value="0" />
            </el-col>
            <el-col :span="2">
              <el-switch v-model="model[index + '_bind']" :active-value="1" :inactive-value="0" />
            </el-col>
          </el-form-item>
        </el-form>
        <el-form ref="ref" :model="model" :rules="rules" label-width="120px">
          <el-form-item>
            <el-button :loading="loading" @click="refresh()">刷新</el-button>
            <el-button :loading="loading" type="primary" @click="submit()">提交</el-button>
          </el-form-item>
        </el-form>
      </el-scrollbar>
    </el-col>
  </el-row>
  <el-dialog v-model="dialogFormVisible" title="说明" center>
    <el-form :model="model" label-width="0">
      <el-form-item prod="platform_desc">
        <div v-html="model.platform_desc"></div>
      </el-form-item>
    </el-form>
    <template #footer>
      <el-button type="primary" @click="dialogFormVisible = false">关闭</el-button>
    </template>
  </el-dialog>
</template>

<script>
import screenHeight from '@/utils/screen-height'
import { thirdInfo, thirdEdit } from '@/api/member/setting'

export default {
  name: 'MemberSettingThird',
  data() {
    return {
      name: '第三方账号设置',
      height: 680,
      loading: false,
      apps: {
        wx_miniapp: '微信小程序',
        wx_offiacc: '微信公众号',
        wx_website: '微信网站应用',
        wx_mobile: '微信移动应用',
        qq_miniapp: 'QQ小程序',
        qq_website: 'QQ网站应用',
        qq_mobile: 'QQ移动应用',
        wb_website: '微博网站应用'
      },
      model: {
        wx_miniapp_appid: '',
        wx_miniapp_appsecret: '',
        wx_miniapp_register: 1,
        wx_miniapp_login: 1,
        wx_miniapp_bind: 1,
        wx_offiacc_appid: '',
        wx_offiacc_appsecret: '',
        wx_offiacc_register: 1,
        wx_offiacc_login: 1,
        wx_offiacc_bind: 1,
        wx_website_appid: '',
        wx_website_appsecret: '',
        wx_owebsite_register: 1,
        wx_owebsite_login: 1,
        wx_owebsite_bind: 1,
        wx_mobile_appid: '',
        wx_mobile_appsecret: '',
        wx_mobile_register: 1,
        wx_mobile_login: 1,
        wx_mobile_bind: 1,
        qq_miniapp_appid: '',
        qq_miniapp_appsecret: '',
        qq_miniapp_register: 1,
        qq_miniapp_login: 1,
        qq_miniapp_bind: 1,
        qq_website_appid: '',
        qq_website_appsecret: '',
        qq_website_register: 1,
        qq_website_login: 1,
        qq_website_bind: 1,
        qq_mobile_appid: '',
        qq_mobile_appsecret: '',
        qq_mobile_register: 1,
        qq_mobile_login: 1,
        qq_mobile_bind: 1,
        wb_website_appid: '',
        wb_website_appsecret: '',
        wb_owebsite_register: 1,
        wb_owebsite_login: 1,
        wb_owebsite_bind: 1
      },
      rules: {},
      dialogFormVisible: false
    }
  },
  computed: {
    thirdSettingFocusLabel() {
      if (this.configuredAppCount === 0) {
        return '先补平台凭据'
      }
      if (this.enabledLoginCount === 0) {
        return '先开真实使用入口'
      }
      return '优先回查各端登录结果'
    },
    platformCount() {
      return Object.keys(this.apps).length
    },
    configuredAppCount() {
      return Object.keys(this.apps).filter((key) => this.model[`${key}_appid`]).length
    },
    enabledLoginCount() {
      return Object.keys(this.apps).filter((key) => this.model[`${key}_login`] === 1).length
    },
    thirdSettingGuideCards() {
      return [
        {
          title: '第一步：先确认哪些平台真的要用',
          desc: '不是所有平台都要同时接入，先按 H5、小程序、APP 的真实渠道确定范围。',
          action: `当前共 ${this.platformCount} 个平台配置位`
        },
        {
          title: '第二步：再补 AppID 和 Secret',
          desc: '没有平台凭据时不要先开登录开关，否则前端会出现入口在、实际不可用的问题。',
          action: `已配置 AppID：${this.configuredAppCount} 个`
        },
        {
          title: '第三步：最后开注册 / 登录 / 绑定',
          desc: '开关决定前端展示和账号关联能力，改完后要去各端实际点一遍登录流程。',
          action: `当前启用登录：${this.enabledLoginCount} 个平台`
        }
      ]
    }
  },
  created() {
    this.height = screenHeight(210)
    this.info()
  },
  methods: {
    // 信息
    info() {
      thirdInfo().then((res) => {
        this.model = res.data
      })
    },
    // 刷新
    refresh() {
      this.loading = true
      thirdInfo()
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
          thirdEdit(this.model)
            .then((res) => {
              this.loading = false
              ElMessage.success(res.msg)
              this.$nextTick(() => {
                ElMessage.info('建议立刻回 H5 登录页或第三方账号页验证授权登录入口和绑定结果')
              })
            })
            .catch(() => {
              this.loading = false
            })
        }
      })
    },
    goToRoute(path) {
      this.$router.push({
        path,
        query: {
          from: 'member-setting-third'
        }
      })
    },
    openH5Login() {
      const url = `${window.location.origin}/app/pages/my/login`
      window.open(url, '_blank', 'noopener')
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
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 16px;
  padding: 14px 16px;
  border: 1px solid #dbe7f5;
  border-radius: 14px;
  background: #fff;
}

.setting-followup-strip__title {
  font-size: 14px;
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
  align-items: center;
  flex-wrap: wrap;
  gap: 2px;
}

@media (max-width: 960px) {
  .setting-guide-panel__header,
  .setting-followup-strip {
    flex-direction: column;
  }

  .setting-guide-panel__badge {
    min-width: auto;
    width: 100%;
  }

  .setting-guide-panel__grid {
    grid-template-columns: 1fr;
  }
}
</style>
