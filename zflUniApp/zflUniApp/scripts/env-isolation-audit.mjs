import fs from "node:fs";
import path from "node:path";
import { createRequire } from "node:module";
import { fileURLToPath } from "node:url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const projectRoot = path.resolve(__dirname, "..");
const reportDir = path.join(projectRoot, "runtime", "env-isolation");
const require = createRequire(import.meta.url);

const envConfig = require(path.join(projectRoot, "config", "env.js"));
const profiles = envConfig.profiles || {};

function ensureDir(dir) {
  fs.mkdirSync(dir, { recursive: true });
}

function hostnameOf(url = "") {
  const value = String(url || "").trim();
  if (!value) return "";
  try {
    return new URL(value).hostname.toLowerCase();
  } catch (error) {
    const match = value.match(/^https?:\/\/([^/]+)/i);
    return match?.[1] ? String(match[1]).toLowerCase() : "";
  }
}

function isLocalHost(hostname = "") {
  return ["127.0.0.1", "localhost", "::1"].includes(hostname);
}

function isExampleHost(hostname = "") {
  return hostname.endsWith(".example.com") || hostname === "example.com";
}

function pickHosts(profileKey) {
  const profile = profiles[profileKey] || {};
  return {
    key: profileKey,
    label: profile.label || profileKey,
    base: profile.base_root_url || "",
    api: profile.api_root_url || "",
    baseHost: hostnameOf(profile.base_root_url),
    apiHost: hostnameOf(profile.api_root_url),
  };
}

const devProfile = pickHosts("dev");
const localProfile = pickHosts("local");
const lanProfile = pickHosts("lan");
const testProfile = pickHosts("test");
const grayProfile = pickHosts("gray");
const prodProfile = pickHosts("prod");

const checks = [];

function addCheck(name, status, message, details = []) {
  checks.push({ name, status, message, details });
}

function compareAgainstProd(profile) {
  if (profile.key === "prod") {
    if (!profile.baseHost || !profile.apiHost) {
      addCheck("prod-sanity", "FAIL", "prod 缺少正式 base/api 地址", [
        `base=${profile.base || "(empty)"}`,
        `api=${profile.api || "(empty)"}`,
      ]);
      return;
    }

    if (isExampleHost(profile.baseHost) || isExampleHost(profile.apiHost)) {
      addCheck("prod-sanity", "FAIL", "prod 仍为示例域名，不能用于正式运营", [
        `base=${profile.base}`,
        `api=${profile.api}`,
      ]);
      return;
    }

    if (isLocalHost(profile.baseHost) || isLocalHost(profile.apiHost)) {
      addCheck("prod-sanity", "FAIL", "prod 仍指向本地地址，存在严重发布风险", [
        `base=${profile.base}`,
        `api=${profile.api}`,
      ]);
      return;
    }

    addCheck("prod-sanity", "PASS", "prod 地址格式正常，可作为隔离对照组", [
      `base=${profile.base}`,
      `api=${profile.api}`,
    ]);
    return;
  }

  const prodHosts = [prodProfile.baseHost, prodProfile.apiHost].filter(Boolean);
  const currentHosts = [profile.baseHost, profile.apiHost].filter(Boolean);
  const overlaps = currentHosts.filter((item) => prodHosts.includes(item));

  if (!currentHosts.length) {
    addCheck(
      `${profile.key}-missing`,
      "FAIL",
      `${profile.key} 缺少 base/api 地址`,
      [`base=${profile.base || "(empty)"}`, `api=${profile.api || "(empty)"}`],
    );
    return;
  }

  if (overlaps.length) {
    addCheck(
      `${profile.key}-vs-prod`,
      "FAIL",
      `${profile.key} 与 prod 共用域名，存在误写正式数据风险`,
      [
        `overlap=${Array.from(new Set(overlaps)).join(", ")}`,
        `prod_base=${prodProfile.base}`,
        `prod_api=${prodProfile.api}`,
        `${profile.key}_base=${profile.base}`,
        `${profile.key}_api=${profile.api}`,
      ],
    );
    return;
  }

  if (isExampleHost(profile.baseHost) || isExampleHost(profile.apiHost)) {
    addCheck(
      `${profile.key}-placeholder`,
      "WARN",
      `${profile.key} 仍为示例域名，隔离安全但还不能用于真实发布`,
      [`base=${profile.base}`, `api=${profile.api}`],
    );
    return;
  }

  if (isLocalHost(profile.baseHost) || isLocalHost(profile.apiHost)) {
    addCheck(
      `${profile.key}-local`,
      "WARN",
      `${profile.key} 当前仍指向本地地址，仅适合本机联调`,
      [`base=${profile.base}`, `api=${profile.api}`],
    );
    return;
  }

  addCheck(
    `${profile.key}-vs-prod`,
    "PASS",
    `${profile.key} 已与 prod 保持域名隔离`,
    [`base=${profile.base}`, `api=${profile.api}`],
  );
}

