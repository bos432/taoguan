import { getCurrentEnvInfo } from "@/utils/env-runtime.js";

function normalizeUrl(url = "") {
  return String(url || "").trim();
}

function trimRightSlash(url = "") {
  return normalizeUrl(url).replace(/\/+$/, "");
}

function trimLeftSlash(url = "") {
  return normalizeUrl(url).replace(/^\/+/, "");
}

function ensureLeadingSlash(url = "") {
  const normalized = normalizeUrl(url);
  if (!normalized) {
    return "";
  }
  return normalized.indexOf("/") === 0 ? normalized : `/${trimLeftSlash(normalized)}`;
}

function joinUrl(base = "", path = "") {
  const normalizedBase = trimRightSlash(base);
  const normalizedPath = trimLeftSlash(path);

  if (!normalizedBase) {
    return normalizedPath ? `/${normalizedPath}` : "";
  }

  if (!normalizedPath) {
    return normalizedBase;
  }

  return `${normalizedBase}/${normalizedPath}`;
}

function getBaseRootUrl() {
  return getCurrentEnvInfo().base_root_url || "";
}

function getApiRootUrl() {
  return getCurrentEnvInfo().api_root_url || "";
}

function buildApiUrl(path = "") {
  return joinUrl(getApiRootUrl(), path);
}

function buildWebUrl(path = "") {
  return joinUrl(getBaseRootUrl(), path);
}

function isLocalStaticUrl(url = "") {
  return /^(\/)?static\//i.test(normalizeUrl(url));
}

function getRuntimeProtocol() {
  try {
    if (typeof window !== "undefined" && window.location && window.location.protocol) {
      return normalizeUrl(window.location.protocol).replace(/:$/, "").toLowerCase();
    }
  } catch (error) {}

  return "";
}

function getPreferredProtocol() {
  const runtimeProtocol = getRuntimeProtocol();
  if (runtimeProtocol === "https") {
    return "https";
  }

  const baseRootUrl = getBaseRootUrl();
  if (/^https:\/\//i.test(baseRootUrl)) {
    return "https";
  }

  if (runtimeProtocol === "http") {
    return "http";
  }

  if (/^http:\/\//i.test(baseRootUrl)) {
    return "http";
  }

  return "";
}

function resolveResourceUrl(url = "", fallback = "", options = {}) {
  const normalized = normalizeUrl(url);
  const safeFallback = normalizeUrl(fallback);

  if (!normalized) {
    return isLocalStaticUrl(safeFallback) ? ensureLeadingSlash(safeFallback) : safeFallback;
  }

  if (isLocalStaticUrl(normalized)) {
    return ensureLeadingSlash(normalized);
  }

  if (/^(data:|blob:|file:|wxfile:)/i.test(normalized)) {
    return normalized;
  }

  if (/^[a-z][a-z0-9+.-]*:/i.test(normalized) && !/^https?:/i.test(normalized)) {
    return normalized;
  }

  const baseUrl = normalizeUrl(options.baseUrl || getBaseRootUrl());
  const preferredProtocol = normalizeUrl(options.preferProtocol || getPreferredProtocol()).toLowerCase();
  let resolvedUrl = normalized;

  if (/^\/\//.test(resolvedUrl)) {
    return `${preferredProtocol || "https"}:${resolvedUrl}`;
  }

  if (/^http:\/\//i.test(resolvedUrl) && preferredProtocol === "https") {
    return resolvedUrl.replace(/^http:\/\//i, "https://");
  }

  if (/^https?:\/\//i.test(resolvedUrl)) {
    return resolvedUrl;
  }

  if (resolvedUrl.indexOf("/") === 0) {
    return baseUrl ? joinUrl(baseUrl, resolvedUrl) : resolvedUrl;
  }

  return baseUrl ? joinUrl(baseUrl, resolvedUrl) : resolvedUrl;
}

function resolveImageUrl(url = "", fallback = "", options = {}) {
  return resolveResourceUrl(url, fallback, options);
}

function extractUrlList(list = [], field = "file_url") {
  if (!Array.isArray(list)) {
    return [];
  }

  return list
    .map((item) => {
      if (!item || typeof item !== "object") {
        return "";
      }
      return normalizeUrl(item[field] || "");
    })
    .filter(Boolean);
}

function resolveAdminNextUrl(adminNextHost = "") {
  const explicitHost = normalizeUrl(adminNextHost);
  if (explicitHost) {
    return explicitHost;
  }

  const baseRootUrl = getBaseRootUrl();
  return baseRootUrl ? `${trimRightSlash(baseRootUrl)}/admin-next/` : "/admin-next/";
}

export {
  buildApiUrl,
  buildWebUrl,
  extractUrlList,
  getApiRootUrl,
  getBaseRootUrl,
  joinUrl,
  normalizeUrl,
  resolveAdminNextUrl,
  resolveImageUrl,
  resolveResourceUrl,
};
