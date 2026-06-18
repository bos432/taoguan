<template>
  <view class="register">
    <bar-title bgColor="bg-white" isBack :bgColor="'bg-grey-register'">
      <block slot="content"></block>
    </bar-title>
    <view class="content">
      <view class="header">
        <image :src="logoImage"></image>
      </view>

      <view class="login-tabs">
        <view
          v-for="item in loginModes"
          :key="item.code"
          class="login-tab"
          :class="loginMode === item.code ? 'is-active' : ''"
          @tap="switchLoginMode(item.code)"
        >
          <text>{{ item.label }}</text>
        </view>
      </view>

      <view class="mode-tip">{{ currentModeTip }}</view>

      <view
        class="env-hint-card"
        :class="currentEnvInfo.is_prod ? 'is-prod' : 'is-test'"
      >
        <view class="env-hint-head">
          <text class="env-hint-badge">{{ displayEnvLabel }}</text>
          <text
            v-if="allowEnvSwitch"
            class="env-hint-action"
            @tap="switchEnvProfile"
            >切换环境</text
          >
        </view>
        <view class="env-hint-desc">{{ displayEnvDesc }}</view>
        <view class="env-hint-meta">
          <text class="env-hint-meta__item"
            >当前入口：{{ currentModeLabel }}</text
          >
          <text class="env-hint-meta__item"
            >协议状态：{{ agreementStatusText }}</text
          >
          <text class="env-hint-meta__item">{{ envIsolationStatusText }}</text>
          <text class="env-hint-meta__item">{{ envReleaseStageText }}</text>
          <text class="env-hint-meta__item">{{ envProfileSourceText }}</text>
        </view>
        <view class="env-hint-route">{{ loginRedirectHint }}</view>
        <view class="env-hint-note">{{ loginEntryWarning }}</view>
        <view class="env-hint-note env-hint-note--strong">{{
          envReleaseHint
        }}</view>
        <view v-if="envRiskList.length" class="env-risk-list">
          <view v-for="item in envRiskList" :key="item" class="env-risk-item">
            {{ item }}
          </view>
        </view>
        <view class="env-profile-board">
          <view class="env-profile-board__title">环境就绪看板</view>
          <view class="env-profile-list">
            <view
              v-for="item in profileReadinessList"
              :key="item.key"
              class="env-profile-item"
              :class="item.key === currentEnvInfo.key ? 'is-current' : ''"
            >
              <view class="env-profile-item__head">
                <text class="env-profile-item__name">{{ item.label }}</text>
                <text
                  class="env-profile-item__status"
                  :class="'is-' + ((item.status_text || '').toLowerCase())"
                >
                  {{ item.status_text }}
                </text>
              </view>
              <view class="env-profile-item__desc">{{ item.short_hint }}</view>
            </view>
          </view>
        </view>
        <view class="env-hint-url">{{ currentEnvInfo.api_root_url }}</view>
      </view>

      <view v-if="loginMode === 'code'" class="main">
        <view class="plain-input-wrap">
          <input
            v-model="phoneData"
            class="plain-input"
            type="number"
            maxlength="11"
            :placeholder="text.phonePlaceholder"
            @input="onPhoneInput"
          />
        </view>
        <view class="plain-input-wrap plain-input-wrap--code">
          <view class="plain-input-row">
            <input
              v-model="verCode"
              class="plain-input plain-input--code"
              type="number"
              maxlength="6"
              :placeholder="text.codePlaceholder"
              @input="onVerCodeInput"
            />
            <view
              class="code-action"
              :class="{ disabled: codeSecond > 0 }"
              @tap="getVerCode"
            >
              {{ codeButtonText }}
            </view>
          </view>
        </view>
        <wButton
          class="wbutton"
          :text="text.codeLoginButton"
          :rotate="isRotate"
          @click="startCodeLogin"
          bgColor="linear-gradient(45deg, #0081ff,#1cbbb4)"
        ></wButton>
      </view>

      <view v-else-if="loginMode === 'wechat'" class="main wechat-panel">
        <view class="wechat-hero">
          <text class="cuIcon-weixin wechat-icon"></text>
          <view class="wechat-title">{{ text.wechatTitle }}</view>
          <view class="wechat-desc">{{ text.wechatDesc }}</view>
        </view>
        <!-- #ifdef MP-WEIXIN -->
        <button
          class="wechat-login-btn"
          open-type="getPhoneNumber"
          @getphonenumber="onGetPhoneNumber"
        >
          {{ text.wechatButton }}
        </button>
        <!-- #endif -->
        <!-- #ifndef MP-WEIXIN -->
        <view class="wechat-fallback">{{ text.wechatFallback }}</view>
        <wButton
          class="wbutton"
          :text="text.goCodeMode"
          :rotate="false"
          @click="switchLoginMode('code')"
          bgColor="linear-gradient(45deg, #0081ff,#1cbbb4)"
        ></wButton>
        <!-- #endif -->
      </view>

      <view v-else class="main">
        <view class="plain-input-wrap">
          <input
            v-model="accountData"
            class="plain-input"
            type="text"
            :placeholder="text.accountPlaceholder"
            @input="onAccountInput"
          />
        </view>
        <view class="plain-input-wrap">
          <input
            v-model="passwordData"
            class="plain-input"
            password
            :placeholder="text.passwordPlaceholder"
            @input="onPasswordInput"
          />
        </view>
        <wButton
          class="wbutton"
          :text="text.accountLoginButton"
          :rotate="isRotate"
          @click="startAccountLogin"
          bgColor="linear-gradient(45deg, #0081ff,#1cbbb4)"
        ></wButton>
      </view>

      <view class="agreement-footer">
        <view class="agreement-check" @tap="isShowAgree">
          <text
            class="cuIcon"
            :class="
              showAgree ? 'cuIcon-radiobox text-blue' : 'cuIcon-round text-gray'
            "
          ></text>
          <text class="agreement-check-text">{{ text.readAgree }}</text>
        </view>
        <view class="agreement-links">
          <text class="agreement-link" @tap="openAccordCenter">{{
            text.accordCenter
          }}</text>
          <text class="agreement-link" @tap="openAccord('user_agreement')">{{
            text.userAgreement
          }}</text>
          <text class="agreement-link" @tap="openAccord('privacy_policy')">{{
            text.privacyPolicy
          }}</text>
        </view>
      </view>
    </view>
  </view>