function compareBetweenNonProd(left, right) {
  const leftHosts = [left.baseHost, left.apiHost].filter(Boolean);
  const rightHosts = [right.baseHost, right.apiHost].filter(Boolean);
  const overlaps = leftHosts.filter((item) => rightHosts.includes(item));

  if (!overlaps.length) {
    addCheck(
      `${left.key}-vs-${right.key}`,
      "PASS",
      `${left.key} 与 ${right.key} 已分离`,
      [
        `${left.key}=${left.base} | ${left.api}`,
        `${right.key}=${right.base} | ${right.api}`,
      ],
    );
    return;
  }

  if (
    isExampleHost(left.baseHost) ||
    isExampleHost(left.apiHost) ||
    isExampleHost(right.baseHost) ||
    isExampleHost(right.apiHost)
  ) {
    addCheck(
      `${left.key}-vs-${right.key}`,
      "WARN",
      `${left.key} 与 ${right.key} 目前仍共用占位域名`,
      [
        `overlap=${Array.from(new Set(overlaps)).join(", ")}`,
        `${left.key}=${left.base} | ${left.api}`,
        `${right.key}=${right.base} | ${right.api}`,
      ],
    );
    return;
  }

  addCheck(
    `${left.key}-vs-${right.key}`,
    "WARN",
    `${left.key} 与 ${right.key} 共用域名，灰度与测试隔离度不足`,
    [
      `overlap=${Array.from(new Set(overlaps)).join(", ")}`,
      `${left.key}=${left.base} | ${left.api}`,
      `${right.key}=${right.base} | ${right.api}`,
    ],
  );
}

function compareDevLocal() {
  const localHosts = [
    devProfile.baseHost,
    devProfile.apiHost,
    localProfile.baseHost,
    localProfile.apiHost,
  ].filter(Boolean);
  const lanHosts = [lanProfile.baseHost, lanProfile.apiHost].filter(Boolean);
  const overlaps = lanHosts.filter((item) => localHosts.includes(item));

  if (overlaps.length) {
    addCheck(
      "lan-vs-local",
      "WARN",
      "lan 与 dev/local 存在域名重叠，局域网联调时请注意环境提示",
      [
        `overlap=${Array.from(new Set(overlaps)).join(", ")}`,
        `lan=${lanProfile.base} | ${lanProfile.api}`,
        `dev=${devProfile.base} | ${devProfile.api}`,
        `local=${localProfile.base} | ${localProfile.api}`,
      ],
    );
    return;
  }

  addCheck("lan-vs-local", "PASS", "lan 与 dev/local 已区分", [
    `lan=${lanProfile.base} | ${lanProfile.api}`,
    `dev=${devProfile.base} | ${devProfile.api}`,
    `local=${localProfile.base} | ${localProfile.api}`,
  ]);
}

compareAgainstProd(testProfile);
compareAgainstProd(grayProfile);
compareAgainstProd(prodProfile);
compareBetweenNonProd(testProfile, grayProfile);
compareDevLocal();

const failCount = checks.filter((item) => item.status === "FAIL").length;
const warnCount = checks.filter((item) => item.status === "WARN").length;
const passCount = checks.filter((item) => item.status === "PASS").length;
const finalStatus = failCount > 0 ? "FAIL" : warnCount > 0 ? "WARN" : "PASS";

