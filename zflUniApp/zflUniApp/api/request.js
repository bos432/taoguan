import store from "@/store/common.js";
import cache from "@/utils/cache.js";
import { getCurrentEnvInfo, isProdEndpointUrl } from "@/utils/env-runtime.js";
import { buildLoginRedirectUrl, setLoginRedirect } from "@/utils/login-redirect.js";

const PENDING_ACCORD_KEY = "pending_accord_accept_map";
const MERCHANT_IDENTITY_KEY = "current_merchant_identity_mer_user_id";
const crypto = require("@/utils/crypto.js");

const TEXT = {
  unsetExpire: "\u672a\u8bbe\u7f6e",
  expireTitle: "\u5546\u5bb6\u670d\u52a1\u5df2\u5230\u671f",
  expireConfirm: "\u53bb\u7eed\u8d39",
  expireKnow: "\u77e5\u9053\u4e86",
  backToMine: "\u8fd4\u56de\u6211\u7684",
  warmPrompt: "\u6e29\u99a8\u63d0\u793a",
  prompt: "\u63d0\u793a",
  loginBanned: "\u7981\u6b62\u767b\u5f55",
  requestFailed: "\u8bf7\u6c42\u5931\u8d25",
  requestFailedWithHost: "\u8bf7\u6c42\u5931\u8d25\uff0c\u8bf7\u68c0\u67e5\u63a5\u53e3\u5730\u5740\u548c\u672c\u5730\u670d\u52a1",
  envGuardBlocked: "\u5f53\u524d\u4e3a\u975e\u6b63\u5f0f\u73af\u5883\uff0c\u5df2\u62e6\u622a\u6307\u5411\u6b63\u5f0f\u57df\u540d\u7684\u8bf7\u6c42",
};

let retryingPendingAccords = false;

function getApiRootUrl() {
  const info = getCurrentEnvInfo();
  return info.api_root_url;
}

function getCurrentRoute() {
  const pages = getCurrentPages();
  if (!pages || !pages.length) {
    return "";
  }
  const current = pages[pages.length - 1] || {};
  return current.route || "";
}

function getCurrentRouteUrl() {
  const pages = getCurrentPages();
  if (!pages || !pages.length) {
    return "";
  }

  const current = pages[pages.length - 1] || {};
  const route = normalizeText(current.route || "");
  if (!route) {
    return "";
  }

  const options = current.options || {};
  const query = Object.keys(options)
    .filter((key) => normalizeText(options[key]))
    .map((key) => `${encodeURIComponent(key)}=${encodeURIComponent(options[key])}`)
    .join("&");

  return `/${route}${query ? `?${query}` : ""}`;
}

function navigateToLoginWithRedirect() {
  const redirectUrl = getCurrentRouteUrl();
  if (redirectUrl) {
    setLoginRedirect(redirectUrl);
  }
  uni.navigateTo({
    url: buildLoginRedirectUrl(redirectUrl),
  });
}

function normalizeText(value) {
  if (value === undefined || value === null) {
    return "";
  }
  return String(value).trim();
}

function getRequestFailedMessage() {
  if (getCurrentEnvInfo().is_local_like) {
    return TEXT.requestFailedWithHost;
  }
  return TEXT.requestFailed;
}

function isReviewModeEnabled() {
  const setting = cache.get("setting", null);
  const system = setting && setting.system ? setting.system : {};
  return Number(system.review_mode || system.wx_approved || 0) === 1;
}

function isMerchantExpiredMessage(message = "") {
  return normalizeText(message).indexOf("MERCHANT_EXPIRED") === 0;
}

function getPendingAccordMap() {
  return cache.get(PENDING_ACCORD_KEY, {}) || {};
}

function setPendingAccordMap(map = {}) {
  cache.set(PENDING_ACCORD_KEY, map);
}

function buildPayload(data = {}) {
  const payload = {
    ...data,
    request_timestamp: Date.now(),
  };

  return payload;
}

function normalRequest(url, data = {}, method = "GET") {
  return new Promise((resolve, reject) => {
    const apiRootUrl = getApiRootUrl();
    const envInfo = getCurrentEnvInfo();
    if (envInfo.is_non_prod && isProdEndpointUrl(apiRootUrl)) {
      try {
        console.error("[wxapp.env.guard.blocked]", {
          url: apiRootUrl + url,
          method: method.toLocaleUpperCase(),
          env: envInfo,
        });
      } catch (error) {}
      reject({
        env_guard_blocked: true,
        message: TEXT.envGuardBlocked,
      });
      return;
    }
    uni.request({
      url: apiRootUrl + url,
      data,
      method: method.toLocaleUpperCase(),
      header: {
        "Content-Type": "application/json",
        ApiToken: cache.get("token", ""),
        MerchantUserId: cache.get(MERCHANT_IDENTITY_KEY, ""),
      },
      success: (res) => {
        resolve(res);
      },
      fail: (err) => {
        try {
          console.error("[wxapp.request.fail]", {
            url: apiRootUrl + url,
            method: method.toLocaleUpperCase(),
            err,
            env: getCurrentEnvInfo(),
          });
        } catch (error) {}
        reject(err);
      },
    });
  });
}

