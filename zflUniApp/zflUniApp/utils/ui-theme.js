import cache from "@/utils/cache.js";

export const UI_THEME_STORAGE_KEY = "tg_ui_theme_style";

export const UI_THEME_META = {
  origin: {
    navBackground: "#ffffff",
    navFrontColor: "#000000",
    tabBackground: "#ffffff",
    tabColor: "#505050",
    tabSelectedColor: "#e54d42",
  },
  red_energy: {
    navBackground: "#a30c15",
    navFrontColor: "#ffffff",
    tabBackground: "#fff4ee",
    tabColor: "#b57d74",
    tabSelectedColor: "#d8242a",
  },
  yellow_energy: {
    navBackground: "#f4c53f",
    navFrontColor: "#000000",
    tabBackground: "#fff7dc",
    tabColor: "#8f6320",
    tabSelectedColor: "#b51f12",
  },
  jade_modern: {
    navBackground: "#164c43",
    navFrontColor: "#ffffff",
    tabBackground: "#f5efe2",
    tabColor: "#7f7466",
    tabSelectedColor: "#1b6f63",
  },
  ocean_sky: {
    navBackground: "#0f4c81",
    navFrontColor: "#ffffff",
    tabBackground: "#eef6ff",
    tabColor: "#5d7594",
    tabSelectedColor: "#1188c9",
  },
  ink_gold: {
    navBackground: "#1b1c22",
    navFrontColor: "#ffffff",
    tabBackground: "#f6f1e7",
    tabColor: "#756956",
    tabSelectedColor: "#b8892d",
  },
  rose_dawn: {
    navBackground: "#a94f6f",
    navFrontColor: "#ffffff",
    tabBackground: "#fff2f5",
    tabColor: "#8c6671",
    tabSelectedColor: "#d56c8f",
  },
};

export function normalizeUiThemeStyle(style = "") {
  const value = String(style || "").trim();
  if (Object.prototype.hasOwnProperty.call(UI_THEME_META, value)) {
    return value;
  }
  return "origin";
}

export function resolveUiThemeStyle(setting = {}) {
  const system = setting.system || setting || {};
  return normalizeUiThemeStyle(system.ui_theme_style || cache.get(UI_THEME_STORAGE_KEY) || "origin");
}

function applyUiThemeDom(style) {
  /* #ifdef H5 */
  if (typeof document !== "undefined") {
    document.documentElement.setAttribute("data-ui-theme", style);
    if (document.body) {
      document.body.setAttribute("data-ui-theme", style);
    }
  }
  /* #endif */
}

function applyUiThemeChrome(style) {
  const meta = UI_THEME_META[style] || UI_THEME_META.origin;

  if (typeof uni !== "undefined" && uni.setTabBarStyle) {
    uni.setTabBarStyle({
      backgroundColor: meta.tabBackground,
      color: meta.tabColor,
      selectedColor: meta.tabSelectedColor,
      borderStyle: "white",
    });
  }

  if (typeof uni !== "undefined" && uni.setNavigationBarColor) {
    uni.setNavigationBarColor({
      backgroundColor: meta.navBackground,
      frontColor: meta.navFrontColor,
      animation: {
        duration: 0,
        timingFunc: "linear",
      },
    });
  }
}

export function applyUiTheme(style = "origin") {
  const themeStyle = normalizeUiThemeStyle(style);
  cache.set(UI_THEME_STORAGE_KEY, themeStyle);
  applyUiThemeDom(themeStyle);
  applyUiThemeChrome(themeStyle);
  return themeStyle;
}

export function applyUiThemeFromSetting(setting = {}) {
  return applyUiTheme(resolveUiThemeStyle(setting));
}

export function applyUiThemeFromCache() {
  const setting = cache.get("setting") || {};
  return applyUiTheme(resolveUiThemeStyle(setting));
}
