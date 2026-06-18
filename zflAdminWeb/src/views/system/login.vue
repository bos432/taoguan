<template>
  <div
    class="login-container"
    :style="{
      backgroundImage: 'url(' + login_bg_url + ')',
      backgroundColor: login_bg_color
    }"
  >
    <div class="login-shell">
      <div class="login-shell__aside">
        <div class="login-shell__eyebrow">Admin Next</div>
        <div class="login-shell__title">运营后台登录</div>
        <div class="login-shell__desc">
          统一进入商家、商品、会员、业务设置与系统管理模块，登录后会按当前权限自动加载对应菜单。
        </div>
        <div v-if="entryContextVisible" class="login-shell__entry">
          <div class="login-shell__entry-badge">{{ entryContextBadge }}</div>
          <div class="login-shell__entry-title">{{ entryContextTitle }}</div>
          <div class="login-shell__entry-desc">{{ entryContextDesc }}</div>
        </div>
        <div class="login-shell__highlights">
          <div class="login-shell__highlight">
            <span>工作台</span>
            <strong>统一入口</strong>
          </div>
          <div class="login-shell__highlight">
            <span>菜单权限</span>
            <strong>按账号加载</strong>
          </div>
          <div class="login-shell__highlight">
            <span>使用建议</span>
            <strong>先登录再核对模块</strong>
          </div>
        </div>
      </div>

      <div class="login-shell__main">
        <el-row class="mt-4 login-toolbar">
          <el-col :span="24" class="login-toolbar__actions">
            <lang-select class="cursor-pointer mr-4 mt-[3px]" />
            <theme-select class="cursor-pointer" />
          </el-col>
        </el-row>
        <el-form ref="ref" class="login-form" :model="model" :rules="rules">
          <div class="login-logo">
            <el-image v-if="logo_url" :src="logo_url" style="height: 108px">
              <template #error>
                <svg-icon icon-class="picture" />
              </template>
            </el-image>
            <div v-else style="height: 115px"></div>
          </div>
          <div class="login-title">
            <h3 class="login-title-name">{{ system_name }}</h3>
            <div class="login-title-sub">请使用后台账号登录管理端</div>
          </div>
          <div v-if="postLoginHint" class="login-entry-tip">
            {{ postLoginHint }}
          </div>
          <el-form-item prop="username">
            <el-input v-model="model.username" :placeholder="$t('login.username')" clearable>
              <template #prefix>
                <svg-icon icon-class="user" />
              </template>
            </el-input>
          </el-form-item>
          <el-form-item prop="password">
            <el-input
              v-model="model.password"
              type="password"
              :placeholder="$t('login.password')"
              clearable
              show-password
            >
              <template #prefix>
                <svg-icon icon-class="lock" />
              </template>
            </el-input>
          </el-form-item>
          <el-form-item v-if="captcha_switch && captcha_src" prop="captcha_code">
            <el-col :span="13">
              <el-input
                v-model="model.captcha_code"
                :placeholder="$t('login.captchaCode')"
                autocomplete="off"
                clearable
              >
                <template #prefix>
                  <svg-icon icon-class="picture" />
                </template>
              </el-input>
            </el-col>
            <el-col :span="11">
              <el-image
                class="login-captcha"
                :style="{ height: captchaHeight }"
                :src="captcha_src"
                fit="fill"
                :alt="$t('login.captchaCode')"
                :title="$t('login.Click to refresh the captcha code')"
                @click="captcha"
              />
            </el-col>
          </el-form-item>
          <aj-captcha
            v-if="captcha_switch && captcha_mode == 2"
            ref="ajcaptcha"
            mode="pop"
            :captcha-type="captcha_type"
            @success="ajcaptchaSuccess"
          />
          <el-button
            v-if="captcha_switch && captcha_mode == 2"
            :loading="loading"
            type="primary"
            class="login-bottom"
            @click="ajcaptchaShow"
          >
            {{ $t('login.login') }}
          </el-button>
          <el-button
            v-else
            :loading="loading"
            type="primary"
            class="login-bottom"
            @click.prevent="handleLogin"
          >
            {{ $t('login.login') }}
          </el-button>
        </el-form>
      </div>
    </div>
  </div>
