import cache from "@/utils/cache.js";

const LOGIN_REDIRECT_CACHE_KEY = "login_redirect_runtime";

function normalizeRedirectUrl(url = "") {
  return String(url || "").trim();
}

function setLoginRedirect(url = "", ttl = 3600) {
  const redirectUrl = normalizeRedirectUrl(url);
  if (!redirectUrl) {
    return "";
  }
  cache.set(LOGIN_REDIRECT_CACHE_KEY, redirectUrl, ttl);
  return redirectUrl;
}

function getLoginRedirect(def = "") {
  return normalizeRedirectUrl(cache.get(LOGIN_REDIRECT_CACHE_KEY, def));
}

function clearLoginRedirect() {
  cache.remove(LOGIN_REDIRECT_CACHE_KEY);
}

function consumeLoginRedirect(def = "") {
  const redirectUrl = getLoginRedirect(def);
  clearLoginRedirect();
  return redirectUrl;
}

function buildLoginRedirectUrl(targetUrl = "", loginPath = "/pages/my/login") {
  const redirectUrl = normalizeRedirectUrl(targetUrl);
  if (!redirectUrl) {
    return loginPath;
  }
  return `${loginPath}?redirect=${encodeURIComponent(redirectUrl)}`;
}

export {
  LOGIN_REDIRECT_CACHE_KEY,
  buildLoginRedirectUrl,
  clearLoginRedirect,
  consumeLoginRedirect,
  getLoginRedirect,
  setLoginRedirect,
};
