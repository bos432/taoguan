function getEnvIsolationTag(envInfo = {}) {
  if (envInfo.is_prod) {
    return "数据状态：真实写入";
  }
  if (envInfo.key === "gray") {
    return "数据状态：灰度隔离";
  }
  if (envInfo.key === "test") {
    return "数据状态：测试隔离";
  }
  return "数据状态：本地联调";
}

function getEnvReleaseStageText(envHealth = {}) {
  if (envHealth.is_prod) {
    return "发布状态：正式运营";
  }
  if (envHealth.has_example_host) {
    return "发布状态：待配置真实地址";
  }
  if (envHealth.key === "gray") {
    return "发布状态：灰度可验收";
  }
  if (envHealth.key === "test") {
    return "发布状态：测试可联调";
  }
  return "发布状态：开发联调";
}

function getEnvReleaseHint(envHealth = {}) {
  if (envHealth.is_prod) {
    return "当前入口已连接正式环境，任何写操作都应按真实运营动作对待。";
  }
  if (envHealth.has_example_host) {
    return "当前环境仍使用示例域名，只适合做页面结构和前端交互核对，不能进入真实提测或灰度发布。";
  }
  if (envHealth.key === "gray") {
    return "当前环境已具备灰度验收基础，可继续核对关键链路和发布前回退方案。";
  }
  if (envHealth.key === "test") {
    return "当前环境已具备测试联调基础，建议先完成关键写操作和异常回显验证。";
  }
  return "当前环境更适合做本地或局域网联调，建议不要直接作为发布入口。";
}

function getEnvIsolationHint(envInfo = {}) {
  if (envInfo.is_prod) {
    return "正式环境下的提交、删除、发布和下单都会直接影响线上真实数据。";
  }
  if (envInfo.key === "gray") {
    return "建议先在灰度入口验证关键流程，通过后再切正式环境。";
  }
  if (envInfo.key === "test") {
    return "建议先在独立测试库完成关键写操作验收，再进入灰度或正式环境。";
  }
  return "建议先在本地或局域网环境做功能联调，不要把开发包直接接到正式可写库。";
}

function buildEnvConfirmText(envInfo = {}, options = {}) {
  const prodText = String(options.prod || "").trim();
  const testText = String(options.test || "").trim();

  if (envInfo.is_prod) {
    return prodText;
  }

  const label = String(envInfo.label || envInfo.key || "当前环境").trim();
  return `当前为${label}，${testText}`;
}

function buildProdUnlockText(envInfo = {}, options = {}) {
  const hostText = String(options.host || "").trim();
  const label = String(envInfo.label || envInfo.key || "当前环境").trim();
  return `${label}准备切换到正式环境。${hostText ? `\n目标地址：${hostText}` : ""}\n该操作会解锁正式环境切换，请确认当前不是测试联调场景。`;
}

export {
  buildProdUnlockText,
  buildEnvConfirmText,
  getEnvIsolationHint,
  getEnvIsolationTag,
  getEnvReleaseHint,
  getEnvReleaseStageText,
};