</template>

<script>
import wButton from "../../components/watch-login/watch-button.vue";
import barTitle from "@/components/zaiui-common/basics/bar-title";
import cache from "@/utils/cache";
import api from "@/api";
import store from "@/store/common";
import { ensureAcceptAccords } from "@/utils/accord-accept.js";
import { clearCurrentMerchantIdentity } from "@/utils/merchant-identity.js";
import { resolveImageUrl as resolveSafeImageUrl } from "@/utils/resource.js";
import {
  consumeLoginRedirect,
  getLoginRedirect,
} from "@/utils/login-redirect.js";
import {
  getAvailableProfileList,
  getCurrentEnvInfo,
  getEnvIsolationHealth,
  getProfileReadinessList,
  getEnvSwitchGuard,
  setEnvProfile,
  unlockProdProfileSwitch,
} from "@/utils/env-runtime.js";
import {
  buildProdUnlockText,
  getEnvReleaseHint,
  getEnvReleaseStageText,
} from "@/utils/env-risk.js";

const TEXT = {
  phonePlaceholder: "手机号",
  codePlaceholder: "验证码",
  accountPlaceholder: "账号 / 手机号 / 邮箱",
  passwordPlaceholder: "密码",
  codeLoginButton: "验证码登录",
  accountLoginButton: "账号密码登录",
  readAgree: "我已阅读并同意",
  accordCenter: "《协议中心》",
  userAgreement: "《用户协议》",
  privacyPolicy: "《隐私政策》",
  invalidPhone: "手机号不正确",
  invalidCode: "验证码不正确",
  invalidAccount: "请输入账号",
  invalidPassword: "请输入密码",
  needAgree: "请先同意用户协议和隐私政策",
  needAgreeForCode: "请先勾选用户协议和隐私政策后再获取验证码",
  loginSuccess: "登录成功",
  retryHint: "协议记录稍后自动补记，已为您完成登录",
  agreeSyncFailed: "协议记录同步失败，请重新登录后再继续",
  tabs: {
    code: "验证码登录",
    wechat: "微信授权",
    account: "账号密码",
  },
  tips: {
    code: "使用手机号验证码快速登录",
    wechat: "使用微信手机号一键授权登录",
    account: "适合已设置账号密码的用户登录",
  },
  wechatTitle: "微信一键授权",
  wechatDesc: "授权手机号后自动完成登录并同步协议记录",
  wechatButton: "微信手机号一键登录",
  wechatFallback:
    "当前环境不支持微信手机号授权，请切换到小程序或改用验证码登录",
  goCodeMode: "切换到验证码登录",
  rejectWechat: "您已拒绝微信授权",
};

