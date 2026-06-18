<script>
import api from "@/api";
import cache from "@/utils/cache";
import store from "@/store/common";
import { applyUiThemeFromCache, applyUiThemeFromSetting } from "@/utils/ui-theme.js";
import { showEnvStartupHint } from "@/utils/env-runtime.js";
import { registerAuditModeRouteGuard, syncAuditModeStateFromSetting } from "@/utils/audit-mode.js";

let latestSettingSyncPromise = null;
let latestSettingSyncAt = 0;
let h5ThemeRefreshBound = false;

function hydrateCachedSetting() {
  const cachedSetting = cache.get("setting") || {};
  if (!cachedSetting || !Object.keys(cachedSetting).length) {
    return;
  }
  store.commit("setSetting", cachedSetting);
  applyUiThemeFromSetting(cachedSetting);
  syncAuditModeStateFromSetting(cachedSetting);
}

function applyLatestSetting(setting = {}) {
  cache.set("setting", setting);
  store.commit("setSetting", setting);
  applyUiThemeFromSetting(setting);
  syncAuditModeStateFromSetting(setting);
  latestSettingSyncAt = Date.now();
}

function syncLatestSetting(force = false) {
  const now = Date.now();
  if (!force && now - latestSettingSyncAt < 1500) {
    return Promise.resolve();
  }

  if (latestSettingSyncPromise) {
    return latestSettingSyncPromise;
  }

  latestSettingSyncPromise = api
    .getSetting()
    .then((res) => {
      applyLatestSetting(res.data || {});
    })
    .catch(() => {})
    .finally(() => {
      latestSettingSyncPromise = null;
    });

  return latestSettingSyncPromise;
}

function bindH5ThemeRefresh() {
  /* #ifdef H5 */
  if (h5ThemeRefreshBound || typeof window === "undefined") {
    return;
  }

  const refresh = () => {
    syncLatestSetting(true);
  };

  window.addEventListener("focus", refresh);
  if (typeof document !== "undefined") {
    document.addEventListener("visibilitychange", () => {
      if (!document.hidden) {
        refresh();
      }
    });
  }
  h5ThemeRefreshBound = true;
  /* #endif */
}

export default {
  onLaunch() {
    registerAuditModeRouteGuard();
    hydrateCachedSetting();
    applyUiThemeFromCache();
    bindH5ThemeRefresh();
    showEnvStartupHint();
    syncLatestSetting(true);
  },
  onShow() {
    hydrateCachedSetting();
    applyUiThemeFromCache();
    syncLatestSetting();
  },
  onHide() {},
};
</script>

<style lang="scss">
/* #ifndef APP-PLUS */
@import "static/colorui/main.css";
@import "static/colorui/icon.css";
@import "static/zaiui/style/app.scss";
/* #endif */
/* #ifdef H5 */
@import "static/zaiui/style/theme-ui-presets.scss";
/* #endif */
/* #ifdef MP-WEIXIN */
@import "static/zaiui/style/theme-ui-presets-mp.scss";
/* #endif */
</style>