</template>

<script>
import LangSelect from '@/components/LangSelect/index.vue'
import ThemeSelect from '@/components/ThemeSelect/index.vue'
import AjCaptcha from '@/components/AjCaptcha/index.vue'
import { captcha, setting } from '@/api/system/login'
import { useAppStoreHook } from '@/store/modules/app'
import { useSettingsStoreHook } from '@/store/modules/settings'
import { useUserStoreHook } from '@/store/modules/user'
import { delNotice } from '@/utils/settings'

export default {
  name: 'SystemLogin',
  components: { LangSelect, ThemeSelect, AjCaptcha },
  data() {
    return {
      name: '登录',
      loading: false,
      redirect: undefined,
      otherQuery: {},
      captcha_switch: 0,
      captcha_mode: 1,
      captcha_type: 'blockPuzzle',
      captcha_src: '',
      login_bg_url: '',
      login_bg_color: '',
      logo_url: '',
      system_name: 'yylAdmin',
      model: {
        username: '',
        password: '',
        captcha_id: '',
        captcha_code: '',
        ajcaptcha: {}
      }
    }
  },
  computed: {
    captchaHeight() {
      const appStore = useAppStoreHook()
      if (appStore.size == 'large') {
        return '40px'
      } else if (appStore.size == 'small') {
        return '24px'
      }
      return '32px'
    },
    rules() {
      return {
        username: [
          { required: true, message: this.$t('login.Please enter username'), trigger: 'blur' }
        ],
        password: [
          { required: true, message: this.$t('login.Please enter password'), trigger: 'blur' }
        ],
        captcha_code: [
          { required: true, message: this.$t('login.Please enter captcha code'), trigger: 'blur' }
        ]
      }
    },
    entrySource() {
      return String(this.$route.query?.from || '')
    },
    entryContextVisible() {
      return Boolean(this.entrySource || this.redirect)
    },
    entryContextBadge() {
      if (this.entrySource === 'system-logout') {
        return '已安全退出'
      }
      if (this.entrySource === 'system-setting') {
        return '系统配置复核'
      }
      if (this.entrySource === 'legacy-module') {
        return '旧页回流'
      }
      return '登录承接'
    },
    entryContextTitle() {
      if (this.entrySource === 'system-logout') {
        return '你刚刚已经退出后台，会话已清理完成'
      }
      if (this.entrySource === 'system-setting') {
        return '你是从系统设置或权限调整链路回到登录页'
      }
      if (this.entrySource === 'legacy-module') {
        return '你是从旧后台承接链路回到登录入口'
      }
      if (this.redirect) {
        return '登录后会继续回到刚才要访问的后台页面'
      }
      return '请先完成登录，再继续后台操作'
    },
    entryContextDesc() {
      if (this.entrySource === 'system-logout') {
        return '适合切换账号、复核角色权限，或确认菜单是否按最新授权重新加载。'
      }
      if (this.entrySource === 'system-setting') {
        return '这通常发生在你刚改完登录、安全、菜单或接口配置，系统需要重新建立一次干净会话。'
      }
      if (this.entrySource === 'legacy-module') {
        return '旧页链路回到这里通常是为了重新建立权限或避免历史缓存影响，登录后建议先回新后台页继续操作。'
      }
      if (this.redirect) {
        return '当前存在待回流页面，登录成功后系统会自动带你回去，不需要重新找菜单。'
      }
      return '统一从这里进入 Admin Next，后续会按权限自动加载菜单和首页。'
    },
    postLoginHint() {
      if (this.redirect) {
        return `登录成功后将自动返回 ${this.resolveRedirectLabel(this.redirect)}。`
      }
      if (this.entrySource === 'system-logout') {
        return '重新登录后建议先看控制台、系统设置或操作日志，确认最新权限与配置是否生效。'
      }
      if (this.entrySource === 'legacy-module') {
        return '重新登录后建议优先回新页，不要先扎进旧后台 iframe。'
      }
      return ''
    }
  },
  watch: {
    $route: {
      handler(route) {
        const query = route.query
        if (query) {
          this.redirect = query.redirect
          this.otherQuery = this.getOtherQuery(query)
        }
      },
      immediate: true
    }
  },
  created() {
    this.setting()
  },
  methods: {
    // 验证码
    captcha() {
      captcha().then((res) => {
        this.captchaData(res.data)
      })
    },
    captchaData(data) {
      this.model.captcha_id = ''
      this.model.captcha_code = ''
      if (data.captcha_switch) {
        if (data.captcha_mode === 1) {
          this.captcha_src = data.captcha_src
          this.model.captcha_id = data.captcha_id
        }
      }
      this.captcha_switch = data.captcha_switch
      this.captcha_mode = data.captcha_mode
      if (data.captcha_type === 1) {
        this.captcha_type = 'blockPuzzle'
      } else {
        this.captcha_type = 'clickWord'
      }
    },
    ajcaptchaSuccess(params) {
      this.model.ajcaptcha = params
      this.handleLogin()
    },
    ajcaptchaShow() {
      this.$refs['ref'].validate((valid) => {
        if (valid) {
          this.$refs.ajcaptcha.show()
        } else {
          return false
        }
      })
    },
    // 设置
    setting() {
      this.model.captcha_id = ''
      this.model.captcha_code = ''
      setting()
        .then((res) => {
          delNotice()
          const data = res.data
          const settingsStore = useSettingsStoreHook()
          this.captchaData(data)
          this.login_bg_url = data.login_bg_url
          this.login_bg_color = data.login_bg_color
          this.logo_url = data.logo_url
          this.system_name = data.system_name
          settingsStore.changeSetting({ key: 'loginBgUrl', value: data.login_bg_url })
          settingsStore.changeSetting({ key: 'loginBgColor', value: data.login_bg_color })
          settingsStore.changeSetting({ key: 'logoUrl', value: data.logo_url })
          settingsStore.changeSetting({ key: 'systemName', value: data.system_name })
          settingsStore.changeSetting({ key: 'pageTitle', value: data.page_title })
          settingsStore.changeSetting({ key: 'pageLimit', value: data.page_limit })
          settingsStore.changeSetting({ key: 'notice', value: 0 })
        })
        .catch(() => {})
    },
    // 登录
    handleLogin() {
      this.$refs['ref'].validate((valid) => {
        if (valid) {
          this.loading = true
          const userStore = useUserStoreHook()
          userStore
            .login(this.model)
            .then(() => {
              this.$router
                .push({
                  path: this.redirect || '/',
                  query: this.otherQuery
                })
                .catch(() => {
                  this.loading = false
                })
            })
            .catch(() => {
              this.loading = false
              if (this.captcha_switch && this.captcha_mode === 2) {
                this.$refs.ajcaptcha.refresh()
              } else {
                this.captcha()
              }
            })
        } else {
          return false
        }
      })
    },
    getOtherQuery(query) {
      return Object.keys(query).reduce((acc, cur) => {
        if (cur !== 'redirect') {
          acc[cur] = query[cur]
        }
        return acc
      }, {})
    },
    resolveRedirectLabel(path) {
      const redirectPath = String(path || '')
      if (!redirectPath) return '控制台'
      if (redirectPath.startsWith('/system/setting')) return '系统设置中心'
      if (redirectPath.startsWith('/system/user-log')) return '操作日志'
      if (redirectPath.startsWith('/system/user-center')) return '个人中心'
      if (redirectPath.startsWith('/member/setting')) return '会员设置中心'
      if (redirectPath.startsWith('/analytics')) return '平台分析'
      if (redirectPath.startsWith('/report/internal-takeover')) return '内部接盘对账'
      if (redirectPath.startsWith('/merchant/merchant')) return '商家管理'
      if (redirectPath.startsWith('/goods/')) return '商品管理'
      if (redirectPath.startsWith('/legacy/')) return '旧模块承接页'
      if (redirectPath === '/' || redirectPath.startsWith('/dashboard')) return '控制台'
      return '刚才访问的页面'
    }
  }
}
</script>