export default {
  data() {
    return {
      text: TEXT,
      logoImage: "/static/images/avatar/1.jpg",
      loginMode: "code",
      loginModes: [
        { code: "code", label: TEXT.tabs.code },
        { code: "wechat", label: TEXT.tabs.wechat },
        { code: "account", label: TEXT.tabs.account },
      ],
      phoneData: "",
      verCode: "",
      accountData: "",
      passwordData: "",
      codeSecond: 0,
      codeTimer: null,
      showAgree: false,
      isRotate: false,
      loginRedirectUrl: "",
    };
  },
  computed: {
    currentModeTip() {
      return this.text.tips[this.loginMode] || "";
    },
    codeButtonText() {
      return this.codeSecond > 0 ? `${this.codeSecond}s` : "获取验证码";
    },
    currentEnvInfo() {
      return getCurrentEnvInfo();
    },
    displayEnvLabel() {
      return this.currentEnvInfo.label || "未配置";
    },
    displayEnvDesc() {
      return this.currentEnvInfo.is_prod
        ? "当前为正式环境，请勿用于开发联调和测试写入。"
        : "当前为非正式环境，可用于联调、验收和灰度验证。";
    },
    currentModeLabel() {
      return this.text.tabs[this.loginMode] || "登录";
    },
    agreementStatusText() {
      return this.showAgree ? "已勾选，可继续提交" : "未勾选，提交会被拦截";
    },
    loginEntryWarning() {
      return this.showAgree
        ? "当前登录入口已解锁，提交后会按当前环境执行登录。"
        : "协议默认不勾选，验证码登录、微信授权和账号密码登录都会被拦截。";
    },
    loginRedirectHint() {
      return this.loginRedirectUrl
        ? `登录成功后将返回：${decodeURIComponent(this.loginRedirectUrl)}`
        : "登录成功后将进入“我的”主页。";
    },
    allowEnvSwitch() {
      return getAvailableProfileList().length > 1;
    },
    envIsolationHealth() {
      return getEnvIsolationHealth();
    },
    envIsolationStatusText() {
      return this.envIsolationHealth.is_isolated_ready
        ? "隔离状态：已就绪"
        : "隔离状态：待处理";
    },
    envReleaseStageText() {
      return getEnvReleaseStageText(this.envIsolationHealth);
    },
    envReleaseHint() {
      return getEnvReleaseHint(this.envIsolationHealth);
    },
    envRiskList() {
      return this.envIsolationHealth.warnings || [];
    },
    envProfileSourceText() {
      return this.currentEnvInfo.profile_overrides_enabled
        ? "私有配置：已启用"
        : "私有配置：未启用";
    },
    profileReadinessList() {
      return getProfileReadinessList();
    },
  },
  components: {
    wButton,
    barTitle,
  },
  beforeDestroy() {
    this.clearCodeTimer();
  },
  onUnload() {
    this.clearCodeTimer();
  },
  onShow() {
    this.showAgree = false;
  },
  onLoad(options) {
    this.showAgree = false;
    if (this.$store.state.setting && this.$store.state.setting.member) {
      this.logoImage = this.$store.state.setting.member.default_avatar_url;
    } else if (cache.get("setting")) {
      const data = cache.get("setting");
      this.logoImage =
        (data.member && data.member.default_avatar_url) || this.logoImage;
    }
    if (cache.get("phone")) {
      this.phoneData = cache.get("phone");
      this.accountData = cache.get("phone");
    }
    if (cache.get("account")) {
      this.accountData = cache.get("account");
    }
    const redirectUrl = this.normalizeText(
      (options && options.redirect) || getLoginRedirect(""),
    );
    this.loginRedirectUrl = redirectUrl;
    this.logoImage = this.resolveImageUrl(
      this.logoImage,
      "/static/images/avatar/1.jpg",
    );
  },
  methods: {
    resolveImageUrl(url, fallback = "/static/images/avatar/1.jpg") {
      return resolveSafeImageUrl(url, fallback);
    },
    normalizeText(value) {
      if (value === undefined || value === null) {
        return "";
      }
      return String(value);
    },
    normalizePhone(value) {
      return this.normalizeText(value).replace(/\D/g, "").slice(0, 11);
    },
    normalizeCode(value) {
      return this.normalizeText(value).replace(/\D/g, "").slice(0, 6);
    },
    onPhoneInput(event) {
      this.phoneData = this.normalizePhone(event.detail && event.detail.value);
    },
    onVerCodeInput(event) {
      this.verCode = this.normalizeCode(event.detail && event.detail.value);
    },
    onAccountInput(event) {
      this.accountData = this.normalizeText(event.detail && event.detail.value);
    },
    onPasswordInput(event) {
      this.passwordData = this.normalizeText(
        event.detail && event.detail.value,
      );
    },
    clearCodeTimer() {
      if (this.codeTimer) {
        clearInterval(this.codeTimer);
        this.codeTimer = null;
      }
      this.codeSecond = 0;
    },
    startCodeTimer() {
      this.clearCodeTimer();
      this.codeSecond = 60;
      this.codeTimer = setInterval(() => {
        if (this.codeSecond <= 1) {
          this.clearCodeTimer();
          return;
        }
        this.codeSecond -= 1;
      }, 1000);
    },
    switchLoginMode(mode) {
      this.loginMode = mode;
    },
    switchEnvProfile() {
      const profiles = getProfileReadinessList();
      const labels = profiles.map(
        (item) => `${item.label || item.key} · ${item.status_text}`,
      );
      uni.showActionSheet({
        itemList: labels,
        success: (res) => {
          const selected = profiles[res.tapIndex];
          if (!selected || selected.key === this.currentEnvInfo.key) {
            return;
          }
          const applySwitch = () => {
            const envInfo = setEnvProfile(selected.key);
            uni.showToast({
              icon: "none",
              title: `已切换到${envInfo.label}`,
            });
          };

          if (selected.key === "prod") {
            uni.showModal({
              title: "切换正式环境",
              content: buildProdUnlockText(this.currentEnvInfo, {
                host: selected.api_root_url || selected.base_root_url,
              }),
              success: (modalRes) => {
                if (modalRes.confirm) {
                  unlockProdProfileSwitch();
                  const guard = getEnvSwitchGuard(selected.key);
                  if (!guard.allowed) {
                    uni.showToast({
                      icon: "none",
                      title: guard.message,
                    });
                    return;
                  }
                  const envInfo = setEnvProfile(selected.key, {
                    force_prod: true,
                  });
                  uni.showToast({
                    icon: "none",
                    title: `已切换到${envInfo.label}`,
                  });
                }
              },
            });
            return;
          }

          if (selected.has_example_host) {
            uni.showModal({
              title: `切换到${selected.label}`,
              content: `${selected.label} 当前仍是占位域名。\n目标地址：${selected.api_root_url || selected.base_root_url || "未配置"}\n该环境只能用于页面结构和交互核对，不能做真实联调、灰度提交或写操作验收。`,
              success: (modalRes) => {
                if (modalRes.confirm) {
                  applySwitch();
                }
              },
            });
            return;
          }

          applySwitch();
        },
      });
    },
    isShowAgree() {
      this.showAgree = !this.showAgree;
    },
    openAccord(accordId) {
      uni.navigateTo({
        url: "/pages/system/accord?accord_id=" + accordId,
      });
    },
    openAccordCenter() {
      uni.navigateTo({
        url: "/pages/system/accord-center",
      });
    },
    consumeLoginRedirectUrl() {
      const redirectUrl = this.normalizeText(
        this.loginRedirectUrl || consumeLoginRedirect(""),
      );
      this.loginRedirectUrl = "";
      return redirectUrl;
    },
    redirectAfterLogin() {
      const redirectUrl = this.consumeLoginRedirectUrl();
      if (!redirectUrl) {
        uni.reLaunch({
          url: "/pages/app/my",
        });
        return;
      }
      if (redirectUrl.indexOf("/pages/app/") === 0) {
        uni.switchTab({
          url: redirectUrl,
        });
        return;
      }
      uni.reLaunch({
        url: redirectUrl,
      });
    },
    ensureAgreeAccepted(actionLabel = "登录") {
      if (!this.showAgree) {
        uni.showToast({
          icon: "none",
          position: "bottom",
          title:
            actionLabel === "获取验证码"
              ? this.text.needAgreeForCode
              : this.text.needAgree,
        });
        return false;
      }
      return true;
    },
    clearLoginRuntimeCache() {
      cache.remove("userInfo");
      cache.remove("token");
    },
    getActionErrorMessage(error, fallback = "登录失败，请稍后重试") {
      const message = this.normalizeText(
        error && (error.msg || error.message || error.errMsg),
      );
      return message || fallback;
    },
    finishLogin(resData, extraCache = {}) {
      cache.set("userInfo", resData, 1296000);
      cache.set("token", resData.ApiToken, 1296000);
      if (extraCache.phone) {
        cache.set("phone", extraCache.phone);
      }
      if (extraCache.account) {
        cache.set("account", extraCache.account);
      }
      clearCurrentMerchantIdentity();

      return ensureAcceptAccords(
        {
          scene: "login",
          accord_uniques: ["user_agreement", "privacy_policy"],
        },
        {
          toast: true,
          message: this.text.agreeSyncFailed,
        },
      )
        .then(() => {
          store.commit("login");
          uni.showToast({
            icon: "none",
            position: "bottom",
            title: this.text.loginSuccess,
          });
          this.redirectAfterLogin();
        })
        .catch((error) => {
          this.clearLoginRuntimeCache();
          return Promise.reject(error);
        });
    },
    getVerCode() {
      const phone = this.normalizePhone(this.phoneData);
      this.phoneData = phone;
      if (this.codeSecond > 0) {
        return false;
      }
      if (!this.ensureAgreeAccepted("获取验证码")) {
        return false;
      }
      if (phone.length !== 11) {
        uni.showToast({
          icon: "none",
          position: "bottom",
          title: this.text.invalidPhone,
        });
        return false;
      }
      api.phoneCaptcha({ phone }).then(() => {
        this.startCodeTimer();
      });
    },
    startCodeLogin() {
      if (this.isRotate) {
        return false;
      }
      if (!this.ensureAgreeAccepted()) {
        return false;
      }
      const phone = this.normalizePhone(this.phoneData);
      const captchaCode = this.normalizeCode(this.verCode);
      this.phoneData = phone;
      this.verCode = captchaCode;
      if (phone.length !== 11) {
        uni.showToast({
          icon: "none",
          position: "bottom",
          title: this.text.invalidPhone,
        });
        return false;
      }
      if (captchaCode.length !== 6) {
        uni.showToast({
          icon: "none",
          position: "bottom",
          title: this.text.invalidCode,
        });
        return false;
      }

      this.isRotate = true;
      api
        .phoneLogin({ phone, captcha_code: captchaCode })
        .then((res) => this.finishLogin(res.data, { phone, account: phone }))
        .catch((error) => {
          if (error && error.accord_accept_failed) {
            return;
          }
          uni.showToast({
            icon: "none",
            position: "bottom",
            title: this.getActionErrorMessage(error),
          });
        })
        .finally(() => {
          this.isRotate = false;
        });
    },
    startAccountLogin() {
      if (this.isRotate) {
        return false;
      }
      if (!this.ensureAgreeAccepted()) {
        return false;
      }
      const account = this.normalizeText(this.accountData).trim();
      const password = this.normalizeText(this.passwordData);
      this.accountData = account;
      this.passwordData = password;
      if (!account) {
        uni.showToast({
          icon: "none",
          position: "bottom",
          title: this.text.invalidAccount,
        });
        return false;
      }
      if (!password) {
        uni.showToast({
          icon: "none",
          position: "bottom",
          title: this.text.invalidPassword,
        });
        return false;
      }

      this.isRotate = true;
      api
        .accountLogin({
          account,
          password,
          acc_type: "",
          captcha_id: "",
          captcha_code: "",
          ajcaptcha: null,
        })
        .then((res) => this.finishLogin(res.data, { account }))
        .catch((error) => {
          if (error && error.accord_accept_failed) {
            return;
          }
          uni.showToast({
            icon: "none",
            position: "bottom",
            title: this.getActionErrorMessage(error),
          });
        })
        .finally(() => {
          this.isRotate = false;
        });
    },
    getMiniappLoginCode() {
      return new Promise((resolve, reject) => {
        const onSuccess = (result) => {
          const code = this.normalizeText(result && result.code).trim();
          if (!code) {
            reject(new Error("empty_code"));
            return;
          }
          resolve(code);
        };
        const onFail = (error) => {
          reject(error || new Error("login_failed"));
        };

        // #ifdef MP-WEIXIN
        if (typeof uni !== "undefined" && typeof uni.login === "function") {
          uni.login({
            provider: "weixin",
            success: onSuccess,
            fail: () => {
              if (typeof wx !== "undefined" && typeof wx.login === "function") {
                wx.login({
                  success: onSuccess,
                  fail: onFail,
                });
                return;
              }
              onFail(new Error("login_failed"));
            },
          });
          return;
        }
        if (typeof wx !== "undefined" && typeof wx.login === "function") {
          wx.login({
            success: onSuccess,
            fail: onFail,
          });
          return;
        }
        // #endif
        reject(new Error("login_failed"));
      });
    },
    onGetPhoneNumber(e) {
      if (!this.ensureAgreeAccepted()) {
        return false;
      }
      if (this.isRotate) {
        return false;
      }
      if (e.detail.errMsg !== "getPhoneNumber:ok") {
        uni.showToast({
          icon: "none",
          title: this.text.rejectWechat,
        });
        return false;
      }

      this.isRotate = true;
      // #ifdef MP-WEIXIN
      const phoneCode = this.normalizeText(e.detail.code).trim();
      if (!phoneCode) {
        this.isRotate = false;
        uni.showToast({
          icon: "none",
          title: "手机号授权凭证获取失败",
        });
        return false;
      }
      this.getMiniappLoginCode()
        .then((loginCode) =>
          api.miniappLogin({
            code: loginCode,
            phone_code: phoneCode,
            register: 1,
          }),
        )
        .then((res) =>
          this.finishLogin(res.data, {
            phone: res.data.phone || "",
            account: res.data.phone || "",
          }),
        )
        .catch((error) => {
          if (error && error.accord_accept_failed) {
            return;
          }
          uni.showToast({
            icon: "none",
            title: this.getActionErrorMessage(
              error,
              "微信登录凭证获取失败，请重试",
            ),
          });
        })
        .finally(() => {
          this.isRotate = false;
        });
      // #endif
      // #ifndef MP-WEIXIN
      this.isRotate = false;
      uni.showToast({
        icon: "none",
        title: this.text.wechatFallback,
      });
      // #endif
    },
  },
};
</script>

