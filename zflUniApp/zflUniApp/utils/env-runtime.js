import cache from "@/utils/cache.js";

const envConfig = require("@/config/env.js");

const PROFILE_STORAGE_KEY = envConfig.storage_key || "env_profile";
const PROD_SWITCH_UNLOCK_KEY = "env_profile_prod_unlock_until";
const PROD_SWITCH_UNLOCK_MINUTES = 15;
let startupHintShown = false;

function getAvailableProfiles() {
  return envConfig.profiles || {};
}

function getAvailableProfileList() {
  return Object.values(getAvailableProfiles() || {});
}

function getProfileReadiness(profile = "") {
  const envInfo = buildEnvInfo(profile, getAvailableProfiles());
  const warnings = getEnvIsolationWarnings(envInfo.key);
  const hasExampleHost =
    isExampleHostname(envInfo.api_root_url) ||
    isExampleHostname(envInfo.base_root_url);

  return {
    ...envInfo,
    warnings,
    has_example_host: hasExampleHost,
    is_hold: hasExampleHost,
    is_ready_for_release:
      envInfo.is_prod ||
      (!hasExampleHost && ["test", "gray"].includes(envInfo.key)),
    status_text: envInfo.is_prod
      ? "READY"
      : hasExampleHost
        ? "HOLD"
        : envInfo.key === "gray"
          ? "GRAY"
          : envInfo.key === "test"
            ? "TEST"
            : "LOCAL",
    short_hint: hasExampleHost
      ? "占位域名，仅可做前端结构核对"
      : envInfo.is_prod
        ? "正式环境，连接真实数据"
        : envInfo.key === "gray"
          ? "灰度环境，可做上线前验收"
          : envInfo.key === "test"
            ? "测试环境，可做联调和写操作验证"
            : "本地或局域网联调环境",
  };
}

function getProfileReadinessList() {
  return getAvailableProfileList().map((item) =>
    getProfileReadiness(item.key || item.env || item.label),
  );
}

function normalizeProfile(profile = "") {
  const key = String(profile || "")
    .trim()
    .toLowerCase();
  const profiles = getAvailableProfiles();
  if (profiles[key]) {
    return key;
  }
  return envConfig.default_profile || envConfig.env || "prod";
}

function extractHostname(url = "") {
  const value = String(url || "").trim();
  if (!value) {
    return "";
  }
  try {
    return new URL(value).hostname.toLowerCase();
  } catch (error) {
    const match = value.match(/^https?:\/\/([^/]+)/i);
    return match && match[1] ? String(match[1]).toLowerCase() : "";
  }
}

function isSameHostname(firstUrl = "", secondUrl = "") {
  const first = extractHostname(firstUrl);
  const second = extractHostname(secondUrl);
  return !!first && !!second && first === second;
}

function isExampleHostname(url = "") {
  const hostname = extractHostname(url);
  return !!hostname && hostname.endsWith(".example.com");
}

function buildEnvInfo(profile = "", profiles = getAvailableProfiles()) {
  const normalized = normalizeProfile(profile);
  const current = profiles[normalized] || profiles.prod || {};

  return {
    key: normalized,
    label: current.label || normalized,
    base_root_url: current.base_root_url || "",
    api_root_url: current.api_root_url || "",
    profile_overrides_enabled: !!envConfig.profile_overrides_enabled,
    is_prod: normalized === "prod",
    is_local_like: ["dev", "local", "lan"].includes(normalized),
    is_non_prod: normalized !== "prod",
    is_safe_for_testing: ["dev", "local", "lan", "test", "gray"].includes(
      normalized,
    ),
  };
}

function getCurrentEnvInfo() {
  const profiles = getAvailableProfiles();
  const overrideProfile = cache.get(PROFILE_STORAGE_KEY, "");
  const profile = normalizeProfile(
    overrideProfile || envConfig.default_profile || envConfig.env,
  );
  return buildEnvInfo(profile, profiles);
}

function getEnvIsolationWarnings(profile = "") {
  const profiles = getAvailableProfiles();
  const envInfo = profile
    ? buildEnvInfo(profile, profiles)
    : getCurrentEnvInfo();
  const currentKey = envInfo.key || normalizeProfile(profile);
  const currentApiRoot = envInfo.api_root_url || "";
  const currentBaseRoot = envInfo.base_root_url || "";
  const prodProfile = profiles.prod || {};
  const localProfile = profiles.local || profiles.dev || {};
  const warnings = [];

  if (!currentApiRoot && !currentBaseRoot) {
    warnings.push("当前环境未配置站点地址和接口地址。");
  }

  if (isExampleHostname(currentApiRoot) || isExampleHostname(currentBaseRoot)) {
    warnings.push(
      `${envInfo.label || currentKey}仍为示例域名，发布前必须替换为真实隔离地址。`,
    );
  }

  if (currentKey === "test" || currentKey === "gray") {
    if (
      isSameHostname(currentApiRoot, localProfile.api_root_url) ||
      isSameHostname(currentBaseRoot, localProfile.base_root_url)
    ) {
      warnings.push(
        `${envInfo.label || currentKey}仍回退到本地地址，尚未接入独立${currentKey === "gray" ? "灰度" : "测试"}环境。`,
      );
    }
  }

  if (
    !envInfo.is_prod &&
    (isSameHostname(currentApiRoot, prodProfile.api_root_url) ||
      isSameHostname(currentBaseRoot, prodProfile.base_root_url))
  ) {
    warnings.push("当前为非正式环境标识，但地址仍指向正式域名。");
  }

  if (envInfo.is_prod) {
    warnings.push("当前环境会直接写入线上真实数据。");
  }

  return warnings;
}

