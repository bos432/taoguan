const DEFAULT_AUDIT_WHITELIST_ROUTES = [
  "pages/app/home",
  "pages/app/sell",
  "pages/app/my",
  "pages/goods/list",
  "pages/goods/details",
  "pages/goods/trace",
  "pages/goods/my_cart",
  "pages/goods/settlement",
  "pages/home/search",
  "pages/news/list",
  "pages/news/details",
  "pages/order/list",
  "pages/order/logistics",
  "pages/order/evaluate",
  "pages/order/service",
  "pages/merchant/apply",
  "pages/merchant/renew",
  "pages/my/login",
  "pages/system/accord-center",
  "pages/system/accord",
  "pages/help/service",
  "pages/notice/detail",
];

let guardRegistered = false;
let currentAuditMode = false;
let currentWhitelist = DEFAULT_AUDIT_WHITELIST_ROUTES.slice();
let currentNotice = "当前为审核模式，仅开放审核白名单页面";

function normalizeText(value) {
  if (value === undefined || value === null) {
    return "";
  }
  return String(value).trim();
}

function normalizeRoutePath(rawUrl = "") {
  const url = normalizeText(rawUrl);
  if (!url) {
    return "";
  }
  let path = url;
  const queryIndex = path.indexOf("?");
  if (queryIndex >= 0) {
    path = path.slice(0, queryIndex);
  }
  if (path.startsWith("/")) {
    path = path.slice(1);
  }
  return path;
}

function resolveAuditMode(system = {}) {
  return Number(system.audit_mode ?? system.review_mode ?? system.wx_approved ?? 0) === 1;
}

function resolveWhitelist(system = {}) {
  const source = Array.isArray(system.audit_whitelist_routes) ? system.audit_whitelist_routes : [];
  const routes = source
    .map((item) => normalizeRoutePath(item))
    .filter((item) => !!item);
  if (!routes.length) {
    return DEFAULT_AUDIT_WHITELIST_ROUTES.slice();
  }
  return Array.from(new Set(routes));
}

function syncAuditModeStateFromSetting(setting = {}) {
  const system = setting && setting.system ? setting.system : {};
  currentAuditMode = resolveAuditMode(system);
  currentWhitelist = resolveWhitelist(system);
  currentNotice = normalizeText(system.audit_notice) || "当前为审核模式，仅开放审核白名单页面";
}

function isRouteAllowed(rawUrl = "") {
  const path = normalizeRoutePath(rawUrl);
  if (!path) {
    return true;
  }
  if (currentWhitelist.includes(path)) {
    return true;
  }
  if (path === "pages/app/home") {
    return true;
  }
  return false;
}

function blockAndFallback() {
  uni.showToast({
    icon: "none",
    title: currentNotice,
  });
  setTimeout(() => {
    uni.switchTab({
      url: "/pages/app/home",
    });
  }, 80);
}

function createInterceptor() {
  return {
    invoke(args) {
      if (!currentAuditMode) {
        return args;
      }
      const target = args && args.url ? args.url : "";
      if (isRouteAllowed(target)) {
        return args;
      }
      blockAndFallback();
      return false;
    },
  };
}

function registerAuditModeRouteGuard() {
  if (guardRegistered) {
    return;
  }
  guardRegistered = true;
  ["navigateTo", "redirectTo", "reLaunch", "switchTab"].forEach((apiName) => {
    uni.addInterceptor(apiName, createInterceptor());
  });
}

export {
  registerAuditModeRouteGuard,
  syncAuditModeStateFromSetting,
  normalizeRoutePath,
};