<style>
/* #ifdef APP-PLUS */
@import "../../static/colorui/main.css";
@import "../../static/colorui/icon.css";
@import "../../static/zaiui/style/app.scss";
/* #endif */
@import url("../../components/watch-login/css/icon.css");
@import url("../../static/zaiui/style/register.css");

.login-tabs {
  margin: 0 70rpx 22rpx;
  padding: 8rpx;
  display: flex;
  align-items: center;
  border-radius: 999rpx;
  background: rgba(255, 255, 255, 0.9);
  box-shadow: 0 12rpx 40rpx rgba(0, 0, 0, 0.06);
}

.login-tab {
  flex: 1;
  text-align: center;
  padding: 18rpx 0;
  border-radius: 999rpx;
  font-size: 24rpx;
  color: #64748b;
}

.login-tab.is-active {
  background: linear-gradient(45deg, #0081ff, #1cbbb4);
  color: #ffffff;
  font-weight: 600;
}

.mode-tip {
  margin: 0 78rpx 18rpx;
  text-align: center;
  font-size: 24rpx;
  color: #7b8a97;
}

.env-hint-card {
  margin: 0 60rpx 24rpx;
  padding: 20rpx 24rpx;
  border-radius: 24rpx;
  background: rgba(255, 255, 255, 0.92);
  box-shadow: 0 10rpx 24rpx rgba(15, 23, 42, 0.06);
}

.env-hint-card.is-test {
  border: 1rpx solid rgba(28, 187, 180, 0.24);
}

.env-hint-card.is-prod {
  border: 1rpx solid rgba(245, 108, 108, 0.26);
}

.env-hint-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16rpx;
}

