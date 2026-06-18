import fs from "node:fs";
import path from "node:path";
import { spawnSync } from "node:child_process";
import { fileURLToPath } from "node:url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const projectRoot = path.resolve(__dirname, "..");
const reportDir = path.join(projectRoot, "runtime", "release-preflight");

const profile = String(process.argv[2] || "")
  .trim()
  .toLowerCase();

if (!["test", "gray", "prod"].includes(profile)) {
  console.error(
    "[release-preflight] usage: node ./scripts/release-preflight.mjs <test|gray|prod>",
  );
  process.exit(1);
}

function ensureDir(dir) {
  fs.mkdirSync(dir, { recursive: true });
}

function runStep(name, command, args = []) {
  const result = spawnSync(command, args, {
    cwd: projectRoot,
    env: {
      ...process.env,
      UNI_APP_ENV_PROFILE: profile,
    },
    shell: false,
    encoding: "utf8",
  });

  const stdout = String(result.stdout || "").trim();
  const stderr = String(result.stderr || "").trim();
  const combined = [stdout, stderr].filter(Boolean).join("\n");

  return {
    name,
    command: [command, ...args].join(" "),
    exitCode: result.status ?? 1,
    status: (result.status ?? 1) === 0 ? "PASS" : "FAIL",
    output: combined,
  };
}

const steps = [
  runStep("agreement-flow-audit", process.execPath, [
    "./scripts/agreement-flow-audit.mjs",
  ]),
  runStep("runtime-readiness", process.execPath, [
    "./scripts/runtime-readiness-report.mjs",
  ]),
  runStep("env-local-check", process.execPath, [
    "./scripts/validate-env-profile-local.mjs",
  ]),
  runStep("env-status", process.execPath, ["./scripts/env-status-report.mjs"]),
  runStep("env-isolation-audit", process.execPath, [
    "./scripts/env-isolation-audit.mjs",
  ]),
  runStep(`release-check-${profile}`, process.execPath, [
    "./scripts/validate-env-config.mjs",
    profile,
    "--strict",
  ]),
];

const passCount = steps.filter((item) => item.status === "PASS").length;
const failCount = steps.filter((item) => item.status === "FAIL").length;
const finalStatus = failCount > 0 ? "FAIL" : "PASS";

const isolationJson = path.join(
  projectRoot,
  "runtime",
  "env-isolation",
  "latest.json",
);
let isolationSummary = null;
if (fs.existsSync(isolationJson)) {
  try {
    const isolation = JSON.parse(fs.readFileSync(isolationJson, "utf8"));
    isolationSummary = {
      status: isolation.summary?.status || "UNKNOWN",
      test: isolation.readiness?.test || null,
      gray: isolation.readiness?.gray || null,
      prod: isolation.readiness?.prod || null,
      next_actions: isolation.next_actions || [],
    };
  } catch (error) {
    isolationSummary = {
      status: "ERROR",
      message: `无法解析环境隔离报告：${error.message}`,
    };
  }
}

ensureDir(reportDir);

const report = {
  generated_at: new Date().toISOString(),
  profile,
  project_root: projectRoot,
  summary: {
    status: finalStatus,
    pass: passCount,
    fail: failCount,
  },
  data_isolation: isolationSummary,
  next_actions:
    finalStatus === "PASS"
      ? [
          `${profile} 发布前源码预检已通过，可进入 HBuilderX 对应包型验证。`,
          ...(isolationSummary?.next_actions || []),
        ]
      : [
          profile === "test"
            ? "补齐 test 的真实 base/api 地址后重跑 release:preflight:test。"
            : profile === "gray"
              ? "补齐 gray 的真实 base/api 地址后重跑 release:preflight:gray。"
              : "先修复 prod 环境校验失败项，再继续正式发布。",
        ],
  steps,
};

const jsonPath = path.join(reportDir, `${profile}.latest.json`);
const mdPath = path.join(reportDir, `${profile}.latest.md`);
fs.writeFileSync(jsonPath, `${JSON.stringify(report, null, 2)}\n`, "utf8");

const mdLines = [
  "# Uni-app Release Preflight",
  "",
  `- Generated At: ${report.generated_at}`,
  `- Profile: ${profile}`,
  `- Summary: ${finalStatus} / PASS ${passCount} / FAIL ${failCount}`,
  "",
  "## Data Isolation",
  "",
  isolationSummary
    ? `- Status: ${isolationSummary.status}`
    : "- Status: 未生成环境隔离摘要",
  ...(isolationSummary?.test
    ? [
        `- test: [${isolationSummary.test.status}] ${isolationSummary.test.message}`,
      ]
    : []),
  ...(isolationSummary?.gray
    ? [
        `- gray: [${isolationSummary.gray.status}] ${isolationSummary.gray.message}`,
      ]
    : []),
  ...(isolationSummary?.prod
    ? [
        `- prod: [${isolationSummary.prod.status}] ${isolationSummary.prod.message}`,
      ]
    : []),
  ...(isolationSummary?.message ? [`- ${isolationSummary.message}`] : []),
  "",
  "## Next Actions",
  "",
  ...report.next_actions.map((item) => `- ${item}`),
  "",
];

steps.forEach((step) => {
  mdLines.push(`## [${step.status}] ${step.name}`);
  mdLines.push("");
  mdLines.push(`Command: \`${step.command}\``);
  mdLines.push("");
  if (step.output) {
    mdLines.push("```text");
    mdLines.push(step.output);
    mdLines.push("```");
    mdLines.push("");
  }
});

fs.writeFileSync(mdPath, `${mdLines.join("\n")}\n`, "utf8");

console.log(
  `[release-preflight] ${finalStatus} profile=${profile} pass=${passCount} fail=${failCount}`,
);
console.log(`[release-preflight] json=${jsonPath}`);
console.log(`[release-preflight] md=${mdPath}`);
steps.forEach((step) => {
  console.log(`[${step.status}] ${step.name}`);
  if (step.output) {
    console.log(step.output);
  }
});

if (failCount > 0) {
  process.exit(1);
}