<style lang="scss" scoped>
.login-container {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  min-height: 100%;
  padding: 32px;
  overflow: hidden;
  background-color: #2d3a4b;
  background-size: 100% 100%;
  background-position: center center;

  .login-shell {
    display: grid;
    grid-template-columns: minmax(320px, 420px) minmax(360px, 480px);
    align-items: stretch;
    width: min(1120px, 100%);
    border: 1px solid rgba(255, 255, 255, 0.18);
    border-radius: 28px;
    overflow: hidden;
    background: rgba(15, 23, 42, 0.24);
    backdrop-filter: blur(18px);
    box-shadow: 0 24px 70px rgba(15, 23, 42, 0.28);
  }

  .login-shell__aside {
    padding: 56px 42px;
    color: #e2e8f0;
    background: linear-gradient(180deg, rgba(15, 23, 42, 0.78), rgba(30, 41, 59, 0.72));
  }

  .login-shell__eyebrow {
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: #93c5fd;
  }

  .login-shell__title {
    margin-top: 10px;
    font-size: 32px;
    font-weight: 700;
    color: #f8fafc;
  }

  .login-shell__desc {
    margin-top: 14px;
    line-height: 1.8;
    font-size: 14px;
    color: rgba(226, 232, 240, 0.88);
  }

  .login-shell__entry {
    margin-top: 22px;
    padding: 16px 18px;
    border: 1px solid rgba(147, 197, 253, 0.22);
    border-radius: 18px;
    background: rgba(59, 130, 246, 0.12);
  }

  .login-shell__entry-badge {
    font-size: 12px;
    font-weight: 700;
    color: #bfdbfe;
  }

  .login-shell__entry-title {
    margin-top: 8px;
    font-size: 18px;
    font-weight: 700;
    color: #f8fafc;
  }

  .login-shell__entry-desc {
    margin-top: 8px;
    line-height: 1.8;
    font-size: 13px;
    color: rgba(226, 232, 240, 0.88);
  }

  .login-shell__highlights {
    display: grid;
    gap: 12px;
    margin-top: 26px;
  }

  .login-shell__highlight {
    padding: 14px 16px;
    border: 1px solid rgba(148, 163, 184, 0.2);
    border-radius: 16px;
    background: rgba(255, 255, 255, 0.06);

    span {
      display: block;
      margin-bottom: 6px;
      font-size: 12px;
      color: rgba(191, 219, 254, 0.88);
    }

    strong {
      font-size: 16px;
      color: #f8fafc;
    }
  }

  .login-shell__main {
    padding: 22px 28px 30px;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(248, 250, 252, 0.94));
  }

  .login-toolbar {
    margin-bottom: 12px;
  }

  .login-toolbar__actions {
    display: flex;
    justify-content: flex-end;
    align-items: center;
  }

  .login-form {
    position: relative;
    padding: 34px 35px 0;
    margin: 0 auto;
    width: 100%;
    max-width: 100%;
    overflow: hidden;
  }

  .login-title {
    position: relative;

    .login-title-name {
      line-height: 30px;
      margin: 0px auto 22px auto;
      text-align: center;
      font-weight: bold;
      font-size: 26px;
      color: #0f172a;
    }
  }

  .login-title-sub {
    margin: -8px auto 22px;
    text-align: center;
    font-size: 13px;
    color: #64748b;
  }

  .login-entry-tip {
    margin: -4px 0 18px;
    padding: 12px 14px;
    border: 1px solid rgba(59, 130, 246, 0.12);
    border-radius: 14px;
    background: rgba(239, 246, 255, 0.96);
    font-size: 13px;
    line-height: 1.7;
    color: #475569;
  }

  .login-logo {
    margin-bottom: 22px;
    text-align: center;
  }

  .login-captcha {
    float: right;
    width: 90%;
    border-radius: 4px;
    vertical-align: middle;
  }

  .login-bottom {
    width: 100%;
    margin-bottom: 30px;
  }
}

@media (max-width: 900px) {
  .login-container {
    padding: 16px;

    .login-shell {
      grid-template-columns: 1fr;
    }

    .login-shell__aside {
      padding: 30px 24px 22px;
    }

    .login-shell__title {
      font-size: 26px;
    }

    .login-shell__main {
      padding: 16px 16px 24px;
    }

    .login-form {
      padding: 16px 8px 0;
    }
  }
}
</style>