.env-hint-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 42rpx;
  padding: 0 18rpx;
  border-radius: 999rpx;
  background: linear-gradient(45deg, #0081ff, #1cbbb4);
  color: #ffffff;
  font-size: 22rpx;
}

.env-hint-action {
  color: #2f6f97;
  font-size: 22rpx;
}

.env-hint-desc {
  margin-top: 12rpx;
  color: #5b6b7b;
  font-size: 22rpx;
  line-height: 1.7;
}

.env-hint-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 12rpx;
  margin-top: 12rpx;
}

.env-hint-meta__item {
  display: inline-flex;
  align-items: center;
  min-height: 40rpx;
  padding: 0 16rpx;
  border-radius: 999rpx;
  background: rgba(15, 23, 42, 0.06);
  color: #526274;
  font-size: 20rpx;
}

.env-hint-route {
  margin-top: 12rpx;
  padding: 14rpx 18rpx;
  border-radius: 16rpx;
  background: rgba(15, 23, 42, 0.04);
  color: #395078;
  font-size: 22rpx;
  line-height: 1.6;
  word-break: break-all;
}

.env-hint-note {
  margin-top: 12rpx;
  color: #6b7b8c;
  font-size: 22rpx;
  line-height: 1.7;
}

.env-hint-note--strong {
  padding: 14rpx 18rpx;
  border-radius: 16rpx;
  background: rgba(15, 23, 42, 0.04);
  color: #395078;
}

