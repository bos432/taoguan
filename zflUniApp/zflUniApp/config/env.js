const STORAGE_KEY = "env_profile";
const PROD_HOSTS = ["413.chaimen666.com"];
let profileOverrides = {};

const ENV_MAP = {
  dev: {
    key: "dev",
    label: "开发环境",
    base_root_url: "http://127.0.0.1:807",
    api_root_url: "http://127.0.0.1:807/index.php?s=/api",
  },
  local: {
    key: "local",
    label: "本地联调",
    base_root_url: "http://127.0.0.1:807",
    api_root_url: "http://127.0.0.1:807/index.php?s=/api",
  },
  lan: {
    key: "lan",
    label: "局域网联调",
    // 真机调试时，把下面两行改成你电脑当前可访问的局域网 IP
    base_root_url: "http://192.168.1.2:807",
    api_root_url: "http://192.168.1.2:807/index.php?s=/api",
  },
  test: {
    key: "test",
    label: "测试环境",
    // 上线前请替换为独立测试环境，默认回退到本地地址以避免误连正式可写库
    base_root_url: "http://127.0.0.1:807",
    api_root_url: "http://127.0.0.1:807/index.php?s=/api",
  },
  gray: {
    key: "gray",
    label: "灰度环境",
    // 灰度发布请替换为单独灰度入口，默认回退到本地地址以避免误连正式可写库
    base_root_url: "http://127.0.0.1:807",
    api_root_url: "http://127.0.0.1:807/index.php?s=/api",
  },
  prod: {
    key: "prod",
    label: "正式环境",
    base_root_url: "https://413.chaimen666.com",
    api_root_url: "https://413.chaimen666.com/api",
  },
};

function tryLoadProfileOverrides() {
  try {
    // 仅用于本地私有覆盖，不应提交真实测试/灰度地址到公共源码。
    const overrides = require("./env.profile.local.json");
    if (overrides && typeof overrides === "object") {
      profileOverrides = overrides;
    }
  } catch (error) {
    profileOverrides = {};
  }
}

function mergeProfileMap(baseMap = {}, overrideMap = {}) {
  const nextMap = { ...baseMap };
  Object.keys(overrideMap || {}).forEach((key) => {
    const current = baseMap[key] || {};
    const patch = overrideMap[key] || {};
    nextMap[key] = {
      ...current,
      ...patch,
      key: patch.key || current.key || key,
    };
  });
  return nextMap;
}

tryLoadProfileOverrides();
const RESOLVED_ENV_MAP = mergeProfileMap(ENV_MAP, profileOverrides);

function normalizeProfile(profile = "") {
  const key = String(profile || "").trim().toLowerCase();
  return RESOLVED_ENV_MAP[key] ? key : "";
}

function getProcessEnvProfile() {
  try {
    return normalizeProfile(
      process.env.UNI_APP_ENV_PROFILE ||
        process.env.VUE_APP_ENV_PROFILE ||
        process.env.ENV_PROFILE
    );
  } catch (error) {
    return "";
  }
}

function getCurrentHostname() {
  if (typeof window === "undefined" || !window.location) {
    return "";
  }
  return String(window.location.hostname || "").trim().toLowerCase();
}

function isLocalPreviewHost() {
  const hostname = getCurrentHostname();
  return hostname === "127.0.0.1" || hostname === "localhost" || hostname === "::1";
}

function isProdHost() {
  const hostname = getCurrentHostname();
  return !!hostname && PROD_HOSTS.includes(hostname);
}

const isDevelopment = process.env.NODE_ENV === "development";
const explicitProfile = getProcessEnvProfile();
const defaultProfile = explicitProfile ||
  (isDevelopment
    ? "dev"
    : (isLocalPreviewHost()
      ? "local"
      : (isProdHost() ? "prod" : "test")));
const resolvedProfile = RESOLVED_ENV_MAP[defaultProfile] ? defaultProfile : "prod";

module.exports = {
  env: resolvedProfile,
  default_profile: defaultProfile,
  storage_key: STORAGE_KEY,
  profiles: RESOLVED_ENV_MAP,
  profile_overrides_enabled: Object.keys(profileOverrides).length > 0,
  ...RESOLVED_ENV_MAP[resolvedProfile],
  aes_cipher: "AES-128-CBC",
  aes_key: "4th7yt2w9iuvc54c",
  aes_iv: "afdh443b54bu54y5",
};