function getEnvIsolationHealth(profile = "") {
  const envInfo = profile
    ? buildEnvInfo(profile, getAvailableProfiles())
    : getCurrentEnvInfo();
  const warnings = getEnvIsolationWarnings(profile || envInfo.key);
  const hasExampleHost =
    isExampleHostname(envInfo.api_root_url) ||
    isExampleHostname(envInfo.base_root_url);
  const readinessStage = envInfo.is_prod
    ? "prod"
    : hasExampleHost
      ? "placeholder"
      : envInfo.key === "gray"
        ? "gray"
        : envInfo.key === "test"
          ? "test"
          : envInfo.is_local_like
            ? "local"
            : "non_prod";
  return {
    ...envInfo,
    warnings,
    has_example_host: hasExampleHost,
    readiness_stage: readinessStage,
    is_isolated_ready: !warnings.length,
    summary: warnings.length ? warnings[0] : "当前环境隔离状态正常。",
  };
}

function isProdEndpointUrl(url = "") {
  const hostname = extractHostname(url);
  if (!hostname) {
    return false;
  }
  const profiles = getAvailableProfiles();
  const prodProfile = profiles.prod || {};
  const prodHosts = [prodProfile.base_root_url, prodProfile.api_root_url]
    .map((item) => extractHostname(item))
    .filter(Boolean);
  return prodHosts.includes(hostname);
}

function getProdSwitchUnlockUntil() {
  return Number(cache.get(PROD_SWITCH_UNLOCK_KEY, 0) || 0);
}

function isProdProfileSwitchUnlocked() {
  return getProdSwitchUnlockUntil() > Date.now();
}

function unlockProdProfileSwitch(minutes = PROD_SWITCH_UNLOCK_MINUTES) {
  const duration = Math.max(1, Number(minutes || PROD_SWITCH_UNLOCK_MINUTES));
  const unlockUntil = Date.now() + duration * 60 * 1000;
  cache.set(PROD_SWITCH_UNLOCK_KEY, unlockUntil);
  return unlockUntil;
}

function clearProdProfileSwitchUnlock() {
  cache.remove(PROD_SWITCH_UNLOCK_KEY);
}

function getEnvSwitchGuard(profile = "") {
  const normalized = normalizeProfile(profile);
  if (normalized !== "prod") {
    return {
      allowed: true,
      requires_unlock: false,
      message: "",
    };
  }

  if (isProdProfileSwitchUnlocked()) {
    return {
      allowed: true,
      requires_unlock: false,
      message: "",
    };
  }

  return {
    allowed: false,
    requires_unlock: true,
    message: "正式环境切换已加锁，请先显式确认解锁，再继续切换。",
  };
}

function setEnvProfile(profile = "", options = {}) {
  const normalized = normalizeProfile(profile);
  const guard = getEnvSwitchGuard(normalized);
  if (!guard.allowed && !options.force_prod) {
    return {
      ...getCurrentEnvInfo(),
      switch_blocked: true,
      switch_message: guard.message,
    };
  }
  cache.set(PROFILE_STORAGE_KEY, normalized);
  if (normalized === "prod") {
    clearProdProfileSwitchUnlock();
  }
  return getCurrentEnvInfo();
}

function clearEnvProfileOverride() {
  cache.remove(PROFILE_STORAGE_KEY);
  clearProdProfileSwitchUnlock();
  return getCurrentEnvInfo();
}

function showEnvStartupHint() {
  const info = getCurrentEnvInfo();
  if (startupHintShown || info.is_prod) {
    return info;
  }

  startupHintShown = true;

  try {
    console.info("[wxapp.env]", {
      profile: info.key,
      label: info.label,
      api_root_url: info.api_root_url,
      base_root_url: info.base_root_url,
    });
  } catch (error) {}

  try {
    uni.showToast({
      icon: "none",
      duration: 2200,
      title: `环境：${info.label}`,
    });
  } catch (error) {}

  return info;
}

export {
  PROFILE_STORAGE_KEY,
  PROD_SWITCH_UNLOCK_KEY,
  getAvailableProfileList,
  getAvailableProfiles,
  getProfileReadiness,
  getProfileReadinessList,
  clearEnvProfileOverride,
  clearProdProfileSwitchUnlock,
  getCurrentEnvInfo,
  getEnvIsolationHealth,
  getEnvIsolationWarnings,
  getEnvSwitchGuard,
  getProdSwitchUnlockUntil,
  isProdEndpointUrl,
  isExampleHostname,
  isSameHostname,
  isProdProfileSwitchUnlocked,
  setEnvProfile,
  showEnvStartupHint,
  unlockProdProfileSwitch,
};
