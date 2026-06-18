import api from "@/api";
import cache from "@/utils/cache.js";

const MERCHANT_INFO_CACHE_KEY = "global_merchant_info_cache";
const MERCHANT_MODAL_LOCK_KEY = "global_merchant_expire_modal_lock";
const MERCHANT_INFO_CACHE_TTL = 30;
const MERCHANT_MODAL_LOCK_TTL = 3;
const TEXT = {
  unsetExpire: "\u672a\u8bbe\u7f6e",
  expireTitle: "\u5546\u5bb6\u670d\u52a1\u5df2\u5230\u671f",
  expireConfirm: "\u53bb\u7eed\u8d39",
  expireKnow: "\u77e5\u9053\u4e86",
  backToMine: "\u8fd4\u56de\u6211\u7684",
};
const EXCLUDED_ROUTES = [
  "pages/app/my",
  "pages/help/service",
  "pages/merchant/renew",
];

function getCurrentRoute() {
  const pages = getCurrentPages();
  if (!pages || !pages.length) {
    return "";
  }
  const current = pages[pages.length - 1] || {};
  return current.route || "";
}

function normalizeText(value) {
  if (value === undefined || value === null) {
    return "";
  }
  return String(value).trim();
}

function shouldSkipRoute(route = "") {
  if (!route) {
    return true;
  }
  return EXCLUDED_ROUTES.includes(route);
}

function getExpireModalContent(merchant = {}) {
  const expireTime = normalizeText(merchant.expire_time) || TEXT.unsetExpire;
  return `\u60a8\u7684\u5546\u5bb6\u670d\u52a1\u5df2\u4e8e ${expireTime} \u5230\u671f\uff0c\u5f53\u524d\u8d26\u53f7\u6d89\u53ca\u5546\u5bb6\u8eab\u4efd\u7684\u529f\u80fd\u5df2\u6682\u505c\uff0c\u8bf7\u5148\u7eed\u8d39\u540e\u518d\u7ee7\u7eed\u4f7f\u7528\u3002`;
}

function showExpiredModal(merchant = {}, options = {}) {
  const route = options.route || getCurrentRoute();
  if (cache.get(MERCHANT_MODAL_LOCK_KEY)) {
    return;
  }
  cache.set(MERCHANT_MODAL_LOCK_KEY, 1, MERCHANT_MODAL_LOCK_TTL);
  uni.showModal({
    title: TEXT.expireTitle,
    content: getExpireModalContent(merchant),
    confirmText: TEXT.expireConfirm,
    cancelText: shouldSkipRoute(route) ? TEXT.expireKnow : TEXT.backToMine,
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
      if (!shouldSkipRoute(route)) {
        uni.switchTab({
          url: "/pages/app/my",
        });
      }
    },
  });
}

function getCachedMerchantInfo() {
  const payload = cache.get(MERCHANT_INFO_CACHE_KEY, null);
  const token = cache.get("token", "");
  if (!payload || typeof payload !== "object") {
    return null;
  }
  if ((payload.token || "") !== token) {
    cache.remove(MERCHANT_INFO_CACHE_KEY);
    return null;
  }
  return payload.data || null;
}

function setCachedMerchantInfo(merchant) {
  if (!merchant || typeof merchant !== "object") {
    cache.remove(MERCHANT_INFO_CACHE_KEY);
    return;
  }
  cache.set(
    MERCHANT_INFO_CACHE_KEY,
    {
      token: cache.get("token", ""),
      data: merchant,
    },
    MERCHANT_INFO_CACHE_TTL,
  );
}

export function isMerchantExpiredMessage(message = "") {
  return normalizeText(message).indexOf("MERCHANT_EXPIRED") === 0;
}

export function showMerchantExpiredModalByMessage(message = "", route = "") {
  let expireTime = TEXT.unsetExpire;
  if (isMerchantExpiredMessage(message)) {
    const parts = normalizeText(message).split("|");
    if (parts[1]) {
      expireTime = parts[1];
    }
  }
  showExpiredModal({ expire_time: expireTime }, { route });
}

export function syncMerchantExpireOnPageShow() {
  const route = getCurrentRoute();
  if (shouldSkipRoute(route)) {
    return Promise.resolve(null);
  }
  if (!cache.get("token")) {
    return Promise.resolve(null);
  }

  const cached = getCachedMerchantInfo();
  if (cached && Number(cached.is_expired || 0) === 1) {
    showExpiredModal(cached, { route });
    return Promise.resolve(cached);
  }

  return api
    .merchantInfo({})
    .then((res) => {
      const merchant = res.data || {};
      setCachedMerchantInfo(merchant);
      if (merchant.id && Number(merchant.is_expired || 0) === 1) {
        showExpiredModal(merchant, { route });
      }
      return merchant;
    })
    .catch(() => {
      setCachedMerchantInfo(null);
      return null;
    });
}

export function clearMerchantExpireCache() {
  cache.remove(MERCHANT_INFO_CACHE_KEY);
  cache.remove(MERCHANT_MODAL_LOCK_KEY);
}