.env-hint-url {
  margin-top: 10rpx;
  color: #94a3b8;
  font-size: 20rpx;
  line-height: 1.6;
  word-break: break-all;
}

.env-profile-board {
  margin-top: 14rpx;
  padding: 14rpx 16rpx;
  border-radius: 18rpx;
  background: rgba(15, 23, 42, 0.04);
}

.env-profile-board__title {
  color: #526274;
  font-size: 22rpx;
  font-weight: 600;
}

.env-profile-list {
  display: flex;
  flex-direction: column;
  gap: 12rpx;
  margin-top: 12rpx;
}

.env-profile-item {
  padding: 14rpx 16rpx;
  border-radius: 16rpx;
  background: rgba(255, 255, 255, 0.9);
}

.env-profile-item.is-current {
  border: 1rpx solid rgba(0, 129, 255, 0.26);
}

.env-profile-item__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12rpx;
}

.env-profile-item__name {
  color: #23405d;
  font-size: 22rpx;
  font-weight: 600;
}

.env-profile-item__status {
  min-width: 88rpx;
  text-align: center;
  padding: 4rpx 14rpx;
  border-radius: 999rpx;
  font-size: 20rpx;
  color: #ffffff;
  background: linear-gradient(45deg, #0081ff, #1cbbb4);
}

.env-profile-item__status.is-hold {
  background: linear-gradient(45deg, #f59e0b, #f97316);
}

.env-profile-item__status.is-ready {
  background: linear-gradient(45deg, #ef4444, #f97316);
}

.env-profile-item__desc {
  margin-top: 8rpx;
  color: #6b7b8c;
  font-size: 20rpx;
  line-height: 1.6;
}

.env-risk-list {
  margin-top: 12rpx;
  padding: 12rpx 16rpx;
  border-radius: 16rpx;
  background: rgba(245, 108, 108, 0.08);
}

.env-risk-item {
  color: #b45309;
  font-size: 22rpx;
  line-height: 1.7;
}

.env-risk-item + .env-risk-item {
  margin-top: 8rpx;
}

.plain-input-wrap {
  margin-bottom: 24rpx;
  padding: 0 70rpx;
}

.plain-input-row {
  display: flex;
  align-items: center;
  gap: 20rpx;
  width: 100%;
  height: 88rpx;
  padding: 0 30rpx;
  border-radius: 44rpx;
  background: #ffffff;
  box-shadow: 0 8rpx 28rpx rgba(0, 0, 0, 0.04);
}

.plain-input {
  width: 100%;
  height: 88rpx;
  padding: 0 30rpx;
  border-radius: 44rpx;
  background: #ffffff;
  box-shadow: 0 8rpx 28rpx rgba(0, 0, 0, 0.04);
  font-size: 28rpx;
  color: #333333;
}

.plain-input-wrap--code .plain-input-row {
  padding-right: 18rpx;
}

.plain-input--code {
  flex: 1;
  padding: 0;
  box-shadow: none;
  background: transparent;
}

.code-action {
  flex-shrink: 0;
  min-width: 150rpx;
  text-align: center;
  font-size: 24rpx;
  color: #0f8fe8;
}

.code-action.disabled {
  color: #94a3b8;
}

.wechat-panel {
  padding: 0 70rpx;
}

.wechat-hero {
  padding: 34rpx 30rpx;
  border-radius: 28rpx;
  background: linear-gradient(
    135deg,
    rgba(0, 129, 255, 0.08),
    rgba(28, 187, 180, 0.12)
  );
  text-align: center;
}

.wechat-icon {
  font-size: 72rpx;
  color: #1aad19;
}

.wechat-title {
  margin-top: 12rpx;
  font-size: 32rpx;
  font-weight: 600;
  color: #17324d;
}

.wechat-desc {
  margin-top: 10rpx;
  font-size: 24rpx;
  line-height: 1.7;
  color: #6b7b8c;
}

.wechat-login-btn {
  margin-top: 38rpx;
  width: 100%;
  height: 88rpx;
  line-height: 88rpx;
  border-radius: 44rpx;
  border: 0;
  color: #ffffff;
  font-size: 30rpx;
  background: linear-gradient(45deg, #07c160, #1cbbb4);
}

.wechat-fallback {
  margin-top: 28rpx;
  margin-bottom: 18rpx;
  text-align: center;
  font-size: 24rpx;
  color: #6b7b8c;
  line-height: 1.7;
}

.agreement-footer {
  margin-top: 28rpx;
  padding: 0 24rpx;
}

.agreement-check {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10rpx;
  font-size: 24rpx;
  color: #5b6b7b;
}

.agreement-check-text {
  color: #5b6b7b;
}

.agreement-links {
  margin-top: 14rpx;
  display: flex;
  justify-content: center;
  gap: 16rpx;
  flex-wrap: wrap;
  font-size: 24rpx;
}

.agreement-link {
  color: #2f6f97;
}
</style>