function profileReadiness(profile) {
  const hosts = [profile.baseHost, profile.apiHost].filter(Boolean);
  if (!hosts.length) {
    return {
      key: profile.key,
      status: "FAIL",
      message: `${profile.key} 缺少可用地址`,
    };
  }

  if (hosts.some((host) => isExampleHost(host))) {
    return {
      key: profile.key,
      status: "HOLD",
      message: `${profile.key} 仍是占位域名，不能进入真实联调或发布`,
    };
  }

  if (hosts.some((host) => isLocalHost(host))) {
    return {
      key: profile.key,
      status: "LOCAL",
      message: `${profile.key} 当前仅适合本机联调`,
    };
  }

  return {
    key: profile.key,
    status: "READY",
    message: `${profile.key} 地址已具备真实环境联调条件`,
  };
}

const readiness = {
  test: profileReadiness(testProfile),
  gray: profileReadiness(grayProfile),
  prod: profileReadiness(prodProfile),
};

const blockingItems = checks
  .filter((item) => item.status === "FAIL")
  .map((item) => `${item.name}: ${item.message}`);
const warningItems = checks
  .filter((item) => item.status === "WARN")
  .map((item) => `${item.name}: ${item.message}`);
const nextActions = [];

if (readiness.test.status !== "READY") {
  nextActions.push(`test：${readiness.test.message}`);
}
if (readiness.gray.status !== "READY") {
  nextActions.push(`gray：${readiness.gray.message}`);
}
if (readiness.prod.status !== "READY") {
  nextActions.push(`prod：${readiness.prod.message}`);
}
if (!nextActions.length) {
  nextActions.push(
    "测试、灰度、正式环境均已具备隔离条件，可继续做对应包型验证。",
  );
}

const report = {
  generated_at: new Date().toISOString(),
  summary: {
    status: finalStatus,
    pass: passCount,
    warn: warnCount,
    fail: failCount,
  },
  profiles: {
    dev: devProfile,
    local: localProfile,
    lan: lanProfile,
    test: testProfile,
    gray: grayProfile,
    prod: prodProfile,
  },
  readiness,
  blocking_items: blockingItems,
  warning_items: warningItems,
  next_actions: nextActions,
  checks,
};

ensureDir(reportDir);

const jsonPath = path.join(reportDir, "latest.json");
const mdPath = path.join(reportDir, "latest.md");
fs.writeFileSync(jsonPath, `${JSON.stringify(report, null, 2)}\n`, "utf8");

const mdLines = [
  "# Env Isolation Audit",
  "",
  `- Generated At: ${report.generated_at}`,
  `- Summary: ${finalStatus} / PASS ${passCount} / WARN ${warnCount} / FAIL ${failCount}`,
  "",
  "## Readiness",
  "",
  `- test: [${readiness.test.status}] ${readiness.test.message}`,
  `- gray: [${readiness.gray.status}] ${readiness.gray.message}`,
  `- prod: [${readiness.prod.status}] ${readiness.prod.message}`,
  "",
  "## Next Actions",
  "",
  ...nextActions.map((item) => `- ${item}`),
  "",
  "## Checks",
  "",
];

checks.forEach((check) => {
  mdLines.push(`- [${check.status}] ${check.name}: ${check.message}`);
  check.details.forEach((detail) => mdLines.push(`  - ${detail}`));
});

fs.writeFileSync(mdPath, `${mdLines.join("\n")}\n`, "utf8");

console.log(
  `[env-isolation-audit] ${finalStatus} / PASS ${passCount} / WARN ${warnCount} / FAIL ${failCount}`,
);
console.log(`[env-isolation-audit] json=${jsonPath}`);
console.log(`[env-isolation-audit] md=${mdPath}`);
checks.forEach((check) => {
  console.log(`[${check.status}] ${check.name} - ${check.message}`);
  check.details.forEach((detail) => console.log(`  ${detail}`));
});

if (failCount > 0) {
  process.exit(1);
}
