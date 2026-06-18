import api from "@/api";
import cache from "@/utils/cache";

const PENDING_ACCORD_KEY = "pending_accord_accept_map";
const ACCORD_RUNTIME_KEY = "accord_accept_runtime_summary";

function normalizeAccordUniques(accordUniques = []) {
  return Array.from(
    new Set(
      (accordUniques || [])
        .map((item) => String(item || "").trim())
        .filter(Boolean),
    ),
  );
}

function getPendingAccordMap() {
  return cache.get(PENDING_ACCORD_KEY, {}) || {};
}

function setPendingAccordMap(map = {}) {
  cache.set(PENDING_ACCORD_KEY, map);
}

function getRuntimeSummary() {
  const summary = cache.get(ACCORD_RUNTIME_KEY, {}) || {};
  return {
    last_scene: summary.last_scene || "",
    last_attempt_status: summary.last_attempt_status || "idle",
    last_attempt_at: summary.last_attempt_at || "",
    last_success_at: summary.last_success_at || "",
    last_failure_at: summary.last_failure_at || "",
    last_error_message: summary.last_error_message || "",
    accords: summary.accords && typeof summary.accords === "object" ? summary.accords : {},
  };
}

function setRuntimeSummary(summary = {}) {
  cache.set(ACCORD_RUNTIME_KEY, summary);
}

function buildErrorMessage(error) {
  if (!error) {
    return "";
  }
  return String(error.msg || error.message || error.errMsg || "").trim();
}

function updateRuntimeSummary(accordUniques = [], scene = "", status = "success", error = null) {
  const summary = getRuntimeSummary();
  const now = new Date().toISOString();
  const nextAccords = {
    ...summary.accords,
  };

  normalizeAccordUniques(accordUniques).forEach((unique) => {
    const prev = nextAccords[unique] || {};
    nextAccords[unique] = {
      last_scene: scene || prev.last_scene || "",
      last_attempt_status: status,
      last_attempt_at: now,
      last_success_at: status === "success" ? now : (prev.last_success_at || ""),
      last_failure_at: status === "fail" ? now : (prev.last_failure_at || ""),
      last_error_message: status === "fail" ? buildErrorMessage(error) : "",
    };
  });

  const nextSummary = {
    ...summary,
    last_scene: scene || summary.last_scene || "",
    last_attempt_status: status,
    last_attempt_at: now,
    last_success_at: status === "success" ? now : summary.last_success_at,
    last_failure_at: status === "fail" ? now : summary.last_failure_at,
    last_error_message: status === "fail" ? buildErrorMessage(error) : "",
    accords: nextAccords,
  };

  setRuntimeSummary(nextSummary);
  return nextSummary;
}

function markPendingAccords(accordUniques = [], scene = "") {
  const map = getPendingAccordMap();
  const now = new Date().toISOString();

  normalizeAccordUniques(accordUniques).forEach((unique) => {
    map[unique] = {
      scene,
      updated_at: now,
    };
  });

  setPendingAccordMap(map);
  return map;
}

function clearPendingAccords(accordUniques = []) {
  const map = getPendingAccordMap();
  normalizeAccordUniques(accordUniques).forEach((unique) => {
    delete map[unique];
  });
  setPendingAccordMap(map);
  return map;
}

export function getPendingAccords() {
  return getPendingAccordMap();
}

export function getAccordRuntimeSummary() {
  const pendingMap = getPendingAccordMap();
  const summary = getRuntimeSummary();
  const pendingAccords = Object.keys(pendingMap);

  return {
    ...summary,
    pending_count: pendingAccords.length,
    pending_accords: pendingAccords,
  };
}

export function bestEffortAcceptAccords(params = {}, options = {}) {
  const {
    toast = false,
    message = "\u534f\u8bae\u8bb0\u5f55\u6682\u672a\u540c\u6b65\uff0c\u5c06\u7ee7\u7eed\u5904\u7406\u5f53\u524d\u64cd\u4f5c",
  } = options;

  const accordUniques = normalizeAccordUniques(params.accord_uniques || []);
  const scene = String(params.scene || "").trim() || "default";

  return api
    .acceptAccords({
      ...params,
      scene,
      accord_uniques: accordUniques,
    })
    .then((res) => {
      clearPendingAccords(accordUniques);
      updateRuntimeSummary(accordUniques, scene, "success");
      return {
        ok: true,
        data: res,
      };
    })
    .catch((error) => {
      markPendingAccords(accordUniques, scene);
      updateRuntimeSummary(accordUniques, scene, "fail", error);
      if (toast) {
        uni.showToast({
          icon: "none",
          title: message,
        });
      }
      return {
        ok: false,
        error,
        pending: true,
      };
    });
}

export function ensureAcceptAccords(params = {}, options = {}) {
  const {
    toast = true,
    message = "\u534f\u8bae\u8bb0\u5f55\u540c\u6b65\u5931\u8d25\uff0c\u8bf7\u7a0d\u540e\u91cd\u8bd5",
  } = options;

  const accordUniques = normalizeAccordUniques(params.accord_uniques || []);
  const scene = String(params.scene || "").trim() || "default";

  return api
    .acceptAccords({
      ...params,
      scene,
      accord_uniques: accordUniques,
    })
    .then((res) => {
      clearPendingAccords(accordUniques);
      updateRuntimeSummary(accordUniques, scene, "success");
      return {
        ok: true,
        data: res,
      };
    })
    .catch((error) => {
      const nextError = {
        ...(error || {}),
        accord_accept_failed: true,
        pending: true,
      };
      markPendingAccords(accordUniques, scene);
      updateRuntimeSummary(accordUniques, scene, "fail", nextError);
      if (toast) {
        uni.showToast({
          icon: "none",
          title: message,
        });
      }
      return Promise.reject(nextError);
    });
}

export function retryPendingAccords() {
  const map = getPendingAccordMap();
  const groups = {};

  Object.keys(map).forEach((unique) => {
    const scene = String((map[unique] && map[unique].scene) || "default");
    if (!groups[scene]) {
      groups[scene] = [];
    }
    groups[scene].push(unique);
  });

  const tasks = Object.keys(groups).map((scene) =>
    api
      .acceptAccords({
        scene,
        accord_uniques: groups[scene],
      })
      .then(() => {
        clearPendingAccords(groups[scene]);
        updateRuntimeSummary(groups[scene], scene, "success");
        return { scene, ok: true };
      })
      .catch((error) => ({
        ...updateRuntimeSummary(groups[scene], scene, "fail", error),
        scene,
        ok: false,
        error,
      })),
  );

  return Promise.all(tasks);
}