function decryptResponseIfNeeded(response) {
  if (response.data && response.data.crypt_status && response.data.crypt_status == 1) {
    response.data.data = crypto.aesDecrypt(response.data.data);
  }
  return response;
}

function showMerchantExpiredModalByMessage(message = "", route = "") {
  const parts = normalizeText(message).split("|");
  const expireTime = parts[1] || TEXT.unsetExpire;
  if (cache.get("global_merchant_expire_modal_lock")) {
    return;
  }
  cache.set("global_merchant_expire_modal_lock", 1, 3);
  uni.showModal({
    title: TEXT.expireTitle,
    content: `\u60a8\u7684\u5546\u5bb6\u670d\u52a1\u5df2\u4e8e ${expireTime} \u5230\u671f\uff0c\u5f53\u524d\u8d26\u53f7\u6d89\u53ca\u5546\u5bb6\u8eab\u4efd\u7684\u529f\u80fd\u5df2\u6682\u505c\uff0c\u8bf7\u5148\u7eed\u8d39\u540e\u518d\u7ee7\u7eed\u4f7f\u7528\u3002`,
    confirmText: TEXT.expireConfirm,
    cancelText:
      route === "pages/app/my" ||
      route === "pages/help/service" ||
      route === "pages/merchant/renew"
        ? TEXT.expireKnow
        : TEXT.backToMine,
    success: (res) => {
      if (res.confirm) {
        if (route === "pages/merchant/renew") {
          return;
        }
        uni.navigateTo({
          url: "/pages/merchant/renew",
        });
        return;
      }
      if (
        route !== "pages/app/my" &&
        route !== "pages/help/service" &&
        route !== "pages/merchant/renew"
      ) {
        uni.switchTab({
          url: "/pages/app/my",
        });
      }
    },
  });
}

function flushPendingAccords() {
  const token = cache.get("token", "");
  const pendingMap = getPendingAccordMap();
  const pendingKeys = Object.keys(pendingMap);

  if (!token || !pendingKeys.length || retryingPendingAccords) {
    return;
  }

  retryingPendingAccords = true;
  const sceneGroups = {};

  pendingKeys.forEach((unique) => {
    const scene = String((pendingMap[unique] && pendingMap[unique].scene) || "default");
    if (!sceneGroups[scene]) {
      sceneGroups[scene] = [];
    }
    sceneGroups[scene].push(unique);
  });

  const tasks = Object.keys(sceneGroups).map((scene) =>
    normalRequest(
      "/setting.Accord/accept",
      buildPayload({
        scene,
        accord_uniques: sceneGroups[scene],
      }),
      "POST",
    )
      .then((res) => decryptResponseIfNeeded(res))
      .then((res) => {
        if (res.data && res.data.code === 200) {
          const latestMap = getPendingAccordMap();
          sceneGroups[scene].forEach((unique) => {
            delete latestMap[unique];
          });
          setPendingAccordMap(latestMap);
        }
      })
      .catch(() => {})
  );

  Promise.all(tasks).finally(() => {
    retryingPendingAccords = false;
  });
}

function request(url = "", data = {}, method = "GET") {
  return new Promise((resolve, reject) => {
    const payload = buildPayload(data);

    normalRequest(url, payload, method)
      .then((res) => decryptResponseIfNeeded(res))
      .then((res) => {
        if (res.data && res.data.code && res.data.code == 200) {
          if (url !== "/setting.Accord/accept") {
            flushPendingAccords();
          }
          resolve(res.data);
        } else if (res.data.code == 400) {
          if (isMerchantExpiredMessage(res.data.msg || "")) {
            showMerchantExpiredModalByMessage(res.data.msg || "", getCurrentRoute());
            reject({
              ...res.data,
              merchant_expired: true,
            });
            return;
          }
          uni.showToast({
            icon: "none",
            duration: 3000,
            title: res.data.msg,
          });
          reject(res.data);
        } else if (res.data.code == 401) {
          store.commit("clearUserInfo");
          if (isReviewModeEnabled()) {
            reject(res.data);
            return;
          }
          uni.showModal({
            title: TEXT.warmPrompt,
            showCancel: false,
            content: res.data.msg,
            success: function (modalRes) {
              if (modalRes.confirm) {
                navigateToLoginWithRedirect();
              }
            },
          });
        } else if (res.data.code == -3) {
          uni.hideToast();
          reject(res.data);
        } else if (res.data.code == -4) {
          uni.hideToast();
          store.commit("clearUserInfo");
          uni.showModal({
            title: TEXT.prompt,
            content: TEXT.loginBanned,
            showCancel: false,
            success: function (modalRes) {
              if (modalRes.confirm) {
                navigateToLoginWithRedirect();
              }
            },
          });
        } else {
          uni.hideToast();
          let msg = getRequestFailedMessage();
          if (res.data && res.data.msg) {
            msg = res.data.msg;
          }
          uni.showToast({
            icon: "none",
            duration: 3000,
            title: msg,
          });
          reject(res.data);
        }
      })
      .catch((err) => {
        uni.hideToast();
        uni.showToast({
          icon: "none",
          duration: 3000,
          title: err && err.env_guard_blocked ? TEXT.envGuardBlocked : getRequestFailedMessage(),
        });
        reject(err);
      });
  });
}

export default request;
